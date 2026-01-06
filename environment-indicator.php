<?php
/**
 * Plugin Name: 365i Environment Indicator
 * Description: Displays a prominent admin bar label for the current environment: DEV, STAGING, or LIVE.
 * Version: 1.0.6
 * Author: Mark McNeece
 * Author URI: https://www.365i.co.uk/author/mark-mcneece/
 * Text Domain: 365i-environment-indicator
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'I365EI_VERSION', '1.0.6' );
define( 'I365EI_PLUGIN_FILE', __FILE__ );
define( 'I365EI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'I365EI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once I365EI_PLUGIN_DIR . 'includes/helpers.php';
require_once I365EI_PLUGIN_DIR . 'includes/detection.php';
require_once I365EI_PLUGIN_DIR . 'includes/admin-bar.php';
require_once I365EI_PLUGIN_DIR . 'includes/settings.php';
require_once I365EI_PLUGIN_DIR . 'includes/dashboard-widget.php';
