<?php
/**
 * Compatibility — checks for WP/WC versions and common plugins.
 *
 * @package SocialProofLive\Utils
 */

namespace SocialProofLive\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Class Compatibility
 *
 * Detects environment and provides compatibility information.
 */
class Compatibility {

    /**
     * Check if WooCommerce is active and meets minimum version.
     *
     * @return bool True if compatible.
     */
    public static function is_woocommerce_compatible() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return false;
        }

        return version_compare( WC()->version, SPLIVE_MIN_WC_VERSION, '>=' );
    }

    /**
     * Check if a persistent object cache is available.
     *
     * @return bool
     */
    public static function has_object_cache() {
        return wp_using_ext_object_cache();
    }

    /**
     * Detect if a popular caching plugin is active.
     *
     * @return array List of detected caching plugins.
     */
    public static function detect_caching_plugins() {
        $detected = array();

        $plugins = array(
            'WP Super Cache'  => 'wp-super-cache/wp-cache.php',
            'W3 Total Cache'  => 'w3-total-cache/w3-total-cache.php',
            'WP Rocket'       => 'wp-rocket/wp-rocket.php',
            'LiteSpeed Cache' => 'litespeed-cache/litespeed-cache.php',
            'WP Fastest Cache' => 'wp-fastest-cache/wpFastestCache.php',
            'Autoptimize'     => 'autoptimize/autoptimize.php',
        );

        foreach ( $plugins as $name => $path ) {
            if ( is_plugin_active( $path ) ) {
                $detected[] = $name;
            }
        }

        return $detected;
    }

    /**
     * Detect the active page builder.
     *
     * @return string|null Page builder name or null.
     */
    public static function detect_page_builder() {
        if ( defined( 'ELEMENTOR_VERSION' ) ) {
            return 'Elementor';
        }
        if ( class_exists( 'FLBuilder' ) ) {
            return 'Beaver Builder';
        }
        if ( defined( 'ET_BUILDER_VERSION' ) ) {
            return 'Divi';
        }
        if ( defined( 'JETSTORY_VERSION' ) || class_exists( 'Jetstory' ) ) {
            return 'JetEngine';
        }

        return null;
    }

    /**
     * Get system status information.
     *
     * @return array System info.
     */
    public static function get_system_info() {
        global $wp_version;

        return array(
            'php_version'     => PHP_VERSION,
            'wp_version'      => $wp_version,
            'wc_version'      => class_exists( 'WooCommerce' ) ? WC()->version : 'N/A',
            'plugin_version'  => SPLIVE_VERSION,
            'db_version'      => get_option( 'splive_db_version', '0' ),
            'object_cache'    => self::has_object_cache() ? 'Yes' : 'No',
            'caching_plugins' => self::detect_caching_plugins(),
            'page_builder'    => self::detect_page_builder(),
            'multisite'       => is_multisite() ? 'Yes' : 'No',
            'memory_limit'    => ini_get( 'memory_limit' ),
            'max_execution'   => ini_get( 'max_execution_time' ),
        );
    }
}
