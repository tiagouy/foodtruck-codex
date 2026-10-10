<?php
defined( 'ABSPATH' ) || exit;
class FTUY_Home {
    public static function agenda_items( $filter = 'upcoming' ) {
        global $wpdb;
        $now = FTUY_Events::now();
        $where = $wpdb->prepare( "status='published' AND cancelled=0 AND CONCAT(end_date,' ',IF(end_time='', '23:59', end_time)) >= %s", $now->format( 'Y-m-d H:i' ) );
        if ( $filter === 'today' || $filter === 'weekend' ) {
            $start = $now;
            if ( $filter === 'weekend' && (int) $now->format( 'N' ) < 5 ) { $start = $now->modify( 'next friday' ); }
            $end = $filter === 'today' ? $now : $start->modify( '+' . ( 7 - (int) $start->format( 'N' ) ) . ' days' );
            $where .= $wpdb->prepare( ' AND start_date <= %s AND end_date >= %s', $end->format( 'Y-m-d' ), $start->format( 'Y-m-d' ) );
        }
        return $wpdb->get_results( 'SELECT * FROM ' . FTUY_Events::table() . " WHERE $where ORDER BY start_date,start_time,id LIMIT 8", ARRAY_A );
    }
    public static function agenda() {
        $today = self::agenda_items( 'today' );
        $weekend = self::agenda_items( 'weekend' );
        $filter = sanitize_key( wp_unslash( $_GET['ft_agenda'] ?? '' ) );
        if ( ! in_array( $filter, array( 'today', 'weekend', 'upcoming' ), true ) ) { $filter = $weekend ? 'weekend' : 'upcoming'; }
        $items = $filter === 'today' ? $today : ( $filter === 'weekend' ? $weekend : self::agenda_items() );
        echo '<section class="ft-home-agenda"><div class="ft-container"><div class="ft-agenda-heading"><h2>' . esc_html( $filter === 'today' ? 'Hoy' : ( $filter === 'weekend' ? 'Este finde' : 'Próximos eventos' ) ) . '</h2><nav class="ft-agenda-tabs" aria-label="Agenda de eventos">';
        $tabs = array( 'weekend' => 'Este finde', 'upcoming' => 'Próximos' );
        if ( $today ) { $tabs = array( 'today' => 'Hoy' ) + $tabs; }
        foreach ( $tabs as $key => $label ) { echo '<a' . ( $key === $filter ? ' aria-current="true"' : '' ) . ' href="' . esc_url( add_query_arg( 'ft_agenda', $key, home_url( '/' ) ) . '#agenda' ) . '">' . esc_html( $label ) . '</a>'; }
        echo '<a href="#mapa-eventos">Mapa</a></nav><a class="ft-agenda-all" href="' . esc_url( home_url( '/eventos/' ) ) . '">Ver todos los eventos →</a></div><div class="ft-agenda-cards" id="agenda">';
        $zone = wp_timezone();
        foreach ( $items as $event ) {
            $date = new DateTimeImmutable( $event['start_date'], $zone );
            echo '<article class="ft-agenda-card"><a class="ft-agenda-poster" href="' . esc_url( FTUY_Events::url( $event ) ) . '">' . FTUY_Public::image( $event['image_id'], 'medium_large', $event['title'] ) . '</a><div class="ft-agenda-meta"><div class="ft-agenda-date"><span>' . esc_html( wp_date( 'D', $date->getTimestamp(), $zone ) ) . '</span><strong>' . esc_html( $date->format( 'j' ) ) . '</strong><span>' . esc_html( wp_date( 'M', $date->getTimestamp(), $zone ) ) . '</span></div><div><h3><a href="' . esc_url( FTUY_Events::url( $event ) ) . '">' . esc_html( $event['title'] ) . '</a></h3><p>' . esc_html( $event['venue'] ?: $event['address'] ) . '</p><p class="ft-agenda-range">' . esc_html( FTUY_Public::dates( $event ) ) . '</p></div></div></article>';
        }
        echo '</div>';
        if ( ! $items ) { echo '<p>No hay eventos publicados para estas fechas. <a href="' . esc_url( home_url( '/eventos/' ) ) . '">Ver la agenda completa</a>.</p>'; }
        echo '</div></section>';
    }
    public static function init() {
        foreach ( array( 'hero', 'events', 'trucks', 'map', 'news', 'app', 'friends', 'contact' ) as $block ) {
            add_shortcode( 'ftuy_home_' . $block, function () use ( $block ) { ob_start(); self::render( $block ); return ob_get_clean(); } );
        }
        add_action( 'wp_enqueue_scripts', function () {
            wp_enqueue_style( 'ftuy-events', FTUY_URL . 'assets/events.css', array(), FOODTRUCKS_UY_CORE_VERSION );
            wp_enqueue_style( 'ftuy-brand', FTUY_URL . 'assets/brand.css', array( 'ftuy-events' ), FOODTRUCKS_UY_CORE_VERSION );
            if ( is_front_page() ) { wp_enqueue_script( 'ftuy-home', FTUY_URL . 'assets/home.js', array(), FOODTRUCKS_UY_CORE_VERSION, true ); }
        } );
    }
    public static function render( $block ) {
        if ( $block === 'hero' ) {
            echo '<section class="ft-hero" aria-label="Foodtrucks Uruguay"><div class="ft-hero-track">';
            foreach ( array( 2802, 2803, 2804, 2805 ) as $i => $id ) {
                echo '<div class="ft-hero-slide">' . wp_get_attachment_image( $id, 'large', false, array( 'loading' => $i ? 'lazy' : 'eager', 'fetchpriority' => $i ? 'auto' : 'high', 'alt' => 'Foodtrucks y encuentros gastronómicos' ) ) . '</div>';
            }
            echo '</div><div class="ft-hero-copy"><h1>¿Qué se come<br>este finde?</h1><p>Eventos, foodtrucks y la comunidad<br>gastronómica sobre ruedas de Uruguay.</p><a class="ft-button" href="' . esc_url( home_url( '/eventos/' ) ) . '">Ver eventos →</a></div><div class="ft-hero-controls"><button type="button" data-hero-prev aria-label="Foto anterior">←</button><button type="button" data-hero-next aria-label="Foto siguiente">→</button></div></section>'; return;
        }
        if ( $block === 'events' ) { self::agenda(); return; }
        $titles = array( 'events' => 'Próximos eventos', 'trucks' => 'Los foodtrucks', 'map' => '¿Dónde son los próximos eventos?', 'news' => 'Últimas novedades', 'app' => 'Llevá la comunidad con vos', 'friends' => 'Algunos amigos', 'contact' => 'Contactá con nosotros' );
        echo '<section class="ft-home-section ft-container"' . ( $block === 'app' ? ' id="la-app"' : ( $block === 'map' ? ' id="mapa-eventos"' : ( $block === 'contact' ? ' id="contacto"' : '' ) ) ) . '><h2>' . esc_html( $titles[$block] ) . '</h2>';
        if ( $block === 'trucks' ) {
            $result = FTUY_Foodtruck_Public::listing(); echo '<div class="ft-cards ft-home-trucks">';
            foreach ( array_slice( $result['items'], 0, 4 ) as $truck ) {
                $url = FTUY_Foodtruck_Public::url( $truck ); $image = FTUY_Foodtruck_Public::image_id( $truck, 'truck_photo' ) ?: FTUY_Foodtruck_Public::image_id( $truck, 'logo' );
                echo '<article class="ft-card"><a class="ft-card-image" href="' . esc_url( $url ) . '">' . FTUY_Public::image( $image, 'medium_large', $truck['name'] ) . '</a><div class="ft-card-body"><p class="ft-eyebrow">' . esc_html( implode( ' · ', FTUY_Foodtruck_Public::cuisines( $truck ) ) ) . '</p><h3><a href="' . esc_url( $url ) . '">' . esc_html( $truck['name'] ) . '</a></h3><p>' . esc_html( $truck['food_offering'] ) . '</p></div></article>';
            }
            echo '</div><a class="ft-button" href="' . esc_url( home_url( '/foodtrucks/' ) ) . '">Conocer foodtrucks</a>';
        } elseif ( $block === 'map' ) {
            global $wpdb; $table = FTUY_Events::table();
            $events = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE status='published' AND cancelled=0 AND latitude IS NOT NULL AND longitude IS NOT NULL AND CONCAT(end_date,' ',IF(end_time='', '23:59', end_time)) >= %s ORDER BY start_date,id LIMIT 200", FTUY_Events::now()->format( 'Y-m-d H:i' ) ), ARRAY_A );
            $points = array();
            foreach ( $events as $e ) { if ( abs( (float) $e['latitude'] ) <= 90 && abs( (float) $e['longitude'] ) <= 180 ) { $points[] = array( 'lat' => (float) $e['latitude'], 'lng' => (float) $e['longitude'], 'title' => $e['title'], 'url' => FTUY_Events::url( $e ) ); } }
            $legacy = get_option( 'option_tree', array() ); $key = get_option( 'ftuy_google_maps_key', '' ) ?: ( is_array( $legacy ) ? ( $legacy['googlemapapi'] ?? '' ) : '' );
            echo '<p>Eventos publicados con ubicación confirmada. Tocá un marcador para conocer el evento.</p>';
            if ( $points && $key ) {
                echo '<div class="ft-home-map" data-points="' . esc_attr( wp_json_encode( $points ) ) . '" data-map-key="' . esc_attr( $key ) . '" data-map-id="' . esc_attr( get_option( 'ftuy_google_map_id', 'DEMO_MAP_ID' ) ) . '"><button class="ft-button" type="button">Cargar mapa</button></div>';
            } else { echo '<p>El mapa se mostrará cuando haya ubicaciones disponibles y Google Maps esté configurado.</p>'; }
        } elseif ( $block === 'news' ) {
            $posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 3 ) ); echo '<div class="ft-cards">';
            foreach ( $posts as $post ) { echo '<article class="ft-card"><a class="ft-card-image" href="' . esc_url( get_permalink( $post ) ) . '">' . get_the_post_thumbnail( $post, 'medium_large', array( 'loading' => 'lazy' ) ) . '</a><div class="ft-card-body"><h3><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( $post->post_title ) . '</a></h3><p>' . esc_html( wp_trim_words( wp_strip_all_tags( $post->post_content ), 22 ) ) . '</p></div></article>'; }
            echo '</div>';
        } elseif ( $block === 'app' ) {
            echo '<p>Eventos, foodtrucks y fotos de la comunidad en una misma app. Estamos preparando la nueva versión.</p><a class="ft-button" href="' . esc_url( home_url( '/fotosusuarios/' ) ) . '">Explorá la comunidad</a>';
        } elseif ( $block === 'friends' ) {
            echo '<p>Amigos que nos han acompañado en el camino.</p><div class="ft-friends">';
            foreach ( array( 2880, 2881, 2879, 2854, 2853, 2852 ) as $id ) { echo wp_get_attachment_image( $id, 'medium', false, array( 'loading' => 'lazy' ) ); } echo '</div>';
        } elseif ( $block === 'contact' ) {
            echo '<p>Contanos algo, saludá o recomendá un encuentro.</p>' . do_shortcode( '[contact-form-7 id="2887"]' );
        }
        echo '</section>';
    }
}
