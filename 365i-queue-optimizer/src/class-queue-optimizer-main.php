<?php
/**
 * Main Queue Optimizer Class
 *
 * @package QueueOptimizer
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class - handles ActionScheduler optimization filters.
 *
 * @since 1.0.0
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
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Load admin components only if needed.
		if ( is_admin() ) {
			require_once QUEUE_OPTIMIZER_PLUGIN_DIR . 'admin/class-settings-page.php';
			Queue_Optimizer_Settings_Page::get_instance();

			require_once QUEUE_OPTIMIZER_PLUGIN_DIR . 'admin/class-dashboard-widget.php';
			Queue_Optimizer_Dashboard_Widget::get_instance();
		}

		// Core ActionScheduler optimization filters.
		add_filter( 'action_scheduler_queue_runner_time_limit', array( $this, 'set_time_limit' ) );
		add_filter( 'action_scheduler_queue_runner_concurrent_batches', array( $this, 'set_concurrent_batches' ) );
		add_filter( 'action_scheduler_queue_runner_batch_size', array( $this, 'set_batch_size' ) );
		add_filter( 'action_scheduler_retention_period', array( $this, 'set_retention_period' ) );

		// Image editor priority.
		add_filter( 'wp_image_editors', array( $this, 'set_image_editor' ) );

		// Raise memory limit for image processing.
		add_filter( 'image_memory_limit', array( $this, 'set_image_memory_limit' ) );

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
		$value     = get_option( 'queue_optimizer_time_limit', 60 );
		$validated = $this->validate_int_option( $value, 10, 300, 60 );

		return apply_filters( 'queue_optimizer_time_limit', $validated, $value, $time_limit );
	}

	/**
	 * Set ActionScheduler concurrent batches.
	 *
	 * @param int $batches The default number of concurrent batches.
	 * @return int Modified number of concurrent batches.
	 */
	public function set_concurrent_batches( $batches ) {
		$value     = get_option( 'queue_optimizer_concurrent_batches', 4 );
		$validated = $this->validate_int_option( $value, 1, 10, 4 );

		return apply_filters( 'queue_optimizer_concurrent_batches', $validated, $value, $batches );
	}

	/**
	 * Set ActionScheduler batch size.
	 *
	 * @since 1.4.0
	 * @param int $batch_size The default batch size.
	 * @return int Modified batch size.
	 */
	public function set_batch_size( $batch_size ) {
		$value     = get_option( 'queue_optimizer_batch_size', 50 );
		$validated = $this->validate_int_option( $value, 25, 200, 50 );

		return apply_filters( 'queue_optimizer_batch_size', $validated, $value, $batch_size );
	}

	/**
	 * Set ActionScheduler data retention period.
	 *
	 * @since 1.4.0
	 * @param int $period The default retention period in seconds.
	 * @return int Modified retention period.
	 */
	public function set_retention_period( $period ) {
		$days      = get_option( 'queue_optimizer_retention_days', 7 );
		$validated = $this->validate_int_option( $days, 1, 30, 7 );
		$seconds   = $validated * DAY_IN_SECONDS;

		return apply_filters( 'queue_optimizer_retention_period', $seconds, $validated, $period );
	}

	/**
	 * Set image editor priority.
	 *
	 * @param array $editors Array of image editor class names.
	 * @return array Modified array with preferred editor prioritized.
	 */
	public function set_image_editor( $editors ) {
		$allowed   = array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' );
		$preferred = get_option( 'queue_optimizer_image_engine', 'WP_Image_Editor_Imagick' );

		if ( ! in_array( $preferred, $allowed, true ) ) {
			$preferred = 'WP_Image_Editor_Imagick';
		}

		$result = array_merge( array( $preferred ), array_diff( $editors, array( $preferred ) ) );

		return apply_filters( 'queue_optimizer_image_editors', $result, $preferred, $editors );
	}

	/**
	 * Set image memory limit for processing.
	 *
	 * @since 1.4.0
	 * @param int $limit Current memory limit.
	 * @return int Optimized memory limit.
	 */
	public function set_image_memory_limit( $limit ) {
		// Use WordPress's recommended image memory limit.
		$wp_limit = wp_convert_hr_to_bytes( WP_MAX_MEMORY_LIMIT );

		// Return the higher of current or WP max.
		$optimized = max( $limit, $wp_limit );

		return apply_filters( 'queue_optimizer_image_memory_limit', $optimized, $limit );
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
	 * Get server environment information.
	 *
	 * @since 1.4.0
	 * @return array Server environment data.
	 */
	public static function get_server_environment() {
		$env = array();

		// PHP settings.
		$env['php_version']         = PHP_VERSION;
		$env['max_execution_time']  = (int) ini_get( 'max_execution_time' );
		$env['memory_limit']        = ini_get( 'memory_limit' );
		$env['memory_limit_bytes']  = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );

		// WordPress settings.
		$env['wp_version']          = $GLOBALS['wp_version'];
		$env['wp_memory_limit']     = WP_MEMORY_LIMIT;
		$env['wp_max_memory_limit'] = WP_MAX_MEMORY_LIMIT;

		// ImageMagick detection.
		$env['imagick_available'] = extension_loaded( 'imagick' ) && class_exists( 'Imagick' );
		$env['imagick_version']   = '';
		if ( $env['imagick_available'] ) {
			$imagick = new Imagick();
			$version = $imagick->getVersion();
			if ( isset( $version['versionString'] ) ) {
				// Extract version number from string like "ImageMagick 7.1.0-62 Q16..."
				preg_match( '/ImageMagick\s+([\d.]+)/', $version['versionString'], $matches );
				$env['imagick_version'] = isset( $matches[1] ) ? $matches[1] : '';
			}
		}

		// GD detection.
		$env['gd_available'] = extension_loaded( 'gd' ) && function_exists( 'gd_info' );
		$env['gd_version']   = '';
		if ( $env['gd_available'] ) {
			$gd_info = gd_info();
			$env['gd_version'] = isset( $gd_info['GD Version'] ) ? $gd_info['GD Version'] : '';
		}

		// WebP/AVIF support.
		$env['webp_support'] = false;
		$env['avif_support'] = false;
		if ( $env['imagick_available'] ) {
			$formats = Imagick::queryFormats();
			$env['webp_support'] = in_array( 'WEBP', $formats, true );
			$env['avif_support'] = in_array( 'AVIF', $formats, true );
		} elseif ( $env['gd_available'] ) {
			$gd_info = gd_info();
			$env['webp_support'] = ! empty( $gd_info['WebP Support'] );
			$env['avif_support'] = ! empty( $gd_info['AVIF Support'] );
		}

		// ActionScheduler status.
		$env['actionscheduler_active']  = class_exists( 'ActionScheduler' );
		$env['actionscheduler_version'] = '';
		$env['pending_actions']         = 0;
		if ( $env['actionscheduler_active'] && class_exists( 'ActionScheduler_Versions' ) ) {
			$env['actionscheduler_version'] = ActionScheduler_Versions::instance()->latest_version();
			if ( function_exists( 'as_get_scheduled_actions' ) ) {
				$pending = as_get_scheduled_actions( array(
					'status'   => ActionScheduler_Store::STATUS_PENDING,
					'per_page' => 0,
				), 'ids' );
				$env['pending_actions'] = count( $pending );
			}
		}

		// Hosting environment detection.
		$env['server_type'] = self::detect_server_type();

		return $env;
	}

	/**
	 * Detect server type for recommended settings.
	 *
	 * @since 1.4.0
	 * @since 1.5.0 Added manual override and more conservative auto-detection.
	 * @return string Server type: 'shared', 'vps', or 'dedicated'.
	 */
	private static function detect_server_type() {
		// First check for manual override.
		$manual = get_option( 'queue_optimizer_server_type_override', '' );
		if ( in_array( $manual, array( 'shared', 'vps', 'dedicated' ), true ) ) {
			return $manual;
		}

		$memory_bytes   = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
		$execution_time = (int) ini_get( 'max_execution_time' );

		// Very high thresholds for "dedicated" - be conservative.
		// Many shared hosts now offer high PHP limits but share CPU/DB resources.
		if ( $memory_bytes >= 1024 * MB_IN_BYTES && ( $execution_time >= 300 || 0 === $execution_time ) ) {
			return 'dedicated';
		}

		// Higher thresholds for "vps".
		if ( $memory_bytes >= 512 * MB_IN_BYTES && $execution_time >= 120 ) {
			return 'vps';
		}

		// Default: assume shared hosting (safest).
		return 'shared';
	}

	/**
	 * Get recommended settings based on server environment.
	 *
	 * @since 1.4.0
	 * @since 1.5.0 More conservative default values.
	 * @return array Recommended settings.
	 */
	public static function get_recommended_settings() {
		$server_type = self::detect_server_type();

		// Conservative recommendations to prevent failures on shared resources.
		$recommendations = array(
			'shared' => array(
				'time_limit'         => 30,
				'concurrent_batches' => 1,
				'batch_size'         => 25,
				'retention_days'     => 3,
			),
			'vps' => array(
				'time_limit'         => 45,
				'concurrent_batches' => 2,
				'batch_size'         => 35,
				'retention_days'     => 5,
			),
			'dedicated' => array(
				'time_limit'         => 60,
				'concurrent_batches' => 4,
				'batch_size'         => 50,
				'retention_days'     => 7,
			),
		);

		return isset( $recommendations[ $server_type ] )
			? $recommendations[ $server_type ]
			: $recommendations['shared'];
	}

	/**
	 * Plugin activation.
	 */
	public static function activate() {
		// Set default options.
		add_option( 'queue_optimizer_time_limit', 60 );
		add_option( 'queue_optimizer_concurrent_batches', 4 );
		add_option( 'queue_optimizer_batch_size', 50 );
		add_option( 'queue_optimizer_retention_days', 7 );
		add_option( 'queue_optimizer_image_engine', 'WP_Image_Editor_Imagick' );
		add_option( 'queue_optimizer_activated', time() );

		// Clear any caches.
		wp_cache_flush();
	}
}
