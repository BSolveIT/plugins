<?php
/**
 * Image Engine Field Template
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$engines = array(
	'imagick' => __( 'ImageMagick', '365i-queue-optimizer' ),
	'gd'      => __( 'GD Library', '365i-queue-optimizer' ),
);
?>

<select id="365i_qo_image_engine" name="365i_qo_image_engine" class="regular-text">
	<?php foreach ( $engines as $engine_value => $engine_label ) : ?>
		<option value="<?php echo esc_attr( $engine_value ); ?>" 
		        <?php selected( $value, $engine_value ); ?>>
			<?php echo esc_html( $engine_label ); ?>
		</option>
	<?php endforeach; ?>
</select>
<p class="description">
	<?php esc_html_e( 'Select the image processing engine. ImageMagick is recommended for better performance and quality.', '365i-queue-optimizer' ); ?>
</p>