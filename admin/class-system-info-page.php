<?php
/**
 * Queue Optimizer System Info Page Class
 *
 * Provides comprehensive system diagnostic information for troubleshooting
 * and support purposes.
 *
 * @package QueueOptimizer
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queue Optimizer System Info Page class.
 */
class Queue_Optimizer_System_Info_Page {

	/**
	 * Single instance of the system info page.
	 *
	 * @var Queue_Optimizer_System_Info_Page
	 */
	private static $instance = null;

	/**
	 * Page hook suffix.
	 *
	 * @var string
	 */
	private $page_hook;

	/**
	 * Get single instance of the system info page.
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
		$this->init();
	}

	/**
	 * Initialize the system info page.
	 */
	private function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'wp_ajax_queue_optimizer_export_system_info', array( $this, 'ajax_export_system_info' ) );
	}

	/**
	 * Add admin menu page.
	 */
	public function add_admin_menu() {
		$this->page_hook = add_management_page(
			__( 'System Info', '365i-queue-optimizer' ),
			__( 'System Info', '365i-queue-optimizer' ),
			'manage_options',
			'queue-optimizer-system-info',
			array( $this, 'render_system_info_page' )
		);

		// Enqueue assets only on this page
		add_action( 'admin_print_styles-' . $this->page_hook, array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue page-specific assets.
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'queue-optimizer-system-info',
			QUEUE_OPTIMIZER_PLUGIN_URL . 'assets/system-info.css',
			array(),
			QUEUE_OPTIMIZER_VERSION
		);

		wp_enqueue_script(
			'queue-optimizer-system-info',
			QUEUE_OPTIMIZER_PLUGIN_URL . 'assets/system-info.js',
			array( 'jquery', 'postbox' ),
			QUEUE_OPTIMIZER_VERSION,
			true
		);

		// Localize script for AJAX
		wp_localize_script(
			'queue-optimizer-system-info',
			'queueOptimizerSystemInfo',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'queue_optimizer_system_info_nonce' ),
				'strings'  => array(
					'copied'     => __( 'Copied to clipboard!', '365i-queue-optimizer' ),
					'copy_error' => __( 'Failed to copy to clipboard', '365i-queue-optimizer' ),
					'exporting'  => __( 'Exporting...', '365i-queue-optimizer' ),
					'export_error' => __( 'Export failed', '365i-queue-optimizer' ),
				),
			)
		);
	}

	/**
	 * Render the system info page.
	 */
	public function render_system_info_page() {
		// Get all system information
		$system_info = $this->gather_system_info();
		
		// Include template
		require_once plugin_dir_path( __FILE__ ) . '../includes/admin/templates/system-info-page.php';
	}

	/**
	 * Gather comprehensive system information.
	 *
	 * @return array System information organized by category.
	 */
	private function gather_system_info() {
		$info = array();

		// Apply filter to allow extensions
		$info = apply_filters( '365i_system_info_data', array(
			'server'        => $this->get_server_info(),
			'database'      => $this->get_database_info(),
			'wordpress'     => $this->get_wordpress_info(),
			'theme'         => $this->get_theme_info(),
			'plugins'       => $this->get_plugins_info(),
			'php_extensions' => $this->get_php_extensions_info(),
			'queue_system'  => $this->get_queue_system_info(),
		) );

		return $info;
	}

	/**
	 * Get server environment information.
	 *
	 * @return array Server information.
	 */
	private function get_server_info() {
		global $wpdb;

		$server_info = array();

		// Operating System
		$server_info['operating_system'] = php_uname( 's' ) . ' ' . php_uname( 'r' );

		// Web Server
		$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
		$server_info['web_server'] = $server_software;

		// PHP Information
		$server_info['php_version'] = phpversion();
		$server_info['php_sapi'] = php_sapi_name();
		$server_info['php_memory_limit'] = ini_get( 'memory_limit' );
		$server_info['php_max_execution_time'] = ini_get( 'max_execution_time' );
		$server_info['php_upload_max_filesize'] = ini_get( 'upload_max_filesize' );
		$server_info['php_post_max_size'] = ini_get( 'post_max_size' );
		$server_info['php_max_input_vars'] = ini_get( 'max_input_vars' );
		$server_info['php_max_input_time'] = ini_get( 'max_input_time' );

		// Disabled functions
		$disabled_functions = ini_get( 'disable_functions' );
		$server_info['php_disabled_functions'] = ! empty( $disabled_functions ) ? $disabled_functions : __( 'None', '365i-queue-optimizer' );

		// MySQL Information
		$server_info['mysql_version'] = $wpdb->db_version();

		return $server_info;
	}

	/**
	 * Get database information.
	 *
	 * @return array Database information.
	 */
	private function get_database_info() {
		global $wpdb;

		$db_info = array();

		$db_info['database_host'] = DB_HOST;
		$db_info['database_name'] = DB_NAME;
		$db_info['database_user'] = DB_USER;
		$db_info['table_prefix'] = $wpdb->prefix;
		$db_info['database_charset'] = DB_CHARSET;
		$db_info['database_collate'] = DB_COLLATE;

		// Database size
		$result = $wpdb->get_row( $wpdb->prepare( 
			"SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb' 
			FROM information_schema.tables 
			WHERE table_schema = %s", 
			DB_NAME 
		) );
		$db_info['database_size'] = isset( $result->size_mb ) ? $result->size_mb . ' MB' : __( 'Unknown', '365i-queue-optimizer' );

		return $db_info;
	}

	/**
	 * Get WordPress core information.
	 *
	 * @return array WordPress information.
	 */
	private function get_wordpress_info() {
		$wp_info = array();

		$wp_info['wordpress_version'] = get_bloginfo( 'version' );
		$wp_info['site_url'] = site_url();
		$wp_info['home_url'] = home_url();
		$wp_info['wp_debug'] = defined( 'WP_DEBUG' ) && WP_DEBUG ? __( 'Enabled', '365i-queue-optimizer' ) : __( 'Disabled', '365i-queue-optimizer' );
		$wp_info['wp_debug_log'] = defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ? __( 'Enabled', '365i-queue-optimizer' ) : __( 'Disabled', '365i-queue-optimizer' );
		$wp_info['wp_memory_limit'] = defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : __( 'Default', '365i-queue-optimizer' );
		$wp_info['wp_max_memory_limit'] = defined( 'WP_MAX_MEMORY_LIMIT' ) ? WP_MAX_MEMORY_LIMIT : __( 'Default', '365i-queue-optimizer' );
		$wp_info['multisite'] = is_multisite() ? __( 'Yes', '365i-queue-optimizer' ) : __( 'No', '365i-queue-optimizer' );
		$wp_info['language'] = get_locale();
		$wp_info['timezone'] = get_option( 'timezone_string' ) ?: get_option( 'gmt_offset' );
		$wp_info['permalink_structure'] = get_option( 'permalink_structure' ) ?: __( 'Plain', '365i-queue-optimizer' );

		// Cron status
		$wp_info['wp_cron'] = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? __( 'Disabled', '365i-queue-optimizer' ) : __( 'Enabled', '365i-queue-optimizer' );

		return $wp_info;
	}

	/**
	 * Get active theme information.
	 *
	 * @return array Theme information.
	 */
	private function get_theme_info() {
		$theme = wp_get_theme();
		$parent_theme = $theme->parent();

		$theme_info = array();
		$theme_info['theme_name'] = $theme->get( 'Name' );
		$theme_info['theme_version'] = $theme->get( 'Version' );
		$theme_info['theme_author'] = $theme->get( 'Author' );
		$theme_info['theme_uri'] = $theme->get( 'ThemeURI' );
		$theme_info['theme_text_domain'] = $theme->get( 'TextDomain' );

		if ( $parent_theme ) {
			$theme_info['parent_theme'] = $parent_theme->get( 'Name' ) . ' (' . $parent_theme->get( 'Version' ) . ')';
			$theme_info['is_child_theme'] = __( 'Yes', '365i-queue-optimizer' );
		} else {
			$theme_info['parent_theme'] = __( 'N/A', '365i-queue-optimizer' );
			$theme_info['is_child_theme'] = __( 'No', '365i-queue-optimizer' );
		}

		return $theme_info;
	}

	/**
	 * Get plugins information.
	 *
	 * @return array Plugins information.
	 */
	private function get_plugins_info() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );
		$plugins_info = array();

		foreach ( $all_plugins as $plugin_path => $plugin_data ) {
			$is_active = in_array( $plugin_path, $active_plugins, true );
			
			$plugins_info[] = array(
				'name'        => $plugin_data['Name'],
				'version'     => $plugin_data['Version'],
				'author'      => $plugin_data['Author'],
				'status'      => $is_active ? __( 'Active', '365i-queue-optimizer' ) : __( 'Inactive', '365i-queue-optimizer' ),
				'plugin_uri'  => $plugin_data['PluginURI'],
				'text_domain' => $plugin_data['TextDomain'],
				'path'        => $plugin_path,
				'active'      => $is_active,
			);
		}

		// Sort by active status, then by name
		usort( $plugins_info, function( $a, $b ) {
			if ( $a['active'] !== $b['active'] ) {
				return $b['active'] - $a['active']; // Active first
			}
			return strcmp( $a['name'], $b['name'] );
		} );

		return $plugins_info;
	}

	/**
	 * Get PHP extensions information.
	 *
	 * @return array PHP extensions information.
	 */
	private function get_php_extensions_info() {
		$extensions = get_loaded_extensions();
		sort( $extensions );

		$extension_info = array();
		$important_extensions = array(
			'curl', 'gd', 'imagick', 'mbstring', 'openssl', 'zip', 'json', 
			'mysql', 'mysqli', 'xml', 'xmlreader', 'zlib', 'fileinfo'
		);

		foreach ( $extensions as $extension ) {
			$version = phpversion( $extension );
			$extension_info[] = array(
				'name'      => $extension,
				'version'   => $version ?: __( 'Unknown', '365i-queue-optimizer' ),
				'important' => in_array( strtolower( $extension ), $important_extensions, true ),
			);
		}

		return $extension_info;
	}

	/**
	 * Get queue system specific information.
	 *
	 * @return array Queue system information.
	 */
	private function get_queue_system_info() {
		$queue_info = array();

		// Plugin version
		$queue_info['queue_optimizer_version'] = QUEUE_OPTIMIZER_VERSION;

		// Action Scheduler information
		if ( class_exists( 'ActionScheduler_Versions' ) ) {
			$queue_info['action_scheduler_version'] = ActionScheduler_Versions::instance()->latest_version();
			$queue_info['action_scheduler_available'] = __( 'Yes', '365i-queue-optimizer' );
		} else {
			$queue_info['action_scheduler_version'] = __( 'Not Available', '365i-queue-optimizer' );
			$queue_info['action_scheduler_available'] = __( 'No', '365i-queue-optimizer' );
		}

		// Queue status
		$scheduler = Queue_Optimizer_Scheduler::get_instance();
		$status = $scheduler->get_queue_status();
		$queue_info['pending_jobs'] = $status['pending'];
		$queue_info['processing_jobs'] = $status['processing'];
		$queue_info['completed_jobs'] = $status['completed'];
		$queue_info['failed_jobs'] = $status['failed'];
		$queue_info['last_run'] = $status['last_run'] ? date( 'Y-m-d H:i:s', $status['last_run'] ) : __( 'Never', '365i-queue-optimizer' );

		// Plugin settings
		$queue_info['logging_enabled'] = get_option( 'queue_optimizer_logging_enabled' ) ? __( 'Yes', '365i-queue-optimizer' ) : __( 'No', '365i-queue-optimizer' );
		$queue_info['log_retention_days'] = get_option( 'queue_optimizer_log_retention_days', 7 );
		$queue_info['time_limit'] = get_option( 'queue_optimizer_time_limit', 30 );
		$queue_info['concurrent_batches'] = get_option( 'queue_optimizer_concurrent_batches', 3 );

		// Log file information
		$upload_dir = wp_upload_dir();
		$log_file = $upload_dir['basedir'] . '/365i-queue-optimizer.log';
		if ( file_exists( $log_file ) ) {
			$queue_info['log_file_size'] = size_format( filesize( $log_file ) );
			$queue_info['log_file_location'] = $log_file;
		} else {
			$queue_info['log_file_size'] = __( 'No log file', '365i-queue-optimizer' );
			$queue_info['log_file_location'] = __( 'N/A', '365i-queue-optimizer' );
		}

		return $queue_info;
	}

	/**
	 * AJAX handler for exporting system info.
	 */
	public function ajax_export_system_info() {
		// Verify nonce and permissions
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'queue_optimizer_system_info_nonce' ) ||
			 ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Security check failed.', '365i-queue-optimizer' ), 403 );
		}

		$format = sanitize_text_field( $_POST['format'] ?? 'json' );
		$system_info = $this->gather_system_info();

		switch ( $format ) {
			case 'csv':
				$this->export_as_csv( $system_info );
				break;
			case 'json':
			default:
				$this->export_as_json( $system_info );
				break;
		}
	}

	/**
	 * Export system info as JSON.
	 *
	 * @param array $system_info System information data.
	 */
	private function export_as_json( $system_info ) {
		$filename = 'system-info-' . date( 'Y-m-d-H-i-s' ) . '.json';
		
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: no-cache, must-revalidate' );
		
		echo wp_json_encode( $system_info, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Export system info as CSV.
	 *
	 * @param array $system_info System information data.
	 */
	private function export_as_csv( $system_info ) {
		$filename = 'system-info-' . date( 'Y-m-d-H-i-s' ) . '.csv';
		
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: no-cache, must-revalidate' );
		
		$output = fopen( 'php://output', 'w' );
		
		// Write CSV headers
		fputcsv( $output, array( 'Category', 'Key', 'Value' ) );
		
		// Write data
		foreach ( $system_info as $category => $data ) {
			if ( 'plugins' === $category ) {
				foreach ( $data as $plugin ) {
					fputcsv( $output, array( ucfirst( $category ), $plugin['name'], $plugin['version'] . ' (' . $plugin['status'] . ')' ) );
				}
			} elseif ( 'php_extensions' === $category ) {
				foreach ( $data as $extension ) {
					fputcsv( $output, array( 'PHP Extensions', $extension['name'], $extension['version'] ) );
				}
			} else {
				foreach ( $data as $key => $value ) {
					fputcsv( $output, array( ucfirst( str_replace( '_', ' ', $category ) ), ucfirst( str_replace( '_', ' ', $key ) ), $value ) );
				}
			}
		}
		
		fclose( $output );
		exit;
	}
}