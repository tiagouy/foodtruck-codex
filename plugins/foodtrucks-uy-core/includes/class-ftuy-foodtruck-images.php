<?php
defined( 'ABSPATH' ) || exit;
require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

class FTUY_Truck_Editor_GD extends WP_Image_Editor_GD {
    public function prepare_jpeg() {
        $size = $this->get_size(); $canvas = imagecreatetruecolor( $size['width'], $size['height'] );
        imagefill( $canvas, 0, 0, imagecolorallocate( $canvas, 255, 255, 255 ) );
        imagecopy( $canvas, $this->image, 0, 0, 0, 0, $size['width'], $size['height'] );
        $this->image = $canvas; imageresolution( $this->image, 72, 72 );
    }
}
class FTUY_Truck_Editor_Imagick extends WP_Image_Editor_Imagick {
    public function prepare_jpeg() {
        $this->image->setImageBackgroundColor( 'white' );
        $this->image = $this->image->mergeImageLayers( Imagick::LAYERMETHOD_FLATTEN );
        $this->image->stripImage(); $this->image->setImageUnits( Imagick::RESOLUTION_PIXELSPERINCH ); $this->image->setImageResolution( 72, 72 );
    }
}
class FTUY_Foodtruck_Images {
    public static function settings( $role ) { return $role === 'logo' ? array( 500, 120 * 1024 ) : array( 900, 300 * 1024 ); }
    public static function normalize( $images ) {
        $result = array(); $photo = null; $photos = array();
        foreach ( (array) $images as $image ) {
            if ( ! is_array( $image ) || ! isset( $image['role'], $image['attachment_id'] ) || ! is_scalar( $image['role'] ) || ! is_scalar( $image['attachment_id'] ) ) { continue; }
            if ( $image['role'] === 'logo' ) { $result[] = $image; }
            elseif ( $image['role'] === 'truck_photo' ) { $photos[] = $image; }
            elseif ( in_array( $image['role'], array( 'cover', 'official' ), true ) && ( ! $photo || $image['role'] === 'cover' ) ) { $photo = $image; $photo['role'] = 'truck_photo'; }
        }
        if ( ! $photos && $photo ) { $photos[] = $photo; } return array_merge( $result, $photos );
    }
    public static function process( $path, $role, $name = 'foodtruck' ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        if ( ! in_array( $role, array( 'logo', 'truck_photo' ), true ) ) { return new WP_Error( 'role', 'Tipo de imagen inválido.' ); }
        $info = wp_getimagesize( $path );
        if ( ! $info || ! in_array( $info['mime'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) || $info[0] * $info[1] > 40000000 || filesize( $path ) > 5 * MB_IN_BYTES ) { return new WP_Error( 'image', 'Usá JPG, PNG o WebP de hasta 5 MB y 40 megapíxeles. El archivo se reducirá antes de guardarse.' ); }
        $filter = function () { return array( 'FTUY_Truck_Editor_GD', 'FTUY_Truck_Editor_Imagick' ); };
        add_filter( 'wp_image_editors', $filter );
        try { $editor = wp_get_image_editor( $path ); } finally { remove_filter( 'wp_image_editors', $filter ); }
        if ( is_wp_error( $editor ) ) { return new WP_Error( 'editor', 'El servidor no pudo procesar la imagen. No se guardó el archivo grande.' ); }
        $rotated = $editor->maybe_exif_rotate(); if ( is_wp_error( $rotated ) ) { return $rotated; }
        list( $edge, $limit ) = self::settings( $role ); $size = $editor->get_size(); $side = min( $size['width'], $size['height'] );
        $crop = $editor->crop( (int) floor( ( $size['width'] - $side ) / 2 ), (int) floor( ( $size['height'] - $side ) / 2 ), $side, $side, $edge, $edge ); if ( is_wp_error( $crop ) ) { return $crop; }
        $editor->prepare_jpeg(); $tmp = wp_tempnam( 'ftuy-optimized.jpg' ); if ( ! $tmp ) { return new WP_Error( 'temp', 'No se pudo preparar la imagen.' ); }
        $saved = null;
        foreach ( array( 82, 75, 68, 60, 52, 45, 35 ) as $quality ) {
            $editor->set_quality( $quality ); $saved = $editor->save( $tmp, 'image/jpeg' );
            if ( is_wp_error( $saved ) ) { wp_delete_file( $tmp ); return $saved; }
            clearstatcache( true, $saved['path'] ); if ( filesize( $saved['path'] ) <= $limit ) { break; }
        }
        $file = $saved['path'];
        $dimensions = wp_getimagesize( $file );
        if ( ! $dimensions || $dimensions[0] !== $edge || $dimensions[1] !== $edge ) { wp_delete_file( $file ); wp_delete_file( $tmp ); return new WP_Error( 'size', 'No se pudo generar el tamaño requerido. Probá otra imagen.' ); }
        if ( substr( file_get_contents( $file, false, null, 0, 2 ), 0, 2 ) !== "\xFF\xD8" ) { wp_delete_file( $file ); wp_delete_file( $tmp ); return new WP_Error( 'format', 'No se pudo generar el JPEG optimizado.' ); }
        // JFIF density is metadata, not the mechanism that reduces file size.
        $bytes = file_get_contents( $file );
        $jfif = "\xFF\xE0\x00\x10JFIF\x00\x01\x02\x01\x00\x48\x00\x48\x00\x00";
        if ( substr( $bytes, 2, 2 ) === "\xFF\xE0" && substr( $bytes, 6, 5 ) === "JFIF\x00" ) { $bytes = substr_replace( $bytes, "\x01\x00\x48\x00\x48", 13, 5 ); }
        else { $bytes = substr( $bytes, 0, 2 ) . $jfif . substr( $bytes, 2 ); }
        if ( strlen( $bytes ) > $limit || file_put_contents( $file, $bytes ) === false ) { wp_delete_file( $file ); wp_delete_file( $tmp ); return new WP_Error( 'weight', 'No se pudo reducir la imagen al peso permitido. Probá otra foto.' ); }
        require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
        // Only the processed JPEG is added to the library, never the large source.
        $id = media_handle_sideload( array( 'name' => sanitize_title( $name ) . '-' . $role . '-' . $edge . '.jpg', 'tmp_name' => $file ), 0 );
        if ( is_wp_error( $id ) ) { wp_delete_file( $file ); }
        if ( $tmp !== $file ) { wp_delete_file( $tmp ); }
        if ( ! is_wp_error( $id ) ) { update_post_meta( $id, '_ftuy_image_role', $role ); update_post_meta( $id, '_ftuy_image_version', 1 ); }
        return $id;
    }
    public static function upload( $file, $role ) {
        if ( ! is_array( $file ) || ! isset( $file['tmp_name'], $file['error'] ) || $file['error'] !== UPLOAD_ERR_OK || ! is_string( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) { return new WP_Error( 'upload', 'La imagen no pudo cargarse.' ); }
        return self::process( $file['tmp_name'], $role, pathinfo( $file['name'], PATHINFO_FILENAME ) );
    }
    public static function ensure( $id, $role ) {
        $path = get_attached_file( $id ); $info = $path && is_file( $path ) ? wp_getimagesize( $path ) : false; list( $edge, $limit ) = self::settings( $role );
        if ( $info && $info[0] === $edge && $info[1] === $edge && filesize( $path ) <= $limit && get_post_meta( $id, '_ftuy_image_role', true ) === $role && get_post_meta( $id, '_ftuy_image_version', true ) === '1' ) { return $id; }
        return $info ? self::process( $path, $role, get_the_title( $id ) ) : new WP_Error( 'image', 'No se encuentra el archivo original de la imagen.' );
    }
}
