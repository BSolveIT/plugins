=== 365i Queue Optimizer ===
Contributors: bsolveit
Tags: actionscheduler, queue, optimization, performance, background-tasks
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 1.6.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight WordPress plugin to optimize ActionScheduler queue processing for faster image optimization and background tasks.

== Description ==

**365i Queue Optimizer** is an ultra-lightweight WordPress plugin designed to optimize ActionScheduler performance for faster image processing and background task execution. It automatically detects your server environment and recommends optimal settings. Perfect for sites using image optimization plugins, WooCommerce, or any plugin that relies on ActionScheduler.

### What This Plugin Does

This plugin applies essential ActionScheduler optimizations with intelligent server detection:

* **Time Limit Control** - Sets how long ActionScheduler processes tasks (default: 60 seconds, configurable 10-300)
* **Concurrent Batch Processing** - Controls simultaneous background processes (default: 4 batches, configurable 1-10)
* **Batch Size Control** - Sets how many actions to process per batch (default: 50, configurable 25-200)
* **Data Retention** - Controls how long completed action logs are kept (default: 7 days, configurable 1-30)
* **Image Processing Engine** - Prioritizes your chosen image processor (ImageMagick by default, GD fallback)

### Key Features

* **Dashboard Widget** - At-a-glance queue status on your WordPress dashboard with health indicators
* **Server Detection** - Automatically detects your hosting type (Shared, VPS, Dedicated) and recommends optimal settings
* **One-Click Optimization** - Apply recommended settings instantly based on your server environment
* **Run Queue Now** - Manually trigger queue processing when you need immediate results
* **Ultra-Lightweight** - Minimal server impact with no complex logging or debugging overhead
* **Simple Configuration** - Clean settings page under Tools > Queue Optimizer
* **Instant Results** - Optimizations apply automatically after activation
* **WordPress Standards** - Follows all WordPress coding and security standards
* **ImageMagick-First Defaults** - Prefers ImageMagick for better quality and stability when available

### Perfect For

* **Image Optimization** - Faster processing with Elementor Image Optimizer, Smush, ShortPixel, etc.
* **WooCommerce Sites** - Better handling of product imports, order processing, and bulk operations
* **High-Volume Sites** - Improved performance for sites with large background task queues
* **Plugin Compatibility** - Works with any plugin that uses ActionScheduler

### Philosophy

Simple, fast, and effective. This plugin does one thing well: optimize ActionScheduler performance with minimal overhead and maximum compatibility.

== Installation ==

### Automatic Installation

1. Log in to your WordPress admin panel
2. Navigate to **Plugins > Add New**
3. Search for "365i Queue Optimizer"
4. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin zip file
2. Extract to `/wp-content/plugins/365i-queue-optimizer/`
3. Activate through the **Plugins** menu in WordPress

### GitHub Installation

1. Clone from GitHub: `git clone https://github.com/BSolveIT/plugins.git`
2. Copy the `365i-queue-optimizer` folder to `/wp-content/plugins/`
3. Activate through the **Plugins** menu in WordPress

### Configuration

1. Go to **Tools > Queue Optimizer** in WordPress admin
2. Review your detected server type and recommended settings
3. Configure your preferred settings:
   - **Time Limit**: How long ActionScheduler runs per batch (10-300 seconds)
   - **Concurrent Batches**: Number of simultaneous processes (1-10 batches)
   - **Batch Size**: Actions processed per batch (25-200 actions)
   - **Data Retention**: How long to keep completed action logs (1-30 days)
   - **Image Engine**: Prioritize ImageMagick (default) or switch to GD if needed
4. Click **Save Settings** or use **Apply Recommended Settings** for one-click optimization
5. Optimizations apply automatically - no further setup required

== Usage ==

### Dashboard Widget

The Queue Optimizer dashboard widget appears on your WordPress admin dashboard and shows:

* **Health Status** - Overall queue health (Healthy, Backlog, or Needs Attention)
* **Pending Actions** - Number of actions waiting to be processed
* **Running Actions** - Actions currently being processed
* **Failed Actions** - Actions that failed in the last 24 hours
* **Run Queue Button** - Manually trigger queue processing

