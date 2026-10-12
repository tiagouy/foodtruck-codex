<?php
if ( ! defined( 'ABSPATH' ) ) { throw new RuntimeException( 'Requiere WordPress.' ); }
$count = 0;
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
$hero = do_shortcode( '[ftuy_home_hero]' );
$assert( ! preg_match( '/\bplanes?\b/iu', $hero ), 'No generic plan copy' );
$assert( strpos( $hero, 'Foodtrucks, sabores' ) !== false && strpos( $hero, 'en tus eventos' ) !== false, 'Foodtruck identity in titles' );
$assert( substr_count( $hero, 'data-hero-message=' ) === 4, 'Four messages' );
$assert( substr_count( $hero, '<h1>' ) === 1, 'Single heading' );
preg_match_all( '/data-hero-message="([^"]+)"/', $hero, $matches );
$messages = array_map( function ( $message ) { return json_decode( html_entity_decode( $message, ENT_QUOTES, 'UTF-8' ), true ); }, $matches[1] );
$assert( strpos( $messages[1]['text'], 'Con foodtrucks y mucho más.' ) !== false, 'Broad agenda' );
foreach ( $matches[1] as $message ) {
    $data = json_decode( html_entity_decode( $message, ENT_QUOTES, 'UTF-8' ), true );
    $assert( is_array( $data ) && isset( $data['title'], $data['text'], $data['label'], $data['url'] ), 'Valid slide message' );
    $assert( strpos( $data['url'], home_url() ) === 0, 'Local destination' );
}
$blocks = do_shortcode( '[ftuy_home_events][ftuy_home_trucks]' );
$assert( ! preg_match( '/\bplanes?\b/iu', $blocks ), 'No plan copy in CTAs' );
$assert( substr_count( $blocks, '<article class="ft-participate-card">' ) === 3, 'Three CTAs' );
$assert( strpos( $blocks, 'ft-home-agenda' ) < strpos( $blocks, 'ft-participate-grid' ) && strpos( $blocks, 'ft-participate-grid' ) < strpos( $blocks, 'ft-home-trucks' ), 'Placement' );
foreach ( array( '/sugerir-evento/', '/agregar-foodtruck/', '/contacto/' ) as $path ) { $assert( strpos( $blocks, esc_url( home_url( $path ) ) ) !== false, 'CTA destination' ); }
$assert( substr_count( do_shortcode( '[ftuy_home_participate]' ), '<article class="ft-participate-card">' ) === 3, 'Reusable shortcode' );
WP_CLI::success( "$count checks passed; no data changed." );
