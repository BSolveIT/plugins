<?php
/**
 * Admin interface class for 365i AI FAQ Generator.
 * 
 * This class handles all admin-related functionality including
 * settings pages, menu items, and admin dashboard features.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Admin
 * @since 2.0.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin interface class.
 * 
 * Manages WordPress admin interface including menus, settings pages,
 * and dashboard functionality.
 * 
 * @since 2.0.0
 */
class AI_FAQ_Admin {

	/**
	 * Plugin core instance.
	 * 
	 * @since 2.0.0
	 * @var AI_FAQ_Core
	 */
	private $core;

	/**
	 * Constructor.
	 * 
	 * Initialize the admin component.
	 * 
	 * @since 2.0.0
	 */
	public function __construct() {
		// We'll get the core instance when needed.
	}

	/**
	 * Initialize the admin component.
	 * 
	 * Set up hooks and filters for admin functionality.
	 * 
	 * @since 2.0.0
	 */
	public function init() {
		// Add admin menu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		
		// Register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		
		// Enqueue admin scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		
		// Add plugin action links.
		add_filter( 'plugin_action_links_' . AI_FAQ_GEN_BASENAME, array( $this, 'add_action_links' ) );
		
		// Handle activation redirect.
		add_action( 'admin_init', array( $this, 'activation_redirect' ) );
		
		// Add dashboard widget.
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
	}

	/**
	 * Add admin menu items.
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

		// Dashboard submenu.
		add_submenu_page(
			'ai-faq-generator',
			__( 'Dashboard', '365i-ai-faq-generator' ),
			__( 'Dashboard', '365i-ai-faq-generator' ),
			'manage_options',
			'ai-faq-generator',
			array( $this, 'display_main_page' )
		);

		// Generator submenu.
		add_submenu_page(
			'ai-faq-generator',
			__( 'FAQ Generator', '365i-ai-faq-generator' ),
			__( 'Generator', '365i-ai-faq-generator' ),
			'manage_options',
			'ai-faq-generator-tool',
			array( $this, 'display_generator_page' )
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
	 * Register plugin settings.
	 * 
	 * @since 2.0.0
	 */
	public function register_settings() {
		// Register main options.
		register_setting(
			'ai_faq_gen_options',
			'ai_faq_gen_options',
			array(
				'sanitize_callback' => array( $this, 'sanitize_options' ),
			)
		);

		// Add settings sections.
		add_settings_section(
			'ai_faq_gen_general',
			__( 'General Settings', '365i-ai-faq-generator' ),
			array( $this, 'general_section_callback' ),
			'ai_faq_gen_settings'
		);

		add_settings_section(
			'ai_faq_gen_workers',
			__( 'Worker Configuration', '365i-ai-faq-generator' ),
			array( $this, 'workers_section_callback' ),
			'ai_faq_gen_workers'
		);

		// Add settings fields.
		add_settings_field(
			'default_faq_count',
			__( 'Default FAQ Count', '365i-ai-faq-generator' ),
			array( $this, 'default_faq_count_callback' ),
			'ai_faq_gen_settings',
			'ai_faq_gen_general'
		);

		add_settings_field(
			'auto_save_interval',
			__( 'Auto-save Interval (minutes)', '365i-ai-faq-generator' ),
			array( $this, 'auto_save_interval_callback' ),
			'ai_faq_gen_settings',
			'ai_faq_gen_general'
		);

		add_settings_field(
			'debug_mode',
			__( 'Debug Mode', '365i-ai-faq-generator' ),
			array( $this, 'debug_mode_callback' ),
			'ai_faq_gen_settings',
			'ai_faq_gen_general'
		);
	}

	/**
	 * Sanitize options.
	 * 
	 * @since 2.0.0
	 * @param array $options Raw options to sanitize.
	 * @return array Sanitized options.
	 */
	public function sanitize_options( $options ) {
		$sanitized = array();

		// Sanitize general settings.
		if ( isset( $options['settings'] ) ) {
			$sanitized['settings'] = array();
			
			if ( isset( $options['settings']['default_faq_count'] ) ) {
				$sanitized['settings']['default_faq_count'] = intval( $options['settings']['default_faq_count'] );
			}
			
			if ( isset( $options['settings']['auto_save_interval'] ) ) {
				$sanitized['settings']['auto_save_interval'] = intval( $options['settings']['auto_save_interval'] );
			}
			
			if ( isset( $options['settings']['debug_mode'] ) ) {
				$sanitized['settings']['debug_mode'] = (bool) $options['settings']['debug_mode'];
			}
		}

		// Sanitize worker settings.
		if ( isset( $options['workers'] ) ) {
			$sanitized['workers'] = array();
			
			foreach ( $options['workers'] as $worker_name => $config ) {
				$sanitized['workers'][ sanitize_key( $worker_name ) ] = array(
					'url' => esc_url_raw( $config['url'] ),
					'enabled' => (bool) $config['enabled'],
					'rate_limit' => intval( $config['rate_limit'] ),
				);
			}
		}

		return $sanitized;
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
	 * Display generator page.
	 * 
	 * @since 2.0.0
	 */
	public function display_generator_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', '365i-ai-faq-generator' ) );
		}

		include AI_FAQ_GEN_DIR . 'templates/admin/generator.php';
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
	 * General settings section callback.
	 * 
	 * @since 2.0.0
	 */
	public function general_section_callback() {
		echo '<p>' . esc_html__( 'Configure general plugin settings.', '365i-ai-faq-generator' ) . '</p>';
	}

