# Changelog

All notable changes to the 365i Queue Optimizer plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Email Notifications feature placeholder in Settings Overview (shows as "Disabled")

### Technical Notes
- Email Notifications feature is UI placeholder only (Phase 2 pending)

## [1.7.7] - 2025-06-15

### Removed
- **Activity Log Page**: Removed redundant Activity Log interface in favor of native ActionScheduler
- **Activity Log Files**: Cleaned up unused Activity Log related templates and assets
- **Duplicate Queue Monitoring**: Eliminated redundant ActionScheduler data display

### Changed
- **Queue Activity Menu**: "Activity Log" menu item renamed to "Queue Activity" and now redirects to `Tools > Scheduled Actions`
- **User Experience**: Users now access comprehensive queue monitoring through native WordPress ActionScheduler interface

### Technical
- **Menu Structure Optimization**: Streamlined admin menu by removing duplicate interfaces
- **Code Cleanup**: Removed Activity Log dependencies from main plugin file and admin menu
- **ActionScheduler Integration**: Direct users to superior native queue monitoring interface

### Files Removed
- Activity Log page logic and template dependencies from plugin initialization

### Files Modified
- `365i-queue-optimizer.php` - Removed Activity Log file includes and asset enqueuing
- `src/Admin_Menu.php` - Replaced Activity Log menu with Queue Activity redirect to ActionScheduler

## [1.7.6] - 2025-06-15

### Added
- **Debug Mode Implementation (Phase 1 Complete)** - Full debug logging system with:
  - WordPress option registration for `queue_optimizer_debug_mode`
  - Settings field template with proper checkbox and descriptions
  - Comprehensive `Debug_Manager` class with verbose logging, performance monitoring
  - Action Scheduler integration for queue operation monitoring
  - JSON-lines logging format with automatic log rotation
  - Settings Overview panel now correctly shows "Debug Mode: Enabled/Disabled"

### Technical Notes
- Debug Mode now fully functional with backend WordPress option integration

### Files Added
- `templates/settings/debug-mode-field.php` - Debug mode settings field template
- `src/Debug_Manager.php` - Comprehensive debug logging system (278 lines)

### Fixed
- **Status Badge Colors**: Debug Mode and Email Notifications now use consistent green for "Enabled" and grey for "Disabled" (previously Debug Mode used confusing orange/green colors)
- **Double Notifications**: Removed duplicate "Settings saved" messages that appeared when saving settings
- **Last Run Timestamp**: Fixed raw timestamp display (e.g., "1749990099") in System Status panel - now shows formatted date/time (e.g., "June 15, 2025 12:34:56 PM")
- **Activity Log Timestamps**: Fixed "2027 years ago" timestamp formatting issue - now shows "Unknown" for invalid timestamps with proper error handling
- **Activity Log Time Calculation**: Fixed multiple entries showing "1 second ago" - now displays accurate relative time differences
- **Activity Log Cancel Actions**: Fixed "Action not found or cannot be canceled" errors with enhanced validation and database fallback method
- **Activity Log Expand Functionality**: Enhanced expand button to show detailed information including Action ID, scheduled time, arguments, and timestamp validation warnings
- **Activity Log Time Display**: Improved "Time Ago" column formatting - changed "Unknown ago" to "Invalid timestamp" and removed redundant "ago" text
- **Activity Log Bulk Delete**: Added bulk delete functionality for completed, failed, and cancelled actions with proper validation and confirmation dialogs

### Files Modified
- `admin/class-settings-page.php` - Added debug mode option registration and field
- `365i-queue-optimizer.php` - Added Debug Manager inclusion and initialization
- `templates/dashboard/settings-overview.php` - Fixed status badge color consistency
- `templates/settings-page.php` - Removed duplicate settings error display
- `src/Dashboard_Page.php` - Added proper timestamp formatting for last run display
## [1.7.5] - 2025-06-15

### Documentation
- **Missing Features Analysis**: Added comprehensive documentation to [`readme.txt`](readme.txt:35-56) explaining **Debug Mode** and **Email Notifications** placeholder status
- Documented that both features appear in Settings Overview dashboard but are not yet functionally implemented
- Added detailed feature specifications explaining what Debug Mode would provide (verbose logging, timing analysis, error reporting, troubleshooting tools)
- Added comprehensive Email Notifications feature description (failure alerts, status reports, threshold notifications, administrative alerts)
- Clarified that these features currently always display as "Disabled" until proper backend implementation is completed

### Technical Analysis
- Confirmed [`templates/dashboard/settings-overview.php`](templates/dashboard/settings-overview.php:64-94) contains hardcoded UI elements checking for non-existent option values
- Debug Mode checks for `$plugin_settings['debug_mode']` which defaults to 'no' and has no corresponding WordPress option registration
- Email Notifications checks for `$plugin_settings['email_notifications']` which defaults to 'no' and has no corresponding WordPress option registration
- Only 5 actual settings are registered in [`admin/class-settings-page.php`](admin/class-settings-page.php:123-221): Time Limit, Concurrent Batches, Logging, Retention Days, Image Engine

### Development Roadmap
- Features serve as UI placeholders for planned functionality expansion
- Implementation would require WordPress option registration, settings field templates, backend logic, and dashboard integration
- Clear development path identified for implementing these valuable debugging and monitoring capabilities

## [1.7.4] - 2025-06-15

### Fixed
- **CRITICAL**: Fixed PHP Fatal error in Settings page (`templates/settings-page.php:52`) where `date()` function was receiving string timestamp instead of integer
- **CRITICAL**: Fixed PHP Fatal error in System Info page (`src/System_Info_Page.php:475`) with same timestamp type issue
- Added robust timestamp validation with `strtotime()` conversion and fallback logic for mixed data types
- Improved error handling for WordPress option values that can be stored as strings or integers

### Improved
- Enhanced timestamp handling across all plugin interfaces
- Better defensive programming practices for date formatting
- More reliable queue status display and system information rendering

### Tested
- Comprehensive browser testing of all plugin pages and functionality
- Dashboard page: Queue statistics, system status, recent activity, quick actions
- Settings page: Configuration forms, action buttons, timestamp display
- System Info page: Server environment, WordPress config, PHP extensions, export functionality
- Activity Log page: Log statistics, management controls, activity tracking
- All navigation and menu functionality confirmed working

### Technical Details
- Implemented type checking for timestamp values before passing to `date()` function
- Added `strtotime()` conversion for string timestamps with validation
- Fallback to `time()` for invalid timestamp values
- Applied fixes consistently across both affected files