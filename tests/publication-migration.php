<?php
if ( ! FTUY_Accounts::local() ) { throw new RuntimeException( 'Solo local.' ); }
global $wpdb; $old_user = get_current_user_id(); $uid = 0; $id = 0; $root = null; $photo = null; $count = 0;
$legacy_author = random_int( 930000000, 940000000 ); $legacy_post = random_int( 940000001, 950000000 ); $hash = hash( 'sha256', wp_generate_uuid4() );
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
try {
    wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
    $uid = wp_insert_user( array( 'user_login' => 'ftuy_pm_' . wp_generate_uuid4(), 'user_email' => 'ftuy-pm-' . wp_generate_uuid4() . '@example.invalid', 'user_pass' => wp_generate_password( 40 ), 'role' => 'subscriber' ) );
    FTUY_Accounts::set_legacy_ids( $uid, $legacy_author );
    $root = get_temp_dir() . 'ftuy-pm-' . wp_generate_uuid4(); $dir = $root . '/api2/offers/uploads/' . $legacy_post; wp_mkdir_p( $dir ); $photo = $dir . '/fixture.jpg';
    $canvas = imagecreatetruecolor( 1600, 800 ); imagejpeg( $canvas, $photo ); imagedestroy( $canvas ); $source_hash = hash_file( 'sha256', $photo );
    $row = array( 'idOffer' => $legacy_post, 'idUser' => $legacy_author, 'idCategory' => 21, 'description' => 'Texto original', 'lat' => '-34.9', 'lon' => '-56.1', 'address' => 'Lugar ficticio', 'photo' => 'fixture.jpg', 'creationDate' => '2018-01-04 16:35:31', 'status' => '0', 'import_discount' => 0, 'val' => 8, 'slug' => $legacy_post . '-foto-de-prueba' );
    $plan = FTUY_Publication_Migration::plan( array( $row ), $root );
    $assert( $plan['summary']['ready'] === 1 && $plan['summary']['blocked'] === 0, 'Plan con autor y foto válidos.' );
    $result = FTUY_Publication_Migration::apply( $plan, $hash ); $id = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . FTUY_Publications::table() . ' WHERE legacy_id=%d', $legacy_post ) );
    $assert( $result['imported'] === 1 && $id > 0, 'Importa una publicación.' );
    $p = FTUY_Publications::get( $id );
    $assert( $p['status'] === 'unpublished' && $p['created_at'] === $row['creationDate'] && (int) $p['author_user_id'] === $uid && $p['legacy_slug'] === $row['slug'], 'Conserva estado, fecha, autor y slug.' );
    $meta = json_decode( $p['legacy_metadata'], true );
    $assert( $meta['original'] === $row && $meta['date_timezone'] === 'unknown', 'Datos originales preservados sin inventar zona horaria.' );
    $image = get_attached_file( $p['image_id'] ); $size = wp_getimagesize( $image );
    $assert( $size[0] === 900 && $size[1] === 450 && filesize( $image ) <= 300 * 1024 && strpos( $image, '/media/publicaciones/' ) !== false, 'Optimiza proporción sin crop.' );
    $assert( hash_file( 'sha256', $photo ) === $source_hash, 'No altera original.' );
    $repeat = FTUY_Publication_Migration::apply( FTUY_Publication_Migration::plan( array( $row ), $root ), $hash );
    $assert( $repeat['imported'] === 0 && $repeat['already_imported'] === 1, 'Repetible sin duplicar medios ni filas.' );
    $assert( FTUY_Publication_Public::by_slug( $row['slug'] ) === null, 'Histórica despublicada permanece oculta.' );
    $assert( FTUY_Publication_Public::by_slug( (string) $legacy_post ) === null, 'ID histórico corto despublicado también oculto.' );
    $request = new WP_REST_Request( 'GET', '/foodtrucks-uy/v1/publications/by-slug/' . $row['slug'] );
    $assert( rest_do_request( $request )->get_status() === 404, 'Resolver de enlaces no expone despublicadas.' );
    $edited = array( 'caption' => $p['caption'], 'address' => $p['address'], 'latitude' => $p['latitude'], 'longitude' => $p['longitude'], 'status' => 'published' );
    $assert( FTUY_Publications::edit( $id, $edited, 1 ) === true && (int) FTUY_Publication_Public::by_slug( (string) $legacy_post )['id'] === $id, 'ID antiguo corto resuelve a misma publicación publicada.' );
    $data = rest_do_request( $request )->get_data();
    $assert( $data['id'] === $id && $data['created_timezone'] === null && ! isset( $data['legacy_metadata'] ), 'API resuelve slug sin exponer datos privados ni inventar UTC.' );
    $edited['status'] = 'unpublished'; FTUY_Publications::edit( $id, $edited, 2 );
    FTUY_Publication_Migration::apply( FTUY_Publication_Migration::plan( array( $row ), $root ), $hash );
    $assert( FTUY_Publications::get( $id )['status'] === 'unpublished', 'Reimportar conserva moderación posterior.' );
    $bad = $row; $bad['idUser'] = $legacy_author + 1; $bad['idOffer'] = $legacy_post + 1; $bad['slug'] .= '-otro';
    $assert( FTUY_Publication_Migration::plan( array( $bad ), $root )['summary']['blocked'] === 1, 'No asigna autores desconocidos.' );
    $failed = false; try { FTUY_Publication_Migration::plan( array( $row, $row ), $root ); } catch ( RuntimeException $e ) { $failed = true; }
    $assert( $failed, 'IDs/slug duplicados rechazados.' );
    $bad = $row; $bad['creationDate'] = '0000-00-00 00:00:00'; $failed = false; try { FTUY_Publication_Migration::plan( array( $bad ), $root ); } catch ( RuntimeException $e ) { $failed = true; }
    $assert( $failed, 'Fecha inválida no se inventa.' );
    wp_set_current_user( $uid ); $failed = false; try { FTUY_Publication_Migration::apply( $plan, $hash ); } catch ( RuntimeException $e ) { $failed = true; }
    $assert( $failed, 'Suscriptor no importa.' );
    echo "OK: $count comprobaciones de migración de publicaciones.\n";
} finally {
    if ( $id ) { $record = FTUY_Publications::get( $id ); if ( $record ) { wp_delete_attachment( $record['image_id'], true ); } foreach ( array( 'publication_reports', 'publication_audit' ) as $table ) { $wpdb->delete( FTUY_Publications::table( $table ), array( 'publication_id' => $id ) ); } $wpdb->delete( FTUY_Publications::table(), array( 'id' => $id ) ); }
    if ( $uid && ! is_wp_error( $uid ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $uid ); } delete_option( 'ftuy_legacy_owner_' . $legacy_author );
    if ( $photo ) { wp_delete_file( $photo ); @rmdir( dirname( $photo ) ); @rmdir( dirname( dirname( $photo ) ) ); @rmdir( $root . '/api2/offers' ); @rmdir( $root . '/api2' ); @rmdir( $root ); }
    wp_set_current_user( $old_user );
}
