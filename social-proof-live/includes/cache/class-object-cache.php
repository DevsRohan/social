<?php
/**
 * Object Cache adapter — uses WordPress persistent object cache.
 *
 * @package SocialProofLive\Cache
 */

namespace SocialProofLive\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Class Object_Cache
 *
 * Adapter for WordPress object cache (Redis, Memcached, etc.).
 */
class Object_Cache {

    /**
     * Cache group.
     *
     * @var string
     */
    private $group = 'splive';

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
        $found = false;
        $value = wp_cache_get( $this->prefix . $key, $this->group, false, $found );

        if ( ! $found ) {
            return false;
        }

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
        return wp_cache_set( $this->prefix . $key, $value, $this->group, $expiration );
    }

    /**
     * Delete a cached value.
     *
     * @param string $key Cache key.
     * @return bool
     */
    public function delete( $key ) {
        return wp_cache_delete( $this->prefix . $key, $this->group );
    }

    /**
     * Flush all keys in this group.
     *
     * @return bool
     */
    public function flush() {
        // WordPress doesn't support group flush natively.
        // This is a best-effort — relies on cache invalidation per-key.
        return true;
    }
}
