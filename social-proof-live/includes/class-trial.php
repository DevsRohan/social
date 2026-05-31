<?php
/**
 * Trial mode — 24-hour self-expiring build.
 *
 * Only active when the main plugin file defines:  define( 'SPLIVE_TRIAL', true );
 * In that build the plugin runs fully for 24 hours, then deactivates and
 * deletes itself automatically.
 *
 * @package SocialProofLive
 */

namespace SocialProofLive;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trial
 */
class Trial {

    const OPTION_START = 'splive_trial_started';
    const DURATION     = 86400; // 24 hours in seconds.

    /**
     * Initialize trial enforcement.
     *
     * @return bool True to continue loading; false if expired (and handled).
     */
    public function init() {
        if ( ! defined( 'SPLIVE_TRIAL' ) || ! SPLIVE_TRIAL ) {
            return true; // Not a trial build — full plugin.
        }

        $start = (int) get_option( self::OPTION_START, 0 );
        if ( ! $start ) {
            $start = time();
            update_option( self::OPTION_START, $start );
        }

        if ( $this->remaining( $start ) <= 0 ) {
            $this->self_destruct();
            return false;
        }

        // Still in trial — show a countdown banner in the admin.
        add_action( 'admin_notices', array( $this, 'render_banner' ) );
        return true;
    }

    /**
     * Seconds remaining in the trial.
     *
     * @param int $start Start timestamp.
     * @return int
     */
    private function remaining( $start ) {
        return ( $start + self::DURATION ) - time();
    }

    /**
     * Deactivate and delete the plugin once the trial ends.
     *
     * @return void
     */
    private function self_destruct() {
        if ( ! function_exists( 'deactivate_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Stop the plugin running on the next request.
        deactivate_plugins( SPLIVE_PLUGIN_BASENAME );

        // Leave a flag so the admin sees why it vanished.
        set_transient( 'splive_trial_expired', 1, DAY_IN_SECONDS );

        // Only attempt file deletion inside the admin (filesystem APIs available).
        if ( is_admin() ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            // Initialise the WP filesystem.
            if ( function_exists( 'WP_Filesystem' ) ) {
                WP_Filesystem();
            }

            if ( function_exists( 'delete_plugins' ) ) {
                // This also runs uninstall.php, removing tables, options & cron.
                delete_plugins( array( SPLIVE_PLUGIN_BASENAME ) );
            }
        }

        add_action( 'admin_notices', array( $this, 'render_expired_notice' ) );
    }

    /**
     * Render the live trial countdown banner.
     *
     * @return void
     */
    public function render_banner() {
        $start     = (int) get_option( self::OPTION_START, time() );
        $remaining = max( 0, $this->remaining( $start ) );
        $hours     = floor( $remaining / 3600 );
        $minutes   = floor( ( $remaining % 3600 ) / 60 );

        echo '<div class="notice notice-warning"><p>';
        printf(
            /* translators: 1: hours, 2: minutes */
            esc_html__( '⏳ Social Proof LIVE — Trial Mode: %1$dh %2$dm left. After that the plugin removes itself automatically.', 'social-proof-live' ),
            (int) $hours,
            (int) $minutes
        );
        echo ' <a href="https://devsarun.io/" target="_blank" rel="noopener"><strong>' . esc_html__( 'Upgrade to Pro →', 'social-proof-live' ) . '</strong></a>';
        echo '</p></div>';
    }

    /**
     * Render the "trial expired" notice.
     *
     * @return void
     */
    public function render_expired_notice() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__( 'Your Social Proof LIVE trial has ended and the plugin has been removed.', 'social-proof-live' );
        echo ' <a href="https://devsarun.io/" target="_blank" rel="noopener"><strong>' . esc_html__( 'Get the Pro version →', 'social-proof-live' ) . '</strong></a>';
        echo '</p></div>';
    }
}
