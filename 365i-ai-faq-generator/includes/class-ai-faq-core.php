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
	 * Settings handler instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Settings_Handler
	 */
	public $settings_handler;

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
		// Load settings handler first (needed by other components).
		require_once AI_FAQ_GEN_DIR . 'includes/class-ai-faq-settings-handler.php';
		
		// Load logging system early (needed by all components).
		require_once AI_FAQ_GEN_DIR . 'includes/logging/class-ai-faq-365i-logger.php';
		require_once AI_FAQ_GEN_DIR . 'includes/logging/functions-logging.php';
		
		// Load admin class.
		require_once AI_FAQ_GEN_DIR . 'includes/admin/class-ai-faq-admin.php';
		
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
		// Initialize settings handler first (needed by other components).
		$this->settings_handler = new AI_FAQ_Settings_Handler();
		
		// DEBUG: Log which admin class we're about to instantiate
		ai_faq_log_debug('AI_FAQ_Core: About to instantiate AI_FAQ_Admin class');
		ai_faq_log_debug('AI_FAQ_Core: AI_FAQ_Admin class exists: ' . (class_exists('AI_FAQ_Admin') ? 'YES' : 'NO'));
		
		// Initialize admin component.
		$this->admin = new AI_FAQ_Admin();
		
		// DEBUG: Log admin class type and methods
		ai_faq_log_debug('AI_FAQ_Core: Admin instance created - Class: ' . get_class($this->admin));
		ai_faq_log_debug('AI_FAQ_Core: Admin has init method: ' . (method_exists($this->admin, 'init') ? 'YES' : 'NO'));
		ai_faq_log_debug('AI_FAQ_Core: Admin class methods: ' . implode(', ', get_class_methods($this->admin)));
		
		// Initialize frontend component.
		$this->frontend = new AI_FAQ_Frontend();
		
		// Initialize workers component.
		$this->workers = new AI_FAQ_Workers();
		
		// Set up logging cache clearing hook.
		
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
			// DEBUG: Log admin initialization context
			ai_faq_log_debug('AI_FAQ_Core: Calling admin->init() - is_admin: ' . (is_admin() ? 'YES' : 'NO') . ', DOING_AJAX: ' . (defined('DOING_AJAX') && DOING_AJAX ? 'YES' : 'NO'));
			ai_faq_log_debug('AI_FAQ_Core: Current action: ' . (isset($_POST['action']) ? $_POST['action'] : 'none'));
			
			$this->admin->init();
			
			// DEBUG: Log if admin init completed
			ai_faq_log_debug('AI_FAQ_Core: Admin init completed');
		}
		
		// Initialize frontend hooks.
		$this->frontend->init();
		
		// Initialize worker hooks.
// Set up logging cache clearing hook.
		add_action( 'update_option_ai_faq_gen_options', array( $this, 'clear_logging_cache' ) );
		$this->workers->init();
	}

	/**
	 * Clear logging cache when settings are updated.
	 *
	 * @since 2.1.0
	 */
	public function clear_logging_cache() {
		$logger = AI_FAQ_365i_Logger::get_instance();
		$logger->clear_settings_cache();
		ai_faq_log_debug('AI_FAQ_Core: Logging cache cleared due to settings update');
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
				'url_faq_generator' => array(
					'url' => 'https://url-to-faq-generator-worker.winter-cake-bf57.workers.dev',
					'enabled' => true,
					'rate_limit' => 100,
				),
			),
			'settings' => array(
				'default_faq_count' => 12,
				'auto_save_interval' => 3,
				'debug_mode' => false,
			),
			'ai_models' => array(
				'faq_answer_generator' => '@cf/meta/llama-3.1-8b-instruct',
				'faq_enhancement_worker' => '@cf/meta/llama-3.1-8b-instruct',
				'faq_seo_analyzer_worker' => '@cf/meta/llama-3.1-8b-instruct',
				'faq_realtime_assistant_worker' => '@cf/meta/llama-3.1-8b-instruct',
				'url_to_faq_generator_worker' => '@cf/meta/llama-3.1-70b-instruct',
				'faq_proxy_fetch' => '@cf/meta/llama-3.1-8b-instruct',
			),
			'enable_logging' => false,
			'log_level' => 'error',
			'enable_analytics' => true,
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