<?php
defined( 'ABSPATH' ) || exit;
$row = FTUY_Publication_Public::$row; $missing = FTUY_Publication_Public::$missing;
$title = $missing ? 'Publicación no disponible' : ( $row ? 'Foto de la comunidad' : 'Fotos de la comunidad' );
$list_url = home_url( '/fotosusuarios/' );
$canonical = $row ? FTUY_Publication_Public::url( $row ) : $list_url;
if ( ! $row && ! $missing && FTUY_Publication_Public::$list['page'] > 1 ) { $canonical = add_query_arg( 'pagina', FTUY_Publication_Public::$list['page'], $canonical ); }
if ( ! $row && FTUY_Publication_Public::$author ) { $canonical = add_query_arg( 'autor', FTUY_Publication_Public::$author, $canonical ); }
$description = $row ? wp_trim_words( $row['caption'], 30 ) : 'Momentos compartidos por la comunidad de Foodtrucks Uruguay.';
?>
<!doctype html><html lang="es-UY"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?php echo esc_html( $title ); ?> · Foodtrucks Uruguay</title>
<?php if ( $missing ) : ?><meta name="robots" content="noindex,nofollow">
<?php else : ?>
<link rel="canonical" href="<?php echo esc_url( $canonical ); ?>"><meta name="description" content="<?php echo esc_attr( $description ); ?>"><meta property="og:title" content="<?php echo esc_attr( $title . ' · Foodtrucks Uruguay' ); ?>"><meta property="og:type" content="website"><meta property="og:url" content="<?php echo esc_url( $canonical ); ?>"><meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
<?php if ( $row ) : $image_url = wp_get_attachment_image_url( $row['image_id'], 'large' ); ?>
<meta property="og:image" content="<?php echo esc_url( $image_url ); ?>"><meta name="twitter:card" content="summary_large_image"><meta name="twitter:image" content="<?php echo esc_url( $image_url ); ?>">
<?php endif; endif; ?>
<?php wp_print_styles( array( 'ftuy-events', 'ftuy-publications' ) ); ?><script defer src="<?php echo esc_url( add_query_arg( 'ver', FOODTRUCKS_UY_CORE_VERSION, FTUY_URL . 'assets/publications.js' ) ); ?>"></script></head><body class="ft-site ft-photo-site">
<?php include FTUY_PATH . 'templates/header.php'; ?>
<section class="ft-titlebar"><div class="ft-container"><p class="ft-eyebrow">Nuestra comunidad</p><h1><?php echo esc_html( $title ); ?></h1><?php if ( ! $row && ! $missing ) : ?><p>Fotos y momentos compartidos desde la app.</p><?php endif; ?></div></section>
<main class="ft-container ft-main">
<?php if ( $missing ) : ?>
<div class="ft-empty"><h2>Esta publicación no está disponible</h2><p>Podés ver otras fotos publicadas por la comunidad.</p><a class="ft-button" href="<?php echo esc_url( $list_url ); ?>">Ver fotos</a></div>
<?php elseif ( $row ) : $author = get_user_by( 'id', $row['author_user_id'] ); ?>
<p class="ft-back"><a href="<?php echo esc_url( $list_url ); ?>">← Todas las fotos</a></p>
<article class="ft-photo-detail">
<div class="ft-photo-frame"><?php echo FTUY_Publication_Public::image( $row['image_id'], 'large', 'Foto compartida por la comunidad', 'eager', '(max-width:650px) 100vw, 66vw' ); ?></div>
<div class="ft-photo-info"><div class="ft-photo-author"><?php echo FTUY_Publication_Public::avatar( $row['author_user_id'], 'eager', 44 ); ?><a href="<?php echo esc_url( add_query_arg( 'autor', $row['author_user_id'], $list_url ) ); ?>"><?php echo esc_html( $author ? $author->display_name : 'Usuario' ); ?></a></div><p class="ft-photo-meta"><?php echo FTUY_Publication_Public::meta( $row ); ?></p>
<p class="ft-photo-caption"><?php echo nl2br( esc_html( $row['caption'] ) ); ?></p>
<p><label class="ft-photo-share">Enlace para compartir<input readonly value="<?php echo esc_attr( FTUY_Publication_Public::url( $row ) ); ?>" aria-label="Enlace de esta foto" type="url"></label></p>
<p class="ft-photo-hint">Las fotos se suben desde la app.</p></div></article>
<?php else : $list = FTUY_Publication_Public::$list; ?>
<?php if ( FTUY_Publication_Public::$author ) : ?><p><a href="<?php echo esc_url( $list_url ); ?>">Ver todas las fotos</a></p><?php endif; ?>
<?php if ( $list['items'] ) : ?><div class="ft-photo-grid">
<?php foreach ( $list['items'] as $photo ) : $author = get_user_by( 'id', $photo['author_user_id'] ); ?>
<article class="ft-photo-card"><a class="ft-photo-card-image" href="<?php echo esc_url( FTUY_Publication_Public::url( $photo ) ); ?>" aria-label="Ver foto de la comunidad"><?php echo FTUY_Publication_Public::image( $photo['image_id'], 'medium_large', 'Foto compartida por la comunidad' ); ?></a><div class="ft-photo-card-body"><div class="ft-photo-author"><?php echo FTUY_Publication_Public::avatar( $photo['author_user_id'] ); ?><a class="ft-photo-name" href="<?php echo esc_url( add_query_arg( 'autor', $photo['author_user_id'], $list_url ) ); ?>"><?php echo esc_html( $author ? $author->display_name : 'Usuario' ); ?></a></div><p class="ft-photo-meta"><?php echo FTUY_Publication_Public::meta( $photo ); ?></p><?php if ( $photo['caption'] ) : ?><p class="ft-photo-card-caption"><?php echo esc_html( $photo['caption'] ); ?></p><?php endif; ?></div></article>
<?php endforeach; ?></div>
<nav class="ft-pagination" aria-label="Páginas de fotos">
<?php $base = FTUY_Publication_Public::$author ? add_query_arg( 'autor', FTUY_Publication_Public::$author, $list_url ) : $list_url; ?>
<?php if ( $list['page'] > 1 ) : ?><a href="<?php echo esc_url( add_query_arg( 'pagina', $list['page'] - 1, $base ) ); ?>">Anterior</a><?php endif; ?>
<span class="current" aria-current="page"><?php echo (int) $list['page']; ?></span>
<?php if ( $list['page'] * 12 < $list['total'] ) : ?><a href="<?php echo esc_url( add_query_arg( 'pagina', $list['page'] + 1, $base ) ); ?>">Siguiente</a><?php endif; ?></nav>
<?php else : ?><div class="ft-empty"><h2>Todavía no hay fotos publicadas<?php echo FTUY_Publication_Public::$author ? ' de esta persona' : ''; ?></h2><p>Las fotos aparecerán aquí cuando estén disponibles.</p></div><?php endif; ?>
<?php endif; ?>
</main><footer class="ft-footer"><div class="ft-container">Foodtrucks Uruguay · Una comunidad para encontrarnos alrededor de la comida.</div></footer></body></html>
