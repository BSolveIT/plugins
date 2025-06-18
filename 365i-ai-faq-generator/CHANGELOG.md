# Changelog

All notable changes to the 365i AI FAQ Generator plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.3] - 2025-06-18

### Fixed
- **CRITICAL: Worker Configuration Save Fix:** Resolved "Save Configuration" button functionality on Rate Limiting page
  - Fixed missing JavaScript event handlers for worker configuration forms causing blank page errors
  - Added `initRateLimitingConfig()` function to properly initialize rate limiting configuration interface
  - Implemented `handleWorkerConfigSave()` method for AJAX worker configuration submissions
  - Added `handleGlobalSettingsSave()` method for global settings form processing
  - Fixed field name mismatch between template and PHP handler (hourlyLimit vs requests_per_hour, etc.)
  - Updated `handle_rate_limit_update()` method to accept template field names (hourlyLimit, dailyLimit, weeklyLimit, monthlyLimit)
  - Added support for violation thresholds configuration (soft, hard, ban levels)
  - Updated `get_default_worker_config()` to match template field structure and expectations
  - Fixed badge persistence issue: configurations now properly marked as 'custom' in KV storage
  - Added `source: 'custom'` field to saved configurations ensuring badge shows "CUSTOM" after page reload
  - Fixed "Reset to Defaults" functionality to properly delete custom configurations from KV storage
  - Added `handle_worker_config_reset()` AJAX handler and `reset_worker_config_in_kv()` method
  - Updated `handleWorkerConfigReset()` JavaScript to call backend API instead of just resetting form fields
  - "Reset to Defaults" now properly changes badge from "CUSTOM" back to "DEFAULT" and removes timestamps
  - Worker configuration forms now save successfully with proper visual feedback and status updates
  - "Save Configuration" buttons now change worker status from "DEFAULT" to "CUSTOM" upon successful save and persist after reload
- **"Demo Data" Display Fix:** Resolved misleading "Demo data" display in Usage Analytics when Cloudflare KV is connected but empty
  - Fixed analytics template to properly handle `kv_empty` data source status
  - Added proper distinction between actual demo data and connected KV with no analytics data yet
  - Enhanced status messaging to clearly indicate KV connection status vs. data availability
- **Bullet Point Styling Fix:** Removed unsightly bullet points from Analytics Overview cards
  - Replaced `● KV connected (no data yet)` style indicators with professional status badges
  - Applied consistent badge styling across both Analytics Overview and Diagnostics sections
  - Improved visual consistency and professional appearance

### Added
- **KV Connection Diagnostics:** Added comprehensive diagnostic section to Usage Analytics page
  - Real-time API credentials validation (Account ID and API Token status)
  - Live KV connection testing with detailed error reporting
  - Clear data source indicators showing connection health and data origin
  - Enhanced troubleshooting information for Cloudflare KV connectivity issues
- **Enhanced Status Indicators:** Improved analytics cards with clearer data source labeling
  - "KV connected (no data yet)" for empty but connected KV storage
  - "Live from KV" for active data streams
  - "Fallback data" for credential issues
  - "Demo data" only for actual demonstration mode

### Improved
- Better user understanding of KV connection status and data availability
- Enhanced error messaging to help users distinguish between connection issues and empty data
- More informative status displays throughout the Usage Analytics interface
- **Professional Styling Upgrade:** Completely redesigned KV diagnostics section with modern card-based layout
  - Removed bullet lists in favor of elegant status badges and cards
  - Added gradient backgrounds, hover effects, and color-coded status indicators
  - Implemented responsive design with proper mobile breakpoints
  - Enhanced typography and spacing for better readability
  - Added visual status badges (Connected, Disconnected, Pending, Ready) for instant recognition
- **Enhanced Analytics Card Styling:** Completely modernized analytics overview cards with professional design elements
  - Implemented gradient backgrounds (white to light gray) with subtle transitions
  - Added colored top accent bars that dynamically change based on metric type (blue for normal, red for blocked, orange for violations)
  - Enhanced hover animations with lift effect and increased shadow depth
  - Improved typography with larger metrics (36px), text shadows, and better visual hierarchy
  - Added elegant timestamp styling with clock emoji icons and blue gradient backgrounds
  - Implemented smooth transitions and modern border radius for professional appearance
