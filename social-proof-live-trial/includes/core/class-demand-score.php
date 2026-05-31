<?php
/**
 * Demand Score — combines real signals into a single 0-100 "demand" score.
 *
 * @package SocialProofLive\Core
 */

namespace SocialProofLive\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Demand_Score
 *
 * Aggregates live viewers, cart activity, recent sales velocity, and stock
 * scarcity into one transparent demand score with a label and level. This
 * powers the animated "demand meter" on product pages.
 */
class Demand_Score {

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
     * Calculate the demand score for a product.
     *
     * @param int      $product_id Product ID.
     * @param int      $viewers    Current live viewers.
     * @param int      $cart       Current cart count.
     * @param int|null $stock      Remaining stock (null if not managed).
     * @return array|null Score data or null if disabled.
     */
    public function calculate( $product_id, $viewers, $cart, $stock ) {
        if ( empty( $this->settings['enable_demand_score'] ) ) {
            return null;
        }

        // Configurable "caps" — the value at which a signal contributes 100%.
        $viewers_cap = max( 1, (int) ( $this->settings['demand_viewers_cap'] ?? 20 ) );
        $cart_cap    = max( 1, (int) ( $this->settings['demand_cart_cap'] ?? 10 ) );
        $velocity_cap = max( 1, (int) ( $this->settings['demand_velocity_cap'] ?? 10 ) );

        // Sales velocity (real recent purchases).
        $purchase_tracker = new Purchase_Tracker( $this->settings );
        $velocity = $purchase_tracker->get_recent_purchase_count( $product_id, 72 );

        // Normalise each signal to 0..1.
        $viewers_n  = min( (int) $viewers / $viewers_cap, 1 );
        $cart_n     = min( (int) $cart / $cart_cap, 1 );
        $velocity_n = min( $velocity / $velocity_cap, 1 );

        // Scarcity: lower stock => higher demand pressure.
        $scarcity_n = 0;
        if ( null !== $stock && $stock > 0 ) {
            $threshold = max( 1, (int) ( $this->settings['stock_threshold'] ?? 10 ) );
            if ( $stock <= $threshold ) {
                $scarcity_n = 1 - ( $stock / $threshold );
            }
        }

        // Weighted blend (weights sum to 1).
        $score = ( 0.35 * $viewers_n ) + ( 0.30 * $cart_n ) + ( 0.25 * $velocity_n ) + ( 0.10 * $scarcity_n );
        $score = (int) round( $score * 100 );
        $score = max( 0, min( 100, $score ) );

        /**
         * Filter the computed demand score.
         *
         * @param int $score      0-100 score.
         * @param int $product_id Product ID.
         */
        $score = (int) apply_filters( 'splive_demand_score', $score, $product_id );

        $min_show = isset( $this->settings['demand_min_show'] ) ? (int) $this->settings['demand_min_show'] : 20;
        if ( $score < $min_show ) {
            return null;
        }

        return array(
            'score' => $score,
            'level' => $this->level_for( $score ),
            'label' => $this->label_for( $score ),
        );
    }

    /**
     * Get the level keyword for a score.
     *
     * @param int $score Score.
     * @return string
     */
    private function level_for( $score ) {
        if ( $score >= 80 ) {
            return 'hot';
        }
        if ( $score >= 60 ) {
            return 'high';
        }
        if ( $score >= 40 ) {
            return 'trending';
        }
        return 'rising';
    }

    /**
     * Get the human label for a score.
     *
     * @param int $score Score.
     * @return string
     */
    private function label_for( $score ) {
        if ( $score >= 80 ) {
            return __( 'Selling Fast', 'social-proof-live' );
        }
        if ( $score >= 60 ) {
            return __( 'High Demand', 'social-proof-live' );
        }
        if ( $score >= 40 ) {
            return __( 'Trending', 'social-proof-live' );
        }
        return __( 'Getting Attention', 'social-proof-live' );
    }
}
