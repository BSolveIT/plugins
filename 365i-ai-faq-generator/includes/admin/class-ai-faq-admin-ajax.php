<?php
/**
 * Admin AJAX handler class for 365i AI FAQ Generator.
 * 
 * This class processes all AJAX requests for admin functionality,
 * including worker testing, settings management, analytics, and security.
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
 * Admin AJAX handler class.
 * 
 * @since 2.1.0
 */
class AI_FAQ_Admin_Ajax {

	/**
	 * Workers component instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Admin_Workers
	 */
	private $workers;
	
	/**
	 * Initialize the AJAX component.
	 * 
	 * Set up hooks for AJAX request handling.
	 * 
	 * @since 2.1.0
	 */
	public function init() {
		// Get workers instance for worker-related operations
		$this->workers = new AI_FAQ_Admin_Workers();
		
		// Register AJAX handlers
		add_action( 'wp_ajax_ai_faq_test_worker', array( $this, 'ajax_test_worker' ) );
		add_action( 'wp_ajax_ai_faq_reset_worker_usage', array( $this, 'ajax_reset_worker_usage' ) );
		add_action( 'wp_ajax_ai_faq_get_worker_status', array( $this, 'ajax_get_worker_status' ) );
		add_action( 'wp_ajax_ai_faq_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_ai_faq_save_workers', array( $this, 'ajax_save_workers' ) );
		add_action( 'wp_ajax_ai_faq_get_violations', array( $this, 'ajax_get_violations' ) );
		add_action( 'wp_ajax_ai_faq_block_ip', array( $this, 'ajax_block_ip' ) );
		add_action( 'wp_ajax_ai_faq_unblock_ip', array( $this, 'ajax_unblock_ip' ) );
		add_action( 'wp_ajax_ai_faq_get_analytics', array( $this, 'ajax_get_analytics' ) );
		add_action( 'wp_ajax_ai_faq_test_api_connection', array( $this, 'ajax_test_api_connection' ) );
		add_action( 'wp_ajax_ai_faq_import_settings', array( $this, 'ajax_import_settings' ) );
		add_action( 'wp_ajax_ai_faq_reset_settings', array( $this, 'ajax_reset_settings' ) );
		add_action( 'wp_ajax_ai_faq_run_tests', array( $this, 'ajax_run_tests' ) );
		add_action( 'wp_ajax_ai_faq_fetch_cloudflare_stats', array( $this, 'ajax_fetch_cloudflare_stats' ) );
		// NOTE: AI model AJAX handlers moved to AI_FAQ_Admin_AI_Models class to avoid conflicts
		// add_action( 'wp_ajax_ai_faq_save_ai_models', array( $this, 'ajax_save_ai_models' ) );
		// add_action( 'wp_ajax_ai_faq_reset_ai_models', array( $this, 'ajax_reset_ai_models' ) );
		// add_action( 'wp_ajax_ai_faq_test_model_performance', array( $this, 'ajax_test_model_performance' ) );
	}

	/**
	 * AJAX handler for testing worker connectivity.
	 *
	 * Tests worker connection by making a GET request to the /health endpoint.
	 * All workers implement a standardized /health endpoint that accepts GET requests
	 * and returns detailed health status information.
	 *
	 * Testing process:
	 * 1. Makes a GET request to the worker's /health endpoint
	 * 2. Falls back to legacy multi-strategy approach if health check fails
	 *
	 * Returns comprehensive status information including response time
	 * and full API response data.
	 *
	 * @since 2.0.0
	 * @since 2.1.0 Updated to use GET requests to /health endpoint
	 */
	public function ajax_test_worker() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Get and validate worker name
		$worker_name = isset( $_POST['worker_name'] ) ? sanitize_key( $_POST['worker_name'] ) : '';
		if ( empty( $worker_name ) ) {
			wp_send_json_error( __( 'Worker name is required.', '365i-ai-faq-generator' ) );
		}

		// Get and validate worker URL
		$worker_url = isset( $_POST['worker_url'] ) ? $_POST['worker_url'] : '';
		if ( empty( $worker_url ) ) {
			wp_send_json_error( __( 'Worker URL is required.', '365i-ai-faq-generator' ) );
		}
		
		// Minimal URL sanitization - just trim whitespace
		// FILTER_SANITIZE_URL can break valid URLs by removing characters like colons in ports
		$worker_url = trim( $worker_url );
		
		// Validate URL format without modifying it
		if ( ! filter_var( $worker_url, FILTER_VALIDATE_URL ) ) {
			// If it doesn't have a protocol, add https:// and try again
			if ( ! preg_match( '~^(?:f|ht)tps?://~i', $worker_url ) ) {
				$worker_url_with_protocol = 'https://' . $worker_url;
				if ( filter_var( $worker_url_with_protocol, FILTER_VALIDATE_URL ) ) {
					$worker_url = $worker_url_with_protocol;
				} else {
					wp_send_json_error( __( 'Invalid worker URL format.', '365i-ai-faq-generator' ) );
				}
			} else {
				wp_send_json_error( __( 'Invalid worker URL format.', '365i-ai-faq-generator' ) );
			}
		}
		
		// Ensure URL doesn't have trailing slashes
		$worker_url = rtrim( $worker_url, '/' );
		
		// Append /health endpoint
		$health_url = $worker_url . '/health';
		
		// Normalize the worker name for consistent handling
		$normalized_worker = str_replace( '-', '_', $worker_name );
		
		$start_time = microtime( true );
		
		// Prepare request arguments for health check
		$args = array(
			'method'  => 'GET',
			'timeout' => 30,
			'headers' => array(
				'User-Agent'   => 'WordPress/365i-AI-FAQ-Generator',
				'X-Worker-Name' => $worker_name,
			),
			'sslverify' => false, // Allow self-signed certs
		);
		
		// Apply filters to request arguments
		$args = apply_filters( 'ai_faq_gen_test_worker_args', $args, $worker_name, $health_url );
		
		// Make the request
		$response = wp_remote_request( $health_url, $args );
		
		$end_time = microtime( true );
		$response_time = round( ( $end_time - $start_time ) * 1000 );
		
		// Handle response errors
		if ( is_wp_error( $response ) ) {
			
			$health_result = array(
				'status' => 'error',
				'message' => $response->get_error_message(),
				'error_code' => $response->get_error_code(),
				'response_time' => $response_time,
				'url' => $worker_url,
			);
		} else {
			// Process successful request
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );
			
