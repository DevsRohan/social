# Social Proof LIVE — Landing Page

A premium, fully responsive, conversion-focused landing page for the **Social Proof LIVE** WooCommerce plugin, with **Razorpay** checkout, a **24-hour free trial**, **automatic plugin download after payment**, and a **WhatsApp** contact widget.

**Tech:** HTML + Tailwind CSS (CDN) + vanilla JavaScript, with a tiny PHP backend for secure payments & downloads.

---

## 📁 Structure

```
landing/
├── index.html              # The landing page (HTML + Tailwind + JS)
├── assets/
│   ├── css/styles.css      # Extra polish on top of Tailwind
│   └── js/main.js          # Nav, animations, Razorpay flow, downloads, WhatsApp
├── api/
│   ├── config.php          # ⬅️ PASTE your Razorpay keys + price here
│   ├── create-order.php    # Creates a Razorpay order
│   ├── verify-payment.php  # Verifies the payment signature, issues a download token
│   └── download.php        # Streams the trial (free) or pro (paid) ZIP securely
└── downloads/
    ├── .htaccess           # Blocks direct URL access to the ZIPs
    ├── README.txt          # How to build the two ZIPs
    ├── social-proof-live-trial.zip   (you add this)
    └── social-proof-live-pro.zip     (you add this)
```

---

## 🚀 Setup (3 steps)

### 1. Add your Razorpay keys
Open `api/config.php` and paste:
```php
define( 'RZP_KEY_ID',     'rzp_live_xxxxxxxxxxxx' );
define( 'RZP_KEY_SECRET', 'your_secret_here' );
define( 'PRO_PRICE_INR',  1599 ); // price in rupees
```
Get keys from the [Razorpay Dashboard → API Keys](https://dashboard.razorpay.com/app/keys).

### 2. Upload your two plugin ZIPs
Put `social-proof-live-trial.zip` and `social-proof-live-pro.zip` in the `downloads/` folder (see `downloads/README.txt` for how to build them).

### 3. Upload the `landing/` folder to your server
Any PHP host works (e.g. `https://wp.devsarun.io/landing/`). Open it in a browser — done!

---

## 💳 How the payment + download flow works

1. Visitor clicks **Get Pro** → `main.js` calls `api/create-order.php`.
2. `create-order.php` uses your **secret key** to create a Razorpay order and returns the `order_id` + **public** key.
3. The Razorpay checkout opens (UPI / cards / netbanking / wallets).
4. On success, `main.js` posts the payment details to `api/verify-payment.php`.
5. `verify-payment.php` verifies the signature with your secret and returns a **short-lived signed download link**.
6. The browser **auto-downloads** `social-proof-live-pro.zip`. ✅

The **secret key is never exposed** to the browser — only the backend uses it.

**Free trial:** clicking **Free Trial** downloads `social-proof-live-trial.zip` directly (no payment). That trial build self-deletes after 24 hours.

---

## 💬 WhatsApp widget
The bottom-right widget collects **name, mobile and message**, then opens WhatsApp with a pre-filled message to **+91 76547 58443**. Support email: **itsdevsarun@gmail.com**.

To change the number, edit `WHATSAPP_NUMBER` near the top of `assets/js/main.js`.

---

## 🔒 Notes
- Requires PHP with cURL (standard on any WordPress host).
- HTTPS is strongly recommended (Razorpay requires it in live mode).
- The download token is stateless and HMAC-signed, valid for 15 minutes.
