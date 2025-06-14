<?php
/**
 * Queue Optimizer Settings Page Class
 *
 * Handles the admin settings page and dashboard.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queue Optimizer Settings Page class.
 */
class Queue_Optimizer_Settings_Page {

	/**
	 * Single instance of the settings page.
	 *
	 * @var Queue_Optimizer_Settings_Page
	 */
	private static $instance = null;

	/**
	 * Page hook suffix.
	 *
	 * @var string
	 */
	private $page_hook;

	/**
	 * Get single instance of the settings page.
	 *
	 * @return Queue_Optimizer_Settings_Page
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
	 * Initialize the settings page.
	 */
	private function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add admin menu page.
	 */
	public function add_admin_menu() {
		$this->page_hook = add_management_page(
			__( '365i Queue Optimizer', '365i-queue-optimizer' ),
			__( 'Queue Optimizer', '365i-queue-optimizer' ),
			'manage_options',
			'queue-optimizer',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		// Register settings.
		register_setting(
			'queue_optimizer_settings',
			'queue_optimizer_time_limit',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_time_limit' ),
				'default'           => 30,
			)
		);

		register_setting(
			'queue_optimizer_settings',
			'queue_optimizer_concurrent_batches',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_concurrent_batches' ),
				'default'           => 3,
			)
		);

		register_setting(
			'queue_optimizer_settings',
			'queue_optimizer_logging_enabled',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);

