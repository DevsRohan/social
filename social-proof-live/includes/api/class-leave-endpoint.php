<?php
/**
 * Leave Endpoint — marks a session as inactive when visitor leaves.
 *
 * @package SocialProofLive\Api
 */

namespace SocialProofLive\Api;

use SocialProofLive\Core\Session_Manager;
use SocialProofLive\Core\Viewer_Tracker;
use SocialProofLive\Database\Session_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Leave_Endpoint
 *
 * POST /splive/v1/leave
 * Called via Beacon API when visitor navigates away from product page.
 */
class Leave_Endpoint extends Rest_Controller {

    /**
     * Register routes.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route( $this->namespace, '/leave', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'handle_leave' ),
            'permission_callback' => array( $this, 'public_permission_check' ),
            'args'                => array(
                'product_id'   => array(
                    'required'          => true,
                    'validate_callback' => array( $this, 'validate_product_id' ),
                    'sanitize_callback' => array( $this, 'sanitize_product_id' ),
                ),
                'session_hash' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );
    }

    /**
     * Handle leave request.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_leave( $request ) {
        $product_id   = $request->get_param( 'product_id' );
        $session_hash = $request->get_param( 'session_hash' );

        // Validate session hash format.
        if ( ! Session_Manager::validate_session_hash( $session_hash ) ) {
            return $this->error( 'invalid_session', __( 'Invalid session hash.', 'social-proof-live' ), 400 );
        }

        // Deactivate the session.
        $session_repo = new Session_Repository();
        $session_repo->deactivate_session( $session_hash, $product_id );

        // Invalidate cache.
        $viewer_tracker = new Viewer_Tracker( $this->settings );
        $viewer_tracker->invalidate_cache( $product_id );

        return $this->success( array( 'status' => 'ok' ) );
    }
}
