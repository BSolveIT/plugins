<?php
/**
 * Admin workers management class for 365i AI FAQ Generator.
 * 
 * This class handles worker testing, health checking, and
 * status monitoring functionality.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Admin
 * @since 2.1.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin workers management class.
 * 
 * @since 2.1.0
 */
class AI_FAQ_Admin_Workers {

	/**
	 * Initialize the workers component.
	 *
	 * Set up hooks for worker configuration management.
	 *
	 * @since 2.1.0
	 */
	public function init() {
		// Add hooks for form submission handling
		add_action( 'admin_init', array( $this, 'handle_worker_configuration_form' ) );
		
		// Add AJAX handlers for worker testing
		add_action( 'wp_ajax_ai_faq_test_worker_connection', array( $this, 'handle_test_worker_connection_ajax' ) );
		add_action( 'wp_ajax_ai_faq_test_all_workers', array( $this, 'handle_test_all_workers_ajax' ) );
		add_action( 'wp_ajax_ai_faq_refresh_worker_status', array( $this, 'handle_refresh_worker_status_ajax' ) );
		
		// Handle success messages for worker configuration saves
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
	}

	/**
	 * Show admin notices for worker configuration saves.
	 *
	 * Displays success/error messages after form submissions.
	 *
	 * @since 2.5.1
	 */
	public function show_admin_notices() {
		// Only show on our plugin pages
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'ai-faq-generator-workers' ) === false ) {
			return;
		}

		// Check for settings updated message after redirect
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
			$options = get_option( 'ai_faq_gen_options', array() );
			$workers = isset( $options['workers'] ) ? $options['workers'] : array();
			
			// Count enabled workers with URLs
			$enabled_workers = array_filter( $workers, function( $config ) {
				return isset( $config['enabled'] ) && $config['enabled'] && ! empty( $config['url'] );
			} );
			
			$enabled_count = count( $enabled_workers );
			$total_count = count( $workers );
			
			$message = sprintf(
				/* translators: %1$d: Number of enabled workers, %2$d: Total workers */
				__( 'Worker configuration saved successfully! %1$d of %2$d workers are enabled and configured.', '365i-ai-faq-generator' ),
				$enabled_count,
				$total_count
			);
			
			// Add details about which workers are enabled
			if ( $enabled_count > 0 ) {
				$enabled_names = array();
				foreach ( $enabled_workers as $worker_key => $config ) {
					$enabled_names[] = ucfirst( str_replace( '_', ' ', $worker_key ) );
				}
				$message .= ' ' . sprintf(
					/* translators: %s: List of enabled workers */
					__( 'Enabled workers: %s', '365i-ai-faq-generator' ),
					implode( ', ', $enabled_names )
				);
			}
			
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
		}
	}

	/**
	 * Handle worker configuration form submission.
	 *
	 * Processes the worker configuration form from the Workers admin page,
	 * validates input data, and saves the configuration to WordPress options.
	 *
	 * @since 2.5.1
	 */
	public function handle_worker_configuration_form() {
		// Check if this is a worker configuration form submission
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ai_faq_gen_save_workers' ) ) {
			return;
		}

		// Check if user has permission to manage options
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', '365i-ai-faq-generator' ) );
		}

		// Check if workers data is present
		if ( ! isset( $_POST['workers'] ) || ! is_array( $_POST['workers'] ) ) {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid worker configuration data received.', '365i-ai-faq-generator' ) . '</p></div>';
			} );
			return;
		}

		// Get current options
		$options = get_option( 'ai_faq_gen_options', array() );
		
		// Initialize workers array if it doesn't exist
		if ( ! isset( $options['workers'] ) ) {
			$options['workers'] = array();
		}

		// Sanitize and validate worker configurations
		$worker_configs = $_POST['workers'];
		$sanitized_workers = array();
		$validation_errors = array();

		// Define valid worker types
		$valid_worker_types = array(
			'question_generator',
			'answer_generator',
			'faq_enhancer',
			'seo_analyzer',
			'faq_extractor',
			'topic_generator'
		);

		foreach ( $worker_configs as $worker_type => $worker_config ) {
			$worker_type = sanitize_key( $worker_type );
			
			// Validate worker type
			if ( ! in_array( $worker_type, $valid_worker_types, true ) ) {
				$validation_errors[] = sprintf(
					/* translators: %s: Invalid worker type */
					__( 'Invalid worker type: %s', '365i-ai-faq-generator' ),
					$worker_type
				);
				continue;
			}

			// Sanitize worker configuration
			$sanitized_config = array(
				'enabled' => isset( $worker_config['enabled'] ) && $worker_config['enabled'] === '1',
				'url' => '',
				'rate_limit' => 50, // Default rate limit
			);

			// Sanitize and validate URL
			if ( isset( $worker_config['url'] ) && ! empty( $worker_config['url'] ) ) {
				$url = sanitize_url( $worker_config['url'] );
				
				// Basic URL validation
				if ( filter_var( $url, FILTER_VALIDATE_URL ) && (
					strpos( $url, 'https://' ) === 0 ||
					strpos( $url, 'http://' ) === 0
				) ) {
					$sanitized_config['url'] = $url;
				} else {
					$validation_errors[] = sprintf(
						/* translators: %s: Worker type */
						__( 'Invalid URL provided for %s worker.', '365i-ai-faq-generator' ),
						ucfirst( str_replace( '_', ' ', $worker_type ) )
					);
					continue;
				}
			}

			// Sanitize rate limit if provided
			if ( isset( $worker_config['rate_limit'] ) ) {
				$rate_limit = absint( $worker_config['rate_limit'] );
				if ( $rate_limit > 0 && $rate_limit <= 1000 ) {
					$sanitized_config['rate_limit'] = $rate_limit;
				}
			}

			$sanitized_workers[ $worker_type ] = $sanitized_config;
		}

		// If there are validation errors, show them and return
		if ( ! empty( $validation_errors ) ) {
			add_action( 'admin_notices', function() use ( $validation_errors ) {
				echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Worker configuration errors:', '365i-ai-faq-generator' ) . '</strong></p><ul>';
				foreach ( $validation_errors as $error ) {
					echo '<li>' . esc_html( $error ) . '</li>';
				}
				echo '</ul></div>';
			} );
			return;
		}

		// Update worker configurations in options
		$options['workers'] = $sanitized_workers;
		
		// Also save individual worker URLs for backward compatibility
		foreach ( $sanitized_workers as $worker_type => $worker_config ) {
			$url_key = $worker_type . '_url';
			$options[ $url_key ] = $worker_config['url'];
			
			// Debug logging for URL saving
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( '[365i AI FAQ] SAVING URL: %s = %s', $url_key, $worker_config['url'] ) );
			}
		}

		// Debug log the complete options structure being saved
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$debug_options = array();
			foreach ( $sanitized_workers as $worker_type => $worker_config ) {
				$url_key = $worker_type . '_url';
				$debug_options[ $url_key ] = isset( $options[ $url_key ] ) ? $options[ $url_key ] : 'NOT_SET';
			}
			error_log( '[365i AI FAQ] OPTIONS BEFORE SAVE: ' . wp_json_encode( $debug_options ) );
		}

		// Save the updated options
		$update_result = update_option( 'ai_faq_gen_options', $options );

		if ( $update_result ) {
			// Clear any worker-related transients
			$this->clear_worker_caches();
			
			// Log successful configuration update
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$enabled_workers = array_filter( $sanitized_workers, function( $config ) {
					return $config['enabled'] && ! empty( $config['url'] );
				} );
				error_log( '[365i AI FAQ] Admin ' . wp_get_current_user()->user_login . ' updated worker configuration' );
			}
			
			// Redirect to prevent form resubmission and show success message
			$redirect_url = add_query_arg(
				array(
					'page' => 'ai-faq-generator-workers',
					'settings-updated' => 'true'
				),
				admin_url( 'admin.php' )
			);
			
			wp_safe_redirect( $redirect_url );
			exit;
		} else {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to save worker configuration. Please try again.', '365i-ai-faq-generator' ) . '</p></div>';
			} );
		}
	}

	/**
	 * Clear worker-related caches and transients.
	 *
	 * @since 2.5.1
	 */
	private function clear_worker_caches() {
		$cache_keys = array(
			'ai_faq_worker_status',
			'ai_faq_worker_health_check',
			'ai_faq_gen_ai_models_kv',
			'ai_faq_gen_models_cache',
		);

		foreach ( $cache_keys as $cache_key ) {
			delete_transient( $cache_key );
		}

		// Clear any paginated models cache
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_ai_faq_%'
			)
		);
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_timeout_ai_faq_%'
			)
		);
	}

	/**
	 * Handle AJAX request to test individual worker connection.
	 *
	 * @since 2.5.1
	 */
	public function handle_test_worker_connection_ajax() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_admin_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		$worker_type = isset( $_POST['worker_type'] ) ? sanitize_key( $_POST['worker_type'] ) : '';
		$worker_url = isset( $_POST['worker_url'] ) ? sanitize_url( $_POST['worker_url'] ) : '';

		if ( empty( $worker_type ) ) {
			wp_send_json_error( __( 'Worker type is required.', '365i-ai-faq-generator' ) );
		}

		// Test the worker health
		$health_result = $this->test_worker_health( $worker_type, $worker_url );

		if ( $health_result['status'] === 'healthy' ) {
			wp_send_json_success( array(
				'message' => sprintf(
					/* translators: %s: Worker type */
					__( '%s worker connection successful!', '365i-ai-faq-generator' ),
					ucfirst( str_replace( '_', ' ', $worker_type ) )
				),
				'health_data' => $health_result,
				'worker_info' => isset( $health_result['worker_info'] ) ? $health_result['worker_info'] : null,
			) );
		} else {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: Worker type */
					__( '%s worker connection failed.', '365i-ai-faq-generator' ),
					ucfirst( str_replace( '_', ' ', $worker_type ) )
				),
				'health_data' => $health_result,
				'error_details' => isset( $health_result['message'] ) ? $health_result['message'] : 'Unknown error',
			) );
		}
	}

	/**
	 * Handle AJAX request to test all workers.
	 *
	 * @since 2.5.1
	 */
	public function handle_test_all_workers_ajax() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_admin_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		$options = get_option( 'ai_faq_gen_options', array() );
		$workers = isset( $options['workers'] ) ? $options['workers'] : array();

		$test_results = array();
		$total_tests = 0;
		$successful_tests = 0;

		foreach ( $workers as $worker_type => $worker_config ) {
			if ( ! $worker_config['enabled'] || empty( $worker_config['url'] ) ) {
				continue;
			}

			$total_tests++;
			$health_result = $this->test_worker_health( $worker_type );

			$test_results[ $worker_type ] = array(
				'name' => ucfirst( str_replace( '_', ' ', $worker_type ) ),
				'status' => $health_result['status'],
				'message' => isset( $health_result['message'] ) ? $health_result['message'] : '',
				'response_time' => isset( $health_result['response_time'] ) ? $health_result['response_time'] : null,
				'health_data' => $health_result,
			);

			if ( $health_result['status'] === 'healthy' ) {
				$successful_tests++;
			}
		}

		$success_rate = $total_tests > 0 ? round( ( $successful_tests / $total_tests ) * 100, 1 ) : 0;

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %1$d: Successful tests, %2$d: Total tests, %3$s: Success percentage */
				__( 'Tested %1$d/%2$d workers successfully (%3$s%% success rate)', '365i-ai-faq-generator' ),
				$successful_tests,
				$total_tests,
				$success_rate
			),
			'test_results' => $test_results,
			'summary' => array(
				'total_tests' => $total_tests,
				'successful_tests' => $successful_tests,
				'success_rate' => $success_rate,
			),
		) );
	}

	/**
	 * Handle AJAX request to refresh worker status.
	 *
	 * @since 2.5.1
	 */
	public function handle_refresh_worker_status_ajax() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_admin_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Clear worker caches
		$this->clear_worker_caches();

		// Get fresh worker status
		$worker_status = $this->get_all_worker_status();

		wp_send_json_success( array(
			'message' => __( 'Worker status refreshed successfully.', '365i-ai-faq-generator' ),
			'worker_status' => $worker_status,
			'timestamp' => current_time( 'c' ),
		) );
	}

	/**
	 * Test worker health by using multiple strategies.
	 *
	 * Tries several approaches to verify worker connectivity:
	 * 1. First tries OPTIONS request which should work even for POST-only endpoints
	 * 2. Then tries GET to /health endpoint
	 * 3. Finally tries a minimal valid POST request
	 *
	 * Note: When browsers test connections to worker URLs, they automatically
	 * request favicon.ico from the domain root. This causes 404 errors in Cloudflare
	 * logs that can be safely ignored - they don't indicate actual connection problems.
	 * These errors are expected and normal browser behavior.
	 *
	 * @since 2.0.0
	 * @param string $worker_name Worker name to test.
	 * @param string $worker_url Optional. Custom URL to test, overrides saved configuration.
	 * @return array Health check results.
	 */
	public function test_worker_health( $worker_name, $worker_url = '' ) {
		// If custom URL is provided, use it; otherwise get from options
		if ( empty( $worker_url ) ) {
			$options = get_option( 'ai_faq_gen_options', array() );
			$workers = isset( $options['workers'] ) ? $options['workers'] : array();

			if ( ! isset( $workers[ $worker_name ] ) ) {
				return array(
					'status' => 'error',
					'message' => __( 'Worker not configured', '365i-ai-faq-generator' ),
					'response_time' => null,
				);
			}

			$worker_config = $workers[ $worker_name ];
			$worker_url = $worker_config['url'];
		}

		// Clean base URL - VERY IMPORTANT - log exact URL for debugging
		$base_url = rtrim( $worker_url, '/' );
		error_log( sprintf( '[365i AI FAQ] TESTING WORKER: %s at URL: %s (worker type: %s)', $worker_name, $base_url, $worker_name ) );
		error_log( sprintf( '[365i AI FAQ] TESTING METHOD: Using multi-strategy approach (OPTIONS, GET, POST)' ) );
		
		// Debug: Log detailed request information
		error_log( sprintf(
			'[365i AI FAQ] DEBUGGING DETAILS - Original worker URL: %s, Cleaned base URL: %s, Worker name: %s, Normalized worker: %s',
			$worker_url,
			$base_url,
			$worker_name,
			str_replace( '-', '_', $worker_name )
		) );
		
		// IMPORTANT: Strategy 1: Try health endpoint with GET first (dedicated testing endpoint)
		// This should be a simple GET to /health with no data/parameters
		$health_url = $base_url . '/health';
		
		// Debug: Log the exact health URL we're constructing
		error_log( sprintf( '[365i AI FAQ] DEBUG HEALTH URL: Original base=%s, Health URL=%s', $base_url, $health_url ) );
		error_log( sprintf( '[365i AI FAQ] PRIORITY STRATEGY: Testing health endpoint with simple GET: %s', $health_url ) );
		$result = $this->test_get_request( $health_url, $worker_name );
		
		// Log detailed information about the health endpoint test result
		error_log( sprintf( '[365i AI FAQ] Health endpoint test result status: %s', $result['status'] ) );
		
		// If health endpoint test succeeds, return immediately - don't try other methods
		if ( $result['status'] === 'healthy' ) {
			error_log( '[365i AI FAQ] Health endpoint test SUCCESSFUL - returning result without trying other methods' );
			return $result;
		}
		
		error_log( sprintf( '[365i AI FAQ] Health endpoint test FAILED with status: %s - Error: %s',
			$result['status'],
			isset( $result['message'] ) ? $result['message'] : 'No error message'
		));
		
		// Only try the other methods if health endpoint test fails
		
		// Strategy 2: Try OPTIONS request (CORS preflight should work for any endpoint)
		error_log( sprintf( '[365i AI FAQ] Health endpoint failed, trying OPTIONS request to: %s', $base_url ) );
		$result = $this->test_options_request( $base_url, $worker_name );
		if ( $result['status'] === 'healthy' ) {
			error_log( '[365i AI FAQ] OPTIONS request SUCCESSFUL' );
			return $result;
		}
		
		// Strategy 3: Try POST with minimal valid payload as last resort
		error_log( sprintf( '[365i AI FAQ] OPTIONS failed, trying POST request to base URL: %s', $base_url ) );
		return $this->test_post_request( $base_url, $worker_name );
	}

	/**
	 * Test with OPTIONS request (CORS preflight).
	 *
	 * This should work with any endpoint that properly implements CORS.
	 *
	 * @since 2.1.0
	 * @param string $url URL to test.
	 * @param string $worker_name Worker name.
	 * @return array Health check results.
	 */
	/**
	 * Test with OPTIONS request (CORS preflight).
	 *
	 * This method tests CORS configuration which is critical for browser-based API access.
	 * Properly configured workers should respond to OPTIONS requests with appropriate CORS headers.
	 *
	 * @since 2.1.0
	 * @param string $url URL to test.
	 * @param string $worker_name Worker name.
	 * @return array Health check results.
	 */
	private function test_options_request( $url, $worker_name ) {
		// Ensure URL is properly formatted
		$url = rtrim( $url, '/' );
		
		error_log( sprintf( '[365i AI FAQ] Testing OPTIONS request to: %s for worker: %s', $url, $worker_name ) );
		
		$start_time = microtime( true );

		// Prepare headers for a standard CORS preflight request
		$headers = array(
			'User-Agent' => 'WordPress/365i-AI-FAQ-Generator-Health-Check',
			'Origin' => home_url(),
			'Access-Control-Request-Method' => 'POST',
			'Access-Control-Request-Headers' => 'Content-Type, X-Request-ID',
		);
		
		$response = wp_remote_request( $url, array(
			'method' => 'OPTIONS',
			'timeout' => 15, // Increased timeout for slower connections
			'headers' => $headers,
		) );

		$response_time = round( ( microtime( true ) - $start_time ) * 1000, 2 ); // Convert to milliseconds.

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			error_log( sprintf( '[365i AI FAQ] OPTIONS request WP error: %s', $error_message ) );
			return array(
				'status' => 'error',
				'message' => $error_message,
				'response_time' => $response_time,
				'request_url' => $url,
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_headers = wp_remote_retrieve_headers( $response );
		$response_body = wp_remote_retrieve_body( $response );
		
		error_log( sprintf( '[365i AI FAQ] OPTIONS response code: %d', $response_code ) );
		error_log( sprintf( '[365i AI FAQ] OPTIONS response headers: %s', json_encode( $response_headers ) ) );
		
		// Log empty response body instead of null
		if ( empty( $response_body ) ) {
			error_log( '[365i AI FAQ] OPTIONS response body: [empty body - this is normal for OPTIONS]' );
		} else {
			error_log( sprintf( '[365i AI FAQ] OPTIONS response body: %s', substr( $response_body, 0, 100 ) ) );
		}
		
		// OPTIONS requests typically return 200, 204, or 2xx with CORS headers
		if ( $response_code >= 200 && $response_code < 300 ) {
			// Check for CORS headers - normalize header keys for case-insensitive comparison
			$normalized_headers = array();
			foreach ( $response_headers as $key => $value ) {
				$normalized_headers[ strtolower( $key ) ] = $value;
			}
			
			// Check for various CORS header patterns
			$cors_headers_found = array();
			$required_cors_headers = array(
				'access-control-allow-origin',
				'access-control-allow-methods',
				'access-control-allow-headers',
			);
			
			foreach ( $required_cors_headers as $header ) {
				if ( isset( $normalized_headers[ $header ] ) ) {
					$cors_headers_found[] = $header;
				}
			}
			
			// If we have at least the Allow-Origin header, it's likely a valid CORS setup
			if ( in_array( 'access-control-allow-origin', $cors_headers_found ) ) {
				return array(
					'status' => 'healthy',
					'data' => array(
						'status' => 'healthy',
						'service' => $worker_name,
						'method' => 'OPTIONS',
						'timestamp' => current_time( 'c' ),
						'note' => sprintf(
							'CORS preflight successful. Headers found: %s',
							implode( ', ', $cors_headers_found )
						),
						'headers' => $cors_headers_found,
					),
					'response_time' => $response_time,
				);
			}
		}
		
		// Some APIs return 405 Method Not Allowed but still work with POST
		if ( $response_code == 405 || $response_code == 403 ) {
			error_log( '[365i AI FAQ] OPTIONS returned 405/403 - this is okay for some APIs that do not support OPTIONS' );
			return array(
				'status' => 'warning',
				'data' => array(
					'status' => 'warning',
					'service' => $worker_name,
					'method' => 'OPTIONS',
					'timestamp' => current_time( 'c' ),
					'note' => sprintf(
						'Worker returned %d for OPTIONS. This may be normal for some APIs that do not explicitly support CORS preflight.',
						$response_code
					),
				),
				'response_time' => $response_time,
			);
		}
		
		// Not a successful CORS response
		return array(
			'status' => 'error',
			'http_code' => $response_code,
			'response_time' => $response_time,
			'message' => sprintf(
				/* translators: %1$d: HTTP status code, %2$s: Worker name */
				__( 'OPTIONS request failed with HTTP %1$d for worker %2$s', '365i-ai-faq-generator' ),
				$response_code,
				$worker_name
			),
			'request_url' => $url,
			'response_preview' => substr( $response_body, 0, 100 ),
		);
	}

	/**
	 * Test with GET request to specified URL.
	 *
	 * Primarily used for health endpoint checks. Enhanced to handle various worker health
	 * response formats and provide detailed logging for diagnostics.
	 *
	 * @since 2.1.0
	 * @param string $url URL to test.
	 * @param string $worker_name Worker name.
	 * @return array Health check results.
	 */
	private function test_get_request( $url, $worker_name ) {
		// URL should already have the endpoint path (e.g., /health) appended by the caller
		$url = rtrim( $url, '/' );
		
		// Critical debugging information
		error_log( sprintf( '[365i AI FAQ] Testing GET request to URL: %s', $url ) );
		error_log( sprintf( '[365i AI FAQ] This should be a simple GET request with NO parameters' ) );
		
		$start_time = microtime( true );

		// Use a minimal request with only essential headers
		// IMPORTANT: We specifically want a clean GET request with no body/payload
		$response = wp_remote_get( $url, array(
			'timeout' => 15, // Increased timeout for slower connections
			'method' => 'GET', // Explicitly set method to GET
			'headers' => array(
				'User-Agent' => 'WordPress/365i-AI-FAQ-Generator-Health-Check',
				'Accept' => 'application/json',
			),
			// No body parameter - we want a clean GET request
		) );

		$response_time = round( ( microtime( true ) - $start_time ) * 1000, 2 ); // Convert to milliseconds.

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			error_log( sprintf( '[365i AI FAQ] GET request WP error: %s', $error_message ) );
			return array(
				'status' => 'error',
				'message' => $error_message,
				'response_time' => $response_time,
				'request_url' => $url,
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_headers = wp_remote_retrieve_headers( $response );

		error_log( sprintf( '[365i AI FAQ] GET response code: %d', $response_code ) );
		error_log( sprintf( '[365i AI FAQ] GET response body: %s', substr( $response_body, 0, 200 ) ) );
		error_log( sprintf( '[365i AI FAQ] GET response headers: %s', json_encode( $response_headers ) ) );

		// Handle success codes (200-299)
		if ( $response_code >= 200 && $response_code < 300 ) {
			$health_data = json_decode( $response_body, true );
			
			// Handle standardized health response format
			if ( is_array( $health_data ) ) {
				// Check if status is explicitly marked in the response (new standardized format)
				if ( isset( $health_data['status'] ) ) {
					$is_healthy = (
						$health_data['status'] === 'healthy' ||
						$health_data['status'] === 'ok' ||
						$health_data['status'] === 'online' ||
						$health_data['status'] === true
					);
					
					if ( $is_healthy ) {
						// Extract standardized information from health response
						$standardized_data = $this->parse_standardized_health_response( $health_data, $worker_name );
						
						return array(
							'status' => 'healthy',
							'data' => $standardized_data,
							'response_time' => $response_time,
							'method' => 'GET',
							'worker_info' => $this->extract_worker_summary( $standardized_data ),
						);
					}
				}
				// Fallback: If no explicit status but response is success and has data, consider it healthy
				else if ( !empty( $health_data ) ) {
					// Parse as legacy format but extract what we can
					$legacy_data = $this->parse_legacy_health_response( $health_data, $worker_name );
					
					return array(
						'status' => 'healthy',
						'data' => $legacy_data,
						'response_time' => $response_time,
						'method' => 'GET',
						'worker_info' => $this->extract_worker_summary( $legacy_data ),
					);
				}
			}
			// Even empty responses with 200 status code indicate the endpoint exists
			else {
				$empty_response_data = array(
					'status' => 'healthy',
					'worker' => $worker_name,
					'timestamp' => current_time( 'c' ),
					'method' => 'GET',
					'note' => 'Worker responded but with empty or non-JSON response',
					'current_model' => null,
					'model_source' => 'unknown',
					'worker_type' => $worker_name,
				);
				
				return array(
					'status' => 'healthy',
					'data' => $empty_response_data,
					'response_time' => $response_time,
					'method' => 'GET',
					'worker_info' => $this->extract_worker_summary( $empty_response_data ),
				);
			}
		}

		// For unsuccessful responses, provide detailed error information
		return array(
			'status' => 'error',
			'http_code' => $response_code,
			'response_time' => $response_time,
			'message' => sprintf(
				/* translators: %1$d: HTTP status code, %2$s: Worker name */
				__( 'Health check failed with HTTP %1$d for worker %2$s', '365i-ai-faq-generator' ),
				$response_code,
				$worker_name
			),
			'request_url' => $url,
			'response_preview' => substr( $response_body, 0, 100 ),
		);
	}

	/**
	 * Test with POST request with minimal valid payload.
	 *
	 * @since 2.1.0
	 * @param string $url URL to test.
	 * @param string $worker_name Worker name.
	 * @return array Health check results.
	 */
	private function test_post_request( $url, $worker_name ) {
		// Create payload based on worker type
		$payload = $this->get_test_payload_for_worker( $worker_name );
		
		// Normalize worker name for consistent handling
		$normalized_worker = str_replace( '-', '_', $worker_name );
		
		// Log detailed testing information
		error_log( sprintf( '[365i AI FAQ] Testing POST request to: %s for worker: %s (normalized: %s)', $url, $worker_name, $normalized_worker ) );
		error_log( sprintf( '[365i AI FAQ] POST payload: %s', wp_json_encode( $payload ) ) );
		
		$start_time = microtime( true );

		// Ensure headers are properly set for the worker
		$headers = array(
			'User-Agent' => 'WordPress/365i-AI-FAQ-Generator-Health-Check',
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
			'Origin' => home_url(),
		);
		
		// Add additional headers if needed for specific workers
		if ( strpos( $worker_name, 'cloudflare' ) !== false ) {
			$options = get_option( 'ai_faq_gen_options', array() );
			if ( ! empty( $options['cloudflare_api_token'] ) ) {
				$headers['Authorization'] = 'Bearer ' . $options['cloudflare_api_token'];
			}
		}
		
		$response = wp_remote_post( $url, array(
			'timeout' => 15, // Increased timeout for slower workers
			'headers' => $headers,
			'body' => wp_json_encode( $payload ),
		) );

		$response_time = round( ( microtime( true ) - $start_time ) * 1000, 2 ); // Convert to milliseconds.

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			error_log( sprintf( '[365i AI FAQ] POST request WP error: %s', $error_message ) );
			return array(
				'status' => 'error',
				'message' => $error_message,
				'response_time' => $response_time,
				'request_url' => $url,
				'request_payload' => $payload,
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_headers = wp_remote_retrieve_headers( $response );
		
		error_log( sprintf( '[365i AI FAQ] POST response code: %d', $response_code ) );
		error_log( sprintf( '[365i AI FAQ] POST response body: %s', substr( $response_body, 0, 200 ) ) );
		error_log( sprintf( '[365i AI FAQ] POST response headers: %s', json_encode( $response_headers ) ) );

		// POST requests should return 200-299 for success
		if ( $response_code >= 200 && $response_code < 300 ) {
			$response_data = json_decode( $response_body, true );
			
			// Check if the response indicates success specifically
			$is_success = false;
			if ( is_array( $response_data ) ) {
				// Different workers may indicate success differently
				if ( isset( $response_data['success'] ) && $response_data['success'] === true ) {
					$is_success = true;
				} elseif ( isset( $response_data['status'] ) && $response_data['status'] === 'healthy' ) {
					$is_success = true;
				} elseif ( isset( $response_data['mode'] ) ) {
					// If response contains mode, it's likely valid
					$is_success = true;
				}
			}
			
			if ( $is_success ) {
				return array(
					'status' => 'healthy',
					'data' => array(
						'status' => 'healthy',
						'service' => $worker_name,
						'method' => 'POST',
						'timestamp' => current_time( 'c' ),
						'note' => 'POST request successful',
						'response' => $response_data,
					),
					'response_time' => $response_time,
				);
			}
		}
		
		// Any response from the worker is a good sign that it exists
		if ( $response_code < 500 ) {
			$note = sprintf( 'Worker exists but returned %s code %d',
				( $response_code >= 200 && $response_code < 300 ) ? 'success' : 'error',
				$response_code
			);
			
			// Attempt to parse the response body for more details
			$response_data = json_decode( $response_body, true );
			if ( is_array( $response_data ) && isset( $response_data['error'] ) ) {
				$note .= ': ' . $response_data['error'];
			}
			
			return array(
				'status' => 'healthy',
				'data' => array(
					'status' => 'healthy',
					'service' => $worker_name,
					'method' => 'POST',
					'timestamp' => current_time( 'c' ),
					'note' => $note,
					'response_code' => $response_code,
					'response_preview' => substr( $response_body, 0, 100 ),
				),
				'response_time' => $response_time,
			);
		}

		return array(
			'status' => 'unhealthy',
			'http_code' => $response_code,
			'response_time' => $response_time,
			'message' => sprintf(
				/* translators: %d: HTTP response code */
				__( 'Worker responded with HTTP %d', '365i-ai-faq-generator' ),
				$response_code
			),
			'request_url' => $url,
			'request_payload' => $payload,
		);
	}
	
	/**
	 * Get test payload for specific worker.
	 *
	 * Different workers expect different payloads, so we need to create
	 * a minimal valid payload for each worker type.
	 *
	 * @since 2.1.0
	 * @param string $worker_name Worker name.
	 * @return array Test payload.
	 */
	private function get_test_payload_for_worker( $worker_name ) {
		// Base minimal test data for all workers
		$test_data = array(
			'question' => 'What is AI FAQ Generator?',
			'answer' => 'AI FAQ Generator is a WordPress plugin that uses AI to generate FAQ content.',
			'pageUrl' => home_url(),
		);
		
		// Customize based on worker type
		switch ( $worker_name ) {
			case 'question_generator':
				return array(
					'question' => 'What is AI FAQ Generator?',
					'mode' => 'generate',
				);
				
			case 'answer_generator':
				return array(
					'question' => 'What is AI FAQ Generator?',
					'mode' => 'generate',
				);
				
			case 'faq_enhancer':
				return array(
					'faq' => array(
						array(
							'question' => 'What is AI FAQ Generator?',
							'answer' => 'A WordPress plugin for generating FAQs.',
						),
					),
					'mode' => 'enhance',
				);
				
			case 'seo_analyzer':
				return array(
					'content' => 'What is AI FAQ Generator?',
					'mode' => 'analyze',
				);
				
			case 'faq_extractor':
				return array(
					'url' => home_url(),
					'mode' => 'extract',
				);
				
			case 'topic_generator':
				return array(
					'input' => 'WordPress AI FAQ Generator',
					'mode' => 'generate',
				);
				
			default:
				// Generic test data for unknown worker types
				return array(
					'question' => 'What is AI FAQ Generator?',
					'mode' => 'generate',
				);
		}
	}

	/**
	 * Get status information for all workers.
	 * 
	 * @since 2.1.0
	 * @return array Status information for all workers.
	 */
	public function get_all_worker_status() {
		// Get workers instance and status.
		$workers_api = new AI_FAQ_Workers();
		$worker_status = $workers_api->get_worker_status();

		// Add health check data for each worker.
		foreach ( $worker_status as $worker_name => &$status ) {
			$health_result = $this->test_worker_health( $worker_name );
			$status['health'] = $health_result;
		}

		// Get violation counts.
		$violations = get_option( 'ai_faq_violations_log', array() );
		$recent_violations = array_filter( $violations, function( $violation ) {
			return $violation['timestamp'] > ( time() - DAY_IN_SECONDS );
		} );

		$status_data = array(
			'workers' => $worker_status,
			'violations' => array(
				'total_24h' => count( $recent_violations ),
				'unique_ips' => count( array_unique( array_column( $recent_violations, 'ip' ) ) ),
			),
			'blocked_ips_count' => count( get_option( 'ai_faq_blocked_ips', array() ) ),
		);

		return $status_data;
	}

	/**
	 * Test Cloudflare API connection.
	 * 
	 * @since 2.1.0
	 * @param string $account_id Cloudflare account ID.
	 * @param string $api_token Cloudflare API token.
	 * @return array Test results.
	 */
	public function test_api_connection( $account_id, $api_token ) {
		// Test API connection by calling Cloudflare API.
		$test_url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/workers/scripts";
		
		$response = wp_remote_get( $test_url, array(
			'timeout' => 10,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type' => 'application/json',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => sprintf( 
					/* translators: %s: Error message */
					__( 'API connection failed: %s', '365i-ai-faq-generator' ), 
					$response->get_error_message() 
				),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 === $response_code ) {
			$data = json_decode( $response_body, true );
			$worker_count = isset( $data['result'] ) ? count( $data['result'] ) : 0;
			
			return array(
				'success' => true,
				'message' => sprintf( 
					/* translators: %d: Number of workers found */
					__( 'Connection successful! Found %d workers in account.', '365i-ai-faq-generator' ), 
					$worker_count 
				),
				'worker_count' => $worker_count,
			);
		} else {
			$error_data = json_decode( $response_body, true );
			$error_message = isset( $error_data['errors'][0]['message'] ) 
				? $error_data['errors'][0]['message'] 
				: __( 'Unknown API error', '365i-ai-faq-generator' );

			return array(
				'success' => false,
				'message' => sprintf( 
					/* translators: %1$d: HTTP status code, %2$s: Error message */
					__( 'API connection failed (HTTP %1$d): %2$s', '365i-ai-faq-generator' ), 
					$response_code, 
					$error_message 
				),
			);
		}
	}

	/**
	 * Extract AI model information from worker health response.
	 *
	 * Parses the health response to extract current AI model configuration
	 * including model ID, source, and worker type.
	 *
	 * @since 2.3.0
	 * @param array $health_data Health response data from worker.
	 * @return array AI model information.
	 */
	private function extract_ai_model_info( $health_data ) {
		$ai_model_info = array(
			'current_model' => null,
			'model_source' => 'unknown',
			'worker_type' => null,
		);

		if ( ! is_array( $health_data ) ) {
			return $ai_model_info;
		}

		// Extract current AI model
		if ( isset( $health_data['current_model'] ) ) {
			$ai_model_info['current_model'] = sanitize_text_field( $health_data['current_model'] );
		} elseif ( isset( $health_data['ai_model'] ) ) {
			$ai_model_info['current_model'] = sanitize_text_field( $health_data['ai_model'] );
		} elseif ( isset( $health_data['model'] ) ) {
			$ai_model_info['current_model'] = sanitize_text_field( $health_data['model'] );
		}

		// Extract model source
		if ( isset( $health_data['model_source'] ) ) {
			$valid_sources = array( 'kv_config', 'env_fallback', 'hardcoded_default' );
			if ( in_array( $health_data['model_source'], $valid_sources, true ) ) {
				$ai_model_info['model_source'] = $health_data['model_source'];
			}
		}

		// Extract worker type
		if ( isset( $health_data['worker_type'] ) ) {
			$ai_model_info['worker_type'] = sanitize_text_field( $health_data['worker_type'] );
		} elseif ( isset( $health_data['service'] ) ) {
			$ai_model_info['worker_type'] = sanitize_text_field( $health_data['service'] );
		}

		// Add model display name if available
		if ( ! empty( $ai_model_info['current_model'] ) ) {
			$ai_model_info['model_display_name'] = $this->get_model_display_name( $ai_model_info['current_model'] );
		}

		return $ai_model_info;
	}

	/**
	 * Get human-readable model display name.
	 *
	 * Converts technical model IDs to user-friendly display names.
	 *
	 * @since 2.3.0
	 * @param string $model_id The model ID.
	 * @return string Human-readable model name.
	 */
	private function get_model_display_name( $model_id ) {
		$model_names = array(
			'@cf/meta/llama-3.1-8b-instruct' => 'Llama 3.1 8B Instruct',
			'@cf/meta/llama-3.3-70b-instruct-fp8-fast' => 'Llama 3.3 70B Instruct (Fast)',
			'@cf/meta/llama-4-scout-17b-16e-instruct' => 'Llama 4 Scout 17B Instruct',
			'@cf/mistralai/mistral-small-3.1-24b-instruct' => 'Mistral Small 3.1 24B',
			'@cf/qwen/qwen2.5-coder-32b-instruct' => 'Qwen 2.5 Coder 32B',
			'@cf/google/gemma-3-12b-it' => 'Gemma 3 12B IT',
			'@cf/deepseek-ai/deepseek-r1-distill-qwen-32b' => 'DeepSeek R1 Distill 32B',
		);

		return isset( $model_names[ $model_id ] ) ? $model_names[ $model_id ] : $model_id;
	}

	/**
	 * Parse standardized health response format.
	 *
	 * Processes the new standardized health endpoint response format from workers.
	 * Expected format includes: worker, status, timestamp, version, capabilities,
	 * current_model, model_source, worker_type, rate_limiting, cache_status.
	 *
	 * @since 2.4.0
	 * @param array  $health_data Raw health response data from worker.
	 * @param string $worker_name Worker name for fallback identification.
	 * @return array Parsed and standardized health data.
	 */
	private function parse_standardized_health_response( $health_data, $worker_name ) {
		// Start with the raw response and enhance it
		$standardized_data = array_merge( $health_data, array(
			'worker' => isset( $health_data['worker'] ) ? sanitize_text_field( $health_data['worker'] ) : $worker_name,
			'status' => isset( $health_data['status'] ) ? sanitize_text_field( $health_data['status'] ) : 'healthy',
			'timestamp' => isset( $health_data['timestamp'] ) ? sanitize_text_field( $health_data['timestamp'] ) : current_time( 'c' ),
			'version' => isset( $health_data['version'] ) ? sanitize_text_field( $health_data['version'] ) : 'unknown',
			'current_model' => isset( $health_data['current_model'] ) ? sanitize_text_field( $health_data['current_model'] ) : null,
			'model_source' => isset( $health_data['model_source'] ) ? sanitize_text_field( $health_data['model_source'] ) : 'unknown',
			'worker_type' => isset( $health_data['worker_type'] ) ? sanitize_text_field( $health_data['worker_type'] ) : $worker_name,
			'cache_status' => isset( $health_data['cache_status'] ) ? sanitize_text_field( $health_data['cache_status'] ) : 'unknown',
		) );

		// Parse capabilities array if present
		if ( isset( $health_data['capabilities'] ) && is_array( $health_data['capabilities'] ) ) {
			$standardized_data['capabilities'] = array_map( 'sanitize_text_field', $health_data['capabilities'] );
		} else {
			$standardized_data['capabilities'] = array();
		}

		// Parse rate limiting object if present
		if ( isset( $health_data['rate_limiting'] ) && is_array( $health_data['rate_limiting'] ) ) {
			$standardized_data['rate_limiting'] = array(
				'enabled' => isset( $health_data['rate_limiting']['enabled'] ) ? (bool) $health_data['rate_limiting']['enabled'] : false,
				'enhanced' => isset( $health_data['rate_limiting']['enhanced'] ) ? (bool) $health_data['rate_limiting']['enhanced'] : false,
			);
		} else {
			$standardized_data['rate_limiting'] = array(
				'enabled' => false,
				'enhanced' => false,
			);
		}

		// Add model display name for UI
		if ( ! empty( $standardized_data['current_model'] ) ) {
			$standardized_data['model_display_name'] = $this->get_model_display_name( $standardized_data['current_model'] );
		}

		// Add method for tracking
		$standardized_data['test_method'] = 'GET /health (standardized)';

		return $standardized_data;
	}

	/**
	 * Parse legacy health response format.
	 *
	 * Handles older or non-standardized health response formats for backward compatibility.
	 * Attempts to extract similar information to the standardized format.
	 *
	 * @since 2.4.0
	 * @param array  $health_data Raw health response data from worker.
	 * @param string $worker_name Worker name for identification.
	 * @return array Parsed health data in standardized-like format.
	 */
	private function parse_legacy_health_response( $health_data, $worker_name ) {
		// Extract AI model information using existing method
		$ai_model_info = $this->extract_ai_model_info( $health_data );

		// Build standardized-like structure from legacy data
		$legacy_data = array(
			'worker' => $worker_name,
			'status' => 'healthy',
			'timestamp' => current_time( 'c' ),
			'version' => isset( $health_data['version'] ) ? sanitize_text_field( $health_data['version'] ) : 'unknown',
			'current_model' => $ai_model_info['current_model'],
			'model_source' => $ai_model_info['model_source'],
			'worker_type' => $ai_model_info['worker_type'] ?: $worker_name,
			'cache_status' => 'unknown',
			'capabilities' => array(),
			'rate_limiting' => array(
				'enabled' => false,
				'enhanced' => false,
			),
			'test_method' => 'GET /health (legacy)',
		);

		// Try to extract any additional data from the original response
		foreach ( $health_data as $key => $value ) {
			if ( ! isset( $legacy_data[ $key ] ) && is_scalar( $value ) ) {
				$legacy_data[ $key ] = sanitize_text_field( $value );
			}
		}

		// Add model display name if we have a model
		if ( ! empty( $legacy_data['current_model'] ) ) {
			$legacy_data['model_display_name'] = $this->get_model_display_name( $legacy_data['current_model'] );
		}

		return $legacy_data;
	}

	/**
	 * Extract worker summary information for notifications and displays.
	 *
	 * Creates a concise summary of worker information for use in UI notifications,
	 * dashboard displays, and status indicators.
	 *
	 * @since 2.4.0
	 * @param array $worker_data Complete worker health data.
	 * @return array Worker summary information.
	 */
	private function extract_worker_summary( $worker_data ) {
		$summary = array(
			'name' => isset( $worker_data['worker'] ) ? $worker_data['worker'] : 'Unknown Worker',
			'status' => isset( $worker_data['status'] ) ? $worker_data['status'] : 'unknown',
			'model' => isset( $worker_data['current_model'] ) ? $worker_data['current_model'] : null,
			'model_display' => isset( $worker_data['model_display_name'] ) ? $worker_data['model_display_name'] : null,
			'version' => isset( $worker_data['version'] ) ? $worker_data['version'] : null,
			'worker_type' => isset( $worker_data['worker_type'] ) ? $worker_data['worker_type'] : null,
			'cache_enabled' => isset( $worker_data['cache_status'] ) && $worker_data['cache_status'] === 'active',
			'rate_limiting_enabled' => isset( $worker_data['rate_limiting']['enabled'] ) ? $worker_data['rate_limiting']['enabled'] : false,
			'capabilities_count' => isset( $worker_data['capabilities'] ) && is_array( $worker_data['capabilities'] ) ? count( $worker_data['capabilities'] ) : 0,
		);

		// Create a status message
		$status_parts = array();
		if ( $summary['model_display'] ) {
			$status_parts[] = sprintf( 'Model: %s', $summary['model_display'] );
		}
		if ( $summary['version'] ) {
			$status_parts[] = sprintf( 'v%s', $summary['version'] );
		}
		if ( $summary['cache_enabled'] ) {
			$status_parts[] = 'Cache: Active';
		}
		if ( $summary['rate_limiting_enabled'] ) {
			$status_parts[] = 'Rate Limiting: Enabled';
		}

		$summary['status_message'] = implode( ' | ', $status_parts );

		return $summary;
	}

	/**
	 * Run worker tests.
	 *
	 * @since 2.1.0
	 * @return array Test results.
	 */
	public function run_worker_tests() {
		// Path to Node.js script
		$script_path = AI_FAQ_GEN_DIR . 'tools/test-workers.js';
		
		// Check if the script exists
		if ( ! file_exists( $script_path ) ) {
			return array(
				'success' => false,
				'message' => __( 'Test script not found.', '365i-ai-faq-generator' ),
			);
		}
		
		// Execute the test script
		$node_path = 'node'; // Assuming Node.js is in the system PATH
		$command = escapeshellcmd( "$node_path $script_path --iterations=10 --delay=500" );
		
		// Execute command and capture output
		$output = array();
		$return_var = 0;
		exec( $command, $output, $return_var );
		
		if ( $return_var !== 0 ) {
			return array(
				'success' => false,
				'message' => __( 'Test execution failed.', '365i-ai-faq-generator' ),
				'output' => $output,
				'code' => $return_var,
			);
		}
		
		// Import test results into WordPress
		$import_script_path = AI_FAQ_GEN_DIR . 'tools/import-test-data.php';
		$import_results = null;
		
		if ( file_exists( $import_script_path ) ) {
			include_once $import_script_path;
			
			// Create an instance of the importer class and run it
			$importer = new AI_FAQ_Test_Data_Importer();
			$import_results = $importer->run( true ); // Run in silent mode
			
			return array(
				'success' => true,
				'message' => __( 'Tests completed and data imported successfully.', '365i-ai-faq-generator' ),
				'test_output' => $output,
				'import_results' => $import_results,
			);
		} else {
			return array(
				'success' => true,
				'message' => __( 'Tests completed but import script not found.', '365i-ai-faq-generator' ),
				'test_output' => $output,
			);
		}
	}
}