<?php
/**
 * Category-specific ordering functionality for the plugin.
 *
 * Handles category-specific FAQ ordering, migration from global ordering,
 * and provides hybrid data storage system.
 *
 * @package Quick_FAQ_Markup
 * @since 2.1.0
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Category-specific ordering functionality.
 *
 * Manages FAQ ordering within categories using meta fields and maintains
 * global ordering for backward compatibility and performance.
 *
 * @since 2.1.0
 */
class Quick_FAQ_Markup_Category_Order {

	/**
	 * Meta key prefix for category-specific ordering.
	 *
	 * @since 2.1.0
	 * @var string $meta_prefix Meta key prefix for category orders.
	 */
	private $meta_prefix = '_qfm_faq_order_';

	/**
	 * Meta key for uncategorized FAQs.
	 *
	 * @since 2.1.0
	 * @var string $uncategorized_meta Meta key for uncategorized FAQ ordering.
	 */
	private $uncategorized_meta = '_qfm_faq_order_uncategorized';

	/**
	 * Term meta key for category display order.
	 *
	 * @since 2.1.0
	 * @var string $category_order_meta Term meta key for category ordering.
	 */
	private $category_order_meta = '_qfm_category_display_order';

	/**
	 * Migration status option key.
	 *
	 * @since 2.1.0
	 * @var string $migration_status_key Option key for migration status.
	 */
	private $migration_status_key = 'qfm_category_order_migration_status';

	/**
	 * Initialize the category ordering system.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		// Hook into plugin initialization - use wp_loaded to ensure taxonomy is registered
		add_action( 'wp_loaded', array( $this, 'maybe_run_migration' ) );
		add_action( 'admin_init', array( $this, 'check_migration_status' ) );
		
		// Hook into FAQ save process
		add_action( 'save_post_qfm_faq', array( $this, 'handle_faq_save' ), 20, 2 );
		
		// Hook into category assignment changes
		add_action( 'set_object_terms', array( $this, 'handle_category_assignment' ), 10, 6 );
		
		// Hook into category term operations
		add_action( 'created_qfm_faq_category', array( $this, 'handle_new_category' ), 10, 2 );
		add_action( 'edited_qfm_faq_category', array( $this, 'handle_category_edit' ), 10, 2 );
		add_action( 'deleted_qfm_faq_category', array( $this, 'handle_category_delete' ), 10, 2 );
	}

	/**
	 * Check if migration has been completed.
	 *
	 * @since 2.1.0
	 * @return bool True if migration is complete, false otherwise.
	 */
	public function is_migration_complete() {
		return 'complete' === get_option( $this->migration_status_key, 'pending' );
	}

	/**
	 * Maybe run migration if needed.
	 *
	 * @since 2.1.0
	 */
	public function maybe_run_migration() {
		if ( ! $this->is_migration_complete() ) {
			$this->run_migration();
		}
	}

	/**
	 * Check migration status and show admin notices if needed.
	 *
	 * @since 2.1.0
	 */
	public function check_migration_status() {
		// Check for migration reset parameter
		if ( isset( $_GET['qfm_reset_migration'] ) && current_user_can( 'manage_options' ) ) {
			$nonce = $_GET['qfm_reset_migration'] ?? '';
			if ( wp_verify_nonce( $nonce, 'qfm_reset_migration' ) ) {
				$this->reset_migration();
				
				// Redirect to remove the parameter
				$redirect_url = remove_query_arg( 'qfm_reset_migration' );
				wp_redirect( $redirect_url );
				exit;
			}
		}
		
		$status = get_option( $this->migration_status_key, 'pending' );
		
		if ( 'error' === $status ) {
			add_action( 'admin_notices', array( $this, 'show_migration_error_notice' ) );
		} elseif ( 'complete' === $status ) {
			$migration_time = get_option( $this->migration_status_key . '_time' );
			$notice_dismissed = get_option( $this->migration_status_key . '_notice_dismissed', false );
			
			// Show notice for 24 hours after migration and only if not dismissed
			if ( $migration_time && ( time() - $migration_time ) < DAY_IN_SECONDS && ! $notice_dismissed ) {
				add_action( 'admin_notices', array( $this, 'show_migration_success_notice' ) );
			}
		}
	}

