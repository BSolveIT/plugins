<?php
/**
 * Server Information Panel Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$server_info = $data['server_info'] ?? array();
?>

<div class="postbox">
	<div class="postbox-header">
		<h2 class="hndle">
			<span class="dashicons dashicons-admin-settings"></span>
			<?php esc_html_e( 'Server Environment', '365i-queue-optimizer' ); ?>
		</h2>
		<div class="handle-actions">
			<button type="button" class="button button-link copy-section" data-section="server_info">
				<span class="dashicons dashicons-clipboard"></span>
				<?php esc_html_e( 'Copy', '365i-queue-optimizer' ); ?>
			</button>
		</div>
	</div>
	<div class="inside">
		<table class="form-table system-info-table">
			<tbody>
				<?php foreach ( $server_info as $key => $value ) : ?>
					<?php
					$label = ucwords( str_replace( '_', ' ', $key ) );
					$label = apply_filters( 'queue_optimizer_system_info_server_label', $label, $key );
					$value = apply_filters( 'queue_optimizer_system_info_server_value', $value, $key );
					?>
					<tr>
						<th scope="row"><?php echo esc_html( $label ); ?></th>
						<td>
							<code><?php echo esc_html( $value ); ?></code>
							<?php if ( 'php_version' === $key && version_compare( $value, '8.0', '<' ) ) : ?>
								<span class="status-warning" title="<?php esc_attr_e( 'Consider upgrading to PHP 8.0+ for better performance', '365i-queue-optimizer' ); ?>">⚠️</span>
							<?php endif; ?>
							<?php if ( 'memory_limit' === $key && (int) $value < 256 ) : ?>
								<span class="status-warning" title="<?php esc_attr_e( 'Low memory limit may cause issues', '365i-queue-optimizer' ); ?>">⚠️</span>
							<?php endif; ?>
							<?php if ( 'max_execution_time' === $key && (int) $value < 30 ) : ?>
								<span class="status-warning" title="<?php esc_attr_e( 'Low execution time limit', '365i-queue-optimizer' ); ?>">⚠️</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>