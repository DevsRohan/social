<?php
/**
 * Admin Settings Template
 *
 * @package SocialProofLive
 */

defined( 'ABSPATH' ) || exit;

$settings = get_option( 'splive_settings', array() );
$defaults = \SocialProofLive\Plugin::get_default_settings();
$settings = wp_parse_args( $settings, $defaults );
?>
<div class="splive-admin-wrap">

    <div class="splive-header">
        <div class="splive-header-left">
            <h1 class="splive-header-title"><?php esc_html_e( 'Settings', 'social-proof-live' ); ?></h1>
        </div>
        <div class="splive-header-right">
            <button class="splive-btn splive-btn-secondary splive-reset-btn"><?php esc_html_e( 'Reset Defaults', 'social-proof-live' ); ?></button>
            <button class="splive-btn splive-btn-primary splive-save-btn"><?php esc_html_e( 'Save Changes', 'social-proof-live' ); ?></button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="splive-tabs">
        <button class="splive-tab-btn active" data-tab="display"><?php esc_html_e( 'Display', 'social-proof-live' ); ?></button>
        <button class="splive-tab-btn" data-tab="appearance"><?php esc_html_e( 'Appearance', 'social-proof-live' ); ?></button>
        <button class="splive-tab-btn" data-tab="text"><?php esc_html_e( 'Text & Labels', 'social-proof-live' ); ?></button>
        <button class="splive-tab-btn" data-tab="behavior"><?php esc_html_e( 'Behavior', 'social-proof-live' ); ?></button>
        <button class="splive-tab-btn" data-tab="advanced"><?php esc_html_e( 'Advanced', 'social-proof-live' ); ?></button>
    </div>

    <form class="splive-settings-form">


        <!-- Display Tab -->
        <div class="splive-tab-panel active" data-panel="display">
            <div class="splive-card">
                <h3 class="splive-card-title splive-mb-24"><?php esc_html_e( 'Enabled Widgets', 'social-proof-live' ); ?></h3>

                <div class="splive-field">
                    <label class="splive-toggle">
                        <input type="checkbox" class="splive-toggle-input" name="enable_viewers" <?php checked( $settings['enable_viewers'] ); ?>>
                        <span class="splive-toggle-track"></span>
                        <span class="splive-toggle-label">🔥 <?php esc_html_e( 'Live Viewer Count', 'social-proof-live' ); ?></span>
                    </label>
                    <p class="splive-field-help"><?php esc_html_e( 'Shows "X people are viewing this right now"', 'social-proof-live' ); ?></p>
                </div>

                <div class="splive-field">
                    <label class="splive-toggle">
                        <input type="checkbox" class="splive-toggle-input" name="enable_cart" <?php checked( $settings['enable_cart'] ); ?>>
                        <span class="splive-toggle-track"></span>
                        <span class="splive-toggle-label">⏰ <?php esc_html_e( 'Cart Activity Count', 'social-proof-live' ); ?></span>
                    </label>
                    <p class="splive-field-help"><?php esc_html_e( 'Shows "X people have this in their cart"', 'social-proof-live' ); ?></p>
                </div>

                <div class="splive-field">
                    <label class="splive-toggle">
                        <input type="checkbox" class="splive-toggle-input" name="enable_purchase" <?php checked( $settings['enable_purchase'] ); ?>>
                        <span class="splive-toggle-track"></span>
                        <span class="splive-toggle-label">✓ <?php esc_html_e( 'Recent Purchase Time', 'social-proof-live' ); ?></span>
                    </label>
                    <p class="splive-field-help"><?php esc_html_e( 'Shows "Last purchased X minutes ago"', 'social-proof-live' ); ?></p>
                </div>
            </div>

            <div class="splive-card">
                <h3 class="splive-card-title splive-mb-24"><?php esc_html_e( 'Widget Position', 'social-proof-live' ); ?></h3>

                <div class="splive-field">
                    <select name="widget_position" class="splive-input splive-select">
                        <option value="after_add_to_cart" <?php selected( $settings['widget_position'], 'after_add_to_cart' ); ?>><?php esc_html_e( 'After Add to Cart button', 'social-proof-live' ); ?></option>
                        <option value="before_add_to_cart" <?php selected( $settings['widget_position'], 'before_add_to_cart' ); ?>><?php esc_html_e( 'Before Add to Cart button', 'social-proof-live' ); ?></option>
                        <option value="after_price" <?php selected( $settings['widget_position'], 'after_price' ); ?>><?php esc_html_e( 'After Price', 'social-proof-live' ); ?></option>
                        <option value="after_summary" <?php selected( $settings['widget_position'], 'after_summary' ); ?>><?php esc_html_e( 'After Product Summary', 'social-proof-live' ); ?></option>
                        <option value="shortcode" <?php selected( $settings['widget_position'], 'shortcode' ); ?>><?php esc_html_e( 'Shortcode Only (manual placement)', 'social-proof-live' ); ?></option>
                    </select>
                    <p class="splive-field-help"><?php esc_html_e( 'Where the widget appears on product pages. Use [social_proof_live] shortcode for custom placement.', 'social-proof-live' ); ?></p>
                </div>
            </div>

            <div class="splive-card">
                <h3 class="splive-card-title splive-mb-24"><?php esc_html_e( 'Thresholds', 'social-proof-live' ); ?></h3>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Minimum Viewers to Display', 'social-proof-live' ); ?></label>
                    <input type="number" name="minimum_viewers" class="splive-input" value="<?php echo esc_attr( $settings['minimum_viewers'] ); ?>" min="1" max="100" style="max-width:120px;">
                    <p class="splive-field-help"><?php esc_html_e( "Widget won't show until at least this many people are viewing.", 'social-proof-live' ); ?></p>
                </div>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Minimum Cart Count to Display', 'social-proof-live' ); ?></label>
                    <input type="number" name="minimum_cart" class="splive-input" value="<?php echo esc_attr( $settings['minimum_cart'] ); ?>" min="0" max="100" style="max-width:120px;">
                    <p class="splive-field-help"><?php esc_html_e( "Cart count line won't show unless this many carts contain the product.", 'social-proof-live' ); ?></p>
                </div>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Display Delay (ms)', 'social-proof-live' ); ?></label>
                    <input type="number" name="display_delay" class="splive-input" value="<?php echo esc_attr( $settings['display_delay'] ); ?>" min="0" max="5000" step="100" style="max-width:120px;">
                    <p class="splive-field-help"><?php esc_html_e( 'Milliseconds to wait after page load before showing widget. 1500 = 1.5 seconds.', 'social-proof-live' ); ?></p>
                </div>
            </div>
        </div>


        <!-- Appearance Tab -->
        <div class="splive-tab-panel" data-panel="appearance">
            <div class="splive-card">
                <h3 class="splive-card-title splive-mb-24"><?php esc_html_e( 'Theme', 'social-proof-live' ); ?></h3>

                <div class="splive-field">
                    <select name="theme" class="splive-input splive-select">
                        <option value="default" <?php selected( $settings['theme'], 'default' ); ?>><?php esc_html_e( 'Default (Clean Card)', 'social-proof-live' ); ?></option>
                        <option value="minimal" <?php selected( $settings['theme'], 'minimal' ); ?>><?php esc_html_e( 'Minimal (Inline Text)', 'social-proof-live' ); ?></option>
                        <option value="bold" <?php selected( $settings['theme'], 'bold' ); ?>><?php esc_html_e( 'Bold (High Urgency)', 'social-proof-live' ); ?></option>
                        <option value="glass" <?php selected( $settings['theme'], 'glass' ); ?>><?php esc_html_e( 'Glass (Frosted Effect)', 'social-proof-live' ); ?></option>
                    </select>
                </div>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Color Scheme', 'social-proof-live' ); ?></label>
                    <select name="color_scheme" class="splive-input splive-select">
                        <option value="auto" <?php selected( $settings['color_scheme'], 'auto' ); ?>><?php esc_html_e( 'Auto (follows system)', 'social-proof-live' ); ?></option>
                        <option value="light" <?php selected( $settings['color_scheme'], 'light' ); ?>><?php esc_html_e( 'Light', 'social-proof-live' ); ?></option>
                        <option value="dark" <?php selected( $settings['color_scheme'], 'dark' ); ?>><?php esc_html_e( 'Dark', 'social-proof-live' ); ?></option>
                    </select>
                </div>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Accent Color', 'social-proof-live' ); ?></label>
                    <input type="color" name="accent_color" class="splive-input" value="<?php echo esc_attr( $settings['accent_color'] ); ?>" style="max-width:80px;height:40px;padding:4px;">
                </div>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Animation Style', 'social-proof-live' ); ?></label>
                    <select name="animation_style" class="splive-input splive-select">
                        <option value="fade-slide" <?php selected( $settings['animation_style'], 'fade-slide' ); ?>><?php esc_html_e( 'Fade & Slide (recommended)', 'social-proof-live' ); ?></option>
                        <option value="fade" <?php selected( $settings['animation_style'], 'fade' ); ?>><?php esc_html_e( 'Fade', 'social-proof-live' ); ?></option>
                        <option value="slide-up" <?php selected( $settings['animation_style'], 'slide-up' ); ?>><?php esc_html_e( 'Slide Up', 'social-proof-live' ); ?></option>
                        <option value="bounce" <?php selected( $settings['animation_style'], 'bounce' ); ?>><?php esc_html_e( 'Bounce', 'social-proof-live' ); ?></option>
                        <option value="scale" <?php selected( $settings['animation_style'], 'scale' ); ?>><?php esc_html_e( 'Scale', 'social-proof-live' ); ?></option>
                        <option value="none" <?php selected( $settings['animation_style'], 'none' ); ?>><?php esc_html_e( 'None', 'social-proof-live' ); ?></option>
                    </select>
                </div>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Border Radius', 'social-proof-live' ); ?></label>
                    <input type="range" name="border_radius" class="splive-range" value="<?php echo esc_attr( $settings['border_radius'] ); ?>" min="0" max="20" step="1">
                    <span class="splive-range-value"><?php echo esc_html( $settings['border_radius'] ); ?>px</span>
                </div>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Icon Style', 'social-proof-live' ); ?></label>
                    <select name="icon_style" class="splive-input splive-select">
                        <option value="emoji" <?php selected( $settings['icon_style'], 'emoji' ); ?>><?php esc_html_e( 'Emoji (🔥 ⏰ ✓)', 'social-proof-live' ); ?></option>
                        <option value="none" <?php selected( $settings['icon_style'], 'none' ); ?>><?php esc_html_e( 'No Icons', 'social-proof-live' ); ?></option>
                    </select>
                </div>
            </div>
        </div>


        <!-- Text & Labels Tab -->
        <div class="splive-tab-panel" data-panel="text">
            <div class="splive-card">
                <h3 class="splive-card-title splive-mb-24"><?php esc_html_e( 'Customize Messages', 'social-proof-live' ); ?></h3>
                <p class="splive-field-help splive-mb-24"><?php esc_html_e( 'Use {count} for the number and {time} for the time period.', 'social-proof-live' ); ?></p>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Viewers Text (plural)', 'social-proof-live' ); ?></label>
                    <input type="text" name="text_viewers" class="splive-input" value="<?php echo esc_attr( $settings['text_viewers'] ); ?>" style="max-width:100%;">
                </div>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Viewers Text (singular)', 'social-proof-live' ); ?></label>
                    <input type="text" name="text_viewers_singular" class="splive-input" value="<?php echo esc_attr( $settings['text_viewers_singular'] ); ?>" style="max-width:100%;">
                </div>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Cart Text (plural)', 'social-proof-live' ); ?></label>
                    <input type="text" name="text_cart" class="splive-input" value="<?php echo esc_attr( $settings['text_cart'] ); ?>" style="max-width:100%;">
                </div>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Cart Text (singular)', 'social-proof-live' ); ?></label>
                    <input type="text" name="text_cart_singular" class="splive-input" value="<?php echo esc_attr( $settings['text_cart_singular'] ); ?>" style="max-width:100%;">
                </div>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Purchase Text', 'social-proof-live' ); ?></label>
                    <input type="text" name="text_purchase" class="splive-input" value="<?php echo esc_attr( $settings['text_purchase'] ); ?>" style="max-width:100%;">
                </div>
            </div>

            <div class="splive-card">
                <h3 class="splive-card-title splive-mb-24"><?php esc_html_e( 'Custom Icons', 'social-proof-live' ); ?></h3>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Viewers Icon', 'social-proof-live' ); ?></label>
                    <input type="text" name="icon_viewers" class="splive-input" value="<?php echo esc_attr( $settings['icon_viewers'] ); ?>" style="max-width:80px;">
                </div>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Cart Icon', 'social-proof-live' ); ?></label>
                    <input type="text" name="icon_cart" class="splive-input" value="<?php echo esc_attr( $settings['icon_cart'] ); ?>" style="max-width:80px;">
                </div>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Purchase Icon', 'social-proof-live' ); ?></label>
                    <input type="text" name="icon_purchase" class="splive-input" value="<?php echo esc_attr( $settings['icon_purchase'] ); ?>" style="max-width:80px;">
                </div>
            </div>
        </div>


        <!-- Behavior Tab -->
        <div class="splive-tab-panel" data-panel="behavior">
            <div class="splive-card">
                <h3 class="splive-card-title splive-mb-24"><?php esc_html_e( 'Tracking Behavior', 'social-proof-live' ); ?></h3>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Heartbeat Interval (seconds)', 'social-proof-live' ); ?></label>
                    <select name="heartbeat_interval" class="splive-input splive-select">
                        <option value="15" <?php selected( $settings['heartbeat_interval'], 15 ); ?>>15s</option>
                        <option value="20" <?php selected( $settings['heartbeat_interval'], 20 ); ?>>20s (<?php esc_html_e( 'recommended', 'social-proof-live' ); ?>)</option>
                        <option value="30" <?php selected( $settings['heartbeat_interval'], 30 ); ?>>30s</option>
                        <option value="45" <?php selected( $settings['heartbeat_interval'], 45 ); ?>>45s</option>
                        <option value="60" <?php selected( $settings['heartbeat_interval'], 60 ); ?>>60s</option>
                    </select>
                    <p class="splive-field-help"><?php esc_html_e( 'How often the widget checks for updates. Lower = more real-time but more server requests.', 'social-proof-live' ); ?></p>
                </div>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Session Timeout (seconds)', 'social-proof-live' ); ?></label>
                    <input type="number" name="session_timeout" class="splive-input" value="<?php echo esc_attr( $settings['session_timeout'] ); ?>" min="30" max="600" style="max-width:120px;">
                    <p class="splive-field-help"><?php esc_html_e( 'A visitor is counted as "viewing" until this many seconds after their last heartbeat.', 'social-proof-live' ); ?></p>
                </div>

                <div class="splive-field">
                    <label class="splive-toggle">
                        <input type="checkbox" class="splive-toggle-input" name="count_bots" <?php checked( $settings['count_bots'] ); ?>>
                        <span class="splive-toggle-track"></span>
                        <span class="splive-toggle-label"><?php esc_html_e( 'Count bots/crawlers', 'social-proof-live' ); ?></span>
                    </label>
                    <p class="splive-field-help"><?php esc_html_e( 'By default, known bots are excluded from viewer counts.', 'social-proof-live' ); ?></p>
                </div>

                <div class="splive-field">
                    <label class="splive-toggle">
                        <input type="checkbox" class="splive-toggle-input" name="disable_on_mobile" <?php checked( $settings['disable_on_mobile'] ); ?>>
                        <span class="splive-toggle-track"></span>
                        <span class="splive-toggle-label"><?php esc_html_e( 'Disable on mobile devices', 'social-proof-live' ); ?></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Advanced Tab -->
        <div class="splive-tab-panel" data-panel="advanced">
            <div class="splive-card">
                <h3 class="splive-card-title splive-mb-24"><?php esc_html_e( 'Performance', 'social-proof-live' ); ?></h3>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Cache TTL (seconds)', 'social-proof-live' ); ?></label>
                    <input type="number" name="cache_ttl" class="splive-input" value="<?php echo esc_attr( $settings['cache_ttl'] ); ?>" min="1" max="60" style="max-width:120px;">
                    <p class="splive-field-help"><?php esc_html_e( 'How long to cache live counts. Higher = less DB load, slightly less real-time.', 'social-proof-live' ); ?></p>
                </div>

                <div class="splive-field">
                    <label class="splive-field-label"><?php esc_html_e( 'Stats Retention (days)', 'social-proof-live' ); ?></label>
                    <input type="number" name="stats_retention" class="splive-input" value="<?php echo esc_attr( $settings['stats_retention'] ); ?>" min="7" max="365" style="max-width:120px;">
                    <p class="splive-field-help"><?php esc_html_e( 'Historical analytics data older than this is automatically purged.', 'social-proof-live' ); ?></p>
                </div>

                <div class="splive-field">
                    <label class="splive-toggle">
                        <input type="checkbox" class="splive-toggle-input" name="debug_mode" <?php checked( $settings['debug_mode'] ); ?>>
                        <span class="splive-toggle-track"></span>
                        <span class="splive-toggle-label"><?php esc_html_e( 'Debug Mode', 'social-proof-live' ); ?></span>
                    </label>
                    <p class="splive-field-help"><?php esc_html_e( 'Enables detailed logging. Requires WP_DEBUG to be true.', 'social-proof-live' ); ?></p>
                </div>
            </div>
        </div>

    </form>
</div>
