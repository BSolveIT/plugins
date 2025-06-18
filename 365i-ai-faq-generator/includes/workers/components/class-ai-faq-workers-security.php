<?php
/**
 * Workers Security class for 365i AI FAQ Generator.
 * 
 * This class handles IP management, including blocking, whitelisting,
 * and violation tracking for worker API requests.
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
 * Workers Security class.
 * 
 * Manages IP blocking, whitelisting, and violation tracking.
 * 
 * @since 2.1.0
 */
class AI_FAQ_Workers_Security {

	/**
	 * Constructor.
	 * 
	 * Initialize the security component.
	 * 
	 * @since 2.1.0
	 */
	public function __construct() {
		// Constructor is empty for now.
	}

	/**
	 * Initialize the security component.
	 * 
	 * Set up hooks and filters.
	 * 
	 * @since 2.1.0
	 */
	public function init() {
		// Schedule cleanup task for expired blocks.
		if ( ! wp_next_scheduled( 'ai_faq_cleanup_blocks' ) ) {
			wp_schedule_event( time(), 'daily', 'ai_faq_cleanup_blocks' );
		}

		// Add cleanup hook.
		add_action( 'ai_faq_cleanup_blocks', array( $this, 'cleanup_expired_blocks' ) );
	}

	/**
	 * Get client IP address with proxy detection.
	 * 
	 * @since 2.1.0
	 * @return string Client IP address.
	 */
	public function get_client_ip() {
		// Check for various headers that might contain the real IP.
		$ip_headers = array(
			'HTTP_CF_CONNECTING_IP',     // Cloudflare.
			'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy.
			'HTTP_X_FORWARDED',          // Proxy.
			'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster.
			'HTTP_FORWARDED_FOR',        // Proxy.
			'HTTP_FORWARDED',            // Proxy.
			'REMOTE_ADDR',               // Standard.
		);

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip_list = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
				$ip = trim( $ip_list[0] );
				
				// Validate IP address.
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		// Fallback to REMOTE_ADDR even if it's a private IP.
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
	}

	/**
	 * Check if IP address is whitelisted.
	 * 
	 * @since 2.1.0
	 * @param string $ip_address IP address to check.
	 * @return bool True if whitelisted, false otherwise.
	 */
	public function is_ip_whitelisted( $ip_address ) {
		$whitelist = get_option( 'ai_faq_ip_whitelist', array() );
		return isset( $whitelist[ $ip_address ] ) && 'active' === $whitelist[ $ip_address ]['status'];
	}

	/**
	 * Check if IP is blocked.
	 * 
	 * @since 2.1.0
	 * @param string $ip_address IP address to check.
	 * @return bool|array False if not blocked, block data if blocked.
	 */
	public function is_ip_blocked( $ip_address ) {
		$blocked_ips = get_option( 'ai_faq_blocked_ips', array() );
		
		if ( ! isset( $blocked_ips[ $ip_address ] ) ) {
			return false;
		}
		
		$block_info = $blocked_ips[ $ip_address ];
		
		// Check if block has expired (0 = permanent).
		if ( $block_info['expires_at'] > 0 && time() > $block_info['expires_at'] ) {
			// Remove expired block.
			unset( $blocked_ips[ $ip_address ] );
			update_option( 'ai_faq_blocked_ips', $blocked_ips );
			return false;
		}
		
		return $block_info;
	}

	/**
	 * Block an IP address.
	 * 
	 * @since 2.1.0
	 * @param string $ip_address IP address to block.
	 * @param string $reason Block reason.
	 * @param int    $duration_hours Duration in hours (0 for permanent).
	 * @return array Result of blocking operation.
	 */
	public function block_ip( $ip_address, $reason = '', $duration_hours = 24 ) {
		// Validate IP address format.
		if ( ! filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid IP address format.', '365i-ai-faq-generator' ),
			);
		}

		// Get current blocked IPs.
		$blocked_ips = get_option( 'ai_faq_blocked_ips', array() );

		// Calculate expiration time.
		$expires_at = $duration_hours > 0 ? time() + ( $duration_hours * HOUR_IN_SECONDS ) : 0; // 0 = permanent

