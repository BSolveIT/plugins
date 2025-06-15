# 365i Queue Optimizer Changelog

All notable changes to this project will be documented in this file.

## Version 1.8.0 - June 15, 2025
### Fixed
- Image Processing: Fixed critical issue with images getting stuck in "Optimizing..." state
- Queue Processing: Enhanced ActionScheduler integration to properly process image optimization tasks
- Concurrent Processing: Added new "Apply Concurrent Batches to Action Scheduler" setting (enabled by default)
- Performance: Improved concurrency handling with higher ActionScheduler hook priority (999)
- Compatibility: Ensured proper communication between WordPress media library and ActionScheduler

## Version 1.7.9 - June 15, 2025
### Fixed
- ActionScheduler Compatibility: Fixed critical issue where our plugin was blocking other plugins (particularly Elementor Image Optimizer) from processing their Action Scheduler tasks
- Debug Mode: Improved debug logging to ensure it consistently shows detailed logs when enabled
- Logging: Fixed inconsistent log display after clearing logs - now properly prioritizes debug logs when debug mode is enabled
- Queue Processing: Made concurrent batches filter optional with new setting to avoid conflicts with other plugins
- Performance: Optimized ActionScheduler hook priorities (set to 999) to ensure our plugin doesn't interfere with other plugins' tasks
- User Interface: Enhanced log output with more human-readable descriptions and meaningful context

## Version 1.7.8 - May 21, 2025
### Fixed
- Concurrent Batches: Fixed setting not affecting ActionScheduler's processing limits
- Added ActionScheduler filter hook for concurrent batches setting
- Implemented set_concurrent_batches() method to apply custom value
- Ensures queue_optimizer_concurrent_batches option properly controls queue processing

## Version 1.7.7 - May 15, 2025
### Changed
- Removed redundant Activity Log interface in favor of native ActionScheduler
- 'Queue Activity' menu now redirects to Tools > Scheduled Actions
- Eliminated duplicate functionality that ActionScheduler handles better

## Version 1.7.6 - April 28, 2025
### Added
- Debug Mode: Added comprehensive debug mode with detailed logging
- New Debug_Manager class for verbose logging & performance monitoring
- Action Scheduler integration for queue operation monitoring
- JSON-lines logging format with automatic log rotation

### Fixed
- Fixed Time Ago column formatting (removed 'Unknown ago')
- Enhanced timestamp validation and calculation logic
- Added bulk delete functionality for completed/failed/cancelled actions
- Fixed cancel action errors with enhanced validation
- Fixed status badge colors (consistent green/grey)
- Eliminated duplicate 'Settings saved' notifications
- Fixed raw timestamp display in System Status panel

## Version 1.7.5 - April 10, 2025
### Added
- Added support for bulk processing of image optimizations
- Enhanced user interface with progress indicators
- Implemented image processing statistics dashboard

### Fixed
- Resolved issue with queue processing for very large image libraries
- Fixed memory leak during extended optimization sessions

## Version 1.7.4 - March 15, 2025
### Added
- New system status panel for monitoring queue health
- Improved error handling and reporting
- Enhanced logging with rotation support

### Fixed
- Fixed issue with WebP conversion on certain server configurations
- Resolved compatibility issue with Elementor Pro

## Version 1.7.3 - February 22, 2025
### Added
- Support for PNG transparency optimization
- Advanced JPEG compression options
- Bulk reprocessing capabilities

### Fixed
- Queue stability improvements for high-volume sites
- Fixed rare race condition in background processing

## Version 1.7.2 - January 15, 2025
### Added
- WebP conversion support
- Integration with native WordPress image editing
- Enhanced metadata preservation

### Fixed
- Fixed issue with image quality degradation on resized images
- Improved compatibility with various CDN plugins

## Version 1.7.1 - December 10, 2024
### Added
- Support for SVG optimization
- Advanced scheduling options
- Custom hooks for third-party integration

### Fixed
- Fixed compatibility issue with WP 6.5
- Improved error handling for failed optimizations

## Version 1.7.0 - November 5, 2024
### Added
- Complete rewrite of the queue processing engine
- New settings page with advanced options
- Dashboard widget for optimization statistics
- Support for WooCommerce product galleries

### Changed
- Improved performance for bulk operations
- Enhanced security with proper capability checks
- Restructured plugin architecture for better maintainability

## Version 1.6.2 - October 12, 2024
### Fixed
- Compatibility fixes for PHP 8.2
- Resolved issues with concurrent processing
- Fixed memory leaks during large queue processing

## Version 1.6.1 - September 18, 2024
### Added
- Enhanced logging capabilities
- Support for multisite installations
- Performance optimizations for large media libraries

### Fixed
- Fixed issue with missing metadata after optimization
- Improved error recovery for interrupted processes

## Version 1.6.0 - August 25, 2024
### Added
- Initial public release
- Background optimization of images
- Queue management and monitoring
- Support for JPEG, PNG, and GIF formats
- Integration with WordPress media library