<?php
/**
 * System Info - Theme Information Panel
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$card_title = 'Theme Information';
$card_icon = 'dashicons-admin-appearance';
$card_id = 'theme-information';
include plugin_dir_path( __FILE__ ) . '../partials/card-header.php';
?>

<div class="components-card__body">
	<table class="widefat striped components-table">
		<caption class="screen-reader-text">Active Theme Information</caption>
		<tbody>
			<tr>
				<th scope="row" style="width: 30%;">Active Theme</th>
				<td>
					<strong><?php echo esc_html( $data['theme']['name'] ?? 'Unknown' ); ?></strong>
					<?php if ( ! empty( $data['theme']['version'] ) ) : ?>
						<span style="color: #666; margin-left: 8px;">
							v<?php echo esc_html( $data['theme']['version'] ); ?>
						</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row">Theme Directory</th>
				<td>
					<code><?php echo esc_html( $data['theme']['template'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Theme Author</th>
				<td>
					<?php echo esc_html( $data['theme']['author'] ?? 'Unknown' ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">Theme URI</th>
				<td>
					<?php if ( ! empty( $data['theme']['theme_uri'] ) ) : ?>
						<a href="<?php echo esc_url( $data['theme']['theme_uri'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $data['theme']['theme_uri'] ); ?>
						</a>
					<?php else : ?>
						<span style="color: #666;">Not specified</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row">Child Theme</th>
				<td>
					<?php $is_child_theme = $data['theme']['is_child_theme'] ?? false; ?>
					<code><?php echo $is_child_theme ? 'Yes' : 'No'; ?></code>
					<?php if ( $is_child_theme ) : ?>
						<span class="components-badge is-success"
							style="margin-left: 8px; background-color: #46b450; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px;">
							Child Theme
						</span>
					<?php endif; ?>
				</td>
			</tr>
			<?php if ( ! empty( $data['theme']['parent_theme'] ) ) : ?>
			<tr>
				<th scope="row">Parent Theme</th>
				<td>
					<strong><?php echo esc_html( $data['theme']['parent_theme'] ); ?></strong>
				</td>
			</tr>
			<?php endif; ?>
			<tr>
				<th scope="row">Text Domain</th>
				<td>
					<code><?php echo esc_html( $data['theme']['text_domain'] ?? 'Unknown' ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Theme Tags</th>
				<td>
					<?php if ( ! empty( $data['theme']['tags'] ) && is_array( $data['theme']['tags'] ) ) : ?>
						<?php foreach ( $data['theme']['tags'] as $tag ) : ?>
							<span class="components-badge" style="background-color: #f0f0f1; color: #1d2327; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-right: 4px;">
								<?php echo esc_html( $tag ); ?>
							</span>
						<?php endforeach; ?>
					<?php else : ?>
						<span style="color: #666;">No tags</span>
					<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<?php
$footer_actions = array(
	array(
		'text' => 'Copy Theme Info',
		'onclick' => 'copyToClipboard(\'theme-information\')',
		'class' => 'is-secondary',
		'icon' => 'dashicons-clipboard'
	)
);
include plugin_dir_path( __FILE__ ) . '../partials/card-footer.php';
?>