<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Foodtrucks {
    public static function table( $name = 'foodtrucks' ) { global $wpdb; return $wpdb->prefix . 'ft_' . $name; }
    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $c = $wpdb->get_charset_collate(); $t = self::table();
        dbDelta( "CREATE TABLE $t (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slug varchar(191) NOT NULL,
            name varchar(255) NOT NULL,
            description longtext NOT NULL,
            food_offering text NOT NULL,
            department varchar(64) NOT NULL,
            locality varchar(191) NOT NULL,
            whatsapp varchar(32) NOT NULL DEFAULT '',
            instagram varchar(500) NOT NULL DEFAULT '',
            serves_events tinyint(1) NOT NULL DEFAULT 0,
            serves_private_events tinyint(1) NOT NULL DEFAULT 0,
            has_fixed_location tinyint(1) NOT NULL DEFAULT 0,
            responsible_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            status varchar(24) NOT NULL DEFAULT 'pending',
            sample_key varchar(191) DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            UNIQUE KEY sample_key (sample_key),
            KEY listing (status,department),
            KEY responsible (responsible_user_id)
        ) ENGINE=InnoDB $c;" );
        $definitions = array(
            'cuisine_categories' => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n name varchar(191) NOT NULL,\n slug varchar(191) NOT NULL,\n PRIMARY KEY  (id),\n UNIQUE KEY slug (slug)",
            'foodtruck_cuisines' => "foodtruck_id bigint(20) unsigned NOT NULL,\n cuisine_id bigint(20) unsigned NOT NULL,\n PRIMARY KEY  (foodtruck_id,cuisine_id),\n KEY cuisine (cuisine_id)",
            'foodtruck_images' => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n foodtruck_id bigint(20) unsigned NOT NULL,\n attachment_id bigint(20) unsigned NOT NULL,\n role varchar(16) NOT NULL,\n sort_order int NOT NULL DEFAULT 0,\n PRIMARY KEY  (id),\n KEY foodtruck (foodtruck_id,role)",
            'foodtruck_reviews' => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n foodtruck_id bigint(20) unsigned NOT NULL,\n author_id bigint(20) unsigned NOT NULL,\n payload longtext NOT NULL,\n status varchar(24) NOT NULL DEFAULT 'pending',\n note text NOT NULL,\n reviewer_id bigint(20) unsigned NOT NULL DEFAULT 0,\n created_at datetime NOT NULL,\n reviewed_at datetime DEFAULT NULL,\n PRIMARY KEY  (id),\n KEY queue (status,created_at),\n KEY foodtruck (foodtruck_id)"
        );
        foreach ( $definitions as $name => $body ) { $table = self::table( $name ); dbDelta( "CREATE TABLE $table (\n$body\n) ENGINE=InnoDB $c;" ); }
        foreach ( array_merge( array( 'foodtrucks' ), array_keys( $definitions ) ) as $name ) { if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( self::table( $name ) ) ) ) ) { return; } }
        if ( $role = get_role( 'administrator' ) ) { $role->add_cap( 'manage_ft_foodtrucks' ); }
        foreach ( array( 'Churros y crepes', 'Helados y postres', 'Chivitos', 'Café y desayunos', 'Cervezas', 'Dulces y tortas', 'Licuados y jugos', 'Hamburguesas', 'Tortas fritas', 'Pizzas y calzones', 'Street food', 'Panchos y hot dogs', 'Tacos y burritos', 'Comida de Medio Oriente' ) as $name ) {
            $wpdb->query( $wpdb->prepare( 'INSERT IGNORE INTO ' . self::table( 'cuisine_categories' ) . ' (name,slug) VALUES (%s,%s)', $name, sanitize_title( $name ) ) );
        }
        update_option( 'ftuy_foodtruck_schema', '1', false );
    }
    public static function cuisines() { global $wpdb; return $wpdb->get_results( 'SELECT * FROM ' . self::table( 'cuisine_categories' ) . ' ORDER BY name', ARRAY_A ); }
    public static function get( $id ) {
        global $wpdb; $e = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d', $id ), ARRAY_A );
        if ( ! $e ) { return null; }
        $e['cuisine_ids'] = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( 'SELECT cuisine_id FROM ' . self::table( 'foodtruck_cuisines' ) . ' WHERE foodtruck_id=%d', $id ) ) );
        $e['images'] = $wpdb->get_results( $wpdb->prepare( 'SELECT attachment_id,role,sort_order FROM ' . self::table( 'foodtruck_images' ) . ' WHERE foodtruck_id=%d ORDER BY sort_order,id', $id ), ARRAY_A );
        $e['images'] = FTUY_Foodtruck_Images::normalize( $e['images'] );
        return $e;
    }
    public static function validate( $input ) {
        if ( ! is_array( $input ) ) { return new WP_Error( 'input', 'Datos inválidos.' ); }
        $data = array();
        foreach ( array( 'name' => 255, 'description' => 20000, 'food_offering' => 3000, 'department' => 64, 'locality' => 191, 'whatsapp' => 32, 'instagram' => 500 ) as $key => $max ) {
            if ( isset( $input[$key] ) && ! is_scalar( $input[$key] ) ) { return new WP_Error( 'input', 'Datos inválidos.' ); }
            $data[$key] = $key === 'description' ? wp_kses_post( $input[$key] ?? '' ) : sanitize_text_field( $input[$key] ?? '' );
            if ( ( function_exists( 'mb_strlen' ) ? mb_strlen( $data[$key] ) : strlen( $data[$key] ) ) > $max ) { return new WP_Error( 'length', 'Un campo supera el largo permitido.' ); }
        }
        foreach ( array( 'name', 'description', 'food_offering', 'department', 'locality' ) as $key ) { if ( ! trim( wp_strip_all_tags( $data[$key] ) ) ) { return new WP_Error( 'required', 'Completá nombre, descripción, qué sirven, departamento y localidad.' ); } }
        if ( ! in_array( $data['department'], FTUY_Events::departments(), true ) ) { return new WP_Error( 'department', 'Departamento inválido.' ); }
        $ig = FTUY_Events::instagram_url( $data['instagram'] ); if ( is_wp_error( $ig ) ) { return $ig; } $data['instagram'] = $ig;
        $phone = preg_replace( '/[\s()+.-]/', '', $data['whatsapp'] );
        if ( preg_match( '/^09\d{7}$/', $phone ) ) { $phone = '598' . substr( $phone, 1 ); }
        if ( $phone && ! preg_match( '/^[1-9]\d{7,14}$/', $phone ) ) { return new WP_Error( 'whatsapp', 'Usá un celular uruguayo (09…) o un número con código de país.' ); }
        $data['whatsapp'] = $phone;
        foreach ( array( 'serves_events', 'serves_private_events', 'has_fixed_location' ) as $key ) { if ( isset( $input[$key] ) && ! is_scalar( $input[$key] ) ) { return new WP_Error( 'input', 'Modalidad inválida.' ); } $data[$key] = empty( $input[$key] ) ? 0 : 1; }
        if ( ! $data['serves_events'] && ! $data['serves_private_events'] && ! $data['has_fixed_location'] ) { return new WP_Error( 'mode', 'Elegí al menos una modalidad.' ); }
        $ids = $input['cuisine_ids'] ?? array(); if ( ! is_array( $ids ) || count( $ids ) > 30 ) { return new WP_Error( 'cuisine', 'Rubros inválidos.' ); }
        foreach ( $ids as $id ) { if ( ! is_scalar( $id ) || ! ctype_digit( (string) $id ) ) { return new WP_Error( 'cuisine', 'Rubros inválidos.' ); } }
        $data['cuisine_ids'] = array_values( array_unique( array_map( 'absint', $ids ) ) );
        $known = array_map( 'intval', array_column( self::cuisines(), 'id' ) );
        if ( ! $ids || array_diff( $data['cuisine_ids'], $known ) ) { return new WP_Error( 'cuisine', 'Seleccioná uno o varios rubros válidos.' ); }
        $images = $input['images'] ?? array(); if ( ! is_array( $images ) || count( $images ) > 10 ) { return new WP_Error( 'image', 'Imágenes inválidas.' ); }
        foreach ( $images as $image ) { if ( ! is_array( $image ) || ! isset( $image['role'], $image['attachment_id'] ) || ! is_scalar( $image['role'] ) || ! is_scalar( $image['attachment_id'] ) || ! in_array( $image['role'], array( 'logo', 'truck_photo', 'cover', 'official' ), true ) ) { return new WP_Error( 'image', 'Imagen inválida.' ); } }
        $images = FTUY_Foodtruck_Images::normalize( $images );
        if ( count( $images ) > 2 ) { return new WP_Error( 'image', 'Usá un logo y una foto del foodtruck.' ); }
        $data['images'] = array(); $single = array();
        foreach ( $images as $i => $image ) {
            if ( ! is_array( $image ) || ! isset( $image['attachment_id'], $image['role'] ) || ! is_scalar( $image['attachment_id'] ) || ! is_scalar( $image['role'] ) ) { return new WP_Error( 'image', 'Imagen inválida.' ); }
            $id = absint( $image['attachment_id'] ); $role = $image['role'];
            if ( ! in_array( $role, array( 'logo', 'truck_photo' ), true ) || ! wp_attachment_is_image( $id ) || isset( $single[$role] ) ) { return new WP_Error( 'image', 'Revisá los medios: un logo y una foto del foodtruck.' ); }
            $single[$role] = true; $data['images'][] = array( 'attachment_id' => $id, 'role' => $role, 'sort_order' => $i );
        }
        return $data;
    }
    public static function propose( $input, $actor, $id = 0, $responsible = null ) {
        global $wpdb; $existing = $id ? self::get( $id ) : null; $manager = user_can( $actor, 'manage_ft_foodtrucks' );
        if ( $id && ( ! $existing || ( ! $manager && (int) $existing['responsible_user_id'] !== (int) $actor ) ) ) { return new WP_Error( 'owner', 'No podés editar esta ficha.' ); }
        $responsible = $manager && $responsible !== null ? absint( $responsible ) : ( $existing['responsible_user_id'] ?? $actor );
        if ( ! get_userdata( $responsible ) ) { return new WP_Error( 'owner', 'Cuenta responsable inválida.' ); }
        $data = self::validate( $input ); if ( is_wp_error( $data ) ) { return $data; }
        if ( ! $manager ) {
            $allowed = array_map( 'intval', array_column( $existing['images'] ?? array(), 'attachment_id' ) );
            if ( $existing ) {
                $latest = $wpdb->get_var( $wpdb->prepare( 'SELECT payload FROM ' . self::table( 'foodtruck_reviews' ) . ' WHERE foodtruck_id=%d ORDER BY id DESC LIMIT 1', $id ) );
                $latest = json_decode( $latest ?: '{}', true );
                $allowed = array_merge( $allowed, array_map( 'intval', array_column( $latest['images'] ?? array(), 'attachment_id' ) ) );
            }
            foreach ( $data['images'] as $image ) { if ( (int) get_post_field( 'post_author', $image['attachment_id'] ) !== (int) $actor && ! in_array( $image['attachment_id'], $allowed, true ) ) { return new WP_Error( 'image_owner', 'No podés usar ese medio en tu ficha.' ); } }
        }
        $data['responsible_user_id'] = $responsible; $now = current_time( 'mysql', true ); $table = self::table();
        $wpdb->query( 'START TRANSACTION' );
        if ( ! $id ) {
            $base = sanitize_title( $data['name'] ) ?: 'foodtruck'; $slug = $base; $n = 2;
            while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE slug=%s", $slug ) ) ) { $slug = $base . '-' . $n++; }
            $row = $data; unset( $row['images'], $row['cuisine_ids'] );
            if ( false === $wpdb->insert( $table, array_merge( $row, array( 'slug' => $slug, 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now ) ) ) ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'save', 'No se pudo guardar.' ); }
            $id = $wpdb->insert_id;
        }
        $wpdb->query( $wpdb->prepare( 'UPDATE ' . self::table( 'foodtruck_reviews' ) . " SET status='superseded' WHERE foodtruck_id=%d AND status IN ('pending','corrections')", $id ) );
        $ok = $wpdb->insert( self::table( 'foodtruck_reviews' ), array( 'foodtruck_id' => $id, 'author_id' => $actor, 'payload' => wp_json_encode( $data ), 'note' => '', 'created_at' => $now ) );
        if ( false === $ok ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'save', 'No se pudo guardar la propuesta.' ); }
        $review = $wpdb->insert_id; $wpdb->query( 'COMMIT' ); return $review;
    }
    public static function review( $id ) { global $wpdb; return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'foodtruck_reviews' ) . ' WHERE id=%d', $id ), ARRAY_A ); }
    public static function moderate( $id, $decision, $note = '' ) {
        global $wpdb;
        if ( ! current_user_can( 'manage_ft_foodtrucks' ) || ! in_array( $decision, array( 'published', 'corrections', 'rejected' ), true ) ) { return new WP_Error( 'permission', 'Sin permiso o decisión inválida.' ); }
        $wpdb->query( 'START TRANSACTION' );
        $r = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'foodtruck_reviews' ) . ' WHERE id=%d FOR UPDATE', $id ), ARRAY_A );
        if ( ! $r || ! in_array( $r['status'], array( 'pending', 'corrections' ), true ) ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'closed', 'La revisión ya está cerrada.' ); }
        $payload = json_decode( $r['payload'], true ); $data = self::validate( $payload );
        if ( $decision === 'published' ) {
            if ( is_wp_error( $data ) || ! get_userdata( $payload['responsible_user_id'] ?? 0 ) ) { $wpdb->query( 'ROLLBACK' ); return is_wp_error( $data ) ? $data : new WP_Error( 'owner', 'Cuenta responsable inválida.' ); }
            $row = $data; unset( $row['images'], $row['cuisine_ids'] ); $row['responsible_user_id'] = absint( $payload['responsible_user_id'] ); $row['status'] = 'published'; $row['updated_at'] = current_time( 'mysql', true );
            $ok = $wpdb->update( self::table(), $row, array( 'id' => $r['foodtruck_id'] ) );
            $ok = $ok !== false && $wpdb->delete( self::table( 'foodtruck_cuisines' ), array( 'foodtruck_id' => $r['foodtruck_id'] ) ) !== false && $wpdb->delete( self::table( 'foodtruck_images' ), array( 'foodtruck_id' => $r['foodtruck_id'] ) ) !== false;
            foreach ( $data['cuisine_ids'] as $cuisine ) { $ok = $wpdb->insert( self::table( 'foodtruck_cuisines' ), array( 'foodtruck_id' => $r['foodtruck_id'], 'cuisine_id' => $cuisine ) ) !== false && $ok; }
            foreach ( $data['images'] as $image ) { $ok = $wpdb->insert( self::table( 'foodtruck_images' ), array_merge( $image, array( 'foodtruck_id' => $r['foodtruck_id'] ) ) ) !== false && $ok; }
            if ( ! $ok ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'save', 'No se pudo publicar.' ); }
        }
        $ok = $wpdb->update( self::table( 'foodtruck_reviews' ), array( 'status' => $decision, 'note' => sanitize_textarea_field( $note ), 'reviewer_id' => get_current_user_id(), 'reviewed_at' => current_time( 'mysql', true ) ), array( 'id' => $id ) );
        if ( false === $ok ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'save', 'No se pudo guardar la revisión.' ); }
        $wpdb->query( 'COMMIT' ); return true;
    }
    public static function public_data( $e ) {
        $data = array_intersect_key( $e, array_flip( array( 'id', 'slug', 'name', 'description', 'food_offering', 'department', 'locality', 'whatsapp', 'instagram', 'updated_at' ) ) ); $data['id'] = (int) $e['id'];
        foreach ( array( 'serves_events', 'serves_private_events', 'has_fixed_location' ) as $key ) { $data[$key] = (bool) $e[$key]; }
        $data['cuisines'] = array_values( array_filter( self::cuisines(), function ( $c ) use ( $e ) { return in_array( (int) $c['id'], $e['cuisine_ids'], true ); } ) );
        $data['images'] = array_map( function ( $i ) { return array( 'role' => $i['role'], 'url' => wp_get_attachment_image_url( $i['attachment_id'], 'large' ) ?: null ); }, $e['images'] );
        return $data;
    }
    public static function sample() {
        global $wpdb;
        if ( ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) { return array( 'error' => 'Solo en local.' ); }
        $admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) ); if ( ! $admin ) { return array( 'error' => 'Sin administrador.' ); }
        $samples = array( 76 => array( 'Street food', 'Servicio gastronómico para fiestas y eventos', 'Montevideo', 'Montevideo' ), 79 => array( 'Comida de Medio Oriente', 'Comida de Medio Oriente, mediterránea, judía y vegetariana', 'Montevideo', 'Montevideo' ), 2922 => array( 'Street food', 'Street food', 'Montevideo', 'Montevideo' ), 2810 => array( 'Pizzas y calzones', 'Pizzas', 'Montevideo', 'Montevideo' ) );
        $result = array();
        foreach ( $samples as $post_id => $s ) {
            $key = 'pilot-wp-' . $post_id;
            if ( $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE sample_key=%s', $key ) ) ) { $result[] = array( 'source' => $post_id, 'skipped' => true ); continue; }
            $p = get_post( $post_id ); if ( ! $p || $p->post_type !== 'speaker' ) { $result[] = array( 'source' => $post_id, 'error' => 'Origen no disponible' ); continue; }
            $instagram = ''; foreach ( (array) get_post_meta( $post_id, 'social-links', true ) as $link ) { if ( is_array( $link ) && strpos( $link['url'] ?? '', 'instagram.com/' ) !== false ) { $instagram = $link['url']; } }
            $cuisine = array_values( array_filter( self::cuisines(), function ( $c ) use ( $s ) { return $c['name'] === $s[0]; } ) );
            $photo = attachment_url_to_postid( get_post_meta( $post_id, 'speaker-profile-photo', true ) ); if ( ! $photo ) { $photo = get_post_thumbnail_id( $post_id ); }
            $data = array( 'name' => $p->post_title, 'description' => $p->post_content, 'food_offering' => $s[1], 'department' => $s[2], 'locality' => $s[3], 'instagram' => $instagram, 'whatsapp' => '', 'serves_events' => 1, 'serves_private_events' => 1, 'cuisine_ids' => array_column( $cuisine, 'id' ), 'images' => $photo ? array( array( 'attachment_id' => $photo, 'role' => 'cover' ) ) : array() );
            $review = self::propose( $data, $admin[0]->ID ); if ( is_wp_error( $review ) ) { $result[] = array( 'source' => $post_id, 'error' => $review->get_error_message() ); continue; }
            $r = self::review( $review ); $wpdb->update( self::table(), array( 'sample_key' => $key ), array( 'id' => $r['foodtruck_id'] ) );
            $wpdb->update( self::table( 'foodtruck_reviews' ), array( 'note' => 'Muestra histórica: confirmar nombre, localidad, modalidades, WhatsApp y medios. Responsable provisional: administrador de carga; no titular verificado.' ), array( 'id' => $review ) );
            $result[] = array( 'id' => $r['foodtruck_id'], 'name' => $data['name'], 'status' => 'pending' );
        }
        return $result;
    }
}
