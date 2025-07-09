/*!
 * Quick FAQ Markup - Frontend JavaScript
 * Handles accordion functionality, accessibility, and search
 * Version: 1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Quick FAQ Markup Frontend Handler
	 */
	const QFMFrontend = {

		/**
		 * Initialize the frontend functionality
		 */
		init: function() {
			this.setupAccordions();
			this.setupSearch();
			this.setupAnchorLinks();
			this.setupKeyboardNavigation();
			this.handlePageLoad();
		},

		/**
		 * Setup accordion functionality
		 */
		setupAccordions: function() {
			const $accordions = $('.qfm-accordion');
			
			if ($accordions.length === 0) {
				return;
			}

			$accordions.each(function() {
				const $accordion = $(this);
				const $buttons = $accordion.find('.qfm-accordion-button');
				const $panels = $accordion.find('.qfm-accordion-panel');

				// Initialize ARIA attributes
				$buttons.each(function(index) {
					const $button = $(this);
					const $panel = $($button.attr('aria-controls'));
					
					// Ensure proper ARIA setup
					$button.attr({
						'aria-expanded': 'false',
						'tabindex': '0'
					});
					
					$panel.attr({
						'aria-hidden': 'true',
						'hidden': true
					});
				});

				// Handle button clicks
				$buttons.on('click', function(e) {
					e.preventDefault();
					QFMFrontend.toggleAccordionItem($(this));
				});

				// Handle Enter and Space key presses
				$buttons.on('keydown', function(e) {
					if (e.which === 13 || e.which === 32) { // Enter or Space
						e.preventDefault();
						QFMFrontend.toggleAccordionItem($(this));
					}
				});
			});
		},

		/**
		 * Toggle accordion item
		 * @param {jQuery} $button The accordion button
		 */
		toggleAccordionItem: function($button) {
			const $panel = $('#' + $button.attr('aria-controls'));
			const isExpanded = $button.attr('aria-expanded') === 'true';
			const $accordion = $button.closest('.qfm-accordion');
			const allowMultiple = $accordion.data('allow-multiple') !== false;

			// Close other panels if multiple isn't allowed
			if (!allowMultiple && !isExpanded) {
				const $otherButtons = $accordion.find('.qfm-accordion-button').not($button);
				const $otherPanels = $accordion.find('.qfm-accordion-panel').not($panel);
				
				$otherButtons.attr('aria-expanded', 'false');
				$otherPanels.attr('aria-hidden', 'true').attr('hidden', true);
				
				// Remove animation classes
				$otherPanels.removeClass('qfm-slide-down qfm-slide-up');
			}

			// Toggle current panel
			if (isExpanded) {
				this.closeAccordionPanel($button, $panel);
			} else {
				this.openAccordionPanel($button, $panel);
			}

			// Update URL hash if panel has an ID
			const panelId = $panel.closest('.qfm-accordion-item').attr('id');
			if (panelId && !isExpanded) {
				this.updateHash(panelId);
			}

			// Log interaction for analytics
			this.logInteraction('accordion_toggle', {
				faq_id: $panel.closest('.qfm-accordion-item').attr('id'),
				action: isExpanded ? 'close' : 'open'
			});
		},

		/**
		 * Open accordion panel
		 * @param {jQuery} $button The accordion button
		 * @param {jQuery} $panel The accordion panel
		 */
		openAccordionPanel: function($button, $panel) {
			$button.attr('aria-expanded', 'true');
			$panel.attr('aria-hidden', 'false').removeAttr('hidden');
			
			// Add animation class if animations are enabled
			if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
				$panel.addClass('qfm-slide-down');
				
				// Remove animation class after animation completes
				setTimeout(function() {
					$panel.removeClass('qfm-slide-down');
				}, 300);
			}
		},

		/**
		 * Close accordion panel
		 * @param {jQuery} $button The accordion button
		 * @param {jQuery} $panel The accordion panel
		 */
		closeAccordionPanel: function($button, $panel) {
			$button.attr('aria-expanded', 'false');
			
			// Add animation class if animations are enabled
			if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
				$panel.addClass('qfm-slide-up');
				
				// Hide panel after animation completes
				setTimeout(function() {
					$panel.attr('aria-hidden', 'true').attr('hidden', true);
					$panel.removeClass('qfm-slide-up');
				}, 300);
			} else {
				$panel.attr('aria-hidden', 'true').attr('hidden', true);
			}
		},

		/**
		 * Setup search functionality
		 */
		setupSearch: function() {
			const $searchBoxes = $('.qfm-search-box');
			
			if ($searchBoxes.length === 0) {
				return;
			}

			$searchBoxes.each(function() {
				const $searchBox = $(this);
				const $input = $searchBox.find('.qfm-search-input');
				const $clearButton = $searchBox.find('.qfm-search-clear');
				const $container = $searchBox.closest('.qfm-faq-container');
				
				let searchTimeout;

				// Handle search input
				$input.on('input', function() {
					const query = $(this).val().trim();
					
					// Show/hide clear button
					$clearButton.toggle(query.length > 0);
					
					// Debounce search
					clearTimeout(searchTimeout);
					searchTimeout = setTimeout(function() {
						QFMFrontend.performSearch(query, $container);
					}, 300);
				});

				// Handle clear button
				$clearButton.on('click', function() {
					$input.val('').trigger('input').focus();
				});

				// Handle Enter key
				$input.on('keydown', function(e) {
					if (e.which === 13) { // Enter key
						e.preventDefault();
						const query = $(this).val().trim();
						QFMFrontend.performSearch(query, $container);
					}
				});
			});
		},

		/**
		 * Perform search within FAQ container
		 * @param {string} query The search query
		 * @param {jQuery} $container The FAQ container
		 */
		performSearch: function(query, $container) {
			const $items = $container.find('.qfm-faq-item, .qfm-accordion-item, .qfm-card');
			let visibleCount = 0;

			if (query === '') {
				// Show all items
				$items.show().removeClass('qfm-search-hidden');
				visibleCount = $items.length;
			} else {
				// Filter items
				$items.each(function() {
					const $item = $(this);
					const question = $item.find('.qfm-question, .qfm-question-text, .qfm-card-question').text().toLowerCase();
					const answer = $item.find('.qfm-answer, .qfm-card-answer').text().toLowerCase();
					const searchText = (question + ' ' + answer).toLowerCase();
					
					if (searchText.indexOf(query.toLowerCase()) !== -1) {
						$item.show().removeClass('qfm-search-hidden');
						visibleCount++;
						
						// Open accordion item if it matches
						const $button = $item.find('.qfm-accordion-button');
						if ($button.length && $button.attr('aria-expanded') === 'false') {
							this.openAccordionPanel($button, $('#' + $button.attr('aria-controls')));
						}
					} else {
						$item.hide().addClass('qfm-search-hidden');
					}
				}.bind(this));
			}

			// Show/hide no results message
			this.handleSearchResults($container, visibleCount, query);

			// Log search interaction
			this.logInteraction('search', {
				query: query,
				results_count: visibleCount
			});
		},

		/**
		 * Handle search results display
		 * @param {jQuery} $container The FAQ container
		 * @param {number} visibleCount Number of visible results
		 * @param {string} query The search query
		 */
		handleSearchResults: function($container, visibleCount, query) {
			let $noResults = $container.find('.qfm-no-results');
			
			if (visibleCount === 0 && query !== '') {
				if ($noResults.length === 0) {
					$noResults = $('<div class="qfm-no-results" role="status" aria-live="polite"></div>');
					$container.append($noResults);
				}
				
				$noResults.html(
					'<p>' + 
					qfmPublic.strings.no_results.replace('%s', '<strong>' + this.escapeHtml(query) + '</strong>') + 
					'</p>'
				).show();
			} else {
				$noResults.hide();
			}
		},

		/**
		 * Setup anchor link functionality
		 */
		setupAnchorLinks: function() {
			// Handle anchor links within FAQ items
			$('.qfm-anchor-link').on('click', function(e) {
				const href = $(this).attr('href');
				
				if (href && href.startsWith('#')) {
					const targetId = href.substring(1);
					const $target = $('#' + targetId);
					
					if ($target.length) {
						e.preventDefault();
						QFMFrontend.scrollToTarget($target);
						QFMFrontend.updateHash(targetId);
					}
				}
			});
		},

		/**
		 * Setup keyboard navigation
		 */
		setupKeyboardNavigation: function() {
			// Handle arrow key navigation for accordions
			$('.qfm-accordion').on('keydown', '.qfm-accordion-button', function(e) {
				const $buttons = $(this).closest('.qfm-accordion').find('.qfm-accordion-button');
				const currentIndex = $buttons.index(this);
				let nextIndex;

				switch (e.which) {
					case 38: // Up arrow
						e.preventDefault();
						nextIndex = currentIndex > 0 ? currentIndex - 1 : $buttons.length - 1;
						$buttons.eq(nextIndex).focus();
						break;
					case 40: // Down arrow
						e.preventDefault();
						nextIndex = currentIndex < $buttons.length - 1 ? currentIndex + 1 : 0;
						$buttons.eq(nextIndex).focus();
						break;
					case 36: // Home
						e.preventDefault();
						$buttons.first().focus();
						break;
					case 35: // End
						e.preventDefault();
						$buttons.last().focus();
						break;
				}
			});
		},

		/**
		 * Handle page load and hash targeting
		 */
		handlePageLoad: function() {
			// Handle hash on page load
			if (window.location.hash) {
				const targetId = window.location.hash.substring(1);
				const $target = $('#' + targetId);
				
				if ($target.length) {
					// Small delay to ensure page is fully loaded
					setTimeout(function() {
						QFMFrontend.scrollToTarget($target);
					}, 100);
				}
			}

			// Handle hash changes
			$(window).on('hashchange', function() {
				if (window.location.hash) {
					const targetId = window.location.hash.substring(1);
					const $target = $('#' + targetId);
					
					if ($target.length) {
						QFMFrontend.scrollToTarget($target);
					}
				}
			});
		},

		/**
		 * Scroll to target element
		 * @param {jQuery} $target The target element
		 */
		scrollToTarget: function($target) {
			// Open accordion item if it's closed
			const $button = $target.find('.qfm-accordion-button');
			if ($button.length && $button.attr('aria-expanded') === 'false') {
				this.openAccordionPanel($button, $('#' + $button.attr('aria-controls')));
			}

			// Scroll to target with offset for fixed headers
			const offset = this.getScrollOffset();
			const targetTop = $target.offset().top - offset;
			
			// Smooth scroll if supported and not reduced motion
			if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
				$('html, body').animate({
					scrollTop: targetTop
				}, 500, function() {
					// Focus management for accessibility
					QFMFrontend.manageFocus($target);
				});
			} else {
				window.scrollTo(0, targetTop);
				this.manageFocus($target);
			}
		},

		/**
		 * Get scroll offset for fixed headers
		 * @return {number} Scroll offset in pixels
		 */
		getScrollOffset: function() {
			// Check for common fixed header selectors
			const $adminBar = $('#wpadminbar');
			const $siteHeader = $('.site-header, .main-header, header');
			let offset = 20; // Default offset

			if ($adminBar.length && $adminBar.is(':visible')) {
				offset += $adminBar.outerHeight();
			}

			if ($siteHeader.length) {
				const headerHeight = $siteHeader.outerHeight();
				if ($siteHeader.css('position') === 'fixed' || $siteHeader.css('position') === 'sticky') {
					offset += headerHeight;
				}
			}

			return offset;
		},

		/**
		 * Manage focus for accessibility
		 * @param {jQuery} $target The target element
		 */
		manageFocus: function($target) {
			// Find the best element to focus
			let $focusTarget = $target.find('.qfm-accordion-button').first();
			
			if ($focusTarget.length === 0) {
				$focusTarget = $target.find('.qfm-anchor-link').first();
			}
			
			if ($focusTarget.length === 0) {
				// Make the target focusable temporarily
				$target.attr('tabindex', '-1');
				$focusTarget = $target;
			}

			// Focus the element
			$focusTarget.focus();

			// Remove temporary tabindex after a short delay
			if ($focusTarget.is($target)) {
				setTimeout(function() {
					$target.removeAttr('tabindex');
				}, 1000);
			}
		},

		/**
		 * Update URL hash without triggering scroll
		 * @param {string} hash The hash to set
		 */
		updateHash: function(hash) {
			if (history.replaceState) {
				history.replaceState(null, null, '#' + hash);
			} else {
				// Fallback for older browsers
				const scrollTop = $(window).scrollTop();
				window.location.hash = hash;
				$(window).scrollTop(scrollTop);
			}
		},

		/**
		 * Escape HTML to prevent XSS
		 * @param {string} text The text to escape
		 * @return {string} Escaped text
		 */
		escapeHtml: function(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		},

		/**
		 * Log user interactions for analytics
		 * @param {string} action The action type
		 * @param {Object} data Additional data
		 */
		logInteraction: function(action, data) {
			// Only log if analytics functions are available
			if (typeof gtag === 'function') {
				gtag('event', 'qfm_' + action, data);
			} else if (typeof _gaq !== 'undefined') {
				_gaq.push(['_trackEvent', 'Quick FAQ Markup', action, JSON.stringify(data)]);
			}
		},

		/**
		 * Handle responsive behavior
		 */
		handleResponsive: function() {
			// Handle window resize
			$(window).on('resize', this.debounce(function() {
				// Recalculate any layout-dependent functionality
			}, 250));
		},

		/**
		 * Debounce function to limit rate of function calls
		 * @param {Function} func The function to debounce
		 * @param {number} wait Wait time in milliseconds
		 * @return {Function} Debounced function
		 */
		debounce: function(func, wait) {
			let timeout;
			return function executedFunction(...args) {
				const later = function() {
					clearTimeout(timeout);
					func(...args);
				};
				clearTimeout(timeout);
				timeout = setTimeout(later, wait);
			};
		}
	};

	/**
	 * Initialize when document is ready
	 */
	$(document).ready(function() {
		// Ensure qfmPublic is available
		if (typeof qfmPublic === 'undefined') {
			window.qfmPublic = {
				strings: {
					expand: 'Expand',
					collapse: 'Collapse',
					loading: 'Loading...',
					no_results: 'No FAQs found matching "%s"'
				}
			};
		}

		// Add default no_results string if missing
		if (!qfmPublic.strings.no_results) {
			qfmPublic.strings.no_results = 'No FAQs found matching "%s"';
		}

		// Initialize the frontend
		QFMFrontend.init();
	});

	/**
	 * Export for external access if needed
	 */
	window.QFMFrontend = QFMFrontend;

})(jQuery);