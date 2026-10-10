<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<main id="main"><?php while ( have_posts() ) : the_post(); if ( ! is_front_page() ) : ?><div class="ft-container ft-main"><h1><?php the_title(); ?></h1><?php endif; the_content(); if ( ! is_front_page() ) : ?></div><?php endif; endwhile; ?></main>
<?php get_footer(); ?>
