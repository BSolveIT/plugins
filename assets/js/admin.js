/**
 * 365i Queue Optimizer Admin JavaScript
 *
 * Comprehensive JavaScript functionality for WordPress admin pages.
 *
 * @package QueueOptimizer
 */

(function($) {
	'use strict';

	/**
	 * Admin functionality namespace
	 */
	window.QueueOptimizerAdmin = {
		
		/**
		 * Initialize all admin functionality
		 */
		init: function() {
			this.bindEvents();
			this.initializeTooltips();
			this.initializeSearch();
			this.initializeExport();
			this.initializeDashboard();
			this.initializeSystemInfo();
		},

		/**
		 * Bind global event listeners
		 */
		bindEvents: function() {
			$(document).ready(function() {
				QueueOptimizerAdmin.init();
			});

			// Global copy to clipboard functionality
			$(document).on('click', '[data-copy]', function(e) {
				e.preventDefault();
				QueueOptimizerAdmin.copyToClipboard($(this).data('copy'));
			});

			// Global refresh functionality
			$(document).on('click', '[data-refresh]', function(e) {
				e.preventDefault();
				QueueOptimizerAdmin.refreshData($(this).data('refresh'));
			});

			// Global export functionality
			$(document).on('click', '[data-export]', function(e) {
				e.preventDefault();
				QueueOptimizerAdmin.exportData($(this).data('export'));
			});
		},

		/**
		 * Initialize tooltips for better UX
		 */
		initializeTooltips: function() {
			// Tooltips removed - jQuery UI not loaded
			// Title attributes will show native browser tooltips
		},

		/**
		 * Initialize search functionality
		 */
		initializeSearch: function() {
			// Global search input handler
			$('#system-info-search, #plugins-search, #extensions-search').on('input', function() {
				var query = $(this).val().toLowerCase();
				var targetTable = $(this).closest('.components-card').find('table');
				var targetItems = $(this).closest('.components-card').find('[data-plugin-name], [data-extension-name]');
				
				if (targetTable.length) {
					QueueOptimizerAdmin.filterTable(targetTable, query);
				}
				
				if (targetItems.length) {
					QueueOptimizerAdmin.filterItems(targetItems, query);
				}
			});
		},

		/**
		 * Filter table rows based on search query
		 */
		filterTable: function(table, query) {
			var rows = table.find('tbody tr');
			var visibleCount = 0;

			rows.each(function() {
				var row = $(this);
				var text = row.text().toLowerCase();
				
				if (text.indexOf(query) !== -1 || query === '') {
					row.show();
					visibleCount++;
				} else {
					row.hide();
				}
			});

			// Update search results info
			this.updateSearchResults(table, visibleCount, rows.length);
		},

		/**
		 * Filter items based on search query
		 */
		filterItems: function(items, query) {
			var visibleCount = 0;

			items.each(function() {
				var item = $(this);
				var searchText = item.data('plugin-name') || item.data('extension-name') || '';
				
				if (searchText.indexOf(query) !== -1 || query === '') {
					item.show();
					visibleCount++;
				} else {
					item.hide();
				}
			});

			// Update search results info
			this.updateSearchResults(items.parent(), visibleCount, items.length);
		},

		/**
		 * Update search results information
		 */
		updateSearchResults: function(container, visible, total) {
			var info = container.find('.search-results-info');
			
			if (info.length === 0) {
				info = $('<div class="search-results-info" style="margin-top: 8px; font-size: 12px; color: #646970;"></div>');
				container.append(info);
			}
			
			if (visible === total) {
				info.text('Showing all ' + total + ' items');
			} else {
				info.text('Showing ' + visible + ' of ' + total + ' items');
			}
		},

		/**
		 * Initialize export functionality
		 */
		initializeExport: function() {
			$('#export-json').on('click', function() {
				QueueOptimizerAdmin.exportJSON();
			});

			$('#export-csv').on('click', function() {
				QueueOptimizerAdmin.exportCSV();
			});
		},

		/**
		 * Export data as JSON
		 */
		exportJSON: function() {
			var data = window.queueOptimizerSystemInfo || window.queueOptimizerDashboard || {};
			var filename = 'queue-optimizer-' + new Date().toISOString().split('T')[0] + '.json';
			
			this.downloadFile(JSON.stringify(data, null, 2), filename, 'application/json');
			this.showNotification('System information exported as JSON', 'success');
		},

		/**
		 * Export data as CSV
		 */
		exportCSV: function() {
			var csv = this.convertToCSV();
			var filename = 'queue-optimizer-' + new Date().toISOString().split('T')[0] + '.csv';
			
			this.downloadFile(csv, filename, 'text/csv');
			this.showNotification('System information exported as CSV', 'success');
		},

		/**
		 * Convert system data to CSV format
		 */
		convertToCSV: function() {
			var data = window.queueOptimizerSystemInfo || {};
			var csv = 'Category,Property,Value\n';
			
			// Flatten the data structure for CSV export
			for (var category in data) {
				if (data.hasOwnProperty(category) && typeof data[category] === 'object') {
					for (var property in data[category]) {
						if (data[category].hasOwnProperty(property)) {
							var value = data[category][property];
							if (typeof value === 'object') {
								value = JSON.stringify(value);
							}
							csv += '"' + category + '","' + property + '","' + value + '"\n';
						}
					}
				}
			}
			
			return csv;
		},

		/**
		 * Download file helper
		 */
		downloadFile: function(content, filename, contentType) {
			var blob = new Blob([content], { type: contentType });
			var url = window.URL.createObjectURL(blob);
			var a = document.createElement('a');
			
			a.style.display = 'none';
			a.href = url;
			a.download = filename;
			document.body.appendChild(a);
			a.click();
			window.URL.revokeObjectURL(url);
			document.body.removeChild(a);
		},

		/**
		 * Initialize dashboard-specific functionality
		 */
		initializeDashboard: function() {
			// Auto-refresh removed - manual refresh only
			
			// Quick action buttons
			$('.quick-action-btn').on('click', function(e) {
				e.preventDefault();
				var action = $(this).data('action');
				QueueOptimizerAdmin.executeQuickAction(action, $(this));
			});

			// Stats card hover effects
			$('.stats-card').hover(
				function() { $(this).addClass('hover'); },
				function() { $(this).removeClass('hover'); }
			);

			// Queue Optimizer dashboard panel button handlers
			this.initializeDashboardButtons();
		},

		/**
		 * Initialize dashboard panel button event handlers
		 */
		initializeDashboardButtons: function() {
			// Run Queue Now button
			$(document).on('click', '#run-queue-now', function(e) {
				e.preventDefault();
				QueueOptimizerAdmin.handleRunQueueNow($(this));
			});

			// View Logs button
			$(document).on('click', '#view-logs', function(e) {
				e.preventDefault();
				QueueOptimizerAdmin.handleViewLogs();
			});

			// Clear Plugin Logs button
			$(document).on('click', '#clear-logs', function(e) {
				e.preventDefault();
				QueueOptimizerAdmin.handleClearLogs($(this));
			});

			// Clear Action Scheduler Logs button
			$(document).on('click', '#clear-action-scheduler-logs', function(e) {
				e.preventDefault();
				QueueOptimizerAdmin.handleClearActionSchedulerLogs($(this));
			});

			// Refresh Logs button
			$(document).on('click', '#refresh-logs', function(e) {
				e.preventDefault();
				QueueOptimizerAdmin.loadLogs();
			});

			// Close Logs button
			$(document).on('click', '#close-logs', function(e) {
				e.preventDefault();
				$('#queue-optimizer-logs').hide();
			});
		},

		/**
		 * Handle Run Queue Now button click
		 */
		handleRunQueueNow: function(button) {
			button.prop('disabled', true).text(queueOptimizerAdmin.strings.processing);
			
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_run_now',
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						QueueOptimizerAdmin.showNotification(response.data.message, 'success');
						// Update dashboard stats if available
						if (response.data.status) {
							QueueOptimizerAdmin.updateQueueStatus(response.data.status);
						}
					} else {
						QueueOptimizerAdmin.showNotification(response.data.message || queueOptimizerAdmin.strings.error, 'error');
					}
				},
				error: function() {
					QueueOptimizerAdmin.showNotification(queueOptimizerAdmin.strings.error, 'error');
				},
				complete: function() {
					button.prop('disabled', false).text('Run Now');
				}
			});
		},

		/**
		 * Handle View Logs button click
		 */
		handleViewLogs: function() {
			var logsContainer = $('#queue-optimizer-logs');
			
			if (logsContainer.is(':visible')) {
				logsContainer.hide();
			} else {
				logsContainer.show();
				this.loadLogs();
			}
		},

		/**
		 * Load logs content via AJAX
		 */
		loadLogs: function() {
			var logDisplay = $('#log-display');
			
			logDisplay.html('<span style="color: #666;">Loading logs...</span>');
			
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_get_logs',
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						logDisplay.text(response.data.logs || 'No logs available.');
					} else {
						logDisplay.text('Error loading logs: ' + (response.data.message || 'Unknown error'));
					}
				},
				error: function() {
					logDisplay.text('Network error occurred while loading logs.');
				}
			});
		},

		/**
		 * Handle Clear Plugin Logs button click
		 */
		handleClearLogs: function(button) {
			if (!confirm('Are you sure you want to clear all plugin logs? This action cannot be undone.')) {
				return;
			}

			button.prop('disabled', true).text(queueOptimizerAdmin.strings.processing);
			
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_clear_logs',
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						QueueOptimizerAdmin.showNotification(response.data.message, 'success');
						// Clear the log display
						$('#log-display').text('Logs have been cleared.');
					} else {
						QueueOptimizerAdmin.showNotification(response.data.message || queueOptimizerAdmin.strings.error, 'error');
					}
				},
				error: function() {
					QueueOptimizerAdmin.showNotification(queueOptimizerAdmin.strings.error, 'error');
				},
				complete: function() {
					button.prop('disabled', false).text('Clear Plugin Logs');
				}
			});
		},

		/**
		 * Handle Clear Action Scheduler Logs button click
		 */
		handleClearActionSchedulerLogs: function(button) {
			if (!confirm('Are you sure you want to clear all Action Scheduler logs? This action cannot be undone.')) {
				return;
			}

			button.prop('disabled', true).text(queueOptimizerAdmin.strings.processing);
			
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_clear_action_scheduler_logs',
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						QueueOptimizerAdmin.showNotification(response.data.message, 'success');
					} else {
						QueueOptimizerAdmin.showNotification(response.data.message || queueOptimizerAdmin.strings.error, 'error');
					}
				},
				error: function() {
					QueueOptimizerAdmin.showNotification(queueOptimizerAdmin.strings.error, 'error');
				},
				complete: function() {
					button.prop('disabled', false).text('Clear Action Scheduler Logs');
				}
			});
		},

		/**
		 * Update queue status display
		 */
		updateQueueStatus: function(status) {
			if (status.pending !== undefined) {
				$('#pending-count').text(this.formatNumber(status.pending));
			}
			if (status.processing !== undefined) {
				$('#processing-count').text(this.formatNumber(status.processing));
			}
			if (status.completed !== undefined) {
				$('#completed-count').text(this.formatNumber(status.completed));
			}
			if (status.failed !== undefined) {
				$('#failed-count').text(this.formatNumber(status.failed));
			}
		},

		/**
		 * Refresh dashboard statistics
		 */
		refreshDashboardStats: function() {
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_refresh_stats',
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						QueueOptimizerAdmin.updateDashboardStats(response.data);
					}
				}
			});
		},

		/**
		 * Update dashboard statistics display
		 */
		updateDashboardStats: function(data) {
			// Update each stat card
			$('.stats-card').each(function() {
				var card = $(this);
				var metric = card.data('metric');
				
				if (data[metric] !== undefined) {
					card.find('.stats-card__value').text(data[metric]);
					card.addClass('updated');
					
					setTimeout(function() {
						card.removeClass('updated');
					}, 1000);
				}
			});
		},

		/**
		 * Execute quick actions
		 */
		executeQuickAction: function(action, button) {
			button.prop('disabled', true).addClass('loading');
			
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_quick_action',
					quick_action: action,
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						QueueOptimizerAdmin.showNotification(response.data.message, 'success');
						// Refresh relevant data
						setTimeout(function() {
							QueueOptimizerAdmin.refreshDashboardStats();
						}, 1000);
					} else {
						QueueOptimizerAdmin.showNotification(response.data.message || 'Action failed', 'error');
					}
				},
				error: function() {
					QueueOptimizerAdmin.showNotification('Network error occurred', 'error');
				},
				complete: function() {
					button.prop('disabled', false).removeClass('loading');
				}
			});
		},

		/**
		 * Initialize system info specific functionality
		 */
		initializeSystemInfo: function() {
			// Removed card header click handler to prevent conflicts with WordPress postbox
			// WordPress postbox functionality handles collapsing/expanding automatically
			
			// Plugin list export
			$('#export-plugin-list').on('click', function() {
				QueueOptimizerAdmin.exportPluginList();
			});
		},

		/**
		 * Export plugin list functionality
		 */
		exportPluginList: function() {
			var plugins = window.queueOptimizerSystemInfo.plugins || {};
			var csv = 'Plugin Name,Version,Status,Author,Description\n';
			
			for (var pluginFile in plugins) {
				if (plugins.hasOwnProperty(pluginFile)) {
					var plugin = plugins[pluginFile];
					var status = plugin.is_active ? 'Active' : 'Inactive';
					if (plugin.is_mu_plugin) status = 'Must-Use';
					
					csv += '"' + (plugin.Name || '') + '","' + 
						   (plugin.Version || '') + '","' + 
						   status + '","' + 
						   (plugin.Author || '') + '","' + 
						   (plugin.Description || '').replace(/"/g, '""') + '"\n';
				}
			}
			
			this.downloadFile(csv, 'plugins-list-' + new Date().toISOString().split('T')[0] + '.csv', 'text/csv');
			this.showNotification('Plugin list exported successfully', 'success');
		},

		/**
		 * Copy to clipboard functionality
		 */
		copyToClipboard: function(elementId) {
			var element = document.getElementById(elementId);
			if (!element) return;
			
			var textToCopy = this.extractTextFromElement(element);
			
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(textToCopy).then(function() {
					QueueOptimizerAdmin.showNotification('Copied to clipboard', 'success');
				}).catch(function() {
					QueueOptimizerAdmin.fallbackCopyToClipboard(textToCopy);
				});
			} else {
				this.fallbackCopyToClipboard(textToCopy);
			}
		},

		/**
		 * Extract text content from element for copying
		 */
		extractTextFromElement: function(element) {
			var text = '';
			var $element = $(element);
			
			// Get card title
			var title = $element.find('.components-card__title').text();
			if (title) {
				text += title + '\n' + '='.repeat(title.length) + '\n\n';
			}
			
			// Extract table data
			var table = $element.find('table');
			if (table.length) {
				table.find('tr').each(function() {
					var row = $(this);
					var cells = [];
					
					row.find('th, td').each(function() {
						cells.push($(this).text().trim());
					});
					
					text += cells.join(': ') + '\n';
				});
			}
			
			return text;
		},

		/**
		 * Fallback copy to clipboard for older browsers
		 */
		fallbackCopyToClipboard: function(text) {
			var textArea = document.createElement('textarea');
			textArea.value = text;
			textArea.style.position = 'fixed';
			textArea.style.left = '-999999px';
			textArea.style.top = '-999999px';
			document.body.appendChild(textArea);
			textArea.focus();
			textArea.select();
			
			try {
				document.execCommand('copy');
				this.showNotification('Copied to clipboard', 'success');
			} catch (err) {
				this.showNotification('Copy failed - please copy manually', 'error');
			}
			
			document.body.removeChild(textArea);
		},

		/**
		 * Refresh specific data sections
		 */
		refreshData: function(section) {
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_refresh_section',
					section: section,
					nonce: queueOptimizerAdmin.nonce
				},
				beforeSend: function() {
					$('#' + section).addClass('loading');
				},
				success: function(response) {
					if (response.success) {
						$('#' + section).html(response.data.html);
						QueueOptimizerAdmin.showNotification('Data refreshed successfully', 'success');
					} else {
						QueueOptimizerAdmin.showNotification('Failed to refresh data', 'error');
					}
				},
				complete: function() {
					$('#' + section).removeClass('loading');
				}
			});
		},

		/**
		 * Show notification messages
		 */
		showNotification: function(message, type) {
			type = type || 'info';
			
			// Remove existing notifications
			$('.queue-optimizer-notification').remove();
			
			// Create notification
			var notification = $('<div class="queue-optimizer-notification notice notice-' + type + ' is-dismissible">' +
				'<p>' + message + '</p>' +
				'<button type="button" class="notice-dismiss">' +
					'<span class="screen-reader-text">Dismiss this notice.</span>' +
				'</button>' +
			'</div>');
			
			// Insert after page title
			$('.wrap h1').after(notification);
			
			// Auto-dismiss after 5 seconds
			setTimeout(function() {
				notification.fadeOut(300, function() {
					$(this).remove();
				});
			}, 5000);
			
			// Manual dismiss
			notification.find('.notice-dismiss').on('click', function() {
				notification.fadeOut(300, function() {
					$(this).remove();
				});
			});
		},

		/**
		 * Utility function to format numbers
		 */
		formatNumber: function(num) {
			return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
		},

		/**
		 * Utility function to format bytes
		 */
		formatBytes: function(bytes, decimals) {
			if (bytes === 0) return '0 Bytes';
			
			var k = 1024;
			var dm = decimals || 2;
			var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
			var i = Math.floor(Math.log(bytes) / Math.log(k));
			
			return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
		},

		/**
		 * Debug function for development
		 */
		debug: function(message, data) {
			if (window.console && queueOptimizerAdmin.debug) {
				console.log('[Queue Optimizer] ' + message, data || '');
			}
		}
	};

	// Global functions for backwards compatibility
	window.copyToClipboard = function(elementId) {
		QueueOptimizerAdmin.copyToClipboard(elementId);
	};

	window.exportPluginList = function() {
		QueueOptimizerAdmin.exportPluginList();
	};

	window.exportData = function(type) {
		if (type === 'json') {
			QueueOptimizerAdmin.exportJSON();
		} else if (type === 'csv') {
			QueueOptimizerAdmin.exportCSV();
		}
	};

	// Initialization is handled in bindEvents() method above

})(jQuery);