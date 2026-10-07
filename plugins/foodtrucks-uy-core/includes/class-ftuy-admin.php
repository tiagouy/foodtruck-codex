<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Admin {
    public static function init() {
        add_action( 'admin_menu', function () {
            add_menu_page( 'Foodtrucks UY', 'Foodtrucks UY', 'manage_ft_events', 'ftuy-events', array( __CLASS__, 'page' ), 'dashicons-calendar-alt', 26 );
            add_submenu_page( 'ftuy-events', 'Eventos', 'Eventos', 'manage_ft_events', 'ftuy-events', array( __CLASS__, 'page' ) );
            add_submenu_page( 'ftuy-events', 'Correos de eventos', 'Correos', 'manage_ft_events', 'ftuy-mail', array( __CLASS__, 'mail_page' ) );
        } );
        add_action( 'admin_post_ftuy_review', array( __CLASS__, 'handle' ) );
        add_action( 'admin_post_ftuy_retry_mail', function () {
            if ( ! current_user_can( 'manage_ft_events' ) ) { wp_die( 'Sin permiso.', '', array( 'response' => 403 ) ); }
            check_admin_referer( 'ftuy_retry_mail' );
            FTUY_Events::send_mail( absint( $_POST['mail_id'] ?? 0 ) );
            wp_safe_redirect( admin_url( 'admin.php?page=ftuy-mail' ) ); exit;
        } );
        add_action( 'ftuy_retry_mail', array( 'FTUY_Events', 'send_mail' ) );
    }
    public static function labels() { return array( 'pending' => 'Pendiente', 'corrections' => 'Requiere correcciones', 'published' => 'Publicado', 'rejected' => 'Rechazado', 'superseded' => 'Reemplazado' ); }
    public static function fields( $data = array(), $public = false ) {
        FTUY_Form::assets();
        $fields = array( 'title' => 'Nombre del evento', 'description' => 'Descripción', 'start_date' => 'Fecha de inicio', 'end_date' => 'Fecha de finalización', 'start_time' => 'Hora de apertura (opcional)', 'end_time' => 'Hora de cierre (opcional)', 'venue' => 'Nombre del lugar', 'address' => 'Dirección', 'department' => 'Departamento', 'locality' => 'Localidad', 'instagram' => 'Instagram (@usuario o enlace al perfil)', 'website' => 'Sitio web (opcional)', 'tickets_url' => 'Enlace de entradas', 'organizer' => 'Organizador (opcional)' );
        $entry = $data['entry_type'] ?? '';
        if ( ! $entry ) { $entry = ! empty( $data['tickets_url'] ) ? 'paid' : ( empty( $data['id'] ) || preg_match( '/gratis|free|gratuit/i', $data['price'] ?? '' ) ? 'free' : '' ); }
        echo '<input type="hidden" name="schedule_json" value="' . esc_attr( ( $data['schedule_json'] ?? '' ) ?: '[]' ) . '"><input type="hidden" name="price" value="' . esc_attr( $data['price'] ?? '' ) . '">';
        foreach ( array( 'latitude', 'longitude' ) as $coordinate ) { echo '<input type="hidden" name="' . esc_attr( $coordinate ) . '" value="' . esc_attr( $data[$coordinate] ?? '' ) . '">'; }
        echo '<div class="ft-form-grid">';
        foreach ( $fields as $key => $label ) {
            if ( $key === 'start_time' ) {
                echo '<fieldset class="ft-hours-mode"><legend>Horarios</legend><p>Podés dejarlos vacíos si todavía no están confirmados.</p><label><input type="radio" name="hours_mode" value="same" checked> Mismo horario todos los días</label> <label><input type="radio" name="hours_mode" value="daily"> Horarios distintos por día</label></fieldset><div class="ft-daily-hours" hidden></div>';
            }
            if ( $key === 'address' ) { echo '<div class="ft-place-search"><label>Buscar dirección en Google</label><div id="ft-place-widget"></div><small class="ft-place-status" role="status">Podés completar la dirección manualmente si no encontrás el lugar.</small></div>'; }
            if ( $key === 'tickets_url' ) {
                echo '<fieldset class="ft-entry"><legend>Entrada *</legend><label><input type="radio" name="entry_type" value="free" required ' . checked( $entry, 'free', false ) . '> Gratis</label> <label><input type="radio" name="entry_type" value="paid" required ' . checked( $entry, 'paid', false ) . '> Con entrada</label></fieldset>';
            }
            $value = $data[$key] ?? '';
            $required = in_array( $key, array( 'title', 'description', 'start_date', 'end_date', 'department', 'locality', 'address' ), true ) ? ' required' : '';
            echo '<label class="ft-field ft-field-' . esc_attr( $key ) . '"><span>' . esc_html( $label ) . ( $required ? ' *' : '' ) . '</span>';
            if ( $key === 'description' ) {
                echo '<textarea name="description" rows="9"' . $required . '>' . esc_textarea( $value ) . '</textarea>';
            } elseif ( $key === 'department' ) {
                echo '<select name="department" required><option value="">Seleccioná un departamento</option>';
                foreach ( FTUY_Events::departments() as $dep ) { echo '<option ' . selected( $value, $dep, false ) . '>' . esc_html( $dep ) . '</option>'; }
                echo '</select>';
            } else {
                $type = strpos( $key, '_date' ) !== false ? 'date' : ( strpos( $key, '_time' ) !== false ? 'time' : ( in_array( $key, array( 'website', 'tickets_url' ), true ) ? 'url' : 'text' ) );
                echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '"' . $required . '>';
            }
            echo '</label>';
        }
        $image_id = absint( $data['image_id'] ?? 0 );
        echo '<label class="ft-field"><span>Imagen principal ' . ( $image_id ? '(opcional: reemplazar)' : '*' ) . '</span><input type="file" name="event_image" accept="image/jpeg,image/png,image/webp"' . ( ! $image_id ? ' required' : '' ) . '><small>JPG, PNG o WebP, máximo 5 MB. Recomendado 4:5; se conserva el afiche completo.</small></label>';
        if ( $image_id ) { echo '<div>' . wp_get_attachment_image( $image_id, 'thumbnail' ) . '</div>'; }
        if ( ! empty( $data['id'] ) ) { echo '<label class="ft-field"><span><input type="checkbox" name="cancelled" value="1" ' . checked( ! empty( $data['cancelled'] ), true, false ) . '> Evento cancelado</span></label>'; }
        echo '</div>';
        if ( ! $public ) { wp_print_scripts( 'ftuy-event-form' ); }
    }
    public static function image( $existing = 0 ) {
        if ( empty( $_FILES['event_image']['name'] ) ) { return $existing; }
        $file = $_FILES['event_image'];
        if ( $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * MB_IN_BYTES ) { return new WP_Error( 'upload', 'La imagen no pudo cargarse o supera los 5 MB.' ); }
        $info = wp_getimagesize( $file['tmp_name'] );
        if ( ! $info || ! in_array( $info['mime'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) || $info[0] * $info[1] > 40000000 ) { return new WP_Error( 'image', 'Usá JPG, PNG o WebP de hasta 40 megapíxeles.' ); }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        return FTUY_Media::store( 'eventos', function () {
            return media_handle_upload( 'event_image', 0, array(), array( 'test_form' => false, 'mimes' => array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) ) );
        } );
    }
    public static function handle() {
        if ( ! current_user_can( 'manage_ft_events' ) ) { wp_die( 'Sin permiso.', '', array( 'response' => 403 ) ); }
        check_admin_referer( 'ftuy_review' );
        global $wpdb;
        $review_id = absint( $_POST['review_id'] ?? 0 );
        $r = FTUY_Events::review( $review_id );
        $event_id = absint( $_POST['event_id'] ?? 0 );
        if ( $r ) {
            if ( ! in_array( $r['status'], array( 'pending', 'corrections' ), true ) ) { wp_die( 'Esta propuesta ya fue revisada.' ); }
            $event_id = $r['event_id'];
            $original = json_decode( $r['payload'], true );
        } else {
            $original = $event_id ? FTUY_Events::get( $event_id ) : array();
            if ( $event_id && ! $original ) { wp_die( 'Evento inexistente.' ); }
            if ( $review_id ) { wp_die( 'Propuesta inexistente.' ); }
        }
        $image = self::image( $original['image_id'] ?? 0 );
        if ( is_wp_error( $image ) ) { wp_die( esc_html( $image->get_error_message() ) ); }
        $input = wp_unslash( $_POST ); $input['image_id'] = $image;
        $data = FTUY_Events::validate( $input );
        if ( is_wp_error( $data ) ) { wp_die( esc_html( $data->get_error_message() ) ); }
        if ( ! $r ) {
            $now = current_time( 'mysql', true );
            if ( ! $event_id ) {
                $created = FTUY_Events::suggest( $data, get_current_user_id() );
                if ( is_wp_error( $created ) ) { wp_die( esc_html( $created->get_error_message() ) ); }
                $review_id = $created;
            } else {
                $wpdb->query( $wpdb->prepare( 'UPDATE ' . FTUY_Events::table( 'event_reviews' ) . " SET status='superseded' WHERE event_id=%d AND status IN ('pending','corrections')", $event_id ) );
                if ( false === $wpdb->insert( FTUY_Events::table( 'event_reviews' ), array( 'event_id' => $event_id, 'author_id' => $original['author_id'], 'payload' => wp_json_encode( $data ), 'note' => '', 'created_at' => $now ) ) ) { wp_die( 'No se pudo guardar.' ); }
                $review_id = $wpdb->insert_id;
            }
        } else {
            $wpdb->update( FTUY_Events::table( 'event_reviews' ), array( 'payload' => wp_json_encode( $data ) ), array( 'id' => $review_id ) );
        }
        $decision = sanitize_key( $_POST['decision'] ?? 'pending' );
        if ( $decision !== 'pending' ) {
            $result = FTUY_Events::moderate( $review_id, $decision, wp_unslash( $_POST['note'] ?? '' ) );
            if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ) ); }
        }
        wp_safe_redirect( admin_url( 'admin.php?page=ftuy-events&saved=1' . ( $decision === 'pending' ? '&review=' . $review_id : '' ) ) ); exit;
    }
    public static function page() {
        if ( ! current_user_can( 'manage_ft_events' ) ) { return; }
        global $wpdb;
        wp_enqueue_style( 'ftuy-events', FTUY_URL . 'assets/events.css', array(), FOODTRUCKS_UY_CORE_VERSION );
        echo '<div class="wrap ft-admin"><h1>Foodtrucks UY · Eventos</h1><p>Administración propia del sitio y la app. Los eventos originales de Eventchamp se conservan.</p>';
        if ( isset( $_GET['saved'] ) ) { echo '<div class="notice notice-success"><p>Revisión guardada.</p></div>'; }
        $r = isset( $_GET['review'] ) ? FTUY_Events::review( absint( $_GET['review'] ) ) : null;
        $event = isset( $_GET['edit'] ) ? FTUY_Events::get( absint( $_GET['edit'] ) ) : null;
        if ( $r || $event || isset( $_GET['new'] ) ) {
            $data = $r ? array_merge( FTUY_Events::get( $r['event_id'] ), json_decode( $r['payload'], true ) ) : ( $event ?: array() );
            echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=ftuy-events' ) ) . '">← Todos los eventos</a></p>';
            if ( $r ) {
                echo '<p>Estado: <strong>' . esc_html( self::labels()[$r['status']] ?? $r['status'] ) . '</strong></p>';
                $preview = wp_nonce_url( add_query_arg( 'ft_preview', $r['id'], home_url( '/eventos/' ) ), 'ft_preview_' . $r['id'] );
                echo '<p><a class="button" target="_blank" href="' . esc_url( $preview ) . '">Abrir vista previa privada</a></p>';
                if ( ! in_array( $r['status'], array( 'pending', 'corrections' ), true ) ) { echo '<p>Esta revisión está cerrada.</p></div>'; return; }
            }
            echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ftuy_review"><input type="hidden" name="review_id" value="' . absint( $r['id'] ?? 0 ) . '"><input type="hidden" name="event_id" value="' . absint( $event['id'] ?? 0 ) . '">';
            wp_nonce_field( 'ftuy_review' ); self::fields( $data );
            echo '<p><label>Mensaje para el autor<br><textarea name="note" rows="3" style="width:100%">' . esc_textarea( $r['note'] ?? '' ) . '</textarea></label></p><p><button class="button" name="decision" value="pending">Guardar para revisar / preview</button> <button class="button button-primary" name="decision" value="published">Aprobar y publicar</button> <button class="button" name="decision" value="corrections">Pedir correcciones</button> <button class="button" name="decision" value="rejected">Rechazar</button></p></form></div>'; return;
        }
        echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=ftuy-events&new=1' ) ) . '">Crear evento</a> <a class="button" href="' . esc_url( home_url( '/eventos/pasados/' ) ) . '">Ver histórico</a></p><h2>Pendientes de revisión</h2><table class="widefat striped"><thead><tr><th>Evento</th><th>Autor</th><th>Estado</th><th>Enviado</th></tr></thead><tbody>';
        $rows = $wpdb->get_results( 'SELECT r.*, e.title FROM ' . FTUY_Events::table( 'event_reviews' ) . ' r JOIN ' . FTUY_Events::table() . " e ON r.event_id=e.id WHERE r.status IN ('pending','corrections') ORDER BY r.created_at DESC LIMIT 100", ARRAY_A );
        foreach ( $rows as $r ) {
            $user = get_userdata( $r['author_id'] ); $payload = json_decode( $r['payload'], true );
            echo '<tr><td><a href="' . esc_url( admin_url( 'admin.php?page=ftuy-events&review=' . $r['id'] ) ) . '">' . esc_html( $payload['title'] ?? $r['title'] ) . '</a></td><td>' . esc_html( $user ? $user->display_name : 'Cuenta histórica' ) . '</td><td>' . esc_html( self::labels()[$r['status']] ) . '</td><td>' . esc_html( $r['created_at'] ) . ' UTC</td></tr>';
        }
        if ( ! $rows ) { echo '<tr><td colspan="4">No hay propuestas pendientes.</td></tr>'; }
        echo '</tbody></table><h2>Eventos propios</h2><table class="widefat striped"><thead><tr><th>Evento</th><th>Fechas</th><th>Departamento</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>';
        $page = max( 1, absint( $_GET['ft_page'] ?? 1 ) );
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Events::table() . ' ORDER BY start_date DESC LIMIT 25 OFFSET %d', ( $page - 1 ) * 25 ), ARRAY_A );
        foreach ( $rows as $e ) {
            echo '<tr><td>' . esc_html( $e['title'] ) . '</td><td>' . esc_html( $e['start_date'] . ' → ' . $e['end_date'] ) . '</td><td>' . esc_html( $e['department'] ) . '</td><td>' . esc_html( self::labels()[$e['status']] ?? $e['status'] ) . ( $e['status'] === 'published' && FTUY_Events::temporal( $e ) === 'past' ? ' · Pasado' : '' ) . '</td><td><a href="' . esc_url( admin_url( 'admin.php?page=ftuy-events&edit=' . $e['id'] ) ) . '">Editar</a>' . ( $e['status'] === 'published' ? ' · <a href="' . esc_url( FTUY_Events::url( $e ) ) . '">Ver</a>' : '' ) . '</td></tr>';
        }
        echo '</tbody></table><p>' . paginate_links( array( 'base' => add_query_arg( 'ft_page', '%#%' ), 'current' => $page, 'total' => max( 1, (int) ceil( $wpdb->get_var( 'SELECT COUNT(*) FROM ' . FTUY_Events::table() ) / 25 ) ) ) ) . '</p></div>';
    }
    public static function mail_page() {
        if ( ! current_user_can( 'manage_ft_events' ) ) { return; }
        global $wpdb;
        echo '<div class="wrap"><h1>Correos de eventos</h1><p>En localhost se capturan aquí sin enviar emails reales. Los fallos en producción se reintentan y permanecen visibles.</p><table class="widefat striped"><tr><th>Destinatario</th><th>Contenido</th><th>Estado</th><th>Acción</th></tr>';
        foreach ( $wpdb->get_results( 'SELECT * FROM ' . FTUY_Events::table( 'event_mail' ) . ' ORDER BY id DESC LIMIT 100', ARRAY_A ) as $row ) {
            echo '<tr><td>' . esc_html( $row['recipient'] ) . '</td><td><strong>' . esc_html( $row['subject'] ) . '</strong><pre style="white-space:pre-wrap">' . esc_html( $row['message'] ) . '</pre></td><td>' . esc_html( $row['status'] ) . ' (' . (int) $row['attempts'] . ')</td><td>';
            if ( in_array( $row['status'], array( 'failed', 'queued' ), true ) ) { echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post"><input type="hidden" name="action" value="ftuy_retry_mail"><input type="hidden" name="mail_id" value="' . (int) $row['id'] . '">'; wp_nonce_field( 'ftuy_retry_mail' ); echo '<button class="button">Reintentar</button></form>'; }
            echo '</td></tr>';
        }
        echo '</table></div>';
    }
}
