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
            $css .= '--splive-font-size: ' . ( $sizes[ $font ] ?? '14px' ) . ';';
        }

        $css .= '}';

        return $css;
    }
}
