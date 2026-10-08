<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Publication_Public {
    public static $row = null;
    public static $list = null;
    public static $missing = false;
    public static $author = 0;
    public static function init() { add_action( 'template_redirect', array( __CLASS__, 'route' ), 0 ); }
    public static function slug( $row ) {
        return ! empty( $row['legacy_slug'] ) && preg_match( '/^[a-zA-Z0-9-]{1,200}$/D', $row['legacy_slug'] ) ? $row['legacy_slug'] : 'p-' . (int) $row['id'];
    }
    public static function url( $row ) { return home_url( '/fotousuario/' . self::slug( $row ) . '/' ); }
    public static function map_url( $row ) {
        $lat = $row['latitude'] ?? null; $lng = $row['longitude'] ?? null;
        $valid = is_numeric( $lat ) && is_finite( (float) $lat ) && abs( (float) $lat ) <= 90 && is_numeric( $lng ) && is_finite( (float) $lng ) && abs( (float) $lng ) <= 180;
        $query = $valid ? (float) $lat . ',' . (float) $lng : trim( (string) ( $row['address'] ?? '' ) );
        return $query !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $query ) : '';
    }
    public static function meta( $row ) {
        $html = '<time datetime="' . esc_attr( self::datetime( $row ) ) . '">' . esc_html( mysql2date( 'd/m/Y', $row['created_at'] ) ) . '</time>';
        if ( ! empty( $row['address'] ) ) { $html .= ' · <a class="ft-photo-map" href="' . esc_url( self::map_url( $row ) ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( 'Abrir ' . $row['address'] . ' en Google Maps' ) . '">' . esc_html( $row['address'] ) . '</a>'; }
        return $html;
    }
    public static function datetime( $row ) {
        // Legacy server timezone is unknown: retain its date without claiming UTC.
        return ! empty( $row['legacy_id'] ) ? substr( $row['created_at'], 0, 10 ) : str_replace( ' ', 'T', $row['created_at'] ) . 'Z';
    }
    /** Standalone templates do not load Eventchamp's placeholder/lazy-load scripts. */
    public static function image( $id, $size, $alt, $loading = 'lazy', $sizes = '(max-width:650px) 100vw, (max-width:950px) 50vw, 33vw' ) {
        $image = wp_get_attachment_image_src( $id, $size ); if ( ! $image ) { return ''; }
        $srcset = wp_get_attachment_image_srcset( $id, $size );
        return '<img src="' . esc_url( $image[0] ) . '" width="' . (int) $image[1] . '" height="' . (int) $image[2] . '" alt="' . esc_attr( $alt ) . '" loading="' . esc_attr( $loading ) . '" decoding="async"' . ( $srcset ? ' srcset="' . esc_attr( $srcset ) . '" sizes="' . esc_attr( $sizes ) . '"' : '' ) . '>';
    }
    public static function avatar( $user_id, $loading = 'lazy', $size = 36 ) {
        $id = FTUY_Profile_Images::attachment( $user_id );
        $image = $id ? self::image( $id, 'thumbnail', '', $loading, $size . 'px' ) : '';
        return '<span class="ft-photo-avatar" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>' . $image . '</span>';
    }
    public static function by_slug( $slug ) {
        if ( ! is_string( $slug ) || ! preg_match( '/^[a-zA-Z0-9-]{1,200}$/D', $slug ) ) { return null; }
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Publications::table() . " WHERE legacy_slug=%s AND status='published' LIMIT 2", $slug ), ARRAY_A );
        if ( count( $rows ) > 1 ) { return null; } // Ambiguous historical links must not select an arbitrary photo.
        if ( $rows ) { return $rows[0]; }
        if ( ctype_digit( $slug ) ) {
            return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Publications::table() . " WHERE legacy_id=%d AND status='published'", $slug ), ARRAY_A );
        }
        if ( preg_match( '/^p-([1-9][0-9]*)$/D', $slug, $match ) ) {
            $row = FTUY_Publications::get( $match[1] ); return $row && $row['status'] === 'published' ? $row : null;
        }
        return null;
    }
    public static function route() {
        $path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
        $base = trailingslashit( wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ?: '/' );
        if ( ! is_string( $path ) || strpos( $path, $base ) !== 0 ) { return; }
        $path = trim( substr( $path, strlen( $base ) ), '/' );
        $legacy_detail = $path === 'fotosusuarios/oferta.php';
        $is_list = in_array( $path, array( 'fotosusuarios', 'fotosusuarios/index.php' ), true );
        if ( ! $is_list && ! $legacy_detail && ! preg_match( '~^fotousuario/([^/]+)$~D', $path, $match ) ) { return; }
        self::$row = null; self::$list = null; self::$missing = false; self::$author = 0;
        nocache_headers(); header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );
        if ( $is_list ) {
            $valid = true;
            foreach ( array( 'pagina', 'autor' ) as $key ) {
                if ( isset( $_GET[$key] ) && ( ! is_string( $_GET[$key] ) || ! ctype_digit( $_GET[$key] ) || strlen( $_GET[$key] ) > 7 ) ) { $valid = false; }
            }
            $page = $valid ? max( 1, (int) ( $_GET['pagina'] ?? 1 ) ) : 1;
            self::$author = $valid ? (int) ( $_GET['autor'] ?? 0 ) : 0;
            self::$missing = ! $valid;
            if ( ! self::$missing ) {
                self::$list = FTUY_Publications::listing( 'published', $page, 12, self::$author );
                if ( $page > 1 && ! self::$list['items'] ) { self::$missing = true; self::$list = null; }
                $canonical = home_url( '/fotosusuarios/' );
                if ( $page > 1 ) { $canonical = add_query_arg( 'pagina', $page, $canonical ); }
                if ( self::$author ) { $canonical = add_query_arg( 'autor', self::$author, $canonical ); }
                if ( $path === 'fotosusuarios/index.php' || substr( wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), -1 ) !== '/' ) { wp_safe_redirect( $canonical, 301 ); exit; }
            }
        } else {
            $slug = $legacy_detail ? ( $_GET['slug'] ?? '' ) : rawurldecode( $match[1] );
            self::$row = self::by_slug( $slug ); self::$missing = ! self::$row;
            if ( self::$row ) {
                $canonical = self::url( self::$row );
                if ( $legacy_detail || wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) !== wp_parse_url( $canonical, PHP_URL_PATH ) ) { wp_safe_redirect( $canonical, 301 ); exit; }
            }
        }
        if ( self::$missing ) { global $wp_query; $wp_query->set_404(); header( 'X-Robots-Tag: noindex, nofollow' ); }
        status_header( self::$missing ? 404 : 200 );
        wp_enqueue_style( 'ftuy-events', FTUY_URL . 'assets/events.css', array(), FOODTRUCKS_UY_CORE_VERSION );
        wp_enqueue_style( 'ftuy-publications', FTUY_URL . 'assets/publications.css', array( 'ftuy-events' ), FOODTRUCKS_UY_CORE_VERSION );
        include FTUY_PATH . 'templates/publications.php'; exit;
    }
}
