<?php
/**
 * Base REST Controller — shared functionality for all endpoints.
 *
 * @package SocialProofLive\Api
 */

namespace SocialProofLive\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Class Rest_Controller
 *
 * Base class providing common REST API functionality.
 */
class Rest_Controller {

    /**
     * API namespace.
     *
     * @var string
     */
    protected $namespace = 'splive/v1';

    /**
     * Plugin settings.
     *
     * @var array
     */
    protected $settings;

    /**
     * Constructor.
     *
     * @param array $settings Plugin settings.
     */
    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    /**
     * Build a success response.
     *
     * @param array $data Response data.
     * @param int   $code HTTP status code.
     * @return \WP_REST_Response
     */
    protected function success( $data = array(), $code = 200 ) {
        return new \WP_REST_Response( $data, $code );
    }

    /**
     * Build an error response.
     *
     * @param string $code    Error code.
     * @param string $message Error message.
     * @param int    $status  HTTP status code.
     * @return \WP_Error
     */
    protected function error( $code, $message, $status = 400 ) {
        return new \WP_Error( $code, $message, array( 'status' => $status ) );
    }

    /**
     * Admin permission callback.
     *
     * @param \WP_REST_Request $request Request object.
     * @return bool
     */
    public function admin_permission_check( $request ) {
        return current_user_can( 'manage_woocommerce' );
    }

    /**
     * Public permission callback (allows all).
     *
     * @param \WP_REST_Request $request Request object.
     * @return bool
     */
    public function public_permission_check( $request ) {
        return true;
    }

    /**
     * Validate product ID argument.
     *
     * @param mixed           $value   Value to validate.
     * @param \WP_REST_Request $request Request object.
     * @param string          $param   Parameter name.
     * @return bool
     */
    public function validate_product_id( $value, $request, $param ) {
        return is_numeric( $value ) && (int) $value > 0;
    }

    /**
     * Sanitize product ID argument.
     *
     * @param mixed $value Value to sanitize.
     * @return int
     */
    public function sanitize_product_id( $value ) {
        return absint( $value );
    }
}
