<?php
/**
 * Workers Manager class for 365i AI FAQ Generator.
 * 
 * This class serves as a facade for the worker components, coordinating
 * between different specialized worker classes.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Workers
 * @since 2.1.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Workers Manager class.
 * 
 * Coordinates all worker functionality components.
 * 
 * @since 2.1.0
 */
class AI_FAQ_Workers_Manager {

	/**
	 * Rate Limiter component instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Workers_Rate_Limiter
	 */
	private $rate_limiter;

	/**
	 * Security component instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Workers_Security
	 */
	private $security;

	/**
	 * Analytics component instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Workers_Analytics
	 */
	private $analytics;

	/**
	 * Request Handler component instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Workers_Request_Handler
	 */
	private $request_handler;

	/**
	 * Available workers configuration.
	 * 
	 * @since 2.1.0
	 * @var array
	 */
	private $workers = array();

	/**
	 * Tracks which component instances were passed to the constructor.
	 *
	 * @since 2.1.0
	 * @var array
	 */
	private $constructor_params = array();

	/**
	 * Constructor.
	 *
	 * Initialize the workers manager.
	 *
	 * @since 2.1.0
	 * @param AI_FAQ_Workers_Rate_Limiter|null $rate_limiter Optional. Rate Limiter instance.
	 * @param AI_FAQ_Workers_Security|null     $security     Optional. Security instance.
	 * @param AI_FAQ_Workers_Analytics|null    $analytics    Optional. Analytics instance.
	 */
	public function __construct( $rate_limiter = null, $security = null, $analytics = null ) {
		$this->load_worker_config();
		
		// Track which components were provided to constructor
		$this->constructor_params = array();
		
		// Set components if provided
		if ( $rate_limiter !== null ) {
			$this->rate_limiter = $rate_limiter;
			$this->constructor_params[] = $rate_limiter;
		}
		
		if ( $security !== null ) {
			$this->security = $security;
			$this->constructor_params[] = $security;
		}
		
		if ( $analytics !== null ) {
			$this->analytics = $analytics;
			$this->constructor_params[] = $analytics;
		}
	}

	/**
	 * Initialize the workers manager.
	 *
	 * Load dependencies and set up hooks and filters.
	 *
	 * @since 2.1.0
	 */
	public function init() {
		// Load dependencies first.
		$this->load_dependencies();

		// Initialize components only if they weren't provided in the constructor
		if ( $this->rate_limiter === null ) {
			$this->rate_limiter = new AI_FAQ_Workers_Rate_Limiter( $this->workers );
		}
		
		if ( $this->security === null ) {
			$this->security = new AI_FAQ_Workers_Security();
		}
		
		if ( $this->analytics === null ) {
			$this->analytics = new AI_FAQ_Workers_Analytics();
		}
		
		if ( $this->request_handler === null ) {
			$this->request_handler = new AI_FAQ_Workers_Request_Handler(
				$this,
				$this->rate_limiter,
				$this->security,
				$this->analytics
			);
		}

		// Determine which components to initialize (those created locally, not passed from facade)
		// Explicitly define variables to avoid undefined variable warnings
		$should_init_rate_limiter = false;
		$should_init_security = false;
		$should_init_analytics = false;
		
		// Only initialize components that were created here (not passed from facade)
		if ($this->rate_limiter !== null && !in_array($this->rate_limiter, $this->constructor_params, true)) {
			$should_init_rate_limiter = true;
		}
		
		if ($this->security !== null && !in_array($this->security, $this->constructor_params, true)) {
			$should_init_security = true;
		}
		
		if ($this->analytics !== null && !in_array($this->analytics, $this->constructor_params, true)) {
			$should_init_analytics = true;
		}
		
		// Initialize each component that's newly created in this class
		if ( $should_init_rate_limiter ) {
			$this->rate_limiter->init();
		}
		
		if ( $should_init_security ) {
			$this->security->init();
		}
		
		if ( $should_init_analytics ) {
			$this->analytics->init();
		}
		
		if ( $this->request_handler !== null && $should_init_rate_limiter ) {
			$this->request_handler->init();
		}
	}

