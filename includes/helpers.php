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
function ei_get_option_name() {
	return 'ei_settings';
}

/**
 * Get the default settings.
 *
 * @return array
 */
function ei_get_default_settings() {
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
function ei_is_network_active() {
	if ( ! is_multisite() ) {
		return false;
	}

	$network_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
	$plugin_basename = plugin_basename( EI_PLUGIN_FILE );

	return isset( $network_plugins[ $plugin_basename ] );
}

/**
 * Get plugin settings with defaults applied.
 *
 * @return array
 */
function ei_get_settings() {
	global $ei_settings_cache;

	if ( null !== $ei_settings_cache ) {
		return $ei_settings_cache;
	}

	$defaults = ei_get_default_settings();
	$stored   = array();

	if ( ei_is_network_active() ) {
		$stored = get_site_option( ei_get_option_name(), array() );
	} else {
		$stored = get_option( ei_get_option_name(), array() );
	}

	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	$ei_settings_cache = wp_parse_args( $stored, $defaults );

	$ei_settings_cache['auto_detect']            = ! empty( $ei_settings_cache['auto_detect'] ) ? 1 : 0;
	$ei_settings_cache['admin_bar_background']   = ! empty( $ei_settings_cache['admin_bar_background'] ) ? 1 : 0;
	$ei_settings_cache['admin_top_border']       = ! empty( $ei_settings_cache['admin_top_border'] ) ? 1 : 0;
	$ei_settings_cache['admin_footer_watermark'] = ! empty( $ei_settings_cache['admin_footer_watermark'] ) ? 1 : 0;
	$ei_settings_cache['dashboard_widget']       = ! empty( $ei_settings_cache['dashboard_widget'] ) ? 1 : 0;
	$ei_settings_cache['custom_colors']          = ! empty( $ei_settings_cache['custom_colors'] ) ? 1 : 0;
	$ei_settings_cache['custom_labels']          = ! empty( $ei_settings_cache['custom_labels'] ) ? 1 : 0;
	$ei_settings_cache['role_visibility']        = ! empty( $ei_settings_cache['role_visibility'] ) ? 1 : 0;

	if ( empty( $ei_settings_cache['manual_environment'] ) ) {
		$ei_settings_cache['manual_environment'] = $defaults['manual_environment'];
	}

	if ( ! is_array( $ei_settings_cache['visible_roles'] ) ) {
		$ei_settings_cache['visible_roles'] = $defaults['visible_roles'];
	}

	return $ei_settings_cache;
}

/**
 * Update plugin settings.
 *
 * @param array $settings Settings array.
 * @return bool
 */
function ei_update_settings( $settings ) {
	global $ei_environment_cache, $ei_settings_cache;

	if ( ei_is_network_active() ) {
		$updated = update_site_option( ei_get_option_name(), $settings );
	} else {
		$updated = update_option( ei_get_option_name(), $settings );
	}

	$ei_settings_cache = $settings;
	$ei_environment_cache = null;

	return $updated;
}

/**
 * Get the color for a specific environment.
 *
 * @param string $environment Environment name (DEV, STAGING, LIVE).
 * @return string Hex color code.
 */
function ei_get_environment_color( $environment ) {
	$settings  = ei_get_settings();
	$defaults  = ei_get_default_settings();
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
function ei_get_environment_label( $environment ) {
	$settings  = ei_get_settings();
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
function ei_user_can_see_indicator() {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	$settings = ei_get_settings();

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
function ei_export_settings() {
	$settings = ei_get_settings();
	return wp_json_encode( $settings, JSON_PRETTY_PRINT );
}

/**
 * Import settings from JSON.
 *
 * @param string $json JSON string.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function ei_import_settings( $json ) {
	$data = json_decode( $json, true );

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return new WP_Error( 'invalid_json', __( 'Invalid JSON format.', 'environment-indicator' ) );
	}

	if ( ! is_array( $data ) ) {
		return new WP_Error( 'invalid_data', __( 'Invalid settings data.', 'environment-indicator' ) );
	}

	$defaults = ei_get_default_settings();
	$merged   = wp_parse_args( $data, $defaults );

	return ei_update_settings( $merged );
}
