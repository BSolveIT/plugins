# Changelog

All notable changes to the 365i Queue Optimizer plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.7.5] - 2025-06-15

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