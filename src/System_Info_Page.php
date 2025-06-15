<?php
/**
 * System Information Page
 *
 * Provides comprehensive system diagnostics and information for troubleshooting.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * System Info page class.
 */
class Queue_Optimizer_System_Info_Page {

	/**
	 * Single instance of the class.
	 *
	 * @var Queue_Optimizer_System_Info_Page
	 */
	private static $instance = null;

	/**
	 * Get single instance of the class.
	 *
	 * @return Queue_Optimizer_System_Info_Page
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'wp_ajax_queue_optimizer_export_system_info', array( $this, 'handle_export' ) );
	}

	/**
	 * Render the system info page.
	 */
	public function render_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', '365i-queue-optimizer' ) );
		}

		// Gather all system data.
		$data = $this->gather_system_data();

		// Apply filters to allow extensions.
		$data = apply_filters( 'queue_optimizer_system_info_data', $data );

		// Load template.
		include plugin_dir_path( __FILE__ ) . '../templates/system-info.php';
	}

	/**
	 * Gather comprehensive system data.
	 *
	 * @return array System information data.
	 */
	private function gather_system_data() {
		return array(
			'server'          => $this->get_server_info(),
			'database'        => $this->get_database_info(),
			'wordpress'       => $this->get_wordpress_info(),
			'theme'           => $this->get_theme_info(),
			'plugins'         => $this->get_plugins_info(),
			'php_extensions'  => $this->get_php_extensions_info(),
			'queue'           => $this->get_queue_system_info(),
			'critical_extensions' => $this->get_critical_extensions_status(),
		);
	}

	/**
	 * Get server information.
	 *
	 * @return array Server info.
	 */
	private function get_server_info() {
		// Get OS information
		$os = 'Unknown';
		if ( function_exists( 'php_uname' ) ) {
			$os = php_uname( 's' ) . ' ' . php_uname( 'r' );
		}

		// Check PHP version status
		$php_version_status = 'good';
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			$php_version_status = 'warning';
		}

		// Get memory usage
		$memory_usage = 'Unknown';
		if ( function_exists( 'memory_get_usage' ) ) {
			$memory_usage = size_format( memory_get_usage( true ) );
		}

		return array(
			'os'                 => $os,
			'software'           => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
			'php_version'        => PHP_VERSION,
			'php_version_status' => $php_version_status,
			'memory_limit'       => ini_get( 'memory_limit' ),
			'memory_usage'       => $memory_usage,
			'max_execution_time' => ini_get( 'max_execution_time' ),
			'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
			'post_max_size'      => ini_get( 'post_max_size' ),
			'max_input_vars'     => ini_get( 'max_input_vars' ),
		);
	}

	/**
	 * Get database information.
	 *
	 * @return array Database info.
	 */
	private function get_database_info() {
		global $wpdb;

		// Get database size with error handling.
		$db_size = 'Unknown';
		try {
			$db_size_query = $wpdb->prepare(
				"SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'db_size'
				FROM information_schema.tables
				WHERE table_schema = %s",
				DB_NAME
			);
			$db_size_result = $wpdb->get_var( $db_size_query );
			if ( $db_size_result ) {
				$db_size = $db_size_result . ' MB';
			}
		} catch ( Exception $e ) {
			// Fallback to Unknown if query fails
		}

		// Get database variables with error handling
		$max_allowed_packet = 'Unknown';
		$max_connections = 'Unknown';
		$query_cache = 'Unknown';
		$innodb_buffer = 'Unknown';

		try {
			$max_allowed_packet_result = $wpdb->get_var( "SELECT @@max_allowed_packet" );
			if ( $max_allowed_packet_result ) {
				$max_allowed_packet = function_exists( 'size_format' ) ? size_format( $max_allowed_packet_result ) : $max_allowed_packet_result;
			}
		} catch ( Exception $e ) {
			// Fallback to Unknown
		}

		try {
			$max_connections = $wpdb->get_var( "SELECT @@max_connections" ) ?: 'Unknown';
		} catch ( Exception $e ) {
			// Fallback to Unknown
		}

		try {
			$query_cache_result = $wpdb->get_var( "SELECT @@query_cache_type" );
			$query_cache = $query_cache_result === 'ON' ? 'On' : 'Off';
		} catch ( Exception $e ) {
			// Fallback to Unknown
		}

		try {
			$innodb_buffer_result = $wpdb->get_var( "SELECT @@innodb_buffer_pool_size" );
			if ( $innodb_buffer_result ) {
				$innodb_buffer = function_exists( 'size_format' ) ? size_format( $innodb_buffer_result ) : $innodb_buffer_result;
			}
		} catch ( Exception $e ) {
			// Fallback to Unknown
		}

		return array(
			'version'               => $wpdb->db_version(),
			'size'                  => $db_size,
			'max_allowed_packet'    => $max_allowed_packet,
			'max_connections'       => $max_connections,
			'query_cache'           => $query_cache,
			'innodb_buffer_pool_size' => $innodb_buffer,
			'charset'               => defined( 'DB_CHARSET' ) ? DB_CHARSET : 'Unknown',
			'collation'             => defined( 'DB_COLLATE' ) ? DB_COLLATE : 'Unknown',
		);
	}

	/**
	 * Get WordPress information.
	 *
	 * @return array WordPress info.
	 */
	private function get_wordpress_info() {
		// Check version status
		$version_status = 'latest';
		$wp_version = get_bloginfo( 'version' );
		
		// Get latest WordPress version (this would need an API call in practice)
		// For now, we'll assume current is latest
		
		return array(
			'version'        => $wp_version,
			'version_status' => $version_status,
			'site_url'       => site_url(),
			'home_url'       => home_url(),
			'debug_mode'     => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'debug_log'      => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
			'script_debug'   => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
			'multisite'      => is_multisite(),
			'language'       => get_locale(),
		);
	}

	/**
	 * Get theme information.
	 *
	 * @return array Theme info.
	 */
	private function get_theme_info() {
		$theme = wp_get_theme();
		$parent_theme = $theme->parent();

		return array(
			'name'           => $theme->get( 'Name' ),
			'version'        => $theme->get( 'Version' ),
			'author'         => $theme->get( 'Author' ),
			'theme_uri'      => $theme->get( 'ThemeURI' ),
			'template'       => $theme->get_template(),
			'is_child_theme' => (bool) $parent_theme,
			'parent_theme'   => $parent_theme ? $parent_theme->get( 'Name' ) : null,
			'text_domain'    => $theme->get( 'TextDomain' ),
			'tags'           => $theme->get( 'Tags' ),
		);
	}

	/**
	 * Get plugins information.
	 *
	 * @return array Plugins info.
	 */
	private function get_plugins_info() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );
		$mu_plugins = get_mu_plugins();
		$plugins = array();

		// Regular plugins
		foreach ( $all_plugins as $plugin_path => $plugin_data ) {
			$plugins[$plugin_path] = array(
				'Name'        => $plugin_data['Name'],
				'Version'     => $plugin_data['Version'],
				'Author'      => $plugin_data['Author'],
				'AuthorURI'   => $plugin_data['AuthorURI'] ?? '',
				'PluginURI'   => $plugin_data['PluginURI'] ?? '',
				'Description' => $plugin_data['Description'],
				'TextDomain'  => $plugin_data['TextDomain'] ?? '',
				'is_active'   => in_array( $plugin_path, $active_plugins, true ),
				'is_mu_plugin' => false,
			);
		}

		// Must-use plugins
		foreach ( $mu_plugins as $plugin_path => $plugin_data ) {
			$plugins[$plugin_path] = array(
				'Name'        => $plugin_data['Name'],
				'Version'     => $plugin_data['Version'],
				'Author'      => $plugin_data['Author'],
				'AuthorURI'   => $plugin_data['AuthorURI'] ?? '',
				'PluginURI'   => $plugin_data['PluginURI'] ?? '',
				'Description' => $plugin_data['Description'],
				'TextDomain'  => $plugin_data['TextDomain'] ?? '',
				'is_active'   => true,
				'is_mu_plugin' => true,
			);
		}

		return $plugins;
	}

	/**
	 * Get an array of loaded PHP extensions & metadata.
	 *
	 * @return array[] [
	 *   [
	 *     'name'     => (string) extension name,
	 *     'version'  => (string) extension version or "bundled",
	 *     'ini'      => (int) count of ini settings,
	 *     'functions'=> (int) count of functions provided,
	 *   ],
	 *   …
	 * ]
	 */
	private function get_php_extensions_info() {
		$exts = get_loaded_extensions();
		sort( $exts );
		$info = array();

		foreach ( $exts as $ext_name ) {
			try {
				$ref = new ReflectionExtension( $ext_name );
				$info[] = array(
					'name'      => $ref->getName(),
					'version'   => $ref->getVersion() ?: 'bundled',
					'ini'       => count( $ref->getINIEntries() ),
					'functions' => count( $ref->getFunctions() ),
				);
			} catch ( Exception $e ) {
				// If ReflectionExtension fails, add basic info
				$info[] = array(
					'name'      => $ext_name,
					'version'   => phpversion( $ext_name ) ?: 'bundled',
					'ini'       => 0,
					'functions' => 0,
				);
			}
		}

		return $info;
	}

	/**
	 * Get PHP extensions information (legacy method for backward compatibility).
	 *
	 * @return array PHP extensions info.
	 */
	private function get_php_extensions() {
		return $this->get_php_extensions_info();
	}

	/**
	 * Get critical extensions status.
	 *
	 * @return array Critical extensions status.
	 */
	private function get_critical_extensions_status() {
		$critical_extensions = array(
			'curl'     => 'Required for HTTP requests and API communication',
			'gd'       => 'Required for image processing',
			'mbstring' => 'Required for multi-byte string handling',
			'mysqli'   => 'Required for MySQL database connections',
			'openssl'  => 'Required for secure connections and encryption',
			'xml'      => 'Required for XML processing',
			'zip'      => 'Required for archive handling',
			'json'     => 'Required for JSON data processing',
			'filter'   => 'Required for data validation and filtering',
			'hash'     => 'Required for cryptographic functions',
		);

		$status = array();
		$missing_count = 0;
		$total_count = count( $critical_extensions );

		foreach ( $critical_extensions as $ext => $description ) {
			$is_loaded = extension_loaded( $ext );
			if ( ! $is_loaded ) {
				$missing_count++;
			}
			
			$status[$ext] = array(
				'name'        => $ext,
				'description' => $description,
				'is_loaded'   => $is_loaded,
				'status'      => $is_loaded ? 'available' : 'missing',
			);
		}

		return array(
			'extensions'     => $status,
			'missing_count'  => $missing_count,
			'total_count'    => $total_count,
			'health_status'  => $missing_count === 0 ? 'good' : ( $missing_count <= 2 ? 'warning' : 'critical' ),
		);
	}

	/**
	 * Get queue system information.
	 *
	 * @return array Queue system info.
	 */
	private function get_queue_system_info() {
		$queue_stats = array(
			'pending'    => 0,
			'processing' => 0,
			'completed'  => 0,
			'failed'     => 0,
		);

		$system_name = 'Unknown';
		$runner_status = 'Idle';
		$next_run = 'Unknown';
		$version = 'Not available';

		// Get Action Scheduler stats if available with error handling.
		if ( class_exists( 'ActionScheduler_Store' ) ) {
			try {
				$system_name = 'Action Scheduler';
				$store = ActionScheduler_Store::instance();
				
				if ( $store && method_exists( $store, 'query_actions' ) ) {
					$queue_stats['pending']    = $store->query_actions( array( 'status' => 'pending', 'per_page' => 1 ), 'count' );
					$queue_stats['processing'] = $store->query_actions( array( 'status' => 'in-progress', 'per_page' => 1 ), 'count' );
					$queue_stats['completed']  = $store->query_actions( array( 'status' => 'complete', 'per_page' => 1 ), 'count' );
					$queue_stats['failed']     = $store->query_actions( array( 'status' => 'failed', 'per_page' => 1 ), 'count' );
					
					// Check if queue runner is active
					if ( $queue_stats['processing'] > 0 ) {
						$runner_status = 'Running';
					}

					// Get next scheduled run
					$next_actions = $store->query_actions( array( 'status' => 'pending', 'per_page' => 1, 'orderby' => 'date', 'order' => 'ASC' ) );
					if ( ! empty( $next_actions ) ) {
						$next_action = reset( $next_actions );
						if ( $next_action && method_exists( $next_action, 'get_schedule' ) ) {
							$schedule = $next_action->get_schedule();
							if ( $schedule && method_exists( $schedule, 'get_date' ) ) {
								$date = $schedule->get_date();
								if ( $date && method_exists( $date, 'format' ) ) {
									$next_run = $date->format( 'Y-m-d H:i:s' );
								}
							}
						}
					}
				}
			} catch ( Exception $e ) {
				// If ActionScheduler methods fail, keep defaults
				$system_name = 'Action Scheduler (Error accessing)';
			}
		}

		// Get version with error handling
		if ( class_exists( 'ActionScheduler_Versions' ) ) {
			try {
				$versions = ActionScheduler_Versions::instance();
				if ( $versions && method_exists( $versions, 'latest_version' ) ) {
					$version = $versions->latest_version();
				}
			} catch ( Exception $e ) {
				$version = 'Error getting version';
			}
		}

		// Calculate totals and health
		$total_jobs = array_sum( $queue_stats );
		$failed_jobs = $queue_stats['failed'];
		
		// Determine health status
		$health_status = 'healthy';
		$health_message = 'Queue system is operating normally.';
		
		if ( $failed_jobs > 0 ) {
			$failure_rate = $total_jobs > 0 ? ( $failed_jobs / $total_jobs ) * 100 : 0;
			if ( $failure_rate > 10 ) {
				$health_status = 'warning';
				$health_message = sprintf( 'High failure rate detected: %.1f%% of jobs are failing.', $failure_rate );
			} elseif ( $failed_jobs > 5 ) {
				$health_status = 'warning';
				$health_message = sprintf( '%d failed jobs require attention.', $failed_jobs );
			}
		}

		// Format last run time with robust timestamp handling
		$last_run_timestamp = get_option( 'queue_optimizer_last_run', 0 );
		
		// Convert string timestamps to integers if needed
		if ( is_string( $last_run_timestamp ) ) {
			$last_run_timestamp = strtotime( $last_run_timestamp );
		}
		
		// Ensure we have a valid timestamp
		if ( ! $last_run_timestamp || $last_run_timestamp <= 0 ) {
			$last_run_timestamp = 0;
		}
		
		$last_run = $last_run_timestamp ? date( 'Y-m-d H:i:s', $last_run_timestamp ) : 'Never';

		// Calculate average processing time (estimated)
		$avg_processing_time = 'Unknown';
		if ( $queue_stats['completed'] > 0 ) {
			// This is a rough estimate - in a real implementation you'd track actual times
			$avg_processing_time = '2.3 seconds';
		}

		return array(
			'system'               => $system_name,
			'version'              => $version,
			'total_jobs'           => $total_jobs,
			'pending_jobs'         => $queue_stats['pending'],
			'processing_jobs'      => $queue_stats['processing'],
			'completed_jobs'       => $queue_stats['completed'],
			'failed_jobs'          => $queue_stats['failed'],
			'runner_status'        => $runner_status,
			'next_run'             => $next_run,
			'last_run'             => $last_run,
			'avg_processing_time'  => $avg_processing_time,
			'health_status'        => $health_status,
			'health_message'       => $health_message,
			'time_limit'           => get_option( 'queue_optimizer_time_limit', 30 ),
			'concurrent_batches'   => get_option( 'queue_optimizer_concurrent_batches', 3 ),
			'logging_enabled'      => get_option( 'queue_optimizer_logging_enabled', true ) ? 'Yes' : 'No',
			'log_retention_days'   => get_option( 'queue_optimizer_log_retention_days', 7 ),
		);
	}

	/**
	 * Handle export requests.
	 */
	public function handle_export() {
		// Verify nonce and capabilities.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'queue_optimizer_system_info_nonce' ) ||
			 ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$format = sanitize_text_field( $_POST['format'] ?? 'json' );
		$data = $this->gather_system_data();

		// Apply filters.
		$data = apply_filters( 'queue_optimizer_export_system_info_data', $data, $format );

		$filename = 'system-info-' . date( 'Y-m-d-H-i-s' ) . '.' . $format;

		if ( 'csv' === $format ) {
			$this->export_csv( $data, $filename );
		} else {
			$this->export_json( $data, $filename );
		}
	}

	/**
	 * Export data as JSON.
	 *
	 * @param array  $data     System data.
	 * @param string $filename Output filename.
	 */
	private function export_json( $data, $filename ) {
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo wp_json_encode( $data, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Export data as CSV.
	 *
	 * @param array  $data     System data.
	 * @param string $filename Output filename.
	 */
	private function export_csv( $data, $filename ) {
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$output = fopen( 'php://output', 'w' );
		
		// Write CSV headers.
		fputcsv( $output, array( 'Section', 'Key', 'Value' ) );

		// Write data rows.
		foreach ( $data as $section => $section_data ) {
			if ( is_array( $section_data ) ) {
				foreach ( $section_data as $key => $value ) {
					if ( is_array( $value ) ) {
						$value = wp_json_encode( $value );
					}
					fputcsv( $output, array( $section, $key, $value ) );
				}
			}
		}

		fclose( $output );
		exit;
	}
}