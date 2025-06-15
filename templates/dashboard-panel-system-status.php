<?php
/**
 * Dashboard System Status Panel Template
 *
 * Displays system status information in a dashboard widget format.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$system_status = $data['system_status'] ?? array();
$overall_status = $system_status['overall_status'] ?? 'unknown';
?>

<div class="postbox dashboard-panel">
	<div class="postbox-header">
		<h2 class="hndle"><?php esc_html_e( 'System Status', '365i-queue-optimizer' ); ?></h2>
	</div>
	<div class="inside">
		
		<div class="system-status-overview">
			<div class="status-indicator status-<?php echo esc_attr( $overall_status ); ?>">
				<?php if ( 'good' === $overall_status ) : ?>
					<span class="dashicons dashicons-yes-alt"></span>
					<?php esc_html_e( 'System Running Normally', '365i-queue-optimizer' ); ?>
				<?php elseif ( 'warning' === $overall_status ) : ?>
					<span class="dashicons dashicons-warning"></span>
					<?php esc_html_e( 'System Warnings Detected', '365i-queue-optimizer' ); ?>
				<?php else : ?>
					<span class="dashicons dashicons-dismiss"></span>
					<?php esc_html_e( 'System Issues Detected', '365i-queue-optimizer' ); ?>
				<?php endif; ?>
			</div>
		</div>

		<div class="system-status-details">
			<table class="widefat">
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Queue Status', '365i-queue-optimizer' ); ?></td>
						<td>
							<span class="status-badge status-<?php echo esc_attr( $system_status['queue_status'] ?? 'unknown' ); ?>">
								<?php echo esc_html( ucfirst( $system_status['queue_status'] ?? 'Unknown' ) ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Last Run', '365i-queue-optimizer' ); ?></td>
						<td><?php echo esc_html( $system_status['last_run'] ?? __( 'Never', '365i-queue-optimizer' ) ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'PHP Version', '365i-queue-optimizer' ); ?></td>
						<td><?php echo esc_html( $system_status['php_version'] ?? 'Unknown' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'WordPress Version', '365i-queue-optimizer' ); ?></td>
						<td><?php echo esc_html( $system_status['wp_version'] ?? 'Unknown' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Plugin Version', '365i-queue-optimizer' ); ?></td>
						<td><?php echo esc_html( $system_status['plugin_version'] ?? 'Unknown' ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>

	</div>
</div>