<?php
/**
 * Admin Menu Management
 *
 * Handles registration of top-level menu and sub-pages for the plugin.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Menu class.
 */
class Queue_Optimizer_Admin_Menu {

	/**
	 * Single instance of the class.
	 *
	 * @var Queue_Optimizer_Admin_Menu
	 */
	private static $instance = null;

	/**
	 * Dashboard page instance.
	 *
	 * @var Queue_Optimizer_Dashboard_Page
	 */
	private $dashboard_page;

	/**
	 * System Info page instance.
	 *
	 * @var Queue_Optimizer_System_Info_Page
	 */
	private $system_info_page;


	/**
	 * Get single instance of the class.
	 *
	 * @return Queue_Optimizer_Admin_Menu
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		$this->init_page_handlers();
	}

	/**
	 * Initialize page handler instances.
	 */
	private function init_page_handlers() {
		$this->dashboard_page = Queue_Optimizer_Dashboard_Page::get_instance();
		$this->system_info_page = Queue_Optimizer_System_Info_Page::get_instance();
	}

	/**
	 * Register admin menus.
	 */
	public function register_menus() {
		// Add top-level menu page.
		add_menu_page(
			__( 'Queue Optimizer', '365i-queue-optimizer' ),
			__( 'Queue Optimizer', '365i-queue-optimizer' ),
			'manage_options',
			'365i-queue-optimizer',
			array( $this, 'render_dashboard_page' ),
			'dashicons-images-alt2',
			60
		);

		// Add Dashboard submenu (same as parent for consistent navigation).
		add_submenu_page(
			'365i-queue-optimizer',
			__( 'Queue Optimizer - Dashboard', '365i-queue-optimizer' ),
			__( 'Dashboard', '365i-queue-optimizer' ),
			'manage_options',
			'365i-queue-optimizer',
			array( $this, 'render_dashboard_page' )
		);

		// Add Queue Activity submenu - Redirect to ActionScheduler.
		add_submenu_page(
			'365i-queue-optimizer',
			__( 'Queue Optimizer - Queue Activity', '365i-queue-optimizer' ),
			__( 'Queue Activity', '365i-queue-optimizer' ),
			'manage_options',
			'365i-queue-activity',
			array( $this, 'redirect_to_action_scheduler' )
		);

		// Add System Info submenu.
		add_submenu_page(
			'365i-queue-optimizer',
			__( 'Queue Optimizer - System Info', '365i-queue-optimizer' ),
			__( 'System Info', '365i-queue-optimizer' ),
			'manage_options',
			'365i-system-info',
			array( $this, 'render_system_info_page' )
		);

		// Apply filters to allow menu extensions.
		do_action( 'queue_optimizer_admin_menu_registered' );
	}

	/**
	 * Render dashboard page.
	 */
	public function render_dashboard_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', '365i-queue-optimizer' ) );
		}

		$this->dashboard_page->render_page();
	}

	/**
	 * Redirect to ActionScheduler interface.
	 */
	public function redirect_to_action_scheduler() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', '365i-queue-optimizer' ) );
		}

		// Redirect to ActionScheduler interface.
		$redirect_url = admin_url( 'tools.php?page=action-scheduler' );
		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Render system info page.
	 */
	public function render_system_info_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', '365i-queue-optimizer' ) );
		}

		$this->system_info_page->render_page();
	}

	/**
	 * Get current admin page slug.
	 *
	 * @return string Current page slug.
	 */
	public function get_current_page() {
		return $_GET['page'] ?? '';
	}

	/**
	 * Check if we're on a plugin admin page.
	 *
	 * @return bool True if on plugin admin page.
	 */
	public function is_plugin_page() {
		$page = $this->get_current_page();
		$plugin_pages = array(
			'365i-queue-optimizer',
			'365i-queue-activity',
			'365i-system-info',
		);

		return in_array( $page, $plugin_pages, true );
	}
}