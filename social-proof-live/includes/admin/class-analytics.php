<?php
/**
 * Analytics — processes stats data for admin charts.
 *
 * @package SocialProofLive\Admin
 */

namespace SocialProofLive\Admin;

use SocialProofLive\Database\Stats_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Analytics
 *
 * Provides processed analytics data for the admin dashboard and analytics page.
 */
class Analytics {

    /**
     * Stats repository.
     *
     * @var Stats_Repository
     */
    private $stats_repo;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->stats_repo = new Stats_Repository();
    }

    /**
     * Get formatted chart data for a date range.
     *
     * @param string $start_date Start date.
     * @param string $end_date   End date.
     * @return array Chart-ready data.
     */
    public function get_chart_data( $start_date = '', $end_date = '' ) {
        $raw_data = $this->stats_repo->get_stats_range( 0, $start_date, $end_date );

        $chart_data = array(
            'labels' => array(),
            'viewers' => array(),
            'sessions' => array(),
        );

        foreach ( $raw_data as $row ) {
            $label = $row['stat_date'] . ' ' . str_pad( $row['stat_hour'], 2, '0', STR_PAD_LEFT ) . ':00';
            $chart_data['labels'][]   = $label;
            $chart_data['viewers'][]  = (int) $row['max_viewers'];
            $chart_data['sessions'][] = (int) $row['total_sessions'];
        }

        return $chart_data;
    }

    /**
     * Get conversion metrics.
     *
     * @param string $start_date Start date.
     * @param string $end_date   End date.
     * @return array Conversion data.
     */
    public function get_conversion_metrics( $start_date = '', $end_date = '' ) {
        $summary = $this->stats_repo->get_summary( $start_date, $end_date );

        $total_sessions = max( 1, (int) $summary['total_sessions'] );
        $purchases      = (int) $summary['total_purchases'];
        $cart_additions = (int) $summary['total_cart_additions'];

        return array(
            'total_sessions'    => $total_sessions,
            'total_purchases'   => $purchases,
            'total_cart_adds'   => $cart_additions,
            'purchase_rate'     => round( ( $purchases / $total_sessions ) * 100, 2 ),
            'cart_rate'         => round( ( $cart_additions / $total_sessions ) * 100, 2 ),
        );
    }

    /**
     * Get hourly heatmap data.
     *
     * @param string $start_date Start date.
     * @param string $end_date   End date.
     * @return array 24-element array of average viewers per hour.
     */
    public function get_hourly_heatmap( $start_date = '', $end_date = '' ) {
        $raw_data = $this->stats_repo->get_stats_range( 0, $start_date, $end_date );
        $hours    = array_fill( 0, 24, array( 'total' => 0, 'count' => 0 ) );

        foreach ( $raw_data as $row ) {
            $hour = (int) $row['stat_hour'];
            $hours[ $hour ]['total'] += (float) $row['avg_viewers'];
            $hours[ $hour ]['count']++;
        }

        $heatmap = array();
        for ( $i = 0; $i < 24; $i++ ) {
            $avg = $hours[ $i ]['count'] > 0
                ? round( $hours[ $i ]['total'] / $hours[ $i ]['count'], 1 )
                : 0;
            $heatmap[] = $avg;
        }

        return $heatmap;
    }
}
