<?php
/**
 * Settings admin template for 365i AI FAQ Generator.
 * 
 * This template displays the plugin settings interface with
 * configuration options for API keys, defaults, and preferences.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Templates
 * @since 2.0.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include header.
include AI_FAQ_GEN_DIR . 'templates/partials/header.php';

// Get plugin options.
$options = get_option( 'ai_faq_gen_options', array() );

// Default values.
$cloudflare_account_id = isset( $options['cloudflare_account_id'] ) ? $options['cloudflare_account_id'] : '';
$cloudflare_api_token = isset( $options['cloudflare_api_token'] ) ? $options['cloudflare_api_token'] : '';
$default_tone = isset( $options['default_tone'] ) ? $options['default_tone'] : 'professional';
$default_length = isset( $options['default_length'] ) ? $options['default_length'] : 'medium';
$default_schema_type = isset( $options['default_schema_type'] ) ? $options['default_schema_type'] : 'json-ld';
$enable_auto_schema = isset( $options['enable_auto_schema'] ) ? $options['enable_auto_schema'] : true;
$enable_seo_optimization = isset( $options['enable_seo_optimization'] ) ? $options['enable_seo_optimization'] : true;
$enable_rate_limiting = isset( $options['enable_rate_limiting'] ) ? $options['enable_rate_limiting'] : true;
$enable_caching = isset( $options['enable_caching'] ) ? $options['enable_caching'] : true;
$cache_duration = isset( $options['cache_duration'] ) ? $options['cache_duration'] : 3600;
$max_questions_per_batch = isset( $options['max_questions_per_batch'] ) ? $options['max_questions_per_batch'] : 10;
$enable_logging = isset( $options['enable_logging'] ) ? $options['enable_logging'] : false;
$log_level = isset( $options['log_level'] ) ? $options['log_level'] : 'error';
$enable_analytics = isset( $options['enable_analytics'] ) ? $options['enable_analytics'] : true;
?>

<div class="ai-faq-gen-settings">
	
	<!-- Settings Form -->
	<form id="settings-form" method="post" action="">
		<?php wp_nonce_field( 'ai_faq_gen_save_settings', '_wpnonce' ); ?>
		
		<!-- API Configuration -->
		<div class="ai-faq-gen-section api-config-section">
			<h3>
				<span class="dashicons dashicons-admin-network"></span>
				<?php esc_html_e( 'API Configuration', '365i-ai-faq-generator' ); ?>
			</h3>
			
			<p><?php esc_html_e( 'Configure your Cloudflare account credentials for worker access.', '365i-ai-faq-generator' ); ?></p>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="cloudflare_account_id"><?php esc_html_e( 'Cloudflare Account ID', '365i-ai-faq-generator' ); ?></label>
					</th>
					<td>
						<input type="text" id="cloudflare_account_id" name="cloudflare_account_id" value="<?php echo esc_attr( $cloudflare_account_id ); ?>" class="regular-text" placeholder="32-character account ID">
						<p class="description">
							<?php esc_html_e( 'Your Cloudflare account ID. Found in the right sidebar of your Cloudflare dashboard.', '365i-ai-faq-generator' ); ?>
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="cloudflare_api_token"><?php esc_html_e( 'Cloudflare API Token', '365i-ai-faq-generator' ); ?></label>
					</th>
					<td>
						<input type="password" id="cloudflare_api_token" name="cloudflare_api_token" value="<?php echo esc_attr( $cloudflare_api_token ); ?>" class="regular-text" placeholder="Your API token">
						<button type="button" class="button button-secondary toggle-password" data-target="cloudflare_api_token">
							<span class="dashicons dashicons-visibility"></span>
						</button>
						<p class="description">
							<?php esc_html_e( 'API token with Workers:Edit and Account:Read permissions.', '365i-ai-faq-generator' ); ?>
							<a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank"><?php esc_html_e( 'Create token', '365i-ai-faq-generator' ); ?></a>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<!-- Default Generation Settings -->
		<div class="ai-faq-gen-section default-settings-section">
			<h3>
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e( 'Default Generation Settings', '365i-ai-faq-generator' ); ?>
			</h3>
			
			<p><?php esc_html_e( 'Set default preferences for FAQ generation that will be used across the plugin.', '365i-ai-faq-generator' ); ?></p>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="default_tone"><?php esc_html_e( 'Default Tone', '365i-ai-faq-generator' ); ?></label>
					</th>
					<td>
						<select id="default_tone" name="default_tone">
							<option value="professional" <?php selected( $default_tone, 'professional' ); ?>><?php esc_html_e( 'Professional', '365i-ai-faq-generator' ); ?></option>
							<option value="friendly" <?php selected( $default_tone, 'friendly' ); ?>><?php esc_html_e( 'Friendly', '365i-ai-faq-generator' ); ?></option>
							<option value="casual" <?php selected( $default_tone, 'casual' ); ?>><?php esc_html_e( 'Casual', '365i-ai-faq-generator' ); ?></option>
							<option value="technical" <?php selected( $default_tone, 'technical' ); ?>><?php esc_html_e( 'Technical', '365i-ai-faq-generator' ); ?></option>
							<option value="conversational" <?php selected( $default_tone, 'conversational' ); ?>><?php esc_html_e( 'Conversational', '365i-ai-faq-generator' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Default tone for generated FAQ content.', '365i-ai-faq-generator' ); ?></p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="default_length"><?php esc_html_e( 'Default Length', '365i-ai-faq-generator' ); ?></label>
					</th>
					<td>
						<select id="default_length" name="default_length">
							<option value="short" <?php selected( $default_length, 'short' ); ?>><?php esc_html_e( 'Short (1-2 sentences)', '365i-ai-faq-generator' ); ?></option>
							<option value="medium" <?php selected( $default_length, 'medium' ); ?>><?php esc_html_e( 'Medium (2-4 sentences)', '365i-ai-faq-generator' ); ?></option>
							<option value="long" <?php selected( $default_length, 'long' ); ?>><?php esc_html_e( 'Long (4+ sentences)', '365i-ai-faq-generator' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Default length for generated answers.', '365i-ai-faq-generator' ); ?></p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="max_questions_per_batch"><?php esc_html_e( 'Max Questions per Batch', '365i-ai-faq-generator' ); ?></label>
					</th>
					<td>
						<input type="number" id="max_questions_per_batch" name="max_questions_per_batch" value="<?php echo esc_attr( $max_questions_per_batch ); ?>" min="1" max="50" class="small-text">
						<p class="description"><?php esc_html_e( 'Maximum number of questions to generate in a single batch (1-50).', '365i-ai-faq-generator' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<!-- Schema & SEO Settings -->
		<div class="ai-faq-gen-section schema-seo-section">
			<h3>
				<span class="dashicons dashicons-search"></span>
				<?php esc_html_e( 'Schema & SEO Settings', '365i-ai-faq-generator' ); ?>
			</h3>
			
			<p><?php esc_html_e( 'Configure schema markup and SEO optimization preferences.', '365i-ai-faq-generator' ); ?></p>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="default_schema_type"><?php esc_html_e( 'Default Schema Type', '365i-ai-faq-generator' ); ?></label>
					</th>
					<td>
						<select id="default_schema_type" name="default_schema_type">
							<option value="json-ld" <?php selected( $default_schema_type, 'json-ld' ); ?>><?php esc_html_e( 'JSON-LD (Recommended)', '365i-ai-faq-generator' ); ?></option>
							<option value="microdata" <?php selected( $default_schema_type, 'microdata' ); ?>><?php esc_html_e( 'Microdata', '365i-ai-faq-generator' ); ?></option>
							<option value="rdfa" <?php selected( $default_schema_type, 'rdfa' ); ?>><?php esc_html_e( 'RDFa', '365i-ai-faq-generator' ); ?></option>
							<option value="html" <?php selected( $default_schema_type, 'html' ); ?>><?php esc_html_e( 'Plain HTML', '365i-ai-faq-generator' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Default schema markup format for FAQ content.', '365i-ai-faq-generator' ); ?></p>
					</td>
				</tr>
				
				<tr>
					<th scope="row"><?php esc_html_e( 'Automatic Schema', '365i-ai-faq-generator' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enable_auto_schema" value="1" <?php checked( $enable_auto_schema ); ?>>
							<?php esc_html_e( 'Automatically add schema markup to generated FAQs', '365i-ai-faq-generator' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'When enabled, schema markup will be automatically added to FAQ content.', '365i-ai-faq-generator' ); ?></p>
					</td>
				</tr>
				
				<tr>
					<th scope="row"><?php esc_html_e( 'SEO Optimization', '365i-ai-faq-generator' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enable_seo_optimization" value="1" <?php checked( $enable_seo_optimization ); ?>>
							<?php esc_html_e( 'Enable SEO optimization and Position Zero targeting', '365i-ai-faq-generator' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Optimize FAQ content for search engines and featured snippets.', '365i-ai-faq-generator' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<!-- Performance Settings -->
		<div class="ai-faq-gen-section performance-section">
			<h3>
				<span class="dashicons dashicons-performance"></span>
				<?php esc_html_e( 'Performance Settings', '365i-ai-faq-generator' ); ?>
			</h3>
			
			<p><?php esc_html_e( 'Configure caching, rate limiting, and performance optimization settings.', '365i-ai-faq-generator' ); ?></p>
			
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Rate Limiting', '365i-ai-faq-generator' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enable_rate_limiting" value="1" <?php checked( $enable_rate_limiting ); ?>>
							<?php esc_html_e( 'Enable rate limiting for worker requests', '365i-ai-faq-generator' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Prevent excessive API calls and manage usage limits.', '365i-ai-faq-generator' ); ?></p>
					</td>
				</tr>
				
				<tr>
					<th scope="row"><?php esc_html_e( 'Caching', '365i-ai-faq-generator' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enable_caching" value="1" <?php checked( $enable_caching ); ?>>
							<?php esc_html_e( 'Enable caching for generated content', '365i-ai-faq-generator' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Cache generated FAQs to improve performance and reduce API calls.', '365i-ai-faq-generator' ); ?></p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="cache_duration"><?php esc_html_e( 'Cache Duration (seconds)', '365i-ai-faq-generator' ); ?></label>
					</th>
					<td>
						<input type="number" id="cache_duration" name="cache_duration" value="<?php echo esc_attr( $cache_duration ); ?>" min="300" max="86400" class="regular-text">
						<p class="description"><?php esc_html_e( 'How long to cache generated content (300-86400 seconds).', '365i-ai-faq-generator' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<!-- Logging & Analytics -->
		<div class="ai-faq-gen-section logging-section">
			<h3>
				<span class="dashicons dashicons-chart-line"></span>
				<?php esc_html_e( 'Logging & Analytics', '365i-ai-faq-generator' ); ?>
			</h3>
			
			<p><?php esc_html_e( 'Configure logging and analytics settings for debugging and monitoring.', '365i-ai-faq-generator' ); ?></p>
			
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Logging', '365i-ai-faq-generator' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enable_logging" value="1" <?php checked( $enable_logging ); ?>>
							<?php esc_html_e( 'Enable detailed logging for debugging', '365i-ai-faq-generator' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Log plugin activities for troubleshooting. Disable in production for better performance.', '365i-ai-faq-generator' ); ?></p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="log_level"><?php esc_html_e( 'Log Level', '365i-ai-faq-generator' ); ?></label>
					</th>
					<td>
						<select id="log_level" name="log_level">
							<option value="error" <?php selected( $log_level, 'error' ); ?>><?php esc_html_e( 'Error Only', '365i-ai-faq-generator' ); ?></option>
							<option value="warning" <?php selected( $log_level, 'warning' ); ?>><?php esc_html_e( 'Warning & Error', '365i-ai-faq-generator' ); ?></option>
							<option value="info" <?php selected( $log_level, 'info' ); ?>><?php esc_html_e( 'Info, Warning & Error', '365i-ai-faq-generator' ); ?></option>
							<option value="debug" <?php selected( $log_level, 'debug' ); ?>><?php esc_html_e( 'All (Debug Mode)', '365i-ai-faq-generator' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Level of detail for logged information.', '365i-ai-faq-generator' ); ?></p>
					</td>
				</tr>
				
				<tr>
					<th scope="row"><?php esc_html_e( 'Analytics', '365i-ai-faq-generator' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="enable_analytics" value="1" <?php checked( $enable_analytics ); ?>>
							<?php esc_html_e( 'Enable usage analytics and statistics', '365i-ai-faq-generator' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Track usage statistics for performance monitoring. No personal data is collected.', '365i-ai-faq-generator' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<!-- Form Actions -->
		<div class="form-actions">
			<button type="submit" class="button button-primary">
				<span class="dashicons dashicons-yes"></span>
				<?php esc_html_e( 'Save Settings', '365i-ai-faq-generator' ); ?>
			</button>
			
			<button type="button" class="button button-secondary test-api-connection">
				<span class="dashicons dashicons-admin-network"></span>
				<?php esc_html_e( 'Test API Connection', '365i-ai-faq-generator' ); ?>
			</button>
			
			<button type="button" class="button button-secondary reset-settings">
				<span class="dashicons dashicons-undo"></span>
				<?php esc_html_e( 'Reset to Defaults', '365i-ai-faq-generator' ); ?>
			</button>
			
			<button type="button" class="button button-secondary export-settings">
				<span class="dashicons dashicons-download"></span>
				<?php esc_html_e( 'Export Settings', '365i-ai-faq-generator' ); ?>
			</button>
			
			<button type="button" class="button button-secondary import-settings">
				<span class="dashicons dashicons-upload"></span>
				<?php esc_html_e( 'Import Settings', '365i-ai-faq-generator' ); ?>
			</button>
		</div>
	</form>

	<!-- Import Settings Modal -->
	<div id="import-settings-modal" class="import-modal" style="display: none;">
		<div class="modal-content">
			<h3><?php esc_html_e( 'Import Settings', '365i-ai-faq-generator' ); ?></h3>
			<form id="import-form" enctype="multipart/form-data">
				<?php wp_nonce_field( 'ai_faq_gen_import_settings', '_import_nonce' ); ?>
				<p><?php esc_html_e( 'Select a settings file to import:', '365i-ai-faq-generator' ); ?></p>
				<input type="file" name="settings_file" accept=".json" required>
				<div class="modal-actions">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Import', '365i-ai-faq-generator' ); ?></button>
					<button type="button" class="button button-secondary close-modal"><?php esc_html_e( 'Cancel', '365i-ai-faq-generator' ); ?></button>
				</div>
			</form>
		</div>
	</div>

</div><!-- .ai-faq-gen-settings -->

<?php
// Include footer.
include AI_FAQ_GEN_DIR . 'templates/partials/footer.php';
?>