	/**
	 * Load worker component dependencies.
	 * 
	 * Include all necessary class files for worker functionality.
	 * 
	 * @since 2.1.0
	 */
	private function load_dependencies() {
		$components_dir = AI_FAQ_GEN_DIR . 'includes/workers/components/';

		// Load component classes.
		require_once $components_dir . 'class-ai-faq-workers-rate-limiter.php';
		require_once $components_dir . 'class-ai-faq-workers-security.php';
		require_once $components_dir . 'class-ai-faq-workers-analytics.php';
		require_once $components_dir . 'class-ai-faq-workers-request-handler.php';
	}

	/**
	 * Load worker configuration from options.
	 *
	 * @since 2.1.0
	 */
	private function load_worker_config() {
		$options = get_option( 'ai_faq_gen_options', array() );
		$this->workers = isset( $options['workers'] ) ? $options['workers'] : array();
	}

	/**
	 * Reload worker configuration from database.
	 *
	 * This method forces a fresh reload of worker configuration from the database,
	 * ensuring that any recently saved changes are immediately available.
	 *
	 * @since 2.1.0
	 * @return bool True if configuration was reloaded successfully.
	 */
	public function reload_worker_config() {
		// Clear any object cache for the options to ensure fresh data
		wp_cache_delete( 'ai_faq_gen_options', 'options' );
		
		// Load fresh configuration from database
		$this->load_worker_config();
		
		// Log the reload for debugging
		error_log( '[365i AI FAQ] Worker configuration reloaded from database' );
		error_log( '[365i AI FAQ] Reloaded workers: ' . implode( ', ', array_keys( $this->workers ) ) );
		
		return true;
	}

	/**
	 * Generate questions using Question Generator worker.
	 * 
	 * @since 2.1.0
	 * @param string $topic Topic to generate questions for.
	 * @param int    $count Number of questions to generate.
	 * @return array|WP_Error Generated questions or error.
	 */
	public function generate_questions( $topic, $count = 12 ) {
		return $this->make_worker_request( 'question_generator', array(
			'topic' => sanitize_text_field( $topic ),
			'count' => intval( $count ),
		) );
	}

	/**
	 * Generate answers using Answer Generator worker.
	 * 
	 * @since 2.1.0
	 * @param array $questions Array of questions.
	 * @return array|WP_Error Generated answers or error.
	 */
	public function generate_answers( $questions ) {
		return $this->make_worker_request( 'answer_generator', array(
			'questions' => array_map( 'sanitize_text_field', $questions ),
		) );
	}

	/**
	 * Enhance FAQ using FAQ Enhancer worker.
	 * 
	 * @since 2.1.0
	 * @param array $faq_data FAQ data to enhance.
	 * @return array|WP_Error Enhanced FAQ or error.
	 */
	public function enhance_faq( $faq_data ) {
		return $this->make_worker_request( 'faq_enhancer', array(
			'faq' => $faq_data,
		) );
	}

	/**
	 * Analyze SEO using SEO Analyzer worker.
	 * 
	 * @since 2.1.0
	 * @param array $faq_data FAQ data to analyze.
	 * @return array|WP_Error SEO analysis or error.
	 */
	public function analyze_seo( $faq_data ) {
		return $this->make_worker_request( 'seo_analyzer', array(
			'faq' => $faq_data,
		) );
	}

	/**
	 * Extract FAQ from URL using FAQ Extractor worker.
	 * 
	 * @since 2.1.0
	 * @param string $url URL to extract FAQ from.
	 * @return array|WP_Error Extracted FAQ or error.
	 */
	public function extract_faq( $url ) {
		return $this->make_worker_request( 'faq_extractor', array(
			'url' => esc_url_raw( $url ),
		) );
	}

	/**
	 * Generate topics using Topic Generator worker.
	 * 
	 * @since 2.1.0
	 * @param string $input Input text for topic generation.
	 * @return array|WP_Error Generated topics or error.
	 */
	public function generate_topics( $input ) {
		return $this->make_worker_request( 'topic_generator', array(
			'input' => sanitize_textarea_field( $input ),
		) );
	}

