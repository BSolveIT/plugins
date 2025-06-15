<?php
/**
 * Dashboard Stats Cards Template
 *
 * Displays queue statistics in component card format.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$stats = $data['queue_stats'] ?? array();
?>

<div class="dashboard-stats-section">
	<div class="stats-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
		<h2 style="margin: 0;"><?php esc_html_e( 'Queue Statistics', '365i-queue-optimizer' ); ?></h2>
		<button type="button" class="components-button is-secondary refresh-stats">
			<span class="dashicons dashicons-update-alt" style="margin-right: 4px;"></span>
			<?php esc_html_e( 'Refresh Stats', '365i-queue-optimizer' ); ?>
		</button>
	</div>

<div class="dashboard-stats-grid">
	
	<div class="components-card stat-card">
		<div class="components-card__body" style="text-align: center; padding: 20px;">
			<div class="stat-icon" style="margin-bottom: 12px;">
				<span class="dashicons dashicons-list-view" style="font-size: 24px; color: #0073aa;"></span>
			</div>
			<div class="stat-number" style="font-size: 32px; font-weight: 600; margin-bottom: 4px;">
				<?php echo esc_html( number_format_i18n( $stats['total_jobs'] ?? 0 ) ); ?>
			</div>
			<div class="stat-label" style="color: #646970; font-size: 14px;">
				<?php esc_html_e( 'Total Jobs', '365i-queue-optimizer' ); ?>
			</div>
		</div>
	</div>

	<div class="components-card stat-card">
		<div class="components-card__body" style="text-align: center; padding: 20px;">
			<div class="stat-icon" style="margin-bottom: 12px;">
				<span class="dashicons dashicons-clock" style="font-size: 24px; color: #ffb900;"></span>
			</div>
			<div class="stat-number" style="font-size: 32px; font-weight: 600; margin-bottom: 4px;">
				<?php echo esc_html( number_format_i18n( $stats['pending_jobs'] ?? 0 ) ); ?>
			</div>
			<div class="stat-label" style="color: #646970; font-size: 14px;">
				<?php esc_html_e( 'Pending', '365i-queue-optimizer' ); ?>
			</div>
		</div>
	</div>

	<div class="components-card stat-card">
		<div class="components-card__body" style="text-align: center; padding: 20px;">
			<div class="stat-icon" style="margin-bottom: 12px;">
				<span class="dashicons dashicons-yes-alt" style="font-size: 24px; color: #46b450;"></span>
			</div>
			<div class="stat-number" style="font-size: 32px; font-weight: 600; margin-bottom: 4px;">
				<?php echo esc_html( number_format_i18n( $stats['completed_jobs'] ?? 0 ) ); ?>
			</div>
			<div class="stat-label" style="color: #646970; font-size: 14px;">
				<?php esc_html_e( 'Completed', '365i-queue-optimizer' ); ?>
			</div>
		</div>
	</div>

	<div class="components-card stat-card">
		<div class="components-card__body" style="text-align: center; padding: 20px;">
			<div class="stat-icon" style="margin-bottom: 12px;">
				<span class="dashicons dashicons-dismiss" style="font-size: 24px; color: #dc3232;"></span>
			</div>
			<div class="stat-number" style="font-size: 32px; font-weight: 600; margin-bottom: 4px;">
				<?php echo esc_html( number_format_i18n( $stats['failed_jobs'] ?? 0 ) ); ?>
			</div>
			<div class="stat-label" style="color: #646970; font-size: 14px;">
				<?php esc_html_e( 'Failed', '365i-queue-optimizer' ); ?>
			</div>
		</div>
	</div>

	<div class="components-card stat-card">
		<div class="components-card__body" style="text-align: center; padding: 20px;">
			<div class="stat-icon" style="margin-bottom: 12px;">
				<span class="dashicons dashicons-update" style="font-size: 24px; color: #229fd8;"></span>
			</div>
			<div class="stat-number" style="font-size: 32px; font-weight: 600; margin-bottom: 4px;">
				<?php echo esc_html( number_format_i18n( $stats['in_progress_jobs'] ?? 0 ) ); ?>
			</div>
			<div class="stat-label" style="color: #646970; font-size: 14px;">
				<?php esc_html_e( 'In Progress', '365i-queue-optimizer' ); ?>
			</div>
		</div>
	</div>

</div>