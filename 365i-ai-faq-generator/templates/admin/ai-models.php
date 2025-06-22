<?php
/**
 * AI Models Status admin template for 365i AI FAQ Generator.
 * 
 * Modern card-based interface for viewing current AI model status from KV namespace
 * and testing connectivity. No model selection - models are managed via KV storage.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Templates
 * @since 2.3.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include header.
include AI_FAQ_GEN_DIR . 'templates/partials/header.php';

// Worker definitions for display.
$worker_definitions = array(
	'question_generator' => array(
		'name' => __( 'Question Generator', '365i-ai-faq-generator' ),
		'description' => __( 'Generates contextual questions with duplicate prevention', '365i-ai-faq-generator' ),
		'icon' => 'editor-help',
		'use_case' => __( 'Best for creative question generation and brainstorming FAQ topics', '365i-ai-faq-generator' ),
	),
	'answer_generator' => array(
		'name' => __( 'Answer Generator', '365i-ai-faq-generator' ),
		'description' => __( 'Creates comprehensive answers with tone and length control', '365i-ai-faq-generator' ),
		'icon' => 'format-chat',
		'use_case' => __( 'Optimal for detailed, well-structured answer generation', '365i-ai-faq-generator' ),
	),
	'faq_enhancer' => array(
		'name' => __( 'FAQ Enhancer', '365i-ai-faq-generator' ),
		'description' => __( 'Improves existing FAQ content with SEO optimization', '365i-ai-faq-generator' ),
		'icon' => 'admin-tools',
		'use_case' => __( 'Perfect for refining and optimizing existing FAQ content', '365i-ai-faq-generator' ),
	),
	'seo_analyzer' => array(
		'name' => __( 'SEO Analyzer', '365i-ai-faq-generator' ),
		'description' => __( 'Analyzes FAQ content for SEO optimization and Position Zero targeting', '365i-ai-faq-generator' ),
		'icon' => 'search',
		'use_case' => __( 'Ideal for technical SEO analysis and search optimization', '365i-ai-faq-generator' ),
	),
	'faq_extractor' => array(
		'name' => __( 'FAQ Extractor', '365i-ai-faq-generator' ),
		'description' => __( 'Extracts existing FAQ schema from websites', '365i-ai-faq-generator' ),
		'icon' => 'download',
		'use_case' => __( 'Proxy service - no AI model required', '365i-ai-faq-generator' ),
		'no_model' => true,
	),
	'topic_generator' => array(
		'name' => __( 'Topic Generator', '365i-ai-faq-generator' ),
		'description' => __( 'Generates comprehensive FAQ sets from website URLs', '365i-ai-faq-generator' ),
		'icon' => 'networking',
		'use_case' => __( 'Best for comprehensive content analysis and topic extraction', '365i-ai-faq-generator' ),
	),
);
?>

<div class="ai-faq-gen-ai-models modern-layout">
	
	<!-- Worker Status Cards Grid -->
	<div class="worker-models-grid">
		<?php foreach ( $worker_definitions as $worker_key => $worker_def ) : ?>
			<?php
			$is_no_model = isset( $worker_def['no_model'] ) ? $worker_def['no_model'] : false;
			?>
			
			<div class="worker-model-card<?php echo $is_no_model ? ' no-model-required' : ''; ?>"
			     data-worker="<?php echo esc_attr( $worker_key ); ?>">
				
				<!-- Card Header -->
				<div class="card-header">
					<div class="worker-info">
						<span class="dashicons dashicons-<?php echo esc_attr( $worker_def['icon'] ); ?>"></span>
						<div class="worker-details">
							<h3 class="worker-name"><?php echo esc_html( $worker_def['name'] ); ?></h3>
							<p class="worker-description"><?php echo esc_html( $worker_def['description'] ); ?></p>
						</div>
					</div>
					
					<div class="card-status">
						<span class="status-badge kv-config"><?php esc_html_e( 'KV Config', '365i-ai-faq-generator' ); ?></span>
					</div>
				</div>
				
				<!-- Card Content -->
				<div class="card-content">
					<?php if ( $is_no_model ) : ?>
						<!-- No Model Required -->
						<div class="no-model-notice">
							<span class="dashicons dashicons-info"></span>
							<div class="notice-content">
								<h4><?php esc_html_e( 'Proxy Service', '365i-ai-faq-generator' ); ?></h4>
								<p><?php esc_html_e( 'This worker is a proxy service and does not use an AI model.', '365i-ai-faq-generator' ); ?></p>
							</div>
						</div>
					<?php else : ?>
						<!-- Current AI Model from KV -->
						<div class="current-ai-model-section">
							<h4><?php esc_html_e( 'Current AI Model', '365i-ai-faq-generator' ); ?></h4>
							
							<!-- Real-time AI Model Info from KV -->
							<div class="realtime-model-info">
								<div class="realtime-header">
									<span class="realtime-badge">
										<span class="dashicons dashicons-cloud"></span>
										<?php esc_html_e( 'KV Config', '365i-ai-faq-generator' ); ?>
									</span>
								</div>
								
								<div class="realtime-content">
									<div class="active-model-display">
										<span class="model-name-display"><?php esc_html_e( 'Loading...', '365i-ai-faq-generator' ); ?></span>
										<span class="model-source-badge unknown">
											<span class="dashicons dashicons-info"></span>
											<span class="source-text"><?php esc_html_e( 'Unknown', '365i-ai-faq-generator' ); ?></span>
										</span>
									</div>
									
									<div class="model-source-info">
										<div class="source-explanation">
											<p class="source-description"><?php esc_html_e( 'AI model configuration is managed via Cloudflare KV namespace AI_MODEL_CONFIG.', '365i-ai-faq-generator' ); ?></p>
										</div>
									</div>
								</div>
							</div>
						</div>
						
						<!-- Connectivity Status - Hidden by default, shown after test -->
						<div class="connectivity-status" style="display: none;">
							<div class="status-indicator pending">
								<span class="dashicons dashicons-update status-icon"></span>
								<span class="status-text"></span>
								<span class="status-time"></span>
							</div>
						</div>
						
						<!-- Test Connectivity Button -->
						<div class="model-actions">
							<button type="button" class="button button-secondary test-model-connectivity"
							        data-worker="<?php echo esc_attr( $worker_key ); ?>"
							        title="<?php esc_attr_e( 'Test connectivity to this worker', '365i-ai-faq-generator' ); ?>">
								<span class="dashicons dashicons-networking"></span>
								<?php esc_html_e( 'Test Connectivity', '365i-ai-faq-generator' ); ?>
							</button>
						</div>
						
					<?php endif; ?>
				</div>
			</div>
			
		<?php endforeach; ?>
	</div>
	
	<!-- Test All Connectivity Section - Hidden by default -->
	<div class="test-all-section" style="display: none;">
		<div class="test-all-actions">
			<button type="button" class="button button-primary test-all-models">
				<span class="dashicons dashicons-networking"></span>
				<?php esc_html_e( 'Test All Connectivity', '365i-ai-faq-generator' ); ?>
			</button>
		</div>
	</div>

</div><!-- .ai-faq-gen-ai-models -->

<!-- Pass data to JavaScript -->
<script type="text/javascript">
window.aiFaqModelsData = <?php echo wp_json_encode( array(
	'workers' => $worker_definitions,
	'apiEndpoint' => admin_url( 'admin-ajax.php' ),
	'nonce' => wp_create_nonce( 'ai_faq_admin_nonce' ),
	'strings' => array(
		'loading' => __( 'Loading...', '365i-ai-faq-generator' ),
		'error' => __( 'An error occurred', '365i-ai-faq-generator' ),
		'connectivityTestFailed' => __( 'Connectivity test failed', '365i-ai-faq-generator' ),
		'bulkTestStarted' => __( 'Starting bulk connectivity tests...', '365i-ai-faq-generator' ),
		'bulkTestCompleted' => __( 'Bulk connectivity tests completed', '365i-ai-faq-generator' ),
	),
) ); ?>;
</script>

<?php
// Include footer.
include AI_FAQ_GEN_DIR . 'templates/partials/footer.php';
?>