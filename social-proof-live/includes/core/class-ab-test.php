<?php
/**
 * A/B Test — splits visitors into control/treatment to PROVE conversion lift.
 *
 * @package SocialProofLive\Core
 */

namespace SocialProofLive\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class AB_Test
 *
 * Assigns each visitor to a variant (persisted in the WooCommerce session,
 * which uses WooCommerce's own cookie — no extra cookies added):
 *  - "control"   : social proof is hidden
 *  - "treatment" : social proof is shown
 *
 * Conversions are attributed per variant so the plugin can report the
 * real, measured conversion lift and extra revenue it generated.
 */
class AB_Test {

    const SESSION_KEY  = 'splive_variant';
    const COUNTED_KEY  = 'splive_ab_counted';

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
     * Is the A/B test feature enabled?
     *
     * @return bool
     */
    public function is_enabled() {
        return ! empty( $this->settings['enable_ab_test'] );
    }

    /**
     * Get the WooCommerce session object if available.
     *
     * @return \WC_Session|null
     */
    private function wc_session() {
        if ( function_exists( 'WC' ) && WC()->session ) {
            return WC()->session;
        }
        return null;
    }

    /**
     * Get the current visitor's variant.
     *
     * Fails open to "treatment" when no session is available so social
     * proof still shows (and the test simply isn't recorded for that hit).
     *
     * @return string 'control' or 'treatment'.
     */
    public function get_variant() {
        if ( ! $this->is_enabled() ) {
            return 'treatment';
        }

        $session = $this->wc_session();
        if ( ! $session ) {
            return 'treatment';
        }

        $variant = $session->get( self::SESSION_KEY );

        if ( 'control' === $variant || 'treatment' === $variant ) {
            return $variant;
        }

        $variant = $this->assign_variant();
        $session->set( self::SESSION_KEY, $variant );

        return $variant;
    }

    /**
     * Randomly assign a variant based on the configured control percentage.
     *
     * @return string
     */
    private function assign_variant() {
        $control_pct = isset( $this->settings['ab_control_percent'] ) ? (int) $this->settings['ab_control_percent'] : 15;
        $control_pct = max( 5, min( 50, $control_pct ) );

        // wp_rand is cryptographically stronger than rand().
        $roll = wp_rand( 1, 100 );

        return ( $roll <= $control_pct ) ? 'control' : 'treatment';
    }

    /**
     * Record the visitor once per session per day for their variant.
     *
     * @param string $variant Variant.
     * @return void
     */
    public function maybe_count_visitor( $variant ) {
        if ( ! $this->is_enabled() ) {
            return;
        }

        $session = $this->wc_session();
        if ( ! $session ) {
            return;
        }

        $today        = gmdate( 'Y-m-d' );
        $last_counted = $session->get( self::COUNTED_KEY );

        if ( $last_counted === $today ) {
            return; // Already counted today.
        }

        $repo = new \SocialProofLive\Database\Stats_Repository();
        $repo->record_ab_visitor( $variant );

        $session->set( self::COUNTED_KEY, $today );
    }
}
