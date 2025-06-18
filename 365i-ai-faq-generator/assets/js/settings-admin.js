/**
 * Settings admin JavaScript for 365i AI FAQ Generator.
 * 
 * Handles all settings page functionality including save, test API,
 * reset defaults, import/export settings with proper visual feedback.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Assets
 * @since 2.0.1
 */

(function($) {
    'use strict';

    /**
     * Track if initialization has already occurred.
     */
    let isInitialized = false;

    /**
     * Initialize settings admin functionality.
     */
    function initSettingsAdmin() {
        // Prevent multiple initializations
        if (isInitialized) {
            return;
        }

        initFormHandlers();
        initPasswordToggles();
        initImportExport();
        initTestApiConnection();
        initResetDefaults();
        
        isInitialized = true;
    }

    /**
     * Initialize form submission handlers.
     */
    function initFormHandlers() {
        const settingsForm = document.getElementById('settings-form');
        if (!settingsForm) {
            return;
        }

        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveSettings();
        });
    }

    /**
     * Initialize password toggle functionality.
     */
    function initPasswordToggles() {
        const toggleButtons = document.querySelectorAll('.toggle-password');
        toggleButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const targetInput = document.getElementById(targetId);
                const icon = this.querySelector('.dashicons');
                
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    icon.className = 'dashicons dashicons-hidden';
                } else {
                    targetInput.type = 'password';
                    icon.className = 'dashicons dashicons-visibility';
                }
            });
        });
    }

    /**
     * Initialize import/export functionality.
     */
    function initImportExport() {
        const importButton = document.querySelector('.import-settings');
        const exportButton = document.querySelector('.export-settings');
        const importModal = document.getElementById('import-settings-modal');
        const closeModal = document.querySelector('.close-modal');
        const importForm = document.getElementById('import-form');
        
        if (importButton && importModal) {
            importButton.addEventListener('click', function() {
                importModal.style.display = 'block';
            });
        }
        
        if (closeModal && importModal) {
            closeModal.addEventListener('click', function() {
                importModal.style.display = 'none';
            });
        }

        // Close modal when clicking outside
        if (importModal) {
            importModal.addEventListener('click', function(e) {
                if (e.target === importModal) {
                    importModal.style.display = 'none';
                }
            });
        }
        
        if (exportButton) {
            exportButton.addEventListener('click', function() {
                exportSettings();
            });
        }

        if (importForm) {
            importForm.addEventListener('submit', function(e) {
                e.preventDefault();
                importSettings();
            });
        }
    }

    /**
     * Initialize test API connection functionality.
     */
    function initTestApiConnection() {
        const testButton = document.querySelector('.test-api-connection');
        if (!testButton) {
            return;
        }

        testButton.addEventListener('click', function(e) {
            e.preventDefault();
            testApiConnection();
        });
    }

    /**
     * Initialize reset defaults functionality.
     */
    function initResetDefaults() {
        const resetButton = document.querySelector('.reset-settings');
        if (!resetButton) {
            return;
        }

        resetButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to reset all settings to defaults? This action cannot be undone.')) {
                resetToDefaults();
            }
        });
    }

    /**
     * Save settings via AJAX.
     */
    function saveSettings() {
        const form = document.getElementById('settings-form');
        const submitButton = form.querySelector('button[type="submit"]');
        const buttonText = submitButton.querySelector('span:not(.dashicons)');
        const buttonIcon = submitButton.querySelector('.dashicons');
        const originalText = buttonText ? buttonText.textContent : 'Save Settings';
        
        // Show loading state
        submitButton.disabled = true;
        if (buttonText) {
            buttonText.textContent = 'Saving...';
        }
        if (buttonIcon) {
            buttonIcon.style.animation = 'spin 1s linear infinite';
        }
        
        // Collect form data
        const formData = new FormData(form);
        const settings = {};
        
        // Convert FormData to nested object structure
        for (let [key, value] of formData.entries()) {
            if (key !== '_wpnonce') {
                settings[key] = value;
            }
        }
        
        // Handle checkboxes (they won't be in FormData if unchecked)
        const checkboxes = form.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(function(checkbox) {
            if (!formData.has(checkbox.name)) {
                settings[checkbox.name] = '0';
            }
        });

        // Make AJAX request
        $.ajax({
            url: aiFaqGen.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ai_faq_save_settings',
                nonce: aiFaqGen.nonce,
                settings: settings
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Settings saved successfully!', 'success');
                    
                    // Show success state on button
                    if (buttonText) {
                        buttonText.textContent = '✓ Saved';
                        submitButton.style.backgroundColor = '#00a32a';
                        submitButton.style.color = 'white';
                        submitButton.style.borderColor = '#00a32a';
                        
                        setTimeout(function() {
                            buttonText.textContent = originalText;
                            submitButton.style.backgroundColor = '';
                            submitButton.style.color = '';
                            submitButton.style.borderColor = '';
                        }, 3000);
                    }
                } else {
                    showNotification('Failed to save settings: ' + (response.data || 'Unknown error'), 'error');
                    
                    // Show error state on button
                    if (buttonText) {
                        buttonText.textContent = '✗ Failed';
                        submitButton.style.backgroundColor = '#d63638';
                        submitButton.style.color = 'white';
                        submitButton.style.borderColor = '#d63638';
                        
                        setTimeout(function() {
                            buttonText.textContent = originalText;
                            submitButton.style.backgroundColor = '';
                            submitButton.style.color = '';
                            submitButton.style.borderColor = '';
                        }, 3000);
                    }
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error saving settings: ' + error, 'error');
                
                // Show error state on button
                if (buttonText) {
                    buttonText.textContent = '✗ Error';
                    submitButton.style.backgroundColor = '#d63638';
                    submitButton.style.color = 'white';
                    submitButton.style.borderColor = '#d63638';
                    
                    setTimeout(function() {
                        buttonText.textContent = originalText;
                        submitButton.style.backgroundColor = '';
                        submitButton.style.color = '';
                        submitButton.style.borderColor = '';
                    }, 3000);
                }
            },
            complete: function() {
                // Reset button state
                submitButton.disabled = false;
                if (buttonIcon) {
                    buttonIcon.style.animation = '';
                }
            }
        });
    }

    /**
     * Test API connection.
     */
    function testApiConnection() {
        const testButton = document.querySelector('.test-api-connection');
        const buttonText = testButton.querySelector('span:not(.dashicons)');
        const buttonIcon = testButton.querySelector('.dashicons');
        const originalText = buttonText ? buttonText.textContent : 'Test API Connection';
        
        // Show loading state
        testButton.disabled = true;
        if (buttonText) {
            buttonText.textContent = 'Testing...';
        }
        if (buttonIcon) {
            buttonIcon.style.animation = 'spin 1s linear infinite';
        }

        // Get API credentials from form
        const accountId = document.getElementById('cloudflare_account_id').value;
        const apiToken = document.getElementById('cloudflare_api_token').value;

        if (!accountId || !apiToken) {
            showNotification('Please enter Cloudflare Account ID and API Token first.', 'warning');
            resetTestButton();
            return;
        }

        // Make AJAX request
        $.ajax({
            url: aiFaqGen.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ai_faq_test_api_connection',
                nonce: aiFaqGen.nonce,
                account_id: accountId,
                api_token: apiToken
            },
            success: function(response) {
                if (response.success) {
                    showNotification('API connection successful! ' + (response.data.message || ''), 'success');
                    
                    // Show success state on button
                    if (buttonText) {
                        buttonText.textContent = '✓ Connected';
                        testButton.style.backgroundColor = '#00a32a';
                        testButton.style.color = 'white';
                        testButton.style.borderColor = '#00a32a';
                        
                        setTimeout(function() {
                            buttonText.textContent = originalText;
                            testButton.style.backgroundColor = '';
                            testButton.style.color = '';
                            testButton.style.borderColor = '';
                        }, 3000);
                    }
                } else {
                    showNotification('API connection failed: ' + (response.data || 'Unknown error'), 'error');
                    showErrorState(testButton, buttonText, originalText, '✗ Failed');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error testing API connection: ' + error, 'error');
                showErrorState(testButton, buttonText, originalText, '✗ Error');
            },
            complete: function() {
                resetTestButton();
            }
        });

        function resetTestButton() {
            testButton.disabled = false;
            if (buttonIcon) {
                buttonIcon.style.animation = '';
            }
        }
    }

    /**
     * Export settings as JSON file.
     */
    function exportSettings() {
        const exportButton = document.querySelector('.export-settings');
        const buttonText = exportButton.querySelector('span:not(.dashicons)');
        const originalText = buttonText ? buttonText.textContent : 'Export Settings';
        
        // Show processing state
        if (buttonText) {
            buttonText.textContent = 'Exporting...';
        }
        
        try {
            // Collect all form data
            const form = document.getElementById('settings-form');
            const formData = new FormData(form);
            const settings = {};
            
            for (let [key, value] of formData.entries()) {
                if (key !== '_wpnonce') {
                    settings[key] = value;
                }
            }
            
            // Handle checkboxes
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(function(checkbox) {
                settings[checkbox.name] = checkbox.checked ? '1' : '0';
            });
            
            // Add metadata
            const exportData = {
                plugin: '365i AI FAQ Generator',
                version: '2.0.1',
                exported_at: new Date().toISOString(),
                settings: settings
            };
            
            // Create and download file
            const blob = new Blob([JSON.stringify(exportData, null, 2)], {type: 'application/json'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'ai-faq-gen-settings-' + new Date().toISOString().split('T')[0] + '.json';
            a.click();
            URL.revokeObjectURL(url);
            
            showNotification('Settings exported successfully!', 'success');
            
            // Show success feedback
            if (buttonText) {
                buttonText.textContent = '✓ Exported';
                setTimeout(function() {
                    buttonText.textContent = originalText;
                }, 2000);
            }
        } catch (error) {
            showNotification('Failed to export settings: ' + error.message, 'error');
            if (buttonText) {
                buttonText.textContent = originalText;
            }
        }
    }

    /**
     * Import settings from JSON file.
     */
    function importSettings() {
        const importForm = document.getElementById('import-form');
        const submitButton = importForm.querySelector('button[type="submit"]');
        const fileInput = importForm.querySelector('input[type="file"]');
        const originalText = submitButton.textContent;
        
        if (!fileInput.files[0]) {
            showNotification('Please select a file to import.', 'warning');
            return;
        }
        
        // Show loading state
        submitButton.disabled = true;
        submitButton.textContent = 'Importing...';
        
        const file = fileInput.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            try {
                const importData = JSON.parse(e.target.result);
                
                // Validate import data
                if (!importData.settings) {
                    throw new Error('Invalid settings file format');
                }
                
                // Make AJAX request to import
                $.ajax({
                    url: aiFaqGen.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ai_faq_import_settings',
                        nonce: aiFaqGen.nonce,
                        settings: importData.settings
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('Settings imported successfully! Page will reload.', 'success');
                            
                            // Close modal and reload page after delay
                            setTimeout(function() {
                                document.getElementById('import-settings-modal').style.display = 'none';
                                location.reload();
                            }, 2000);
                        } else {
                            showNotification('Failed to import settings: ' + (response.data || 'Unknown error'), 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showNotification('Error importing settings: ' + error, 'error');
                    },
                    complete: function() {
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                });
                
            } catch (error) {
                showNotification('Invalid settings file: ' + error.message, 'error');
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        };
        
        reader.onerror = function() {
            showNotification('Failed to read file.', 'error');
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        };
        
        reader.readAsText(file);
    }

    /**
     * Reset settings to defaults.
     */
    function resetToDefaults() {
        const resetButton = document.querySelector('.reset-settings');
        const buttonText = resetButton.querySelector('span:not(.dashicons)');
        const buttonIcon = resetButton.querySelector('.dashicons');
        const originalText = buttonText ? buttonText.textContent : 'Reset to Defaults';
        
        // Show loading state
        resetButton.disabled = true;
        if (buttonText) {
            buttonText.textContent = 'Resetting...';
        }
        if (buttonIcon) {
            buttonIcon.style.animation = 'spin 1s linear infinite';
        }

        // Make AJAX request
        $.ajax({
            url: aiFaqGen.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ai_faq_reset_settings',
                nonce: aiFaqGen.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Settings reset to defaults successfully! Page will reload.', 'success');
                    
                    // Show success state and reload
                    if (buttonText) {
                        buttonText.textContent = '✓ Reset';
                    }
                    
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotification('Failed to reset settings: ' + (response.data || 'Unknown error'), 'error');
                    showErrorState(resetButton, buttonText, originalText, '✗ Failed');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error resetting settings: ' + error, 'error');
                showErrorState(resetButton, buttonText, originalText, '✗ Error');
            },
            complete: function() {
                resetButton.disabled = false;
                if (buttonIcon) {
                    buttonIcon.style.animation = '';
                }
            }
        });
    }

    /**
     * Show error state on button.
     */
    function showErrorState(button, buttonText, originalText, errorText) {
        if (buttonText) {
            buttonText.textContent = errorText;
            button.style.backgroundColor = '#d63638';
            button.style.color = 'white';
            button.style.borderColor = '#d63638';
            
            setTimeout(function() {
                buttonText.textContent = originalText;
                button.style.backgroundColor = '';
                button.style.color = '';
                button.style.borderColor = '';
            }, 3000);
        }
    }

    /**
     * Show notification message.
     * Remove any existing notifications to prevent duplicates.
     * 
     * @param {string} message - Message to display
     * @param {string} type - Notification type (success, error, warning, info)
     */
    function showNotification(message, type) {
        type = type || 'info';
        
        // Remove any existing notifications to prevent duplicates
        const existingNotifications = document.querySelectorAll('.settings-notice');
        existingNotifications.forEach(function(notification) {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        });
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'notice notice-' + type + ' is-dismissible settings-notice';
        notification.innerHTML = '<p><strong>' + message + '</strong></p>';
        notification.style.fontSize = '14px';
        notification.style.fontWeight = 'bold';
        notification.style.margin = '15px 0';
        notification.style.padding = '12px';
        notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
        
        // Find appropriate location to insert notification
        const targetElement = document.querySelector('.ai-faq-gen-settings') || 
                            document.querySelector('.wrap') || 
                            document.body;
        
        // Insert notification at the top
        targetElement.insertBefore(notification, targetElement.firstChild);
        
        // Scroll to notification
        notification.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Auto-remove after 6 seconds
        setTimeout(function() {
            if (notification.parentNode) {
                notification.style.transition = 'opacity 0.5s ease';
                notification.style.opacity = '0';
                setTimeout(function() {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 500);
            }
        }, 6000);
    }

    /**
     * Add required CSS styles.
     */
    function addRequiredStyles() {
        // Check if styles are already added
        if (document.getElementById('settings-admin-styles')) {
            return;
        }

        const styles = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .settings-notice {
                margin: 15px 0;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .import-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                z-index: 100000;
            }
            .modal-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 20px;
                border-radius: 4px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                min-width: 400px;
            }
            .modal-actions {
                margin-top: 15px;
                text-align: right;
            }
            .modal-actions .button {
                margin-left: 10px;
            }
        `;
        
        const styleSheet = document.createElement('style');
        styleSheet.type = 'text/css';
        styleSheet.id = 'settings-admin-styles';
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }

    /**
     * Safe initialization function that prevents duplicate initialization.
     */
    function safeInit() {
        if (isInitialized) {
            return;
        }
        
        initSettingsAdmin();
        addRequiredStyles();
    }

    // Initialize when document is ready - use only one method to prevent duplicates
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', safeInit);
    } else {
        // Document is already loaded
        safeInit();
    }

})(jQuery);