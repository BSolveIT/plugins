<?php
/**
 * Workers class for 365i AI FAQ Generator.
 * 
 * This class implements a facade pattern to provide backward compatibility
 * while delegating to specialized component classes.
 * 
 * @package AI_FAQ_Generator
 * @since 1.0.0
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Workers class.
 * 
 * Coordinates worker operations and provides a facade for the component-based architecture.
 * 
 * @since 1.0.0
 */
class AI_FAQ_Workers {

	/**
	 * Workers Manager instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Workers_Manager
	 */
	private $manager;

	/**
	 * Rate Limiter instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Workers_Rate_Limiter
	 */
	private $rate_limiter;

	/**
	 * Security instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Workers_Security
	 */
	private $security;

	/**
	 * Analytics instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Workers_Analytics
	 */
	private $analytics;

	/**
	 * Request Handler instance.
	 *
	 * @since 2.1.0
	 * @var AI_FAQ_Workers_Request_Handler
	 */
	private $request_handler;

	/**
	 * Constructor.
	 *
	 * Initialize the worker components with enhanced error handling.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		try {
			// Load component classes.
			$this->load_dependencies();
			
			// Get worker configuration from options with fallbacks
			$options = get_option( 'ai_faq_gen_options', array() );
			
			// Create a default config if the option doesn't exist or is malformed
			if ( empty( $options ) || !isset( $options['workers'] ) || !is_array( $options['workers'] ) ) {
				$workers_config = $this->get_default_workers_config();
			} else {
				$workers_config = $options['workers'];
			}
			
			// Initialize components with try/catch for each one
			try {
				$this->security = new AI_FAQ_Workers_Security();
			} catch ( Exception $e ) {
				$this->security = null;
				error_log( 'AI FAQ Generator: Error initializing Security component: ' . $e->getMessage() );
			}
			
			try {
				$this->rate_limiter = new AI_FAQ_Workers_Rate_Limiter( $workers_config );
			} catch ( Exception $e ) {
				$this->rate_limiter = null;
				error_log( 'AI FAQ Generator: Error initializing Rate Limiter component: ' . $e->getMessage() );
			}
			
			try {
				$this->analytics = new AI_FAQ_Workers_Analytics();
			} catch ( Exception $e ) {
				$this->analytics = null;
				error_log( 'AI FAQ Generator: Error initializing Analytics component: ' . $e->getMessage() );
			}
			
			// Only create manager if required components exist
			if ( $this->rate_limiter && $this->security && $this->analytics ) {
				try {
					$this->manager = new AI_FAQ_Workers_Manager(
						$this->rate_limiter,
						$this->security,
						$this->analytics
					);
				} catch ( Exception $e ) {
					$this->manager = null;
					error_log( 'AI FAQ Generator: Error initializing Workers Manager: ' . $e->getMessage() );
				}
			} else {
				$this->manager = null;
			}
			
			// Only create request handler if all components exist
			if ( $this->manager && $this->rate_limiter && $this->security && $this->analytics ) {
				try {
					$this->request_handler = new AI_FAQ_Workers_Request_Handler(
						$this->manager,
						$this->rate_limiter,
						$this->security,
						$this->analytics
					);
				} catch ( Exception $e ) {
					$this->request_handler = null;
					error_log( 'AI FAQ Generator: Error initializing Request Handler: ' . $e->getMessage() );
				}
			} else {
				$this->request_handler = null;
			}
		} catch ( Exception $e ) {
			// Log any uncaught exceptions
			error_log( 'AI FAQ Generator: Fatal error in Workers initialization: ' . $e->getMessage() );
		}
	}

	/**
	 * Initialize the Workers system.
	 *
	 * Sets up hooks and initializes components in the correct order.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Skip initialization if any critical component is missing
		if ( ! $this->security || ! $this->rate_limiter || ! $this->analytics || ! $this->manager ) {
			error_log( 'AI FAQ Generator: Skipping worker initialization due to missing components' );
			return;
		}
		
		try {
			// Initialize components in the right order
			// First, initialize the security component
			$this->security->init();
			
			// Next, initialize the rate limiter that depends on security
			$this->rate_limiter->init();
			
			// Then analytics
			$this->analytics->init();
			
			// Initialize the manager with already initialized components
			// The manager will not re-initialize components passed from here
			$this->manager->init();
			
			// Finally, initialize the request handler
			if ( $this->request_handler ) {
				$this->request_handler->init();
			}
		} catch ( Exception $e ) {
			error_log( 'AI FAQ Generator: Error during worker initialization: ' . $e->getMessage() );
		}
	}
	
	/**
	 * Get default workers configuration.
	 *
	 * Provides a fallback configuration when no configuration exists.
	 *
	 * @since 2.1.0
	 * @return array Default workers configuration.
	 */
	private function get_default_workers_config() {
		return array(
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
			'faq_extractor' => array(
				'url' => 'https://faq-proxy-fetch.winter-cake-bf57.workers.dev',
				'enabled' => true,
				'rate_limit' => 100,
			),
			'topic_generator' => array(
				'url' => 'https://url-to-faq-generator-worker.winter-cake-bf57.workers.dev',
				'enabled' => true,
				'rate_limit' => 10,
			),
		);
	}

