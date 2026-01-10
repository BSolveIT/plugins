<?php
/**
 * Admin page header partial
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'Simple & Fast', '365i-queue-optimizer' ); ?></strong></p>
		<p><?php esc_html_e( 'This plugin applies three essential optimizations to ActionScheduler for faster image processing and background tasks. No complex configuration needed - just set your preferences below.', '365i-queue-optimizer' ); ?></p>
	</div>