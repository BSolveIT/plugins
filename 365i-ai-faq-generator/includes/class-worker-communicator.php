<?php
/**
 * Worker Communicator - Handles communication with Cloudflare AI workers
 *
 * @link       https://365i.com
 * @since      1.0.0
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/includes
 */

/**
 * Worker Communicator class.
 *
 * This class is responsible for communicating with the AI workers, 
 * handling rate limiting, and processing responses.
 *
 * @since      1.0.0
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/includes
 * @author     365i
 */
class Worker_Communicator {

    /**
     * The worker settings from options.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $worker_settings    The worker settings.
     */
    private $worker_settings;
    
    /**
     * API key for authentication with workers.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_key    The API key.
     */
    private $api_key;
    
    /**
     * Mapping of short worker keys to actual worker endpoints.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $worker_key_map    The worker key mapping.
     */
    private $worker_key_map = array(
        'question' => 'faq-realtime-assistant-worker',
        'answer' => 'faq-answer-generator-worker',
        'seo' => 'faq-seo-analyzer-worker',
        'enhance' => 'faq-enhancement-worker',
        'extract' => 'url-to-faq-generator-worker',
        'topic' => 'faq-topic-generator-worker',
        'validate' => 'faq-content-validator-worker'
    );

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->worker_settings = get_option('faq_ai_generator_workers', array());
        $this->api_key = get_option('faq_ai_generator_api_key', '');
    }

    /**
     * Send a request to a specific AI worker.
     *
     * @since    1.0.0
     * @param    string   $worker_key    The worker key (e.g., 'question', 'answer', etc.).
     * @param    array    $data          The data to send to the worker.
     * @param    bool     $bypass_rate_limit Whether to bypass rate limit check.
     * @return   array|WP_Error         The response from the worker or an error.
     */
    public function send_request($worker_key, $data, $bypass_rate_limit = false) {
        // Check if we need to map the worker key to the actual endpoint
        $original_key = $worker_key;
        if (isset($this->worker_key_map[$worker_key])) {
            // Using new key format, map to original for backward compatibility
            $worker_key = $this->worker_key_map[$worker_key];
        }
        
        // Check if worker exists and is enabled
        if (!isset($this->worker_settings[$original_key]) || !$this->worker_settings[$original_key]['enabled']) {
            return new WP_Error(
                'worker_disabled',
                sprintf(__('The worker %s is not available or disabled.', 'faq-ai-generator'), $original_key)
            );
        }

        $worker_url = $this->worker_settings[$original_key]['url'];
        $rate_limit = $this->worker_settings[$original_key]['rate_limit'];
        $cooldown = $this->worker_settings[$original_key]['cooldown'];

        // Check rate limit if not bypassing
        if (!$bypass_rate_limit) {
            $rate_status = $this->check_rate_limit($worker_key, $rate_limit);
            if (is_wp_error($rate_status)) {
                return $rate_status;
            }
        }

        // Prepare the request
        $headers = array(
            'Content-Type' => 'application/json',
        );
        
        // Add API key if available
        if (!empty($this->api_key)) {
            $headers['X-API-Key'] = $this->api_key;
        }
        
        $args = array(
            'method'      => 'POST',
            'timeout'     => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'headers'     => $headers,
            'body'        => json_encode($data),
            'cookies'     => array(),
        );

        // Send the request
        $response = wp_remote_post($worker_url, $args);

        // Handle response
        if (is_wp_error($response)) {
            return $response;
        }

        // Check response code
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_body($response);
            return new WP_Error(
                'worker_error',
                sprintf(
                    __('Worker returned error: %s (Code: %d)', 'faq-ai-generator'),
                    $error_message,
                    $response_code
                )
            );
        }

        // Update rate limit counters
        if (!$bypass_rate_limit) {
            $this->update_rate_limit_counter($worker_key);
        }

        // Process successful response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'json_parse_error',
                __('Failed to parse JSON response from worker.', 'faq-ai-generator')
            );
        }

        return $data;
    }

    /**
     * Check if a worker has exceeded its rate limit.
     *
     * @since    1.0.0
     * @param    string   $worker_key    The worker key.
     * @param    int      $rate_limit    The worker's rate limit.
     * @return   bool|WP_Error          True if rate limit is good, WP_Error if exceeded.
     */
    private function check_rate_limit($worker_key, $rate_limit) {
        $transient_key = 'faq_ai_rate_' . $worker_key;
        $current_count = get_transient($transient_key);
        
        if ($current_count !== false && $current_count >= $rate_limit) {
            // Rate limit exceeded
            $reset_time = get_option('faq_ai_rate_reset_' . $worker_key, time() + 3600);
            $time_left = $reset_time - time();
            
            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(
                    __('Rate limit exceeded for %s. Please try again in %d minutes.', 'faq-ai-generator'),
                    $worker_key,
                    ceil($time_left / 60)
                ),
                array(
                    'reset_time' => $reset_time,
                    'time_left' => $time_left,
                    'rate_limit' => $rate_limit,
                    'current_count' => $current_count
                )
            );
        }
        
        return true;
    }

    /**
     * Update the rate limit counter for a worker.
     *
     * @since    1.0.0
     * @param    string   $worker_key    The worker key.
     * @return   void
     */
    private function update_rate_limit_counter($worker_key) {
        $transient_key = 'faq_ai_rate_' . $worker_key;
        $current_count = get_transient($transient_key);
        
        if ($current_count === false) {
            // Initialize counter with expiration at midnight UTC
            $tomorrow = strtotime('tomorrow midnight');
            $seconds_until_reset = $tomorrow - time();
            
            set_transient($transient_key, 1, $seconds_until_reset);
            update_option('faq_ai_rate_reset_' . $worker_key, $tomorrow);
        } else {
            // Increment counter
            set_transient($transient_key, $current_count + 1, get_option('faq_ai_rate_reset_' . $worker_key, time() + 3600) - time());
        }
    }

    /**
     * Reset rate limit counters for all workers or a specific worker.
     *
     * @since    1.0.0
     * @param    string|null   $worker_key    Optional. The worker key to reset, or null for all workers.
     * @return   void
     */
    public function reset_rate_limits($worker_key = null) {
        if ($worker_key !== null) {
            // Reset specific worker
            delete_transient('faq_ai_rate_' . $worker_key);
            delete_option('faq_ai_rate_reset_' . $worker_key);
        } else {
            // Reset all workers
            foreach (array_keys($this->worker_settings) as $key) {
                delete_transient('faq_ai_rate_' . $key);
                delete_option('faq_ai_rate_reset_' . $key);
            }
            
            // Also reset any old-format keys that might still have transients
            foreach ($this->worker_key_map as $new_key => $old_key) {
                delete_transient('faq_ai_rate_' . $old_key);
                delete_option('faq_ai_rate_reset_' . $old_key);
            }
        }
    }

    /**
     * Get current rate limit usage for a worker.
     *
     * @since    1.0.0
     * @param    string   $worker_key    The worker key (new format, e.g., 'question').
     * @return   array                  Rate limit information.
     */
    public function get_rate_limit_info($worker_key) {
        // Check if we need to map the worker key
        $original_key = $worker_key;
        if (isset($this->worker_key_map[$worker_key])) {
            // For backward compatibility with existing transients
            $worker_key = $this->worker_key_map[$worker_key];
        }
        
        if (!isset($this->worker_settings[$original_key])) {
            return array(
                'used' => 0,
                'limit' => 0,
                'remaining' => 0,
                'reset_time' => time(),
                'status' => 'unknown'
            );
        }

        $transient_key = 'faq_ai_rate_' . $worker_key;
        $current_count = get_transient($transient_key);
        $rate_limit = $this->worker_settings[$original_key]['rate_limit'];
        $reset_time = get_option('faq_ai_rate_reset_' . $worker_key, strtotime('tomorrow midnight'));
        
        $used = $current_count !== false ? intval($current_count) : 0;
        $remaining = $rate_limit - $used;
        
        $status = 'good';
        if ($remaining <= 0) {
            $status = 'exceeded';
        } elseif ($remaining < ($rate_limit * 0.2)) {
            $status = 'warning';
        }
        
        return array(
            'used' => $used,
            'limit' => $rate_limit,
            'remaining' => $remaining,
            'reset_time' => $reset_time,
            'reset_in' => human_time_diff(time(), $reset_time),
            'status' => $status
        );
    }
}