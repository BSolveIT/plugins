<?php
/**
 * Settings Page Template
 *
 * This file is used to markup the admin-facing settings page.
 *
 * @package Quick_FAQ_Markup
 * @since 1.0.0
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Get current settings
$settings = get_option( 'quick_faq_markup_settings', array() );
$defaults = array(
	'default_style'  => 'classic',
	'show_anchors'   => true,
	'enable_schema'  => true,
	'cache_enabled'  => true,
	'cache_duration' => HOUR_IN_SECONDS,
);
$settings = wp_parse_args( $settings, $defaults );
?>

<div class="wrap qfm-settings-page">
	<h1><?php esc_html_e( 'Quick FAQ Markup Settings', 'quick-faq-markup' ); ?></h1>
	
	<?php
	// Display admin notices
	if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'Settings saved successfully!', 'quick-faq-markup' )
		);
	}
	?>
	
	<form method="post" action="options.php">
		<?php
		settings_fields( 'quick_faq_markup_settings' );
		do_settings_sections( 'quick_faq_markup_settings' );
		?>
		
		<div class="qfm-settings-section">
			<h3><?php esc_html_e( 'Display Settings', 'quick-faq-markup' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="qfm_default_style"><?php esc_html_e( 'Default Style', 'quick-faq-markup' ); ?></label>
					</th>
					<td>
						<select id="qfm_default_style" name="quick_faq_markup_settings[default_style]">
							<option value="classic" <?php selected( $settings['default_style'], 'classic' ); ?>>
								<?php esc_html_e( 'Classic', 'quick-faq-markup' ); ?>
							</option>
							<option value="accordion-modern" <?php selected( $settings['default_style'], 'accordion-modern' ); ?>>
								<?php esc_html_e( 'Accordion Modern', 'quick-faq-markup' ); ?>
							</option>
							<option value="accordion-minimal" <?php selected( $settings['default_style'], 'accordion-minimal' ); ?>>
								<?php esc_html_e( 'Accordion Minimal', 'quick-faq-markup' ); ?>
							</option>
							<option value="cards" <?php selected( $settings['default_style'], 'cards' ); ?>>
								<?php esc_html_e( 'Cards', 'quick-faq-markup' ); ?>
							</option>
						</select>
						<span class="qfm-style-preview">
							<?php esc_html_e( 'Preview available in Phase 3', 'quick-faq-markup' ); ?>
						</span>
						<p class="description">
							<?php esc_html_e( 'Choose the default display style for FAQ shortcodes when no style is specified.', 'quick-faq-markup' ); ?>
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="qfm_show_anchors"><?php esc_html_e( 'Show Anchor Links', 'quick-faq-markup' ); ?></label>
					</th>
					<td>
						<input 
							type="checkbox" 
							id="qfm_show_anchors" 
							name="quick_faq_markup_settings[show_anchors]" 
							value="1" 
							<?php checked( $settings['show_anchors'], true ); ?>
						/>
						<label for="qfm_show_anchors">
							<?php esc_html_e( 'Display anchor links for direct FAQ linking', 'quick-faq-markup' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'When enabled, each FAQ will have a permalink anchor for direct linking and sharing.', 'quick-faq-markup' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>
		
		<div class="qfm-settings-section">
			<h3><?php esc_html_e( 'SEO Settings', 'quick-faq-markup' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="qfm_enable_schema"><?php esc_html_e( 'Enable Schema Markup', 'quick-faq-markup' ); ?></label>
					</th>
					<td>
						<input 
							type="checkbox" 
							id="qfm_enable_schema" 
							name="quick_faq_markup_settings[enable_schema]" 
							value="1" 
							<?php checked( $settings['enable_schema'], true ); ?>
						/>
						<label for="qfm_enable_schema">
							<?php esc_html_e( 'Generate JSON-LD schema markup for better SEO', 'quick-faq-markup' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Enables structured data markup that helps search engines understand your FAQ content. This can improve your chances of appearing in rich results.', 'quick-faq-markup' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>
		
		<div class="qfm-settings-section">
			<h3><?php esc_html_e( 'Performance Settings', 'quick-faq-markup' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="qfm_cache_enabled"><?php esc_html_e( 'Enable Caching', 'quick-faq-markup' ); ?></label>
					</th>
					<td>
						<input 
							type="checkbox" 
							id="qfm_cache_enabled" 
							name="quick_faq_markup_settings[cache_enabled]" 
							value="1" 
							<?php checked( $settings['cache_enabled'], true ); ?>
						/>
						<label for="qfm_cache_enabled">
							<?php esc_html_e( 'Cache FAQ queries for improved performance', 'quick-faq-markup' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Caches FAQ database queries to reduce server load and improve page loading times.', 'quick-faq-markup' ); ?>
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="qfm_cache_duration"><?php esc_html_e( 'Cache Duration', 'quick-faq-markup' ); ?></label>
					</th>
					<td>
						<input 
							type="number" 
							id="qfm_cache_duration" 
							name="quick_faq_markup_settings[cache_duration]" 
							value="<?php echo esc_attr( $settings['cache_duration'] ); ?>" 
							min="300" 
							step="300" 
							class="small-text"
						/>
						<span><?php esc_html_e( 'seconds', 'quick-faq-markup' ); ?></span>
						<p class="description">
							<?php esc_html_e( 'How long to cache FAQ queries (minimum 300 seconds / 5 minutes). Default is 3600 seconds (1 hour).', 'quick-faq-markup' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>
		
		<?php submit_button(); ?>
	</form>
	
	<div class="qfm-settings-section">
		<h3><?php esc_html_e( 'Order Management Tools', 'quick-faq-markup' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'These tools help maintain data integrity and performance for the category-specific ordering system.', 'quick-faq-markup' ); ?>
		</p>
		
		<table class="form-table">
			<tr>
				<th scope="row">
					<label><?php esc_html_e( 'Recalculate Orders', 'quick-faq-markup' ); ?></label>
				</th>
				<td>
					<button type="button" class="button button-secondary" id="qfm-recalculate-orders">
						<?php esc_html_e( 'Recalculate All Orders', 'quick-faq-markup' ); ?>
					</button>
					<span class="qfm-tool-status" id="qfm-recalculate-status"></span>
					<p class="description">
						<?php esc_html_e( 'Recalculates global menu_order values based on category-specific orders. Use this if orders appear incorrect.', 'quick-faq-markup' ); ?>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label><?php esc_html_e( 'Validate Order Integrity', 'quick-faq-markup' ); ?></label>
				</th>
				<td>
					<button type="button" class="button button-secondary" id="qfm-validate-orders">
						<?php esc_html_e( 'Validate Order Integrity', 'quick-faq-markup' ); ?>
					</button>
					<span class="qfm-tool-status" id="qfm-validate-status"></span>
					<p class="description">
						<?php esc_html_e( 'Checks for duplicate orders, missing meta fields, and other integrity issues. Provides a detailed report.', 'quick-faq-markup' ); ?>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label><?php esc_html_e( 'Clear Order Cache', 'quick-faq-markup' ); ?></label>
				</th>
				<td>
					<button type="button" class="button button-secondary" id="qfm-clear-cache">
						<?php esc_html_e( 'Clear Order Cache', 'quick-faq-markup' ); ?>
					</button>
					<span class="qfm-tool-status" id="qfm-clear-cache-status"></span>
					<p class="description">
						<?php esc_html_e( 'Clears all cached FAQ query results. Use this after making manual database changes.', 'quick-faq-markup' ); ?>
					</p>
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label><?php esc_html_e( 'Migration Status', 'quick-faq-markup' ); ?></label>
				</th>
				<td>
					<button type="button" class="button button-secondary" id="qfm-check-migration">
						<?php esc_html_e( 'Check Migration Status', 'quick-faq-markup' ); ?>
					</button>
					<span class="qfm-tool-status" id="qfm-migration-status"></span>
					<p class="description">
						<?php esc_html_e( 'Checks if the category-specific ordering migration has been completed successfully.', 'quick-faq-markup' ); ?>
					</p>
				</td>
			</tr>
		</table>
		
		<div id="qfm-tool-results" class="qfm-tool-results" style="display: none;">
			<h4><?php esc_html_e( 'Tool Results', 'quick-faq-markup' ); ?></h4>
			<div id="qfm-tool-results-content"></div>
		</div>
	</div>
	
	<div class="qfm-help-text">
		<h4><?php esc_html_e( 'Quick Start Guide', 'quick-faq-markup' ); ?></h4>
		<p><?php esc_html_e( 'Follow these steps to get started with Quick FAQ Markup:', 'quick-faq-markup' ); ?></p>
		<ul>
			<li>
				<strong><?php esc_html_e( 'Step 1:', 'quick-faq-markup' ); ?></strong>
				<?php esc_html_e( 'Go to FAQs → Add New to create your first FAQ entry.', 'quick-faq-markup' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Step 2:', 'quick-faq-markup' ); ?></strong>
				<?php esc_html_e( 'Fill in the question and answer fields. You can use HTML formatting in answers.', 'quick-faq-markup' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Step 3:', 'quick-faq-markup' ); ?></strong>
				<?php esc_html_e( 'Set the display order and category (optional) for better organization.', 'quick-faq-markup' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Step 4:', 'quick-faq-markup' ); ?></strong>
				<?php esc_html_e( 'Use the shortcode to display FAQs on any page or post.', 'quick-faq-markup' ); ?>
			</li>
		</ul>
		
		<h4><?php esc_html_e( 'Shortcode Examples', 'quick-faq-markup' ); ?></h4>
		<ul>
			<li>
				<code>[qfm_faq]</code> - 
				<?php esc_html_e( 'Display all FAQs', 'quick-faq-markup' ); ?>
			</li>
			<li>
				<code>[qfm_faq style="accordion-modern"]</code> - 
				<?php esc_html_e( 'Display FAQs with accordion style', 'quick-faq-markup' ); ?>
			</li>
			<li>
				<code>[qfm_faq category="general" limit="5"]</code> - 
				<?php esc_html_e( 'Display 5 FAQs from "general" category', 'quick-faq-markup' ); ?>
			</li>
			<li>
				<code>[qfm_faq ids="1,2,3"]</code> - 
				<?php esc_html_e( 'Display specific FAQs by ID', 'quick-faq-markup' ); ?>
			</li>
		</ul>
		
		<h4><?php esc_html_e( 'Available Shortcode Parameters', 'quick-faq-markup' ); ?></h4>
		<ul>
			<li>
				<strong>style</strong> - 
				<?php esc_html_e( 'Display style: classic, accordion-modern, accordion-minimal, cards', 'quick-faq-markup' ); ?>
			</li>
			<li>
				<strong>category</strong> - 
				<?php esc_html_e( 'Filter by category name', 'quick-faq-markup' ); ?>
			</li>
			<li>
				<strong>limit</strong> - 
				<?php esc_html_e( 'Number of FAQs to display (default: all)', 'quick-faq-markup' ); ?>
			</li>
			<li>
				<strong>order</strong> - 
				<?php esc_html_e( 'Sort order: custom, date_desc, date_asc, title_asc, title_desc', 'quick-faq-markup' ); ?>
			</li>
			<li>
				<strong>ids</strong> - 
				<?php esc_html_e( 'Comma-separated list of FAQ IDs to display', 'quick-faq-markup' ); ?>
			</li>
		</ul>
	</div>
	
	<div class="qfm-help-text">
		<h4><?php esc_html_e( 'Plugin Information', 'quick-faq-markup' ); ?></h4>
		<p>
			<strong><?php esc_html_e( 'Version:', 'quick-faq-markup' ); ?></strong> 
			<?php echo esc_html( QUICK_FAQ_MARKUP_VERSION ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Development Phase:', 'quick-faq-markup' ); ?></strong> 
			<?php esc_html_e( 'Phase 1 - Core Foundation', 'quick-faq-markup' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Features Available:', 'quick-faq-markup' ); ?></strong>
			<?php esc_html_e( 'Custom post type, meta boxes, admin interface, drag-and-drop reordering', 'quick-faq-markup' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Coming in Phase 2:', 'quick-faq-markup' ); ?></strong>
			<?php esc_html_e( 'Enhanced admin interface, bulk operations, advanced reordering', 'quick-faq-markup' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Coming in Phase 3:', 'quick-faq-markup' ); ?></strong>
			<?php esc_html_e( 'Frontend display, multiple styles, shortcode system', 'quick-faq-markup' ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Coming in Phase 4:', 'quick-faq-markup' ); ?></strong>
			<?php esc_html_e( 'JSON-LD schema, SEO features, performance optimization', 'quick-faq-markup' ); ?>
		</p>
	</div>
</div>