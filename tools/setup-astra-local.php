<?php
// Run via wp eval-file only after backup. Non-production guard. Does not delete legacy content.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) { throw new Exception( 'Solo CLI local' ); }
if ( ! class_exists( '\Elementor\Plugin' ) ) { throw new Exception( 'Activar Elementor primero' ); }
$existing = get_option( 'ftuy_astra_home_id' );
$blocks = array( 'hero', 'events', 'trucks', 'app', 'news', 'friends', 'contact', 'map' );
$elements = array();
foreach ( $blocks as $i => $block ) {
    $elements[] = array( 'id' => substr( md5( 'ftuy-section-' . $block ), 0, 7 ), 'elType' => 'section', 'settings' => array( 'layout' => 'full_width', 'gap' => 'no' ), 'elements' => array( array( 'id' => substr( md5( 'ftuy-column-' . $block ), 0, 7 ), 'elType' => 'column', 'settings' => array( '_column_size' => 100 ), 'elements' => array( array( 'id' => substr( md5( 'ftuy-widget-' . $block ), 0, 7 ), 'elType' => 'widget', 'widgetType' => 'shortcode', 'settings' => array( 'shortcode' => '[ftuy_home_' . $block . ']' ), 'elements' => array() ) ) ) ) );
}
if ( ! get_option( 'ftuy_home_before_astra' ) ) { update_option( 'ftuy_home_before_astra', array( 'page_on_front' => get_option( 'page_on_front' ), 'show_on_front' => get_option( 'show_on_front' ), 'stylesheet' => get_option( 'stylesheet' ), 'plugins' => get_option( 'active_plugins' ) ), false ); }
if ( ! $existing || ! get_post( $existing ) ) {
    $existing = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Inicio · Foodtrucks Uruguay', 'post_status' => 'publish', 'post_content' => implode( "\n", array_map( function ( $b ) { return '[ftuy_home_' . $b . ']'; }, $blocks ) ) ), true );
    if ( is_wp_error( $existing ) ) { throw new Exception( 'No se pudo crear Home' ); }
    update_option( 'ftuy_astra_home_id', $existing, false );
    update_post_meta( $existing, '_elementor_edit_mode', 'builder' );
    update_post_meta( $existing, '_elementor_template_type', 'wp-page' );
    update_post_meta( $existing, '_elementor_version', ELEMENTOR_VERSION );
    update_post_meta( $existing, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );
}
// Preserve Google's existing browser key server-side option without printing it.
$legacy = get_option( 'option_tree', array() );
if ( ! get_option( 'ftuy_google_maps_key' ) && is_array( $legacy ) && ! empty( $legacy['googlemapapi'] ) ) { update_option( 'ftuy_google_maps_key', $legacy['googlemapapi'], false ); }
switch_theme( 'foodtrucks-astra' );
update_option( 'show_on_front', 'page' ); update_option( 'page_on_front', $existing );
update_post_meta( $existing, '_wp_page_template', 'default' );
\Elementor\Plugin::$instance->files_manager->clear_cache();
echo 'Astra child activo; nueva Home ' . (int) $existing . '. Home anterior conservada. Fotos 2802–2805 intactas.' . "\n";
