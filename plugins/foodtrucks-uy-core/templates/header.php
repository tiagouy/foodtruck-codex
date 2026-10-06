<?php defined( 'ABSPATH' ) || exit; ?>
<header class="ft-header"><div class="ft-container ft-header-inner">
<a class="ft-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">FOODTRUCKS<span>URUGUAY</span></a>
<nav aria-label="Navegación principal">
<a href="<?php echo esc_url( home_url( '/foodtrucks/' ) ); ?>">Foodtrucks</a>
<a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>">Eventos</a>
<a href="<?php echo esc_url( home_url( '/eventos/pasados/' ) ); ?>">Eventos pasados</a>
<details class="ft-account-menu">
<summary>Mi cuenta</summary>
<div class="ft-account-links">
<a href="<?php echo esc_url( home_url( '/mis-eventos/' ) ); ?>">Mis eventos</a>
<a href="<?php echo esc_url( home_url( '/mis-foodtrucks/' ) ); ?>">Mis foodtrucks</a>
<div class="ft-account-divider"></div>
<a href="<?php echo esc_url( home_url( '/sugerir-evento/' ) ); ?>">Sugerir evento</a>
<a href="<?php echo esc_url( home_url( '/agregar-foodtruck/' ) ); ?>">Agregar mi foodtruck</a>
<div class="ft-account-divider"></div>
<?php if ( is_user_logged_in() ) : ?>
<a href="<?php echo esc_url( wp_logout_url( home_url( '/eventos/' ) ) ); ?>">Cerrar sesión</a>
<?php else : ?>
<a href="<?php echo esc_url( wp_login_url( home_url( '/mis-foodtrucks/' ) ) ); ?>">Iniciar sesión</a>
<?php if ( get_option( 'users_can_register' ) ) : ?><a href="<?php echo esc_url( wp_registration_url() ); ?>">Crear cuenta</a><?php endif; ?>
<?php endif; ?>
</div></details>
</nav></div></header>
