<?php
/**
 * System Info - PHP Extensions Panel
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$card_title = 'PHP Extensions';
$card_icon = 'dashicons-code-standards';
$card_id = 'php-extensions';
include plugin_dir_path( __FILE__ ) . '../partials/card-header.php';
?>

<div class="components-card__body">
	<?php if ( ! empty( $data['php_extensions'] ) && is_array( $data['php_extensions'] ) ) : ?>
		<!-- Search Input -->
		<div style="margin-bottom: 16px;">
			<input type="search" 
				class="components-text-control__input" 
				id="php-ext-search" 
				placeholder="<?php esc_attr_e( 'Search extensions…', '365i-queue-optimizer' ); ?>"
				style="width: 100%; max-width: 300px; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px;"
				aria-label="<?php esc_attr_e( 'Search PHP extensions', '365i-queue-optimizer' ); ?>">
		</div>

		<!-- Extensions Table -->
		<div class="table-responsive">
			<table class="widefat striped components-table" style="border: 1px solid #c3c4c7; border-radius: 4px;">
				<thead>
					<tr style="background: #f6f7f7;">
						<th scope="col" style="padding: 12px; font-weight: 600; color: #1d2327;">
							<?php esc_html_e( 'Extension', '365i-queue-optimizer' ); ?>
						</th>
						<th scope="col" style="padding: 12px; font-weight: 600; color: #1d2327;">
							<?php esc_html_e( 'Version', '365i-queue-optimizer' ); ?>
						</th>
						<th scope="col" style="padding: 12px; font-weight: 600; color: #1d2327; text-align: center;">
							<?php esc_html_e( 'INI Keys', '365i-queue-optimizer' ); ?>
						</th>
						<th scope="col" style="padding: 12px; font-weight: 600; color: #1d2327; text-align: center;">
							<?php esc_html_e( 'Functions', '365i-queue-optimizer' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $data['php_extensions'] as $ext ) : ?>
					<tr style="border-top: 1px solid #c3c4c7;">
						<td style="padding: 12px; font-weight: 500; color: #1d2327;">
							<?php echo esc_html( $ext['name'] ); ?>
						</td>
						<td style="padding: 12px; color: #646970;">
							<span class="components-badge" style="background: #f0f6fc; color: #1e1e1e; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 500;">
								<?php echo esc_html( $ext['version'] ); ?>
							</span>
						</td>
						<td style="padding: 12px; color: #646970; text-align: center;">
							<?php echo esc_html( $ext['ini'] ); ?>
						</td>
						<td style="padding: 12px; color: #646970; text-align: center;">
							<?php echo esc_html( $ext['functions'] ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<!-- Summary Statistics -->
		<div style="margin-top: 16px; padding: 12px; background: #f6f7f7; border-radius: 4px; font-size: 13px; color: #646970;">
			<?php 
			$total_extensions = count( $data['php_extensions'] );
			$total_ini_keys = array_sum( array_column( $data['php_extensions'], 'ini' ) );
			$total_functions = array_sum( array_column( $data['php_extensions'], 'functions' ) );
			?>
			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px;">
				<div>
					<strong><?php esc_html_e( 'Total Extensions:', '365i-queue-optimizer' ); ?></strong>
					<span><?php echo esc_html( $total_extensions ); ?></span>
				</div>
				<div>
					<strong><?php esc_html_e( 'INI Settings:', '365i-queue-optimizer' ); ?></strong>
					<span><?php echo esc_html( $total_ini_keys ); ?></span>
				</div>
				<div>
					<strong><?php esc_html_e( 'Functions:', '365i-queue-optimizer' ); ?></strong>
					<span><?php echo esc_html( $total_functions ); ?></span>
				</div>
				<div>
					<strong><?php esc_html_e( 'PHP Version:', '365i-queue-optimizer' ); ?></strong>
					<span><?php echo esc_html( PHP_VERSION ); ?></span>
				</div>
			</div>
		</div>

	<?php else : ?>
		<div style="text-align: center; color: #646970; padding: 24px;">
			<span class="dashicons dashicons-warning" style="font-size: 24px; margin-bottom: 8px; color: #dba617;"></span>
			<p style="margin: 0;"><?php esc_html_e( 'No PHP extension information available.', '365i-queue-optimizer' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Critical Extensions Check -->
	<?php if ( ! empty( $data['critical_extensions']['extensions'] ) ) : ?>
		<div style="margin-top: 20px; padding: 16px; background: #f0f6fc; border-left: 4px solid #0073aa; border-radius: 4px;">
			<h4 style="margin: 0 0 12px 0; color: #1d2327; font-size: 14px; display: flex; align-items: center;">
				<span class="dashicons dashicons-admin-plugins" style="font-size: 16px; margin-right: 8px; color: #0073aa;"></span>
				<?php esc_html_e( 'Critical Extensions Status', '365i-queue-optimizer' ); ?>
				<?php 
				$health_status = $data['critical_extensions']['health_status'] ?? 'good';
				$missing_count = $data['critical_extensions']['missing_count'] ?? 0;
				$health_color = $health_status === 'good' ? '#46b450' : ( $health_status === 'warning' ? '#dba617' : '#dc3232' );
				?>
				<span class="components-badge" style="background: <?php echo esc_attr( $health_color ); ?>; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-left: 8px;">
					<?php echo $missing_count > 0 ? esc_html( sprintf( _n( '%d Missing', '%d Missing', $missing_count, '365i-queue-optimizer' ), $missing_count ) ) : esc_html__( 'All Present', '365i-queue-optimizer' ); ?>
				</span>
			</h4>
			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px;">
				<?php foreach ( $data['critical_extensions']['extensions'] as $ext_name => $ext_info ) : ?>
					<div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: white; border-radius: 3px; border: 1px solid #dcdcde;">
						<div>
							<span style="font-weight: 500; color: #1d2327;"><?php echo esc_html( $ext_info['name'] ); ?></span>
							<?php if ( ! empty( $ext_info['description'] ) ) : ?>
								<div style="font-size: 11px; color: #646970; margin-top: 2px;">
									<?php echo esc_html( $ext_info['description'] ); ?>
								</div>
							<?php endif; ?>
						</div>
						<span class="components-badge" 
							style="background-color: <?php echo $ext_info['is_loaded'] ? '#46b450' : '#dc3232'; ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 500;">
							<?php echo $ext_info['is_loaded'] ? esc_html__( 'Loaded', '365i-queue-optimizer' ) : esc_html__( 'Missing', '365i-queue-optimizer' ); ?>
						</span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>

<?php
$footer_actions = array(
	array(
		'text' => __( 'Export Extensions', '365i-queue-optimizer' ),
		'onclick' => 'exportPhpExtensions()',
		'class' => 'is-secondary',
		'icon' => 'dashicons-download'
	),
	array(
		'text' => __( 'Copy to Clipboard', '365i-queue-optimizer' ),
		'onclick' => 'copyExtensionsToClipboard()',
		'class' => 'is-secondary',
		'icon' => 'dashicons-clipboard'
	)
);
include plugin_dir_path( __FILE__ ) . '../partials/card-footer.php';
?>