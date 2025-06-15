<?php
/**
 * Activity Log Page Management
 *
 * Handles the activity log page display and functionality.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activity Log Page class.
 */
class Queue_Optimizer_Activity_Log_Page {

	/**
	 * Single instance of the class.
	 *
	 * @var Queue_Optimizer_Activity_Log_Page
	 */
	private static $instance = null;

	/**
	 * Get single instance of the class.
	 *
	 * @return Queue_Optimizer_Activity_Log_Page
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
		add_action( 'wp_ajax_queue_optimizer_clear_logs', array( $this, 'handle_clear_logs' ) );
		add_action( 'wp_ajax_queue_optimizer_export_logs', array( $this, 'handle_export_logs' ) );
		add_action( 'wp_ajax_queue_optimizer_retry_action', array( $this, 'handle_retry_action' ) );
		add_action( 'wp_ajax_queue_optimizer_cancel_action', array( $this, 'handle_cancel_action' ) );
		add_action( 'wp_ajax_queue_optimizer_bulk_actions', array( $this, 'handle_bulk_actions' ) );
	}

	/**
	 * Render the activity log page.
	 */
	public function render_page() {
		// Gather activity log data.
		$data = $this->gather_activity_data();

		// Set page variables for header.
		$page_title = __( 'Activity Log', '365i-queue-optimizer' );
		$page_description = __( 'View and manage queue activity logs and system events.', '365i-queue-optimizer' );

		// Include the activity log template.
		include plugin_dir_path( __FILE__ ) . '../templates/activity-log.php';
	}

	/**
	 * Gather activity log data.
	 *
	 * @return array Activity log data array.
	 */
	private function gather_activity_data() {
		$data = array();

		// Get activity logs.
		$data['activity_logs'] = $this->get_activity_logs();

		// Get log statistics.
		$data['log_stats'] = $this->get_log_statistics();

		// Get system events.
		$data['system_events'] = $this->get_system_events();

		// Log management settings.
		$data['log_settings'] = $this->get_log_settings();

		// Apply filter for extensibility.
		return apply_filters( 'queue_optimizer_activity_log_data', $data );
	}

	/**
	 * Get activity logs.
	 *
	 * @param int $limit Number of logs to retrieve.
	 * @param int $offset Offset for pagination.
	 * @return array Activity logs.
	 */
	private function get_activity_logs( $limit = 50, $offset = 0 ) {
		global $wpdb;

		$logs = array();

		if ( class_exists( 'ActionScheduler' ) ) {
			$table_name = $wpdb->prefix . 'actionscheduler_actions';
			$logs_table = $wpdb->prefix . 'actionscheduler_logs';

			try {
				$query = $wpdb->prepare(
					"SELECT a.action_id, a.hook, a.status, a.scheduled_date_gmt, a.last_attempt_gmt,
					        a.last_attempt_local, a.claim_id, a.args, l.message, l.log_date_gmt,
					        COALESCE(a.last_attempt_gmt, a.scheduled_date_gmt) as sort_date
					FROM {$table_name} a
					LEFT JOIN {$logs_table} l ON a.action_id = l.action_id AND l.log_date_gmt = (
						SELECT MAX(log_date_gmt) FROM {$logs_table} WHERE action_id = a.action_id
					)
					WHERE a.status IN ('complete', 'failed', 'pending', 'in-progress', 'canceled')
					ORDER BY sort_date DESC
					LIMIT %d OFFSET %d",
					$limit,
					$offset
				);

				$results = $wpdb->get_results( $query );

				foreach ( $results as $result ) {
					$executed_time = $result->last_attempt_local ?: $result->scheduled_date_gmt;
					$time_reference = $result->last_attempt_gmt ?: $result->scheduled_date_gmt;
					
					$logs[] = array(
						'id' => $result->action_id,
						'action' => $result->hook,
						'status' => $result->status,
						'scheduled' => $result->scheduled_date_gmt,
						'executed' => $executed_time,
						'message' => $result->message ?: $this->get_status_message( $result->status ),
						'time_ago' => human_time_diff( strtotime( $time_reference ) ),
						'args' => $result->args,
						'can_retry' => in_array( $result->status, array( 'failed', 'canceled' ) ),
						'can_cancel' => in_array( $result->status, array( 'pending', 'in-progress' ) ),
					);
				}
			} catch ( Exception $e ) {
				$logs[] = array(
					'id' => 0,
					'action' => __( 'Error loading logs', '365i-queue-optimizer' ),
					'status' => 'error',
					'scheduled' => '',
					'executed' => current_time( 'mysql' ),
					'message' => $e->getMessage(),
					'time_ago' => '0 seconds',
					'args' => '',
					'can_retry' => false,
					'can_cancel' => false,
				);
			}
		}

		return $logs;
	}

