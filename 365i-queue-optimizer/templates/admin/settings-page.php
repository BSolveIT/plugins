<?php
/**
 * Settings page template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include header.
require_once QUEUE_OPTIMIZER_PLUGIN_DIR . 'templates/partials/header.php';

// Get current status data with filters for extensibility.
$status_data = apply_filters( 'queue_optimizer_status_data', array(
	'time_limit'             => get_option( 'queue_optimizer_time_limit', 60 ),
	'concurrent_batches'     => get_option( 'queue_optimizer_concurrent_batches', 4 ),
	'image_engine'           => get_option( 'queue_optimizer_image_engine', 'WP_Image_Editor_Imagick' ),
	'actionscheduler_active' => class_exists( 'ActionScheduler' ),
) );
?>

<form method="post" action="options.php">
	<?php
	settings_fields( 'queue_optimizer_settings' );
	do_settings_sections( 'queue_optimizer_settings' );
	submit_button();
	?>
</form>

<div class="card">
	<h2><?php esc_html_e( 'Current Status', '365i-queue-optimizer' ); ?></h2>
	<table class="widefat striped">
		<tbody>
			<tr>
				<td><strong><?php esc_html_e( 'Time Limit', '365i-queue-optimizer' ); ?></strong></td>
				<td><?php echo esc_html( $status_data['time_limit'] ); ?> <?php esc_html_e( 'seconds', '365i-queue-optimizer' ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Concurrent Batches', '365i-queue-optimizer' ); ?></strong></td>
				<td><?php echo esc_html( $status_data['concurrent_batches'] ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Image Engine', '365i-queue-optimizer' ); ?></strong></td>
				<td><?php echo esc_html( str_replace( 'WP_Image_Editor_', '', $status_data['image_engine'] ) ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'ActionScheduler Status', '365i-queue-optimizer' ); ?></strong></td>
				<td>
					<?php if ( $status_data['actionscheduler_active'] ) : ?>
						<span style="color: green;">V <?php esc_html_e( 'Active & Optimized', '365i-queue-optimizer' ); ?></span>
					<?php else : ?>
						<span style="color: orange;">? <?php esc_html_e( 'ActionScheduler not detected', '365i-queue-optimizer' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>
	
	<?php if ( $status_data['actionscheduler_active'] ) : ?>
		<p style="margin-top: 15px;">
			<a href="<?php echo esc_url( admin_url( 'tools.php?page=action-scheduler' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'View ActionScheduler Status', '365i-queue-optimizer' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>

<?php
// Include footer.
require_once QUEUE_OPTIMIZER_PLUGIN_DIR . 'templates/partials/footer.php';
