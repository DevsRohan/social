<?php
/**
 * Social Proof LIVE — Landing page backend configuration.
 *
 * ============================================================
 *  STEP 1: Paste your Razorpay API keys below.
 *  STEP 2: Put your two plugin ZIP files in the /downloads folder.
 *  STEP 3: Set the price. Done!
 * ============================================================
 *
 * Get your keys from: https://dashboard.razorpay.com/app/keys
 */

// ---- Razorpay keys (REQUIRED) ----
define( 'RZP_KEY_ID',     'rzp_test_XXXXXXXXXXXXXX' ); // <-- paste your Key ID
define( 'RZP_KEY_SECRET', 'XXXXXXXXXXXXXXXXXXXXXXXX' ); // <-- paste your Key Secret

// ---- Pricing ----
// Amount the customer pays for the Pro plugin, in RUPEES.
define( 'PRO_PRICE_INR', 1599 );
define( 'CURRENCY', 'INR' );

// ---- Plugin ZIP files (put these in the /downloads folder) ----
define( 'PRO_ZIP_FILE',   __DIR__ . '/../downloads/social-proof-live-pro.zip' );
define( 'TRIAL_ZIP_FILE', __DIR__ . '/../downloads/social-proof-live-trial.zip' );

// Friendly download filenames.
define( 'PRO_ZIP_NAME',   'social-proof-live-pro.zip' );
define( 'TRIAL_ZIP_NAME', 'social-proof-live-trial.zip' );

// ---- Security ----
// How long a post-payment download link stays valid (seconds).
define( 'DOWNLOAD_TOKEN_TTL', 900 ); // 15 minutes

// Support contact shown in error messages.
define( 'SUPPORT_EMAIL', 'itsdevsarun@gmail.com' );

// ------------------------------------------------------------
//  Helpers (no need to edit below this line)
// ------------------------------------------------------------

/**
 * Send a JSON response and exit.
 *
 * @param array $data Response payload.
 * @param int   $code HTTP status code.
 */
function splive_json( $data, $code = 200 ) {
    http_response_code( $code );
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'X-Content-Type-Options: nosniff' );
    echo json_encode( $data );
    exit;
}

/**
 * Basic CORS / method guard for same-origin POST endpoints.
 *
 * @param string $method Required HTTP method.
 */
function splive_guard_method( $method ) {
    if ( strtoupper( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) !== strtoupper( $method ) ) {
        splive_json( array( 'success' => false, 'message' => 'Method not allowed.' ), 405 );
    }
}

/**
 * Are the Razorpay keys configured (not the placeholders)?
 *
 * @return bool
 */
function splive_keys_ready() {
    return RZP_KEY_ID && RZP_KEY_SECRET
        && strpos( RZP_KEY_ID, 'XXXX' ) === false
        && strpos( RZP_KEY_SECRET, 'XXXX' ) === false;
}

/**
 * Create a short-lived signed download token (stateless, no DB needed).
 *
 * @param string $type Download type (pro|trial).
 * @return string
 */
function splive_make_token( $type ) {
    $payload = $type . '.' . ( time() + DOWNLOAD_TOKEN_TTL );
    $sig     = hash_hmac( 'sha256', $payload, RZP_KEY_SECRET );
    return rtrim( strtr( base64_encode( $payload . '.' . $sig ), '+/', '-_' ), '=' );
}

/**
 * Validate a signed download token.
 *
 * @param string $token Token from the URL.
 * @param string $type  Expected type.
 * @return bool
 */
function splive_check_token( $token, $type ) {
    $raw = base64_decode( strtr( $token, '-_', '+/' ) );
    $parts = explode( '.', $raw );
    if ( count( $parts ) !== 3 ) {
        return false;
    }
    list( $t, $exp, $sig ) = $parts;
    if ( $t !== $type || (int) $exp < time() ) {
        return false;
    }
    $expected = hash_hmac( 'sha256', $t . '.' . $exp, RZP_KEY_SECRET );
    return hash_equals( $expected, $sig );
}
