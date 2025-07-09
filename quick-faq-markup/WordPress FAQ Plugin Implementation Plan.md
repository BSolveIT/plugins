# WordPress FAQ Plugin Implementation Plan

## Plugin Overview
**Plugin Name:** Quick FAQ Markup  
**Description:** Lightweight FAQ plugin with proper schema markup for Google Featured Snippets and voice search optimization  
**Version:** 1.0.0  
**Requires WordPress:** 6.0+  
**Tested up to:** 6.7  
**PHP Version:** 8.0+  
**License:** GPL v2 or later  
**Text Domain:** quick-faq-markup  

## Alternative Safe Names (choose one):
1. **Quick FAQ Markup** (recommended)
2. **Smart FAQ Display** 
3. **FAQ Rich Snippets**
4. **365i FAQ Builder**
5. **Pro FAQ Snippets**

## 1. File Structure & Organization

```
quick-faq-markup/
├── quick-faq-markup.php              # Main plugin file
├── readme.txt                        # WordPress repo readme
├── LICENSE                          # GPL v2 license
├── uninstall.php                    # Cleanup on uninstall
├── includes/
│   ├── class-quick-faq-markup.php           # Main plugin class
│   ├── class-quick-faq-markup-admin.php     # Admin functionality
│   ├── class-quick-faq-markup-frontend.php  # Frontend display
│   ├── class-quick-faq-markup-schema.php    # Schema markup generation
│   └── class-quick-faq-markup-shortcode.php # Shortcode handling
├── admin/
│   ├── css/
│   │   └── quick-faq-markup-admin.css       # Admin interface styles
│   ├── js/
│   │   └── quick-faq-markup-admin.js        # Admin JavaScript
│   └── partials/
│       ├── faq-meta-box.php          # FAQ meta box template
│       └── settings-page.php         # Settings page template
├── public/
│   ├── css/
│   │   └── quick-faq-markup-public.css      # Frontend FAQ styles
│   └── js/
│       └── quick-faq-markup-public.js       # Frontend JavaScript
└── languages/
    └── quick-faq-markup.pot         # Translation template
```

## 2. WordPress Coding Standards & Requirements

### 2.1 Naming Conventions
- **Plugin Slug:** `quick-faq-markup`
- **Text Domain:** `quick-faq-markup`
- **Class Prefix:** `Quick_FAQ_Markup_`
- **Function Prefix:** `quick_faq_markup_`
- **Hook Prefix:** `quick_faq_markup_`
- **CSS Class Prefix:** `qfm-` (Quick FAQ Markup)
- **Database Options:** `quick_faq_markup_`
- **Custom Post Type:** `qfm_faq`
- **Meta Keys:** `_qfm_faq_`

### 2.2 Class Naming Rules
- Use `class-` prefix for filenames: `class-quick-faq-markup.php`
- PascalCase with underscores for class names: `Quick_FAQ_Markup_Admin`
- snake_case for functions and variables
- SCREAMING_SNAKE_CASE for constants

