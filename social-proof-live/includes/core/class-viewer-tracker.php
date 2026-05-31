<?php
/**
 * Viewer Tracker — counts active viewers per product.
 *
 * @package SocialProofLive\Core
 */

namespace SocialProofLive\Core;

use SocialProofLive\Database\Session_Repository;
use SocialProofLive\Cache\Cache_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Class Viewer_Tracker
 *
 * Provides the live viewer count for a given product.
 */
class Viewer_Tracker {

    /**
     * Session repository.
     *
     * @var Session_Repository
     */
    private $session_repo;

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
        $this->settings     = $settings;
        $this->session_repo = new Session_Repository();
    }

    /**
     * Get the current live viewer count for a product.
     *
     * Uses caching to avoid hitting the DB on every request.
     *
     * @param int $product_id Product ID.
     * @return int Viewer count.
     */
    public function get_viewer_count( $product_id ) {
        $cache    = Cache_Manager::get_instance();
        $cache_key = 'viewers_' . $product_id;
        $cached   = $cache->get( $cache_key );

        if ( false !== $cached ) {
            return (int) $cached;
        }

        $timeout = isset( $this->settings['session_timeout'] ) ? (int) $this->settings['session_timeout'] : 120;
        $count   = $this->session_repo->count_active_viewers( $product_id, $timeout );

        $cache_ttl = isset( $this->settings['cache_ttl'] ) ? (int) $this->settings['cache_ttl'] : 5;
        $cache->set( $cache_key, $count, $cache_ttl );

        return $count;
    }

    /**
     * Get total active viewers site-wide.
     *
     * @return int Total viewers.
     */
    public function get_total_viewers() {
        $cache     = Cache_Manager::get_instance();
        $cache_key = 'total_viewers';
        $cached    = $cache->get( $cache_key );

        if ( false !== $cached ) {
            return (int) $cached;
        }

        $timeout = isset( $this->settings['session_timeout'] ) ? (int) $this->settings['session_timeout'] : 120;
        $count   = $this->session_repo->count_total_active_viewers( $timeout );

        $cache->set( $cache_key, $count, 10 );

        return $count;
    }

    /**
     * Get top products by live viewer count.
     *
     * @param int $limit Number of products.
     * @return array Array of product data.
     */
    public function get_top_products( $limit = 10 ) {
        $cache     = Cache_Manager::get_instance();
        $cache_key = 'top_products_' . $limit;
        $cached    = $cache->get( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }

        $timeout  = isset( $this->settings['session_timeout'] ) ? (int) $this->settings['session_timeout'] : 120;
        $products = $this->session_repo->get_top_products( $limit, $timeout );

        // Enrich with product names.
        if ( function_exists( 'wc_get_product' ) ) {
            foreach ( $products as &$item ) {
                $product = wc_get_product( $item['product_id'] );
                if ( $product ) {
                    $item['product_name'] = $product->get_name();
                    $item['product_url']  = get_edit_post_link( $item['product_id'], 'raw' );
                } else {
                    $item['product_name'] = __( 'Unknown Product', 'social-proof-live' );
                    $item['product_url']  = '';
                }
            }
        }

        $cache->set( $cache_key, $products, 15 );

        return $products;
    }

    /**
     * Invalidate viewer cache for a product.
     *
     * @param int $product_id Product ID.
     * @return void
     */
    public function invalidate_cache( $product_id ) {
        $cache = Cache_Manager::get_instance();
        $cache->delete( 'viewers_' . $product_id );
        $cache->delete( 'total_viewers' );
        $cache->delete( 'top_products_10' );
    }
}
