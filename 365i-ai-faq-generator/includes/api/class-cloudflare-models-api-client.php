<?php
/**
 * Cloudflare Models API Client for 365i AI FAQ Generator.
 * 
 * This class handles communication with the Cloudflare Models API Worker,
 * providing methods to fetch model data, capabilities, and provider information
 * with robust caching and error handling.
 * 
 * @package AI_FAQ_Generator
 * @subpackage API
 * @since 2.5.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cloudflare Models API Client class.
 * 
 * Handles all interactions with the Cloudflare Models API Worker including
 * model retrieval, caching, error handling, and data transformation.
 * 
 * @since 2.5.0
 */
class AI_FAQ_Cloudflare_Models_API_Client {

	/**
	 * The base URL for the Cloudflare Models API Worker.
	 *
	 * @since 2.5.0
	 * @var string
	 */
	private $base_url = 'https://cloudflare-models-api-worker.winter-cake-bf57.workers.dev';

	/**
	 * API version for compatibility.
	 *
	 * @since 2.5.0
	 * @var string
	 */
	private $api_version = 'v1';

	/**
	 * Cache configuration settings.
	 *
	 * @since 2.5.0
	 * @var array
	 */
	private $cache_config = array(
		'models_list'     => 15 * MINUTE_IN_SECONDS,  // 15 minutes
		'model_details'   => 1 * HOUR_IN_SECONDS,     // 1 hour
		'capabilities'    => 4 * HOUR_IN_SECONDS,     // 4 hours
		'providers'       => 4 * HOUR_IN_SECONDS,     // 4 hours
		'health_check'    => 5 * MINUTE_IN_SECONDS,   // 5 minutes
	);

	/**
	 * HTTP timeout for API requests.
	 *
	 * @since 2.5.0
	 * @var int
	 */
	private $request_timeout = 30;

	/**
	 * Constructor.
	 *
	 * @since 2.5.0
	 */
	public function __construct() {
		// Allow customization of base URL via filter
		$this->base_url = apply_filters( 'ai_faq_gen_models_api_base_url', $this->base_url );
		
		// Allow customization of cache configuration
		$this->cache_config = apply_filters( 'ai_faq_gen_models_api_cache_config', $this->cache_config );
	}

	/**
	 * Get available models with optional filtering and pagination.
	 *
	 * @since 2.5.0
	 * @param array $filters Optional filters (provider, capability, task).
	 * @param array $pagination Optional pagination (limit, page).
	 * @return array|WP_Error Array of models or WP_Error on failure.
	 */
	public function get_models( $filters = array(), $pagination = array() ) {
		// Build query parameters
		$params = array_merge( $filters, $pagination );
		
		// Create cache key based on parameters
		$cache_key = 'ai_faq_models_' . md5( serialize( $params ) );
		
		error_log( "AI FAQ Generator: get_models() called with cache_key: {$cache_key}" );
		error_log( "AI FAQ Generator: Filters: " . wp_json_encode( $filters ) );
		error_log( "AI FAQ Generator: Pagination: " . wp_json_encode( $pagination ) );
		
		// TEMPORARILY CLEAR CACHE TO DEBUG API CALLS
		$this->clear_cache();
		error_log( "AI FAQ Generator: Cache cleared for debugging - forcing fresh API request" );
		
		// Try to get from cache first
		$cached_response = $this->get_cached_response( $cache_key );
		if ( false !== $cached_response ) {
			error_log( "AI FAQ Generator: Returning cached response with " . ( isset( $cached_response['models'] ) ? count( $cached_response['models'] ) : 'unknown' ) . " models" );
			return $cached_response;
		}
		
		error_log( "AI FAQ Generator: No cache found, making API request to /models" );

		// Make API request
		$response = $this->make_request( '/models', $params );
		
		if ( is_wp_error( $response ) ) {
			error_log( "AI FAQ Generator: API request failed: " . $response->get_error_message() );
			// Try to return cached data even if expired
			$stale_cache = get_transient( $cache_key . '_stale' );
			if ( false !== $stale_cache ) {
				error_log( 'AI FAQ Generator: Using stale cache for models due to API error: ' . $response->get_error_message() );
				return $stale_cache;
			}
			return $response;
		}

		error_log( "AI FAQ Generator: API request succeeded, transforming response" );

		// Transform and validate response
		$transformed_models = $this->transform_models_response( $response );
		
		if ( is_wp_error( $transformed_models ) ) {
			error_log( "AI FAQ Generator: Response transformation failed: " . $transformed_models->get_error_message() );
			return $transformed_models;
		}

		error_log( "AI FAQ Generator: Response transformed successfully, caching result" );

		// Cache the response
		$this->cache_response( $cache_key, $transformed_models, $this->cache_config['models_list'] );
		
		// Also save as stale cache for fallback
		set_transient( $cache_key . '_stale', $transformed_models, DAY_IN_SECONDS );

		return $transformed_models;
	}

