<!-- Status Dashboard -->
	<div class="queue-optimizer-dashboard-panel">
		<h2><?php esc_html_e( 'Queue Status Dashboard', '365i-queue-optimizer' ); ?></h2>
		
		<div class="queue-optimizer-status-grid">
			<div class="status-item pending">
				<h3><?php esc_html_e( 'Pending', '365i-queue-optimizer' ); ?></h3>
				<span class="count" id="pending-count"><?php echo esc_html( number_format( $status['pending'] ) ); ?></span>
			</div>
			
			<div class="status-item processing">
				<h3><?php esc_html_e( 'Processing', '365i-queue-optimizer' ); ?></h3>
				<span class="count" id="processing-count"><?php echo esc_html( number_format( $status['processing'] ) ); ?></span>
			</div>
			
			<div class="status-item completed">
				<h3><?php esc_html_e( 'Completed', '365i-queue-optimizer' ); ?></h3>
				<span class="count" id="completed-count"><?php echo esc_html( number_format( $status['completed'] ) ); ?></span>
			</div>
			
			<div class="status-item failed">
				<h3><?php esc_html_e( 'Failed', '365i-queue-optimizer' ); ?></h3>
				<span class="count" id="failed-count"><?php echo esc_html( number_format( $status['failed'] ) ); ?></span>
			</div>
		</div>

		<div class="queue-optimizer-last-run">
			<p>
				<strong><?php esc_html_e( 'Last Run:', '365i-queue-optimizer' ); ?></strong>
				<?php
				if ( $status['last_run'] ) {
					echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $status['last_run'] ) );
				} else {
					esc_html_e( 'Never', '365i-queue-optimizer' );
				}
				?>
			</p>
		</div>

		<div class="queue-optimizer-actions">
			<button type="button" id="run-queue-now" class="button button-primary">
				<?php esc_html_e( 'Run Now', '365i-queue-optimizer' ); ?>
			</button>
			
			<?php if ( get_option( 'queue_optimizer_logging_enabled', false ) ) : ?>
			<button type="button" id="view-logs" class="button button-secondary">
				<?php esc_html_e( 'View Logs', '365i-queue-optimizer' ); ?>
			</button>
			<?php endif; ?>
			
			<button type="button" id="clear-logs" class="button button-secondary">
				<?php esc_html_e( 'Clear Plugin Logs', '365i-queue-optimizer' ); ?>
			</button>
			
			<button type="button" id="clear-action-scheduler-logs" class="button button-secondary">
				<?php esc_html_e( 'Clear Action Scheduler Logs', '365i-queue-optimizer' ); ?>
			</button>
		</div>

		<div id="queue-optimizer-messages" class="queue-optimizer-messages" style="display: none;"></div>
		
		<?php if ( get_option( 'queue_optimizer_logging_enabled', false ) ) : ?>
		<div id="queue-optimizer-logs" class="queue-optimizer-logs" style="display: none;">
			<h3><?php esc_html_e( 'Queue Optimizer Logs', '365i-queue-optimizer' ); ?></h3>
			<div class="log-content">
				<pre id="log-display"></pre>
			</div>
			<div class="log-actions">
				<button type="button" id="refresh-logs" class="button button-small">
					<?php esc_html_e( 'Refresh', '365i-queue-optimizer' ); ?>
				</button>
				<button type="button" id="close-logs" class="button button-small">
					<?php esc_html_e( 'Close', '365i-queue-optimizer' ); ?>
				</button>
			</div>
		</div>
		<?php endif; ?>
		
		<!-- Powered by 365i WordPress Hosting -->
		<div style="margin-top: 30px; padding: 10px; border-top: 1px solid #ddd; color: #666; font-size: 12px;">
			<p><?php esc_html_e( 'Powered by', '365i-queue-optimizer' ); ?> <a href="https://365i.com/" target="_blank" rel="noopener">365i WordPress Hosting</a></p>
		</div>
	</div>
</div>