### Settings Configuration

**Time Limit (10-300 seconds)**
Controls how long ActionScheduler processes tasks in each batch. Recommended values:
* Shared hosting: 30 seconds
* VPS/Managed: 60 seconds
* Dedicated/High: 120 seconds

**Concurrent Batches (1-10 batches)**
Determines how many background processes run simultaneously. Recommended values:
* Shared hosting: 2 batches
* VPS/Managed: 4 batches
* Dedicated/High: 8 batches

**Batch Size (25-200 actions)**
Sets how many actions are processed in each batch. Recommended values:
* Shared hosting: 25 actions
* VPS/Managed: 50 actions
* Dedicated/High: 100 actions

**Data Retention (1-30 days)**
Controls how long completed action logs are stored. Lower values reduce database size. Recommended values:
* Shared hosting: 3 days
* VPS/Managed: 7 days
* Dedicated/High: 14 days

**Image Processing Engine (ImageMagick or GD)**
Prioritizes your chosen image processing library. ImageMagick is preferred for quality and stability.

### Server Environment

The settings page displays your server environment including:
* Detected hosting type (Shared, VPS, Dedicated)
* PHP version and memory limit
* WordPress version
* Max execution time
* ImageMagick and GD availability
* WebP and AVIF support

### Queue Status

Monitor your ActionScheduler queue with real-time stats:
* Pending actions count with breakdown by hook type
* Currently running actions
* Failed actions in the last 24 hours
* Quick link to view all actions in ActionScheduler

== Frequently Asked Questions ==

= How does this plugin improve performance? =

It optimizes key ActionScheduler settings that control how WordPress processes background tasks. This results in faster image optimization, smoother WooCommerce operations, and better handling of any background processing.

= What is the dashboard widget? =

The dashboard widget provides a quick overview of your queue health directly on your WordPress admin dashboard. It shows pending, running, and failed action counts, plus a button to manually run the queue.

= How does server detection work? =

The plugin analyzes your PHP memory limit and max execution time to determine if you're on shared hosting, VPS/managed hosting, or a dedicated/high-performance server. It then recommends optimal settings for your environment.

= Will this affect my website's front-end performance? =

No. This plugin only affects background task processing and has zero impact on your website's front-end performance. It actually improves overall site responsiveness by making background tasks more efficient.

= Is this compatible with my existing plugins? =

Yes. This plugin works with any plugin that uses ActionScheduler, including Elementor, WooCommerce, image optimization plugins, and backup plugins. It enhances their performance rather than conflicting with them.

= Do I need to configure anything after installation? =

The plugin works with sensible defaults immediately after activation. However, you can use the "Apply Recommended Settings" button to optimize for your specific server environment.

= What happens if I deactivate the plugin? =

All optimizations are immediately removed and ActionScheduler returns to its default behavior. Your site will continue working normally, just without the performance enhancements.

= Can I use this on shared hosting? =

Yes. The plugin detects shared hosting environments and recommends conservative settings that are safe for limited resources.

= How do I know if it's working? =

Check the dashboard widget for queue health status, or visit Tools > Queue Optimizer to see current settings and queue statistics. After activation, image processing and background tasks should be noticeably faster.

= What do the health statuses mean? =

* **Healthy** (green) - Queue is processing normally with no issues
* **Backlog** (yellow) - More than 50 pending actions; consider running the queue manually
* **Needs Attention** (red) - Failed actions detected in the last 24 hours; investigate the ActionScheduler logs

== Screenshots ==

1. **Settings Page** - Configuration interface with server type selection, optimization settings, and recommended values based on your hosting environment
2. **Dashboard Widget** - At-a-glance queue health monitoring on your WordPress dashboard showing pending, running, and failed actions

== Changelog ==

= 1.6.0 - 2025-01-20 =

**Improved Settings UX**

* Server type dropdown now auto-populates recommended settings immediately when changed
* No need to save first before applying recommendations - settings update instantly via AJAX
* "Apply Recommended Settings" button still works as a shortcut
* Streamlined workflow: select server type, adjust if needed, save once

