<?php
/**
 * Admin menu management class for 365i AI FAQ Generator.
 * 
 * This class handles admin menu registration, page rendering,
 * and plugin action links.
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
 * Admin menu management class.
 * 
 * @since 2.1.0
 */
class AI_FAQ_Admin_Menu {

	/**
	 * Initialize the menu component.
	 *
	 * Set up hooks for menu registration and plugin actions.
	 *
	 * @since 2.1.0
	 */
	public function init() {
		// Add admin menu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		
		// Add plugin action links.
		add_filter( 'plugin_action_links_' . AI_FAQ_GEN_BASENAME, array( $this, 'add_action_links' ) );
		
	}

	/**
	 * Add admin menu items.
	 * 
	 * Only adds worker configuration and settings - no FAQ generation.
	 * 
	 * @since 2.0.0
	 */
	public function add_admin_menu() {
		// Main menu page.
		add_menu_page(
			__( '365i AI FAQ Generator', '365i-ai-faq-generator' ),
			__( 'AI FAQ Gen', '365i-ai-faq-generator' ),
			'manage_options',
			'ai-faq-generator',
			array( $this, 'display_main_page' ),
			'dashicons-format-chat',
			30
		);

		// Workers submenu.
		add_submenu_page(
			'ai-faq-generator',
			__( 'Worker Configuration', '365i-ai-faq-generator' ),
			__( 'Workers', '365i-ai-faq-generator' ),
			'manage_options',
			'ai-faq-generator-workers',
			array( $this, 'display_workers_page' )
		);

		// Analytics submenu.
		add_submenu_page(
			'ai-faq-generator',
			__( 'Analytics Dashboard', '365i-ai-faq-generator' ),
			__( 'Analytics', '365i-ai-faq-generator' ),
			'manage_options',
			'ai-faq-generator-analytics',
			array( $this, 'display_analytics_page' )
		);


		// AI Models submenu.
		add_submenu_page(
			'ai-faq-generator',
			__( 'AI Model Configuration', '365i-ai-faq-generator' ),
			__( 'AI Models', '365i-ai-faq-generator' ),
			'manage_options',
			'ai-faq-generator-ai-models',
			array( $this, 'display_ai_models_page' )
		);

		// Settings submenu.
		add_submenu_page(
			'ai-faq-generator',
			__( 'Settings', '365i-ai-faq-generator' ),
			__( 'Settings', '365i-ai-faq-generator' ),
			'manage_options',
			'ai-faq-generator-settings',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Display main admin page.
	 * 
	 * @since 2.0.0
	 */
	public function display_main_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', '365i-ai-faq-generator' ) );
		}

		include AI_FAQ_GEN_DIR . 'templates/admin/dashboard.php';
	}

	/**
	 * Display workers configuration page.
	 * 
	 * @since 2.0.0
	 */
	public function display_workers_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', '365i-ai-faq-generator' ) );
		}

		include AI_FAQ_GEN_DIR . 'templates/admin/workers.php';
	}

	/**
	 * Display settings page.
	 * 
	 * @since 2.0.0
	 */
	public function display_settings_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', '365i-ai-faq-generator' ) );
		}

		include AI_FAQ_GEN_DIR . 'templates/admin/settings.php';
	}

	/**
	 * Display analytics dashboard page.
	 *
	 * @since 2.0.2
	 */
	public function display_analytics_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', '365i-ai-faq-generator' ) );
		}

		include AI_FAQ_GEN_DIR . 'templates/admin/analytics.php';
	}


	/**
	 * Display AI models configuration page.
	 *
	 * @since 2.2.0
	 */
	public function display_ai_models_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', '365i-ai-faq-generator' ) );
		}

		include AI_FAQ_GEN_DIR . 'templates/admin/ai-models.php';
	}

	/**
	 * Add plugin action links.
	 *
	 * @since 2.0.0
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=ai-faq-generator-settings' ) ) . '">' . __( 'Settings', '365i-ai-faq-generator' ) . '</a>';
		$workers_link = '<a href="' . esc_url( admin_url( 'admin.php?page=ai-faq-generator-workers' ) ) . '">' . __( 'Workers', '365i-ai-faq-generator' ) . '</a>';
		
		array_unshift( $links, $settings_link, $workers_link );
		
		return $links;
	}

}