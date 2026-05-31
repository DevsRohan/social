<?php
/**
 * Session Manager — handles visitor session creation and validation.
 *
 * @package SocialProofLive\Core
 */

namespace SocialProofLive\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Session_Manager
 *
 * Creates anonymous session hashes, validates sessions, detects bots.
 */
class Session_Manager {

    /**
     * Plugin settings.
     *
     * @var array
     */
    private $settings;

    /**
     * Known bot user agent patterns.
     *
     * @var array
     */
    private static $bot_patterns = array(
        'bot',
        'crawl',
        'spider',
        'slurp',
        'mediapartners',
        'facebookexternalhit',
        'twitterbot',
        'linkedinbot',
        'pingdom',
        'pagespeed',
        'lighthouse',
        'gtmetrix',
        'semrush',
        'ahrefs',
        'mj12bot',
        'dotbot',
        'petalbot',
        'yandexbot',
        'bingbot',
        'googlebot',
        'baiduspider',
        'duckduckbot',
    );

    /**
     * Constructor.
     *
     * @param array $settings Plugin settings.
     */
    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    /**
     * Initialize session manager hooks.
     *
     * @return void
     */
    public function init() {
        // No persistent hooks needed — session logic is invoked via REST API.
    }

    /**
     * Generate a session hash from request data.
     *
     * Creates an anonymous, non-reversible identifier from IP + User Agent + a daily salt.
     * This ensures the same visitor gets the same hash within a day without storing PII.
     *
     * @param string $ip         Visitor IP address.
     * @param string $user_agent Visitor user agent string.
     * @return string 64-character hex hash.
     */
    public static function generate_session_hash( $ip, $user_agent ) {
        $daily_salt = wp_salt( 'auth' ) . gmdate( 'Y-m-d' );
        $raw        = $ip . '|' . $user_agent . '|' . $daily_salt;

        return hash( 'sha256', $raw );
    }

    /**
     * Hash an IP address for storage (non-reversible).
     *
     * @param string $ip IP address.
     * @return string Hashed IP.
     */
    public static function hash_ip( $ip ) {
        return hash( 'sha256', $ip . wp_salt( 'secure_auth' ) );
    }

    /**
     * Hash a user agent string for storage (non-reversible).
     *
     * @param string $user_agent User agent string.
     * @return string Hashed user agent.
     */
    public static function hash_user_agent( $user_agent ) {
        return hash( 'sha256', $user_agent . wp_salt( 'logged_in' ) );
    }

    /**
     * Validate a session hash format.
     *
     * @param string $hash Session hash to validate.
     * @return bool True if valid hex string of correct length.
     */
    public static function validate_session_hash( $hash ) {
        return is_string( $hash ) && 1 === preg_match( '/^[a-f0-9]{64}$/', $hash );
    }

    /**
     * Detect if the current request is from a bot.
     *
     * @param string $user_agent User agent string.
     * @return bool True if detected as bot.
     */
    public static function is_bot( $user_agent ) {
        if ( empty( $user_agent ) ) {
            return true;
        }

        $ua_lower = strtolower( $user_agent );

        foreach ( self::$bot_patterns as $pattern ) {
            if ( false !== strpos( $ua_lower, $pattern ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the visitor's real IP address (handles proxies).
     *
     * @return string IP address.
     */
    public static function get_visitor_ip() {
        $headers = array(
            'HTTP_CF_CONNECTING_IP',  // Cloudflare.
            'HTTP_X_FORWARDED_FOR',   // Standard proxy.
            'HTTP_X_REAL_IP',         // Nginx proxy.
            'REMOTE_ADDR',            // Direct connection.
        );

        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
                // X-Forwarded-For can contain multiple IPs — take the first.
                if ( false !== strpos( $ip, ',' ) ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get the visitor's user agent.
     *
     * @return string User agent string.
     */
    public static function get_visitor_user_agent() {
        return isset( $_SERVER['HTTP_USER_AGENT'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
            : '';
    }

    /**
     * Check if rate limiting should block this request.
     *
     * Allows max 1 heartbeat per 10 seconds per session.
     *
     * @param string $session_hash Session identifier.
     * @param int    $product_id   Product ID.
     * @return bool True if request should be allowed.
     */
    public function check_rate_limit( $session_hash, $product_id ) {
        $cache_key = 'splive_rate_' . substr( $session_hash, 0, 16 ) . '_' . $product_id;
        $last_time = get_transient( $cache_key );

        if ( false !== $last_time && ( time() - (int) $last_time ) < 10 ) {
            return false; // Rate limited.
        }

        set_transient( $cache_key, time(), 30 );
        return true;
    }
}
