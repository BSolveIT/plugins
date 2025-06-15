<?php
/**
 * Queue Optimizer Scheduler Class
 *
 * Handles background queue processing and scheduling logic.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queue Optimizer Scheduler class.
 */
class Queue_Optimizer_Scheduler {

	/**
	 * Single instance of the scheduler.
	 *
	 * @var Queue_Optimizer_Scheduler
	 */
	private static $instance = null;

	/**
	 * Current processing batch.
	 *
	 * @var array
	 */
	private $current_batch = array();

	/**
	 * Current run ID for tracking queue processing sessions.
	 *
	 * @var string|null
	 */
	private $current_run_id = null;

	/**
	 * Run start time for performance tracking.
	 *
	 * @var float|null
	 */
	private $run_start_time = null;

	/**
	 * Memory usage at run start.
	 *
	 * @var int|null
	 */
	private $run_start_memory = null;

	/**
	 * Track action start times for duration calculation.
	 *
	 * @var array
	 */
	private $action_start_times = array();

	/**
	 * Track action start memory for memory delta calculation.
	 *
	 * @var array
	 */
	private $action_start_memory = array();

	/**
	 * Get single instance of the scheduler.
	 *
	 * @return Queue_Optimizer_Scheduler
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
	 * Initialize the scheduler.
	 */
	private function init() {
		// Hook into the scheduled event.
		add_action( 'queue_optimizer_process_queue', array( $this, 'process_queue' ) );

		// AJAX handlers for manual queue processing.
		add_action( 'wp_ajax_queue_optimizer_run_now', array( $this, 'ajax_run_now' ) );
		add_action( 'wp_ajax_queue_optimizer_clear_logs', array( $this, 'ajax_clear_logs' ) );
		add_action( 'wp_ajax_queue_optimizer_clear_action_scheduler_logs', array( $this, 'ajax_clear_action_scheduler_logs' ) );
		add_action( 'wp_ajax_queue_optimizer_get_status', array( $this, 'ajax_get_status' ) );
		add_action( 'wp_ajax_queue_optimizer_get_logs', array( $this, 'ajax_get_logs' ) );

		// Hook into comprehensive Action Scheduler events for JSON logging.
		add_action( 'action_scheduler_before_process_queue', array( $this, 'log_run_start' ) );
		add_action( 'action_scheduler_after_process_queue', array( $this, 'log_run_end' ) );
		add_action( 'action_scheduler_stored_action', array( $this, 'log_action_scheduled' ) );
		add_action( 'action_scheduler_before_execute', array( $this, 'log_action_started' ), 10, 2 );
		add_action( 'action_scheduler_after_execute', array( $this, 'log_action_completed' ), 10, 3 );
		add_action( 'action_scheduler_failed_execution', array( $this, 'log_action_failed' ), 10, 3 );
		add_action( 'action_scheduler_canceled_action', array( $this, 'log_action_canceled' ), 10, 2 );
		
		// Initialize tracking variables
		$this->current_run_id = null;
		$this->run_start_time = null;
		$this->run_start_memory = null;
		$this->action_start_times = array();
		$this->action_start_memory = array();

		// Schedule daily cleanup
		$this->schedule_daily_cleanup();
	}

	/**
	 * Process the queue.
	 * This method performs maintenance tasks and cleanup.
	 */
	public function process_queue() {
		$logging_enabled = (bool) get_option( 'queue_optimizer_logging_enabled', false );

		if ( $logging_enabled ) {
			// Log current Action Scheduler status
			$status = $this->get_queue_status();
			$this->log_json_event( 'maintenance_start', array(
				'pending' => $status['pending'],
				'processing' => $status['processing'],
				'completed' => $status['completed'],
				'failed' => $status['failed'],
			) );
		}

		// Update completion timestamp.
		update_option( 'queue_optimizer_last_run', time() );

		// Clean up old log files based on retention setting.
		$this->cleanup_old_logs();

		if ( $logging_enabled ) {
			$this->log_json_event( 'maintenance_end', array() );
		}
	}



