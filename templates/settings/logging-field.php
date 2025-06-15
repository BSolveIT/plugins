<?php
/**
 * Logging Field Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<label for="queue_optimizer_logging_enabled">
	<input type="checkbox" 
	       id="queue_optimizer_logging_enabled" 
	       name="queue_optimizer_logging_enabled" 
	       value="1" 
	       <?php checked( $value, true ); ?> />
	<?php esc_html_e( 'Enable detailed logging of queue operations', '365i-queue-optimizer' ); ?>
</label>
<p class="description">
	<?php esc_html_e( 'When enabled, queue operations will be logged for debugging and monitoring purposes.', '365i-queue-optimizer' ); ?>
</p>