<?php
/** HTTP tests, via WP-CLI eval-file, exclusively against localhost. */
if ( ! defined( 'ABSPATH' ) || wp_parse_url( home_url(), PHP_URL_HOST ) !== 'localhost' ) { throw new RuntimeException( 'Solo en localhost.' ); }
global $wpdb;
$count = 0;
$assert = function ( $yes, $message ) use ( &$count ) { if ( ! $yes ) { throw new RuntimeException( $message ); } $count++; };
$uid = 0; $tokens = array(); $truck_media = array(); $title = 'Prueba HTTP ' . wp_generate_uuid4();
$call = function ( $path, $cookie = '', $data = null ) {
    $ch = curl_init( home_url( $path ) );
    curl_setopt_array( $ch, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_FOLLOWLOCATION => false, CURLOPT_COOKIE => $cookie ) );
    if ( $data !== null ) { curl_setopt( $ch, CURLOPT_POST, true ); curl_setopt( $ch, CURLOPT_POSTFIELDS, $data ); }
    $body = curl_exec( $ch ); $code = curl_getinfo( $ch, CURLINFO_HTTP_CODE ); $error = curl_error( $ch ); curl_close( $ch );
    if ( $error ) { throw new RuntimeException( $error ); }
    return array( $code, $body );
};
$cookies = function ( $id ) use ( &$tokens ) {
    $expires = time() + 600; $token = WP_Session_Tokens::get_instance( $id )->create( $expires ); $tokens[] = array( $id, $token );
    return LOGGED_IN_COOKIE . '=' . rawurlencode( wp_generate_auth_cookie( $id, $expires, 'logged_in', $token ) ) . '; ' . AUTH_COOKIE . '=' . rawurlencode( wp_generate_auth_cookie( $id, $expires, 'auth', $token ) );
};
$nonce = function ( $html ) {
    if ( ! preg_match( '/name="_wpnonce" value="([^"]+)"/', $html, $match ) ) { throw new RuntimeException( 'Formulario sin nonce.' ); }
    return html_entity_decode( $match[1] );
};
try {
    $uid = wp_insert_user( array( 'user_login' => 'ftuy-http-' . wp_generate_uuid4(), 'user_email' => 'ftuy-http-' . wp_generate_uuid4() . '@example.invalid', 'user_pass' => wp_generate_password( 40 ), 'role' => 'subscriber' ) );
    if ( is_wp_error( $uid ) ) { throw new RuntimeException( 'No se pudo crear la cuenta de ensayo.' ); }
    $cookie = $cookies( $uid );
    $admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
    $admin_cookie = $cookies( $admin->ID );
    $seed = FTUY_Events::listing( 'past' )['items'][0];
    list( $code, $body ) = $call( '/sugerir-evento/' );
    $assert( $code === 200 && strpos( $body, 'Iniciar sesión' ) !== false, 'Carga exige login.' );
    list( $code, $body ) = $call( '/sugerir-evento/', $cookie );
    $assert( $code === 200 && strpos( $body, 'Enviar a revisión' ) !== false, 'Formulario autenticado.' );
    $form_nonce = $nonce( $body );
    $assert( strpos( $body, 'name="summary"' ) === false && strpos( $body, 'name="entry_type"' ) !== false && strpos( $body, 'event-form.js' ) !== false, 'Formulario simplificado con entradas y horarios.' );
    $today = FTUY_Events::now()->format( 'Y-m-d' );
    $fields = array( '_wpnonce' => $form_nonce, 'title' => $title, 'description' => 'Evento de prueba HTTP.', 'start_date' => $today, 'end_date' => $today, 'department' => 'Montevideo', 'locality' => 'Montevideo', 'address' => 'Dirección de prueba', 'event_image' => new CURLFile( get_attached_file( $seed['image_id'] ), 'image/jpeg', 'afiche.jpg' ) );
    $fields['entry_type'] = 'paid'; $fields['tickets_url'] = 'https://example.com/entradas';
    $fields['venue'] = 'Lugar HTTP de prueba';
    $fields['schedule_json'] = wp_json_encode( array( array( 'date' => $today, 'start' => '10:00', 'end' => '20:00' ) ) );
    $bad = $fields; $bad['_wpnonce'] = 'invalid';
    list( $code ) = $call( '/sugerir-evento/', $cookie, $bad );
    $assert( $code === 403, 'Rechaza CSRF.' );
    list( $code, $body ) = $call( '/sugerir-evento/', $cookie, $fields );
    $assert( $code === 302, 'Formulario real con foto enviado.' );
    $event = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Events::table() . ' WHERE author_id=%d', $uid ), ARRAY_A );
    $assert( $event && $event['status'] === 'pending' && (int) $event['image_id'] !== (int) $seed['image_id'], 'Imagen subida y propuesta pendiente.' );
    $r = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Events::table( 'event_reviews' ) . ' WHERE event_id=%d ORDER BY id DESC LIMIT 1', $event['id'] ), ARRAY_A );
    list( $code ) = $call( '/wp-json/foodtrucks-uy/v1/events/' . $event['slug'] );
    $assert( $code === 404, 'Propuesta no visible en API HTTP.' );
    list( $code, $body ) = $call( '/mis-eventos/', $cookie );
    $assert( $code === 200 && strpos( $body, $title ) !== false && strpos( $body, 'Pendiente' ) !== false, 'Autor ve estado.' );
    list( $code ) = $call( '/sugerir-evento/?edit=' . $seed['id'], $cookie );
    $assert( $code === 403, 'HTTP impide edición ajena.' );
    list( $code, $body ) = $call( '/wp-admin/admin.php?page=ftuy-events&review=' . $r['id'], $admin_cookie );
    $assert( $code === 200 && strpos( $body, 'Abrir vista previa privada' ) !== false, 'Panel del revisor.' );
    $admin_nonce = $nonce( $body );
    preg_match( '/href="([^"]+ft_preview[^\"]+)"/', $body, $match );
    $preview_url = html_entity_decode( $match[1] ?? '' );
    $preview_path = str_replace( home_url(), '', $preview_url );
    list( $code ) = $call( $preview_path );
    $assert( $code === 403, 'Preview privado rechaza visitante.' );
    list( $code ) = $call( $preview_path, $cookie );
    $assert( $code === 403, 'Preview privado rechaza comunidad.' );
    list( $code, $preview ) = $call( $preview_path, $admin_cookie );
    $assert( $code === 200 && strpos( $preview, 'Vista previa privada' ) !== false && strpos( $preview, $title ) !== false, 'Revisor ve ficha preview.' );
    unset( $fields['event_image'] ); $fields['_wpnonce'] = $admin_nonce; $fields['review_id'] = $r['id']; $fields['action'] = 'ftuy_review'; $fields['decision'] = 'published';
    list( $code ) = $call( '/wp-admin/admin-post.php', $admin_cookie, $fields );
    $assert( $code === 302, 'Aprobación por formulario administrativo.' );
    list( $code, $json ) = $call( '/wp-json/foodtrucks-uy/v1/events/' . $event['slug'] );
    $decoded = json_decode( $json, true );
    $assert( $code === 200 && $decoded && $decoded['title'] === $title, 'API HTTP JSON válido tras aprobar.' );
    $assert( $decoded['entry_type'] === 'paid' && $decoded['tickets_url'] === $fields['tickets_url'] && $decoded['schedule'][0]['end'] === '20:00', 'Horarios por día y entradas conservados hasta la API.' );
    list( $code, $body ) = $call( '/evento/' . $event['slug'] . '/' );
    $assert( $code === 200 && strpos( $body, $title ) !== false, 'Detalle público nuevo.' );
    $assert( strpos( $body, '10:00 — 20:00' ) !== false && strpos( $body, 'Con entrada' ) !== false, 'Detalle muestra horarios y tipo de entrada.' );
    $assert( preg_match( '/<section class="ft-location">.*?<strong>Lugar HTTP de prueba<\/strong>.*?Dirección de prueba/s', $body ), 'Dónde será muestra lugar antes de dirección.' );
    list( $code, $body ) = $call( '/eventos/pasados/?departamento=Canelones' );
    $assert( $code === 200 && strpos( $body, '0 eventos' ) !== false, 'Filtro HTTP sin resultados.' );
    list( $code ) = $call( '/wp-admin/admin.php?page=ftuy-foodtrucks&new=1', $cookie );
    $assert( $code === 403, 'Suscriptor no puede administrar foodtrucks.' );
    list( $code, $body ) = $call( '/wp-admin/admin.php?page=ftuy-foodtrucks&new=1', $admin_cookie );
    $assert( $code === 200 && strpos( $body, 'Proponer nombre y logo desde Instagram' ) !== false && strpos( $body, 'name="email"' ) === false, 'Formulario foodtruck con Instagram sin email.' );
    $truck_nonce = $nonce( $body );
    preg_match( '/href="([^"]+foodtrucks\/\?ft_truck_preview[^\"]+)"/', $body, $truck_preview_match );
    $truck_preview_url = html_entity_decode( $truck_preview_match[1] ?? '' );
    $truck_preview_path = str_replace( home_url(), '', $truck_preview_url );
    $assert( $truck_preview_path && strpos( $truck_preview_path, '_wpnonce=' ) !== false, 'Panel enlaza catálogo privado con nonce.' );
    preg_match( '/var ftuyTruck = (\{[^\n]+\});/', $body, $truck_js ); $ig_config = json_decode( $truck_js[1] ?? '', true );
    $assert( ! empty( $ig_config['nonce'] ), 'Consulta Instagram con nonce.' );
    list( $code, $json ) = $call( '/wp-admin/admin-ajax.php', $cookie, array( 'action' => 'ftuy_instagram_preview', 'nonce' => $ig_config['nonce'], 'instagram' => '@quechurrouy' ) );
    $assert( $code === 403, 'Consulta Instagram requiere permisos.' );
    list( $code, $json ) = $call( '/wp-admin/admin-ajax.php', $admin_cookie, array( 'action' => 'ftuy_instagram_preview', 'nonce' => $ig_config['nonce'], 'instagram' => 'https://127.0.0.1/private' ) );
    $assert( $code === 422 && ! json_decode( $json, true )['success'], 'Instagram rechaza URL privada sin consultarla.' );
    $truck_fields = array( 'action' => 'ftuy_foodtruck', '_wpnonce' => $truck_nonce, 'name' => $title, 'description' => 'Prueba foodtruck HTTP', 'food_offering' => 'Churros', 'department' => 'Montevideo', 'locality' => 'Montevideo', 'cuisine_ids[0]' => FTUY_Foodtrucks::cuisines()[0]['id'], 'serves_events' => '1', 'responsible_user_id' => $uid, 'decision' => 'pending', 'truck_logo' => new CURLFile( get_attached_file( $seed['image_id'] ), 'image/jpeg', 'logo-prueba.jpg' ) );
    $truck_fields['whatsapp'] = '099 123 456'; $truck_fields['instagram'] = '@quechurrouy';
    list( $code ) = $call( '/wp-admin/admin-post.php', $admin_cookie, $truck_fields );
    $assert( $code === 302, 'Foodtruck enviado con logo real.' );
    $truck = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Foodtrucks::table() . ' WHERE responsible_user_id=%d', $uid ), ARRAY_A );
    $truck_review = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Foodtrucks::table( 'foodtruck_reviews' ) . ' WHERE foodtruck_id=%d ORDER BY id DESC LIMIT 1', $truck['id'] ), ARRAY_A );
    $payload = json_decode( $truck_review['payload'], true ); $truck_media = array_column( $payload['images'], 'attachment_id' );
    $assert( $truck['status'] === 'pending' && $payload['images'][0]['role'] === 'logo', 'Propuesta conserva rol de logo.' );
    list( $code ) = $call( '/wp-json/foodtrucks-uy/v1/foodtrucks/' . $truck['slug'] ); $assert( $code === 404, 'API no expone foodtruck pendiente.' );
    list( $code, $body ) = $call( '/foodtruck/' . $truck['slug'] . '/' ); $assert( $code === 404 && strpos( $body, $title ) === false, 'Detalle público no filtra pendientes ni redirige a fichas viejas.' );
    list( $code, $body ) = $call( '/foodtrucks/' ); $assert( $code === 200 && strpos( $body, $title ) === false && strpos( $body, 'Rubro gastronómico' ) !== false, 'Directorio público con filtros oculta pendientes.' );
    list( $code ) = $call( $truck_preview_path ); $assert( $code === 403, 'Catálogo privado rechaza anónimos.' );
    list( $code ) = $call( $truck_preview_path, $cookie ); $assert( $code === 403, 'Catálogo privado rechaza suscriptores.' );
    list( $code, $body ) = $call( $truck_preview_path, $admin_cookie ); $assert( $code === 200 && strpos( $body, $title ) !== false && strpos( $body, 'noindex,nofollow' ) !== false, 'Admin ve propuestas en catálogo privado no indexable.' );
    $preview_detail = '/foodtruck/' . $truck['slug'] . '/?' . wp_parse_url( $truck_preview_url, PHP_URL_QUERY );
    list( $code, $body ) = $call( $preview_detail, $admin_cookie ); $assert( $code === 200 && strpos( $body, $title ) !== false && strpos( $body, 'Sobre este foodtruck' ) !== false, 'Preview usa diseño del detalle público.' );
    list( $code, $body ) = $call( '/wp-admin/admin.php?page=ftuy-foodtrucks&edit=' . $truck['id'] . '&preview=1', $admin_cookie );
    $assert( $code === 200 && strpos( $body, 'Vista previa privada' ) !== false && strpos( $body, $title ) !== false, 'Preview foodtruck privado.' );
    unset( $truck_fields['truck_logo'] ); $truck_fields['foodtruck_id'] = $truck['id']; $truck_fields['decision'] = 'published'; $truck_fields['keep_images[0]'] = 'logo:' . $truck_media[0];
    list( $code ) = $call( '/wp-admin/admin-post.php', $admin_cookie, $truck_fields ); $assert( $code === 302, 'Aprobación foodtruck por formulario.' );
    list( $code, $json ) = $call( '/wp-json/foodtrucks-uy/v1/foodtrucks/' . $truck['slug'] ); $public = json_decode( $json, true );
    $assert( $code === 200 && $public['images'][0]['role'] === 'logo' && ! isset( $public['responsible_user_id'], $public['email'] ), 'API foodtruck aprobada sin datos privados.' );
    list( $code, $body ) = $call( '/foodtrucks/?departamento=Montevideo&rubro=' . $truck_fields['cuisine_ids[0]'] ); $assert( $code === 200 && strpos( $body, $title ) !== false, 'Directorio filtra por departamento y rubro.' );
    list( $code, $body ) = $call( '/foodtrucks/?departamento=Artigas' ); $assert( $code === 200 && strpos( $body, $title ) === false, 'Filtro excluye otra base.' );
    list( $code, $body ) = $call( '/foodtruck/' . $truck['slug'] . '/' ); $assert( $code === 200 && strpos( $body, 'wa.me/59899123456' ) !== false && strpos( $body, 'https://www.instagram.com/quechurrouy/' ) !== false && strpos( $body, 'responsible_user_id' ) === false, 'Detalle publicado muestra contactos sin gestión privada.' );
    list( $code ) = $call( '/foodtruck/no-existe-' . wp_generate_uuid4() . '/' ); $assert( $code === 404, 'Ficha inexistente devuelve 404 real.' );
    list( $code, $body ) = $call( '/agregar-foodtruck/' );
    $assert( $code === 200 && strpos( $body, 'Iniciar sesión' ) !== false && strpos( $body, 'name="name"' ) === false, 'Alta pública exige login sin exponer formulario.' );
    list( $code, $body ) = $call( '/mis-foodtrucks/' ); $assert( $code === 200 && strpos( $body, $title ) === false, 'Mis foodtrucks anónimo no expone fichas propias.' );
    list( $code, $body ) = $call( '/agregar-foodtruck/', $cookie );
    $assert( $code === 200 && strpos( $body, 'Enviar a revisión' ) !== false && strpos( $body, 'name="responsible_user_id"' ) === false && strpos( $body, 'Aprobar y publicar' ) === false && strpos( $body, 'name="email"' ) === false, 'Alta de suscriptor sin campos administrativos/email.' );
    $assert( strpos( $body, '900 × 900' ) === false && strpos( $body, '500 × 500' ) === false && strpos( $body, '72 dpi' ) === false && strpos( $body, '120 KB' ) === false && strpos( $body, 'name="truck_truck_photo"' ) !== false && strpos( $body, 'name="truck_cover"' ) === false && strpos( $body, 'name="truck_photos[]"' ) === false, 'Formulario simple sin tamaños internos, portada ni galería.' );
    $public_nonce = $nonce( $body ); preg_match( '/var ftuyTruck = (\{[^\n]+\});/', $body, $public_js ); $public_ig = json_decode( $public_js[1] ?? '', true );
    $assert( $public_ig['action'] === 'ftuy_instagram_public_preview', 'Formulario usa consulta pública autenticada de Instagram.' );
    list( $code ) = $call( '/wp-admin/admin-ajax.php', '', array( 'action' => $public_ig['action'], 'nonce' => $public_ig['nonce'], 'instagram' => '@quechurrouy' ) ); $assert( $code === 403, 'Instagram del formulario no admite anónimos.' );
    list( $code ) = $call( '/wp-admin/admin-ajax.php', $cookie, array( 'action' => $public_ig['action'], 'nonce' => $public_ig['nonce'], 'instagram' => 'https://127.0.0.1/private' ) ); $assert( $code === 422, 'Suscriptor autorizado para consulta, no para URLs privadas.' );
    $submission = array( '_wpnonce' => $public_nonce, 'name' => $title . ' propietario', 'description' => 'Ficha del propietario', 'food_offering' => 'Churros', 'department' => 'Montevideo', 'locality' => 'Montevideo', 'cuisine_ids[0]' => FTUY_Foodtrucks::cuisines()[0]['id'], 'serves_events' => '1', 'responsible_user_id' => $admin->ID, 'decision' => 'published', 'status' => 'published' );
    $bad = $submission; $bad['_wpnonce'] = 'invalid'; list( $code ) = $call( '/agregar-foodtruck/', $cookie, $bad ); $assert( $code === 403, 'Alta pública rechaza CSRF.' );
    $bad = $submission; $bad['description'] = ''; list( $code, $body ) = $call( '/agregar-foodtruck/', $cookie, $bad ); $assert( $code === 200 && strpos( $body, 'Completá nombre' ) !== false && strpos( $body, $submission['name'] ) !== false, 'Errores conservan datos escritos.' );
    $bad = $submission; $bad['keep_images[0]'] = 'logo:' . $seed['image_id']; list( $code, $body ) = $call( '/agregar-foodtruck/', $cookie, $bad ); $assert( $code === 200 && strpos( $body, 'No podés usar esa imagen' ) !== false, 'No puede apropiarse de medios de otra ficha.' );
    $submission['truck_logo'] = new CURLFile( get_attached_file( $seed['image_id'] ), 'image/jpeg', 'logo-propietario.jpg' );
    $submission['truck_truck_photo'] = new CURLFile( get_attached_file( $seed['image_id'] ), 'image/jpeg', 'foto-propietario.jpg' );
    list( $code ) = $call( '/agregar-foodtruck/', $cookie, $submission ); $assert( $code === 302, 'Suscriptor carga ficha y logo sin panel administrativo.' );
    $owned = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Foodtrucks::table() . ' WHERE responsible_user_id=%d ORDER BY id DESC LIMIT 1', $uid ), ARRAY_A );
    $assert( $owned['name'] === $submission['name'] && $owned['status'] === 'pending' && (int) $owned['responsible_user_id'] === (int) $uid, 'Ignora responsable, estado y decisión manipulados.' );
    list( $code, $body ) = $call( '/mis-foodtrucks/', $cookie ); $assert( $code === 200 && strpos( $body, $owned['name'] ) !== false && strpos( $body, 'Pendiente' ) !== false, 'Autor ve propuesta y estado.' );
    list( $code ) = $call( '/agregar-foodtruck/?edit=1', $cookie ); $assert( $code === 403, 'Autor no puede editar muestra ajena.' );
    list( $code ) = $call( '/foodtruck/' . $owned['slug'] . '/' ); $assert( $code === 404, 'Alta propia pendiente no se publica.' );
    $own_review = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Foodtrucks::table( 'foodtruck_reviews' ) . ' WHERE foodtruck_id=%d ORDER BY id DESC LIMIT 1', $owned['id'] ), ARRAY_A );
    $own_images = json_decode( $own_review['payload'], true )['images']; $own_media = $own_images[0]['attachment_id'];
    foreach ( $own_images as $image ) {
        $path = get_attached_file( $image['attachment_id'] ); $info = wp_getimagesize( $path ); list( $edge, $limit ) = FTUY_Foodtruck_Images::settings( $image['role'] );
        $assert( $info[0] === $edge && $info[1] === $edge && filesize( $path ) <= $limit && $info['mime'] === 'image/jpeg', 'Subida real optimizada: ' . $image['role'] );
    }
    $assert( count( $own_images ) === 2, 'Guarda solo logo y foto del foodtruck.' );
    list( $code, $body ) = $call( '/agregar-foodtruck/?edit=' . $owned['id'], $cookie ); $assert( $code === 200 && strpos( $body, 'logo:' . $own_media ) !== false, 'Edición recupera logo y datos pendientes.' );
    delete_transient( 'ftuy_truck_submit_' . $uid );
    unset( $submission['truck_logo'], $submission['truck_truck_photo'] ); $submission['keep_images[0]'] = 'logo:' . $own_media;
    $submission['keep_images[1]'] = 'truck_photo:' . $own_images[1]['attachment_id'];
    $approve = $submission; $approve['_wpnonce'] = $truck_nonce; $approve['action'] = 'ftuy_foodtruck'; $approve['foodtruck_id'] = $owned['id']; $approve['responsible_user_id'] = $uid; $approve['decision'] = 'published';
    list( $code ) = $call( '/wp-admin/admin-post.php', $admin_cookie, $approve ); $assert( $code === 302, 'Revisor aprueba alta enviada por propietario.' );
    $submission['name'] .= ' editado'; list( $code ) = $call( '/agregar-foodtruck/?edit=' . $owned['id'], $cookie, $submission ); $assert( $code === 302, 'Propietario propone cambios conservando logo.' );
    $assert( FTUY_Foodtrucks::get( $owned['id'] )['name'] !== $submission['name'], 'Cambios pendientes no pisan ficha aprobada.' );
    list( $code, $body ) = $call( '/mis-foodtrucks/', $cookie ); $assert( $code === 200 && strpos( $body, 'La versión aprobada sigue publicada' ) !== false, 'Estado distingue publicado y cambio pendiente.' );
    echo "OK: $count comprobaciones HTTP, incluida carga real de imagen.\n";
} finally {
    if ( $uid && ! is_wp_error( $uid ) ) {
        foreach ( $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . FTUY_Foodtrucks::table() . ' WHERE responsible_user_id=%d', $uid ) ) as $truck_id ) { foreach ( array( 'foodtruck_reviews', 'foodtruck_images', 'foodtruck_cuisines' ) as $table ) { $wpdb->delete( FTUY_Foodtrucks::table( $table ), array( 'foodtruck_id' => $truck_id ) ); } $wpdb->delete( FTUY_Foodtrucks::table(), array( 'id' => $truck_id ) ); }
        foreach ( $truck_media as $image ) { wp_delete_attachment( $image, true ); }
        $events = $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM ' . FTUY_Events::table() . ' WHERE author_id=%d', $uid ), ARRAY_A );
        foreach ( $events as $e ) { $wpdb->delete( FTUY_Events::table( 'event_reviews' ), array( 'event_id' => $e['id'] ) ); $wpdb->delete( FTUY_Events::table(), array( 'id' => $e['id'] ) ); }
        foreach ( get_posts( array( 'post_type' => 'attachment', 'author' => $uid, 'post_status' => 'inherit', 'numberposts' => -1 ) ) as $attachment ) { wp_delete_attachment( $attachment->ID, true ); }
        $user = get_userdata( $uid );
        $wpdb->delete( FTUY_Events::table( 'event_mail' ), array( 'recipient' => $user->user_email ) );
        $wpdb->delete( FTUY_Events::table( 'event_mail' ), array( 'subject' => 'Evento pendiente: ' . $title ) );
        delete_transient( 'ftuy_submit_' . $uid );
        delete_transient( 'ftuy_truck_submit_' . $uid ); delete_transient( 'ftuy_ig_rate_' . $uid );
        require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $uid );
    }
    foreach ( $tokens as $session ) { WP_Session_Tokens::get_instance( $session[0] )->destroy( $session[1] ); }
}
