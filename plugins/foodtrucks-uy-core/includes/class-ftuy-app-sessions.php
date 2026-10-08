<?php
defined( 'ABSPATH' ) || exit;

/** App-only bearer sessions; no WordPress cookies, passwords or tokens returned as profile data. */
class FTUY_App_Sessions {
    const PREFIX = 'ftuy_app_session_';
    public static function init() {
        add_action( 'rest_api_init', function () {
            foreach ( array( 'login' => 'POST', 'session' => 'GET', 'logout' => 'POST', 'update_profile' => 'POST', 'avatar' => 'POST' ) as $action => $method ) {
                $path = $action === 'update_profile' ? 'profile' : $action;
                register_rest_route( 'foodtrucks-uy/v1', '/accounts/' . $path, array( 'methods' => $method, 'permission_callback' => '__return_true', 'callback' => array( __CLASS__, $action ) ) );
            }
        } );
        add_filter( 'rest_post_dispatch', function ( $response, $server, $request ) {
            if ( strpos( $request->get_route(), '/foodtrucks-uy/v1/accounts/' ) === 0 ) { $response->header( 'Cache-Control', 'no-store, private' ); }
            return $response;
        }, 10, 3 );
    }
    public static function transport() {
        return is_ssl() || FTUY_Accounts::local();
    }
    public static function error() { return new WP_Error( 'app_login', 'No pudimos ingresar. Revisá email y contraseña; si tenías una cuenta anterior, reactivala primero.', array( 'status' => 401 ) ); }
    public static function allowed( $user ) {
        return $user && ! user_can( $user, 'manage_options' ) && in_array( 'subscriber', $user->roles, true ) && in_array( get_user_meta( $user->ID, 'ftuy_account_status', true ), array( '', 'active' ), true );
    }
    public static function profile( $user ) {
        $avatar = FTUY_Profile_Images::attachment( $user->ID );
        return array( 'id' => (int) $user->ID, 'name' => $user->display_name, 'first_name' => get_user_meta( $user->ID, 'first_name', true ), 'last_name' => get_user_meta( $user->ID, 'last_name', true ), 'email' => $user->user_email, 'avatar' => $avatar ? ( wp_get_attachment_image_url( $avatar, 'thumbnail' ) ?: null ) : null );
    }
    public static function update_profile( $request ) {
        $auth = self::authenticate( $request );
        if ( is_wp_error( $auth ) ) { return $auth; }
        $first = $request['first_name']; $last = $request['last_name'];
        if ( ! is_string( $first ) || ! is_string( $last ) || strlen( $first ) > 200 || strlen( $last ) > 200 ) { return new WP_Error( 'fields', 'Revisá tu nombre y apellido.', array( 'status' => 400 ) ); }
        $first = sanitize_text_field( trim( $first ) ); $last = sanitize_text_field( trim( $last ) );
        if ( ! $first ) { return new WP_Error( 'fields', 'Ingresá tu nombre.', array( 'status' => 400 ) ); }
        $id = wp_update_user( array( 'ID' => $auth['user']->ID, 'first_name' => $first, 'last_name' => $last, 'display_name' => trim( $first . ' ' . $last ) ) );
        if ( is_wp_error( $id ) ) { return new WP_Error( 'profile', 'No pudimos guardar el perfil.', array( 'status' => 503 ) ); }
        return new WP_REST_Response( array( 'user' => self::profile( get_user_by( 'id', $id ) ) ), 200 );
    }
    public static function avatar( $request ) {
        $auth = self::authenticate( $request );
        if ( is_wp_error( $auth ) ) { return $auth; }
        $id = $auth['user']->ID; $files = $request->get_file_params(); $file = $files['photo'] ?? null;
        if ( is_array( $file ) && in_array( $file['error'] ?? -1, array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) ) { return new WP_Error( 'weight', 'La imagen pesa demasiado. Elegí una de hasta 5 MB.', array( 'status' => 413 ) ); }
        if ( ! is_array( $file ) || ( $file['error'] ?? -1 ) !== UPLOAD_ERR_OK || ! is_string( $file['tmp_name'] ?? null ) || ! is_uploaded_file( $file['tmp_name'] ) ) { return new WP_Error( 'upload', 'Elegí una foto para tu perfil.', array( 'status' => 400 ) ); }
        if ( FTUY_Accounts::limited( 'avatar-user-' . $id, 10, HOUR_IN_SECONDS ) ) { return new WP_Error( 'rate', 'Hubo varios intentos. Probá nuevamente más tarde.', array( 'status' => 429 ) ); }
        $lock = 'ftuy_avatar_lock_' . $id;
        $previous = get_option( $lock );
        if ( $previous && (int) $previous < time() - 300 ) {
            global $wpdb;
            $wpdb->delete( $wpdb->options, array( 'option_name' => $lock, 'option_value' => (string) $previous ) );
            wp_cache_delete( $lock, 'options' );
        }
        if ( ! add_option( $lock, time(), '', false ) ) { return new WP_Error( 'busy', 'Hay una foto procesándose. Probá nuevamente en un momento.', array( 'status' => 429 ) ); }
        try {
            $image = FTUY_Profile_Images::replace( $id, $file['tmp_name'] );
            if ( is_wp_error( $image ) ) { return new WP_Error( 'avatar', $image->get_error_message(), array( 'status' => 400 ) ); }
            return new WP_REST_Response( array( 'user' => self::profile( get_user_by( 'id', $id ) ) ), 200 );
        } finally { delete_option( $lock ); }
    }
    public static function login( $request ) {
        if ( ! self::transport() ) { return new WP_Error( 'https', 'El ingreso requiere una conexión segura.', array( 'status' => 503 ) ); }
        $email = $request['email']; $password = $request['password'];
        if ( ! is_string( $email ) || ! is_string( $password ) || ! is_email( trim( $email ) ) || strlen( $email ) > 100 || ! $password || strlen( $password ) > 4096 ) { return self::error(); }
        $email = strtolower( trim( $email ) );
        if ( FTUY_Accounts::limited( 'app-login-ip-' . ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ), 30, 15 * MINUTE_IN_SECONDS ) || FTUY_Accounts::limited( 'app-login-email-' . $email, 10, 15 * MINUTE_IN_SECONDS ) ) { return new WP_Error( 'rate', 'Hubo varios intentos. Probá nuevamente más tarde.', array( 'status' => 429 ) ); }
        $user = wp_authenticate( $email, $password );
        if ( is_wp_error( $user ) || ! self::allowed( $user ) ) { return self::error(); }
        $secret = bin2hex( random_bytes( 32 ) ); $token = $user->ID . '.' . $secret;
        $key = self::PREFIX . hash( 'sha256', $secret ); $expiry = time() + 30 * DAY_IN_SECONDS;
        // Keep at most five sessions. Store only a hash, never the bearer secret.
        $sessions = array();
        foreach ( get_user_meta( $user->ID ) as $name => $values ) {
            if ( strpos( $name, self::PREFIX ) !== 0 ) { continue; }
            $row = get_user_meta( $user->ID, $name, true );
            if ( ! is_array( $row ) || ( $row['expires'] ?? 0 ) <= time() ) { delete_user_meta( $user->ID, $name ); }
            else { $sessions[$name] = $row['expires']; }
        }
        asort( $sessions );
        while ( count( $sessions ) >= 5 ) { $old = key( $sessions ); delete_user_meta( $user->ID, $old ); unset( $sessions[$old] ); }
        if ( ! add_user_meta( $user->ID, $key, array( 'expires' => $expiry, 'password_signature' => hash_hmac( 'sha256', $user->user_pass, wp_salt( 'auth' ) ) ), true ) ) { return new WP_Error( 'session', 'No pudimos guardar la sesión.', array( 'status' => 503 ) ); }
        return new WP_REST_Response( array( 'token' => $token, 'expires_at' => gmdate( 'c', $expiry ), 'user' => self::profile( $user ) ), 200 );
    }
    public static function authenticate( $request ) {
        if ( ! self::transport() || ! preg_match( '/^Bearer ([1-9][0-9]*)\.([a-f0-9]{64})$/D', (string) $request->get_header( 'authorization' ), $match ) ) { return self::error(); }
        $user = get_user_by( 'id', (int) $match[1] );
        if ( ! self::allowed( $user ) ) { return self::error(); }
        $key = self::PREFIX . hash( 'sha256', $match[2] ); $row = get_user_meta( $user->ID, $key, true );
        if ( ! is_array( $row ) || ( $row['expires'] ?? 0 ) <= time() || ! hash_equals( hash_hmac( 'sha256', $user->user_pass, wp_salt( 'auth' ) ), (string) ( $row['password_signature'] ?? '' ) ) ) { delete_user_meta( $user->ID, $key ); return self::error(); }
        return array( 'user' => $user, 'key' => $key );
    }
    public static function session( $request ) {
        $auth = self::authenticate( $request );
        return is_wp_error( $auth ) ? $auth : new WP_REST_Response( array( 'user' => self::profile( $auth['user'] ) ), 200 );
    }
    public static function logout( $request ) {
        $auth = self::authenticate( $request );
        if ( is_wp_error( $auth ) ) { return $auth; }
        delete_user_meta( $auth['user']->ID, $auth['key'] );
        return new WP_REST_Response( array( 'message' => 'Sesión cerrada.' ), 200 );
    }
}
