<?php
/**
 * Dynamic settings handler for 365i AI FAQ Generator.
 * 
 * This class manages the dynamic retrieval, processing, and synchronization
 * of plugin settings between admin configuration and frontend interface.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Settings
 * @since 2.1.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dynamic settings handler class.
 * 
 * Handles real-time synchronization between backend configuration
 * and frontend display elements with comprehensive caching and
 * fallback mechanisms.
 * 
 * @since 2.1.0
 */
class AI_FAQ_Settings_Handler {

	/**
	 * Settings cache key.
	 * 
	 * @since 2.1.0
	 * @var string
	 */
	private $cache_key = 'ai_faq_gen_processed_settings';

	/**
	 * Cache group for settings.
	 * 
	 * @since 2.1.0
	 * @var string
	 */
	private $cache_group = 'ai_faq_gen_settings';

	/**
	 * Default cache duration in seconds.
	 * 
	 * @since 2.1.0
	 * @var int
	 */
	private $cache_duration = 3600; // 1 hour

	/**
	 * Settings version for cache invalidation.
	 * 
	 * @since 2.1.0
	 * @var string
	 */
	private $settings_version = '2.1.0';

	/**
	 * Constructor.
	 * 
	 * Initialize the settings handler and set up hooks.
	 * 
	 * @since 2.1.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks and filters.
	 * 
	 * @since 2.1.0
	 */
	private function init_hooks() {
		// Clear cache when settings are updated
		add_action( 'update_option_ai_faq_gen_options', array( $this, 'clear_settings_cache' ) );
		add_action( 'delete_option_ai_faq_gen_options', array( $this, 'clear_settings_cache' ) );
		
		// AJAX handlers for real-time settings sync
		add_action( 'wp_ajax_ai_faq_get_settings', array( $this, 'ajax_get_settings' ) );
		add_action( 'wp_ajax_nopriv_ai_faq_get_settings', array( $this, 'ajax_get_settings' ) );
		add_action( 'wp_ajax_ai_faq_refresh_settings', array( $this, 'ajax_refresh_settings' ) );
		add_action( 'wp_ajax_nopriv_ai_faq_refresh_settings', array( $this, 'ajax_refresh_settings' ) );
		
		// Add settings injection to head
		add_action( 'wp_head', array( $this, 'inject_dynamic_css_variables' ) );
		add_action( 'admin_head', array( $this, 'inject_dynamic_css_variables' ) );
	}

	/**
	 * Get comprehensive plugin settings with processing and fallbacks.
	 * 
	 * Retrieves all plugin settings, processes them for frontend use,
	 * and provides intelligent fallbacks for missing or corrupted data.
	 * 
	 * @since 2.1.0
	 * @param bool $use_cache Whether to use cached settings.
	 * @return array Processed settings array.
	 */
	public function get_comprehensive_settings( $use_cache = true ) {
		// Try to get from cache first
		if ( $use_cache ) {
			$cached_settings = $this->get_cached_settings();
			if ( false !== $cached_settings ) {
				return $cached_settings;
			}
		}

		// Get raw settings from database
		$raw_settings = get_option( 'ai_faq_gen_options', array() );
		
		// Process and enhance settings
		$processed_settings = $this->process_raw_settings( $raw_settings );
		
		// Apply filters for extensibility
		$processed_settings = apply_filters( 'ai_faq_gen_processed_settings', $processed_settings, $raw_settings );
		
		// Cache the processed settings
		if ( $use_cache ) {
			$this->cache_settings( $processed_settings );
		}
		
		return $processed_settings;
	}

