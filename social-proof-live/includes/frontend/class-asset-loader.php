<?php
/**
 * Asset Loader — conditionally enqueues frontend JS/CSS.
 *
 * @package SocialProofLive\Frontend
 */

namespace SocialProofLive\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Class Asset_Loader
 *
 * Enqueues frontend assets only on WooCommerce single product pages.
 */
class Asset_Loader {

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
     * Initialize asset loading.
     *
     * @return void
     */
    public function init() {
        add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_global' ) );
    }

    /**
     * Conditionally enqueue assets on product pages.
     *
     * @return void
     */
    public function maybe_enqueue() {
        if ( ! $this->should_load() ) {
            return;
        }

        $this->enqueue_styles();
        $this->enqueue_scripts();
    }

    /**
     * Check if assets should be loaded on this page.
     *
     * @return bool
     */
    private function should_load() {
        // Load on single product pages.
        if ( function_exists( 'is_product' ) && is_product() ) {
            return true;
        }

        // Also load if shortcode is used (checked via has_shortcode later).
        global $post;
        if ( $post && has_shortcode( $post->post_content, 'social_proof_live' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Enqueue frontend styles.
     *
     * @return void
     */
    private function enqueue_styles() {
        wp_enqueue_style(
            'social-proof-live',
            SPLIVE_PLUGIN_URL . 'public/css/social-proof-live.css',
            array(),
            SPLIVE_VERSION
        );

        // Inline custom CSS for accent color.
        $custom_css = $this->generate_custom_css();
        if ( $custom_css ) {
            wp_add_inline_style( 'social-proof-live', $custom_css );
        }
    }

    /**
     * Enqueue frontend scripts.
     *
     * @return void
     */
    private function enqueue_scripts() {
        wp_enqueue_script(
            'social-proof-live',
            SPLIVE_PLUGIN_URL . 'public/js/social-proof-live.js',
            array(),
            SPLIVE_VERSION,
            true
        );

        // Pass configuration to JavaScript.
        wp_localize_script( 'social-proof-live', 'spliveConfig', array(
            'restUrl'          => rest_url( 'splive/v1/' ),
            'nonce'            => wp_create_nonce( 'wp_rest' ),
            'heartbeatInterval' => (int) $this->settings['heartbeat_interval'] * 1000,
            'displayDelay'     => (int) $this->settings['display_delay'],
            'animationStyle'   => $this->settings['animation_style'],
            'minimumViewers'   => (int) $this->settings['minimum_viewers'],
            'minimumCart'      => (int) $this->settings['minimum_cart'],
            'textViewers'      => $this->settings['text_viewers'],
            'textViewersSingular' => $this->settings['text_viewers_singular'],
            'textCart'         => $this->settings['text_cart'],
            'textCartSingular' => $this->settings['text_cart_singular'],
            'textPurchase'     => $this->settings['text_purchase'],
            'enableViewers'    => (bool) $this->settings['enable_viewers'],
            'enableCart'       => (bool) $this->settings['enable_cart'],
            'enablePurchase'   => (bool) $this->settings['enable_purchase'],
            'enableStock'      => (bool) $this->settings['enable_stock'],
            'enableCountdown'  => (bool) $this->settings['enable_countdown'],
            'textStock'        => $this->settings['text_stock'],
            'textCountdown'    => $this->settings['text_countdown'],
            'enableDemand'     => (bool) $this->settings['enable_demand_score'],
            'enableConversionTracking' => (bool) $this->settings['enable_conversion_tracking'],
        ) );
    }

    /**
     * Generate custom CSS based on settings.
     *
     * @return string CSS string.
     */

    private function generate_custom_css() {
        $accent = sanitize_hex_color( $this->settings['accent_color'] );
        $radius = absint( $this->settings['border_radius'] );
        $font   = $this->settings['font_size'];

        $css = ':root {';
        $css .= '--splive-accent: ' . $accent . ';';
        $css .= '--splive-accent-light: ' . $accent . '14;';
        $css .= '--splive-radius: ' . $radius . 'px;';

        if ( 'inherit' !== $font ) {
            $sizes = array( 'sm' => '12px', 'md' => '14px', 'lg' => '16px' );
            $css .= '--splive-font-size: ' . ( isset( $sizes[ $font ] ) ? $sizes[ $font ] : '14px' ) . ';';
        }

        $css .= '}';

        return $css;
    }

    /**
     * Enqueue site-wide assets (FOMO notifications + visitor badge).
     *
     * @return void
     */
    public function maybe_enqueue_global() {
        $notifications_on = ! empty( $this->settings['enable_notifications'] );
        $badge_on         = ! empty( $this->settings['enable_visitor_badge'] );

        if ( ( ! $notifications_on && ! $badge_on ) || is_admin() || is_feed() ) {
            return;
        }

        wp_enqueue_style(
            'splive-notifications',
            SPLIVE_PLUGIN_URL . 'public/css/notifications.css',
            array(),
            SPLIVE_VERSION
        );

        $custom_css = $this->generate_custom_css();
        if ( $custom_css ) {
            wp_add_inline_style( 'splive-notifications', $custom_css );
        }

        wp_enqueue_script(
            'splive-notifications',
            SPLIVE_PLUGIN_URL . 'public/js/notifications.js',
            array(),
            SPLIVE_VERSION,
            true
        );

        $is_product_page = function_exists( 'is_product' ) && is_product();

        wp_localize_script( 'splive-notifications', 'spliveGlobal', array(
            'restUrl'             => rest_url( 'splive/v1/' ),
            'nonce'               => wp_create_nonce( 'wp_rest' ),
            'pollInterval'        => max( 10, (int) $this->settings['heartbeat_interval'] ) * 1000,
            'enableNotifications' => $notifications_on,
            'notifPosition'       => $this->settings['notif_position'],
            'notifDisplayTime'    => (int) $this->settings['notif_display_time'] * 1000,
            'notifGap'            => (int) $this->settings['notif_gap'] * 1000,
            'notifInitialDelay'   => (int) $this->settings['notif_initial_delay'] * 1000,
            'notifLoop'           => (bool) $this->settings['notif_loop'],
            'notifShowImage'      => (bool) $this->settings['notif_show_image'],
            'notifShowLocation'   => (bool) $this->settings['notif_show_location'],
            'notifShowTime'       => (bool) $this->settings['notif_show_time'],
            'notifClickToProduct' => (bool) $this->settings['notif_click_to_product'],
            'notifHideOnMobile'   => (bool) $this->settings['notif_hide_on_mobile'],
            'notifSound'          => (bool) $this->settings['notif_sound'],
            'textNotif'           => $this->settings['text_notif'],
            'textNotifNoLocation' => $this->settings['text_notif_no_location'],
            'textNotifVerb'       => $this->settings['text_notif_verb'],
            'enableBadge'         => $badge_on,
            'badgePosition'       => $this->settings['badge_position'],
            'textBadge'           => $this->settings['text_badge'],
            'iconBadge'           => $this->settings['icon_badge'],
            'allowedDevices'      => array_values( (array) $this->settings['rules_devices'] ),
            'sendGlobalHeartbeat' => ! $is_product_page,
        ) );
    }
}
