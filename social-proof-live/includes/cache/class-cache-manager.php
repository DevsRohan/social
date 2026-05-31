<?php
/**
 * Cache Manager — strategy pattern for caching.
 *
 * @package SocialProofLive\Cache
 */

namespace SocialProofLive\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Class Cache_Manager
 *
 * Singleton that auto-detects the best caching strategy available:
 * persistent object cache (Redis/Memcached) > transients.
 */
class Cache_Manager {

    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Cache driver instance.
     *
     * @var Object_Cache|Transient_Cache
     */
    private $driver;

    /**
     * Cache key prefix.
     *
     * @var string
     */
    private $prefix = 'splive_';

    /**
     * Get singleton instance.
     *
     * @return self
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor (singleton).
     */
    private function __construct() {}

    /**
     * Initialize the cache manager.
     *
     * @return void
     */
    public function init() {
        if ( $this->has_persistent_object_cache() ) {
            $this->driver = new Object_Cache( $this->prefix );
        } else {
            $this->driver = new Transient_Cache( $this->prefix );
        }
    }

    /**
     * Check if a persistent object cache is available.
     *
     * @return bool
     */
    private function has_persistent_object_cache() {
        return wp_using_ext_object_cache();
    }

    /**
     * Get a cached value.
     *
     * @param string $key Cache key (without prefix).
     * @return mixed|false Cached value or false if not found.
     */
    public function get( $key ) {
        if ( ! $this->driver ) {
            $this->init();
        }
        return $this->driver->get( $key );
    }

    /**
     * Set a cached value.
     *
     * @param string $key        Cache key (without prefix).
     * @param mixed  $value      Value to cache.
     * @param int    $expiration Expiration in seconds.
     * @return bool True on success.
     */
    public function set( $key, $value, $expiration = 5 ) {
        if ( ! $this->driver ) {
            $this->init();
        }
        return $this->driver->set( $key, $value, $expiration );
    }

    /**
     * Delete a cached value.
     *
     * @param string $key Cache key (without prefix).
     * @return bool True on success.
     */
    public function delete( $key ) {
        if ( ! $this->driver ) {
            $this->init();
        }
        return $this->driver->delete( $key );
    }

    /**
     * Flush all plugin cache.
     *
     * @return bool True on success.
     */
    public function flush() {
        if ( ! $this->driver ) {
            $this->init();
        }
        return $this->driver->flush();
    }

    /**
     * Get the active driver type.
     *
     * @return string 'object_cache' or 'transient'.
     */
    public function get_driver_type() {
        if ( ! $this->driver ) {
            $this->init();
        }
        return $this->driver instanceof Object_Cache ? 'object_cache' : 'transient';
    }
}
