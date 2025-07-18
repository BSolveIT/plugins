<?php
/**
 * Admin AI Models management class for 365i AI FAQ Generator.
 * 
 * This class handles AI model selection, configuration, and management
 * for each FAQ worker, providing curated model lists and performance insights.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Admin
 * @since 2.2.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin AI Models management class.
 * 
 * @since 2.2.0
 */
class AI_FAQ_Admin_AI_Models {

	/**
	 * Note: Cloudflare Models API Client has been removed.
	 * The plugin now uses static fallback models and KV namespace for model configuration.
	 *
	 * @since 2.5.0
	 * @deprecated 2.5.1 Removed obsolete Cloudflare Models API Worker dependency.
	 */

	/**
	 * Initialize the AI models component.
	 *
	 * Set up hooks for model management and configuration.
	 *
	 * @since 2.2.0
	 */
	public function init() {
		// Note: Removed obsolete Cloudflare Models API Client initialization
		// The plugin now uses static fallback models and KV namespace configuration
		ai_faq_log_debug( 'AI FAQ Generator: AI Models admin initialized with static fallback models' );

		// Add AJAX handlers for model management.
		add_action( 'wp_ajax_ai_faq_save_ai_models', array( $this, 'handle_save_models_ajax' ) );
		add_action( 'wp_ajax_ai_faq_reset_ai_models', array( $this, 'handle_reset_models_ajax' ) );
		add_action( 'wp_ajax_ai_faq_test_model_connectivity', array( $this, 'handle_test_connectivity_ajax' ) );
		add_action( 'wp_ajax_ai_faq_test_model_performance', array( $this, 'handle_test_performance_ajax' ) );
		
		// Note: Removed obsolete AJAX handlers that depended on the API client
		// Only KV namespace model configuration is now supported
		add_action( 'wp_ajax_ai_faq_get_worker_ai_models', array( $this, 'handle_get_worker_ai_models_ajax' ) );
		add_action( 'wp_ajax_ai_faq_change_worker_model', array( $this, 'handle_change_worker_model_ajax' ) );
		
		// Enqueue scripts and styles for AI Models admin page
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin assets for AI Models page.
	 *
	 * @since 2.5.0
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Debug logging to see what hook suffix we're getting
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			ai_faq_log_debug( 'AI FAQ Generator: enqueue_admin_assets called with hook_suffix: ' . $hook_suffix );
		}
		
		// Check for AI Models admin page - be more flexible with hook suffix
		$is_ai_models_page = (
			strpos( $hook_suffix, 'ai-models' ) !== false ||
			strpos( $hook_suffix, 'ai-faq-generator' ) !== false ||
			$hook_suffix === 'ai-faq-generator_page_ai-faq-generator-ai-models' ||
			$hook_suffix === 'toplevel_page_ai-faq-generator-ai-models' ||
			$hook_suffix === 'admin_page_ai-faq-generator-ai-models'
		);
		
		// TEMPORARY DEBUG: Force load on AI FAQ pages for testing
		$force_load_debug = strpos( $hook_suffix, 'ai-faq-generator' ) !== false;
		