	/**
	 * Get queue status counts from Action Scheduler.
	 *
	 * @return array Array with pending, processing, completed, and failed counts.
	 */
	public function get_queue_status() {
		// Check if Action Scheduler is available
		if ( ! class_exists( 'ActionScheduler' ) ) {
			return array(
				'pending'    => 0,
				'processing' => 0,
				'completed'  => 0,
				'failed'     => 0,
				'last_run'   => get_option( 'queue_optimizer_last_run', 0 ),
			);
		}

		$store = ActionScheduler_Store::instance();
		
		// Get counts from Action Scheduler
		$pending_count = $store->query_actions( array(
			'status' => ActionScheduler_Store::STATUS_PENDING,
			'per_page' => 0,
		) );
		
		$running_count = $store->query_actions( array(
			'status' => ActionScheduler_Store::STATUS_RUNNING,
			'per_page' => 0,
		) );
		
		$complete_count = $store->query_actions( array(
			'status' => ActionScheduler_Store::STATUS_COMPLETE,
			'per_page' => 0,
		) );

		$failed_count = $store->query_actions( array(
			'status' => ActionScheduler_Store::STATUS_FAILED,
			'per_page' => 0,
		) );

		return array(
			'pending'    => is_array( $pending_count ) ? count( $pending_count ) : 0,
			'processing' => is_array( $running_count ) ? count( $running_count ) : 0,
			'completed'  => is_array( $complete_count ) ? count( $complete_count ) : 0,
			'failed'     => is_array( $failed_count ) ? count( $failed_count ) : 0,
			'last_run'   => get_option( 'queue_optimizer_last_run', 0 ),
		);
	}

