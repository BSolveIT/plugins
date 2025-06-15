<?php
/**
 * Dashboard Activity Panel Template
 *
 * Displays recent queue activity using component card format.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$recent_activity = $data['recent_activity'] ?? array();

// Set up card header variables
$card_title = __( 'Recent Activity', '365i-queue-optimizer' );
$card_icon = 'dashicons-clock';

// Set up footer actions
$footer_actions = array(
	array(
		'label' => __( 'View All Activity', '365i-queue-optimizer' ),
		'url' => admin_url( 'admin.php?page=365i-activity-log' ),
		'type' => 'is-secondary',
	),
);

// Include card header
include plugin_dir_path( __FILE__ ) . '../partials/card-header.php';
?>

<?php if ( ! empty( $recent_activity ) ) : ?>
	<div class="activity-list" style="max-height: 300px; overflow-y: auto;">
		<?php foreach ( $recent_activity as $activity ) : ?>
			<div class="activity-item" style="display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f0f0f1;">
				<div class="activity-status" style="margin-top: 4px;">
					<?php $status = $activity['status'] ?? 'unknown'; ?>
					<?php if ( 'complete' === $status ) : ?>
						<span class="components-badge" style="background-color: #46b450; color: white; width: 8px; height: 8px; border-radius: 50%; display: block; padding: 0;"></span>
					<?php elseif ( 'failed' === $status ) : ?>
						<span class="components-badge" style="background-color: #dc3232; color: white; width: 8px; height: 8px; border-radius: 50%; display: block; padding: 0;"></span>
					<?php elseif ( 'pending' === $status ) : ?>
						<span class="components-badge" style="background-color: #ffb900; color: white; width: 8px; height: 8px; border-radius: 50%; display: block; padding: 0;"></span>
					<?php else : ?>
						<span class="components-badge" style="background-color: #229fd8; color: white; width: 8px; height: 8px; border-radius: 50%; display: block; padding: 0;"></span>
					<?php endif; ?>
				</div>
				<div class="activity-details" style="flex: 1;">
					<div class="activity-action" style="font-weight: 500; font-size: 14px; margin-bottom: 4px;">
						<?php echo esc_html( $activity['action'] ); ?>
					</div>
					<div class="activity-meta" style="display: flex; gap: 12px; font-size: 12px; color: #646970;">
						<span class="activity-time"><?php echo esc_html( $activity['time_ago'] ); ?> ago</span>
						<span class="activity-status-text"><?php echo esc_html( ucfirst( $activity['status'] ) ); ?></span>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php else : ?>
	<div class="no-activity" style="text-align: center; padding: 20px; color: #646970;">
		<p><?php esc_html_e( 'No recent activity found.', '365i-queue-optimizer' ); ?></p>
		<p class="components-base-control__help" style="font-size: 13px; margin-top: 8px;">
			<?php esc_html_e( 'Queue activities will appear here once jobs start running.', '365i-queue-optimizer' ); ?>
		</p>
	</div>
<?php endif; ?>

<?php
// Include card footer
include plugin_dir_path( __FILE__ ) . '../partials/card-footer.php';
?>