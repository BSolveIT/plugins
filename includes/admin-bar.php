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
function i365ei_add_admin_bar_indicator( $wp_admin_bar ) {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	if ( ! i365ei_user_can_see_indicator() ) {
		return;
	}

	$environment = i365ei_get_environment();
	$label       = i365ei_get_environment_label( $environment );

	$wp_admin_bar->add_node(
		array(
			'id'    => 'i365ei-environment',
			'title' => esc_html( $label ),
			'meta'  => array(
				'class' => 'i365ei-admin-bar-node',
			),
		)
	);
}
add_action( 'admin_bar_menu', 'i365ei_add_admin_bar_indicator', 100 );

/**
 * Enqueue admin styles.
 */
function i365ei_enqueue_admin_styles() {
	wp_enqueue_style(
		'i365ei-admin',
		I365EI_PLUGIN_URL . 'assets/admin.css',
		array(),
		I365EI_VERSION
	);

	// Enqueue color picker on settings page.
	$current_screen = get_current_screen();
	if ( $current_screen && ( $current_screen->id === 'settings_page_365i-environment-indicator' || $current_screen->id === 'settings_page_365i-environment-indicator-network' ) ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script(
			'i365ei-settings',
			I365EI_PLUGIN_URL . 'assets/settings.js',
			array( 'jquery', 'wp-color-picker' ),
			I365EI_VERSION,
			true
		);
	}

	i365ei_add_inline_styles();
}
add_action( 'admin_enqueue_scripts', 'i365ei_enqueue_admin_styles' );

/**
 * Enqueue front-end styles when admin bar is visible.
 */
function i365ei_enqueue_front_styles() {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	if ( ! i365ei_user_can_see_indicator() ) {
		return;
	}

	wp_enqueue_style(
		'i365ei-admin',
		I365EI_PLUGIN_URL . 'assets/admin.css',
		array(),
		I365EI_VERSION
	);

	i365ei_add_inline_styles();
}
add_action( 'wp_enqueue_scripts', 'i365ei_enqueue_front_styles' );

/**
 * Add body classes in the admin area.
 *
 * @param string $classes Body classes.
 * @return string
 */
function i365ei_admin_body_class( $classes ) {
	$settings    = i365ei_get_settings();
	$environment = strtolower( i365ei_get_environment() );
	$new_classes = array( 'i365ei-env-' . $environment );

	if ( ! empty( $settings['admin_bar_background'] ) ) {
		$new_classes[] = 'i365ei-admin-bar-bg';
	}

	if ( ! empty( $settings['admin_top_border'] ) ) {
		$new_classes[] = 'i365ei-admin-top-border';
	}

	if ( ! empty( $settings['admin_footer_watermark'] ) ) {
		$new_classes[] = 'i365ei-admin-footer-watermark';
	}

	if ( ! empty( $settings['custom_colors'] ) ) {
		$new_classes[] = 'i365ei-custom-colors';
	}

	return trim( $classes . ' ' . implode( ' ', $new_classes ) );
}
add_filter( 'admin_body_class', 'i365ei_admin_body_class' );

/**
 * Add body classes on the front end when the admin bar is visible.
 *
 * @param array $classes Body classes.
 * @return array
 */
function i365ei_front_body_class( $classes ) {
	if ( ! is_admin_bar_showing() ) {
		return $classes;
	}

	if ( ! i365ei_user_can_see_indicator() ) {
		return $classes;
	}

	$settings    = i365ei_get_settings();
	$environment = strtolower( i365ei_get_environment() );

	$classes[] = 'i365ei-env-' . $environment;

	if ( ! empty( $settings['admin_bar_background'] ) ) {
		$classes[] = 'i365ei-admin-bar-bg';
	}

	if ( ! empty( $settings['custom_colors'] ) ) {
		$classes[] = 'i365ei-custom-colors';
	}

	return $classes;
}
add_filter( 'body_class', 'i365ei_front_body_class' );

