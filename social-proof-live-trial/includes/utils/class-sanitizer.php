<?php
/**
 * Sanitizer — centralized input sanitization.
 *
 * @package SocialProofLive\Utils
 */

namespace SocialProofLive\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Class Sanitizer
 *
 * Provides sanitization methods for all input types.
 */
class Sanitizer {

    /**
     * Sanitize a hex color value.
     *
     * @param string $color Color value.
     * @return string Sanitized color or default.
     */
    public static function color( $color ) {
        $color = sanitize_hex_color( $color );
        return $color ? $color : '#FF6B35';
    }

    /**
     * Sanitize a positive integer.
     *
     * @param mixed $value Value to sanitize.
     * @param int   $min   Minimum allowed value.
     * @param int   $max   Maximum allowed value.
     * @return int Sanitized integer.
     */
    public static function int_range( $value, $min = 0, $max = PHP_INT_MAX ) {
        $value = absint( $value );
        return max( $min, min( $max, $value ) );
    }

    /**
     * Sanitize a boolean value.
     *
     * @param mixed $value Value to sanitize.
     * @return bool
     */
    public static function bool( $value ) {
        return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
    }

    /**
     * Sanitize an array of integers.
     *
     * @param mixed $value Value to sanitize.
     * @return array Array of positive integers.
     */
    public static function int_array( $value ) {
        if ( ! is_array( $value ) ) {
            return array();
        }
        return array_map( 'absint', array_filter( $value ) );
    }

    /**
     * Sanitize text that allows limited HTML (for custom messages).
     *
     * @param string $value Text to sanitize.
     * @return string Sanitized text.
     */
    public static function text_with_placeholders( $value ) {
        $value = sanitize_text_field( $value );
        // Ensure placeholders are preserved.
        return $value;
    }

    /**
     * Sanitize a session hash.
     *
     * @param string $hash Hash to validate.
     * @return string|false Sanitized hash or false.
     */
    public static function session_hash( $hash ) {
        $hash = sanitize_text_field( $hash );
        if ( preg_match( '/^[a-f0-9]{64}$/', $hash ) ) {
            return $hash;
        }
        return false;
    }

    /**
     * Sanitize a select/dropdown value against allowed options.
     *
     * @param string $value   Selected value.
     * @param array  $allowed Allowed values.
     * @param string $default Default if not in allowed.
     * @return string Sanitized value.
     */
    public static function select( $value, $allowed, $default ) {
        $value = sanitize_text_field( $value );
        return in_array( $value, $allowed, true ) ? $value : $default;
    }
}
