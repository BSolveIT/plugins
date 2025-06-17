<?php
/**
 * FAQ Enhancer Worker integration class for 365i AI FAQ Generator.
 * 
 * This class handles communication with the FAQ Enhancement Cloudflare worker,
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
 * FAQ Enhancer Worker class.
 * 
 * Manages communication with the FAQ Enhancement Cloudflare worker
 * with enhanced error handling, rate limiting, and response validation.
 * 
 * @since 2.0.0
 */
class AI_FAQ_FAQ_Enhancer {

	/**
	 * Worker name identifier.
	 * 
	 * @since 2.0.0
	 * @var string
	 */
	private $worker_name = 'faq_enhancer';

	/**
	 * Default worker URL.
	 * 
	 * @since 2.0.0
	 * @var string
	 */
	private $default_url = 'https://faq-enhancement-worker.winter-cake-bf57.workers.dev';

	/**
	 * Default worker configuration.
	 * 
	 * @since 2.0.0
	 * @var array
	 */
	private $default_config = array(
		'enabled' => true,
		'rate_limit' => 25,
		'rate_limit_period' => 'daily',
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
	 * Initialize the FAQ Enhancer worker with configuration.
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
	 * Enhance FAQ using the worker.
	 * 
	 * @since 2.0.0
	 * @param string $question FAQ question to enhance.
	 * @param string $answer FAQ answer to enhance.
	 * @param string $page_url Optional page URL for context.
	 * @param string $session_id Optional session ID for caching.
	 * @return array|WP_Error Enhanced FAQ or error.
	 */
	public function enhance_faq( $question, $answer, $page_url = '', $session_id = '' ) {
		// Validate inputs.
		if ( empty( $question ) || empty( $answer ) ) {
			return new WP_Error(
				'invalid_faq_data',
				__( 'Both question and answer are required for FAQ enhancement.', '365i-ai-faq-generator' )
			);
		}

		// Check if worker is enabled.
		if ( ! $this->is_enabled() ) {
			return new WP_Error(
				'worker_disabled',
				__( 'FAQ Enhancer worker is disabled.', '365i-ai-faq-generator' )
			);
		}

		// Check rate limiting.
		if ( ! $this->check_rate_limit() ) {
			return new WP_Error(
				'rate_limit_exceeded',
				__( 'Daily enhancement limit reached for FAQ Enhancer worker.', '365i-ai-faq-generator' )
			);
		}

		// Prepare request data.
		$request_data = array(
			'question' => sanitize_text_field( $question ),
			'answer' => sanitize_textarea_field( $answer ),
			'pageUrl' => ! empty( $page_url ) ? esc_url_raw( $page_url ) : '',
			'sessionId' => ! empty( $session_id ) ? sanitize_text_field( $session_id ) : '',
			'timestamp' => current_time( 'timestamp' ),
		);

		// Apply filters to request data.
		$request_data = apply_filters( 'ai_faq_gen_faq_enhancer_request_data', $request_data, $question, $answer, $page_url, $session_id );

		// Make the request with retry logic.
		$result = $this->make_request_with_retry( $request_data );

		// Update rate limit counter.
		if ( ! is_wp_error( $result ) ) {
			$this->update_rate_limit();
		}

		// Apply filters to response.
		$result = apply_filters( 'ai_faq_gen_faq_enhancer_response', $result, $request_data );

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
			'question' => 'What is a test question?',
			'answer' => 'This is a test answer for connectivity testing.',
			'pageUrl' => '',
			'sessionId' => 'test_session',
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
			if ( in_array( $error_code, array( 'invalid_faq_data', 'worker_disabled', 'rate_limit_exceeded' ), true ) ) {
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
		$args = apply_filters( 'ai_faq_gen_faq_enhancer_request_args', $args, $data );

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
					__( 'HTTP error %d from FAQ Enhancer worker', '365i-ai-faq-generator' ),
					$response_code
				)
			);
		}

		// Decode JSON response.
		$decoded_response = json_decode( $response_body, true );

