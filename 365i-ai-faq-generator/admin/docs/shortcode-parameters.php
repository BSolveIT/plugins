<?php
/**
 * Shortcode Parameters Documentation
 *
 * Comprehensive documentation for all available shortcode parameters
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/admin/docs
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="faq-ai-documentation shortcode-parameters">
    <div class="faq-ai-doc-header">
        <h1><?php _e('FAQ AI Generator - Shortcode Parameters', 'faq-ai-generator'); ?></h1>
        <p class="faq-ai-doc-description"><?php _e('A complete reference for all available shortcode parameters to customize your FAQ display.', 'faq-ai-generator'); ?></p>
    </div>
    
    <div class="faq-ai-doc-navigation">
        <ul>
            <li><a href="#basic-usage"><?php _e('Basic Usage', 'faq-ai-generator'); ?></a></li>
            <li><a href="#appearance"><?php _e('Appearance Parameters', 'faq-ai-generator'); ?></a></li>
            <li><a href="#behavior"><?php _e('Behavior Parameters', 'faq-ai-generator'); ?></a></li>
            <li><a href="#content"><?php _e('Content Parameters', 'faq-ai-generator'); ?></a></li>
            <li><a href="#advanced"><?php _e('Advanced Parameters', 'faq-ai-generator'); ?></a></li>
            <li><a href="#examples"><?php _e('Examples', 'faq-ai-generator'); ?></a></li>
        </ul>
    </div>
    
    <div class="faq-ai-doc-section" id="basic-usage">
        <h2><?php _e('Basic Usage', 'faq-ai-generator'); ?></h2>
        <p><?php _e('The simplest way to use the FAQ AI Generator shortcode is:', 'faq-ai-generator'); ?></p>
        <pre><code>[ai_faq_generator]</code></pre>
        <p><?php _e('This will display the FAQ editor with default settings. To customize the appearance and behavior, you can add parameters to the shortcode.', 'faq-ai-generator'); ?></p>
    </div>
    
    <div class="faq-ai-doc-section" id="appearance">
        <h2><?php _e('Appearance Parameters', 'faq-ai-generator'); ?></h2>
        <p><?php _e('These parameters control the visual appearance of the FAQ display.', 'faq-ai-generator'); ?></p>
        
        <table class="faq-ai-doc-table">
            <thead>
                <tr>
                    <th><?php _e('Parameter', 'faq-ai-generator'); ?></th>
                    <th><?php _e('Description', 'faq-ai-generator'); ?></th>
                    <th><?php _e('Default', 'faq-ai-generator'); ?></th>
                    <th><?php _e('Options', 'faq-ai-generator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>theme</code></td>
                    <td><?php _e('The visual theme for the FAQ display.', 'faq-ai-generator'); ?></td>
                    <td><code>default</code></td>
                    <td><code>default</code>, <code>minimal</code>, <code>modern</code>, <code>classic</code>, <code>custom</code></td>
                </tr>
                <tr>
                    <td><code>primary_color</code></td>
                    <td><?php _e('The primary color for buttons and accents.', 'faq-ai-generator'); ?></td>
                    <td><code>#4a6bef</code></td>
                    <td><?php _e('Any valid hex color code', 'faq-ai-generator'); ?></td>
                </tr>
                <tr>
                    <td><code>secondary_color</code></td>
                    <td><?php _e('The secondary color for highlights and secondary elements.', 'faq-ai-generator'); ?></td>
                    <td><code>#1abc9c</code></td>
                    <td><?php _e('Any valid hex color code', 'faq-ai-generator'); ?></td>
                </tr>
                <tr>
                    <td><code>text_color</code></td>
                    <td><?php _e('The color for text content.', 'faq-ai-generator'); ?></td>
                    <td><code>#333333</code></td>
                    <td><?php _e('Any valid hex color code', 'faq-ai-generator'); ?></td>
                </tr>
                <tr>
                    <td><code>background_color</code></td>
                    <td><?php _e('The background color for the FAQ container.', 'faq-ai-generator'); ?></td>
                    <td><code>#ffffff</code></td>
                    <td><?php _e('Any valid hex color code', 'faq-ai-generator'); ?></td>
                </tr>
                <tr>
                    <td><code>border_radius</code></td>
                    <td><?php _e('The border radius for elements (in pixels).', 'faq-ai-generator'); ?></td>
                    <td><code>4</code></td>
                    <td><?php _e('Any integer value', 'faq-ai-generator'); ?></td>
                </tr>
                <tr>
                    <td><code>font_family</code></td>
                    <td><?php _e('The font family for text content.', 'faq-ai-generator'); ?></td>
                    <td><code>inherit</code></td>
                    <td><?php _e('Any valid CSS font family', 'faq-ai-generator'); ?></td>
                </tr>
                <tr>
                    <td><code>icon_style</code></td>
                    <td><?php _e('The style of icons used in the FAQ.', 'faq-ai-generator'); ?></td>
                    <td><code>default</code></td>
                    <td><code>default</code>, <code>minimal</code>, <code>rounded</code>, <code>square</code></td>
                </tr>
                <tr>
                    <td><code>animation</code></td>
                    <td><?php _e('The animation style for expanding/collapsing FAQs.', 'faq-ai-generator'); ?></td>
                    <td><code>fade</code></td>
                    <td><code>none</code>, <code>fade</code>, <code>slide</code>, <code>bounce</code></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="faq-ai-doc-section" id="behavior">
        <h2><?php _e('Behavior Parameters', 'faq-ai-generator'); ?></h2>
        <p><?php _e('These parameters control how the FAQ behaves and interacts with users.', 'faq-ai-generator'); ?></p>
        
        <table class="faq-ai-doc-table">
            <thead>
                <tr>
                    <th><?php _e('Parameter', 'faq-ai-generator'); ?></th>
                    <th><?php _e('Description', 'faq-ai-generator'); ?></th>
                    <th><?php _e('Default', 'faq-ai-generator'); ?></th>
                    <th><?php _e('Options', 'faq-ai-generator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>initial_mode</code></td>
                    <td><?php _e('The initial mode when loading the FAQ editor.', 'faq-ai-generator'); ?></td>
                    <td><code>manual</code></td>
                    <td><code>manual</code>, <code>topic</code>, <code>url</code>, <code>template</code></td>
                </tr>
                <tr>
                    <td><code>collapse_others</code></td>
                    <td><?php _e('Whether to collapse other FAQs when one is expanded.', 'faq-ai-generator'); ?></td>
                    <td><code>false</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>show_search</code></td>
                    <td><?php _e('Whether to show the search box.', 'faq-ai-generator'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>show_seo</code></td>
                    <td><?php _e('Whether to show the SEO score display.', 'faq-ai-generator'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>show_export</code></td>
                    <td><?php _e('Whether to show the export options.', 'faq-ai-generator'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>show_ai_buttons</code></td>
                    <td><?php _e('Whether to show the AI enhancement buttons.', 'faq-ai-generator'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>editable</code></td>
                    <td><?php _e('Whether the FAQs are editable by users.', 'faq-ai-generator'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>auto_save</code></td>
                    <td><?php _e('Whether to automatically save changes.', 'faq-ai-generator'); ?></td>
                    <td><code>true</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
                <tr>
                    <td><code>save_interval</code></td>
                    <td><?php _e('The interval for auto-saving (in seconds).', 'faq-ai-generator'); ?></td>
                    <td><code>30</code></td>
                    <td><?php _e('Any positive integer', 'faq-ai-generator'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="faq-ai-doc-section" id="content">
        <h2><?php _e('Content Parameters', 'faq-ai-generator'); ?></h2>
        <p><?php _e('These parameters control the content of the FAQ display.', 'faq-ai-generator'); ?></p>
        
        <table class="faq-ai-doc-table">
            <thead>
                <tr>
                    <th><?php _e('Parameter', 'faq-ai-generator'); ?></th>
                    <th><?php _e('Description', 'faq-ai-generator'); ?></th>
                    <th><?php _e('Default', 'faq-ai-generator'); ?></th>
                    <th><?php _e('Options', 'faq-ai-generator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>faq_count</code></td>
                    <td><?php _e('The initial number of empty FAQ items to display.', 'faq-ai-generator'); ?></td>
                    <td><code>3</code></td>
                    <td><?php _e('Any positive integer', 'faq-ai-generator'); ?></td>
                </tr>
                <tr>
                    <td><code>max_faqs</code></td>
                    <td><?php _e('The maximum number of FAQs allowed.', 'faq-ai-generator'); ?></td>
                    <td><code>20</code></td>
                    <td><?php _e('Any positive integer', 'faq-ai-generator'); ?></td>
                </tr>
                <tr>
                    <td><code>template_category</code></td>
                    <td><?php _e('The default template category to use.', 'faq-ai-generator'); ?></td>
                    <td><code>general</code></td>
                    <td><code>general</code>, <code>business</code>, <code>product</code>, <code>service</code>, <code>technical</code></td>
                </tr>
                <tr>
                    <td><code>default_question</code></td>
                    <td><?php _e('The default question text for new FAQs.', 'faq-ai-generator'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('Any text string', 'faq-ai-generator'); ?></td>
                </tr>
                <tr>
                    <td><code>default_answer</code></td>
                    <td><?php _e('The default answer text for new FAQs.', 'faq-ai-generator'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('Any text string', 'faq-ai-generator'); ?></td>
                </tr>
                <tr>
                    <td><code>schema_type</code></td>
                    <td><?php _e('The default schema type for export.', 'faq-ai-generator'); ?></td>
                    <td><code>json-ld</code></td>
                    <td><code>json-ld</code>, <code>microdata</code>, <code>rdfa</code>, <code>html</code></td>
                </tr>
                <tr>
                    <td><code>base_url</code></td>
                    <td><?php _e('The base URL for schema generation.', 'faq-ai-generator'); ?></td>
                    <td><?php _e('Current page URL', 'faq-ai-generator'); ?></td>
                    <td><?php _e('Any valid URL', 'faq-ai-generator'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="faq-ai-doc-section" id="advanced">
        <h2><?php _e('Advanced Parameters', 'faq-ai-generator'); ?></h2>
        <p><?php _e('These parameters provide advanced customization options.', 'faq-ai-generator'); ?></p>
        
        <table class="faq-ai-doc-table">
            <thead>
                <tr>
                    <th><?php _e('Parameter', 'faq-ai-generator'); ?></th>
                    <th><?php _e('Description', 'faq-ai-generator'); ?></th>
                    <th><?php _e('Default', 'faq-ai-generator'); ?></th>
                    <th><?php _e('Options', 'faq-ai-generator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>storage_key</code></td>
                    <td><?php _e('The key used for localStorage to save FAQs.', 'faq-ai-generator'); ?></td>
                    <td><?php _e('Based on page ID', 'faq-ai-generator'); ?></td>
                    <td><?php _e('Any string', 'faq-ai-generator'); ?></td>
                </tr>
                <tr>
                    <td><code>custom_css</code></td>
                    <td><?php _e('Custom CSS to apply to the FAQ display.', 'faq-ai-generator'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('Any valid CSS', 'faq-ai-generator'); ?></td>
                </tr>
                <tr>
                    <td><code>custom_js</code></td>
                    <td><?php _e('Custom JavaScript to apply to the FAQ display.', 'faq-ai-generator'); ?></td>
                    <td><code>''</code> (empty)</td>
                    <td><?php _e('Any valid JavaScript', 'faq-ai-generator'); ?></td>
                </tr>
                <tr>
                    <td><code>rate_limit</code></td>
                    <td><?php _e('The rate limit for AI requests (per minute).', 'faq-ai-generator'); ?></td>
                    <td><code>10</code></td>
                    <td><?php _e('Any positive integer', 'faq-ai-generator'); ?></td>
                </tr>
                <tr>
                    <td><code>cache_duration</code></td>
                    <td><?php _e('The duration to cache AI responses (in minutes).', 'faq-ai-generator'); ?></td>
                    <td><code>60</code></td>
                    <td><?php _e('Any positive integer', 'faq-ai-generator'); ?></td>
                </tr>
                <tr>
                    <td><code>debug_mode</code></td>
                    <td><?php _e('Whether to enable debug mode.', 'faq-ai-generator'); ?></td>
                    <td><code>false</code></td>
                    <td><code>true</code>, <code>false</code></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="faq-ai-doc-section" id="examples">
        <h2><?php _e('Examples', 'faq-ai-generator'); ?></h2>
        <p><?php _e('Here are some examples of how to use the shortcode with different parameters:', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Basic FAQ with Custom Colors', 'faq-ai-generator'); ?></h3>
        <pre><code>[ai_faq_generator primary_color="#ff5722" secondary_color="#2196f3" text_color="#212121" background_color="#f5f5f5"]</code></pre>
        
        <h3><?php _e('Read-Only FAQ Display', 'faq-ai-generator'); ?></h3>
        <pre><code>[ai_faq_generator editable="false" show_ai_buttons="false" show_export="false" show_seo="false"]</code></pre>
        
        <h3><?php _e('FAQ with Topic Generator Mode', 'faq-ai-generator'); ?></h3>
        <pre><code>[ai_faq_generator initial_mode="topic" faq_count="5" template_category="business"]</code></pre>
        
        <h3><?php _e('FAQ with Custom Theme and Animation', 'faq-ai-generator'); ?></h3>
        <pre><code>[ai_faq_generator theme="modern" animation="slide" border_radius="8" icon_style="rounded"]</code></pre>
        
        <h3><?php _e('FAQ with Advanced Customization', 'faq-ai-generator'); ?></h3>
        <pre><code>[ai_faq_generator 
    theme="custom" 
    primary_color="#4a6bef" 
    secondary_color="#1abc9c" 
    text_color="#333333" 
    background_color="#ffffff" 
    border_radius="4" 
    font_family="'Roboto', sans-serif" 
    icon_style="minimal" 
    animation="fade" 
    initial_mode="manual" 
    collapse_others="true" 
    show_search="true" 
    show_seo="true" 
    show_export="true" 
    show_ai_buttons="true" 
    editable="true" 
    auto_save="true" 
    save_interval="30" 
    faq_count="3" 
    max_faqs="20" 
    template_category="general" 
    schema_type="json-ld" 
    rate_limit="10" 
    cache_duration="60" 
    debug_mode="false"
]</code></pre>
        
        <div class="faq-ai-doc-note">
            <p><strong><?php _e('Note:', 'faq-ai-generator'); ?></strong> <?php _e('When using multiple parameters, make sure to separate them with spaces, not commas.', 'faq-ai-generator'); ?></p>
        </div>
    </div>
</div>