- **Cleaner Interface:** Removed unnecessary notification banners from Usage Analytics page
  - Removed status notifications (Connected to KV, Live Data, Fallback Mode messages)
  - Cleaner, more focused interface without redundant status messaging
  - Data source information still available in KV Diagnostics section
- **Fixed KV Connection Error:** Resolved "limit argument must be at least 10" API error
  - Fixed KV connection test to use minimum required limit parameter (changed from limit=1 to limit=10)
  - Eliminates spurious connection failure notifications in KV diagnostics
  - Connection testing now properly validates API connectivity without parameter errors
- **Fixed IP Management Actions:** Resolved "Invalid action specified" error when adding IPs to whitelist/blacklist
  - Fixed JavaScript action name mismatch: `'add_whitelist'` → `'add_to_whitelist'` and `'add_blacklist'` → `'add_to_blacklist'`
  - Updated all button handlers and form clearing logic to use correct action names
  - IP management now works properly for adding/removing IPs from both whitelist and blacklist
- **Fixed IP Metadata Display:** Resolved "Unknown" values for added_by and date_added fields in IP management
  - Enhanced `fetch_ip_list_from_kv()` method to retrieve complete IP metadata from KV storage
  - Added `fetch_ip_metadata_from_kv()` method for individual IP metadata retrieval
  - Implemented `get_user_display_name()` method for proper username resolution
  - IP lists now display actual user names and timestamps instead of "Unknown" values
- **Enhanced AJAX Experience:** Eliminated full page refreshes in favor of smooth dynamic updates
  - Replaced `location.reload()` with proper AJAX-based IP list updates
  - Added `updateIPLists()`, `addIPToList()`, and `removeIPFromList()` JavaScript functions
  - Implemented dynamic DOM manipulation with fade-in/fade-out animations
  - Added current user information to script localization for proper "Added By" display
  - IP management operations now update lists instantly without page reload
- **Fixed Remove IP Functionality:** Resolved action name mismatch and DOM targeting issues causing "Remove" button failures
  - Fixed JavaScript action construction: `remove_whitelist` → `remove_from_whitelist`
  - Added proper IDs (`whitelist-list`, `blacklist-list`) to template containers for DOM targeting
  - Added `data-ip` attributes to existing table rows for proper element selection
  - Fixed empty state handling to match actual HTML structure
  - Corrected data attribute usage in dynamically created remove buttons
  - Added debugging console logs to track DOM operations
  - Remove IP operations now work properly for both whitelist and blacklist entries
- **Improved Table Styling:** Enhanced visual presentation with proper vertical alignment
  - Added `vertical-align: middle` to all table headers and cells
  - Table row content is now properly centered vertically for better readability
  - Consistent styling across IP management and analytics tables
- **Disabled Notification System:** Completely disabled all JavaScript notifications to prevent duplication issues
  - No more notification messages displayed from JavaScript functions
  - Resolves duplicate notification conflicts between JavaScript and WordPress admin notices
  - Clean interface without notification interference
- **Dynamic Status Counter Updates:** Enhanced IP management with real-time status box updates
  - Added `updateStatusCounters()` function to dynamically update whitelist and blacklist counts
  - Status boxes now automatically reflect changes when adding or removing IPs
  - Fixed timing issue where removal counter updates occurred before DOM element removal
  - Counter updates now properly wait for fade-out animations to complete before recounting
  - Improved user experience with instant visual feedback on IP list modifications
  - Eliminates need for page refresh to see updated counts

## [2.1.2] - 2025-06-18

### Fixed
- **CRITICAL FIX:** Resolved 405 HTTP errors in rate limiting configuration interface:
  - Fixed Cloudflare KV API requests to use actual namespace IDs instead of namespace names
  - Updated all KV namespace references to use proper 32-character namespace IDs:
    - FAQ_RATE_LIMITS: 77fcd59503e34efcaf4d77d1a550433b
    - FAQ_CACHE: 8a2d095ab02947408cbf81e70a3e7f8a
    - FAQ_IP_WHITELIST: 98e217d3ffdf439f9080f29b9868dce0
    - FAQ_IP_BLACKLIST: ea349175a0dd4a01923c9da59e794b9b
    - FAQ_VIOLATIONS: 99d05632fa564f95bd47f22891f943aa
    - FAQ_ANALYTICS: a3573648cc1d4c1990a06090dab3e646
