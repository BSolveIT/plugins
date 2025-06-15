<?php
/**
 * Dashboard Page Management
 *
 * Handles the main dashboard page display and functionality.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard Page class.
 */
class Queue_Optimizer_Dashboard_Page {

	/**
	 * Single instance of the class.
	 *
	 * @var Queue_Optimizer_Dashboard_Page
	 */
	private static $instance = null;

	/**
	 * Get single instance of the class.
	 *
	 * @return Queue_Optimizer_Dashboard_Page
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
		// Register AJAX handlers.
		add_action( 'wp_ajax_queue_optimizer_refresh_stats', array( $this, 'ajax_refresh_stats' ) );
		add_action( 'wp_ajax_queue_optimizer_quick_action', array( $this, 'ajax_quick_action' ) );
	}

	/**
	 * Render the dashboard page.
	 */
	public function render_page() {
		// Gather dashboard data.
		$data = $this->gather_dashboard_data();

		// Set page variables for header.
		$page_title = __( 'Queue Optimizer Dashboard', '365i-queue-optimizer' );
		$page_description = __( 'Monitor and manage your WordPress queue optimization system.', '365i-queue-optimizer' );

		// Include the dashboard template.
		include plugin_dir_path( __FILE__ ) . '../templates/dashboard.php';
	}

	/**
	 * Gather dashboard data.
	 *
	 * @return array Dashboard data array.
	 */
	private function gather_dashboard_data() {
		$data = array();

		// Queue statistics.
		$data['queue_stats'] = $this->get_queue_statistics();

		// System status.
		$data['system_status'] = $this->get_system_status();

		// Recent activity.
		$data['recent_activity'] = $this->get_recent_activity();

		// Plugin settings.
		$data['plugin_settings'] = $this->get_plugin_settings();

		// Quick actions.
		$data['quick_actions'] = $this->get_quick_actions();

		// Apply filter for extensibility.
		return apply_filters( 'queue_optimizer_dashboard_data', $data );
	}

	/**
	 * Get queue statistics.
	 *
	 * @return array Queue statistics.
	 */
	private function get_queue_statistics() {
		global $wpdb;

		$stats = array(
			'total_jobs' => 0,
			'pending_jobs' => 0,
			'completed_jobs' => 0,
			'failed_jobs' => 0,
			'in_progress_jobs' => 0,
		);

		// Check if Action Scheduler is available.
		if ( class_exists( 'ActionScheduler' ) ) {
			$table_name = $wpdb->prefix . 'actionscheduler_actions';

			// Total jobs.
			$stats['total_jobs'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

			// Pending jobs.
			$stats['pending_jobs'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'pending' ) );

			// Completed jobs.
			$stats['completed_jobs'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'complete' ) );

			// Failed jobs.
			$stats['failed_jobs'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'failed' ) );

