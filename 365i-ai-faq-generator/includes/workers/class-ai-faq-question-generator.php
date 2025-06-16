<?php
/**
 * Question Generator Worker integration class for 365i AI FAQ Generator.
 * 
 * This class handles communication with the Question Generator Cloudflare worker,
 * including rate limiting, error handling, retry logic, and response validation.
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
 * Question Generator Worker class.
 * 
 * Manages communication with the Question Generator Cloudflare worker
 * with enhanced error handling, rate limiting, and response validation.
 * 
 * @since 2.0.0
 */
class AI_FAQ_Question_Generator {

	/**
	 * Worker name identifier.
	 * 
	 * @since 2.0.0
	 * @var string
	 */
	private $worker_name = 'question_generator';

	/**
	 * Default worker URL.
	 * 
	 * @since 2.0.0
	 * @var string
	 */
	private $default_url = 'https://faq-realtime-assistant-worker.winter-cake-bf57.workers.dev';

	/**
	 * Default worker configuration.
	 * 
	 * @since 2.0.0
	 * @var array
	 */
	private $default_config = array(
		'enabled' => true,
		'rate_limit' => 100,
		'timeout' => 30,
		'max_retries' => 3,
		'retry_delay' => 2,
	);

	/**
	 * Worker configuration.
	 * 
	 * @since 2.0.0
	 * @var array
	 */
	private $config;

	/**
	 * Constructor.
	 * 
	 * Initialize the Question Generator worker with configuration.
	 * 
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->load_config();
	}

	/**
	 * Load worker configuration from options.
	 * 
	 * @since 2.0.0
	 */
	private function load_config() {
		$options = get_option( 'ai_faq_gen_options', array() );
		$workers = isset( $options['workers'] ) ? $options['workers'] : array();
		
		// Merge with defaults.
		$this->config = wp_parse_args(
			isset( $workers[ $this->worker_name ] ) ? $workers[ $this->worker_name ] : array(),
			array_merge( $this->default_config, array( 'url' => $this->default_url ) )
		);
	}

	/**
	 * Generate questions using the worker.
	 * 
	 * @since 2.0.0
	 * @param string $topic Topic to generate questions for.
	 * @param int    $count Number of questions to generate.
	 * @param array  $context Additional context data.
	 * @return array|WP_Error Generated questions or error.
	 */
	public function generate_questions( $topic, $count = 12, $context = array() ) {
		// Validate inputs.
		if ( empty( $topic ) ) {
			return new WP_Error(
				'invalid_topic',
				__( 'Topic is required for question generation.', '365i-ai-faq-generator' )
			);
		}

		$count = max( 1, min( 50, intval( $count ) ) );

		// Check if worker is enabled.
		if ( ! $this->is_enabled() ) {
			return new WP_Error(
				'worker_disabled',
				__( 'Question Generator worker is disabled.', '365i-ai-faq-generator' )
			);
		}

		// Check rate limiting.
		if ( ! $this->check_rate_limit() ) {
			return new WP_Error(
				'rate_limit_exceeded',
				__( 'Rate limit exceeded for Question Generator worker.', '365i-ai-faq-generator' )
			);
		}

		// Prepare request data.
		$request_data = array(
			'topic' => sanitize_text_field( $topic ),
			'count' => $count,
			'context' => $this->sanitize_context( $context ),
			'timestamp' => current_time( 'timestamp' ),
		);

		// Apply filters to request data.
		$request_data = apply_filters( 'ai_faq_gen_question_generator_request_data', $request_data, $topic, $count, $context );

		// Make the request with retry logic.
		$result = $this->make_request_with_retry( $request_data );

		// Update rate limit counter.
		if ( ! is_wp_error( $result ) ) {
			$this->update_rate_limit();
		}

		// Apply filters to response.
		$result = apply_filters( 'ai_faq_gen_question_generator_response', $result, $request_data );

		return $result;
	}

	/**
	 * Test worker connectivity and health.
	 * 
	 * @since 2.0.0
	 * @return array Test results with status and details.
	 */
	public function test_connectivity() {
		$test_data = array(
			'topic' => 'Test connectivity',
			'count' => 1,
			'test' => true,
		);

		$start_time = microtime( true );
		$result = $this->make_request( $test_data );
		$end_time = microtime( true );

		$response_time = round( ( $end_time - $start_time ) * 1000 );

		if ( is_wp_error( $result ) ) {
			return array(
				'status' => 'error',
				'message' => $result->get_error_message(),
				'response_time' => $response_time,
				'url' => $this->get_worker_url(),
			);
		}

		return array(
			'status' => 'success',
			'message' => __( 'Worker is responding correctly.', '365i-ai-faq-generator' ),
			'response_time' => $response_time,
			'url' => $this->get_worker_url(),
			'data' => $result,
		);
	}

