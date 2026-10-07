<?php
if ( ! defined( 'ABSPATH' ) || ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo en local.' ); }
$count = 0; $fixture = null;
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
$row = function ( $id, $email, $name = 'Nombre' ) { return array( 'idUser' => (string) $id, 'firstname' => $name, 'lastname' => 'Apellido', 'email' => $email, 'creationDate' => '0000-00-00 00:00:00', 'status' => '1', 'photo' => '' ); };
$photo = function ( $id, $author ) { return array( 'idOffer' => (string) $id, 'idUser' => (string) $author, 'photo' => 'test.jpg', 'status' => '1' ); };
try {
    $data = array( 'users' => array( $row( 20, 'SAME@example.invalid', 'Segundo' ), $row( 10, 'same@example.invalid', 'Primero' ), $row( 30, '' ), $row( 40, 'bad-email' ), $row( 50, 'owner@example.invalid' ) ), 'offers' => array( $photo( 2, 20 ), $photo( 1, 10 ), $photo( 3, 50 ) ) );
    $existing = array( array( 'id' => 77, 'email' => 'owner@example.invalid', 'legacy_id' => 0, 'aliases' => array(), 'is_admin' => true ) );
    $plan = FTUY_User_Migration::plan( $data, '', 5, $existing ); $summary = $plan['summary'];
    $assert( $summary['source_accounts'] === 5 && $summary['excluded_unusable_email'] === 2 && $summary['destination_accounts'] === 2, 'Excluye emails inválidos y unifica duplicados.' );
    $assert( $summary['would_create_subscribers'] === 1 && $summary['would_link_existing'] === 1, 'Cuenta nueva y vínculo existente diferenciados.' );
    $assert( $plan['accounts'][0]['legacy_primary_id'] === 10 && $plan['accounts'][0]['legacy_alias_ids'] === array( 20 ) && $plan['accounts'][0]['display_name'] === 'Primero Apellido', 'El menor ID es principal y conserva alias.' );
    $assert( $plan['accounts'][0]['first_name'] === 'Primero' && $plan['accounts'][0]['last_name'] === 'Apellido', 'Campos de nombre y apellido separados desde origen.' );
    $assert( $plan['accounts'][1]['preserve_admin'] && $plan['accounts'][1]['target_wp_user_id'] === 77, 'No duplica ni degrada al administrador.' );
    $assert( $summary['publications']['with_account_mapping'] === 3 && $plan['publications'][1]['canonical_author_id'] === 10, 'Fotos de ambos IDs conservan autor canónico.' );
    $assert( $plan === FTUY_User_Migration::plan( $data, '', 5, $existing ), 'Simulación repetible.' );
    $reverse = $data; $reverse['users'] = array_reverse( $data['users'] ); $reverse['offers'] = array_reverse( $data['offers'] );
    $assert( $plan === FTUY_User_Migration::plan( $reverse, '', 5, $existing ), 'Orden del SQL no altera selección o resultado.' );
    $mapped = $existing; $mapped[0]['legacy_id'] = 50; $done = FTUY_User_Migration::plan( $data, '', 5, $mapped );
    $assert( $done['summary']['already_mapped'] === 1 && $done['summary']['would_link_existing'] === 0, 'Reconoce equivalencias ya realizadas.' );
    $conflict = $existing; $conflict[0]['legacy_id'] = 10; $blocked = FTUY_User_Migration::plan( $data, '', 5, $conflict );
    $assert( $blocked['summary']['blocked_conflicts'] === 2 && $blocked['summary']['publications']['blocked_or_unmapped'] === 3, 'Bloquea IDs asignados a otra identidad.' );
    $duplicate_wp = array_merge( $existing, $existing );
    $assert( FTUY_User_Migration::plan( $data, '', 5, $duplicate_wp )['summary']['blocked_conflicts'] === 1, 'No elige arbitrariamente entre dos cuentas WordPress.' );
    $orphan = $data; $orphan['offers'][] = $photo( 4, 999 );
    $assert( FTUY_User_Migration::plan( $orphan, '', 5, $existing )['summary']['publications']['blocked_or_unmapped'] === 1, 'Detecta fotos sin cuenta de destino.' );
    $bad = $data; $bad['users'][] = $row( 10, 'duplicate@example.invalid' ); $failed = false;
    try { FTUY_User_Migration::plan( $bad, '', 5, $existing ); } catch ( RuntimeException $e ) { $failed = true; }
    $assert( $failed, 'Rechaza IDs de usuario repetidos.' );
    $assert( strpos( wp_json_encode( $summary ), '@' ) === false && strpos( wp_json_encode( $summary ), 'Primero' ) === false, 'Informe público solo con totales.' );
    require_once ABSPATH . 'wp-admin/includes/file.php'; $fixture = wp_tempnam( 'ftuy-sql-plan-test' );
    // Synthetic data only; no database or account creation in this test.
    file_put_contents( $fixture, "INSERT INTO `users` (`idUser`, `name`, `password`) VALUES\n(1, 'O\\'Brien; texto, UTF-8 ñ', 'secret'),\n(2, 'Doble''comilla', NULL);\n" );
    $parsed = FTUY_Legacy_SQL::read( $fixture, array( 'users' => array( 'idUser', 'name' ) ) );
    $assert( count( $parsed['users'] ) === 2 && $parsed['users'][0]['name'] === "O'Brien; texto, UTF-8 ñ" && $parsed['users'][1]['name'] === "Doble'comilla", 'Parser maneja escapes, comas y punto y coma en texto.' );
    $assert( ! isset( $parsed['users'][0]['password'] ), 'Credenciales fuera del plan.' );
    file_put_contents( $fixture, "INSERT INTO `users` (`idUser`) VALUES (NOW());" ); $failed = false;
    try { FTUY_Legacy_SQL::read( $fixture, array( 'users' => array( 'idUser' ) ) ); } catch ( RuntimeException $e ) { $failed = true; }
    $assert( $failed, 'Nunca evalúa expresiones SQL.' );
    file_put_contents( $fixture, "INSERT INTO `users` (`idUser`) VALUES (1)" ); $failed = false;
    try { FTUY_Legacy_SQL::read( $fixture, array( 'users' => array( 'idUser' ) ) ); } catch ( RuntimeException $e ) { $failed = true; }
    $assert( $failed, 'Rechaza INSERT truncado.' );
    global $wpdb;
    $snapshot = function () use ( $wpdb ) { return hash( 'sha256', wp_json_encode( array( $wpdb->get_results( "SELECT * FROM {$wpdb->users} ORDER BY ID", ARRAY_A ), $wpdb->get_results( "SELECT * FROM {$wpdb->usermeta} ORDER BY umeta_id", ARRAY_A ), get_option( 'ftuy_account_mail_local', array() ) ) ) ); };
    $before = $snapshot(); $source = dirname( __DIR__ ) . '/Bds/usefsgir_foodTruck.sql'; $source_hash = hash_file( 'sha256', $source );
    $actual = FTUY_Legacy_SQL::read( $source, array( 'users' => array( 'idUser', 'firstname', 'lastname', 'email', 'creationDate', 'status', 'photo' ), 'offers' => array( 'idOffer', 'idUser', 'photo', 'status' ) ) );
    $real = FTUY_User_Migration::plan( $actual, dirname( __DIR__ ) . '/foodtruckuruguay.com', 5 );
    $assert( $real['summary']['source_accounts'] === 3677 && $real['summary']['excluded_unusable_email'] === 16 && $real['summary']['destination_accounts'] === 3660, 'Snapshot auditado: cantidades de cuentas correctas.' );
    $assert( $real['summary']['publications']['total'] === 51 && $real['summary']['publications']['with_account_mapping'] === 51 && $real['summary']['publications']['files_found'] === 51, 'Conserva relación y archivos de las 51 publicaciones.' );
    $assert( $before === $snapshot() && $source_hash === hash_file( 'sha256', $source ), 'Simulación no cambia usuarios, metadata, correos ni SQL.' );
    echo "OK: $count comprobaciones del plan de migración.\n";
} finally { if ( $fixture ) { wp_delete_file( $fixture ); } }
