<?php
/**
 * Plugin Name: Social Proof LIVE
 * Plugin URI: https://devsarun.io/
 * Description: Show REAL-TIME visitor activity on WooCommerce product pages — live viewer counts, cart activity, recent purchases, FOMO sales popups, stock urgency, sale countdowns, and a site-wide live visitor badge. 100% real data, zero fakes.
 * Version: 1.2.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: DevsArun
 * Author URI: https://devsarun.io/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: social-proof-live
 * Domain Path: /languages
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 *
 * @package SocialProofLive
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin constants.
 */
define( 'SPLIVE_VERSION', '1.2.0' );
define( 'SPLIVE_DB_VERSION', '1.2.0' );
define( 'SPLIVE_PLUGIN_FILE', __FILE__ );
define( 'SPLIVE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPLIVE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SPLIVE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'SPLIVE_MIN_PHP_VERSION', '7.4' );
define( 'SPLIVE_MIN_WP_VERSION', '5.8' );
define( 'SPLIVE_MIN_WC_VERSION', '5.0' );

/**
 * Autoloader.
 */
spl_autoload_register( function ( $class ) {
    $prefix = 'SocialProofLive\\';
    $base_dir = SPLIVE_PLUGIN_DIR . 'includes/';

    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );
    $relative_class = strtolower( $relative_class );
    $relative_class = str_replace( '_', '-', $relative_class );
    $relative_class = str_replace( '\\', '/', $relative_class );

    $parts = explode( '/', $relative_class );
    $filename = 'class-' . array_pop( $parts );
    $path = $base_dir;

    if ( ! empty( $parts ) ) {
        $path .= implode( '/', $parts ) . '/';
    }

    $file = $path . $filename . '.php';

    if ( file_exists( $file ) ) {
        require_once $file;
    }
});

/**
 * Check minimum requirements before loading the plugin.
 *
 * @return bool
 */
function splive_check_requirements() {
    $errors = array();

    if ( version_compare( PHP_VERSION, SPLIVE_MIN_PHP_VERSION, '<' ) ) {
        $errors[] = sprintf(
            /* translators: 1: Required PHP version, 2: Current PHP version */
            __( 'Social Proof LIVE requires PHP %1$s or higher. You are running PHP %2$s.', 'social-proof-live' ),
            SPLIVE_MIN_PHP_VERSION,
            PHP_VERSION
        );
    }

    global $wp_version;
    if ( version_compare( $wp_version, SPLIVE_MIN_WP_VERSION, '<' ) ) {
        $errors[] = sprintf(
            /* translators: 1: Required WP version, 2: Current WP version */
            __( 'Social Proof LIVE requires WordPress %1$s or higher. You are running WordPress %2$s.', 'social-proof-live' ),
            SPLIVE_MIN_WP_VERSION,
            $wp_version
        );
    }

    if ( ! empty( $errors ) ) {
        add_action( 'admin_notices', function () use ( $errors ) {
            foreach ( $errors as $error ) {
                echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
            }
        });
        return false;
    }

    return true;
}

/**
 * Check if WooCommerce is active.
 *
 * @return bool
 */
function splive_is_woocommerce_active() {
    return class_exists( 'WooCommerce' );
}

/**
 * Register custom cron schedules.
 *
 * Registered at the top level (not inside splive_init) so the interval is
 * available during the plugin activation request, when `plugins_loaded`
 * has not fired for this plugin yet. Without this, the cleanup cron event
 * fails to schedule.
 *
 * @param array $schedules Existing cron schedules.
 * @return array Modified schedules.
 */
function splive_register_cron_schedules( $schedules ) {
    if ( ! isset( $schedules['splive_five_minutes'] ) ) {
        $schedules['splive_five_minutes'] = array(
            'interval' => 300,
            'display'  => __( 'Every 5 Minutes (Social Proof LIVE)', 'social-proof-live' ),
        );
    }
    return $schedules;
}
add_filter( 'cron_schedules', 'splive_register_cron_schedules' );

/**
 * Initialize the plugin.
 */
function splive_init() {
    if ( ! splive_check_requirements() ) {
        return;
    }

    if ( ! splive_is_woocommerce_active() ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-warning"><p>';
            esc_html_e( 'Social Proof LIVE requires WooCommerce to be installed and activated for full functionality. Cart and purchase tracking will be disabled.', 'social-proof-live' );
            echo '</p></div>';
        });
    }

    // Load text domain.
    load_plugin_textdomain( 'social-proof-live', false, dirname( SPLIVE_PLUGIN_BASENAME ) . '/languages' );

    // Boot the plugin.
    $plugin = new SocialProofLive\Plugin();
    $plugin->init();
}
add_action( 'plugins_loaded', 'splive_init' );

/**
 * Activation hook.
 */
register_activation_hook( __FILE__, function () {
    require_once SPLIVE_PLUGIN_DIR . 'includes/class-activator.php';
    SocialProofLive\Activator::activate();
});

/**
 * Deactivation hook.
 */
register_deactivation_hook( __FILE__, function () {
    require_once SPLIVE_PLUGIN_DIR . 'includes/class-deactivator.php';
    SocialProofLive\Deactivator::deactivate();
});

/**
 * Declare WooCommerce HPOS compatibility.
 */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});
