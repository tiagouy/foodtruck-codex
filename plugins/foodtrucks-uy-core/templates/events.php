<?php
defined( 'ABSPATH' ) || exit;
$view = FTUY_Public::$view;
$event = FTUY_Public::$event;
$titles = array( 'list' => 'Eventos', 'past' => 'Eventos pasados', 'suggest' => 'Sugerir un evento', 'mine' => 'Mis eventos' );
$title = $event ? $event['title'] : $titles[$view];
?>
<!doctype html>
<html lang="es-UY">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $title ); ?> · Foodtrucks Uruguay</title>
<?php if ( FTUY_Public::$preview || in_array( $view, array( 'mine', 'suggest' ), true ) ) : ?><meta name="robots" content="noindex,nofollow"><?php else : ?>
<link rel="canonical" href="<?php echo esc_url( $event ? FTUY_Events::url( $event ) : home_url( $view === 'past' ? '/eventos/pasados/' : '/eventos/' ) ); ?>">
<?php endif; ?>
<?php wp_print_styles( 'ftuy-events' ); ?>
</head>
<body class="ft-site">
<header class="ft-header"><div class="ft-container ft-header-inner"><a class="ft-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">FOODTRUCKS<span>URUGUAY</span></a><nav aria-label="Navegación principal"><a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>">Eventos</a><a href="<?php echo esc_url( home_url( '/eventos/pasados/' ) ); ?>">Pasados</a><a href="<?php echo esc_url( home_url( '/mis-eventos/' ) ); ?>">Mis eventos</a><a class="ft-button" href="<?php echo esc_url( home_url( '/sugerir-evento/' ) ); ?>">Sugerir evento</a></nav></div></header>
<?php if ( FTUY_Public::$preview ) : ?><div class="ft-preview">Vista previa privada · Esta propuesta todavía no está publicada.</div><?php endif; ?>
<section class="ft-titlebar"><div class="ft-container"><p class="ft-eyebrow">Agenda de Foodtrucks Uruguay</p><h1><?php echo esc_html( $title ); ?></h1><?php if ( ! $event ) : ?><p>Encuentros, sabores y experiencias para descubrir Uruguay.</p><?php endif; ?></div></section>
<main class="ft-container ft-main">
<?php if ( in_array( $view, array( 'list', 'past' ), true ) ) :
    $dep = sanitize_text_field( wp_unslash( $_GET['departamento'] ?? '' ) );
    if ( ! in_array( $dep, FTUY_Events::departments(), true ) ) { $dep = ''; }
    $page = max( 1, absint( $_GET['ft_page'] ?? 1 ) );
    $result = FTUY_Events::listing( $view === 'past' ? 'past' : 'upcoming', $dep, $page );
