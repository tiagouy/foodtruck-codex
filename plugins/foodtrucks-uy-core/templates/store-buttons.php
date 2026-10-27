<?php defined( 'ABSPATH' ) || exit; ?>
<div class="ft-store-buttons">
<?php
$links = get_option( 'ftuy_store_links', array() );
foreach ( array( 'ios' => 'App Store', 'android' => 'Google Play' ) as $platform => $label ) :
    $url = is_array( $links ) && ! empty( $links['enabled'] ) && ! empty( $links[$platform] ) ? esc_url( $links[$platform], array( 'https' ) ) : '';
    if ( $url ) : ?><a class="ft-store-button" href="<?php echo $url; ?>" target="_blank" rel="noopener"><?php else : ?><button class="ft-store-button" type="button" disabled aria-label="<?php echo esc_attr( $label . ': próximamente' ); ?>"><?php endif; ?>
    <?php if ( $platform === 'ios' ) : ?>
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17.05 12.54c.03 3.15 2.76 4.2 2.79 4.22-.02.07-.44 1.5-1.44 2.98-.87 1.28-1.77 2.56-3.2 2.59-1.41.03-1.86-.83-3.48-.83-1.61 0-2.11.8-3.45.86-1.38.05-2.44-1.39-3.32-2.67-1.8-2.61-3.18-7.39-1.33-10.62a5.16 5.16 0 0 1 4.37-2.65c1.36-.03 2.65.92 3.48.92.84 0 2.4-1.14 4.05-.97.69.03 2.62.28 3.86 2.1-.1.06-2.31 1.35-2.33 4.07M14.38 4.63c.73-.89 1.22-2.13 1.09-3.36-1.05.04-2.33.7-3.09 1.59-.68.79-1.28 2.06-1.12 3.27 1.17.09 2.37-.6 3.12-1.5"/></svg>
    <?php else : ?>
    <svg viewBox="0 0 24 26" aria-hidden="true" focusable="false"><path fill="#4bc3ef" d="M2 1v24l12-12z"/><path fill="#54d187" d="M2 1l15 8-3 4z"/><path fill="#ffd35b" d="M17 9l5 3q2 1 0 2l-5 3-3-4z"/><path fill="#f96c75" d="M2 25l15-8-3-4z"/></svg>
    <?php endif; ?>
    <span><small><?php echo $url ? 'Descargá en' : 'Próximamente en'; ?></small><strong><?php echo esc_html( $label ); ?></strong></span>
    <?php echo $url ? '</a>' : '</button>'; ?>
<?php endforeach; ?>
</div>
