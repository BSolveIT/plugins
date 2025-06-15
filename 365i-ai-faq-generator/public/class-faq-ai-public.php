<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://365i.com
 * @since      1.0.0
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * enqueuing the public-facing stylesheet and JavaScript.
 * Handles the shortcode rendering and AJAX callbacks.
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/public
 * @author     365i
 */
class FAQ_AI_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The worker communicator instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Worker_Communicator    $worker_communicator    Handles communication with AI workers.
	 */
	private $worker_communicator;

	/**
	 * The schema generator instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Schema_Generator    $schema_generator    Handles schema generation.
	 */
	private $schema_generator;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name          The name of this plugin.
	 * @param    string    $version              The version of this plugin.
	 * @param    Worker_Communicator    $worker_communicator    The worker communicator instance.
	 * @param    Schema_Generator       $schema_generator       The schema generator instance.
	 */
	public function __construct( $plugin_name, $version, $worker_communicator, $schema_generator ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->worker_communicator = $worker_communicator;
		$this->schema_generator = $schema_generator;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		// Only load if shortcode is present
		global $post;
		if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'ai_faq_generator')) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/faq-generator.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name . '-quill', plugin_dir_url( __FILE__ ) . 'css/quill.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name . '-led', plugin_dir_url( __FILE__ ) . 'css/led-display.css', array(), $this->version, 'all' );
			
			// Load responsive styles
			wp_enqueue_style( $this->plugin_name . '-responsive', plugin_dir_url( __FILE__ ) . 'css/responsive.css', array($this->plugin_name), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		// Only load if shortcode is present
		global $post;
		if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'ai_faq_generator')) {
			// Third-party libraries
			wp_enqueue_script( 'quill-js', plugin_dir_url( __FILE__ ) . '../vendor/quill/quill.min.js', array( 'jquery' ), '1.3.7', false );
			wp_enqueue_script( 'sortable-js', plugin_dir_url( __FILE__ ) . '../vendor/sortable/Sortable.min.js', array(), '1.15.0', false );

			// Services
			wp_enqueue_script( $this->plugin_name . '-storage', plugin_dir_url( __FILE__ ) . 'js/services/storage-service.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . '-utils', plugin_dir_url( __FILE__ ) . 'js/services/util-service.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . '-ui', plugin_dir_url( __FILE__ ) . 'js/services/ui-controller.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . '-cloudflare', plugin_dir_url( __FILE__ ) . 'js/services/cloudflare-service.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . '-export', plugin_dir_url( __FILE__ ) . 'js/services/export-service.js', array( 'jquery' ), $this->version, false );
			
			// Main application JS
			wp_enqueue_script( $this->plugin_name . '-app', plugin_dir_url( __FILE__ ) . 'js/faq-app.js', array( 'jquery', 'quill-js', 'sortable-js', $this->plugin_name . '-storage', $this->plugin_name . '-utils', $this->plugin_name . '-ui', $this->plugin_name . '-cloudflare', $this->plugin_name . '-export' ), $this->version, false );
			
			// Initialization (must be loaded last)
			wp_enqueue_script( $this->plugin_name . '-init', plugin_dir_url( __FILE__ ) . 'js/faq-init.js', array( $this->plugin_name . '-app' ), $this->version, false );

			// Get settings for localization
			$settings = get_option('faq_ai_generator_settings', array());
			$current_page_url = get_permalink();

			// Localize script with necessary data
			wp_localize_script( $this->plugin_name . '-init', 'faqAiConfig', array(
				'debug' => !empty($settings['debug_mode']) && $settings['debug_mode'],
				'services' => array(
					'cloudflare' => array(
						'workerUrls' => array(
							'question' => !empty($settings['worker_urls']['question']) ? $settings['worker_urls']['question'] : '',
							'answer' => !empty($settings['worker_urls']['answer']) ? $settings['worker_urls']['answer'] : '',
							'enhance' => !empty($settings['worker_urls']['enhance']) ? $settings['worker_urls']['enhance'] : '',
							'seo' => !empty($settings['worker_urls']['seo']) ? $settings['worker_urls']['seo'] : '',
							'extract' => !empty($settings['worker_urls']['extract']) ? $settings['worker_urls']['extract'] : '',
							'topic' => !empty($settings['worker_urls']['topic']) ? $settings['worker_urls']['topic'] : '',
							'validate' => !empty($settings['worker_urls']['validate']) ? $settings['worker_urls']['validate'] : ''
						),
						'apiKey' => !empty($settings['api_key']) ? $settings['api_key'] : ''
					),
					'export' => array(
						'baseUrl' => !empty($settings['faq_page_url']) ? $settings['faq_page_url'] : $current_page_url,
						'defaultFormat' => !empty($settings['default_schema_format']) ? $settings['default_schema_format'] : 'json-ld'
					)
				)
			));
			
			wp_localize_script( $this->plugin_name . '-app', 'faqAiData', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'faq_ai_nonce' ),
				'settings' => array(
					'faq_page_url' => !empty($settings['faq_page_url']) ? $settings['faq_page_url'] : $current_page_url,
					'default_anchor_format' => !empty($settings['default_anchor_format']) ? $settings['default_anchor_format'] : 'question',
					'auto_save_interval' => !empty($settings['auto_save_interval']) ? intval($settings['auto_save_interval']) : 3,
					'debug_mode' => !empty($settings['debug_mode']) && $settings['debug_mode'],
				),
				'strings' => array(
					'saving' => __( 'Saving...', 'faq-ai-generator' ),
					'saved' => __( 'Saved', 'faq-ai-generator' ),
					'error' => __( 'Error', 'faq-ai-generator' ),
					'generating' => __( 'Generating...', 'faq-ai-generator' ),
					'copySuccess' => __( 'Copied to clipboard!', 'faq-ai-generator' ),
					'copyError' => __( 'Failed to copy. Please try again.', 'faq-ai-generator' ),
					'confirmDelete' => __( 'Are you sure you want to delete this FAQ?', 'faq-ai-generator' ),
					'confirmDeleteAll' => __( 'Are you sure you want to delete all FAQs? This cannot be undone.', 'faq-ai-generator' ),
					'newQuestion' => __( 'New question', 'faq-ai-generator' ),
					'newAnswer' => __( 'Enter answer here...', 'faq-ai-generator' ),
				)
			));
		}
	}

	/**
	 * Register shortcodes.
	 *
	 * @since    1.0.0
	 */
	public function register_shortcodes() {
		add_shortcode( 'ai_faq_generator', array( $this, 'render_faq_generator_shortcode' ) );
	}

	/**
	 * Render the FAQ generator shortcode.
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes.
	 * @return   string            Shortcode output.
	 */
	public function render_faq_generator_shortcode( $atts ) {
		// Parse attributes
		$atts = shortcode_atts(
			array(
				'theme' => 'default',
				'initial_mode' => 'topic',
				'faq_count' => 10,
				'show_seo' => 'true',
				'primary_color' => '#667eea',
				'secondary_color' => '#764ba2',
				'text_color' => '#2c3e50',
				'background_color' => '#ffffff',
			),
			$atts,
			'ai_faq_generator'
		);

		// Apply custom styles if provided
		$custom_styles = '';
		if ($atts['primary_color'] !== '#667eea' || $atts['secondary_color'] !== '#764ba2' || 
			$atts['text_color'] !== '#2c3e50' || $atts['background_color'] !== '#ffffff') {
			
			$custom_styles = '<style>
				.faq-ai-generator {
					--primary-color: ' . esc_attr($atts['primary_color']) . ';
					--secondary-color: ' . esc_attr($atts['secondary_color']) . ';
					--text-color: ' . esc_attr($atts['text_color']) . ';
					--background-color: ' . esc_attr($atts['background_color']) . ';
				}
			</style>';
		}

		// Convert boolean string to actual boolean
		$show_seo = filter_var($atts['show_seo'], FILTER_VALIDATE_BOOLEAN);

		// Start output buffer
		ob_start();
		
		// Include custom styles
		echo $custom_styles;
		
		// Include the shortcode template
		include plugin_dir_path( __FILE__ ) . 'partials/shortcode-display.php';
		
		// Return the buffered content
		return ob_get_clean();
	}

	/**
	 * AJAX handler for generating questions.
	 *
	 * @since    1.0.0
	 */
	public function ajax_generate_question() {
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'faq_ai_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'faq-ai-generator')));
		}

		// Get input data
		$questions = isset($_POST['questions']) ? (array) $_POST['questions'] : array();
		$current_answer = isset($_POST['currentAnswer']) ? sanitize_textarea_field($_POST['currentAnswer']) : '';
		$mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'improve';
		$website_context = isset($_POST['websiteContext']) ? sanitize_textarea_field($_POST['websiteContext']) : '';
		$page_url = isset($_POST['pageUrl']) ? esc_url_raw($_POST['pageUrl']) : '';

		// Sanitize questions
		$sanitized_questions = array();
		foreach ($questions as $question) {
			$sanitized_questions[] = sanitize_text_field($question);
		}

		// Prepare data for worker
		$data = array(
			'questions' => $sanitized_questions,
			'currentAnswer' => $current_answer,
			'mode' => $mode,
			'websiteContext' => $website_context,
			'pageUrl' => $page_url,
		);

		// Call the worker
		$response = $this->worker_communicator->send_request('faq-realtime-assistant-worker', $data);

		if (is_wp_error($response)) {
			wp_send_json_error(array(
				'message' => $response->get_error_message(),
				'debug' => $this->get_debug_info()
			));
		}

		// Return the response
		wp_send_json_success($response);
	}

	/**
	 * AJAX handler for generating answers.
	 *
	 * @since    1.0.0
	 */
	public function ajax_generate_answer() {
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'faq_ai_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'faq-ai-generator')));
		}

		// Get input data
		$question = isset($_POST['question']) ? sanitize_text_field($_POST['question']) : '';
		$answers = isset($_POST['answers']) ? (array) $_POST['answers'] : array();
		$mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'generate';
		$tone = isset($_POST['tone']) ? sanitize_text_field($_POST['tone']) : 'professional';
		$website_context = isset($_POST['websiteContext']) ? sanitize_textarea_field($_POST['websiteContext']) : '';
		$page_url = isset($_POST['pageUrl']) ? esc_url_raw($_POST['pageUrl']) : '';

		if (empty($question)) {
			wp_send_json_error(array('message' => __('Question is required.', 'faq-ai-generator')));
		}

		// Sanitize answers
		$sanitized_answers = array();
		foreach ($answers as $answer) {
			$sanitized_answers[] = wp_kses_post($answer);
		}

		// Prepare data for worker
		$data = array(
			'question' => $question,
			'answers' => $sanitized_answers,
			'mode' => $mode,
			'tone' => $tone,
			'websiteContext' => $website_context,
			'pageUrl' => $page_url,
		);

		// Call the worker
		$response = $this->worker_communicator->send_request('faq-answer-generator-worker', $data);

		if (is_wp_error($response)) {
			wp_send_json_error(array(
				'message' => $response->get_error_message(),
				'debug' => $this->get_debug_info()
			));
		}

		// Return the response
		wp_send_json_success($response);
	}

	/**
	 * AJAX handler for analyzing SEO.
	 *
	 * @since    1.0.0
	 */
	public function ajax_analyze_seo() {
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'faq_ai_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'faq-ai-generator')));
		}

		// Get input data
		$question = isset($_POST['question']) ? sanitize_text_field($_POST['question']) : '';
		$answer = isset($_POST['answer']) ? wp_kses_post($_POST['answer']) : '';
		$website_context = isset($_POST['websiteContext']) ? sanitize_textarea_field($_POST['websiteContext']) : '';

		if (empty($question) || empty($answer)) {
			wp_send_json_error(array('message' => __('Question and answer are required.', 'faq-ai-generator')));
		}

		// Prepare data for worker
		$data = array(
			'question' => $question,
			'answer' => $answer,
			'websiteContext' => $website_context,
		);

		// Call the worker
		$response = $this->worker_communicator->send_request('faq-seo-analyzer-worker', $data);

		if (is_wp_error($response)) {
			wp_send_json_error(array(
				'message' => $response->get_error_message(),
				'debug' => $this->get_debug_info()
			));
		}

		// Return the response
		wp_send_json_success($response);
	}

	/**
	 * AJAX handler for fetching URL content.
	 *
	 * @since    1.0.0
	 */
	public function ajax_fetch_url() {
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'faq_ai_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'faq-ai-generator')));
		}

		// Get input data
		$url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';

		if (empty($url)) {
			wp_send_json_error(array('message' => __('URL is required.', 'faq-ai-generator')));
		}

		// Prepare data for worker
		$data = array(
			'url' => $url,
			'extractFaqs' => true,
		);

		// Call the worker
		$response = $this->worker_communicator->send_request('faq-proxy-fetch', $data);

		if (is_wp_error($response)) {
			wp_send_json_error(array(
				'message' => $response->get_error_message(),
				'debug' => $this->get_debug_info()
			));
		}

		// Return the response
		wp_send_json_success($response);
	}

	/**
	 * AJAX handler for enhancing FAQs.
	 *
	 * @since    1.0.0
	 */
	public function ajax_enhance_faq() {
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'faq_ai_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'faq-ai-generator')));
		}

		// Get input data
		$question = isset($_POST['question']) ? sanitize_text_field($_POST['question']) : '';
		$answer = isset($_POST['answer']) ? wp_kses_post($_POST['answer']) : '';
		$mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'enhance';
		$website_context = isset($_POST['websiteContext']) ? sanitize_textarea_field($_POST['websiteContext']) : '';

		if (empty($question) || empty($answer)) {
			wp_send_json_error(array('message' => __('Question and answer are required.', 'faq-ai-generator')));
		}

		// Prepare data for worker
		$data = array(
			'question' => $question,
			'answer' => $answer,
			'mode' => $mode,
			'websiteContext' => $website_context,
		);

		// Call the worker
		$response = $this->worker_communicator->send_request('faq-enhancement-worker', $data);

		if (is_wp_error($response)) {
			wp_send_json_error(array(
				'message' => $response->get_error_message(),
				'debug' => $this->get_debug_info()
			));
		}

		// Return the response
		wp_send_json_success($response);
	}

	/**
	 * AJAX handler for generating schema.
	 *
	 * @since    1.0.0
	 */
	public function ajax_generate_schema() {
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'faq_ai_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'faq-ai-generator')));
		}

		// Get input data
		$faqs = isset($_POST['faqs']) ? (array) $_POST['faqs'] : array();
		$format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'json-ld';
		$base_url = isset($_POST['baseUrl']) ? esc_url_raw($_POST['baseUrl']) : '';

		if (empty($faqs)) {
			wp_send_json_error(array('message' => __('No FAQs provided.', 'faq-ai-generator')));
		}

		// Set base URL for schema generator
		if (!empty($base_url)) {
			$this->schema_generator->set_base_url($base_url);
		}

		// Process FAQs
		$processed_faqs = array();
		foreach ($faqs as $faq) {
			if (isset($faq['question']) && isset($faq['answer'])) {
				$processed_faqs[] = array(
					'question' => sanitize_text_field($faq['question']),
					'answer' => wp_kses_post($faq['answer']),
					'id' => isset($faq['id']) ? sanitize_text_field($faq['id']) : '',
				);
			}
		}

		// Generate schema based on format
		$schema = '';
		switch ($format) {
			case 'json-ld':
				$schema = $this->schema_generator->generate_json_ld($processed_faqs);
				break;
			case 'microdata':
				$schema = $this->schema_generator->generate_microdata($processed_faqs);
				break;
			case 'rdfa':
				$schema = $this->schema_generator->generate_rdfa($processed_faqs);
				break;
			case 'html':
				$schema = $this->schema_generator->generate_html($processed_faqs);
				break;
			default:
				wp_send_json_error(array('message' => __('Invalid schema format.', 'faq-ai-generator')));
				break;
		}

		// Return the response
		wp_send_json_success(array(
			'schema' => $schema,
			'format' => $format,
			'count' => count($processed_faqs)
		));
	}

	/**
	 * Get debug information for error responses.
	 *
	 * @since    1.0.0
	 * @return   array    Debug information.
	 */
	private function get_debug_info() {
		$settings = get_option('faq_ai_generator_settings', array());
		
		// Only include debug info if debug mode is enabled
		if (empty($settings['debug_mode']) || !$settings['debug_mode']) {
			return array();
		}
		
		return array(
			'php_version' => PHP_VERSION,
			'wordpress_version' => get_bloginfo('version'),
			'plugin_version' => $this->version,
			'is_ssl' => is_ssl(),
			'server' => $_SERVER['SERVER_SOFTWARE'],
			'memory_limit' => ini_get('memory_limit'),
			'max_execution_time' => ini_get('max_execution_time'),
			'time' => current_time('mysql'),
		);
	}
}