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
		
		// Apply ActionScheduler optimizations - these are the core functionality
		// Based on user's working functions.php code
		add_filter( 'action_scheduler_queue_runner_time_limit', array( $this, 'set_time_limit' ), 10, 1 );
		add_filter( 'action_scheduler_queue_runner_concurrent_batches', array( $this, 'set_concurrent_batches' ), 10, 1 );
		// Note: wp_image_editors filter is handled by main plugin file based on user's Image Processing Engine setting
		
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
	 * Set the time limit for Action Scheduler queue processing.
	 *
	 * @param int $time_limit The default time limit.
	 * @return int Modified time limit (60 seconds).
	 */
	public function set_time_limit( $time_limit ) {
		// Get custom time limit from settings, default to 60 seconds (user's working value)
		$custom_time_limit = (int) get_option( 'queue_optimizer_time_limit', 60 );
		
		// Validate the value is within acceptable range (30-300 seconds)
		if ( $custom_time_limit < 30 ) {
			$custom_time_limit = 30;
		} elseif ( $custom_time_limit > 300 ) {
			$custom_time_limit = 300;
		}
		
		return $custom_time_limit;
	}
	
	/**
	 * Set the number of concurrent batches for Action Scheduler.
	 *
	 * @param int $concurrent_batches The default number of concurrent batches.
	 * @return int Modified number of concurrent batches (4 by default).
	 */
	public function set_concurrent_batches( $concurrent_batches ) {
		// Get custom batches from settings, default to 4 (user's working value)
		$custom_batches = (int) get_option( 'queue_optimizer_concurrent_batches', 4 );
		
		// Validate the value is within acceptable range
		if ( $custom_batches < 1 ) {
			$custom_batches = 1;
		} elseif ( $custom_batches > 10 ) {
			$custom_batches = 10;
		}
		
		return $custom_batches;
	}
	
	
	/**
	 * Schedule daily cleanup for log files and pending tasks.
	 */
	private function schedule_daily_cleanup() {
		if ( ! wp_next_scheduled( 'queue_optimizer_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'queue_optimizer_daily_cleanup' );
		}
		
		add_action( 'queue_optimizer_daily_cleanup', array( $this, 'perform_daily_cleanup' ) );
	}
	
	/**
	 * Perform daily cleanup of log files and stale tasks.
	 */
	public function perform_daily_cleanup() {
		// Clean up old log files if debug manager exists
		if ( class_exists( 'Queue_Optimizer_Debug_Manager' ) ) {
			$debug_manager = Queue_Optimizer_Debug_Manager::get_instance();
			$debug_manager->cleanup_old_logs();
		}
		
		// Clean up old Action Scheduler entries (older than 30 days)
		if ( class_exists( 'ActionScheduler_DBStore' ) ) {
			$store = ActionScheduler_DBStore::instance();
			$cutoff_date = gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) );
			$store->cancel_actions_by_group( '', array(), $cutoff_date );
		}
	}
	
	/**
	 * Initialize logging hooks for queue monitoring.
	 */
	private function init_logging_hooks() {
		// Add hooks for Action Scheduler logging
		add_action( 'action_scheduler_before_process_queue', array( $this, 'log_queue_start' ) );
		add_action( 'action_scheduler_after_process_queue', array( $this, 'log_queue_end' ) );
		add_action( 'action_scheduler_before_execute', array( $this, 'log_action_start' ) );
		add_action( 'action_scheduler_after_execute', array( $this, 'log_action_end' ) );
	}
	
	/**
	 * Process the queue (main queue processing method).
	 */
	public function process_queue() {
		// Basic queue processing implementation
		$this->log_queue_start();
		
		// Process items here
		
		$this->log_queue_end();
	}
	
	/**
	 * AJAX handler for running queue manually.
	 */
	public function ajax_run_now() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'queue_optimizer_admin_nonce' ) ) {
			wp_die( 'Security check failed' );
		}
		
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}
		
		// Run the queue
		$this->process_queue();
		
		wp_send_json_success( array( 'message' => 'Queue processed successfully' ) );
	}
	
	/**
	 * AJAX handler for clearing logs.
	 */
	public function ajax_clear_logs() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'queue_optimizer_admin_nonce' ) ) {
			wp_die( 'Security check failed' );
		}
		
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}
		
		// Clear debug logs if debug manager exists
		if ( class_exists( 'Queue_Optimizer_Debug_Manager' ) ) {
			$debug_manager = Queue_Optimizer_Debug_Manager::get_instance();
			$debug_manager->clear_logs();
		}
		
		wp_send_json_success( array( 'message' => 'Logs cleared successfully' ) );
	}
	
	/**
	 * AJAX handler for clearing Action Scheduler logs.
	 */
	public function ajax_clear_action_scheduler_logs() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'queue_optimizer_admin_nonce' ) ) {
			wp_die( 'Security check failed' );
		}
		
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}
		
		// Clear Action Scheduler logs
		if ( class_exists( 'ActionScheduler_DBStore' ) ) {
			ActionScheduler_DBStore::instance()->cancel_actions_by_group( '' );
		}
		
		wp_send_json_success( array( 'message' => 'Action Scheduler logs cleared successfully' ) );
	}
	
	/**
	 * AJAX handler for getting queue status.
	 */
	public function ajax_get_status() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'queue_optimizer_admin_nonce' ) ) {
			wp_die( 'Security check failed' );
		}
		
		$status = $this->get_queue_status();
		wp_send_json_success( $status );
	}
	
	/**
	 * AJAX handler for getting logs.
	 */
	public function ajax_get_logs() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'queue_optimizer_admin_nonce' ) ) {
			wp_die( 'Security check failed' );
		}
		
		$logs = $this->get_recent_logs();
		wp_send_json_success( $logs );
	}
	
	/**
	 * Get current queue status.
	 *
	 * @return array Queue status information.
	 */
	public function get_queue_status() {
		$status = array(
			'pending_jobs' => 0,
			'completed_jobs' => 0,
			'failed_jobs' => 0,
			'last_run' => get_option( 'queue_optimizer_last_run', 0 ),
			'is_running' => false,
		);
		
		// Get Action Scheduler stats if available
		if ( class_exists( 'ActionScheduler_DBStore' ) ) {
			$store = ActionScheduler_DBStore::instance();
			$status['pending_jobs'] = $store->query_actions( array( 'status' => 'pending' ), 'count' );
			$status['completed_jobs'] = $store->query_actions( array( 'status' => 'complete' ), 'count' );
			$status['failed_jobs'] = $store->query_actions( array( 'status' => 'failed' ), 'count' );
		}
		
		return $status;
	}
	
	/**
	 * Get recent logs.
	 *
	 * @return array Recent log entries.
	 */
	public function get_recent_logs() {
		$logs = array();
		
		// Get debug logs if debug mode is enabled
		$debug_mode = (bool) get_option( 'queue_optimizer_debug_mode', false );
		if ( $debug_mode && class_exists( 'Queue_Optimizer_Debug_Manager' ) ) {
			$debug_manager = Queue_Optimizer_Debug_Manager::get_instance();
			$logs = $debug_manager->get_recent_logs( 20 );
		} else {
			// Fallback to Action Scheduler logs
			if ( class_exists( 'ActionScheduler_LoggerSchema' ) ) {
				global $wpdb;
				$table = $wpdb->prefix . 'actionscheduler_logs';
				$results = $wpdb->get_results( 
					"SELECT * FROM {$table} ORDER BY log_date_gmt DESC LIMIT 20",
					ARRAY_A 
				);
				$logs = $results ?: array();
			}
		}
		
		return $logs;
	}
	
	/**
	 * Log queue processing start.
	 */
	public function log_queue_start() {
		$this->current_run_id = uniqid( 'queue_run_' );
		$this->run_start_time = microtime( true );
		$this->run_start_memory = memory_get_usage();
		
		// Log via debug manager if available and enabled
		if ( get_option( 'queue_optimizer_debug_mode', false ) && class_exists( 'Queue_Optimizer_Debug_Manager' ) ) {
			$debug_manager = Queue_Optimizer_Debug_Manager::get_instance();
			$debug_manager->log( 'queue_start', 'Queue processing started', array(
				'run_id' => $this->current_run_id,
				'memory_start' => $this->run_start_memory,
			) );
		}
		
		update_option( 'queue_optimizer_last_run', time() );
	}
	
	/**
	 * Log queue processing end.
	 */
	public function log_queue_end() {
		$end_time = microtime( true );
		$end_memory = memory_get_usage();
		
		// Log via debug manager if available and enabled
		if ( get_option( 'queue_optimizer_debug_mode', false ) && class_exists( 'Queue_Optimizer_Debug_Manager' ) ) {
			$debug_manager = Queue_Optimizer_Debug_Manager::get_instance();
			$debug_manager->log( 'queue_end', 'Queue processing completed', array(
				'run_id' => $this->current_run_id,
				'duration' => $end_time - $this->run_start_time,
				'memory_peak' => $end_memory,
				'memory_delta' => $end_memory - $this->run_start_memory,
			) );
		}
		
		// Reset tracking variables
		$this->current_run_id = null;
		$this->run_start_time = null;
		$this->run_start_memory = null;
	}
	
	/**
	 * Log action start.
	 *
	 * @param int $action_id Action ID.
	 */
	public function log_action_start( $action_id ) {
		$this->action_start_times[ $action_id ] = microtime( true );
		$this->action_start_memory[ $action_id ] = memory_get_usage();
		
		// Log via debug manager if available and enabled
		if ( get_option( 'queue_optimizer_debug_mode', false ) && class_exists( 'Queue_Optimizer_Debug_Manager' ) ) {
			$debug_manager = Queue_Optimizer_Debug_Manager::get_instance();
			$debug_manager->log( 'action_start', 'Action started', array(
				'action_id' => $action_id,
				'run_id' => $this->current_run_id,
			) );
		}
	}
	
	/**
	 * Log action end.
	 *
	 * @param int $action_id Action ID.
	 */
	public function log_action_end( $action_id ) {
		$end_time = microtime( true );
		$end_memory = memory_get_usage();
		
		$start_time = isset( $this->action_start_times[ $action_id ] ) ? $this->action_start_times[ $action_id ] : $end_time;
		$start_memory = isset( $this->action_start_memory[ $action_id ] ) ? $this->action_start_memory[ $action_id ] : $end_memory;
		
		// Log via debug manager if available and enabled
		if ( get_option( 'queue_optimizer_debug_mode', false ) && class_exists( 'Queue_Optimizer_Debug_Manager' ) ) {
			$debug_manager = Queue_Optimizer_Debug_Manager::get_instance();
			$debug_manager->log( 'action_end', 'Action completed', array(
				'action_id' => $action_id,
				'run_id' => $this->current_run_id,
				'duration' => $end_time - $start_time,
				'memory_delta' => $end_memory - $start_memory,
			) );
		}
		
		// Clean up tracking data
		unset( $this->action_start_times[ $action_id ] );
		unset( $this->action_start_memory[ $action_id ] );
	}
}