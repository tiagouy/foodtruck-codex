<?php
/** Run via WP-CLI eval-file against the LOCAL WordPress with the plugin active. */
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'FTUY_Events' ) ) { throw new RuntimeException( 'Requiere WordPress y el plugin activo.' ); }
if ( ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) { throw new RuntimeException( 'Solo en local.' ); }
global $wpdb;
$assertions = 0;
$assert = function ( $condition, $message ) use ( &$assertions ) {
    if ( ! $condition ) { throw new RuntimeException( $message ); }
    $assertions++;
};
$user_id = 0; $event_id = 0;
$mail_before = (int) $wpdb->get_var( 'SELECT COALESCE(MAX(id),0) FROM ' . FTUY_Events::table( 'event_mail' ) );
$original_user = get_current_user_id();
try {
    $admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
    $user_id = wp_insert_user( array( 'user_login' => 'ftuy-test-' . wp_generate_uuid4(), 'user_email' => 'ftuy-test-' . wp_generate_uuid4() . '@example.invalid', 'user_pass' => wp_generate_password( 40 ), 'role' => 'subscriber' ) );
    $assert( ! is_wp_error( $user_id ), 'Cuenta de prueba.' );
    $seed = FTUY_Events::listing( 'past' )['items'][0];
    $today = FTUY_Events::now()->format( 'Y-m-d' );
    $data = array( 'title' => 'Evento de integración temporal', 'description' => '<p>Comunidad de prueba</p><script>alert(1)</script>', 'department' => 'Montevideo', 'locality' => 'Montevideo', 'address' => 'Lugar de prueba', 'start_date' => $today, 'end_date' => $today, 'image_id' => $seed['image_id'] );
    $bad = $data; $bad['end_date'] = '2020-01-01';
    $assert( is_wp_error( FTUY_Events::validate( $bad ) ), 'Rechaza rango inválido.' );
    $bad = $data; $bad['start_date'] = '2026-02-30';
    $assert( is_wp_error( FTUY_Events::validate( $bad ) ), 'Rechaza fecha inexistente.' );
    $bad = $data; $bad['department'] = 'Otro';
    $assert( is_wp_error( FTUY_Events::validate( $bad ) ), 'Rechaza departamento inválido.' );
    $bad = $data; $bad['description'] = array( 'invalid' );
    $assert( is_wp_error( FTUY_Events::validate( $bad ) ), 'Rechaza input compuesto.' );
    $bad = $data; $bad['image_id'] = 0;
    $assert( is_wp_error( FTUY_Events::validate( $bad ) ), 'Imagen obligatoria.' );
    $assert( FTUY_Events::instagram_url( '@expocafeuruguay' ) === 'https://www.instagram.com/expocafeuruguay/', 'Normaliza usuario Instagram.' );
    $assert( is_wp_error( FTUY_Events::instagram_url( 'https://instagram.com.ejemplo.com/perfil/' ) ), 'Rechaza dominio engañoso.' );
    $assert( FTUY_Events::instagram_url( 'https://www.instagram.com/garage_gourmet/?utm_source=test' ) === 'https://www.instagram.com/garage_gourmet/', 'Normaliza URL sin tracking.' );
    $assert( FTUY_Events::legacy_links( $seed['legacy_post_id'] )['instagram'] === $seed['instagram'] && ! empty( $seed['website'] ), 'Recupera enlaces originales.' );
    $data['instagram'] = '@expocafeuruguay';
    $bad = $data; $bad['entry_type'] = 'paid';
    $assert( is_wp_error( FTUY_Events::validate( $bad ) ), 'Entrada paga requiere enlace.' );
    $bad['tickets_url'] = 'javascript:alert(1)';
    $assert( is_wp_error( FTUY_Events::validate( $bad ) ), 'No acepta enlace de entradas peligroso.' );
    $bad['tickets_url'] = 'https://example.com/entradas';
    $assert( FTUY_Events::validate( $bad )['entry_type'] === 'paid', 'Acepta entrada paga con enlace.' );
    $bad['entry_type'] = 'free';
    $assert( FTUY_Events::validate( $bad )['tickets_url'] === '' && FTUY_Events::validate( $bad )['price'] === 'Gratis', 'Gratis elimina enlace de entradas.' );
    $daily = $data; $daily['schedule_json'] = wp_json_encode( array( array( 'date' => $today, 'start' => '10:00', 'end' => '20:00' ) ) );
    $assert( FTUY_Events::validate( $daily )['end_time'] === '20:00', 'Horarios por día definen los límites de la agenda.' );
    $bad = $daily; $bad['schedule_json'] = '[{"date":"2020-01-01","start":"10:00","end":"20:00"}]';
    $assert( is_wp_error( FTUY_Events::validate( $bad ) ), 'Horario fuera del evento rechazado.' );
    $bad['schedule_json'] = '[{"date":"' . $today . '","start":"99:00","end":"20:00"}]';
    $assert( is_wp_error( FTUY_Events::validate( $bad ) ), 'Horario por día inválido rechazado.' );
    $bad['schedule_json'] = '{"broken":true}';
    $assert( is_wp_error( FTUY_Events::validate( $bad ) ), 'JSON de horarios mal formado rechazado.' );
    $assert( FTUY_Events::public_data( array_merge( $seed, FTUY_Events::validate( $daily ) ) )['schedule'][0]['start'] === '10:00', 'API expone horarios por día.' );
    wp_set_current_user( $user_id );
    $review_id = FTUY_Events::suggest( $data, $user_id );
    $assert( ! is_wp_error( $review_id ), 'Alta de propuesta.' );
    $r = FTUY_Events::review( $review_id ); $event_id = $r['event_id'];
    $event = FTUY_Events::get( $event_id );
    $assert( $event['status'] === 'pending', 'Alta pendiente.' );
    $assert( strpos( $event['description'], '<script>' ) === false, 'HTML peligroso filtrado.' );
    $assert( FTUY_Events::by_slug( $event['slug'] ) === null, 'Pendiente invisible.' );
    $response = rest_do_request( '/foodtrucks-uy/v1/events/' . $event['slug'] );
    $assert( $response->get_status() === 404, 'API no expone pendientes.' );
    $assert( is_wp_error( FTUY_Events::moderate( $review_id, 'published', '' ) ), 'Comunidad no puede aprobar.' );
    $assert( is_wp_error( FTUY_Events::suggest( $data, $admin->ID, $event_id ) ), 'Control de propiedad.' );
    wp_set_current_user( $admin->ID );
    $assert( FTUY_Events::moderate( $review_id, 'published', '' ) === true, 'Moderador aprueba.' );
    $event = FTUY_Events::get( $event_id );
    $assert( FTUY_Events::temporal( $event ) === 'ongoing', 'Sin horario termina al fin del día.' );
    $assert( FTUY_Events::by_slug( $event['slug'] )['id'] === $event['id'], 'Publicado visible.' );
    $assert( is_wp_error( FTUY_Events::moderate( $review_id, 'published', '' ) ), 'No aprobar dos veces.' );
    $data['title'] = 'Cambio pendiente de revisión';
    $edit_id = FTUY_Events::suggest( $data, $user_id, $event_id );
    $assert( ! is_wp_error( $edit_id ), 'Cambios enviados.' );
    $assert( FTUY_Events::get( $event_id )['title'] === 'Evento de integración temporal', 'Conserva versión publicada.' );
    $assert( FTUY_Events::moderate( $edit_id, 'corrections', 'Revisar nombre.' ) === true, 'Pide correcciones.' );
    $next_id = FTUY_Events::suggest( $data, $user_id, $event_id );
    $assert( FTUY_Events::review( $edit_id )['status'] === 'superseded', 'Propuesta anterior archivada.' );
    $assert( FTUY_Events::moderate( $next_id, 'rejected', 'No aprobado.' ) === true, 'Rechazo.' );
    $assert( FTUY_Events::get( $event_id )['title'] === 'Evento de integración temporal', 'Rechazo conserva ficha pública.' );
    $data['cancelled'] = 1;
    $next_id = FTUY_Events::suggest( $data, $user_id, $event_id );
    $assert( FTUY_Events::moderate( $next_id, 'published', '' ) === true, 'Cambio aprobado.' );
    $public = FTUY_Events::public_data( FTUY_Events::get( $event_id ), true );
    $assert( $public['cancelled'] && ! isset( $public['author_id'], $public['legacy_post_id'] ), 'Contrato público explícito.' );
    $assert( $public['instagram'] === 'https://www.instagram.com/expocafeuruguay/', 'Instagram en API y moderación.' );
    $request = new WP_REST_Request( 'GET', '/foodtrucks-uy/v1/events' ); $request->set_param( 'view', 'past' ); $request->set_param( 'per_page', 2 );
    $response = rest_do_request( $request );
    $assert( $response->get_status() === 200 && count( $response->get_data()['items'] ) === 2 && $response->get_data()['pages'] >= 2, 'Paginación.' );
    $request->set_param( 'department', 'Canelones' );
    $assert( rest_do_request( $request )->get_data()['total'] === 0, 'Filtro sin resultados.' );
    $request->set_param( 'department', 'Inexistente' );
    $assert( rest_do_request( $request )->get_status() === 400, 'API valida filtros.' );
    $assert( FTUY_Events::import_legacy( 4 )['skipped'] === 4, 'Importación idempotente.' );
    $mail = $wpdb->get_results( $wpdb->prepare( 'SELECT status FROM ' . FTUY_Events::table( 'event_mail' ) . ' WHERE id>%d AND recipient LIKE %s', $mail_before, '%@example.invalid' ), ARRAY_A );
    $assert( count( $mail ) > 0 && count( array_filter( $mail, function ( $m ) { return $m['status'] !== 'local'; } ) ) === 0, 'Emails capturados sin envío.' );
    echo "OK: $assertions comprobaciones de integración.\n";
} finally {
    // Exclusivamente filas creadas por esta prueba, sin tocar históricos ni propuestas de usuarios.
    if ( $event_id ) {
        $wpdb->delete( FTUY_Events::table( 'event_reviews' ), array( 'event_id' => $event_id ) );
        $wpdb->delete( FTUY_Events::table(), array( 'id' => $event_id ) );
    }
    $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . FTUY_Events::table( 'event_mail' ) . ' WHERE id>%d AND (recipient LIKE %s OR subject LIKE %s)', $mail_before, '%@example.invalid', '%Evento de integración temporal%' ) );
    // Equipo recibió también una notificación de la edición: limpiar solo títulos de prueba.
    $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . FTUY_Events::table( 'event_mail' ) . ' WHERE id>%d AND subject LIKE %s', $mail_before, '%Cambio pendiente de revisión%' ) );
    if ( $user_id && ! is_wp_error( $user_id ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $user_id ); }
    wp_set_current_user( $original_user );
}
