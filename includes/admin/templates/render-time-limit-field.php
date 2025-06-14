<?php
$value = get_option( 'queue_optimizer_time_limit', 30 );
?>
<input type="number" 
	   id="queue_optimizer_time_limit" 
	   name="queue_optimizer_time_limit" 
	   value="<?php echo esc_attr( $value ); ?>" 
	   min="5" 
	   max="300" 
	   class="small-text" />
<p class="description">
	<?php esc_html_e( 'Maximum time in seconds for queue processing. Range: 5-300 seconds.', '365i-queue-optimizer' ); ?>
</p>