	/**
	 * Get detailed information for a specific model.
	 *
	 * @since 2.5.0
	 * @param string $model_id The model ID to get details for.
	 * @return array|WP_Error Model details or WP_Error on failure.
	 */
	public function get_model_details( $model_id ) {
		if ( empty( $model_id ) || ! is_string( $model_id ) ) {
			return new WP_Error( 'invalid_model_id', __( 'Invalid model ID provided.', '365i-ai-faq-generator' ) );
		}

		$model_id = sanitize_text_field( $model_id );
		$cache_key = 'ai_faq_model_details_' . md5( $model_id );
		
		// Try to get from cache first
		$cached_response = $this->get_cached_response( $cache_key );
		if ( false !== $cached_response ) {
			return $cached_response;
		}

		// Make API request
		$endpoint = '/model/' . urlencode( $model_id );
		$response = $this->make_request( $endpoint );
		
		if ( is_wp_error( $response ) ) {
			// Try to return cached data even if expired
			$stale_cache = get_transient( $cache_key . '_stale' );
			if ( false !== $stale_cache ) {
				error_log( 'AI FAQ Generator: Using stale cache for model details due to API error: ' . $response->get_error_message() );
				return $stale_cache;
			}
			return $response;
		}

		// Transform and validate response
		$transformed_model = $this->transform_model_details_response( $response );
		
		if ( is_wp_error( $transformed_model ) ) {
			return $transformed_model;
		}

		// Cache the response
		$this->cache_response( $cache_key, $transformed_model, $this->cache_config['model_details'] );
		
		// Also save as stale cache for fallback
		set_transient( $cache_key . '_stale', $transformed_model, DAY_IN_SECONDS );

		return $transformed_model;
	}

	/**
	 * Get list of available providers.
	 *
	 * @since 2.5.0
	 * @return array|WP_Error Array of providers or WP_Error on failure.
	 */
	public function get_providers() {
		$cache_key = 'ai_faq_providers_list';
		
		// Try to get from cache first
		$cached_response = $this->get_cached_response( $cache_key );
		if ( false !== $cached_response ) {
			return $cached_response;
		}

		// Make API request
		$response = $this->make_request( '/providers' );
		
		if ( is_wp_error( $response ) ) {
			// Try to return cached data even if expired
			$stale_cache = get_transient( $cache_key . '_stale' );
			if ( false !== $stale_cache ) {
				error_log( 'AI FAQ Generator: Using stale cache for providers due to API error: ' . $response->get_error_message() );
				return $stale_cache;
			}
			return $response;
		}

		// Validate and process response
		if ( ! isset( $response['providers'] ) || ! is_array( $response['providers'] ) ) {
			return new WP_Error( 'invalid_providers_response', __( 'Invalid providers response format.', '365i-ai-faq-generator' ) );
		}

		$providers = $response['providers'];

		// Cache the response
		$this->cache_response( $cache_key, $providers, $this->cache_config['providers'] );
		
		// Also save as stale cache for fallback
		set_transient( $cache_key . '_stale', $providers, DAY_IN_SECONDS );

		return $providers;
	}

