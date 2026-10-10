<?php
// Read-only integration checks against the local installed transition.
$checks = 0;
$assert = function ( $ok, $label ) use ( &$checks ) { if ( ! $ok ) { throw new Exception( $label ); } $checks++; };
$assert( get_option( 'stylesheet' ) === 'foodtrucks-astra', 'Astra child active' );
$assert( is_plugin_active( 'elementor/elementor.php' ), 'Elementor active' );
foreach ( array( 'js_composer/js_composer.php', 'theme-event-champ-elements/theme-event-champ-elements.php', 'revslider/revslider.php' ) as $plugin ) { $assert( ! is_plugin_active( $plugin ), 'Legacy dependency inactive' ); }
$page = get_post( get_option( 'page_on_front' ) );
$data = json_decode( get_post_meta( $page->ID, '_elementor_data', true ), true );
$assert( count( $data ) === 8, 'Eight separate Elementor blocks' );
$assert( get_post( 1321 ) && strpos( get_post( 1321 )->post_content, 'eventchamp_event_counter_slider' ) !== false, 'Original Home preserved' );
foreach ( array( 2802, 2803, 2804, 2805 ) as $id ) { $assert( is_file( get_attached_file( $id ) ), 'Original hero photo intact' ); }
foreach ( array( '/', '/eventos/', '/eventos/pasados/', '/foodtrucks/', '/foodtruck/robin-foodtruck/', '/fotosusuarios/', '/registro/', '/mi-cuenta/', '/wp-json/foodtrucks-uy/v1/events', '/wp-json/foodtrucks-uy/v1/foodtrucks', '/wp-json/foodtrucks-uy/v1/publications' ) as $route ) {
    $response = wp_remote_get( home_url( $route ), array( 'timeout' => 20 ) );
    $assert( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200, 'Route ' . $route );
    $body = wp_remote_retrieve_body( $response );
    $assert( strpos( $body, 'Fatal error' ) === false && strpos( $body, 'Array to string conversion' ) === false, 'No runtime error ' . $route );
    if ( $route === '/' ) {
        foreach ( array( 'ft-hero-track', 'Próximos eventos', 'Los foodtrucks', 'ft-home-map', 'Descargá nuestra app', 'home.js' ) as $needle ) { $assert( strpos( $body, $needle ) !== false, 'Home block ' . $needle ); }
        foreach ( array( '[vc_row', '[eventchamp_', 'js_composer/assets', 'revslider/public', '/themes/eventchamp/' ) as $needle ) { $assert( strpos( $body, $needle ) === false, 'No old renderer ' . $needle ); }
    }
}
echo "$checks checks passed; no content or user data changed.\n";
