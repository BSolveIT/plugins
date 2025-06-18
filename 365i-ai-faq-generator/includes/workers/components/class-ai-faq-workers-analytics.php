<?php
/**
 * Workers Analytics class for 365i AI FAQ Generator.
 * 
 * This class handles usage analytics tracking, storage, and reporting
 * for worker API requests.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Workers
 * @since 2.1.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Workers Analytics class.
 * 
 * Manages analytics data collection and reporting.
 * 
 * @since 2.1.0
 */
class AI_FAQ_Workers_Analytics {

	/**
	 * Analytics retention period in days.
	 * 
	 * @since 2.1.0
	 * @var int
	 */
	private $retention_days = 90;

	/**
	 * Constructor.
	 * 
	 * Initialize the analytics component.
	 * 
	 * @since 2.1.0
	 */
	public function __construct() {
		// Constructor is empty for now.
	}

	/**
	 * Initialize the analytics component.
	 * 
	 * Set up hooks and filters.
	 * 
	 * @since 2.1.0
	 */
	public function init() {
		// Schedule cleanup task for old analytics data.
		if ( ! wp_next_scheduled( 'ai_faq_cleanup_analytics' ) ) {
			wp_schedule_event( time(), 'daily', 'ai_faq_cleanup_analytics' );
		}

		// Add cleanup hook.
		add_action( 'ai_faq_cleanup_analytics', array( $this, 'cleanup_old_analytics' ) );
	}

	/**
	 * Track usage analytics for dashboard.
	 * 
	 * @since 2.1.0
	 * @param string $worker_name Worker identifier.
	 * @param string $ip_address Client IP address.
	 * @param bool   $success Whether request was successful.
	 * @param float  $start_time Start time of request for response time calculation.
	 */
	public function track_usage( $worker_name, $ip_address, $success = true, $start_time = 0 ) {
		$stats = get_option( 'ai_faq_usage_stats', array() );
		$today = date( 'Y-m-d' );
		$response_time = $start_time > 0 ? round( ( microtime( true ) - $start_time ) * 1000, 2 ) : 0; // Convert to milliseconds
		
		// Initialize daily stats if not exists.
		if ( ! isset( $stats[ $today ] ) ) {
			$stats[ $today ] = array(
				'total_requests' => 0,
				'successful_requests' => 0,
				'failed_requests' => 0,
				'unique_ips' => array(),
				'total_response_time' => 0,
				'workers' => array(),
			);
		}
		
		// Update daily totals.
		$stats[ $today ]['total_requests']++;
		$stats[ $today ]['total_response_time'] += $response_time;
		
		if ( $success ) {
			$stats[ $today ]['successful_requests']++;
		} else {
			$stats[ $today ]['failed_requests']++;
		}
		
		// Track unique IPs (hashed for privacy).
		$ip_hash = md5( $ip_address . date( 'Y-m-d' ) ); // Daily unique tracking.
		$stats[ $today ]['unique_ips'][ $ip_hash ] = 1;
		
		// Track per-worker usage.
		if ( ! isset( $stats[ $today ]['workers'][ $worker_name ] ) ) {
			$stats[ $today ]['workers'][ $worker_name ] = array(
				'requests' => 0,
				'success' => 0,
				'failed' => 0,
				'total_response_time' => 0,
			);
		}
		
		$stats[ $today ]['workers'][ $worker_name ]['requests']++;
		$stats[ $today ]['workers'][ $worker_name ]['total_response_time'] += $response_time;
		
		if ( $success ) {
			$stats[ $today ]['workers'][ $worker_name ]['success']++;
		} else {
			$stats[ $today ]['workers'][ $worker_name ]['failed']++;
		}
		
		// Keep only within retention period.
		$cutoff_date = date( 'Y-m-d', strtotime( '-' . $this->retention_days . ' days' ) );
		$stats = array_filter( $stats, function( $key ) use ( $cutoff_date ) {
			return $key >= $cutoff_date;
		}, ARRAY_FILTER_USE_KEY );
		
		update_option( 'ai_faq_usage_stats', $stats );
	}

