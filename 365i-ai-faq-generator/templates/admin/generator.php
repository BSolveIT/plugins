<?php
/**
 * FAQ Generator admin template for 365i AI FAQ Generator.
 * 
 * This template displays the main FAQ generation interface with
 * all worker tools and generation options.
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
$settings = isset( $options['settings'] ) ? $options['settings'] : array();

// Check if workers are configured.
$has_enabled_workers = false;
foreach ( $workers as $worker ) {
	if ( $worker['enabled'] ) {
		$has_enabled_workers = true;
		break;
	}
}
?>

<div class="ai-faq-gen-generator">
	
	<?php if ( ! $has_enabled_workers ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'No Workers Configured', '365i-ai-faq-generator' ); ?></strong><br>
				<?php esc_html_e( 'Please configure at least one Cloudflare worker to start generating FAQ content.', '365i-ai-faq-generator' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-faq-generator-workers' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Configure Workers', '365i-ai-faq-generator' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>

	<!-- Generation Methods -->
	<div class="ai-faq-gen-section generation-methods-section">
		<h3>
			<span class="dashicons dashicons-lightbulb"></span>
			<?php esc_html_e( 'FAQ Generation Methods', '365i-ai-faq-generator' ); ?>
		</h3>
		
		<div class="method-tabs">
			<button type="button" class="method-tab active" data-method="topic">
				<span class="dashicons dashicons-lightbulb"></span>
				<?php esc_html_e( 'Generate from Topic', '365i-ai-faq-generator' ); ?>
			</button>
			<button type="button" class="method-tab" data-method="url">
				<span class="dashicons dashicons-admin-links"></span>
				<?php esc_html_e( 'Extract from URL', '365i-ai-faq-generator' ); ?>
			</button>
			<button type="button" class="method-tab" data-method="enhance">
				<span class="dashicons dashicons-editor-spellcheck"></span>
				<?php esc_html_e( 'Enhance Existing', '365i-ai-faq-generator' ); ?>
			</button>
		</div>

		<!-- Topic Generation Method -->
		<div class="method-panel active" id="topic-method">
			<div class="method-content">
				<h4><?php esc_html_e( 'Generate FAQ from Topic', '365i-ai-faq-generator' ); ?></h4>
				<p><?php esc_html_e( 'Enter a topic or subject and let AI generate relevant questions and answers.', '365i-ai-faq-generator' ); ?></p>
				
				<form id="topic-generation-form" class="generation-form">
					<div class="form-row">
						<label for="topic-input"><?php esc_html_e( 'Topic or Subject', '365i-ai-faq-generator' ); ?></label>
						<input type="text" id="topic-input" name="topic" placeholder="<?php esc_attr_e( 'e.g., WordPress Development, E-commerce, Digital Marketing', '365i-ai-faq-generator' ); ?>" required>
					</div>
					
					<div class="form-row">
						<label for="topic-count"><?php esc_html_e( 'Number of FAQs', '365i-ai-faq-generator' ); ?></label>
						<input type="number" id="topic-count" name="count" min="1" max="50" value="<?php echo esc_attr( isset( $settings['default_faq_count'] ) ? $settings['default_faq_count'] : 12 ); ?>">
					</div>
					
					<div class="form-actions">
						<button type="submit" class="button button-primary">
							<span class="dashicons dashicons-update"></span>
							<?php esc_html_e( 'Generate FAQ', '365i-ai-faq-generator' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>

		<!-- URL Extraction Method -->
		<div class="method-panel" id="url-method">
			<div class="method-content">
				<h4><?php esc_html_e( 'Extract FAQ from URL', '365i-ai-faq-generator' ); ?></h4>
				<p><?php esc_html_e( 'Extract existing FAQ content from a webpage and enhance it with AI.', '365i-ai-faq-generator' ); ?></p>
				
				<form id="url-extraction-form" class="generation-form">
					<div class="form-row">
						<label for="url-input"><?php esc_html_e( 'Website URL', '365i-ai-faq-generator' ); ?></label>
						<input type="url" id="url-input" name="url" placeholder="<?php esc_attr_e( 'https://example.com/faq', '365i-ai-faq-generator' ); ?>" required>
					</div>
					
					<div class="form-actions">
						<button type="submit" class="button button-primary">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Extract FAQ', '365i-ai-faq-generator' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>

		<!-- Enhancement Method -->
		<div class="method-panel" id="enhance-method">
			<div class="method-content">
				<h4><?php esc_html_e( 'Enhance Existing FAQ', '365i-ai-faq-generator' ); ?></h4>
				<p><?php esc_html_e( 'Import existing FAQ data and enhance it with AI improvements.', '365i-ai-faq-generator' ); ?></p>
				
				<form id="enhancement-form" class="generation-form">
					<div class="form-row">
						<label for="faq-import"><?php esc_html_e( 'Import FAQ Data', '365i-ai-faq-generator' ); ?></label>
						<textarea id="faq-import" name="faq_data" rows="10" placeholder="<?php esc_attr_e( 'Paste your existing FAQ content here...', '365i-ai-faq-generator' ); ?>"></textarea>
					</div>
					
					<div class="form-actions">
						<button type="submit" class="button button-primary">
							<span class="dashicons dashicons-editor-spellcheck"></span>
							<?php esc_html_e( 'Enhance FAQ', '365i-ai-faq-generator' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Quick Start Guide -->
	<div class="ai-faq-gen-section quick-start-section">
		<h3>
			<span class="dashicons dashicons-info"></span>
			<?php esc_html_e( 'How to Use', '365i-ai-faq-generator' ); ?>
		</h3>
		
		<div class="quick-start-steps">
			<div class="step">
				<div class="step-number">1</div>
				<div class="step-content">
					<h4><?php esc_html_e( 'Choose Generation Method', '365i-ai-faq-generator' ); ?></h4>
					<p><?php esc_html_e( 'Select whether to generate from a topic, extract from a URL, or enhance existing content.', '365i-ai-faq-generator' ); ?></p>
				</div>
			</div>
			
			<div class="step">
				<div class="step-number">2</div>
				<div class="step-content">
					<h4><?php esc_html_e( 'Provide Input', '365i-ai-faq-generator' ); ?></h4>
					<p><?php esc_html_e( 'Enter your topic, URL, or paste existing FAQ content depending on your chosen method.', '365i-ai-faq-generator' ); ?></p>
				</div>
			</div>
			
			<div class="step">
				<div class="step-number">3</div>
				<div class="step-content">
					<h4><?php esc_html_e( 'Generate & Review', '365i-ai-faq-generator' ); ?></h4>
					<p><?php esc_html_e( 'Click generate and review the AI-created FAQ content. You can then export or use the shortcode.', '365i-ai-faq-generator' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<!-- Shortcode Information -->
	<div class="ai-faq-gen-section shortcode-info-section">
		<h3>
			<span class="dashicons dashicons-editor-code"></span>
			<?php esc_html_e( 'Frontend Usage', '365i-ai-faq-generator' ); ?>
		</h3>
		
		<p><?php esc_html_e( 'You can also use the frontend FAQ generator tool on any page or post using the shortcode:', '365i-ai-faq-generator' ); ?></p>
		
		<div class="shortcode-example">
			<code>[ai_faq_generator]</code>
			<button type="button" class="button button-small copy-shortcode" data-shortcode="[ai_faq_generator]">
				<?php esc_html_e( 'Copy', '365i-ai-faq-generator' ); ?>
			</button>
		</div>
		
		<p><?php esc_html_e( 'The frontend tool allows public users to generate and export FAQ content without accessing the WordPress admin.', '365i-ai-faq-generator' ); ?></p>
	</div>

</div><!-- .ai-faq-gen-generator -->

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
	// Method tab switching
	const methodTabs = document.querySelectorAll('.method-tab');
	const methodPanels = document.querySelectorAll('.method-panel');
	
	methodTabs.forEach(function(tab) {
		tab.addEventListener('click', function() {
			const method = this.getAttribute('data-method');
			
			// Update active tab
			methodTabs.forEach(function(t) { t.classList.remove('active'); });
			this.classList.add('active');
			
			// Update active panel
			methodPanels.forEach(function(panel) {
				panel.classList.remove('active');
				if (panel.id === method + '-method') {
					panel.classList.add('active');
				}
			});
		});
	});
	
	// Form submissions (placeholder - will be enhanced in Phase 2)
	const forms = document.querySelectorAll('.generation-form');
	forms.forEach(function(form) {
		form.addEventListener('submit', function(e) {
			e.preventDefault();
			alert('<?php echo esc_js( __( 'FAQ generation functionality will be implemented in Phase 2.', '365i-ai-faq-generator' ) ); ?>');
		});
	});
});
</script>

<?php
// Include footer.
include AI_FAQ_GEN_DIR . 'templates/partials/footer.php';
?>