<?php
/**
 * Widget Renderer — outputs social proof widget HTML on product pages.
 *
 * @package SocialProofLive\Frontend
 */

namespace SocialProofLive\Frontend;

use SocialProofLive\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class Widget_Renderer
 *
 * Hooks into WooCommerce product page and renders the widget container.
 * Actual data is populated via JavaScript/REST API.
 */
class Widget_Renderer {

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
     * Initialize widget renderer hooks.
     *
     * @return void
     */
    public function init() {
        if ( 'shortcode' === $this->settings['widget_position'] ) {
            return; // Widget placed via shortcode only.
        }

        $hook     = $this->get_position_hook();
        $priority = $this->get_position_priority();

        add_action( $hook, array( $this, 'render_widget' ), $priority );
    }

    /**
     * Get the WooCommerce hook for widget position.
     *
     * @return string Hook name.
     */
    private function get_position_hook() {
        switch ( $this->settings['widget_position'] ) {
            case 'before_add_to_cart':
                return 'woocommerce_before_add_to_cart_form';
            case 'after_price':
                return 'woocommerce_single_product_summary';
            case 'after_summary':
                return 'woocommerce_after_single_product_summary';
            case 'after_add_to_cart':
            default:
                return 'woocommerce_after_add_to_cart_form';
        }
    }

    /**
     * Get priority for the hook.
     *
     * @return int Priority.
     */
    private function get_position_priority() {
        switch ( $this->settings['widget_position'] ) {
            case 'after_price':
                return 15; // After price (priority 10).
            case 'after_summary':
                return 5;
            default:
                return 10;
        }
    }

    /**
     * Render the widget HTML container.
     *
     * @return void
     */
    public function render_widget() {
        global $product;

        if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
            return;
        }

        $product_id = $product->get_id();

        // Check if disabled for this product.
        if ( Settings::is_product_disabled( $product_id ) ) {
            return;
        }

        // Check exclusions.
        if ( $this->is_excluded( $product_id ) ) {
            return;
        }

        // Check mobile disable.
        if ( ! empty( $this->settings['disable_on_mobile'] ) && wp_is_mobile() ) {
            return;
        }

        $this->output_widget_html( $product_id );
    }

    /**
     * Check if product is excluded by category or ID.
     *
     * @param int $product_id Product ID.
     * @return bool True if excluded.
     */
    private function is_excluded( $product_id ) {
        // Check product exclusions.
        if ( ! empty( $this->settings['excluded_products'] ) && in_array( $product_id, (array) $this->settings['excluded_products'], true ) ) {
            return true;
        }

        // Check category exclusions.
        if ( ! empty( $this->settings['excluded_categories'] ) ) {
            $product_cats = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
            if ( array_intersect( (array) $this->settings['excluded_categories'], $product_cats ) ) {
                return true;
            }
        }

        /**
         * Filter whether a product is excluded from social proof display.
         *
         * @param bool $excluded   Whether product is excluded.
         * @param int  $product_id Product ID.
         */
        return (bool) apply_filters( 'splive_product_excluded', false, $product_id );
    }

    /**
     * Output the widget HTML.
     *
     * @param int $product_id Product ID.
     * @return void
     */
    private function output_widget_html( $product_id ) {
        $theme     = esc_attr( $this->settings['theme'] );
        $animation = esc_attr( $this->settings['animation_style'] );
        $scheme    = esc_attr( $this->settings['color_scheme'] );

        /**
         * Fire before widget renders.
         *
         * @param int $product_id Product ID.
         */
        do_action( 'splive_before_widget', $product_id );

        $html = sprintf(
            '<div id="splive-widget" class="splive-widget splive-theme-%s splive-scheme-%s" data-product-id="%d" data-animation="%s" style="display:none;" aria-live="polite" aria-atomic="true">',
            $theme,
            $scheme,
            $product_id,
            $animation
        );

        // Viewers line.
        if ( ! empty( $this->settings['enable_viewers'] ) ) {
            $html .= '<div class="splive-line splive-viewers" data-type="viewers" style="display:none;">';
            $html .= '<span class="splive-icon">' . esc_html( $this->settings['icon_viewers'] ) . '</span>';
            $html .= '<span class="splive-text"></span>';
            $html .= '</div>';
        }

        // Cart line.
        if ( ! empty( $this->settings['enable_cart'] ) ) {
            $html .= '<div class="splive-line splive-cart" data-type="cart" style="display:none;">';
            $html .= '<span class="splive-icon">' . esc_html( $this->settings['icon_cart'] ) . '</span>';
            $html .= '<span class="splive-text"></span>';
            $html .= '</div>';
        }

        // Purchase line.
        if ( ! empty( $this->settings['enable_purchase'] ) ) {
            $html .= '<div class="splive-line splive-purchase" data-type="purchase" style="display:none;">';
            $html .= '<span class="splive-icon">' . esc_html( $this->settings['icon_purchase'] ) . '</span>';
            $html .= '<span class="splive-text"></span>';
            $html .= '</div>';
        }

        // Stock urgency line.
        if ( ! empty( $this->settings['enable_stock'] ) ) {
            $html .= '<div class="splive-line splive-stock" data-type="stock" style="display:none;">';
            $html .= '<span class="splive-icon">' . esc_html( $this->settings['icon_stock'] ) . '</span>';
            $html .= '<span class="splive-text"></span>';
            $html .= '</div>';
        }

        // Sale countdown line.
        if ( ! empty( $this->settings['enable_countdown'] ) ) {
            $html .= '<div class="splive-line splive-countdown" data-type="countdown" style="display:none;">';
            $html .= '<span class="splive-icon">' . esc_html( $this->settings['icon_countdown'] ) . '</span>';
            $html .= '<span class="splive-text"></span>';
            $html .= '</div>';
        }

        $html .= '</div>';

        /**
         * Filter the widget HTML output.
         *
         * @param string $html       Widget HTML.
         * @param int    $product_id Product ID.
         */
        $html = apply_filters( 'splive_widget_html', $html, $product_id );

        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is pre-escaped above.

        do_action( 'splive_after_widget', $product_id );
    }
}
