<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Instagram {
    public static function init() {
        add_action( 'wp_ajax_ftuy_instagram_preview', array( __CLASS__, 'ajax' ) );
        add_action( 'wp_ajax_ftuy_instagram_public_preview', array( __CLASS__, 'ajax_public' ) );
        add_action( 'wp_ajax_nopriv_ftuy_instagram_public_preview', array( __CLASS__, 'ajax_public' ) );
    }
    public static function ajax_public() {
        if ( ! is_user_logged_in() ) { wp_send_json_error( array( 'message' => 'Ingresá para consultar Instagram.' ), 403 ); }
        check_ajax_referer( 'ftuy_instagram_public', 'nonce' );
        self::respond();
    }
    public static function ajax() {
        if ( ! current_user_can( 'manage_ft_foodtrucks' ) ) { wp_send_json_error( array( 'message' => 'Sin permiso.' ), 403 ); }
        check_ajax_referer( 'ftuy_instagram', 'nonce' );
        self::respond();
    }
    private static function respond() {
        $key = 'ftuy_ig_rate_' . get_current_user_id(); $count = (int) get_transient( $key );
        if ( $count >= 10 ) { wp_send_json_error( array( 'message' => 'Esperá unos minutos antes de volver a consultar.' ), 429 ); }
        set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );
        $result = self::lookup( wp_unslash( $_POST['instagram'] ?? '' ) );
        if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 422 ); }
        wp_send_json_success( $result );
    }
    public static function allowed_image( $url ) {
        $host = strtolower( wp_parse_url( $url, PHP_URL_HOST ) ?: '' );
        return wp_parse_url( $url, PHP_URL_SCHEME ) === 'https' && (bool) preg_match( '/(?:^|\.)(?:cdninstagram\.com|fbcdn\.net)$/', $host );
    }
    public static function extract( $html, $url ) {
        if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) { return new WP_Error( 'version', 'Esta función necesita WordPress 6.2 o superior.' ); }
        $parser = new WP_HTML_Tag_Processor( $html ); $meta = array();
        while ( $parser->next_tag( array( 'tag_name' => 'META' ) ) ) { $property = $parser->get_attribute( 'property' ); if ( in_array( $property, array( 'og:title', 'og:image' ), true ) ) { $meta[$property] = $parser->get_attribute( 'content' ); } }
        $user = trim( wp_parse_url( $url, PHP_URL_PATH ), '/' );
        $title = $meta['og:title'] ?? ''; $marker = ' (@' . $user . ')'; $pos = stripos( $title, $marker );
        if ( $pos === false || ! $pos ) { return new WP_Error( 'unavailable', 'Instagram no devolvió datos de ese perfil. Completá nombre y logo manualmente.' ); }
        $name = sanitize_text_field( substr( $title, 0, $pos ) );
        $image = esc_url_raw( $meta['og:image'] ?? '' );
        return array( 'name' => $name, 'instagram' => $url, 'image' => self::allowed_image( $image ) ? $image : '' );
    }
    public static function lookup( $input ) {
        if ( ! is_scalar( $input ) ) { return new WP_Error( 'input', 'Instagram inválido.' ); }
        $url = FTUY_Events::instagram_url( $input ); if ( is_wp_error( $url ) || ! $url ) { return is_wp_error( $url ) ? $url : new WP_Error( 'input', 'Ingresá un perfil de Instagram.' ); }
        // Solo la URL canónica de Instagram; nunca endpoints privados, cookies ni credenciales.
        $r = wp_safe_remote_get( $url, array( 'timeout' => 12, 'redirection' => 2, 'limit_response_size' => 1500000 ) );
        if ( is_wp_error( $r ) || wp_remote_retrieve_response_code( $r ) !== 200 ) { return new WP_Error( 'unavailable', 'No pudimos consultar Instagram. Podés completar los datos manualmente.' ); }
        $data = self::extract( wp_remote_retrieve_body( $r ), $url ); if ( is_wp_error( $data ) ) { return $data; }
        $token = wp_generate_password( 32, false, false );
        set_transient( 'ftuy_ig_' . get_current_user_id() . '_' . $token, $data, 10 * MINUTE_IN_SECONDS );
        $data['token'] = $token; return $data;
    }
    public static function import_logo( $token, $instagram ) {
        if ( ! is_string( $token ) || ! preg_match( '/^[a-zA-Z0-9]{32}$/', $token ) ) { return new WP_Error( 'token', 'Propuesta de Instagram inválida.' ); }
        $key = 'ftuy_ig_' . get_current_user_id() . '_' . $token; $data = get_transient( $key );
        $canonical = FTUY_Events::instagram_url( $instagram );
        if ( ! $data || is_wp_error( $canonical ) || $data['instagram'] !== $canonical || ! self::allowed_image( $data['image'] ) ) { return new WP_Error( 'token', 'La propuesta de Instagram venció o cambió el perfil. Consultalo otra vez o cargá el logo manualmente.' ); }
        $r = wp_safe_remote_get( $data['image'], array( 'timeout' => 12, 'redirection' => 0, 'limit_response_size' => 5 * MB_IN_BYTES + 1 ) );
        if ( is_wp_error( $r ) || wp_remote_retrieve_response_code( $r ) !== 200 ) { return new WP_Error( 'image', 'No pudimos descargar el logo. Podés cargarlo manualmente.' ); }
        $body = wp_remote_retrieve_body( $r ); if ( strlen( $body ) > 5 * MB_IN_BYTES ) { return new WP_Error( 'image', 'El logo supera 5 MB.' ); }
        $tmp = wp_tempnam( 'instagram-logo' ); if ( ! $tmp || file_put_contents( $tmp, $body ) === false ) { return new WP_Error( 'image', 'No se pudo preparar el logo.' ); }
        $info = wp_getimagesize( $tmp ); $types = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp' );
        if ( ! $info || ! isset( $types[$info['mime']] ) || $info[0] * $info[1] > 40000000 ) { wp_delete_file( $tmp ); return new WP_Error( 'image', 'El archivo de Instagram no es una imagen válida.' ); }
        require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
        $id = media_handle_sideload( array( 'name' => 'instagram-' . sanitize_title( $data['name'] ) . '.' . $types[$info['mime']], 'tmp_name' => $tmp ), 0, $data['name'] );
        if ( is_wp_error( $id ) ) { wp_delete_file( $tmp ); } else { delete_transient( $key ); }
        return $id;
    }
}
