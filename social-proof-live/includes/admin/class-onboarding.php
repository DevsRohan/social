<?php
/**
 * Onboarding — setup wizard for first-time users.
 *
 * @package SocialProofLive\Admin
 */

namespace SocialProofLive\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Onboarding
 *
 * Handles the onboarding wizard display and completion.
 */
class Onboarding {

    /**
     * Initialize onboarding hooks.
     *
     * @return void
     */
    public function init() {
        // Redirect to onboarding on first activation.
        add_action( 'admin_init', array( $this, 'maybe_redirect_to_onboarding' ) );
    }

    /**
     * Redirect to plugin dashboard if onboarding needed.
     *
     * @return void
     */
    public function maybe_redirect_to_onboarding() {
        if ( ! get_option( 'splive_show_onboarding' ) ) {
            return;
        }

        // Only redirect once.
        if ( get_transient( 'splive_activation_redirect' ) ) {
            delete_transient( 'splive_activation_redirect' );

            if ( ! isset( $_GET['page'] ) || 'social-proof-live' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
                wp_safe_redirect( admin_url( 'admin.php?page=social-proof-live&onboarding=1' ) );
                exit;
            }
        }
    }

    /**
     * Check if onboarding is complete.
     *
     * @return bool
     */
    public static function is_complete() {
        return (bool) get_option( 'splive_onboarding_complete', false );
    }

    /**
     * Mark onboarding as complete.
     *
     * @return void
     */
    public static function complete() {
        update_option( 'splive_onboarding_complete', true );
        delete_option( 'splive_show_onboarding' );
    }
}
