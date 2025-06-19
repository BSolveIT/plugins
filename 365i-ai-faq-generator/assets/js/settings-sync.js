/**
 * Real-time settings synchronization for 365i AI FAQ Generator.
 * 
 * Handles dynamic settings retrieval, cross-tab synchronization,
 * CSS variable injection, and UI updates based on admin configuration.
 * 
 * @package AI_FAQ_Generator
 * @subpackage JavaScript
 * @since 2.1.0
 */

(function($) {
    'use strict';

    /**
     * Settings synchronization manager.
     */
    window.aiFaqSettingsSync = {
        
        /**
         * Configuration options.
         */
        config: {
            pollInterval: 30000,        // 30 seconds
            debounceDelay: 300,         // 300ms
            maxRetries: 3,              // Maximum retry attempts
            retryDelay: 1000,           // Base retry delay in ms
            storageKey: 'ai_faq_settings_cache',
            lastUpdateKey: 'ai_faq_last_update',
            enableCrossTabs: true,      // Enable cross-tab synchronization
            enablePolling: true,        // Enable automatic polling
            enableCSSVars: true,        // Enable CSS variable injection
            debugMode: false,           // Debug logging
        },

        /**
         * Current state.
         */
        state: {
            isPolling: false,
            lastUpdate: 0,
            retryCount: 0,
            currentSettings: null,
            pollTimer: null,
            debounceTimer: null,
        },

        /**
         * Initialize the settings synchronization system.
         */
        init: function() {
            if (typeof aiFaqGen === 'undefined') {
                this.log('aiFaqGen object not found, settings sync disabled');
                return;
            }

            this.log('Initializing settings synchronization...');
            
            // Load configuration from localized data
            if (aiFaqGen.settings_sync) {
                $.extend(this.config, aiFaqGen.settings_sync);
            }

            // Set debug mode from admin settings
            if (aiFaqGen.debug_mode) {
                this.config.debugMode = true;
            }

            // Load cached settings
            this.loadCachedSettings();
            
            // Set up cross-tab communication
            if (this.config.enableCrossTabs) {
                this.initCrossTabSync();
            }
            
            // Start automatic polling
            if (this.config.enablePolling) {
                this.startPolling();
            }
            
            // Initial settings fetch
            this.fetchSettings();
            
            this.log('Settings synchronization initialized');
        },

        /**
         * Fetch settings from server.
         */
        fetchSettings: function(forceRefresh = false) {
            if (typeof aiFaqGen === 'undefined') {
                return;
            }

            const action = forceRefresh ? 'ai_faq_refresh_settings' : 'ai_faq_get_settings';
            
            $.ajax({
                url: aiFaqGen.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    nonce: aiFaqGen.settings_nonce || aiFaqGen.nonce,
                },
                timeout: 10000,
                success: (response) => {
                    if (response.success && response.data.settings) {
                        this.handleSettingsUpdate(response.data.settings);
                        this.state.retryCount = 0; // Reset retry count on success
                    } else {
                        this.log('Settings fetch failed:', response.data?.message || 'Unknown error');
                        this.handleFetchError();
                    }
                },
                error: (xhr, status, error) => {
                    this.log('Settings fetch error:', error);
                    this.handleFetchError();
                }
            });
        },

        /**
         * Handle settings update.
         */
        handleSettingsUpdate: function(settings) {
            this.log('Settings updated:', settings);
            
            // Store previous settings for comparison
            const previousSettings = this.state.currentSettings;
            this.state.currentSettings = settings;
            this.state.lastUpdate = Date.now();
            
            // Cache settings
            this.cacheSettings(settings);
            
            // Update UI elements
            this.updateUI(settings, previousSettings);
            
            // Inject CSS variables
            if (this.config.enableCSSVars && settings.css_variables) {
                this.injectCSSVariables(settings.css_variables);
            }
            
            // Broadcast to other tabs
            if (this.config.enableCrossTabs) {
                this.broadcastToTabs(settings);
            }
            
            // Trigger custom event
            $(document).trigger('ai_faq_settings_updated', [settings, previousSettings]);
        },

        /**
         * Update UI elements based on settings.
         */
        updateUI: function(newSettings, previousSettings) {
            // Update form defaults if on frontend
            if ($('.ai-faq-generator').length > 0) {
                this.updateFrontendDefaults(newSettings);
            }
            
            // Update admin dashboard stats
            if ($('.ai-faq-gen-stats').length > 0) {
                this.updateDashboardStats(newSettings);
            }
            
            // Update theme if changed
            if (previousSettings && newSettings.ui?.theme !== previousSettings.ui?.theme) {
                this.updateTheme(newSettings.ui.theme);
            }
        },

        /**
         * Update frontend form defaults.
         */
        updateFrontendDefaults: function(settings) {
            const general = settings.general || {};
            const generation = settings.generation || {};
            
            // Update number of questions slider
            if (general.default_faq_count) {
                const questionSliders = $('input[name="num_questions"]');
                questionSliders.each(function() {
                    if ($(this).val() === $(this).attr('data-default')) {
                        $(this).val(general.default_faq_count);
                        $(this).attr('data-default', general.default_faq_count);
                        
                        // Update display value
                        const valueDisplay = $(this).closest('.ai-faq-slider-group').find('.ai-faq-slider-value');
                        valueDisplay.text(general.default_faq_count + ' questions');
                    }
                });
            }
            
            // Update tone selection
            if (generation.default_tone) {
                const toneInputs = $('input[name="tone"]');
                toneInputs.prop('checked', false);
                toneInputs.filter(`[value="${generation.default_tone}"]`).prop('checked', true);
                
                // Update visual selection
                $('.ai-faq-tone-option').removeClass('active');
                $(`input[value="${generation.default_tone}"]`).closest('label').addClass('active');
            }
            
            // Update length selection
            if (generation.default_length) {
                const lengthMapping = { 'short': 1, 'medium': 2, 'long': 3 };
                const lengthValue = lengthMapping[generation.default_length] || 2;
                
                const lengthSliders = $('input[name="length"]');
                lengthSliders.each(function() {
                    if ($(this).val() === $(this).attr('data-default')) {
                        $(this).val(lengthValue);
                        $(this).attr('data-default', lengthValue);
                        
                        // Update display value
                        const valueDisplay = $(this).closest('.ai-faq-slider-group').find('.ai-faq-slider-value');
                        valueDisplay.text(generation.default_length_label || generation.default_length);
                    }
                });
            }
            
            // Update schema selection
            if (generation.default_schema_type) {
                const schemaInputs = $('input[name="schema_output"]');
                schemaInputs.prop('checked', false);
                schemaInputs.filter(`[value="${generation.default_schema_type}"]`).prop('checked', true);
                
                // Update visual selection
                $('.ai-faq-schema-option').removeClass('active');
                $(`input[value="${generation.default_schema_type}"]`).closest('label').addClass('active');
            }
        },

        /**
         * Update dashboard statistics.
         */
        updateDashboardStats: function(settings) {
            const general = settings.general || {};
            
            // Update default FAQ count stat
            if (general.default_faq_count) {
                $('.ai-faq-gen-stats .stat-box').each(function() {
                    const label = $(this).find('.stat-label').text();
                    if (label.includes('Default FAQ Count')) {
                        $(this).find('.stat-number').text(general.default_faq_count);
                    }
                });
            }
            
            // Update auto-save interval
            if (general.auto_save_interval) {
                $('.ai-faq-gen-stats .stat-box').each(function() {
                    const label = $(this).find('.stat-label').text();
                    if (label.includes('Auto-save Interval')) {
                        $(this).find('.stat-number').text(general.auto_save_interval + 'm');
                    }
                });
            }
        },

        /**
         * Update theme.
         */
        updateTheme: function(theme) {
            $('body').removeClass(function(index, className) {
                return (className.match(/(^|\s)ai-faq-theme-\S+/g) || []).join(' ');
            }).addClass('ai-faq-theme-' + theme);
        },

        /**
         * Inject CSS variables.
         */
        injectCSSVariables: function(cssVars) {
            let cssText = ':root {\n';
            for (const [property, value] of Object.entries(cssVars)) {
                cssText += `  ${property}: ${value};\n`;
            }
            cssText += '}';
            
            // Remove existing style block
            $('#ai-faq-gen-dynamic-css').remove();
            
            // Add new style block
            $('<style>', {
                id: 'ai-faq-gen-dynamic-css',
                text: cssText
            }).appendTo('head');
        },

        /**
         * Handle fetch errors with retry logic.
         */
        handleFetchError: function() {
            this.state.retryCount++;
            
            if (this.state.retryCount < this.config.maxRetries) {
                const delay = this.config.retryDelay * Math.pow(2, this.state.retryCount - 1);
                this.log(`Retrying settings fetch in ${delay}ms (attempt ${this.state.retryCount})`);
                
                setTimeout(() => {
                    this.fetchSettings();
                }, delay);
            } else {
                this.log('Max retry attempts reached, giving up');
                this.state.retryCount = 0;
            }
        },

        /**
         * Start automatic polling.
         */
        startPolling: function() {
            if (this.state.isPolling) {
                return;
            }
            
            this.state.isPolling = true;
            this.state.pollTimer = setInterval(() => {
                this.fetchSettings();
            }, this.config.pollInterval);
            
            this.log('Started automatic polling every', this.config.pollInterval + 'ms');
        },

        /**
         * Stop automatic polling.
         */
        stopPolling: function() {
            if (this.state.pollTimer) {
                clearInterval(this.state.pollTimer);
                this.state.pollTimer = null;
            }
            this.state.isPolling = false;
            this.log('Stopped automatic polling');
        },

        /**
         * Initialize cross-tab synchronization.
         */
        initCrossTabSync: function() {
            $(window).on('storage', (e) => {
                if (e.originalEvent.key === this.config.storageKey) {
                    const settings = this.parseJSON(e.originalEvent.newValue);
                    if (settings) {
                        this.log('Received settings update from another tab');
                        this.handleSettingsUpdate(settings);
                    }
                }
            });
            
            this.log('Cross-tab synchronization enabled');
        },

        /**
         * Broadcast settings to other tabs.
         */
        broadcastToTabs: function(settings) {
            try {
                localStorage.setItem(this.config.storageKey, JSON.stringify(settings));
                localStorage.setItem(this.config.lastUpdateKey, this.state.lastUpdate.toString());
            } catch (e) {
                this.log('Failed to broadcast to other tabs:', e);
            }
        },

        /**
         * Cache settings locally.
         */
        cacheSettings: function(settings) {
            try {
                sessionStorage.setItem(this.config.storageKey, JSON.stringify(settings));
                sessionStorage.setItem(this.config.lastUpdateKey, this.state.lastUpdate.toString());
            } catch (e) {
                this.log('Failed to cache settings:', e);
            }
        },

        /**
         * Load cached settings.
         */
        loadCachedSettings: function() {
            try {
                const cached = sessionStorage.getItem(this.config.storageKey);
                const lastUpdate = sessionStorage.getItem(this.config.lastUpdateKey);
                
                if (cached && lastUpdate) {
                    const settings = this.parseJSON(cached);
                    if (settings) {
                        this.state.currentSettings = settings;
                        this.state.lastUpdate = parseInt(lastUpdate, 10);
                        this.log('Loaded cached settings from', new Date(this.state.lastUpdate));
                        
                        // Apply cached settings immediately
                        this.updateUI(settings);
                        if (this.config.enableCSSVars && settings.css_variables) {
                            this.injectCSSVariables(settings.css_variables);
                        }
                    }
                }
            } catch (e) {
                this.log('Failed to load cached settings:', e);
            }
        },

        /**
         * Force refresh settings.
         */
        forceRefresh: function() {
            this.log('Forcing settings refresh...');
            this.fetchSettings(true);
        },

        /**
         * Get current settings.
         */
        getCurrentSettings: function() {
            return this.state.currentSettings;
        },

        /**
         * Parse JSON safely.
         */
        parseJSON: function(str) {
            try {
                return JSON.parse(str);
            } catch (e) {
                return null;
            }
        },

        /**
         * Debug logging.
         */
        log: function(...args) {
            if (this.config.debugMode) {
                console.log('[AI FAQ Settings Sync]', ...args);
            }
        },

        /**
         * Cleanup resources.
         */
        destroy: function() {
            this.stopPolling();
            $(window).off('storage');
            $('#ai-faq-gen-dynamic-css').remove();
            this.log('Settings synchronization destroyed');
        }
    };

    /**
     * Initialize when document is ready.
     */
    $(document).ready(function() {
        // Initialize with a small delay to ensure other scripts are loaded
        setTimeout(function() {
            window.aiFaqSettingsSync.init();
        }, 100);
    });

    /**
     * Expose public API for manual control.
     */
    window.aiFaqSettingsAPI = {
        refresh: () => window.aiFaqSettingsSync.forceRefresh(),
        getSettings: () => window.aiFaqSettingsSync.getCurrentSettings(),
        startPolling: () => window.aiFaqSettingsSync.startPolling(),
        stopPolling: () => window.aiFaqSettingsSync.stopPolling(),
    };

})(jQuery);