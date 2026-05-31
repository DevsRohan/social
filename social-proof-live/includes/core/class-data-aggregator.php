<?php
/**
 * Data Aggregator — combines all data sources into a single response.
 *
 * @package SocialProofLive\Core
 */

namespace SocialProofLive\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Data_Aggregator
 *
 * Central class that fetches viewer count, cart count, and purchase data,
 * applies thresholds and filters, and returns a unified response.
 */
class Data_Aggregator {

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
     * Get all social proof data for a product.
     *
     * @param int $product_id Product ID.
     * @return array Aggregated data.
     */
    public function get_product_data( $product_id ) {
        $data = array(
            'product_id' => $product_id,
            'viewers'    => null,
            'cart'       => null,
            'purchase'   => null,
            'stock'      => null,
            'countdown'  => null,
            'demand'     => null,
            'show'       => false,
        );

        $raw_viewers = 0;
        $raw_cart    = 0;
        $raw_stock   = null;

        // Viewer count.
        if ( ! empty( $this->settings['enable_viewers'] ) ) {
            $viewer_tracker = new Viewer_Tracker( $this->settings );
            $viewer_count   = $viewer_tracker->get_viewer_count( $product_id );
            $raw_viewers    = $viewer_count;

            $min_viewers = isset( $this->settings['minimum_viewers'] ) ? (int) $this->settings['minimum_viewers'] : 2;

            if ( $viewer_count >= $min_viewers ) {
                $data['viewers'] = $viewer_count;
                $data['show']    = true;
            }
        }

        // Cart count.
        if ( ! empty( $this->settings['enable_cart'] ) ) {
            $cart_tracker = new Cart_Tracker( $this->settings );
            $cart_count   = $cart_tracker->get_cart_count( $product_id );
            $raw_cart     = $cart_count;

            $min_cart = isset( $this->settings['minimum_cart'] ) ? (int) $this->settings['minimum_cart'] : 1;

            if ( $cart_count >= $min_cart ) {
                $data['cart'] = $cart_count;
                $data['show'] = true;
            }
        }

        // Last purchase.
        if ( ! empty( $this->settings['enable_purchase'] ) ) {
            $purchase_tracker = new Purchase_Tracker( $this->settings );
            $purchase_data    = $purchase_tracker->get_last_purchase_time( $product_id );

            if ( $purchase_data && $purchase_data['seconds'] < 86400 ) {
                $data['purchase'] = $purchase_data;
                $data['show']     = true;
            }
        }

        // Stock urgency + sale countdown (real WooCommerce data).
        if ( function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $product_id );

            if ( $product ) {
                // Low stock urgency.
                if ( ! empty( $this->settings['enable_stock'] ) && $product->managing_stock() ) {
                    $stock_qty = $product->get_stock_quantity();
                    $threshold = isset( $this->settings['stock_threshold'] ) ? (int) $this->settings['stock_threshold'] : 10;

                    if ( null !== $stock_qty ) {
                        $raw_stock = (int) $stock_qty;
                    }

                    if ( null !== $stock_qty && $stock_qty > 0 && $stock_qty <= $threshold ) {
                        $data['stock'] = (int) $stock_qty;
                        $data['show']  = true;
                    }
                }

                // Sale countdown — only when a sale end date is set in the future.
                if ( ! empty( $this->settings['enable_countdown'] ) && $product->is_on_sale() ) {
                    $sale_end = $product->get_date_on_sale_to();
                    if ( $sale_end ) {
                        $end_ts  = $sale_end->getTimestamp();
                        $seconds = $end_ts - time();
                        if ( $seconds > 0 ) {
                            $data['countdown'] = array(
                                'seconds'   => $seconds,
                                'timestamp' => $end_ts,
                            );
                            $data['show'] = true;
                        }
                    }
                }
            }
        }

        // Demand Score — blends viewers, cart, sales velocity, and scarcity.
        $demand_engine = new Demand_Score( $this->settings );
        $demand        = $demand_engine->calculate( $product_id, $raw_viewers, $raw_cart, $raw_stock );
        if ( $demand ) {
            $data['demand'] = $demand;
            $data['show']   = true;
        }

        /**
         * Filter the aggregated product data.
         *
         * @param array $data       Aggregated data.
         * @param int   $product_id Product ID.
         * @param array $settings   Plugin settings.
         */
        $data = apply_filters( 'splive_product_data', $data, $product_id, $this->settings );

        return $data;
    }

    /**
     * Format the response for the REST API.
     *
     * @param array $data Raw aggregated data.
     * @return array Formatted response.
     */
    public function format_response( $data ) {
        $response = array(
            'show'    => $data['show'],
            'viewers' => $data['viewers'],
            'cart'    => $data['cart'],
        );

        if ( $data['purchase'] ) {
            $response['last_purchase']         = $data['purchase']['human_time'];
            $response['last_purchase_seconds'] = $data['purchase']['seconds'];
        } else {
            $response['last_purchase']         = null;
            $response['last_purchase_seconds'] = null;
        }

        // Stock urgency.
        $response['stock'] = isset( $data['stock'] ) ? $data['stock'] : null;

        // Sale countdown — send remaining seconds; client ticks it down locally.
        if ( ! empty( $data['countdown'] ) ) {
            $response['countdown_seconds'] = $data['countdown']['seconds'];
        } else {
            $response['countdown_seconds'] = null;
        }

        // Demand score meter.
        $response['demand'] = isset( $data['demand'] ) ? $data['demand'] : null;

        return $response;
    }
}
