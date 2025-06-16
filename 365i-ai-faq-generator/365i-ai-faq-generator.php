<?php
/**
 * Plugin Name: 365i AI FAQ Generator
 * Plugin URI: https://365i.co.uk/plugins/ai-faq-generator
 * Description: Frontend-only AI-powered FAQ generation tool with 6 Cloudflare workers integration. Clients use shortcodes to generate SEO-optimized FAQ content directly on pages.
 * Version: 2.0.0
 * Author: 365i
 * Author URI: https://365i.co.uk
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 365i-ai-faq-generator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package AI_FAQ_Generator
 * @version 2.0.0
 * @author 365i
 * @copyright 2024 365i
 * @license GPL v2 or later
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'AI_FAQ_GEN_VERSION', '2.0.0' );
define( 'AI_FAQ_GEN_FILE', __FILE__ );
define( 'AI_FAQ_GEN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AI_FAQ_GEN_URL', plugin_dir_url( __FILE__ ) );
define( 'AI_FAQ_GEN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin initialization function.
 * 
 * Loads the main plugin class and initializes the plugin.
 * 
 * @since 2.0.0
 */
function ai_faq_gen_init() {
	// Load the main plugin class.
	require_once AI_FAQ_GEN_DIR . 'includes/class-ai-faq-core.php';
	
	// Initialize the plugin.
	$plugin = new AI_FAQ_Core();
	$plugin->init();
}

/**
 * Plugin activation hook callback.
 * 
 * Runs when the plugin is activated.
 * 
 * @since 2.0.0
 */
function ai_faq_gen_activate() {
	// Load the main plugin class.
	require_once AI_FAQ_GEN_DIR . 'includes/class-ai-faq-core.php';
	
	// Run activation.
	AI_FAQ_Core::activate();
}

/**
 * Plugin deactivation hook callback.
 * 
 * Runs when the plugin is deactivated.
 * 
 * @since 2.0.0
 */
function ai_faq_gen_deactivate() {
	// Load the main plugin class.
	require_once AI_FAQ_GEN_DIR . 'includes/class-ai-faq-core.php';
	
	// Run deactivation.
	AI_FAQ_Core::deactivate();
}

/**
 * Plugin uninstall hook callback.
 * 
 * Runs when the plugin is uninstalled.
 * 
 * @since 2.0.0
 */
function ai_faq_gen_uninstall() {
	// Load the main plugin class.
	require_once AI_FAQ_GEN_DIR . 'includes/class-ai-faq-core.php';
	
	// Run uninstall.
	AI_FAQ_Core::uninstall();
}

/**
 * Check minimum requirements.
 * 
 * Checks if minimum WordPress and PHP versions are met.
 * 
 * @since 2.0.0
 * @return bool True if requirements met, false otherwise.
 */
function ai_faq_gen_check_requirements() {
	global $wp_version;
	
	$min_wp = '5.0';
	$min_php = '7.4';
	
	// Check WordPress version.
	if ( version_compare( $wp_version, $min_wp, '<' ) ) {
		add_action( 'admin_notices', function() use ( $min_wp ) {
			echo '<div class="notice notice-error"><p>';
			printf(
				/* translators: %s: minimum WordPress version */
				esc_html__( '365i AI FAQ Generator requires WordPress %s or higher.', '365i-ai-faq-generator' ),
				esc_html( $min_wp )
			);
			echo '</p></div>';
		} );
		return false;
	}
	
	// Check PHP version.
	if ( version_compare( PHP_VERSION, $min_php, '<' ) ) {
		add_action( 'admin_notices', function() use ( $min_php ) {
			echo '<div class="notice notice-error"><p>';
			printf(
				/* translators: %s: minimum PHP version */
				esc_html__( '365i AI FAQ Generator requires PHP %s or higher.', '365i-ai-faq-generator' ),
				esc_html( $min_php )
			);
			echo '</p></div>';
		} );
		return false;
	}
	
	return true;
}

// Check requirements before proceeding.
if ( ! ai_faq_gen_check_requirements() ) {
	return;
}

// Register hooks.
register_activation_hook( __FILE__, 'ai_faq_gen_activate' );
register_deactivation_hook( __FILE__, 'ai_faq_gen_deactivate' );
register_uninstall_hook( __FILE__, 'ai_faq_gen_uninstall' );

// Initialize plugin.
add_action( 'plugins_loaded', 'ai_faq_gen_init' );