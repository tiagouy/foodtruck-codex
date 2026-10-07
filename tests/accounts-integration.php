<?php
if ( ! defined( 'ABSPATH' ) || ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo en local.' ); }
$count = 0; $ids = array(); $old_user = get_current_user_id(); $old_ip = $_SERVER['REMOTE_ADDR'] ?? null;
$old_registration = get_option( 'ftuy_registration_enabled', null );
$emails = array(); $legacy = random_int( 800000000, 900000000 );
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
try {
    $_SERVER['REMOTE_ADDR'] = 'accounts-test-' . wp_generate_uuid4(); update_option( 'ftuy_registration_enabled', true ); wp_set_current_user( 0 );
    $email = 'ftuy-ac-' . wp_generate_uuid4() . '@example.invalid'; $emails[] = $email;
    $request = new WP_REST_Request( 'POST', '/foodtrucks-uy/v1/accounts/register' );
    $request->set_body_params( array( 'email' => $email, 'name' => 'Cuenta de prueba', 'role' => 'administrator', 'ftuy_legacy_user_id' => $legacy ) );
    $response = rest_do_request( $request ); $user = get_user_by( 'email', $email ); if ( $user ) { $ids[] = $user->ID; }
    $assert( $response->get_status() === 202 && $user, 'Registro vía API compartida.' );
    $assert( $user->roles === array( 'subscriber' ) && ! user_can( $user, 'manage_options' ), 'No acepta rol administrativo del cliente.' );
    $assert( ! get_user_meta( $user->ID, FTUY_Accounts::LEGACY_META, true ), 'Cuenta nueva no recibe ID histórico manipulado.' );
    $assert( get_user_meta( $user->ID, 'ftuy_account_status', true ) === 'email_pending', 'Cuenta pendiente de confirmar email.' );
    $result = apply_filters( 'wp_authenticate_user', $user ); $assert( is_wp_error( $result ), 'No ingresa pendiente.' );
    $mails = get_option( 'ftuy_account_mail_local' ); $mail = end( $mails ); preg_match( '#https?://[^\s]+#', $mail['message'], $link ); parse_str( wp_parse_url( $link[0], PHP_URL_QUERY ), $params );
    $assert( ! is_wp_error( check_password_reset_key( $params['key'], $params['login'] ) ), 'Enlace local válido.' );
    reset_password( $user, 'Clave-de-prueba-larga-2026!' );
    $assert( get_user_meta( $user->ID, 'ftuy_account_status', true ) === 'active', 'Confirmación activa la cuenta.' );
    $assert( is_wp_error( check_password_reset_key( $params['key'], $params['login'] ) ), 'Enlace consumido no reutilizable.' );
    $assert( ! is_wp_error( wp_authenticate( $email, 'Clave-de-prueba-larga-2026!' ) ), 'Login con email y contraseña nueva.' );
    $before = count( get_users( array( 'search' => $email, 'search_columns' => array( 'user_email' ) ) ) );
    $original = get_user_by( 'id', $user->ID );
    $assert( FTUY_Accounts::request( 'register', $email, 'Duplicado' ) === true && count( get_users( array( 'search' => $email, 'search_columns' => array( 'user_email' ) ) ) ) === $before, 'No duplica email ni divulga existencia.' );
    $after = get_user_by( 'email', $email );
    $assert( $after->ID === $original->ID && $after->display_name === $original->display_name && $after->user_pass === $original->user_pass, 'Otro nombre con email existente no cambia identidad, nombre ni contraseña.' );
    $assert( is_wp_error( FTUY_Accounts::set_legacy_id( $user->ID, $legacy ) ), 'Anónimo no asigna IDs históricos.' );
    wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
    $assert( FTUY_Accounts::set_legacy_id( $user->ID, $legacy ) === true && (int) get_user_meta( $user->ID, FTUY_Accounts::LEGACY_META, true ) === $legacy, 'Administrador puede mapear ID.' );
    $assert( FTUY_Accounts::set_legacy_id( $user->ID, $legacy ) === true, 'Mapeo idempotente.' );
    $assert( is_wp_error( FTUY_Accounts::set_legacy_id( $user->ID, $legacy + 1 ) ), 'No cambia la identidad histórica ya asignada.' );
    $other_email = 'ftuy-ac-' . wp_generate_uuid4() . '@example.invalid'; $emails[] = $other_email;
    FTUY_Accounts::request( 'register', $other_email, 'Otra cuenta' ); $other = get_user_by( 'email', $other_email ); $ids[] = $other->ID;
    $assert( is_wp_error( FTUY_Accounts::set_legacy_id( $other->ID, $legacy ) ), 'Un ID antiguo no corresponde a dos cuentas.' );
    update_user_meta( $user->ID, 'ftuy_account_status', 'legacy_pending' );
    $assert( FTUY_Accounts::request( 'register', $email, 'Pepe' ) === true && get_user_by( 'email', $email )->display_name === $original->display_name && (int) get_user_meta( $user->ID, FTUY_Accounts::LEGACY_META, true ) === $legacy, 'Registro no reemplaza una cuenta histórica ni su ID.' );
    delete_transient( 'ftuy_ac_' . hash_hmac( 'sha256', 'request-email-' . $email, wp_salt( 'auth' ) ) );
    $assert( FTUY_Accounts::request( 'reactivate', $email ) === true, 'Reactivación de cuenta con mapeo.' );
    $mails = get_option( 'ftuy_account_mail_local' ); $mail = end( $mails );
    $assert( $mail['subject'] === 'Reactivá tu cuenta de Foodtrucks Uruguay', 'Correo de reactivación separado.' );
    $assert( FTUY_Accounts::target( 'https://evil.example/' ) === home_url( '/mi-cuenta/' ) && FTUY_Accounts::target( admin_url() ) === home_url( '/mi-cuenta/' ), 'Redirecciones restringidas a nuestras pantallas.' );
    $assert( FTUY_Accounts::target( home_url( '/mis-eventos/' ) ) === home_url( '/mis-eventos/' ), 'Conserva destino autorizado.' );
    $assert( is_wp_error( FTUY_Accounts::request( 'register', array(), 'Nombre' ) ), 'Rechaza datos compuestos.' );
    $unknown = 'ftuy-ac-' . wp_generate_uuid4() . '@example.invalid'; $emails[] = $unknown;
    $assert( FTUY_Accounts::request( 'forgot-password', $unknown ) === true, 'Recuperación no revela emails existentes.' );
    update_option( 'ftuy_registration_enabled', false );
    $assert( is_wp_error( FTUY_Accounts::request( 'register', $unknown, 'Nombre' ) ), 'Registro deshabilitable para producción.' );
    echo "OK: $count comprobaciones de cuentas.\n";
} finally {
    $logins = array(); foreach ( $ids as $id ) { $user = get_user_by( 'id', $id ); if ( $user ) { $logins[] = $user->user_login; } }
    require_once ABSPATH . 'wp-admin/includes/user.php'; foreach ( $ids as $id ) { wp_delete_user( $id ); }
    delete_option( 'ftuy_legacy_owner_' . $legacy );
    update_option( 'ftuy_account_mail_local', array_values( array_filter( get_option( 'ftuy_account_mail_local', array() ), function ( $mail ) use ( $emails, $logins ) {
        if ( in_array( $mail['recipient'], $emails, true ) ) { return false; }
        foreach ( $logins as $login ) { if ( strpos( $mail['message'], $login ) !== false ) { return false; } } return true;
    } ) ), false );
    foreach ( array_merge( array( 'request-ip-' . $_SERVER['REMOTE_ADDR'] ), array_map( function ( $email ) { return 'request-email-' . $email; }, $emails ) ) as $bucket ) { delete_transient( 'ftuy_ac_' . hash_hmac( 'sha256', $bucket, wp_salt( 'auth' ) ) ); }
    if ( $old_registration === null ) { delete_option( 'ftuy_registration_enabled' ); } else { update_option( 'ftuy_registration_enabled', $old_registration ); }
    if ( $old_ip === null ) { unset( $_SERVER['REMOTE_ADDR'] ); } else { $_SERVER['REMOTE_ADDR'] = $old_ip; }
    wp_set_current_user( $old_user );
}
