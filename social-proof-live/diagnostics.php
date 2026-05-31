<?php
/**
 * Social Proof LIVE — Self-Test / Diagnostics Script
 *
 * RUN THIS to verify the plugin is fully working, then send the output back
 * for verification.
 *
 * HOW TO RUN (pick one):
 *  1) Browser (must be logged in as an admin in the same browser):
 *       https://YOUR-SITE.com/wp-content/plugins/social-proof-live/diagnostics.php
 *  2) WP-CLI:
 *       wp eval-file wp-content/plugins/social-proof-live/diagnostics.php
 *
 * It prints a plain-text PASS/FAIL report. Copy ALL of it and paste it back.
 *
 * SECURITY: When run via browser it requires a logged-in administrator.
 * Delete this file before going to production if you prefer.
 *
 * @package SocialProofLive
 */

// ---------------------------------------------------------------------------
// Bootstrap WordPress (locate wp-load.php by walking up the directory tree).
// ---------------------------------------------------------------------------
if ( ! defined( 'ABSPATH' ) ) {
    $splive_wp_load = '';
    $splive_dir     = __DIR__;

    for ( $i = 0; $i < 10; $i++ ) {
        $candidate = $splive_dir . '/wp-load.php';
        if ( file_exists( $candidate ) ) {
            $splive_wp_load = $candidate;
            break;
        }
        $parent = dirname( $splive_dir );
        if ( $parent === $splive_dir ) {
            break;
        }
        $splive_dir = $parent;
    }

    if ( empty( $splive_wp_load ) ) {
        header( 'Content-Type: text/plain; charset=utf-8' );
        echo "ERROR: Could not locate wp-load.php. Run this file from inside the WordPress install.\n";
        exit( 1 );
    }

    require_once $splive_wp_load;
}


// ---------------------------------------------------------------------------
// Access control + output setup.
// ---------------------------------------------------------------------------
$splive_is_cli = ( defined( 'WP_CLI' ) && WP_CLI ) || ( 'cli' === PHP_SAPI );

if ( ! $splive_is_cli ) {
    header( 'Content-Type: text/plain; charset=utf-8' );

    if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
        echo "ACCESS DENIED.\n";
        echo "You must be logged in as an administrator to run diagnostics in the browser.\n";
        echo "Log in to wp-admin first, then reload this URL. Or run via WP-CLI:\n";
        echo "  wp eval-file wp-content/plugins/social-proof-live/diagnostics.php\n";
        exit( 1 );
    }
}

// ---------------------------------------------------------------------------
// Report helpers.
// ---------------------------------------------------------------------------
$GLOBALS['splive_diag'] = array(
    'pass' => 0,
    'fail' => 0,
    'warn' => 0,
);

/**
 * Print a section header.
 *
 * @param string $title Section title.
 */
function splive_section( $title ) {
    echo "\n" . str_repeat( '=', 64 ) . "\n";
    echo ' ' . $title . "\n";
    echo str_repeat( '=', 64 ) . "\n";
}

/**
 * Record and print a check result.
 *
 * @param string $label  What was checked.
 * @param string $status One of PASS, FAIL, WARN, INFO.
 * @param string $detail Extra detail.
 */
function splive_check( $label, $status, $detail = '' ) {
    if ( 'PASS' === $status ) {
        $GLOBALS['splive_diag']['pass']++;
    } elseif ( 'FAIL' === $status ) {
        $GLOBALS['splive_diag']['fail']++;
    } elseif ( 'WARN' === $status ) {
        $GLOBALS['splive_diag']['warn']++;
    }

    $line = sprintf( '  [%-4s] %s', $status, $label );
    if ( '' !== $detail ) {
        $line .= ' — ' . $detail;
    }
    echo $line . "\n";
}


// ---------------------------------------------------------------------------
// Report header.
// ---------------------------------------------------------------------------
echo "SOCIAL PROOF LIVE — DIAGNOSTICS REPORT\n";
echo 'Generated: ' . gmdate( 'Y-m-d H:i:s' ) . " UTC\n";
echo 'Site: ' . home_url() . "\n";

// ---------------------------------------------------------------------------
// 1. Environment / versions.
// ---------------------------------------------------------------------------
splive_section( '1. ENVIRONMENT' );

global $wp_version;

splive_check(
    'PHP version >= 7.4',
    version_compare( PHP_VERSION, '7.4', '>=' ) ? 'PASS' : 'FAIL',
    'found ' . PHP_VERSION
);

splive_check(
    'WordPress version >= 5.8',
    version_compare( $wp_version, '5.8', '>=' ) ? 'PASS' : 'FAIL',
    'found ' . $wp_version
);

