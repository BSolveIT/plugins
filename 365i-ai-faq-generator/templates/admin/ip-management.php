<?php
/**
 * IP Management Admin Template
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

<div class="wrap ai-faq-ip-management">
	<h1><?php esc_html_e( 'IP Management', '365i-ai-faq-generator' ); ?></h1>
	
	<p class="description">
		<?php esc_html_e( 'Manage IP whitelist and blacklist for rate limiting. Whitelisted IPs bypass all rate limits, while blacklisted IPs are permanently blocked.', '365i-ai-faq-generator' ); ?>
	</p>

	<!-- Add IP Section -->
	<div class="ai-faq-admin-section">
		<h2><?php esc_html_e( 'Add IP Address', '365i-ai-faq-generator' ); ?></h2>
		
		<form id="add-ip-form" class="ai-faq-ip-form">
			<?php wp_nonce_field( 'ai_faq_rate_limit_nonce', 'ip_management_nonce' ); ?>
			
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="ip_address_input"><?php esc_html_e( 'IP Address', '365i-ai-faq-generator' ); ?></label>
						</th>
						<td>
							<input type="text" 
							       id="ip_address_input" 
							       name="ip_address" 
							       class="regular-text" 
							       placeholder="<?php esc_attr_e( 'e.g., 192.168.1.100', '365i-ai-faq-generator' ); ?>" 
							       required />
							<p class="description">
								<?php esc_html_e( 'Enter a valid IPv4 or IPv6 address.', '365i-ai-faq-generator' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="ip_reason_input"><?php esc_html_e( 'Reason', '365i-ai-faq-generator' ); ?></label>
						</th>
						<td>
							<textarea id="ip_reason_input" 
							          name="reason" 
							          rows="3" 
							          class="regular-text" 
							          placeholder="<?php esc_attr_e( 'Reason for adding this IP address...', '365i-ai-faq-generator' ); ?>"></textarea>
							<p class="description">
								<?php esc_html_e( 'Optional reason for adding this IP address to the list.', '365i-ai-faq-generator' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
			
			<div class="ip-management-actions">
				<button type="button" class="button button-primary" id="add-to-whitelist">
					<?php esc_html_e( 'Add to Whitelist', '365i-ai-faq-generator' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="add-to-blacklist">
					<?php esc_html_e( 'Add to Blacklist', '365i-ai-faq-generator' ); ?>
				</button>
			</div>
		</form>
	</div>

	<div class="ai-faq-ip-lists">
		<!-- Whitelist Section -->
		<div class="ai-faq-admin-section ai-faq-ip-list">
			<h2><?php esc_html_e( 'IP Whitelist', '365i-ai-faq-generator' ); ?></h2>
			
			<p class="description">
				<?php esc_html_e( 'These IP addresses bypass all rate limits and restrictions.', '365i-ai-faq-generator' ); ?>
			</p>
			
			<?php if ( ! empty( $whitelist_ips ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'IP Address', '365i-ai-faq-generator' ); ?></th>
							<th><?php esc_html_e( 'Reason', '365i-ai-faq-generator' ); ?></th>
							<th><?php esc_html_e( 'Added By', '365i-ai-faq-generator' ); ?></th>
							<th><?php esc_html_e( 'Date Added', '365i-ai-faq-generator' ); ?></th>
							<th><?php esc_html_e( 'Actions', '365i-ai-faq-generator' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $whitelist_ips as $entry ) : ?>
							<tr>
								<td><code><?php echo esc_html( $entry['ip'] ?? 'N/A' ); ?></code></td>
								<td><?php echo esc_html( $entry['reason'] ?? 'No reason provided' ); ?></td>
								<td><?php echo esc_html( $entry['added_by'] ?? 'Unknown' ); ?></td>
								<td><?php echo esc_html( $entry['added_date'] ?? 'Unknown' ); ?></td>
								<td>
									<button type="button" 
									        class="button button-small remove-ip" 
									        data-ip="<?php echo esc_attr( $entry['ip'] ?? '' ); ?>" 
									        data-list="whitelist">
										<?php esc_html_e( 'Remove', '365i-ai-faq-generator' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p class="no-items"><?php esc_html_e( 'No IP addresses in whitelist.', '365i-ai-faq-generator' ); ?></p>
			<?php endif; ?>
		</div>

		<!-- Blacklist Section -->
		<div class="ai-faq-admin-section ai-faq-ip-list">
			<h2><?php esc_html_e( 'IP Blacklist', '365i-ai-faq-generator' ); ?></h2>
			
			<p class="description">
				<?php esc_html_e( 'These IP addresses are permanently blocked from accessing all workers.', '365i-ai-faq-generator' ); ?>
			</p>
			
			<?php if ( ! empty( $blacklist_ips ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'IP Address', '365i-ai-faq-generator' ); ?></th>
							<th><?php esc_html_e( 'Reason', '365i-ai-faq-generator' ); ?></th>
							<th><?php esc_html_e( 'Added By', '365i-ai-faq-generator' ); ?></th>
							<th><?php esc_html_e( 'Date Added', '365i-ai-faq-generator' ); ?></th>
							<th><?php esc_html_e( 'Actions', '365i-ai-faq-generator' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $blacklist_ips as $entry ) : ?>
							<tr>
								<td><code><?php echo esc_html( $entry['ip'] ?? 'N/A' ); ?></code></td>
								<td><?php echo esc_html( $entry['reason'] ?? 'No reason provided' ); ?></td>
								<td><?php echo esc_html( $entry['added_by'] ?? 'Unknown' ); ?></td>
								<td><?php echo esc_html( $entry['added_date'] ?? 'Unknown' ); ?></td>
								<td>
									<button type="button" 
									        class="button button-small remove-ip" 
									        data-ip="<?php echo esc_attr( $entry['ip'] ?? '' ); ?>" 
									        data-list="blacklist">
										<?php esc_html_e( 'Remove', '365i-ai-faq-generator' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p class="no-items"><?php esc_html_e( 'No IP addresses in blacklist.', '365i-ai-faq-generator' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<!-- Status Overview -->
	<div class="ai-faq-admin-section">
		<h2><?php esc_html_e( 'IP Management Status', '365i-ai-faq-generator' ); ?></h2>
		
		<div class="ai-faq-status-grid">
			<div class="status-card">
				<h3><?php esc_html_e( 'Whitelist Status', '365i-ai-faq-generator' ); ?></h3>
				<div class="status-indicator">
					<span class="status-blue"><?php echo esc_html( count( $whitelist_ips ) ); ?></span>
					<?php esc_html_e( 'trusted IPs', '365i-ai-faq-generator' ); ?>
				</div>
			</div>
			
			<div class="status-card">
				<h3><?php esc_html_e( 'Blacklist Status', '365i-ai-faq-generator' ); ?></h3>
				<div class="status-indicator">
					<span class="status-red"><?php echo esc_html( count( $blacklist_ips ) ); ?></span>
					<?php esc_html_e( 'blocked IPs', '365i-ai-faq-generator' ); ?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
// Include footer partial.
require_once AI_FAQ_GEN_DIR . 'templates/partials/footer.php';
?>