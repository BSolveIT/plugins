<?php
/**
 * Cloudflare KV Sync Class for 365i AI FAQ Generator.
 * 
 * This class handles syncing WordPress settings to Cloudflare KV storage
 * for dynamic configuration of workers.
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
 * Cloudflare KV sync management class.
 * 
 * @since 2.1.0
 */
class AI_FAQ_Cloudflare_Sync {

	/**
	 * Initialize the sync component.
	 *
	 * Set up hooks for syncing settings to Cloudflare KV.
	 *
	 * @since 2.1.0
	 */
	public function init() {
		// Hook into settings updates to sync to Cloudflare
		add_action( 'update_option_ai_faq_gen_options', array( $this, 'sync_settings_to_cloudflare' ), 10, 2 );
		
		// Add AJAX handlers for manual operations
		add_action( 'wp_ajax_ai_faq_test_cloudflare_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_ai_faq_sync_to_cloudflare', array( $this, 'ajax_sync_settings' ) );
		
		// Add admin notice for sync status
		add_action( 'admin_notices', array( $this, 'sync_status_notices' ) );
	}

	/**
	 * Sync WordPress settings to Cloudflare KV storage.
	 *
	 * @since 2.1.0
	 * @param array $old_value Old option value.
	 * @param array $value New option value.
	 * @return bool Success status.
	 */
	public function sync_settings_to_cloudflare( $old_value, $value ) {
		// Check if rate limiting settings have changed
		if ( ! $this->rate_limiting_settings_changed( $old_value, $value ) ) {
			return true; // No sync needed
		}

		// Extract rate limiting settings
		$rate_settings = $this->extract_rate_limiting_settings( $value );
		
		// Log the sync attempt
		ai_faq_log_info( '[365i AI FAQ] Attempting to sync rate limiting settings to Cloudflare KV' );
		
		// Sync to Cloudflare KV
		$result = $this->push_settings_to_kv( $rate_settings );
		
		if ( $result['success'] ) {
			ai_faq_log_info( '[365i AI FAQ] Successfully synced rate limiting settings to Cloudflare KV' );
			update_option( 'ai_faq_cloudflare_sync_status', array(
				'last_sync' => current_time( 'mysql' ),
				'status' => 'success',
				'message' => 'Settings synced successfully'
			) );
		} else {
			ai_faq_log_error( '[365i AI FAQ] Failed to sync rate limiting settings to Cloudflare KV: ' . $result['message'] );
			update_option( 'ai_faq_cloudflare_sync_status', array(
				'last_sync' => current_time( 'mysql' ),
				'status' => 'error',
				'message' => $result['message']
			) );
		}
		
		return $result['success'];
	}

