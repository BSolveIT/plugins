<?php
/**
 * WordPress Information Panel Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wordpress_info = $data['wordpress_info'] ?? array();
?>

<div class="postbox">
	<div class="postbox-header">
		<h2 class="hndle">
			<span class="dashicons dashicons-wordpress"></span>
			<?php esc_html_e( 'WordPress Configuration', '365i-queue-optimizer' ); ?>
		</h2>
		<div class="handle-actions">
			<button type="button" class="button button-link copy-section" data-section="wordpress_info">
				<span class="dashicons dashicons-clipboard"></span>
				<?php esc_html_e( 'Copy', '365i-queue-optimizer' ); ?>
			</button>
		</div>
	</div>
	<div class="inside">
		<table class="form-table system-info-table">
			<tbody>
				<?php foreach ( $wordpress_info as $key => $value ) : ?>
					<?php
					$label = ucwords( str_replace( '_', ' ', $key ) );
					$label = apply_filters( 'queue_optimizer_system_info_wordpress_label', $label, $key );
					$value = apply_filters( 'queue_optimizer_system_info_wordpress_value', $value, $key );
					?>
					<tr>
						<th scope="row"><?php echo esc_html( $label ); ?></th>
						<td>
							<code><?php echo esc_html( $value ); ?></code>
							<?php if ( 'wp_debug' === $key && 'Yes' === $value ) : ?>
								<span class="status-info" title="<?php esc_attr_e( 'Debug mode is enabled', '365i-queue-optimizer' ); ?>">ℹ️</span>
							<?php endif; ?>
							<?php if ( 'wp_cron' === $key && 'Disabled' === $value ) : ?>
								<span class="status-warning" title="<?php esc_attr_e( 'WP Cron is disabled - scheduled tasks may not work', '365i-queue-optimizer' ); ?>">⚠️</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>