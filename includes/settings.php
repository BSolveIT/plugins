<?php
/**
 * Settings page and registration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register settings and fields.
 */
function ei_register_settings() {
	register_setting(
		'ei_settings_group',
		ei_get_option_name(),
		array(
			'sanitize_callback' => 'ei_sanitize_settings',
			'default'           => ei_get_default_settings(),
		)
	);

	// Detection Settings.
	add_settings_section(
		'ei_detection_section',
		esc_html__( 'Environment Detection', 'environment-indicator' ),
		'ei_detection_section_callback',
		'ei_settings'
	);

	add_settings_field(
		'ei_auto_detect',
		esc_html__( 'Detection Mode', 'environment-indicator' ),
		'ei_field_auto_detect',
		'ei_settings',
		'ei_detection_section'
	);

	add_settings_field(
		'ei_manual_environment',
		esc_html__( 'Manual Environment', 'environment-indicator' ),
		'ei_field_manual_environment',
		'ei_settings',
		'ei_detection_section'
	);

	add_settings_field(
		'ei_detected_environment',
		esc_html__( 'Current Environment', 'environment-indicator' ),
		'ei_field_detected_environment',
		'ei_settings',
		'ei_detection_section'
	);

	// Customization Settings.
	add_settings_section(
		'ei_customization_section',
		esc_html__( 'Customization', 'environment-indicator' ),
		'ei_customization_section_callback',
		'ei_settings'
	);

	add_settings_field(
		'ei_custom_labels',
		esc_html__( 'Custom Labels', 'environment-indicator' ),
		'ei_field_custom_labels',
		'ei_settings',
		'ei_customization_section'
	);

	add_settings_field(
		'ei_custom_colors',
		esc_html__( 'Custom Colors', 'environment-indicator' ),
		'ei_field_custom_colors',
		'ei_settings',
		'ei_customization_section'
	);

	// Visual Enhancements.
	add_settings_section(
		'ei_visual_section',
		esc_html__( 'Visual Enhancements', 'environment-indicator' ),
		'ei_visual_section_callback',
		'ei_settings'
	);

	add_settings_field(
		'ei_admin_bar_background',
		esc_html__( 'Admin Bar Background', 'environment-indicator' ),
		'ei_field_admin_bar_background',
		'ei_settings',
		'ei_visual_section'
	);

	add_settings_field(
		'ei_admin_top_border',
		esc_html__( 'Top Border', 'environment-indicator' ),
		'ei_field_admin_top_border',
		'ei_settings',
		'ei_visual_section'
	);

	add_settings_field(
		'ei_admin_footer_watermark',
		esc_html__( 'Footer Watermark', 'environment-indicator' ),
		'ei_field_admin_footer_watermark',
		'ei_settings',
		'ei_visual_section'
	);

	add_settings_field(
		'ei_dashboard_widget',
		esc_html__( 'Dashboard Widget', 'environment-indicator' ),
		'ei_field_dashboard_widget',
		'ei_settings',
		'ei_visual_section'
	);

	// Visibility Settings.
	add_settings_section(
		'ei_visibility_section',
		esc_html__( 'Visibility & Permissions', 'environment-indicator' ),
		'ei_visibility_section_callback',
		'ei_settings'
	);

	add_settings_field(
		'ei_role_visibility',
		esc_html__( 'Role-Based Visibility', 'environment-indicator' ),
		'ei_field_role_visibility',
		'ei_settings',
		'ei_visibility_section'
	);

	// Export/Import Settings.
	add_settings_section(
		'ei_import_export_section',
		esc_html__( 'Export / Import', 'environment-indicator' ),
		'ei_import_export_section_callback',
		'ei_settings'
	);

	add_settings_field(
		'ei_export_import',
		esc_html__( 'Settings Transfer', 'environment-indicator' ),
		'ei_field_export_import',
		'ei_settings',
		'ei_import_export_section'
	);
}
add_action( 'admin_init', 'ei_register_settings' );

/**
 * Add the settings page in the admin menu.
 */
function ei_add_settings_page() {
	if ( ei_is_network_active() ) {
		return;
	}

	add_options_page(
		esc_html__( 'Environment Indicator', 'environment-indicator' ),
		esc_html__( 'Environment Indicator', 'environment-indicator' ),
		'manage_options',
		'environment-indicator',
		'ei_render_settings_page'
	);
}
add_action( 'admin_menu', 'ei_add_settings_page' );

/**
 * Add the settings page in the network admin menu.
 */