	/**
	 * Get status message for entries without log messages.
	 *
	 * @param string $status Action status.
	 * @return string Status message.
	 */
	private function get_status_message( $status ) {
		$messages = array(
			'complete' => __( 'Action completed successfully', '365i-queue-optimizer' ),
			'failed' => __( 'Action failed to execute', '365i-queue-optimizer' ),
			'pending' => __( 'Action is pending execution', '365i-queue-optimizer' ),
			'in-progress' => __( 'Action is currently running', '365i-queue-optimizer' ),
			'canceled' => __( 'Action was canceled', '365i-queue-optimizer' ),
		);

		return $messages[ $status ] ?? __( 'No message available', '365i-queue-optimizer' );
	}

	/**
	 * Get log statistics.
	 *
	 * @return array Log statistics.
	 */
	private function get_log_statistics() {
		global $wpdb;

		$stats = array(
			'total_logs' => 0,
			'error_logs' => 0,
			'success_logs' => 0,
			'pending_logs' => 0,
		);

		if ( class_exists( 'ActionScheduler' ) ) {
			$table_name = $wpdb->prefix . 'actionscheduler_actions';

			try {
				$stats['total_logs'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
				$stats['error_logs'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'failed' ) );
				$stats['success_logs'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'complete' ) );
				$stats['pending_logs'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'pending' ) );
			} catch ( Exception $e ) {
				// Keep default values on error.
			}
		}

		return $stats;
	}

	/**
	 * Get system events.
	 *
	 * @return array System events.
	 */
	private function get_system_events() {
		$events = array();

		// Get plugin activation/deactivation events.
		$plugin_events = get_option( 'queue_optimizer_system_events', array() );
		
		// Add recent plugin events.
		foreach ( array_slice( $plugin_events, -10 ) as $event ) {
			$events[] = array(
				'type' => 'system',
				'event' => $event['event'] ?? __( 'Unknown event', '365i-queue-optimizer' ),
				'timestamp' => $event['timestamp'] ?? current_time( 'mysql' ),
				'details' => $event['details'] ?? '',
			);
		}

		// Add WordPress error log events if debug mode is enabled.
		if ( get_option( 'queue_optimizer_debug_mode', 'no' ) === 'yes' ) {
			$debug_logs = $this->get_debug_logs();
			$events = array_merge( $events, $debug_logs );
		}

		return $events;
	}

	/**
	 * Get debug logs.
	 *
	 * @return array Debug logs.
	 */
	private function get_debug_logs() {
		$logs = array();
		$debug_log_option = get_option( 'queue_optimizer_debug_logs', array() );

		foreach ( array_slice( $debug_log_option, -20 ) as $log ) {
			$logs[] = array(
				'type' => 'debug',
				'event' => $log['message'] ?? __( 'Debug log entry', '365i-queue-optimizer' ),
				'timestamp' => $log['timestamp'] ?? current_time( 'mysql' ),
				'details' => $log['context'] ?? '',
			);
		}

		return $logs;
	}

	/**
	 * Get log settings.
	 *
	 * @return array Log settings.
	 */
	private function get_log_settings() {
		return array(
			'logging_enabled' => get_option( 'queue_optimizer_logging_enabled', true ) ? 'yes' : 'no',
			'debug_mode' => get_option( 'queue_optimizer_debug_mode', false ) ? 'yes' : 'no',
			'log_retention_days' => get_option( 'queue_optimizer_log_retention_days', 7 ),
			'max_log_entries' => get_option( 'queue_optimizer_max_log_entries', 1000 ),
		);
	}

	/**
	 * Handle clear logs AJAX request.
	 */
	public function handle_clear_logs() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'queue_optimizer_admin_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', '365i-queue-optimizer' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', '365i-queue-optimizer' ) );
		}

		$log_type = sanitize_text_field( $_POST['log_type'] ?? 'all' );
		$cleared_count = 0;

		try {
			if ( 'debug' === $log_type ) {
				delete_option( 'queue_optimizer_debug_logs' );
				$cleared_count = 1;
			} elseif ( 'system' === $log_type ) {
				delete_option( 'queue_optimizer_system_events' );
				$cleared_count = 1;
			} elseif ( 'all' === $log_type && class_exists( 'ActionScheduler' ) ) {
				// Clear ActionScheduler logs older than retention period.
				global $wpdb;
				$retention_days = get_option( 'queue_optimizer_log_retention_days', 30 );
				$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

				$logs_table = $wpdb->prefix . 'actionscheduler_logs';
				$actions_table = $wpdb->prefix . 'actionscheduler_actions';

				$cleared_count = $wpdb->query( $wpdb->prepare(
					"DELETE FROM {$logs_table} WHERE log_date_gmt < %s",
					$cutoff_date
				) );

				$wpdb->query( $wpdb->prepare(
					"DELETE FROM {$actions_table} WHERE last_attempt_gmt < %s AND status IN ('complete', 'failed')",
					$cutoff_date
				) );
			}

			wp_send_json_success( array(
				'message' => sprintf( __( 'Successfully cleared %d log entries.', '365i-queue-optimizer' ), $cleared_count ),
				'cleared_count' => $cleared_count,
			) );
		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => __( 'Failed to clear logs: ', '365i-queue-optimizer' ) . $e->getMessage(),
			) );
		}
	}

	/**
	 * Handle export logs AJAX request.
	 */
	public function handle_export_logs() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'queue_optimizer_admin_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', '365i-queue-optimizer' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', '365i-queue-optimizer' ) );
		}

		$format = sanitize_text_field( $_POST['format'] ?? 'csv' );
		$logs = $this->get_activity_logs( 1000 ); // Get more logs for export

		if ( 'csv' === $format ) {
			$csv_data = $this->export_logs_as_csv( $logs );
			wp_send_json_success( array(
				'data' => $csv_data,
				'filename' => 'queue-optimizer-logs-' . date( 'Y-m-d-H-i-s' ) . '.csv',
			) );
		} else {
			wp_send_json_success( array(
				'data' => wp_json_encode( $logs, JSON_PRETTY_PRINT ),
				'filename' => 'queue-optimizer-logs-' . date( 'Y-m-d-H-i-s' ) . '.json',
			) );
		}
	}

	/**
	 * Export logs as CSV format.
	 *
	 * @param array $logs Logs to export.
	 * @return string CSV data.
	 */
	private function export_logs_as_csv( $logs ) {
		$csv_data = "Action,Status,Scheduled,Executed,Message,Time Ago\n";

		foreach ( $logs as $log ) {
			$csv_data .= sprintf(
				'"%s","%s","%s","%s","%s","%s"' . "\n",
				str_replace( '"', '""', $log['action'] ),
				str_replace( '"', '""', $log['status'] ),
				str_replace( '"', '""', $log['scheduled'] ),
				str_replace( '"', '""', $log['executed'] ),
				str_replace( '"', '""', $log['message'] ),
				str_replace( '"', '""', $log['time_ago'] )
			);
		}

		return $csv_data;
	}

	/**
	 * Handle retry action AJAX request.
	 */
	public function handle_retry_action() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'queue_optimizer_admin_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', '365i-queue-optimizer' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', '365i-queue-optimizer' ) );
		}

		$action_id = absint( $_POST['action_id'] ?? 0 );

		if ( ! $action_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid action ID.', '365i-queue-optimizer' ) ) );
		}

		try {
			if ( class_exists( 'ActionScheduler' ) && function_exists( 'as_get_scheduled_actions' ) ) {
				// Get the original action details
				global $wpdb;
				$table_name = $wpdb->prefix . 'actionscheduler_actions';
				
				$action = $wpdb->get_row( $wpdb->prepare(
					"SELECT hook, args, schedule FROM {$table_name} WHERE action_id = %d",
					$action_id
				) );

				if ( $action ) {
					$args = json_decode( $action->args, true ) ?: array();
					
					// Schedule the action again
					$new_action_id = as_schedule_single_action( time(), $action->hook, $args );
					
					if ( $new_action_id ) {
						wp_send_json_success( array(
							'message' => __( 'Action has been scheduled for retry.', '365i-queue-optimizer' ),
							'new_action_id' => $new_action_id,
						) );
					} else {
						wp_send_json_error( array( 'message' => __( 'Failed to schedule retry.', '365i-queue-optimizer' ) ) );
					}
				} else {
					wp_send_json_error( array( 'message' => __( 'Action not found.', '365i-queue-optimizer' ) ) );
				}
			} else {
				wp_send_json_error( array( 'message' => __( 'ActionScheduler not available.', '365i-queue-optimizer' ) ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Failed to retry action: ', '365i-queue-optimizer' ) . $e->getMessage() ) );
		}
	}

	/**
	 * Handle cancel action AJAX request.
	 */
	public function handle_cancel_action() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'queue_optimizer_admin_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', '365i-queue-optimizer' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', '365i-queue-optimizer' ) );
		}

		$action_id = absint( $_POST['action_id'] ?? 0 );

		if ( ! $action_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid action ID.', '365i-queue-optimizer' ) ) );
		}

		try {
			if ( class_exists( 'ActionScheduler' ) && function_exists( 'as_unschedule_action' ) ) {
				global $wpdb;
				$table_name = $wpdb->prefix . 'actionscheduler_actions';
				
				// Get the action details
				$action = $wpdb->get_row( $wpdb->prepare(
					"SELECT hook, args FROM {$table_name} WHERE action_id = %d AND status IN ('pending', 'in-progress')",
					$action_id
				) );

				if ( $action ) {
					$args = json_decode( $action->args, true ) ?: array();
					
					// Cancel the action
					$result = as_unschedule_action( $action->hook, $args );
					
					if ( $result ) {
						wp_send_json_success( array(
							'message' => __( 'Action has been canceled.', '365i-queue-optimizer' ),
						) );
					} else {
						wp_send_json_error( array( 'message' => __( 'Failed to cancel action.', '365i-queue-optimizer' ) ) );
					}
				} else {
					wp_send_json_error( array( 'message' => __( 'Action not found or cannot be canceled.', '365i-queue-optimizer' ) ) );
				}
			} else {
				wp_send_json_error( array( 'message' => __( 'ActionScheduler not available.', '365i-queue-optimizer' ) ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Failed to cancel action: ', '365i-queue-optimizer' ) . $e->getMessage() ) );
		}
	}

	/**
	 * Handle bulk actions AJAX request.
	 */
	public function handle_bulk_actions() {
		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'queue_optimizer_admin_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', '365i-queue-optimizer' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', '365i-queue-optimizer' ) );
		}

		$bulk_action = sanitize_text_field( $_POST['bulk_action'] ?? '' );
		$action_ids = array_map( 'absint', $_POST['action_ids'] ?? array() );

		if ( empty( $bulk_action ) || empty( $action_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid bulk action or no actions selected.', '365i-queue-optimizer' ) ) );
		}

		$success_count = 0;
		$error_count = 0;

		try {
			foreach ( $action_ids as $action_id ) {
				if ( 'retry' === $bulk_action ) {
					// Retry logic (similar to individual retry)
					if ( $this->retry_single_action( $action_id ) ) {
						$success_count++;
					} else {
						$error_count++;
					}
				} elseif ( 'cancel' === $bulk_action ) {
					// Cancel logic (similar to individual cancel)
					if ( $this->cancel_single_action( $action_id ) ) {
						$success_count++;
					} else {
						$error_count++;
					}
				}
			}

			$message = sprintf(
				__( 'Bulk action completed: %d successful, %d failed.', '365i-queue-optimizer' ),
				$success_count,
				$error_count
			);

			wp_send_json_success( array(
				'message' => $message,
				'success_count' => $success_count,
				'error_count' => $error_count,
			) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Bulk action failed: ', '365i-queue-optimizer' ) . $e->getMessage() ) );
		}
	}

	/**
	 * Retry a single action.
	 *
	 * @param int $action_id Action ID to retry.
	 * @return bool Success status.
	 */
	private function retry_single_action( $action_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'actionscheduler_actions';
		
		$action = $wpdb->get_row( $wpdb->prepare(
			"SELECT hook, args FROM {$table_name} WHERE action_id = %d",
			$action_id
		) );

		if ( $action && class_exists( 'ActionScheduler' ) && function_exists( 'as_schedule_single_action' ) ) {
			$args = json_decode( $action->args, true ) ?: array();
			return (bool) as_schedule_single_action( time(), $action->hook, $args );
		}

		return false;
	}

	/**
	 * Cancel a single action.
	 *
	 * @param int $action_id Action ID to cancel.
	 * @return bool Success status.
	 */
	private function cancel_single_action( $action_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'actionscheduler_actions';
		
		$action = $wpdb->get_row( $wpdb->prepare(
			"SELECT hook, args FROM {$table_name} WHERE action_id = %d AND status IN ('pending', 'in-progress')",
			$action_id
		) );

		if ( $action && class_exists( 'ActionScheduler' ) && function_exists( 'as_unschedule_action' ) ) {
			$args = json_decode( $action->args, true ) ?: array();
			return (bool) as_unschedule_action( $action->hook, $args );
		}

		return false;
	}
}