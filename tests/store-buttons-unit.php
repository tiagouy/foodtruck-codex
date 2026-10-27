<?php
// Standalone rendering checks: no database, network, or production writes.
define( 'ABSPATH', __DIR__ );
define( 'FTUY_PATH', dirname( __DIR__ ) . '/plugins/foodtrucks-uy-core/' );
$store_links = array();
function get_option( $name, $default = false ) { global $store_links; return $name === 'ftuy_store_links' ? $store_links : $default; }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value, $protocols = array( 'https' ) ) { return in_array( parse_url( $value, PHP_URL_SCHEME ), $protocols, true ) ? esc_attr( $value ) : ''; }
function get_posts( $args ) { return array(); }
require FTUY_PATH . 'includes/class-ftuy-home.php';
$checks = 0;
function check( $ok, $message ) { global $checks; if ( ! $ok ) { throw new RuntimeException( $message ); } $checks++; }
function home_app() { ob_start(); FTUY_Home::render( 'app' ); return ob_get_clean(); }
function store_buttons() { ob_start(); include FTUY_PATH . 'templates/store-buttons.php'; return ob_get_clean(); }
$store_links = array( 'enabled' => true, 'ios' => 'https://apps.apple.com/us/app/food-trucks-uy/id1200507576', 'android' => 'https://play.google.com/store/apps/details?id=org.useful_media_app.foodtruck' );
$home = home_app();
check( strpos( $home, 'id="la-app"' ) !== false, 'Preserves home anchor' );
check( strpos( $home, 'Estamos preparando' ) === false && strpos( $home, 'Explorá la comunidad' ) === false, 'Removes outdated CTA' );
check( strpos( $home, 'Descargala gratis para iPhone y Android.' ) !== false, 'Current download copy' );
foreach ( array( 'ios', 'android' ) as $platform ) { check( strpos( $home, esc_url( $store_links[$platform] ) ) !== false, 'Configured store URL' ); }
check( substr_count( $home, 'target="_blank" rel="noopener"' ) === 2, 'Safe external links' );
check( strpos( $home, store_buttons() ) !== false, 'Shared home markup' );
ob_start(); include FTUY_PATH . 'templates/footer.php'; $footer = ob_get_clean();
check( strpos( $footer, store_buttons() ) !== false, 'Footer uses identical shared markup' );
$store_links['ios'] = '';
check( substr_count( home_app(), 'disabled aria-label=' ) === 1, 'Missing store stays disabled' );
$store_links['ios'] = 'javascript:alert(1)';
check( strpos( home_app(), 'javascript:' ) === false, 'Unsafe URL not rendered' );
$store_links['enabled'] = false;
check( substr_count( home_app(), 'disabled aria-label=' ) === 2, 'Global release gate preserved' );
$store_links = 'invalid';
check( substr_count( home_app(), 'disabled aria-label=' ) === 2, 'Malformed option handled safely' );
echo "$checks store button rendering checks passed; no data changed.\n";
