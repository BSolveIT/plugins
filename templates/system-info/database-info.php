<?php
/**
 * System Info - Database Information Panel
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$card_title = 'Database Information';
$card_icon = 'dashicons-database';
$card_id = 'database-information';
include plugin_dir_path( __FILE__ ) . '../partials/card-header.php';
?>

<div class="components-card__body">
	<table class="widefat striped components-table">
		<caption class="screen-reader-text">Database Configuration Information</caption>
		<tbody>
			<tr>
				<th scope="row" style="width: 30%;">Database Version</th>
				<td>
					<code><?php echo esc_html( $data['database']['version'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Database Size</th>
				<td>
					<code><?php echo esc_html( $data['database']['size'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Max Allowed Packet</th>
				<td>
					<code><?php echo esc_html( $data['database']['max_allowed_packet'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Max Connections</th>
				<td>
					<code><?php echo esc_html( $data['database']['max_connections'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Query Cache</th>
				<td>
					<?php $query_cache = $data['database']['query_cache'] ?? 'Unknown'; ?>
					<code><?php echo esc_html( $query_cache ); ?></code>
					<?php if ( $query_cache !== 'Unknown' ) : ?>
						<span class="components-badge <?php echo esc_attr( strtolower( $query_cache ) === 'on' ? 'is-success' : 'is-warning' ); ?>"
							style="margin-left: 8px; background-color: <?php echo strtolower( $query_cache ) === 'on' ? '#46b450' : '#ffb900'; ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
							<?php echo strtolower( $query_cache ) === 'on' ? 'Enabled' : 'Disabled'; ?>
						</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row">InnoDB Buffer Pool Size</th>
				<td>
					<code><?php echo esc_html( $data['database']['innodb_buffer_pool_size'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Database Charset</th>
				<td>
					<code><?php echo esc_html( $data['database']['charset'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Database Collation</th>
				<td>
					<code><?php echo esc_html( $data['database']['collation'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<?php
$footer_actions = array(
	array(
		'text' => 'Copy Database Info',
		'onclick' => 'copyToClipboard(\'database-information\')',
		'class' => 'is-secondary',
		'icon' => 'dashicons-clipboard'
	)
);
include plugin_dir_path( __FILE__ ) . '../partials/card-footer.php';
?>