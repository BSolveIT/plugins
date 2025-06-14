<?php
$value = get_option( '365i_qo_image_engine', 'imagick' );
$imagick_available = class_exists( 'Imagick' );
?>
<select id="365i_qo_image_engine" name="365i_qo_image_engine">
	<option value="imagick" <?php selected( $value, 'imagick' ); ?>>
		<?php esc_html_e( 'ImageMagick (Imagick)', '365i-queue-optimizer' ); ?>
		<?php if ( ! $imagick_available ) : ?>
			- <?php esc_html_e( 'Not Available', '365i-queue-optimizer' ); ?>
		<?php endif; ?>
	</option>
	<option value="gd" <?php selected( $value, 'gd' ); ?>>
		<?php esc_html_e( 'GD Library', '365i-queue-optimizer' ); ?>
	</option>
</select>
<p class="description">
	<?php esc_html_e( 'Choose the image processing engine. ImageMagick provides better quality but requires the Imagick PHP extension.', '365i-queue-optimizer' ); ?>
	<?php if ( ! $imagick_available ) : ?>
		<br><strong><?php esc_html_e( 'Note: ImageMagick is not available on this server. GD Library will be used as fallback.', '365i-queue-optimizer' ); ?></strong>
	<?php endif; ?>
</p>