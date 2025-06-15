<?php
/**
 * System Info - Plugins Table Panel
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$card_title = 'Installed Plugins';
$card_icon = 'dashicons-admin-plugins';
$card_id = 'plugins-table';
include plugin_dir_path( __FILE__ ) . '../partials/card-header.php';
?>

<div class="components-card__body">
	<div class="system-info-search" style="margin-bottom: 16px;">
		<input type="search" 
			id="plugins-search" 
			placeholder="Search plugins..." 
			class="regular-text" 
			style="width: 100%; max-width: 300px;"
			aria-label="Search installed plugins">
	</div>

	<div class="table-wrapper" style="overflow-x: auto;">
		<table class="widefat striped components-table" id="plugins-info-table">
			<caption class="screen-reader-text">Installed WordPress Plugins Information</caption>
			<thead>
				<tr>
					<th scope="col" style="width: 25%;">Plugin Name</th>
					<th scope="col" style="width: 10%;">Version</th>
					<th scope="col" style="width: 10%;">Status</th>
					<th scope="col" style="width: 20%;">Author</th>
					<th scope="col" style="width: 35%;">Description</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $data['plugins'] ) && is_array( $data['plugins'] ) ) : ?>
					<?php foreach ( $data['plugins'] as $plugin_file => $plugin_data ) : ?>
						<tr class="plugin-row" data-plugin-name="<?php echo esc_attr( strtolower( $plugin_data['Name'] ?? '' ) ); ?>">
							<td>
								<strong><?php echo esc_html( $plugin_data['Name'] ?? 'Unknown' ); ?></strong>
								<?php if ( ! empty( $plugin_data['PluginURI'] ) ) : ?>
									<br>
									<a href="<?php echo esc_url( $plugin_data['PluginURI'] ); ?>" target="_blank" rel="noopener noreferrer" style="font-size: 12px; color: #0073aa;">
										<?php echo esc_html( $plugin_data['PluginURI'] ); ?>
									</a>
								<?php endif; ?>
							</td>
							<td>
								<code><?php echo esc_html( $plugin_data['Version'] ?? 'Unknown' ); ?></code>
							</td>
							<td>
								<?php 
								$is_active = $plugin_data['is_active'] ?? false;
								$is_mu_plugin = $plugin_data['is_mu_plugin'] ?? false;
								?>
								<?php if ( $is_mu_plugin ) : ?>
									<span class="components-badge" style="background-color: #229fd8; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
										Must-Use
									</span>
								<?php elseif ( $is_active ) : ?>
									<span class="components-badge is-success" style="background-color: #46b450; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
										Active
									</span>
								<?php else : ?>
									<span class="components-badge" style="background-color: #dcdcde; color: #1d2327; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
										Inactive
									</span>
								<?php endif; ?>
							</td>
							<td>
								<?php echo esc_html( $plugin_data['Author'] ?? 'Unknown' ); ?>
								<?php if ( ! empty( $plugin_data['AuthorURI'] ) ) : ?>
									<br>
									<a href="<?php echo esc_url( $plugin_data['AuthorURI'] ); ?>" target="_blank" rel="noopener noreferrer" style="font-size: 12px; color: #0073aa;">
										Author URI
									</a>
								<?php endif; ?>
							</td>
							<td>
								<div style="max-width: 300px; word-wrap: break-word;">
									<?php 
									$description = $plugin_data['Description'] ?? '';
									if ( strlen( $description ) > 150 ) {
										echo esc_html( substr( $description, 0, 150 ) ) . '...';
									} else {
										echo esc_html( $description );
									}
									?>
								</div>
								<?php if ( ! empty( $plugin_data['TextDomain'] ) ) : ?>
									<div style="margin-top: 4px; font-size: 12px; color: #666;">
										Text Domain: <code><?php echo esc_html( $plugin_data['TextDomain'] ); ?></code>
									</div>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="5" style="text-align: center; color: #666; padding: 24px;">
							No plugin information available.
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php if ( ! empty( $data['plugins'] ) ) : ?>
		<div style="margin-top: 12px; padding: 8px; background: #f6f7f7; border-radius: 4px; font-size: 12px; color: #646970;">
			<strong>Total Plugins:</strong> <?php echo count( $data['plugins'] ); ?> |
			<strong>Active:</strong> <?php echo count( array_filter( $data['plugins'], function( $plugin ) { return $plugin['is_active'] ?? false; } ) ); ?> |
			<strong>Must-Use:</strong> <?php echo count( array_filter( $data['plugins'], function( $plugin ) { return $plugin['is_mu_plugin'] ?? false; } ) ); ?>
		</div>
	<?php endif; ?>
</div>

<?php
$footer_actions = array(
	array(
		'text' => 'Copy Plugins Info',
		'onclick' => 'copyToClipboard(\'plugins-table\')',
		'class' => 'is-secondary',
		'icon' => 'dashicons-clipboard'
	),
	array(
		'text' => 'Export Plugin List',
		'onclick' => 'exportPluginList()',
		'class' => 'is-secondary',
		'icon' => 'dashicons-download'
	)
);
include plugin_dir_path( __FILE__ ) . '../partials/card-footer.php';
?>