	/**
	 * Get the endpoint path for a worker.
	 *
	 * Maps worker names to their corresponding API endpoint paths.
	 * For health checks, empty strings should be returned for workers that
	 * expect operations on their base URL rather than specific endpoints.
	 *
	 * @since 2.1.0
	 * @param string $worker_name Worker name.
	 * @param string $operation Optional. Specific operation to get endpoint for. Default is empty.
	 * @return string Endpoint path.
	 */
	private function get_worker_endpoint( $worker_name, $operation = '' ) {
		// For health checks, most workers support the /health endpoint
		if ( 'health' === $operation ) {
			return '/health';
		}
		
		// For normal API operations, use specific endpoints
		$endpoints = array(
			'question_generator' => '', // Base URL with question parameter
			'answer_generator' => '', // Base URL with answer parameter
			'faq_enhancer' => '/enhance',
			'seo_analyzer' => '/analyze',
			'faq_extractor' => '/extract',
			'topic_generator' => '/generate-topics',
		);

		return isset( $endpoints[ $worker_name ] ) ? $endpoints[ $worker_name ] : '';
	}

	/**
	 * Make API request to worker.
	 *
	 * This method handles all worker API requests with support for different
	 * HTTP methods, rate limiting, caching, and detailed error logging.
	 *
	 * @since 2.1.0
	 * @param string $worker_name Worker name.
	 * @param array  $data Request data.
	 * @param string $method HTTP method.
	 * @param string $operation Optional. Specific operation type for endpoint determination. Default empty.
	 * @return array|WP_Error Response data or error.
	 */
	public function make_worker_request( $worker_name, $data = array(), $method = 'POST', $operation = '' ) {
		// Normalize worker name for consistent handling
		$normalized_worker = str_replace( '-', '_', $worker_name );
		
		// Check if worker exists and is enabled.
		if ( ! isset( $this->workers[ $normalized_worker ] ) && ! isset( $this->workers[ $worker_name ] ) ) {
			return new WP_Error(
				'worker_not_found',
				/* translators: %s: Worker name */
				sprintf( __( 'Worker %s not found in configuration', '365i-ai-faq-generator' ), $worker_name )
			);
		}
		
		// Get the correct worker key
		$worker_key = isset( $this->workers[ $normalized_worker ] ) ? $normalized_worker : $worker_name;
		
		// Check if worker is enabled
		if ( ! $this->workers[ $worker_key ]['enabled'] ) {
			return new WP_Error(
				'worker_disabled',
				/* translators: %s: Worker name */
				sprintf( __( 'Worker %s is not enabled', '365i-ai-faq-generator' ), $worker_name )
			);
		}

		// Check rate limiting.
		if ( ! $this->rate_limiter->check_rate_limit( $worker_key ) ) {
			return new WP_Error(
				'rate_limit_exceeded',
				/* translators: %s: Worker name */
				sprintf( __( 'Rate limit exceeded for worker %s', '365i-ai-faq-generator' ), $worker_name )
			);
		}

		$worker_config = $this->workers[ $worker_key ];

		// Check cache for GET requests.
		if ( 'GET' === $method && $this->rate_limiter->is_cache_enabled() ) {
			$cached_response = $this->rate_limiter->get_cached_response( $worker_key, $data );
			
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
				'Origin'       => home_url(),
				'Accept'       => 'application/json',
			),
		);

		// Add body for POST requests.
		if ( 'POST' === $method && ! empty( $data ) ) {
			$args['body'] = wp_json_encode( $data );
		}

		// Get the endpoint path and build the full URL
		$endpoint = $this->get_worker_endpoint( $worker_key, $operation );
		$base_url = rtrim( $worker_config['url'], '/' );
		
		// Only append endpoint if it's not empty
		$full_url = !empty($endpoint) ? $base_url . $endpoint : $base_url;
		
		// Log the exact URL we're using for debugging
		error_log( sprintf(
			'[365i AI FAQ] Making %s request to worker: %s at URL: %s (operation: %s)',
			$method,
			$worker_name,
			$full_url,
			empty($operation) ? 'default' : $operation
		) );

