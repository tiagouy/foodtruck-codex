<?php defined( 'ABSPATH' ) || exit; ?>
<footer class="ft-footer"><div class="ft-container ft-footer-grid">
<section><h2>Contacto</h2><p>Montevideo, Uruguay</p><a href="mailto:info@foodtruckuruguay.com">info@foodtruckuruguay.com</a><div class="ft-social-links">
<a href="https://www.instagram.com/foodtrucksuy/" target="_blank" rel="noopener" aria-label="Instagram de Foodtrucks Uruguay"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor"/></svg></a>
<a href="https://www.facebook.com/FoodTruckUy" target="_blank" rel="noopener" aria-label="Facebook de Foodtrucks Uruguay"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M14 22v-9h3l.5-4H14V7c0-1.2.4-2 2-2h2V1.4C17.4 1.2 16.3 1 15 1c-3 0-5 1.8-5 5v3H7v4h3v9z"/></svg></a>
</div></section>
<section><h2>Últimas novedades</h2><?php foreach ( get_posts( array( 'post_type' => 'post', 'numberposts' => 3, 'post_status' => 'publish' ) ) as $news ) : ?><p><a href="<?php echo esc_url( get_permalink( $news ) ); ?>"><?php echo esc_html( $news->post_title ); ?></a><br><small><?php echo esc_html( get_the_date( 'd/m/Y', $news ) ); ?></small></p><?php endforeach; ?></section>
<section><h2>Descargá nuestra app</h2><p>Encontrá eventos, descubrí foodtrucks y compartí tus fotos.</p>
<?php include FTUY_PATH . 'templates/store-buttons.php'; ?></section>
</div><div class="ft-container ft-footer-bottom">Foodtrucks Uruguay · Una comunidad para encontrarnos alrededor de la comida.</div></footer>
