<?php
defined( 'ABSPATH' ) || exit;
get_header();
get_template_part( 'template-parts/page-header', null, array(
    'title' => 'Novedades',
    'description' => 'Noticias e historias de la comunidad gastronómica sobre ruedas.',
) );
?>
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
