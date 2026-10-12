<?php
// Local-only fixture. Does not modify existing foodtrucks or delete attachments.
if ( ! defined( 'ABSPATH' ) || ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) { throw new RuntimeException( 'Solo local.' ); }
global $wpdb;
$original = get_current_user_id(); $id = 0; $count = 0;
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
try {
    $admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
    $seed = $wpdb->get_row( 'SELECT * FROM ' . FTUY_Foodtrucks::table() . " WHERE status='published' LIMIT 1", ARRAY_A );
    unset( $seed['id'] ); $seed['sample_key'] = null; $seed['slug'] = 'unpublish-test-' . wp_generate_uuid4();
    $assert( $wpdb->insert( FTUY_Foodtrucks::table(), $seed ) !== false, 'Fixture' ); $id = $wpdb->insert_id;
    $assert( $wpdb->insert( FTUY_Foodtrucks::table( 'foodtruck_reviews' ), array( 'foodtruck_id' => $id, 'author_id' => $admin->ID, 'payload' => wp_json_encode( $seed ), 'status' => 'pending', 'note' => '', 'created_at' => current_time( 'mysql', true ) ) ) !== false, 'Review fixture' );
    $review = $wpdb->insert_id;
    wp_set_current_user( 0 );
    $assert( is_wp_error( FTUY_Foodtrucks::unpublish( $id ) ), 'Anonymous denied' );
    wp_set_current_user( $admin->ID );
    ob_start(); FTUY_Foodtruck_Admin::unpublish_button( FTUY_Foodtrucks::get( $id ) ); $html = ob_get_clean();
    $assert( strpos( $html, 'method="post"' ) !== false && strpos( $html, '_wpnonce' ) !== false && strpos( $html, 'confirm(' ) !== false, 'POST nonce and confirmation' );
    $assert( ! is_wp_error( FTUY_Foodtrucks::unpublish( $id ) ), 'Unpublish' );
    $current = FTUY_Foodtrucks::get( $id );
    $assert( $current['status'] === 'unpublished', 'Status' );
    $assert( $current['name'] === $seed['name'] && $current['description'] === $seed['description'] && $current['responsible_user_id'] === $seed['responsible_user_id'], 'Data and owner preserved' );
    $assert( FTUY_Foodtrucks::review( $review )['status'] === 'superseded', 'Pending proposal closed' );
    $assert( is_wp_error( FTUY_Foodtrucks::moderate( $review, 'published' ) ), 'Old review cannot republish' );
    $items = FTUY_Foodtruck_Public::listing()['items'];
    $assert( ! in_array( $id, array_map( 'intval', array_column( $items, 'id' ) ), true ), 'Hidden web catalog' );
    $response = rest_do_request( '/foodtrucks-uy/v1/foodtrucks/' . $seed['slug'] );
    $assert( $response->get_status() === 404, 'API detail hidden' );
    $response = rest_do_request( '/foodtrucks-uy/v1/foodtrucks' );
    $assert( ! in_array( $id, array_map( 'intval', array_column( $response->get_data()['items'], 'id' ) ), true ), 'API list hidden' );
    $assert( is_wp_error( FTUY_Foodtrucks::unpublish( $id ) ), 'Only published allowed' );
    WP_CLI::success( "$count checks passed." );
} finally {
    if ( $id ) { $wpdb->delete( FTUY_Foodtrucks::table( 'foodtruck_reviews' ), array( 'foodtruck_id' => $id ) ); $wpdb->delete( FTUY_Foodtrucks::table(), array( 'id' => $id ) ); }
    wp_set_current_user( $original );
}