	/**
	 * Process raw settings into frontend-ready format.
	 * 
	 * @since 2.1.0
	 * @param array $raw_settings Raw settings from database.
	 * @return array Processed settings.
	 */
	private function process_raw_settings( $raw_settings ) {
		// Define default settings structure
		$defaults = $this->get_default_settings();
		
		// Merge with defaults to ensure all keys exist
		$settings = wp_parse_args( $raw_settings, $defaults );
		
		// Process each settings category
		$processed = array(
			'version' => $this->settings_version,
			'timestamp' => current_time( 'timestamp' ),
			'general' => $this->process_general_settings( $settings ),
			'generation' => $this->process_generation_settings( $settings ),
			'ui' => $this->process_ui_settings( $settings ),
			'performance' => $this->process_performance_settings( $settings ),
			'workers' => $this->process_workers_settings( $settings ),
			'localization' => $this->process_localization_settings( $settings ),
			'advanced' => $this->process_advanced_settings( $settings ),
			'css_variables' => $this->generate_css_variables( $settings ),
			'js_config' => $this->generate_js_config( $settings ),
		);
		
		// Add computed values
		$processed['computed'] = $this->compute_derived_values( $processed );
		
		return $processed;
	}

	/**
	 * Get default settings structure.
	 * 
	 * @since 2.1.0
	 * @return array Default settings.
	 */
	private function get_default_settings() {
		return array(
			'settings' => array(
				'default_faq_count' => 12,
				'auto_save_interval' => 3,
				'debug_mode' => false,
			),
			'workers' => array(),
			'cloudflare_account_id' => '',
			'cloudflare_api_token' => '',
			'default_tone' => 'professional',
			'default_length' => 'medium',
			'default_schema_type' => 'json-ld',
			'max_questions_per_batch' => 20,
			'cache_duration' => 3600,
			'enable_auto_schema' => true,
			'enable_seo_optimization' => true,
			'enable_rate_limiting' => true,
			'enable_caching' => true,
			'enable_logging' => false,
			'enable_analytics' => true,
			'log_level' => 'error',
		);
	}

	/**
	 * Process general settings.
	 * 
	 * @since 2.1.0
	 * @param array $settings Raw settings.
	 * @return array Processed general settings.
	 */
	private function process_general_settings( $settings ) {
		$general_settings = isset( $settings['settings'] ) ? $settings['settings'] : array();
		
		return array(
			'default_faq_count' => absint( $settings['default_faq_count'] ?? $general_settings['default_faq_count'] ?? 12 ),
			'auto_save_interval' => absint( $general_settings['auto_save_interval'] ?? 3 ),
			'debug_mode' => (bool) ( $general_settings['debug_mode'] ?? false ),
			'max_questions_per_batch' => absint( $settings['max_questions_per_batch'] ?? 20 ),
			'cache_duration' => absint( $settings['cache_duration'] ?? 3600 ),
		);
	}

	/**
	 * Process generation settings.
	 * 
	 * @since 2.1.0
	 * @param array $settings Raw settings.
	 * @return array Processed generation settings.
	 */
	private function process_generation_settings( $settings ) {
		// Tone options with labels
		$tone_options = array(
			'professional' => __( 'Professional', '365i-ai-faq-generator' ),
			'friendly' => __( 'Friendly', '365i-ai-faq-generator' ),
			'casual' => __( 'Casual', '365i-ai-faq-generator' ),
			'technical' => __( 'Technical', '365i-ai-faq-generator' ),
		);
		
		// Length options with labels
		$length_options = array(
			'short' => __( 'Short (1-2 sentences)', '365i-ai-faq-generator' ),
			'medium' => __( 'Medium (2-4 sentences)', '365i-ai-faq-generator' ),
			'long' => __( 'Long (4+ sentences)', '365i-ai-faq-generator' ),
		);
		
		// Schema options with labels
		$schema_options = array(
			'json-ld' => __( 'JSON-LD (Recommended)', '365i-ai-faq-generator' ),
			'microdata' => __( 'Microdata', '365i-ai-faq-generator' ),
			'rdfa' => __( 'RDFa', '365i-ai-faq-generator' ),
			'html' => __( 'Plain HTML', '365i-ai-faq-generator' ),
		);
		
		$default_tone = sanitize_text_field( $settings['default_tone'] ?? 'professional' );
		$default_length = sanitize_text_field( $settings['default_length'] ?? 'medium' );
		$default_schema = sanitize_text_field( $settings['default_schema_type'] ?? 'json-ld' );
		
		return array(
			'default_tone' => $default_tone,
			'default_tone_label' => $tone_options[ $default_tone ] ?? $tone_options['professional'],
			'tone_options' => $tone_options,
			'default_length' => $default_length,
			'default_length_label' => $length_options[ $default_length ] ?? $length_options['medium'],
			'length_options' => $length_options,
			'default_schema_type' => $default_schema,
			'default_schema_label' => $schema_options[ $default_schema ] ?? $schema_options['json-ld'],
			'schema_options' => $schema_options,
			'enable_auto_schema' => (bool) ( $settings['enable_auto_schema'] ?? true ),
			'enable_seo_optimization' => (bool) ( $settings['enable_seo_optimization'] ?? true ),
		);
	}

