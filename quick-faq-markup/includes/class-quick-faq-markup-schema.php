<?php
/**
 * The schema markup functionality of the plugin.
 *
 * Defines JSON-LD schema generation for FAQ structured data,
 * Google FAQ schema compliance, and SEO optimization features.
 *
 * @package Quick_FAQ_Markup
 * @since 1.0.0
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * The schema markup functionality of the plugin.
 *
 * Generates JSON-LD schema markup for FAQ pages to improve
 * Google Featured Snippets visibility and SEO performance.
 *
 * @since 1.0.0
 */
class Quick_FAQ_Markup_Schema {

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
	 * FAQ data collected from the current page.
	 *
	 * @since 1.0.0
	 * @var array $current_page_faqs FAQ data for schema generation.
	 */
	private $current_page_faqs = array();

	/**
	 * Schema markup cache.
	 *
	 * @since 1.0.0
	 * @var array $schema_cache Cached schema markup data.
	 */
	private $schema_cache = array();

	/**
	 * Whether schema has been output to avoid duplicates.
	 *
	 * @since 1.0.0
	 * @var bool $schema_output_done Flag to track schema output.
	 */
	private $schema_output_done = false;

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
	 * Add FAQ data to the current page for schema generation.
	 *
	 * @since 1.0.0
	 * @param array $faqs FAQ data array.
	 */
	public function add_page_faqs( $faqs ) {
		if ( ! is_array( $faqs ) ) {
			return;
		}

		foreach ( $faqs as $faq ) {
			if ( $this->validate_faq_data( $faq ) ) {
				$this->current_page_faqs[] = $faq;
			}
		}
	}

	/**
	 * Generate JSON-LD schema markup for FAQ data.
	 *
	 * @since 1.0.0
	 * @param array  $faqs FAQ data array.
	 * @param string $page_url Current page URL for anchors.
	 * @return array|false Generated schema data or false on error.
	 */
	public function generate_faq_schema( $faqs = array(), $page_url = '' ) {
		if ( empty( $faqs ) && empty( $this->current_page_faqs ) ) {
			return false;
		}

		$faq_data = ! empty( $faqs ) ? $faqs : $this->current_page_faqs;

		if ( empty( $page_url ) ) {
			$page_url = $this->get_current_page_url();
		}

		// Check cache first
		$cache_key = 'qfm_schema_' . md5( wp_json_encode( $faq_data ) . $page_url );
		$cached_schema = wp_cache_get( $cache_key, 'quick_faq_markup' );

		if ( false !== $cached_schema ) {
			return $cached_schema;
		}

		$schema_data = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => array(),
		);

		// Add organization context if available
		$organization_schema = $this->get_organization_context();
		if ( ! empty( $organization_schema ) ) {
			$schema_data['publisher'] = $organization_schema;
		}

		// Add breadcrumb context if available
		$breadcrumb_schema = $this->get_breadcrumb_context();
		if ( ! empty( $breadcrumb_schema ) ) {
			$schema_data['breadcrumb'] = $breadcrumb_schema;
		}

		foreach ( $faq_data as $faq ) {
			if ( ! $this->validate_faq_data( $faq ) ) {
				continue;
			}

			$anchor_url = $this->generate_faq_anchor_url( $faq, $page_url );
			$question_schema = array(
				'@type'          => 'Question',
				'name'           => $this->sanitize_schema_text( $faq['question'] ),
				'url'            => esc_url( $anchor_url ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $this->sanitize_schema_html( $faq['answer'] ),
				),
			);

			// Add author information if available
			$author_info = $this->get_author_context( $faq );
			if ( ! empty( $author_info ) ) {
				$question_schema['author'] = $author_info;
			}

			// Add date information if available
			$date_info = $this->get_date_context( $faq );
			if ( ! empty( $date_info ) ) {
				$question_schema['dateCreated'] = $date_info['created'];
				if ( ! empty( $date_info['modified'] ) ) {
					$question_schema['dateModified'] = $date_info['modified'];
				}
			}

			// Add image if answer contains images
			$image_info = $this->extract_answer_images( $faq['answer'] );
			if ( ! empty( $image_info ) ) {
				$question_schema['acceptedAnswer']['image'] = $image_info;
			}

			$schema_data['mainEntity'][] = $question_schema;
		}

		// Validate the generated schema
		if ( ! $this->validate_schema( $schema_data ) ) {
			quick_faq_markup_log( 'Generated FAQ schema failed validation', 'warning', array(
				'schema' => $schema_data,
				'faqs'   => count( $faq_data ),
			) );
			return false;
		}

		/**
		 * Filter the generated FAQ schema data.
		 *
		 * @since 1.0.0
		 * @param array $schema_data The generated schema data.
		 * @param array $faq_data The original FAQ data.
		 * @param string $page_url The current page URL.
		 */
		$schema_data = apply_filters( 'qfm_schema_data', $schema_data, $faq_data, $page_url );

