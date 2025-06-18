<?php
/**
 * Rate Limiting Configuration Admin Template
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

<div class="wrap ai-faq-rate-limiting">
	<h1><?php esc_html_e( 'Rate Limiting Configuration', '365i-ai-faq-generator' ); ?></h1>
	
	<p class="description">
		<?php esc_html_e( 'Configure rate limits for each Cloudflare Worker to control AI usage and prevent abuse. Changes are applied immediately to all workers.', '365i-ai-faq-generator' ); ?>
	</p>

	<!-- Global Settings Section -->
	<div class="ai-faq-admin-section">
		<h2><?php esc_html_e( 'Global Settings', '365i-ai-faq-generator' ); ?></h2>
		
		<form class="ai-faq-global-settings-form" id="global-settings-form">
			<?php wp_nonce_field( 'ai_faq_rate_limit_nonce', 'global_settings_nonce' ); ?>
			
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="global_enable_rate_limiting"><?php esc_html_e( 'Enable Rate Limiting', '365i-ai-faq-generator' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox"
								       id="global_enable_rate_limiting"
								       name="enableRateLimiting"
								       value="1"
								       <?php checked( $global_settings['enableRateLimiting'] ?? true ); ?> />
								<?php esc_html_e( 'Enable enhanced rate limiting across all workers', '365i-ai-faq-generator' ); ?>
							</label>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="global_enable_ip_whitelist"><?php esc_html_e( 'Enable IP Whitelist', '365i-ai-faq-generator' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox"
								       id="global_enable_ip_whitelist"
								       name="enableIPWhitelist"
								       value="1"
								       <?php checked( $global_settings['enableIPWhitelist'] ?? true ); ?> />
								<?php esc_html_e( 'Allow trusted IPs to bypass rate limits', '365i-ai-faq-generator' ); ?>
							</label>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="global_enable_ip_blacklist"><?php esc_html_e( 'Enable IP Blacklist', '365i-ai-faq-generator' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox"
								       id="global_enable_ip_blacklist"
								       name="enableIPBlacklist"
								       value="1"
								       <?php checked( $global_settings['enableIPBlacklist'] ?? true ); ?> />
								<?php esc_html_e( 'Block banned IPs from accessing workers', '365i-ai-faq-generator' ); ?>
							</label>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="global_admin_notification_email"><?php esc_html_e( 'Notification Email', '365i-ai-faq-generator' ); ?></label>
						</th>
						<td>
							<input type="email"
							       id="global_admin_notification_email"
							       name="adminNotificationEmail"
							       value="<?php echo esc_attr( $global_settings['adminNotificationEmail'] ?? get_option( 'admin_email' ) ); ?>"
							       class="regular-text" />
							<p class="description">
								<?php esc_html_e( 'Email address to receive notifications about rate limiting violations and blocks.', '365i-ai-faq-generator' ); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="global_notify_on_violations"><?php esc_html_e( 'Notify on Violations', '365i-ai-faq-generator' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox"
								       id="global_notify_on_violations"
								       name="notifyOnViolations"
								       value="1"
								       <?php checked( $global_settings['notifyOnViolations'] ?? true ); ?> />
								<?php esc_html_e( 'Send email notifications when rate limit violations occur', '365i-ai-faq-generator' ); ?>
							</label>
						</td>
					</tr>
				</tbody>
			</table>
			
			<div class="global-settings-actions">
				<button type="submit" class="button button-primary save-global-settings" id="global_settings_submit">
					<?php esc_html_e( 'Save Global Settings', '365i-ai-faq-generator' ); ?>
				</button>
			</div>
		</form>
	</div>

	<!-- Worker-Specific Rate Limits Section -->
	<div class="ai-faq-admin-section">
		<h2><?php esc_html_e( 'Worker-Specific Rate Limits', '365i-ai-faq-generator' ); ?></h2>
		
		<p class="description">
			<?php esc_html_e( 'Configure individual rate limits for each AI worker. These limits control how often users can access each specific service.', '365i-ai-faq-generator' ); ?>
		</p>

		<div class="ai-faq-workers-grid">
			<?php foreach ( $this->workers as $worker_key => $worker_name ) : ?>
				<?php
				$config = $current_configs[ $worker_key ] ?? $this->get_default_worker_config( $worker_key );
				?>
				<div class="ai-faq-worker-card" data-worker="<?php echo esc_attr( $worker_key ); ?>">
					<div class="worker-header">
						<h3><?php echo esc_html( $worker_name ); ?></h3>
						<span class="config-source <?php echo esc_attr( $config['source'] ?? 'default' ); ?>">
							<?php
							$source_text = $config['source'] === 'custom' ? __( 'Custom', '365i-ai-faq-generator' ) : __( 'Default', '365i-ai-faq-generator' );
							echo esc_html( $source_text );
							?>
						</span>
					</div>
					
					<form class="worker-config-form" data-worker="<?php echo esc_attr( $worker_key ); ?>">
						<?php wp_nonce_field( 'ai_faq_rate_limit_nonce', $worker_key . '_nonce' ); ?>
						
						<div class="limits-grid">
							<div class="limit-field">
								<label for="<?php echo esc_attr( $worker_key ); ?>_hourly">
									<?php esc_html_e( 'Hourly Limit', '365i-ai-faq-generator' ); ?>
								</label>
								<input type="number"
								       id="<?php echo esc_attr( $worker_key ); ?>_hourly"
								       name="hourlyLimit"
								       value="<?php echo esc_attr( $config['hourlyLimit'] ?? 10 ); ?>"
								       min="1"
								       max="1000"
								       class="small-text" />
							</div>
							
							<div class="limit-field">
								<label for="<?php echo esc_attr( $worker_key ); ?>_daily">
									<?php esc_html_e( 'Daily Limit', '365i-ai-faq-generator' ); ?>
								</label>
								<input type="number"
								       id="<?php echo esc_attr( $worker_key ); ?>_daily"
								       name="dailyLimit"
								       value="<?php echo esc_attr( $config['dailyLimit'] ?? 50 ); ?>"
								       min="1"
								       max="10000"
								       class="small-text" />
							</div>
							
							<div class="limit-field">
								<label for="<?php echo esc_attr( $worker_key ); ?>_weekly">
									<?php esc_html_e( 'Weekly Limit', '365i-ai-faq-generator' ); ?>
								</label>
								<input type="number"
								       id="<?php echo esc_attr( $worker_key ); ?>_weekly"
								       name="weeklyLimit"
								       value="<?php echo esc_attr( $config['weeklyLimit'] ?? 250 ); ?>"
								       min="1"
								       max="50000"
								       class="small-text" />
							</div>
							
							<div class="limit-field">
								<label for="<?php echo esc_attr( $worker_key ); ?>_monthly">
									<?php esc_html_e( 'Monthly Limit', '365i-ai-faq-generator' ); ?>
								</label>
								<input type="number"
								       id="<?php echo esc_attr( $worker_key ); ?>_monthly"
								       name="monthlyLimit"
								       value="<?php echo esc_attr( $config['monthlyLimit'] ?? 1000 ); ?>"
								       min="1"
								       max="200000"
								       class="small-text" />
							</div>
						</div>
						
						<div class="violation-thresholds">
							<h4><?php esc_html_e( 'Violation Thresholds', '365i-ai-faq-generator' ); ?></h4>
							<div class="thresholds-grid">
								<div class="threshold-field">
									<label for="<?php echo esc_attr( $worker_key ); ?>_soft">
										<?php esc_html_e( 'Soft Warning', '365i-ai-faq-generator' ); ?>
									</label>
									<input type="number"
									       id="<?php echo esc_attr( $worker_key ); ?>_soft"
									       name="violationThresholds[soft]"
									       value="<?php echo esc_attr( $config['violationThresholds']['soft'] ?? 3 ); ?>"
									       min="1"
									       max="20"
									       class="small-text" />
								</div>
								
								<div class="threshold-field">
									<label for="<?php echo esc_attr( $worker_key ); ?>_hard">
										<?php esc_html_e( 'Hard Block', '365i-ai-faq-generator' ); ?>
									</label>
									<input type="number"
									       id="<?php echo esc_attr( $worker_key ); ?>_hard"
									       name="violationThresholds[hard]"
									       value="<?php echo esc_attr( $config['violationThresholds']['hard'] ?? 6 ); ?>"
									       min="1"
									       max="50"
									       class="small-text" />
								</div>
								
								<div class="threshold-field">
									<label for="<?php echo esc_attr( $worker_key ); ?>_ban">
										<?php esc_html_e( 'Permanent Ban', '365i-ai-faq-generator' ); ?>
									</label>
									<input type="number"
									       id="<?php echo esc_attr( $worker_key ); ?>_ban"
									       name="violationThresholds[ban]"
									       value="<?php echo esc_attr( $config['violationThresholds']['ban'] ?? 12 ); ?>"
									       min="1"
									       max="100"
									       class="small-text" />
								</div>
							</div>
						</div>
						
						<div class="worker-actions">
							<button type="submit" class="button button-primary save-worker-config" id="<?php echo esc_attr( $worker_key ); ?>_submit">
								<?php esc_html_e( 'Save Configuration', '365i-ai-faq-generator' ); ?>
							</button>
							<button type="button" class="button reset-worker-config" data-worker="<?php echo esc_attr( $worker_key ); ?>" id="<?php echo esc_attr( $worker_key ); ?>_reset">
								<?php esc_html_e( 'Reset to Defaults', '365i-ai-faq-generator' ); ?>
							</button>
						</div>
						
						<?php if ( ! empty( $config['lastUpdated'] ) ) : ?>
							<div class="config-meta">
								<small>
									<?php
									printf(
										/* translators: 1: Last updated date, 2: Updated by user */
										esc_html__( 'Last updated: %1$s by %2$s', '365i-ai-faq-generator' ),
										esc_html( $config['lastUpdated'] ),
										esc_html( $config['updatedBy'] ?? 'system' )
									);
									?>
								</small>
							</div>
						<?php endif; ?>
					</form>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Status Overview -->
	<div class="ai-faq-admin-section">
		<h2><?php esc_html_e( 'Status Overview', '365i-ai-faq-generator' ); ?></h2>
		
		<div class="ai-faq-status-grid">
			<div class="status-card">
				<h3><?php esc_html_e( 'Configuration Status', '365i-ai-faq-generator' ); ?></h3>
				<div class="status-indicator">
					<?php
					$options = get_option( 'ai_faq_gen_options', array() );
					$api_configured = ! empty( $options['cloudflare_api_token'] ) && ! empty( $options['cloudflare_account_id'] );
					?>
					<?php if ( $api_configured ) : ?>
						<span class="status-green">✓</span>
						<?php esc_html_e( 'Cloudflare API configured', '365i-ai-faq-generator' ); ?>
					<?php else : ?>
						<span class="status-red">✗</span>
						<?php esc_html_e( 'Cloudflare API not configured', '365i-ai-faq-generator' ); ?>
						<br><small><a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-faq-generator-settings' ) ); ?>"><?php esc_html_e( 'Configure in Settings', '365i-ai-faq-generator' ); ?></a></small>
					<?php endif; ?>
				</div>
			</div>
			
			<div class="status-card">
				<h3><?php esc_html_e( 'Active Workers', '365i-ai-faq-generator' ); ?></h3>
				<div class="status-indicator">
					<span class="status-blue"><?php echo esc_html( count( $this->workers ) ); ?></span>
					<?php esc_html_e( 'workers configured', '365i-ai-faq-generator' ); ?>
				</div>
			</div>
			
			<div class="status-card">
				<h3><?php esc_html_e( 'Rate Limiting', '365i-ai-faq-generator' ); ?></h3>
				<div class="status-indicator">
					<?php if ( $global_settings['enableRateLimiting'] ?? true ) : ?>
						<span class="status-green">✓</span>
						<?php esc_html_e( 'Active', '365i-ai-faq-generator' ); ?>
					<?php else : ?>
						<span class="status-orange">⚠</span>
						<?php esc_html_e( 'Disabled', '365i-ai-faq-generator' ); ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<!-- Help & Documentation -->
	<div class="ai-faq-admin-section">
		<h2>
			<span class="dashicons dashicons-editor-help"></span>
			<?php esc_html_e( 'Help & Documentation', '365i-ai-faq-generator' ); ?>
		</h2>
		
		<div class="help-grid">
			<div class="help-card">
				<h4><?php esc_html_e( 'Worker Setup Guide', '365i-ai-faq-generator' ); ?></h4>
				<p><?php esc_html_e( 'Learn how to configure your Cloudflare workers and KV namespaces for optimal performance. This guide walks you through the complete setup process with step-by-step instructions.', '365i-ai-faq-generator' ); ?></p>
				<button type="button" class="button button-secondary ai-faq-doc-button" data-doc-type="setup_guide">
					<span class="dashicons dashicons-media-text"></span>
					<?php esc_html_e( 'View Guide', '365i-ai-faq-generator' ); ?>
				</button>
			</div>
			
			<div class="help-card">
				<h4><?php esc_html_e( 'Troubleshooting', '365i-ai-faq-generator' ); ?></h4>
				<p><?php esc_html_e( 'Common issues and solutions for worker connectivity problems. Find answers to frequently encountered setup issues and learn how to diagnose connection failures.', '365i-ai-faq-generator' ); ?></p>
				<button type="button" class="button button-secondary ai-faq-doc-button" data-doc-type="troubleshooting">
					<span class="dashicons dashicons-sos"></span>
					<?php esc_html_e( 'Get Help', '365i-ai-faq-generator' ); ?>
				</button>
			</div>
			
			<div class="help-card">
				<h4><?php esc_html_e( 'API Reference', '365i-ai-faq-generator' ); ?></h4>
				<p><?php esc_html_e( 'Complete API documentation for all worker endpoints and parameters. This technical reference provides detailed information about request formats, response structures, and authentication.', '365i-ai-faq-generator' ); ?></p>
				<button type="button" class="button button-secondary ai-faq-doc-button" data-doc-type="api_reference">
					<span class="dashicons dashicons-editor-code"></span>
					<?php esc_html_e( 'View API Docs', '365i-ai-faq-generator' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>

<?php
// Include footer partial.
require_once AI_FAQ_GEN_DIR . 'templates/partials/footer.php';
?>