	/**
	 * Get list of available capabilities.
	 *
	 * @since 2.5.0
	 * @return array|WP_Error Array of capabilities or WP_Error on failure.
	 */
	public function get_capabilities() {
		$cache_key = 'ai_faq_capabilities_list';
		
		// Try to get from cache first
		$cached_response = $this->get_cached_response( $cache_key );
		if ( false !== $cached_response ) {
			return $cached_response;
		}

		// Make API request
		$response = $this->make_request( '/capabilities' );
		
		if ( is_wp_error( $response ) ) {
			// Try to return cached data even if expired
			$stale_cache = get_transient( $cache_key . '_stale' );
			if ( false !== $stale_cache ) {
				error_log( 'AI FAQ Generator: Using stale cache for capabilities due to API error: ' . $response->get_error_message() );
				return $stale_cache;
			}
			return $response;
		}

		// Validate and process response
		if ( ! isset( $response['capabilities'] ) || ! is_array( $response['capabilities'] ) ) {
			return new WP_Error( 'invalid_capabilities_response', __( 'Invalid capabilities response format.', '365i-ai-faq-generator' ) );
		}

		$capabilities = $response['capabilities'];

		// Cache the response
		$this->cache_response( $cache_key, $capabilities, $this->cache_config['capabilities'] );
		
		// Also save as stale cache for fallback
		set_transient( $cache_key . '_stale', $capabilities, DAY_IN_SECONDS );

		return $capabilities;
	}

	/**
	 * Get models from a specific provider.
	 *
	 * @since 2.5.0
	 * @param string $provider The provider name.
	 * @return array|WP_Error Array of models or WP_Error on failure.
	 */
	public function get_provider_models( $provider ) {
		if ( empty( $provider ) || ! is_string( $provider ) ) {
			return new WP_Error( 'invalid_provider', __( 'Invalid provider specified.', '365i-ai-faq-generator' ) );
		}

		return $this->get_models( array( 'provider' => sanitize_text_field( $provider ) ) );
	}

	/**
	 * Perform health check on the API worker.
	 *
	 * @since 2.5.0
	 * @return array|WP_Error Health check result or WP_Error on failure.
	 */
	public function health_check() {
		$cache_key = 'ai_faq_models_health_check';
		
		// Try to get from cache first (short cache for health checks)
		$cached_response = $this->get_cached_response( $cache_key );
		if ( false !== $cached_response ) {
			return $cached_response;
		}

		// Make API request
		$response = $this->make_request( '/health' );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Validate health response
		$health_data = array(
			'status' => isset( $response['status'] ) ? sanitize_text_field( $response['status'] ) : 'unknown',
			'api_connectivity' => isset( $response['api_connectivity'] ) ? sanitize_text_field( $response['api_connectivity'] ) : 'unknown',
			'model_count' => isset( $response['model_count'] ) ? (int) $response['model_count'] : 0,
			'response_time' => isset( $response['response_time'] ) ? sanitize_text_field( $response['response_time'] ) : 'unknown',
			'timestamp' => current_time( 'c' ),
		);

		// Cache the response
		$this->cache_response( $cache_key, $health_data, $this->cache_config['health_check'] );

		return $health_data;
	}

	/**
	 * Search models by query string.
	 *
	 * @since 2.5.0
	 * @param string $query Search query.
	 * @param array  $filters Optional additional filters.
	 * @return array|WP_Error Array of matching models or WP_Error on failure.
	 */
	public function search_models( $query, $filters = array() ) {
		if ( empty( $query ) || ! is_string( $query ) ) {
			return new WP_Error( 'invalid_search_query', __( 'Invalid search query provided.', '365i-ai-faq-generator' ) );
		}

		$search_params = array_merge( $filters, array( 'search' => sanitize_text_field( $query ) ) );
		
		return $this->get_models( $search_params );
	}

