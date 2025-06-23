<?php
/**
 * Centralized Logger for 365i AI FAQ Generator.
 * 
 * This class provides a centralized logging system that integrates with
 * existing WordPress infrastructure and plugin settings to replace direct
 * error_log() calls throughout the plugin.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Logging
 * @since 2.2.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized logger class for the AI FAQ Generator plugin.
 * 
 * Provides performance-optimized logging with level filtering, settings
 * integration, and WordPress debug.log output. Maintains existing message
 * format patterns while providing structured logging capabilities.
 * 
 * @since 2.2.0
 */
class AI_FAQ_365i_Logger {

	/**
	 * Plugin prefix for log messages.
	 * 
	 * @since 2.2.0
	 * @var string
	 */
	private $plugin_prefix = '[365i AI FAQ]';

	/**
	 * Log level definitions for priority filtering.
	 * 
	 * @since 2.2.0
	 * @var array
	 */
	private $log_levels = array(
		'error'   => 1,
		'warning' => 2,
		'info'    => 3,
		'debug'   => 4,
	);

	/**
	 * Settings handler instance for configuration access.
	 * 
	 * @since 2.2.0
	 * @var AI_FAQ_Settings_Handler|null
	 */
	private $settings_handler = null;

	/**
	 * Cached logging enabled status for performance.
	 * 
	 * @since 2.2.0
	 * @var bool|null
	 */
	private $logging_enabled_cache = null;

	/**
	 * Cached current log level for performance.
	 * 
	 * @since 2.2.0
	 * @var string|null
	 */
	private $current_log_level_cache = null;

	/**
	 * Singleton instance.
	 * 
	 * @since 2.2.0
	 * @var AI_FAQ_365i_Logger|null
	 */
	private static $instance = null;

	/**
	 * Constructor.
	 * 
	 * Initialize the logger and set up settings integration.
	 * 
	 * @since 2.2.0
	 */
	private function __construct() {
		// Private constructor for singleton pattern
	}

	/**
	 * Get the singleton instance of the logger.
	 * 
	 * @since 2.2.0
	 * @return AI_FAQ_365i_Logger Logger instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Primary logging method with level filtering and performance optimization.
	 * 
	 * @since 2.2.0
	 * @param string $level   Log level (error, warning, info, debug).
	 * @param string $message Log message.
	 * @param array  $context Optional context data for structured logging.
	 * @return bool Whether the message was logged.
	 */
	public function log( $level, $message, $context = array() ) {
		// Performance optimization: Early return if logging disabled
		if ( ! $this->is_logging_enabled() ) {
			return false;
		}

		// Validate and sanitize log level
		$level = $this->validate_log_level( $level );
		if ( false === $level ) {
			return false;
		}

		// Performance optimization: Check level filtering
		if ( ! $this->should_log( $level ) ) {
			return false;
		}

		// Sanitize message and context
		$message = sanitize_text_field( $message );
		$context = $this->sanitize_context( $context );

		// Build the final log entry
		$log_entry = $this->build_log_entry( $level, $message, $context );

		// Write to WordPress debug.log using error_log
		error_log( $log_entry );

		return true;
	}

	/**
	 * Log an error message.
	 * 
	 * @since 2.2.0
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 * @return bool Whether the message was logged.
	 */
	public function error( $message, $context = array() ) {
		return $this->log( 'error', $message, $context );
	}

	/**
	 * Log a warning message.
	 * 
	 * @since 2.2.0
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 * @return bool Whether the message was logged.
	 */
	public function warning( $message, $context = array() ) {
		return $this->log( 'warning', $message, $context );
	}

	/**
	 * Log an info message.
	 * 
	 * @since 2.2.0
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 * @return bool Whether the message was logged.
	 */
	public function info( $message, $context = array() ) {
		return $this->log( 'info', $message, $context );
	}

	/**
	 * Log a debug message.
	 * 
	 * @since 2.2.0
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 * @return bool Whether the message was logged.
	 */
	public function debug( $message, $context = array() ) {
		return $this->log( 'debug', $message, $context );
	}

