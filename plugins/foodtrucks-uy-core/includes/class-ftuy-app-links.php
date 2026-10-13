<?php
defined( 'ABSPATH' ) || exit;

class FTUY_App_Links {
    public static function init() { add_action( 'template_redirect', array( __CLASS__, 'route' ), -20 ); }
    public static function apple() {
        return array( 'applinks' => array( 'apps' => array(), 'details' => array( array(
            'appID' => '3Y7GC99X3J.org.useful-media-app.foodtruck',
            'paths' => array( '/fotousuario/*', '/fotosusuarios', '/fotosusuarios/', '/fotosusuarios/index.php', '/fotosusuarios/oferta.php' ),
        ) ) ) );
    }
    public static function route() {
        $path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
        $base = untrailingslashit( wp_parse_url( home_url(), PHP_URL_PATH ) ?: '' );
        if ( ! in_array( $path, array( $base . '/.well-known/apple-app-site-association', $base . '/apple-app-site-association' ), true ) ) { return; }
        status_header( 200 );
        header( 'Content-Type: application/json; charset=UTF-8' );
        header( 'Cache-Control: public, max-age=3600' );
        header( 'X-Content-Type-Options: nosniff' );
        echo wp_json_encode( self::apple(), JSON_UNESCAPED_SLASHES ); exit;
    }
}
