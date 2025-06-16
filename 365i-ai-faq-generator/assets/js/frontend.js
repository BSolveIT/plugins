/**
 * Frontend JavaScript for 365i AI FAQ Generator
 * 
 * This file contains all JavaScript functionality for the frontend
 * FAQ display and generation functionality.
 * 
 * @package AI_FAQ_Generator
 * @subpackage Assets
 * @since 2.0.0
 */

(function($) {
	'use strict';

	/**
	 * AI FAQ Generator Frontend Class
	 */
	class AIFAQGenerator {
		
		/**
		 * Constructor
		 */
		constructor() {
			this.init();
		}
		
		/**
		 * Initialize the FAQ generator
		 */
		init() {
			this.bindEvents();
			this.initializeAccordions();
			this.initializeSearch();
			this.loadExistingFAQs();
		}
		
		/**
		 * Bind event listeners
		 */
		bindEvents() {
			// FAQ accordion toggle
			$(document).on('click', '.ai-faq-question', this.toggleAccordion.bind(this));
			
			// FAQ generation form submission
			$(document).on('submit', '.ai-faq-form', this.handleFormSubmission.bind(this));
			
			// Search functionality
			$(document).on('input', '.ai-faq-search-input', this.handleSearch.bind(this));
			
			// Clear search
			$(document).on('click', '.ai-faq-search-clear', this.clearSearch.bind(this));
			
			// Keyboard navigation
			$(document).on('keydown', '.ai-faq-question', this.handleKeyNavigation.bind(this));
		}
		
		/**
		 * Initialize accordion functionality
		 */
		initializeAccordions() {
			$('.ai-faq-item').each(function() {
				const $item = $(this);
				const $question = $item.find('.ai-faq-question');
				const $answer = $item.find('.ai-faq-answer');
				
				// Set ARIA attributes
				$question.attr({
					'role': 'button',
					'aria-expanded': 'false',
					'aria-controls': $answer.attr('id') || 'faq-answer-' + Math.random().toString(36).substr(2, 9),
					'tabindex': '0'
				});
				
				$answer.attr({
					'role': 'region',
					'aria-labelledby': $question.attr('id') || 'faq-question-' + Math.random().toString(36).substr(2, 9)
				});
			});
		}
		
		/**
		 * Initialize search functionality
		 */
		initializeSearch() {
			const $searchContainer = $('.ai-faq-search');
			if ($searchContainer.length === 0) return;
			
			// Add clear button if not present
			if ($searchContainer.find('.ai-faq-search-clear').length === 0) {
				$searchContainer.append('<button type="button" class="ai-faq-search-clear" aria-label="Clear search">×</button>');
			}
		}
		
		/**
		 * Toggle accordion item
		 */
		toggleAccordion(event) {
			event.preventDefault();
			
			const $question = $(event.currentTarget);
			const $item = $question.closest('.ai-faq-item');
			const $answer = $item.find('.ai-faq-answer');
			const isExpanded = $item.hasClass('active');
			
			if (isExpanded) {
				this.closeAccordion($item);
			} else {
				this.openAccordion($item);
			}
		}
		
		/**
		 * Open accordion item
		 */
		openAccordion($item) {
			const $question = $item.find('.ai-faq-question');
			const $answer = $item.find('.ai-faq-answer');
			
			$item.addClass('active');
			$question.attr('aria-expanded', 'true');
			$answer.slideDown(300);
			
			// Trigger custom event
			$item.trigger('faq:opened');
		}
		
		/**
		 * Close accordion item
		 */
		closeAccordion($item) {
			const $question = $item.find('.ai-faq-question');
			const $answer = $item.find('.ai-faq-answer');
			
			$item.removeClass('active');
			$question.attr('aria-expanded', 'false');
			$answer.slideUp(300);
			
			// Trigger custom event
			$item.trigger('faq:closed');
		}
		
		/**
		 * Handle keyboard navigation
		 */
		handleKeyNavigation(event) {
			const $question = $(event.currentTarget);
			
			switch (event.key) {
				case 'Enter':
				case ' ':
					event.preventDefault();
					$question.click();
					break;
				case 'ArrowDown':
					event.preventDefault();
					this.focusNextQuestion($question);
					break;
				case 'ArrowUp':
					event.preventDefault();
					this.focusPreviousQuestion($question);
					break;
				case 'Home':
					event.preventDefault();
					this.focusFirstQuestion();
					break;
				case 'End':
					event.preventDefault();
					this.focusLastQuestion();
					break;
			}
		}
		
		/**
		 * Focus next question
		 */
		focusNextQuestion($currentQuestion) {
			const $questions = $('.ai-faq-question');
			const currentIndex = $questions.index($currentQuestion);
			const nextIndex = (currentIndex + 1) % $questions.length;
			$questions.eq(nextIndex).focus();
		}
		
		/**
		 * Focus previous question
		 */
		focusPreviousQuestion($currentQuestion) {
			const $questions = $('.ai-faq-question');
			const currentIndex = $questions.index($currentQuestion);
			const prevIndex = currentIndex === 0 ? $questions.length - 1 : currentIndex - 1;
			$questions.eq(prevIndex).focus();
		}
		
		/**
		 * Focus first question
		 */
		focusFirstQuestion() {
			$('.ai-faq-question').first().focus();
		}
		
		/**
		 * Focus last question
		 */
		focusLastQuestion() {
			$('.ai-faq-question').last().focus();
		}
		
		/**
		 * Handle form submission
		 */
		handleFormSubmission(event) {
			event.preventDefault();
			
			const $form = $(event.currentTarget);
			const $submitBtn = $form.find('.ai-faq-generate-btn');
			const formData = this.getFormData($form);
			
			// Validate form
			if (!this.validateForm(formData)) {
				return;
			}
			
			// Show loading state
			this.showLoadingState($form, $submitBtn);
			
			// Make AJAX request
			this.generateFAQs(formData, $form)
				.done((response) => {
					this.handleGenerationSuccess(response, $form);
				})
				.fail((xhr) => {
					this.handleGenerationError(xhr, $form);
				})
				.always(() => {
					this.hideLoadingState($form, $submitBtn);
				});
		}
		
		/**
		 * Get form data
		 */
		getFormData($form) {
			const formData = {};
			
			$form.find('input, textarea, select').each(function() {
				const $field = $(this);
				const name = $field.attr('name');
				const value = $field.val();
				
				if (name && value) {
					formData[name] = value;
				}
			});
			
			return formData;
		}
		
		/**
		 * Validate form data
		 */
		validateForm(formData) {
			const errors = [];
			
			// Check required fields
			if (!formData.topic && !formData.url) {
				errors.push('Please provide either a topic or URL');
			}
			
			if (formData.url && !this.isValidURL(formData.url)) {
				errors.push('Please provide a valid URL');
			}
			
			if (formData.num_questions) {
				const numQuestions = parseInt(formData.num_questions);
				if (isNaN(numQuestions) || numQuestions < 1 || numQuestions > 50) {
					errors.push('Number of questions must be between 1 and 50');
				}
			}
			
			// Show errors if any
			if (errors.length > 0) {
				this.showFormErrors(errors);
				return false;
			}
			
			return true;
		}
		
		/**
		 * Check if URL is valid
		 */
		isValidURL(string) {
			try {
				new URL(string);
				return true;
			} catch (_) {
				return false;
			}
		}
		
		/**
		 * Show form errors
		 */
		showFormErrors(errors) {
			const errorHtml = `
				<div class="ai-faq-error">
					<strong>Please correct the following errors:</strong>
					<ul>
						${errors.map(error => `<li>${this.escapeHtml(error)}</li>`).join('')}
					</ul>
				</div>
			`;
			
			$('.ai-faq-error').remove();
			$(errorHtml).insertBefore('.ai-faq-form');
			
			// Scroll to error
			$('html, body').animate({
				scrollTop: $('.ai-faq-error').offset().top - 20
			}, 300);
		}
		
		/**
		 * Show loading state
		 */
		showLoadingState($form, $submitBtn) {
			$submitBtn.addClass('loading').prop('disabled', true);
			$submitBtn.find('.spinner').show();
			
			// Show loading message
			const loadingHtml = `
				<div class="ai-faq-loading">
					<div class="ai-faq-loading-spinner"></div>
					<div class="ai-faq-loading-text">Generating FAQs... This may take a few moments.</div>
				</div>
			`;
			
			$('.ai-faq-loading').remove();
			$(loadingHtml).insertAfter($form);
		}
		
		/**
		 * Hide loading state
		 */
		hideLoadingState($form, $submitBtn) {
			$submitBtn.removeClass('loading').prop('disabled', false);
			$submitBtn.find('.spinner').hide();
			$('.ai-faq-loading').remove();
		}
		
		/**
		 * Generate FAQs via AJAX
		 */
		generateFAQs(formData, $form) {
			const data = {
				action: 'ai_faq_generate',
				nonce: ai_faq_frontend.nonce,
				...formData
			};
			
			return $.ajax({
				url: ai_faq_frontend.ajax_url,
				type: 'POST',
				data: data,
				timeout: 60000 // 60 second timeout
			});
		}
		
		/**
		 * Handle successful FAQ generation
		 */
		handleGenerationSuccess(response, $form) {
			if (!response.success) {
				this.handleGenerationError({ responseJSON: response }, $form);
				return;
			}
			
			// Remove any existing errors
			$('.ai-faq-error').remove();
			
			// Display generated FAQs
			this.displayFAQs(response.data.faqs, $form);
			
			// Show success message
			this.showSuccessMessage(response.data.message || 'FAQs generated successfully!');
			
			// Trigger custom event
			$(document).trigger('faq:generated', [response.data]);
		}
		
		/**
		 * Handle FAQ generation error
		 */
		handleGenerationError(xhr, $form) {
			let errorMessage = 'An error occurred while generating FAQs. Please try again.';
			
			if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
				errorMessage = xhr.responseJSON.data.message;
			} else if (xhr.responseText) {
				errorMessage = xhr.responseText;
			}
			
			this.showFormErrors([errorMessage]);
			
			// Trigger custom event
			$(document).trigger('faq:error', [xhr]);
		}
		
		/**
		 * Display generated FAQs
		 */
		displayFAQs(faqs, $form) {
			if (!faqs || faqs.length === 0) {
				this.showFormErrors(['No FAQs were generated. Please try different parameters.']);
				return;
			}
			
			const faqsHtml = this.generateFAQsHTML(faqs);
			
			// Find or create FAQ container
			let $container = $('.ai-faq-list');
			if ($container.length === 0) {
				$container = $('<div class="ai-faq-list"></div>');
				$container.insertAfter($form);
			}
			
			// Replace content
			$container.html(faqsHtml);
			
			// Initialize new accordions
			this.initializeAccordions();
			
			// Scroll to results
			$('html, body').animate({
				scrollTop: $container.offset().top - 20
			}, 500);
		}
		
		/**
		 * Generate FAQs HTML
		 */
		generateFAQsHTML(faqs) {
			let html = '';
			
			faqs.forEach((faq, index) => {
				const questionId = `faq-question-${index}`;
				const answerId = `faq-answer-${index}`;
				
				html += `
					<div class="ai-faq-item" itemscope itemtype="https://schema.org/Question">
						<button class="ai-faq-question" id="${questionId}" aria-expanded="false" aria-controls="${answerId}">
							<span itemprop="name">${this.escapeHtml(faq.question)}</span>
						</button>
						<div class="ai-faq-answer" id="${answerId}" role="region" aria-labelledby="${questionId}" itemscope itemtype="https://schema.org/Answer">
							<div itemprop="text">${this.formatAnswer(faq.answer)}</div>
						</div>
					</div>
				`;
			});
			
			return html;
		}
		
		/**
		 * Format answer text
		 */
		formatAnswer(answer) {
			// Convert line breaks to paragraphs
			const paragraphs = answer.split('\n\n').filter(p => p.trim());
			return paragraphs.map(p => `<p>${this.escapeHtml(p.trim())}</p>`).join('');
		}
		
		/**
		 * Show success message
		 */
		showSuccessMessage(message) {
			const successHtml = `
				<div class="ai-faq-success" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; padding: 15px; margin: 20px 0;">
					${this.escapeHtml(message)}
				</div>
			`;
			
			$('.ai-faq-success').remove();
			$(successHtml).insertBefore('.ai-faq-list');
			
			// Auto-hide after 5 seconds
			setTimeout(() => {
				$('.ai-faq-success').fadeOut(300, function() {
					$(this).remove();
				});
			}, 5000);
		}
		
		/**
		 * Handle search functionality
		 */
		handleSearch(event) {
			const query = $(event.currentTarget).val().toLowerCase();
			const $items = $('.ai-faq-item');
			let visibleCount = 0;
			
			$items.each(function() {
				const $item = $(this);
				const question = $item.find('.ai-faq-question').text().toLowerCase();
				const answer = $item.find('.ai-faq-answer').text().toLowerCase();
				
				if (question.includes(query) || answer.includes(query)) {
					$item.show();
					visibleCount++;
				} else {
					$item.hide();
				}
			});
			
			// Show/hide no results message
			this.toggleNoResultsMessage(visibleCount === 0 && query.length > 0);
		}
		
		/**
		 * Clear search
		 */
		clearSearch() {
			$('.ai-faq-search-input').val('');
			$('.ai-faq-item').show();
			this.toggleNoResultsMessage(false);
		}
		
		/**
		 * Toggle no results message
		 */
		toggleNoResultsMessage(show) {
			if (show) {
				if ($('.ai-faq-no-results').length === 0) {
					const noResultsHtml = `
						<div class="ai-faq-no-results">
							No FAQs match your search. Try different keywords.
						</div>
					`;
					$(noResultsHtml).insertAfter('.ai-faq-search');
				}
			} else {
				$('.ai-faq-no-results').remove();
			}
		}
		
		/**
		 * Load existing FAQs (if any)
		 */
		loadExistingFAQs() {
			// This method can be extended to load saved FAQs
			// from the database or localStorage
		}
		
		/**
		 * Escape HTML to prevent XSS
		 */
		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}
	}

	/**
	 * Utility functions
	 */
	const AIFAQUtils = {
		
		/**
		 * Debounce function
		 */
		debounce(func, wait, immediate) {
			let timeout;
			return function executedFunction() {
				const context = this;
				const args = arguments;
				const later = function() {
					timeout = null;
					if (!immediate) func.apply(context, args);
				};
				const callNow = immediate && !timeout;
				clearTimeout(timeout);
				timeout = setTimeout(later, wait);
				if (callNow) func.apply(context, args);
			};
		},
		
		/**
		 * Throttle function
		 */
		throttle(func, limit) {
			let inThrottle;
			return function() {
				const args = arguments;
				const context = this;
				if (!inThrottle) {
					func.apply(context, args);
					inThrottle = true;
					setTimeout(() => inThrottle = false, limit);
				}
			};
		}
	};

	/**
	 * Initialize when document is ready
	 */
	$(document).ready(function() {
		// Check if we have the necessary global variables
		if (typeof ai_faq_frontend === 'undefined') {
			console.warn('AI FAQ Generator: Frontend configuration not found');
			return;
		}
		
		// Initialize the FAQ generator
		window.AIFAQGenerator = new AIFAQGenerator();
		
		// Make utilities available globally
		window.AIFAQUtils = AIFAQUtils;
		
		// Trigger initialization event
		$(document).trigger('ai-faq:initialized');
	});

})(jQuery);