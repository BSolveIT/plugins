<?php
/**
 * Settings Page Class
 *
 * Handles the admin settings interface for Queue Optimizer.
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
		// Register settings.
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

		register_setting( 'queue_optimizer_settings', 'queue_optimizer_image_engine', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_image_engine' ),
			'default'           => 'WP_Image_Editor_GD',
		) );

		// Add settings section.
		add_settings_section(
			'queue_optimizer_main',
			__( 'ActionScheduler Optimization Settings', '365i-queue-optimizer' ),
			array( $this, 'render_section_description' ),
			'queue_optimizer_settings'
		);

		// Add settings fields.
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

		// Enqueue JavaScript file if needed.
		wp_enqueue_script(
			'queue-optimizer-admin',
			QUEUE_OPTIMIZER_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			QUEUE_OPTIMIZER_VERSION,
			true
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		// Load the settings page template.
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
			   step="1" />
		<span class="description">
			<?php esc_html_e( 'Maximum time (in seconds) for ActionScheduler queue processing. Default: 60 seconds.', '365i-queue-optimizer' ); ?>
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
			   step="1" />
		<span class="description">
			<?php esc_html_e( 'Number of concurrent batches ActionScheduler can process. Higher values = faster processing. Default: 4 batches.', '365i-queue-optimizer' ); ?>
		</span>
		<?php
	}

	/**
	 * Render image engine field.
	 */
	public function render_image_engine_field() {
		$value = get_option( 'queue_optimizer_image_engine', 'WP_Image_Editor_GD' );
		?>
		<select id="queue_optimizer_image_engine" name="queue_optimizer_image_engine">
			<option value="WP_Image_Editor_GD" <?php selected( $value, 'WP_Image_Editor_GD' ); ?>>
				<?php esc_html_e( 'GD Library (Recommended)', '365i-queue-optimizer' ); ?>
			</option>
			<option value="WP_Image_Editor_Imagick" <?php selected( $value, 'WP_Image_Editor_Imagick' ); ?>>
				<?php esc_html_e( 'ImageMagick', '365i-queue-optimizer' ); ?>
			</option>
		</select>
		<span class="description">
			<?php esc_html_e( 'Image processing engine to prioritize. GD is usually faster for basic operations.', '365i-queue-optimizer' ); ?>
		</span>
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
	 * Sanitize image engine setting.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return string Sanitized value.
	 */
	public function sanitize_image_engine( $value ) {
		$allowed = array( 'WP_Image_Editor_GD', 'WP_Image_Editor_Imagick' );
		return in_array( $value, $allowed, true ) ? $value : 'WP_Image_Editor_GD';
	}
}