	/**
	 * Make HTTP request with retry logic.
	 * 
	 * @since 2.0.0
	 * @param array $data Request data.
	 * @return array|WP_Error Response data or error.
	 */
	private function make_request_with_retry( $data ) {
		$max_retries = intval( $this->config['max_retries'] );
		$retry_delay = intval( $this->config['retry_delay'] );

		for ( $attempt = 0; $attempt <= $max_retries; $attempt++ ) {
			$result = $this->make_request( $data );

			// Return success immediately.
			if ( ! is_wp_error( $result ) ) {
				return $result;
			}

			// Don't retry on certain errors.
			$error_code = $result->get_error_code();
			if ( in_array( $error_code, array( 'invalid_topic', 'worker_disabled', 'rate_limit_exceeded' ), true ) ) {
				return $result;
			}

			// Wait before retry (except on last attempt).
			if ( $attempt < $max_retries ) {
				sleep( $retry_delay * ( $attempt + 1 ) );
			}
		}

		return $result;
	}

	/**
	 * Make HTTP request to worker.
	 * 
	 * @since 2.0.0
	 * @param array $data Request data.
	 * @return array|WP_Error Response data or error.
	 */
	private function make_request( $data ) {
		$worker_url = $this->get_worker_url();

		if ( empty( $worker_url ) ) {
			return new WP_Error(
				'missing_url',
				__( 'Worker URL is not configured.', '365i-ai-faq-generator' )
			);
		}

		// Prepare request arguments.
		$args = array(
			'method'  => 'POST',
			'timeout' => intval( $this->config['timeout'] ),
			'headers' => array(
				'Content-Type' => 'application/json',
				'User-Agent'   => 'WordPress/365i-AI-FAQ-Generator',
				'X-Worker-Name' => $this->worker_name,
			),
			'body' => wp_json_encode( $data ),
		);

		// Apply filters to request arguments.
		$args = apply_filters( 'ai_faq_gen_question_generator_request_args', $args, $data );

		// Make the request.
		$response = wp_remote_request( $worker_url, $args );

		// Handle response errors.
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'request_failed',
				sprintf(
					/* translators: %s: Error message */
					__( 'Request failed: %s', '365i-ai-faq-generator' ),
					$response->get_error_message()
				)
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Check for HTTP errors.
		if ( $response_code < 200 || $response_code >= 300 ) {
			return new WP_Error(
				'http_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'HTTP error %d from Question Generator worker', '365i-ai-faq-generator' ),
					$response_code
				)
			);
		}

		// Decode JSON response.
		$decoded_response = json_decode( $response_body, true );

		if ( null === $decoded_response ) {
			return new WP_Error(
				'json_decode_error',
				__( 'Invalid JSON response from Question Generator worker', '365i-ai-faq-generator' )
			);
		}

