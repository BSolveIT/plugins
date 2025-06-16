# 365i Queue Optimizer Changelog

All notable changes to this project will be documented in this file.

### [1.2.0] - 2025-06-16

#### Added
- **JavaScript-Based Upload Detection**: Revolutionary approach using WordPress media uploader events to detect when all uploads are complete
- **Post-Upload Processing**: Triggers ActionScheduler immediately after bulk or single uploads finish, eliminating upload slowdowns
- **AJAX Handler**: Clean AJAX endpoint to process upload completion notifications from the frontend
- **Backward Compatibility**: Automatic migration from `queue_optimizer_immediate_processing` to `queue_optimizer_post_upload_processing`

#### Technical Implementation
- **New JavaScript File**: `assets/js/upload-complete-trigger.js` - Hooks into WordPress media uploader events
- **Enhanced Main Class**: 
  - `enqueue_admin_scripts()` - Enqueues upload detection script on relevant pages
  - `handle_upload_complete_ajax()` - Processes AJAX requests when uploads complete
  - `maybe_handle_option_upgrade()` - Handles backward compatibility for option names
- **Updated Settings**: Renamed "Immediate Processing" to "Post-Upload Processing" for clarity
- **Smart Script Loading**: Only loads JavaScript on media-related admin pages (upload, post, page)

#### Performance Improvements
- **Eliminated Upload Slowdowns**: No processing during upload, only after completion
- **Precise Detection**: Uses WordPress's native `UploadComplete` event for accurate timing
- **Zero Guesswork**: No delays or rate limiting needed - knows exactly when uploads finish
- **Optimized for Bulk Uploads**: Single ActionScheduler trigger regardless of batch size

#### User Interface Updates
- **Terminology Update**: Changed "Immediate Processing" to "Post-Upload Processing" throughout UI
- **Enhanced Status Display**: Updated admin status table to reflect new processing approach
- **Improved Descriptions**: Clearer explanations of how the feature works

#### Backward Compatibility
- **Automatic Option Migration**: Seamlessly upgrades from old option name during plugin initialization
- **Legacy Support**: Maintains fallback processing for edge cases
- **Clean Uninstall**: Removes both old and new options during plugin removal
#### Security
- **Fixed WordPress Security Issues**: Resolved nonce verification warnings by eliminating direct $_POST access
- **Enhanced Form Data Handling**: Added safer method to check AJAX action context without security risks
- **Improved Code Security**: Replaced direct superglobal access with WordPress-approved alternatives

### [1.1.1] - 2025-06-16

#### Changed
- **Documentation**: Added reference to detailed plugin guide in readme.txt Support section
- **Plugin Header**: Updated Plugin URI to specific blog post documentation
- **Plugin Header**: Updated Author URI to personal profile page

## [1.1.0] - 2025-06-15

### WordPress Repository Preparation
Major restructuring to make the plugin ready for WordPress repository submission.

#### Added
- **Security Files**: Added index.php files to all directories for enhanced security
- **LICENSE.txt**: Full GPL v2 license text for repository compliance
- **uninstall.php**: Proper WordPress uninstall script replacing static method
- **Translation Support**: Added languages/365i-queue-optimizer.pot template file
- **Template System**: Separated HTML from PHP with dedicated template files
- **JavaScript Enhancement**: Added assets/js/admin.js for form validation and UX
- **Filter Hooks**: Added extensibility filters around data arrays
- **Screenshots**: Added screenshot-1.png and screenshot-2.png for WordPress repository visual documentation
- **Plugin Check Compliance**: Fixed output escaping for QUEUE_OPTIMIZER_MIN_WP_VERSION and updated "Tested up to" version to 6.6

#### Changed
- **Directory Structure**: Reorganized to use src/ for PHP logic, templates/ for HTML
- **Code Separation**: Moved main class to src/class-queue-optimizer-main.php
- **Template Architecture**: Created templates/admin/ and templates/partials/ structure
- **Asset Management**: Enhanced CSS with form validation styles
- **Extensibility**: Added filters for time_limit, concurrent_batches, and image_editors

#### Technical Improvements
- **WordPress Standards**: Full compliance with WordPress coding standards
- **PSR-4 Structure**: Proper autoloading-ready file organization
- **Template Partials**: Reusable header.php and footer.php components
- **Enhanced Security**: Directory protection and proper file structure
- **Repository Ready**: All WordPress.org plugin directory requirements met

## [1.0.0] - 2025-06-15

### Initial Release
A lightweight WordPress plugin designed to optimize ActionScheduler queue processing for faster image optimization and background tasks.

### Features
- **ActionScheduler Time Limit Optimization**: Configurable processing time limit (10-300 seconds, default: 60)
- **Concurrent Batch Processing**: Configurable concurrent batches (1-10 batches, default: 4)  
- **Image Processing Engine Control**: Choose between GD and ImageMagick for optimal performance
- **Clean Settings Interface**: Simple configuration page under Tools > Queue Optimizer
- **Current Status Display**: Shows optimization status and ActionScheduler health
- **WordPress Integration**: Proper WordPress coding standards and plugin architecture

### Core Functionality
Applies three essential ActionScheduler filters:
- `action_scheduler_queue_runner_time_limit` - Controls how long ActionScheduler processes queued tasks
- `action_scheduler_queue_runner_concurrent_batches` - Controls how many tasks run simultaneously  
- `wp_image_editors` - Prioritizes chosen image processing engine (GD/ImageMagick)

### Technical Details
- **Ultra-Lightweight**: Only 5 files total for minimal server impact
- **Performance Focused**: Zero unnecessary overhead or complex features
- **WordPress Standards**: Follows WordPress coding standards and security best practices
- **Modern PHP**: Requires PHP 7.4+ and WordPress 5.8+
- **Singleton Pattern**: Efficient resource usage with single instance architecture

### Settings
- **Time Limit**: 10-300 seconds (default: 60) - How long ActionScheduler runs per batch
- **Concurrent Batches**: 1-10 batches (default: 4) - Number of simultaneous background processes
- **Image Engine**: GD or ImageMagick (default: GD) - Which image processing library to prioritize

### Installation
1. Upload plugin files to `/wp-content/plugins/365i-queue-optimizer/`
2. Activate the plugin through WordPress admin
3. Configure settings under Tools > Queue Optimizer
4. Optimizations apply automatically - no additional setup required

### Use Cases
- **Image Optimization**: Faster processing of image optimization tasks
- **Background Tasks**: Improved performance for any ActionScheduler-dependent plugins
- **High-Volume Sites**: Better handling of large queues and concurrent processing
- **Plugin Compatibility**: Works with any plugin that uses ActionScheduler (Elementor, WooCommerce, etc.)

### Philosophy
Simple, fast, and effective. This plugin does one thing well: optimize ActionScheduler performance with minimal overhead and maximum compatibility.