<?php
/**
 * Activity Log Template
 *
 * Main activity log page display for the Queue Optimizer plugin.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include header partial.
include plugin_dir_path( __FILE__ ) . 'partials/header.php';

$activity_logs = $data['activity_logs'] ?? array();
$log_stats = $data['log_stats'] ?? array();
$system_events = $data['system_events'] ?? array();
$log_settings = $data['log_settings'] ?? array();
?>

<div class="queue-optimizer-activity-log">

	<!-- Log Statistics -->
	<div class="activity-log-stats-grid">
		
		<div class="components-card">
			<div class="components-card__body" style="text-align: center; padding: 20px;">
				<div class="stat-icon" style="margin-bottom: 12px;">
					<span class="dashicons dashicons-list-view" style="font-size: 24px; color: #0073aa;"></span>
				</div>
				<div class="stat-number" style="font-size: 28px; font-weight: 600; margin-bottom: 4px;">
					<?php echo esc_html( number_format_i18n( $log_stats['total_logs'] ?? 0 ) ); ?>
				</div>
				<div class="stat-label" style="color: #646970; font-size: 14px;">
					<?php esc_html_e( 'Total Logs', '365i-queue-optimizer' ); ?>
				</div>
			</div>
		</div>

		<div class="components-card">
			<div class="components-card__body" style="text-align: center; padding: 20px;">
				<div class="stat-icon" style="margin-bottom: 12px;">
					<span class="dashicons dashicons-yes-alt" style="font-size: 24px; color: #46b450;"></span>
				</div>
				<div class="stat-number" style="font-size: 28px; font-weight: 600; margin-bottom: 4px;">
					<?php echo esc_html( number_format_i18n( $log_stats['success_logs'] ?? 0 ) ); ?>
				</div>
				<div class="stat-label" style="color: #646970; font-size: 14px;">
					<?php esc_html_e( 'Successful', '365i-queue-optimizer' ); ?>
				</div>
			</div>
		</div>

		<div class="components-card">
			<div class="components-card__body" style="text-align: center; padding: 20px;">
				<div class="stat-icon" style="margin-bottom: 12px;">
					<span class="dashicons dashicons-dismiss" style="font-size: 24px; color: #dc3232;"></span>
				</div>
				<div class="stat-number" style="font-size: 28px; font-weight: 600; margin-bottom: 4px;">
					<?php echo esc_html( number_format_i18n( $log_stats['error_logs'] ?? 0 ) ); ?>
				</div>
				<div class="stat-label" style="color: #646970; font-size: 14px;">
					<?php esc_html_e( 'Errors', '365i-queue-optimizer' ); ?>
				</div>
			</div>
		</div>

		<div class="components-card">
			<div class="components-card__body" style="text-align: center; padding: 20px;">
				<div class="stat-icon" style="margin-bottom: 12px;">
					<span class="dashicons dashicons-clock" style="font-size: 24px; color: #ffb900;"></span>
				</div>
				<div class="stat-number" style="font-size: 28px; font-weight: 600; margin-bottom: 4px;">
					<?php echo esc_html( number_format_i18n( $log_stats['pending_logs'] ?? 0 ) ); ?>
				</div>
				<div class="stat-label" style="color: #646970; font-size: 14px;">
					<?php esc_html_e( 'Pending', '365i-queue-optimizer' ); ?>
				</div>
			</div>
		</div>

	</div>

	<!-- Log Management Controls -->
	<div class="components-card" style="margin-bottom: 24px;">
		<div class="components-card__header">
			<h2><?php esc_html_e( 'Log Management', '365i-queue-optimizer' ); ?></h2>
		</div>
		<div class="components-card__body">
			<div class="log-controls" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
				
				<div class="log-control-group">
					<label class="components-base-control__label" style="margin-bottom: 8px; display: block;">
						<?php esc_html_e( 'Logging Enabled', '365i-queue-optimizer' ); ?>
					</label>
					<div class="components-toggle-control">
						<span class="components-badge" style="background-color: <?php echo $log_settings['logging_enabled'] === 'yes' ? '#46b450' : '#dc3232'; ?>; color: white; padding: 4px 8px; border-radius: 4px;">
							<?php echo $log_settings['logging_enabled'] === 'yes' ? esc_html__( 'Enabled', '365i-queue-optimizer' ) : esc_html__( 'Disabled', '365i-queue-optimizer' ); ?>
						</span>
					</div>
				</div>

				<div class="log-control-group">
					<label class="components-base-control__label" style="margin-bottom: 8px; display: block;">
						<?php esc_html_e( 'Debug Mode', '365i-queue-optimizer' ); ?>
					</label>
					<div class="components-toggle-control">
						<span class="components-badge" style="background-color: <?php echo $log_settings['debug_mode'] === 'yes' ? '#46b450' : '#646970'; ?>; color: white; padding: 4px 8px; border-radius: 4px;">
							<?php echo $log_settings['debug_mode'] === 'yes' ? esc_html__( 'Enabled', '365i-queue-optimizer' ) : esc_html__( 'Disabled', '365i-queue-optimizer' ); ?>
						</span>
					</div>
				</div>

				<div class="log-actions" style="margin-left: auto; display: flex; gap: 8px;">
					<button type="button" class="components-button is-secondary" data-action="export-logs" data-format="csv">
						<span class="dashicons dashicons-download" style="margin-right: 4px;"></span>
						<?php esc_html_e( 'Export CSV', '365i-queue-optimizer' ); ?>
					</button>
					<button type="button" class="components-button is-secondary" data-action="export-logs" data-format="json">
						<span class="dashicons dashicons-download" style="margin-right: 4px;"></span>
						<?php esc_html_e( 'Export JSON', '365i-queue-optimizer' ); ?>
					</button>
					<button type="button" class="components-button is-destructive" data-action="clear-logs" data-type="all">
						<span class="dashicons dashicons-trash" style="margin-right: 4px;"></span>
						<?php esc_html_e( 'Clear All Logs', '365i-queue-optimizer' ); ?>
					</button>
				</div>

			</div>
		</div>
	</div>

	<!-- Activity Logs -->
	<div class="components-card" style="margin-bottom: 24px;">
		<div class="components-card__header">
			<h2><?php esc_html_e( 'Activity Logs', '365i-queue-optimizer' ); ?></h2>
		</div>
		<div class="components-card__body">
			<?php if ( ! empty( $activity_logs ) ) : ?>
				
				<!-- Search and Filter Controls -->
				<div class="log-search-filter-controls">
					<input type="text" class="log-search-input" placeholder="<?php esc_attr_e( 'Search logs...', '365i-queue-optimizer' ); ?>" />
					<select class="log-filter-select">
						<option value="all"><?php esc_html_e( 'All Status', '365i-queue-optimizer' ); ?></option>
						<option value="complete"><?php esc_html_e( 'Complete', '365i-queue-optimizer' ); ?></option>
						<option value="failed"><?php esc_html_e( 'Failed', '365i-queue-optimizer' ); ?></option>
						<option value="pending"><?php esc_html_e( 'Pending', '365i-queue-optimizer' ); ?></option>
						<option value="in-progress"><?php esc_html_e( 'In Progress', '365i-queue-optimizer' ); ?></option>
						<option value="canceled"><?php esc_html_e( 'Canceled', '365i-queue-optimizer' ); ?></option>
					</select>
					<a href="#" class="refresh-logs">
						<span class="dashicons dashicons-update-alt"></span>
						<?php esc_html_e( 'Refresh', '365i-queue-optimizer' ); ?>
					</a>
				</div>

				<!-- Bulk Actions -->
				<div class="bulk-actions-container" style="display: none; margin-bottom: 16px; padding: 12px; background: #f9f9f9; border-radius: 4px;">
					<div style="display: flex; align-items: center; gap: 12px;">
						<span class="selected-count">0 <?php esc_html_e( 'items selected', '365i-queue-optimizer' ); ?></span>
						<select class="bulk-action-select">
							<option value=""><?php esc_html_e( 'Bulk Actions', '365i-queue-optimizer' ); ?></option>
							<option value="retry"><?php esc_html_e( 'Retry Selected', '365i-queue-optimizer' ); ?></option>
							<option value="cancel"><?php esc_html_e( 'Cancel Selected', '365i-queue-optimizer' ); ?></option>
						</select>
						<button type="button" class="components-button is-secondary apply-bulk-action">
							<?php esc_html_e( 'Apply', '365i-queue-optimizer' ); ?>
						</button>
						<button type="button" class="components-button is-tertiary clear-selection">
							<?php esc_html_e( 'Clear Selection', '365i-queue-optimizer' ); ?>
						</button>
					</div>
				</div>

				<div class="activity-log-table" style="overflow-x: auto;">
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th style="width: 3%;"><input type="checkbox" class="select-all-logs" /></th>
								<th style="width: 20%;"><?php esc_html_e( 'Action', '365i-queue-optimizer' ); ?></th>
								<th style="width: 10%;"><?php esc_html_e( 'Status', '365i-queue-optimizer' ); ?></th>
								<th style="width: 15%;"><?php esc_html_e( 'Executed', '365i-queue-optimizer' ); ?></th>
								<th style="width: 10%;"><?php esc_html_e( 'Time Ago', '365i-queue-optimizer' ); ?></th>
								<th style="width: 32%;"><?php esc_html_e( 'Message', '365i-queue-optimizer' ); ?></th>
								<th style="width: 10%;"><?php esc_html_e( 'Actions', '365i-queue-optimizer' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $activity_logs as $log ) : ?>
								<tr data-action-id="<?php echo esc_attr( $log['id'] ?? 0 ); ?>">
									<td>
										<input type="checkbox" class="log-checkbox" value="<?php echo esc_attr( $log['id'] ?? 0 ); ?>" />
									</td>
									<td>
										<strong><?php echo esc_html( $log['action'] ); ?></strong>
									</td>
									<td>
										<?php
										$status = $log['status'];
										$badge_color = '#646970';
										if ( 'complete' === $status ) {
											$badge_color = '#46b450';
										} elseif ( 'failed' === $status ) {
											$badge_color = '#dc3232';
										} elseif ( 'pending' === $status ) {
											$badge_color = '#ffb900';
										} elseif ( 'in-progress' === $status ) {
											$badge_color = '#00a0d2';
										} elseif ( 'canceled' === $status ) {
											$badge_color = '#999';
										}
										?>
										<span class="components-badge" style="background-color: <?php echo esc_attr( $badge_color ); ?>; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
											<?php echo esc_html( ucfirst( $status ) ); ?>
										</span>
									</td>
									<td>
										<?php echo esc_html( $log['executed'] ); ?>
									</td>
									<td>
										<?php echo esc_html( $log['time_ago'] ); ?> ago
									</td>
									<td>
										<div class="log-message" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: pointer;" title="<?php echo esc_attr( $log['message'] ); ?>">
											<?php echo esc_html( $log['message'] ); ?>
										</div>
									</td>
									<td>
										<div class="row-actions" style="display: flex; gap: 4px;">
											<?php if ( $log['can_retry'] ?? false ) : ?>
												<button type="button" class="components-button is-small is-secondary retry-action"
														data-action-id="<?php echo esc_attr( $log['id'] ?? 0 ); ?>"
														title="<?php esc_attr_e( 'Retry this action', '365i-queue-optimizer' ); ?>">
													<span class="dashicons dashicons-update-alt" style="font-size: 14px; width: 14px; height: 14px;"></span>
												</button>
											<?php endif; ?>
											<?php if ( $log['can_cancel'] ?? false ) : ?>
												<button type="button" class="components-button is-small is-destructive cancel-action"
														data-action-id="<?php echo esc_attr( $log['id'] ?? 0 ); ?>"
														title="<?php esc_attr_e( 'Cancel this action', '365i-queue-optimizer' ); ?>">
													<span class="dashicons dashicons-no-alt" style="font-size: 14px; width: 14px; height: 14px;"></span>
												</button>
											<?php endif; ?>
											<button type="button" class="components-button is-small expand-message"
													title="<?php esc_attr_e( 'Expand message', '365i-queue-optimizer' ); ?>">
												<span class="dashicons dashicons-visibility" style="font-size: 14px; width: 14px; height: 14px;"></span>
											</button>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<div class="no-logs" style="text-align: center; padding: 40px; color: #646970;">
					<p><?php esc_html_e( 'No activity logs found.', '365i-queue-optimizer' ); ?></p>
					<p class="components-base-control__help" style="font-size: 13px; margin-top: 8px;">
						<?php esc_html_e( 'Activity logs will appear here when queue jobs are processed.', '365i-queue-optimizer' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<div class="components-card__footer">
			<p class="components-base-control__help">
				<?php esc_html_e( 'Click on rows to select them for bulk actions. Use the expand button to view full messages.', '365i-queue-optimizer' ); ?>
			</p>
		</div>
	</div>

	<!-- System Events -->
	<?php if ( ! empty( $system_events ) ) : ?>
		<div class="components-card">
			<div class="components-card__header">
				<h2><?php esc_html_e( 'System Events', '365i-queue-optimizer' ); ?></h2>
			</div>
			<div class="components-card__body">
				<div class="system-events-list">
					<?php foreach ( $system_events as $event ) : ?>
						<div class="system-event-item" style="display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f0f0f1;">
							<div class="event-type" style="margin-top: 4px;">
								<?php if ( 'system' === $event['type'] ) : ?>
									<span class="dashicons dashicons-admin-settings" style="color: #0073aa;"></span>
								<?php else : ?>
									<span class="dashicons dashicons-info" style="color: #646970;"></span>
								<?php endif; ?>
							</div>
							<div class="event-details" style="flex: 1;">
								<div class="event-title" style="font-weight: 500; margin-bottom: 4px;">
									<?php echo esc_html( $event['event'] ); ?>
								</div>
								<div class="event-meta" style="font-size: 12px; color: #646970;">
									<?php echo esc_html( $event['timestamp'] ); ?>
									<?php if ( ! empty( $event['details'] ) ) : ?>
										- <?php echo esc_html( $event['details'] ); ?>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>

</div>

<?php
// Include footer partial.
include plugin_dir_path( __FILE__ ) . 'partials/footer.php';
?>