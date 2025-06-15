<?php
/**
 * Queue System Panel Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$queue_system = $data['queue_system'] ?? array();
?>

<div class="postbox">
	<div class="postbox-header">
		<h2 class="hndle">
			<span class="dashicons dashicons-performance"></span>
			<?php esc_html_e( 'Queue System Status', '365i-queue-optimizer' ); ?>
		</h2>
		<div class="handle-actions">
			<button type="button" class="button button-link copy-section" data-section="queue_system">
				<span class="dashicons dashicons-clipboard"></span>
				<?php esc_html_e( 'Copy', '365i-queue-optimizer' ); ?>
			</button>
		</div>
	</div>
	<div class="inside">
		<?php if ( isset( $queue_system['queue_stats'] ) ) : ?>
			<div class="queue-stats-grid">
				<?php foreach ( $queue_system['queue_stats'] as $status => $count ) : ?>
					<div class="queue-stat-item status-<?php echo esc_attr( $status ); ?>">
						<div class="stat-number"><?php echo esc_html( number_format( $count ) ); ?></div>
						<div class="stat-label"><?php echo esc_html( ucfirst( $status ) ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<table class="form-table system-info-table">
			<tbody>
				<?php foreach ( $queue_system as $key => $value ) : ?>
					<?php if ( 'queue_stats' === $key ) continue; ?>
					<?php
					$label = ucwords( str_replace( '_', ' ', $key ) );
					$label = apply_filters( 'queue_optimizer_system_info_queue_label', $label, $key );
					$value = apply_filters( 'queue_optimizer_system_info_queue_value', $value, $key );
					
					// Format last run time.
					if ( 'last_run' === $key && is_numeric( $value ) ) {
						$value = $value > 0 ? date( 'Y-m-d H:i:s', $value ) : 'Never';
					}
					?>
					<tr>
						<th scope="row"><?php echo esc_html( $label ); ?></th>
						<td>
							<code><?php echo esc_html( $value ); ?></code>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>