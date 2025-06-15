<?php
/**
 * Dashboard Settings Overview Template
 *
 * Displays plugin settings overview using component card format.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin_settings = $data['plugin_settings'] ?? array();

// Set up card header variables
$card_title = __( 'Settings Overview', '365i-queue-optimizer' );
$card_icon = 'dashicons-admin-generic';

// Set up footer actions
$footer_actions = array(
	array(
		'label' => __( 'Manage Settings', '365i-queue-optimizer' ),
		'url' => admin_url( 'options-general.php?page=queue-optimizer-settings' ),
		'type' => 'is-secondary',
	),
);

// Include card header
include plugin_dir_path( __FILE__ ) . '../partials/card-header.php';
?>

<div class="table-responsive">
	<table class="widefat striped components-table">
		<caption class="screen-reader-text"><?php esc_html_e( 'Plugin Settings Overview', '365i-queue-optimizer' ); ?></caption>
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Retention Days', '365i-queue-optimizer' ); ?></th>
				<td>
					<strong style="font-size: 18px; font-weight: 500;">
						<?php echo esc_html( $plugin_settings['retention_days'] ?? 30 ); ?>
					</strong>
					<span style="color: #646970;"><?php esc_html_e( ' days', '365i-queue-optimizer' ); ?></span>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Auto Cleanup', '365i-queue-optimizer' ); ?></th>
				<td>
					<?php if ( 'yes' === ( $plugin_settings['auto_cleanup'] ?? 'no' ) ) : ?>
						<span class="components-badge" style="background-color: #46b450; color: white; padding: 4px 8px; border-radius: 4px;">
							<span class="dashicons dashicons-yes-alt" style="font-size: 12px; margin-right: 4px;"></span>
							<?php esc_html_e( 'Enabled', '365i-queue-optimizer' ); ?>
						</span>
					<?php else : ?>
						<span class="components-badge" style="background-color: #646970; color: white; padding: 4px 8px; border-radius: 4px;">
							<span class="dashicons dashicons-dismiss" style="font-size: 12px; margin-right: 4px;"></span>
							<?php esc_html_e( 'Disabled', '365i-queue-optimizer' ); ?>
						</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Debug Mode', '365i-queue-optimizer' ); ?></th>
				<td>
					<?php if ( 'yes' === ( $plugin_settings['debug_mode'] ?? 'no' ) ) : ?>
						<span class="components-badge" style="background-color: #ffb900; color: white; padding: 4px 8px; border-radius: 4px;">
							<span class="dashicons dashicons-warning" style="font-size: 12px; margin-right: 4px;"></span>
							<?php esc_html_e( 'Enabled', '365i-queue-optimizer' ); ?>
						</span>
					<?php else : ?>
						<span class="components-badge" style="background-color: #46b450; color: white; padding: 4px 8px; border-radius: 4px;">
							<span class="dashicons dashicons-yes-alt" style="font-size: 12px; margin-right: 4px;"></span>
							<?php esc_html_e( 'Disabled', '365i-queue-optimizer' ); ?>
						</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Email Notifications', '365i-queue-optimizer' ); ?></th>
				<td>
					<?php if ( 'yes' === ( $plugin_settings['email_notifications'] ?? 'no' ) ) : ?>
						<span class="components-badge" style="background-color: #229fd8; color: white; padding: 4px 8px; border-radius: 4px;">
							<span class="dashicons dashicons-email-alt" style="font-size: 12px; margin-right: 4px;"></span>
							<?php esc_html_e( 'Enabled', '365i-queue-optimizer' ); ?>
						</span>
					<?php else : ?>
						<span class="components-badge" style="background-color: #646970; color: white; padding: 4px 8px; border-radius: 4px;">
							<span class="dashicons dashicons-dismiss" style="font-size: 12px; margin-right: 4px;"></span>
							<?php esc_html_e( 'Disabled', '365i-queue-optimizer' ); ?>
						</span>
					<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<?php
// Include card footer
include plugin_dir_path( __FILE__ ) . '../partials/card-footer.php';
?>