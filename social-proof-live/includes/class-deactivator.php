<?php
/**
 * Plugin deactivation handler.
 *
 * @package SocialProofLive
 */

namespace SocialProofLive;

defined( 'ABSPATH' ) || exit;

/**
 * Class Deactivator
 *
 * Handles deactivation tasks: clears cron, marks sessions inactive.
 */
class Deactivator {

    /**
     * Run deactivation tasks.
     *
     * @return void
     */
    public static function deactivate() {
        self::clear_cron();
        self::deactivate_sessions();
        self::clear_transients();

        flush_rewrite_rules();
    }

    /**
     * Remove scheduled cron events.
     *
     * @return void
     */
    private static function clear_cron() {
        $hooks = array(
            'splive_cleanup_sessions',
            'splive_aggregate_stats',
            'splive_daily_maintenance',
        );

        foreach ( $hooks as $hook ) {
            $timestamp = wp_next_scheduled( $hook );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, $hook );
            }
        }
    }

    /**
     * Mark all active sessions as inactive.
     *
     * @return void
     */
    private static function deactivate_sessions() {
        global $wpdb;

        $table = $wpdb->prefix . 'splive_sessions';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->update(
            $table,
            array( 'is_active' => 0 ),
            array( 'is_active' => 1 ),
            array( '%d' ),
            array( '%d' )
        );
    }

    /**
     * Clear all plugin transients.
     *
     * @return void
     */
    private static function clear_transients() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_splive_%' OR option_name LIKE '_transient_timeout_splive_%'"
        );
    }
}
