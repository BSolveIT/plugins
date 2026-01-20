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
        initHelpPopovers();
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
     * Initialize help popovers
     */
    function initHelpPopovers() {
        var $activePopover = null;

        // Close popover function
        function closePopover() {
            if ($activePopover) {
                $activePopover.removeClass('qo-popover-visible');
                $activePopover.prev('.qo-help-trigger').attr('aria-expanded', 'false');
                setTimeout(function() {
                    $activePopover.remove();
                    $activePopover = null;
                }, 200);
            }
        }

        // Handle help trigger clicks
        $(document).on('click', '.qo-help-trigger', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $trigger = $(this);
            var helpId = $trigger.data('help');

            // If clicking same trigger, close it
            if ($activePopover && $trigger.attr('aria-expanded') === 'true') {
                closePopover();
                return;
            }

            // Close any existing popover
            closePopover();

            // Get help content
            var helpContent = getHelpContent(helpId);
            if (!helpContent) {
                return;
            }

            // Create popover
            var $popover = $(
                '<div class="qo-help-popover" role="dialog" aria-modal="true">' +
                    '<div class="qo-popover-header">' +
                        '<h4 class="qo-popover-title">' + helpContent.title + '</h4>' +
                        '<button type="button" class="qo-popover-close" aria-label="Close">&times;</button>' +
                    '</div>' +
                    '<div class="qo-popover-content">' + helpContent.content + '</div>' +
                '</div>'
            );

            // Position popover
            $trigger.after($popover);
            $trigger.attr('aria-expanded', 'true');

            // Calculate position
            var triggerOffset = $trigger.offset();
            var triggerHeight = $trigger.outerHeight();

            $popover.css({
                position: 'absolute',
                top: triggerHeight + 8,
                left: 0
            });

            // Show with animation
            setTimeout(function() {
                $popover.addClass('qo-popover-visible');
            }, 10);

            $activePopover = $popover;

            // Focus close button for accessibility
            $popover.find('.qo-popover-close').focus();
        });

        // Close button click
        $(document).on('click', '.qo-popover-close', function(e) {
            e.preventDefault();
            closePopover();
        });

        // Close on outside click
        $(document).on('click', function(e) {
            if ($activePopover && !$(e.target).closest('.qo-help-popover, .qo-help-trigger').length) {
                closePopover();
            }
        });

        // Close on Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $activePopover) {
                closePopover();
            }
        });
    }

    /**
     * Get help content for a specific field
     */
    function getHelpContent(helpId) {
        var helpData = {
            'time_limit': {
                title: 'Time Limit',
                content: '<p>Controls how long ActionScheduler is allowed to process tasks in a single run before stopping.</p>' +
                    '<p><strong>Higher values</strong> = more tasks processed per run, but longer server load.</p>' +
                    '<p><strong>Lower values</strong> = shorter processing bursts, better for shared hosting.</p>' +
                    '<div class="qo-popover-recommendations">' +
                        '<strong>Recommended by Server Type</strong>' +
                        '<ul>' +
                            '<li><span>Shared Hosting</span><span>30 seconds</span></li>' +
                            '<li><span>VPS / Managed</span><span>45 seconds</span></li>' +
                            '<li><span>Dedicated</span><span>60 seconds</span></li>' +
                        '</ul>' +
                    '</div>'
            },
            'concurrent_batches': {
                title: 'Concurrent Batches',
                content: '<p>The number of simultaneous queue runners that can process actions at the same time.</p>' +
                    '<p><strong>Higher values</strong> = faster processing but more server resources used.</p>' +
                    '<p><strong>Lower values</strong> = slower processing but gentler on the server.</p>' +
                    '<div class="qo-popover-recommendations">' +
                        '<strong>Recommended by Server Type</strong>' +
                        '<ul>' +
                            '<li><span>Shared Hosting</span><span>1 batch</span></li>' +
                            '<li><span>VPS / Managed</span><span>2 batches</span></li>' +
                            '<li><span>Dedicated</span><span>4 batches</span></li>' +
                        '</ul>' +
                    '</div>' +
                    '<div class="qo-popover-tip">Start low and increase gradually while monitoring server performance.</div>'
            },
            'batch_size': {
                title: 'Batch Size',
                content: '<p>The maximum number of actions that can be claimed and processed in each batch.</p>' +
                    '<p><strong>Higher values</strong> = more actions processed per batch, faster overall.</p>' +
                    '<p><strong>Lower values</strong> = smaller batches, reduces memory usage and timeout risk.</p>' +
                    '<div class="qo-popover-recommendations">' +
                        '<strong>Recommended by Server Type</strong>' +
                        '<ul>' +
                            '<li><span>Shared Hosting</span><span>25 actions</span></li>' +
                            '<li><span>VPS / Managed</span><span>35 actions</span></li>' +
                            '<li><span>Dedicated</span><span>50 actions</span></li>' +
                        '</ul>' +
                    '</div>'
            },
            'retention_days': {
                title: 'Data Retention',
                content: '<p>How many days to keep completed action logs in the database before they are automatically deleted.</p>' +
                    '<p><strong>Higher values</strong> = longer history for debugging, but larger database.</p>' +
                    '<p><strong>Lower values</strong> = smaller database, faster queries.</p>' +
                    '<div class="qo-popover-recommendations">' +
                        '<strong>Recommended by Server Type</strong>' +
                        '<ul>' +
                            '<li><span>Shared Hosting</span><span>3 days</span></li>' +
                            '<li><span>VPS / Managed</span><span>5 days</span></li>' +
                            '<li><span>Dedicated</span><span>7 days</span></li>' +
                        '</ul>' +
                    '</div>' +
                    '<div class="qo-popover-tip">Shorter retention keeps your database lean and improves performance.</div>'
            },
            'image_engine': {
                title: 'Image Processing Engine',
                content: '<p>Choose which PHP image library WordPress should prioritize for image processing.</p>' +
                    '<p><strong>ImageMagick</strong> (Recommended)</p>' +
                    '<ul style="margin: 8px 0 8px 20px; font-size: 12px;">' +
                        '<li>Better quality for resizing and compression</li>' +
                        '<li>More memory efficient for large images</li>' +
                        '<li>Better color profile handling</li>' +
                        '<li>Supports more image formats</li>' +
                    '</ul>' +
                    '<p><strong>GD Library</strong></p>' +
                    '<ul style="margin: 8px 0 8px 20px; font-size: 12px;">' +
                        '<li>More widely available on shared hosting</li>' +
                        '<li>Simpler, fewer dependencies</li>' +
                        '<li>May be faster for simple operations</li>' +
                    '</ul>' +
                    '<div class="qo-popover-tip">If ImageMagick is available, use it. Only switch to GD if you experience issues.</div>'
            },
            'server_type': {
                title: 'Server Type',
                content: '<p>Select your hosting environment to get appropriate recommended settings.</p>' +
                    '<p><strong>Auto-detect</strong> analyzes your PHP memory limit and execution time to guess your server type.</p>' +
                    '<p><strong>Shared Hosting</strong> - Budget hosting with limited resources (e.g., Bluehost, SiteGround shared plans)</p>' +
                    '<p><strong>VPS / Managed</strong> - Virtual private server or managed WordPress hosting (e.g., Cloudways, Kinsta)</p>' +
                    '<p><strong>Dedicated</strong> - High-performance dedicated server or enterprise hosting</p>' +
                    '<div class="qo-popover-tip">If auto-detect gets it wrong, manually select your actual hosting type for better recommendations.</div>'
            }
        };

        return helpData[helpId] || null;
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
