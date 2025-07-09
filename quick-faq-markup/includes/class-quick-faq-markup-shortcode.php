<?php
/**
 * The shortcode functionality of the plugin.
 *
 * Defines and handles the [qfm_faq] shortcode with various
 * parameters for displaying FAQs.
 *
 * @package Quick_FAQ_Markup
 * @since 1.0.0
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * The shortcode functionality of the plugin.
 *
 * Defines and handles the [qfm_faq] shortcode registration
 * and processing.
 *
 * @since 1.0.0
 */
class Quick_FAQ_Markup_Shortcode {

	/**
	 * The frontend instance for FAQ display.
	 *
	 * @since 1.0.0
	 * @var Quick_FAQ_Markup_Frontend $frontend The frontend instance.
	 */
	private $frontend;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param Quick_FAQ_Markup_Frontend $frontend The frontend instance.
	 */
	public function __construct( $frontend ) {
		$this->frontend = $frontend;
	}

	/**
	 * Register the shortcode with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function register_shortcode() {
		add_shortcode( 'qfm_faq', array( $this, 'shortcode_handler' ) );
	}

	/**
	 * Handle the [qfm_faq] shortcode.
	 *
	 * @since 1.0.0
	 * @param array|string $atts Shortcode attributes.
	 * @return string Rendered FAQ output.
	 */
	public function shortcode_handler( $atts ) {
		// Parse and validate attributes
		$args = $this->parse_attributes( $atts );

		// Get FAQ output from frontend class
		$output = $this->frontend->display_faqs( $args );

		/**
		 * Filter the shortcode output.
		 *
		 * @since 1.0.0
		 * @param string $output The rendered shortcode output.
		 * @param array $args The parsed shortcode arguments.
		 * @param array|string $atts The original shortcode attributes.
		 */
		return apply_filters( 'qfm_shortcode_output', $output, $args, $atts );
	}

	/**
	 * Parse and validate shortcode attributes.
	 *
	 * @since 1.0.0
	 * @param array|string $atts Raw shortcode attributes.
	 * @return array Parsed and sanitized attributes.
	 */
	public function parse_attributes( $atts ) {
		// Get plugin settings for defaults
		$plugin_settings = get_option( 'quick_faq_markup_settings', array() );
		
		$defaults = array(
			'style'        => isset( $plugin_settings['default_style'] ) ? $plugin_settings['default_style'] : 'classic',
			'category'     => '',
			'limit'        => -1,
			'order'        => 'ASC',
			'ids'          => '',
			'show_anchors' => isset( $plugin_settings['show_anchors'] ) ? $plugin_settings['show_anchors'] : true,
			'show_search'  => false,
		);

		// Parse attributes
		$parsed_atts = shortcode_atts( $defaults, $atts, 'qfm_faq' );

		// Validate and sanitize
		$args = $this->validate_shortcode_attributes( $parsed_atts );

		/**
		 * Filter the parsed shortcode attributes.
		 *
		 * @since 1.0.0
		 * @param array $args The parsed and validated attributes.
		 * @param array $parsed_atts The raw parsed attributes.
		 * @param array|string $atts The original shortcode attributes.
		 */
		return apply_filters( 'qfm_shortcode_attributes', $args, $parsed_atts, $atts );
	}

	/**
	 * Validate and sanitize shortcode attributes.
	 *
	 * @since 1.0.0
	 * @param array $atts Raw parsed attributes.
	 * @return array Validated and sanitized attributes.
	 */
	private function validate_shortcode_attributes( $atts ) {
		$available_styles = $this->frontend->get_available_styles();

		$validated = array();

		// Validate style
		$style = sanitize_text_field( $atts['style'] );
		$validated['style'] = array_key_exists( $style, $available_styles ) ? $style : 'classic';

		// Validate category
		$validated['category'] = sanitize_text_field( $atts['category'] );

		// Validate limit
		$limit = intval( $atts['limit'] );
		$validated['limit'] = ( $limit > 0 ) ? $limit : -1;

		// Validate order
		$order = strtoupper( sanitize_text_field( $atts['order'] ) );
		$validated['order'] = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';

		// Validate IDs
		$ids = sanitize_text_field( $atts['ids'] );
		if ( ! empty( $ids ) ) {
			// Extract and validate numeric IDs
			$id_array = array_map( 'intval', explode( ',', $ids ) );
			$id_array = array_filter( $id_array, function( $id ) {
				return $id > 0;
			} );
			$validated['ids'] = implode( ',', $id_array );
		} else {
			$validated['ids'] = '';
		}

		// Validate boolean options
		$validated['show_anchors'] = $this->parse_boolean_attribute( $atts['show_anchors'] );
		$validated['show_search'] = $this->parse_boolean_attribute( $atts['show_search'] );

		return $validated;
	}

