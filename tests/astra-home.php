<?php
// Read-only integration checks against the local installed transition.
$checks = 0;
$assert = function ( $ok, $label ) use ( &$checks ) { if ( ! $ok ) { throw new Exception( $label ); } $checks++; };
$assert( get_option( 'stylesheet' ) === 'foodtrucks-astra', 'Astra child active' );
$assert( is_plugin_active( 'elementor/elementor.php' ), 'Elementor active' );
foreach ( array( 'js_composer/js_composer.php', 'theme-event-champ-elements/theme-event-champ-elements.php', 'revslider/revslider.php' ) as $plugin ) { $assert( ! is_plugin_active( $plugin ), 'Legacy dependency inactive' ); }
$page = get_post( get_option( 'page_on_front' ) );
$data = json_decode( get_post_meta( $page->ID, '_elementor_data', true ), true );
$assert( is_array( $data ) && count( $data ) >= 6, 'Home retains independent Elementor blocks' );
$assert( strpos( wp_json_encode( $data ), '[ftuy_home_contact]' ) === false, 'Contact removed from Home Elementor blocks' );
$assert( get_post( 1321 ) && strpos( get_post( 1321 )->post_content, 'eventchamp_event_counter_slider' ) !== false, 'Original Home preserved' );
foreach ( array( 2802, 2803, 2804, 2805 ) as $id ) { $assert( is_file( get_attached_file( $id ) ), 'Original hero photo intact' ); }
foreach ( array( '/', '/contacto/', '/blog/', '/eventos/', '/eventos/pasados/', '/foodtrucks/', '/foodtruck/robin-foodtruck/', '/fotosusuarios/', '/registro/', '/mi-cuenta/', '/wp-json/foodtrucks-uy/v1/events', '/wp-json/foodtrucks-uy/v1/foodtrucks', '/wp-json/foodtrucks-uy/v1/publications' ) as $route ) {
    $response = wp_remote_get( home_url( $route ), array( 'timeout' => 20 ) );
    $assert( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200, 'Route ' . $route );
    $body = wp_remote_retrieve_body( $response );
    if ( $route === '/contacto/' ) {
        $assert( strpos( $body, 'Formulario de contacto' ) !== false && strpos( $body, '[vc_row' ) === false, 'Contact shortcode renders without legacy builder' );
        $assert( strpos( $body, '<h1>Contacto</h1>' ) !== false && strpos( $body, 'ft-titlebar' ) !== false, 'Contact uses standard page header' );
    }
    $assert( strpos( $body, 'Fatal error' ) === false && strpos( $body, 'Array to string conversion' ) === false, 'No runtime error ' . $route );
    if ( $route === '/blog/' ) {
        $assert( strpos( $body, '<h1>Novedades</h1>' ) !== false && strpos( $body, 'ft-titlebar' ) !== false, 'Blog has shared page titlebar' );
    }
    if ( $route === '/' ) {
        $assert( strpos( $body, 'Formulario de contacto' ) === false, 'No contact form on Home' );
        foreach ( array( 'ft-hero-track', 'ft-home-agenda', 'Los foodtrucks', 'ft-home-map', 'Descargá nuestra app', 'home.js' ) as $needle ) { $assert( strpos( $body, $needle ) !== false, 'Home block ' . $needle ); }
        foreach ( array( '[vc_row', '[eventchamp_', 'js_composer/assets', 'revslider/public', '/themes/eventchamp/' ) as $needle ) { $assert( strpos( $body, $needle ) === false, 'No old renderer ' . $needle ); }
    }
}
$now = FTUY_Events::now();
foreach ( array( 'today', 'weekend', 'upcoming' ) as $filter ) {
    $items = FTUY_Home::agenda_items( $filter );
    $assert( count( $items ) <= 8, 'Home agenda capped at eight cards' );
    foreach ( $items as $event ) {
        $assert( $event['status'] === 'published' && ! $event['cancelled'], 'Only published non-cancelled agenda events' );
        $assert( FTUY_Events::temporal( $event ) !== 'past', 'No past agenda event' );
        if ( $filter === 'today' ) { $assert( $event['start_date'] <= $now->format( 'Y-m-d' ) && $event['end_date'] >= $now->format( 'Y-m-d' ), 'Today overlaps actual local date' ); }
    }
}
$_GET['ft_agenda'] = 'upcoming';
ob_start(); FTUY_Home::render( 'map' ); $map = ob_get_clean();
$assert( strpos( $map, '<ul>' ) === false, 'No event link list below the Home map' );
ob_start(); include FTUY_PATH . 'templates/navigation.php'; $nav = ob_get_clean();
$assert( strpos( $nav, '/eventos/pasados/' ) === false, 'Past events removed only from main menu' );
$assert( strpos( $nav, '>Novedades</a>' ) !== false && strpos( $nav, '>Contacto</a>' ) !== false, 'Blog and contact menu links' );
$assert( strpos( file_get_contents( FTUY_PATH . 'templates/events.php' ), '>Eventos pasados</a>' ) !== false, 'Past events still accessible inside Events' );
ob_start(); FTUY_Home::agenda(); $agenda = ob_get_clean(); unset( $_GET['ft_agenda'] );
$assert( strpos( $agenda, 'Próximos eventos' ) !== false, 'Upcoming filter renders proper heading' );
$assert( ( strpos( $agenda, '>Hoy</a>' ) !== false ) === (bool) FTUY_Home::agenda_items( 'today' ), 'Today tab conditional on real events' );
echo "$checks checks passed; no content or user data changed.\n";
