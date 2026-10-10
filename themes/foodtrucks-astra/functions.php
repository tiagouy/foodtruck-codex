<?php
defined( 'ABSPATH' ) || exit;
add_action( 'wp_enqueue_scripts', function () { wp_enqueue_style( 'ftuy-astra-child', get_stylesheet_uri(), array(), '0.1.0' ); }, 30 );
add_filter( 'body_class', function ( $classes ) { $classes[] = 'ft-site'; return $classes; } );
