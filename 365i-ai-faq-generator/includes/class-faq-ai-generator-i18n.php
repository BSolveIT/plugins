<?php
/**
 * Define the internationalization functionality
 *
 * @link       https://365i.com
 * @since      1.0.0
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/includes
 * @author     365i
 */
class FAQ_AI_Generator_i18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'faq-ai-generator',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}