		// Add the block.
		$blocked_ips[ $ip_address ] = array(
			'blocked_at' => time(),
			'expires_at' => $expires_at,
			'block_type' => 'manual',
			'reason' => $reason ?: __( 'Manual block by admin', '365i-ai-faq-generator' ),
			'blocked_by' => wp_get_current_user()->user_login,
			'can_appeal' => true,
		);

		// Save the updated blocked IPs.
		$update_result = update_option( 'ai_faq_blocked_ips', $blocked_ips );

		if ( $update_result ) {
			// Log the action.
			error_log( sprintf(
				'[365i AI FAQ] Admin %s blocked IP %s for %d hours. Reason: %s',
				wp_get_current_user()->user_login,
				$ip_address,
				$duration_hours,
				$reason
			) );

			return array(
				'success' => true,
				'message' => sprintf(
					/* translators: %s: IP address */
					__( 'IP address %s has been blocked successfully.', '365i-ai-faq-generator' ),
					$ip_address
				),
			);
		} else {
			return array(
				'success' => false,
				'message' => __( 'Failed to block IP address.', '365i-ai-faq-generator' ),
			);
		}
	}

	/**
	 * Auto-block an IP based on violations.
	 * 
	 * @since 2.1.0
	 * @param string $ip_address IP address to auto-block.
	 * @param string $worker Worker that triggered the auto-block.
	 * @param int    $duration_hours Duration to block for.
	 * @return bool Whether the IP was blocked.
	 */
	public function auto_block_ip( $ip_address, $worker, $duration_hours = 24 ) {
		$reason = sprintf(
			/* translators: %s: Worker name */
			__( 'Auto-blocked due to rate limit violations on %s worker', '365i-ai-faq-generator' ),
			$worker
		);

		$result = $this->block_ip( $ip_address, $reason, $duration_hours );
		
		return $result['success'];
	}

	/**
	 * Unblock an IP address.
	 * 
	 * @since 2.1.0
	 * @param string $ip_address IP address to unblock.
	 * @return array Result of unblocking operation.
	 */
	public function unblock_ip( $ip_address ) {
		// Get current blocked IPs.
		$blocked_ips = get_option( 'ai_faq_blocked_ips', array() );

		if ( ! isset( $blocked_ips[ $ip_address ] ) ) {
			return array(
				'success' => false,
				'message' => __( 'IP address is not currently blocked.', '365i-ai-faq-generator' ),
			);
		}

		// Remove the block.
		unset( $blocked_ips[ $ip_address ] );

		// Save the updated blocked IPs.
		$update_result = update_option( 'ai_faq_blocked_ips', $blocked_ips );

		if ( $update_result ) {
			// Log the action.
			error_log( sprintf(
				'[365i AI FAQ] Admin %s unblocked IP %s',
				wp_get_current_user()->user_login,
				$ip_address
			) );

			return array(
				'success' => true,
				'message' => sprintf(
					/* translators: %s: IP address */
					__( 'IP address %s has been unblocked successfully.', '365i-ai-faq-generator' ),
					$ip_address
				),
			);
		} else {
			return array(
				'success' => false,
				'message' => __( 'Failed to unblock IP address.', '365i-ai-faq-generator' ),
			);
		}
	}

	/**
	 * Log rate limit violation with detailed tracking.
	 * 
	 * @since 2.1.0
	 * @param string $ip_address Client IP address.
	 * @param string $worker_name Worker identifier.
	 * @param int    $current_count Current usage count.
	 * @param int    $rate_limit Rate limit threshold.
	 * @return bool Whether the violation was logged.
	 */
	public function log_violation( $ip_address, $worker_name, $current_count, $rate_limit ) {
		$violations = get_option( 'ai_faq_violations_log', array() );
		
		$violation = array(
			'ip' => $ip_address,
			'worker' => $worker_name,
			'timestamp' => time(),
			'requests_count' => $current_count,
			'limit' => $rate_limit,
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'severity' => $this->calculate_violation_severity( $current_count, $rate_limit ),
			'page_url' => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
		);
		
		$violations[] = $violation;
		
		// Maintain rolling log: keep last 1000 violations, 30-day retention.
		$violations = array_slice( $violations, -1000 );
		$violations = array_filter( $violations, function( $v ) {
			return $v['timestamp'] > ( time() - ( 30 * DAY_IN_SECONDS ) );
		} );
		
		$result = update_option( 'ai_faq_violations_log', $violations );
		
		// Check if this should trigger consequences.
		if ( $result ) {
			$this->process_violation_consequences( $ip_address, $worker_name );
		}
		
		return $result;
	}

	/**
	 * Calculate violation severity based on usage vs limit.
	 * 
	 * @since 2.1.0
	 * @param int $current_count Current usage count.
	 * @param int $rate_limit Rate limit threshold.
	 * @return string Severity level (low, medium, high, critical).
	 */
	private function calculate_violation_severity( $current_count, $rate_limit ) {
		$ratio = $current_count / max( $rate_limit, 1 );
		
		if ( $ratio >= 3.0 ) {
			return 'critical';
		} elseif ( $ratio >= 2.0 ) {
			return 'high';
		} elseif ( $ratio >= 1.5 ) {
			return 'medium';
		} else {
			return 'low';
		}
	}

	/**
	 * Process violation consequences including automatic blocking.
	 * 
	 * @since 2.1.0
	 * @param string $ip_address Client IP address.
	 * @param string $worker_name Worker name.
	 */
	private function process_violation_consequences( $ip_address, $worker_name ) {
		$violations_last_hour = $this->get_violations_last_hour( $ip_address );
		$total_violations = count( $violations_last_hour );
		
		// Get configurable thresholds.
		$options = get_option( 'ai_faq_gen_options', array() );
		$rate_limiting = isset( $options['rate_limiting'] ) ? $options['rate_limiting'] : array();
		
		$violation_threshold = isset( $rate_limiting['violation_threshold'] ) ? intval( $rate_limiting['violation_threshold'] ) : 3;
		$auto_block_threshold = isset( $rate_limiting['auto_block_threshold'] ) ? intval( $rate_limiting['auto_block_threshold'] ) : 5;
		
		// Send alert if threshold reached.
		if ( $total_violations >= $violation_threshold ) {
			$this->send_violation_alert( 'threshold_reached', array(
				'ip' => $ip_address,
				'worker' => $worker_name,
				'count' => $total_violations,
				'timestamp' => time(),
			) );
		}
		
		// Auto-block if severe threshold reached.
		if ( $total_violations >= $auto_block_threshold ) {
			$this->auto_block_ip( $ip_address, $worker_name );
			
			$this->send_violation_alert( 'ip_blocked', array(
				'ip' => $ip_address,
				'worker' => $worker_name,
				'count' => $total_violations,
				'timestamp' => time(),
			) );
		}
	}

	/**
	 * Get violations for IP in the last hour.
	 * 
	 * @since 2.1.0
	 * @param string $ip_address IP address to check.
	 * @return array Array of violations.
	 */
	public function get_violations_last_hour( $ip_address ) {
		$violations = get_option( 'ai_faq_violations_log', array() );
		$cutoff_time = time() - HOUR_IN_SECONDS;
		
		return array_filter( $violations, function( $violation ) use ( $ip_address, $cutoff_time ) {
			return $violation['ip'] === $ip_address && $violation['timestamp'] > $cutoff_time;
		} );
	}

	/**
	 * Get violations data.
	 * 
	 * @since 2.1.0
	 * @param int $period_hours Period in hours to fetch violations for.
	 * @return array Violations data.
	 */
	public function get_violations_data( $period_hours = 24 ) {
		$cutoff_time = time() - ( $period_hours * HOUR_IN_SECONDS );

		$violations = get_option( 'ai_faq_violations_log', array() );
		$filtered_violations = array_filter( $violations, function( $violation ) use ( $cutoff_time ) {
			return $violation['timestamp'] > $cutoff_time;
		} );

		// Group violations by IP.
		$violations_by_ip = array();
		foreach ( $filtered_violations as $violation ) {
			$ip = $violation['ip'];
			if ( ! isset( $violations_by_ip[ $ip ] ) ) {
				$violations_by_ip[ $ip ] = array(
					'ip' => $ip,
					'count' => 0,
					'workers' => array(),
					'last_violation' => 0,
					'severity' => 'low',
				);
			}
			$violations_by_ip[ $ip ]['count']++;
			$violations_by_ip[ $ip ]['workers'][ $violation['worker'] ] = true;
			$violations_by_ip[ $ip ]['last_violation'] = max( $violations_by_ip[ $ip ]['last_violation'], $violation['timestamp'] );
		}

		// Sort by violation count (highest first).
		uasort( $violations_by_ip, function( $a, $b ) {
			return $b['count'] - $a['count'];
		} );

		// Get blocked IPs status.
		$blocked_ips = get_option( 'ai_faq_blocked_ips', array() );

		// Add block status to each IP.
		foreach ( $violations_by_ip as &$violation_data ) {
			$violation_data['is_blocked'] = isset( $blocked_ips[ $violation_data['ip'] ] );
			$violation_data['workers'] = array_keys( $violation_data['workers'] );
			$violation_data['severity'] = $violation_data['count'] >= 5 ? 'high' : ( $violation_data['count'] >= 3 ? 'medium' : 'low' );
		}

		$response_data = array(
			'violations' => array_values( $violations_by_ip ),
			'summary' => array(
				'total_violations' => count( $filtered_violations ),
				'unique_ips' => count( $violations_by_ip ),
				'blocked_ips' => count( $blocked_ips ),
				'period_hours' => $period_hours,
			),
		);

		return $response_data;
	}

	/**
	 * Send violation alert email to admin.
	 * 
	 * @since 2.1.0
	 * @param string $alert_type Type of alert.
	 * @param array  $data Alert data.
	 */
	private function send_violation_alert( $alert_type, $data ) {
		$options = get_option( 'ai_faq_gen_options', array() );
		$rate_limiting = isset( $options['rate_limiting'] ) ? $options['rate_limiting'] : array();
		
		// Check if alerts are enabled.
		if ( ! isset( $rate_limiting['enable_alerts'] ) || ! $rate_limiting['enable_alerts'] ) {
			return;
		}
		
		$admin_email = isset( $rate_limiting['alert_email'] ) ? $rate_limiting['alert_email'] : get_option( 'admin_email' );
		$site_name = get_bloginfo( 'name' );
		
		$subject = sprintf( 
			/* translators: %s: site name */
			__( '[%s] AI FAQ Generator - Rate Limit Violation Alert', '365i-ai-faq-generator' ), 
			$site_name 
		);
		
		$message = sprintf(
			/* translators: 1: site name, 2: alert type, 3: IP address, 4: worker name, 5: violation count, 6: timestamp, 7: admin URL */
			__( "Rate limit violations detected on %1\$s:\n\nAlert Type: %2\$s\nIP Address: %3\$s\nWorker: %4\$s\nViolation Count: %5\$d\nTime: %6\$s\n\nAdmin Dashboard: %7\$s", '365i-ai-faq-generator' ),
			$site_name,
			$alert_type,
			$data['ip'],
			$data['worker'],
			$data['count'],
			date( 'Y-m-d H:i:s', $data['timestamp'] ),
			admin_url( 'admin.php?page=ai-faq-generator' )
		);
		
		// Rate limit alerts: max 1 email per hour per IP.
		$alert_key = 'ai_faq_alert_' . md5( $data['ip'] );
		if ( ! get_transient( $alert_key ) ) {
			wp_mail( $admin_email, $subject, $message );
			set_transient( $alert_key, 1, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Clean up expired blocks.
	 * 
	 * @since 2.1.0
	 * @return int Number of blocks removed.
	 */
	public function cleanup_expired_blocks() {
		$blocked_ips = get_option( 'ai_faq_blocked_ips', array() );
		$current_time = time();
		$removed_count = 0;

		foreach ( $blocked_ips as $ip => $block_data ) {
			if ( $block_data['expires_at'] > 0 && $block_data['expires_at'] < $current_time ) {
				unset( $blocked_ips[ $ip ] );
				$removed_count++;
			}
		}

		if ( $removed_count > 0 ) {
			update_option( 'ai_faq_blocked_ips', $blocked_ips );
		}

		return $removed_count;
	}
}