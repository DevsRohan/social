<?php
/**
 * Template: Viewer Count Line
 *
 * Override: yourtheme/social-proof-live/widget-viewers.php
 *
 * @package SocialProofLive
 * @var array $settings Plugin settings.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="splive-line splive-viewers" data-type="viewers" style="display:none;">
    <span class="splive-icon"><?php echo esc_html( $settings['icon_viewers'] ); ?></span>
    <span class="splive-text"></span>
</div>
