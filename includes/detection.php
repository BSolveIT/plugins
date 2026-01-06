<?php
/**
 * Environment detection logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize an environment value to DEV, STAGING, or LIVE.
 *
 * @param string $value Raw environment value.
 * @return string
 */
function i365ei_normalize_environment( $value ) {
	$value = strtolower( sanitize_key( $value ) );

	switch ( $value ) {
		case 'development':
		case 'dev':
			return 'DEV';
		case 'staging':
		case 'stage':
			return 'STAGING';
		case 'production':
		case 'prod':
		case 'live':
			return 'LIVE';
		default:
			return '';
	}
}

/**
 * Detect environment from constants.
 *
 * Checks WordPress core constants and popular hosting provider constants.
 *
 * @return string
 */
function i365ei_detect_environment_from_constants() {
	// WordPress core constant (5.5+).
	if ( defined( 'WP_ENVIRONMENT_TYPE' ) ) {
		$normalized = i365ei_normalize_environment( WP_ENVIRONMENT_TYPE );
		if ( '' !== $normalized ) {
			return $normalized;
		}
	}

	// Legacy Bedrock constant.
	if ( defined( 'WP_ENV' ) ) {
		$normalized = i365ei_normalize_environment( WP_ENV );
		if ( '' !== $normalized ) {
			return $normalized;
		}
	}

	// WP Engine.
	if ( defined( 'WPE_ENVIRONMENT' ) ) {
		$normalized = i365ei_normalize_environment( WPE_ENVIRONMENT );
		if ( '' !== $normalized ) {
			return $normalized;
		}
	}

	// Pantheon.
	if ( defined( 'PANTHEON_ENVIRONMENT' ) ) {
		$normalized = i365ei_normalize_environment( PANTHEON_ENVIRONMENT );
		if ( '' !== $normalized ) {
			return $normalized;
		}
	}

	// Kinsta (uses WP_ENVIRONMENT_TYPE but check for fallback).
	if ( isset( $_ENV['KINSTA_ENV_TYPE'] ) ) {
		$normalized = i365ei_normalize_environment( sanitize_text_field( wp_unslash( $_ENV['KINSTA_ENV_TYPE'] ) ) );
		if ( '' !== $normalized ) {
			return $normalized;
		}
	}

	// Flywheel.
	if ( defined( 'FLYWHEEL_CONFIG_DIR' ) ) {
		if ( strpos( FLYWHEEL_CONFIG_DIR, 'local' ) !== false ) {
			return 'DEV';
		}
	}

	return '';
}

/**
 * Detect environment using the subdomain label.
 *
 * @return string
 */
function i365ei_detect_environment_from_subdomain() {
	$host = wp_parse_url( get_home_url(), PHP_URL_HOST );

	if ( empty( $host ) ) {
		return '';
	}

	$host  = strtolower( $host );
	$parts = explode( '.', $host );

	if ( count( $parts ) < 3 ) {
		return 'LIVE';
	}

	$subdomain = $parts[0];

	if ( '' === $subdomain || 'www' === $subdomain ) {
		return 'LIVE';
	}

	if ( false !== strpos( $subdomain, 'development' ) || false !== strpos( $subdomain, 'dev' ) ) {
		return 'DEV';
	}

	if ( false !== strpos( $subdomain, 'staging' ) || false !== strpos( $subdomain, 'stage' ) || false !== strpos( $subdomain, 'test' ) || false !== strpos( $subdomain, 'qa' ) ) {
		return 'STAGING';
	}

	return 'LIVE';
}

/**
 * Get the current environment.
 *
 * @return string
 */
function i365ei_get_environment() {
	global $i365ei_environment_cache;

	if ( ! empty( $i365ei_environment_cache ) ) {
		return $i365ei_environment_cache;
	}

	$settings = i365ei_get_settings();

	if ( empty( $settings['auto_detect'] ) ) {
		$i365ei_environment_cache = i365ei_normalize_environment( $settings['manual_environment'] );
		if ( '' === $i365ei_environment_cache ) {
			$i365ei_environment_cache = 'LIVE';
		}
		return $i365ei_environment_cache;
	}

	$i365ei_environment_cache = i365ei_detect_environment_from_constants();

	if ( '' === $i365ei_environment_cache ) {
		$i365ei_environment_cache = i365ei_detect_environment_from_subdomain();
	}

	if ( '' === $i365ei_environment_cache ) {
		$i365ei_environment_cache = 'LIVE';
	}

	return $i365ei_environment_cache;
}

/**
 * Get the detection source for the current environment.
 *
 * Returns a human-readable description of how the environment was detected.
 *
 * @return string
 */
function i365ei_get_detection_source() {
	$settings = i365ei_get_settings();

	if ( empty( $settings['auto_detect'] ) ) {
		return __( 'Manual selection', '365i-environment-indicator' );
	}

	// Check constants in order.
	if ( defined( 'WP_ENVIRONMENT_TYPE' ) && '' !== i365ei_normalize_environment( WP_ENVIRONMENT_TYPE ) ) {
		return 'WP_ENVIRONMENT_TYPE';
	}

	if ( defined( 'WP_ENV' ) && '' !== i365ei_normalize_environment( WP_ENV ) ) {
		return 'WP_ENV';
	}

	if ( defined( 'WPE_ENVIRONMENT' ) && '' !== i365ei_normalize_environment( WPE_ENVIRONMENT ) ) {
		return 'WPE_ENVIRONMENT';
	}

	if ( defined( 'PANTHEON_ENVIRONMENT' ) && '' !== i365ei_normalize_environment( PANTHEON_ENVIRONMENT ) ) {
		return 'PANTHEON_ENVIRONMENT';
	}

	if ( isset( $_ENV['KINSTA_ENV_TYPE'] ) && '' !== i365ei_normalize_environment( sanitize_text_field( wp_unslash( $_ENV['KINSTA_ENV_TYPE'] ) ) ) ) {
		return 'KINSTA_ENV_TYPE';
	}

	if ( defined( 'FLYWHEEL_CONFIG_DIR' ) && strpos( FLYWHEEL_CONFIG_DIR, 'local' ) !== false ) {
		return 'FLYWHEEL_CONFIG_DIR';
	}

	// Must be subdomain detection.
	$host = wp_parse_url( get_home_url(), PHP_URL_HOST );
	if ( ! empty( $host ) ) {
		/* translators: %s: The subdomain hostname used for environment detection */
		return sprintf( __( 'Subdomain: %s', '365i-environment-indicator' ), esc_html( $host ) );
	}

	return __( 'Default (no detection)', '365i-environment-indicator' );
}
