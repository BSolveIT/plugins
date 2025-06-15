<?php
/**
 * System Info - Queue Status Panel
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$card_title = 'Queue System Status';
$card_icon = 'dashicons-clock';
$card_id = 'queue-status';
include plugin_dir_path( __FILE__ ) . '../partials/card-header.php';
?>

<div class="components-card__body">
	<table class="widefat striped components-table">
		<caption class="screen-reader-text">Queue System Status Information</caption>
		<tbody>
			<tr>
				<th scope="row" style="width: 30%;">Queue System</th>
				<td>
					<?php $queue_system = $data['queue']['system'] ?? 'Unknown'; ?>
					<code><?php echo esc_html( $queue_system ); ?></code>
					<?php if ( $queue_system !== 'Unknown' ) : ?>
						<span class="components-badge is-success"
							style="margin-left: 8px; background-color: #46b450; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
							Active
						</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row">Total Jobs</th>
				<td>
					<strong style="color: #229fd8;"><?php echo number_format( $data['queue']['total_jobs'] ?? 0 ); ?></strong>
				</td>
			</tr>
			<tr>
				<th scope="row">Pending Jobs</th>
				<td>
					<strong style="color: #ffb900;"><?php echo number_format( $data['queue']['pending_jobs'] ?? 0 ); ?></strong>
				</td>
			</tr>
			<tr>
				<th scope="row">Processing Jobs</th>
				<td>
					<strong style="color: #229fd8;"><?php echo number_format( $data['queue']['processing_jobs'] ?? 0 ); ?></strong>
				</td>
			</tr>
			<tr>
				<th scope="row">Completed Jobs</th>
				<td>
					<strong style="color: #46b450;"><?php echo number_format( $data['queue']['completed_jobs'] ?? 0 ); ?></strong>
				</td>
			</tr>
			<tr>
				<th scope="row">Failed Jobs</th>
				<td>
					<strong style="color: #dc3232;"><?php echo number_format( $data['queue']['failed_jobs'] ?? 0 ); ?></strong>
					<?php if ( ( $data['queue']['failed_jobs'] ?? 0 ) > 0 ) : ?>
						<span class="components-badge is-error"
							style="margin-left: 8px; background-color: #dc3232; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
							Attention Required
						</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row">Queue Runner</th>
				<td>
					<?php $runner_status = $data['queue']['runner_status'] ?? 'Unknown'; ?>
					<code><?php echo esc_html( $runner_status ); ?></code>
					<span class="components-badge <?php echo esc_attr( $runner_status === 'Running' ? 'is-success' : 'is-warning' ); ?>"
						style="margin-left: 8px; background-color: <?php echo $runner_status === 'Running' ? '#46b450' : '#ffb900'; ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
						<?php echo esc_html( $runner_status ); ?>
					</span>
				</td>
			</tr>
			<tr>
				<th scope="row">Next Scheduled Run</th>
				<td>
					<code><?php echo esc_html( $data['queue']['next_run'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Last Run</th>
				<td>
					<code><?php echo esc_html( $data['queue']['last_run'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Average Processing Time</th>
				<td>
					<code><?php echo esc_html( $data['queue']['avg_processing_time'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
		</tbody>
	</table>

	<!-- Queue Health Status -->
	<?php if ( ! empty( $data['queue']['health_status'] ) ) : ?>
		<div style="margin-top: 20px; padding: 16px; background: <?php echo $data['queue']['health_status'] === 'healthy' ? '#f0f6fc' : '#fef7f0'; ?>; border-left: 4px solid <?php echo $data['queue']['health_status'] === 'healthy' ? '#46b450' : '#ffb900'; ?>; border-radius: 4px;">
			<h4 style="margin: 0 0 8px 0; color: #1d2327; font-size: 14px;">
				<span class="dashicons <?php echo $data['queue']['health_status'] === 'healthy' ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" 
					style="font-size: 16px; margin-right: 8px; color: <?php echo $data['queue']['health_status'] === 'healthy' ? '#46b450' : '#ffb900'; ?>;"></span>
				Queue Health: <?php echo esc_html( ucfirst( $data['queue']['health_status'] ) ); ?>
			</h4>
			<?php if ( ! empty( $data['queue']['health_message'] ) ) : ?>
				<p style="margin: 0; color: #646970; font-size: 13px;">
					<?php echo esc_html( $data['queue']['health_message'] ); ?>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>

<?php
$footer_actions = array(
	array(
		'text' => 'Copy Queue Info',
		'onclick' => 'copyToClipboard(\'queue-status\')',
		'class' => 'is-secondary',
		'icon' => 'dashicons-clipboard'
	),
	array(
		'text' => 'View Queue Details',
		'url' => admin_url( 'tools.php?page=queue-optimizer' ),
		'class' => 'is-primary',
		'icon' => 'dashicons-external'
	)
);
include plugin_dir_path( __FILE__ ) . '../partials/card-footer.php';
?>