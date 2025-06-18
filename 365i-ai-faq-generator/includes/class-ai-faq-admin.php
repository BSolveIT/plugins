<?php
/**
 * Admin interface bootstrap class for 365i AI FAQ Generator.
 * 
 * This class serves as a bootstrap for the admin interface and
 * delegates all functionality to specialized admin components.
 * 
 * @package AI_FAQ_Generator
 * @since 2.0.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin interface bootstrap class.
 * 
 * Bootstrap class that delegates to specialized admin components.
 * 
 * @since 2.0.0
 */
class AI_FAQ_Admin {

	/**
	 * Admin component instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Admin
	 */
	private $admin_component;

	/**
	 * Constructor.
	 * 
	 * Initialize the admin component.
	 * 
	 * @since 2.0.0
	 */
	public function __construct() {
		// We'll initialize components in init()
	}

	/**
	 * Initialize the admin component.
	 *
	 * Load admin component dependencies and set up hooks.
	 *
	 * @since 2.0.0
	 */
	public function init() {
		// Load admin components directly without requiring a duplicate class
		require_once AI_FAQ_GEN_DIR . 'includes/admin/class-ai-faq-admin-menu.php';
		require_once AI_FAQ_GEN_DIR . 'includes/admin/class-ai-faq-admin-settings.php';
		require_once AI_FAQ_GEN_DIR . 'includes/admin/class-ai-faq-admin-ajax.php';
		require_once AI_FAQ_GEN_DIR . 'includes/admin/class-ai-faq-admin-workers.php';
		require_once AI_FAQ_GEN_DIR . 'includes/admin/class-ai-faq-admin-analytics.php';
		require_once AI_FAQ_GEN_DIR . 'includes/admin/class-ai-faq-admin-security.php';
		require_once AI_FAQ_GEN_DIR . 'includes/admin/class-ai-faq-rate-limiting-admin.php';
		
		// Create and initialize admin menu component
		$menu = new AI_FAQ_Admin_Menu();
		$menu->init();
		
		// Create and initialize settings component
		$settings = new AI_FAQ_Admin_Settings();
		$settings->init();
		
		// Create and initialize AJAX component
		$ajax = new AI_FAQ_Admin_Ajax();
		$ajax->init();
		
		// Initialize other components as needed
		$workers = new AI_FAQ_Admin_Workers();
		$workers->init();
		
		// Create and initialize rate limiting admin component
		$rate_limiting = new AI_FAQ_Rate_Limiting_Admin();
		// Note: AI_FAQ_Rate_Limiting_Admin initializes itself in constructor
	}
}