/**
 * Dashboard JavaScript
 *
 * Handles interactive functionality for the Queue Optimizer dashboard page.
 *
 * @package QueueOptimizer
 */

(function($) {
	'use strict';

	/**
	 * Dashboard functionality
	 */
	var Dashboard = {
		
		// Flag to prevent repeated error logging
		errorLogged: false,
		
		/**
		 * Initialize dashboard
		 */
		init: function() {
			this.bindEvents();
			this.initPostboxes();
			// Don't refresh stats on load - they're already loaded server-side
		},

		/**
		 * Bind event listeners
		 */
		bindEvents: function() {
			// Quick action buttons
			$(document).on('click', '[data-action]', this.handleQuickAction);
			
			// Refresh button
			$(document).on('click', '.refresh-dashboard', this.refreshDashboard);
			
			// Manual refresh stats button
			$(document).on('click', '.refresh-stats', this.handleRefreshStats.bind(this));
			
			// Legacy button handlers for old dashboard system
			$(document).on('click', '#run-queue-now', this.handleRunQueueNow.bind(this));
			$(document).on('click', '#view-logs', this.handleViewLogs.bind(this));
			$(document).on('click', '#clear-logs', this.handleClearLogs.bind(this));
			$(document).on('click', '#clear-action-scheduler-logs', this.handleClearActionSchedulerLogs.bind(this));
			$(document).on('click', '#refresh-logs', this.handleRefreshLogs.bind(this));
			$(document).on('click', '#close-logs', this.handleCloseLogs.bind(this));
		},

		/**
		 * Initialize WordPress postboxes
		 */
		initPostboxes: function() {
			if (typeof postboxes !== 'undefined') {
				postboxes.add_postbox_toggles('queue-optimizer-dashboard');
			}
		},

		/**
		 * Handle quick action button clicks
		 */
		handleQuickAction: function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var action = $button.data('action');
			var originalText = $button.text();
			
			// Check if admin variables exist
			if (typeof queueOptimizerAdmin === 'undefined') {
				console.error('Queue Optimizer admin variables not loaded');
				return;
			}
			
			// Check if AJAX URL exists
			if (!queueOptimizerAdmin.ajax_url) {
				console.error('AJAX URL not available in queueOptimizerAdmin');
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
					action: 'queue_optimizer_quick_action',
					quick_action: action,
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						Dashboard.showNotice(response.data.message || 'Action completed successfully.', 'success');
						
						// Refresh relevant sections
						if (action === 'run_cleanup' || action === 'clear_failed') {
							Dashboard.refreshStats();
						}
					} else {
						Dashboard.showNotice(response.data.message || 'Action failed.', 'error');
					}
				},
				error: function() {
					Dashboard.showNotice('An error occurred while processing the action.', 'error');
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
		 * Refresh dashboard data
		 */
		refreshDashboard: function(e) {
			if (e) {
				e.preventDefault();
			}
			
			var $button = $('.refresh-dashboard');
			var originalText = $button.text();
			
			$button.prop('disabled', true)
				   .addClass('updating-message')
				   .text('Refreshing...');

			// Reload the page to refresh all data
			window.location.reload();
		},

		/**
		 * Handle manual refresh stats
		 */
		handleRefreshStats: function(e) {
			if (e) {
				e.preventDefault();
			}

			var $button = $(e.currentTarget);
			var originalHtml = $button.html();
			
			// Show loading state
			$button.prop('disabled', true)
				   .html('<span class="dashicons dashicons-update-alt spin"></span> Refreshing...');

			// Call refresh stats
			this.refreshStats(function() {
				// Restore button state
				$button.prop('disabled', false).html(originalHtml);
			});
		},

		/**
		 * Refresh statistics only
		 */
		refreshStats: function(callback) {
			// Check if admin variables exist
			if (typeof queueOptimizerAdmin === 'undefined' || typeof ajaxurl === 'undefined') {
				if (callback) callback();
				return;
			}

			// Prevent multiple simultaneous requests
			if (this.refreshing) {
				if (callback) callback();
				return;
			}

			this.refreshing = true;

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'queue_optimizer_refresh_stats',
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success && response.data.stats) {
						Dashboard.updateStatsDisplay(response.data.stats);
						Dashboard.showNotice('Statistics refreshed successfully.', 'success');
					}
				},
				error: function(xhr, status, error) {
					console.error('Failed to refresh dashboard stats:', error);
					Dashboard.showNotice('Failed to refresh statistics. Please try again.', 'error');
				},
				complete: function() {
					Dashboard.refreshing = false;
					if (callback) callback();
				}
			});
		},

		/**
		 * Update stats display
		 */
		updateStatsDisplay: function(stats) {
			// Update stat cards
			$('.stat-card.total-jobs h3').text(Dashboard.formatNumber(stats.total_jobs || 0));
			$('.stat-card.pending-jobs h3').text(Dashboard.formatNumber(stats.pending_jobs || 0));
			$('.stat-card.completed-jobs h3').text(Dashboard.formatNumber(stats.completed_jobs || 0));
			$('.stat-card.failed-jobs h3').text(Dashboard.formatNumber(stats.failed_jobs || 0));
			$('.stat-card.in-progress-jobs h3').text(Dashboard.formatNumber(stats.in_progress_jobs || 0));
			
			// Add visual feedback
			$('.stat-card').addClass('updated');
			setTimeout(function() {
				$('.stat-card').removeClass('updated');
			}, 1000);
		},

		/**
		 * Show admin notice
		 */
		showNotice: function(message, type) {
			var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
			var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
			
			$('.queue-optimizer-dashboard').before($notice);
			
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
		},

		/**
		 * Animate stats cards on load
		 */
		animateStatsCards: function() {
			$('.stat-card').each(function(index) {
				var $card = $(this);
				setTimeout(function() {
					$card.addClass('animate-in');
				}, index * 100);
			});
		},

		/**
		 * Handle Run Queue Now button click
		 */
		handleRunQueueNow: function(e) {
			e.preventDefault();
			
			var $button = $(e.currentTarget);
			var originalText = $button.text();
			
			// Check if admin variables exist
			if (typeof queueOptimizerAdmin === 'undefined') {
				console.error('Queue Optimizer admin variables not loaded');
				return;
			}

			// Disable button and show loading state
			$button.prop('disabled', true)
				   .addClass('updating-message')
				   .text(queueOptimizerAdmin.strings.processing || 'Processing...');

			// Send AJAX request
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_run_now',
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						Dashboard.showNotice(response.data.message || 'Queue processed successfully.', 'success');
						Dashboard.refreshStats();
					} else {
						Dashboard.showNotice(response.data.message || 'Failed to process queue.', 'error');
					}
				},
				error: function() {
					Dashboard.showNotice('An error occurred while processing the queue.', 'error');
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
		 * Handle View Logs button click
		 */
		handleViewLogs: function(e) {
			e.preventDefault();
			
			var $button = $(e.currentTarget);
			var originalText = $button.text();
			
			// Check if admin variables exist
			if (typeof queueOptimizerAdmin === 'undefined') {
				console.error('Queue Optimizer admin variables not loaded');
				return;
			}

			// Disable button and show loading state
			$button.prop('disabled', true)
				   .addClass('updating-message')
				   .text(queueOptimizerAdmin.strings.processing || 'Loading...');

			// Send AJAX request
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_get_logs',
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						Dashboard.displayLogs(response.data.logs);
						$('#queue-optimizer-logs').show();
					} else {
						Dashboard.showNotice(response.data.message || 'Failed to load logs.', 'error');
					}
				},
				error: function() {
					Dashboard.showNotice('An error occurred while loading logs.', 'error');
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
		 * Handle Clear Logs button click
		 */
		handleClearLogs: function(e) {
			e.preventDefault();
			
			if (!confirm('Are you sure you want to clear all plugin logs?')) {
				return;
			}
			
			var $button = $(e.currentTarget);
			var originalText = $button.text();
			
			// Check if admin variables exist
			if (typeof queueOptimizerAdmin === 'undefined') {
				console.error('Queue Optimizer admin variables not loaded');
				return;
			}

			// Disable button and show loading state
			$button.prop('disabled', true)
				   .addClass('updating-message')
				   .text(queueOptimizerAdmin.strings.processing || 'Clearing...');

			// Send AJAX request
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_clear_logs',
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						Dashboard.showNotice(response.data.message || 'Logs cleared successfully.', 'success');
						$('#queue-optimizer-logs').hide();
						$('#log-display').empty();
					} else {
						Dashboard.showNotice(response.data.message || 'Failed to clear logs.', 'error');
					}
				},
				error: function() {
					Dashboard.showNotice('An error occurred while clearing logs.', 'error');
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
		 * Handle Clear Action Scheduler Logs button click
		 */
		handleClearActionSchedulerLogs: function(e) {
			e.preventDefault();
			
			if (!confirm('Are you sure you want to clear all Action Scheduler logs?')) {
				return;
			}
			
			var $button = $(e.currentTarget);
			var originalText = $button.text();
			
			// Check if admin variables exist
			if (typeof queueOptimizerAdmin === 'undefined') {
				console.error('Queue Optimizer admin variables not loaded');
				return;
			}

			// Disable button and show loading state
			$button.prop('disabled', true)
				   .addClass('updating-message')
				   .text(queueOptimizerAdmin.strings.processing || 'Clearing...');

			// Send AJAX request
			$.ajax({
				url: queueOptimizerAdmin.ajax_url,
				type: 'POST',
				data: {
					action: 'queue_optimizer_clear_action_scheduler_logs',
					nonce: queueOptimizerAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						Dashboard.showNotice(response.data.message || 'Action Scheduler logs cleared successfully.', 'success');
						Dashboard.refreshStats();
					} else {
						Dashboard.showNotice(response.data.message || 'Failed to clear Action Scheduler logs.', 'error');
					}
				},
				error: function() {
					Dashboard.showNotice('An error occurred while clearing Action Scheduler logs.', 'error');
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
		 * Handle Refresh Logs button click
		 */
		handleRefreshLogs: function(e) {
			e.preventDefault();
			this.handleViewLogs(e);
		},

		/**
		 * Handle Close Logs button click
		 */
		handleCloseLogs: function(e) {
			e.preventDefault();
			$('#queue-optimizer-logs').hide();
		},

		/**
		 * Display logs in the log container
		 */
		displayLogs: function(logs) {
			var $logDisplay = $('#log-display');
			if (logs && logs.trim()) {
				$logDisplay.text(logs);
			} else {
				$logDisplay.text('No logs available.');
			}
		}
	};

	/**
	 * Initialize when document is ready
	 */
	$(document).ready(function() {
		// Only run on dashboard page
		if ($('.queue-optimizer-dashboard').length) {
			Dashboard.init();
			Dashboard.animateStatsCards();
		}
	});

	// Export for potential external use
	window.QueueOptimizerDashboard = Dashboard;

})(jQuery);