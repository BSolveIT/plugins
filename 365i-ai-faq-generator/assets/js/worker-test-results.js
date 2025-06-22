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
                    // Format the test results with comprehensive worker data
                    var healthData = response.data;
                    var resultHtml = '<div class="test-result-success">';
                    resultHtml += '<span class="dashicons dashicons-yes-alt"></span> ';
                    resultHtml += '<span class="test-status">Connection successful</span>';
                    resultHtml += '</div>';
                    
                    // Create responsive grid layout
                    resultHtml += '<div class="worker-test-grid">';
                    
                    // Left column
                    resultHtml += '<div class="worker-test-column">';
                    
                    // Add response time if available
                    if (healthData.response_time) {
                        resultHtml += '<div class="test-detail"><strong>Response time:</strong> ' +
                            healthData.response_time + ' ms</div>';
                    }
                    
                    // Display comprehensive worker health data
                    if (healthData.data) {
                        var workerData = healthData.data;
                        
                        // Worker status and basic info
                        if (workerData.status) {
                            resultHtml += '<div class="test-detail"><strong>Status:</strong> ' +
                                workerData.status + '</div>';
                        }
                        
                        if (workerData.model) {
                            resultHtml += '<div class="test-detail"><strong>Model:</strong> ' +
                                workerData.model + '</div>';
                        }
                        
                        if (workerData.service) {
                            resultHtml += '<div class="test-detail"><strong>Service:</strong> ' + workerData.service + '</div>';
                        }
                        
                        if (workerData.response_time) {
                            resultHtml += '<div class="test-detail"><strong>Expected Response Time:</strong> ' +
                                workerData.response_time + '</div>';
                        }
                        
                        // Additional worker information
                        if (workerData.version) {
                            resultHtml += '<div class="test-detail"><strong>Version:</strong> ' + workerData.version + '</div>';
                        }
                        
                        if (workerData.environment) {
                            resultHtml += '<div class="test-detail"><strong>Environment:</strong> ' + workerData.environment + '</div>';
                        }
                        
                        // Rate limiting information from worker
                        if (workerData.rate_limits) {
                            resultHtml += '<div class="test-detail-section"><strong>Rate Limits:</strong>';
                            if (workerData.rate_limits.hourly) {
                                resultHtml += '<br><span style="margin-left: 10px;">Hourly: ' + workerData.rate_limits.hourly + '</span>';
                            }
                            if (workerData.rate_limits.daily) {
                                resultHtml += '<br><span style="margin-left: 10px;">Daily: ' + workerData.rate_limits.daily + '</span>';
                            }
                            if (workerData.rate_limits.weekly) {
                                resultHtml += '<br><span style="margin-left: 10px;">Weekly: ' + workerData.rate_limits.weekly + '</span>';
                            }
                            if (workerData.rate_limits.monthly) {
                                resultHtml += '<br><span style="margin-left: 10px;">Monthly: ' + workerData.rate_limits.monthly + '</span>';
                            }
                            resultHtml += '</div>';
                        }
                        
                        resultHtml += '</div>'; // End left column
                        
                        // Right column
                        resultHtml += '<div class="worker-test-column">';
                        
                        // Model, Service, and Timestamp in RIGHT column
                        if (workerData.model) {
                            resultHtml += '<div class="test-detail"><strong>Model:</strong> ' +
                                workerData.model + '</div>';
                        }
                        
                        if (workerData.service) {
                            resultHtml += '<div class="test-detail"><strong>Service:</strong> ' + workerData.service + '</div>';
                        }
                        
                        if (workerData.timestamp) {
                            resultHtml += '<div class="test-detail"><strong>Timestamp:</strong> ' + workerData.timestamp + '</div>';
                        }
                        
                        // Violation thresholds from worker
                        if (workerData.violation_thresholds) {
                            resultHtml += '<div class="test-detail-section"><strong>Violation Thresholds:</strong>';
                            if (workerData.violation_thresholds.soft_warning) {
                                resultHtml += '<br><span style="margin-left: 10px;">Soft Warning: ' + workerData.violation_thresholds.soft_warning + '</span>';
                            }
                            if (workerData.violation_thresholds.hard_block) {
                                resultHtml += '<br><span style="margin-left: 10px;">Hard Block: ' + workerData.violation_thresholds.hard_block + '</span>';
                            }
                            if (workerData.violation_thresholds.permanent_ban) {
                                resultHtml += '<br><span style="margin-left: 10px;">Permanent Ban: ' + workerData.violation_thresholds.permanent_ban + '</span>';
                            }
                            resultHtml += '</div>';
                        }
                        
                        // Display any other key-value pairs from the worker response
                        Object.keys(workerData).forEach(function(key) {
                            if (!['status', 'model', 'service', 'timestamp', 'response_time', 'rate_limits', 'violation_thresholds', 'version', 'environment'].includes(key)) {
                                var value = workerData[key];
                                var keyLabel = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                
                                if (typeof value === 'string' || typeof value === 'number') {
                                    resultHtml += '<div class="test-detail"><strong>' + keyLabel + ':</strong> ' + value + '</div>';
                                } else if (Array.isArray(value)) {
                                    // Handle arrays (like features) specially with chips/badges
                                    if (value.length > 0) {
                                        resultHtml += '<div class="test-detail-section"><strong>' + keyLabel + ':</strong>';
                                        resultHtml += '<div class="feature-chips">';
                                        value.forEach(function(item) {
                                            var displayItem = typeof item === 'string' ? item.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : item;
                                            resultHtml += '<span class="feature-chip">' + displayItem + '</span>';
                                        });
                                        resultHtml += '</div></div>';
                                    }
                                } else if (typeof value === 'object' && value !== null) {
                                    resultHtml += '<div class="test-detail"><strong>' + keyLabel + ':</strong> ' + JSON.stringify(value) + '</div>';
                                }
                            }
                        });
                        
                        resultHtml += '</div>'; // End right column
                    }
                    
                    resultHtml += '</div>'; // End grid
                    
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