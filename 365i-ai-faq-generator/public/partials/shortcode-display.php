<?php
/**
 * Display template for the FAQ AI Generator shortcode
 *
 * This template displays the frontend interface for the FAQ generator
 * including the editor, controls, and preview areas
 *
 * @link       https://365i.com
 * @since      1.0.0
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Extract shortcode attributes
$theme = $atts['theme'];
$initial_mode = $atts['initial_mode'];
$faq_count = intval($atts['faq_count']);
$show_seo = filter_var($atts['show_seo'], FILTER_VALIDATE_BOOLEAN);
?>

<div id="faq-ai-generator" class="faq-ai-container faq-ai-theme-<?php echo esc_attr($theme); ?>" data-theme="<?php echo esc_attr($theme); ?>" data-count="<?php echo esc_attr($faq_count); ?>">
    
    <!-- Loading Overlay -->
    <div class="faq-ai-loading-overlay">
        <div class="faq-ai-spinner"></div>
        <div class="faq-ai-loading-message">Loading...</div>
    </div>
    
    <!-- Notifications Area -->
    <div class="faq-ai-notifications"></div>
    
    <!-- Main Tabs -->
    <div class="faq-ai-tabs">
        <div class="faq-ai-tab-list">
            <div class="faq-ai-tab active" data-tab="editor"><?php _e('Editor', 'faq-ai-generator'); ?></div>
            <div class="faq-ai-tab" data-tab="settings"><?php _e('Settings', 'faq-ai-generator'); ?></div>
            <div class="faq-ai-tab" data-tab="export"><?php _e('Export', 'faq-ai-generator'); ?></div>
        </div>
        
        <!-- Editor Tab -->
        <div id="faq-ai-editor-panel" class="faq-ai-tab-panel active">
            <!-- Toolbar -->
            <div class="faq-ai-toolbar">
                <div class="faq-ai-toolbar-group">
                    <button id="faq-ai-add-faq" class="faq-ai-button primary"><?php _e('Add FAQ', 'faq-ai-generator'); ?></button>
                    <button id="faq-ai-import" class="faq-ai-button"><?php _e('Import', 'faq-ai-generator'); ?></button>
                    <button id="faq-ai-templates" class="faq-ai-button"><?php _e('Templates', 'faq-ai-generator'); ?></button>
                    <button id="faq-ai-fetch-url" class="faq-ai-button"><?php _e('Fetch URL', 'faq-ai-generator'); ?></button>
                </div>
            </div>
            
            <!-- URL Import Panel -->
            <div class="faq-ai-url-import-panel faq-ai-panel">
                <div class="faq-ai-panel-header">
                    <h3><?php _e('Import from URL', 'faq-ai-generator'); ?></h3>
                    <button class="faq-ai-url-close faq-ai-button-icon">&times;</button>
                </div>
                <div class="faq-ai-panel-body">
                    <div class="faq-ai-url-input-group">
                        <input type="text" id="faq-ai-url-input" placeholder="<?php _e('Enter URL to fetch FAQs from', 'faq-ai-generator'); ?>" class="faq-ai-input">
                        <button id="faq-ai-fetch-url-submit" class="faq-ai-button primary"><?php _e('Fetch', 'faq-ai-generator'); ?></button>
                    </div>
                    <div id="faq-ai-url-results" class="faq-ai-url-results"></div>
                </div>
            </div>
            
            <!-- Templates Panel -->
            <div class="faq-ai-templates-panel faq-ai-panel">
                <div class="faq-ai-panel-header">
                    <h3><?php _e('FAQ Templates', 'faq-ai-generator'); ?></h3>
                    <button class="faq-ai-templates-close faq-ai-button-icon">&times;</button>
                </div>
                <div class="faq-ai-panel-body">
                    <div class="faq-ai-template-grid">
                        <div class="faq-ai-template-card" data-template="business">
                            <h4><?php _e('Business', 'faq-ai-generator'); ?></h4>
                            <p><?php _e('General business FAQs including hours, refunds, and contact information.', 'faq-ai-generator'); ?></p>
                        </div>
                        <div class="faq-ai-template-card" data-template="product">
                            <h4><?php _e('Product', 'faq-ai-generator'); ?></h4>
                            <p><?php _e('Product-specific FAQs about warranty, returns, and specifications.', 'faq-ai-generator'); ?></p>
                        </div>
                        <div class="faq-ai-template-card" data-template="service">
                            <h4><?php _e('Service', 'faq-ai-generator'); ?></h4>
                            <p><?php _e('Service-based FAQs about offerings, pricing, and packages.', 'faq-ai-generator'); ?></p>
                        </div>
                        <div class="faq-ai-template-card" data-template="support">
                            <h4><?php _e('Support', 'faq-ai-generator'); ?></h4>
                            <p><?php _e('Technical support FAQs for troubleshooting and account help.', 'faq-ai-generator'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Empty State -->
            <div class="faq-ai-empty-state">
                <div class="faq-ai-empty-icon">📋</div>
                <h3><?php _e('No FAQs Yet', 'faq-ai-generator'); ?></h3>
                <p><?php _e('Get started by adding your first FAQ or choosing a template.', 'faq-ai-generator'); ?></p>
                <button class="faq-ai-button primary"><?php _e('Add Your First FAQ', 'faq-ai-generator'); ?></button>
            </div>
            
            <!-- FAQ List -->
            <div id="faq-ai-list" class="faq-ai-list"></div>
        </div>
        
        <!-- Settings Tab -->
        <div id="faq-ai-settings-panel" class="faq-ai-tab-panel">
            <div class="faq-ai-settings-section">
                <h3><?php _e('Display Settings', 'faq-ai-generator'); ?></h3>
                
                <div class="faq-ai-setting-row">
                    <label for="faq-ai-display-mode"><?php _e('Display Mode', 'faq-ai-generator'); ?></label>
                    <select id="faq-ai-display-mode" class="faq-ai-select">
                        <option value="accordion"><?php _e('Accordion', 'faq-ai-generator'); ?></option>
                        <option value="tabs"><?php _e('Tabs', 'faq-ai-generator'); ?></option>
                        <option value="list"><?php _e('Simple List', 'faq-ai-generator'); ?></option>
                    </select>
                </div>
                
                <div class="faq-ai-setting-row">
                    <label for="faq-ai-show-numbers"><?php _e('Show Numbers', 'faq-ai-generator'); ?></label>
                    <input type="checkbox" id="faq-ai-show-numbers" class="faq-ai-checkbox">
                </div>
            </div>
            
            <div class="faq-ai-settings-section">
                <h3><?php _e('AI Settings', 'faq-ai-generator'); ?></h3>
                
                <div class="faq-ai-setting-row">
                    <label for="faq-ai-answer-length"><?php _e('Preferred Answer Length', 'faq-ai-generator'); ?></label>
                    <select id="faq-ai-answer-length" class="faq-ai-select">
                        <option value="short"><?php _e('Short', 'faq-ai-generator'); ?></option>
                        <option value="medium" selected><?php _e('Medium', 'faq-ai-generator'); ?></option>
                        <option value="long"><?php _e('Long', 'faq-ai-generator'); ?></option>
                    </select>
                </div>
                
                <div class="faq-ai-setting-row">
                    <label for="faq-ai-answer-tone"><?php _e('Answer Tone', 'faq-ai-generator'); ?></label>
                    <select id="faq-ai-answer-tone" class="faq-ai-select">
                        <option value="professional" selected><?php _e('Professional', 'faq-ai-generator'); ?></option>
                        <option value="friendly"><?php _e('Friendly', 'faq-ai-generator'); ?></option>
                        <option value="casual"><?php _e('Casual', 'faq-ai-generator'); ?></option>
                        <option value="technical"><?php _e('Technical', 'faq-ai-generator'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="faq-ai-settings-section">
                <h3><?php _e('Import/Export', 'faq-ai-generator'); ?></h3>
                
                <div class="faq-ai-setting-row faq-ai-button-group">
                    <button id="faq-ai-export-json" class="faq-ai-button"><?php _e('Export as JSON', 'faq-ai-generator'); ?></button>
                    <button id="faq-ai-import-json" class="faq-ai-button"><?php _e('Import from JSON', 'faq-ai-generator'); ?></button>
                </div>
                
                <div class="faq-ai-setting-row">
                    <button id="faq-ai-clear-all" class="faq-ai-button danger"><?php _e('Clear All FAQs', 'faq-ai-generator'); ?></button>
                </div>
            </div>
        </div>
        
        <!-- Export Tab -->
        <div id="faq-ai-export-panel" class="faq-ai-tab-panel">
            <div class="faq-ai-export-section">
                <h3><?php _e('Schema Settings', 'faq-ai-generator'); ?></h3>
                
                <div class="faq-ai-setting-row">
                    <label for="faq-ai-schema-format"><?php _e('Schema Format', 'faq-ai-generator'); ?></label>
                    <select id="faq-ai-schema-format" class="faq-ai-select">
                        <option value="json-ld" selected><?php _e('JSON-LD', 'faq-ai-generator'); ?></option>
                        <option value="microdata"><?php _e('Microdata', 'faq-ai-generator'); ?></option>
                        <option value="rdfa"><?php _e('RDFa', 'faq-ai-generator'); ?></option>
                        <option value="html"><?php _e('HTML Only', 'faq-ai-generator'); ?></option>
                    </select>
                </div>
                
                <div class="faq-ai-setting-row">
                    <label for="faq-ai-base-url"><?php _e('Base URL', 'faq-ai-generator'); ?></label>
                    <input type="text" id="faq-ai-base-url" class="faq-ai-input" placeholder="<?php _e('https://example.com/faqs/', 'faq-ai-generator'); ?>">
                </div>
            </div>
            
            <div class="faq-ai-export-section">
                <div class="faq-ai-button-group">
                    <button id="faq-ai-generate-schema" class="faq-ai-button primary"><?php _e('Generate Schema', 'faq-ai-generator'); ?></button>
                    <button id="faq-ai-copy-schema" class="faq-ai-button"><?php _e('Copy to Clipboard', 'faq-ai-generator'); ?></button>
                    <button id="faq-ai-download-schema" class="faq-ai-button"><?php _e('Download', 'faq-ai-generator'); ?></button>
                </div>
                
                <textarea id="faq-ai-schema-output" class="faq-ai-textarea" readonly></textarea>
            </div>
            
            <?php if ($show_seo): ?>
            <div class="faq-ai-export-section">
                <h3><?php _e('SEO Analysis', 'faq-ai-generator'); ?></h3>
                
                <div class="faq-ai-seo-score-display">
                    <div class="faq-ai-seo-score-title"><?php _e('Overall SEO Score', 'faq-ai-generator'); ?></div>
                    
                    <div class="faq-ai-led-display">
                        <div class="faq-ai-led-digit">
                            <div class="faq-ai-led-segment horizontal a"></div>
                            <div class="faq-ai-led-segment vertical b"></div>
                            <div class="faq-ai-led-segment vertical c"></div>
                            <div class="faq-ai-led-segment horizontal d"></div>
                            <div class="faq-ai-led-segment vertical e"></div>
                            <div class="faq-ai-led-segment vertical f"></div>
                            <div class="faq-ai-led-segment horizontal g"></div>
                        </div>
                        <div class="faq-ai-led-digit">
                            <div class="faq-ai-led-segment horizontal a"></div>
                            <div class="faq-ai-led-segment vertical b"></div>
                            <div class="faq-ai-led-segment vertical c"></div>
                            <div class="faq-ai-led-segment horizontal d"></div>
                            <div class="faq-ai-led-segment vertical e"></div>
                            <div class="faq-ai-led-segment vertical f"></div>
                            <div class="faq-ai-led-segment horizontal g"></div>
                        </div>
                    </div>
                    
                    <div class="faq-ai-seo-legend">
                        <div class="faq-ai-seo-legend-item">
                            <div class="faq-ai-seo-legend-color low"></div>
                            <span><?php _e('Poor', 'faq-ai-generator'); ?></span>
                        </div>
                        <div class="faq-ai-seo-legend-item">
                            <div class="faq-ai-seo-legend-color medium"></div>
                            <span><?php _e('Good', 'faq-ai-generator'); ?></span>
                        </div>
                        <div class="faq-ai-seo-legend-item">
                            <div class="faq-ai-seo-legend-color high"></div>
                            <span><?php _e('Great', 'faq-ai-generator'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Template for FAQ Items -->
    <script type="text/template" id="faq-ai-item-template">
        <div class="faq-ai-item">
            <div class="faq-ai-item-header">
                <div class="faq-ai-drag-handle">⋮⋮</div>
                <button class="faq-ai-toggle-item faq-ai-button-icon"><span class="faq-ai-icon">▼</span></button>
                <div class="faq-ai-item-actions">
                    <button class="faq-ai-suggest-question faq-ai-button small"><?php _e('Improve Question', 'faq-ai-generator'); ?></button>
                    <button class="faq-ai-generate-answer faq-ai-button small"><?php _e('Generate Answer', 'faq-ai-generator'); ?></button>
                    <button class="faq-ai-enhance-answer faq-ai-button small"><?php _e('Enhance', 'faq-ai-generator'); ?></button>
                    <button class="faq-ai-analyze-seo faq-ai-button small"><?php _e('SEO', 'faq-ai-generator'); ?></button>
                    <button class="faq-ai-delete-item faq-ai-button-icon danger">&times;</button>
                </div>
            </div>
            <div class="faq-ai-item-content">
                <div class="faq-ai-question-editor-container faq-ai-editor-container">
                    <label class="faq-ai-editor-label"><?php _e('Question', 'faq-ai-generator'); ?></label>
                    <div class="faq-ai-question-editor"></div>
                    <button class="faq-ai-validate-question faq-ai-button small"><?php _e('Validate', 'faq-ai-generator'); ?></button>
                </div>
                
                <div class="faq-ai-answer-editor-container faq-ai-editor-container">
                    <label class="faq-ai-editor-label"><?php _e('Answer', 'faq-ai-generator'); ?></label>
                    <div class="faq-ai-answer-editor"></div>
                </div>
                
                <div class="faq-ai-suggestions-panel">
                    <div class="faq-ai-suggestions-header">
                        <h4><?php _e('AI Suggestions', 'faq-ai-generator'); ?></h4>
                        <div class="faq-ai-suggestions-actions">
                            <button class="faq-ai-refresh-suggestions faq-ai-button-icon" title="<?php _e('Refresh', 'faq-ai-generator'); ?>">↻</button>
                            <button class="faq-ai-close-suggestions faq-ai-button-icon" title="<?php _e('Close', 'faq-ai-generator'); ?>">&times;</button>
                        </div>
                    </div>
                    <div class="faq-ai-suggestions-content"></div>
                </div>
            </div>
        </div>
    </script>
    
    <!-- Template for AI Suggestions -->
    <script type="text/template" id="faq-ai-suggestion-template">
        <div class="faq-ai-suggestion">
            <div class="faq-ai-suggestion-text"></div>
            <div class="faq-ai-suggestion-metadata">
                <div class="faq-ai-suggestion-benefit"></div>
                <div class="faq-ai-suggestion-reason"></div>
            </div>
            <button class="faq-ai-apply-suggestion faq-ai-button small primary"><?php _e('Apply', 'faq-ai-generator'); ?></button>
        </div>
    </script>
</div>