<?php
/**
 * Logger — debug logging utility.
 *
 * @package SocialProofLive\Utils
 */

namespace SocialProofLive\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Class Logger
 *
 * Provides structured debug logging when debug mode is enabled.
 */
class Logger {

    /**
     * Log a debug message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public static function debug( $message, $context = array() ) {
        if ( ! self::is_debug_enabled() ) {
            return;
        }

        self::log( 'DEBUG', $message, $context );
    }

    /**
     * Log an info message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public static function info( $message, $context = array() ) {
        if ( ! self::is_debug_enabled() ) {
            return;
        }

        self::log( 'INFO', $message, $context );
    }

    /**
     * Log a warning message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public static function warning( $message, $context = array() ) {
        self::log( 'WARNING', $message, $context );
    }

    /**
     * Log an error message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context data.
     * @return void
     */
    public static function error( $message, $context = array() ) {
        self::log( 'ERROR', $message, $context );
    }

    /**
     * Write a log entry.
     *
     * @param string $level   Log level.
     * @param string $message Message.
     * @param array  $context Context data.
     * @return void
     */
    private static function log( $level, $message, $context = array() ) {
        $entry = sprintf(
            '[Social Proof LIVE] [%s] %s',
            $level,
            $message
        );

        if ( ! empty( $context ) ) {
            $entry .= ' | Context: ' . wp_json_encode( $context );
        }

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log( $entry );
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool
     */
    private static function is_debug_enabled() {
        $settings = get_option( 'splive_settings', array() );
        return ! empty( $settings['debug_mode'] ) && ( defined( 'WP_DEBUG' ) && WP_DEBUG );
    }
}
