<?php
/**
 * Impression Endpoint — records a widget impression for conversion tracking.
 *
 * @package SocialProofLive\Api
 */

namespace SocialProofLive\Api;

use SocialProofLive\Core\Session_Manager;
use SocialProofLive\Database\Stats_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Impression_Endpoint
 *
 * POST /splive/v1/impression — fired once per session when the widget is shown.
 * Throttled per session+product to avoid double counting.
 */
class Impression_Endpoint extends Rest_Controller {

    /**
     * Register routes.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route( $this->namespace, '/impression', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'record_impression' ),
            'permission_callback' => array( $this, 'public_permission_check' ),
            'args'                => array(
                'product_id' => array(
                    'required'          => true,
                    'validate_callback' => array( $this, 'validate_product_id' ),
                    'sanitize_callback' => array( $this, 'sanitize_product_id' ),
                ),
            ),
        ) );
    }

    /**
     * Record a widget impression (throttled).
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function record_impression( $request ) {
        if ( empty( $this->settings['enable_conversion_tracking'] ) ) {
            return $this->success( array( 'status' => 'disabled' ) );
        }

        $product_id   = $request->get_param( 'product_id' );
        $session_hash = $request->get_param( 'session_hash' );

        // Build a throttle key. Fall back to IP if no valid session hash.
        if ( ! Session_Manager::validate_session_hash( (string) $session_hash ) ) {
            $session_hash = Session_Manager::generate_session_hash(
                Session_Manager::get_visitor_ip(),
                Session_Manager::get_visitor_user_agent()
            );
        }

        $throttle_key = 'splive_imp_' . substr( $session_hash, 0, 16 ) . '_' . $product_id;

        // Only count one impression per session+product per hour.
        if ( false === get_transient( $throttle_key ) ) {
            $stats = new Stats_Repository();
            $stats->increment_metric( $product_id, 'impressions', 1 );
            set_transient( $throttle_key, 1, HOUR_IN_SECONDS );
        }

        return $this->success( array( 'status' => 'ok' ) );
    }
}
