/**
 * Export Service - Handles exporting FAQs in different formats
 *
 * This service manages the generation of various output formats including
 * JSON-LD schema, HTML with microdata, and other export formats.
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/public/js/services
 */

const ExportService = (function($) {
    'use strict';
    
    // Private variables
    let _settings = {
        baseUrl: '',
        defaultFormat: 'json-ld'
    };
    
    // Private methods
    const _escapeHtml = function(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    };
    
    const _stripHtml = function(html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    };
    
    const _createJsonLdSchema = function(faqs, baseUrl) {
        // Create FAQPage schema
        const schema = {
            "@context": "https://schema.org",
            "@type": "FAQPage",
            "mainEntity": []
        };
        
        // Add each FAQ as a Question
        faqs.forEach(faq => {
            const question = _stripHtml(faq.question);
            const answer = faq.answer; // Keep HTML in answer
            
            schema.mainEntity.push({
                "@type": "Question",
                "name": question,
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": answer
                }
            });
        });
        
        return JSON.stringify(schema, null, 2);
    };
    
    const _createMicrodataSchema = function(faqs, baseUrl) {
        let html = '<div itemscope itemtype="https://schema.org/FAQPage">\n';
        
        // Add each FAQ as a Question with microdata
        faqs.forEach(faq => {
            const question = _escapeHtml(_stripHtml(faq.question));
            const answer = faq.answer; // Keep HTML in answer
            
            html += '  <div itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">\n';
            html += `    <h3 itemprop="name">${question}</h3>\n`;
            html += '    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">\n';
            html += `      <div itemprop="text">${answer}</div>\n`;
            html += '    </div>\n';
            html += '  </div>\n';
        });
        
        html += '</div>';
        return html;
    };
    
    const _createRdfaSchema = function(faqs, baseUrl) {
        let html = '<div vocab="https://schema.org/" typeof="FAQPage">\n';
        
        // Add each FAQ as a Question with RDFa
        faqs.forEach(faq => {
            const question = _escapeHtml(_stripHtml(faq.question));
            const answer = faq.answer; // Keep HTML in answer
            
            html += '  <div property="mainEntity" typeof="Question">\n';
            html += `    <h3 property="name">${question}</h3>\n`;
            html += '    <div property="acceptedAnswer" typeof="Answer">\n';
            html += `      <div property="text">${answer}</div>\n`;
            html += '    </div>\n';
            html += '  </div>\n';
        });
        
        html += '</div>';
        return html;
    };
    
    const _createHtmlOutput = function(faqs, settings) {
        const showNumbers = settings.showNumbers;
        const displayMode = settings.displayMode || 'accordion';
        
        let html = '<div class="faq-ai-container">\n';
        
        // Create FAQs based on display mode
        if (displayMode === 'accordion') {
            html += '  <div class="faq-ai-accordion">\n';
            
            faqs.forEach((faq, index) => {
                const question = faq.question; // Keep HTML in question
                const answer = faq.answer; // Keep HTML in answer
                const number = showNumbers ? `<span class="faq-ai-number">${index + 1}.</span> ` : '';
                
                html += '    <div class="faq-ai-item">\n';
                html += `      <div class="faq-ai-question">${number}${question}</div>\n`;
                html += `      <div class="faq-ai-answer">${answer}</div>\n`;
                html += '    </div>\n';
            });
            
            html += '  </div>\n';
        } else if (displayMode === 'tabs') {
            html += '  <div class="faq-ai-tabs">\n';
            html += '    <div class="faq-ai-tab-list">\n';
            
            // Create tab buttons
            faqs.forEach((faq, index) => {
                const question = _stripHtml(faq.question);
                const number = showNumbers ? `<span class="faq-ai-number">${index + 1}.</span> ` : '';
                const activeClass = index === 0 ? ' active' : '';
                
                html += `      <div class="faq-ai-tab${activeClass}" data-tab="${index}">${number}${question}</div>\n`;
            });
            
            html += '    </div>\n';
            html += '    <div class="faq-ai-tab-content">\n';
            
            // Create tab panels
            faqs.forEach((faq, index) => {
                const answer = faq.answer; // Keep HTML in answer
                const activeClass = index === 0 ? ' active' : '';
                
                html += `      <div class="faq-ai-tab-panel${activeClass}" data-panel="${index}">${answer}</div>\n`;
            });
            
            html += '    </div>\n';
            html += '  </div>\n';
        } else if (displayMode === 'list') {
            html += '  <div class="faq-ai-list">\n';
            
            faqs.forEach((faq, index) => {
                const question = faq.question; // Keep HTML in question
                const answer = faq.answer; // Keep HTML in answer
                const number = showNumbers ? `<span class="faq-ai-number">${index + 1}.</span> ` : '';
                
                html += '    <div class="faq-ai-item">\n';
                html += `      <div class="faq-ai-question">${number}${question}</div>\n`;
                html += `      <div class="faq-ai-answer">${answer}</div>\n`;
                html += '    </div>\n';
            });
            
            html += '  </div>\n';
        }
        
        html += '</div>';
        return html;
    };
    
    const _createJsonExport = function(faqs) {
        // Create export object
        const exportData = {
            faqs: faqs.map(faq => ({
                question: _stripHtml(faq.question),
                answer: _stripHtml(faq.answer)
            })),
            exportDate: new Date().toISOString(),
            version: '1.0.0'
        };
        
        return JSON.stringify(exportData, null, 2);
    };
    
    // Public API
    return {
        /**
         * Initialize the service with settings
         * 
         * @param {Object} settings - Configuration settings
         */
        init: function(settings) {
            _settings = $.extend({}, _settings, settings || {});
        },
        
        /**
         * Generate schema markup based on format
         * 
         * @param {Array} faqs - Array of FAQ objects
         * @param {string} format - Schema format (json-ld, microdata, rdfa)
         * @param {string} baseUrl - Base URL for the schema
         * @return {string} - Generated schema markup
         */
        generateSchema: function(faqs, format, baseUrl) {
            // Use provided baseUrl or fall back to settings
            baseUrl = baseUrl || _settings.baseUrl;
            format = format || _settings.defaultFormat;
            
            // Ensure faqs is an array
            if (!Array.isArray(faqs) || faqs.length === 0) {
                return '';
            }
            
            // Generate schema based on format
            switch (format.toLowerCase()) {
                case 'json-ld':
                    return _createJsonLdSchema(faqs, baseUrl);
                
                case 'microdata':
                    return _createMicrodataSchema(faqs, baseUrl);
                
                case 'rdfa':
                    return _createRdfaSchema(faqs, baseUrl);
                
                case 'html':
                    return _createHtmlOutput(faqs, {
                        showNumbers: true,
                        displayMode: 'accordion'
                    });
                
                default:
                    return _createJsonLdSchema(faqs, baseUrl);
            }
        },
        
        /**
         * Generate HTML output for FAQs
         * 
         * @param {Array} faqs - Array of FAQ objects
         * @param {Object} settings - Display settings
         * @return {string} - Generated HTML
         */
        generateHtml: function(faqs, settings) {
            // Ensure faqs is an array
            if (!Array.isArray(faqs) || faqs.length === 0) {
                return '';
            }
            
            return _createHtmlOutput(faqs, settings || {});
        },
        
        /**
         * Export FAQs as JSON
         * 
         * @param {Array} faqs - Array of FAQ objects
         * @return {string} - JSON string
         */
        exportJson: function(faqs) {
            // Ensure faqs is an array
            if (!Array.isArray(faqs) || faqs.length === 0) {
                return '';
            }
            
            return _createJsonExport(faqs);
        },
        
        /**
         * Parse imported JSON data
         * 
         * @param {string} jsonString - JSON string to parse
         * @return {Object} - Parsed data or null if invalid
         */
        parseImportedJson: function(jsonString) {
            try {
                const data = JSON.parse(jsonString);
                
                // Validate data structure
                if (!data.faqs || !Array.isArray(data.faqs)) {
                    throw new Error('Invalid FAQ data structure');
                }
                
                // Validate each FAQ
                data.faqs.forEach(faq => {
                    if (typeof faq.question === 'undefined' || typeof faq.answer === 'undefined') {
                        throw new Error('Invalid FAQ item structure');
                    }
                });
                
                return data;
            } catch (error) {
                console.error('Error parsing import data:', error);
                return null;
            }
        },
        
        /**
         * Strip HTML tags from text
         * 
         * @param {string} html - HTML string
         * @return {string} - Plain text
         */
        stripHtml: function(html) {
            return _stripHtml(html);
        }
    };
})(jQuery);