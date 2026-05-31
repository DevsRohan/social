<?php
/**
 * Admin Menu — registers admin menu items.
 *
 * @package SocialProofLive\Admin
 */

namespace SocialProofLive\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin_Menu
 *
 * Registers the Social Proof LIVE menu under WooCommerce.
 */
class Admin_Menu {

    /**
     * Initialize menu hooks.
     *
     * @return void
     */
    public function init() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
    }

    /**
     * Register admin menu pages.
     *
     * @return void
     */
    public function register_menu() {
        // Main menu page.
        add_menu_page(
            __( 'Social Proof LIVE', 'social-proof-live' ),
            __( 'Social Proof', 'social-proof-live' ),
            'manage_woocommerce',
            'social-proof-live',
            array( $this, 'render_dashboard' ),
            'data:image/svg+xml;base64,' . base64_encode( $this->get_menu_icon() ),
            56
        );

        // Dashboard submenu (same as parent).
        add_submenu_page(
            'social-proof-live',
            __( 'Dashboard', 'social-proof-live' ),
            __( 'Dashboard', 'social-proof-live' ),
            'manage_woocommerce',
            'social-proof-live',
            array( $this, 'render_dashboard' )
        );

        // Settings.
        add_submenu_page(
            'social-proof-live',
            __( 'Settings', 'social-proof-live' ),
            __( 'Settings', 'social-proof-live' ),
            'manage_woocommerce',
            'social-proof-live-settings',
            array( $this, 'render_settings' )
        );

        // Analytics.
        add_submenu_page(
            'social-proof-live',
            __( 'Analytics', 'social-proof-live' ),
            __( 'Analytics', 'social-proof-live' ),
            'manage_woocommerce',
            'social-proof-live-analytics',
            array( $this, 'render_analytics' )
        );
    }

    /**
     * Render dashboard page.
     *
     * @return void
     */
    public function render_dashboard() {
        include SPLIVE_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render settings page.
     *
     * @return void
     */
    public function render_settings() {
        include SPLIVE_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Render analytics page.
     *
     * @return void
     */
    public function render_analytics() {
        include SPLIVE_PLUGIN_DIR . 'admin/views/analytics.php';
    }

    /**
     * Get the SVG menu icon.
     *
     * @return string SVG markup.
     */
    private function get_menu_icon() {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14.5a6.5 6.5 0 110-13 6.5 6.5 0 010 13zm-1-9.5a1 1 0 112 0v3.5a1 1 0 01-.5.87l-2.5 1.5a1 1 0 01-1-1.74L9 9.87V7z"/></svg>';
    }
}
