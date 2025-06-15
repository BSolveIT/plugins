/**
 * Activity Log JavaScript
 *
 * Handles interactive functionality for the Queue Optimizer activity log page.
 *
 * @package QueueOptimizer
 */

(function($) {
	'use strict';

	/**
	 * Activity Log functionality
	 */
	var ActivityLog = {
		
		/**
		 * Initialize activity log
		 */
		init: function() {
			this.bindEvents();
			this.initTable();
		},

		/**
		 * Bind event listeners
		 */
		bindEvents: function() {
			// Log management buttons
			$(document).on('click', '[data-action="clear-logs"]', this.handleClearLogs);
			$(document).on('click', '[data-action="export-logs"]', this.handleExportLogs);
			
			// Search functionality
			$(document).on('input', '.log-search-input', this.handleSearch);
			
			// Filter functionality
			$(document).on('change', '.log-filter-select', this.handleFilter);
			
			// Refresh button
			$(document).on('click', '.refresh-logs', this.refreshLogs);
		},

		/**
		 * Initialize table functionality
		 */
		initTable: function() {
			// Handle row selection
			$(document).on('change', '.select-all-logs', this.handleSelectAll);
			$(document).on('change', '.log-checkbox', this.handleRowSelect);
			
			// Handle bulk actions
			$(document).on('click', '.apply-bulk-action', this.handleBulkAction);
			$(document).on('click', '.clear-selection', this.handleClearSelection);
			
			// Handle individual actions
			$(document).on('click', '.retry-action', this.handleRetryAction);
			$(document).on('click', '.cancel-action', this.handleCancelAction);
			$(document).on('click', '.expand-message', this.handleExpandMessage);
			
			// Prevent row clicks when clicking on controls
			$(document).on('click', '.activity-log-table input, .activity-log-table button', function(e) {
				e.stopPropagation();
			});
		},

		/**
		 * Handle select all checkbox
		 */
		handleSelectAll: function() {
			var isChecked = $(this).prop('checked');
			$('.log-checkbox:visible').prop('checked', isChecked);
			ActivityLog.updateBulkActionsVisibility();
		},

		/**
		 * Handle individual row selection
		 */
		handleRowSelect: function() {
			var $allCheckboxes = $('.log-checkbox:visible');
			var $checkedBoxes = $('.log-checkbox:visible:checked');
			
			// Update select all checkbox
			$('.select-all-logs').prop('checked', $allCheckboxes.length === $checkedBoxes.length);
			
			ActivityLog.updateBulkActionsVisibility();
		},

		/**
		 * Update bulk actions visibility
		 */
		updateBulkActionsVisibility: function() {
			var selectedCount = $('.log-checkbox:checked').length;
			var $container = $('.bulk-actions-container');
			var $countSpan = $('.selected-count');
			
			if (selectedCount > 0) {
				$container.show();
				$countSpan.text(selectedCount + ' ' + (selectedCount === 1 ? 'item selected' : 'items selected'));
			} else {
				$container.hide();
			}
		},

		/**
		 * Handle bulk action
		 */
		handleBulkAction: function(e) {
			e.preventDefault();
			
			var bulkAction = $('.bulk-action-select').val();
			var selectedIds = $('.log-checkbox:checked').map(function() {
				return $(this).val();
			}).get();
			
			if (!bulkAction || selectedIds.length === 0) {
				ActivityLog.showNotice('Please select an action and at least one item.', 'error');
				return;
			}
			
			var actionText = bulkAction === 'retry' ? 'retry' : 'cancel';
			var confirmMessage = 'Are you sure you want to ' + actionText + ' ' + selectedIds.length + ' selected items?';
			
			if (!confirm(confirmMessage)) {
				return;
			}
			
			var $button = $(this);
			var originalText = $button.text();
			
			$button.prop('disabled', true)
				   .addClass('updating-message')
				   .text('Processing...');
			
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_bulk_actions',
					bulk_action: bulkAction,
					action_ids: selectedIds,
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						ActivityLog.showNotice(response.data.message, 'success');
						setTimeout(function() {
							window.location.reload();
						}, 1500);
					} else {
						ActivityLog.showNotice(response.data.message || 'Bulk action failed.', 'error');
					}
				},
				error: function() {
					ActivityLog.showNotice('An error occurred while performing bulk action.', 'error');
				},
				complete: function() {
					$button.prop('disabled', false)
						   .removeClass('updating-message')
						   .text(originalText);
				}
			});
		},

		/**
		 * Handle clear selection
		 */
		handleClearSelection: function(e) {
			e.preventDefault();
			$('.log-checkbox, .select-all-logs').prop('checked', false);
			ActivityLog.updateBulkActionsVisibility();
		},

		/**
		 * Handle retry action
		 */
		handleRetryAction: function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			var actionId = $(this).data('action-id');
			var $button = $(this);
			var originalHtml = $button.html();
			
			if (!confirm('Are you sure you want to retry this action?')) {
				return;
			}
			
			$button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spin"></span>');
			
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_retry_action',
					action_id: actionId,
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						ActivityLog.showNotice(response.data.message, 'success');
						setTimeout(function() {
							window.location.reload();
						}, 1500);
					} else {
						ActivityLog.showNotice(response.data.message || 'Failed to retry action.', 'error');
					}
				},
				error: function() {
					ActivityLog.showNotice('An error occurred while retrying action.', 'error');
				},
				complete: function() {
					$button.prop('disabled', false).html(originalHtml);
				}
			});
		},

		/**
		 * Handle cancel action
		 */
		handleCancelAction: function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			var actionId = $(this).data('action-id');
			var $button = $(this);
			var originalHtml = $button.html();
			
			if (!confirm('Are you sure you want to cancel this action?')) {
				return;
			}
			
			$button.prop('disabled', true).html('<span class="dashicons dashicons-no-alt spin"></span>');
			
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_cancel_action',
					action_id: actionId,
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						ActivityLog.showNotice(response.data.message, 'success');
						setTimeout(function() {
							window.location.reload();
						}, 1500);
					} else {
						ActivityLog.showNotice(response.data.message || 'Failed to cancel action.', 'error');
					}
				},
				error: function() {
					ActivityLog.showNotice('An error occurred while canceling action.', 'error');
				},
				complete: function() {
					$button.prop('disabled', false).html(originalHtml);
				}
			});
		},

		/**
		 * Handle expand message
		 */
		handleExpandMessage: function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			var $row = $(this).closest('tr');
			var $messageDiv = $row.find('.log-message');
			var $icon = $(this).find('.dashicons');
			
			if ($messageDiv.css('white-space') === 'nowrap') {
				$messageDiv.css({
					'white-space': 'normal',
					'overflow': 'visible',
					'text-overflow': 'clip',
					'max-width': 'none'
				});
				$icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
				$row.addClass('expanded');
			} else {
				$messageDiv.css({
					'white-space': 'nowrap',
					'overflow': 'hidden',
					'text-overflow': 'ellipsis',
					'max-width': '300px'
				});
				$icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
				$row.removeClass('expanded');
			}
		},

		/**
		 * Handle clear logs button click
		 */
		handleClearLogs: function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var logType = $button.data('type') || 'all';
			var originalText = $button.text();
			
			// Confirm action
			if (!confirm(queueOptimizerAdmin.strings.confirm_clear_logs || 'Are you sure you want to clear logs? This action cannot be undone.')) {
				return;
			}
			
			// Disable button and show loading state
			$button.prop('disabled', true)
				   .addClass('updating-message')
				   .text(queueOptimizerAdmin.loading_text || 'Processing...');

			// Send AJAX request
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_clear_logs',
					log_type: logType,
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						ActivityLog.showNotice(response.data.message || 'Logs cleared successfully.', 'success');
						
						// Refresh the page to show updated logs
						setTimeout(function() {
							window.location.reload();
						}, 1500);
					} else {
						ActivityLog.showNotice(response.data.message || 'Failed to clear logs.', 'error');
					}
				},
				error: function() {
					ActivityLog.showNotice('An error occurred while clearing logs.', 'error');
				},
				complete: function() {
					// Restore button state
					$button.prop('disabled', false)
						   .removeClass('updating-message')
						   .text(originalText);
				}
			});
		},

		/**
		 * Handle export logs button click
		 */
		handleExportLogs: function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var format = $button.data('format') || 'csv';
			var originalText = $button.text();
			
			// Disable button and show loading state
			$button.prop('disabled', true)
				   .addClass('updating-message')
				   .text('Exporting...');

			// Send AJAX request
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_export_logs',
					format: format,
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						ActivityLog.downloadFile(response.data.data, response.data.filename);
						ActivityLog.showNotice('Logs exported successfully.', 'success');
					} else {
						ActivityLog.showNotice(response.data.message || 'Failed to export logs.', 'error');
					}
				},
				error: function() {
					ActivityLog.showNotice('An error occurred while exporting logs.', 'error');
				},
				complete: function() {
					// Restore button state
					$button.prop('disabled', false)
						   .removeClass('updating-message')
						   .text(originalText);
				}
			});
		},

		/**
		 * Handle search input
		 */
		handleSearch: function() {
			var searchTerm = $(this).val().toLowerCase();
			var $rows = $('.activity-log-table tbody tr');
			
			$rows.each(function() {
				var $row = $(this);
				var rowText = $row.text().toLowerCase();
				
				if (rowText.indexOf(searchTerm) !== -1) {
					$row.show();
				} else {
					$row.hide();
				}
			});
		},

		/**
		 * Handle filter select change
		 */
		handleFilter: function() {
			var filterValue = $(this).val();
			var $rows = $('.activity-log-table tbody tr');
			
			if (filterValue === 'all') {
				$rows.show();
			} else {
				$rows.each(function() {
					var $row = $(this);
					var status = $row.find('.components-badge').text().toLowerCase();
					
					if (status === filterValue.toLowerCase()) {
						$row.show();
					} else {
						$row.hide();
					}
				});
			}
		},

		/**
		 * Refresh logs
		 */
		refreshLogs: function(e) {
			if (e) {
				e.preventDefault();
			}
			
			var $button = $('.refresh-logs');
			var originalText = $button.text();
			
			$button.prop('disabled', true)
				   .addClass('updating-message')
				   .text('Refreshing...');

			// Reload the page to refresh logs
			window.location.reload();
		},

		/**
		 * Download file
		 */
		downloadFile: function(data, filename) {
			var blob = new Blob([data], { type: 'text/plain' });
			var url = window.URL.createObjectURL(blob);
			var a = document.createElement('a');
			
			a.href = url;
			a.download = filename;
			document.body.appendChild(a);
			a.click();
			
			window.URL.revokeObjectURL(url);
			document.body.removeChild(a);
		},

		/**
		 * Show admin notice
		 */
		showNotice: function(message, type) {
			var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
			var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
			
			$('.queue-optimizer-activity-log').before($notice);
			
			// Auto-dismiss after 5 seconds
			setTimeout(function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			}, 5000);
		},

		/**
		 * Format number with localization
		 */
		formatNumber: function(number) {
			if (typeof Intl !== 'undefined' && Intl.NumberFormat) {
				return new Intl.NumberFormat().format(number);
			}
			return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
		}
	};

	/**
	 * Initialize when document is ready
	 */
	$(document).ready(function() {
		// Only run on activity log page
		if ($('.queue-optimizer-activity-log').length) {
			ActivityLog.init();
		}
	});

	// Export for potential external use
	window.QueueOptimizerActivityLog = ActivityLog;

})(jQuery);