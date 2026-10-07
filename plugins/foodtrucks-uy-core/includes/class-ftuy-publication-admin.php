<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Publication_Admin {
    public static function init() {
        add_action( 'admin_menu', function () { add_submenu_page( 'ftuy-events', 'Publicaciones', 'Publicaciones', 'manage_ft_publications', 'ftuy-publications', array( __CLASS__, 'page' ) ); } );
        add_action( 'admin_post_ftuy_publication', array( __CLASS__, 'handle' ) );
        add_action( 'rest_api_init', array( 'FTUY_Publications', 'api' ) );
    }
    public static function handle() {
        if ( ! current_user_can( 'manage_ft_publications' ) ) { wp_die( 'Sin permiso.', '', array( 'response' => 403 ) ); }
        check_admin_referer( 'ftuy_publication' );
        $input = wp_unslash( $_POST );
        foreach ( array( 'publication_id', 'operation' ) as $key ) { if ( ! isset( $input[$key] ) || ! is_string( $input[$key] ) ) { wp_die( 'Datos inválidos.', '', array( 'response' => 400 ) ); } }
        $id = absint( $input['publication_id'] );
        if ( ! $id || ! FTUY_Publications::get( $id ) ) { wp_die( 'Publicación inexistente.', '', array( 'response' => 404 ) ); }
        if ( $input['operation'] === 'report' ) { $result = FTUY_Publications::report( $id, $input['reason'] ?? '' ); }
        elseif ( $input['operation'] === 'resolve' ) {
            if ( ! isset( $input['report_id'] ) || ! is_string( $input['report_id'] ) ) { wp_die( 'Denuncia inválida.' ); }
            $result = FTUY_Publications::resolve( $id, absint( $input['report_id'] ) );
        } elseif ( in_array( $input['operation'], array( 'save', 'publish', 'unpublish' ), true ) ) {
            if ( ! isset( $input['version'] ) || ! is_string( $input['version'] ) || ! ctype_digit( $input['version'] ) ) { wp_die( 'Versión inválida.' ); }
            if ( $input['operation'] === 'publish' ) { $input['status'] = 'published'; }
            if ( $input['operation'] === 'unpublish' ) { $input['status'] = 'unpublished'; }
            $result = FTUY_Publications::edit( $id, $input, (int) $input['version'], $input['note'] ?? '' );
        } else { wp_die( 'Acción inválida.', '', array( 'response' => 400 ) ); }
        if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 400 ) ); }
        wp_safe_redirect( admin_url( 'admin.php?page=ftuy-publications&edit=' . $id . '&saved=1' ) ); exit;
    }
    private static function form_start( $id ) {
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ftuy_publication"><input type="hidden" name="publication_id" value="' . (int) $id . '">'; wp_nonce_field( 'ftuy_publication' );
    }
    public static function page() {
        if ( ! current_user_can( 'manage_ft_publications' ) ) { return; }
        echo '<div class="wrap"><h1>Publicaciones de la comunidad</h1><p>Fotos subidas desde la app. Aquí se editan y moderan; no se suben publicaciones desde el sitio.</p>';
        if ( isset( $_GET['saved'] ) ) { echo '<div class="notice notice-success"><p>Cambios guardados.</p></div>'; }
        $id = isset( $_GET['edit'] ) && is_scalar( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
        if ( $id ) { self::detail( $id ); echo '</div>'; return; }
        $status = isset( $_GET['status'] ) && is_string( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'all';
        $filters = array_merge( array( 'all' => 'Todas' ), FTUY_Publications::labels(), array( 'reported' => 'Denunciadas' ) );
        if ( ! isset( $filters[$status] ) ) { $status = 'all'; }
        echo '<p>';
        foreach ( $filters as $key => $label ) { echo '<a class="button' . ( $key === $status ? ' button-primary' : '' ) . '" href="' . esc_url( add_query_arg( array( 'page' => 'ftuy-publications', 'status' => $key ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( $label ) . '</a> '; }
        echo '</p>';
        $page = isset( $_GET['paged'] ) && is_scalar( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $list = FTUY_Publications::listing( $status, $page );
        echo '<p>' . (int) $list['total'] . ' publicaciones.</p><table class="widefat striped"><thead><tr><th>Foto</th><th>Texto / ubicación</th><th>Autor</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead><tbody>';
        foreach ( $list['items'] as $row ) {
            $user = get_user_by( 'id', $row['author_user_id'] );
            $legacy_label = $row['legacy_id'] ? ' · ID anterior: ' . (int) $row['legacy_id'] : '';
            echo '<tr><td>' . wp_get_attachment_image( $row['image_id'], array( 80, 80 ) ) . '</td><td>' . esc_html( wp_trim_words( $row['caption'], 20 ) ) . '<br><small>' . esc_html( $row['address'] . $legacy_label ) . '</small></td><td>' . esc_html( $user ? $user->display_name : 'Usuario no disponible' ) . '</td><td>' . esc_html( FTUY_Publications::labels()[$row['status']] ?? $row['status'] ) . '</td><td>' . esc_html( $row['created_at'] ) . '</td><td><a class="button" href="' . esc_url( admin_url( 'admin.php?page=ftuy-publications&edit=' . $row['id'] ) ) . '">Ver / moderar</a></td></tr>';
        }
        if ( ! $list['items'] ) { echo '<tr><td colspan="6">No hay publicaciones en este filtro.</td></tr>'; }
        echo '</tbody></table><p>';
        if ( $page > 1 ) { echo '<a class="button" href="' . esc_url( add_query_arg( array( 'page' => 'ftuy-publications', 'status' => $status, 'paged' => $page - 1 ), admin_url( 'admin.php' ) ) ) . '">Anterior</a> '; }
        if ( $page * 20 < $list['total'] ) { echo '<a class="button" href="' . esc_url( add_query_arg( array( 'page' => 'ftuy-publications', 'status' => $status, 'paged' => $page + 1 ), admin_url( 'admin.php' ) ) ) . '">Siguiente</a>'; }
        echo '</p></div>';
    }
    private static function detail( $id ) {
        $row = FTUY_Publications::get( $id ); if ( ! $row ) { echo '<p>Publicación inexistente.</p>'; return; }
        $user = get_user_by( 'id', $row['author_user_id'] );
        echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=ftuy-publications' ) ) . '">← Todas las publicaciones</a></p><h2>Publicación #' . (int) $id . ' · ' . esc_html( FTUY_Publications::labels()[$row['status']] ) . '</h2><p>Autor: ' . esc_html( $user ? $user->display_name : 'Usuario no disponible' ) . ' · Fecha: ' . esc_html( $row['created_at'] ) . ( $row['legacy_id'] ? ' (original; zona horaria no comprobada)' : ' UTC' ) . '</p>';
        if ( $row['legacy_id'] ) { echo '<p>ID de publicación anterior: ' . (int) $row['legacy_id'] . '</p>'; }
        if ( $row['status'] === 'published' ) { echo '<p><a class="button" href="' . esc_url( FTUY_Publication_Public::url( $row ) ) . '">Ver enlace público</a></p>'; }
        echo '<div style="max-width:480px">' . wp_get_attachment_image( $row['image_id'], 'medium_large', false, array( 'style' => 'max-width:100%;height:auto' ) ) . '</div>';
        self::form_start( $id );
        echo '<input type="hidden" name="version" value="' . (int) $row['version'] . '"><p><label>Texto<br><textarea class="large-text" rows="5" name="caption" maxlength="10000">' . esc_textarea( $row['caption'] ) . '</textarea></label></p><p><label>Ubicación / dirección<br><input class="large-text" name="address" maxlength="255" value="' . esc_attr( $row['address'] ) . '"></label></p>';
        foreach ( array( 'latitude' => 'Latitud', 'longitude' => 'Longitud' ) as $key => $label ) { echo '<p><label>' . esc_html( $label ) . ' <input type="number" step="any" name="' . esc_attr( $key ) . '" value="' . esc_attr( $row[$key] ?? '' ) . '"></label></p>'; }
        echo '<p><label>Estado <select name="status">'; foreach ( FTUY_Publications::labels() as $key => $label ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( $row['status'], $key, false ) . '>' . esc_html( $label ) . '</option>'; }
        echo '</select></label></p><p><label>Nota interna / motivo de moderación<br><textarea class="large-text" name="note" rows="2" maxlength="3000"></textarea></label></p><p><button class="button button-primary" name="operation" value="save">Guardar cambios</button> ';
        if ( $row['status'] === 'published' ) { echo '<button class="button" name="operation" value="unpublish">Despublicar</button>'; } else { echo '<button class="button" name="operation" value="publish">Publicar</button>'; }
        echo '</p></form><p>Despublicar la oculta de la API pública, también del listado por autor. No elimina la foto ni cambia su propietario. El archivo directo y copias descargadas no se retiran automáticamente.</p><h2>Denuncias</h2><p>Podés registrar aquí una denuncia recibida por email. El registro no despublica automáticamente: la decisión se toma con el control de estado de arriba.</p>';
        self::form_start( $id ); echo '<input type="hidden" name="operation" value="report"><p><textarea class="large-text" name="reason" rows="2" maxlength="3000" placeholder="Motivo de la denuncia" required></textarea></p><button class="button">Registrar denuncia</button></form>';
        global $wpdb;
        $reports = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Publications::table( 'publication_reports' ) . ' WHERE publication_id=%d ORDER BY id DESC LIMIT 100', $id ), ARRAY_A );
        foreach ( $reports as $report ) {
            echo '<div><p><strong>' . esc_html( $report['status'] === 'open' ? 'Pendiente de revisión' : 'Revisada' ) . '</strong> · ' . esc_html( $report['created_at'] ) . ' UTC</p><p>' . nl2br( esc_html( $report['reason'] ) ) . '</p>';
            if ( $report['status'] === 'open' ) { self::form_start( $id ); echo '<input type="hidden" name="operation" value="resolve"><input type="hidden" name="report_id" value="' . (int) $report['id'] . '"><button class="button">Marcar como revisada</button></form>'; } echo '</div>';
        }
        echo '<h2>Historial de moderación</h2><table class="widefat striped"><thead><tr><th>Fecha (UTC)</th><th>Administrador</th><th>Acción</th><th>Nota</th></tr></thead><tbody>';
        $audit = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Publications::table( 'publication_audit' ) . ' WHERE publication_id=%d ORDER BY id DESC LIMIT 50', $id ), ARRAY_A );
        $labels = array( 'create' => 'Registro inicial', 'edit' => 'Edición / cambio de estado', 'report' => 'Denuncia registrada', 'resolve_report' => 'Denuncia revisada', 'import_legacy' => 'Importación histórica' );
        foreach ( $audit as $entry ) { $actor = get_user_by( 'id', $entry['actor_user_id'] ); echo '<tr><td>' . esc_html( $entry['created_at'] ) . '</td><td>' . esc_html( $actor ? $actor->display_name : 'Usuario no disponible' ) . '</td><td>' . esc_html( $labels[$entry['action']] ?? $entry['action'] ) . '</td><td>' . esc_html( $entry['note'] ) . '</td></tr>'; }
        echo '</tbody></table>';
    }
}