- **SETTINGS SAVE FIX:** Fixed global settings not saving properly:
  - Integrated AI_FAQ_Rate_Limiting_Admin into main admin initialization system
  - Added proper AJAX handler registration and settings registration
  - Fixed save_global_settings_to_kv() method to properly store settings in Cloudflare KV
  - Added validation and error handling for global settings saves
  - Global rate limiting settings now save successfully with proper feedback

### Added
- **AJAX Global Settings**: Implemented AJAX saving for global rate limiting settings:
  - No more full page refresh when saving global settings
  - Beautiful visual feedback with success/error messages using WordPress notice styling
  - Real-time status updates in the overview section when settings change
  - Enhanced user experience with loading states and button state changes
  - Client-side email validation for notification settings
- **Enhanced Global Settings Form**: Added missing global settings options:
  - IP whitelist enable/disable toggle
  - IP blacklist enable/disable toggle
  - Notification preferences for rate limit violations
  - Improved form structure with proper nonces and AJAX handling
- **Missing Admin Templates**: Created missing admin template files:
  - Created IP Management admin template (templates/admin/ip-management.php)
  - Created Usage Analytics admin template (templates/admin/usage-analytics.php)
  - Added comprehensive IP whitelist/blacklist management interface
  - Added detailed usage analytics with worker breakdown and violation tracking
  - Resolved PHP warnings caused by missing admin template files
- **Admin Template Styling**: Created comprehensive CSS styling system:
  - Created admin-templates.css for improved spacing and visual layout
  - Added proper margin/padding for form sections and cards
  - Implemented responsive grid layouts for IP lists and analytics
  - Enhanced visual hierarchy with consistent typography and borders
  - Improved mobile responsiveness with proper breakpoints
  - Integrated CSS enqueuing into rate limiting admin system
- **UI Improvements**: Enhanced admin interface usability:
  - Removed data retention policy notification from Usage Analytics page
  - Cleaner interface without unnecessary informational notices
- **JavaScript Functionality**: Created comprehensive admin interaction system:
  - Created rate-limiting-admin.js with full button functionality
  - Added AJAX handlers for IP management (Add to Whitelist/Blacklist, Remove IP)
  - Implemented analytics refresh and export functionality
  - Added real-time IP address validation with visual feedback
  - Integrated user notification system with auto-dismiss features
  - Added loading states and error handling for all interactions
  - Connected all admin buttons to proper backend AJAX endpoints
- **Backend Integration**: Completed button functionality with working backend:
  - Fixed corrupted PHP class structure in rate limiting admin
  - Added missing analytics export AJAX handler with CSV generation
  - Implemented demo data for analytics display and testing
  - Created proper method structure for all admin operations
  - All buttons now properly connected to functional backend endpoints
- **AJAX Security Fix**: Resolved console error and security check failures:
  - Fixed automatic AJAX calls triggering on page load causing security errors
  - Updated nonce verification to use wp_verify_nonce for improved reliability
  - Changed analytics filter behavior to require manual refresh instead of automatic calls
  - Removed debugging code and cleaned up AJAX handlers
  - Eliminated "Security check failed" console errors
  - **CRITICAL FINAL FIX**: Resolved duplicate AJAX action name conflicts:
    - Identified root cause: duplicate AJAX action names between rate limiting system and main plugin
    - Main plugin's AI_FAQ_Admin_Ajax already registered `ai_faq_get_analytics` with different nonce expectations
    - Implemented unique action naming convention using `ai_faq_rl_` prefix for all rate limiting AJAX handlers
    - Updated all JavaScript AJAX calls to use new unique action names
    - Re-enabled full JavaScript functionality after resolving conflicts
    - All admin interface interactions now work without console security errors
- **LIVE CLOUDFLARE KV INTEGRATION**: Implemented real-time data integration:
  - Replaced demo/staging data with live Cloudflare KV API calls for all rate limiting functionality
  - Added comprehensive analytics data fetching from KV storage with 5-minute caching
  - Implemented live IP whitelist/blacklist management with real KV operations (add/remove IPs)
  - Added global settings and worker configuration saving/loading from KV storage
  - Created robust error handling with graceful fallback to default data when API unavailable
  - Added data source indicators in admin interface (Live from KV, Fallback data, Demo data)
  - Implemented proper input validation and sanitization for all KV operations
  - Added comprehensive caching system to reduce API calls and improve performance
  - Enhanced admin templates to show real-time connection status and data freshness
  - All rate limiting data now syncs bidirectionally between WordPress admin and Cloudflare Workers

