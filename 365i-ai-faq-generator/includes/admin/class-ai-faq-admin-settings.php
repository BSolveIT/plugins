<?php
/**
 * Admin settings management class for 365i AI FAQ Generator.
 * 
 * This class handles settings registration, sanitization, and 
 * settings field callbacks.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Admin
 * @since 2.1.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings management class.
 * 
 * @since 2.1.0
 */
class AI_FAQ_Admin_Settings {

	/**
	 * Initialize the settings component.
	 * 
	 * Set up hooks for settings registration and asset enqueuing.
	 * 
	 * @since 2.1.0
	 */
	public function init() {
		// Register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		
		// Enqueue admin scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register plugin settings.
	 * 
	 * @since 2.0.0
	 */
	public function register_settings() {
		// Register main options.
		register_setting(
			'ai_faq_gen_options',
			'ai_faq_gen_options',
			array(
				'sanitize_callback' => array( $this, 'sanitize_options' ),
			)
		);

		// Add settings sections.
		add_settings_section(
			'ai_faq_gen_general',
			__( 'General Settings', '365i-ai-faq-generator' ),
			array( $this, 'general_section_callback' ),
			'ai_faq_gen_settings'
		);

		add_settings_section(
			'ai_faq_gen_workers',
			__( 'Worker Configuration', '365i-ai-faq-generator' ),
			array( $this, 'workers_section_callback' ),
			'ai_faq_gen_workers'
		);

		// Add settings fields.
		add_settings_field(
			'default_faq_count',
			__( 'Default FAQ Count', '365i-ai-faq-generator' ),
			array( $this, 'default_faq_count_callback' ),
			'ai_faq_gen_settings',
			'ai_faq_gen_general'
		);

		add_settings_field(
			'auto_save_interval',
			__( 'Auto-save Interval (minutes)', '365i-ai-faq-generator' ),
			array( $this, 'auto_save_interval_callback' ),
			'ai_faq_gen_settings',
			'ai_faq_gen_general'
		);

		add_settings_field(
			'debug_mode',
			__( 'Debug Mode', '365i-ai-faq-generator' ),
			array( $this, 'debug_mode_callback' ),
			'ai_faq_gen_settings',
			'ai_faq_gen_general'
		);
	}

	/**
	 * Sanitize options.
	 * 
	 * @since 2.0.0
	 * @param array $options Raw options to sanitize.
	 * @return array Sanitized options.
	 */
	public function sanitize_options( $options ) {
		$sanitized = array();

		// Sanitize general settings.
		if ( isset( $options['settings'] ) ) {
			$sanitized['settings'] = array();
			
			if ( isset( $options['settings']['default_faq_count'] ) ) {
				$sanitized['settings']['default_faq_count'] = intval( $options['settings']['default_faq_count'] );
			}
			
			if ( isset( $options['settings']['auto_save_interval'] ) ) {
				$sanitized['settings']['auto_save_interval'] = intval( $options['settings']['auto_save_interval'] );
			}
			
			if ( isset( $options['settings']['debug_mode'] ) ) {
				$sanitized['settings']['debug_mode'] = (bool) $options['settings']['debug_mode'];
			}
		}

		// Sanitize worker settings.
		if ( isset( $options['workers'] ) ) {
			$sanitized['workers'] = array();
			
			foreach ( $options['workers'] as $worker_name => $config ) {
				$sanitized['workers'][ sanitize_key( $worker_name ) ] = array(
					'url' => esc_url_raw( $config['url'] ),
					'enabled' => (bool) $config['enabled'],
					'rate_limit' => intval( $config['rate_limit'] ),
				);
			}
		}

		// Sanitize other settings fields if they exist
		$text_fields = array( 
			'cloudflare_account_id', 
			'cloudflare_api_token', 
			'default_tone', 
			'default_length', 
			'default_schema_type',
			'log_level'
		);
		
		foreach ( $text_fields as $field ) {
			if ( isset( $options[$field] ) ) {
				$sanitized[$field] = sanitize_text_field( $options[$field] );
			}
		}
		
		// Sanitize numeric fields
		$numeric_fields = array( 
			'max_questions_per_batch', 
			'cache_duration' 
		);
		
		foreach ( $numeric_fields as $field ) {
			if ( isset( $options[$field] ) ) {
				$sanitized[$field] = intval( $options[$field] );
			}
		}
		
		// Sanitize boolean fields
		$boolean_fields = array( 
			'enable_auto_schema', 
			'enable_seo_optimization', 
			'enable_rate_limiting', 
			'enable_caching', 
			'enable_logging', 
			'enable_analytics' 
		);
		
		foreach ( $boolean_fields as $field ) {
			if ( isset( $options[$field] ) ) {
				$sanitized[$field] = (bool) $options[$field];
			}
		}

		return $sanitized;
	}

	/**
	 * General settings section callback.
	 * 
	 * @since 2.0.0
	 */
	public function general_section_callback() {
		echo '<p>' . esc_html__( 'Configure general plugin settings for frontend FAQ generation.', '365i-ai-faq-generator' ) . '</p>';
	}

	/**
	 * Workers section callback.
	 * 
	 * @since 2.0.0
	 */
	public function workers_section_callback() {
		echo '<p>' . esc_html__( 'Configure Cloudflare worker settings and rate limits for frontend use.', '365i-ai-faq-generator' ) . '</p>';
	}

	/**
	 * Default FAQ count field callback.
	 * 
	 * @since 2.0.0
	 */
	public function default_faq_count_callback() {
		$options = get_option( 'ai_faq_gen_options', array() );
		$value = isset( $options['settings']['default_faq_count'] ) ? $options['settings']['default_faq_count'] : 12;
		
		printf(
			'<input type="number" id="default_faq_count" name="ai_faq_gen_options[settings][default_faq_count]" value="%d" min="1" max="50" />',
			intval( $value )
		);
		echo '<p class="description">' . esc_html__( 'Default number of FAQ items to generate on frontend.', '365i-ai-faq-generator' ) . '</p>';
	}

	/**
	 * Auto-save interval field callback.
	 * 
	 * @since 2.0.0
	 */
	public function auto_save_interval_callback() {
		$options = get_option( 'ai_faq_gen_options', array() );
		$value = isset( $options['settings']['auto_save_interval'] ) ? $options['settings']['auto_save_interval'] : 3;
		
		printf(
			'<input type="number" id="auto_save_interval" name="ai_faq_gen_options[settings][auto_save_interval]" value="%d" min="1" max="60" />',
			intval( $value )
		);
		echo '<p class="description">' . esc_html__( 'Auto-save interval in minutes for local storage on frontend.', '365i-ai-faq-generator' ) . '</p>';
	}

	/**
	 * Debug mode field callback.
	 * 
	 * @since 2.0.0
	 */
	public function debug_mode_callback() {
		$options = get_option( 'ai_faq_gen_options', array() );
		$value = isset( $options['settings']['debug_mode'] ) ? $options['settings']['debug_mode'] : false;
		
		printf(
			'<input type="checkbox" id="debug_mode" name="ai_faq_gen_options[settings][debug_mode]" value="1" %s />',
			checked( $value, true, false )
		);
		echo '<label for="debug_mode">' . esc_html__( 'Enable debug mode for troubleshooting frontend issues.', '365i-ai-faq-generator' ) . '</label>';
	}

	/**
	 * Reset settings to defaults.
	 *
	 * Resets all plugin settings to their default values with unique worker URLs.
	 *
	 * @since 2.0.1
	 * @return array Result array with success status and message.
	 */
	public function reset_settings() {
		// Define default settings with unique worker URLs
		$default_settings = array(
			'settings' => array(
				'default_faq_count' => 12,
				'auto_save_interval' => 3,
				'debug_mode' => false,
			),
			'workers' => array(
				'question_generator' => array(
					'url' => 'https://faq-realtime-assistant-worker.winter-cake-bf57.workers.dev',
					'enabled' => true,
					'rate_limit' => 10,
				),
				'answer_generator' => array(
					'url' => 'https://faq-answer-generator-worker.winter-cake-bf57.workers.dev',
					'enabled' => true,
					'rate_limit' => 10,
				),
				'topic_generator' => array(
					'url' => 'https://faq-proxy-fetch.winter-cake-bf57.workers.dev',
					'enabled' => true,
					'rate_limit' => 10,
				),
				'faq_extractor' => array(
					'url' => 'https://url-to-faq-generator-worker.winter-cake-bf57.workers.dev',
					'enabled' => true,
					'rate_limit' => 10,
				),
				'faq_enhancer' => array(
					'url' => 'https://faq-enhancement-worker.winter-cake-bf57.workers.dev',
					'enabled' => true,
					'rate_limit' => 10,
				),
				'seo_analyzer' => array(
					'url' => 'https://faq-seo-analyzer-worker.winter-cake-bf57.workers.dev',
					'enabled' => true,
					'rate_limit' => 10,
				),
			),
			'cloudflare_account_id' => '',
			'cloudflare_api_token' => '',
			'default_tone' => 'professional',
			'default_length' => 'medium',
			'default_schema_type' => 'faq',
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

		// Apply filters to allow customization of defaults
		$default_settings = apply_filters( 'ai_faq_gen_default_settings', $default_settings );

		// Update the option
		$result = update_option( 'ai_faq_gen_options', $default_settings );

		if ( $result ) {
			// Log the reset action
			error_log( sprintf(
				'[365i AI FAQ] Admin %s reset all settings to defaults',
				wp_get_current_user()->user_login
			) );

			return array(
				'success' => true,
				'message' => __( 'Settings have been reset to defaults successfully.', '365i-ai-faq-generator' ),
			);
		} else {
			return array(
				'success' => false,
				'message' => __( 'Failed to reset settings. Please try again.', '365i-ai-faq-generator' ),
			);
		}
	}

	/**
	 * Import settings from provided data.
	 *
	 * Validates and imports settings from JSON data.
	 *
	 * @since 2.0.1
	 * @param array $import_data Settings data to import.
	 * @return array Result array with success status and message.
	 */
	public function import_settings( $import_data ) {
		if ( ! is_array( $import_data ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid import data format.', '365i-ai-faq-generator' ),
			);
		}

		// Get current settings as a base
		$current_settings = get_option( 'ai_faq_gen_options', array() );
		
		// Merge imported settings with current settings
		$merged_settings = wp_parse_args( $import_data, $current_settings );
		
		// Sanitize the merged settings
		$sanitized_settings = $this->sanitize_options( $merged_settings );
		
		// Update the option
		$result = update_option( 'ai_faq_gen_options', $sanitized_settings );

		if ( $result ) {
			// Count imported items
			$imported_count = 0;
			if ( isset( $import_data['settings'] ) ) {
				$imported_count += count( $import_data['settings'] );
			}
			if ( isset( $import_data['workers'] ) ) {
				$imported_count += count( $import_data['workers'] );
			}
			
			// Log the import action
			error_log( sprintf(
				'[365i AI FAQ] Admin %s imported %d settings',
				wp_get_current_user()->user_login,
				$imported_count
			) );

			return array(
				'success' => true,
				'message' => sprintf(
					/* translators: %d: Number of imported settings */
					__( 'Successfully imported %d settings.', '365i-ai-faq-generator' ),
					$imported_count
				),
				'imported_count' => $imported_count,
			);
		} else {
			return array(
				'success' => false,
				'message' => __( 'Failed to import settings. Please try again.', '365i-ai-faq-generator' ),
			);
		}
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 2.0.0
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Only load on our plugin pages.
		if ( strpos( $hook_suffix, 'ai-faq-generator' ) === false ) {
			return;
		}

		// Enqueue admin CSS.
		wp_enqueue_style(
			'ai-faq-gen-admin',
			AI_FAQ_GEN_URL . 'assets/css/admin.css',
			array(),
			AI_FAQ_GEN_VERSION
		);

		// Enqueue admin JavaScript.
		wp_enqueue_script(
			'ai-faq-gen-admin',
			AI_FAQ_GEN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-util' ),
			AI_FAQ_GEN_VERSION,
			true
		);

		// Enqueue worker test results assets for workers page.
		if ( strpos( $hook_suffix, 'ai-faq-generator-workers' ) !== false ) {
			wp_enqueue_style(
				'ai-faq-gen-worker-test-results',
				AI_FAQ_GEN_URL . 'assets/css/worker-test-results.css',
				array( 'ai-faq-gen-admin' ),
				AI_FAQ_GEN_VERSION
			);

			wp_enqueue_script(
				'ai-faq-gen-worker-test-results',
				AI_FAQ_GEN_URL . 'assets/js/worker-test-results.js',
				array( 'jquery', 'ai-faq-gen-admin' ),
				AI_FAQ_GEN_VERSION,
				true
			);

			wp_enqueue_script(
				'ai-faq-gen-workers-admin',
				AI_FAQ_GEN_URL . 'assets/js/workers-admin.js',
				array( 'jquery', 'ai-faq-gen-admin', 'ai-faq-gen-worker-test-results' ),
				AI_FAQ_GEN_VERSION,
				true
			);
		}

		// Enqueue settings-specific assets for settings page.
		if ( strpos( $hook_suffix, 'ai-faq-generator-settings' ) !== false ) {
			wp_enqueue_script(
				'ai-faq-gen-settings-admin',
				AI_FAQ_GEN_URL . 'assets/js/settings-admin.js',
				array( 'jquery', 'ai-faq-gen-admin' ),
				AI_FAQ_GEN_VERSION,
				true
			);
			
			// Localize script for settings page specifically.
			wp_localize_script(
				'ai-faq-gen-settings-admin',
				'aiFaqGen',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'adminUrl' => admin_url( 'admin.php' ),
					'nonce' => wp_create_nonce( 'ai_faq_gen_nonce' ),
					'strings' => array(
						'loading' => __( 'Loading...', '365i-ai-faq-generator' ),
						'error' => __( 'An error occurred. Please try again.', '365i-ai-faq-generator' ),
						'success' => __( 'Operation completed successfully.', '365i-ai-faq-generator' ),
						'confirm' => __( 'Are you sure?', '365i-ai-faq-generator' ),
					),
				)
			);
		}
		
		// Enqueue analytics-specific assets for analytics page.
		if ( strpos( $hook_suffix, 'ai-faq-generator-analytics' ) !== false ) {
			// Enqueue Chart.js library
			wp_enqueue_script(
				'chartjs',
				'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
				array(),
				'3.7.1',
				true
			);
			
			wp_enqueue_style(
				'ai-faq-gen-analytics-dashboard',
				AI_FAQ_GEN_URL . 'assets/css/analytics-dashboard.css',
				array( 'ai-faq-gen-admin' ),
				AI_FAQ_GEN_VERSION
			);
			
			wp_enqueue_script(
				'ai-faq-gen-analytics-dashboard',
				AI_FAQ_GEN_URL . 'assets/js/analytics-dashboard.js',
				array( 'jquery', 'chartjs', 'ai-faq-gen-admin' ),
				AI_FAQ_GEN_VERSION,
				true
			);
		}

		// Enqueue rate limiting assets for rate limiting pages.
		if ( strpos( $hook_suffix, 'ai-faq-generator-rate-limiting' ) !== false ||
		     strpos( $hook_suffix, 'ai-faq-generator-ip-management' ) !== false ||
		     strpos( $hook_suffix, 'ai-faq-generator-usage-analytics' ) !== false ) {
			
			wp_enqueue_style(
				'ai-faq-rate-limiting-admin',
				AI_FAQ_GEN_URL . 'assets/css/rate-limiting-admin.css',
				array( 'ai-faq-gen-admin' ),
				AI_FAQ_GEN_VERSION
			);

			wp_enqueue_script(
				'ai-faq-rate-limiting-admin',
				AI_FAQ_GEN_URL . 'assets/js/rate-limiting-admin.js',
				array( 'jquery', 'wp-util', 'ai-faq-gen-admin' ),
				AI_FAQ_GEN_VERSION,
				true
			);

			wp_localize_script(
				'ai-faq-rate-limiting-admin',
				'aiFAQRateLimit',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'ai_faq_rate_limit_nonce' ),
					'workers'  => array(
						'faq-answer-generator-worker'  => 'FAQ Answer Generator',
						'faq-realtime-assistant-worker' => 'Realtime Assistant',
						'faq-enhancement-worker'       => 'FAQ Enhancement',
						'faq-seo-analyzer-worker'      => 'SEO Analyzer',
						'faq-proxy-fetch'              => 'Proxy Fetch',
						'url-to-faq-generator-worker'  => 'URL to FAQ Generator',
					),
				)
			);
		}

		// Localize script.
		wp_localize_script(
			'ai-faq-gen-admin',
			'aiFaqGen',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'adminUrl' => admin_url( 'admin.php' ),
				'nonce' => wp_create_nonce( 'ai_faq_gen_nonce' ),
				'strings' => array(
					'loading' => __( 'Loading...', '365i-ai-faq-generator' ),
					'error' => __( 'An error occurred. Please try again.', '365i-ai-faq-generator' ),
					'success' => __( 'Operation completed successfully.', '365i-ai-faq-generator' ),
					'confirm' => __( 'Are you sure?', '365i-ai-faq-generator' ),
				),
			)
		);
	}
}