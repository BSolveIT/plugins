<?php
/**
 * System Info Page Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php esc_html_e( '365i Queue Optimizer - System Info', '365i-queue-optimizer' ); ?></h1>
	
	<div class="queue-optimizer-system-info">
		<!-- Header Actions -->
		<div class="system-info-actions">
			<div class="search-container">
				<label for="system-info-search" class="screen-reader-text"><?php esc_html_e( 'Search system info', '365i-queue-optimizer' ); ?></label>
				<input type="text" id="system-info-search" placeholder="<?php esc_attr_e( 'Search system info...', '365i-queue-optimizer' ); ?>" />
				<span class="dashicons dashicons-search"></span>
			</div>
			
			<div class="export-actions">
				<button type="button" class="button button-secondary" id="export-json">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Export JSON', '365i-queue-optimizer' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="export-csv">
					<span class="dashicons dashicons-media-spreadsheet"></span>
					<?php esc_html_e( 'Export CSV', '365i-queue-optimizer' ); ?>
				</button>
			</div>
		</div>

		<!-- System Info Panels -->
		<div class="postbox-container">
			<div class="meta-box-sortables">

				<!-- Queue System Info -->
				<div class="postbox" data-section="queue-system">
					<div class="postbox-header">
						<h2 class="hndle">
							<span><?php esc_html_e( 'Queue System Status', '365i-queue-optimizer' ); ?></span>
						</h2>
						<div class="handle-actions">
							<button type="button" class="handlediv hide-if-no-js" aria-expanded="true">
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel', '365i-queue-optimizer' ); ?></span>
								<span class="toggle-indicator" aria-hidden="true"></span>
							</button>
							<button type="button" class="copy-section button button-small" data-section="queue_system">
								<span class="dashicons dashicons-clipboard"></span>
								<?php esc_html_e( 'Copy', '365i-queue-optimizer' ); ?>
							</button>
						</div>
					</div>
					<div class="inside">
						<table class="widefat fixed striped">
							<tbody>
								<?php foreach ( $system_info['queue_system'] as $key => $value ) : ?>
									<tr data-key="<?php echo esc_attr( $key ); ?>">
										<td class="info-key"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?></td>
										<td class="info-value"><?php echo esc_html( $value ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Server Environment -->
				<div class="postbox" data-section="server">
					<div class="postbox-header">
						<h2 class="hndle">
							<span><?php esc_html_e( 'Server Environment', '365i-queue-optimizer' ); ?></span>
						</h2>
						<div class="handle-actions">
							<button type="button" class="handlediv hide-if-no-js" aria-expanded="true">
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel', '365i-queue-optimizer' ); ?></span>
								<span class="toggle-indicator" aria-hidden="true"></span>
							</button>
							<button type="button" class="copy-section button button-small" data-section="server">
								<span class="dashicons dashicons-clipboard"></span>
								<?php esc_html_e( 'Copy', '365i-queue-optimizer' ); ?>
							</button>
						</div>
					</div>
					<div class="inside">
						<table class="widefat fixed striped">
							<tbody>
								<?php foreach ( $system_info['server'] as $key => $value ) : ?>
									<tr data-key="<?php echo esc_attr( $key ); ?>">
										<td class="info-key"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?></td>
										<td class="info-value"><?php echo esc_html( $value ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Database -->
				<div class="postbox" data-section="database">
					<div class="postbox-header">
						<h2 class="hndle">
							<span><?php esc_html_e( 'Database', '365i-queue-optimizer' ); ?></span>
						</h2>
						<div class="handle-actions">
							<button type="button" class="handlediv hide-if-no-js" aria-expanded="true">
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel', '365i-queue-optimizer' ); ?></span>
								<span class="toggle-indicator" aria-hidden="true"></span>
							</button>
							<button type="button" class="copy-section button button-small" data-section="database">
								<span class="dashicons dashicons-clipboard"></span>
								<?php esc_html_e( 'Copy', '365i-queue-optimizer' ); ?>
							</button>
						</div>
					</div>
					<div class="inside">
						<table class="widefat fixed striped">
							<tbody>
								<?php foreach ( $system_info['database'] as $key => $value ) : ?>
									<tr data-key="<?php echo esc_attr( $key ); ?>">
										<td class="info-key"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?></td>
										<td class="info-value"><?php echo esc_html( $value ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>

				<!-- WordPress Core -->
				<div class="postbox" data-section="wordpress">
					<div class="postbox-header">
						<h2 class="hndle">
							<span><?php esc_html_e( 'WordPress Core', '365i-queue-optimizer' ); ?></span>
						</h2>
						<div class="handle-actions">
							<button type="button" class="handlediv hide-if-no-js" aria-expanded="true">
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel', '365i-queue-optimizer' ); ?></span>
								<span class="toggle-indicator" aria-hidden="true"></span>
							</button>
							<button type="button" class="copy-section button button-small" data-section="wordpress">
								<span class="dashicons dashicons-clipboard"></span>
								<?php esc_html_e( 'Copy', '365i-queue-optimizer' ); ?>
							</button>
						</div>
					</div>
					<div class="inside">
						<table class="widefat fixed striped">
							<tbody>
								<?php foreach ( $system_info['wordpress'] as $key => $value ) : ?>
									<tr data-key="<?php echo esc_attr( $key ); ?>">
										<td class="info-key"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?></td>
										<td class="info-value"><?php echo esc_html( $value ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Active Theme -->
				<div class="postbox" data-section="theme">
					<div class="postbox-header">
						<h2 class="hndle">
							<span><?php esc_html_e( 'Active Theme', '365i-queue-optimizer' ); ?></span>
						</h2>
						<div class="handle-actions">
							<button type="button" class="handlediv hide-if-no-js" aria-expanded="true">
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel', '365i-queue-optimizer' ); ?></span>
								<span class="toggle-indicator" aria-hidden="true"></span>
							</button>
							<button type="button" class="copy-section button button-small" data-section="theme">
								<span class="dashicons dashicons-clipboard"></span>
								<?php esc_html_e( 'Copy', '365i-queue-optimizer' ); ?>
							</button>
						</div>
					</div>
					<div class="inside">
						<table class="widefat fixed striped">
							<tbody>
								<?php foreach ( $system_info['theme'] as $key => $value ) : ?>
									<tr data-key="<?php echo esc_attr( $key ); ?>">
										<td class="info-key"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?></td>
										<td class="info-value"><?php echo esc_html( $value ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Active Plugins -->
				<div class="postbox" data-section="plugins">
					<div class="postbox-header">
						<h2 class="hndle">
							<span><?php esc_html_e( 'Plugins', '365i-queue-optimizer' ); ?></span>
						</h2>
						<div class="handle-actions">
							<button type="button" class="handlediv hide-if-no-js" aria-expanded="true">
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel', '365i-queue-optimizer' ); ?></span>
								<span class="toggle-indicator" aria-hidden="true"></span>
							</button>
							<button type="button" class="copy-section button button-small" data-section="plugins">
								<span class="dashicons dashicons-clipboard"></span>
								<?php esc_html_e( 'Copy', '365i-queue-optimizer' ); ?>
							</button>
						</div>
					</div>
					<div class="inside">
						<div class="plugin-search-container">
							<input type="text" id="plugin-search" placeholder="<?php esc_attr_e( 'Filter plugins...', '365i-queue-optimizer' ); ?>" />
						</div>
						<table class="widefat fixed striped">
							<thead>
								<tr>
									<th class="manage-column"><?php esc_html_e( 'Name', '365i-queue-optimizer' ); ?></th>
									<th class="manage-column"><?php esc_html_e( 'Version', '365i-queue-optimizer' ); ?></th>
									<th class="manage-column"><?php esc_html_e( 'Author', '365i-queue-optimizer' ); ?></th>
									<th class="manage-column"><?php esc_html_e( 'Status', '365i-queue-optimizer' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $system_info['plugins'] as $plugin ) : ?>
									<tr class="plugin-row" data-name="<?php echo esc_attr( strtolower( $plugin['name'] ) ); ?>" data-status="<?php echo esc_attr( strtolower( $plugin['status'] ) ); ?>">
										<td class="plugin-name">
											<strong><?php echo esc_html( $plugin['name'] ); ?></strong>
										</td>
										<td class="plugin-version"><?php echo esc_html( $plugin['version'] ); ?></td>
										<td class="plugin-author"><?php echo esc_html( $plugin['author'] ); ?></td>
										<td class="plugin-status">
											<span class="status-badge status-<?php echo esc_attr( strtolower( $plugin['status'] ) ); ?>">
												<?php echo esc_html( $plugin['status'] ); ?>
											</span>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>

				<!-- PHP Extensions -->
				<div class="postbox" data-section="php-extensions">
					<div class="postbox-header">
						<h2 class="hndle">
							<span><?php esc_html_e( 'PHP Extensions', '365i-queue-optimizer' ); ?></span>
						</h2>
						<div class="handle-actions">
							<button type="button" class="handlediv hide-if-no-js" aria-expanded="true">
								<span class="screen-reader-text"><?php esc_html_e( 'Toggle panel', '365i-queue-optimizer' ); ?></span>
								<span class="toggle-indicator" aria-hidden="true"></span>
							</button>
							<button type="button" class="copy-section button button-small" data-section="php_extensions">
								<span class="dashicons dashicons-clipboard"></span>
								<?php esc_html_e( 'Copy', '365i-queue-optimizer' ); ?>
							</button>
						</div>
					</div>
					<div class="inside">
						<div class="extensions-grid">
							<?php foreach ( $system_info['php_extensions'] as $extension ) : ?>
								<div class="extension-item <?php echo $extension['important'] ? 'important' : ''; ?>">
									<div class="extension-name"><?php echo esc_html( $extension['name'] ); ?></div>
									<div class="extension-version"><?php echo esc_html( $extension['version'] ); ?></div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

			</div>
		</div>

		<!-- Copy Success Message -->
		<div id="copy-success" class="notice notice-success is-dismissible" style="display: none;">
			<p><?php esc_html_e( 'Copied to clipboard!', '365i-queue-optimizer' ); ?></p>
		</div>

		<!-- Raw JSON Data (hidden) -->
		<script type="application/json" id="system-info-data">
			<?php echo wp_json_encode( $system_info ); ?>
		</script>
	</div>
</div>