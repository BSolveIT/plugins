/**
 * Documentation JavaScript
 *
 * Handles the documentation page navigation and interactions
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/admin/js
 */

(function($) {
    'use strict';

    /**
     * Initialize the documentation page functionality
     */
    function initDocumentation() {
        // Handle navigation clicks
        $('.faq-ai-doc-navigation a').on('click', function(e) {
            e.preventDefault();
            
            // Get the target section
            var target = $(this).attr('href');
            
            // Scroll to the section
            $('html, body').animate({
                scrollTop: $(target).offset().top - 50
            }, 500);
            
            // Update URL hash
            window.location.hash = target;
        });
        
        // Check for hash in URL on page load
        if (window.location.hash) {
            var hash = window.location.hash;
            
            // Scroll to the section after a short delay to ensure page is fully loaded
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: $(hash).offset().top - 50
                }, 500);
            }, 300);
        }
        
        // Add active class to navigation items when scrolling
        $(window).on('scroll', function() {
            var scrollPosition = $(window).scrollTop();
            
            // Check each section
            $('.faq-ai-doc-section').each(function() {
                var currentSection = $(this);
                var sectionTop = currentSection.offset().top - 100;
                var sectionBottom = sectionTop + currentSection.outerHeight();
                
                if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                    // Remove active class from all links
                    $('.faq-ai-doc-navigation a').removeClass('active');
                    
                    // Add active class to corresponding navigation item
                    $('.faq-ai-doc-navigation a[href="#' + currentSection.attr('id') + '"]').addClass('active');
                }
            });
        });
        
        // Handle copy code buttons
        $('.faq-ai-doc-copy-button').on('click', function() {
            var codeBlock = $(this).prev('pre').find('code');
            var textToCopy = codeBlock.text();
            
            // Create a temporary textarea element to copy the text
            var textarea = document.createElement('textarea');
            textarea.value = textToCopy;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            // Show copied message
            var originalText = $(this).text();
            $(this).text('Copied!');
            
            // Reset button text after a delay
            var button = $(this);
            setTimeout(function() {
                button.text(originalText);
            }, 2000);
        });
        
        // Add copy buttons to all code blocks
        $('pre').each(function() {
            var copyButton = $('<button class="faq-ai-doc-copy-button">Copy</button>');
            $(this).after(copyButton);
        });
        
        // Handle expandable sections
        $('.faq-ai-doc-expandable-header').on('click', function() {
            $(this).next('.faq-ai-doc-expandable-content').slideToggle();
            $(this).toggleClass('expanded');
        });
    }
    
    // Initialize when document is ready
    $(document).ready(function() {
        initDocumentation();
    });

})(jQuery);