= 1.5.0 - 2025-01-10 =

**Server Detection Improvements**

* Fixed server detection being too optimistic (shared hosting incorrectly detected as dedicated)
* Added manual server type override setting - choose Shared, VPS, or Dedicated manually
* More conservative auto-detection thresholds (requires 1GB+ for dedicated, 512MB+ for VPS)
* Lowered recommended settings to safer defaults that work better on shared resources
* Default fallback changed from VPS to Shared for unknown environments

**Changed Recommended Settings:**
* Shared: 30s time limit, 1 batch, 25 actions, 3 days retention
* VPS: 45s time limit, 2 batches, 35 actions, 5 days retention
* Dedicated: 60s time limit, 4 batches, 50 actions, 7 days retention

= 1.4.2 - 2025-01-10 =
* Added save notification that appears when settings are saved and auto-dismisses after 3 seconds

= 1.4.1 - 2025-01-10 =
* Added WordPress Playground blueprint.json for Live Preview support
* Fixed Contributors field to use valid WordPress.org username

= 1.4.0 - 2025-01-10 =

**Dashboard Widget & Server Detection**

Major feature update adding intelligent server detection and a dashboard widget for at-a-glance queue monitoring.

**Added:**
* Dashboard widget showing queue health, pending/running/failed counts
* Server environment detection (Shared, VPS, Dedicated hosting)
* Recommended settings based on detected server type
* "Apply Recommended Settings" one-click optimization button
* "Run Queue Now" button on both settings page and dashboard widget
* Batch size setting (25-200 actions per batch)
* Data retention setting (1-30 days for completed action logs)
* Queue status display with breakdown by action hook type
* Server environment info panel (PHP, WordPress, memory, image libraries)
* Image processing capabilities display (WebP, AVIF support)

**Changed:**
* Enhanced settings page with two-column layout
* Improved current settings display with recommended value indicators
* Updated admin styling for better visual hierarchy

**Fixed:**
* Plugin Check compliance (translators comments, variable prefixes, output escaping)
* Removed deprecated load_plugin_textdomain() call (handled by WordPress 4.6+)

**Technical:**
* New dashboard widget class with health status calculation
* Server type detection based on memory and execution time
* AJAX handlers for queue running and settings application
* Enhanced uninstall script to clean up all plugin data

= 1.3.1 - 2025-12-17 =
* Removed the Post-Upload Processing setting and associated upload completion trigger to avoid slowing uploads.
* Simplified the settings and status UI to match the streamlined feature set.
* Switched ImageMagick to the default/recommended image engine across settings and activation defaults.
* Cleaned uninstall logic to drop legacy upload-processing options.

= 1.3.0 - 2025-06-16 =
* Added capability checks to restrict upload-triggered queue runs to users who can upload files.
* Bound upload completion handling to the core uploader instead of overriding prototypes for WordPress 6.9 compatibility.
* Broadened admin screen detection (with a new filter) so CPT uploads trigger background processing.
* Loaded the plugin text domain for translations and tighter repository compliance.
* Throttled the fallback metadata hook to fire queue runs once per request.
* Tested up to WordPress 6.9 and raised minimum PHP to 8.0.

= 1.2.0 - 2025-06-16 =

**Revolutionary JavaScript-Based Post-Upload Processing (removed in 1.3.1)**

This major update introduces a groundbreaking approach to post-upload processing that completely eliminates upload slowdowns while maintaining instant ActionScheduler optimization.

**Added:**
* JavaScript-based upload detection using WordPress media uploader events
* Post-upload processing that triggers ActionScheduler after uploads complete
* AJAX handler for clean upload completion notifications
* Backward compatibility with automatic option migration
* Enhanced security with WordPress-approved form data handling

**Performance Improvements:**
* Eliminated upload slowdowns completely
* Precise detection using WordPress's native UploadComplete event
* Zero guesswork - no delays or rate limiting needed
* Optimized for both single and bulk upload scenarios

