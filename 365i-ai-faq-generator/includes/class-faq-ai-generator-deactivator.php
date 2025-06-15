<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://365i.com
 * @since      1.0.0
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/includes
 * @author     365i
 */
class FAQ_AI_Generator_Deactivator {

	/**
	 * Cleanup on plugin deactivation.
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Remove custom capabilities
		self::remove_capabilities();
		
		// We intentionally keep settings and data to ensure 
		// users don't lose their FAQs when temporarily deactivating
	}
	
	/**
	 * Remove custom capabilities when plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function remove_capabilities() {
		// Remove custom capabilities from roles
		$role = get_role('administrator');
		if ($role) {
			$role->remove_cap('manage_faq_ai_settings');
		}
	}
}