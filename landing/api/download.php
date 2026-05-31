<?php
/**
 * Secure plugin download handler.
 *
 *  - Trial: ?type=trial         (free, direct download)
 *  - Pro:   ?type=pro&token=... (only after verified payment)
 *
 * @package SocialProofLive\Landing
 */

require_once __DIR__ . '/config.php';

$type = isset( $_GET['type'] ) ? preg_replace( '/[^a-z]/', '', strtolower( $_GET['type'] ) ) : '';

if ( 'trial' === $type ) {
    splive_stream_file( TRIAL_ZIP_FILE, TRIAL_ZIP_NAME );
}

if ( 'pro' === $type ) {
    $token = isset( $_GET['token'] ) ? trim( $_GET['token'] ) : '';
    if ( ! $token || ! splive_check_token( $token, 'pro' ) ) {
        http_response_code( 403 );
        header( 'Content-Type: text/plain; charset=utf-8' );
        echo "Invalid or expired download link.\nPlease complete payment again or email " . SUPPORT_EMAIL;
        exit;
    }
    splive_stream_file( PRO_ZIP_FILE, PRO_ZIP_NAME );
}

http_response_code( 400 );
header( 'Content-Type: text/plain; charset=utf-8' );
echo 'Unknown download type.';
exit;

/**
 * Stream a ZIP file to the browser as an attachment.
 *
 * @param string $path     Absolute file path.
 * @param string $filename Download filename.
 */
function splive_stream_file( $path, $filename ) {
    if ( ! is_file( $path ) || ! is_readable( $path ) ) {
        http_response_code( 404 );
        header( 'Content-Type: text/plain; charset=utf-8' );
        echo "File not available yet.\nThe store owner needs to upload it. Contact " . SUPPORT_EMAIL;
        exit;
    }

    // Clear any output buffers so the binary isn't corrupted.
    while ( ob_get_level() ) {
        ob_end_clean();
    }

    header( 'Content-Type: application/zip' );
    header( 'Content-Disposition: attachment; filename="' . basename( $filename ) . '"' );
    header( 'Content-Length: ' . filesize( $path ) );
    header( 'Cache-Control: no-store, no-cache, must-revalidate' );
    header( 'Pragma: no-cache' );
    header( 'X-Content-Type-Options: nosniff' );

    readfile( $path );
    exit;
}
