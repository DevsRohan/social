<?php
/**
 * Template: Cart Count Line
 *
 * Override: yourtheme/social-proof-live/widget-cart.php
 *
 * @package SocialProofLive
 * @var array $settings Plugin settings.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="splive-line splive-cart" data-type="cart" style="display:none;">
    <span class="splive-icon"><?php echo esc_html( $settings['icon_cart'] ); ?></span>
    <span class="splive-text"></span>
</div>