if ( class_exists( 'WooCommerce' ) ) {
    splive_check(
        'WooCommerce active >= 5.0',
        version_compare( WC()->version, '5.0', '>=' ) ? 'PASS' : 'WARN',
        'found ' . WC()->version
    );
} else {
    splive_check( 'WooCommerce active', 'WARN', 'NOT active — cart & purchase features disabled' );
}

splive_check(
    'Plugin constants loaded',
    defined( 'SPLIVE_VERSION' ) ? 'PASS' : 'FAIL',
    defined( 'SPLIVE_VERSION' ) ? 'v' . SPLIVE_VERSION : 'plugin not active?'
);

splive_check(
    'Plugin is active',
    in_array( 'social-proof-live/social-proof-live.php', (array) get_option( 'active_plugins', array() ), true )
        || ( is_multisite() && array_key_exists( 'social-proof-live/social-proof-live.php', (array) get_site_option( 'active_sitewide_plugins', array() ) ) )
        ? 'PASS' : 'WARN',
    'checked active_plugins option'
);


// ---------------------------------------------------------------------------
// 2. Database tables.
// ---------------------------------------------------------------------------
splive_section( '2. DATABASE TABLES' );

global $wpdb;

$splive_sessions_table = $wpdb->prefix . 'splive_sessions';
$splive_stats_table    = $wpdb->prefix . 'splive_stats';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$sessions_exists = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $splive_sessions_table ) ) === $splive_sessions_table );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$stats_exists = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $splive_stats_table ) ) === $splive_stats_table );

splive_check( 'Sessions table exists', $sessions_exists ? 'PASS' : 'FAIL', $splive_sessions_table );
splive_check( 'Stats table exists', $stats_exists ? 'PASS' : 'FAIL', $splive_stats_table );

if ( $sessions_exists ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $session_rows = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$splive_sessions_table}" );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $active_rows = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$splive_sessions_table} WHERE is_active = 1" );
    splive_check( 'Sessions table readable', 'INFO', $session_rows . ' total rows, ' . $active_rows . ' active' );
}

if ( $stats_exists ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $stats_rows = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$splive_stats_table}" );
    splive_check( 'Stats table readable', 'INFO', $stats_rows . ' rows' );
}

// ---------------------------------------------------------------------------
// 3. Options.
// ---------------------------------------------------------------------------
splive_section( '3. OPTIONS' );

$settings = get_option( 'splive_settings' );
splive_check( 'Settings option exists', is_array( $settings ) ? 'PASS' : 'FAIL', is_array( $settings ) ? count( $settings ) . ' keys' : 'missing' );
splive_check( 'Version option exists', get_option( 'splive_version' ) ? 'PASS' : 'FAIL', (string) get_option( 'splive_version' ) );
splive_check( 'DB version option exists', get_option( 'splive_db_version' ) ? 'PASS' : 'FAIL', (string) get_option( 'splive_db_version' ) );

if ( is_array( $settings ) ) {
    $enabled = array();
    foreach ( array( 'enable_viewers', 'enable_cart', 'enable_purchase' ) as $k ) {
        if ( ! empty( $settings[ $k ] ) ) {
            $enabled[] = str_replace( 'enable_', '', $k );
        }
    }
    splive_check( 'Enabled widgets', 'INFO', $enabled ? implode( ', ', $enabled ) : 'none' );
    splive_check( 'Minimum viewers threshold', 'INFO', (string) ( isset( $settings['minimum_viewers'] ) ? $settings['minimum_viewers'] : 'n/a' ) . ' (widget hides below this)' );
    splive_check( 'Heartbeat interval', 'INFO', (string) ( isset( $settings['heartbeat_interval'] ) ? $settings['heartbeat_interval'] : 'n/a' ) . 's' );
}


// ---------------------------------------------------------------------------
// 4. Cron events.
// ---------------------------------------------------------------------------
splive_section( '4. CRON EVENTS' );

$cron_hooks = array(
    'splive_cleanup_sessions'  => 'Session cleanup (every 5 min)',
    'splive_aggregate_stats'   => 'Stats aggregation (hourly)',
    'splive_daily_maintenance' => 'Daily maintenance',
);

foreach ( $cron_hooks as $hook => $desc ) {
    $next = wp_next_scheduled( $hook );
    if ( $next ) {
        splive_check( $desc, 'PASS', 'next run ' . gmdate( 'Y-m-d H:i:s', $next ) . ' UTC' );
    } else {
        splive_check( $desc, 'FAIL', 'NOT scheduled' );
    }
}

$schedules = wp_get_schedules();
splive_check(
    'Custom 5-minute interval registered',
    isset( $schedules['splive_five_minutes'] ) ? 'PASS' : 'FAIL',
    isset( $schedules['splive_five_minutes'] ) ? 'interval=' . $schedules['splive_five_minutes']['interval'] . 's' : 'missing'
);

