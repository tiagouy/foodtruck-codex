<?php
// WP-CLI eval-file. Temporary fixture only; never run on production.
if ( ! defined( 'ABSPATH' ) || ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) { throw new RuntimeException( 'Solo local.' ); }
global $wpdb;
$original = get_current_user_id(); $id = 0; $count = 0;
$assert = function ( $ok, $label ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $label ); } $count++; };
try {
    $admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
    $seed = $wpdb->get_row( 'SELECT * FROM ' . FTUY_Events::table() . " WHERE status='published' LIMIT 1", ARRAY_A );
    unset( $seed['id'] ); $seed['legacy_post_id'] = null; $seed['slug'] = 'trash-test-' . wp_generate_uuid4();
    $seed['author_id'] = $admin->ID;
    $assert( $wpdb->insert( FTUY_Events::table(), $seed ) !== false, 'Fixture' ); $id = $wpdb->insert_id;
    $wpdb->insert( FTUY_Events::table( 'event_reviews' ), array( 'event_id' => $id, 'author_id' => $admin->ID, 'payload' => wp_json_encode( $seed ), 'status' => 'pending', 'note' => '', 'created_at' => current_time( 'mysql', true ) ) );
    $review = $wpdb->insert_id;
    wp_set_current_user( 0 );
    $assert( is_wp_error( FTUY_Events::trash( $id ) ), 'Denied anonymous' );
    wp_set_current_user( $admin->ID );
    $assert( ! is_wp_error( FTUY_Events::trash( $id ) ), 'Trash succeeds' );
    $assert( FTUY_Events::get( $id )['status'] === 'trash', 'Status' );
    $assert( ! FTUY_Events::by_slug( $seed['slug'] ), 'Public detail hidden' );
    $assert( FTUY_Events::review( $review )['status'] === 'superseded', 'Pending review closed' );
    $assert( is_wp_error( FTUY_Events::moderate( $review, 'published', '' ) ), 'Old review cannot republish' );
    $assert( is_wp_error( FTUY_Events::suggest( $seed, $admin->ID, $id ) ), 'Owner cannot revise trash' );
    $assert( FTUY_Events::get( $id )['image_id'] === $seed['image_id'] && get_post( $seed['image_id'] ), 'Media preserved' );
    $assert( ! is_wp_error( FTUY_Events::trash( $id, true ) ), 'Restore' );
    $assert( FTUY_Events::get( $id )['status'] === 'pending' && ! FTUY_Events::by_slug( $seed['slug'] ), 'Restore does not republish' );
    $assert( is_wp_error( FTUY_Events::trash( $id, true ) ), 'Restore only trash' );
    WP_CLI::success( "$count checks passed." );
} finally {
    if ( $id ) { $wpdb->delete( FTUY_Events::table( 'event_reviews' ), array( 'event_id' => $id ) ); $wpdb->delete( FTUY_Events::table(), array( 'id' => $id ) ); }
    wp_set_current_user( $original );
}
