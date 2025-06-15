<?php
/**
 * Debug Manager Class
 *
 * Handles debug mode functionality including verbose logging,
 * performance monitoring, and detailed error reporting.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Debug Manager class.
 */
class Queue_Optimizer_Debug_Manager {

	/**
	 * Single instance of the class.
	 *
	 * @var Queue_Optimizer_Debug_Manager
	 */
	private static $instance = null;

	/**
	 * Debug log file path.
	 *
	 * @var string
	 */
	private $debug_log_file;

	/**
	 * Performance tracking data.
	 *
	 * @var array
	 */
	private $performance_data = array();

	/**
	 * Get single instance of the class.
	 *
	 * @return Queue_Optimizer_Debug_Manager
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
		$this->debug_log_file = plugin_dir_path( __FILE__ ) . '../logs/debug.log';
		$this->init_hooks();
	}

	/**
	 * Initialize debug hooks if debug mode is enabled.
	 */
	private function init_hooks() {
		if ( ! $this->is_debug_enabled() ) {
			return;
		}

		// Action Scheduler debug hooks.
		add_action( 'action_scheduler_before_execute', array( $this, 'log_action_start' ), 10, 2 );
		add_action( 'action_scheduler_after_execute', array( $this, 'log_action_end' ), 10, 3 );
		add_action( 'action_scheduler_failed_execution', array( $this, 'log_action_failure' ), 10, 3 );

		// WordPress cron debug hooks.
		add_action( 'wp_cron_api_init', array( $this, 'log_cron_init' ) );
	}

	/**
	 * Check if debug mode is enabled.
	 *
	 * @return bool True if debug mode is enabled.
	 */
	public function is_debug_enabled() {
		return (bool) get_option( 'queue_optimizer_debug_mode', false );
	}

	/**
	 * Log debug message with timestamp and memory usage.
	 *
	 * @param string $message Debug message.
	 * @param string $level Log level (info, warning, error).
	 * @param array  $context Additional context data.
	 */
	public function log( $message, $level = 'info', $context = array() ) {
		if ( ! $this->is_debug_enabled() ) {
			return;
		}

		$timestamp = current_time( 'Y-m-d H:i:s' );
		$memory_usage = $this->format_bytes( memory_get_usage() );
		$peak_memory = $this->format_bytes( memory_get_peak_usage() );

		$log_entry = array(
			'timestamp' => $timestamp,
			'level' => strtoupper( $level ),
			'message' => $message,
			'memory_usage' => $memory_usage,
			'peak_memory' => $peak_memory,
			'context' => $context,
		);

		$this->write_log_entry( $log_entry );
	}

	/**
	 * Log action scheduler start.
	 *
	 * @param int    $action_id Action ID.
	 * @param object $action Action object.
	 */
	public function log_action_start( $action_id, $action ) {
		$this->performance_data[ $action_id ] = array(
			'start_time' => microtime( true ),
			'start_memory' => memory_get_usage(),
		);

		$this->log(
			sprintf( 'Action Scheduler: Starting action %s (ID: %d)', $action->get_hook(), $action_id ),
			'info',
			array(
				'action_id' => $action_id,
				'hook' => $action->get_hook(),
				'args' => $action->get_args(),
			)
		);
	}

	/**
	 * Log action scheduler end.
	 *
	 * @param int      $action_id Action ID.
	 * @param object   $action Action object.
	 * @param mixed    $result Execution result.
	 */
	public function log_action_end( $action_id, $action, $result ) {
		$performance = $this->calculate_performance( $action_id );

		$this->log(
			sprintf( 
				'Action Scheduler: Completed action %s (ID: %d) in %s seconds, memory delta: %s',
				$action->get_hook(),
				$action_id,
				$performance['duration'],
				$performance['memory_delta']
			),
			'info',
			array(
				'action_id' => $action_id,
				'hook' => $action->get_hook(),
				'result' => $result,
				'performance' => $performance,
			)
		);
	}

	/**
	 * Log action scheduler failure.
	 *
	 * @param int       $action_id Action ID.
	 * @param object    $action Action object.
	 * @param Exception $exception Exception object.
	 */
	public function log_action_failure( $action_id, $action, $exception ) {
		$performance = $this->calculate_performance( $action_id );

		$this->log(
			sprintf( 
				'Action Scheduler: Failed action %s (ID: %d) - %s',
				$action->get_hook(),
				$action_id,
				$exception->getMessage()
			),
			'error',
			array(
				'action_id' => $action_id,
				'hook' => $action->get_hook(),
				'error' => $exception->getMessage(),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'trace' => $exception->getTraceAsString(),
				'performance' => $performance,
			)
		);
	}

