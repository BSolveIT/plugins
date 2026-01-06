<?php
/**
 * Helper functions for Environment Indicator.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the settings option name.
 *
 * @return string
 */
function i365ei_get_option_name() {
	return 'i365ei_settings';
}

/**
 * Get the default settings.
 *
 * @return array
 */
function i365ei_get_default_settings() {
	return array(
		'auto_detect'            => 1,
		'manual_environment'     => 'live',
		'admin_bar_background'   => 0,
		'admin_top_border'       => 0,
		'admin_footer_watermark' => 0,
		'dashboard_widget'       => 1,
		'custom_colors'          => 0,
		'color_dev'              => '#2e8b57',
		'color_staging'          => '#f39c12',
		'color_live'             => '#c0392b',
		'custom_labels'          => 0,
		'label_dev'              => 'DEV',
		'label_staging'          => 'STAGING',
		'label_live'             => 'LIVE',
		'role_visibility'        => 0,
		'visible_roles'          => array( 'administrator' ),
	);
}

/**
 * Determine if the plugin is network activated.
 *
 * @return bool
 */
function i365ei_is_network_active() {
	if ( ! is_multisite() ) {
		return false;
	}

	$network_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
	$plugin_basename = plugin_basename( I365EI_PLUGIN_FILE );

	return isset( $network_plugins[ $plugin_basename ] );
}

/**
 * Get plugin settings with defaults applied.
 *
 * @return array
 */
function i365ei_get_settings() {
	global $i365ei_settings_cache;

	if ( null !== $i365ei_settings_cache ) {
		return $i365ei_settings_cache;
	}

	$defaults = i365ei_get_default_settings();
	$stored   = array();

	if ( i365ei_is_network_active() ) {
		$stored = get_site_option( i365ei_get_option_name(), array() );
	} else {
		$stored = get_option( i365ei_get_option_name(), array() );
	}

	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	$i365ei_settings_cache = wp_parse_args( $stored, $defaults );

	$i365ei_settings_cache['auto_detect']            = ! empty( $i365ei_settings_cache['auto_detect'] ) ? 1 : 0;
	$i365ei_settings_cache['admin_bar_background']   = ! empty( $i365ei_settings_cache['admin_bar_background'] ) ? 1 : 0;
	$i365ei_settings_cache['admin_top_border']       = ! empty( $i365ei_settings_cache['admin_top_border'] ) ? 1 : 0;
	$i365ei_settings_cache['admin_footer_watermark'] = ! empty( $i365ei_settings_cache['admin_footer_watermark'] ) ? 1 : 0;
	$i365ei_settings_cache['dashboard_widget']       = ! empty( $i365ei_settings_cache['dashboard_widget'] ) ? 1 : 0;
	$i365ei_settings_cache['custom_colors']          = ! empty( $i365ei_settings_cache['custom_colors'] ) ? 1 : 0;
	$i365ei_settings_cache['custom_labels']          = ! empty( $i365ei_settings_cache['custom_labels'] ) ? 1 : 0;
	$i365ei_settings_cache['role_visibility']        = ! empty( $i365ei_settings_cache['role_visibility'] ) ? 1 : 0;

	if ( empty( $i365ei_settings_cache['manual_environment'] ) ) {
		$i365ei_settings_cache['manual_environment'] = $defaults['manual_environment'];
	}

	if ( ! is_array( $i365ei_settings_cache['visible_roles'] ) ) {
		$i365ei_settings_cache['visible_roles'] = $defaults['visible_roles'];
	}

	return $i365ei_settings_cache;
}

/**
 * Update plugin settings.
 *
 * @param array $settings Settings array.
 * @return bool
 */
function i365ei_update_settings( $settings ) {
	global $i365ei_environment_cache, $i365ei_settings_cache;

	if ( i365ei_is_network_active() ) {
		$updated = update_site_option( i365ei_get_option_name(), $settings );
	} else {
		$updated = update_option( i365ei_get_option_name(), $settings );
	}

	$i365ei_settings_cache = $settings;
	$i365ei_environment_cache = null;

	return $updated;
}

/**
 * Get the color for a specific environment.
 *
 * @param string $environment Environment name (DEV, STAGING, LIVE).
 * @return string Hex color code.
 */
function i365ei_get_environment_color( $environment ) {
	$settings  = i365ei_get_settings();
	$defaults  = i365ei_get_default_settings();
	$env_lower = strtolower( $environment );

	if ( ! empty( $settings['custom_colors'] ) && ! empty( $settings[ 'color_' . $env_lower ] ) ) {
		return sanitize_hex_color( $settings[ 'color_' . $env_lower ] );
	}

	return $defaults[ 'color_' . $env_lower ];
}

/**
 * Get the label for a specific environment.
 *
 * @param string $environment Environment name (DEV, STAGING, LIVE).
 * @return string Display label.
 */
function i365ei_get_environment_label( $environment ) {
	$settings  = i365ei_get_settings();
	$env_lower = strtolower( $environment );

	if ( ! empty( $settings['custom_labels'] ) && ! empty( $settings[ 'label_' . $env_lower ] ) ) {
		return sanitize_text_field( $settings[ 'label_' . $env_lower ] );
	}

	return $environment;
}

/**
 * Check if the current user should see the environment indicator.
 *
 * @return bool
 */
function i365ei_user_can_see_indicator() {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	$settings = i365ei_get_settings();

	if ( empty( $settings['role_visibility'] ) ) {
		return true;
	}

	$visible_roles = isset( $settings['visible_roles'] ) ? $settings['visible_roles'] : array( 'administrator' );

	if ( empty( $visible_roles ) || ! is_array( $visible_roles ) ) {
		return true;
	}

	$user = wp_get_current_user();

	foreach ( $visible_roles as $role ) {
		if ( in_array( $role, (array) $user->roles, true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Export settings as JSON.
 *
 * @return string JSON string.
 */
function i365ei_export_settings() {
	$settings = i365ei_get_settings();
	return wp_json_encode( $settings, JSON_PRETTY_PRINT );
}

/**
 * Import settings from JSON.
 *
 * @param string $json JSON string.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function i365ei_import_settings( $json ) {
	$data = json_decode( $json, true );

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return new WP_Error( 'invalid_json', __( 'Invalid JSON format.', '365i-environment-indicator' ) );
	}

	if ( ! is_array( $data ) ) {
		return new WP_Error( 'invalid_data', __( 'Invalid settings data.', '365i-environment-indicator' ) );
	}

	$defaults = i365ei_get_default_settings();
	$merged   = wp_parse_args( $data, $defaults );

	return i365ei_update_settings( $merged );
}
