# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **WordPress plugin** designed for submission to the WordPress.org Plugin Repository. The plugin displays visual environment indicators (DEV/STAGING/LIVE) in the WordPress admin bar to prevent accidental modifications to production sites.

- **Plugin Name:** 365i Environment Indicator
- **Plugin Slug:** 365i-environment-indicator
- **Text Domain:** 365i-environment-indicator
- **Function Prefix:** `i365ei_`
- **Constant Prefix:** `I365EI_`
- **CSS Class Prefix:** `i365ei-`
- **Current Version:** 1.0.6
- **Requirements:** WordPress 6.0+, PHP 7.4+

## Core Architecture

### Modular Procedural Design

The plugin uses a **procedural architecture** with clear separation of concerns across four core modules:

1. **Detection Module** (`includes/detection.php`)
   - Environment detection with priority: `WP_ENVIRONMENT_TYPE` → `WP_ENV` → subdomain heuristics → default to LIVE
   - Main function: `i365ei_get_environment()` (globally cached)
   - Normalizes all environments to: DEV, STAGING, or LIVE

2. **Helpers Module** (`includes/helpers.php`)
   - Settings management with global caching
   - Multisite-aware: uses `get_site_option()` when network-activated
   - Main functions: `i365ei_get_settings()`, `i365ei_update_settings()`

3. **Admin Bar Module** (`includes/admin-bar.php`)
   - Adds environment label to WordPress admin bar
   - Handles all visual enhancements (background color, top border, footer watermark)
   - Only displays for logged-in users (zero front-end impact for visitors)

4. **Settings Module** (`includes/settings.php`)
   - WordPress Settings API implementation
   - Network admin support for multisite installations
   - Accessible via Settings → Environment Indicator

### File Structure

```
environment-indicator/
├── environment-indicator.php    # Main plugin file (entry point)
├── readme.txt                   # WordPress.org readme
├── specification.md             # Complete plugin specification
├── /includes/                   # Core functionality
│   ├── helpers.php             # Settings & utilities
│   ├── detection.php           # Environment detection logic
│   ├── admin-bar.php           # Admin bar & visual indicators
│   └── settings.php            # Settings page & UI
├── /assets/
│   └── admin.css               # Styling for indicators
└── /languages/                  # Translation files (uses WP.org translations)
```

## Development Workflow

### No Build Tools Required

This plugin follows WordPress.org guidelines with **no build tools, no compilation steps, and no third-party libraries**. The code is deployment-ready as written.

### Testing

Since there are no automated tests configured, manual testing is required:

1. **Local WordPress Installation:**
   - Copy the plugin folder to `wp-content/plugins/environment-indicator/`
   - Activate via Plugins → Installed Plugins
   - Test detection logic by setting `WP_ENVIRONMENT_TYPE` in `wp-config.php`
   - Verify admin bar indicator appears for logged-in users
   - Test settings page at Settings → Environment Indicator

2. **Test Different Environments:**
   ```php
   // Add to wp-config.php to test detection
   define( 'WP_ENVIRONMENT_TYPE', 'development' );  // or 'staging', 'production'
   define( 'WP_ENV', 'staging' );  // Alternative constant
   ```

3. **Multisite Testing:**
   - Test network activation
   - Verify network-wide settings at Network Admin → Settings → Environment Indicator
   - Confirm settings are stored using `get_site_option()`

### Creating Distribution Package

To create a WordPress.org submission package:

```bash
# From the parent directory
zip -r environment-indicator.zip environment-indicator/ -x "*.git*" "*.zip" "specification.md"
```

## WordPress.org Compliance

This plugin is built for WordPress.org submission and strictly adheres to:

### Coding Standards
- WordPress PHP Coding Standards
- All functions/hooks prefixed with `i365ei_`
- All constants prefixed with `I365EI_`
- All CSS classes prefixed with `i365ei-`
- No PHP namespaces (procedural only)
- No unnecessary abstraction or frameworks

### Security Requirements
- Direct access prevention: `if ( ! defined( 'ABSPATH' ) ) { exit; }`
- Output escaping: `esc_html()`, `esc_attr()`, `esc_url()`
- Input sanitization on all saved settings
- Nonce verification on forms
- Capability checks: `manage_options`

