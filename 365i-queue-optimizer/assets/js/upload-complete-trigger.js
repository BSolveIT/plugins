(function($) {
    'use strict';
    
    // Wait for the media modal to initialize
    if (typeof wp !== 'undefined' && wp.media && wp.media.view && wp.media.view.Uploader) {
        var origInitialize = wp.media.view.Uploader.prototype.initialize;
        wp.media.view.Uploader.prototype.initialize = function() {
            // Listen for the queue-done event
            this.uploader.bind('UploadComplete', function(up, files) {
                // Only trigger if we have valid AJAX settings
                if (typeof QueueOptimizer !== 'undefined' && QueueOptimizer.ajaxUrl && QueueOptimizer.nonce) {
                    // Trigger our PHP handler
                    $.post(QueueOptimizer.ajaxUrl, {
                        action: 'queue_optimizer_upload_complete',
                        nonce: QueueOptimizer.nonce
                    }).done(function(response) {
                        // Optional: handle successful response
                        if (response.success && window.console) {
                            console.log('Queue Optimizer: Processing triggered after upload completion');
                        }
                    }).fail(function() {
                        // Optional: handle failed response
                        if (window.console) {
                            console.log('Queue Optimizer: Failed to trigger processing after upload');
                        }
                    });
                }
            });

            // Call the original initializer
            origInitialize.apply(this, arguments);
        };
    }
})(jQuery);