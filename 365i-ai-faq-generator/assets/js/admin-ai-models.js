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
            console.log('[365i AI FAQ] AIModelsStatus constructor called');
            this.workers = {};
            this.currentTests = new Map();
            this.notifications = [];
            
            this.init();
        }

        /**
         * Initialize the AI Models status interface
         */
        init() {
            console.log('[365i AI FAQ] AIModelsStatus init() called');
            this.cacheElements();
            this.bindEvents();
            this.loadWorkersData();
            this.initializeTooltips();
            this.checkForTestAllVisibility();
            this.loadInitialAIModelInfo();
            this.bindModelInputEvents();
            console.log('[365i AI FAQ] AIModelsStatus initialization complete');
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
            
            // Model input and change functionality
            $(document).on('input', '.model-input', this.handleModelInput.bind(this));
            $(document).on('click', '.change-model-btn', this.handleModelChange.bind(this));
            $(document).on('click', '.reset-model-btn', this.handleModelReset.bind(this));
            
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
         * Bind events for model input fields
         */
        bindModelInputEvents() {
            // Enable all model input fields on page load
            $('.model-input').prop('disabled', false);
        }

        /**
         * Handle model input change
         */
        handleModelInput(e) {
            const $input = $(e.target);
            const $card = $input.closest('.worker-model-card');
            const $changeBtn = $card.find('.change-model-btn');
            let inputValue = $input.val().trim();
            
            // Auto-correct backslashes to forward slashes for better UX
            if (inputValue.includes('\\')) {
                inputValue = inputValue.replace(/\\/g, '/');
                $input.val(inputValue);
            }
            
            // Basic validation - check if input looks like a valid model ID
            const isValidModel = this.validateModelId(inputValue);
            
            if (inputValue && isValidModel) {
                // Enable change button
                $changeBtn.prop('disabled', false);
                $input.removeClass('invalid');
            } else {
                // Disable change button
                $changeBtn.prop('disabled', true);
                
                // Add visual feedback for invalid input
                if (inputValue && !isValidModel) {
                    $input.addClass('invalid');
                } else {
                    $input.removeClass('invalid');
                }
            }
        }

        /**
         * Validate model ID format
         */
        validateModelId(modelId) {
            if (!modelId || typeof modelId !== 'string') {
                return false;
            }
            
            // Normalize backslashes to forward slashes for validation
            const normalizedModelId = modelId.replace(/\\/g, '/');
            
            // Check for basic Cloudflare Workers AI model format
            // Should start with @cf/ and have reasonable structure
            const modelPattern = /^@cf\/[a-zA-Z0-9\-_]+\/[a-zA-Z0-9\-_.]+$/;
            return modelPattern.test(normalizedModelId);
        }

        /**
         * Handle model change button click
         */
        handleModelChange(e) {
            e.preventDefault();
            
            const $button = $(e.target).closest('.change-model-btn');
            const $card = $button.closest('.worker-model-card');
            const $input = $card.find('.model-input');
            const workerType = $button.data('worker');
            const newModelId = $input.val().trim();
            
            if (!workerType || !newModelId) {
                this.showNotification('warning', 'Please enter a model ID first');
                return;
            }
            
            // Validate the model ID format
            if (!this.validateModelId(newModelId)) {
                this.showNotification('error', 'Please enter a valid Cloudflare Workers AI model ID (e.g., @cf/meta/llama-3.3-70b-instruct-fp8-fast)');
                return;
            }
            
            // Confirm the change
            const workerName = this.formatWorkerName(workerType);
            
            if (!confirm(`Are you sure you want to change ${workerName} to use "${newModelId}"?\n\nThis will update the KV namespace and purge worker caches.`)) {
                return;
            }
            
            this.performModelChange($card, workerType, newModelId, $button, false);
        }

        /**
         * Handle model reset button click
         */
        handleModelReset(e) {
            e.preventDefault();
            
            const $button = $(e.target).closest('.reset-model-btn');
            const $card = $button.closest('.worker-model-card');
            const workerType = $button.data('worker');
            const defaultModel = $button.data('default-model');
            
            if (!workerType || !defaultModel) {
                this.showNotification('warning', 'Default model information not found');
                return;
            }
            
            // Confirm the reset
            const workerName = this.formatWorkerName(workerType);
            
            if (!confirm(`Are you sure you want to reset ${workerName} to the default model "${defaultModel}"?\n\nThis will update the KV namespace and purge worker caches.`)) {
                return;
            }
            
            this.performModelChange($card, workerType, defaultModel, $button, true);
        }

        /**
         * Perform the actual model change
         */
        performModelChange($card, workerType, newModelId, $button, isReset = false) {
            // Set loading state
            const loadingText = isReset ? 'Resetting...' : 'Changing...';
            this.setLoadingState($button, true, loadingText);
            
            // Prepare AJAX data
            const ajaxData = {
                action: 'ai_faq_change_worker_model',
                worker_type: workerType,
                model_id: newModelId,
                nonce: window.aiFaqModelsData?.nonce || ''
            };
            
            $.ajax({
                url: window.aiFaqModelsData?.apiEndpoint || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: ajaxData,
                timeout: 30000
            })
            .done((response) => {
                if (response.success) {
                    this.showNotification('success', response.data.message);
                    
                    // Update the current model display
                    this.updateRealtimeModelInfo($card, 'success', {
                        current_model: response.data.new_model_id,
                        model_display_name: response.data.model_display_name,
                        model_source: 'kv_config'
                    });
                    
                    // Reset the input
                    const $input = $card.find('.model-input');
                    $input.val('').removeClass('invalid');
                    $card.find('.change-model-btn').prop('disabled', true);
                    
                    // If cache was purged, show additional info
                    if (response.data.cache_purged) {
                        setTimeout(() => {
                            this.showNotification('info', 'Worker caches have been purged. Changes should take effect immediately.');
                        }, 2000);
                    }
                } else {
                    this.showNotification('error', response.data || 'Failed to change model');
                }
            })
            .fail((xhr, status, error) => {
                console.error('Model change failed:', error);
                this.showNotification('error', 'Failed to change model: ' + error);
            })
            .always(() => {
                const originalText = isReset ? 'Reset to Default' : 'Change Model';
                this.setLoadingState($button, false, originalText);
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
            console.log('[365i AI FAQ] Test connectivity button clicked');
            
            const $button = $(e.target).closest('.test-model-connectivity');
            const $card = $button.closest('.worker-model-card');
            const workerType = $card.data('worker');
            
            console.log('[365i AI FAQ] Button:', $button);
            console.log('[365i AI FAQ] Card:', $card);
            console.log('[365i AI FAQ] Worker type:', workerType);
            
            if (!workerType) {
                console.log('[365i AI FAQ] No worker type found');
                this.showNotification('warning', 'Worker type not found');
                return;
            }
            
            console.log('[365i AI FAQ] Starting connectivity test for:', workerType);
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
                        // Ensure model_source is properly mapped for connectivity test responses
                        const aiModelInfo = response.data.ai_model_info;
                        
                        // Debug logging for model info structure
                        console.log('[365i AI FAQ] AI model info from connectivity test:', aiModelInfo);
                        
                        // Normalize model_source for consistent display
                        if (aiModelInfo.model_source === 'kv_namespace_override' || aiModelInfo.model_source === 'kv_config') {
                            aiModelInfo.model_source = 'kv_config';
                        }
                        
                        this.updateRealtimeModelInfo($card, 'success', aiModelInfo);
                    } else {
                        // If connectivity test didn't return AI model info, try fetching it separately
                        console.log('[365i AI FAQ] No AI model info in connectivity response, fetching separately');
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
            console.log('[365i AI FAQ] updateConnectivityStatus called:', {status, message, timeText, data});
            
            if (!$card || !$card.length) {
                console.log('[365i AI FAQ] No card found for connectivity status update');
                return;
            }
            
            const $statusDiv = $card.find('.connectivity-status');
            const $indicator = $statusDiv.find('.status-indicator');
            const $icon = $indicator.find('.status-icon');
            const $text = $indicator.find('.status-text');
            const $time = $indicator.find('.status-time');
            
            console.log('[365i AI FAQ] Status elements found:', {
                statusDiv: $statusDiv.length,
                indicator: $indicator.length,
                icon: $icon.length,
                text: $text.length,
                time: $time.length
            });
            
            // Show the status div if it's hidden
            $statusDiv.show();
            
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
            
            console.log('[365i AI FAQ] Connectivity status updated successfully');
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
                        
                        console.log('[365i AI FAQ] Processing AI model info for display:', aiModelInfo);
                        
                        // Update model name with display name if available
                        const displayName = aiModelInfo.model_display_name || aiModelInfo.current_model || 'Unknown Model';
                        $modelNameDisplay.text(displayName);
                        
                        // Normalize and validate model_source - prioritize KV config display
                        let modelSource = aiModelInfo.model_source || 'kv_config';
                        
                        // If we have a valid model but no clear source, assume it's from KV config
                        if (aiModelInfo.current_model && (modelSource === 'unknown' || !modelSource)) {
                            modelSource = 'kv_config';
                            console.log('[365i AI FAQ] Defaulting to kv_config for model with valid current_model');
                        }
                        
                        // Handle various model source values that should map to kv_config
                        if (['kv_namespace_override', 'kv_namespace', 'configured', 'config'].includes(modelSource)) {
                            modelSource = 'kv_config';
                        }
                        
                        console.log('[365i AI FAQ] Final model source for display:', modelSource);
                        
                        // Update model source badge
                        $modelSourceBadge.removeClass('kv_config env_fallback hardcoded_default unknown error loading')
                                        .addClass(modelSource);
                        
                        const sourceTexts = {
                            'kv_config': 'KV Config',
                            'env_fallback': 'Environment',
                            'hardcoded_default': 'Default',
                            'unknown': 'Unknown'
                        };
                        $sourceText.text(sourceTexts[modelSource] || 'KV Config');
                        
                        // Update source description
                        const sourceDescriptions = {
                            'kv_config': 'Model configured via Cloudflare KV storage - user-defined configuration.',
                            'env_fallback': 'Model determined from environment variables - fallback configuration.',
                            'hardcoded_default': 'Model using built-in default value - no custom configuration found.',
                            'unknown': 'Model source could not be determined from worker response.'
                        };
                        $sourceDescription.text(sourceDescriptions[modelSource] || 'Model configured via Cloudflare KV storage - user-defined configuration.');
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
        console.log('[365i AI FAQ] AI Models JS loaded, checking for page elements...');
        
        // Debug page detection
        const bodyClasses = $('body').attr('class') || '';
        const hasModernLayout = $('.ai-faq-gen-ai-models.modern-layout').length;
        
        console.log('[365i AI FAQ] Body classes:', bodyClasses);
        console.log('[365i AI FAQ] Modern layout found:', hasModernLayout);
        console.log('[365i AI FAQ] aiFaqModelsData:', window.aiFaqModelsData);
        
        // Only initialize on AI Models status page
        if ($('body').hasClass('ai-faq-generator_page_ai-faq-generator-ai-models') ||
            $('body').hasClass('ai-faq-gen_page_ai-faq-generator-ai-models') ||
            $('.ai-faq-gen-ai-models.modern-layout').length) {
            console.log('[365i AI FAQ] Initializing AI Models Status...');
            new AIModelsStatus();
        } else {
            console.log('[365i AI FAQ] Not on AI Models page, skipping initialization');
        }
    });

})(jQuery);