		// Make the request.
		$response = wp_remote_request( $full_url, $args );

		// Update rate limit counter.
		$this->rate_limiter->update_rate_limit( $worker_key );

		// Track analytics for this request.
		$start_time = microtime( true );
		$success = ! is_wp_error( $response );
		$ip_address = $this->security->get_client_ip();
		$this->analytics->track_usage( $worker_key, $ip_address, $success, $start_time );

		// Handle response errors.
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			error_log( sprintf( '[365i AI FAQ] Worker request error: %s', $error_message ) );
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		
		// Log response details
		error_log( sprintf( '[365i AI FAQ] Worker response code: %d', $response_code ) );
		error_log( sprintf( '[365i AI FAQ] Worker response preview: %s', substr( $response_body, 0, 200 ) ) );

		// Check for HTTP errors.
		if ( $response_code < 200 || $response_code >= 300 ) {
			$error_data = json_decode( $response_body, true );
			$error_detail = '';
			
			// Try to extract more specific error information
			if ( is_array( $error_data ) ) {
				if ( isset( $error_data['error'] ) ) {
					$error_detail = ': ' . $error_data['error'];
				} elseif ( isset( $error_data['message'] ) ) {
					$error_detail = ': ' . $error_data['message'];
				}
			}
			
			return new WP_Error(
				'http_error',
				/* translators: 1: HTTP response code, 2: Worker name, 3: Error detail */
				sprintf(
					__( 'HTTP error %1$d from worker %2$s%3$s', '365i-ai-faq-generator' ),
					$response_code,
					$worker_name,
					$error_detail
				)
			);
		}

		// Decode JSON response.
		$decoded_response = json_decode( $response_body, true );

		if ( null === $decoded_response ) {
			error_log( sprintf( '[365i AI FAQ] Invalid JSON response from worker: %s', $worker_name ) );
			return new WP_Error(
				'json_decode_error',
				/* translators: %s: Worker name */
				sprintf( __( 'Invalid JSON response from worker %s', '365i-ai-faq-generator' ), $worker_name )
			);
		}

		// Cache successful GET responses.
		if ( 'GET' === $method && $this->rate_limiter->is_cache_enabled() ) {
			$this->rate_limiter->cache_response( $worker_key, $data, $decoded_response );
		}

		return $decoded_response;
	}

	/**
	 * Get worker status.
	 * 
	 * Check the status of all workers.
	 * 
	 * @since 2.1.0
	 * @return array Worker status information.
	 */
	public function get_worker_status() {
		$status = array();

		foreach ( $this->workers as $worker_name => $config ) {
			$usage = $this->rate_limiter->get_rate_limit_usage( $worker_name );
			
			$status[ $worker_name ] = array(
				'enabled' => $config['enabled'],
				'url' => $config['url'],
				'rate_limit' => $config['rate_limit'],
				'current_usage' => $usage['current'],
				'usage_percentage' => $usage['percentage'],
			);
		}

		return $status;
	}

	/**
	 * Get Rate Limiter component.
	 *
	 * @since 2.1.0
	 * @return AI_FAQ_Workers_Rate_Limiter
	 */
	public function get_rate_limiter() {
		return $this->rate_limiter;
	}

	/**
	 * Get Security component.
	 *
	 * @since 2.1.0
	 * @return AI_FAQ_Workers_Security
	 */
	public function get_security() {
		return $this->security;
	}

	/**
	 * Get Analytics component.
	 *
	 * @since 2.1.0
	 * @return AI_FAQ_Workers_Analytics
	 */
	public function get_analytics() {
		return $this->analytics;
	}

	/**
	 * Get Request Handler component.
	 *
	 * @since 2.1.0
	 * @return AI_FAQ_Workers_Request_Handler
	 */
	public function get_request_handler() {
		return $this->request_handler;
	}

	/**
	 * Get workers configuration.
	 *
	 * @since 2.1.0
	 * @return array
	 */
	public function get_workers_config() {
		return $this->workers;
	}
	
	// The get_constructor_params() method has been removed as it's no longer needed.
	// We now track parameters directly in the constructor_params property.
}