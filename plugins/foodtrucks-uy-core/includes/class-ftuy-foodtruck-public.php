<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Foodtruck_Public {
    public static $foodtruck = null;
    public static $preview = false;
    public static $not_found = false;
    public static function init() {
        add_action( 'init', function () {
            add_rewrite_rule( '^foodtrucks/?$', 'index.php?ft_view=foodtrucks', 'top' );
            add_rewrite_rule( '^foodtruck/([^/]+)/?$', 'index.php?ft_view=foodtruck', 'top' );
        } );
        add_action( 'template_redirect', array( __CLASS__, 'route' ), 0 );
    }
    public static function url( $e, $preview = false ) {
        $url = home_url( '/foodtruck/' . $e['slug'] . '/' );
        return $preview ? self::preview_url( $url ) : $url;
    }
    public static function preview_url( $url = '' ) { return wp_nonce_url( add_query_arg( 'ft_truck_preview', 1, $url ?: home_url( '/foodtrucks/' ) ), 'ftuy_truck_preview' ); }
    public static function proposed( $e ) {
        global $wpdb;
        $r = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Foodtrucks::table( 'foodtruck_reviews' ) . ' WHERE foodtruck_id=%d ORDER BY id DESC LIMIT 1', $e['id'] ), ARRAY_A );
        return $r && in_array( $r['status'], array( 'pending', 'corrections' ), true ) ? array_merge( $e, json_decode( $r['payload'], true ) ) : $e;
    }
    public static function listing( $department = '', $cuisine = 0, $page = 1, $preview = false ) {
        global $wpdb; $table = FTUY_Foodtrucks::table(); $page = max( 1, (int) $page );
        $where = $preview ? "e.status IN ('published','pending')" : "e.status='published'";
        if ( ! $preview ) {
            if ( $department ) { $where .= $wpdb->prepare( ' AND e.department=%s', $department ); }
            if ( $cuisine ) { $where .= $wpdb->prepare( ' AND EXISTS (SELECT 1 FROM ' . FTUY_Foodtrucks::table( 'foodtruck_cuisines' ) . ' c WHERE c.foodtruck_id=e.id AND c.cuisine_id=%d)', $cuisine ); }
            $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table e WHERE $where" );
            $ids = $wpdb->get_col( $wpdb->prepare( "SELECT e.id FROM $table e WHERE $where ORDER BY e.name,e.id LIMIT 12 OFFSET %d", ( $page - 1 ) * 12 ) );
            $items = array_map( array( 'FTUY_Foodtrucks', 'get' ), $ids );
        } else {
            // Vista privada: filtrar los valores de las propuestas, no solo la ficha publicada.
            $ids = $wpdb->get_col( "SELECT e.id FROM $table e WHERE $where ORDER BY e.id" );
            $items = array();
            foreach ( $ids as $id ) {
                $e = self::proposed( FTUY_Foodtrucks::get( $id ) );
                if ( ( ! $department || $e['department'] === $department ) && ( ! $cuisine || in_array( (int) $cuisine, array_map( 'intval', $e['cuisine_ids'] ), true ) ) ) { $items[] = $e; }
            }
            usort( $items, function ( $a, $b ) { return strcasecmp( $a['name'], $b['name'] ); } ); $total = count( $items ); $items = array_slice( $items, ( $page - 1 ) * 12, 12 );
        }
        return array( 'items' => $items, 'total' => $total, 'page' => $page, 'pages' => (int) ceil( $total / 12 ) );
    }
    public static function route() {
        $path = trim( rawurldecode( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ), '/' ); $base = trim( wp_parse_url( home_url(), PHP_URL_PATH ) ?: '', '/' );
        if ( $base && strpos( $path, $base . '/' ) === 0 ) { $path = substr( $path, strlen( $base ) + 1 ); }
        if ( $path !== 'foodtrucks' && ! preg_match( '#^foodtruck/([^/]+)$#', $path, $m ) ) { return; }
        self::$preview = ! empty( $_GET['ft_truck_preview'] );
        $nonce = $_GET['_wpnonce'] ?? '';
        if ( self::$preview && ( ! current_user_can( 'manage_ft_foodtrucks' ) || ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'ftuy_truck_preview' ) ) ) { wp_die( 'Vista previa privada.', '', array( 'response' => 403 ) ); }
        self::$foodtruck = null;
        if ( $path !== 'foodtrucks' ) {
            global $wpdb;
            $id = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . FTUY_Foodtrucks::table() . ' WHERE slug=%s' . ( self::$preview ? '' : " AND status='published'" ), sanitize_title( $m[1] ) ) );
            if ( ! $id ) { self::$not_found = true; global $wp_query; $wp_query->set_404(); }
            else { self::$foodtruck = FTUY_Foodtrucks::get( $id ); if ( self::$preview ) { self::$foodtruck = self::proposed( self::$foodtruck ); } }
        }
        status_header( self::$not_found ? 404 : 200 ); nocache_headers(); if ( self::$preview || self::$not_found ) { header( 'X-Robots-Tag: noindex, nofollow' ); }
        wp_enqueue_style( 'ftuy-events', FTUY_URL . 'assets/events.css', array(), FOODTRUCKS_UY_CORE_VERSION );
        wp_enqueue_style( 'ftuy-foodtrucks', FTUY_URL . 'assets/foodtrucks.css', array( 'ftuy-events' ), FOODTRUCKS_UY_CORE_VERSION );
        include FTUY_PATH . 'templates/foodtrucks.php'; exit;
    }
    public static function image_id( $e, $role ) { foreach ( $e['images'] as $image ) { if ( $image['role'] === $role ) { return (int) $image['attachment_id']; } } return 0; }
    public static function image( $id, $name, $class = '', $loading = 'lazy' ) {
        if ( ! $id ) { return '<span class="ft-truck-placeholder ' . esc_attr( $class ) . '" aria-hidden="true">' . esc_html( function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1 ) : substr( $name, 0, 1 ) ) . '</span>'; }
        $src = wp_get_attachment_image_src( $id, 'large' ); if ( ! $src ) { return self::image( 0, $name, $class, $loading ); }
        return '<img class="' . esc_attr( $class ) . '" src="' . esc_url( $src[0] ) . '" width="' . (int) $src[1] . '" height="' . (int) $src[2] . '" alt="' . esc_attr( $name ) . '" loading="' . esc_attr( $loading ) . '" decoding="async">';
    }
    public static function cuisines( $e ) {
        $names = array(); foreach ( FTUY_Foodtrucks::cuisines() as $c ) { if ( in_array( (int) $c['id'], array_map( 'intval', $e['cuisine_ids'] ), true ) ) { $names[] = $c['name']; } } return $names;
    }
    public static function modes( $e ) {
        $names = array(); foreach ( array( 'serves_events' => 'Eventos', 'serves_private_events' => 'Catering / privados', 'has_fixed_location' => 'Punto fijo' ) as $key => $label ) { if ( ! empty( $e[$key] ) ) { $names[] = $label; } } return $names;
    }
}