	/**
	 * Process UI settings.
	 * 
	 * @since 2.1.0
	 * @param array $settings Raw settings.
	 * @return array Processed UI settings.
	 */
	private function process_ui_settings( $settings ) {
		return array(
			'theme' => 'default', // Could be made configurable
			'show_advanced_options' => (bool) ( $settings['show_advanced_options'] ?? false ),
			'enable_animations' => (bool) ( $settings['enable_animations'] ?? true ),
			'compact_mode' => (bool) ( $settings['compact_mode'] ?? false ),
			'color_scheme' => sanitize_text_field( $settings['color_scheme'] ?? 'default' ),
		);
	}

	/**
	 * Process performance settings.
	 * 
	 * @since 2.1.0
	 * @param array $settings Raw settings.
	 * @return array Processed performance settings.
	 */
	private function process_performance_settings( $settings ) {
		return array(
			'enable_caching' => (bool) ( $settings['enable_caching'] ?? true ),
			'enable_rate_limiting' => (bool) ( $settings['enable_rate_limiting'] ?? true ),
			'cache_duration' => absint( $settings['cache_duration'] ?? 3600 ),
			'lazy_load_faqs' => (bool) ( $settings['lazy_load_faqs'] ?? false ),
			'debounce_delay' => absint( $settings['debounce_delay'] ?? 300 ),
		);
	}

	/**
	 * Process workers settings.
	 * 
	 * @since 2.1.0
	 * @param array $settings Raw settings.
	 * @return array Processed workers settings.
	 */
	private function process_workers_settings( $settings ) {
		$workers = isset( $settings['workers'] ) ? $settings['workers'] : array();
		$processed_workers = array();
		
		foreach ( $workers as $worker_name => $config ) {
			$processed_workers[ $worker_name ] = array(
				'url' => esc_url_raw( $config['url'] ?? '' ),
				'enabled' => (bool) ( $config['enabled'] ?? false ),
				'rate_limit' => absint( $config['rate_limit'] ?? 10 ),
				'timeout' => absint( $config['timeout'] ?? 30 ),
				'retry_attempts' => absint( $config['retry_attempts'] ?? 3 ),
			);
		}
		
		// Count enabled workers
		$enabled_count = count( array_filter( $processed_workers, function( $worker ) {
			return $worker['enabled'];
		} ) );
		
		return array(
			'workers' => $processed_workers,
			'enabled_count' => $enabled_count,
			'has_enabled_workers' => $enabled_count > 0,
			'cloudflare_account_id' => sanitize_text_field( $settings['cloudflare_account_id'] ?? '' ),
			'api_configured' => ! empty( $settings['cloudflare_account_id'] ) && ! empty( $settings['cloudflare_api_token'] ),
		);
	}

	/**
	 * Process localization settings.
	 * 
	 * @since 2.1.0
	 * @param array $settings Raw settings.
	 * @return array Processed localization settings.
	 */
	private function process_localization_settings( $settings ) {
		$locale = get_locale();
		$language = substr( $locale, 0, 2 );
		
		return array(
			'locale' => $locale,
			'language' => $language,
			'text_direction' => is_rtl() ? 'rtl' : 'ltr',
			'date_format' => get_option( 'date_format', 'F j, Y' ),
			'time_format' => get_option( 'time_format', 'g:i a' ),
			'timezone' => wp_timezone_string(),
			'strings' => $this->get_localized_strings(),
		);
	}