	/**
	 * Check if logging is enabled in plugin settings.
	 * 
	 * Uses caching for performance optimization to avoid repeated
	 * settings retrieval.
	 * 
	 * @since 2.2.0
	 * @return bool Whether logging is enabled.
	 */
	public function is_logging_enabled() {
		// Use cached value if available
		if ( null !== $this->logging_enabled_cache ) {
			return $this->logging_enabled_cache;
		}

		// Get settings handler
		$settings_handler = $this->get_settings_handler();
		if ( null === $settings_handler ) {
			// Fallback: check direct option access
			$options = get_option( 'ai_faq_gen_options', array() );
			$this->logging_enabled_cache = ! empty( $options['enable_logging'] );
		} else {
			// Use settings handler for consistent access
			$settings = $settings_handler->get_comprehensive_settings();
			$this->logging_enabled_cache = ! empty( $settings['advanced']['enable_logging'] );
		}

		return $this->logging_enabled_cache;
	}

	/**
	 * Check if a message should be logged based on current log level setting.
	 * 
	 * @since 2.2.0
	 * @param string $level Message log level.
	 * @return bool Whether the message should be logged.
	 */
	public function should_log( $level ) {
		$current_level = $this->get_current_log_level();
		$current_level_value = isset( $this->log_levels[ $current_level ] ) ? $this->log_levels[ $current_level ] : 1;
		$message_level_value = isset( $this->log_levels[ $level ] ) ? $this->log_levels[ $level ] : 1;

		// Log if message level is same or higher priority (lower number)
		return $message_level_value <= $current_level_value;
	}

	/**
	 * Get the current log level from plugin settings.
	 * 
	 * @since 2.2.0
	 * @return string Current log level.
	 */
	private function get_current_log_level() {
		// Use cached value if available
		if ( null !== $this->current_log_level_cache ) {
			return $this->current_log_level_cache;
		}

		// Get settings handler
		$settings_handler = $this->get_settings_handler();
		if ( null === $settings_handler ) {
			// Fallback: check direct option access
			$options = get_option( 'ai_faq_gen_options', array() );
			$this->current_log_level_cache = ! empty( $options['log_level'] ) ? $options['log_level'] : 'error';
		} else {
			// Use settings handler for consistent access
			$settings = $settings_handler->get_comprehensive_settings();
			$this->current_log_level_cache = ! empty( $settings['advanced']['log_level'] ) ? $settings['advanced']['log_level'] : 'error';
		}

		return $this->current_log_level_cache;
	}

	/**
	 * Build the final log entry with proper formatting.
	 * 
	 * Maintains existing message format patterns while adding structured
	 * logging capabilities.
	 * 
	 * @since 2.2.0
	 * @param string $level   Log level.
	 * @param string $message Log message.
	 * @param array  $context Context data.
	 * @return string Formatted log entry.
	 */
	private function build_log_entry( $level, $message, $context ) {
		// Check if message already has a plugin prefix
		$has_prefix = $this->message_has_prefix( $message );
		
		// Build base message with level indicator
		$level_indicator = strtoupper( $level );
		
		if ( $has_prefix ) {
			// Message already has prefix, just add level if not present
			$log_message = $message;
			if ( false === strpos( $message, $level_indicator ) ) {
				// Insert level indicator after existing prefix
				$log_message = $this->insert_level_in_prefixed_message( $message, $level_indicator );
			}
		} else {
			// Add standard plugin prefix with level
			$log_message = sprintf( '%s [%s] %s', $this->plugin_prefix, $level_indicator, $message );
		}

		// Add context data if present
		if ( ! empty( $context ) ) {
			$context_json = wp_json_encode( $context );
			if ( false !== $context_json ) {
				$log_message .= ' | Context: ' . $context_json;
			}
		}

		return $log_message;
	}

