/**
 * Queue Optimizer Admin JavaScript
 * Handles AJAX interactions for the admin interface.
 */

(function($) {
    'use strict';

    // DOM ready handler
    $(document).ready(function() {
        initQueueOptimizer();
    });

    /**
     * Initialize the Queue Optimizer admin functionality.
     */
    function initQueueOptimizer() {
        // Bind event handlers
        $('#run-queue-now').on('click', handleRunQueueNow);
        $('#clear-logs').on('click', handleClearLogs);
        $('#clear-action-scheduler-logs').on('click', handleClearActionSchedulerLogs);
        $('#view-logs').on('click', handleViewLogs);
        $('#refresh-logs').on('click', handleRefreshLogs);
        $('#close-logs').on('click', handleCloseLogs);

        // Auto-refresh status every 30 seconds
        setInterval(refreshQueueStatus, 30000);
    }

    /**
     * Handle "Run Now" button click.
     */
    function handleRunQueueNow() {
        var $button = $('#run-queue-now');
        var $container = $('.queue-optimizer-dashboard-panel');

        // Prevent multiple clicks
        if ($button.hasClass('queue-optimizer-processing')) {
            return;
        }

        // Update UI to show processing state
        $button.addClass('queue-optimizer-processing');
        $button.prop('disabled', true);
        $button.text(queueOptimizerAjax.strings.processing);
        $container.addClass('queue-optimizer-loading');

        // Hide any previous messages
        hideMessages();

        // Make AJAX request
        $.ajax({
            url: queueOptimizerAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'queue_optimizer_run_now',
                nonce: queueOptimizerAjax.nonce
            },
            timeout: 60000, // 60 second timeout
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                    
                    // Update status counts if provided
                    if (response.data.status) {
                        updateStatusCounts(response.data.status);
                    }
                } else {
                    showMessage('error', response.data || queueOptimizerAjax.strings.error);
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = queueOptimizerAjax.strings.error;
                
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. The queue may still be processing.';
                } else if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                
                showMessage('error', errorMessage);
                console.error('Queue Optimizer AJAX error:', error);
            },
            complete: function() {
                // Reset UI state
                $button.removeClass('queue-optimizer-processing');
                $button.prop('disabled', false);
                $button.text($button.data('original-text') || 'Run Now');
                $container.removeClass('queue-optimizer-loading');
            }
        });
    }

    /**
     * Handle "Clear Logs" button click.
     */
    function handleClearLogs() {
        var $button = $('#clear-logs');

        // Confirm action
        if (!confirm('Are you sure you want to clear all plugin logs? This action cannot be undone.')) {
            return;
        }

        // Prevent multiple clicks
        if ($button.hasClass('queue-optimizer-processing')) {
            return;
        }

        // Update UI to show processing state
        $button.addClass('queue-optimizer-processing');
        $button.prop('disabled', true);
        var originalText = $button.text();
        $button.text(queueOptimizerAjax.strings.processing);

        // Hide any previous messages
        hideMessages();

        // Make AJAX request
        $.ajax({
            url: queueOptimizerAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'queue_optimizer_clear_logs',
                nonce: queueOptimizerAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                } else {
                    showMessage('error', response.data || queueOptimizerAjax.strings.error);
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = queueOptimizerAjax.strings.error;
                
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                
                showMessage('error', errorMessage);
                console.error('Queue Optimizer AJAX error:', error);
            },
            complete: function() {
                // Reset UI state
                $button.removeClass('queue-optimizer-processing');
                $button.prop('disabled', false);
                $button.text(originalText);
            }
        });
    }

    /**
     * Handle "Clear Action Scheduler Logs" button click.
     */
    function handleClearActionSchedulerLogs() {
        var $button = $('#clear-action-scheduler-logs');

        // Confirm action
        if (!confirm('Are you sure you want to clear all completed and failed Action Scheduler entries? This action cannot be undone.')) {
            return;
        }

        // Prevent multiple clicks
        if ($button.hasClass('queue-optimizer-processing')) {
            return;
        }

        // Update UI to show processing state
        $button.addClass('queue-optimizer-processing');
        $button.prop('disabled', true);
        var originalText = $button.text();
        $button.text(queueOptimizerAjax.strings.processing);

        // Hide any previous messages
        hideMessages();

        // Make AJAX request
        $.ajax({
            url: queueOptimizerAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'queue_optimizer_clear_action_scheduler_logs',
                nonce: queueOptimizerAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                    // Refresh status after clearing
                    setTimeout(refreshQueueStatus, 1000);
                } else {
                    showMessage('error', response.data || queueOptimizerAjax.strings.error);
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = queueOptimizerAjax.strings.error;
                
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                
                showMessage('error', errorMessage);
                console.error('Queue Optimizer AJAX error:', error);
            },
            complete: function() {
                // Reset UI state
                $button.removeClass('queue-optimizer-processing');
                $button.prop('disabled', false);
                $button.text(originalText);
            }
        });
    }

    /**
     * Refresh queue status counts.
     */
    function refreshQueueStatus() {
        // Only refresh if we're on the queue optimizer page
        if (!$('#pending-count').length) {
            return;
        }

        $.ajax({
            url: queueOptimizerAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'queue_optimizer_get_status',
                nonce: queueOptimizerAjax.nonce
            },
            success: function(response) {
                if (response.success && response.data.status) {
                    updateStatusCounts(response.data.status);
                }
            },
            error: function() {
                // Silently fail for background refresh
                console.log('Queue Optimizer: Status refresh failed');
            }
        });
    }

    /**
     * Update status count displays.
     *
     * @param {Object} status Status object with pending, processing, completed, failed counts
     */
    function updateStatusCounts(status) {
        if (typeof status !== 'object') {
            return;
        }

        // Update counts with animation
        if (typeof status.pending !== 'undefined') {
            animateCountChange('#pending-count', status.pending);
        }
        
        if (typeof status.processing !== 'undefined') {
            animateCountChange('#processing-count', status.processing);
        }
        
        if (typeof status.completed !== 'undefined') {
            animateCountChange('#completed-count', status.completed);
        }
        
        if (typeof status.failed !== 'undefined') {
            animateCountChange('#failed-count', status.failed);
        }

        // Update last run time if provided
        if (status.last_run && $('.queue-optimizer-last-run p').length) {
            var lastRunText = 'Last Run: ';
            if (status.last_run > 0) {
                var date = new Date(status.last_run * 1000);
                lastRunText += date.toLocaleString();
            } else {
                lastRunText += 'Never';
            }
            $('.queue-optimizer-last-run p').html('<strong>' + lastRunText.split(':')[0] + ':</strong> ' + lastRunText.split(':').slice(1).join(':'));
        }
    }

    /**
     * Animate count change with a brief highlight effect.
     *
     * @param {string} selector Element selector
     * @param {number} newValue New count value
     */
    function animateCountChange(selector, newValue) {
        var $element = $(selector);
        var currentValue = parseInt($element.text().replace(/,/g, ''), 10) || 0;
        
        if (currentValue !== newValue) {
            $element.addClass('count-updating');
            
            // Update the number with formatting
            $element.text(numberFormat(newValue));
            
            // Remove highlight after animation
            setTimeout(function() {
                $element.removeClass('count-updating');
            }, 500);
        }
    }

    /**
     * Format number with thousands separator.
     *
     * @param {number} num Number to format
     * @return {string} Formatted number string
     */
    function numberFormat(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Show a message to the user.
     *
     * @param {string} type Message type: 'success', 'error', 'info'
     * @param {string} message Message text
     */
    function showMessage(type, message) {
        var $messagesContainer = $('#queue-optimizer-messages');
        
        $messagesContainer
            .removeClass('success error info')
            .addClass(type)
            .html('<p>' + escapeHtml(message) + '</p>')
            .slideDown(200);

        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                hideMessages();
            }, 5000);
        }
    }

    /**
     * Hide messages.
     */
    function hideMessages() {
        $('#queue-optimizer-messages').slideUp(200);
    }

    /**
     * Handle "View Logs" button click.
     */
    function handleViewLogs() {
        var $button = $('#view-logs');
        var $logsContainer = $('#queue-optimizer-logs');

        // Show logs container
        $logsContainer.slideDown(200);

        // Load logs
        loadLogs();
    }

    /**
     * Handle "Refresh Logs" button click.
     */
    function handleRefreshLogs() {
        loadLogs();
    }

    /**
     * Handle "Close Logs" button click.
     */
    function handleCloseLogs() {
        var $logsContainer = $('#queue-optimizer-logs');
        $logsContainer.slideUp(200);
    }

    /**
     * Load and display logs.
     */
    function loadLogs() {
        var $logDisplay = $('#log-display');
        var $refreshButton = $('#refresh-logs');

        // Show loading state
        $logDisplay.text('Loading logs...');
        $refreshButton.prop('disabled', true);

        // Make AJAX request
        $.ajax({
            url: queueOptimizerAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'queue_optimizer_get_logs',
                nonce: queueOptimizerAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $logDisplay.text(response.data.logs || 'No logs available.');
                } else {
                    $logDisplay.text('Error loading logs: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Failed to load logs.';
                
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage += ' ' + xhr.responseJSON.data;
                }
                
                $logDisplay.text(errorMessage);
                console.error('Queue Optimizer logs error:', error);
            },
            complete: function() {
                // Reset button state
                $refreshButton.prop('disabled', false);
            }
        });
    }

    /**
     * Escape HTML to prevent XSS.
     *
     * @param {string} text Text to escape
     * @return {string} Escaped text
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }

    // Store original button text on page load
    $(document).ready(function() {
        $('#run-queue-now').data('original-text', $('#run-queue-now').text());
    });

    // Add CSS for count updating animation
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .count-updating {
                background-color: #ffeb3b !important;
                transition: background-color 0.5s ease;
            }
            .queue-optimizer-processing {
                position: relative;
                color: transparent !important;
            }
            .queue-optimizer-processing::after {
                content: "";
                position: absolute;
                top: 50%;
                left: 50%;
                width: 16px;
                height: 16px;
                margin: -8px 0 0 -8px;
                border: 2px solid transparent;
                border-top: 2px solid currentColor;
                border-radius: 50%;
                animation: queue-optimizer-spin 1s linear infinite;
                opacity: 0.7;
            }
            @keyframes queue-optimizer-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `)
        .appendTo('head');

})(jQuery);