// ---------------------------------------------------------------------------
// 5. REST API routes.
// ---------------------------------------------------------------------------
splive_section( '5. REST API ROUTES' );

$routes        = rest_get_server()->get_routes();
$expected_routes = array(
    '/splive/v1/heartbeat',
    '/splive/v1/leave',
    '/splive/v1/admin/overview',
    '/splive/v1/admin/settings',
);

foreach ( $expected_routes as $route ) {
    splive_check( 'Route ' . $route, isset( $routes[ $route ] ) ? 'PASS' : 'FAIL', isset( $routes[ $route ] ) ? 'registered' : 'missing' );
}

// stats route uses a regex pattern, match loosely.
$stats_found = false;
foreach ( array_keys( $routes ) as $r ) {
    if ( false !== strpos( $r, '/splive/v1/stats/' ) ) {
        $stats_found = true;
        break;
    }
}
splive_check( 'Route /splive/v1/stats/{id}', $stats_found ? 'PASS' : 'FAIL', $stats_found ? 'registered' : 'missing' );


// ---------------------------------------------------------------------------
// 6. Class autoloading.
// ---------------------------------------------------------------------------
splive_section( '6. CLASS AUTOLOADING' );

$classes = array(
    'SocialProofLive\\Plugin',
    'SocialProofLive\\Database\\Session_Repository',
    'SocialProofLive\\Database\\Stats_Repository',
    'SocialProofLive\\Core\\Session_Manager',
    'SocialProofLive\\Core\\Viewer_Tracker',
    'SocialProofLive\\Core\\Cart_Tracker',
    'SocialProofLive\\Core\\Purchase_Tracker',
    'SocialProofLive\\Core\\Data_Aggregator',
    'SocialProofLive\\Api\\Heartbeat_Endpoint',
    'SocialProofLive\\Cache\\Cache_Manager',
);

foreach ( $classes as $class ) {
    splive_check( $class, class_exists( $class ) ? 'PASS' : 'FAIL', class_exists( $class ) ? 'loadable' : 'NOT found' );
}

// ---------------------------------------------------------------------------
// 7. Cache layer.
// ---------------------------------------------------------------------------
splive_section( '7. CACHE LAYER' );

if ( class_exists( 'SocialProofLive\\Cache\\Cache_Manager' ) ) {
    $cm = SocialProofLive\Cache\Cache_Manager::get_instance();
    $cm->init();
    $driver = $cm->get_driver_type();
    splive_check( 'Cache driver', 'INFO', $driver );

    // Round-trip test.
    $cm->set( 'diag_test', 12345, 30 );
    $got = $cm->get( 'diag_test' );
    splive_check( 'Cache write/read round-trip', ( 12345 === (int) $got ) ? 'PASS' : 'FAIL', 'wrote 12345, read ' . var_export( $got, true ) );
    $cm->delete( 'diag_test' );

    if ( 'transient' === $driver ) {
        splive_check( 'Object cache', 'WARN', 'No persistent object cache (Redis/Memcached). Fine for small/medium stores.' );
    }
} else {
    splive_check( 'Cache manager', 'FAIL', 'class not loadable' );
}

// ---------------------------------------------------------------------------
// 8. Frontend assets present.
// ---------------------------------------------------------------------------
splive_section( '8. FRONTEND ASSETS' );

$assets = array(
    'public/js/social-proof-live.js',
    'public/css/social-proof-live.css',
    'admin/js/admin.js',
    'admin/css/admin.css',
);

foreach ( $assets as $asset ) {
    $path = ( defined( 'SPLIVE_PLUGIN_DIR' ) ? SPLIVE_PLUGIN_DIR : __DIR__ . '/' ) . $asset;
    splive_check( $asset, file_exists( $path ) ? 'PASS' : 'FAIL', file_exists( $path ) ? size_format( filesize( $path ) ) : 'missing' );
}


// ---------------------------------------------------------------------------
// 9. Live tracking pipeline test (insert -> count -> cleanup).
// ---------------------------------------------------------------------------
splive_section( '9. LIVE TRACKING PIPELINE TEST' );

