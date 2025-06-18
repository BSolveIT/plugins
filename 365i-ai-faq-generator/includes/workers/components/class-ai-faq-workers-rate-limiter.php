<?php
/**
 * Workers Rate Limiter class for 365i AI FAQ Generator.
 * 
 * This class handles rate limiting for worker API requests, including
 * counter management, caching, and usage statistics.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Workers
 * @since 2.1.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Workers Rate Limiter class.
 * 
 * Manages rate limiting and caching for worker API requests.
 * 
 * @since 2.1.0
 */
class AI_FAQ_Workers_Rate_Limiter {

	/**
	 * Rate limiting cache key prefix.
	 * 
	 * @since 2.1.0
	 * @var string
	 */
	private $rate_limit_prefix = 'ai_faq_rate_limit_';

	/**
	 * Response cache key prefix.
	 * 
	 * @since 2.1.0
	 * @var string
	 */
	private $cache_prefix = 'ai_faq_response_';

	/**
	 * Available workers configuration.
	 * 
	 * @since 2.1.0
	 * @var array
	 */
	private $workers = array();

	/**
	 * Cache duration in seconds.
	 * 
	 * @since 2.1.0
	 * @var int
	 */
	private $cache_duration = 3600; // Default: 1 hour

	/**
	 * Whether caching is enabled.
	 * 
	 * @since 2.1.0
	 * @var bool
	 */
	private $cache_enabled = true;

	/**
	 * Constructor.
	 * 
	 * Initialize rate limiter with worker configuration.
	 * 
	 * @since 2.1.0
	 * @param array $workers Worker configuration.
	 */
	public function __construct( $workers ) {
		$this->workers = $workers;
		$this->load_cache_settings();
	}

	/**
	 * Initialize the rate limiter.
	 * 
	 * Set up hooks and filters.
	 * 
	 * @since 2.1.0
	 */
	public function init() {
		// Schedule cleanup task for expired cache items.
		if ( ! wp_next_scheduled( 'ai_faq_cleanup_cache' ) ) {
			wp_schedule_event( time(), 'daily', 'ai_faq_cleanup_cache' );
		}

		// Add cleanup hook.
		add_action( 'ai_faq_cleanup_cache', array( $this, 'cleanup_expired_cache' ) );
	}

	/**
	 * Load cache settings from options.
	 * 
	 * @since 2.1.0
	 */
	private function load_cache_settings() {
		$options = get_option( 'ai_faq_gen_options', array() );
		
		if ( isset( $options['enable_caching'] ) ) {
			$this->cache_enabled = (bool) $options['enable_caching'];
		}
		
		if ( isset( $options['cache_duration'] ) ) {
			$this->cache_duration = max( 60, intval( $options['cache_duration'] ) );
		}
	}

	/**
	 * Check rate limit for worker with IP-based tracking.
	 * 
	 * @since 2.1.0
	 * @param string $worker_name Worker name.
	 * @param string $ip_address Optional. IP address to check. Defaults to current user IP.
	 * @return bool True if within rate limit, false otherwise.
	 */
	public function check_rate_limit( $worker_name, $ip_address = null ) {
		// If worker doesn't exist, allow the request.
		if ( ! isset( $this->workers[ $worker_name ] ) ) {
			return true;
		}

		// If rate limit is not set, allow the request.
		if ( ! isset( $this->workers[ $worker_name ]['rate_limit'] ) ) {
			return true;
		}

		// Admin users bypass rate limiting.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$rate_limit = $this->workers[ $worker_name ]['rate_limit'];
		
		// Get client IP if not provided.
		if ( null === $ip_address ) {
			// We'll defer to the security component for getting the IP.
			// Here we'll use a simple fallback just to maintain functionality.
			$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
		}
		
		// Use IP-specific cache key for frontend users.
		$cache_key = $this->rate_limit_prefix . $worker_name . '_' . md5( $ip_address );
		$current_count = get_transient( $cache_key );
		
		return ( false === $current_count || $current_count < $rate_limit );
	}

