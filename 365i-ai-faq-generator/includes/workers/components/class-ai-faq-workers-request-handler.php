<?php
/**
 * Workers Request Handler class for 365i AI FAQ Generator.
 * 
 * This class handles AJAX requests for worker operations, including
 * input validation, permission checks, and response formatting.
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
 * Workers Request Handler class.
 * 
 * Manages AJAX request handling for worker operations.
 * 
 * @since 2.1.0
 */
class AI_FAQ_Workers_Request_Handler {

	/**
	 * Workers Manager instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Workers_Manager
	 */
	private $manager;

	/**
	 * Rate Limiter instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Workers_Rate_Limiter
	 */
	private $rate_limiter;

	/**
	 * Security instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Workers_Security
	 */
	private $security;

	/**
	 * Analytics instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Workers_Analytics
	 */
	private $analytics;

	/**
	 * Constructor.
	 * 
	 * Initialize the request handler with component dependencies.
	 * 
	 * @since 2.1.0
	 * @param AI_FAQ_Workers_Manager    $manager      Workers Manager instance.
	 * @param AI_FAQ_Workers_Rate_Limiter $rate_limiter Rate Limiter instance.
	 * @param AI_FAQ_Workers_Security     $security     Security instance.
	 * @param AI_FAQ_Workers_Analytics    $analytics    Analytics instance.
	 */
	public function __construct( $manager, $rate_limiter, $security, $analytics ) {
		$this->manager = $manager;
		$this->rate_limiter = $rate_limiter;
		$this->security = $security;
		$this->analytics = $analytics;
	}

