<?php
/**
 * Template: Widget Wrapper
 *
 * This template can be overridden by copying it to:
 * yourtheme/social-proof-live/widget-wrapper.php
 *
 * @package SocialProofLive
 * @var int    $product_id Product ID.
 * @var string $theme      Widget theme.
 * @var string $animation  Animation style.
 * @var string $scheme     Color scheme.
 * @var array  $settings   Plugin settings.
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="splive-widget"
     class="splive-widget splive-theme-<?php echo esc_attr( $theme ); ?> splive-scheme-<?php echo esc_attr( $scheme ); ?>"
     data-product-id="<?php echo esc_attr( $product_id ); ?>"
     data-animation="<?php echo esc_attr( $animation ); ?>"
     style="display:none;"
     aria-live="polite"
     aria-atomic="true">

    <?php if ( ! empty( $settings['enable_viewers'] ) ) : ?>
    <div class="splive-line splive-viewers" data-type="viewers" style="display:none;">
        <span class="splive-icon"><?php echo esc_html( $settings['icon_viewers'] ); ?></span>
        <span class="splive-text"></span>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $settings['enable_cart'] ) ) : ?>
    <div class="splive-line splive-cart" data-type="cart" style="display:none;">
        <span class="splive-icon"><?php echo esc_html( $settings['icon_cart'] ); ?></span>
        <span class="splive-text"></span>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $settings['enable_purchase'] ) ) : ?>
    <div class="splive-line splive-purchase" data-type="purchase" style="display:none;">
        <span class="splive-icon"><?php echo esc_html( $settings['icon_purchase'] ); ?></span>
        <span class="splive-text"></span>
    </div>
    <?php endif; ?>

</div>