### 2.3 Security Requirements (2025 Enhanced Standards)
- **Input Sanitization**: Use `sanitize_text_field()`, `sanitize_textarea_field()`, `wp_kses_post()`
- **Nonce Verification**: Always use `wp_create_nonce()` and `wp_verify_nonce()`
- **Nonce Processing**: Must use `sanitize_text_field( wp_unslash( $_POST['nonce_field'] ) )` before verification
- **Output Escaping**: Use `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- **Capability Checks**: Verify user permissions for all admin actions
- **SQL Injection Prevention**: Use `$wpdb->prepare()` for all database queries
- **CSRF Protection**: Implement nonces for all form submissions
- **XSS Prevention**: Escape all output, validate all input
- **No Direct File Access**: Include `defined('ABSPATH') || exit;` in all PHP files

### 2.4 WordPress Standards Compliance (2025 Updated)
- Follow WordPress PHP Coding Standards (PSR-12 based)
- Use WordPress hooks and filters appropriately
- **Internationalization (i18n)**: No longer require `load_plugin_textdomain()` calls (WordPress 6.8+)
- **Accessibility Compliance**: WCAG 2.1 AA standards mandatory
- **Performance Optimization**: Core Web Vitals optimization required
- **No Direct Database Queries**: Use WordPress APIs exclusively
- **European Accessibility Act 2025**: Full compliance required for EU markets
- **Schema.org Standards**: JSON-LD format only (Google's 2025 recommendation)

## 3. Main Plugin File Structure

### 3.1 Plugin Header (quick-faq-markup.php)
```php
<?php
/**
 * Plugin Name:       Quick FAQ Markup
 * Plugin URI:        https://365i.co.uk/quick-faq-markup/
 * Description:       Lightweight FAQ plugin with proper schema markup for Google Featured Snippets and voice search optimization.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            365i
 * Author URI:        https://365i.co.uk/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       quick-faq-markup
 * Domain Path:       /languages
 * Network:           false
 *
 * Quick FAQ Markup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'QUICK_FAQ_MARKUP_VERSION', '1.0.0' );
define( 'QUICK_FAQ_MARKUP_PLUGIN_FILE', __FILE__ );
define( 'QUICK_FAQ_MARKUP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QUICK_FAQ_MARKUP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QUICK_FAQ_MARKUP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Activation and deactivation hooks
register_activation_hook( __FILE__, 'quick_faq_markup_activate' );
register_deactivation_hook( __FILE__, 'quick_faq_markup_deactivate' );

// Initialize plugin
add_action( 'plugins_loaded', 'quick_faq_markup_init' );
```

## 4. Database Schema

### 4.1 Custom Post Type: `qfm_faq`
- **Post Type:** `qfm_faq`
- **Public:** false
- **Show UI:** true
- **Show in Menu:** true
- **Supports:** title, editor, page-attributes (for ordering)

### 4.2 Meta Fields
- `_qfm_faq_question` - FAQ question text
- `_qfm_faq_answer` - FAQ answer text
- `_qfm_faq_order` - Display order (integer)
- `_qfm_faq_category` - FAQ category/group

### 4.3 Post Type Order Support
- **Custom Post Type** supports `page-attributes` for menu_order
- **Drag-and-drop reordering** in admin list table
- **Manual order override** via meta field
- **Frontend display** respects custom ordering

### 4.4 Options Table Entries
- `quick_faq_markup_settings` - Plugin settings array
- `quick_faq_markup_version` - Plugin version for updates

## 5. Core Classes Implementation

### 5.1 Main Plugin Class
**File:** `includes/class-quick-faq-markup.php`

**Responsibilities:**
- Plugin initialization
- Dependency management
- Hook registration
- Version management

**Key Methods:**
- `__construct()` - Initialize plugin
- `init()` - Setup hooks and filters
- `load_dependencies()` - Include required files
- `define_admin_hooks()` - Admin-specific hooks
- `define_public_hooks()` - Frontend hooks

### 5.2 Admin Class
**File:** `includes/class-quick-faq-markup-admin.php`

**Responsibilities:**
- Custom post type registration with ordering support
- Meta box creation
- **Drag-and-drop FAQ reordering interface**
- Settings page
- Admin scripts/styles

**Key Methods:**
- `register_post_type()` - Register FAQ post type with page-attributes
- `add_meta_boxes()` - FAQ question/answer fields
- `save_meta_data()` - Save FAQ meta data
- `enqueue_admin_scripts()` - Load admin assets including sortable
- `create_settings_page()` - Plugin settings interface
- **`add_admin_columns()` - Custom admin list columns with drag handles**
- **`make_columns_sortable()` - Enable sorting by custom order**
- **`handle_ajax_reorder()` - Process drag-and-drop reordering**
- **`add_reorder_interface()` - Add drag-and-drop functionality to list table**

### 5.3 Frontend Class
**File:** `includes/class-quick-faq-markup-frontend.php`

**Responsibilities:**
- Frontend FAQ display
- Style loading
- Script enqueueing

**Key Methods:**
- `enqueue_public_scripts()` - Load frontend assets
- `display_faqs()` - Render FAQ output
- `apply_style_template()` - Apply selected style

### 5.4 Schema Class
**File:** `includes/class-quick-faq-markup-schema.php`

**Responsibilities:**
- Generate JSON-LD schema markup
- Validate schema structure
- Output schema to head

**Key Methods:**
- `generate_faq_schema()` - Create FAQ schema array
- `output_schema()` - Add schema to page head
- `validate_schema()` - Ensure proper format

### 5.5 Shortcode Class
**File:** `includes/class-quick-faq-markup-shortcode.php`

**Responsibilities:**
- Register shortcode
- Process shortcode attributes
- Generate FAQ output

**Key Methods:**
- `register_shortcode()` - Register [qfm_faq] shortcode
- `shortcode_handler()` - Process shortcode
- `parse_attributes()` - Handle shortcode parameters

## 6. Frontend Styles (4 Professional Options)

### 6.1 Style Options
1. **Classic List** (`qfm-style-classic`)
2. **Accordion Modern** (`qfm-style-accordion-modern`)
3. **Accordion Minimal** (`qfm-style-accordion-minimal`)
4. **Card Layout** (`qfm-style-cards`)

### 6.2 CSS Structure (2025 Accessibility Enhanced)
```css
/* Base FAQ container with accessibility */
.qfm-faq-container {
    margin: 20px 0;
    font-family: inherit;
}

/* Classic List Style */
.qfm-style-classic .qfm-faq-item {
    margin-bottom: 20px;
    border-left: 3px solid #0073aa;
    padding-left: 15px;
}

