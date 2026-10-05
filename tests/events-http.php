<?php
/** HTTP tests, via WP-CLI eval-file, exclusively against localhost. */
if ( ! defined( 'ABSPATH' ) || wp_parse_url( home_url(), PHP_URL_HOST ) !== 'localhost' ) { throw new RuntimeException( 'Solo en localhost.' ); }
global $wpdb;
$count = 0;
$assert = function ( $yes, $message ) use ( &$count ) { if ( ! $yes ) { throw new RuntimeException( $message ); } $count++; };
$uid = 0; $tokens = array(); $title = 'Prueba HTTP ' . wp_generate_uuid4();
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
    list( $code, $body ) = $call( '/eventos/pasados/?departamento=Canelones' );
    $assert( $code === 200 && strpos( $body, '0 eventos' ) !== false, 'Filtro HTTP sin resultados.' );
    echo "OK: $count comprobaciones HTTP, incluida carga real de imagen.\n";
} finally {
    if ( $uid && ! is_wp_error( $uid ) ) {
        $events = $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM ' . FTUY_Events::table() . ' WHERE author_id=%d', $uid ), ARRAY_A );
        foreach ( $events as $e ) { $wpdb->delete( FTUY_Events::table( 'event_reviews' ), array( 'event_id' => $e['id'] ) ); $wpdb->delete( FTUY_Events::table(), array( 'id' => $e['id'] ) ); }
        foreach ( get_posts( array( 'post_type' => 'attachment', 'author' => $uid, 'post_status' => 'inherit', 'numberposts' => -1 ) ) as $attachment ) { wp_delete_attachment( $attachment->ID, true ); }
        $user = get_userdata( $uid );
        $wpdb->delete( FTUY_Events::table( 'event_mail' ), array( 'recipient' => $user->user_email ) );
        $wpdb->delete( FTUY_Events::table( 'event_mail' ), array( 'subject' => 'Evento pendiente: ' . $title ) );
        delete_transient( 'ftuy_submit_' . $uid );
        require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $uid );
    }
    foreach ( $tokens as $session ) { WP_Session_Tokens::get_instance( $session[0] )->destroy( $session[1] ); }
}
