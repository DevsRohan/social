<?php
/**
 * Admin Endpoint — dashboard data, analytics, and settings API.
 *
 * @package SocialProofLive\Api
 */

namespace SocialProofLive\Api;

use SocialProofLive\Core\Viewer_Tracker;
use SocialProofLive\Database\Session_Repository;
use SocialProofLive\Database\Stats_Repository;
use SocialProofLive\Cache\Cache_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin_Endpoint
 *
 * Admin-only REST endpoints for dashboard, analytics, and settings.
 */
class Admin_Endpoint extends Rest_Controller {

    /**
     * Register routes.
     *
     * @return void
     */
    public function register_routes() {
        // Dashboard overview.
        register_rest_route( $this->namespace, '/admin/overview', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_overview' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
        ) );

        // Analytics data.
        register_rest_route( $this->namespace, '/admin/analytics', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_analytics' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
            'args'                => array(
                'start_date' => array(
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'end_date'   => array(
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );

        // Save settings.
        register_rest_route( $this->namespace, '/admin/settings', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_settings' ),
                'permission_callback' => array( $this, 'admin_permission_check' ),
            ),
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'save_settings' ),
                'permission_callback' => array( $this, 'admin_permission_check' ),
            ),
        ) );

        // Onboarding complete.
        register_rest_route( $this->namespace, '/admin/onboarding-complete', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'complete_onboarding' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
        ) );
    }

    /**
     * Get dashboard overview data.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_overview( $request ) {
        $viewer_tracker = new Viewer_Tracker( $this->settings );
        $session_repo   = new Session_Repository();
        $stats_repo     = new Stats_Repository();

        $active_viewers  = $viewer_tracker->get_total_viewers();
        $today_sessions  = $session_repo->get_today_session_count();
        $top_products    = $viewer_tracker->get_top_products( 10 );
        $hourly_data     = $stats_repo->get_today_hourly();
        $summary         = $stats_repo->get_summary(
            gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
            gmdate( 'Y-m-d' )
        );

        $avg_concurrent = ! empty( $summary['avg_viewers'] ) ? round( (float) $summary['avg_viewers'], 1 ) : 0;

        $response = array(
            'active_viewers'  => $active_viewers,
            'today_sessions'  => $today_sessions,
            'avg_concurrent'  => $avg_concurrent,
            'top_products'    => $top_products,
            'hourly_data'     => $hourly_data,
            'cache_driver'    => Cache_Manager::get_instance()->get_driver_type(),
            'plugin_version'  => SPLIVE_VERSION,
        );

        return $this->success( $response );
    }

    /**
     * Get analytics data for date range.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_analytics( $request ) {
        $start_date = $request->get_param( 'start_date' );
        $end_date   = $request->get_param( 'end_date' );

        if ( empty( $start_date ) ) {
            $start_date = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
        }
        if ( empty( $end_date ) ) {
            $end_date = gmdate( 'Y-m-d' );
        }

        $stats_repo = new Stats_Repository();

        $response = array(
            'summary'      => $stats_repo->get_summary( $start_date, $end_date ),
            'hourly_data'  => $stats_repo->get_stats_range( 0, $start_date, $end_date ),
            'top_products' => $stats_repo->get_top_products_by_sessions( 20, $start_date, $end_date ),
            'start_date'   => $start_date,
            'end_date'     => $end_date,
        );

        return $this->success( $response );
    }

    /**
     * Get current settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_settings( $request ) {
        return $this->success( $this->settings );
    }

    /**
     * Save settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function save_settings( $request ) {
        $input    = $request->get_json_params();
        $defaults = \SocialProofLive\Plugin::get_default_settings();
        $sanitized = array();

        // Sanitize each setting based on its type.
        foreach ( $defaults as $key => $default ) {
            if ( ! array_key_exists( $key, $input ) ) {
                $sanitized[ $key ] = $default;
                continue;
            }

            $value = $input[ $key ];

            if ( is_bool( $default ) ) {
                $sanitized[ $key ] = (bool) $value;
            } elseif ( is_int( $default ) ) {
                $sanitized[ $key ] = absint( $value );
            } elseif ( is_float( $default ) ) {
                $sanitized[ $key ] = (float) $value;
            } elseif ( is_array( $default ) ) {
                $sanitized[ $key ] = is_array( $value ) ? array_map( 'absint', $value ) : array();
            } else {
                $sanitized[ $key ] = sanitize_text_field( $value );
            }
        }

        // Validate ranges.
        $sanitized['heartbeat_interval'] = max( 15, min( 120, $sanitized['heartbeat_interval'] ) );
        $sanitized['session_timeout']    = max( 30, min( 600, $sanitized['session_timeout'] ) );
        $sanitized['display_delay']      = max( 0, min( 5000, $sanitized['display_delay'] ) );
        $sanitized['minimum_viewers']    = max( 1, min( 100, $sanitized['minimum_viewers'] ) );
        $sanitized['minimum_cart']       = max( 0, min( 100, $sanitized['minimum_cart'] ) );
        $sanitized['cache_ttl']          = max( 1, min( 60, $sanitized['cache_ttl'] ) );
        $sanitized['stats_retention']    = max( 7, min( 365, $sanitized['stats_retention'] ) );
        $sanitized['border_radius']      = max( 0, min( 30, $sanitized['border_radius'] ) );

        // Validate color.
        if ( ! preg_match( '/^#[a-fA-F0-9]{6}$/', $sanitized['accent_color'] ) ) {
            $sanitized['accent_color'] = '#FF6B35';
        }

        // Validate selects.
        $valid_themes     = array( 'default', 'minimal', 'bold', 'glass' );
        $valid_positions  = array( 'after_add_to_cart', 'before_add_to_cart', 'after_price', 'after_summary', 'shortcode' );
        $valid_animations = array( 'fade', 'slide-up', 'fade-slide', 'bounce', 'scale', 'none' );
        $valid_icons      = array( 'emoji', 'icon', 'none' );
        $valid_schemes    = array( 'auto', 'light', 'dark' );
        $valid_fonts      = array( 'inherit', 'sm', 'md', 'lg' );

        if ( ! in_array( $sanitized['theme'], $valid_themes, true ) ) {
            $sanitized['theme'] = 'default';
        }
        if ( ! in_array( $sanitized['widget_position'], $valid_positions, true ) ) {
            $sanitized['widget_position'] = 'after_add_to_cart';
        }
        if ( ! in_array( $sanitized['animation_style'], $valid_animations, true ) ) {
            $sanitized['animation_style'] = 'fade-slide';
        }
        if ( ! in_array( $sanitized['icon_style'], $valid_icons, true ) ) {
            $sanitized['icon_style'] = 'emoji';
        }
        if ( ! in_array( $sanitized['color_scheme'], $valid_schemes, true ) ) {
            $sanitized['color_scheme'] = 'auto';
        }
        if ( ! in_array( $sanitized['font_size'], $valid_fonts, true ) ) {
            $sanitized['font_size'] = 'inherit';
        }

        update_option( 'splive_settings', $sanitized );

        // Clear cache on settings change.
        Cache_Manager::get_instance()->flush();

        return $this->success( array(
            'success'  => true,
            'settings' => $sanitized,
        ) );
    }

    /**
     * Mark onboarding as complete.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function complete_onboarding( $request ) {
        update_option( 'splive_onboarding_complete', true );
        delete_option( 'splive_show_onboarding' );

        return $this->success( array( 'success' => true ) );
    }
}