**Security:**
* Fixed WordPress Plugin Check security warnings
* Removed direct $_POST access for enhanced security
* Added safer AJAX action detection methods

**Technical:**
* New JavaScript file: assets/js/upload-complete-trigger.js (removed in 1.3.1)
* Enhanced main class with upload detection capabilities
* Updated settings terminology from "Immediate Processing" to "Post-Upload Processing"
* Smart script loading only on relevant admin pages

= 1.1.0 - 2025-06-15 =

**WordPress Repository Preparation**

Major restructuring to make the plugin ready for WordPress repository submission.

**Added:**
* Security files: index.php protection in all directories
* LICENSE.txt: Full GPL v2 license text for repository compliance
* uninstall.php: Proper WordPress uninstall script
* Translation support: languages/365i-queue-optimizer.pot template file
* Template system: Separated HTML from PHP with dedicated template files
* JavaScript enhancement: assets/js/admin.js for form validation and UX
* Filter hooks: Added extensibility filters around data arrays
* Screenshots: Added screenshot-1.png and screenshot-2.png for WordPress repository

**Changed:**
* Directory structure: Reorganized to use src/ for PHP logic, templates/ for HTML
* Code separation: Moved main class to src/class-queue-optimizer-main.php
* Template architecture: Created templates/admin/ and templates/partials/ structure
* Asset management: Enhanced CSS with form validation styles
* Extensibility: Added filters for time_limit, concurrent_batches, and image_editors

**Technical Improvements:**
* WordPress standards: Full compliance with WordPress coding standards
* PSR-4 structure: Proper autoloading-ready file organization
* Template partials: Reusable header.php and footer.php components
* Enhanced security: Directory protection and proper file structure
* Repository ready: All WordPress.org plugin directory requirements met

= 1.0.0 - 2025-06-15 =

**Initial Release**

A lightweight WordPress plugin designed to optimize ActionScheduler queue processing for faster image optimization and background tasks.

**Features:**
* ActionScheduler Time Limit Optimization (10-300 seconds, default: 60)
* Concurrent Batch Processing Control (1-10 batches, default: 4)
* Image Processing Engine Priority (GD/ImageMagick selection)
* Clean Settings Interface under Tools > Queue Optimizer
* Current Status Display showing optimization health
* Ultra-Lightweight Architecture (only 5 files)

**Core Functionality:**
* `action_scheduler_queue_runner_time_limit` filter
* `action_scheduler_queue_runner_concurrent_batches` filter
* `wp_image_editors` filter for engine prioritization

**Technical Details:**
* WordPress 5.8+ and PHP 7.4+ required
* WordPress coding standards compliant
* Singleton pattern for efficient resource usage
* Proper settings validation and sanitization
* Complete cleanup on uninstall

== Upgrade Notice ==

= 1.5.0 =
Important fix: Server detection now more conservative to prevent failures on shared hosting. Adds manual server type override. Recommended settings lowered to safer defaults.

= 1.4.2 =
UX improvement: Save notification now appears when settings are saved.

= 1.4.1 =
Added WordPress Playground Live Preview support.

= 1.4.0 =
Major feature update: Dashboard widget for queue monitoring, server detection with recommended settings, batch size and retention controls. Full Plugin Check compliance.

= 1.3.1 =
Removes post-upload processing to fix upload slowdowns. Switches to ImageMagick as default engine.

= 1.3.0 =
Security and compatibility release for WordPress 6.9. Adds capability checks, safer media handling, and translation loading.

= 1.0.0 =
Initial release of 365i Queue Optimizer. A lightweight solution for optimizing ActionScheduler performance with minimal overhead.

== Support ==

For detailed documentation and usage examples, see the [complete plugin guide](https://www.365i.co.uk/blog/2025/04/20/fix-wordpress-6-8-slow-image-uploads-with-365i-queue-optimizer/).

For support and WordPress hosting solutions optimized for performance, visit [365i WordPress Hosting](https://www.365i.co.uk/).

Professional WordPress hosting with ActionScheduler optimization built-in.
