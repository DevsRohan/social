=== Social Proof LIVE — Real-Time Visitor Activity for WooCommerce ===
Contributors: socialprooflive
Tags: woocommerce, social proof, live viewers, urgency, conversion optimization
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show REAL-TIME visitor activity on product pages — live viewer counts, cart activity, and recent purchases. 100% real data, zero fakes.

== Description ==

**Social Proof LIVE** displays authentic, real-time visitor activity on your WooCommerce product pages to create urgency and social proof, directly increasing conversions.

= What It Shows =

* 🔥 **Live Viewer Count** — "14 people are viewing this right now"
* ⏰ **Cart Activity** — "3 people have this in their cart"
* ✓ **Recent Purchase** — "Last purchased 7 minutes ago"

= Key Features =

* **100% Real Data** — No fake numbers, no inflated counts. All data sourced from actual visitor sessions and WooCommerce orders.
* **Lightweight** — Under 5KB JavaScript, under 3KB CSS. Zero jQuery dependency.
* **Privacy First** — No cookies, no personal data stored, GDPR-friendly by design.
* **4 Beautiful Themes** — Default, Minimal, Bold, and Glass (glassmorphism).
* **Dark Mode** — Auto-detects system preference or set manually.
* **Mobile Responsive** — Looks great on all devices.
* **Smart Thresholds** — Don't show widget until enough viewers are active.
* **Performance Optimized** — Caching, indexed queries, minimal server load.
* **WooCommerce HPOS Compatible** — Works with new order storage.
* **Developer Friendly** — Filters, hooks, shortcodes, overridable templates.

= How It Works =

1. Install and activate the plugin
2. Complete the 60-second setup wizard
3. Widget automatically appears on product pages
4. Real visitor data updates every 15-60 seconds

No external services. No tracking scripts. Everything runs on YOUR server.

== Installation ==

1. Upload the `social-proof-live` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Complete the setup wizard that appears automatically
4. Visit any product page to see the widget in action

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 5.0 or higher (for cart and purchase features)
* PHP 7.4 or higher

== Frequently Asked Questions ==

= Is the data real? =

Yes, 100%. Every number shown comes from actual visitor sessions, WooCommerce cart data, and completed orders. Nothing is fabricated.

= Does it slow down my site? =

No. The JavaScript payload is under 5KB. Data updates happen via lightweight REST API calls every 20 seconds (configurable). A persistent object cache (Redis/Memcached) is recommended for high-traffic stores.

= Is it GDPR compliant? =

Yes. No cookies are set. No personally identifiable information is stored. Visitor sessions use non-reversible cryptographic hashes.

= Can I customize the text? =

Yes. Every message is fully customizable with placeholder variables ({count} and {time}).

= Can I disable it for specific products? =

Yes. Each product has a meta box option to disable social proof individually.

= Does it work with page builders? =

Yes. The widget hooks into WooCommerce product page actions which work with all major page builders. You can also use the [social_proof_live] shortcode.

== Screenshots ==

1. Widget on product page (Default theme)
2. Admin Dashboard
3. Settings page
4. Onboarding wizard
5. Glass theme with dark mode

== Changelog ==

= 1.0.0 =
* Initial release
* Live viewer count tracking
* Cart activity display
* Recent purchase timestamps
* 4 widget themes (Default, Minimal, Bold, Glass)
* 6 animation styles
* Dark mode support
* Admin dashboard with live stats
* Analytics with historical data
* Product-level disable option
* Shortcode support
* REST API endpoints
* Object cache support
* Bot detection
* GDPR-compliant (no PII, no cookies)

== Upgrade Notice ==

= 1.0.0 =
Initial release. Install and activate to start showing real-time social proof on your WooCommerce product pages.
