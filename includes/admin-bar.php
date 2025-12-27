<?php
/**
 * Admin bar indicator and visual enhancements.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the environment label to the admin bar.
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
 */
function ei_add_admin_bar_indicator( $wp_admin_bar ) {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	if ( ! ei_user_can_see_indicator() ) {
		return;
	}

	$environment = ei_get_environment();
	$label       = ei_get_environment_label( $environment );

	$wp_admin_bar->add_node(
		array(
			'id'    => 'ei-environment',
			'title' => esc_html( $label ),
			'meta'  => array(
				'class' => 'ei-admin-bar-node',
			),
		)
	);
}
add_action( 'admin_bar_menu', 'ei_add_admin_bar_indicator', 100 );

/**
 * Enqueue admin styles.
 */
function ei_enqueue_admin_styles() {
	wp_enqueue_style(
		'ei-admin',
		EI_PLUGIN_URL . 'assets/admin.css',
		array(),
		EI_VERSION
	);

	// Enqueue color picker on settings page.
	$current_screen = get_current_screen();
	if ( $current_screen && ( $current_screen->id === 'settings_page_environment-indicator' || $current_screen->id === 'settings_page_environment-indicator-network' ) ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script(
			'ei-settings',
			EI_PLUGIN_URL . 'assets/settings.js',
			array( 'jquery', 'wp-color-picker' ),
			EI_VERSION,
			true
		);
	}

	ei_add_inline_styles();
}
add_action( 'admin_enqueue_scripts', 'ei_enqueue_admin_styles' );

/**
 * Enqueue front-end styles when admin bar is visible.
 */
function ei_enqueue_front_styles() {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	if ( ! ei_user_can_see_indicator() ) {
		return;
	}

	wp_enqueue_style(
		'ei-admin',
		EI_PLUGIN_URL . 'assets/admin.css',
		array(),
		EI_VERSION
	);

	ei_add_inline_styles();
}
add_action( 'wp_enqueue_scripts', 'ei_enqueue_front_styles' );

/**
 * Add body classes in the admin area.
 *
 * @param string $classes Body classes.
 * @return string
 */
function ei_admin_body_class( $classes ) {
	$settings    = ei_get_settings();
	$environment = strtolower( ei_get_environment() );
	$new_classes = array( 'ei-env-' . $environment );

	if ( ! empty( $settings['admin_bar_background'] ) ) {
		$new_classes[] = 'ei-admin-bar-bg';
	}

	if ( ! empty( $settings['admin_top_border'] ) ) {
		$new_classes[] = 'ei-admin-top-border';
	}

	if ( ! empty( $settings['admin_footer_watermark'] ) ) {
		$new_classes[] = 'ei-admin-footer-watermark';
	}

	if ( ! empty( $settings['custom_colors'] ) ) {
		$new_classes[] = 'ei-custom-colors';
	}

	return trim( $classes . ' ' . implode( ' ', $new_classes ) );
}
add_filter( 'admin_body_class', 'ei_admin_body_class' );

/**
 * Add body classes on the front end when the admin bar is visible.
 *
 * @param array $classes Body classes.
 * @return array
 */
function ei_front_body_class( $classes ) {
	if ( ! is_admin_bar_showing() ) {
		return $classes;
	}

	if ( ! ei_user_can_see_indicator() ) {
		return $classes;
	}

	$settings    = ei_get_settings();
	$environment = strtolower( ei_get_environment() );

	$classes[] = 'ei-env-' . $environment;

	if ( ! empty( $settings['admin_bar_background'] ) ) {
		$classes[] = 'ei-admin-bar-bg';
	}

	if ( ! empty( $settings['custom_colors'] ) ) {
		$classes[] = 'ei-custom-colors';
	}

	return $classes;
}
add_filter( 'body_class', 'ei_front_body_class' );

/**
 * Append an environment watermark to the admin footer.
 *
 * @param string $footer_text Footer text.
 * @return string
 */
function ei_admin_footer_watermark( $footer_text ) {
	$settings = ei_get_settings();

	if ( empty( $settings['admin_footer_watermark'] ) ) {
		return $footer_text;
	}

	if ( ! ei_user_can_see_indicator() ) {
		return $footer_text;
	}

	$environment = ei_get_environment();
	$label       = ei_get_environment_label( $environment );
	$watermark   = '<span class="ei-footer-watermark">' . esc_html( $label ) . '</span>';

	if ( '' !== $footer_text ) {
		$footer_text .= ' ';
	}

	return $footer_text . $watermark;
}
add_filter( 'admin_footer_text', 'ei_admin_footer_watermark', 20 );

/**
 * Add inline styles for custom colors.
 */
function ei_add_inline_styles() {
	$settings = ei_get_settings();

	if ( empty( $settings['custom_colors'] ) ) {
		return;
	}

	$color_dev     = ei_get_environment_color( 'DEV' );
	$color_staging = ei_get_environment_color( 'STAGING' );
	$color_live    = ei_get_environment_color( 'LIVE' );

	$css = "
		body.ei-custom-colors.ei-env-dev #wp-admin-bar-ei-environment .ab-item { background: {$color_dev} !important; }
		body.ei-custom-colors.ei-env-staging #wp-admin-bar-ei-environment .ab-item { background: {$color_staging} !important; }
		body.ei-custom-colors.ei-env-live #wp-admin-bar-ei-environment .ab-item { background: {$color_live} !important; }
		body.ei-custom-colors.ei-admin-bar-bg.ei-env-dev #wpadminbar { background: {$color_dev} !important; }
		body.ei-custom-colors.ei-admin-bar-bg.ei-env-staging #wpadminbar { background: {$color_staging} !important; }
		body.ei-custom-colors.ei-admin-bar-bg.ei-env-live #wpadminbar { background: {$color_live} !important; }
		body.ei-custom-colors.ei-admin-top-border.ei-env-dev #wpcontent { border-top-color: {$color_dev} !important; }
		body.ei-custom-colors.ei-admin-top-border.ei-env-staging #wpcontent { border-top-color: {$color_staging} !important; }
		body.ei-custom-colors.ei-admin-top-border.ei-env-live #wpcontent { border-top-color: {$color_live} !important; }
		body.ei-custom-colors.ei-env-dev .ei-footer-watermark { color: {$color_dev} !important; }
		body.ei-custom-colors.ei-env-staging .ei-footer-watermark { color: {$color_staging} !important; }
		body.ei-custom-colors.ei-env-live .ei-footer-watermark { color: {$color_live} !important; }
		.ei-dashboard-widget .ei-env-badge { background-color: {$color_dev} !important; }
	";

	wp_add_inline_style( 'ei-admin', $css );
}
