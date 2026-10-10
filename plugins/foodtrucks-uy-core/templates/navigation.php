<?php defined( 'ABSPATH' ) || exit; ?>
<nav aria-label="Navegación principal">
<a href="<?php echo esc_url( home_url( '/foodtrucks/' ) ); ?>">Foodtrucks</a>
<a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>">Eventos</a>
<a href="<?php echo esc_url( home_url( '/eventos/pasados/' ) ); ?>">Eventos pasados</a>
<a href="<?php echo esc_url( home_url( '/fotosusuarios/' ) ); ?>">Fotos</a>
<details class="ft-account-menu"><summary>Mi cuenta</summary><div class="ft-account-links">
<a href="<?php echo esc_url( home_url( '/mi-cuenta/' ) ); ?>">Mi perfil</a>
<a href="<?php echo esc_url( home_url( '/mis-eventos/' ) ); ?>">Mis eventos</a>
<a href="<?php echo esc_url( home_url( '/mis-foodtrucks/' ) ); ?>">Mis foodtrucks</a>
<div class="ft-account-divider"></div>
<a href="<?php echo esc_url( home_url( '/sugerir-evento/' ) ); ?>">Sugerir evento</a>
<a href="<?php echo esc_url( home_url( '/agregar-foodtruck/' ) ); ?>">Agregar mi foodtruck</a>
<div class="ft-account-divider"></div>
<?php if ( is_user_logged_in() ) : ?>
<a href="<?php echo esc_url( wp_nonce_url( home_url( '/salir/' ), 'ftuy_account_logout' ) ); ?>">Cerrar sesión</a>
<?php else : ?>
<a href="<?php echo esc_url( FTUY_Accounts::login_url() ); ?>">Iniciar sesión</a>
<a class="ft-reactivate-link" href="<?php echo esc_url( home_url( '/reactivar-cuenta/' ) ); ?>">Reactivar cuenta</a>
<?php if ( FTUY_Accounts::registration_enabled() ) : ?><a href="<?php echo esc_url( home_url( '/registro/' ) ); ?>">Crear cuenta</a><?php endif; ?>
<?php endif; ?></div></details></nav>
