<?php
/**
 * Frontend functionality class for 365i AI FAQ Generator.
 * 
 * This class handles all frontend-related functionality including
 * shortcode implementation, asset enqueuing, and public-facing features.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Frontend
 * @since 2.0.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend functionality class.
 * 
 * Manages shortcode implementation, frontend asset loading,
 * and public-facing functionality.
 * 
 * @since 2.0.0
 */
class AI_FAQ_Frontend {

	/**
	 * Shortcode tag.
	 * 
	 * @since 2.0.0
	 * @var string
	 */
	private $shortcode_tag = 'ai_faq_generator';

	/**
	 * Constructor.
	 * 
	 * Initialize the frontend component.
	 * 
	 * @since 2.0.0
	 */
	public function __construct() {
		// Constructor logic if needed.
	}

	/**
	 * Initialize the frontend component.
	 * 
	 * Set up hooks and filters for frontend functionality.
	 * 
	 * @since 2.0.0
	 */
	public function init() {
		// Register shortcode.
		add_shortcode( $this->shortcode_tag, array( $this, 'render_shortcode' ) );
		
		// Enqueue frontend scripts conditionally.
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
		
		// Add schema markup to head if FAQ is present.
		add_action( 'wp_head', array( $this, 'add_schema_markup' ) );
	}

	/**
	 * Render the FAQ generator shortcode.
	 * 
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_shortcode( $atts ) {
		// Parse shortcode attributes.
		$atts = shortcode_atts(
			array(
				'mode' => 'full', // full, generator-only, display-only
				'theme' => 'default', // default, modern, minimal
				'count' => 12, // default number of FAQs
				'topic' => '', // default topic
				'show_schema' => 'true', // include schema markup
				'show_export' => 'true', // show export options
				'auto_save' => 'true', // enable auto-save
				'class' => '', // additional CSS classes
				'id' => '', // custom container ID
			),
			$atts,
			$this->shortcode_tag
		);

		// Sanitize attributes.
		$atts['mode'] = sanitize_text_field( $atts['mode'] );
		$atts['theme'] = sanitize_text_field( $atts['theme'] );
		$atts['count'] = intval( $atts['count'] );
		$atts['topic'] = sanitize_text_field( $atts['topic'] );
		$atts['show_schema'] = 'true' === $atts['show_schema'];
		$atts['show_export'] = 'true' === $atts['show_export'];
		$atts['auto_save'] = 'true' === $atts['auto_save'];
		$atts['class'] = sanitize_html_class( $atts['class'] );
		$atts['id'] = sanitize_html_class( $atts['id'] );

		// Enqueue assets for this instance.
		$this->enqueue_frontend_assets();

		// Generate unique ID if not provided.
		if ( empty( $atts['id'] ) ) {
			$atts['id'] = 'ai-faq-gen-' . wp_rand( 1000, 9999 );
		}

		// Start output buffering.
		ob_start();
		
		// Include the frontend template.
		$this->render_frontend_template( $atts );
		
		// Get the output and clean the buffer.
		$output = ob_get_clean();
		
		// Apply filters to allow customization.
		$output = apply_filters( 'ai_faq_gen_shortcode_output', $output, $atts );
		
		return $output;
	}

	/**
	 * Render the frontend template.
	 * 
	 * @since 2.0.0
	 * @param array $atts Shortcode attributes.
	 */
	private function render_frontend_template( $atts ) {
		// Set template variables.
		$container_id = esc_attr( $atts['id'] );
		$container_class = 'ai-faq-generator-container ai-faq-theme-' . esc_attr( $atts['theme'] );
		
		if ( ! empty( $atts['class'] ) ) {
			$container_class .= ' ' . esc_attr( $atts['class'] );
		}

		// Get plugin options.
		$options = get_option( 'ai_faq_gen_options', array() );
		$workers = isset( $options['workers'] ) ? $options['workers'] : array();
		
		// Check if workers are available.
		$has_enabled_workers = false;
		foreach ( $workers as $worker ) {
			if ( $worker['enabled'] ) {
				$has_enabled_workers = true;
				break;
			}
		}

		// Include the template file.
		include AI_FAQ_GEN_DIR . 'templates/frontend/generator.php';
	}

