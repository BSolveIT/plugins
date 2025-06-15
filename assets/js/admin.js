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
		},

		/**
		 * Refresh dashboard statistics
		 */
		refreshDashboardStats: function() {
			$.ajax({
				url: ajaxurl,
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
				url: ajaxurl,
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
			// System info panel collapsing
			$('.components-card__header').on('click', function() {
				var card = $(this).closest('.components-card');
				var body = card.find('.components-card__body');
				
				body.slideToggle(300);
				card.toggleClass('collapsed');
			});

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
				url: ajaxurl,
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

	// Initialize when DOM is ready
	$(document).ready(function() {
		QueueOptimizerAdmin.init();
	});

})(jQuery);