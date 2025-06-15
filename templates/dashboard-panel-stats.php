<?php
/**
 * Dashboard Stats Panel Template
 *
 * Displays key queue statistics in a dashboard widget format.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$stats = $data['queue_stats'] ?? array();
?>

<div class="dashboard-stats-grid">
	
	<div class="stat-card total-jobs">
		<div class="stat-icon">
			<span class="dashicons dashicons-list-view"></span>
		</div>
		<div class="stat-content">
			<h3><?php echo esc_html( number_format_i18n( $stats['total_jobs'] ?? 0 ) ); ?></h3>
			<p><?php esc_html_e( 'Total Jobs', '365i-queue-optimizer' ); ?></p>
		</div>
	</div>

	<div class="stat-card pending-jobs">
		<div class="stat-icon">
			<span class="dashicons dashicons-clock"></span>
		</div>
		<div class="stat-content">
			<h3><?php echo esc_html( number_format_i18n( $stats['pending_jobs'] ?? 0 ) ); ?></h3>
			<p><?php esc_html_e( 'Pending', '365i-queue-optimizer' ); ?></p>
		</div>
	</div>

	<div class="stat-card completed-jobs">
		<div class="stat-icon">
			<span class="dashicons dashicons-yes-alt"></span>
		</div>
		<div class="stat-content">
			<h3><?php echo esc_html( number_format_i18n( $stats['completed_jobs'] ?? 0 ) ); ?></h3>
			<p><?php esc_html_e( 'Completed', '365i-queue-optimizer' ); ?></p>
		</div>
	</div>

	<div class="stat-card failed-jobs">
		<div class="stat-icon">
			<span class="dashicons dashicons-dismiss"></span>
		</div>
		<div class="stat-content">
			<h3><?php echo esc_html( number_format_i18n( $stats['failed_jobs'] ?? 0 ) ); ?></h3>
			<p><?php esc_html_e( 'Failed', '365i-queue-optimizer' ); ?></p>
		</div>
	</div>

	<div class="stat-card in-progress-jobs">
		<div class="stat-icon">
			<span class="dashicons dashicons-update"></span>
		</div>
		<div class="stat-content">
			<h3><?php echo esc_html( number_format_i18n( $stats['in_progress_jobs'] ?? 0 ) ); ?></h3>
			<p><?php esc_html_e( 'In Progress', '365i-queue-optimizer' ); ?></p>
		</div>
	</div>

</div>