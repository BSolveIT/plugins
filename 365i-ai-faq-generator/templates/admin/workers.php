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

// Get rate limiting configurations for comprehensive display.
$rate_limiting_admin = new AI_FAQ_Rate_Limiting_Admin();
$rate_configs = $rate_limiting_admin->get_rate_configs_for_display();

// Map workers template keys to rate limiting system keys.
$worker_rate_mapping = array(
	'question_generator' => 'faq-answer-generator-worker',
	'answer_generator'   => 'faq-answer-generator-worker',
	'faq_enhancer'       => 'faq-enhancement-worker',
	'seo_analyzer'       => 'faq-seo-analyzer-worker',
	'faq_extractor'      => 'faq-proxy-fetch',
	'topic_generator'    => 'url-to-faq-generator-worker',
);

// Worker definitions with descriptions (default/fallback models).
$worker_definitions = array(
	'question_generator' => array(
		'name' => __( 'Question Generator', '365i-ai-faq-generator' ),
		'description' => __( 'Generates contextual questions with duplicate prevention using AI models.', '365i-ai-faq-generator' ),
		'default_model' => '@cf/meta/llama-3.1-8b-instruct',
	),
	'answer_generator' => array(
		'name' => __( 'Answer Generator', '365i-ai-faq-generator' ),
		'description' => __( 'Creates comprehensive answers with tone and length control using AI models.', '365i-ai-faq-generator' ),
		'default_model' => '@cf/meta/llama-3.1-8b-instruct',
	),
	'faq_enhancer' => array(
		'name' => __( 'FAQ Enhancer', '365i-ai-faq-generator' ),
		'description' => __( 'Improves existing FAQ content with SEO optimization and context awareness.', '365i-ai-faq-generator' ),
		'default_model' => '@cf/meta/llama-3.1-8b-instruct',
	),
	'seo_analyzer' => array(
		'name' => __( 'SEO Analyzer', '365i-ai-faq-generator' ),
		'description' => __( 'Analyzes FAQ content for SEO optimization and Position Zero targeting.', '365i-ai-faq-generator' ),
		'default_model' => '@cf/meta/llama-4-scout-17b-16e-instruct',
	),
	'faq_extractor' => array(
		'name' => __( 'FAQ Extractor', '365i-ai-faq-generator' ),
		'description' => __( 'Extracts existing FAQ schema from websites (JSON-LD, Microdata, RDFa).', '365i-ai-faq-generator' ),
		'default_model' => 'N/A (Proxy Service)',
	),
	'topic_generator' => array(
		'name' => __( 'Topic Generator', '365i-ai-faq-generator' ),
		'description' => __( 'Generates comprehensive FAQ sets from website URLs using premium analysis.', '365i-ai-faq-generator' ),
		'default_model' => '@cf/meta/llama-4-scout-17b-16e-instruct',
	),
);

// Get actual AI model configurations from KV/WordPress storage.
// Wrap in try-catch to prevent errors during page load from affecting worker save functionality
$model_configurations = array();
$ai_models_admin = null;
try {
	$ai_models_admin = new AI_FAQ_Admin_AI_Models();
	$ai_models_admin->init(); // Initialize the API client and hooks
	$model_configurations = $ai_models_admin->get_worker_model_configurations();
} catch ( Exception $e ) {
	// Log the error but don't let it break the page
	error_log( 'AI FAQ Generator: Error fetching model configurations during workers page load: ' . $e->getMessage() );
	// Use empty array so the page still works
	$model_configurations = array();
	$ai_models_admin = null;
}

