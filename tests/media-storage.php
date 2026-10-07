<?php
if ( ! defined( 'ABSPATH' ) || wp_parse_url( home_url(), PHP_URL_HOST ) !== 'localhost' ) { throw new RuntimeException( 'Solo local.' ); }
$ids = array(); $directories = array(); $source = null; $count = 0;
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
try {
    require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
    $before = wp_upload_dir();
    $source = wp_tempnam( 'ftuy-media-test.jpg' );
    $image = imagecreatetruecolor( 1000, 1000 ); imagejpeg( $image, $source ); imagedestroy( $image );
    foreach ( array( 'eventos', 'foodtrucks', 'perfiles', 'publicaciones' ) as $category ) {
        $tmp = wp_tempnam( 'ftuy-media-copy' ); copy( $source, $tmp );
        $id = FTUY_Media::store( $category, function () use ( $tmp ) { return media_handle_sideload( array( 'name' => 'ftuy-storage-test.jpg', 'tmp_name' => $tmp ), 0 ); } );
        if ( is_wp_error( $id ) ) { wp_delete_file( $tmp ); throw new RuntimeException( $id->get_error_message() ); }
        $ids[] = $id; $path = get_attached_file( $id ); $directories[] = dirname( $path );
        $assert( is_file( $path ) && strpos( $path, FTUY_Media::root() . '/' . $category . '/' ) === 0, 'Archivo en categoría propia.' );
        $url = wp_get_attachment_url( $id );
        $assert( strpos( $url, FTUY_Media::baseurl() . '/' . $category . '/' ) === 0, 'URL estable fuera de uploads.' );
        $thumb = wp_get_attachment_image_src( $id, 'thumbnail' );
        $assert( $thumb && is_file( dirname( $path ) . '/' . wp_basename( $thumb[0] ) ), 'Miniatura accesible.' );
        $srcset = wp_get_attachment_image_srcset( $id, 'full' );
        $assert( $srcset && strpos( $srcset, 'wp-content/uploads' ) === false && strpos( $srcset, FTUY_Media::baseurl() ) !== false, 'Srcset usa media.' );
        $response = wp_remote_get( $url );
        $assert( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200, 'HTTP público 200.' );
    }
    $assert( wp_upload_dir()['basedir'] === $before['basedir'], 'No cambia subidas normales.' );
    $assert( is_wp_error( FTUY_Media::store( '../escape', function () {} ) ), 'Rechaza categoría manipulada.' );
    try { FTUY_Media::store( 'eventos', function () { throw new RuntimeException( 'fixture' ); } ); } catch ( RuntimeException $e ) {}
    $assert( wp_upload_dir()['basedir'] === $before['basedir'], 'Restaura filtros tras excepción.' );
    foreach ( $ids as $id ) {
        $path = get_attached_file( $id ); wp_delete_attachment( $id, true );
        $assert( ! file_exists( $path ), 'Eliminación WP borra archivo propio.' );
    }
    $ids = array();
    echo "OK: $count comprobaciones de almacenamiento.\n";
} finally {
    foreach ( $ids as $id ) { wp_delete_attachment( $id, true ); }
    foreach ( $directories as $directory ) { if ( is_dir( $directory ) ) { @rmdir( $directory ); } }
    if ( $source ) { wp_delete_file( $source ); }
}
