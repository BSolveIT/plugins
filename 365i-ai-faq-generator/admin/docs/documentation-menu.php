<?php
/**
 * Documentation Menu
 *
 * Main entry point for all documentation pages
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/admin/docs
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="faq-ai-documentation documentation-menu">
    <div class="faq-ai-doc-header">
        <h1><?php _e('FAQ AI Generator - Documentation', 'faq-ai-generator'); ?></h1>
        <p class="faq-ai-doc-description"><?php _e('Welcome to the FAQ AI Generator documentation. Choose a topic below to learn more about the plugin.', 'faq-ai-generator'); ?></p>
    </div>
    
    <div class="doc-menu-container">
        <div class="doc-menu-item">
            <div class="doc-menu-icon">
                <span class="dashicons dashicons-book-alt"></span>
            </div>
            <div class="doc-menu-content">
                <h2><?php _e('User Guide', 'faq-ai-generator'); ?></h2>
                <p><?php _e('A comprehensive guide to setting up and using the FAQ AI Generator plugin.', 'faq-ai-generator'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=faq-ai-generator-docs&doc=user-guide'); ?>" class="doc-menu-button"><?php _e('Read User Guide', 'faq-ai-generator'); ?></a>
            </div>
        </div>
        
        <div class="doc-menu-item">
            <div class="doc-menu-icon">
                <span class="dashicons dashicons-shortcode"></span>
            </div>
            <div class="doc-menu-content">
                <h2><?php _e('Shortcode Parameters', 'faq-ai-generator'); ?></h2>
                <p><?php _e('A complete reference for all available shortcode parameters to customize your FAQ display.', 'faq-ai-generator'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=faq-ai-generator-docs&doc=shortcode-parameters'); ?>" class="doc-menu-button"><?php _e('View Parameters', 'faq-ai-generator'); ?></a>
            </div>
        </div>
        
        <div class="doc-menu-item">
            <div class="doc-menu-icon">
                <span class="dashicons dashicons-code-standards"></span>
            </div>
            <div class="doc-menu-content">
                <h2><?php _e('FAQ Schema Types', 'faq-ai-generator'); ?></h2>
                <p><?php _e('Learn about the different schema formats supported by the FAQ AI Generator.', 'faq-ai-generator'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=faq-ai-generator-docs&doc=faq-schema-types'); ?>" class="doc-menu-button"><?php _e('Explore Schema Types', 'faq-ai-generator'); ?></a>
            </div>
        </div>
        
        <div class="doc-menu-item">
            <div class="doc-menu-icon">
                <span class="dashicons dashicons-cloud"></span>
            </div>
            <div class="doc-menu-content">
                <h2><?php _e('Cloudflare Workers', 'faq-ai-generator'); ?></h2>
                <p><?php _e('Information about the Cloudflare Workers that power the AI capabilities of the plugin.', 'faq-ai-generator'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=faq-ai-generator-settings&tab=worker-configuration'); ?>" class="doc-menu-button"><?php _e('Configure Workers', 'faq-ai-generator'); ?></a>
            </div>
        </div>
        
        <div class="doc-menu-item">
            <div class="doc-menu-icon">
                <span class="dashicons dashicons-admin-generic"></span>
            </div>
            <div class="doc-menu-content">
                <h2><?php _e('Plugin Settings', 'faq-ai-generator'); ?></h2>
                <p><?php _e('Configure the plugin settings to customize its behavior.', 'faq-ai-generator'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=faq-ai-generator-settings'); ?>" class="doc-menu-button"><?php _e('Go to Settings', 'faq-ai-generator'); ?></a>
            </div>
        </div>
        
        <div class="doc-menu-item">
            <div class="doc-menu-icon">
                <span class="dashicons dashicons-editor-help"></span>
            </div>
            <div class="doc-menu-content">
                <h2><?php _e('Troubleshooting', 'faq-ai-generator'); ?></h2>
                <p><?php _e('Solutions to common issues and answers to frequently asked questions.', 'faq-ai-generator'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=faq-ai-generator-docs&doc=user-guide#troubleshooting'); ?>" class="doc-menu-button"><?php _e('View Troubleshooting', 'faq-ai-generator'); ?></a>
            </div>
        </div>
    </div>
    
    <div class="doc-menu-footer">
        <p><?php _e('Need more help? Contact our support team at <a href="mailto:support@365i.com">support@365i.com</a>.', 'faq-ai-generator'); ?></p>
    </div>
</div>

<style>
    /* Additional styles specific to the documentation menu */
    .doc-menu-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .doc-menu-item {
        display: flex;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .doc-menu-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .doc-menu-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 80px;
        background-color: #4a6bef;
        color: #fff;
    }
    
    .doc-menu-icon .dashicons {
        font-size: 30px;
        width: 30px;
        height: 30px;
    }
    
    .doc-menu-content {
        flex: 1;
        padding: 20px;
    }
    
    .doc-menu-content h2 {
        margin-top: 0;
        margin-bottom: 10px;
        font-size: 20px;
        color: #23282d;
    }
    
    .doc-menu-content p {
        margin-bottom: 15px;
        color: #666;
    }
    
    .doc-menu-button {
        display: inline-block;
        padding: 8px 15px;
        background-color: #4a6bef;
        color: #fff;
        text-decoration: none;
        border-radius: 4px;
        font-weight: 500;
        transition: background-color 0.2s;
    }
    
    .doc-menu-button:hover {
        background-color: #3a5bd9;
        color: #fff;
    }
    
    .doc-menu-footer {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #eaeaea;
        text-align: center;
        color: #666;
    }
    
    .doc-menu-footer a {
        color: #4a6bef;
        text-decoration: none;
    }
    
    .doc-menu-footer a:hover {
        text-decoration: underline;
    }
    
    @media screen and (max-width: 782px) {
        .doc-menu-container {
            grid-template-columns: 1fr;
        }
    }
</style>