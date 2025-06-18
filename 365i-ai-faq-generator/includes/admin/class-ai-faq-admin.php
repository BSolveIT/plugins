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
	 * Rate_Limiting_Admin component instance.
	 *
	 * @since 2.1.2
	 * @var AI_FAQ_Rate_Limiting_Admin
	 */
	private $rate_limiting;

	/**
	 * Admin_Documentation component instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Admin_Documentation
	 */
	private $documentation;

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
		// Load dependencies first.
		$this->load_dependencies();

		// Initialize components.
		$this->menu = new AI_FAQ_Admin_Menu();
		$this->settings = new AI_FAQ_Admin_Settings();
		$this->ajax = new AI_FAQ_Admin_Ajax();
		$this->workers = new AI_FAQ_Admin_Workers();
		$this->analytics = new AI_FAQ_Admin_Analytics();
		$this->security = new AI_FAQ_Admin_Security();
		$this->rate_limiting = new AI_FAQ_Rate_Limiting_Admin();
		$this->documentation = new AI_FAQ_Admin_Documentation();

		// Initialize each component.
		$this->menu->init();
		$this->settings->init();
		$this->ajax->init();
		$this->workers->init();
		$this->analytics->init();
		$this->security->init();
		$this->documentation->init();
		// Note: rate_limiting admin handles its own initialization in constructor

		// Add admin hooks.
		add_action( 'admin_init', array( $this, 'activation_redirect' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
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
		require_once $admin_dir . 'class-ai-faq-rate-limiting-admin.php';
		require_once $admin_dir . 'class-ai-faq-admin-documentation.php';
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
		// Debug: Log the hook suffix to see what pages are being loaded
		error_log( '[365i AI FAQ] Admin assets called for hook: ' . $hook_suffix );
		
		// Only load on our plugin admin pages.
		$plugin_pages = array(
			'toplevel_page_ai-faq-generator',
			'ai-faq-gen_page_ai-faq-generator-workers',
			'ai-faq-gen_page_ai-faq-generator-analytics',
			'ai-faq-gen_page_ai-faq-generator-rate-limiting',
			'ai-faq-gen_page_ai-faq-generator-ip-management',
			'ai-faq-gen_page_ai-faq-generator-usage-analytics',
			'ai-faq-gen_page_ai-faq-generator-settings',
		);

		if ( ! in_array( $hook_suffix, $plugin_pages, true ) ) {
			error_log( '[365i AI FAQ] Hook suffix not in plugin pages, skipping asset enqueue' );
			return;
		}

		error_log( '[365i AI FAQ] Enqueuing documentation modal assets for: ' . $hook_suffix );

		// Enqueue documentation modal assets.
		wp_enqueue_style(
			'ai-faq-documentation-modal',
			AI_FAQ_GEN_URL . 'assets/css/documentation-modal.css',
			array(),
			AI_FAQ_GEN_VERSION
		);

		wp_enqueue_script(
			'ai-faq-documentation-modal',
			AI_FAQ_GEN_URL . 'assets/js/documentation-modal.js',
			array( 'jquery' ),
			AI_FAQ_GEN_VERSION,
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
		
		error_log( '[365i AI FAQ] Documentation modal assets enqueued successfully' );
	}
}