	/**
	 * Load required dependencies.
	 * 
	 * @since 2.1.0
	 */
	private function load_dependencies() {
		// Load Manager class.
		if ( ! class_exists( 'AI_FAQ_Workers_Manager' ) ) {
			require_once AI_FAQ_GEN_DIR . 'includes/workers/class-ai-faq-workers-manager.php';
		}

		// Load Rate Limiter class.
		if ( ! class_exists( 'AI_FAQ_Workers_Rate_Limiter' ) ) {
			require_once AI_FAQ_GEN_DIR . 'includes/workers/components/class-ai-faq-workers-rate-limiter.php';
		}

		// Load Security class.
		if ( ! class_exists( 'AI_FAQ_Workers_Security' ) ) {
			require_once AI_FAQ_GEN_DIR . 'includes/workers/components/class-ai-faq-workers-security.php';
		}

		// Load Analytics class.
		if ( ! class_exists( 'AI_FAQ_Workers_Analytics' ) ) {
			require_once AI_FAQ_GEN_DIR . 'includes/workers/components/class-ai-faq-workers-analytics.php';
		}

		// Load Request Handler class.
		if ( ! class_exists( 'AI_FAQ_Workers_Request_Handler' ) ) {
			require_once AI_FAQ_GEN_DIR . 'includes/workers/components/class-ai-faq-workers-request-handler.php';
		}
	}

	/**
	 * Generate questions based on a topic.
	 * 
	 * @since 1.0.0
	 * @param string $topic The topic to generate questions for.
	 * @param int    $count The number of questions to generate.
	 * @return array|WP_Error An array of questions or a WP_Error object on failure.
	 */
	public function generate_questions( $topic, $count = 12 ) {
		return $this->manager->generate_questions( $topic, $count );
	}

	/**
	 * Generate answers for a list of questions.
	 * 
	 * @since 1.0.0
	 * @param array $questions The list of questions to generate answers for.
	 * @return array|WP_Error An array of question-answer pairs or a WP_Error object on failure.
	 */
	public function generate_answers( $questions ) {
		return $this->manager->generate_answers( $questions );
	}

	/**
	 * Enhance an existing FAQ with additional information.
	 * 
	 * @since 1.0.0
	 * @param array $faq_data The FAQ data to enhance.
	 * @return array|WP_Error Enhanced FAQ data or a WP_Error object on failure.
	 */
	public function enhance_faq( $faq_data ) {
		return $this->manager->enhance_faq( $faq_data );
	}

	/**
	 * Analyze FAQ content for SEO.
	 * 
	 * @since 1.1.0
	 * @param array $faq_data The FAQ data to analyze.
	 * @return array|WP_Error SEO analysis results or a WP_Error object on failure.
	 */
	public function analyze_seo( $faq_data ) {
		return $this->manager->analyze_seo( $faq_data );
	}

