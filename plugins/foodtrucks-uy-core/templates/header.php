<?php defined( 'ABSPATH' ) || exit;
wp_register_style( 'ftuy-brand', FTUY_URL . 'assets/brand.css', array( 'ftuy-events' ), FOODTRUCKS_UY_CORE_VERSION );
wp_print_styles( 'ftuy-brand' );
?>
<header class="ft-header"><div class="ft-container ft-header-inner">
<a class="ft-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( FTUY_URL . 'assets/logo-horizontal.png' ); ?>" width="760" height="200" alt="Foodtrucks Uruguay"></a>
<div class="ft-desktop-nav"><?php include FTUY_PATH . 'templates/navigation.php'; ?></div>
<details class="ft-primary-menu"><summary>Menú ☰</summary><?php include FTUY_PATH . 'templates/navigation.php'; ?></details>
</div></header>