function ei_add_network_settings_page() {
	if ( ! ei_is_network_active() ) {
		return;
	}

	add_submenu_page(
		'settings.php',
		esc_html__( 'Environment Indicator', 'environment-indicator' ),
		esc_html__( 'Environment Indicator', 'environment-indicator' ),
		'manage_options',
		'environment-indicator',
		'ei_render_settings_page'
	);
}
add_action( 'network_admin_menu', 'ei_add_network_settings_page' );

/**
 * Add a Settings link on the Plugins page.
 *
 * @param array $links Plugin action links.
 * @return array
 */
function ei_add_plugin_action_links( $links ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return $links;
	}

	if ( ei_is_network_active() ) {
		return $links;
	}

	$url           = admin_url( 'options-general.php?page=environment-indicator' );
	$settings_link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'environment-indicator' ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( EI_PLUGIN_FILE ), 'ei_add_plugin_action_links' );

/**
 * Add a Settings link on the Network Plugins page.
 *
 * @param array $links Plugin action links.
 * @return array
 */
function ei_add_network_plugin_action_links( $links ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return $links;
	}

	if ( ! ei_is_network_active() ) {
		return $links;
	}

	$url           = network_admin_url( 'settings.php?page=environment-indicator' );
	$settings_link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'environment-indicator' ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'network_admin_plugin_action_links_' . plugin_basename( EI_PLUGIN_FILE ), 'ei_add_network_plugin_action_links' );

/**
 * Detection section callback.
 */
function ei_detection_section_callback() {
	echo '<p class="description">' . esc_html__( 'Configure how the environment is detected and displayed.', 'environment-indicator' ) . '</p>';
}

/**
 * Customization section callback.
 */
function ei_customization_section_callback() {
	echo '<p class="description">' . esc_html__( 'Customize the appearance of environment labels and colors.', 'environment-indicator' ) . '</p>';
}

/**
 * Visual section callback.
 */
function ei_visual_section_callback() {
	echo '<p class="description">' . esc_html__( 'Enable optional visual enhancements to make the environment more prominent.', 'environment-indicator' ) . '</p>';
}

/**
 * Visibility section callback.
 */
function ei_visibility_section_callback() {
	echo '<p class="description">' . esc_html__( 'Control which user roles can see the environment indicator.', 'environment-indicator' ) . '</p>';
}

/**
 * Import/Export section callback.
 */
function ei_import_export_section_callback() {
	echo '<p class="description">' . esc_html__( 'Export your settings to use on another site, or import settings from a JSON file.', 'environment-indicator' ) . '</p>';
}

/**
 * Sanitize settings input.
 *
 * @param array $input Raw input.
 * @return array
 */
function ei_sanitize_settings( $input ) {
	$output  = ei_get_default_settings();
	$input   = is_array( $input ) ? $input : array();
	$allowed = array( 'dev', 'staging', 'live' );

	// Checkboxes.
	$output['auto_detect']            = ! empty( $input['auto_detect'] ) ? 1 : 0;
	$output['admin_bar_background']   = ! empty( $input['admin_bar_background'] ) ? 1 : 0;
	$output['admin_top_border']       = ! empty( $input['admin_top_border'] ) ? 1 : 0;
	$output['admin_footer_watermark'] = ! empty( $input['admin_footer_watermark'] ) ? 1 : 0;
	$output['dashboard_widget']       = ! empty( $input['dashboard_widget'] ) ? 1 : 0;
	$output['custom_colors']          = ! empty( $input['custom_colors'] ) ? 1 : 0;
	$output['custom_labels']          = ! empty( $input['custom_labels'] ) ? 1 : 0;
	$output['role_visibility']        = ! empty( $input['role_visibility'] ) ? 1 : 0;

	// Manual environment.
	if ( ! empty( $input['manual_environment'] ) ) {
		$manual = sanitize_key( $input['manual_environment'] );
		if ( in_array( $manual, $allowed, true ) ) {
			$output['manual_environment'] = $manual;
		}
	}

	// Custom colors.
	if ( ! empty( $input['color_dev'] ) ) {
		$output['color_dev'] = sanitize_hex_color( $input['color_dev'] );
	}
	if ( ! empty( $input['color_staging'] ) ) {
		$output['color_staging'] = sanitize_hex_color( $input['color_staging'] );
	}
	if ( ! empty( $input['color_live'] ) ) {
		$output['color_live'] = sanitize_hex_color( $input['color_live'] );
	}

	// Custom labels.
	if ( ! empty( $input['label_dev'] ) ) {
		$output['label_dev'] = sanitize_text_field( $input['label_dev'] );
	}
	if ( ! empty( $input['label_staging'] ) ) {
		$output['label_staging'] = sanitize_text_field( $input['label_staging'] );
	}
	if ( ! empty( $input['label_live'] ) ) {
		$output['label_live'] = sanitize_text_field( $input['label_live'] );
	}

	// Visible roles.
	if ( isset( $input['visible_roles'] ) && is_array( $input['visible_roles'] ) ) {
		$output['visible_roles'] = array_map( 'sanitize_key', $input['visible_roles'] );
	} else {
		$output['visible_roles'] = array();
	}

	return $output;
}

