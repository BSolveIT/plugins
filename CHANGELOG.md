# 365i Queue Optimizer Changelog

All notable changes to this project will be documented in this file.

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