<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @link       https://365i.com
 * @since      1.0.0
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/includes
 * @author     365i
 */
class FAQ_AI_Generator {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      FAQ_AI_Generator_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The worker communicator instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Worker_Communicator    $worker_communicator    Handles communication with AI workers.
	 */
	protected $worker_communicator;

	/**
	 * The schema generator instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Schema_Generator    $schema_generator    Handles schema generation.
	 */
	protected $schema_generator;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'FAQ_AI_GENERATOR_VERSION' ) ) {
			$this->version = FAQ_AI_GENERATOR_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'faq-ai-generator';

		$this->load_dependencies();
		$this->set_locale();
		$this->initialize_components();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - FAQ_AI_Generator_Loader. Orchestrates the hooks of the plugin.
	 * - FAQ_AI_Generator_i18n. Defines internationalization functionality.
	 * - FAQ_AI_Generator_Admin. Defines all hooks for the admin area.
	 * - FAQ_AI_Generator_Public. Defines all hooks for the public side of the site.
	 * - Worker_Communicator. Handles communication with AI workers.
	 * - Schema_Generator. Handles schema generation for FAQs.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-faq-ai-generator-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-faq-ai-generator-i18n.php';

		/**
		 * The class responsible for communicating with AI workers.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-worker-communicator.php';

		/**
		 * The class responsible for generating schema markup.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-schema-generator.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-faq-ai-admin.php';

		/**
		 * The class responsible for documentation functionality.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-faq-ai-generator-docs.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-faq-ai-public.php';

		$this->loader = new FAQ_AI_Generator_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the FAQ_AI_Generator_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new FAQ_AI_Generator_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Initialize core components of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function initialize_components() {
		// Initialize worker communicator
		$this->worker_communicator = new Worker_Communicator();

		// Initialize schema generator with base URL from settings
		$settings = get_option('faq_ai_generator_settings', array());
		$base_url = !empty($settings['faq_page_url']) ? $settings['faq_page_url'] : site_url();
		$this->schema_generator = new Schema_Generator($base_url);
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new FAQ_AI_Admin( $this->get_plugin_name(), $this->get_version(), $this->worker_communicator, $this->schema_generator );

		// Admin assets
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Admin menu
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );

		// Settings API
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );

		// AJAX handlers for admin operations
		$this->loader->add_action( 'wp_ajax_faq_ai_get_worker_status', $plugin_admin, 'ajax_get_worker_status' );
		$this->loader->add_action( 'wp_ajax_faq_ai_reset_rate_limits', $plugin_admin, 'ajax_reset_rate_limits' );
		$this->loader->add_action( 'wp_ajax_faq_ai_test_worker', $plugin_admin, 'ajax_test_worker' );
		$this->loader->add_action( 'wp_ajax_faq_ai_update_worker', $plugin_admin, 'ajax_update_worker' );
		
		// Documentation hooks
		$plugin_docs = new FAQ_AI_Generator_Docs( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_docs, 'enqueue_styles' );
		$this->loader->add_action( 'admin_menu', $plugin_docs, 'add_documentation_menu' );
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( plugin_dir_path( dirname( __FILE__ ) ) . $this->plugin_name . '.php' ), $plugin_docs, 'add_documentation_link' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new FAQ_AI_Public( $this->get_plugin_name(), $this->get_version(), $this->worker_communicator, $this->schema_generator );

		// Public assets
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// Register shortcode
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );

		// AJAX handlers for frontend operations
		$this->loader->add_action( 'wp_ajax_faq_ai_generate_question', $plugin_public, 'ajax_generate_question' );
		$this->loader->add_action( 'wp_ajax_faq_ai_generate_answer', $plugin_public, 'ajax_generate_answer' );
		$this->loader->add_action( 'wp_ajax_faq_ai_analyze_seo', $plugin_public, 'ajax_analyze_seo' );
		$this->loader->add_action( 'wp_ajax_faq_ai_fetch_url', $plugin_public, 'ajax_fetch_url' );
		$this->loader->add_action( 'wp_ajax_faq_ai_enhance_faq', $plugin_public, 'ajax_enhance_faq' );
		$this->loader->add_action( 'wp_ajax_faq_ai_generate_schema', $plugin_public, 'ajax_generate_schema' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    FAQ_AI_Generator_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get the worker communicator instance.
	 *
	 * @since     1.0.0
	 * @return    Worker_Communicator    The worker communicator instance.
	 */
	public function get_worker_communicator() {
		return $this->worker_communicator;
	}

	/**
	 * Get the schema generator instance.
	 *
	 * @since     1.0.0
	 * @return    Schema_Generator    The schema generator instance.
	 */
	public function get_schema_generator() {
		return $this->schema_generator;
	}
}