/**
 * Render the settings page.
 */
function ei_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$is_network = ei_is_network_active() && is_network_admin();

	// Handle import.
	$import_file = filter_input( INPUT_POST, 'ei_import_file', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
	if ( ! empty( $import_file ) && isset( $_FILES['ei_import_json'] ) ) {
		check_admin_referer( 'ei_import_settings' );
		$file = $_FILES['ei_import_json'];
		if ( UPLOAD_ERR_OK === $file['error'] ) {
			$json   = file_get_contents( $file['tmp_name'] );
			$result = ei_import_settings( $json );
			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings imported successfully.', 'environment-indicator' ) . '</p></div>';
			}
		}
	}

	// Handle network save.
	$submitted = filter_input( INPUT_POST, 'ei_settings_submit', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

	if ( $is_network && ! empty( $submitted ) ) {
		check_admin_referer( 'ei_network_settings' );
		$raw_input = filter_input( INPUT_POST, 'ei_settings', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$input     = is_array( $raw_input ) ? wp_unslash( $raw_input ) : array();
		$sanitized = ei_sanitize_settings( $input );
		ei_update_settings( $sanitized );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'environment-indicator' ) . '</p></div>';
	}

	echo '<div class="wrap ei-settings-wrap">';
	echo '<h1>' . esc_html__( 'Environment Indicator Settings', 'environment-indicator' ) . '</h1>';

	// Live preview box.
	$environment = ei_get_environment();
	$label       = ei_get_environment_label( $environment );
	$color       = ei_get_environment_color( $environment );
	$source      = ei_get_detection_source();

	echo '<div class="ei-preview-box">';
	echo '<div class="ei-preview-label">' . esc_html__( 'Live Preview', 'environment-indicator' ) . '</div>';
	echo '<div class="ei-preview-badge" style="background-color: ' . esc_attr( $color ) . ';">';
	echo '<span>' . esc_html( $label ) . '</span>';
	echo '</div>';
	echo '<div class="ei-preview-info">';
	echo '<strong>' . esc_html__( 'Detection:', 'environment-indicator' ) . '</strong> ';
	echo '<code>' . esc_html( $source ) . '</code>';
	echo '</div>';
	echo '</div>';

	echo '<form method="post" action="' . esc_url( $is_network ? network_admin_url( 'settings.php?page=environment-indicator' ) : 'options.php' ) . '" class="ei-settings-form">';

	if ( $is_network ) {
		wp_nonce_field( 'ei_network_settings' );
		echo '<input type="hidden" name="ei_settings_submit" value="1" />';
	} else {
		settings_fields( 'ei_settings_group' );
	}

	do_settings_sections( 'ei_settings' );
	submit_button();
	echo '</form>';
	echo '</div>';
}

/**
 * Field: Automatic detection checkbox.
 */
function ei_field_auto_detect() {
	$settings = ei_get_settings();

	echo '<label>';
	echo '<input type="checkbox" name="ei_settings[auto_detect]" value="1" id="ei_auto_detect" ' . checked( 1, $settings['auto_detect'], false ) . ' />';
	echo ' ' . esc_html__( 'Enable automatic environment detection', 'environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Automatically detect environment using constants (WP_ENVIRONMENT_TYPE, WP_ENV, etc.) or subdomain patterns.', 'environment-indicator' ) . '</p>';
}

/**
 * Field: Manual environment selection.
 */
function ei_field_manual_environment() {
	$settings     = ei_get_settings();
	$current      = isset( $settings['manual_environment'] ) ? $settings['manual_environment'] : 'live';
	$environments = array(
		'dev'     => esc_html__( 'DEV', 'environment-indicator' ),
		'staging' => esc_html__( 'STAGING', 'environment-indicator' ),
		'live'    => esc_html__( 'LIVE', 'environment-indicator' ),
	);

	echo '<div id="ei_manual_environment_wrapper">';
	echo '<fieldset>';
	foreach ( $environments as $value => $label ) {
		echo '<label>';
		echo '<input type="radio" name="ei_settings[manual_environment]" value="' . esc_attr( $value ) . '" ' . checked( $value, $current, false ) . ' />';
		echo ' ' . esc_html( $label );
		echo '</label><br />';
	}
	echo '</fieldset>';
	echo '<p class="description">' . esc_html__( 'Manually select the environment when automatic detection is disabled.', 'environment-indicator' ) . '</p>';
	echo '</div>';
}

