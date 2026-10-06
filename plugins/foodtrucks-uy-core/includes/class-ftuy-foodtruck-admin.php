<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Foodtruck_Admin {
    public static function init() {
        add_action( 'admin_menu', function () { add_submenu_page( 'ftuy-events', 'Foodtrucks', 'Foodtrucks', 'manage_ft_foodtrucks', 'ftuy-foodtrucks', array( __CLASS__, 'page' ) ); } );
        add_action( 'admin_post_ftuy_foodtruck', array( __CLASS__, 'handle' ) );
        add_action( 'rest_api_init', function () {
            register_rest_route( 'foodtrucks-uy/v1', '/foodtrucks', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'args' => array( 'page' => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ), 'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 12 ), 'department' => array( 'default' => '', 'validate_callback' => function ( $d ) { return $d === '' || in_array( $d, FTUY_Events::departments(), true ); } ) ), 'callback' => function ( $r ) {
                global $wpdb; $where = "status='published'"; if ( $r['department'] ) { $where .= $wpdb->prepare( ' AND department=%s', $r['department'] ); }
                $total = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . FTUY_Foodtrucks::table() . " WHERE $where" );
                $ids = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . FTUY_Foodtrucks::table() . " WHERE $where ORDER BY name,id LIMIT %d OFFSET %d", $r['per_page'], ( $r['page'] - 1 ) * $r['per_page'] ) );
                $response = new WP_REST_Response( array( 'items' => array_map( function ( $id ) { return FTUY_Foodtrucks::public_data( FTUY_Foodtrucks::get( $id ) ); }, $ids ), 'total' => $total, 'page' => $r['page'] ) ); $response->header( 'X-WP-Total', $total ); return $response;
            } ) );
            register_rest_route( 'foodtrucks-uy/v1', '/foodtrucks/(?P<slug>[a-z0-9-]+)', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function ( $r ) {
                global $wpdb; $id = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . FTUY_Foodtrucks::table() . " WHERE slug=%s AND status='published'", $r['slug'] ) );
                return $id ? new WP_REST_Response( FTUY_Foodtrucks::public_data( FTUY_Foodtrucks::get( $id ) ) ) : new WP_Error( 'not_found', 'Foodtruck no encontrado.', array( 'status' => 404 ) );
            } ) );
        } );
    }
    public static function upload( $file, $role = 'logo' ) {
        return FTUY_Foodtruck_Images::upload( $file, $role );
    }
    public static function handle() {
        if ( ! current_user_can( 'manage_ft_foodtrucks' ) ) { wp_die( 'Sin permiso.', '', array( 'response' => 403 ) ); }
        check_admin_referer( 'ftuy_foodtruck' );
        $id = absint( $_POST['foodtruck_id'] ?? 0 ); $input = wp_unslash( $_POST ); $new_images = array();
        if ( $id && ! FTUY_Foodtrucks::get( $id ) ) { wp_die( 'Ficha inexistente.' ); }
        $input['images'] = array();
        foreach ( (array) ( $input['keep_images'] ?? array() ) as $value ) {
            if ( ! is_string( $value ) || ! preg_match( '/^(logo|truck_photo|cover):(\d+)$/', $value, $m ) ) { wp_die( 'Referencia de imagen inválida.' ); }
            $input['images'][] = array( 'role' => $m[1], 'attachment_id' => (int) $m[2] );
        }
        $valid = FTUY_Foodtrucks::validate( $input ); if ( is_wp_error( $valid ) ) { wp_die( esc_html( $valid->get_error_message() ) ); }
        $fail = function ( $error ) use ( &$new_images ) { foreach ( $new_images as $image ) { wp_delete_attachment( $image, true ); } wp_die( esc_html( $error->get_error_message() ) ); };
        foreach ( array( 'logo', 'truck_photo' ) as $role ) {
            $file = $_FILES['truck_' . $role] ?? null;
            if ( $file && ! empty( $file['name'] ) ) {
                $image = self::upload( $file, $role ); if ( is_wp_error( $image ) ) { $fail( $image ); } $new_images[] = $image;
                $valid['images'] = array_values( array_filter( $valid['images'], function ( $i ) use ( $role ) { return $i['role'] !== $role; } ) ); $valid['images'][] = array( 'role' => $role, 'attachment_id' => $image );
            }
        }
        if ( ! empty( $input['instagram_token'] ) && empty( $_FILES['truck_logo']['name'] ) ) {
            $image = FTUY_Instagram::import_logo( $input['instagram_token'], $valid['instagram'] ); if ( is_wp_error( $image ) ) { $fail( $image ); } $new_images[] = $image;
            $valid['images'] = array_values( array_filter( $valid['images'], function ( $i ) { return $i['role'] !== 'logo'; } ) ); $valid['images'][] = array( 'role' => 'logo', 'attachment_id' => $image );
        }
        $extra = $_FILES['truck_photos']['name'] ?? array();
        if ( is_array( $extra ) ? (bool) array_filter( $extra ) : (bool) $extra ) { $fail( new WP_Error( 'image', 'Solo se admiten logo y foto del foodtruck.' ) ); }
        foreach ( $valid['images'] as &$image ) { $optimized = FTUY_Foodtruck_Images::ensure( $image['attachment_id'], $image['role'] ); if ( is_wp_error( $optimized ) ) { $fail( $optimized ); } if ( $optimized !== $image['attachment_id'] ) { $new_images[] = $optimized; } $image['attachment_id'] = $optimized; } unset( $image );
        $responsible = $input['responsible_user_id'] ?? get_current_user_id(); if ( ! is_scalar( $responsible ) ) { $fail( new WP_Error( 'owner', 'Cuenta responsable inválida.' ) ); }
        $review = FTUY_Foodtrucks::propose( $valid, get_current_user_id(), $id, $responsible ); if ( is_wp_error( $review ) ) { $fail( $review ); }
        $decision = sanitize_key( $input['decision'] ?? 'pending' );
        if ( $decision === 'pending' ) { global $wpdb; $wpdb->update( FTUY_Foodtrucks::table( 'foodtruck_reviews' ), array( 'note' => sanitize_textarea_field( $input['note'] ?? '' ) ), array( 'id' => $review ) ); }
        if ( $decision !== 'pending' ) { $result = FTUY_Foodtrucks::moderate( $review, $decision, $input['note'] ?? '' ); if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ) ); } }
        wp_safe_redirect( admin_url( 'admin.php?page=ftuy-foodtrucks&saved=1&edit=' . FTUY_Foodtrucks::review( $review )['foodtruck_id'] ) ); exit;
    }
    public static function page() {
        if ( ! current_user_can( 'manage_ft_foodtrucks' ) ) { return; }
        global $wpdb;
        wp_enqueue_style( 'ftuy-events', FTUY_URL . 'assets/events.css', array(), FOODTRUCKS_UY_CORE_VERSION ); wp_print_styles( 'ftuy-events' );
        echo '<div class="wrap ft-admin"><h1>Foodtrucks</h1><p>Ficha única para sitio y app. Sin email público. Las muestras históricas necesitan revisión.</p>';
        echo '<p><a class="button" href="' . esc_url( home_url( '/foodtrucks/' ) ) . '">Ver página pública</a> <a class="button" href="' . esc_url( FTUY_Foodtruck_Public::preview_url() ) . '">Vista privada del catálogo con muestras</a></p>';
        if ( isset( $_GET['saved'] ) ) { echo '<div class="notice notice-success"><p>Ficha guardada.</p></div>'; }
        $id = absint( $_GET['edit'] ?? 0 ); $e = $id ? FTUY_Foodtrucks::get( $id ) : null;
        if ( $id && ! $e ) { echo '<p>Ficha inexistente.</p></div>'; return; }
        if ( $e || isset( $_GET['new'] ) ) {
            $review = $e ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Foodtrucks::table( 'foodtruck_reviews' ) . ' WHERE foodtruck_id=%d ORDER BY id DESC LIMIT 1', $id ), ARRAY_A ) : null;
            $data = $review && in_array( $review['status'], array( 'pending', 'corrections' ), true ) ? array_merge( $e, json_decode( $review['payload'], true ) ) : ( $e ?: array() );
            echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=ftuy-foodtrucks' ) ) . '">← Todas las fichas</a></p>';
            if ( $review ) { echo '<p>Última revisión: <strong>' . esc_html( FTUY_Admin::labels()[$review['status']] ?? $review['status'] ) . '</strong></p><p>' . esc_html( $review['note'] ) . '</p>'; }
            if ( $e && $e['sample_key'] ) { echo '<div class="notice notice-warning"><p>Muestra histórica. Datos y modalidades a confirmar; la cuenta administradora es responsable provisional de carga.</p></div>'; }
            if ( isset( $_GET['preview'] ) ) { self::preview( $data ); echo '</div>'; return; }
            echo '<form class="ft-truck-form" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="ftuy_foodtruck"><input type="hidden" name="foodtruck_id" value="' . $id . '"><input type="hidden" name="instagram_token" value="">'; wp_nonce_field( 'ftuy_foodtruck' );
            FTUY_Foodtruck_Form::fields( $data );
        echo '<h2>Gestión interna</h2><p><label>Cuenta responsable '; wp_dropdown_users( array( 'name' => 'responsible_user_id', 'selected' => $data['responsible_user_id'] ?? get_current_user_id(), 'show' => 'display_name' ) ); echo '</label></p><p>Última actualización de la ficha: ' . esc_html( $e['updated_at'] ?? 'Sin guardar' ) . ' UTC</p><p><label>Nota de revisión<br><textarea name="note" rows="3" style="width:100%">' . esc_textarea( $review['note'] ?? '' ) . '</textarea></label></p><p><button class="button" name="decision" value="pending">Guardar propuesta</button> <button class="button button-primary" name="decision" value="published">Aprobar y publicar</button> <button class="button" name="decision" value="corrections">Pedir correcciones</button> <button class="button" name="decision" value="rejected">Rechazar</button></p></form>';
            if ( $e ) { echo '<p><a class="button" href="' . esc_url( FTUY_Foodtruck_Public::url( $e, true ) ) . '">Vista previa privada de la propuesta guardada</a></p>'; }
            wp_enqueue_script( 'ftuy-trucks', FTUY_URL . 'assets/foodtrucks.js', array(), FOODTRUCKS_UY_CORE_VERSION, true ); wp_localize_script( 'ftuy-trucks', 'ftuyTruck', array( 'ajax' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'ftuy_instagram' ) ) ); wp_print_scripts( 'ftuy-trucks' );
        } else {
            echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=ftuy-foodtrucks&new=1' ) ) . '">Crear foodtruck</a></p><table class="widefat striped"><tr><th>Nombre</th><th>Base</th><th>Estado público</th><th>Última propuesta</th><th>Actualización</th></tr>';
            $page = max( 1, absint( $_GET['ft_page'] ?? 1 ) );
            foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Foodtrucks::table() . ' ORDER BY id DESC LIMIT 25 OFFSET %d', ( $page - 1 ) * 25 ), ARRAY_A ) as $e ) {
                $review = $wpdb->get_var( $wpdb->prepare( 'SELECT status FROM ' . FTUY_Foodtrucks::table( 'foodtruck_reviews' ) . ' WHERE foodtruck_id=%d ORDER BY id DESC LIMIT 1', $e['id'] ) );
                echo '<tr><td><a href="' . esc_url( admin_url( 'admin.php?page=ftuy-foodtrucks&edit=' . $e['id'] ) ) . '">' . esc_html( $e['name'] ) . '</a>' . ( $e['sample_key'] ? ' · Muestra' : '' ) . '</td><td>' . esc_html( $e['locality'] . ', ' . $e['department'] ) . '</td><td>' . esc_html( FTUY_Admin::labels()[$e['status']] ?? $e['status'] ) . '</td><td>' . esc_html( FTUY_Admin::labels()[$review] ?? $review ) . '</td><td>' . esc_html( $e['updated_at'] ) . '</td></tr>';
            }
            echo '</table>' . paginate_links( array( 'base' => add_query_arg( 'ft_page', '%#%' ), 'current' => $page, 'total' => max( 1, (int) ceil( $wpdb->get_var( 'SELECT COUNT(*) FROM ' . FTUY_Foodtrucks::table() ) / 25 ) ) ) );
        }
        echo '</div>';
    }
    public static function preview( $data ) {
        echo '<div class="ft-preview">Vista previa privada · datos guardados, no publicación automática</div><h2>' . esc_html( $data['name'] ) . '</h2>';
        foreach ( $data['images'] ?? array() as $i ) { echo '<figure style="display:inline-block">' . wp_get_attachment_image( $i['attachment_id'], 'medium' ) . '<figcaption>' . esc_html( $i['role'] ) . '</figcaption></figure>'; }
        echo wpautop( wp_kses_post( $data['description'] ) ) . '<h3>Qué sirven</h3><p>' . esc_html( $data['food_offering'] ) . '</p><p>' . esc_html( $data['locality'] . ', ' . $data['department'] ) . '</p><p>Rubros: ';
        foreach ( FTUY_Foodtrucks::cuisines() as $c ) { if ( in_array( (int) $c['id'], array_map( 'intval', $data['cuisine_ids'] ?? array() ), true ) ) { echo esc_html( $c['name'] ) . ' · '; } } echo '</p><p>Modalidades: ';
        foreach ( array( 'serves_events' => 'Eventos', 'serves_private_events' => 'Catering / privados', 'has_fixed_location' => 'Punto fijo' ) as $key => $label ) { if ( ! empty( $data[$key] ) ) { echo esc_html( $label ) . ' · '; } } echo '</p>';
        if ( $data['whatsapp'] ) { echo '<p><a target="_blank" rel="noopener" href="' . esc_url( 'https://wa.me/' . $data['whatsapp'] ) . '">WhatsApp</a></p>'; }
        if ( $data['instagram'] ) { echo '<p><a target="_blank" rel="noopener" href="' . esc_url( $data['instagram'] ) . '">Instagram</a></p>'; }
    }
}