	/**
	 * Log WordPress cron initialization.
	 */
	public function log_cron_init() {
		$this->log( 
			'WordPress Cron: Cron system initialized',
			'info',
			array(
				'doing_cron' => defined( 'DOING_CRON' ) && DOING_CRON,
				'cron_lock' => get_transient( 'doing_cron' ),
			)
		);
	}

	/**
	 * Calculate performance metrics for an action.
	 *
	 * @param int $action_id Action ID.
	 * @return array Performance data.
	 */
	private function calculate_performance( $action_id ) {
		if ( ! isset( $this->performance_data[ $action_id ] ) ) {
			return array(
				'duration' => 'unknown',
				'memory_delta' => 'unknown',
			);
		}

		$start_data = $this->performance_data[ $action_id ];
		$end_time = microtime( true );
		$end_memory = memory_get_usage();

		$duration = round( $end_time - $start_data['start_time'], 4 );
		$memory_delta = $end_memory - $start_data['start_memory'];

		// Clean up performance data.
		unset( $this->performance_data[ $action_id ] );

		return array(
			'duration' => $duration,
			'memory_delta' => $this->format_bytes( $memory_delta ),
		);
	}

	/**
	 * Write log entry to debug file.
	 *
	 * @param array $log_entry Log entry data.
	 */
	private function write_log_entry( $log_entry ) {
		// Ensure logs directory exists.
		$logs_dir = dirname( $this->debug_log_file );
		if ( ! file_exists( $logs_dir ) ) {
			wp_mkdir_p( $logs_dir );
		}

		// Format log entry as JSON.
		$json_entry = wp_json_encode( $log_entry ) . "\n";

		// Write to log file.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		file_put_contents( $this->debug_log_file, $json_entry, FILE_APPEND | LOCK_EX );

		// Rotate log if it gets too large (10MB).
		$this->rotate_log_if_needed();
	}

	/**
	 * Rotate debug log if it exceeds size limit.
	 */
	private function rotate_log_if_needed() {
		if ( ! file_exists( $this->debug_log_file ) ) {
			return;
		}

		$max_size = 10 * 1024 * 1024; // 10MB
		if ( filesize( $this->debug_log_file ) > $max_size ) {
			$backup_file = $this->debug_log_file . '.' . date( 'Y-m-d_H-i-s' );
			rename( $this->debug_log_file, $backup_file );
		}
	}

	/**
	 * Format bytes to human readable format.
	 *
	 * @param int $bytes Number of bytes.
	 * @return string Formatted bytes.
	 */
	private function format_bytes( $bytes ) {
		$units = array( 'B', 'KB', 'MB', 'GB' );
		$bytes = max( $bytes, 0 );
		$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow = min( $pow, count( $units ) - 1 );

		$bytes /= pow( 1024, $pow );

		return round( $bytes, 2 ) . ' ' . $units[ $pow ];
	}

	/**
	 * Get recent debug log entries.
	 *
	 * @param int $limit Number of entries to retrieve.
	 * @return array Log entries.
	 */
	public function get_recent_logs( $limit = 100 ) {
		if ( ! file_exists( $this->debug_log_file ) ) {
			return array();
		}

		$lines = file( $this->debug_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( ! $lines ) {
			return array();
		}

		// Get last X lines.
		$recent_lines = array_slice( $lines, -$limit );
		$log_entries = array();

		foreach ( $recent_lines as $line ) {
			$entry = json_decode( $line, true );
			if ( $entry ) {
				$log_entries[] = $entry;
			}
		}

		return array_reverse( $log_entries );
	}

	/**
	 * Clear debug log file.
	 */
	public function clear_debug_log() {
		if ( file_exists( $this->debug_log_file ) ) {
			unlink( $this->debug_log_file );
		}
	}

	/**
	 * Get debug log file size.
	 *
	 * @return string Formatted file size.
	 */
	public function get_log_file_size() {
		if ( ! file_exists( $this->debug_log_file ) ) {
			return '0 B';
		}

		return $this->format_bytes( filesize( $this->debug_log_file ) );
	}
}