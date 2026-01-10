/**
 * Queue Optimizer Dashboard Widget JavaScript
 *
 * @package QueueOptimizer
 * @since 1.4.0
 */

(function($) {
    'use strict';

    var isRunning = false;

    /**
     * Initialize the dashboard widget.
     */
    function init() {
        $('#qo-widget-run').on('click', runQueue);
    }

    /**
     * Run the queue via AJAX.
     */
    function runQueue(e) {
        e.preventDefault();

        if (isRunning || typeof qoDashboard === 'undefined') {
            return;
        }

        var $button = $('#qo-widget-run');
        var $result = $('#qo-widget-result');
        var $pending = $('#qo-widget-pending');
        var originalHtml = $button.html();

        isRunning = true;
        $button.prop('disabled', true).html(
            '<span class="dashicons dashicons-update-alt spin"></span> ' +
            qoDashboard.i18n.processing
        );
        $result.removeClass('qo-success qo-error').empty();

        $.ajax({
            url: qoDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'qo_run_queue',
                nonce: qoDashboard.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.addClass('qo-success').text(response.data.message);
                    $pending.text(response.data.remaining);

                    // Update button state.
                    if (response.data.remaining > 0) {
                        $button.prop('disabled', false);
                    }

                    // Update health indicator if queue cleared.
                    if (response.data.remaining === 0) {
                        updateHealthIndicator('healthy');
                    } else if (response.data.remaining <= 50) {
                        updateHealthIndicator('healthy');
                    }
                } else {
                    $result.addClass('qo-error').text(response.data.message || 'Error');
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                $result.addClass('qo-error').text('Connection error. Please try again.');
                $button.prop('disabled', false);
            },
            complete: function() {
                isRunning = false;
                $button.html(originalHtml);
            }
        });
    }

    /**
     * Update the health indicator.
     *
     * @param {string} health The health status: healthy, warning, critical.
     */
    function updateHealthIndicator(health) {
        var $widget = $('.qo-widget');
        var labels = {
            healthy: 'Healthy',
            warning: 'Backlog',
            critical: 'Needs Attention'
        };

        $widget.attr('data-health', health);
        $widget.find('.qo-health-label').text(labels[health] || health);
    }

    // Initialize on document ready.
    $(document).ready(init);

    // Add spinning animation for loading state.
    $('<style>')
        .text('.dashicons.spin { animation: qo-spin 1s linear infinite; } @keyframes qo-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }')
        .appendTo('head');

})(jQuery);
