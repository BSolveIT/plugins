/**
 * Storage Service - Handles data persistence
 *
 * This service manages saving and loading FAQ data, user settings,
 * and website context from localStorage with fallback mechanisms.
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/public/js/services
 */

const StorageService = (function($) {
    'use strict';
    
    // Private variables
    const STORAGE_KEY = 'faq_ai_generator_data';
    let _debug = false;
    
    // Private methods
    const _logDebug = function(message, data) {
        if (_debug) {
            console.log(`[Storage Service] ${message}`, data || '');
        }
    };
    
    const _isLocalStorageAvailable = function() {
        try {
            const testKey = 'test_storage';
            localStorage.setItem(testKey, testKey);
            localStorage.removeItem(testKey);
            return true;
        } catch (e) {
            return false;
        }
    };
    
    // Session cache as fallback when localStorage is not available
    let _sessionCache = null;
    
    // Public API
    return {
        /**
         * Initialize the service with settings
         * 
         * @param {Object} settings - Configuration settings
         */
        init: function(settings) {
            _debug = settings?.debug || false;
            _logDebug('Initialized');
        },
        
        /**
         * Save data to storage
         * 
         * @param {Object} data - Data to save
         * @return {boolean} - Success indicator
         */
        saveData: function(data) {
            try {
                // Add timestamp
                const storageData = {
                    ...data,
                    timestamp: new Date().getTime()
                };
                
                // Convert to JSON string
                const jsonString = JSON.stringify(storageData);
                
                // Try to save to localStorage
                if (_isLocalStorageAvailable()) {
                    localStorage.setItem(STORAGE_KEY, jsonString);
                    _logDebug('Data saved to localStorage', data);
                } else {
                    // Fallback to session cache
                    _sessionCache = storageData;
                    _logDebug('Data saved to session cache (localStorage unavailable)', data);
                }
                
                return true;
            } catch (error) {
                console.error('Error saving data:', error);
                return false;
            }
        },
        
        /**
         * Load data from storage
         * 
         * @return {Object|null} - Loaded data or null if not found/error
         */
        loadData: function() {
            try {
                let data = null;
                
                // Try to load from localStorage
                if (_isLocalStorageAvailable()) {
                    const storedData = localStorage.getItem(STORAGE_KEY);
                    if (storedData) {
                        data = JSON.parse(storedData);
                        _logDebug('Data loaded from localStorage', data);
                    }
                } else if (_sessionCache) {
                    // Fallback to session cache
                    data = _sessionCache;
                    _logDebug('Data loaded from session cache', data);
                }
                
                return data;
            } catch (error) {
                console.error('Error loading data:', error);
                return null;
            }
        },
        
        /**
         * Check if there is saved data available
         * 
         * @return {boolean} - True if data exists
         */
        hasData: function() {
            if (_isLocalStorageAvailable()) {
                return localStorage.getItem(STORAGE_KEY) !== null;
            } else {
                return _sessionCache !== null;
            }
        },
        
        /**
         * Clear all saved data
         * 
         * @return {boolean} - Success indicator
         */
        clearData: function() {
            try {
                if (_isLocalStorageAvailable()) {
                    localStorage.removeItem(STORAGE_KEY);
                }
                
                _sessionCache = null;
                _logDebug('Data cleared');
                
                return true;
            } catch (error) {
                console.error('Error clearing data:', error);
                return false;
            }
        },
        
        /**
         * Get the timestamp of the last save
         * 
         * @return {number|null} - Timestamp or null if no data
         */
        getLastSaveTime: function() {
            const data = this.loadData();
            return data ? data.timestamp : null;
        },
        
        /**
         * Create a backup of the current data
         * 
         * @return {string|null} - JSON string or null if error
         */
        createBackup: function() {
            try {
                const data = this.loadData();
                if (!data) {
                    return null;
                }
                
                // Add backup metadata
                const backupData = {
                    ...data,
                    backupDate: new Date().toISOString(),
                    version: '1.0.0'
                };
                
                return JSON.stringify(backupData, null, 2);
            } catch (error) {
                console.error('Error creating backup:', error);
                return null;
            }
        },
        
        /**
         * Restore data from a backup
         * 
         * @param {string} backupJson - Backup JSON string
         * @return {boolean} - Success indicator
         */
        restoreFromBackup: function(backupJson) {
            try {
                const backupData = JSON.parse(backupJson);
                
                // Validate backup structure
                if (!backupData.faqs || !Array.isArray(backupData.faqs)) {
                    throw new Error('Invalid backup data structure');
                }
                
                // Save the restored data
                return this.saveData(backupData);
            } catch (error) {
                console.error('Error restoring from backup:', error);
                return false;
            }
        },
        
        /**
         * Save faqs to storage
         * 
         * @param {Array} faqs - Array of FAQ objects
         * @param {Object} settings - User settings
         * @param {string} websiteContext - Website context for AI
         * @return {boolean} - Success indicator
         */
        saveFaqs: function(faqs, settings, websiteContext) {
            return this.saveData({
                faqs: faqs || [],
                settings: settings || {},
                websiteContext: websiteContext || ''
            });
        },
        
        /**
         * Get stored FAQs
         * 
         * @return {Array} - Array of FAQ objects or empty array
         */
        getFaqs: function() {
            const data = this.loadData();
            return data && data.faqs ? data.faqs : [];
        },
        
        /**
         * Get stored settings
         * 
         * @return {Object} - Settings object or empty object
         */
        getSettings: function() {
            const data = this.loadData();
            return data && data.settings ? data.settings : {};
        },
        
        /**
         * Get stored website context
         * 
         * @return {string} - Website context or empty string
         */
        getWebsiteContext: function() {
            const data = this.loadData();
            return data && data.websiteContext ? data.websiteContext : '';
        }
    };
})(jQuery);