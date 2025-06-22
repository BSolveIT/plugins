/**
 * AI Models Status Interface JavaScript
 * 
 * Handles connectivity testing and real-time status updates for AI model status page.
 * Models are managed via Cloudflare KV namespace - no selection functionality.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Admin
 * @since 2.3.0
 */

(function($) {
    'use strict';

    /**
     * AI Models Status class
     */
    class AIModelsStatus {
        constructor() {
            this.workers = {};
            this.currentTests = new Map();
            this.notifications = [];
            
            this.init();
        }

        /**
         * Initialize the AI Models status interface
         */
        init() {
            this.cacheElements();
            this.bindEvents();
            this.loadWorkersData();
            this.initializeTooltips();
            this.checkForTestAllVisibility();
            this.loadInitialAIModelInfo();
        }

        /**
         * Cache DOM elements for better performance
         */
        cacheElements() {
            this.$container = $('.ai-faq-gen-ai-models.modern-layout');
            this.$workerCards = $('.worker-model-card');
            this.$testAllSection = $('.test-all-section');
        }

        /**
         * Bind event handlers
         */
        bindEvents() {
            // Test model connectivity
            $(document).on('click', '.test-model-connectivity', this.handleConnectivityTest.bind(this));
            $(document).on('click', '.test-all-models', this.handleTestAllModels.bind(this));
            
            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts.bind(this));
        }

        /**
         * Load workers data from window object
         */
        loadWorkersData() {
            if (window.aiFaqModelsData) {
                this.workers = window.aiFaqModelsData.workers || {};
            }
        }

        /**
         * Initialize tooltips for enhanced UX
         */
        initializeTooltips() {
            // Add tooltips to status badges and buttons
            $('.status-badge, .test-model-connectivity').each(function() {
                const $this = $(this);
                const title = $this.attr('title');
                if (title) {
                    $this.attr('title', title);
                }
            });
        }

        /**
         * Check if Test All section should be visible
         */
        checkForTestAllVisibility() {
            // Show Test All section only if there are testable workers
            const testableWorkers = this.$workerCards.filter(':not(.no-model-required)').length;
            if (testableWorkers > 1) {
                this.$testAllSection.show();
            }
        }

        /**
         * Load initial AI model information for all workers
         */
        loadInitialAIModelInfo() {
            const $testableCards = $('.worker-model-card').filter(function() {
                // Skip cards that don't require AI models (like faq_extractor)
                return !$(this).find('.no-model-notice').length;
            });

            $testableCards.each((index, card) => {
                const $card = $(card);
                const workerType = $card.data('worker');
                
                if (workerType) {
                    // Stagger requests to avoid overwhelming the server
                    setTimeout(() => {
                        this.fetchWorkerAIModelInfo($card, workerType);
                    }, index * 200);
                }
            });
        }

        /**
         * Fetch AI model information for a specific worker
         */
        fetchWorkerAIModelInfo($card, workerType) {
            // Update UI to show loading state
            this.updateRealtimeModelInfo($card, 'loading');

            // Prepare AJAX data
            const ajaxData = {
                action: 'ai_faq_get_worker_ai_models',
                worker_type: workerType,
                nonce: window.aiFaqModelsData?.nonce || ''
            };

            $.ajax({
                url: window.aiFaqModelsData?.apiEndpoint || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: ajaxData,
                timeout: 15000
            })
            .done((response) => {
                if (response.success && response.data && response.data.ai_model_info) {
                    this.updateRealtimeModelInfo($card, 'success', response.data.ai_model_info);
                } else {
                    this.updateRealtimeModelInfo($card, 'failed');
                }
            })
            .fail((xhr, status, error) => {
                console.error('Failed to fetch AI model info for ' + workerType + ':', error);
                this.updateRealtimeModelInfo($card, 'error');
            });
        }

        /**
         * Handle connectivity test
         */
        handleConnectivityTest(e) {
            e.preventDefault();
            
            const $button = $(e.target).closest('.test-model-connectivity');
            const $card = $button.closest('.worker-model-card');
            const workerType = $card.data('worker');
            
            if (!workerType) {
                this.showNotification('warning', 'Worker type not found');
                return;
            }
            
            this.performConnectivityTest($card, workerType, $button);
        }

        /**
         * Handle test all models
         */
        handleTestAllModels(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            this.setLoadingState($button, true, 'Testing...');
            
            const $testableCards = $('.worker-model-card:not(.no-model-required)');
            let testsCompleted = 0;
            const totalTests = $testableCards.length;
            
            this.showNotification('info', `Starting connectivity tests for ${totalTests} workers...`);
            
            $testableCards.each((index, card) => {
                const $card = $(card);
                const workerType = $card.data('worker');
                
                if (workerType) {
                    setTimeout(() => {
                        this.performConnectivityTest($card, workerType, null, () => {
                            testsCompleted++;
                            if (testsCompleted === totalTests) {
                                this.setLoadingState($button, false, 'Test All Connectivity');
                                this.showNotification('success', `Completed connectivity tests for ${totalTests} workers`);
                            }
                        });
                    }, index * 500); // Stagger tests to avoid rate limiting
                }
            });
        }

        /**
         * Perform connectivity test
         */
        performConnectivityTest($card, workerType, $button, callback) {
            const testId = `${workerType}_${Date.now()}`;
            this.currentTests.set(testId, true);
            
            // Update UI for testing state
            if ($card) {
                this.updateConnectivityStatus($card, 'testing', 'Testing connectivity...', '');
                this.updateRealtimeModelInfo($card, 'loading');
            }
            
            if ($button) {
                this.setLoadingState($button, true, 'Testing...');
            }
            
            // Prepare AJAX data
            const ajaxData = {
                action: 'ai_faq_test_model_connectivity',
                worker_type: workerType,
                nonce: window.aiFaqModelsData?.nonce || ''
            };
            
            $.ajax({
                url: window.aiFaqModelsData?.apiEndpoint || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: ajaxData,
                timeout: 30000
            })
            .done((response) => {
                const notification = this.generateConnectivityNotification(response);
                
                if ($card) {
                    this.updateConnectivityStatus(
                        $card,
                        response.success ? 'connected' : 'failed',
                        notification.message,
                        notification.timestamp_relative,
                        response.data || response
                    );
                    
                    // Update real-time AI model information
                    if (response.success && response.data && response.data.ai_model_info) {
                        this.updateRealtimeModelInfo($card, 'success', response.data.ai_model_info);
                    } else {
                        // If connectivity test didn't return AI model info, try fetching it separately
                        this.fetchWorkerAIModelInfo($card, workerType);
                    }
                }
                
                // Show notification for standalone tests
                if (!$card || !$button) {
                    const type = response.success ? 'success' : 'error';
                    this.showNotification(type, notification.message);
                }
            })
            .fail((xhr, status, error) => {
                console.error('Connectivity test failed:', error);
                
                const errorMsg = `Connection test failed: ${error || 'Network error'}`;
                
                if ($card) {
                    this.updateConnectivityStatus($card, 'error', errorMsg, 'just now');
                    this.updateRealtimeModelInfo($card, 'error');
                } else {
                    this.showNotification('error', errorMsg);
                }
            })
            .always(() => {
                this.currentTests.delete(testId);
                
                if ($button) {
                    this.setLoadingState($button, false, 'Test Connectivity');
                }
                
                if (callback) {
                    callback();
                }
            });
        }

        /**
         * Generate connectivity notification
         */
        generateConnectivityNotification(result) {
            const data = result.data || result;
            const status = data.status || (result.success ? 'connected' : 'failed');
            
            let message = data.message || 'Test completed';
            let timestampRelative = 'just now';
            
            if (data.response_time_ms) {
                message += ` (${data.response_time_ms}ms)`;
            }
            
            if (data.timestamp) {
                const testTime = new Date(data.timestamp);
                const now = new Date();
                const diffMs = now - testTime;
                const diffSecs = Math.round(diffMs / 1000);
                
                if (diffSecs < 60) {
                    timestampRelative = `${diffSecs} seconds ago`;
                } else {
                    timestampRelative = `${Math.round(diffSecs / 60)} minutes ago`;
                }
            }
            
            return {
                status: status,
                message: message,
                timestamp_relative: timestampRelative,
                badge_class: status === 'connected' ? 'status-success' : 'status-error',
                badge_text: status === 'connected' ? 'Connected' : 'Failed',
                icon: status === 'connected' ? 'yes-alt' : 'dismiss'
            };
        }

        /**
         * Update connectivity status display
         */
        updateConnectivityStatus($card, status, message, timeText, data) {
            if (!$card || !$card.length) return;
            
            const $statusDiv = $card.find('.connectivity-status');
            const $indicator = $statusDiv.find('.status-indicator');
            const $icon = $indicator.find('.status-icon');
            const $text = $indicator.find('.status-text');
            const $time = $indicator.find('.status-time');
            
            // Remove old status classes from both indicator and status div
            $indicator.removeClass('pending connected failed error testing');
            $statusDiv.removeClass('connected failed error testing');
            
            // Add new status class to both
            $indicator.addClass(status);
            $statusDiv.addClass(status);
            
            // Update icon
            $icon.removeClass('dashicons-update dashicons-yes-alt dashicons-dismiss dashicons-warning dashicons-networking');
            switch (status) {
                case 'connected':
                    $icon.addClass('dashicons-yes-alt');
                    break;
                case 'failed':
                case 'error':
                    $icon.addClass('dashicons-dismiss');
                    break;
                case 'testing':
                    $icon.addClass('dashicons-update');
                    break;
                default:
                    $icon.addClass('dashicons-networking');
            }
            
            // Update text and time with enhanced formatting
            $text.text(message);
            
            if (timeText && data && data.response_time_ms) {
                $time.html(`${timeText} &bull; <strong>${data.response_time_ms}ms</strong>`);
            } else if (timeText) {
                $time.text(timeText);
            } else {
                $time.empty();
            }
            
            // Add fade-in animation for new status
            $statusDiv.addClass('fadeInUp');
            setTimeout(() => {
                $statusDiv.removeClass('fadeInUp');
            }, 500);
            
            // Update card border color based on status
            $card.removeClass('status-connected status-failed status-error status-testing');
            $card.addClass(`status-${status}`);
        }

        /**
         * Update real-time AI model information display
         */
        updateRealtimeModelInfo($card, status, aiModelInfo = null) {
            if (!$card || !$card.length) return;
            
            const $realtimeSection = $card.find('.realtime-model-info');
            const $modelNameDisplay = $realtimeSection.find('.model-name-display');
            const $modelSourceBadge = $realtimeSection.find('.model-source-badge');
            const $sourceText = $modelSourceBadge.find('.source-text');
            const $sourceDescription = $realtimeSection.find('.source-description');
            
            switch (status) {
                case 'loading':
                    $realtimeSection.show();
                    $modelNameDisplay.text('Fetching model info...');
                    $modelSourceBadge.removeClass('kv_config env_fallback hardcoded_default unknown error')
                                    .addClass('loading');
                    $sourceText.text('Loading');
                    $sourceDescription.text('Checking worker health endpoint for current AI model configuration...');
                    break;
                    
                case 'success':
                    if (aiModelInfo) {
                        $realtimeSection.show();
                        
                        // Update model name with display name if available
                        const displayName = aiModelInfo.model_display_name || aiModelInfo.current_model || 'Unknown Model';
                        $modelNameDisplay.text(displayName);
                        
                        // Update model source badge
                        $modelSourceBadge.removeClass('kv_config env_fallback hardcoded_default unknown error loading')
                                        .addClass(aiModelInfo.model_source || 'unknown');
                        
                        const sourceTexts = {
                            'kv_config': 'KV Config',
                            'env_fallback': 'Environment',
                            'hardcoded_default': 'Default',
                            'unknown': 'Unknown'
                        };
                        $sourceText.text(sourceTexts[aiModelInfo.model_source] || 'Unknown');
                        
                        // Update source description
                        const sourceDescriptions = {
                            'kv_config': 'Model configured via Cloudflare KV storage - user-defined configuration.',
                            'env_fallback': 'Model determined from environment variables - fallback configuration.',
                            'hardcoded_default': 'Model using built-in default value - no custom configuration found.',
                            'unknown': 'Model source could not be determined from worker response.'
                        };
                        $sourceDescription.text(sourceDescriptions[aiModelInfo.model_source] || 'Model source information unavailable.');
                    }
                    break;
                    
                case 'failed':
                    $realtimeSection.show();
                    $modelNameDisplay.text('Failed to fetch model info');
                    $modelSourceBadge.removeClass('kv_config env_fallback hardcoded_default unknown loading')
                                    .addClass('error');
                    $sourceText.text('Error');
                    $sourceDescription.text('Unable to fetch AI model information from worker health endpoint. The worker may be unavailable or not responding.');
                    break;
                    
                case 'error':
                    $realtimeSection.show();
                    $modelNameDisplay.text('Connection Error');
                    $modelSourceBadge.removeClass('kv_config env_fallback hardcoded_default unknown loading')
                                    .addClass('error');
                    $sourceText.text('Error');
                    $sourceDescription.text('Failed to connect to worker health endpoint. Please check worker connectivity and try again.');
                    break;
                    
                default:
                    $realtimeSection.hide();
            }
        }

        /**
         * Handle keyboard shortcuts
         */
        handleKeyboardShortcuts(e) {
            // Escape to clear any focused inputs
            if (e.key === 'Escape') {
                $('input:focus, button:focus').blur();
            }
        }

        /**
         * Set loading state for buttons
         */
        setLoadingState($button, loading, text) {
            if (loading) {
                $button.prop('disabled', true)
                       .addClass('loading')
                       .data('original-text', $button.text())
                       .text(text || 'Loading...');
            } else {
                $button.prop('disabled', false)
                       .removeClass('loading')
                       .text($button.data('original-text') || text || 'Button');
            }
        }

        /**
         * Show notification
         */
        showNotification(type, message) {
            // Remove existing notifications
            $('.ai-models-notification').fadeOut(() => {
                $(this).remove();
            });
            
            // Create new notification
            const $notification = $(`
                <div class="ai-models-notification ${type}">
                    ${message}
                </div>
            `);
            
            // Insert at top of container
            this.$container.prepend($notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                $notification.fadeOut(() => {
                    $notification.remove();
                });
            }, 5000);
            
            // Scroll to notification
            $('html, body').animate({
                scrollTop: $notification.offset().top - 100
            }, 300);
        }

        /**
         * Format worker name for display
         */
        formatWorkerName(workerType) {
            const names = {
                'question_generator': 'Question Generator',
                'answer_generator': 'Answer Generator',
                'faq_enhancer': 'FAQ Enhancer',
                'seo_analyzer': 'SEO Analyzer',
                'topic_generator': 'Topic Generator'
            };
            return names[workerType] || workerType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
    }

    /**
     * Initialize when document is ready
     */
    $(document).ready(() => {
        // Only initialize on AI Models status page
        if ($('body').hasClass('ai-faq-generator_page_ai-faq-generator-ai-models') || 
            $('.ai-faq-gen-ai-models.modern-layout').length) {
            new AIModelsStatus();
        }
    });

})(jQuery);