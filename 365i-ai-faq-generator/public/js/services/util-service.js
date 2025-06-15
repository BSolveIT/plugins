/**
 * Utility Service - Common helper functions
 *
 * This service provides utility functions for:
 * - HTML/text processing
 * - URL handling
 * - Object/array manipulation
 * - Form validation
 * - Misc helpers
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/public/js/services
 */

const UtilService = (function($) {
    'use strict';
    
    // Public API
    return {
        /**
         * Sanitize HTML to prevent XSS
         * 
         * @param {string} html - HTML content to sanitize
         * @return {string} - Sanitized HTML
         */
        sanitizeHtml: function(html) {
            if (!html) return '';
            
            // Create a new div element
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // Remove potentially dangerous elements and attributes
            const dangerous = tempDiv.querySelectorAll('script, iframe, object, embed, form');
            dangerous.forEach(el => el.remove());
            
            // Remove dangerous attributes from all elements
            const allElements = tempDiv.querySelectorAll('*');
            const dangerousAttrs = ['onload', 'onerror', 'onclick', 'onmouseover', 'onmouseout', 'onmouseenter', 'onmouseleave', 'onkeydown', 'onkeypress', 'onkeyup'];
            
            allElements.forEach(el => {
                dangerousAttrs.forEach(attr => {
                    if (el.hasAttribute(attr)) {
                        el.removeAttribute(attr);
                    }
                });
                
                // Sanitize style attributes
                if (el.hasAttribute('style')) {
                    const style = el.getAttribute('style');
                    const sanitizedStyle = style.replace(/expression\(.*?\)|javascript:|behavior:|background-image:|background:|url\(/gi, '');
                    el.setAttribute('style', sanitizedStyle);
                }
                
                // Sanitize href attributes
                if (el.hasAttribute('href')) {
                    const href = el.getAttribute('href');
                    if (href.toLowerCase().startsWith('javascript:')) {
                        el.setAttribute('href', '#');
                    }
                }
            });
            
            return tempDiv.innerHTML;
        },
        
        /**
         * Strip HTML tags from text
         * 
         * @param {string} html - HTML content
         * @return {string} - Plain text
         */
        stripHtml: function(html) {
            if (!html) return '';
            
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            return tempDiv.textContent || tempDiv.innerText || '';
        },
        
        /**
         * Truncate text to specified length with ellipsis
         * 
         * @param {string} text - Text to truncate
         * @param {number} length - Maximum length
         * @return {string} - Truncated text
         */
        truncateText: function(text, length) {
            if (!text) return '';
            
            if (text.length <= length) {
                return text;
            }
            
            return text.substring(0, length) + '...';
        },
        
        /**
         * Generate a slug from text
         * 
         * @param {string} text - Text to convert to slug
         * @return {string} - URL-friendly slug
         */
        slugify: function(text) {
            if (!text) return '';
            
            return text
                .toLowerCase()
                .replace(/[^\w\s-]/g, '')  // Remove non-word chars
                .replace(/[\s_-]+/g, '-')  // Replace spaces and underscores with hyphens
                .replace(/^-+|-+$/g, '');  // Remove leading/trailing hyphens
        },
        
        /**
         * Generate an anchor ID from text
         * 
         * @param {string} text - Text to convert to ID
         * @param {string} prefix - Optional prefix
         * @return {string} - Anchor ID
         */
        generateAnchorId: function(text, prefix) {
            const slug = this.slugify(text);
            return prefix ? `${prefix}-${slug}` : slug;
        },
        
        /**
         * Format a date
         * 
         * @param {Date|string|number} date - Date to format
         * @param {string} format - Format string (simple)
         * @return {string} - Formatted date string
         */
        formatDate: function(date, format) {
            if (!date) return '';
            
            const d = new Date(date);
            if (isNaN(d.getTime())) return '';
            
            format = format || 'YYYY-MM-DD';
            
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');
            const seconds = String(d.getSeconds()).padStart(2, '0');
            
            return format
                .replace('YYYY', year)
                .replace('MM', month)
                .replace('DD', day)
                .replace('HH', hours)
                .replace('mm', minutes)
                .replace('ss', seconds);
        },
        
        /**
         * Format a relative time (e.g., "5 minutes ago")
         * 
         * @param {Date|string|number} date - Date to format
         * @return {string} - Relative time string
         */
        timeAgo: function(date) {
            if (!date) return '';
            
            const d = new Date(date);
            if (isNaN(d.getTime())) return '';
            
            const now = new Date();
            const seconds = Math.floor((now - d) / 1000);
            
            if (seconds < 60) {
                return 'just now';
            }
            
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) {
                return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
            }
            
            const hours = Math.floor(minutes / 60);
            if (hours < 24) {
                return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            }
            
            const days = Math.floor(hours / 24);
            if (days < 30) {
                return `${days} day${days > 1 ? 's' : ''} ago`;
            }
            
            const months = Math.floor(days / 30);
            if (months < 12) {
                return `${months} month${months > 1 ? 's' : ''} ago`;
            }
            
            const years = Math.floor(months / 12);
            return `${years} year${years > 1 ? 's' : ''} ago`;
        },
        
        /**
         * Get URL parameters as an object
         * 
         * @param {string} url - URL to parse (defaults to current URL)
         * @return {Object} - Object containing parameters
         */
        getUrlParams: function(url) {
            const params = {};
            
            // Use current URL if none provided
            url = url || window.location.href;
            
            // Extract query string
            const queryString = url.split('?')[1];
            if (!queryString) return params;
            
            // Parse parameters
            const paramPairs = queryString.split('&');
            paramPairs.forEach(pair => {
                const [key, value] = pair.split('=');
                params[decodeURIComponent(key)] = decodeURIComponent(value || '');
            });
            
            return params;
        },
        
        /**
         * Add parameters to a URL
         * 
         * @param {string} url - Base URL
         * @param {Object} params - Parameters to add
         * @return {string} - URL with added parameters
         */
        addUrlParams: function(url, params) {
            if (!url || !params) return url;
            
            const urlObj = new URL(url, window.location.origin);
            
            // Add each parameter
            Object.keys(params).forEach(key => {
                if (params[key] !== undefined && params[key] !== null) {
                    urlObj.searchParams.append(key, params[key]);
                }
            });
            
            return urlObj.toString();
        },
        
        /**
         * Deep clone an object
         * 
         * @param {Object} obj - Object to clone
         * @return {Object} - Cloned object
         */
        deepClone: function(obj) {
            return JSON.parse(JSON.stringify(obj));
        },
        
        /**
         * Check if an object is empty
         * 
         * @param {Object} obj - Object to check
         * @return {boolean} - True if empty
         */
        isEmptyObject: function(obj) {
            return obj && Object.keys(obj).length === 0 && obj.constructor === Object;
        },
        
        /**
         * Debounce a function call
         * 
         * @param {Function} func - Function to debounce
         * @param {number} wait - Wait time in milliseconds
         * @return {Function} - Debounced function
         */
        debounce: function(func, wait) {
            let timeout;
            
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        /**
         * Throttle a function call
         * 
         * @param {Function} func - Function to throttle
         * @param {number} limit - Limit in milliseconds
         * @return {Function} - Throttled function
         */
        throttle: function(func, limit) {
            let inThrottle;
            
            return function executedFunction(...args) {
                if (!inThrottle) {
                    func(...args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },
        
        /**
         * Validate an email address
         * 
         * @param {string} email - Email address to validate
         * @return {boolean} - True if valid
         */
        isValidEmail: function(email) {
            if (!email) return false;
            
            const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        },
        
        /**
         * Validate a URL
         * 
         * @param {string} url - URL to validate
         * @return {boolean} - True if valid
         */
        isValidUrl: function(url) {
            if (!url) return false;
            
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        },
        
        /**
         * Generate a random ID
         * 
         * @param {string} prefix - Optional prefix
         * @return {string} - Random ID
         */
        generateId: function(prefix) {
            const random = Math.random().toString(36).substring(2, 9);
            return prefix ? `${prefix}-${random}` : random;
        },
        
        /**
         * Format a number with commas
         * 
         * @param {number} num - Number to format
         * @return {string} - Formatted number
         */
        formatNumber: function(num) {
            if (isNaN(num)) return '0';
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },
        
        /**
         * Convert HTML to plain text for SEO analysis
         * 
         * @param {string} html - HTML content
         * @return {string} - Plain text
         */
        htmlToPlainText: function(html) {
            if (!html) return '';
            
            // Create a new div element
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // Convert some elements to add proper spacing
            const brs = tempDiv.querySelectorAll('br');
            brs.forEach(br => br.replaceWith('\n'));
            
            const paragraphs = tempDiv.querySelectorAll('p, div, h1, h2, h3, h4, h5, h6, li');
            paragraphs.forEach(p => {
                if (p.textContent.trim()) {
                    p.insertAdjacentText('afterend', '\n\n');
                }
            });
            
            // Get text content
            let text = tempDiv.textContent || tempDiv.innerText || '';
            
            // Clean up spacing
            text = text.replace(/\s+/g, ' ').trim();
            
            return text;
        },
        
        /**
         * Count words in text
         * 
         * @param {string} text - Text to count words in
         * @return {number} - Word count
         */
        countWords: function(text) {
            if (!text) return 0;
            
            // Strip HTML if present
            if (text.indexOf('<') !== -1 && text.indexOf('>') !== -1) {
                text = this.stripHtml(text);
            }
            
            // Split by whitespace and filter out empty strings
            const words = text.trim().split(/\s+/).filter(Boolean);
            return words.length;
        },
        
        /**
         * Calculate reading time in minutes
         * 
         * @param {string} text - Text to calculate reading time for
         * @param {number} wpm - Words per minute (default: 200)
         * @return {number} - Reading time in minutes
         */
        calculateReadingTime: function(text, wpm) {
            if (!text) return 0;
            
            const words = this.countWords(text);
            wpm = wpm || 200;
            
            return Math.ceil(words / wpm);
        }
    };
})(jQuery);