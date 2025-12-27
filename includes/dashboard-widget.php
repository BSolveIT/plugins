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

	echo '<div class="ei-dashboard-widget">';

	// Environment badge.
	echo '<div class="ei-env-badge" style="background-color: ' . esc_attr( $color ) . ';">';
	echo '<span class="ei-env-label">' . esc_html( $label ) . '</span>';
	echo '</div>';

	// Detection information.
	echo '<div class="ei-widget-info">';
	echo '<div class="ei-info-row">';
	echo '<span class="ei-info-label">' . esc_html__( 'Detection method:', 'environment-indicator' ) . '</span>';
	echo '<span class="ei-info-value">';
	if ( empty( $settings['auto_detect'] ) ) {
		echo esc_html__( 'Manual selection', 'environment-indicator' );
	} else {
		echo '<code>' . esc_html( $source ) . '</code>';
	}
	echo '</span>';
	echo '</div>';

	// Site URL.
	echo '<div class="ei-info-row">';
	echo '<span class="ei-info-label">' . esc_html__( 'Site URL:', 'environment-indicator' ) . '</span>';
	echo '<span class="ei-info-value"><code>' . esc_html( get_home_url() ) . '</code></span>';
	echo '</div>';

	// WordPress version.
	echo '<div class="ei-info-row">';
	echo '<span class="ei-info-label">' . esc_html__( 'WordPress:', 'environment-indicator' ) . '</span>';
	echo '<span class="ei-info-value">' . esc_html( get_bloginfo( 'version' ) ) . '</span>';
	echo '</div>';

	// PHP version.
	echo '<div class="ei-info-row">';
	echo '<span class="ei-info-label">' . esc_html__( 'PHP:', 'environment-indicator' ) . '</span>';
	echo '<span class="ei-info-value">' . esc_html( phpversion() ) . '</span>';
	echo '</div>';

	echo '</div>';

	// Quick actions.
	if ( current_user_can( 'manage_options' ) ) {
		$settings_url = ei_is_network_active()
			? network_admin_url( 'settings.php?page=environment-indicator' )
			: admin_url( 'options-general.php?page=environment-indicator' );

		echo '<div class="ei-widget-actions">';
		echo '<a href="' . esc_url( $settings_url ) . '" class="button button-secondary">';
		echo esc_html__( 'Configure Settings', 'environment-indicator' );
		echo '</a>';
		echo '</div>';
	}

	echo '</div>';
}
