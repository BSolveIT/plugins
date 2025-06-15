<?php
/**
 * Plugin Name: 365i Queue Optimizer
 * Plugin URI: https://www.365i.co.uk/
 * Description: A lightweight WordPress plugin to optimize ActionScheduler queue processing for faster image optimization and background tasks.
 * Version: 1.0.0
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
define( 'QUEUE_OPTIMIZER_VERSION', '1.0.0' );
define( 'QUEUE_OPTIMIZER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QUEUE_OPTIMIZER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QUEUE_OPTIMIZER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'QUEUE_OPTIMIZER_MIN_WP_VERSION', '5.8' );

/**
 * Main plugin class - simplified approach based on user's working plugin.
 */
class Queue_Optimizer_Main {

	/**
	 * Single instance of the plugin.
	 *
	 * @var Queue_Optimizer_Main
	 */
	private static $instance = null;

	/**
	 * Get single instance of the plugin.
	 *
	 * @return Queue_Optimizer_Main
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
		// Check WordPress version compatibility.
		if ( version_compare( $GLOBALS['wp_version'], QUEUE_OPTIMIZER_MIN_WP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'display_version_notice' ) );
			return;
		}

		$this->init_hooks();
	}

	/**
	 * Initialize hooks - simplified approach.
	 */
	private function init_hooks() {
		// Load admin settings page only if needed.
		if ( is_admin() ) {
			require_once QUEUE_OPTIMIZER_PLUGIN_DIR . 'admin/class-settings-page.php';
			Queue_Optimizer_Settings_Page::get_instance();
		}

		// Apply the three essential ActionScheduler optimization filters.
		// This is the core functionality that makes image processing fast.
		add_filter( 'action_scheduler_queue_runner_time_limit', array( $this, 'set_time_limit' ) );
		add_filter( 'action_scheduler_queue_runner_concurrent_batches', array( $this, 'set_concurrent_batches' ) );
		add_filter( 'wp_image_editors', array( $this, 'set_image_editor' ) );

		// Plugin activation/uninstall hooks.
		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Display WordPress version compatibility notice.
	 */
	public function display_version_notice() {
		echo '<div class="error"><p>' .
			sprintf(
				esc_html__( '365i Queue Optimizer requires WordPress version %s or higher.', '365i-queue-optimizer' ),
				QUEUE_OPTIMIZER_MIN_WP_VERSION
			) .
			'</p></div>';
	}

	/**
	 * Set ActionScheduler time limit.
	 *
	 * @param int $time_limit The default time limit.
	 * @return int Modified time limit.
	 */
	public function set_time_limit( $time_limit ) {
		$value = get_option( 'queue_optimizer_time_limit', 60 );
		return $this->validate_int_option( $value, 10, 300, 60 );
	}

	/**
	 * Set ActionScheduler concurrent batches.
	 *
	 * @param int $batches The default number of concurrent batches.
	 * @return int Modified number of concurrent batches.
	 */
	public function set_concurrent_batches( $batches ) {
		$value = get_option( 'queue_optimizer_concurrent_batches', 4 );
		return $this->validate_int_option( $value, 1, 10, 4 );
	}

	/**
	 * Set image editor priority.
	 *
	 * @param array $editors Array of image editor class names.
	 * @return array Modified array with preferred editor prioritized.
	 */
	public function set_image_editor( $editors ) {
		$allowed = array( 'WP_Image_Editor_GD', 'WP_Image_Editor_Imagick' );
		$preferred = get_option( 'queue_optimizer_image_engine', 'WP_Image_Editor_GD' );
		
		if ( ! in_array( $preferred, $allowed, true ) ) {
			$preferred = 'WP_Image_Editor_GD';
		}
		
		// Return array with preferred editor first, others after.
		return array_merge( array( $preferred ), array_diff( $editors, array( $preferred ) ) );
	}

	/**
	 * Validate integer option within range.
	 *
	 * @param mixed $value   The value to validate.
	 * @param int   $min     Minimum allowed value.
	 * @param int   $max     Maximum allowed value.
	 * @param int   $default Default value if validation fails.
	 * @return int Validated value.
	 */
	private function validate_int_option( $value, $min, $max, $default ) {
		$value = intval( $value );
		return ( $value < $min || $value > $max ) ? $default : $value;
	}

	/**
	 * Plugin activation.
	 */
	public static function activate() {
		// Set default options.
		add_option( 'queue_optimizer_time_limit', 60 );
		add_option( 'queue_optimizer_concurrent_batches', 4 );
		add_option( 'queue_optimizer_image_engine', 'WP_Image_Editor_GD' );
		add_option( 'queue_optimizer_activated', time() );
		
		// Clear any caches.
		wp_cache_flush();
	}

	/**
	 * Plugin uninstall.
	 */
	public static function uninstall() {
		// Clean up options.
		delete_option( 'queue_optimizer_time_limit' );
		delete_option( 'queue_optimizer_concurrent_batches' );
		delete_option( 'queue_optimizer_image_engine' );
		delete_option( 'queue_optimizer_activated' );
		
		// Clean up any legacy options.
		delete_option( 'queue_optimizer_logging_enabled' );
		delete_option( 'queue_optimizer_log_retention_days' );
		delete_option( 'queue_optimizer_last_run' );
		delete_option( 'queue_optimizer_debug_mode' );
		delete_option( 'queue_optimizer_enable_concurrency_filter' );
		delete_option( '365i_qo_image_engine' );
	}
}

// Initialize the plugin.
Queue_Optimizer_Main::get_instance();