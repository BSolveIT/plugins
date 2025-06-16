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

		// Handle option name upgrade for backward compatibility.
		$this->maybe_handle_option_upgrade();

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

		// Add hooks for post-upload processing.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_queue_optimizer_upload_complete', array( $this, 'handle_upload_complete_ajax' ) );
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'on_attachment_metadata_generated' ), 999, 2 );

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
	 * Enqueue admin scripts for media uploader detection.
	 */
	public function enqueue_admin_scripts() {
		// Only enqueue on media-related pages.
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'upload', 'post', 'page' ), true ) ) {
			return;
		}

		wp_enqueue_script(
			'queue-optimizer-upload-complete',
			plugin_dir_url( QUEUE_OPTIMIZER_PLUGIN_DIR . '365i-queue-optimizer.php' ) . 'assets/js/upload-complete-trigger.js',
			array( 'jquery', 'media-views' ),
			'1.2.0',
			true
		);

		wp_localize_script(
			'queue-optimizer-upload-complete',
			'QueueOptimizer',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'queue-optimizer-upload-complete' ),
			)
		);
	}

	/**
	 * Handle the AJAX request when uploads complete.
	 */
	public function handle_upload_complete_ajax() {
		// Verify nonce.
		check_ajax_referer( 'queue-optimizer-upload-complete', 'nonce' );

		// Check if post-upload processing is enabled.
		$enabled = get_option( 'queue_optimizer_post_upload_processing', true );
		if ( ! $enabled ) {
			wp_send_json_success( 'Processing disabled by settings.' );
			return;
		}

		// Trigger ActionScheduler to run now.
		if ( class_exists( 'ActionScheduler' ) ) {
			do_action( 'action_scheduler_run_queue', 'upload-complete-trigger' );
			do_action( 'queue_optimizer_processing_triggered' );
		}

		wp_send_json_success( 'Queue processing triggered.' );
	}

	/**
	 * Maybe handle option name upgrade for backward compatibility.
	 */
	private function maybe_handle_option_upgrade() {
		// Check if the old option exists but the new one doesn't.
		if ( get_option( 'queue_optimizer_immediate_processing' ) !== false &&
			 get_option( 'queue_optimizer_post_upload_processing' ) === false ) {

			// Copy value from old option to new option.
			$old_value = get_option( 'queue_optimizer_immediate_processing' );
			add_option( 'queue_optimizer_post_upload_processing', $old_value );

			// Remove old option.
			delete_option( 'queue_optimizer_immediate_processing' );
		}
	}

	/**
	 * Set ActionScheduler time limit.
	 *
	 * @param int $time_limit The default time limit.
	 * @return int Modified time limit.
	 */
	public function set_time_limit( $time_limit ) {
		$value      = get_option( 'queue_optimizer_time_limit', 60 );
		$validated  = $this->validate_int_option( $value, 10, 300, 60 );

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
		$value     = get_option( 'queue_optimizer_concurrent_batches', 4 );
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
		$allowed   = array( 'WP_Image_Editor_GD', 'WP_Image_Editor_Imagick' );
		$preferred = get_option( 'queue_optimizer_image_engine', 'WP_Image_Editor_GD' );

		if ( ! in_array( $preferred, $allowed, true ) ) {
			$preferred = 'WP_Image_Editor_GD';
		}

		$result = array_merge( array( $preferred ), array_diff( $editors, array( $preferred ) ) );

		/**
		 * Filter the image editor priority array.
		 *
		 * @param array  $result The reordered editors array.
		 * @param string $preferred The preferred editor.
		 * @param array  $editors The original editors array.
		 */
		return apply_filters( 'queue_optimizer_image_editors', $result, $preferred, $editors );
	}

	/**
	 * Process attachment metadata for single uploads (fallback).
	 *
	 * @param array $metadata      Attachment metadata.
	 * @param int   $attachment_id Attachment ID.
	 * @return array Unmodified metadata.
	 */
	public function on_attachment_metadata_generated( $metadata, $attachment_id ) {
		// Check if it's an image.
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return $metadata;
		}

		// Check if we're in a single upload context (not bulk).
		// This is more of a fallback for uploads that don't use the media modal.
		if ( defined( 'DOING_AJAX' ) && isset( $_POST['action'] ) &&
			 'queue_optimizer_upload_complete' !== sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) {

			// Check if post-upload processing is enabled.
			$enabled = get_option( 'queue_optimizer_post_upload_processing', true );
			if ( ! $enabled ) {
				return $metadata;
			}

			// Trigger ActionScheduler for this single upload.
			if ( class_exists( 'ActionScheduler' ) ) {
				do_action( 'action_scheduler_run_queue', 'single-upload-trigger' );
				do_action( 'queue_optimizer_processing_triggered' );
			}
		}

		return $metadata;
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
		add_option( 'queue_optimizer_post_upload_processing', true );
		add_option( 'queue_optimizer_activated', time() );

		// Clear any caches.
		wp_cache_flush();
	}
}