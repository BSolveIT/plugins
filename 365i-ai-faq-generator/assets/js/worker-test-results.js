/**
 * Worker test results JavaScript for 365i AI FAQ Generator admin interface.
 *
 * Clean, professional worker connection test interface with improved
 * information hierarchy and user experience.
 *
 * @package AI_FAQ_Generator
 * @subpackage Assets
 * @since 2.2.0
 */

(function($) {
    'use strict';

    // Override the original test worker connection event
    $(document).on('click', '.test-worker-connection', function(e) {
        var $button = $(this);
        var $workerCard = $button.closest('.worker-card, .worker-config-card');
        var workerName = $button.data('worker');
        
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
            }, 300);
        }
        
        // Show loading indicator
        var loadingHtml = '<div class="test-result-loading">';
        loadingHtml += '<span class="dashicons dashicons-update-alt" style="animation: spin 2s linear infinite;"></span>';
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
                    // Format the test results with clean dashboard design
                    var healthData = response.data;
                    var resultHtml = buildSuccessDisplay(healthData, workerName);
                    
                    $testResults.html(resultHtml).show().addClass('fade-in');
                    $workerCard.addClass('tested-success').removeClass('tested-error');
                } else {
                    // Format error message
                    var resultHtml = buildErrorDisplay(response);
                    
                    $testResults.html(resultHtml).show().addClass('fade-in');
                    $workerCard.addClass('tested-error').removeClass('tested-success');
                }
                
                // Remove this listener to avoid multiple executions
                $(document).off('ajaxComplete', ajaxCompleteHandler);
            }
        };
        
        // Monitor AJAX requests
        $(document).on('ajaxComplete', ajaxCompleteHandler);
    });

    // Handle dismiss button clicks
    $(document).on('click', '.test-results-dismiss', function(e) {
        e.preventDefault();
        var $testResults = $(this).closest('.test-results');
        var $workerCard = $testResults.closest('.worker-card, .worker-config-card');
        
        $testResults.removeClass('fade-in').addClass('fade-out');
        setTimeout(function() {
            $testResults.removeClass('fade-out').hide();
            $workerCard.removeClass('tested-success tested-error');
        }, 300);
    });

    /**
     * Build success display with clean dashboard design
     */
    function buildSuccessDisplay(healthData, workerName) {
        var html = '<div class="test-result-success">';
        html += '<span class="dashicons dashicons-yes-alt"></span>';
        html += '<span class="test-status">Connection successful</span>';
        html += '<button type="button" class="test-results-dismiss" title="Dismiss results">';
        html += '<span class="dashicons dashicons-no-alt"></span>';
        html += '</button>';
        html += '</div>';

        if (healthData.data) {
            var workerData = healthData.data;
            
            html += '<div class="worker-health-dashboard">';
            
            // Header with title and status
            html += '<div class="health-header">';
            html += '<h3 class="health-title">Worker Health Report</h3>';
            var status = workerData.status || 'operational';
            var statusClass = (status.toLowerCase() === 'operational') ? 'operational' : 'error';
            html += '<span class="health-status-badge ' + statusClass + '">' + status + '</span>';
            html += '</div>';
            
            // Key metrics
            html += '<div class="health-metrics">';
            
            if (healthData.response_time) {
                html += '<div class="health-metric">';
                html += '<span class="health-metric-value">' + healthData.response_time + ' ms</span>';
                html += '<span class="health-metric-label">Response Time</span>';
                html += '</div>';
            }
            
            if (workerData.version) {
                html += '<div class="health-metric">';
                html += '<span class="health-metric-value">' + workerData.version + '</span>';
                html += '<span class="health-metric-label">Version</span>';
                html += '</div>';
            }
            
            if (workerData.cache_status) {
                html += '<div class="health-metric">';
                html += '<span class="health-metric-value">' + formatCacheStatus(workerData.cache_status) + '</span>';
                html += '<span class="health-metric-label">Cache Status</span>';
                html += '</div>';
            }
            
            html += '</div>';
            
            // Detailed content
            html += '<div class="health-content">';
            
            // AI Model Configuration
            if (workerData.model || workerData.current_model || workerData.model_source) {
                html += '<div class="health-section">';
                html += '<h4 class="health-section-title">AI Model Configuration</h4>';
                html += '<div class="health-info-grid">';
                
                if (workerData.current_model || workerData.model) {
                    html += buildInfoItem('Model', workerData.current_model || workerData.model);
                }
                if (workerData.model_source) {
                    html += buildInfoItem('Source', workerData.model_source);
                }
                if (workerData.mode) {
                    html += buildInfoItem('Mode', workerData.mode);
                }
                
                html += '</div>';
                html += '</div>';
            }
            
            // Technical Details
            html += '<div class="health-section">';
            html += '<h4 class="health-section-title">Technical Details</h4>';
            html += '<div class="health-info-grid">';
            
            if (workerData.service) {
                html += buildInfoItem('Service', workerData.service);
            }
            if (workerData.worker_type) {
                html += buildInfoItem('Type', workerData.worker_type);
            }
            if (workerData.environment) {
                html += buildInfoItem('Environment', workerData.environment);
            }
            if (workerData.timestamp) {
                html += buildInfoItem('Last Updated', formatTimestamp(workerData.timestamp));
            }
            
            html += '</div>';
            html += '</div>';
            
            // Performance & Rate Limiting
            if (workerData.performance || workerData.rate_limiting || workerData.rate_limits) {
                html += '<div class="health-section">';
                html += '<h4 class="health-section-title">Performance & Limits</h4>';
                html += '<div class="health-info-grid">';
                
                if (workerData.performance) {
                    Object.keys(workerData.performance).forEach(function(key) {
                        var label = formatLabel(key);
                        html += buildInfoItem(label, workerData.performance[key]);
                    });
                }
                
                var rateLimits = workerData.rate_limiting || workerData.rate_limits;
                if (rateLimits && typeof rateLimits === 'object') {
                    Object.keys(rateLimits).forEach(function(key) {
                        var label = formatLabel(key);
                        html += buildInfoItem(label, rateLimits[key]);
                    });
                }
                
                html += '</div>';
                html += '</div>';
            }
            
            // Features
            var features = collectFeatures(workerData);
            if (features.length > 0) {
                html += '<div class="health-section">';
                html += '<h4 class="health-section-title">Features & Capabilities</h4>';
                html += '<div class="health-features">';
                features.forEach(function(feature) {
                    html += '<span class="health-feature-tag">' + feature + '</span>';
                });
                html += '</div>';
                html += '</div>';
            }
            
            html += '</div>'; // End health-content
            html += '</div>'; // End worker-health-dashboard
        }

        return html;
    }

    /**
     * Build error display
     */
    function buildErrorDisplay(response) {
        var html = '<div class="test-result-error">';
        html += '<span class="dashicons dashicons-warning"></span>';
        html += '<span class="test-status">Connection failed</span>';
        html += '<button type="button" class="test-results-dismiss" title="Dismiss results">';
        html += '<span class="dashicons dashicons-no-alt"></span>';
        html += '</button>';
        html += '</div>';
        
        if (response.data && response.data.error_code) {
            var errorCode = response.data.error_code;
            var errorMessage = response.data.error_message || response.data;
            
            html += '<div class="test-detail">';
            html += '<strong>Error ' + errorCode + ':</strong> ' + errorMessage;
            html += '</div>';
            
            // Add specific guidance based on error code
            if (errorCode === 405) {
                html += '<div class="test-detail note">';
                html += '<strong>Method Not Allowed (405)</strong>: This worker requires POST requests with JSON data. ';
                html += 'The test system automatically uses POST requests with test data, so this may indicate an API configuration issue.';
                html += '</div>';
            } else if (errorCode === 400) {
                html += '<div class="test-detail note">';
                html += '<strong>Bad Request (400)</strong>: The worker rejected the request. ';
                html += 'This may indicate an issue with the test data format or missing required fields in the request payload.';
                html += '</div>';
            } else if (errorCode === 404) {
                html += '<div class="test-detail note">';
                html += '<strong>Not Found (404)</strong>: The /health endpoint was not found. ';
                html += 'Verify the worker URL is correct and includes the proper domain/path.';
                html += '</div>';
            }
        } else if (response.data) {
            html += '<div class="test-detail">';
            html += '<strong>Error:</strong> ' + response.data;
            html += '</div>';
        }
        
        return html;
    }

    /**
     * Build info item HTML
     */
    function buildInfoItem(label, value) {
        return '<div class="health-info-item">' +
               '<span class="health-info-label">' + label + '</span>' +
               '<span class="health-info-value">' + value + '</span>' +
               '</div>';
    }

    /**
     * Format label for display
     */
    function formatLabel(key) {
        return key.replace(/_/g, ' ')
                 .replace(/\b\w/g, function(l) { return l.toUpperCase(); });
    }

    /**
     * Format cache status
     */
    function formatCacheStatus(status) {
        return status.charAt(0).toUpperCase() + status.slice(1);
    }

    /**
     * Format timestamp
     */
    function formatTimestamp(timestamp) {
        try {
            var date = new Date(timestamp);
            return date.toLocaleString();
        } catch(e) {
            return timestamp;
        }
    }

    /**
     * Collect features from worker data
     */
    function collectFeatures(workerData) {
        var features = [];
        
        Object.keys(workerData).forEach(function(key) {
            if (Array.isArray(workerData[key]) && workerData[key].length > 0) {
                workerData[key].forEach(function(item) {
                    var displayItem = typeof item === 'string' ? 
                        formatLabel(item) : 
                        String(item);
                    features.push(displayItem);
                });
            }
        });
        
        // Add some standard features based on worker data
        if (workerData.rate_limiting) {
            features.push('Rate Limited');
        }
        if (workerData.cache_status === 'active') {
            features.push('Caching Enabled');
        }
        if (workerData.model || workerData.current_model) {
            features.push('AI Powered');
        }
        
        return features;
    }
    
    // Add CSS for spin animation
    $('<style>')
        .text('@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }')
        .appendTo('head');

})(jQuery);