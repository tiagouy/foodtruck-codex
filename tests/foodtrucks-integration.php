<?php
if ( ! defined( 'ABSPATH' ) || wp_parse_url( home_url(), PHP_URL_HOST ) !== 'localhost' ) { throw new RuntimeException( 'Solo en local.' ); }
global $wpdb; $count = 0; $uid = 0; $ids = array(); $media_ids = array(); $mock = null; $old = get_current_user_id();
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
try {
    $admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
    $uid = wp_insert_user( array( 'user_login' => 'ft-truck-' . wp_generate_uuid4(), 'user_email' => 'ft-truck-' . wp_generate_uuid4() . '@example.invalid', 'user_pass' => wp_generate_password( 40 ), 'role' => 'subscriber' ) );
    $assert( ! is_wp_error( $uid ), 'Cuenta de prueba.' );
    $data = array( 'name' => 'Foodtruck de prueba', 'description' => '<p>Descripción</p><script>bad()</script>', 'food_offering' => 'Churros', 'department' => 'Montevideo', 'locality' => 'Montevideo', 'whatsapp' => '099 123 456', 'instagram' => '@quechurrouy', 'serves_events' => 1, 'cuisine_ids' => array( FTUY_Foodtrucks::cuisines()[0]['id'] ), 'images' => array() );
    $v = FTUY_Foodtrucks::validate( $data );
    $assert( ! is_wp_error( $v ) && $v['whatsapp'] === '59899123456', 'WhatsApp canónico.' );
    $assert( $v['instagram'] === 'https://www.instagram.com/quechurrouy/' && strpos( $v['description'], '<script>' ) === false, 'Instagram y HTML normalizados.' );
    $bad = $data; $bad['name'] = array(); $assert( is_wp_error( FTUY_Foodtrucks::validate( $bad ) ), 'Rechaza nombre compuesto.' );
    $bad = $data; $bad['department'] = 'Invalid'; $assert( is_wp_error( FTUY_Foodtrucks::validate( $bad ) ), 'Departamento válido.' );
    $bad = $data; $bad['cuisine_ids'] = array( 999999 ); $assert( is_wp_error( FTUY_Foodtrucks::validate( $bad ) ), 'Rubros conocidos.' );
    $bad = $data; $bad['cuisine_ids'] = array( array( 1 ) ); $assert( is_wp_error( FTUY_Foodtrucks::validate( $bad ) ), 'Rubros compuestos rechazados.' );
    $bad = $data; $bad['serves_events'] = 0; $assert( is_wp_error( FTUY_Foodtrucks::validate( $bad ) ), 'Modalidad obligatoria.' );
    $bad = $data; $bad['whatsapp'] = 'abc'; $assert( is_wp_error( FTUY_Foodtrucks::validate( $bad ) ), 'WhatsApp inválido.' );
    $bad = $data; $bad['instagram'] = 'https://127.0.0.1/private'; $assert( is_wp_error( FTUY_Foodtrucks::validate( $bad ) ), 'URL privada no es Instagram.' );
    $bad = $data; $bad['images'] = array( array( 'role' => 'logo', 'attachment_id' => 999999 ) ); $assert( is_wp_error( FTUY_Foodtrucks::validate( $bad ) ), 'Medio inexistente rechazado.' );
    wp_set_current_user( $uid ); $r = FTUY_Foodtrucks::propose( $data, $uid ); $assert( ! is_wp_error( $r ), 'Propuesta creada.' );
    $id = FTUY_Foodtrucks::review( $r )['foodtruck_id']; $ids[] = $id; $e = FTUY_Foodtrucks::get( $id );
    $assert( $e['status'] === 'pending', 'No publica automáticamente.' );
    $assert( rest_do_request( '/foodtrucks-uy/v1/foodtrucks/' . $e['slug'] )->get_status() === 404, 'API oculta pendientes.' );
    $assert( is_wp_error( FTUY_Foodtrucks::moderate( $r, 'published' ) ), 'Suscriptor no aprueba.' );
    $assert( is_wp_error( FTUY_Foodtrucks::propose( $data, 0, $id ) ), 'Control de propietario.' );
    wp_set_current_user( $admin->ID ); $assert( FTUY_Foodtrucks::moderate( $r, 'published' ) === true, 'Admin aprueba.' );
    $assert( is_wp_error( FTUY_Foodtrucks::moderate( $r, 'published' ) ), 'No cerrar una revisión dos veces.' );
    $e = FTUY_Foodtrucks::get( $id ); $public = FTUY_Foodtrucks::public_data( $e );
    $assert( ! isset( $public['responsible_user_id'], $public['sample_key'], $public['email'] ) && $public['cuisines'], 'Respuesta pública sin gestión privada/email.' );
    $assert( rest_do_request( '/foodtrucks-uy/v1/foodtrucks/' . $e['slug'] )->get_status() === 200, 'API publica aprobado.' );
    $data['name'] = 'Cambio pendiente'; $r2 = FTUY_Foodtrucks::propose( $data, $uid, $id );
    $assert( FTUY_Foodtrucks::get( $id )['name'] === 'Foodtruck de prueba', 'Conserva ficha publicada mientras revisa.' );
    $assert( FTUY_Foodtrucks::moderate( $r2, 'corrections', 'Corregir' ) === true, 'Correcciones.' );
    $r3 = FTUY_Foodtrucks::propose( $data, $uid, $id ); $assert( FTUY_Foodtrucks::review( $r2 )['status'] === 'superseded', 'Archivado de propuesta anterior.' );
    $assert( FTUY_Foodtrucks::moderate( $r3, 'rejected', 'No aprobado' ) === true && FTUY_Foodtrucks::get( $id )['name'] === 'Foodtruck de prueba', 'Rechazo conserva ficha.' );
    $request = new WP_REST_Request( 'GET', '/foodtrucks-uy/v1/foodtrucks' ); $request->set_param( 'per_page', 51 );
    $assert( rest_do_request( $request )->get_status() === 400, 'Paginación acotada.' );
    $meta = '<meta property="og:title" content="#QuéChurro Food Truck (@quechurrouy) • Instagram"><meta property="og:image" content="https://scontent.cdninstagram.com/logo.jpg">';
    $suggestion = FTUY_Instagram::extract( $meta, 'https://www.instagram.com/quechurrouy/' );
    $assert( $suggestion['name'] === '#QuéChurro Food Truck' && $suggestion['image'], 'Extrae propuesta pública.' );
    $assert( is_wp_error( FTUY_Instagram::extract( '<meta property="og:title" content="Login • Instagram">', $suggestion['instagram'] ) ), 'Login no se confunde con perfil.' );
    $assert( ! FTUY_Instagram::allowed_image( 'https://cdninstagram.com.evil.com/image.jpg' ) && ! FTUY_Instagram::allowed_image( 'http://cdninstagram.com/image.jpg' ), 'No acepta dominios engañosos ni HTTP.' );
    $assert( is_wp_error( FTUY_Instagram::import_logo( 'invalid', $suggestion['instagram'] ) ), 'Importación requiere propuesta del servidor.' );
    $seed = FTUY_Events::listing( 'past' )['items'][0];
    $mock = function ( $pre, $args, $url ) use ( $meta, $seed ) {
        if ( $url === 'https://www.instagram.com/quechurrouy/' ) { return array( 'headers' => array(), 'body' => $meta, 'response' => array( 'code' => 200, 'message' => 'OK' ) ); }
        if ( $url === 'https://scontent.cdninstagram.com/logo.jpg' ) { return array( 'headers' => array(), 'body' => file_get_contents( get_attached_file( $seed['image_id'] ) ), 'response' => array( 'code' => 200, 'message' => 'OK' ) ); }
        return $pre;
    };
    add_filter( 'pre_http_request', $mock, 10, 3 );
    $lookup = FTUY_Instagram::lookup( '@quechurrouy' );
    $assert( ! is_wp_error( $lookup ) && strlen( $lookup['token'] ) === 32, 'Genera propuesta temporal del servidor.' );
    wp_set_current_user( $uid );
    $assert( is_wp_error( FTUY_Instagram::import_logo( $lookup['token'], $lookup['instagram'] ) ), 'Token ligado a la cuenta que consultó.' );
    wp_set_current_user( $admin->ID );
    $assert( is_wp_error( FTUY_Instagram::import_logo( $lookup['token'], '@otroperfil' ) ), 'Token ligado al perfil consultado.' );
    $logo = FTUY_Instagram::import_logo( $lookup['token'], $lookup['instagram'] );
    if ( ! is_wp_error( $logo ) ) { $media_ids[] = $logo; }
    $assert( ! is_wp_error( $logo ) && wp_attachment_is_image( $logo ), 'Importa imagen confirmada a medios locales.' );
    $assert( is_wp_error( FTUY_Instagram::import_logo( $lookup['token'], $lookup['instagram'] ) ), 'Token consumido tras importar.' );
    remove_filter( 'pre_http_request', $mock, 10 ); $mock = null;
    $assert( count( array_filter( FTUY_Foodtrucks::sample(), function ( $s ) { return ! empty( $s['skipped'] ); } ) ) === 4, 'Muestra idempotente, sin importar resto.' );
    echo "OK: $count comprobaciones de foodtrucks.\n";
} finally {
    if ( $mock ) { remove_filter( 'pre_http_request', $mock, 10 ); }
    foreach ( $media_ids as $image ) { wp_delete_attachment( $image, true ); }
    foreach ( $ids as $id ) { foreach ( array( 'foodtruck_reviews', 'foodtruck_images', 'foodtruck_cuisines' ) as $table ) { $wpdb->delete( FTUY_Foodtrucks::table( $table ), array( 'foodtruck_id' => $id ) ); } $wpdb->delete( FTUY_Foodtrucks::table(), array( 'id' => $id ) ); }
    if ( $uid && ! is_wp_error( $uid ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $uid ); } wp_set_current_user( $old );
}