/**
 * Field: Currently detected environment display.
 */
function ei_field_detected_environment() {
	$environment = ei_get_environment();
	$label       = ei_get_environment_label( $environment );
	$color       = ei_get_environment_color( $environment );

	echo '<div class="ei-current-env" style="display: inline-block; background-color: ' . esc_attr( $color ) . '; color: #fff; padding: 8px 16px; border-radius: 4px; font-weight: 700;">';
	echo esc_html( $label );
	echo '</div>';
	echo '<p class="description">' . esc_html__( 'This is the currently active environment as detected or manually selected.', 'environment-indicator' ) . '</p>';
}

/**
 * Field: Custom labels toggle and inputs.
 */
function ei_field_custom_labels() {
	$settings = ei_get_settings();

	echo '<label>';
	echo '<input type="checkbox" name="ei_settings[custom_labels]" value="1" id="ei_custom_labels" ' . checked( 1, $settings['custom_labels'], false ) . ' />';
	echo ' ' . esc_html__( 'Use custom environment labels', 'environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Replace DEV/STAGING/LIVE with your own labels (e.g., LOCAL, UAT, PRODUCTION).', 'environment-indicator' ) . '</p>';

	echo '<div id="ei_custom_labels_inputs" style="margin-top: 15px;">';
	echo '<table class="form-table" style="margin: 0;">';
	echo '<tr><th scope="row">' . esc_html__( 'DEV Label', 'environment-indicator' ) . '</th><td>';
	echo '<input type="text" name="ei_settings[label_dev]" value="' . esc_attr( $settings['label_dev'] ) . '" class="regular-text" placeholder="DEV" />';
	echo '</td></tr>';
	echo '<tr><th scope="row">' . esc_html__( 'STAGING Label', 'environment-indicator' ) . '</th><td>';
	echo '<input type="text" name="ei_settings[label_staging]" value="' . esc_attr( $settings['label_staging'] ) . '" class="regular-text" placeholder="STAGING" />';
	echo '</td></tr>';
	echo '<tr><th scope="row">' . esc_html__( 'LIVE Label', 'environment-indicator' ) . '</th><td>';
	echo '<input type="text" name="ei_settings[label_live]" value="' . esc_attr( $settings['label_live'] ) . '" class="regular-text" placeholder="LIVE" />';
	echo '</td></tr>';
	echo '</table>';
	echo '</div>';
}

/**
 * Field: Custom colors toggle and pickers.
 */
function ei_field_custom_colors() {
	$settings = ei_get_settings();

	echo '<label>';
	echo '<input type="checkbox" name="ei_settings[custom_colors]" value="1" id="ei_custom_colors" ' . checked( 1, $settings['custom_colors'], false ) . ' />';
	echo ' ' . esc_html__( 'Use custom environment colors', 'environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Customize the colors for each environment to match your branding or preferences.', 'environment-indicator' ) . '</p>';

	echo '<div id="ei_custom_colors_inputs" style="margin-top: 15px;">';
	echo '<table class="form-table" style="margin: 0;">';
	echo '<tr><th scope="row">' . esc_html__( 'DEV Color', 'environment-indicator' ) . '</th><td>';
	echo '<input type="text" name="ei_settings[color_dev]" value="' . esc_attr( $settings['color_dev'] ) . '" class="ei-color-picker" data-default-color="#2e8b57" />';
	echo '</td></tr>';
	echo '<tr><th scope="row">' . esc_html__( 'STAGING Color', 'environment-indicator' ) . '</th><td>';
	echo '<input type="text" name="ei_settings[color_staging]" value="' . esc_attr( $settings['color_staging'] ) . '" class="ei-color-picker" data-default-color="#f39c12" />';
	echo '</td></tr>';
	echo '<tr><th scope="row">' . esc_html__( 'LIVE Color', 'environment-indicator' ) . '</th><td>';
	echo '<input type="text" name="ei_settings[color_live]" value="' . esc_attr( $settings['color_live'] ) . '" class="ei-color-picker" data-default-color="#c0392b" />';
	echo '</td></tr>';
	echo '</table>';
	echo '</div>';
}

/**
 * Field: Admin bar background toggle.
 */
