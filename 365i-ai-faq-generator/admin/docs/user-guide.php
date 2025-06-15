<?php
/**
 * User Guide Documentation
 *
 * Comprehensive documentation for using the FAQ AI Generator plugin
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/admin/docs
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="faq-ai-documentation user-guide">
    <div class="faq-ai-doc-header">
        <h1><?php _e('FAQ AI Generator - User Guide', 'faq-ai-generator'); ?></h1>
        <p class="faq-ai-doc-description"><?php _e('A comprehensive guide to setting up and using the FAQ AI Generator plugin.', 'faq-ai-generator'); ?></p>
    </div>
    
    <div class="faq-ai-doc-navigation">
        <ul>
            <li><a href="#getting-started"><?php _e('Getting Started', 'faq-ai-generator'); ?></a></li>
            <li><a href="#setup-cloudflare"><?php _e('Setting Up Cloudflare Workers', 'faq-ai-generator'); ?></a></li>
            <li><a href="#api-key"><?php _e('Obtaining an API Key', 'faq-ai-generator'); ?></a></li>
            <li><a href="#creating-faqs"><?php _e('Creating FAQs', 'faq-ai-generator'); ?></a></li>
            <li><a href="#ai-generation"><?php _e('AI-Powered Generation', 'faq-ai-generator'); ?></a></li>
            <li><a href="#using-shortcode"><?php _e('Using the Shortcode', 'faq-ai-generator'); ?></a></li>
            <li><a href="#schema-export"><?php _e('Schema Export', 'faq-ai-generator'); ?></a></li>
            <li><a href="#troubleshooting"><?php _e('Troubleshooting', 'faq-ai-generator'); ?></a></li>
        </ul>
    </div>
    
    <div class="faq-ai-doc-section" id="getting-started">
        <h2><?php _e('Getting Started', 'faq-ai-generator'); ?></h2>
        <p><?php _e('The FAQ AI Generator plugin allows you to create, manage, and optimize FAQs for your WordPress site using advanced AI technology powered by Cloudflare Workers.', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('System Requirements', 'faq-ai-generator'); ?></h3>
        <ul>
            <li><?php _e('WordPress 5.6 or higher', 'faq-ai-generator'); ?></li>
            <li><?php _e('PHP 7.4 or higher', 'faq-ai-generator'); ?></li>
            <li><?php _e('Cloudflare account (for AI worker access)', 'faq-ai-generator'); ?></li>
        </ul>
        
        <h3><?php _e('Installation', 'faq-ai-generator'); ?></h3>
        <ol>
            <li><?php _e('Upload the plugin files to the <code>/wp-content/plugins/faq-ai-generator</code> directory.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Activate the plugin through the \'Plugins\' menu in WordPress.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Navigate to the FAQ AI Generator settings page to configure the plugin.', 'faq-ai-generator'); ?></li>
        </ol>
    </div>
    
    <div class="faq-ai-doc-section" id="setup-cloudflare">
        <h2><?php _e('Setting Up Cloudflare Workers', 'faq-ai-generator'); ?></h2>
        <p><?php _e('The FAQ AI Generator uses Cloudflare Workers to power its AI capabilities. These workers handle different aspects of FAQ generation and optimization.', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Default Workers', 'faq-ai-generator'); ?></h3>
        <p><?php _e('The plugin comes pre-configured with the following Cloudflare Workers:', 'faq-ai-generator'); ?></p>
        
        <table class="faq-ai-doc-table">
            <thead>
                <tr>
                    <th><?php _e('Worker', 'faq-ai-generator'); ?></th>
                    <th><?php _e('Purpose', 'faq-ai-generator'); ?></th>
                    <th><?php _e('Default URL', 'faq-ai-generator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php _e('Question Generator', 'faq-ai-generator'); ?></td>
                    <td><?php _e('Generates and improves questions based on content.', 'faq-ai-generator'); ?></td>
                    <td><code>https://faq-realtime-assistant-worker.winter-cake-bf57.workers.dev</code></td>
                </tr>
                <tr>
                    <td><?php _e('Answer Generator', 'faq-ai-generator'); ?></td>
                    <td><?php _e('Creates detailed answers for questions.', 'faq-ai-generator'); ?></td>
                    <td><code>https://faq-answer-generator-worker.winter-cake-bf57.workers.dev</code></td>
                </tr>
                <tr>
                    <td><?php _e('FAQ Enhancer', 'faq-ai-generator'); ?></td>
                    <td><?php _e('Enhances existing FAQs with additional details.', 'faq-ai-generator'); ?></td>
                    <td><code>https://faq-enhancement-worker.winter-cake-bf57.workers.dev</code></td>
                </tr>
                <tr>
                    <td><?php _e('SEO Analyzer', 'faq-ai-generator'); ?></td>
                    <td><?php _e('Analyzes FAQ content for SEO optimization.', 'faq-ai-generator'); ?></td>
                    <td><code>https://faq-seo-analyzer-worker.winter-cake-bf57.workers.dev</code></td>
                </tr>
                <tr>
                    <td><?php _e('FAQ Extractor', 'faq-ai-generator'); ?></td>
                    <td><?php _e('Extracts FAQs from external URLs and content.', 'faq-ai-generator'); ?></td>
                    <td><code>https://url-to-faq-generator-worker.winter-cake-bf57.workers.dev</code></td>
                </tr>
                <tr>
                    <td><?php _e('Topic Generator', 'faq-ai-generator'); ?></td>
                    <td><?php _e('Generates FAQ topics based on website content.', 'faq-ai-generator'); ?></td>
                    <td><code>https://faq-topic-generator-worker.winter-cake-bf57.workers.dev</code></td>
                </tr>
                <tr>
                    <td><?php _e('Content Validator', 'faq-ai-generator'); ?></td>
                    <td><?php _e('Validates and checks FAQ content for quality.', 'faq-ai-generator'); ?></td>
                    <td><code>https://faq-content-validator-worker.winter-cake-bf57.workers.dev</code></td>
                </tr>
            </tbody>
        </table>
        
        <h3><?php _e('Custom Workers', 'faq-ai-generator'); ?></h3>
        <p><?php _e('You can also use your own custom Cloudflare Workers by changing the URLs in the Worker Configuration tab of the settings page.', 'faq-ai-generator'); ?></p>
    </div>
    
    <div class="faq-ai-doc-section" id="api-key">
        <h2><?php _e('Obtaining an API Key', 'faq-ai-generator'); ?></h2>
        <p><?php _e('To use the Cloudflare Workers, you need an API key for authentication. Here\'s how to obtain one:', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Cloudflare API Key', 'faq-ai-generator'); ?></h3>
        <p><?php _e('The API key authenticates your requests to the Cloudflare Workers. This ensures secure access to the AI services.', 'faq-ai-generator'); ?></p>
        
        <ol>
            <li><?php _e('Sign up for a Cloudflare account at <a href="https://dash.cloudflare.com/sign-up" target="_blank">https://dash.cloudflare.com/sign-up</a> if you don\'t already have one.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Log in to your Cloudflare dashboard.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Navigate to "My Profile" > "API Tokens".', 'faq-ai-generator'); ?></li>
            <li><?php _e('Click "Create Token".', 'faq-ai-generator'); ?></li>
            <li><?php _e('Select "Create Custom Token".', 'faq-ai-generator'); ?></li>
            <li><?php _e('Set the following permissions:', 'faq-ai-generator'); ?>
                <ul>
                    <li><?php _e('Account > Worker Script > Read', 'faq-ai-generator'); ?></li>
                    <li><?php _e('Account > Workers Routes > Read', 'faq-ai-generator'); ?></li>
                </ul>
            </li>
            <li><?php _e('Set the token name and an optional expiration.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Click "Create Token" and copy the generated token.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Enter this token in the API Key field in the Worker Configuration tab of the plugin settings.', 'faq-ai-generator'); ?></li>
        </ol>
        
        <div class="faq-ai-doc-note">
            <p><strong><?php _e('Note:', 'faq-ai-generator'); ?></strong> <?php _e('The API key provided in this plugin will only work with the default Cloudflare Workers. If you are using your own custom workers, you\'ll need to ensure they accept the same API key format.', 'faq-ai-generator'); ?></p>
        </div>
    </div>
    
    <div class="faq-ai-doc-section" id="creating-faqs">
        <h2><?php _e('Creating FAQs', 'faq-ai-generator'); ?></h2>
        <p><?php _e('The FAQ AI Generator provides multiple ways to create and manage FAQs:', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Manual Creation', 'faq-ai-generator'); ?></h3>
        <ol>
            <li><?php _e('Create a new page or post where you want to add FAQs.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Add the <code>[ai_faq_generator]</code> shortcode to the page.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Click "Add FAQ" to add a new question and answer pair.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Enter your question and answer in the rich text editors.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Add more FAQs as needed and arrange them using drag and drop.', 'faq-ai-generator'); ?></li>
        </ol>
        
        <h3><?php _e('Using Templates', 'faq-ai-generator'); ?></h3>
        <ol>
            <li><?php _e('On the FAQ editor page, click the "Templates" button.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Choose a template category that fits your needs (Business, Product, Service, etc.).', 'faq-ai-generator'); ?></li>
            <li><?php _e('The template FAQs will be added to your page.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Customize the questions and answers as needed.', 'faq-ai-generator'); ?></li>
        </ol>
        
        <h3><?php _e('Importing from URL', 'faq-ai-generator'); ?></h3>
        <ol>
            <li><?php _e('On the FAQ editor page, click the "Fetch URL" button.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Enter the URL of a page that contains FAQs.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Click "Fetch" to extract FAQs from the page.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Review the extracted FAQs and click "Import All FAQs" to add them to your page.', 'faq-ai-generator'); ?></li>
        </ol>
    </div>
    
    <div class="faq-ai-doc-section" id="ai-generation">
        <h2><?php _e('AI-Powered Generation', 'faq-ai-generator'); ?></h2>
        <p><?php _e('The FAQ AI Generator offers several AI-powered features to help you create and optimize your FAQs:', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Question Improvement', 'faq-ai-generator'); ?></h3>
        <ol>
            <li><?php _e('Enter a basic question in the question editor.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Click the "Improve Question" button.', 'faq-ai-generator'); ?></li>
            <li><?php _e('The AI will suggest improvements to make your question more effective.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Review the suggestions and click "Apply" to use one.', 'faq-ai-generator'); ?></li>
        </ol>
        
        <h3><?php _e('Answer Generation', 'faq-ai-generator'); ?></h3>
        <ol>
            <li><?php _e('After entering a question, click the "Generate Answer" button.', 'faq-ai-generator'); ?></li>
            <li><?php _e('The AI will create a detailed answer based on your question.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Review the generated answer and click "Apply" to use it.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Edit the answer as needed to fit your specific requirements.', 'faq-ai-generator'); ?></li>
        </ol>
        
        <h3><?php _e('FAQ Enhancement', 'faq-ai-generator'); ?></h3>
        <ol>
            <li><?php _e('After creating a basic FAQ, click the "Enhance" button.', 'faq-ai-generator'); ?></li>
            <li><?php _e('The AI will suggest improvements to make your FAQ more comprehensive.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Review the suggestions and click "Apply" to use one.', 'faq-ai-generator'); ?></li>
        </ol>
        
        <h3><?php _e('SEO Analysis', 'faq-ai-generator'); ?></h3>
        <ol>
            <li><?php _e('After creating an FAQ, click the "SEO" button.', 'faq-ai-generator'); ?></li>
            <li><?php _e('The AI will analyze your FAQ for SEO effectiveness and provide a score.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Review the suggestions for improvement and implement them as needed.', 'faq-ai-generator'); ?></li>
        </ol>
    </div>
    
    <div class="faq-ai-doc-section" id="using-shortcode">
        <h2><?php _e('Using the Shortcode', 'faq-ai-generator'); ?></h2>
        <p><?php _e('The FAQ AI Generator provides a flexible shortcode to display your FAQs on any page or post:', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Basic Usage', 'faq-ai-generator'); ?></h3>
        <pre><code>[ai_faq_generator]</code></pre>
        <p><?php _e('This will display the FAQ editor with default settings.', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Advanced Usage', 'faq-ai-generator'); ?></h3>
        <pre><code>[ai_faq_generator theme="custom" initial_mode="topic" faq_count="5" show_seo="true" primary_color="#4a6bef" secondary_color="#1abc9c" text_color="#333333" background_color="#ffffff"]</code></pre>
        
        <p><?php _e('See the Shortcode Parameters documentation for a full list of available options.', 'faq-ai-generator'); ?></p>
    </div>
    
    <div class="faq-ai-doc-section" id="schema-export">
        <h2><?php _e('Schema Export', 'faq-ai-generator'); ?></h2>
        <p><?php _e('The FAQ AI Generator can automatically generate structured data schema for your FAQs, which helps search engines understand and display your content:', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Export Formats', 'faq-ai-generator'); ?></h3>
        <ul>
            <li><strong><?php _e('JSON-LD', 'faq-ai-generator'); ?></strong>: <?php _e('The recommended format for most websites.', 'faq-ai-generator'); ?></li>
            <li><strong><?php _e('Microdata', 'faq-ai-generator'); ?></strong>: <?php _e('HTML-embedded schema format.', 'faq-ai-generator'); ?></li>
            <li><strong><?php _e('RDFa', 'faq-ai-generator'); ?></strong>: <?php _e('Resource Description Framework in Attributes format.', 'faq-ai-generator'); ?></li>
            <li><strong><?php _e('HTML Only', 'faq-ai-generator'); ?></strong>: <?php _e('Clean HTML without schema markup.', 'faq-ai-generator'); ?></li>
        </ul>
        
        <h3><?php _e('Exporting Schema', 'faq-ai-generator'); ?></h3>
        <ol>
            <li><?php _e('Create your FAQs using the editor.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Click on the "Export" tab.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Select your desired schema format.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Enter the base URL for your FAQ page (optional).', 'faq-ai-generator'); ?></li>
            <li><?php _e('Click "Generate Schema" to create the schema code.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Use "Copy to Clipboard" to copy the schema or "Download" to save it as a file.', 'faq-ai-generator'); ?></li>
        </ol>
        
        <h3><?php _e('Using the Schema', 'faq-ai-generator'); ?></h3>
        <p><?php _e('For JSON-LD schema (recommended):', 'faq-ai-generator'); ?></p>
        <ol>
            <li><?php _e('Copy the generated JSON-LD code.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Add it to your website\'s head section or use a schema plugin to add it to your page.', 'faq-ai-generator'); ?></li>
        </ol>
        
        <p><?php _e('For Microdata or RDFa schema:', 'faq-ai-generator'); ?></p>
        <ol>
            <li><?php _e('Copy the generated HTML code.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Replace your existing FAQ HTML with this schema-enhanced version.', 'faq-ai-generator'); ?></li>
        </ol>
    </div>
    
    <div class="faq-ai-doc-section" id="troubleshooting">
        <h2><?php _e('Troubleshooting', 'faq-ai-generator'); ?></h2>
        <p><?php _e('Common issues and their solutions:', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('API Connection Issues', 'faq-ai-generator'); ?></h3>
        <ul>
            <li><strong><?php _e('Problem', 'faq-ai-generator'); ?>:</strong> <?php _e('Cannot connect to AI workers.', 'faq-ai-generator'); ?></li>
            <li><strong><?php _e('Solution', 'faq-ai-generator'); ?>:</strong> <?php _e('Verify your API key is correct and that the worker URLs are accessible.', 'faq-ai-generator'); ?></li>
        </ul>
        
        <h3><?php _e('Rate Limit Exceeded', 'faq-ai-generator'); ?></h3>
        <ul>
            <li><strong><?php _e('Problem', 'faq-ai-generator'); ?>:</strong> <?php _e('Receiving "Rate limit exceeded" errors.', 'faq-ai-generator'); ?></li>
            <li><strong><?php _e('Solution', 'faq-ai-generator'); ?>:</strong> <?php _e('Wait for the rate limit to reset or increase the rate limit in the Worker Configuration settings.', 'faq-ai-generator'); ?></li>
        </ul>
        
        <h3><?php _e('FAQs Not Saving', 'faq-ai-generator'); ?></h3>
        <ul>
            <li><strong><?php _e('Problem', 'faq-ai-generator'); ?>:</strong> <?php _e('FAQs are not being saved properly.', 'faq-ai-generator'); ?></li>
            <li><strong><?php _e('Solution', 'faq-ai-generator'); ?>:</strong> <?php _e('Check browser localStorage functionality and ensure you have not exceeded storage limits.', 'faq-ai-generator'); ?></li>
        </ul>
        
        <h3><?php _e('Schema Not Working', 'faq-ai-generator'); ?></h3>
        <ul>
            <li><strong><?php _e('Problem', 'faq-ai-generator'); ?>:</strong> <?php _e('Generated schema is not being recognized by search engines.', 'faq-ai-generator'); ?></li>
            <li><strong><?php _e('Solution', 'faq-ai-generator'); ?>:</strong> <?php _e('Validate your schema using Google\'s Structured Data Testing Tool and ensure it\'s properly implemented on your page.', 'faq-ai-generator'); ?></li>
        </ul>
        
        <h3><?php _e('Getting Support', 'faq-ai-generator'); ?></h3>
        <p><?php _e('If you encounter issues not covered in this guide, please contact our support team:', 'faq-ai-generator'); ?></p>
        <ul>
            <li><?php _e('Email: support@365i.com', 'faq-ai-generator'); ?></li>
            <li><?php _e('Support Ticket: https://365i.com/support', 'faq-ai-generator'); ?></li>
        </ul>
    </div>
</div>