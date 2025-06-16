/**
 * Admin JavaScript for 365i AI FAQ Generator.
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
            
            // Settings forms
            $(document).on('submit', '#ai-faq-gen-settings-form', this.saveSettings);
            
            // Dashboard interactions
            $(document).on('click', '#show-shortcode-help', this.toggleShortcodeHelp);
            $(document).on('click', '.copy-shortcode', this.copyShortcode);
            
            // Worker status refresh
            $(document).on('click', '.refresh-worker-status', this.refreshWorkerStatus);
            
            // Form validation
            $(document).on('change', 'input[type="url"]', this.validateUrl);
            $(document).on('change', 'input[type="number"]', this.validateNumber);
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
         */
        testWorkerConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var workerName = $button.data('worker');
            var workerUrl = $button.closest('.worker-card').find('input[name*="[url]"]').val();
            
            if (!workerUrl) {
                AIFaqGenAdmin.showNotice('error', aiFaqGen.strings.error);
                return;
            }
            
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
                        AIFaqGenAdmin.showNotice('success', 'Worker connection successful!');
                    } else {
                        AIFaqGenAdmin.showNotice('error', response.data || aiFaqGen.strings.error);
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
                        $button.closest('.worker-card').find('.usage-current').text('0');
                        $button.closest('.worker-card').find('.usage-fill').css('width', '0%');
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
            var $submitButton = $form.find('input[type="submit"]');
            
            $submitButton.prop('disabled', true).val(aiFaqGen.strings.loading);
            
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
                error: function() {
                    AIFaqGenAdmin.showNotice('error', aiFaqGen.strings.error);
                },
                complete: function() {
                    $submitButton.prop('disabled', false).val('Save Changes');
                }
            });
        },

        /**
         * Toggle shortcode help section.
         */
        toggleShortcodeHelp: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $helpSection = $('#shortcode-help');
            
            if ($helpSection.is(':visible')) {
                $helpSection.slideUp();
                $button.text('Show Examples');
            } else {
                $helpSection.slideDown();
                $button.text('Hide Examples');
            }
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
                AIFaqGenAdmin.showNotice('success', aiFaqGen.strings.copied);
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
                var $workerCard = $('.worker-card[data-worker="' + workerName + '"]');
                
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
            var $target = $('.ai-faq-gen-header').length > 0 ? $('.ai-faq-gen-header') : $('.ai-faq-gen-content');
            $target.after($notice);
            
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
        },

        /**
         * Debounce function for performance.
         */
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
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