	/**
	 * Check if message already has a plugin-related prefix.
	 * 
	 * @since 2.2.0
	 * @param string $message Message to check.
	 * @return bool Whether message has a prefix.
	 */
	private function message_has_prefix( $message ) {
		$prefixes = array(
			'[365i AI FAQ]',
			'AI FAQ Generator:',
			'AI FAQ Rate Limiting:',
		);

		foreach ( $prefixes as $prefix ) {
			if ( 0 === strpos( $message, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Insert level indicator into an existing prefixed message.
	 * 
	 * @since 2.2.0
	 * @param string $message        Original message with prefix.
	 * @param string $level_indicator Level indicator to insert.
	 * @return string Modified message.
	 */
	private function insert_level_in_prefixed_message( $message, $level_indicator ) {
		// Find the end of the prefix (first ']' or ':')
		$prefix_end = max( strpos( $message, ']' ), strpos( $message, ':' ) );
		
		if ( false !== $prefix_end ) {
			// Insert level indicator after the prefix
			$prefix = substr( $message, 0, $prefix_end + 1 );
			$remainder = substr( $message, $prefix_end + 1 );
			return $prefix . ' [' . $level_indicator . ']' . $remainder;
		}

		// Fallback: prepend level indicator
		return '[' . $level_indicator . '] ' . $message;
	}

	/**
	 * Get the settings handler instance.
	 * 
	 * @since 2.2.0
	 * @return AI_FAQ_Settings_Handler|null Settings handler or null if not available.
	 */
	private function get_settings_handler() {
		if ( null === $this->settings_handler && class_exists( 'AI_FAQ_Settings_Handler' ) ) {
			$this->settings_handler = new AI_FAQ_Settings_Handler();
		}
		return $this->settings_handler;
	}

	/**
	 * Validate and sanitize log level.
	 * 
	 * @since 2.2.0
	 * @param string $level Log level to validate.
	 * @return string|false Valid log level or false if invalid.
	 */
	private function validate_log_level( $level ) {
		if ( ! is_string( $level ) ) {
			return false;
		}

		$level = strtolower( sanitize_text_field( $level ) );
		
		if ( ! isset( $this->log_levels[ $level ] ) ) {
			return false;
		}

		return $level;
	}

	/**
	 * Sanitize context data for safe logging.
	 * 
	 * @since 2.2.0
	 * @param array $context Context data to sanitize.
	 * @return array Sanitized context data.
	 */
	private function sanitize_context( $context ) {
		if ( ! is_array( $context ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $context as $key => $value ) {
			$sanitized_key = sanitize_key( $key );
			
			if ( is_string( $value ) ) {
				$sanitized[ $sanitized_key ] = sanitize_text_field( $value );
			} elseif ( is_numeric( $value ) ) {
				$sanitized[ $sanitized_key ] = $value;
			} elseif ( is_bool( $value ) ) {
				$sanitized[ $sanitized_key ] = $value;
			} elseif ( is_array( $value ) ) {
				// Recursive sanitization for nested arrays (limited depth)
				$sanitized[ $sanitized_key ] = $this->sanitize_context( $value );
			} else {
				// Convert other types to string and sanitize
				$sanitized[ $sanitized_key ] = sanitize_text_field( (string) $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Clear cached settings to force refresh on next access.
	 * 
	 * This method should be called when logging settings are updated
	 * to ensure the logger picks up the new configuration.
	 * 
	 * @since 2.2.0
	 * @return void
	 */
	public function clear_settings_cache() {
		$this->logging_enabled_cache = null;
		$this->current_log_level_cache = null;
	}

	/**
	 * Get logging statistics for admin dashboard.
	 * 
	 * @since 2.2.0
	 * @return array Logging statistics.
	 */
	public function get_logging_stats() {
		return array(
			'logging_enabled' => $this->is_logging_enabled(),
			'current_log_level' => $this->get_current_log_level(),
			'available_levels' => array_keys( $this->log_levels ),
			'settings_cached' => null !== $this->logging_enabled_cache && null !== $this->current_log_level_cache,
		);
	}
}