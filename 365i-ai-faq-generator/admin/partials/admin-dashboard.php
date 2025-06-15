<?php
/**
 * Provide a admin area view for the plugin dashboard
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

<div class="wrap faq-ai-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="faq-ai-dashboard-header">
        <div class="faq-ai-dashboard-welcome">
            <h2><?php _e('Welcome to AI FAQ Generator', 'faq-ai-generator'); ?></h2>
            <p><?php _e('Create professional FAQ content using AI with schema generation capabilities, rich text editing, and drag-and-drop functionality.', 'faq-ai-generator'); ?></p>
        </div>
        
        <div class="faq-ai-dashboard-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=faq-ai-generator-settings')); ?>" class="button button-primary"><?php _e('Configure Settings', 'faq-ai-generator'); ?></a>
            <button id="faq-ai-reset-all-limits" class="button"><?php _e('Reset All Rate Limits', 'faq-ai-generator'); ?></button>
        </div>
    </div>
    
    <div class="faq-ai-dashboard-cards">
        <div class="faq-ai-card">
            <h3><?php _e('Shortcode Usage', 'faq-ai-generator'); ?></h3>
            <p><?php _e('Add the FAQ generator to any page or post using this shortcode:', 'faq-ai-generator'); ?></p>
            <code>[ai_faq_generator]</code>
            
            <p><?php _e('Customize with parameters:', 'faq-ai-generator'); ?></p>
            <pre>[ai_faq_generator theme="default" initial_mode="topic" faq_count="10" show_seo="true" primary_color="#667eea" secondary_color="#764ba2" text_color="#2c3e50" background_color="#ffffff"]</pre>
        </div>
        
        <div class="faq-ai-card">
            <h3><?php _e('Worker Status', 'faq-ai-generator'); ?></h3>
            <div class="faq-ai-worker-status">
                <?php
                $workers = get_option('faq_ai_generator_workers', array());
                foreach ($workers as $key => $worker):
                    $display_name = $this->get_worker_display_name($key);
                    $enabled = isset($worker['enabled']) && $worker['enabled'];
                    $status_class = $enabled ? 'enabled' : 'disabled';
                ?>
                <div class="faq-ai-worker-item <?php echo esc_attr($status_class); ?>" data-worker="<?php echo esc_attr($key); ?>">
                    <span class="faq-ai-worker-name"><?php echo esc_html($display_name); ?></span>
                    <span class="faq-ai-worker-status-indicator"></span>
                    <span class="faq-ai-worker-status-text">
                        <?php echo $enabled ? esc_html__('Enabled', 'faq-ai-generator') : esc_html__('Disabled', 'faq-ai-generator'); ?>
                    </span>
                    <div class="faq-ai-worker-rate-limit">
                        <span class="faq-ai-worker-rate-limit-label"><?php _e('Rate Limit:', 'faq-ai-generator'); ?></span>
                        <span class="faq-ai-worker-rate-limit-value">
                            <?php echo esc_html(sprintf(__('%d/hour', 'faq-ai-generator'), $worker['rate_limit'])); ?>
                        </span>
                    </div>
                    <div class="faq-ai-worker-actions">
                        <button class="button faq-ai-test-worker" data-worker="<?php echo esc_attr($key); ?>">
                            <?php _e('Test Connection', 'faq-ai-generator'); ?>
                        </button>
                        <button class="button faq-ai-reset-limit" data-worker="<?php echo esc_attr($key); ?>">
                            <?php _e('Reset Limit', 'faq-ai-generator'); ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="faq-ai-dashboard-usage">
        <h3><?php _e('Getting Started', 'faq-ai-generator'); ?></h3>
        <ol>
            <li><?php _e('Configure your AI workers in the settings page', 'faq-ai-generator'); ?></li>
            <li><?php _e('Add the shortcode to any page or post', 'faq-ai-generator'); ?></li>
            <li><?php _e('Create FAQs using the intuitive interface with AI assistance', 'faq-ai-generator'); ?></li>
            <li><?php _e('Export your FAQs in various schema formats', 'faq-ai-generator'); ?></li>
        </ol>
        
        <h3><?php _e('Documentation', 'faq-ai-generator'); ?></h3>
        <p><?php _e('For more information on using the AI FAQ Generator, please refer to the following resources:', 'faq-ai-generator'); ?></p>
        <ul>
            <li><a href="#" target="_blank"><?php _e('User Guide', 'faq-ai-generator'); ?></a></li>
            <li><a href="#" target="_blank"><?php _e('Shortcode Parameters', 'faq-ai-generator'); ?></a></li>
            <li><a href="#" target="_blank"><?php _e('FAQ Schema Types', 'faq-ai-generator'); ?></a></li>
        </ul>
    </div>
</div>