<div class="queue-optimizer-admin-container">
	<!-- Settings Form -->
	<div class="queue-optimizer-settings-panel">
		<form method="post" action="options.php">
			<?php
			settings_fields( 'queue_optimizer_settings' );
			do_settings_sections( 'queue_optimizer_settings' );
			submit_button( __( 'Save Settings', '365i-queue-optimizer' ) );
			?>
		</form>
	</div>