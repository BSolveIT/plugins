<?php
/**
 * Dashboard widget functionality.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the dashboard widget.
 */
function ei_register_dashboard_widget() {
	$settings = ei_get_settings();

	if ( empty( $settings['dashboard_widget'] ) ) {
		return;
	}

	if ( ! ei_user_can_see_indicator() ) {
		return;
	}

	wp_add_dashboard_widget(
		'ei_dashboard_widget',
		__( 'Environment Status', 'environment-indicator' ),
		'ei_render_dashboard_widget'
	);
}
add_action( 'wp_dashboard_setup', 'ei_register_dashboard_widget' );

/**
 * Render the dashboard widget content.
 */
function ei_render_dashboard_widget() {
	$environment = ei_get_environment();
	$label       = ei_get_environment_label( $environment );
	$color       = ei_get_environment_color( $environment );
	$source      = ei_get_detection_source();
	$settings    = ei_get_settings();
	$env_lower   = strtolower( $environment );

	echo '<div class="ei-dashboard-widget">';

	// Header with environment badge.
	echo '<div class="ei-widget-header">';
	echo '<div class="ei-header-content">';
	echo '<div class="ei-env-status">';
	echo '<span class="ei-status-label">' . esc_html__( 'Environment Status', 'environment-indicator' ) . '</span>';
	echo '<div class="ei-env-badge-wrapper">';
	echo '<span class="ei-env-badge ei-env-' . esc_attr( $env_lower ) . '" style="background: linear-gradient(135deg, ' . esc_attr( $color ) . ' 0%, ' . esc_attr( ei_adjust_color_brightness( $color, -15 ) ) . ' 100%);">';
	echo esc_html( $label );
	echo '</span>';
	echo '</div>';
	echo '</div>';
	echo '</div>';
	echo '</div>';

	// System information grid.
	echo '<div class="ei-widget-body">';
	echo '<div class="ei-info-grid">';

	// Detection method.
	echo '<div class="ei-info-card">';
	echo '<div class="ei-card-header">';
	echo '<span class="ei-card-icon ei-icon-detection"><span class="dashicons dashicons-search"></span></span>';
	echo '<span class="ei-card-title">' . esc_html__( 'Detection', 'environment-indicator' ) . '</span>';
	echo '</div>';
	echo '<div class="ei-card-body">';
	if ( empty( $settings['auto_detect'] ) ) {
		echo esc_html__( 'Manual Selection', 'environment-indicator' );
	} else {
		echo esc_html( $source );
	}
	echo '</div>';
	echo '</div>';

	// Site URL.
	echo '<div class="ei-info-card">';
	echo '<div class="ei-card-header">';
	echo '<span class="ei-card-icon ei-icon-url"><span class="dashicons dashicons-admin-site"></span></span>';
	echo '<span class="ei-card-title">' . esc_html__( 'Site URL', 'environment-indicator' ) . '</span>';
	echo '</div>';
	echo '<div class="ei-card-body ei-card-url">' . esc_html( parse_url( get_home_url(), PHP_URL_HOST ) ) . '</div>';
	echo '</div>';

	// WordPress version.
	echo '<div class="ei-info-card">';
	echo '<div class="ei-card-header">';
	echo '<span class="ei-card-icon ei-icon-wp"><span class="dashicons dashicons-wordpress-alt"></span></span>';
	echo '<span class="ei-card-title">' . esc_html__( 'WordPress', 'environment-indicator' ) . '</span>';
	echo '</div>';
	echo '<div class="ei-card-body">Version ' . esc_html( get_bloginfo( 'version' ) ) . '</div>';
	echo '</div>';

	// PHP version.
	echo '<div class="ei-info-card">';
	echo '<div class="ei-card-header">';
	echo '<span class="ei-card-icon ei-icon-php"><span class="dashicons dashicons-editor-code"></span></span>';
	echo '<span class="ei-card-title">' . esc_html__( 'PHP', 'environment-indicator' ) . '</span>';
	echo '</div>';
	echo '<div class="ei-card-body">Version ' . esc_html( phpversion() ) . '</div>';
	echo '</div>';

	echo '</div>'; // .ei-info-grid
	echo '</div>'; // .ei-widget-body

	// Quick actions.
	if ( current_user_can( 'manage_options' ) ) {
		$settings_url = ei_is_network_active()
			? network_admin_url( 'settings.php?page=environment-indicator' )
			: admin_url( 'options-general.php?page=environment-indicator' );

		echo '<div class="ei-widget-footer">';
		echo '<a href="' . esc_url( $settings_url ) . '" class="ei-settings-link">';
		echo '<span style="display: flex; align-items: center; gap: 8px;">';
		echo '<span class="dashicons dashicons-admin-generic"></span>';
		echo '<span>' . esc_html__( 'Configure Settings', 'environment-indicator' ) . '</span>';
		echo '</span>';
		echo '<span class="dashicons dashicons-arrow-right-alt2"></span>';
		echo '</a>';
		echo '</div>';
	}

	echo '</div>';
}

/**
 * Adjust color brightness for gradient effect.
 *
 * @param string $hex Hex color code.
 * @param int    $steps Steps to adjust (-255 to 255).
 * @return string Adjusted hex color.
 */
function ei_adjust_color_brightness( $hex, $steps ) {
	$hex = str_replace( '#', '', $hex );

	if ( strlen( $hex ) === 3 ) {
		$hex = str_repeat( substr( $hex, 0, 1 ), 2 ) . str_repeat( substr( $hex, 1, 1 ), 2 ) . str_repeat( substr( $hex, 2, 1 ), 2 );
	}

	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );

	$r = max( 0, min( 255, $r + $steps ) );
	$g = max( 0, min( 255, $g + $steps ) );
	$b = max( 0, min( 255, $b + $steps ) );

	return '#' . str_pad( dechex( $r ), 2, '0', STR_PAD_LEFT ) . str_pad( dechex( $g ), 2, '0', STR_PAD_LEFT ) . str_pad( dechex( $b ), 2, '0', STR_PAD_LEFT );
}