.qfm-style-classic .qfm-faq-question {
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.qfm-style-classic .qfm-faq-answer {
    color: #666;
    line-height: 1.6;
}

/* Accordion Modern Style - Enhanced Accessibility */
.qfm-style-accordion-modern .qfm-faq-item {
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    margin-bottom: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.qfm-style-accordion-modern .qfm-faq-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* WCAG 2.1 AA Compliant Button */
.qfm-style-accordion-modern .qfm-faq-question {
    background: #f8f9fa;
    padding: 15px 20px;
    margin: 0;
    cursor: pointer;
    position: relative;
    border-radius: 8px 8px 0 0;
    font-weight: 600;
    color: #2c3e50;
    transition: background-color 0.3s ease;
    border: none;
    width: 100%;
    text-align: left;
    font-size: 16px;
    line-height: 1.4;
}

/* Enhanced focus styles for accessibility */
.qfm-style-accordion-modern .qfm-faq-question:focus {
    outline: 2px solid #005cee;
    outline-offset: 2px;
    background: #e9ecef;
}

.qfm-style-accordion-modern .qfm-faq-question:hover {
    background: #e9ecef;
}

/* ARIA-compliant expand/collapse indicator */
.qfm-style-accordion-modern .qfm-faq-question::after {
    content: '+';
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 20px;
    transition: transform 0.3s ease;
    line-height: 1;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qfm-style-accordion-modern .qfm-faq-item[aria-expanded="true"] .qfm-faq-question::after {
    transform: translateY(-50%) rotate(45deg);
}

.qfm-style-accordion-modern .qfm-faq-answer {
    padding: 20px;
    display: none;
    background: white;
    border-radius: 0 0 8px 8px;
    color: #495057;
    line-height: 1.6;
}

/* Accordion Minimal Style - Enhanced */
.qfm-style-accordion-minimal .qfm-faq-item {
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 0;
}

.qfm-style-accordion-minimal .qfm-faq-question {
    padding: 20px 0;
    margin: 0;
    cursor: pointer;
    position: relative;
    font-weight: 500;
    color: #343a40;
    border: none;
    background: transparent;
    text-align: left;
    width: 100%;
    font-size: 16px;
    line-height: 1.4;
}

/* Enhanced focus for minimal style */
.qfm-style-accordion-minimal .qfm-faq-question:focus {
    outline: 2px solid #005cee;
    outline-offset: 2px;
}

.qfm-style-accordion-minimal .qfm-faq-question::after {
    content: '→';
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    transition: transform 0.3s ease;
    line-height: 1;
}

.qfm-style-accordion-minimal .qfm-faq-item[aria-expanded="true"] .qfm-faq-question::after {
    transform: translateY(-50%) rotate(90deg);
}

.qfm-style-accordion-minimal .qfm-faq-answer {
    padding: 0 0 20px 0;
    display: none;
    color: #6c757d;
    line-height: 1.6;
}

/* Card Layout Style - Enhanced */
.qfm-style-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.qfm-style-cards .qfm-faq-item {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.qfm-style-cards .qfm-faq-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.qfm-style-cards .qfm-faq-item:focus-within {
    outline: 2px solid #005cee;
    outline-offset: 2px;
}

.qfm-style-cards .qfm-faq-question {
    font-weight: 600;
    margin-bottom: 12px;
    color: #2c3e50;
    font-size: 16px;
    line-height: 1.4;
}

.qfm-style-cards .qfm-faq-answer {
    color: #5a6c7d;
    line-height: 1.6;
    margin: 0;
}

/* High Contrast Mode Support */
@media (prefers-contrast: high) {
    .qfm-style-accordion-modern .qfm-faq-question,
    .qfm-style-accordion-minimal .qfm-faq-question {
        border: 2px solid;
    }
    
    .qfm-style-cards .qfm-faq-item {
        border-width: 2px;
    }
}

/* Reduced Motion Support */
@media (prefers-reduced-motion: reduce) {
    .qfm-style-accordion-modern .qfm-faq-item,
    .qfm-style-accordion-modern .qfm-faq-question,
    .qfm-style-accordion-modern .qfm-faq-question::after,
    .qfm-style-accordion-minimal .qfm-faq-question::after,
    .qfm-style-cards .qfm-faq-item {
        transition: none;
    }
}

/* Responsive Design - Enhanced */
@media (max-width: 768px) {
    .qfm-style-cards {
        grid-template-columns: 1fr;
    }
    
    .qfm-style-accordion-modern .qfm-faq-question,
    .qfm-style-accordion-modern .qfm-faq-answer {
        padding: 12px 15px;
    }
    
    /* Larger touch targets for mobile */
    .qfm-style-accordion-modern .qfm-faq-question,
    .qfm-style-accordion-minimal .qfm-faq-question {
        min-height: 44px;
        display: flex;
        align-items: center;
    }
}

/* Screen Reader Only Content */
.qfm-sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
```

## 7. JavaScript Functionality

### 7.1 Frontend JavaScript (2025 Accessibility Enhanced)
**File:** `public/js/quick-faq-markup-public.js`

```javascript
(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize accordion functionality with accessibility
        initAccessibleAccordionFAQs();
    });

    function initAccessibleAccordionFAQs() {
        // Select accordion question buttons
        const $accordionQuestions = $('.qfm-style-accordion-modern .qfm-faq-question, .qfm-style-accordion-minimal .qfm-faq-question');
        
        // Add ARIA attributes for accessibility
        $accordionQuestions.each(function(index) {
            const $question = $(this);
            const $item = $question.closest('.qfm-faq-item');
            const $answer = $item.find('.qfm-faq-answer');
            
            // Generate unique IDs
            const questionId = 'qfm-question-' + index;
            const answerId = 'qfm-answer-' + index;
            
            // Set ARIA attributes
            $question.attr({
                'id': questionId,
                'aria-expanded': 'false',
                'aria-controls': answerId,
                'role': 'button',
                'tabindex': '0'
            });
            
            $answer.attr({
                'id': answerId,
                'aria-labelledby': questionId,
                'role': 'region'
            });
            
            $item.attr('aria-expanded', 'false');
            
            // Add screen reader text
            $question.append('<span class="qfm-sr-only"> (Click to expand)</span>');
        });

        // Handle click events
        $accordionQuestions.on('click', function(e) {
            e.preventDefault();
            toggleAccordionItem($(this));
        });

        // Handle keyboard events for accessibility
        $accordionQuestions.on('keydown', function(e) {
            // Enter or Space key
            if (e.which === 13 || e.which === 32) {
                e.preventDefault();
                toggleAccordionItem($(this));
            }
            
            // Arrow key navigation
            if (e.which === 38 || e.which === 40) { // Up/Down arrows
                e.preventDefault();
                const currentIndex = $accordionQuestions.index(this);
                let nextIndex;
                
                if (e.which === 38) { // Up arrow
                    nextIndex = currentIndex > 0 ? currentIndex - 1 : $accordionQuestions.length - 1;
                } else { // Down arrow
                    nextIndex = currentIndex < $accordionQuestions.length - 1 ? currentIndex + 1 : 0;
                }
                
                $accordionQuestions.eq(nextIndex).focus();
            }
        });
    }

    function toggleAccordionItem($question) {
        const $item = $question.closest('.qfm-faq-item');
        const $answer = $item.find('.qfm-faq-answer');
        const isExpanded = $item.attr('aria-expanded') === 'true';
        
        if (isExpanded) {
            // Collapse
            $item.attr('aria-expanded', 'false');
            $question.attr('aria-expanded', 'false');
            $question.find('.qfm-sr-only').text(' (Click to expand)');
            $answer.slideUp(300, function() {
                // Announce state change to screen readers
                $question.focus();
            });
        } else {
            // Expand (close others first - optional)
            $item.siblings('.qfm-faq-item').each(function() {
                const $siblingItem = $(this);
                const $siblingQuestion = $siblingItem.find('.qfm-faq-question');
                const $siblingAnswer = $siblingItem.find('.qfm-faq-answer');
                
                $siblingItem.attr('aria-expanded', 'false');
                $siblingQuestion.attr('aria-expanded', 'false');
                $siblingQuestion.find('.qfm-sr-only').text(' (Click to expand)');
                $siblingAnswer.slideUp(300);
            });
            
            // Expand current item
            $item.attr('aria-expanded', 'true');
            $question.attr('aria-expanded', 'true');
            $question.find('.qfm-sr-only').text(' (Click to collapse)');
            $answer.slideDown(300, function() {
                // Announce state change to screen readers
                $question.focus();
            });
        }
    }

    // Initialize on AJAX content load
    $(document).on('qfm_content_loaded', function() {
        initAccessibleAccordionFAQs();
    });

})(jQuery);
```

## 8. Shortcode Implementation

### 8.1 Shortcode Syntax (Enhanced with Ordering)
```
[qfm_faq style="accordion-modern" category="" limit="10" order="custom" show_anchors="true"]
```

### 8.2 Shortcode Attributes
- `style` - FAQ display style (classic|accordion-modern|accordion-minimal|cards)
- `category` - Filter by FAQ category
- `limit` - Number of FAQs to display
- `order` - Sort order (custom|date_desc|date_asc|title_asc|title_desc)
- `ids` - Specific FAQ IDs to display
- `show_anchors` - Show permalink anchors (true|false)

### 8.3 Shortcode Handler (Enhanced with Ordering)
```php
public function shortcode_handler( $atts ) {
    $atts = shortcode_atts( array(
        'style'        => 'classic',
        'category'     => '',
        'limit'        => 10,
        'order'        => 'custom', // Default to custom drag-and-drop order
        'ids'          => '',
        'show_anchors' => 'true',
    ), $atts, 'qfm_faq' );

    // Sanitize attributes
    $style = sanitize_text_field( $atts['style'] );
    $category = sanitize_text_field( $atts['category'] );
    $limit = absint( $atts['limit'] );
    
    // Validate order parameter
    $valid_orders = array( 'custom', 'date_desc', 'date_asc', 'title_asc', 'title_desc' );
    $order = in_array( $atts['order'], $valid_orders ) ? $atts['order'] : 'custom';
    
    $show_anchors = filter_var( $atts['show_anchors'], FILTER_VALIDATE_BOOLEAN );
    
    return $this->get_faq_output( $style, $category, $limit, $order, $atts['ids'], $show_anchors );
}

/**
 * Generate FAQ output with enhanced ordering
 */
private function get_faq_output( $style, $category, $limit, $order, $ids, $show_anchors = true ) {
    $query_args = array(
        'category' => $category,
        'limit' => $limit,
        'qfm_order' => $order,
        'ids' => $ids
    );
    
    $faqs = $this->query_faqs( $query_args );
    
    if ( empty( $faqs ) ) {
        return '<p class="qfm-no-faqs">' . esc_html__( 'No FAQs found.', 'quick-faq-markup' ) . '</p>';
    }
    
    // Store FAQs for schema generation
    $this->current_page_faqs = $faqs;
    add_action( 'wp_head', array( $this, 'output_schema_to_head' ), 5 );
    
    return $this->render_faq_output( $faqs, $style, $show_anchors );
}
```

## 9. Schema Markup Implementation

### 9.1 JSON-LD Schema Structure (Enhanced with Anchor URLs)
```php
public function generate_faq_schema( $faqs, $page_url = '' ) {
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array()
    );

    if ( empty( $page_url ) ) {
        $page_url = get_permalink();
    }

    foreach ( $faqs as $index => $faq ) {
        // Generate unique anchor for each FAQ
        $anchor = $this->generate_faq_anchor( $faq['question'], $faq['id'] );
        $full_url = trailingslashit( $page_url ) . '#' . $anchor;
        
        $question_schema = array(
            '@type' => 'Question',
            'name' => wp_strip_all_tags( $faq['question'] ),
            'url' => esc_url( $full_url ), // Direct link to this FAQ
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text' => wp_strip_all_tags( $faq['answer'] ),
                'url' => esc_url( $full_url ) // Same URL for the answer
            )
        );
        
        $schema['mainEntity'][] = $question_schema;
    }

    return $schema;
}

