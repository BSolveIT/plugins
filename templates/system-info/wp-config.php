<?php
/**
 * System Info - WordPress Configuration Panel
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$card_title = 'WordPress Configuration';
$card_icon = 'dashicons-wordpress';
$card_id = 'wordpress-configuration';
include plugin_dir_path( __FILE__ ) . '../partials/card-header.php';
?>

<div class="components-card__body">
	<table class="widefat striped components-table">
		<caption class="screen-reader-text">WordPress Configuration Information</caption>
		<tbody>
			<tr>
				<th scope="row" style="width: 30%;">WordPress Version</th>
				<td>
					<code><?php echo esc_html( $data['wordpress']['version'] ?? 'Unknown' ); ?></code>
					<?php if ( ! empty( $data['wordpress']['version_status'] ) ) : ?>
						<span class="components-badge <?php echo esc_attr( $data['wordpress']['version_status'] === 'latest' ? 'is-success' : 'is-warning' ); ?>"
							style="margin-left: 8px; background-color: <?php echo $data['wordpress']['version_status'] === 'latest' ? '#46b450' : '#ffb900'; ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
							<?php echo esc_html( ucfirst( $data['wordpress']['version_status'] ) ); ?>
						</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row">Site URL</th>
				<td>
					<code><?php echo esc_html( $data['wordpress']['site_url'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Home URL</th>
				<td>
					<code><?php echo esc_html( $data['wordpress']['home_url'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Debug Mode</th>
				<td>
					<?php $debug_mode = $data['wordpress']['debug_mode'] ?? false; ?>
					<code><?php echo $debug_mode ? 'Enabled' : 'Disabled'; ?></code>
					<span class="components-badge <?php echo esc_attr( $debug_mode ? 'is-warning' : 'is-success' ); ?>"
						style="margin-left: 8px; background-color: <?php echo $debug_mode ? '#ffb900' : '#46b450'; ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
						<?php echo $debug_mode ? 'On' : 'Off'; ?>
					</span>
				</td>
			</tr>
			<tr>
				<th scope="row">Debug Log</th>
				<td>
					<?php $debug_log = $data['wordpress']['debug_log'] ?? false; ?>
					<code><?php echo $debug_log ? 'Enabled' : 'Disabled'; ?></code>
					<span class="components-badge <?php echo esc_attr( $debug_log ? 'is-warning' : 'is-success' ); ?>"
						style="margin-left: 8px; background-color: <?php echo $debug_log ? '#ffb900' : '#46b450'; ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
						<?php echo $debug_log ? 'On' : 'Off'; ?>
					</span>
				</td>
			</tr>
			<tr>
				<th scope="row">Script Debug</th>
				<td>
					<?php $script_debug = $data['wordpress']['script_debug'] ?? false; ?>
					<code><?php echo $script_debug ? 'Enabled' : 'Disabled'; ?></code>
					<span class="components-badge <?php echo esc_attr( $script_debug ? 'is-warning' : 'is-success' ); ?>"
						style="margin-left: 8px; background-color: <?php echo $script_debug ? '#ffb900' : '#46b450'; ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
						<?php echo $script_debug ? 'On' : 'Off'; ?>
					</span>
				</td>
			</tr>
			<tr>
				<th scope="row">Multisite</th>
				<td>
					<?php $multisite = $data['wordpress']['multisite'] ?? false; ?>
					<code><?php echo $multisite ? 'Yes' : 'No'; ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Language</th>
				<td>
					<code><?php echo esc_html( $data['wordpress']['language'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<?php
$footer_actions = array(
	array(
		'text' => 'Copy WordPress Info',
		'onclick' => 'copyToClipboard(\'wordpress-configuration\')',
		'class' => 'is-secondary',
		'icon' => 'dashicons-clipboard'
	)
);
include plugin_dir_path( __FILE__ ) . '../partials/card-footer.php';
?>