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
function i365ei_register_settings() {
	register_setting(
		'i365ei_settings_group',
		i365ei_get_option_name(),
		array(
			'sanitize_callback' => 'i365ei_sanitize_settings',
			'default'           => i365ei_get_default_settings(),
		)
	);

	// Detection Settings.
	add_settings_section(
		'i365ei_detection_section',
		esc_html__( 'Environment Detection', '365i-environment-indicator' ),
		'i365ei_detection_section_callback',
		'i365ei_settings'
	);

	add_settings_field(
		'i365ei_auto_detect',
		esc_html__( 'Detection Mode', '365i-environment-indicator' ),
		'i365ei_field_auto_detect',
		'i365ei_settings',
		'i365ei_detection_section'
	);

	add_settings_field(
		'i365ei_manual_environment',
		esc_html__( 'Manual Environment', '365i-environment-indicator' ),
		'i365ei_field_manual_environment',
		'i365ei_settings',
		'i365ei_detection_section'
	);

	add_settings_field(
		'i365ei_detected_environment',
		esc_html__( 'Current Environment', '365i-environment-indicator' ),
		'i365ei_field_detected_environment',
		'i365ei_settings',
		'i365ei_detection_section'
	);

	// Customization Settings.
	add_settings_section(
		'i365ei_customization_section',
		esc_html__( 'Customization', '365i-environment-indicator' ),
		'i365ei_customization_section_callback',
		'i365ei_settings'
	);

	add_settings_field(
		'i365ei_custom_labels',
		esc_html__( 'Custom Labels', '365i-environment-indicator' ),
		'i365ei_field_custom_labels',
		'i365ei_settings',
		'i365ei_customization_section'
	);

	add_settings_field(
		'i365ei_custom_colors',
		esc_html__( 'Custom Colors', '365i-environment-indicator' ),
		'i365ei_field_custom_colors',
		'i365ei_settings',
		'i365ei_customization_section'
	);

	// Visual Enhancements.
	add_settings_section(
		'i365ei_visual_section',
		esc_html__( 'Visual Enhancements', '365i-environment-indicator' ),
		'i365ei_visual_section_callback',
		'i365ei_settings'
	);

	add_settings_field(
		'i365ei_admin_bar_background',
		esc_html__( 'Admin Bar Background', '365i-environment-indicator' ),
		'i365ei_field_admin_bar_background',
		'i365ei_settings',
		'i365ei_visual_section'
	);

	add_settings_field(
		'i365ei_admin_top_border',
		esc_html__( 'Top Border', '365i-environment-indicator' ),
		'i365ei_field_admin_top_border',
		'i365ei_settings',
		'i365ei_visual_section'
	);

	add_settings_field(
		'i365ei_admin_footer_watermark',
		esc_html__( 'Footer Watermark', '365i-environment-indicator' ),
		'i365ei_field_admin_footer_watermark',
		'i365ei_settings',
		'i365ei_visual_section'
	);

	add_settings_field(
		'i365ei_dashboard_widget',
		esc_html__( 'Dashboard Widget', '365i-environment-indicator' ),
		'i365ei_field_dashboard_widget',
		'i365ei_settings',
		'i365ei_visual_section'
	);

	// Visibility Settings.
	add_settings_section(
		'i365ei_visibility_section',
		esc_html__( 'Visibility & Permissions', '365i-environment-indicator' ),
		'i365ei_visibility_section_callback',
		'i365ei_settings'
	);

	add_settings_field(
		'i365ei_role_visibility',
		esc_html__( 'Role-Based Visibility', '365i-environment-indicator' ),
		'i365ei_field_role_visibility',
		'i365ei_settings',
		'i365ei_visibility_section'
	);

	// Export/Import Settings.
	add_settings_section(
		'i365ei_import_export_section',
		esc_html__( 'Export / Import', '365i-environment-indicator' ),
		'i365ei_import_export_section_callback',
		'i365ei_settings'
	);

	add_settings_field(
		'i365ei_export_import',
		esc_html__( 'Settings Transfer', '365i-environment-indicator' ),
		'i365ei_field_export_import',
		'i365ei_settings',
		'i365ei_import_export_section'
	);
}
add_action( 'admin_init', 'i365ei_register_settings' );

