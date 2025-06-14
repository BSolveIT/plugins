<?php
/**
 * Plugin Name: 365i Queue Optimizer
 * Plugin URI: https://www.365i.co.uk/
 * Description: A lightweight WordPress plugin to manage and optimize background queue processing with native WP scheduling.
 * Version: 1.1.0
 * Author: 365i
 * Author URI: https://www.365i.co.uk/
 * Text Domain: 365i-queue-optimizer
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'QUEUE_OPTIMIZER_VERSION', '1.1.0' );
define( 'QUEUE_OPTIMIZER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QUEUE_OPTIMIZER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QUEUE_OPTIMIZER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class.
 */
class Queue_Optimizer_Plugin {

	/**
	 * Single instance of the plugin.
	 *
	 * @var Queue_Optimizer_Plugin
	 */
	private static $instance = null;

	/**
	 * Get single instance of the plugin.
	 *
	 * @return Queue_Optimizer_Plugin
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
		$this->init();
	}

	/**
	 * Initialize the plugin.
	 */
	private function init() {
		// Load plugin textdomain.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Include required files.
		$this->include_files();

		// Initialize components.
		add_action( 'init', array( $this, 'init_components' ) );

		// Activation and deactivation hooks.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		
		// Uninstall hook.
		register_uninstall_hook( __FILE__, array( 'Queue_Optimizer_Plugin', 'uninstall' ) );

		// Enqueue admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Apply image processor preference.
		add_action( 'init', array( $this, 'set_image_processor_preference' ) );
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'365i-queue-optimizer',
			false,
			dirname( QUEUE_OPTIMIZER_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Include required files.
	 */
	private function include_files() {
		require_once QUEUE_OPTIMIZER_PLUGIN_DIR . 'includes/class-scheduler.php';
		
		if ( is_admin() ) {
			require_once QUEUE_OPTIMIZER_PLUGIN_DIR . 'admin/class-settings-page.php';
		}
	}

	/**
	 * Initialize plugin components.
	 */
	public function init_components() {
		// Initialize scheduler.
		Queue_Optimizer_Scheduler::get_instance();

		// Initialize admin settings page.
		if ( is_admin() ) {
			Queue_Optimizer_Settings_Page::get_instance();
		}
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		// Set default options.
		$default_options = array(
			'time_limit'        => 30,
			'concurrent_batches' => 3,
			'logging_enabled'   => true,
			'log_retention_days' => 7,
		);

		foreach ( $default_options as $option => $value ) {
			add_option( 'queue_optimizer_' . $option, $value );
		}

		// Initialize last run timestamp.
		add_option( 'queue_optimizer_last_run', 0 );

		// Schedule the main queue processing event.
		if ( ! wp_next_scheduled( 'queue_optimizer_process_queue' ) ) {
			wp_schedule_event( time(), 'hourly', 'queue_optimizer_process_queue' );
		}

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		// Clear scheduled events.
		wp_clear_scheduled_hook( 'queue_optimizer_process_queue' );
		
		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin uninstall.
	 */
	public static function uninstall() {
		// Include and run uninstall script.
		include_once QUEUE_OPTIMIZER_PLUGIN_DIR . 'includes/uninstall.php';
	}

	/**
	 * Set image processor preference to override WordPress default selection.
	 */
	public function set_image_processor_preference() {
		$preferred_engine = get_option( '365i_qo_image_engine', 'imagick' );
		
		if ( 'gd' === $preferred_engine ) {
			// Force GD Library usage.
			add_filter( 'wp_image_editors', array( $this, 'force_gd_image_editor' ), 999 );
		} elseif ( 'imagick' === $preferred_engine ) {
			// Force ImageMagick usage (if available).
			add_filter( 'wp_image_editors', array( $this, 'force_imagick_image_editor' ), 999 );
		}
	}

	/**
	 * Force WordPress to use GD Library for image processing.
	 *
	 * @param array $editors Array of image editor class names.
	 * @return array Modified array with GD editor prioritized.
	 */
	public function force_gd_image_editor( $editors ) {
		return array( 'WP_Image_Editor_GD' );
	}

	/**
	 * Force WordPress to use ImageMagick for image processing.
	 *
	 * @param array $editors Array of image editor class names.
	 * @return array Modified array with ImageMagick editor prioritized.
	 */
	public function force_imagick_image_editor( $editors ) {
		// Only use ImageMagick if it's available.
		if ( class_exists( 'Imagick' ) ) {
			return array( 'WP_Image_Editor_Imagick' );
		}
		
		// Fallback to default editors if ImageMagick is not available.
		return $editors;
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Only enqueue on plugin admin pages.
		if ( false === strpos( $hook_suffix, 'queue-optimizer' ) ) {
			return;
		}

		wp_enqueue_style(
			'queue-optimizer-admin',
			QUEUE_OPTIMIZER_PLUGIN_URL . 'assets/admin.css',
			array(),
			QUEUE_OPTIMIZER_VERSION
		);

		wp_enqueue_script(
			'queue-optimizer-admin',
			QUEUE_OPTIMIZER_PLUGIN_URL . 'assets/admin.js',
			array( 'jquery' ),
			QUEUE_OPTIMIZER_VERSION,
			true
		);

		// Localize script for AJAX.
		wp_localize_script(
			'queue-optimizer-admin',
			'queueOptimizerAjax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'queue_optimizer_nonce' ),
				'strings'  => array(
					'processing' => __( 'Processing...', '365i-queue-optimizer' ),
					'error'      => __( 'Error occurred. Please try again.', '365i-queue-optimizer' ),
					'success'    => __( 'Operation completed successfully.', '365i-queue-optimizer' ),
				),
			)
		);
	}
}

// Initialize the plugin.
Queue_Optimizer_Plugin::get_instance();