/**
 * Generate SEO-friendly anchor from FAQ question
 */
private function generate_faq_anchor( $question, $faq_id ) {
    // Create SEO-friendly anchor from question
    $anchor = sanitize_title( $question );
    
    // Fallback to ID-based anchor if question is too short
    if ( strlen( $anchor ) < 3 ) {
        $anchor = 'faq-' . absint( $faq_id );
    }
    
    // Ensure uniqueness by prefixing
    $anchor = 'qfm-' . $anchor;
    
    // Limit length for clean URLs
    if ( strlen( $anchor ) > 50 ) {
        $anchor = substr( $anchor, 0, 47 ) . '...';
        $anchor = rtrim( $anchor, '-' );
    }
    
    return $anchor;
}
```

### 9.2 Frontend FAQ Output with Anchors
```php
public function render_faq_output( $faqs, $style = 'classic' ) {
    $output = '<div class="qfm-faq-container qfm-style-' . esc_attr( $style ) . '">';
    
    foreach ( $faqs as $index => $faq ) {
        $anchor = $this->generate_faq_anchor( $faq['question'], $faq['id'] );
        
        $output .= '<div class="qfm-faq-item" id="' . esc_attr( $anchor ) . '">';
        
        if ( in_array( $style, array( 'accordion-modern', 'accordion-minimal' ) ) ) {
            // Accordion styles with proper anchor targeting
            $output .= '<button class="qfm-faq-question" aria-expanded="false">';
            $output .= '<span class="qfm-question-text">' . wp_kses_post( $faq['question'] ) . '</span>';
            $output .= '<span class="qfm-anchor-link">';
            $output .= '<a href="#' . esc_attr( $anchor ) . '" class="qfm-permalink" title="' . esc_attr__( 'Permalink to this FAQ', 'quick-faq-markup' ) . '">';
            $output .= '<span class="qfm-sr-only">' . esc_html__( 'Direct link to this FAQ', 'quick-faq-markup' ) . '</span>';
            $output .= '#</a></span>';
            $output .= '</button>';
            $output .= '<div class="qfm-faq-answer">' . wp_kses_post( $faq['answer'] ) . '</div>';
        } else {
            // Non-accordion styles
            $output .= '<h3 class="qfm-faq-question">';
            $output .= wp_kses_post( $faq['question'] );
            $output .= '<a href="#' . esc_attr( $anchor ) . '" class="qfm-permalink" title="' . esc_attr__( 'Permalink to this FAQ', 'quick-faq-markup' ) . '">#</a>';
            $output .= '</h3>';
            $output .= '<div class="qfm-faq-answer">' . wp_kses_post( $faq['answer'] ) . '</div>';
        }
        
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}
```

### 9.3 Enhanced CSS for Permalink Styling
```css
/* Permalink anchor styling */
.qfm-permalink {
    color: #666;
    text-decoration: none;
    font-size: 0.8em;
    margin-left: 8px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.qfm-faq-item:hover .qfm-permalink,
.qfm-faq-item:focus-within .qfm-permalink {
    opacity: 1;
}

.qfm-permalink:hover,
.qfm-permalink:focus {
    color: #0073aa;
    text-decoration: underline;
}

/* Smooth scroll targeting */
.qfm-faq-item:target {
    scroll-margin-top: 20px;
    animation: qfm-highlight 2s ease-out;
}

@keyframes qfm-highlight {
    0% { background-color: #fff3cd; }
    100% { background-color: transparent; }
}

/* Accordion-specific permalink placement */
.qfm-style-accordion-modern .qfm-anchor-link,
.qfm-style-accordion-minimal .qfm-anchor-link {
    position: absolute;
    right: 50px;
    top: 50%;
    transform: translateY(-50%);
}
```
```

### 9.4 Schema Output with URL Context
```php
public function output_schema_to_head() {
    if ( $this->has_faq_shortcode() || is_singular( 'qfm_faq' ) ) {
        $faqs = $this->get_page_faqs();
        if ( ! empty( $faqs ) ) {
            $current_url = get_permalink();
            $schema = $this->generate_faq_schema( $faqs, $current_url );
            echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
        }
    }
}

/**
 * Detect if page contains FAQ shortcode
 */
private function has_faq_shortcode() {
    global $post;
    
    if ( ! is_object( $post ) || ! isset( $post->post_content ) ) {
        return false;
    }
    
    return has_shortcode( $post->post_content, 'qfm_faq' );
}

/**
 * Handle anchor targeting with JavaScript
 */
public function add_anchor_targeting_script() {
    if ( $this->has_faq_shortcode() || is_singular( 'qfm_faq' ) ) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle anchor targeting on page load
            if (window.location.hash) {
                const target = document.querySelector(window.location.hash);
                if (target && target.classList.contains('qfm-faq-item')) {
                    // If it's an accordion, expand it
                    const question = target.querySelector('.qfm-faq-question');
                    if (question && question.getAttribute('aria-expanded') === 'false') {
                        question.click();
                    }
                    
                    // Smooth scroll to target
                    setTimeout(() => {
                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                }
            }
        });
        </script>
        <?php
    }
}
```

## 10. Admin Interface

### 10.1 Settings Page
- Plugin settings in WordPress admin
- Style preview
- Default style selection
- Import/Export functionality

### 10.2 FAQ Management
- Custom post type in admin menu
- Question/Answer meta boxes
- Category taxonomy
- Bulk actions
- Quick edit support

### 10.3 Meta Box Implementation
```php
public function add_faq_meta_boxes() {
    add_meta_box(
        'qfm-faq-details',
        __( 'FAQ Details', 'quick-faq-markup' ),
        array( $this, 'render_faq_meta_box' ),
        'qfm_faq',
        'normal',
        'high'
    );
}

public function render_faq_meta_box( $post ) {
    wp_nonce_field( 'qfm_faq_meta_box', 'qfm_faq_meta_box_nonce' );
    
    $question = get_post_meta( $post->ID, '_qfm_faq_question', true );
    $answer = get_post_meta( $post->ID, '_qfm_faq_answer', true );
    
    include QUICK_FAQ_MARKUP_PLUGIN_DIR . 'admin/partials/faq-meta-box.php';
}
```

## 11. Security Implementation

### 11.1 Input Sanitization (2025 Standards)
```php
// Sanitize FAQ question
$question = sanitize_textarea_field( wp_unslash( $_POST['qfm_faq_question'] ) );

// Sanitize FAQ answer (allow basic HTML)
$answer = wp_kses_post( wp_unslash( $_POST['qfm_faq_answer'] ) );

// Sanitize shortcode attributes
$style = sanitize_text_field( $atts['style'] );

// Sanitize arrays
$ids = array_map( 'absint', explode( ',', $atts['ids'] ) );
```

### 11.2 Enhanced Nonce Verification (2025 Requirements)
```php
public function save_faq_meta_data( $post_id ) {
    // Check if user can edit posts
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    // Verify nonce (2025 standard: sanitize before verification)
    $nonce = sanitize_text_field( wp_unslash( $_POST['qfm_faq_meta_box_nonce'] ?? '' ) );
    if ( ! wp_verify_nonce( $nonce, 'qfm_faq_meta_box' ) ) {
        wp_die( esc_html__( 'Security verification failed.', 'quick-faq-markup' ) );
    }
    
    // Check autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    
    // Verify post type
    if ( 'qfm_faq' !== get_post_type( $post_id ) ) {
        return;
    }
}
```

### 11.3 Output Escaping (Enhanced)
```php
// Context-aware escaping
echo esc_html( $faq_question );
echo wp_kses_post( $faq_answer );
echo esc_url( $settings_url );
echo esc_attr( $css_class );
echo esc_js( $javascript_var );

// For HTML attributes in form fields
echo '<input type="text" value="' . esc_attr( $value ) . '" />';

// For URLs with additional validation
echo esc_url( $url, array( 'http', 'https' ) );
```

## 12. Internationalization (i18n) - WordPress 6.8+ Standards

### 12.1 Automatic Text Domain Loading (New in WordPress 6.8+)
```php
// NO LONGER REQUIRED - WordPress handles this automatically
// load_plugin_textdomain() calls are deprecated for plugins

// Simply ensure your plugin header includes:
// Text Domain: quick-faq-markup
// Domain Path: /languages

// WordPress will automatically load translations
```

### 12.2 Translation Functions (Enhanced)
```php
// Standard translation functions
__( 'FAQ Question', 'quick-faq-markup' )
_e( 'Add New FAQ', 'quick-faq-markup' )
esc_html__( 'FAQ Settings', 'quick-faq-markup' )
esc_html_e( 'Display Style', 'quick-faq-markup' )

// Context-specific translations
_x( 'Classic', 'FAQ display style', 'quick-faq-markup' )
esc_html_x( 'Cards', 'FAQ layout option', 'quick-faq-markup' )

// Pluralization support
_n( '%s FAQ', '%s FAQs', $count, 'quick-faq-markup' )
sprintf( _n( '%s FAQ', '%s FAQs', $count, 'quick-faq-markup' ), number_format_i18n( $count ) )
```

### 12.3 Translation File Generation
```bash
# Generate POT file using WP-CLI (recommended 2025 method)
wp i18n make-pot . languages/quick-faq-markup.pot

# Or use traditional tools
xgettext --language=PHP --add-comments --sort-output --from-code=UTF-8 -o languages/quick-faq-markup.pot *.php
```

## 13. Performance Optimization

### 13.1 Asset Loading Strategy
- Only load CSS/JS when FAQ shortcode present
- Minified production assets
- Conditional loading based on page content

### 13.2 Database Optimization
- Efficient queries using WP_Query
- Proper indexing on meta fields
- Caching for frequently accessed FAQs

### 13.3 Caching Implementation
```php
public function get_cached_faqs( $args ) {
    $cache_key = 'qfm_faqs_' . md5( serialize( $args ) );
    $faqs = wp_cache_get( $cache_key, 'quick_faq_markup' );
    
    if ( false === $faqs ) {
        $faqs = $this->query_faqs( $args );
        wp_cache_set( $cache_key, $faqs, 'quick_faq_markup', HOUR_IN_SECONDS );
    }
    
    return $faqs;
}
```

## 14. Uninstall Process

### 14.1 Uninstall.php Implementation
```php
<?php
// Prevent direct access
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove all FAQ posts
$faqs = get_posts( array(
    'post_type' => 'qfm_faq',
    'numberposts' => -1,
    'post_status' => 'any'
) );

foreach ( $faqs as $faq ) {
    wp_delete_post( $faq->ID, true );
}

// Remove plugin options
delete_option( 'quick_faq_markup_settings' );
delete_option( 'quick_faq_markup_version' );

// Clear any cached data
wp_cache_flush();
```

## 15. WordPress Repository Requirements

### 15.1 readme.txt Format
```
```
=== Quick FAQ Markup ===
Contributors: 365i
Tags: faq, schema, structured data, seo, snippets, accessibility, wcag
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight FAQ plugin with proper schema markup for Google Featured Snippets and full WCAG 2.1 AA accessibility compliance.

== Description ==

Quick FAQ Markup creates beautiful, professional FAQ sections with proper JSON-LD schema markup to help your content appear in Google Featured Snippets and voice search results. Fully compliant with WCAG 2.1 AA accessibility standards and the European Accessibility Act 2025.

Features:
* 4 professional display styles with full accessibility support
* **Intuitive drag-and-drop FAQ reordering** in admin interface
* **Manual order control** with numeric inputs
* Automatic JSON-LD schema markup with anchor URLs (Google's 2025 recommendation)
* Direct-linkable FAQs with unique anchors for each question
* WCAG 2.1 AA compliant with keyboard navigation
* Enhanced SEO with deep-linking capabilities
* **Multiple ordering options**: custom, date, alphabetical
* Smooth scroll targeting from external links (e.g., Google search results)
* Lightweight and optimized for Core Web Vitals
* Easy shortcode implementation with flexible ordering
* Mobile responsive with enhanced touch targets
* High contrast and reduced motion support
* European Accessibility Act 2025 compliant
* PHP 8.0+ optimized
* Permalink anchors with visual highlighting

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/quick-faq-markup/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Create FAQs in the admin panel
4. Use the [qfm_faq] shortcode to display them

== Frequently Asked Questions ==

= How do I display FAQs? =
Use the shortcode [qfm_faq] in any post, page, or widget area.

= Does this help with SEO? =
Yes! The plugin generates proper FAQ schema markup that helps Google understand your content and potentially show it in Featured Snippets.

= Is this plugin accessible? =
Absolutely! This plugin is fully compliant with WCAG 2.1 AA standards and includes keyboard navigation, screen reader support, and high contrast mode compatibility.

= What PHP version do I need? =
PHP 8.0 or higher is required for optimal performance and security.

== Changelog ==

= 1.0.0 =
* Initial release
* WCAG 2.1 AA accessibility compliance
* JSON-LD schema markup
* 4 professional display styles
* Full keyboard navigation support
* European Accessibility Act 2025 compliance
```
```

### 15.2 Plugin Review Requirements Checklist
- [ ] No PHP errors or warnings
- [ ] Follows WordPress coding standards
- [ ] Proper sanitization and validation
- [ ] Security best practices implemented
- [ ] GPL compatible license
- [ ] No trademark violations
- [ ] Professional code quality
- [ ] Comprehensive documentation
- [ ] Tested with latest WordPress version
- [ ] Mobile responsive design
- [ ] Accessibility compliant
- [ ] Translation ready

## 16. Testing Strategy

### 16.1 Unit Testing
- Test all public methods
- Mock WordPress functions
- Validate schema output
- Test shortcode parsing

### 16.2 Integration Testing
- Test with various themes
- Plugin conflict testing
- Performance testing
- Cross-browser testing

### 16.3 User Acceptance Testing
- Admin interface usability
- **Drag-and-drop reordering functionality**:
  - Smooth drag-and-drop experience
  - Visual feedback during dragging
  - Immediate order updates via AJAX
  - Manual order input functionality
  - Order persistence across page loads
- Frontend display verification
- **Anchor link functionality testing**:
  - Direct links to FAQs work correctly
  - Accordion FAQs expand when targeted via anchor
  - Smooth scrolling to FAQ items
  - Schema URLs include correct anchors
- **FAQ ordering verification**:
  - Custom order displays correctly on frontend
  - Different order options work via shortcode
  - Order maintained in schema markup
- Schema validation tools (Google Rich Results Test)
- SEO testing tools
- **Cross-browser anchor testing**:
  - Chrome, Firefox, Safari, Edge
  - Mobile browsers
  - Screen reader compatibility with anchors
- **Admin reordering testing**:
  - Drag-and-drop works across different browsers
  - Mobile admin reordering (touch devices)
  - Large numbers of FAQs (100+ items)

## 17. Launch Preparation

### 17.1 Pre-Launch Checklist
- [ ] Code review completed
- [ ] Security audit passed
- [ ] Performance testing completed
- [ ] Documentation finalized
- [ ] Translation template generated
- [ ] Plugin assets created (banners, icons)
- [ ] WordPress.org account prepared

### 17.2 Post-Launch Monitoring
- Download statistics tracking
- User support preparation
- Bug report monitoring
- Feature request collection

## 18. FAQ Reordering System

### 18.1 Custom Post Type with Ordering Support
```php
public function register_post_type() {
    $args = array(
        'labels' => array(
            'name' => __( 'FAQs', 'quick-faq-markup' ),
            'singular_name' => __( 'FAQ', 'quick-faq-markup' ),
            'add_new' => __( 'Add New FAQ', 'quick-faq-markup' ),
            'add_new_item' => __( 'Add New FAQ', 'quick-faq-markup' ),
            'edit_item' => __( 'Edit FAQ', 'quick-faq-markup' ),
            'new_item' => __( 'New FAQ', 'quick-faq-markup' ),
            'view_item' => __( 'View FAQ', 'quick-faq-markup' ),
            'search_items' => __( 'Search FAQs', 'quick-faq-markup' ),
            'not_found' => __( 'No FAQs found', 'quick-faq-markup' ),
            'not_found_in_trash' => __( 'No FAQs found in trash', 'quick-faq-markup' ),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-editor-help',
        'menu_position' => 25,
        'supports' => array( 'title', 'page-attributes' ), // page-attributes enables menu_order
        'hierarchical' => false,
        'capability_type' => 'post',
        'has_archive' => false,
        'rewrite' => false,
        'query_var' => false,
    );
    
    register_post_type( 'qfm_faq', $args );
}
```

### 18.2 Admin List Table Enhancements
```php
/**
 * Add custom columns to FAQ list table
 */
public function add_admin_columns( $columns ) {
    // Remove date column and add our custom columns
    unset( $columns['date'] );
    
    // Add drag handle column at the beginning
    $new_columns = array();
    $new_columns['drag_handle'] = '<span class="dashicons dashicons-menu" title="' . esc_attr__( 'Drag to reorder', 'quick-faq-markup' ) . '"></span>';
    
    // Add existing columns
    $new_columns = array_merge( $new_columns, $columns );
    
    // Add our custom columns
    $new_columns['faq_question'] = __( 'Question', 'quick-faq-markup' );
    $new_columns['faq_order'] = __( 'Order', 'quick-faq-markup' );
    $new_columns['faq_shortcode'] = __( 'Shortcode ID', 'quick-faq-markup' );
    $new_columns['date'] = __( 'Date', 'quick-faq-markup' );
    
    return $new_columns;
}

/**
 * Populate custom column content
 */
public function populate_admin_columns( $column, $post_id ) {
    switch ( $column ) {
        case 'drag_handle':
            echo '<span class="qfm-drag-handle dashicons dashicons-menu" data-post-id="' . esc_attr( $post_id ) . '"></span>';
            break;
            
        case 'faq_question':
            $question = get_post_meta( $post_id, '_qfm_faq_question', true );
            echo esc_html( wp_trim_words( $question, 10 ) );
            break;
            
        case 'faq_order':
            $order = get_post_field( 'menu_order', $post_id );
            echo '<input type="number" class="small-text qfm-order-input" value="' . esc_attr( $order ) . '" data-post-id="' . esc_attr( $post_id ) . '" min="0" />';
            break;
            
        case 'faq_shortcode':
            echo '<code>[qfm_faq ids="' . esc_attr( $post_id ) . '"]</code>';
            break;
    }
}

/**
 * Make custom columns sortable
 */
public function make_columns_sortable( $sortable_columns ) {
    $sortable_columns['faq_order'] = 'menu_order';
    return $sortable_columns;
}
```

### 18.3 Drag-and-Drop JavaScript
**File:** `admin/js/quick-faq-markup-admin.js`

```javascript
(function($) {
    'use strict';

    $(document).ready(function() {
        initFAQReordering();
        initOrderInputs();
    });

    function initFAQReordering() {
        const $table = $('.wp-list-table tbody');
        
        if ($table.length && $('.qfm-drag-handle').length) {
            $table.sortable({
                handle: '.qfm-drag-handle',
                cursor: 'move',
                axis: 'y',
                tolerance: 'pointer',
                helper: function(e, ui) {
                    ui.children().each(function() {
                        $(this).width($(this).width());
                    });
                    return ui;
                },
                placeholder: {
                    element: function(currentItem) {
                        return $('<tr><td colspan="' + currentItem.children().length + '" class="qfm-placeholder">Drop here</td></tr>')[0];
                    },
                    update: function(container, p) {
                        return;
                    }
                },
                start: function(e, ui) {
                    ui.item.addClass('qfm-dragging');
                    $('.qfm-placeholder').height(ui.item.height());
                },
                stop: function(e, ui) {
                    ui.item.removeClass('qfm-dragging');
                },
                update: function(e, ui) {
                    updateFAQOrder();
                }
            });

            // Add visual feedback
            $table.addClass('qfm-sortable');
        }
    }

    function updateFAQOrder() {
        const order = [];
        
        $('.wp-list-table tbody tr').each(function(index) {
            const postId = $(this).find('.qfm-drag-handle').data('post-id');
            if (postId) {
                order.push({
                    id: postId,
                    order: index + 1
                });
                
                // Update the order input field
                $(this).find('.qfm-order-input').val(index + 1);
            }
        });

        // Send AJAX request to save new order
        $.ajax({
            url: qfmAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'qfm_update_faq_order',
                nonce: qfmAdmin.nonce,
                order: order
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', qfmAdmin.messages.orderUpdated);
                } else {
                    showNotice('error', qfmAdmin.messages.orderError);
                }
            },
            error: function() {
                showNotice('error', qfmAdmin.messages.orderError);
            }
        });
    }

    function initOrderInputs() {
        // Handle manual order input changes
        $(document).on('change', '.qfm-order-input', function() {
            const $input = $(this);
            const postId = $input.data('post-id');
            const newOrder = parseInt($input.val()) || 0;

            $.ajax({
                url: qfmAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'qfm_update_single_faq_order',
                    nonce: qfmAdmin.nonce,
                    post_id: postId,
                    order: newOrder
                },
                success: function(response) {
                    if (response.success) {
                        // Reload the page to reflect new order
                        location.reload();
                    } else {
                        showNotice('error', qfmAdmin.messages.orderError);
                    }
                }
            });
        });
    }

    function showNotice(type, message) {
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut();
        }, 3000);
    }

})(jQuery);
```

### 18.4 AJAX Handlers for Reordering
```php
/**
 * Handle bulk FAQ reordering via AJAX
 */
public function handle_ajax_reorder() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'qfm_admin_nonce' ) ) {
        wp_die( esc_html__( 'Security verification failed.', 'quick-faq-markup' ) );
    }

    // Check capabilities
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( __( 'Insufficient permissions.', 'quick-faq-markup' ) );
    }

    $order = isset( $_POST['order'] ) ? (array) $_POST['order'] : array();

    if ( empty( $order ) ) {
        wp_send_json_error( __( 'No order data received.', 'quick-faq-markup' ) );
    }

    foreach ( $order as $item ) {
        $post_id = absint( $item['id'] );
        $menu_order = absint( $item['order'] );

        if ( $post_id && get_post_type( $post_id ) === 'qfm_faq' ) {
            wp_update_post( array(
                'ID' => $post_id,
                'menu_order' => $menu_order
            ) );
        }
    }

    wp_send_json_success( __( 'FAQ order updated successfully.', 'quick-faq-markup' ) );
}

/**
 * Handle single FAQ order update
 */
public function handle_single_faq_order() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'qfm_admin_nonce' ) ) {
        wp_die( esc_html__( 'Security verification failed.', 'quick-faq-markup' ) );
    }

    // Check capabilities
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( __( 'Insufficient permissions.', 'quick-faq-markup' ) );
    }

    $post_id = absint( $_POST['post_id'] );
    $order = absint( $_POST['order'] );

    if ( ! $post_id || get_post_type( $post_id ) !== 'qfm_faq' ) {
        wp_send_json_error( __( 'Invalid FAQ ID.', 'quick-faq-markup' ) );
    }

    $result = wp_update_post( array(
        'ID' => $post_id,
        'menu_order' => $order
    ) );

    if ( $result ) {
        wp_send_json_success( __( 'FAQ order updated.', 'quick-faq-markup' ) );
    } else {
        wp_send_json_error( __( 'Failed to update FAQ order.', 'quick-faq-markup' ) );
    }
}
```

### 18.5 Admin CSS for Reordering Interface
**File:** `admin/css/quick-faq-markup-admin.css`

```css
/* FAQ Reordering Styles */
.qfm-sortable .qfm-drag-handle {
    cursor: move;
    color: #666;
    padding: 8px;
    display: inline-block;
}

.qfm-sortable .qfm-drag-handle:hover {
    color: #0073aa;
}

.qfm-sortable tr.qfm-dragging {
    opacity: 0.7;
    background-color: #f0f8ff;
}

.qfm-placeholder {
    background-color: #e1f5fe !important;
    border: 2px dashed #0073aa !important;
    text-align: center;
    color: #0073aa;
    font-weight: 600;
}

.qfm-order-input {
    width: 60px;
    text-align: center;
}

/* Drag handle column */
.column-drag_handle {
    width: 40px;
    text-align: center;
}

.column-faq_order {
    width: 80px;
}

.column-faq_shortcode {
    width: 180px;
}

/* Reorder instructions */
.qfm-reorder-instructions {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 12px;
    margin: 10px 0;
    font-size: 13px;
}

.qfm-reorder-instructions .dashicons {
    color: #0073aa;
    margin-right: 5px;
}

/* Loading state */
.qfm-sortable.qfm-updating {
    opacity: 0.6;
    pointer-events: none;
}

/* Success/Error notices */
.notice.qfm-notice {
    margin-top: 10px;
}
```

### 18.6 Frontend Query Modifications
```php
/**
 * Modify FAQ queries to respect menu_order
 */
public function query_faqs( $args = array() ) {
    $defaults = array(
        'post_type' => 'qfm_faq',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'meta_query' => array(),
    );

    $args = wp_parse_args( $args, $defaults );

    // Handle specific ordering requests
    if ( isset( $args['qfm_order'] ) ) {
        switch ( $args['qfm_order'] ) {
            case 'date_desc':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
            case 'date_asc':
                $args['orderby'] = 'date';
                $args['order'] = 'ASC';
                break;
            case 'title_asc':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
            case 'title_desc':
                $args['orderby'] = 'title';
                $args['order'] = 'DESC';
                break;
            case 'custom':
            default:
                $args['orderby'] = 'menu_order';
                $args['order'] = 'ASC';
                break;
        }
    }

    return get_posts( $args );
}
```

## 19. Future Enhancement Roadmap

### 19.1 Version 1.1 Features
- FAQ categories/grouping with drag-and-drop between categories
- Search functionality within FAQ collections
- Analytics integration for FAQ performance tracking
- Bulk import/export functionality
- FAQ templates and quick creation tools

### 19.2 Version 1.2 Features
- Custom CSS editor with live preview
- Additional display styles (timeline, grid, masonry)
- FAQ voting system with analytics
- Advanced schema options (organization markup)
- Multi-language support with WPML integration
- FAQ usage analytics and popular questions tracking

### 19.3 Version 1.3 Features
- FAQ chatbot integration
- Advanced search with filters
- FAQ content suggestions based on site content
- Integration with help desk systems
- FAQ performance optimization recommendations

This implementation plan provides a comprehensive roadmap for creating a professional, repository-ready WordPress FAQ plugin that meets all 2025 coding standards, accessibility requirements, and user experience expectations with robust reordering capabilities.