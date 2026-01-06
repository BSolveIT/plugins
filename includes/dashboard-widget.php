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
function i365ei_register_dashboard_widget() {
	$settings = i365ei_get_settings();

	if ( empty( $settings['dashboard_widget'] ) ) {
		return;
	}

	if ( ! i365ei_user_can_see_indicator() ) {
		return;
	}

	wp_add_dashboard_widget(
		'i365ei_dashboard_widget',
		__( 'Environment Status', '365i-environment-indicator' ),
		'i365ei_render_dashboard_widget'
	);
}
add_action( 'wp_dashboard_setup', 'i365ei_register_dashboard_widget' );

/**
 * Render the dashboard widget content.
 */
function i365ei_render_dashboard_widget() {
	$environment = i365ei_get_environment();
	$label       = i365ei_get_environment_label( $environment );
	$color       = i365ei_get_environment_color( $environment );
	$source      = i365ei_get_detection_source();
	$settings    = i365ei_get_settings();
	$env_lower   = strtolower( $environment );

	echo '<div class="i365ei-dashboard-widget">';

	// Header with environment badge.
	echo '<div class="i365ei-widget-header">';
	echo '<div class="i365ei-header-content">';
	echo '<div class="i365ei-env-status">';
	echo '<span class="i365ei-status-label">' . esc_html__( 'Environment Status', '365i-environment-indicator' ) . '</span>';
	echo '<div class="i365ei-env-badge-wrapper">';
	echo '<span class="i365ei-env-badge i365ei-env-' . esc_attr( $env_lower ) . '" style="background: linear-gradient(135deg, ' . esc_attr( $color ) . ' 0%, ' . esc_attr( i365ei_adjust_color_brightness( $color, -15 ) ) . ' 100%);">';
	echo esc_html( $label );
	echo '</span>';
	echo '</div>';
	echo '</div>';
	echo '</div>';
	echo '</div>';

	// System information grid.
	echo '<div class="i365ei-widget-body">';
	echo '<div class="i365ei-info-grid">';

	// Detection method.
	echo '<div class="i365ei-info-card">';
	echo '<div class="i365ei-card-header">';
	echo '<span class="i365ei-card-icon i365ei-icon-detection"><span class="dashicons dashicons-search"></span></span>';
	echo '<span class="i365ei-card-title">' . esc_html__( 'Detection', '365i-environment-indicator' ) . '</span>';
	echo '</div>';
	echo '<div class="i365ei-card-body">';
	if ( empty( $settings['auto_detect'] ) ) {
		echo esc_html__( 'Manual Selection', '365i-environment-indicator' );
	} else {
		echo esc_html( $source );
	}
	echo '</div>';
	echo '</div>';

	// Site URL.
	echo '<div class="i365ei-info-card">';
	echo '<div class="i365ei-card-header">';
	echo '<span class="i365ei-card-icon i365ei-icon-url"><span class="dashicons dashicons-admin-site"></span></span>';
	echo '<span class="i365ei-card-title">' . esc_html__( 'Site URL', '365i-environment-indicator' ) . '</span>';
	echo '</div>';
	echo '<div class="i365ei-card-body i365ei-card-url">' . esc_html( wp_parse_url( get_home_url(), PHP_URL_HOST ) ) . '</div>';
	echo '</div>';

	// WordPress version.
	echo '<div class="i365ei-info-card">';
	echo '<div class="i365ei-card-header">';
	echo '<span class="i365ei-card-icon i365ei-icon-wp"><span class="dashicons dashicons-wordpress-alt"></span></span>';
	echo '<span class="i365ei-card-title">' . esc_html__( 'WordPress', '365i-environment-indicator' ) . '</span>';
	echo '</div>';
	echo '<div class="i365ei-card-body">Version ' . esc_html( get_bloginfo( 'version' ) ) . '</div>';
	echo '</div>';

	// PHP version.
	echo '<div class="i365ei-info-card">';
	echo '<div class="i365ei-card-header">';
	echo '<span class="i365ei-card-icon i365ei-icon-php"><span class="dashicons dashicons-editor-code"></span></span>';
	echo '<span class="i365ei-card-title">' . esc_html__( 'PHP', '365i-environment-indicator' ) . '</span>';
	echo '</div>';
	echo '<div class="i365ei-card-body">Version ' . esc_html( phpversion() ) . '</div>';
	echo '</div>';

	echo '</div>'; // .i365ei-info-grid
	echo '</div>'; // .i365ei-widget-body

	// Quick actions.
	if ( current_user_can( 'manage_options' ) ) {
		$settings_url = i365ei_is_network_active()
			? network_admin_url( 'settings.php?page=365i-environment-indicator' )
			: admin_url( 'options-general.php?page=365i-environment-indicator' );

		echo '<div class="i365ei-widget-footer">';
		echo '<a href="' . esc_url( $settings_url ) . '" class="i365ei-settings-link">';
		echo '<span style="display: flex; align-items: center; gap: 8px;">';
		echo '<span class="dashicons dashicons-admin-generic"></span>';
		echo '<span>' . esc_html__( 'Configure Settings', '365i-environment-indicator' ) . '</span>';
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
function i365ei_adjust_color_brightness( $hex, $steps ) {
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
