<?php
if ( ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo local.' ); }
require_once ABSPATH . 'wp-admin/includes/file.php';
global $wpdb;
$user = 0; $files = array(); $count = 0;
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
try {
    $user = wp_insert_user( array( 'user_login' => 'upload-' . wp_generate_uuid4(), 'user_email' => wp_generate_uuid4() . '@example.invalid', 'user_pass' => wp_generate_password( 32 ), 'role' => 'subscriber', 'display_name' => 'Fixture foto', 'meta_input' => array( 'ftuy_account_status' => 'active' ) ) );
    if ( is_wp_error( $user ) ) { throw new RuntimeException( 'Fixture.' ); }
    $secret = bin2hex( random_bytes( 32 ) ); $token = $user . '.' . $secret;
    update_user_meta( $user, FTUY_App_Sessions::PREFIX . hash( 'sha256', $secret ), array( 'expires' => time() + 3600, 'password_signature' => hash_hmac( 'sha256', get_userdata( $user )->user_pass, wp_salt( 'auth' ) ) ) );
    $source = wp_tempnam( 'ftuy-publication-jpeg' ); $files[] = $source;
    $image = imagecreatetruecolor( 1600, 1000 ); imagefill( $image, 0, 0, imagecolorallocate( $image, 150, 100, 60 ) ); imagejpeg( $image, $source ); imagedestroy( $image );
    $hash = hash_file( 'sha256', $source ); $uuid = wp_generate_uuid4();
    $upload = function ( $file, $overrides = array(), $auth = true ) use ( $token, $uuid ) {
        $fields = array_merge( array( 'photo' => new CURLFile( $file, 'image/jpeg', 'foto.jpg' ), 'request_id' => $uuid, 'caption' => 'Foto de prueba', 'address' => 'Plaza de prueba', 'latitude' => '-34.9', 'longitude' => '-56.2', 'author_user_id' => '1', 'status' => 'pending', 'puntaje' => '5' ), $overrides );
        $ch = curl_init( rest_url( 'foodtrucks-uy/v1/publications/upload' ) );
        curl_setopt_array( $ch, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => $auth ? array( 'Authorization: Bearer ' . $token ) : array(), CURLOPT_POST => true, CURLOPT_POSTFIELDS => $fields ) );
        $body = curl_exec( $ch ); $status = curl_getinfo( $ch, CURLINFO_HTTP_CODE ); curl_close( $ch ); return array( $status, json_decode( $body, true ) );
    };
    list( $code ) = $upload( $source, array(), false ); $assert( $code === 401, 'Sin sesión rechazada.' );
    list( $code ) = $upload( $source, array( 'caption' => '' ) ); $assert( $code === 400, 'Texto vacío rechazado.' );
    list( $code ) = $upload( $source, array( 'latitude' => '200' ) ); $assert( $code === 400, 'Coordenadas inválidas rechazadas.' );
    list( $code, $data ) = $upload( $source ); $id = (int) ( $data['publication_id'] ?? 0 );
    $assert( $code === 201 && $id, 'Foto publicada.' );
    $row = FTUY_Publications::get( $id ); $attachment = (int) $row['image_id']; $path = get_attached_file( $attachment ); $info = wp_getimagesize( $path );
    $assert( (int) $row['author_user_id'] === $user && $row['status'] === 'published' && (int) get_post( $attachment )->post_author === $user, 'Autor real y publicación directa; ignora manipulación.' );
    $assert( $info[0] === 900 && $info[1] < 900 && $info['mime'] === 'image/jpeg' && filesize( $path ) <= 300 * 1024, 'Optimiza y conserva proporción; sin crop.' );
    $assert( strpos( $path, '/media/publicaciones/' ) !== false && hash_file( 'sha256', $source ) === $hash, 'Almacenamiento independiente; fuente sin modificar.' );
    list( $code, $again ) = $upload( $source );
    $assert( $code === 201 && $again['publication_id'] === $id && (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . FTUY_Publications::table() . ' WHERE author_user_id=%d', $user ) ) === 1, 'Reintento idempotente.' );
    $public = wp_remote_get( rest_url( 'foodtrucks-uy/v1/publications/' . $id ) );
    $assert( wp_remote_retrieve_response_code( $public ) === 200, 'Detalle público inmediatamente.' );
    $mine = FTUY_Publications::listing( 'published', 1, 12, $user ); $assert( $mine['total'] === 1, 'Disponible en Mis fotos.' );
    $bad = wp_tempnam( 'ftuy-publication-bad' ); $files[] = $bad; file_put_contents( $bad, '<?php not an image' );
    list( $code ) = $upload( $bad, array( 'request_id' => wp_generate_uuid4() ) ); $assert( $code === 400, 'Rechaza archivo falso.' );
    $large = wp_tempnam( 'ftuy-publication-large' ); $files[] = $large; file_put_contents( $large, str_repeat( 'x', 5 * MB_IN_BYTES + 1 ) );
    list( $code ) = $upload( $large, array( 'request_id' => wp_generate_uuid4() ) ); $assert( $code === 413, 'Rechaza más de 5 MB.' );
    $wpdb->update( FTUY_Publications::table(), array( 'status' => 'unpublished' ), array( 'id' => $id ) );
    $public = wp_remote_get( rest_url( 'foodtrucks-uy/v1/publications/' . $id ) ); $assert( wp_remote_retrieve_response_code( $public ) === 404, 'Despublicada desaparece.' );
    list( $code, $again ) = $upload( $source ); $assert( $code === 201 && $again['publication_id'] === $id && FTUY_Publications::get( $id )['status'] === 'unpublished', 'Reintento no republica una foto retirada.' );
    echo "OK: $count comprobaciones HTTP de publicación.\n";
} finally {
    if ( $user && ! is_wp_error( $user ) ) {
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id,image_id FROM ' . FTUY_Publications::table() . ' WHERE author_user_id=%d', $user ), ARRAY_A );
        foreach ( $rows as $row ) {
            foreach ( array( 'publication_uploads', 'publication_audit', 'publication_reports' ) as $table ) { $wpdb->delete( FTUY_Publications::table( $table ), array( 'publication_id' => $row['id'] ) ); }
            $wpdb->delete( FTUY_Publications::table(), array( 'id' => $row['id'] ) ); wp_delete_attachment( $row['image_id'], true );
        }
        delete_option( 'ftuy_publication_upload_lock_' . $user );
        delete_transient( 'ftuy_ac_' . hash_hmac( 'sha256', 'publication-upload-' . $user, wp_salt( 'auth' ) ) );
        require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $user );
    }
    foreach ( $files as $file ) { wp_delete_file( $file ); }
}
