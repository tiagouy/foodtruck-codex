<?php
if ( ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo local.' ); }
$id = 0; $legacy = random_int( 980000000, 990000000 ); $count = 0;
$assert = function ( $ok ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( 'Falló prueba de nombres.' ); } $count++; };
try {
    $hash = hash( 'sha256', wp_generate_uuid4() );
    $id = wp_insert_user( array( 'user_login' => 'ftuy-names-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password( 40 ), 'role' => 'subscriber', 'display_name' => 'Nombre público conservado', 'meta_input' => array( 'ftuy_migration_source_sha256' => $hash ) ) );
    if ( is_wp_error( $id ) ) { throw new RuntimeException( 'No se creó fixture.' ); }
    FTUY_Accounts::set_legacy_ids( $id, $legacy, array( $legacy + 1 ) );
    $source = array( array( 'idUser' => $legacy, 'firstname' => ' María José ', 'lastname' => 'De León' ), array( 'idUser' => $legacy + 1, 'firstname' => 'Alias', 'lastname' => 'Otro' ) );
    $updates = FTUY_User_Migration::name_updates( $source, $hash );
    $assert( count( $updates ) === 2 && $updates[0]['value'] === 'María José' && $updates[1]['value'] === 'De León' );
    $assert( FTUY_User_Migration::name_updates( $source, 'otra-fuente' ) === array() );
    foreach ( $updates as $update ) { update_user_meta( $update['id'], $update['field'], $update['value'] ); }
    $assert( FTUY_User_Migration::name_updates( $source, $hash ) === array() );
    $assert( get_userdata( $id )->display_name === 'Nombre público conservado' );
    update_user_meta( $id, 'first_name', 'Editado' ); delete_user_meta( $id, 'last_name' );
    $updates = FTUY_User_Migration::name_updates( $source, $hash );
    $assert( count( $updates ) === 1 && $updates[0]['field'] === 'last_name' );
    $source[0]['lastname'] = ''; $assert( FTUY_User_Migration::name_updates( $source, $hash ) === array() );
    get_userdata( $id )->set_role( 'administrator' );
    $source[0]['lastname'] = 'De León'; $assert( FTUY_User_Migration::name_updates( $source, $hash ) === array() );
    echo "OK: $count comprobaciones de nombres históricos.\n";
} finally {
    if ( $id && ! is_wp_error( $id ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $id ); }
    delete_option( 'ftuy_legacy_owner_' . $legacy ); delete_option( 'ftuy_legacy_owner_' . ( $legacy + 1 ) );
}