	/**
	 * Get analytics data for a period.
	 * 
	 * @since 2.1.0
	 * @param int $period_days Period in days to fetch data for.
	 * @return array Analytics data.
	 */
	public function get_analytics_data( $period_days = 30 ) {
		$stats = get_option( 'ai_faq_usage_stats', array() );
		$start_date = date( 'Y-m-d', strtotime( "-{$period_days} days" ) );

		$analytics_data = array(
			'total_requests' => 0,
			'successful_requests' => 0,
			'failed_requests' => 0,
			'unique_users' => array(),
			'daily_data' => array(),
			'worker_performance' => array(),
		);

		// Process daily statistics.
		foreach ( $stats as $date => $daily_stats ) {
			if ( $date >= $start_date ) {
				$analytics_data['total_requests'] += $daily_stats['total_requests'];
				$analytics_data['successful_requests'] += $daily_stats['successful_requests'];
				$analytics_data['failed_requests'] += $daily_stats['failed_requests'];
				
				// Merge unique users (already hashed).
				$analytics_data['unique_users'] = array_merge( 
					$analytics_data['unique_users'], 
					array_keys( $daily_stats['unique_ips'] ) 
				);

				// Add daily data for charts.
				$analytics_data['daily_data'][ $date ] = array(
					'total' => $daily_stats['total_requests'],
					'success' => $daily_stats['successful_requests'],
					'failed' => $daily_stats['failed_requests'],
				);

				// Process worker performance.
				if ( isset( $daily_stats['workers'] ) ) {
					foreach ( $daily_stats['workers'] as $worker_name => $worker_stats ) {
						if ( ! isset( $analytics_data['worker_performance'][ $worker_name ] ) ) {
							$analytics_data['worker_performance'][ $worker_name ] = array(
								'requests' => 0,
								'success' => 0,
								'failed' => 0,
								'total_response_time' => 0,
							);
						}

						$analytics_data['worker_performance'][ $worker_name ]['requests'] += $worker_stats['requests'];
						$analytics_data['worker_performance'][ $worker_name ]['success'] += $worker_stats['success'];
						$analytics_data['worker_performance'][ $worker_name ]['failed'] += $worker_stats['failed'];
						$analytics_data['worker_performance'][ $worker_name ]['total_response_time'] += $worker_stats['total_response_time'];
					}
				}
			}
		}

		// Calculate derived metrics.
		$analytics_data['unique_users'] = count( array_unique( $analytics_data['unique_users'] ) );
		$analytics_data['success_rate'] = $analytics_data['total_requests'] > 0 
			? round( ( $analytics_data['successful_requests'] / $analytics_data['total_requests'] ) * 100, 1 )
			: 0;
		$analytics_data['daily_average'] = round( $analytics_data['total_requests'] / max( $period_days, 1 ), 1 );

		// Calculate average response times for workers.
		foreach ( $analytics_data['worker_performance'] as &$worker_data ) {
			$worker_data['avg_response_time'] = $worker_data['requests'] > 0
				? round( $worker_data['total_response_time'] / $worker_data['requests'], 2 )
				: 0;
			$worker_data['success_rate'] = $worker_data['requests'] > 0
				? round( ( $worker_data['success'] / $worker_data['requests'] ) * 100, 1 )
				: 0;
		}

		// Sort dates for charts.
		ksort( $analytics_data['daily_data'] );

		return $analytics_data;
	}