/**
 * Add the settings page in the admin menu.
 */
function i365ei_add_settings_page() {
	if ( i365ei_is_network_active() ) {
		return;
	}

	add_options_page(
		esc_html__( 'Environment Indicator', '365i-environment-indicator' ),
		esc_html__( 'Environment Indicator', '365i-environment-indicator' ),
		'manage_options',
		'365i-environment-indicator',
		'i365ei_render_settings_page'
	);
}
add_action( 'admin_menu', 'i365ei_add_settings_page' );

/**
 * Add the settings page in the network admin menu.
 */
function i365ei_add_network_settings_page() {
	if ( ! i365ei_is_network_active() ) {
		return;
	}

	add_submenu_page(
		'settings.php',
		esc_html__( 'Environment Indicator', '365i-environment-indicator' ),
		esc_html__( 'Environment Indicator', '365i-environment-indicator' ),
		'manage_options',
		'365i-environment-indicator',
		'i365ei_render_settings_page'
	);
}
add_action( 'network_admin_menu', 'i365ei_add_network_settings_page' );

/**
 * Add a Settings link on the Plugins page.
 *
 * @param array $links Plugin action links.
 * @return array
 */
function i365ei_add_plugin_action_links( $links ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return $links;
	}

	if ( i365ei_is_network_active() ) {
		return $links;
	}

	$url           = admin_url( 'options-general.php?page=365i-environment-indicator' );
	$settings_link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', '365i-environment-indicator' ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( I365EI_PLUGIN_FILE ), 'i365ei_add_plugin_action_links' );

/**
 * Add a Settings link on the Network Plugins page.
 *
 * @param array $links Plugin action links.
 * @return array
 */
function i365ei_add_network_plugin_action_links( $links ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return $links;
	}

	if ( ! i365ei_is_network_active() ) {
		return $links;
	}

	$url           = network_admin_url( 'settings.php?page=365i-environment-indicator' );
	$settings_link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', '365i-environment-indicator' ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'network_admin_plugin_action_links_' . plugin_basename( I365EI_PLUGIN_FILE ), 'i365ei_add_network_plugin_action_links' );

/**
 * Detection section callback.
 */
function i365ei_detection_section_callback() {
	echo '<p class="description">' . esc_html__( 'Configure how the environment is detected and displayed.', '365i-environment-indicator' ) . '</p>';
}

/**
 * Customization section callback.
 */
function i365ei_customization_section_callback() {
	echo '<p class="description">' . esc_html__( 'Customize the appearance of environment labels and colors.', '365i-environment-indicator' ) . '</p>';
}

/**
 * Visual section callback.
 */
function i365ei_visual_section_callback() {
	echo '<p class="description">' . esc_html__( 'Enable optional visual enhancements to make the environment more prominent.', '365i-environment-indicator' ) . '</p>';
}

/**
 * Visibility section callback.
 */
function i365ei_visibility_section_callback() {
	echo '<p class="description">' . esc_html__( 'Control which user roles can see the environment indicator.', '365i-environment-indicator' ) . '</p>';
}

/**
 * Import/Export section callback.
 */
function i365ei_import_export_section_callback() {
	echo '<p class="description">' . esc_html__( 'Export your settings to use on another site, or import settings from a JSON file.', '365i-environment-indicator' ) . '</p>';
}

/**
 * Sanitize settings input.
 *
 * @param array $input Raw input.
 * @return array
 */