	/**
	 * Run the migration from global ordering to category-specific ordering.
	 *
	 * @since 2.1.0
	 * @return bool True on success, false on failure.
	 */
	public function run_migration() {
		global $wpdb;

		quick_faq_markup_log( 'Starting category-specific ordering migration', 'info' );

		// Set migration status to in progress
		update_option( $this->migration_status_key, 'in_progress' );

		try {
			// Clear existing category-specific orders first
			$this->clear_existing_category_orders();

			// Get all FAQ posts ordered by their current menu_order
			$faqs = get_posts( array(
				'post_type'      => 'qfm_faq',
				'post_status'    => array( 'publish', 'draft', 'private', 'future' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			) );

			$migrated_count = 0;
			$error_count = 0;
			$category_counters = array(); // Track order counters for each category

			foreach ( $faqs as $faq_id ) {
				if ( $this->migrate_single_faq( $faq_id, $category_counters ) ) {
					$migrated_count++;
				} else {
					$error_count++;
				}
			}

			// Initialize category display orders
			$this->initialize_category_display_orders();

			// Recalculate global orders based on new sequential system
			$this->recalculate_global_order();

			// Set migration as complete
			update_option( $this->migration_status_key, 'complete' );
			update_option( $this->migration_status_key . '_time', time() );
			update_option( $this->migration_status_key . '_stats', array(
				'migrated' => $migrated_count,
				'errors'   => $error_count,
				'total'    => count( $faqs ),
			) );

			quick_faq_markup_log(
				sprintf( 'Migration completed successfully. Migrated: %d, Errors: %d, Total: %d',
					$migrated_count,
					$error_count,
					count( $faqs )
				),
				'info'
			);

			return true;

		} catch ( Exception $e ) {
			// Set migration status to error
			update_option( $this->migration_status_key, 'error' );
			update_option( $this->migration_status_key . '_error', $e->getMessage() );

			quick_faq_markup_log(
				sprintf( 'Migration failed with error: %s', $e->getMessage() ),
				'error'
			);

			return false;
		}
	}

	/**
	 * Migrate a single FAQ from global to category-specific ordering.
	 *
	 * @since 2.1.0
	 * @param int $faq_id FAQ post ID.
	 * @param array $category_counters Reference to category order counters.
	 * @return bool True on success, false on failure.
	 */
	private function migrate_single_faq( $faq_id, &$category_counters ) {
		try {
			// Get FAQ categories
			$categories = wp_get_post_terms( $faq_id, 'qfm_faq_category', array( 'fields' => 'ids' ) );

			if ( is_wp_error( $categories ) ) {
				quick_faq_markup_log(
					sprintf( 'Error getting categories for FAQ %d: %s', $faq_id, $categories->get_error_message() ),
					'error'
				);
				return false;
			}

			// Clear ALL existing category-specific meta fields for this FAQ first
			$this->clear_faq_category_orders( $faq_id );

			if ( ! empty( $categories ) ) {
				// FAQ has categories - create sequential category-specific orders
				foreach ( $categories as $category_id ) {
					// Initialize counter for this category if not exists
					if ( ! isset( $category_counters[ $category_id ] ) ) {
						$category_counters[ $category_id ] = 1;
					}
					
					// Assign sequential order in this category
					$category_order = $category_counters[ $category_id ];
					update_post_meta( $faq_id, $this->meta_prefix . $category_id, $category_order );
					
					quick_faq_markup_log(
						sprintf( 'MIGRATION: FAQ %d -> Category %d -> Order %d', $faq_id, $category_id, $category_order ),
						'info'
					);
					
					// Increment counter for next FAQ in this category
					$category_counters[ $category_id ]++;
				}
			} else {
				// FAQ has no categories - use uncategorized meta with sequential numbering
				if ( ! isset( $category_counters['uncategorized'] ) ) {
					$category_counters['uncategorized'] = 1;
				}
				
				$uncategorized_order = $category_counters['uncategorized'];
				update_post_meta( $faq_id, $this->uncategorized_meta, $uncategorized_order );
				
				quick_faq_markup_log(
					sprintf( 'MIGRATION: FAQ %d -> Uncategorized -> Order %d', $faq_id, $uncategorized_order ),
					'info'
				);
				
				$category_counters['uncategorized']++;
			}

			return true;

		} catch ( Exception $e ) {
			quick_faq_markup_log(
				sprintf( 'Error migrating FAQ %d: %s', $faq_id, $e->getMessage() ),
				'error'
			);
			return false;
		}
	}

	/**
	 * Clear existing category-specific order meta fields.
	 *
	 * @since 2.1.0
	 */
	private function clear_existing_category_orders() {
		global $wpdb;

		quick_faq_markup_log( 'Clearing existing category-specific order meta fields', 'info' );

		// Delete all category-specific order meta fields
		$wpdb->delete(
			$wpdb->postmeta,
			array(
				'meta_key' => $this->uncategorized_meta
			),
			array( '%s' )
		);

		// Delete category-specific order meta fields (pattern: _qfm_faq_order_*)
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
			$this->meta_prefix . '%'
		) );

