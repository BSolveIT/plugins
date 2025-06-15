/**
 * FAQ AI Generator - Main Application JavaScript
 *
 * Handles all frontend functionality including:
 * - Quill editor integration
 * - Drag and drop sorting with Sortable.js
 * - AI suggestion handling
 * - Schema generation
 * - Local storage persistence
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/public/js
 */

(function($) {
    'use strict';

    // Global application state
    const FAQApp = {
        // Configuration
        config: {
            autoSaveInterval: 3, // Seconds
            defaultAnchorFormat: 'question',
            pageUrl: '',
            defaultTheme: 'default',
            maxFaqCount: 100,
            debug: false
        },
        
        // Application state
        state: {
            faqs: [],
            activeTab: 'editor',
            lastSaved: null,
            dirty: false,
            nextId: 1,
            websiteContext: '',
            hasLoadedFromStorage: false,
            generators: {
                question: {
                    loading: false,
                    activeFaq: null
                },
                answer: {
                    loading: false,
                    activeFaq: null
                },
                seo: {
                    loading: false,
                    activeFaq: null,
                    score: 0
                }
            }
        },
        
        // Editor instances
        editors: {
            questions: {},
            answers: {}
        },
        
        // Sortable instance
        sortable: null,
        
        // Timer references
        timers: {
            autoSave: null,
            typingDebounce: null
        },
        
        // DOM element references
        elements: {},
        
        // Initialize application
        init: function() {
            console.log('FAQ AI Generator: Initializing application...');
            
            // Load configuration
            this.loadConfig();
            
            // Cache DOM elements
            this.cacheElements();
            
            // Setup event listeners
            this.setupEventListeners();
            
            // Initialize tabs
            this.initTabs();
            
            // Initialize sortable
            this.initSortable();
            
            // Attempt to load FAQs from localStorage
            this.loadFromStorage();
            
            // Setup auto-save
            this.setupAutoSave();
            
            // Initialize empty state if no FAQs
            this.updateEmptyState();
            
            // Gather website context for AI if none exists
            if (!this.state.websiteContext) {
                this.gatherWebsiteContext();
            }
            
            console.log('FAQ AI Generator: Initialization complete');
        },
        
        // Load configuration from data attributes and localized script
        loadConfig: function() {
            const $container = $('#faq-ai-generator');
            
            // Get config from data attributes
            this.config.defaultTheme = $container.data('theme') || 'default';
            this.config.maxFaqCount = parseInt($container.data('count'), 10) || 100;
            
            // Get config from localized script
            if (typeof faqAiData !== 'undefined') {
                this.config.autoSaveInterval = faqAiData.settings.auto_save_interval || 3;
                this.config.defaultAnchorFormat = faqAiData.settings.default_anchor_format || 'question';
                this.config.pageUrl = faqAiData.settings.faq_page_url || window.location.href;
                this.config.debug = faqAiData.settings.debug_mode || false;
            }
            
            if (this.config.debug) {
                console.log('FAQ AI Generator: Configuration loaded', this.config);
            }
        },
        
        // Cache frequently used DOM elements
        cacheElements: function() {
            this.elements = {
                container: $('#faq-ai-generator'),
                tabs: $('.faq-ai-tab'),
                tabPanels: $('.faq-ai-tab-panel'),
                faqList: $('#faq-ai-list'),
                emptyState: $('.faq-ai-empty-state'),
                addButton: $('#faq-ai-add-faq'),
                importButton: $('#faq-ai-import'),
                templatesButton: $('#faq-ai-templates'),
                fetchUrlButton: $('#faq-ai-fetch-url'),
                urlPanel: $('.faq-ai-url-import-panel'),
                urlInput: $('#faq-ai-url-input'),
                urlSubmit: $('#faq-ai-fetch-url-submit'),
                urlResults: $('#faq-ai-url-results'),
                urlClose: $('.faq-ai-url-close'),
                templatesPanel: $('.faq-ai-templates-panel'),
                templateCards: $('.faq-ai-template-card'),
                templatesClose: $('.faq-ai-templates-close'),
                schemaFormat: $('#faq-ai-schema-format'),
                baseUrl: $('#faq-ai-base-url'),
                generateSchema: $('#faq-ai-generate-schema'),
                copySchema: $('#faq-ai-copy-schema'),
                downloadSchema: $('#faq-ai-download-schema'),
                schemaOutput: $('#faq-ai-schema-output'),
                displayMode: $('#faq-ai-display-mode'),
                showNumbers: $('#faq-ai-show-numbers'),
                answerLength: $('#faq-ai-answer-length'),
                answerTone: $('#faq-ai-answer-tone'),
                exportJson: $('#faq-ai-export-json'),
                importJson: $('#faq-ai-import-json'),
                clearAll: $('#faq-ai-clear-all'),
                loadingOverlay: $('.faq-ai-loading-overlay'),
                loadingMessage: $('.faq-ai-loading-message'),
                itemTemplate: $('#faq-ai-item-template'),
                suggestionTemplate: $('#faq-ai-suggestion-template'),
                seoDisplay: $('.faq-ai-led-display')
            };
        },
        
        // Setup event listeners
        setupEventListeners: function() {
            // Tab switching
            this.elements.tabs.on('click', this.handleTabClick.bind(this));
            
            // Add new FAQ
            this.elements.addButton.on('click', this.addNewFaq.bind(this));
            
            // Import/Templates buttons
            this.elements.importButton.on('click', this.showImportPanel.bind(this));
            this.elements.templatesButton.on('click', this.showTemplatesPanel.bind(this));
            this.elements.fetchUrlButton.on('click', this.showUrlPanel.bind(this));
            
            // Close panels
            this.elements.urlClose.on('click', this.hideUrlPanel.bind(this));
            this.elements.templatesClose.on('click', this.hideTemplatesPanel.bind(this));
            
            // URL import
            this.elements.urlSubmit.on('click', this.fetchUrl.bind(this));
            
            // Template selection
            this.elements.templateCards.on('click', this.applyTemplate.bind(this));
            
            // Schema generation
            this.elements.generateSchema.on('click', this.generateSchema.bind(this));
            this.elements.copySchema.on('click', this.copySchema.bind(this));
            this.elements.downloadSchema.on('click', this.downloadSchema.bind(this));
            
            // Settings actions
            this.elements.displayMode.on('change', this.saveSettings.bind(this));
            this.elements.showNumbers.on('change', this.saveSettings.bind(this));
            this.elements.answerLength.on('change', this.saveSettings.bind(this));
            this.elements.answerTone.on('change', this.saveSettings.bind(this));
            
            // Export/Import JSON
            this.elements.exportJson.on('click', this.exportJson.bind(this));
            this.elements.importJson.on('click', this.importJson.bind(this));
            
            // Clear all FAQs
            this.elements.clearAll.on('click', this.clearAllFaqs.bind(this));
            
            // Dynamic event binding for FAQ items (using delegation)
            this.elements.faqList.on('click', '.faq-ai-toggle-item', this.toggleFaqItem.bind(this));
            this.elements.faqList.on('click', '.faq-ai-delete-item', this.deleteFaqItem.bind(this));
            this.elements.faqList.on('click', '.faq-ai-suggest-question', this.suggestQuestion.bind(this));
            this.elements.faqList.on('click', '.faq-ai-generate-answer', this.generateAnswer.bind(this));
            this.elements.faqList.on('click', '.faq-ai-enhance-answer', this.enhanceAnswer.bind(this));
            this.elements.faqList.on('click', '.faq-ai-analyze-seo', this.analyzeSeo.bind(this));
            this.elements.faqList.on('click', '.faq-ai-close-suggestions', this.closeSuggestions.bind(this));
            this.elements.faqList.on('click', '.faq-ai-apply-suggestion', this.applySuggestion.bind(this));
            this.elements.faqList.on('click', '.faq-ai-refresh-suggestions', this.refreshSuggestions.bind(this));
            this.elements.faqList.on('click', '.faq-ai-validate-question', this.validateQuestion.bind(this));
        },
        
        // Initialize tabs
        initTabs: function() {
            // Set initial active tab from URL hash if present
            const hash = window.location.hash.substring(1);
            if (hash && this.elements.tabPanels.filter('#faq-ai-' + hash + '-panel').length) {
                this.switchTab(hash);
            }
        },
        
        // Initialize sortable for drag and drop
        initSortable: function() {
            if (typeof Sortable !== 'undefined') {
                this.sortable = Sortable.create(this.elements.faqList[0], {
                    handle: '.faq-ai-drag-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: this.handleSortEnd.bind(this)
                });
            } else {
                console.warn('FAQ AI Generator: Sortable.js not loaded, drag and drop disabled');
            }
        },
        
        // Load FAQs from localStorage
        loadFromStorage: function() {
            try {
                const stored = localStorage.getItem('faq_ai_generator_data');
                if (stored) {
                    const data = JSON.parse(stored);
                    
                    // Load FAQs
                    if (data.faqs && Array.isArray(data.faqs)) {
                        this.state.faqs = data.faqs;
                        
                        // Find the highest ID to set nextId
                        let maxId = 0;
                        data.faqs.forEach(faq => {
                            const idNum = parseInt(faq.id.replace('faq-', ''), 10);
                            if (idNum > maxId) maxId = idNum;
                        });
                        this.state.nextId = maxId + 1;
                        
                        // Render FAQs
                        this.renderFaqs();
                    }
                    
                    // Load settings
                    if (data.settings) {
                        if (data.settings.displayMode) {
                            this.elements.displayMode.val(data.settings.displayMode);
                        }
                        if (data.settings.showNumbers !== undefined) {
                            this.elements.showNumbers.prop('checked', data.settings.showNumbers);
                        }
                        if (data.settings.answerLength) {
                            this.elements.answerLength.val(data.settings.answerLength);
                        }
                        if (data.settings.answerTone) {
                            this.elements.answerTone.val(data.settings.answerTone);
                        }
                    }
                    
                    // Load website context
                    if (data.websiteContext) {
                        this.state.websiteContext = data.websiteContext;
                    }
                    
                    this.state.lastSaved = data.timestamp || new Date().getTime();
                    this.state.hasLoadedFromStorage = true;
                    
                    if (this.config.debug) {
                        console.log('FAQ AI Generator: Loaded from localStorage', this.state.faqs.length + ' FAQs');
                    }
                }
            } catch (error) {
                console.error('FAQ AI Generator: Error loading from localStorage', error);
            }
        },
        
        // Save FAQs to localStorage
        saveToStorage: function() {
            try {
                // Update FAQ content from editors before saving
                this.updateFaqsFromEditors();
                
                // Gather settings
                const settings = {
                    displayMode: this.elements.displayMode.val(),
                    showNumbers: this.elements.showNumbers.prop('checked'),
                    answerLength: this.elements.answerLength.val(),
                    answerTone: this.elements.answerTone.val()
                };
                
                // Create storage object
                const storage = {
                    faqs: this.state.faqs,
                    settings: settings,
                    websiteContext: this.state.websiteContext,
                    timestamp: new Date().getTime()
                };
                
                // Save to localStorage
                localStorage.setItem('faq_ai_generator_data', JSON.stringify(storage));
                
                this.state.lastSaved = new Date().getTime();
                this.state.dirty = false;
                
                if (this.config.debug) {
                    console.log('FAQ AI Generator: Saved to localStorage', this.state.faqs.length + ' FAQs');
                }
            } catch (error) {
                console.error('FAQ AI Generator: Error saving to localStorage', error);
            }
        },
        
        // Setup auto-save
        setupAutoSave: function() {
            // Clear any existing timer
            if (this.timers.autoSave) {
                clearInterval(this.timers.autoSave);
            }
            
            // Set up new timer
            this.timers.autoSave = setInterval(() => {
                if (this.state.dirty) {
                    this.saveToStorage();
                }
            }, this.config.autoSaveInterval * 1000);
        },
        
        // Update empty state display
        updateEmptyState: function() {
            if (this.state.faqs.length === 0) {
                this.elements.emptyState.show();
            } else {
                this.elements.emptyState.hide();
            }
        },
        
        // Gather website context for AI
        gatherWebsiteContext: function() {
            // Check if we already have context
            if (this.state.websiteContext) {
                return;
            }
            
            // Get content from the current page
            const pageContent = $('body').text().trim();
            if (pageContent) {
                // Truncate to a reasonable size for context
                this.state.websiteContext = pageContent.substring(0, 2000);
                this.state.dirty = true;
                
                if (this.config.debug) {
                    console.log('FAQ AI Generator: Gathered website context', this.state.websiteContext.length + ' chars');
                }
            }
        },
        
        // Tab switching handler
        handleTabClick: function(event) {
            event.preventDefault();
            const tab = $(event.currentTarget).data('tab');
            this.switchTab(tab);
        },
        
        // Switch active tab
        switchTab: function(tab) {
            // Update tab buttons
            this.elements.tabs.removeClass('active');
            this.elements.tabs.filter(`[data-tab="${tab}"]`).addClass('active');
            
            // Update tab panels
            this.elements.tabPanels.removeClass('active');
            this.elements.tabPanels.filter(`#faq-ai-${tab}-panel`).addClass('active');
            
            // Update state
            this.state.activeTab = tab;
            
            // Special handling for tabs
            if (tab === 'export') {
                // Generate schema when switching to export tab
                this.generateSchema();
            }
        },
        
        // Add new FAQ handler
        addNewFaq: function() {
            // Check if we've reached the maximum number of FAQs
            if (this.state.faqs.length >= this.config.maxFaqCount) {
                alert(`Maximum of ${this.config.maxFaqCount} FAQs reached.`);
                return;
            }
            
            // Create new FAQ
            const newFaq = {
                id: 'faq-' + this.state.nextId++,
                question: faqAiData.strings.newQuestion || 'New question',
                answer: faqAiData.strings.newAnswer || 'Enter answer here...'
            };
            
            // Add to state
            this.state.faqs.push(newFaq);
            this.state.dirty = true;
            
            // Render the new FAQ
            this.renderFaq(newFaq);
            
            // Update empty state
            this.updateEmptyState();
            
            // Save to storage
            this.saveToStorage();
        },
        
        // Render all FAQs
        renderFaqs: function() {
            // Clear the list
            this.elements.faqList.empty();
            
            // Render each FAQ
            this.state.faqs.forEach(faq => {
                this.renderFaq(faq);
            });
            
            // Update empty state
            this.updateEmptyState();
        },
        
        // Render a single FAQ
        renderFaq: function(faq) {
            // Clone the template
            const $template = this.elements.itemTemplate.html();
            const $faqItem = $($template);
            
            // Set ID and data attributes
            $faqItem.attr('data-id', faq.id);
            
            // Add to the list
            this.elements.faqList.append($faqItem);
            
            // Initialize editors
            this.initFaqEditors(faq);
        },
        
        // Initialize Quill editors for a FAQ
        initFaqEditors: function(faq) {
            const $faqItem = this.elements.faqList.find(`[data-id="${faq.id}"]`);
            
            // Question editor
            const $questionEditor = $faqItem.find('.faq-ai-question-editor');
            if ($questionEditor.length && typeof Quill !== 'undefined') {
                const questionEditor = new Quill($questionEditor[0], {
                    theme: 'snow',
                    placeholder: 'Enter your question here...',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline'],
                            ['clean']
                        ]
                    }
                });
                
                // Set content
                questionEditor.root.innerHTML = faq.question;
                
                // Store editor reference
                this.editors.questions[faq.id] = questionEditor;
                
                // Add change handler
                questionEditor.on('text-change', this.handleEditorChange.bind(this));
            }
            
            // Answer editor
            const $answerEditor = $faqItem.find('.faq-ai-answer-editor');
            if ($answerEditor.length && typeof Quill !== 'undefined') {
                const answerEditor = new Quill($answerEditor[0], {
                    theme: 'snow',
                    placeholder: 'Enter your answer here...',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['link', 'blockquote', 'code-block'],
                            ['clean']
                        ]
                    }
                });
                
                // Set content
                answerEditor.root.innerHTML = faq.answer;
                
                // Store editor reference
                this.editors.answers[faq.id] = answerEditor;
                
                // Add change handler
                answerEditor.on('text-change', this.handleEditorChange.bind(this));
            }
        },
        
        // Handle editor content changes
        handleEditorChange: function() {
            this.state.dirty = true;
            
            // Debounce auto-save
            if (this.timers.typingDebounce) {
                clearTimeout(this.timers.typingDebounce);
            }
            
            this.timers.typingDebounce = setTimeout(() => {
                this.saveToStorage();
            }, 1000);
        },
        
        // Update FAQ data from editors
        updateFaqsFromEditors: function() {
            this.state.faqs.forEach(faq => {
                // Update question from editor
                const questionEditor = this.editors.questions[faq.id];
                if (questionEditor) {
                    faq.question = questionEditor.root.innerHTML;
                }
                
                // Update answer from editor
                const answerEditor = this.editors.answers[faq.id];
                if (answerEditor) {
                    faq.answer = answerEditor.root.innerHTML;
                }
            });
        },
        
        // Handle sort end (after drag and drop)
        handleSortEnd: function(event) {
            // Update the order of FAQs in state
            const newOrder = [];
            this.elements.faqList.children().each(function() {
                const id = $(this).data('id');
                const faq = FAQApp.state.faqs.find(f => f.id === id);
                if (faq) {
                    newOrder.push(faq);
                }
            });
            
            this.state.faqs = newOrder;
            this.state.dirty = true;
            this.saveToStorage();
        },
        
        // Toggle FAQ item open/closed
        toggleFaqItem: function(event) {
            const $button = $(event.currentTarget);
            const $item = $button.closest('.faq-ai-item');
            const $content = $item.find('.faq-ai-item-content');
            const $icon = $button.find('.faq-ai-icon');
            
            $content.slideToggle(200, function() {
                if ($content.is(':visible')) {
                    $icon.text('▼');
                } else {
                    $icon.text('▶');
                }
            });
        },
        
        // Delete FAQ item
        deleteFaqItem: function(event) {
            if (!confirm(faqAiData.strings.confirmDelete)) {
                return;
            }
            
            const $item = $(event.currentTarget).closest('.faq-ai-item');
            const id = $item.data('id');
            
            // Remove from state
            this.state.faqs = this.state.faqs.filter(faq => faq.id !== id);
            
            // Remove from DOM
            $item.fadeOut(300, function() {
                $(this).remove();
                
                // Clean up editors
                if (FAQApp.editors.questions[id]) {
                    delete FAQApp.editors.questions[id];
                }
                if (FAQApp.editors.answers[id]) {
                    delete FAQApp.editors.answers[id];
                }
                
                // Update empty state
                FAQApp.updateEmptyState();
            });
            
            this.state.dirty = true;
            this.saveToStorage();
        },
        
        // Show URL import panel
        showUrlPanel: function() {
            this.hideTemplatesPanel();
            this.elements.urlPanel.slideDown(300);
        },
        
        // Hide URL import panel
        hideUrlPanel: function() {
            this.elements.urlPanel.slideUp(300);
        },
        
        // Show templates panel
        showTemplatesPanel: function() {
            this.hideUrlPanel();
            this.elements.templatesPanel.slideDown(300);
        },
        
        // Hide templates panel
        hideTemplatesPanel: function() {
            this.elements.templatesPanel.slideUp(300);
        },
        
        // Fetch FAQs from URL
        fetchUrl: function() {
            const url = this.elements.urlInput.val().trim();
            
            if (!url) {
                alert('Please enter a valid URL');
                return;
            }
            
            // Show loading
            this.showLoading('Fetching content from URL...');
            
            // AJAX request to fetch URL content
            $.ajax({
                url: faqAiData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'faq_ai_fetch_url',
                    nonce: faqAiData.nonce,
                    url: url
                },
                success: (response) => {
                    this.hideLoading();
                    
                    if (response.success && response.data) {
                        // Display the results
                        this.displayUrlResults(response.data);
                    } else {
                        // Show error
                        this.elements.urlResults.html(`<div class="faq-ai-error">Error: ${response.data.message || 'Failed to fetch URL'}</div>`);
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.elements.urlResults.html('<div class="faq-ai-error">Error: Failed to fetch URL. Please try again.</div>');
                }
            });
        },
        
        // Display URL fetch results
        displayUrlResults: function(data) {
            if (data.faqs && data.faqs.length > 0) {
                let html = '<div class="faq-ai-url-results-header">';
                html += `<h4>${data.faqs.length} FAQs found on page</h4>`;
                html += `<button class="faq-ai-button primary faq-ai-import-url-faqs">Import All FAQs</button>`;
                html += '</div>';
                html += '<div class="faq-ai-url-results-list">';
                
                data.faqs.forEach((faq, index) => {
                    html += `<div class="faq-ai-url-result-item">`;
                    html += `<div class="faq-ai-url-result-question">${faq.question}</div>`;
                    html += `<div class="faq-ai-url-result-answer">${faq.answer}</div>`;
                    html += '</div>';
                });
                
                html += '</div>';
                
                this.elements.urlResults.html(html);
                
                // Add click handler for import button
                $('.faq-ai-import-url-faqs').on('click', () => {
                    this.importUrlFaqs(data.faqs);
                });
            } else if (data.content) {
                // No FAQs found but we have content, use it as context for AI
                this.state.websiteContext = data.content;
                this.state.dirty = true;
                this.saveToStorage();
                
                let html = '<div class="faq-ai-url-results-header">';
                html += '<h4>No FAQs found, but page content was captured for AI context</h4>';
                html += '</div>';
                html += '<div class="faq-ai-url-results-content">';
                html += '<p>Page content will be used to improve AI suggestions.</p>';
                html += '</div>';
                
                this.elements.urlResults.html(html);
            } else {
                // No content found
                this.elements.urlResults.html('<div class="faq-ai-error">No FAQs or content found on this page.</div>');
            }
        },
        
        // Import FAQs from URL results
        importUrlFaqs: function(faqs) {
            if (!faqs || faqs.length === 0) {
                return;
            }
            
            // Check if we'll exceed the maximum
            if (this.state.faqs.length + faqs.length > this.config.maxFaqCount) {
                alert(`Cannot import all FAQs. Maximum of ${this.config.maxFaqCount} FAQs allowed.`);
                return;
            }
            
            // Import each FAQ
            faqs.forEach(faq => {
                const newFaq = {
                    id: 'faq-' + this.state.nextId++,
                    question: faq.question,
                    answer: faq.answer
                };
                
                this.state.faqs.push(newFaq);
            });
            
            // Update UI
            this.renderFaqs();
            this.updateEmptyState();
            
            // Save changes
            this.state.dirty = true;
            this.saveToStorage();
            
            // Hide the panel
            this.hideUrlPanel();
            
            // Show success message
            alert(`Successfully imported ${faqs.length} FAQs`);
        },
        
        // Apply a template
        applyTemplate: function(event) {
            const templateType = $(event.currentTarget).data('template');
            
            // Show loading
            this.showLoading('Loading template...');
            
            // Simulate loading time (in a real app, you'd fetch templates from a server)
            setTimeout(() => {
                // Apply the selected template
                const templateFaqs = this.getTemplateFaqs(templateType);
                
                // Check if we'll exceed the maximum
                if (this.state.faqs.length + templateFaqs.length > this.config.maxFaqCount) {
                    alert(`Cannot import all template FAQs. Maximum of ${this.config.maxFaqCount} FAQs allowed.`);
                    this.hideLoading();
                    return;
                }
                
                // Import each FAQ
                templateFaqs.forEach(faq => {
                    const newFaq = {
                        id: 'faq-' + this.state.nextId++,
                        question: faq.question,
                        answer: faq.answer
                    };
                    
                    this.state.faqs.push(newFaq);
                });
                
                // Update UI
                this.renderFaqs();
                this.updateEmptyState();
                
                // Save changes
                this.state.dirty = true;
                this.saveToStorage();
                
                // Hide loading and panel
                this.hideLoading();
                this.hideTemplatesPanel();
                
                // Show success message
                alert(`Successfully applied ${templateType} template with ${templateFaqs.length} FAQs`);
            }, 500);
        },
        
        // Get template FAQs based on type
        getTemplateFaqs: function(type) {
            // Sample templates - in a real app, these would be fetched from the server
            const templates = {
                business: [
                    { question: 'What are your business hours?', answer: 'Our standard business hours are Monday to Friday from 9:00 AM to 5:00 PM. We are closed on weekends and major holidays.' },
                    { question: 'Do you offer refunds?', answer: 'Yes, we offer a 30-day money-back guarantee on all our products and services. Please contact our customer service team to initiate a refund.' },
                    { question: 'How can I contact customer support?', answer: 'You can reach our customer support team via email at support@example.com, by phone at (555) 123-4567, or through the chat feature on our website.' }
                ],
                product: [
                    { question: 'What is the warranty period for your products?', answer: 'All our products come with a 1-year standard warranty that covers manufacturing defects. Extended warranties are available for purchase.' },
                    { question: 'How do I return a product?', answer: 'To return a product, please fill out the return form available on our website, pack the item in its original packaging, and use the provided shipping label.' },
                    { question: 'Are your products environmentally friendly?', answer: 'Yes, we are committed to sustainability. Our products are designed with eco-friendly materials and our packaging is 100% recyclable.' }
                ],
                service: [
                    { question: 'What services do you offer?', answer: 'We offer a wide range of services including consultation, implementation, training, and ongoing support tailored to meet your specific needs.' },
                    { question: 'How much do your services cost?', answer: 'Our service pricing varies based on the scope and requirements of your project. We offer customized quotes after an initial consultation.' },
                    { question: 'Do you offer service packages?', answer: 'Yes, we offer several service packages designed to meet different needs and budgets. Contact us for details on our basic, standard, and premium packages.' }
                ],
                support: [
                    { question: 'How do I reset my password?', answer: 'To reset your password, click on the "Forgot Password" link on the login page and follow the instructions sent to your registered email address.' },
                    { question: 'Why is the system running slowly?', answer: 'Slow performance can be caused by various factors including high traffic, browser cache issues, or network problems. Try clearing your browser cache or connecting to a different network.' },
                    { question: 'How do I update to the latest version?', answer: 'Updates are automatically applied to your account. If you\'re using a desktop application, you\'ll be prompted to update when a new version is available.' }
                ]
            };
            
            return templates[type] || [];
        },
        
        // Save settings
        saveSettings: function() {
            this.state.dirty = true;
            this.saveToStorage();
        },
        
        // Generate schema
        generateSchema: function() {
            const format = this.elements.schemaFormat.val();
            const baseUrl = this.elements.baseUrl.val();
            
            // Update FAQs from editors before generating schema
            this.updateFaqsFromEditors();
            
            // Show loading
            this.showLoading('Generating schema...');
            
            // AJAX request to generate schema
            $.ajax({
                url: faqAiData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'faq_ai_generate_schema',
                    nonce: faqAiData.nonce,
                    faqs: this.state.faqs,
                    format: format,
                    baseUrl: baseUrl
                },
                success: (response) => {
                    this.hideLoading();
                    
                    if (response.success && response.data) {
                        // Display the schema
                        this.elements.schemaOutput.text(response.data.schema);
                    } else {
                        // Show error
                        this.elements.schemaOutput.text(`Error: ${response.data.message || 'Failed to generate schema'}`);
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.elements.schemaOutput.text('Error: Failed to generate schema. Please try again.');
                }
            });
        },
        
        // Copy schema to clipboard
        copySchema: function() {
            const schema = this.elements.schemaOutput.text();
            
            if (!schema) {
                alert('No schema to copy. Please generate schema first.');
                return;
            }
            
            // Create temporary textarea for copying
            const textarea = document.createElement('textarea');
            textarea.value = schema;
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                // Copy to clipboard
                document.execCommand('copy');
                alert(faqAiData.strings.copySuccess || 'Schema copied to clipboard!');
            } catch (err) {
                alert(faqAiData.strings.copyError || 'Failed to copy schema. Please try again.');
            } finally {
                document.body.removeChild(textarea);
            }
        },
        
        // Download schema as file
        downloadSchema: function() {
            const schema = this.elements.schemaOutput.text();
            const format = this.elements.schemaFormat.val();
            
            if (!schema) {
                alert('No schema to download. Please generate schema first.');
                return;
            }
            
            // Determine file extension
            let extension = 'txt';
            if (format === 'json-ld') {
                extension = 'json';
            } else if (format === 'html' || format === 'microdata' || format === 'rdfa') {
                extension = 'html';
            }
            
            // Create download link
            const blob = new Blob([schema], { type: 'text/plain' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `faq-schema-${format}.${extension}`;
            
            // Trigger download
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        
        // Export FAQs as JSON
        exportJson: function() {
            // Update FAQs from editors
            this.updateFaqsFromEditors();
            
            // Create export object
            const exportData = {
                faqs: this.state.faqs,
                exportDate: new Date().toISOString(),
                version: '1.0.0'
            };
            
            // Convert to JSON
            const json = JSON.stringify(exportData, null, 2);
            
            // Create download link
            const blob = new Blob([json], { type: 'application/json' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'faq-export.json';
            
            // Trigger download
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        
        // Import FAQs from JSON
        importJson: function() {
            // Create file input
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.json';
            
            // Add change handler
            input.addEventListener('change', (event) => {
                const file = event.target.files[0];
                
                if (file) {
                    const reader = new FileReader();
                    
                    reader.onload = (e) => {
                        try {
                            const data = JSON.parse(e.target.result);
                            
                            if (data.faqs && Array.isArray(data.faqs)) {
                                // Check if we'll exceed the maximum
                                if (data.faqs.length > this.config.maxFaqCount) {
                                    alert(`Cannot import all FAQs. Maximum of ${this.config.maxFaqCount} FAQs allowed.`);
                                    return;
                                }
                                
                                // Confirm import
                                if (confirm(`Import ${data.faqs.length} FAQs? This will replace your current FAQs.`)) {
                                    // Clear existing FAQs
                                    this.state.faqs = [];
                                    
                                    // Import each FAQ
                                    data.faqs.forEach(faq => {
                                        const newFaq = {
                                            id: 'faq-' + this.state.nextId++,
                                            question: faq.question,
                                            answer: faq.answer
                                        };
                                        
                                        this.state.faqs.push(newFaq);
                                    });
                                    
                                    // Update UI
                                    this.renderFaqs();
                                    this.updateEmptyState();
                                    
                                    // Save changes
                                    this.state.dirty = true;
                                    this.saveToStorage();
                                    
                                    // Show success message
                                    alert(`Successfully imported ${data.faqs.length} FAQs`);
                                }
                            } else {
                                alert('Invalid import file. No FAQs found.');
                            }
                        } catch (error) {
                            alert('Error parsing import file. Please ensure it is a valid JSON file.');
                            console.error('Import error:', error);
                        }
                    };
                    
                    reader.readAsText(file);
                }
            });
            
            // Trigger file selection
            input.click();
        },
        
        // Clear all FAQs
        clearAllFaqs: function() {
            if (!confirm(faqAiData.strings.confirmDeleteAll)) {
                return;
            }
            
            // Clear FAQs from state
            this.state.faqs = [];
            
            // Clear editors
            this.editors.questions = {};
            this.editors.answers = {};
            
            // Update UI
            this.elements.faqList.empty();
            this.updateEmptyState();
            
            // Save changes
            this.state.dirty = true;
            this.saveToStorage();
        },
        
        // Suggest question with AI
        suggestQuestion: function(event) {
            const $item = $(event.currentTarget).closest('.faq-ai-item');
            const id = $item.data('id');
            const faq = this.state.faqs.find(f => f.id === id);
            
            if (!faq) return;
            
            // Get current question
            const questionEditor = this.editors.questions[id];
            if (!questionEditor) return;
            
            const currentQuestion = questionEditor.root.innerHTML;
            
            // Show suggestions panel
            const $suggestionsPanel = $item.find('.faq-ai-suggestions-panel');
            const $suggestionsContent = $item.find('.faq-ai-suggestions-content');
            
            // Clear previous suggestions
            $suggestionsContent.empty();
            $suggestionsContent.html('<div class="faq-ai-loading">Loading suggestions...</div>');
            $suggestionsPanel.slideDown(300);
            
            // Set active FAQ for suggestions
            this.state.generators.question.activeFaq = id;
            this.state.generators.question.loading = true;
            
            // Get all questions for context
            const allQuestions = this.state.faqs.map(f => {
                const editor = this.editors.questions[f.id];
                return editor ? editor.root.innerHTML : f.question;
            });
            
            // Get current answer
            const answerEditor = this.editors.answers[id];
            const currentAnswer = answerEditor ? answerEditor.root.innerHTML : faq.answer;
            
            // AJAX request for AI suggestions
            $.ajax({
                url: faqAiData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'faq_ai_generate_question',
                    nonce: faqAiData.nonce,
                    questions: allQuestions,
                    currentAnswer: currentAnswer,
                    mode: 'improve',
                    websiteContext: this.state.websiteContext,
                    pageUrl: this.config.pageUrl
                },
                success: (response) => {
                    this.state.generators.question.loading = false;
                    
                    if (response.success && response.data && response.data.suggestions) {
                        // Display suggestions
                        this.displaySuggestions($suggestionsContent, response.data.suggestions, 'question', id);
                    } else {
                        // Show error
                        $suggestionsContent.html(`<div class="faq-ai-error">Error: ${response.data.message || 'Failed to get suggestions'}</div>`);
                    }
                },
                error: () => {
                    this.state.generators.question.loading = false;
                    $suggestionsContent.html('<div class="faq-ai-error">Error: Failed to connect to AI service. Please try again.</div>');
                }
            });
        },
        
        // Generate answer with AI
        generateAnswer: function(event) {
            const $item = $(event.currentTarget).closest('.faq-ai-item');
            const id = $item.data('id');
            const faq = this.state.faqs.find(f => f.id === id);
            
            if (!faq) return;
            
            // Get current question
            const questionEditor = this.editors.questions[id];
            if (!questionEditor) return;
            
            const currentQuestion = questionEditor.root.innerHTML;
            
            // Show suggestions panel
            const $suggestionsPanel = $item.find('.faq-ai-suggestions-panel');
            const $suggestionsContent = $item.find('.faq-ai-suggestions-content');
            
            // Clear previous suggestions
            $suggestionsContent.empty();
            $suggestionsContent.html('<div class="faq-ai-loading">Generating answers...</div>');
            $suggestionsPanel.slideDown(300);
            
            // Set active FAQ for suggestions
            this.state.generators.answer.activeFaq = id;
            this.state.generators.answer.loading = true;
            
            // Get all answers for context
            const allAnswers = this.state.faqs.map(f => {
                const editor = this.editors.answers[f.id];
                return editor ? editor.root.innerHTML : f.answer;
            });
            
            // AJAX request for AI answers
            $.ajax({
                url: faqAiData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'faq_ai_generate_answer',
                    nonce: faqAiData.nonce,
                    question: currentQuestion,
                    answers: allAnswers,
                    mode: 'generate',
                    tone: this.elements.answerTone.val(),
                    websiteContext: this.state.websiteContext,
                    pageUrl: this.config.pageUrl
                },
                success: (response) => {
                    this.state.generators.answer.loading = false;
                    
                    if (response.success && response.data && response.data.suggestions) {
                        // Display suggestions
                        this.displaySuggestions($suggestionsContent, response.data.suggestions, 'answer', id);
                    } else {
                        // Show error
                        $suggestionsContent.html(`<div class="faq-ai-error">Error: ${response.data.message || 'Failed to generate answers'}</div>`);
                    }
                },
                error: () => {
                    this.state.generators.answer.loading = false;
                    $suggestionsContent.html('<div class="faq-ai-error">Error: Failed to connect to AI service. Please try again.</div>');
                }
            });
        },
        
        // Enhance existing answer with AI
        enhanceAnswer: function(event) {
            const $item = $(event.currentTarget).closest('.faq-ai-item');
            const id = $item.data('id');
            const faq = this.state.faqs.find(f => f.id === id);
            
            if (!faq) return;
            
            // Get current question and answer
            const questionEditor = this.editors.questions[id];
            const answerEditor = this.editors.answers[id];
            if (!questionEditor || !answerEditor) return;
            
            const currentQuestion = questionEditor.root.innerHTML;
            const currentAnswer = answerEditor.root.innerHTML;
            
            // Show suggestions panel
            const $suggestionsPanel = $item.find('.faq-ai-suggestions-panel');
            const $suggestionsContent = $item.find('.faq-ai-suggestions-content');
            
            // Clear previous suggestions
            $suggestionsContent.empty();
            $suggestionsContent.html('<div class="faq-ai-loading">Enhancing answer...</div>');
            $suggestionsPanel.slideDown(300);
            
            // Set active FAQ for suggestions
            this.state.generators.answer.activeFaq = id;
            this.state.generators.answer.loading = true;
            
            // AJAX request for AI enhancement
            $.ajax({
                url: faqAiData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'faq_ai_enhance_faq',
                    nonce: faqAiData.nonce,
                    question: currentQuestion,
                    answer: currentAnswer,
                    mode: 'enhance',
                    websiteContext: this.state.websiteContext
                },
                success: (response) => {
                    this.state.generators.answer.loading = false;
                    
                    if (response.success && response.data && response.data.suggestions) {
                        // Display suggestions
                        this.displaySuggestions($suggestionsContent, response.data.suggestions, 'answer', id);
                    } else {
                        // Show error
                        $suggestionsContent.html(`<div class="faq-ai-error">Error: ${response.data.message || 'Failed to enhance answer'}</div>`);
                    }
                },
                error: () => {
                    this.state.generators.answer.loading = false;
                    $suggestionsContent.html('<div class="faq-ai-error">Error: Failed to connect to AI service. Please try again.</div>');
                }
            });
        },
        
        // Analyze SEO with AI
        analyzeSeo: function(event) {
            const $item = $(event.currentTarget).closest('.faq-ai-item');
            const id = $item.data('id');
            const faq = this.state.faqs.find(f => f.id === id);
            
            if (!faq) return;
            
            // Get current question and answer
            const questionEditor = this.editors.questions[id];
            const answerEditor = this.editors.answers[id];
            if (!questionEditor || !answerEditor) return;
            
            const currentQuestion = questionEditor.root.innerHTML;
            const currentAnswer = answerEditor.root.innerHTML;
            
            // Show suggestions panel
            const $suggestionsPanel = $item.find('.faq-ai-suggestions-panel');
            const $suggestionsContent = $item.find('.faq-ai-suggestions-content');
            
            // Clear previous suggestions
            $suggestionsContent.empty();
            $suggestionsContent.html('<div class="faq-ai-loading">Analyzing SEO...</div>');
            $suggestionsPanel.slideDown(300);
            
            // Set active FAQ for suggestions
            this.state.generators.seo.activeFaq = id;
            this.state.generators.seo.loading = true;
            
            // AJAX request for SEO analysis
            $.ajax({
                url: faqAiData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'faq_ai_analyze_seo',
                    nonce: faqAiData.nonce,
                    question: currentQuestion,
                    answer: currentAnswer,
                    websiteContext: this.state.websiteContext
                },
                success: (response) => {
                    this.state.generators.seo.loading = false;
                    
                    if (response.success && response.data) {
                        // Update SEO score if provided
                        if (response.data.score) {
                            this.state.generators.seo.score = response.data.score;
                            this.updateSeoScore(response.data.score);
                        }
                        
                        // Display suggestions if provided
                        if (response.data.suggestions) {
                            this.displaySuggestions($suggestionsContent, response.data.suggestions, 'seo', id);
                        } else {
                            // Show analysis results
                            let html = '<div class="faq-ai-seo-analysis">';
                            html += `<div class="faq-ai-seo-score">SEO Score: <span class="score">${response.data.score || 'N/A'}</span></div>`;
                            
                            if (response.data.analysis) {
                                html += '<div class="faq-ai-seo-details">';
                                html += '<h4>SEO Analysis</h4>';
                                html += '<ul>';
                                
                                for (const key in response.data.analysis) {
                                    html += `<li><strong>${key}:</strong> ${response.data.analysis[key]}</li>`;
                                }
                                
                                html += '</ul>';
                                html += '</div>';
                            }
                            
                            html += '</div>';
                            $suggestionsContent.html(html);
                        }
                    } else {
                        // Show error
                        $suggestionsContent.html(`<div class="faq-ai-error">Error: ${response.data.message || 'Failed to analyze SEO'}</div>`);
                    }
                },
                error: () => {
                    this.state.generators.seo.loading = false;
                    $suggestionsContent.html('<div class="faq-ai-error">Error: Failed to connect to AI service. Please try again.</div>');
                }
            });
        },
        
        // Update SEO score display
        updateSeoScore: function(score) {
            // Convert score to 2-digit number
            const scoreNum = parseInt(score, 10) || 0;
            const scoreFormatted = scoreNum.toString().padStart(2, '0');
            
            // Get the digits
            const digit1 = scoreFormatted.charAt(0);
            const digit2 = scoreFormatted.charAt(1);
            
            // Update LED display
            const $ledDigits = this.elements.seoDisplay.find('.faq-ai-led-digit');
            this.setLedDigit($($ledDigits[0]), digit1);
            this.setLedDigit($($ledDigits[1]), digit2);
            
            // Set color based on score
            let colorClass = 'low';
            if (scoreNum >= 80) {
                colorClass = 'high';
            } else if (scoreNum >= 60) {
                colorClass = 'medium';
            }
            
            // Update color
            this.elements.seoDisplay.find('.faq-ai-led-segment').removeClass('low medium high').addClass(colorClass);
        },
        
        // Set LED digit display
        setLedDigit: function($digit, value) {
            // Map of which segments to light for each digit
            const digitMap = {
                '0': [true, true, true, true, true, true, false],
                '1': [false, true, true, false, false, false, false],
                '2': [true, true, false, true, true, false, true],
                '3': [true, true, true, true, false, false, true],
                '4': [false, true, true, false, false, true, true],
                '5': [true, false, true, true, false, true, true],
                '6': [true, false, true, true, true, true, true],
                '7': [true, true, true, false, false, false, false],
                '8': [true, true, true, true, true, true, true],
                '9': [true, true, true, true, false, true, true]
            };
            
            // Get segments
            const segments = $digit.find('.faq-ai-led-segment');
            
            // Set segments based on digit
            const pattern = digitMap[value] || digitMap['0'];
            segments.each(function(index) {
                $(this).toggleClass('active', pattern[index]);
            });
        },
        
        // Display AI suggestions
        displaySuggestions: function($container, suggestions, type, faqId) {
            if (!suggestions || suggestions.length === 0) {
                $container.html('<div class="faq-ai-no-suggestions">No suggestions available.</div>');
                return;
            }
            
            // Clear container
            $container.empty();
            
            // Add each suggestion
            suggestions.forEach(suggestion => {
                // Clone suggestion template
                const $template = $(this.elements.suggestionTemplate.html());
                
                // Set content
                $template.find('.faq-ai-suggestion-text').html(suggestion.text);
                $template.find('.faq-ai-suggestion-benefit').text(suggestion.benefit);
                $template.find('.faq-ai-suggestion-reason').text(suggestion.reason);
                
                // Store original data for applying
                $template.data('suggestion', suggestion);
                $template.data('type', type);
                $template.data('faqId', faqId);
                
                // Add to container
                $container.append($template);
            });
        },
        
        // Close suggestions panel
        closeSuggestions: function(event) {
            const $panel = $(event.currentTarget).closest('.faq-ai-suggestions-panel');
            $panel.slideUp(300);
        },
        
        // Apply a suggestion
        applySuggestion: function(event) {
            const $suggestion = $(event.currentTarget).closest('.faq-ai-suggestion');
            const suggestion = $suggestion.data('suggestion');
            const type = $suggestion.data('type');
            const faqId = $suggestion.data('faqId');
            
            if (!suggestion || !type || !faqId) return;
            
            // Apply suggestion based on type
            if (type === 'question') {
                const editor = this.editors.questions[faqId];
                if (editor) {
                    editor.root.innerHTML = suggestion.text;
                    this.state.dirty = true;
                }
            } else if (type === 'answer') {
                const editor = this.editors.answers[faqId];
                if (editor) {
                    editor.root.innerHTML = suggestion.text;
                    this.state.dirty = true;
                }
            }
            
            // Close suggestions panel
            const $panel = $suggestion.closest('.faq-ai-suggestions-panel');
            $panel.slideUp(300);
            
            // Save changes
            this.saveToStorage();
        },
        
        // Refresh suggestions
        refreshSuggestions: function(event) {
            const $item = $(event.currentTarget).closest('.faq-ai-item');
            const id = $item.data('id');
            
            // Determine which suggestion type to refresh
            if (this.state.generators.question.activeFaq === id) {
                this.suggestQuestion(event);
            } else if (this.state.generators.answer.activeFaq === id) {
                this.generateAnswer(event);
            } else if (this.state.generators.seo.activeFaq === id) {
                this.analyzeSeo(event);
            }
        },
        
        // Validate question
        validateQuestion: function(event) {
            const $item = $(event.currentTarget).closest('.faq-ai-item');
            const id = $item.data('id');
            const faq = this.state.faqs.find(f => f.id === id);
            
            if (!faq) return;
            
            // Get current question
            const questionEditor = this.editors.questions[id];
            if (!questionEditor) return;
            
            const currentQuestion = questionEditor.root.innerHTML;
            
            // Show suggestions panel
            const $suggestionsPanel = $item.find('.faq-ai-suggestions-panel');
            const $suggestionsContent = $item.find('.faq-ai-suggestions-content');
            
            // Clear previous suggestions
            $suggestionsContent.empty();
            $suggestionsContent.html('<div class="faq-ai-loading">Validating question...</div>');
            $suggestionsPanel.slideDown(300);
            
            // Set active FAQ for suggestions
            this.state.generators.question.activeFaq = id;
            this.state.generators.question.loading = true;
            
            // Get all questions for context
            const allQuestions = this.state.faqs.map(f => {
                const editor = this.editors.questions[f.id];
                return editor ? editor.root.innerHTML : f.question;
            });
            
            // AJAX request for validation
            $.ajax({
                url: faqAiData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'faq_ai_generate_question',
                    nonce: faqAiData.nonce,
                    questions: allQuestions,
                    mode: 'validate',
                    websiteContext: this.state.websiteContext,
                    pageUrl: this.config.pageUrl
                },
                success: (response) => {
                    this.state.generators.question.loading = false;
                    
                    if (response.success && response.data && response.data.suggestions) {
                        // Display suggestions
                        this.displaySuggestions($suggestionsContent, response.data.suggestions, 'question', id);
                    } else {
                        // Show error
                        $suggestionsContent.html(`<div class="faq-ai-error">Error: ${response.data.message || 'Failed to validate question'}</div>`);
                    }
                },
                error: () => {
                    this.state.generators.question.loading = false;
                    $suggestionsContent.html('<div class="faq-ai-error">Error: Failed to connect to AI service. Please try again.</div>');
                }
            });
        },
        
        // Show loading overlay
        showLoading: function(message) {
            this.elements.loadingMessage.text(message || 'Loading...');
            this.elements.loadingOverlay.fadeIn(200);
        },
        
        // Hide loading overlay
        hideLoading: function() {
            this.elements.loadingOverlay.fadeOut(200);
        }
    };
    
    // Initialize application when DOM is ready
    $(document).ready(function() {
        FAQApp.init();
    });

})(jQuery);