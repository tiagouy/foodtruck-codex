<?php
if ( ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo local.' ); }
$old_user = get_current_user_id(); $ids = array(); $base = random_int( 960000000, 970000000 ); $file = null; $count = 0;
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
try {
    wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
    require_once ABSPATH . 'wp-admin/includes/file.php'; $file = wp_tempnam( 'ftuy-html-not-avatar' ); file_put_contents( $file, '<html>No es una foto</html>' );
    $rows = array();
    for ( $n = 0; $n < 6; $n++ ) { $rows[] = array( 'legacy_primary_id' => $base + $n, 'legacy_alias_ids' => $n === 0 ? array( $base + 100 ) : array(), 'email' => 'ftuy-all-' . wp_generate_uuid4() . '@example.invalid', 'display_name' => 'Prueba por lotes', 'legacy_created_at' => null, 'avatar_source' => $n === 0 ? $file : null, 'action' => 'create_subscriber', 'target_wp_user_id' => null, 'preserve_admin' => false, 'publication_count' => 0 ); }
    $plan = array( 'accounts' => $rows, 'summary' => array( 'blocked_conflicts' => 0 ) ); $hash = hash( 'sha256', wp_generate_uuid4() ); $callbacks = 0;
    $result = FTUY_User_Migration::apply_all( $plan, $hash, function () use ( &$callbacks ) { $callbacks++; } );
    foreach ( $rows as $row ) { $ids[] = get_user_by( 'email', $row['email'] )->ID; }
    $assert( $result['processed'] === 6 && $result['created_subscribers'] === 6 && $callbacks === 1, 'Importación completa supera límite de muestra, por lotes.' );
    $assert( $result['invalid_avatar_sources'] === 1 && get_user_meta( $ids[0], 'ftuy_legacy_avatar_needs_review', true ) === '1' && ! FTUY_Profile_Images::attachment( $ids[0] ), 'HTML no se publica como imagen; marca revisión.' );
    $assert( FTUY_Accounts::legacy_owner( $base + 100 ) === $ids[0], 'Alias preservado.' );
    $again = FTUY_User_Migration::apply_all( $plan, $hash );
    $assert( $again['created_subscribers'] === 0 && $again['already_mapped'] === 6, 'Retomar todo no duplica.' );
    $assert( get_user_meta( $ids[0], 'ftuy_account_status', true ) === 'legacy_pending', 'Cuenta sin imagen sigue reactivable.' );
    wp_set_current_user( 0 ); $failed = false; try { FTUY_User_Migration::apply_all( $plan, $hash ); } catch ( RuntimeException $e ) { $failed = true; }
    $assert( $failed, 'Importación completa solo administrativa.' );
    echo "OK: $count comprobaciones de importación completa.\n";
} finally {
    require_once ABSPATH . 'wp-admin/includes/user.php'; foreach ( $ids as $id ) { wp_delete_user( $id ); }
    for ( $n = 0; $n < 6; $n++ ) { delete_option( 'ftuy_legacy_owner_' . ( $base + $n ) ); delete_option( 'ftuy_user_migration_' . ( $base + $n ) ); }
    delete_option( 'ftuy_legacy_owner_' . ( $base + 100 ) ); if ( $file ) { wp_delete_file( $file ); } wp_set_current_user( $old_user );
}
