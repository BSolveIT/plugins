<?php
/**
 * AI FAQ Generator
 *
 * @package           FAQAIGenerator
 * @author            365i
 * @copyright         2025 365i
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       AI FAQ Generator
 * Plugin URI:        https://365i.com/faq-ai-generator
 * Description:       Generate professional FAQ content using AI with schema generation capabilities, rich text editing, and drag-and-drop functionality.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            365i
 * Author URI:        https://365i.com
 * Text Domain:       faq-ai-generator
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('FAQ_AI_GENERATOR_VERSION', '1.0.0');

/**
 * Plugin base path.
 */
define('FAQ_AI_GENERATOR_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin base URL.
 */
define('FAQ_AI_GENERATOR_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-faq-ai-generator-activator.php
 */
function activate_faq_ai_generator() {
    require_once FAQ_AI_GENERATOR_PATH . 'includes/class-faq-ai-generator-activator.php';
    FAQ_AI_Generator_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-faq-ai-generator-deactivator.php
 */
function deactivate_faq_ai_generator() {
    require_once FAQ_AI_GENERATOR_PATH . 'includes/class-faq-ai-generator-deactivator.php';
    FAQ_AI_Generator_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_faq_ai_generator');
register_deactivation_hook(__FILE__, 'deactivate_faq_ai_generator');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require FAQ_AI_GENERATOR_PATH . 'includes/class-faq-ai-generator.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_faq_ai_generator() {
    $plugin = new FAQ_AI_Generator();
    $plugin->run();
}

run_faq_ai_generator();