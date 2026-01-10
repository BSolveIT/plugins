<?php
/**
 * Dashboard Widget Class
 *
 * Provides an at-a-glance queue status widget for the WordPress dashboard.
 *
 * @package QueueOptimizer
 * @since 1.4.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queue Optimizer Dashboard Widget class.
 *
 * @since 1.4.0
 */
class Queue_Optimizer_Dashboard_Widget {

	/**
	 * Single instance.
	 *
	 * @var Queue_Optimizer_Dashboard_Widget
	 */
	private static $instance = null;

	/**
	 * Get single instance.
	 *
	 * @return Queue_Optimizer_Dashboard_Widget
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
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the dashboard widget.
	 */
	public function register_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'qo_queue_status',
			__( 'Queue Optimizer', '365i-queue-optimizer' ),
			array( $this, 'render_widget' )
		);
	}

	/**
	 * Enqueue widget assets on dashboard only.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'index.php' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'qo-dashboard-widget',
			QUEUE_OPTIMIZER_PLUGIN_URL . 'assets/css/dashboard-widget.css',
			array(),
			QUEUE_OPTIMIZER_VERSION
		);

		wp_enqueue_script(
			'qo-dashboard-widget',
			QUEUE_OPTIMIZER_PLUGIN_URL . 'assets/js/dashboard-widget.js',
			array( 'jquery' ),
			QUEUE_OPTIMIZER_VERSION,
			true
		);

		wp_localize_script( 'qo-dashboard-widget', 'qoDashboard', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'qo_admin_nonce' ),
			'i18n'    => array(
				'processing' => __( 'Processing...', '365i-queue-optimizer' ),
				'runQueue'   => __( 'Run Queue', '365i-queue-optimizer' ),
			),
		) );
	}

	/**
	 * Get queue health status.
	 *
	 * @return array Health status data.
	 */
	private function get_health_status() {
		$status = array(
			'health'    => 'healthy',
			'label'     => __( 'Healthy', '365i-queue-optimizer' ),
			'pending'   => 0,
			'running'   => 0,
			'failed'    => 0,
			'available' => false,
		);

		if ( ! class_exists( 'ActionScheduler' ) || ! function_exists( 'as_get_scheduled_actions' ) ) {
			return $status;
		}

		$status['available'] = true;

		// Get pending count.
		$pending = as_get_scheduled_actions( array(
			'status'   => ActionScheduler_Store::STATUS_PENDING,
			'per_page' => 0,
		), 'ids' );
		$status['pending'] = count( $pending );

		// Get running count.
		$running = as_get_scheduled_actions( array(
			'status'   => ActionScheduler_Store::STATUS_RUNNING,
			'per_page' => 0,
		), 'ids' );
		$status['running'] = count( $running );

		// Get failed count (last 24h).
		$failed = as_get_scheduled_actions( array(
			'status'       => ActionScheduler_Store::STATUS_FAILED,
			'per_page'     => 0,
			'date'         => gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ),
			'date_compare' => '>',
		), 'ids' );
		$status['failed'] = count( $failed );

		// Determine health.
		if ( $status['failed'] > 0 ) {
			$status['health'] = 'critical';
			$status['label']  = __( 'Needs Attention', '365i-queue-optimizer' );
		} elseif ( $status['pending'] > 50 ) {
			$status['health'] = 'warning';
			$status['label']  = __( 'Backlog', '365i-queue-optimizer' );
		}

		return $status;
	}

	/**
	 * Render the dashboard widget.
	 */
	public function render_widget() {
		$status = $this->get_health_status();
		?>
		<div class="qo-widget" data-health="<?php echo esc_attr( $status['health'] ); ?>">
			<?php if ( ! $status['available'] ) : ?>
				<div class="qo-widget-inactive">
					<span class="dashicons dashicons-info-outline"></span>
					<p><?php esc_html_e( 'ActionScheduler not detected. Install WooCommerce or a compatible plugin.', '365i-queue-optimizer' ); ?></p>
				</div>
			<?php else : ?>
				<div class="qo-widget-header">
					<div class="qo-health-indicator">
						<span class="qo-health-dot"></span>
						<span class="qo-health-label"><?php echo esc_html( $status['label'] ); ?></span>
					</div>
					<?php if ( $status['running'] > 0 ) : ?>
						<span class="qo-processing-badge">
							<span class="qo-pulse"></span>
							<?php esc_html_e( 'Processing', '365i-queue-optimizer' ); ?>
						</span>
					<?php endif; ?>
				</div>

				<div class="qo-widget-stats">
					<div class="qo-stat">
						<span class="qo-stat-value" id="qo-widget-pending"><?php echo esc_html( $status['pending'] ); ?></span>
						<span class="qo-stat-label"><?php esc_html_e( 'Pending', '365i-queue-optimizer' ); ?></span>
					</div>
					<div class="qo-stat">
						<span class="qo-stat-value"><?php echo esc_html( $status['running'] ); ?></span>
						<span class="qo-stat-label"><?php esc_html_e( 'Running', '365i-queue-optimizer' ); ?></span>
					</div>
					<div class="qo-stat <?php echo $status['failed'] > 0 ? 'qo-stat-alert' : ''; ?>">
						<span class="qo-stat-value"><?php echo esc_html( $status['failed'] ); ?></span>
						<span class="qo-stat-label"><?php esc_html_e( 'Failed', '365i-queue-optimizer' ); ?></span>
					</div>
				</div>

				<?php if ( 'critical' === $status['health'] ) : ?>
					<div class="qo-widget-alert">
						<span class="dashicons dashicons-warning"></span>
						<?php
						printf(
							/* translators: %d: number of failed actions */
							esc_html__( '%d actions failed in the last 24 hours.', '365i-queue-optimizer' ),
							absint( $status['failed'] )
						);
						?>
						<a href="<?php echo esc_url( admin_url( 'tools.php?page=action-scheduler&status=failed' ) ); ?>">
							<?php esc_html_e( 'View', '365i-queue-optimizer' ); ?>
						</a>
					</div>
				<?php endif; ?>

				<div class="qo-widget-actions">
					<button type="button" id="qo-widget-run" class="button button-primary" <?php echo 0 === $status['pending'] ? 'disabled' : ''; ?>>
						<span class="dashicons dashicons-controls-play"></span>
						<?php esc_html_e( 'Run Queue', '365i-queue-optimizer' ); ?>
					</button>
					<a href="<?php echo esc_url( admin_url( 'tools.php?page=queue-optimizer' ) ); ?>" class="button">
						<?php esc_html_e( 'Settings', '365i-queue-optimizer' ); ?>
					</a>
				</div>

				<div class="qo-widget-result" id="qo-widget-result"></div>
			<?php endif; ?>
		</div>
		<?php
	}
}
