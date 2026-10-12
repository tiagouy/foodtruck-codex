<?php
defined( 'ABSPATH' ) || exit;

class FTUY_Analytics {
    public static function init() { add_action( 'wp_head', array( __CLASS__, 'render' ), 20 ); }
    public static function render() {
        $host = wp_parse_url( home_url(), PHP_URL_HOST );
        if ( ! in_array( $host, array( 'foodtruckuruguay.com', 'www.foodtruckuruguay.com' ), true ) || is_admin() || current_user_can( 'manage_options' ) ) { return; }
        $path = trim( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH ) ?: '', '/' );
        // Never load tracking on account/reset/preview pages, where URLs may contain secrets.
        if ( in_array( $path, array( 'ingresar', 'salir', 'registro', 'mi-cuenta', 'mis-eventos', 'mis-foodtrucks', 'sugerir-evento', 'agregar-foodtruck', 'reactivar-cuenta', 'recordar-contrasena', 'elegir-contrasena', 'wp-login.php' ), true ) || isset( $_GET['key'] ) || isset( $_GET['login'] ) || isset( $_GET['ft_preview'] ) || isset( $_GET['ft_truck_preview'] ) ) { return; }
        ?>
<!-- Foodtrucks UY · Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-RBK3ZR6ZWP"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
(function () {
    var locationURL = new URL(window.location.href);
    var referrer = '';
    try { var referrerURL = new URL(document.referrer); referrer = referrerURL.origin + referrerURL.pathname; } catch (_) {}
    gtag('config', 'G-RBK3ZR6ZWP', {
        page_location: locationURL.origin + locationURL.pathname,
        page_referrer: referrer
    });
})();
</script>
        <?php
    }
}
