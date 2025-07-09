/**
 * Admin JavaScript for Quick FAQ Markup
 *
 * This file contains all the admin-specific JavaScript functionality.
 *
 * @package Quick_FAQ_Markup
 * @since 1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Main admin object
	 */
	var QFMAdmin = {
		
		/**
		 * Initialize admin functionality
		 */
		init: function() {
			this.bindEvents();
			this.initSortable();
			this.initOrderInputs();
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			// Handle order input changes
			$(document).on('change', '.qfm-order-input', this.handleOrderInputChange);
			
			// Handle form submissions
			$(document).on('submit', '#posts-filter', this.handleBulkActions);
			
			// Handle meta box form validation
			$(document).on('submit', '#post', this.validateMetaBox);
		},

		/**
		 * Initialize sortable functionality for FAQ list
		 */
		initSortable: function() {
			if ($('#the-list').length && $('.post-type-qfm_faq').length) {
				$('#the-list').sortable({
					items: 'tr',
					cursor: 'move',
					axis: 'y',
					handle: '.qfm-drag-handle',
					helper: function(e, ui) {
						ui.children().each(function() {
							$(this).width($(this).width());
						});
						return ui;
					},
					start: function(event, ui) {
						ui.item.addClass('qfm-sorting');
					},
					stop: function(event, ui) {
						ui.item.removeClass('qfm-sorting');
						QFMAdmin.updateBulkOrder();
					},
					placeholder: 'ui-state-highlight qfm-sort-placeholder'
				});
			}
		},

		/**
		 * Initialize order input functionality
		 */
		initOrderInputs: function() {
			// Auto-save on blur
			$(document).on('blur', '.qfm-order-input', function() {
				var $input = $(this);
				var postId = $input.data('post-id');
				var order = parseInt($input.val()) || 0;
				
				if (postId && order >= 0) {
					QFMAdmin.updateSingleOrder(postId, order);
				}
			});

			// Handle enter key
			$(document).on('keypress', '.qfm-order-input', function(e) {
				if (e.which === 13) { // Enter key
					$(this).blur();
				}
			});
		},

		/**
		 * Handle order input changes
		 */
		handleOrderInputChange: function(e) {
			var $input = $(e.target);
			var postId = $input.data('post-id');
			var order = parseInt($input.val()) || 0;
			
			// Validate input
			if (order < 0) {
				order = 0;
				$input.val(order);
			}
			
			// Update immediately on change
			if (postId) {
				QFMAdmin.updateSingleOrder(postId, order);
			}
		},

		/**
		 * Update bulk order after drag and drop
		 */
		updateBulkOrder: function() {
			var orderData = [];
			
			$('#the-list tr').each(function(index) {
				var postId = $(this).find('.qfm-order-input').data('post-id');
				if (postId) {
					orderData.push(postId);
					// Update the input field to reflect new order
					$(this).find('.qfm-order-input').val(index + 1);
				}
			});

			if (orderData.length > 0) {
				this.sendOrderUpdate(orderData, 'bulk');
			}
		},

		/**
		 * Update single FAQ order
		 */
		updateSingleOrder: function(postId, order) {
			$.ajax({
				url: qfmAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'qfm_single_faq_order',
					nonce: qfmAdmin.nonce,
					post_id: postId,
					order: order
				},
				beforeSend: function() {
					$('.qfm-order-input[data-post-id="' + postId + '"]').addClass('qfm-loading');
				},
				success: function(response) {
					if (response.success) {
						QFMAdmin.showNotice(response.data.message, 'success');
					} else {
						QFMAdmin.showNotice(response.data.message, 'error');
					}
				},
				error: function() {
					QFMAdmin.showNotice(qfmAdmin.messages.orderError, 'error');
				},
				complete: function() {
					$('.qfm-order-input[data-post-id="' + postId + '"]').removeClass('qfm-loading');
				}
			});
		},

		/**
		 * Send order update to server
		 */
		sendOrderUpdate: function(orderData, type) {
			var action = type === 'bulk' ? 'qfm_update_faq_order' : 'qfm_single_faq_order';
			
			$.ajax({
				url: qfmAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: action,
					nonce: qfmAdmin.nonce,
					order: orderData
				},
				beforeSend: function() {
					$('#the-list').addClass('qfm-loading');
				},
				success: function(response) {
					if (response.success) {
						QFMAdmin.showNotice(response.data.message, 'success');
					} else {
						QFMAdmin.showNotice(response.data.message, 'error');
					}
				},
				error: function() {
					QFMAdmin.showNotice(qfmAdmin.messages.orderError, 'error');
				},
				complete: function() {
					$('#the-list').removeClass('qfm-loading');
				}
			});
		},

		/**
		 * Handle bulk actions
		 */
		handleBulkActions: function(e) {
			var action = $('#bulk-action-selector-top').val();
			var selectedItems = $('input[name="post[]"]:checked').length;
			
			if (action === 'trash' && selectedItems > 0) {
				if (!confirm(qfmAdmin.messages.confirmDelete)) {
					e.preventDefault();
					return false;
				}
			}
		},

		/**
		 * Validate meta box form
		 */
		validateMetaBox: function(e) {
			if ($('#post_type').val() !== 'qfm_faq') {
				return true;
			}

			var question = $('#qfm_faq_question').val().trim();
			var answer = '';
			
			// Get answer from TinyMCE or textarea
			if (typeof tinyMCE !== 'undefined' && tinyMCE.get('qfm_faq_answer')) {
				answer = tinyMCE.get('qfm_faq_answer').getContent();
			} else {
				answer = $('#qfm_faq_answer').val();
			}
			
			answer = answer.trim();

			// Validate required fields
			if (!question) {
				QFMAdmin.showNotice('Please enter a question for this FAQ.', 'error');
				$('#qfm_faq_question').focus();
				e.preventDefault();
				return false;
			}

			if (!answer) {
				QFMAdmin.showNotice('Please enter an answer for this FAQ.', 'error');
				if (typeof tinyMCE !== 'undefined' && tinyMCE.get('qfm_faq_answer')) {
					tinyMCE.get('qfm_faq_answer').focus();
				} else {
					$('#qfm_faq_answer').focus();
				}
				e.preventDefault();
				return false;
			}

			return true;
		},

		/**
		 * Show admin notice
		 */
		showNotice: function(message, type) {
			type = type || 'success';
			
			// Remove existing notices
			$('.qfm-notice').remove();
			
			// Create notice element
			var $notice = $('<div class="qfm-notice ' + type + '">' + message + '</div>');
			
			// Insert notice
			if ($('.wrap h1').length) {
				$('.wrap h1').after($notice);
			} else {
				$('.wrap').prepend($notice);
			}
			
			// Auto-hide success notices
			if (type === 'success') {
				setTimeout(function() {
					$notice.fadeOut(300, function() {
						$(this).remove();
					});
				}, 3000);
			}
		},

		/**
		 * Utility function to get post ID from URL
		 */
		getPostIdFromUrl: function() {
			var urlParams = new URLSearchParams(window.location.search);
			return urlParams.get('post') || null;
		},

		/**
		 * Initialize meta box enhancements
		 */
		initMetaBoxEnhancements: function() {
			// Character counter for question field
			$('#qfm_faq_question').on('input', function() {
				var length = $(this).val().length;
				var maxLength = 500;
				var remaining = maxLength - length;
				
				var $counter = $(this).siblings('.character-counter');
				if (!$counter.length) {
					$counter = $('<div class="character-counter"></div>');
					$(this).after($counter);
				}
				
				$counter.text(remaining + ' characters remaining');
				
				if (remaining < 50) {
					$counter.addClass('warning');
				} else {
					$counter.removeClass('warning');
				}
			});
		}
	};

	/**
	 * Initialize when document is ready
	 */
	$(document).ready(function() {
		// Only initialize on FAQ admin pages
		if ($('body.post-type-qfm_faq').length || $('body.qfm_faq_page_quick-faq-markup-settings').length) {
			QFMAdmin.init();
		}
		
		// Initialize meta box enhancements on edit page
		if ($('#qfm_faq_question').length) {
			QFMAdmin.initMetaBoxEnhancements();
		}
	});

	/**
	 * Make QFMAdmin available globally for debugging
	 */
	window.QFMAdmin = QFMAdmin;

})(jQuery);