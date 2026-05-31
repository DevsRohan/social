<?php
/**
 * Admin Analytics Template
 *
 * @package SocialProofLive
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="splive-admin-wrap">

    <div class="splive-header">
        <div class="splive-header-left">
            <h1 class="splive-header-title"><?php esc_html_e( 'Analytics', 'social-proof-live' ); ?></h1>
        </div>
        <div class="splive-header-right">
            <div class="splive-date-range">
                <button class="splive-date-btn active" data-days="7"><?php esc_html_e( '7 Days', 'social-proof-live' ); ?></button>
                <button class="splive-date-btn" data-days="14"><?php esc_html_e( '14 Days', 'social-proof-live' ); ?></button>
                <button class="splive-date-btn" data-days="30"><?php esc_html_e( '30 Days', 'social-proof-live' ); ?></button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="splive-stats-grid">
        <div class="splive-stat-card">
            <div class="splive-stat-icon">🏔️</div>
            <div class="splive-stat-value splive-analytics-peak">—</div>
            <div class="splive-stat-label"><?php esc_html_e( 'Peak Concurrent Viewers', 'social-proof-live' ); ?></div>
        </div>
        <div class="splive-stat-card">
            <div class="splive-stat-icon">👥</div>
            <div class="splive-stat-value splive-analytics-sessions">—</div>
            <div class="splive-stat-label"><?php esc_html_e( 'Total Sessions', 'social-proof-live' ); ?></div>
        </div>
        <div class="splive-stat-card">
            <div class="splive-stat-icon">🛒</div>
            <div class="splive-stat-value splive-analytics-purchases">—</div>
            <div class="splive-stat-label"><?php esc_html_e( 'Purchases Tracked', 'social-proof-live' ); ?></div>
        </div>
        <div class="splive-stat-card">
            <div class="splive-stat-icon">🎯</div>
            <div class="splive-stat-value splive-analytics-conversion">—</div>
            <div class="splive-stat-label"><?php esc_html_e( 'Conversion Rate', 'social-proof-live' ); ?></div>
        </div>
    </div>

    <!-- Chart -->
    <div class="splive-card">
        <div class="splive-card-header">
            <h2 class="splive-card-title"><?php esc_html_e( 'Viewer Trends', 'social-proof-live' ); ?></h2>
        </div>
        <div class="splive-chart-container splive-analytics-chart"></div>
    </div>

    <!-- Info -->
    <div class="splive-card">
        <div class="splive-card-header">
            <h2 class="splive-card-title"><?php esc_html_e( 'About Analytics', 'social-proof-live' ); ?></h2>
        </div>
        <p style="color:var(--splive-text-secondary);font-size:13px;margin:0;">
            <?php esc_html_e( 'Analytics data is aggregated hourly. Real-time counts are available on the Dashboard. Data retention can be configured in Settings → Advanced.', 'social-proof-live' ); ?>
        </p>
    </div>

</div>
