<?php
/**
 * Concurrent Batches Field Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<input type="number"
       id="queue_optimizer_concurrent_batches"
       name="queue_optimizer_concurrent_batches"
       value="<?php echo esc_attr( $value ); ?>"
       min="1"
       max="10"
       step="1"
       class="regular-text" />
<p class="description">
	<?php esc_html_e( 'Number of concurrent batches for ActionScheduler processing. Default: 4 batches. Range: 1-10 batches.', '365i-queue-optimizer' ); ?>
</p>