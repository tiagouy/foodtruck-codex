<?php
if ( ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo local.' ); }
global $wpdb;
$user = 0; $photo = 0; $count = 0; $mails = 0;
$capture = function () use ( &$mails ) { $mails++; return true; };
add_filter( 'pre_wp_mail', $capture, 1 );
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
try {
    $user = wp_insert_user( array( 'user_login' => 'report-fixture-' . wp_generate_uuid4(), 'user_email' => wp_generate_uuid4() . '@example.invalid', 'user_pass' => wp_generate_password( 32 ), 'role' => 'subscriber' ) );
    if ( is_wp_error( $user ) ) { throw new RuntimeException( 'Fixture.' ); }
    $secret = bin2hex( random_bytes( 32 ) );
    update_user_meta( $user, FTUY_App_Sessions::PREFIX . hash( 'sha256', $secret ), array( 'expires' => time() + 3600, 'password_signature' => hash_hmac( 'sha256', get_userdata( $user )->user_pass, wp_salt( 'auth' ) ) ) );
    $wpdb->insert( FTUY_Publications::table(), array( 'author_user_id' => $user, 'image_id' => 0, 'caption' => 'Fixture', 'status' => 'published', 'created_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ) ) );
    $photo = (int) $wpdb->insert_id;
    $r = new WP_REST_Request( 'POST' ); $r['id'] = $photo;
    $assert( is_wp_error( FTUY_Publications::app_report( $r ) ), 'No permite denuncias anónimas.' );
    $r->set_header( 'authorization', 'Bearer ' . $user . '.' . $secret );
    $r['added_by'] = 1; $r['status'] = 'unpublished';
    $result = FTUY_Publications::app_report( $r );
    $assert( $result instanceof WP_REST_Response && $result->get_status() === 202, 'Denuncia aceptada.' );
    $assert( $result->get_data() === array( 'received' => true ), 'Sin datos privados.' );
    $assert( FTUY_Publications::get( $photo )['status'] === 'published', 'No despublica automáticamente.' );
    $report = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Publications::table( 'publication_reports' ) . ' WHERE publication_id=%d', $photo ), ARRAY_A );
    $assert( (int) $report['added_by'] === $user && $report['status'] === 'open', 'Actor autenticado; ignora campos manipulados.' );
    $assert( $mails === 1, 'Aviso enviado al sistema de correos.' );
    FTUY_Publications::app_report( $r );
    $assert( $mails === 1 && (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . FTUY_Publications::table( 'publication_reports' ) . ' WHERE publication_id=%d', $photo ) ) === 1, 'Repetición no duplica denuncia ni email.' );
    $assert( (int) $wpdb->get_var( $wpdb->prepare( 'SELECT actor_user_id FROM ' . FTUY_Publications::table( 'publication_audit' ) . ' WHERE publication_id=%d', $photo ) ) === $user, 'Historial atribuido al denunciante.' );
    update_user_meta( $user, 'ftuy_account_status', 'email_pending' );
    $assert( is_wp_error( FTUY_Publications::app_report( $r ) ), 'Cuenta pendiente rechazada.' );
    update_user_meta( $user, 'ftuy_account_status', 'active' );
    $wpdb->update( FTUY_Publications::table(), array( 'status' => 'unpublished' ), array( 'id' => $photo ) );
    $result = FTUY_Publications::app_report( $r );
    $assert( is_wp_error( $result ) && $result->get_error_data()['status'] === 404, 'Foto despublicada no admite denuncias.' );
    echo "OK: $count comprobaciones de denuncias.\n";
} finally {
    remove_filter( 'pre_wp_mail', $capture, 1 );
    foreach ( array( 'publication_reports', 'publication_audit' ) as $table ) { $wpdb->delete( FTUY_Publications::table( $table ), array( 'publication_id' => $photo ) ); }
    $wpdb->delete( FTUY_Publications::table(), array( 'id' => $photo ) );
    delete_transient( 'ftuy_ac_' . hash_hmac( 'sha256', 'report-user-' . $user, wp_salt( 'auth' ) ) );
    if ( $user && ! is_wp_error( $user ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $user ); }
}