	/**
	 * Extract FAQ content from a URL.
	 * 
	 * @since 1.2.0
	 * @param string $url The URL to extract FAQ content from.
	 * @return array|WP_Error Extracted FAQ data or a WP_Error object on failure.
	 */
	public function extract_faq( $url ) {
		return $this->manager->extract_faq( $url );
	}

	/**
	 * Generate topic suggestions from input text.
	 * 
	 * @since 1.3.0
	 * @param string $input The input text to generate topics from.
	 * @return array|WP_Error Topic suggestions or a WP_Error object on failure.
	 */
	public function generate_topics( $input ) {
		return $this->manager->generate_topics( $input );
	}

	/**
	 * Make a request to a worker.
	 * 
	 * @since 1.0.0
	 * @param string $worker_name The name of the worker to request.
	 * @param array  $data        The data to send to the worker.
	 * @return array|WP_Error The response data or a WP_Error object on failure.
	 */
	public function make_worker_request( $worker_name, $data ) {
		return $this->manager->make_worker_request( $worker_name, $data );
	}

	/**
	 * Check if an IP address is blocked.
	 * 
	 * @since 1.0.0
	 * @param string $ip_address The IP address to check.
	 * @return bool Whether the IP address is blocked.
	 */
	public function is_ip_blocked( $ip_address = '' ) {
		return $this->security->is_ip_blocked( $ip_address );
	}

	/**
	 * Block an IP address.
	 * 
	 * @since 1.0.0
	 * @param string $ip_address    The IP address to block.
	 * @param string $reason        The reason for blocking.
	 * @param int    $duration_hours The duration to block for, in hours.
	 * @return array Result of the blocking operation.
	 */
	public function block_ip( $ip_address, $reason = '', $duration_hours = 24 ) {
		return $this->security->block_ip( $ip_address, $reason, $duration_hours );
	}

	/**
	 * Unblock an IP address.
	 * 
	 * @since 1.0.0
	 * @param string $ip_address The IP address to unblock.
	 * @return array Result of the unblocking operation.
	 */
	public function unblock_ip( $ip_address ) {
		return $this->security->unblock_ip( $ip_address );
	}

	/**
	 * Get a list of blocked IP addresses.
	 * 
	 * @since 1.0.0
	 * @return array The list of blocked IP addresses.
	 */
	public function get_blocked_ips() {
		return $this->security->get_blocked_ips();
	}

	/**
	 * Check if a user has reached their rate limit.
	 * 
	 * @since 1.0.0
	 * @param string $worker_name The name of the worker to check.
	 * @param string $ip_address  The IP address to check.
	 * @return bool Whether the rate limit has been reached.
	 */
	public function is_rate_limited( $worker_name, $ip_address = '' ) {
		return $this->rate_limiter->is_rate_limited( $worker_name, $ip_address );
	}

	/**
	 * Track a worker request for rate limiting.
	 * 
	 * @since 1.0.0
	 * @param string $worker_name The name of the worker that was requested.
	 * @param string $ip_address  The IP address that made the request.
	 * @return void
	 */
	public function track_request( $worker_name, $ip_address = '' ) {
		$this->rate_limiter->track_request( $worker_name, $ip_address );
	}

	/**
	 * Reset rate limit for a worker.
	 * 
	 * @since 1.0.0
	 * @param string $worker_name The name of the worker to reset.
	 * @return bool Whether the reset was successful.
	 */
	public function reset_rate_limit( $worker_name ) {
		return $this->rate_limiter->reset_rate_limit( $worker_name );
	}

	/**
	 * Get usage statistics for workers.
	 * 
	 * @since 1.0.0
	 * @param int $days The number of days to get statistics for.
	 * @return array The usage statistics.
	 */
	public function get_usage_stats( $days = 30 ) {
		return $this->analytics->get_usage_stats( $days );
	}

	/**
	 * Get worker status information.
	 * 
	 * @since 1.1.0
	 * @return array Worker status information.
	 */
	public function get_worker_status() {
		return $this->manager->get_worker_status();
	}

