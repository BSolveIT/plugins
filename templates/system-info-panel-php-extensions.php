<?php
/**
 * PHP Extensions Panel Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$php_extensions = $data['php_extensions'] ?? array();
?>

<div class="postbox">
	<div class="postbox-header">
		<h2 class="hndle">
			<span class="dashicons dashicons-admin-tools"></span>
			<?php esc_html_e( 'PHP Extensions', '365i-queue-optimizer' ); ?>
			<span class="extension-count">(<?php echo count( $php_extensions ); ?>)</span>
		</h2>
		<div class="handle-actions">
			<button type="button" class="button button-link copy-section" data-section="php_extensions">
				<span class="dashicons dashicons-clipboard"></span>
				<?php esc_html_e( 'Copy', '365i-queue-optimizer' ); ?>
			</button>
		</div>
	</div>
	<div class="inside">
		<div class="extensions-grid">
			<?php foreach ( $php_extensions as $extension ) : ?>
				<div class="extension-item <?php echo $extension['important'] ? 'important' : ''; ?>">
					<div class="extension-name">
						<?php echo esc_html( $extension['name'] ); ?>
						<?php if ( $extension['important'] ) : ?>
							<span class="important-badge" title="<?php esc_attr_e( 'Important for WordPress', '365i-queue-optimizer' ); ?>">★</span>
						<?php endif; ?>
					</div>
					<div class="extension-version">
						<code><?php echo esc_html( $extension['version'] ); ?></code>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		
		<div class="extensions-legend">
			<p>
				<span class="important-badge">★</span>
				<?php esc_html_e( 'Important extensions for WordPress functionality', '365i-queue-optimizer' ); ?>
			</p>
		</div>
	</div>
</div>