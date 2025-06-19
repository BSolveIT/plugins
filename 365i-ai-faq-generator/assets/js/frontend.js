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
			this.initializeFormElements();
			this.initializeManualEditor();
			this.initializeStorageManagement();
			this.loadExistingFAQs();
			this.initializeSettingsIntegration();
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
			
			// Method selection cards
			$(document).on('click', '.ai-faq-method-card', this.handleMethodSelection.bind(this));
			
			// Direct radio button changes (backup handling)
			$(document).on('change', 'input[name="generation_method"]', this.handleMethodChange.bind(this));
			
			// Tone and schema selection
			$(document).on('click', '.ai-faq-tone-option, .ai-faq-schema-option', this.handleOptionSelection.bind(this));
			
			// Manual FAQ editor
			$(document).on('click', '.ai-faq-add-question-btn', this.addManualQuestion.bind(this));
			$(document).on('click', '.ai-faq-remove-question-btn', this.removeManualQuestion.bind(this));
			$(document).on('click', '.ai-faq-load-template-btn', this.loadQuestionTemplate.bind(this));
			
			// Storage management
			$(document).on('click', '.ai-faq-save-btn', this.saveFAQs.bind(this));
			$(document).on('click', '.ai-faq-load-btn', this.loadFAQs.bind(this));
			$(document).on('click', '.ai-faq-export-btn', this.exportFAQs.bind(this));
			$(document).on('click', '.ai-faq-import-btn', this.importFAQs.bind(this));
			
			// Version history
			$(document).on('click', '.ai-faq-version-restore-btn', this.restoreVersion.bind(this));
			$(document).on('change', '.ai-faq-version-select', this.previewVersion.bind(this));
			
			// Collapsible section
			$(document).on('click', '.ai-faq-collapsible-header', this.toggleCollapsibleSection.bind(this));
			$(document).on('keydown', '.ai-faq-collapsible-header', this.handleCollapsibleKeydown.bind(this));
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
		 * Initialize form elements and set default states
		 */
		initializeFormElements() {
			// Initialize based on already checked radio buttons in the HTML
			this.syncActiveStatesWithRadios();
			
			// Initialize sliders with current values
			this.initializeSliders();
		}
		
		/**
		 * Sync active CSS classes with checked radio buttons
		 */
		syncActiveStatesWithRadios() {
			// Handle method selection
			$('input[name="generation_method"]').each(function() {
				const $radio = $(this);
				const $label = $(`label[for="${$radio.attr('id')}"]`);
				
				if ($radio.is(':checked')) {
					$label.addClass('active');
				} else {
					$label.removeClass('active');
				}
			});
			
			// Handle tone selection
			$('input[name="tone"]').each(function() {
				const $radio = $(this);
				const $label = $(`label[for="${$radio.attr('id')}"]`);
				
				if ($radio.is(':checked')) {
					$label.addClass('active');
				} else {
					$label.removeClass('active');
				}
			});
			
			// Handle schema selection
			$('input[name="schema_output"]').each(function() {
				const $radio = $(this);
				const $label = $(`label[for="${$radio.attr('id')}"]`);
				
				if ($radio.is(':checked')) {
					$label.addClass('active');
				} else {
					$label.removeClass('active');
				}
			});
			
			// Handle content sections based on method selection
			const checkedMethod = $('input[name="generation_method"]:checked');
			if (checkedMethod.length) {
				this.toggleContentSections(checkedMethod.val());
			}
		}
		
		/**
		 * Initialize slider functionality
		 */
		initializeSliders() {
			// Handle number of questions slider
			$(document).on('input', '[id^="num_questions_"]:not([id*="value"])', function() {
				const $slider = $(this);
				const value = $slider.val();
				const sliderId = $slider.attr('id');
				const valueId = sliderId.replace('num_questions_', 'num_questions_value_');
				const $valueDisplay = $('#' + valueId);
				
				if ($valueDisplay.length) {
					$valueDisplay.text(value + ' question' + (value === '1' ? '' : 's'));
				}
			});
			
			// Handle answer length slider
			$(document).on('input', '[id^="length_"]:not([id*="value"])', function() {
				const $slider = $(this);
				const value = parseInt($slider.val());
				const lengthLabels = ['Short', 'Medium', 'Long', 'Detailed'];
				const sliderId = $slider.attr('id');
				const valueId = sliderId.replace('length_', 'length_value_');
				const $valueDisplay = $('#' + valueId);
				
				if ($valueDisplay.length) {
					const index = value - 1;
					$valueDisplay.text(lengthLabels[index] || 'Medium');
				}
			});
			
			// Initialize slider values on page load - use setTimeout to ensure DOM is ready
			setTimeout(() => {
				$('[id^="num_questions_"]:not([id*="value"])').each(function() {
					const $slider = $(this);
					const value = $slider.val();
					const sliderId = $slider.attr('id');
					const valueId = sliderId.replace('num_questions_', 'num_questions_value_');
					const $valueDisplay = $('#' + valueId);
					
					if ($valueDisplay.length) {
						$valueDisplay.text(value + ' question' + (value === '1' ? '' : 's'));
					}
				});
				
				$('[id^="length_"]:not([id*="value"])').each(function() {
					const $slider = $(this);
					const value = parseInt($slider.val());
					const lengthLabels = ['Short', 'Medium', 'Long', 'Detailed'];
					const sliderId = $slider.attr('id');
					const valueId = sliderId.replace('length_', 'length_value_');
					const $valueDisplay = $('#' + valueId);
					
					if ($valueDisplay.length) {
						const index = value - 1;
						$valueDisplay.text(lengthLabels[index] || 'Medium');
					}
				});
			}, 100);
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
		 * Handle method selection
		 */
		handleMethodSelection(event) {
			// Don't prevent default - let the label work normally
			const $clickedCard = $(event.currentTarget);
			
			// Find the associated radio input (it's a sibling, not a child)
			const labelFor = $clickedCard.attr('for');
			const $radioInput = $(`#${labelFor}`);
			
			if (!$radioInput.length) return;
			
			// Directly trigger the radio change
			$radioInput.prop('checked', true).trigger('change');
		}
		
		/**
		 * Handle method radio button changes
		 */
		handleMethodChange(event) {
			const $radio = $(event.currentTarget);
			const methodValue = $radio.val();
			const groupName = $radio.attr('name');
			
			// Remove active class from all method cards
			$(`input[name="${groupName}"]`).each(function() {
				$(`label[for="${$(this).attr('id')}"]`).removeClass('active');
			});
			
			// Add active class to the card associated with the checked radio
			const $associatedLabel = $(`label[for="${$radio.attr('id')}"]`);
			$associatedLabel.addClass('active');
			
			// Show/hide relevant content sections
			this.toggleContentSections(methodValue);
			
			// Trigger custom event
			$(document).trigger('faq:method-selected', [methodValue]);
		}
		
		/**
		 * Handle option selection (tone, schema, etc.)
		 */
		handleOptionSelection(event) {
			event.preventDefault();
			
			const $clickedOption = $(event.currentTarget);
			
			// Find the associated radio input (it's a sibling, not a child)
			const labelFor = $clickedOption.attr('for');
			const $radioInput = $(`#${labelFor}`);
			
			if (!$radioInput.length) return;
			
			const optionValue = $radioInput.val();
			const groupName = $radioInput.attr('name');
			
			// Remove active class from all options in the same group and uncheck all radios
			$(`input[name="${groupName}"]`).each(function() {
				$(this).prop('checked', false);
				$(`label[for="${$(this).attr('id')}"]`).removeClass('active');
			});
			
			// Add active class to clicked option and check its radio
			$clickedOption.addClass('active');
			$radioInput.prop('checked', true);
			
			// Trigger custom event
			$(document).trigger('faq:option-selected', [groupName, optionValue]);
		}
		
		/**
		 * Toggle content sections based on method selection
		 */
		toggleContentSections(methodValue) {
			// Hide all content sections first
			$('#url-import-content, #ai-url-content, #schema-import-content, #manual-content').hide();
			
			// Show the appropriate section based on method
			switch (methodValue) {
				case 'import_url':
					$('#url-import-content').show();
					break;
				case 'ai_url':
					$('#ai-url-content').show();
					break;
				case 'import_schema':
					$('#schema-import-content').show();
					break;
				case 'manual':
					$('#manual-content').show();
					break;
			}
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
		 * Initialize manual FAQ editor
		 */
		initializeManualEditor() {
			// Add initial empty state if manual questions container exists but is empty
			const $manualContainer = $('.ai-faq-manual-questions');
			if ($manualContainer.length && $manualContainer.children().length === 0) {
				this.updateManualEditorState();
			}
		}
		
		/**
		 * Initialize storage management
		 */
		initializeStorageManagement() {
			this.updateStorageInfo();
			this.loadVersionHistory();
		}
		
		/**
		 * Toggle collapsible section
		 */
		toggleCollapsibleSection(event) {
			event.preventDefault();
			
			const $header = $(event.currentTarget);
			const controlsId = $header.attr('aria-controls');
			const $content = controlsId ? $('#' + controlsId) : $header.siblings('.ai-faq-collapsible-content');
			const isExpanded = $header.attr('aria-expanded') === 'true';
			
			if ($content.length === 0) {
				console.warn('Collapsible content not found');
				return;
			}
			
			if (isExpanded) {
				this.collapseSection($header, $content);
			} else {
				this.expandSection($header, $content);
			}
		}
		
		/**
		 * Handle keyboard navigation for collapsible header
		 */
		handleCollapsibleKeydown(event) {
			const $header = $(event.currentTarget);
			
			switch (event.key) {
				case 'Enter':
				case ' ':
					event.preventDefault();
					$header.click();
					break;
			}
		}
		
		/**
		 * Expand collapsible section
		 */
		expandSection($header, $content) {
			$header.attr('aria-expanded', 'true');
			$content.removeClass('collapsing').addClass('expanding');
			$content.css('display', 'block');
			
			// Remove animation class after animation completes
			setTimeout(() => {
				$content.removeClass('expanding');
			}, 300);
		}
		
		/**
		 * Collapse collapsible section
		 */
		collapseSection($header, $content) {
			$header.attr('aria-expanded', 'false');
			$content.removeClass('expanding').addClass('collapsing');
			
			// Hide content after animation completes
			setTimeout(() => {
				$content.css('display', 'none').removeClass('collapsing');
			}, 300);
		}
		
		/**
		 * Add manual question
		 */
		addManualQuestion(event) {
			event.preventDefault();
			
			const $container = $('.ai-faq-manual-questions');
			const questionIndex = $container.children().length;
			
			const questionHtml = `
				<div class="ai-faq-manual-question-item">
					<button type="button" class="ai-faq-remove-question-btn" aria-label="Remove question">×</button>
					<div class="ai-faq-form-group">
						<label class="ai-faq-form-label">Question ${questionIndex + 1}</label>
						<input type="text" class="ai-faq-form-input" name="manual_questions[${questionIndex}][question]" placeholder="Enter your question here..." required>
					</div>
					<div class="ai-faq-form-group">
						<label class="ai-faq-form-label">Answer</label>
						<textarea class="ai-faq-form-textarea" name="manual_questions[${questionIndex}][answer]" rows="4" placeholder="Enter the answer here..." required></textarea>
					</div>
				</div>
			`;
			
			$container.append(questionHtml);
			this.updateManualEditorState();
			
			// Focus on the new question input
			$container.find('.ai-faq-manual-question-item:last .ai-faq-form-input').focus();
		}
		
		/**
		 * Remove manual question
		 */
		removeManualQuestion(event) {
			event.preventDefault();
			
			const $questionItem = $(event.currentTarget).closest('.ai-faq-manual-question-item');
			$questionItem.remove();
			
			// Reindex remaining questions
			$('.ai-faq-manual-question-item').each(function(index) {
				const $item = $(this);
				$item.find('.ai-faq-form-label').first().text(`Question ${index + 1}`);
				$item.find('input, textarea').each(function() {
					const name = $(this).attr('name');
					if (name) {
						const newName = name.replace(/\[\d+\]/, `[${index}]`);
						$(this).attr('name', newName);
					}
				});
			});
			
			this.updateManualEditorState();
		}
		
		/**
		 * Load question template
		 */
		loadQuestionTemplate(event) {
			event.preventDefault();
			
			const templates = [
				{
					question: "What is [Your Product/Service]?",
					answer: "Provide a clear, concise description of what your product or service is and what it does for customers."
				},
				{
					question: "How much does [Your Product/Service] cost?",
					answer: "Include your pricing information, available plans, and any special offers or discounts."
				},
				{
					question: "How do I get started with [Your Product/Service]?",
					answer: "Explain the onboarding process, first steps, and what customers need to do to begin using your product or service."
				},
				{
					question: "What support options are available?",
					answer: "Detail your customer support channels, hours of operation, and response times."
				},
				{
					question: "Can I cancel or refund my purchase?",
					answer: "Explain your cancellation policy, refund terms, and the process customers should follow."
				}
			];
			
			// Add template questions
			templates.forEach(() => {
				this.addManualQuestion(event);
			});
			
			// Populate with template content
			$('.ai-faq-manual-question-item').each(function(index) {
				if (templates[index]) {
					$(this).find('input[name*="question"]').val(templates[index].question);
					$(this).find('textarea[name*="answer"]').val(templates[index].answer);
				}
			});
		}
		
		/**
		 * Update manual editor state
		 */
		updateManualEditorState() {
			const $container = $('.ai-faq-manual-questions');
			const hasQuestions = $container.children().length > 0;
			
			if (hasQuestions) {
				$container.removeClass('ai-faq-manual-questions-empty');
			} else {
				$container.addClass('ai-faq-manual-questions-empty');
			}
		}
		
		/**
		 * Save FAQs to local storage
		 */
		saveFAQs(event) {
			event.preventDefault();
			
			const faqData = this.getCurrentFAQData();
			const timestamp = new Date().toISOString();
			const saveKey = `ai-faq-save-${Date.now()}`;
			
			try {
				// Save current state
				localStorage.setItem(saveKey, JSON.stringify({
					data: faqData,
					timestamp: timestamp,
					version: '1.0'
				}));
				
				// Add to version history
				this.addToVersionHistory(saveKey, timestamp);
				
				// Update UI
				this.updateStorageInfo();
				this.loadVersionHistory();
				
				this.showNotification('FAQs saved successfully!', 'success');
			} catch (error) {
				this.showNotification('Failed to save FAQs. Storage may be full.', 'error');
				console.error('Save error:', error);
			}
		}
		
		/**
		 * Load FAQs from local storage
		 */
		loadFAQs(event) {
			event.preventDefault();
			
			const savedKeys = this.getSavedFAQKeys();
			if (savedKeys.length === 0) {
				this.showNotification('No saved FAQs found.', 'info');
				return;
			}
			
			// Show load dialog
			this.showLoadDialog(savedKeys);
		}
		
		/**
		 * Export FAQs
		 */
		exportFAQs(event) {
			event.preventDefault();
			
			const faqData = this.getCurrentFAQData();
			const exportData = {
				faqs: faqData,
				exported: new Date().toISOString(),
				source: 'AI FAQ Generator',
				version: '1.0'
			};
			
			// Create and download file
			const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
			const url = URL.createObjectURL(blob);
			const a = document.createElement('a');
			a.href = url;
			a.download = `faqs-export-${new Date().toISOString().split('T')[0]}.json`;
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
			URL.revokeObjectURL(url);
			
			this.showNotification('FAQs exported successfully!', 'success');
		}
		
		/**
		 * Import FAQs
		 */
		importFAQs(event) {
			event.preventDefault();
			
			// Create file input
			const input = document.createElement('input');
			input.type = 'file';
			input.accept = '.json';
			input.onchange = (e) => {
				const file = e.target.files[0];
				if (!file) return;
				
				const reader = new FileReader();
				reader.onload = (e) => {
					try {
						const importData = JSON.parse(e.target.result);
						this.processImportedFAQs(importData);
					} catch (error) {
						this.showNotification('Invalid file format. Please select a valid FAQ export file.', 'error');
						console.error('Import error:', error);
					}
				};
				reader.readAsText(file);
			};
			input.click();
		}
		
		/**
		 * Restore version from history
		 */
		restoreVersion(event) {
			event.preventDefault();
			
			const $select = $('.ai-faq-version-select');
			const selectedKey = $select.val();
			
			if (!selectedKey) {
				this.showNotification('Please select a version to restore.', 'info');
				return;
			}
			
			try {
				const savedData = JSON.parse(localStorage.getItem(selectedKey));
				if (savedData && savedData.data) {
					this.loadFAQData(savedData.data);
					this.showNotification('Version restored successfully!', 'success');
				} else {
					this.showNotification('Selected version data not found.', 'error');
				}
			} catch (error) {
				this.showNotification('Failed to restore version.', 'error');
				console.error('Restore error:', error);
			}
		}
		
		/**
		 * Preview version
		 */
		previewVersion(event) {
			const selectedKey = $(event.currentTarget).val();
			if (!selectedKey) return;
			
			try {
				const savedData = JSON.parse(localStorage.getItem(selectedKey));
				if (savedData && savedData.data) {
					// Show preview in a tooltip or modal
					this.showVersionPreview(savedData);
				}
			} catch (error) {
				console.error('Preview error:', error);
			}
		}
		
		/**
		 * Get current FAQ data
		 */
		getCurrentFAQData() {
			const data = {
				method: $('input[name="generation_method"]:checked').val(),
				form_data: {},
				faqs: []
			};
			
			// Get form data
			$('.ai-faq-form input, .ai-faq-form textarea, .ai-faq-form select').each(function() {
				const $field = $(this);
				const name = $field.attr('name');
				const value = $field.val();
				
				if (name && value) {
					data.form_data[name] = value;
				}
			});
			
			// Get displayed FAQs
			$('.ai-faq-item').each(function() {
				const $item = $(this);
				const question = $item.find('.ai-faq-question span').text();
				const answer = $item.find('.ai-faq-answer [itemprop="text"]').text();
				
				if (question && answer) {
					data.faqs.push({ question, answer });
				}
			});
			
			return data;
		}
		
		/**
		 * Load FAQ data into form and display
		 */
		loadFAQData(data) {
			// Load form data
			if (data.form_data) {
				Object.keys(data.form_data).forEach(name => {
					const $field = $(`[name="${name}"]`);
					if ($field.length) {
						$field.val(data.form_data[name]);
						if ($field.is(':radio')) {
							$field.prop('checked', true).trigger('change');
						}
					}
				});
			}
			
			// Load FAQ display
			if (data.faqs && data.faqs.length > 0) {
				this.displayFAQs(data.faqs, $('.ai-faq-form'));
			}
			
			// Update method selection
			if (data.method) {
				$(`input[name="generation_method"][value="${data.method}"]`).prop('checked', true).trigger('change');
			}
			
			// Re-initialize form elements
			this.initializeFormElements();
		}
		
		/**
		 * Update storage info display
		 */
		updateStorageInfo() {
			const usage = this.calculateStorageUsage();
			const lastSaved = this.getLastSavedTime();
			
			$('#storage-usage').text(this.formatBytes(usage));
			$('#last-saved').text(lastSaved || 'Never');
		}
		
		/**
		 * Load version history
		 */
		loadVersionHistory() {
			const $select = $('.ai-faq-version-select');
			const savedKeys = this.getSavedFAQKeys();
			
			$select.empty().append('<option value="">Select a version to restore...</option>');
			
			savedKeys.forEach(key => {
				try {
					const data = JSON.parse(localStorage.getItem(key));
					if (data && data.timestamp) {
						const date = new Date(data.timestamp);
						const label = date.toLocaleString();
						$select.append(`<option value="${key}">${label}</option>`);
					}
				} catch (error) {
					console.error('Error loading version:', error);
				}
			});
		}
		
		/**
		 * Show notification
		 */
		showNotification(message, type = 'info') {
			const typeClasses = {
				success: 'ai-faq-success',
				error: 'ai-faq-error',
				info: 'ai-faq-info',
				warning: 'ai-faq-warning'
			};
			
			const className = typeClasses[type] || typeClasses.info;
			const notification = $(`
				<div class="${className}" style="position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 15px 20px; border-radius: 8px; color: white; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-width: 400px;">
					${this.escapeHtml(message)}
				</div>
			`);
			
			$('body').append(notification);
			
			// Auto-remove after 5 seconds
			setTimeout(() => {
				notification.fadeOut(300, function() {
					$(this).remove();
				});
			}, 5000);
		}
		
		/**
		 * Storage utility methods
		 */
		getSavedFAQKeys() {
			const keys = [];
			for (let i = 0; i < localStorage.length; i++) {
				const key = localStorage.key(i);
				if (key && key.startsWith('ai-faq-save-')) {
					keys.push(key);
				}
			}
			return keys.sort((a, b) => {
				const timeA = parseInt(a.split('-').pop());
				const timeB = parseInt(b.split('-').pop());
				return timeB - timeA; // Most recent first
			});
		}
		
		calculateStorageUsage() {
			let total = 0;
			this.getSavedFAQKeys().forEach(key => {
				const data = localStorage.getItem(key);
				if (data) {
					total += new Blob([data]).size;
				}
			});
			return total;
		}
		
		getLastSavedTime() {
			const keys = this.getSavedFAQKeys();
			if (keys.length === 0) return null;
			
			try {
				const latest = localStorage.getItem(keys[0]);
				const data = JSON.parse(latest);
				return new Date(data.timestamp).toLocaleString();
			} catch (error) {
				return null;
			}
		}
		
		formatBytes(bytes) {
			if (bytes === 0) return '0 Bytes';
			const k = 1024;
			const sizes = ['Bytes', 'KB', 'MB'];
			const i = Math.floor(Math.log(bytes) / Math.log(k));
			return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
		}
		
		addToVersionHistory(key, timestamp) {
			// Keep only the latest 10 versions
			const keys = this.getSavedFAQKeys();
			if (keys.length > 10) {
				const oldKeys = keys.slice(10);
				oldKeys.forEach(oldKey => {
					localStorage.removeItem(oldKey);
				});
			}
		}
		
		/**
		 * Process imported FAQs
		 */
		processImportedFAQs(importData) {
			if (!importData.faqs || !Array.isArray(importData.faqs)) {
				this.showNotification('Invalid FAQ data in import file.', 'error');
				return;
			}
			
			// Confirm import
			if (confirm(`Import ${importData.faqs.length} FAQs? This will replace current content.`)) {
				this.loadFAQData(importData);
				this.showNotification(`Successfully imported ${importData.faqs.length} FAQs!`, 'success');
			}
		}
		
		/**
		 * Show version preview
		 */
		showVersionPreview(savedData) {
			const faqCount = savedData.data.faqs ? savedData.data.faqs.length : 0;
			const method = savedData.data.method || 'Unknown';
			const timestamp = new Date(savedData.timestamp).toLocaleString();
			
			this.showNotification(`Version from ${timestamp}: ${faqCount} FAQs, Method: ${method}`, 'info');
		}
		
		/**
		 * Load existing FAQs (if any)
		 */
		loadExistingFAQs() {
			// This method can be extended to load saved FAQs
			// from the database or localStorage
			this.updateStorageInfo();
		}
		
		/**
		 * Initialize settings integration with dynamic sync
		 */
		initializeSettingsIntegration() {
			// Wait for settings sync to be ready
			$(document).on('ai-faq:settings-sync-ready', () => {
				this.onSettingsSyncReady();
			});
			
			// Listen for settings updates
			$(document).on('ai-faq:settings-updated', (event, newSettings, oldSettings) => {
				this.onSettingsUpdated(newSettings, oldSettings);
			});
			
			// If settings sync is already available, initialize immediately
			if (window.AIFAQSettingsSync) {
				this.onSettingsSyncReady();
			}
		}
		
		/**
		 * Handle settings sync ready event
		 */
		onSettingsSyncReady() {
			if (window.AIFAQSettingsSync) {
				// Store reference to settings sync
				this.settingsSync = window.AIFAQSettingsSync;
				
				// Apply current settings to interface
				this.applyCurrentSettings();
				
				// Set up debounce delay from settings
				const debounceDelay = this.settingsSync.getSetting('performance.debounce_delay', 300);
				this.updateDebounceDelay(debounceDelay);
			}
		}
		
		/**
		 * Handle settings update event
		 */
		onSettingsUpdated(newSettings, oldSettings) {
			// Update debounce delay if performance settings changed
			if (newSettings.performance && newSettings.performance.debounce_delay !== oldSettings.performance?.debounce_delay) {
				this.updateDebounceDelay(newSettings.performance.debounce_delay);
			}
			
			// Update auto-save interval if changed
			if (newSettings.general && newSettings.general.auto_save_interval !== oldSettings.general?.auto_save_interval) {
				this.updateAutoSaveInterval(newSettings.general.auto_save_interval);
			}
			
			// Refresh form defaults if generation settings changed
			if (newSettings.generation && JSON.stringify(newSettings.generation) !== JSON.stringify(oldSettings.generation)) {
				this.updateFormDefaults(newSettings.generation);
			}
		}
		
		/**
		 * Apply current settings to interface
		 */
		applyCurrentSettings() {
			if (!this.settingsSync) return;
			
			const settings = this.settingsSync.getSettings();
			
			// Apply generation defaults
			if (settings.generation) {
				this.updateFormDefaults(settings.generation);
			}
			
			// Apply performance settings
			if (settings.performance) {
				this.updateDebounceDelay(settings.performance.debounce_delay || 300);
			}
			
			// Apply general settings
			if (settings.general) {
				this.updateAutoSaveInterval(settings.general.auto_save_interval || 3);
			}
		}
		
		/**
		 * Update debounce delay for performance optimization
		 */
		updateDebounceDelay(delay) {
			this.debounceDelay = parseInt(delay) || 300;
			
			// Update search debouncing if it exists
			if (this.searchDebounce) {
				clearTimeout(this.searchDebounce);
				this.searchDebounce = null;
			}
		}
		
		/**
		 * Update auto-save interval
		 */
		updateAutoSaveInterval(intervalMinutes) {
			this.autoSaveInterval = (parseInt(intervalMinutes) || 3) * 60000; // Convert to milliseconds
			
			// Restart auto-save timer if it exists
			if (this.autoSaveTimer) {
				clearInterval(this.autoSaveTimer);
				this.startAutoSave();
			}
		}
		
		/**
		 * Update form defaults based on generation settings
		 */
		updateFormDefaults(generationSettings) {
			const $form = $('.ai-faq-form');
			if ($form.length === 0) return;
			
			// Update tone default
			if (generationSettings.default_tone) {
				const $toneInput = $form.find(`input[name="tone"][value="${generationSettings.default_tone}"]`);
				if ($toneInput.length && !$toneInput.is(':checked')) {
					$toneInput.prop('checked', true).trigger('change');
				}
			}
			
			// Update length default
			if (generationSettings.default_length) {
				const $lengthInput = $form.find(`input[name="length"][value="${generationSettings.default_length}"]`);
				if ($lengthInput.length && !$lengthInput.is(':checked')) {
					$lengthInput.prop('checked', true).trigger('change');
				}
			}
			
			// Update schema default
			if (generationSettings.default_schema_type) {
				const $schemaInput = $form.find(`input[name="schema_output"][value="${generationSettings.default_schema_type}"]`);
				if ($schemaInput.length && !$schemaInput.is(':checked')) {
					$schemaInput.prop('checked', true).trigger('change');
				}
			}
		}
		
		/**
		 * Start auto-save functionality
		 */
		startAutoSave() {
			if (this.autoSaveInterval && this.autoSaveInterval > 0) {
				this.autoSaveTimer = setInterval(() => {
					this.performAutoSave();
				}, this.autoSaveInterval);
			}
		}
		
		/**
		 * Perform auto-save operation
		 */
		performAutoSave() {
			const faqData = this.getCurrentFAQData();
			if (faqData.faqs && faqData.faqs.length > 0) {
				try {
					const timestamp = new Date().toISOString();
					const autoSaveKey = `ai-faq-autosave-${Date.now()}`;
					
					localStorage.setItem(autoSaveKey, JSON.stringify({
						data: faqData,
						timestamp: timestamp,
						version: '1.0',
						auto_save: true
					}));
					
					// Clean up old auto-saves (keep only last 3)
					this.cleanupAutoSaves();
					
					// Show subtle notification
					this.showNotification('Auto-saved', 'info');
				} catch (error) {
					console.warn('Auto-save failed:', error);
				}
			}
		}
		
		/**
		 * Clean up old auto-save entries
		 */
		cleanupAutoSaves() {
			const autoSaveKeys = [];
			for (let i = 0; i < localStorage.length; i++) {
				const key = localStorage.key(i);
				if (key && key.startsWith('ai-faq-autosave-')) {
					autoSaveKeys.push(key);
				}
			}
			
			// Sort by timestamp and keep only the 3 most recent
			autoSaveKeys.sort((a, b) => {
				const timeA = parseInt(a.split('-').pop());
				const timeB = parseInt(b.split('-').pop());
				return timeB - timeA;
			});
			
			// Remove old auto-saves
			autoSaveKeys.slice(3).forEach(key => {
				localStorage.removeItem(key);
			});
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