?>
<div class="ft-toolbar"><div class="ft-tabs"><a class="<?php echo $view === 'list' ? 'active' : ''; ?>" href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>">Próximos y en curso</a><a class="<?php echo $view === 'past' ? 'active' : ''; ?>" href="<?php echo esc_url( home_url( '/eventos/pasados/' ) ); ?>">Eventos pasados</a></div><form method="get" class="ft-filter"><label for="ft-dep">Departamento</label><select id="ft-dep" name="departamento"><option value="">Todos los departamentos</option><?php foreach ( FTUY_Events::departments() as $d ) : ?><option <?php selected( $d, $dep ); ?>><?php echo esc_html( $d ); ?></option><?php endforeach; ?></select><button class="ft-button ft-outline">Filtrar</button></form></div>
<p class="ft-result-count"><?php echo (int) $result['total']; ?> eventos<?php echo $dep ? ' en ' . esc_html( $dep ) : ''; ?></p>
<?php if ( $result['items'] ) : FTUY_Public::cards( $result['items'] ); else : ?>
<div class="ft-empty"><h2><?php echo $view === 'past' ? 'No hay eventos pasados para este filtro' : 'La próxima fecha puede ser la tuya'; ?></h2><p><?php echo $view === 'past' ? 'Probá otro departamento para explorar el histórico.' : 'Todavía no hay eventos próximos publicados para este filtro. Podés explorar los encuentros anteriores o sugerir uno nuevo.'; ?></p><a class="ft-button" href="<?php echo esc_url( home_url( $view === 'past' ? '/eventos/pasados/' : '/sugerir-evento/' ) ); ?>"><?php echo $view === 'past' ? 'Ver todos' : 'Sugerir un evento'; ?></a><?php if ( $view === 'list' ) : ?> <a class="ft-button ft-outline" href="<?php echo esc_url( home_url( '/eventos/pasados/' ) ); ?>">Ver histórico</a><?php endif; ?></div>
<?php endif; ?>
<nav class="ft-pagination" aria-label="Páginas de eventos"><?php echo paginate_links( array( 'base' => add_query_arg( 'ft_page', '%#%' ), 'current' => $page, 'total' => $result['pages'] ) ); ?></nav>
<?php elseif ( $view === 'detail' ) : ?>
<p class="ft-back"><a href="<?php echo esc_url( home_url( FTUY_Events::temporal( $event ) === 'past' ? '/eventos/pasados/' : '/eventos/' ) ); ?>">← Volver a eventos</a></p>
<div class="ft-detail-layout"><article class="ft-detail"><a class="ft-poster" target="_blank" rel="noopener" aria-label="Ampliar imagen del evento" href="<?php echo esc_url( wp_get_attachment_image_url( $event['image_id'], 'full' ) ); ?>"><?php echo FTUY_Public::image( $event['image_id'], 'large', $event['title'], 'eager' ); ?></a><div class="ft-detail-text"><?php echo wpautop( wp_kses_post( $event['description'] ) ); ?></div>
<?php if ( $event['address'] ) : ?><section class="ft-location"><h2>Dónde será</h2><p><?php echo esc_html( $event['address'] ); ?></p><a class="ft-button ft-outline" href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $event['latitude'] !== null && $event['longitude'] !== null ? $event['latitude'] . ',' . $event['longitude'] : $event['address'] . ', Uruguay' ) ); ?>" target="_blank" rel="noopener">Abrir ubicación en el mapa ↗</a></section><?php endif; ?></article>
<aside class="ft-details-panel"><h2>Detalles del evento</h2><span class="ft-status"><?php echo esc_html( FTUY_Public::status_label( $event ) ); ?></span><dl><dt>Fecha</dt><dd><?php echo esc_html( FTUY_Public::dates( $event ) ); ?></dd><dt>Horario</dt><dd><?php FTUY_Form::hours( $event ); ?></dd><dt>Departamento</dt><dd><?php echo esc_html( $event['department'] ); ?></dd><dt>Lugar</dt><dd><?php echo esc_html( $event['venue'] ?: $event['address'] ); ?></dd><?php if ( $event['organizer'] ) : ?><dt>Organiza</dt><dd><?php echo esc_html( $event['organizer'] ); ?></dd><?php endif; ?><?php if ( $event['price'] || ( $event['entry_type'] ?? '' ) === 'paid' ) : ?><dt>Entrada</dt><dd><?php echo esc_html( $event['price'] ?: 'Con entrada' ); ?></dd><?php endif; ?></dl><?php if ( $event['tickets_url'] ) : ?><a class="ft-button" href="<?php echo esc_url( $event['tickets_url'] ); ?>" target="_blank" rel="noopener">Información de entradas ↗</a><?php endif; ?><?php if ( ! empty( $event['instagram'] ) ) : ?><a class="ft-button ft-outline" href="<?php echo esc_url( $event['instagram'] ); ?>" target="_blank" rel="noopener">Instagram · @<?php echo esc_html( trim( wp_parse_url( $event['instagram'], PHP_URL_PATH ), '/' ) ); ?> ↗</a><?php endif; ?><?php if ( $event['website'] ) : ?><a class="ft-more" href="<?php echo esc_url( $event['website'] ); ?>" target="_blank" rel="noopener">Sitio web ↗</a><?php endif; ?><?php if ( ! FTUY_Public::$preview ) : ?><a class="ft-button ft-outline" href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( $event['title'] . ' ' . FTUY_Events::url( $event ) ) ); ?>" target="_blank" rel="noopener">Compartir por WhatsApp</a><?php endif; ?></aside></div>
<?php elseif ( $view === 'suggest' ) : ?>
<?php if ( ! is_user_logged_in() ) : FTUY_Public::login(); else : ?>
<div class="ft-form-intro"><h2><?php echo isset( $_GET['edit'] ) ? 'Proponer cambios' : 'Contanos sobre tu evento'; ?></h2><p>Completá los datos y adjuntá el afiche. Revisaremos la propuesta antes de publicarla. Los campos con * son obligatorios.</p></div>
<?php if ( FTUY_Public::$error ) : ?><p class="ft-error" role="alert"><?php echo esc_html( FTUY_Public::$error ); ?></p><?php endif; ?>
<form class="ft-event-form" method="post" enctype="multipart/form-data"><?php wp_nonce_field( 'ftuy_suggest' ); FTUY_Admin::fields( FTUY_Public::$form_data, true ); ?><p>La revisión se notificará al email de tu cuenta. Si ya está publicado, seguirá visible hasta que aprobemos los cambios.</p><button class="ft-button" type="submit">Enviar a revisión</button></form>
<?php endif; ?>
<?php elseif ( $view === 'mine' ) : FTUY_Public::mine(); endif; ?>
</main>
<footer class="ft-footer"><div class="ft-container"><strong>Foodtrucks Uruguay</strong><p>Una comunidad para encontrarnos alrededor de la comida.</p><a href="<?php echo esc_url( home_url( '/sugerir-evento/' ) ); ?>">¿Organizás un evento? Contanos.</a></div></footer>
<?php if ( $view === 'suggest' && is_user_logged_in() ) { wp_print_scripts( 'ftuy-event-form' ); } ?>
</body></html>