### Improved
- **User Experience**: Global settings now save instantly with professional visual feedback
- **Form Validation**: Added client-side email validation and real-time error messaging
- **Visual Design**: Enhanced global settings messages with WordPress notice styling and smooth animations
- **Loading States**: Added professional loading overlays and disabled button states during AJAX operations
- **Status Updates**: Real-time updates to status overview cards when settings are saved

### Removed
- **Removed Geographic Restrictions feature** (as requested):
  - Removed geographic restrictions section from rate limiting configuration template
  - Removed geographic restrictions settings from PHP validation
  - Removed geographic restrictions JavaScript handlers
  - Cleaned up default settings to remove geographic-related options
  - Removed geographic restrictions from worker rate limiter implementation
  - Removed `checkGeographicRestrictions()` method from rate limiter
  - Cleaned up dynamic configuration to remove geo-restriction settings
  - Interface is now cleaner and focused on core rate limiting functionality
  - Fixed get_worker_config() method to use correct KV namespace ID in API calls
  - Fixed update_worker_rate_config() method to use correct KV namespace ID in PUT requests
  - Fixed get_global_settings() method to use correct KV namespace ID
  - Fixed get_ip_list() method to use correct namespace IDs for whitelist/blacklist operations
  - Fixed manage_ip_address() method to properly handle IP whitelist/blacklist management
  - Fixed get_analytics_data() method to use correct analytics namespace ID
  - Enhanced IP management with full CRUD operations for whitelist/blacklist functionality
  - Added proper error handling and response validation for all KV operations
  - Rate limiting configuration interface now fully functional without HTTP errors

## [2.1.1] - 2025-06-18

### Improved
- Removed debug logging statements from JavaScript and PHP files for cleaner production code:
  - Removed console.log statements from admin.js in testWorkerConnection, saveSettings, and saveWorkerConfig functions
  - Removed console.warn statement in refreshWorkerStatus function
  - Removed error_log statements from class-ai-faq-admin-ajax.php in ajax_test_worker, ajax_reset_worker_usage, ajax_save_settings, and ajax_reset_settings methods
  - Removed error_log statement from reload_worker_configuration method
  - Code now maintains all functionality without unnecessary debugging output

## [2.1.0] - 2025-06-18

### Added
- New Admin Security component for better handling of IP blocking and rate limit violations
- Added documentation for specialized admin components
- New specialized Worker components for improved request handling, security, and analytics

### Changed
- Major architectural refactoring of admin interface to implement Single Responsibility Principle
- Refactored monolithic admin class (1,463 lines) into specialized components:
  - AI_FAQ_Admin: Facade pattern coordinator (148 lines)
  - AI_FAQ_Admin_Menu: Menu registration and page rendering (152 lines)
  - AI_FAQ_Admin_Settings: Settings registration and sanitization (350 lines)
  - AI_FAQ_Admin_Ajax: AJAX request processing (461 lines)
  - AI_FAQ_Admin_Workers: Worker testing and health checks (246 lines)
  - AI_FAQ_Admin_Analytics: Analytics data processing (271 lines)
  - AI_FAQ_Admin_Security: IP blocking and violations management (265 lines)
- Refactored monolithic Workers class (887 lines) into specialized components:
  - AI_FAQ_Workers: Facade pattern coordinator (418 lines)
  - AI_FAQ_Workers_Manager: Worker coordination and API (354 lines)
  - AI_FAQ_Workers_Rate_Limiter: Rate limiting and caching (285 lines)
  - AI_FAQ_Workers_Security: IP detection and blocking (444 lines)
  - AI_FAQ_Workers_Analytics: Usage tracking and reporting (278 lines)
  - AI_FAQ_Workers_Request_Handler: AJAX request processing (538 lines)

### Improved
- Code maintainability through proper separation of concerns
- Reduced complexity of individual components
- Better organization for future extensibility
- Enhanced security with dedicated security components
- Improved performance through better caching and rate limiting
- Fixed PowerShell test data generation script with proper error handling and best practices

