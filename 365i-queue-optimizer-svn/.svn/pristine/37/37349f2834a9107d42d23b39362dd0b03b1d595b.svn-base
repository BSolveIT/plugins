/**
 * 365i Queue Optimizer Admin JavaScript
 *
 * @package QueueOptimizer
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Initialize admin functionality
     */
    function initAdmin() {
        validateNumericInputs();
        initServerTypeChange();
        initApplyRecommended();
        initRunQueue();
        initHelpText();
        initSaveNotification();
    }

    /**
     * Validate numeric inputs on the settings form
     */
    function validateNumericInputs() {
        var numericFields = [
            '#queue_optimizer_time_limit',
            '#queue_optimizer_concurrent_batches',
            '#queue_optimizer_batch_size',
            '#queue_optimizer_retention_days'
        ];

        numericFields.forEach(function(selector) {
            $(selector).on('input', function() {
                var value = parseInt($(this).val());
                var min = parseInt($(this).attr('min'));
                var max = parseInt($(this).attr('max'));

                if (isNaN(value) || value < min || value > max) {
                    $(this).addClass('error');
                } else {
                    $(this).removeClass('error');
                }
            });
        });
    }

    /**
     * Apply recommended settings to form fields
     *
     * @param {Object} rec Recommended settings object
     * @param {boolean} showNotice Whether to show the notification
     */
    function applyRecommendedSettings(rec, showNotice) {
        if (!rec) {
            return;
        }

        // Apply recommended values to form fields
        $('#queue_optimizer_time_limit').val(rec.time_limit).removeClass('error');
        $('#queue_optimizer_concurrent_batches').val(rec.concurrent_batches).removeClass('error');
        $('#queue_optimizer_batch_size').val(rec.batch_size).removeClass('error');
        $('#queue_optimizer_retention_days').val(rec.retention_days).removeClass('error');

        // Highlight changed fields briefly
        var fields = [
            '#queue_optimizer_time_limit',
            '#queue_optimizer_concurrent_batches',
            '#queue_optimizer_batch_size',
            '#queue_optimizer_retention_days'
        ];

        fields.forEach(function(selector) {
            $(selector).addClass('qo-highlight');
            setTimeout(function() {
                $(selector).removeClass('qo-highlight');
            }, 1500);
        });

        // Show notice if requested
        if (showNotice) {
            var message = queueOptimizerAdmin.i18n.recommendedApplied ||
                'Recommended settings applied. Click "Save Changes" to save.';

            var $notice = $('<div class="notice notice-info is-dismissible qo-applied-notice"><p>' +
                message + '</p></div>');

            $('.qo-applied-notice').remove();
            $('form').before($notice);

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }

    /**
     * Handle server type dropdown change - auto-populate recommended settings
     */
    function initServerTypeChange() {
        $('#queue_optimizer_server_type_override').on('change', function() {
            if (typeof queueOptimizerAdmin === 'undefined' || !queueOptimizerAdmin.allRecommendations) {
                return;
            }

            var selectedType = $(this).val();
            var rec;

            if (selectedType && queueOptimizerAdmin.allRecommendations[selectedType]) {
                // User selected a specific server type
                rec = queueOptimizerAdmin.allRecommendations[selectedType];
            } else {
                // Auto-detect selected - use the current recommended settings
                rec = queueOptimizerAdmin.recommended;
            }

            applyRecommendedSettings(rec, true);
        });
    }

    /**
     * Handle "Apply Recommended Settings" button
     */
    function initApplyRecommended() {
        $('#qo-apply-recommended').on('click', function(e) {
            e.preventDefault();

            if (typeof queueOptimizerAdmin === 'undefined') {
                return;
            }

            var selectedType = $('#queue_optimizer_server_type_override').val();
            var rec;

            if (selectedType && queueOptimizerAdmin.allRecommendations && queueOptimizerAdmin.allRecommendations[selectedType]) {
                rec = queueOptimizerAdmin.allRecommendations[selectedType];
            } else {
                rec = queueOptimizerAdmin.recommended;
            }

            applyRecommendedSettings(rec, true);
        });
    }

    /**
     * Handle "Run Queue Now" button
     */
    function initRunQueue() {
        var $button = $('#qo-run-queue');
        var $result = $('#qo-queue-result');
        var $pendingCount = $('#qo-pending-count');
        var isRunning = false;

        $button.on('click', function(e) {
            e.preventDefault();

            if (isRunning || typeof queueOptimizerAdmin === 'undefined') {
                return;
            }

            isRunning = true;
            var originalText = $button.text();

            $button.prop('disabled', true).text(queueOptimizerAdmin.i18n.running);
            $result.removeClass('qo-result-success qo-result-error').text('');

            $.ajax({
                url: queueOptimizerAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'qo_run_queue',
                    nonce: queueOptimizerAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.addClass('qo-result-success').text(response.data.message);
                        $pendingCount.text(response.data.remaining);

                        // Update button state based on remaining actions
                        if (response.data.remaining > 0) {
                            $button.prop('disabled', false);
                        }
                    } else {
                        $result.addClass('qo-result-error').text(response.data.message || queueOptimizerAdmin.i18n.error);
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    $result.addClass('qo-result-error').text(queueOptimizerAdmin.i18n.error);
                    $button.prop('disabled', false);
                },
                complete: function() {
                    isRunning = false;
                    $button.text(originalText);
                }
            });
        });
    }

    /**
     * Initialize help text interactions
     */
    function initHelpText() {
        $('.description').each(function() {
            $(this).attr('title', $(this).text().trim());
        });
    }

    /**
     * Show save notification when settings are saved
     */
    function initSaveNotification() {
        // Check if settings were just saved (check both possible parameter names)
        var urlParams = new URLSearchParams(window.location.search);
        var settingsUpdated = urlParams.get('settings-updated') === 'true';

        // Also check for the settings saved notice as a fallback
        var hasWpNotice = $('.qo-settings-saved, .notice-success, .updated').length > 0;

        if (settingsUpdated || hasWpNotice) {
            // Hide the native WordPress notice
            $('.qo-settings-saved, .notice-success, .updated').hide();

            // Create and show our custom notification
            var $notice = $('<div class="qo-save-notice">' +
                '<span class="dashicons dashicons-yes-alt"></span> ' +
                'Settings saved successfully!</div>');

            $('#wpbody').append($notice);

            // Trigger animation after a brief delay
            setTimeout(function() {
                $notice.addClass('qo-notice-visible');
            }, 100);

            // Auto-dismiss after 3 seconds
            setTimeout(function() {
                $notice.removeClass('qo-notice-visible');
                setTimeout(function() {
                    $notice.remove();
                }, 300);
            }, 3000);

            // Remove the query parameter from URL without reload
            if (settingsUpdated) {
                var newUrl = window.location.pathname + '?page=queue-optimizer';
                window.history.replaceState({}, '', newUrl);
            }
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initAdmin();
    });

})(jQuery);
