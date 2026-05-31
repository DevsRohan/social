<?php
/**
 * Sales Notifications — builds a real recent-sales feed for FOMO popups.
 *
 * @package SocialProofLive\Notifications
 */

namespace SocialProofLive\Notifications;

use SocialProofLive\Cache\Cache_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Class Sales_Notifications
 *
 * Builds a feed of recent real purchases (customer first name, location,
 * product, image, time-ago) for the FOMO notification popups.
 */
class Sales_Notifications {

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
     * Get the recent sales feed (cached).
     *
     * @return array List of notification events.
     */
    public function get_feed() {
        if ( ! function_exists( 'wc_get_orders' ) ) {
            return array();
        }

        $cache     = Cache_Manager::get_instance();
        $cache_key = 'notif_feed';
        $cached    = $cache->get( $cache_key );

        if ( false !== $cached && is_array( $cached ) ) {
            return $cached;
        }

        $feed = $this->build_feed();

        // Cache for 2 minutes — feed does not need to be real-time to the second.
        $cache->set( $cache_key, $feed, 120 );

        return $feed;
    }

    /**
     * Build the feed from recent WooCommerce orders.
     *
     * @return array
     */
    private function build_feed() {
        $max       = isset( $this->settings['notif_max_events'] ) ? (int) $this->settings['notif_max_events'] : 12;
        $lookback  = isset( $this->settings['notif_lookback_hours'] ) ? (int) $this->settings['notif_lookback_hours'] : 168;
        $after_ts  = time() - ( $lookback * HOUR_IN_SECONDS );

        $orders = wc_get_orders( array(
            'limit'        => $max * 2,
            'status'       => array( 'wc-completed', 'wc-processing' ),
            'orderby'      => 'date',
            'order'        => 'DESC',
            'date_created' => '>' . $after_ts,
            'return'       => 'objects',
        ) );

        if ( empty( $orders ) ) {
            return array();
        }

        $events = array();

        foreach ( $orders as $order ) {
            if ( ! is_a( $order, 'WC_Order' ) ) {
                continue;
            }

            $event = $this->build_event( $order );
            if ( $event ) {
                $events[] = $event;
            }

            if ( count( $events ) >= $max ) {
                break;
            }
        }

        /**
         * Filter the recent sales notification feed.
         *
         * @param array $events   Feed events.
         * @param array $settings Plugin settings.
         */
        return apply_filters( 'splive_notification_feed', $events, $this->settings );
    }


    /**
     * Build a single notification event from an order.
     *
     * @param \WC_Order $order Order object.
     * @return array|null Event data or null if not usable.
     */
    private function build_event( $order ) {
        // Pick a representative line item (first item with a valid product).
        $product   = null;
        $product_id = 0;

        foreach ( $order->get_items() as $item ) {
            $maybe_product = $item->get_product();
            if ( $maybe_product ) {
                $product    = $maybe_product;
                $product_id = $maybe_product->get_id();
                break;
            }
        }

        if ( ! $product ) {
            return null;
        }

        // Customer name (first name only for privacy).
        $first_name = $order->get_billing_first_name();
        if ( ! empty( $this->settings['notif_anonymize'] ) || empty( $first_name ) ) {
            $name = __( 'Someone', 'social-proof-live' );
        } else {
            $name = $this->sanitize_name( $first_name );
        }

        // Location (city, country).
        $location = $this->build_location( $order );

        // Product image.
        $image = '';
        if ( ! empty( $this->settings['notif_show_image'] ) ) {
            $image_id = $product->get_image_id();
            if ( $image_id ) {
                $src = wp_get_attachment_image_src( $image_id, 'thumbnail' );
                if ( $src ) {
                    $image = $src[0];
                }
            }
            if ( empty( $image ) && function_exists( 'wc_placeholder_img_src' ) ) {
                $image = wc_placeholder_img_src( 'thumbnail' );
            }
        }

        // Time ago.
        $date_created = $order->get_date_created();
        $seconds_ago  = $date_created ? ( time() - $date_created->getTimestamp() ) : 0;
        if ( $seconds_ago < 0 ) {
            $seconds_ago = 0;
        }

        return array(
            'name'        => $name,
            'location'    => $location,
            'product'     => $product->get_name(),
            'product_url' => $product->get_permalink(),
            'image'       => $image,
            'seconds_ago' => $seconds_ago,
            'time_human'  => $this->human_time( $seconds_ago ),
        );
    }

    /**
     * Build a location string from the order billing address.
     *
     * @param \WC_Order $order Order object.
     * @return string
     */
    private function build_location( $order ) {
        if ( empty( $this->settings['notif_show_location'] ) ) {
            return '';
        }

        $city    = $this->sanitize_name( $order->get_billing_city() );
        $country = $order->get_billing_country();

        if ( $city && $country ) {
            return $city . ', ' . $country;
        }
        if ( $city ) {
            return $city;
        }
        if ( $country ) {
            return $country;
        }

        return '';
    }


    /**
     * Sanitize a name/city for safe display.
     *
     * @param string $value Raw value.
     * @return string
     */
    private function sanitize_name( $value ) {
        $value = wp_strip_all_tags( (string) $value );
        $value = trim( $value );
        return $value;
    }

    /**
     * Convert seconds to a short human-readable string.
     *
     * @param int $seconds Seconds elapsed.
     * @return string
     */
    private function human_time( $seconds ) {
        if ( $seconds < 60 ) {
            return __( 'just now', 'social-proof-live' );
        }

        if ( $seconds < 3600 ) {
            $minutes = (int) floor( $seconds / 60 );
            return sprintf(
                /* translators: %d: number of minutes */
                _n( '%d minute ago', '%d minutes ago', $minutes, 'social-proof-live' ),
                $minutes
            );
        }

        if ( $seconds < 86400 ) {
            $hours = (int) floor( $seconds / 3600 );
            return sprintf(
                /* translators: %d: number of hours */
                _n( '%d hour ago', '%d hours ago', $hours, 'social-proof-live' ),
                $hours
            );
        }

        $days = (int) floor( $seconds / 86400 );
        return sprintf(
            /* translators: %d: number of days */
            _n( '%d day ago', '%d days ago', $days, 'social-proof-live' ),
            $days
        );
    }
}