	/**
	 * Process advanced settings.
	 * 
	 * @since 2.1.0
	 * @param array $settings Raw settings.
	 * @return array Processed advanced settings.
	 */
	private function process_advanced_settings( $settings ) {
		return array(
			'enable_logging' => (bool) ( $settings['enable_logging'] ?? false ),
			'log_level' => sanitize_text_field( $settings['log_level'] ?? 'error' ),
			'enable_analytics' => (bool) ( $settings['enable_analytics'] ?? true ),
			'custom_css' => wp_kses_post( $settings['custom_css'] ?? '' ),
			'custom_js' => wp_kses_post( $settings['custom_js'] ?? '' ),
			'developer_mode' => (bool) ( $settings['developer_mode'] ?? false ),
		);
	}

	/**
	 * Generate CSS variables from settings.
	 * 
	 * @since 2.1.0
	 * @param array $settings Raw settings.
	 * @return array CSS variables.
	 */
	private function generate_css_variables( $settings ) {
		$css_vars = array(
			'--ai-faq-primary-color' => '#0073aa',
			'--ai-faq-secondary-color' => '#005177',
			'--ai-faq-accent-color' => '#00a0d2',
			'--ai-faq-text-color' => '#32373c',
			'--ai-faq-background-color' => '#ffffff',
			'--ai-faq-border-color' => '#ddd',
			'--ai-faq-border-radius' => '8px',
			'--ai-faq-shadow' => '0 2px 8px rgba(0,0,0,0.1)',
			'--ai-faq-transition' => '0.3s ease',
			'--ai-faq-font-family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
			'--ai-faq-font-size-base' => '16px',
			'--ai-faq-line-height' => '1.6',
			'--ai-faq-spacing-xs' => '4px',
			'--ai-faq-spacing-sm' => '8px',
			'--ai-faq-spacing-md' => '16px',
			'--ai-faq-spacing-lg' => '24px',
			'--ai-faq-spacing-xl' => '32px',
		);
		
		// Allow customization via settings
		if ( ! empty( $settings['color_scheme'] ) && 'dark' === $settings['color_scheme'] ) {
			$css_vars['--ai-faq-text-color'] = '#ffffff';
			$css_vars['--ai-faq-background-color'] = '#1e1e1e';
			$css_vars['--ai-faq-border-color'] = '#333';
		}
		
		return apply_filters( 'ai_faq_gen_css_variables', $css_vars, $settings );
	}

