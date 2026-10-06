<?php
defined( 'ABSPATH' ) || exit;
$truck = FTUY_Foodtruck_Public::$foodtruck; $preview = FTUY_Foodtruck_Public::$preview;
$not_found = FTUY_Foodtruck_Public::$not_found;
$view = FTUY_Foodtruck_Public::$view;
$private = in_array( $view, array( 'add', 'mine' ), true );
$title = $view === 'add' ? 'Agregar mi foodtruck' : ( $view === 'mine' ? 'Mis foodtrucks' : ( $not_found ? 'Foodtruck no encontrado' : ( $truck ? $truck['name'] : 'Foodtrucks' ) ) );
$catalog_url = $preview ? FTUY_Foodtruck_Public::preview_url() : home_url( '/foodtrucks/' );
?>
<!doctype html><html lang="es-UY"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?php echo esc_html( $title ); ?> · Foodtrucks Uruguay</title>
<?php if ( $preview || $not_found || $private ) : ?><meta name="robots" content="noindex,nofollow"><?php else : ?><link rel="canonical" href="<?php echo esc_url( $truck ? FTUY_Foodtruck_Public::url( $truck ) : $catalog_url ); ?>"><meta name="description" content="<?php echo esc_attr( $truck ? wp_trim_words( wp_strip_all_tags( $truck['food_offering'] ), 25 ) : 'Descubrí foodtrucks por rubro gastronómico y departamento. Conocé qué sirven y contactalos para tu evento.' ); ?>"><?php endif; ?>
<?php wp_print_styles( array( 'ftuy-foodtrucks', 'ftuy-truck-form' ) ); ?></head><body class="ft-site">
<?php include FTUY_PATH . 'templates/header.php'; ?>
<?php if ( $preview ) : ?><div class="ft-preview">Vista previa privada · Incluye propuestas pendientes y muestras históricas. No es el catálogo público.</div><?php endif; ?>
<section class="ft-titlebar"><div class="ft-container"><p class="ft-eyebrow">Sabores sobre ruedas</p><h1><?php echo esc_html( $title ); ?></h1><?php if ( ! $truck ) : ?><p><?php echo esc_html( $view === 'add' ? 'Compartí tu propuesta gastronómica con la comunidad.' : ( $view === 'mine' ? 'Gestioná tus fichas y seguí el estado de revisión.' : 'Conocé qué sirven y encontrá una propuesta para tu próximo evento.' ) ); ?></p><?php endif; ?></div></section>
<main class="ft-container ft-main">
<?php if ( $not_found ) : ?><div class="ft-empty"><h2>Esta ficha no está disponible</h2><p>Podés explorar los foodtrucks publicados en el directorio.</p><a class="ft-button" href="<?php echo esc_url( $catalog_url ); ?>">Ver foodtrucks</a></div>
<?php elseif ( $view === 'add' ) : FTUY_Foodtruck_Submissions::form(); ?>
<?php elseif ( $view === 'mine' ) : FTUY_Foodtruck_Submissions::mine(); ?>
<?php elseif ( ! $truck ) :
    $department = sanitize_text_field( wp_unslash( $_GET['departamento'] ?? '' ) ); if ( ! in_array( $department, FTUY_Events::departments(), true ) ) { $department = ''; }
    $cuisine = absint( $_GET['rubro'] ?? 0 ); if ( ! in_array( $cuisine, array_map( 'intval', array_column( FTUY_Foodtrucks::cuisines(), 'id' ) ), true ) ) { $cuisine = 0; }
    $page = max( 1, absint( $_GET['ft_page'] ?? 1 ) ); $result = FTUY_Foodtruck_Public::listing( $department, $cuisine, $page, $preview );
