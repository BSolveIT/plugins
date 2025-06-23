<?php
/**
 * Admin security management class for 365i AI FAQ Generator.
 * 
 * This class handles IP blocking, rate limit violations, and
 * security-related functionality.
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
 * Admin security management class.
 * 
 * @since 2.1.0
 */
class AI_FAQ_Admin_Security {

	/**
	 * Initialize the security component.
	 * 
	 * No direct hooks needed as this is used by other components.
	 * 
	 * @since 2.1.0
	 */
	public function init() {
		// This class is primarily used by other components rather than hooking directly.
	}

	/**
	 * Get rate limit violations data.
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
	 * Block an IP address.
	 * 
	 * @since 2.1.0
	 * @param string $ip_address IP address to block.
	 * @param string $reason Reason for blocking.
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
			ai_faq_log_info( sprintf(
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
			ai_faq_log_info( sprintf(
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
	 * Record a rate limit violation.
	 * 
	 * @since 2.1.0
	 * @param string $worker Worker name.
	 * @param string $ip_address IP address.
	 * @param int    $requests_count Number of requests made.
	 * @param int    $limit Rate limit threshold.
	 * @param bool   $blocked Whether the IP was blocked.
	 * @return bool Whether the violation was recorded.
	 */
	public function record_violation( $worker, $ip_address, $requests_count, $limit, $blocked = false ) {
		// Get current violations log.
		$violations = get_option( 'ai_faq_violations_log', array() );

		// Add new violation.
		$violations[] = array(
			'timestamp' => time(),
			'ip' => $ip_address,
			'worker' => $worker,
			'requests_count' => $requests_count,
			'limit' => $limit,
			'blocked' => $blocked,
		);

		// Trim log if it gets too large (keep last 1000 entries).
		if ( count( $violations ) > 1000 ) {
			$violations = array_slice( $violations, -1000 );
		}

		// Save the updated violations log.
		return update_option( 'ai_faq_violations_log', $violations );
	}

	/**
	 * Check if an IP is blocked.
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

		$block_data = $blocked_ips[ $ip_address ];

		// Check if block has expired.
		if ( $block_data['expires_at'] > 0 && $block_data['expires_at'] < time() ) {
			// Block has expired, remove it.
			unset( $blocked_ips[ $ip_address ] );
			update_option( 'ai_faq_blocked_ips', $blocked_ips );
			return false;
		}

		return $block_data;
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