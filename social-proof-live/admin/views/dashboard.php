<?php
/**
 * Admin Dashboard Template
 *
 * @package SocialProofLive
 */

defined( 'ABSPATH' ) || exit;

$show_onboarding = get_option( 'splive_show_onboarding', false );
?>
<div class="splive-admin-wrap">

    <!-- Header -->
    <div class="splive-header">
        <div class="splive-header-left">
            <h1 class="splive-header-title">Social Proof LIVE</h1>
            <span class="splive-header-badge">Active</span>
        </div>
        <div class="splive-header-right">
            <span class="splive-header-version">v<?php echo esc_html( SPLIVE_VERSION ); ?></span>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="splive-stats-grid">
        <div class="splive-stat-card">
            <div class="splive-stat-icon">👁️</div>
            <div class="splive-stat-value splive-stat-active-viewers">—</div>
            <div class="splive-stat-label"><?php esc_html_e( 'Active Viewers Now', 'social-proof-live' ); ?></div>
        </div>
        <div class="splive-stat-card">
            <div class="splive-stat-icon">📊</div>
            <div class="splive-stat-value splive-stat-today-sessions">—</div>
            <div class="splive-stat-label"><?php esc_html_e( "Today's Sessions", 'social-proof-live' ); ?></div>
        </div>
        <div class="splive-stat-card">
            <div class="splive-stat-icon">📈</div>
            <div class="splive-stat-value splive-stat-avg-concurrent">—</div>
            <div class="splive-stat-label"><?php esc_html_e( 'Avg. Concurrent', 'social-proof-live' ); ?></div>
        </div>
    </div>

    <!-- Activity Chart -->
    <div class="splive-card">
        <div class="splive-card-header">
            <h2 class="splive-card-title"><?php esc_html_e( 'Live Activity (24 Hours)', 'social-proof-live' ); ?></h2>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=social-proof-live-analytics' ) ); ?>" class="splive-card-action">
                <?php esc_html_e( 'View Analytics →', 'social-proof-live' ); ?>
            </a>
        </div>
        <div class="splive-chart-container"></div>
    </div>

    <!-- Top Products -->
    <div class="splive-card">
        <div class="splive-card-header">
            <h2 class="splive-card-title"><?php esc_html_e( 'Top Products (Live)', 'social-proof-live' ); ?></h2>
        </div>
        <table class="splive-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Product', 'social-proof-live' ); ?></th>
                    <th class="splive-center"><?php esc_html_e( 'Viewers', 'social-proof-live' ); ?></th>
                    <th class="splive-center"><?php esc_html_e( 'In Cart', 'social-proof-live' ); ?></th>
                    <th class="splive-center"><?php esc_html_e( 'Last Purchase', 'social-proof-live' ); ?></th>
                </tr>
            </thead>
            <tbody class="splive-top-products-body">
                <tr>
                    <td colspan="4" class="splive-empty-state">
                        <?php esc_html_e( 'Loading...', 'social-proof-live' ); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Onboarding Wizard Overlay -->
    <?php if ( $show_onboarding ) : ?>
    <div class="splive-onboarding">
        <div class="splive-wizard-card">
            <div class="splive-wizard-logo">🔥</div>
            <h2 class="splive-wizard-title"><?php esc_html_e( 'Welcome to Social Proof LIVE', 'social-proof-live' ); ?></h2>
            <p class="splive-wizard-subtitle"><?php esc_html_e( "Let's set up your live social proof in under 60 seconds.", 'social-proof-live' ); ?></p>

            <div class="splive-wizard-progress">
                <span class="splive-wizard-dot active current"></span>
                <span class="splive-wizard-dot"></span>
                <span class="splive-wizard-dot"></span>
            </div>

            <!-- Step 1: Choose Widgets -->
            <div class="splive-wizard-step active" data-step="1">
                <div class="splive-wizard-options">
                    <label class="splive-wizard-option selected">
                        <span class="splive-wizard-option-icon">🔥</span>
                        <span class="splive-wizard-option-text"><?php esc_html_e( 'Live Viewer Count', 'social-proof-live' ); ?></span>
                        <input type="checkbox" checked style="margin-left:auto;">
                    </label>
                    <label class="splive-wizard-option selected">
                        <span class="splive-wizard-option-icon">⏰</span>
                        <span class="splive-wizard-option-text"><?php esc_html_e( 'Cart Activity Count', 'social-proof-live' ); ?></span>
                        <input type="checkbox" checked style="margin-left:auto;">
                    </label>
                    <label class="splive-wizard-option selected">
                        <span class="splive-wizard-option-icon">✓</span>
                        <span class="splive-wizard-option-text"><?php esc_html_e( 'Recent Purchase Time', 'social-proof-live' ); ?></span>
                        <input type="checkbox" checked style="margin-left:auto;">
                    </label>
                </div>
                <button class="splive-btn splive-btn-primary splive-wizard-next"><?php esc_html_e( 'Continue →', 'social-proof-live' ); ?></button>
            </div>

            <!-- Step 2: Style -->
            <div class="splive-wizard-step" data-step="2">
                <div class="splive-wizard-options">
                    <label class="splive-wizard-option selected">
                        <span class="splive-wizard-option-icon">🎨</span>
                        <span class="splive-wizard-option-text"><?php esc_html_e( 'Default (Clean Card)', 'social-proof-live' ); ?></span>
                        <input type="radio" name="wizard_theme" value="default" checked style="margin-left:auto;">
                    </label>
                    <label class="splive-wizard-option">
                        <span class="splive-wizard-option-icon">✨</span>
                        <span class="splive-wizard-option-text"><?php esc_html_e( 'Glass (Frosted Effect)', 'social-proof-live' ); ?></span>
                        <input type="radio" name="wizard_theme" value="glass" style="margin-left:auto;">
                    </label>
                    <label class="splive-wizard-option">
                        <span class="splive-wizard-option-icon">⚡</span>
                        <span class="splive-wizard-option-text"><?php esc_html_e( 'Bold (High Urgency)', 'social-proof-live' ); ?></span>
                        <input type="radio" name="wizard_theme" value="bold" style="margin-left:auto;">
                    </label>
                    <label class="splive-wizard-option">
                        <span class="splive-wizard-option-icon">〰️</span>
                        <span class="splive-wizard-option-text"><?php esc_html_e( 'Minimal (Text Only)', 'social-proof-live' ); ?></span>
                        <input type="radio" name="wizard_theme" value="minimal" style="margin-left:auto;">
                    </label>
                </div>
                <div class="splive-flex splive-gap-8">
                    <button class="splive-btn splive-btn-secondary splive-wizard-back"><?php esc_html_e( '← Back', 'social-proof-live' ); ?></button>
                    <button class="splive-btn splive-btn-primary splive-wizard-next"><?php esc_html_e( 'Continue →', 'social-proof-live' ); ?></button>
                </div>
            </div>

            <!-- Step 3: Behavior -->
            <div class="splive-wizard-step" data-step="3">
                <div class="splive-wizard-options">
                    <div class="splive-field">
                        <label class="splive-field-label"><?php esc_html_e( 'Minimum viewers to show widget', 'social-proof-live' ); ?></label>
                        <input type="number" class="splive-input" value="2" min="1" max="20" style="max-width:120px;">
                        <p class="splive-field-help"><?php esc_html_e( "Widget stays hidden until this many people are viewing.", 'social-proof-live' ); ?></p>
                    </div>
                    <div class="splive-field">
                        <label class="splive-field-label"><?php esc_html_e( 'Update frequency', 'social-proof-live' ); ?></label>
                        <select class="splive-input splive-select" style="max-width:200px;">
                            <option value="15"><?php esc_html_e( 'Every 15 seconds', 'social-proof-live' ); ?></option>
                            <option value="20" selected><?php esc_html_e( 'Every 20 seconds (recommended)', 'social-proof-live' ); ?></option>
                            <option value="30"><?php esc_html_e( 'Every 30 seconds', 'social-proof-live' ); ?></option>
                            <option value="60"><?php esc_html_e( 'Every 60 seconds', 'social-proof-live' ); ?></option>
                        </select>
                    </div>
                </div>
                <div class="splive-flex splive-gap-8">
                    <button class="splive-btn splive-btn-secondary splive-wizard-back"><?php esc_html_e( '← Back', 'social-proof-live' ); ?></button>
                    <button class="splive-btn splive-btn-primary splive-wizard-finish">🚀 <?php esc_html_e( 'Finish Setup', 'social-proof-live' ); ?></button>
                </div>
            </div>

            <a href="#" class="splive-wizard-skip"><?php esc_html_e( 'Skip setup, use defaults', 'social-proof-live' ); ?></a>
        </div>
    </div>
    <?php endif; ?>

</div>
