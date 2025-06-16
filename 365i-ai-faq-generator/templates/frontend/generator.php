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
			<form class="ai-faq-form" method="post" data-max-questions="<?php echo esc_attr( $max_questions ); ?>">
				<?php wp_nonce_field( 'ai_faq_generate_frontend', 'ai_faq_nonce' ); ?>
				
				<h3 class="ai-faq-form-title">
					<?php esc_html_e( 'Generate AI-Powered FAQs', '365i-ai-faq-generator' ); ?>
				</h3>
				
				<!-- Generation Method Selection -->
				<div class="ai-faq-form-row">
					<label class="ai-faq-form-label" for="generation_method_<?php echo esc_attr( $instance_id ); ?>">
						<?php esc_html_e( 'Generation Method', '365i-ai-faq-generator' ); ?>
					</label>
					<select id="generation_method_<?php echo esc_attr( $instance_id ); ?>" name="generation_method" class="ai-faq-form-select" required>
						<option value=""><?php esc_html_e( 'Select method...', '365i-ai-faq-generator' ); ?></option>
						<option value="topic"><?php esc_html_e( 'From Topic/Keywords', '365i-ai-faq-generator' ); ?></option>
						<option value="url"><?php esc_html_e( 'From Website URL', '365i-ai-faq-generator' ); ?></option>
						<option value="enhance"><?php esc_html_e( 'Enhance Existing FAQ', '365i-ai-faq-generator' ); ?></option>
					</select>
					<p class="ai-faq-form-help"><?php esc_html_e( 'Choose how you want to generate your FAQs.', '365i-ai-faq-generator' ); ?></p>
				</div>
				
				<!-- Topic Input (shown when method is 'topic') -->
				<div class="ai-faq-form-row" data-show-when="generation_method" data-show-value="topic" style="display: none;">
					<label class="ai-faq-form-label" for="topic_<?php echo esc_attr( $instance_id ); ?>">
						<?php esc_html_e( 'Topic or Keywords', '365i-ai-faq-generator' ); ?>
					</label>
					<textarea id="topic_<?php echo esc_attr( $instance_id ); ?>" name="topic" class="ai-faq-form-textarea" rows="3" placeholder="<?php esc_attr_e( 'e.g., WordPress hosting, SEO optimization, e-commerce marketing...', '365i-ai-faq-generator' ); ?>"></textarea>
					<p class="ai-faq-form-help"><?php esc_html_e( 'Describe your topic or provide keywords for FAQ generation.', '365i-ai-faq-generator' ); ?></p>
				</div>
				
				<!-- URL Input (shown when method is 'url') -->
				<div class="ai-faq-form-row" data-show-when="generation_method" data-show-value="url" style="display: none;">
					<label class="ai-faq-form-label" for="url_<?php echo esc_attr( $instance_id ); ?>">
						<?php esc_html_e( 'Website URL', '365i-ai-faq-generator' ); ?>
					</label>
					<input type="url" id="url_<?php echo esc_attr( $instance_id ); ?>" name="url" class="ai-faq-form-input" placeholder="https://example.com">
					<p class="ai-faq-form-help"><?php esc_html_e( 'Enter the URL of the website to analyze and generate FAQs from.', '365i-ai-faq-generator' ); ?></p>
				</div>
				
				<!-- Existing FAQ Input (shown when method is 'enhance') -->
				<div class="ai-faq-form-row" data-show-when="generation_method" data-show-value="enhance" style="display: none;">
					<label class="ai-faq-form-label" for="existing_faq_<?php echo esc_attr( $instance_id ); ?>">
						<?php esc_html_e( 'Existing FAQ Content', '365i-ai-faq-generator' ); ?>
					</label>
					<textarea id="existing_faq_<?php echo esc_attr( $instance_id ); ?>" name="existing_faq" class="ai-faq-form-textarea" rows="5" placeholder="<?php esc_attr_e( 'Paste your existing FAQ content here...', '365i-ai-faq-generator' ); ?>"></textarea>
					<p class="ai-faq-form-help"><?php esc_html_e( 'Provide existing FAQ content to enhance and improve.', '365i-ai-faq-generator' ); ?></p>
				</div>
				
				<!-- Generation Options -->
				<div class="ai-faq-form-options">
					<div class="ai-faq-form-option">
						<label class="ai-faq-form-label" for="num_questions_<?php echo esc_attr( $instance_id ); ?>">
							<?php esc_html_e( 'Number of Questions', '365i-ai-faq-generator' ); ?>
						</label>
						<select id="num_questions_<?php echo esc_attr( $instance_id ); ?>" name="num_questions" class="ai-faq-form-select">
							<option value="5">5 <?php esc_html_e( 'questions', '365i-ai-faq-generator' ); ?></option>
							<option value="10" selected>10 <?php esc_html_e( 'questions', '365i-ai-faq-generator' ); ?></option>
							<option value="15">15 <?php esc_html_e( 'questions', '365i-ai-faq-generator' ); ?></option>
							<option value="20">20 <?php esc_html_e( 'questions', '365i-ai-faq-generator' ); ?></option>
						</select>
					</div>
					
					<div class="ai-faq-form-option">
						<label class="ai-faq-form-label" for="tone_<?php echo esc_attr( $instance_id ); ?>">
							<?php esc_html_e( 'Tone', '365i-ai-faq-generator' ); ?>
						</label>
						<select id="tone_<?php echo esc_attr( $instance_id ); ?>" name="tone" class="ai-faq-form-select">
							<option value="professional"><?php esc_html_e( 'Professional', '365i-ai-faq-generator' ); ?></option>
							<option value="friendly"><?php esc_html_e( 'Friendly', '365i-ai-faq-generator' ); ?></option>
							<option value="casual"><?php esc_html_e( 'Casual', '365i-ai-faq-generator' ); ?></option>
							<option value="technical"><?php esc_html_e( 'Technical', '365i-ai-faq-generator' ); ?></option>
							<option value="conversational"><?php esc_html_e( 'Conversational', '365i-ai-faq-generator' ); ?></option>
						</select>
					</div>
					
					<div class="ai-faq-form-option">
						<label class="ai-faq-form-label" for="length_<?php echo esc_attr( $instance_id ); ?>">
							<?php esc_html_e( 'Answer Length', '365i-ai-faq-generator' ); ?>
						</label>
						<select id="length_<?php echo esc_attr( $instance_id ); ?>" name="length" class="ai-faq-form-select">
							<option value="short"><?php esc_html_e( 'Short', '365i-ai-faq-generator' ); ?></option>
							<option value="medium" selected><?php esc_html_e( 'Medium', '365i-ai-faq-generator' ); ?></option>
							<option value="long"><?php esc_html_e( 'Long', '365i-ai-faq-generator' ); ?></option>
						</select>
					</div>
				</div>
				
				<!-- Advanced Options -->
				<details class="ai-faq-advanced-options">
					<summary><?php esc_html_e( 'Advanced Options', '365i-ai-faq-generator' ); ?></summary>
					
					<div class="ai-faq-form-options">
						<div class="ai-faq-form-option">
							<label class="ai-faq-form-label" for="schema_output_<?php echo esc_attr( $instance_id ); ?>">
								<?php esc_html_e( 'Schema Format', '365i-ai-faq-generator' ); ?>
							</label>
							<select id="schema_output_<?php echo esc_attr( $instance_id ); ?>" name="schema_output" class="ai-faq-form-select">
								<option value="json-ld" <?php selected( $schema_type, 'json-ld' ); ?>><?php esc_html_e( 'JSON-LD', '365i-ai-faq-generator' ); ?></option>
								<option value="microdata" <?php selected( $schema_type, 'microdata' ); ?>><?php esc_html_e( 'Microdata', '365i-ai-faq-generator' ); ?></option>
								<option value="rdfa" <?php selected( $schema_type, 'rdfa' ); ?>><?php esc_html_e( 'RDFa', '365i-ai-faq-generator' ); ?></option>
								<option value="html" <?php selected( $schema_type, 'html' ); ?>><?php esc_html_e( 'Plain HTML', '365i-ai-faq-generator' ); ?></option>
							</select>
						</div>
						
						<div class="ai-faq-form-option">
							<label class="ai-faq-form-label">
								<input type="checkbox" name="enable_seo" value="1" checked>
								<?php esc_html_e( 'SEO Optimization', '365i-ai-faq-generator' ); ?>
							</label>
						</div>
						
						<div class="ai-faq-form-option">
							<label class="ai-faq-form-label">
								<input type="checkbox" name="prevent_duplicates" value="1" checked>
								<?php esc_html_e( 'Prevent Duplicates', '365i-ai-faq-generator' ); ?>
							</label>
						</div>
					</div>
				</details>
				
				<!-- Submit Button -->
				<button type="submit" class="ai-faq-generate-btn">
					<span class="text"><?php esc_html_e( 'Generate FAQs', '365i-ai-faq-generator' ); ?></span>
					<span class="spinner"></span>
				</button>
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

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
	// Show/hide conditional form fields
	const methodSelect = document.getElementById('generation_method_<?php echo esc_js( $instance_id ); ?>');
	if (methodSelect) {
		methodSelect.addEventListener('change', function() {
			// Hide all conditional fields first
			document.querySelectorAll('[data-show-when="generation_method"]').forEach(function(field) {
				field.style.display = 'none';
			});
			
			// Show the relevant field
			if (this.value) {
				const targetField = document.querySelector('[data-show-when="generation_method"][data-show-value="' + this.value + '"]');
				if (targetField) {
					targetField.style.display = 'block';
				}
			}
		});
	}
});
</script>