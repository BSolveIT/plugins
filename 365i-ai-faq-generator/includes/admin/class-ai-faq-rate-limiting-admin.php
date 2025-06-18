<?php
/**
 * Rate Limiting Administration Interface
 *
 * @package    AI_FAQ_Generator
 * @subpackage Admin
 * @since      2.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rate Limiting Administration Class
 *
 * Provides WordPress admin interface for managing Cloudflare Worker rate limits
 * via KV storage integration.
 *
 * @since 2.1.0
 */
class AI_FAQ_Rate_Limiting_Admin {

	/**
	 * Cloudflare API configuration
	 *
	 * @var array
	 */
	private $cloudflare_config;

	/**
	 * Cloudflare KV namespace IDs
	 *
	 * @var array
	 */
	private $kv_namespaces = array(
		'FAQ_RATE_LIMITS'  => '77fcd59503e34efcaf4d77d1a550433b',
		'FAQ_CACHE'        => '8a2d095ab02947408cbf81e70a3e7f8a',
		'FAQ_IP_WHITELIST' => '98e217d3ffdf439f9080f29b9868dce0',
		'FAQ_IP_BLACKLIST' => 'ea349175a0dd4a01923c9da59e794b9b',
		'FAQ_VIOLATIONS'   => '99d05632fa564f95bd47f22891f943aa',
		'FAQ_ANALYTICS'    => 'a3573648cc1d4c1990a06090dab3e646',
	);

	/**
	 * Available workers
	 *
	 * @var array
	 */
	private $workers = array(
		'faq-answer-generator-worker'  => 'FAQ Answer Generator',
		'faq-realtime-assistant-worker' => 'Realtime Assistant',
		'faq-enhancement-worker'       => 'FAQ Enhancement',
		'faq-seo-analyzer-worker'      => 'SEO Analyzer',
		'faq-proxy-fetch'              => 'Proxy Fetch',
		'url-to-faq-generator-worker'  => 'URL to FAQ Generator',
	);

	/**
	 * Initialize the admin interface
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		$this->cloudflare_config = $this->get_cloudflare_config();
		
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		// Use unique action names to avoid conflicts with main plugin AJAX handlers
		add_action( 'wp_ajax_ai_faq_rl_update_rate_limits', array( $this, 'handle_rate_limit_update' ) );
		add_action( 'wp_ajax_ai_faq_rl_save_global_settings', array( $this, 'handle_global_settings_save' ) );
		add_action( 'wp_ajax_ai_faq_rl_reset_worker_config', array( $this, 'handle_worker_config_reset' ) );
		add_action( 'wp_ajax_ai_faq_rl_manage_ip', array( $this, 'handle_ip_management' ) );
		add_action( 'wp_ajax_ai_faq_rl_get_analytics', array( $this, 'handle_analytics_request' ) );
		add_action( 'wp_ajax_ai_faq_rl_export_analytics', array( $this, 'handle_analytics_export' ) );
	}

	/**
	 * Handle rate limit configuration updates via AJAX
	 *
	 * @since 2.1.2
	 */
	public function handle_rate_limit_update() {
		// Verify nonce and permissions
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_rate_limit_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$worker_id = sanitize_text_field( $_POST['worker_id'] ?? '' );
		
		// Parse violation thresholds
		$violation_thresholds = array(
			'soft' => absint( $_POST['violationThresholds']['soft'] ?? 3 ),
			'hard' => absint( $_POST['violationThresholds']['hard'] ?? 6 ),
			'ban'  => absint( $_POST['violationThresholds']['ban'] ?? 12 ),
		);
		
		$config = array(
			'hourlyLimit'          => absint( $_POST['hourlyLimit'] ?? 10 ),
			'dailyLimit'           => absint( $_POST['dailyLimit'] ?? 50 ),
			'weeklyLimit'          => absint( $_POST['weeklyLimit'] ?? 250 ),
			'monthlyLimit'         => absint( $_POST['monthlyLimit'] ?? 1000 ),
			'violationThresholds'  => $violation_thresholds,
			'enabled'              => filter_var( $_POST['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN ),
		);

		// Validate worker ID
		if ( ! array_key_exists( $worker_id, $this->workers ) ) {
			wp_send_json_error( array( 'message' => 'Invalid worker ID' ) );
		}

		$result = $this->save_worker_rate_config_to_kv( $worker_id, $config );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * Handle worker configuration reset via AJAX
	 *
	 * @since 2.1.3
	 */
	public function handle_worker_config_reset() {
		// Verify nonce and permissions
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_rate_limit_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$worker_id = sanitize_text_field( $_POST['worker_id'] ?? '' );

		// Validate worker ID
		if ( ! array_key_exists( $worker_id, $this->workers ) ) {
			wp_send_json_error( array( 'message' => 'Invalid worker ID' ) );
		}

		$result = $this->reset_worker_config_in_kv( $worker_id );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * Handle global settings save via AJAX
	 *
	 * @since 2.1.2
	 */
	public function handle_global_settings_save() {
		// Verify nonce and permissions
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_rate_limit_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$settings = array(
			'enableRateLimiting'              => filter_var( $_POST['enableRateLimiting'] ?? true, FILTER_VALIDATE_BOOLEAN ),
			'enableIPWhitelist'               => filter_var( $_POST['enableIPWhitelist'] ?? true, FILTER_VALIDATE_BOOLEAN ),
			'enableIPBlacklist'               => filter_var( $_POST['enableIPBlacklist'] ?? true, FILTER_VALIDATE_BOOLEAN ),
			'enableViolationTracking'         => filter_var( $_POST['enableViolationTracking'] ?? true, FILTER_VALIDATE_BOOLEAN ),
			'enableAnalytics'                 => filter_var( $_POST['enableAnalytics'] ?? true, FILTER_VALIDATE_BOOLEAN ),
			'adminNotificationEmail'          => sanitize_email( $_POST['adminNotificationEmail'] ?? get_option( 'admin_email' ) ),
			'notifyOnViolations'              => filter_var( $_POST['notifyOnViolations'] ?? true, FILTER_VALIDATE_BOOLEAN ),
			'violationNotificationThreshold' => absint( $_POST['violationNotificationThreshold'] ?? 5 ),
		);

		// Validate email
		if ( ! is_email( $settings['adminNotificationEmail'] ) ) {
			wp_send_json_error( array( 'message' => 'Invalid email address provided.' ) );
		}

		$result = $this->save_global_settings_to_kv( $settings );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * Get Cloudflare configuration from plugin settings
	 *
	 * @since 2.1.0
	 * @return array Cloudflare configuration
	 */
	private function get_cloudflare_config() {
		$options = get_option( 'ai_faq_gen_options', array() );
		
		return array(
			'account_id' => isset( $options['cloudflare_account_id'] ) ? $options['cloudflare_account_id'] : '',
			'api_token'  => isset( $options['cloudflare_api_token'] ) ? $options['cloudflare_api_token'] : '',
			'zone_id'    => '', // Zone ID not needed for workers.dev domains
		);
	}

	/**
	 * Initialize admin settings
	 *
	 * @since 2.1.0
	 */
	public function admin_init() {
		// Register settings for rate limiting
		register_setting(
			'ai_faq_rate_limiting_settings',
			'ai_faq_global_rate_settings',
			array( $this, 'validate_global_settings' )
		);

	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @since 2.1.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on our plugin pages.
		if ( strpos( $hook, 'ai-faq-generator' ) === false ) {
			return;
		}

		// Enqueue rate limiting assets for rate limiting pages.
		if ( strpos( $hook, 'ai-faq-generator-rate-limiting' ) !== false ||
		     strpos( $hook, 'ai-faq-generator-ip-management' ) !== false ||
		     strpos( $hook, 'ai-faq-generator-usage-analytics' ) !== false ) {
			
			wp_enqueue_style(
				'ai-faq-rate-limiting-admin',
				AI_FAQ_GEN_URL . 'assets/css/rate-limiting-admin.css',
				array(),
				AI_FAQ_GEN_VERSION
			);

			// Enqueue admin templates styling for improved spacing and layout
			wp_enqueue_style(
				'ai-faq-admin-templates',
				AI_FAQ_GEN_URL . 'assets/css/admin-templates.css',
				array(),
				AI_FAQ_GEN_VERSION
			);

			wp_enqueue_script(
				'ai-faq-rate-limiting-admin',
				AI_FAQ_GEN_URL . 'assets/js/rate-limiting-admin.js',
				array( 'jquery', 'wp-util' ),
				AI_FAQ_GEN_VERSION,
				true
			);

			wp_localize_script(
				'ai-faq-rate-limiting-admin',
				'aiFAQRateLimit',
				array(
					'ajax_url'    => admin_url( 'admin-ajax.php' ),
					'nonce'       => wp_create_nonce( 'ai_faq_rate_limit_nonce' ),
					'workers'     => $this->workers,
					'currentUser' => $this->get_current_user_display_name(),
				)
			);
		}
	}

	/**
	 * Display rate limiting configuration page
	 *
	 * @since 2.1.0
	 */
	public function display_rate_limiting_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', '365i-ai-faq-generator' ) );
		}

		$current_configs = $this->get_current_rate_configs();
		$global_settings = $this->get_global_settings();

		include AI_FAQ_GEN_DIR . 'templates/admin/rate-limiting-config.php';
	}

	/**
	 * Display IP management page
	 *
	 * @since 2.1.0
	 */
	public function display_ip_management_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', '365i-ai-faq-generator' ) );
		}

		$whitelist_ips = $this->get_ip_list( 'whitelist' );
		$blacklist_ips = $this->get_ip_list( 'blacklist' );

		include AI_FAQ_GEN_DIR . 'templates/admin/ip-management.php';
	}

	/**
	 * Display usage analytics page
	 *
	 * @since 2.1.0
	 */
	public function display_usage_analytics_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', '365i-ai-faq-generator' ) );
		}

