<?php
if ( ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo local.' ); }
$id = 0; $count = 0;
$assert = function ( $ok ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( 'Falló comprobación HTTP de sesión.' ); } $count++; };
try {
    $email = 'ftuy-session-http-' . wp_generate_uuid4() . '@example.invalid'; $pass = wp_generate_password( 30 );
    $id = wp_insert_user( array( 'user_login' => 'fixture-http-' . wp_generate_uuid4(), 'user_email' => $email, 'user_pass' => $pass, 'role' => 'subscriber', 'display_name' => 'Fixture HTTP', 'meta_input' => array( 'ftuy_account_status' => 'active' ) ) );
    if ( is_wp_error( $id ) ) { throw new RuntimeException( 'Fixture.' ); }
    $url = rest_url( 'foodtrucks-uy/v1/accounts/' );
    $response = wp_remote_post( $url . 'login', array( 'timeout' => 15, 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( array( 'email' => $email, 'password' => $pass ) ) ) );
    $assert( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 );
    $data = json_decode( wp_remote_retrieve_body( $response ), true ); $token = $data['token'] ?? '';
    $assert( preg_match( '/^[1-9][0-9]*\.[a-f0-9]{64}$/D', $token ) && $data['user']['id'] === $id );
    $headers = array( 'Authorization' => 'Bearer ' . $token );
    $response = wp_remote_get( $url . 'session', array( 'timeout' => 15, 'headers' => $headers ) );
    $assert( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 && strpos( wp_remote_retrieve_header( $response, 'cache-control' ), 'no-store' ) !== false );
    $response = wp_remote_post( $url . 'logout', array( 'timeout' => 15, 'headers' => $headers ) );
    $assert( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 );
    $response = wp_remote_get( $url . 'session', array( 'timeout' => 15, 'headers' => $headers ) );
    $assert( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 401 );
    echo "OK: $count comprobaciones HTTP de sesión.\n";
} finally {
    if ( $id && ! is_wp_error( $id ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $id ); }
    if ( isset( $email ) ) { delete_transient( 'ftuy_ac_' . hash_hmac( 'sha256', 'app-login-email-' . $email, wp_salt( 'auth' ) ) ); }
}
