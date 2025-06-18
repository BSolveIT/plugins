<?php
/**
 * Admin analytics management class for 365i AI FAQ Generator.
 * 
 * This class handles analytics data collection, processing, and visualization.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Admin
 * @since 2.1.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin analytics management class.
 * 
 * @since 2.1.0
 */
class AI_FAQ_Admin_Analytics {

	/**
	 * Initialize the analytics component.
	 * 
	 * No direct hooks needed as this is used by other components.
	 * 
	 * @since 2.1.0
	 */
	public function init() {
		// This class is primarily used by other components rather than hooking directly.
	}

	/**
	 * Get analytics data for the specified period.
	 * 
	 * @since 2.1.0
	 * @param int $period_days Number of days to include in the analytics.
	 * @return array Analytics data.
	 */
	public function get_analytics_data( $period_days = 30 ) {
		// Get usage statistics.
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
				$analytics_data['total_requests'] += isset( $daily_stats['total_requests'] ) ? $daily_stats['total_requests'] : 0;
				$analytics_data['successful_requests'] += isset( $daily_stats['successful_requests'] ) ? $daily_stats['successful_requests'] : 0;
				$analytics_data['failed_requests'] += isset( $daily_stats['failed_requests'] ) ? $daily_stats['failed_requests'] : 0;
				
				// Merge unique users (already hashed).
				if ( isset( $daily_stats['unique_ips'] ) && is_array( $daily_stats['unique_ips'] ) ) {
					$analytics_data['unique_users'] = array_merge( 
						$analytics_data['unique_users'], 
						array_keys( $daily_stats['unique_ips'] ) 
					);
				}

				// Add daily data for charts.
				$analytics_data['daily_data'][ $date ] = array(
					'total' => isset( $daily_stats['total_requests'] ) ? $daily_stats['total_requests'] : 0,
					'success' => isset( $daily_stats['successful_requests'] ) ? $daily_stats['successful_requests'] : 0,
					'failed' => isset( $daily_stats['failed_requests'] ) ? $daily_stats['failed_requests'] : 0,
				);

				// Process worker performance.
				if ( isset( $daily_stats['workers'] ) && is_array( $daily_stats['workers'] ) ) {
					foreach ( $daily_stats['workers'] as $worker_name => $worker_stats ) {
						if ( ! isset( $analytics_data['worker_performance'][ $worker_name ] ) ) {
							$analytics_data['worker_performance'][ $worker_name ] = array(
								'requests' => 0,
								'success' => 0,
								'failed' => 0,
								'total_response_time' => 0,
							);
						}

						$analytics_data['worker_performance'][ $worker_name ]['requests'] += isset( $worker_stats['requests'] ) ? $worker_stats['requests'] : 0;
						$analytics_data['worker_performance'][ $worker_name ]['success'] += isset( $worker_stats['success'] ) ? $worker_stats['success'] : 0;
						$analytics_data['worker_performance'][ $worker_name ]['failed'] += isset( $worker_stats['failed'] ) ? $worker_stats['failed'] : 0;
						$analytics_data['worker_performance'][ $worker_name ]['total_response_time'] += isset( $worker_stats['total_response_time'] ) ? $worker_stats['total_response_time'] : 0;
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

		// Format data for charts
		$analytics_data = $this->format_chart_data( $analytics_data );
		
		// Add recent activity data
		$analytics_data = $this->add_recent_activity_data( $analytics_data );
		
		return $analytics_data;
	}
	
	/**
	 * Format analytics data for chart display.
	 *
	 * @since 2.1.0
	 * @param array $analytics_data Raw analytics data.
	 * @return array Formatted analytics data for charts.
	 */
	private function format_chart_data( $analytics_data ) {
		// Prepare data structure for charts
		$formatted_data = $analytics_data;
		
		// Format daily data for charts
		$chart_labels = array();
		$chart_total = array();
		$chart_success = array();
		$chart_failed = array();
		
		// Sort dates chronologically
		ksort( $analytics_data['daily_data'] );
		
		foreach ( $analytics_data['daily_data'] as $date => $data ) {
			$chart_labels[] = date( 'M j', strtotime( $date ) );
			$chart_total[] = $data['total'];
			$chart_success[] = $data['success'];
			$chart_failed[] = $data['failed'];
		}
		
		$formatted_data['daily_chart'] = array(
			'labels' => $chart_labels,
			'total' => $chart_total,
			'success' => $chart_success,
			'failed' => $chart_failed
		);
		
		// Format worker performance for charts
		$worker_names = array();
		$worker_requests = array();
		$worker_success_rates = array();
		$worker_response_times = array();
		
		// Generate human-readable names for display
		$worker_display_names = array(
			'question_generator' => 'Question Generator',
			'answer_generator' => 'Answer Generator',
			'faq_enhancer' => 'FAQ Enhancer',
			'seo_analyzer' => 'SEO Analyzer',
			'faq_extractor' => 'FAQ Extractor',
			'topic_generator' => 'Topic Generator',
		);
		
		foreach ( $analytics_data['worker_performance'] as $worker_name => $data ) {
			$display_name = isset( $worker_display_names[ $worker_name ] ) ? 
				$worker_display_names[ $worker_name ] : $worker_name;
				
			$worker_names[] = $display_name;
			$worker_requests[] = $data['requests'];
			$worker_success_rates[] = $data['success_rate'];
			$worker_response_times[] = $data['avg_response_time'];
		}
		
		$formatted_data['worker_chart'] = array(
			'names' => $worker_names,
			'requests' => $worker_requests,
			'success_rates' => $worker_success_rates,
			'response_times' => $worker_response_times
		);
		
		return $formatted_data;
	}
	
	/**
	 * Add recent activity data to analytics.
	 *
	 * @since 2.1.0
	 * @param array $analytics_data Analytics data.
	 * @return array Analytics data with activity information.
	 */
	private function add_recent_activity_data( $analytics_data ) {
		// Get recent activity
		$activity_log = get_option( 'ai_faq_activity_log', array() );
		$recent_activity = array_slice( $activity_log, 0, 10 );
		
		// Format activity data for display
		$formatted_activity = array();
		foreach ( $recent_activity as $activity ) {
			$formatted_activity[] = array(
				'type' => $activity['activity_type'],
				'details' => $activity['details'],
				'time_ago' => human_time_diff( $activity['timestamp'], current_time( 'timestamp' ) ),
				'timestamp' => $activity['timestamp']
			);
		}
		
		$analytics_data['recent_activity'] = $formatted_activity;
		
		// Get recent violations from security component
		$security = new AI_FAQ_Admin_Security();
		$violations_data = $security->get_violations_data( 24 ); // Last 24 hours
		
		$analytics_data['violations'] = array(
			'total_24h' => $violations_data['summary']['total_violations'],
			'unique_ips' => $violations_data['summary']['unique_ips'],
			'recent' => array_slice( $violations_data['violations'], 0, 5 )
		);
		
		return $analytics_data;
	}
	
	/**
	 * Record usage event in analytics.
	 *
	 * @since 2.1.0
	 * @param string $worker_name Worker name.
	 * @param bool   $success Whether the request was successful.
	 * @param int    $response_time Response time in milliseconds.
	 * @param string $ip_address User IP address.
	 * @return bool Whether the event was recorded.
	 */
	public function record_usage_event( $worker_name, $success, $response_time, $ip_address ) {
		// Get current date (YYYY-MM-DD)
		$date = date( 'Y-m-d' );
		
		// Get current usage stats
		$usage_stats = get_option( 'ai_faq_usage_stats', array() );
		
		// Initialize the date entry if it doesn't exist
		if ( ! isset( $usage_stats[ $date ] ) ) {
			$usage_stats[ $date ] = array(
				'total_requests' => 0,
				'successful_requests' => 0,
				'failed_requests' => 0,
				'unique_ips' => array(),
				'workers' => array()
			);
		}
		
		// Update the statistics
		$usage_stats[ $date ]['total_requests']++;
		
		if ( $success ) {
			$usage_stats[ $date ]['successful_requests']++;
		} else {
			$usage_stats[ $date ]['failed_requests']++;
		}
		
		// Track unique IPs (using hash for privacy)
		$ip_hash = md5( $ip_address );
		$usage_stats[ $date ]['unique_ips'][ $ip_hash ] = true;
		
		// Track worker usage
		if ( ! isset( $usage_stats[ $date ]['workers'][ $worker_name ] ) ) {
			$usage_stats[ $date ]['workers'][ $worker_name ] = array(
				'requests' => 0,
				'success' => 0,
				'failed' => 0,
				'total_response_time' => 0
			);
		}
		
		$usage_stats[ $date ]['workers'][ $worker_name ]['requests']++;
		
		if ( $success ) {
			$usage_stats[ $date ]['workers'][ $worker_name ]['success']++;
		} else {
			$usage_stats[ $date ]['workers'][ $worker_name ]['failed']++;
		}
		
		$usage_stats[ $date ]['workers'][ $worker_name ]['total_response_time'] += $response_time;
		
		// Save the updated stats
		return update_option( 'ai_faq_usage_stats', $usage_stats );
	}
}