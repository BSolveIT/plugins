<?php
/**
 * System Information Page Template
 *
 * Main template for system diagnostics page.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set page variables for header.
$page_title = __( 'System Information', '365i-queue-optimizer' );
$page_description = __( 'Comprehensive system diagnostics and configuration details for troubleshooting and support.', '365i-queue-optimizer' );

// Include header.
include plugin_dir_path( __FILE__ ) . 'partials/header.php';
?>

<div class="queue-optimizer-system-info">

	<!-- System Information Controls -->
	<div class="system-info-controls" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding: 16px; background: #f9f9f9; border-radius: 4px;">
		<div class="system-info-search-container">
			<input type="search" id="system-info-search" placeholder="<?php esc_attr_e( 'Search system information...', '365i-queue-optimizer' ); ?>" class="regular-text" style="width: 300px;" />
		</div>
		<div class="system-info-export-container" style="display: flex; gap: 8px;">
			<button type="button" id="export-json" class="components-button is-secondary">
				<span class="dashicons dashicons-download" style="margin-right: 4px;"></span>
				<?php esc_html_e( 'Export JSON', '365i-queue-optimizer' ); ?>
			</button>
			<button type="button" id="export-csv" class="components-button is-secondary">
				<span class="dashicons dashicons-media-spreadsheet" style="margin-right: 4px;"></span>
				<?php esc_html_e( 'Export CSV', '365i-queue-optimizer' ); ?>
			</button>
		</div>
	</div>

	<div id="copy-success" class="notice notice-success is-dismissible" style="display: none;">
		<p></p>
	</div>

	<!-- System Information Grid -->
	<div class="components-grid" style="grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
		
		<!-- Left Column -->
		<div class="system-info-left-column" style="display: flex; flex-direction: column; gap: 24px;">
			<!-- Server Environment -->
			<?php include plugin_dir_path( __FILE__ ) . 'system-info/server-env.php'; ?>
			
			<!-- WordPress Configuration -->
			<?php include plugin_dir_path( __FILE__ ) . 'system-info/wp-config.php'; ?>
			
			<!-- PHP Extensions -->
			<?php include plugin_dir_path( __FILE__ ) . 'system-info/php-extensions.php'; ?>
		</div>

		<!-- Right Column -->
		<div class="system-info-right-column" style="display: flex; flex-direction: column; gap: 24px;">
			<!-- Database Information -->
			<?php include plugin_dir_path( __FILE__ ) . 'system-info/database-info.php'; ?>
			
			<!-- Theme Information -->
			<?php include plugin_dir_path( __FILE__ ) . 'system-info/theme-info.php'; ?>
			
			<!-- Queue System Status -->
			<?php include plugin_dir_path( __FILE__ ) . 'system-info/queue-status.php'; ?>
		</div>

	</div>

	<!-- Full Width Panels -->
	<div style="margin-bottom: 24px;">
		<!-- Plugins Table -->
		<?php include plugin_dir_path( __FILE__ ) . 'system-info/plugins-table.php'; ?>
	</div>

</div>

<!-- Make system info data available to JavaScript -->
<script type="text/javascript">
	var queueOptimizerSystemInfo = <?php echo wp_json_encode( $data ); ?>;
</script>

<?php
// Include footer.
include plugin_dir_path( __FILE__ ) . 'partials/footer.php';