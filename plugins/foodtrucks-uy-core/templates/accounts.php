<?php
defined( 'ABSPATH' ) || exit;
$view = FTUY_Accounts::$view;
$titles = array( 'login' => 'Iniciar sesión', 'register' => 'Crear cuenta', 'forgot-password' => 'Recordar contraseña', 'reactivate' => 'Reactivar cuenta', 'reset' => 'Elegir contraseña', 'account' => 'Mi cuenta' );
$title = $titles[$view];
?>
<!doctype html><html lang="es-UY"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?php echo esc_html( $title ); ?> · Foodtrucks Uruguay</title><?php wp_print_styles( 'ftuy-events' ); ?></head><body class="ft-site">
<?php include FTUY_PATH . 'templates/header.php'; ?>
<section class="ft-titlebar"><div class="ft-container"><p class="ft-eyebrow">Foodtrucks Uruguay</p><h1><?php echo esc_html( $title ); ?></h1><p>Una misma cuenta para la web y la app.</p></div></section>
<main class="ft-container ft-main"><div class="ft-account-page">
<?php if ( in_array( $view, array( 'login', 'register' ), true ) ) : ?>
<section class="ft-reactivate-callout" aria-labelledby="ft-reactivate-title"><h2 id="ft-reactivate-title">¿Ya usabas la app anterior?</h2><p>Usá el email de tu cuenta anterior para recuperar tu acceso.</p><a class="ft-button" href="<?php echo esc_url( home_url( '/reactivar-cuenta/' ) ); ?>">Reactivar mi cuenta</a></section>
<?php endif; ?>
<?php if ( FTUY_Accounts::$error ) : ?><p class="ft-error" role="alert"><?php echo esc_html( FTUY_Accounts::$error ); ?></p><?php endif; ?>
<?php if ( FTUY_Accounts::$notice ) : ?><p class="ft-notice" role="status"><?php echo esc_html( FTUY_Accounts::$notice ); ?></p><?php endif; ?>
<?php if ( $view === 'account' && ! is_user_logged_in() ) : ?><p>Ingresá para gestionar tu cuenta, tus eventos y tus foodtrucks.</p><a class="ft-button" href="<?php echo esc_url( FTUY_Accounts::login_url() ); ?>">Iniciar sesión</a>
<?php elseif ( $view === 'account' ) : $user = wp_get_current_user(); ?>
<p><?php echo esc_html( $user->user_email ); ?></p>
<form method="post"><?php wp_nonce_field( 'ftuy_account_account' ); ?><label class="ft-field"><span>Nombre</span><input name="name" required maxlength="200" autocomplete="name" value="<?php echo esc_attr( $user->display_name ); ?>"></label><p><button class="ft-button">Guardar nombre</button></p></form>
<div class="ft-account-actions"><a class="ft-button ft-outline" href="<?php echo esc_url( home_url( '/mis-eventos/' ) ); ?>">Mis eventos</a><a class="ft-button ft-outline" href="<?php echo esc_url( home_url( '/mis-foodtrucks/' ) ); ?>">Mis foodtrucks</a></div>
<p><a href="<?php echo esc_url( home_url( '/recordar-contrasena/' ) ); ?>">Cambiar mi contraseña por email</a></p>
<?php elseif ( $view === 'register' && ! FTUY_Accounts::registration_enabled() ) : ?><p>El registro todavía no está habilitado.</p>
<?php elseif ( $view === 'reset' && is_wp_error( FTUY_Accounts::reset_user() ) ) : ?><p class="ft-error">El enlace no es válido, ya fue utilizado o venció.</p><a class="ft-button" href="<?php echo esc_url( home_url( '/recordar-contrasena/' ) ); ?>">Solicitar un enlace nuevo</a>
<?php else : ?>
<?php if ( $view === 'register' ) : ?><p>Ingresá tu nombre y email. Te enviaremos un enlace para confirmar la cuenta y elegir tu contraseña.</p><?php elseif ( $view === 'reactivate' ) : ?><p>¿Usabas la app anterior? Ingresá el email de aquella cuenta. Cuando esté migrada podrás reactivarla sin perder el vínculo con tus fotos.</p><?php elseif ( $view === 'forgot-password' ) : ?><p>Ingresá el email de tu cuenta y te enviaremos un enlace para elegir una contraseña nueva.</p><?php elseif ( $view === 'login' && isset( $_GET['updated'] ) ) : ?><p class="ft-notice">Tu contraseña quedó guardada. Ya podés ingresar.</p><?php endif; ?>
<form method="post"><?php wp_nonce_field( 'ftuy_account_' . $view ); ?>
<?php if ( $view === 'register' ) : ?><label class="ft-field"><span>Nombre</span><input name="name" required maxlength="200" autocomplete="name" value="<?php echo esc_attr( FTUY_Accounts::$name ); ?>"></label><?php endif; ?>
<?php if ( $view !== 'reset' ) : ?><label class="ft-field"><span>Email</span><input type="email" name="email" required maxlength="100" autocomplete="email" value="<?php echo esc_attr( FTUY_Accounts::$email ); ?>"></label><?php endif; ?>
<?php if ( in_array( $view, array( 'login', 'reset' ), true ) ) : ?><label class="ft-field"><span><?php echo $view === 'reset' ? 'Nueva contraseña' : 'Contraseña'; ?></span><input type="password" name="password" required <?php if ( $view === 'reset' ) { echo 'minlength="8"'; } ?> maxlength="4096" autocomplete="<?php echo $view === 'reset' ? 'new-password' : 'current-password'; ?>"></label><?php endif; ?>
<?php if ( $view === 'reset' ) : ?><label class="ft-field"><span>Repetir contraseña</span><input type="password" name="password_confirm" required minlength="8" maxlength="4096" autocomplete="new-password"></label><p>Usá al menos 8 caracteres.</p><?php endif; ?>
<?php if ( $view === 'login' ) : ?><input type="hidden" name="redirect_to" value="<?php echo esc_attr( FTUY_Accounts::target( $_GET['redirect_to'] ?? '' ) ); ?>"><p><label><input type="checkbox" name="remember" value="1"> Mantener mi sesión</label></p><?php endif; ?>
<p><button class="ft-button" type="submit"><?php echo $view === 'login' ? 'Ingresar' : ( $view === 'reset' ? 'Guardar contraseña' : 'Enviar enlace por email' ); ?></button></p></form>
<?php endif; ?>
<div class="ft-account-bottom"><a href="<?php echo esc_url( home_url( '/ingresar/' ) ); ?>">Iniciar sesión</a><?php if ( FTUY_Accounts::registration_enabled() ) : ?><a href="<?php echo esc_url( home_url( '/registro/' ) ); ?>">Crear cuenta</a><?php endif; ?><a href="<?php echo esc_url( home_url( '/recordar-contrasena/' ) ); ?>">Recordar contraseña</a><a href="<?php echo esc_url( home_url( '/reactivar-cuenta/' ) ); ?>">Reactivar cuenta</a></div>
</div></main><footer class="ft-footer"><div class="ft-container">Foodtrucks Uruguay · Una comunidad para encontrarnos alrededor de la comida.</div></footer></body></html>
