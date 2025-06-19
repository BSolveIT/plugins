<?php
/**
 * Frontend FAQ Generator template for shortcode display.
 * 
 * This template is used by the [ai_faq_generator] shortcode to display
 * the FAQ generation form and FAQ content on the frontend.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Templates
 * @since 2.0.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get shortcode attributes with defaults.
$attributes = wp_parse_args( $attributes, array(
	'show_form' => true,
	'show_search' => true,
	'layout' => 'accordion',
	'schema_type' => 'json-ld',
	'auto_open' => false,
	'theme' => 'default',
	'max_questions' => 10,
) );

// Convert string booleans to actual booleans.
$show_form = filter_var( $attributes['show_form'], FILTER_VALIDATE_BOOLEAN );
$show_search = filter_var( $attributes['show_search'], FILTER_VALIDATE_BOOLEAN );
$auto_open = filter_var( $attributes['auto_open'], FILTER_VALIDATE_BOOLEAN );

// Sanitize attributes.
$layout = sanitize_key( $attributes['layout'] );
$schema_type = sanitize_key( $attributes['schema_type'] );
$theme = sanitize_key( $attributes['theme'] );
$max_questions = absint( $attributes['max_questions'] );

// Get any existing FAQs passed to the template.
$faqs = isset( $faqs ) ? $faqs : array();

// Generate unique ID for this instance.
$instance_id = 'ai-faq-' . wp_rand( 1000, 9999 );

/**
 * Filter the FAQ generator container classes.
 * 
 * @since 2.0.0
 * 
 * @param array $classes Array of CSS classes.
 * @param array $attributes Shortcode attributes.
 */
$container_classes = apply_filters( 'ai_faq_generator_container_classes', array(
	'ai-faq-generator',
	'ai-faq-theme-' . $theme,
	'ai-faq-layout-' . $layout,
), $attributes );

?>