### Prohibited Behavior
The plugin must NOT:
- Track users or collect analytics
- Load external scripts, fonts, or APIs
- Display ads, upsells, or promotional notices
- Show persistent admin notices
- Modify front-end HTML for logged-out visitors

## Key Implementation Patterns

### Global Caching for Performance

```php
// Settings are cached globally to avoid repeated option lookups
function i365ei_get_settings() {
    global $i365ei_settings_cache;
    if ( null === $i365ei_settings_cache ) {
        // Load from database only once per request
    }
    return $i365ei_settings_cache;
}
```

### Multisite Awareness

Functions automatically detect network activation and use appropriate storage:
- Network-activated: `get_site_option()` / `update_site_option()`
- Single-site: `get_option()` / `update_option()`

### Environment Color Coding

- **DEV:** Green (#2e8b57)
- **STAGING:** Orange (#f39c12)
- **LIVE:** Red (#c0392b)

## Important Constants

Defined in `environment-indicator.php:18-21`:
- `I365EI_VERSION` - Plugin version
- `I365EI_PLUGIN_FILE` - Main plugin file path
- `I365EI_PLUGIN_DIR` - Plugin directory path
- `I365EI_PLUGIN_URL` - Plugin URL

## Making Changes

When modifying this plugin:

1. **Follow the specification:** All requirements are documented in `specification.md`
2. **Maintain simplicity:** No classes, no build tools, no external dependencies
3. **Test manually:** Verify in actual WordPress installation
4. **Update documentation:** Keep `readme.txt` changelog current
5. **Preserve WP.org compliance:** This plugin must remain submission-ready

## Version Management (CRITICAL)

This plugin follows **STRICT SEMANTIC VERSIONING** (Major.Minor.Patch). **ANY code change REQUIRES a version bump and changelog update.**

### Version Bump Requirements

**ALWAYS increment the version number for:**
- Bug fixes (patch version: 1.0.1 → 1.0.2)
- New features (minor version: 1.0.2 → 1.1.0)
- Breaking changes (major version: 1.1.0 → 2.0.0)
- UI/UX improvements
- Performance optimizations
- Code refactoring
- Documentation updates that affect functionality

**NO EXCEPTIONS.** Every commit that changes functionality must increment the version.

### Files That MUST Be Updated With Every Version Change

1. **environment-indicator.php** (line 5) - Plugin header version
2. **environment-indicator.php** (line 18) - `I365EI_VERSION` constant
3. **readme.txt** (line 7) - Stable tag
4. **readme.txt** - Changelog section (add new entry)
5. **readme.txt** - Upgrade Notice section (add new entry)
6. **CLAUDE.md** (line 15) - Current Version

### Changelog Entry Format

Every version must have a changelog entry in `readme.txt`:

```
= 1.0.2 =
* Fix: Description of bug fix
* Enhancement: Description of improvement
* New: Description of new feature
```

### Version Bump Workflow

1. Make code changes
2. Update ALL version numbers in files listed above
3. Write detailed changelog entry
4. Commit with message: "Release version X.X.X"
5. Create git tag: `git tag vX.X.X`
6. Push tag: `git push origin vX.X.X`
7. Create installation package: `git archive --format=zip --prefix=environment-indicator/ -o ../environment-indicator-X.X.X.zip HEAD`

### Significant Updates Require Full Documentation Review

For **minor or major version bumps**, also review and update:
- **specification.md** - If features/architecture changed
- **README screenshots** - If UI changed significantly
- **Installation instructions** - If setup process changed

**CRITICAL REMINDER:** Version numbers are NOT optional. Every functional change, no matter how small, requires a version bump. This ensures WordPress.org users receive proper update notifications and can track changes through the changelog.

## Text Domain & Internationalization

- Text domain: `365i-environment-indicator`
- The plugin relies on WordPress.org translation loading (no manual `load_plugin_textdomain()` call)
- All user-facing strings must use `__()`, `esc_html__()`, `esc_attr__()`, etc.