		// Initialize analytics data with defaults to prevent undefined variable warnings
		$analytics_data = $this->get_analytics_data();
		
		// Ensure we have default structure even if API call fails
		if ( empty( $analytics_data ) || ! is_array( $analytics_data ) ) {
			$analytics_data = array(
				'total_requests'   => 0,
				'blocked_requests' => 0,
				'violations'       => 0,
				'unique_ips'       => 0,
				'top_violators'    => array(),
				'worker_usage'     => array(),
				'last_updated'     => current_time( 'mysql' ),
			);
		}

		// Add diagnostic information for debugging
		$analytics_data['diagnostic_info'] = $this->get_kv_diagnostic_info();

		include AI_FAQ_GEN_DIR . 'templates/admin/usage-analytics.php';
	}

	/**
	 * Get diagnostic information about KV connection
	 *
	 * @since 2.1.1
	 * @return array Diagnostic information
	 */
	private function get_kv_diagnostic_info() {
		$diagnostic = array(
			'credentials_configured' => false,
			'account_id_set'         => false,
			'api_token_set'          => false,
			'test_connection'        => 'not_tested',
			'namespaces'             => $this->kv_namespaces,
		);

		// Check if credentials are configured
		$diagnostic['account_id_set'] = ! empty( $this->cloudflare_config['account_id'] );
		$diagnostic['api_token_set'] = ! empty( $this->cloudflare_config['api_token'] );
		$diagnostic['credentials_configured'] = $diagnostic['account_id_set'] && $diagnostic['api_token_set'];

		// If credentials are configured, test a simple connection
		if ( $diagnostic['credentials_configured'] ) {
			$diagnostic['test_connection'] = $this->test_kv_connection();
		}

		return $diagnostic;
	}

	/**
	 * Test KV connection with a simple API call
	 *
	 * @since 2.1.1
	 * @return string Connection status ('success', 'failed', 'error')
	 */
	private function test_kv_connection() {
		$namespace_id = $this->kv_namespaces['FAQ_RATE_LIMITS'];
		$account_id = $this->cloudflare_config['account_id'];
		$api_token = $this->cloudflare_config['api_token'];

		// Try to list keys in the FAQ_RATE_LIMITS namespace (lightweight test)
		$api_url = sprintf(
			'https://api.cloudflare.com/client/v4/accounts/%s/storage/kv/namespaces/%s/keys?limit=10',
			$account_id,
			$namespace_id
		);

		$response = wp_remote_get( $api_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 10, // Short timeout for diagnostics
		) );

		if ( is_wp_error( $response ) ) {
			return 'error: ' . $response->get_error_message();
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		
		if ( 200 === $response_code ) {
			return 'success';
		} else {
			$response_body = wp_remote_retrieve_body( $response );
			$error_data = json_decode( $response_body, true );
			$error_message = isset( $error_data['errors'][0]['message'] )
				? $error_data['errors'][0]['message']
				: 'Unknown error';
			return 'failed: ' . $error_message;
		}
	}

	/**
	 * Handle IP management actions via AJAX
	 *
	 * @since 2.1.0
	 */
	public function handle_ip_management() {
		// Verify nonce and permissions
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_rate_limit_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$action = sanitize_text_field( $_POST['ip_action'] ?? '' );
		$ip_address = sanitize_text_field( $_POST['ip_address'] ?? '' );
		$reason = sanitize_textarea_field( $_POST['reason'] ?? '' );

		if ( ! filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
			wp_send_json_error( array( 'message' => 'Invalid IP address format' ) );
		}

		$result = $this->manage_ip_address( $action, $ip_address, $reason );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * Handle analytics data requests via AJAX
	 *
	 * @since 2.1.0
	 */
	public function handle_analytics_request() {
		// Verify nonce and permissions
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ai_faq_rate_limit_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$timeframe = sanitize_text_field( $_POST['timeframe'] ?? 'daily' );
		$worker = sanitize_text_field( $_POST['worker'] ?? 'all' );

		$analytics_data = $this->get_analytics_data( $timeframe, $worker );

		wp_send_json_success( $analytics_data );
	}

	/**
	 * Handle analytics export via AJAX
	 *
	 * @since 2.1.2
	 */
	public function handle_analytics_export() {
		check_ajax_referer( 'ai_faq_rate_limit_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$timeframe = sanitize_text_field( $_GET['timeframe'] ?? 'daily' );
		$worker = sanitize_text_field( $_GET['worker'] ?? 'all' );

		$analytics_data = $this->get_analytics_data( $timeframe, $worker );

		// Prepare data for CSV export
		$csv_data = $this->prepare_analytics_csv( $analytics_data, $timeframe, $worker );

		// Set headers for download
		$filename = 'analytics-' . $timeframe . '-' . $worker . '-' . gmdate( 'Y-m-d' ) . '.csv';
		
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: 0' );

		// Output CSV
		echo $csv_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Get global rate limiting settings from Cloudflare KV
	 *
	 * @since 2.1.0
	 * @return array Global settings
	 */
	private function get_global_settings() {
		// Check for cached data first (cache for 15 minutes)
		$cache_key = 'ai_faq_global_rate_settings';
		$cached_data = get_transient( $cache_key );
		
		if ( $cached_data !== false ) {
			return $cached_data;
		}

		// Validate Cloudflare configuration
		if ( empty( $this->cloudflare_config['account_id'] ) || empty( $this->cloudflare_config['api_token'] ) ) {
			return $this->get_default_global_settings();
		}

		// Fetch global settings from KV
		$global_settings = $this->fetch_global_settings_from_kv();
		
		// If API call failed, return default settings
		if ( empty( $global_settings ) || ! is_array( $global_settings ) ) {
			return $this->get_default_global_settings();
		}

		// Cache the result for 15 minutes
		set_transient( $cache_key, $global_settings, 900 );

		return $global_settings;
	}

	/**
	 * Fetch global settings from Cloudflare KV
	 *
	 * @since 2.1.2
	 * @return array|false Global settings or false on failure
	 */
	private function fetch_global_settings_from_kv() {
		$namespace_id = $this->kv_namespaces['FAQ_RATE_LIMITS'];
		$account_id = $this->cloudflare_config['account_id'];
		$api_token = $this->cloudflare_config['api_token'];

		// KV key for global settings
		$kv_key = 'global_settings';
		
		// Build API URL
		$api_url = sprintf(
			'https://api.cloudflare.com/client/v4/accounts/%s/storage/kv/namespaces/%s/values/%s',
			$account_id,
			$namespace_id,
			$kv_key
		);

		// Make API request
		$response = wp_remote_get( $api_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			error_log( 'AI FAQ Rate Limiting: Failed to fetch global settings from KV: ' . $response->get_error_message() );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Handle 404 (key not found) - return defaults
		if ( 404 === $response_code ) {
			return $this->get_default_global_settings();
		}

		if ( 200 !== $response_code ) {
			error_log( 'AI FAQ Rate Limiting: KV API returned ' . $response_code . ' for global settings: ' . $response_body );
			return false;
		}

		$settings_data = json_decode( $response_body, true );
		
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( 'AI FAQ Rate Limiting: Failed to parse global settings JSON: ' . json_last_error_msg() );
			return false;
		}

		// Merge with defaults to ensure all required fields exist
		return array_merge( $this->get_default_global_settings(), $settings_data );
	}

	/**
	 * Get default global settings
	 *
	 * @since 2.1.2
	 * @return array Default global settings
	 */
	private function get_default_global_settings() {
		return array(
			'enableRateLimiting'            => true,
			'enableIPWhitelist'             => true,
			'enableIPBlacklist'             => true,
			'enableViolationTracking'       => true,
			'enableAnalytics'               => true,
			'adminNotificationEmail'        => get_option( 'admin_email' ),
			'notifyOnViolations'            => true,
			'violationNotificationThreshold' => 5,
			'source'                        => 'default',
			'last_updated'                  => current_time( 'mysql' ),
		);
	}

	/**
	 * Get IP list (whitelist or blacklist) from Cloudflare KV
	 *
	 * @since 2.1.0
	 * @param string $list_type Type of list ('whitelist' or 'blacklist').
	 * @return array IP list
	 */
	private function get_ip_list( $list_type ) {
		// Validate list type
		if ( ! in_array( $list_type, array( 'whitelist', 'blacklist' ), true ) ) {
			return array();
		}

		// Check for cached data first (cache for 10 minutes)
		$cache_key = 'ai_faq_ip_' . $list_type;
		$cached_data = get_transient( $cache_key );
		
		if ( $cached_data !== false ) {
			return $cached_data;
		}

		// Validate Cloudflare configuration
		if ( empty( $this->cloudflare_config['account_id'] ) || empty( $this->cloudflare_config['api_token'] ) ) {
			return array();
		}

		// Get namespace ID for the list type
		$namespace_key = 'whitelist' === $list_type ? 'FAQ_IP_WHITELIST' : 'FAQ_IP_BLACKLIST';
		$namespace_id = $this->kv_namespaces[ $namespace_key ];
		
		// Fetch IP list from KV
		$ip_list = $this->fetch_ip_list_from_kv( $namespace_id, $list_type );
		
		// Cache the result for 10 minutes
		set_transient( $cache_key, $ip_list, 600 );

		return $ip_list;
	}

	/**
	 * Fetch IP list from Cloudflare KV namespace
	 *
	 * @since 2.1.2
	 * @param string $namespace_id KV namespace ID.
	 * @param string $list_type Type of list for logging.
	 * @return array IP list
	 */
	private function fetch_ip_list_from_kv( $namespace_id, $list_type ) {
		$account_id = $this->cloudflare_config['account_id'];
		$api_token = $this->cloudflare_config['api_token'];

		// List all keys in the namespace
		$api_url = sprintf(
			'https://api.cloudflare.com/client/v4/accounts/%s/storage/kv/namespaces/%s/keys',
			$account_id,
			$namespace_id
		);

		$response = wp_remote_get( $api_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			error_log( 'AI FAQ Rate Limiting: Failed to fetch ' . $list_type . ' from KV: ' . $response->get_error_message() );
			return array();
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $response_code ) {
			error_log( 'AI FAQ Rate Limiting: KV API returned ' . $response_code . ' for ' . $list_type . ': ' . $response_body );
			return array();
		}

		$data = json_decode( $response_body, true );
		
		if ( json_last_error() !== JSON_ERROR_NONE || ! isset( $data['result'] ) ) {
			error_log( 'AI FAQ Rate Limiting: Failed to parse ' . $list_type . ' JSON: ' . json_last_error_msg() );
			return array();
		}

		// Fetch actual values for each IP to get metadata
		$ip_list = array();
		foreach ( $data['result'] as $key_info ) {
			$ip_address = $key_info['name'];
			
			// Validate IP address
			if ( filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
				// Fetch the actual value for this IP
				$ip_metadata = $this->fetch_ip_metadata_from_kv( $namespace_id, $ip_address );
				
				// Merge with key info
				$ip_entry = array(
					'ip'           => $ip_address,
					'added_date'   => isset( $ip_metadata['added_date'] ) ? $ip_metadata['added_date'] : ( isset( $key_info['modified'] ) ? $key_info['modified'] : 'Unknown' ),
					'reason'       => isset( $ip_metadata['reason'] ) ? $ip_metadata['reason'] : 'No reason provided',
					'added_by'     => isset( $ip_metadata['added_by'] ) ? $this->get_user_display_name( $ip_metadata['added_by'] ) : 'Unknown',
					'list_type'    => $list_type,
				);
				
				$ip_list[] = $ip_entry;
			}
		}

		return $ip_list;
	}

	/**
	 * Fetch metadata for a specific IP from KV
	 *
	 * @since 2.1.3
	 * @param string $namespace_id KV namespace ID.
	 * @param string $ip_address IP address.
	 * @return array IP metadata
	 */
	private function fetch_ip_metadata_from_kv( $namespace_id, $ip_address ) {
		$account_id = $this->cloudflare_config['account_id'];
		$api_token = $this->cloudflare_config['api_token'];

		// Get the value for this specific IP
		$api_url = sprintf(
			'https://api.cloudflare.com/client/v4/accounts/%s/storage/kv/namespaces/%s/values/%s',
			$account_id,
			$namespace_id,
			$ip_address
		);

		$response = wp_remote_get( $api_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $response_code ) {
			return array();
		}

		$metadata = json_decode( $response_body, true );
		
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return array();
		}

		return is_array( $metadata ) ? $metadata : array();
	}

	/**
	 * Get display name for user ID
	 *
	 * @since 2.1.3
	 * @param int|string $user_id User ID.
	 * @return string Display name
	 */
	private function get_user_display_name( $user_id ) {
		if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
			return 'Unknown';
		}

		$user = get_user_by( 'id', $user_id );
		
		if ( ! $user ) {
			return 'Unknown';
		}

		return $user->display_name ?: $user->user_login;
	}

	/**
	 * Get current user's display name for script localization
	 *
	 * @since 2.1.3
	 * @return string Current user display name
	 */
	private function get_current_user_display_name() {
		$current_user = wp_get_current_user();
		
		if ( ! $current_user || ! $current_user->exists() ) {
			return 'Unknown User';
		}

		return $current_user->display_name ?: $current_user->user_login;
	}

	/**
	 * Manage IP address (add/remove from whitelist/blacklist) in Cloudflare KV
	 *
	 * @since 2.1.0
	 * @param string $action Action to perform.
	 * @param string $ip_address IP address.
	 * @param string $reason Reason for action.
	 * @return array Result with success status and message
	 */
	private function manage_ip_address( $action, $ip_address, $reason ) {
		// Validate Cloudflare configuration
		if ( empty( $this->cloudflare_config['account_id'] ) || empty( $this->cloudflare_config['api_token'] ) ) {
			return array(
				'success' => false,
				'message' => esc_html__( 'Cloudflare API credentials not configured. Please check your settings.', '365i-ai-faq-generator' ),
			);
		}

		// Determine namespace and operation based on action
		$operation_result = $this->execute_ip_management_operation( $action, $ip_address, $reason );
		
		// Clear relevant cache
		$this->clear_ip_management_cache( $action );

		return $operation_result;
	}

	/**
	 * Execute IP management operation in Cloudflare KV
	 *
	 * @since 2.1.2
	 * @param string $action Action to perform.
	 * @param string $ip_address IP address.
	 * @param string $reason Reason for action.
	 * @return array Result with success status and message
	 */
	private function execute_ip_management_operation( $action, $ip_address, $reason ) {
		$account_id = $this->cloudflare_config['account_id'];
		$api_token = $this->cloudflare_config['api_token'];

		// Determine namespace and HTTP method based on action
		switch ( $action ) {
			case 'add_to_whitelist':
				$namespace_id = $this->kv_namespaces['FAQ_IP_WHITELIST'];
				$method = 'PUT';
				$success_message = esc_html__( 'Successfully added IP to whitelist', '365i-ai-faq-generator' );
				break;
			
			case 'add_to_blacklist':
				$namespace_id = $this->kv_namespaces['FAQ_IP_BLACKLIST'];
				$method = 'PUT';
				$success_message = esc_html__( 'Successfully added IP to blacklist', '365i-ai-faq-generator' );
				break;
			
			case 'remove_from_whitelist':
				$namespace_id = $this->kv_namespaces['FAQ_IP_WHITELIST'];
				$method = 'DELETE';
				$success_message = esc_html__( 'Successfully removed IP from whitelist', '365i-ai-faq-generator' );
				break;
			
			case 'remove_from_blacklist':
				$namespace_id = $this->kv_namespaces['FAQ_IP_BLACKLIST'];
				$method = 'DELETE';
				$success_message = esc_html__( 'Successfully removed IP from blacklist', '365i-ai-faq-generator' );
				break;
			
			default:
				return array(
					'success' => false,
					'message' => esc_html__( 'Invalid action specified.', '365i-ai-faq-generator' ),
				);
		}

		// Build API URL
		$api_url = sprintf(
			'https://api.cloudflare.com/client/v4/accounts/%s/storage/kv/namespaces/%s/values/%s',
			$account_id,
			$namespace_id,
			$ip_address
		);

		// Prepare request arguments
		$request_args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 30,
		);

		// Add body for PUT requests (adding IPs)
		if ( 'PUT' === $method ) {
			$ip_data = array(
				'ip'         => $ip_address,
				'reason'     => sanitize_textarea_field( $reason ),
				'added_date' => current_time( 'mysql' ),
				'added_by'   => get_current_user_id(),
			);
			$request_args['body'] = wp_json_encode( $ip_data );
		}

		// Make API request
		$response = wp_remote_request( $api_url, $request_args );

		// Check for errors
		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: Error message */
					esc_html__( 'Failed to connect to Cloudflare API: %s', '365i-ai-faq-generator' ),
					$response->get_error_message()
				),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Check response code
		if ( ! in_array( $response_code, array( 200, 204 ), true ) ) {
			$error_data = json_decode( $response_body, true );
			$error_message = isset( $error_data['errors'][0]['message'] )
				? $error_data['errors'][0]['message']
				: 'Unknown API error';

			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: 1: HTTP code, 2: Error message */
					esc_html__( 'Cloudflare API error (%1$d): %2$s', '365i-ai-faq-generator' ),
					$response_code,
					$error_message
				),
			);
		}

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: 1: Success message, 2: IP address */
				'%1$s: %2$s',
				$success_message,
				$ip_address
			),
		);
	}

	/**
	 * Clear IP management cache after operations
	 *
	 * @since 2.1.2
	 * @param string $action Action that was performed.
	 */
	private function clear_ip_management_cache( $action ) {
		// Clear cache for both whitelist and blacklist as operations might affect both
		delete_transient( 'ai_faq_ip_whitelist' );
		delete_transient( 'ai_faq_ip_blacklist' );
		
		// Also clear analytics cache as IP changes might affect analytics
		$timeframes = array( 'hourly', 'daily', 'weekly', 'monthly' );
		$workers = array_merge( array( 'all' ), array_keys( $this->workers ) );
		
		foreach ( $timeframes as $timeframe ) {
			foreach ( $workers as $worker ) {
				delete_transient( 'ai_faq_analytics_' . $timeframe . '_' . $worker );
			}
		}
	}

	/**
	 * Get analytics data from Cloudflare KV storage
	 *
	 * @since 2.1.0
	 * @param string $timeframe Timeframe for analytics.
	 * @param string $worker Specific worker or 'all'.
	 * @return array Analytics data
	 */
	private function get_analytics_data( $timeframe = 'daily', $worker = 'all' ) {
		// Check for cached data first (cache for 5 minutes)
		$cache_key = 'ai_faq_analytics_' . $timeframe . '_' . $worker;
		$cached_data = get_transient( $cache_key );
		
		if ( $cached_data !== false ) {
			return $cached_data;
		}

		// Validate Cloudflare configuration
		if ( empty( $this->cloudflare_config['account_id'] ) || empty( $this->cloudflare_config['api_token'] ) ) {
			return $this->get_fallback_analytics_data( $timeframe, $worker );
		}

		// Get analytics data from Cloudflare KV
		$analytics_data = $this->fetch_analytics_from_kv( $timeframe, $worker );
		
		// If API call failed, return fallback data
		if ( empty( $analytics_data ) || ! is_array( $analytics_data ) ) {
			return $this->get_fallback_analytics_data( $timeframe, $worker );
		}

		// Cache the result for 5 minutes
		set_transient( $cache_key, $analytics_data, 300 );

		return $analytics_data;
	}

	/**
	 * Fetch analytics data from Cloudflare KV namespace
	 *
	 * @since 2.1.2
	 * @param string $timeframe Timeframe for analytics.
	 * @param string $worker Specific worker or 'all'.
	 * @return array|false Analytics data or false on failure
	 */
	private function fetch_analytics_from_kv( $timeframe, $worker ) {
		$namespace_id = $this->kv_namespaces['FAQ_ANALYTICS'];
		$account_id = $this->cloudflare_config['account_id'];
		$api_token = $this->cloudflare_config['api_token'];

		// Construct KV key based on timeframe and worker
		$kv_key = $this->build_analytics_kv_key( $timeframe, $worker );
		
		// Build API URL for KV value retrieval
		$api_url = sprintf(
			'https://api.cloudflare.com/client/v4/accounts/%s/storage/kv/namespaces/%s/values/%s',
			$account_id,
			$namespace_id,
			$kv_key
		);

		// Make API request
		$response = wp_remote_get( $api_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 30,
		) );

		// Check for errors
		if ( is_wp_error( $response ) ) {
			error_log( 'AI FAQ Rate Limiting: Failed to fetch analytics from KV: ' . $response->get_error_message() );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Handle 404 (key not found) - return empty analytics
		if ( 404 === $response_code ) {
			return $this->get_empty_analytics_structure( $timeframe, $worker );
		}

		// Handle other errors
		if ( $response_code !== 200 ) {
			error_log( 'AI FAQ Rate Limiting: KV API returned ' . $response_code . ': ' . $response_body );
			return false;
		}

		// Parse JSON response
		$analytics_data = json_decode( $response_body, true );
		
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( 'AI FAQ Rate Limiting: Failed to parse analytics JSON: ' . json_last_error_msg() );
			return false;
		}

		// Ensure data structure is valid and add metadata
		return $this->validate_and_enhance_analytics_data( $analytics_data, $timeframe, $worker );
	}

	/**
	 * Build KV key for analytics data
	 *
	 * @since 2.1.2
	 * @param string $timeframe Timeframe for analytics.
	 * @param string $worker Specific worker or 'all'.
	 * @return string KV key
	 */
	private function build_analytics_kv_key( $timeframe, $worker ) {
		$date_suffix = '';
		
		switch ( $timeframe ) {
			case 'hourly':
				$date_suffix = gmdate( 'Y-m-d-H' );
				break;
			case 'daily':
				$date_suffix = gmdate( 'Y-m-d' );
				break;
			case 'weekly':
				$date_suffix = gmdate( 'Y-W' );
				break;
			case 'monthly':
				$date_suffix = gmdate( 'Y-m' );
				break;
			default:
				$date_suffix = gmdate( 'Y-m-d' );
		}

		return sprintf( 'analytics_%s_%s_%s', $timeframe, $worker, $date_suffix );
	}

	/**
	 * Get empty analytics structure for new periods
	 *
	 * @since 2.1.2
	 * @param string $timeframe Timeframe for analytics.
	 * @param string $worker Specific worker or 'all'.
	 * @return array Empty analytics structure
	 */
	private function get_empty_analytics_structure( $timeframe, $worker ) {
		return array(
			'timeframe'        => $timeframe,
			'worker'           => $worker,
			'total_requests'   => 0,
			'blocked_requests' => 0,
			'violations'       => 0,
			'unique_ips'       => 0,
			'top_violators'    => array(),
			'worker_usage'     => array(),
			'last_updated'     => current_time( 'mysql' ),
			'data_source'      => 'kv_empty',
		);
	}

	/**
	 * Validate and enhance analytics data from KV
	 *
	 * @since 2.1.2
	 * @param array  $data Raw analytics data from KV.
	 * @param string $timeframe Timeframe for analytics.
	 * @param string $worker Specific worker or 'all'.
	 * @return array Enhanced analytics data
	 */
	private function validate_and_enhance_analytics_data( $data, $timeframe, $worker ) {
		// Ensure required fields exist with defaults
		$validated_data = array(
			'timeframe'        => $timeframe,
			'worker'           => $worker,
			'total_requests'   => isset( $data['total_requests'] ) ? absint( $data['total_requests'] ) : 0,
			'blocked_requests' => isset( $data['blocked_requests'] ) ? absint( $data['blocked_requests'] ) : 0,
			'violations'       => isset( $data['violations'] ) ? absint( $data['violations'] ) : 0,
			'unique_ips'       => isset( $data['unique_ips'] ) ? absint( $data['unique_ips'] ) : 0,
			'top_violators'    => isset( $data['top_violators'] ) && is_array( $data['top_violators'] ) ? $data['top_violators'] : array(),
			'worker_usage'     => isset( $data['worker_usage'] ) && is_array( $data['worker_usage'] ) ? $data['worker_usage'] : array(),
			'last_updated'     => isset( $data['last_updated'] ) ? sanitize_text_field( $data['last_updated'] ) : current_time( 'mysql' ),
			'data_source'      => 'kv_live',
		);

		// Validate and sanitize top_violators array
		if ( ! empty( $validated_data['top_violators'] ) ) {
			$validated_data['top_violators'] = array_map( function( $violator ) {
				return array(
					'ip'              => filter_var( $violator['ip'] ?? '', FILTER_VALIDATE_IP ) ?: 'Invalid IP',
					'violation_count' => absint( $violator['violation_count'] ?? 0 ),
					'last_violation'  => sanitize_text_field( $violator['last_violation'] ?? 'Unknown' ),
					'status'          => in_array( $violator['status'] ?? '', array( 'active', 'blocked' ), true ) ? $violator['status'] : 'active',
				);
			}, $validated_data['top_violators'] );
		}

		// Validate and sanitize worker_usage array
		if ( ! empty( $validated_data['worker_usage'] ) ) {
			$sanitized_usage = array();
			foreach ( $validated_data['worker_usage'] as $worker_name => $usage ) {
				$sanitized_name = sanitize_text_field( $worker_name );
				$sanitized_usage[ $sanitized_name ] = array(
					'total'      => absint( $usage['total'] ?? 0 ),
					'successful' => absint( $usage['successful'] ?? 0 ),
					'blocked'    => absint( $usage['blocked'] ?? 0 ),
				);
			}
			$validated_data['worker_usage'] = $sanitized_usage;
		}

		return $validated_data;
	}

	/**
	 * Get fallback analytics data when KV is unavailable
	 *
	 * @since 2.1.2
	 * @param string $timeframe Timeframe for analytics.
	 * @param string $worker Specific worker or 'all'.
	 * @return array Fallback analytics data
	 */
	private function get_fallback_analytics_data( $timeframe, $worker ) {
		return array(
			'timeframe'        => $timeframe,
			'worker'           => $worker,
			'total_requests'   => 0,
			'blocked_requests' => 0,
			'violations'       => 0,
			'unique_ips'       => 0,
			'top_violators'    => array(),
			'worker_usage'     => array(),
			'last_updated'     => current_time( 'mysql' ),
			'data_source'      => 'fallback',
			'error_message'    => 'Unable to connect to Cloudflare KV. Please check your API credentials.',
		);
	}

	/**
	 * Get current rate limit configurations from Cloudflare KV
	 *
	 * @since 2.1.0
	 * @return array Current configurations for all workers
	 */
	private function get_current_rate_configs() {
		// Check for cached data first (cache for 10 minutes)
		$cache_key = 'ai_faq_rate_configs';
		$cached_data = get_transient( $cache_key );
		
		if ( $cached_data !== false ) {
			return $cached_data;
		}

		// Validate Cloudflare configuration
		if ( empty( $this->cloudflare_config['account_id'] ) || empty( $this->cloudflare_config['api_token'] ) ) {
			return array();
		}

		// Fetch rate configurations from KV
		$rate_configs = $this->fetch_rate_configs_from_kv();
		
		// Cache the result for 10 minutes
		set_transient( $cache_key, $rate_configs, 600 );

		return $rate_configs;
	}

	/**
	 * Fetch rate configurations from Cloudflare KV
	 *
	 * @since 2.1.2
	 * @return array Rate configurations
	 */
	private function fetch_rate_configs_from_kv() {
		$namespace_id = $this->kv_namespaces['FAQ_RATE_LIMITS'];
		$account_id = $this->cloudflare_config['account_id'];
		$api_token = $this->cloudflare_config['api_token'];

		$rate_configs = array();

		// Fetch configuration for each worker
		foreach ( $this->workers as $worker_id => $worker_name ) {
			$kv_key = 'worker_config_' . $worker_id;
			
			$api_url = sprintf(
				'https://api.cloudflare.com/client/v4/accounts/%s/storage/kv/namespaces/%s/values/%s',
				$account_id,
				$namespace_id,
				$kv_key
			);

			$response = wp_remote_get( $api_url, array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_token,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 30,
			) );

			if ( is_wp_error( $response ) ) {
				error_log( 'AI FAQ Rate Limiting: Failed to fetch config for ' . $worker_id . ': ' . $response->get_error_message() );
				continue;
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );

			// Handle 404 (key not found) - use default config
			if ( 404 === $response_code ) {
				$rate_configs[ $worker_id ] = $this->get_default_worker_config( $worker_id );
				continue;
			}

			if ( 200 !== $response_code ) {
				error_log( 'AI FAQ Rate Limiting: KV API returned ' . $response_code . ' for ' . $worker_id . ': ' . $response_body );
				continue;
			}

			$config_data = json_decode( $response_body, true );
			
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				error_log( 'AI FAQ Rate Limiting: Failed to parse config JSON for ' . $worker_id . ': ' . json_last_error_msg() );
				$rate_configs[ $worker_id ] = $this->get_default_worker_config( $worker_id );
				continue;
			}

			// Merge with defaults and validate
			$rate_configs[ $worker_id ] = array_merge(
				$this->get_default_worker_config( $worker_id ),
				$config_data
			);
		}

		return $rate_configs;
	}

	/**
	 * Get default configuration for a worker
	 *
	 * @since 2.1.2
	 * @param string $worker_id Worker identifier.
	 * @return array Default worker configuration
	 */
	private function get_default_worker_config( $worker_id ) {
		return array(
			'worker_id'           => $worker_id,
			'hourlyLimit'         => 10,
			'dailyLimit'          => 50,
			'weeklyLimit'         => 250,
			'monthlyLimit'        => 1000,
			'violationThresholds' => array(
				'soft' => 3,
				'hard' => 6,
				'ban'  => 12,
			),
			'enabled'             => true,
			'source'              => 'default',
			'last_updated'        => current_time( 'mysql' ),
		);
	}

	/**
	 * Save global settings to Cloudflare KV
	 *
	 * @since 2.1.2
	 * @param array $settings Global settings to save.
	 * @return array Result with success status and message
	 */
	public function save_global_settings_to_kv( $settings ) {
		// Validate Cloudflare configuration
		if ( empty( $this->cloudflare_config['account_id'] ) || empty( $this->cloudflare_config['api_token'] ) ) {
			return array(
				'success' => false,
				'message' => esc_html__( 'Cloudflare API credentials not configured.', '365i-ai-faq-generator' ),
			);
		}

		$namespace_id = $this->kv_namespaces['FAQ_RATE_LIMITS'];
		$account_id = $this->cloudflare_config['account_id'];
		$api_token = $this->cloudflare_config['api_token'];

		// Prepare settings data
		$settings_data = array_merge( $settings, array(
			'last_updated' => current_time( 'mysql' ),
			'updated_by'   => get_current_user_id(),
		) );

		// Build API URL
		$api_url = sprintf(
			'https://api.cloudflare.com/client/v4/accounts/%s/storage/kv/namespaces/%s/values/global_settings',
			$account_id,
			$namespace_id
		);

		// Make API request
		$response = wp_remote_request( $api_url, array(
			'method'  => 'PUT',
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $settings_data ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: Error message */
					esc_html__( 'Failed to save to Cloudflare KV: %s', '365i-ai-faq-generator' ),
					$response->get_error_message()
				),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		
		if ( ! in_array( $response_code, array( 200, 204 ), true ) ) {
			$response_body = wp_remote_retrieve_body( $response );
			$error_data = json_decode( $response_body, true );
			$error_message = isset( $error_data['errors'][0]['message'] )
				? $error_data['errors'][0]['message']
				: 'Unknown API error';

			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: 1: HTTP code, 2: Error message */
					esc_html__( 'Cloudflare API error (%1$d): %2$s', '365i-ai-faq-generator' ),
					$response_code,
					$error_message
				),
			);
		}

		// Clear cache
		delete_transient( 'ai_faq_global_rate_settings' );

		return array(
			'success' => true,
			'message' => esc_html__( 'Global settings saved successfully to Cloudflare KV.', '365i-ai-faq-generator' ),
		);
	}

	/**
	 * Save rate configuration for a worker to Cloudflare KV
	 *
	 * @since 2.1.2
	 * @param string $worker_id Worker identifier.
	 * @param array  $config Worker configuration.
	 * @return array Result with success status and message
	 */
	public function save_worker_rate_config_to_kv( $worker_id, $config ) {
		// Validate Cloudflare configuration
		if ( empty( $this->cloudflare_config['account_id'] ) || empty( $this->cloudflare_config['api_token'] ) ) {
			return array(
				'success' => false,
				'message' => esc_html__( 'Cloudflare API credentials not configured.', '365i-ai-faq-generator' ),
			);
		}

		$namespace_id = $this->kv_namespaces['FAQ_RATE_LIMITS'];
		$account_id = $this->cloudflare_config['account_id'];
		$api_token = $this->cloudflare_config['api_token'];

		// Prepare config data
		$config_data = array_merge( $config, array(
			'worker_id'    => $worker_id,
			'source'       => 'custom',
			'last_updated' => current_time( 'mysql' ),
			'updated_by'   => get_current_user_id(),
		) );

		// Build API URL
		$kv_key = 'worker_config_' . $worker_id;
		$api_url = sprintf(
			'https://api.cloudflare.com/client/v4/accounts/%s/storage/kv/namespaces/%s/values/%s',
			$account_id,
			$namespace_id,
			$kv_key
		);

		// Make API request
		$response = wp_remote_request( $api_url, array(
			'method'  => 'PUT',
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $config_data ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: Error message */
					esc_html__( 'Failed to save worker config to Cloudflare KV: %s', '365i-ai-faq-generator' ),
					$response->get_error_message()
				),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		
		if ( ! in_array( $response_code, array( 200, 204 ), true ) ) {
			$response_body = wp_remote_retrieve_body( $response );
			$error_data = json_decode( $response_body, true );
			$error_message = isset( $error_data['errors'][0]['message'] )
				? $error_data['errors'][0]['message']
				: 'Unknown API error';

			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: 1: HTTP code, 2: Error message */
					esc_html__( 'Cloudflare API error (%1$d): %2$s', '365i-ai-faq-generator' ),
					$response_code,
					$error_message
				),
			);
		}

		// Clear cache
		delete_transient( 'ai_faq_rate_configs' );

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %s: Worker name */
				esc_html__( 'Rate configuration saved successfully for %s.', '365i-ai-faq-generator' ),
				$this->workers[ $worker_id ] ?? $worker_id
			),
		);
	}

	/**
	 * Prepare analytics data for CSV export
	 *
	 * @since 2.1.2
	 * @param array  $data Analytics data.
	 * @param string $timeframe Timeframe.
	 * @param string $worker Worker filter.
	 * @return string CSV content
	 */
	private function prepare_analytics_csv( $data, $timeframe, $worker ) {
		$csv = array();
		
		// Add header
		$csv[] = sprintf( 'Analytics Export - %s - %s - %s', $timeframe, $worker, gmdate( 'Y-m-d H:i:s' ) );
		$csv[] = '';
		
		// Add summary data
		$csv[] = 'Summary';
		$csv[] = 'Metric,Value';
		$csv[] = sprintf( 'Total Requests,%d', $data['total_requests'] ?? 0 );
		$csv[] = sprintf( 'Blocked Requests,%d', $data['blocked_requests'] ?? 0 );
		$csv[] = sprintf( 'Violations,%d', $data['violations'] ?? 0 );
		$csv[] = sprintf( 'Unique IPs,%d', $data['unique_ips'] ?? 0 );
		$csv[] = '';
		
		// Add top violators
		if ( ! empty( $data['top_violators'] ) ) {
			$csv[] = 'Top Violators';
			$csv[] = 'IP Address,Violations,Last Violation,Status';
			foreach ( $data['top_violators'] as $violator ) {
				$csv[] = sprintf(
					'%s,%d,%s,%s',
					$violator['ip'] ?? 'N/A',
					$violator['violation_count'] ?? 0,
					$violator['last_violation'] ?? 'Unknown',
					$violator['status'] ?? 'active'
				);
			}
			$csv[] = '';
		}
		
		// Add worker usage
		if ( ! empty( $data['worker_usage'] ) ) {
			$csv[] = 'Worker Usage';
			$csv[] = 'Worker,Total Requests,Successful,Blocked,Success Rate';
			foreach ( $data['worker_usage'] as $worker_name => $usage ) {
				$total = $usage['total'] ?? 0;
				$successful = $usage['successful'] ?? 0;
				$rate = $total > 0 ? round( ( $successful / $total ) * 100, 1 ) : 0;
				
				$csv[] = sprintf(
					'%s,%d,%d,%d,%s%%',
					$worker_name,
					$total,
					$successful,
					$usage['blocked'] ?? 0,
					$rate
				);
			}
		}
		
		return implode( "\n", $csv );
	}

	/**
		* Reset worker configuration to defaults by deleting custom config from KV
		*
		* @since 2.1.3
		* @param string $worker_id Worker identifier.
		* @return array Result with success status and message
		*/
	private function reset_worker_config_in_kv( $worker_id ) {
		// Validate Cloudflare configuration
		if ( empty( $this->cloudflare_config['account_id'] ) || empty( $this->cloudflare_config['api_token'] ) ) {
			return array(
				'success' => false,
				'message' => esc_html__( 'Cloudflare API credentials not configured.', '365i-ai-faq-generator' ),
			);
		}

		$namespace_id = $this->kv_namespaces['FAQ_RATE_LIMITS'];
		$account_id = $this->cloudflare_config['account_id'];
		$api_token = $this->cloudflare_config['api_token'];

		// Build API URL to delete the custom configuration
		$kv_key = 'worker_config_' . $worker_id;
		$api_url = sprintf(
			'https://api.cloudflare.com/client/v4/accounts/%s/storage/kv/namespaces/%s/values/%s',
			$account_id,
			$namespace_id,
			$kv_key
		);

		// Make DELETE request to remove custom configuration
		$response = wp_remote_request( $api_url, array(
			'method'  => 'DELETE',
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: Error message */
					esc_html__( 'Failed to reset worker config in Cloudflare KV: %s', '365i-ai-faq-generator' ),
					$response->get_error_message()
				),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		
		// Accept both 200 (deleted) and 404 (already didn't exist) as success
		if ( ! in_array( $response_code, array( 200, 204, 404 ), true ) ) {
			$response_body = wp_remote_retrieve_body( $response );
			$error_data = json_decode( $response_body, true );
			$error_message = isset( $error_data['errors'][0]['message'] )
				? $error_data['errors'][0]['message']
				: 'Unknown API error';

			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: 1: HTTP code, 2: Error message */
					esc_html__( 'Cloudflare API error (%1$d): %2$s', '365i-ai-faq-generator' ),
					$response_code,
					$error_message
				),
			);
		}

		// Clear cache
		delete_transient( 'ai_faq_rate_configs' );

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %s: Worker name */
				esc_html__( 'Configuration reset to defaults for %s.', '365i-ai-faq-generator' ),
				$this->workers[ $worker_id ] ?? $worker_id
			),
		);
	}

}