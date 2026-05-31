/**
 * Social Proof LIVE — Frontend JavaScript
 *
 * Handles heartbeat communication, widget rendering, and animations.
 * Vanilla JS, no jQuery dependency. < 5KB minified.
 *
 * @package SocialProofLive
 */
(function () {
    'use strict';

    // Bail if config not available.
    if (typeof spliveConfig === 'undefined') {
        return;
    }

    var config = spliveConfig;
    var widgets = [];
    var sessionHash = '';
    var heartbeatTimer = null;
    var isPageVisible = true;
    var retryCount = 0;
    var maxRetries = 5;

    /**
     * Initialize the plugin.
     */
    function init() {
        widgets = document.querySelectorAll('.splive-widget[data-product-id]');

        if (!widgets.length) {
            return;
        }

        // Listen for page visibility changes.
        document.addEventListener('visibilitychange', onVisibilityChange);

        // Listen for page unload to send leave beacon.
        window.addEventListener('beforeunload', onPageLeave);
        window.addEventListener('pagehide', onPageLeave);

        // Start heartbeat after configured delay.
        setTimeout(function () {
            sendHeartbeat();
        }, config.displayDelay || 1500);
    }

    /**
     * Send heartbeat to server.
     */
    function sendHeartbeat() {
        if (!isPageVisible) {
            scheduleNextHeartbeat();
            return;
        }

        widgets.forEach(function (widget) {
            var productId = widget.getAttribute('data-product-id');
            if (!productId) return;

            var body = {
                product_id: parseInt(productId, 10)
            };

            if (sessionHash) {
                body.session_hash = sessionHash;
            }

            fetch(config.restUrl + 'heartbeat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': config.nonce
                },
                body: JSON.stringify(body),
                credentials: 'same-origin'
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                retryCount = 0;
                if (data.session_hash) {
                    sessionHash = data.session_hash;
                }
                updateWidget(widget, data);
            })
            .catch(function (error) {
                retryCount++;
                if (retryCount >= maxRetries) {
                    // Stop polling after too many failures.
                    return;
                }
            });
        });

        scheduleNextHeartbeat();
    }

    /**
     * Schedule the next heartbeat.
     */
    function scheduleNextHeartbeat() {
        if (heartbeatTimer) {
            clearTimeout(heartbeatTimer);
        }

        // Exponential backoff on errors.
        var interval = config.heartbeatInterval || 20000;
        if (retryCount > 0) {
            interval = interval * Math.pow(1.5, retryCount);
        }

        // Double interval when page not visible.
        if (!isPageVisible) {
            interval = interval * 2;
        }

        heartbeatTimer = setTimeout(sendHeartbeat, interval);
    }

    /**
     * Update a widget with new data.
     */
    function updateWidget(widget, data) {
        if (!data.show) {
            hideWidget(widget);
            return;
        }

        var hasContent = false;

        // Update viewers.
        if (config.enableViewers && data.viewers !== null && data.viewers >= config.minimumViewers) {
            var viewersEl = widget.querySelector('.splive-viewers');
            if (viewersEl) {
                var text = data.viewers === 1 ? config.textViewersSingular : config.textViewers;
                text = text.replace('{count}', '<strong>' + animateNumber(viewersEl, data.viewers) + '</strong>');
                updateLine(viewersEl, text);
                hasContent = true;
            }
        } else {
            hideLine(widget.querySelector('.splive-viewers'));
        }

        // Update cart.
        if (config.enableCart && data.cart !== null && data.cart >= config.minimumCart) {
            var cartEl = widget.querySelector('.splive-cart');
            if (cartEl) {
                var cartText = data.cart === 1 ? config.textCartSingular : config.textCart;
                cartText = cartText.replace('{count}', '<strong>' + animateNumber(cartEl, data.cart) + '</strong>');
                updateLine(cartEl, cartText);
                hasContent = true;
            }
        } else {
            hideLine(widget.querySelector('.splive-cart'));
        }

        // Update purchase.
        if (config.enablePurchase && data.last_purchase) {
            var purchaseEl = widget.querySelector('.splive-purchase');
            if (purchaseEl) {
                var purchaseText = config.textPurchase.replace('{time}', '<strong>' + escapeHtml(data.last_purchase) + '</strong>');
                updateLine(purchaseEl, purchaseText);
                hasContent = true;
            }
        } else {
            hideLine(widget.querySelector('.splive-purchase'));
        }

        // Update stock urgency.
        if (config.enableStock && data.stock !== null && data.stock > 0) {
            var stockEl = widget.querySelector('.splive-stock');
            if (stockEl) {
                var stockText = config.textStock.replace('{count}', '<strong>' + parseInt(data.stock, 10) + '</strong>');
                updateLine(stockEl, stockText);
                hasContent = true;
            }
        } else {
            hideLine(widget.querySelector('.splive-stock'));
        }

        // Update sale countdown.
        if (config.enableCountdown && data.countdown_seconds !== null && data.countdown_seconds > 0) {
            var countdownEl = widget.querySelector('.splive-countdown');
            if (countdownEl) {
                startCountdown(countdownEl, parseInt(data.countdown_seconds, 10));
                hasContent = true;
            }
        } else {
            hideLine(widget.querySelector('.splive-countdown'));
            stopCountdown();
        }

        if (hasContent) {
            showWidget(widget);
            fireImpression(widget);
        } else {
            hideWidget(widget);
        }
    }

    var countdownTimer = null;
    var countdownRemaining = 0;

    /**
     * Start (or restart) the sale countdown ticking every second locally.
     */
    function startCountdown(el, seconds) {
        countdownRemaining = seconds;
        renderCountdown(el);

        if (el.style.display === 'none') {
            el.style.display = '';
            el.classList.add('splive-entering');
            setTimeout(function () { el.classList.remove('splive-entering'); }, 400);
        }

        if (countdownTimer) {
            clearInterval(countdownTimer);
        }

        countdownTimer = setInterval(function () {
            countdownRemaining--;
            if (countdownRemaining <= 0) {
                stopCountdown();
                el.style.display = 'none';
                return;
            }
            renderCountdown(el);
        }, 1000);
    }

    /**
     * Stop the countdown timer.
     */
    function stopCountdown() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    /**
     * Render the countdown text (HH:MM:SS or with days).
     */
    function renderCountdown(el) {
        var textEl = el.querySelector('.splive-text');
        if (!textEl) return;

        var s = countdownRemaining;
        var days = Math.floor(s / 86400);
        s -= days * 86400;
        var hours = Math.floor(s / 3600);
        s -= hours * 3600;
        var mins = Math.floor(s / 60);
        var secs = s - mins * 60;

        var time = '';
        if (days > 0) {
            time = days + 'd ';
        }
        time += pad(hours) + ':' + pad(mins) + ':' + pad(secs);

        textEl.innerHTML = config.textCountdown.replace('{time}', '<strong>' + time + '</strong>');
    }

    function pad(n) {
        return n < 10 ? '0' + n : '' + n;
    }

    var impressionFired = false;

    /**
     * Fire a one-time conversion impression beacon for this session.
     */
    function fireImpression(widget) {
        if (impressionFired || !config.enableConversionTracking) {
            return;
        }
        var productId = widget.getAttribute('data-product-id');
        if (!productId) return;

        impressionFired = true;

        var data = JSON.stringify({
            product_id: parseInt(productId, 10),
            session_hash: sessionHash
        });

        fetch(config.restUrl + 'impression', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': config.nonce
            },
            body: data,
            credentials: 'same-origin',
            keepalive: true
        }).catch(function () {});
    }

    /**
     * Show a widget line with content update.
     */
    function updateLine(el, html) {
        if (!el) return;

        var textEl = el.querySelector('.splive-text');
        if (textEl) {
            textEl.innerHTML = html;
        }

        if (el.style.display === 'none') {
            el.style.display = '';
            el.classList.add('splive-entering');
            setTimeout(function () {
                el.classList.remove('splive-entering');
            }, 400);
        }
    }

    /**
     * Hide a widget line.
     */
    function hideLine(el) {
        if (el) {
            el.style.display = 'none';
        }
    }

    /**
     * Show the main widget container with animation.
     */
    function showWidget(widget) {
        if (widget.style.display !== 'none') {
            return; // Already visible.
        }

        widget.style.display = '';
        var animation = widget.getAttribute('data-animation') || 'fade-slide';

        widget.classList.add('splive-animate-' + animation);
        widget.classList.add('splive-visible');

        setTimeout(function () {
            widget.classList.add('splive-animated');
        }, 50);
    }

    /**
     * Hide the main widget container.
     */
    function hideWidget(widget) {
        widget.style.display = 'none';
        widget.classList.remove('splive-visible', 'splive-animated');
    }

    /**
     * Animate a number change with count-up effect.
     */
    function animateNumber(el, newValue) {
        var dataKey = 'splive-last-value';
        var oldValue = parseInt(el.getAttribute('data-' + dataKey) || '0', 10);
        el.setAttribute('data-' + dataKey, newValue);

        if (oldValue === 0 || oldValue === newValue) {
            return newValue;
        }

        // Pulse animation on change.
        var textEl = el.querySelector('.splive-text');
        if (textEl) {
            textEl.classList.add('splive-pulse');
            setTimeout(function () {
                textEl.classList.remove('splive-pulse');
            }, 400);
        }

        return newValue;
    }

    /**
     * Handle page visibility changes.
     */
    function onVisibilityChange() {
        isPageVisible = !document.hidden;

        if (isPageVisible && heartbeatTimer) {
            // Resume with immediate heartbeat.
            clearTimeout(heartbeatTimer);
            sendHeartbeat();
        }
    }

    /**
     * Handle page leave — send beacon to deactivate session.
     */
    function onPageLeave() {
        if (!sessionHash) return;

        widgets.forEach(function (widget) {
            var productId = widget.getAttribute('data-product-id');
            if (!productId) return;

            var data = JSON.stringify({
                product_id: parseInt(productId, 10),
                session_hash: sessionHash
            });

            // Use Beacon API for reliable delivery during page unload.
            if (navigator.sendBeacon) {
                var blob = new Blob([data], { type: 'application/json' });
                navigator.sendBeacon(config.restUrl + 'leave?_wpnonce=' + config.nonce, blob);
            } else {
                // Fallback: synchronous XHR (last resort).
                var xhr = new XMLHttpRequest();
                xhr.open('POST', config.restUrl + 'leave', false);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.setRequestHeader('X-WP-Nonce', config.nonce);
                xhr.send(data);
            }
        });
    }

    /**
     * Escape HTML entities.
     */
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Initialize when DOM is ready.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
