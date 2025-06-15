<?php
/**
 * Card Header Partial
 *
 * Shared header for component cards across admin pages.
 *
 * @package QueueOptimizer
 * @var string $card_title Card title text
 * @var string $card_icon Optional dashicon class
 * @var string $card_id Optional card ID for accessibility
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$card_title = $card_title ?? '';
$card_icon = $card_icon ?? '';
$card_id = $card_id ?? '';
?>

<div class="components-card">
	<?php if ( ! empty( $card_title ) ) : ?>
		<div class="components-card__header">
			<h2 class="components-heading-medium" <?php echo $card_id ? 'id="' . esc_attr( $card_id ) . '"' : ''; ?>>
				<?php if ( ! empty( $card_icon ) ) : ?>
					<span class="dashicons <?php echo esc_attr( $card_icon ); ?>" aria-hidden="true"></span>
				<?php endif; ?>
				<?php echo esc_html( $card_title ); ?>
			</h2>
		</div>
	<?php endif; ?>
	<div class="components-card__body">