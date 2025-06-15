<?php
/**
 * Dashboard Status Panel Template
 *
 * Displays system status information using component card format.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$system_status = $data['system_status'] ?? array();
$overall_status = $system_status['overall_status'] ?? 'unknown';

// Set up card header variables
$card_title = __( 'System Status', '365i-queue-optimizer' );
$card_icon = 'dashicons-admin-tools';

// Include card header
include plugin_dir_path( __FILE__ ) . '../partials/card-header.php';
?>

<div class="system-status-overview" style="margin-bottom: 20px;">
	<?php if ( 'good' === $overall_status ) : ?>
		<span class="components-badge" style="background-color: #46b450; color: white; padding: 4px 8px; border-radius: 4px;">
			<span class="dashicons dashicons-yes-alt" style="font-size: 14px; margin-right: 4px;"></span>
			<?php esc_html_e( 'System Running Normally', '365i-queue-optimizer' ); ?>
		</span>
	<?php elseif ( 'warning' === $overall_status ) : ?>
		<span class="components-badge" style="background-color: #ffb900; color: white; padding: 4px 8px; border-radius: 4px;">
			<span class="dashicons dashicons-warning" style="font-size: 14px; margin-right: 4px;"></span>
			<?php esc_html_e( 'System Warnings Detected', '365i-queue-optimizer' ); ?>
		</span>
	<?php else : ?>
		<span class="components-badge" style="background-color: #dc3232; color: white; padding: 4px 8px; border-radius: 4px;">
			<span class="dashicons dashicons-dismiss" style="font-size: 14px; margin-right: 4px;"></span>
			<?php esc_html_e( 'System Issues Detected', '365i-queue-optimizer' ); ?>
		</span>
	<?php endif; ?>
</div>

<div class="table-responsive">
	<table class="widefat striped components-table">
		<caption class="screen-reader-text"><?php esc_html_e( 'System Status Information', '365i-queue-optimizer' ); ?></caption>
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Queue Status', '365i-queue-optimizer' ); ?></th>
				<td>
					<?php $queue_status = $system_status['queue_status'] ?? 'unknown'; ?>
					<?php if ( 'running' === $queue_status ) : ?>
						<span class="components-badge" style="background-color: #46b450; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
							<?php esc_html_e( 'RUNNING', '365i-queue-optimizer' ); ?>
						</span>
					<?php else : ?>
						<span class="components-badge" style="background-color: #dc3232; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
							<?php echo esc_html( strtoupper( $queue_status ) ); ?>
						</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Last Run', '365i-queue-optimizer' ); ?></th>
				<td style="font-size: 18px; font-weight: 500;">
					<?php echo esc_html( $system_status['last_run'] ?? __( 'Never', '365i-queue-optimizer' ) ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'PHP Version', '365i-queue-optimizer' ); ?></th>
				<td style="font-size: 18px; font-weight: 500;">
					<?php echo esc_html( $system_status['php_version'] ?? 'Unknown' ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'WordPress Version', '365i-queue-optimizer' ); ?></th>
				<td style="font-size: 18px; font-weight: 500;">
					<?php echo esc_html( $system_status['wp_version'] ?? 'Unknown' ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Plugin Version', '365i-queue-optimizer' ); ?></th>
				<td style="font-size: 18px; font-weight: 500;">
					<?php echo esc_html( $system_status['plugin_version'] ?? 'Unknown' ); ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<?php
// Include card footer
include plugin_dir_path( __FILE__ ) . '../partials/card-footer.php';
?>