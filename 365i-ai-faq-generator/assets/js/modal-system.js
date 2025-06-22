/**
 * Modal System for AI Models Interface
 * 
 * Handles modal creation, management, and interactions for the AI model
 * selection interface including model details, comparison, and selection workflows.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Admin
 * @since 2.5.0
 */

(function($) {
    'use strict';

    /**
     * Modal System Class
     */
    class AIModelsModalSystem {
        constructor() {
            this.currentModal = null;
            this.modals = new Map();
            this.loadingStates = new Map();
            this.modalCounter = 0;
            
            this.init();
        }

        /**
         * Initialize the modal system
         */
        init() {
            this.createModalContainer();
            this.bindEvents();
            this.initializeKeyboardHandlers();
        }

        /**
         * Create the main modal container
         */
        createModalContainer() {
            if ($('#ai-models-modal-container').length) {
                return; // Already exists
            }

            const modalContainer = $(`
                <div id="ai-models-modal-container" class="ai-models-modal-overlay" style="display: none;">
                    <div class="ai-models-modal-backdrop"></div>
                    <div class="ai-models-modal-wrapper">
                        <div class="ai-models-modal-content">
                            <!-- Modal content will be dynamically inserted here -->
                        </div>
                    </div>
                </div>
            `);

            $('body').append(modalContainer);
        }

        /**
         * Bind global events
         */
        bindEvents() {
            // Close modal when clicking backdrop
            $(document).on('click', '.ai-models-modal-backdrop', this.closeCurrentModal.bind(this));
            
            // Close modal when clicking close button
            $(document).on('click', '.ai-models-modal-close', this.closeCurrentModal.bind(this));
            
            // Handle model detail buttons
            $(document).on('click', '.view-model-details', this.handleViewModelDetails.bind(this));
            
            // Handle model selection from modal
            $(document).on('click', '.select-model-from-modal', this.handleSelectModelFromModal.bind(this));
            
            // Handle compare model buttons
            $(document).on('click', '.compare-model', this.handleCompareModel.bind(this));
            
            // Modal tab navigation
            $(document).on('click', '.modal-tab-nav button', this.handleTabSwitch.bind(this));
            
            // Prevent modal content clicks from closing modal
            $(document).on('click', '.ai-models-modal-content', function(e) {
                e.stopPropagation();
            });
        }

        /**
         * Initialize keyboard handlers
         */
        initializeKeyboardHandlers() {
            $(document).on('keydown', (e) => {
                if (this.currentModal) {
                    switch(e.key) {
                        case 'Escape':
                            e.preventDefault();
                            this.closeCurrentModal();
                            break;
                        case 'Tab':
                            this.handleTabNavigation(e);
                            break;
                    }
                }
            });
        }

        /**
         * Handle tab navigation within modal
         */
        handleTabNavigation(e) {
            const $modal = $('#ai-models-modal-container');
            if (!$modal.is(':visible')) return;

            const $focusableElements = $modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            const $firstElement = $focusableElements.first();
            const $lastElement = $focusableElements.last();

            if (e.shiftKey) {
                if (document.activeElement === $firstElement[0]) {
                    e.preventDefault();
                    $lastElement.focus();
                }
            } else {
                if (document.activeElement === $lastElement[0]) {
                    e.preventDefault();
                    $firstElement.focus();
                }
            }
        }

        /**
         * Show modal with specified content
         */
        showModal(content, options = {}) {
            const modalId = 'modal_' + (++this.modalCounter);
            
            const defaultOptions = {
                title: '',
                size: 'large',
                showCloseButton: true,
                showFooter: true,
                animation: 'fadeIn',
                focusOnOpen: true,
                closeOnBackdrop: true,
                modalClass: ''
            };

            const config = Object.assign({}, defaultOptions, options);
            
            // Build modal HTML
            const modalHTML = this.buildModalHTML(content, config);
            
            // Insert content into modal
            const $container = $('#ai-models-modal-container');
            const $content = $container.find('.ai-models-modal-content');
            
            $content.html(modalHTML);
            
            // Apply modal class if specified
            if (config.modalClass) {
                $container.addClass(config.modalClass);
            }
            
            // Show modal with animation
            this.animateModalIn($container, config.animation);
            
            // Store modal reference
            this.currentModal = modalId;
            this.modals.set(modalId, { $element: $container, config: config });
            
            // Focus management
            if (config.focusOnOpen) {
                setTimeout(() => {
                    const $firstFocusable = $content.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])').first();
                    if ($firstFocusable.length) {
                        $firstFocusable.focus();
                    }
                }, 150);
            }
            
            // Prevent body scroll
            $('body').addClass('ai-models-modal-open');
            
            return modalId;
        }

        /**
         * Build modal HTML structure
         */
        buildModalHTML(content, config) {
            let modalHTML = `<div class="ai-models-modal-dialog ${config.size}">`;
            
            // Header
            if (config.title || config.showCloseButton) {
                modalHTML += '<div class="ai-models-modal-header">';
                
                if (config.title) {
                    modalHTML += `<h2 class="ai-models-modal-title">${this.escapeHtml(config.title)}</h2>`;
                }
                
                if (config.showCloseButton) {
                    modalHTML += `
                        <button type="button" class="ai-models-modal-close" aria-label="Close Modal">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    `;
                }
                
                modalHTML += '</div>';
            }
            
            // Body
            modalHTML += '<div class="ai-models-modal-body">';
            modalHTML += content;
            modalHTML += '</div>';
            
            // Footer (if needed)
            if (config.showFooter && config.footerContent) {
                modalHTML += '<div class="ai-models-modal-footer">';
                modalHTML += config.footerContent;
                modalHTML += '</div>';
            }
            
            modalHTML += '</div>';
            
            return modalHTML;
        }

        /**
         * Animate modal in
         */
        animateModalIn($container, animation) {
            $container.show();
            
            switch(animation) {
                case 'fadeIn':
                    $container.css({ opacity: 0 }).animate({ opacity: 1 }, 250);
                    $container.find('.ai-models-modal-dialog').css({
                        transform: 'scale(0.9) translateY(-50px)',
                        opacity: 0
                    }).animate({
                        opacity: 1
                    }, 250).css({
                        transform: 'scale(1) translateY(0)',
                        transition: 'transform 250ms ease-out'
                    });
                    break;
                    
                case 'slideDown':
                    $container.css({ opacity: 0 }).animate({ opacity: 1 }, 200);
                    $container.find('.ai-models-modal-dialog').css({
                        transform: 'translateY(-100%)'
                    }).animate({
                        transform: 'translateY(0)'
                    }, 300);
                    break;
                    
                default:
                    $container.fadeIn(250);
            }
        }

        /**
         * Close the current modal
         */
        closeCurrentModal() {
            if (!this.currentModal) return;

            const modalData = this.modals.get(this.currentModal);
            if (!modalData) return;

            const $container = modalData.$element;
            
            // Animate out
            $container.fadeOut(200, () => {
                // Remove modal class if it was added
                if (modalData.config.modalClass) {
                    $container.removeClass(modalData.config.modalClass);
                }
                
                // Clear content
                $container.find('.ai-models-modal-content').empty();
                
                // Allow body scroll
                $('body').removeClass('ai-models-modal-open');
            });
            
            // Clean up references
            this.modals.delete(this.currentModal);
            this.currentModal = null;
        }

        /**
         * Handle view model details button click
         */
        handleViewModelDetails(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const modelId = $button.data('model-id');
            const workerType = $button.data('worker-type') || '';
            
            if (!modelId) {
                this.showErrorModal('Model ID not found.');
                return;
            }
            
            this.showModelDetailsModal(modelId, workerType);
        }

        /**
         * Show model details modal
         */
        showModelDetailsModal(modelId, workerType = '') {
            // Show loading modal first
            const loadingContent = `
                <div class="modal-loading-container">
                    <div class="modal-loading-spinner">
                        <div class="spinner"></div>
                    </div>
                    <p>Loading model details...</p>
                </div>
            `;
            
            const modalId = this.showModal(loadingContent, {
                title: 'Model Details',
                size: 'large',
                modalClass: 'model-details-modal',
                showFooter: false
            });
            
            // Fetch model details via AJAX
            this.fetchModelDetails(modelId, workerType)
                .then((response) => {
                    if (response.success) {
                        this.updateModalContent(this.buildModelDetailsContent(response.data.model, workerType));
                    } else {
                        this.updateModalContent(this.buildErrorContent('Failed to load model details: ' + response.data));
                    }
                })
                .catch((error) => {
                    console.error('Error loading model details:', error);
                    this.updateModalContent(this.buildErrorContent('Network error while loading model details.'));
                });
        }

        /**
         * Fetch model details via AJAX
         */
        fetchModelDetails(modelId, workerType) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: window.ajaxurl || '/wp-admin/admin-ajax.php',
                    type: 'POST',
                    data: {
                        action: 'ai_faq_get_model_details',
                        model_id: modelId,
                        worker_type: workerType,
                        nonce: window.aiFaqAjax?.nonce || ''
                    },
                    timeout: 15000
                })
                .done(resolve)
                .fail(reject);
            });
        }

        /**
         * Build model details content
         */
        buildModelDetailsContent(model, workerType) {
            if (!model) {
                return this.buildErrorContent('No model data available.');
            }

            let content = '<div class="model-details-content">';
            
            // Model header
            content += '<div class="model-header">';
            content += `<div class="model-title-section">`;
            content += `<h3 class="model-name">${this.escapeHtml(model.name)}</h3>`;
            content += `<span class="provider-badge ${this.escapeHtml(model.provider.toLowerCase())}">${this.escapeHtml(model.provider)}</span>`;
            content += `</div>`;
            content += `<div class="model-id-section">`;
            content += `<span class="model-id">${this.escapeHtml(model.id)}</span>`;
            content += `</div>`;
            content += '</div>';

            // Tabbed content
            content += '<div class="modal-tabs-container">';
            content += '<nav class="modal-tab-nav" role="tablist">';
            content += '<button type="button" class="tab-button active" data-tab="overview" role="tab">Overview</button>';
            content += '<button type="button" class="tab-button" data-tab="performance" role="tab">Performance</button>';
            content += '<button type="button" class="tab-button" data-tab="capabilities" role="tab">Capabilities</button>';
            content += '<button type="button" class="tab-button" data-tab="technical" role="tab">Technical</button>';
            content += '</nav>';

            // Tab content
            content += '<div class="modal-tab-content">';
            
            // Overview tab
            content += '<div class="tab-pane active" id="overview-tab">';
            content += `<div class="model-description">`;
            content += `<p>${this.escapeHtml(model.description || 'No description available.')}</p>`;
            content += `</div>`;
            
            if (model.use_cases && model.use_cases.length > 0) {
                content += `<div class="use-cases-section">`;
                content += `<h4>Best Use Cases</h4>`;
                content += `<ul class="use-cases-list">`;
                model.use_cases.forEach(useCase => {
                    content += `<li>${this.escapeHtml(useCase)}</li>`;
                });
                content += `</ul>`;
                content += `</div>`;
            }

            if (model.best_for && model.best_for.length > 0) {
                content += `<div class="worker-compatibility-section">`;
                content += `<h4>Compatible Workers</h4>`;
                content += `<div class="worker-tags">`;
                model.best_for.forEach(worker => {
                    content += `<span class="worker-tag">${this.escapeHtml(this.formatWorkerName(worker))}</span>`;
                });
                content += `</div>`;
                content += `</div>`;
            }
            content += '</div>';

            // Performance tab
            content += '<div class="tab-pane" id="performance-tab">';
            if (model.performance) {
                content += `<div class="performance-metrics-grid">`;
                Object.entries(model.performance).forEach(([metric, value]) => {
                    content += `<div class="performance-metric">`;
                    content += `<div class="metric-label">${this.escapeHtml(this.formatMetricName(metric))}</div>`;
                    content += `<div class="metric-value ${metric}-${value}">${this.escapeHtml(value)}</div>`;
                    content += `</div>`;
                });
                content += `</div>`;
            }
            
            if (model.pricing_tier && model.pricing_tier !== 'unknown') {
                content += `<div class="pricing-section">`;
                content += `<h4>Pricing Information</h4>`;
                content += `<div class="pricing-tier ${model.pricing_tier}">`;
                content += `<span class="tier-label">${this.escapeHtml(model.pricing_tier.charAt(0).toUpperCase() + model.pricing_tier.slice(1))} Tier</span>`;
                content += `</div>`;
                content += `</div>`;
            }
            content += '</div>';

            // Capabilities tab
            content += '<div class="tab-pane" id="capabilities-tab">';
            if (model.capabilities && model.capabilities.length > 0) {
                content += `<div class="capabilities-grid">`;
                model.capabilities.forEach(capability => {
                    content += `<div class="capability-item">`;
                    content += `<span class="capability-name">${this.escapeHtml(this.formatCapabilityName(capability))}</span>`;
                    content += `</div>`;
                });
                content += `</div>`;
            } else {
                content += `<p>No capability information available.</p>`;
            }
            content += '</div>';

            // Technical tab
            content += '<div class="tab-pane" id="technical-tab">';
            if (model.parameters) {
                content += `<div class="technical-parameters">`;
                content += `<h4>Model Parameters</h4>`;
                content += `<div class="parameters-grid">`;
                Object.entries(model.parameters).forEach(([param, value]) => {
                    content += `<div class="parameter-item">`;
                    content += `<span class="param-name">${this.escapeHtml(this.formatParameterName(param))}</span>`;
                    content += `<span class="param-value">${this.escapeHtml(value)}</span>`;
                    content += `</div>`;
                });
                content += `</div>`;
                content += `</div>`;
            }
            content += '</div>';

            content += '</div>'; // Close tab-content
            content += '</div>'; // Close tabs-container

            // Modal footer with actions
            content += '<div class="modal-actions">';
            content += `<button type="button" class="button button-primary select-model-from-modal" data-model-id="${this.escapeHtml(model.id)}" data-worker-type="${this.escapeHtml(workerType)}">`;
            content += `<span class="dashicons dashicons-yes"></span> Select This Model`;
            content += `</button>`;
            content += `<button type="button" class="button button-secondary compare-model" data-model-id="${this.escapeHtml(model.id)}">`;
            content += `<span class="dashicons dashicons-chart-bar"></span> Compare`;
            content += `</button>`;
            content += `<button type="button" class="button ai-models-modal-close">`;
            content += `Cancel`;
            content += `</button>`;
            content += '</div>';

            content += '</div>';

            return content;
        }

        /**
         * Build error content
         */
        buildErrorContent(message) {
            return `
                <div class="modal-error-content">
                    <div class="error-icon">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="error-message">
                        <h4>Error</h4>
                        <p>${this.escapeHtml(message)}</p>
                    </div>
                    <div class="error-actions">
                        <button type="button" class="button ai-models-modal-close">Close</button>
                    </div>
                </div>
            `;
        }

        /**
         * Update current modal content
         */
        updateModalContent(newContent) {
            if (!this.currentModal) return;

            const $container = $('#ai-models-modal-container');
            const $body = $container.find('.ai-models-modal-body');
            
            // Fade out current content
            $body.fadeOut(150, () => {
                // Update content
                $body.html(newContent);
                // Fade in new content
                $body.fadeIn(150);
            });
        }

        /**
         * Handle tab switching
         */
        handleTabSwitch(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const targetTab = $button.data('tab');
            
            // Update active tab button
            $button.siblings().removeClass('active');
            $button.addClass('active');
            
            // Update active tab content
            const $container = $button.closest('.modal-tabs-container');
            $container.find('.tab-pane').removeClass('active');
            $container.find(`#${targetTab}-tab`).addClass('active');
        }

        /**
         * Handle model selection from modal
         */
        handleSelectModelFromModal(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const modelId = $button.data('model-id');
            const workerType = $button.data('worker-type');
            
            if (!modelId) {
                console.error('No model ID specified for selection');
                return;
            }
            
            // Find the appropriate worker card and update its selection
            this.updateWorkerModelSelection(modelId, workerType);
            
            // Close the modal
            this.closeCurrentModal();
            
            // Show success notification
            this.showSuccessNotification(`Model ${modelId} selected for ${this.formatWorkerName(workerType)}`);
        }

        /**
         * Update worker model selection
         */
        updateWorkerModelSelection(modelId, workerType) {
            if (!workerType) {
                console.error('No worker type specified for model selection');
                return;
            }
            
            const $workerCard = $(`.worker-model-card[data-worker="${workerType}"]`);
            if ($workerCard.length === 0) {
                console.error(`Worker card not found for type: ${workerType}`);
                return;
            }
            
            const $selector = $workerCard.find('.model-selector');
            if ($selector.length === 0) {
                console.error(`Model selector not found in worker card: ${workerType}`);
                return;
            }
            
            // Update the select value
            $selector.val(modelId);
            
            // Trigger change event to update UI
            $selector.trigger('change');
        }

        /**
         * Handle compare model functionality
         */
        handleCompareModel(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const modelId = $button.data('model-id');
            
            // TODO: Implement model comparison functionality
            console.log('Compare model:', modelId);
            
            // For now, show a placeholder message
            this.showInfoNotification('Model comparison feature coming soon!');
        }

        /**
         * Show success notification
         */
        showSuccessNotification(message) {
            this.showNotification(message, 'success');
        }

        /**
         * Show error notification
         */
        showErrorNotification(message) {
            this.showNotification(message, 'error');
        }

        /**
         * Show info notification
         */
        showInfoNotification(message) {
            this.showNotification(message, 'info');
        }

        /**
         * Show notification
         */
        showNotification(message, type = 'info') {
            const $notification = $(`
                <div class="ai-models-notification ${type}">
                    ${this.escapeHtml(message)}
                </div>
            `);
            
            // Insert at top of page
            $('.ai-faq-gen-ai-models').prepend($notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                $notification.fadeOut(() => {
                    $notification.remove();
                });
            }, 5000);
        }

        /**
         * Show error modal
         */
        showErrorModal(message) {
            this.showModal(this.buildErrorContent(message), {
                title: 'Error',
                size: 'medium',
                modalClass: 'error-modal',
                showFooter: false
            });
        }

        /**
         * Utility functions
         */
        escapeHtml(text) {
            if (typeof text !== 'string') return text;
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

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

        formatMetricName(metric) {
            return metric.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        formatCapabilityName(capability) {
            return capability.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        formatParameterName(param) {
            return param.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
    }

    /**
     * Initialize modal system when document is ready
     */
    $(document).ready(() => {
        // Only initialize on AI Models admin page
        if ($('body').hasClass('ai-faq-generator_page_ai-faq-generator-ai-models') || 
            $('.ai-faq-gen-ai-models.modern-layout').length) {
            
            window.aiModelsModalSystem = new AIModelsModalSystem();
        }
    });

})(jQuery);