	/**
	 * Update rate limit counter for worker with IP-based tracking.
	 * 
	 * @since 2.1.0
	 * @param string $worker_name Worker name.
	 * @param string $ip_address Optional. IP address to update. Defaults to current user IP.
	 */
	public function update_rate_limit( $worker_name, $ip_address = null ) {
		// Admin users don't contribute to rate limiting.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Get client IP if not provided.
		if ( null === $ip_address ) {
			// We'll defer to the security component for getting the IP.
			// Here we'll use a simple fallback just to maintain functionality.
			$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
		}
		
		// Use IP-specific cache key for frontend users.
		$cache_key = $this->rate_limit_prefix . $worker_name . '_' . md5( $ip_address );
		$current_count = get_transient( $cache_key );

		if ( false === $current_count ) {
			set_transient( $cache_key, 1, HOUR_IN_SECONDS );
		} else {
			set_transient( $cache_key, $current_count + 1, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Get current rate limit usage.
	 * 
	 * @since 2.1.0
	 * @param string $worker_name Worker name.
	 * @param string $ip_address Optional. IP address to check. Defaults to current user IP.
	 * @return array Rate limit usage information.
	 */
	public function get_rate_limit_usage( $worker_name, $ip_address = null ) {
		// If worker doesn't exist, return empty stats.
		if ( ! isset( $this->workers[ $worker_name ] ) ) {
			return array(
				'current' => 0,
				'limit' => 0,
				'remaining' => 0,
				'percentage' => 0,
			);
		}

		$rate_limit = isset( $this->workers[ $worker_name ]['rate_limit'] ) ? 
			intval( $this->workers[ $worker_name ]['rate_limit'] ) : 0;
		
		// Get client IP if not provided.
		if ( null === $ip_address ) {
			// We'll defer to the security component for getting the IP.
			// Here we'll use a simple fallback just to maintain functionality.
			$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
		}
		
		$cache_key = $this->rate_limit_prefix . $worker_name . '_' . md5( $ip_address );
		$current_count = get_transient( $cache_key );

		return array(
			'current' => $current_count ? intval( $current_count ) : 0,
			'limit' => $rate_limit,
			'remaining' => max( 0, $rate_limit - ( $current_count ? intval( $current_count ) : 0 ) ),
			'percentage' => $rate_limit > 0 ? round( ( ( $current_count ? intval( $current_count ) : 0 ) / $rate_limit ) * 100, 2 ) : 0,
		);
	}

	/**
	 * Reset rate limit counter.
	 * 
	 * @since 2.1.0
	 * @param string $worker_name Worker name.
	 * @param string $ip_address Optional. IP address to reset. If null, resets for all IPs.
	 * @return bool True if reset successfully, false otherwise.
	 */
	public function reset_rate_limit( $worker_name, $ip_address = null ) {
		if ( null !== $ip_address ) {
			// Reset for specific IP.
			$cache_key = $this->rate_limit_prefix . $worker_name . '_' . md5( $ip_address );
			return delete_transient( $cache_key );
		} else {
			// Reset for all IPs.
			global $wpdb;
			$transient_pattern = $wpdb->esc_like( '_transient_' . $this->rate_limit_prefix . $worker_name ) . '%';
			
			return $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					$transient_pattern
				)
			);
		}
	}

	/**
	 * Check if caching is enabled.
	 * 
	 * @since 2.1.0
	 * @return bool True if caching is enabled, false otherwise.
	 */
	public function is_cache_enabled() {
		return $this->cache_enabled;
	}

	/**
	 * Get cached response.
	 * 
	 * @since 2.1.0
	 * @param string $worker_name Worker name.
	 * @param array  $data Request data.
	 * @return mixed|false Cached response or false if not cached.
	 */
	public function get_cached_response( $worker_name, $data ) {
		if ( ! $this->cache_enabled ) {
			return false;
		}

		$cache_key = $this->cache_prefix . $worker_name . '_' . md5( wp_json_encode( $data ) );
		return get_transient( $cache_key );
	}

	/**
	 * Cache response.
	 * 
	 * @since 2.1.0
	 * @param string $worker_name Worker name.
	 * @param array  $data Request data.
	 * @param mixed  $response Response data.
	 * @return bool True if cached successfully, false otherwise.
	 */
	public function cache_response( $worker_name, $data, $response ) {
		if ( ! $this->cache_enabled ) {
			return false;
		}

		$cache_key = $this->cache_prefix . $worker_name . '_' . md5( wp_json_encode( $data ) );
		return set_transient( $cache_key, $response, $this->cache_duration );
	}

	/**
	 * Cleanup expired cache items.
	 * 
	 * @since 2.1.0
	 */
	public function cleanup_expired_cache() {
		// WordPress automatically removes expired transients,
		// but we can force a cleanup here if needed.
		global $wpdb;
		
		// Delete expired transients.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_timeout_%' 
			AND option_value < " . time()
		);
		
		// Delete the corresponding transient values.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_%' 
			AND option_name NOT LIKE '_transient_timeout_%' 
			AND NOT EXISTS (
				SELECT * FROM {$wpdb->options} 
				WHERE option_name = CONCAT('_transient_timeout_', SUBSTRING(option_name, 12))
			)"
		);
	}

	/**
	 * Set cache settings.
	 * 
	 * @since 2.1.0
	 * @param bool $enabled Whether caching is enabled.
	 * @param int  $duration Cache duration in seconds.
	 */
	public function set_cache_settings( $enabled, $duration ) {
		$this->cache_enabled = (bool) $enabled;
		$this->cache_duration = max( 60, intval( $duration ) );
		
		// Update settings in database.
		$options = get_option( 'ai_faq_gen_options', array() );
		$options['enable_caching'] = $this->cache_enabled;
		$options['cache_duration'] = $this->cache_duration;
		
		update_option( 'ai_faq_gen_options', $options );
	}
}