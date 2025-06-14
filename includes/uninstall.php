<?php
/**
 * Queue Optimizer Uninstall Script
 *
 * Handles cleanup when the plugin is uninstalled.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin data on uninstall.
 */
function queue_optimizer_uninstall_cleanup() {
	// Clear scheduled events.
	wp_clear_scheduled_hook( 'queue_optimizer_process_queue' );

	// Remove plugin options.
	$options_to_delete = array(
		'queue_optimizer_time_limit',
		'queue_optimizer_concurrent_batches',
		'queue_optimizer_logging_enabled',
		'queue_optimizer_log_retention_days',
		'365i_qo_image_engine',
		'queue_optimizer_pending_count',
		'queue_optimizer_processing_count',
		'queue_optimizer_completed_count',
		'queue_optimizer_last_run',
	);

	foreach ( $options_to_delete as $option ) {
		delete_option( $option );
	}

	// Remove any custom tables if they exist.
	global $wpdb;
	
	// Example: If we had a custom queue table, we would drop it here.
	// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}queue_optimizer_jobs" );

	// Clean up log files.
	queue_optimizer_cleanup_logs();

	// Clear any cached data.
	wp_cache_flush();

	// Remove any custom capabilities if they were added.
	// Note: We're using 'manage_options' which is a core capability, so no cleanup needed.

	// Clear rewrite rules.
	flush_rewrite_rules();
}

/**
 * Clean up log files.
 */
function queue_optimizer_cleanup_logs() {
	$plugin_dir = dirname( dirname( __FILE__ ) );
	$logs_dir = $plugin_dir . '/logs';

	if ( ! is_dir( $logs_dir ) ) {
		return;
	}

	// Get all log files.
	$log_files = glob( $logs_dir . '/*.log' );

	if ( empty( $log_files ) ) {
		return;
	}

	// Delete all log files.
	foreach ( $log_files as $log_file ) {
		if ( is_file( $log_file ) ) {
			unlink( $log_file );
		}
	}

	// Remove the logs directory if it's empty.
	if ( is_dir( $logs_dir ) ) {
		$remaining_files = scandir( $logs_dir );
		$remaining_files = array_diff( $remaining_files, array( '.', '..' ) );
		
		if ( empty( $remaining_files ) ) {
			rmdir( $logs_dir );
		}
	}
}

/**
 * Remove user meta data related to the plugin.
 */
function queue_optimizer_cleanup_user_meta() {
	global $wpdb;

	// Remove any user meta keys that start with our plugin prefix.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
			'queue_optimizer_%'
		)
	);
}

/**
 * Remove any transients created by the plugin.
 */
function queue_optimizer_cleanup_transients() {
	global $wpdb;

	// Remove any transients that start with our plugin prefix.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			'_transient_queue_optimizer_%',
			'_transient_timeout_queue_optimizer_%'
		)
	);
}

/**
 * Log uninstall event (if logging is enabled).
 */
function queue_optimizer_log_uninstall() {
	$logging_enabled = get_option( 'queue_optimizer_logging_enabled', false );
	
	if ( ! $logging_enabled ) {
		return;
	}

	$plugin_dir = dirname( dirname( __FILE__ ) );
	$logs_dir = $plugin_dir . '/logs';
	
	if ( ! is_dir( $logs_dir ) ) {
		return;
	}

	$log_file = $logs_dir . '/queue-optimizer-' . date( 'Y-m-d' ) . '.log';
	$timestamp = date( 'Y-m-d H:i:s' );
	$log_entry = "[{$timestamp}] Plugin uninstalled and all data cleaned up." . PHP_EOL;

	// Write final log entry.
	file_put_contents( $log_file, $log_entry, FILE_APPEND | LOCK_EX );
}

// Execute cleanup functions.
queue_optimizer_log_uninstall();
queue_optimizer_cleanup_user_meta();
queue_optimizer_cleanup_transients();
queue_optimizer_uninstall_cleanup();