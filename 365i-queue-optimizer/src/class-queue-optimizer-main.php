<?php
/**
 * Main Queue Optimizer Class
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		if ( version_compare( $GLOBALS['wp_version'], esc_html( QUEUE_OPTIMIZER_MIN_WP_VERSION ), '<' ) ) {
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

		// Plugin activation hook.
		register_activation_hook( QUEUE_OPTIMIZER_PLUGIN_DIR . '365i-queue-optimizer.php', array( __CLASS__, 'activate' ) );
	}

	/**
	 * Display WordPress version compatibility notice.
	 */
	public function display_version_notice() {
		echo '<div class="error"><p>' .
			sprintf(
/* translators: %s: minimum required WordPress version */
				esc_html__( '365i Queue Optimizer requires WordPress version %s or higher.', '365i-queue-optimizer' ),
				esc_html( QUEUE_OPTIMIZER_MIN_WP_VERSION )
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
		$validated = $this->validate_int_option( $value, 10, 300, 60 );
		
		/**
		 * Filter the ActionScheduler time limit.
		 *
		 * @param int $validated The validated time limit value.
		 * @param int $value The original option value.
		 * @param int $time_limit The default time limit.
		 */
		return apply_filters( 'queue_optimizer_time_limit', $validated, $value, $time_limit );
	}

	/**
	 * Set ActionScheduler concurrent batches.
	 *
	 * @param int $batches The default number of concurrent batches.
	 * @return int Modified number of concurrent batches.
	 */
	public function set_concurrent_batches( $batches ) {
		$value = get_option( 'queue_optimizer_concurrent_batches', 4 );
		$validated = $this->validate_int_option( $value, 1, 10, 4 );
		
		/**
		 * Filter the ActionScheduler concurrent batches.
		 *
		 * @param int $validated The validated concurrent batches value.
		 * @param int $value The original option value.
		 * @param int $batches The default number of batches.
		 */
		return apply_filters( 'queue_optimizer_concurrent_batches', $validated, $value, $batches );
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
		
		$result = array_merge( array( $preferred ), array_diff( $editors, array( $preferred ) ) );
		
		/**
		 * Filter the image editor priority array.
		 *
		 * @param array $result The reordered editors array.
		 * @param string $preferred The preferred editor.
		 * @param array $editors The original editors array.
		 */
		return apply_filters( 'queue_optimizer_image_editors', $result, $preferred, $editors );
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
}