	/**
	 * Parse boolean shortcode attribute.
	 *
	 * @since 1.0.0
	 * @param mixed $value Attribute value to parse.
	 * @return bool Parsed boolean value.
	 */
	private function parse_boolean_attribute( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );
			
			// True values
			if ( in_array( $value, array( 'true', '1', 'yes', 'on' ), true ) ) {
				return true;
			}
			
			// False values
			if ( in_array( $value, array( 'false', '0', 'no', 'off', '' ), true ) ) {
				return false;
			}
		}

		// Default to true for any other value
		return true;
	}

	/**
	 * Get FAQ output with specified parameters.
	 *
	 * @since 1.0.0
	 * @param string $style Display style.
	 * @param string $category FAQ category filter.
	 * @param int    $limit Number of FAQs to show.
	 * @param string $order Sort order (ASC/DESC).
	 * @param string $ids Comma-separated FAQ IDs.
	 * @param bool   $show_anchors Whether to show anchor links.
	 * @param bool   $show_search Whether to show search box.
	 * @return string Rendered FAQ output.
	 */
	public function get_faq_output( $style = 'classic', $category = '', $limit = -1, $order = 'ASC', $ids = '', $show_anchors = true, $show_search = false ) {
		$args = array(
			'style'        => $style,
			'category'     => $category,
			'limit'        => $limit,
			'order'        => $order,
			'ids'          => $ids,
			'show_anchors' => $show_anchors,
			'show_search'  => $show_search,
		);

		// Validate arguments
		$args = $this->validate_shortcode_attributes( $args );

		return $this->frontend->display_faqs( $args );
	}

	/**
	 * Generate shortcode documentation.
	 *
	 * @since 1.0.0
	 * @return string Shortcode documentation HTML.
	 */
	public function get_shortcode_documentation() {
		$available_styles = $this->frontend->get_available_styles();
		
		$doc = '<div class="qfm-shortcode-docs">';
		$doc .= '<h3>' . esc_html__( 'FAQ Shortcode Usage', 'quick-faq-markup' ) . '</h3>';
		
		$doc .= '<h4>' . esc_html__( 'Basic Usage', 'quick-faq-markup' ) . '</h4>';
		$doc .= '<code>[qfm_faq]</code><br><br>';
		
		$doc .= '<h4>' . esc_html__( 'Available Parameters', 'quick-faq-markup' ) . '</h4>';
		$doc .= '<table class="qfm-params-table">';
		
		// Style parameter
		$doc .= '<tr>';
		$doc .= '<td><strong>style</strong></td>';
		$doc .= '<td>' . esc_html__( 'Display style for FAQs', 'quick-faq-markup' ) . '</td>';
		$doc .= '<td>' . esc_html( implode( ', ', array_keys( $available_styles ) ) ) . '</td>';
		$doc .= '<td>classic</td>';
		$doc .= '</tr>';
		
		// Category parameter
		$doc .= '<tr>';
		$doc .= '<td><strong>category</strong></td>';
		$doc .= '<td>' . esc_html__( 'Filter FAQs by category', 'quick-faq-markup' ) . '</td>';
		$doc .= '<td>' . esc_html__( 'Any category name', 'quick-faq-markup' ) . '</td>';
		$doc .= '<td>' . esc_html__( '(all)', 'quick-faq-markup' ) . '</td>';
		$doc .= '</tr>';
		
		// Limit parameter
		$doc .= '<tr>';
		$doc .= '<td><strong>limit</strong></td>';
		$doc .= '<td>' . esc_html__( 'Number of FAQs to display', 'quick-faq-markup' ) . '</td>';
		$doc .= '<td>' . esc_html__( 'Any positive number', 'quick-faq-markup' ) . '</td>';
		$doc .= '<td>-1 (' . esc_html__( 'all', 'quick-faq-markup' ) . ')</td>';
		$doc .= '</tr>';
		
		// Order parameter
		$doc .= '<tr>';
		$doc .= '<td><strong>order</strong></td>';
		$doc .= '<td>' . esc_html__( 'Sort order', 'quick-faq-markup' ) . '</td>';
		$doc .= '<td>ASC, DESC</td>';
		$doc .= '<td>ASC</td>';
		$doc .= '</tr>';
		
		// IDs parameter
		$doc .= '<tr>';
		$doc .= '<td><strong>ids</strong></td>';
		$doc .= '<td>' . esc_html__( 'Specific FAQ IDs to display', 'quick-faq-markup' ) . '</td>';
		$doc .= '<td>' . esc_html__( 'Comma-separated IDs', 'quick-faq-markup' ) . '</td>';
		$doc .= '<td>' . esc_html__( '(all)', 'quick-faq-markup' ) . '</td>';
		$doc .= '</tr>';
		
		// Show anchors parameter
		$doc .= '<tr>';
		$doc .= '<td><strong>show_anchors</strong></td>';
		$doc .= '<td>' . esc_html__( 'Show anchor links for direct linking', 'quick-faq-markup' ) . '</td>';
		$doc .= '<td>true, false</td>';
		$doc .= '<td>true</td>';
		$doc .= '</tr>';
		
		// Show search parameter
		$doc .= '<tr>';
		$doc .= '<td><strong>show_search</strong></td>';
		$doc .= '<td>' . esc_html__( 'Show search box', 'quick-faq-markup' ) . '</td>';
		$doc .= '<td>true, false</td>';
		$doc .= '<td>false</td>';
		$doc .= '</tr>';
		
		$doc .= '</table>';
		
		$doc .= '<h4>' . esc_html__( 'Examples', 'quick-faq-markup' ) . '</h4>';
		$doc .= '<p><code>[qfm_faq style="accordion-modern"]</code></p>';
		$doc .= '<p><code>[qfm_faq category="support" limit="5"]</code></p>';
		$doc .= '<p><code>[qfm_faq style="card-layout" show_search="true"]</code></p>';
		$doc .= '<p><code>[qfm_faq ids="1,5,10" show_anchors="false"]</code></p>';
		
		$doc .= '</div>';
		
		return $doc;
	}

	/**
	 * Render shortcode in Gutenberg editor.
	 *
	 * @since 1.0.0
	 * @param array $attributes Block attributes.
	 * @return string Rendered output for editor.
	 */
	public function render_for_editor( $attributes ) {
		// Convert block attributes to shortcode format
		$shortcode_atts = array();
		
		foreach ( $attributes as $key => $value ) {
			if ( ! empty( $value ) ) {
				$shortcode_atts[] = $key . '="' . esc_attr( $value ) . '"';
			}
		}
		
		$shortcode = '[qfm_faq' . ( ! empty( $shortcode_atts ) ? ' ' . implode( ' ', $shortcode_atts ) : '' ) . ']';
		
		// Add editor wrapper for preview
		$output = '<div class="qfm-editor-preview">';
		$output .= '<div class="qfm-editor-label">' . esc_html__( 'FAQ Display Preview:', 'quick-faq-markup' ) . '</div>';
		$output .= do_shortcode( $shortcode );
		$output .= '</div>';
		
		return $output;
	}

	/**
	 * Get available FAQ categories for shortcode builder.
	 *
	 * @since 1.0.0
	 * @return array Available categories.
	 */
	public function get_available_categories() {
		$categories = wp_cache_get( 'qfm_categories', 'quick_faq_markup' );

		if ( false === $categories ) {
			$terms = get_terms( array(
				'taxonomy'   => 'qfm_faq_category',
				'hide_empty' => true,
				'fields'     => 'names',
				'orderby'    => 'name',
				'order'      => 'ASC',
			) );

			$categories = ! is_wp_error( $terms ) ? $terms : array();

			wp_cache_set( 'qfm_categories', $categories, 'quick_faq_markup', HOUR_IN_SECONDS );
		}

		/**
		 * Filter available FAQ categories.
		 *
		 * @since 1.0.0
		 * @param array $categories Available categories.
		 */
		return apply_filters( 'qfm_available_categories', $categories );
	}

	/**
	 * Validate FAQ exists by ID.
	 *
	 * @since 1.0.0
	 * @param int $faq_id FAQ post ID.
	 * @return bool True if FAQ exists and is published.
	 */
	public function validate_faq_exists( $faq_id ) {
		$post = get_post( $faq_id );
		
		return $post && 
			   $post->post_type === 'qfm_faq' && 
			   $post->post_status === 'publish' &&
			   ! empty( get_post_meta( $faq_id, '_qfm_faq_question', true ) ) &&
			   ! empty( get_post_meta( $faq_id, '_qfm_faq_answer', true ) );
	}
}