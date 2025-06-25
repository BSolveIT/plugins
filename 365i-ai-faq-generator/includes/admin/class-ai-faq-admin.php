<?php
/**
 * Admin interface coordinator class for 365i AI FAQ Generator.
 * 
 * This class serves as a coordinator for various admin components including
 * menu registration, settings management, AJAX handlers, worker management,
 * analytics, and security features.
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
 * Admin interface coordinator class.
 * 
 * Coordinates all admin functionality components to avoid a "God class".
 * 
 * @since 2.1.0
 */
class AI_FAQ_Admin {

	/**
	 * Admin_Menu component instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Admin_Menu
	 */
	private $menu;

	/**
	 * Admin_Settings component instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Admin_Settings
	 */
	private $settings;

	/**
	 * Admin_Ajax component instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Admin_Ajax
	 */
	private $ajax;

	/**
	 * Admin_Workers component instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Admin_Workers
	 */
	private $workers;

	/**
	 * Admin_Analytics component instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Admin_Analytics
	 */
	private $analytics;

	/**
	 * Admin_Security component instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Admin_Security
	 */
	private $security;


	/**
	 * Admin_Documentation component instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Admin_Documentation
	 */
	private $documentation;

	/**
	 * Admin_AI_Models component instance.
	 *
	 * @since 2.3.1
	 * @var AI_FAQ_Admin_AI_Models
	 */
	private $ai_models;

	/**
	 * Cloudflare_Sync component instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Cloudflare_Sync
	 */
	private $cloudflare_sync;

	/**
	 * Constructor.
	 * 
	 * Initialize the admin component.
	 * 
	 * @since 2.1.0
	 */
	public function __construct() {
		// We'll load dependencies on init.
	}