	/**
	 * Maybe enqueue frontend assets.
	 * 
	 * Only enqueue if shortcode is present or forced.
	 * 
	 * @since 2.0.0
	 */
	public function maybe_enqueue_assets() {
		global $post;
		
		// Check if shortcode is present in content.
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $this->shortcode_tag ) ) {
			$this->enqueue_frontend_assets();
		}
	}

	/**
	 * Enqueue frontend assets.
	 * 
	 * @since 2.0.0
	 */
	public function enqueue_frontend_assets() {
		// Prevent double enqueuing.
		if ( wp_script_is( 'ai-faq-gen-frontend', 'enqueued' ) ) {
			return;
		}

		// Enqueue frontend CSS.
		wp_enqueue_style(
			'ai-faq-gen-frontend',
			AI_FAQ_GEN_URL . 'assets/css/frontend.css',
			array(),
			AI_FAQ_GEN_VERSION
		);

		// Enqueue frontend JavaScript.
		wp_enqueue_script(
			'ai-faq-gen-frontend',
			AI_FAQ_GEN_URL . 'assets/js/frontend.js',
			array( 'jquery', 'wp-util' ),
			AI_FAQ_GEN_VERSION,
			true
		);

		// Localize script with necessary data.
		$localize_data = array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'ai_faq_gen_nonce' ),
			'strings' => array(
				'loading' => __( 'Loading...', '365i-ai-faq-generator' ),
				'error' => __( 'An error occurred. Please try again.', '365i-ai-faq-generator' ),
				'success' => __( 'Operation completed successfully.', '365i-ai-faq-generator' ),
				'confirm' => __( 'Are you sure?', '365i-ai-faq-generator' ),
				'generating' => __( 'Generating FAQ...', '365i-ai-faq-generator' ),
				'enhancing' => __( 'Enhancing FAQ...', '365i-ai-faq-generator' ),
				'analyzing' => __( 'Analyzing SEO...', '365i-ai-faq-generator' ),
				'extracting' => __( 'Extracting FAQ...', '365i-ai-faq-generator' ),
				'noResults' => __( 'No results found.', '365i-ai-faq-generator' ),
				'invalidUrl' => __( 'Please enter a valid URL.', '365i-ai-faq-generator' ),
				'topicRequired' => __( 'Please enter a topic.', '365i-ai-faq-generator' ),
				'saved' => __( 'FAQ saved successfully.', '365i-ai-faq-generator' ),
				'exported' => __( 'FAQ exported successfully.', '365i-ai-faq-generator' ),
				'copied' => __( 'Copied to clipboard!', '365i-ai-faq-generator' ),
				'schemaGenerated' => __( 'Schema markup generated successfully.', '365i-ai-faq-generator' ),
			),
			'settings' => array(
				'autoSaveInterval' => $this->get_auto_save_interval(),
				'defaultFaqCount' => $this->get_default_faq_count(),
				'debugMode' => $this->is_debug_mode(),
			),
		);

		wp_localize_script( 'ai-faq-gen-frontend', 'aiFaqGen', $localize_data );
	}

	/**
	 * Add schema markup to head if FAQ is present.
	 * 
	 * @since 2.0.0
	 */
	public function add_schema_markup() {
		global $post;
		
		// Only add if shortcode is present and schema is enabled.
		if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, $this->shortcode_tag ) ) {
			return;
		}

		// Check if schema is disabled in shortcode.
		$pattern = get_shortcode_regex( array( $this->shortcode_tag ) );
		if ( preg_match( "/$pattern/", $post->post_content, $matches ) ) {
			$atts = shortcode_parse_atts( $matches[3] );
			if ( isset( $atts['show_schema'] ) && 'false' === $atts['show_schema'] ) {
				return;
			}
		}

		// Add FAQ schema placeholder (will be populated by JavaScript).
		echo "<!-- AI FAQ Generator Schema Placeholder -->\n";
		echo '<script type="application/ld+json" id="ai-faq-gen-schema"></script>' . "\n";
	}

	/**
	 * Get auto-save interval setting.
	 * 
	 * @since 2.0.0
	 * @return int Auto-save interval in minutes.
	 */
	private function get_auto_save_interval() {
		$options = get_option( 'ai_faq_gen_options', array() );
		return isset( $options['settings']['auto_save_interval'] ) ? intval( $options['settings']['auto_save_interval'] ) : 3;
	}

	/**
	 * Get default FAQ count setting.
	 * 
	 * @since 2.0.0
	 * @return int Default FAQ count.
	 */
	private function get_default_faq_count() {
		$options = get_option( 'ai_faq_gen_options', array() );
		return isset( $options['settings']['default_faq_count'] ) ? intval( $options['settings']['default_faq_count'] ) : 12;
	}

	/**
	 * Check if debug mode is enabled.
	 * 
	 * @since 2.0.0
	 * @return bool True if debug mode is enabled.
	 */
	private function is_debug_mode() {
		$options = get_option( 'ai_faq_gen_options', array() );
		return isset( $options['settings']['debug_mode'] ) ? (bool) $options['settings']['debug_mode'] : false;
	}

	/**
	 * Get FAQ data for export.
	 * 
	 * @since 2.0.0
	 * @param array $faq_data FAQ data to format.
	 * @param string $format Export format (json, csv, xml).
	 * @return string Formatted FAQ data.
	 */
	public function format_faq_export( $faq_data, $format = 'json' ) {
		switch ( $format ) {
			case 'csv':
				return $this->format_faq_csv( $faq_data );
			case 'xml':
				return $this->format_faq_xml( $faq_data );
			case 'json':
			default:
				return wp_json_encode( $faq_data, JSON_PRETTY_PRINT );
		}
	}

	/**
	 * Format FAQ data as CSV.
	 * 
	 * @since 2.0.0
	 * @param array $faq_data FAQ data.
	 * @return string CSV formatted data.
	 */
	private function format_faq_csv( $faq_data ) {
		$csv = "Question,Answer\n";
		
		if ( isset( $faq_data['faqs'] ) && is_array( $faq_data['faqs'] ) ) {
			foreach ( $faq_data['faqs'] as $faq ) {
				$question = isset( $faq['question'] ) ? str_replace( '"', '""', $faq['question'] ) : '';
				$answer = isset( $faq['answer'] ) ? str_replace( '"', '""', $faq['answer'] ) : '';
				$csv .= '"' . $question . '","' . $answer . '"' . "\n";
			}
		}
		
		return $csv;
	}

	/**
	 * Format FAQ data as XML.
	 * 
	 * @since 2.0.0
	 * @param array $faq_data FAQ data.
	 * @return string XML formatted data.
	 */
	private function format_faq_xml( $faq_data ) {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<faq>' . "\n";
		
		if ( isset( $faq_data['faqs'] ) && is_array( $faq_data['faqs'] ) ) {
			foreach ( $faq_data['faqs'] as $faq ) {
				$xml .= '  <item>' . "\n";
				$xml .= '    <question><![CDATA[' . ( isset( $faq['question'] ) ? $faq['question'] : '' ) . ']]></question>' . "\n";
				$xml .= '    <answer><![CDATA[' . ( isset( $faq['answer'] ) ? $faq['answer'] : '' ) . ']]></answer>' . "\n";
				$xml .= '  </item>' . "\n";
			}
		}
		
		$xml .= '</faq>';
		
		return $xml;
	}

	/**
	 * Generate JSON-LD schema markup for FAQ.
	 * 
	 * @since 2.0.0
	 * @param array $faq_data FAQ data.
	 * @return string JSON-LD schema markup.
	 */
	public function generate_faq_schema( $faq_data ) {
		if ( ! isset( $faq_data['faqs'] ) || ! is_array( $faq_data['faqs'] ) ) {
			return '';
		}

		$schema = array(
			'@context' => 'https://schema.org',
			'@type' => 'FAQPage',
			'mainEntity' => array(),
		);

		foreach ( $faq_data['faqs'] as $faq ) {
			if ( ! isset( $faq['question'] ) || ! isset( $faq['answer'] ) ) {
				continue;
			}

			$schema['mainEntity'][] = array(
				'@type' => 'Question',
				'name' => $faq['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text' => $faq['answer'],
				),
			);
		}

		return wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Handle FAQ export AJAX request.
	 * 
	 * @since 2.0.0
	 */
	public function ajax_export_faq() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_die( __( 'Security check failed', '365i-ai-faq-generator' ) );
		}

		$faq_data = isset( $_POST['faq_data'] ) ? $_POST['faq_data'] : array();
		$format = isset( $_POST['format'] ) ? sanitize_text_field( $_POST['format'] ) : 'json';

		if ( empty( $faq_data ) ) {
			wp_send_json_error( __( 'FAQ data is required', '365i-ai-faq-generator' ) );
		}

		$formatted_data = $this->format_faq_export( $faq_data, $format );

		wp_send_json_success( array(
			'data' => $formatted_data,
			'format' => $format,
		) );
	}

	/**
	 * Handle schema generation AJAX request.
	 * 
	 * @since 2.0.0
	 */
	public function ajax_generate_schema() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_die( __( 'Security check failed', '365i-ai-faq-generator' ) );
		}

		$faq_data = isset( $_POST['faq_data'] ) ? $_POST['faq_data'] : array();

		if ( empty( $faq_data ) ) {
			wp_send_json_error( __( 'FAQ data is required', '365i-ai-faq-generator' ) );
		}

		$schema = $this->generate_faq_schema( $faq_data );

		wp_send_json_success( array(
			'schema' => $schema,
		) );
	}
}