/**
 * Worker test results JavaScript for 365i AI FAQ Generator admin interface.
 *
 * Provides visual feedback for worker connection tests by displaying
 * success/error states and detailed information on worker cards.
 *
 * Note: When testing connections to worker URLs, browsers will automatically
 * request favicon.ico from the domain root. This causes 404 errors in Cloudflare
 * logs that can be safely ignored - they don't indicate actual connection problems.
 *
 * @package AI_FAQ_Generator
 * @subpackage Assets
 * @since 2.0.0
 */

(function($) {
    'use strict';

    // Auto-hide timeout (in milliseconds)
    var autoHideDelay = 6000;
    var activeTimers = {};

    // Override the original test worker connection event
    $(document).on('click', '.test-worker-connection', function(e) {
        // Don't prevent default - let the original handler run too
        
        var $button = $(this);
        var $workerCard = $button.closest('.worker-card, .worker-config-card');
        var workerName = $button.data('worker');
        
        // Clear any existing timers for this worker
        if (activeTimers[workerName]) {
            clearTimeout(activeTimers[workerName]);
            delete activeTimers[workerName];
        }
        
        // Remove any existing result classes from the card
        $workerCard.removeClass('tested-success tested-error');
        
        // Create a test results container if it doesn't exist
        var $testResults = $workerCard.find('.test-results');
        if ($testResults.length === 0) {
            $testResults = $('<div class="test-results"></div>');
            $workerCard.find('.worker-actions').before($testResults);
        } else {
            // Hide any existing results
            $testResults.removeClass('fade-in').addClass('fade-out');
            setTimeout(function() {
                $testResults.removeClass('fade-out').hide();
            }, 500);
        }
        
        // Show loading indicator
        var loadingHtml = '<div class="test-result-loading">';
        loadingHtml += '<span class="dashicons dashicons-update-alt" style="animation: spin 2s linear infinite;"></span> ';
        loadingHtml += '<span class="test-status">Testing connection...</span>';
        loadingHtml += '</div>';
        
        $testResults.html(loadingHtml).show().addClass('fade-in');
        
        // Define the AJAX complete handler function
        var ajaxCompleteHandler = function(event, xhr, settings) {
            // Check if this is a response to our test worker action
            if (settings.data && settings.data.indexOf('action=ai_faq_test_worker') !== -1 && 
                settings.data.indexOf('worker_name=' + workerName) !== -1) {
                
                // Parse the response
                var response;
                try {
                    response = JSON.parse(xhr.responseText);
                } catch(e) {
                    console.error('Failed to parse AJAX response', e);
                    return;
                }
                
                if (response.success) {
                    // Format the test results
                    var healthData = response.data;
                    var resultHtml = '<div class="test-result-success">';
                    resultHtml += '<span class="dashicons dashicons-yes-alt"></span> ';
                    resultHtml += '<span class="test-status">Connection successful</span>';
                    resultHtml += '</div>';
                    
                    // Add response time if available
                    if (healthData.response_time) {
                        resultHtml += '<div class="test-detail"><strong>Response time:</strong> ' +
                            healthData.response_time + ' ms</div>';
                    }
                    
                    // Add note about favicon.ico 404 errors and health endpoint details
                    resultHtml += '<div class="test-detail note"><small>Note: Cloudflare logs may show 404 errors for favicon.ico requests - these are normal browser behavior and can be ignored. <strong>Health Check:</strong> Workers implement a standardized /health endpoint that responds to GET requests with detailed status information.</small></div>';
                    
                    // Add health data if available
                    if (healthData.data && healthData.data.status) {
                        resultHtml += '<div class="test-detail"><strong>Status:</strong> ' + 
                            healthData.data.status + '</div>';
                    }
                    
                    $testResults.html(resultHtml).show().addClass('fade-in');
                    $workerCard.addClass('tested-success').removeClass('tested-error');
                } else {
                    // Format error message
                    var resultHtml = '<div class="test-result-error">';
                    resultHtml += '<span class="dashicons dashicons-warning"></span> ';
                    resultHtml += '<span class="test-status">Test failed</span>';
                    resultHtml += '</div>';
                    
                    if (response.data && response.data.error_code) {
                        // If we have detailed error information
                        var errorCode = response.data.error_code;
                        var errorMessage = response.data.error_message || response.data;
                        
                        resultHtml += '<div class="test-detail"><strong>Error ' + errorCode + ':</strong> ' + errorMessage + '</div>';
                        
                        // Add specific guidance based on error code
                        if (errorCode === 405) {
                            resultHtml += '<div class="test-detail note"><small><strong>Method Not Allowed (405)</strong>: This worker requires POST requests with JSON data. The test system automatically uses POST requests with test data, so this may indicate an API configuration issue.</small></div>';
                        } else if (errorCode === 400) {
                            resultHtml += '<div class="test-detail note"><small><strong>Bad Request (400)</strong>: The worker rejected the request. This may indicate an issue with the test data format or missing required fields in the request payload.</small></div>';
                        } else if (errorCode === 404) {
                            resultHtml += '<div class="test-detail note"><small><strong>Not Found (404)</strong>: The /health endpoint was not found. Verify the worker URL is correct and includes the proper domain/path.</small></div>';
                        }
                    } else if (response.data) {
                        // Simple error message
                        resultHtml += '<div class="test-detail"><strong>Error:</strong> ' + response.data + '</div>';
                    }
                    
                    // Always add a note about worker endpoint implementation
                    resultHtml += '<div class="test-detail note"><small><strong>WORKER ENDPOINT:</strong> Workers implement two types of endpoints: (1) A /health endpoint for GET requests to check connectivity, and (2) The main endpoint for POST requests with JSON data for actual FAQ processing. The health check ensures basic connectivity before production use.</small></div>';
                    
                    $testResults.html(resultHtml).show().addClass('fade-in');
                    $workerCard.addClass('tested-error').removeClass('tested-success');
                }
                
                // Set auto-hide timer
                activeTimers[workerName] = setTimeout(function() {
                    $testResults.removeClass('fade-in').addClass('fade-out');
                    setTimeout(function() {
                        $testResults.removeClass('fade-out').hide();
                    }, 500);
                    delete activeTimers[workerName];
                }, autoHideDelay);
                
                // Remove this listener to avoid multiple executions
                $(document).off('ajaxComplete', ajaxCompleteHandler);
            }
        };
        
        // Monitor AJAX requests
        $(document).on('ajaxComplete', ajaxCompleteHandler);
    });
    
    // Add CSS for spin animation
    $('<style>')
        .text('@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }')
        .appendTo('head');

})(jQuery);