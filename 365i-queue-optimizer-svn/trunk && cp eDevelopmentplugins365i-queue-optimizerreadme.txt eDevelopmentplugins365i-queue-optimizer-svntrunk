<?php
/**
 * Plugin Name: 365i Queue Optimizer
 * Plugin URI: https://www.365i.co.uk/blog/2025/04/20/fix-wordpress-6-8-slow-image-uploads-with-365i-queue-optimizer/
 * Description: A lightweight WordPress plugin to optimize ActionScheduler queue processing for faster image optimization and background tasks.
 * Version: 1.6.0
 * Author: 365i
 * Author URI: https://www.mcneece.com/author/mark-mcneece/
 * Text Domain: 365i-queue-optimizer
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.9
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'QUEUE_OPTIMIZER_VERSION', '1.6.0' );
define( 'QUEUE_OPTIMIZER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QUEUE_OPTIMIZER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QUEUE_OPTIMIZER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'QUEUE_OPTIMIZER_MIN_WP_VERSION', '5.8' );

// Load the main plugin class.
require_once QUEUE_OPTIMIZER_PLUGIN_DIR . 'src/class-queue-optimizer-main.php';

// Initialize the plugin.
Queue_Optimizer_Main::get_instance();
