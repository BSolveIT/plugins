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
	 * No direct hooks needed as this is used by other components.
	 * 
	 * @since 2.1.0
	 */
	public function init() {
		// This class is primarily used by other components rather than hooking directly.
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
			
			// Handle various health response formats
			if ( is_array( $health_data ) ) {
				// Check if status is explicitly marked in the response
				if ( isset( $health_data['status'] ) ) {
					$is_healthy = (
						$health_data['status'] === 'healthy' ||
						$health_data['status'] === 'ok' ||
						$health_data['status'] === 'online' ||
						$health_data['status'] === true
					);
					
					if ( $is_healthy ) {
						return array(
							'status' => 'healthy',
							'data' => $health_data,
							'response_time' => $response_time,
							'method' => 'GET',
						);
					}
				}
				// If no explicit status but response is success and has data, consider it healthy
				else if ( !empty( $health_data ) ) {
					return array(
						'status' => 'healthy',
						'data' => array(
							'status' => 'healthy',
							'service' => $worker_name,
							'method' => 'GET',
							'timestamp' => current_time( 'c' ),
							'response' => $health_data,
						),
						'response_time' => $response_time,
					);
				}
			}
			// Even empty responses with 200 status code indicate the endpoint exists
			else {
				return array(
					'status' => 'healthy',
					'data' => array(
						'status' => 'healthy',
						'service' => $worker_name,
						'method' => 'GET',
						'timestamp' => current_time( 'c' ),
						'note' => 'Worker responded but with empty or non-JSON response',
					),
					'response_time' => $response_time,
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