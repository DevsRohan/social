/**
 * Social Proof LIVE — Admin JavaScript
 *
 * Dashboard, settings management, charts, and onboarding wizard.
 * Vanilla JS, no jQuery dependency.
 *
 * @package SocialProofLive
 */
(function () {
    'use strict';

    if (typeof spliveAdmin === 'undefined') {
        return;
    }

    var admin = spliveAdmin;
    var currentPage = '';

    /**
     * Initialize admin JS.
     */
    function init() {
        currentPage = detectPage();

        switch (currentPage) {
            case 'dashboard':
                initDashboard();
                break;
            case 'settings':
                initSettings();
                break;
            case 'analytics':
                initAnalytics();
                break;
        }

        // Show onboarding if needed.
        if (admin.isOnboarding) {
            initOnboarding();
        }

        // Toast notifications.
        initToasts();
    }

    /**
     * Detect which admin page we're on.
     */
    function detectPage() {
        var urlParams = new URLSearchParams(window.location.search);
        var page = urlParams.get('page') || '';

        if (page === 'social-proof-live') return 'dashboard';
        if (page === 'social-proof-live-settings') return 'settings';
        if (page === 'social-proof-live-analytics') return 'analytics';
        return 'unknown';
    }

    /* ==========================================================================
       DASHBOARD
       ========================================================================== */

    function initDashboard() {
        loadDashboardData();

        // Auto-refresh every 30 seconds.
        setInterval(loadDashboardData, 30000);
    }

    function loadDashboardData() {
        apiGet('admin/overview').then(function (data) {
            updateStatCards(data);
            updateTopProducts(data.top_products || []);
            updateActivityChart(data.hourly_data || []);
        });
    }

    function updateStatCards(data) {
        setElementText('.splive-stat-active-viewers', data.active_viewers || 0);
        setElementText('.splive-stat-today-sessions', formatNumber(data.today_sessions || 0));
        setElementText('.splive-stat-avg-concurrent', data.avg_concurrent || 0);
    }

    function updateTopProducts(products) {
        var container = document.querySelector('.splive-top-products-body');
        if (!container) return;

        if (!products.length) {
            container.innerHTML = '<tr><td colspan="4" class="splive-empty-state">No active viewers yet. Data will appear when visitors view your products.</td></tr>';
            return;
        }

        var html = '';
        products.forEach(function (product) {
            html += '<tr>';
            html += '<td class="splive-product-name">' + escapeHtml(product.product_name || 'Product #' + product.product_id) + '</td>';
            html += '<td class="splive-center"><span class="splive-badge splive-badge-viewers">' + product.viewer_count + '</span></td>';
            html += '<td class="splive-center">—</td>';
            html += '<td class="splive-center">—</td>';
            html += '</tr>';
        });

        container.innerHTML = html;
    }

    function updateActivityChart(hourlyData) {
        var container = document.querySelector('.splive-chart-container');
        if (!container) return;

        if (!hourlyData.length) {
            container.innerHTML = '<div class="splive-chart-empty">Chart data will appear after your first hour of tracking.</div>';
            return;
        }

        // Simple SVG line chart.
        var width = container.clientWidth || 600;
        var height = 200;
        var padding = 40;
        var chartWidth = width - padding * 2;
        var chartHeight = height - padding * 2;

        var maxViewers = Math.max.apply(null, hourlyData.map(function (d) { return parseInt(d.viewers || 0, 10); }));
        maxViewers = Math.max(maxViewers, 5); // Minimum scale.

        var points = hourlyData.map(function (d, i) {
            var x = padding + (i / Math.max(hourlyData.length - 1, 1)) * chartWidth;
            var y = padding + chartHeight - (parseInt(d.viewers || 0, 10) / maxViewers) * chartHeight;
            return x + ',' + y;
        });

        // Gradient fill points.
        var fillPoints = [padding + ',' + (padding + chartHeight)].concat(points).concat([(padding + chartWidth) + ',' + (padding + chartHeight)]);

        var svg = '<svg width="' + width + '" height="' + height + '" class="splive-svg-chart">';

        // Grid lines.
        for (var i = 0; i <= 4; i++) {
            var yPos = padding + (i / 4) * chartHeight;
            var label = Math.round(maxViewers * (1 - i / 4));
            svg += '<line x1="' + padding + '" y1="' + yPos + '" x2="' + (padding + chartWidth) + '" y2="' + yPos + '" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="4"/>';
            svg += '<text x="' + (padding - 8) + '" y="' + (yPos + 4) + '" font-size="10" fill="#94a3b8" text-anchor="end">' + label + '</text>';
        }

        // Hour labels.
        hourlyData.forEach(function (d, idx) {
            if (idx % 3 === 0) {
                var xPos = padding + (idx / Math.max(hourlyData.length - 1, 1)) * chartWidth;
                svg += '<text x="' + xPos + '" y="' + (height - 8) + '" font-size="10" fill="#94a3b8" text-anchor="middle">' + d.stat_hour + ':00</text>';
            }
        });

        // Fill area.
        svg += '<polygon points="' + fillPoints.join(' ') + '" fill="url(#spliveGradient)" opacity="0.3"/>';

        // Line.
        svg += '<polyline points="' + points.join(' ') + '" fill="none" stroke="var(--splive-accent, #FF6B35)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>';

        // Gradient definition.
        svg += '<defs><linearGradient id="spliveGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="var(--splive-accent, #FF6B35)" stop-opacity="0.4"/><stop offset="100%" stop-color="var(--splive-accent, #FF6B35)" stop-opacity="0.02"/></linearGradient></defs>';

        svg += '</svg>';

        container.innerHTML = svg;
    }

    /* ==========================================================================
       SETTINGS
       ========================================================================== */

    function initSettings() {
        // Tab navigation.
        var tabs = document.querySelectorAll('.splive-tab-btn');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                switchTab(this.getAttribute('data-tab'));
            });
        });

        // Toggle switches.
        var toggles = document.querySelectorAll('.splive-toggle-input');
        toggles.forEach(function (toggle) {
            toggle.addEventListener('change', markDirty);
        });

        // All inputs.
        var inputs = document.querySelectorAll('.splive-settings-form input, .splive-settings-form select');
        inputs.forEach(function (input) {
            input.addEventListener('change', markDirty);
        });

        // Save button.
        var saveBtn = document.querySelector('.splive-save-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', saveSettings);
        }

        // Reset button.
        var resetBtn = document.querySelector('.splive-reset-btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (confirm(admin.strings.confirm_reset)) {
                    resetSettings();
                }
            });
        }

        // Load first tab.
        var firstTab = document.querySelector('.splive-tab-btn.active');
        if (firstTab) {
            switchTab(firstTab.getAttribute('data-tab'));
        }
    }

    function switchTab(tabId) {
        // Update tab buttons.
        document.querySelectorAll('.splive-tab-btn').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
        });

        // Update tab panels.
        document.querySelectorAll('.splive-tab-panel').forEach(function (panel) {
            panel.classList.toggle('active', panel.getAttribute('data-panel') === tabId);
        });
    }

    var isDirty = false;

    function markDirty() {
        isDirty = true;
        var saveBtn = document.querySelector('.splive-save-btn');
        if (saveBtn) {
            saveBtn.classList.add('splive-btn-pulse');
        }
    }

    function saveSettings() {
        var saveBtn = document.querySelector('.splive-save-btn');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = admin.strings.saving;
        }

        var settings = collectSettings();

        apiPost('admin/settings', settings).then(function (data) {
            if (data.success) {
                showToast(admin.strings.saved, 'success');
                isDirty = false;
                if (saveBtn) {
                    saveBtn.classList.remove('splive-btn-pulse');
                }
            } else {
                showToast(admin.strings.error, 'error');
            }
        }).catch(function () {
            showToast(admin.strings.error, 'error');
        }).finally(function () {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Changes';
            }
        });
    }

    function collectSettings() {
        var settings = {};
        var form = document.querySelector('.splive-settings-form');
        if (!form) return admin.settings;

        // Toggles (booleans).
        form.querySelectorAll('.splive-toggle-input').forEach(function (el) {
            settings[el.name] = el.checked;
        });

        // Text inputs.
        form.querySelectorAll('input[type="text"], input[type="number"], input[type="color"]').forEach(function (el) {
            if (el.name) {
                settings[el.name] = el.type === 'number' ? parseInt(el.value, 10) : el.value;
            }
        });

        // Selects.
        form.querySelectorAll('select').forEach(function (el) {
            if (el.name) {
                settings[el.name] = el.value;
            }
        });

        // Range sliders.
        form.querySelectorAll('input[type="range"]').forEach(function (el) {
            if (el.name) {
                settings[el.name] = parseInt(el.value, 10);
            }
        });

        return settings;
    }

    function resetSettings() {
        apiPost('admin/settings', {}).then(function () {
            showToast('Settings reset to defaults.', 'success');
            setTimeout(function () { window.location.reload(); }, 1000);
        });
    }

    /* ==========================================================================
       ANALYTICS
       ========================================================================== */

    function initAnalytics() {
        loadAnalyticsData();

        // Date range buttons.
        document.querySelectorAll('.splive-date-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.splive-date-btn').forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
                var days = parseInt(this.getAttribute('data-days'), 10);
                loadAnalyticsData(days);
            });
        });
    }

    function loadAnalyticsData(days) {
        days = days || 7;
        var endDate = new Date().toISOString().split('T')[0];
        var startDate = new Date(Date.now() - days * 86400000).toISOString().split('T')[0];

        apiGet('admin/analytics?start_date=' + startDate + '&end_date=' + endDate).then(function (data) {
            updateAnalyticsSummary(data.summary || {});
            updateAnalyticsChart(data.hourly_data || []);
        });
    }

    function updateAnalyticsSummary(summary) {
        setElementText('.splive-analytics-peak', summary.peak_viewers || 0);
        setElementText('.splive-analytics-sessions', formatNumber(summary.total_sessions || 0));
        setElementText('.splive-analytics-purchases', summary.total_purchases || 0);
    }

    function updateAnalyticsChart(data) {
        var container = document.querySelector('.splive-analytics-chart');
        if (container) {
            updateActivityChart.call(null, data);
        }
    }

    /* ==========================================================================
       ONBOARDING WIZARD
       ========================================================================== */

    function initOnboarding() {
        var wizard = document.querySelector('.splive-onboarding');
        if (!wizard) return;

        wizard.style.display = 'flex';
        var currentStep = 1;

        // Next buttons.
        wizard.querySelectorAll('.splive-wizard-next').forEach(function (btn) {
            btn.addEventListener('click', function () {
                currentStep++;
                showWizardStep(wizard, currentStep);
            });
        });

        // Back buttons.
        wizard.querySelectorAll('.splive-wizard-back').forEach(function (btn) {
            btn.addEventListener('click', function () {
                currentStep--;
                showWizardStep(wizard, currentStep);
            });
        });

        // Finish button.
        var finishBtn = wizard.querySelector('.splive-wizard-finish');
        if (finishBtn) {
            finishBtn.addEventListener('click', function () {
                completeOnboarding(wizard);
            });
        }

        // Skip link.
        var skipLink = wizard.querySelector('.splive-wizard-skip');
        if (skipLink) {
            skipLink.addEventListener('click', function (e) {
                e.preventDefault();
                completeOnboarding(wizard);
            });
        }
    }

    function showWizardStep(wizard, step) {
        wizard.querySelectorAll('.splive-wizard-step').forEach(function (s) {
            s.classList.toggle('active', parseInt(s.getAttribute('data-step'), 10) === step);
        });

        // Update progress dots.
        wizard.querySelectorAll('.splive-wizard-dot').forEach(function (dot, i) {
            dot.classList.toggle('active', i < step);
            dot.classList.toggle('current', i + 1 === step);
        });
    }

    function completeOnboarding(wizard) {
        apiPost('admin/onboarding-complete', {}).then(function () {
            wizard.style.opacity = '0';
            setTimeout(function () {
                wizard.style.display = 'none';
            }, 300);
        });
    }

    /* ==========================================================================
       TOAST NOTIFICATIONS
       ========================================================================== */

    function initToasts() {
        if (!document.querySelector('.splive-toast-container')) {
            var container = document.createElement('div');
            container.className = 'splive-toast-container';
            document.body.appendChild(container);
        }
    }

    function showToast(message, type) {
        type = type || 'success';
        var container = document.querySelector('.splive-toast-container');
        if (!container) return;

        var toast = document.createElement('div');
        toast.className = 'splive-toast splive-toast-' + type;

        var icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
        toast.innerHTML = '<span class="splive-toast-icon">' + icon + '</span><span class="splive-toast-message">' + escapeHtml(message) + '</span>';

        container.appendChild(toast);

        // Animate in.
        setTimeout(function () { toast.classList.add('visible'); }, 50);

        // Auto-dismiss.
        setTimeout(function () {
            toast.classList.remove('visible');
            setTimeout(function () { toast.remove(); }, 300);
        }, 4000);
    }

    /* ==========================================================================
       API HELPERS
       ========================================================================== */

    function apiGet(endpoint) {
        return fetch(admin.restUrl + endpoint, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': admin.nonce,
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); });
    }

    function apiPost(endpoint, data) {
        return fetch(admin.restUrl + endpoint, {
            method: 'POST',
            headers: {
                'X-WP-Nonce': admin.nonce,
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify(data || {})
        }).then(function (r) { return r.json(); });
    }

    /* ==========================================================================
       UTILITIES
       ========================================================================== */

    function setElementText(selector, value) {
        var el = document.querySelector(selector);
        if (el) el.textContent = value;
    }

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Initialize.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
