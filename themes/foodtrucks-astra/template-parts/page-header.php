<?php
defined( 'ABSPATH' ) || exit;
$title = $args['title'] ?? get_the_title();
$description = $args['description'] ?? '';
?>
<section class="ft-titlebar">
    <div class="ft-container">
        <p class="ft-eyebrow">Comunidad Foodtrucks Uruguay</p>
        <h1><?php echo esc_html( $title ); ?></h1>
        <?php if ( $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?>
    </div>
</section>
