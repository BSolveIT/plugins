<?php
$value = get_option( 'queue_optimizer_logging_enabled', false );
?>
<label for="queue_optimizer_logging_enabled">
	<input type="checkbox" 
		   id="queue_optimizer_logging_enabled" 
		   name="queue_optimizer_logging_enabled" 
		   value="1" 
		   <?php checked( $value ); ?> />
	<?php esc_html_e( 'Enable detailed logging of queue processing', '365i-queue-optimizer' ); ?>
</label>
<p class="description">
	<?php esc_html_e( 'When enabled, detailed logs will be written to the plugin\'s logs directory.', '365i-queue-optimizer' ); ?>
</p>