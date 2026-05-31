<?php
/**
 * Create a Razorpay order for the Pro plugin purchase.
 *
 * Returns: { success, order_id, amount, currency, key_id }
 *
 * @package SocialProofLive\Landing
 */

require_once __DIR__ . '/config.php';

splive_guard_method( 'POST' );

if ( ! splive_keys_ready() ) {
    splive_json( array(
        'success' => false,
        'message' => 'Payment is not configured yet. Please contact ' . SUPPORT_EMAIL,
    ), 500 );
}

$amount_paise = (int) round( PRO_PRICE_INR * 100 ); // Razorpay expects paise.

$body = array(
    'amount'          => $amount_paise,
    'currency'        => CURRENCY,
    'receipt'         => 'spl_' . time(),
    'payment_capture' => 1,
    'notes'           => array( 'product' => 'social-proof-live-pro' ),
);

$ch = curl_init( 'https://api.razorpay.com/v1/orders' );
curl_setopt_array( $ch, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_USERPWD        => RZP_KEY_ID . ':' . RZP_KEY_SECRET,
    CURLOPT_HTTPHEADER     => array( 'Content-Type: application/json' ),
    CURLOPT_POSTFIELDS     => json_encode( $body ),
    CURLOPT_TIMEOUT        => 30,
) );

$response = curl_exec( $ch );
$status   = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
$err      = curl_error( $ch );
curl_close( $ch );

if ( $response === false || $err ) {
    splive_json( array( 'success' => false, 'message' => 'Could not reach payment gateway.' ), 502 );
}

$data = json_decode( $response, true );

if ( $status >= 400 || empty( $data['id'] ) ) {
    $msg = isset( $data['error']['description'] ) ? $data['error']['description'] : 'Order creation failed.';
    splive_json( array( 'success' => false, 'message' => $msg ), 502 );
}

splive_json( array(
    'success'  => true,
    'order_id' => $data['id'],
    'amount'   => $amount_paise,
    'currency' => CURRENCY,
    'key_id'   => RZP_KEY_ID,
) );
