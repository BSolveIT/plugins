<?php
/**
 * Centralized Logging Wrapper Functions for 365i AI FAQ Generator.
 * 
 * This file provides convenient wrapper functions for the centralized
 * logging system, making it easy to replace direct error_log() calls
 * throughout the plugin while maintaining existing message formats.
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
 * Log an error message using the centralized logger.
 * 
 * This function provides a simple interface to log error-level messages
 * through the centralized logging system. Messages will only be logged
 * if logging is enabled and the current log level allows error messages.
 * 
 * @since 2.2.0
 * @param string $message Log message to record.
 * @param array  $context Optional context data for structured logging.
 * @return bool Whether the message was logged successfully.
 */
function ai_faq_log_error( $message, $context = array() ) {
	$logger = AI_FAQ_365i_Logger::get_instance();
	return $logger->error( $message, $context );
}

/**
 * Log a warning message using the centralized logger.
 * 
 * This function provides a simple interface to log warning-level messages
 * through the centralized logging system. Messages will only be logged
 * if logging is enabled and the current log level allows warning messages.
 * 
 * @since 2.2.0
 * @param string $message Log message to record.
 * @param array  $context Optional context data for structured logging.
 * @return bool Whether the message was logged successfully.
 */
function ai_faq_log_warning( $message, $context = array() ) {
	$logger = AI_FAQ_365i_Logger::get_instance();
	return $logger->warning( $message, $context );
}

/**
 * Log an info message using the centralized logger.
 * 
 * This function provides a simple interface to log info-level messages
 * through the centralized logging system. Messages will only be logged
 * if logging is enabled and the current log level allows info messages.
 * 
 * @since 2.2.0
 * @param string $message Log message to record.
 * @param array  $context Optional context data for structured logging.
 * @return bool Whether the message was logged successfully.
 */
function ai_faq_log_info( $message, $context = array() ) {
	$logger = AI_FAQ_365i_Logger::get_instance();
	return $logger->info( $message, $context );
}

/**
 * Log a debug message using the centralized logger.
 * 
 * This function provides a simple interface to log debug-level messages
 * through the centralized logging system. Messages will only be logged
 * if logging is enabled and the current log level allows debug messages.
 * 
 * @since 2.2.0
 * @param string $message Log message to record.
 * @param array  $context Optional context data for structured logging.
 * @return bool Whether the message was logged successfully.
 */
function ai_faq_log_debug( $message, $context = array() ) {
	$logger = AI_FAQ_365i_Logger::get_instance();
	return $logger->debug( $message, $context );
}

/**
 * Log a message with custom level using the centralized logger.
 * 
 * This function provides a flexible interface to log messages at any
 * supported level through the centralized logging system.
 * 
 * @since 2.2.0
 * @param string $level   Log level (error, warning, info, debug).
 * @param string $message Log message to record.
 * @param array  $context Optional context data for structured logging.
 * @return bool Whether the message was logged successfully.
 */
function ai_faq_log( $level, $message, $context = array() ) {
	$logger = AI_FAQ_365i_Logger::get_instance();
	return $logger->log( $level, $message, $context );
}

/**
 * Conditional logging function for WP_DEBUG compatibility.
 * 
 * This function maintains backward compatibility with existing WP_DEBUG
 * conditional logging patterns found throughout the plugin. It will log
 * the message only if WP_DEBUG is enabled OR if the centralized logger
 * is configured to log debug messages.
 * 
 * @since 2.2.0
 * @param string $message Log message to record.
 * @param array  $context Optional context data for structured logging.
 * @return bool Whether the message was logged successfully.
 */
function ai_faq_log_if_debug( $message, $context = array() ) {
	// Check both WP_DEBUG and centralized logger debug level
	$wp_debug_enabled = defined( 'WP_DEBUG' ) && WP_DEBUG;
	$logger = AI_FAQ_365i_Logger::get_instance();
	$centralized_debug_enabled = $logger->is_logging_enabled() && $logger->should_log( 'debug' );
	
	// Log if either condition is met
	if ( $wp_debug_enabled || $centralized_debug_enabled ) {
		return $logger->debug( $message, $context );
	}
	
	return false;
}

/**
 * Legacy compatibility function for existing error_log patterns.
 * 
 * This function provides a drop-in replacement for direct error_log() calls
 * with an optional level parameter. It's designed to ease the migration
 * process while providing centralized logging capabilities.
 * 
 * @since 2.2.0
 * @param string $message Log message to record.
 * @param string $level   Optional log level (default: 'error').
 * @param array  $context Optional context data for structured logging.
 * @return bool Whether the message was logged successfully.
 */
function ai_faq_legacy_log( $message, $level = 'error', $context = array() ) {
	$logger = AI_FAQ_365i_Logger::get_instance();
	return $logger->log( $level, $message, $context );
}

