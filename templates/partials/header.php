<?php
/**
 * Admin Page Header Partial
 *
 * Shared header for all 365i Queue Optimizer admin pages.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get page title if provided, otherwise use default.
$page_title = $page_title ?? __( '365i Queue Optimizer', '365i-queue-optimizer' );
$page_description = $page_description ?? '';
?>
<div class="wrap queue-optimizer-admin">
	<div class="queue-optimizer-header">
		<div class="queue-optimizer-branding">
			<h1 class="queue-optimizer-title">
				<span class="dashicons dashicons-performance"></span>
				<?php echo esc_html( $page_title ); ?>
			</h1>
			<?php if ( ! empty( $page_description ) ) : ?>
				<p class="queue-optimizer-description"><?php echo esc_html( $page_description ); ?></p>
			<?php endif; ?>
		</div>
		<div class="queue-optimizer-branding-logo">
			<strong>365i</strong>
		</div>
	</div>

	<?php
	// Show admin notices.
	settings_errors();
	?>

	<div class="queue-optimizer-content">