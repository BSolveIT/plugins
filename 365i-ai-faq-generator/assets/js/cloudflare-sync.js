/**
 * Cloudflare Sync JavaScript functionality.
 * 
 * Handles UI interactions for Cloudflare connection testing and settings sync.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Assets
 * @since 2.1.0
 */

(function($) {
    'use strict';

    /**
     * Cloudflare Sync handler.
     */
    var CloudflareSync = {
        
        /**
         * Initialize the Cloudflare Sync functionality.
         */
        init: function() {
            console.log('[365i AI FAQ] CloudflareSync.init() called');
            this.bindEvents();
            console.log('[365i AI FAQ] Events bound, calling checkInitialStatus()');
            this.checkInitialStatus();
            console.log('[365i AI FAQ] CloudflareSync initialization complete');
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            $(document).on('click', '#test-cloudflare-connection', this.testConnection.bind(this));
            $(document).on('click', '#sync-to-cloudflare', this.syncSettings.bind(this));
        },

        /**
         * Check initial connection and sync status.
         */
        checkInitialStatus: function() {
            console.log('[365i AI FAQ] checkInitialStatus() called');
            
            // Check if we have API credentials to determine initial status
            var accountId = $('#cloudflare_account_id').val();
            var apiToken = $('#cloudflare_api_token').val();
            
            console.log('[365i AI FAQ] Account ID present:', !!accountId);
            console.log('[365i AI FAQ] API Token present:', !!apiToken);
            
            if (!accountId || !apiToken) {
                console.log('[365i AI FAQ] Missing credentials, updating status');
                this.updateConnectionStatus('missing-credentials', 'API credentials required');
                this.updateSyncStatus('disabled', 'Configure API credentials first');
                $('#sync-to-cloudflare').prop('disabled', true);
                return;
            }
            
            console.log('[365i AI FAQ] Credentials found, starting auto-connect sequence');
            
            // If credentials are present, auto-test connection and sync
            var self = this;
            
            // Update status to show initial testing
            this.updateConnectionStatus('testing', 'Auto-testing connection...');
            this.updateSyncStatus('ready', 'Waiting for connection test...');
            
            // Auto-test connection
            console.log('[365i AI FAQ] Scheduling auto-connection test in 500ms');
            setTimeout(function() {
                console.log('[365i AI FAQ] Executing auto-connection test');
                self.performAutoConnectionTest();
            }, 500); // Small delay to let UI update
        },

        /**
         * Perform automatic connection test on page load.
         */
        performAutoConnectionTest: function() {
            console.log('[365i AI FAQ] performAutoConnectionTest() called');
            var self = this;
            
            // Prepare data
            var data = {
                action: 'ai_faq_test_cloudflare_connection',
                nonce: aiFaqCloudflareSync.nonce,
                account_id: $('#cloudflare_account_id').val(),
                api_token: $('#cloudflare_api_token').val()
            };
            
            console.log('[365i AI FAQ] Making auto-connection AJAX request');
            
            // Make AJAX request
            $.ajax({
                url: aiFaqCloudflareSync.ajaxurl,
                type: 'POST',
                data: data,
                timeout: 30000,
                success: function(response) {
                    console.log('[365i AI FAQ] Auto-connection test response:', response);
                    if (response.success) {
                        self.updateConnectionStatus('connected', response.data.message || 'Connection successful');
                        self.updateSyncStatus('ready', 'Ready to sync');
                        $('#sync-to-cloudflare').prop('disabled', false);
                        
                        // Auto-sync if connection successful
                        console.log('[365i AI FAQ] Connection successful, scheduling auto-sync in 1000ms');
                        setTimeout(function() {
                            console.log('[365i AI FAQ] Executing auto-sync');
                            self.performAutoSync();
                        }, 1000);
                    } else {
                        console.log('[365i AI FAQ] Auto-connection test failed:', response.data.message);
                        self.updateConnectionStatus('failed', response.data.message || 'Connection failed');
                        self.updateSyncStatus('error', 'Connection required');
                        $('#sync-to-cloudflare').prop('disabled', true);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[365i AI FAQ] Auto-connection test error:', status, error);
                    var message = 'Auto-connection test failed';
                    
                    if (status === 'timeout') {
                        message = 'Connection test timed out - check your credentials and network';
                    } else if (xhr.responseJSON && xhr.responseJSON.data) {
                        message = xhr.responseJSON.data.message || message;
                    }
                    
                    self.updateConnectionStatus('failed', message);
                    self.updateSyncStatus('error', 'Connection required');
                    $('#sync-to-cloudflare').prop('disabled', true);
                }
            });
        },

        /**
         * Perform automatic sync after successful connection.
         */
        performAutoSync: function() {
            console.log('[365i AI FAQ] performAutoSync() called');
            var self = this;
            
            this.updateSyncStatus('syncing', 'Auto-syncing settings...');
            
            // Prepare data - get all form data
            var formData = $('#settings-form').serialize();
            
            var data = {
                action: 'ai_faq_sync_to_cloudflare',
                nonce: aiFaqCloudflareSync.nonce,
                form_data: formData
            };
            
            console.log('[365i AI FAQ] Making auto-sync AJAX request');
            
            // Make AJAX request
            $.ajax({
                url: aiFaqCloudflareSync.ajaxurl,
                type: 'POST',
                data: data,
                timeout: 45000,
                success: function(response) {
                    console.log('[365i AI FAQ] Auto-sync response:', response);
                    if (response.success) {
                        self.updateSyncStatus('success', 'Settings synced successfully');
                        self.updateLastSyncTime();
                        self.showNotice('success', 'Auto-sync completed: ' + (response.data.message || 'Settings synced to Cloudflare successfully!'));
                    } else {
                        console.log('[365i AI FAQ] Auto-sync failed:', response.data.message);
                        self.updateSyncStatus('error', response.data.message || 'Auto-sync failed');
                        self.showNotice('error', 'Auto-sync failed: ' + (response.data.message || 'Settings sync failed'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[365i AI FAQ] Auto-sync error:', status, error);
                    var message = 'Auto-sync failed';
                    
                    if (status === 'timeout') {
                        message = 'Auto-sync timed out - please try manual sync';
                    } else if (xhr.responseJSON && xhr.responseJSON.data) {
                        message = xhr.responseJSON.data.message || message;
                    }
                    
                    self.updateSyncStatus('error', message);
                    self.showNotice('error', 'Auto-sync failed: ' + message);
                }
            });
        },

        /**
         * Test Cloudflare API connection.
         */
        testConnection: function(e) {
            e.preventDefault();
            
            var $button = $(e.target);
            var originalText = $button.html();
            var self = this;
            
            // Update button state
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Testing...');
            this.updateConnectionStatus('testing', 'Testing connection...');
            
            // Prepare data
            var data = {
                action: 'ai_faq_test_cloudflare_connection',
                nonce: aiFaqCloudflareSync.nonce,
                account_id: $('#cloudflare_account_id').val(),
                api_token: $('#cloudflare_api_token').val()
            };
            
            // Make AJAX request
            $.ajax({
                url: aiFaqCloudflareSync.ajaxurl,
                type: 'POST',
                data: data,
                timeout: 30000,
                success: function(response) {
                    self.handleConnectionTestSuccess(response);
                    // Restore button state in success callback
                    $button.prop('disabled', false).html(originalText);
                },
                error: function(xhr, status, error) {
                    self.handleConnectionTestError(xhr, status, error);
                    // Restore button state in error callback
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },

        /**
         * Handle successful connection test.
         */
        handleConnectionTestSuccess: function(response) {
            if (response.success) {
                this.updateConnectionStatus('connected', response.data.message || 'Connection successful');
                this.updateSyncStatus('ready', 'Ready to sync');
                $('#sync-to-cloudflare').prop('disabled', false);
                this.showNotice('success', response.data.message || 'Cloudflare connection test successful!');
            } else {
                this.updateConnectionStatus('failed', response.data.message || 'Connection failed');
                this.updateSyncStatus('error', 'Connection required');
                $('#sync-to-cloudflare').prop('disabled', true);
                this.showNotice('error', response.data.message || 'Connection test failed');
            }
        },

        /**
         * Handle connection test error.
         */
        handleConnectionTestError: function(xhr, status, error) {
            var message = 'Connection test failed';
            
            if (status === 'timeout') {
                message = 'Connection test timed out - check your credentials and network';
            } else if (xhr.responseJSON && xhr.responseJSON.data) {
                message = xhr.responseJSON.data.message || message;
            }
            
            this.updateConnectionStatus('failed', message);
            this.updateSyncStatus('error', 'Connection required');
            $('#sync-to-cloudflare').prop('disabled', true);
            this.showNotice('error', message);
        },

        /**
         * Sync settings to Cloudflare KV.
         */
        syncSettings: function(e) {
            e.preventDefault();
            
            var $button = $(e.target);
            var originalText = $button.html();
            var self = this;
            
            // Update button state
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Syncing...');
            this.updateSyncStatus('syncing', 'Syncing settings to Cloudflare...');
            
            // Prepare data - get all form data
            var formData = $('#settings-form').serialize();
            
            var data = {
                action: 'ai_faq_sync_to_cloudflare',
                nonce: aiFaqCloudflareSync.nonce,
                form_data: formData
            };
            
            // Make AJAX request
            $.ajax({
                url: aiFaqCloudflareSync.ajaxurl,
                type: 'POST',
                data: data,
                timeout: 45000,
                success: function(response) {
                    self.handleSyncSuccess(response);
                    // Restore button state in success callback
                    $button.prop('disabled', false).html(originalText);
                },
                error: function(xhr, status, error) {
                    self.handleSyncError(xhr, status, error);
                    // Restore button state in error callback
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },

        /**
         * Handle successful sync.
         */
        handleSyncSuccess: function(response) {
            if (response.success) {
                this.updateSyncStatus('success', 'Settings synced successfully');
                this.updateLastSyncTime();
                this.showNotice('success', response.data.message || 'Settings synced to Cloudflare successfully!');
            } else {
                this.updateSyncStatus('error', response.data.message || 'Sync failed');
                this.showNotice('error', response.data.message || 'Settings sync failed');
            }
        },

        /**
         * Handle sync error.
         */
        handleSyncError: function(xhr, status, error) {
            var message = 'Settings sync failed';
            
            if (status === 'timeout') {
                message = 'Sync timed out - please try again';
            } else if (xhr.responseJSON && xhr.responseJSON.data) {
                message = xhr.responseJSON.data.message || message;
            }
            
            this.updateSyncStatus('error', message);
            this.showNotice('error', message);
        },

        /**
         * Update connection status display.
         */
        updateConnectionStatus: function(status, message) {
            var $statusEl = $('#cloudflare-connection-status .status-indicator');
            var iconClass = 'dashicons-warning';
            var statusClass = 'unknown';
            
            switch (status) {
                case 'connected':
                    iconClass = 'dashicons-yes-alt';
                    statusClass = 'connected';
                    break;
                case 'failed':
                    iconClass = 'dashicons-dismiss';
                    statusClass = 'failed';
                    break;
                case 'testing':
                    iconClass = 'dashicons-update spin';
                    statusClass = 'testing';
                    break;
                case 'missing-credentials':
                    iconClass = 'dashicons-warning';
                    statusClass = 'missing-credentials';
                    break;
            }
            
            // Remove all previous classes including spin
            $statusEl.removeClass('unknown connected failed testing missing-credentials')
                     .addClass(statusClass)
                     .find('.dashicons')
                     .removeClass('dashicons-warning dashicons-yes-alt dashicons-dismiss dashicons-update spin')
                     .addClass(iconClass);
            
            // Clear previous text content
            $statusEl.contents().filter(function() {
                return this.nodeType === 3; // Text nodes
            }).remove();
            
            // Update with new message
            $statusEl.append(' ' + message);
        },

        /**
         * Update sync status display.
         */
        updateSyncStatus: function(status, message) {
            var $statusEl = $('#cloudflare-sync-status .status-indicator');
            var $textEl = $('#sync-status-text');
            var iconClass = 'dashicons-admin-generic';
            var statusClass = 'unknown';
            
            switch (status) {
                case 'success':
                    iconClass = 'dashicons-yes-alt';
                    statusClass = 'success';
                    break;
                case 'error':
                    iconClass = 'dashicons-dismiss';
                    statusClass = 'error';
                    break;
                case 'syncing':
                    iconClass = 'dashicons-update spin';
                    statusClass = 'syncing';
                    break;
                case 'ready':
                    iconClass = 'dashicons-admin-generic';
                    statusClass = 'ready';
                    break;
                case 'disabled':
                    iconClass = 'dashicons-warning';
                    statusClass = 'disabled';
                    break;
            }
            
            // Remove all previous classes including spin
            $statusEl.removeClass('unknown success error syncing ready disabled')
                     .addClass(statusClass)
                     .find('.dashicons')
                     .removeClass('dashicons-admin-generic dashicons-yes-alt dashicons-dismiss dashicons-update dashicons-warning spin')
                     .addClass(iconClass);
            
            $textEl.text(message);
        },

        /**
         * Update last sync time display.
         */
        updateLastSyncTime: function() {
            var now = new Date();
            var timeString = now.toLocaleString();
            $('#last-sync-time').text('Last synced: ' + timeString);
        },

        /**
         * Show admin notice.
         */
        showNotice: function(type, message) {
            // Remove existing notices
            $('.ai-faq-cloudflare-notice').remove();
            
            // Create new notice
            var $notice = $('<div class="notice ai-faq-cloudflare-notice is-dismissible notice-' + type + '">' +
                           '<p>' + message + '</p>' +
                           '</div>');
            
            // Insert after page title
            $('.ai-faq-gen-settings').prepend($notice);
            
            // Auto-dismiss success notices after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $notice.fadeOut(function() {
                        $notice.remove();
                    });
                }, 5000);
            }
            
            // Scroll to top to show notice
            $('html, body').animate({ scrollTop: 0 }, 500);
        }
    };

    // Initialize when document is ready (with duplicate prevention)
    $(document).ready(function() {
        // Prevent multiple initializations
        if (window.aiFaqCloudflareSyncInitialized) {
            console.log('[365i AI FAQ] Cloudflare Sync already initialized, skipping...');
            return;
        }

        console.log('[365i AI FAQ] Cloudflare Sync JS loaded, checking for elements...');
        console.log('[365i AI FAQ] Test connection button exists:', $('#test-cloudflare-connection').length > 0);
        console.log('[365i AI FAQ] Sync button exists:', $('#sync-to-cloudflare').length > 0);
        console.log('[365i AI FAQ] Account ID field exists:', $('#cloudflare_account_id').length > 0);
        console.log('[365i AI FAQ] API Token field exists:', $('#cloudflare_api_token').length > 0);
        
        // Only initialize if we're on the settings page with Cloudflare sync elements
        if ($('#test-cloudflare-connection').length) {
            console.log('[365i AI FAQ] Initializing Cloudflare Sync...');
            CloudflareSync.init();
            window.aiFaqCloudflareSyncInitialized = true;
        } else {
            console.log('[365i AI FAQ] Cloudflare Sync elements not found, skipping initialization');
        }
    });

    // Add CSS for spinner animation and status indicators
    var styles = `
        <style>
        .dashicons.spin {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .connection-status .status-indicator,
        .sync-status .status-indicator {
            padding: 8px 12px;
            border-radius: 4px;
            display: inline-block;
            font-weight: 500;
        }
        
        .status-indicator.connected {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        
        .status-indicator.failed,
        .status-indicator.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-indicator.testing,
        .status-indicator.syncing {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #b8daff;
        }
        
        .status-indicator.unknown,
        .status-indicator.ready {
            background-color: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
        }
        
        .status-indicator.missing-credentials,
        .status-indicator.disabled {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-indicator.success {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        
        .last-sync-time {
            font-size: 0.9em;
            color: #666;
            font-style: italic;
        }
        
        .ai-faq-cloudflare-notice {
            margin: 20px 0;
        }
        </style>
    `;
    
    $('head').append(styles);

})(jQuery);