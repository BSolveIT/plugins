<?php
/**
 * System Info - Server Environment Panel
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$card_title = 'Server Environment';
$card_icon = 'dashicons-admin-tools';
$card_id = 'server-environment';
include plugin_dir_path( __FILE__ ) . '../partials/card-header.php';
?>

<div class="components-card__body">
	<table class="widefat striped components-table">
		<caption class="screen-reader-text">Server Environment Information</caption>
		<tbody>
			<tr>
				<th scope="row" style="width: 30%;">Operating System</th>
				<td>
					<code><?php echo esc_html( $data['server']['os'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Web Server</th>
				<td>
					<code><?php echo esc_html( $data['server']['software'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">PHP Version</th>
				<td>
					<code><?php echo esc_html( $data['server']['php_version'] ?? 'Unknown' ); ?></code>
					<?php if ( ! empty( $data['server']['php_version_status'] ) ) : ?>
						<span class="components-badge <?php echo esc_attr( $data['server']['php_version_status'] === 'good' ? 'is-success' : 'is-warning' ); ?>"
							style="margin-left: 8px; background-color: <?php echo $data['server']['php_version_status'] === 'good' ? '#46b450' : '#ffb900'; ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
							<?php echo esc_html( ucfirst( $data['server']['php_version_status'] ) ); ?>
						</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row">Memory Limit</th>
				<td>
					<code><?php echo esc_html( $data['server']['memory_limit'] ?? 'Unknown' ); ?></code>
					<?php if ( ! empty( $data['server']['memory_usage'] ) ) : ?>
						<span style="color: #666; margin-left: 8px;">
							(<?php echo esc_html( $data['server']['memory_usage'] ); ?> used)
						</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row">Max Execution Time</th>
				<td>
					<code><?php echo esc_html( $data['server']['max_execution_time'] ?? 'Unknown' ); ?> seconds</code>
				</td>
			</tr>
			<tr>
				<th scope="row">Upload Max Filesize</th>
				<td>
					<code><?php echo esc_html( $data['server']['upload_max_filesize'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Post Max Size</th>
				<td>
					<code><?php echo esc_html( $data['server']['post_max_size'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Max Input Vars</th>
				<td>
					<code><?php echo esc_html( $data['server']['max_input_vars'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<?php
$footer_actions = array(
	array(
		'text' => 'Copy Server Info',
		'onclick' => 'copyToClipboard(\'server-environment\')',
		'class' => 'is-secondary',
		'icon' => 'dashicons-clipboard'
	)
);
include plugin_dir_path( __FILE__ ) . '../partials/card-footer.php';
?>