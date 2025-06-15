<?php
/**
 * Retention Days Field Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<input type="number" 
       id="queue_optimizer_log_retention_days" 
       name="queue_optimizer_log_retention_days" 
       value="<?php echo esc_attr( $value ); ?>" 
       min="1" 
       max="365" 
       step="1" 
       class="regular-text" />
<p class="description">
	<?php esc_html_e( 'Number of days to retain log files before automatic cleanup. Range: 1-365 days.', '365i-queue-optimizer' ); ?>
</p>