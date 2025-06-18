<?php
/**
 * Core plugin class for 365i AI FAQ Generator.
 * 
 * This class handles the main plugin functionality including initialization,
 * loading dependencies, and coordinating between different components.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Core
 * @since 2.0.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin core class.
 * 
 * Handles plugin initialization, dependency loading, and component coordination.
 * 
 * @since 2.0.0
 */
class AI_FAQ_Core {

	/**
	 * Plugin version.
	 * 
	 * @since 2.0.0
	 * @var string
	 */
	public $version;

	/**
	 * Plugin name.
	 * 
	 * @since 2.0.0
	 * @var string
	 */
	public $plugin_name;

	/**
	 * Admin class instance.
	 * 
	 * @since 2.0.0
	 * @var AI_FAQ_Admin
	 */
	public $admin;

	/**
	 * Frontend class instance.
	 * 
	 * @since 2.0.0
	 * @var AI_FAQ_Frontend
	 */
	public $frontend;

	/**
	 * Workers class instance.
	 * 
	 * @since 2.0.0
	 * @var AI_FAQ_Workers
	 */
	public $workers;

	/**
	 * Constructor.
	 * 
	 * Initialize the plugin properties.
	 * 
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->version = AI_FAQ_GEN_VERSION;
		$this->plugin_name = '365i AI FAQ Generator';
	}

	/**
	 * Initialize the plugin.
	 * 
	 * Load dependencies, set up hooks, and initialize components.
	 * 
	 * @since 2.0.0
	 */
	public function init() {
		// Load dependencies.
		$this->load_dependencies();
		
		// Initialize components.
		$this->init_components();
		
		// Set up hooks.
		$this->define_hooks();
		
		// Load text domain for internationalization.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load required dependencies.
	 * 
	 * Include all necessary class files.
	 * 
	 * @since 2.0.0
	 */
	private function load_dependencies() {
		// Load admin class.
		require_once AI_FAQ_GEN_DIR . 'includes/class-ai-faq-admin.php';
		
		// Load frontend class.
		require_once AI_FAQ_GEN_DIR . 'includes/class-ai-faq-frontend.php';
		
		// Load workers class.
		require_once AI_FAQ_GEN_DIR . 'includes/class-ai-faq-workers.php';
	}

	/**
	 * Initialize plugin components.
	 * 
	 * Create instances of main plugin components.
	 * 
	 * @since 2.0.0
	 */
	private function init_components() {
		// Initialize admin component.
		$this->admin = new AI_FAQ_Admin();
		
		// Initialize frontend component.
		$this->frontend = new AI_FAQ_Frontend();
		
		// Initialize workers component.
		$this->workers = new AI_FAQ_Workers();
	}

	/**
	 * Define plugin hooks.
	 *
	 * Set up WordPress action and filter hooks.
	 *
	 * @since 2.0.0
	 */
	private function define_hooks() {
		// Initialize admin hooks if in admin or doing AJAX.
		// AJAX handlers must be registered for admin-ajax.php requests.
		// Using DOING_AJAX constant as it's available earlier than wp_doing_ajax().
		if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			$this->admin->init();
		}
		
		// Initialize frontend hooks.
		$this->frontend->init();
		
		// Initialize worker hooks.
		$this->workers->init();
	}

	/**
	 * Load plugin text domain.
	 * 
	 * Load translations for internationalization.
	 * 
	 * @since 2.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'365i-ai-faq-generator',
			false,
			dirname( AI_FAQ_GEN_BASENAME ) . '/languages/'
		);
	}

	/**
	 * Plugin activation hook.
	 * 
	 * Runs when the plugin is activated. Sets up default options.
	 * 
	 * @since 2.0.0
	 */
	public static function activate() {
		// Set default plugin options.
		$default_options = array(
			'version' => AI_FAQ_GEN_VERSION,
			'workers' => array(
				'question_generator' => array(
					'url' => 'https://faq-realtime-assistant-worker.winter-cake-bf57.workers.dev',
					'enabled' => true,
					'rate_limit' => 100,
				),
				'answer_generator' => array(
					'url' => 'https://faq-answer-generator-worker.winter-cake-bf57.workers.dev',
					'enabled' => true,
					'rate_limit' => 50,
				),
				'faq_enhancer' => array(
					'url' => 'https://faq-enhancement-worker.winter-cake-bf57.workers.dev',
					'enabled' => true,
					'rate_limit' => 50,
				),
				'seo_analyzer' => array(
					'url' => 'https://faq-seo-analyzer-worker.winter-cake-bf57.workers.dev',
					'enabled' => true,
					'rate_limit' => 75,
				),
				'faq_extractor' => array(
					'url' => 'https://url-to-faq-generator-worker.winter-cake-bf57.workers.dev',
					'enabled' => true,
					'rate_limit' => 100,
				),
				'topic_generator' => array(
					'url' => 'https://faq-proxy-fetch.winter-cake-bf57.workers.dev',
					'enabled' => true,
					'rate_limit' => 10,
				),
			),
			'settings' => array(
				'default_faq_count' => 12,
				'auto_save_interval' => 3,
				'debug_mode' => false,
			),
		);

		// Save default options if they don't exist.
		if ( ! get_option( 'ai_faq_gen_options' ) ) {
			add_option( 'ai_faq_gen_options', $default_options );
		}

		// Set activation flag for redirect to settings page.
		add_option( 'ai_faq_gen_activation_redirect', true );
		
		/**
		 * Fires after plugin activation.
		 * 
		 * @since 2.0.0
		 */
		do_action( 'ai_faq_gen_activated' );
	}

