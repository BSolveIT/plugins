<?php
/**
 * Usage Analytics Admin Template
 *
 * @package    AI_FAQ_Generator
 * @subpackage Templates/Admin
 * @since      2.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include header partial.
require_once AI_FAQ_GEN_DIR . 'templates/partials/header.php';
?>

<div class="wrap ai-faq-usage-analytics">
	<h1><?php esc_html_e( 'Usage Analytics', '365i-ai-faq-generator' ); ?></h1>
	
	<p class="description">
		<?php esc_html_e( 'Monitor worker usage, rate limiting violations, and system performance metrics across all Cloudflare Workers.', '365i-ai-faq-generator' ); ?>
	</p>

	<!-- Analytics Filters -->
	<div class="ai-faq-admin-section">
		<h2><?php esc_html_e( 'Analytics Filters', '365i-ai-faq-generator' ); ?></h2>
		
		<form id="analytics-filters" class="ai-faq-analytics-filters">
			<?php wp_nonce_field( 'ai_faq_rate_limit_nonce', 'analytics_nonce' ); ?>
			
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="analytics_timeframe"><?php esc_html_e( 'Timeframe', '365i-ai-faq-generator' ); ?></label>
						</th>
						<td>
							<select id="analytics_timeframe" name="timeframe" class="regular-text">
								<option value="hourly"><?php esc_html_e( 'Last 24 Hours', '365i-ai-faq-generator' ); ?></option>
								<option value="daily" selected><?php esc_html_e( 'Last 7 Days', '365i-ai-faq-generator' ); ?></option>
								<option value="weekly"><?php esc_html_e( 'Last 4 Weeks', '365i-ai-faq-generator' ); ?></option>
								<option value="monthly"><?php esc_html_e( 'Last 12 Months', '365i-ai-faq-generator' ); ?></option>
							</select>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="analytics_worker"><?php esc_html_e( 'Worker', '365i-ai-faq-generator' ); ?></label>
						</th>
						<td>
							<select id="analytics_worker" name="worker" class="regular-text">
								<option value="all"><?php esc_html_e( 'All Workers', '365i-ai-faq-generator' ); ?></option>
								<option value="faq-answer-generator-worker"><?php esc_html_e( 'FAQ Answer Generator', '365i-ai-faq-generator' ); ?></option>
								<option value="faq-realtime-assistant-worker"><?php esc_html_e( 'Realtime Assistant', '365i-ai-faq-generator' ); ?></option>
								<option value="faq-enhancement-worker"><?php esc_html_e( 'FAQ Enhancement', '365i-ai-faq-generator' ); ?></option>
								<option value="faq-seo-analyzer-worker"><?php esc_html_e( 'SEO Analyzer', '365i-ai-faq-generator' ); ?></option>
								<option value="faq-proxy-fetch"><?php esc_html_e( 'Proxy Fetch', '365i-ai-faq-generator' ); ?></option>
								<option value="url-to-faq-generator-worker"><?php esc_html_e( 'URL to FAQ Generator', '365i-ai-faq-generator' ); ?></option>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			
			<div class="analytics-actions">
				<button type="button" class="button button-primary" id="refresh-analytics">
					<?php esc_html_e( 'Refresh Analytics', '365i-ai-faq-generator' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="export-analytics">
					<?php esc_html_e( 'Export Data', '365i-ai-faq-generator' ); ?>
				</button>
			</div>
		</form>
	</div>


	<!-- Analytics Overview -->
	<div class="ai-faq-admin-section">
		<h2><?php esc_html_e( 'Analytics Overview', '365i-ai-faq-generator' ); ?></h2>
		
		<div class="ai-faq-analytics-grid">
			<div class="analytics-card">
				<h3><?php esc_html_e( 'Total Requests', '365i-ai-faq-generator' ); ?></h3>
				<div class="analytics-metric">
					<span class="metric-value"><?php echo esc_html( number_format( $analytics_data['total_requests'] ?? 0 ) ); ?></span>
					<span class="metric-label"><?php esc_html_e( 'requests', '365i-ai-faq-generator' ); ?></span>
				</div>
				<?php if ( isset( $analytics_data['data_source'] ) ) : ?>
					<div class="data-source-indicator">
						<?php if ( 'kv_live' === $analytics_data['data_source'] ) : ?>
							<span class="connection-status-badge connected">Live from KV</span>
						<?php elseif ( 'kv_empty' === $analytics_data['data_source'] ) : ?>
							<span class="connection-status-badge ready">KV connected (no data yet)</span>
						<?php elseif ( 'fallback' === $analytics_data['data_source'] ) : ?>
							<span class="connection-status-badge pending">Fallback data</span>
						<?php else : ?>
							<span class="connection-status-badge ready">Demo data</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
			
			<div class="analytics-card">
				<h3><?php esc_html_e( 'Blocked Requests', '365i-ai-faq-generator' ); ?></h3>
				<div class="analytics-metric">
					<span class="metric-value blocked"><?php echo esc_html( number_format( $analytics_data['blocked_requests'] ?? 0 ) ); ?></span>
					<span class="metric-label"><?php esc_html_e( 'blocked', '365i-ai-faq-generator' ); ?></span>
				</div>
				<?php if ( $analytics_data['total_requests'] > 0 ) : ?>
					<?php $block_rate = round( ( $analytics_data['blocked_requests'] / $analytics_data['total_requests'] ) * 100, 1 ); ?>
					<div class="data-source-indicator">
						<span class="<?php echo $block_rate > 5 ? 'status-red' : 'status-green'; ?>">
							<?php echo esc_html( $block_rate ); ?>% block rate
						</span>
					</div>
				<?php endif; ?>
			</div>
			
			<div class="analytics-card">
				<h3><?php esc_html_e( 'Violations', '365i-ai-faq-generator' ); ?></h3>
				<div class="analytics-metric">
					<span class="metric-value violations"><?php echo esc_html( number_format( $analytics_data['violations'] ?? 0 ) ); ?></span>
					<span class="metric-label"><?php esc_html_e( 'violations', '365i-ai-faq-generator' ); ?></span>
				</div>
				<div class="data-source-indicator">
					<?php if ( $analytics_data['violations'] > 0 ) : ?>
						<span class="status-red">Active violations</span>
					<?php else : ?>
						<span class="status-green">No violations</span>
					<?php endif; ?>
				</div>
			</div>
			
			<div class="analytics-card">
				<h3><?php esc_html_e( 'Unique IPs', '365i-ai-faq-generator' ); ?></h3>
				<div class="analytics-metric">
					<span class="metric-value"><?php echo esc_html( number_format( $analytics_data['unique_ips'] ?? 0 ) ); ?></span>
					<span class="metric-label"><?php esc_html_e( 'unique IPs', '365i-ai-faq-generator' ); ?></span>
				</div>
				<?php if ( isset( $analytics_data['last_updated'] ) ) : ?>
					<div class="analytics-updated">
						<strong><?php echo esc_html( gmdate( 'H:i', strtotime( $analytics_data['last_updated'] ) ) ); ?></strong>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Top Violators -->
	<?php if ( ! empty( $analytics_data['top_violators'] ) ) : ?>
		<div class="ai-faq-admin-section">
			<h2><?php esc_html_e( 'Top Violators', '365i-ai-faq-generator' ); ?></h2>
			
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'IP Address', '365i-ai-faq-generator' ); ?></th>
						<th><?php esc_html_e( 'Violations', '365i-ai-faq-generator' ); ?></th>
						<th><?php esc_html_e( 'Last Violation', '365i-ai-faq-generator' ); ?></th>
						<th><?php esc_html_e( 'Status', '365i-ai-faq-generator' ); ?></th>
						<th><?php esc_html_e( 'Actions', '365i-ai-faq-generator' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $analytics_data['top_violators'] as $violator ) : ?>
						<tr>
							<td><code><?php echo esc_html( $violator['ip'] ?? 'N/A' ); ?></code></td>
							<td><?php echo esc_html( $violator['violation_count'] ?? 0 ); ?></td>
							<td><?php echo esc_html( $violator['last_violation'] ?? 'Unknown' ); ?></td>
							<td>
								<?php
								$status = $violator['status'] ?? 'active';
								$status_class = $status === 'blocked' ? 'status-red' : 'status-orange';
								?>
								<span class="<?php echo esc_attr( $status_class ); ?>">
									<?php echo esc_html( ucfirst( $status ) ); ?>
								</span>
							</td>
							<td>
								<button type="button" 
								        class="button button-small add-to-blacklist" 
								        data-ip="<?php echo esc_attr( $violator['ip'] ?? '' ); ?>">
									<?php esc_html_e( 'Blacklist', '365i-ai-faq-generator' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<!-- Worker Usage -->
	<?php if ( ! empty( $analytics_data['worker_usage'] ) ) : ?>
		<div class="ai-faq-admin-section">
			<h2><?php esc_html_e( 'Worker Usage Breakdown', '365i-ai-faq-generator' ); ?></h2>
			
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Worker', '365i-ai-faq-generator' ); ?></th>
						<th><?php esc_html_e( 'Total Requests', '365i-ai-faq-generator' ); ?></th>
						<th><?php esc_html_e( 'Successful', '365i-ai-faq-generator' ); ?></th>
						<th><?php esc_html_e( 'Blocked', '365i-ai-faq-generator' ); ?></th>
						<th><?php esc_html_e( 'Success Rate', '365i-ai-faq-generator' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $analytics_data['worker_usage'] as $worker => $usage ) : ?>
						<tr>
							<td><?php echo esc_html( $worker ); ?></td>
							<td><?php echo esc_html( $usage['total'] ?? 0 ); ?></td>
							<td class="status-green"><?php echo esc_html( $usage['successful'] ?? 0 ); ?></td>
							<td class="status-red"><?php echo esc_html( $usage['blocked'] ?? 0 ); ?></td>
							<td>
								<?php
								$total = $usage['total'] ?? 0;
								$successful = $usage['successful'] ?? 0;
								$rate = $total > 0 ? round( ( $successful / $total ) * 100, 1 ) : 0;
								?>
								<?php echo esc_html( $rate ); ?>%
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<!-- System Status -->
	<div class="ai-faq-admin-section">
		<h2><?php esc_html_e( 'System Status', '365i-ai-faq-generator' ); ?></h2>
		
		<div class="ai-faq-status-grid">
			<div class="status-card">
				<h3><?php esc_html_e( 'Analytics Status', '365i-ai-faq-generator' ); ?></h3>
				<div class="status-indicator">
					<span class="status-green">✓</span>
					<?php esc_html_e( 'Collecting data', '365i-ai-faq-generator' ); ?>
				</div>
			</div>
			
			<div class="status-card">
				<h3><?php esc_html_e( 'Last Update', '365i-ai-faq-generator' ); ?></h3>
				<div class="status-indicator">
					<span class="status-blue">⏱</span>
					<?php echo esc_html( $analytics_data['last_updated'] ?? 'Unknown' ); ?>
				</div>
			</div>
		</div>
	</div>

	<!-- KV Connection Diagnostics -->
	<?php if ( isset( $analytics_data['diagnostic_info'] ) ) : ?>
		<div class="ai-faq-admin-section ai-faq-kv-diagnostics">
			<h2><?php esc_html_e( 'Cloudflare KV Diagnostics', '365i-ai-faq-generator' ); ?></h2>
			
			<div class="ai-faq-status-grid">
				<?php
				$credentials_configured = $analytics_data['diagnostic_info']['credentials_configured'];
				$card_class = $credentials_configured ? 'success' : 'error';
				?>
				<div class="status-card <?php echo esc_attr( $card_class ); ?>">
					<h3><?php esc_html_e( 'API Credentials', '365i-ai-faq-generator' ); ?></h3>
					<div class="status-indicator">
						<?php if ( $credentials_configured ) : ?>
							<span class="status-icon status-green">✓</span>
							<span class="connection-status-badge connected"><?php esc_html_e( 'Configured', '365i-ai-faq-generator' ); ?></span>
						<?php else : ?>
							<span class="status-icon status-red">✗</span>
							<span class="connection-status-badge disconnected"><?php esc_html_e( 'Missing Credentials', '365i-ai-faq-generator' ); ?></span>
						<?php endif; ?>
					</div>
					<div class="status-details">
						<small>
							<strong>Account ID:</strong> <?php echo $analytics_data['diagnostic_info']['account_id_set'] ? '<span class="status-green">✓ Configured</span>' : '<span class="status-red">✗ Missing</span>'; ?><br>
							<strong>API Token:</strong> <?php echo $analytics_data['diagnostic_info']['api_token_set'] ? '<span class="status-green">✓ Configured</span>' : '<span class="status-red">✗ Missing</span>'; ?>
						</small>
					</div>
				</div>
				
				<?php
				$test_result = $analytics_data['diagnostic_info']['test_connection'];
				if ( 'success' === $test_result ) {
					$connection_class = 'success';
				} elseif ( 'not_tested' === $test_result ) {
					$connection_class = 'warning';
				} else {
					$connection_class = 'error';
				}
				?>
				<div class="status-card <?php echo esc_attr( $connection_class ); ?>">
					<h3><?php esc_html_e( 'KV Connection', '365i-ai-faq-generator' ); ?></h3>
					<div class="status-indicator">
						<?php if ( 'success' === $test_result ) : ?>
							<span class="status-icon status-green">✓</span>
							<span class="connection-status-badge connected"><?php esc_html_e( 'Connected', '365i-ai-faq-generator' ); ?></span>
						<?php elseif ( 'not_tested' === $test_result ) : ?>
							<span class="status-icon status-orange">⚠</span>
							<span class="connection-status-badge pending"><?php esc_html_e( 'Not Tested', '365i-ai-faq-generator' ); ?></span>
						<?php else : ?>
							<span class="status-icon status-red">✗</span>
							<span class="connection-status-badge disconnected"><?php esc_html_e( 'Connection Failed', '365i-ai-faq-generator' ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( 'success' !== $test_result && 'not_tested' !== $test_result ) : ?>
						<div class="status-details">
							<small><strong>Error Details:</strong><br><?php echo esc_html( $test_result ); ?></small>
						</div>
					<?php endif; ?>
				</div>

				<?php
				$data_source = $analytics_data['data_source'] ?? 'unknown';
				if ( 'kv_live' === $data_source ) {
					$source_class = 'success';
				} elseif ( 'kv_empty' === $data_source ) {
					$source_class = 'info';
				} elseif ( 'fallback' === $data_source ) {
					$source_class = 'warning';
				} else {
					$source_class = 'info';
				}
				?>
				<div class="status-card <?php echo esc_attr( $source_class ); ?>">
					<h3><?php esc_html_e( 'Data Source', '365i-ai-faq-generator' ); ?></h3>
					<div class="status-indicator">
						<?php if ( 'kv_live' === $data_source ) : ?>
							<span class="status-icon status-green">🚀</span>
							<span class="connection-status-badge connected"><?php esc_html_e( 'Live KV Data', '365i-ai-faq-generator' ); ?></span>
						<?php elseif ( 'kv_empty' === $data_source ) : ?>
							<span class="status-icon status-blue">📊</span>
							<span class="connection-status-badge ready"><?php esc_html_e( 'KV Ready (no data yet)', '365i-ai-faq-generator' ); ?></span>
						<?php elseif ( 'fallback' === $data_source ) : ?>
							<span class="status-icon status-orange">⚠</span>
							<span class="connection-status-badge pending"><?php esc_html_e( 'Fallback Mode', '365i-ai-faq-generator' ); ?></span>
						<?php else : ?>
							<span class="status-icon status-blue">📋</span>
							<span class="connection-status-badge ready"><?php esc_html_e( 'Demo Data', '365i-ai-faq-generator' ); ?></span>
						<?php endif; ?>
					</div>
					<div class="status-details">
						<small>
							<?php if ( 'kv_live' === $data_source ) : ?>
								<strong>Status:</strong> Analytics data is being fetched in real-time from Cloudflare KV storage.
							<?php elseif ( 'kv_empty' === $data_source ) : ?>
								<strong>Status:</strong> KV storage is connected and ready. Data will appear once your workers start processing requests.
							<?php elseif ( 'fallback' === $data_source ) : ?>
								<strong>Status:</strong> Unable to connect to KV storage. Check your API credentials in Settings.
							<?php else : ?>
								<strong>Status:</strong> Currently displaying demonstration data for interface preview.
							<?php endif; ?>
						</small>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

</div>

<?php
// Include footer partial.
require_once AI_FAQ_GEN_DIR . 'templates/partials/footer.php';
?>