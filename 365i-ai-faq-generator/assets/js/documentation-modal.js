/**
 * Documentation Modal Handler for 365i AI FAQ Generator.
 *
 * Handles documentation modal display, AJAX loading, and user interactions
 * for the help system.
 *
 * @package AI_FAQ_Generator
 * @subpackage Assets
 * @since 2.1.0
 */

console.log('[365i AI FAQ] Documentation JS file loaded');

(function($) {
    'use strict';

    /**
     * Documentation Modal Manager Class
     */
    class DocumentationModal {
        constructor() {
            this.modal = null;
            this.currentDocType = null;
            this.isLoading = false;
            
            this.init();
        }

        /**
         * Initialize documentation modal system
         */
        init() {
            this.createModal();
            this.bindEvents();
            
            console.log('[365i AI FAQ] Documentation modal system initialized');
        }

        /**
         * Create the modal HTML structure
         */
        createModal() {
            const modalHtml = `
                <div id="ai-faq-documentation-modal" class="ai-faq-modal-overlay">
                    <div class="ai-faq-modal-container">
                        <div class="ai-faq-modal-header">
                            <h2 id="ai-faq-modal-title">Documentation</h2>
                            <button type="button" class="ai-faq-modal-close" aria-label="Close">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                        <div class="ai-faq-modal-content">
                            <div id="ai-faq-documentation-content">
                                <div class="ai-faq-loading">
                                    <span class="dashicons dashicons-update-alt"></span>
                                    <span>Loading documentation...</span>
                                </div>
                            </div>
                        </div>
                        <div class="ai-faq-modal-footer">
                            <button type="button" class="button button-secondary ai-faq-modal-close">Close</button>
                            <button type="button" class="button button-primary" id="ai-faq-print-docs">
                                <span class="dashicons dashicons-printer"></span>
                                Print Documentation
                            </button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
            this.modal = $('#ai-faq-documentation-modal');
            
            console.log('[365i AI FAQ] Modal created and appended to body');
        }

        /**
         * Bind event handlers
         */
        bindEvents() {
            const self = this;

            // Documentation button clicks
            $(document).on('click', '.ai-faq-doc-button', function(e) {
                e.preventDefault();
                
                const docType = $(this).data('doc-type');
                if (docType) {
                    self.openDocumentation(docType);
                }
            });

            // Close button events (use document delegation for dynamically created elements)
            $(document).on('click', '.ai-faq-modal-close', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[365i AI FAQ] Close button clicked');
                self.closeModal();
            });

            // Overlay click to close (clicking outside modal content)
            $(document).on('click', '#ai-faq-documentation-modal', function(e) {
                if (e.target === this || $(e.target).hasClass('ai-faq-modal-overlay')) {
                    console.log('[365i AI FAQ] Overlay clicked, closing modal');
                    self.closeModal();
                }
            });

            // Prevent modal content clicks from closing modal
            $(document).on('click', '.ai-faq-modal-container', function(e) {
                e.stopPropagation();
            });

            // Print documentation
            $(document).on('click', '#ai-faq-print-docs', function(e) {
                e.preventDefault();
                self.printDocumentation();
            });

            // Keyboard events
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.modal && self.modal.is(':visible')) {
                    console.log('[365i AI FAQ] Escape key pressed, closing modal');
                    self.closeModal();
                }
            });

            // Section navigation within documentation
            $(document).on('click', '.ai-faq-doc-nav a', function(e) {
                e.preventDefault();
                const targetId = $(this).attr('href');
                self.scrollToSection(targetId);
            });
        }

        /**
         * Open documentation modal for specific type
         * @param {string} docType - Documentation type (setup_guide, troubleshooting, api_reference)
         */
        openDocumentation(docType) {
            if (this.isLoading) {
                return;
            }

            this.currentDocType = docType;
            this.showModal();
            this.loadDocumentation(docType);
        }

        /**
         * Show the modal
         */
        showModal() {
            console.log('[365i AI FAQ] showModal() called');
            this.modal.addClass('ai-faq-modal-show');
            $('body').addClass('ai-faq-modal-open');
            this.modal.find('.ai-faq-modal-container').focus();
        }

        /**
         * Close the modal
         */
        closeModal() {
            console.log('[365i AI FAQ] closeModal() called');
            
            if (this.modal && this.modal.length) {
                // Hide modal using CSS class
                this.modal.removeClass('ai-faq-modal-show');
                
                // Remove body class
                $('body').removeClass('ai-faq-modal-open');
                
                // Clear current doc type
                this.currentDocType = null;
                
                console.log('[365i AI FAQ] Modal closed successfully');
            } else {
                console.error('[365i AI FAQ] Modal element not found');
            }
        }

        /**
         * Load documentation content via AJAX
         * @param {string} docType - Documentation type
         */
        loadDocumentation(docType) {
            const self = this;
            
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;
            this.showLoading();

            const data = {
                action: 'ai_faq_get_documentation',
                doc_type: docType,
                nonce: ai_faq_ajax.documentation_nonce
            };

            $.ajax({
                url: ai_faq_ajax.ajax_url,
                type: 'POST',
                data: data,
                timeout: 15000,
                success: function(response) {
                    self.isLoading = false;
                    
                    if (response.success && response.data) {
                        self.renderDocumentation(response.data);
                    } else {
                        self.showError('Failed to load documentation: ' + (response.data?.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    self.isLoading = false;
                    
                    let errorMessage = 'Failed to load documentation.';
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    } else if (xhr.responseJSON?.data?.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    }
                    
                    self.showError(errorMessage);
                    console.error('[365i AI FAQ] Documentation load error:', error);
                }
            });
        }

        /**
         * Show loading state
         */
        showLoading() {
            const loadingHtml = `
                <div class="ai-faq-loading">
                    <span class="dashicons dashicons-update-alt"></span>
                    <span>Loading documentation...</span>
                </div>
            `;

            $('#ai-faq-documentation-content').html(loadingHtml);
            $('#ai-faq-modal-title').text('Loading Documentation...');
        }

        /**
         * Show error message
         * @param {string} message - Error message
         */
        showError(message) {
            const errorHtml = `
                <div class="ai-faq-error">
                    <span class="dashicons dashicons-warning"></span>
                    <h3>Documentation Load Error</h3>
                    <p>${this.escapeHtml(message)}</p>
                    <button type="button" class="button button-primary" onclick="location.reload()">
                        Reload Page
                    </button>
                </div>
            `;

            $('#ai-faq-documentation-content').html(errorHtml);
            $('#ai-faq-modal-title').text('Error Loading Documentation');
        }

        /**
         * Render documentation content
         * @param {Object} data - Documentation data
         */
        renderDocumentation(data) {
            $('#ai-faq-modal-title').text(data.content.title);
            
            let contentHtml = '';
            
            // Create navigation if multiple sections
            if (data.content.sections && data.content.sections.length > 1) {
                contentHtml += this.renderNavigation(data.content.sections);
            }
            
            // Render sections
            if (data.content.sections) {
                contentHtml += this.renderSections(data.content.sections);
            }

            $('#ai-faq-documentation-content').html(contentHtml);
            
            // Scroll to top of content
            this.modal.find('.ai-faq-modal-content').scrollTop(0);
        }

        /**
         * Render navigation for documentation sections
         * @param {Array} sections - Documentation sections
         * @returns {string} Navigation HTML
         */
        renderNavigation(sections) {
            let navHtml = '<div class="ai-faq-doc-nav"><h4>Contents</h4><ul>';
            
            sections.forEach((section, index) => {
                const sectionId = this.createSectionId(section.title);
                navHtml += `<li><a href="#${sectionId}">${this.escapeHtml(section.title)}</a></li>`;
            });
            
            navHtml += '</ul></div>';
            return navHtml;
        }

        /**
         * Render documentation sections
         * @param {Array} sections - Documentation sections
         * @returns {string} Sections HTML
         */
        renderSections(sections) {
            let sectionsHtml = '<div class="ai-faq-doc-sections">';
            
            sections.forEach((section) => {
                sectionsHtml += this.renderSection(section);
            });
            
            sectionsHtml += '</div>';
            return sectionsHtml;
        }

        /**
         * Render individual documentation section
         * @param {Object} section - Section data
         * @returns {string} Section HTML
         */
        renderSection(section) {
            const sectionId = this.createSectionId(section.title);
            let sectionHtml = `<div class="ai-faq-doc-section" id="${sectionId}">`;
            
            sectionHtml += `<h3>${this.escapeHtml(section.title)}</h3>`;
            
            if (section.content) {
                sectionHtml += `<p>${this.escapeHtml(section.content)}</p>`;
            }
            
            // Render endpoint info for API documentation
            if (section.endpoint) {
                sectionHtml += `<div class="ai-faq-endpoint">
                    <h4>Endpoint</h4>
                    <code class="ai-faq-endpoint-url">${this.escapeHtml(section.endpoint)}</code>
                </div>`;
            }
            
            // Render items list
            if (section.items && section.items.length > 0) {
                sectionHtml += '<ul class="ai-faq-doc-list">';
                section.items.forEach(item => {
                    sectionHtml += `<li>${this.escapeHtml(item)}</li>`;
                });
                sectionHtml += '</ul>';
            }
            
            // Render problems and solutions
            if (section.problems && section.problems.length > 0) {
                section.problems.forEach(problem => {
                    sectionHtml += `<div class="ai-faq-problem">
                        <h4 class="ai-faq-problem-title">${this.escapeHtml(problem.problem)}</h4>
                        <ul class="ai-faq-solutions">`;
                    
                    problem.solutions.forEach(solution => {
                        sectionHtml += `<li>${this.escapeHtml(solution)}</li>`;
                    });
                    
                    sectionHtml += '</ul></div>';
                });
            }
            
            // Render code examples
            if (section.request || section.response || section.code) {
                sectionHtml += '<div class="ai-faq-code-examples">';
                
                if (section.request) {
                    sectionHtml += `<div class="ai-faq-code-block">
                        <h4>Request Example</h4>
                        <pre><code>${this.escapeHtml(JSON.stringify(section.request, null, 2))}</code></pre>
                    </div>`;
                }
                
                if (section.response) {
                    sectionHtml += `<div class="ai-faq-code-block">
                        <h4>Response Example</h4>
                        <pre><code>${this.escapeHtml(JSON.stringify(section.response, null, 2))}</code></pre>
                    </div>`;
                }
                
                if (section.code) {
                    sectionHtml += `<div class="ai-faq-code-block">
                        <h4>Code Example</h4>
                        <pre><code>${this.escapeHtml(JSON.stringify(section.code, null, 2))}</code></pre>
                    </div>`;
                }
                
                if (section.headers) {
                    sectionHtml += `<div class="ai-faq-code-block">
                        <h4>Response Headers</h4>
                        <pre><code>${this.escapeHtml(JSON.stringify(section.headers, null, 2))}</code></pre>
                    </div>`;
                }
                
                if (section.error_response) {
                    sectionHtml += `<div class="ai-faq-code-block ai-faq-error-block">
                        <h4>Error Response</h4>
                        <pre><code>${this.escapeHtml(JSON.stringify(section.error_response, null, 2))}</code></pre>
                    </div>`;
                }
                
                if (section.error_codes) {
                    sectionHtml += '<div class="ai-faq-error-codes"><h4>Error Codes</h4><ul>';
                    Object.entries(section.error_codes).forEach(([code, description]) => {
                        sectionHtml += `<li><code>${this.escapeHtml(code)}</code> - ${this.escapeHtml(description)}</li>`;
                    });
                    sectionHtml += '</ul></div>';
                }
                
                sectionHtml += '</div>';
            }
            
            sectionHtml += '</div>';
            return sectionHtml;
        }

        /**
         * Create section ID from title
         * @param {string} title - Section title
         * @returns {string} Section ID
         */
        createSectionId(title) {
            return title.toLowerCase()
                      .replace(/[^a-z0-9\s]/g, '')
                      .replace(/\s+/g, '-')
                      .substring(0, 50);
        }

        /**
         * Scroll to specific section
         * @param {string} targetId - Target section ID
         */
        scrollToSection(targetId) {
            const target = this.modal.find(targetId);
            if (target.length) {
                const modalContent = this.modal.find('.ai-faq-modal-content');
                const scrollTop = target.offset().top - modalContent.offset().top + modalContent.scrollTop() - 20;
                modalContent.animate({ scrollTop: scrollTop }, 300);
            }
        }

        /**
         * Print documentation
         */
        printDocumentation() {
            const printWindow = window.open('', '_blank');
            const content = $('#ai-faq-documentation-content').html();
            const title = $('#ai-faq-modal-title').text();
            
            const printHtml = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${this.escapeHtml(title)} - 365i AI FAQ Generator</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
                        h1, h2, h3, h4 { color: #333; margin-top: 30px; margin-bottom: 10px; }
                        h1 { border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
                        .ai-faq-doc-nav { display: none; }
                        .ai-faq-code-block { background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin: 15px 0; }
                        .ai-faq-code-block h4 { margin-top: 0; }
                        pre { white-space: pre-wrap; word-wrap: break-word; }
                        code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
                        .ai-faq-problem { margin: 20px 0; padding: 15px; background: #f8f8f8; border-radius: 5px; }
                        .ai-faq-problem-title { color: #d63638; margin-bottom: 10px; }
                        .ai-faq-error-block { border-left-color: #d63638; }
                        ul { margin-left: 20px; }
                        li { margin-bottom: 5px; }
                        @media print {
                            body { margin: 0; }
                            .ai-faq-code-block { break-inside: avoid; }
                        }
                    </style>
                </head>
                <body>
                    <h1>${this.escapeHtml(title)}</h1>
                    ${content}
                    <hr style="margin-top: 40px;">
                    <p><small>Generated by 365i AI FAQ Generator on ${new Date().toLocaleString()}</small></p>
                </body>
                </html>
            `;
            
            printWindow.document.write(printHtml);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        }

        /**
         * Escape HTML to prevent XSS
         * @param {string} text - Text to escape
         * @returns {string} Escaped text
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if we're on a plugin admin page
        if (window.ai_faq_ajax && window.ai_faq_ajax.documentation_nonce) {
            window.aiFaqDocumentation = new DocumentationModal();
        }
    });

})(jQuery);