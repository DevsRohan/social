<?php
/**
 * Trial mode — 24-hour self-expiring build.
 *
 * Only active when the main plugin file defines:  define( 'SPLIVE_TRIAL', true );
 * In that build the plugin runs fully (every feature) for 24 hours, shows a
 * live ticking countdown bar at the top of the WordPress admin, and then
 * deactivates and deletes itself automatically.
 *
 * @package SocialProofLive
 */

namespace SocialProofLive;

defined( 'ABSPATH' ) || exit;

/**
 * Class Trial
 */
class Trial {

    const OPTION_START = 'splive_trial_started';
    const DURATION     = 86400; // 24 hours in seconds.
    const UPGRADE_URL  = 'https://devsarun.io/plugin/chat/';
    const AUTHOR_URL   = 'https://devsarun.io/';

    /**
     * Initialize trial enforcement.
     *
     * @return bool True to continue loading; false if expired (and handled).
     */
    public function init() {
        if ( ! defined( 'SPLIVE_TRIAL' ) || ! SPLIVE_TRIAL ) {
            return true; // Not a trial build — full plugin, no restrictions.
        }

        $start = (int) get_option( self::OPTION_START, 0 );
        if ( ! $start ) {
            $start = time();
            update_option( self::OPTION_START, $start );
        }

        if ( $this->remaining( $start ) <= 0 ) {
            $this->self_destruct();
            return false;
        }

        // Still in trial — show the live countdown bar at the very top of admin.
        add_action( 'all_admin_notices', array( $this, 'render_timer_bar' ) );
        return true;
    }

    /**
     * Seconds remaining in the trial.
     *
     * @param int $start Start timestamp.
     * @return int
     */
    private function remaining( $start ) {
        return ( $start + self::DURATION ) - time();
    }

    /**
     * Render the live ticking 24-hour countdown bar (admin, top).
     *
     * @return void
     */
    public function render_timer_bar() {
        $start     = (int) get_option( self::OPTION_START, time() );
        $remaining = max( 0, $this->remaining( $start ) );
        ?>
        <div id="splive-trial-bar" style="margin:10px 20px 10px 2px;border-radius:14px;overflow:hidden;background:linear-gradient(110deg,#0B1120,#1e293b);box-shadow:0 8px 30px -10px rgba(0,0,0,.4);">
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:14px 18px;">
                <span style="font-size:22px;line-height:1;">🔥</span>
                <div style="flex:1;min-width:200px;">
                    <div style="color:#fff;font-weight:700;font-size:14px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
                        Social Proof LIVE — <span style="color:#ff8a5c;">Free Trial</span>
                    </div>
                    <div style="color:#cbd5e1;font-size:12px;margin-top:2px;">
                        Every feature unlocked. This trial removes itself automatically when the timer hits zero.
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:.05em;">Time left</span>
                    <span id="splive-trial-clock" data-remaining="<?php echo esc_attr( $remaining ); ?>"
                        style="font-variant-numeric:tabular-nums;font-feature-settings:'tnum';background:rgba(255,255,255,.08);color:#fff;font-weight:700;font-size:18px;padding:6px 12px;border-radius:10px;border:1px solid rgba(255,255,255,.12);letter-spacing:.04em;">
                        --:--:--
                    </span>
                </div>
                <a href="<?php echo esc_url( self::UPGRADE_URL ); ?>" target="_blank" rel="noopener"
                   style="background:linear-gradient(135deg,#ff8a5c,#ec3909);color:#fff;font-weight:700;font-size:13px;text-decoration:none;padding:9px 18px;border-radius:10px;box-shadow:0 6px 18px -6px rgba(236,57,9,.7);white-space:nowrap;">
                    Upgrade to Pro →
                </a>
            </div>
            <div style="height:4px;background:rgba(255,255,255,.08);">
                <div id="splive-trial-progress" style="height:100%;width:0;background:linear-gradient(90deg,#ff8a5c,#ec3909);transition:width 1s linear;"></div>
            </div>
        </div>
        <script>
        (function () {
            var clock = document.getElementById('splive-trial-clock');
            var bar = document.getElementById('splive-trial-progress');
            if (!clock) { return; }
            var total = <?php echo (int) self::DURATION; ?>;
            var remaining = parseInt(clock.getAttribute('data-remaining'), 10) || 0;
            function pad(n) { return n < 10 ? '0' + n : '' + n; }
            function render() {
                if (remaining <= 0) {
                    clock.textContent = '00:00:00';
                    if (bar) { bar.style.width = '100%'; }
                    setTimeout(function () { window.location.reload(); }, 800);
                    return;
                }
                var h = Math.floor(remaining / 3600);
                var m = Math.floor((remaining % 3600) / 60);
                var s = remaining % 60;
                clock.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
                if (bar) { bar.style.width = (100 - (remaining / total) * 100).toFixed(3) + '%'; }
                remaining--;
            }
            render();
            setInterval(render, 1000);
        })();
        </script>
        <?php
    }

    /**
     * Deactivate and delete the plugin once the trial ends.
     *
     * @return void
     */
    private function self_destruct() {
        if ( ! function_exists( 'deactivate_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Stop the plugin running on the next request.
        deactivate_plugins( SPLIVE_PLUGIN_BASENAME );

        // Leave a flag so the admin sees why it vanished.
        set_transient( 'splive_trial_expired', 1, DAY_IN_SECONDS );

        // Only attempt file deletion inside the admin (filesystem APIs available).
        if ( is_admin() ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            if ( function_exists( 'WP_Filesystem' ) ) {
                WP_Filesystem();
            }

            if ( function_exists( 'delete_plugins' ) ) {
                // This also runs uninstall.php, removing tables, options & cron.
                delete_plugins( array( SPLIVE_PLUGIN_BASENAME ) );
            }
        }

        add_action( 'all_admin_notices', array( $this, 'render_expired_notice' ) );
    }

    /**
     * Render the "trial expired" notice.
     *
     * @return void
     */
    public function render_expired_notice() {
        echo '<div class="notice notice-error" style="border-left-color:#ec3909;"><p style="font-size:14px;">';
        echo '⏳ <strong>' . esc_html__( 'Your Social Proof LIVE trial has ended', 'social-proof-live' ) . '</strong> — ';
        echo esc_html__( 'the plugin has removed itself.', 'social-proof-live' );
        echo ' <a href="' . esc_url( self::UPGRADE_URL ) . '" target="_blank" rel="noopener"><strong>' . esc_html__( 'Get the Pro version →', 'social-proof-live' ) . '</strong></a>';
        echo '</p></div>';
    }
}
