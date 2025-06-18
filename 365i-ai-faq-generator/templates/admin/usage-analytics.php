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
					<span class="metric-value"><?php echo esc_html( $analytics_data['total_requests'] ?? 0 ); ?></span>
					<span class="metric-label"><?php esc_html_e( 'requests', '365i-ai-faq-generator' ); ?></span>
				</div>
			</div>
			
			<div class="analytics-card">
				<h3><?php esc_html_e( 'Blocked Requests', '365i-ai-faq-generator' ); ?></h3>
				<div class="analytics-metric">
					<span class="metric-value blocked"><?php echo esc_html( $analytics_data['blocked_requests'] ?? 0 ); ?></span>
					<span class="metric-label"><?php esc_html_e( 'blocked', '365i-ai-faq-generator' ); ?></span>
				</div>
			</div>
			
			<div class="analytics-card">
				<h3><?php esc_html_e( 'Violations', '365i-ai-faq-generator' ); ?></h3>
				<div class="analytics-metric">
					<span class="metric-value violations"><?php echo esc_html( $analytics_data['violations'] ?? 0 ); ?></span>
					<span class="metric-label"><?php esc_html_e( 'violations', '365i-ai-faq-generator' ); ?></span>
				</div>
			</div>
			
			<div class="analytics-card">
				<h3><?php esc_html_e( 'Unique IPs', '365i-ai-faq-generator' ); ?></h3>
				<div class="analytics-metric">
					<span class="metric-value"><?php echo esc_html( $analytics_data['unique_ips'] ?? 0 ); ?></span>
					<span class="metric-label"><?php esc_html_e( 'unique IPs', '365i-ai-faq-generator' ); ?></span>
				</div>
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

</div>

<?php
// Include footer partial.
require_once AI_FAQ_GEN_DIR . 'templates/partials/footer.php';
?>