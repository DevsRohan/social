<?php
/**
 * Plugin activation handler.
 *
 * @package SocialProofLive
 */

namespace SocialProofLive;

defined( 'ABSPATH' ) || exit;

/**
 * Class Activator
 *
 * Handles all activation tasks: creates database tables, sets defaults, schedules cron.
 */
class Activator {

    /**
     * Run activation tasks.
     *
     * @return void
     */
    public static function activate() {
        self::check_requirements();
        self::create_tables();
        self::set_default_options();
        self::schedule_cron();
        self::set_activation_flag();

        flush_rewrite_rules();
    }

    /**
     * Check minimum requirements on activation.
     *
     * @return void
     */
    private static function check_requirements() {
        if ( version_compare( PHP_VERSION, SPLIVE_MIN_PHP_VERSION, '<' ) ) {
            deactivate_plugins( SPLIVE_PLUGIN_BASENAME );
            wp_die(
                sprintf(
                    /* translators: %s: Required PHP version */
                    esc_html__( 'Social Proof LIVE requires PHP %s or higher.', 'social-proof-live' ),
                    SPLIVE_MIN_PHP_VERSION
                ),
                'Plugin Activation Error',
                array( 'back_link' => true )
            );
        }
    }

    /**
     * Create custom database tables.
     *
     * @return void
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $sessions_table  = $wpdb->prefix . 'splive_sessions';
        $stats_table     = $wpdb->prefix . 'splive_stats';

        $sql_sessions = "CREATE TABLE {$sessions_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_hash VARCHAR(64) NOT NULL,
            product_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            ip_hash VARCHAR(64) NOT NULL DEFAULT '',
            user_agent_hash VARCHAR(64) NOT NULL DEFAULT '',
            started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY session_product (session_hash, product_id),
            KEY idx_product_active (product_id, is_active, last_seen),
            KEY idx_last_seen (last_seen),
            KEY idx_active (is_active)
        ) {$charset_collate};";

        $sql_stats = "CREATE TABLE {$stats_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            stat_date DATE NOT NULL,
            stat_hour TINYINT(2) UNSIGNED NOT NULL DEFAULT 0,
            max_viewers INT(11) UNSIGNED NOT NULL DEFAULT 0,
            avg_viewers DECIMAL(8,1) NOT NULL DEFAULT 0.0,
            total_sessions INT(11) UNSIGNED NOT NULL DEFAULT 0,
            impressions INT(11) UNSIGNED NOT NULL DEFAULT 0,
            cart_additions INT(11) UNSIGNED NOT NULL DEFAULT 0,
            purchases INT(11) UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY product_date_hour (product_id, stat_date, stat_hour),
            KEY idx_stat_date (stat_date),
            KEY idx_product_id (product_id)
        ) {$charset_collate};";

        $experiments_table = $wpdb->prefix . 'splive_experiments';

        $sql_experiments = "CREATE TABLE {$experiments_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            stat_date DATE NOT NULL,
            variant VARCHAR(12) NOT NULL,
            visitors INT(11) UNSIGNED NOT NULL DEFAULT 0,
            conversions INT(11) UNSIGNED NOT NULL DEFAULT 0,
            revenue DECIMAL(14,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY date_variant (stat_date, variant),
            KEY idx_stat_date (stat_date)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_sessions );
        dbDelta( $sql_stats );
        dbDelta( $sql_experiments );
    }

    /**
     * Set default plugin options.
     *
     * @return void
     */
    private static function set_default_options() {
        if ( ! get_option( 'splive_settings' ) ) {
            update_option( 'splive_settings', Plugin::get_default_settings() );
        }

        update_option( 'splive_version', SPLIVE_VERSION );
        update_option( 'splive_db_version', SPLIVE_DB_VERSION );

        if ( ! get_option( 'splive_activated_at' ) ) {
            update_option( 'splive_activated_at', current_time( 'mysql' ) );
        }
    }

    /**
     * Schedule cron events.
     *
     * @return void
     */
    private static function schedule_cron() {
        // Ensure the custom interval is registered (the main plugin file registers
        // this at top level, but we add it defensively here too).
        if ( function_exists( 'splive_register_cron_schedules' ) ) {
            add_filter( 'cron_schedules', 'splive_register_cron_schedules' );
        }

        if ( ! wp_next_scheduled( 'splive_cleanup_sessions' ) ) {
            wp_schedule_event( time(), 'splive_five_minutes', 'splive_cleanup_sessions' );
        }

        if ( ! wp_next_scheduled( 'splive_aggregate_stats' ) ) {
            wp_schedule_event( time(), 'hourly', 'splive_aggregate_stats' );
        }

        if ( ! wp_next_scheduled( 'splive_daily_maintenance' ) ) {
            wp_schedule_event( time(), 'daily', 'splive_daily_maintenance' );
        }
    }

    /**
     * Set activation flag for onboarding.
     *
     * @return void
     */
    private static function set_activation_flag() {
        if ( ! get_option( 'splive_onboarding_complete' ) ) {
            update_option( 'splive_show_onboarding', true );
            // Trigger a one-time redirect to the onboarding wizard.
            set_transient( 'splive_activation_redirect', true, 60 );
        }
    }
}