		if ( null === $decoded_response ) {
			return new WP_Error(
				'json_decode_error',
				__( 'Invalid JSON response from FAQ Enhancer worker', '365i-ai-faq-generator' )
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

		// Validate required fields for enhancement response.
		if ( ! isset( $response['enhancements'] ) || ! is_array( $response['enhancements'] ) ) {
			return new WP_Error(
				'invalid_response',
				__( 'Invalid response structure from FAQ Enhancer worker', '365i-ai-faq-generator' )
			);
		}

		// Sanitize enhancement data.
		$enhancements = $response['enhancements'];
		
		// Sanitize question variations.
		if ( isset( $enhancements['question_variations'] ) && is_array( $enhancements['question_variations'] ) ) {
			$sanitized_variations = array();
			foreach ( $enhancements['question_variations'] as $variation ) {
				if ( is_array( $variation ) && isset( $variation['question'] ) ) {
					$sanitized_variation = array(
						'question' => sanitize_text_field( $variation['question'] ),
						'reason' => isset( $variation['reason'] ) ? sanitize_text_field( $variation['reason'] ) : '',
						'type' => isset( $variation['type'] ) ? sanitize_text_field( $variation['type'] ) : '',
						'priority' => isset( $variation['priority'] ) ? sanitize_text_field( $variation['priority'] ) : '',
						'seo_benefit' => isset( $variation['seo_benefit'] ) ? sanitize_text_field( $variation['seo_benefit'] ) : '',
					);
					
					// Sanitize answers object.
					if ( isset( $variation['answers'] ) && is_array( $variation['answers'] ) ) {
						$sanitized_variation['answers'] = array(
							'optimised' => isset( $variation['answers']['optimised'] ) ? sanitize_textarea_field( $variation['answers']['optimised'] ) : '',
							'detailed' => isset( $variation['answers']['detailed'] ) ? sanitize_textarea_field( $variation['answers']['detailed'] ) : '',
						);
					}
					
					$sanitized_variations[] = $sanitized_variation;
				}
			}
			$enhancements['question_variations'] = $sanitized_variations;
		}

		// Sanitize additional suggestions.
		if ( isset( $enhancements['additional_suggestions'] ) && is_array( $enhancements['additional_suggestions'] ) ) {
			$sanitized_suggestions = array();
			foreach ( $enhancements['additional_suggestions'] as $suggestion ) {
				if ( is_array( $suggestion ) && isset( $suggestion['suggestion'] ) ) {
					$sanitized_suggestions[] = array(
						'suggestion' => sanitize_text_field( $suggestion['suggestion'] ),
						'type' => isset( $suggestion['type'] ) ? sanitize_text_field( $suggestion['type'] ) : '',
						'reason' => isset( $suggestion['reason'] ) ? sanitize_text_field( $suggestion['reason'] ) : '',
						'impact' => isset( $suggestion['impact'] ) ? sanitize_text_field( $suggestion['impact'] ) : '',
					);
				}
			}
			$enhancements['additional_suggestions'] = $sanitized_suggestions;
		}

		$response['enhancements'] = $enhancements;

		return $response;
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
	 * Get client IP address.
	 *
	 * @since 2.0.0
	 * @return string Client IP address.
	 */
	private function get_client_ip() {
		$ip_headers = array(
			'HTTP_CF_CONNECTING_IP',     // Cloudflare
			'HTTP_CLIENT_IP',            // Proxy
			'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
			'HTTP_X_FORWARDED',          // Proxy
			'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
			'HTTP_FORWARDED_FOR',        // Proxy
			'HTTP_FORWARDED',            // Proxy
			'REMOTE_ADDR'                // Standard
		);

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				// Handle comma-separated IPs (take first one)
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		// Fallback to REMOTE_ADDR even if it's private/reserved
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
	}

	/**
	 * Check rate limit for worker (daily limit per IP).
	 *
	 * @since 2.0.0
	 * @return bool True if within rate limit, false otherwise.
	 */
	private function check_rate_limit() {
		$rate_limit = intval( $this->config['rate_limit'] );
		
		if ( $rate_limit <= 0 ) {
			return true;
		}

		$client_ip = $this->get_client_ip();
		$cache_key = 'ai_faq_rate_limit_' . $this->worker_name . '_' . md5( $client_ip ) . '_' . gmdate( 'Y-m-d' );
		$current_count = get_transient( $cache_key );

		return ( false === $current_count || $current_count < $rate_limit );
	}

	/**
		* Update rate limit counter (daily per IP).
		*
		* @since 2.0.0
		*/
	private function update_rate_limit() {
		$client_ip = $this->get_client_ip();
		$cache_key = 'ai_faq_rate_limit_' . $this->worker_name . '_' . md5( $client_ip ) . '_' . gmdate( 'Y-m-d' );
		$current_count = get_transient( $cache_key );

		if ( false === $current_count ) {
			set_transient( $cache_key, 1, DAY_IN_SECONDS );
		} else {
			set_transient( $cache_key, $current_count + 1, DAY_IN_SECONDS );
		}
	}

	/**
		* Get current rate limit usage (per IP).
		*
		* @since 2.0.0
		* @return array Rate limit usage information.
		*/
	public function get_rate_limit_usage() {
		$client_ip = $this->get_client_ip();
		$cache_key = 'ai_faq_rate_limit_' . $this->worker_name . '_' . md5( $client_ip ) . '_' . gmdate( 'Y-m-d' );
		$current_count = get_transient( $cache_key );
		$rate_limit = intval( $this->config['rate_limit'] );

		return array(
			'current' => $current_count ? intval( $current_count ) : 0,
			'limit' => $rate_limit,
			'remaining' => max( 0, $rate_limit - ( $current_count ? intval( $current_count ) : 0 ) ),
			'percentage' => $rate_limit > 0 ? round( ( ( $current_count ? intval( $current_count ) : 0 ) / $rate_limit ) * 100, 2 ) : 0,
			'period' => 'daily',
			'client_ip' => $client_ip,
		);
	}

	/**
		* Reset rate limit counter (per IP).
		*
		* @since 2.0.0
		* @return bool True if reset successfully, false otherwise.
		*/
	public function reset_rate_limit() {
		$client_ip = $this->get_client_ip();
		$cache_key = 'ai_faq_rate_limit_' . $this->worker_name . '_' . md5( $client_ip ) . '_' . gmdate( 'Y-m-d' );
		return delete_transient( $cache_key );
	}
}