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
            // Remove AJAX interception for workers form - let PHP handle it natively
            // $(document).on('submit', '#workers-configuration-form', this.saveWorkerConfig);

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
            
            $button.prop('disabled', true).text(aiFaqGen.strings.loading);
            
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
                        
                        // Format the test results with standardized worker data
                        var healthData = response.data;
                        var resultHtml = '<div class="test-result-success">';
                        resultHtml += '<span class="dashicons dashicons-yes-alt"></span> ';
                        resultHtml += '<span class="test-status">Connection successful</span>';
                        
                        // Add quick status summary if available
                        if (healthData.worker_info && healthData.worker_info.status_message) {
                            resultHtml += '<div class="status-summary">' + healthData.worker_info.status_message + '</div>';
                        }
                        
                        resultHtml += '</div>';
                        
                        // Create responsive grid layout
                        resultHtml += '<div class="worker-test-grid">';
                        
                        // Left column - Core Information
                        resultHtml += '<div class="worker-test-column">';
                        
                        // Add response time if available
                        if (healthData.response_time) {
                            resultHtml += '<div class="test-detail"><strong>Response Time:</strong> ' +
                                healthData.response_time + ' ms</div>';
                        }
                        
                        // Display standardized worker health data
                        if (healthData.data) {
                            var workerData = healthData.data;
                            
                            // Core worker information - LEFT COLUMN
                            if (workerData.worker) {
                                resultHtml += '<div class="test-detail"><strong>Worker:</strong> ' +
                                    workerData.worker + '</div>';
                            }
                            
                            if (workerData.status) {
                                resultHtml += '<div class="test-detail"><strong>Status:</strong> ' +
                                    '<span class="status-indicator status-' + workerData.status + '">' +
                                    workerData.status.charAt(0).toUpperCase() + workerData.status.slice(1) +
                                    '</span></div>';
                            }
                            
                            if (workerData.version) {
                                resultHtml += '<div class="test-detail"><strong>Version:</strong> ' + workerData.version + '</div>';
                            }
                            
                            if (workerData.worker_type) {
                                resultHtml += '<div class="test-detail"><strong>Worker Type:</strong> ' + workerData.worker_type + '</div>';
                            }
                            
                            // AI Model Information
                            if (workerData.current_model) {
                                var modelDisplay = workerData.model_display_name || workerData.current_model;
                                resultHtml += '<div class="test-detail"><strong>AI Model:</strong> ' + modelDisplay + '</div>';
                                
                                if (workerData.model_source) {
                                    var sourceLabel = workerData.model_source.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                    resultHtml += '<div class="test-detail"><strong>Model Source:</strong> ' + sourceLabel + '</div>';
                                }
                            }
                            
                            resultHtml += '</div>'; // End left column
                            
                            // Right column - Features & Status
                            resultHtml += '<div class="worker-test-column">';
                            
                            // Cache Status
                            if (workerData.cache_status) {
                                var cacheClass = workerData.cache_status === 'active' ? 'status-healthy' : 'status-warning';
                                resultHtml += '<div class="test-detail"><strong>Cache:</strong> ' +
                                    '<span class="status-indicator ' + cacheClass + '">' +
                                    workerData.cache_status.charAt(0).toUpperCase() + workerData.cache_status.slice(1) +
                                    '</span></div>';
                            }
                            
                            // Rate Limiting Status
                            if (workerData.rate_limiting) {
                                var rateStatus = workerData.rate_limiting.enabled ? 'Enabled' : 'Disabled';
                                var rateClass = workerData.rate_limiting.enabled ? 'status-healthy' : 'status-warning';
                                if (workerData.rate_limiting.enhanced) {
                                    rateStatus += ' (Enhanced)';
                                }
                                resultHtml += '<div class="test-detail"><strong>Rate Limiting:</strong> ' +
                                    '<span class="status-indicator ' + rateClass + '">' + rateStatus + '</span></div>';
                            }
                            
                            // Capabilities
                            if (workerData.capabilities && Array.isArray(workerData.capabilities) && workerData.capabilities.length > 0) {
                                resultHtml += '<div class="test-detail-section"><strong>Capabilities:</strong>';
                                resultHtml += '<div class="feature-chips">';
                                workerData.capabilities.forEach(function(capability) {
                                    var displayCapability = capability.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                    resultHtml += '<span class="feature-chip">' + displayCapability + '</span>';
                                });
                                resultHtml += '</div></div>';
                            }
                            
                            // Timestamp
                            if (workerData.timestamp) {
                                var formattedTime = new Date(workerData.timestamp).toLocaleString();
                                resultHtml += '<div class="test-detail"><strong>Tested:</strong> ' + formattedTime + '</div>';
                            }
                            
                            // Test Method
                            if (workerData.test_method) {
                                resultHtml += '<div class="test-detail"><strong>Method:</strong> ' + workerData.test_method + '</div>';
                            }
                            
                            resultHtml += '</div>'; // End right column
                        }
                        
                        resultHtml += '</div>'; // End grid
                        
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
            
            // Make AJAX request
            $.ajax({
                url: aiFaqGen.ajaxUrl,
                type: 'POST',
                data: $form.serialize() + '&action=ai_faq_save_workers',
                success: function(response) {
                    
                    if (response.success) {
                        AIFaqGenAdmin.showNotice('success', response.data.message || aiFaqGen.strings.success);
                        
                        // Refresh worker status to show updated configuration
                        AIFaqGenAdmin.refreshWorkerStatus();
                    } else {
                        AIFaqGenAdmin.showNotice('error', response.data || aiFaqGen.strings.error);
                    }
                },
                error: function(xhr, status, error) {
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
                    // Error silently handled
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