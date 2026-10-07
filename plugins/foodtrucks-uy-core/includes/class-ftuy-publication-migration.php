<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Publication_Migration {
    public static function fields() { return array( 'idOffer', 'idUser', 'idCategory', 'description', 'lat', 'lon', 'address', 'photo', 'creationDate', 'status', 'import_discount', 'val', 'slug' ); }
    public static function plan( $rows, $root ) {
        global $wpdb; $items = array(); $seen = array(); $slugs = array();
        $summary = array( 'total' => count( $rows ), 'ready' => 0, 'already_imported' => 0, 'blocked' => 0, 'published_in_source' => 0 );
        usort( $rows, function ( $a, $b ) { return (int) $a['idOffer'] <=> (int) $b['idOffer']; } );
        foreach ( $rows as $row ) {
            $id = (int) $row['idOffer'];
            if ( $id < 1 || isset( $seen[$id] ) ) { throw new RuntimeException( 'ID de publicación inválido o repetido.' ); } $seen[$id] = true;
            $slug = $row['slug'];
            if ( ! is_string( $slug ) || ! preg_match( '/^[a-zA-Z0-9-]{1,200}$/D', $slug ) || isset( $slugs[strtolower( $slug )] ) ) { throw new RuntimeException( 'Slug histórico inválido o repetido.' ); } $slugs[strtolower( $slug )] = true;
            $date = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $row['creationDate'] );
            if ( ! $date || $date->format( 'Y-m-d H:i:s' ) !== $row['creationDate'] ) { throw new RuntimeException( 'Fecha de publicación inválida.' ); }
            if ( ! in_array( (string) $row['status'], array( '0', '1' ), true ) ) { throw new RuntimeException( 'Estado histórico no reconocido.' ); }
            $author = FTUY_Accounts::legacy_owner( $row['idUser'] );
            $file = FTUY_User_Migration::media( $root, 'offers', $id, $row['photo'] ); $info = $file ? wp_getimagesize( $file ) : false;
            $existing = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . FTUY_Publications::table() . ' WHERE legacy_id=%d', $id ), ARRAY_A );
            $conflicting_slug = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . FTUY_Publications::table() . ' WHERE legacy_slug=%s AND (legacy_id IS NULL OR legacy_id<>%d) LIMIT 1', $slug, $id ) );
            $valid = FTUY_Publications::validate( array( 'caption' => (string) $row['description'], 'address' => (string) $row['address'], 'latitude' => $row['lat'], 'longitude' => $row['lon'], 'status' => (int) $row['status'] === 1 ? 'published' : 'unpublished' ) );
            $blocked = ! $author || ! $info || ! in_array( $info['mime'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) || filesize( $file ) > 5 * MB_IN_BYTES || $info[0] * $info[1] > 40000000 || is_wp_error( $valid ) || $conflicting_slug || ( $existing && ( (int) $existing['author_user_id'] !== $author || ! is_file( get_attached_file( $existing['image_id'] ) ) ) );
            $action = $blocked ? 'blocked' : ( $existing ? 'already_imported' : 'ready' ); $summary[$action]++;
            if ( (int) $row['status'] === 1 ) { $summary['published_in_source']++; }
            $items[] = array( 'source' => $row, 'author' => $author, 'file' => $file, 'action' => $action );
        }
        return array( 'summary' => $summary, 'items' => $items );
    }
    public static function apply( $plan, $hash ) {
        if ( ! FTUY_Accounts::local() || ! current_user_can( 'manage_ft_publications' ) || ! preg_match( '/^[a-f0-9]{64}$/D', $hash ) || ! empty( $plan['summary']['blocked'] ) ) { throw new RuntimeException( 'Plan inválido, bloqueado o sin permiso.' ); }
        if ( ! add_option( 'ftuy_publication_migration_lock', time(), '', false ) ) { throw new RuntimeException( 'Otra importación de publicaciones está en curso.' ); }
        $result = array( 'imported' => 0, 'already_imported' => 0 );
        try {
            foreach ( $plan['items'] as $item ) {
                if ( $item['action'] === 'already_imported' ) { $result['already_imported']++; continue; }
                // Recheck identity before adding files; never infer an owner from a name.
                if ( FTUY_Accounts::legacy_owner( $item['source']['idUser'] ) !== $item['author'] ) { throw new RuntimeException( 'El autor cambió; repetir el plan.' ); }
                $image = FTUY_Foodtruck_Images::process( $item['file'], 'community_photo', 'foto-comunidad', 'publicaciones' );
                if ( is_wp_error( $image ) ) { throw new RuntimeException( 'No se pudo procesar una foto. Avance previo conservado.' ); }
                $updated = wp_update_post( array( 'ID' => $image, 'post_author' => $item['author'] ), true );
                if ( is_wp_error( $updated ) ) { wp_delete_attachment( $image, true ); throw new RuntimeException( 'No se pudo asociar la foto con su autor.' ); }
                $id = FTUY_Publications::import_legacy( $item['source'], $image, $item['author'], $hash );
                if ( is_wp_error( $id ) ) { wp_delete_attachment( $image, true ); throw new RuntimeException( 'No se pudo importar una publicación; avance previo conservado.' ); }
                // If a concurrent/manual import won the unique ID, remove only our unused copy.
                if ( (int) FTUY_Publications::get( $id )['image_id'] !== (int) $image ) { wp_delete_attachment( $image, true ); }
                $result['imported']++;
            }
            return $result;
        } finally { delete_option( 'ftuy_publication_migration_lock' ); }
    }
    public static function command( $args, $assoc ) {
        if ( ! FTUY_Accounts::local() || ! isset( $assoc['source'], $assoc['media-root'] ) || ( isset( $assoc['dry-run'] ) === isset( $assoc['apply'] ) ) ) { WP_CLI::error( 'Solo local: --dry-run o --apply, --source y --media-root.' ); }
        try {
            $data = FTUY_Legacy_SQL::read( $assoc['source'], array( 'offers' => self::fields() ) );
            $plan = self::plan( $data['offers'], $assoc['media-root'] );
            WP_CLI::log( wp_json_encode( $plan['summary'], JSON_PRETTY_PRINT ) );
            if ( $plan['summary']['blocked'] ) { throw new RuntimeException( 'Hay autores, fotos o datos pendientes. No aplicar.' ); }
            if ( isset( $assoc['apply'] ) ) {
                global $wpdb; $backup = $assoc['backup'] ?? '';
                if ( ! current_user_can( 'manage_options' ) || ! is_file( $backup ) || realpath( $backup ) === realpath( $assoc['source'] ) || strpos( file_get_contents( $backup, false, null, 0, 1048576 ), 'CREATE TABLE `' . $wpdb->options . '`' ) === false ) { throw new RuntimeException( 'Aplicar requiere administrador y --backup de WordPress.' ); }
                WP_CLI::log( wp_json_encode( self::apply( $plan, hash_file( 'sha256', $assoc['source'] ) ), JSON_PRETTY_PRINT ) );
                WP_CLI::success( 'Publicaciones importadas; originales intactos, sin correos.' );
            } else { WP_CLI::success( 'Simulación completada; sin cambios.' ); }
        } catch ( Throwable $e ) { WP_CLI::error( $e->getMessage() ); }
    }
}