			if ( $response_code >= 200 && $response_code < 300 ) {
				
				// Try to parse response as JSON
				$json_response = json_decode( $response_body, true );
				$json_success = ( json_last_error() === JSON_ERROR_NONE );
				
				$health_result = array(
					'status' => 'success',
					'message' => __( 'Worker is responding correctly.', '365i-ai-faq-generator' ),
					'response_time' => $response_time,
					'url' => $health_url,
					'data' => $json_success ? $json_response : array(),
					'http_code' => $response_code,
					'json_parsed' => $json_success,
				);
			} else {
				// Create specific error messages based on common error codes
				$error_message = sprintf( 'Connection failed with status code: %d', $response_code );
				
				if ( $response_code === 405 ) {
					$error_message = __( 'Worker health endpoint rejected GET method (405 Method Not Allowed). This indicates a configuration issue.', '365i-ai-faq-generator' );
				} elseif ( $response_code === 404 ) {
					$error_message = __( 'Health endpoint not found (404 Not Found). Verify the worker URL is correct and includes /health endpoint.', '365i-ai-faq-generator' );
				} elseif ( $response_code === 403 ) {
					$error_message = __( 'Access forbidden (403 Forbidden). The health endpoint may require authentication.', '365i-ai-faq-generator' );
				}
				
				// Try to extract more error details if available
				$error_details = $error_message;
				$json_response = json_decode( $response_body, true );
				
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $json_response ) ) {
					if ( isset( $json_response['error'] ) ) {
						$error_details = is_string( $json_response['error'] )
							? $json_response['error']
							: wp_json_encode( $json_response['error'] );
					} elseif ( isset( $json_response['message'] ) ) {
						$error_details = $json_response['message'];
					}
				}
				
				$health_result = array(
					'status' => 'error',
					'message' => $error_message,
					'response_time' => $response_time,
					'url' => $health_url,
					'error_code' => $response_code,
					'error_details' => $error_details,
					'http_code' => $response_code,
				);
			}
		}
		
		// Fall back to legacy test method if needed
		if ( ! isset( $health_result ) || ( isset( $health_result['status'] ) && 'error' === $health_result['status'] ) ) {
			// Use the original worker_name, not normalized, as the legacy method will handle its own normalization
			$fallback_result = $this->workers->test_worker_health( $worker_name, $worker_url );
			
			// Only use fallback if it was successful
			if ( isset( $fallback_result['status'] ) && 'healthy' === $fallback_result['status'] ) {
				$health_result = $fallback_result;
			}
		}
		
		// Add request details to the response for debugging
		$health_result['debug'] = array(
			'worker_name' => $worker_name,
			'normalized_name' => $normalized_worker,
			'worker_url' => $worker_url,
			'health_url' => $health_url,
			'test_time' => current_time( 'c' ),
			'approach' => 'GET to /health endpoint',
		);

		wp_send_json_success( $health_result );
	}

	/**
	 * AJAX handler for resetting worker usage statistics.
	 * 
	 * Clears rate limiting counters and usage statistics for specified worker.
	 * 
	 * @since 2.0.0
	 */
	public function ajax_reset_worker_usage() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$worker_name = isset( $_POST['worker_name'] ) ? sanitize_key( $_POST['worker_name'] ) : '';

		if ( empty( $worker_name ) ) {
			wp_send_json_error( __( 'Worker name is required.', '365i-ai-faq-generator' ) );
		}

		// Clear rate limit transients for all IPs for this worker.
		global $wpdb;
		$transient_pattern = $wpdb->esc_like( '_transient_ai_faq_rate_limit_' . $worker_name ) . '%';
		
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$transient_pattern
			)
		);

		wp_send_json_success( array(
			'message' => sprintf( 
				/* translators: %s: Worker name */
				__( 'Usage statistics reset for worker: %s', '365i-ai-faq-generator' ), 
				$worker_name 
			),
		) );
	}

	/**
	 * AJAX handler for getting worker status.
	 * 
	 * Returns current status, health, and usage statistics for all workers.
	 * 
	 * @since 2.0.0
	 */
	public function ajax_get_worker_status() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Get worker status
		$status_data = $this->workers->get_all_worker_status();

		wp_send_json_success( $status_data );
	}

	/**
	 * AJAX handler for saving admin settings.
	 *
	 * Validates and saves all plugin configuration settings.
	 *
	 * @since 2.0.0
	 */
	public function ajax_save_settings() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$settings_data = isset( $_POST['settings'] ) ? $_POST['settings'] : array();

		if ( empty( $settings_data ) ) {
			wp_send_json_error( __( 'Settings data is required.', '365i-ai-faq-generator' ) );
		}

		// Get existing options to preserve structure
		$existing_options = get_option( 'ai_faq_gen_options', array() );
		
		// Merge new settings with existing options while preserving worker settings
		$merged_settings = wp_parse_args( $settings_data, $existing_options );
		
		// Load the admin settings class for proper sanitization
		if ( ! class_exists( 'AI_FAQ_Admin_Settings' ) ) {
			require_once AI_FAQ_GEN_DIR . 'includes/admin/class-ai-faq-admin-settings.php';
		}
		
		$admin_settings = new AI_FAQ_Admin_Settings();
		
		// Sanitize the merged settings using the proper sanitization method
		$sanitized_settings = $admin_settings->sanitize_options( $merged_settings );
		
		// Update option with sanitized data
		$update_result = update_option( 'ai_faq_gen_options', $sanitized_settings );
		
		if ( $update_result ) {
			wp_send_json_success( array(
				'message' => __( 'Settings saved successfully.', '365i-ai-faq-generator' ),
			) );
		} else {
			// Get more specific error information
			$current_options = get_option( 'ai_faq_gen_options', array() );
			
			// Check if it's a permission issue, database issue, or same data
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Failed to save settings: Insufficient permissions.', '365i-ai-faq-generator' ) );
			} elseif ( $current_options === $sanitized_settings ) {
				wp_send_json_success( array(
					'message' => __( 'Settings were already up to date - no changes needed.', '365i-ai-faq-generator' ),
				) );
			} else {
				wp_send_json_error( __( 'Failed to save settings: Database update failed. Please check your database connection or try again.', '365i-ai-faq-generator' ) );
			}
		}
	}

	/**
	 * AJAX handler for fetching Enhanced Cloudflare Analytics.
	 *
	 * Uses Cloudflare's GraphQL Analytics API to fetch comprehensive statistics
	 * including Workers analytics, KV Storage metrics, and performance data.
	 *
	 * @since 2.1.0
	 */
	public function ajax_fetch_cloudflare_stats() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Check cache first (unless forced refresh)
		$days = isset( $_POST['days'] ) ? max( 1, min( 30, intval( $_POST['days'] ) ) ) : 7;
		$force_refresh = isset( $_POST['force_refresh'] ) && $_POST['force_refresh'];
		$cache_key = 'ai_faq_cloudflare_analytics_' . md5( $days );
		
		if ( ! $force_refresh ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				wp_send_json_success( $cached_data );
			}
		}

		// Get Cloudflare credentials from options
		$options = get_option( 'ai_faq_gen_options', array() );
		$account_id = isset( $options['cloudflare_account_id'] ) ? sanitize_text_field( $options['cloudflare_account_id'] ) : '';
		$api_token = isset( $options['cloudflare_api_token'] ) ? sanitize_text_field( $options['cloudflare_api_token'] ) : '';

		if ( empty( $account_id ) || empty( $api_token ) ) {
			wp_send_json_error( __( 'Cloudflare Account ID and API Token are required. Please configure them in Settings.', '365i-ai-faq-generator' ) );
		}

		// FIXED: Proper date range calculation (was causing 292-year ranges!)
		$datetime_start = gmdate( 'Y-m-d\T00:00:00\Z', strtotime( "-{$days} days" ) );
		$datetime_end = gmdate( 'Y-m-d\T23:59:59\Z' ); // Use current time, not relative
		$date_start = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );
		$date_end = gmdate( 'Y-m-d' ); // Use current date, not relative

		// Get worker and KV configuration
		$workers = isset( $options['workers'] ) ? $options['workers'] : array();
		$enabled_workers = $this->extract_enabled_workers( $workers );

		if ( empty( $enabled_workers ) ) {
			wp_send_json_error( __( 'No enabled workers found for statistics. Please configure workers first.', '365i-ai-faq-generator' ) );
		}

		// Initialize comprehensive analytics data
		$analytics_data = array(
			'period' => array(
				'days' => $days,
				'start' => $datetime_start,
				'end' => $datetime_end,
			),
			'workers' => array(),
			'kv_storage' => array(),
			'totals' => array(
				'requests' => 0,
				'errors' => 0,
				'subrequests' => 0,
				'avg_cpu_time_ms' => 0, // Changed from cpu_time to be clearer
				'egress_bytes' => 0,
				'egress_formatted' => '0 B',
				'kv_operations' => 0,
				'kv_storage_bytes' => 0,
				'kv_storage_formatted' => '0 B',
				'kv_keys' => 0,
			),
			'performance_summary' => array(
				'avg_cpu_time' => 0,
				'p50_cpu_time' => 0,
				'p95_cpu_time' => 0,
				'p99_cpu_time' => 0,
			),
			'errors' => array(),
		);

		// Fetch Enhanced Workers Analytics
		$worker_cpu_times = array(); // Store CPU times for proper averaging
		foreach ( $enabled_workers as $worker_name => $cloudflare_script_name ) {
			$worker_stats = $this->fetch_workers_analytics_official(
				$account_id,
				$api_token,
				$cloudflare_script_name,
				$datetime_start,
				$datetime_end
			);
			
			if ( is_wp_error( $worker_stats ) ) {
				$analytics_data['errors'][ $worker_name ] = $worker_stats->get_error_message();
				$analytics_data['workers'][ $worker_name ] = array(
					'error' => $worker_stats->get_error_message(),
					'script_name' => $cloudflare_script_name,
					'requests' => 0,
					'errors' => 0,
					'success_rate' => 0,
				);
			} else {
				// Calculate success rate for this worker
				$worker_stats['success_rate'] = $worker_stats['requests'] > 0
					? round( ( ( $worker_stats['requests'] - $worker_stats['errors'] ) / $worker_stats['requests'] ) * 100, 2 )
					: 0;
					
				$analytics_data['workers'][ $worker_name ] = array_merge( $worker_stats, array(
					'script_name' => $cloudflare_script_name,
					'egress_formatted' => $this->format_bytes( $worker_stats['egress_bytes'] ?? 0 ),
				) );
				
				// Aggregate totals properly
				$this->aggregate_worker_totals( $analytics_data['totals'], $worker_stats );
				
				// Collect CPU times for averaging (using P50 as average approximation)
				if ( $worker_stats['cpu_time_p50'] > 0 ) {
					$worker_cpu_times[] = array(
						'avg' => $worker_stats['cpu_time_p50'], // Use P50 as average
						'p50' => $worker_stats['cpu_time_p50'],
						'p95' => 0, // Not available in official API
						'p99' => $worker_stats['cpu_time_p99'],
						'requests' => $worker_stats['requests'],
					);
				}
			}
		}

		// Calculate proper performance averages
		if ( ! empty( $worker_cpu_times ) ) {
			$this->calculate_performance_summary( $analytics_data['performance_summary'], $worker_cpu_times );
			$analytics_data['totals']['avg_cpu_time_ms'] = $analytics_data['performance_summary']['avg_cpu_time'];
		}

		// Fetch KV Storage Analytics
		$kv_namespaces = $this->get_kv_namespaces( $account_id, $api_token );
		if ( ! is_wp_error( $kv_namespaces ) && ! empty( $kv_namespaces ) ) {
			foreach ( $kv_namespaces as $namespace ) {
				$kv_stats = $this->fetch_kv_storage_analytics_official(
					$account_id,
					$api_token,
					$namespace['id'],
					$date_start,
					$date_end
				);
				
				if ( ! is_wp_error( $kv_stats ) ) {
					$kv_stats['storage_formatted'] = $this->format_bytes( $kv_stats['storage_bytes'] ?? 0 );
					$analytics_data['kv_storage'][ $namespace['id'] ] = array_merge( $kv_stats, array(
						'name' => $namespace['title'],
						'id' => $namespace['id'],
					) );
					
					// Aggregate KV totals
					$analytics_data['totals']['kv_operations'] += $kv_stats['total_operations'] ?? 0;
					$analytics_data['totals']['kv_storage_bytes'] += $kv_stats['storage_bytes'] ?? 0;
					$analytics_data['totals']['kv_keys'] += $kv_stats['storage_keys'] ?? 0;
				} else {
					$analytics_data['errors']['kv_' . $namespace['id']] = $kv_stats->get_error_message();
				}
			}
		}

		// Format totals
		$analytics_data['totals']['egress_formatted'] = $this->format_bytes( $analytics_data['totals']['egress_bytes'] );
		$analytics_data['totals']['kv_storage_formatted'] = $this->format_bytes( $analytics_data['totals']['kv_storage_bytes'] );

		// Calculate overall success rate
		$analytics_data['success_rate'] = $analytics_data['totals']['requests'] > 0
			? round( ( ( $analytics_data['totals']['requests'] - $analytics_data['totals']['errors'] ) / $analytics_data['totals']['requests'] ) * 100, 2 )
			: 0;

		$analytics_data['message'] = sprintf(
			/* translators: %d: Number of days */
			__( 'Enhanced Cloudflare analytics fetched successfully for the last %d days.', '365i-ai-faq-generator' ),
			$days
		);

		// Add metadata
		$analytics_data['metadata'] = array(
			'generated_at' => current_time( 'mysql' ),
			'cache_expires' => gmdate( 'Y-m-d H:i:s', time() + 300 ),
			'worker_count' => count( $enabled_workers ),
			'kv_namespace_count' => is_array( $kv_namespaces ) ? count( $kv_namespaces ) : 0,
		);

		// Cache results for 5 minutes to respect API rate limits
		set_transient( $cache_key, $analytics_data, 300 );

		wp_send_json_success( $analytics_data );
	}

	/**
	 * Extract enabled workers from configuration.
	 *
	 * Improved to handle various URL formats and edge cases.
	 *
	 * @since 2.1.0
	 * @param array $workers Worker configuration array.
	 * @return array Enabled workers with script names.
	 */
	private function extract_enabled_workers( $workers ) {
		$enabled_workers = array();

		foreach ( $workers as $worker_name => $worker_config ) {
			if ( isset( $worker_config['enabled'] ) && $worker_config['enabled'] &&
				 isset( $worker_config['url'] ) && ! empty( $worker_config['url'] ) ) {
				
				// Extract worker script name from URL
				$script_name = $this->extract_script_name_from_url( $worker_config['url'] );
				
				if ( $script_name ) {
					$enabled_workers[ $worker_name ] = $script_name;
				}
			}
		}

		return $enabled_workers;
	}

	/**
	 * Extract script name from worker URL.
	 *
	 * IMPROVED: Better handling for auto-assigned workers.dev domains
	 *
	 * Handles various URL formats:
	 * - https://winter-cake-bf57.workers.dev → winter-cake-bf57
	 * - https://script-name.account.workers.dev → script-name
	 * - https://custom-domain.com/path → custom-domain (fallback)
	 *
	 * @since 2.1.0
	 * @param string $url Worker URL.
	 * @return string|false Script name or false if unable to extract.
	 */
	private function extract_script_name_from_url( $url ) {
		$url_parts = parse_url( $url );
		
		if ( ! isset( $url_parts['host'] ) ) {
			return false;
		}
		
		$host = $url_parts['host'];
		
		// Check if it's a workers.dev domain (most common case)
		if ( strpos( $host, '.workers.dev' ) !== false ) {
			// Extract subdomain as script name
			$parts = explode( '.', $host );
			
			// Handle different workers.dev formats:
			// 1. winter-cake-bf57.workers.dev (auto-assigned) → winter-cake-bf57
			// 2. script-name.account.workers.dev → script-name
			if ( count( $parts ) >= 3 ) {
				// script-name.account.workers.dev format
				return $parts[0];
			} elseif ( count( $parts ) === 2 && $parts[1] === 'workers.dev' ) {
				// Direct script-name.workers.dev format (shouldn't happen but handle it)
				return $parts[0];
			}
		}
		
		// For custom domains, try to extract from path first
		if ( isset( $url_parts['path'] ) && ! empty( $url_parts['path'] ) ) {
			// If there's a path, the script name might be in the path
			$path_parts = explode( '/', trim( $url_parts['path'], '/' ) );
			if ( ! empty( $path_parts[0] ) ) {
				return $path_parts[0];
			}
		}
		
		// Fallback: use the first part of the domain
		$parts = explode( '.', $host );
		return $parts[0];
	}

	/**
	 * Aggregate worker statistics into totals.
	 *
	 * Fixed to properly handle statistical aggregation.
	 *
	 * @since 2.1.0
	 * @param array $totals Reference to totals array.
	 * @param array $worker_stats Worker statistics to aggregate.
	 */
	private function aggregate_worker_totals( &$totals, $worker_stats ) {
		$totals['requests'] += $worker_stats['requests'] ?? 0;
		$totals['errors'] += $worker_stats['errors'] ?? 0;
		$totals['subrequests'] += $worker_stats['subrequests'] ?? 0;
		$totals['egress_bytes'] += $worker_stats['egress_bytes'] ?? 0;
		
		// Note: CPU times are handled separately for proper averaging
	}

	/**
	 * Calculate performance summary from multiple workers.
	 *
	 * UPDATED: Handle the fact that P95 is not available in official API.
	 * Properly averages CPU times weighted by request count.
	 *
	 * @since 2.1.7
	 * @param array $summary Reference to performance summary array.
	 * @param array $cpu_times Array of CPU time data from workers.
	 */
	private function calculate_performance_summary( &$summary, $cpu_times ) {
		if ( empty( $cpu_times ) ) {
			return;
		}
		
		$total_requests = array_sum( array_column( $cpu_times, 'requests' ) );
		
		if ( $total_requests === 0 ) {
			return;
		}
		
		// Calculate weighted averages
		$weighted_avg = 0;
		$weighted_p50 = 0;
		$weighted_p95 = 0; // Will be 0 since not available
		$weighted_p99 = 0;
		
		foreach ( $cpu_times as $data ) {
			$weight = $data['requests'] / $total_requests;
			$weighted_avg += $data['avg'] * $weight;
			$weighted_p50 += $data['p50'] * $weight;
			$weighted_p95 += $data['p95'] * $weight; // Will be 0
			$weighted_p99 += $data['p99'] * $weight;
		}
		
		$summary['avg_cpu_time'] = round( $weighted_avg, 2 );
		$summary['p50_cpu_time'] = round( $weighted_p50, 2 );
		$summary['p95_cpu_time'] = round( $weighted_p95, 2 ); // Will be 0
		$summary['p99_cpu_time'] = round( $weighted_p99, 2 );
	}

	/**
	 * Format bytes to human readable string.
	 *
	 * @since 2.1.0
	 * @param int $bytes Number of bytes.
	 * @param int $precision Decimal precision.
	 * @return string Formatted string.
	 */
	private function format_bytes( $bytes, $precision = 2 ) {
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		
		$bytes = max( $bytes, 0 );
		$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow = min( $pow, count( $units ) - 1 );
		
		$bytes /= pow( 1024, $pow );
		
		return round( $bytes, $precision ) . ' ' . $units[$pow];
	}

	/**
		* Get KV namespaces via REST API.
		*
		* @since 2.1.0
		* @param string $account_id Cloudflare account ID.
		* @param string $api_token Cloudflare API token.
		* @return array|WP_Error KV namespaces or error.
		*/
	private function get_kv_namespaces( $account_id, $api_token ) {
		$endpoint = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/storage/kv/namespaces";
		
		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type' => 'application/json',
				'User-Agent' => 'WordPress/365i-AI-FAQ-Generator',
			),
			'timeout' => 30,
		);

		$response = wp_remote_get( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'kv_api_error',
				sprintf(
					/* translators: %s: Error message */
					__( 'KV Namespaces API request failed: %s', '365i-ai-faq-generator' ),
					$response->get_error_message()
				)
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code !== 200 ) {
			return new WP_Error(
				'kv_api_http_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'KV Namespaces API returned HTTP %d', '365i-ai-faq-generator' ),
					$response_code
				)
			);
		}

		$data = json_decode( $response_body, true );

		if ( null === $data ) {
			return new WP_Error(
				'kv_api_json_error',
				__( 'Invalid JSON response from KV Namespaces API', '365i-ai-faq-generator' )
			);
		}

		if ( ! isset( $data['success'] ) || ! $data['success'] ) {
			$error_msg = isset( $data['errors'][0]['message'] ) ? $data['errors'][0]['message'] : __( 'Unknown KV API error', '365i-ai-faq-generator' );
			return new WP_Error( 'kv_api_error', $error_msg );
		}

		return $data['result'] ?? array();
	}

	/**
	 * Fetch KV Storage analytics using OFFICIAL Cloudflare GraphQL schema.
	 *
	 * BASED ON OFFICIAL DOCUMENTATION:
	 * https://developers.cloudflare.com/kv/observability/metrics-analytics/
	 *
	 * FIXES APPLIED:
	 * - REMOVED orderBy clauses (causing errors)
	 * - FIXED date range validation (was causing 292-year ranges)
	 * - Use proper field names from official docs
	 *
	 * @since 2.1.7
	 * @param string $account_id Cloudflare account ID.
	 * @param string $api_token Cloudflare API token.
	 * @param string $namespace_id KV namespace ID.
	 * @param string $date_start Start date in Y-m-d format.
	 * @param string $date_end End date in Y-m-d format.
	 * @return array|WP_Error KV statistics or error.
	 */
	private function fetch_kv_storage_analytics_official( $account_id, $api_token, $namespace_id, $date_start, $date_end ) {
		// Official GraphQL query from Cloudflare KV documentation
		$query = array(
			'query' => 'query KVAnalytics($accountTag: String!, $namespaceId: String!, $start: Date!, $end: Date!) {
				viewer {
					accounts(filter: { accountTag: $accountTag }) {
						kvOperationsAdaptiveGroups(
							filter: {
								namespaceId: $namespaceId,
								date_geq: $start,
								date_leq: $end
							}
							limit: 1000
						) {
							sum {
								requests
							}
							dimensions {
								actionType
							}
						}
						
						kvStorageAdaptiveGroups(
							filter: {
								namespaceId: $namespaceId,
								date_geq: $start,
								date_leq: $end
							}
							limit: 100
						) {
							max {
								keyCount
								byteCount
							}
							dimensions {
								date
							}
						}
					}
				}
			}',
			'variables' => array(
				'accountTag' => $account_id,
				'namespaceId' => $namespace_id,
				'start' => $date_start,
				'end' => $date_end,
			),
		);

		$response = $this->graphql_request( $query, $api_token );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$accounts = $response['data']['viewer']['accounts'][0] ?? array();
		
		// Process operations breakdown exactly as shown in official docs
		$operations_breakdown = array(
			'read' => 0,
			'write' => 0,
			'delete' => 0,
			'list' => 0,
		);
		
		$total_operations = 0;
		
		if ( isset( $accounts['kvOperationsAdaptiveGroups'] ) ) {
			foreach ( $accounts['kvOperationsAdaptiveGroups'] as $op ) {
				$action = $op['dimensions']['actionType'] ?? '';
				$count = $op['sum']['requests'] ?? 0;
				
				if ( isset( $operations_breakdown[$action] ) ) {
					$operations_breakdown[$action] += $count;
				}
				
				$total_operations += $count;
			}
		}
		
		// Get current storage metrics (maximum values)
		$storage_bytes = 0;
		$storage_keys = 0;
		
		if ( isset( $accounts['kvStorageAdaptiveGroups'] ) && ! empty( $accounts['kvStorageAdaptiveGroups'] ) ) {
			foreach ( $accounts['kvStorageAdaptiveGroups'] as $storage ) {
				$bytes = $storage['max']['byteCount'] ?? 0;
				$keys = $storage['max']['keyCount'] ?? 0;
				
				// Take maximum values as current storage
				if ( $bytes > $storage_bytes ) {
					$storage_bytes = $bytes;
				}
				if ( $keys > $storage_keys ) {
					$storage_keys = $keys;
				}
			}
		}
		
		return array(
			'total_operations' => $total_operations,
			'operations_breakdown' => $operations_breakdown,
			'storage_bytes' => $storage_bytes,
			'storage_keys' => $storage_keys,
		);
	}

	/**
	 * Fetch Workers analytics using OFFICIAL Cloudflare GraphQL schema.
	 *
	 * BASED ON OFFICIAL DOCUMENTATION:
	 * https://developers.cloudflare.com/analytics/graphql-api/tutorials/querying-workers-metrics/
	 *
	 * CORRECT FIELDS (from official docs):
	 * - sum { subrequests, requests, errors }
	 * - quantiles { cpuTimeP50, cpuTimeP99 }
	 * - dimensions { datetime, scriptName, status }
	 *
	 * REMOVED INVALID FIELDS:
	 * - avg { cpuTime } (doesn't exist)
	 * - egressBytes (doesn't exist)
	 * - cpuTimeP95 (not in official schema)
	 *
	 * @since 2.1.7
	 * @param string $account_id Cloudflare account ID.
	 * @param string $api_token Cloudflare API token.
	 * @param string $script_name Worker script name.
	 * @param string $datetime_start Start datetime in ISO format.
	 * @param string $datetime_end End datetime in ISO format.
	 * @return array|WP_Error Worker statistics or error.
	 */
	private function fetch_workers_analytics_official( $account_id, $api_token, $script_name, $datetime_start, $datetime_end ) {
		// Official GraphQL query from Cloudflare documentation
		$query = array(
			'query' => 'query GetWorkersAnalytics($accountTag: String!, $datetimeStart: String!, $datetimeEnd: String!, $scriptName: String!) {
				viewer {
					accounts(filter: { accountTag: $accountTag }) {
						workersInvocationsAdaptive(
							limit: 100,
							filter: {
								scriptName: $scriptName,
								datetime_geq: $datetimeStart,
								datetime_leq: $datetimeEnd
							}
						) {
							sum {
								subrequests
								requests
								errors
							}
							quantiles {
								cpuTimeP50
								cpuTimeP99
							}
							dimensions {
								datetime
								scriptName
								status
							}
						}
					}
				}
			}',
			'variables' => array(
				'accountTag' => $account_id,
				'scriptName' => $script_name,
				'datetimeStart' => $datetime_start,
				'datetimeEnd' => $datetime_end,
			),
		);

		$response = $this->graphql_request( $query, $api_token );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Initialize statistics
		$stats = array(
			'requests' => 0,
			'errors' => 0,
			'subrequests' => 0,
			'egress_bytes' => 0, // Keep for compatibility, always 0
			'cpu_time_avg' => 0,
			'cpu_time_p50' => 0,
			'cpu_time_p95' => 0, // Not available in official API
			'cpu_time_p99' => 0,
			'time_series' => array(),
		);

		// Parse response exactly as shown in official documentation
		if ( isset( $response['data']['viewer']['accounts'][0]['workersInvocationsAdaptive'] ) ) {
			$invocations = $response['data']['viewer']['accounts'][0]['workersInvocationsAdaptive'];
			
			$cpu_time_sum = 0;
			$cpu_time_count = 0;
			
			// Aggregate all data points
			foreach ( $invocations as $invocation ) {
				// Sum basic metrics
				if ( isset( $invocation['sum'] ) ) {
					$stats['requests'] += intval( $invocation['sum']['requests'] ?? 0 );
					$stats['errors'] += intval( $invocation['sum']['errors'] ?? 0 );
					$stats['subrequests'] += intval( $invocation['sum']['subrequests'] ?? 0 );
				}
				
				// Handle CPU time quantiles (take most recent non-zero values)
				if ( isset( $invocation['quantiles'] ) ) {
					if ( isset( $invocation['quantiles']['cpuTimeP50'] ) && $invocation['quantiles']['cpuTimeP50'] > 0 ) {
						$stats['cpu_time_p50'] = floatval( $invocation['quantiles']['cpuTimeP50'] );
						$cpu_time_sum += $stats['cpu_time_p50'];
						$cpu_time_count++;
					}
					if ( isset( $invocation['quantiles']['cpuTimeP99'] ) && $invocation['quantiles']['cpuTimeP99'] > 0 ) {
						$stats['cpu_time_p99'] = floatval( $invocation['quantiles']['cpuTimeP99'] );
					}
				}
				
				// Store time series data
				if ( isset( $invocation['dimensions']['datetime'] ) ) {
					$stats['time_series'][] = array(
						'datetime' => $invocation['dimensions']['datetime'],
						'requests' => intval( $invocation['sum']['requests'] ?? 0 ),
						'errors' => intval( $invocation['sum']['errors'] ?? 0 ),
						'cpu_time_p50' => floatval( $invocation['quantiles']['cpuTimeP50'] ?? 0 ),
						'status' => $invocation['dimensions']['status'] ?? '',
					);
				}
			}
			
			// Calculate average CPU time from P50 values
			if ( $cpu_time_count > 0 ) {
				$stats['cpu_time_avg'] = $cpu_time_sum / $cpu_time_count;
			}
		}

		return $stats;
	}

	/**
		* Make GraphQL request to Cloudflare API.
		*
		* @since 2.1.0
		* @param array  $query GraphQL query and variables.
		* @param string $api_token Cloudflare API token.
		* @return array|WP_Error Response data or error.
		*/
	private function graphql_request( $query, $api_token ) {
		$endpoint = 'https://api.cloudflare.com/client/v4/graphql';
		
		$args = array(
			'method' => 'POST',
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type' => 'application/json',
				'User-Agent' => 'WordPress/365i-AI-FAQ-Generator',
			),
			'body' => wp_json_encode( $query ),
			'timeout' => 30,
		);

		$response = wp_remote_post( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'cloudflare_api_error',
				sprintf(
					/* translators: %s: Error message */
					__( 'Cloudflare API request failed: %s', '365i-ai-faq-generator' ),
					$response->get_error_message()
				)
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code !== 200 ) {
			return new WP_Error(
				'cloudflare_api_http_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Cloudflare API returned HTTP %d', '365i-ai-faq-generator' ),
					$response_code
				)
			);
		}

		$data = json_decode( $response_body, true );

		if ( null === $data ) {
			return new WP_Error(
				'cloudflare_api_json_error',
				__( 'Invalid JSON response from Cloudflare API', '365i-ai-faq-generator' )
			);
		}

		// Check for GraphQL errors
		if ( isset( $data['errors'] ) && ! empty( $data['errors'] ) ) {
			$error_messages = array();
			foreach ( $data['errors'] as $error ) {
				$error_messages[] = isset( $error['message'] ) ? $error['message'] : __( 'Unknown GraphQL error', '365i-ai-faq-generator' );
			}
			return new WP_Error(
				'cloudflare_graphql_error',
				sprintf(
					/* translators: %s: Error messages */
					__( 'Cloudflare GraphQL errors: %s', '365i-ai-faq-generator' ),
					implode( ', ', $error_messages )
				)
			);
		}

		return $data;
	}

	/**
		* Validate worker script names and test basic connectivity.
		*
		* This helps debug script name extraction issues by comparing
		* configured workers with actual workers available in your Cloudflare account.
		*
		* @since 2.1.7
		* @param string $account_id Cloudflare account ID.
		* @param string $api_token Cloudflare API token.
		* @return array List of available worker scripts and validation results.
		*/
	private function validate_worker_scripts( $account_id, $api_token ) {
		// Get all available worker scripts from the account
		$endpoint = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/workers/scripts";
		
		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type' => 'application/json',
				'User-Agent' => 'WordPress/365i-AI-FAQ-Generator',
			),
			'timeout' => 30,
		);

		$response = wp_remote_get( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error' => 'API request failed: ' . $response->get_error_message(),
				'available_scripts' => array(),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code !== 200 ) {
			return array(
				'success' => false,
				'error' => "API returned HTTP {$response_code}",
				'available_scripts' => array(),
				'response_body' => $response_body,
			);
		}

		$data = json_decode( $response_body, true );

		if ( null === $data ) {
			return array(
				'success' => false,
				'error' => 'Invalid JSON response from API',
				'available_scripts' => array(),
			);
		}

		if ( ! isset( $data['success'] ) || ! $data['success'] ) {
			$error_msg = isset( $data['errors'][0]['message'] ) ? $data['errors'][0]['message'] : 'Unknown API error';
			return array(
				'success' => false,
				'error' => $error_msg,
				'available_scripts' => array(),
			);
		}

		// Extract script names from the response
		$available_scripts = array();
		if ( isset( $data['result'] ) && is_array( $data['result'] ) ) {
			foreach ( $data['result'] as $script ) {
				if ( isset( $script['id'] ) ) {
					$available_scripts[] = array(
						'id' => $script['id'],
						'created_on' => $script['created_on'] ?? 'Unknown',
						'modified_on' => $script['modified_on'] ?? 'Unknown',
					);
				}
			}
		}

		return array(
			'success' => true,
			'available_scripts' => $available_scripts,
			'total_scripts' => count( $available_scripts ),
		);
	}

	/**
	 * AJAX handler for saving AI model configurations.
	 *
	 * @deprecated 2.3.0 Use AI_FAQ_Admin_AI_Models::handle_save_models_ajax() instead
	 * @since 2.2.0
	 */
	public function ajax_save_ai_models() {
		// NOTE: This method is deprecated. AI model AJAX handlers moved to AI_FAQ_Admin_AI_Models class.
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$model_configs = isset( $_POST['ai_models'] ) ? $_POST['ai_models'] : array();

		if ( empty( $model_configs ) || ! is_array( $model_configs ) ) {
			wp_send_json_error( __( 'AI model configuration data is required.', '365i-ai-faq-generator' ) );
		}

		// Load AI models management class
		if ( ! class_exists( 'AI_FAQ_Admin_AI_Models' ) ) {
			require_once AI_FAQ_GEN_DIR . 'includes/admin/class-ai-faq-admin-ai-models.php';
		}
		
		$ai_models_admin = new AI_FAQ_Admin_AI_Models();
		$result = $ai_models_admin->save_model_configurations( $model_configs );

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message' => $result['message'],
				'changed_count' => $result['changed_count'],
				'configurations' => $result['configurations'],
			) );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
		* AJAX handler for resetting AI model configurations to defaults.
		*
		* Resets all AI model configurations to recommended defaults.
		*
		* @since 2.2.0
		*/
	public function ajax_reset_ai_models() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Load AI models management class
		if ( ! class_exists( 'AI_FAQ_Admin_AI_Models' ) ) {
			require_once AI_FAQ_GEN_DIR . 'includes/admin/class-ai-faq-admin-ai-models.php';
		}
		
		$ai_models_admin = new AI_FAQ_Admin_AI_Models();
		$result = $ai_models_admin->reset_to_defaults();

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message' => $result['message'],
				'configurations' => $result['configurations'],
			) );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
		* AJAX handler for testing AI model performance.
		*
		* Tests response times and capabilities of selected AI models.
		*
		* @since 2.2.0
		*/
	public function ajax_test_model_performance() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$worker_type = isset( $_POST['worker_type'] ) ? sanitize_key( $_POST['worker_type'] ) : '';
		$model_id = isset( $_POST['model_id'] ) ? sanitize_text_field( $_POST['model_id'] ) : '';

		if ( empty( $worker_type ) || empty( $model_id ) ) {
			wp_send_json_error( __( 'Worker type and model ID are required for testing.', '365i-ai-faq-generator' ) );
		}

		// Get worker configuration to test the model
		$options = get_option( 'ai_faq_gen_options', array() );
		$workers = isset( $options['workers'] ) ? $options['workers'] : array();

		if ( ! isset( $workers[ $worker_type ] ) ) {
			wp_send_json_error( __( 'Worker not configured for testing.', '365i-ai-faq-generator' ) );
		}

		$worker_config = $workers[ $worker_type ];
		$worker_url = $worker_config['url'];

		if ( empty( $worker_url ) ) {
			wp_send_json_error( __( 'Worker URL not configured.', '365i-ai-faq-generator' ) );
		}

		// Create test payload with model specification
		$test_payload = array(
			'question' => 'What are the benefits of AI-powered FAQ generation?',
			'mode' => 'test',
			'model' => $model_id,
			'test_mode' => true,
		);

		$start_time = microtime( true );

		// Make test request to worker
		$response = wp_remote_post( $worker_url, array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/json',
				'User-Agent' => 'WordPress/365i-AI-FAQ-Generator-Model-Test',
			),
			'body' => wp_json_encode( $test_payload ),
		) );

		$response_time = round( ( microtime( true ) - $start_time ) * 1000, 2 );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: Error message */
					__( 'Model test failed: %s', '365i-ai-faq-generator' ),
					$response->get_error_message()
				),
				'response_time' => $response_time,
			) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code >= 200 && $response_code < 300 ) {
			$response_data = json_decode( $response_body, true );
			
			wp_send_json_success( array(
				'message' => sprintf(
					/* translators: %1$s: Model ID, %2$d: Response time */
					__( 'Model %1$s test completed successfully in %2$dms', '365i-ai-faq-generator' ),
					$model_id,
					$response_time
				),
				'response_time' => $response_time,
				'model_id' => $model_id,
				'worker_type' => $worker_type,
				'response_preview' => is_array( $response_data ) ?
					( isset( $response_data['answer'] ) ? substr( $response_data['answer'], 0, 100 ) . '...' : 'Response received' ) :
					'Response received',
				'full_response' => $response_data,
			) );
		} else {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %1$s: Model ID, %2$d: HTTP status code */
					__( 'Model %1$s test failed with HTTP %2$d', '365i-ai-faq-generator' ),
					$model_id,
					$response_code
				),
				'response_time' => $response_time,
				'http_code' => $response_code,
				'response_body' => substr( $response_body, 0, 200 ),
			) );
		}
	}

	/**
		* Legacy method: Fetch Cloudflare Worker statistics via GraphQL API.
		*
		* @since 2.1.0
		* @param string $account_id Cloudflare account ID.
		* @param string $api_token Cloudflare API token.
		* @param string $script_name Worker script name.
		* @param string $datetime_start Start datetime in ISO format.
		* @param string $datetime_end End datetime in ISO format.
		* @return array|WP_Error Worker statistics or error.
		* @deprecated 2.1.0 Use fetch_enhanced_worker_analytics() instead.
		*/
	private function fetch_cloudflare_worker_stats( $account_id, $api_token, $script_name, $datetime_start, $datetime_end ) {
		$endpoint = 'https://api.cloudflare.com/client/v4/graphql';
		
		$query = array(
			'query' => '
				query ($accountTag: String, $scriptName: String, $datetimeStart: String, $datetimeEnd: String) {
					viewer {
						accounts(filter: { accountTag: $accountTag }) {
							workersInvocationsAdaptive(
								filter: {
									scriptName: $scriptName
									datetime_geq: $datetimeStart
									datetime_leq: $datetimeEnd
								}
								limit: 10000
							) {
								sum {
									requests
									errors
									subrequests
								}
								quantiles {
									cpuTimeP50
									cpuTimeP99
								}
								dimensions {
									datetime
									scriptName
									status
								}
							}
						}
					}
				}
			',
			'variables' => array(
				'accountTag' => $account_id,
				'scriptName' => $script_name,
				'datetimeStart' => $datetime_start,
				'datetimeEnd' => $datetime_end,
			),
		);

		$args = array(
			'method' => 'POST',
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type' => 'application/json',
				'User-Agent' => 'WordPress/365i-AI-FAQ-Generator',
			),
			'body' => wp_json_encode( $query ),
			'timeout' => 30,
		);

		$response = wp_remote_post( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'cloudflare_api_error',
				sprintf(
					/* translators: %s: Error message */
					__( 'Cloudflare API request failed: %s', '365i-ai-faq-generator' ),
					$response->get_error_message()
				)
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code !== 200 ) {
			return new WP_Error(
				'cloudflare_api_http_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Cloudflare API returned HTTP %d', '365i-ai-faq-generator' ),
					$response_code
				)
			);
		}

		$data = json_decode( $response_body, true );

		if ( null === $data ) {
			return new WP_Error(
				'cloudflare_api_json_error',
				__( 'Invalid JSON response from Cloudflare API', '365i-ai-faq-generator' )
			);
		}

		// Check for GraphQL errors
		if ( isset( $data['errors'] ) && ! empty( $data['errors'] ) ) {
			$error_messages = array();
			foreach ( $data['errors'] as $error ) {
				$error_messages[] = isset( $error['message'] ) ? $error['message'] : __( 'Unknown GraphQL error', '365i-ai-faq-generator' );
			}
			return new WP_Error(
				'cloudflare_graphql_error',
				sprintf(
					/* translators: %s: Error messages */
					__( 'Cloudflare GraphQL errors: %s', '365i-ai-faq-generator' ),
					implode( ', ', $error_messages )
				)
			);
		}

		// Extract statistics from response
		$stats = array(
			'requests' => 0,
			'errors' => 0,
			'subrequests' => 0,
			'cpu_time_p50' => 0,
			'cpu_time_p99' => 0,
			'egress_bytes' => 0, // Note: This field isn't available in workersInvocationsAdaptive
		);

		// Parse response - note that workersInvocationsAdaptive returns an array of data points
		if ( isset( $data['data']['viewer']['accounts'][0]['workersInvocationsAdaptive'] ) ) {
			$invocations = $data['data']['viewer']['accounts'][0]['workersInvocationsAdaptive'];
			
			// Aggregate all the data points
			foreach ( $invocations as $invocation ) {
				if ( isset( $invocation['sum'] ) ) {
					$stats['requests'] += isset( $invocation['sum']['requests'] ) ? intval( $invocation['sum']['requests'] ) : 0;
					$stats['errors'] += isset( $invocation['sum']['errors'] ) ? intval( $invocation['sum']['errors'] ) : 0;
					$stats['subrequests'] += isset( $invocation['sum']['subrequests'] ) ? intval( $invocation['sum']['subrequests'] ) : 0;
				}
				
				// For quantiles, you might want to calculate an average or take the last value
				// Here we're taking the last non-zero value
				if ( isset( $invocation['quantiles'] ) ) {
					if ( isset( $invocation['quantiles']['cpuTimeP50'] ) && $invocation['quantiles']['cpuTimeP50'] > 0 ) {
						$stats['cpu_time_p50'] = floatval( $invocation['quantiles']['cpuTimeP50'] );
					}
					if ( isset( $invocation['quantiles']['cpuTimeP99'] ) && $invocation['quantiles']['cpuTimeP99'] > 0 ) {
						$stats['cpu_time_p99'] = floatval( $invocation['quantiles']['cpuTimeP99'] );
					}
				}
			}
		}

		return $stats;
	}

	/**
		* AJAX handler for saving worker configurations.
		*
		* Validates and saves worker URL and settings configurations.
		*
		* @since 2.1.0
		*/
	public function ajax_save_workers() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce. Check both possible nonce fields.
		$nonce = '';
		if ( isset( $_POST['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( $_POST['_wpnonce'] );
		} elseif ( isset( $_POST['nonce'] ) ) {
			$nonce = sanitize_text_field( $_POST['nonce'] );
		}
		
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_save_workers' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$workers_data = isset( $_POST['workers'] ) ? $_POST['workers'] : array();

		if ( empty( $workers_data ) || ! is_array( $workers_data ) ) {
			wp_send_json_error( __( 'Worker data is required.', '365i-ai-faq-generator' ) );
		}

		// Get existing options
		$existing_options = get_option( 'ai_faq_gen_options', array() );
		
		// Sanitize worker data
		$sanitized_workers = array();
		foreach ( $workers_data as $worker_name => $worker_config ) {
			$worker_name = sanitize_key( $worker_name );
			
			if ( ! is_array( $worker_config ) ) {
				continue;
			}
			
			$sanitized_workers[ $worker_name ] = array(
				'url' => isset( $worker_config['url'] ) ? esc_url_raw( trim( $worker_config['url'] ) ) : '',
				'enabled' => isset( $worker_config['enabled'] ) ? (bool) $worker_config['enabled'] : true,
				'rate_limit' => isset( $worker_config['rate_limit'] ) ? max( 1, intval( $worker_config['rate_limit'] ) ) : 10,
			);
		}

		// Update workers section while preserving other settings
		$existing_options['workers'] = $sanitized_workers;

		// Update option
		$update_result = update_option( 'ai_faq_gen_options', $existing_options );
		
		if ( $update_result ) {
			// Reload worker configuration to ensure fresh URLs are used immediately
			$this->reload_worker_configuration();

			wp_send_json_success( array(
				'message' => sprintf(
					/* translators: %d: Number of workers saved */
					__( 'Successfully saved %d worker configurations.', '365i-ai-faq-generator' ),
					count( $sanitized_workers )
				),
				'workers_count' => count( $sanitized_workers ),
			) );
		} else {
			// Get more specific error information
			$current_options = get_option( 'ai_faq_gen_options', array() );
			
			// Check if it's a permission issue, database issue, or same data
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Failed to save worker configurations: Insufficient permissions.', '365i-ai-faq-generator' ) );
			} elseif ( isset( $current_options['workers'] ) && $current_options['workers'] === $sanitized_workers ) {
				wp_send_json_success( array(
					'message' => __( 'Worker configurations were already up to date - no changes needed.', '365i-ai-faq-generator' ),
					'workers_count' => count( $sanitized_workers ),
				) );
			} else {
				wp_send_json_error( __( 'Failed to save worker configurations: Database update failed. Please check your database connection or try again.', '365i-ai-faq-generator' ) );
			}
		}
	}

	/**
		* Reload worker configuration after settings changes.
		*
		* This method ensures that worker configuration changes are immediately
		* reflected in the active workers system without requiring a page refresh.
		*
		* @since 2.1.0
		* @return bool True if configuration was reloaded successfully.
		*/
	private function reload_worker_configuration() {
		// Access the global workers instance to reload configuration
		global $ai_faq_workers;
		
		if ( $ai_faq_workers && method_exists( $ai_faq_workers, 'manager' ) ) {
			$manager = $ai_faq_workers->manager;
			if ( $manager && method_exists( $manager, 'reload_worker_config' ) ) {
				return $manager->reload_worker_config();
			}
		}
		
		// Fallback: Try to access workers through the facade
		if ( class_exists( 'AI_FAQ_Workers' ) ) {
			$workers_instance = new AI_FAQ_Workers();
			if ( method_exists( $workers_instance, 'reload_worker_config' ) ) {
				return $workers_instance->reload_worker_config();
			}
		}
		
		return false;
	}

	/**
		* AJAX handler for getting rate limit violations.
	 * 
	 * Returns violation data for dashboard display and management.
	 * 
	 * @since 2.0.0
	 */
	public function ajax_get_violations() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$period_hours = isset( $_POST['period'] ) ? intval( $_POST['period'] ) : 24;
		$cutoff_time = time() - ( $period_hours * HOUR_IN_SECONDS );

		// Get security component to process violations
		$security = new AI_FAQ_Admin_Security();
		$response_data = $security->get_violations_data( $period_hours );

		wp_send_json_success( $response_data );
	}

	/**
	 * AJAX handler for blocking an IP address.
	 * 
	 * Manually blocks an IP address with specified duration and reason.
	 * 
	 * @since 2.0.0
	 */
	public function ajax_block_ip() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$ip_address = isset( $_POST['ip_address'] ) ? sanitize_text_field( $_POST['ip_address'] ) : '';
		$reason = isset( $_POST['reason'] ) ? sanitize_text_field( $_POST['reason'] ) : '';
		$duration_hours = isset( $_POST['duration'] ) ? intval( $_POST['duration'] ) : 24;

		if ( empty( $ip_address ) ) {
			wp_send_json_error( __( 'IP address is required.', '365i-ai-faq-generator' ) );
		}

		// Validate IP address format.
		if ( ! filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
			wp_send_json_error( __( 'Invalid IP address format.', '365i-ai-faq-generator' ) );
		}

		// Get security component to handle IP blocking
		$security = new AI_FAQ_Admin_Security();
		$result = $security->block_ip( $ip_address, $reason, $duration_hours );

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message' => $result['message'],
			) );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX handler for unblocking an IP address.
	 * 
	 * Removes an IP address from the blocked list.
	 * 
	 * @since 2.0.0
	 */
	public function ajax_unblock_ip() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$ip_address = isset( $_POST['ip_address'] ) ? sanitize_text_field( $_POST['ip_address'] ) : '';

		if ( empty( $ip_address ) ) {
			wp_send_json_error( __( 'IP address is required.', '365i-ai-faq-generator' ) );
		}

		// Get security component to handle IP unblocking
		$security = new AI_FAQ_Admin_Security();
		$result = $security->unblock_ip( $ip_address );

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message' => $result['message'],
			) );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX handler for getting analytics data.
	 * 
	 * Returns usage analytics and statistics for dashboard display.
	 * 
	 * @since 2.0.0
	 */
	public function ajax_get_analytics() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$period_days = isset( $_POST['period'] ) ? intval( $_POST['period'] ) : 30;

		// Get analytics component to process data
		$analytics = new AI_FAQ_Admin_Analytics();
		$analytics_data = $analytics->get_analytics_data( $period_days );

		wp_send_json_success( $analytics_data );
	}

	/**
	 * AJAX handler for testing API connection.
	 * 
	 * Tests Cloudflare API connection using provided credentials.
	 * 
	 * @since 2.0.1
	 */
	public function ajax_test_api_connection() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$account_id = isset( $_POST['account_id'] ) ? sanitize_text_field( $_POST['account_id'] ) : '';
		$api_token = isset( $_POST['api_token'] ) ? sanitize_text_field( $_POST['api_token'] ) : '';

		if ( empty( $account_id ) || empty( $api_token ) ) {
			wp_send_json_error( __( 'Account ID and API Token are required.', '365i-ai-faq-generator' ) );
		}

		// Get workers component to handle API testing
		$result = $this->workers->test_api_connection( $account_id, $api_token );

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message' => $result['message'],
				'worker_count' => $result['worker_count'],
			) );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX handler for importing settings.
	 * 
	 * Imports and validates settings from JSON data.
	 * 
	 * @since 2.0.1
	 */
	public function ajax_import_settings() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$settings_data = isset( $_POST['settings'] ) ? $_POST['settings'] : array();

		if ( empty( $settings_data ) || ! is_array( $settings_data ) ) {
			wp_send_json_error( __( 'Invalid settings data provided.', '365i-ai-faq-generator' ) );
		}

		// Load and get the settings component
		require_once AI_FAQ_GEN_DIR . 'includes/admin/class-ai-faq-admin-settings.php';
		$settings = new AI_FAQ_Admin_Settings();
		$result = $settings->import_settings( $settings_data );

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message' => $result['message'],
				'imported_count' => $result['imported_count'],
			) );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX handler for resetting settings to defaults.
	 *
	 * Resets all plugin settings to their default values.
	 *
	 * @since 2.0.1
	 */
	public function ajax_reset_settings() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Check if constants are defined
		if ( ! defined( 'AI_FAQ_GEN_DIR' ) ) {
			wp_send_json_error( __( 'Plugin configuration error. Please contact support.', '365i-ai-faq-generator' ) );
		}

		$settings_file = AI_FAQ_GEN_DIR . 'includes/admin/class-ai-faq-admin-settings.php';
		
		// Check if file exists before requiring
		if ( ! file_exists( $settings_file ) ) {
			wp_send_json_error( __( 'Settings class file not found. Please contact support.', '365i-ai-faq-generator' ) );
		}

		// Load and get the settings component
		require_once $settings_file;
		
		// Check if class exists after requiring
		if ( ! class_exists( 'AI_FAQ_Admin_Settings' ) ) {
			wp_send_json_error( __( 'Settings class not found. Please contact support.', '365i-ai-faq-generator' ) );
		}
		
		$settings = new AI_FAQ_Admin_Settings();
		$result = $settings->reset_settings();

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message' => $result['message'],
			) );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX handler for running tests.
	 * 
	 * Executes the test-workers.js script and returns the results.
	 * 
	 * @since 2.0.2
	 */
	public function ajax_run_tests() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Get the workers component to run tests
		$result = $this->workers->run_worker_tests();

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message' => $result['message'],
				'test_output' => $result['test_output'],
				'import_results' => $result['import_results'] ?? null,
			) );
		} else {
			wp_send_json_error( array(
				'message' => $result['message'],
				'output' => $result['output'] ?? null,
				'code' => $result['code'] ?? null,
			) );
		}
	}
}