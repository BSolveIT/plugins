/**
 * 365i Queue Optimizer Admin JavaScript
 *
 * @package QueueOptimizer
 */

(function($) {
    'use strict';

    /**
     * Initialize admin functionality
     */
    function initAdmin() {
        // Add form validation for numeric inputs
        validateNumericInputs();
        
        // Add tooltips or help text interactions if needed
        initHelpText();
    }

    /**
     * Validate numeric inputs on the settings form
     */
    function validateNumericInputs() {
        $('#queue_optimizer_time_limit').on('input', function() {
            var value = parseInt($(this).val());
            var min = parseInt($(this).attr('min'));
            var max = parseInt($(this).attr('max'));
            
            if (value < min || value > max) {
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });

        $('#queue_optimizer_concurrent_batches').on('input', function() {
            var value = parseInt($(this).val());
            var min = parseInt($(this).attr('min'));
            var max = parseInt($(this).attr('max'));
            
            if (value < min || value > max) {
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
    }

    /**
     * Initialize help text interactions
     */
    function initHelpText() {
        // Add any interactive help text functionality here
        $('.description').each(function() {
            $(this).attr('title', $(this).text());
        });
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initAdmin();
    });

})(jQuery);