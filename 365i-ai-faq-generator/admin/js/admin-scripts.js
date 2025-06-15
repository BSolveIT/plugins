/**
 * Admin JavaScript for the FAQ AI Generator
 *
 * Handles admin interactions, tabs, testing workers, and other functionality.
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/admin/js
 */

(function($) {
    'use strict';

    /**
     * Document ready handler
     */
    $(document).ready(function() {
        // Dashboard functionality
        initDashboard();
        
        // Settings page functionality
        initSettingsTabs();
        initRangeSliders();
        initWorkerTests();
        initRateLimitReset();
    });

    /**
     * Initialize dashboard functionality
     */
    function initDashboard() {
        // Worker status updates
        updateWorkerStatuses();
        
        // Reset all rate limits
        $('#faq-ai-reset-all-limits').on('click', function() {
            if (confirm(faqAiAdmin.strings.confirm)) {
                resetAllRateLimits();
            }
        });
    }
    
    /**
     * Initialize settings tabs
     */
    function initSettingsTabs() {
        // Already implemented in admin-settings.php with inline script
    }
    
    /**
     * Initialize range sliders
     */
    function initRangeSliders() {
        // Already implemented in admin-settings.php with inline script
    }
    
    /**
     * Initialize worker tests
     */
    function initWorkerTests() {
        // Individual worker tests - already implemented in admin-settings.php
        
        // Test all workers button
        $('#faq-ai-test-all-workers').on('click', function() {
            testAllWorkers();
        });
    }
    
    /**
     * Initialize rate limit reset functionality
     */
    function initRateLimitReset() {
        // Reset individual worker
        $('.faq-ai-reset-limit').on('click', function() {
            const worker = $(this).data('worker');
            if (confirm(faqAiAdmin.strings.confirm)) {
                resetRateLimit(worker);
            }
        });
        
        // Reset all limits button in settings
        $('#faq-ai-reset-all-limits-settings').on('click', function() {
            if (confirm(faqAiAdmin.strings.confirm)) {
                resetAllRateLimits();
            }
        });
    }
    
    /**
     * Update worker statuses on dashboard
     */
    function updateWorkerStatuses() {
        $('.faq-ai-worker-item').each(function() {
            const worker = $(this).data('worker');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'faq_ai_get_worker_status',
                    nonce: faqAiAdmin.nonce,
                    worker: worker
                },
                success: function(response) {
                    if (response.success) {
                        updateWorkerStatusUI(worker, response.data.rate_info);
                    }
                }
            });
        });
    }
    
    /**
     * Update worker status UI
     */
    function updateWorkerStatusUI(worker, rateInfo) {
        const workerItem = $(`.faq-ai-worker-item[data-worker="${worker}"]`);
        const rateLimit = workerItem.find('.faq-ai-worker-rate-limit-value');
        
        // Update rate limit info
        rateLimit.text(`${rateInfo.used}/${rateInfo.limit} (${rateInfo.status})`);
        
        // Update status color
        if (rateInfo.status === 'exceeded') {
            rateLimit.addClass('exceeded');
        } else if (rateInfo.status === 'warning') {
            rateLimit.addClass('warning');
        } else {
            rateLimit.removeClass('exceeded warning');
        }
    }
    
    /**
     * Test all workers
     */
    function testAllWorkers() {
        const resultsElement = $('#faq-ai-test-all-results');
        resultsElement.html('<p class="testing">' + 'Testing all workers...' + '</p>');
        
        // Get worker keys from worker items
        const workers = [];
        $('.faq-ai-worker-item').each(function() {
            workers.push($(this).data('worker'));
        });
        
        const results = {};
        let completedTests = 0;
        
        // Test each worker
        workers.forEach(function(worker) {
            // Create appropriate test data for each worker type
            const testData = createTestData(worker);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'faq_ai_test_worker',
                    nonce: faqAiAdmin.nonce,
                    worker: worker,
                    test_data: JSON.stringify(testData)
                },
                success: function(response) {
                    results[worker] = {
                        success: response.success,
                        message: response.success ? faqAiAdmin.strings.testSuccess : faqAiAdmin.strings.testFailed + response.data.message
                    };
                    completedTests++;
                    
                    if (completedTests === workers.length) {
                        displayTestResults(workers, results, resultsElement);
                    }
                },
                error: function() {
                    results[worker] = {
                        success: false,
                        message: 'Connection error'
                    };
                    completedTests++;
                    
                    if (completedTests === workers.length) {
                        displayTestResults(workers, results, resultsElement);
                    }
                }
            });
        });
    }
    
    /**
     * Display test results for all workers
     */
    function displayTestResults(workers, results, resultsElement) {
        let html = '<ul class="faq-ai-test-results-list">';
        
        workers.forEach(function(worker) {
            const result = results[worker];
            const className = result.success ? 'success' : 'error';
            const displayName = worker.replace(/-/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
            
            html += '<li class="' + className + '">';
            html += '<span class="worker-name">' + displayName + ':</span> ';
            html += '<span class="result-message">' + result.message + '</span>';
            html += '</li>';
        });
        
        html += '</ul>';
        resultsElement.html(html);
    }
    
    /**
     * Reset rate limit for a specific worker
     */
    function resetRateLimit(worker) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'faq_ai_reset_rate_limits',
                nonce: faqAiAdmin.nonce,
                worker: worker
            },
            success: function(response) {
                if (response.success) {
                    alert(faqAiAdmin.strings.resetRateLimits);
                    // Update the status
                    updateWorkerStatuses();
                } else {
                    alert(faqAiAdmin.strings.resetFailed + response.data.message);
                }
            },
            error: function() {
                alert('Connection error');
            }
        });
    }
    
    /**
     * Reset all worker rate limits
     */
    function resetAllRateLimits() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'faq_ai_reset_rate_limits',
                nonce: faqAiAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(faqAiAdmin.strings.resetRateLimits);
                    // Update all statuses
                    updateWorkerStatuses();
                } else {
                    alert(faqAiAdmin.strings.resetFailed + response.data.message);
                }
            },
            error: function() {
                alert('Connection error');
            }
        });
    }

    /**
     * Create appropriate test data for each worker type
     *
     * @param {string} worker - The worker key
     * @return {object} - Test data object
     */
    function createTestData(worker) {
        // Default test data
        const defaultData = {
            test: true,
            timestamp: Math.floor(Date.now() / 1000)
        };
        
        // Specific test data for each worker type
        switch(worker) {
            case 'question':
                return {
                    ...defaultData,
                    content: "This is a test content for generating questions.",
                    mode: "test"
                };
            
            case 'answer':
                return {
                    ...defaultData,
                    question: "What is this plugin used for?",
                    mode: "test"
                };
            
            case 'enhance':
                return {
                    ...defaultData,
                    question: "What is this plugin used for?",
                    answer: "This plugin is used for generating FAQs.",
                    mode: "test"
                };
            
            case 'seo':
                return {
                    ...defaultData,
                    question: "What is this plugin used for?",
                    answer: "This plugin is used for generating FAQs.",
                    mode: "test"
                };
            
            case 'extract':
                return {
                    ...defaultData,
                    url: "https://example.com",
                    mode: "test"
                };
            
            case 'topic':
                return {
                    ...defaultData,
                    content: "This is a test content for generating topics.",
                    mode: "test"
                };
            
            case 'validate':
                return {
                    ...defaultData,
                    question: "What is this plugin used for?",
                    answer: "This plugin is used for generating FAQs.",
                    mode: "test"
                };
            
            default:
                return defaultData;
        }
    }

})(jQuery);