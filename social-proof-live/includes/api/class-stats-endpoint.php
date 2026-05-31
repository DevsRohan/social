<?php
/**
 * Stats Endpoint — returns current live counts for a product.
 *
 * @package SocialProofLive\Api
 */

namespace SocialProofLive\Api;

use SocialProofLive\Core\Data_Aggregator;

defined( 'ABSPATH' ) || exit;

/**
 * Class Stats_Endpoint
 *
 * GET /splive/v1/stats/{product_id}
 * Returns social proof data without recording a heartbeat.
 */
class Stats_Endpoint extends Rest_Controller {

    /**
     * Register routes.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route( $this->namespace, '/stats/(?P<product_id>\d+)', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_stats' ),
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
     * Handle stats request.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_stats( $request ) {
        $product_id = $request->get_param( 'product_id' );

        $aggregator = new Data_Aggregator( $this->settings );
        $data       = $aggregator->get_product_data( $product_id );
        $response   = $aggregator->format_response( $data );

        return $this->success( $response );
    }
}
