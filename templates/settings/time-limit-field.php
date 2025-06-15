<?php
/**
 * Time Limit Field Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<input type="number" 
       id="queue_optimizer_time_limit" 
       name="queue_optimizer_time_limit" 
       value="<?php echo esc_attr( $value ); ?>" 
       min="5" 
       max="300" 
       step="1" 
       class="regular-text" />
<p class="description">
	<?php esc_html_e( 'Maximum time (in seconds) for processing queue batches. Range: 5-300 seconds.', '365i-queue-optimizer' ); ?>
</p>