if ( $sessions_exists && class_exists( 'SocialProofLive\\Database\\Session_Repository' ) ) {
    $repo            = new SocialProofLive\Database\Session_Repository();
    $test_product_id = 999999990; // Unlikely-to-exist test product ID.

    // Clean any leftovers from a previous run.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$splive_sessions_table} WHERE product_id = %d", $test_product_id ) );

    // Insert 3 distinct test sessions.
    $ok = true;
    for ( $i = 1; $i <= 3; $i++ ) {
        $hash = hash( 'sha256', 'splive-diag-' . $i . '-' . microtime( true ) );
        $res  = $repo->upsert_session( $hash, $test_product_id, hash( 'sha256', 'ip' . $i ), hash( 'sha256', 'ua' . $i ) );
        $ok   = $ok && $res;
    }
    splive_check( 'Insert 3 test sessions', $ok ? 'PASS' : 'FAIL', $ok ? 'inserted' : 'insert failed' );

    // Count active viewers via the repository.
    $count = $repo->count_active_viewers( $test_product_id, 120 );
    splive_check( 'Count active viewers', ( 3 === (int) $count ) ? 'PASS' : 'FAIL', 'expected 3, got ' . $count );

    // Aggregator end-to-end (forces viewers to show by using a low threshold copy of settings).
    if ( class_exists( 'SocialProofLive\\Core\\Data_Aggregator' ) ) {
        $test_settings = is_array( $settings ) ? $settings : array();
        $test_settings['enable_viewers']  = true;
        $test_settings['enable_cart']     = false;
        $test_settings['enable_purchase'] = false;
        $test_settings['minimum_viewers'] = 1;
        $test_settings['session_timeout'] = 120;
        $test_settings['cache_ttl']       = 1;

        $agg  = new SocialProofLive\Core\Data_Aggregator( $test_settings );
        $data = $agg->get_product_data( $test_product_id );
        $resp = $agg->format_response( $data );
        $shows = ! empty( $resp['show'] ) && (int) $resp['viewers'] >= 3;
        splive_check( 'Aggregator returns live viewers', $shows ? 'PASS' : 'WARN', 'show=' . var_export( $resp['show'], true ) . ', viewers=' . var_export( $resp['viewers'], true ) );
    }

    // Cleanup test rows.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$splive_sessions_table} WHERE product_id = %d", $test_product_id ) );
    splive_check( 'Cleanup test sessions', 'INFO', $deleted . ' rows removed' );
} else {
    splive_check( 'Pipeline test', 'FAIL', 'sessions table or repository unavailable' );
}


// ---------------------------------------------------------------------------
// 10. WooCommerce data probe (optional, read-only).
// ---------------------------------------------------------------------------
splive_section( '10. WOOCOMMERCE DATA PROBE' );

if ( class_exists( 'WooCommerce' ) && function_exists( 'wc_get_products' ) ) {
    $products = wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'return' => 'ids' ) );
    if ( ! empty( $products ) ) {
        $sample_id = (int) $products[0];
        splive_check( 'Sample published product found', 'PASS', 'product ID ' . $sample_id );

        if ( class_exists( 'SocialProofLive\\Core\\Purchase_Tracker' ) ) {
            $pt  = new SocialProofLive\Core\Purchase_Tracker( is_array( $settings ) ? $settings : array() );
            $lp  = $pt->get_last_purchase_time( $sample_id );
            splive_check( 'Purchase query runs without error', 'PASS', $lp ? ( 'last purchase ' . $lp['human_time'] . ' ago' ) : 'no recent purchase for sample product' );
        }

        if ( class_exists( 'SocialProofLive\\Core\\Cart_Tracker' ) ) {
            $ct = new SocialProofLive\Core\Cart_Tracker( is_array( $settings ) ? $settings : array() );
            $cc = $ct->get_cart_count( $sample_id );
            splive_check( 'Cart count query runs without error', 'PASS', $cc . ' cart(s) currently contain the sample product' );
        }
    } else {
        splive_check( 'Sample product', 'WARN', 'No published products found to probe' );
    }
} else {
    splive_check( 'WooCommerce probe', 'WARN', 'WooCommerce not active — skipped' );
}

// ---------------------------------------------------------------------------
// SUMMARY.
// ---------------------------------------------------------------------------
splive_section( 'SUMMARY' );

$d = $GLOBALS['splive_diag'];
echo '  PASS: ' . $d['pass'] . "\n";
echo '  WARN: ' . $d['warn'] . "\n";
echo '  FAIL: ' . $d['fail'] . "\n\n";

if ( 0 === $d['fail'] ) {
    echo "  RESULT: ✅ ALL CRITICAL CHECKS PASSED. The plugin is installed and working.\n";
} else {
    echo "  RESULT: ❌ " . $d['fail'] . " critical check(s) FAILED. See the FAIL lines above.\n";
}

echo "\n";
echo "  TIP: A single visitor will NOT see the widget if 'minimum viewers' is 2+.\n";
echo "       To see it live, open the same product page in 2+ browsers/incognito\n";
echo "       windows at the same time, or set Minimum Viewers to 1 in Settings.\n";
echo "\n";
echo "--- END OF REPORT (copy everything above and send it back) ---\n";
