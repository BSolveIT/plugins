<?php
/**
 * Workers API integration class for 365i AI FAQ Generator.
 * 
 * This class handles communication with all 6 Cloudflare workers,
 * including rate limiting, error handling, and response caching.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Workers
 * @since 2.0.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Workers API integration class.
 * 
 * Manages communication with Cloudflare workers including rate limiting,
 * error handling, response caching, and unified API interface.
 * 
 * @since 2.0.0
 */
class AI_FAQ_Workers {

	/**
	 * Available workers configuration.
	 * 
	 * @since 2.0.0
	 * @var array
	 */
	private $workers = array();

	/**
	 * Rate limiting cache key prefix.
	 * 
	 * @since 2.0.0
	 * @var string
	 */
	private $rate_limit_prefix = 'ai_faq_rate_limit_';

	/**
	 * Response cache key prefix.
	 * 
	 * @since 2.0.0
	 * @var string
	 */
	private $cache_prefix = 'ai_faq_response_';

	/**
	 * Constructor.
	 * 
	 * Initialize the workers configuration.
	 * 
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->load_worker_config();
	}

	/**
	 * Initialize the workers component.
	 * 
	 * Set up hooks and filters for worker functionality.
	 * 
	 * @since 2.0.0
	 */
	public function init() {
		// Add AJAX handlers for worker requests.
		add_action( 'wp_ajax_ai_faq_generate_questions', array( $this, 'ajax_generate_questions' ) );
		add_action( 'wp_ajax_ai_faq_generate_answers', array( $this, 'ajax_generate_answers' ) );
		add_action( 'wp_ajax_ai_faq_enhance_faq', array( $this, 'ajax_enhance_faq' ) );
		add_action( 'wp_ajax_ai_faq_analyze_seo', array( $this, 'ajax_analyze_seo' ) );
		add_action( 'wp_ajax_ai_faq_extract_faq', array( $this, 'ajax_extract_faq' ) );
		add_action( 'wp_ajax_ai_faq_generate_topics', array( $this, 'ajax_generate_topics' ) );

		// Add public AJAX handlers for shortcode.
		add_action( 'wp_ajax_nopriv_ai_faq_generate_questions', array( $this, 'ajax_generate_questions' ) );
		add_action( 'wp_ajax_nopriv_ai_faq_generate_answers', array( $this, 'ajax_generate_answers' ) );
		add_action( 'wp_ajax_nopriv_ai_faq_enhance_faq', array( $this, 'ajax_enhance_faq' ) );
		add_action( 'wp_ajax_nopriv_ai_faq_analyze_seo', array( $this, 'ajax_analyze_seo' ) );
		add_action( 'wp_ajax_nopriv_ai_faq_extract_faq', array( $this, 'ajax_extract_faq' ) );
		add_action( 'wp_ajax_nopriv_ai_faq_generate_topics', array( $this, 'ajax_generate_topics' ) );
	}

	/**
	 * Load worker configuration from options.
	 * 
	 * @since 2.0.0
	 */
	private function load_worker_config() {
		$options = get_option( 'ai_faq_gen_options', array() );
		$this->workers = isset( $options['workers'] ) ? $options['workers'] : array();
	}

	/**
	 * Make API request to worker.
	 * 
	 * @since 2.0.0
	 * @param string $worker_name Worker name.
	 * @param array  $data Request data.
	 * @param string $method HTTP method.
	 * @return array|WP_Error Response data or error.
	 */
	private function make_request( $worker_name, $data = array(), $method = 'POST' ) {
		// Check if worker exists and is enabled.
		if ( ! isset( $this->workers[ $worker_name ] ) || ! $this->workers[ $worker_name ]['enabled'] ) {
			return new WP_Error(
				'worker_disabled',
				/* translators: %s: Worker name */
				sprintf( __( 'Worker %s is not enabled', '365i-ai-faq-generator' ), $worker_name )
			);
		}

		$worker_config = $this->workers[ $worker_name ];

		// Check rate limiting.
		if ( ! $this->check_rate_limit( $worker_name ) ) {
			return new WP_Error(
				'rate_limit_exceeded',
				/* translators: %s: Worker name */
				sprintf( __( 'Rate limit exceeded for worker %s', '365i-ai-faq-generator' ), $worker_name )
			);
		}

		// Check cache for GET requests.
		if ( 'GET' === $method ) {
			$cache_key = $this->cache_prefix . $worker_name . '_' . md5( serialize( $data ) );
			$cached_response = get_transient( $cache_key );
			
			if ( false !== $cached_response ) {
				return $cached_response;
			}
		}

		// Prepare request arguments.
		$args = array(
			'method'  => $method,
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/json',
				'User-Agent'   => 'WordPress/365i-AI-FAQ-Generator',
			),
		);