	/**
	 * Make an HTTP request to the API worker.
	 *
	 * @since 2.5.0
	 * @param string $endpoint The API endpoint.
	 * @param array  $params Optional query parameters.
	 * @return array|WP_Error Decoded response or WP_Error on failure.
	 */
	private function make_request( $endpoint, $params = array() ) {
		// Build full URL
		$url = rtrim( $this->base_url, '/' ) . '/' . ltrim( $endpoint, '/' );
		
		// Add query parameters if provided
		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		// Prepare request arguments
		$args = array(
			'timeout'     => $this->request_timeout,
			'user-agent'  => 'WordPress-AI-FAQ-Generator/' . ( defined( 'AI_FAQ_GEN_VERSION' ) ? AI_FAQ_GEN_VERSION : '2.5.0' ),
			'headers'     => array(
				'Accept' => 'application/json',
			),
		);

		// Allow filtering of request arguments
		$args = apply_filters( 'ai_faq_gen_models_api_request_args', $args, $endpoint, $params );

		// Make the request
		$response = wp_remote_get( $url, $args );

		// Enhanced debugging
		error_log( "AI FAQ Generator: Making API request to: {$url}" );
		error_log( "AI FAQ Generator: Request args: " . print_r( $args, true ) );

		// Check for request errors
		if ( is_wp_error( $response ) ) {
			error_log( 'AI FAQ Generator: API request failed: ' . $response->get_error_message() );
			return new WP_Error( 'api_request_failed',
				sprintf(
					/* translators: %s: Error message */
					__( 'Failed to connect to models API: %s', '365i-ai-faq-generator' ),
					$response->get_error_message()
				)
			);
		}

		// Get response code
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		error_log( "AI FAQ Generator: Response code: {$response_code}" );
		error_log( "AI FAQ Generator: Response body (first 500 chars): " . substr( $response_body, 0, 500 ) );

		// Check for HTTP errors
		if ( $response_code < 200 || $response_code >= 300 ) {
			error_log( "AI FAQ Generator: API returned HTTP {$response_code}: {$response_body}" );
			return new WP_Error( 'api_http_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'API returned HTTP %d error.', '365i-ai-faq-generator' ),
					$response_code
				)
			);
		}

