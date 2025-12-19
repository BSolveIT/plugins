 <?php
/**
 * Uninstall 365i Queue Optimizer
 *
 * @package QueueOptimizer
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Clean up options.
delete_option( 'queue_optimizer_time_limit' );
delete_option( 'queue_optimizer_concurrent_batches' );
delete_option( 'queue_optimizer_image_engine' );
delete_option( 'queue_optimizer_activated' );

// Clean up any legacy options.
delete_option( 'queue_optimizer_logging_enabled' );
delete_option( 'queue_optimizer_log_retention_days' );
delete_option( 'queue_optimizer_last_run' );
delete_option( 'queue_optimizer_debug_mode' );
delete_option( 'queue_optimizer_enable_concurrency_filter' );
delete_option( '365i_qo_image_engine' );

// Clear any caches.
wp_cache_flush();
