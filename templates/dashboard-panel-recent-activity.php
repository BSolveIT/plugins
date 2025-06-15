<?php
/**
 * Dashboard Recent Activity Panel Template
 *
 * Displays recent queue activity in a dashboard widget format.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$recent_activity = $data['recent_activity'] ?? array();
?>

<div class="postbox dashboard-panel">
	<div class="postbox-header">
		<h2 class="hndle"><?php esc_html_e( 'Recent Activity', '365i-queue-optimizer' ); ?></h2>
	</div>
	<div class="inside">
		
		<?php if ( ! empty( $recent_activity ) ) : ?>
			<div class="activity-list">
				<?php foreach ( $recent_activity as $activity ) : ?>
					<div class="activity-item">
						<div class="activity-status">
							<span class="status-indicator status-<?php echo esc_attr( $activity['status'] ); ?>"></span>
						</div>
						<div class="activity-details">
							<div class="activity-action">
								<?php echo esc_html( $activity['action'] ); ?>
							</div>
							<div class="activity-meta">
								<span class="activity-time"><?php echo esc_html( $activity['time_ago'] ); ?> ago</span>
								<span class="activity-status-text"><?php echo esc_html( ucfirst( $activity['status'] ) ); ?></span>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			
			<div class="activity-footer">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=365i-system-info' ) ); ?>" 
				   class="button button-secondary">
					<?php esc_html_e( 'View All Activity', '365i-queue-optimizer' ); ?>
				</a>
			</div>
		<?php else : ?>
			<div class="no-activity">
				<p><?php esc_html_e( 'No recent activity found.', '365i-queue-optimizer' ); ?></p>
				<p class="description">
					<?php esc_html_e( 'Queue activities will appear here once jobs start running.', '365i-queue-optimizer' ); ?>
				</p>
			</div>
		<?php endif; ?>

	</div>
</div>