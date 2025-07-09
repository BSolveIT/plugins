<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @package Quick_FAQ_Markup
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Quick FAQ Markup Uninstall Class
 *
 * Handles the cleanup of plugin data when the plugin is uninstalled.
 *
 * @since 1.0.0
 */
class Quick_FAQ_Markup_Uninstall {

	/**
	 * Run the uninstall process.
	 *
	 * @since 1.0.0
	 */
	public static function run() {
		// Check if we should preserve data
		$preserve_data = get_option( 'quick_faq_markup_preserve_data', false );
		
		if ( $preserve_data ) {
			// Only remove plugin options but keep FAQ data
			self::remove_plugin_options();
		} else {
			// Remove all plugin data
			self::remove_all_data();
		}
		
		// Clear any cached data
		self::clear_cache();
		
		// Log the uninstall
		if ( function_exists( 'quick_faq_markup_log' ) ) {
			quick_faq_markup_log( 'Plugin uninstalled', 'info' );
		}
	}

	/**
	 * Remove all plugin data including FAQs.
	 *
	 * @since 1.0.0
	 */
	private static function remove_all_data() {
		global $wpdb;

		// Remove all FAQ posts
		$faq_posts = get_posts( array(
			'post_type'      => 'qfm_faq',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		) );

		foreach ( $faq_posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		// Remove any remaining post meta
		$wpdb->query( 
			$wpdb->prepare( 
				"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
				'_qfm_%'
			)
		);

		// Remove plugin options
		self::remove_plugin_options();
	}

	/**
	 * Remove only plugin options, preserve FAQ data.
	 *
	 * @since 1.0.0
	 */
	private static function remove_plugin_options() {
		// Remove plugin settings
		delete_option( 'quick_faq_markup_settings' );
		delete_option( 'quick_faq_markup_version' );
		delete_option( 'quick_faq_markup_preserve_data' );
		
		// Remove any activation/deactivation options
		delete_option( 'quick_faq_markup_activation_time' );
		delete_option( 'quick_faq_markup_deactivation_time' );
		
		// Remove any cache options
		delete_option( 'quick_faq_markup_cache_cleared' );
	}

	/**
	 * Clear all cached data.
	 *
	 * @since 1.0.0
	 */
	private static function clear_cache() {
		// Clear WordPress object cache
		wp_cache_flush();
		
		// Clear any specific plugin cache
		wp_cache_delete_group( 'quick_faq_markup' );
		
		// Clear transients
		global $wpdb;
		$wpdb->query( 
			$wpdb->prepare( 
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_qfm_%'
			)
		);
		
		$wpdb->query( 
			$wpdb->prepare( 
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_timeout_qfm_%'
			)
		);
	}

	/**
	 * Remove custom database tables (if any exist in future versions).
	 *
	 * @since 1.0.0
	 */
	private static function remove_custom_tables() {
		global $wpdb;
		
		// Future versions might have custom tables
		// For now, we only use WordPress core tables
		
		// Example for future use:
		// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}qfm_statistics" );
	}

	/**
	 * Remove user meta related to the plugin.
	 *
	 * @since 1.0.0
	 */
	private static function remove_user_meta() {
		global $wpdb;
		
		// Remove any user meta keys related to the plugin
		$wpdb->query( 
			$wpdb->prepare( 
				"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
				'qfm_%'
			)
		);
	}

	/**
	 * Remove site options for multisite installations.
	 *
	 * @since 1.0.0
	 */
	private static function remove_site_options() {
		if ( is_multisite() ) {
			delete_site_option( 'quick_faq_markup_network_settings' );
			delete_site_option( 'quick_faq_markup_network_version' );
		}
	}

	/**
	 * Complete uninstall process including multisite cleanup.
	 *
	 * @since 1.0.0
	 */
	public static function complete_uninstall() {
		// Remove user meta
		self::remove_user_meta();
		
		// Remove site options (multisite)
		self::remove_site_options();
		
		// Remove custom tables
		self::remove_custom_tables();
		
		// Final cache clear
		wp_cache_flush();
	}
}

// Execute the uninstall process
Quick_FAQ_Markup_Uninstall::run();

// For multisite, we need to handle network-wide uninstall
if ( is_multisite() ) {
	// Get all sites
	$sites = get_sites( array( 'number' => 0 ) );
	
	foreach ( $sites as $site ) {
		switch_to_blog( $site->blog_id );
		Quick_FAQ_Markup_Uninstall::run();
		restore_current_blog();
	}
	
	// Clean up network-wide options
	Quick_FAQ_Markup_Uninstall::complete_uninstall();
} else {
	// Single site cleanup
	Quick_FAQ_Markup_Uninstall::complete_uninstall();
}

// Final verification - ensure all plugin data is removed
if ( ! get_option( 'quick_faq_markup_preserve_data', false ) ) {
	// Verify FAQ posts are removed
	$remaining_faqs = get_posts( array(
		'post_type'   => 'qfm_faq',
		'post_status' => 'any',
		'fields'      => 'ids',
		'numberposts' => 1,
	) );
	
	if ( empty( $remaining_faqs ) ) {
		// Success - all FAQ data removed
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Quick FAQ Markup: All plugin data successfully removed during uninstall' );
		}
	} else {
		// Log warning - some data might remain
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Quick FAQ Markup: Warning - Some FAQ data may remain after uninstall' );
		}
	}
}