/**
 * FAQ AI Generator - Initialization
 *
 * This script initializes the FAQ application by:
 * 1. Loading all required services
 * 2. Configuring service dependencies
 * 3. Connecting services together
 * 4. Starting the application
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/public/js
 */

(function($) {
    'use strict';
    
    // Application initialization
    const FaqInit = {
        // Configuration defaults
        config: {
            debug: false,
            services: {
                cloudflare: {
                    workerUrls: {
                        question: '',
                        answer: '',
                        enhance: '',
                        seo: '',
                        extract: '',
                        topic: '',
                        validate: ''
                    },
                    apiKey: ''
                },
                export: {
                    baseUrl: '',
                    defaultFormat: 'json-ld'
                }
            }
        },
        
        /**
         * Initialize the application
         */
        init: function() {
            console.log('FAQ AI Generator: Starting initialization...');
            
            // Load configuration from global variable if available
            this.loadConfig();
            
            // Initialize services
            this.initServices();
            
            // Start the main application
            if (typeof FAQApp !== 'undefined' && typeof FAQApp.init === 'function') {
                FAQApp.init();
                console.log('FAQ AI Generator: Application initialized successfully');
            } else {
                console.error('FAQ AI Generator: Main application not found');
            }
        },
        
        /**
         * Load configuration from global variable
         */
        loadConfig: function() {
            if (typeof faqAiConfig !== 'undefined') {
                // Deep merge configuration
                this.config = this.deepMerge(this.config, faqAiConfig);
                console.log('FAQ AI Generator: Configuration loaded from global variable');
            } else {
                console.warn('FAQ AI Generator: No global configuration found, using defaults');
            }
        },
        
        /**
         * Initialize all services
         */
        initServices: function() {
            // Initialize storage service first
            if (typeof StorageService !== 'undefined') {
                StorageService.init({
                    debug: this.config.debug
                });
                console.log('FAQ AI Generator: Storage service initialized');
            } else {
                console.warn('FAQ AI Generator: Storage service not found');
            }
            
            // Initialize UI controller
            if (typeof UiController !== 'undefined') {
                UiController.init({
                    debug: this.config.debug
                });
                console.log('FAQ AI Generator: UI controller initialized');
            } else {
                console.warn('FAQ AI Generator: UI controller not found');
            }
            
            // Initialize Cloudflare service
            if (typeof CloudflareService !== 'undefined') {
                CloudflareService.init({
                    workerUrls: this.config.services.cloudflare.workerUrls,
                    apiKey: this.config.services.cloudflare.apiKey,
                    debug: this.config.debug
                });
                console.log('FAQ AI Generator: Cloudflare service initialized');
            } else {
                console.warn('FAQ AI Generator: Cloudflare service not found');
            }
            
            // Initialize export service
            if (typeof ExportService !== 'undefined') {
                ExportService.init({
                    baseUrl: this.config.services.export.baseUrl,
                    defaultFormat: this.config.services.export.defaultFormat,
                    debug: this.config.debug
                });
                console.log('FAQ AI Generator: Export service initialized');
            } else {
                console.warn('FAQ AI Generator: Export service not found');
            }
        },
        
        /**
         * Deep merge two objects
         * 
         * @param {Object} target - Target object
         * @param {Object} source - Source object
         * @return {Object} - Merged object
         */
        deepMerge: function(target, source) {
            // Handle edge cases
            if (!source) return target;
            if (!target) return source;
            
            // Create a new object to avoid modifying either parameter
            const output = Object.assign({}, target);
            
            // Iterate through source properties
            Object.keys(source).forEach(key => {
                if (source[key] instanceof Object && key in target && target[key] instanceof Object) {
                    // Property exists in both objects and both are objects - recurse
                    output[key] = this.deepMerge(target[key], source[key]);
                } else {
                    // Simple property or doesn't exist in target - copy from source
                    output[key] = source[key];
                }
            });
            
            return output;
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        FaqInit.init();
    });
    
})(jQuery);