// Update worker definitions with actual configured models
foreach ( array_keys( $worker_definitions ) as $worker_key ) {
	if ( isset( $model_configurations[ $worker_key ] ) ) {
		$config = $model_configurations[ $worker_key ];
		$worker_definitions[ $worker_key ]['current_model'] = $config['model'];
		$worker_definitions[ $worker_key ]['is_custom'] = $config['is_custom'];
		$worker_definitions[ $worker_key ]['data_source'] = $config['data_source'];
		
		// Get display name for the model efficiently (avoid unnecessary API calls)
		if ( $ai_models_admin ) {
			$model_display_name = $ai_models_admin->get_model_display_name_efficiently( $config['model'] );
			$worker_definitions[ $worker_key ]['model_display_name'] = $model_display_name;
			
			// Get dynamic response time based on configured model
			$worker_definitions[ $worker_key ]['response_time'] = $ai_models_admin->get_model_response_time( $config['model'] );
		} else {
			// Fallback to basic model ID if AI models admin failed to initialize
			$worker_definitions[ $worker_key ]['model_display_name'] = $config['model'];
			$worker_definitions[ $worker_key ]['response_time'] = __( 'N/A', '365i-ai-faq-generator' );
		}
		
		// Set source indicator based on data source
		switch ( $config['data_source'] ) {
			case 'kv_namespace':
				$worker_definitions[ $worker_key ]['model_source'] = 'kv_config';
				break;
			case 'wordpress_fallback':
				$worker_definitions[ $worker_key ]['model_source'] = 'wordpress_storage';
				break;
			default:
				$worker_definitions[ $worker_key ]['model_source'] = 'defaults_only';
				break;
		}
	} else {
		// Fallback: No model configuration found, use default response time based on worker type
		switch ( $worker_key ) {
			case 'faq_extractor':
				$worker_definitions[ $worker_key ]['response_time'] = __( '5-15 seconds', '365i-ai-faq-generator' );
				break;
			case 'topic_generator':
				$worker_definitions[ $worker_key ]['response_time'] = __( '15-30 seconds', '365i-ai-faq-generator' );
				break;
			default:
				// Use default model's response time
				if ( $ai_models_admin ) {
					$worker_definitions[ $worker_key ]['response_time'] = $ai_models_admin->get_model_response_time( $worker_definitions[ $worker_key ]['default_model'] );
				} else {
					$worker_definitions[ $worker_key ]['response_time'] = __( 'N/A', '365i-ai-faq-generator' );
				}
				break;
		}
	}
}

