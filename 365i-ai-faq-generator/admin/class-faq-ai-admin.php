<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://365i.com
 * @since      1.0.0
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks for
 * enqueuing the admin-specific stylesheet and JavaScript.
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/admin
 * @author     365i
 */
class FAQ_AI_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The worker communicator instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Worker_Communicator    $worker_communicator    Handles communication with AI workers.
	 */
	private $worker_communicator;

	/**
	 * The schema generator instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Schema_Generator    $schema_generator    Handles schema generation.
	 */
	private $schema_generator;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name          The name of this plugin.
	 * @param    string    $version              The version of this plugin.
	 * @param    Worker_Communicator    $worker_communicator    The worker communicator instance.
	 * @param    Schema_Generator       $schema_generator       The schema generator instance.
	 */
	public function __construct( $plugin_name, $version, $worker_communicator, $schema_generator ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->worker_communicator = $worker_communicator;
		$this->schema_generator = $schema_generator;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		// Only load on our admin pages
		if (!$this->is_plugin_admin_page()) {
			return;
		}

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/admin-styles.css', array(), $this->version, 'all' );
		
		// Load worker configuration styles on settings page
		$screen = get_current_screen();
		if ($screen && $screen->id === 'ai-faq-generator_page_faq-ai-generator-settings') {
			wp_enqueue_style( $this->plugin_name . '-worker-config', plugin_dir_url( __FILE__ ) . 'css/worker-config.css', array($this->plugin_name), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		// Only load on our admin pages
		if (!$this->is_plugin_admin_page()) {
			return;
		}

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/admin-scripts.js', array( 'jquery' ), $this->version, false );

		// Localize the script with data for AJAX and translations
		wp_localize_script( $this->plugin_name, 'faqAiAdmin', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'faq_ai_admin_nonce' ),
			'strings' => array(
				'testSuccess' => __( 'Connection successful!', 'faq-ai-generator' ),
				'testFailed' => __( 'Connection failed: ', 'faq-ai-generator' ),
				'savingSettings' => __( 'Saving settings...', 'faq-ai-generator' ),
				'settingsSaved' => __( 'Settings saved successfully!', 'faq-ai-generator' ),
				'settingsFailed' => __( 'Failed to save settings: ', 'faq-ai-generator' ),
				'resetRateLimits' => __( 'Rate limits reset successfully!', 'faq-ai-generator' ),
				'resetFailed' => __( 'Failed to reset rate limits: ', 'faq-ai-generator' ),
				'confirm' => __( 'Are you sure?', 'faq-ai-generator' ),
			)
		));
	}

	/**
	 * Check if we're on a plugin admin page.
	 *
	 * @since    1.0.0
	 * @return   boolean   True if on plugin admin page.
	 */
	private function is_plugin_admin_page() {
		$screen = get_current_screen();
		if (!$screen) {
			return false;
		}

		// Check if we're on our plugin's settings page
		if (strpos($screen->id, 'faq-ai-generator') !== false) {
			return true;
		}

		return false;
	}

	/**
	 * Add menu items to the admin dashboard.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		// Main menu item
		add_menu_page(
			__( 'AI FAQ Generator', 'faq-ai-generator' ),
			__( 'AI FAQ Generator', 'faq-ai-generator' ),
			'manage_options',
			'faq-ai-generator',
			array( $this, 'display_main_dashboard' ),
			'dashicons-format-chat',
			30
		);

		// Settings submenu
		add_submenu_page(
			'faq-ai-generator',
			__( 'AI FAQ Generator Settings', 'faq-ai-generator' ),
			__( 'Settings', 'faq-ai-generator' ),
			'manage_options',
			'faq-ai-generator-settings',
			array( $this, 'display_settings_page' )
		);

		// Change main menu name
		global $submenu;
		if (isset($submenu['faq-ai-generator'])) {
			$submenu['faq-ai-generator'][0][0] = __( 'Dashboard', 'faq-ai-generator' );
		}
	}

	/**
	 * Display the main plugin dashboard.
	 *
	 * @since    1.0.0
	 */
	public function display_main_dashboard() {
		// Include the dashboard partial
		include_once plugin_dir_path( __FILE__ ) . 'partials/admin-dashboard.php';
	}

	/**
	 * Display the settings page.
	 *
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		// Include the settings page partial
		include_once plugin_dir_path( __FILE__ ) . 'partials/admin-settings.php';
	}

	/**
	 * Register the plugin settings.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		// Worker settings
		register_setting(
			'faq_ai_generator_workers',
			'faq_ai_generator_workers',
			array($this, 'validate_worker_settings')
		);

		// General settings
		register_setting(
			'faq_ai_generator_settings',
			'faq_ai_generator_settings',
			array($this, 'validate_general_settings')
		);

		// Worker settings section
		add_settings_section(
			'faq_ai_worker_settings',
			__( 'AI Worker Configuration', 'faq-ai-generator' ),
			array( $this, 'render_worker_settings_section' ),
			'faq_ai_generator_workers'
		);

		// General settings section
		add_settings_section(
			'faq_ai_general_settings',
			__( 'General Settings', 'faq-ai-generator' ),
			array( $this, 'render_general_settings_section' ),
			'faq_ai_generator_settings'
		);

		// Advanced settings section
		add_settings_section(
			'faq_ai_advanced_settings',
			__( 'Advanced Settings', 'faq-ai-generator' ),
			array( $this, 'render_advanced_settings_section' ),
			'faq_ai_generator_settings'
		);

		// Add all worker fields
		$worker_settings = get_option('faq_ai_generator_workers', array());
		
		foreach ($worker_settings as $key => $worker) {
			// Worker URL field
			add_settings_field(
				'faq_ai_' . $key . '_url',
				sprintf(__( '%s URL', 'faq-ai-generator' ), $this->get_worker_display_name($key)),
				array( $this, 'render_worker_url_field' ),
				'faq_ai_generator_workers',
				'faq_ai_worker_settings',
				array('worker_key' => $key)
			);

			// Worker rate limit field
			add_settings_field(
				'faq_ai_' . $key . '_rate_limit',
				sprintf(__( '%s Rate Limit', 'faq-ai-generator' ), $this->get_worker_display_name($key)),
				array( $this, 'render_worker_rate_limit_field' ),
				'faq_ai_generator_workers',
				'faq_ai_worker_settings',
				array('worker_key' => $key)
			);

			// Worker cooldown field
			add_settings_field(
				'faq_ai_' . $key . '_cooldown',
				sprintf(__( '%s Cooldown', 'faq-ai-generator' ), $this->get_worker_display_name($key)),
				array( $this, 'render_worker_cooldown_field' ),
				'faq_ai_generator_workers',
				'faq_ai_worker_settings',
				array('worker_key' => $key)
			);

			// Worker enabled field
			add_settings_field(
				'faq_ai_' . $key . '_enabled',
				sprintf(__( 'Enable %s', 'faq-ai-generator' ), $this->get_worker_display_name($key)),
				array( $this, 'render_worker_enabled_field' ),
				'faq_ai_generator_workers',
				'faq_ai_worker_settings',
				array('worker_key' => $key)
			);
		}

		// Add general settings fields
		add_settings_field(
			'faq_ai_faq_page_url',
			__( 'FAQ Page URL', 'faq-ai-generator' ),
			array( $this, 'render_faq_page_url_field' ),
			'faq_ai_generator_settings',
			'faq_ai_general_settings'
		);

		add_settings_field(
			'faq_ai_default_anchor_format',
			__( 'Default Anchor Format', 'faq-ai-generator' ),
			array( $this, 'render_default_anchor_format_field' ),
			'faq_ai_generator_settings',
			'faq_ai_general_settings'
		);

		add_settings_field(
			'faq_ai_auto_save_interval',
			__( 'Auto-save Interval', 'faq-ai-generator' ),
			array( $this, 'render_auto_save_interval_field' ),
			'faq_ai_generator_settings',
			'faq_ai_general_settings'
		);

		// Add advanced settings fields
		add_settings_field(
			'faq_ai_debug_mode',
			__( 'Debug Mode', 'faq-ai-generator' ),
			array( $this, 'render_debug_mode_field' ),
			'faq_ai_generator_settings',
			'faq_ai_advanced_settings'
		);

		add_settings_field(
			'faq_ai_usage_analytics',
			__( 'Usage Analytics', 'faq-ai-generator' ),
			array( $this, 'render_usage_analytics_field' ),
			'faq_ai_generator_settings',
			'faq_ai_advanced_settings'
		);

		add_settings_field(
			'faq_ai_performance_monitoring',
			__( 'Performance Monitoring', 'faq-ai-generator' ),
			array( $this, 'render_performance_monitoring_field' ),
			'faq_ai_generator_settings',
			'faq_ai_advanced_settings'
		);
	}

	/**
	 * Render the worker settings section.
	 *
	 * @since    1.0.0
	 */
	public function render_worker_settings_section() {
		echo '<p>' . __( 'Configure the AI workers that power the FAQ generator. These settings control how the plugin communicates with the Cloudflare Workers AI services.', 'faq-ai-generator' ) . '</p>';
	}

	/**
	 * Render the general settings section.
	 *
	 * @since    1.0.0
	 */
	public function render_general_settings_section() {
		echo '<p>' . __( 'Configure general settings for the FAQ AI Generator.', 'faq-ai-generator' ) . '</p>';
	}

	/**
	 * Render the advanced settings section.
	 *
	 * @since    1.0.0
	 */
	public function render_advanced_settings_section() {
		echo '<p>' . __( 'Advanced settings for debugging and performance monitoring.', 'faq-ai-generator' ) . '</p>';
	}

	/**
	 * Render a worker URL field.
	 *
	 * @since    1.0.0
	 * @param    array    $args    The field arguments.
	 */
	public function render_worker_url_field( $args ) {
		$worker_key = $args['worker_key'];
		$workers = get_option('faq_ai_generator_workers', array());
		$value = isset($workers[$worker_key]['url']) ? $workers[$worker_key]['url'] : '';

		echo '<input type="text" class="regular-text" name="faq_ai_generator_workers[' . esc_attr($worker_key) . '][url]" value="' . esc_attr($value) . '" />';
		echo '<p class="description">' . __( 'The URL endpoint for this worker.', 'faq-ai-generator' ) . '</p>';
		echo '<button type="button" class="button test-worker-button" data-worker="' . esc_attr($worker_key) . '">' . __( 'Test Connection', 'faq-ai-generator' ) . '</button>';
		echo '<span class="test-result" id="test-result-' . esc_attr($worker_key) . '"></span>';
	}

	/**
	 * Render a worker rate limit field.
	 *
	 * @since    1.0.0
	 * @param    array    $args    The field arguments.
	 */
	public function render_worker_rate_limit_field( $args ) {
		$worker_key = $args['worker_key'];
		$workers = get_option('faq_ai_generator_workers', array());
		$value = isset($workers[$worker_key]['rate_limit']) ? $workers[$worker_key]['rate_limit'] : 50;

		echo '<input type="range" min="0" max="200" step="5" name="faq_ai_generator_workers[' . esc_attr($worker_key) . '][rate_limit]" value="' . esc_attr($value) . '" class="rate-limit-slider" data-worker="' . esc_attr($worker_key) . '" />';
		echo '<span class="rate-limit-value" id="rate-limit-value-' . esc_attr($worker_key) . '">' . esc_html($value) . '</span> ' . __( 'requests per hour', 'faq-ai-generator' );
		echo '<p class="description">' . __( 'Maximum number of requests allowed per hour.', 'faq-ai-generator' ) . '</p>';
	}

	/**
	 * Render a worker cooldown field.
	 *
	 * @since    1.0.0
	 * @param    array    $args    The field arguments.
	 */
	public function render_worker_cooldown_field( $args ) {
		$worker_key = $args['worker_key'];
		$workers = get_option('faq_ai_generator_workers', array());
		$value = isset($workers[$worker_key]['cooldown']) ? $workers[$worker_key]['cooldown'] : 3;

		echo '<input type="range" min="1" max="30" step="1" name="faq_ai_generator_workers[' . esc_attr($worker_key) . '][cooldown]" value="' . esc_attr($value) . '" class="cooldown-slider" data-worker="' . esc_attr($worker_key) . '" />';
		echo '<span class="cooldown-value" id="cooldown-value-' . esc_attr($worker_key) . '">' . esc_html($value) . '</span> ' . __( 'seconds', 'faq-ai-generator' );
		echo '<p class="description">' . __( 'Cooldown period between consecutive requests.', 'faq-ai-generator' ) . '</p>';
	}

	/**
	 * Render a worker enabled field.
	 *
	 * @since    1.0.0
	 * @param    array    $args    The field arguments.
	 */
	public function render_worker_enabled_field( $args ) {
		$worker_key = $args['worker_key'];
		$workers = get_option('faq_ai_generator_workers', array());
		$checked = isset($workers[$worker_key]['enabled']) && $workers[$worker_key]['enabled'] ? 'checked' : '';

		echo '<label for="faq_ai_' . esc_attr($worker_key) . '_enabled">';
		echo '<input type="checkbox" id="faq_ai_' . esc_attr($worker_key) . '_enabled" name="faq_ai_generator_workers[' . esc_attr($worker_key) . '][enabled]" value="1" ' . $checked . ' />';
		echo __( 'Enable this worker', 'faq-ai-generator' ) . '</label>';
		echo '<p class="description">' . __( 'When disabled, the plugin will not use this worker.', 'faq-ai-generator' ) . '</p>';
	}

	/**
	 * Render the FAQ page URL field.
	 *
	 * @since    1.0.0
	 */
	public function render_faq_page_url_field() {
		$settings = get_option('faq_ai_generator_settings', array());
		$value = isset($settings['faq_page_url']) ? $settings['faq_page_url'] : '';

		echo '<input type="url" class="regular-text" name="faq_ai_generator_settings[faq_page_url]" value="' . esc_attr($value) . '" />';
		echo '<p class="description">' . __( 'The URL of your FAQ page. Used for schema generation and AI context.', 'faq-ai-generator' ) . '</p>';
	}

	/**
	 * Render the default anchor format field.
	 *
	 * @since    1.0.0
	 */
	public function render_default_anchor_format_field() {
		$settings = get_option('faq_ai_generator_settings', array());
		$value = isset($settings['default_anchor_format']) ? $settings['default_anchor_format'] : 'question';

		echo '<select name="faq_ai_generator_settings[default_anchor_format]">';
		echo '<option value="question" ' . selected($value, 'question', false) . '>' . __( 'Question-based (faq-how-do-i)', 'faq-ai-generator' ) . '</option>';
		echo '<option value="id" ' . selected($value, 'id', false) . '>' . __( 'ID-based (faq-1)', 'faq-ai-generator' ) . '</option>';
		echo '<option value="custom" ' . selected($value, 'custom', false) . '>' . __( 'Custom (user-defined)', 'faq-ai-generator' ) . '</option>';
		echo '</select>';
		echo '<p class="description">' . __( 'Format for FAQ anchor links in generated schemas.', 'faq-ai-generator' ) . '</p>';
	}

	/**
	 * Render the auto-save interval field.
	 *
	 * @since    1.0.0
	 */
	public function render_auto_save_interval_field() {
		$settings = get_option('faq_ai_generator_settings', array());
		$value = isset($settings['auto_save_interval']) ? $settings['auto_save_interval'] : 3;

		echo '<input type="range" min="1" max="10" step="1" name="faq_ai_generator_settings[auto_save_interval]" value="' . esc_attr($value) . '" class="auto-save-slider" />';
		echo '<span class="auto-save-value">' . esc_html($value) . '</span> ' . __( 'seconds', 'faq-ai-generator' );
		echo '<p class="description">' . __( 'How often to auto-save FAQ changes to localStorage.', 'faq-ai-generator' ) . '</p>';
	}

	/**
	 * Render the debug mode field.
	 *
	 * @since    1.0.0
	 */
	public function render_debug_mode_field() {
		$settings = get_option('faq_ai_generator_settings', array());
		$checked = isset($settings['debug_mode']) && $settings['debug_mode'] ? 'checked' : '';

		echo '<label for="faq_ai_debug_mode">';
		echo '<input type="checkbox" id="faq_ai_debug_mode" name="faq_ai_generator_settings[debug_mode]" value="1" ' . $checked . ' />';
		echo __( 'Enable debug mode', 'faq-ai-generator' ) . '</label>';
		echo '<p class="description">' . __( 'When enabled, detailed error messages and logs will be shown.', 'faq-ai-generator' ) . '</p>';
	}

	/**
	 * Render the usage analytics field.
	 *
	 * @since    1.0.0
	 */
	public function render_usage_analytics_field() {
		$settings = get_option('faq_ai_generator_settings', array());
		$checked = isset($settings['usage_analytics']) && $settings['usage_analytics'] ? 'checked' : '';

		echo '<label for="faq_ai_usage_analytics">';
		echo '<input type="checkbox" id="faq_ai_usage_analytics" name="faq_ai_generator_settings[usage_analytics]" value="1" ' . $checked . ' />';
		echo __( 'Track usage analytics', 'faq-ai-generator' ) . '</label>';
		echo '<p class="description">' . __( 'Collects anonymous usage data to help improve the plugin.', 'faq-ai-generator' ) . '</p>';
	}

	/**
	 * Render the performance monitoring field.
	 *
	 * @since    1.0.0
	 */
	public function render_performance_monitoring_field() {
		$settings = get_option('faq_ai_generator_settings', array());
		$checked = isset($settings['performance_monitoring']) && $settings['performance_monitoring'] ? 'checked' : '';

		echo '<label for="faq_ai_performance_monitoring">';
		echo '<input type="checkbox" id="faq_ai_performance_monitoring" name="faq_ai_generator_settings[performance_monitoring]" value="1" ' . $checked . ' />';
		echo __( 'Monitor performance', 'faq-ai-generator' ) . '</label>';
		echo '<p class="description">' . __( 'Tracks response times and performance metrics for AI operations.', 'faq-ai-generator' ) . '</p>';
	}

	/**
	 * Validate worker settings before saving.
	 *
	 * @since    1.0.0
	 * @param    array    $input    The settings to validate.
	 * @return   array              The validated settings.
	 */
	public function validate_worker_settings( $input ) {
		$validated = array();
		$current_settings = get_option('faq_ai_generator_workers', array());

		foreach ($current_settings as $key => $worker) {
			// Validate URL
			$validated[$key]['url'] = isset($input[$key]['url']) ? esc_url_raw($input[$key]['url']) : $worker['url'];

			// Validate rate limit
			$validated[$key]['rate_limit'] = isset($input[$key]['rate_limit']) ? 
				intval($input[$key]['rate_limit']) : $worker['rate_limit'];
			
			// Ensure rate limit is within range
			$validated[$key]['rate_limit'] = max(0, min(200, $validated[$key]['rate_limit']));

			// Validate cooldown
			$validated[$key]['cooldown'] = isset($input[$key]['cooldown']) ? 
				intval($input[$key]['cooldown']) : $worker['cooldown'];
			
			// Ensure cooldown is within range
			$validated[$key]['cooldown'] = max(1, min(30, $validated[$key]['cooldown']));

			// Validate enabled status
			$validated[$key]['enabled'] = isset($input[$key]['enabled']) ? true : false;
		}

		return $validated;
	}

	/**
	 * Validate general settings before saving.
	 *
	 * @since    1.0.0
	 * @param    array    $input    The settings to validate.
	 * @return   array              The validated settings.
	 */
	public function validate_general_settings( $input ) {
		$validated = array();
		$current_settings = get_option('faq_ai_generator_settings', array());

		// Validate FAQ page URL
		$validated['faq_page_url'] = isset($input['faq_page_url']) ? esc_url_raw($input['faq_page_url']) : '';

		// Validate default anchor format
		$valid_formats = array('question', 'id', 'custom');
		$validated['default_anchor_format'] = isset($input['default_anchor_format']) && in_array($input['default_anchor_format'], $valid_formats) ? 
			$input['default_anchor_format'] : 'question';

		// Validate auto-save interval
		$validated['auto_save_interval'] = isset($input['auto_save_interval']) ? 
			intval($input['auto_save_interval']) : 3;
		
		// Ensure auto-save interval is within range
		$validated['auto_save_interval'] = max(1, min(10, $validated['auto_save_interval']));

		// Validate debug mode
		$validated['debug_mode'] = isset($input['debug_mode']) ? true : false;

		// Validate usage analytics
		$validated['usage_analytics'] = isset($input['usage_analytics']) ? true : false;

		// Validate performance monitoring
		$validated['performance_monitoring'] = isset($input['performance_monitoring']) ? true : false;

		return $validated;
	}

	/**
	 * Get a display name for a worker.
	 *
	 * @since    1.0.0
	 * @param    string    $key    The worker key.
	 * @return   string            The display name.
	 */
	private function get_worker_display_name( $key ) {
		$display_names = array(
			'faq-realtime-assistant-worker' => __( 'Realtime Assistant', 'faq-ai-generator' ),
			'faq-answer-generator-worker' => __( 'Answer Generator', 'faq-ai-generator' ),
			'faq-seo-analyzer-worker' => __( 'SEO Analyzer', 'faq-ai-generator' ),
			'faq-enhancement-worker' => __( 'Enhancement Worker', 'faq-ai-generator' ),
			'url-to-faq-generator-worker' => __( 'URL-to-FAQ Generator', 'faq-ai-generator' ),
			'faq-proxy-fetch' => __( 'Proxy Fetch Worker', 'faq-ai-generator' ),
		);

		return isset($display_names[$key]) ? $display_names[$key] : ucfirst(str_replace('-', ' ', $key));
	}

	/**
	 * AJAX handler for getting worker status.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_worker_status() {
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'faq_ai_admin_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'faq-ai-generator')));
		}

		// Check capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to do this.', 'faq-ai-generator')));
		}

		$worker_key = isset($_POST['worker']) ? sanitize_text_field($_POST['worker']) : '';
		
		if (empty($worker_key)) {
			wp_send_json_error(array('message' => __('No worker specified.', 'faq-ai-generator')));
		}

		// Get rate limit info for the worker
		$rate_info = $this->worker_communicator->get_rate_limit_info($worker_key);
		
		// Send the response
		wp_send_json_success(array(
			'worker' => $worker_key,
			'rate_info' => $rate_info,
			'display_name' => $this->get_worker_display_name($worker_key)
		));
	}

	/**
	 * AJAX handler for resetting rate limits.
	 *
	 * @since    1.0.0
	 */
	public function ajax_reset_rate_limits() {
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'faq_ai_admin_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'faq-ai-generator')));
		}

		// Check capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to do this.', 'faq-ai-generator')));
		}

		$worker_key = isset($_POST['worker']) ? sanitize_text_field($_POST['worker']) : '';
		
		if (empty($worker_key)) {
			// Reset all workers
			$this->worker_communicator->reset_rate_limits();
		} else {
			// Reset specific worker
			$this->worker_communicator->reset_rate_limits($worker_key);
		}
		
		// Send the response
		wp_send_json_success(array(
			'message' => __('Rate limits reset successfully.', 'faq-ai-generator'),
			'worker' => $worker_key
		));
	}

	/**
	 * AJAX handler for testing worker connection.
	 *
	 * @since    1.0.0
	 */
	public function ajax_test_worker() {
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'faq_ai_admin_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'faq-ai-generator')));
		}

		// Check capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to do this.', 'faq-ai-generator')));
		}

		$worker_key = isset($_POST['worker']) ? sanitize_text_field($_POST['worker']) : '';
		
		if (empty($worker_key)) {
			wp_send_json_error(array('message' => __('No worker specified.', 'faq-ai-generator')));
		}

		// Get the worker URL
		$workers = get_option('faq_ai_generator_workers', array());
		
		if (!isset($workers[$worker_key])) {
			wp_send_json_error(array('message' => __('Worker not found.', 'faq-ai-generator')));
		}

		// Get test data from request or use default
		$test_data = array(
			'test' => true,
			'timestamp' => time()
		);

		// If test_data is provided in the request, use it
		if (isset($_POST['test_data']) && !empty($_POST['test_data'])) {
			$provided_test_data = $_POST['test_data'];
			if (is_string($provided_test_data)) {
				$decoded_data = json_decode(stripslashes($provided_test_data), true);
				if (is_array($decoded_data)) {
					$test_data = array_merge($test_data, $decoded_data);
				}
			} elseif (is_array($provided_test_data)) {
				$test_data = array_merge($test_data, $provided_test_data);
			}
		}

		// Add worker-specific test data if not provided
		if (!isset($test_data['mode'])) {
			$test_data['mode'] = 'test';
		}

		// Send the test request
		$result = $this->worker_communicator->send_request($worker_key, $test_data, true);
		
		if (is_wp_error($result)) {
			wp_send_json_error(array('message' => $result->get_error_message()));
		}
		
		// Send the response
		wp_send_json_success(array(
			'message' => __('Connection successful!', 'faq-ai-generator'),
			'worker' => $worker_key,
			'result' => $result
		));
	}

	/**
	 * AJAX handler for updating worker settings.
	 *
	 * @since    1.0.0
	 */
	public function ajax_update_worker() {
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'faq_ai_admin_nonce')) {
			wp_send_json_error(array('message' => __('Security check failed.', 'faq-ai-generator')));
		}

		// Check capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('You do not have permission to do this.', 'faq-ai-generator')));
		}

		$worker_key = isset($_POST['worker']) ? sanitize_text_field($_POST['worker']) : '';
		$setting = isset($_POST['setting']) ? sanitize_text_field($_POST['setting']) : '';
		$value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';
		
		if (empty($worker_key) || empty($setting)) {
			wp_send_json_error(array('message' => __('Missing required parameters.', 'faq-ai-generator')));
		}

		// Update the worker setting
		$workers = get_option('faq_ai_generator_workers', array());
		
		if (!isset($workers[$worker_key])) {
			wp_send_json_error(array('message' => __('Worker not found.', 'faq-ai-generator')));
		}

		// Process the value based on setting type
		switch ($setting) {
			case 'url':
				$workers[$worker_key]['url'] = esc_url_raw($value);
				break;
			case 'rate_limit':
				$workers[$worker_key]['rate_limit'] = intval($value);
				// Ensure it's within range
				$workers[$worker_key]['rate_limit'] = max(0, min(200, $workers[$worker_key]['rate_limit']));
				break;
			case 'cooldown':
				$workers[$worker_key]['cooldown'] = intval($value);
				// Ensure it's within range
				$workers[$worker_key]['cooldown'] = max(1, min(30, $workers[$worker_key]['cooldown']));
				break;
			case 'enabled':
				$workers[$worker_key]['enabled'] = ($value === 'true');
				break;
			default:
				wp_send_json_error(array('message' => __('Invalid setting.', 'faq-ai-generator')));
				break;
		}

		// Save the updated settings
		update_option('faq_ai_generator_workers', $workers);
		
		// Send the response
		wp_send_json_success(array(
			'message' => __('Worker setting updated successfully.', 'faq-ai-generator'),
			'worker' => $worker_key,
			'setting' => $setting,
			'value' => $workers[$worker_key][$setting]
		));
	}
}