<?php
$value = get_option( 'queue_optimizer_concurrent_batches', 3 );
?>
<input type="number" 
	   id="queue_optimizer_concurrent_batches" 
	   name="queue_optimizer_concurrent_batches" 
	   value="<?php echo esc_attr( $value ); ?>" 
	   min="1" 
	   max="10" 
	   class="small-text" />
<p class="description">
	<?php esc_html_e( 'Number of batches to process concurrently. Range: 1-10 batches.', '365i-queue-optimizer' ); ?>
</p>