		// Decode JSON response
		$decoded_response = json_decode( $response_body, true );
		
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( 'AI FAQ Generator: Invalid JSON response: ' . json_last_error_msg() );
			error_log( 'AI FAQ Generator: Raw response body: ' . $response_body );
			return new WP_Error( 'invalid_json_response', __( 'Invalid JSON response from API.', '365i-ai-faq-generator' ) );
		}

		error_log( "AI FAQ Generator: Decoded response structure: " . print_r( array_keys( $decoded_response ), true ) );
		if ( isset( $decoded_response['models'] ) ) {
			error_log( "AI FAQ Generator: Models array count: " . count( $decoded_response['models'] ) );
		} else {
			error_log( "AI FAQ Generator: No 'models' key found in response" );
		}

		return $decoded_response;
	}

	/**
	 * Transform the models list API response to WordPress format.
	 *
	 * @since 2.5.0
	 * @param array $response Raw API response.
	 * @return array|WP_Error Transformed models data or WP_Error on failure.
	 */
	private function transform_models_response( $response ) {
		error_log( "AI FAQ Generator: transform_models_response() called" );
		error_log( "AI FAQ Generator: Response keys: " . print_r( array_keys( $response ), true ) );
		
		if ( ! isset( $response['models'] ) || ! is_array( $response['models'] ) ) {
			error_log( "AI FAQ Generator: TRANSFORM ERROR - No 'models' key or not array" );
			error_log( "AI FAQ Generator: Response structure: " . print_r( $response, true ) );
			return new WP_Error( 'invalid_models_response', __( 'Invalid models response format.', '365i-ai-faq-generator' ) );
		}

		error_log( "AI FAQ Generator: Found models array with " . count( $response['models'] ) . " items" );
		
		$transformed_models = array();
		$transform_errors = 0;

		foreach ( $response['models'] as $index => $model ) {
			$transformed_model = $this->transform_single_model( $model );
			if ( ! is_wp_error( $transformed_model ) ) {
				$transformed_models[] = $transformed_model;
			} else {
				$transform_errors++;
				error_log( "AI FAQ Generator: Transform error for model {$index}: " . $transformed_model->get_error_message() );
			}
		}

		error_log( "AI FAQ Generator: Successfully transformed " . count( $transformed_models ) . " models, {$transform_errors} errors" );

		// Add metadata from response
		$result = array(
			'models' => $transformed_models,
			'total_count' => isset( $response['total'] ) ? (int) $response['total'] : count( $transformed_models ),
			'page' => isset( $response['page'] ) ? (int) $response['page'] : 1,
			'per_page' => isset( $response['limit'] ) ? (int) $response['limit'] : count( $transformed_models ),
			'available_filters' => isset( $response['available_filters'] ) ? $response['available_filters'] : array(),
		);

		error_log( "AI FAQ Generator: Final result has " . count( $result['models'] ) . " models" );
		return $result;
	}

	/**
	 * Transform a single model details API response.
	 *
	 * @since 2.5.0
	 * @param array $response Raw API response.
	 * @return array|WP_Error Transformed model data or WP_Error on failure.
	 */
	private function transform_model_details_response( $response ) {
		if ( ! isset( $response['model'] ) || ! is_array( $response['model'] ) ) {
			return new WP_Error( 'invalid_model_response', __( 'Invalid model response format.', '365i-ai-faq-generator' ) );
		}

		return $this->transform_single_model( $response['model'] );
	}

	/**
	 * Transform a single model from API format to WordPress format.
	 *
	 * @since 2.5.0
	 * @param array $model Raw model data from API.
	 * @return array|WP_Error Transformed model data or WP_Error on failure.
	 */
	private function transform_single_model( $model ) {
		// Validate required fields - API returns 'id' and 'display_name'
		if ( ! isset( $model['id'] ) || ! isset( $model['display_name'] ) ) {
			error_log( "AI FAQ Generator: Transform validation failed for model: " . print_r( array_keys( $model ), true ) );
			return new WP_Error( 'invalid_model_data', __( 'Model missing required fields.', '365i-ai-faq-generator' ) );
		}

		error_log( "AI FAQ Generator: Transforming model with ID: " . $model['id'] );

		// Build transformed model using the actual API response structure
		$transformed = array(
			'id' => sanitize_text_field( $model['id'] ),
			'name' => sanitize_text_field( $model['display_name'] ),
			'provider' => isset( $model['provider'] ) ? sanitize_text_field( $model['provider'] ) : 'Unknown',
			'description' => isset( $model['best_for'] ) ? sanitize_textarea_field( $model['best_for'] ) : '',
			'capabilities' => isset( $model['capabilities'] ) && is_array( $model['capabilities'] ) ?
				array_map( 'sanitize_text_field', $model['capabilities'] ) : array(),
			'use_cases' => isset( $model['use_cases'] ) && is_array( $model['use_cases'] ) ?
				array_map( 'sanitize_text_field', $model['use_cases'] ) : array(),
			'pricing_tier' => isset( $model['pricing_tier'] ) ? sanitize_text_field( $model['pricing_tier'] ) : 'unknown',
			'performance' => $this->extract_performance_metrics_from_api( $model ),
			'parameters' => $this->extract_model_parameters_from_api( $model ),
			'raw_data' => $model, // Keep original for debugging
		);

		// Determine worker compatibility
		$transformed['best_for'] = $this->determine_worker_compatibility( $transformed );

		error_log( "AI FAQ Generator: Successfully transformed model: " . $transformed['id'] );
		return apply_filters( 'ai_faq_gen_transform_model_data', $transformed, $model );
	}

	/**
	 * Extract performance metrics from enhanced metadata.
	 *
	 * @since 2.5.0
	 * @param array $enhanced Enhanced metadata.
	 * @return array Performance metrics.
	 */
	private function extract_performance_metrics( $enhanced ) {
		$performance = array(
			'speed' => 'medium',
			'quality' => 'good',
			'cost' => 'medium',
			'response_time' => '3-8s',
		);

		if ( isset( $enhanced['performance'] ) && is_array( $enhanced['performance'] ) ) {
			$perf_data = $enhanced['performance'];
			
			if ( isset( $perf_data['inference_speed'] ) ) {
				$performance['speed'] = $this->map_performance_value( $perf_data['inference_speed'], 'speed' );
			}
			
			if ( isset( $perf_data['output_quality'] ) ) {
				$performance['quality'] = $this->map_performance_value( $perf_data['output_quality'], 'quality' );
			}
			
			if ( isset( $perf_data['cost_efficiency'] ) ) {
				$performance['cost'] = $this->map_performance_value( $perf_data['cost_efficiency'], 'cost' );
			}
			
			if ( isset( $perf_data['typical_response_time'] ) ) {
				$performance['response_time'] = sanitize_text_field( $perf_data['typical_response_time'] );
			}
		}

		return $performance;
	}

	/**
	 * Extract performance metrics from API response structure.
	 *
	 * @since 2.5.0
	 * @param array $model Model data from API.
	 * @return array Performance metrics.
	 */
	private function extract_performance_metrics_from_api( $model ) {
		$performance = array(
			'speed' => 'medium',
			'quality' => 'good',
			'cost' => 'medium',
			'response_time' => '3-8s',
		);

		// Extract from API performance characteristics
		if ( isset( $model['performance_characteristics'] ) && is_array( $model['performance_characteristics'] ) ) {
			$chars = $model['performance_characteristics'];
			
			if ( in_array( 'fast_inference', $chars, true ) ) {
				$performance['speed'] = 'fast';
			}
			
			if ( in_array( 'high_accuracy', $chars, true ) ) {
				$performance['quality'] = 'excellent';
			} elseif ( in_array( 'good_accuracy', $chars, true ) ) {
				$performance['quality'] = 'good';
			}
			
			if ( in_array( 'efficient', $chars, true ) ) {
				$performance['cost'] = 'low';
			}
		}

		// Extract from pricing tier
		if ( isset( $model['pricing_tier'] ) ) {
			$tier = $model['pricing_tier'];
			switch ( $tier ) {
				case 'economy':
					$performance['cost'] = 'low';
					break;
				case 'basic':
					$performance['cost'] = 'low';
					break;
				case 'standard':
					$performance['cost'] = 'medium';
					break;
				case 'premium':
					$performance['cost'] = 'high';
					break;
			}
		}

		return $performance;
	}

	/**
	 * Map performance values from API to WordPress format.
	 *
	 * @since 2.5.0
	 * @param string $value API performance value.
	 * @param string $type Performance type (speed, quality, cost).
	 * @return string Mapped performance value.
	 */
	private function map_performance_value( $value, $type ) {
		$value = strtolower( sanitize_text_field( $value ) );
		
		$mappings = array(
			'speed' => array(
				'very fast' => 'fast',
				'fast' => 'fast',
				'medium' => 'medium',
				'slow' => 'slow',
				'optimized' => 'fast',
			),
			'quality' => array(
				'exceptional' => 'exceptional',
				'excellent' => 'excellent',
				'very good' => 'very good',
				'good' => 'good',
				'basic' => 'good',
			),
			'cost' => array(
				'economy' => 'low',
				'basic' => 'low',
				'standard' => 'medium',
				'premium' => 'high',
				'low' => 'low',
				'medium' => 'medium',
				'high' => 'high',
			),
		);

		return isset( $mappings[ $type ][ $value ] ) ? $mappings[ $type ][ $value ] : 'medium';
	}

	/**
	 * Extract model parameters from raw data.
	 *
	 * @since 2.5.0
	 * @param array $model Raw model data.
	 * @return array Model parameters.
	 */
	private function extract_model_parameters( $model ) {
		$parameters = array();

		// Extract context length/window
		if ( isset( $model['properties']['max_input_tokens'] ) ) {
			$parameters['context_length'] = (int) $model['properties']['max_input_tokens'];
		} elseif ( isset( $model['properties']['context_length'] ) ) {
			$parameters['context_length'] = (int) $model['properties']['context_length'];
		}

		// Extract max tokens
		if ( isset( $model['properties']['max_new_tokens'] ) ) {
			$parameters['max_tokens'] = (int) $model['properties']['max_new_tokens'];
		} elseif ( isset( $model['properties']['max_tokens'] ) ) {
			$parameters['max_tokens'] = (int) $model['properties']['max_tokens'];
		}

		return $parameters;
	}

	/**
	 * Extract model parameters from API response structure.
	 *
	 * @since 2.5.0
	 * @param array $model Model data from API.
	 * @return array Model parameters.
	 */
	private function extract_model_parameters_from_api( $model ) {
		$parameters = array();

		// Extract parameter count
		if ( isset( $model['parameter_count'] ) ) {
			$parameters['parameter_count'] = sanitize_text_field( $model['parameter_count'] );
		}

		// Extract context length from task properties
		if ( isset( $model['task']['properties']['max_input_tokens'] ) ) {
			$parameters['context_length'] = (int) $model['task']['properties']['max_input_tokens'];
		}

		// Extract max tokens from task properties
		if ( isset( $model['task']['properties']['max_new_tokens'] ) ) {
			$parameters['max_tokens'] = (int) $model['task']['properties']['max_new_tokens'];
		}

		// Extract pricing tier
		if ( isset( $model['pricing_tier'] ) ) {
			$parameters['pricing_tier'] = sanitize_text_field( $model['pricing_tier'] );
		}

		return $parameters;
	}

	/**
	 * Determine worker compatibility based on model characteristics.
	 *
	 * @since 2.5.0
	 * @param array $model Transformed model data.
	 * @return array Array of compatible worker types.
	 */
	private function determine_worker_compatibility( $model ) {
		$compatible_workers = array();
		$capabilities = $model['capabilities'];
		$performance = $model['performance'];

		// Question Generator - needs creative and fast models
		if ( in_array( 'text_generation', $capabilities, true ) && 
			 ( $performance['speed'] === 'fast' || $performance['speed'] === 'medium' ) ) {
			$compatible_workers[] = 'question_generator';
		}

		// Answer Generator - needs comprehensive models
		if ( in_array( 'text_generation', $capabilities, true ) ) {
			$compatible_workers[] = 'answer_generator';
		}

		// FAQ Enhancer - needs high quality models
		if ( in_array( 'text_generation', $capabilities, true ) && 
			 ( $performance['quality'] === 'excellent' || $performance['quality'] === 'exceptional' ) ) {
			$compatible_workers[] = 'faq_enhancer';
		}

		// SEO Analyzer - needs reasoning and analysis capabilities
		if ( in_array( 'advanced_reasoning', $capabilities, true ) || 
			 in_array( 'language_understanding', $capabilities, true ) ) {
			$compatible_workers[] = 'seo_analyzer';
		}

		// Topic Generator - needs comprehensive analysis
		if ( in_array( 'content_creation', $capabilities, true ) && 
			 in_array( 'language_understanding', $capabilities, true ) ) {
			$compatible_workers[] = 'topic_generator';
		}

		return $compatible_workers;
	}

	/**
	 * Cache API response using WordPress transients.
	 *
	 * @since 2.5.0
	 * @param string $key Cache key.
	 * @param mixed  $data Data to cache.
	 * @param int    $ttl Time to live in seconds.
	 * @return bool True if cached successfully, false otherwise.
	 */
	private function cache_response( $key, $data, $ttl ) {
		$cache_key = 'ai_faq_gen_' . sanitize_key( $key );
		return set_transient( $cache_key, $data, $ttl );
	}

	/**
	 * Get cached response from WordPress transients.
	 *
	 * @since 2.5.0
	 * @param string $key Cache key.
	 * @return mixed Cached data or false if not found.
	 */
	private function get_cached_response( $key ) {
		$cache_key = 'ai_faq_gen_' . sanitize_key( $key );
		return get_transient( $cache_key );
	}

	/**
	 * Clear all cached responses.
	 *
	 * @since 2.5.0
	 * @return bool True if cleared successfully.
	 */
	public function clear_cache() {
		global $wpdb;

		// Delete all transients with our prefix
		$wpdb->query( 
			$wpdb->prepare( 
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_ai_faq_gen_%',
				'_transient_timeout_ai_faq_gen_%'
			)
		);

		return true;
	}
}