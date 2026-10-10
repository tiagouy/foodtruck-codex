<?php
defined( 'ABSPATH' ) || exit;
class FTUY_Home {
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
            echo '</div><div class="ft-hero-copy"><p>Sabores sobre ruedas</p><h1>Salí a descubrir<br>Foodtrucks Uruguay</h1><div><a class="ft-button" href="' . esc_url( home_url( '/eventos/' ) ) . '">Ver eventos</a> <a class="ft-button ft-outline" href="#la-app">La app</a></div></div><div class="ft-hero-controls"><button type="button" data-hero-prev aria-label="Foto anterior">←</button><button type="button" data-hero-next aria-label="Foto siguiente">→</button></div></section>'; return;
        }
        $titles = array( 'events' => 'Próximos eventos', 'trucks' => 'Los foodtrucks', 'map' => '¿Dónde son los próximos eventos?', 'news' => 'Últimas novedades', 'app' => 'Llevá la comunidad con vos', 'friends' => 'Algunos amigos', 'contact' => 'Contactá con nosotros' );
        echo '<section class="ft-home-section ft-container"' . ( $block === 'app' ? ' id="la-app"' : '' ) . '><h2>' . esc_html( $titles[$block] ) . '</h2>';
        if ( $block === 'events' ) {
            $result = FTUY_Events::listing( 'upcoming', '', 1, 6 );
            if ( $result['items'] ) { FTUY_Public::cards( $result['items'] ); } else { echo '<p>Pronto tendremos nuevos encuentros. Mientras tanto, explorá el histórico.</p>'; }
            echo '<a class="ft-button" href="' . esc_url( home_url( '/eventos/' ) ) . '">Ver la agenda</a>';
        } elseif ( $block === 'trucks' ) {
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
            echo '<ul>'; foreach ( $points as $point ) { echo '<li><a href="' . esc_url( $point['url'] ) . '">' . esc_html( $point['title'] ) . '</a></li>'; } echo '</ul>';
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