### Fixed
- **CRITICAL FIX:** Fixed missing form submission handler for worker configuration form:
  - Added missing JavaScript event handler for the workers configuration form
  - Implemented saveWorkerConfig method to process the form via AJAX
  - The "Save Worker Configuration" button now properly saves worker settings
  - This resolves an issue where changing worker URLs had no effect despite UI showing success
- **SECURITY FIX:** Fixed nonce verification in worker configuration form:
  - Updated AJAX handler to support both WordPress generated nonces (`_wpnonce`) and custom nonces
  - Enhanced form serialization to correctly include WordPress standard nonce fields
  - Added additional debug logging to help diagnose security verification issues
  - Fixed "Security check failed" error when saving worker configuration
- Resolved critical dependency issues in Workers system causing WordPress fatal errors
- Fixed constructor parameter mismatch in AI_FAQ_Workers_Rate_Limiter initialization
- Corrected component initialization order to prevent double initialization
- Added proper dependency injection between Workers facade and Manager classes
- Implemented robust error handling in Workers facade to prevent fatal errors during component initialization
- Added default worker configuration fallback to prevent errors when no configuration exists
- Added component null checks to prevent double initialization
- Enhanced test scripts to verify component initialization and error handling
- Created syntax checking tool to verify PHP file integrity
- Fixed fatal class name collision between duplicate AI_FAQ_Admin classes
- Resolved circular reference in admin bootstrapping system
- Fixed worker URL handling to properly separate base URLs and endpoint paths
- Standardized worker endpoint construction for improved maintainability
- Fixed test connection functionality to use consistent health endpoint paths
- Fixed "Test Connection" functionality to use the URL from the form field rather than the saved configuration
- Improved test connection error handling with better error logging
- Implemented comprehensive multi-strategy worker testing system (OPTIONS, GET, POST)
- Added customized test payloads for each worker type to improve connection success rates
- Added extensive debug logging to identify exact request URLs and response codes
- Redesigned connection testing to be more resilient to different API implementations
- Fixed syntax error in class-ai-faq-admin-workers.php that was causing fatal errors
- Completely redesigned worker connection testing system with robust multi-strategy approach:
  - Improved health endpoint detection and URL construction
  - Enhanced error handling and reporting for detailed diagnostics
  - Added support for multiple response formats across different worker types
  - Normalized worker names for consistent handling (both hyphenated and underscore formats)
  - Fixed worker payload structure to use correct mode values (generate, enhance, analyze, extract)
  - Improved URL sanitization and validation in AJAX handlers
  - Added comprehensive logging throughout testing process
  - Implemented more resilient connection strategies with graceful fallbacks
  - Added documentation about favicon.ico 404 errors in worker testing to prevent confusion
  - Enhanced JavaScript with explanatory notes about browser automatic favicon requests
  - Added user-facing notifications about expected favicon.ico 404 errors in test results
  - Added comprehensive debugging output to JavaScript and PHP code:
    - Added detailed console.log messages for worker test requests and responses
    - Added enhanced error logging for connection failures
    - Improved server-side logging with complete request and URL information
    - Added normalized worker name logging for better cross-referencing
  - Fixed critical issue with worker health endpoint testing:
    - Implemented direct GET request to /health endpoint in AJAX handler
    - Ensured clean GET requests with no parameters or payload
    - Added explicit method type to prevent HTTP method confusion
    - Added forced URL concatenation to guarantee /health endpoint is used
    - Enhanced logging to track exact health endpoint URL and request details
    - Fixed request prioritization to properly use health endpoint before fallbacks
    - Added detailed status logging for health endpoint tests
    - Bypassed complex testing flow when direct health check succeeds
|       - Fixed client-side worker URL handling to explicitly force health endpoint usage:
|         - Modified admin.js to automatically append /health to any worker URL
|         - Added URL normalization to remove trailing slashes before appending /health
|         - Added detailed console logging for better debugging of health endpoint URLs
|         - Ensured consistent health endpoint URL construction across all worker types
|       - Improved server-side health endpoint testing:
|         - Completely changed approach to use POST requests with JSON payloads instead of GET requests
|         - Implemented direct POST request to /health endpoint with minimal valid JSON payload
|         - Added proper Content-Type headers for JSON requests (application/json)
|         - Updated worker-test-results.js to inform users about POST request requirements
|         - Added support for self-signed certificates in testing environments
|         - Improved error handling with clear status logging
|         - Fixed URL normalization for consistent health endpoint paths
|         - Simplified client-side URL manipulation to prevent JavaScript errors
|         - Added User-Agent header to identify health check requests in worker logs
- Fixed critical issue with HTTP method requirements for worker health checks:
  - Added specific error messages for 405 Method Not Allowed errors
  - Enhanced UI feedback for users when workers reject incorrect request methods
  - Updated ajax handler to provide detailed error information for different HTTP status codes
  - Added specific guidance for different error types (405, 400, 404) in the UI
  - Improved client-side error handling to display clear explanations about endpoint requirements
  - Enhanced debug logging to clearly identify method-related failures
