<?php
/**
 * Theme Information Panel Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$theme_info = $data['theme_info'] ?? array();
?>

<div class="postbox">
	<div class="postbox-header">
		<h2 class="hndle">
			<span class="dashicons dashicons-admin-appearance"></span>
			<?php esc_html_e( 'Active Theme', '365i-queue-optimizer' ); ?>
		</h2>
		<div class="handle-actions">
			<button type="button" class="button button-link copy-section" data-section="theme_info">
				<span class="dashicons dashicons-clipboard"></span>
				<?php esc_html_e( 'Copy', '365i-queue-optimizer' ); ?>
			</button>
		</div>
	</div>
	<div class="inside">
		<table class="form-table system-info-table">
			<tbody>
				<?php foreach ( $theme_info as $key => $value ) : ?>
					<?php
					$label = ucwords( str_replace( '_', ' ', $key ) );
					$label = apply_filters( 'queue_optimizer_system_info_theme_label', $label, $key );
					$value = apply_filters( 'queue_optimizer_system_info_theme_value', $value, $key );
					?>
					<tr>
						<th scope="row"><?php echo esc_html( $label ); ?></th>
						<td>
							<code><?php echo esc_html( $value ); ?></code>
							<?php if ( 'child_theme' === $key && 'Yes' === $value ) : ?>
								<span class="status-success" title="<?php esc_attr_e( 'Child theme is active - good for customization safety', '365i-queue-optimizer' ); ?>">✅</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>