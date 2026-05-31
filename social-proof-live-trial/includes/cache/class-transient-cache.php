<?php
/**
 * Transient Cache fallback — uses WordPress transients.
 *
 * @package SocialProofLive\Cache
 */

namespace SocialProofLive\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Class Transient_Cache
 *
 * Fallback caching using WordPress transients when no persistent object cache is available.
 */
class Transient_Cache {

    /**
     * Key prefix.
     *
     * @var string
     */
    private $prefix;

    /**
     * Constructor.
     *
     * @param string $prefix Key prefix.
     */
    public function __construct( $prefix = 'splive_' ) {
        $this->prefix = $prefix;
    }

    /**
     * Get a cached value.
     *
     * @param string $key Cache key.
     * @return mixed|false
     */
    public function get( $key ) {
        $value = get_transient( $this->prefix . $key );
        return $value;
    }

    /**
     * Set a cached value.
     *
     * @param string $key        Cache key.
     * @param mixed  $value      Value to cache.
     * @param int    $expiration TTL in seconds.
     * @return bool
     */
    public function set( $key, $value, $expiration = 5 ) {
        return set_transient( $this->prefix . $key, $value, $expiration );
    }

    /**
     * Delete a cached value.
     *
     * @param string $key Cache key.
     * @return bool
     */
    public function delete( $key ) {
        return delete_transient( $this->prefix . $key );
    }

    /**
     * Flush all plugin transients.
     *
     * @return bool
     */
    public function flush() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_splive_%'
            OR option_name LIKE '_transient_timeout_splive_%'"
        );

        return true;
    }
}
