<?php
/**
 * Session repository — all session table database operations.
 *
 * @package SocialProofLive\Database
 */

namespace SocialProofLive\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Class Session_Repository
 *
 * Handles CRUD operations for the sessions table.
 */
class Session_Repository {

    /**
     * Get the sessions table name.
     *
     * @return string
     */
    private function table() {
        return Database::get_sessions_table();
    }

    /**
     * Record or update a visitor session (heartbeat).
     *
     * Uses INSERT ... ON DUPLICATE KEY UPDATE for atomic upsert.
     *
     * @param string $session_hash  Unique session identifier.
     * @param int    $product_id    Product being viewed.
     * @param string $ip_hash       Hashed IP address.
     * @param string $ua_hash       Hashed user agent.
     * @return bool True on success.
     */
    public function upsert_session( $session_hash, $product_id, $ip_hash, $ua_hash ) {
        global $wpdb;

        $table = $this->table();
        $now   = current_time( 'mysql', true );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$table} (session_hash, product_id, ip_hash, user_agent_hash, started_at, last_seen, is_active)
                VALUES (%s, %d, %s, %s, %s, %s, 1)
                ON DUPLICATE KEY UPDATE last_seen = %s, is_active = 1",
                $session_hash,
                $product_id,
                $ip_hash,
                $ua_hash,
                $now,
                $now,
                $now
            )
        );

        return false !== $result;
    }

    /**
     * Mark a session as inactive (visitor left).
     *
     * @param string $session_hash Session identifier.
     * @param int    $product_id   Product ID.
     * @return bool True on success.
     */
    public function deactivate_session( $session_hash, $product_id ) {
        global $wpdb;

        $table = $this->table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->update(
            $table,
            array( 'is_active' => 0 ),
            array(
                'session_hash' => $session_hash,
                'product_id'   => $product_id,
            ),
            array( '%d' ),
            array( '%s', '%d' )
        );

        return false !== $result;
    }

    /**
     * Count active viewers for a specific product.
     *
     * @param int $product_id Product ID.
     * @param int $timeout    Session timeout in seconds.
     * @return int Number of active viewers.
     */
    public function count_active_viewers( $product_id, $timeout = 120 ) {
        global $wpdb;

        $table     = $this->table();
        $threshold = gmdate( 'Y-m-d H:i:s', time() - $timeout );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                WHERE product_id = %d
                AND is_active = 1
                AND last_seen >= %s",
                $product_id,
                $threshold
            )
        );

        return absint( $count );
    }

    /**
     * Count total active viewers across all products.
     *
     * @param int $timeout Session timeout in seconds.
     * @return int Total active viewers site-wide.
     */
    public function count_total_active_viewers( $timeout = 120 ) {
        global $wpdb;

        $table     = $this->table();
        $threshold = gmdate( 'Y-m-d H:i:s', time() - $timeout );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                WHERE is_active = 1
                AND last_seen >= %s",
                $threshold
            )
        );

        return absint( $count );
    }

    /**
     * Get top products by active viewer count.
     *
     * @param int $limit   Number of products to return.
     * @param int $timeout Session timeout in seconds.
     * @return array Array of product_id => viewer_count pairs.
     */
    public function get_top_products( $limit = 10, $timeout = 120 ) {
        global $wpdb;

        $table     = $this->table();
        $threshold = gmdate( 'Y-m-d H:i:s', time() - $timeout );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT product_id, COUNT(*) as viewer_count
                FROM {$table}
                WHERE is_active = 1
                AND last_seen >= %s
                GROUP BY product_id
                ORDER BY viewer_count DESC
                LIMIT %d",
                $threshold,
                $limit
            ),
            ARRAY_A
        );

        return $results ? $results : array();
    }

    /**
     * Mark expired sessions as inactive.
     *
     * @param int $timeout Session timeout in seconds.
     * @return int Number of sessions marked inactive.
     */
    public function expire_stale_sessions( $timeout = 120 ) {
        global $wpdb;

        $table     = $this->table();
        $threshold = gmdate( 'Y-m-d H:i:s', time() - $timeout );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $affected = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table}
                SET is_active = 0
                WHERE is_active = 1
                AND last_seen < %s",
                $threshold
            )
        );

        return absint( $affected );
    }

    /**
     * Delete sessions older than a given number of hours.
     *
     * @param int $hours Number of hours.
     * @return int Number of rows deleted.
     */
    public function purge_old_sessions( $hours = 24 ) {
        global $wpdb;

        $table     = $this->table();
        $threshold = gmdate( 'Y-m-d H:i:s', time() - ( $hours * HOUR_IN_SECONDS ) );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE last_seen < %s",
                $threshold
            )
        );

        return absint( $deleted );
    }

    /**
     * Get today's total unique sessions.
     *
     * @return int Total sessions today.
     */
    public function get_today_session_count() {
        global $wpdb;

        $table = $this->table();
        $today = gmdate( 'Y-m-d' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT session_hash) FROM {$table}
                WHERE DATE(started_at) = %s",
                $today
            )
        );

        return absint( $count );
    }

    /**
     * Check if a session hash already exists for a product.
     *
     * @param string $session_hash Session identifier.
     * @param int    $product_id   Product ID.
     * @return bool True if exists.
     */
    public function session_exists( $session_hash, $product_id ) {
        global $wpdb;

        $table = $this->table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                WHERE session_hash = %s AND product_id = %d",
                $session_hash,
                $product_id
            )
        );

        return absint( $exists ) > 0;
    }

    /**
     * Get session data for rate limiting checks.
     *
     * @param string $session_hash Session identifier.
     * @param int    $product_id   Product ID.
     * @return object|null Session row or null.
     */
    public function get_session( $session_hash, $product_id ) {
        global $wpdb;

        $table = $this->table();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                WHERE session_hash = %s AND product_id = %d",
                $session_hash,
                $product_id
            )
        );

        return $row;
    }
}
