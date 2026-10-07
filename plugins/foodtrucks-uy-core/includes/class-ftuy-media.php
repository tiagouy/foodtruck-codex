<?php
defined( 'ABSPATH' ) || exit;

/** Scoped storage: never redirects unrelated WordPress/library uploads. */
class FTUY_Media {
    public static function init() {
        add_filter( 'get_attached_file', array( __CLASS__, 'file' ), 10, 2 );
        add_filter( 'wp_get_attachment_url', array( __CLASS__, 'url' ), 10, 2 );
        add_filter( 'wp_calculate_image_srcset', array( __CLASS__, 'srcset' ), 10, 5 );
        add_action( 'delete_attachment', array( __CLASS__, 'delete_files' ) );
    }
    public static function root() { return untrailingslashit( defined( 'FTUY_MEDIA_DIR' ) ? FTUY_MEDIA_DIR : ABSPATH . 'media' ); }
    public static function baseurl() { return untrailingslashit( defined( 'FTUY_MEDIA_URL' ) ? FTUY_MEDIA_URL : site_url( '/media' ) ); }
    private static function relative( $id ) {
        $path = get_post_meta( $id, '_ftuy_media_path', true );
        if ( ! is_string( $path ) ) { return ''; }
        $parts = explode( '/', $path );
        return count( $parts ) === 3 && in_array( $parts[0], array( 'eventos', 'foodtrucks', 'perfiles', 'publicaciones' ), true ) && preg_match( '/^[a-f0-9-]{36}$/D', $parts[1] ) && $parts[2] !== '' && strpos( $path, '..' ) === false && strpos( $path, chr(92) ) === false && ! preg_match( '/[\x00-\x1f]/', $path ) ? $path : '';
    }
    public static function file( $file, $id ) {
        $relative = self::relative( $id ); return $relative ? self::root() . '/' . $relative : $file;
    }
    public static function url( $url, $id ) {
        $relative = self::relative( $id ); return $relative ? self::baseurl() . '/' . dirname( $relative ) . '/' . rawurlencode( wp_basename( $relative ) ) : $url;
    }
    public static function srcset( $sources, $size, $src, $meta, $id ) {
        if ( self::relative( $id ) && is_array( $sources ) ) {
            $directory = dirname( self::url( '', $id ) );
            foreach ( $sources as &$source ) { $source['url'] = $directory . '/' . wp_basename( wp_parse_url( $source['url'], PHP_URL_PATH ) ); }
        }
        return $sources;
    }
    public static function delete_files( $id ) {
        $relative = self::relative( $id ); if ( ! $relative ) { return; }
        $directory = self::root() . '/' . dirname( $relative );
        $meta = wp_get_attachment_metadata( $id );
        $names = array( wp_basename( $relative ) );
        foreach ( (array) ( $meta['sizes'] ?? array() ) as $size ) { if ( isset( $size['file'] ) ) { $names[] = $size['file']; } }
        foreach ( array( 'original_image', 'thumb', 'source_image' ) as $key ) { if ( ! empty( $meta[$key] ) ) { $names[] = $meta[$key]; } }
        foreach ( (array) get_post_meta( $id, '_wp_attachment_backup_sizes', true ) as $size ) { if ( isset( $size['file'] ) ) { $names[] = $size['file']; } }
        foreach ( array_unique( $names ) as $name ) {
            if ( is_string( $name ) && $name === wp_basename( $name ) ) { wp_delete_file_from_directory( $directory . '/' . $name, $directory ); }
        }
        wp_delete_file_from_directory( $directory . '/index.html', $directory );
        @rmdir( $directory ); // Only removes the exact, now-empty attachment directory.
    }
    /** Callback must perform a WP media upload including metadata generation. */
    public static function store( $category, $callback ) {
        if ( ! in_array( $category, array( 'eventos', 'foodtrucks', 'perfiles', 'publicaciones' ), true ) ) { return new WP_Error( 'media_category', 'Categoría de imagen inválida.' ); }
        $root = self::root();
        if ( ! wp_mkdir_p( $root ) || ! is_writable( $root ) ) { return new WP_Error( 'media_directory', 'No se pudo preparar la carpeta de imágenes.' ); }
        // Do not overwrite an operator's configuration. No directory listing or PHP execution.
        foreach ( array( 'index.html' => '', '.htaccess' => "<FilesMatch \"(?i)\\.(php[0-9]*|phtml|phar)(\\.|$)\">\nRequire all denied\n</FilesMatch>\n" ) as $name => $content ) {
            if ( ! file_exists( $root . '/' . $name ) ) {
                $handle = @fopen( $root . '/' . $name, 'x' );
                if ( ! $handle ) { return new WP_Error( 'media_protection', 'No se pudo proteger la carpeta de imágenes.' ); }
                fwrite( $handle, $content ); fclose( $handle );
            }
        }
        foreach ( array( 'eventos', 'foodtrucks', 'perfiles', 'publicaciones' ) as $folder ) {
            if ( ! wp_mkdir_p( $root . '/' . $folder ) ) { return new WP_Error( 'media_directory', 'No se pudo preparar la carpeta de imágenes.' ); }
            if ( ! file_exists( $root . '/' . $folder . '/index.html' ) && file_put_contents( $root . '/' . $folder . '/index.html', '' ) === false ) { return new WP_Error( 'media_directory', 'No se pudo proteger la carpeta de imágenes.' ); }
        }
        $group = $category . '/' . wp_generate_uuid4();
        if ( ! wp_mkdir_p( $root . '/' . $group ) || file_put_contents( $root . '/' . $group . '/index.html', '' ) === false ) { return new WP_Error( 'media_directory', 'No se pudo preparar la carpeta de imágenes.' ); }
        $filter = function ( $uploads ) use ( $root, $group ) {
            return array( 'basedir' => $root, 'baseurl' => self::baseurl(), 'subdir' => '/' . $group, 'path' => $root . '/' . $group, 'url' => self::baseurl() . '/' . $group, 'error' => false );
        };
        add_filter( 'upload_dir', $filter, 999 );
        try {
            $id = call_user_func( $callback );
            if ( ! is_wp_error( $id ) && $id ) {
                $path = get_attached_file( $id );
                update_post_meta( $id, '_ftuy_media_path', $group . '/' . wp_basename( $path ) );
                update_post_meta( $id, '_ftuy_media_category', $category );
            }
            return $id;
        } finally {
            remove_filter( 'upload_dir', $filter, 999 );
            if ( empty( $id ) || is_wp_error( $id ) ) {
                wp_delete_file_from_directory( $root . '/' . $group . '/index.html', $root . '/' . $group );
                @rmdir( $root . '/' . $group );
            }
        }
    }
}
