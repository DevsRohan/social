<?php
/**
 * Purchase Tracker — finds the most recent purchase of a product.
 *
 * @package SocialProofLive\Core
 */

namespace SocialProofLive\Core;

use SocialProofLive\Cache\Cache_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Class Purchase_Tracker
 *
 * Queries WooCommerce orders to find when a product was last purchased.
 */
class Purchase_Tracker {

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
     * Get the time since last purchase for a product.
     *
     * @param int $product_id Product ID.
     * @return array|null Array with 'seconds' and 'human_time', or null if never purchased.
     */
    public function get_last_purchase_time( $product_id ) {
        if ( ! function_exists( 'WC' ) ) {
            return null;
        }

        $cache     = Cache_Manager::get_instance();
        $cache_key = 'last_purchase_' . $product_id;
        $cached    = $cache->get( $cache_key );

        if ( false !== $cached ) {
            if ( 'none' === $cached ) {
                return null;
            }
            // Recalculate human time from stored timestamp.
            return $this->format_purchase_time( (int) $cached );
        }

        $timestamp = $this->query_last_purchase( $product_id );

        if ( null === $timestamp ) {
            $cache->set( $cache_key, 'none', 60 ); // Cache "no purchase" for 60s.
            return null;
        }

        // Cache the timestamp (not the human string, since that changes).
        $cache->set( $cache_key, $timestamp, 30 );

        return $this->format_purchase_time( $timestamp );
    }

    /**
     * Query WooCommerce for the most recent completed order containing this product.
     *
     * Supports both HPOS (Custom Order Tables) and legacy post-based orders.
     *
     * @param int $product_id Product ID.
     * @return int|null Unix timestamp of last purchase, or null.
     */
    private function query_last_purchase( $product_id ) {
        // Try HPOS first (WooCommerce 7.1+).
        if ( $this->is_hpos_enabled() ) {
            return $this->query_hpos_orders( $product_id );
        }

        return $this->query_legacy_orders( $product_id );
    }

    /**
     * Check if HPOS (High-Performance Order Storage) is enabled.
     *
     * @return bool
     */
    private function is_hpos_enabled() {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }

