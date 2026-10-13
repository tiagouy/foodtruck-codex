<?php
if ( ! defined( 'ABSPATH' ) ) { throw new RuntimeException( 'Requiere WordPress.' ); }
$value = FTUY_App_Links::apple();
$assert = function ( $ok ) { if ( ! $ok ) { throw new RuntimeException( 'Asociación incorrecta.' ); } };
$assert( $value['applinks']['details'][0]['appID'] === '3Y7GC99X3J.org.useful-media-app.foodtruck' );
$paths = $value['applinks']['details'][0]['paths'];
$assert( in_array( '/fotousuario/*', $paths, true ) );
$assert( in_array( '/fotosusuarios/oferta.php', $paths, true ) );
$assert( ! in_array( '*', $paths, true ) && ! in_array( '/elegir-contrasena/*', $paths, true ) );
foreach ( array( '/.well-known/apple-app-site-association', '/apple-app-site-association' ) as $path ) {
    $response = wp_remote_get( home_url( $path ), array( 'redirection' => 0 ) );
    $assert( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 );
    $assert( strpos( wp_remote_retrieve_header( $response, 'content-type' ), 'application/json' ) !== false );
    $assert( json_decode( wp_remote_retrieve_body( $response ), true ) === $value );
}
WP_CLI::success( '10 checks passed; no data changed.' );
