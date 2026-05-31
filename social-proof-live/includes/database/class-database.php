<?php
/**
 * Database management class.
 *
 * @package SocialProofLive\Database
 */

namespace SocialProofLive\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Class Database
 *
 * Handles database initialization, version checking, and migrations.
 */
class Database {

    /**
     * Sessions table name.
     *
     * @var string
     */
    public static $sessions_table;

    /**
     * Stats table name.
     *
     * @var string
     */
    public static $stats_table;

    /**
     * Initialize database references.
     *
     * @return void
     */
    public function init() {
        global $wpdb;

        self::$sessions_table = $wpdb->prefix . 'splive_sessions';
        self::$stats_table    = $wpdb->prefix . 'splive_stats';

        // Check if database needs updating.
        $this->maybe_upgrade();

        // Register custom cron interval.
        add_filter( 'cron_schedules', array( $this, 'add_cron_intervals' ) );
    }

    /**
     * Check if database schema needs upgrading.
     *
     * @return void
     */
    private function maybe_upgrade() {
        $current_db_version = get_option( 'splive_db_version', '0' );

        if ( version_compare( $current_db_version, SPLIVE_DB_VERSION, '<' ) ) {
            require_once SPLIVE_PLUGIN_DIR . 'includes/class-activator.php';
            \SocialProofLive\Activator::activate();
        }
    }

    /**
     * Add custom cron intervals.
     *
     * @param array $schedules Existing cron schedules.
     * @return array Modified schedules.
     */
    public function add_cron_intervals( $schedules ) {
        $schedules['splive_five_minutes'] = array(
            'interval' => 300,
            'display'  => __( 'Every 5 Minutes', 'social-proof-live' ),
        );

        return $schedules;
    }

    /**
     * Get sessions table name.
     *
     * @return string
     */
    public static function get_sessions_table() {
        global $wpdb;
        return $wpdb->prefix . 'splive_sessions';
    }

    /**
     * Get stats table name.
     *
     * @return string
     */
    public static function get_stats_table() {
        global $wpdb;
        return $wpdb->prefix . 'splive_stats';
    }
}
