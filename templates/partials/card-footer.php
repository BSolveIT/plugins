<?php
/**
 * Card Footer Partial
 *
 * Shared footer for component cards across admin pages.
 *
 * @package QueueOptimizer
 * @var array $footer_actions Optional array of footer actions/buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$footer_actions = $footer_actions ?? array();
?>

	</div><!-- .components-card__body -->
	
	<?php if ( ! empty( $footer_actions ) ) : ?>
		<div class="components-card__footer">
			<div style="display: flex; justify-content: flex-end; gap: 8px;">
				<?php foreach ( $footer_actions as $action ) : ?>
					<?php if ( isset( $action['url'] ) ) : ?>
						<a href="<?php echo esc_url( $action['url'] ); ?>"
						   class="components-button <?php echo esc_attr( $action['class'] ?? $action['type'] ?? 'is-secondary' ); ?>"
						   <?php echo isset( $action['target'] ) ? 'target="' . esc_attr( $action['target'] ) . '"' : ''; ?>>
							<?php if ( isset( $action['icon'] ) ) : ?>
								<span class="dashicons <?php echo esc_attr( $action['icon'] ); ?>" style="margin-right: 4px; font-size: 16px;"></span>
							<?php endif; ?>
							<?php echo esc_html( $action['text'] ?? $action['label'] ?? 'Button' ); ?>
						</a>
					<?php else : ?>
						<button type="button"
								class="components-button <?php echo esc_attr( $action['class'] ?? $action['type'] ?? 'is-secondary' ); ?>"
								<?php echo isset( $action['onclick'] ) ? 'onclick="' . esc_attr( $action['onclick'] ) . '"' : ''; ?>
								<?php echo isset( $action['data'] ) ? 'data-action="' . esc_attr( $action['data'] ) . '"' : ''; ?>>
							<?php if ( isset( $action['icon'] ) ) : ?>
								<span class="dashicons <?php echo esc_attr( $action['icon'] ); ?>" style="margin-right: 4px; font-size: 16px;"></span>
							<?php endif; ?>
							<?php echo esc_html( $action['text'] ?? $action['label'] ?? 'Button' ); ?>
						</button>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
	
</div><!-- .components-card -->