<?php
/**
 * Plugin Name: Foodtrucks UY Core
 * Description: Plataforma compartida de Foodtrucks Uruguay para el sitio y la aplicación.
 * Version: 0.14.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: foodtrucks-uy-core
 */

defined( 'ABSPATH' ) || exit;

define( 'FOODTRUCKS_UY_CORE_VERSION', '0.14.1' );
define( 'FTUY_PATH', plugin_dir_path( __FILE__ ) );
define( 'FTUY_URL', plugin_dir_url( __FILE__ ) );

require_once FTUY_PATH . 'includes/class-ftuy-events.php';
require_once FTUY_PATH . 'includes/class-ftuy-media.php';
FTUY_Media::init();
require_once FTUY_PATH . 'includes/class-ftuy-form.php';
require_once FTUY_PATH . 'includes/class-ftuy-admin.php';
require_once FTUY_PATH . 'includes/class-ftuy-public.php';
require_once FTUY_PATH . 'includes/class-ftuy-foodtrucks.php';
require_once FTUY_PATH . 'includes/class-ftuy-foodtruck-images.php';
require_once FTUY_PATH . 'includes/class-ftuy-foodtruck-admin.php';
require_once FTUY_PATH . 'includes/class-ftuy-foodtruck-public.php';
require_once FTUY_PATH . 'includes/class-ftuy-foodtruck-form.php';
require_once FTUY_PATH . 'includes/class-ftuy-foodtruck-submissions.php';
require_once FTUY_PATH . 'includes/class-ftuy-instagram.php';
require_once FTUY_PATH . 'includes/class-ftuy-accounts.php';
require_once FTUY_PATH . 'includes/class-ftuy-app-sessions.php';
require_once FTUY_PATH . 'includes/class-ftuy-profile-images.php';
FTUY_Profile_Images::init();
require_once FTUY_PATH . 'includes/class-ftuy-publications.php';
require_once FTUY_PATH . 'includes/class-ftuy-publication-admin.php';
require_once FTUY_PATH . 'includes/class-ftuy-publication-public.php';

register_activation_hook( __FILE__, array( 'FTUY_Events', 'install' ) );
add_action( 'plugins_loaded', function () {
    FTUY_Accounts::init();
    FTUY_App_Sessions::init();
    if ( get_option( 'ftuy_schema_version' ) !== '3' ) {
        FTUY_Events::install();
    }
    FTUY_Admin::init();
    FTUY_Public::init();
    if ( get_option( 'ftuy_foodtruck_schema' ) !== '1' ) { FTUY_Foodtrucks::install(); }
    FTUY_Foodtruck_Admin::init();
    FTUY_Foodtruck_Public::init();
    FTUY_Instagram::init();
    if ( get_option( 'ftuy_publication_schema' ) !== '2' ) { FTUY_Publications::install(); }
    FTUY_Publication_Admin::init();
    FTUY_Publication_Public::init();
} );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once FTUY_PATH . 'includes/class-ftuy-legacy-sql.php';
    require_once FTUY_PATH . 'includes/class-ftuy-user-migration.php';
    require_once FTUY_PATH . 'includes/class-ftuy-publication-migration.php';
    WP_CLI::add_command( 'ftuy migrate-users', array( 'FTUY_User_Migration', 'command' ) );
    WP_CLI::add_command( 'ftuy migrate-user-names', array( 'FTUY_User_Migration', 'names_command' ) );
    WP_CLI::add_command( 'ftuy migrate-publications', array( 'FTUY_Publication_Migration', 'command' ) );
    WP_CLI::add_command( 'ftuy sample-foodtrucks', function () { WP_CLI::log( wp_json_encode( FTUY_Foodtrucks::sample(), JSON_UNESCAPED_UNICODE ) ); } );
    WP_CLI::add_command( 'ftuy import-events', function ( $args, $assoc ) {
        $result = FTUY_Events::import_legacy( isset( $assoc['limit'] ) ? absint( $assoc['limit'] ) : 4, isset( $assoc['dry-run'] ) );
        WP_CLI::log( wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
        if ( $result['errors'] ) { WP_CLI::error( 'Hay registros pendientes de revisión; consultar informe.' ); }
        WP_CLI::success( 'Importación finalizada; originales conservados.' );
    } );
}
