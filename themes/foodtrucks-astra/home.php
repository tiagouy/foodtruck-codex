<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="ft-titlebar">
    <div class="ft-container">
        <p class="ft-eyebrow">Comunidad Foodtrucks Uruguay</p>
        <h1>Novedades</h1>
        <p>Noticias e historias de la comunidad gastronómica sobre ruedas.</p>
    </div>
</section>
<div class="ft-container ft-blog-layout">
<?php if ( astra_page_layout() === 'left-sidebar' ) { get_sidebar(); } ?>
<div id="primary" <?php astra_primary_class(); ?>>
    <?php
    astra_primary_content_top();
    astra_content_loop();
    astra_pagination();
    astra_primary_content_bottom();
    ?>
</div>
<?php
if ( astra_page_layout() === 'right-sidebar' ) { get_sidebar(); }
?>
</div>
<?php get_footer(); ?>
