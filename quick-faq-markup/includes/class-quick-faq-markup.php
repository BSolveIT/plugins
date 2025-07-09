<?php
/**
 * The main plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package Quick_FAQ_Markup
 * @since 1.0.0
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * The main plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since 1.0.0
 */
class Quick_FAQ_Markup {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since 1.0.0
	 * @var string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since 1.0.0
	 * @var string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * The admin-specific functionality of the plugin.
	 *
	 * @since 1.0.0
	 * @var Quick_FAQ_Markup_Admin $admin The admin-specific functionality of the plugin.
	 */
	protected $admin;

	/**
	 * The frontend-specific functionality of the plugin.
	 *
	 * @since 1.0.0
	 * @var Quick_FAQ_Markup_Frontend $frontend The frontend-specific functionality of the plugin.
	 */
	protected $frontend;

	/**
	 * The shortcode functionality of the plugin.
	 *
	 * @since 1.0.0
	 * @var Quick_FAQ_Markup_Shortcode $shortcode The shortcode functionality of the plugin.
	 */
	protected $shortcode;

	/**
	 * The schema markup functionality of the plugin.
	 *
	 * @since 1.0.0
	 * @var Quick_FAQ_Markup_Schema $schema The schema markup functionality of the plugin.
	 */
	protected $schema;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->plugin_name = QUICK_FAQ_MARKUP_PLUGIN_NAME;
		$this->version     = QUICK_FAQ_MARKUP_VERSION;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once QUICK_FAQ_MARKUP_PLUGIN_DIR . 'admin/class-quick-faq-markup-admin.php';

		/**
		 * The class responsible for defining all actions that occur on the frontend.
		 */
		require_once QUICK_FAQ_MARKUP_PLUGIN_DIR . 'includes/class-quick-faq-markup-frontend.php';

		/**
		 * The class responsible for defining shortcode functionality.
		 */
		require_once QUICK_FAQ_MARKUP_PLUGIN_DIR . 'includes/class-quick-faq-markup-shortcode.php';

		/**
		 * The class responsible for defining schema markup functionality.
		 */
		require_once QUICK_FAQ_MARKUP_PLUGIN_DIR . 'includes/class-quick-faq-markup-schema.php';

