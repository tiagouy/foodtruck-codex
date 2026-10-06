<?php
/** Run with WP-CLI eval-file on the local installation only. */
if ( ! defined( 'ABSPATH' ) || wp_parse_url( home_url(), PHP_URL_HOST ) !== 'localhost' ) { throw new RuntimeException( 'Solo en localhost.' ); }
$count = 0; $ids = array(); $source = null; $oversized = null;
$assert = function ( $ok, $message ) use ( &$count ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $count++; };
try {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    // A small transparent rectangular PNG also exercises enlargement and flattening.
    $source = wp_tempnam( 'ftuy-image-test.png' );
    $canvas = imagecreatetruecolor( 80, 40 ); imagealphablending( $canvas, false ); imagesavealpha( $canvas, true );
    imagefill( $canvas, 0, 0, imagecolorallocatealpha( $canvas, 0, 0, 0, 127 ) ); imagepng( $canvas, $source ); imagedestroy( $canvas );
    $hash = hash_file( 'sha256', $source );
    foreach ( array( 'logo', 'truck_photo' ) as $role ) {
        $id = FTUY_Foodtruck_Images::process( $source, $role, 'test-optimized' );
        if ( ! is_wp_error( $id ) ) { $ids[] = $id; }
        $assert( ! is_wp_error( $id ), 'Procesa ' . $role );
        $path = get_attached_file( $id ); $info = wp_getimagesize( $path ); list( $edge, $limit ) = FTUY_Foodtruck_Images::settings( $role );
        $assert( $info[0] === $edge && $info[1] === $edge && $info['mime'] === 'image/jpeg', 'Dimensiones exactas y JPEG.' );
        $assert( filesize( $path ) <= $limit, 'Peso máximo.' );
        $bytes = file_get_contents( $path ); $jfif = strpos( $bytes, "JFIF\x00" );
        $assert( $jfif !== false && substr( $bytes, $jfif + 7, 5 ) === "\x01\x00\x48\x00\x48", 'Densidad 72 dpi.' );
        $jpeg = imagecreatefromjpeg( $path ); $rgb = imagecolorat( $jpeg, 0, 0 ); imagedestroy( $jpeg );
        $assert( ( $rgb & 0xFFFFFF ) === 0xFFFFFF, 'Transparencia sobre blanco.' );
        $assert( FTUY_Foodtruck_Images::ensure( $id, $role ) === $id, 'No duplica imágenes optimizadas.' );
    }
    $assert( hash_file( 'sha256', $source ) === $hash, 'No altera el original.' );
    $assert( is_wp_error( FTUY_Foodtruck_Images::process( $source, 'cover' ) ), 'No admite nuevas portadas.' );
    $oversized = wp_tempnam( 'ftuy-oversized-test' ); $handle = fopen( $oversized, 'wb' ); ftruncate( $handle, 5 * MB_IN_BYTES + 1 ); fclose( $handle );
    $error = FTUY_Foodtruck_Images::process( $oversized, 'logo' );
    $assert( is_wp_error( $error ) && $error->get_error_message() === 'La imagen pesa demasiado. Elegí un archivo de hasta 5 MB.', 'Error claro para archivos mayores a 5 MB.' );
    $error = FTUY_Foodtruck_Images::upload( array( 'error' => UPLOAD_ERR_INI_SIZE ), 'logo' );
    $assert( is_wp_error( $error ) && strpos( $error->get_error_message(), 'pesa demasiado' ) !== false, 'Error claro cuando PHP rechaza por tamaño.' );
    $legacy = FTUY_Foodtruck_Images::normalize( array( array( 'role' => 'official', 'attachment_id' => 1 ), array( 'role' => 'cover', 'attachment_id' => 2 ) ) );
    $assert( count( $legacy ) === 1 && $legacy[0]['role'] === 'truck_photo' && $legacy[0]['attachment_id'] === 2, 'Compatibilidad histórica sin galería.' );
    echo "OK: $count comprobaciones de imágenes.\n";
} finally {
    foreach ( $ids as $id ) { wp_delete_attachment( $id, true ); }
    if ( $source ) { wp_delete_file( $source ); }
    if ( $oversized ) { wp_delete_file( $oversized ); }
}
