<?php
if ( ! defined( 'ABSPATH' ) || ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo local.' ); }
global $wpdb; $old_user = get_current_user_id(); $old_cookie = $_COOKIE[LOGGED_IN_COOKIE] ?? null; $count = 0; $id = 0; $uid = 0; $image = 0; $source = null; $tokens = array();
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
$call = function ( $path, $cookie = '', $post = null ) {
    $curl = curl_init( home_url( $path ) ); curl_setopt_array( $curl, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 20, CURLOPT_COOKIE => $cookie ) );
    if ( $post !== null ) { curl_setopt( $curl, CURLOPT_POST, true ); curl_setopt( $curl, CURLOPT_POSTFIELDS, http_build_query( $post ) ); }
    $body = curl_exec( $curl ); $status = curl_getinfo( $curl, CURLINFO_HTTP_CODE ); $error = curl_error( $curl ); curl_close( $curl ); if ( $error ) { throw new RuntimeException( $error ); } return array( $status, $body );
};
$session = function ( $user ) use ( &$tokens ) {
    $expires = time() + 600; $token = WP_Session_Tokens::get_instance( $user )->create( $expires ); $tokens[] = array( $user, $token );
    $login = wp_generate_auth_cookie( $user, $expires, 'logged_in', $token ); $_COOKIE[LOGGED_IN_COOKIE] = $login; wp_set_current_user( $user );
    return AUTH_COOKIE . '=' . wp_generate_auth_cookie( $user, $expires, 'auth', $token ) . '; ' . LOGGED_IN_COOKIE . '=' . $login;
};
try {
    $admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID; $admin_cookie = $session( $admin );
    $nonce = wp_create_nonce( 'ftuy_publication' );
    $uid = wp_insert_user( array( 'user_login' => 'ftuy_phttp_' . wp_generate_uuid4(), 'user_email' => 'ftuy-phttp-' . wp_generate_uuid4() . '@example.invalid', 'user_pass' => wp_generate_password( 40 ), 'role' => 'subscriber' ) );
    require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
    $source = wp_tempnam( 'ftuy-phttp-image' ); $canvas = imagecreatetruecolor( 800, 600 ); imagejpeg( $canvas, $source ); imagedestroy( $canvas );
    $image = FTUY_Media::store( 'publicaciones', function () use ( $source ) { return media_handle_sideload( array( 'name' => 'ftuy-phttp.jpg', 'tmp_name' => $source ), 0 ); } );
    if ( is_wp_error( $image ) ) { throw new RuntimeException( 'Imagen de prueba falló.' ); } wp_update_post( array( 'ID' => $image, 'post_author' => $uid ) );
    $fields = array( 'caption' => 'Publicación de prueba HTTP', 'address' => 'Ubicación de prueba', 'latitude' => '', 'longitude' => '', 'status' => 'published' );
    $id = FTUY_Publications::create( $fields, $uid, $image ); if ( is_wp_error( $id ) ) { throw new RuntimeException( 'No se pudo crear fixture.' ); }
    $detail = $call( '/wp-admin/admin.php?page=ftuy-publications&edit=' . $id, $admin_cookie );
    $assert( $detail[0] === 200 && strpos( $detail[1], 'Despublicar' ) !== false && strpos( $detail[1], 'Denuncias' ) !== false, 'Detalle real de administración.' );
    $post = array_merge( $fields, array( 'action' => 'ftuy_publication', 'publication_id' => $id, 'version' => 1, '_wpnonce' => $nonce, 'operation' => 'unpublish', 'note' => 'Prueba de moderación' ) );
    $bad = $post; $bad['_wpnonce'] = 'invalid';
    $assert( $call( '/wp-admin/admin-post.php', $admin_cookie, $bad )[0] === 403 && FTUY_Publications::get( $id )['status'] === 'published', 'CSRF rechazado sin despublicar.' );
    $subscriber_cookie = $session( $uid );
    $denied = $call( '/wp-admin/admin-post.php', $subscriber_cookie, $post );
    $assert( in_array( $denied[0], array( 302, 403 ), true ) && FTUY_Publications::get( $id )['status'] === 'published', 'Suscriptor no modera por HTTP (redirect de cuentas o 403).' );
    $assert( $call( '/wp-admin/admin-post.php', $admin_cookie, $post )[0] === 302, 'Administrador despublica.' );
    $assert( $call( '/wp-json/foodtrucks-uy/v1/publications/' . $id )[0] === 404, 'Detalle HTTP oculto.' );
    $feed = json_decode( $call( '/wp-json/foodtrucks-uy/v1/publications?author=' . $uid )[1], true );
    $assert( $feed['total'] === 0, 'Feed HTTP por autor oculto.' );
    $post['operation'] = 'publish'; $post['version'] = 2;
    $assert( $call( '/wp-admin/admin-post.php', $admin_cookie, $post )[0] === 302 && $call( '/wp-json/foodtrucks-uy/v1/publications/' . $id )[0] === 200, 'Republicar restaura visibilidad.' );
    $assert( $call( '/wp-admin/admin-post.php', $admin_cookie, $post )[0] === 400, 'Formulario viejo rechazado.' );
    $report = array( 'action' => 'ftuy_publication', 'publication_id' => $id, '_wpnonce' => $nonce, 'operation' => 'report', 'reason' => 'Aviso recibido por email' );
    $assert( $call( '/wp-admin/admin-post.php', $admin_cookie, $report )[0] === 302, 'Denuncia registrada por formulario.' );
    $reported = $call( '/wp-admin/admin.php?page=ftuy-publications&status=reported', $admin_cookie );
    $assert( $reported[0] === 200 && strpos( $reported[1], 'edit=' . $id ) !== false, 'Listado denunciadas HTTP.' );
    $report_id = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . FTUY_Publications::table( 'publication_reports' ) . ' WHERE publication_id=%d', $id ) );
    $report['operation'] = 'resolve'; $report['report_id'] = $report_id;
    $assert( $call( '/wp-admin/admin-post.php', $admin_cookie, $report )[0] === 302, 'Revisión de denuncia por formulario.' );
    $image_response = $call( str_replace( home_url(), '', wp_get_attachment_url( $image ) ) );
    $assert( $image_response[0] === 200, 'Archivo físico conservado.' );
    echo "OK: $count comprobaciones HTTP de publicaciones.\n";
} finally {
    foreach ( $tokens as $token ) { WP_Session_Tokens::get_instance( $token[0] )->destroy( $token[1] ); }
    if ( $id && ! is_wp_error( $id ) ) { foreach ( array( 'publication_reports', 'publication_audit' ) as $table ) { $wpdb->delete( FTUY_Publications::table( $table ), array( 'publication_id' => $id ) ); } $wpdb->delete( FTUY_Publications::table(), array( 'id' => $id ) ); }
    if ( $image && ! is_wp_error( $image ) ) { wp_delete_attachment( $image, true ); }
    if ( $source && is_file( $source ) ) { wp_delete_file( $source ); }
    if ( $uid && ! is_wp_error( $uid ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $uid ); }
    if ( $old_cookie === null ) { unset( $_COOKIE[LOGGED_IN_COOKIE] ); } else { $_COOKIE[LOGGED_IN_COOKIE] = $old_cookie; }
    wp_set_current_user( $old_user );
}
