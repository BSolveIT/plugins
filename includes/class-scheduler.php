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

		// Only add logging hooks if logging is enabled - lightweight by default
		$logging_enabled = (bool) get_option( 'queue_optimizer_logging_enabled', false );
		if ( $logging_enabled ) {
			$this->init_logging_hooks();
		}
		
		// Apply our concurrent batches setting to Action Scheduler
		// Only if explicitly enabled to avoid conflicts with other plugins
		$enable_concurrent_batches = (bool) get_option( 'queue_optimizer_enable_concurrency_filter', false );
		if ( $enable_concurrent_batches ) {
			add_filter( 'action_scheduler_queue_runner_concurrent_batches', array( $this, 'set_concurrent_batches' ), 10, 1 );
		}
		
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
	 * Initialize logging hooks - only called if logging is enabled
	 */
	private function init_logging_hooks() {
		// Hook into comprehensive Action Scheduler events for JSON logging.
		add_action( 'action_scheduler_before_process_queue', array( $this, 'log_run_start' ), 999 );
		add_action( 'action_scheduler_after_process_queue', array( $this, 'log_run_end' ), 999 );
		add_action( 'action_scheduler_stored_action', array( $this, 'log_action_scheduled' ), 999 );
		add_action( 'action_scheduler_before_execute', array( $this, 'log_action_started' ), 999, 2 );
		add_action( 'action_scheduler_after_execute', array( $this, 'log_action_completed' ), 999, 3 );
		add_action( 'action_scheduler_failed_execution', array( $this, 'log_action_failed' ), 999, 3 );
		add_action( 'action_scheduler_canceled_action', array( $this, 'log_action_canceled' ), 999, 2 );
	}

	/**
	 * Set the number of concurrent batches for Action Scheduler.
	 * This is now carefully applied with a very low priority to ensure
	 * we don't interfere with other plugins.
	 *
	 * @param int $concurrent_batches Default concurrent batches value.
	 * @return int Modified concurrent batches value.
	 */
	public function set_concurrent_batches( $concurrent_batches ) {
		// Get our custom setting
		$custom_batches = (int) get_option( 'queue_optimizer_concurrent_batches', 3 );
		
		// Ensure it's within valid range and NEVER allow 0
		if ( $custom_batches >= 1 && $custom_batches <= 10 ) {
			return $custom_batches;
		}
		
		// Fall back to default if invalid
		return $concurrent_batches;
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
		
		// Also clear our own log file to ensure UI is updated
		$this->clear_logs();

		// Clear Action Scheduler logs from the database directly
		$this->clear_action_scheduler_database_logs();

		wp_send_json_success( array(
			'message' => $cleared_count > 0 ?
				sprintf(
					/* translators: %d: number of cleared actions */
					__( 'Cleared %d completed and failed Action Scheduler entries.', '365i-queue-optimizer' ),
					$cleared_count
				) :
				__( 'No completed or failed Action Scheduler entries found to clear.', '365i-queue-optimizer' ),
			'reload' => true, // Tell the JS to reload the page
		) );
	}

	/**
	 * Clear Action Scheduler logs from the database directly.
	 * This now explicitly only clears completed/failed/canceled actions,
	 * being careful not to affect pending or currently running actions.
	 */
	private function clear_action_scheduler_database_logs() {
		global $wpdb;
		
		// Check if Action Scheduler tables exist
		$logs_table = $wpdb->prefix . 'actionscheduler_logs';
		$actions_table = $wpdb->prefix . 'actionscheduler_actions';
		
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$logs_table'" ) !== $logs_table ) {
			return;
		}
		
		// Delete log entries for completed, failed, and canceled actions
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$actions_table'" ) === $actions_table ) {
			$statuses = array(
				'complete',
				'failed',
				'canceled',
			);
			
			$status_placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
			
			// Find action IDs with the specified statuses
			$action_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT action_id FROM $actions_table WHERE status IN ($status_placeholders)",
					$statuses
				)
			);
			
			if ( ! empty( $action_ids ) ) {
				// Delete log entries for these actions
				$action_id_placeholders = implode( ',', array_fill( 0, count( $action_ids ), '%d' ) );
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $logs_table WHERE action_id IN ($action_id_placeholders)",
						$action_ids
					)
				);
				
				// Also delete the actions themselves
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $actions_table WHERE action_id IN ($action_id_placeholders)",
						$action_ids
					)
				);
			}
		}
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
		$debug_enabled = (bool) get_option( 'queue_optimizer_debug_mode', false );
		
		if ( ! $logging_enabled && ! $debug_enabled ) {
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

		// Check if debug mode is enabled
		$debug_enabled = (bool) get_option( 'queue_optimizer_debug_mode', false );
		
		// If debug mode is enabled, get the debug logs instead of action scheduler logs
		if ( $debug_enabled && class_exists( 'Queue_Optimizer_Debug_Manager' ) ) {
			// Get the debug logs from Debug_Manager
			$debug_manager = Queue_Optimizer_Debug_Manager::get_instance();
			$logs = $debug_manager->get_recent_logs( 100 ); // Get the last 100 log entries
			
			$logs_content = $this->format_debug_logs( $logs );
		} else {
			// Get the regular logs (action scheduler logs)
			$logs_content = $this->get_logs_content();
		}

		wp_send_json_success( array(
			'logs' => $logs_content,
		) );
	}
	
	/**
	 * Format debug logs for display.
	 * 
	 * @param array $logs Array of log entries from Debug_Manager.
	 * @return string Formatted log content.
	 */
	private function format_debug_logs( $logs ) {
		if ( empty( $logs ) ) {
			return __( 'No debug logs found.', '365i-queue-optimizer' );
		}
		
		$formatted_logs = "=== Debug Log (Most Recent Entries) ===\n\n";
		
		foreach ( $logs as $log ) {
			$formatted_entry = sprintf(
				"[%s] %s: %s\n",
				$log['timestamp'] ?? 'Unknown Time',
				$log['level'] ?? 'INFO',
				$log['message'] ?? 'No message'
			);
			
			// Add context information if available
			if ( isset( $log['context'] ) && ! empty( $log['context'] ) ) {
				$formatted_entry .= "  Context: " . $this->format_log_context( $log['context'] ) . "\n";
			}
			
			// Add memory usage info if available
			if ( isset( $log['memory_usage'] ) ) {
				$formatted_entry .= "  Memory: " . $log['memory_usage'];
				
				if ( isset( $log['peak_memory'] ) ) {
					$formatted_entry .= " (Peak: " . $log['peak_memory'] . ")";
				}
				
				$formatted_entry .= "\n";
			}
			
			$formatted_entry .= "\n";
			$formatted_logs .= $formatted_entry;
		}
		
		return $formatted_logs;
	}
	
	/**
	 * Format log context for display.
	 * 
	 * @param array $context Context data from log entry.
	 * @return string Formatted context string.
	 */
	private function format_log_context( $context ) {
		if ( ! is_array( $context ) ) {
			return print_r( $context, true );
		}
		
		$context_items = array();
		
		foreach ( $context as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( count( $value ) > 0 ) {
					// For database status and similar, show in simplified format
					if ( $key === 'database_status' || $key === 'plugin_settings' ) {
						$items = array();
						foreach ( $value as $subkey => $subvalue ) {
							$items[] = "$subkey: $subvalue";
						}
						$context_items[] = "$key: {" . implode( ', ', $items ) . "}";
					} else {
						$context_items[] = "$key: [Array with " . count( $value ) . " items]";
					}
				} else {
					$context_items[] = "$key: []";
				}
			} elseif ( is_object( $value ) ) {
				$context_items[] = "$key: " . get_class( $value ) . " object";
			} elseif ( is_resource( $value ) ) {
				$context_items[] = "$key: Resource";
			} elseif ( is_bool( $value ) ) {
				$context_items[] = "$key: " . ( $value ? 'true' : 'false' );
			} elseif ( is_null( $value ) ) {
				$context_items[] = "$key: null";
			} else {
				$context_items[] = "$key: $value";
			}
		}
		
		return implode( ', ', $context_items );
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
		$shown_runs = array(); // Track already shown run_start/run_end pairs
		$action_data = array(); // Track action data to enhance logging
		$action_hooks = array(); // Cache for action hook names

		// Process lines in reverse order to show most recent first
		$lines = array_reverse( $lines );

		// First pass - collect action data
		foreach ( $lines as $line ) {
			if ( empty( trim( $line ) ) ) {
				continue;
			}

			$log_entry = json_decode( $line, true );
			if ( ! $log_entry ) {
				continue;
			}

			// Collect action data for better context
			if ( isset( $log_entry['action_id'] ) && isset( $log_entry['hook'] ) ) {
				$action_id = $log_entry['action_id'];
				if ( !isset( $action_data[$action_id] ) ) {
					$action_data[$action_id] = array(
						'hook' => $log_entry['hook'],
						'args' => isset( $log_entry['args'] ) ? $log_entry['args'] : array(),
					);
				}
				
				// Store hook names for easy lookup
				if (!isset($action_hooks[$action_id])) {
					$action_hooks[$action_id] = $log_entry['hook'];
				}
			}
		}

		// Second pass - format the logs
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

			// Skip duplicate run_start/run_end logs
			if ( ( $log_entry['event'] === 'run_start' || $log_entry['event'] === 'run_end' ) 
				&& isset( $log_entry['run_id'] ) ) {
				
				if ( isset( $shown_runs[$log_entry['run_id']] ) ) {
					continue;
				}
				
				if ( $log_entry['event'] === 'run_start' ) {
					$shown_runs[$log_entry['run_id']] = true;
				}
			}

			// Format the JSON entry for human reading
			$time = isset( $log_entry['time'] ) ? $log_entry['time'] : 'unknown';
			$event = isset( $log_entry['event'] ) ? $log_entry['event'] : 'unknown';
			
			// Convert timestamp to human-readable format
			$time = date( 'Y-m-d H:i:s', strtotime( $time ) );
			
			// Add relevant details based on event type
			switch ( $event ) {
				case 'run_start':
					$formatted_line = sprintf( '[%s] %s: Queue processing started with %d pending actions',
						$time,
						'QUEUE PROCESS START',
						$log_entry['queue_size'] ?? 0
					);
					break;
					
				case 'run_end':
					$formatted_line = sprintf( '[%s] %s: Queue processing completed in %.2f seconds (Memory: %s)',
						$time,
						'QUEUE PROCESS END',
						$log_entry['duration_s'] ?? 0,
						isset($log_entry['peak_memory_kb']) ? $this->format_memory_kb($log_entry['peak_memory_kb']) : '0 KB'
					);
					break;
					
				case 'before_execute':
					$action_id = $log_entry['action_id'] ?? 0;
					$hook = $log_entry['hook'] ?? $action_hooks[$action_id] ?? 'unknown';
					
					// Create a human-readable description of the action
					$action_description = $this->get_action_description($hook, $log_entry['args'] ?? array());
					
					$formatted_line = sprintf( '[%s] %s: Starting action "%s" (ID: %d)',
						$time,
						'ACTION START',
						$action_description,
						$action_id
					);
					break;
					
				case 'after_execute':
					$action_id = $log_entry['action_id'] ?? 0;
					$hook = '';
					
					if (isset($action_hooks[$action_id])) {
						$hook = $action_hooks[$action_id];
					}
					
					// Create a human-readable description
					$action_description = $this->get_action_description($hook, $action_data[$action_id]['args'] ?? array());
					
					$formatted_line = sprintf( '[%s] %s: Completed action "%s" (ID: %d) in %d ms',
						$time,
						'ACTION COMPLETE',
						$action_description,
						$action_id,
						$log_entry['duration_ms'] ?? 0
					);
					break;
					
				case 'failed_execution':
					$action_id = $log_entry['action_id'] ?? 0;
					$hook = $log_entry['hook'] ?? $action_hooks[$action_id] ?? 'unknown';
					
					// Create a human-readable description
					$action_description = $this->get_action_description($hook, $log_entry['args'] ?? array());
					
					$formatted_line = sprintf( '[%s] %s: Failed action "%s" (ID: %d) - Error: %s',
						$time,
						'ACTION FAILED',
						$action_description,
						$action_id,
						$log_entry['error'] ?? 'unknown error'
					);
					break;
					
				case 'scheduled_action':
					$action_id = $log_entry['action_id'] ?? 0;
					$hook = $log_entry['hook'] ?? 'unknown';
					$next_run = isset($log_entry['next_run']) ? date('Y-m-d H:i:s', strtotime($log_entry['next_run'])) : 'unknown';
					
					// Create a human-readable description
					$action_description = $this->get_action_description($hook, $log_entry['args'] ?? array());
					
					$formatted_line = sprintf( '[%s] %s: Scheduled new action "%s" (ID: %d) to run at %s',
						$time,
						'ACTION SCHEDULED',
						$action_description,
						$action_id,
						$next_run
					);
					break;
					
				case 'canceled_action':
					$action_id = $log_entry['action_id'] ?? 0;
					$hook = $log_entry['hook'] ?? $action_hooks[$action_id] ?? 'unknown';
					
					// Create a human-readable description
					$action_description = $this->get_action_description($hook, $action_data[$action_id]['args'] ?? array());
					
					$formatted_line = sprintf( '[%s] %s: Canceled action "%s" (ID: %d)',
						$time,
						'ACTION CANCELED',
						$action_description,
						$action_id
					);
					break;
					
				case 'legacy_message':
					$formatted_line = sprintf( '[%s] %s: %s',
						$time,
						'SYSTEM MESSAGE',
						$log_entry['message'] ?? 'No message'
					);
					break;
					
				case 'maintenance_start':
					$formatted_line = sprintf( '[%s] %s: Queue maintenance started (Pending: %d, Processing: %d, Completed: %d, Failed: %d)',
						$time,
						'MAINTENANCE START',
						$log_entry['pending'] ?? 0,
						$log_entry['processing'] ?? 0,
						$log_entry['completed'] ?? 0,
						$log_entry['failed'] ?? 0
					);
					break;
					
				case 'maintenance_end':
					$formatted_line = sprintf( '[%s] %s: Queue maintenance completed',
						$time,
						'MAINTENANCE END'
					);
					break;
					
				default:
					// For any other event types
					$formatted_line = sprintf( '[%s] %s: %s',
						$time,
						strtoupper(str_replace('_', ' ', $event)),
						$this->summarize_event_data($log_entry)
					);
					break;
			}

			$formatted_lines[] = $formatted_line;
			$line_count++;
		}

		if ( empty( $formatted_lines ) ) {
			return __( 'No log entries found.', '365i-queue-optimizer' );
		}

		return "=== Action Scheduler Log (Last $line_count events) ===\n\n" . 
			__( 'To clear these logs, click the "Clear Action Scheduler Logs" button below. This will remove both completed and failed actions.', '365i-queue-optimizer' ) . 
			"\n\n" . implode( "\n", $formatted_lines );
	}

	/**
	 * Get a human-readable description of an action based on its hook and arguments.
	 *
	 * @param string $hook The action hook name.
	 * @param array  $args The action arguments.
	 * @return string Human-readable description.
	 */
	private function get_action_description($hook, $args) {
		// Custom descriptions for common Action Scheduler hooks
		switch ($hook) {
			case 'action_scheduler/migration_hook':
				return 'Action Scheduler Database Migration';
				
			case 'action_scheduler/custom_cleanup':
				return 'Action Scheduler Cleanup';
				
			case 'action_scheduler/migration_status':
				return 'Action Scheduler Migration Status Check';
				
			case 'woocommerce_cleanup_sessions':
				return 'WooCommerce Session Cleanup';
				
			case 'woocommerce_cleanup_orders':
				return 'WooCommerce Order Cleanup';
				
			case 'woocommerce_run_product_attribute_lookup_update_callback':
				return 'WooCommerce Product Attribute Update';
				
			case 'woocommerce_scheduled_sales':
				return 'WooCommerce Scheduled Sales';
				
			case 'elementor/images_manager/clear_cache':
				return 'Elementor Clear Images Cache';
				
			case 'elementor_pro/images/optimize':
				return 'Elementor Image Optimization';
		}
		
		// Custom descriptions for Queue Optimizer hooks
		if (strpos($hook, 'queue_optimizer_') === 0) {
			$action_name = str_replace('queue_optimizer_', '', $hook);
			$action_name = ucwords(str_replace('_', ' ', $action_name));
			return '365i Queue Optimizer: ' . $action_name;
		}
		
		// Elementor hooks
		if (strpos($hook, 'elementor') === 0) {
			return 'Elementor: ' . $hook;
		}
		
		// If we can't determine a specific description, return the hook name
		// with the first argument if available
		if (!empty($args) && isset($args[0])) {
			if (is_scalar($args[0])) {
				return "$hook: " . substr((string)$args[0], 0, 30);
			} elseif (is_array($args[0])) {
				return "$hook with " . count($args[0]) . " items";
			}
		}
		
		return $hook;
	}
	
	/**
	 * Create a summary of event data for display in logs.
	 *
	 * @param array $event_data The event data array.
	 * @return string A human-readable summary.
	 */
	private function summarize_event_data($event_data) {
		$summary_parts = array();
		
		// Remove common fields that don't need to be in the summary
		$ignore_keys = array('time', 'event');
		
		foreach ($event_data as $key => $value) {
			if (in_array($key, $ignore_keys)) {
				continue;
			}
			
			if (is_scalar($value)) {
				$summary_parts[] = "$key: $value";
			} elseif (is_array($value)) {
				$summary_parts[] = "$key: [" . count($value) . " items]";
			} elseif (is_object($value)) {
				$summary_parts[] = "$key: " . get_class($value);
			}
		}
		
		return implode(', ', $summary_parts);
	}
	
	/**
	 * Format memory in kilobytes to a human-readable string.
	 *
	 * @param int $kb Memory in kilobytes.
	 * @return string Formatted memory string.
	 */
	private function format_memory_kb($kb) {
		if ($kb < 1024) {
			return "$kb KB";
		} elseif ($kb < 1024 * 1024) {
			return round($kb / 1024, 2) . " MB";
		} else {
			return round($kb / (1024 * 1024), 2) . " GB";
		}
	}

	/**
	 * Format arguments for readable display in logs.
	 *
	 * @param array $args Arguments array.
	 * @return string Formatted arguments string.
	 */
	private function format_args( $args ) {
		if ( ! is_array( $args ) ) {
			return 'none';
		}
		
		$formatted = array();
		foreach ( $args as $key => $value ) {
			if ( is_array( $value ) ) {
				$formatted[] = "$key: [array]";
			} elseif ( is_object( $value ) ) {
				$formatted[] = "$key: " . get_class( $value );
			} elseif ( is_string( $value ) && strlen( $value ) > 30 ) {
				$formatted[] = "$key: " . substr( $value, 0, 30 ) . '...';
			} else {
				$formatted[] = "$key: $value";
			}
		}
		
		return implode( ', ', $formatted );
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
	 * Avoids interfering with pending or running actions.
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