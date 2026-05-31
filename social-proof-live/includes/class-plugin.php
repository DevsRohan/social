<?php
/**
 * Main plugin orchestrator class.
 *
 * @package SocialProofLive
 */

namespace SocialProofLive;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 *
 * Central class that initializes all plugin components and registers hooks.
 */
class Plugin {

    /**
     * Plugin settings.
     *
     * @var array
     */
    private $settings;

    /**
     * Initialize the plugin.
     *
     * @return void
     */
    public function init() {
        $this->settings = $this->get_settings();

        // Core components.
        $this->init_database();
        $this->init_cache();
        $this->init_core();
        $this->init_api();

        // Frontend.
        if ( ! is_admin() ) {
            $this->init_frontend();
        }

        // Admin.
        if ( is_admin() ) {
            $this->init_admin();
        }

        // Cron.
        $this->init_cron();
    }

    /**
     * Initialize database layer.
     *
     * @return void
     */
    private function init_database() {
        $database = new Database\Database();
        $database->init();
    }

    /**
     * Initialize cache layer.
     *
     * @return void
     */
    private function init_cache() {
        $cache_manager = Cache\Cache_Manager::get_instance();
        $cache_manager->init();
    }

    /**
     * Initialize core tracking components.
     *
     * @return void
     */
    private function init_core() {
        $session_manager = new Core\Session_Manager( $this->settings );
        $session_manager->init();

        // Real conversion tracking (runs in admin + frontend so order hooks fire).
        $conversion_tracker = new Core\Conversion_Tracker( $this->settings );
        $conversion_tracker->init();
    }

    /**
     * Initialize REST API.
     *
     * @return void
     */
    private function init_api() {
        add_action( 'rest_api_init', function () {
            $heartbeat = new Api\Heartbeat_Endpoint( $this->settings );
            $heartbeat->register_routes();

            $stats = new Api\Stats_Endpoint( $this->settings );
            $stats->register_routes();

            $leave = new Api\Leave_Endpoint( $this->settings );
            $leave->register_routes();

            $admin_api = new Api\Admin_Endpoint( $this->settings );
            $admin_api->register_routes();

            $notifications = new Api\Notifications_Endpoint( $this->settings );
            $notifications->register_routes();

            $impression = new Api\Impression_Endpoint( $this->settings );
            $impression->register_routes();
        });
    }

    /**
     * Initialize frontend components.
     *
     * @return void
     */
    private function init_frontend() {
        $widget_renderer = new Frontend\Widget_Renderer( $this->settings );
        $widget_renderer->init();

        $asset_loader = new Frontend\Asset_Loader( $this->settings );
        $asset_loader->init();

        $shortcodes = new Frontend\Shortcodes( $this->settings );
        $shortcodes->init();
    }

    /**
     * Initialize admin components.
     *
     * @return void
     */
    private function init_admin() {
        $admin_menu = new Admin\Admin_Menu();
        $admin_menu->init();

        $admin_pages = new Admin\Admin_Pages( $this->settings );
        $admin_pages->init();

        $settings = new Admin\Settings();
        $settings->init();

        $onboarding = new Admin\Onboarding();
        $onboarding->init();
    }

    /**
     * Initialize cron jobs.
     *
     * @return void
     */
    private function init_cron() {
        $cleanup = new Core\Cleanup_Cron( $this->settings );
        $cleanup->init();
    }

    /**
     * Get plugin settings with defaults.
     *
     * @return array
     */
    public function get_settings() {
        $defaults = self::get_default_settings();
        $saved = get_option( 'splive_settings', array() );

        return wp_parse_args( $saved, $defaults );
    }

    /**
     * Get default settings.
     *
     * @return array
     */
    public static function get_default_settings() {
        return array(
            // Display.
            'enable_viewers'         => true,
            'enable_cart'            => true,
            'enable_purchase'        => true,
            'widget_position'        => 'after_add_to_cart',
            'display_delay'          => 1500,
            'minimum_viewers'        => 2,
            'minimum_cart'           => 1,

            // Appearance.
            'theme'                  => 'default',
            'color_scheme'           => 'auto',
            'accent_color'           => '#FF6B35',
            'icon_style'             => 'emoji',
            'animation_style'        => 'fade-slide',
            'border_radius'          => 8,
            'font_size'              => 'inherit',

            // Behavior.
            'heartbeat_interval'     => 20,
            'session_timeout'        => 120,
            'count_bots'             => false,
            'count_logged_in_only'   => false,
            'excluded_products'      => array(),
            'excluded_categories'    => array(),

            // Text.
            'text_viewers'           => '{count} people are viewing this right now',
            'text_cart'              => '{count} people have this in their cart',
            'text_purchase'          => 'Last purchased {time} ago',
            'text_viewers_singular'  => '1 person is viewing this right now',
            'text_cart_singular'     => '1 person has this in their cart',
            'icon_viewers'           => '🔥',
            'icon_cart'              => '⏰',
            'icon_purchase'          => '✓',

            // Advanced.
            'enable_rest_api'        => true,
            'ajax_fallback'          => true,
            'cache_ttl'              => 5,
            'cleanup_interval'       => 300,
            'stats_retention'        => 30,
            'debug_mode'             => false,
            'disable_on_mobile'      => false,

            // --- PREMIUM: Stock Urgency ---
            'enable_stock'           => true,
            'stock_threshold'        => 10,
            'text_stock'             => 'Hurry! Only {count} left in stock',
            'icon_stock'             => '📦',

            // --- PREMIUM: Sale Countdown ---
            'enable_countdown'       => true,
            'text_countdown'         => 'Sale ends in {time}',
            'icon_countdown'         => '⏳',

            // --- PREMIUM: Recent Sales FOMO Notifications ---
            'enable_notifications'   => true,
            'notif_position'         => 'bottom-left',
            'notif_lookback_hours'   => 168,
            'notif_max_events'       => 12,
            'notif_display_time'     => 6,
            'notif_gap'              => 8,
            'notif_initial_delay'    => 4,
            'notif_loop'             => true,
            'notif_show_image'       => true,
            'notif_show_location'    => true,
            'notif_show_time'        => true,
            'notif_anonymize'        => false,
            'notif_click_to_product' => true,
            'notif_hide_on_mobile'   => false,
            'notif_sound'            => false,
            'text_notif'             => '{name} from {location} purchased',
            'text_notif_no_location' => '{name} purchased',
            'text_notif_verb'        => 'just bought',

            // --- PREMIUM: Site-wide Live Visitor Counter ---
            'enable_visitor_badge'   => true,
            'badge_position'         => 'bottom-right',
            'badge_min_visitors'     => 3,
            'text_badge'             => '{count} people are browsing right now',
            'icon_badge'             => '🟢',

            // --- PREMIUM: Display Rules ---
            'rules_devices'          => array( 'desktop', 'tablet', 'mobile' ),
            'rules_days'             => array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ),
            'rules_hour_start'       => 0,
            'rules_hour_end'         => 23,
            'rules_logged_in_only'   => false,

            // --- PREMIUM: Conversion Tracking ---
            'enable_conversion_tracking' => true,
        );
    }
}