	/**
	 * Cache worker response.
	 * 
	 * @since 1.0.0
	 * @param string $cache_key The cache key.
	 * @param mixed  $data      The data to cache.
	 * @param int    $expiration The cache expiration in seconds.
	 * @return bool Whether the data was cached successfully.
	 */
	public function cache_response( $cache_key, $data, $expiration = 3600 ) {
		return $this->rate_limiter->cache_response( $cache_key, $data, $expiration );
	}

	/**
	 * Get cached worker response.
	 * 
	 * @since 1.0.0
	 * @param string $cache_key The cache key.
	 * @return mixed|false The cached data or false if not found.
	 */
	public function get_cached_response( $cache_key ) {
		return $this->rate_limiter->get_cached_response( $cache_key );
	}

	/**
	 * Record a usage event.
	 * 
	 * @since 1.0.0
	 * @param string $worker_name The name of the worker used.
	 * @param string $event_type  The type of event.
	 * @param array  $metadata    Additional metadata about the event.
	 * @return bool Whether the event was recorded successfully.
	 */
	public function record_usage( $worker_name, $event_type, $metadata = array() ) {
		return $this->analytics->record_usage( $worker_name, $event_type, $metadata );
	}

	/**
	 * Get analytics data.
	 * 
	 * @since 1.1.0
	 * @param int $period_days The number of days to get data for.
	 * @return array The analytics data.
	 */
	public function get_analytics_data( $period_days = 30 ) {
		return $this->analytics->get_analytics_data( $period_days );
	}

	/**
	 * Get violations data.
	 * 
	 * @since 1.1.0
	 * @param int $period_hours The number of hours to get data for.
	 * @return array The violations data.
	 */
	public function get_violations_data( $period_hours = 24 ) {
		return $this->security->get_violations_data( $period_hours );
	}

	/**
	 * Record a rate limit violation.
	 * 
	 * @since 1.0.0
	 * @param string $worker_name The name of the worker that was requested.
	 * @param string $ip_address  The IP address that made the request.
	 * @param array  $metadata    Additional metadata about the violation.
	 * @return bool Whether the violation was recorded successfully.
	 */
	public function record_violation( $worker_name, $ip_address = '', $metadata = array() ) {
		return $this->security->record_violation( $worker_name, $ip_address, $metadata );
	}

	/**
	 * Clean up old violation records.
	 * 
	 * @since 1.0.0
	 * @param int $days The number of days to keep records for.
	 * @return int The number of records cleaned up.
	 */
	public function cleanup_violations( $days = 30 ) {
		return $this->security->cleanup_violations( $days );
	}

	/**
	 * Check if cached response exists.
	 * 
	 * @since 1.0.0
	 * @param string $cache_key The cache key.
	 * @return bool Whether the cached response exists.
	 */
	public function has_cached_response( $cache_key ) {
		return $this->rate_limiter->has_cached_response( $cache_key );
	}

	/**
	 * Delete cached response.
	 * 
	 * @since 1.0.0
	 * @param string $cache_key The cache key.
	 * @return bool Whether the cached response was deleted.
	 */
	public function delete_cached_response( $cache_key ) {
		return $this->rate_limiter->delete_cached_response( $cache_key );
	}

	/**
	 * Clean up old cached responses.
	 * 
	 * @since 1.0.0
	 * @return int The number of cached responses cleaned up.
	 */
	public function cleanup_cache() {
		return $this->rate_limiter->cleanup_cache();
	}

	/**
	 * Get current IP address.
	 *
	 * @since 1.0.0
	 * @return string The current IP address.
	 */
	public function get_ip_address() {
		return $this->security->get_ip_address();
	}

	/**
	 * Reload worker configuration from database.
	 *
	 * Forces a fresh reload of worker configuration from the database,
	 * ensuring that any recently saved changes are immediately available.
	 *
	 * @since 2.1.0
	 * @return bool True if configuration was reloaded successfully.
	 */
	public function reload_worker_config() {
		if ( $this->manager && method_exists( $this->manager, 'reload_worker_config' ) ) {
			return $this->manager->reload_worker_config();
		}
		
		error_log( '[365i AI FAQ] Warning: Cannot reload worker config - manager not available' );
		return false;
	}
}