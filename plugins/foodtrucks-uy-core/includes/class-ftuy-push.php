<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Push {
    const ENDPOINT = 'https://useful-media-push.org/pushv3/api/';
    public static function table() { global $wpdb; return $wpdb->prefix . 'ft_push_sends'; }
    public static function config() {
        $key = trim( (string) getenv( 'PUSH_API_KEY' ) );
        $token = trim( (string) getenv( 'FTUY_PUSH_TOKEN_APP' ) );
        return $key && $token ? array( 'key' => $key, 'tokenApp' => $token, 'idapp' => 12 ) : null;
    }
    public static function init() {
        if ( get_option( 'ftuy_push_schema' ) !== '1' ) { self::install(); }
        add_action( 'admin_menu', function () {
            add_submenu_page( 'ftuy-events', 'Notificaciones', 'Notificaciones', 'manage_options', 'ftuy-push', array( __CLASS__, 'page' ) );
        } );
        add_action( 'admin_post_ftuy_push_send', array( __CLASS__, 'handle' ) );
    }
    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table(); $charset = $wpdb->get_charset_collate();
        dbDelta( "CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            request_id varchar(36) NOT NULL,
            author_id bigint(20) unsigned NOT NULL,
            title varchar(120) NOT NULL,
            message text NOT NULL,
            audience varchar(20) NOT NULL,
            users varchar(500) NOT NULL DEFAULT '',
            status varchar(24) NOT NULL,
            success_count int unsigned NOT NULL DEFAULT 0,
            failure_count int unsigned NOT NULL DEFAULT 0,
            total_count int unsigned NOT NULL DEFAULT 0,
            provider varchar(24) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY request_id (request_id)
        ) ENGINE=InnoDB $charset;" );
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) { update_option( 'ftuy_push_schema', '1', false ); }
    }
    // A unique request is claimed before contacting the provider. Never retry uncertain sends automatically.
    public static function send( $input ) {
        global $wpdb;
        if ( ! current_user_can( 'manage_options' ) ) { return new WP_Error( 'permission', 'Sin permiso.' ); }
        $config = self::config();
        if ( ! $config ) { return new WP_Error( 'config', 'Faltan variables de entorno en PHP.' ); }
        foreach ( array( 'title', 'message', 'audience', 'users', 'request_id' ) as $field ) {
            if ( isset( $input[$field] ) && ! is_string( $input[$field] ) ) { return new WP_Error( 'input', 'Datos inválidos.' ); }
        }
        $title = trim( sanitize_text_field( $input['title'] ?? '' ) );
        $message = trim( sanitize_textarea_field( $input['message'] ?? '' ) );
        $audience = $input['audience'] ?? ''; $users = trim( $input['users'] ?? '' ); $uuid = $input['request_id'] ?? '';
        if ( ! $title || ! $message || mb_strlen( $title ) > 120 || mb_strlen( $message ) > 2000 || ! preg_match( '/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/i', $uuid ) ) { return new WP_Error( 'input', 'Completá título (hasta 120) y mensaje (hasta 2000).' ); }
        if ( ! in_array( $audience, array( 'all', 'users' ), true ) ) { return new WP_Error( 'input', 'Elegí el destino.' ); }
        if ( $audience === 'all' && ( $input['confirm_all'] ?? '' ) !== 'yes' ) { return new WP_Error( 'confirm', 'Confirmá el envío a todos los dispositivos activos.' ); }
        if ( $audience === 'users' ) {
            if ( strlen( $users ) > 500 || ! preg_match( '/^[1-9][0-9]*(?:\s*,\s*[1-9][0-9]*)*$/', $users ) ) { return new WP_Error( 'users', 'Indicá IDs de WordPress separados por comas; sin 0.' ); }
            $ids = array_unique( array_map( 'absint', explode( ',', $users ) ) );
            foreach ( $ids as $id ) { if ( ! get_userdata( $id ) ) { return new WP_Error( 'users', 'Uno de los usuarios no existe.' ); } }
            $users = implode( ',', $ids );
        } else { $users = ''; }
        $table = self::table();
        $claimed = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $table (request_id,author_id,title,message,audience,users,status,created_at) VALUES (%s,%d,%s,%s,%s,%s,'sending',%s)", $uuid, get_current_user_id(), $title, $message, $audience, $users, current_time( 'mysql', true ) ) );
        if ( $claimed !== 1 ) { return new WP_Error( 'duplicate', 'Este envío ya fue iniciado o no pudo registrarse. Revisá el historial; no se reenviará.' ); }
        $id = $wpdb->insert_id;
        $body = array_merge( $config, array( 'metodo' => 'send', 'titulo' => $title, 'mensaje' => $message ) );
        if ( $audience === 'users' ) { $body['users'] = $users; }
        $response = wp_remote_post( self::ENDPOINT, array( 'timeout' => 30, 'redirection' => 0, 'sslverify' => true, 'body' => $body ) );
        $status = 'unknown'; $counts = array();
        if ( ! is_wp_error( $response ) ) {
            $code = wp_remote_retrieve_response_code( $response ); $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( $code >= 200 && $code < 300 && is_array( $data ) && isset( $data['success'], $data['failure'], $data['total'], $data['provider'] ) && $data['provider'] === 'fcm_v1' ) {
                $valid = true;
                foreach ( array( 'success', 'failure', 'total' ) as $key ) { if ( ! is_int( $data[$key] ) || $data[$key] < 0 ) { $valid = false; } }
                if ( $valid && $data['success'] + $data['failure'] === $data['total'] ) {
                    $status = $data['failure'] ? 'partial_or_failed' : 'accepted';
                    $counts = array( 'success_count' => $data['success'], 'failure_count' => $data['failure'], 'total_count' => $data['total'], 'provider' => 'fcm_v1' );
                }
            } elseif ( in_array( $code, array( 400, 401, 403, 404, 405 ), true ) ) { $status = 'rejected'; }
        }
        // Do not persist raw responses, tokens, credentials or provider error bodies.
        if ( $wpdb->update( $table, array_merge( array( 'status' => $status ), $counts ), array( 'id' => $id ) ) === false ) { return new WP_Error( 'history', 'El envío fue iniciado pero no se pudo actualizar el historial. Consultá pushv3 antes de repetir.' ); }
        return $id;
    }
    public static function handle() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Sin permiso.', '', array( 'response' => 403 ) ); }
        check_admin_referer( 'ftuy_push_send' );
        $result = self::send( wp_unslash( $_POST ) );
        if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), 'Notificaciones', array( 'back_link' => true ) ); }
        wp_safe_redirect( admin_url( 'admin.php?page=ftuy-push' ) ); exit;
    }
    public static function page() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        global $wpdb; $configured = (bool) self::config();
        echo '<div class="wrap"><h1>Notificaciones</h1><p>Foodtrucks Uruguay · pushv3 · app 12. Configuración del servidor: <strong>' . ( $configured ? 'disponible' : 'no disponible' ) . '</strong>.</p><p>Envía un aviso que abre la app. No incluye todavía buzón ni programación.</p>';
        if ( $configured ) {
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
            wp_nonce_field( 'ftuy_push_send' );
            echo '<input type="hidden" name="action" value="ftuy_push_send"><input type="hidden" name="request_id" value="' . esc_attr( wp_generate_uuid4() ) . '">';
            echo '<p><label>Título<br><input class="regular-text" name="title" maxlength="120" required></label></p><p><label>Mensaje<br><textarea class="large-text" name="message" rows="4" maxlength="2000" required></textarea></label></p>';
            echo '<fieldset><legend><strong>Destino</strong></legend><p><label><input type="radio" name="audience" value="users" checked> Prueba / usuarios específicos</label> <label><input type="radio" name="audience" value="all"> Todos los dispositivos activos</label></p><p><label>IDs de usuarios WordPress separados por comas<br><input class="regular-text" name="users" maxlength="500" inputmode="numeric"></label></p><p>Para probar: usá el ID de tu cuenta con sesión iniciada en la app. Si no tiene dispositivos activos, no recibirá el aviso.</p><p><label><input type="checkbox" name="confirm_all" value="yes"> Confirmo el envío general a todos los dispositivos activos, incluidos quienes no iniciaron sesión.</label></p></fieldset>';
            submit_button( 'Enviar notificación' ); echo '</form>';
        } else { echo '<p>Configurar PUSH_API_KEY y FTUY_PUSH_TOKEN_APP como variables de entorno del proceso PHP. No pegar claves en el panel.</p>'; }
        echo '<h2>Últimos envíos</h2><p>“Aceptado” significa aceptado por Firebase, no confirmación de lectura. Ante un resultado incierto, consultá pushv3 antes de repetir.</p><table class="widefat striped"><thead><tr><th>Fecha UTC</th><th>Aviso</th><th>Destino</th><th>Estado</th><th>FCM: aceptados / fallidos / total</th></tr></thead><tbody>';
        $labels = array( 'sending' => 'En proceso / comprobar pushv3', 'unknown' => 'Resultado incierto', 'accepted' => 'Aceptado', 'partial_or_failed' => 'Con fallos', 'rejected' => 'Rechazado' );
        foreach ( $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY id DESC LIMIT 30', ARRAY_A ) as $row ) {
            echo '<tr><td>' . esc_html( $row['created_at'] ) . '</td><td><strong>' . esc_html( $row['title'] ) . '</strong><br>' . nl2br( esc_html( $row['message'] ) ) . '</td><td>' . esc_html( $row['audience'] === 'all' ? 'Todos' : 'Usuarios: ' . $row['users'] ) . '</td><td>' . esc_html( $labels[$row['status']] ?? $row['status'] ) . '</td><td>' . esc_html( $row['provider'] ? "{$row['success_count']} / {$row['failure_count']} / {$row['total_count']} · {$row['provider']}" : 'Sin resultado confirmado' ) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
}
