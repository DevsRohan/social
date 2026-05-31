<?php
/**
 * Validator — input validation rules.
 *
 * @package SocialProofLive\Utils
 */

namespace SocialProofLive\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Class Validator
 *
 * Provides validation methods for REST API and settings inputs.
 */
class Validator {

    /**
     * Validate that a product exists in WooCommerce.
     *
     * @param int $product_id Product ID to validate.
     * @return bool True if product exists.
     */
    public static function product_exists( $product_id ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            // If WooCommerce isn't active, just check post exists.
            return 'product' === get_post_type( $product_id );
        }

        $product = wc_get_product( $product_id );
        return false !== $product;
    }

    /**
     * Validate a date string.
     *
     * @param string $date Date string.
     * @param string $format Expected format.
     * @return bool True if valid.
     */
    public static function is_valid_date( $date, $format = 'Y-m-d' ) {
        $d = \DateTime::createFromFormat( $format, $date );
        return $d && $d->format( $format ) === $date;
    }

    /**
     * Validate a hex color.
     *
     * @param string $color Color value.
     * @return bool True if valid hex color.
     */
    public static function is_hex_color( $color ) {
        return (bool) preg_match( '/^#[a-fA-F0-9]{6}$/', $color );
    }

    /**
     * Validate an integer is within range.
     *
     * @param mixed $value Value to check.
     * @param int   $min   Minimum.
     * @param int   $max   Maximum.
     * @return bool True if valid.
     */
    public static function int_in_range( $value, $min, $max ) {
        if ( ! is_numeric( $value ) ) {
            return false;
        }
        $int = (int) $value;
        return $int >= $min && $int <= $max;
    }

    /**
     * Validate a session hash format.
     *
     * @param string $hash Hash to validate.
     * @return bool True if valid.
     */
    public static function is_valid_session_hash( $hash ) {
        return is_string( $hash ) && preg_match( '/^[a-f0-9]{64}$/', $hash );
    }

    /**
     * Validate URL format.
     *
     * @param string $url URL to validate.
     * @return bool True if valid.
     */
    public static function is_valid_url( $url ) {
        return (bool) filter_var( $url, FILTER_VALIDATE_URL );
    }
}
