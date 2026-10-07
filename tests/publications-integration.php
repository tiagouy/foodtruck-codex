<?php
if ( ! defined( 'ABSPATH' ) || ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo local.' ); }
global $wpdb; $count = 0; $ids = array(); $user_id = 0; $image_id = 0; $source = null; $old_user = get_current_user_id();
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
$input = array( 'caption' => 'Foto de prueba', 'address' => 'Lugar de prueba', 'latitude' => '', 'longitude' => '', 'status' => 'pending' );
try {
    $admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID; wp_set_current_user( $admin );
    $assert( current_user_can( 'manage_ft_publications' ), 'Administrador con capacidad propia.' );
    $user_id = wp_insert_user( array( 'user_login' => 'ftuy_pub_' . wp_generate_uuid4(), 'user_email' => 'ftuy-pub-' . wp_generate_uuid4() . '@example.invalid', 'role' => 'subscriber', 'user_pass' => wp_generate_password( 40 ) ) );
    require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
    $source = wp_tempnam( 'ftuy-publication-test' ); $canvas = imagecreatetruecolor( 900, 600 ); imagejpeg( $canvas, $source ); imagedestroy( $canvas );
    $image_id = FTUY_Media::store( 'publicaciones', function () use ( $source ) { return media_handle_sideload( array( 'name' => 'ftuy-publication-test.jpg', 'tmp_name' => $source ), 0 ); } );
    $assert( ! is_wp_error( $image_id ), 'Imagen de fixture en media/publicaciones.' ); wp_update_post( array( 'ID' => $image_id, 'post_author' => $user_id ) );
    $id = FTUY_Publications::create( $input, $user_id, $image_id ); if ( ! is_wp_error( $id ) ) { $ids[] = $id; }
    $assert( is_int( $id ) && $id > 0, 'Registro pendiente creado.' );
    $api = function ( $path ) { return rest_do_request( new WP_REST_Request( 'GET', '/foodtrucks-uy/v1/' . $path ) ); };
    $assert( $api( 'publications/' . $id )->get_status() === 404, 'Pendiente no accesible por detalle público.' );
    $assert( ! in_array( $id, array_column( $api( 'publications' )->get_data()['items'], 'id' ), true ), 'Pendiente no aparece en feed.' );
    $input['status'] = 'published';
    $assert( FTUY_Publications::edit( $id, $input, 1, 'Aprobada' ) === true, 'Publicar funciona.' );
    $assert( $api( 'publications/' . $id )->get_status() === 200, 'Publicada accesible.' );
    $request = new WP_REST_Request( 'GET', '/foodtrucks-uy/v1/publications' ); $request->set_param( 'author', $user_id );
    $data = rest_do_request( $request )->get_data();
    $assert( $data['total'] === 1 && $data['items'][0]['id'] === $id, 'Feed por autor solo publicado.' );
    $serialized = wp_json_encode( $data );
    $assert( strpos( $serialized, '@' ) === false && strpos( $serialized, 'legacy_' ) === false && strpos( $serialized, 'Aprobada' ) === false, 'API no expone emails, IDs antiguos ni notas.' );
    $input['status'] = 'unpublished';
    $assert( FTUY_Publications::edit( $id, $input, 2, 'Contenido no corresponde' ) === true, 'Despublicación guarda motivo.' );
    $assert( $api( 'publications/' . $id )->get_status() === 404 && rest_do_request( $request )->get_data()['total'] === 0, 'Despublicada desaparece de detalle y autor.' );
    $assert( is_file( get_attached_file( $image_id ) ) && (int) FTUY_Publications::get( $id )['author_user_id'] === $user_id, 'Conserva imagen y dueño.' );
    $assert( is_wp_error( FTUY_Publications::edit( $id, $input, 2 ) ), 'Versión vieja no sobrescribe moderación.' );
    $assert( FTUY_Publications::report( $id, 'Denuncia recibida por email' ) === true, 'Registra denuncia.' );
    $assert( FTUY_Publications::listing( 'reported' )['total'] >= 1, 'Filtro denunciadas encuentra registro.' );
    $report = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . FTUY_Publications::table( 'publication_reports' ) . ' WHERE publication_id=%d', $id ) );
    $assert( is_wp_error( FTUY_Publications::resolve( $id + 100000, $report ) ), 'No resuelve denuncia de otra publicación.' );
    $assert( FTUY_Publications::resolve( $id, $report ) === true && FTUY_Publications::get( $id )['status'] === 'unpublished', 'Resolver denuncia no republica.' );
    $assert( is_wp_error( FTUY_Publications::resolve( $id, $report ) ), 'No vuelve a resolver denuncia cerrada.' );
    $input['status'] = 'published'; $input['author_user_id'] = $admin; $input['image_id'] = 0; $input['caption'] = 'Texto editado <script>alert(1)</script>';
    $assert( FTUY_Publications::edit( $id, $input, 3 ) === true, 'Republicación y edición.' );
    $row = FTUY_Publications::get( $id );
    $assert( (int) $row['author_user_id'] === $user_id && (int) $row['image_id'] === $image_id && strpos( $row['caption'], '<script>' ) === false, 'Autor y foto inmutables; sanea texto.' );
    $bad = $input; $bad['status'] = 'deleted'; $assert( is_wp_error( FTUY_Publications::validate( $bad ) ), 'Estado desconocido rechazado.' );
    $bad = $input; $bad['latitude'] = 100; $bad['longitude'] = 0; $assert( is_wp_error( FTUY_Publications::validate( $bad ) ), 'Coordenadas inválidas rechazadas.' );
    $bad = $input; $bad['caption'] = array(); $assert( is_wp_error( FTUY_Publications::validate( $bad ) ), 'Campos compuestos rechazados.' );
    $bad = $input; $bad['latitude'] = 0; $bad['longitude'] = ''; $assert( is_wp_error( FTUY_Publications::validate( $bad ) ), 'No acepta coordenadas incompletas.' );
    $assert( (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . FTUY_Publications::table( 'publication_audit' ) . ' WHERE publication_id=%d', $id ) ) === 6, 'Historial completo de creación, edición y denuncias.' );
    $fail_audit = function ( $query ) { $table = FTUY_Publications::table( 'publication_audit' ); return strpos( $query, 'INSERT INTO `' . $table . '`' ) === 0 ? str_replace( '`' . $table . '`', '`' . $table . '_fixture_missing`', $query ) : $query; };
    $suppressed = $wpdb->suppress_errors(); add_filter( 'query', $fail_audit );
    try { $failed_save = FTUY_Publications::edit( $id, $input, 4, 'Fallo sintético' ); } finally { remove_filter( 'query', $fail_audit ); $wpdb->suppress_errors( $suppressed ); }
    $assert( is_wp_error( $failed_save ) && FTUY_Publications::get( $id ) === $row, 'Si falla historial se revierte edición (InnoDB).' );
    $_GET['edit'] = $id; ob_start(); FTUY_Publication_Admin::page(); $html = ob_get_clean(); unset( $_GET['edit'] );
    $assert( strpos( $html, 'Despublicar' ) !== false && strpos( $html, 'Historial de moderación' ) !== false && strpos( $html, 'name="_wpnonce"' ) !== false, 'Pantalla de detalle y nonce.' );
    wp_set_current_user( $user_id );
    $assert( is_wp_error( FTUY_Publications::edit( $id, $input, 4 ) ) && is_wp_error( FTUY_Publications::report( $id, 'No autorizado' ) ) && is_wp_error( FTUY_Publications::resolve( $id, $report ) ), 'Suscriptor no modera ni gestiona denuncias.' );
    wp_set_current_user( 0 ); $assert( is_wp_error( FTUY_Publications::create( $input, $user_id, $image_id ) ), 'Anónimo no crea registros.' );
    $post = new WP_REST_Request( 'POST', '/foodtrucks-uy/v1/publications' ); $assert( rest_do_request( $post )->get_status() === 404, 'Sin endpoint de subida pública.' );
    $oversize = new WP_REST_Request( 'GET', '/foodtrucks-uy/v1/publications' ); $oversize->set_param( 'per_page', 500 ); $assert( rest_do_request( $oversize )->get_status() === 400, 'Paginación acotada.' );
    echo "OK: $count comprobaciones de publicaciones.\n";
} finally {
    foreach ( $ids as $id ) { foreach ( array( 'publication_reports', 'publication_audit' ) as $table ) { $wpdb->delete( FTUY_Publications::table( $table ), array( 'publication_id' => $id ) ); } $wpdb->delete( FTUY_Publications::table(), array( 'id' => $id ) ); }
    if ( $image_id && ! is_wp_error( $image_id ) ) { wp_delete_attachment( $image_id, true ); }
    if ( $source && is_file( $source ) ) { wp_delete_file( $source ); }
    if ( $user_id && ! is_wp_error( $user_id ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $user_id ); }
    unset( $_GET['edit'] ); wp_set_current_user( $old_user );
}
