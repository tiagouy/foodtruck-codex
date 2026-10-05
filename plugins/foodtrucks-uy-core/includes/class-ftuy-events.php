<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Events {
    public static function table( $name = 'events' ) {
        global $wpdb;
        return $wpdb->prefix . 'ft_' . $name;
    }

    public static function departments() {
        return array( 'Artigas', 'Canelones', 'Cerro Largo', 'Colonia', 'Durazno', 'Flores', 'Florida', 'Lavalleja', 'Maldonado', 'Montevideo', 'Paysandú', 'Río Negro', 'Rivera', 'Rocha', 'Salto', 'San José', 'Soriano', 'Tacuarembó', 'Treinta y Tres' );
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $events = self::table();
        $reviews = self::table( 'event_reviews' );
        $mail = self::table( 'event_mail' );
        dbDelta( "CREATE TABLE $events (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            legacy_post_id bigint(20) unsigned DEFAULT NULL,
            slug varchar(191) NOT NULL,
            title varchar(255) NOT NULL,
            summary text NOT NULL,
            description longtext NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            start_time varchar(5) NOT NULL DEFAULT '',
            end_time varchar(5) NOT NULL DEFAULT '',
            schedule_json longtext NOT NULL,
            entry_type varchar(12) NOT NULL DEFAULT '',
            department varchar(64) NOT NULL DEFAULT '',
            locality varchar(191) NOT NULL DEFAULT '',
            venue varchar(255) NOT NULL DEFAULT '',
            address varchar(255) NOT NULL DEFAULT '',
            latitude decimal(10,7) DEFAULT NULL,
            longitude decimal(10,7) DEFAULT NULL,
            organizer varchar(255) NOT NULL DEFAULT '',
            website varchar(500) NOT NULL DEFAULT '',
            instagram varchar(500) NOT NULL DEFAULT '',
            tickets_url varchar(500) NOT NULL DEFAULT '',
            price varchar(191) NOT NULL DEFAULT '',
            image_id bigint(20) unsigned NOT NULL DEFAULT 0,
            author_id bigint(20) unsigned NOT NULL DEFAULT 0,
            status varchar(24) NOT NULL DEFAULT 'pending',
            cancelled tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            published_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            UNIQUE KEY legacy_post_id (legacy_post_id),
            KEY listing (status,end_date,start_date),
            KEY department_listing (department,status,end_date),
            KEY author (author_id)
        ) ENGINE=InnoDB $charset;" );
        dbDelta( "CREATE TABLE $reviews (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL,
            author_id bigint(20) unsigned NOT NULL,
            payload longtext NOT NULL,
            status varchar(24) NOT NULL DEFAULT 'pending',
            note text NOT NULL,
            reviewer_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            reviewed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY event_status (event_id,status),
            KEY queue (status,created_at)
        ) ENGINE=InnoDB $charset;" );
        dbDelta( "CREATE TABLE $mail (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            recipient varchar(255) NOT NULL,
            subject varchar(255) NOT NULL,
            message text NOT NULL,
            status varchar(24) NOT NULL DEFAULT 'queued',
            attempts int NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY queue (status)
        ) ENGINE=InnoDB $charset;" );
        foreach ( array( $events, $reviews, $mail ) as $table ) {
            if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) { return; }
        }
        if ( $role = get_role( 'administrator' ) ) { $role->add_cap( 'manage_ft_events' ); }
        if ( ! $wpdb->get_var( "SHOW COLUMNS FROM $events LIKE 'instagram'" ) ) { return; }
        // Completar enlaces omitidos en 0.2.0 sin reemplazar valores editados.
        foreach ( $wpdb->get_results( "SELECT id,legacy_post_id,website,instagram FROM $events WHERE legacy_post_id IS NOT NULL", ARRAY_A ) as $event ) {
            $links = self::legacy_links( $event['legacy_post_id'] ); $changes = array();
            foreach ( $links as $key => $value ) { if ( ! $event[$key] && $value ) { $changes[$key] = $value; } }
            if ( $changes ) { $wpdb->update( $events, $changes, array( 'id' => $event['id'] ) ); }
        }
        if ( ! $wpdb->get_var( "SHOW COLUMNS FROM $events LIKE 'schedule_json'" ) || ! $wpdb->get_var( "SHOW COLUMNS FROM $events LIKE 'entry_type'" ) ) { return; }
        update_option( 'ftuy_schema_version', '3', false );
    }

    public static function now() { return new DateTimeImmutable( 'now', new DateTimeZone( 'America/Montevideo' ) ); }
    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d', $id ), ARRAY_A );
    }
    public static function by_slug( $slug ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE slug=%s AND status=%s', $slug, 'published' ), ARRAY_A );
    }
    public static function url( $event ) { return home_url( '/evento/' . $event['slug'] . '/' ); }
    public static function temporal( $event ) {
        $now = self::now()->format( 'Y-m-d H:i' );
        if ( $event['end_date'] . ' ' . ( $event['end_time'] ?: '23:59' ) < $now ) { return 'past'; }
        return $event['start_date'] . ' ' . ( $event['start_time'] ?: '00:00' ) <= $now ? 'ongoing' : 'upcoming';
    }
    public static function listing( $view = 'upcoming', $department = '', $page = 1, $per_page = 12 ) {
        global $wpdb;
        $page = max( 1, (int) $page ); $per_page = min( 50, max( 1, (int) $per_page ) );
        $table = self::table();
        $where = "status='published'";
        if ( $department ) { $where .= $wpdb->prepare( ' AND department=%s', $department ); }
        $end = "CONCAT(end_date,' ',IF(end_time='', '23:59', end_time))";
        $where .= $wpdb->prepare( $view === 'past' ? " AND $end < %s" : " AND $end >= %s", self::now()->format( 'Y-m-d H:i' ) );
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE $where" );
        $order = $view === 'past' ? 'end_date DESC,end_time DESC,id DESC' : 'start_date ASC,start_time ASC,id ASC';
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE $where ORDER BY $order LIMIT %d OFFSET %d", $per_page, ( $page - 1 ) * $per_page ), ARRAY_A );
        return array( 'items' => $items, 'total' => $total, 'page' => $page, 'pages' => (int) ceil( $total / $per_page ) );
    }

    public static function validate( $input, $image_required = true ) {
        if ( ! is_array( $input ) ) { return new WP_Error( 'input', 'Datos inválidos.' ); }
        foreach ( $input as $value ) { if ( ! is_scalar( $value ) && $value !== null ) { return new WP_Error( 'input', 'Datos inválidos.' ); } }
        $data = array();
        foreach ( array( 'title', 'summary', 'department', 'locality', 'venue', 'address', 'organizer', 'price', 'start_date', 'end_date', 'start_time', 'end_time' ) as $key ) { $data[$key] = sanitize_text_field( $input[$key] ?? '' ); }
        $data['description'] = wp_kses_post( $input['description'] ?? '' );
        foreach ( array( 'website', 'tickets_url' ) as $key ) { $data[$key] = esc_url_raw( $input[$key] ?? '', array( 'http', 'https' ) ); }
        $instagram = self::instagram_url( $input['instagram'] ?? '' );
        if ( is_wp_error( $instagram ) ) { return $instagram; }
        $data['instagram'] = $instagram;
        $data['cancelled'] = empty( $input['cancelled'] ) ? 0 : 1;
        foreach ( array( 'title' => 255, 'summary' => 1200, 'description' => 50000, 'locality' => 191, 'venue' => 255, 'address' => 255, 'organizer' => 255, 'price' => 191, 'website' => 500, 'tickets_url' => 500 ) as $key => $max ) {
            $length = function_exists( 'mb_strlen' ) ? mb_strlen( $data[$key] ) : strlen( $data[$key] );
            if ( $length > $max ) { return new WP_Error( 'length', 'Un campo supera el largo permitido (' . $key . ').' ); }
        }
        $data['image_id'] = absint( $input['image_id'] ?? 0 );
        foreach ( array( 'latitude' => 90, 'longitude' => 180 ) as $key => $max ) {
            $v = $input[$key] ?? '';
            if ( $v !== '' && ( ! is_numeric( $v ) || abs( (float) $v ) > $max ) ) { return new WP_Error( 'coordinates', 'Las coordenadas no son válidas.' ); }
            $data[$key] = $v === '' ? null : (float) $v;
        }
        foreach ( array( 'title', 'description', 'department', 'locality', 'address' ) as $key ) {
            if ( ! trim( wp_strip_all_tags( $data[$key] ) ) ) { return new WP_Error( 'required', 'Completá nombre, descripción, departamento, localidad y dirección.' ); }
        }
        if ( ! in_array( $data['department'], self::departments(), true ) ) { return new WP_Error( 'department', 'Elegí un departamento válido.' ); }
        foreach ( array( 'start_date', 'end_date' ) as $key ) {
            $date = DateTimeImmutable::createFromFormat( '!Y-m-d', $data[$key] );
            if ( ! $date || $date->format( 'Y-m-d' ) !== $data[$key] ) { return new WP_Error( 'date', 'Las fechas no son válidas.' ); }
        }
        foreach ( array( 'start_time', 'end_time' ) as $key ) {
            if ( $data[$key] && ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $data[$key] ) ) { return new WP_Error( 'time', 'Los horarios no son válidos.' ); }
        }
        if ( $data['end_date'] < $data['start_date'] || ( $data['end_date'] === $data['start_date'] && $data['start_time'] && $data['end_time'] && $data['end_time'] < $data['start_time'] ) ) { return new WP_Error( 'range', 'El final debe ser posterior al inicio.' ); }
        if ( strlen( $input['schedule_json'] ?? '' ) > 50000 ) { return new WP_Error( 'schedule', 'Los horarios superan el largo permitido.' ); }
        $schedule = json_decode( ( $input['schedule_json'] ?? '' ) ?: '[]', true );
        if ( ! is_array( $schedule ) || count( $schedule ) > 366 ) { return new WP_Error( 'schedule', 'Los horarios por día no son válidos.' ); }
        $clean = array(); $seen = array();
        foreach ( $schedule as $row ) {
            if ( ! is_array( $row ) || ! isset( $row['date'], $row['start'], $row['end'] ) || ! is_string( $row['date'] ) || ! is_string( $row['start'] ) || ! is_string( $row['end'] ) ) { return new WP_Error( 'schedule', 'Los horarios por día no son válidos.' ); }
            $day = DateTimeImmutable::createFromFormat( '!Y-m-d', $row['date'] );
            if ( ! $day || $day->format( 'Y-m-d' ) !== $row['date'] || $row['date'] < $data['start_date'] || $row['date'] > $data['end_date'] || isset( $seen[$row['date']] ) ) { return new WP_Error( 'schedule', 'Revisá las fechas de los horarios por día.' ); }
            foreach ( array( 'start', 'end' ) as $key ) { if ( $row[$key] !== '' && ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $row[$key] ) ) { return new WP_Error( 'schedule', 'Revisá los horarios por día.' ); } }
            if ( $row['start'] && $row['end'] && $row['end'] < $row['start'] ) { return new WP_Error( 'schedule', 'La hora de cierre debe ser posterior a la de apertura del mismo día.' ); }
            $seen[$row['date']] = true; $clean[] = array_intersect_key( $row, array_flip( array( 'date', 'start', 'end' ) ) );
        }
        usort( $clean, function ( $a, $b ) { return strcmp( $a['date'], $b['date'] ); } );
        $data['schedule_json'] = wp_json_encode( $clean );
        if ( $clean ) {
            $data['start_time'] = ''; $data['end_time'] = '';
            foreach ( $clean as $row ) { if ( $row['date'] === $data['start_date'] ) { $data['start_time'] = $row['start']; } if ( $row['date'] === $data['end_date'] ) { $data['end_time'] = $row['end']; } }
        }
        // Sin clasificación explícita, conservar el precio histórico sin asumir gratuidad.
        $data['entry_type'] = sanitize_key( $input['entry_type'] ?? '' );
        if ( ! in_array( $data['entry_type'], array( '', 'free', 'paid' ), true ) ) { return new WP_Error( 'entry', 'Elegí Gratis o Con entrada.' ); }
        if ( $data['entry_type'] === 'paid' && ! $data['tickets_url'] ) { return new WP_Error( 'tickets', 'Agregá el enlace de entradas.' ); }
        if ( $data['entry_type'] === 'free' ) { $data['tickets_url'] = ''; $data['price'] = 'Gratis'; }
        if ( $data['entry_type'] === 'paid' && preg_match( '/^(gratis|free|gratuito)$/i', $data['price'] ) ) { $data['price'] = ''; }
        if ( $image_required && ! wp_attachment_is_image( $data['image_id'] ) ) { return new WP_Error( 'image', 'Cargá una imagen válida para el evento.' ); }
        if ( ! $data['summary'] ) { $data['summary'] = wp_trim_words( wp_strip_all_tags( $data['description'] ), 28, '…' ); }
        return $data;
    }

    public static function suggest( $data, $author_id, $event_id = 0 ) {
        global $wpdb;
        $events = self::table(); $reviews = self::table( 'event_reviews' );
        if ( $event_id ) {
            $existing = self::get( $event_id );
            if ( ! $existing || (int) $existing['author_id'] !== (int) $author_id ) { return new WP_Error( 'owner', 'No podés modificar este evento.' ); }
        }
        $data = self::validate( $data );
        if ( is_wp_error( $data ) ) { return $data; }
        $now = current_time( 'mysql', true );
        $wpdb->query( 'START TRANSACTION' );
        if ( ! $event_id ) {
            $base = sanitize_title( $data['title'] ) ?: 'evento'; $slug = $base; $n = 2;
            while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $events WHERE slug=%s", $slug ) ) || $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type='event' AND post_name=%s", $slug ) ) ) { $slug = $base . '-' . $n++; }
            if ( false === $wpdb->insert( $events, array_merge( $data, array( 'slug' => $slug, 'author_id' => $author_id, 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now ) ) ) ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'save', 'No se pudo guardar el evento.' ); }
            $event_id = $wpdb->insert_id;
        }
        // La versión pública se conserva mientras se revisa la propuesta.
        $wpdb->query( $wpdb->prepare( "UPDATE $reviews SET status='superseded' WHERE event_id=%d AND status IN ('pending','corrections')", $event_id ) );
        if ( false === $wpdb->insert( $reviews, array( 'event_id' => $event_id, 'author_id' => $author_id, 'payload' => wp_json_encode( $data ), 'status' => 'pending', 'note' => '', 'created_at' => $now ) ) ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'save', 'No se pudo enviar la propuesta.' ); }
        $review_id = $wpdb->insert_id;
        $wpdb->query( 'COMMIT' );
        self::queue_mail( get_option( 'admin_email' ), 'Evento pendiente: ' . $data['title'], 'Revisar y abrir vista previa: ' . admin_url( 'admin.php?page=ftuy-events&review=' . $review_id ) );
        $user = get_userdata( $author_id );
        self::queue_mail( $user->user_email, 'Recibimos tu evento', 'Tu propuesta está pendiente de revisión: ' . $data['title'] . '. Estado: ' . home_url( '/mis-eventos/' ) );
        return $review_id;
    }

    public static function review( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'event_reviews' ) . ' WHERE id=%d', $id ), ARRAY_A );
    }
    public static function moderate( $id, $decision, $note ) {
        global $wpdb;
        if ( ! current_user_can( 'manage_ft_events' ) ) { return new WP_Error( 'permission', 'Sin permiso.' ); }
        if ( ! in_array( $decision, array( 'published', 'corrections', 'rejected' ), true ) ) { return new WP_Error( 'decision', 'Decisión inválida.' ); }
        $wpdb->query( 'START TRANSACTION' );
        $r = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'event_reviews' ) . ' WHERE id=%d FOR UPDATE', $id ), ARRAY_A );
        if ( ! $r || ! in_array( $r['status'], array( 'pending', 'corrections' ), true ) ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'review', 'Esta propuesta ya fue revisada.' ); }
        $data = array_merge( self::get( $r['event_id'] ), json_decode( $r['payload'], true ) );
        if ( $decision === 'published' ) {
            $data = self::validate( $data );
            if ( is_wp_error( $data ) ) { $wpdb->query( 'ROLLBACK' ); return $data; }
        }
        $now = current_time( 'mysql', true );
        if ( $decision === 'published' ) {
            $event = self::get( $r['event_id'] );
            $data['status'] = 'published'; $data['updated_at'] = $now; $data['published_at'] = $event['published_at'] ?: $now;
            if ( false === $wpdb->update( self::table(), $data, array( 'id' => $r['event_id'] ) ) ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'save', 'No se pudo publicar.' ); }
        }
        if ( false === $wpdb->update( self::table( 'event_reviews' ), array( 'status' => $decision, 'note' => sanitize_textarea_field( $note ), 'reviewer_id' => get_current_user_id(), 'reviewed_at' => $now ), array( 'id' => $id ) ) ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'save', 'No se pudo guardar la revisión.' ); }
        $wpdb->query( 'COMMIT' );
        $labels = array( 'published' => 'Publicado', 'corrections' => 'Necesita correcciones', 'rejected' => 'No aprobado' );
        $user = get_userdata( $r['author_id'] );
        if ( $user ) { self::queue_mail( $user->user_email, 'Evento: ' . $labels[$decision], $data['title'] . "\n" . sanitize_textarea_field( $note ) . "\n" . home_url( '/mis-eventos/' ) ); }
        return true;
    }

    public static function queue_mail( $to, $subject, $message ) {
        global $wpdb;
        if ( ! is_email( $to ) ) { return; }
        $wpdb->insert( self::table( 'event_mail' ), array( 'recipient' => $to, 'subject' => $subject, 'message' => $message, 'created_at' => current_time( 'mysql', true ) ) );
        self::send_mail( $wpdb->insert_id );
    }
    public static function send_mail( $id ) {
        global $wpdb;
        $table = self::table( 'event_mail' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d", $id ), ARRAY_A );
        if ( ! $row || in_array( $row['status'], array( 'sent', 'local' ), true ) ) { return; }
        $host = wp_parse_url( home_url(), PHP_URL_HOST );
        if ( in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) || wp_get_environment_type() === 'local' ) {
            $status = 'local'; // Correo capturado en la tabla: nunca sale de MAMP.
        } else {
            $status = wp_mail( $row['recipient'], $row['subject'], $row['message'] ) ? 'sent' : 'failed';
        }
        $wpdb->update( $table, array( 'status' => $status, 'attempts' => (int) $row['attempts'] + 1 ), array( 'id' => $id ) );
        if ( $status === 'failed' && (int) $row['attempts'] < 2 ) { wp_schedule_single_event( time() + 300, 'ftuy_retry_mail', array( $id ) ); }
    }

    public static function public_data( $e, $detail = false ) {
        $e['schedule'] = json_decode( $e['schedule_json'] ?? '[]', true ) ?: array();
        $data = array_intersect_key( $e, array_flip( array( 'id', 'slug', 'title', 'summary', 'start_date', 'end_date', 'start_time', 'end_time', 'schedule', 'entry_type', 'department', 'locality', 'venue', 'address', 'cancelled' ) ) );
        $data['id'] = (int) $e['id']; $data['cancelled'] = (bool) $e['cancelled'];
        $data['temporal_status'] = self::temporal( $e ); $data['url'] = self::url( $e );
        $data['image'] = array( 'thumbnail' => wp_get_attachment_image_url( $e['image_id'], 'medium_large' ) ?: null, 'full' => wp_get_attachment_image_url( $e['image_id'], 'full' ) ?: null );
        if ( $detail ) {
            foreach ( array( 'description', 'organizer', 'website', 'instagram', 'tickets_url', 'price', 'latitude', 'longitude' ) as $key ) { $data[$key] = $e[$key]; }
        }
        return $data;
    }

    public static function import_legacy( $limit = 4, $dry = false ) {
        global $wpdb;
        $limit = min( 500, max( 1, $limit ) );
        $posts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_type='event' AND post_status='publish' ORDER BY post_date DESC,ID DESC LIMIT %d", $limit ) );
        $report = array( 'created' => 0, 'skipped' => 0, 'errors' => array(), 'items' => array(), 'dry_run' => $dry );
        foreach ( $posts as $p ) {
            $old = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE legacy_post_id=%d', $p->ID ) );
            if ( $old ) { $report['skipped']++; continue; }
            $meta = function ( $key ) use ( $p ) { return get_post_meta( $p->ID, $key, true ); };
            $address = $meta( 'event_detailed_address' );
            $terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.name,tt.taxonomy FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON t.term_id=tt.term_id JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id=tt.term_taxonomy_id WHERE tr.object_id=%d AND tt.taxonomy IN ('organizer','location')", $p->ID ) );
            $organizers = array(); $locations = array();
            foreach ( $terms as $term ) { if ( $term->taxonomy === 'organizer' ) { $organizers[] = $term->name; } else { $locations[] = $term->name; } }
            $department = '';
            foreach ( self::departments() as $dep ) { if ( stripos( $address . ' ' . implode( ' ', $locations ), $dep ) !== false ) { $department = $dep; break; } }
            $data = array( 'title' => $p->post_title, 'description' => $p->post_content, 'summary' => $p->post_excerpt, 'start_date' => $meta( 'event_start_date' ), 'end_date' => $meta( 'event_end_date' ), 'start_time' => $meta( 'event_start_time' ), 'end_time' => $meta( 'event_end_time' ), 'department' => $department, 'locality' => $department === 'Montevideo' ? 'Montevideo' : implode( ', ', $locations ), 'address' => $address, 'organizer' => implode( ', ', $organizers ), 'price' => $meta( 'event-ticket-main-price' ), 'image_id' => get_post_thumbnail_id( $p->ID ), 'latitude' => $meta( 'event-map-lat' ), 'longitude' => $meta( 'event-map-lng' ) );
            $data = array_merge( $data, self::legacy_links( $p->ID ) );
            $valid = self::validate( $data );
            if ( is_wp_error( $valid ) ) { $report['errors'][] = array( 'post_id' => $p->ID, 'title' => $p->post_title, 'error' => $valid->get_error_message() ); continue; }
            $valid = array_merge( $valid, array( 'slug' => $p->post_name, 'legacy_post_id' => $p->ID, 'author_id' => $p->post_author, 'status' => 'published', 'created_at' => $p->post_date_gmt, 'updated_at' => current_time( 'mysql', true ), 'published_at' => $p->post_date_gmt ) );
            if ( ! $dry && false === $wpdb->insert( self::table(), $valid ) ) { $report['errors'][] = array( 'post_id' => $p->ID, 'error' => 'No se pudo importar (posible slug duplicado).' ); continue; }
            $report['created']++; $report['items'][] = array( 'post_id' => $p->ID, 'title' => $p->post_title, 'slug' => $p->post_name );
        }
        return $report;
    }

    public static function instagram_url( $value ) {
        $value = trim( sanitize_text_field( $value ) );
        if ( $value === '' ) { return ''; }
        $handle = ltrim( $value, '@' );
        if ( preg_match( '/^[a-zA-Z0-9._]{1,30}$/', $handle ) ) { return 'https://www.instagram.com/' . $handle . '/'; }
        if ( preg_match( '#^(?:www\.)?instagram\.com/#i', $value ) ) { $value = 'https://' . $value; }
        $host = strtolower( wp_parse_url( $value, PHP_URL_HOST ) ?: '' );
        $path = trim( wp_parse_url( $value, PHP_URL_PATH ) ?: '', '/' );
        if ( in_array( $host, array( 'instagram.com', 'www.instagram.com' ), true ) && preg_match( '/^[a-zA-Z0-9._]{1,30}$/', $path ) ) { return 'https://www.instagram.com/' . $path . '/'; }
        return new WP_Error( 'instagram', 'Ingresá un @usuario o el enlace al perfil de Instagram.' );
    }

    public static function legacy_links( $post_id ) {
        $result = array( 'website' => '', 'instagram' => '' );
        $links = get_post_meta( $post_id, 'social-links', true );
        if ( ! is_array( $links ) ) { return $result; }
        foreach ( $links as $link ) {
            if ( ! is_array( $link ) ) { continue; }
            $url = esc_url_raw( $link['url'] ?? '', array( 'http', 'https' ) );
            if ( in_array( strtolower( wp_parse_url( $url, PHP_URL_HOST ) ?: '' ), array( 'instagram.com', 'www.instagram.com' ), true ) ) {
                $normalized = self::instagram_url( $url );
                if ( ! is_wp_error( $normalized ) ) { $result['instagram'] = $normalized; }
            } elseif ( stripos( $link['title'] ?? '', 'web' ) !== false || ( $link['icon'] ?? '' ) === 'link-1' ) { $result['website'] = $url; }
        }
        return $result;
    }
}
