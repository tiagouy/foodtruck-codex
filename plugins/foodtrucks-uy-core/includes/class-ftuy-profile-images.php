<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Profile_Images {
    const META = 'ftuy_profile_image_id';
    public static function init() { add_filter( 'get_avatar_data', array( __CLASS__, 'avatar' ), 10, 2 ); }
    public static function attachment( $user_id ) {
        $id = (int) get_user_meta( $user_id, self::META, true ); $post = get_post( $id );
        return $post && $post->post_type === 'attachment' && (int) $post->post_author === (int) $user_id && get_post_meta( $id, '_ftuy_media_category', true ) === 'perfiles' ? $id : 0;
    }
    public static function import( $user_id, $source ) {
        $existing = self::attachment( $user_id );
        if ( $existing ) { return $existing; }
        if ( ! get_user_by( 'id', $user_id ) || ! is_file( $source ) ) { return new WP_Error( 'profile_image', 'No se encuentra la imagen de perfil.' ); }
        $id = FTUY_Foodtruck_Images::process( $source, 'logo', 'perfil', 'perfiles' );
        if ( is_wp_error( $id ) ) { return $id; }
        $result = wp_update_post( array( 'ID' => $id, 'post_author' => $user_id ), true );
        if ( is_wp_error( $result ) ) { wp_delete_attachment( $id, true ); return $result; }
        update_user_meta( $user_id, self::META, $id );
        if ( self::attachment( $user_id ) !== $id ) { wp_delete_attachment( $id, true ); return new WP_Error( 'profile_image', 'No se pudo vincular la imagen de perfil.' ); }
        return $id;
    }
    /** Process a replacement before linking it; keep the previous medium recoverable. */
    public static function replace( $user_id, $source ) {
        if ( ! get_user_by( 'id', $user_id ) || ! is_file( $source ) ) { return new WP_Error( 'profile_image', 'No se encuentra la imagen de perfil.' ); }
        $old = self::attachment( $user_id );
        $id = FTUY_Foodtruck_Images::process( $source, 'logo', 'perfil', 'perfiles' );
        if ( is_wp_error( $id ) ) { return $id; }
        $result = wp_update_post( array( 'ID' => $id, 'post_author' => $user_id ), true );
        if ( is_wp_error( $result ) ) { wp_delete_attachment( $id, true ); return $result; }
        update_user_meta( $user_id, self::META, $id );
        if ( self::attachment( $user_id ) !== $id ) {
            if ( $old ) { update_user_meta( $user_id, self::META, $old ); } else { delete_user_meta( $user_id, self::META ); }
            wp_delete_attachment( $id, true ); return new WP_Error( 'profile_image', 'No se pudo guardar la foto de perfil.' );
        }
        return $id;
    }
    public static function avatar( $args, $identity ) {
        $user_id = is_numeric( $identity ) ? (int) $identity : 0;
        if ( $identity instanceof WP_User ) { $user_id = $identity->ID; }
        elseif ( $identity instanceof WP_Comment ) { $user_id = (int) $identity->user_id; }
        elseif ( is_string( $identity ) && is_email( $identity ) ) { $user = get_user_by( 'email', $identity ); $user_id = $user ? $user->ID : 0; }
        $id = $user_id ? self::attachment( $user_id ) : 0;
        if ( $id && empty( $args['force_default'] ) ) { $url = wp_get_attachment_image_url( $id, 'thumbnail' ); if ( $url ) { $args['url'] = $url; $args['found_avatar'] = true; } }
        return $args;
    }
}
