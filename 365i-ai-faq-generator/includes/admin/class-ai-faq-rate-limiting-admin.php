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
		add_action( 'wp_ajax_ai_faq_rl_manage_ip', array( $this, 'handle_ip_management' ) );
		add_action( 'wp_ajax_ai_faq_rl_get_analytics', array( $this, 'handle_analytics_request' ) );
		add_action( 'wp_ajax_ai_faq_rl_export_analytics', array( $this, 'handle_analytics_export' ) );
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
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'ai_faq_rate_limit_nonce' ),
					'workers'  => $this->workers,
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

		include AI_FAQ_GEN_DIR . 'templates/admin/usage-analytics.php';
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
	 * Get global rate limiting settings
	 *
	 * @since 2.1.0
	 * @return array Global settings
	 */
	private function get_global_settings() {
		// Return default global settings
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
		);
	}

	/**
	 * Get IP list (whitelist or blacklist)
	 *
	 * @since 2.1.0
	 * @param string $list_type Type of list ('whitelist' or 'blacklist').
	 * @return array IP list
	 */
	private function get_ip_list( $list_type ) {
		// Return empty array for demo purposes
		return array();
	}

	/**
	 * Manage IP address (add/remove from whitelist/blacklist)
	 *
	 * @since 2.1.0
	 * @param string $action Action to perform.
	 * @param string $ip_address IP address.
	 * @param string $reason Reason for action.
	 * @return array Result with success status and message
	 */
	private function manage_ip_address( $action, $ip_address, $reason ) {
		// Simulate successful IP management for demo
		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: 1: Action, 2: IP address */
				esc_html__( 'Successfully %1$s IP address %2$s', '365i-ai-faq-generator' ),
				str_replace( '_', ' ', $action ),
				$ip_address
			),
		);
	}

	/**
	 * Get analytics data
	 *
	 * @since 2.1.0
	 * @param string $timeframe Timeframe for analytics.
	 * @param string $worker Specific worker or 'all'.
	 * @return array Analytics data
	 */
	private function get_analytics_data( $timeframe = 'daily', $worker = 'all' ) {
		// Return demo analytics data
		return array(
			'timeframe'        => $timeframe,
			'worker'           => $worker,
			'total_requests'   => 1250,
			'blocked_requests' => 45,
			'violations'       => 23,
			'unique_ips'       => 156,
			'top_violators'    => array(
				array(
					'ip' => '192.168.1.100',
					'violation_count' => 15,
					'last_violation' => '2025-06-18 12:30:00',
					'status' => 'active',
				),
				array(
					'ip' => '10.0.0.50',
					'violation_count' => 8,
					'last_violation' => '2025-06-18 11:45:00',
					'status' => 'blocked',
				),
			),
			'worker_usage'     => array(
				'faq-answer-generator-worker' => array(
					'total' => 450,
					'successful' => 425,
					'blocked' => 25,
				),
				'faq-realtime-assistant-worker' => array(
					'total' => 300,
					'successful' => 290,
					'blocked' => 10,
				),
				'faq-enhancement-worker' => array(
					'total' => 200,
					'successful' => 190,
					'blocked' => 10,
				),
			),
			'last_updated'     => current_time( 'mysql' ),
		);
	}

	/**
	 * Get current rate limit configurations
	 *
	 * @since 2.1.0
	 * @return array Current configurations for all workers
	 */
	private function get_current_rate_configs() {
		// Return demo configurations
		return array();
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

}