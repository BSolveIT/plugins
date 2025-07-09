<?php
/**
 * Plugin Name: Quick FAQ Markup
 * Plugin URI: https://github.com/BSolveIT/plugins
 * Description: A WordPress plugin for creating and managing FAQ sections with drag-and-drop reordering, multiple display styles, and JSON-LD schema markup support. Supports shortcodes, accessibility features, and direct linking.
 * Version: 2.0.1
 * Author: BSolveIT
 * Author URI: https://bsolveit.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: quick-faq-markup
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.8
 * Requires PHP: 8.0
 * Network: false
 *
 * @package Quick_FAQ_Markup
 * @since 1.0.0
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Currently plugin version.
 */
define( 'QUICK_FAQ_MARKUP_VERSION', '2.0.1' );

/**
 * Plugin name for internal use.
 */
define( 'QUICK_FAQ_MARKUP_PLUGIN_NAME', 'quick-faq-markup' );

/**
 * Plugin directory path.
 */
define( 'QUICK_FAQ_MARKUP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'QUICK_FAQ_MARKUP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'QUICK_FAQ_MARKUP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Minimum WordPress version required.
 */
define( 'QUICK_FAQ_MARKUP_MIN_WP_VERSION', '6.0' );

/**
 * Minimum PHP version required.
 */
define( 'QUICK_FAQ_MARKUP_MIN_PHP_VERSION', '8.0' );

/**
 * Check WordPress and PHP version requirements.
 * 
 * @since 1.0.0
 * @return bool True if requirements are met, false otherwise.
 */
function quick_faq_markup_check_requirements() {
	global $wp_version;

	// Check WordPress version
	if ( version_compare( $wp_version, QUICK_FAQ_MARKUP_MIN_WP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'quick_faq_markup_wp_version_notice' );
		return false;
	}

	// Check PHP version
	if ( version_compare( PHP_VERSION, QUICK_FAQ_MARKUP_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'quick_faq_markup_php_version_notice' );
		return false;
	}

	return true;
}

/**
 * Display WordPress version requirement notice.
 * 
 * @since 1.0.0
 */
function quick_faq_markup_wp_version_notice() {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		sprintf(
			/* translators: 1: Plugin name, 2: Required WordPress version, 3: Current WordPress version */
			esc_html__( '%1$s requires WordPress version %2$s or higher. You are running version %3$s.', 'quick-faq-markup' ),
			'<strong>' . esc_html__( 'Quick FAQ Markup', 'quick-faq-markup' ) . '</strong>',
			QUICK_FAQ_MARKUP_MIN_WP_VERSION,
			$GLOBALS['wp_version']
		)
	);
}

/**
 * Display PHP version requirement notice.
 * 
 * @since 1.0.0
 */
function quick_faq_markup_php_version_notice() {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		sprintf(
			/* translators: 1: Plugin name, 2: Required PHP version, 3: Current PHP version */
			esc_html__( '%1$s requires PHP version %2$s or higher. You are running version %3$s.', 'quick-faq-markup' ),
			'<strong>' . esc_html__( 'Quick FAQ Markup', 'quick-faq-markup' ) . '</strong>',
			QUICK_FAQ_MARKUP_MIN_PHP_VERSION,
			PHP_VERSION
		)
	);
}

/**
 * The code that runs during plugin activation.
 * 
 * @since 1.0.0
 */
function quick_faq_markup_activate() {
	// Check requirements before activation
	if ( ! quick_faq_markup_check_requirements() ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( esc_html__( 'Plugin activation failed due to unmet requirements.', 'quick-faq-markup' ) );
	}

	// Include the main plugin class
	require_once QUICK_FAQ_MARKUP_PLUGIN_DIR . 'includes/class-quick-faq-markup.php';

	// Run activation procedures
	Quick_FAQ_Markup::activate();
}

/**
 * The code that runs during plugin deactivation.
 * 
 * @since 1.0.0
 */
function quick_faq_markup_deactivate() {
	// Include the main plugin class
	require_once QUICK_FAQ_MARKUP_PLUGIN_DIR . 'includes/class-quick-faq-markup.php';

	// Run deactivation procedures
	Quick_FAQ_Markup::deactivate();
}

/**
 * Register activation and deactivation hooks.
 */
register_activation_hook( __FILE__, 'quick_faq_markup_activate' );
register_deactivation_hook( __FILE__, 'quick_faq_markup_deactivate' );

/**
 * Global plugin instance for cross-class access.
 *
 * @since 1.0.0
 * @global Quick_FAQ_Markup $quick_faq_markup
 */
global $quick_faq_markup;

/**
 * Main plugin execution.
 *
 * @since 1.0.0
 */
function quick_faq_markup_run() {
	global $quick_faq_markup;

	// Check requirements
	if ( ! quick_faq_markup_check_requirements() ) {
		return;
	}

	// Include the main plugin class
	require_once QUICK_FAQ_MARKUP_PLUGIN_DIR . 'includes/class-quick-faq-markup.php';

	// Initialize the plugin and store globally
	$quick_faq_markup = new Quick_FAQ_Markup();
	$quick_faq_markup->run();
}

/**
 * Initialize the plugin.
 */
add_action( 'plugins_loaded', 'quick_faq_markup_run' );

/**
 * Add plugin action links.
 * 
 * @since 1.0.0
 * @param array $links Current plugin action links.
 * @return array Modified plugin action links.
 */
function quick_faq_markup_add_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'edit.php?post_type=qfm_faq&page=quick-faq-markup-settings' ) ),
		esc_html__( 'Settings', 'quick-faq-markup' )
	);
	
	array_unshift( $links, $settings_link );
	
	return $links;
}

add_filter( 'plugin_action_links_' . QUICK_FAQ_MARKUP_PLUGIN_BASENAME, 'quick_faq_markup_add_action_links' );

/**
 * Centralized logging function.
 *
 * @since 1.0.0
 * @param string $message Log message.
 * @param string $level Log level (info, warning, error).
 * @param array  $context Additional context data.
 */
function quick_faq_markup_log( $message, $level = 'info', $context = array() ) {
	// Check if centralized logging is available
	if ( function_exists( 'centralized_log' ) ) {
		centralized_log( 'QFM: ' . $message, $level, $context );
		return;
	}

	// Fallback to WordPress error_log if WP_DEBUG is enabled
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$log_message = sprintf( '[QFM-%s] %s', strtoupper( $level ), $message );
		if ( ! empty( $context ) ) {
			$log_message .= ' | Context: ' . wp_json_encode( $context );
		}
		error_log( $log_message );
	}
}