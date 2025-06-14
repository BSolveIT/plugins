=== 365i Queue Optimizer ===
Contributors: 365i
Tags: queue, scheduler, background-jobs, optimization, performance
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight WordPress plugin to manage and optimize background queue processing with native WP scheduling.

== Description ==

**365i Queue Optimizer** is a simple yet powerful plugin designed to help WordPress site owners and developers manage background queue processing efficiently. Built with WordPress standards in mind, it provides a clean interface for controlling how your site handles scheduled tasks and background jobs.

### Key Features

* **Native WordPress Integration** - Uses built-in `wp_schedule_event` for reliable scheduling
* **Configurable Time Limits** - Set processing time limits from 5 to 300 seconds
* **Concurrent Batch Processing** - Control how many batches run simultaneously (1-10 batches)
* **Real-time Dashboard** - View pending, processing, and completed job counts
* **Manual Processing** - "Run Now" button for immediate queue processing
* **Optional Logging** - Enable detailed logging for debugging and monitoring
* **Clean Uninstall** - Completely removes all data when uninstalled
* **Security First** - Proper nonce verification and capability checks
* **Translation Ready** - Full internationalization support

### Perfect For

* **Site Owners** who need fine-tuned control over background jobs like image processing, report generation, or data imports
* **Developers** who want an easy-to-read, easy-to-extend plugin skeleton for scheduling tasks
* **Agencies** managing multiple WordPress sites with custom background processing needs

### Technical Highlights

* PSR-12 PHP coding standards
* WordPress Coding Standards compliant
* Minimal overhead - assets only load on plugin pages
* Secure AJAX endpoints with nonce verification
* Capability checks for all admin actions
* Responsive admin interface
* Compatible with all major WordPress admin color schemes

### Professional Support

This plugin is developed and maintained by [365i WordPress Hosting](https://www.365i.co.uk/), specialists in high-performance WordPress hosting solutions.

== Installation ==

### Automatic Installation

1. Log in to your WordPress admin panel
2. Navigate to **Plugins > Add New**
3. Search for "365i Queue Optimizer"
4. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin zip file
2. Extract the files to your `/wp-content/plugins/365i-queue-optimizer/` directory
3. Activate the plugin through the **Plugins** menu in WordPress

### After Installation

1. Navigate to **Tools > Queue Optimizer** in your WordPress admin
2. Configure your preferred settings:
   - **Time Limit**: Maximum processing time per run (5-300 seconds)
   - **Concurrent Batches**: Number of batches to process simultaneously (1-10)
   - **Enable Logging**: Turn on detailed logging for debugging
3. Click **Save Settings**
4. Use the **Run Now** button to test queue processing
5. Monitor the status dashboard for real-time updates

== Usage ==

### Basic Configuration

Once installed, the plugin provides a simple settings interface under **Tools > Queue Optimizer**:

**Time Limit Settings**
Set how long (in seconds) the queue processor should run during each execution. Lower values are safer for shared hosting environments.

**Concurrent Batches**
Control how many job batches are processed simultaneously. Higher values can improve performance but may increase server load.

**Logging Options**
Enable logging to track queue processing activity. Logs are stored in the plugin's logs directory and can be cleared via the admin interface.

### Dashboard Features

The plugin includes a real-time dashboard showing:

* **Pending Jobs** - Tasks waiting to be processed
* **Processing Jobs** - Currently active tasks
* **Completed Jobs** - Successfully finished tasks
* **Last Run Time** - When the queue was last processed

### Manual Processing

Use the **Run Now** button to immediately process the queue without waiting for the scheduled interval. This is useful for:

* Testing configuration changes
* Processing urgent jobs
* Debugging queue issues

### Log Management

When logging is enabled:

* Daily log files are created in `/wp-content/plugins/365i-queue-optimizer/logs/`
* Use the **Clear Logs** button to remove old log files
* Logs include timestamps and detailed processing information

== Frequently Asked Questions ==

= How often does the queue process automatically? =

By default, the queue processes every hour using WordPress's built-in cron system. This can be customized by developers using WordPress hooks.

= Can I add my own jobs to the queue? =

Yes! The plugin provides WordPress actions and filters that developers can use to add custom jobs. See the plugin's source code for available hooks.

= Will this plugin slow down my website? =

No. The plugin only loads its assets on the admin settings page and processes jobs in the background. It's designed to have minimal impact on site performance.

= What happens if I deactivate the plugin? =

Deactivating the plugin stops all queue processing and clears scheduled events. Your data remains intact until you uninstall the plugin.

= What happens when I uninstall the plugin? =

Complete cleanup! All plugin options, log files, scheduled events, and data are permanently removed from your database.

= Is this plugin compatible with multisite? =

The plugin is designed to work on single WordPress installations. Multisite compatibility may require additional configuration.

= Can I customize the processing logic? =

Yes! Developers can extend the plugin using WordPress actions and filters. The code follows WordPress standards and is well-documented.

= Does this plugin create any database tables? =

No. The plugin uses WordPress options for data storage, keeping your database clean and avoiding compatibility issues.

== Screenshots ==

1. **Settings Panel** - Configure time limits, concurrent batches, and logging options
2. **Status Dashboard** - Real-time view of pending, processing, and completed jobs
3. **Admin Interface** - Clean, responsive design that works on all devices

== Changelog ==

= 1.0.0 - 2025-06-14 =
**Initial Release**

* Native WordPress scheduling integration
* Configurable time limits (5-300 seconds)
* Concurrent batch processing (1-10 batches)
* Real-time status dashboard
* Manual "Run Now" functionality
* Optional detailed logging
* Secure AJAX endpoints with nonce verification
* Complete uninstall cleanup
* Translation ready with full i18n support
* Responsive admin interface
* WordPress Coding Standards compliant
* Professional 365i branding integration

== Upgrade Notice ==

= 1.0.0 =
Initial release of 365i Queue Optimizer. A lightweight, secure solution for WordPress background queue processing.

== Developer Notes ==

### Hooks and Filters

The plugin provides several hooks for developers:

**Actions:**
* `queue_optimizer_process_job` - Fired when processing individual jobs
* `queue_optimizer_before_batch` - Fired before processing a batch
* `queue_optimizer_after_batch` - Fired after processing a batch

**Filters:**
* `queue_optimizer_time_limit` - Modify the processing time limit
* `queue_optimizer_batch_size` - Modify the batch size
* `queue_optimizer_log_message` - Modify log messages before writing

### Code Structure

* `365i-queue-optimizer.php` - Main plugin file and bootstrap
* `admin/class-settings-page.php` - Admin interface and settings
* `includes/class-scheduler.php` - Core scheduling and processing logic
* `includes/uninstall.php` - Cleanup procedures
* `assets/admin.css` - Admin interface styling
* `assets/admin.js` - Admin AJAX functionality

### Contributing

This plugin follows WordPress Coding Standards and PSR-12 PHP standards. All contributions should include proper documentation and security measures.

== Support ==

For support, documentation, and hosting solutions, visit [365i WordPress Hosting](https://www.365i.co.uk/).

Professional WordPress hosting optimized for performance, security, and reliability.