<?php
if ( ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo local.' ); }
$id = 0; $count = 0; $ip = $_SERVER['REMOTE_ADDR'] ?? null;
$email = 'ftuy-session-' . wp_generate_uuid4() . '@example.invalid';
$_SERVER['REMOTE_ADDR'] = 'fixture-session-' . wp_generate_uuid4();
// Loginizer caches the IP before eval-file starts. Give fixtures their own bucket.
global $loginizer;
$old_loginizer_ip = $loginizer['current_ip'] ?? null;
if ( isset( $loginizer ) ) { $loginizer['current_ip'] = $_SERVER['REMOTE_ADDR']; }
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
$login = function ( $password ) use ( $email ) { $r = new WP_REST_Request( 'POST' ); $r['email'] = $email; $r['password'] = $password; return FTUY_App_Sessions::login( $r ); };
$auth = function ( $token ) { $r = new WP_REST_Request( 'GET' ); $r->set_header( 'authorization', 'Bearer ' . $token ); return $r; };
try {
    $pass = wp_generate_password( 30 );
    $id = wp_insert_user( array( 'user_login' => 'fixture-' . wp_generate_uuid4(), 'user_email' => $email, 'user_pass' => $pass, 'role' => 'subscriber', 'display_name' => 'Fixture', 'meta_input' => array( 'ftuy_account_status' => 'email_pending' ) ) );
    if ( is_wp_error( $id ) ) { throw new RuntimeException( 'Fixture.' ); }
    $assert( is_wp_error( $login( $pass ) ), 'Pendiente no ingresa.' );
    update_user_meta( $id, 'ftuy_account_status', 'active' );
    $assert( is_wp_error( $login( 'wrong' ) ), 'Contraseña incorrecta rechazada.' );
    $result = $login( $pass ); $assert( $result instanceof WP_REST_Response, 'Ingreso válido.' );
    $data = $result->get_data(); $token = $data['token'];
    $assert( $data['user']['id'] === $id && ! isset( $data['user']['legacy_id'], $data['user']['user_pass'] ), 'Perfil sin datos internos.' );
    $assert( ! is_wp_error( FTUY_App_Sessions::session( $auth( $token ) ) ), 'Sesión válida.' );
    $assert( is_wp_error( FTUY_App_Sessions::session( $auth( $id . '.' . str_repeat( '0', 64 ) ) ) ), 'Token falso rechazado.' );
    $meta = get_user_meta( $id ); $assert( strpos( serialize( $meta ), explode( '.', $token )[1] ) === false, 'Secreto no guardado en texto claro.' );
    FTUY_App_Sessions::logout( $auth( $token ) );
    $assert( is_wp_error( FTUY_App_Sessions::session( $auth( $token ) ) ), 'Logout revoca.' );
    $token = $login( $pass )->get_data()['token'];
    $key = FTUY_App_Sessions::PREFIX . hash( 'sha256', explode( '.', $token )[1] );
    $row = get_user_meta( $id, $key, true ); $row['expires'] = time() - 1; update_user_meta( $id, $key, $row );
    $assert( is_wp_error( FTUY_App_Sessions::session( $auth( $token ) ) ), 'Sesión vencida rechazada.' );
    $token = $login( $pass )->get_data()['token'];
    update_user_meta( $id, 'ftuy_account_status', 'suspended' );
    $assert( is_wp_error( FTUY_App_Sessions::session( $auth( $token ) ) ), 'Estado no activo bloquea sesión.' );
    update_user_meta( $id, 'ftuy_account_status', 'active' );
    wp_set_password( wp_generate_password( 30 ), $id );
    $assert( is_wp_error( FTUY_App_Sessions::session( $auth( $token ) ) ), 'Cambio contraseña revoca.' );
    $pass = wp_generate_password( 30 ); wp_set_password( $pass, $id );
    get_userdata( $id )->set_role( 'administrator' );
    $assert( is_wp_error( $login( $pass ) ), 'No emite sesiones administrativas.' );
    $r = new WP_REST_Request( 'GET', '/foodtrucks-uy/v1/accounts/session' );
    $response = rest_get_server()->dispatch( $r );
    $response = apply_filters( 'rest_post_dispatch', $response, rest_get_server(), $r );
    $assert( $response->get_status() === 401 && $response->get_headers()['Cache-Control'] === 'no-store, private', 'REST sin sesión: 401 no cacheable.' );
    echo "OK: $count comprobaciones de sesión app.\n";
} finally {
    require_once ABSPATH . 'wp-admin/includes/user.php'; if ( $id && ! is_wp_error( $id ) ) { wp_delete_user( $id ); }
    foreach ( array( 'app-login-ip-' . $_SERVER['REMOTE_ADDR'], 'app-login-email-' . $email ) as $bucket ) { delete_transient( 'ftuy_ac_' . hash_hmac( 'sha256', $bucket, wp_salt( 'auth' ) ) ); }
    if ( $ip === null ) { unset( $_SERVER['REMOTE_ADDR'] ); } else { $_SERVER['REMOTE_ADDR'] = $ip; }
    if ( isset( $loginizer ) ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'loginizer_logs', array( 'ip' => $loginizer['current_ip'] ) );
        $loginizer['current_ip'] = $old_loginizer_ip;
    }
}
