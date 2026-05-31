<?php
/**
 * Cleanup Cron — scheduled maintenance tasks.
 *
 * @package SocialProofLive\Core
 */

namespace SocialProofLive\Core;

use SocialProofLive\Database\Session_Repository;
use SocialProofLive\Database\Stats_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Cleanup_Cron
 *
 * Handles scheduled cleanup of expired sessions and stats aggregation.
 */
class Cleanup_Cron {

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
     * Initialize cron hooks.
     *
     * @return void
     */
    public function init() {
        add_action( 'splive_cleanup_sessions', array( $this, 'cleanup_sessions' ) );
        add_action( 'splive_aggregate_stats', array( $this, 'aggregate_stats' ) );
        add_action( 'splive_daily_maintenance', array( $this, 'daily_maintenance' ) );
    }

    /**
     * Cleanup expired sessions (runs every 5 minutes).
     *
     * @return void
     */
    public function cleanup_sessions() {
        $session_repo = new Session_Repository();
        $timeout      = isset( $this->settings['session_timeout'] ) ? (int) $this->settings['session_timeout'] : 120;

        // Mark stale sessions as inactive.
        $expired = $session_repo->expire_stale_sessions( $timeout );

        if ( ! empty( $this->settings['debug_mode'] ) ) {
            error_log( sprintf( '[Social Proof LIVE] Cleanup: %d sessions expired.', $expired ) );
        }
    }

    /**
     * Aggregate stats hourly.
     *
     * Records current viewer counts into the stats table for historical tracking.
     *
     * @return void
     */
    public function aggregate_stats() {
        $session_repo = new Session_Repository();
        $stats_repo   = new Stats_Repository();
        $timeout      = isset( $this->settings['session_timeout'] ) ? (int) $this->settings['session_timeout'] : 120;

        // Get top products with active viewers.
        $top_products = $session_repo->get_top_products( 50, $timeout );

        foreach ( $top_products as $product ) {
            $stats_repo->record_hourly_stats(
                (int) $product['product_id'],
                (int) $product['viewer_count']
            );
        }

        if ( ! empty( $this->settings['debug_mode'] ) ) {
            error_log( sprintf( '[Social Proof LIVE] Stats aggregated for %d products.', count( $top_products ) ) );
        }
    }

    /**
     * Daily maintenance tasks.
     *
     * Purges old sessions and expired stats.
     *
     * @return void
     */
    public function daily_maintenance() {
        $session_repo = new Session_Repository();
        $stats_repo   = new Stats_Repository();

        // Purge sessions older than 24 hours.
        $purged_sessions = $session_repo->purge_old_sessions( 24 );

        // Purge stats older than retention period.
        $retention   = isset( $this->settings['stats_retention'] ) ? (int) $this->settings['stats_retention'] : 30;
        $purged_stats = $stats_repo->purge_old_stats( $retention );

        if ( ! empty( $this->settings['debug_mode'] ) ) {
            error_log( sprintf(
                '[Social Proof LIVE] Daily maintenance: %d sessions purged, %d stats rows purged.',
                $purged_sessions,
                $purged_stats
            ) );
        }
    }
}
