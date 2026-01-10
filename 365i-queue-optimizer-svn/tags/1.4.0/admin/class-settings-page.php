<?php
/**
 * Settings Page Class
 *
 * Handles the admin settings interface for Queue Optimizer.
 *
 * @package QueueOptimizer
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queue Optimizer Settings Page class.
 *
 * @since 1.0.0
 */
class Queue_Optimizer_Settings_Page {

	/**
	 * Single instance of the settings page.
	 *
	 * @var Queue_Optimizer_Settings_Page
	 */
	private static $instance = null;

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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_qo_run_queue', array( $this, 'ajax_run_queue' ) );
		add_action( 'wp_ajax_qo_get_queue_status', array( $this, 'ajax_get_queue_status' ) );
	}

	/**
	 * Add admin menu page.
	 */
	public function add_admin_menu() {
		add_management_page(
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
		register_setting( 'queue_optimizer_settings', 'queue_optimizer_time_limit', array(
			'type'              => 'integer',
			'sanitize_callback' => array( $this, 'sanitize_time_limit' ),
			'default'           => 60,
		) );

		register_setting( 'queue_optimizer_settings', 'queue_optimizer_concurrent_batches', array(
			'type'              => 'integer',
			'sanitize_callback' => array( $this, 'sanitize_concurrent_batches' ),
			'default'           => 4,
		) );

		register_setting( 'queue_optimizer_settings', 'queue_optimizer_batch_size', array(
			'type'              => 'integer',
			'sanitize_callback' => array( $this, 'sanitize_batch_size' ),
			'default'           => 50,
		) );

		register_setting( 'queue_optimizer_settings', 'queue_optimizer_retention_days', array(
			'type'              => 'integer',
			'sanitize_callback' => array( $this, 'sanitize_retention_days' ),
			'default'           => 7,
		) );

		register_setting( 'queue_optimizer_settings', 'queue_optimizer_image_engine', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_image_engine' ),
			'default'           => 'WP_Image_Editor_Imagick',
		) );

		add_settings_section(
			'queue_optimizer_main',
			__( 'ActionScheduler Optimization Settings', '365i-queue-optimizer' ),
			array( $this, 'render_section_description' ),
			'queue_optimizer_settings'
		);

		add_settings_field(
			'queue_optimizer_time_limit',
			__( 'Time Limit', '365i-queue-optimizer' ),
			array( $this, 'render_time_limit_field' ),
			'queue_optimizer_settings',
			'queue_optimizer_main'
		);

		add_settings_field(
			'queue_optimizer_concurrent_batches',
			__( 'Concurrent Batches', '365i-queue-optimizer' ),
			array( $this, 'render_concurrent_batches_field' ),
			'queue_optimizer_settings',
			'queue_optimizer_main'
		);

		add_settings_field(
			'queue_optimizer_batch_size',
			__( 'Batch Size', '365i-queue-optimizer' ),
			array( $this, 'render_batch_size_field' ),
			'queue_optimizer_settings',
			'queue_optimizer_main'
		);

		add_settings_field(
			'queue_optimizer_retention_days',
			__( 'Data Retention', '365i-queue-optimizer' ),
			array( $this, 'render_retention_days_field' ),
			'queue_optimizer_settings',
			'queue_optimizer_main'
		);

		add_settings_field(
			'queue_optimizer_image_engine',
			__( 'Image Processing Engine', '365i-queue-optimizer' ),
			array( $this, 'render_image_engine_field' ),
			'queue_optimizer_settings',
			'queue_optimizer_main'
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'tools_page_queue-optimizer' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'queue-optimizer-admin',
			QUEUE_OPTIMIZER_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			QUEUE_OPTIMIZER_VERSION
		);

		wp_enqueue_script(
			'queue-optimizer-admin',
			QUEUE_OPTIMIZER_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			QUEUE_OPTIMIZER_VERSION,
			true
		);

		wp_localize_script( 'queue-optimizer-admin', 'queueOptimizerAdmin', array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'qo_admin_nonce' ),
			'recommended' => Queue_Optimizer_Main::get_recommended_settings(),
			'i18n'        => array(
				'running'    => __( 'Processing...', '365i-queue-optimizer' ),
				'runQueue'   => __( 'Run Queue Now', '365i-queue-optimizer' ),
				/* translators: %d: number of actions processed */
				'processed'  => __( 'Processed %d actions', '365i-queue-optimizer' ),
				'noActions'  => __( 'No pending actions to process', '365i-queue-optimizer' ),
				'error'      => __( 'Error running queue', '365i-queue-optimizer' ),
			),
		) );
	}

	/**
	 * AJAX handler: Run the ActionScheduler queue.
	 *
	 * @since 1.4.0
	 */
	public function ajax_run_queue() {
		check_ajax_referer( 'qo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', '365i-queue-optimizer' ) ) );
		}

		if ( ! class_exists( 'ActionScheduler' ) ) {
			wp_send_json_error( array( 'message' => __( 'ActionScheduler is not available.', '365i-queue-optimizer' ) ) );
		}

		// Get pending count before processing.
		$before_count = 0;
		if ( function_exists( 'as_get_scheduled_actions' ) ) {
			$pending = as_get_scheduled_actions( array(
				'status'   => ActionScheduler_Store::STATUS_PENDING,
				'per_page' => 0,
			), 'ids' );
			$before_count = count( $pending );
		}

		if ( 0 === $before_count ) {
			wp_send_json_success( array(
				'processed' => 0,
				'remaining' => 0,
				'message'   => __( 'No pending actions to process.', '365i-queue-optimizer' ),
			) );
		}

		// Run the queue.
		$processed = 0;
		if ( class_exists( 'ActionScheduler_QueueRunner' ) ) {
			$runner    = ActionScheduler_QueueRunner::instance();
			$processed = $runner->run();
		}

		// Get remaining count.
		$remaining = 0;
		if ( function_exists( 'as_get_scheduled_actions' ) ) {
			$pending   = as_get_scheduled_actions( array(
				'status'   => ActionScheduler_Store::STATUS_PENDING,
				'per_page' => 0,
			), 'ids' );
			$remaining = count( $pending );
		}

		wp_send_json_success( array(
			'processed' => $processed,
			'remaining' => $remaining,
			'message'   => sprintf(
				/* translators: %d: number of actions processed */
				__( 'Processed %d actions.', '365i-queue-optimizer' ),
				$processed
			),
		) );
	}

	/**
	 * AJAX handler: Get queue status.
	 *
	 * @since 1.4.0
	 */
	public function ajax_get_queue_status() {
		check_ajax_referer( 'qo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', '365i-queue-optimizer' ) ) );
		}

		wp_send_json_success( self::get_queue_status() );
	}

	/**
	 * Get pending actions grouped by hook.
	 *
	 * @since 1.4.0
	 * @return array Queue status data.
	 */
	public static function get_queue_status() {
		$status = array(
			'pending_total'   => 0,
			'pending_by_hook' => array(),
			'running'         => 0,
			'failed'          => 0,
		);

		if ( ! class_exists( 'ActionScheduler' ) || ! function_exists( 'as_get_scheduled_actions' ) ) {
			return $status;
		}

		// Get pending actions.
		$pending = as_get_scheduled_actions( array(
			'status'   => ActionScheduler_Store::STATUS_PENDING,
			'per_page' => 500,
		), 'ARRAY_A' );

		$status['pending_total'] = count( $pending );

		// Group by hook.
		$by_hook = array();
		foreach ( $pending as $action ) {
			$hook = isset( $action['hook'] ) ? $action['hook'] : 'unknown';
			if ( ! isset( $by_hook[ $hook ] ) ) {
				$by_hook[ $hook ] = 0;
			}
			$by_hook[ $hook ]++;
		}

		// Sort by count descending and limit to top 5.
		arsort( $by_hook );
		$status['pending_by_hook'] = array_slice( $by_hook, 0, 5, true );

		// Get running count.
		$running = as_get_scheduled_actions( array(
			'status'   => ActionScheduler_Store::STATUS_RUNNING,
			'per_page' => 0,
		), 'ids' );
		$status['running'] = count( $running );

		// Get failed count (last 24 hours).
		$failed = as_get_scheduled_actions( array(
			'status'     => ActionScheduler_Store::STATUS_FAILED,
			'per_page'   => 0,
			'date'       => gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ),
			'date_compare' => '>',
		), 'ids' );
		$status['failed'] = count( $failed );

		return $status;
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		require_once QUEUE_OPTIMIZER_PLUGIN_DIR . 'templates/admin/settings-page.php';
	}

	/**
	 * Render section description.
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Configure ActionScheduler optimization settings to improve background task processing speed.', '365i-queue-optimizer' ) . '</p>';
	}

	/**
	 * Render time limit field.
	 */
	public function render_time_limit_field() {
		$value = get_option( 'queue_optimizer_time_limit', 60 );
		?>
		<input type="number"
			   id="queue_optimizer_time_limit"
			   name="queue_optimizer_time_limit"
			   value="<?php echo esc_attr( $value ); ?>"
			   min="10"
			   max="300"
			   step="1"
			   class="small-text" />
		<span class="description">
			<?php esc_html_e( 'seconds (10-300). Maximum time for queue processing per run.', '365i-queue-optimizer' ); ?>
		</span>
		<?php
	}

	/**
	 * Render concurrent batches field.
	 */
	public function render_concurrent_batches_field() {
		$value = get_option( 'queue_optimizer_concurrent_batches', 4 );
		?>
		<input type="number"
			   id="queue_optimizer_concurrent_batches"
			   name="queue_optimizer_concurrent_batches"
			   value="<?php echo esc_attr( $value ); ?>"
			   min="1"
			   max="10"
			   step="1"
			   class="small-text" />
		<span class="description">
			<?php esc_html_e( 'batches (1-10). Simultaneous processing threads.', '365i-queue-optimizer' ); ?>
		</span>
		<?php
	}

	/**
	 * Render batch size field.
	 *
	 * @since 1.4.0
	 */
	public function render_batch_size_field() {
		$value = get_option( 'queue_optimizer_batch_size', 50 );
		?>
		<input type="number"
			   id="queue_optimizer_batch_size"
			   name="queue_optimizer_batch_size"
			   value="<?php echo esc_attr( $value ); ?>"
			   min="25"
			   max="200"
			   step="5"
			   class="small-text" />
		<span class="description">
			<?php esc_html_e( 'actions (25-200). Actions claimed per batch.', '365i-queue-optimizer' ); ?>
		</span>
		<?php
	}

	/**
	 * Render retention days field.
	 *
	 * @since 1.4.0
	 */
	public function render_retention_days_field() {
		$value = get_option( 'queue_optimizer_retention_days', 7 );
		?>
		<input type="number"
			   id="queue_optimizer_retention_days"
			   name="queue_optimizer_retention_days"
			   value="<?php echo esc_attr( $value ); ?>"
			   min="1"
			   max="30"
			   step="1"
			   class="small-text" />
		<span class="description">
			<?php esc_html_e( 'days (1-30). How long to keep completed action logs.', '365i-queue-optimizer' ); ?>
		</span>
		<?php
	}

	/**
	 * Render image engine field with availability detection.
	 */
	public function render_image_engine_field() {
		$value             = get_option( 'queue_optimizer_image_engine', 'WP_Image_Editor_Imagick' );
		$imagick_available = extension_loaded( 'imagick' ) && class_exists( 'Imagick' );
		$gd_available      = extension_loaded( 'gd' ) && function_exists( 'gd_info' );
		?>
		<select id="queue_optimizer_image_engine" name="queue_optimizer_image_engine">
			<option value="WP_Image_Editor_Imagick" <?php selected( $value, 'WP_Image_Editor_Imagick' ); ?>>
				<?php
				if ( $imagick_available ) {
					esc_html_e( 'ImageMagick (Recommended)', '365i-queue-optimizer' );
				} else {
					esc_html_e( 'ImageMagick (Not Available)', '365i-queue-optimizer' );
				}
				?>
			</option>
			<option value="WP_Image_Editor_GD" <?php selected( $value, 'WP_Image_Editor_GD' ); ?>>
				<?php
				if ( $gd_available ) {
					esc_html_e( 'GD Library', '365i-queue-optimizer' );
				} else {
					esc_html_e( 'GD Library (Not Available)', '365i-queue-optimizer' );
				}
				?>
			</option>
		</select>
		<?php if ( ! $imagick_available && 'WP_Image_Editor_Imagick' === $value ) : ?>
			<span class="qo-warning">
				<?php esc_html_e( 'ImageMagick is not installed. GD will be used as fallback.', '365i-queue-optimizer' ); ?>
			</span>
		<?php elseif ( ! $gd_available && ! $imagick_available ) : ?>
			<span class="qo-error">
				<?php esc_html_e( 'No image processing library available!', '365i-queue-optimizer' ); ?>
			</span>
		<?php endif; ?>
		<?php
	}

	/**
	 * Sanitize time limit setting.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return int Sanitized value.
	 */
	public function sanitize_time_limit( $value ) {
		$value = intval( $value );
		return ( $value < 10 || $value > 300 ) ? 60 : $value;
	}

	/**
	 * Sanitize concurrent batches setting.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return int Sanitized value.
	 */
	public function sanitize_concurrent_batches( $value ) {
		$value = intval( $value );
		return ( $value < 1 || $value > 10 ) ? 4 : $value;
	}

	/**
	 * Sanitize batch size setting.
	 *
	 * @since 1.4.0
	 * @param mixed $value The value to sanitize.
	 * @return int Sanitized value.
	 */
	public function sanitize_batch_size( $value ) {
		$value = intval( $value );
		return ( $value < 25 || $value > 200 ) ? 50 : $value;
	}

	/**
	 * Sanitize retention days setting.
	 *
	 * @since 1.4.0
	 * @param mixed $value The value to sanitize.
	 * @return int Sanitized value.
	 */
	public function sanitize_retention_days( $value ) {
		$value = intval( $value );
		return ( $value < 1 || $value > 30 ) ? 7 : $value;
	}

	/**
	 * Sanitize image engine setting.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return string Sanitized value.
	 */
	public function sanitize_image_engine( $value ) {
		$allowed = array( 'WP_Image_Editor_GD', 'WP_Image_Editor_Imagick' );
		return in_array( $value, $allowed, true ) ? $value : 'WP_Image_Editor_Imagick';
	}
}
