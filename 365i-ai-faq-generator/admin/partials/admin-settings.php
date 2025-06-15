<?php
/**
 * Provide a admin area view for the plugin settings
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://365i.com
 * @since      1.0.0
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap faq-ai-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="faq-ai-settings-description">
        <p><?php _e('Configure the AI FAQ Generator settings below. These settings control how the plugin communicates with AI workers, generates schema, and more.', 'faq-ai-generator'); ?></p>
    </div>
    
    <?php settings_errors(); ?>
    
    <div class="faq-ai-settings-tabs">
        <ul class="faq-ai-tabs-nav">
            <li><a href="#worker-settings" class="active"><?php _e('Worker Configuration', 'faq-ai-generator'); ?></a></li>
            <li><a href="#general-settings"><?php _e('General Settings', 'faq-ai-generator'); ?></a></li>
            <li><a href="#advanced-settings"><?php _e('Advanced Options', 'faq-ai-generator'); ?></a></li>
        </ul>
        
        <div class="faq-ai-tabs-content">
            <div id="worker-settings" class="faq-ai-tab-panel active">
                <div class="faq-ai-worker-dashboard">
                    <div class="faq-ai-worker-description">
                        <p><?php _e('Configure the Cloudflare AI workers that power the FAQ generator. Each worker serves a specific purpose in the FAQ generation process.', 'faq-ai-generator'); ?></p>
                        <div class="faq-ai-api-key-container">
                            <h3><?php _e('API Key', 'faq-ai-generator'); ?></h3>
                            <p><?php _e('Your API key is used to authenticate requests to all workers.', 'faq-ai-generator'); ?> <span class="faq-ai-tooltip" title="<?php _e('This is your Cloudflare API key. You can obtain it from your Cloudflare dashboard under My Profile > API Tokens. Create a custom token with Workers Script Read and Workers Routes Read permissions.', 'faq-ai-generator'); ?>"><span class="dashicons dashicons-info"></span></span></p>
                            <form method="post" action="options.php" id="api-key-form">
                                <?php
                                settings_fields('faq_ai_generator_api_key');
                                ?>
                                <div class="faq-ai-api-key-field">
                                    <?php
                                    $api_key = get_option('faq_ai_generator_api_key', '');
                                    $api_key_masked = !empty($api_key) ? substr($api_key, 0, 4) . str_repeat('•', strlen($api_key) - 8) . substr($api_key, -4) : '';
                                    ?>
                                    <input type="password" id="faq_ai_generator_api_key" name="faq_ai_generator_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" autocomplete="off" placeholder="<?php _e('Enter your API key', 'faq-ai-generator'); ?>" />
                                    <button type="button" id="toggle-api-key" class="button button-secondary"><?php _e('Show', 'faq-ai-generator'); ?></button>
                                </div>
                                <?php submit_button(__('Save API Key', 'faq-ai-generator'), 'primary', 'submit', false); ?>
                            </form>
                        </div>
                    </div>
                    
                    <form method="post" action="options.php" id="worker-settings-form">
                        <?php settings_fields('faq_ai_generator_workers'); ?>
                        
                        <div class="faq-ai-worker-grid">
                            <!-- Question Generator Worker -->
                            <div class="faq-ai-worker-card">
                                <div class="faq-ai-worker-header">
                                    <div class="faq-ai-worker-icon question">
                                        <span class="dashicons dashicons-editor-help"></span>
                                    </div>
                                    <h3><?php _e('Question Generator', 'faq-ai-generator'); ?></h3>
                                </div>
                                <div class="faq-ai-worker-body">
                                    <p><?php _e('Generates and improves questions based on content.', 'faq-ai-generator'); ?> <span class="faq-ai-tooltip" title="<?php _e('This worker analyzes your content and generates relevant questions that your audience might ask. It can also improve existing questions for clarity and SEO value.', 'faq-ai-generator'); ?>"><span class="dashicons dashicons-info"></span></span></p>
                                    <div class="faq-ai-worker-field">
                                        <label for="faq_ai_generator_workers[question][url]"><?php _e('Worker URL', 'faq-ai-generator'); ?></label>
                                        <input type="url" id="faq_ai_generator_workers[question][url]" name="faq_ai_generator_workers[question][url]" value="<?php echo esc_url(get_option('faq_ai_generator_workers')['question']['url'] ?? 'https://faq-realtime-assistant-worker.winter-cake-bf57.workers.dev'); ?>" class="regular-text" placeholder="https://faq-realtime-assistant-worker.winter-cake-bf57.workers.dev" />
                                    </div>
                                    <div class="faq-ai-worker-field">
                                        <label for="faq_ai_generator_workers[question][rate_limit]"><?php _e('Rate Limit (requests per minute)', 'faq-ai-generator'); ?></label>
                                        <div class="faq-ai-range-slider">
                                            <input type="range" id="faq_ai_generator_workers[question][rate_limit]" name="faq_ai_generator_workers[question][rate_limit]" value="<?php echo intval(get_option('faq_ai_generator_workers')['question']['rate_limit'] ?? 10); ?>" min="1" max="60" step="1" class="rate-limit-slider" data-worker="question" />
                                            <span id="rate-limit-value-question"><?php echo intval(get_option('faq_ai_generator_workers')['question']['rate_limit'] ?? 10); ?></span>
                                        </div>
                                    </div>
                                    <div class="faq-ai-worker-actions">
                                        <button type="button" class="button test-worker-button" data-worker="question"><?php _e('Test Connection', 'faq-ai-generator'); ?></button>
                                        <div id="test-result-question" class="faq-ai-test-result"></div>
                                    </div>
                                </div>
                                <div class="faq-ai-worker-status <?php echo !empty(get_option('faq_ai_generator_workers')['question']['url']) ? 'configured' : 'not-configured'; ?>">
                                    <span class="status-indicator"></span>
                                    <span class="status-text"><?php echo !empty(get_option('faq_ai_generator_workers')['question']['url']) ? __('Configured', 'faq-ai-generator') : __('Not Configured', 'faq-ai-generator'); ?></span>
                                </div>
                            </div>
                            
                            <!-- Answer Generator Worker -->
                            <div class="faq-ai-worker-card">
                                <div class="faq-ai-worker-header">
                                    <div class="faq-ai-worker-icon answer">
                                        <span class="dashicons dashicons-format-chat"></span>
                                    </div>
                                    <h3><?php _e('Answer Generator', 'faq-ai-generator'); ?></h3>
                                </div>
                                <div class="faq-ai-worker-body">
                                    <p><?php _e('Creates detailed answers for questions.', 'faq-ai-generator'); ?> <span class="faq-ai-tooltip" title="<?php _e('This worker generates comprehensive, accurate answers to your questions. It uses AI to create responses that are informative, engaging, and tailored to your audience.', 'faq-ai-generator'); ?>"><span class="dashicons dashicons-info"></span></span></p>
                                    <div class="faq-ai-worker-field">
                                        <label for="faq_ai_generator_workers[answer][url]"><?php _e('Worker URL', 'faq-ai-generator'); ?></label>
                                        <input type="url" id="faq_ai_generator_workers[answer][url]" name="faq_ai_generator_workers[answer][url]" value="<?php echo esc_url(get_option('faq_ai_generator_workers')['answer']['url'] ?? 'https://faq-answer-generator-worker.winter-cake-bf57.workers.dev'); ?>" class="regular-text" placeholder="https://faq-answer-generator-worker.winter-cake-bf57.workers.dev" />
                                    </div>
                                    <div class="faq-ai-worker-field">
                                        <label for="faq_ai_generator_workers[answer][rate_limit]"><?php _e('Rate Limit (requests per minute)', 'faq-ai-generator'); ?></label>
                                        <div class="faq-ai-range-slider">
                                            <input type="range" id="faq_ai_generator_workers[answer][rate_limit]" name="faq_ai_generator_workers[answer][rate_limit]" value="<?php echo intval(get_option('faq_ai_generator_workers')['answer']['rate_limit'] ?? 10); ?>" min="1" max="60" step="1" class="rate-limit-slider" data-worker="answer" />
                                            <span id="rate-limit-value-answer"><?php echo intval(get_option('faq_ai_generator_workers')['answer']['rate_limit'] ?? 10); ?></span>
                                        </div>
                                    </div>
                                    <div class="faq-ai-worker-actions">
                                        <button type="button" class="button test-worker-button" data-worker="answer"><?php _e('Test Connection', 'faq-ai-generator'); ?></button>
                                        <div id="test-result-answer" class="faq-ai-test-result"></div>
                                    </div>
                                </div>
                                <div class="faq-ai-worker-status <?php echo !empty(get_option('faq_ai_generator_workers')['answer']['url']) ? 'configured' : 'not-configured'; ?>">
                                    <span class="status-indicator"></span>
                                    <span class="status-text"><?php echo !empty(get_option('faq_ai_generator_workers')['answer']['url']) ? __('Configured', 'faq-ai-generator') : __('Not Configured', 'faq-ai-generator'); ?></span>
                                </div>
                            </div>
                            
                            <!-- Enhance Worker -->
                            <div class="faq-ai-worker-card">
                                <div class="faq-ai-worker-header">
                                    <div class="faq-ai-worker-icon enhance">
                                        <span class="dashicons dashicons-star-filled"></span>
                                    </div>
                                    <h3><?php _e('FAQ Enhancer', 'faq-ai-generator'); ?></h3>
                                </div>
                                <div class="faq-ai-worker-body">
                                    <p><?php _e('Enhances existing FAQs with additional details.', 'faq-ai-generator'); ?> <span class="faq-ai-tooltip" title="<?php _e('This worker improves your existing FAQs by adding relevant details, examples, and context. It helps make your answers more comprehensive and valuable to users.', 'faq-ai-generator'); ?>"><span class="dashicons dashicons-info"></span></span></p>
                                    <div class="faq-ai-worker-field">
                                        <label for="faq_ai_generator_workers[enhance][url]"><?php _e('Worker URL', 'faq-ai-generator'); ?></label>
                                        <input type="url" id="faq_ai_generator_workers[enhance][url]" name="faq_ai_generator_workers[enhance][url]" value="<?php echo esc_url(get_option('faq_ai_generator_workers')['enhance']['url'] ?? 'https://faq-enhancement-worker.winter-cake-bf57.workers.dev'); ?>" class="regular-text" placeholder="https://faq-enhancement-worker.winter-cake-bf57.workers.dev" />
                                    </div>
                                    <div class="faq-ai-worker-field">
                                        <label for="faq_ai_generator_workers[enhance][rate_limit]"><?php _e('Rate Limit (requests per minute)', 'faq-ai-generator'); ?></label>
                                        <div class="faq-ai-range-slider">
                                            <input type="range" id="faq_ai_generator_workers[enhance][rate_limit]" name="faq_ai_generator_workers[enhance][rate_limit]" value="<?php echo intval(get_option('faq_ai_generator_workers')['enhance']['rate_limit'] ?? 10); ?>" min="1" max="60" step="1" class="rate-limit-slider" data-worker="enhance" />
                                            <span id="rate-limit-value-enhance"><?php echo intval(get_option('faq_ai_generator_workers')['enhance']['rate_limit'] ?? 10); ?></span>
                                        </div>
                                    </div>
                                    <div class="faq-ai-worker-actions">
                                        <button type="button" class="button test-worker-button" data-worker="enhance"><?php _e('Test Connection', 'faq-ai-generator'); ?></button>
                                        <div id="test-result-enhance" class="faq-ai-test-result"></div>
                                    </div>
                                </div>
                                <div class="faq-ai-worker-status <?php echo !empty(get_option('faq_ai_generator_workers')['enhance']['url']) ? 'configured' : 'not-configured'; ?>">
                                    <span class="status-indicator"></span>
                                    <span class="status-text"><?php echo !empty(get_option('faq_ai_generator_workers')['enhance']['url']) ? __('Configured', 'faq-ai-generator') : __('Not Configured', 'faq-ai-generator'); ?></span>
                                </div>
                            </div>
                            
                            <!-- SEO Worker -->
                            <div class="faq-ai-worker-card">
                                <div class="faq-ai-worker-header">
                                    <div class="faq-ai-worker-icon seo">
                                        <span class="dashicons dashicons-chart-bar"></span>
                                    </div>
                                    <h3><?php _e('SEO Analyzer', 'faq-ai-generator'); ?></h3>
                                </div>
                                <div class="faq-ai-worker-body">
                                    <p><?php _e('Analyzes FAQ content for SEO optimization.', 'faq-ai-generator'); ?> <span class="faq-ai-tooltip" title="<?php _e('This worker evaluates your FAQs for search engine optimization. It analyzes keyword usage, readability, and relevance, then provides a score and suggestions for improvement.', 'faq-ai-generator'); ?>"><span class="dashicons dashicons-info"></span></span></p>
                                    <div class="faq-ai-worker-field">
                                        <label for="faq_ai_generator_workers[seo][url]"><?php _e('Worker URL', 'faq-ai-generator'); ?></label>
                                        <input type="url" id="faq_ai_generator_workers[seo][url]" name="faq_ai_generator_workers[seo][url]" value="<?php echo esc_url(get_option('faq_ai_generator_workers')['seo']['url'] ?? 'https://faq-seo-analyzer-worker.winter-cake-bf57.workers.dev'); ?>" class="regular-text" placeholder="https://faq-seo-analyzer-worker.winter-cake-bf57.workers.dev" />
                                    </div>
                                    <div class="faq-ai-worker-field">
                                        <label for="faq_ai_generator_workers[seo][rate_limit]"><?php _e('Rate Limit (requests per minute)', 'faq-ai-generator'); ?></label>
                                        <div class="faq-ai-range-slider">
                                            <input type="range" id="faq_ai_generator_workers[seo][rate_limit]" name="faq_ai_generator_workers[seo][rate_limit]" value="<?php echo intval(get_option('faq_ai_generator_workers')['seo']['rate_limit'] ?? 10); ?>" min="1" max="60" step="1" class="rate-limit-slider" data-worker="seo" />
                                            <span id="rate-limit-value-seo"><?php echo intval(get_option('faq_ai_generator_workers')['seo']['rate_limit'] ?? 10); ?></span>
                                        </div>
                                    </div>
                                    <div class="faq-ai-worker-actions">
                                        <button type="button" class="button test-worker-button" data-worker="seo"><?php _e('Test Connection', 'faq-ai-generator'); ?></button>
                                        <div id="test-result-seo" class="faq-ai-test-result"></div>
                                    </div>
                                </div>
                                <div class="faq-ai-worker-status <?php echo !empty(get_option('faq_ai_generator_workers')['seo']['url']) ? 'configured' : 'not-configured'; ?>">
                                    <span class="status-indicator"></span>
                                    <span class="status-text"><?php echo !empty(get_option('faq_ai_generator_workers')['seo']['url']) ? __('Configured', 'faq-ai-generator') : __('Not Configured', 'faq-ai-generator'); ?></span>
                                </div>
                            </div>
                            
                            <!-- Extract Worker -->
                            <div class="faq-ai-worker-card">
                                <div class="faq-ai-worker-header">
                                    <div class="faq-ai-worker-icon extract">
                                        <span class="dashicons dashicons-search"></span>
                                    </div>
                                    <h3><?php _e('FAQ Extractor', 'faq-ai-generator'); ?></h3>
                                </div>
                                <div class="faq-ai-worker-body">
                                    <p><?php _e('Extracts FAQs from external URLs and content.', 'faq-ai-generator'); ?> <span class="faq-ai-tooltip" title="<?php _e('This worker can analyze external web pages and extract existing FAQs. It identifies question-answer pairs from various formats and imports them into your FAQ collection.', 'faq-ai-generator'); ?>"><span class="dashicons dashicons-info"></span></span></p>
                                    <div class="faq-ai-worker-field">
                                        <label for="faq_ai_generator_workers[extract][url]"><?php _e('Worker URL', 'faq-ai-generator'); ?></label>
                                        <input type="url" id="faq_ai_generator_workers[extract][url]" name="faq_ai_generator_workers[extract][url]" value="<?php echo esc_url(get_option('faq_ai_generator_workers')['extract']['url'] ?? 'https://url-to-faq-generator-worker.winter-cake-bf57.workers.dev'); ?>" class="regular-text" placeholder="https://url-to-faq-generator-worker.winter-cake-bf57.workers.dev" />
                                    </div>
                                    <div class="faq-ai-worker-field">
                                        <label for="faq_ai_generator_workers[extract][rate_limit]"><?php _e('Rate Limit (requests per minute)', 'faq-ai-generator'); ?></label>
                                        <div class="faq-ai-range-slider">
                                            <input type="range" id="faq_ai_generator_workers[extract][rate_limit]" name="faq_ai_generator_workers[extract][rate_limit]" value="<?php echo intval(get_option('faq_ai_generator_workers')['extract']['rate_limit'] ?? 10); ?>" min="1" max="60" step="1" class="rate-limit-slider" data-worker="extract" />
                                            <span id="rate-limit-value-extract"><?php echo intval(get_option('faq_ai_generator_workers')['extract']['rate_limit'] ?? 10); ?></span>
                                        </div>
                                    </div>
                                    <div class="faq-ai-worker-actions">
                                        <button type="button" class="button test-worker-button" data-worker="extract"><?php _e('Test Connection', 'faq-ai-generator'); ?></button>
                                        <div id="test-result-extract" class="faq-ai-test-result"></div>
                                    </div>
                                </div>
                                <div class="faq-ai-worker-status <?php echo !empty(get_option('faq_ai_generator_workers')['extract']['url']) ? 'configured' : 'not-configured'; ?>">
                                    <span class="status-indicator"></span>
                                    <span class="status-text"><?php echo !empty(get_option('faq_ai_generator_workers')['extract']['url']) ? __('Configured', 'faq-ai-generator') : __('Not Configured', 'faq-ai-generator'); ?></span>
                                </div>
                            </div>
                            
                            <!-- Topic Generator Worker -->
                            <div class="faq-ai-worker-card">
                                <div class="faq-ai-worker-header">
                                    <div class="faq-ai-worker-icon topic">
                                        <span class="dashicons dashicons-category"></span>
                                    </div>
                                    <h3><?php _e('Topic Generator', 'faq-ai-generator'); ?></h3>
                                </div>
                                <div class="faq-ai-worker-body">
                                    <p><?php _e('Generates FAQ topics based on website content.', 'faq-ai-generator'); ?> <span class="faq-ai-tooltip" title="<?php _e('This worker analyzes your website content to suggest relevant FAQ topics. It identifies common questions your audience might have based on your existing content.', 'faq-ai-generator'); ?>"><span class="dashicons dashicons-info"></span></span></p>
                                    <div class="faq-ai-worker-field">
                                        <label for="faq_ai_generator_workers[topic][url]"><?php _e('Worker URL', 'faq-ai-generator'); ?></label>
                                        <input type="url" id="faq_ai_generator_workers[topic][url]" name="faq_ai_generator_workers[topic][url]" value="<?php echo esc_url(get_option('faq_ai_generator_workers')['topic']['url'] ?? 'https://faq-topic-generator-worker.winter-cake-bf57.workers.dev'); ?>" class="regular-text" placeholder="https://faq-topic-generator-worker.winter-cake-bf57.workers.dev" />
                                    </div>
                                    <div class="faq-ai-worker-field">
                                        <label for="faq_ai_generator_workers[topic][rate_limit]"><?php _e('Rate Limit (requests per minute)', 'faq-ai-generator'); ?></label>
                                        <div class="faq-ai-range-slider">
                                            <input type="range" id="faq_ai_generator_workers[topic][rate_limit]" name="faq_ai_generator_workers[topic][rate_limit]" value="<?php echo intval(get_option('faq_ai_generator_workers')['topic']['rate_limit'] ?? 10); ?>" min="1" max="60" step="1" class="rate-limit-slider" data-worker="topic" />
                                            <span id="rate-limit-value-topic"><?php echo intval(get_option('faq_ai_generator_workers')['topic']['rate_limit'] ?? 10); ?></span>
                                        </div>
                                    </div>
                                    <div class="faq-ai-worker-actions">
                                        <button type="button" class="button test-worker-button" data-worker="topic"><?php _e('Test Connection', 'faq-ai-generator'); ?></button>
                                        <div id="test-result-topic" class="faq-ai-test-result"></div>
                                    </div>
                                </div>
                                <div class="faq-ai-worker-status <?php echo !empty(get_option('faq_ai_generator_workers')['topic']['url']) ? 'configured' : 'not-configured'; ?>">
                                    <span class="status-indicator"></span>
                                    <span class="status-text"><?php echo !empty(get_option('faq_ai_generator_workers')['topic']['url']) ? __('Configured', 'faq-ai-generator') : __('Not Configured', 'faq-ai-generator'); ?></span>
                                </div>
                            </div>
                            
                            <!-- Validator Worker -->
                            <div class="faq-ai-worker-card">
                                <div class="faq-ai-worker-header">
                                    <div class="faq-ai-worker-icon validate">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                    </div>
                                    <h3><?php _e('Content Validator', 'faq-ai-generator'); ?></h3>
                                </div>
                                <div class="faq-ai-worker-body">
                                    <p><?php _e('Validates and checks FAQ content for quality.', 'faq-ai-generator'); ?> <span class="faq-ai-tooltip" title="<?php _e('This worker evaluates your FAQ content for accuracy, clarity, and completeness. It checks for grammar issues, factual consistency, and suggests improvements for overall quality.', 'faq-ai-generator'); ?>"><span class="dashicons dashicons-info"></span></span></p>
                                    <div class="faq-ai-worker-field">
                                        <label for="faq_ai_generator_workers[validate][url]"><?php _e('Worker URL', 'faq-ai-generator'); ?></label>
                                        <input type="url" id="faq_ai_generator_workers[validate][url]" name="faq_ai_generator_workers[validate][url]" value="<?php echo esc_url(get_option('faq_ai_generator_workers')['validate']['url'] ?? 'https://faq-content-validator-worker.winter-cake-bf57.workers.dev'); ?>" class="regular-text" placeholder="https://faq-content-validator-worker.winter-cake-bf57.workers.dev" />
                                    </div>
                                    <div class="faq-ai-worker-field">
                                        <label for="faq_ai_generator_workers[validate][rate_limit]"><?php _e('Rate Limit (requests per minute)', 'faq-ai-generator'); ?></label>
                                        <div class="faq-ai-range-slider">
                                            <input type="range" id="faq_ai_generator_workers[validate][rate_limit]" name="faq_ai_generator_workers[validate][rate_limit]" value="<?php echo intval(get_option('faq_ai_generator_workers')['validate']['rate_limit'] ?? 10); ?>" min="1" max="60" step="1" class="rate-limit-slider" data-worker="validate" />
                                            <span id="rate-limit-value-validate"><?php echo intval(get_option('faq_ai_generator_workers')['validate']['rate_limit'] ?? 10); ?></span>
                                        </div>
                                    </div>
                                    <div class="faq-ai-worker-actions">
                                        <button type="button" class="button test-worker-button" data-worker="validate"><?php _e('Test Connection', 'faq-ai-generator'); ?></button>
                                        <div id="test-result-validate" class="faq-ai-test-result"></div>
                                    </div>
                                </div>
                                <div class="faq-ai-worker-status <?php echo !empty(get_option('faq_ai_generator_workers')['validate']['url']) ? 'configured' : 'not-configured'; ?>">
                                    <span class="status-indicator"></span>
                                    <span class="status-text"><?php echo !empty(get_option('faq_ai_generator_workers')['validate']['url']) ? __('Configured', 'faq-ai-generator') : __('Not Configured', 'faq-ai-generator'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php submit_button(__('Save All Worker Settings', 'faq-ai-generator'), 'primary', 'submit', true, ['class' => 'faq-ai-save-all-button']); ?>
                    </form>
                </div>
            </div>
            
            <div id="general-settings" class="faq-ai-tab-panel">
                <form method="post" action="options.php" id="general-settings-form">
                    <?php
                    settings_fields('faq_ai_generator_settings');
                    do_settings_sections('faq_ai_generator_settings');
                    submit_button(__('Save General Settings', 'faq-ai-generator'));
                    ?>
                </form>
            </div>
            
            <div id="advanced-settings" class="faq-ai-tab-panel">
                <div class="faq-ai-advanced-actions">
                    <div class="faq-ai-action-card">
                        <h3><?php _e('Reset Rate Limits', 'faq-ai-generator'); ?></h3>
                        <p><?php _e('Reset all worker rate limits to allow immediate usage.', 'faq-ai-generator'); ?></p>
                        <button id="faq-ai-reset-all-limits-settings" class="button button-primary"><?php _e('Reset All Rate Limits', 'faq-ai-generator'); ?></button>
                    </div>
                    
                    <div class="faq-ai-action-card">
                        <h3><?php _e('Test Workers', 'faq-ai-generator'); ?></h3>
                        <p><?php _e('Test the connection to all AI workers.', 'faq-ai-generator'); ?></p>
                        <button id="faq-ai-test-all-workers" class="button button-primary"><?php _e('Test All Workers', 'faq-ai-generator'); ?></button>
                        <div id="faq-ai-test-all-results" class="faq-ai-test-results"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="faq-ai-settings-footer">
        <div class="faq-ai-support">
            <h3><?php _e('Need Help?', 'faq-ai-generator'); ?></h3>
            <p><?php _e('If you\'re experiencing issues with the AI FAQ Generator or have questions, please check the documentation or contact support.', 'faq-ai-generator'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=faq-ai-generator-docs'); ?>" class="button"><?php _e('Documentation', 'faq-ai-generator'); ?></a>
            <a href="mailto:support@365i.com" class="button"><?php _e('Support', 'faq-ai-generator'); ?></a>
        </div>
        
        <div class="faq-ai-version">
            <p><?php echo sprintf(__('AI FAQ Generator Version %s', 'faq-ai-generator'), FAQ_AI_GENERATOR_VERSION); ?></p>
        </div>
    </div>
</div>

<style>
    /* Tooltip styles */
    .faq-ai-tooltip {
        position: relative;
        display: inline-block;
        cursor: help;
    }
    
    .faq-ai-tooltip .dashicons {
        color: #4a6bef;
        font-size: 16px;
        width: 16px;
        height: 16px;
        vertical-align: text-bottom;
    }
    
    .faq-ai-tooltip:hover::after {
        content: attr(title);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background-color: #333;
        color: #fff;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 13px;
        line-height: 1.4;
        width: 250px;
        z-index: 100;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
    
    .faq-ai-tooltip:hover::before {
        content: '';
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        border-width: 6px;
        border-style: solid;
        border-color: #333 transparent transparent transparent;
        z-index: 100;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Tab navigation
        $('.faq-ai-tabs-nav a').on('click', function(e) {
            e.preventDefault();
            
            // Update active tab
            $('.faq-ai-tabs-nav a').removeClass('active');
            $(this).addClass('active');
            
            // Show corresponding panel
            var target = $(this).attr('href');
            $('.faq-ai-tab-panel').removeClass('active');
            $(target).addClass('active');
        });
        
        // Toggle API key visibility
        $('#toggle-api-key').on('click', function() {
            const $input = $('#faq_ai_generator_api_key');
            const type = $input.attr('type');
            
            if (type === 'password') {
                $input.attr('type', 'text');
                $(this).text('<?php _e('Hide', 'faq-ai-generator'); ?>');
            } else {
                $input.attr('type', 'password');
                $(this).text('<?php _e('Show', 'faq-ai-generator'); ?>');
            }
        });
        
        // Range slider value display
        $('.rate-limit-slider, .cooldown-slider, .auto-save-slider').on('input', function() {
            var worker = $(this).data('worker');
            var value = $(this).val();
            
            if ($(this).hasClass('rate-limit-slider')) {
                $('#rate-limit-value-' + worker).text(value);
            } else if ($(this).hasClass('cooldown-slider')) {
                $('#cooldown-value-' + worker).text(value);
            } else if ($(this).hasClass('auto-save-slider')) {
                $('.auto-save-value').text(value);
            }
        });
        
        // Test worker connection
        $('.test-worker-button').on('click', function() {
            var worker = $(this).data('worker');
            var resultElement = $('#test-result-' + worker);
            
            resultElement.html('<span class="testing"><?php _e('Testing...', 'faq-ai-generator'); ?></span>');
            
            // Create appropriate test data for the worker
            var testData = createTestData(worker);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'faq_ai_test_worker',
                    nonce: faqAiAdmin.nonce,
                    worker: worker,
                    test_data: JSON.stringify(testData)
                },
                success: function(response) {
                    if (response.success) {
                        resultElement.html('<span class="success">' + faqAiAdmin.strings.testSuccess + '</span>');
                    } else {
                        resultElement.html('<span class="error">' + faqAiAdmin.strings.testFailed + response.data.message + '</span>');
                    }
                },
                error: function() {
                    resultElement.html('<span class="error"><?php _e('Connection error', 'faq-ai-generator'); ?></span>');
                }
            });
        });
        
        // Reset all rate limits
        $('#faq-ai-reset-all-limits-settings').on('click', function() {
            if (confirm(faqAiAdmin.strings.confirm)) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'faq_ai_reset_rate_limits',
                        nonce: faqAiAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(faqAiAdmin.strings.resetRateLimits);
                        } else {
                            alert(faqAiAdmin.strings.resetFailed + response.data.message);
                        }
                    },
                    error: function() {
                        alert('<?php _e('Connection error', 'faq-ai-generator'); ?>');
                    }
                });
            }
        });
        
        // Test all workers
        $('#faq-ai-test-all-workers').on('click', function() {
            var resultsElement = $('#faq-ai-test-all-results');
            resultsElement.html('<p class="testing"><?php _e('Testing all workers...', 'faq-ai-generator'); ?></p>');
            
            var workers = <?php echo json_encode(array_keys(get_option('faq_ai_generator_workers', array()))); ?>;
            var results = {};
            var completedTests = 0;
            
            // Test each worker
            workers.forEach(function(worker) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'faq_ai_test_worker',
                        nonce: faqAiAdmin.nonce,
                        worker: worker
                    },
                    success: function(response) {
                        results[worker] = {
                            success: response.success,
                            message: response.success ? faqAiAdmin.strings.testSuccess : faqAiAdmin.strings.testFailed + response.data.message
                        };
                        completedTests++;
                        
                        if (completedTests === workers.length) {
                            displayTestResults();
                        }
                    },
                    error: function() {
                        results[worker] = {
                            success: false,
                            message: '<?php _e('Connection error', 'faq-ai-generator'); ?>'
                        };
                        completedTests++;
                        
                        if (completedTests === workers.length) {
                            displayTestResults();
                        }
                    }
                });
            });
            
            /**
             * Create appropriate test data for each worker type
             *
             * @param {string} worker - The worker key
             * @return {object} - Test data object
             */
            function createTestData(worker) {
                // Default test data
                var defaultData = {
                    test: true,
                    timestamp: Math.floor(Date.now() / 1000)
                };
                
                // Specific test data for each worker type
                switch(worker) {
                    case 'question':
                        return {
                            test: true,
                            timestamp: Math.floor(Date.now() / 1000),
                            content: "This is a test content for generating questions.",
                            mode: "test"
                        };
                    
                    case 'answer':
                        return {
                            test: true,
                            timestamp: Math.floor(Date.now() / 1000),
                            question: "What is this plugin used for?",
                            mode: "test"
                        };
                    
                    case 'enhance':
                        return {
                            test: true,
                            timestamp: Math.floor(Date.now() / 1000),
                            question: "What is this plugin used for?",
                            answer: "This plugin is used for generating FAQs.",
                            mode: "test"
                        };
                    
                    case 'seo':
                        return {
                            test: true,
                            timestamp: Math.floor(Date.now() / 1000),
                            question: "What is this plugin used for?",
                            answer: "This plugin is used for generating FAQs.",
                            mode: "test"
                        };
                    
                    case 'extract':
                        return {
                            test: true,
                            timestamp: Math.floor(Date.now() / 1000),
                            url: "https://example.com",
                            mode: "test"
                        };
                    
                    case 'topic':
                        return {
                            test: true,
                            timestamp: Math.floor(Date.now() / 1000),
                            content: "This is a test content for generating topics.",
                            mode: "test"
                        };
                    
                    case 'validate':
                        return {
                            test: true,
                            timestamp: Math.floor(Date.now() / 1000),
                            question: "What is this plugin used for?",
                            answer: "This plugin is used for generating FAQs.",
                            mode: "test"
                        };
                    
                    default:
                        return defaultData;
                }
            }
            
            // Display test results
            function displayTestResults() {
                var html = '<ul class="faq-ai-test-results-list">';
                
                workers.forEach(function(worker) {
                    var result = results[worker];
                    var className = result.success ? 'success' : 'error';
                    var displayName = worker.replace('-', ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
                    
                    html += '<li class="' + className + '">';
                    html += '<span class="worker-name">' + displayName + ':</span> ';
                    html += '<span class="result-message">' + result.message + '</span>';
                    html += '</li>';
                });
                
                html += '</ul>';
                resultsElement.html(html);
            }
        });
    });
</script>