<?php
defined( 'ABSPATH' ) || exit;

/** App-only uploads. Bearer identity, direct publication, retry-safe receipts. */
class FTUY_Publication_Upload {
    public static function init() {
        add_action( 'rest_api_init', function () {
            register_rest_route( 'foodtrucks-uy/v1', '/publications/upload', array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => array( __CLASS__, 'upload' ) ) );
        } );
        add_filter( 'rest_post_dispatch', function ( $response, $server, $request ) {
            if ( $request->get_route() === '/foodtrucks-uy/v1/publications/upload' ) { $response->header( 'Cache-Control', 'no-store, private' ); }
            return $response;
        }, 10, 3 );
    }
    public static function error( $message, $status = 400 ) { return new WP_Error( 'publication_upload', $message, array( 'status' => $status ) ); }
    private static function receipt( $id ) { return new WP_REST_Response( array( 'publication_id' => (int) $id ), 201 ); }
    public static function upload( $request ) {
        $auth = FTUY_App_Sessions::authenticate( $request );
        if ( is_wp_error( $auth ) ) { return $auth; }
        $actor = (int) $auth['user']->ID; $client = $request['request_id'];
        if ( ! is_string( $client ) || ! preg_match( '/^[a-zA-Z0-9_-]{20,80}$/D', $client ) ) { return self::error( 'No pudimos identificar la subida. Volvé a intentar.' ); }
        $key = hash_hmac( 'sha256', $actor . ':' . $client, wp_salt( 'auth' ) );
        global $wpdb;
        $find = function () use ( $wpdb, $key ) { return $wpdb->get_var( $wpdb->prepare( 'SELECT publication_id FROM ' . FTUY_Publications::table( 'publication_uploads' ) . ' WHERE request_key=%s', $key ) ); };
        if ( $existing = $find() ) { return self::receipt( $existing ); }
        $input = array( 'caption' => $request['caption'], 'address' => $request['address'], 'latitude' => $request['latitude'] ?? '', 'longitude' => $request['longitude'] ?? '', 'status' => 'published' );
        $input['street_address'] = $request['street_address'] ?? '';
        $data = FTUY_Publications::validate( $input );
        if ( is_wp_error( $data ) ) { return self::error( $data->get_error_message() ); }
        if ( ! trim( $data['caption'] ) || ! trim( $data['address'] ) ) { return self::error( 'Completá el texto y la dirección de la foto.' ); }
        $files = $request->get_file_params(); $file = $files['photo'] ?? null;
        if ( is_array( $file ) && in_array( $file['error'] ?? -1, array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) ) { return self::error( 'La imagen pesa demasiado. Elegí una de hasta 5 MB.', 413 ); }
        if ( ! is_array( $file ) || ( $file['error'] ?? -1 ) !== UPLOAD_ERR_OK || ! is_string( $file['tmp_name'] ?? null ) || ! is_uploaded_file( $file['tmp_name'] ) ) { return self::error( 'Elegí una foto para publicar.' ); }
        if ( filesize( $file['tmp_name'] ) > 5 * MB_IN_BYTES ) { return self::error( 'La imagen pesa demasiado. Elegí una de hasta 5 MB.', 413 ); }
        $lock = 'ftuy_publication_upload_lock_' . $actor; $stamp = (string) time();
        $old = get_option( $lock );
        if ( $old && (int) $old < time() - 300 ) { $wpdb->delete( $wpdb->options, array( 'option_name' => $lock, 'option_value' => (string) $old ) ); wp_cache_delete( $lock, 'options' ); }
        if ( ! add_option( $lock, $stamp, '', false ) ) { return self::error( 'Hay una foto procesándose. Esperá un momento y volvé a intentar.', 429 ); }
        $image = 0; $committed = false;
        try {
            if ( $existing = $find() ) { return self::receipt( $existing ); }
            if ( FTUY_Accounts::limited( 'publication-upload-' . $actor, 10, HOUR_IN_SECONDS ) ) { return self::error( 'Subiste varias fotos. Probá nuevamente más tarde.', 429 ); }
            $image = FTUY_Foodtruck_Images::process( $file['tmp_name'], 'community_photo', 'foto', 'publicaciones' );
            if ( is_wp_error( $image ) ) { return self::error( $image->get_error_message() ); }
            $owner = wp_update_post( array( 'ID' => $image, 'post_author' => $actor ), true );
            if ( is_wp_error( $owner ) ) { return self::error( 'No pudimos guardar la foto. Intentá nuevamente.', 503 ); }
            $data['author_user_id'] = $actor; $data['image_id'] = $image;
            $data['created_at'] = $data['updated_at'] = current_time( 'mysql', true );
            $wpdb->query( 'START TRANSACTION' );
            if ( $wpdb->insert( FTUY_Publications::table(), $data ) === false ) { $wpdb->query( 'ROLLBACK' ); return self::error( 'No pudimos publicar la foto. Intentá nuevamente.', 503 ); }
            $id = (int) $wpdb->insert_id;
            $audit = array( 'publication_id' => $id, 'actor_user_id' => $actor, 'action' => 'create', 'before_json' => '{}', 'after_json' => wp_json_encode( $data ), 'note' => 'Publicación directa desde la app.', 'created_at' => $data['created_at'] );
            $ok = $wpdb->insert( FTUY_Publications::table( 'publication_uploads' ), array( 'request_key' => $key, 'publication_id' => $id ) ) !== false && $wpdb->insert( FTUY_Publications::table( 'publication_audit' ), $audit ) !== false;
            $wpdb->query( $ok ? 'COMMIT' : 'ROLLBACK' );
            if ( ! $ok ) { return self::error( 'No pudimos guardar la publicación. Intentá nuevamente.', 503 ); }
            $committed = true; return self::receipt( $id );
        } finally {
            if ( ! $committed && $image && ! is_wp_error( $image ) ) { wp_delete_attachment( $image, true ); }
            // Delete only our lease, not a recovered lock owned by another request.
            $wpdb->delete( $wpdb->options, array( 'option_name' => $lock, 'option_value' => $stamp ) ); wp_cache_delete( $lock, 'options' );
        }
    }
}
