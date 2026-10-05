<?php
/**
 * Plugin Name: Foodtrucks UY Core
 * Description: Plataforma compartida de Foodtrucks Uruguay para el sitio y la aplicación.
 * Version: 0.3.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: foodtrucks-uy-core
 */

defined( 'ABSPATH' ) || exit;

define( 'FOODTRUCKS_UY_CORE_VERSION', '0.3.1' );
define( 'FTUY_PATH', plugin_dir_path( __FILE__ ) );
define( 'FTUY_URL', plugin_dir_url( __FILE__ ) );

require_once FTUY_PATH . 'includes/class-ftuy-events.php';
require_once FTUY_PATH . 'includes/class-ftuy-form.php';
require_once FTUY_PATH . 'includes/class-ftuy-admin.php';
require_once FTUY_PATH . 'includes/class-ftuy-public.php';

register_activation_hook( __FILE__, array( 'FTUY_Events', 'install' ) );
add_action( 'plugins_loaded', function () {
    if ( get_option( 'ftuy_schema_version' ) !== '3' ) {
        FTUY_Events::install();
    }
    FTUY_Admin::init();
    FTUY_Public::init();
} );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'ftuy import-events', function ( $args, $assoc ) {
        $result = FTUY_Events::import_legacy( isset( $assoc['limit'] ) ? absint( $assoc['limit'] ) : 4, isset( $assoc['dry-run'] ) );
        WP_CLI::log( wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
        if ( $result['errors'] ) { WP_CLI::error( 'Hay registros pendientes de revisión; consultar informe.' ); }
        WP_CLI::success( 'Importación finalizada; originales conservados.' );
    } );
}
