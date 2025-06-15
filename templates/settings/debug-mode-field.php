<?php
/**
 * Debug Mode Field Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<fieldset>
	<legend class="screen-reader-text"><?php esc_html_e( 'Debug Mode', '365i-queue-optimizer' ); ?></legend>
	<label for="queue_optimizer_debug_mode">
		<input 
			type="checkbox" 
			id="queue_optimizer_debug_mode" 
			name="queue_optimizer_debug_mode" 
			value="1" 
			<?php checked( $value, true ); ?>
		/>
		<?php esc_html_e( 'Enable debug mode for verbose logging and performance monitoring', '365i-queue-optimizer' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'When enabled, detailed debug information will be logged including execution times, memory usage, and processing steps. This may impact performance and should only be used for troubleshooting.', '365i-queue-optimizer' ); ?>
	</p>
</fieldset>