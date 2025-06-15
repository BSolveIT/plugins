<?php
/**
 * Dashboard Quick Actions Panel Template
 *
 * Displays quick action buttons in a dashboard widget format.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quick_actions = $data['quick_actions'] ?? array();
?>

<div class="postbox dashboard-panel">
	<div class="postbox-header">
		<h2 class="hndle"><?php esc_html_e( 'Quick Actions', '365i-queue-optimizer' ); ?></h2>
	</div>
	<div class="inside">
		
		<div class="quick-actions-grid">
			<?php foreach ( $quick_actions as $action ) : ?>
				<div class="quick-action-item">
					<h4><?php echo esc_html( $action['title'] ); ?></h4>
					<p><?php echo esc_html( $action['description'] ); ?></p>
					
					<?php if ( isset( $action['url'] ) ) : ?>
						<a href="<?php echo esc_url( $action['url'] ); ?>" 
						   class="button <?php echo esc_attr( $action['class'] ?? 'button-secondary' ); ?>">
							<?php echo esc_html( $action['title'] ); ?>
						</a>
					<?php else : ?>
						<button type="button" 
								class="button <?php echo esc_attr( $action['class'] ?? 'button-secondary' ); ?>" 
								data-action="<?php echo esc_attr( $action['action'] ); ?>">
							<?php echo esc_html( $action['title'] ); ?>
						</button>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( empty( $quick_actions ) ) : ?>
			<p class="no-actions"><?php esc_html_e( 'No quick actions available.', '365i-queue-optimizer' ); ?></p>
		<?php endif; ?>

	</div>
</div>