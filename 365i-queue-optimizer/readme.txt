=== 365i Queue Optimizer ===
Contributors: 365i
Tags: actionscheduler, queue, optimization, performance, background-tasks
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight WordPress plugin to optimize ActionScheduler queue processing for faster image optimization and background tasks.

== Description ==

**365i Queue Optimizer** is an ultra-lightweight WordPress plugin designed to optimize ActionScheduler performance for faster image processing and background task execution. Perfect for sites using image optimization plugins, WooCommerce, or any plugin that relies on ActionScheduler.

### What This Plugin Does

This plugin applies three essential ActionScheduler optimizations:

* **Time Limit Control** - Sets how long ActionScheduler processes tasks (default: 60 seconds, configurable 10-300)
* **Concurrent Batch Processing** - Controls simultaneous background processes (default: 4 batches, configurable 1-10)
* **Image Processing Engine** - Prioritizes your chosen image processor (GD or ImageMagick)

### Key Features

* **Ultra-Lightweight** - Only 5 files, minimal server impact
* **Zero Overhead** - No complex dashboards, logging, or debugging features
* **Simple Configuration** - Clean settings page under Tools > Queue Optimizer
* **Instant Results** - Optimizations apply automatically after activation
* **WordPress Standards** - Follows all WordPress coding and security standards
* **Performance Focused** - Based on proven optimization techniques

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

### Configuration

1. Go to **Tools > Queue Optimizer** in WordPress admin
2. Configure your preferred settings:
   - **Time Limit**: How long ActionScheduler runs per batch (10-300 seconds)
   - **Concurrent Batches**: Number of simultaneous processes (1-10 batches)
   - **Image Engine**: Choose GD or ImageMagick for image processing
3. Click **Save Settings**
4. Optimizations apply automatically - no further setup required

== Usage ==

### Settings Configuration

**Time Limit (10-300 seconds, default: 60)**
Controls how long ActionScheduler processes tasks in each batch. Higher values process more tasks per run but may impact server performance. Lower values are safer for shared hosting.

**Concurrent Batches (1-10 batches, default: 4)**
Determines how many background processes run simultaneously. More batches can speed up processing but increase server load. Start with 4 and adjust based on your server capacity.

**Image Processing Engine (GD or ImageMagick, default: GD)**
Prioritizes your chosen image processing library. GD is more universally compatible, while ImageMagick often provides better quality and performance for image operations.

### Current Status

The settings page shows your current optimization status, including:
* Whether optimizations are active
* Current ActionScheduler processing time limit
* Number of concurrent batches configured
* Selected image processing engine

== Frequently Asked Questions ==

= How does this plugin improve performance? =

It optimizes three key ActionScheduler settings that control how WordPress processes background tasks. This results in faster image optimization, smoother WooCommerce operations, and better handling of any background processing.

= Will this affect my website's front-end performance? =

No. This plugin only affects background task processing and has zero impact on your website's front-end performance. It actually improves overall site responsiveness by making background tasks more efficient.

= Is this compatible with my existing plugins? =

Yes. This plugin works with any plugin that uses ActionScheduler, including Elementor, WooCommerce, image optimization plugins, and backup plugins. It enhances their performance rather than conflicting with them.

= Do I need to configure anything after installation? =

The plugin works with sensible defaults immediately after activation. However, you can fine-tune the settings under Tools > Queue Optimizer to optimize for your specific server and needs.

= What happens if I deactivate the plugin? =

All optimizations are immediately removed and ActionScheduler returns to its default behavior. Your site will continue working normally, just without the performance enhancements.

= Can I use this on shared hosting? =

Yes. The default settings are conservative and safe for shared hosting environments. You can even reduce the time limit and concurrent batches if needed.

= How do I know if it's working? =

After activation, image processing and background tasks should be noticeably faster. You can also check the Current Status section in the plugin settings to confirm optimizations are active.

== Screenshots ==

1. **Settings Page** - Simple configuration interface under Tools > Queue Optimizer
2. **Current Status** - Shows your optimization settings and ActionScheduler status

== Changelog ==

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

= 1.0.0 =
Initial release of 365i Queue Optimizer. A lightweight solution for optimizing ActionScheduler performance with minimal overhead.

== Support ==

For support and WordPress hosting solutions optimized for performance, visit [365i WordPress Hosting](https://www.365i.co.uk/).

Professional WordPress hosting with ActionScheduler optimization built-in.