// Get live worker health status for connection testing (optional).
$workers_admin = new AI_FAQ_Admin_Workers();
$live_worker_status = array();
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
								<?php
								// Display actual configured model from AI Models admin
								if ( isset( $worker_def['current_model'] ) ) {
									$model_display = $worker_def['model_display_name'] ?? $worker_def['current_model'];
									$model_source = $worker_def['model_source'] ?? 'unknown';
									$data_source = $worker_def['data_source'] ?? 'unknown';
									$is_custom = $worker_def['is_custom'] ?? false;
									
									$source_class = '';
									$source_text = '';
									
									switch ( $model_source ) {
										case 'kv_config':
											$source_class = 'model-source-kv';
											$source_text = __( 'from AI Models config', '365i-ai-faq-generator' );
											break;
										case 'wordpress_storage':
											$source_class = 'model-source-wp';
											$source_text = __( 'from WordPress storage', '365i-ai-faq-generator' );
											break;
										case 'defaults_only':
											$source_class = 'model-source-default';
											$source_text = __( 'using defaults', '365i-ai-faq-generator' );
											break;
										default:
											$source_class = 'model-source-unknown';
											$source_text = __( 'unknown source', '365i-ai-faq-generator' );
											break;
									}
									
									// Add visual indicator for custom vs default
									$indicator_class = $is_custom ? 'model-custom' : 'model-default';
									
									echo '<span class="current-model ' . esc_attr( $source_class ) . ' ' . esc_attr( $indicator_class ) . '" title="' . esc_attr( $source_text ) . '">';
									echo esc_html( $model_display );
									echo '</span>';
								} else {
									// Fallback to default model
									echo '<span class="default-model" title="' . esc_attr__( 'No configuration found - using fallback default', '365i-ai-faq-generator' ) . '">';
									echo esc_html( $worker_def['default_model'] );
									echo '</span>';
								}
								?>
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
							
							<?php
							// Get rate limiting configuration for this worker.
							$rate_limit_key = isset( $worker_rate_mapping[ $worker_key ] ) ? $worker_rate_mapping[ $worker_key ] : $worker_key;
							$rate_config = isset( $rate_configs[ $rate_limit_key ] ) ? $rate_configs[ $rate_limit_key ] : array();
							
							// Use default values if no configuration found.
							$hourly_limit = isset( $rate_config['hourlyLimit'] ) ? $rate_config['hourlyLimit'] : 10;
							$daily_limit = isset( $rate_config['dailyLimit'] ) ? $rate_config['dailyLimit'] : 50;
							$weekly_limit = isset( $rate_config['weeklyLimit'] ) ? $rate_config['weeklyLimit'] : 250;
							$monthly_limit = isset( $rate_config['monthlyLimit'] ) ? $rate_config['monthlyLimit'] : 1000;
							$violation_thresholds = isset( $rate_config['violationThresholds'] ) ? $rate_config['violationThresholds'] : array(
								'soft' => 3,
								'hard' => 6,
								'ban'  => 12,
							);
							?>
							
							<div class="config-row rate-limits-display">
								<label><?php esc_html_e( 'IP-Based Rate Limits', '365i-ai-faq-generator' ); ?></label>
								<div class="rate-limits-grid">
									<div class="rate-limit-item">
										<div class="rate-limit-label"><?php esc_html_e( 'Hourly', '365i-ai-faq-generator' ); ?></div>
										<div class="rate-limit-value"><?php echo esc_html( $hourly_limit ); ?></div>
									</div>
									<div class="rate-limit-item">
										<div class="rate-limit-label"><?php esc_html_e( 'Daily', '365i-ai-faq-generator' ); ?></div>
										<div class="rate-limit-value"><?php echo esc_html( $daily_limit ); ?></div>
									</div>
									<div class="rate-limit-item">
										<div class="rate-limit-label"><?php esc_html_e( 'Weekly', '365i-ai-faq-generator' ); ?></div>
										<div class="rate-limit-value"><?php echo esc_html( $weekly_limit ); ?></div>
									</div>
									<div class="rate-limit-item">
										<div class="rate-limit-label"><?php esc_html_e( 'Monthly', '365i-ai-faq-generator' ); ?></div>
										<div class="rate-limit-value"><?php echo esc_html( $monthly_limit ); ?></div>
									</div>
								</div>
							</div>
							
							<div class="config-row violation-thresholds-display">
								<label><?php esc_html_e( 'Violation Thresholds', '365i-ai-faq-generator' ); ?></label>
								<div class="violation-thresholds-grid">
									<div class="threshold-item soft">
										<div class="threshold-icon">
											<span class="dashicons dashicons-warning"></span>
										</div>
										<div class="threshold-content">
											<div class="threshold-label"><?php esc_html_e( 'Soft Warning', '365i-ai-faq-generator' ); ?></div>
											<div class="threshold-value"><?php echo esc_html( $violation_thresholds['soft'] ); ?></div>
										</div>
									</div>
									<div class="threshold-item hard">
										<div class="threshold-icon">
											<span class="dashicons dashicons-dismiss"></span>
										</div>
										<div class="threshold-content">
											<div class="threshold-label"><?php esc_html_e( 'Hard Block', '365i-ai-faq-generator' ); ?></div>
											<div class="threshold-value"><?php echo esc_html( $violation_thresholds['hard'] ); ?></div>
										</div>
									</div>
									<div class="threshold-item ban">
										<div class="threshold-icon">
											<span class="dashicons dashicons-lock"></span>
										</div>
										<div class="threshold-content">
											<div class="threshold-label"><?php esc_html_e( 'Permanent Ban', '365i-ai-faq-generator' ); ?></div>
											<div class="threshold-value"><?php echo esc_html( $violation_thresholds['ban'] ); ?></div>
										</div>
									</div>
								</div>
							</div>
							
							<div class="config-row rate-limits-note">
								<p class="description">
									<?php esc_html_e( 'These limits are applied per IP address. Configure rate limits on the', '365i-ai-faq-generator' ); ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-faq-generator-rate-limiting' ) ); ?>" class="rate-limits-link">
										<?php esc_html_e( 'Rate Limiting page', '365i-ai-faq-generator' ); ?>
									</a>.
								</p>
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
				<button type="button" class="button button-secondary ai-faq-doc-button" data-doc-type="setup_guide">
					<span class="dashicons dashicons-media-text"></span>
					<?php esc_html_e( 'View Guide', '365i-ai-faq-generator' ); ?>
				</button>
			</div>
			
			<div class="help-card">
				<h4><?php esc_html_e( 'Troubleshooting', '365i-ai-faq-generator' ); ?></h4>
				<p><?php esc_html_e( 'Common issues and solutions for worker connectivity problems. Find answers to frequently encountered setup issues and learn how to diagnose connection failures.', '365i-ai-faq-generator' ); ?></p>
				<button type="button" class="button button-secondary ai-faq-doc-button" data-doc-type="troubleshooting">
					<span class="dashicons dashicons-sos"></span>
					<?php esc_html_e( 'Get Help', '365i-ai-faq-generator' ); ?>
				</button>
			</div>
			
			<div class="help-card">
				<h4><?php esc_html_e( 'API Reference', '365i-ai-faq-generator' ); ?></h4>
				<p><?php esc_html_e( 'Complete API documentation for all worker endpoints and parameters. This technical reference provides detailed information about request formats, response structures, and authentication.', '365i-ai-faq-generator' ); ?></p>
				<button type="button" class="button button-secondary ai-faq-doc-button" data-doc-type="api_reference">
					<span class="dashicons dashicons-editor-code"></span>
					<?php esc_html_e( 'View API Docs', '365i-ai-faq-generator' ); ?>
				</button>
			</div>
		</div>
	</div>

</div><!-- .ai-faq-gen-workers -->

<?php
// Include footer.
include AI_FAQ_GEN_DIR . 'templates/partials/footer.php';
?>