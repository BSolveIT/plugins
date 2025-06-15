/**
 * System Info Page JavaScript
 * 
 * Handles interactive features for the system info page including
 * search, filtering, copy functionality, and exports.
 *
 * @package QueueOptimizer
 */

(function($) {
	'use strict';

	// DOM ready handler
	$(document).ready(function() {
		initSystemInfo();
	});

	/**
	 * Initialize system info page functionality.
	 */
	function initSystemInfo() {
		// Initialize postbox functionality
		postboxes.add_postbox_toggles('queue-optimizer-system-info');

		// Bind event handlers
		bindEventHandlers();

		// Initialize search functionality
		initSearch();
		initPluginSearch();
	}

	/**
	 * Bind all event handlers.
	 */
	function bindEventHandlers() {
		// Copy section buttons
		$('.copy-section').on('click', handleCopySection);

		// Export buttons
		$('#export-json').on('click', function() {
			handleExport('json');
		});

		$('#export-csv').on('click', function() {
			handleExport('csv');
		});

		// Dismiss success message
		$(document).on('click', '#copy-success .notice-dismiss', function() {
			$('#copy-success').fadeOut();
		});
	}

	/**
	 * Initialize global search functionality.
	 */
	function initSearch() {
		var $searchInput = $('#system-info-search');
		var searchTimeout;

		$searchInput.on('input', function() {
			clearTimeout(searchTimeout);
			var query = $(this).val().toLowerCase().trim();

			searchTimeout = setTimeout(function() {
				performSearch(query);
			}, 300);
		});
	}

	/**
	 * Initialize plugin-specific search.
	 */
	function initPluginSearch() {
		var $pluginSearch = $('#plugin-search');
		var searchTimeout;

		$pluginSearch.on('input', function() {
			clearTimeout(searchTimeout);
			var query = $(this).val().toLowerCase().trim();

			searchTimeout = setTimeout(function() {
				filterPlugins(query);
			}, 200);
		});
	}

	/**
	 * Perform global search across all system info.
	 *
	 * @param {string} query Search query
	 */
	function performSearch(query) {
		// Clear previous highlights
		clearSearchHighlights();

		if (!query) {
			// Show all postboxes and rows
			$('.postbox').show();
			$('.postbox tr').show();
			return;
		}

		var hasResults = false;

		// Search through each postbox
		$('.postbox').each(function() {
			var $postbox = $(this);
			var postboxHasResults = false;

			// Search table rows
			$postbox.find('tr').each(function() {
				var $row = $(this);
				var rowText = $row.text().toLowerCase();

				if (rowText.includes(query)) {
					// Highlight matching text
					highlightText($row, query);
					$row.show();
					postboxHasResults = true;
					hasResults = true;
				} else {
					$row.hide();
				}
			});

			// Search extension items
			$postbox.find('.extension-item').each(function() {
				var $item = $(this);
				var itemText = $item.text().toLowerCase();

				if (itemText.includes(query)) {
					highlightText($item, query);
					$item.show();
					postboxHasResults = true;
					hasResults = true;
				} else {
					$item.hide();
				}
			});

			// Show/hide postbox based on results
			if (postboxHasResults) {
				$postbox.show();
				// Expand collapsed postboxes with results
				if ($postbox.hasClass('closed')) {
					$postbox.removeClass('closed');
				}
			} else {
				$postbox.hide();
			}
		});

		// Show message if no results
		if (!hasResults) {
			showNoResultsMessage();
		}
	}

	/**
	 * Filter plugins table.
	 *
	 * @param {string} query Search query
	 */
	function filterPlugins(query) {
		var $pluginRows = $('.plugin-row');
		var visibleCount = 0;

		$pluginRows.each(function() {
			var $row = $(this);
			var pluginName = $row.data('name');
			var pluginStatus = $row.data('status');
			var pluginText = $row.text().toLowerCase();

			if (!query || 
				pluginName.includes(query) || 
				pluginStatus.includes(query) || 
				pluginText.includes(query)) {
				$row.removeClass('hidden').show();
				visibleCount++;
			} else {
				$row.addClass('hidden').hide();
			}
		});

		// Update plugin count indicator if needed
		updatePluginCountIndicator(visibleCount, $pluginRows.length);
	}

	/**
	 * Highlight matching text in element.
	 *
	 * @param {jQuery} $element Element to highlight in
	 * @param {string} query Search query
	 */
	function highlightText($element, query) {
		if (!query) return;

		$element.find('*').addBack().contents().filter(function() {
			return this.nodeType === 3; // Text nodes only
		}).each(function() {
			var text = this.textContent;
			var regex = new RegExp('(' + escapeRegex(query) + ')', 'gi');
			
			if (regex.test(text)) {
				var highlightedText = text.replace(regex, '<span class="search-highlight">$1</span>');
				$(this).replaceWith(highlightedText);
			}
		});
	}

	/**
	 * Clear search highlights.
	 */
	function clearSearchHighlights() {
		$('.search-highlight').each(function() {
			var $this = $(this);
			$this.replaceWith($this.text());
		});

		// Remove no results message
		$('#no-results-message').remove();
	}

	/**
	 * Show no results message.
	 */
	function showNoResultsMessage() {
		if ($('#no-results-message').length === 0) {
			var message = '<div id="no-results-message" class="postbox"><div class="postbox-header"><h2>' + 
						  queueOptimizerSystemInfo.strings.no_results + '</h2></div></div>';
			$('.meta-box-sortables').append(message);
		}
	}

	/**
	 * Update plugin count indicator.
	 *
	 * @param {number} visible Number of visible plugins
	 * @param {number} total Total number of plugins
	 */
	function updatePluginCountIndicator(visible, total) {
		var $indicator = $('#plugin-count-indicator');
		
		if ($indicator.length === 0) {
			$indicator = $('<div id="plugin-count-indicator" class="plugin-count"></div>');
			$('#plugin-search').after($indicator);
		}

		if (visible !== total) {
			$indicator.text('Showing ' + visible + ' of ' + total + ' plugins').show();
		} else {
			$indicator.hide();
		}
	}

	/**
	 * Handle copy section functionality.
	 *
	 * @param {Event} e Click event
	 */
	function handleCopySection(e) {
		e.preventDefault();
		
		var $button = $(this);
		var section = $button.data('section');
		var systemInfo = JSON.parse($('#system-info-data').text());
		
		if (!systemInfo[section]) {
			showNotification(queueOptimizerSystemInfo.strings.copy_error, 'error');
			return;
		}

		var textToCopy = formatSectionForCopy(section, systemInfo[section]);
		
		// Copy to clipboard
		if (copyToClipboard(textToCopy)) {
			showNotification(queueOptimizerSystemInfo.strings.copied, 'success');
			
			// Visual feedback on button
			var originalText = $button.html();
			$button.html('<span class="dashicons dashicons-yes"></span> Copied!');
			setTimeout(function() {
				$button.html(originalText);
			}, 2000);
		} else {
			showNotification(queueOptimizerSystemInfo.strings.copy_error, 'error');
		}
	}

	/**
	 * Format section data for copying.
	 *
	 * @param {string} sectionName Section name
	 * @param {Object|Array} sectionData Section data
	 * @return {string} Formatted text
	 */
	function formatSectionForCopy(sectionName, sectionData) {
		var output = '=== ' + sectionName.toUpperCase().replace('_', ' ') + ' ===\n\n';

		if (Array.isArray(sectionData)) {
			// Handle arrays (like plugins, extensions)
			if (sectionName === 'plugins') {
				sectionData.forEach(function(item) {
					output += item.name + ' (v' + item.version + ') - ' + item.status + '\n';
				});
			} else if (sectionName === 'php_extensions') {
				sectionData.forEach(function(item) {
					output += item.name + ' (v' + item.version + ')' + (item.important ? ' *' : '') + '\n';
				});
			}
		} else {
			// Handle objects
			Object.keys(sectionData).forEach(function(key) {
				var label = key.replace(/_/g, ' ').replace(/\b\w/g, function(l) {
					return l.toUpperCase();
				});
				output += label + ': ' + sectionData[key] + '\n';
			});
		}

		return output;
	}

	/**
	 * Handle export functionality.
	 *
	 * @param {string} format Export format (json|csv)
	 */
	function handleExport(format) {
		var $button = $('#export-' + format);
		var originalText = $button.text();
		
		// Show loading state
		$button.text(queueOptimizerSystemInfo.strings.exporting).prop('disabled', true);

		// Prepare data for export
		$.ajax({
			url: queueOptimizerSystemInfo.ajax_url,
			type: 'POST',
			data: {
				action: 'queue_optimizer_export_system_info',
				format: format,
				nonce: queueOptimizerSystemInfo.nonce
			},
			xhrFields: {
				responseType: 'blob'
			},
			success: function(data, status, xhr) {
				// Create download link
				var blob = new Blob([data], {
					type: format === 'json' ? 'application/json' : 'text/csv'
				});
				var url = window.URL.createObjectURL(blob);
				var link = document.createElement('a');
				
				// Get filename from response headers or create default
				var contentDisposition = xhr.getResponseHeader('Content-Disposition');
				var filename = 'system-info-' + getCurrentDateTime() + '.' + format;
				
				if (contentDisposition) {
					var matches = /filename="([^"]*)"/.exec(contentDisposition);
					if (matches && matches[1]) {
						filename = matches[1];
					}
				}
				
				link.href = url;
				link.download = filename;
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);
				window.URL.revokeObjectURL(url);
				
				showNotification('Export completed successfully!', 'success');
			},
			error: function() {
				showNotification(queueOptimizerSystemInfo.strings.export_error, 'error');
			},
			complete: function() {
				// Reset button state
				$button.text(originalText).prop('disabled', false);
			}
		});
	}

	/**
	 * Copy text to clipboard.
	 *
	 * @param {string} text Text to copy
	 * @return {boolean} Success status
	 */
	function copyToClipboard(text) {
		try {
			// Modern approach
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(text);
				return true;
			}
			
			// Fallback approach
			var textArea = document.createElement('textarea');
			textArea.value = text;
			textArea.style.position = 'fixed';
			textArea.style.left = '-999999px';
			textArea.style.top = '-999999px';
			document.body.appendChild(textArea);
			textArea.focus();
			textArea.select();
			
			var result = document.execCommand('copy');
			document.body.removeChild(textArea);
			return result;
		} catch (err) {
			console.error('Failed to copy text: ', err);
			return false;
		}
	}

	/**
	 * Show notification message.
	 *
	 * @param {string} message Message to show
	 * @param {string} type Message type (success|error|info)
	 */
	function showNotification(message, type) {
		var $notice = $('#copy-success');
		
		$notice.removeClass('notice-success notice-error notice-info')
			   .addClass('notice-' + type)
			   .find('p').text(message);
		
		$notice.fadeIn().delay(3000).fadeOut();
	}

	/**
	 * Get current date time for filenames.
	 *
	 * @return {string} Formatted date time
	 */
	function getCurrentDateTime() {
		var now = new Date();
		return now.getFullYear() + '-' + 
			   String(now.getMonth() + 1).padStart(2, '0') + '-' + 
			   String(now.getDate()).padStart(2, '0') + '-' + 
			   String(now.getHours()).padStart(2, '0') + '-' + 
			   String(now.getMinutes()).padStart(2, '0') + '-' + 
			   String(now.getSeconds()).padStart(2, '0');
	}

	/**
	 * Escape regex special characters.
	 *
	 * @param {string} string String to escape
	 * @return {string} Escaped string
	 */
	function escapeRegex(string) {
		return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

})(jQuery);