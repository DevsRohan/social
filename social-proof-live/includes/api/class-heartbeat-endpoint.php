<?php
/**
 * Heartbeat Endpoint — receives visitor pings and returns live counts.
 *
 * @package SocialProofLive\Api
 */

namespace SocialProofLive\Api;

use SocialProofLive\Core\Session_Manager;
use SocialProofLive\Core\Data_Aggregator;
use SocialProofLive\Database\Session_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Heartbeat_Endpoint
 *
 * POST /splive/v1/heartbeat
 * Records a visitor heartbeat and returns current social proof data.
 */
class Heartbeat_Endpoint extends Rest_Controller {

    /**
     * Register routes.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route( $this->namespace, '/heartbeat', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'handle_heartbeat' ),
            'permission_callback' => array( $this, 'public_permission_check' ),
            'args'                => array(
                'product_id'   => array(
                    'required'          => true,
                    'validate_callback' => array( $this, 'validate_product_id' ),
                    'sanitize_callback' => array( $this, 'sanitize_product_id' ),
                ),
                'session_hash' => array(
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );
    }

    /**
     * Handle heartbeat request.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_heartbeat( $request ) {
        $product_id = $request->get_param( 'product_id' );

        // Get visitor info.
        $ip         = Session_Manager::get_visitor_ip();
        $user_agent = Session_Manager::get_visitor_user_agent();

        // Bot detection.
        if ( empty( $this->settings['count_bots'] ) && Session_Manager::is_bot( $user_agent ) ) {
            // Still return data but don't count this visitor.
            $aggregator = new Data_Aggregator( $this->settings );
            $data       = $aggregator->get_product_data( $product_id );
            return $this->success( $aggregator->format_response( $data ) );
        }

        // Generate or validate session hash.
        $session_hash = $request->get_param( 'session_hash' );
        $server_hash  = Session_Manager::generate_session_hash( $ip, $user_agent );

        // If client didn't send a hash, or it doesn't match, use server-generated.
        if ( empty( $session_hash ) || ! Session_Manager::validate_session_hash( $session_hash ) ) {
            $session_hash = $server_hash;
        }

        // Rate limiting.
        $session_mgr = new Session_Manager( $this->settings );
        if ( ! $session_mgr->check_rate_limit( $session_hash, $product_id ) ) {
            // Return cached data without updating session.
            $aggregator = new Data_Aggregator( $this->settings );
            $data       = $aggregator->get_product_data( $product_id );
            $response   = $aggregator->format_response( $data );
            $response['session_hash'] = $session_hash;
            return $this->success( $response );
        }

        // Record/update session.
        $ip_hash = Session_Manager::hash_ip( $ip );
        $ua_hash = Session_Manager::hash_user_agent( $user_agent );

        $session_repo = new Session_Repository();
        $session_repo->upsert_session( $session_hash, $product_id, $ip_hash, $ua_hash );

        // Get aggregated data.
        $aggregator = new Data_Aggregator( $this->settings );
        $data       = $aggregator->get_product_data( $product_id );
        $response   = $aggregator->format_response( $data );

        // Include session hash for client to reuse.
        $response['session_hash'] = $session_hash;

        return $this->success( $response );
    }
}
