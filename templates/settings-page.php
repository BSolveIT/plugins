<?php
/**
 * Settings Page Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include header partial.
include plugin_dir_path( __FILE__ ) . 'partials/header.php';
?>

<div class="wrap">
	<h1><?php echo esc_html( $template_data['page_title'] ); ?></h1>
	
	<?php settings_errors(); ?>
	
	<div class="queue-optimizer-settings-container">
		<div class="settings-panel">
			<form method="post" action="options.php">
				<?php
				settings_fields( 'queue_optimizer_settings' );
				do_settings_sections( 'queue_optimizer_settings' );
				submit_button();
				?>
			</form>
		</div>
		
		<div class="status-panel">
			<h2><?php esc_html_e( 'Queue Status', '365i-queue-optimizer' ); ?></h2>
			<div class="status-info">
				<?php if ( ! empty( $template_data['status'] ) ) : ?>
					<p><strong><?php esc_html_e( 'Pending:', '365i-queue-optimizer' ); ?></strong>
					   <?php echo esc_html( $template_data['status']['pending'] ?? 0 ); ?></p>
					<p><strong><?php esc_html_e( 'Processing:', '365i-queue-optimizer' ); ?></strong>
					   <?php echo esc_html( $template_data['status']['processing'] ?? 0 ); ?></p>
					<p><strong><?php esc_html_e( 'Completed:', '365i-queue-optimizer' ); ?></strong>
					   <?php echo esc_html( $template_data['status']['completed'] ?? 0 ); ?></p>
					<p><strong><?php esc_html_e( 'Failed:', '365i-queue-optimizer' ); ?></strong>
					   <?php echo esc_html( $template_data['status']['failed'] ?? 0 ); ?></p>
				<?php else : ?>
					<p><?php esc_html_e( 'Queue status information unavailable.', '365i-queue-optimizer' ); ?></p>
				<?php endif; ?>
			</div>
			
			<div class="dashboard-buttons" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #dcdcde;">
				<p><strong><?php esc_html_e( 'Last Run:', '365i-queue-optimizer' ); ?></strong>
				   <?php echo esc_html( date( 'F j, Y g:i:s A', $template_data['status']['last_run'] ?? time() ) ); ?></p>
				
				<div style="margin-top: 15px;">
					<button type="button" id="run-queue-now" class="button button-primary" style="margin-right: 8px;">
						<?php esc_html_e( 'Run Now', '365i-queue-optimizer' ); ?>
					</button>
					<button type="button" id="view-logs" class="button button-secondary" style="margin-right: 8px;">
						<?php esc_html_e( 'View Logs', '365i-queue-optimizer' ); ?>
					</button>
					<button type="button" id="clear-logs" class="button button-secondary" style="margin-right: 8px;">
						<?php esc_html_e( 'Clear Plugin Logs', '365i-queue-optimizer' ); ?>
					</button>
					<button type="button" id="clear-action-scheduler-logs" class="button button-secondary">
						<?php esc_html_e( 'Clear Action Scheduler Logs', '365i-queue-optimizer' ); ?>
					</button>
				</div>
				
				<div id="queue-optimizer-logs" style="display: none; margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #dcdcde; border-radius: 4px;">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
						<h4 style="margin: 0;"><?php esc_html_e( 'Recent Logs', '365i-queue-optimizer' ); ?></h4>
						<div>
							<button type="button" id="refresh-logs" class="button button-small" style="margin-right: 5px;">
								<?php esc_html_e( 'Refresh', '365i-queue-optimizer' ); ?>
							</button>
							<button type="button" id="close-logs" class="button button-small">
								<?php esc_html_e( 'Close', '365i-queue-optimizer' ); ?>
							</button>
						</div>
					</div>
					<pre id="log-display" style="white-space: pre-wrap; max-height: 300px; overflow-y: auto; font-size: 12px; background: #fff; padding: 10px; border: 1px solid #dcdcde; border-radius: 3px;"></pre>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
// Include footer partial.
include plugin_dir_path( __FILE__ ) . 'partials/footer.php';
?>