- Completely overhauled worker connectivity testing approach:
  - Switched from unreliable GET requests to /health endpoint to robust POST with test data
  - Implemented approach proven successful in individual worker classes (SEO_Analyzer, etc.)
  - Updated server-side AJAX handler to use WordPress's native wp_remote_request
  - Replaced custom cURL implementation with standard WordPress HTTP API
  - Added proper test data payload to validate full worker functionality
  - Updated PHPDoc blocks to document correct worker request expectations
  - Modified client-side JavaScript to accurately describe worker requirements
  - Added detailed error handling and comprehensive test data
  - Improved fallback strategy when primary test method fails
- **LATEST FIX:** Corrected worker health checking to use GET requests to /health endpoint:
  - Examined actual worker code to verify correct implementation
  - Workers implement a standardized /health endpoint that responds to GET requests
  - Updated PHP AJAX handler to use GET method for health checks
  - Fixed health URL construction to properly append /health to worker base URL
  - Updated JavaScript documentation to reflect correct approach
  - Enhanced error messages to be specific about health endpoint requirements
  - Clarified that workers have two endpoints:
    - /health for GET requests (connectivity checks)
    - Main endpoint for POST requests (FAQ processing)
- **CRITICAL FIX:** Fixed double /health path appending issue:
  - Removed redundant /health appending in test_get_request method
  - URLs were being constructed as /health/health causing 404 errors
  - Fixed in class-ai-faq-admin-workers.php line 287
- **CRITICAL FIX:** Resolved duplicate AJAX handler registration conflict:
  - Found duplicate registrations for ai_faq_test_worker action
  - Removed duplicate from class-ai-faq-workers-request-handler.php
  - This ensures the correct handler (in admin-ajax) is always executed
- **SECURITY FIX:** Fixed critical nonce validation vulnerability:
  - All AJAX handlers were using unsanitized $_POST['nonce'] values directly
  - Added proper sanitization using sanitize_text_field() before wp_verify_nonce()
  - Applied fix to all 11 AJAX handler methods in class-ai-faq-admin-ajax.php
  - This prevents potential security exploits through malformed nonce values
- **CRITICAL FIX:** Fixed AJAX handler registration for admin-ajax.php requests:
  - Admin component was only initialized when is_admin() returned true
  - Added wp_doing_ajax() check to ensure AJAX handlers are registered for AJAX requests
  - This fixes 400 Bad Request errors when making AJAX calls to test worker connections
- **CRITICAL FIX:** Updated AJAX check to use DOING_AJAX constant instead of wp_doing_ajax():
  - wp_doing_ajax() function may not be available during early plugin initialization
  - Using defined('DOING_AJAX') && DOING_AJAX ensures proper detection of AJAX requests
  - This ensures admin handlers are registered when WordPress processes admin-ajax.php
- **ROOT CAUSE IDENTIFIED:** Worker URL configuration mismatch:
  - The 400 Bad Request errors are caused by incorrect worker URL configuration
  - question_generator worker is configured with answer_generator's URL
  - Each worker must be configured with its own correct URL in the Workers settings page
  - This is a configuration issue, not a code issue - users need to update their worker URLs
- **URL SANITIZATION FIX:** Fixed aggressive URL sanitization breaking valid Cloudflare Worker URLs:
  - Replaced FILTER_SANITIZE_URL with minimal URL processing (just trim whitespace)
  - FILTER_SANITIZE_URL was removing valid characters from Cloudflare Worker URLs
  - Added proper URL validation using FILTER_VALIDATE_URL instead
  - Enhanced logging to capture original and processed URLs for debugging
  - Fixed worker name handling in fallback method to use original name instead of normalized
  - This resolves 400 Bad Request errors caused by malformed URLs after sanitization