		// Cache the schema data
		wp_cache_set( $cache_key, $schema_data, 'quick_faq_markup', HOUR_IN_SECONDS );

		return $schema_data;
	}

	/**
	 * Output schema markup to page head.
	 *
	 * @since 1.0.0
	 */
	public function output_schema_to_head() {
		// Prevent duplicate output
		if ( $this->schema_output_done ) {
			return;
		}

		// Check if schema is enabled
		$settings = get_option( 'quick_faq_markup_settings', array() );
		if ( empty( $settings['enable_schema'] ) ) {
			return;
		}

		// Only output on pages with FAQ content
		if ( ! $this->should_output_schema() ) {
			return;
		}

		$schema_data = $this->generate_faq_schema();

		if ( empty( $schema_data ) ) {
			return;
		}

		// Minify JSON output for performance
		$json_flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		if ( ! WP_DEBUG ) {
			$json_flags |= JSON_UNESCAPED_SLASHES;
		}

		$schema_json = wp_json_encode( $schema_data, $json_flags );

		if ( false === $schema_json ) {
			quick_faq_markup_log( 'Failed to encode FAQ schema as JSON', 'error' );
			return;
		}

		echo '<script type="application/ld+json">' . $schema_json . '</script>' . "\n";

		$this->schema_output_done = true;

		quick_faq_markup_log( 'FAQ schema markup output to head', 'info', array(
			'faqs_count' => count( $this->current_page_faqs ),
			'url'        => $this->get_current_page_url(),
		) );
	}

	/**
	 * Generate SEO-friendly anchor URL for FAQ.
	 *
	 * @since 1.0.0
	 * @param array  $faq FAQ data.
	 * @param string $base_url Base page URL.
	 * @return string Complete anchor URL.
	 */
	public function generate_faq_anchor_url( $faq, $base_url = '' ) {
		if ( empty( $base_url ) ) {
			$base_url = $this->get_current_page_url();
		}

		$anchor_id = $this->generate_seo_anchor_id( $faq['question'], $faq['id'] );

		return $base_url . '#' . $anchor_id;
	}

	/**
	 * Generate SEO-friendly anchor ID.
	 *
	 * @since 1.0.0
	 * @param string $question FAQ question.
	 * @param int    $faq_id FAQ post ID.
	 * @return string SEO-friendly anchor ID.
	 */
	public function generate_seo_anchor_id( $question, $faq_id ) {
		// Create base slug from question
		$slug = sanitize_title( $question );
		
		// Remove common words for better SEO
		$stop_words = array( 'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should' );
		$words = explode( '-', $slug );
		$filtered_words = array_diff( $words, $stop_words );
		
		if ( ! empty( $filtered_words ) ) {
			$slug = implode( '-', $filtered_words );
		}
		
		// Limit length for URLs
		$slug = substr( $slug, 0, 60 );
		$slug = rtrim( $slug, '-' );
		
		// Ensure uniqueness with ID
		$anchor = 'faq-' . $faq_id . '-' . $slug;

		/**
		 * Filter the generated SEO anchor ID.
		 *
		 * @since 1.0.0
		 * @param string $anchor The generated anchor ID.
		 * @param string $question The FAQ question.
		 * @param int $faq_id The FAQ post ID.
		 */
		return apply_filters( 'qfm_seo_anchor_id', $anchor, $question, $faq_id );
	}

	/**
	 * Validate FAQ data structure.
	 *
	 * @since 1.0.0
	 * @param array $faq FAQ data to validate.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_faq_data( $faq ) {
		if ( ! is_array( $faq ) ) {
			return false;
		}

		$required_fields = array( 'id', 'question', 'answer' );

		foreach ( $required_fields as $field ) {
			if ( empty( $faq[ $field ] ) ) {
				return false;
			}
		}

		// Validate question length (Google recommends 250 chars max)
		if ( strlen( $faq['question'] ) > 250 ) {
			quick_faq_markup_log( 'FAQ question exceeds recommended length', 'warning', array(
				'faq_id' => $faq['id'],
				'length' => strlen( $faq['question'] ),
			) );
		}

		return true;
	}

	/**
	 * Validate generated schema data.
	 *
	 * @since 1.0.0
	 * @param array $schema Schema data to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_schema( $schema ) {
		if ( ! is_array( $schema ) ) {
			return false;
		}

		// Check required schema.org fields
		$required_fields = array( '@context', '@type', 'mainEntity' );

		foreach ( $required_fields as $field ) {
			if ( ! isset( $schema[ $field ] ) ) {
				return false;
			}
		}

		// Validate context
		if ( $schema['@context'] !== 'https://schema.org' ) {
			return false;
		}

		// Validate type
		if ( $schema['@type'] !== 'FAQPage' ) {
			return false;
		}

		// Validate main entity
		if ( ! is_array( $schema['mainEntity'] ) || empty( $schema['mainEntity'] ) ) {
			return false;
		}

		// Validate each question
		foreach ( $schema['mainEntity'] as $question ) {
			if ( ! $this->validate_question_schema( $question ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate individual question schema.
	 *
	 * @since 1.0.0
	 * @param array $question Question schema data.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_question_schema( $question ) {
		if ( ! is_array( $question ) ) {
			return false;
		}

		// Required fields for Question
		$required_fields = array( '@type', 'name', 'acceptedAnswer' );

		foreach ( $required_fields as $field ) {
			if ( ! isset( $question[ $field ] ) ) {
				return false;
			}
		}

		// Validate type
		if ( $question['@type'] !== 'Question' ) {
			return false;
		}

		// Validate accepted answer
		if ( ! is_array( $question['acceptedAnswer'] ) ) {
			return false;
		}

		$answer = $question['acceptedAnswer'];
		if ( ! isset( $answer['@type'] ) || $answer['@type'] !== 'Answer' ) {
			return false;
		}

		if ( empty( $answer['text'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if schema should be output on current page.
	 *
	 * @since 1.0.0
	 * @return bool True if schema should be output.
	 */
	private function should_output_schema() {
		// Check if we have FAQ data
		if ( empty( $this->current_page_faqs ) ) {
			return false;
		}

		// Check if page has FAQ shortcode
		if ( $this->has_faq_shortcode() ) {
			return true;
		}

		// Check if it's a FAQ single post
		if ( is_singular( 'qfm_faq' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if current page content has FAQ shortcode.
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
	 * Get current page URL for schema context.
	 *
	 * @since 1.0.0
	 * @return string Current page URL.
	 */
	private function get_current_page_url() {
		global $wp;

		$current_url = home_url( add_query_arg( array(), $wp->request ) );

		// Ensure proper URL structure
		if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
			$current_url .= '?' . sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) );
		}

		return esc_url( $current_url );
	}

	/**
	 * Get organization context for schema.
	 *
	 * @since 1.0.0
	 * @return array|null Organization schema data.
	 */
	private function get_organization_context() {
		$organization = array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => esc_url( home_url() ),
		);

		// Add logo if available
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			$logo_data = wp_get_attachment_image_src( $custom_logo_id, 'full' );
			if ( $logo_data ) {
				$organization['logo'] = array(
					'@type' => 'ImageObject',
					'url'   => esc_url( $logo_data[0] ),
				);
			}
		}

		/**
		 * Filter organization schema context.
		 *
		 * @since 1.0.0
		 * @param array $organization Organization schema data.
		 */
		return apply_filters( 'qfm_organization_schema', $organization );
	}

	/**
	 * Get breadcrumb context for schema.
	 *
	 * @since 1.0.0
	 * @return array|null Breadcrumb schema data.
	 */
	private function get_breadcrumb_context() {
		if ( ! is_singular() ) {
			return null;
		}

		$breadcrumbs = array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => array(),
		);

		// Home page
		$breadcrumbs['itemListElement'][] = array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => esc_html__( 'Home', 'quick-faq-markup' ),
			'item'     => esc_url( home_url() ),
		);

		// Current page
		$breadcrumbs['itemListElement'][] = array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => esc_html( get_the_title() ),
			'item'     => esc_url( get_permalink() ),
		);

		/**
		 * Filter breadcrumb schema context.
		 *
		 * @since 1.0.0
		 * @param array $breadcrumbs Breadcrumb schema data.
		 */
		return apply_filters( 'qfm_breadcrumb_schema', $breadcrumbs );
	}

	/**
	 * Get author context for FAQ.
	 *
	 * @since 1.0.0
	 * @param array $faq FAQ data.
	 * @return array|null Author schema data.
	 */
	private function get_author_context( $faq ) {
		if ( empty( $faq['id'] ) ) {
			return null;
		}

		$post = get_post( $faq['id'] );
		if ( ! $post ) {
			return null;
		}

		$author_id = $post->post_author;
		$author = get_userdata( $author_id );

		if ( ! $author ) {
			return null;
		}

		$author_schema = array(
			'@type' => 'Person',
			'name'  => esc_html( $author->display_name ),
		);

		// Add author URL if available
		$author_url = get_author_posts_url( $author_id );
		if ( $author_url ) {
			$author_schema['url'] = esc_url( $author_url );
		}

		return $author_schema;
	}

	/**
	 * Get date context for FAQ.
	 *
	 * @since 1.0.0
	 * @param array $faq FAQ data.
	 * @return array|null Date information.
	 */
	private function get_date_context( $faq ) {
		if ( empty( $faq['id'] ) ) {
			return null;
		}

		$post = get_post( $faq['id'] );
		if ( ! $post ) {
			return null;
		}

		$date_info = array(
			'created' => gmdate( 'c', strtotime( $post->post_date_gmt ) ),
		);

		if ( $post->post_modified_gmt !== $post->post_date_gmt ) {
			$date_info['modified'] = gmdate( 'c', strtotime( $post->post_modified_gmt ) );
		}

		return $date_info;
	}

	/**
	 * Extract images from FAQ answer content.
	 *
	 * @since 1.0.0
	 * @param string $answer FAQ answer content.
	 * @return array|null Image schema data.
	 */
	private function extract_answer_images( $answer ) {
		if ( empty( $answer ) ) {
			return null;
		}

		// Extract images from HTML content
		preg_match_all( '/<img[^>]+src="([^"]+)"[^>]*>/i', $answer, $matches );

		if ( empty( $matches[1] ) ) {
			return null;
		}

		$images = array();
		foreach ( $matches[1] as $image_url ) {
			$images[] = array(
				'@type' => 'ImageObject',
				'url'   => esc_url( $image_url ),
			);
		}

		return count( $images ) === 1 ? $images[0] : $images;
	}

	/**
	 * Sanitize text for schema markup.
	 *
	 * @since 1.0.0
	 * @param string $text Text to sanitize.
	 * @return string Sanitized text.
	 */
	private function sanitize_schema_text( $text ) {
		// Remove HTML tags and decode entities
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		
		// Normalize whitespace
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );

		return $text;
	}

	/**
	 * Sanitize HTML content for schema markup.
	 *
	 * @since 1.0.0
	 * @param string $html HTML content to sanitize.
	 * @return string Sanitized HTML.
	 */
	private function sanitize_schema_html( $html ) {
		// Allow basic HTML tags for rich answers
		$allowed_tags = array(
			'p'      => array(),
			'br'     => array(),
			'strong' => array(),
			'em'     => array(),
			'b'      => array(),
			'i'      => array(),
			'ul'     => array(),
			'ol'     => array(),
			'li'     => array(),
			'a'      => array( 'href' => true ),
		);

		$html = wp_kses( $html, $allowed_tags );
		$html = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return $html;
	}

	/**
	 * Clear schema cache.
	 *
	 * @since 1.0.0
	 */
	public function clear_schema_cache() {
		// Clear object cache group
		wp_cache_flush_group( 'quick_faq_markup' );

		// Reset current page FAQs
		$this->current_page_faqs = array();
		$this->schema_output_done = false;

		quick_faq_markup_log( 'Schema cache cleared', 'info' );
	}

	/**
	 * Get current page FAQ data.
	 *
	 * @since 1.0.0
	 * @return array Current page FAQ data.
	 */
	public function get_current_page_faqs() {
		return $this->current_page_faqs;
	}

	/**
	 * Generate Open Graph meta tags for FAQ content.
	 *
	 * @since 1.0.0
	 */
	public function output_open_graph_meta() {
		if ( empty( $this->current_page_faqs ) ) {
			return;
		}

		$first_faq = $this->current_page_faqs[0];
		$description = $this->sanitize_schema_text( $first_faq['answer'] );
		$description = substr( $description, 0, 155 ) . '...';

		echo '<meta property="og:type" content="article" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
	}

	/**
	 * Generate FAQ sitemap data.
	 *
	 * @since 1.0.0
	 * @return array FAQ sitemap entries.
	 */
	public function generate_sitemap_data() {
		$faq_posts = get_posts( array(
			'post_type'      => 'qfm_faq',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_qfm_faq_question',
					'compare' => 'EXISTS',
				),
			),
		) );

		$sitemap_data = array();

		foreach ( $faq_posts as $post ) {
			$question = get_post_meta( $post->ID, '_qfm_faq_question', true );
			$answer = get_post_meta( $post->ID, '_qfm_faq_answer', true );

			if ( empty( $question ) || empty( $answer ) ) {
				continue;
			}

			$sitemap_data[] = array(
				'url'          => get_permalink( $post->ID ),
				'lastmod'      => gmdate( 'c', strtotime( $post->post_modified_gmt ) ),
				'priority'     => 0.7,
				'changefreq'   => 'monthly',
				'title'        => $question,
				'description'  => wp_trim_words( wp_strip_all_tags( $answer ), 20 ),
			);
		}

		/**
		 * Filter FAQ sitemap data.
		 *
		 * @since 1.0.0
		 * @param array $sitemap_data FAQ sitemap entries.
		 */
		return apply_filters( 'qfm_sitemap_data', $sitemap_data );
	}
}