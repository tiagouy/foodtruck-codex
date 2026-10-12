<?php
if ( ! defined( 'ABSPATH' ) || ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) { throw new RuntimeException( 'Solo local.' ); }
$original_user = get_current_user_id(); $original_uri = $_SERVER['REQUEST_URI'] ?? null; $original_get = $_GET;
$count = 0;
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
$render = function () { ob_start(); FTUY_Analytics::render(); return ob_get_clean(); };
$production = function () { return 'https://foodtruckuruguay.com'; };
try {
    wp_set_current_user( 0 ); $_GET = array(); $_SERVER['REQUEST_URI'] = '/';
    $assert( $render() === '', 'No local tracking' );
    add_filter( 'home_url', $production );
    $html = $render();
    $assert( substr_count( $html, 'gtag/js?id=G-RBK3ZR6ZWP' ) === 1, 'One loader' );
    $assert( strpos( $html, "gtag('config', 'G-RBK3ZR6ZWP'" ) !== false, 'Correct property' );
    $assert( strpos( $html, 'locationURL.origin + locationURL.pathname' ) !== false && strpos( $html, 'referrerURL.origin + referrerURL.pathname' ) !== false, 'Strip URL and referrer secrets' );
    foreach ( array( '/registro/', '/mi-cuenta/', '/elegir-contrasena/', '/ingresar/', '/mis-foodtrucks/', '/sugerir-evento/' ) as $uri ) { $_SERVER['REQUEST_URI'] = $uri; $assert( $render() === '', 'Private account page' ); }
    $_SERVER['REQUEST_URI'] = '/eventos/'; $_GET = array( 'ft_preview' => 1 );
    $assert( $render() === '', 'Private preview' );
    $_GET = array(); $admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]; wp_set_current_user( $admin->ID );
    $assert( $render() === '', 'Admin frontend excluded' );
    WP_CLI::success( "$count checks passed; no Google requests or data changes." );
} finally {
    remove_filter( 'home_url', $production ); wp_set_current_user( $original_user ); $_GET = $original_get;
    if ( $original_uri === null ) { unset( $_SERVER['REQUEST_URI'] ); } else { $_SERVER['REQUEST_URI'] = $original_uri; }
}