	/**
	 * AJAX handler for manual queue processing.
	 */
	public function ajax_run_now() {
		// Verify nonce and permissions.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'queue_optimizer_admin_nonce' ) ||
			 ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'Security check failed.', '365i-queue-optimizer' ),
				esc_html__( 'Error', '365i-queue-optimizer' ),
				array( 'response' => 403 )
			);
		}

		// Trigger Action Scheduler queue processing if available
		if ( class_exists( 'ActionScheduler_QueueRunner' ) ) {
			$queue_runner = ActionScheduler_QueueRunner::instance();
			$queue_runner->run();
			
			$this->log( __( 'Manual Action Scheduler queue processing triggered.', '365i-queue-optimizer' ) );
		} else {
			// Fallback: process our own queue
			$this->process_queue();
		}

		// Update completion timestamp.
		update_option( 'queue_optimizer_last_run', time() );

		// Return updated status.
		$updated_status = $this->get_queue_status();
		
		wp_send_json_success( array(
			'message' => __( 'Queue processing completed successfully.', '365i-queue-optimizer' ),
			'status'  => $updated_status,
		) );
	}

	/**
	 * AJAX handler for clearing logs.
	 */
	public function ajax_clear_logs() {
		// Verify nonce and permissions.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'queue_optimizer_admin_nonce' ) ||
			 ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'Security check failed.', '365i-queue-optimizer' ),
				esc_html__( 'Error', '365i-queue-optimizer' ),
				array( 'response' => 403 )
			);
		}

		// Clear log files.
		$logs_cleared = $this->clear_logs();

		wp_send_json_success( array(
			'message' => $logs_cleared ?
				__( 'Plugin logs cleared successfully.', '365i-queue-optimizer' ) :
				__( 'No plugin logs found to clear.', '365i-queue-optimizer' ),
		) );
	}

	/**
	 * AJAX handler for clearing Action Scheduler logs.
	 */
	public function ajax_clear_action_scheduler_logs() {
		// Verify nonce and permissions.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'queue_optimizer_admin_nonce' ) ||
			 ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'Security check failed.', '365i-queue-optimizer' ),
				esc_html__( 'Error', '365i-queue-optimizer' ),
				array( 'response' => 403 )
			);
		}

		// Clear Action Scheduler logs (completed and failed actions).
		$cleared_count = $this->clear_action_scheduler_logs();

		wp_send_json_success( array(
			'message' => $cleared_count > 0 ?
				sprintf(
					/* translators: %d: number of cleared actions */
					__( 'Cleared %d completed and failed Action Scheduler entries.', '365i-queue-optimizer' ),
					$cleared_count
				) :
				__( 'No completed or failed Action Scheduler entries found to clear.', '365i-queue-optimizer' ),
		) );
	}

	/**
	 * AJAX handler for getting queue status.
	 */
	public function ajax_get_status() {
		// Verify nonce and permissions.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'queue_optimizer_admin_nonce' ) ||
			 ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'Security check failed.', '365i-queue-optimizer' ),
				esc_html__( 'Error', '365i-queue-optimizer' ),
				array( 'response' => 403 )
			);
		}

		// Get current queue status.
		$status = $this->get_queue_status();

		wp_send_json_success( array(
			'status' => $status,
		) );
	}

	/**
	 * Log a JSON event to the master log file.
	 *
	 * @param string $event Event type.
	 * @param array  $data  Event data.
	 */
	private function log_json_event( $event, $data = array() ) {
		$logging_enabled = (bool) get_option( 'queue_optimizer_logging_enabled', false );
		
		if ( ! $logging_enabled ) {
			return;
		}

		// Prepare log entry
		$log_entry = array(
			'time'  => current_time( 'c' ), // ISO 8601 format
			'event' => $event,
		);

		// Merge in event-specific data
		$log_entry = array_merge( $log_entry, $data );

		// Get uploads directory
		$upload_dir = wp_upload_dir();
		$log_file = $upload_dir['basedir'] . '/365i-queue-optimizer.log';

		// Check if file needs rotation (>10MB)
		if ( file_exists( $log_file ) && filesize( $log_file ) > 10 * 1024 * 1024 ) {
			$this->rotate_log_file( $log_file );
		}

		// Use WP_Filesystem for portability
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Convert to JSON and append newline
		$json_line = wp_json_encode( $log_entry ) . "\n";

		// Append to file
		if ( $wp_filesystem ) {
			$existing_content = $wp_filesystem->exists( $log_file ) ? $wp_filesystem->get_contents( $log_file ) : '';
			$wp_filesystem->put_contents( $log_file, $existing_content . $json_line );
		} else {
			// Fallback to direct file operations
			file_put_contents( $log_file, $json_line, FILE_APPEND | LOCK_EX );
		}
	}

	/**
	 * Rotate log file when it gets too large.
	 *
	 * @param string $log_file Path to log file.
	 */
	private function rotate_log_file( $log_file ) {
		$backup_file = $log_file . '.' . date( 'Y-m-d-H-i-s' );
		
		global $wp_filesystem;
		if ( $wp_filesystem && $wp_filesystem->exists( $log_file ) ) {
			$wp_filesystem->move( $log_file, $backup_file );
		} else {
			// Fallback
			if ( file_exists( $log_file ) ) {
				rename( $log_file, $backup_file );
			}
		}
	}

	/**
	 * Legacy log method for backwards compatibility.
	 *
	 * @param string $message Message to log.
	 */
	private function log( $message ) {
		$this->log_json_event( 'legacy_message', array( 'message' => $message ) );
	}

	/**
	 * AJAX handler for getting logs.
	 */
	public function ajax_get_logs() {
		// Verify nonce and permissions.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'queue_optimizer_admin_nonce' ) ||
			 ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'Security check failed.', '365i-queue-optimizer' ),
				esc_html__( 'Error', '365i-queue-optimizer' ),
				array( 'response' => 403 )
			);
		}

		$logs_content = $this->get_logs_content();

		wp_send_json_success( array(
			'logs' => $logs_content,
		) );
	}

	/**
	 * Get logs content.
	 *
	 * @return string Log content or empty string if no logs found.
	 */
	private function get_logs_content() {
		// Check for new JSON log file first
		$upload_dir = wp_upload_dir();
		$json_log_file = $upload_dir['basedir'] . '/365i-queue-optimizer.log';
		
		if ( file_exists( $json_log_file ) ) {
			return $this->get_json_logs_content( $json_log_file );
		}

		// Fall back to old log format
		$logs_dir = QUEUE_OPTIMIZER_PLUGIN_DIR . 'logs';
		
		if ( ! file_exists( $logs_dir ) ) {
			return __( 'No logs found.', '365i-queue-optimizer' );
		}

		// Get all log files, sorted by modification time (newest first).
		$log_files = glob( $logs_dir . '/*.log' );
		
		if ( empty( $log_files ) ) {
			return __( 'No log files found.', '365i-queue-optimizer' );
		}

		// Sort files by modification time, newest first.
		usort( $log_files, function( $a, $b ) {
			return filemtime( $b ) - filemtime( $a );
		} );

		$logs_content = '';
		$max_files = 5; // Show last 5 log files
		$files_shown = 0;

		foreach ( $log_files as $log_file ) {
			if ( $files_shown >= $max_files ) {
				break;
			}

			$file_name = basename( $log_file );
			$file_content = file_get_contents( $log_file );
			
			if ( false === $file_content || empty( trim( $file_content ) ) ) {
				continue;
			}

			$logs_content .= "=== {$file_name} ===\n";
			
			// Show last 100 lines of each file to prevent overwhelming output.
			$lines = explode( "\n", $file_content );
			$lines = array_slice( $lines, -100 );
			$logs_content .= implode( "\n", $lines );
			$logs_content .= "\n\n";
			
			$files_shown++;
		}

		if ( empty( trim( $logs_content ) ) ) {
			return __( 'Log files are empty.', '365i-queue-optimizer' );
		}

		return $logs_content;
	}

	/**
	 * Get formatted content from JSON log file.
	 *
	 * @param string $log_file Path to JSON log file.
	 * @return string Formatted log content.
	 */
	private function get_json_logs_content( $log_file ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( ! $wp_filesystem || ! $wp_filesystem->exists( $log_file ) ) {
			return __( 'No master log file found.', '365i-queue-optimizer' );
		}

		$content = $wp_filesystem->get_contents( $log_file );
		if ( empty( $content ) ) {
			return __( 'Master log file is empty.', '365i-queue-optimizer' );
		}

		$lines = explode( "\n", $content );
		$formatted_lines = array();
		$line_count = 0;
		$max_lines = 200; // Show last 200 events

		// Process lines in reverse order to show most recent first
		$lines = array_reverse( $lines );

		foreach ( $lines as $line ) {
			if ( empty( trim( $line ) ) || $line_count >= $max_lines ) {
				continue;
			}

			$log_entry = json_decode( $line, true );
			if ( ! $log_entry ) {
				// Handle malformed JSON
				$formatted_lines[] = 'MALFORMED: ' . $line;
				$line_count++;
				continue;
			}

			// Format the JSON entry for human reading
			$time = isset( $log_entry['time'] ) ? $log_entry['time'] : 'unknown';
			$event = isset( $log_entry['event'] ) ? $log_entry['event'] : 'unknown';
			
			$formatted_line = "[$time] $event";
			
			// Add relevant details based on event type
			switch ( $event ) {
				case 'run_start':
					$formatted_line .= sprintf( ' (Run ID: %s, Queue Size: %d)',
						$log_entry['run_id'] ?? 'unknown',
						$log_entry['queue_size'] ?? 0
					);
					break;
				case 'run_end':
					$formatted_line .= sprintf( ' (Run ID: %s, Duration: %.3fs, Peak Memory: %dKB)',
						$log_entry['run_id'] ?? 'unknown',
						$log_entry['duration_s'] ?? 0,
						$log_entry['peak_memory_kb'] ?? 0
					);
					break;
				case 'before_execute':
					$formatted_line .= sprintf( ' (Action ID: %d, Hook: %s)',
						$log_entry['action_id'] ?? 0,
						$log_entry['hook'] ?? 'unknown'
					);
					break;
				case 'after_execute':
					$formatted_line .= sprintf( ' (Action ID: %d, Duration: %dms, Memory: %dKB)',
						$log_entry['action_id'] ?? 0,
						$log_entry['duration_ms'] ?? 0,
						$log_entry['memory_delta_kb'] ?? 0
					);
					break;
				case 'failed_execution':
					$formatted_line .= sprintf( ' (Action ID: %d, Hook: %s, Error: %s)',
						$log_entry['action_id'] ?? 0,
						$log_entry['hook'] ?? 'unknown',
						$log_entry['error'] ?? 'unknown'
					);
					break;
				case 'scheduled_action':
					$formatted_line .= sprintf( ' (Action ID: %d, Hook: %s, Next Run: %s)',
						$log_entry['action_id'] ?? 0,
						$log_entry['hook'] ?? 'unknown',
						$log_entry['next_run'] ?? 'unknown'
					);
					break;
			}

			$formatted_lines[] = $formatted_line;
			$line_count++;
		}

		if ( empty( $formatted_lines ) ) {
			return __( 'No log entries found.', '365i-queue-optimizer' );
		}

		return "=== Action Scheduler Master Log (Last $line_count events) ===\n\n" . implode( "\n", $formatted_lines );
	}

	/**
	 * Clean up old log files based on retention period.
	 */
	private function cleanup_old_logs() {
		$logging_enabled = (bool) get_option( 'queue_optimizer_logging_enabled', false );
		
		if ( ! $logging_enabled ) {
			return;
		}

		$retention_days = (int) get_option( 'queue_optimizer_log_retention_days', 7 );
		$logs_dir = QUEUE_OPTIMIZER_PLUGIN_DIR . 'logs';
		
		if ( ! file_exists( $logs_dir ) ) {
			return;
		}

		$log_files = glob( $logs_dir . '/*.log' );
		
		if ( empty( $log_files ) ) {
			return;
		}

		$cutoff_time = time() - ( $retention_days * DAY_IN_SECONDS );
		$files_deleted = 0;

		foreach ( $log_files as $log_file ) {
			if ( filemtime( $log_file ) < $cutoff_time ) {
				if ( unlink( $log_file ) ) {
					$files_deleted++;
				}
			}
		}

		if ( $files_deleted > 0 && $logging_enabled ) {
			$this->log( sprintf(
				/* translators: %1$d: number of files deleted, %2$d: retention days */
				__( 'Automatic cleanup: Deleted %1$d old log files (retention period: %2$d days).', '365i-queue-optimizer' ),
				$files_deleted,
				$retention_days
			) );
		}
	}

	/**
	 * Clear log files.
	 *
	 * @return bool True if logs were cleared, false if no logs found.
	 */
	private function clear_logs() {
		$logs_cleared = false;
		
		// Clear new JSON-lines log file in uploads directory
		$upload_dir = wp_upload_dir();
		$json_log_file = $upload_dir['basedir'] . '/365i-queue-optimizer.log';
		
		if ( file_exists( $json_log_file ) ) {
			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}
			
			if ( $wp_filesystem && $wp_filesystem->exists( $json_log_file ) ) {
				$wp_filesystem->delete( $json_log_file );
				$logs_cleared = true;
			} else {
				// Fallback
				if ( unlink( $json_log_file ) ) {
					$logs_cleared = true;
				}
			}
		}
		
		// Clear old format log files in plugin directory
		$logs_dir = QUEUE_OPTIMIZER_PLUGIN_DIR . 'logs';
		
		if ( file_exists( $logs_dir ) ) {
			$log_files = glob( $logs_dir . '/*.log' );
			
			if ( ! empty( $log_files ) ) {
				foreach ( $log_files as $log_file ) {
					unlink( $log_file );
					$logs_cleared = true;
				}
			}
		}

		return $logs_cleared;
	}

	/**
	 * Log when a queue processing run starts.
	 */
	public function log_run_start() {
		$this->current_run_id = uniqid( 'run_', true );
		$this->run_start_time = microtime( true );
		$this->run_start_memory = memory_get_usage( true );

		// Get current queue size
		$queue_size = 0;
		if ( class_exists( 'ActionScheduler_Store' ) ) {
			$store = ActionScheduler_Store::instance();
			$pending_actions = $store->query_actions( array(
				'status' => ActionScheduler_Store::STATUS_PENDING,
				'per_page' => 0,
			) );
			$queue_size = is_array( $pending_actions ) ? count( $pending_actions ) : 0;
		}

		$this->log_json_event( 'run_start', array(
			'run_id' => $this->current_run_id,
			'queue_size' => $queue_size,
		) );
	}

	/**
	 * Log when a queue processing run ends.
	 */
	public function log_run_end() {
		if ( ! $this->current_run_id || ! $this->run_start_time ) {
			return;
		}

		$duration_s = microtime( true ) - $this->run_start_time;
		$peak_memory_kb = round( ( memory_get_peak_usage( true ) - $this->run_start_memory ) / 1024 );

		$this->log_json_event( 'run_end', array(
			'run_id' => $this->current_run_id,
			'duration_s' => round( $duration_s, 3 ),
			'peak_memory_kb' => $peak_memory_kb,
		) );

		// Reset tracking variables
		$this->current_run_id = null;
		$this->run_start_time = null;
		$this->run_start_memory = null;
		$this->action_start_times = array();
		$this->action_start_memory = array();
	}

	/**
	 * Log when an action is scheduled.
	 *
	 * @param int $action_id Action ID.
	 */
	public function log_action_scheduled( $action_id ) {
		if ( ! class_exists( 'ActionScheduler_Store' ) ) {
			return;
		}

		$store = ActionScheduler_Store::instance();
		$action = $store->fetch_action( $action_id );
		
		if ( $action ) {
			$schedule = $action->get_schedule();
			$next_run = null;
			
			if ( $schedule && method_exists( $schedule, 'get_date' ) ) {
				$next_run_date = $schedule->get_date();
				if ( $next_run_date ) {
					$next_run = $next_run_date->format( 'c' );
				}
			}

			$this->log_json_event( 'scheduled_action', array(
				'action_id' => $action_id,
				'hook' => $action->get_hook(),
				'next_run' => $next_run,
				'args' => $action->get_args(),
			) );
		}
	}

	/**
	 * Log when an action starts executing.
	 *
	 * @param int                     $action_id Action ID.
	 * @param ActionScheduler_Action $action    Action object.
	 */
	public function log_action_started( $action_id, $action ) {
		// Track start time and memory for duration calculation
		$this->action_start_times[ $action_id ] = microtime( true );
		$this->action_start_memory[ $action_id ] = memory_get_usage( true );

		$this->log_json_event( 'before_execute', array(
			'action_id' => $action_id,
			'hook' => $action->get_hook(),
			'args' => $action->get_args(),
		) );
	}

	/**
	 * Log when an action completes successfully.
	 *
	 * @param int                     $action_id Action ID.
	 * @param ActionScheduler_Action $action    Action object.
	 * @param mixed                  $return    Return value from action.
	 */
	public function log_action_completed( $action_id, $action, $return ) {
		$duration_ms = 0;
		$memory_delta_kb = 0;

		// Calculate duration and memory delta
		if ( isset( $this->action_start_times[ $action_id ] ) ) {
			$duration_ms = round( ( microtime( true ) - $this->action_start_times[ $action_id ] ) * 1000 );
			unset( $this->action_start_times[ $action_id ] );
		}

		if ( isset( $this->action_start_memory[ $action_id ] ) ) {
			$memory_delta_kb = round( ( memory_get_usage( true ) - $this->action_start_memory[ $action_id ] ) / 1024 );
			unset( $this->action_start_memory[ $action_id ] );
		}

		$this->log_json_event( 'after_execute', array(
			'action_id' => $action_id,
			'duration_ms' => $duration_ms,
			'memory_delta_kb' => $memory_delta_kb,
			'status' => 'success',
		) );
	}

	/**
	 * Log when an action fails.
	 *
	 * @param int                     $action_id Action ID.
	 * @param ActionScheduler_Action $action    Action object.
	 * @param Exception              $exception Exception that caused failure.
	 */
	public function log_action_failed( $action_id, $action, $exception ) {
		// Clean up tracking arrays
		unset( $this->action_start_times[ $action_id ] );
		unset( $this->action_start_memory[ $action_id ] );

		$this->log_json_event( 'failed_execution', array(
			'action_id' => $action_id,
			'hook' => $action->get_hook(),
			'error' => $exception->getMessage(),
			'error_class' => get_class( $exception ),
			'args' => $action->get_args(),
		) );
	}

	/**
	 * Log when an action is canceled.
	 *
	 * @param int                     $action_id Action ID.
	 * @param ActionScheduler_Action $action    Action object.
	 */
	public function log_action_canceled( $action_id, $action ) {
		$this->log_json_event( 'canceled_action', array(
			'action_id' => $action_id,
			'hook' => $action->get_hook(),
			'args' => $action->get_args(),
		) );
	}

	/**
	 * Schedule daily cleanup cron job.
	 */
	public function schedule_daily_cleanup() {
		if ( ! wp_next_scheduled( 'queue_optimizer_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'queue_optimizer_daily_cleanup' );
		}
		
		// Hook the cleanup function
		add_action( 'queue_optimizer_daily_cleanup', array( $this, 'cleanup_old_log_entries' ) );
	}

	/**
	 * Clean up old log entries based on retention period.
	 */
	public function cleanup_old_log_entries() {
		$logging_enabled = (bool) get_option( 'queue_optimizer_logging_enabled', false );
		
		if ( ! $logging_enabled ) {
			return;
		}

		$retention_days = (int) get_option( 'queue_optimizer_log_retention_days', 7 );
		$cutoff_time = current_time( 'timestamp' ) - ( $retention_days * DAY_IN_SECONDS );

		// Get uploads directory
		$upload_dir = wp_upload_dir();
		$log_file = $upload_dir['basedir'] . '/365i-queue-optimizer.log';

		if ( ! file_exists( $log_file ) ) {
			return;
		}

		// Use WP_Filesystem
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( ! $wp_filesystem || ! $wp_filesystem->exists( $log_file ) ) {
			return;
		}

		$content = $wp_filesystem->get_contents( $log_file );
		if ( empty( $content ) ) {
			return;
		}

		$lines = explode( "\n", $content );
		$filtered_lines = array();
		$removed_count = 0;

		foreach ( $lines as $line ) {
			if ( empty( trim( $line ) ) ) {
				continue;
			}

			$log_entry = json_decode( $line, true );
			if ( ! $log_entry || ! isset( $log_entry['time'] ) ) {
				// Keep malformed entries
				$filtered_lines[] = $line;
				continue;
			}

			$log_timestamp = strtotime( $log_entry['time'] );
			if ( $log_timestamp >= $cutoff_time ) {
				$filtered_lines[] = $line;
			} else {
				$removed_count++;
			}
		}

		// Write back filtered content
		if ( $removed_count > 0 ) {
			$new_content = implode( "\n", $filtered_lines );
			if ( ! empty( $new_content ) ) {
				$new_content .= "\n";
			}
			
			$wp_filesystem->put_contents( $log_file, $new_content );

			// Log the cleanup operation
			$this->log_json_event( 'log_cleanup', array(
				'removed_entries' => $removed_count,
				'retention_days' => $retention_days,
			) );
		}
	}

	/**
	 * Clear completed and failed Action Scheduler logs.
	 *
	 * @return int Number of actions cleared.
	 */
	private function clear_action_scheduler_logs() {
		if ( ! class_exists( 'ActionScheduler_Store' ) ) {
			return 0;
		}

		$store = ActionScheduler_Store::instance();
		$cleared_count = 0;

		// Get completed actions
		$completed_actions = $store->query_actions( array(
			'status' => ActionScheduler_Store::STATUS_COMPLETE,
			'per_page' => -1,
		) );

		// Get failed actions
		$failed_actions = $store->query_actions( array(
			'status' => ActionScheduler_Store::STATUS_FAILED,
			'per_page' => -1,
		) );

		// Get canceled actions
		$canceled_actions = $store->query_actions( array(
			'status' => ActionScheduler_Store::STATUS_CANCELED,
			'per_page' => -1,
		) );

		// Combine all actions to clear
		$actions_to_clear = array_merge(
			is_array( $completed_actions ) ? $completed_actions : array(),
			is_array( $failed_actions ) ? $failed_actions : array(),
			is_array( $canceled_actions ) ? $canceled_actions : array()
		);

		// Delete each action
		foreach ( $actions_to_clear as $action_id ) {
			try {
				$store->delete_action( $action_id );
				$cleared_count++;
			} catch ( Exception $e ) {
				// Log error but continue processing
				$this->log( sprintf(
					/* translators: %1$d: action ID, %2$s: error message */
					__( 'Failed to delete Action Scheduler entry %1$d: %2$s', '365i-queue-optimizer' ),
					$action_id,
					$e->getMessage()
				) );
			}
		}

		if ( $cleared_count > 0 ) {
			$this->log( sprintf(
				/* translators: %d: number of cleared actions */
				__( 'Cleared %d completed/failed/canceled Action Scheduler entries.', '365i-queue-optimizer' ),
				$cleared_count
			) );
		}

		return $cleared_count;
	}
}