- **RESET DEFAULTS FIX:** Fixed "Reset to Defaults" button to correctly set unique URLs for each worker:
  - Added missing reset_settings() and import_settings() methods to AI_FAQ_Admin_Settings class
  - Each worker now gets its own unique URL when resetting to defaults:
    - question_generator: https://faq-realtime-assistant-worker.winter-cake-bf57.workers.dev
    - answer_generator: https://faq-answer-generator-worker.winter-cake-bf57.workers.dev
    - topic_generator: https://faq-topic-generator-worker.winter-cake-bf57.workers.dev
    - faq_extractor: https://url-to-faq-generator-worker.winter-cake-bf57.workers.dev
    - faq_enhancer: https://faq-enhancement-worker.winter-cake-bf57.workers.dev
    - seo_analyzer: https://faq-seo-analyzer-worker.winter-cake-bf57.workers.dev
  - Added proper filter hook 'ai_faq_gen_default_settings' for customizing default values
  - Enhanced import functionality with proper data validation and sanitization
- Enhanced reset_settings AJAX handler with comprehensive error logging and validation to debug 400 Bad Request issues
- **CRITICAL FIX:** Fixed missing AJAX component initialization causing 400 Bad Request errors on all AJAX endpoints including reset settings
  - Fixed 400 Bad Request error by ensuring settings class is properly loaded in AJAX handlers
- **CRITICAL FIX:** Corrected incorrect worker URL configuration causing genuine 404 errors:
  - Fixed question_generator worker to use correct URL: https://faq-realtime-assistant-worker.winter-cake-bf57.workers.dev
  - The worker was misconfigured to use non-existent "faq-question-generator-worker" URL
  - Updated reset_settings() method to use correct worker URL in default configuration
  - This resolves the actual 404 errors (not favicon.ico requests) that were occurring during worker connectivity tests
  - All workers now properly configured with existing Cloudflare Worker URLs
- **CLARIFICATION:** Resolved confusion about 404 errors in worker connectivity tests:
  - The 404 errors observed in logs are from automatic browser favicon.ico requests - this is normal browser behavior
  - All workers (question_generator, answer_generator, topic_generator, faq_extractor, faq_enhancer, seo_analyzer) exist and function correctly
  - Worker connectivity tests show "Connection successful" with "Status: healthy" for all workers
  - Added user-facing documentation to explain that favicon.ico 404 errors in Cloudflare logs can be safely ignored
  - Worker health endpoints (/health) respond correctly to GET requests with proper status information
- **CRITICAL DATABASE CONFIGURATION FIX:** Fixed issue where manually changed worker URLs weren't used due to stale configuration cache:
  - Added `reload_worker_config()` methods to Workers facade and Manager classes
  - Implemented automatic configuration reloading after worker settings saves
  - Added proper cache clearing (`wp_cache_delete`) to ensure fresh configuration data
  - Fixed critical issue where saved worker URLs were ignored in favor of hardcoded defaults
  - Configuration changes now immediately reflected in active workers system without page refresh
  - Enhanced AJAX save handler to trigger configuration reload after successful database saves

### Added
- Improved error handling with graceful component degradation when parts of the system fail
- Added comprehensive test suite for verifying component functionality and error handling

## [2.0.2] - 2025-06-17

### Fixed
- Fixed issue where "Reset to Defaults" button wasn't properly restoring worker URLs to default values
- Fixed critical bug where saving Cloudflare credentials would cause worker URLs to be lost
- Improved error handling and logging in settings save operation

### Added
- Added worker testing tools in the tools/ directory:
  - test-workers.js: A comprehensive Node.js script to test all worker endpoints with realistic data
  - package.json: Configuration for easy running of test scripts
- Improved logging of test results to aid in debugging and performance analysis

## [2.0.1] - 2025-06-10

### Added
- Enhanced error handling for worker connections
- Added detailed error messages for debugging

### Fixed
- Fixed issue with rate limiting configuration
- Corrected worker URL format validation

## [2.0.0] - 2025-06-01

### Added
- Complete redesign of the plugin with frontend-focused FAQ generation
- New Cloudflare Workers architecture for scalable AI processing
- Minimalist admin interface for worker configuration
- Frontend FAQ generator with live preview
- Schema markup generation for SEO
- Import/export functionality for FAQ data
- Rate limiting and usage analytics