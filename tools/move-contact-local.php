<?php
// Local, reversible content migration. Preserve all unrelated Elementor edits.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) { throw new Exception( 'Solo CLI local' ); }
$home_id = (int) get_option( 'page_on_front' );
$home = get_post( $home_id );
$contact = get_page_by_path( 'contacto' );
if ( ! $home || ! $contact ) { throw new Exception( 'Falta Home o página Contacto; no se modifica nada.' ); }
$raw = get_post_meta( $home_id, '_elementor_data', true );
$data = json_decode( $raw, true );
if ( ! is_array( $data ) ) { throw new Exception( 'Datos Elementor inválidos; no se modifica nada.' ); }
if ( ! get_option( 'ftuy_before_contact_move' ) ) {
    update_option( 'ftuy_before_contact_move', array( 'home_id' => $home_id, 'home_content' => $home->post_content, 'home_elementor' => $raw, 'contact_id' => $contact->ID, 'contact_content' => $contact->post_content, 'contact_status' => $contact->post_status ), false );
}
$prune = function ( $nodes ) use ( &$prune ) {
    $kept = array();
    foreach ( $nodes as $node ) {
        if ( ( $node['widgetType'] ?? '' ) === 'shortcode' && trim( $node['settings']['shortcode'] ?? '' ) === '[ftuy_home_contact]' ) { continue; }
        if ( ! empty( $node['elements'] ) ) {
            $node['elements'] = $prune( $node['elements'] );
            if ( ! $node['elements'] ) { continue; }
        }
        $kept[] = $node;
    }
    return $kept;
};
$updated = $prune( $data );
$result = wp_update_post( wp_slash( array( 'ID' => $contact->ID, 'post_status' => 'publish', 'post_content' => '[ftuy_home_contact]' ) ), true );
if ( is_wp_error( $result ) ) { throw new Exception( 'No se pudo actualizar Contacto.' ); }
$result = wp_update_post( wp_slash( array( 'ID' => $home_id, 'post_content' => str_replace( '[ftuy_home_contact]', '', $home->post_content ) ) ), true );
if ( is_wp_error( $result ) ) { throw new Exception( 'No se pudo actualizar Home.' ); }
update_post_meta( $home_id, '_elementor_data', wp_slash( wp_json_encode( $updated ) ) );
if ( class_exists( '\Elementor\Plugin' ) ) { \Elementor\Plugin::$instance->files_manager->clear_cache(); }
echo 'Contacto ' . (int) $contact->ID . ' conserva /contacto/ y usa el shortcode. Home sin contacto; demás bloques intactos. Copia previa en ftuy_before_contact_move.' . "\n";
