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

		// Get existing options
		$existing_options = get_option( 'ai_faq_gen_options', array() );
		
		// Update existing options with new settings while preserving worker settings
		if ( isset( $existing_options['workers'] ) ) {
			// Ensure workers are preserved
			if ( ! isset( $settings_data['workers'] ) ) {
				$settings_data['workers'] = $existing_options['workers'];
			}
		}

		// Update option
		$update_result = update_option( 'ai_faq_gen_options', $settings_data );
		
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
			} elseif ( $current_options === $settings_data ) {
				wp_send_json_success( array(
					'message' => __( 'Settings were already up to date - no changes needed.', '365i-ai-faq-generator' ),
				) );
			} else {
				wp_send_json_error( __( 'Failed to save settings: Database update failed. Please check your database connection or try again.', '365i-ai-faq-generator' ) );
			}
		}
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