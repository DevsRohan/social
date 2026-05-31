<?php
/**
 * Stats repository — all stats table database operations.
 *
 * @package SocialProofLive\Database
 */

namespace SocialProofLive\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Class Stats_Repository
 *
 * Handles CRUD operations for the stats table.
 */
class Stats_Repository {

    /**
     * Get the stats table name.
     *
     * @return string
     */
    private function table() {
        return Database::get_stats_table();
    }

    /**
     * Record or update hourly stats for a product.
     *
     * @param int $product_id     Product ID.
     * @param int $current_viewers Current viewer count.
     * @param int $cart_additions  Cart additions this period.
     * @param int $purchases       Purchases this period.
     * @return bool True on success.
     */
    public function record_hourly_stats( $product_id, $current_viewers, $cart_additions = 0, $purchases = 0 ) {
        global $wpdb;

        $table = $this->table();
        $date  = gmdate( 'Y-m-d' );
        $hour  = absint( gmdate( 'G' ) );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$table} (product_id, stat_date, stat_hour, max_viewers, avg_viewers, total_sessions, cart_additions, purchases)
                VALUES (%d, %s, %d, %d, %f, 1, %d, %d)
                ON DUPLICATE KEY UPDATE
                    max_viewers = GREATEST(max_viewers, VALUES(max_viewers)),
                    avg_viewers = (avg_viewers * total_sessions + VALUES(avg_viewers)) / (total_sessions + 1),
                    total_sessions = total_sessions + 1,
                    cart_additions = cart_additions + VALUES(cart_additions),
                    purchases = purchases + VALUES(purchases)",
                $product_id,
                $date,
                $hour,
                $current_viewers,
                (float) $current_viewers,
                $cart_additions,
                $purchases
            )
        );

        return false !== $result;
    }

    /**
     * Increment a single metric counter for a product in the current hour.
     *
     * Used for real conversion tracking (impressions, cart_additions, purchases).
     *
     * @param int    $product_id Product ID.
     * @param string $metric     One of: impressions, cart_additions, purchases.
     * @param int    $amount     Amount to increment by.
     * @return bool True on success.
     */
    public function increment_metric( $product_id, $metric, $amount = 1 ) {
        $allowed = array( 'impressions', 'cart_additions', 'purchases' );
        if ( ! in_array( $metric, $allowed, true ) ) {
            return false;
        }

        global $wpdb;

        $table  = $this->table();
        $date   = gmdate( 'Y-m-d' );
        $hour   = absint( gmdate( 'G' ) );
        $amount = absint( $amount );

        // The metric column name is validated against a whitelist above, so it is
        // safe to interpolate directly into the query.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$table} (product_id, stat_date, stat_hour, {$metric})
                VALUES (%d, %s, %d, %d)
                ON DUPLICATE KEY UPDATE {$metric} = {$metric} + VALUES({$metric})",
                $product_id,
                $date,
                $hour,
                $amount
            )
        );

        return false !== $result;
    }

    /**
     * Get stats for a product within a date range.
     *
     * @param int    $product_id Product ID. 0 for all products.
     * @param string $start_date Start date (Y-m-d).
     * @param string $end_date   End date (Y-m-d).
     * @return array Stats rows.
     */
    public function get_stats_range( $product_id = 0, $start_date = '', $end_date = '' ) {
        global $wpdb;

        $table = $this->table();

        if ( empty( $start_date ) ) {
            $start_date = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
        }
        if ( empty( $end_date ) ) {
            $end_date = gmdate( 'Y-m-d' );
        }

        if ( $product_id > 0 ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table}
                    WHERE product_id = %d
                    AND stat_date BETWEEN %s AND %s
                    ORDER BY stat_date ASC, stat_hour ASC",
                    $product_id,
                    $start_date,
                    $end_date
                ),
                ARRAY_A
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT stat_date, stat_hour,
                        SUM(max_viewers) as max_viewers,
                        AVG(avg_viewers) as avg_viewers,
                        SUM(total_sessions) as total_sessions,
                        SUM(cart_additions) as cart_additions,
                        SUM(purchases) as purchases
                    FROM {$table}
                    WHERE stat_date BETWEEN %s AND %s
                    GROUP BY stat_date, stat_hour
                    ORDER BY stat_date ASC, stat_hour ASC",
                    $start_date,
                    $end_date
                ),
                ARRAY_A
            );
        }

        return $results ? $results : array();
    }

    /**
     * Get hourly stats for today (for the live chart).
     *
     * @return array Hourly data points.
     */
    public function get_today_hourly() {
        global $wpdb;

        $table = $this->table();
        $today = gmdate( 'Y-m-d' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT stat_hour, SUM(max_viewers) as viewers, SUM(total_sessions) as sessions
                FROM {$table}
                WHERE stat_date = %s
                GROUP BY stat_hour
                ORDER BY stat_hour ASC",
                $today
            ),
            ARRAY_A
        );

        return $results ? $results : array();
    }

    /**
     * Get summary stats for a date range.
     *
     * @param string $start_date Start date (Y-m-d).
     * @param string $end_date   End date (Y-m-d).
     * @return array Summary data.
     */
    public function get_summary( $start_date = '', $end_date = '' ) {
        global $wpdb;

        $table = $this->table();

        if ( empty( $start_date ) ) {
            $start_date = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
        }
        if ( empty( $end_date ) ) {
            $end_date = gmdate( 'Y-m-d' );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    MAX(max_viewers) as peak_viewers,
                    AVG(avg_viewers) as avg_viewers,
                    SUM(total_sessions) as total_sessions,
                    SUM(impressions) as total_impressions,
                    SUM(cart_additions) as total_cart_additions,
                    SUM(purchases) as total_purchases
                FROM {$table}
                WHERE stat_date BETWEEN %s AND %s",
                $start_date,
                $end_date
            ),
            ARRAY_A
        );

        return $result ? $result : array(
            'peak_viewers'         => 0,
            'avg_viewers'          => 0,
            'total_sessions'       => 0,
            'total_impressions'    => 0,
            'total_cart_additions'  => 0,
            'total_purchases'      => 0,
        );
    }

    /**
     * Purge stats older than retention period.
     *
     * @param int $days Number of days to retain.
     * @return int Number of rows deleted.
     */
    public function purge_old_stats( $days = 30 ) {
        global $wpdb;

        $table     = $this->table();
        $threshold = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE stat_date < %s",
                $threshold
            )
        );

        return absint( $deleted );
    }

    /**
     * Get top products by total sessions in date range.
     *
     * @param int    $limit      Number of products.
     * @param string $start_date Start date.
     * @param string $end_date   End date.
     * @return array Top products with stats.
     */
    public function get_top_products_by_sessions( $limit = 10, $start_date = '', $end_date = '' ) {
        global $wpdb;

        $table = $this->table();

        if ( empty( $start_date ) ) {
            $start_date = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
        }
        if ( empty( $end_date ) ) {
            $end_date = gmdate( 'Y-m-d' );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT product_id,
                    MAX(max_viewers) as peak_viewers,
                    AVG(avg_viewers) as avg_viewers,
                    SUM(total_sessions) as total_sessions,
                    SUM(purchases) as total_purchases
                FROM {$table}
                WHERE stat_date BETWEEN %s AND %s
                GROUP BY product_id
                ORDER BY total_sessions DESC
                LIMIT %d",
                $start_date,
                $end_date,
                $limit
            ),
            ARRAY_A
        );

        return $results ? $results : array();
    }
}
