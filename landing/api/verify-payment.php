<?php
/**
 * Verify a Razorpay payment signature and issue a secure download link.
 *
 * Razorpay signature = HMAC_SHA256(order_id + "|" + payment_id, key_secret)
 *
 * @package SocialProofLive\Landing
 */

require_once __DIR__ . '/config.php';

splive_guard_method( 'POST' );

if ( ! splive_keys_ready() ) {
    splive_json( array( 'success' => false, 'message' => 'Payment is not configured.' ), 500 );
}

$input = json_decode( file_get_contents( 'php://input' ), true );

$payment_id = isset( $input['razorpay_payment_id'] ) ? trim( $input['razorpay_payment_id'] ) : '';
$order_id   = isset( $input['razorpay_order_id'] ) ? trim( $input['razorpay_order_id'] ) : '';
$signature  = isset( $input['razorpay_signature'] ) ? trim( $input['razorpay_signature'] ) : '';

if ( ! $payment_id || ! $order_id || ! $signature ) {
    splive_json( array( 'success' => false, 'message' => 'Missing payment details.' ), 400 );
}

// Verify the signature.
$expected = hash_hmac( 'sha256', $order_id . '|' . $payment_id, RZP_KEY_SECRET );

if ( ! hash_equals( $expected, $signature ) ) {
    splive_json( array(
        'success' => false,
        'message' => 'Payment could not be verified. If you were charged, email ' . SUPPORT_EMAIL,
    ), 400 );
}

// Signature valid — issue a short-lived signed download token for the Pro zip.
$token = splive_make_token( 'pro' );

splive_json( array(
    'success'      => true,
    'message'      => 'Payment verified.',
    'payment_id'   => $payment_id,
    'download_url' => 'download.php?type=pro&token=' . rawurlencode( $token ),
) );
