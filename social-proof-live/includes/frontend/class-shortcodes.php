<?php
/**
 * Shortcodes — registers plugin shortcodes.
 *
 * @package SocialProofLive\Frontend
 */

namespace SocialProofLive\Frontend;

use SocialProofLive\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class Shortcodes
 *
 * Provides [social_proof_live] shortcode for custom widget placement.
 */
class Shortcodes {

    /**
     * Plugin settings.
     *
     * @var array
     */
    private $settings;

    /**
     * Constructor.
     *
     * @param array $settings Plugin settings.
     */
    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    /**
     * Initialize shortcodes.
     *
     * @return void
     */
    public function init() {
        add_shortcode( 'social_proof_live', array( $this, 'render_shortcode' ) );
    }

    /**
     * Render the shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'product_id' => 0,
            'theme'      => $this->settings['theme'],
        ), $atts, 'social_proof_live' );

        $product_id = absint( $atts['product_id'] );

        // If no product ID specified, try to get current product.
        if ( ! $product_id ) {
            global $product;
            if ( $product && is_a( $product, 'WC_Product' ) ) {
                $product_id = $product->get_id();
            }
        }

        if ( ! $product_id ) {
            return '';
        }

        // Check if disabled.
        if ( Settings::is_product_disabled( $product_id ) ) {
            return '';
        }

        $theme     = esc_attr( sanitize_text_field( $atts['theme'] ) );
        $animation = esc_attr( $this->settings['animation_style'] );
        $scheme    = esc_attr( $this->settings['color_scheme'] );

        $html = sprintf(
            '<div class="splive-widget splive-theme-%s splive-scheme-%s splive-shortcode" data-product-id="%d" data-animation="%s" style="display:none;" aria-live="polite">',
            $theme,
            $scheme,
            $product_id,
            $animation
        );

        if ( ! empty( $this->settings['enable_viewers'] ) ) {
            $html .= '<div class="splive-line splive-viewers" data-type="viewers" style="display:none;">';
            $html .= '<span class="splive-icon">' . esc_html( $this->settings['icon_viewers'] ) . '</span>';
            $html .= '<span class="splive-text"></span>';
            $html .= '</div>';
        }

        if ( ! empty( $this->settings['enable_cart'] ) ) {
            $html .= '<div class="splive-line splive-cart" data-type="cart" style="display:none;">';
            $html .= '<span class="splive-icon">' . esc_html( $this->settings['icon_cart'] ) . '</span>';
            $html .= '<span class="splive-text"></span>';
            $html .= '</div>';
        }

        if ( ! empty( $this->settings['enable_purchase'] ) ) {
            $html .= '<div class="splive-line splive-purchase" data-type="purchase" style="display:none;">';
            $html .= '<span class="splive-icon">' . esc_html( $this->settings['icon_purchase'] ) . '</span>';
            $html .= '<span class="splive-text"></span>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
