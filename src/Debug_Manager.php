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
		add_action( 'action_scheduler_stored_action', array( $this, 'log_action_scheduled' ), 10, 1 );
		
		// Additional Action Scheduler hooks for more comprehensive logging
		add_action( 'action_scheduler_canceled_action', array( $this, 'log_action_canceled' ), 10, 1 );
		add_action( 'action_scheduler_begin_execute', array( $this, 'log_queue_processing_start' ), 10, 2 );
		add_action( 'action_scheduler_completed_execution', array( $this, 'log_queue_processing_end' ), 10, 2 );

		// WordPress cron debug hooks.
		add_action( 'wp_cron_api_init', array( $this, 'log_cron_init' ) );
		
		// System status logging
		add_action( 'shutdown', array( $this, 'log_system_status' ) );
		
		// Log detailed debug information at plugin load
		$this->log_plugin_initialization();
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
	 * Log initial plugin information upon debug mode activation.
	 */
	private function log_plugin_initialization() {
		global $wpdb;
		
		// Get WordPress and PHP versions
		$wp_version = get_bloginfo( 'version' );
		$php_version = phpversion();
		
		// Get server information
		$server_info = array(
			'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
			'memory_limit' => ini_get( 'memory_limit' ),
			'max_execution_time' => ini_get( 'max_execution_time' ),
		);
		
		// Get plugin settings
		$settings = array(
			'time_limit' => get_option( 'queue_optimizer_time_limit', 30 ),
			'concurrent_batches' => get_option( 'queue_optimizer_concurrent_batches', 3 ),
			'logging_enabled' => get_option( 'queue_optimizer_logging_enabled', false ) ? 'Yes' : 'No',
			'log_retention_days' => get_option( 'queue_optimizer_log_retention_days', 7 ),
			'image_engine' => get_option( '365i_qo_image_engine', 'imagick' ),
			'debug_mode' => get_option( 'queue_optimizer_debug_mode', false ) ? 'Enabled' : 'Disabled',
		);
		
		// Get Action Scheduler information
		$as_version = defined( 'ACTION_SCHEDULER_VERSION' ) ? ACTION_SCHEDULER_VERSION : 'unknown';
		
		// Count pending actions
		$pending_count = 0;
		$action_table = $wpdb->prefix . 'actionscheduler_actions';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$action_table'" ) === $action_table ) {
			$pending_count = $wpdb->get_var( "SELECT COUNT(*) FROM $action_table WHERE status = 'pending'" );
		}
		
		$this->log(
			'Queue Optimizer Debug Mode Initialized',
			'info',
			array(
				'wordpress_version' => $wp_version,
				'php_version' => $php_version,
				'server_info' => $server_info,
				'plugin_settings' => $settings,
				'action_scheduler_version' => $as_version,
				'pending_actions' => $pending_count,
			)
		);
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
	 * Log action scheduled event.
	 *
	 * @param int $action_id Action ID.
	 */
	public function log_action_scheduled( $action_id ) {
		if ( ! class_exists( 'ActionScheduler_Store' ) ) {
			return;
		}
		
		$store = ActionScheduler_Store::instance();
		$action = $store->fetch_action( $action_id );
		
		if ( ! $action ) {
			return;
		}
		
		// Get schedule information
		$schedule = $action->get_schedule();
		$schedule_info = 'Unknown schedule';
		
		if ( $schedule ) {
			if ( method_exists( $schedule, 'is_recurring' ) && $schedule->is_recurring() ) {
				$schedule_info = 'Recurring';
			} else {
				$schedule_info = 'One-time';
			}
			
			if ( method_exists( $schedule, 'get_date' ) ) {
				$next_run_date = $schedule->get_date();
				if ( $next_run_date ) {
					$schedule_info .= ', Next run: ' . $next_run_date->format( 'Y-m-d H:i:s' );
				}
			}
		}
		
		$this->log(
			sprintf( 'Action Scheduler: Action scheduled - ID: %d, Hook: %s', $action_id, $action->get_hook() ),
			'info',
			array(
				'action_id' => $action_id,
				'hook' => $action->get_hook(),
				'args' => $this->format_action_args( $action->get_args() ),
				'schedule' => $schedule_info,
				'group' => method_exists( $action, 'get_group' ) ? $action->get_group() : 'default',
			)
		);
	}
	
	/**
	 * Log when an action is canceled.
	 *
	 * @param int $action_id Action ID.
	 */
	public function log_action_canceled( $action_id ) {
		if ( ! class_exists( 'ActionScheduler_Store' ) ) {
			return;
		}
		
		$store = ActionScheduler_Store::instance();
		$action = $store->fetch_action( $action_id );
		
		if ( ! $action ) {
			$this->log(
				sprintf( 'Action Scheduler: Action canceled - ID: %d (action details not available)', $action_id ),
				'info',
				array(
					'action_id' => $action_id,
				)
			);
			return;
		}
		
		$this->log(
			sprintf( 'Action Scheduler: Action canceled - ID: %d, Hook: %s', $action_id, $action->get_hook() ),
			'info',
			array(
				'action_id' => $action_id,
				'hook' => $action->get_hook(),
				'args' => $this->format_action_args( $action->get_args() ),
				'group' => method_exists( $action, 'get_group' ) ? $action->get_group() : 'default',
			)
		);
	}
	
	/**
	 * Log the start of queue processing.
	 *
	 * @param int $count Number of actions to process.
	 * @param int $batch_size Batch size.
	 */
	public function log_queue_processing_start( $count, $batch_size ) {
		$this->log(
			sprintf( 'Action Scheduler: Queue processing started - Processing %d actions (batch size: %d)', $count, $batch_size ),
			'info',
			array(
				'action_count' => $count,
				'batch_size' => $batch_size,
				'concurrent_batches' => apply_filters( 'action_scheduler_queue_runner_concurrent_batches', 1 ),
			)
		);
	}
	
	/**
	 * Log the end of queue processing.
	 *
	 * @param int $processed_actions Number of actions processed.
	 * @param int $time_limit Time limit.
	 */
	public function log_queue_processing_end( $processed_actions, $time_limit ) {
		$this->log(
			sprintf( 'Action Scheduler: Queue processing completed - Processed %d actions (time limit: %d seconds)', $processed_actions, $time_limit ),
			'info',
			array(
				'processed_actions' => $processed_actions,
				'time_limit' => $time_limit,
			)
		);
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
				'args' => $this->format_action_args( $action->get_args() ),
				'group' => method_exists( $action, 'get_group' ) ? $action->get_group() : 'default',
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
				'result' => $this->format_result( $result ),
				'performance' => $performance,
				'group' => method_exists( $action, 'get_group' ) ? $action->get_group() : 'default',
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
				'group' => method_exists( $action, 'get_group' ) ? $action->get_group() : 'default',
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
	 * Log system status information.
	 */
	public function log_system_status() {
		// Only log on admin pages to prevent excessive logging
		if ( ! is_admin() || ! $this->is_debug_enabled() ) {
			return;
		}
		
		// Log once per session
		static $logged = false;
		if ( $logged ) {
			return;
		}
		$logged = true;
		
		global $wpdb;
		
		// Get database status
		$actions_table = $wpdb->prefix . 'actionscheduler_actions';
		$logs_table = $wpdb->prefix . 'actionscheduler_logs';
		
		$db_status = array();
		
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$actions_table'" ) === $actions_table ) {
			$db_status['pending_actions'] = $wpdb->get_var( "SELECT COUNT(*) FROM $actions_table WHERE status = 'pending'" );
			$db_status['running_actions'] = $wpdb->get_var( "SELECT COUNT(*) FROM $actions_table WHERE status = 'running'" );
			$db_status['completed_actions'] = $wpdb->get_var( "SELECT COUNT(*) FROM $actions_table WHERE status = 'complete'" );
			$db_status['failed_actions'] = $wpdb->get_var( "SELECT COUNT(*) FROM $actions_table WHERE status = 'failed'" );
			$db_status['canceled_actions'] = $wpdb->get_var( "SELECT COUNT(*) FROM $actions_table WHERE status = 'canceled'" );
		}
		
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$logs_table'" ) === $logs_table ) {
			$db_status['log_entries'] = $wpdb->get_var( "SELECT COUNT(*) FROM $logs_table" );
		}
		
		// Performance information
		$memory_usage = memory_get_usage( true );
		$peak_memory = memory_get_peak_usage( true );
		
		$this->log(
			'System Status Report',
			'info',
			array(
				'database_status' => $db_status,
				'current_memory_usage' => $this->format_bytes( $memory_usage ),
				'peak_memory_usage' => $this->format_bytes( $peak_memory ),
				'memory_limit' => ini_get( 'memory_limit' ),
				'concurrent_batches' => get_option( 'queue_optimizer_concurrent_batches', 3 ),
				'debug_mode' => get_option( 'queue_optimizer_debug_mode', false ) ? 'Enabled' : 'Disabled',
				'time_limit' => get_option( 'queue_optimizer_time_limit', 30 ),
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
	 * Format action arguments for logging.
	 *
	 * @param array $args Action arguments.
	 * @return array|string Formatted arguments.
	 */
	private function format_action_args( $args ) {
		if ( ! is_array( $args ) ) {
			return is_scalar( $args ) ? (string) $args : gettype( $args );
		}

		$formatted = array();
		foreach ( $args as $key => $value ) {
			if ( is_array( $value ) ) {
				$count = count( $value );
				$formatted[ $key ] = "[array($count)]";
			} elseif ( is_object( $value ) ) {
				$formatted[ $key ] = '[object:' . get_class( $value ) . ']';
			} elseif ( is_resource( $value ) ) {
				$formatted[ $key ] = '[resource]';
			} elseif ( is_string( $value ) && strlen( $value ) > 50 ) {
				$formatted[ $key ] = substr( $value, 0, 50 ) . '...';
			} else {
				$formatted[ $key ] = $value;
			}
		}

		return $formatted;
	}

	/**
	 * Format action result for logging.
	 *
	 * @param mixed $result Action result.
	 * @return mixed Formatted result.
	 */
	private function format_result( $result ) {
		if ( is_array( $result ) ) {
			$count = count( $result );
			return "[array($count)]";
		} elseif ( is_object( $result ) ) {
			return '[object:' . get_class( $result ) . ']';
		} elseif ( is_resource( $result ) ) {
			return '[resource]';
		} elseif ( is_string( $result ) && strlen( $result ) > 100 ) {
			return substr( $result, 0, 100 ) . '...';
		}

		return $result;
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