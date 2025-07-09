<?php
/**
 * The frontend-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for enqueueing
 * the frontend-specific stylesheet and JavaScript. Also handles
 * FAQ display logic and rendering.
 *
 * @package Quick_FAQ_Markup
 * @since 1.0.0
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * The frontend-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for enqueueing
 * the frontend-specific stylesheet and JavaScript.
 *
 * @since 1.0.0
 */
class Quick_FAQ_Markup_Frontend {

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
	 * FAQ data for the current page (for schema generation).
	 *
	 * @since 1.0.0
	 * @var array $page_faqs FAQ data for schema generation.
	 */
	private $page_faqs = array();

	/**
	 * Available display styles.
	 *
	 * @since 1.0.0
	 * @var array $available_styles Available FAQ display styles.
	 */
	private $available_styles = array(
		'classic'           => 'Classic List',
		'accordion-modern'  => 'Accordion Modern',
		'accordion-minimal' => 'Accordion Minimal',
		'card-layout'      => 'Card Layout',
	);

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the frontend.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		// Only enqueue if shortcode is present or FAQ post type is being displayed
		if ( $this->has_faq_shortcode() || is_singular( 'qfm_faq' ) ) {
			wp_enqueue_style(
				$this->plugin_name,
				QUICK_FAQ_MARKUP_PLUGIN_URL . 'public/css/quick-faq-markup-public.css',
				array(),
				$this->version,
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the frontend.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		// Only enqueue if shortcode is present or FAQ post type is being displayed
		if ( $this->has_faq_shortcode() || is_singular( 'qfm_faq' ) ) {
			wp_enqueue_script(
				$this->plugin_name,
				QUICK_FAQ_MARKUP_PLUGIN_URL . 'public/js/quick-faq-markup-public.js',
				array( 'jquery' ),
				$this->version,
				true
			);

			// Localize script with settings
			wp_localize_script(
				$this->plugin_name,
				'qfmPublic',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'qfm_frontend_nonce' ),
					'strings' => array(
						'expand'   => esc_html__( 'Expand', 'quick-faq-markup' ),
						'collapse' => esc_html__( 'Collapse', 'quick-faq-markup' ),
						'loading'  => esc_html__( 'Loading...', 'quick-faq-markup' ),
					),
				)
			);
		}
	}

	/**
	 * Display FAQs with specified parameters.
	 *
	 * @since 1.0.0
	 * @param array $args Display arguments.
	 * @return string Rendered FAQ output.
	 */
	public function display_faqs( $args = array() ) {
		$defaults = array(
			'style'        => 'classic',
			'category'     => '',
			'limit'        => -1,
			'order'        => 'ASC',
			'ids'          => '',
			'show_anchors' => true,
			'show_search'  => false,
		);

		$args = wp_parse_args( $args, $defaults );

		// Sanitize arguments
		$args = $this->sanitize_display_args( $args );

		// Query FAQs
		$faqs = $this->query_faqs( $args );

		if ( empty( $faqs ) ) {
			return '<div class="qfm-no-faqs">' . esc_html__( 'No FAQs found.', 'quick-faq-markup' ) . '</div>';
		}

		// Store FAQs for schema generation
		$this->page_faqs = array_merge( $this->page_faqs, $faqs );

		// Add FAQs to schema class for JSON-LD generation
		$this->add_faqs_to_schema( $faqs );

		// Generate output
		$output = $this->render_faq_output( $faqs, $args );

		/**
		 * Filter the final FAQ output.
		 *
		 * @since 1.0.0
		 * @param string $output The rendered FAQ output.
		 * @param array $faqs The FAQ data array.
		 * @param array $args The display arguments.
		 */
		return apply_filters( 'qfm_faq_output', $output, $faqs, $args );
	}

	/**
	 * Render FAQ output with specified style.
	 *
	 * @since 1.0.0
	 * @param array $faqs FAQ data array.
	 * @param array $args Display arguments.
	 * @return string Rendered FAQ output.
	 */
	public function render_faq_output( $faqs, $args ) {
		$style = $args['style'];
		$show_anchors = $args['show_anchors'];
		$show_search = $args['show_search'];

		// Validate style
		if ( ! array_key_exists( $style, $this->available_styles ) ) {
			$style = 'classic';
		}

		$output = '<div class="qfm-faq-container qfm-style-' . esc_attr( $style ) . '">';

		// Add search box if requested
		if ( $show_search ) {
			$output .= $this->render_search_box();
		}

		// Render based on style
		switch ( $style ) {
			case 'accordion-modern':
			case 'accordion-minimal':
				$output .= $this->render_accordion_style( $faqs, $style, $show_anchors );
				break;
			case 'card-layout':
				$output .= $this->render_card_style( $faqs, $show_anchors );
				break;
			case 'classic':
			default:
				$output .= $this->render_classic_style( $faqs, $show_anchors );
				break;
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render classic list style.
	 *
	 * @since 1.0.0
	 * @param array $faqs FAQ data array.
	 * @param bool  $show_anchors Whether to show anchor links.
	 * @return string Rendered output.
	 */
	private function render_classic_style( $faqs, $show_anchors ) {
		$output = '<div class="qfm-classic-list">';

		foreach ( $faqs as $faq ) {
			$anchor_id = $this->generate_faq_anchor( $faq['question'], $faq['id'] );
			
			$output .= '<div class="qfm-faq-item" id="' . esc_attr( $anchor_id ) . '">';
			
			// Question
			$output .= '<h3 class="qfm-question">';
			if ( $show_anchors ) {
				$output .= '<a href="#' . esc_attr( $anchor_id ) . '" class="qfm-anchor-link" aria-label="' . esc_attr__( 'Direct link to this FAQ', 'quick-faq-markup' ) . '">';
			}
			$output .= esc_html( $faq['question'] );
			if ( $show_anchors ) {
				$output .= '</a>';
			}
			$output .= '</h3>';
			
			// Answer
			$output .= '<div class="qfm-answer">';
			$output .= wp_kses_post( $faq['answer'] );
			$output .= '</div>';
			
			$output .= '</div>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render accordion style.
	 *
	 * @since 1.0.0
	 * @param array  $faqs FAQ data array.
	 * @param string $style Accordion style variant.
	 * @param bool   $show_anchors Whether to show anchor links.
	 * @return string Rendered output.
	 */
	private function render_accordion_style( $faqs, $style, $show_anchors ) {
		$output = '<div class="qfm-accordion qfm-' . esc_attr( $style ) . '" role="tablist">';

		foreach ( $faqs as $index => $faq ) {
			$anchor_id = $this->generate_faq_anchor( $faq['question'], $faq['id'] );
			$button_id = 'qfm-button-' . $faq['id'];
			$panel_id = 'qfm-panel-' . $faq['id'];
			
			$output .= '<div class="qfm-accordion-item" id="' . esc_attr( $anchor_id ) . '">';
			
			// Question button
			$output .= '<button class="qfm-accordion-button" ';
			$output .= 'id="' . esc_attr( $button_id ) . '" ';
			$output .= 'aria-expanded="false" ';
			$output .= 'aria-controls="' . esc_attr( $panel_id ) . '" ';
			$output .= 'role="tab" ';
			$output .= 'type="button">';
			
			if ( $show_anchors ) {
				$output .= '<a href="#' . esc_attr( $anchor_id ) . '" class="qfm-anchor-link" tabindex="-1" aria-label="' . esc_attr__( 'Direct link to this FAQ', 'quick-faq-markup' ) . '">';
			}
			
			$output .= '<span class="qfm-question-text">' . esc_html( $faq['question'] ) . '</span>';
			
			if ( $show_anchors ) {
				$output .= '</a>';
			}
			
			$output .= '<span class="qfm-accordion-icon" aria-hidden="true"></span>';
			$output .= '</button>';
			
			// Answer panel
			$output .= '<div class="qfm-accordion-panel" ';
			$output .= 'id="' . esc_attr( $panel_id ) . '" ';
			$output .= 'aria-labelledby="' . esc_attr( $button_id ) . '" ';
			$output .= 'role="tabpanel" ';
			$output .= 'hidden>';
			
			$output .= '<div class="qfm-answer">';
			$output .= wp_kses_post( $faq['answer'] );
			$output .= '</div>';
			
			$output .= '</div>';
			$output .= '</div>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render card layout style.
	 *
	 * @since 1.0.0
	 * @param array $faqs FAQ data array.
	 * @param bool  $show_anchors Whether to show anchor links.
	 * @return string Rendered output.
	 */
	private function render_card_style( $faqs, $show_anchors ) {
		$output = '<div class="qfm-card-grid">';

		foreach ( $faqs as $faq ) {
			$anchor_id = $this->generate_faq_anchor( $faq['question'], $faq['id'] );
			
			$output .= '<div class="qfm-card" id="' . esc_attr( $anchor_id ) . '">';
			
			// Question
			$output .= '<h3 class="qfm-card-question">';
			if ( $show_anchors ) {
				$output .= '<a href="#' . esc_attr( $anchor_id ) . '" class="qfm-anchor-link" aria-label="' . esc_attr__( 'Direct link to this FAQ', 'quick-faq-markup' ) . '">';
			}
			$output .= esc_html( $faq['question'] );
			if ( $show_anchors ) {
				$output .= '</a>';
			}
			$output .= '</h3>';
			
			// Answer
			$output .= '<div class="qfm-card-answer">';
			$output .= wp_kses_post( $faq['answer'] );
			$output .= '</div>';
			
			$output .= '</div>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render search box.
	 *
	 * @since 1.0.0
	 * @return string Rendered search box.
	 */
	private function render_search_box() {
		$output = '<div class="qfm-search-box">';
		$output .= '<label for="qfm-search-input" class="screen-reader-text">' . esc_html__( 'Search FAQs', 'quick-faq-markup' ) . '</label>';
		$output .= '<input type="search" id="qfm-search-input" class="qfm-search-input" placeholder="' . esc_attr__( 'Search FAQs...', 'quick-faq-markup' ) . '" />';
		$output .= '<button type="button" class="qfm-search-clear" aria-label="' . esc_attr__( 'Clear search', 'quick-faq-markup' ) . '" hidden>';
		$output .= '<span aria-hidden="true">&times;</span>';
		$output .= '</button>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Query FAQs based on arguments.
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments.
	 * @return array FAQ data array.
	 */
	private function query_faqs( $args ) {
		// Check cache first
		$cache_key = 'qfm_faqs_' . md5( wp_json_encode( $args ) );
		$faqs = wp_cache_get( $cache_key, 'quick_faq_markup' );

		if ( false !== $faqs ) {
			return $faqs;
		}

		// Build query arguments
		$query_args = array(
			'post_type'      => 'qfm_faq',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $args['limit'],
			'orderby'        => 'menu_order',
			'order'          => sanitize_text_field( $args['order'] ),
			'meta_query'     => array(
				array(
					'key'     => '_qfm_faq_question',
					'compare' => 'EXISTS',
				),
			),
		);

		// Add category filter if specified
		if ( ! empty( $args['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'qfm_faq_category',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $args['category'] ),
				),
			);
		}

		// Add specific IDs if provided
		if ( ! empty( $args['ids'] ) ) {
			$ids = array_map( 'intval', explode( ',', $args['ids'] ) );
			$query_args['post__in'] = $ids;
			$query_args['orderby'] = 'post__in';
		}

		/**
		 * Filter FAQ query arguments.
		 *
		 * @since 1.0.0
		 * @param array $query_args WP_Query arguments.
		 * @param array $args Original display arguments.
		 */
		$query_args = apply_filters( 'qfm_faq_query_args', $query_args, $args );

		// Execute query
		$query = new WP_Query( $query_args );
		$faqs = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();

				// Get taxonomy terms for this FAQ
				$categories = wp_get_post_terms( $post_id, 'qfm_faq_category', array( 'fields' => 'names' ) );
				
				$faq_data = array(
					'id'         => $post_id,
					'question'   => get_post_meta( $post_id, '_qfm_faq_question', true ),
					'answer'     => get_post_meta( $post_id, '_qfm_faq_answer', true ),
					'categories' => ! is_wp_error( $categories ) ? $categories : array(),
					'order'      => get_post_meta( $post_id, '_qfm_faq_order', true ),
				);

				// Skip if question or answer is empty
				if ( empty( $faq_data['question'] ) || empty( $faq_data['answer'] ) ) {
					continue;
				}

				$faqs[] = $faq_data;
			}
		}

		wp_reset_postdata();

		// Cache the results
		wp_cache_set( $cache_key, $faqs, 'quick_faq_markup', HOUR_IN_SECONDS );

		return $faqs;
	}

	/**
	 * Generate FAQ anchor ID.
	 *
	 * @since 1.0.0
	 * @param string $question FAQ question.
	 * @param int    $faq_id FAQ post ID.
	 * @return string Anchor ID.
	 */
	public function generate_faq_anchor( $question, $faq_id ) {
		// Use schema class for SEO-optimized anchor generation
		$schema_instance = $this->get_schema_instance();
		if ( $schema_instance && method_exists( $schema_instance, 'generate_seo_anchor_id' ) ) {
			return $schema_instance->generate_seo_anchor_id( $question, $faq_id );
		}

		// Fallback to basic anchor generation
		$slug = sanitize_title( $question );
		$slug = substr( $slug, 0, 50 );
		$anchor = 'qfm-faq-' . $faq_id . '-' . $slug;

		/**
		 * Filter the FAQ anchor ID.
		 *
		 * @since 1.0.0
		 * @param string $anchor The generated anchor ID.
		 * @param string $question The FAQ question.
		 * @param int $faq_id The FAQ post ID.
		 */
		return apply_filters( 'qfm_faq_anchor', $anchor, $question, $faq_id );
	}

	/**
	 * Sanitize display arguments.
	 *
	 * @since 1.0.0
	 * @param array $args Raw arguments.
	 * @return array Sanitized arguments.
	 */
	private function sanitize_display_args( $args ) {
		return array(
			'style'        => sanitize_text_field( $args['style'] ),
			'category'     => sanitize_text_field( $args['category'] ),
			'limit'        => (int) $args['limit'],
			'order'        => in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'ASC',
			'ids'          => sanitize_text_field( $args['ids'] ),
			'show_anchors' => (bool) $args['show_anchors'],
			'show_search'  => (bool) $args['show_search'],
		);
	}

	/**
	 * Check if current content has FAQ shortcode.
	 *
	 * @since 1.0.0
	 * @return bool True if shortcode is present.
	 */
	private function has_faq_shortcode() {
		global $post;

		if ( ! $post || ! is_object( $post ) ) {
			return false;
		}

		return has_shortcode( $post->post_content, 'qfm_faq' );
	}

	/**
	 * Get available display styles.
	 *
	 * @since 1.0.0
	 * @return array Available styles.
	 */
	public function get_available_styles() {
		/**
		 * Filter available FAQ display styles.
		 *
		 * @since 1.0.0
		 * @param array $styles Available display styles.
		 */
		return apply_filters( 'qfm_available_styles', $this->available_styles );
	}

	/**
	 * Get FAQs for the current page (for schema generation).
	 *
	 * @since 1.0.0
	 * @return array FAQ data for current page.
	 */
	public function get_page_faqs() {
		return $this->page_faqs;
	}

	/**
	 * Add anchor targeting functionality.
	 *
	 * @since 1.0.0
	 */
	public function add_anchor_targeting_script() {
		if ( ! $this->has_faq_shortcode() && ! is_singular( 'qfm_faq' ) ) {
			return;
		}

		?>
		<script type="text/javascript">
		(function() {
			// Handle anchor links on page load
			function handleAnchorOnLoad() {
				if (window.location.hash) {
					var target = document.querySelector(window.location.hash);
					if (target && target.classList.contains('qfm-faq-item')) {
						// If it's an accordion item, open it
						var button = target.querySelector('.qfm-accordion-button');
						if (button && button.getAttribute('aria-expanded') === 'false') {
							button.click();
						}
						// Smooth scroll to target
						setTimeout(function() {
							target.scrollIntoView({ behavior: 'smooth', block: 'start' });
						}, 100);
					}
				}
			}

			// Handle hash changes
			window.addEventListener('hashchange', handleAnchorOnLoad);
			
			// Handle initial load
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', handleAnchorOnLoad);
			} else {
				handleAnchorOnLoad();
			}
		})();
		</script>
		<?php
	}

	/**
	 * Add FAQs to schema generation system.
	 *
	 * @since 1.0.0
	 * @param array $faqs FAQ data array.
	 */
	private function add_faqs_to_schema( $faqs ) {
		$schema_instance = $this->get_schema_instance();
		if ( $schema_instance && method_exists( $schema_instance, 'add_page_faqs' ) ) {
			$schema_instance->add_page_faqs( $faqs );
		}
	}

	/**
	 * Get schema instance from global plugin instance.
	 *
	 * @since 1.0.0
	 * @return Quick_FAQ_Markup_Schema|null Schema instance or null if not available.
	 */
	private function get_schema_instance() {
		global $quick_faq_markup;
		
		if ( $quick_faq_markup && method_exists( $quick_faq_markup, 'get_schema' ) ) {
			return $quick_faq_markup->get_schema();
		}
		
		return null;
	}
}