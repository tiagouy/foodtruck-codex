<?php
if ( ! defined( 'ABSPATH' ) || ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo local.' ); }
$old_user = get_current_user_id(); $old_ip = $_SERVER['REMOTE_ADDR'] ?? null;
$ids = array(); $emails = array(); $legacy = random_int( 910000000, 920000000 ); $source = null; $count = 0;
$hash = hash( 'sha256', 'synthetic-' . wp_generate_uuid4() );
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
$row = function ( $id, $email, $target = null, $avatar = null, $aliases = array() ) { return array( 'legacy_primary_id' => $id, 'legacy_alias_ids' => $aliases, 'email' => $email, 'display_name' => 'Prueba migración', 'legacy_created_at' => null, 'avatar_source' => $avatar, 'action' => $target ? 'link_existing_preserve_permissions' : 'create_subscriber', 'target_wp_user_id' => $target, 'preserve_admin' => false, 'publication_count' => 0, 'block_reason' => '' ); };
try {
    wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
    $_SERVER['REMOTE_ADDR'] = 'migration-sample-test-' . wp_generate_uuid4();
    require_once ABSPATH . 'wp-admin/includes/file.php'; $source = wp_tempnam( 'ftuy-avatar-fixture' );
    $image = imagecreatetruecolor( 600, 400 ); imagejpeg( $image, $source ); imagedestroy( $image ); $source_hash = hash_file( 'sha256', $source );
    for ( $i = 0; $i < 4; $i++ ) { $emails[] = 'ftuy-ms-' . wp_generate_uuid4() . '@example.invalid'; }
    $existing = wp_insert_user( array( 'user_login' => 'ftuy_ms_' . wp_generate_uuid4(), 'user_email' => $emails[0], 'display_name' => 'Nombre existente', 'user_pass' => wp_generate_password( 40 ), 'role' => 'subscriber' ) ); $ids[] = $existing;
    $before = get_user_by( 'id', $existing )->to_array();
    $plan = array( 'sample' => array( $row( $legacy, $emails[0], $existing ), $row( $legacy + 1, $emails[1], null, $source, array( $legacy + 2 ) ), $row( $legacy + 3, $emails[2] ) ) );
    $result = FTUY_User_Migration::apply_sample( $plan, $hash );
    foreach ( array_slice( $emails, 1, 2 ) as $email ) { $ids[] = get_user_by( 'email', $email )->ID; }
    $assert( $result['created_subscribers'] === 2 && $result['linked_existing'] === 1 && $result['avatars_imported'] === 1, 'Muestra crea, vincula e importa avatar.' );
    $after = get_user_by( 'id', $existing )->to_array();
    $assert( $before === $after && get_user_meta( $existing, 'ftuy_account_status', true ) === '', 'Cuenta existente intacta, incluidos nombre/pass/rol/fecha/estado.' );
    $user = get_user_by( 'email', $emails[1] );
    $assert( $user->roles === array( 'subscriber' ) && get_user_meta( $user->ID, 'ftuy_account_status', true ) === 'legacy_pending', 'Nueva cuenta suscriptora pendiente.' );
    $assert( FTUY_Accounts::legacy_owner( $legacy + 1 ) === $user->ID && FTUY_Accounts::legacy_owner( $legacy + 2 ) === $user->ID, 'Principal y alias resuelven mismo autor.' );
    $assert( is_wp_error( FTUY_Accounts::set_legacy_id( $existing, $legacy + 2 ) ), 'Otro usuario no toma el alias.' );
    $assert( is_wp_error( FTUY_Accounts::set_legacy_ids( $existing, $legacy, array( $legacy + 2 ) ) ), 'Otro usuario no toma un alias con setter múltiple.' );
    $avatar = FTUY_Profile_Images::attachment( $user->ID ); $path = get_attached_file( $avatar ); $info = wp_getimagesize( $path );
    $assert( $info[0] === 500 && $info[1] === 500 && filesize( $path ) <= 120 * 1024 && strpos( $path, '/media/perfiles/' ) !== false, 'Avatar optimizado y separado de foodtrucks.' );
    $assert( strpos( get_avatar_url( $user->ID ), '/media/perfiles/' ) !== false && hash_file( 'sha256', $source ) === $source_hash, 'Avatar propio sin cambiar fuente.' );
    $assert( get_user_meta( $user->ID, 'ftuy_legacy_date_unknown', true ) === '1', 'Fecha desconocida preservada como dato desconocido.' );
    $again = FTUY_User_Migration::apply_sample( $plan, $hash );
    $assert( $again['created_subscribers'] === 0 && $again['already_mapped'] === 3 && $again['avatars_already_present'] === 1 && FTUY_Profile_Images::attachment( $user->ID ) === $avatar, 'Repetir no duplica usuarios ni imágenes.' );
    $assert( FTUY_Accounts::request( 'reactivate', $emails[1] ) === true, 'Solicitud de reactivación.' );
    $mails = get_option( 'ftuy_account_mail_local' ); $mail = end( $mails ); preg_match( '~https?://[^\s]+~', $mail['message'], $match ); parse_str( wp_parse_url( $match[0], PHP_URL_QUERY ), $query );
    $reset = check_password_reset_key( $query['key'], $query['login'] );
    $assert( ! is_wp_error( $reset ) && $reset->ID === $user->ID, 'Enlace vinculado a usuario migrado.' );
    reset_password( $reset, 'Clave-sintetica-de-prueba-2026!' );
    $assert( get_user_meta( $user->ID, 'ftuy_account_status', true ) === 'active' && ! is_wp_error( wp_authenticate( $emails[1], 'Clave-sintetica-de-prueba-2026!' ) ), 'Reactivación activa y permite login.' );
    $assert( FTUY_Accounts::legacy_owner( $legacy + 2 ) === $user->ID && FTUY_Profile_Images::attachment( $user->ID ) === $avatar, 'Reactivación conserva alias y avatar.' );
    $too_many = $plan; $too_many['sample'] = array_merge( $plan['sample'], $plan['sample'] ); $failed = false;
    try { FTUY_User_Migration::apply_sample( $too_many, $hash ); } catch ( RuntimeException $e ) { $failed = true; }
    $assert( $failed, 'No permite más de cinco cuentas.' );
    wp_set_current_user( 0 ); $failed = false;
    try { FTUY_User_Migration::apply_sample( $plan, $hash ); } catch ( RuntimeException $e ) { $failed = true; }
    $assert( $failed, 'Anónimo no aplica migración.' );
    wp_set_current_user( $old_user ?: get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
    $partial = array( 'sample' => array( $row( $legacy + 4, $emails[3], null, $source ) ) );
    file_put_contents( $source, 'not an image' ); $failed = false;
    try { FTUY_User_Migration::apply_sample( $partial, $hash ); } catch ( RuntimeException $e ) { $failed = true; }
    $partial_user = get_user_by( 'email', $emails[3] ); if ( $partial_user ) { $ids[] = $partial_user->ID; }
    $assert( $failed && $partial_user && get_option( 'ftuy_user_migration_' . ( $legacy + 4 ) )['state'] === 'running' && ! get_option( 'ftuy_user_migration_lock' ), 'Error conserva avance y libera bloqueo.' );
    $image = imagecreatetruecolor( 500, 500 ); imagejpeg( $image, $source ); imagedestroy( $image );
    $resumed = FTUY_User_Migration::apply_sample( $partial, $hash );
    $assert( $resumed['created_subscribers'] === 0 && $resumed['already_mapped'] === 1 && $resumed['avatars_imported'] === 1, 'Retoma cuenta parcial sin duplicar.' );
    echo "OK: $count comprobaciones de importación de muestra.\n";
} finally {
    require_once ABSPATH . 'wp-admin/includes/user.php';
    $logins = array(); foreach ( $ids as $id ) { $fixture_user = get_user_by( 'id', $id ); if ( $fixture_user ) { $logins[] = $fixture_user->user_login; } }
    foreach ( $ids as $id ) { $avatar = FTUY_Profile_Images::attachment( $id ); if ( $avatar ) { wp_delete_attachment( $avatar, true ); } wp_delete_user( $id ); }
    for ( $i = 0; $i <= 4; $i++ ) { delete_option( 'ftuy_legacy_owner_' . ( $legacy + $i ) ); delete_option( 'ftuy_user_migration_' . ( $legacy + $i ) ); }
    update_option( 'ftuy_account_mail_local', array_values( array_filter( get_option( 'ftuy_account_mail_local', array() ), function ( $mail ) use ( $emails, $logins ) { if ( in_array( $mail['recipient'], $emails, true ) ) { return false; } foreach ( array_merge( $emails, $logins ) as $fixture ) { if ( strpos( $mail['message'], $fixture ) !== false ) { return false; } } return true; } ) ), false );
    foreach ( array_merge( array( 'request-ip-' . ( $_SERVER['REMOTE_ADDR'] ?? '' ) ), array_map( function ( $email ) { return 'request-email-' . $email; }, $emails ) ) as $bucket ) { delete_transient( 'ftuy_ac_' . hash_hmac( 'sha256', $bucket, wp_salt( 'auth' ) ) ); }
    if ( $source ) { wp_delete_file( $source ); }
    if ( $old_ip === null ) { unset( $_SERVER['REMOTE_ADDR'] ); } else { $_SERVER['REMOTE_ADDR'] = $old_ip; }
    wp_set_current_user( $old_user );
}
