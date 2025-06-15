<?php
/**
 * Enable Concurrency Filter Field Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<label for="queue_optimizer_enable_concurrency_filter">
	<input type="checkbox" 
		id="queue_optimizer_enable_concurrency_filter" 
		name="queue_optimizer_enable_concurrency_filter" 
		value="1" 
		<?php checked( $value, true ); ?> />
	<?php esc_html_e( 'Apply concurrent batches setting to Action Scheduler', '365i-queue-optimizer' ); ?>
</label>
<p class="description">
	<?php esc_html_e( 'Enable this option to apply the above concurrent batches setting to WordPress Action Scheduler. Recommended for optimal image processing performance.', '365i-queue-optimizer' ); ?>
</p>