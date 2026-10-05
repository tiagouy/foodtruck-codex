<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Public {
    public static $view = 'list';
    public static $event = null;
    public static $preview = false;
    public static $form_data = array();
    public static $error = '';

    public static function init() {
        add_action( 'init', function () {
            add_rewrite_rule( '^eventos/pasados/?$', 'index.php?ft_view=past', 'top' );
            add_rewrite_rule( '^eventos/?$', 'index.php?ft_view=list', 'top' );
            add_rewrite_rule( '^sugerir-evento/?$', 'index.php?ft_view=suggest', 'top' );
            add_rewrite_rule( '^mis-eventos/?$', 'index.php?ft_view=mine', 'top' );
        } );
        add_filter( 'query_vars', function ( $vars ) { $vars[] = 'ft_view'; return $vars; } );
        add_action( 'template_redirect', array( __CLASS__, 'route' ), 0 );
        add_action( 'rest_api_init', array( __CLASS__, 'api' ) );
    }

    public static function api() {
        register_rest_route( 'foodtrucks-uy/v1', '/events', array(
            'methods' => 'GET', 'permission_callback' => '__return_true',
            'args' => array(
                'view' => array( 'default' => 'upcoming', 'enum' => array( 'upcoming', 'past' ) ),
                'department' => array( 'default' => '', 'validate_callback' => function ( $v ) { return $v === '' || in_array( $v, FTUY_Events::departments(), true ); } ),
                'page' => array( 'default' => 1, 'type' => 'integer', 'minimum' => 1 ),
                'per_page' => array( 'default' => 12, 'type' => 'integer', 'minimum' => 1, 'maximum' => 50 ),
            ),
            'callback' => function ( $request ) {
                $result = FTUY_Events::listing( $request['view'], $request['department'], $request['page'], $request['per_page'] );
                $result['items'] = array_map( array( 'FTUY_Events', 'public_data' ), $result['items'] );
                $response = new WP_REST_Response( $result );
                $response->header( 'X-WP-Total', $result['total'] ); $response->header( 'X-WP-TotalPages', $result['pages'] );
                return $response;
            },
        ) );
        register_rest_route( 'foodtrucks-uy/v1', '/events/(?P<slug>[a-z0-9-]+)', array(
            'methods' => 'GET', 'permission_callback' => '__return_true',
            'callback' => function ( $request ) {
                $event = FTUY_Events::by_slug( $request['slug'] );
                return $event ? new WP_REST_Response( FTUY_Events::public_data( $event, true ) ) : new WP_Error( 'not_found', 'Evento no encontrado.', array( 'status' => 404 ) );
            },
        ) );
    }

    public static function route() {
        $request_path = trim( rawurldecode( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ), '/' );
        $base = trim( wp_parse_url( home_url(), PHP_URL_PATH ) ?: '', '/' );
        if ( $base && strpos( $request_path, $base . '/' ) === 0 ) { $request_path = substr( $request_path, strlen( $base ) + 1 ); }
        $paths = array( 'eventos' => 'list', 'eventos/pasados' => 'past', 'sugerir-evento' => 'suggest', 'mis-eventos' => 'mine' );
        if ( isset( $paths[$request_path] ) ) { self::$view = $paths[$request_path]; }
        elseif ( preg_match( '#^evento/([^/]+)$#', $request_path, $matches ) ) {
            self::$event = FTUY_Events::by_slug( sanitize_title( $matches[1] ) );
            if ( ! self::$event ) { return; } // Los eventos no migrados siguen en Eventchamp.
            self::$view = 'detail';
        } else { return; }
        if ( isset( $_GET['ft_preview'] ) ) {
            $id = absint( $_GET['ft_preview'] );
            if ( ! current_user_can( 'manage_ft_events' ) || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'ft_preview_' . $id ) ) { wp_die( 'Vista previa privada.', '', array( 'response' => 403 ) ); }
            $review = FTUY_Events::review( $id );
            if ( ! $review ) { wp_die( 'Propuesta inexistente.', '', array( 'response' => 404 ) ); }
            self::$event = array_merge( FTUY_Events::get( $review['event_id'] ), json_decode( $review['payload'], true ) );
            self::$preview = true; self::$view = 'detail';
        }
        if ( self::$view === 'suggest' ) { self::submission(); }
        status_header( 200 );
        nocache_headers();
        if ( self::$preview || in_array( self::$view, array( 'mine', 'suggest' ), true ) ) { header( 'X-Robots-Tag: noindex, nofollow' ); }
        // El módulo no depende de plantillas, scripts o tipos de contenido de Eventchamp.
        wp_enqueue_style( 'ftuy-events', FTUY_URL . 'assets/events.css', array(), FOODTRUCKS_UY_CORE_VERSION );
        include FTUY_PATH . 'templates/events.php';
        exit;
    }

    public static function submission() {
        if ( ! is_user_logged_in() ) { return; }
        global $wpdb;
        $id = absint( $_GET['edit'] ?? 0 );
        if ( $id ) {
            $existing = FTUY_Events::get( $id );
            if ( ! $existing || (int) $existing['author_id'] !== get_current_user_id() ) { wp_die( 'No podés editar este evento.', '', array( 'response' => 403 ) ); }
            $review = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Events::table( 'event_reviews' ) . ' WHERE event_id=%d ORDER BY id DESC LIMIT 1', $id ), ARRAY_A );
            self::$form_data = $review && $review['status'] !== 'published' ? json_decode( $review['payload'], true ) : $existing;
        }
        if ( ( $_SERVER['REQUEST_METHOD'] ?? '' ) !== 'POST' ) { return; }
        if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'ftuy_suggest' ) ) { wp_die( 'La sesión del formulario venció.', '', array( 'response' => 403 ) ); }
        self::$form_data = array_merge( self::$form_data, wp_unslash( $_POST ) );
        // Evita ráfagas de carga sin impedir la corrección de validaciones.
        $key = 'ftuy_submit_' . get_current_user_id();
        if ( get_transient( $key ) ) { self::$error = 'Esperá un minuto antes de enviar otra propuesta.'; return; }
        $previous_image = self::$form_data['image_id'] ?? 0;
        // Nunca confiar en IDs de medios enviados por el formulario.
        $previous_image = $id ? ( $existing['image_id'] ?? 0 ) : 0;
        if ( $id && ! empty( $review ) ) { $payload = json_decode( $review['payload'], true ); $previous_image = $payload['image_id'] ?? $previous_image; }
        $candidate = self::$form_data; $candidate['image_id'] = $previous_image;
        $valid = FTUY_Events::validate( $candidate, false );
        if ( is_wp_error( $valid ) ) { self::$error = $valid->get_error_message(); return; }
        $image = FTUY_Admin::image( $previous_image );
        if ( is_wp_error( $image ) ) { self::$error = $image->get_error_message(); return; }
        $valid['image_id'] = $image;
        $result = FTUY_Events::suggest( $valid, get_current_user_id(), $id );
        if ( is_wp_error( $result ) ) {
            if ( $image && $image !== $previous_image ) { wp_delete_attachment( $image, true ); }
            self::$error = $result->get_error_message(); return;
        }
        set_transient( $key, 1, 60 );
        wp_safe_redirect( add_query_arg( 'sent', 1, home_url( '/mis-eventos/' ) ) ); exit;
    }

    public static function dates( $e ) {
        $zone = new DateTimeZone( 'America/Montevideo' );
        $start = wp_date( 'j M Y', ( new DateTimeImmutable( $e['start_date'], $zone ) )->getTimestamp(), $zone );
        $end = wp_date( 'j M Y', ( new DateTimeImmutable( $e['end_date'], $zone ) )->getTimestamp(), $zone );
        return $start . ( $e['end_date'] !== $e['start_date'] ? ' — ' . $end : '' );
    }
    public static function status_label( $e ) {
        if ( $e['cancelled'] ) { return 'Cancelado'; }
        return array( 'past' => 'Evento pasado', 'ongoing' => 'En curso', 'upcoming' => 'Próximamente' )[FTUY_Events::temporal( $e )];
    }
    public static function cards( $items ) {
        echo '<div class="ft-cards">';
        foreach ( $items as $e ) {
            echo '<article class="ft-card"><a class="ft-card-image" href="' . esc_url( FTUY_Events::url( $e ) ) . '">' . self::image( $e['image_id'], 'medium_large', $e['title'] ) . '<span class="ft-badge">' . esc_html( self::status_label( $e ) ) . '</span></a><div class="ft-card-body"><p class="ft-eyebrow">' . esc_html( $e['department'] ) . '</p><h2><a href="' . esc_url( FTUY_Events::url( $e ) ) . '">' . esc_html( $e['title'] ) . '</a></h2><p class="ft-card-date">' . esc_html( self::dates( $e ) ) . '</p><p class="ft-card-address">' . esc_html( $e['venue'] ?: $e['address'] ) . '</p><p class="ft-card-summary">' . esc_html( $e['summary'] ) . '</p><a class="ft-more" href="' . esc_url( FTUY_Events::url( $e ) ) . '">Ver evento →</a></div></article>';
        }
        echo '</div>';
    }
    public static function image( $id, $size, $alt, $loading = 'lazy' ) {
        $src = wp_get_attachment_image_src( $id, $size );
        if ( ! $src ) { return ''; }
        // Imagen nativa: los lazy-load del tema viejo dependen de scripts que esta plantilla no carga.
        return '<img src="' . esc_url( $src[0] ) . '" width="' . (int) $src[1] . '" height="' . (int) $src[2] . '" alt="' . esc_attr( $alt ) . '" loading="' . esc_attr( $loading ) . '" decoding="async">';
    }
    public static function mine() {
        if ( ! is_user_logged_in() ) { self::login(); return; }
        global $wpdb;
        if ( isset( $_GET['sent'] ) ) { echo '<p class="ft-notice">Recibimos tu propuesta. Podés consultar su estado aquí; se publicará después de la revisión.</p>'; }
        $page = max( 1, absint( $_GET['ft_page'] ?? 1 ) );
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Events::table() . ' WHERE author_id=%d ORDER BY created_at DESC LIMIT 20 OFFSET %d', get_current_user_id(), ( $page - 1 ) * 20 ), ARRAY_A );
        if ( ! $rows ) { echo '<p>Todavía no cargaste eventos.</p>'; }
        foreach ( $rows as $e ) {
            $r = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Events::table( 'event_reviews' ) . ' WHERE event_id=%d ORDER BY id DESC LIMIT 1', $e['id'] ), ARRAY_A );
            $state = $r['status'] ?? $e['status'];
            echo '<article class="ft-my-event"><h2>' . esc_html( $e['title'] ) . '</h2><p>' . esc_html( FTUY_Admin::labels()[$state] ?? $state ) . '</p>';
            if ( ! empty( $r['note'] ) ) { echo '<p>' . esc_html( $r['note'] ) . '</p>'; }
            echo '<a class="ft-button ft-outline" href="' . esc_url( add_query_arg( 'edit', $e['id'], home_url( '/sugerir-evento/' ) ) ) . '">Proponer cambios</a>';
            if ( $e['status'] === 'published' ) { echo ' <a href="' . esc_url( FTUY_Events::url( $e ) ) . '">Ver publicado</a>'; }
            echo '</article>';
        }
        $total = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . FTUY_Events::table() . ' WHERE author_id=%d', get_current_user_id() ) );
        echo '<nav class="ft-pagination">' . paginate_links( array( 'base' => add_query_arg( 'ft_page', '%#%' ), 'current' => $page, 'total' => max( 1, (int) ceil( $total / 20 ) ) ) ) . '</nav>';
    }
    public static function login() {
        echo '<div class="ft-empty"><h2>Ingresá para sugerir un evento</h2><p>Usaremos tu cuenta para avisarte del resultado y permitirte corregir la información.</p><a class="ft-button" href="' . esc_url( wp_login_url( home_url( '/sugerir-evento/' ) ) ) . '">Iniciar sesión</a>';
        if ( get_option( 'users_can_register' ) ) { echo ' <a href="' . esc_url( wp_registration_url() ) . '">Crear cuenta</a>'; }
        echo '</div>';
    }
}
