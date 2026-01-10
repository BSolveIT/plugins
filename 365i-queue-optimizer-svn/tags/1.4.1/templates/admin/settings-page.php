<?php
/**
 * Settings page template
 *
 * @package QueueOptimizer
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get server environment data.
$queue_optimizer_env = Queue_Optimizer_Main::get_server_environment();
$queue_optimizer_recommended = Queue_Optimizer_Main::get_recommended_settings();
$queue_optimizer_queue_status = Queue_Optimizer_Settings_Page::get_queue_status();

// Get current status data with filters for extensibility.
$queue_optimizer_status_data = apply_filters( 'queue_optimizer_status_data', array(
	'time_limit'         => get_option( 'queue_optimizer_time_limit', 60 ),
	'concurrent_batches' => get_option( 'queue_optimizer_concurrent_batches', 4 ),
	'batch_size'         => get_option( 'queue_optimizer_batch_size', 50 ),
	'retention_days'     => get_option( 'queue_optimizer_retention_days', 7 ),
	'image_engine'       => get_option( 'queue_optimizer_image_engine', 'WP_Image_Editor_Imagick' ),
) );

// Include header.
require_once QUEUE_OPTIMIZER_PLUGIN_DIR . 'templates/partials/header.php';
?>

<div class="qo-grid">
	<div class="qo-main">
		<form method="post" action="options.php">
			<?php
			settings_fields( 'queue_optimizer_settings' );
			do_settings_sections( 'queue_optimizer_settings' );
			submit_button();
			?>
		</form>

		<div class="card qo-card">
			<h2><?php esc_html_e( 'Current Settings', '365i-queue-optimizer' ); ?></h2>
			<table class="widefat striped">
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Time Limit', '365i-queue-optimizer' ); ?></strong></td>
						<td>
							<?php echo esc_html( $queue_optimizer_status_data['time_limit'] ); ?> <?php esc_html_e( 'seconds', '365i-queue-optimizer' ); ?>
							<?php if ( (int) $queue_optimizer_status_data['time_limit'] !== $queue_optimizer_recommended['time_limit'] ) : ?>
								<span class="qo-rec"><?php
									/* translators: %d: recommended setting value */
									printf( esc_html__( '(Recommended: %d)', '365i-queue-optimizer' ), absint( $queue_optimizer_recommended['time_limit'] ) );
								?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Concurrent Batches', '365i-queue-optimizer' ); ?></strong></td>
						<td>
							<?php echo esc_html( $queue_optimizer_status_data['concurrent_batches'] ); ?>
							<?php if ( (int) $queue_optimizer_status_data['concurrent_batches'] !== $queue_optimizer_recommended['concurrent_batches'] ) : ?>
								<span class="qo-rec"><?php
									/* translators: %d: recommended setting value */
									printf( esc_html__( '(Recommended: %d)', '365i-queue-optimizer' ), absint( $queue_optimizer_recommended['concurrent_batches'] ) );
								?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Batch Size', '365i-queue-optimizer' ); ?></strong></td>
						<td>
							<?php echo esc_html( $queue_optimizer_status_data['batch_size'] ); ?> <?php esc_html_e( 'actions', '365i-queue-optimizer' ); ?>
							<?php if ( (int) $queue_optimizer_status_data['batch_size'] !== $queue_optimizer_recommended['batch_size'] ) : ?>
								<span class="qo-rec"><?php
									/* translators: %d: recommended setting value */
									printf( esc_html__( '(Recommended: %d)', '365i-queue-optimizer' ), absint( $queue_optimizer_recommended['batch_size'] ) );
								?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Data Retention', '365i-queue-optimizer' ); ?></strong></td>
						<td>
							<?php echo esc_html( $queue_optimizer_status_data['retention_days'] ); ?> <?php esc_html_e( 'days', '365i-queue-optimizer' ); ?>
							<?php if ( (int) $queue_optimizer_status_data['retention_days'] !== $queue_optimizer_recommended['retention_days'] ) : ?>
								<span class="qo-rec"><?php
									/* translators: %d: recommended setting value */
									printf( esc_html__( '(Recommended: %d)', '365i-queue-optimizer' ), absint( $queue_optimizer_recommended['retention_days'] ) );
								?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Image Engine', '365i-queue-optimizer' ); ?></strong></td>
						<td><?php echo esc_html( str_replace( 'WP_Image_Editor_', '', $queue_optimizer_status_data['image_engine'] ) ); ?></td>
					</tr>
				</tbody>
			</table>

			<p class="qo-actions">
				<button type="button" id="qo-apply-recommended" class="button button-secondary">
					<?php esc_html_e( 'Apply Recommended Settings', '365i-queue-optimizer' ); ?>
				</button>
			</p>
		</div>
	</div>

	<div class="qo-sidebar">
		<?php if ( $queue_optimizer_env['actionscheduler_active'] ) : ?>
		<div class="card qo-card qo-queue-card">
			<h2><?php esc_html_e( 'Queue Status', '365i-queue-optimizer' ); ?></h2>
			<div class="qo-queue-stats">
				<div class="qo-stat">
					<span class="qo-stat-value" id="qo-pending-count"><?php echo esc_html( $queue_optimizer_queue_status['pending_total'] ); ?></span>
					<span class="qo-stat-label"><?php esc_html_e( 'Pending', '365i-queue-optimizer' ); ?></span>
				</div>
				<div class="qo-stat">
					<span class="qo-stat-value qo-stat-running"><?php echo esc_html( $queue_optimizer_queue_status['running'] ); ?></span>
					<span class="qo-stat-label"><?php esc_html_e( 'Running', '365i-queue-optimizer' ); ?></span>
				</div>
				<div class="qo-stat">
					<span class="qo-stat-value <?php echo $queue_optimizer_queue_status['failed'] > 0 ? 'qo-stat-failed' : ''; ?>"><?php echo esc_html( $queue_optimizer_queue_status['failed'] ); ?></span>
					<span class="qo-stat-label"><?php esc_html_e( 'Failed (24h)', '365i-queue-optimizer' ); ?></span>
				</div>
			</div>

			<?php if ( ! empty( $queue_optimizer_queue_status['pending_by_hook'] ) ) : ?>
			<div class="qo-pending-hooks">
				<strong><?php esc_html_e( 'Pending by type:', '365i-queue-optimizer' ); ?></strong>
				<ul>
					<?php foreach ( $queue_optimizer_queue_status['pending_by_hook'] as $queue_optimizer_hook => $queue_optimizer_count ) : ?>
					<li>
						<code><?php echo esc_html( $queue_optimizer_hook ); ?></code>
						<span class="qo-hook-count"><?php echo esc_html( $queue_optimizer_count ); ?></span>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>

			<div class="qo-queue-actions">
				<button type="button" id="qo-run-queue" class="button button-primary" <?php echo 0 === $queue_optimizer_queue_status['pending_total'] ? 'disabled' : ''; ?>>
					<?php esc_html_e( 'Run Queue Now', '365i-queue-optimizer' ); ?>
				</button>
				<a href="<?php echo esc_url( admin_url( 'tools.php?page=action-scheduler' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'View All', '365i-queue-optimizer' ); ?>
				</a>
			</div>
			<p class="qo-queue-result" id="qo-queue-result"></p>
		</div>
		<?php else : ?>
		<div class="card qo-card">
			<h2><?php esc_html_e( 'Queue Status', '365i-queue-optimizer' ); ?></h2>
			<p class="qo-no-as"><?php esc_html_e( 'ActionScheduler is not active. Install WooCommerce or another plugin that includes ActionScheduler to use queue optimization.', '365i-queue-optimizer' ); ?></p>
		</div>
		<?php endif; ?>

		<div class="card qo-card">
			<h2><?php esc_html_e( 'Server Environment', '365i-queue-optimizer' ); ?></h2>
			<table class="widefat striped qo-env-table">
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Detected Type', '365i-queue-optimizer' ); ?></td>
						<td>
							<span class="qo-server-type qo-server-<?php echo esc_attr( $queue_optimizer_env['server_type'] ); ?>">
								<?php
								$queue_optimizer_types = array(
									'shared'    => __( 'Shared Hosting', '365i-queue-optimizer' ),
									'vps'       => __( 'VPS / Managed', '365i-queue-optimizer' ),
									'dedicated' => __( 'Dedicated / High', '365i-queue-optimizer' ),
								);
								echo esc_html( isset( $queue_optimizer_types[ $queue_optimizer_env['server_type'] ] ) ? $queue_optimizer_types[ $queue_optimizer_env['server_type'] ] : $queue_optimizer_env['server_type'] );
								?>
							</span>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'PHP Version', '365i-queue-optimizer' ); ?></td>
						<td><?php echo esc_html( $queue_optimizer_env['php_version'] ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'WordPress', '365i-queue-optimizer' ); ?></td>
						<td><?php echo esc_html( $queue_optimizer_env['wp_version'] ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Memory Limit', '365i-queue-optimizer' ); ?></td>
						<td><?php echo esc_html( $queue_optimizer_env['memory_limit'] ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Max Execution', '365i-queue-optimizer' ); ?></td>
						<td>
							<?php
							if ( 0 === $queue_optimizer_env['max_execution_time'] ) {
								esc_html_e( 'Unlimited', '365i-queue-optimizer' );
							} else {
								/* translators: %d: number of seconds */
								printf( esc_html__( '%d seconds', '365i-queue-optimizer' ), absint( $queue_optimizer_env['max_execution_time'] ) );
							}
							?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="card qo-card">
			<h2><?php esc_html_e( 'Image Processing', '365i-queue-optimizer' ); ?></h2>
			<table class="widefat striped qo-env-table">
				<tbody>
					<tr>
						<td><?php esc_html_e( 'ImageMagick', '365i-queue-optimizer' ); ?></td>
						<td>
							<?php if ( $queue_optimizer_env['imagick_available'] ) : ?>
								<span class="qo-status-ok"><?php esc_html_e( 'Available', '365i-queue-optimizer' ); ?></span>
								<?php if ( $queue_optimizer_env['imagick_version'] ) : ?>
									<span class="qo-version">v<?php echo esc_html( $queue_optimizer_env['imagick_version'] ); ?></span>
								<?php endif; ?>
							<?php else : ?>
								<span class="qo-status-warn"><?php esc_html_e( 'Not installed', '365i-queue-optimizer' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'GD Library', '365i-queue-optimizer' ); ?></td>
						<td>
							<?php if ( $queue_optimizer_env['gd_available'] ) : ?>
								<span class="qo-status-ok"><?php esc_html_e( 'Available', '365i-queue-optimizer' ); ?></span>
								<?php if ( $queue_optimizer_env['gd_version'] ) : ?>
									<span class="qo-version"><?php echo esc_html( $queue_optimizer_env['gd_version'] ); ?></span>
								<?php endif; ?>
							<?php else : ?>
								<span class="qo-status-warn"><?php esc_html_e( 'Not installed', '365i-queue-optimizer' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'WebP Support', '365i-queue-optimizer' ); ?></td>
						<td>
							<?php if ( $queue_optimizer_env['webp_support'] ) : ?>
								<span class="qo-status-ok"><?php esc_html_e( 'Yes', '365i-queue-optimizer' ); ?></span>
							<?php else : ?>
								<span class="qo-status-warn"><?php esc_html_e( 'No', '365i-queue-optimizer' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'AVIF Support', '365i-queue-optimizer' ); ?></td>
						<td>
							<?php if ( $queue_optimizer_env['avif_support'] ) : ?>
								<span class="qo-status-ok"><?php esc_html_e( 'Yes', '365i-queue-optimizer' ); ?></span>
							<?php else : ?>
								<span class="qo-status-warn"><?php esc_html_e( 'No', '365i-queue-optimizer' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php
// Include footer.
require_once QUEUE_OPTIMIZER_PLUGIN_DIR . 'templates/partials/footer.php';
