<?php
/**
 * Admin Pages — handles admin page rendering and asset loading.
 *
 * @package SocialProofLive\Admin
 */

namespace SocialProofLive\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin_Pages
 *
 * Enqueues admin assets and provides data to admin JS.
 */
class Admin_Pages {

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
     * Initialize admin pages.
     *
     * @return void
     */
    public function init() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_filter( 'plugin_action_links_' . SPLIVE_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
    }

    /**
     * Enqueue admin assets only on our pages.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_assets( $hook ) {
        // Reliably detect our admin pages via the `page` query param rather than
        // depending on the menu hook suffix (which varies by menu title).
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

        $our_pages = array(
            'social-proof-live',
            'social-proof-live-settings',
            'social-proof-live-analytics',
        );

        if ( ! in_array( $page, $our_pages, true ) ) {
            return;
        }

        // Admin CSS.
        wp_enqueue_style(
            'splive-admin',
            SPLIVE_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            SPLIVE_VERSION
        );

        // Google Fonts - Inter.
        wp_enqueue_style(
            'splive-font-inter',
            'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
            array(),
            SPLIVE_VERSION
        );

        // Admin JS.
        wp_enqueue_script(
            'splive-admin',
            SPLIVE_PLUGIN_URL . 'admin/js/admin.js',
            array(),
            SPLIVE_VERSION,
            true
        );

        // Localize script data.
        wp_localize_script( 'splive-admin', 'spliveAdmin', array(
            'restUrl'       => rest_url( 'splive/v1/' ),
            'nonce'         => wp_create_nonce( 'wp_rest' ),
            'settings'      => $this->settings,
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'pluginUrl'     => SPLIVE_PLUGIN_URL,
            'isOnboarding'  => (bool) get_option( 'splive_show_onboarding', false ),
            'strings'       => array(
                'saving'        => __( 'Saving...', 'social-proof-live' ),
                'saved'         => __( 'Settings saved successfully!', 'social-proof-live' ),
                'error'         => __( 'An error occurred. Please try again.', 'social-proof-live' ),
                'confirm_reset' => __( 'Reset all settings to defaults? This cannot be undone.', 'social-proof-live' ),
            ),
        ) );
    }

    /**
     * Add action links on plugins page.
     *
     * @param array $links Existing links.
     * @return array Modified links.
     */
    public function add_action_links( $links ) {
        $custom_links = array(
            '<a href="' . admin_url( 'admin.php?page=social-proof-live-settings' ) . '">' . __( 'Settings', 'social-proof-live' ) . '</a>',
            '<a href="' . admin_url( 'admin.php?page=social-proof-live' ) . '">' . __( 'Dashboard', 'social-proof-live' ) . '</a>',
        );

        return array_merge( $custom_links, $links );
    }
}
