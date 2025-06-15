<?php
/**
 * Admin Page Footer Partial
 *
 * Shared footer for all 365i Queue Optimizer admin pages.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
	</div><!-- .queue-optimizer-content -->

	<div class="queue-optimizer-footer">
		<div class="queue-optimizer-footer-content">
			<div class="queue-optimizer-footer-left">
				<p>
					<?php
					printf(
						/* translators: %s: Plugin version */
						esc_html__( '365i Queue Optimizer v%s', '365i-queue-optimizer' ),
						esc_html( QUEUE_OPTIMIZER_VERSION )
					);
					?>
				</p>
			</div>
			<div class="queue-optimizer-footer-right">
				<p>
					<?php
					printf(
						/* translators: %s: 365i URL */
						wp_kses_post( __( 'Powered by <a href="%s" target="_blank">365i WordPress Hosting</a>', '365i-queue-optimizer' ) ),
						esc_url( 'https://www.365i.co.uk/' )
					);
					?>
				</p>
			</div>
		</div>
	</div>
</div><!-- .queue-optimizer-admin -->