/**
 * Social Proof LIVE — Landing page interactions
 * Vanilla JS. Handles nav, scroll reveal, Razorpay checkout,
 * trial/pro downloads, counters and the WhatsApp widget.
 */
(function () {
    'use strict';

    /* -------------------------------------------------------------
       CONFIG — your backend lives here. Update API_BASE if needed.
       The WhatsApp number is set once and reused everywhere.
       ------------------------------------------------------------- */
    var API_BASE = 'api/';                 // PHP backend folder (same server)
    var WHATSAPP_NUMBER = '917654758443';  // +91 76547 58443
    var SUPPORT_EMAIL = 'itsdevsarun@gmail.com';

    /* ----------------------------- helpers ----------------------------- */
    function $(s, c) { return (c || document).querySelector(s); }
    function $all(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }

    function toast(msg) {
        var t = $('#toast');
        t.textContent = msg;
        t.classList.add('is-show');
        clearTimeout(t._t);
        t._t = setTimeout(function () { t.classList.remove('is-show'); t.style.display = 'none'; }, 4000);
        t.style.display = 'block';
    }

    function showOverlay(text) {
        $('#overlay-text').textContent = text || 'Processing…';
        var o = $('#overlay');
        o.style.display = 'flex';
    }
    function hideOverlay() { $('#overlay').style.display = 'none'; }

    /* ----------------------------- year ----------------------------- */
    var y = $('#year'); if (y) { y.textContent = new Date().getFullYear(); }


    /* ----------------------------- sticky nav ----------------------------- */
    var nav = $('#nav');
    function onScroll() {
        if (window.scrollY > 24) {
            nav.classList.add('bg-ink-900/85', 'backdrop-blur-xl', 'shadow-lg', 'ring-1', 'ring-white/10');
        } else {
            nav.classList.remove('bg-ink-900/85', 'backdrop-blur-xl', 'shadow-lg', 'ring-1', 'ring-white/10');
        }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    /* ----------------------------- mobile menu ----------------------------- */
    var hamburger = $('#hamburger');
    var menu = $('#mobile-menu');
    var icOpen = $('#ic-open');
    var icClose = $('#ic-close');

    function toggleMenu(open) {
        if (open) {
            menu.classList.remove('hidden');
            menu.classList.add('is-open');
            icOpen.classList.add('hidden');
            icClose.classList.remove('hidden');
        } else {
            menu.classList.add('hidden');
            menu.classList.remove('is-open');
            icOpen.classList.remove('hidden');
            icClose.classList.add('hidden');
        }
    }
    hamburger.addEventListener('click', function () {
        toggleMenu(menu.classList.contains('hidden'));
    });
    $all('[data-mlink]').forEach(function (a) {
        a.addEventListener('click', function () { toggleMenu(false); });
    });

    /* ----------------------------- scroll reveal ----------------------------- */
    var revealEls = $all('[data-reveal]');
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) { e.target.classList.add('is-visible'); io.unobserve(e.target); }
            });
        }, { threshold: 0.12 });
        revealEls.forEach(function (el) { io.observe(el); });
    } else {
        revealEls.forEach(function (el) { el.classList.add('is-visible'); });
    }

    /* ----------------------------- counters ----------------------------- */
    $all('.counter').forEach(function (el) {
        var to = parseInt(el.getAttribute('data-to'), 10) || 0;
        var start = null, dur = 1400;
        function step(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / dur, 1);
            el.textContent = Math.floor(p * to);
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    });


    /* ----------------------------- WhatsApp widget ----------------------------- */
    var waCard = $('#wa-card');
    var waLauncher = $('#wa-launcher');
    var waClose = $('#wa-close');
    var waForm = $('#wa-form');

    function openWa(open) {
        if (open) { waCard.classList.add('is-open'); }
        else { waCard.classList.remove('is-open'); waCard.style.display = 'none'; }
    }
    waLauncher.addEventListener('click', function () {
        openWa(waCard.style.display !== 'block' && !waCard.classList.contains('is-open'));
        if (!waCard.classList.contains('is-open')) { openWa(true); }
    });
    waClose.addEventListener('click', function () { openWa(false); });

    waForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var name = waForm.name.value.trim();
        var mobile = waForm.mobile.value.trim();
        var desc = waForm.description.value.trim();
        if (!name || !mobile || !desc) { toast('Please fill all fields'); return; }

        var msg = '*New enquiry — Social Proof LIVE*%0A%0A' +
            '*Name:* ' + encodeURIComponent(name) + '%0A' +
            '*Mobile:* ' + encodeURIComponent(mobile) + '%0A' +
            '*Message:* ' + encodeURIComponent(desc);

        var url = 'https://wa.me/' + WHATSAPP_NUMBER + '?text=' + msg;
        window.open(url, '_blank');
        toast('Opening WhatsApp…');
        waForm.reset();
        openWa(false);
    });

    /* ----------------------------- downloads ----------------------------- */
    function triggerDownload(url) {
        var a = document.createElement('a');
        a.href = url;
        a.rel = 'noopener';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    // FREE TRIAL — direct download from the server.
    $all('[data-trial]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            showOverlay('Preparing your free trial…');
            // download.php streams the trial zip (no payment required).
            setTimeout(function () {
                hideOverlay();
                triggerDownload(API_BASE + 'download.php?type=trial');
                toast('Trial download started — enjoy your 24 hours!');
            }, 700);
        });
    });


    /* ----------------------------- Razorpay checkout (PRO) ----------------------------- */
    function startCheckout() {
        showOverlay('Connecting to secure checkout…');

        fetch(API_BASE + 'create-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ plan: 'pro' })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            hideOverlay();
            if (!data || !data.success) {
                toast((data && data.message) || 'Could not start checkout. Please try again.');
                return;
            }

            if (typeof Razorpay === 'undefined') {
                toast('Payment library failed to load. Check your connection.');
                return;
            }

            var options = {
                key: data.key_id,
                amount: data.amount,           // in paise
                currency: data.currency || 'INR',
                name: 'Social Proof LIVE',
                description: 'Pro License — lifetime use, 1 year updates',
                order_id: data.order_id,
                theme: { color: '#FF6B35' },
                handler: function (resp) {
                    verifyPayment(resp);
                },
                modal: {
                    ondismiss: function () { toast('Checkout closed.'); }
                },
                notes: { product: 'social-proof-live-pro' }
            };

            try {
                var rzp = new Razorpay(options);
                rzp.on('payment.failed', function (resp) {
                    toast('Payment failed: ' + (resp.error && resp.error.description ? resp.error.description : 'try again'));
                });
                rzp.open();
            } catch (err) {
                toast('Unable to open checkout.');
            }
        })
        .catch(function () {
            hideOverlay();
            toast('Network error. Please try again or contact ' + SUPPORT_EMAIL);
        });
    }

    function verifyPayment(resp) {
        showOverlay('Verifying payment & preparing your download…');

        fetch(API_BASE + 'verify-payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                razorpay_payment_id: resp.razorpay_payment_id,
                razorpay_order_id: resp.razorpay_order_id,
                razorpay_signature: resp.razorpay_signature
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            hideOverlay();
            if (data && data.success && data.download_url) {
                toast('✅ Payment successful! Your Pro plugin is downloading…');
                triggerDownload(data.download_url);
            } else {
                toast('Payment verified but download failed. Email ' + SUPPORT_EMAIL);
            }
        })
        .catch(function () {
            hideOverlay();
            toast('Verification error. Contact ' + SUPPORT_EMAIL + ' with your payment ID.');
        });
    }

    $all('[data-buy]').forEach(function (btn) {
        btn.addEventListener('click', startCheckout);
    });

})();
