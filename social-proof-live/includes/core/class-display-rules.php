<?php
/**
 * Display Rules — controls when/where social proof is shown.
 *
 * @package SocialProofLive\Core
 */

namespace SocialProofLive\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Display_Rules
 *
 * Evaluates schedule (days + hours) and device rules. Day/hour checks run
 * server-side; device checks run client-side (config flags).
 */
class Display_Rules {

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
     * Check whether the current time is within the configured schedule.
     *
     * @return bool True if allowed to display.
     */
    public function is_within_schedule() {
        $allowed_days = isset( $this->settings['rules_days'] ) ? (array) $this->settings['rules_days'] : array();

        // If no day restriction configured, always allow.
        if ( ! empty( $allowed_days ) ) {
            $today = strtolower( gmdate( 'D', current_time( 'timestamp' ) ) ); // mon, tue, ...
            $map   = array(
                'mon' => 'mon', 'tue' => 'tue', 'wed' => 'wed', 'thu' => 'thu',
                'fri' => 'fri', 'sat' => 'sat', 'sun' => 'sun',
            );
            $today_key = isset( $map[ $today ] ) ? $map[ $today ] : $today;

            if ( ! in_array( $today_key, $allowed_days, true ) ) {
                return false;
            }
        }

        // Hour window.
        $start = isset( $this->settings['rules_hour_start'] ) ? (int) $this->settings['rules_hour_start'] : 0;
        $end   = isset( $this->settings['rules_hour_end'] ) ? (int) $this->settings['rules_hour_end'] : 23;
        $hour  = (int) gmdate( 'G', current_time( 'timestamp' ) );

        if ( $start <= $end ) {
            if ( $hour < $start || $hour > $end ) {
                return false;
            }
        } else {
            // Overnight window (e.g. 22 -> 6).
            if ( $hour < $start && $hour > $end ) {
                return false;
            }
        }

        // Logged-in only rule.
        if ( ! empty( $this->settings['rules_logged_in_only'] ) && ! is_user_logged_in() ) {
            return false;
        }

        return true;
    }

    /**
     * Get device rules for the client (which device types are allowed).
     *
     * @return array
     */
    public function get_allowed_devices() {
        return isset( $this->settings['rules_devices'] ) && is_array( $this->settings['rules_devices'] )
            ? array_values( $this->settings['rules_devices'] )
            : array( 'desktop', 'tablet', 'mobile' );
    }
}