function ei_field_admin_bar_background() {
	$settings = ei_get_settings();

	echo '<label>';
	echo '<input type="checkbox" name="ei_settings[admin_bar_background]" value="1" ' . checked( 1, $settings['admin_bar_background'], false ) . ' />';
	echo ' ' . esc_html__( 'Color the entire admin bar background', 'environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Makes the environment highly visible by coloring the entire admin bar (very prominent).', 'environment-indicator' ) . '</p>';
}

/**
 * Field: Admin top border toggle.
 */
function ei_field_admin_top_border() {
	$settings = ei_get_settings();

	echo '<label>';
	echo '<input type="checkbox" name="ei_settings[admin_top_border]" value="1" ' . checked( 1, $settings['admin_top_border'], false ) . ' />';
	echo ' ' . esc_html__( 'Show colored top border in admin', 'environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Adds a 4px colored border at the top of all wp-admin screens.', 'environment-indicator' ) . '</p>';
}

/**
 * Field: Admin footer watermark toggle.
 */
function ei_field_admin_footer_watermark() {
	$settings = ei_get_settings();

	echo '<label>';
	echo '<input type="checkbox" name="ei_settings[admin_footer_watermark]" value="1" ' . checked( 1, $settings['admin_footer_watermark'], false ) . ' />';
	echo ' ' . esc_html__( 'Show environment label in admin footer', 'environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Displays the environment name in the admin footer area.', 'environment-indicator' ) . '</p>';
}

/**
 * Field: Dashboard widget toggle.
 */
function ei_field_dashboard_widget() {
	$settings = ei_get_settings();

	echo '<label>';
	echo '<input type="checkbox" name="ei_settings[dashboard_widget]" value="1" ' . checked( 1, $settings['dashboard_widget'], false ) . ' />';
	echo ' ' . esc_html__( 'Show environment status dashboard widget', 'environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Adds a dashboard widget showing environment info, detection method, and system details.', 'environment-indicator' ) . '</p>';
}

/**
 * Field: Role visibility settings.
 */
function ei_field_role_visibility() {
	$settings = ei_get_settings();
	global $wp_roles;

	echo '<label>';
	echo '<input type="checkbox" name="ei_settings[role_visibility]" value="1" id="ei_role_visibility" ' . checked( 1, $settings['role_visibility'], false ) . ' />';
	echo ' ' . esc_html__( 'Restrict visibility to specific roles', 'environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Only show the environment indicator to users with selected roles.', 'environment-indicator' ) . '</p>';

	echo '<div id="ei_role_visibility_inputs" style="margin-top: 15px;">';
	echo '<fieldset>';

	$visible_roles = isset( $settings['visible_roles'] ) ? $settings['visible_roles'] : array( 'administrator' );

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}

	foreach ( $wp_roles->get_names() as $role_slug => $role_name ) {
		$checked = in_array( $role_slug, $visible_roles, true ) ? 'checked' : '';
		echo '<label style="display: block; margin-bottom: 5px;">';
		echo '<input type="checkbox" name="ei_settings[visible_roles][]" value="' . esc_attr( $role_slug ) . '" ' . esc_attr( $checked ) . ' />';
		echo ' ' . esc_html( translate_user_role( $role_name ) );
		echo '</label>';
	}

	echo '</fieldset>';
	echo '</div>';
}

/**
 * Field: Export/Import functionality.
 */
function ei_field_export_import() {
	echo '<div class="ei-export-import-section">';

	// Export.
	echo '<h4>' . esc_html__( 'Export Settings', 'environment-indicator' ) . '</h4>';
	echo '<p>' . esc_html__( 'Export your current settings as a JSON file to use on another site.', 'environment-indicator' ) . '</p>';
	echo '<button type="button" id="ei_export_settings" class="button button-secondary">' . esc_html__( 'Download Settings', 'environment-indicator' ) . '</button>';
	echo '<textarea id="ei_export_data" style="display:none;">' . esc_textarea( ei_export_settings() ) . '</textarea>';

	// Import.
	echo '<h4 style="margin-top: 25px;">' . esc_html__( 'Import Settings', 'environment-indicator' ) . '</h4>';
	echo '<p>' . esc_html__( 'Import settings from a JSON file. This will overwrite your current settings.', 'environment-indicator' ) . '</p>';
	echo '<form method="post" enctype="multipart/form-data" style="margin-top: 10px;">';
	wp_nonce_field( 'ei_import_settings' );
	echo '<input type="file" name="ei_import_json" accept=".json" required />';
	echo '<input type="hidden" name="ei_import_file" value="1" />';
	echo '<button type="submit" class="button button-secondary" style="margin-left: 10px;">' . esc_html__( 'Import Settings', 'environment-indicator' ) . '</button>';
	echo '</form>';

	echo '</div>';
}