			// In progress jobs.
			$stats['in_progress_jobs'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'in-progress' ) );
		}

		return $stats;
	}

	/**
	 * Get system status.
	 *
	 * @return array System status.
	 */
	private function get_system_status() {
		$status = array(
			'overall_status' => 'good',
			'queue_status' => 'running',
			'last_run' => get_option( 'queue_optimizer_last_run', __( 'Never', '365i-queue-optimizer' ) ),
			'php_version' => PHP_VERSION,
			'wp_version' => get_bloginfo( 'version' ),
			'plugin_version' => QUEUE_OPTIMIZER_VERSION,
		);

		// Check Action Scheduler status.
		if ( ! class_exists( 'ActionScheduler' ) ) {
			$status['overall_status'] = 'error';
			$status['queue_status'] = 'unavailable';
		}

		// Check for critical PHP version.
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			$status['overall_status'] = 'warning';
		}

		return $status;
	}

	/**
	 * Get recent activity.
	 *
	 * @return array Recent activity.
	 */
	private function get_recent_activity() {
		global $wpdb;

		$activity = array();

		if ( class_exists( 'ActionScheduler' ) ) {
			$table_name = $wpdb->prefix . 'actionscheduler_actions';
			
			$recent_jobs = $wpdb->get_results( $wpdb->prepare( 
				"SELECT hook, status, last_attempt_gmt, last_attempt_local 
				FROM {$table_name} 
				WHERE last_attempt_gmt IS NOT NULL 
				ORDER BY last_attempt_gmt DESC 
				LIMIT %d", 
				10 
			) );

			foreach ( $recent_jobs as $job ) {
				$activity[] = array(
					'action' => $job->hook,
					'status' => $job->status,
					'timestamp' => $job->last_attempt_local,
					'time_ago' => human_time_diff( strtotime( $job->last_attempt_gmt ) ),
				);
			}
		}

		return $activity;
	}

	/**
	 * Get plugin settings.
	 *
	 * @return array Plugin settings.
	 */
	private function get_plugin_settings() {
		return array(
			'retention_days' => get_option( 'queue_optimizer_retention_days', 30 ),
			'auto_cleanup' => get_option( 'queue_optimizer_auto_cleanup', 'yes' ),
			'debug_mode' => get_option( 'queue_optimizer_debug_mode', 'no' ),
			'email_notifications' => get_option( 'queue_optimizer_email_notifications', 'no' ),
		);
	}

	/**
	 * Get quick actions.
	 *
	 * @return array Quick actions.
	 */
	private function get_quick_actions() {
		return array(
			array(
				'title' => __( 'Run Queue Cleanup', '365i-queue-optimizer' ),
				'description' => __( 'Manually trigger queue cleanup process', '365i-queue-optimizer' ),
				'action' => 'run_cleanup',
				'class' => 'button-primary',
			),
			array(
				'title' => __( 'View System Info', '365i-queue-optimizer' ),
				'description' => __( 'Check detailed system information', '365i-queue-optimizer' ),
				'action' => 'view_system_info',
				'class' => 'button-secondary',
				'url' => admin_url( 'admin.php?page=365i-system-info' ),
			),
			array(
				'title' => __( 'Clear Failed Jobs', '365i-queue-optimizer' ),
				'description' => __( 'Remove all failed queue jobs', '365i-queue-optimizer' ),
				'action' => 'clear_failed',
				'class' => 'button-secondary',
			),
		);
	}

	/**
		* AJAX handler for refreshing dashboard statistics.
		*/
	public function ajax_refresh_stats() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'queue_optimizer_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', '365i-queue-optimizer' ) ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', '365i-queue-optimizer' ) ) );
		}

		// Get fresh statistics.
		$stats = $this->get_queue_statistics();

		// Send success response with stats.
		wp_send_json_success( array( 'stats' => $stats ) );
	}

	/**
		* AJAX handler for quick actions.
		*/
	public function ajax_quick_action() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'queue_optimizer_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', '365i-queue-optimizer' ) ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', '365i-queue-optimizer' ) ) );
		}

		// Get the quick action.
		$quick_action = isset( $_POST['quick_action'] ) ? sanitize_text_field( wp_unslash( $_POST['quick_action'] ) ) : '';

		if ( empty( $quick_action ) ) {
			wp_send_json_error( array( 'message' => __( 'No action specified.', '365i-queue-optimizer' ) ) );
		}

		// Handle different quick actions.
		switch ( $quick_action ) {
			case 'run_cleanup':
				$result = $this->run_queue_cleanup();
				break;

			case 'clear_failed':
				$result = $this->clear_failed_jobs();
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Invalid action.', '365i-queue-optimizer' ) ) );
				return;
		}

		// Send response.
		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
		* Run queue cleanup process.
		*
		* @return array Result array with success status and message.
		*/
	private function run_queue_cleanup() {
		global $wpdb;

		if ( ! class_exists( 'ActionScheduler' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Action Scheduler is not available.', '365i-queue-optimizer' ),
			);
		}

		try {
			$table_name = $wpdb->prefix . 'actionscheduler_actions';
			$retention_days = get_option( 'queue_optimizer_retention_days', 30 );
			$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

			// Delete old completed jobs.
			$deleted = $wpdb->query( $wpdb->prepare(
				"DELETE FROM {$table_name} WHERE status = %s AND last_attempt_gmt < %s",
				'complete',
				$cutoff_date
			) );

			// Update last run time.
			update_option( 'queue_optimizer_last_run', current_time( 'mysql' ) );

			return array(
				'success' => true,
				'message' => sprintf(
					/* translators: %d: Number of jobs cleaned up */
					__( 'Cleanup completed. %d old jobs removed.', '365i-queue-optimizer' ),
					$deleted
				),
			);
		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: Error message */
					__( 'Cleanup failed: %s', '365i-queue-optimizer' ),
					$e->getMessage()
				),
			);
		}
	}

	/**
		* Clear all failed jobs.
		*
		* @return array Result array with success status and message.
		*/
	private function clear_failed_jobs() {
		global $wpdb;

		if ( ! class_exists( 'ActionScheduler' ) ) {
			return array(
				'success' => false,
				'message' => __( 'Action Scheduler is not available.', '365i-queue-optimizer' ),
			);
		}

		try {
			$table_name = $wpdb->prefix . 'actionscheduler_actions';

			// Delete failed jobs.
			$deleted = $wpdb->query( $wpdb->prepare(
				"DELETE FROM {$table_name} WHERE status = %s",
				'failed'
			) );

			return array(
				'success' => true,
				'message' => sprintf(
					/* translators: %d: Number of failed jobs cleared */
					__( 'Cleared %d failed jobs.', '365i-queue-optimizer' ),
					$deleted
				),
			);
		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: Error message */
					__( 'Failed to clear jobs: %s', '365i-queue-optimizer' ),
					$e->getMessage()
				),
			);
		}
	}
}