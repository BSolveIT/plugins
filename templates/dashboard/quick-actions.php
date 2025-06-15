<?php
/**
 * Dashboard Quick Actions Template
 *
 * Displays quick action buttons using component card format.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Quick actions should be available via extract() in parent template
$quick_actions = $quick_actions ?? array();

// Set up card header variables
$card_title = __( 'Quick Actions', '365i-queue-optimizer' );
$card_icon = 'dashicons-admin-tools';

// Include card header
include plugin_dir_path( __FILE__ ) . '../partials/card-header.php';
?>

<?php if ( ! empty( $quick_actions ) ) : ?>
	<div class="quick-actions-list">
		<?php foreach ( $quick_actions as $action ) : ?>
			<div class="quick-action-item" style="margin-bottom: 20px; padding: 16px; border: 1px solid #dcdcde; border-radius: 4px;">
				<h4 class="components-heading-medium" style="margin: 0 0 8px 0; font-size: 16px;">
					<?php echo esc_html( $action['title'] ); ?>
				</h4>
				<p class="components-base-control__help" style="margin: 0 0 12px 0; color: #646970;">
					<?php echo esc_html( $action['description'] ); ?>
				</p>
				
				<?php if ( isset( $action['url'] ) ) : ?>
					<a href="<?php echo esc_url( $action['url'] ); ?>" 
					   class="components-button <?php echo esc_attr( $action['class'] === 'button-primary' ? 'is-primary' : 'is-secondary' ); ?>"
					   style="text-decoration: none;">
						<?php echo esc_html( $action['title'] ); ?>
					</a>
				<?php else : ?>
					<button type="button" 
							class="components-button <?php echo esc_attr( $action['class'] === 'button-primary' ? 'is-primary' : 'is-secondary' ); ?>" 
							data-action="<?php echo esc_attr( $action['action'] ); ?>"
							aria-label="<?php echo esc_attr( $action['description'] ); ?>">
						<?php echo esc_html( $action['title'] ); ?>
					</button>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
<?php else : ?>
	<div class="no-actions" style="text-align: center; padding: 20px; color: #646970;">
		<p><?php esc_html_e( 'No quick actions available.', '365i-queue-optimizer' ); ?></p>
	</div>
<?php endif; ?>

<?php
// Include card footer
include plugin_dir_path( __FILE__ ) . '../partials/card-footer.php';
?>