?>
<form class="ft-truck-filters" method="get" action="<?php echo esc_url( home_url( '/foodtrucks/' ) ); ?>">
<?php if ( $preview ) : ?><input type="hidden" name="ft_truck_preview" value="1"><input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'ftuy_truck_preview' ) ); ?>"><?php endif; ?>
<label>Departamento<select name="departamento"><option value="">Todos los departamentos</option><?php foreach ( FTUY_Events::departments() as $d ) : ?><option <?php selected( $department, $d ); ?>><?php echo esc_html( $d ); ?></option><?php endforeach; ?></select></label>
<label>Rubro gastronómico<select name="rubro"><option value="">Todos los rubros</option><?php foreach ( FTUY_Foodtrucks::cuisines() as $c ) : ?><option value="<?php echo (int) $c['id']; ?>" <?php selected( $cuisine, $c['id'] ); ?>><?php echo esc_html( $c['name'] ); ?></option><?php endforeach; ?></select></label><button class="ft-button" type="submit">Filtrar</button><a href="<?php echo esc_url( $catalog_url ); ?>">Limpiar filtros</a></form>
<p class="ft-result-count"><?php echo (int) $result['total']; ?> foodtrucks<?php if ( $preview ) : ?> · vista previa<?php endif; ?></p>
<?php if ( $result['items'] ) : ?><div class="ft-truck-cards"><?php foreach ( $result['items'] as $e ) : $url = FTUY_Foodtruck_Public::url( $e, $preview ); ?>
<article class="ft-truck-card"><a class="ft-truck-card-cover" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( 'Ver ' . $e['name'] ); ?>"><?php echo FTUY_Foodtruck_Public::image( FTUY_Foodtruck_Public::image_id( $e, 'truck_photo' ) ?: FTUY_Foodtruck_Public::image_id( $e, 'logo' ), $e['name'] ); ?></a><div class="ft-truck-card-body"><p class="ft-truck-rubros"><?php echo esc_html( implode( ' · ', FTUY_Foodtruck_Public::cuisines( $e ) ) ); ?></p><h2><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $e['name'] ); ?></a></h2><p class="ft-truck-base"><?php echo esc_html( $e['locality'] . ', ' . $e['department'] ); ?></p><p class="ft-truck-offering"><?php echo esc_html( $e['food_offering'] ); ?></p><a class="ft-more" href="<?php echo esc_url( $url ); ?>">Conocer foodtruck →</a></div></article>
<?php endforeach; ?></div><?php else : ?><div class="ft-empty"><h2><?php echo $department || $cuisine ? 'No encontramos foodtrucks con estos filtros' : 'Estamos preparando el directorio'; ?></h2><p><?php echo $department || $cuisine ? 'Probá otro departamento o rubro gastronómico.' : 'Las fichas aparecerán aquí cuando estén revisadas y publicadas.'; ?></p><?php if ( $department || $cuisine ) : ?><a class="ft-button ft-outline" href="<?php echo esc_url( $catalog_url ); ?>">Ver todos</a><?php endif; ?></div><?php endif; ?>
<?php if ( ! $preview && current_user_can( 'manage_ft_foodtrucks' ) ) : ?><p><a class="ft-button ft-outline" href="<?php echo esc_url( FTUY_Foodtruck_Public::preview_url() ); ?>">Ver catálogo de prueba privado</a></p><?php endif; ?>
<nav class="ft-pagination" aria-label="Páginas de foodtrucks"><?php echo paginate_links( array( 'base' => add_query_arg( 'ft_page', '%#%' ), 'current' => $page, 'total' => $result['pages'] ) ); ?></nav>
<?php else : ?>
<p class="ft-back"><a href="<?php echo esc_url( $catalog_url ); ?>">← Volver a foodtrucks</a></p>
<div class="ft-detail-layout"><article class="ft-truck-detail">
<?php $cover = FTUY_Foodtruck_Public::image_id( $truck, 'truck_photo' ); if ( $cover ) : ?><a class="ft-truck-cover" href="<?php echo esc_url( wp_get_attachment_image_url( $cover, 'full' ) ); ?>" target="_blank" rel="noopener" aria-label="Ampliar foto del foodtruck"><?php echo FTUY_Foodtruck_Public::image( $cover, $truck['name'], '', 'eager' ); ?></a><?php endif; ?>
<div class="ft-truck-identity"><?php $logo = FTUY_Foodtruck_Public::image_id( $truck, 'logo' ); if ( $logo ) { echo FTUY_Foodtruck_Public::image( $logo, 'Logo de ' . $truck['name'], 'ft-truck-logo', 'eager' ); } ?><div><p class="ft-truck-rubros"><?php echo esc_html( implode( ' · ', FTUY_Foodtruck_Public::cuisines( $truck ) ) ); ?></p><h2><?php echo esc_html( $truck['name'] ); ?></h2></div></div>
<section class="ft-truck-text"><h2>Qué sirven</h2><p><?php echo nl2br( esc_html( $truck['food_offering'] ) ); ?></p><h2>Sobre este foodtruck</h2><?php echo wpautop( wp_kses_post( $truck['description'] ) ); ?></section>
</article><aside class="ft-details-panel"><h2>Conocé y contactá</h2><dl><dt>Base</dt><dd><?php echo esc_html( $truck['locality'] . ', ' . $truck['department'] ); ?></dd><dt>Modalidades</dt><dd><?php echo esc_html( implode( ' · ', FTUY_Foodtruck_Public::modes( $truck ) ) ); ?></dd></dl><p class="ft-truck-help">La base no indica dónde está hoy el foodtruck. Consultá disponibilidad para tu evento.</p>
<?php if ( $truck['whatsapp'] ) : ?><a class="ft-button" href="<?php echo esc_url( 'https://wa.me/' . $truck['whatsapp'] . '?text=' . rawurlencode( 'Hola, vi ' . $truck['name'] . ' en Foodtrucks Uruguay y quisiera consultar disponibilidad.' ) ); ?>" target="_blank" rel="noopener">Consultar por WhatsApp ↗</a><?php endif; ?>
<?php if ( $truck['instagram'] ) : ?><a class="ft-button ft-outline" href="<?php echo esc_url( $truck['instagram'] ); ?>" target="_blank" rel="noopener">Ver Instagram ↗</a><?php endif; ?>
<?php if ( ! $truck['whatsapp'] && ! $truck['instagram'] ) : ?><p>Este foodtruck todavía no tiene un contacto público cargado.</p><?php endif; ?>
</aside></div>
<?php endif; ?></main><footer class="ft-footer"><div class="ft-container"><strong>Foodtrucks Uruguay</strong><p>Una comunidad para encontrarnos alrededor de la comida.</p><a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>">Explorá los próximos eventos.</a></div></footer><?php if ( $view === 'add' && is_user_logged_in() ) { wp_print_scripts( 'ftuy-trucks' ); } ?></body></html>
