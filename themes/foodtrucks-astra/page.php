<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<main id="main">
<?php while ( have_posts() ) : the_post(); ?>
    <?php if ( ! is_front_page() ) : ?>
        <?php get_template_part( 'template-parts/page-header', null, array( 'title' => get_the_title() ) ); ?>
        <div class="ft-container ft-main">
    <?php endif; ?>
    <?php the_content(); ?>
    <?php if ( ! is_front_page() ) : ?></div><?php endif; ?>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>
