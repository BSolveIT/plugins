/**
 * UI Controller - Manages UI interactions and state
 *
 * This service handles UI-specific functionality including:
 * - Tab switching
 * - Panel visibility
 * - Loading indicators
 * - Notifications
 * - UI state persistence
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/public/js/services
 */

const UiController = (function($) {
    'use strict';
    
    // Private variables
    let _elements = {};
    let _state = {
        activeTab: 'editor',
        panels: {},
        notifications: []
    };
    let _debug = false;
    
    // Private methods
    const _logDebug = function(message, data) {
        if (_debug) {
            console.log(`[UI Controller] ${message}`, data || '');
        }
    };
    
    const _cacheElements = function() {
        _elements = {
            container: $('#faq-ai-generator'),
            tabs: $('.faq-ai-tab'),
            tabPanels: $('.faq-ai-tab-panel'),
            panels: $('.faq-ai-panel'),
            loadingOverlay: $('.faq-ai-loading-overlay'),
            loadingMessage: $('.faq-ai-loading-message'),
            notificationArea: $('.faq-ai-notifications'),
            emptyState: $('.faq-ai-empty-state')
        };
    };
    
    // Public API
    return {
        /**
         * Initialize the UI controller
         * 
         * @param {Object} settings - Configuration settings
         */
        init: function(settings) {
            _debug = settings?.debug || false;
            _logDebug('Initializing UI Controller');
            
            // Cache DOM elements
            _cacheElements();
            
            // Set initial active tab from URL hash if present
            const hash = window.location.hash.substring(1);
            if (hash && _elements.tabPanels.filter(`#faq-ai-${hash}-panel`).length) {
                this.switchTab(hash);
            }
            
            // Set up resize handler
            $(window).on('resize', this.handleResize.bind(this));
            
            _logDebug('UI Controller initialized');
        },
        
        /**
         * Re-cache DOM elements
         * Useful after dynamic content is added
         */
        refreshElements: function() {
            _logDebug('Refreshing DOM elements');
            _cacheElements();
        },
        
        /**
         * Switch active tab
         * 
         * @param {string} tab - Tab identifier
         */
        switchTab: function(tab) {
            _logDebug('Switching to tab', tab);
            
            // Update tab buttons
            _elements.tabs.removeClass('active');
            _elements.tabs.filter(`[data-tab="${tab}"]`).addClass('active');
            
            // Update tab panels
            _elements.tabPanels.removeClass('active');
            _elements.tabPanels.filter(`#faq-ai-${tab}-panel`).addClass('active');
            
            // Update state
            _state.activeTab = tab;
            
            // Update URL hash
            if (history.pushState) {
                history.pushState(null, null, '#' + tab);
            } else {
                location.hash = '#' + tab;
            }
        },
        
        /**
         * Get the current active tab
         * 
         * @return {string} - Active tab identifier
         */
        getActiveTab: function() {
            return _state.activeTab;
        },
        
        /**
         * Show a panel
         * 
         * @param {string} panelSelector - Panel selector
         * @param {function} callback - Optional callback after animation
         */
        showPanel: function(panelSelector, callback) {
            _logDebug('Showing panel', panelSelector);
            
            const $panel = $(panelSelector);
            if (!$panel.length) {
                return;
            }
            
            // Hide any other open panels first
            _elements.panels.not($panel).slideUp(300);
            
            // Show the requested panel
            $panel.slideDown(300, function() {
                // Update state
                _state.panels[panelSelector] = true;
                
                // Execute callback if provided
                if (typeof callback === 'function') {
                    callback();
                }
            });
        },
        
        /**
         * Hide a panel
         * 
         * @param {string} panelSelector - Panel selector
         * @param {function} callback - Optional callback after animation
         */
        hidePanel: function(panelSelector, callback) {
            _logDebug('Hiding panel', panelSelector);
            
            const $panel = $(panelSelector);
            if (!$panel.length) {
                return;
            }
            
            // Hide the panel
            $panel.slideUp(300, function() {
                // Update state
                _state.panels[panelSelector] = false;
                
                // Execute callback if provided
                if (typeof callback === 'function') {
                    callback();
                }
            });
        },
        
        /**
         * Toggle a panel's visibility
         * 
         * @param {string} panelSelector - Panel selector
         */
        togglePanel: function(panelSelector) {
            _logDebug('Toggling panel', panelSelector);
            
            const $panel = $(panelSelector);
            if (!$panel.length) {
                return;
            }
            
            if ($panel.is(':visible')) {
                this.hidePanel(panelSelector);
            } else {
                this.showPanel(panelSelector);
            }
        },
        
        /**
         * Show loading overlay
         * 
         * @param {string} message - Loading message
         */
        showLoading: function(message) {
            _logDebug('Showing loading overlay', message);
            
            _elements.loadingMessage.text(message || 'Loading...');
            _elements.loadingOverlay.fadeIn(200);
        },
        
        /**
         * Hide loading overlay
         */
        hideLoading: function() {
            _logDebug('Hiding loading overlay');
            
            _elements.loadingOverlay.fadeOut(200);
        },
        
        /**
         * Show a notification
         * 
         * @param {string} message - Notification message
         * @param {string} type - Notification type (success, error, warning, info)
         * @param {number} duration - Display duration in ms (0 for persistent)
         * @return {string} - Notification ID
         */
        showNotification: function(message, type, duration) {
            _logDebug('Showing notification', { message, type, duration });
            
            type = type || 'info';
            duration = duration || 3000;
            
            // Create unique ID
            const id = 'notification-' + new Date().getTime();
            
            // Create notification element
            const $notification = $(`
                <div class="faq-ai-notification ${type}" id="${id}">
                    <div class="faq-ai-notification-content">${message}</div>
                    <button class="faq-ai-notification-close">&times;</button>
                </div>
            `);
            
            // Add to notification area
            _elements.notificationArea.append($notification);
            
            // Add to state
            _state.notifications.push({
                id: id,
                type: type,
                message: message,
                timestamp: new Date().getTime()
            });
            
            // Add close handler
            $notification.find('.faq-ai-notification-close').on('click', function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Auto-dismiss after duration (if not persistent)
            if (duration > 0) {
                setTimeout(function() {
                    $notification.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, duration);
            }
            
            // Show with animation
            $notification.hide().fadeIn(300);
            
            return id;
        },
        
        /**
         * Remove a specific notification
         * 
         * @param {string} id - Notification ID
         */
        removeNotification: function(id) {
            _logDebug('Removing notification', id);
            
            const $notification = $('#' + id);
            if ($notification.length) {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
                
                // Remove from state
                _state.notifications = _state.notifications.filter(n => n.id !== id);
            }
        },
        
        /**
         * Clear all notifications
         */
        clearNotifications: function() {
            _logDebug('Clearing all notifications');
            
            _elements.notificationArea.find('.faq-ai-notification').fadeOut(300, function() {
                $(this).remove();
            });
            
            // Clear state
            _state.notifications = [];
        },
        
        /**
         * Show confirmation dialog
         * 
         * @param {string} message - Confirmation message
         * @param {function} onConfirm - Callback when confirmed
         * @param {function} onCancel - Callback when canceled
         */
        confirm: function(message, onConfirm, onCancel) {
            _logDebug('Showing confirmation dialog', message);
            
            // Use native confirm for simplicity
            // In a production app, you might want to use a custom modal
            const confirmed = window.confirm(message);
            
            if (confirmed && typeof onConfirm === 'function') {
                onConfirm();
            } else if (!confirmed && typeof onCancel === 'function') {
                onCancel();
            }
        },
        
        /**
         * Handle window resize event
         */
        handleResize: function() {
            // This method can be extended to handle responsive UI adjustments
            _logDebug('Window resized');
        },
        
        /**
         * Show empty state
         */
        showEmptyState: function() {
            _logDebug('Showing empty state');
            _elements.emptyState.show();
        },
        
        /**
         * Hide empty state
         */
        hideEmptyState: function() {
            _logDebug('Hiding empty state');
            _elements.emptyState.hide();
        },
        
        /**
         * Update empty state visibility based on condition
         * 
         * @param {boolean} isEmpty - Whether the container is empty
         */
        updateEmptyState: function(isEmpty) {
            if (isEmpty) {
                this.showEmptyState();
            } else {
                this.hideEmptyState();
            }
        }
    };
})(jQuery);