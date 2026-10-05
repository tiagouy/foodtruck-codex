<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Foodtruck_Submissions {
    public static $data = array();
    public static $error = '';
    public static function latest( $id ) { global $wpdb; return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Foodtrucks::table( 'foodtruck_reviews' ) . ' WHERE foodtruck_id=%d ORDER BY id DESC LIMIT 1', $id ), ARRAY_A ); }
    public static function prepare() {
        if ( ! is_user_logged_in() ) { return; }
        $raw_id = $_GET['edit'] ?? '0'; if ( ! is_scalar( $raw_id ) || ! ctype_digit( (string) $raw_id ) ) { wp_die( 'Ficha inválida.', '', array( 'response' => 400 ) ); } $id = absint( $raw_id );
        $existing = $id ? FTUY_Foodtrucks::get( $id ) : null;
        if ( $id && ( ! $existing || (int) $existing['responsible_user_id'] !== get_current_user_id() ) ) { wp_die( 'No podés editar esta ficha.', '', array( 'response' => 403 ) ); }
        $review = $id ? self::latest( $id ) : null;
        self::$data = $existing ?: array();
        if ( $review && $review['status'] !== 'published' ) { self::$data = array_merge( self::$data, json_decode( $review['payload'], true ) ); }
        if ( ( $_SERVER['REQUEST_METHOD'] ?? '' ) !== 'POST' ) { return; }
        $nonce = $_POST['_wpnonce'] ?? ''; if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'ftuy_submit_foodtruck' ) ) { wp_die( 'La sesión del formulario venció.', '', array( 'response' => 403 ) ); }
        $rate = 'ftuy_truck_submit_' . get_current_user_id();
        if ( get_transient( $rate ) ) { self::$error = 'Esperá un minuto antes de enviar otra propuesta.'; return; }
        $input = wp_unslash( $_POST );
        $allowed_images = self::$data['images'] ?? array(); $images = array();
        $keep = $input['keep_images'] ?? array(); if ( ! is_array( $keep ) || count( $keep ) > 10 ) { self::$error = 'Referencias de imágenes inválidas.'; return; }
        foreach ( $keep as $value ) {
            $match = null;
            foreach ( $allowed_images as $image ) { if ( is_string( $value ) && $value === $image['role'] . ':' . $image['attachment_id'] ) { $match = $image; break; } }
            if ( ! $match ) { self::$error = 'No podés usar esa imagen en esta ficha.'; return; } $images[] = $match;
        }
        $candidate = array();
        foreach ( array( 'name', 'description', 'food_offering', 'department', 'locality', 'whatsapp', 'instagram', 'serves_events', 'serves_private_events', 'has_fixed_location', 'cuisine_ids' ) as $key ) { $candidate[$key] = $input[$key] ?? ( $key === 'cuisine_ids' ? array() : '' ); }
        $candidate['images'] = $images;
        $display = $candidate;
        foreach ( $display as $key => $value ) { if ( ! in_array( $key, array( 'images', 'cuisine_ids' ), true ) && ! is_scalar( $value ) ) { $display[$key] = ''; } }
        $display['cuisine_ids'] = is_array( $display['cuisine_ids'] ) ? array_values( array_filter( $display['cuisine_ids'], 'is_scalar' ) ) : array();
        self::$data = array_merge( self::$data, $display );
        $valid = FTUY_Foodtrucks::validate( $candidate );
        if ( is_wp_error( $valid ) ) { self::$error = $valid->get_error_message(); return; }
        self::$data = array_merge( self::$data, $valid );
        $new = array();
        $fail = function ( $error ) use ( &$new ) { foreach ( $new as $id ) { wp_delete_attachment( $id, true ); } self::$error = $error->get_error_message(); };
        foreach ( array( 'logo', 'cover' ) as $role ) {
            $file = $_FILES['truck_' . $role] ?? null;
            if ( $file && ! empty( $file['name'] ) ) {
                if ( ! is_string( $file['name'] ) ) { $fail( new WP_Error( 'image', 'Archivo inválido.' ) ); return; }
                $image = FTUY_Foodtruck_Admin::upload( $file ); if ( is_wp_error( $image ) ) { $fail( $image ); return; } $new[] = $image;
                $valid['images'] = array_values( array_filter( $valid['images'], function ( $i ) use ( $role ) { return $i['role'] !== $role; } ) ); $valid['images'][] = array( 'role' => $role, 'attachment_id' => $image );
            }
        }
        if ( ! empty( $input['instagram_token'] ) && empty( $_FILES['truck_logo']['name'] ) ) {
            $image = FTUY_Instagram::import_logo( $input['instagram_token'], $valid['instagram'] ); if ( is_wp_error( $image ) ) { $fail( $image ); return; } $new[] = $image;
            $valid['images'] = array_values( array_filter( $valid['images'], function ( $i ) { return $i['role'] !== 'logo'; } ) ); $valid['images'][] = array( 'role' => 'logo', 'attachment_id' => $image );
        }
        $files = $_FILES['truck_photos'] ?? array();
        if ( isset( $files['name'] ) && ! is_array( $files['name'] ) ) { $fail( new WP_Error( 'image', 'Galería inválida.' ) ); return; }
        foreach ( $files['name'] ?? array() as $index => $name ) {
            if ( ! $name ) { continue; }
            if ( ! is_string( $name ) || count( $valid['images'] ) >= 10 ) { $fail( new WP_Error( 'image', 'Máximo diez imágenes incluyendo logo y portada.' ) ); return; }
            $file = array(); foreach ( array( 'name', 'type', 'tmp_name', 'error', 'size' ) as $key ) { $file[$key] = $files[$key][$index] ?? ''; }
            $image = FTUY_Foodtruck_Admin::upload( $file ); if ( is_wp_error( $image ) ) { $fail( $image ); return; } $new[] = $image; $valid['images'][] = array( 'role' => 'official', 'attachment_id' => $image );
        }
        // Responsable y estado nunca se leen del formulario público, incluso para un administrador.
        $r = FTUY_Foodtrucks::propose( $valid, get_current_user_id(), $id, get_current_user_id() );
        if ( is_wp_error( $r ) ) { $fail( $r ); return; }
        set_transient( $rate, 1, MINUTE_IN_SECONDS );
        wp_safe_redirect( add_query_arg( 'sent', 1, home_url( '/mis-foodtrucks/' ) ) ); exit;
    }
    public static function login( $target ) {
        echo '<div class="ft-empty"><h2>Ingresá para gestionar tu foodtruck</h2><p>Podés explorar eventos y foodtrucks sin cuenta. Para enviar una ficha o editar las tuyas, necesitás iniciar sesión.</p><a class="ft-button" href="' . esc_url( wp_login_url( home_url( $target ) ) ) . '">Iniciar sesión</a>';
        if ( get_option( 'users_can_register' ) ) { echo ' <a class="ft-button ft-outline" href="' . esc_url( add_query_arg( 'redirect_to', home_url( $target ), wp_registration_url() ) ) . '">Crear cuenta</a>'; }
        else { echo '<p class="ft-truck-help">Por ahora el registro no está habilitado. Registro y reactivación se incorporarán en la etapa de usuarios; podés probar con una cuenta existente.</p>'; }
        echo '</div>';
    }
    public static function form() {
        if ( ! is_user_logged_in() ) { self::login( '/agregar-foodtruck/' ); return; }
        echo '<div class="ft-form-intro"><h2>' . ( ! empty( self::$data['id'] ) ? 'Proponer cambios en mi ficha' : 'Contanos sobre tu foodtruck' ) . '</h2><p>Revisaremos la información antes de publicarla. Los campos con * son obligatorios. No publicamos el email de tu cuenta.</p></div>';
        if ( self::$error ) { echo '<p class="ft-error" role="alert">' . esc_html( self::$error ) . '</p>'; }
        echo '<form class="ft-truck-form" method="post" enctype="multipart/form-data"><input type="hidden" name="instagram_token" value="">'; wp_nonce_field( 'ftuy_submit_foodtruck' ); FTUY_Foodtruck_Form::fields( self::$data, true );
        echo '<div class="ft-truck-help-box"><p>Tu cuenta quedará como responsable de esta ficha. Si ya está publicada, seguirá visible hasta que aprobemos los cambios.</p></div><button class="ft-button" type="submit">Enviar a revisión</button></form>';
    }
    public static function mine() {
        if ( ! is_user_logged_in() ) { self::login( '/mis-foodtrucks/' ); return; }
        global $wpdb; $page = max( 1, absint( $_GET['ft_page'] ?? 1 ) );
        if ( isset( $_GET['sent'] ) ) { echo '<p class="ft-notice" role="status">Recibimos tu propuesta. Está pendiente de revisión.</p>'; }
        echo '<p><a class="ft-button" href="' . esc_url( home_url( '/agregar-foodtruck/' ) ) . '">Agregar mi foodtruck</a></p>';
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Foodtrucks::table() . ' WHERE responsible_user_id=%d ORDER BY id DESC LIMIT 20 OFFSET %d', get_current_user_id(), ( $page - 1 ) * 20 ), ARRAY_A );
        foreach ( $rows as $e ) {
            $r = self::latest( $e['id'] ); $payload = $r ? json_decode( $r['payload'], true ) : array();
            $state = $r['status'] ?? $e['status'];
            echo '<article class="ft-my-event"><h2>' . esc_html( $payload['name'] ?? $e['name'] ) . '</h2><span class="ft-truck-mine-status">' . esc_html( FTUY_Admin::labels()[$state] ?? $state ) . '</span>';
            if ( $e['status'] === 'published' && $state !== 'published' ) { echo '<p>La versión aprobada sigue publicada mientras revisamos tus cambios.</p>'; }
            if ( $r && in_array( $state, array( 'corrections', 'rejected' ), true ) && $r['note'] ) { echo '<p>' . nl2br( esc_html( $r['note'] ) ) . '</p>'; }
            echo '<p class="ft-truck-account-links"><a class="ft-button ft-outline" href="' . esc_url( add_query_arg( 'edit', $e['id'], home_url( '/agregar-foodtruck/' ) ) ) . '">Editar mi ficha</a>';
            if ( $e['status'] === 'published' ) { echo '<a href="' . esc_url( FTUY_Foodtruck_Public::url( $e ) ) . '">Ver publicada</a>'; } echo '</p></article>';
        }
        if ( ! $rows ) { echo '<div class="ft-empty"><h2>Todavía no tenés foodtrucks cargados</h2><p>Podés enviar tu primera ficha para que la revisemos.</p></div>'; }
        $total = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . FTUY_Foodtrucks::table() . ' WHERE responsible_user_id=%d', get_current_user_id() ) );
        echo '<nav class="ft-pagination" aria-label="Mis foodtrucks">' . paginate_links( array( 'base' => add_query_arg( 'ft_page', '%#%' ), 'current' => $page, 'total' => max( 1, (int) ceil( $total / 20 ) ) ) ) . '</nav>';
    }
}
