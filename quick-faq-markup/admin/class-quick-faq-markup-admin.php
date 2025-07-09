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
			'supports'              => array( 'title', 'page-attributes' ),
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
		$question = get_post_meta( $post->ID, '_qfm_faq_question', true );
		$answer   = get_post_meta( $post->ID, '_qfm_faq_answer', true );

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

		// Sanitize and save question
		if ( isset( $_POST['qfm_faq_question'] ) ) {
			$question = sanitize_textarea_field( wp_unslash( $_POST['qfm_faq_question'] ) );
			update_post_meta( $post_id, '_qfm_faq_question', $question );
		}

		// Sanitize and save answer (allow HTML)
		if ( isset( $_POST['qfm_faq_answer'] ) ) {
			$answer = wp_kses_post( wp_unslash( $_POST['qfm_faq_answer'] ) );
			update_post_meta( $post_id, '_qfm_faq_answer', $answer );
		}


		// Log the save action
		quick_faq_markup_log( 
			sprintf( 'FAQ meta data saved for post ID: %d', $post_id ), 
			'info' 
		);
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

		// Add title column first (after checkbox)
		if ( isset( $columns['title'] ) ) {
			$new_columns['title'] = $columns['title'];
		}

		// Add custom columns in new order
		$new_columns['faq_question'] = __( 'Question', 'quick-faq-markup' );
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
			case 'faq_question':
				$question = get_post_meta( $post_id, '_qfm_faq_question', true );
				echo esc_html( wp_trim_words( $question, 10 ) );
				break;

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
				$order = get_post_field( 'menu_order', $post_id );
				printf(
					'<input type="number" class="small-text qfm-order-input" value="%s" data-post-id="%s" min="0" />',
					esc_attr( $order ),
					esc_attr( $post_id )
				);
				break;
		}
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

		// Create dropdown
		echo '<select name="qfm_faq_category" id="qfm_faq_category_filter">';
		echo '<option value="">' . esc_html__( 'All Categories', 'quick-faq-markup' ) . '</option>';

		foreach ( $categories as $category ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $category->slug ),
				selected( $selected, $category->slug, false ),
				esc_html( $category->name )
			);
		}

		echo '</select>';
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
	 * Inject drag handles into title column for horizontal layout.
	 * This method outputs JavaScript to modify the title column after page load.
	 *
	 * @since 1.0.0
	 */
	public function inject_title_drag_handles() {
		global $pagenow, $post_type;
		
		// Only on FAQ list pages
		if ( 'edit.php' !== $pagenow || 'qfm_faq' !== $post_type ) {
			return;
		}
		
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
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
				
				// Create wrapper with drag handle
				var $wrapper = $('<div class="qfm-title-wrapper"></div>');
				var $dragHandle = $('<span class="qfm-drag-handle" title="<?php echo esc_attr__( 'Drag to reorder', 'quick-faq-markup' ); ?>">⋮⋮</span>');
				var $titleContent = $('<div class="qfm-title-content"></div>');
				
				// Move all title cell content to the content wrapper
				$titleContent.append($titleCell.contents());
				
				// Build the new structure
				$wrapper.append($dragHandle);
				$wrapper.append($titleContent);
				
				// Replace title cell content with wrapper
				$titleCell.empty().append($wrapper);
				$titleCell.addClass('qfm-processed');
			});
		});
		</script>
		<?php
	}
}