		// Add body for POST requests.
		if ( 'POST' === $method && ! empty( $data ) ) {
			$args['body'] = wp_json_encode( $data );
		}

		// Make the request.
		$response = wp_remote_request( $worker_config['url'], $args );

		// Update rate limit counter.
		$this->update_rate_limit( $worker_name );

		// Handle response errors.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Check for HTTP errors.
		if ( $response_code < 200 || $response_code >= 300 ) {
			return new WP_Error(
				'http_error',
				/* translators: 1: HTTP response code, 2: Worker name */
				sprintf( __( 'HTTP error %1$d from worker %2$s', '365i-ai-faq-generator' ), $response_code, $worker_name )
			);
		}

		// Decode JSON response.
		$decoded_response = json_decode( $response_body, true );

		if ( null === $decoded_response ) {
			return new WP_Error(
				'json_decode_error',
				/* translators: %s: Worker name */
				sprintf( __( 'Invalid JSON response from worker %s', '365i-ai-faq-generator' ), $worker_name )
			);
		}

		// Cache successful GET responses.
		if ( 'GET' === $method && isset( $cache_key ) ) {
			set_transient( $cache_key, $decoded_response, HOUR_IN_SECONDS );
		}

		return $decoded_response;
	}

	/**
	 * Check rate limit for worker.
	 * 
	 * @since 2.0.0
	 * @param string $worker_name Worker name.
	 * @return bool True if within rate limit, false otherwise.
	 */
	private function check_rate_limit( $worker_name ) {
		if ( ! isset( $this->workers[ $worker_name ]['rate_limit'] ) ) {
			return true;
		}

		$rate_limit = $this->workers[ $worker_name ]['rate_limit'];
		$cache_key = $this->rate_limit_prefix . $worker_name;
		$current_count = get_transient( $cache_key );

		return ( false === $current_count || $current_count < $rate_limit );
	}

	/**
	 * Update rate limit counter for worker.
	 * 
	 * @since 2.0.0
	 * @param string $worker_name Worker name.
	 */
	private function update_rate_limit( $worker_name ) {
		$cache_key = $this->rate_limit_prefix . $worker_name;
		$current_count = get_transient( $cache_key );

		if ( false === $current_count ) {
			set_transient( $cache_key, 1, HOUR_IN_SECONDS );
		} else {
			set_transient( $cache_key, $current_count + 1, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Generate questions using Question Generator worker.
	 * 
	 * @since 2.0.0
	 * @param string $topic Topic to generate questions for.
	 * @param int    $count Number of questions to generate.
	 * @return array|WP_Error Generated questions or error.
	 */
	public function generate_questions( $topic, $count = 12 ) {
		$data = array(
			'topic' => sanitize_text_field( $topic ),
			'count' => intval( $count ),
		);

		return $this->make_request( 'question_generator', $data );
	}

	/**
	 * Generate answers using Answer Generator worker.
	 * 
	 * @since 2.0.0
	 * @param array $questions Array of questions.
	 * @return array|WP_Error Generated answers or error.
	 */
	public function generate_answers( $questions ) {
		$data = array(
			'questions' => array_map( 'sanitize_text_field', $questions ),
		);

		return $this->make_request( 'answer_generator', $data );
	}

	/**
	 * Enhance FAQ using FAQ Enhancer worker.
	 * 
	 * @since 2.0.0
	 * @param array $faq_data FAQ data to enhance.
	 * @return array|WP_Error Enhanced FAQ or error.
	 */
	public function enhance_faq( $faq_data ) {
		$data = array(
			'faq' => $faq_data,
		);

		return $this->make_request( 'faq_enhancer', $data );
	}

	/**
	 * Analyze SEO using SEO Analyzer worker.
	 * 
	 * @since 2.0.0
	 * @param array $faq_data FAQ data to analyze.
	 * @return array|WP_Error SEO analysis or error.
	 */
	public function analyze_seo( $faq_data ) {
		$data = array(
			'faq' => $faq_data,
		);

		return $this->make_request( 'seo_analyzer', $data );
	}

	/**
	 * Extract FAQ from URL using FAQ Extractor worker.
	 * 
	 * @since 2.0.0
	 * @param string $url URL to extract FAQ from.
	 * @return array|WP_Error Extracted FAQ or error.
	 */
	public function extract_faq( $url ) {
		$data = array(
			'url' => esc_url_raw( $url ),
		);

		return $this->make_request( 'faq_extractor', $data );
	}

	/**
	 * Generate topics using Topic Generator worker.
	 * 
	 * @since 2.0.0
	 * @param string $input Input text for topic generation.
	 * @return array|WP_Error Generated topics or error.
	 */
	public function generate_topics( $input ) {
		$data = array(
			'input' => sanitize_textarea_field( $input ),
		);

		return $this->make_request( 'topic_generator', $data );
	}

	/**
	 * AJAX handler for generating questions.
	 * 
	 * @since 2.0.0
	 */
	public function ajax_generate_questions() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_die( __( 'Security check failed', '365i-ai-faq-generator' ) );
		}

		$topic = isset( $_POST['topic'] ) ? sanitize_text_field( $_POST['topic'] ) : '';
		$count = isset( $_POST['count'] ) ? intval( $_POST['count'] ) : 12;

		if ( empty( $topic ) ) {
			wp_send_json_error( __( 'Topic is required', '365i-ai-faq-generator' ) );
		}

		$result = $this->generate_questions( $topic, $count );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for generating answers.
	 * 
	 * @since 2.0.0
	 */
	public function ajax_generate_answers() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_die( __( 'Security check failed', '365i-ai-faq-generator' ) );
		}

		$questions = isset( $_POST['questions'] ) ? $_POST['questions'] : array();

		if ( empty( $questions ) || ! is_array( $questions ) ) {
			wp_send_json_error( __( 'Questions are required', '365i-ai-faq-generator' ) );
		}

		$result = $this->generate_answers( $questions );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for enhancing FAQ.
	 * 
	 * @since 2.0.0
	 */
	public function ajax_enhance_faq() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_die( __( 'Security check failed', '365i-ai-faq-generator' ) );
		}

		$faq_data = isset( $_POST['faq_data'] ) ? $_POST['faq_data'] : array();

		if ( empty( $faq_data ) ) {
			wp_send_json_error( __( 'FAQ data is required', '365i-ai-faq-generator' ) );
		}

		$result = $this->enhance_faq( $faq_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for SEO analysis.
	 * 
	 * @since 2.0.0
	 */
	public function ajax_analyze_seo() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_die( __( 'Security check failed', '365i-ai-faq-generator' ) );
		}

		$faq_data = isset( $_POST['faq_data'] ) ? $_POST['faq_data'] : array();

		if ( empty( $faq_data ) ) {
			wp_send_json_error( __( 'FAQ data is required', '365i-ai-faq-generator' ) );
		}

		$result = $this->analyze_seo( $faq_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for FAQ extraction.
	 * 
	 * @since 2.0.0
	 */
	public function ajax_extract_faq() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_die( __( 'Security check failed', '365i-ai-faq-generator' ) );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';

		if ( empty( $url ) ) {
			wp_send_json_error( __( 'URL is required', '365i-ai-faq-generator' ) );
		}

		$result = $this->extract_faq( $url );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for topic generation.
	 * 
	 * @since 2.0.0
	 */
	public function ajax_generate_topics() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_die( __( 'Security check failed', '365i-ai-faq-generator' ) );
		}

		$input = isset( $_POST['input'] ) ? sanitize_textarea_field( $_POST['input'] ) : '';

		if ( empty( $input ) ) {
			wp_send_json_error( __( 'Input text is required', '365i-ai-faq-generator' ) );
		}

		$result = $this->generate_topics( $input );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Get worker status.
	 * 
	 * Check the status of all workers.
	 * 
	 * @since 2.0.0
	 * @return array Worker status information.
	 */
	public function get_worker_status() {
		$status = array();

		foreach ( $this->workers as $worker_name => $config ) {
			$status[ $worker_name ] = array(
				'enabled' => $config['enabled'],
				'url' => $config['url'],
				'rate_limit' => $config['rate_limit'],
				'current_usage' => get_transient( $this->rate_limit_prefix . $worker_name ) ?: 0,
			);
		}

		return $status;
	}
}