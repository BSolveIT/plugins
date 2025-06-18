/**
 * Rate Limiting Admin JavaScript
 * 
 * Handles AJAX interactions for IP management and analytics pages
 * 
 * @package    AI_FAQ_Generator
 * @subpackage Assets/JS
 * @since      2.1.2
 */

(function($) {
    'use strict';

    // Wait for document ready
    $(document).ready(function() {
        // Check if aiFAQRateLimit object exists before initializing
        if (typeof aiFAQRateLimit === 'undefined') {
            console.error('aiFAQRateLimit object not found. Admin scripts may not be properly localized.');
            return;
        }
        
        initIPManagement();
        initAnalytics();
    });

    /**
     * Initialize IP Management functionality
     */
    function initIPManagement() {
        // Only initialize if we're on the IP management page
        if ($('#ip_address_input').length === 0) {
            return;
        }

        // Add to Whitelist button
        $('#add-to-whitelist').on('click', function(e) {
            e.preventDefault();
            handleIPAction('add_whitelist');
        });

        // Add to Blacklist button
        $('#add-to-blacklist').on('click', function(e) {
            e.preventDefault();
            handleIPAction('add_blacklist');
        });

        // Remove IP buttons (using event delegation for dynamic content)
        $(document).on('click', '.remove-ip', function(e) {
            e.preventDefault();
            var ip = $(this).data('ip');
            var listType = $(this).data('list');
            var action = 'remove_' + listType;
            
            if (confirm('Are you sure you want to remove this IP address?')) {
                handleIPAction(action, ip, '');
            }
        });

        // Add to blacklist from analytics (for violators)
        $(document).on('click', '.add-to-blacklist', function(e) {
            e.preventDefault();
            var ip = $(this).data('ip');
            
            if (confirm('Are you sure you want to blacklist this IP address?')) {
                handleIPAction('add_blacklist', ip, 'Added from usage analytics - rate limit violator');
            }
        });

        // Real-time IP validation for form input
        $('#ip_address_input').on('input', function() {
            var ip = $(this).val().trim();
            var $button = $('#add-to-whitelist, #add-to-blacklist');
            
            if (ip && !isValidIP(ip)) {
                $(this).addClass('invalid');
                $button.prop('disabled', true);
            } else {
                $(this).removeClass('invalid');
                $button.prop('disabled', false);
            }
        });
    }

    /**
     * Handle IP management actions
     */
    function handleIPAction(action, ip, reason) {
        var $ipInput = $('#ip_address_input');
        var $reasonInput = $('#ip_reason_input');
        
        // Use provided values or get from form
        var ipAddress = ip || $ipInput.val().trim();
        var ipReason = reason !== undefined ? reason : $reasonInput.val().trim();
        
        // Validate IP address
        if (!ipAddress) {
            showMessage('error', 'Please enter an IP address.');
            return;
        }
        
        if (!isValidIP(ipAddress)) {
            showMessage('error', 'Please enter a valid IPv4 or IPv6 address.');
            return;
        }

        // Show loading state
        var $button = action.includes('add_whitelist') ? $('#add-to-whitelist') : 
                     action.includes('add_blacklist') ? $('#add-to-blacklist') : 
                     $('.remove-ip[data-ip="' + ipAddress + '"]');
        
        var originalText = $button.text();
        $button.prop('disabled', true).text('Processing...');

        // Make AJAX request
        $.ajax({
            url: aiFAQRateLimit.ajax_url,
            type: 'POST',
            data: {
                action: 'ai_faq_rl_manage_ip',
                nonce: aiFAQRateLimit.nonce,
                ip_action: action,
                ip_address: ipAddress,
                reason: ipReason
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                    
                    // Clear form if adding new IP
                    if (action.includes('add_')) {
                        $ipInput.val('');
                        $reasonInput.val('');
                    }
                    
                    // Reload page to update lists
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage('error', response.data.message || 'An error occurred.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showMessage('error', 'Network error occurred. Please try again.');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Initialize Analytics functionality
     */
    function initAnalytics() {
        // Only initialize if we're on the analytics page
        if ($('#analytics-filters').length === 0) {
            return;
        }

        // Refresh Analytics button
        $('#refresh-analytics').on('click', function(e) {
            e.preventDefault();
            refreshAnalytics();
        });

        // Export Analytics button
        $('#export-analytics').on('click', function(e) {
            e.preventDefault();
            exportAnalytics();
        });

        // Analytics filter changes - disable automatic refresh to prevent security errors
        $('#analytics-filters select').on('change', function() {
            showMessage('info', 'Analytics filters updated. Click "Refresh Analytics" to reload data.');
        });
    }

    /**
     * Refresh analytics data
     */
    function refreshAnalytics() {
        // Check if required objects exist
        if (typeof aiFAQRateLimit === 'undefined') {
            console.error('aiFAQRateLimit object not found');
            showMessage('error', 'Configuration error. Please refresh the page.');
            return;
        }

        var $button = $('#refresh-analytics');
        var originalText = $button.text();
        $button.prop('disabled', true).text('Loading...');

        var timeframe = $('#analytics_timeframe').val() || 'daily';
        var worker = $('#analytics_worker').val() || 'all';


        $.ajax({
            url: aiFAQRateLimit.ajax_url,
            type: 'POST',
            data: {
                action: 'ai_faq_rl_get_analytics',
                nonce: aiFAQRateLimit.nonce,
                timeframe: timeframe,
                worker: worker
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', 'Analytics data refreshed successfully.');
                    
                    // Update analytics display with new data
                    updateAnalyticsDisplay(response.data);
                } else {
                    showMessage('error', response.data.message || response.data || 'Failed to refresh analytics.');
                }
            },
            error: function(xhr, status, error) {
                showMessage('error', 'Failed to refresh analytics data.');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Export analytics data
     */
    function exportAnalytics() {
        var timeframe = $('#analytics_timeframe').val() || 'daily';
        var worker = $('#analytics_worker').val() || 'all';
        
        showMessage('info', 'Preparing analytics export...');
        
        // Create a form to download the export
        var exportUrl = aiFAQRateLimit.ajax_url +
                       '?action=ai_faq_rl_export_analytics' +
                       '&nonce=' + aiFAQRateLimit.nonce +
                       '&timeframe=' + timeframe +
                       '&worker=' + worker;
        
        // Open in new window for download
        window.open(exportUrl, '_blank');
    }

    /**
     * Update analytics display with new data
     */
    function updateAnalyticsDisplay(data) {
        // Update metric cards
        $('.analytics-card .metric-value').each(function() {
            var $card = $(this).closest('.analytics-card');
            var cardTitle = $card.find('h3').text().toLowerCase();
            
            if (cardTitle.includes('total requests')) {
                $(this).text(data.total_requests || 0);
            } else if (cardTitle.includes('blocked requests')) {
                $(this).text(data.blocked_requests || 0);
            } else if (cardTitle.includes('violations')) {
                $(this).text(data.violations || 0);
            } else if (cardTitle.includes('unique ips')) {
                $(this).text(data.unique_ips || 0);
            }
        });

        // Update last updated time
        $('.status-indicator').each(function() {
            if ($(this).siblings('h3').text().includes('Last Update')) {
                $(this).find('span:not(.status-blue)').text(data.last_updated || 'Just now');
            }
        });
    }

    /**
     * Validate IP address format
     */
    function isValidIP(ip) {
        // IPv4 pattern
        var ipv4Pattern = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
        
        // IPv6 pattern (simplified)
        var ipv6Pattern = /^(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$/;
        
        return ipv4Pattern.test(ip) || ipv6Pattern.test(ip);
    }

    /**
     * Show notification message
     */
    function showMessage(type, message) {
        // Remove existing messages
        $('.ai-faq-message').remove();
        
        var messageClass = 'notice notice-' + (type === 'success' ? 'success' : 
                          type === 'error' ? 'error' : 'info');
        
        var $message = $('<div class="ai-faq-message ' + messageClass + ' is-dismissible">' +
                        '<p>' + message + '</p>' +
                        '<button type="button" class="notice-dismiss">' +
                        '<span class="screen-reader-text">Dismiss this notice.</span>' +
                        '</button>' +
                        '</div>');
        
        // Insert after page title
        $('.wrap h1').after($message);
        
        // Handle dismiss button
        $message.find('.notice-dismiss').on('click', function() {
            $message.fadeOut(function() {
                $(this).remove();
            });
        });
        
        // Auto-dismiss success messages
        if (type === 'success') {
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }

})(jQuery);