/**
 * Enhanced logging function with automatic context detection.
 * 
 * This function automatically adds useful context information such as
 * the calling function, file, and line number to help with debugging.
 * 
 * @since 2.2.0
 * @param string $level   Log level (error, warning, info, debug).
 * @param string $message Log message to record.
 * @param array  $context Optional additional context data.
 * @return bool Whether the message was logged successfully.
 */
function ai_faq_log_with_context( $level, $message, $context = array() ) {
	// Get backtrace information for automatic context
	$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
	$caller = isset( $backtrace[1] ) ? $backtrace[1] : array();
	
	// Add automatic context information
	$auto_context = array();
	if ( isset( $caller['function'] ) ) {
		$auto_context['function'] = $caller['function'];
	}
	if ( isset( $caller['class'] ) ) {
		$auto_context['class'] = $caller['class'];
	}
	if ( isset( $caller['file'] ) ) {
		$auto_context['file'] = basename( $caller['file'] );
	}
	if ( isset( $caller['line'] ) ) {
		$auto_context['line'] = $caller['line'];
	}
	
	// Merge automatic context with provided context
	$full_context = array_merge( $auto_context, $context );
	
	$logger = AI_FAQ_365i_Logger::get_instance();
	return $logger->log( $level, $message, $full_context );
}

/**
 * Specialized logging function for API communication.
 * 
 * This function is designed specifically for logging API-related messages
 * with common context data patterns found in the plugin's API communications.
 * 
 * @since 2.2.0
 * @param string $level        Log level (error, warning, info, debug).
 * @param string $message      Log message to record.
 * @param string $api_endpoint Optional API endpoint being accessed.
 * @param int    $response_code Optional HTTP response code.
 * @param array  $context      Optional additional context data.
 * @return bool Whether the message was logged successfully.
 */
function ai_faq_log_api( $level, $message, $api_endpoint = '', $response_code = 0, $context = array() ) {
	// Add API-specific context
	$api_context = array();
	if ( ! empty( $api_endpoint ) ) {
		$api_context['api_endpoint'] = sanitize_text_field( $api_endpoint );
	}
	if ( $response_code > 0 ) {
		$api_context['response_code'] = intval( $response_code );
	}
	
	// Merge API context with provided context
	$full_context = array_merge( $api_context, $context );
	
	$logger = AI_FAQ_365i_Logger::get_instance();
	return $logger->log( $level, $message, $full_context );
}

/**
 * Specialized logging function for worker operations.
 * 
 * This function is designed specifically for logging worker-related messages
 * with common context data patterns found in the plugin's worker operations.
 * 
 * @since 2.2.0
 * @param string $level       Log level (error, warning, info, debug).
 * @param string $message     Log message to record.
 * @param string $worker_name Optional worker name.
 * @param string $operation   Optional operation being performed.
 * @param array  $context     Optional additional context data.
 * @return bool Whether the message was logged successfully.
 */
function ai_faq_log_worker( $level, $message, $worker_name = '', $operation = '', $context = array() ) {
	// Add worker-specific context
	$worker_context = array();
	if ( ! empty( $worker_name ) ) {
		$worker_context['worker'] = sanitize_text_field( $worker_name );
	}
	if ( ! empty( $operation ) ) {
		$worker_context['operation'] = sanitize_text_field( $operation );
	}
	
	// Merge worker context with provided context
	$full_context = array_merge( $worker_context, $context );
	
	$logger = AI_FAQ_365i_Logger::get_instance();
	return $logger->log( $level, $message, $full_context );
}

/**
 * Get logging system status and statistics.
 * 
 * This function provides access to logging system status information
 * that can be used for debugging or admin dashboard display.
 * 
 * @since 2.2.0
 * @return array Logging system status and statistics.
 */
function ai_faq_get_logging_status() {
	$logger = AI_FAQ_365i_Logger::get_instance();
	return $logger->get_logging_stats();
}

/**
 * Clear the logging system's settings cache.
 * 
 * This function should be called whenever logging-related settings
 * are updated to ensure the logger picks up the new configuration.
 * 
 * @since 2.2.0
 * @return void
 */
function ai_faq_clear_logging_cache() {
	$logger = AI_FAQ_365i_Logger::get_instance();
	$logger->clear_settings_cache();
}

/**
 * Check if centralized logging is available and enabled.
 * 
 * This function can be used by other parts of the plugin to determine
 * whether centralized logging is available and properly configured.
 * 
 * @since 2.2.0
 * @return bool Whether centralized logging is available and enabled.
 */
function ai_faq_is_logging_available() {
	if ( ! class_exists( 'AI_FAQ_365i_Logger' ) ) {
		return false;
	}
	
	$logger = AI_FAQ_365i_Logger::get_instance();
	return $logger->is_logging_enabled();
}