/**
 * Append an environment watermark to the admin footer.
 *
 * @param string $footer_text Footer text.
 * @return string
 */
function i365ei_admin_footer_watermark( $footer_text ) {
	$settings = i365ei_get_settings();

	if ( empty( $settings['admin_footer_watermark'] ) ) {
		return $footer_text;
	}

	if ( ! i365ei_user_can_see_indicator() ) {
		return $footer_text;
	}

	$environment = i365ei_get_environment();
	$label       = i365ei_get_environment_label( $environment );
	$watermark   = '<span class="i365ei-footer-watermark">' . esc_html( $label ) . '</span>';

	if ( '' !== $footer_text ) {
		$footer_text .= ' ';
	}

	return $footer_text . $watermark;
}
add_filter( 'admin_footer_text', 'i365ei_admin_footer_watermark', 20 );

/**
 * Escape a hex color for safe use in CSS.
 *
 * @param string $color Hex color code.
 * @return string Escaped hex color or empty string if invalid.
 */
function i365ei_escape_css_color( $color ) {
	// Validate hex color format (3 or 6 characters with optional #).
	if ( preg_match( '/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $color ) ) {
		// Ensure it starts with #.
		if ( '#' !== substr( $color, 0, 1 ) ) {
			$color = '#' . $color;
		}
		return $color;
	}
	return '';
}

/**
 * Add inline styles for custom colors.
 */
function i365ei_add_inline_styles() {
	$settings = i365ei_get_settings();

	if ( empty( $settings['custom_colors'] ) ) {
		return;
	}

	$color_dev     = i365ei_escape_css_color( i365ei_get_environment_color( 'DEV' ) );
	$color_staging = i365ei_escape_css_color( i365ei_get_environment_color( 'STAGING' ) );
	$color_live    = i365ei_escape_css_color( i365ei_get_environment_color( 'LIVE' ) );

	// Only output CSS if we have valid colors.
	if ( empty( $color_dev ) || empty( $color_staging ) || empty( $color_live ) ) {
		return;
	}

	$css = "
		body.i365ei-custom-colors.i365ei-env-dev #wp-admin-bar-i365ei-environment .ab-item { background: {$color_dev} !important; }
		body.i365ei-custom-colors.i365ei-env-staging #wp-admin-bar-i365ei-environment .ab-item { background: {$color_staging} !important; }
		body.i365ei-custom-colors.i365ei-env-live #wp-admin-bar-i365ei-environment .ab-item { background: {$color_live} !important; }
		body.i365ei-custom-colors.i365ei-admin-bar-bg.i365ei-env-dev #wpadminbar { background: {$color_dev} !important; }
		body.i365ei-custom-colors.i365ei-admin-bar-bg.i365ei-env-staging #wpadminbar { background: {$color_staging} !important; }
		body.i365ei-custom-colors.i365ei-admin-bar-bg.i365ei-env-live #wpadminbar { background: {$color_live} !important; }
		body.i365ei-custom-colors.i365ei-admin-top-border.i365ei-env-dev #wpcontent { border-top-color: {$color_dev} !important; }
		body.i365ei-custom-colors.i365ei-admin-top-border.i365ei-env-staging #wpcontent { border-top-color: {$color_staging} !important; }
		body.i365ei-custom-colors.i365ei-admin-top-border.i365ei-env-live #wpcontent { border-top-color: {$color_live} !important; }
		body.i365ei-custom-colors.i365ei-env-dev .i365ei-footer-watermark { color: {$color_dev} !important; }
		body.i365ei-custom-colors.i365ei-env-staging .i365ei-footer-watermark { color: {$color_staging} !important; }
		body.i365ei-custom-colors.i365ei-env-live .i365ei-footer-watermark { color: {$color_live} !important; }
		.i365ei-dashboard-widget .i365ei-env-badge { background-color: {$color_dev} !important; }
	";

	wp_add_inline_style( 'i365ei-admin', $css );
}
