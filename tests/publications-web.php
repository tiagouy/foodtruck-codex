<?php
if ( ! defined( 'ABSPATH' ) || ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo local.' ); }
global $wpdb; $old_user = get_current_user_id(); $uid = 0; $ids = array(); $image = 0; $source = null; $count = 0;
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
$call = function ( $path ) {
    $curl = curl_init( home_url( $path ) ); curl_setopt_array( $curl, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 20, CURLOPT_HEADER => true ) );
    $bytes = curl_exec( $curl ); $status = curl_getinfo( $curl, CURLINFO_HTTP_CODE ); $length = curl_getinfo( $curl, CURLINFO_HEADER_SIZE ); $error = curl_error( $curl ); curl_close( $curl ); if ( $error ) { throw new RuntimeException( $error ); }
    return array( $status, substr( $bytes, $length ), substr( $bytes, 0, $length ) );
};
try {
    wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
    $uid = wp_insert_user( array( 'user_login' => 'ftuy_pweb_' . wp_generate_uuid4(), 'user_email' => 'ftuy-pweb-' . wp_generate_uuid4() . '@example.invalid', 'display_name' => 'Persona de prueba', 'user_pass' => wp_generate_password( 40 ), 'role' => 'subscriber' ) );
    require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
    $source = wp_tempnam( 'ftuy-web-fixture' ); $canvas = imagecreatetruecolor( 800, 500 ); imagejpeg( $canvas, $source ); imagedestroy( $canvas );
    $image = FTUY_Media::store( 'publicaciones', function () use ( $source ) { return media_handle_sideload( array( 'name' => 'ftuy-web-fixture.jpg', 'tmp_name' => $source ), 0 ); } );
    if ( is_wp_error( $image ) ) { throw new RuntimeException( 'No pudo crear imagen de prueba.' ); } wp_update_post( array( 'ID' => $image, 'post_author' => $uid ) );
    $input = array( 'caption' => 'Texto exclusivo de fixture ' . wp_generate_uuid4(), 'address' => 'Dirección ficticia', 'latitude' => '', 'longitude' => '', 'status' => 'published' );
    $id = FTUY_Publications::create( $input, $uid, $image ); if ( is_wp_error( $id ) ) { throw new RuntimeException( 'No pudo crear publicación de prueba.' ); } $ids[] = $id;
    $url = '/fotousuario/p-' . $id . '/';
    $detail = $call( $url );
    $assert( $detail[0] === 200 && strpos( $detail[1], esc_html( $input['caption'] ) ) !== false, 'Detalle público muestra texto.' );
    $assert( strpos( $detail[1], 'property="og:image"' ) !== false && strpos( $detail[1], 'Enlace para compartir' ) !== false, 'Vista previa y enlace compartible publicados.' );
    $assert( stripos( $detail[2], 'no-store' ) !== false, 'HTML no cacheable.' );
    $assert( strpos( $detail[1], 'gt-lazy-load' ) === false && strpos( $detail[1], 'data:image/svg' ) === false && strpos( $detail[1], 'loading="eager"' ) !== false, 'Foto real sin placeholders JavaScript del tema.' );
    $assert( strpos( $detail[1], '@example.invalid' ) === false && strpos( $detail[1], 'type="file"' ) === false && strpos( $detail[1], '<form' ) === false, 'Sin email ni formulario de subida web.' );
    $assert( $call( '/fotousuario/p-' . $id )[0] === 301, 'Normaliza slash de detalle.' );
    $assert( $call( '/fotosusuarios' )[0] === 301 && $call( '/fotosusuarios/index.php' )[0] === 301, 'Normaliza URLs de listado.' );
    $list = $call( '/fotosusuarios/' ); $assert( $list[0] === 200 && strpos( $list[1], '/fotousuario/p-' . $id . '/' ) !== false, 'Listado incluye foto publicada.' );
    $assert( strpos( $list[1], 'gt-lazy-load' ) === false && strpos( $list[1], 'loading="lazy"' ) !== false, 'Listado con lazy loading nativo.' );
    $assert( strpos( $call( '/fotosusuarios/?autor=' . $uid )[1], esc_html( $input['caption'] ) ) !== false, 'Lista por autor.' );
    $legacy = '999999-foto-historica-de-prueba'; $wpdb->update( FTUY_Publications::table(), array( 'legacy_slug' => $legacy ), array( 'id' => $id ) );
    $historical = $call( '/fotousuario/' . $legacy . '/' );
    $assert( $historical[0] === 200 && strpos( $historical[1], '/fotousuario/' . $legacy . '/' ) !== false, 'Conserva slug histórico.' );
    $assert( $call( '/fotosusuarios/oferta.php?slug=' . $legacy )[0] === 301 && $call( $url )[0] === 301, 'Aliases publicados redirigen al histórico canónico.' );
    $before_url = FTUY_Publication_Public::url( FTUY_Publications::get( $id ) ); $input['caption'] = 'Texto cambiado';
    $assert( FTUY_Publications::edit( $id, $input, 1 ) === true && FTUY_Publication_Public::url( FTUY_Publications::get( $id ) ) === $before_url, 'Editar texto no rompe enlace.' );
    $input['status'] = 'unpublished'; FTUY_Publications::edit( $id, $input, 2, 'Nota interna secreta' );
    foreach ( array( '/fotousuario/' . $legacy . '/', $url, '/fotosusuarios/oferta.php?slug=' . $legacy ) as $path ) {
        $response = $call( $path );
        $assert( $response[0] === 404 && strpos( $response[1], 'Publicación no disponible' ) !== false, 'URL despublicada responde no disponible.' );
        $assert( strpos( $response[1], 'og:image' ) === false && strpos( $response[1], 'twitter:image' ) === false && strpos( $response[1], '/media/publicaciones/' ) === false && strpos( $response[1], 'Texto cambiado' ) === false && strpos( $response[1], 'Nota interna secreta' ) === false, 'No filtra foto, caption, preview ni motivo interno.' );
    }
    $assert( strpos( $call( '/fotosusuarios/?autor=' . $uid )[1], $legacy ) === false, 'Despublicada desaparece también del autor.' );
    $input['status'] = 'pending'; FTUY_Publications::edit( $id, $input, 3 ); $assert( $call( '/fotousuario/' . $legacy . '/' )[0] === 404, 'Pendiente tampoco accesible.' );
    $input['status'] = 'published'; FTUY_Publications::edit( $id, $input, 4 ); $assert( $call( '/fotousuario/' . $legacy . '/' )[0] === 200, 'Republicar restaura misma URL.' );
    $assert( $call( '/fotosusuarios/?autor[]=1' )[0] === 404 && $call( '/fotosusuarios/?pagina=999999' )[0] === 404, 'Filtros manipulados/página inexistente no abren listado alternativo.' );
    $assert( $call( '/fotousuario/no-existe/' )[0] === 404 && $call( '/fotosusuarios/oferta.php?slug[]=x' )[0] === 404, 'Slugs desconocidos/manipulados no disponibles.' );
    $api = new WP_REST_Request( 'GET', '/foodtrucks-uy/v1/publications/' . $id ); $assert( rest_do_request( $api )->get_data()['share_url'] === $before_url, 'API entrega URL compartible, no URL de archivo como enlace de publicación.' );
    echo "OK: $count comprobaciones web de publicaciones.\n";
} finally {
    foreach ( $ids as $id ) { foreach ( array( 'publication_reports', 'publication_audit' ) as $table ) { $wpdb->delete( FTUY_Publications::table( $table ), array( 'publication_id' => $id ) ); } $wpdb->delete( FTUY_Publications::table(), array( 'id' => $id ) ); }
    if ( $image && ! is_wp_error( $image ) ) { wp_delete_attachment( $image, true ); }
    if ( $source && is_file( $source ) ) { wp_delete_file( $source ); }
    if ( $uid && ! is_wp_error( $uid ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $uid ); }
    wp_set_current_user( $old_user );
}
