<?php
defined( 'ABSPATH' ) || exit;

/** Shared gate for account email requests; never trust a client/platform bypass. */
class FTUY_Turnstile {
    public static function enabled() { return (bool) get_option( 'ftuy_turnstile_enabled', false ); }
    public static function site_key() { return defined( 'FTUY_TURNSTILE_SITE_KEY' ) && is_string( FTUY_TURNSTILE_SITE_KEY ) ? trim( FTUY_TURNSTILE_SITE_KEY ) : ''; }
    private static function secret() { return defined( 'FTUY_TURNSTILE_SECRET_KEY' ) && is_string( FTUY_TURNSTILE_SECRET_KEY ) ? trim( FTUY_TURNSTILE_SECRET_KEY ) : ''; }
    public static function action( $action ) { return 'ftuy_' . str_replace( '-', '_', $action ); }
    public static function page_url( $action ) {
        $paths = array( 'register' => '/registro/', 'forgot-password' => '/recordar-contrasena/', 'reactivate' => '/reactivar-cuenta/' );
        return home_url( $paths[$action] ?? '/ingresar/' );
    }
    public static function verify( $action, $token ) {
        if ( ! self::enabled() ) { return true; }
        if ( ! self::site_key() || ! self::secret() ) {
            return new WP_Error( 'turnstile_config', 'La verificación de seguridad no está disponible. Probá más tarde.', array( 'status' => 503 ) );
        }
        $data = array( 'status' => 403, 'continue_url' => self::page_url( $action ) );
        $error = new WP_Error( 'turnstile_required', 'Completá la verificación de seguridad en ' . self::page_url( $action ) . ' y volvé a intentarlo.', $data );
        if ( ! is_string( $token ) || ! trim( $token ) || strlen( $token ) > 2048 ) { return $error; }
        // Cloudflare validates expiry and single-use. Do not cache successful checks.
        // No email, name, password or reset key is sent to the provider.
        $response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
            'timeout' => 10, 'redirection' => 0, 'limit_response_size' => 8192,
            'body' => array( 'secret' => self::secret(), 'response' => trim( $token ) ),
        ) );
        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return new WP_Error( 'turnstile_unavailable', 'No pudimos verificar la seguridad. Recargá la página y probá nuevamente.', array( 'status' => 503 ) );
        }
        $result = json_decode( wp_remote_retrieve_body( $response ), true );
        $host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
        if ( ! is_array( $result ) || ( $result['success'] ?? null ) !== true
            || ! is_string( $result['hostname'] ?? null ) || strtolower( $result['hostname'] ) !== $host
            || ( $result['action'] ?? '' ) !== self::action( $action ) ) { return $error; }
        return true;
    }
    public static function widget( $action ) {
        if ( ! self::enabled() || ! in_array( $action, array( 'register', 'forgot-password', 'reactivate' ), true ) ) { return; }
        if ( ! self::site_key() || ! self::secret() ) {
            echo '<p class="ft-error" role="alert">La verificación de seguridad no está disponible. Probá más tarde.</p>'; return;
        }
        echo '<div class="cf-turnstile" data-sitekey="' . esc_attr( self::site_key() ) . '" data-action="' . esc_attr( self::action( $action ) ) . '" data-theme="light" data-size="flexible"></div>';
        echo '<noscript><p>Activá JavaScript para completar la verificación de seguridad.</p></noscript>';
        echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    }
}
