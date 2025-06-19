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
 * and public-facing functionality with dynamic settings integration.
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
	 * Settings handler instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Settings_Handler
	 */
	private $settings_handler;

	/**
	 * Constructor.
	 *
	 * Initialize the frontend component.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->settings_handler = new AI_FAQ_Settings_Handler();
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
		
		// Register AJAX handlers for frontend FAQ generation.
		add_action( 'wp_ajax_ai_faq_generate', array( $this, 'ajax_generate_faq' ) );
		add_action( 'wp_ajax_nopriv_ai_faq_generate', array( $this, 'ajax_generate_faq' ) );
		
		// Register AJAX handlers for export and schema functionality.
		add_action( 'wp_ajax_ai_faq_export', array( $this, 'ajax_export_faq' ) );
		add_action( 'wp_ajax_nopriv_ai_faq_export', array( $this, 'ajax_export_faq' ) );
		add_action( 'wp_ajax_ai_faq_generate_schema', array( $this, 'ajax_generate_schema' ) );
		add_action( 'wp_ajax_nopriv_ai_faq_generate_schema', array( $this, 'ajax_generate_schema' ) );
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
	 * Enqueue frontend assets with dynamic settings integration.
	 *
	 * @since 2.0.0
	 */
	public function enqueue_frontend_assets() {
		// Prevent double enqueuing.
		if ( wp_script_is( 'ai-faq-gen-frontend', 'enqueued' ) ) {
			return;
		}

		// Get comprehensive settings
		$comprehensive_settings = $this->settings_handler->get_comprehensive_settings();

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

		// Enqueue settings synchronization JavaScript.
		wp_enqueue_script(
			'ai-faq-gen-settings-sync',
			AI_FAQ_GEN_URL . 'assets/js/settings-sync.js',
			array( 'jquery', 'ai-faq-gen-frontend' ),
			AI_FAQ_GEN_VERSION,
			true
		);

		// Prepare comprehensive localization data from settings handler
		$localize_data = array_merge(
			$comprehensive_settings['js_config'],
			array(
				'strings' => $comprehensive_settings['localization']['strings'],
				'settings' => array(
					'autoSaveInterval' => $comprehensive_settings['general']['auto_save_interval'],
					'defaultFaqCount' => $comprehensive_settings['general']['default_faq_count'],
					'debugMode' => $comprehensive_settings['general']['debug_mode'],
					'maxQuestions' => $comprehensive_settings['general']['max_questions_per_batch'],
					'cacheDuration' => $comprehensive_settings['general']['cache_duration'],
					'enableAnimations' => $comprehensive_settings['ui']['enable_animations'],
					'compactMode' => $comprehensive_settings['ui']['compact_mode'],
					'theme' => $comprehensive_settings['ui']['theme'],
					'colorScheme' => $comprehensive_settings['ui']['color_scheme'],
				),
				'generation' => array(
					'defaultTone' => $comprehensive_settings['generation']['default_tone'],
					'defaultLength' => $comprehensive_settings['generation']['default_length'],
					'defaultSchema' => $comprehensive_settings['generation']['default_schema_type'],
					'toneOptions' => $comprehensive_settings['generation']['tone_options'],
					'lengthOptions' => $comprehensive_settings['generation']['length_options'],
					'schemaOptions' => $comprehensive_settings['generation']['schema_options'],
					'enableAutoSchema' => $comprehensive_settings['generation']['enable_auto_schema'],
					'enableSeoOptimization' => $comprehensive_settings['generation']['enable_seo_optimization'],
				),
				'workers' => array(
					'hasEnabledWorkers' => $comprehensive_settings['workers']['has_enabled_workers'],
					'enabledCount' => $comprehensive_settings['workers']['enabled_count'],
					'apiConfigured' => $comprehensive_settings['workers']['api_configured'],
				),
				'performance' => array(
					'enableCaching' => $comprehensive_settings['performance']['enable_caching'],
					'enableRateLimiting' => $comprehensive_settings['performance']['enable_rate_limiting'],
					'debounceDelay' => $comprehensive_settings['performance']['debounce_delay'],
					'lazyLoadFaqs' => $comprehensive_settings['performance']['lazy_load_faqs'],
				),
				'localization' => array(
					'locale' => $comprehensive_settings['localization']['locale'],
					'language' => $comprehensive_settings['localization']['language'],
					'textDirection' => $comprehensive_settings['localization']['text_direction'],
					'dateFormat' => $comprehensive_settings['localization']['date_format'],
					'timeFormat' => $comprehensive_settings['localization']['time_format'],
					'timezone' => $comprehensive_settings['localization']['timezone'],
				),
				'computed' => $comprehensive_settings['computed'],
				'version' => $comprehensive_settings['version'],
				'timestamp' => $comprehensive_settings['timestamp'],
			)
		);

		// Apply filters for extensibility
		$localize_data = apply_filters( 'ai_faq_gen_frontend_localize_data', $localize_data, $comprehensive_settings );

		wp_localize_script( 'ai-faq-gen-frontend', 'ai_faq_frontend', $localize_data );
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
	 * Handle frontend FAQ generation AJAX request.
	 *
	 * This method bridges frontend form submissions to the backend worker system.
	 * It supports multiple generation methods: topic-based, URL-based, and enhancement.
	 *
	 * @since 2.0.0
	 */
	public function ajax_generate_faq() {
		// Verify nonce for security.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'ai_faq_generate_nonce' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Security verification failed. Please refresh the page and try again.', '365i-ai-faq-generator' ),
				'code' => 'security_failed'
			) );
		}

		// Sanitize and validate input data.
		$generation_method = sanitize_text_field( $_POST['generation_method'] ?? '' );
		$num_questions = absint( $_POST['num_questions'] ?? 10 );
		$tone = sanitize_text_field( $_POST['tone'] ?? 'professional' );
		$length = sanitize_text_field( $_POST['length'] ?? 'medium' );
		$enable_seo = filter_var( $_POST['enable_seo'] ?? true, FILTER_VALIDATE_BOOLEAN );
		$schema_output = sanitize_text_field( $_POST['schema_output'] ?? 'json-ld' );

		// Validate required fields based on generation method.
		$errors = array();
		
		if ( empty( $generation_method ) ) {
			$errors[] = __( 'Please select a generation method.', '365i-ai-faq-generator' );
		}

		if ( $num_questions < 1 || $num_questions > 50 ) {
			$errors[] = __( 'Number of questions must be between 1 and 50.', '365i-ai-faq-generator' );
		}

		// Method-specific validation and data preparation.
		$topic = '';
		$url = '';
		$existing_faq = '';
		
		switch ( $generation_method ) {
			case 'topic':
				$topic = sanitize_textarea_field( $_POST['topic'] ?? '' );
				if ( empty( $topic ) ) {
					$errors[] = __( 'Please provide a topic or keywords.', '365i-ai-faq-generator' );
				}
				break;
				
			case 'url':
				$url = esc_url_raw( $_POST['url'] ?? '' );
				if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
					$errors[] = __( 'Please provide a valid website URL.', '365i-ai-faq-generator' );
				}
				break;
				
			case 'enhance':
				$existing_faq = sanitize_textarea_field( $_POST['existing_faq'] ?? '' );
				if ( empty( $existing_faq ) ) {
					$errors[] = __( 'Please provide existing FAQ content to enhance.', '365i-ai-faq-generator' );
				}
				break;
				
			default:
				$errors[] = __( 'Invalid generation method selected.', '365i-ai-faq-generator' );
				break;
		}

		// Return errors if validation failed.
		if ( ! empty( $errors ) ) {
			wp_send_json_error( array(
				'message' => implode( ' ', $errors ),
				'code' => 'validation_failed'
			) );
		}

		// Get the workers facade to access the backend system.
		$workers = new AI_FAQ_Workers();
		
		if ( ! $workers ) {
			wp_send_json_error( array(
				'message' => __( 'FAQ generation system is not available. Please try again later.', '365i-ai-faq-generator' ),
				'code' => 'system_unavailable'
			) );
		}

		try {
			$generated_faqs = array();
			$metadata = array(
				'generation_method' => $generation_method,
				'tone' => $tone,
				'length' => $length,
				'enable_seo' => $enable_seo,
				'timestamp' => current_time( 'mysql' ),
				'user_ip' => $this->get_client_ip()
			);

			// Generate FAQs based on the selected method.
			switch ( $generation_method ) {
				case 'topic':
					$generated_faqs = $workers->generate_questions( $topic, $num_questions );
					$metadata['topic'] = $topic;
					break;
					
				case 'url':
					$generated_faqs = $workers->extract_faq( $url );
					$metadata['url'] = $url;
					break;
					
				case 'enhance':
					// For enhancement, we'll use the FAQ enhancer worker.
					// This is a simplified approach - you might want to parse the existing FAQ first.
					$enhanced_result = $workers->enhance_faq(
						__( 'Existing FAQ Content', '365i-ai-faq-generator' ),
						$existing_faq
					);
					$generated_faqs = isset( $enhanced_result['enhanced_faqs'] ) ? $enhanced_result['enhanced_faqs'] : array();
					$metadata['existing_faq_length'] = strlen( $existing_faq );
					break;
			}

			// Validate the response.
			if ( empty( $generated_faqs ) || ! is_array( $generated_faqs ) ) {
				wp_send_json_error( array(
					'message' => __( 'No FAQs were generated. Please try different parameters or check your worker configuration.', '365i-ai-faq-generator' ),
					'code' => 'no_results'
				) );
			}

			// Process and format the generated FAQs.
			$formatted_faqs = array();
			foreach ( $generated_faqs as $faq ) {
				if ( isset( $faq['question'] ) && isset( $faq['answer'] ) ) {
					$formatted_faqs[] = array(
						'question' => sanitize_text_field( $faq['question'] ),
						'answer' => wp_kses_post( $faq['answer'] ),
						'id' => 'faq-' . wp_rand( 1000, 9999 )
					);
				}
			}

			// Generate schema markup if requested.
			$schema_markup = '';
			if ( 'json-ld' === $schema_output && ! empty( $formatted_faqs ) ) {
				$schema_markup = $this->generate_faq_schema( array( 'faqs' => $formatted_faqs ) );
			}

			// Log the successful generation for analytics.
			$workers->record_usage( 'frontend_generation', 'success', $metadata );

			// Return success response.
			wp_send_json_success( array(
				'message' => sprintf(
					/* translators: %d: number of FAQs generated */
					__( 'Successfully generated %d FAQs!', '365i-ai-faq-generator' ),
					count( $formatted_faqs )
				),
				'faqs' => $formatted_faqs,
				'schema' => $schema_markup,
				'metadata' => $metadata,
				'count' => count( $formatted_faqs )
			) );

		} catch ( Exception $e ) {
			// Log the error for debugging.
			error_log( 'AI FAQ Generator frontend error: ' . $e->getMessage() );
			
			// Record the failure for analytics.
			$workers->record_usage( 'frontend_generation', 'error', array_merge( $metadata, array(
				'error' => $e->getMessage()
			) ) );

			wp_send_json_error( array(
				'message' => __( 'An error occurred while generating FAQs. Please try again or contact support if the problem persists.', '365i-ai-faq-generator' ),
				'code' => 'generation_failed',
				'debug' => WP_DEBUG ? $e->getMessage() : ''
			) );
		}
	}

	/**
	 * Get client IP address for analytics and rate limiting.
	 *
	 * @since 2.0.0
	 * @return string Client IP address.
	 */
	private function get_client_ip() {
		// Check for various headers that might contain the real IP.
		$ip_headers = array(
			'HTTP_CF_CONNECTING_IP',     // Cloudflare.
			'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy.
			'HTTP_X_REAL_IP',            // Nginx proxy.
			'HTTP_X_FORWARDED',          // Proxy.
			'HTTP_FORWARDED_FOR',        // Proxy.
			'HTTP_FORWARDED',            // Proxy.
			'REMOTE_ADDR',               // Standard.
		);

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip_list = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
				$ip = trim( $ip_list[0] );

				// Validate IP address.
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		// Fallback to REMOTE_ADDR even if it's a private IP.
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
	}

	/**
	 * Handle FAQ export AJAX request.
	 *
	 * @since 2.0.0
	 */
	public function ajax_export_faq() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_generate_nonce' ) ) {
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
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_generate_nonce' ) ) {
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