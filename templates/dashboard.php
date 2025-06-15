<?php
/**
 * Dashboard Template
 *
 * Main dashboard page display for the Queue Optimizer plugin.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include header partial.
include plugin_dir_path( __FILE__ ) . 'partials/header.php';
?>

<div class="queue-optimizer-dashboard">

	<!-- Stats Cards -->
	<?php include plugin_dir_path( __FILE__ ) . 'dashboard/stats-cards.php'; ?>

	<!-- Main Content Grid -->
	<div class="dashboard-content-row">
		
		<!-- Left Column -->
		<div class="dashboard-left-column">
			<?php include plugin_dir_path( __FILE__ ) . 'dashboard/status-panel.php'; ?>
			<?php include plugin_dir_path( __FILE__ ) . 'dashboard/quick-actions.php'; ?>
		</div>

		<!-- Right Column -->
		<div class="dashboard-right-column">
			<?php include plugin_dir_path( __FILE__ ) . 'dashboard/activity-panel.php'; ?>
			<?php include plugin_dir_path( __FILE__ ) . 'dashboard/settings-overview.php'; ?>
		</div>

	</div>

</div>

<?php
// Include footer partial.
include plugin_dir_path( __FILE__ ) . 'partials/footer.php';
?>