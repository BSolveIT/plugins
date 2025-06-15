/**
 * Cloudflare Service - Handles communication with Cloudflare AI workers
 *
 * This service abstracts the API calls to different Cloudflare AI workers that
 * power the FAQ generation, question/answer improvements, and SEO analysis.
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/public/js/services
 */

const CloudflareService = (function($) {
    'use strict';
    
    // Private variables
    let _workerUrls = {};
    let _apiKey = '';
    let _rateLimitRemaining = {};
    let _rateLimitReset = {};
    let _debug = false;
    
    // Private methods
    const _logDebug = function(message, data) {
        if (_debug) {
            console.log(`[Cloudflare Service] ${message}`, data || '');
        }
    };
    
    const _handleError = function(error, workerType) {
        _logDebug(`Error in ${workerType} worker:`, error);
        
        // Check if it's a rate limit error
        if (error.status === 429) {
            return {
                success: false,
                error: 'Rate limit exceeded. Please try again later.',
                rateLimited: true,
                resetTime: _rateLimitReset[workerType] || null
            };
        }
        
        return {
            success: false,
            error: error.message || 'An unknown error occurred.',
            rateLimited: false
        };
    };
    
    const _processHeaders = function(headers, workerType) {
        // Update rate limit information if available
        if (headers.get('X-RateLimit-Remaining')) {
            _rateLimitRemaining[workerType] = parseInt(headers.get('X-RateLimit-Remaining'), 10);
        }
        
        if (headers.get('X-RateLimit-Reset')) {
            _rateLimitReset[workerType] = parseInt(headers.get('X-RateLimit-Reset'), 10);
        }
        
        _logDebug(`Rate limit for ${workerType}:`, {
            remaining: _rateLimitRemaining[workerType],
            reset: _rateLimitReset[workerType]
        });
    };
    
    // Public API
    return {
        /**
         * Initialize the service with configuration
         * 
         * @param {Object} config - Configuration object
         */
        init: function(config) {
            _workerUrls = config.workerUrls || {};
            _apiKey = config.apiKey || '';
            _debug = config.debug || false;
            
            _logDebug('Initialized with URLs:', _workerUrls);
        },
        
        /**
         * Generate question suggestions using AI
         * 
         * @param {Object} data - Request data
         * @return {Promise} - Promise resolving to suggestions
         */
        generateQuestions: function(data) {
            const workerType = 'question';
            const url = _workerUrls[workerType];
            
            if (!url) {
                return Promise.reject({
                    success: false,
                    error: 'Question worker URL not configured.'
                });
            }
            
            _logDebug('Generating questions with data:', data);
            
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': _apiKey
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                _processHeaders(response.headers, workerType);
                
                if (!response.ok) {
                    throw {
                        status: response.status,
                        message: `Server responded with ${response.status}: ${response.statusText}`
                    };
                }
                
                return response.json();
            })
            .then(result => {
                _logDebug('Question generation successful:', result);
                return {
                    success: true,
                    suggestions: result.suggestions || [],
                    rateLimitRemaining: _rateLimitRemaining[workerType]
                };
            })
            .catch(error => _handleError(error, workerType));
        },
        
        /**
         * Generate answer suggestions using AI
         * 
         * @param {Object} data - Request data
         * @return {Promise} - Promise resolving to suggestions
         */
        generateAnswers: function(data) {
            const workerType = 'answer';
            const url = _workerUrls[workerType];
            
            if (!url) {
                return Promise.reject({
                    success: false,
                    error: 'Answer worker URL not configured.'
                });
            }
            
            _logDebug('Generating answers with data:', data);
            
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': _apiKey
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                _processHeaders(response.headers, workerType);
                
                if (!response.ok) {
                    throw {
                        status: response.status,
                        message: `Server responded with ${response.status}: ${response.statusText}`
                    };
                }
                
                return response.json();
            })
            .then(result => {
                _logDebug('Answer generation successful:', result);
                return {
                    success: true,
                    suggestions: result.suggestions || [],
                    rateLimitRemaining: _rateLimitRemaining[workerType]
                };
            })
            .catch(error => _handleError(error, workerType));
        },
        
        /**
         * Enhance an existing FAQ with AI
         * 
         * @param {Object} data - Request data
         * @return {Promise} - Promise resolving to enhanced content
         */
        enhanceFaq: function(data) {
            const workerType = 'enhance';
            const url = _workerUrls[workerType];
            
            if (!url) {
                return Promise.reject({
                    success: false,
                    error: 'Enhance worker URL not configured.'
                });
            }
            
            _logDebug('Enhancing FAQ with data:', data);
            
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': _apiKey
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                _processHeaders(response.headers, workerType);
                
                if (!response.ok) {
                    throw {
                        status: response.status,
                        message: `Server responded with ${response.status}: ${response.statusText}`
                    };
                }
                
                return response.json();
            })
            .then(result => {
                _logDebug('FAQ enhancement successful:', result);
                return {
                    success: true,
                    suggestions: result.suggestions || [],
                    rateLimitRemaining: _rateLimitRemaining[workerType]
                };
            })
            .catch(error => _handleError(error, workerType));
        },
        
        /**
         * Analyze FAQ SEO using AI
         * 
         * @param {Object} data - Request data
         * @return {Promise} - Promise resolving to SEO analysis
         */
        analyzeSeo: function(data) {
            const workerType = 'seo';
            const url = _workerUrls[workerType];
            
            if (!url) {
                return Promise.reject({
                    success: false,
                    error: 'SEO worker URL not configured.'
                });
            }
            
            _logDebug('Analyzing SEO with data:', data);
            
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': _apiKey
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                _processHeaders(response.headers, workerType);
                
                if (!response.ok) {
                    throw {
                        status: response.status,
                        message: `Server responded with ${response.status}: ${response.statusText}`
                    };
                }
                
                return response.json();
            })
            .then(result => {
                _logDebug('SEO analysis successful:', result);
                return {
                    success: true,
                    score: result.score || 0,
                    analysis: result.analysis || {},
                    suggestions: result.suggestions || [],
                    rateLimitRemaining: _rateLimitRemaining[workerType]
                };
            })
            .catch(error => _handleError(error, workerType));
        },
        
        /**
         * Extract FAQs from URL using AI
         * 
         * @param {Object} data - Request data
         * @return {Promise} - Promise resolving to extracted FAQs
         */
        extractFaqs: function(data) {
            const workerType = 'extract';
            const url = _workerUrls[workerType];
            
            if (!url) {
                return Promise.reject({
                    success: false,
                    error: 'Extract worker URL not configured.'
                });
            }
            
            _logDebug('Extracting FAQs with data:', data);
            
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': _apiKey
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                _processHeaders(response.headers, workerType);
                
                if (!response.ok) {
                    throw {
                        status: response.status,
                        message: `Server responded with ${response.status}: ${response.statusText}`
                    };
                }
                
                return response.json();
            })
            .then(result => {
                _logDebug('FAQ extraction successful:', result);
                return {
                    success: true,
                    faqs: result.faqs || [],
                    content: result.content || '',
                    rateLimitRemaining: _rateLimitRemaining[workerType]
                };
            })
            .catch(error => _handleError(error, workerType));
        },
        
        /**
         * Generate topics for FAQs using AI
         * 
         * @param {Object} data - Request data
         * @return {Promise} - Promise resolving to topic suggestions
         */
        generateTopics: function(data) {
            const workerType = 'topic';
            const url = _workerUrls[workerType];
            
            if (!url) {
                return Promise.reject({
                    success: false,
                    error: 'Topic worker URL not configured.'
                });
            }
            
            _logDebug('Generating topics with data:', data);
            
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': _apiKey
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                _processHeaders(response.headers, workerType);
                
                if (!response.ok) {
                    throw {
                        status: response.status,
                        message: `Server responded with ${response.status}: ${response.statusText}`
                    };
                }
                
                return response.json();
            })
            .then(result => {
                _logDebug('Topic generation successful:', result);
                return {
                    success: true,
                    topics: result.topics || [],
                    rateLimitRemaining: _rateLimitRemaining[workerType]
                };
            })
            .catch(error => _handleError(error, workerType));
        },
        
        /**
         * Get rate limit information for a specific worker
         * 
         * @param {string} workerType - Type of worker
         * @return {Object} - Rate limit information
         */
        getRateLimitInfo: function(workerType) {
            return {
                remaining: _rateLimitRemaining[workerType] || null,
                reset: _rateLimitReset[workerType] || null
            };
        },
        
        /**
         * Check if a worker has available rate limits
         * 
         * @param {string} workerType - Type of worker
         * @return {boolean} - True if worker has available requests
         */
        hasAvailableRequests: function(workerType) {
            return !_rateLimitRemaining[workerType] || _rateLimitRemaining[workerType] > 0;
        }
    };
})(jQuery);