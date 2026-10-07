<?php
defined( 'ABSPATH' ) || exit;

/** Planner and resumable local sample/full importer. Never executes source SQL. */
class FTUY_User_Migration {
    public static function current_users() {
        $result = array();
        foreach ( get_users() as $user ) {
            $result[] = array( 'id' => $user->ID, 'email' => $user->user_email, 'legacy_id' => (int) get_user_meta( $user->ID, FTUY_Accounts::LEGACY_META, true ), 'aliases' => (array) get_user_meta( $user->ID, 'ftuy_legacy_alias_ids', true ), 'is_admin' => user_can( $user, 'manage_options' ) );
        } return $result;
    }
    public static function media( $root, $type, $id, $filename ) {
        if ( ! $root || ! is_string( $filename ) || ! trim( $filename ) || strtoupper( trim( $filename ) ) === 'NULL' ) { return null; }
        $base = realpath( $root ); if ( ! $base ) { return null; }
        foreach ( array( 'api2', 'api' ) as $api ) {
            $path = realpath( $base . '/' . $api . '/' . $type . '/uploads/' . $id . '/' . basename( $filename ) );
            if ( $path && is_file( $path ) && strpos( $path, $base . DIRECTORY_SEPARATOR ) === 0 ) { return $path; }
        } return null;
    }
    public static function plan( $data, $media_root, $sample_size = 5, $existing = null ) {
        if ( $existing === null ) { $existing = self::current_users(); }
        usort( $data['users'], function ( $a, $b ) { return (int) $a['idUser'] <=> (int) $b['idUser']; } );
        usort( $data['offers'], function ( $a, $b ) { return (int) $a['idOffer'] <=> (int) $b['idOffer']; } );
        $groups = array(); $source_ids = array(); $rows = array(); $by_legacy = array(); $excluded = array();
        $summary = array( 'mode' => 'dry-run', 'source_accounts' => count( $data['users'] ), 'excluded_unusable_email' => 0, 'destination_accounts' => 0, 'would_create_subscribers' => 0, 'would_link_existing' => 0, 'already_mapped' => 0, 'blocked_conflicts' => 0, 'merged_email_groups' => 0, 'alias_ids_to_preserve' => 0, 'unknown_creation_dates' => 0, 'avatars_referenced' => 0, 'avatar_files_found' => 0 );
        foreach ( $data['users'] as $u ) {
            if ( ! ctype_digit( (string) $u['idUser'] ) || (int) $u['idUser'] < 1 || isset( $source_ids[(int) $u['idUser']] ) ) { throw new RuntimeException( 'ID histórico inválido o repetido.' ); }
            $id = (int) $u['idUser']; $source_ids[$id] = true; $email = strtolower( trim( (string) $u['email'] ) );
            if ( $u['creationDate'] === '0000-00-00 00:00:00' ) { $summary['unknown_creation_dates']++; }
            if ( trim( (string) $u['photo'] ) && strtoupper( trim( (string) $u['photo'] ) ) !== 'NULL' ) { $summary['avatars_referenced']++; }
            if ( self::media( $media_root, 'users', $id, $u['photo'] ) ) { $summary['avatar_files_found']++; }
            if ( ! is_email( $email ) || strlen( $email ) > 100 ) { $excluded[] = $id; $summary['excluded_unusable_email']++; continue; }
            $groups[$email][] = $u;
        }
        foreach ( $groups as $email => $members ) {
            usort( $members, function ( $a, $b ) { return (int) $a['idUser'] <=> (int) $b['idUser']; } );
            $primary = $members[0]; $ids = array_map( function ( $u ) { return (int) $u['idUser']; }, $members );
            $matches = array_values( array_filter( $existing, function ( $wp ) use ( $email ) { return strtolower( trim( $wp['email'] ) ) === $email; } ) );
            $action = 'create_subscriber'; $target = null; $reason = '';
            if ( count( $matches ) > 1 ) { $action = 'blocked'; $reason = 'multiple_wp_accounts_for_email'; }
            elseif ( $matches ) {
                $target = $matches[0]; $mapped = (int) $target['legacy_id'];
                if ( $mapped && $mapped !== $ids[0] ) { $action = 'blocked'; $reason = 'existing_primary_id_conflict'; }
                else { $action = $mapped && ! array_diff( array_slice( $ids, 1 ), array_map( 'intval', $target['aliases'] ) ) ? 'already_mapped' : 'link_existing_preserve_permissions'; }
            }
            foreach ( $existing as $wp ) {
                $claimed = array_filter( array_merge( array( (int) $wp['legacy_id'] ), array_map( 'intval', $wp['aliases'] ) ) );
                if ( array_intersect( $ids, $claimed ) && ( ! $target || (int) $wp['id'] !== (int) $target['id'] ) ) { $action = 'blocked'; $reason = 'historical_id_claimed_elsewhere'; }
            }
            if ( count( $members ) > 1 ) { $summary['merged_email_groups']++; $summary['alias_ids_to_preserve'] += count( $members ) - 1; }
            $avatar = null; foreach ( $members as $member ) { $avatar = self::media( $media_root, 'users', (int) $member['idUser'], $member['photo'] ); if ( $avatar ) { break; } }
            $row = array( 'legacy_primary_id' => $ids[0], 'legacy_alias_ids' => array_slice( $ids, 1 ), 'email' => $email, 'display_name' => sanitize_text_field( trim( $primary['firstname'] . ' ' . $primary['lastname'] ) ) ?: 'Usuario', 'legacy_created_at' => $primary['creationDate'] === '0000-00-00 00:00:00' ? null : $primary['creationDate'], 'avatar_source' => $avatar, 'action' => $action, 'target_wp_user_id' => $target ? (int) $target['id'] : null, 'preserve_admin' => $target && $target['is_admin'], 'publication_count' => 0, 'block_reason' => $reason );
            $index = count( $rows ); $rows[] = $row; foreach ( $ids as $id ) { $by_legacy[$id] = $index; }
            $key = array( 'create_subscriber' => 'would_create_subscribers', 'link_existing_preserve_permissions' => 'would_link_existing', 'already_mapped' => 'already_mapped', 'blocked' => 'blocked_conflicts' )[$action]; $summary[$key]++;
        }
        $summary['destination_accounts'] = count( $rows );
        $publications = array(); $photo_ids = array(); $authors = array();
        $summary['publications'] = array( 'total' => count( $data['offers'] ), 'authors' => 0, 'with_account_mapping' => 0, 'blocked_or_unmapped' => 0, 'files_found' => 0 );
        foreach ( $data['offers'] as $photo ) {
            $id = (int) $photo['idOffer']; if ( ! ctype_digit( (string) $photo['idOffer'] ) || $id < 1 || isset( $photo_ids[$id] ) ) { throw new RuntimeException( 'ID de publicación inválido o repetido.' ); } $photo_ids[$id] = true;
            $author = (int) $photo['idUser']; $authors[$author] = true; $index = $by_legacy[$author] ?? null;
            $mapped = $index !== null && $rows[$index]['action'] !== 'blocked';
            $summary['publications'][$mapped ? 'with_account_mapping' : 'blocked_or_unmapped']++;
            if ( $index !== null ) { $rows[$index]['publication_count']++; }
            $file = self::media( $media_root, 'offers', $id, $photo['photo'] ); if ( $file ) { $summary['publications']['files_found']++; }
            $publications[] = array( 'legacy_publication_id' => $id, 'legacy_author_id' => $author, 'canonical_author_id' => $mapped ? $rows[$index]['legacy_primary_id'] : null, 'source_file' => $file, 'legacy_status' => $photo['status'] );
        }
        $summary['publications']['authors'] = count( $authors );
        $sample_size = max( 1, min( 20, (int) $sample_size ) ); $sample = array();
        $pick = function ( $predicate, $limit ) use ( &$sample, $rows, $sample_size ) {
            foreach ( $rows as $row ) {
                $id = $row['legacy_primary_id']; if ( count( $sample ) >= $sample_size || $limit < 1 ) { break; }
                if ( ! isset( $sample[$id] ) && $row['action'] !== 'blocked' && $predicate( $row ) ) { $sample[$id] = $row; $limit--; }
            }
        };
        $pick( function ( $row ) { return $row['preserve_admin']; }, 1 );
        $pick( function ( $row ) { return $row['publication_count'] > 0 && $row['avatar_source']; }, 2 );
        $pick( function ( $row ) { return (bool) $row['legacy_alias_ids']; }, 1 );
        $pick( function ( $row ) { return $row['legacy_created_at'] === null; }, 1 );
        $pick( function () { return true; }, $sample_size );
        $summary['sample'] = array( 'accounts' => count( $sample ), 'with_publications' => count( array_filter( $sample, function ( $r ) { return $r['publication_count'] > 0; } ) ), 'with_avatar_file' => count( array_filter( $sample, function ( $r ) { return (bool) $r['avatar_source']; } ) ), 'preserving_admin' => count( array_filter( $sample, function ( $r ) { return $r['preserve_admin']; } ) ), 'with_aliases' => count( array_filter( $sample, function ( $r ) { return (bool) $r['legacy_alias_ids']; } ) ), 'unknown_date' => count( array_filter( $sample, function ( $r ) { return $r['legacy_created_at'] === null; } ) ) );
        // Private rows remain in memory. CLI output uses only the summary, never emails/names/files.
        return array( 'summary' => $summary, 'accounts' => $rows, 'excluded_ids' => $excluded, 'publications' => $publications, 'sample' => array_values( $sample ) );
    }
    public static function apply_sample( $plan, $hash ) {
        if ( ! FTUY_Accounts::local() || ! current_user_can( 'manage_options' ) || empty( $plan['sample'] ) || count( $plan['sample'] ) > 5 || ! preg_match( '/^[a-f0-9]{64}$/D', $hash ) ) { throw new RuntimeException( 'Solo muestra local de hasta cinco cuentas, con permiso administrativo.' ); }
        return self::apply_rows( $plan['sample'], $hash );
    }
    public static function apply_all( $plan, $hash, $progress = null ) {
        if ( ! FTUY_Accounts::local() || ! current_user_can( 'manage_options' ) || empty( $plan['accounts'] ) || count( $plan['accounts'] ) > 10000 || ! preg_match( '/^[a-f0-9]{64}$/D', $hash ) || ! empty( $plan['summary']['blocked_conflicts'] ) ) { throw new RuntimeException( 'Importación completa inválida o sin permiso.' ); }
        $totals = array( 'mode' => 'apply-all', 'processed' => 0, 'created_subscribers' => 0, 'linked_existing' => 0, 'already_mapped' => 0, 'avatars_imported' => 0, 'avatars_already_present' => 0, 'accounts_without_avatar' => 0, 'invalid_avatar_sources' => 0 );
        foreach ( array_chunk( $plan['accounts'], 50 ) as $batch ) {
            foreach ( $batch as &$row ) {
                if ( $row['avatar_source'] ) {
                    $info = wp_getimagesize( $row['avatar_source'] );
                    if ( ! $info || ! in_array( $info['mime'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) { $row['avatar_source'] = null; $row['avatar_invalid'] = true; $totals['invalid_avatar_sources']++; }
                }
            } unset( $row );
            $result = self::apply_rows( $batch, $hash );
            foreach ( $batch as $row ) { if ( ! empty( $row['avatar_invalid'] ) ) { update_user_meta( FTUY_Accounts::legacy_owner( $row['legacy_primary_id'] ), 'ftuy_legacy_avatar_needs_review', 1 ); } }
            foreach ( $totals as $key => $value ) { if ( isset( $result[$key] ) && is_int( $value ) ) { $totals[$key] += $result[$key]; } }
            $totals['processed'] += count( $batch );
            if ( is_callable( $progress ) ) { call_user_func( $progress, $totals, count( $plan['accounts'] ) ); }
            // CLI-only import can rebuild runtime caches between bounded batches.
            if ( function_exists( 'wp_cache_flush_runtime' ) ) { wp_cache_flush_runtime(); }
        }
        return $totals;
    }
    private static function apply_rows( $rows, $hash ) {
        $plan = array( 'sample' => $rows );
        if ( ! add_option( 'ftuy_user_migration_lock', time(), '', false ) ) { throw new RuntimeException( 'Hay otra importación en curso. Revisar el bloqueo antes de reintentar.' ); }
        $result = array( 'mode' => 'apply-sample', 'created_subscribers' => 0, 'linked_existing' => 0, 'already_mapped' => 0, 'avatars_imported' => 0, 'avatars_already_present' => 0, 'accounts_without_avatar' => 0, 'publications_imported' => 0 );
        // Even third-party hooks may not send real mail during this bounded local operation.
        $no_mail = function () { return true; }; add_filter( 'pre_wp_mail', $no_mail, PHP_INT_MAX );
        try {
            // Preflight all five before starting. Reservations are checked as well as usermeta.
            foreach ( $plan['sample'] as $row ) {
                if ( $row['action'] === 'blocked' ) { throw new RuntimeException( 'La muestra contiene conflictos.' ); }
                $user = get_user_by( 'email', $row['email'] );
                foreach ( array_merge( array( $row['legacy_primary_id'] ), $row['legacy_alias_ids'] ) as $legacy_id ) {
                    $owner = (int) get_option( 'ftuy_legacy_owner_' . $legacy_id );
                    if ( $owner && ( ! $user || $owner !== (int) $user->ID ) ) { throw new RuntimeException( 'ID histórico reservado por otra cuenta. No continuar.' ); }
                }
                if ( $row['target_wp_user_id'] && ( ! $user || (int) $user->ID !== $row['target_wp_user_id'] ) ) { throw new RuntimeException( 'La cuenta de destino cambió. Repetir la simulación.' ); }
                if ( ! $row['target_wp_user_id'] && $user && get_user_meta( $user->ID, 'ftuy_migration_source_sha256', true ) !== $hash ) { throw new RuntimeException( 'Apareció una cuenta nueva con ese email. Repetir la simulación.' ); }
            }
            foreach ( $plan['sample'] as $row ) {
                $journal_key = 'ftuy_user_migration_' . $row['legacy_primary_id'];
                $user = get_user_by( 'email', $row['email'] ); $created = false;
                if ( $row['target_wp_user_id'] && ( ! $user || (int) $user->ID !== $row['target_wp_user_id'] ) ) { throw new RuntimeException( 'La cuenta cambió durante la importación. Repetir la simulación.' ); }
                if ( ! $row['target_wp_user_id'] && $user && get_user_meta( $user->ID, 'ftuy_migration_source_sha256', true ) !== $hash ) { throw new RuntimeException( 'Apareció otra cuenta durante la importación. Repetir la simulación.' ); }
                if ( ! $user ) {
                    $id = wp_insert_user( array( 'user_login' => 'ft_' . str_replace( '-', '', wp_generate_uuid4() ), 'user_email' => $row['email'], 'display_name' => $row['display_name'], 'user_pass' => wp_generate_password( 64 ), 'role' => 'subscriber', 'meta_input' => array( 'ftuy_account_status' => 'legacy_pending', 'ftuy_migration_source_sha256' => $hash ) ) );
                    if ( is_wp_error( $id ) ) { throw new RuntimeException( 'No se pudo crear una cuenta de la muestra. Revisar y retomar.' ); }
                    $user = get_user_by( 'id', $id ); $created = true;
                }
                $was_mapped = (int) get_user_meta( $user->ID, FTUY_Accounts::LEGACY_META, true ) === $row['legacy_primary_id'] && ! array_diff( $row['legacy_alias_ids'], (array) get_user_meta( $user->ID, 'ftuy_legacy_alias_ids', true ) );
                update_option( $journal_key, array( 'source_sha256' => $hash, 'wp_user_id' => $user->ID, 'state' => 'running' ), false );
                $linked = FTUY_Accounts::set_legacy_ids( $user->ID, $row['legacy_primary_id'], $row['legacy_alias_ids'] );
                if ( is_wp_error( $linked ) ) { throw new RuntimeException( 'No se pudo vincular una identidad histórica. Avance conservado para reintentar.' ); }
                // Never alter existing account roles, name, password, registration date or status.
                update_user_meta( $user->ID, 'ftuy_legacy_created_at', $row['legacy_created_at'] ?: '' );
                update_user_meta( $user->ID, 'ftuy_legacy_date_unknown', $row['legacy_created_at'] === null ? 1 : 0 );
                if ( $row['avatar_source'] ) {
                    $present = FTUY_Profile_Images::attachment( $user->ID );
                    $avatar = FTUY_Profile_Images::import( $user->ID, $row['avatar_source'] );
                    if ( is_wp_error( $avatar ) ) { throw new RuntimeException( 'Una foto de perfil no pudo procesarse. Cuenta conservada; reintentar tras revisar.' ); }
                    $result[$present ? 'avatars_already_present' : 'avatars_imported']++;
                } else { $result['accounts_without_avatar']++; }
                update_option( $journal_key, array( 'source_sha256' => $hash, 'wp_user_id' => $user->ID, 'state' => 'complete' ), false );
                $result[$created ? 'created_subscribers' : ( $was_mapped ? 'already_mapped' : 'linked_existing' )]++;
            }
            return $result;
        } finally { remove_filter( 'pre_wp_mail', $no_mail, PHP_INT_MAX ); delete_option( 'ftuy_user_migration_lock' ); }
    }
    public static function command( $args, $assoc ) {
        $all = isset( $assoc['apply-all'] ); $apply = isset( $assoc['apply-sample'] ) || $all;
        if ( ! FTUY_Accounts::local() || ! isset( $assoc['source'], $assoc['media-root'] ) || ( (int) isset( $assoc['dry-run'] ) + (int) isset( $assoc['apply-sample'] ) + (int) $all !== 1 ) ) { WP_CLI::error( 'Solo local: elegir --dry-run, --apply-sample o --apply-all; indicar --source y --media-root.' ); }
        try {
            $data = FTUY_Legacy_SQL::read( $assoc['source'], array( 'users' => array( 'idUser', 'firstname', 'lastname', 'email', 'creationDate', 'status', 'photo' ), 'offers' => array( 'idOffer', 'idUser', 'photo', 'status' ) ) );
            if ( ! is_dir( $assoc['media-root'] ) ) { throw new RuntimeException( 'Carpeta de medios no disponible.' ); }
            $result = self::plan( $data, $assoc['media-root'], $assoc['sample-size'] ?? 5 );
            $summary = array_merge( array( 'source_sha256' => hash_file( 'sha256', $assoc['source'] ) ), $result['summary'] );
            WP_CLI::log( wp_json_encode( $summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
            if ( $summary['blocked_conflicts'] || $summary['publications']['blocked_or_unmapped'] ) { WP_CLI::error( 'Hay conflictos para revisar. No se modificó ningún dato.' ); }
            if ( $apply ) {
                $backup = $assoc['backup'] ?? ''; global $wpdb;
                if ( ! current_user_can( 'manage_options' ) || ! is_file( $backup ) || ! is_readable( $backup ) || realpath( $backup ) === realpath( $assoc['source'] ) || filesize( $backup ) < 1024 || strpos( file_get_contents( $backup, false, null, 0, 1048576 ), 'CREATE TABLE `' . $wpdb->options . '`' ) === false ) { throw new RuntimeException( 'Aplicar requiere --user administrador y --backup de la base WordPress local.' ); }
                if ( ! $all && count( $result['sample'] ) > 5 ) { throw new RuntimeException( 'Solo se permite importar una muestra de hasta cinco cuentas.' ); }
                $applied = $all ? self::apply_all( $result, $summary['source_sha256'], function ( $totals, $total ) { WP_CLI::log( 'Cuentas procesadas: ' . $totals['processed'] . '/' . $total . '; nuevas: ' . $totals['created_subscribers'] . '; avatares nuevos: ' . $totals['avatars_imported'] ); } ) : self::apply_sample( $result, $summary['source_sha256'] );
                WP_CLI::log( wp_json_encode( $applied, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
                WP_CLI::success( 'Usuarios importados. Sin envío real de correos ni importación de publicaciones; progreso reanudable.' );
            } else { WP_CLI::success( 'Simulación terminada. No se crearon usuarios, medios ni publicaciones; no se enviaron correos.' ); }
        } catch ( Throwable $e ) { WP_CLI::error( $e->getMessage() ); }
    }
}