    /**
     * Query orders using HPOS tables.
     *
     * @param int $product_id Product ID.
     * @return int|null Unix timestamp or null.
     */
    private function query_hpos_orders( $product_id ) {
        global $wpdb;

        $order_items_table    = $wpdb->prefix . 'woocommerce_order_items';
        $order_itemmeta_table = $wpdb->prefix . 'woocommerce_order_itemmeta';
        $orders_table         = $wpdb->prefix . 'wc_orders';

        // Check if wc_orders table exists (HPOS).
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $orders_table
            )
        );

        if ( $table_exists ) {
            // Match status with or without the `wc-` prefix for compatibility.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $date = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT o.date_created_gmt
                    FROM {$orders_table} o
                    INNER JOIN {$order_items_table} oi ON o.id = oi.order_id
                    INNER JOIN {$order_itemmeta_table} oim ON oi.order_item_id = oim.order_item_id
                    WHERE o.status IN ('wc-completed', 'wc-processing', 'completed', 'processing')
                    AND o.type = 'shop_order'
                    AND oim.meta_key IN ('_product_id', '_variation_id')
                    AND oim.meta_value = %s
                    ORDER BY o.date_created_gmt DESC
                    LIMIT 1",
                    $product_id
                )
            );

            if ( $date ) {
                return strtotime( $date );
            }

            return null;
        }

        // Fallback to legacy if the HPOS table isn't present.
        return $this->query_legacy_orders( $product_id );
    }

    /**
     * Query orders using legacy post-based storage.
     *
     * @param int $product_id Product ID.
     * @return int|null Unix timestamp or null.
     */
    private function query_legacy_orders( $product_id ) {
        global $wpdb;

        $order_items_table    = $wpdb->prefix . 'woocommerce_order_items';
        $order_itemmeta_table = $wpdb->prefix . 'woocommerce_order_itemmeta';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $date = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT p.post_date_gmt
                FROM {$wpdb->posts} p
                INNER JOIN {$order_items_table} oi ON p.ID = oi.order_id
                INNER JOIN {$order_itemmeta_table} oim ON oi.order_item_id = oim.order_item_id
                WHERE p.post_type = 'shop_order'
                AND p.post_status IN ('wc-completed', 'wc-processing')
                AND oim.meta_key = '_product_id'
                AND oim.meta_value = %s
                ORDER BY p.post_date_gmt DESC
                LIMIT 1",
                $product_id
            )
        );

        if ( $date ) {
            return strtotime( $date );
        }

        // Also check variation_id.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $date = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT p.post_date_gmt
                FROM {$wpdb->posts} p
                INNER JOIN {$order_items_table} oi ON p.ID = oi.order_id
                INNER JOIN {$order_itemmeta_table} oim ON oi.order_item_id = oim.order_item_id
                WHERE p.post_type = 'shop_order'
                AND p.post_status IN ('wc-completed', 'wc-processing')
                AND oim.meta_key = '_variation_id'
                AND oim.meta_value = %s
                AND oim.meta_value != '0'
                ORDER BY p.post_date_gmt DESC
                LIMIT 1",
                $product_id
            )
        );

        if ( $date ) {
            return strtotime( $date );
        }

        return null;
    }

    /**
     * Format a purchase timestamp into human-readable "X ago" format.
     *
     * @param int $timestamp Unix timestamp.
     * @return array Array with 'seconds' and 'human_time'.
     */
    private function format_purchase_time( $timestamp ) {
        $seconds_ago = time() - $timestamp;

        if ( $seconds_ago < 0 ) {
            $seconds_ago = 0;
        }

        return array(
            'seconds'    => $seconds_ago,
            'human_time' => $this->seconds_to_human( $seconds_ago ),
            'timestamp'  => $timestamp,
        );
    }

    /**
     * Convert seconds to a human-readable time string.
     *
     * @param int $seconds Number of seconds.
     * @return string Human-readable string (e.g., "7 minutes").
     */
    private function seconds_to_human( $seconds ) {
        if ( $seconds < 60 ) {
            return __( 'just now', 'social-proof-live' );
        }

        if ( $seconds < 3600 ) {
            $minutes = (int) floor( $seconds / 60 );
            return sprintf(
                /* translators: %d: number of minutes */
                _n( '%d minute', '%d minutes', $minutes, 'social-proof-live' ),
                $minutes
            );
        }

        if ( $seconds < 86400 ) {
            $hours = (int) floor( $seconds / 3600 );
            return sprintf(
                /* translators: %d: number of hours */
                _n( '%d hour', '%d hours', $hours, 'social-proof-live' ),
                $hours
            );
        }

        $days = (int) floor( $seconds / 86400 );
        return sprintf(
            /* translators: %d: number of days */
            _n( '%d day', '%d days', $days, 'social-proof-live' ),
            $days
        );
    }

    /**
     * Count purchases of a product within the last N hours (sales velocity).
     *
     * @param int $product_id Product ID.
     * @param int $hours      Look-back window in hours.
     * @return int Number of recent orders containing the product.
     */
    public function get_recent_purchase_count( $product_id, $hours = 72 ) {
        if ( ! function_exists( 'WC' ) ) {
            return 0;
        }

        $cache     = Cache_Manager::get_instance();
        $cache_key = 'velocity_' . $product_id . '_' . $hours;
        $cached    = $cache->get( $cache_key );

        if ( false !== $cached ) {
            return (int) $cached;
        }

        global $wpdb;

        $order_items_table    = $wpdb->prefix . 'woocommerce_order_items';
        $order_itemmeta_table = $wpdb->prefix . 'woocommerce_order_itemmeta';
        $threshold            = gmdate( 'Y-m-d H:i:s', time() - ( $hours * HOUR_IN_SECONDS ) );
        $orders_table         = $wpdb->prefix . 'wc_orders';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $hpos = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $orders_table
            )
        );

        if ( $hpos ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT o.id)
                    FROM {$orders_table} o
                    INNER JOIN {$order_items_table} oi ON o.id = oi.order_id
                    INNER JOIN {$order_itemmeta_table} oim ON oi.order_item_id = oim.order_item_id
                    WHERE o.status IN ('wc-completed', 'wc-processing', 'completed', 'processing')
                    AND o.type = 'shop_order'
                    AND o.date_created_gmt >= %s
                    AND oim.meta_key IN ('_product_id', '_variation_id')
                    AND oim.meta_value = %s",
                    $threshold,
                    $product_id
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT p.ID)
                    FROM {$wpdb->posts} p
                    INNER JOIN {$order_items_table} oi ON p.ID = oi.order_id
                    INNER JOIN {$order_itemmeta_table} oim ON oi.order_item_id = oim.order_item_id
                    WHERE p.post_type = 'shop_order'
                    AND p.post_status IN ('wc-completed', 'wc-processing')
                    AND p.post_date_gmt >= %s
                    AND oim.meta_key IN ('_product_id', '_variation_id')
                    AND oim.meta_value = %s",
                    $threshold,
                    $product_id
                )
            );
        }

        $count = absint( $count );
        $cache->set( $cache_key, $count, 300 );

        return $count;
    }

    /**
     * Invalidate purchase cache for a product.
     *
     * @param int $product_id Product ID.
     * @return void
     */
    public function invalidate_cache( $product_id ) {
        $cache = Cache_Manager::get_instance();
        $cache->delete( 'last_purchase_' . $product_id );
    }
}