	/**
	 * Check if rate limiting settings have changed.
	 *
	 * @since 2.1.0
	 * @param array $old_value Old option value.
	 * @param array $new_value New option value.
	 * @return bool True if rate limiting settings changed.
	 */
	private function rate_limiting_settings_changed( $old_value, $new_value ) {
		$rate_limit_keys = array(
			'enable_rate_limiting',
			'rate_limit_requests_per_hour',
			'rate_limit_time_window',
			'rate_limit_block_duration',
			'rate_limit_soft_threshold',
			'rate_limit_hard_threshold',
			'rate_limit_ban_threshold'
		);

		foreach ( $rate_limit_keys as $key ) {
			$old_val = isset( $old_value[ $key ] ) ? $old_value[ $key ] : null;
			$new_val = isset( $new_value[ $key ] ) ? $new_value[ $key ] : null;
			
			if ( $old_val !== $new_val ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extract rate limiting settings from options.
	 *
	 * @since 2.1.0
	 * @param array $options Plugin options.
	 * @return array Rate limiting settings.
	 */
	private function extract_rate_limiting_settings( $options ) {
		return array(
			'enableRateLimiting' => isset( $options['enable_rate_limiting'] ) ? (bool) $options['enable_rate_limiting'] : true,
			'requestsPerHour' => isset( $options['rate_limit_requests_per_hour'] ) ? (int) $options['rate_limit_requests_per_hour'] : 100,
			'timeWindowSeconds' => isset( $options['rate_limit_time_window'] ) ? (int) $options['rate_limit_time_window'] : 3600,
			'blockDurationSeconds' => isset( $options['rate_limit_block_duration'] ) ? (int) $options['rate_limit_block_duration'] : 3600,
			'violationThresholds' => array(
				'soft' => isset( $options['rate_limit_soft_threshold'] ) ? (int) $options['rate_limit_soft_threshold'] : 3,
				'hard' => isset( $options['rate_limit_hard_threshold'] ) ? (int) $options['rate_limit_hard_threshold'] : 6,
				'ban' => isset( $options['rate_limit_ban_threshold'] ) ? (int) $options['rate_limit_ban_threshold'] : 12,
			),
			'lastUpdated' => current_time( 'c' ),
			'source' => 'wordpress',
			'version' => 1
		);
	}

	/**
	 * Push settings to Cloudflare KV storage.
	 *
	 * @since 2.1.0
	 * @param array $settings Rate limiting settings.
	 * @return array Result with success status and message.
	 */
	private function push_settings_to_kv( $settings ) {
		$options = get_option( 'ai_faq_gen_options', array() );
		
		// Check if Cloudflare credentials are configured
		$account_id = isset( $options['cloudflare_account_id'] ) ? $options['cloudflare_account_id'] : '';
		$api_token = isset( $options['cloudflare_api_token'] ) ? $options['cloudflare_api_token'] : '';
		
		if ( empty( $account_id ) || empty( $api_token ) ) {
			return array(
				'success' => false,
				'message' => 'Cloudflare credentials not configured. Please add Account ID and API Token in settings.'
			);
		}

		// Get KV namespace ID (you'll need to configure this)
		$namespace_id = $this->get_kv_namespace_id();
		if ( empty( $namespace_id ) ) {
			return array(
				'success' => false,
				'message' => 'KV namespace ID not configured.'
			);
		}

		// Prepare API request
		$url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/storage/kv/namespaces/{$namespace_id}/values/wordpress_rate_settings";
		
		$headers = array(
			'Authorization' => 'Bearer ' . $api_token,
			'Content-Type' => 'application/json'
		);

		// Make API request
		$response = wp_remote_request( $url, array(
			'method' => 'PUT',
			'headers' => $headers,
			'body' => wp_json_encode( $settings ),
			'timeout' => 30,
			'sslverify' => true
		) );

		// Handle response
		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => 'HTTP request failed: ' . $response->get_error_message()
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code >= 200 && $response_code < 300 ) {
			return array(
				'success' => true,
				'message' => 'Settings successfully synced to Cloudflare KV'
			);
		} else {
			$error_data = json_decode( $response_body, true );
			$error_message = isset( $error_data['errors'][0]['message'] ) ? $error_data['errors'][0]['message'] : 'Unknown API error';
			
			return array(
				'success' => false,
				'message' => "Cloudflare API error ({$response_code}): {$error_message}"
			);
		}
	}

	/**
	 * Get KV namespace ID for rate limiting.
	 *
	 * @since 2.1.0
	 * @return string KV namespace ID.
	 */
	private function get_kv_namespace_id() {
		// This should be configured in settings or as a constant
		// For now, using the same namespace ID from wrangler.toml
		return '77fcd59503e34efcaf4d77d1a550433b';
	}

	/**
	 * Handle AJAX request for testing Cloudflare connection.
	 *
	 * @since 2.1.0
	 */
	public function ajax_test_connection() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'ai_faq_cloudflare_sync_nonce' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Security verification failed.', '365i-ai-faq-generator' ),
			) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have sufficient permissions.', '365i-ai-faq-generator' ),
			) );
		}

		// Get credentials from POST data for testing
		$account_id = sanitize_text_field( $_POST['account_id'] ?? '' );
		$api_token = sanitize_text_field( $_POST['api_token'] ?? '' );

		if ( empty( $account_id ) || empty( $api_token ) ) {
			wp_send_json_error( array(
				'message' => __( 'Please provide both Account ID and API Token.', '365i-ai-faq-generator' ),
			) );
		}

		// Test connection with provided credentials
		$result = $this->test_connection_with_credentials( $account_id, $api_token );

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message' => $result['message'],
			) );
		} else {
			wp_send_json_error( array(
				'message' => $result['message'],
			) );
		}
	}

	/**
	 * Handle AJAX request for syncing settings to Cloudflare.
	 *
	 * @since 2.1.0
	 */
	public function ajax_sync_settings() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'ai_faq_cloudflare_sync_nonce' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Security verification failed.', '365i-ai-faq-generator' ),
			) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have sufficient permissions.', '365i-ai-faq-generator' ),
			) );
		}

		// Parse form data to extract rate limiting settings
		$form_data = array();
		parse_str( $_POST['form_data'] ?? '', $form_data );

		// Log the sync attempt
		ai_faq_log_info( '[365i AI FAQ] Manual sync to Cloudflare requested via AJAX' );
		
		// Extract rate limiting settings from form data
		$rate_settings = $this->extract_rate_limiting_settings( $form_data );
		
		$result = $this->push_settings_to_kv( $rate_settings );
		
		// Update sync status
		update_option( 'ai_faq_cloudflare_sync_status', array(
			'last_sync' => current_time( 'mysql' ),
			'status' => $result['success'] ? 'success' : 'error',
			'message' => $result['message']
		) );

		if ( $result['success'] ) {
			ai_faq_log_info( '[365i AI FAQ] Manual sync to Cloudflare successful: ' . $result['message'] );
			wp_send_json_success( array(
				'message' => $result['message'],
			) );
		} else {
			ai_faq_log_error( '[365i AI FAQ] Manual sync to Cloudflare failed: ' . $result['message'] );
			wp_send_json_error( array(
				'message' => $result['message'],
			) );
		}
	}

	/**
	 * Handle AJAX request for manual sync (legacy method).
	 *
	 * @since 2.1.0
	 */
	public function ajax_sync_cloudflare() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'ai_faq_sync_cloudflare' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Security verification failed.', '365i-ai-faq-generator' ),
			) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have sufficient permissions.', '365i-ai-faq-generator' ),
			) );
		}

		// Get current options and sync
		$options = get_option( 'ai_faq_gen_options', array() );
		$rate_settings = $this->extract_rate_limiting_settings( $options );
		
		$result = $this->push_settings_to_kv( $rate_settings );
		
		// Update sync status
		update_option( 'ai_faq_cloudflare_sync_status', array(
			'last_sync' => current_time( 'mysql' ),
			'status' => $result['success'] ? 'success' : 'error',
			'message' => $result['message']
		) );

		if ( $result['success'] ) {
			wp_send_json_success( array(
				'message' => $result['message'],
			) );
		} else {
			wp_send_json_error( array(
				'message' => $result['message'],
			) );
		}
	}

	/**
	 * Display sync status notices.
	 *
	 * @since 2.1.0
	 */
	public function sync_status_notices() {
		// Only show on plugin settings pages
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'ai-faq-generator' ) === false ) {
			return;
		}

		// Check if we should show sync notices
		if ( ! isset( $_GET['show_sync_status'] ) ) {
			return;
		}

		$sync_status = get_option( 'ai_faq_cloudflare_sync_status', null );
		
		if ( $sync_status && isset( $sync_status['status'] ) ) {
			$notice_class = $sync_status['status'] === 'success' ? 'notice-success' : 'notice-error';
			$last_sync = isset( $sync_status['last_sync'] ) ? $sync_status['last_sync'] : 'Never';
			
			?>
			<div class="notice <?php echo esc_attr( $notice_class ); ?> is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Cloudflare Sync Status:', '365i-ai-faq-generator' ); ?></strong>
					<?php echo esc_html( $sync_status['message'] ); ?>
					<br>
					<em><?php printf( esc_html__( 'Last sync: %s', '365i-ai-faq-generator' ), esc_html( $last_sync ) ); ?></em>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Test Cloudflare KV connectivity.
	 *
	 * @since 2.1.0
	 * @return array Result with success status and message.
	 */
	public function test_cloudflare_connection() {
		$options = get_option( 'ai_faq_gen_options', array() );
		
		$account_id = isset( $options['cloudflare_account_id'] ) ? $options['cloudflare_account_id'] : '';
		$api_token = isset( $options['cloudflare_api_token'] ) ? $options['cloudflare_api_token'] : '';
		
		return $this->test_connection_with_credentials( $account_id, $api_token );
	}

	/**
	 * Test Cloudflare KV connectivity with specific credentials.
	 *
	 * @since 2.1.0
	 * @param string $account_id Cloudflare account ID.
	 * @param string $api_token Cloudflare API token.
	 * @return array Result with success status and message.
	 */
	public function test_connection_with_credentials( $account_id, $api_token ) {
		if ( empty( $account_id ) || empty( $api_token ) ) {
			return array(
				'success' => false,
				'message' => 'Cloudflare credentials not configured'
			);
		}

		$namespace_id = $this->get_kv_namespace_id();
		
		// Test with a simple GET request to check if namespace exists
		$url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/storage/kv/namespaces/{$namespace_id}";
		
		$response = wp_remote_get( $url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type' => 'application/json'
			),
			'timeout' => 10,
			'sslverify' => true
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => 'Connection failed: ' . $response->get_error_message()
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		
		if ( $response_code === 200 ) {
			return array(
				'success' => true,
				'message' => 'Successfully connected to Cloudflare KV'
			);
		} else {
			$response_body = wp_remote_retrieve_body( $response );
			$error_data = json_decode( $response_body, true );
			$error_message = isset( $error_data['errors'][0]['message'] ) ? $error_data['errors'][0]['message'] : "Status {$response_code}";
			
			return array(
				'success' => false,
				'message' => "Cloudflare API error: {$error_message}"
			);
		}
	}

	/**
	 * Get sync status for admin display.
	 *
	 * @since 2.1.0
	 * @return array Sync status information.
	 */
	public function get_sync_status() {
		$sync_status = get_option( 'ai_faq_cloudflare_sync_status', array(
			'last_sync' => 'Never',
			'status' => 'unknown',
			'message' => 'No sync attempted yet'
		) );

		return $sync_status;
	}
}