	/**
	 * Generate JavaScript configuration from settings.
	 * 
	 * @since 2.1.0
	 * @param array $settings Raw settings.
	 * @return array JavaScript configuration.
	 */
	private function generate_js_config( $settings ) {
		$general_settings = isset( $settings['settings'] ) ? $settings['settings'] : array();
		
		$js_config = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'ai_faq_generate_nonce' ),
			'settings_nonce' => wp_create_nonce( 'ai_faq_settings_nonce' ),
			'auto_save_interval' => absint( $settings['auto_save_interval'] ?? $general_settings['auto_save_interval'] ?? 3 ) * 60000, // Convert to milliseconds
			'debug_mode' => (bool) ( $settings['debug_mode'] ?? $general_settings['debug_mode'] ?? false ),
			'enable_animations' => (bool) ( $settings['enable_animations'] ?? true ),
			'debounce_delay' => absint( $settings['debounce_delay'] ?? 300 ),
			'max_questions' => absint( $settings['max_questions_per_batch'] ?? 20 ),
			'cache_duration' => absint( $settings['cache_duration'] ?? 3600 ),
			'endpoints' => array(
				'generate' => 'ai_faq_generate',
				'export' => 'ai_faq_export',
				'get_settings' => 'ai_faq_get_settings',
				'refresh_settings' => 'ai_faq_refresh_settings',
			),
		);
		
		return apply_filters( 'ai_faq_gen_js_config', $js_config, $settings );
	}

	/**
	 * Compute derived values from processed settings.
	 * 
	 * @since 2.1.0
	 * @param array $processed_settings Processed settings.
	 * @return array Computed values.
	 */
	private function compute_derived_values( $processed_settings ) {
		return array(
			'is_fully_configured' => $processed_settings['workers']['api_configured'] && $processed_settings['workers']['has_enabled_workers'],
			'estimated_cache_size' => $this->estimate_cache_size( $processed_settings ),
			'performance_score' => $this->calculate_performance_score( $processed_settings ),
			'feature_availability' => $this->check_feature_availability( $processed_settings ),
		);
	}

	/**
	 * Get localized strings for frontend use.
	 * 
	 * @since 2.1.0
	 * @return array Localized strings.
	 */
	private function get_localized_strings() {
		return array(
			'loading' => __( 'Loading...', '365i-ai-faq-generator' ),
			'error' => __( 'An error occurred. Please try again.', '365i-ai-faq-generator' ),
			'success' => __( 'Operation completed successfully.', '365i-ai-faq-generator' ),
			'confirm' => __( 'Are you sure?', '365i-ai-faq-generator' ),
			'generating' => __( 'Generating FAQ...', '365i-ai-faq-generator' ),
			'enhancing' => __( 'Enhancing FAQ...', '365i-ai-faq-generator' ),
			'analyzing' => __( 'Analyzing SEO...', '365i-ai-faq-generator' ),
			'extracting' => __( 'Extracting FAQ...', '365i-ai-faq-generator' ),
			'no_results' => __( 'No results found.', '365i-ai-faq-generator' ),
			'invalid_url' => __( 'Please enter a valid URL.', '365i-ai-faq-generator' ),
			'topic_required' => __( 'Please enter a topic.', '365i-ai-faq-generator' ),
			'saved' => __( 'FAQ saved successfully.', '365i-ai-faq-generator' ),
			'exported' => __( 'FAQ exported successfully.', '365i-ai-faq-generator' ),
			'copied' => __( 'Copied to clipboard!', '365i-ai-faq-generator' ),
			'schema_generated' => __( 'Schema markup generated successfully.', '365i-ai-faq-generator' ),
			'settings_updated' => __( 'Settings updated successfully.', '365i-ai-faq-generator' ),
			'cache_cleared' => __( 'Cache cleared successfully.', '365i-ai-faq-generator' ),
		);
	}

	/**
	 * Get cached settings.
	 * 
	 * @since 2.1.0
	 * @return array|false Cached settings or false if not found.
	 */
	private function get_cached_settings() {
		$cached = wp_cache_get( $this->cache_key, $this->cache_group );
		
		if ( false === $cached ) {
			// Try transient as fallback
			$cached = get_transient( $this->cache_key );
		}
		
		// Validate cache version
		if ( is_array( $cached ) && isset( $cached['version'] ) && $cached['version'] === $this->settings_version ) {
			return $cached;
		}
		
		return false;
	}

	/**
	 * Cache processed settings.
	 * 
	 * @since 2.1.0
	 * @param array $settings Processed settings to cache.
	 */
	private function cache_settings( $settings ) {
		// Use both object cache and transients for reliability
		wp_cache_set( $this->cache_key, $settings, $this->cache_group, $this->cache_duration );
		set_transient( $this->cache_key, $settings, $this->cache_duration );
	}

	/**
	 * Clear settings cache.
	 * 
	 * @since 2.1.0
	 */
	public function clear_settings_cache() {
		wp_cache_delete( $this->cache_key, $this->cache_group );
		delete_transient( $this->cache_key );
		
		// Trigger action for other components
		do_action( 'ai_faq_gen_settings_cache_cleared' );
	}

	/**
	 * AJAX handler to get current settings.
	 * 
	 * @since 2.1.0
	 */
	public function ajax_get_settings() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'ai_faq_settings_nonce' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Security verification failed.', '365i-ai-faq-generator' ),
			) );
		}
		
		try {
			$settings = $this->get_comprehensive_settings();
			
			wp_send_json_success( array(
				'settings' => $settings,
				'message' => __( 'Settings retrieved successfully.', '365i-ai-faq-generator' ),
			) );
		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => __( 'Failed to retrieve settings.', '365i-ai-faq-generator' ),
				'debug' => WP_DEBUG ? $e->getMessage() : '',
			) );
		}
	}

	/**
	 * AJAX handler to refresh settings cache.
	 * 
	 * @since 2.1.0
	 */
	public function ajax_refresh_settings() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'ai_faq_settings_nonce' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Security verification failed.', '365i-ai-faq-generator' ),
			) );
		}
		
		try {
			// Clear cache and get fresh settings
			$this->clear_settings_cache();
			$settings = $this->get_comprehensive_settings( false );
			
			wp_send_json_success( array(
				'settings' => $settings,
				'message' => __( 'Settings refreshed successfully.', '365i-ai-faq-generator' ),
			) );
		} catch ( Exception $e ) {
			wp_send_json_error( array(
				'message' => __( 'Failed to refresh settings.', '365i-ai-faq-generator' ),
				'debug' => WP_DEBUG ? $e->getMessage() : '',
			) );
		}
	}

	/**
	 * Inject dynamic CSS variables into head.
	 * 
	 * @since 2.1.0
	 */
	public function inject_dynamic_css_variables() {
		// Only inject on pages with our shortcode or in admin
		if ( ! is_admin() ) {
			global $post;
			if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'ai_faq_generator' ) ) {
				return;
			}
		}
		
		$settings = $this->get_comprehensive_settings();
		$css_variables = $settings['css_variables'] ?? array();
		
		if ( empty( $css_variables ) ) {
			return;
		}
		
		echo "<style id=\"ai-faq-gen-css-variables\">\n";
		echo ":root {\n";
		foreach ( $css_variables as $property => $value ) {
			echo "  " . esc_attr( $property ) . ": " . esc_attr( $value ) . ";\n";
		}
		echo "}\n";
		echo "</style>\n";
	}

	/**
	 * Estimate cache size for performance monitoring.
	 * 
	 * @since 2.1.0
	 * @param array $settings Processed settings.
	 * @return string Estimated cache size.
	 */
	private function estimate_cache_size( $settings ) {
		$serialized = serialize( $settings );
		$size_bytes = strlen( $serialized );
		
		if ( $size_bytes < 1024 ) {
			return $size_bytes . ' B';
		} elseif ( $size_bytes < 1048576 ) {
			return round( $size_bytes / 1024, 2 ) . ' KB';
		} else {
			return round( $size_bytes / 1048576, 2 ) . ' MB';
		}
	}

	/**
	 * Calculate performance score based on settings.
	 * 
	 * @since 2.1.0
	 * @param array $settings Processed settings.
	 * @return int Performance score (0-100).
	 */
	private function calculate_performance_score( $settings ) {
		$score = 50; // Base score
		
		// Bonus points for performance features
		if ( $settings['performance']['enable_caching'] ) {
			$score += 20;
		}
		if ( $settings['performance']['enable_rate_limiting'] ) {
			$score += 10;
		}
		if ( $settings['workers']['has_enabled_workers'] ) {
			$score += 15;
		}
		
		// Penalty for debug mode in production
		if ( $settings['general']['debug_mode'] && ! WP_DEBUG ) {
			$score -= 10;
		}
		
		// Bonus for reasonable cache duration
		$cache_duration = $settings['general']['cache_duration'];
		if ( $cache_duration >= 1800 && $cache_duration <= 7200 ) { // 30 min to 2 hours
			$score += 5;
		}
		
		return max( 0, min( 100, $score ) );
	}

	/**
	 * Check feature availability based on settings.
	 * 
	 * @since 2.1.0
	 * @param array $settings Processed settings.
	 * @return array Feature availability flags.
	 */
	private function check_feature_availability( $settings ) {
		return array(
			'can_generate_faqs' => $settings['workers']['has_enabled_workers'],
			'can_use_schema' => $settings['generation']['enable_auto_schema'],
			'can_use_seo' => $settings['generation']['enable_seo_optimization'],
			'can_use_caching' => $settings['performance']['enable_caching'],
			'can_use_analytics' => $settings['advanced']['enable_analytics'],
			'can_debug' => $settings['general']['debug_mode'],
		);
	}
}