	/**
	 * Initialize the request handler.
	 * 
	 * Set up hooks and filters for AJAX handling.
	 * 
	 * @since 2.1.0
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

		// Add admin-only AJAX handlers.
		// Note: ai_faq_test_worker is handled by AI_FAQ_Admin_Ajax class
		add_action( 'wp_ajax_ai_faq_reset_worker_usage', array( $this, 'ajax_reset_worker_usage' ) );
		add_action( 'wp_ajax_ai_faq_get_worker_status', array( $this, 'ajax_get_worker_status' ) );
		add_action( 'wp_ajax_ai_faq_get_violations', array( $this, 'ajax_get_violations' ) );
		add_action( 'wp_ajax_ai_faq_block_ip', array( $this, 'ajax_block_ip' ) );
		add_action( 'wp_ajax_ai_faq_unblock_ip', array( $this, 'ajax_unblock_ip' ) );
		add_action( 'wp_ajax_ai_faq_get_analytics', array( $this, 'ajax_get_analytics' ) );
		add_action( 'wp_ajax_ai_faq_run_tests', array( $this, 'ajax_run_tests' ) );
	}

	/**
	 * AJAX handler for generating questions.
	 * 
	 * @since 2.1.0
	 */
	public function ajax_generate_questions() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed', '365i-ai-faq-generator' ) );
		}

		$topic = isset( $_POST['topic'] ) ? sanitize_text_field( wp_unslash( $_POST['topic'] ) ) : '';
		$count = isset( $_POST['count'] ) ? intval( $_POST['count'] ) : 12;

		if ( empty( $topic ) ) {
			wp_send_json_error( __( 'Topic is required', '365i-ai-faq-generator' ) );
		}

		$result = $this->manager->generate_questions( $topic, $count );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for generating answers.
	 * 
	 * @since 2.1.0
	 */
	public function ajax_generate_answers() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed', '365i-ai-faq-generator' ) );
		}

		$questions = isset( $_POST['questions'] ) ? $_POST['questions'] : array();

		if ( empty( $questions ) || ! is_array( $questions ) ) {
			wp_send_json_error( __( 'Questions are required', '365i-ai-faq-generator' ) );
		}

		// Sanitize questions array.
		$questions = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $questions ) );

		$result = $this->manager->generate_answers( $questions );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for enhancing FAQ.
	 * 
	 * @since 2.1.0
	 */
	public function ajax_enhance_faq() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed', '365i-ai-faq-generator' ) );
		}

		// We can't directly sanitize nested arrays with sanitize_text_field(),
		// so we have to handle the FAQ data structure carefully.
		$faq_data = isset( $_POST['faq_data'] ) ? $this->sanitize_faq_data( $_POST['faq_data'] ) : array();

		if ( empty( $faq_data ) ) {
			wp_send_json_error( __( 'FAQ data is required', '365i-ai-faq-generator' ) );
		}

		$result = $this->manager->enhance_faq( $faq_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for SEO analysis.
	 * 
	 * @since 2.1.0
	 */
	public function ajax_analyze_seo() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed', '365i-ai-faq-generator' ) );
		}

		$faq_data = isset( $_POST['faq_data'] ) ? $this->sanitize_faq_data( $_POST['faq_data'] ) : array();

		if ( empty( $faq_data ) ) {
			wp_send_json_error( __( 'FAQ data is required', '365i-ai-faq-generator' ) );
		}

		$result = $this->manager->analyze_seo( $faq_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for FAQ extraction.
	 * 
	 * @since 2.1.0
	 */
	public function ajax_extract_faq() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed', '365i-ai-faq-generator' ) );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

		if ( empty( $url ) ) {
			wp_send_json_error( __( 'URL is required', '365i-ai-faq-generator' ) );
		}

		$result = $this->manager->extract_faq( $url );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for topic generation.
	 * 
	 * @since 2.1.0
	 */
	public function ajax_generate_topics() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed', '365i-ai-faq-generator' ) );
		}

		$input = isset( $_POST['input'] ) ? sanitize_textarea_field( wp_unslash( $_POST['input'] ) ) : '';

		if ( empty( $input ) ) {
			wp_send_json_error( __( 'Input text is required', '365i-ai-faq-generator' ) );
		}

		$result = $this->manager->generate_topics( $input );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for testing worker health.
	 * 
	 * @since 2.1.0
	 */
	public function ajax_test_worker() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$worker_name = isset( $_POST['worker_name'] ) ? sanitize_key( $_POST['worker_name'] ) : '';

		if ( empty( $worker_name ) ) {
			wp_send_json_error( __( 'Worker name is required.', '365i-ai-faq-generator' ) );
		}

		// Make a test request to the worker.
		$test_data = array(
			'topic' => 'Test connectivity',
			'count' => 1,
			'test' => true,
		);

		$start_time = microtime( true );
		$result = $this->manager->make_worker_request( $worker_name, $test_data );
		$response_time = round( ( microtime( true ) - $start_time ) * 1000 ); // ms

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'status' => 'error',
				'message' => $result->get_error_message(),
				'response_time' => $response_time,
			) );
		}

		wp_send_json_success( array(
			'status' => 'success',
			'message' => __( 'Worker is responding correctly.', '365i-ai-faq-generator' ),
			'response_time' => $response_time,
			'data' => $result,
		) );
	}

	/**
	 * AJAX handler for resetting worker usage statistics.
	 * 
	 * @since 2.1.0
	 */
	public function ajax_reset_worker_usage() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$worker_name = isset( $_POST['worker_name'] ) ? sanitize_key( $_POST['worker_name'] ) : '';

		if ( empty( $worker_name ) ) {
			wp_send_json_error( __( 'Worker name is required.', '365i-ai-faq-generator' ) );
		}

		// Reset usage for the worker.
		$result = $this->rate_limiter->reset_rate_limit( $worker_name );

		// Log admin action for audit trail.
		error_log( sprintf( 
			'[365i AI FAQ] Admin %s reset usage for worker: %s', 
			wp_get_current_user()->user_login, 
			$worker_name 
		) );

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
	 * @since 2.1.0
	 */
	public function ajax_get_worker_status() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Get worker status.
		$worker_status = $this->manager->get_worker_status();

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

		wp_send_json_success( $status_data );
	}

	/**
	 * AJAX handler for getting rate limit violations.
	 * 
	 * @since 2.1.0
	 */
	public function ajax_get_violations() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$period_hours = isset( $_POST['period'] ) ? intval( $_POST['period'] ) : 24;
		
		// Get violations data.
		$violations_data = $this->security->get_violations_data( $period_hours );

		wp_send_json_success( $violations_data );
	}

	/**
	 * AJAX handler for blocking an IP address.
	 * 
	 * @since 2.1.0
	 */
	public function ajax_block_ip() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$ip_address = isset( $_POST['ip_address'] ) ? sanitize_text_field( wp_unslash( $_POST['ip_address'] ) ) : '';
		$reason = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';
		$duration_hours = isset( $_POST['duration'] ) ? intval( $_POST['duration'] ) : 24;

		if ( empty( $ip_address ) ) {
			wp_send_json_error( __( 'IP address is required.', '365i-ai-faq-generator' ) );
		}

		// Block the IP.
		$result = $this->security->block_ip( $ip_address, $reason, $duration_hours );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX handler for unblocking an IP address.
	 * 
	 * @since 2.1.0
	 */
	public function ajax_unblock_ip() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$ip_address = isset( $_POST['ip_address'] ) ? sanitize_text_field( wp_unslash( $_POST['ip_address'] ) ) : '';

		if ( empty( $ip_address ) ) {
			wp_send_json_error( __( 'IP address is required.', '365i-ai-faq-generator' ) );
		}

		// Unblock the IP.
		$result = $this->security->unblock_ip( $ip_address );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX handler for getting analytics data.
	 * 
	 * @since 2.1.0
	 */
	public function ajax_get_analytics() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		$period_days = isset( $_POST['period'] ) ? intval( $_POST['period'] ) : 30;

		// Get analytics data.
		$analytics_data = $this->analytics->get_analytics_data( $period_days );

		wp_send_json_success( $analytics_data );
	}

	/**
	 * AJAX handler for running tests.
	 * 
	 * @since 2.1.0
	 */
	public function ajax_run_tests() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', '365i-ai-faq-generator' ) );
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_gen_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', '365i-ai-faq-generator' ) );
		}

		// Path to Node.js script
		$script_path = AI_FAQ_GEN_DIR . 'tools/test-workers.js';
		
		// Check if the script exists
		if ( ! file_exists( $script_path ) ) {
			wp_send_json_error( array(
				'message' => __( 'Test script not found.', '365i-ai-faq-generator' ),
			) );
			return;
		}
		
		// Execute the test script
		$node_path = 'node'; // Assuming Node.js is in the system PATH
		$command = escapeshellcmd( "$node_path $script_path --iterations=10 --delay=500" );
		
		// Execute command and capture output
		$output = array();
		$return_var = 0;
		exec( $command, $output, $return_var );
		
		if ( $return_var !== 0 ) {
			wp_send_json_error( array(
				'message' => __( 'Test execution failed.', '365i-ai-faq-generator' ),
				'output' => $output,
				'code' => $return_var,
			) );
			return;
		}
		
		// Import test results into WordPress
		$import_script_path = AI_FAQ_GEN_DIR . 'tools/import-test-data.php';
		
		if ( file_exists( $import_script_path ) ) {
			include_once $import_script_path;
			
			// Create an instance of the importer class and run it
			$importer = new AI_FAQ_Test_Data_Importer();
			$import_results = $importer->run( true ); // Run in silent mode
			
			wp_send_json_success( array(
				'message' => __( 'Tests completed and data imported successfully.', '365i-ai-faq-generator' ),
				'test_output' => $output,
				'import_results' => $import_results,
			) );
		} else {
			wp_send_json_success( array(
				'message' => __( 'Tests completed but import script not found.', '365i-ai-faq-generator' ),
				'test_output' => $output,
			) );
		}
	}

	/**
	 * Sanitize FAQ data.
	 * 
	 * Helper method to sanitize nested FAQ data structures.
	 * 
	 * @since 2.1.0
	 * @param array $faq_data Raw FAQ data.
	 * @return array Sanitized FAQ data.
	 */
	private function sanitize_faq_data( $faq_data ) {
		if ( ! is_array( $faq_data ) ) {
			return array();
		}

		$sanitized = array();

		// Handle topic field.
		if ( isset( $faq_data['topic'] ) ) {
			$sanitized['topic'] = sanitize_text_field( wp_unslash( $faq_data['topic'] ) );
		}

		// Handle faqs array.
		if ( isset( $faq_data['faqs'] ) && is_array( $faq_data['faqs'] ) ) {
			$sanitized['faqs'] = array();

			foreach ( $faq_data['faqs'] as $faq ) {
				$sanitized_faq = array();

				if ( isset( $faq['question'] ) ) {
					$sanitized_faq['question'] = sanitize_text_field( wp_unslash( $faq['question'] ) );
				}

				if ( isset( $faq['answer'] ) ) {
					$sanitized_faq['answer'] = wp_kses_post( wp_unslash( $faq['answer'] ) );
				}

				// Handle additional metadata.
				if ( isset( $faq['meta'] ) && is_array( $faq['meta'] ) ) {
					$sanitized_faq['meta'] = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $faq['meta'] ) );
				}

				$sanitized['faqs'][] = $sanitized_faq;
			}
		}

		// Handle metadata.
		if ( isset( $faq_data['meta'] ) && is_array( $faq_data['meta'] ) ) {
			$sanitized['meta'] = array();

			foreach ( $faq_data['meta'] as $key => $value ) {
				$sanitized['meta'][ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}

		return $sanitized;
	}
}