		if ( ! $is_ai_models_page && ! $force_load_debug ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				ai_faq_log_debug( 'AI FAQ Generator: Skipping asset enqueue - not AI models page. Hook: ' . $hook_suffix );
			}
			return;
		}
		
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			ai_faq_log_debug( 'AI FAQ Generator: Proceeding with asset enqueue for AI models page' );
		}

		$version = defined( 'AI_FAQ_GEN_VERSION' ) ? AI_FAQ_GEN_VERSION : '2.5.0';
		// Don't use .min suffix as these files don't exist yet
		$min_suffix = '';

		// Enqueue modal system CSS
		wp_enqueue_style(
			'ai-faq-modal-system',
			AI_FAQ_GEN_URL . "assets/css/modal-system{$min_suffix}.css",
			array(),
			$version
		);

		// Enqueue modal system JavaScript
		wp_enqueue_script(
			'ai-faq-modal-system',
			AI_FAQ_GEN_URL . "assets/js/modal-system{$min_suffix}.js",
			array( 'jquery' ),
			$version,
			true
		);

		// Enhanced admin AI models script (if not already enqueued)
		if ( ! wp_script_is( 'ai-faq-admin-ai-models', 'enqueued' ) ) {
			wp_enqueue_script(
				'ai-faq-admin-ai-models',
				AI_FAQ_GEN_URL . "assets/js/admin-ai-models{$min_suffix}.js",
				array( 'jquery', 'ai-faq-modal-system' ),
				$version,
				true
			);

			// Localize script with enhanced AJAX data
			wp_localize_script(
				'ai-faq-admin-ai-models',
				'aiFaqAjax',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'ai_faq_admin_nonce' ),
					'version' => $version,
					'strings' => array(
						'loading' => __( 'Loading...', '365i-ai-faq-generator' ),
						'error' => __( 'An error occurred', '365i-ai-faq-generator' ),
						'success' => __( 'Operation completed successfully', '365i-ai-faq-generator' ),
						'confirm_reset' => __( 'Are you sure you want to reset all configurations to defaults?', '365i-ai-faq-generator' ),
						'confirm_refresh' => __( 'This will refresh model data from the API. Continue?', '365i-ai-faq-generator' ),
						'no_model_selected' => __( 'Please select a model first.', '365i-ai-faq-generator' ),
						'connectivity_test_failed' => __( 'Connectivity test failed', '365i-ai-faq-generator' ),
						'modal_loading' => __( 'Loading model details...', '365i-ai-faq-generator' ),
						'modal_error' => __( 'Failed to load model details', '365i-ai-faq-generator' ),
					),
				)
			);
		}

		// Enhanced admin AI models CSS (if not already enqueued)
		if ( ! wp_style_is( 'ai-faq-admin-ai-models', 'enqueued' ) ) {
			wp_enqueue_style(
				'ai-faq-admin-ai-models',
				AI_FAQ_GEN_URL . "assets/css/admin-ai-models{$min_suffix}.css",
				array( 'ai-faq-modal-system' ),
				$version
			);
		}
		
		// Debug information
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			ai_faq_log_debug( 'AI FAQ Generator: CSS/JS enqueued for hook: ' . $hook_suffix );
		}
	}


	/**
	 * Get default model mappings for each worker type.
	 * 
	 * @since 2.2.0
	 * @return array Default model mappings by worker type.
	 */
	public function get_default_model_mappings() {
		$defaults = array(
			'question_generator' => '@cf/meta/llama-3.1-8b-instruct',
			'answer_generator' => '@cf/meta/llama-4-scout-17b-16e-instruct',
			'faq_enhancer' => '@cf/meta/llama-3.3-70b-instruct-fp8-fast',
			'seo_analyzer' => '@cf/meta/llama-3.3-70b-instruct-fp8-fast',
			'faq_proxy_fetch' => null, // Proxy service, no AI model
			'url_faq_generator' => '@cf/meta/llama-4-scout-17b-16e-instruct', // URL FAQ Generator
		);

		return apply_filters( 'ai_faq_gen_default_model_mappings', $defaults );
	}


	/**
	 * Get model configuration for all workers.
	 *
	 * @since 2.4.0
	 * @param bool $force_refresh Whether to bypass cache and fetch fresh data.
	 * @return array Model configurations by worker type.
	 */
	public function get_worker_model_configurations( $force_refresh = false ) {
		// If force refresh is requested, clear the cache first
		if ( $force_refresh ) {
			$cache_key = 'ai_faq_gen_ai_models_kv';
			delete_transient( $cache_key );
			
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				ai_faq_log_debug( 'AI FAQ Generator: Forced refresh of worker model configurations' );
			}
		}
		
		$stored_models = $this->get_models_from_kv_namespace();
		$data_source = 'kv_namespace';
		
		if ( false === $stored_models ) {
			$stored_models = array();
			$data_source = 'defaults_only';
		}
		
		// Build configurations
		$defaults = $this->get_default_model_mappings();
		$configurations = array();
		
		foreach ( $defaults as $worker_type => $default_model ) {
			$current_model = isset( $stored_models[ $worker_type ] ) ? $stored_models[ $worker_type ] : $default_model;
			
			$configurations[ $worker_type ] = array(
				'model' => $current_model,
				'default' => $default_model,
				'is_custom' => isset( $stored_models[ $worker_type ] ) && $stored_models[ $worker_type ] !== $default_model,
				'data_source' => $data_source,
			);
		}

		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			ai_faq_log_debug( 'AI FAQ Generator: Worker model configurations retrieved - data source: ' . $data_source );
			foreach ( $configurations as $worker_type => $config ) {
				ai_faq_log_debug( 'AI FAQ Generator: ' . $worker_type . ' -> ' . $config['model'] . ' (source: ' . $config['data_source'] . ')' );
			}
		}

		return apply_filters( 'ai_faq_gen_worker_model_configurations', $configurations );
	}

	/**
	 * Save model configurations.
	 *
	 * @since 2.4.1
	 * @param array $model_configs Model configurations to save.
	 * @return array Result array with success status and message.
	 */
	public function save_model_configurations( $model_configs ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Insufficient permissions to save model configurations.', '365i-ai-faq-generator' ),
			);
		}

		$defaults = $this->get_default_model_mappings();
		$sanitized_configs = array();

		// Validate and sanitize each model configuration
		foreach ( $model_configs as $worker_type => $model_id ) {
			$worker_type = sanitize_key( $worker_type );
			
			// Skip if worker type is not valid
			if ( ! isset( $defaults[ $worker_type ] ) ) {
				continue;
			}

			$model_id = sanitize_text_field( $model_id );

			// Validate model ID format
			if ( ! empty( $model_id ) && $this->is_valid_cloudflare_model_id( $model_id ) ) {
				$sanitized_configs[ $worker_type ] = $model_id;
			} else {
				// Fall back to default
				$sanitized_configs[ $worker_type ] = $defaults[ $worker_type ];
			}
		}

		// Save to KV namespace (single source of truth)
		$kv_save_result = $this->save_models_to_kv_namespace( $sanitized_configs );
		
		if ( $kv_save_result ) {
			// Clear local cache after model changes
			$this->clear_model_cache();
			
			// Clear worker caches to force them to reload from KV namespace
			$cache_clear_results = $this->clear_worker_caches_after_model_update( $sanitized_configs );
			
			// Get fresh configurations to ensure UI shows updated data
			$fresh_configurations = $this->get_worker_model_configurations( true );
			
			return array(
				'success' => true,
				'message' => sprintf(
					/* translators: %d: Number of workers configured */
					__( 'AI model configurations saved successfully for %d workers.', '365i-ai-faq-generator' ),
					count( $sanitized_configs )
				),
				'configurations' => $fresh_configurations,
				'cache_clear_results' => $cache_clear_results,
			);
		} else {
			return array(
				'success' => false,
				'message' => __( 'Failed to save AI model configurations to KV namespace. Please check your Cloudflare credentials and try again.', '365i-ai-faq-generator' ),
			);
		}
	}

	/**
	 * Test model connectivity and performance using worker health endpoint.
	 *
	 * This method now uses the worker health endpoint instead of making expensive
	 * AI API calls, providing fast connectivity tests without consuming AI tokens.
	 *
	 * @since 2.3.0
	 * @param string $model_id The model ID to test.
	 * @param string $worker_type The worker type for context.
	 * @return array Test result with connectivity status and performance metrics.
	 */
	public function test_model_connectivity( $model_id, $worker_type = '' ) {
		// Load worker admin for health checking
		if ( ! class_exists( 'AI_FAQ_Admin_Workers' ) ) {
			require_once AI_FAQ_GEN_DIR . 'includes/admin/class-ai-faq-admin-workers.php';
		}
		
		$worker_admin = new AI_FAQ_Admin_Workers();
		
		// Skip connectivity test for workers that don't use AI models
		if ( empty( $model_id ) ) {
			return array(
				'success' => true,
				'status' => 'no_model',
				'message' => __( 'No model configured - connectivity test not applicable', '365i-ai-faq-generator' ),
				'response_time_ms' => 0,
				'worker_type' => $worker_type,
			);
		}

		// Log the test attempt
		ai_faq_log_debug( sprintf(
			'AI FAQ Generator: Testing worker health for %s (model: %s)',
			$worker_type,
			$model_id
		) );

		// Use worker health check instead of direct AI API call
		$health_result = $worker_admin->test_worker_health( $worker_type );
		
		// Map health check result to connectivity result format
		if ( $health_result['status'] === 'healthy' ) {
			$response_time_ms = isset( $health_result['response_time'] ) ? $health_result['response_time'] : 0;
			
			// Get additional worker info if available
			$worker_info = isset( $health_result['worker_info'] ) ? $health_result['worker_info'] : array();
			$current_model = isset( $health_result['data']['current_model'] ) ? $health_result['data']['current_model'] : $model_id;
			
			return array(
				'success' => true,
				'status' => 'connected',
				'message' => __( 'Worker connection successful', '365i-ai-faq-generator' ),
				'response_time_ms' => $response_time_ms,
				'model_id' => $current_model,
				'worker_type' => $worker_type,
				'response_preview' => sprintf(
					__( 'Worker %s is healthy and responsive', '365i-ai-faq-generator' ),
					$worker_type
				),
				'health_data' => $health_result['data'],
				'worker_info' => $worker_info,
			);
		} else {
			$response_time_ms = isset( $health_result['response_time'] ) ? $health_result['response_time'] : 0;
			$error_message = isset( $health_result['message'] ) ? $health_result['message'] : __( 'Worker health check failed', '365i-ai-faq-generator' );
			
			return array(
				'success' => false,
				'status' => 'failed',
				'message' => __( 'Worker connection failed', '365i-ai-faq-generator' ),
				'error_details' => $error_message,
				'response_time_ms' => $response_time_ms,
				'worker_type' => $worker_type,
				'health_data' => isset( $health_result['data'] ) ? $health_result['data'] : null,
			);
		}
	}

	/**
	 * Generate connectivity status notification.
	 *
	 * @since 2.3.0
	 * @param array $connectivity_result Result from test_model_connectivity.
	 * @return array Notification data for frontend display.
	 */
	public function generate_connectivity_notification( $connectivity_result ) {
		$status = $connectivity_result['status'];
		
		// Fix timestamp issue - use current time since we just ran the test
		$timestamp_relative = __( 'just now', '365i-ai-faq-generator' );

		$notification = array(
			'status' => $status,
			'response_time_ms' => $connectivity_result['response_time_ms'],
			'timestamp_relative' => $timestamp_relative,
			'message' => $connectivity_result['message'],
		);

		switch ( $status ) {
			case 'connected':
				$notification['badge_class'] = 'status-success';
				$notification['badge_text'] = __( 'Connected', '365i-ai-faq-generator' );
				$notification['icon'] = 'yes-alt';
				break;

			case 'failed':
				$notification['badge_class'] = 'status-error';
				$notification['badge_text'] = __( 'Failed', '365i-ai-faq-generator' );
				$notification['icon'] = 'dismiss';
				$notification['error_details'] = $connectivity_result['error_details'];
				break;

			case 'error':
				$notification['badge_class'] = 'status-error';
				$notification['badge_text'] = __( 'Error', '365i-ai-faq-generator' );
				$notification['icon'] = 'warning';
				$notification['error_details'] = $connectivity_result['error_details'];
				break;

			default:
				$notification['badge_class'] = 'status-pending';
				$notification['badge_text'] = __( 'Testing', '365i-ai-faq-generator' );
				$notification['icon'] = 'update';
		}

		return $notification;
	}



	/**
	 * Get model display name efficiently by formatting model ID.
	 *
	 * Formats the model ID into a human-readable name without making API requests.
	 * Since we no longer use the API client, this method simply formats the model ID.
	 *
	 * @since 2.5.1
	 * @param string $model_id The model ID to get display name for.
	 * @return string Human-readable model display name.
	 */
	public function get_model_display_name_efficiently( $model_id ) {
		if ( empty( $model_id ) ) {
			return __( 'Unknown Model', '365i-ai-faq-generator' );
		}

		// Special handling for proxy services
		if ( $model_id === 'N/A (Proxy Service)' || $model_id === null ) {
			return __( 'N/A (Proxy Service)', '365i-ai-faq-generator' );
		}

		// Format the model ID into a human-readable name
		return $this->format_model_display_name( $model_id );
	}

	/**
	 * Get estimated response time for a model.
	 *
	 * Returns estimated response times based on model characteristics.
	 * Since we no longer use the API client, this provides generic estimates.
	 *
	 * @since 2.5.1
	 * @param string $model_id The model ID to get response time for.
	 * @return string Response time estimate.
	 */
	public function get_model_response_time( $model_id ) {
		if ( empty( $model_id ) || $model_id === 'N/A (Proxy Service)' ) {
			return __( 'Variable', '365i-ai-faq-generator' );
		}

		// Provide generic estimates based on model name patterns
		if ( strpos( $model_id, 'llama-3.1-8b' ) !== false ) {
			return __( '2-4 seconds', '365i-ai-faq-generator' );
		} elseif ( strpos( $model_id, 'llama-3.3-70b' ) !== false ) {
			return __( '4-8 seconds', '365i-ai-faq-generator' );
		} elseif ( strpos( $model_id, 'llama-4-scout' ) !== false ) {
			return __( '3-6 seconds', '365i-ai-faq-generator' );
		} else {
			// Default fallback response time
			return __( '3-8 seconds', '365i-ai-faq-generator' );
		}
	}


	/**
	 * Get models from KV namespace with transient caching.
	 *
	 * @since 2.4.0
	 * @return array|false AI model configurations or false if not available.
	 */
	private function get_models_from_kv_namespace() {
		// Try to get from cache first
		$cache_key = 'ai_faq_gen_ai_models_kv';
		$cached_models = get_transient( $cache_key );
		
		if ( false !== $cached_models ) {
			return $cached_models;
		}

		$options = get_option( 'ai_faq_gen_options', array() );
		$account_id = isset( $options['cloudflare_account_id'] ) ? $options['cloudflare_account_id'] : '';
		$api_token = isset( $options['cloudflare_api_token'] ) ? $options['cloudflare_api_token'] : '';

		if ( empty( $account_id ) || empty( $api_token ) ) {
			return false;
		}

		$namespace_id = $this->get_kv_namespace_id();
		
		if ( ! $namespace_id ) {
			return false;
		}

		$kv_url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/storage/kv/namespaces/{$namespace_id}/values/ai_model_config";

		$response = wp_remote_get( $kv_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Accept' => 'application/json',
			),
			'timeout' => 8,
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		
		if ( $response_code >= 200 && $response_code < 300 ) {
			$kv_data = json_decode( $response_body, true );
			
			if ( json_last_error() === JSON_ERROR_NONE && isset( $kv_data['ai_models'] ) && is_array( $kv_data['ai_models'] ) ) {
				$ai_models = $kv_data['ai_models'];
				
				// Cache for 5 minutes
				set_transient( $cache_key, $ai_models, 5 * MINUTE_IN_SECONDS );
				
				return $ai_models;
			}
		}

		return false;
	}

	/**
	 * Save models to KV namespace.
	 * 
	 * @since 2.2.0
	 * @param array $model_configs Model configurations to sync.
	 * @return bool Success status of sync operation.
	 */
	private function save_models_to_kv_namespace( $model_configs ) {
		$options = get_option( 'ai_faq_gen_options', array() );
		$account_id = isset( $options['cloudflare_account_id'] ) ? $options['cloudflare_account_id'] : '';
		$api_token = isset( $options['cloudflare_api_token'] ) ? $options['cloudflare_api_token'] : '';

		if ( empty( $account_id ) || empty( $api_token ) ) {
			return false;
		}

		// Format data for KV storage
		$kv_data = array(
			'ai_models' => $model_configs,
			'updated_at' => current_time( 'c' ),
			'updated_by' => wp_get_current_user()->user_login,
			'version' => defined( 'AI_FAQ_GEN_VERSION' ) ? AI_FAQ_GEN_VERSION : '2.4.0',
		);

		$namespace_id = $this->get_kv_namespace_id();
		
		if ( ! $namespace_id ) {
			return false;
		}

		$kv_url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/storage/kv/namespaces/{$namespace_id}/values/ai_model_config";

		$response = wp_remote_request( $kv_url, array(
			'method' => 'PUT',
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode( $kv_data ),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		return ( $response_code >= 200 && $response_code < 300 );
	}


	/**
	 * Get KV namespace ID for model configuration storage.
	 *
	 * @since 2.2.0
	 * @return string|false KV namespace ID or false if not available.
	 */
	private function get_kv_namespace_id() {
		$options = get_option( 'ai_faq_gen_options', array() );
		
		// Check if a specific namespace is configured for AI models
		if ( isset( $options['kv_namespace_id'] ) && ! empty( $options['kv_namespace_id'] ) ) {
			return $options['kv_namespace_id'];
		}

		// Use dedicated AI_MODEL_CONFIG namespace
		return 'e4a2fb4ce24949e3bac458c4176dfecd';
	}

	/**
	 * Reset model configurations to defaults.
	 *
	 * @since 2.2.0
	 * @return array Result array with success status and message.
	 */
	public function reset_to_defaults() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Insufficient permissions to reset model configurations.', '365i-ai-faq-generator' ),
			);
		}

		$defaults = $this->get_default_model_mappings();
		
		// Save to KV namespace (single source of truth)
		$kv_save_result = $this->save_models_to_kv_namespace( $defaults );
		
		if ( $kv_save_result ) {
			// Clear local cache after reset
			$this->clear_model_cache();
			
			// Clear worker caches to force them to reload from KV namespace
			$cache_clear_results = $this->clear_worker_caches_after_model_update( $defaults );
			
			// Get fresh configurations to ensure UI shows updated data
			$fresh_configurations = $this->get_worker_model_configurations( true );
			
			return array(
				'success' => true,
				'message' => __( 'AI model configurations have been reset to recommended defaults.', '365i-ai-faq-generator' ),
				'configurations' => $fresh_configurations,
				'cache_clear_results' => $cache_clear_results,
			);
		} else {
			return array(
				'success' => false,
				'message' => __( 'Failed to reset AI model configurations to KV namespace. Please check your Cloudflare credentials and try again.', '365i-ai-faq-generator' ),
			);
		}
	}

	/**
	 * Clear model configuration cache (KV namespace only).
	 *
	 * @since 2.4.3
	 * @return bool True if cache was cleared successfully.
	 */
	private function clear_model_cache() {
		$cache_keys = array(
			'ai_faq_gen_ai_models_kv', // KV namespace model configurations
		);
		
		$success_count = 0;
		foreach ( $cache_keys as $cache_key ) {
			if ( delete_transient( $cache_key ) ) {
				$success_count++;
			}
		}
		
		// Also clear any WordPress object cache if available
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		
		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			ai_faq_log_debug( 'AI FAQ Generator: Model cache cleared - cleared ' . $success_count . ' primary cache keys (KV namespace only)' );
		}
		
		return $success_count > 0;
	}

	/**
		* Clear worker caches after AI model configuration updates.
		*
		* @since 2.5.2
		* @param array $updated_models The updated model configurations.
		* @return array Results of cache clearing operations.
		*/
	private function clear_worker_caches_after_model_update( $updated_models ) {
		$options = get_option( 'ai_faq_gen_options', array() );
		$cache_clear_results = array(
			'attempted' => 0,
			'successful' => 0,
			'failed' => 0,
			'details' => array(),
		);

		// Enhanced debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			ai_faq_log_debug( 'AI FAQ Generator: Starting worker cache clearing for updated models: ' . wp_json_encode( array_keys( $updated_models ) ) );
		}

		// Get all worker types that use AI models
		$worker_types = array( 'question_generator', 'answer_generator', 'faq_enhancer', 'seo_analyzer' );

		foreach ( $worker_types as $worker_type ) {
			// Skip if this worker wasn't updated
			if ( ! isset( $updated_models[ $worker_type ] ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					ai_faq_log_debug( 'AI FAQ Generator: Skipping ' . $worker_type . ' - not in updated models' );
				}
				continue;
			}

			// Get worker URL
			$worker_url_key = $worker_type . '_url';
			$worker_url = '';

			// Try individual URL key first, then workers array
			if ( isset( $options[ $worker_url_key ] ) && ! empty( $options[ $worker_url_key ] ) ) {
				$worker_url = $options[ $worker_url_key ];
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					ai_faq_log_debug( 'AI FAQ Generator: Found URL for ' . $worker_type . ' in individual key: ' . $worker_url );
				}
			} elseif ( isset( $options['workers'][ $worker_type ]['url'] ) && ! empty( $options['workers'][ $worker_type ]['url'] ) ) {
				$worker_url = $options['workers'][ $worker_type ]['url'];
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					ai_faq_log_debug( 'AI FAQ Generator: Found URL for ' . $worker_type . ' in workers array: ' . $worker_url );
				}
			}

			if ( empty( $worker_url ) ) {
				$cache_clear_results['details'][ $worker_type ] = array(
					'status' => 'skipped',
					'message' => __( 'Worker URL not configured', '365i-ai-faq-generator' ),
				);
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					ai_faq_log_debug( 'AI FAQ Generator: No URL configured for ' . $worker_type . ' - skipping cache clear' );
				}
				continue;
			}

			$cache_clear_results['attempted']++;

			// Clear worker cache
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				ai_faq_log_debug( 'AI FAQ Generator: Attempting to clear cache for ' . $worker_type . ' at ' . $worker_url );
			}
			
			$clear_result = $this->clear_single_worker_cache( $worker_type, $worker_url );

			if ( $clear_result['success'] ) {
				$cache_clear_results['successful']++;
			} else {
				$cache_clear_results['failed']++;
			}

			$cache_clear_results['details'][ $worker_type ] = $clear_result;
		}

		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			ai_faq_log_debug( 'AI FAQ Generator: Worker cache clear COMPLETED - attempted: ' . $cache_clear_results['attempted'] . ', successful: ' . $cache_clear_results['successful'] . ', failed: ' . $cache_clear_results['failed'] );
			ai_faq_log_debug( 'AI FAQ Generator: Cache clear details: ' . wp_json_encode( $cache_clear_results['details'] ) );
		}

		return $cache_clear_results;
	}

	/**
		* Clear cache for a single worker.
		*
		* @since 2.5.2
		* @param string $worker_type The worker type.
		* @param string $worker_url The worker URL.
		* @return array Result of cache clearing operation.
		*/
	private function clear_single_worker_cache( $worker_type, $worker_url ) {
		$worker_url = rtrim( $worker_url, '/' );
		$cache_clear_url = $worker_url . '/cache/clear';

		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			ai_faq_log_debug( 'AI FAQ Generator: Clearing cache for ' . $worker_type . ' at ' . $cache_clear_url );
		}

		// Make request to worker cache clear endpoint
		$response = wp_remote_post( $cache_clear_url, array(
			'timeout' => 10,
			'headers' => array(
				'Content-Type' => 'application/json',
				'User-Agent' => 'WordPress-AI-FAQ-Generator/2.5.2 (Cache-Clear)',
			),
			'body' => wp_json_encode( array(
				'force' => true,
				'reason' => 'ai_model_config_update',
				'timestamp' => current_time( 'c' ),
			) ),
			'sslverify' => false, // Allow self-signed certificates in development
		) );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			ai_faq_log_error( 'AI FAQ Generator: Cache clear failed for ' . $worker_type . ': ' . $error_message );
			
			return array(
				'success' => false,
				'status' => 'error',
				'message' => sprintf(
					/* translators: %s: Error message */
					__( 'Failed to connect to worker: %s', '365i-ai-faq-generator' ),
					$error_message
				),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Consider 200-299 as success, and 404 as "endpoint not available but worker might still work"
		if ( $response_code >= 200 && $response_code < 300 ) {
			return array(
				'success' => true,
				'status' => 'cleared',
				'message' => __( 'Worker cache cleared successfully', '365i-ai-faq-generator' ),
				'response_code' => $response_code,
			);
		} elseif ( $response_code === 404 ) {
			// Cache clear endpoint doesn't exist, but that's okay
			return array(
				'success' => true,
				'status' => 'not_supported',
				'message' => __( 'Worker does not support cache clearing (legacy worker)', '365i-ai-faq-generator' ),
				'response_code' => $response_code,
			);
		} else {
			// Parse error response if available
			$error_message = $response_body;
			$response_data = json_decode( $response_body, true );
			if ( json_last_error() === JSON_ERROR_NONE && isset( $response_data['error'] ) ) {
				$error_message = $response_data['error'];
			}

			ai_faq_log_error( 'AI FAQ Generator: Cache clear failed for ' . $worker_type . ' - HTTP ' . $response_code . ': ' . $error_message );

			return array(
				'success' => false,
				'status' => 'failed',
				'message' => sprintf(
					/* translators: %1$d: HTTP response code, %2$s: Error message */
					__( 'Worker returned HTTP %1$d: %2$s', '365i-ai-faq-generator' ),
					$response_code,
					$error_message
				),
				'response_code' => $response_code,
			);
		}
	}

	/**
	 * Handle AJAX request to save model configurations.
	 *
	 * @since 2.3.0
	 */
	public function handle_save_models_ajax() {
		// Verify nonce
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ai_faq_gen_save_ai_models' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		$ai_models = isset( $_POST['ai_models'] ) ? $_POST['ai_models'] : array();
		
		if ( ! is_array( $ai_models ) ) {
			wp_send_json_error( __( 'Invalid data format received.', '365i-ai-faq-generator' ) );
		}

		$result = $this->save_model_configurations( $ai_models );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * Handle AJAX request to reset model configurations.
	 * 
	 * @since 2.3.0
	 */
	public function handle_reset_models_ajax() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_admin_nonce' ) ) {
			wp_die( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		$result = $this->reset_to_defaults();

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * Handle AJAX request to test model connectivity.
	 * 
	 * @since 2.3.0
	 */
	public function handle_test_connectivity_ajax() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_admin_nonce' ) ) {
			wp_die( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		$worker_type = sanitize_text_field( $_POST['worker_type'] );

		// Prioritize KV namespace configuration (source of truth) over worker health endpoint
		$model_configs = $this->get_worker_model_configurations( true ); // Force fresh data
		$model_id = '';
		$model_source = 'kv_namespace';
		
		// Get model from KV namespace configuration first
		if ( isset( $model_configs[ $worker_type ]['model'] ) ) {
			$model_id = $model_configs[ $worker_type ]['model'];
		}
		
		// Only fall back to worker health endpoint if KV namespace is not available
		if ( empty( $model_id ) ) {
			$ai_model_info = $this->fetch_worker_ai_model_info( $worker_type );
			if ( ! is_wp_error( $ai_model_info ) && ! empty( $ai_model_info['current_model'] ) ) {
				$model_id = $ai_model_info['current_model'];
				$model_source = 'worker_health';
			}
		}
		
		// Get AI model info for display purposes (but use KV namespace model for testing)
		$ai_model_info = $this->fetch_worker_ai_model_info( $worker_type );
		
		// Override worker's reported model with KV namespace model if available
		if ( ! is_wp_error( $ai_model_info ) && ! empty( $model_configs[ $worker_type ]['model'] ) ) {
			$ai_model_info['current_model'] = $model_configs[ $worker_type ]['model'];
			$ai_model_info['model_display_name'] = $this->get_model_display_name_efficiently( $model_configs[ $worker_type ]['model'] );
			$ai_model_info['model_source'] = 'kv_namespace_override';
			$ai_model_info['status'] = 'configured';
		}

		// Skip connectivity test for workers that don't use AI models
		if ( empty( $model_id ) ) {
			wp_send_json_success( array(
				'success' => true,
				'status' => 'no_model',
				'message' => __( 'No model configured - connectivity test not applicable', '365i-ai-faq-generator' ),
				'worker_type' => $worker_type,
				'ai_model_info' => array(
					'status' => 'not_configured',
					'current_model' => null,
					'model_display_name' => __( 'No Model Configured', '365i-ai-faq-generator' ),
					'model_source' => 'not_applicable',
				),
			) );
		}

		$result = $this->test_model_connectivity( $model_id, $worker_type );
		$notification = $this->generate_connectivity_notification( $result );

		// Add AI model information to the result
		$result['ai_model_info'] = $ai_model_info;

		if ( $result['success'] ) {
			wp_send_json_success( array_merge( $result, $notification ) );
		} else {
			wp_send_json_error( array_merge( $result, $notification ) );
		}
	}

	/**
	 * Handle AJAX request to test model performance.
	 *
	 * @since 2.3.0
	 */
	public function handle_test_performance_ajax() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_admin_nonce' ) ) {
			wp_die( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		$model_id = sanitize_text_field( $_POST['model_id'] );
		$worker_type = sanitize_text_field( $_POST['worker_type'] );

		// Test connectivity and performance
		$result = $this->test_model_connectivity( $model_id, $worker_type );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}




	/**
	 * Handle AJAX request to get AI model information from all workers.
	 *
	 * @since 2.3.0
	 */
	public function handle_get_worker_ai_models_ajax() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_admin_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		$worker_type = isset( $_POST['worker_type'] ) ? sanitize_text_field( $_POST['worker_type'] ) : '';
		
		if ( empty( $worker_type ) ) {
			wp_send_json_error( __( 'Worker type is required.', '365i-ai-faq-generator' ) );
		}

		// Prioritize KV namespace configuration (source of truth) over worker health endpoint
		$model_configs = $this->get_worker_model_configurations( true ); // Force fresh data
		$ai_model_info = array(
			'status' => 'unknown',
			'current_model' => null,
			'model_display_name' => null,
			'model_source' => 'unknown',
		);
		
		// Get model from KV namespace configuration first
		if ( isset( $model_configs[ $worker_type ]['model'] ) && ! empty( $model_configs[ $worker_type ]['model'] ) ) {
			$current_model = $model_configs[ $worker_type ]['model'];
			$ai_model_info = array(
				'status' => 'configured',
				'current_model' => $current_model,
				'model_display_name' => $this->get_model_display_name_efficiently( $current_model ),
				'model_source' => 'kv_config',
				'data_source' => $model_configs[ $worker_type ]['data_source'],
				'is_custom' => $model_configs[ $worker_type ]['is_custom'],
			);
		} else {
			// Fallback: get from worker health endpoint if KV namespace is not available
			$worker_ai_model_info = $this->fetch_worker_ai_model_info( $worker_type );
			
			if ( ! is_wp_error( $worker_ai_model_info ) ) {
				$ai_model_info = $worker_ai_model_info;
			} else {
				$ai_model_info['status'] = 'not_configured';
				$ai_model_info['model_display_name'] = __( 'No Model Configured', '365i-ai-faq-generator' );
			}
		}

		wp_send_json_success( array(
			'worker_type' => $worker_type,
			'ai_model_info' => $ai_model_info,
		) );
	}

	/**
	 * Handle AJAX request to change AI model for a worker.
	 *
	 * @since 2.5.0
	 */
	public function handle_change_worker_model_ajax() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_admin_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		$worker_type = isset( $_POST['worker_type'] ) ? sanitize_text_field( $_POST['worker_type'] ) : '';
		$new_model_id = isset( $_POST['model_id'] ) ? sanitize_text_field( $_POST['model_id'] ) : '';
		
		if ( empty( $worker_type ) ) {
			wp_send_json_error( __( 'Worker type is required.', '365i-ai-faq-generator' ) );
		}

		if ( empty( $new_model_id ) ) {
			wp_send_json_error( __( 'Model ID is required.', '365i-ai-faq-generator' ) );
		}

		// Validate worker type
		$defaults = $this->get_default_model_mappings();
		if ( ! isset( $defaults[ $worker_type ] ) ) {
			wp_send_json_error( __( 'Invalid worker type.', '365i-ai-faq-generator' ) );
		}


		// Validate model ID format for Cloudflare models
		if ( ! $this->is_valid_cloudflare_model_id( $new_model_id ) ) {
			wp_send_json_error( __( 'Invalid model ID format. Please use the format: @cf/provider/model-name', '365i-ai-faq-generator' ) );
		}

		// Get current model configurations
		$current_configs = $this->get_worker_model_configurations();
		$updated_configs = array();

		// Build updated configurations array preserving other workers
		foreach ( $current_configs as $wtype => $config ) {
			if ( $wtype === $worker_type ) {
				$updated_configs[ $wtype ] = $new_model_id;
			} else {
				$updated_configs[ $wtype ] = $config['model'];
			}
		}

		// Save the updated configurations
		$save_result = $this->save_model_configurations( $updated_configs );

		if ( $save_result['success'] ) {
			// Get model display name for response
			$model_display_name = $this->get_model_display_name_efficiently( $new_model_id );
			
			wp_send_json_success( array(
				'message' => sprintf(
					/* translators: %1$s: Worker name, %2$s: Model name */
					__( 'Successfully changed %1$s AI model to %2$s', '365i-ai-faq-generator' ),
					$this->format_worker_name( $worker_type ),
					$model_display_name
				),
				'worker_type' => $worker_type,
				'new_model_id' => $new_model_id,
				'model_display_name' => $model_display_name,
				'cache_clear_results' => isset( $save_result['cache_clear_results'] ) ? $save_result['cache_clear_results'] : null,
			) );
		} else {
			wp_send_json_error( $save_result['message'] );
		}
	}

	/**
	 * Fetch AI model information from a specific worker health endpoint.
	 *
	 * @since 2.3.0
	 * @param string $worker_type The worker type to fetch AI model info from.
	 * @return array|WP_Error AI model information or error.
	 */
	private function fetch_worker_ai_model_info( $worker_type ) {
		$options = get_option( 'ai_faq_gen_options', array() );
		
		// Debug logging: Show what options we're working with
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$debug_url_options = array();
			$worker_types = array( 'question_generator', 'answer_generator', 'faq_enhancer', 'seo_analyzer', 'url_faq_generator' );
			foreach ( $worker_types as $wt ) {
				$url_key = $wt . '_url';
				$debug_url_options[ $url_key ] = isset( $options[ $url_key ] ) ? $options[ $url_key ] : 'NOT_SET';
			}
			ai_faq_log_debug( '[365i AI FAQ] AI MODELS READING OPTIONS: ' . wp_json_encode( $debug_url_options ) );
			ai_faq_log_debug( '[365i AI FAQ] AI MODELS LOOKING FOR: ' . $worker_type . '_url' );
		}
		
		// Get worker URL from configuration - try individual URL key first, then workers array
		$worker_url_key = $worker_type . '_url';
		$worker_url = '';
		
		// Strategy 1: Try individual URL key (backward compatibility)
		if ( isset( $options[ $worker_url_key ] ) && ! empty( $options[ $worker_url_key ] ) ) {
			$worker_url = $options[ $worker_url_key ];
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				ai_faq_log_debug( '[365i AI FAQ] FOUND URL IN INDIVIDUAL KEY: ' . $worker_url );
			}
		}
		// Strategy 2: Try workers array (new format)
		elseif ( isset( $options['workers'][ $worker_type ]['url'] ) && ! empty( $options['workers'][ $worker_type ]['url'] ) ) {
			$worker_url = $options['workers'][ $worker_type ]['url'];
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				ai_faq_log_debug( '[365i AI FAQ] FOUND URL IN WORKERS ARRAY: ' . $worker_url );
			}
		}
		
		// If no URL found in either location, return error
		if ( empty( $worker_url ) ) {
			ai_faq_log_error( 'AI FAQ Generator: Worker URL not configured for ' . $worker_type );
			return new WP_Error( 'worker_url_not_configured', sprintf(
				/* translators: %s: Worker type */
				__( 'Worker URL not configured for %s', '365i-ai-faq-generator' ),
				$worker_type
			) );
		}

		$worker_url = rtrim( $worker_url, '/' );
		$health_url = $worker_url . '/health';

		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			ai_faq_log_debug( 'AI FAQ Generator: Fetching AI model info from ' . $health_url . ' for worker type: ' . $worker_type );
		}

		// Make request to worker health endpoint
		$response = wp_remote_get( $health_url, array(
			'timeout' => 15, // Increased timeout
			'headers' => array(
				'Accept' => 'application/json',
				'User-Agent' => 'WordPress-AI-FAQ-Generator/2.5.1 (AI-Model-Info)',
			),
			'sslverify' => false, // Allow self-signed certificates in development
		) );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			ai_faq_log_error( 'AI FAQ Generator: Health request failed for ' . $worker_type . ': ' . $error_message );
			return new WP_Error( 'health_request_failed', sprintf(
				/* translators: %s: Error message */
				__( 'Failed to connect to worker health endpoint: %s', '365i-ai-faq-generator' ),
				$error_message
			) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			ai_faq_log_debug( 'AI FAQ Generator: Health response for ' . $worker_type . ' - HTTP ' . $response_code . ', body length: ' . strlen( $response_body ) );
		}

		if ( $response_code < 200 || $response_code >= 300 ) {
			ai_faq_log_error( 'AI FAQ Generator: Health endpoint returned HTTP ' . $response_code . ' for ' . $worker_type );
			return new WP_Error( 'health_request_error', sprintf(
				/* translators: %d: HTTP response code */
				__( 'Worker health endpoint returned HTTP %d', '365i-ai-faq-generator' ),
				$response_code
			) );
		}

		if ( empty( $response_body ) ) {
			ai_faq_log_error( 'AI FAQ Generator: Empty health response for ' . $worker_type );
			return new WP_Error( 'empty_health_response', __( 'Empty response from worker health endpoint', '365i-ai-faq-generator' ) );
		}

		$health_data = json_decode( $response_body, true );
		
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$json_error = json_last_error_msg();
			ai_faq_log_error( 'AI FAQ Generator: JSON decode error for ' . $worker_type . ': ' . $json_error );
			ai_faq_log_error( 'AI FAQ Generator: Response body: ' . substr( $response_body, 0, 500 ) );
			return new WP_Error( 'invalid_health_response', sprintf(
				/* translators: %s: JSON error message */
				__( 'Invalid JSON response from worker health endpoint: %s', '365i-ai-faq-generator' ),
				$json_error
			) );
		}

		// Debug log the parsed health data structure
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			ai_faq_log_debug( 'AI FAQ Generator: Health data keys for ' . $worker_type . ': ' . implode( ', ', array_keys( $health_data ) ) );
			if ( isset( $health_data['current_model'] ) ) {
				ai_faq_log_debug( 'AI FAQ Generator: Found current_model: ' . $health_data['current_model'] );
			}
			if ( isset( $health_data['model'] ) ) {
				ai_faq_log_debug( 'AI FAQ Generator: Found model field: ' . wp_json_encode( $health_data['model'] ) );
			}
		}

		// Extract AI model information from health response
		$ai_model_info = array(
			'status' => 'unknown',
			'current_model' => null,
			'model_display_name' => null,
			'model_source' => 'unknown',
			'config_timestamp' => null,
			'health_url' => $health_url, // Add for debugging
		);


		// Check for AI model configuration in health response
		// Multiple fallback strategies to handle different response formats
		$model_found = false;

		// Strategy 1: Direct current_model field (primary format)
		if ( isset( $health_data['current_model'] ) && ! empty( $health_data['current_model'] ) ) {
			$ai_model_info['status'] = 'configured';
			$ai_model_info['current_model'] = $health_data['current_model'];
			$ai_model_info['model_display_name'] = $this->get_model_display_name_efficiently( $health_data['current_model'] );
			$ai_model_info['model_source'] = isset( $health_data['model_source'] ) ? $health_data['model_source'] : 'unknown';
			$ai_model_info['worker_type'] = isset( $health_data['worker_type'] ) ? $health_data['worker_type'] : $worker_type;
			$model_found = true;
			
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				ai_faq_log_debug( 'AI FAQ Generator: Strategy 1 - Found current_model: ' . $health_data['current_model'] );
			}
		}

		// Strategy 2: Nested model object with name field
		if ( ! $model_found && isset( $health_data['model'] ) && is_array( $health_data['model'] ) && isset( $health_data['model']['name'] ) ) {
			$ai_model_info['status'] = 'configured';
			$ai_model_info['current_model'] = $health_data['model']['name'];
			$ai_model_info['model_display_name'] = $this->get_model_display_name_efficiently( $health_data['model']['name'] );
			$ai_model_info['model_source'] = isset( $health_data['model_source'] ) ? $health_data['model_source'] : 'config';
			$ai_model_info['worker_type'] = isset( $health_data['worker_type'] ) ? $health_data['worker_type'] : $worker_type;
			$model_found = true;
			
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				ai_faq_log_debug( 'AI FAQ Generator: Strategy 2 - Found model.name: ' . $health_data['model']['name'] );
			}
		}

		// Strategy 3: Direct model field (string)
		if ( ! $model_found && isset( $health_data['model'] ) && is_string( $health_data['model'] ) && ! empty( $health_data['model'] ) ) {
			$ai_model_info['status'] = 'configured';
			$ai_model_info['current_model'] = $health_data['model'];
			$ai_model_info['model_display_name'] = $this->get_model_display_name_efficiently( $health_data['model'] );
			$ai_model_info['model_source'] = isset( $health_data['model_source'] ) ? $health_data['model_source'] : 'config';
			$model_found = true;
			
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				ai_faq_log_debug( 'AI FAQ Generator: Strategy 3 - Found model string: ' . $health_data['model'] );
			}
		}

		// Strategy 4: Nested ai_model_config object
		if ( ! $model_found && isset( $health_data['ai_model_config'] ) && is_array( $health_data['ai_model_config'] ) ) {
			$model_config = $health_data['ai_model_config'];
			
			if ( isset( $model_config['current_model'] ) && ! empty( $model_config['current_model'] ) ) {
				$ai_model_info['status'] = 'configured';
				$ai_model_info['current_model'] = $model_config['current_model'];
				$ai_model_info['model_display_name'] = isset( $model_config['display_name'] ) ? $model_config['display_name'] : $this->get_model_display_name_efficiently( $model_config['current_model'] );
				$ai_model_info['model_source'] = isset( $model_config['model_source'] ) ? $model_config['model_source'] : 'kv_config';
				$ai_model_info['config_timestamp'] = isset( $model_config['timestamp'] ) ? $model_config['timestamp'] : null;
				$model_found = true;
				
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					ai_faq_log_debug( 'AI FAQ Generator: Strategy 4 - Found ai_model_config.current_model: ' . $model_config['current_model'] );
				}
			}
		}

		// Strategy 5: Configuration object with ai_model
		if ( ! $model_found && isset( $health_data['configuration'] ) && is_array( $health_data['configuration'] ) ) {
			$config = $health_data['configuration'];
			
			if ( isset( $config['ai_model'] ) && ! empty( $config['ai_model'] ) ) {
				$ai_model_info['status'] = 'configured';
				$ai_model_info['current_model'] = $config['ai_model'];
				$ai_model_info['model_display_name'] = $this->get_model_display_name_efficiently( $config['ai_model'] );
				$ai_model_info['model_source'] = isset( $config['source'] ) ? $config['source'] : 'config';
				$ai_model_info['config_timestamp'] = isset( $config['last_updated'] ) ? $config['last_updated'] : null;
				$model_found = true;
				
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					ai_faq_log_debug( 'AI FAQ Generator: Strategy 5 - Found configuration.ai_model: ' . $config['ai_model'] );
				}
			}
		}

		// Strategy 6: Check config.ai_model (legacy format)
		if ( ! $model_found && isset( $health_data['config'] ) && is_array( $health_data['config'] ) && isset( $health_data['config']['ai_model'] ) ) {
			$ai_model_info['status'] = 'configured';
			$ai_model_info['current_model'] = $health_data['config']['ai_model'];
			$ai_model_info['model_display_name'] = $this->get_model_display_name_efficiently( $health_data['config']['ai_model'] );
			$ai_model_info['model_source'] = isset( $health_data['config']['model_source'] ) ? $health_data['config']['model_source'] : 'legacy_config';
			$model_found = true;
			
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				ai_faq_log_debug( 'AI FAQ Generator: Strategy 6 - Found config.ai_model: ' . $health_data['config']['ai_model'] );
			}
		}

		// If no model configuration found, log available keys for debugging
		if ( ! $model_found ) {
			$ai_model_info['status'] = 'not_configured';
			$ai_model_info['model_display_name'] = __( 'No Model Configured', '365i-ai-faq-generator' );
			
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				ai_faq_log_debug( 'AI FAQ Generator: No model found in health response for ' . $worker_type );
				ai_faq_log_debug( 'AI FAQ Generator: Available top-level keys: ' . implode( ', ', array_keys( $health_data ) ) );
				
				// Log a sample of the response for debugging
				$sample_response = array_slice( $health_data, 0, 10, true );
				ai_faq_log_debug( 'AI FAQ Generator: Sample response data: ' . wp_json_encode( $sample_response ) );
			}
		}

		// Add timing information from health response if available
		if ( isset( $health_data['timestamp'] ) ) {
			$ai_model_info['health_timestamp'] = $health_data['timestamp'];
		}
		if ( isset( $health_data['performance'] ) && is_array( $health_data['performance'] ) ) {
			$ai_model_info['performance'] = $health_data['performance'];
		}

		return $ai_model_info;
	}

	/**
		* Format model ID into human-readable display name.
		*
		* @since 2.3.0
		* @param string $model_id Model ID to format.
		* @return string Formatted display name.
		*/
	private function format_model_display_name( $model_id ) {
		if ( empty( $model_id ) ) {
			return __( 'Unknown Model', '365i-ai-faq-generator' );
		}

		// Handle @cf/ prefix
		if ( strpos( $model_id, '@cf/' ) === 0 ) {
			$model_id = substr( $model_id, 4 ); // Remove @cf/ prefix
		}

		// Split by slashes and get the last part (actual model name)
		$parts = explode( '/', $model_id );
		$model_name = end( $parts );

		// Convert to title case and replace hyphens/underscores with spaces
		$display_name = str_replace( array( '-', '_' ), ' ', $model_name );
		$display_name = ucwords( $display_name );

		// Handle common abbreviations and capitalize them properly
		$display_name = str_replace(
			array( 'Llama', 'Gpt', 'Ai', 'Ml', 'Nlp', 'Api', 'Fp8', 'It', 'Instruct' ),
			array( 'Llama', 'GPT', 'AI', 'ML', 'NLP', 'API', 'FP8', 'IT', 'Instruct' ),
			$display_name
		);

		return $display_name;
	}

	/**
	 * Format worker type into human-readable name.
	 *
	 * @since 2.5.0
	 * @param string $worker_type The worker type to format.
	 * @return string Formatted worker name.
	 */
	private function format_worker_name( $worker_type ) {
		$worker_names = array(
			'question_generator' => __( 'Question Generator', '365i-ai-faq-generator' ),
			'answer_generator' => __( 'Answer Generator', '365i-ai-faq-generator' ),
			'faq_enhancer' => __( 'FAQ Enhancer', '365i-ai-faq-generator' ),
			'seo_analyzer' => __( 'SEO Analyzer', '365i-ai-faq-generator' ),
			'url_faq_generator' => __( 'FAQ Extractor', '365i-ai-faq-generator' ),
		);

		return isset( $worker_names[ $worker_type ] ) ? $worker_names[ $worker_type ] : ucwords( str_replace( '_', ' ', $worker_type ) );
	}

	/**
	 * Validate Cloudflare model ID format.
	 *
	 * @since 2.5.1
	 * @param string $model_id The model ID to validate.
	 * @return bool True if valid Cloudflare model ID format, false otherwise.
	 */
	private function is_valid_cloudflare_model_id( $model_id ) {
		if ( empty( $model_id ) || ! is_string( $model_id ) ) {
			return false;
		}

		// Check for @cf/ prefix and proper format: @cf/provider/model-name
		if ( ! preg_match( '/^@cf\/[a-zA-Z0-9\-_]+\/[a-zA-Z0-9\-_.]+$/', $model_id ) ) {
			return false;
		}

		return true;
	}
}