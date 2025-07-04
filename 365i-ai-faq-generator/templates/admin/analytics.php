<?php
/**
 * Admin analytics dashboard template.
 *
 * @package AI_FAQ_Generator
 * @subpackage Admin
 * @since 2.0.2
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get analytics data
$days = isset( $_GET['period'] ) ? intval( $_GET['period'] ) : 30;
$days = in_array( $days, array( 7, 30, 90, 365 ), true ) ? $days : 30;

$stats = get_option( 'ai_faq_usage_stats', array() );
$start_date = date( 'Y-m-d', strtotime( "-{$days} days" ) );

// Process stats for display
$analytics_data = array(
	'total_requests' => 0,
	'successful_requests' => 0,
	'failed_requests' => 0,
	'unique_users' => array(),
	'daily_data' => array(),
	'worker_performance' => array(),
);

// Initialize dates for the chart (to ensure all dates are included even with no data)
$date_range = array();
$current = strtotime( $start_date );
$end = strtotime( 'today' );
while ( $current <= $end ) {
	$date_key = date( 'Y-m-d', $current );
	$date_range[ $date_key ] = array(
		'total' => 0,
		'success' => 0,
		'failed' => 0,
	);
	$current = strtotime( '+1 day', $current );
}

// Process daily statistics
foreach ( $stats as $date => $daily_stats ) {
	if ( $date >= $start_date ) {
		$analytics_data['total_requests'] += isset( $daily_stats['total_requests'] ) ? $daily_stats['total_requests'] : 0;
		$analytics_data['successful_requests'] += isset( $daily_stats['successful_requests'] ) ? $daily_stats['successful_requests'] : 0;
		$analytics_data['failed_requests'] += isset( $daily_stats['failed_requests'] ) ? $daily_stats['failed_requests'] : 0;
		
		// Merge unique users (already hashed)
		if ( isset( $daily_stats['unique_ips'] ) && is_array( $daily_stats['unique_ips'] ) ) {
			$analytics_data['unique_users'] = array_merge( 
				$analytics_data['unique_users'], 
				array_keys( $daily_stats['unique_ips'] ) 
			);
		}

		// Add daily data for charts
		$date_range[ $date ] = array(
			'total' => isset( $daily_stats['total_requests'] ) ? $daily_stats['total_requests'] : 0,
			'success' => isset( $daily_stats['successful_requests'] ) ? $daily_stats['successful_requests'] : 0,
			'failed' => isset( $daily_stats['failed_requests'] ) ? $daily_stats['failed_requests'] : 0,
		);

		// Process worker performance
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

// Calculate derived metrics
$analytics_data['unique_users'] = count( array_unique( $analytics_data['unique_users'] ) );
$analytics_data['success_rate'] = $analytics_data['total_requests'] > 0 
	? round( ( $analytics_data['successful_requests'] / $analytics_data['total_requests'] ) * 100, 1 )
	: 0;
$analytics_data['daily_average'] = $days > 0 
	? round( $analytics_data['total_requests'] / $days, 1 )
	: 0;

// Calculate average response times for workers
foreach ( $analytics_data['worker_performance'] as &$worker_data ) {
	$worker_data['avg_response_time'] = $worker_data['requests'] > 0
		? round( $worker_data['total_response_time'] / $worker_data['requests'], 2 )
		: 0;
	$worker_data['success_rate'] = $worker_data['requests'] > 0
		? round( ( $worker_data['success'] / $worker_data['requests'] ) * 100, 1 )
		: 0;
}

// Sort worker performance by request count (highest first)
uasort( $analytics_data['worker_performance'], function( $a, $b ) {
	return $b['requests'] - $a['requests'];
});

// Get recent activity
$activity_log = get_option( 'ai_faq_activity_log', array() );
$recent_activity = array_slice( $activity_log, 0, 10 );

// Get recent violations
$violations = get_option( 'ai_faq_violations_log', array() );
$recent_violations = array_filter( $violations, function( $violation ) use ( $start_date ) {
	return $violation['timestamp'] > strtotime( $start_date );
});
$recent_violations = array_slice( $recent_violations, 0, 10 );

// Test summary if available
$test_summary = get_option( 'ai_faq_test_summary', array() );

// Prepare chart data
$daily_chart_data = array(
	'labels' => array_keys( $date_range ),
	'total' => array_column( $date_range, 'total' ),
	'success' => array_column( $date_range, 'success' ),
	'failed' => array_column( $date_range, 'failed' ),
);

// Format dates for display
$daily_chart_data['labels'] = array_map( function( $date ) {
	return date( 'M j', strtotime( $date ) );
}, $daily_chart_data['labels'] );

// Prepare worker performance chart data
$worker_names = array_keys( $analytics_data['worker_performance'] );
$worker_requests = array_column( $analytics_data['worker_performance'], 'requests' );
$worker_success_rates = array_column( $analytics_data['worker_performance'], 'success_rate' );
$worker_response_times = array_column( $analytics_data['worker_performance'], 'avg_response_time' );

// Generate human-readable names for display
$worker_display_names = array(
	'question_generator' => 'Question Generator',
	'answer_generator' => 'Answer Generator',
	'faq_enhancer' => 'FAQ Enhancer',
	'seo_analyzer' => 'SEO Analyzer',
	'url_faq_generator' => 'URL FAQ Generator',
);

$worker_chart_labels = array_map( function( $worker ) use ( $worker_display_names ) {
	return isset( $worker_display_names[ $worker ] ) ? $worker_display_names[ $worker ] : $worker;
}, $worker_names );

// Include the header
require_once AI_FAQ_GEN_DIR . 'templates/partials/header.php';
?>

<div class="ai-faq-analytics-dashboard">
	<div class="ai-faq-page-header">
		<h1><?php esc_html_e( 'Analytics Dashboard', '365i-ai-faq-generator' ); ?></h1>
		<div class="ai-faq-period-selector">
			<form method="get" action="">
				<input type="hidden" name="page" value="ai-faq-generator-analytics">
				<select name="period" id="period-selector" onchange="this.form.submit()">
					<option value="7" <?php selected( $days, 7 ); ?>><?php esc_html_e( 'Last 7 days', '365i-ai-faq-generator' ); ?></option>
					<option value="30" <?php selected( $days, 30 ); ?>><?php esc_html_e( 'Last 30 days', '365i-ai-faq-generator' ); ?></option>
					<option value="90" <?php selected( $days, 90 ); ?>><?php esc_html_e( 'Last 90 days', '365i-ai-faq-generator' ); ?></option>
					<option value="365" <?php selected( $days, 365 ); ?>><?php esc_html_e( 'Last year', '365i-ai-faq-generator' ); ?></option>
				</select>
			</form>
		</div>
	</div>

	<!-- Key Metrics -->
	<div class="ai-faq-metrics-grid">
		<div class="ai-faq-metric-card">
			<div class="ai-faq-metric-icon">
				<span class="dashicons dashicons-chart-bar"></span>
			</div>
			<div class="ai-faq-metric-content">
				<h3><?php esc_html_e( 'Total Requests', '365i-ai-faq-generator' ); ?></h3>
				<div class="ai-faq-metric-value"><?php echo esc_html( number_format( $analytics_data['total_requests'] ) ); ?></div>
				<div class="ai-faq-metric-caption"><?php echo esc_html( sprintf( __( 'Avg: %s / day', '365i-ai-faq-generator' ), number_format( $analytics_data['daily_average'], 1 ) ) ); ?></div>
			</div>
		</div>

		<div class="ai-faq-metric-card">
			<div class="ai-faq-metric-icon">
				<span class="dashicons dashicons-yes-alt"></span>
			</div>
			<div class="ai-faq-metric-content">
				<h3><?php esc_html_e( 'Success Rate', '365i-ai-faq-generator' ); ?></h3>
				<div class="ai-faq-metric-value"><?php echo esc_html( $analytics_data['success_rate'] ); ?>%</div>
				<div class="ai-faq-metric-caption">
					<?php 
					echo esc_html( sprintf( 
						__( '%s successful, %s failed', '365i-ai-faq-generator' ), 
						number_format( $analytics_data['successful_requests'] ),
						number_format( $analytics_data['failed_requests'] )
					) ); 
					?>
				</div>
			</div>
		</div>

		<div class="ai-faq-metric-card">
			<div class="ai-faq-metric-icon">
				<span class="dashicons dashicons-groups"></span>
			</div>
			<div class="ai-faq-metric-content">
				<h3><?php esc_html_e( 'Unique Users', '365i-ai-faq-generator' ); ?></h3>
				<div class="ai-faq-metric-value"><?php echo esc_html( number_format( $analytics_data['unique_users'] ) ); ?></div>
				<div class="ai-faq-metric-caption"><?php esc_html_e( 'Distinct IP addresses', '365i-ai-faq-generator' ); ?></div>
			</div>
		</div>

		<div class="ai-faq-metric-card">
			<div class="ai-faq-metric-icon">
				<span class="dashicons dashicons-warning"></span>
			</div>
			<div class="ai-faq-metric-content">
				<h3><?php esc_html_e( 'Rate Limit Violations', '365i-ai-faq-generator' ); ?></h3>
				<div class="ai-faq-metric-value"><?php echo esc_html( count( $recent_violations ) ); ?></div>
				<div class="ai-faq-metric-caption">
					<?php echo esc_html( sprintf( __( 'From %d unique IPs', '365i-ai-faq-generator' ), count( array_unique( array_column( $recent_violations, 'ip' ) ) ) ) ); ?>
				</div>
			</div>
		</div>
	</div>

	<!-- Charts Section -->
	<div class="ai-faq-charts-grid">
		<div class="ai-faq-chart-card ai-faq-chart-large">
			<h3><?php esc_html_e( 'Daily Requests', '365i-ai-faq-generator' ); ?></h3>
			<div class="ai-faq-chart-container">
				<canvas id="dailyRequestsChart" width="400" height="200"></canvas>
			</div>
		</div>

		<div class="ai-faq-chart-card">
			<h3><?php esc_html_e( 'Worker Usage', '365i-ai-faq-generator' ); ?></h3>
			<div class="ai-faq-chart-container">
				<canvas id="workerUsageChart" width="200" height="200"></canvas>
			</div>
		</div>

		<div class="ai-faq-chart-card">
			<h3><?php esc_html_e( 'Success Rates', '365i-ai-faq-generator' ); ?></h3>
			<div class="ai-faq-chart-container">
				<canvas id="successRatesChart" width="200" height="200"></canvas>
			</div>
		</div>

		<div class="ai-faq-chart-card ai-faq-chart-large">
			<h3><?php esc_html_e( 'Average Response Time (ms)', '365i-ai-faq-generator' ); ?></h3>
			<div class="ai-faq-chart-container">
				<canvas id="responseTimeChart" width="400" height="200"></canvas>
			</div>
		</div>
	</div>

	<!-- Worker Performance Table -->
	<div class="ai-faq-section">
		<h2><?php esc_html_e( 'Worker Performance', '365i-ai-faq-generator' ); ?></h2>
		<div class="ai-faq-table-container">
			<table class="ai-faq-data-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Worker', '365i-ai-faq-generator' ); ?></th>
						<th><?php esc_html_e( 'Requests', '365i-ai-faq-generator' ); ?></th>
						<th><?php esc_html_e( 'Success Rate', '365i-ai-faq-generator' ); ?></th>
						<th><?php esc_html_e( 'Avg Response Time', '365i-ai-faq-generator' ); ?></th>
						<th><?php esc_html_e( 'Status', '365i-ai-faq-generator' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $analytics_data['worker_performance'] as $worker_name => $worker_data ) : 
						$status_class = $worker_data['success_rate'] >= 95 ? 'healthy' : ( $worker_data['success_rate'] >= 80 ? 'warning' : 'error' );
						$status_text = $worker_data['success_rate'] >= 95 ? __( 'Healthy', '365i-ai-faq-generator' ) : ( $worker_data['success_rate'] >= 80 ? __( 'Warning', '365i-ai-faq-generator' ) : __( 'Critical', '365i-ai-faq-generator' ) );
						$display_name = isset( $worker_display_names[ $worker_name ] ) ? $worker_display_names[ $worker_name ] : $worker_name;
					?>
					<tr>
						<td><?php echo esc_html( $display_name ); ?></td>
						<td><?php echo esc_html( number_format( $worker_data['requests'] ) ); ?></td>
						<td><?php echo esc_html( $worker_data['success_rate'] ); ?>%</td>
						<td><?php echo esc_html( $worker_data['avg_response_time'] ); ?> ms</td>
						<td><span class="ai-faq-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_text ); ?></span></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Recent Activity and Violations -->
	<div class="ai-faq-two-column">
		<div class="ai-faq-section">
			<h2><?php esc_html_e( 'Recent Activity', '365i-ai-faq-generator' ); ?></h2>
			<?php if ( empty( $recent_activity ) ) : ?>
				<p class="ai-faq-empty-state"><?php esc_html_e( 'No recent activity to display.', '365i-ai-faq-generator' ); ?></p>
			<?php else : ?>
				<div class="ai-faq-activity-list">
					<?php foreach ( $recent_activity as $activity ) : ?>
						<div class="ai-faq-activity-item">
							<div class="ai-faq-activity-icon">
								<?php 
								$icon = 'dashicons-info';
								switch ( $activity['activity_type'] ) {
									case 'faq_generation':
										$icon = 'dashicons-editor-help';
										break;
									case 'settings_change':
										$icon = 'dashicons-admin-settings';
										break;
									case 'worker_test':
										$icon = 'dashicons-performance';
										break;
								}
								?>
								<span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
							</div>
							<div class="ai-faq-activity-content">
								<div class="ai-faq-activity-title">
									<?php 
									switch ( $activity['activity_type'] ) {
										case 'faq_generation':
											echo esc_html( sprintf( __( 'FAQ Generation (%d questions)', '365i-ai-faq-generator' ), $activity['details']['question_count'] ?? 0 ) );
											break;
										case 'settings_change':
											echo esc_html( __( 'Settings Changed', '365i-ai-faq-generator' ) );
											break;
										case 'worker_test':
											echo esc_html( sprintf( __( 'Worker Test: %s', '365i-ai-faq-generator' ), $activity['details']['worker'] ?? '' ) );
											break;
										default:
											echo esc_html( $activity['activity_type'] );
											break;
									}
									?>
								</div>
								<div class="ai-faq-activity-meta">
									<?php echo esc_html( human_time_diff( $activity['timestamp'], current_time( 'timestamp' ) ) . ' ' . __( 'ago', '365i-ai-faq-generator' ) ); ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="ai-faq-section">
			<h2><?php esc_html_e( 'Recent Violations', '365i-ai-faq-generator' ); ?></h2>
			<?php if ( empty( $recent_violations ) ) : ?>
				<p class="ai-faq-empty-state"><?php esc_html_e( 'No violations detected during this period.', '365i-ai-faq-generator' ); ?></p>
			<?php else : ?>
				<div class="ai-faq-table-container">
					<table class="ai-faq-data-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'IP Address', '365i-ai-faq-generator' ); ?></th>
								<th><?php esc_html_e( 'Worker', '365i-ai-faq-generator' ); ?></th>
								<th><?php esc_html_e( 'Requests', '365i-ai-faq-generator' ); ?></th>
								<th><?php esc_html_e( 'Limit', '365i-ai-faq-generator' ); ?></th>
								<th><?php esc_html_e( 'When', '365i-ai-faq-generator' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( array_slice( $recent_violations, 0, 5 ) as $violation ) : 
								$display_name = isset( $worker_display_names[ $violation['worker'] ] ) ? $worker_display_names[ $violation['worker'] ] : $violation['worker'];
							?>
							<tr>
								<td><?php echo esc_html( $violation['ip'] ); ?></td>
								<td><?php echo esc_html( $display_name ); ?></td>
								<td><?php echo esc_html( $violation['requests_count'] ); ?></td>
								<td><?php echo esc_html( $violation['limit'] ); ?></td>
								<td><?php echo esc_html( human_time_diff( $violation['timestamp'], current_time( 'timestamp' ) ) . ' ' . __( 'ago', '365i-ai-faq-generator' ) ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<?php if ( count( $recent_violations ) > 5 ) : ?>
					<div class="ai-faq-view-more">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-faq-generator-security' ) ); ?>"><?php esc_html_e( 'View all violations', '365i-ai-faq-generator' ); ?></a>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>

	<!-- Enhanced Cloudflare Analytics Section -->
	<div class="ai-faq-section">
		<h2><?php esc_html_e( 'Enhanced Cloudflare Analytics', '365i-ai-faq-generator' ); ?></h2>
		<div class="ai-faq-cloudflare-analytics">
			<p><?php esc_html_e( 'Real-time comprehensive analytics from Cloudflare\'s GraphQL Analytics API. View detailed worker performance, CPU metrics (P50/P95/P99), KV storage usage, and data transfer statistics.', '365i-ai-faq-generator' ); ?></p>
			
			<div class="ai-faq-analytics-controls">
				<div class="ai-faq-time-selector">
					<label for="cloudflare-analytics-period"><?php esc_html_e( 'Time Range:', '365i-ai-faq-generator' ); ?></label>
					<select id="cloudflare-analytics-period" data-nonce="<?php echo esc_attr( wp_create_nonce( 'ai_faq_gen_nonce' ) ); ?>">
						<option value="1"><?php esc_html_e( 'Last 24 Hours', '365i-ai-faq-generator' ); ?></option>
						<option value="7" selected><?php esc_html_e( 'Last 7 Days', '365i-ai-faq-generator' ); ?></option>
						<option value="30"><?php esc_html_e( 'Last 30 Days', '365i-ai-faq-generator' ); ?></option>
					</select>
					<button type="button" id="refresh-cloudflare-analytics" class="button">
						<span class="dashicons dashicons-update"></span>
						<?php esc_html_e( 'Refresh', '365i-ai-faq-generator' ); ?>
					</button>
					<span class="spinner" style="float: none;"></span>
				</div>
			</div>
			
			<div id="cloudflare-analytics-content" class="ai-faq-analytics-content">
				<div class="ai-faq-analytics-loading">
					<span class="spinner is-active"></span>
					<p><?php esc_html_e( 'Loading analytics data...', '365i-ai-faq-generator' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<!-- Test Summary Section -->
	<?php if ( ! empty( $test_summary ) ) : ?>
	<div class="ai-faq-section">
		<h2><?php esc_html_e( 'Test Results', '365i-ai-faq-generator' ); ?></h2>
		<div class="ai-faq-test-summary">
			<div class="ai-faq-test-metrics">
				<div class="ai-faq-metric-card">
					<div class="ai-faq-metric-content">
						<h3><?php esc_html_e( 'Total Tests', '365i-ai-faq-generator' ); ?></h3>
						<div class="ai-faq-metric-value"><?php echo esc_html( $test_summary['total'] ?? 0 ); ?></div>
					</div>
				</div>
				<div class="ai-faq-metric-card">
					<div class="ai-faq-metric-content">
						<h3><?php esc_html_e( 'Success Rate', '365i-ai-faq-generator' ); ?></h3>
						<div class="ai-faq-metric-value">
							<?php 
							$success_rate = isset( $test_summary['total'] ) && $test_summary['total'] > 0
								? round( ( $test_summary['successful'] / $test_summary['total'] ) * 100, 1 )
								: 0;
							echo esc_html( $success_rate );
							?>%
						</div>
					</div>
				</div>
				<div class="ai-faq-metric-card">
					<div class="ai-faq-metric-content">
						<h3><?php esc_html_e( 'Duration', '365i-ai-faq-generator' ); ?></h3>
						<div class="ai-faq-metric-value"><?php echo esc_html( round( $test_summary['duration'] ?? 0, 1 ) ); ?>s</div>
					</div>
				</div>
			</div>
			
			<?php if ( ! empty( $test_summary['workerStats'] ) ) : ?>
				<div class="ai-faq-table-container">
					<h3><?php esc_html_e( 'Worker Test Results', '365i-ai-faq-generator' ); ?></h3>
					<table class="ai-faq-data-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Worker', '365i-ai-faq-generator' ); ?></th>
								<th><?php esc_html_e( 'Requests', '365i-ai-faq-generator' ); ?></th>
								<th><?php esc_html_e( 'Success Rate', '365i-ai-faq-generator' ); ?></th>
								<th><?php esc_html_e( 'Avg Response Time', '365i-ai-faq-generator' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $test_summary['workerStats'] as $worker_name => $worker_stats ) : 
								if ( $worker_stats['total'] <= 0 ) continue;
								$display_name = isset( $worker_display_names[ $worker_name ] ) ? $worker_display_names[ $worker_name ] : $worker_name;
								$success_rate = $worker_stats['total'] > 0 
									? round( ( $worker_stats['successful'] / $worker_stats['total'] ) * 100, 1 )
									: 0;
							?>
							<tr>
								<td><?php echo esc_html( $display_name ); ?></td>
								<td><?php echo esc_html( $worker_stats['total'] ); ?></td>
								<td><?php echo esc_html( $success_rate ); ?>%</td>
								<td><?php echo esc_html( round( $worker_stats['avgResponseTime'] ?? 0, 2 ) ); ?> ms</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
			
			<div class="ai-faq-button-group">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-faq-generator&action=run-tests' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Run Tests Again', '365i-ai-faq-generator' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-faq-generator&action=view-test-logs' ) ); ?>" class="button">
					<?php esc_html_e( 'View Test Logs', '365i-ai-faq-generator' ); ?>
				</a>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<!-- Help & Documentation -->
	<div class="ai-faq-section">
		<h2>
			<span class="dashicons dashicons-editor-help"></span>
			<?php esc_html_e( 'Help & Documentation', '365i-ai-faq-generator' ); ?>
		</h2>
		
		<div class="help-grid">
			<div class="help-card">
				<h4><?php esc_html_e( 'Worker Setup Guide', '365i-ai-faq-generator' ); ?></h4>
				<p><?php esc_html_e( 'Learn how to configure your Cloudflare workers and KV namespaces for optimal performance. This guide walks you through the complete setup process with step-by-step instructions.', '365i-ai-faq-generator' ); ?></p>
				<button type="button" class="button button-secondary ai-faq-doc-button" data-doc-type="setup_guide">
					<span class="dashicons dashicons-media-text"></span>
					<?php esc_html_e( 'View Guide', '365i-ai-faq-generator' ); ?>
				</button>
			</div>
			
			<div class="help-card">
				<h4><?php esc_html_e( 'Troubleshooting', '365i-ai-faq-generator' ); ?></h4>
				<p><?php esc_html_e( 'Common issues and solutions for worker connectivity problems. Find answers to frequently encountered setup issues and learn how to diagnose connection failures.', '365i-ai-faq-generator' ); ?></p>
				<button type="button" class="button button-secondary ai-faq-doc-button" data-doc-type="troubleshooting">
					<span class="dashicons dashicons-sos"></span>
					<?php esc_html_e( 'Get Help', '365i-ai-faq-generator' ); ?>
				</button>
			</div>
			
			<div class="help-card">
				<h4><?php esc_html_e( 'API Reference', '365i-ai-faq-generator' ); ?></h4>
				<p><?php esc_html_e( 'Complete API documentation for all worker endpoints and parameters. This technical reference provides detailed information about request formats, response structures, and authentication.', '365i-ai-faq-generator' ); ?></p>
				<button type="button" class="button button-secondary ai-faq-doc-button" data-doc-type="api_reference">
					<span class="dashicons dashicons-editor-code"></span>
					<?php esc_html_e( 'View API Docs', '365i-ai-faq-generator' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>

<script>
// Chart.js initialization code
jQuery(document).ready(function($) {
	// Parse PHP data for charts
	const dailyChartData = <?php echo wp_json_encode( $daily_chart_data ); ?>;
	const workerNames = <?php echo wp_json_encode( $worker_chart_labels ); ?>;
	const workerRequests = <?php echo wp_json_encode( $worker_requests ); ?>;
	const workerSuccessRates = <?php echo wp_json_encode( $worker_success_rates ); ?>;
	const workerResponseTimes = <?php echo wp_json_encode( $worker_response_times ); ?>;
	
	// Chart colors
	const colors = {
		blue: 'rgba(54, 162, 235, 0.8)',
		blueLight: 'rgba(54, 162, 235, 0.2)',
		green: 'rgba(75, 192, 192, 0.8)',
		greenLight: 'rgba(75, 192, 192, 0.2)',
		red: 'rgba(255, 99, 132, 0.8)',
		redLight: 'rgba(255, 99, 132, 0.2)',
		orange: 'rgba(255, 159, 64, 0.8)',
		orangeLight: 'rgba(255, 159, 64, 0.2)',
		purple: 'rgba(153, 102, 255, 0.8)',
		purpleLight: 'rgba(153, 102, 255, 0.2)',
		yellow: 'rgba(255, 205, 86, 0.8)',
		yellowLight: 'rgba(255, 205, 86, 0.2)',
	};
	
	// Helper function to generate random color
	function getRandomColor(index) {
		const colorKeys = Object.keys(colors);
		return colors[colorKeys[index % colorKeys.length]];
	}
	
	// Daily Requests Chart
	if (document.getElementById('dailyRequestsChart')) {
		const ctx = document.getElementById('dailyRequestsChart').getContext('2d');
		new Chart(ctx, {
			type: 'line',
			data: {
				labels: dailyChartData.labels,
				datasets: [{
					label: 'Total',
					data: dailyChartData.total,
					backgroundColor: colors.blueLight,
					borderColor: colors.blue,
					borderWidth: 2,
					tension: 0.1
				}, {
					label: 'Successful',
					data: dailyChartData.success,
					backgroundColor: colors.greenLight,
					borderColor: colors.green,
					borderWidth: 2,
					tension: 0.1
				}, {
					label: 'Failed',
					data: dailyChartData.failed,
					backgroundColor: colors.redLight,
					borderColor: colors.red,
					borderWidth: 2,
					tension: 0.1
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							precision: 0
						}
					}
				}
			}
		});
	}
	
	// Worker Usage Chart
	if (document.getElementById('workerUsageChart') && workerNames.length > 0) {
		const ctx = document.getElementById('workerUsageChart').getContext('2d');
		new Chart(ctx, {
			type: 'doughnut',
			data: {
				labels: workerNames,
				datasets: [{
					data: workerRequests,
					backgroundColor: workerNames.map((_, i) => getRandomColor(i)),
					borderWidth: 1
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						position: 'right',
						labels: {
							boxWidth: 12
						}
					}
				}
			}
		});
	}
	
	// Success Rates Chart
	if (document.getElementById('successRatesChart') && workerNames.length > 0) {
		const ctx = document.getElementById('successRatesChart').getContext('2d');
		new Chart(ctx, {
			type: 'bar',
			data: {
				labels: workerNames,
				datasets: [{
					label: 'Success Rate (%)',
					data: workerSuccessRates,
					backgroundColor: workerSuccessRates.map(rate => 
						rate >= 95 ? colors.green : (rate >= 80 ? colors.orange : colors.red)
					),
					borderWidth: 1
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				scales: {
					y: {
						beginAtZero: true,
						max: 100
					}
				},
				plugins: {
					legend: {
						display: false
					}
				}
			}
		});
	}
	
	// Response Time Chart
	if (document.getElementById('responseTimeChart') && workerNames.length > 0) {
		const ctx = document.getElementById('responseTimeChart').getContext('2d');
		new Chart(ctx, {
			type: 'bar',
			data: {
				labels: workerNames,
				datasets: [{
					label: 'Avg Response Time (ms)',
					data: workerResponseTimes,
					backgroundColor: colors.purple,
					borderWidth: 1
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				scales: {
					y: {
						beginAtZero: true
					}
				},
				plugins: {
					legend: {
						display: false
					}
				}
			}
		});
	}
	
	// Enhanced Cloudflare Analytics Manager
	const cloudflareAnalyticsManager = {
		container: $('.ai-faq-cloudflare-analytics'),
		
		init: function() {
			this.loadAnalytics(7); // Auto-load with default 7 days
			this.bindEvents();
		},
		
		bindEvents: function() {
			const self = this;
			
			// Time period onChange
			$('#cloudflare-analytics-period').on('change', function() {
				self.loadAnalytics($(this).val());
			});
			
			// Refresh button
			$('#refresh-cloudflare-analytics').on('click', function() {
				const currentPeriod = $('#cloudflare-analytics-period').val();
				self.loadAnalytics(currentPeriod, true); // Force refresh
			});
		},
		
		loadAnalytics: function(days, forceRefresh = false) {
			const self = this;
			const $content = $('#cloudflare-analytics-content');
			const $spinner = $('#refresh-cloudflare-analytics').next('.spinner');
			const $refreshBtn = $('#refresh-cloudflare-analytics');
			
			$spinner.addClass('is-active');
			$refreshBtn.prop('disabled', true);
			$content.html('<div class="ai-faq-analytics-loading"><span class="spinner is-active"></span><p><?php esc_html_e( 'Loading analytics data...', '365i-ai-faq-generator' ); ?></p></div>');
			
			$.post(ajaxurl, {
				action: 'ai_faq_fetch_cloudflare_stats',
				days: days,
				force_refresh: forceRefresh,
				nonce: $('#cloudflare-analytics-period').data('nonce')
			})
			.done(function(response) {
				if (response.success) {
					self.renderAnalytics(response.data);
					
					// Show success notification that auto-dismisses
					if (forceRefresh) {
						self.showNotification(response.data.message, 'success');
					}
				} else {
					self.showError(response.data || '<?php esc_html_e( 'Failed to load analytics', '365i-ai-faq-generator' ); ?>');
				}
			})
			.fail(function() {
				self.showError('<?php esc_html_e( 'Request failed. Please check your connection.', '365i-ai-faq-generator' ); ?>');
			})
			.always(function() {
				$spinner.removeClass('is-active');
				$refreshBtn.prop('disabled', false);
			});
		},
		
		renderAnalytics: function(data) {
			let html = '<div class="ai-faq-analytics-grid">';
			
			// Summary cards
			html += '<div class="ai-faq-analytics-summary">';
			html += this.createSummaryCard('<?php esc_html_e( 'Total Requests', '365i-ai-faq-generator' ); ?>', data.totals.requests.toLocaleString(), 'dashicons-chart-line');
			html += this.createSummaryCard('<?php esc_html_e( 'Success Rate', '365i-ai-faq-generator' ); ?>', data.success_rate.toFixed(1) + '%', 'dashicons-yes-alt');
			html += this.createSummaryCard('<?php esc_html_e( 'Avg CPU Time', '365i-ai-faq-generator' ); ?>', data.performance_summary.avg_cpu_time + 'ms', 'dashicons-performance');
			html += this.createSummaryCard('<?php esc_html_e( 'Data Transfer', '365i-ai-faq-generator' ); ?>', data.totals.egress_formatted, 'dashicons-download');
			html += '</div>';
			
			// Workers breakdown
			if (data.workers && Object.keys(data.workers).length > 0) {
				html += '<div class="ai-faq-analytics-section">';
				html += '<h3><?php esc_html_e( 'Workers Performance', '365i-ai-faq-generator' ); ?></h3>';
				html += '<table class="widefat">';
				html += '<thead><tr><th><?php esc_html_e( 'Worker', '365i-ai-faq-generator' ); ?></th><th><?php esc_html_e( 'Requests', '365i-ai-faq-generator' ); ?></th><th><?php esc_html_e( 'Errors', '365i-ai-faq-generator' ); ?></th><th><?php esc_html_e( 'Success Rate', '365i-ai-faq-generator' ); ?></th><th><?php esc_html_e( 'CPU P50', '365i-ai-faq-generator' ); ?></th><th><?php esc_html_e( 'CPU P99', '365i-ai-faq-generator' ); ?></th></tr></thead>';
				html += '<tbody>';
				
				for (const [worker, w] of Object.entries(data.workers)) {
					if (!w.error) {
						html += '<tr>';
						html += '<td>' + worker + '</td>';
						html += '<td>' + (w.requests || 0).toLocaleString() + '</td>';
						html += '<td>' + (w.errors || 0).toLocaleString() + '</td>';
						html += '<td>' + (w.success_rate || 0) + '%</td>';
						html += '<td>' + (w.cpu_time_p50 || 0) + 'ms</td>';
						html += '<td>' + (w.cpu_time_p99 || 0) + 'ms</td>';
						html += '</tr>';
					} else {
						html += '<tr><td>' + worker + '</td><td colspan="5" class="error-cell"><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Error:', '365i-ai-faq-generator' ); ?> ' + w.error + '</td></tr>';
					}
				}
				
				html += '</tbody></table>';
				html += '</div>';
			}
			
			// KV Storage
			if (data.kv_storage && Object.keys(data.kv_storage).length > 0) {
				html += '<div class="ai-faq-analytics-section">';
				html += '<h3><?php esc_html_e( 'KV Storage Usage', '365i-ai-faq-generator' ); ?></h3>';
				html += '<table class="widefat">';
				html += '<thead><tr><th><?php esc_html_e( 'Namespace', '365i-ai-faq-generator' ); ?></th><th><?php esc_html_e( 'Operations', '365i-ai-faq-generator' ); ?></th><th><?php esc_html_e( 'Keys', '365i-ai-faq-generator' ); ?></th><th><?php esc_html_e( 'Storage', '365i-ai-faq-generator' ); ?></th></tr></thead>';
				html += '<tbody>';
				
				for (const [ns, kv] of Object.entries(data.kv_storage)) {
					html += '<tr>';
					html += '<td>' + (kv.name || ns) + '</td>';
					html += '<td>' + (kv.total_operations || 0).toLocaleString() + '</td>';
					html += '<td>' + (kv.storage_keys || 0).toLocaleString() + '</td>';
					html += '<td>' + (kv.storage_formatted || '0 B') + '</td>';
					html += '</tr>';
				}
				
				html += '</tbody></table>';
				html += '</div>';
			}
			
			html += '</div>';
			
			$('#cloudflare-analytics-content').html(html);
		},
		
		createSummaryCard: function(title, value, icon) {
			return '<div class="ai-faq-analytics-card">' +
				'<span class="dashicons ' + icon + '"></span>' +
				'<h4>' + title + '</h4>' +
				'<p class="ai-faq-analytics-value">' + value + '</p>' +
				'</div>';
		},
		
		showError: function(message) {
			const html = '<div class="notice notice-error is-dismissible ai-faq-force-dismiss">' +
				'<p>' + message + '</p>' +
				'</div>';
			
			$('#cloudflare-analytics-content').html(html);
			
			// Auto-dismiss after 3 seconds
			setTimeout(function() {
				$('#cloudflare-analytics-content .notice').fadeOut();
			}, 3000);
		},
		
		showNotification: function(message, type) {
			type = type || 'info';
			const html = '<div class="notice notice-' + type + ' is-dismissible">' +
				'<p>' + message + '</p>' +
				'</div>';
			
			const $notice = $(html);
			this.container.prepend($notice);
			
			// Auto-dismiss after 3 seconds
			setTimeout(function() {
				$notice.fadeOut(function() {
					$notice.remove();
				});
			}, 3000);
		}
	};
	
	// Initialize Enhanced Cloudflare Analytics
	cloudflareAnalyticsManager.init();
	
	// Helper function to format bytes
	function formatBytes(bytes, decimals = 2) {
		if (bytes === 0) return '0 Bytes';
		const k = 1024;
		const dm = decimals < 0 ? 0 : decimals;
		const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
		const i = Math.floor(Math.log(bytes) / Math.log(k));
		return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
	}
});
</script>

<?php
// Include the footer
require_once AI_FAQ_GEN_DIR . 'templates/partials/footer.php';
?>