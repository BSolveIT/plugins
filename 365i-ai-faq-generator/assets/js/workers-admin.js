/**
 * Workers admin JavaScript for 365i AI FAQ Generator.
 * 
 * Handles worker configuration interface functionality including
 * worker toggles, test all workers, and refresh status functionality.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Assets
 * @since 2.0.1
 */

(function($) {
    'use strict';

    /**
     * Track if initialization has already occurred.
     */
    let isInitialized = false;

    /**
     * Initialize workers admin functionality.
     */
    function initWorkersAdmin() {
        // Prevent multiple initializations
        if (isInitialized) {
            return;
        }

        initWorkerToggles();
        initTestAllWorkers();
        initRefreshStatus();
        
        isInitialized = true;
    }

    /**
     * Initialize worker toggle functionality.
     * These switches enable/disable individual workers for FAQ generation.
     */
    function initWorkerToggles() {
        const workerToggles = document.querySelectorAll('.worker-toggle input[type="checkbox"]');
        workerToggles.forEach(function(toggle) {
            toggle.addEventListener('change', function() {
                const card = this.closest('.worker-config-card');
                if (this.checked) {
                    card.classList.remove('disabled');
                    card.classList.add('enabled');
                } else {
                    card.classList.remove('enabled');
                    card.classList.add('disabled');
                }
            });
        });
    }

    /**
     * Initialize test all workers functionality.
     * This tests the health/connectivity of each worker individually.
     */
    function initTestAllWorkers() {
        const testAllButton = document.querySelector('.test-all-workers');
        if (testAllButton) {
            testAllButton.addEventListener('click', function() {
                const testButtons = document.querySelectorAll('.test-worker-connection');
                testButtons.forEach(function(button, index) {
                    setTimeout(function() {
                        button.click();
                    }, index * 1000); // Stagger tests by 1 second
                });
            });
        }
    }

    /**
     * Initialize refresh status functionality.
     * This refreshes usage statistics and analytics (NOT connectivity testing).
     */
    function initRefreshStatus() {
        const refreshStatusButton = document.querySelector('.refresh-worker-status');
        if (!refreshStatusButton) {
            return;
        }

        refreshStatusButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const button = this;
            const buttonText = button.querySelector('span:not(.dashicons)');
            const buttonIcon = button.querySelector('.dashicons');
            const originalText = buttonText ? buttonText.textContent : 'Refresh Status';
            
            // Show loading state
            button.disabled = true;
            if (buttonText) {
                buttonText.textContent = 'Refreshing...';
            }
            if (buttonIcon) {
                buttonIcon.style.animation = 'spin 1s linear infinite';
            }
            
            // Make AJAX request to refresh status
            $.ajax({
                url: aiFaqGen.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_faq_get_worker_status',
                    nonce: aiFaqGen.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        refreshWorkerStats(response.data);
                        
                        // Show detailed success message with count
                        var message = 'Worker statistics refreshed successfully';
                        if (response.data.workers) {
                            var workerCount = Object.keys(response.data.workers).length;
                            message += ' (' + workerCount + ' workers updated)';
                        }
                        showNotification(message, 'success');
                        
                        // Add prominent visual feedback to the button
                        if (buttonText) {
                            buttonText.textContent = '✓ Updated';
                            button.style.backgroundColor = '#00a32a';
                            button.style.color = 'white';
                            button.style.borderColor = '#00a32a';
                            
                            setTimeout(function() {
                                buttonText.textContent = originalText;
                                button.style.backgroundColor = '';
                                button.style.color = '';
                                button.style.borderColor = '';
                            }, 3000);
                        }
                    } else {
                        showNotification('Failed to refresh worker status: ' + (response.data || 'Unknown error'), 'error');
                        
                        // Show error state on button
                        if (buttonText) {
                            buttonText.textContent = '✗ Failed';
                            button.style.backgroundColor = '#d63638';
                            button.style.color = 'white';
                            button.style.borderColor = '#d63638';
                            
                            setTimeout(function() {
                                buttonText.textContent = originalText;
                                button.style.backgroundColor = '';
                                button.style.color = '';
                                button.style.borderColor = '';
                            }, 3000);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Error refreshing worker status: ' + error, 'error');
                    
                    // Show error state on button
                    if (buttonText) {
                        buttonText.textContent = '✗ Error';
                        button.style.backgroundColor = '#d63638';
                        button.style.color = 'white';
                        button.style.borderColor = '#d63638';
                        
                        setTimeout(function() {
                            buttonText.textContent = originalText;
                            button.style.backgroundColor = '';
                            button.style.color = '';
                            button.style.borderColor = '';
                        }, 3000);
                    }
                },
                complete: function() {
                    // Reset button state (but preserve temporary success/error styling)
                    button.disabled = false;
                    if (buttonIcon) {
                        buttonIcon.style.animation = '';
                    }
                }
            });
        });
    }

    /**
     * Refresh worker statistics on the page.
     * 
     * @param {Object} data - Worker status data from server
     */
    function refreshWorkerStats(data) {
        if (!data.workers) {
            return;
        }

        var updatedCount = 0;

        // Update usage statistics for each worker
        Object.entries(data.workers).forEach(function([workerName, status]) {
            const workerCard = document.querySelector('[data-worker="' + workerName + '"]');
            if (!workerCard) {
                return;
            }

            // Update current usage display
            const usageCurrentSpan = workerCard.querySelector('.usage-current');
            const usageFill = workerCard.querySelector('.usage-fill');
            const rateLimit = status.rate_limit || 50;
            const currentUsage = status.current_usage || 0;
            
            if (usageCurrentSpan) {
                // Animate the number change
                const oldUsage = parseInt(usageCurrentSpan.textContent) || 0;
                if (oldUsage !== currentUsage) {
                    usageCurrentSpan.style.color = '#00a32a';
                    usageCurrentSpan.style.fontWeight = 'bold';
                    setTimeout(function() {
                        usageCurrentSpan.style.color = '';
                        usageCurrentSpan.style.fontWeight = '';
                    }, 2000);
                }
                usageCurrentSpan.textContent = currentUsage;
                updatedCount++;
            }
            
            if (usageFill) {
                const usagePercent = rateLimit > 0 ? (currentUsage / rateLimit) * 100 : 0;
                usageFill.style.width = Math.min(usagePercent, 100) + '%';
                
                // Update usage bar color based on percentage
                usageFill.className = 'usage-fill';
                if (usagePercent >= 90) {
                    usageFill.classList.add('usage-critical');
                } else if (usagePercent >= 75) {
                    usageFill.classList.add('usage-warning');
                } else {
                    usageFill.classList.add('usage-normal');
                }
                
                // Add a brief highlight animation
                usageFill.style.transition = 'all 0.3s ease';
                usageFill.style.boxShadow = '0 0 10px rgba(0, 163, 42, 0.5)';
                setTimeout(function() {
                    usageFill.style.boxShadow = '';
                }, 1000);
            }
        });

        // Update analytics metrics if available
        if (data.analytics) {
            updateAnalyticsMetrics(data.analytics);
        }

        // Update violation stats if available
        if (data.violations) {
            updateViolationStats(data.violations);
        }

        console.log('Refreshed statistics for ' + updatedCount + ' workers');
    }

    /**
     * Update analytics metrics display.
     * 
     * @param {Object} analytics - Analytics data
     */
    function updateAnalyticsMetrics(analytics) {
        const totalRequestsElement = document.getElementById('total-requests-today');
        const avgResponseTimeElement = document.getElementById('avg-response-time');
        const successRateElement = document.getElementById('success-rate');
        
        if (totalRequestsElement && analytics.total_requests !== undefined) {
            animateNumberChange(totalRequestsElement, analytics.total_requests);
        }
        
        if (avgResponseTimeElement && analytics.avg_response_time !== undefined) {
            animateNumberChange(avgResponseTimeElement, analytics.avg_response_time + 's');
        }
        
        if (successRateElement && analytics.success_rate !== undefined) {
            animateNumberChange(successRateElement, analytics.success_rate + '%');
        }
    }

    /**
     * Animate number changes in elements.
     * 
     * @param {Element} element - Element to animate
     * @param {string} newValue - New value to display
     */
    function animateNumberChange(element, newValue) {
        if (element.textContent !== newValue.toString()) {
            element.style.color = '#00a32a';
            element.style.fontWeight = 'bold';
            element.style.transform = 'scale(1.1)';
            element.textContent = newValue;
            
            setTimeout(function() {
                element.style.color = '';
                element.style.fontWeight = '';
                element.style.transform = '';
            }, 1500);
        }
    }

    /**
     * Update violation statistics display.
     * 
     * @param {Object} violations - Violation data
     */
    function updateViolationStats(violations) {
        // Update violation counters if elements exist
        const violationElements = {
            'total-violations-24h': violations.total_24h,
            'unique-violators': violations.unique_ips,
            'blocked-ips-count': violations.blocked_count
        };

        Object.entries(violationElements).forEach(function([elementId, value]) {
            const element = document.getElementById(elementId);
            if (element && value !== undefined) {
                animateNumberChange(element, value);
            }
        });
    }

    /**
     * Show notification message with enhanced styling.
     * Remove any existing notifications to prevent duplicates.
     * 
     * @param {string} message - Message to display
     * @param {string} type - Notification type (success, error, warning, info)
     */
    function showNotification(message, type) {
        type = type || 'info';
        
        // Remove any existing notifications to prevent duplicates
        const existingNotifications = document.querySelectorAll('.refresh-status-notice');
        existingNotifications.forEach(function(notification) {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        });
        
        // Create notification element with better styling
        const notification = document.createElement('div');
        notification.className = 'notice notice-' + type + ' is-dismissible refresh-status-notice';
        notification.innerHTML = '<p><strong>' + message + '</strong></p>';
        notification.style.fontSize = '14px';
        notification.style.fontWeight = 'bold';
        notification.style.margin = '15px 0';
        notification.style.padding = '12px';
        
        // Find appropriate location to insert notification
        const targetElement = document.querySelector('.ai-faq-gen-workers') || 
                            document.querySelector('.wrap') || 
                            document.body;
        
        // Insert notification at the top
        targetElement.insertBefore(notification, targetElement.firstChild);
        
        // Scroll to notification
        notification.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Auto-remove after 6 seconds
        setTimeout(function() {
            if (notification.parentNode) {
                notification.style.transition = 'opacity 0.5s ease';
                notification.style.opacity = '0';
                setTimeout(function() {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 500);
            }
        }, 6000);
    }

    /**
     * Add CSS for animations and enhanced styling.
     */
    function addRequiredStyles() {
        // Check if styles are already added
        if (document.getElementById('workers-admin-styles')) {
            return;
        }

        const styles = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .usage-fill.usage-normal {
                background-color: #00a32a;
            }
            .usage-fill.usage-warning {
                background-color: #ffb900;
            }
            .usage-fill.usage-critical {
                background-color: #d63638;
            }
            .refresh-status-notice {
                margin: 15px 0;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .metric-value, .usage-current {
                transition: all 0.3s ease;
            }
        `;
        
        const styleSheet = document.createElement('style');
        styleSheet.type = 'text/css';
        styleSheet.id = 'workers-admin-styles';
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }

    /**
     * Safe initialization function that prevents duplicate initialization.
     */
    function safeInit() {
        if (isInitialized) {
            return;
        }
        
        initWorkersAdmin();
        addRequiredStyles();
    }

    // Initialize when document is ready - use only one method to prevent duplicates
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', safeInit);
    } else {
        // Document is already loaded
        safeInit();
    }

})(jQuery);