	/**
	 * Initialize the admin component.
	 *
	 * Set up hooks and filters for admin functionality.
	 *
	 * @since 2.1.0
	 */
	public function init() {
		// DEBUG: Log which admin coordinator class is being used
		ai_faq_log_debug('AI_FAQ_Admin (COORDINATOR): init() called - This is the NEW coordinator class!');
		ai_faq_log_debug('AI_FAQ_Admin (COORDINATOR): File: ' . __FILE__);
		
		// Load dependencies first.
		$this->load_dependencies();

		// Initialize components.
		$this->menu = new AI_FAQ_Admin_Menu();
		$this->settings = new AI_FAQ_Admin_Settings();
		$this->ajax = new AI_FAQ_Admin_Ajax();
		$this->workers = new AI_FAQ_Admin_Workers();
		$this->analytics = new AI_FAQ_Admin_Analytics();
		$this->security = new AI_FAQ_Admin_Security();
		$this->documentation = new AI_FAQ_Admin_Documentation();
		$this->ai_models = new AI_FAQ_Admin_AI_Models();
		$this->cloudflare_sync = new AI_FAQ_Cloudflare_Sync();

		// DEBUG: Log AI models initialization
		ai_faq_log_debug('AI_FAQ_Admin (COORDINATOR): AI Models instance created: ' . get_class($this->ai_models));
		ai_faq_log_debug('AI_FAQ_Admin (COORDINATOR): Cloudflare Sync instance created: ' . get_class($this->cloudflare_sync));

		// Initialize each component.
		$this->menu->init();
		$this->settings->init();
		$this->ajax->init();
		$this->workers->init();
		$this->analytics->init();
		$this->security->init();
		$this->documentation->init();
		
		// DEBUG: Log AI models init call
		ai_faq_log_debug('AI_FAQ_Admin (COORDINATOR): About to call ai_models->init()');
		$this->ai_models->init();
		ai_faq_log_debug('AI_FAQ_Admin (COORDINATOR): AI models init() completed');
		
		// Initialize Cloudflare sync component
		ai_faq_log_debug('AI_FAQ_Admin (COORDINATOR): About to call cloudflare_sync->init()');
		$this->cloudflare_sync->init();
		ai_faq_log_debug('AI_FAQ_Admin (COORDINATOR): Cloudflare sync init() completed');
		

		// Add admin hooks.
		add_action( 'admin_init', array( $this, 'activation_redirect' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		
		ai_faq_log_debug('AI_FAQ_Admin (COORDINATOR): init() completed with AI Models included!');
	}

	/**
	 * Load admin component dependencies.
	 * 
	 * Include all necessary class files for admin functionality.
	 * 
	 * @since 2.1.0
	 */
	private function load_dependencies() {
		$admin_dir = AI_FAQ_GEN_DIR . 'includes/admin/';

		// Load component classes.
		require_once $admin_dir . 'class-ai-faq-admin-menu.php';
		require_once $admin_dir . 'class-ai-faq-admin-settings.php';
		require_once $admin_dir . 'class-ai-faq-admin-ajax.php';
		require_once $admin_dir . 'class-ai-faq-admin-workers.php';
		require_once $admin_dir . 'class-ai-faq-admin-analytics.php';
		require_once $admin_dir . 'class-ai-faq-admin-security.php';
		require_once $admin_dir . 'class-ai-faq-admin-documentation.php';
		require_once $admin_dir . 'class-ai-faq-admin-ai-models.php';
		require_once $admin_dir . 'class-ai-faq-cloudflare-sync.php';
	}

	/**
	 * Handle activation redirect.
	 *
	 * @since 2.0.0
	 */
	public function activation_redirect() {
		// Check if we should redirect after activation.
		if ( get_option( 'ai_faq_gen_activation_redirect', false ) ) {
			delete_option( 'ai_faq_gen_activation_redirect' );
			
			// Only redirect if not activating multiple plugins.
			if ( ! isset( $_GET['activate-multi'] ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=ai-faq-generator' ) );
				exit;
			}
		}
	}

	/**
	 * Enqueue admin assets.
	 *
	 * Load CSS and JavaScript files for admin pages.
	 *
	 * @since 2.1.0
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Only load on our plugin admin pages.
		$plugin_pages = array(
			'toplevel_page_ai-faq-generator',
			'ai-faq-gen_page_ai-faq-generator-workers',
			'ai-faq-gen_page_ai-faq-generator-analytics',
			'ai-faq-gen_page_ai-faq-generator-ai-models',
			'ai-faq-gen_page_ai-faq-generator-settings',
		);

		if ( ! in_array( $hook_suffix, $plugin_pages, true ) ) {
			return;
		}

		// Enqueue core admin CSS first (base foundation styling).
		$admin_css_path = AI_FAQ_GEN_DIR . 'assets/css/admin.css';
		if ( file_exists( $admin_css_path ) ) {
			wp_enqueue_style(
				'ai-faq-admin-core',
				AI_FAQ_GEN_URL . 'assets/css/admin.css',
				array(),
				filemtime( $admin_css_path ),
				'all'
			);
		}

		// Enqueue admin templates CSS for enhanced styling.
		$templates_css_path = AI_FAQ_GEN_DIR . 'assets/css/admin-templates.css';
		if ( file_exists( $templates_css_path ) ) {
			wp_enqueue_style(
				'ai-faq-admin-templates',
				AI_FAQ_GEN_URL . 'assets/css/admin-templates.css',
				array( 'ai-faq-admin-core' ),
				filemtime( $templates_css_path ),
				'all'
			);
		}

		// Enqueue documentation modal assets.
		$doc_css_path = AI_FAQ_GEN_DIR . 'assets/css/documentation-modal.css';
		if ( file_exists( $doc_css_path ) ) {
			wp_enqueue_style(
				'ai-faq-documentation-modal',
				AI_FAQ_GEN_URL . 'assets/css/documentation-modal.css',
				array( 'ai-faq-admin-core' ),
				filemtime( $doc_css_path ),
				'all'
			);
		}

		$doc_js_path = AI_FAQ_GEN_DIR . 'assets/js/documentation-modal.js';
		if ( file_exists( $doc_js_path ) ) {
			wp_enqueue_script(
				'ai-faq-documentation-modal',
				AI_FAQ_GEN_URL . 'assets/js/documentation-modal.js',
				array( 'jquery' ),
				filemtime( $doc_js_path ),
				true
			);

			// Localize script with AJAX data.
			wp_localize_script(
				'ai-faq-documentation-modal',
				'ai_faq_ajax',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'documentation_nonce' => wp_create_nonce( 'ai_faq_documentation_nonce' ),
				)
			);
		}

		// Enqueue page-specific assets based on current page.
		$this->enqueue_page_specific_assets( $hook_suffix );
	}

	/**
	 * Enqueue page-specific CSS and JavaScript assets.
	 *
	 * @since 2.1.0
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	private function enqueue_page_specific_assets( $hook_suffix ) {
		switch ( $hook_suffix ) {
			case 'toplevel_page_ai-faq-generator':
			case 'ai-faq-gen_page_ai-faq-generator-settings':
				$this->enqueue_settings_assets();
				break;

			case 'ai-faq-gen_page_ai-faq-generator-analytics':
				$this->enqueue_analytics_assets();
				break;

			case 'ai-faq-gen_page_ai-faq-generator-ai-models':
				$this->enqueue_ai_models_assets();
				break;

			case 'ai-faq-gen_page_ai-faq-generator-workers':
				$this->enqueue_workers_assets();
				break;
		}
	}

	/**
	 * Enqueue settings page specific assets.
	 *
	 * @since 2.1.0
	 */
	private function enqueue_settings_assets() {
		// Enqueue Cloudflare sync JavaScript
		$cloudflare_sync_js_path = AI_FAQ_GEN_DIR . 'assets/js/cloudflare-sync.js';
		if ( file_exists( $cloudflare_sync_js_path ) ) {
			wp_enqueue_script(
				'ai-faq-cloudflare-sync',
				AI_FAQ_GEN_URL . 'assets/js/cloudflare-sync.js',
				array( 'jquery' ),
				filemtime( $cloudflare_sync_js_path ),
				true
			);

			// Localize script with AJAX data
			wp_localize_script(
				'ai-faq-cloudflare-sync',
				'aiFaqCloudflareSync',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'ai_faq_cloudflare_sync_nonce' ),
				)
			);
		}
	}

	/**
	 * Enqueue analytics dashboard specific assets.
	 *
	 * @since 2.1.0
	 */
	private function enqueue_analytics_assets() {
		$analytics_css_path = AI_FAQ_GEN_DIR . 'assets/css/analytics-dashboard.css';
		if ( file_exists( $analytics_css_path ) ) {
			wp_enqueue_style(
				'ai-faq-analytics-dashboard',
				AI_FAQ_GEN_URL . 'assets/css/analytics-dashboard.css',
				array( 'ai-faq-admin-core', 'ai-faq-admin-templates' ),
				filemtime( $analytics_css_path ),
				'all'
			);
		}

		$analytics_js_path = AI_FAQ_GEN_DIR . 'assets/js/analytics-dashboard.js';
		if ( file_exists( $analytics_js_path ) ) {
			wp_enqueue_script(
				'ai-faq-analytics-dashboard',
				AI_FAQ_GEN_URL . 'assets/js/analytics-dashboard.js',
				array( 'jquery' ),
				filemtime( $analytics_js_path ),
				true
			);
		}
	}

	/**
	 * Enqueue AI models page specific assets.
	 *
	 * @since 2.2.0
	 */
	private function enqueue_ai_models_assets() {
		$ai_models_css_path = AI_FAQ_GEN_DIR . 'assets/css/admin-ai-models.css';
		if ( file_exists( $ai_models_css_path ) ) {
			wp_enqueue_style(
				'ai-faq-admin-ai-models',
				AI_FAQ_GEN_URL . 'assets/css/admin-ai-models.css',
				array( 'ai-faq-admin-core', 'ai-faq-admin-templates' ),
				filemtime( $ai_models_css_path ),
				'all'
			);
		}

		$ai_models_js_path = AI_FAQ_GEN_DIR . 'assets/js/admin-ai-models.js';
		if ( file_exists( $ai_models_js_path ) ) {
			wp_enqueue_script(
				'ai-faq-admin-ai-models',
				AI_FAQ_GEN_URL . 'assets/js/admin-ai-models.js',
				array( 'jquery' ),
				filemtime( $ai_models_js_path ),
				true
			);

			// Localize script with AJAX data and model information.
			wp_localize_script(
				'ai-faq-admin-ai-models',
				'aiFaqAjax',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'ai_faq_admin_nonce' ),
				)
			);

			// Note: Model data is now loaded from KV namespace only, no need to localize for JavaScript
		}
	}


	/**
	 * Enqueue workers page specific assets.
	 *
	 * @since 2.1.0
	 */
	private function enqueue_workers_assets() {
		$worker_test_css_path = AI_FAQ_GEN_DIR . 'assets/css/worker-test-results.css';
		if ( file_exists( $worker_test_css_path ) ) {
			wp_enqueue_style(
				'ai-faq-worker-test-results',
				AI_FAQ_GEN_URL . 'assets/css/worker-test-results.css',
				array( 'ai-faq-admin-core', 'ai-faq-admin-templates' ),
				filemtime( $worker_test_css_path ),
				'all'
			);
		}

		// Load rate limiting CSS since Workers page contains rate limiting configuration.
		$rate_limiting_css_path = AI_FAQ_GEN_DIR . 'assets/css/rate-limiting-admin.css';
		if ( file_exists( $rate_limiting_css_path ) ) {
			wp_enqueue_style(
				'ai-faq-rate-limiting-admin',
				AI_FAQ_GEN_URL . 'assets/css/rate-limiting-admin.css',
				array( 'ai-faq-admin-core', 'ai-faq-admin-templates' ),
				filemtime( $rate_limiting_css_path ),
				'all'
			);
		}

		$workers_js_path = AI_FAQ_GEN_DIR . 'assets/js/workers-admin.js';
		if ( file_exists( $workers_js_path ) ) {
			wp_enqueue_script(
				'ai-faq-workers-admin',
				AI_FAQ_GEN_URL . 'assets/js/workers-admin.js',
				array( 'jquery' ),
				filemtime( $workers_js_path ),
				true
			);
		}
	}

	/**
	 * Get AI Models admin instance.
	 *
	 * @since 2.2.0
	 * @return AI_FAQ_Admin_AI_Models|null AI Models admin instance or null if not initialized.
	 */
	private function get_ai_models_admin_instance() {
		// Return the initialized AI models instance.
		return isset( $this->ai_models ) ? $this->ai_models : null;
	}
}