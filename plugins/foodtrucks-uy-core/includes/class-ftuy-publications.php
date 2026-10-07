<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Publications {
    public static function table( $name = 'publications' ) { global $wpdb; return $wpdb->prefix . 'ft_' . $name; }
    public static function labels() { return array( 'pending' => 'Pendiente', 'published' => 'Publicada', 'unpublished' => 'Despublicada' ); }
    private static function length( $text ) { return function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text ); }
    public static function install() {
        global $wpdb; require_once ABSPATH . 'wp-admin/includes/upgrade.php'; $c = $wpdb->get_charset_collate();
        $definitions = array(
            'publications' => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n author_user_id bigint(20) unsigned NOT NULL,\n image_id bigint(20) unsigned NOT NULL,\n caption text NOT NULL,\n address varchar(255) NOT NULL DEFAULT '',\n latitude decimal(10,7) DEFAULT NULL,\n longitude decimal(10,7) DEFAULT NULL,\n status varchar(24) NOT NULL DEFAULT 'pending',\n version bigint(20) unsigned NOT NULL DEFAULT 1,\n legacy_id bigint(20) unsigned DEFAULT NULL,\n legacy_author_id bigint(20) unsigned DEFAULT NULL,\n legacy_slug varchar(200) NOT NULL DEFAULT '',\n legacy_status int DEFAULT NULL,\n created_at datetime NOT NULL,\n updated_at datetime NOT NULL,\n PRIMARY KEY  (id),\n UNIQUE KEY legacy_id (legacy_id),\n KEY feed (status,created_at,id),\n KEY author (author_user_id,status)",
            'publication_reports' => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n publication_id bigint(20) unsigned NOT NULL,\n reason text NOT NULL,\n status varchar(16) NOT NULL DEFAULT 'open',\n added_by bigint(20) unsigned NOT NULL,\n resolved_by bigint(20) unsigned NOT NULL DEFAULT 0,\n created_at datetime NOT NULL,\n resolved_at datetime DEFAULT NULL,\n PRIMARY KEY  (id),\n KEY queue (status,publication_id)",
            'publication_audit' => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n publication_id bigint(20) unsigned NOT NULL,\n actor_user_id bigint(20) unsigned NOT NULL,\n action varchar(32) NOT NULL,\n before_json longtext NOT NULL,\n after_json longtext NOT NULL,\n note text NOT NULL,\n created_at datetime NOT NULL,\n PRIMARY KEY  (id),\n KEY history (publication_id,id)"
        );
        foreach ( $definitions as $name => $body ) { $table = self::table( $name ); dbDelta( "CREATE TABLE $table (\n$body\n) ENGINE=InnoDB $c;" ); }
        foreach ( array_keys( $definitions ) as $name ) {
            $table = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS LIKE %s', $wpdb->esc_like( self::table( $name ) ) ), ARRAY_A );
            if ( ! $table || $table['Engine'] !== 'InnoDB' ) { return; }
        }
        if ( $role = get_role( 'administrator' ) ) { $role->add_cap( 'manage_ft_publications' ); }
        update_option( 'ftuy_publication_schema', '1', false );
    }
    public static function get( $id ) { global $wpdb; return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d', $id ), ARRAY_A ); }
    public static function validate( $input ) {
        if ( ! is_array( $input ) ) { return new WP_Error( 'fields', 'Datos inválidos.' ); }
        $data = array();
        foreach ( array( 'caption' => 10000, 'address' => 255, 'status' => 24 ) as $key => $max ) {
            if ( ! isset( $input[$key] ) || ! is_string( $input[$key] ) || strlen( $input[$key] ) > $max * 4 ) { return new WP_Error( 'fields', 'Datos inválidos.' ); }
            $data[$key] = $key === 'caption' ? sanitize_textarea_field( $input[$key] ) : sanitize_text_field( $input[$key] );
            if ( self::length( $data[$key] ) > $max ) { return new WP_Error( 'length', 'Un texto supera el largo permitido.' ); }
        }
        if ( ! isset( self::labels()[$data['status']] ) ) { return new WP_Error( 'status', 'Estado inválido.' ); }
        foreach ( array( 'latitude' => 90, 'longitude' => 180 ) as $key => $limit ) {
            $value = $input[$key] ?? '';
            if ( $value === '' || $value === null ) { $data[$key] = null; continue; }
            if ( ! is_scalar( $value ) || ! is_numeric( $value ) || ! is_finite( (float) $value ) || abs( (float) $value ) > $limit ) { return new WP_Error( 'coordinates', 'Coordenadas inválidas.' ); }
            $data[$key] = (float) $value;
        }
        if ( ( $data['latitude'] === null ) !== ( $data['longitude'] === null ) ) { return new WP_Error( 'coordinates', 'Completá ambas coordenadas o dejá ambas vacías.' ); }
        return $data;
    }
    private static function usable( $row ) {
        $post = get_post( $row['image_id'] ); $path = get_attached_file( $row['image_id'] );
        return get_user_by( 'id', $row['author_user_id'] ) && $post && wp_attachment_is_image( $post->ID ) && (int) $post->post_author === (int) $row['author_user_id'] && get_post_meta( $post->ID, '_ftuy_media_category', true ) === 'publicaciones' && $path && is_file( $path );
    }
    private static function audit( $id, $action, $before, $after, $note ) {
        global $wpdb;
        return $wpdb->insert( self::table( 'publication_audit' ), array( 'publication_id' => $id, 'actor_user_id' => get_current_user_id(), 'action' => $action, 'before_json' => wp_json_encode( $before ), 'after_json' => wp_json_encode( $after ), 'note' => $note, 'created_at' => current_time( 'mysql', true ) ) ) !== false;
    }
    /** Backend-only creation service; no website or REST upload form. */
    public static function create( $input, $author, $image ) {
        if ( ! current_user_can( 'manage_ft_publications' ) ) { return new WP_Error( 'permission', 'Sin permiso.' ); }
        if ( ! is_scalar( $author ) || ! is_scalar( $image ) || ! ctype_digit( (string) $author ) || ! ctype_digit( (string) $image ) ) { return new WP_Error( 'image', 'Referencias inválidas.' ); }
        $data = self::validate( $input ); if ( is_wp_error( $data ) ) { return $data; }
        $data['author_user_id'] = absint( $author ); $data['image_id'] = absint( $image );
        if ( ! self::usable( $data ) ) { return new WP_Error( 'image', 'La foto y su autor deben existir y estar vinculados.' ); }
        global $wpdb; $data['created_at'] = $data['updated_at'] = current_time( 'mysql', true );
        $wpdb->query( 'START TRANSACTION' );
        if ( $wpdb->insert( self::table(), $data ) === false ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'save', 'No se pudo guardar.' ); }
        $id = (int) $wpdb->insert_id;
        if ( ! self::audit( $id, 'create', array(), self::get( $id ), '' ) ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'save', 'No se pudo guardar el historial.' ); }
        $wpdb->query( 'COMMIT' ); return $id;
    }
    public static function edit( $id, $input, $version, $note = '' ) {
        if ( ! current_user_can( 'manage_ft_publications' ) ) { return new WP_Error( 'permission', 'Sin permiso.' ); }
        $data = self::validate( $input ); if ( is_wp_error( $data ) ) { return $data; }
        if ( ! is_string( $note ) || self::length( $note ) > 3000 ) { return new WP_Error( 'note', 'Nota inválida.' ); }
        global $wpdb; $wpdb->query( 'START TRANSACTION' );
        $before = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d FOR UPDATE', $id ), ARRAY_A );
        if ( ! $before || (int) $before['version'] !== (int) $version ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'conflict', 'La publicación cambió o no existe. Recargá antes de guardar.' ); }
        if ( $data['status'] === 'published' && ! self::usable( $before ) ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'image', 'No se puede publicar: revisar foto y autor.' ); }
        $data['updated_at'] = current_time( 'mysql', true ); $data['version'] = (int) $before['version'] + 1;
        $ok = $wpdb->update( self::table(), $data, array( 'id' => $id ) ) !== false && self::audit( $id, 'edit', $before, array_merge( $before, $data ), sanitize_textarea_field( $note ) );
        $wpdb->query( $ok ? 'COMMIT' : 'ROLLBACK' ); return $ok ? true : new WP_Error( 'save', 'No se pudo guardar la publicación y su historial.' );
    }
    public static function report( $id, $reason ) {
        if ( ! current_user_can( 'manage_ft_publications' ) ) { return new WP_Error( 'permission', 'Sin permiso.' ); }
        if ( ! self::get( $id ) || ! is_string( $reason ) || ! trim( sanitize_textarea_field( $reason ) ) || self::length( $reason ) > 3000 ) { return new WP_Error( 'report', 'Completá el motivo de la denuncia.' ); }
        global $wpdb; $wpdb->query( 'START TRANSACTION' );
        $data = array( 'publication_id' => $id, 'reason' => sanitize_textarea_field( $reason ), 'added_by' => get_current_user_id(), 'created_at' => current_time( 'mysql', true ) );
        $ok = $wpdb->insert( self::table( 'publication_reports' ), $data ) !== false && self::audit( $id, 'report', array(), $data, '' );
        $wpdb->query( $ok ? 'COMMIT' : 'ROLLBACK' ); return $ok ? true : new WP_Error( 'save', 'No se pudo registrar la denuncia.' );
    }
    public static function resolve( $publication_id, $report_id ) {
        if ( ! current_user_can( 'manage_ft_publications' ) ) { return new WP_Error( 'permission', 'Sin permiso.' ); }
        global $wpdb; $wpdb->query( 'START TRANSACTION' );
        $report = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'publication_reports' ) . ' WHERE id=%d AND publication_id=%d FOR UPDATE', $report_id, $publication_id ), ARRAY_A );
        if ( ! $report || $report['status'] !== 'open' ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'report', 'Denuncia inexistente o ya revisada.' ); }
        $data = array( 'status' => 'resolved', 'resolved_by' => get_current_user_id(), 'resolved_at' => current_time( 'mysql', true ) );
        $ok = $wpdb->update( self::table( 'publication_reports' ), $data, array( 'id' => $report_id ) ) !== false && self::audit( $publication_id, 'resolve_report', $report, array_merge( $report, $data ), '' );
        $wpdb->query( $ok ? 'COMMIT' : 'ROLLBACK' ); return $ok ? true : new WP_Error( 'save', 'No se pudo resolver la denuncia.' );
    }
    public static function listing( $status = 'published', $page = 1, $per_page = 20, $author = 0 ) {
        global $wpdb; $page = max( 1, (int) $page ); $per_page = max( 1, min( 50, (int) $per_page ) );
        $where = '1=1';
        if ( $status === 'reported' ) { $where .= ' AND EXISTS (SELECT 1 FROM ' . self::table( 'publication_reports' ) . " r WHERE r.publication_id=p.id AND r.status='open')"; }
        elseif ( isset( self::labels()[$status] ) ) { $where .= $wpdb->prepare( ' AND p.status=%s', $status ); }
        elseif ( $status !== 'all' ) { $where .= ' AND 1=0'; }
        if ( $author ) { $where .= $wpdb->prepare( ' AND p.author_user_id=%d', $author ); }
        $table = self::table();
        return array( 'items' => $wpdb->get_results( $wpdb->prepare( "SELECT p.* FROM $table p WHERE $where ORDER BY p.created_at DESC,p.id DESC LIMIT %d OFFSET %d", $per_page, ( $page - 1 ) * $per_page ), ARRAY_A ), 'total' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table p WHERE $where" ), 'page' => $page );
    }
    public static function public_data( $row ) {
        $user = get_user_by( 'id', $row['author_user_id'] );
        return array( 'id' => (int) $row['id'], 'caption' => $row['caption'], 'address' => $row['address'], 'latitude' => $row['latitude'] === null ? null : (float) $row['latitude'], 'longitude' => $row['longitude'] === null ? null : (float) $row['longitude'], 'created_at' => $row['created_at'], 'updated_at' => $row['updated_at'], 'image' => array( 'full' => wp_get_attachment_image_url( $row['image_id'], 'full' ), 'thumbnail' => wp_get_attachment_image_url( $row['image_id'], 'medium_large' ) ), 'author' => array( 'id' => (int) $row['author_user_id'], 'name' => $user ? $user->display_name : 'Usuario', 'avatar' => $user && FTUY_Profile_Images::attachment( $user->ID ) ? get_avatar_url( $user->ID ) : null ) );
    }
    public static function api() {
        register_rest_route( 'foodtrucks-uy/v1', '/publications', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'args' => array( 'page' => array( 'type' => 'integer', 'minimum' => 1, 'default' => 1 ), 'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20 ), 'author' => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ) ), 'callback' => function ( $request ) {
            $data = self::listing( 'published', $request['page'], $request['per_page'], $request['author'] ); $data['items'] = array_map( array( __CLASS__, 'public_data' ), $data['items'] );
            $response = new WP_REST_Response( $data ); $response->header( 'Cache-Control', 'no-store' ); return $response;
        } ) );
        register_rest_route( 'foodtrucks-uy/v1', '/publications/(?P<id>\d+)', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function ( $request ) {
            $row = self::get( $request['id'] );
            if ( ! $row || $row['status'] !== 'published' ) { return new WP_Error( 'not_found', 'Publicación no encontrada.', array( 'status' => 404 ) ); }
            $response = new WP_REST_Response( self::public_data( $row ) ); $response->header( 'Cache-Control', 'no-store' ); return $response;
        } ) );
    }
}
