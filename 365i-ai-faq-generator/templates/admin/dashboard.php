<?php
/**
 * Admin dashboard template for 365i AI FAQ Generator.
 * 
 * This template displays the main dashboard with plugin overview,
 * worker status, and configuration information. No FAQ generation here.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Templates
 * @since 2.0.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include header.
include AI_FAQ_GEN_DIR . 'templates/partials/header.php';

// Get plugin options and worker status.
$options = get_option( 'ai_faq_gen_options', array() );
$workers = isset( $options['workers'] ) ? $options['workers'] : array();
$settings = isset( $options['settings'] ) ? $options['settings'] : array();

// Calculate worker statistics.
$total_workers = count( $workers );
$enabled_workers = 0;
$worker_status = array();

foreach ( $workers as $worker_name => $config ) {
	if ( $config['enabled'] ) {
		$enabled_workers++;
	}
	
	$worker_status[ $worker_name ] = array(
		'name' => $worker_name,
		'enabled' => $config['enabled'],
		'url' => $config['url'],
		'rate_limit' => $config['rate_limit'],
		'usage' => get_transient( 'ai_faq_rate_limit_' . $worker_name ) ?: 0,
	);
}
?>

<div class="ai-faq-gen-dashboard">
	
	<!-- Welcome Section - Header Only -->
	<div class="ai-faq-gen-welcome">
		<div class="welcome-panel-content">
			<h2><?php esc_html_e( 'Welcome to 365i AI FAQ Generator', '365i-ai-faq-generator' ); ?></h2>
			<p class="about-description">
				<?php esc_html_e( 'Configure your AI-powered FAQ generation system. This plugin provides a frontend-only FAQ generator tool that clients can use directly through shortcodes. All FAQ generation happens on the frontend - use this admin area only for configuration.', '365i-ai-faq-generator' ); ?>
			</p>
		</div>
	</div>

	<!-- Configuration Cards - Full Width Section -->
	<div>
		<div class="welcome-panel-column-container">
			<div class="welcome-panel-column">
				<h3><?php esc_html_e( 'Frontend Tool', '365i-ai-faq-generator' ); ?></h3>
				<p><?php esc_html_e( 'All FAQ generation happens on the frontend using shortcodes. There is no backend FAQ creation interface.', '365i-ai-faq-generator' ); ?></p>
				<code>[ai_faq_generator]</code>
				<p><small><?php esc_html_e( 'Add this shortcode to any page or post to embed the FAQ generator tool.', '365i-ai-faq-generator' ); ?></small></p>
			</div>
			
			<div class="welcome-panel-column">
				<h3><?php esc_html_e( 'Configure Workers', '365i-ai-faq-generator' ); ?></h3>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ai-faq-generator-workers' ) ); ?>">
					<span class="dashicons dashicons-networking"></span>
					<?php esc_html_e( 'Worker Settings', '365i-ai-faq-generator' ); ?>
				</a>
				<p><?php esc_html_e( 'Configure and monitor your Cloudflare AI workers for optimal performance.', '365i-ai-faq-generator' ); ?></p>
			</div>
			
			<div class="welcome-panel-column">
				<h3><?php esc_html_e( 'Plugin Settings', '365i-ai-faq-generator' ); ?></h3>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ai-faq-generator-settings' ) ); ?>">
					<span class="dashicons dashicons-admin-settings"></span>
					<?php esc_html_e( 'General Settings', '365i-ai-faq-generator' ); ?>
				</a>
				<p><?php esc_html_e( 'Configure default settings and debug options for the frontend tool.', '365i-ai-faq-generator' ); ?></p>
			</div>
		</div>
	</div>

	<!-- Stats Overview -->
	<div class="ai-faq-gen-stats">
		<div class="stats-container">
			<div class="stat-box">
				<div class="stat-number"><?php echo esc_html( $total_workers ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'Total Workers', '365i-ai-faq-generator' ); ?></div>
			</div>
			
			<div class="stat-box">
				<div class="stat-number"><?php echo esc_html( $enabled_workers ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'Active Workers', '365i-ai-faq-generator' ); ?></div>
			</div>
			
			<div class="stat-box">
				<div class="stat-number"><?php echo isset( $settings['default_faq_count'] ) ? esc_html( $settings['default_faq_count'] ) : '12'; ?></div>
				<div class="stat-label"><?php esc_html_e( 'Default FAQ Count', '365i-ai-faq-generator' ); ?></div>
			</div>
			
			<div class="stat-box">
				<div class="stat-number"><?php echo isset( $settings['auto_save_interval'] ) ? esc_html( $settings['auto_save_interval'] ) : '3'; ?>m</div>
				<div class="stat-label"><?php esc_html_e( 'Auto-save Interval', '365i-ai-faq-generator' ); ?></div>
			</div>
		</div>
	</div>

	<div class="ai-faq-gen-main-content">
		
		<!-- Worker Status -->
		<div class="ai-faq-gen-section worker-status-section">
			<h3>
				<span class="dashicons dashicons-networking"></span>
				<?php esc_html_e( 'Worker Status', '365i-ai-faq-generator' ); ?>
			</h3>
			
			<?php if ( empty( $workers ) ) : ?>
				<div class="notice notice-warning">
					<p>
						<?php esc_html_e( 'No workers configured. Please configure your Cloudflare workers to enable frontend FAQ generation.', '365i-ai-faq-generator' ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-faq-generator-workers' ) ); ?>" class="button button-small">
							<?php esc_html_e( 'Configure Workers', '365i-ai-faq-generator' ); ?>
						</a>
					</p>
				</div>
			<?php else : ?>
				<div class="worker-status-grid">
					<?php foreach ( $worker_status as $worker ) : ?>
						<div class="worker-card <?php echo $worker['enabled'] ? 'enabled' : 'disabled'; ?>">
							<div class="worker-header">
								<h4><?php echo esc_html( ucwords( str_replace( '_', ' ', $worker['name'] ) ) ); ?></h4>
								<span class="status-indicator">
									<?php if ( $worker['enabled'] ) : ?>
										<span class="dashicons dashicons-yes-alt"></span>
										<?php esc_html_e( 'Enabled', '365i-ai-faq-generator' ); ?>
									<?php else : ?>
										<span class="dashicons dashicons-dismiss"></span>
										<?php esc_html_e( 'Disabled', '365i-ai-faq-generator' ); ?>
									<?php endif; ?>
								</span>
							</div>
							
							<div class="worker-details">
								<p><strong><?php esc_html_e( 'URL:', '365i-ai-faq-generator' ); ?></strong><br>
								<code><?php echo esc_html( $worker['url'] ); ?></code></p>
								
								<p><strong><?php esc_html_e( 'Rate Limit:', '365i-ai-faq-generator' ); ?></strong><br>
								<?php echo esc_html( $worker['rate_limit'] ); ?> <?php esc_html_e( 'requests/hour', '365i-ai-faq-generator' ); ?></p>
								
								<p><strong><?php esc_html_e( 'Current Usage:', '365i-ai-faq-generator' ); ?></strong><br>
								<?php echo esc_html( $worker['usage'] ); ?> / <?php echo esc_html( $worker['rate_limit'] ); ?></p>
								
								<?php if ( $worker['enabled'] ) : ?>
									<div class="usage-bar">
										<?php $usage_percent = $worker['rate_limit'] > 0 ? ( $worker['usage'] / $worker['rate_limit'] ) * 100 : 0; ?>
										<div class="usage-fill" style="width: <?php echo esc_attr( min( $usage_percent, 100 ) ); ?>%"></div>
									</div>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Shortcode Information -->
		<div class="ai-faq-gen-section shortcode-help-section">
			<h3>
				<span class="dashicons dashicons-editor-code"></span>
				<?php esc_html_e( 'Frontend Usage', '365i-ai-faq-generator' ); ?>
			</h3>
			
			<p><?php esc_html_e( 'Use these shortcodes to embed the FAQ generator tool on your website:', '365i-ai-faq-generator' ); ?></p>
			
			<div class="shortcode-examples">
				<div class="example-item">
					<h4><?php esc_html_e( 'Basic Usage', '365i-ai-faq-generator' ); ?></h4>
					<code>[ai_faq_generator]</code>
					<p><?php esc_html_e( 'Displays the full FAQ generator with default settings.', '365i-ai-faq-generator' ); ?></p>
				</div>
				
				<div class="example-item">
					<h4><?php esc_html_e( 'With Custom Topic', '365i-ai-faq-generator' ); ?></h4>
					<code>[ai_faq_generator topic="WordPress Development" count="8"]</code>
					<p><?php esc_html_e( 'Pre-fills the topic field and sets a custom FAQ count.', '365i-ai-faq-generator' ); ?></p>
				</div>
				
				<div class="example-item">
					<h4><?php esc_html_e( 'Minimal Theme', '365i-ai-faq-generator' ); ?></h4>
					<code>[ai_faq_generator theme="minimal" show_export="false"]</code>
					<p><?php esc_html_e( 'Uses minimal styling and hides export options.', '365i-ai-faq-generator' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Recent Activity -->
		<div class="ai-faq-gen-section recent-activity-section">
			<h3>
				<span class="dashicons dashicons-clock"></span>
				<?php esc_html_e( 'Recent Activity', '365i-ai-faq-generator' ); ?>
			</h3>
			
			<div class="activity-list">
				<?php
				// Get recent transients for activity
				$activity_items = array();
				
				foreach ( $workers as $worker_name => $config ) {
					$usage = get_transient( 'ai_faq_rate_limit_' . $worker_name );
					if ( $usage ) {
						$activity_items[] = array(
							'type' => 'worker_usage',
							'message' => sprintf(
								/* translators: 1: Worker name, 2: Usage count */
								__( '%1$s worker used %2$d times in the last hour', '365i-ai-faq-generator' ),
								ucwords( str_replace( '_', ' ', $worker_name ) ),
								$usage
							),
							'time' => __( 'Last hour', '365i-ai-faq-generator' ),
						);
					}
				}
				
				if ( empty( $activity_items ) ) :
				?>
					<p class="no-activity"><?php esc_html_e( 'No recent activity found. Users can generate FAQ content using the frontend shortcode.', '365i-ai-faq-generator' ); ?></p>
				<?php else : ?>
					<ul class="activity-items">
						<?php foreach ( $activity_items as $item ) : ?>
							<li class="activity-item">
								<span class="activity-icon dashicons dashicons-<?php echo 'worker_usage' === $item['type'] ? 'networking' : 'admin-generic'; ?>"></span>
								<div class="activity-content">
									<p><?php echo esc_html( $item['message'] ); ?></p>
									<span class="activity-time"><?php echo esc_html( $item['time'] ); ?></span>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>

	</div><!-- .ai-faq-gen-main-content -->

</div><!-- .ai-faq-gen-dashboard -->

<?php
// Include footer.
include AI_FAQ_GEN_DIR . 'templates/partials/footer.php';
?>