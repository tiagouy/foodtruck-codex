<?php
if ( ! defined( 'ABSPATH' ) || ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo en local.' ); }
require_once ABSPATH . 'wp-admin/includes/file.php';
$count = 0; $uid = 0; $jar = wp_tempnam( 'ftuy-cookie-test' ); $other_jar = wp_tempnam( 'ftuy-cookie-other-test' );
$email = 'ftuy-web-' . wp_generate_uuid4() . '@example.invalid';
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
$call = function ( $path, $data = null, $cookie_file = null ) use ( $jar ) {
    $ch = curl_init( strpos( $path, 'http' ) === 0 ? $path : home_url( $path ) );
    curl_setopt_array( $ch, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_FOLLOWLOCATION => false, CURLOPT_COOKIEFILE => $cookie_file ?: $jar, CURLOPT_COOKIEJAR => $cookie_file ?: $jar ) );
    if ( $data !== null ) { curl_setopt( $ch, CURLOPT_POST, true ); curl_setopt( $ch, CURLOPT_POSTFIELDS, $data ); }
    $body = curl_exec( $ch ); $status = curl_getinfo( $ch, CURLINFO_HTTP_CODE ); $redirect = curl_getinfo( $ch, CURLINFO_REDIRECT_URL ); $error = curl_error( $ch ); curl_close( $ch );
    if ( $error ) { throw new RuntimeException( $error ); } return array( $status, $body, $redirect );
};
$nonce = function ( $body ) { preg_match( '/name="_wpnonce" value="([^"]+)"/', $body, $match ); return $match[1] ?? ''; };
try {
    list( $code, $body ) = $call( '/registro/' ); $registration_nonce = $nonce( $body );
    $assert( $code === 200 && $registration_nonce && strpos( $body, 'name="name"' ) !== false && strpos( $body, 'name="role"' ) === false, 'Registro propio sin selección de rol.' );
    list( $code, $body ) = $call( '/registro/', null, $other_jar );
    $assert( $nonce( $body ) !== $registration_nonce, 'Nonce anónimo ligado al navegador.' );
    list( $code ) = $call( '/registro/', array( '_wpnonce' => $registration_nonce, 'name' => 'Prueba HTTP', 'email' => $email ), $other_jar ); $assert( $code === 403, 'CSRF entre navegadores rechazado.' );
    list( $code, $body ) = $call( '/registro/', array( '_wpnonce' => $registration_nonce, 'name' => 'Prueba HTTP', 'email' => $email, 'role' => 'administrator', 'ftuy_legacy_user_id' => '9999999' ) );
    $user = get_user_by( 'email', $email ); if ( $user ) { $uid = $user->ID; }
    $assert( $code === 200 && $uid && strpos( $body, FTUY_Accounts::confirmation() ) !== false, 'Registro y confirmación genérica.' );
    $assert( $user->roles === array( 'subscriber' ) && ! get_user_meta( $uid, FTUY_Accounts::LEGACY_META, true ), 'Servidor fuerza suscriptor e ignora ID antiguo.' );
    wp_cache_delete( 'ftuy_account_mail_local', 'options' ); $mails = get_option( 'ftuy_account_mail_local' ); $mail = end( $mails ); preg_match( '#https?://[^\s]+#', $mail['message'], $link ); $reset_url = $link[0];
    list( $code, $body ) = $call( $reset_url ); $reset_nonce = $nonce( $body ); $assert( $code === 200 && strpos( $body, 'name="password_confirm"' ) !== false, 'Enlace abre nuestra pantalla de contraseña.' );
    list( $code, $body ) = $call( $reset_url, array( '_wpnonce' => $reset_nonce, 'password' => 'short', 'password_confirm' => 'short' ) ); $assert( $code === 200 && strpos( $body, 'al menos 12' ) !== false, 'Contraseña breve rechazada.' );
    $password = 'Clave-http-' . wp_generate_uuid4();
    list( $code, $body, $redirect ) = $call( $reset_url, array( '_wpnonce' => $reset_nonce, 'password' => $password, 'password_confirm' => $password ) ); $assert( $code === 302 && strpos( $redirect, '/ingresar/' ) !== false, 'Confirmación vuelve al login propio.' );
    list( $code, $body ) = $call( $reset_url ); $assert( strpos( $body, 'ya fue utilizado' ) !== false && strpos( $body, 'name="password_confirm"' ) === false, 'Enlace de un solo uso.' );
    list( $code, $body ) = $call( '/ingresar/' ); $login_nonce = $nonce( $body );
    $assert( strpos( $body, 'Recordar contraseña' ) !== false && strpos( $body, 'Reactivar cuenta' ) !== false, 'Recuperación y reactivación visibles por separado.' );
    list( $code, $body, $redirect ) = $call( '/ingresar/', array( '_wpnonce' => $login_nonce, 'email' => $email, 'password' => $password, 'redirect_to' => 'https://evil.example/' ) ); $assert( $code === 302 && $redirect === home_url( '/mi-cuenta/' ), 'Login real con redirect seguro.' );
    list( $code, $body ) = $call( '/mi-cuenta/' ); $profile_nonce = $nonce( $body );
    $assert( strpos( $body, $email ) !== false && strpos( $body, 'name="name"' ) !== false && strpos( $body, 'ftuy_legacy_user_id' ) === false, 'Mi cuenta propia sin ID histórico editable.' );
    list( $code, $body ) = $call( '/mi-cuenta/', array( '_wpnonce' => $profile_nonce, 'name' => 'Nombre corregido', 'ID' => '1', 'role' => 'administrator' ) );
    $assert( strpos( $body, 'Guardamos tu nombre.' ) !== false, 'Editar solo nombre propio.' );
    list( $code, $body, $redirect ) = $call( '/wp-admin/' ); $assert( $code === 302 && $redirect === home_url( '/mi-cuenta/' ), 'Suscriptor no entra al administrador.' );
    list( $code ) = $call( '/salir/?_wpnonce=invalid' ); $assert( $code === 403, 'Logout exige nonce.' );
    list( $code, $body ) = $call( '/mi-cuenta/' ); preg_match( '#href="([^"]*/salir/[^\"]*)"#', $body, $logout );
    list( $code ) = $call( html_entity_decode( $logout[1] ) ); $assert( $code === 302, 'Logout propio.' );
    list( $code, $body ) = $call( '/mi-cuenta/' ); $assert( strpos( $body, $email ) === false && strpos( $body, 'Ingresá para gestionar tu cuenta' ) !== false, 'Cuenta privada después del logout.' );
    list( $code, $body ) = $call( '/recordar-contrasena/' );
    list( $code, $body ) = $call( '/recordar-contrasena/', array( '_wpnonce' => $nonce( $body ), 'email' => $email ) ); $assert( $code === 200 && strpos( $body, FTUY_Accounts::confirmation() ) !== false, 'Recuperación desde nuestra pantalla.' );
    echo "OK: $count comprobaciones HTTP de cuentas.\n";
} finally {
    if ( ! $uid ) { $user = get_user_by( 'email', $email ); $uid = $user ? $user->ID : 0; }
    $user = $uid ? get_user_by( 'id', $uid ) : null; $login = $user ? $user->user_login : '';
    if ( $uid ) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $uid ); }
    wp_cache_delete( 'ftuy_account_mail_local', 'options' );
    update_option( 'ftuy_account_mail_local', array_values( array_filter( get_option( 'ftuy_account_mail_local', array() ), function ( $mail ) use ( $email, $login ) { return $mail['recipient'] !== $email && ( ! $login || strpos( $mail['message'], $login ) === false ); } ) ), false );
    wp_delete_file( $jar ); wp_delete_file( $other_jar );
    foreach ( array( 'request-email-' . $email, 'login-email-' . $email ) as $bucket ) { delete_transient( 'ftuy_ac_' . hash_hmac( 'sha256', $bucket, wp_salt( 'auth' ) ) ); }
}