	/**
	 * Get worker-specific analytics data.
	 * 
	 * @since 2.1.0
	 * @param string $worker_name Worker name.
	 * @param int    $period_days Period in days to fetch data for.
	 * @return array Worker analytics data.
	 */
	public function get_worker_analytics( $worker_name, $period_days = 30 ) {
		$stats = get_option( 'ai_faq_usage_stats', array() );
		$start_date = date( 'Y-m-d', strtotime( "-{$period_days} days" ) );

		$worker_data = array(
			'name' => $worker_name,
			'total_requests' => 0,
			'successful_requests' => 0,
			'failed_requests' => 0,
			'avg_response_time' => 0,
			'success_rate' => 0,
			'daily_data' => array(),
		);

		$total_response_time = 0;

		// Process daily statistics.
		foreach ( $stats as $date => $daily_stats ) {
			if ( $date >= $start_date && isset( $daily_stats['workers'][ $worker_name ] ) ) {
				$worker_stats = $daily_stats['workers'][ $worker_name ];
				
				$worker_data['total_requests'] += $worker_stats['requests'];
				$worker_data['successful_requests'] += $worker_stats['success'];
				$worker_data['failed_requests'] += $worker_stats['failed'];
				$total_response_time += $worker_stats['total_response_time'];

				// Add daily data for charts.
				$worker_data['daily_data'][ $date ] = array(
					'total' => $worker_stats['requests'],
					'success' => $worker_stats['success'],
					'failed' => $worker_stats['failed'],
					'avg_response_time' => $worker_stats['requests'] > 0 
						? round( $worker_stats['total_response_time'] / $worker_stats['requests'], 2 )
						: 0,
				);
			}
		}

		// Calculate derived metrics.
		$worker_data['success_rate'] = $worker_data['total_requests'] > 0 
			? round( ( $worker_data['successful_requests'] / $worker_data['total_requests'] ) * 100, 1 )
			: 0;
		$worker_data['avg_response_time'] = $worker_data['total_requests'] > 0
			? round( $total_response_time / $worker_data['total_requests'], 2 )
			: 0;

		// Sort dates for charts.
		ksort( $worker_data['daily_data'] );

		return $worker_data;
	}

	/**
	 * Get top usage patterns.
	 * 
	 * @since 2.1.0
	 * @param int $period_days Period in days to fetch data for.
	 * @param int $limit Number of top items to return.
	 * @return array Top usage data.
	 */
	public function get_usage_patterns( $period_days = 30, $limit = 5 ) {
		$analytics_data = $this->get_analytics_data( $period_days );
		
		// Get top workers by usage.
		$top_workers = array();
		foreach ( $analytics_data['worker_performance'] as $worker_name => $worker_data ) {
			$top_workers[ $worker_name ] = $worker_data['requests'];
		}
		arsort( $top_workers );
		$top_workers = array_slice( $top_workers, 0, $limit, true );
		
		// Get peak usage days.
		$daily_usage = array();
		foreach ( $analytics_data['daily_data'] as $date => $data ) {
			$daily_usage[ $date ] = $data['total'];
		}
		arsort( $daily_usage );
		$peak_days = array_slice( $daily_usage, 0, $limit, true );
		
		return array(
			'top_workers' => $top_workers,
			'peak_days' => $peak_days,
		);
	}

	/**
	 * Reset analytics data.
	 * 
	 * @since 2.1.0
	 * @param bool $confirm Confirmation to prevent accidental resets.
	 * @return bool Whether the reset was successful.
	 */
	public function reset_analytics( $confirm = false ) {
		if ( ! $confirm ) {
			return false;
		}
		
		return delete_option( 'ai_faq_usage_stats' );
	}

	/**
	 * Cleanup old analytics data.
	 * 
	 * @since 2.1.0
	 * @return int Number of days removed.
	 */
	public function cleanup_old_analytics() {
		$stats = get_option( 'ai_faq_usage_stats', array() );
		$cutoff_date = date( 'Y-m-d', strtotime( '-' . $this->retention_days . ' days' ) );
		$initial_count = count( $stats );
		
		$stats = array_filter( $stats, function( $key ) use ( $cutoff_date ) {
			return $key >= $cutoff_date;
		}, ARRAY_FILTER_USE_KEY );
		
		if ( count( $stats ) !== $initial_count ) {
			update_option( 'ai_faq_usage_stats', $stats );
		}
		
		return $initial_count - count( $stats );
	}

	/**
	 * Set analytics retention period.
	 * 
	 * @since 2.1.0
	 * @param int $days Number of days to retain analytics data.
	 * @return bool Whether the setting was updated.
	 */
	public function set_retention_period( $days ) {
		$days = max( 1, min( 365, intval( $days ) ) );
		
		if ( $days !== $this->retention_days ) {
			$this->retention_days = $days;
			
			// Update settings in database.
			$options = get_option( 'ai_faq_gen_options', array() );
			$options['analytics_retention_days'] = $this->retention_days;
			
			return update_option( 'ai_faq_gen_options', $options );
		}
		
		return false;
	}
}