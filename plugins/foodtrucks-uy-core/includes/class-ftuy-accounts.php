<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Accounts {
    public static $view = '';
    public static $error = '';
    public static $notice = '';
    public static $email = '';
    public static $name = '';
    private static $guest = '';
    const LEGACY_META = 'ftuy_legacy_user_id';

    public static function local() { return wp_get_environment_type() === 'local' || in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1', '::1' ), true ); }
    public static function registration_enabled() { return (bool) get_option( 'ftuy_registration_enabled', self::local() ); }
    public static function init() {
        // Also catch native WordPress password-change notifications in local tests.
        if ( self::local() ) { add_filter( 'pre_wp_mail', function ( $result, $args ) {
            $mail = get_option( 'ftuy_account_mail_local', array() );
            $mail[] = array( 'recipient' => is_array( $args['to'] ) ? implode( ', ', $args['to'] ) : $args['to'], 'subject' => $args['subject'], 'message' => $args['message'], 'created_at' => current_time( 'mysql', true ) );
            update_option( 'ftuy_account_mail_local', array_slice( $mail, -100 ), false ); return true;
        }, 999, 2 ); }
        register_meta( 'user', self::LEGACY_META, array( 'type' => 'integer', 'single' => true, 'show_in_rest' => false, 'auth_callback' => function () { return current_user_can( 'manage_options' ); } ) );
        add_action( 'template_redirect', array( __CLASS__, 'route' ), -1 );
        add_filter( 'nonce_user_logged_out', function ( $id, $action ) {
            return strpos( $action, 'ftuy_account_' ) === 0 && self::$guest ? hexdec( substr( hash_hmac( 'sha256', self::$guest, wp_salt( 'nonce' ) ), 0, 7 ) ) : $id;
        }, 10, 2 );
        add_filter( 'wp_authenticate_user', function ( $user ) {
            if ( ! is_wp_error( $user ) && in_array( get_user_meta( $user->ID, 'ftuy_account_status', true ), array( 'email_pending', 'legacy_pending' ), true ) ) { return new WP_Error( 'pending', 'Confirmá tu cuenta mediante el enlace del correo antes de ingresar.' ); }
            return $user;
        } );
        add_filter( 'wp_is_application_passwords_available_for_user', function ( $available, $user ) { return in_array( get_user_meta( $user->ID, 'ftuy_account_status', true ), array( 'email_pending', 'legacy_pending' ), true ) ? false : $available; }, 10, 2 );
        add_action( 'after_password_reset', function ( $user ) {
            if ( in_array( get_user_meta( $user->ID, 'ftuy_account_status', true ), array( 'email_pending', 'legacy_pending' ), true ) ) { update_user_meta( $user->ID, 'ftuy_account_status', 'active' ); }
        } );
        add_filter( 'show_admin_bar', function ( $show ) { return self::subscriber() ? false : $show; } );
        add_action( 'admin_init', function () {
            if ( self::subscriber() && ! wp_doing_ajax() && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) { wp_safe_redirect( home_url( '/mi-cuenta/' ) ); exit; }
        } );
        add_action( 'admin_menu', function () {
            if ( self::local() ) { add_users_page( 'Correos de cuentas', 'Correos de cuentas', 'manage_options', 'ftuy-account-mail', array( __CLASS__, 'mail_page' ) ); }
        } );
        $show_legacy = function ( $user ) {
            if ( ! current_user_can( 'manage_options' ) ) { return; }
            echo '<h2>Foodtrucks Uruguay</h2><p>ID histórico de la app: <strong>' . esc_html( get_user_meta( $user->ID, self::LEGACY_META, true ) ?: 'Sin asignar' ) . '</strong></p><p>Campo interno asignado por la migración. No se modifica desde el perfil ni desde la API pública.</p>';
        };
        add_action( 'show_user_profile', $show_legacy ); add_action( 'edit_user_profile', $show_legacy );
        add_action( 'rest_api_init', function () {
            foreach ( array( 'register', 'forgot-password', 'reactivate' ) as $action ) {
                register_rest_route( 'foodtrucks-uy/v1', '/accounts/' . $action, array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => function ( $request ) use ( $action ) {
                    $result = self::request( $action, $request['email'], $request['name'] ?? '' );
                    return is_wp_error( $result ) ? $result : new WP_REST_Response( array( 'message' => self::confirmation() ), 202 );
                } ) );
            }
        } );
    }
    public static function subscriber() { return is_user_logged_in() && ! current_user_can( 'manage_options' ) && in_array( 'subscriber', wp_get_current_user()->roles, true ); }
    public static function confirmation() { return 'Si corresponde, te enviamos un correo con el enlace para continuar. Revisá también la carpeta de spam.'; }
    public static function login_url( $target = '' ) { return add_query_arg( 'redirect_to', $target ?: home_url( '/mi-cuenta/' ), home_url( '/ingresar/' ) ); }
    public static function target( $raw ) {
        $fallback = home_url( '/mi-cuenta/' );
        if ( ! is_string( $raw ) ) { return $fallback; }
        $url = wp_validate_redirect( wp_unslash( $raw ), $fallback );
        if ( wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) || wp_parse_url( $url, PHP_URL_PORT ) !== wp_parse_url( home_url(), PHP_URL_PORT ) || wp_parse_url( $url, PHP_URL_SCHEME ) !== wp_parse_url( home_url(), PHP_URL_SCHEME ) ) { return $fallback; }
        foreach ( array( 'mi-cuenta', 'mis-eventos', 'mis-foodtrucks', 'sugerir-evento', 'agregar-foodtruck' ) as $path ) { if ( wp_parse_url( $url, PHP_URL_PATH ) === wp_parse_url( home_url( '/' . $path . '/' ), PHP_URL_PATH ) ) { return $url; } }
        return $fallback;
    }
    public static function limited( $bucket, $limit, $seconds ) {
        $key = 'ftuy_ac_' . hash_hmac( 'sha256', $bucket, wp_salt( 'auth' ) );
        $count = (int) get_transient( $key ); if ( $count >= $limit ) { return true; }
        set_transient( $key, $count + 1, $seconds ); return false;
    }
    public static function request( $action, $email, $name = '' ) {
        if ( ! in_array( $action, array( 'register', 'forgot-password', 'reactivate' ), true ) || ! is_string( $email ) || ! is_string( $name ) || ! is_email( $email ) || strlen( $email ) > 100 || strlen( $name ) > 200 ) { return new WP_Error( 'fields', 'Ingresá un email válido.', array( 'status' => 400 ) ); }
        $email = strtolower( trim( $email ) ); $name = sanitize_text_field( $name );
        if ( $action === 'register' && ( ! self::registration_enabled() || ! $name ) ) { return new WP_Error( 'register', self::registration_enabled() ? 'Ingresá tu nombre.' : 'El registro todavía no está habilitado.', array( 'status' => 400 ) ); }
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if ( self::limited( 'request-ip-' . $ip, 10, HOUR_IN_SECONDS ) ) { return new WP_Error( 'rate', 'Hubo varios intentos. Probá nuevamente más tarde.', array( 'status' => 429 ) ); }
        // A per-email limit returns the same confirmation to avoid disclosing accounts.
        if ( self::limited( 'request-email-' . $email, 3, HOUR_IN_SECONDS ) ) { return true; }
        $user = get_user_by( 'email', $email );
        if ( $action === 'register' && ! $user ) {
            $id = wp_insert_user( array( 'user_login' => 'ft_' . str_replace( '-', '', wp_generate_uuid4() ), 'user_email' => $email, 'display_name' => $name, 'user_pass' => wp_generate_password( 40 ), 'role' => 'subscriber', 'meta_input' => array( 'ftuy_account_status' => 'email_pending' ) ) );
            if ( is_wp_error( $id ) ) { return new WP_Error( 'register', 'No pudimos completar la solicitud. Probá nuevamente.', array( 'status' => 500 ) ); }
            $user = get_user_by( 'id', $id );
        }
        if ( ! $user ) { return true; }
        $state = get_user_meta( $user->ID, 'ftuy_account_status', true );
        if ( $action === 'register' && $state !== 'email_pending' ) { return true; }
        if ( $action === 'reactivate' && ( $state !== 'legacy_pending' || ! get_user_meta( $user->ID, self::LEGACY_META, true ) ) ) { return true; }
        $key = get_password_reset_key( $user ); if ( is_wp_error( $key ) ) { return true; }
        $url = add_query_arg( array( 'key' => $key, 'login' => $user->user_login ), home_url( '/elegir-contrasena/' ) );
        $subject = $action === 'register' ? 'Confirmá tu cuenta de Foodtrucks Uruguay' : ( $action === 'reactivate' ? 'Reactivá tu cuenta de Foodtrucks Uruguay' : 'Elegí una nueva contraseña · Foodtrucks Uruguay' );
        $body = "Para confirmar tu email y elegir tu contraseña, abrí este enlace:\n\n" . $url . "\n\nEl enlace vence y solo puede usarse una vez. Si no lo solicitaste, ignorá este mensaje.";
        if ( self::local() ) {
            $mail = get_option( 'ftuy_account_mail_local', array() ); $mail[] = array( 'recipient' => $email, 'subject' => $subject, 'message' => $body, 'created_at' => current_time( 'mysql', true ) );
            update_option( 'ftuy_account_mail_local', array_slice( $mail, -100 ), false );
        } elseif ( ! wp_mail( $email, $subject, $body ) ) {
            // Never log a reset token in production; allow retry via the same screen.
            return new WP_Error( 'mail', 'No pudimos enviar el correo. Probá nuevamente más tarde.', array( 'status' => 503 ) );
        }
        return true;
    }
    public static function set_legacy_id( $user_id, $legacy_id ) {
        if ( ! current_user_can( 'manage_options' ) || ! get_user_by( 'id', $user_id ) || ! is_scalar( $legacy_id ) || ! ctype_digit( (string) $legacy_id ) || (int) $legacy_id < 1 ) { return new WP_Error( 'legacy', 'Asignación histórica inválida o sin permiso.' ); }
        $legacy_id = (int) $legacy_id; $current = get_user_meta( $user_id, self::LEGACY_META, true );
        if ( $current && (int) $current !== $legacy_id ) { return new WP_Error( 'legacy', 'La cuenta ya tiene otro ID histórico.' ); }
        $owners = get_users( array( 'meta_key' => self::LEGACY_META, 'meta_value' => $legacy_id, 'fields' => 'ID' ) );
        foreach ( $owners as $owner ) { if ( (int) $owner !== (int) $user_id ) { return new WP_Error( 'legacy', 'Ese ID histórico ya está asignado.' ); } }
        $claim = 'ftuy_legacy_owner_' . $legacy_id;
        if ( ! add_option( $claim, (int) $user_id, '', false ) && (int) get_option( $claim ) !== (int) $user_id ) { return new WP_Error( 'legacy', 'Ese ID histórico ya está asignado.' ); }
        update_user_meta( $user_id, self::LEGACY_META, $legacy_id );
        if ( (int) get_user_meta( $user_id, self::LEGACY_META, true ) !== $legacy_id ) { if ( ! $current ) { delete_option( $claim ); } return new WP_Error( 'legacy', 'No se pudo guardar el vínculo histórico.' ); }
        return true;
    }
    private static function guest_cookie() {
        if ( is_user_logged_in() ) { return; }
        $cookie = $_COOKIE['ftuy_guest'] ?? '';
        self::$guest = is_string( $cookie ) && preg_match( '/^[a-f0-9]{64}$/', $cookie ) ? $cookie : bin2hex( random_bytes( 32 ) );
        if ( $cookie !== self::$guest ) { setcookie( 'ftuy_guest', self::$guest, array( 'expires' => time() + DAY_IN_SECONDS, 'path' => wp_parse_url( home_url( '/' ), PHP_URL_PATH ), 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) ); }
    }
    public static function route() {
        $path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
        $paths = array( 'ingresar' => 'login', 'registro' => 'register', 'recordar-contrasena' => 'forgot-password', 'reactivar-cuenta' => 'reactivate', 'elegir-contrasena' => 'reset', 'mi-cuenta' => 'account', 'salir' => 'logout' );
        foreach ( $paths as $slug => $view ) { if ( untrailingslashit( $path ) === untrailingslashit( wp_parse_url( home_url( '/' . $slug . '/' ), PHP_URL_PATH ) ) ) { self::$view = $view; break; } }
        if ( ! self::$view ) { return; }
        nocache_headers(); header( 'X-Robots-Tag: noindex, nofollow' ); header( 'Referrer-Policy: no-referrer' );
        self::guest_cookie();
        $post = ( $_SERVER['REQUEST_METHOD'] ?? '' ) === 'POST';
        if ( self::$view === 'logout' ) {
            if ( ! is_user_logged_in() || ! is_string( $_GET['_wpnonce'] ?? null ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ftuy_account_logout' ) ) { wp_die( 'Enlace de salida inválido.', '', array( 'response' => 403 ) ); }
            wp_logout(); wp_safe_redirect( home_url( '/ingresar/' ) ); exit;
        }
        if ( is_user_logged_in() && in_array( self::$view, array( 'login', 'register' ), true ) ) { wp_safe_redirect( self::target( $_GET['redirect_to'] ?? '' ) ); exit; }
        if ( $post ) {
            $input = wp_unslash( $_POST ); $nonce = $input['_wpnonce'] ?? '';
            if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'ftuy_account_' . self::$view ) ) { wp_die( 'La sesión del formulario venció. Recargá la página.', '', array( 'response' => 403 ) ); }
            self::$email = is_string( $input['email'] ?? null ) ? trim( $input['email'] ) : ''; self::$name = is_string( $input['name'] ?? null ) ? $input['name'] : '';
            if ( self::$view === 'login' ) {
                if ( self::limited( 'login-' . ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ), 10, 15 * MINUTE_IN_SECONDS ) || self::limited( 'login-email-' . strtolower( self::$email ), 5, 15 * MINUTE_IN_SECONDS ) ) { self::$error = 'Hubo varios intentos. Probá nuevamente más tarde.'; }
                else {
                    $user = wp_signon( array( 'user_login' => self::$email, 'user_password' => is_string( $input['password'] ?? null ) ? $input['password'] : '', 'remember' => ! empty( $input['remember'] ) ), is_ssl() );
                    if ( is_wp_error( $user ) ) { self::$error = 'No pudimos ingresar con esos datos. Revisá email y contraseña, o usá el enlace de recuperación o reactivación.'; }
                    else { wp_set_current_user( $user->ID ); wp_safe_redirect( self::target( $input['redirect_to'] ?? '' ) ); exit; }
                }
            } elseif ( self::$view === 'reset' ) {
                $user = self::reset_user(); $password = $input['password'] ?? ''; $confirm = $input['password_confirm'] ?? '';
                if ( is_wp_error( $user ) ) { self::$error = 'El enlace no es válido o venció. Solicitá uno nuevo.'; }
                elseif ( ! is_string( $password ) || strlen( $password ) < 12 || strlen( $password ) > 4096 || $password !== $confirm ) { self::$error = 'Elegí una contraseña de al menos 12 caracteres y repetila igual.'; }
                else { reset_password( $user, $password ); wp_safe_redirect( add_query_arg( 'updated', 1, home_url( '/ingresar/' ) ) ); exit; }
            } elseif ( self::$view === 'account' && is_user_logged_in() ) {
                if ( ! self::$name || strlen( self::$name ) > 200 ) { self::$error = 'Ingresá tu nombre.'; }
                else { wp_update_user( array( 'ID' => get_current_user_id(), 'display_name' => sanitize_text_field( self::$name ) ) ); self::$notice = 'Guardamos tu nombre.'; }
            } elseif ( in_array( self::$view, array( 'register', 'forgot-password', 'reactivate' ), true ) ) {
                $result = self::request( self::$view, self::$email, self::$name );
                if ( is_wp_error( $result ) ) { self::$error = $result->get_error_message(); } else { self::$notice = self::confirmation(); }
            }
        }
        status_header( 200 ); wp_enqueue_style( 'ftuy-events', FTUY_URL . 'assets/events.css', array(), FOODTRUCKS_UY_CORE_VERSION );
        include FTUY_PATH . 'templates/accounts.php'; exit;
    }
    public static function reset_user() {
        $key = $_GET['key'] ?? ''; $login = $_GET['login'] ?? '';
        if ( ! is_string( $key ) || ! is_string( $login ) || strlen( $key ) > 100 || strlen( $login ) > 100 ) { return new WP_Error( 'key', 'Enlace inválido.' ); }
        return check_password_reset_key( wp_unslash( $key ), wp_unslash( $login ) );
    }
    public static function mail_page() {
        if ( ! self::local() || ! current_user_can( 'manage_options' ) ) { return; }
        echo '<div class="wrap"><h1>Correos de cuentas · pruebas locales</h1><p>No se envían emails reales. Estos enlaces permiten probar la confirmación, recuperación y reactivación. Solo administradores pueden verlos.</p>';
        foreach ( array_reverse( get_option( 'ftuy_account_mail_local', array() ) ) as $mail ) {
            echo '<article><h2>' . esc_html( $mail['subject'] ) . '</h2><p>' . esc_html( $mail['recipient'] . ' · ' . $mail['created_at'] ) . '</p><pre style="white-space:pre-wrap">' . esc_html( $mail['message'] ) . '</pre></article>';
        } echo '</div>';
    }
}