function i365ei_sanitize_settings( $input ) {
	$output  = i365ei_get_default_settings();
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
function i365ei_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$is_network = i365ei_is_network_active() && is_network_admin();

	// Handle import.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified below with check_admin_referer.
	$import_file = isset( $_POST['i365ei_import_file'] ) ? sanitize_text_field( wp_unslash( $_POST['i365ei_import_file'] ) ) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified below.
	if ( ! empty( $import_file ) && isset( $_FILES['i365ei_import_json'] ) ) {
		check_admin_referer( 'i365ei_import_settings' );

		// Validate file upload.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File array keys validated below.
		$file = isset( $_FILES['i365ei_import_json'] ) ? map_deep( wp_unslash( $_FILES['i365ei_import_json'] ), 'sanitize_text_field' ) : array();

		if ( isset( $file['error'] ) && UPLOAD_ERR_OK === (int) $file['error'] && isset( $file['tmp_name'] ) && isset( $file['name'] ) ) {
			// Validate file type.
			$file_type = wp_check_filetype( $file['name'], array( 'json' => 'application/json' ) );
			if ( 'json' !== $file_type['ext'] ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid file type. Please upload a JSON file.', '365i-environment-indicator' ) . '</p></div>';
			} else {
				// Validate temp file exists and is readable.
				$tmp_name = realpath( $file['tmp_name'] );
				if ( $tmp_name && is_readable( $tmp_name ) && 0 === strpos( $tmp_name, sys_get_temp_dir() ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading uploaded temp file.
					$json   = file_get_contents( $tmp_name );
					$result = i365ei_import_settings( $json );
					if ( is_wp_error( $result ) ) {
						echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
					} else {
						echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings imported successfully.', '365i-environment-indicator' ) . '</p></div>';
					}
				} else {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Unable to read uploaded file.', '365i-environment-indicator' ) . '</p></div>';
				}
			}
		}
	}

	// Handle network save.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified below with check_admin_referer.
	$submitted = isset( $_POST['i365ei_settings_submit'] ) ? sanitize_text_field( wp_unslash( $_POST['i365ei_settings_submit'] ) ) : '';

	if ( $is_network && ! empty( $submitted ) ) {
		check_admin_referer( 'i365ei_network_settings' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via i365ei_sanitize_settings.
		$raw_input = isset( $_POST['i365ei_settings'] ) ? wp_unslash( $_POST['i365ei_settings'] ) : array();
		$input     = is_array( $raw_input ) ? $raw_input : array();
		$sanitized = i365ei_sanitize_settings( $input );
		i365ei_update_settings( $sanitized );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', '365i-environment-indicator' ) . '</p></div>';
	}

	echo '<div class="wrap i365ei-settings-wrap">';
	echo '<h1>' . esc_html__( 'Environment Indicator Settings', '365i-environment-indicator' ) . '</h1>';

	// Live preview box.
	$environment = i365ei_get_environment();
	$label       = i365ei_get_environment_label( $environment );
	$color       = i365ei_get_environment_color( $environment );
	$source      = i365ei_get_detection_source();

	echo '<div class="i365ei-preview-box">';
	echo '<div class="i365ei-preview-label">' . esc_html__( 'Live Preview', '365i-environment-indicator' ) . '</div>';
	echo '<div class="i365ei-preview-badge" style="background-color: ' . esc_attr( $color ) . ';">';
	echo '<span>' . esc_html( $label ) . '</span>';
	echo '</div>';
	echo '<div class="i365ei-preview-info">';
	echo '<strong>' . esc_html__( 'Detection:', '365i-environment-indicator' ) . '</strong> ';
	echo '<code>' . esc_html( $source ) . '</code>';
	echo '</div>';
	echo '</div>';

	echo '<form method="post" action="' . esc_url( $is_network ? network_admin_url( 'settings.php?page=365i-environment-indicator' ) : 'options.php' ) . '" class="i365ei-settings-form">';

	if ( $is_network ) {
		wp_nonce_field( 'i365ei_network_settings' );
		echo '<input type="hidden" name="i365ei_settings_submit" value="1" />';
	} else {
		settings_fields( 'i365ei_settings_group' );
	}

	// Render all sections except import/export.
	global $wp_settings_sections, $wp_settings_fields;
	if ( isset( $wp_settings_sections['i365ei_settings'] ) ) {
		foreach ( (array) $wp_settings_sections['i365ei_settings'] as $section ) {
			if ( 'i365ei_import_export_section' === $section['id'] ) {
				continue; // Skip import/export section in main form.
			}
			if ( $section['title'] ) {
				echo '<h2>' . esc_html( $section['title'] ) . '</h2>';
			}
			if ( $section['callback'] ) {
				call_user_func( $section['callback'], $section );
			}
			if ( isset( $wp_settings_fields['i365ei_settings'][ $section['id'] ] ) ) {
				echo '<table class="form-table" role="presentation">';
				do_settings_fields( 'i365ei_settings', $section['id'] );
				echo '</table>';
			}
		}
	}

	submit_button();
	echo '</form>';

	// Render export/import section separately, outside the main form.
	echo '<hr style="margin: 40px 0;" />';
	echo '<h2>' . esc_html__( 'Export / Import', '365i-environment-indicator' ) . '</h2>';
	i365ei_import_export_section_callback( array() );
	i365ei_field_export_import();

	echo '</div>';
}

/**
 * Field: Automatic detection checkbox.
 */
function i365ei_field_auto_detect() {
	$settings = i365ei_get_settings();

	echo '<label>';
	echo '<input type="checkbox" name="i365ei_settings[auto_detect]" value="1" id="i365ei_auto_detect" ' . checked( 1, $settings['auto_detect'], false ) . ' />';
	echo ' ' . esc_html__( 'Enable automatic environment detection', '365i-environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Automatically detect environment using constants (WP_ENVIRONMENT_TYPE, WP_ENV, etc.) or subdomain patterns.', '365i-environment-indicator' ) . '</p>';
}

/**
 * Field: Manual environment selection.
 */
function i365ei_field_manual_environment() {
	$settings     = i365ei_get_settings();
	$current      = isset( $settings['manual_environment'] ) ? $settings['manual_environment'] : 'live';
	$environments = array(
		'dev'     => esc_html__( 'DEV', '365i-environment-indicator' ),
		'staging' => esc_html__( 'STAGING', '365i-environment-indicator' ),
		'live'    => esc_html__( 'LIVE', '365i-environment-indicator' ),
	);

	echo '<div id="i365ei_manual_environment_wrapper">';
	echo '<fieldset>';
	foreach ( $environments as $value => $label ) {
		echo '<label>';
		echo '<input type="radio" name="i365ei_settings[manual_environment]" value="' . esc_attr( $value ) . '" ' . checked( $value, $current, false ) . ' />';
		echo ' ' . esc_html( $label );
		echo '</label><br />';
	}
	echo '</fieldset>';
	echo '<p class="description">' . esc_html__( 'Manually select the environment when automatic detection is disabled.', '365i-environment-indicator' ) . '</p>';
	echo '</div>';
}

/**
 * Field: Currently detected environment display.
 */
function i365ei_field_detected_environment() {
	$environment = i365ei_get_environment();
	$label       = i365ei_get_environment_label( $environment );
	$color       = i365ei_get_environment_color( $environment );

	echo '<div class="i365ei-current-env" style="display: inline-block; background-color: ' . esc_attr( $color ) . '; color: #fff; padding: 8px 16px; border-radius: 4px; font-weight: 700;">';
	echo esc_html( $label );
	echo '</div>';
	echo '<p class="description">' . esc_html__( 'This is the currently active environment as detected or manually selected.', '365i-environment-indicator' ) . '</p>';
}

/**
 * Field: Custom labels toggle and inputs.
 */
function i365ei_field_custom_labels() {
	$settings = i365ei_get_settings();

	echo '<label>';
	echo '<input type="checkbox" name="i365ei_settings[custom_labels]" value="1" id="i365ei_custom_labels" ' . checked( 1, $settings['custom_labels'], false ) . ' />';
	echo ' ' . esc_html__( 'Use custom environment labels', '365i-environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Replace DEV/STAGING/LIVE with your own labels (e.g., LOCAL, UAT, PRODUCTION).', '365i-environment-indicator' ) . '</p>';

	echo '<div id="i365ei_custom_labels_inputs" style="margin-top: 15px;">';
	echo '<table class="form-table" style="margin: 0;">';
	echo '<tr><th scope="row">' . esc_html__( 'DEV Label', '365i-environment-indicator' ) . '</th><td>';
	echo '<input type="text" name="i365ei_settings[label_dev]" value="' . esc_attr( $settings['label_dev'] ) . '" class="regular-text" placeholder="DEV" />';
	echo '</td></tr>';
	echo '<tr><th scope="row">' . esc_html__( 'STAGING Label', '365i-environment-indicator' ) . '</th><td>';
	echo '<input type="text" name="i365ei_settings[label_staging]" value="' . esc_attr( $settings['label_staging'] ) . '" class="regular-text" placeholder="STAGING" />';
	echo '</td></tr>';
	echo '<tr><th scope="row">' . esc_html__( 'LIVE Label', '365i-environment-indicator' ) . '</th><td>';
	echo '<input type="text" name="i365ei_settings[label_live]" value="' . esc_attr( $settings['label_live'] ) . '" class="regular-text" placeholder="LIVE" />';
	echo '</td></tr>';
	echo '</table>';
	echo '</div>';
}

/**
 * Field: Custom colors toggle and pickers.
 */
function i365ei_field_custom_colors() {
	$settings = i365ei_get_settings();

	echo '<label>';
	echo '<input type="checkbox" name="i365ei_settings[custom_colors]" value="1" id="i365ei_custom_colors" ' . checked( 1, $settings['custom_colors'], false ) . ' />';
	echo ' ' . esc_html__( 'Use custom environment colors', '365i-environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Customize the colors for each environment to match your branding or preferences.', '365i-environment-indicator' ) . '</p>';

	echo '<div id="i365ei_custom_colors_inputs" style="margin-top: 15px;">';
	echo '<table class="form-table" style="margin: 0;">';
	echo '<tr><th scope="row">' . esc_html__( 'DEV Color', '365i-environment-indicator' ) . '</th><td>';
	echo '<input type="text" name="i365ei_settings[color_dev]" value="' . esc_attr( $settings['color_dev'] ) . '" class="i365ei-color-picker" data-default-color="#2e8b57" />';
	echo '</td></tr>';
	echo '<tr><th scope="row">' . esc_html__( 'STAGING Color', '365i-environment-indicator' ) . '</th><td>';
	echo '<input type="text" name="i365ei_settings[color_staging]" value="' . esc_attr( $settings['color_staging'] ) . '" class="i365ei-color-picker" data-default-color="#f39c12" />';
	echo '</td></tr>';
	echo '<tr><th scope="row">' . esc_html__( 'LIVE Color', '365i-environment-indicator' ) . '</th><td>';
	echo '<input type="text" name="i365ei_settings[color_live]" value="' . esc_attr( $settings['color_live'] ) . '" class="i365ei-color-picker" data-default-color="#c0392b" />';
	echo '</td></tr>';
	echo '</table>';
	echo '</div>';
}

/**
 * Field: Admin bar background toggle.
 */
function i365ei_field_admin_bar_background() {
	$settings = i365ei_get_settings();

	echo '<label>';
	echo '<input type="checkbox" name="i365ei_settings[admin_bar_background]" value="1" ' . checked( 1, $settings['admin_bar_background'], false ) . ' />';
	echo ' ' . esc_html__( 'Color the entire admin bar background', '365i-environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Makes the environment highly visible by coloring the entire admin bar (very prominent).', '365i-environment-indicator' ) . '</p>';
}

/**
 * Field: Admin top border toggle.
 */
function i365ei_field_admin_top_border() {
	$settings = i365ei_get_settings();

	echo '<label>';
	echo '<input type="checkbox" name="i365ei_settings[admin_top_border]" value="1" ' . checked( 1, $settings['admin_top_border'], false ) . ' />';
	echo ' ' . esc_html__( 'Show colored top border in admin', '365i-environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Adds a 4px colored border at the top of all wp-admin screens.', '365i-environment-indicator' ) . '</p>';
}

/**
 * Field: Admin footer watermark toggle.
 */
function i365ei_field_admin_footer_watermark() {
	$settings = i365ei_get_settings();

	echo '<label>';
	echo '<input type="checkbox" name="i365ei_settings[admin_footer_watermark]" value="1" ' . checked( 1, $settings['admin_footer_watermark'], false ) . ' />';
	echo ' ' . esc_html__( 'Show environment label in admin footer', '365i-environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Displays the environment name in the admin footer area.', '365i-environment-indicator' ) . '</p>';
}

/**
 * Field: Dashboard widget toggle.
 */
function i365ei_field_dashboard_widget() {
	$settings = i365ei_get_settings();

	echo '<label>';
	echo '<input type="checkbox" name="i365ei_settings[dashboard_widget]" value="1" ' . checked( 1, $settings['dashboard_widget'], false ) . ' />';
	echo ' ' . esc_html__( 'Show environment status dashboard widget', '365i-environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Adds a dashboard widget showing environment info, detection method, and system details.', '365i-environment-indicator' ) . '</p>';
}

/**
 * Field: Role visibility settings.
 */
function i365ei_field_role_visibility() {
	$settings = i365ei_get_settings();
	global $wp_roles;

	echo '<label>';
	echo '<input type="checkbox" name="i365ei_settings[role_visibility]" value="1" id="i365ei_role_visibility" ' . checked( 1, $settings['role_visibility'], false ) . ' />';
	echo ' ' . esc_html__( 'Restrict visibility to specific roles', '365i-environment-indicator' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Only show the environment indicator to users with selected roles.', '365i-environment-indicator' ) . '</p>';

	echo '<div id="i365ei_role_visibility_inputs" style="margin-top: 15px;">';
	echo '<fieldset>';

	$visible_roles = isset( $settings['visible_roles'] ) ? $settings['visible_roles'] : array( 'administrator' );

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}

	foreach ( $wp_roles->get_names() as $role_slug => $role_name ) {
		$checked = in_array( $role_slug, $visible_roles, true ) ? 'checked' : '';
		echo '<label style="display: block; margin-bottom: 5px;">';
		echo '<input type="checkbox" name="i365ei_settings[visible_roles][]" value="' . esc_attr( $role_slug ) . '" ' . esc_attr( $checked ) . ' />';
		echo ' ' . esc_html( translate_user_role( $role_name ) );
		echo '</label>';
	}

	echo '</fieldset>';
	echo '</div>';
}

/**
 * Field: Export/Import functionality.
 */
function i365ei_field_export_import() {
	$is_network = i365ei_is_network_active() && is_network_admin();
	$form_action = $is_network ? network_admin_url( 'settings.php?page=365i-environment-indicator' ) : admin_url( 'options-general.php?page=365i-environment-indicator' );

	echo '<div class="i365ei-export-import-section" style="max-width: 800px;">';

	// Export.
	echo '<h3>' . esc_html__( 'Export Settings', '365i-environment-indicator' ) . '</h3>';
	echo '<p>' . esc_html__( 'Export your current settings as a JSON file to use on another site.', '365i-environment-indicator' ) . '</p>';
	echo '<button type="button" id="i365ei_export_settings" class="button button-secondary">' . esc_html__( 'Download Settings', '365i-environment-indicator' ) . '</button>';
	echo '<textarea id="i365ei_export_data" style="display:none;">' . esc_textarea( i365ei_export_settings() ) . '</textarea>';

	// Import.
	echo '<h3 style="margin-top: 35px;">' . esc_html__( 'Import Settings', '365i-environment-indicator' ) . '</h3>';
	echo '<p>' . esc_html__( 'Import settings from a JSON file. This will overwrite your current settings.', '365i-environment-indicator' ) . '</p>';
	echo '<form method="post" action="' . esc_url( $form_action ) . '" enctype="multipart/form-data" style="margin-top: 10px;">';
	wp_nonce_field( 'i365ei_import_settings' );
	echo '<input type="file" name="i365ei_import_json" accept=".json" required style="margin-right: 10px;" />';
	echo '<input type="hidden" name="i365ei_import_file" value="1" />';
	echo '<button type="submit" class="button button-secondary">' . esc_html__( 'Import Settings', '365i-environment-indicator' ) . '</button>';
	echo '</form>';

	echo '</div>';
}
