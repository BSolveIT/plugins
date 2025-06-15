<?php
/**
 * Schema Generator - Generates different schema formats for FAQs
 *
 * @link       https://365i.com
 * @since      1.0.0
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/includes
 */

/**
 * Schema Generator class.
 *
 * Handles generation of different schema formats: JSON-LD, Microdata, RDFa, and HTML.
 *
 * @since      1.0.0
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/includes
 * @author     365i
 */
class Schema_Generator {

    /**
     * The base URL for FAQ page.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $base_url    The base URL for FAQs.
     */
    private $base_url;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $base_url    Optional. The base URL for the FAQ page.
     */
    public function __construct($base_url = '') {
        $settings = get_option('faq_ai_generator_settings', array());
        $this->base_url = !empty($base_url) ? $base_url : 
                         (!empty($settings['faq_page_url']) ? $settings['faq_page_url'] : site_url());
    }

    /**
     * Generate JSON-LD schema for FAQs.
     *
     * @since    1.0.0
     * @param    array    $faqs          Array of FAQ items with 'question', 'answer', and 'id'.
     * @param    bool     $return_array  Whether to return an array instead of JSON string.
     * @return   string|array            JSON-LD schema markup or array.
     */
    public function generate_json_ld($faqs, $return_array = false) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array()
        );

        foreach ($faqs as $faq) {
            if (empty($faq['question']) || empty($faq['answer'])) {
                continue;
            }

            $question = $this->sanitize_schema_text($faq['question']);
            $answer = $this->sanitize_schema_text($faq['answer']);
            $anchor = isset($faq['id']) ? $this->create_anchor($faq['id'], $faq['question']) : '';
            
            $item = array(
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => $answer
                )
            );

            // Add URL if we have an anchor and base URL
            if (!empty($anchor) && !empty($this->base_url)) {
                $item['url'] = trailingslashit($this->base_url) . '#' . $anchor;
            }

            $schema['mainEntity'][] = $item;
        }

        if ($return_array) {
            return $schema;
        }

        // Format JSON for readability with proper escaping for script tags
        $json = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return '<script type="application/ld+json">' . PHP_EOL . $json . PHP_EOL . '</script>';
    }

    /**
     * Generate Microdata schema for FAQs.
     *
     * @since    1.0.0
     * @param    array    $faqs    Array of FAQ items with 'question', 'answer', and 'id'.
     * @return   string            HTML with Microdata schema markup.
     */
    public function generate_microdata($faqs) {
        $output = '<div itemscope itemtype="https://schema.org/FAQPage" class="faq-microdata-schema">' . PHP_EOL;

        foreach ($faqs as $faq) {
            if (empty($faq['question']) || empty($faq['answer'])) {
                continue;
            }

            $question = $this->sanitize_schema_text($faq['question']);
            $answer = $this->sanitize_schema_text($faq['answer'], false);
            $anchor = isset($faq['id']) ? $this->create_anchor($faq['id'], $faq['question']) : '';
            
            $output .= '  <div itemscope itemprop="mainEntity" itemtype="https://schema.org/Question"';
            
            if (!empty($anchor)) {
                $output .= ' id="' . esc_attr($anchor) . '"';
            }
            
            $output .= '>' . PHP_EOL;
            $output .= '    <h3 itemprop="name">' . $question . '</h3>' . PHP_EOL;
            $output .= '    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">' . PHP_EOL;
            $output .= '      <div itemprop="text">' . $answer . '</div>' . PHP_EOL;
            $output .= '    </div>' . PHP_EOL;
            $output .= '  </div>' . PHP_EOL;
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Generate RDFa schema for FAQs.
     *
     * @since    1.0.0
     * @param    array    $faqs    Array of FAQ items with 'question', 'answer', and 'id'.
     * @return   string            HTML with RDFa schema markup.
     */
    public function generate_rdfa($faqs) {
        $output = '<div vocab="https://schema.org/" typeof="FAQPage" class="faq-rdfa-schema">' . PHP_EOL;

        foreach ($faqs as $faq) {
            if (empty($faq['question']) || empty($faq['answer'])) {
                continue;
            }

            $question = $this->sanitize_schema_text($faq['question']);
            $answer = $this->sanitize_schema_text($faq['answer'], false);
            $anchor = isset($faq['id']) ? $this->create_anchor($faq['id'], $faq['question']) : '';
            
            $output .= '  <div property="mainEntity" typeof="Question"';
            
            if (!empty($anchor)) {
                $output .= ' id="' . esc_attr($anchor) . '"';
            }
            
            $output .= '>' . PHP_EOL;
            $output .= '    <h3 property="name">' . $question . '</h3>' . PHP_EOL;
            $output .= '    <div property="acceptedAnswer" typeof="Answer">' . PHP_EOL;
            $output .= '      <div property="text">' . $answer . '</div>' . PHP_EOL;
            $output .= '    </div>' . PHP_EOL;
            $output .= '  </div>' . PHP_EOL;
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Generate simple HTML markup for FAQs.
     *
     * @since    1.0.0
     * @param    array    $faqs    Array of FAQ items with 'question', 'answer', and 'id'.
     * @return   string            HTML markup without schema.
     */
    public function generate_html($faqs) {
        $output = '<div class="faq-container">' . PHP_EOL;

        foreach ($faqs as $faq) {
            if (empty($faq['question']) || empty($faq['answer'])) {
                continue;
            }

            $question = $this->sanitize_schema_text($faq['question']);
            $answer = $this->sanitize_schema_text($faq['answer'], false);
            $anchor = isset($faq['id']) ? $this->create_anchor($faq['id'], $faq['question']) : '';
            
            $output .= '  <div class="faq-item"';
            
            if (!empty($anchor)) {
                $output .= ' id="' . esc_attr($anchor) . '"';
            }
            
            $output .= '>' . PHP_EOL;
            $output .= '    <h3 class="faq-question">' . $question . '</h3>' . PHP_EOL;
            $output .= '    <div class="faq-answer">' . $answer . '</div>' . PHP_EOL;
            $output .= '  </div>' . PHP_EOL;
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Create a URL-safe anchor from FAQ ID or question.
     *
     * @since    1.0.0
     * @param    string    $id         FAQ ID.
     * @param    string    $question   FAQ question to use as fallback.
     * @return   string                URL-safe anchor.
     */
    private function create_anchor($id, $question) {
        if (!empty($id) && $id !== 'new') {
            return 'faq-' . sanitize_title($id);
        }
        
        // Fallback to question-based anchor
        return 'faq-' . sanitize_title($question);
    }

    /**
     * Sanitize text for schema output.
     *
     * @since    1.0.0
     * @param    string    $text           The text to sanitize.
     * @param    bool      $strip_tags     Whether to strip HTML tags.
     * @return   string                    Sanitized text.
     */
    private function sanitize_schema_text($text, $strip_tags = true) {
        // Remove any potentially harmful scripts
        $text = wp_kses_post($text);
        
        if ($strip_tags) {
            // For fields that should be plain text (like JSON-LD question field)
            $text = wp_strip_all_tags($text);
        }
        
        // Convert entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove excess whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }

    /**
     * Set the base URL for FAQs.
     *
     * @since    1.0.0
     * @param    string    $url    The base URL for FAQs.
     */
    public function set_base_url($url) {
        $this->base_url = $url;
    }
}