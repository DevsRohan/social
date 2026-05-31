<?php
/**
 * Conversion Tracker — records real cart additions and purchases.
 *
 * @package SocialProofLive\Core
 */

namespace SocialProofLive\Core;

use SocialProofLive\Database\Stats_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Conversion_Tracker
 *
 * Hooks into WooCommerce to record cart additions and completed purchases
 * into the stats table for real conversion analytics.
 */
class Conversion_Tracker {

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
     * Register hooks.
     *
     * @return void
     */
    public function init() {
        if ( empty( $this->settings['enable_conversion_tracking'] ) ) {
            return;
        }

        add_action( 'woocommerce_add_to_cart', array( $this, 'on_add_to_cart' ), 10, 6 );
        add_action( 'woocommerce_payment_complete', array( $this, 'on_order_paid' ) );
        add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_paid' ) );
    }

    /**
     * Record a cart addition.
     *
     * @param string $cart_item_key Cart item key.
     * @param int    $product_id    Product ID.
     * @param int    $quantity      Quantity.
     * @param int    $variation_id  Variation ID.
     * @param array  $variation     Variation data.
     * @param array  $cart_item_data Cart item data.
     * @return void
     */
    public function on_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
        $product_id = absint( $product_id );
        if ( $product_id > 0 ) {
            $stats = new Stats_Repository();
            $stats->increment_metric( $product_id, 'cart_additions', 1 );
        }
    }

    /**
     * Record purchases when an order is paid/completed.
     *
     * Guards against double counting using order meta.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function on_order_paid( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Prevent double counting if both hooks fire for the same order.
        if ( 'yes' === $order->get_meta( '_splive_counted' ) ) {
            return;
        }

        $stats = new Stats_Repository();

        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            if ( $product_id ) {
                $qty = max( 1, (int) $item->get_quantity() );
                $stats->increment_metric( $product_id, 'purchases', $qty );
            }
        }

        $order->update_meta_data( '_splive_counted', 'yes' );
        $order->save();
    }
}
