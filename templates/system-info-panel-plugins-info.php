<?php
/**
 * Plugins Information Panel Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugins = $data['plugins'] ?? array();
?>

<div class="postbox">
	<div class="postbox-header">
		<h2 class="hndle">
			<span class="dashicons dashicons-admin-plugins"></span>
			<?php esc_html_e( 'Installed Plugins', '365i-queue-optimizer' ); ?>
			<span class="plugin-count">(<?php echo count( $plugins ); ?>)</span>
		</h2>
		<div class="handle-actions">
			<button type="button" class="button button-link copy-section" data-section="plugins">
				<span class="dashicons dashicons-clipboard"></span>
				<?php esc_html_e( 'Copy', '365i-queue-optimizer' ); ?>
			</button>
		</div>
	</div>
	<div class="inside">
		<div class="plugin-search-container">
			<input type="search" id="plugin-search" placeholder="<?php esc_attr_e( 'Search plugins...', '365i-queue-optimizer' ); ?>" class="regular-text" />
		</div>
		
		<div class="plugins-table-container">
			<table class="wp-list-table widefat fixed striped plugins">
				<thead>
					<tr>
						<th class="manage-column column-name"><?php esc_html_e( 'Plugin', '365i-queue-optimizer' ); ?></th>
						<th class="manage-column column-version"><?php esc_html_e( 'Version', '365i-queue-optimizer' ); ?></th>
						<th class="manage-column column-status"><?php esc_html_e( 'Status', '365i-queue-optimizer' ); ?></th>
						<th class="manage-column column-author"><?php esc_html_e( 'Author', '365i-queue-optimizer' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $plugins as $plugin ) : ?>
						<tr class="plugin-row" data-name="<?php echo esc_attr( strtolower( $plugin['name'] ) ); ?>" data-status="<?php echo esc_attr( $plugin['status'] ); ?>">
							<td class="plugin-title">
								<strong><?php echo esc_html( $plugin['name'] ); ?></strong>
								<?php if ( ! empty( $plugin['description'] ) ) : ?>
									<div class="plugin-description">
										<?php echo esc_html( wp_trim_words( $plugin['description'], 15 ) ); ?>
									</div>
								<?php endif; ?>
							</td>
							<td class="plugin-version">
								<code><?php echo esc_html( $plugin['version'] ); ?></code>
							</td>
							<td class="plugin-status">
								<span class="status-<?php echo esc_attr( $plugin['status'] ); ?>">
									<?php echo esc_html( ucfirst( $plugin['status'] ) ); ?>
								</span>
							</td>
							<td class="plugin-author">
								<?php if ( ! empty( $plugin['plugin_uri'] ) ) : ?>
									<a href="<?php echo esc_url( $plugin['plugin_uri'] ); ?>" target="_blank">
										<?php echo esc_html( $plugin['author'] ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html( $plugin['author'] ); ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>