		quick_faq_markup_log( 'Cleared all existing category-specific order meta fields', 'info' );
	}

	/**
	 * Clear category-specific order meta fields for a single FAQ.
	 *
	 * @since 2.1.0
	 * @param int $faq_id FAQ post ID.
	 */
	private function clear_faq_category_orders( $faq_id ) {
		global $wpdb;

		// Delete all category-specific order meta fields for this FAQ
		$wpdb->delete(
			$wpdb->postmeta,
			array(
				'post_id' => $faq_id,
				'meta_key' => $this->uncategorized_meta
			),
			array( '%d', '%s' )
		);

		// Delete category-specific order meta fields (pattern: _qfm_faq_order_*)
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s",
			$faq_id,
			$this->meta_prefix . '%'
		) );

		quick_faq_markup_log(
			sprintf( 'Cleared category-specific order meta fields for FAQ %d', $faq_id ),
			'debug'
		);
	}

	/**
	 * Initialize display orders for all categories.
	 *
	 * @since 2.1.0
	 */
	private function initialize_category_display_orders() {
		$categories = get_terms( array(
			'taxonomy'   => 'qfm_faq_category',
			'hide_empty' => false,
			'fields'     => 'ids',
		) );

		if ( ! is_wp_error( $categories ) ) {
			foreach ( $categories as $index => $category_id ) {
				// Only set if not already set
				if ( ! get_term_meta( $category_id, $this->category_order_meta, true ) ) {
					update_term_meta( $category_id, $this->category_order_meta, ( $index + 1 ) * 100 );
				}
			}
		}
	}

	/**
	 * Get the next available order number in a specific category.
	 *
	 * @since 2.1.0
	 * @param int $category_id Category term ID.
	 * @return int Next available order number.
	 */
	public function get_next_order_in_category( $category_id ) {
		global $wpdb;

		$meta_key = $this->meta_prefix . $category_id;

		$max_order = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(CAST(meta_value AS UNSIGNED)) 
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = %s 
			 AND p.post_type = %s 
			 AND p.post_status != %s",
			$meta_key,
			'qfm_faq',
			'trash'
		) );

		return $max_order ? (int) $max_order + 1 : 1;
	}

	/**
	 * Get the next available order number for uncategorized FAQs.
	 *
	 * @since 2.1.0
	 * @return int Next available order number.
	 */
	public function get_next_uncategorized_order() {
		global $wpdb;

		$max_order = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(CAST(meta_value AS UNSIGNED)) 
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = %s 
			 AND p.post_type = %s 
			 AND p.post_status != %s",
			$this->uncategorized_meta,
			'qfm_faq',
			'trash'
		) );

		return $max_order ? (int) $max_order + 1 : 1;
	}

	/**
	 * Recalculate the global menu_order for all FAQs using sequential numbering.
	 *
	 * @since 2.1.0
	 * @param int $faq_id FAQ post ID (optional, if provided only recalculates for this FAQ).
	 * @return bool True on success, false on failure.
	 */
	public function recalculate_global_order( $faq_id = null ) {
		global $wpdb;

		// If specific FAQ ID provided, just recalculate all orders for consistency
		if ( $faq_id ) {
			quick_faq_markup_log(
				sprintf( 'Recalculating global order for FAQ %d - will recalculate all orders for consistency', $faq_id ),
				'debug'
			);
		}

		// Get all FAQ categories ordered by display order
		$categories = get_terms( array(
			'taxonomy'   => 'qfm_faq_category',
			'hide_empty' => false,
			'meta_key'   => $this->category_order_meta,
			'orderby'    => 'meta_value_num',
			'order'      => 'ASC',
		) );

		if ( is_wp_error( $categories ) ) {
			return false;
		}

		$global_order = 1;

		// Process each category in order
		foreach ( $categories as $category ) {
			// Get FAQs in this category ordered by category-specific order
			$faqs_in_category = get_posts( array(
				'post_type'      => 'qfm_faq',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => $this->meta_prefix . $category->term_id,
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
				'tax_query'      => array(
					array(
						'taxonomy' => 'qfm_faq_category',
						'field'    => 'term_id',
						'terms'    => $category->term_id,
					),
				),
			) );

			// Update menu_order for each FAQ in this category using direct database update
			// to avoid triggering save_post hooks that would cause infinite recursion
			foreach ( $faqs_in_category as $faq_id_in_category ) {
				$wpdb->update(
					$wpdb->posts,
					array( 'menu_order' => $global_order ),
					array( 'ID' => $faq_id_in_category ),
					array( '%d' ),
					array( '%d' )
				);
				$global_order++;
			}
		}

		// Handle uncategorized FAQs
		$uncategorized_faqs = get_posts( array(
			'post_type'      => 'qfm_faq',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => $this->uncategorized_meta,
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'tax_query'      => array(
				array(
					'taxonomy' => 'qfm_faq_category',
					'operator' => 'NOT EXISTS',
				),
			),
		) );

		// Update menu_order for uncategorized FAQs using direct database update
		// to avoid triggering save_post hooks that would cause infinite recursion
		foreach ( $uncategorized_faqs as $uncategorized_faq_id ) {
			$wpdb->update(
				$wpdb->posts,
				array( 'menu_order' => $global_order ),
				array( 'ID' => $uncategorized_faq_id ),
				array( '%d' ),
				array( '%d' )
			);
			$global_order++;
		}

		quick_faq_markup_log(
			sprintf( 'Recalculated global orders for all FAQs - assigned %d sequential orders', $global_order - 1 ),
			'info'
		);

		return true;
	}

	/**
	 * Assign order numbers to a new FAQ in all its categories.
	 *
	 * @since 2.1.0
	 * @param int   $faq_id FAQ post ID.
	 * @param array $category_ids Array of category term IDs.
	 * @return bool True on success, false on failure.
	 */
	public function assign_new_faq_order( $faq_id, $category_ids = array() ) {
		if ( empty( $category_ids ) ) {
			// Get current categories if not provided
			$categories = wp_get_post_terms( $faq_id, 'qfm_faq_category', array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $categories ) ) {
				$category_ids = $categories;
			}
		}

		if ( ! empty( $category_ids ) ) {
			// Assign orders in each category
			foreach ( $category_ids as $category_id ) {
				$next_order = $this->get_next_order_in_category( $category_id );
				update_post_meta( $faq_id, $this->meta_prefix . $category_id, $next_order );
			}
		} else {
			// No categories - assign uncategorized order
			$next_order = $this->get_next_uncategorized_order();
			update_post_meta( $faq_id, $this->uncategorized_meta, $next_order );
		}

		// Recalculate global order
		$this->recalculate_global_order( $faq_id );

		quick_faq_markup_log(
			sprintf( 'Assigned new FAQ order for FAQ %d in %d categories', $faq_id, count( $category_ids ) ),
			'info'
		);

		return true;
	}

	/**
	 * Handle FAQ save event to update ordering.
	 *
	 * @since 2.1.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public function handle_faq_save( $post_id, $post ) {
		// Skip autosaves and revisions
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Check if this is a new post
		$is_new_post = 'auto-draft' === get_post_status( $post_id ) || empty( get_post_meta( $post_id, $this->meta_prefix . 'assigned', true ) );

		if ( $is_new_post ) {
			// New FAQ - assign orders
			$this->assign_new_faq_order( $post_id );
			update_post_meta( $post_id, $this->meta_prefix . 'assigned', '1' );
		} else {
			// Existing FAQ - just recalculate global order
			$this->recalculate_global_order( $post_id );
		}
	}

	/**
	 * Handle category assignment changes.
	 *
	 * @since 2.1.0
	 * @param int    $object_id Object ID.
	 * @param array  $terms Array of term IDs.
	 * @param array  $tt_ids Array of term taxonomy IDs.
	 * @param string $taxonomy Taxonomy slug.
	 * @param bool   $append Whether to append or replace terms.
	 * @param array  $old_tt_ids Array of old term taxonomy IDs.
	 */
	public function handle_category_assignment( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( 'qfm_faq_category' !== $taxonomy || 'qfm_faq' !== get_post_type( $object_id ) ) {
			return;
		}

		// Get term IDs from term taxonomy IDs
		$new_term_ids = array();
		$old_term_ids = array();

		if ( ! empty( $tt_ids ) ) {
			$new_term_ids = $this->get_term_ids_from_tt_ids( $tt_ids );
		}

		if ( ! empty( $old_tt_ids ) ) {
			$old_term_ids = $this->get_term_ids_from_tt_ids( $old_tt_ids );
		}

		// Handle added categories
		$added_categories = array_diff( $new_term_ids, $old_term_ids );
		foreach ( $added_categories as $category_id ) {
			$next_order = $this->get_next_order_in_category( $category_id );
			update_post_meta( $object_id, $this->meta_prefix . $category_id, $next_order );
		}

		// Handle removed categories
		$removed_categories = array_diff( $old_term_ids, $new_term_ids );
		foreach ( $removed_categories as $category_id ) {
			delete_post_meta( $object_id, $this->meta_prefix . $category_id );
		}

		// Handle uncategorized status
		if ( empty( $new_term_ids ) && ! empty( $old_term_ids ) ) {
			// FAQ became uncategorized
			$next_order = $this->get_next_uncategorized_order();
			update_post_meta( $object_id, $this->uncategorized_meta, $next_order );
		} elseif ( ! empty( $new_term_ids ) && empty( $old_term_ids ) ) {
			// FAQ was uncategorized and now has categories
			delete_post_meta( $object_id, $this->uncategorized_meta );
		}

		// Recalculate global order
		$this->recalculate_global_order( $object_id );

		quick_faq_markup_log(
			sprintf( 'Updated category assignments for FAQ %d. Added: %d, Removed: %d',
				$object_id,
				count( $added_categories ),
				count( $removed_categories )
			),
			'info'
		);
	}

	/**
	 * Convert term taxonomy IDs to term IDs.
	 *
	 * @since 2.1.0
	 * @param array $tt_ids Array of term taxonomy IDs.
	 * @return array Array of term IDs.
	 */
	private function get_term_ids_from_tt_ids( $tt_ids ) {
		global $wpdb;

		if ( empty( $tt_ids ) ) {
			return array();
		}

		$tt_ids_string = implode( ',', array_map( 'intval', $tt_ids ) );

		$term_ids = $wpdb->get_col(
			"SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id IN ({$tt_ids_string})"
		);

		return array_map( 'intval', $term_ids );
	}

	/**
	 * Handle new category creation.
	 *
	 * @since 2.1.0
	 * @param int $term_id Term ID.
	 * @param int $tt_id Term taxonomy ID.
	 */
	public function handle_new_category( $term_id, $tt_id ) {
		// Set default display order for new category
		$max_order = $this->get_max_category_display_order();
		update_term_meta( $term_id, $this->category_order_meta, $max_order + 100 );

		quick_faq_markup_log(
			sprintf( 'Set display order for new category %d: %d', $term_id, $max_order + 100 ),
			'info'
		);
	}

	/**
	 * Handle category edit.
	 *
	 * @since 2.1.0
	 * @param int $term_id Term ID.
	 * @param int $tt_id Term taxonomy ID.
	 */
	public function handle_category_edit( $term_id, $tt_id ) {
		// Recalculate global orders for all FAQs in this category if display order changed
		$this->recalculate_category_global_orders( $term_id );
	}

	/**
	 * Handle category deletion.
	 *
	 * @since 2.1.0
	 * @param int $term_id Term ID.
	 * @param int $tt_id Term taxonomy ID.
	 */
	public function handle_category_delete( $term_id, $tt_id ) {
		global $wpdb;

		// Remove category-specific order meta for all FAQs
		$meta_key = $this->meta_prefix . $term_id;

		$wpdb->delete(
			$wpdb->postmeta,
			array( 'meta_key' => $meta_key ),
			array( '%s' )
		);

		quick_faq_markup_log(
			sprintf( 'Cleaned up order meta for deleted category %d', $term_id ),
			'info'
		);
	}

	/**
	 * Get the maximum category display order.
	 *
	 * @since 2.1.0
	 * @return int Maximum display order.
	 */
	private function get_max_category_display_order() {
		global $wpdb;

		$max_order = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(CAST(meta_value AS UNSIGNED)) 
			 FROM {$wpdb->termmeta} 
			 WHERE meta_key = %s",
			$this->category_order_meta
		) );

		return $max_order ? (int) $max_order : 0;
	}

	/**
	 * Recalculate global orders for all FAQs in a category.
	 *
	 * @since 2.1.0
	 * @param int $category_id Category term ID.
	 */
	private function recalculate_category_global_orders( $category_id ) {
		// Get all FAQs in this category
		$faqs = get_posts( array(
			'post_type'      => 'qfm_faq',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => 'qfm_faq_category',
					'field'    => 'term_id',
					'terms'    => $category_id,
				),
			),
		) );

		foreach ( $faqs as $faq_id ) {
			$this->recalculate_global_order( $faq_id );
		}
	}

	/**
	 * Show migration error notice.
	 *
	 * @since 2.1.0
	 */
	public function show_migration_error_notice() {
		$error = get_option( $this->migration_status_key . '_error', 'Unknown error' );
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Quick FAQ Markup Migration Error', 'quick-faq-markup' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'Failed to migrate to category-specific ordering:', 'quick-faq-markup' ); ?>
				<code><?php echo esc_html( $error ); ?></code>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=qfm_faq&page=quick-faq-markup-settings' ) ); ?>" class="button">
					<?php esc_html_e( 'Retry Migration', 'quick-faq-markup' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Show migration success notice.
	 *
	 * @since 2.1.0
	 */
	public function show_migration_success_notice() {
		$stats = get_option( $this->migration_status_key . '_stats', array() );
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Quick FAQ Markup Migration Complete', 'quick-faq-markup' ); ?></strong>
			</p>
			<?php if ( ! empty( $stats ) ) : ?>
				<p>
					<?php
					printf(
						/* translators: 1: Number of migrated FAQs, 2: Total number of FAQs */
						esc_html__( 'Successfully migrated %1$d of %2$d FAQs to category-specific ordering.', 'quick-faq-markup' ),
						(int) $stats['migrated'],
						(int) $stats['total']
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get FAQ order in a specific category.
	 *
	 * @since 2.1.0
	 * @param int $faq_id FAQ post ID.
	 * @param int $category_id Category term ID.
	 * @return int Order number, or 0 if not found.
	 */
	public function get_faq_order_in_category( $faq_id, $category_id ) {
		$order = get_post_meta( $faq_id, $this->meta_prefix . $category_id, true );
		return $order ? (int) $order : 0;
	}

	/**
	 * Get all category orders for an FAQ.
	 *
	 * @since 2.1.0
	 * @param int $faq_id FAQ post ID.
	 * @return array Array of category_id => order_number pairs.
	 */
	public function get_faq_category_orders( $faq_id ) {
		$categories = wp_get_post_terms( $faq_id, 'qfm_faq_category', array( 'fields' => 'ids' ) );
		$orders = array();

		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			foreach ( $categories as $category_id ) {
				$order = $this->get_faq_order_in_category( $faq_id, $category_id );
				if ( $order > 0 ) {
					$orders[ $category_id ] = $order;
				}
			}
		} else {
			// Check for uncategorized order
			$uncategorized_order = get_post_meta( $faq_id, $this->uncategorized_meta, true );
			if ( $uncategorized_order ) {
				$orders['uncategorized'] = (int) $uncategorized_order;
			}
		}

		return $orders;
	}

	/**
	 * Update FAQ order in a specific category.
	 *
	 * @since 2.1.0
	 * @param int $faq_id FAQ post ID.
	 * @param int $category_id Category term ID.
	 * @param int $new_order New order number.
	 * @return bool True on success, false on failure.
	 */
	public function update_faq_order_in_category( $faq_id, $category_id, $new_order ) {
		$new_order = (int) $new_order;

		if ( $new_order < 1 ) {
			return false;
		}

		// Update category-specific order
		if ( 'uncategorized' === $category_id ) {
			update_post_meta( $faq_id, $this->uncategorized_meta, $new_order );
		} else {
			update_post_meta( $faq_id, $this->meta_prefix . $category_id, $new_order );
		}

		// Recalculate global order
		$this->recalculate_global_order( $faq_id );

		quick_faq_markup_log(
			sprintf( 'Updated FAQ %d order in category %s to %d', $faq_id, $category_id, $new_order ),
			'info'
		);

		return true;
	}

	/**
	 * Get category display order.
	 *
	 * @since 2.1.0
	 * @param int $category_id Category term ID.
	 * @return int Display order number.
	 */
	public function get_category_display_order( $category_id ) {
		$order = get_term_meta( $category_id, $this->category_order_meta, true );
		return $order ? (int) $order : 1000;
	}

	/**
	 * Update category display order.
	 *
	 * @since 2.1.0
	 * @param int $category_id Category term ID.
	 * @param int $new_order New display order.
	 * @return bool True on success, false on failure.
	 */
	public function update_category_display_order( $category_id, $new_order ) {
		$new_order = (int) $new_order;

		if ( $new_order < 1 ) {
			return false;
		}

		update_term_meta( $category_id, $this->category_order_meta, $new_order );

		// Recalculate global orders for all FAQs in this category
		$this->recalculate_category_global_orders( $category_id );

		quick_faq_markup_log(
			sprintf( 'Updated category %d display order to %d', $category_id, $new_order ),
			'info'
		);

		return true;
	}

	/**
	 * Get meta key for category-specific ordering.
	 *
	 * @since 2.1.0
	 * @param int $category_id Category term ID.
	 * @return string Meta key.
	 */
	public function get_category_order_meta_key( $category_id ) {
		return $this->meta_prefix . $category_id;
	}

	/**
	 * Get meta key for uncategorized ordering.
	 *
	 * @since 2.1.0
	 * @return string Meta key.
	 */
	public function get_uncategorized_order_meta_key() {
		return $this->uncategorized_meta;
	}

	/**
	 * Reset migration status to force re-migration.
	 *
	 * @since 2.1.0
	 * @return bool True on success, false on failure.
	 */
	public function reset_migration() {
		// Delete migration status options
		delete_option( $this->migration_status_key );
		delete_option( $this->migration_status_key . '_time' );
		delete_option( $this->migration_status_key . '_stats' );
		delete_option( $this->migration_status_key . '_error' );
		delete_option( $this->migration_status_key . '_notice_dismissed' );

		// Clear existing category-specific orders
		$this->clear_existing_category_orders();

		quick_faq_markup_log( 'Migration status reset - will re-run on next page load', 'info' );

		return true;
	}

	/**
	 * Handle AJAX request to dismiss migration notice.
	 *
	 * @since 2.1.0
	 */
	public function handle_dismiss_migration_notice() {
		// Verify nonce
		$nonce = $_POST['nonce'] ?? '';
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'qfm_dismiss_migration_notice' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'quick-faq-markup' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'quick-faq-markup' ) ) );
		}

		// Mark migration notice as dismissed
		update_option( $this->migration_status_key . '_notice_dismissed', true );

		quick_faq_markup_log( 'Migration notice dismissed by user', 'info' );

		wp_send_json_success( array( 'message' => __( 'Notice dismissed.', 'quick-faq-markup' ) ) );
	}
}