	/**
	 * Workers section callback.
	 * 
	 * @since 2.0.0
	 */
	public function workers_section_callback() {
		echo '<p>' . esc_html__( 'Configure Cloudflare worker settings and rate limits.', '365i-ai-faq-generator' ) . '</p>';
	}

	/**
	 * Default FAQ count field callback.
	 * 
	 * @since 2.0.0
	 */
	public function default_faq_count_callback() {
		$options = get_option( 'ai_faq_gen_options', array() );
		$value = isset( $options['settings']['default_faq_count'] ) ? $options['settings']['default_faq_count'] : 12;
		
		printf(
			'<input type="number" id="default_faq_count" name="ai_faq_gen_options[settings][default_faq_count]" value="%d" min="1" max="50" />',
			intval( $value )
		);
		echo '<p class="description">' . esc_html__( 'Default number of FAQ items to generate.', '365i-ai-faq-generator' ) . '</p>';
	}

	/**
	 * Auto-save interval field callback.
	 * 
	 * @since 2.0.0
	 */
	public function auto_save_interval_callback() {
		$options = get_option( 'ai_faq_gen_options', array() );
		$value = isset( $options['settings']['auto_save_interval'] ) ? $options['settings']['auto_save_interval'] : 3;
		
		printf(
			'<input type="number" id="auto_save_interval" name="ai_faq_gen_options[settings][auto_save_interval]" value="%d" min="1" max="60" />',
			intval( $value )
		);
		echo '<p class="description">' . esc_html__( 'Auto-save interval in minutes for local storage.', '365i-ai-faq-generator' ) . '</p>';
	}

	/**
	 * Debug mode field callback.
	 * 
	 * @since 2.0.0
	 */
	public function debug_mode_callback() {
		$options = get_option( 'ai_faq_gen_options', array() );
		$value = isset( $options['settings']['debug_mode'] ) ? $options['settings']['debug_mode'] : false;
		
		printf(
			'<input type="checkbox" id="debug_mode" name="ai_faq_gen_options[settings][debug_mode]" value="1" %s />',
			checked( $value, true, false )
		);
		echo '<label for="debug_mode">' . esc_html__( 'Enable debug mode for troubleshooting.', '365i-ai-faq-generator' ) . '</label>';
	}

	/**
	 * Enqueue admin assets.
	 * 
	 * @since 2.0.0
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Only load on our plugin pages.
		if ( strpos( $hook_suffix, 'ai-faq-generator' ) === false ) {
			return;
		}

		// Enqueue admin CSS.
		wp_enqueue_style(
			'ai-faq-gen-admin',
			AI_FAQ_GEN_URL . 'assets/css/admin.css',
			array(),
			AI_FAQ_GEN_VERSION
		);

		// Enqueue admin JavaScript.
		wp_enqueue_script(
			'ai-faq-gen-admin',
			AI_FAQ_GEN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-util' ),
			AI_FAQ_GEN_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'ai-faq-gen-admin',
			'aiFaqGen',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'ai_faq_gen_nonce' ),
				'strings' => array(
					'loading' => __( 'Loading...', '365i-ai-faq-generator' ),
					'error' => __( 'An error occurred. Please try again.', '365i-ai-faq-generator' ),
					'success' => __( 'Operation completed successfully.', '365i-ai-faq-generator' ),
					'confirm' => __( 'Are you sure?', '365i-ai-faq-generator' ),
				),
			)
		);
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
		$generator_link = '<a href="' . esc_url( admin_url( 'admin.php?page=ai-faq-generator-tool' ) ) . '">' . __( 'Generator', '365i-ai-faq-generator' ) . '</a>';
		
		array_unshift( $links, $settings_link, $generator_link );
		
		return $links;
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
	 * Add dashboard widget.
	 * 
	 * @since 2.0.0
	 */
	public function add_dashboard_widget() {
		wp_add_dashboard_widget(
			'ai_faq_gen_dashboard_widget',
			__( '365i AI FAQ Generator', '365i-ai-faq-generator' ),
			array( $this, 'display_dashboard_widget' )
		);
	}

	/**
	 * Display dashboard widget content.
	 * 
	 * @since 2.0.0
	 */
	public function display_dashboard_widget() {
		$options = get_option( 'ai_faq_gen_options', array() );
		$workers = isset( $options['workers'] ) ? $options['workers'] : array();
		
		echo '<div class="ai-faq-gen-dashboard-widget">';
		echo '<p>' . esc_html__( 'Quick access to AI FAQ Generator tools:', '365i-ai-faq-generator' ) . '</p>';
		
		echo '<p>';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=ai-faq-generator-tool' ) ) . '" class="button button-primary">' . esc_html__( 'Generate FAQ', '365i-ai-faq-generator' ) . '</a> ';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=ai-faq-generator-workers' ) ) . '" class="button">' . esc_html__( 'Worker Status', '365i-ai-faq-generator' ) . '</a>';
		echo '</p>';
		
		// Show worker status summary.
		$enabled_workers = 0;
		$total_workers = count( $workers );
		
		foreach ( $workers as $worker ) {
			if ( $worker['enabled'] ) {
				$enabled_workers++;
			}
		}
		
		if ( $total_workers > 0 ) {
			echo '<p><strong>' . esc_html__( 'Workers Status:', '365i-ai-faq-generator' ) . '</strong> ';
			/* translators: 1: Number of enabled workers, 2: Total number of workers */
			echo sprintf( esc_html__( '%1$d of %2$d workers enabled', '365i-ai-faq-generator' ), $enabled_workers, $total_workers );
			echo '</p>';
		}
		
		echo '</div>';
	}
}