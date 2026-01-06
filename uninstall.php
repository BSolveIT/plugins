<?php
/**
 * Uninstall script for Environment Indicator.
 *
 * Removes all plugin data when the plugin is deleted.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$i365ei_option_name = 'i365ei_settings';

// Remove single-site settings.
delete_option( $i365ei_option_name );

// Remove network-wide settings if multisite.
if ( is_multisite() ) {
	delete_site_option( $i365ei_option_name );
}
