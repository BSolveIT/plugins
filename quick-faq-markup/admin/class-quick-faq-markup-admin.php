<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package Quick_FAQ_Markup
 * @since 1.0.0
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for admin-specific functionality
 * including custom post type registration, meta boxes, and admin interface.
 *
 * @since 1.0.0
 */
class Quick_FAQ_Markup_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since 1.0.0
	 * @var string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since 1.0.0
	 * @var string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Temporary storage for calculated order during post save.
	 *
	 * @since 2.0.3
	 * @var int|null $temp_order_for_save Temporary order storage.
	 */
	private $temp_order_for_save = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_styles( $hook_suffix ) {
		global $post_type;

		// Only load on FAQ-related pages
		if ( 'qfm_faq' === $post_type || 
			 'qfm_faq_page_quick-faq-markup-settings' === $hook_suffix ||
			 'edit.php' === $hook_suffix && isset( $_GET['post_type'] ) && 'qfm_faq' === $_GET['post_type'] ) {
			
			wp_enqueue_style(
				$this->plugin_name,
				QUICK_FAQ_MARKUP_PLUGIN_URL . 'admin/css/quick-faq-markup-admin.css',
				array(),
				$this->version,
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		global $post_type;

		// Only load on FAQ-related pages
		if ( 'qfm_faq' === $post_type || 
			 'qfm_faq_page_quick-faq-markup-settings' === $hook_suffix ||
			 'edit.php' === $hook_suffix && isset( $_GET['post_type'] ) && 'qfm_faq' === $_GET['post_type'] ) {
			
			wp_enqueue_script(
				$this->plugin_name,
				QUICK_FAQ_MARKUP_PLUGIN_URL . 'admin/js/quick-faq-markup-admin.js',
				array( 'jquery', 'jquery-ui-sortable' ),
				$this->version,
				false
			);

			// Localize script for AJAX
			wp_localize_script(
				$this->plugin_name,
				'qfmAdmin',
				array(
					'ajaxUrl' => esc_url( admin_url( 'admin-ajax.php' ) ),
					'nonce'   => wp_create_nonce( 'qfm_admin_nonce' ),
					'messages' => array(
						'orderUpdated' => esc_js( __( 'Order updated successfully.', 'quick-faq-markup' ) ),
						'orderError'   => esc_js( __( 'Failed to update order.', 'quick-faq-markup' ) ),
						'confirmDelete' => esc_js( __( 'Are you sure you want to delete this FAQ?', 'quick-faq-markup' ) ),
						'processing'   => esc_js( __( 'Processing...', 'quick-faq-markup' ) ),
					),
				)
			);
		}
	}

	/**
	 * Register the FAQ category taxonomy.
	 *
	 * @since 1.0.0
	 */
	public function register_faq_taxonomy() {
		$labels = array(
			'name'                       => _x( 'FAQ Categories', 'Taxonomy General Name', 'quick-faq-markup' ),
			'singular_name'              => _x( 'FAQ Category', 'Taxonomy Singular Name', 'quick-faq-markup' ),
			'menu_name'                  => __( 'FAQ Categories', 'quick-faq-markup' ),
			'all_items'                  => __( 'All FAQ Categories', 'quick-faq-markup' ),
			'parent_item'                => __( 'Parent FAQ Category', 'quick-faq-markup' ),
			'parent_item_colon'          => __( 'Parent FAQ Category:', 'quick-faq-markup' ),
			'new_item_name'              => __( 'New FAQ Category Name', 'quick-faq-markup' ),
			'add_new_item'               => __( 'Add New FAQ Category', 'quick-faq-markup' ),
			'edit_item'                  => __( 'Edit FAQ Category', 'quick-faq-markup' ),
			'update_item'                => __( 'Update FAQ Category', 'quick-faq-markup' ),
			'view_item'                  => __( 'View FAQ Category', 'quick-faq-markup' ),
			'separate_items_with_commas' => __( 'Separate FAQ categories with commas', 'quick-faq-markup' ),
			'add_or_remove_items'        => __( 'Add or remove FAQ categories', 'quick-faq-markup' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'quick-faq-markup' ),
			'popular_items'              => __( 'Popular FAQ Categories', 'quick-faq-markup' ),
			'search_items'               => __( 'Search FAQ Categories', 'quick-faq-markup' ),
			'not_found'                  => __( 'Not Found', 'quick-faq-markup' ),
			'no_terms'                   => __( 'No FAQ categories', 'quick-faq-markup' ),
			'items_list'                 => __( 'FAQ Categories list', 'quick-faq-markup' ),
			'items_list_navigation'      => __( 'FAQ Categories list navigation', 'quick-faq-markup' ),
		);

		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => false,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => false,
			'show_tagcloud'              => false,
			'query_var'                  => true,
			'rewrite'                    => false,
			'capabilities'               => array(
				'manage_terms' => 'edit_posts',
				'edit_terms'   => 'edit_posts',
				'delete_terms' => 'delete_posts',
				'assign_terms' => 'edit_posts',
			),
		);

		register_taxonomy( 'qfm_faq_category', array( 'qfm_faq' ), $args );

		// Log taxonomy registration
		quick_faq_markup_log( 'FAQ Category taxonomy registered successfully', 'info' );
	}

	/**
	 * Register the custom post type for FAQs.
	 *
	 * @since 1.0.0
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'FAQs', 'Post Type General Name', 'quick-faq-markup' ),
			'singular_name'         => _x( 'FAQ', 'Post Type Singular Name', 'quick-faq-markup' ),
			'menu_name'             => __( 'FAQs', 'quick-faq-markup' ),
			'name_admin_bar'        => __( 'FAQ', 'quick-faq-markup' ),
			'archives'              => __( 'FAQ Archives', 'quick-faq-markup' ),
			'attributes'            => __( 'FAQ Attributes', 'quick-faq-markup' ),
			'parent_item_colon'     => __( 'Parent FAQ:', 'quick-faq-markup' ),
			'all_items'             => __( 'All FAQs', 'quick-faq-markup' ),
			'add_new_item'          => __( 'Add New FAQ', 'quick-faq-markup' ),
			'add_new'               => __( 'Add New', 'quick-faq-markup' ),
			'new_item'              => __( 'New FAQ', 'quick-faq-markup' ),
			'edit_item'             => __( 'Edit FAQ', 'quick-faq-markup' ),
			'update_item'           => __( 'Update FAQ', 'quick-faq-markup' ),
			'view_item'             => __( 'View FAQ', 'quick-faq-markup' ),
			'view_items'            => __( 'View FAQs', 'quick-faq-markup' ),
			'search_items'          => __( 'Search FAQs', 'quick-faq-markup' ),
			'not_found'             => __( 'No FAQs found', 'quick-faq-markup' ),
			'not_found_in_trash'    => __( 'No FAQs found in Trash', 'quick-faq-markup' ),
			'featured_image'        => __( 'Featured Image', 'quick-faq-markup' ),
			'set_featured_image'    => __( 'Set featured image', 'quick-faq-markup' ),
			'remove_featured_image' => __( 'Remove featured image', 'quick-faq-markup' ),
			'use_featured_image'    => __( 'Use as featured image', 'quick-faq-markup' ),
			'insert_into_item'      => __( 'Insert into FAQ', 'quick-faq-markup' ),
			'uploaded_to_this_item' => __( 'Uploaded to this FAQ', 'quick-faq-markup' ),
			'items_list'            => __( 'FAQs list', 'quick-faq-markup' ),
			'items_list_navigation' => __( 'FAQs list navigation', 'quick-faq-markup' ),
			'filter_items_list'     => __( 'Filter FAQs list', 'quick-faq-markup' ),
		);

		$args = array(
			'label'                 => __( 'FAQ', 'quick-faq-markup' ),
			'description'           => __( 'Frequently Asked Questions', 'quick-faq-markup' ),
			'labels'                => $labels,
			'supports'              => array( 'title' ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 25,
			'menu_icon'             => 'dashicons-editor-help',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'post',
			'capabilities'          => array(
				'edit_post'          => 'edit_posts',
				'read_post'          => 'read',
				'delete_post'        => 'delete_posts',
				'edit_posts'         => 'edit_posts',
				'edit_others_posts'  => 'edit_others_posts',
				'publish_posts'      => 'publish_posts',
				'read_private_posts' => 'read_private_posts',
			),
			'show_in_rest'          => false,
		);

		register_post_type( 'qfm_faq', $args );
	}

	/**
	 * Add meta boxes for FAQ data.
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'qfm_faq_meta_box',
			__( 'FAQ Content', 'quick-faq-markup' ),
			array( $this, 'display_meta_box' ),
			'qfm_faq',
			'normal',
			'high'
		);
	}

	/**
	 * Display the meta box content.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post The post object.
	 */
	public function display_meta_box( $post ) {
		// Add nonce for security
		wp_nonce_field( 'qfm_faq_meta_box_action', 'qfm_faq_meta_box_nonce' );

		// Get current values
		$answer = get_post_meta( $post->ID, '_qfm_faq_answer', true );

		// Include the meta box template
		include QUICK_FAQ_MARKUP_PLUGIN_DIR . 'admin/partials/faq-meta-box.php';
	}

	/**
	 * Save meta box data.
	 *
	 * @since 1.0.0
	 * @param int $post_id The post ID.
	 */
	public function save_meta_data( $post_id ) {
		// Check if user can edit this post
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Verify nonce
		$nonce_field = $_POST['qfm_faq_meta_box_nonce'] ?? '';
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce_field ) ), 'qfm_faq_meta_box_action' ) ) {
			return;
		}

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Verify post type
		if ( 'qfm_faq' !== get_post_type( $post_id ) ) {
			return;
		}

		// Sanitize and save answer (allow HTML)
		if ( isset( $_POST['qfm_faq_answer'] ) ) {
			$answer = wp_kses_post( wp_unslash( $_POST['qfm_faq_answer'] ) );
			update_post_meta( $post_id, '_qfm_faq_answer', $answer );
		}

		// Handle display order
		$order = isset( $_POST['qfm_faq_order'] ) ? absint( $_POST['qfm_faq_order'] ) : 0;
		
		// Auto-increment order for new posts if order is 0
		if ( 0 === $order ) {
			$order = $this->get_next_faq_order();
		}

		// Store order temporarily for wp_insert_post_data filter
		$this->temp_order_for_save = $order;

		// Handle category-specific orders
		if ( isset( $_POST['qfm_category_order'] ) && is_array( $_POST['qfm_category_order'] ) ) {
			$category_orders = $_POST['qfm_category_order'];
			$category_order_instance = $this->get_category_order_instance();
			
			if ( $category_order_instance ) {
				$updated_categories = array();
				
				foreach ( $category_orders as $category_id => $order_value ) {
					$category_id = absint( $category_id );
					$order_value = absint( $order_value );
					
					// Skip if invalid category ID
					if ( $category_id <= 0 ) {
						continue;
					}
					
					// Validate that the category exists and this FAQ is assigned to it
					$category_term = get_term( $category_id, 'qfm_faq_category' );
					if ( ! $category_term || is_wp_error( $category_term ) ) {
						continue;
					}
					
					// Check if FAQ is assigned to this category
					if ( ! has_term( $category_id, 'qfm_faq_category', $post_id ) ) {
						continue;
					}
					
					// Update category-specific order
					if ( $order_value > 0 ) {
						$result = $category_order_instance->update_faq_order_in_category( $post_id, $category_id, $order_value );
						if ( $result ) {
							$updated_categories[] = $category_id;
						}
					} else {
						// If order is 0 or empty, assign automatic order
						$auto_order = $category_order_instance->get_next_order_in_category( $category_id );
						$result = $category_order_instance->update_faq_order_in_category( $post_id, $category_id, $auto_order );
						if ( $result ) {
							$updated_categories[] = $category_id;
						}
					}
				}
				
				// Recalculate global order if any category orders were updated
				if ( ! empty( $updated_categories ) ) {
					$category_order_instance->recalculate_global_order();
					
					// Log category order updates
					quick_faq_markup_log(
						sprintf( 'Category-specific orders updated for post ID: %d, categories: %s', $post_id, implode( ', ', $updated_categories ) ),
						'info'
					);
				}
			}
		}

		// Log the save action
		quick_faq_markup_log(
			sprintf( 'FAQ meta data saved for post ID: %d, global order: %d', $post_id, $order ),
			'info'
		);
	}

	/**
	 * Filter post data before saving to set menu_order without triggering recursive saves.
	 *
	 * @since 2.0.3
	 * @param array $data    An array of slashed post data.
	 * @param array $postarr An array of sanitized, but otherwise unmodified post data.
	 * @return array Modified post data.
	 */
	public function filter_post_data_for_order( $data, $postarr ) {
		// Only process FAQ posts
		if ( 'qfm_faq' !== $data['post_type'] ) {
			return $data;
		}

		// Enhanced debugging - log post ID and current menu_order
		$post_id = $postarr['ID'] ?? 0;
		$is_new_post = empty( $post_id );
		$current_menu_order = isset( $data['menu_order'] ) ? $data['menu_order'] : 'not set';
		
		// Log the current state for debugging
		quick_faq_markup_log(
			sprintf( 'wp_insert_post_data filter called - Post ID: %d, Is New: %s, Current menu_order: %s, temp_order_for_save: %s, posted order: %s',
				$post_id,
				$is_new_post ? 'YES' : 'NO',
				$current_menu_order,
				var_export( $this->temp_order_for_save, true ),
				var_export( $_POST['qfm_faq_order'] ?? 'not set', true )
			),
			'debug'
		);

		// Check if we should calculate order directly here
		if ( null === $this->temp_order_for_save ) {
			// Handle display order calculation during post creation
			$order = isset( $_POST['qfm_faq_order'] ) ? absint( $_POST['qfm_faq_order'] ) : 0;
			
			// Auto-increment order ONLY for new posts if order is 0
			if ( 0 === $order && $is_new_post ) {
				$order = $this->get_next_faq_order();
				
				// Log auto-increment action
				quick_faq_markup_log(
					sprintf( 'AUTO-INCREMENT TRIGGERED - Post ID: %d, Is New: %s, Calculated Order: %d',
						$post_id,
						$is_new_post ? 'YES' : 'NO',
						$order
					),
					'info'
				);
			} elseif ( 0 === $order && ! $is_new_post ) {
				// For existing posts with no order specified, preserve current order
				$order = isset( $data['menu_order'] ) ? $data['menu_order'] : $current_menu_order;
				
				// Log preservation of existing order
				quick_faq_markup_log(
					sprintf( 'EXISTING POST ORDER PRESERVED - Post ID: %d, Order: %s',
						$post_id,
						$order
					),
					'info'
				);
			}
			
			// Set the menu_order in the post data
			$data['menu_order'] = $order;
			
			// Log the order assignment
			quick_faq_markup_log(
				sprintf( 'FAQ menu_order calculated directly in wp_insert_post_data filter: %d (Post ID: %d)', $order, $post_id ),
				'info'
			);
			
			return $data;
		}

		// Set the menu_order in the post data
		$data['menu_order'] = $this->temp_order_for_save;

		// Clear the temporary order
		$this->temp_order_for_save = null;

		// Log the order assignment
		quick_faq_markup_log(
			sprintf( 'FAQ menu_order set via wp_insert_post_data filter from temp storage: %d (Post ID: %d)', $data['menu_order'], $post_id ),
			'info'
		);

		return $data;
	}

	/**
	 * Get the next auto-increment order number for FAQs.
	 *
	 * @since 2.0.2
	 * @return int Next order number.
	 */
	private function get_next_faq_order() {
		global $wpdb;

		$max_order = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(menu_order) FROM {$wpdb->posts} WHERE post_type = %s AND post_status != %s",
			'qfm_faq',
			'trash'
		) );

		return ( $max_order ? (int) $max_order + 1 : 1 );
	}

	/**
	 * Add custom columns to the FAQ list table.
	 *
	 * @since 1.0.0
	 * @param array $columns Current columns.
	 * @return array Modified columns.
	 */
	public function add_admin_columns( $columns ) {
		$new_columns = array();

		// Add checkbox column
		if ( isset( $columns['cb'] ) ) {
			$new_columns['cb'] = $columns['cb'];
		}

		// Add title column first (after checkbox) - this is now the question
		if ( isset( $columns['title'] ) ) {
			$new_columns['title'] = __( 'Question', 'quick-faq-markup' );
		}

		// Add custom columns in new order
		$new_columns['faq_answer']   = __( 'Answer', 'quick-faq-markup' );
		$new_columns['faq_category'] = __( 'Category', 'quick-faq-markup' );
		$new_columns['faq_order']    = __( 'Order', 'quick-faq-markup' );

		// Add date column
		if ( isset( $columns['date'] ) ) {
			$new_columns['date'] = $columns['date'];
		}

		return $new_columns;
	}

	/**
	 * Populate custom admin columns.
	 *
	 * @since 1.0.0
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 */
	public function populate_admin_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'faq_answer':
				$answer = get_post_meta( $post_id, '_qfm_faq_answer', true );
				echo esc_html( wp_trim_words( wp_strip_all_tags( $answer ), 15 ) );
				break;

			case 'faq_category':
				$terms = get_the_terms( $post_id, 'qfm_faq_category' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$category_names = array();
					foreach ( $terms as $term ) {
						$category_names[] = esc_html( $term->name );
					}
					echo implode( ', ', $category_names );
				} else {
					echo '&mdash;';
				}
				break;

			case 'faq_order':
				$this->display_order_column( $post_id );
				break;
		}
	}

	/**
	 * Display the order column with appropriate order value based on current filter.
	 *
	 * @since 2.1.0
	 * @param int $post_id Post ID.
	 */
	private function display_order_column( $post_id ) {
		// Check if we're filtering by category
		$filtered_category = isset( $_GET['qfm_faq_category'] ) ? sanitize_text_field( wp_unslash( $_GET['qfm_faq_category'] ) ) : '';
		
		if ( ! empty( $filtered_category ) && $this->validate_category_parameter( $filtered_category ) ) {
			// Show category-specific order
			$this->display_category_order_input( $post_id, $filtered_category );
		} else {
			// Show global order (menu_order)
			$this->display_global_order_input( $post_id );
		}
	}

	/**
	 * Display category-specific order input field.
	 *
	 * @since 2.1.0
	 * @param int    $post_id Post ID.
	 * @param string $category_slug Category slug.
	 */
	private function display_category_order_input( $post_id, $category_slug ) {
		$category_order = $this->get_category_order_instance();
		
		if ( ! $category_order ) {
			echo '<span class="qfm-order-error">' . esc_html__( 'N/A', 'quick-faq-markup' ) . '</span>';
			return;
		}

		// Get category term
		$category_term = get_term_by( 'slug', $category_slug, 'qfm_faq_category' );
		if ( ! $category_term ) {
			echo '<span class="qfm-order-error">' . esc_html__( 'Invalid category', 'quick-faq-markup' ) . '</span>';
			return;
		}

		// Get category-specific order
		$category_order_value = $category_order->get_faq_order_in_category( $post_id, $category_term->term_id );
		
		// Debug logging to understand what's happening
		$meta_key = '_qfm_faq_order_' . $category_term->term_id;
		$raw_meta_value = get_post_meta( $post_id, $meta_key, true );
		$global_order = get_post_field( 'menu_order', $post_id );
		
		quick_faq_markup_log(
			sprintf( 'DEBUG: Post %d, Category %s (ID: %d), Expected meta key: %s, Raw meta value: %s, Returned value: %d, Global order: %s',
				$post_id,
				$category_slug,
				$category_term->term_id,
				$meta_key,
				$raw_meta_value ? $raw_meta_value : 'NOT FOUND',
				$category_order_value,
				$global_order
			),
			'debug'
		);
		
		if ( $category_order_value === false || $category_order_value === 0 ) {
			echo '<span class="qfm-order-notice">' . esc_html__( 'Not in category', 'quick-faq-markup' ) . '</span>';
			return;
		}

		printf(
			'<input type="number" class="small-text qfm-category-order-input" value="%s" data-post-id="%s" data-category-id="%s" min="1" title="%s" />',
			esc_attr( $category_order_value ),
			esc_attr( $post_id ),
			esc_attr( $category_term->term_id ),
			esc_attr( sprintf( __( 'Order in %s category', 'quick-faq-markup' ), $category_term->name ) )
		);
	}

	/**
	 * Display global order input field.
	 *
	 * @since 2.1.0
	 * @param int $post_id Post ID.
	 */
	private function display_global_order_input( $post_id ) {
		$order = get_post_field( 'menu_order', $post_id );
		
		printf(
			'<input type="number" class="small-text qfm-order-input" value="%s" data-post-id="%s" min="0" title="%s" />',
			esc_attr( $order ),
			esc_attr( $post_id ),
			esc_attr__( 'Global order across all categories', 'quick-faq-markup' )
		);
	}

	/**
	 * Make admin columns sortable.
	 *
	 * @since 1.0.0
	 * @param array $sortable_columns Current sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function make_columns_sortable( $sortable_columns ) {
		$sortable_columns['faq_order'] = 'menu_order';
		$sortable_columns['faq_category'] = 'qfm_faq_category';
		return $sortable_columns;
	}

	/**
	 * Handle AJAX request for bulk FAQ reordering.
	 *
	 * @since 1.0.0
	 */
	public function handle_ajax_reorder() {
		// Verify nonce
		$nonce = $_POST['nonce'] ?? '';
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'qfm_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'quick-faq-markup' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'quick-faq-markup' ) ) );
		}

		// Get the order data
		$order_data = $_POST['order'] ?? array();
		if ( empty( $order_data ) || ! is_array( $order_data ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order data.', 'quick-faq-markup' ) ) );
		}

		// Update the order
		foreach ( $order_data as $index => $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id > 0 ) {
				wp_update_post( array(
					'ID'         => $post_id,
					'menu_order' => $index + 1,
				) );
			}
		}

		quick_faq_markup_log( 'FAQ order updated via AJAX', 'info' );

		wp_send_json_success( array( 'message' => __( 'Order updated successfully.', 'quick-faq-markup' ) ) );
	}

	/**
	 * Handle AJAX request for single FAQ order update.
	 *
	 * @since 1.0.0
	 */
	public function handle_single_faq_order() {
		// Verify nonce
		$nonce = $_POST['nonce'] ?? '';
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'qfm_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'quick-faq-markup' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'quick-faq-markup' ) ) );
		}

		// Get and validate data
		$post_id = absint( $_POST['post_id'] ?? 0 );
		$order   = absint( $_POST['order'] ?? 0 );

		if ( $post_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'quick-faq-markup' ) ) );
		}

		// Verify post type
		if ( 'qfm_faq' !== get_post_type( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post type.', 'quick-faq-markup' ) ) );
		}

		// Update the order
		wp_update_post( array(
			'ID'         => $post_id,
			'menu_order' => $order,
		) );

		quick_faq_markup_log( 
			sprintf( 'FAQ order updated for post ID: %d to order: %d', $post_id, $order ), 
			'info' 
		);

		wp_send_json_success( array( 'message' => __( 'Order updated successfully.', 'quick-faq-markup' ) ) );
	}

	/**
	 * Handle AJAX request for category-specific FAQ reordering.
	 *
	 * @since 2.1.0
	 */
	public function handle_ajax_category_reorder() {
		// Verify nonce
		$nonce = $_POST['nonce'] ?? '';
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'qfm_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'quick-faq-markup' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'quick-faq-markup' ) ) );
		}

		// Get and validate category parameter
		$category_slug = $_POST['category'] ?? '';
		if ( empty( $category_slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Category parameter is required.', 'quick-faq-markup' ) ) );
		}

		// Validate category exists
		if ( ! $this->validate_category_parameter( $category_slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid category.', 'quick-faq-markup' ) ) );
		}

		// Get the order data
		$order_data = $_POST['order'] ?? array();
		if ( empty( $order_data ) || ! is_array( $order_data ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order data.', 'quick-faq-markup' ) ) );
		}

		// Get category order instance
		$category_order = $this->get_category_order_instance();
		if ( ! $category_order ) {
			wp_send_json_error( array( 'message' => __( 'Category ordering system not available.', 'quick-faq-markup' ) ) );
		}

		// Get category term
		$category_term = get_term_by( 'slug', $category_slug, 'qfm_faq_category' );
		if ( ! $category_term ) {
			wp_send_json_error( array( 'message' => __( 'Category not found.', 'quick-faq-markup' ) ) );
		}

		// Update category-specific order for each FAQ
		$success_count = 0;
		$error_count = 0;

		foreach ( $order_data as $index => $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id <= 0 ) {
				$error_count++;
				continue;
			}

			// Verify post type
			if ( 'qfm_faq' !== get_post_type( $post_id ) ) {
				$error_count++;
				continue;
			}

			// Update category-specific order
			$new_order = $index + 1;
			$result = $category_order->update_faq_order_in_category( $post_id, $category_term->term_id, $new_order );
			
			if ( $result ) {
				$success_count++;
			} else {
				$error_count++;
			}
		}

		// Recalculate global order for all affected FAQs
		$category_order->recalculate_global_order();

		// Log the reorder action
		quick_faq_markup_log(
			sprintf(
				'Category-specific FAQ order updated - Category: %s, Success: %d, Errors: %d',
				$category_slug,
				$success_count,
				$error_count
			),
			'info'
		);

		if ( $error_count > 0 ) {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %1$d: successful updates, %2$d: failed updates */
					__( 'Order partially updated: %1$d successful, %2$d failed.', 'quick-faq-markup' ),
					$success_count,
					$error_count
				)
			) );
		} else {
			wp_send_json_success( array(
				'message' => sprintf(
					/* translators: %d: number of FAQs reordered */
					__( 'Category order updated successfully for %d FAQs.', 'quick-faq-markup' ),
					$success_count
				)
			) );
		}
	}

	/**
	 * Get category order instance from main plugin.
	 *
	 * @since 2.1.0
	 * @return Quick_FAQ_Markup_Category_Order|null Category order instance or null if not available.
	 */
	private function get_category_order_instance() {
		global $quick_faq_markup;
		
		if ( isset( $quick_faq_markup ) && method_exists( $quick_faq_markup, 'get_category_order' ) ) {
			return $quick_faq_markup->get_category_order();
		}
		
		return null;
	}

	/**
	 * Create the plugin settings page.
	 *
	 * @since 1.0.0
	 */
	public function create_settings_page() {
		add_submenu_page(
			'edit.php?post_type=qfm_faq',
			__( 'FAQ Settings', 'quick-faq-markup' ),
			__( 'Settings', 'quick-faq-markup' ),
			'manage_options',
			'quick-faq-markup-settings',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Display the settings page.
	 *
	 * @since 1.0.0
	 */
	public function display_settings_page() {
		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'quick-faq-markup' ) );
		}

		// Include the settings page template
		include QUICK_FAQ_MARKUP_PLUGIN_DIR . 'admin/partials/settings-page.php';
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting(
			'quick_faq_markup_settings',
			'quick_faq_markup_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(
					'default_style'  => 'classic',
					'show_anchors'   => true,
					'enable_schema'  => true,
					'cache_enabled'  => true,
					'cache_duration' => HOUR_IN_SECONDS,
				),
			)
		);
	}

	/**
	 * Sanitize plugin settings.
	 *
	 * @since 1.0.0
	 * @param array $settings Settings to sanitize.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $settings ) {
		$sanitized = array();

		// Sanitize default style
		$valid_styles = array( 'classic', 'accordion-modern', 'accordion-minimal', 'cards' );
		$sanitized['default_style'] = in_array( $settings['default_style'] ?? '', $valid_styles ) ? $settings['default_style'] : 'classic';

		// Sanitize boolean settings
		$sanitized['show_anchors']  = (bool) ( $settings['show_anchors'] ?? true );
		$sanitized['enable_schema'] = (bool) ( $settings['enable_schema'] ?? true );
		$sanitized['cache_enabled'] = (bool) ( $settings['cache_enabled'] ?? true );

		// Sanitize cache duration
		$sanitized['cache_duration'] = absint( $settings['cache_duration'] ?? HOUR_IN_SECONDS );
		$sanitized['cache_duration'] = max( $sanitized['cache_duration'], 300 ); // Minimum 5 minutes

		return $sanitized;
	}

	/**
	 * Get available FAQ categories from taxonomy.
	 *
	 * @since 1.0.0
	 * @return array Available FAQ categories.
	 */
	public function get_faq_categories() {
		$terms = get_terms( array(
			'taxonomy'   => 'qfm_faq_category',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		return ! is_wp_error( $terms ) ? $terms : array();
	}

	/**
	 * Validate category parameter for queries.
	 *
	 * @since 1.0.0
	 * @param string $category Category slug or ID.
	 * @return bool True if valid category.
	 */
	public function validate_category_parameter( $category ) {
		if ( empty( $category ) ) {
			return false;
		}

		// Check if it's a numeric ID
		if ( is_numeric( $category ) ) {
			$term = get_term( (int) $category, 'qfm_faq_category' );
			return $term && ! is_wp_error( $term );
		}

		// Check if it's a valid slug
		$term = get_term_by( 'slug', $category, 'qfm_faq_category' );
		return $term && ! is_wp_error( $term );
	}

	/**
	 * Clear FAQ cache when taxonomy terms are modified.
	 *
	 * @since 1.0.0
	 * @param int    $term_id Term ID.
	 * @param int    $tt_id   Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function clear_faq_cache_on_term_change( $term_id, $tt_id, $taxonomy ) {
		if ( 'qfm_faq_category' === $taxonomy ) {
			wp_cache_delete( 'qfm_categories', 'quick_faq_markup' );
			
			// Clear all FAQ query caches
			wp_cache_flush_group( 'quick_faq_markup' );
			
			quick_faq_markup_log(
				sprintf( 'FAQ cache cleared due to taxonomy term change: %d', $term_id ),
				'info'
			);
		}
	}

	/**
	 * Add category filter dropdown to admin list view.
	 *
	 * @since 1.0.0
	 * @param string $post_type Current post type.
	 */
	public function add_category_filter( $post_type ) {
		// Only add filter for FAQ post type
		if ( 'qfm_faq' !== $post_type ) {
			return;
		}

		// Get current filter value
		$selected = isset( $_GET['qfm_faq_category'] ) ? sanitize_text_field( wp_unslash( $_GET['qfm_faq_category'] ) ) : '';

		// Get all categories
		$categories = get_terms( array(
			'taxonomy'   => 'qfm_faq_category',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		if ( empty( $categories ) || is_wp_error( $categories ) ) {
			return;
		}

		// Create dropdown with enhanced messaging
		echo '<select name="qfm_faq_category" id="qfm_faq_category_filter">';
		
		if ( empty( $selected ) ) {
			echo '<option value="">' . esc_html__( 'Select category to enable ordering', 'quick-faq-markup' ) . '</option>';
		} else {
			echo '<option value="">' . esc_html__( 'All Categories (Global Order)', 'quick-faq-markup' ) . '</option>';
		}

		foreach ( $categories as $category ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $category->slug ),
				selected( $selected, $category->slug, false ),
				esc_html( $category->name )
			);
		}

		echo '</select>';

		// Add ordering status message
		if ( ! empty( $selected ) ) {
			$selected_term = get_term_by( 'slug', $selected, 'qfm_faq_category' );
			if ( $selected_term ) {
				printf(
					'<span class="qfm-filter-status">' . esc_html__( 'Showing category-specific order for: %s', 'quick-faq-markup' ) . '</span>',
					'<strong>' . esc_html( $selected_term->name ) . '</strong>'
				);
			}
		} else {
			echo '<span class="qfm-filter-status qfm-filter-notice">' . esc_html__( 'Showing global order. Select a category to manage category-specific ordering.', 'quick-faq-markup' ) . '</span>';
		}
	}

	/**
	 * Handle category filtering in admin queries.
	 *
	 * @since 1.0.0
	 * @param WP_Query $query Current query.
	 */
	public function filter_faqs_by_category( $query ) {
		// Only process admin queries for FAQ post type
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Check if we're on the FAQ list page
		if ( 'qfm_faq' !== $query->get( 'post_type' ) ) {
			return;
		}

		// Check if category filter is set
		if ( ! isset( $_GET['qfm_faq_category'] ) || empty( $_GET['qfm_faq_category'] ) ) {
			return;
		}

		$category_slug = sanitize_text_field( wp_unslash( $_GET['qfm_faq_category'] ) );

		// Validate category exists
		if ( ! $this->validate_category_parameter( $category_slug ) ) {
			return;
		}

		// Set tax query to filter by category
		$tax_query = array(
			array(
				'taxonomy' => 'qfm_faq_category',
				'field'    => 'slug',
				'terms'    => $category_slug,
			),
		);

		$query->set( 'tax_query', $tax_query );

		// Log the filter action
		quick_faq_markup_log(
			sprintf( 'FAQ admin list filtered by category: %s', $category_slug ),
			'info'
		);
	}

	/**
	 * Set default sorting for FAQ admin list.
	 *
	 * @since 2.0.4
	 * @param WP_Query $query Current query.
	 */
	public function set_default_faq_admin_sorting( $query ) {
		// Only process admin queries for FAQ post type
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Check if we're on the FAQ list page
		if ( 'qfm_faq' !== $query->get( 'post_type' ) ) {
			return;
		}

		// Debug logging to understand current query state
		$current_orderby = $query->get( 'orderby' );
		$current_order = $query->get( 'order' );
		$get_orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'not set';
		$get_order = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'not set';

		quick_faq_markup_log(
			sprintf( 'FAQ admin list query - Current orderby: %s, Current order: %s, GET orderby: %s, GET order: %s',
				var_export( $current_orderby, true ),
				var_export( $current_order, true ),
				$get_orderby,
				$get_order
			),
			'debug'
		);

		// Only set default sorting if no specific orderby is requested
		if ( empty( $current_orderby ) && ! isset( $_GET['orderby'] ) ) {
			// Set default URL parameters to match the sorting behavior
			$_GET['orderby'] = 'menu_order';
			$_GET['order'] = 'asc';
			
			// Also set the query parameters
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );

			quick_faq_markup_log(
				'FAQ admin list default sorting applied: orderby=menu_order, order=ASC (with URL params)',
				'info'
			);
		}
	}

	/**
	 * Inject drag handles into title column for horizontal layout.
	 * This method outputs JavaScript to modify the title column after page load.
	 * Drag handles are only shown when filtering by category.
	 *
	 * @since 1.0.0
	 */
	public function inject_title_drag_handles() {
		global $pagenow, $post_type;
		
		// Only on FAQ list pages
		if ( 'edit.php' !== $pagenow || 'qfm_faq' !== $post_type ) {
			return;
		}

		// Check if we're filtering by category
		$filtered_category = isset( $_GET['qfm_faq_category'] ) ? sanitize_text_field( wp_unslash( $_GET['qfm_faq_category'] ) ) : '';
		$is_category_filtered = ! empty( $filtered_category ) && $this->validate_category_parameter( $filtered_category );
		
		// Get category term if filtering
		$category_term = null;
		if ( $is_category_filtered ) {
			$category_term = get_term_by( 'slug', $filtered_category, 'qfm_faq_category' );
		}
		
		?>
		<script type="text/javascript">
			var isCategoryFiltered = <?php echo $is_category_filtered ? 'true' : 'false'; ?>;
			var currentCategoryId = <?php echo $category_term ? absint( $category_term->term_id ) : 'null'; ?>;
			var currentCategorySlug = <?php echo $category_term ? "'" . esc_js( $category_term->slug ) . "'" : 'null'; ?>;
			
		jQuery(document).ready(function($) {
			// Set category information for JavaScript usage
			window.qfmCurrentCategory = {
				isFiltered: isCategoryFiltered,
				categoryId: currentCategoryId,
				categorySlug: currentCategorySlug
			};
			
			// Inject drag handles into title column for horizontal layout
			$('#the-list tr').each(function() {
				var $row = $(this);
				var $titleCell = $row.find('.column-title');
				
				// Skip if already processed or no title cell
				if ($titleCell.hasClass('qfm-processed') || $titleCell.length === 0) {
					return;
				}
				
				// Find the main title link/text
				var $titleLink = $titleCell.find('.row-title');
				if ($titleLink.length === 0) {
					return;
				}
				
				// Create wrapper and title content
				var $wrapper = $('<div class="qfm-title-wrapper"></div>');
				var $titleContent = $('<div class="qfm-title-content"></div>');
				
				// Move all title cell content to the content wrapper
				$titleContent.append($titleCell.contents());
				
				// Add appropriate control based on filtering state
				if (isCategoryFiltered) {
					// Add drag handle for category filtering
					var $dragHandle = $('<span class="qfm-drag-handle" title="<?php echo esc_attr__( 'Drag to reorder within category', 'quick-faq-markup' ); ?>">⋮⋮</span>');
					$wrapper.append($dragHandle);
					$wrapper.addClass('qfm-draggable');
				} else {
					// Add notice for global view
					var $notice = $('<span class="qfm-order-notice" title="<?php echo esc_attr__( 'Select a category to enable drag-and-drop ordering', 'quick-faq-markup' ); ?>">🔒</span>');
					$wrapper.append($notice);
					$wrapper.addClass('qfm-non-draggable');
				}
				
				// Append title content to wrapper
				$wrapper.append($titleContent);
				
				// Replace title cell content with wrapper
				$titleCell.empty().append($wrapper);
				$titleCell.addClass('qfm-processed');
			});
			
			// Initialize sortable functionality only when category is filtered
			if (isCategoryFiltered) {
				$('#the-list').sortable({
					items: 'tr',
					handle: '.qfm-drag-handle',
					placeholder: 'qfm-sort-placeholder',
					helper: 'clone',
					opacity: 0.7,
					cursor: 'move',
					axis: 'y',
					tolerance: 'pointer',
					update: function(event, ui) {
						var order = [];
						$('#the-list tr').each(function() {
							var postId = $(this).attr('id');
							if (postId && postId.indexOf('post-') === 0) {
								order.push(postId.replace('post-', ''));
							}
						});
						
						// Send AJAX request to update category-specific order
						$.ajax({
							url: qfmAdmin.ajaxUrl,
							type: 'POST',
							data: {
								action: 'qfm_update_faq_category_order',
								nonce: qfmAdmin.nonce,
								order: order,
								category: currentCategorySlug
							},
							beforeSend: function() {
								$('#the-list').addClass('qfm-updating');
							},
							success: function(response) {
								if (response.success) {
									// Show success message
									$('.qfm-filter-status').after('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
								} else {
									// Show error message
									$('.qfm-filter-status').after('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>');
								}
							},
							error: function() {
								// Show error message
								$('.qfm-filter-status').after('<div class="notice notice-error is-dismissible"><p>' + qfmAdmin.messages.orderError + '</p></div>');
							},
							complete: function() {
								$('#the-list').removeClass('qfm-updating');
								// Auto-dismiss notices after 3 seconds
								setTimeout(function() {
									$('.notice.is-dismissible').fadeOut();
								}, 3000);
							}
						});
					}
				});
			}
		});
		</script>
		<?php
	}

	/**
	 * Add category display order field to category add form.
	 *
	 * @since 2.1.0
	 * @param string $taxonomy Current taxonomy slug.
	 */
	public function add_category_order_field_to_add_form( $taxonomy ) {
		// Only add field for FAQ category taxonomy
		if ( 'qfm_faq_category' !== $taxonomy ) {
			return;
		}
		?>
		<div class="form-field term-order-wrap">
			<label for="qfm_category_display_order"><?php esc_html_e( 'Display Order', 'quick-faq-markup' ); ?></label>
			<input 
				type="number" 
				id="qfm_category_display_order" 
				name="qfm_category_display_order" 
				value="" 
				min="0" 
				step="1"
				class="small-text"
			/>
			<p class="description">
				<?php esc_html_e( 'Controls the display order of this category. Lower numbers appear first. Leave empty for automatic ordering.', 'quick-faq-markup' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add category display order field to category edit form.
	 *
	 * @since 2.1.0
	 * @param WP_Term $term Current term object.
	 * @param string  $taxonomy Current taxonomy slug.
	 */
	public function add_category_order_field_to_edit_form( $term, $taxonomy ) {
		// Only add field for FAQ category taxonomy
		if ( 'qfm_faq_category' !== $taxonomy ) {
			return;
		}

		// Get current order value
		$current_order = get_term_meta( $term->term_id, '_qfm_category_display_order', true );
		if ( empty( $current_order ) ) {
			$current_order = '';
		}
		?>
		<tr class="form-field term-order-wrap">
			<th scope="row">
				<label for="qfm_category_display_order"><?php esc_html_e( 'Display Order', 'quick-faq-markup' ); ?></label>
			</th>
			<td>
				<input 
					type="number" 
					id="qfm_category_display_order" 
					name="qfm_category_display_order" 
					value="<?php echo esc_attr( $current_order ); ?>" 
					min="0" 
					step="1"
					class="small-text"
				/>
				<p class="description">
					<?php esc_html_e( 'Controls the display order of this category. Lower numbers appear first. Leave empty for automatic ordering.', 'quick-faq-markup' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save category display order when category is created.
	 *
	 * @since 2.1.0
	 * @param int $term_id Term ID.
	 * @param int $tt_id   Term taxonomy ID.
	 */
	public function save_category_order_on_create( $term_id, $tt_id ) {
		$this->save_category_display_order( $term_id );
	}

	/**
	 * Save category display order when category is updated.
	 *
	 * @since 2.1.0
	 * @param int $term_id Term ID.
	 * @param int $tt_id   Term taxonomy ID.
	 */
	public function save_category_order_on_update( $term_id, $tt_id ) {
		$this->save_category_display_order( $term_id );
	}

	/**
	 * Save category display order meta field.
	 *
	 * @since 2.1.0
	 * @param int $term_id Term ID.
	 */
	private function save_category_display_order( $term_id ) {
		// Check if user can edit terms
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Check if our field was submitted
		if ( ! isset( $_POST['qfm_category_display_order'] ) ) {
			return;
		}

		// Sanitize the input
		$display_order = absint( $_POST['qfm_category_display_order'] );
		
		// Auto-increment order if not provided
		if ( 0 === $display_order ) {
			$display_order = $this->get_next_category_display_order();
		}

		// Update term meta
		$result = update_term_meta( $term_id, '_qfm_category_display_order', $display_order );

		// Log the action
		if ( $result !== false ) {
			quick_faq_markup_log(
				sprintf( 'Category display order updated for term ID: %d, order: %d', $term_id, $display_order ),
				'info'
			);
		}
	}

	/**
	 * Get the next auto-increment display order for categories.
	 *
	 * @since 2.1.0
	 * @return int Next display order number.
	 */
	private function get_next_category_display_order() {
		$terms = get_terms( array(
			'taxonomy'   => 'qfm_faq_category',
			'hide_empty' => false,
			'fields'     => 'ids',
		) );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return 1;
		}

		$max_order = 0;
		foreach ( $terms as $term_id ) {
			$order = get_term_meta( $term_id, '_qfm_category_display_order', true );
			if ( $order && is_numeric( $order ) ) {
				$max_order = max( $max_order, (int) $order );
			}
		}

		return $max_order + 1;
	}

	/**
	 * Handle AJAX request to recalculate all orders.
	 *
	 * @since 2.1.0
	 */
	public function handle_ajax_recalculate_orders() {
		// Verify nonce
		$nonce = $_POST['nonce'] ?? '';
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'qfm_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'quick-faq-markup' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'quick-faq-markup' ) ) );
		}

		// Get category order instance
		$category_order = $this->get_category_order_instance();
		if ( ! $category_order ) {
			wp_send_json_error( array( 'message' => __( 'Category ordering system not available.', 'quick-faq-markup' ) ) );
		}

		// Recalculate all orders
		$result = $category_order->recalculate_global_order();
		
		if ( $result ) {
			// Get statistics
			$stats = $this->get_order_statistics();
			
			quick_faq_markup_log( 'All FAQ orders recalculated successfully', 'info' );
			
			wp_send_json_success( array(
				'message' => __( 'All orders recalculated successfully.', 'quick-faq-markup' ),
				'stats' => $stats,
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to recalculate orders.', 'quick-faq-markup' ) ) );
		}
	}

	/**
	 * Handle AJAX request to validate order integrity.
	 *
	 * @since 2.1.0
	 */
	public function handle_ajax_validate_orders() {
		// Verify nonce
		$nonce = $_POST['nonce'] ?? '';
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'qfm_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'quick-faq-markup' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'quick-faq-markup' ) ) );
		}

		// Get category order instance
		$category_order = $this->get_category_order_instance();
		if ( ! $category_order ) {
			wp_send_json_error( array( 'message' => __( 'Category ordering system not available.', 'quick-faq-markup' ) ) );
		}

		// Validate order integrity
		$validation_report = $this->validate_order_integrity();
		
		quick_faq_markup_log( 'Order integrity validation completed', 'info' );
		
		wp_send_json_success( array(
			'message' => __( 'Order integrity validation completed.', 'quick-faq-markup' ),
			'report' => $validation_report,
		) );
	}

	/**
	 * Handle AJAX request to clear FAQ cache.
	 *
	 * @since 2.1.0
	 */
	public function handle_ajax_clear_cache() {
		// Verify nonce
		$nonce = $_POST['nonce'] ?? '';
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'qfm_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'quick-faq-markup' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'quick-faq-markup' ) ) );
		}

		// Clear specific FAQ caches
		wp_cache_delete( 'qfm_categories', 'quick_faq_markup' );
		wp_cache_flush_group( 'quick_faq_markup' );
		
		// Clear object cache if available
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		
		quick_faq_markup_log( 'FAQ cache cleared successfully', 'info' );
		
		wp_send_json_success( array(
			'message' => __( 'FAQ cache cleared successfully.', 'quick-faq-markup' ),
		) );
	}

	/**
	 * Handle AJAX request to check migration status.
	 *
	 * @since 2.1.0
	 */
	public function handle_ajax_check_migration() {
		// Verify nonce
		$nonce = $_POST['nonce'] ?? '';
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'qfm_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed.', 'quick-faq-markup' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'quick-faq-markup' ) ) );
		}

		// Get category order instance
		$category_order = $this->get_category_order_instance();
		if ( ! $category_order ) {
			wp_send_json_error( array( 'message' => __( 'Category ordering system not available.', 'quick-faq-markup' ) ) );
		}

		// Check migration status
		$migration_status = $this->check_migration_status();
		
		quick_faq_markup_log( 'Migration status checked', 'info' );
		
		wp_send_json_success( array(
			'message' => __( 'Migration status checked.', 'quick-faq-markup' ),
			'status' => $migration_status,
		) );
	}

	/**
	 * Get order statistics for reporting.
	 *
	 * @since 2.1.0
	 * @return array Order statistics.
	 */
	private function get_order_statistics() {
		global $wpdb;

		// Get total FAQs
		$total_faqs = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
			'qfm_faq',
			'publish'
		) );

		// Get FAQs with category orders
		$category_orders = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			 WHERE p.post_type = %s AND p.post_status = %s AND pm.meta_key LIKE %s",
			'qfm_faq',
			'publish',
			'_faq_order_%'
		) );

		// Get categories
		$total_categories = wp_count_terms( 'qfm_faq_category' );

		return array(
			'total_faqs' => (int) $total_faqs,
			'faqs_with_category_orders' => (int) $category_orders,
			'total_categories' => (int) $total_categories,
		);
	}

	/**
	 * Validate order integrity and return report.
	 *
	 * @since 2.1.0
	 * @return array Validation report.
	 */
	private function validate_order_integrity() {
		global $wpdb;

		$report = array(
			'status' => 'success',
			'issues' => array(),
			'warnings' => array(),
			'info' => array(),
		);

		// Check for duplicate orders within categories
		$duplicate_orders = $wpdb->get_results( $wpdb->prepare(
			"SELECT pm.meta_key, pm.meta_value, COUNT(*) as count
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			 WHERE p.post_type = %s AND p.post_status = %s AND pm.meta_key LIKE %s
			 GROUP BY pm.meta_key, pm.meta_value
			 HAVING COUNT(*) > 1",
			'qfm_faq',
			'publish',
			'_faq_order_%'
		) );

		if ( ! empty( $duplicate_orders ) ) {
			$report['issues'][] = sprintf(
				/* translators: %d: number of duplicate orders */
				__( 'Found %d duplicate order values within categories.', 'quick-faq-markup' ),
				count( $duplicate_orders )
			);
			$report['status'] = 'warning';
		}

		// Check for missing menu_order values
		$missing_menu_order = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND (menu_order IS NULL OR menu_order = 0)",
			'qfm_faq',
			'publish'
		) );

		if ( $missing_menu_order > 0 ) {
			$report['warnings'][] = sprintf(
				/* translators: %d: number of FAQs with missing menu_order */
				__( '%d FAQs have missing or zero menu_order values.', 'quick-faq-markup' ),
				$missing_menu_order
			);
		}

		// Check for orphaned category orders
		$orphaned_orders = $wpdb->get_results( $wpdb->prepare(
			"SELECT pm.post_id, pm.meta_key FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			 WHERE p.post_type = %s AND pm.meta_key LIKE %s",
			'qfm_faq',
			'_faq_order_%'
		) );

		$orphaned_count = 0;
		foreach ( $orphaned_orders as $order ) {
			$category_id = str_replace( '_faq_order_', '', $order->meta_key );
			if ( ! get_term( $category_id, 'qfm_faq_category' ) ) {
				$orphaned_count++;
			}
		}

		if ( $orphaned_count > 0 ) {
			$report['warnings'][] = sprintf(
				/* translators: %d: number of orphaned orders */
				__( '%d orphaned category orders found (categories no longer exist).', 'quick-faq-markup' ),
				$orphaned_count
			);
		}

		// Add general info
		$stats = $this->get_order_statistics();
		$report['info'][] = sprintf(
			/* translators: %d: total FAQs */
			__( 'Total FAQs: %d', 'quick-faq-markup' ),
			$stats['total_faqs']
		);
		$report['info'][] = sprintf(
			/* translators: %d: FAQs with category orders */
			__( 'FAQs with category orders: %d', 'quick-faq-markup' ),
			$stats['faqs_with_category_orders']
		);
		$report['info'][] = sprintf(
			/* translators: %d: total categories */
			__( 'Total categories: %d', 'quick-faq-markup' ),
			$stats['total_categories']
		);

		return $report;
	}

	/**
	 * Check migration status.
	 *
	 * @since 2.1.0
	 * @return array Migration status report.
	 */
	private function check_migration_status() {
		global $wpdb;

		$status = array(
			'migrated' => false,
			'legacy_orders' => 0,
			'category_orders' => 0,
			'needs_migration' => false,
		);

		// Check for legacy _faq_order meta
		$legacy_orders = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			 WHERE p.post_type = %s AND pm.meta_key = %s",
			'qfm_faq',
			'_faq_order'
		) );

		// Check for new category-specific orders
		$category_orders = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			 WHERE p.post_type = %s AND pm.meta_key LIKE %s",
			'qfm_faq',
			'_faq_order_%'
		) );

		$status['legacy_orders'] = (int) $legacy_orders;
		$status['category_orders'] = (int) $category_orders;
		$status['needs_migration'] = $legacy_orders > 0 && $category_orders === 0;
		$status['migrated'] = $legacy_orders === 0 && $category_orders > 0;

		return $status;
	}
}