		register_setting(
			'queue_optimizer_settings',
			'365i_qo_image_engine',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_image_engine' ),
				'default'           => 'imagick',
			)
		);

		register_setting(
			'queue_optimizer_settings',
			'queue_optimizer_log_retention_days',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_retention_days' ),
				'default'           => 7,
			)
		);

		// Add settings section.
		add_settings_section(
			'queue_optimizer_main_section',
			__( 'Queue Processing Settings', '365i-queue-optimizer' ),
			array( $this, 'render_section_description' ),
			'queue_optimizer_settings'
		);

		// Add settings fields.
		add_settings_field(
			'queue_optimizer_time_limit',
			__( 'Time Limit (seconds)', '365i-queue-optimizer' ),
			array( $this, 'render_time_limit_field' ),
			'queue_optimizer_settings',
			'queue_optimizer_main_section'
		);

		add_settings_field(
			'queue_optimizer_concurrent_batches',
			__( 'Concurrent Batches', '365i-queue-optimizer' ),
			array( $this, 'render_concurrent_batches_field' ),
			'queue_optimizer_settings',
			'queue_optimizer_main_section'
		);

		add_settings_field(
			'queue_optimizer_logging_enabled',
			__( 'Enable Logging', '365i-queue-optimizer' ),
			array( $this, 'render_logging_field' ),
			'queue_optimizer_settings',
			'queue_optimizer_main_section'
		);

		add_settings_field(
			'queue_optimizer_log_retention_days',
			__( 'Retention Period (days)', '365i-queue-optimizer' ),
			array( $this, 'render_retention_days_field' ),
			'queue_optimizer_settings',
			'queue_optimizer_main_section'
		);

		add_settings_field(
			'365i_qo_image_engine',
			__( 'Image Processing Engine', '365i-queue-optimizer' ),
			array( $this, 'render_image_engine_field' ),
			'queue_optimizer_settings',
			'queue_optimizer_main_section'
		);
	}

	/**
	 * Render the main settings page.
	 */
	public function render_settings_page() {
		// Get queue status for dashboard.
		$scheduler = Queue_Optimizer_Scheduler::get_instance();
		$status = $scheduler->get_queue_status();
		
		// Include header template.
		require_once plugin_dir_path( __FILE__ ) . '../includes/admin/templates/header.php';
		
		// Include settings form template.
		require_once plugin_dir_path( __FILE__ ) . '../includes/admin/templates/settings-form.php';
		
		// Include dashboard panel template.
		require_once plugin_dir_path( __FILE__ ) . '../includes/admin/templates/dashboard-panel.php';
		
		echo '</div>'; // Close the wrap div from header.php.
	}

	/**
	 * Render section description.
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Configure how the queue optimizer processes background jobs.', '365i-queue-optimizer' ) . '</p>';
	}

	/**
	 * Render time limit field.
	 */
	public function render_time_limit_field() {
		require_once plugin_dir_path( __FILE__ ) . '../includes/admin/templates/render-time-limit-field.php';
	}

	/**
	 * Render concurrent batches field.
	 */
	public function render_concurrent_batches_field() {
		require_once plugin_dir_path( __FILE__ ) . '../includes/admin/templates/render-concurrent-batches-field.php';
	}

	/**
	 * Render logging field.
	 */
	public function render_logging_field() {
		require_once plugin_dir_path( __FILE__ ) . '../includes/admin/templates/render-logging-field.php';
	}

	/**
	 * Render retention days field.
	 */
	public function render_retention_days_field() {
		require_once plugin_dir_path( __FILE__ ) . '../includes/admin/templates/render-retention-days-field.php';
	}

	/**
	 * Render image engine field.
	 */
	public function render_image_engine_field() {
		require_once plugin_dir_path( __FILE__ ) . '../includes/admin/templates/render-image-engine-field.php';
	}

	/**
	 * Sanitize time limit setting.
	 *
	 * @param mixed $value Input value to sanitize.
	 * @return int Sanitized time limit value.
	 */
	public function sanitize_time_limit( $value ) {
		$value = absint( $value );
		
		if ( $value < 5 ) {
			add_settings_error(
				'queue_optimizer_time_limit',
				'time_limit_too_low',
				__( 'Time limit must be at least 5 seconds.', '365i-queue-optimizer' )
			);
			return 5;
		}
		
		if ( $value > 300 ) {
			add_settings_error(
				'queue_optimizer_time_limit',
				'time_limit_too_high',
				__( 'Time limit cannot exceed 300 seconds.', '365i-queue-optimizer' )
			);
			return 300;
		}
		
		return $value;
	}

	/**
	 * Sanitize concurrent batches setting.
	 *
	 * @param mixed $value Input value to sanitize.
	 * @return int Sanitized concurrent batches value.
	 */
	public function sanitize_concurrent_batches( $value ) {
		$value = absint( $value );
		
		if ( $value < 1 ) {
			add_settings_error(
				'queue_optimizer_concurrent_batches',
				'concurrent_batches_too_low',
				__( 'Concurrent batches must be at least 1.', '365i-queue-optimizer' )
			);
			return 1;
		}
		
		if ( $value > 10 ) {
			add_settings_error(
				'queue_optimizer_concurrent_batches',
				'concurrent_batches_too_high',
				__( 'Concurrent batches cannot exceed 10.', '365i-queue-optimizer' )
			);
			return 10;
		}
		
		return $value;
	}

	/**
	 * Sanitize retention days setting.
	 *
	 * @param mixed $value Input value to sanitize.
	 * @return int Sanitized retention days value.
	 */
	public function sanitize_retention_days( $value ) {
		$value = absint( $value );
		
		if ( $value < 1 ) {
			add_settings_error(
				'queue_optimizer_log_retention_days',
				'retention_days_too_low',
				__( 'Log retention period must be at least 1 day.', '365i-queue-optimizer' )
			);
			return 1;
		}
		
		if ( $value > 365 ) {
			add_settings_error(
				'queue_optimizer_log_retention_days',
				'retention_days_too_high',
				__( 'Log retention period cannot exceed 365 days.', '365i-queue-optimizer' )
			);
			return 365;
		}
		
		return $value;
	}

	/**
	 * Sanitize image engine setting.
	 *
	 * @param mixed $value Input value to sanitize.
	 * @return string Sanitized image engine value.
	 */
	public function sanitize_image_engine( $value ) {
		$allowed_engines = array( 'imagick', 'gd' );
		
		if ( ! in_array( $value, $allowed_engines, true ) ) {
			add_settings_error(
				'365i_qo_image_engine',
				'invalid_image_engine',
				__( 'Invalid image engine selected.', '365i-queue-optimizer' )
			);
			return 'imagick';
		}
		
		// If ImageMagick is selected but not available, fallback to GD.
		if ( 'imagick' === $value && ! class_exists( 'Imagick' ) ) {
			add_settings_error(
				'365i_qo_image_engine',
				'imagick_not_available',
				__( 'ImageMagick is not available on this server. Falling back to GD Library.', '365i-queue-optimizer' ),
				'warning'
			);
			return 'gd';
		}
		
		return $value;
	}
}