	/**
	 * Plugin deactivation hook.
	 * 
	 * Runs when the plugin is deactivated.
	 * 
	 * @since 2.0.0
	 */
	public static function deactivate() {
		// Clear any scheduled events.
		wp_clear_scheduled_hook( 'ai_faq_gen_cleanup' );
		
		// Remove activation redirect flag.
		delete_option( 'ai_faq_gen_activation_redirect' );
		
		/**
		 * Fires after plugin deactivation.
		 * 
		 * @since 2.0.0
		 */
		do_action( 'ai_faq_gen_deactivated' );
	}

	/**
	 * Plugin uninstall hook.
	 * 
	 * Runs when the plugin is uninstalled. Removes all plugin data.
	 * 
	 * @since 2.0.0
	 */
	public static function uninstall() {
		// Remove plugin options.
		delete_option( 'ai_faq_gen_options' );
		delete_option( 'ai_faq_gen_activation_redirect' );
		
		// Remove any transients.
		delete_transient( 'ai_faq_gen_worker_status' );
		
		/**
		 * Fires after plugin uninstall.
		 * 
		 * @since 2.0.0
		 */
		do_action( 'ai_faq_gen_uninstalled' );
	}

	/**
	 * Get plugin options.
	 * 
	 * Retrieve plugin options with defaults.
	 * 
	 * @since 2.0.0
	 * @param string $key Optional. Specific option key to retrieve.
	 * @return mixed Plugin options or specific option value.
	 */
	public function get_options( $key = '' ) {
		$options = get_option( 'ai_faq_gen_options', array() );
		
		if ( ! empty( $key ) ) {
			return isset( $options[ $key ] ) ? $options[ $key ] : null;
		}
		
		return $options;
	}

	/**
	 * Update plugin options.
	 * 
	 * Update plugin options or specific option key.
	 * 
	 * @since 2.0.0
	 * @param string|array $key Option key or array of options.
	 * @param mixed        $value Option value if key is string.
	 * @return bool True if updated successfully, false otherwise.
	 */
	public function update_options( $key, $value = null ) {
		$options = $this->get_options();
		
		if ( is_array( $key ) ) {
			// Update multiple options.
			$options = array_merge( $options, $key );
		} else {
			// Update single option.
			$options[ $key ] = $value;
		}
		
		return update_option( 'ai_faq_gen_options', $options );
	}

	/**
	 * Get worker configuration.
	 * 
	 * Retrieve configuration for a specific worker.
	 * 
	 * @since 2.0.0
	 * @param string $worker_name Worker name.
	 * @return array|null Worker configuration or null if not found.
	 */
	public function get_worker_config( $worker_name ) {
		$workers = $this->get_options( 'workers' );
		
		return isset( $workers[ $worker_name ] ) ? $workers[ $worker_name ] : null;
	}

	/**
	 * Update worker configuration.
	 * 
	 * Update configuration for a specific worker.
	 * 
	 * @since 2.0.0
	 * @param string $worker_name Worker name.
	 * @param array  $config Worker configuration.
	 * @return bool True if updated successfully, false otherwise.
	 */
	public function update_worker_config( $worker_name, $config ) {
		$workers = $this->get_options( 'workers' );
		$workers[ $worker_name ] = $config;
		
		return $this->update_options( 'workers', $workers );
	}
}