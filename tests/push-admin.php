<?php
// wp eval-file tests/push-admin.php — synthetic credentials and intercepted HTTP only.
require_once dirname( __DIR__ ) . '/plugins/foodtrucks-uy-core/includes/class-ftuy-push.php';
FTUY_Push::install();
$previous_user = get_current_user_id();
$previous_key = getenv( 'PUSH_API_KEY' ); $previous_token = getenv( 'FTUY_PUSH_TOKEN_APP' );
putenv( 'PUSH_API_KEY=synthetic-test-key' ); putenv( 'FTUY_PUSH_TOKEN_APP=synthetic-test-app' );
$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
$calls = 0; $mode = 'ok'; $ids = array(); $checks = 0;
$assert = function ( $ok, $label ) use ( &$checks ) { if ( ! $ok ) { throw new Exception( $label ); } $checks++; };
$intercept = function ( $pre, $args, $url ) use ( &$calls, &$mode, $assert ) {
    if ( $url !== FTUY_Push::ENDPOINT ) { throw new Exception( 'Unexpected HTTP blocked' ); }
    $calls++;
    $assert( $args['redirection'] === 0 && $args['sslverify'] === true, 'Transport protection' );
    $assert( $args['body']['metodo'] === 'send' && $args['body']['idapp'] === 12, 'Documented contract' );
    if ( $mode === 'timeout' ) { return new WP_Error( 'timeout', 'synthetic-test-key must not be recorded' ); }
    if ( $mode === 'rejected' ) { return array( 'response' => array( 'code' => 401 ), 'body' => '{"error":"synthetic-test-key"}' ); }
    $body = $mode === 'malformed' ? '{"success":1}' : wp_json_encode( array( 'success' => 1, 'failure' => $mode === 'partial' ? 1 : 0, 'total' => $mode === 'partial' ? 2 : 1, 'provider' => 'fcm_v1' ) );
    return array( 'response' => array( 'code' => 200 ), 'body' => $body );
};
add_filter( 'pre_http_request', $intercept, 10, 3 );
try {
    $base = array( 'title' => 'Prueba', 'message' => 'Mensaje 🚚', 'audience' => 'users', 'users' => (string) $admin->ID, 'request_id' => wp_generate_uuid4() );
    wp_set_current_user( 0 ); $assert( is_wp_error( FTUY_Push::send( $base ) ), 'Unauthenticated blocked' );
    wp_set_current_user( $admin->ID );
    $assert( is_wp_error( FTUY_Push::send( array_merge( $base, array( 'audience' => 'all' ) ) ) ), 'Broadcast requires confirmation' );
    $assert( is_wp_error( FTUY_Push::send( array_merge( $base, array( 'users' => '0' ) ) ) ), 'Guest ID cannot become broadcast' );
    $assert( is_wp_error( FTUY_Push::send( array_merge( $base, array( 'message' => array() ) ) ) ), 'Arrays rejected' );
    putenv( 'PUSH_API_KEY' ); $assert( is_wp_error( FTUY_Push::send( $base ) ), 'Missing environment blocked' ); putenv( 'PUSH_API_KEY=synthetic-test-key' );
    $assert( $calls === 0, 'Validation never sends HTTP' );
    foreach ( array( 'ok' => 'accepted', 'partial' => 'partial_or_failed', 'timeout' => 'unknown', 'malformed' => 'unknown', 'rejected' => 'rejected' ) as $scenario => $expected ) {
        $mode = $scenario; $input = array_merge( $base, array( 'request_id' => wp_generate_uuid4() ) );
        if ( $scenario === 'ok' ) { $input['audience'] = 'all'; $input['confirm_all'] = 'yes'; }
        $id = FTUY_Push::send( $input ); $assert( is_int( $id ) && $id > 0, 'Registered send' ); $ids[] = $id;
        global $wpdb; $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Push::table() . ' WHERE id=%d', $id ), ARRAY_A );
        $assert( $row['status'] === $expected, 'Correct outcome' );
        $assert( strpos( wp_json_encode( $row ), 'synthetic-test' ) === false, 'No secrets in history' );
        $before = $calls; $assert( is_wp_error( FTUY_Push::send( $input ) ) && $calls === $before, 'No duplicate or uncertain retry' );
    }
    ob_start(); FTUY_Push::page(); $html = ob_get_clean();
    $assert( strpos( $html, 'synthetic-test' ) === false, 'No secrets in admin HTML' );
    $assert( strpos( $html, 'name="_wpnonce"' ) !== false, 'Nonce form' );
    echo "$checks checks passed; no real HTTP or push sent.\n";
} finally {
    remove_filter( 'pre_http_request', $intercept, 10 );
    foreach ( $ids as $id ) { $wpdb->delete( FTUY_Push::table(), array( 'id' => $id ) ); }
    wp_set_current_user( $previous_user );
    putenv( $previous_key === false ? 'PUSH_API_KEY' : 'PUSH_API_KEY=' . $previous_key );
    putenv( $previous_token === false ? 'FTUY_PUSH_TOKEN_APP' : 'FTUY_PUSH_TOKEN_APP=' . $previous_token );
}
