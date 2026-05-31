/**
 * Social Proof LIVE — Site-wide Notifications + Visitor Badge
 *
 * Renders recent-sales FOMO popups and a floating live-visitor badge.
 * Also sends a lightweight site-wide presence heartbeat. Vanilla JS.
 *
 * @package SocialProofLive
 */
(function () {
    'use strict';

    if (typeof spliveGlobal === 'undefined') {
        return;
    }

    var cfg = spliveGlobal;
    var feed = [];
    var feedIndex = 0;
    var popupTimer = null;
    var sessionHash = '';
    var badgeEl = null;
    var popupEl = null;

    /**
     * Detect the current device type.
     */
    function deviceType() {
        var w = window.innerWidth || document.documentElement.clientWidth;
        if (w <= 480) return 'mobile';
        if (w <= 1024) return 'tablet';
        return 'desktop';
    }

    /**
     * Check if the current device is allowed by rules.
     */
    function deviceAllowed() {
        var allowed = cfg.allowedDevices || ['desktop', 'tablet', 'mobile'];
        return allowed.indexOf(deviceType()) !== -1;
    }


    /**
     * Initialize.
     */
    function init() {
        if (!deviceAllowed()) {
            return;
        }

        // Build the visitor badge container if enabled.
        if (cfg.enableBadge) {
            buildBadge();
        }

        // Build the popup container if notifications enabled.
        if (cfg.enableNotifications) {
            buildPopupContainer();
        }

        // First fetch after the configured initial delay.
        setTimeout(function () {
            fetchData();
            if (cfg.enableNotifications) {
                startPopupCycle();
            }
        }, cfg.notifInitialDelay || 4000);

        // Poll for fresh data + send presence heartbeat.
        setInterval(fetchData, cfg.pollInterval || 20000);

        // Send presence heartbeat immediately if needed.
        if (cfg.sendGlobalHeartbeat) {
            sendGlobalHeartbeat();
            setInterval(sendGlobalHeartbeat, cfg.pollInterval || 20000);
        }
    }

    /**
     * Fetch feed + visitor count from the server.
     */
    function fetchData() {
        fetch(cfg.restUrl + 'notifications', {
            method: 'GET',
            headers: { 'X-WP-Nonce': cfg.nonce },
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data) return;
            if (data.in_schedule === false) {
                hideBadge();
                return;
            }
            if (Array.isArray(data.feed)) {
                feed = data.feed;
            }
            updateBadge(data.visitor_count);
        })
        .catch(function () {});
    }


    /**
     * Send a lightweight site-wide presence heartbeat (product_id = 0).
     */
    function sendGlobalHeartbeat() {
        var body = { product_id: 0 };
        if (sessionHash) {
            body.session_hash = sessionHash;
        }
        fetch(cfg.restUrl + 'heartbeat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
            body: JSON.stringify(body),
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (d && d.session_hash) { sessionHash = d.session_hash; } })
        .catch(function () {});
    }

    /* ---------------------------------------------------------------------
       VISITOR BADGE
       --------------------------------------------------------------------- */

    function buildBadge() {
        badgeEl = document.createElement('div');
        badgeEl.className = 'splive-badge-live splive-pos-' + (cfg.badgePosition || 'bottom-right');
        badgeEl.style.display = 'none';
        badgeEl.innerHTML = '<span class="splive-badge-dot">' + escapeHtml(cfg.iconBadge || '') +
            '</span><span class="splive-badge-text"></span>';
        document.body.appendChild(badgeEl);
    }

    function updateBadge(count) {
        if (!cfg.enableBadge || !badgeEl) return;

        if (count === null || typeof count === 'undefined' || count <= 0) {
            hideBadge();
            return;
        }

        var text = (cfg.textBadge || '{count} people are browsing right now')
            .replace('{count}', '<strong>' + parseInt(count, 10) + '</strong>');
        badgeEl.querySelector('.splive-badge-text').innerHTML = text;

        if (badgeEl.style.display === 'none') {
            badgeEl.style.display = '';
            setTimeout(function () { badgeEl.classList.add('visible'); }, 30);
        }
    }

    function hideBadge() {
        if (badgeEl) {
            badgeEl.classList.remove('visible');
        }
    }


    /* ---------------------------------------------------------------------
       FOMO SALES POPUPS
       --------------------------------------------------------------------- */

    function buildPopupContainer() {
        if (cfg.notifHideOnMobile && deviceType() === 'mobile') {
            return;
        }
        popupEl = document.createElement('div');
        popupEl.className = 'splive-popup splive-pos-' + (cfg.notifPosition || 'bottom-left');
        popupEl.style.display = 'none';
        document.body.appendChild(popupEl);
    }

    function startPopupCycle() {
        if (!popupEl) return;
        if (popupTimer) clearTimeout(popupTimer);

        var gap = cfg.notifGap || 8000;
        var showTime = cfg.notifDisplayTime || 6000;

        function cycle() {
            if (!feed.length) {
                popupTimer = setTimeout(cycle, gap);
                return;
            }

            if (feedIndex >= feed.length) {
                if (cfg.notifLoop) {
                    feedIndex = 0;
                } else {
                    return;
                }
            }

            showPopup(feed[feedIndex]);
            feedIndex++;

            popupTimer = setTimeout(function () {
                hidePopup();
                popupTimer = setTimeout(cycle, gap);
            }, showTime);
        }

        cycle();
    }


    function showPopup(event) {
        if (!popupEl || !event) return;

        var html = '';

        if (cfg.notifShowImage && event.image) {
            html += '<div class="splive-popup-img" style="background-image:url(\'' +
                encodeURI(event.image) + '\')"></div>';
        }

        html += '<div class="splive-popup-body">';

        // Headline: "{name} from {location}" or "{name}".
        var headline;
        if (cfg.notifShowLocation && event.location) {
            headline = (cfg.textNotif || '{name} from {location} purchased')
                .replace('{name}', '<strong>' + escapeHtml(event.name) + '</strong>')
                .replace('{location}', escapeHtml(event.location));
        } else {
            headline = (cfg.textNotifNoLocation || '{name} purchased')
                .replace('{name}', '<strong>' + escapeHtml(event.name) + '</strong>');
        }
        html += '<div class="splive-popup-headline">' + headline + '</div>';

        // Product line.
        html += '<div class="splive-popup-product">' + escapeHtml(cfg.textNotifVerb || 'just bought') +
            ' ' + escapeHtml(event.product) + '</div>';

        // Time + verified badge.
        if (cfg.notifShowTime && event.time_human) {
            html += '<div class="splive-popup-meta">' + escapeHtml(event.time_human) +
                ' <span class="splive-popup-verified">&#10003; Verified</span></div>';
        }

        html += '</div>';
        html += '<button class="splive-popup-close" aria-label="Close">&times;</button>';

        popupEl.innerHTML = html;

        // Click to product.
        if (cfg.notifClickToProduct && event.product_url) {
            popupEl.classList.add('splive-clickable');
            popupEl.onclick = function (e) {
                if (e.target.className === 'splive-popup-close') return;
                window.location.href = event.product_url;
            };
        } else {
            popupEl.classList.remove('splive-clickable');
            popupEl.onclick = null;
        }

        var closeBtn = popupEl.querySelector('.splive-popup-close');
        if (closeBtn) {
            closeBtn.onclick = function (e) {
                e.stopPropagation();
                hidePopup();
                if (popupTimer) clearTimeout(popupTimer);
            };
        }

        popupEl.style.display = '';
        setTimeout(function () { popupEl.classList.add('visible'); }, 30);

        if (cfg.notifSound) {
            playPing();
        }
    }

    function hidePopup() {
        if (popupEl) {
            popupEl.classList.remove('visible');
        }
    }

    function playPing() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.05, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.3);
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
        } catch (e) {}
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str == null ? '' : str));
        return div.innerHTML;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
