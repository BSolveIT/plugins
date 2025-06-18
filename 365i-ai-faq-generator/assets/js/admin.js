/**
 * Admin JavaScript for 365i AI FAQ Generator.
 * 
 * Handles minimal admin functionality for worker configuration
 * and settings only. No FAQ generation functionality.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Assets
 * @since 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Admin functionality object.
     */
    var AIFaqGenAdmin = {
        
        /**
         * Initialize admin functionality.
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.checkWorkerStatus();
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            // Worker configuration forms
            $(document).on('click', '.test-worker-connection', this.testWorkerConnection);
            $(document).on('click', '.reset-worker-usage', this.resetWorkerUsage);
            $(document).on('submit', '#workers-configuration-form', this.saveWorkerConfig);
            
            // Settings forms
            $(document).on('submit', '#ai-faq-gen-settings-form', this.saveSettings);
            
            // Worker status refresh
            $(document).on('click', '.refresh-worker-status', this.refreshWorkerStatus);
            
            // Form validation
            $(document).on('change', 'input[type="url"]', this.validateUrl);
            $(document).on('change', 'input[type="number"]', this.validateNumber);
            
            // Copy shortcode functionality
            $(document).on('click', '.copy-shortcode', this.copyShortcode);
        },

        /**
         * Initialize tooltips.
         */
        initTooltips: function() {
            // Add tooltips to elements with data-tooltip attribute
            $('[data-tooltip]').each(function() {
                var $element = $(this);
                var tooltipText = $element.data('tooltip');
                
                $element.attr('title', tooltipText);
            });
        },

        /**
         * Check worker status on page load.
         */
        checkWorkerStatus: function() {
            if ($('.worker-status-section').length > 0) {
                this.refreshWorkerStatus();
            }
        },

        /**
         * Test worker connection.
         *
         * Tests worker health by making a GET request to the /health endpoint.
         * All workers implement this standardized endpoint for connectivity checks.
         *
         * Note: When testing connections to worker URLs, browsers will automatically
         * request favicon.ico from the domain root. This causes 404 errors in Cloudflare
         * logs that can be safely ignored - they don't indicate actual connection problems.
         */
        testWorkerConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var workerName = $button.data('worker');
            var workerUrl = $button.closest('.worker-card, .worker-config-card').find('input[name*="[url]"]').val();
            
            if (!workerUrl) {
                AIFaqGenAdmin.showNotice('error', 'Please enter a worker URL first.');
                return;
            }
            
            // Clean URL - remove trailing slashes
            workerUrl = workerUrl.replace(/\/+$/, '');
            
            console.log('APPROACH: Using GET request to /health endpoint');
            console.log('Worker URL:', workerUrl);
            console.log('Health endpoint:', workerUrl + '/health');
            console.log('- Workers implement a /health endpoint that responds to GET requests');
            console.log('- This provides standardized health check functionality');
            
            $button.prop('disabled', true).text(aiFaqGen.strings.loading);
            
            // Debug: Log request URL and data
            console.log('Test connection request details:');
            console.log('AJAX endpoint:', aiFaqGen.ajaxUrl);
            console.log('Worker name:', workerName);
            console.log('Worker URL being tested:', workerUrl);
            console.log('Full request data:', {
                action: 'ai_faq_test_worker',
                nonce: aiFaqGen.nonce,
                worker_name: workerName,
                worker_url: workerUrl
            });
            
            // Make AJAX request to test connection
            $.ajax({
                url: aiFaqGen.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_faq_test_worker',
                    nonce: aiFaqGen.nonce,
                    worker_name: workerName,
                    worker_url: workerUrl
                },
                success: function(response) {
                    // Debug: Log response details
                    console.log('Test connection response received:', response);
                    console.log('Response success:', response.success);
                    console.log('Response data:', response.data);
                    
                    if (response.success) {
                        // Global notification
                        AIFaqGenAdmin.showNotice('success', 'Worker connection successful!');
                        
                        // Update worker card with test results
                        var $workerCard = $button.closest('.worker-card, .worker-config-card');
                        
                        // Create or update test results display
                        var $testResults = $workerCard.find('.test-results');
                        if ($testResults.length === 0) {
                            $testResults = $('<div class="test-results"></div>');
                            $workerCard.find('.worker-actions').before($testResults);
                        }
                        
                        // Format the test results
                        var healthData = response.data;
                        var resultHtml = '<div class="test-result-success">';
                        resultHtml += '<span class="dashicons dashicons-yes-alt"></span> ';
                        resultHtml += '<span class="test-status">Connection successful</span>';
                        resultHtml += '</div>';
                        
                        // Add note about favicon.ico 404 errors and health endpoint
                        resultHtml += '<div class="test-detail note"><small>Note: Cloudflare logs may show 404 errors for favicon.ico requests - these are normal browser behavior and can be ignored. The worker health check uses a GET request to the /health endpoint.</small></div>';
                        
                        // Add response time if available
                        if (healthData.response_time) {
                            resultHtml += '<div class="test-detail"><strong>Response time:</strong> ' + 
                                healthData.response_time + ' ms</div>';
                        }
                        
                        // Add health data if available
                        if (healthData.data && healthData.data.status) {
                            resultHtml += '<div class="test-detail"><strong>Status:</strong> ' + 
                                healthData.data.status + '</div>';
                        }
                        
                        $testResults.html(resultHtml).show().addClass('fade-in');
                        $workerCard.addClass('tested-success').removeClass('tested-error');
                    } else {
                        // Global notification
                        AIFaqGenAdmin.showNotice('error', response.data || aiFaqGen.strings.error);
                        
                        // Update worker card with error info
                        var $workerCard = $button.closest('.worker-card, .worker-config-card');
                        var $testResults = $workerCard.find('.test-results');
                        if ($testResults.length === 0) {
                            $testResults = $('<div class="test-results"></div>');
                            $workerCard.find('.worker-actions').before($testResults);
                        }
                        
                        var resultHtml = '<div class="test-result-error">';
                        resultHtml += '<span class="dashicons dashicons-warning"></span> ';
                        resultHtml += '<span class="test-status">Test failed</span>';
                        resultHtml += '</div>';
                        
                        $testResults.html(resultHtml).show().addClass('fade-in');
                        $workerCard.addClass('tested-error').removeClass('tested-success');
                    }
                },
                error: function() {
                    AIFaqGenAdmin.showNotice('error', aiFaqGen.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        },

        /**
         * Reset worker usage statistics.
         */
        resetWorkerUsage: function(e) {
            e.preventDefault();
            
            if (!confirm(aiFaqGen.strings.confirm)) {
                return;
            }
            
            var $button = $(this);
            var workerName = $button.data('worker');
            
            $button.prop('disabled', true).text(aiFaqGen.strings.loading);
            
            $.ajax({
                url: aiFaqGen.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_faq_reset_worker_usage',
                    nonce: aiFaqGen.nonce,
                    worker_name: workerName
                },
                success: function(response) {
                    if (response.success) {
                        AIFaqGenAdmin.showNotice('success', 'Worker usage statistics reset.');
                        // Update usage display
                        $button.closest('.worker-card, .worker-config-card').find('.usage-current').text('0');
                        $button.closest('.worker-card, .worker-config-card').find('.usage-fill').css('width', '0%');
                    } else {
                        AIFaqGenAdmin.showNotice('error', response.data || aiFaqGen.strings.error);
                    }
                },
                error: function() {
                    AIFaqGenAdmin.showNotice('error', aiFaqGen.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Reset Usage');
                }
            });
        },

        /**
         * Save settings form.
         */
        saveSettings: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"], button[type="submit"]');
            var originalText = $submitButton.text();
            
            $submitButton.prop('disabled', true).text(aiFaqGen.strings.loading);
            
            $.ajax({
                url: aiFaqGen.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=ai_faq_save_settings&nonce=' + aiFaqGen.nonce,
                success: function(response) {
                    if (response.success) {
                        AIFaqGenAdmin.showNotice('success', aiFaqGen.strings.success);
                    } else {
                        AIFaqGenAdmin.showNotice('error', response.data || aiFaqGen.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    // Debug: Log error details
                    console.log('Test connection error:');
                    console.log('Status:', status);
                    console.log('Error:', error);
                    console.log('Response text:', xhr.responseText);
                    console.log('Status code:', xhr.status);
                    
                    AIFaqGenAdmin.showNotice('error', aiFaqGen.strings.error);
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Save worker configuration form.
         *
         * Processes the worker configuration form via AJAX to save worker settings
         * including URLs, enabled status, and rate limits.
         */
        saveWorkerConfig: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('button[type="submit"]');
            var originalText = $submitButton.text();
            
            // Show loading state
            $submitButton.prop('disabled', true).text(aiFaqGen.strings.loading);
            
            // Debug logging
            console.log('Worker config submission started');
            console.log('Form data:', $form.serialize());
            
            // Debug logging - print form data that will be sent
            console.log('Form data being serialized:', $form.serializeArray());
            
            // Make AJAX request
            $.ajax({
                url: aiFaqGen.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=ai_faq_save_workers',
                success: function(response) {
                    console.log('Worker config response:', response);
                    
                    if (response.success) {
                        AIFaqGenAdmin.showNotice('success', response.data.message || aiFaqGen.strings.success);
                        
                        // Refresh worker status to show updated configuration
                        AIFaqGenAdmin.refreshWorkerStatus();
                    } else {
                        AIFaqGenAdmin.showNotice('error', response.data || aiFaqGen.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    // Debug: Log error details
                    console.log('Worker config error:');
                    console.log('Status:', status);
                    console.log('Error:', error);
                    console.log('Response text:', xhr.responseText);
                    console.log('Status code:', xhr.status);
                    
                    AIFaqGenAdmin.showNotice('error', aiFaqGen.strings.error);
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Copy shortcode to clipboard.
         */
        copyShortcode: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var shortcode = $button.data('shortcode') || '[ai_faq_generator]';
            
            // Create temporary input element
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(shortcode).select();
            
            try {
                document.execCommand('copy');
                AIFaqGenAdmin.showNotice('success', 'Shortcode copied to clipboard!');
                $button.text('Copied!');
                setTimeout(function() {
                    $button.text('Copy');
                }, 2000);
            } catch (err) {
                AIFaqGenAdmin.showNotice('error', 'Failed to copy shortcode');
            }
            
            $temp.remove();
        },

        /**
         * Refresh worker status.
         */
        refreshWorkerStatus: function(e) {
            if (e) {
                e.preventDefault();
            }
            
            var $statusSection = $('.worker-status-section');
            if ($statusSection.length === 0) {
                return;
            }
            
            $statusSection.addClass('loading');
            
            $.ajax({
                url: aiFaqGen.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_faq_get_worker_status',
                    nonce: aiFaqGen.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        AIFaqGenAdmin.updateWorkerStatus(response.data);
                    }
                },
                error: function() {
                    console.warn('Failed to refresh worker status');
                },
                complete: function() {
                    $statusSection.removeClass('loading');
                }
            });
        },

        /**
         * Update worker status display.
         */
        updateWorkerStatus: function(workers) {
            $.each(workers, function(workerName, status) {
                var $workerCard = $('.worker-card[data-worker="' + workerName + '"], .worker-config-card[data-worker="' + workerName + '"]');
                
                if ($workerCard.length > 0) {
                    // Update enabled/disabled state
                    $workerCard.removeClass('enabled disabled');
                    $workerCard.addClass(status.enabled ? 'enabled' : 'disabled');
                    
                    // Update usage display
                    $workerCard.find('.usage-current').text(status.current_usage);
                    
                    // Update usage bar
                    var usagePercent = status.rate_limit > 0 ? (status.current_usage / status.rate_limit) * 100 : 0;
                    $workerCard.find('.usage-fill').css('width', Math.min(usagePercent, 100) + '%');
                    
                    // Update status indicator
                    var $statusIndicator = $workerCard.find('.status-indicator');
                    if (status.enabled) {
                        $statusIndicator.html('<span class="dashicons dashicons-yes-alt"></span> Enabled');
                    } else {
                        $statusIndicator.html('<span class="dashicons dashicons-dismiss"></span> Disabled');
                    }
                }
            });
        },

        /**
         * Validate URL input.
         */
        validateUrl: function() {
            var $input = $(this);
            var url = $input.val();
            
            if (url && !AIFaqGenAdmin.isValidUrl(url)) {
                $input.addClass('error');
                AIFaqGenAdmin.showFieldError($input, 'Please enter a valid URL');
            } else {
                $input.removeClass('error');
                AIFaqGenAdmin.hideFieldError($input);
            }
        },

        /**
         * Validate number input.
         */
        validateNumber: function() {
            var $input = $(this);
            var value = parseInt($input.val());
            var min = parseInt($input.attr('min'));
            var max = parseInt($input.attr('max'));
            
            if (isNaN(value) || (min && value < min) || (max && value > max)) {
                $input.addClass('error');
                var message = 'Please enter a valid number';
                if (min && max) {
                    message += ' between ' + min + ' and ' + max;
                }
                AIFaqGenAdmin.showFieldError($input, message);
            } else {
                $input.removeClass('error');
                AIFaqGenAdmin.hideFieldError($input);
            }
        },

        /**
         * Check if URL is valid.
         */
        isValidUrl: function(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        },

        /**
         * Show field error message.
         */
        showFieldError: function($field, message) {
            var $error = $field.siblings('.field-error');
            if ($error.length === 0) {
                $error = $('<div class="field-error error"></div>');
                $field.after($error);
            }
            $error.text(message);
        },

        /**
         * Hide field error message.
         */
        hideFieldError: function($field) {
            $field.siblings('.field-error').remove();
        },

        /**
         * Show admin notice.
         */
        showNotice: function(type, message) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            // Insert after the header or at the top of content
            var $target = $('.ai-faq-gen-header').length > 0 ? $('.ai-faq-gen-header') : $('.ai-faq-gen-content, .wrap');
            $target.first().after($notice);
            
            // Auto-dismiss success notices
            if (type === 'success') {
                setTimeout(function() {
                    $notice.fadeOut(function() {
                        $notice.remove();
                    });
                }, 3000);
            }
            
            // Handle manual dismiss
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            });
        },

        /**
         * Utility function to get URL parameters.
         */
        getUrlParameter: function(name) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            var results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        },

        /**
         * Format numbers with commas.
         */
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
    };

    /**
     * Initialize when document is ready.
     */
    $(document).ready(function() {
        AIFaqGenAdmin.init();
    });

    /**
     * Auto-refresh worker status every 5 minutes.
     */
    setInterval(function() {
        if ($('.worker-status-section').length > 0) {
            AIFaqGenAdmin.refreshWorkerStatus();
        }
    }, 300000); // 5 minutes

    /**
     * Export to global scope for external access.
     */
    window.AIFaqGenAdmin = AIFaqGenAdmin;

})(jQuery);