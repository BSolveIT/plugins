<?php
/**
 * Worker Configuration admin template for 365i AI FAQ Generator.
 * 
 * This template displays the worker configuration interface with
 * settings for all 6 Cloudflare workers.
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

// Get plugin options.
$options = get_option( 'ai_faq_gen_options', array() );
$workers = isset( $options['workers'] ) ? $options['workers'] : array();

// Worker definitions with descriptions.
$worker_definitions = array(
	'question_generator' => array(
		'name' => __( 'Question Generator', '365i-ai-faq-generator' ),
		'description' => __( 'Generates contextual questions with duplicate prevention using Llama 3.1 8B model.', '365i-ai-faq-generator' ),
		'model' => '@cf/meta/llama-3.1-8b-instruct',
		'response_time' => '3-5 seconds',
	),
	'answer_generator' => array(
		'name' => __( 'Answer Generator', '365i-ai-faq-generator' ),
		'description' => __( 'Creates comprehensive answers with tone and length control using Llama 3.1 8B model.', '365i-ai-faq-generator' ),
		'model' => '@cf/meta/llama-3.1-8b-instruct',
		'response_time' => '3-5 seconds',
	),
	'faq_enhancer' => array(
		'name' => __( 'FAQ Enhancer', '365i-ai-faq-generator' ),
		'description' => __( 'Improves existing FAQ content with SEO optimization and context awareness.', '365i-ai-faq-generator' ),
		'model' => '@cf/meta/llama-3.1-8b-instruct',
		'response_time' => '5-8 seconds',
	),
	'seo_analyzer' => array(
		'name' => __( 'SEO Analyzer', '365i-ai-faq-generator' ),
		'description' => __( 'Analyzes FAQ content for SEO optimization and Position Zero targeting.', '365i-ai-faq-generator' ),
		'model' => '@cf/meta/llama-4-scout-17b-16e-instruct',
		'response_time' => '5-10 seconds',
	),
	'faq_extractor' => array(
		'name' => __( 'FAQ Extractor', '365i-ai-faq-generator' ),
		'description' => __( 'Extracts existing FAQ schema from websites (JSON-LD, Microdata, RDFa).', '365i-ai-faq-generator' ),
		'model' => 'N/A (Proxy Service)',
		'response_time' => '5-15 seconds',
	),
	'topic_generator' => array(
		'name' => __( 'Topic Generator', '365i-ai-faq-generator' ),
		'description' => __( 'Generates comprehensive FAQ sets from website URLs using premium analysis.', '365i-ai-faq-generator' ),
		'model' => '@cf/meta/llama-4-scout-17b-16e-instruct',
		'response_time' => '15-30 seconds',
	),
);
?>

<div class="ai-faq-gen-workers">
	
	<!-- Workers Overview -->
	<div class="ai-faq-gen-section workers-overview-section">
		<div class="analytics-header">
			<span class="dashicons dashicons-networking"></span>
			<h3><?php esc_html_e( 'Cloudflare Workers Overview', '365i-ai-faq-generator' ); ?></h3>
		</div>
		
		<p><?php esc_html_e( 'Configure your Cloudflare AI workers for FAQ generation. Each worker serves a specific purpose in the FAQ creation pipeline.', '365i-ai-faq-generator' ); ?></p>
		
		<div class="workers-stats">
			<?php
			$total_workers = count( $worker_definitions );
			$enabled_workers = 0;
			$total_usage = 0;
			
			foreach ( $workers as $worker_config ) {
				if ( $worker_config['enabled'] ) {
					$enabled_workers++;
				}
			}
			?>
			
			<div class="stat-card">
				<div class="stat-number"><?php echo esc_html( $total_workers ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'Total Workers', '365i-ai-faq-generator' ); ?></div>
			</div>
			
			<div class="stat-card">
				<div class="stat-number"><?php echo esc_html( $enabled_workers ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'Enabled Workers', '365i-ai-faq-generator' ); ?></div>
			</div>
			
			<div class="stat-card">
				<div class="stat-number"><?php echo esc_html( $total_workers - $enabled_workers ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'Disabled Workers', '365i-ai-faq-generator' ); ?></div>
			</div>
		</div>
	</div>

	<!-- Worker Configuration Form -->
	<form id="workers-configuration-form" method="post" action="">
		<?php wp_nonce_field( 'ai_faq_gen_save_workers', '_wpnonce' ); ?>
		
		<div class="workers-grid">
			<?php foreach ( $worker_definitions as $worker_key => $worker_def ) : ?>
				<?php
				$worker_config = isset( $workers[ $worker_key ] ) ? $workers[ $worker_key ] : array();
				$is_enabled = isset( $worker_config['enabled'] ) ? $worker_config['enabled'] : true;
				$worker_url = isset( $worker_config['url'] ) ? $worker_config['url'] : '';
				$rate_limit = isset( $worker_config['rate_limit'] ) ? $worker_config['rate_limit'] : 50;
				$current_usage = get_transient( 'ai_faq_rate_limit_' . $worker_key ) ?: 0;
				?>
				
				<div class="worker-config-card <?php echo $is_enabled ? 'enabled' : 'disabled'; ?>" data-worker="<?php echo esc_attr( $worker_key ); ?>">
					<div class="worker-header">
						<h4><?php echo esc_html( $worker_def['name'] ); ?></h4>
						<label class="worker-toggle">
							<input type="checkbox" name="workers[<?php echo esc_attr( $worker_key ); ?>][enabled]" value="1" <?php checked( $is_enabled ); ?>>
							<span class="toggle-slider"></span>
						</label>
					</div>
					
					<div class="worker-details">
						<p class="worker-description"><?php echo esc_html( $worker_def['description'] ); ?></p>
						
						<div class="worker-specs">
							<div class="spec-item">
								<strong><?php esc_html_e( 'Model:', '365i-ai-faq-generator' ); ?></strong>
								<span><?php echo esc_html( $worker_def['model'] ); ?></span>
							</div>
							<div class="spec-item">
								<strong><?php esc_html_e( 'Response Time:', '365i-ai-faq-generator' ); ?></strong>
								<span><?php echo esc_html( $worker_def['response_time'] ); ?></span>
							</div>
						</div>
						
						<div class="worker-config">
							<div class="config-row">
								<label for="worker_url_<?php echo esc_attr( $worker_key ); ?>"><?php esc_html_e( 'Worker URL', '365i-ai-faq-generator' ); ?></label>
								<input type="url" id="worker_url_<?php echo esc_attr( $worker_key ); ?>" name="workers[<?php echo esc_attr( $worker_key ); ?>][url]" value="<?php echo esc_attr( $worker_url ); ?>" placeholder="https://worker-name.subdomain.workers.dev" class="regular-text">
							</div>
							
							<div class="config-row">
								<label for="worker_rate_limit_<?php echo esc_attr( $worker_key ); ?>"><?php esc_html_e( 'Rate Limit (requests/hour)', '365i-ai-faq-generator' ); ?></label>
								<input type="number" id="worker_rate_limit_<?php echo esc_attr( $worker_key ); ?>" name="workers[<?php echo esc_attr( $worker_key ); ?>][rate_limit]" value="<?php echo esc_attr( $rate_limit ); ?>" min="1" max="1000" class="small-text">
							</div>
							
							<div class="config-row usage-display">
								<label><?php esc_html_e( 'Current Usage', '365i-ai-faq-generator' ); ?></label>
								<div class="usage-info">
									<span class="usage-text">
										<span class="usage-current"><?php echo esc_html( $current_usage ); ?></span> / <?php echo esc_html( $rate_limit ); ?>
									</span>
									<div class="usage-bar">
										<?php $usage_percent = $rate_limit > 0 ? ( $current_usage / $rate_limit ) * 100 : 0; ?>
										<div class="usage-fill" style="width: <?php echo esc_attr( min( $usage_percent, 100 ) ); ?>%"></div>
									</div>
								</div>
							</div>
						</div>
						
						<div class="worker-actions">
							<button type="button" class="button test-worker-connection" data-worker="<?php echo esc_attr( $worker_key ); ?>">
								<span class="dashicons dashicons-yes"></span>
								<?php esc_html_e( 'Test Connection', '365i-ai-faq-generator' ); ?>
							</button>
							<button type="button" class="button reset-worker-usage" data-worker="<?php echo esc_attr( $worker_key ); ?>">
								<span class="dashicons dashicons-update"></span>
								<?php esc_html_e( 'Reset Usage', '365i-ai-faq-generator' ); ?>
							</button>
						</div>
					</div>
				</div>
				
			<?php endforeach; ?>
		</div>
		
		<div class="form-actions">
			<button type="submit" class="button button-primary">
				<span class="dashicons dashicons-yes"></span>
				<?php esc_html_e( 'Save Worker Configuration', '365i-ai-faq-generator' ); ?>
			</button>
			
			<button type="button" class="button button-secondary test-all-workers">
				<span class="dashicons dashicons-networking"></span>
				<?php esc_html_e( 'Test All Workers', '365i-ai-faq-generator' ); ?>
			</button>
			
			<button type="button" class="button button-secondary refresh-worker-status">
				<span class="dashicons dashicons-update"></span>
				<?php esc_html_e( 'Refresh Status', '365i-ai-faq-generator' ); ?>
			</button>
		</div>
	</form>

	<!-- Usage Analytics -->
	<div class="ai-faq-gen-section usage-analytics-section">
		<div class="analytics-header">
			<span class="dashicons dashicons-chart-area"></span>
			<h3><?php esc_html_e( 'Usage Analytics', '365i-ai-faq-generator' ); ?></h3>
		</div>
		
		<p class="analytics-description"><?php esc_html_e( 'Monitor worker usage and performance metrics. Rate limits reset every hour.', '365i-ai-faq-generator' ); ?></p>
		
		<div class="analytics-metrics">
			<div class="metric-card">
				<div class="metric-title">
					<i class="dashicons dashicons-chart-bar"></i>
					<?php esc_html_e( 'Total Requests Today', '365i-ai-faq-generator' ); ?>
				</div>
				<div class="metric-value" id="total-requests-today">0</div>
				<div class="metric-description"><?php esc_html_e( 'Total API requests processed across all workers', '365i-ai-faq-generator' ); ?></div>
			</div>
			
			<div class="metric-card">
				<div class="metric-title">
					<i class="dashicons dashicons-performance"></i>
					<?php esc_html_e( 'Average Response Time', '365i-ai-faq-generator' ); ?>
				</div>
				<div class="metric-value" id="avg-response-time">0</div>
				<div class="metric-description"><?php esc_html_e( 'Average time in seconds for worker response', '365i-ai-faq-generator' ); ?></div>
			</div>
			
			<div class="metric-card success-rate">
				<div class="metric-title">
					<i class="dashicons dashicons-yes-alt"></i>
					<?php esc_html_e( 'Success Rate', '365i-ai-faq-generator' ); ?>
				</div>
				<div class="metric-value" id="success-rate">100%</div>
				<div class="metric-description"><?php esc_html_e( 'Percentage of successful API requests', '365i-ai-faq-generator' ); ?></div>
			</div>
		</div>
		
		<div class="usage-chart no-data">
			<?php esc_html_e( 'Usage data will display here as workers are used', '365i-ai-faq-generator' ); ?>
		</div>
	</div>

	<!-- Help & Documentation -->
	<div class="ai-faq-gen-section help-section">
		<div class="analytics-header">
			<span class="dashicons dashicons-editor-help"></span>
			<h3><?php esc_html_e( 'Help & Documentation', '365i-ai-faq-generator' ); ?></h3>
		</div>
		
		<div class="help-grid">
			<div class="help-card">
				<h4><?php esc_html_e( 'Worker Setup Guide', '365i-ai-faq-generator' ); ?></h4>
				<p><?php esc_html_e( 'Learn how to configure your Cloudflare workers and KV namespaces for optimal performance. This guide walks you through the complete setup process with step-by-step instructions.', '365i-ai-faq-generator' ); ?></p>
				<a href="#" class="button button-secondary">
					<?php esc_html_e( 'View Guide', '365i-ai-faq-generator' ); ?>
				</a>
			</div>
			
			<div class="help-card">
				<h4><?php esc_html_e( 'Troubleshooting', '365i-ai-faq-generator' ); ?></h4>
				<p><?php esc_html_e( 'Common issues and solutions for worker connectivity problems. Find answers to frequently encountered setup issues and learn how to diagnose connection failures.', '365i-ai-faq-generator' ); ?></p>
				<a href="#" class="button button-secondary">
					<?php esc_html_e( 'Get Help', '365i-ai-faq-generator' ); ?>
				</a>
			</div>
			
			<div class="help-card">
				<h4><?php esc_html_e( 'API Reference', '365i-ai-faq-generator' ); ?></h4>
				<p><?php esc_html_e( 'Complete API documentation for all worker endpoints and parameters. This technical reference provides detailed information about request formats, response structures, and authentication.', '365i-ai-faq-generator' ); ?></p>
				<a href="#" class="button button-secondary">
					<?php esc_html_e( 'View API Docs', '365i-ai-faq-generator' ); ?>
				</a>
			</div>
		</div>
	</div>

</div><!-- .ai-faq-gen-workers -->

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
	// Worker toggle functionality
	const workerToggles = document.querySelectorAll('.worker-toggle input[type="checkbox"]');
	workerToggles.forEach(function(toggle) {
		toggle.addEventListener('change', function() {
			const card = this.closest('.worker-config-card');
			if (this.checked) {
				card.classList.remove('disabled');
				card.classList.add('enabled');
			} else {
				card.classList.remove('enabled');
				card.classList.add('disabled');
			}
		});
	});
	
	// Test all workers button
	const testAllButton = document.querySelector('.test-all-workers');
	if (testAllButton) {
		testAllButton.addEventListener('click', function() {
			const testButtons = document.querySelectorAll('.test-worker-connection');
			testButtons.forEach(function(button, index) {
				setTimeout(function() {
					button.click();
				}, index * 1000); // Stagger tests by 1 second
			});
		});
	}
});
</script>

<?php
// Include footer.
include AI_FAQ_GEN_DIR . 'templates/partials/footer.php';
?>