		// Validate response structure.
		return $this->validate_response( $decoded_response );
	}

	/**
	 * Validate worker response structure.
	 * 
	 * @since 2.0.0
	 * @param array $response Response data.
	 * @return array|WP_Error Validated response or error.
	 */
	private function validate_response( $response ) {
		// Check for error in response.
		if ( isset( $response['error'] ) ) {
			return new WP_Error(
				'worker_error',
				sprintf(
					/* translators: %s: Error message from worker */
					__( 'Worker error: %s', '365i-ai-faq-generator' ),
					sanitize_text_field( $response['error'] )
				)
			);
		}

		// Validate required fields.
		if ( ! isset( $response['questions'] ) || ! is_array( $response['questions'] ) ) {
			return new WP_Error(
				'invalid_response',
				__( 'Invalid response structure from Question Generator worker', '365i-ai-faq-generator' )
			);
		}

		// Sanitize questions.
		$sanitized_questions = array();
		foreach ( $response['questions'] as $question ) {
			if ( is_string( $question ) && ! empty( trim( $question ) ) ) {
				$sanitized_questions[] = sanitize_text_field( $question );
			}
		}

		$response['questions'] = $sanitized_questions;

		return $response;
	}

	/**
	 * Sanitize context data.
	 * 
	 * @since 2.0.0
	 * @param array $context Context data.
	 * @return array Sanitized context data.
	 */
	private function sanitize_context( $context ) {
		if ( ! is_array( $context ) ) {
			return array();
		}

		$sanitized = array();

		// Sanitize URL if provided.
		if ( isset( $context['url'] ) ) {
			$sanitized['url'] = esc_url_raw( $context['url'] );
		}

		// Sanitize existing FAQs if provided.
		if ( isset( $context['existing_faqs'] ) && is_array( $context['existing_faqs'] ) ) {
			$sanitized['existing_faqs'] = array_map( 'sanitize_text_field', $context['existing_faqs'] );
		}

		// Sanitize additional context.
		if ( isset( $context['additional_context'] ) ) {
			$sanitized['additional_context'] = sanitize_textarea_field( $context['additional_context'] );
		}

		return $sanitized;
	}

	/**
	 * Check if worker is enabled.
	 * 
	 * @since 2.0.0
	 * @return bool True if enabled, false otherwise.
	 */
	public function is_enabled() {
		return ! empty( $this->config['enabled'] );
	}

	/**
	 * Get worker URL.
	 * 
	 * @since 2.0.0
	 * @return string Worker URL.
	 */
	public function get_worker_url() {
		return ! empty( $this->config['url'] ) ? $this->config['url'] : $this->default_url;
	}

	/**
	 * Get worker configuration.
	 * 
	 * @since 2.0.0
	 * @return array Worker configuration.
	 */
	public function get_config() {
		return $this->config;
	}

	/**
	 * Update worker configuration.
	 * 
	 * @since 2.0.0
	 * @param array $new_config New configuration.
	 * @return bool True if updated successfully, false otherwise.
	 */
	public function update_config( $new_config ) {
		$options = get_option( 'ai_faq_gen_options', array() );
		
		if ( ! isset( $options['workers'] ) ) {
			$options['workers'] = array();
		}

		$options['workers'][ $this->worker_name ] = array_merge( $this->config, $new_config );
		
		$result = update_option( 'ai_faq_gen_options', $options );
		
		if ( $result ) {
			$this->load_config();
		}

		return $result;
	}

	/**
	 * Check rate limit for worker.
	 * 
	 * @since 2.0.0
	 * @return bool True if within rate limit, false otherwise.
	 */
	private function check_rate_limit() {
		$rate_limit = intval( $this->config['rate_limit'] );
		
		if ( $rate_limit <= 0 ) {
			return true;
		}

		$cache_key = 'ai_faq_rate_limit_' . $this->worker_name;
		$current_count = get_transient( $cache_key );

		return ( false === $current_count || $current_count < $rate_limit );
	}

	/**
	 * Update rate limit counter.
	 * 
	 * @since 2.0.0
	 */
	private function update_rate_limit() {
		$cache_key = 'ai_faq_rate_limit_' . $this->worker_name;
		$current_count = get_transient( $cache_key );

		if ( false === $current_count ) {
			set_transient( $cache_key, 1, HOUR_IN_SECONDS );
		} else {
			set_transient( $cache_key, $current_count + 1, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Get current rate limit usage.
	 * 
	 * @since 2.0.0
	 * @return array Rate limit usage information.
	 */
	public function get_rate_limit_usage() {
		$cache_key = 'ai_faq_rate_limit_' . $this->worker_name;
		$current_count = get_transient( $cache_key );
		$rate_limit = intval( $this->config['rate_limit'] );

		return array(
			'current' => $current_count ? intval( $current_count ) : 0,
			'limit' => $rate_limit,
			'remaining' => max( 0, $rate_limit - ( $current_count ? intval( $current_count ) : 0 ) ),
			'percentage' => $rate_limit > 0 ? round( ( ( $current_count ? intval( $current_count ) : 0 ) / $rate_limit ) * 100, 2 ) : 0,
		);
	}

	/**
	 * Reset rate limit counter.
	 * 
	 * @since 2.0.0
	 * @return bool True if reset successfully, false otherwise.
	 */
	public function reset_rate_limit() {
		$cache_key = 'ai_faq_rate_limit_' . $this->worker_name;
		return delete_transient( $cache_key );
	}
}