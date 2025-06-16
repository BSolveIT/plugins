<?php
/**
 * Admin page footer partial for 365i AI FAQ Generator.
 * 
 * This partial is included at the bottom of all admin pages to provide
 * consistent footer styling and closing markup.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Templates
 * @since 2.0.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

	</div><!-- .ai-faq-gen-content -->

	<div class="ai-faq-gen-footer">
		<div class="ai-faq-gen-footer-content">
			<div class="ai-faq-gen-footer-left">
				<p>
					<?php
					printf(
						/* translators: 1: Plugin name, 2: Version number */
						esc_html__( '%1$s v%2$s', '365i-ai-faq-generator' ),
						'<strong>365i AI FAQ Generator</strong>',
						esc_html( AI_FAQ_GEN_VERSION )
					);
					?>
				</p>
			</div>
			
			<div class="ai-faq-gen-footer-right">
				<div class="ai-faq-gen-footer-links">
					<a href="https://365i.co.uk" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( '365i.co.uk', '365i-ai-faq-generator' ); ?>
					</a>
					<span class="separator">|</span>
					<a href="https://github.com/BSolveIT/plugins" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'GitHub', '365i-ai-faq-generator' ); ?>
					</a>
					<span class="separator">|</span>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-faq-generator-settings' ) ); ?>">
						<?php esc_html_e( 'Settings', '365i-ai-faq-generator' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div><!-- .ai-faq-gen-footer -->

</div><!-- .ai-faq-gen-admin -->