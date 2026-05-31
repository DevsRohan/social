<?php
/**
 * Template: Recent Purchase Line
 *
 * Override: yourtheme/social-proof-live/widget-purchase.php
 *
 * @package SocialProofLive
 * @var array $settings Plugin settings.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="splive-line splive-purchase" data-type="purchase" style="display:none;">
    <span class="splive-icon"><?php echo esc_html( $settings['icon_purchase'] ); ?></span>
    <span class="splive-text"></span>
</div>
