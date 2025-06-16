<?php
/**
 * Admin page header partial for 365i AI FAQ Generator.
 * 
 * This partial is included at the top of all admin pages to provide
 * consistent header styling and branding.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Templates
 * @since 2.0.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current page info.
$current_screen = get_current_screen();
$page_slug = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';

// Set page title based on current page.
$page_titles = array(
	'ai-faq-generator' => __( 'Dashboard', '365i-ai-faq-generator' ),
	'ai-faq-generator-tool' => __( 'FAQ Generator', '365i-ai-faq-generator' ),
	'ai-faq-generator-workers' => __( 'Worker Configuration', '365i-ai-faq-generator' ),
	'ai-faq-generator-settings' => __( 'Settings', '365i-ai-faq-generator' ),
);

$page_title = isset( $page_titles[ $page_slug ] ) ? $page_titles[ $page_slug ] : __( '365i AI FAQ Generator', '365i-ai-faq-generator' );
?>

<div class="wrap ai-faq-gen-admin">
	<div class="ai-faq-gen-header">
		<div class="ai-faq-gen-header-content">
			<div class="ai-faq-gen-logo">
				<h1>
					<span class="dashicons dashicons-format-chat"></span>
					<?php echo esc_html( $page_title ); ?>
				</h1>
				<span class="ai-faq-gen-version">v<?php echo esc_html( AI_FAQ_GEN_VERSION ); ?></span>
			</div>
			
			<div class="ai-faq-gen-nav">
				<nav class="ai-faq-gen-nav-tabs">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-faq-generator' ) ); ?>" 
					   class="nav-tab <?php echo 'ai-faq-generator' === $page_slug ? 'nav-tab-active' : ''; ?>">
						<span class="dashicons dashicons-dashboard"></span>
						<?php esc_html_e( 'Dashboard', '365i-ai-faq-generator' ); ?>
					</a>
					
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-faq-generator-tool' ) ); ?>" 
					   class="nav-tab <?php echo 'ai-faq-generator-tool' === $page_slug ? 'nav-tab-active' : ''; ?>">
						<span class="dashicons dashicons-lightbulb"></span>
						<?php esc_html_e( 'Generator', '365i-ai-faq-generator' ); ?>
					</a>
					
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-faq-generator-workers' ) ); ?>" 
					   class="nav-tab <?php echo 'ai-faq-generator-workers' === $page_slug ? 'nav-tab-active' : ''; ?>">
						<span class="dashicons dashicons-networking"></span>
						<?php esc_html_e( 'Workers', '365i-ai-faq-generator' ); ?>
					</a>
					
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-faq-generator-settings' ) ); ?>" 
					   class="nav-tab <?php echo 'ai-faq-generator-settings' === $page_slug ? 'nav-tab-active' : ''; ?>">
						<span class="dashicons dashicons-admin-settings"></span>
						<?php esc_html_e( 'Settings', '365i-ai-faq-generator' ); ?>
					</a>
				</nav>
			</div>
		</div>
	</div>

	<div class="ai-faq-gen-content">