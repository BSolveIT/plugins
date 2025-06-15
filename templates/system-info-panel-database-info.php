<?php
/**
 * Database Information Panel Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$database_info = $data['database_info'] ?? array();
?>

<div class="postbox">
	<div class="postbox-header">
		<h2 class="hndle">
			<span class="dashicons dashicons-database"></span>
			<?php esc_html_e( 'Database Information', '365i-queue-optimizer' ); ?>
		</h2>
		<div class="handle-actions">
			<button type="button" class="button button-link copy-section" data-section="database_info">
				<span class="dashicons dashicons-clipboard"></span>
				<?php esc_html_e( 'Copy', '365i-queue-optimizer' ); ?>
			</button>
		</div>
	</div>
	<div class="inside">
		<table class="form-table system-info-table">
			<tbody>
				<?php foreach ( $database_info as $key => $value ) : ?>
					<?php
					$label = ucwords( str_replace( '_', ' ', $key ) );
					$label = apply_filters( 'queue_optimizer_system_info_database_label', $label, $key );
					$value = apply_filters( 'queue_optimizer_system_info_database_value', $value, $key );
					
					// Hide sensitive information.
					if ( in_array( $key, array( 'database_user', 'database_host' ), true ) ) {
						$value = str_repeat( '*', strlen( $value ) - 4 ) . substr( $value, -4 );
					}
					?>
					<tr>
						<th scope="row"><?php echo esc_html( $label ); ?></th>
						<td>
							<code><?php echo esc_html( $value ); ?></code>
							<?php if ( 'database_version' === $key && version_compare( $value, '5.7', '<' ) ) : ?>
								<span class="status-warning" title="<?php esc_attr_e( 'Consider upgrading MySQL for better performance', '365i-queue-optimizer' ); ?>">⚠️</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>