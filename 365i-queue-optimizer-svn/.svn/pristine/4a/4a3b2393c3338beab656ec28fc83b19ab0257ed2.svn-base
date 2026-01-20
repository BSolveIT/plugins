# 365i Queue Optimizer Changelog

All notable changes to this project will be documented in this file.

### [1.7.0] - 2025-01-20

#### Added
- Inline help popovers with detailed explanations for each setting
- Click (?) icon next to fields to learn what they do and see recommended values
- Server-specific recommendations and tips in help content
- Accessible keyboard navigation and screen reader support

### [1.6.0] - 2025-01-20

#### Improved
- Settings UX: Server type dropdown now auto-populates recommended settings instantly via AJAX
- No longer need to save before applying recommendations - select server type and settings update immediately
- Streamlined workflow: select server type, adjust if needed, save once

### [1.5.0] - 2025-01-10

#### Fixed
- Server detection being too optimistic (shared hosting incorrectly detected as dedicated)
- Default fallback changed from VPS to Shared for unknown environments

#### Added
- Manual server type override setting (Shared/VPS/Dedicated dropdown)
- More conservative auto-detection thresholds (1GB+ for dedicated, 512MB+ for VPS)

#### Changed
- Lowered recommended settings to safer defaults:
  - Shared: 30s time limit, 1 batch, 25 actions, 3 days retention
  - VPS: 45s time limit, 2 batches, 35 actions, 5 days retention
  - Dedicated: 60s time limit, 4 batches, 50 actions, 7 days retention

### [1.4.2] - 2025-01-10

#### Added
- Save notification that appears when settings are saved and auto-dismisses after 3 seconds

### [1.4.1] - 2025-01-10

#### Added
- WordPress Playground blueprint.json for Live Preview support

#### Fixed
- Contributors field to use valid WordPress.org username

### [1.4.0] - 2025-01-10

#### Added
- Dashboard widget showing queue health, pending/running/failed counts
- Server environment detection (Shared, VPS, Dedicated hosting)
- Recommended settings based on detected server type
- "Apply Recommended Settings" one-click optimization button
- "Run Queue Now" button on both settings page and dashboard widget
- Batch size setting (25-200 actions per batch)
- Data retention setting (1-30 days for completed action logs)
- Queue status display with breakdown by action hook type
- Server environment info panel (PHP, WordPress, memory, image libraries)
- Image processing capabilities display (WebP, AVIF support)

#### Changed
- Enhanced settings page with two-column layout
- Improved current settings display with recommended value indicators
- Updated admin styling for better visual hierarchy

#### Fixed
- Plugin Check compliance (translators comments, variable prefixes, output escaping)
- Removed deprecated load_plugin_textdomain() call (handled by WordPress 4.6+)

### [1.3.1] - 2025-12-17

#### Removed
- Post-Upload Processing setting, AJAX handler, and upload completion script to prevent upload slowdowns.
- Admin UI rows and settings fields related to post-upload processing.

#### Changed
- Default image engine now prioritizes ImageMagick, including activation defaults and admin guidance.
- Uninstall cleanup simplified to drop legacy upload-processing options.

### [1.3.0] - 2025-06-16

#### Security
- Added capability checks to restrict upload-triggered queue runs to users who can upload files.

#### Compatibility
- Switched upload completion handling to bind to the core uploader instead of overriding prototypes for WordPress 6.9.
- Broadened admin screen detection (plus a filter) so CPT uploads still trigger background processing.
- Loaded the plugin text domain to ensure translations work with WordPress.org i18n checks.

#### Performance
- Throttled the fallback metadata hook so ActionScheduler is triggered once per request during bulk uploads.

#### Maintenance
- Updated metadata to Tested up to WordPress 6.9 and raised minimum PHP to 8.0.

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
