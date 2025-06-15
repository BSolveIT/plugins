<?php
/**
 * Fired during plugin activation
 *
 * @link       https://365i.com
 * @since      1.0.0
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/includes
 * @author     365i
 */
class FAQ_AI_Generator_Activator {

	/**
	 * Initialize plugin settings on activation.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Set default worker URLs and rate limits
		$default_workers = array(
			'question' => array(
				'url' => 'https://faq-realtime-assistant-worker.winter-cake-bf57.workers.dev',
				'rate_limit' => 100,
				'cooldown' => 3,
				'enabled' => true,
			),
			'answer' => array(
				'url' => 'https://faq-answer-generator-worker.winter-cake-bf57.workers.dev',
				'rate_limit' => 50,
				'cooldown' => 3,
				'enabled' => true,
			),
			'seo' => array(
				'url' => 'https://faq-seo-analyzer-worker.winter-cake-bf57.workers.dev',
				'rate_limit' => 75,
				'cooldown' => 5,
				'enabled' => true,
			),
			'enhance' => array(
				'url' => 'https://faq-enhancement-worker.winter-cake-bf57.workers.dev',
				'rate_limit' => 50,
				'cooldown' => 5,
				'enabled' => true,
			),
			'extract' => array(
				'url' => 'https://url-to-faq-generator-worker.winter-cake-bf57.workers.dev',
				'rate_limit' => 20,
				'cooldown' => 10,
				'enabled' => true,
			),
			'topic' => array(
				'url' => 'https://faq-topic-generator-worker.winter-cake-bf57.workers.dev',
				'rate_limit' => 30,
				'cooldown' => 5,
				'enabled' => true,
			),
			'validate' => array(
				'url' => 'https://faq-content-validator-worker.winter-cake-bf57.workers.dev',
				'rate_limit' => 40,
				'cooldown' => 3,
				'enabled' => true,
			),
		);

		// Default settings
		$default_settings = array(
			'faq_page_url' => '',
			'default_anchor_format' => 'question',
			'default_schema_format' => 'json-ld',
			'auto_save_interval' => 3,
			'debug_mode' => false,
			'usage_analytics' => true,
			'performance_monitoring' => true,
		);
		
		// Default API key (empty, user must enter their own)
		$default_api_key = '';

		// Add options only if they don't exist
		if (!get_option('faq_ai_generator_workers')) {
			update_option('faq_ai_generator_workers', $default_workers);
		}

		if (!get_option('faq_ai_generator_settings')) {
			update_option('faq_ai_generator_settings', $default_settings);
		}
		
		if (!get_option('faq_ai_generator_api_key')) {
			update_option('faq_ai_generator_api_key', $default_api_key);
		}

		// Create custom capabilities
		self::create_capabilities();
	}

	/**
	 * Create custom capabilities for the plugin.
	 *
	 * @since    1.0.0
	 */
	private static function create_capabilities() {
		// Add custom capability to administrators
		$role = get_role('administrator');
		if ($role) {
			$role->add_cap('manage_faq_ai_settings');
		}
	}
}