<div id="<?php echo esc_attr( $instance_id ); ?>" class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>" data-instance="<?php echo esc_attr( $instance_id ); ?>">

	<?php if ( $show_form ) : ?>
		<!-- FAQ Generation Form -->
		<div class="ai-faq-form-container">
			<!-- Header Section -->
			<div class="ai-faq-header">
				<h1 class="ai-faq-header-title">✨ <?php esc_html_e( 'AI FAQ Generator', '365i-ai-faq-generator' ); ?></h1>
				<p class="ai-faq-header-subtitle"><?php esc_html_e( 'Create professional FAQs in seconds with the power of AI', '365i-ai-faq-generator' ); ?></p>
			</div>
			
			<form class="ai-faq-form" method="post" data-max-questions="<?php echo esc_attr( $max_questions ); ?>">
				<?php wp_nonce_field( 'ai_faq_generate_frontend', 'ai_faq_nonce' ); ?>
				
				<!-- Source Method Section -->
				<div class="ai-faq-form-section">
					<div class="ai-faq-section-title">
						<div class="ai-faq-section-icon">1</div>
						<?php esc_html_e( 'Choose FAQ Source Method', '365i-ai-faq-generator' ); ?>
					</div>
					
					<div class="ai-faq-method-selector">
						<input type="radio" id="method_import_url_<?php echo esc_attr( $instance_id ); ?>" name="generation_method" value="import_url" class="ai-faq-radio-input" checked>
						<label for="method_import_url_<?php echo esc_attr( $instance_id ); ?>" class="ai-faq-method-card active">
							<div class="ai-faq-method-icon">🌐</div>
							<div class="ai-faq-method-content">
								<div class="ai-faq-method-title"><?php esc_html_e( 'Import from URL', '365i-ai-faq-generator' ); ?></div>
								<div class="ai-faq-method-description"><?php esc_html_e( 'Extract existing FAQs from a webpage', '365i-ai-faq-generator' ); ?></div>
							</div>
						</label>
						
						<input type="radio" id="method_ai_url_<?php echo esc_attr( $instance_id ); ?>" name="generation_method" value="ai_url" class="ai-faq-radio-input">
						<label for="method_ai_url_<?php echo esc_attr( $instance_id ); ?>" class="ai-faq-method-card">
							<div class="ai-faq-method-icon">🤖</div>
							<div class="ai-faq-method-content">
								<div class="ai-faq-method-title"><?php esc_html_e( 'AI Generate from URL', '365i-ai-faq-generator' ); ?></div>
								<div class="ai-faq-method-description"><?php esc_html_e( 'Create FAQs based on webpage content', '365i-ai-faq-generator' ); ?></div>
							</div>
						</label>
						
						<input type="radio" id="method_import_schema_<?php echo esc_attr( $instance_id ); ?>" name="generation_method" value="import_schema" class="ai-faq-radio-input">
						<label for="method_import_schema_<?php echo esc_attr( $instance_id ); ?>" class="ai-faq-method-card">
							<div class="ai-faq-method-icon">📋</div>
							<div class="ai-faq-method-content">
								<div class="ai-faq-method-title"><?php esc_html_e( 'Import from Schema', '365i-ai-faq-generator' ); ?></div>
								<div class="ai-faq-method-description"><?php esc_html_e( 'Import from existing FAQ schema markup', '365i-ai-faq-generator' ); ?></div>
							</div>
						</label>
						
						<input type="radio" id="method_manual_<?php echo esc_attr( $instance_id ); ?>" name="generation_method" value="manual" class="ai-faq-radio-input">
						<label for="method_manual_<?php echo esc_attr( $instance_id ); ?>" class="ai-faq-method-card">
							<div class="ai-faq-method-icon">✏️</div>
							<div class="ai-faq-method-content">
								<div class="ai-faq-method-title"><?php esc_html_e( 'Manual Creation', '365i-ai-faq-generator' ); ?></div>
								<div class="ai-faq-method-description"><?php esc_html_e( 'Add and edit FAQs manually', '365i-ai-faq-generator' ); ?></div>
							</div>
						</label>
					</div>
				</div>
				
				<!-- URL Import Section -->
				<div class="ai-faq-form-section" id="url-import-content">
					<div class="ai-faq-section-title">
						<div class="ai-faq-section-icon">2</div>
						<?php esc_html_e( 'URL to Import From', '365i-ai-faq-generator' ); ?>
					</div>
					
					<div class="ai-faq-form-group">
						<label class="ai-faq-form-label"><?php esc_html_e( 'Enter the URL containing existing FAQs', '365i-ai-faq-generator' ); ?></label>
						<input type="url" id="import_url_<?php echo esc_attr( $instance_id ); ?>" name="import_url" class="ai-faq-form-input" placeholder="<?php esc_attr_e( 'https://example.com/faq-page', '365i-ai-faq-generator' ); ?>">
					</div>
				</div>
				
				<!-- AI URL Generation Section -->
				<div class="ai-faq-form-section" id="ai-url-content" style="display: none;">
					<div class="ai-faq-section-title">
						<div class="ai-faq-section-icon">2</div>
						<?php esc_html_e( 'URL for AI Analysis', '365i-ai-faq-generator' ); ?>
					</div>
					
					<div class="ai-faq-form-group">
						<label class="ai-faq-form-label"><?php esc_html_e( 'Enter the URL to analyze for FAQ generation', '365i-ai-faq-generator' ); ?></label>
						<input type="url" id="ai_url_<?php echo esc_attr( $instance_id ); ?>" name="ai_url" class="ai-faq-form-input" placeholder="<?php esc_attr_e( 'https://example.com/product-page', '365i-ai-faq-generator' ); ?>">
					</div>
				</div>
				
				<!-- Schema Import Section -->
				<div class="ai-faq-form-section" id="schema-import-content" style="display: none;">
					<div class="ai-faq-section-title">
						<div class="ai-faq-section-icon">2</div>
						<?php esc_html_e( 'FAQ Schema to Import', '365i-ai-faq-generator' ); ?>
					</div>
					
					<div class="ai-faq-form-group">
						<label class="ai-faq-form-label"><?php esc_html_e( 'Paste your existing FAQ schema markup (JSON-LD, Microdata, or RDFa)', '365i-ai-faq-generator' ); ?></label>
						<textarea id="schema_import_<?php echo esc_attr( $instance_id ); ?>" name="schema_import" class="ai-faq-form-textarea" rows="8" placeholder="<?php esc_attr_e( 'Paste your FAQ schema here...', '365i-ai-faq-generator' ); ?>"></textarea>
					</div>
				</div>
				
				<!-- Manual Creation Section -->
				<div class="ai-faq-form-section" id="manual-content" style="display: none;">
					<div class="ai-faq-section-title">
						<div class="ai-faq-section-icon">2</div>
						<?php esc_html_e( 'Manual FAQ Creation', '365i-ai-faq-generator' ); ?>
					</div>
					
					<div class="ai-faq-manual-editor">
						<div class="ai-faq-manual-toolbar">
							<button type="button" class="ai-faq-add-question-btn">
								<span class="ai-faq-btn-icon">➕</span>
								<?php esc_html_e( 'Add Question', '365i-ai-faq-generator' ); ?>
							</button>
							<button type="button" class="ai-faq-load-template-btn">
								<span class="ai-faq-btn-icon">📄</span>
								<?php esc_html_e( 'Load Template', '365i-ai-faq-generator' ); ?>
							</button>
						</div>
						<div class="ai-faq-manual-questions" id="manual-questions-<?php echo esc_attr( $instance_id ); ?>">
							<!-- Manual questions will be added here dynamically -->
						</div>
					</div>
				</div>
				
				<!-- FAQ Page URL Section -->
				<div class="ai-faq-form-section">
					<div class="ai-faq-section-title">
						<div class="ai-faq-section-icon">3</div>
						<?php esc_html_e( 'FAQ Page URL', '365i-ai-faq-generator' ); ?>
					</div>
					
					<div class="ai-faq-form-group">
						<label class="ai-faq-form-label"><?php esc_html_e( 'Where will these FAQs appear? (Required for full schema links)', '365i-ai-faq-generator' ); ?></label>
						<input type="url" id="faq_page_url_<?php echo esc_attr( $instance_id ); ?>" name="faq_page_url" class="ai-faq-form-input" placeholder="<?php esc_attr_e( 'https://yoursite.com/faq/', '365i-ai-faq-generator' ); ?>" value="<?php echo esc_attr( get_permalink() ); ?>">
						<p class="ai-faq-form-help"><?php esc_html_e( 'This URL will be used to generate complete FAQ links in the schema markup.', '365i-ai-faq-generator' ); ?></p>
					</div>
				</div>
				
				<!-- Local Storage Management Section -->
				<div class="ai-faq-form-section">
					<div class="ai-faq-section-title ai-faq-collapsible-header" role="button" tabindex="0" aria-expanded="false" aria-controls="storage-controls-<?php echo esc_attr( $instance_id ); ?>">
						<div class="ai-faq-section-icon">4</div>
						<span class="ai-faq-section-title-text"><?php esc_html_e( 'Save & Load FAQs', '365i-ai-faq-generator' ); ?></span>
						<span class="ai-faq-section-toggle-icon">💾</span>
						<span class="ai-faq-section-arrow">▼</span>
					</div>
					
					<div class="ai-faq-storage-controls ai-faq-collapsible-content" id="storage-controls-<?php echo esc_attr( $instance_id ); ?>" style="display: none;">
						<div class="ai-faq-storage-actions">
							<button type="button" class="ai-faq-storage-btn ai-faq-save-btn">
								<span class="ai-faq-btn-icon">💾</span>
								<span class="ai-faq-btn-text"><?php esc_html_e( 'Save Current', '365i-ai-faq-generator' ); ?></span>
							</button>
							<button type="button" class="ai-faq-storage-btn ai-faq-load-btn">
								<span class="ai-faq-btn-icon">📂</span>
								<span class="ai-faq-btn-text"><?php esc_html_e( 'Load Saved', '365i-ai-faq-generator' ); ?></span>
							</button>
							<button type="button" class="ai-faq-storage-btn ai-faq-export-btn">
								<span class="ai-faq-btn-icon">📤</span>
								<span class="ai-faq-btn-text"><?php esc_html_e( 'Export', '365i-ai-faq-generator' ); ?></span>
							</button>
							<button type="button" class="ai-faq-storage-btn ai-faq-import-btn">
								<span class="ai-faq-btn-icon">📥</span>
								<span class="ai-faq-btn-text"><?php esc_html_e( 'Import', '365i-ai-faq-generator' ); ?></span>
							</button>
						</div>
						
						<div class="ai-faq-version-history">
							<label class="ai-faq-form-label"><?php esc_html_e( 'Version History', '365i-ai-faq-generator' ); ?></label>
							<select class="ai-faq-version-select" id="version-history-<?php echo esc_attr( $instance_id ); ?>">
								<option value=""><?php esc_html_e( 'Select a version to restore...', '365i-ai-faq-generator' ); ?></option>
							</select>
							<button type="button" class="ai-faq-version-restore-btn"><?php esc_html_e( 'Restore', '365i-ai-faq-generator' ); ?></button>
						</div>
						
						<div class="ai-faq-storage-info">
							<div class="ai-faq-storage-status">
								<span class="ai-faq-storage-label"><?php esc_html_e( 'Storage Used:', '365i-ai-faq-generator' ); ?></span>
								<span class="ai-faq-storage-value" id="storage-usage-<?php echo esc_attr( $instance_id ); ?>">0 KB</span>
							</div>
							<div class="ai-faq-last-saved">
								<span class="ai-faq-storage-label"><?php esc_html_e( 'Last Saved:', '365i-ai-faq-generator' ); ?></span>
								<span class="ai-faq-storage-value" id="last-saved-<?php echo esc_attr( $instance_id ); ?>"><?php esc_html_e( 'Never', '365i-ai-faq-generator' ); ?></span>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Generation Settings Section -->
				<div class="ai-faq-form-section">
					<div class="ai-faq-section-title">
						<div class="ai-faq-section-icon">5</div>
						<?php esc_html_e( 'Generation Settings', '365i-ai-faq-generator' ); ?>
					</div>
					
					<div class="ai-faq-settings-grid">
						<div class="ai-faq-slider-group">
							<div class="ai-faq-slider-header">
								<label class="ai-faq-slider-label"><?php esc_html_e( 'Number of Questions', '365i-ai-faq-generator' ); ?></label>
								<div class="ai-faq-slider-value" id="num_questions_value_<?php echo esc_attr( $instance_id ); ?>">10 questions</div>
							</div>
							<div class="ai-faq-slider-container">
								<input type="range" class="ai-faq-slider" id="num_questions_<?php echo esc_attr( $instance_id ); ?>" name="num_questions" min="6" max="20" value="10">
							</div>
						</div>
						
						<div class="ai-faq-slider-group">
							<div class="ai-faq-slider-header">
								<label class="ai-faq-slider-label"><?php esc_html_e( 'Answer Length', '365i-ai-faq-generator' ); ?></label>
								<div class="ai-faq-slider-value" id="length_value_<?php echo esc_attr( $instance_id ); ?>">Medium</div>
							</div>
							<div class="ai-faq-slider-container">
								<input type="range" class="ai-faq-slider" id="length_<?php echo esc_attr( $instance_id ); ?>" name="length" min="1" max="4" value="2">
								<div class="ai-faq-slider-labels">
									<span><?php esc_html_e( 'Short', '365i-ai-faq-generator' ); ?></span>
									<span><?php esc_html_e( 'Medium', '365i-ai-faq-generator' ); ?></span>
									<span><?php esc_html_e( 'Long', '365i-ai-faq-generator' ); ?></span>
									<span><?php esc_html_e( 'Detailed', '365i-ai-faq-generator' ); ?></span>
								</div>
							</div>
						</div>
					</div>
					
					<div class="ai-faq-button-group">
						<label class="ai-faq-form-label"><?php esc_html_e( 'Tone', '365i-ai-faq-generator' ); ?></label>
						<div class="ai-faq-tone-selector">
							<input type="radio" id="tone_professional_<?php echo esc_attr( $instance_id ); ?>" name="tone" value="professional" class="ai-faq-radio-input" checked>
							<label for="tone_professional_<?php echo esc_attr( $instance_id ); ?>" class="ai-faq-tone-option active">
								<div class="ai-faq-tone-icon">🎩</div>
								<div class="ai-faq-tone-title"><?php esc_html_e( 'Professional', '365i-ai-faq-generator' ); ?></div>
								<div class="ai-faq-tone-description"><?php esc_html_e( 'Formal and business-focused', '365i-ai-faq-generator' ); ?></div>
							</label>
							
							<input type="radio" id="tone_friendly_<?php echo esc_attr( $instance_id ); ?>" name="tone" value="friendly" class="ai-faq-radio-input">
							<label for="tone_friendly_<?php echo esc_attr( $instance_id ); ?>" class="ai-faq-tone-option">
								<div class="ai-faq-tone-icon">😊</div>
								<div class="ai-faq-tone-title"><?php esc_html_e( 'Friendly', '365i-ai-faq-generator' ); ?></div>
								<div class="ai-faq-tone-description"><?php esc_html_e( 'Warm and approachable', '365i-ai-faq-generator' ); ?></div>
							</label>
							
							<input type="radio" id="tone_casual_<?php echo esc_attr( $instance_id ); ?>" name="tone" value="casual" class="ai-faq-radio-input">
							<label for="tone_casual_<?php echo esc_attr( $instance_id ); ?>" class="ai-faq-tone-option">
								<div class="ai-faq-tone-icon">👋</div>
								<div class="ai-faq-tone-title"><?php esc_html_e( 'Casual', '365i-ai-faq-generator' ); ?></div>
								<div class="ai-faq-tone-description"><?php esc_html_e( 'Relaxed and conversational', '365i-ai-faq-generator' ); ?></div>
							</label>
							
							<input type="radio" id="tone_authoritative_<?php echo esc_attr( $instance_id ); ?>" name="tone" value="authoritative" class="ai-faq-radio-input">
							<label for="tone_authoritative_<?php echo esc_attr( $instance_id ); ?>" class="ai-faq-tone-option">
								<div class="ai-faq-tone-icon">🎯</div>
								<div class="ai-faq-tone-title"><?php esc_html_e( 'Authoritative', '365i-ai-faq-generator' ); ?></div>
								<div class="ai-faq-tone-description"><?php esc_html_e( 'Expert and commanding', '365i-ai-faq-generator' ); ?></div>
							</label>
						</div>
					</div>
					
					<div class="ai-faq-button-group">
						<label class="ai-faq-form-label"><?php esc_html_e( 'Schema Format', '365i-ai-faq-generator' ); ?></label>
						<div class="ai-faq-schema-selector">
							<input type="radio" id="schema_json_ld_<?php echo esc_attr( $instance_id ); ?>" name="schema_output" value="json-ld" class="ai-faq-radio-input" checked>
							<label for="schema_json_ld_<?php echo esc_attr( $instance_id ); ?>" class="ai-faq-schema-option active">
								<div class="ai-faq-schema-icon">📋</div>
								<div class="ai-faq-schema-title"><?php esc_html_e( 'JSON-LD', '365i-ai-faq-generator' ); ?></div>
								<div class="ai-faq-schema-description"><?php esc_html_e( 'Google\'s preferred format', '365i-ai-faq-generator' ); ?></div>
							</label>
							
							<input type="radio" id="schema_microdata_<?php echo esc_attr( $instance_id ); ?>" name="schema_output" value="microdata" class="ai-faq-radio-input">
							<label for="schema_microdata_<?php echo esc_attr( $instance_id ); ?>" class="ai-faq-schema-option">
								<div class="ai-faq-schema-icon">🔍</div>
								<div class="ai-faq-schema-title"><?php esc_html_e( 'Microdata', '365i-ai-faq-generator' ); ?></div>
								<div class="ai-faq-schema-description"><?php esc_html_e( 'HTML5 structured data', '365i-ai-faq-generator' ); ?></div>
							</label>
							
							<input type="radio" id="schema_rdfa_<?php echo esc_attr( $instance_id ); ?>" name="schema_output" value="rdfa" class="ai-faq-radio-input">
							<label for="schema_rdfa_<?php echo esc_attr( $instance_id ); ?>" class="ai-faq-schema-option">
								<div class="ai-faq-schema-icon">🔗</div>
								<div class="ai-faq-schema-title"><?php esc_html_e( 'RDFa', '365i-ai-faq-generator' ); ?></div>
								<div class="ai-faq-schema-description"><?php esc_html_e( 'Semantic web standard', '365i-ai-faq-generator' ); ?></div>
							</label>
							
							<input type="radio" id="schema_html_<?php echo esc_attr( $instance_id ); ?>" name="schema_output" value="html" class="ai-faq-radio-input">
							<label for="schema_html_<?php echo esc_attr( $instance_id ); ?>" class="ai-faq-schema-option">
								<div class="ai-faq-schema-icon">📄</div>
								<div class="ai-faq-schema-title"><?php esc_html_e( 'HTML', '365i-ai-faq-generator' ); ?></div>
								<div class="ai-faq-schema-description"><?php esc_html_e( 'Plain HTML format', '365i-ai-faq-generator' ); ?></div>
							</label>
						</div>
					</div>
				</div>
				
				<!-- Generate Action -->
				<div class="ai-faq-form-section">
					<div class="ai-faq-section-title">
						<div class="ai-faq-section-icon">6</div>
						<?php esc_html_e( 'Generate Your FAQs', '365i-ai-faq-generator' ); ?>
					</div>
					
					<div class="ai-faq-generation-action">
						<button type="submit" class="ai-faq-generate-btn">
							<div class="ai-faq-btn-content">
								<div class="ai-faq-btn-icon">✨</div>
								<div class="ai-faq-btn-text">
									<span class="ai-faq-btn-title"><?php esc_html_e( 'Generate FAQs', '365i-ai-faq-generator' ); ?></span>
									<span class="ai-faq-btn-subtitle"><?php esc_html_e( 'Create AI-powered FAQ content', '365i-ai-faq-generator' ); ?></span>
								</div>
							</div>
						</button>
						
						<div class="ai-faq-generation-status" style="display: none;">
							<div class="ai-faq-progress-bar">
								<div class="ai-faq-progress-fill"></div>
							</div>
							<div class="ai-faq-status-text"><?php esc_html_e( 'Generating your FAQs...', '365i-ai-faq-generator' ); ?></div>
						</div>
					</div>
				</div>
			</form>
		</div>
	<?php endif; ?>

	<?php if ( $show_search && ! empty( $faqs ) ) : ?>
		<!-- FAQ Search -->
		<div class="ai-faq-search">
			<input type="search" class="ai-faq-search-input" placeholder="<?php esc_attr_e( 'Search FAQs...', '365i-ai-faq-generator' ); ?>" aria-label="<?php esc_attr_e( 'Search FAQs', '365i-ai-faq-generator' ); ?>">
			<span class="ai-faq-search-icon">🔍</span>
		</div>
	<?php endif; ?>

	<!-- FAQ Display Container -->
	<div class="ai-faq-container">
		<?php if ( ! empty( $faqs ) ) : ?>
			
			<?php
			/**
			 * Filter the FAQ display layout.
			 * 
			 * @since 2.0.0
			 * 
			 * @param string $layout Layout type (accordion, grid, list).
			 * @param array $attributes Shortcode attributes.
			 */
			$display_layout = apply_filters( 'ai_faq_generator_display_layout', $layout, $attributes );
			
			// Determine wrapper class based on layout.
			$wrapper_class = 'ai-faq-list';
			if ( 'grid' === $display_layout ) {
				$wrapper_class .= ' ai-faq-grid';
			} elseif ( 'accordion' === $display_layout ) {
				$wrapper_class .= ' ai-faq-accordion';
			}
			?>
			
			<div class="<?php echo esc_attr( $wrapper_class ); ?>" role="tablist" aria-label="<?php esc_attr_e( 'Frequently Asked Questions', '365i-ai-faq-generator' ); ?>">
				
				<?php foreach ( $faqs as $index => $faq ) : ?>
					<?php
					$question_id = $instance_id . '-question-' . $index;
					$answer_id = $instance_id . '-answer-' . $index;
					$is_open = $auto_open && 0 === $index;
					
					/**
					 * Filter individual FAQ item data.
					 * 
					 * @since 2.0.0
					 * 
					 * @param array $faq FAQ item data.
					 * @param int $index FAQ item index.
					 * @param array $attributes Shortcode attributes.
					 */
					$faq = apply_filters( 'ai_faq_generator_faq_item', $faq, $index, $attributes );
					?>
					
					<div class="ai-faq-item<?php echo $is_open ? ' active' : ''; ?>" itemscope itemtype="https://schema.org/Question">
						<button class="ai-faq-question" 
								id="<?php echo esc_attr( $question_id ); ?>"
								aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
								aria-controls="<?php echo esc_attr( $answer_id ); ?>"
								role="tab"
								tabindex="0">
							<span itemprop="name"><?php echo esc_html( $faq['question'] ); ?></span>
						</button>
						
						<div class="ai-faq-answer" 
							 id="<?php echo esc_attr( $answer_id ); ?>"
							 role="tabpanel"
							 aria-labelledby="<?php echo esc_attr( $question_id ); ?>"
							 itemscope 
							 itemtype="https://schema.org/Answer"
							 <?php echo $is_open ? '' : 'style="display: none;"'; ?>>
							<div itemprop="text">
								<?php
								// Format the answer with proper HTML.
								$formatted_answer = wp_kses_post( wpautop( $faq['answer'] ) );
								
								/**
								 * Filter the formatted FAQ answer.
								 * 
								 * @since 2.0.0
								 * 
								 * @param string $formatted_answer Formatted answer HTML.
								 * @param array $faq Original FAQ item data.
								 * @param int $index FAQ item index.
								 */
								echo apply_filters( 'ai_faq_generator_formatted_answer', $formatted_answer, $faq, $index );
								?>
							</div>
						</div>
					</div>
					
				<?php endforeach; ?>
				
			</div>
			
		<?php else : ?>
			
			<!-- No FAQs Message -->
			<div class="ai-faq-empty">
				<p><?php esc_html_e( 'No FAQs to display. Use the form above to generate new FAQs.', '365i-ai-faq-generator' ); ?></p>
			</div>
			
		<?php endif; ?>
	</div>

	<?php if ( 'json-ld' === $schema_type && ! empty( $faqs ) ) : ?>
		<!-- JSON-LD Schema -->
		<script type="application/ld+json">
		<?php
		$schema_data = array(
			'@context' => 'https://schema.org',
			'@type' => 'FAQPage',
			'mainEntity' => array(),
		);
		
		foreach ( $faqs as $faq ) {
			$schema_data['mainEntity'][] = array(
				'@type' => 'Question',
				'name' => $faq['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text' => strip_tags( $faq['answer'] ),
				),
			);
		}
		
		/**
		 * Filter the JSON-LD schema data.
		 * 
		 * @since 2.0.0
		 * 
		 * @param array $schema_data Schema data array.
		 * @param array $faqs FAQ items.
		 * @param array $attributes Shortcode attributes.
		 */
		$schema_data = apply_filters( 'ai_faq_generator_schema_data', $schema_data, $faqs, $attributes );
		
		echo wp_json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		?>
		</script>
	<?php endif; ?>

</div><!-- .ai-faq-generator -->