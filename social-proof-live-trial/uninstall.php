<?php
/**
 * Plugin uninstall handler.
 *
 * Fired when the plugin is deleted via WordPress admin.
 * Removes all database tables, options, and transients.
 *
 * @package SocialProofLive
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Drop custom tables.
$tables = array(
    $wpdb->prefix . 'splive_sessions',
    $wpdb->prefix . 'splive_stats',
);

foreach ( $tables as $table ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// Remove all plugin options.
$options = array(
    'splive_settings',
    'splive_version',
    'splive_db_version',
    'splive_activated_at',
    'splive_onboarding_complete',
    'splive_show_onboarding',
    'splive_license_key',
    'splive_license_status',
);

foreach ( $options as $option ) {
    delete_option( $option );
}

// Remove all transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_splive_%' OR option_name LIKE '_transient_timeout_splive_%'"
);

// Clear any scheduled cron events.
$cron_hooks = array(
    'splive_cleanup_sessions',
    'splive_aggregate_stats',
    'splive_daily_maintenance',
);

foreach ( $cron_hooks as $hook ) {
    $timestamp = wp_next_scheduled( $hook );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, $hook );
    }
}

// Clear object cache.
wp_cache_flush();
