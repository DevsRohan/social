<?php
/**
 * Cart Tracker — counts how many carts contain a specific product.
 *
 * @package SocialProofLive\Core
 */

namespace SocialProofLive\Core;

use SocialProofLive\Cache\Cache_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Class Cart_Tracker
 *
 * Reads WooCommerce session data to determine how many active carts contain a product.
 */
class Cart_Tracker {

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
     * Get the number of active carts containing a specific product.
     *
     * Reads directly from WooCommerce's session storage table.
     *
     * @param int $product_id Product ID.
     * @return int Number of carts containing this product.
     */
    public function get_cart_count( $product_id ) {
        if ( ! function_exists( 'WC' ) ) {
            return 0;
        }

        $cache     = Cache_Manager::get_instance();
        $cache_key = 'cart_count_' . $product_id;
        $cached    = $cache->get( $cache_key );

        if ( false !== $cached ) {
            return (int) $cached;
        }

        $count = $this->query_wc_sessions_for_product( $product_id );

        $cache_ttl = isset( $this->settings['cache_ttl'] ) ? max( (int) $this->settings['cache_ttl'], 5 ) : 10;
        $cache->set( $cache_key, $count, $cache_ttl );

        return $count;
    }

    /**
     * Query WooCommerce session table for carts containing a product.
     *
     * @param int $product_id Product ID.
     * @return int Count of carts.
     */
    private function query_wc_sessions_for_product( $product_id ) {
        global $wpdb;

        $session_table = $wpdb->prefix . 'woocommerce_sessions';

        // Check if table exists.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $session_table
            )
        );

        if ( ! $table_exists ) {
            return 0;
        }

        // Get active sessions (not expired).
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $sessions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT session_value FROM {$session_table}
                WHERE session_expiry > %d
                LIMIT 500",
                time()
            )
        );

        if ( empty( $sessions ) ) {
            return 0;
        }

        $count = 0;
        $product_id_str = (string) $product_id;

        foreach ( $sessions as $session ) {
            if ( empty( $session->session_value ) ) {
                continue;
            }

            $data = maybe_unserialize( $session->session_value );

            if ( ! is_array( $data ) || empty( $data['cart'] ) ) {
                continue;
            }

            $cart = maybe_unserialize( $data['cart'] );

            if ( ! is_array( $cart ) ) {
                continue;
            }

            foreach ( $cart as $cart_item ) {
                if ( ! is_array( $cart_item ) ) {
                    continue;
                }

                $item_product_id = isset( $cart_item['product_id'] ) ? (string) $cart_item['product_id'] : '';
                $item_variation_id = isset( $cart_item['variation_id'] ) ? (string) $cart_item['variation_id'] : '';

                if ( $item_product_id === $product_id_str || $item_variation_id === $product_id_str ) {
                    $count++;
                    break; // One cart counted, move to next session.
                }
            }
        }

        return $count;
    }

    /**
     * Invalidate cart count cache for a product.
     *
     * @param int $product_id Product ID.
     * @return void
     */
    public function invalidate_cache( $product_id ) {
        $cache = Cache_Manager::get_instance();
        $cache->delete( 'cart_count_' . $product_id );
    }
}
