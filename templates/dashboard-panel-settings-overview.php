<?php
/**
 * Dashboard Settings Overview Panel Template
 *
 * Displays plugin settings overview in a dashboard widget format.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin_settings = $data['plugin_settings'] ?? array();
?>

<div class="postbox dashboard-panel">
	<div class="postbox-header">
		<h2 class="hndle"><?php esc_html_e( 'Settings Overview', '365i-queue-optimizer' ); ?></h2>
	</div>
	<div class="inside">
		
		<div class="settings-overview">
			<table class="widefat">
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Retention Days', '365i-queue-optimizer' ); ?></td>
						<td>
							<strong><?php echo esc_html( $plugin_settings['retention_days'] ?? 30 ); ?></strong>
							<?php esc_html_e( 'days', '365i-queue-optimizer' ); ?>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Auto Cleanup', '365i-queue-optimizer' ); ?></td>
						<td>
							<span class="setting-status setting-<?php echo esc_attr( $plugin_settings['auto_cleanup'] ?? 'no' ); ?>">
								<?php if ( 'yes' === ( $plugin_settings['auto_cleanup'] ?? 'no' ) ) : ?>
									<span class="dashicons dashicons-yes-alt"></span>
									<?php esc_html_e( 'Enabled', '365i-queue-optimizer' ); ?>
								<?php else : ?>
									<span class="dashicons dashicons-dismiss"></span>
									<?php esc_html_e( 'Disabled', '365i-queue-optimizer' ); ?>
								<?php endif; ?>
							</span>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Debug Mode', '365i-queue-optimizer' ); ?></td>
						<td>
							<span class="setting-status setting-<?php echo esc_attr( $plugin_settings['debug_mode'] ?? 'no' ); ?>">
								<?php if ( 'yes' === ( $plugin_settings['debug_mode'] ?? 'no' ) ) : ?>
									<span class="dashicons dashicons-warning"></span>
									<?php esc_html_e( 'Enabled', '365i-queue-optimizer' ); ?>
								<?php else : ?>
									<span class="dashicons dashicons-yes-alt"></span>
									<?php esc_html_e( 'Disabled', '365i-queue-optimizer' ); ?>
								<?php endif; ?>
							</span>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Email Notifications', '365i-queue-optimizer' ); ?></td>
						<td>
							<span class="setting-status setting-<?php echo esc_attr( $plugin_settings['email_notifications'] ?? 'no' ); ?>">
								<?php if ( 'yes' === ( $plugin_settings['email_notifications'] ?? 'no' ) ) : ?>
									<span class="dashicons dashicons-email-alt"></span>
									<?php esc_html_e( 'Enabled', '365i-queue-optimizer' ); ?>
								<?php else : ?>
									<span class="dashicons dashicons-dismiss"></span>
									<?php esc_html_e( 'Disabled', '365i-queue-optimizer' ); ?>
								<?php endif; ?>
							</span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="settings-footer">
			<a href="<?php echo esc_url( admin_url( 'options-general.php?page=queue-optimizer-settings' ) ); ?>" 
			   class="button button-secondary">
				<?php esc_html_e( 'Manage Settings', '365i-queue-optimizer' ); ?>
			</a>
		</div>

	</div>
</div>