<?php
/**
 * Notifications Endpoint — recent sales feed + site-wide visitor count.
 *
 * @package SocialProofLive\Api
 */

namespace SocialProofLive\Api;

use SocialProofLive\Notifications\Sales_Notifications;
use SocialProofLive\Core\Viewer_Tracker;
use SocialProofLive\Core\Display_Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Class Notifications_Endpoint
 *
 * GET /splive/v1/notifications — returns FOMO sales feed and the live
 * site-wide visitor count for the floating badge.
 */
class Notifications_Endpoint extends Rest_Controller {

    /**
     * Register routes.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route( $this->namespace, '/notifications', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_notifications' ),
            'permission_callback' => array( $this, 'public_permission_check' ),
        ) );
    }


    /**
     * Handle notifications request.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_notifications( $request ) {
        $response = array(
            'feed'          => array(),
            'visitor_count' => null,
            'in_schedule'   => true,
        );

        // Server-side schedule/day rule check.
        $rules = new Display_Rules( $this->settings );
        if ( ! $rules->is_within_schedule() ) {
            $response['in_schedule'] = false;
            return $this->success( $response );
        }

        // Recent sales FOMO feed.
        if ( ! empty( $this->settings['enable_notifications'] ) ) {
            $sales = new Sales_Notifications( $this->settings );
            $response['feed'] = $sales->get_feed();
        }

        // Site-wide live visitor count for the floating badge.
        if ( ! empty( $this->settings['enable_visitor_badge'] ) ) {
            $viewer_tracker = new Viewer_Tracker( $this->settings );
            $total          = $viewer_tracker->get_total_viewers();
            $min            = isset( $this->settings['badge_min_visitors'] ) ? (int) $this->settings['badge_min_visitors'] : 3;

            $response['visitor_count'] = ( $total >= $min ) ? $total : null;
        }

        return $this->success( $response );
    }
}