		$this->admin = new Quick_FAQ_Markup_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->frontend = new Quick_FAQ_Markup_Frontend( $this->get_plugin_name(), $this->get_version() );
		$this->schema = new Quick_FAQ_Markup_Schema( $this->get_plugin_name(), $this->get_version() );
		$this->shortcode = new Quick_FAQ_Markup_Shortcode( $this->frontend );
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WordPress auto-loading for translations since WordPress 6.8+.
	 * No need for load_plugin_textdomain() as text domain is declared in plugin header.
	 *
	 * @since 1.0.0
	 */
	private function set_locale() {
		// WordPress 6.8+ auto-loads translations based on text domain in plugin header
		// No additional setup required
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 *
	 * @since 1.0.0
	 */
	private function define_admin_hooks() {
		// Register post type and taxonomy
		add_action( 'init', array( $this->admin, 'register_post_type' ) );
		add_action( 'init', array( $this->admin, 'register_faq_taxonomy' ) );

		// Admin interface hooks
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_scripts' ) );
		add_action( 'admin_footer', array( $this->admin, 'inject_title_drag_handles' ) );

		// Meta box hooks
		add_action( 'add_meta_boxes', array( $this->admin, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this->admin, 'save_meta_data' ) );

		// Admin column hooks
		add_filter( 'manage_qfm_faq_posts_columns', array( $this->admin, 'add_admin_columns' ) );
		add_action( 'manage_qfm_faq_posts_custom_column', array( $this->admin, 'populate_admin_columns' ), 10, 2 );
		add_filter( 'manage_edit-qfm_faq_sortable_columns', array( $this->admin, 'make_columns_sortable' ) );

		// Admin filtering hooks
		add_action( 'restrict_manage_posts', array( $this->admin, 'add_category_filter' ) );
		add_action( 'pre_get_posts', array( $this->admin, 'filter_faqs_by_category' ) );

		// AJAX hooks
		add_action( 'wp_ajax_qfm_update_faq_order', array( $this->admin, 'handle_ajax_reorder' ) );
		add_action( 'wp_ajax_qfm_single_faq_order', array( $this->admin, 'handle_single_faq_order' ) );

		// Settings page hooks
		add_action( 'admin_menu', array( $this->admin, 'create_settings_page' ) );
		add_action( 'admin_init', array( $this->admin, 'register_settings' ) );

		// Cache invalidation hooks for taxonomy changes
		add_action( 'created_qfm_faq_category', array( $this->admin, 'clear_faq_cache_on_term_change' ), 10, 3 );
		add_action( 'edited_qfm_faq_category', array( $this->admin, 'clear_faq_cache_on_term_change' ), 10, 3 );
		add_action( 'deleted_qfm_faq_category', array( $this->admin, 'clear_faq_cache_on_term_change' ), 10, 3 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 *
	 * @since 1.0.0
	 */
	private function define_public_hooks() {
		// Frontend asset hooks
		add_action( 'wp_enqueue_scripts', array( $this->frontend, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this->frontend, 'enqueue_scripts' ) );

		// Shortcode registration
		add_action( 'init', array( $this->shortcode, 'register_shortcode' ) );

		// Schema markup hooks
		add_action( 'wp_head', array( $this->schema, 'output_schema_to_head' ), 5 );
		add_action( 'wp_head', array( $this->schema, 'output_open_graph_meta' ), 10 );

		// Anchor targeting script
		add_action( 'wp_footer', array( $this->frontend, 'add_anchor_targeting_script' ) );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		// Plugin is initialized through constructor and hooks
		// No additional runtime actions needed at this time
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since 1.0.0
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since 1.0.0
	 * @return string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get the admin instance.
	 *
	 * @since 1.0.0
	 * @return Quick_FAQ_Markup_Admin The admin instance.
	 */
	public function get_admin() {
		return $this->admin;
	}

	/**
	 * Get the frontend instance.
	 *
	 * @since 1.0.0
	 * @return Quick_FAQ_Markup_Frontend The frontend instance.
	 */
	public function get_frontend() {
		return $this->frontend;
	}

	/**
	 * Get the shortcode instance.
	 *
	 * @since 1.0.0
	 * @return Quick_FAQ_Markup_Shortcode The shortcode instance.
	 */
	public function get_shortcode() {
		return $this->shortcode;
	}

	/**
	 * Get the schema instance.
	 *
	 * @since 1.0.0
	 * @return Quick_FAQ_Markup_Schema The schema instance.
	 */
	public function get_schema() {
		return $this->schema;
	}

	/**
	 * Plugin activation hook.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		// Store plugin version
		update_option( 'quick_faq_markup_version', QUICK_FAQ_MARKUP_VERSION );

		// Initialize default settings
		$default_settings = array(
			'default_style'    => 'classic',
			'show_anchors'     => true,
			'enable_schema'    => true,
			'cache_enabled'    => true,
			'cache_duration'   => HOUR_IN_SECONDS,
		);

		add_option( 'quick_faq_markup_settings', $default_settings );

		// Flush rewrite rules to ensure custom post type URLs work
		flush_rewrite_rules();

		// Log activation
		quick_faq_markup_log( 'Plugin activated successfully', 'info' );
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		// Clear any cached data
		wp_cache_flush();

		// Flush rewrite rules
		flush_rewrite_rules();

		// Log deactivation
		quick_faq_markup_log( 'Plugin deactivated', 'info' );
	}

	/**
	 * Check if plugin database needs upgrade.
	 *
	 * @since 1.0.0
	 * @return bool True if upgrade is needed, false otherwise.
	 */
	public function needs_upgrade() {
		$installed_version = get_option( 'quick_faq_markup_version', '0.0.0' );
		return version_compare( $installed_version, QUICK_FAQ_MARKUP_VERSION, '<' );
	}

	/**
	 * Handle plugin upgrades.
	 *
	 * @since 1.0.0
	 */
	public function handle_upgrade() {
		if ( ! $this->needs_upgrade() ) {
			return;
		}

		$installed_version = get_option( 'quick_faq_markup_version', '0.0.0' );

		// Future upgrade logic would go here
		// For now, just update the version
		update_option( 'quick_faq_markup_version', QUICK_FAQ_MARKUP_VERSION );

		quick_faq_markup_log( 
			sprintf( 
				'Plugin upgraded from version %s to %s', 
				$installed_version, 
				QUICK_FAQ_MARKUP_VERSION 
			), 
			'info' 
		);
	}

	/**
	 * Get plugin settings.
	 *
	 * @since 1.0.0
	 * @return array Plugin settings.
	 */
	public function get_settings() {
		$defaults = array(
			'default_style'    => 'classic',
			'show_anchors'     => true,
			'enable_schema'    => true,
			'cache_enabled'    => true,
			'cache_duration'   => HOUR_IN_SECONDS,
		);

		$settings = get_option( 'quick_faq_markup_settings', $defaults );
		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Update plugin settings.
	 *
	 * @since 1.0.0
	 * @param array $settings Settings to update.
	 * @return bool True if settings were updated, false otherwise.
	 */
	public function update_settings( $settings ) {
		$current_settings = $this->get_settings();
		$new_settings     = wp_parse_args( $settings, $current_settings );

		return update_option( 'quick_faq_markup_settings', $new_settings );
	}
}