# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**365i Queue Optimizer** is a WordPress plugin that optimizes ActionScheduler queue processing for faster image optimization and background tasks. It provides server-aware recommended settings and a dashboard widget for queue monitoring.

## Architecture

### Directory Structure (Actual)
```
365i-queue-optimizer/
├── 365i-queue-optimizer.php       # Bootstrap: defines constants, loads main class
├── src/
│   └── class-queue-optimizer-main.php  # Core: singleton, ActionScheduler filters
├── admin/
│   ├── class-settings-page.php    # Settings API, AJAX handlers
│   └── class-dashboard-widget.php # WP Dashboard widget
├── templates/
│   ├── admin/settings-page.php    # Settings page template
│   └── partials/                  # Header/footer partials
├── assets/
│   ├── css/admin.css, dashboard-widget.css
│   └── js/admin.js, dashboard-widget.js
├── languages/                     # i18n (.pot file)
├── releases/                      # Release archives (git-ignored)
└── uninstall.php                  # Cleanup on uninstall
```

### Class Responsibilities

**Queue_Optimizer_Main** (`src/class-queue-optimizer-main.php`)
- Singleton pattern via `get_instance()`
- Applies ActionScheduler filters on init
- Server environment detection and recommended settings
- Image editor priority management

**Queue_Optimizer_Settings_Page** (`admin/class-settings-page.php`)
- WordPress Settings API registration
- AJAX handlers for queue operations (`qo_run_queue`, `qo_get_queue_status`)
- Sanitization callbacks for all settings

**Queue_Optimizer_Dashboard_Widget** (`admin/class-dashboard-widget.php`)
- Dashboard widget registration
- Queue health status calculation (healthy/warning/critical)
- Assets only loaded on `index.php` (dashboard)

### Conditional Loading
Admin classes are only loaded when `is_admin()` returns true:
```php
if ( is_admin() ) {
    require_once QUEUE_OPTIMIZER_PLUGIN_DIR . 'admin/class-settings-page.php';
    require_once QUEUE_OPTIMIZER_PLUGIN_DIR . 'admin/class-dashboard-widget.php';
}
```

## ActionScheduler Filters

The plugin hooks into these ActionScheduler filters:
- `action_scheduler_queue_runner_time_limit` - Processing time per run (10-300s)
- `action_scheduler_queue_runner_concurrent_batches` - Simultaneous batches (1-10)
- `action_scheduler_queue_runner_batch_size` - Actions per batch (25-200)
- `action_scheduler_retention_period` - Log retention (1-30 days, stored as seconds)

## WordPress Options

All options use `queue_optimizer_` prefix:
| Option | Type | Default | Range |
|--------|------|---------|-------|
| `queue_optimizer_time_limit` | int | 60 | 10-300 |
| `queue_optimizer_concurrent_batches` | int | 4 | 1-10 |
| `queue_optimizer_batch_size` | int | 50 | 25-200 |
| `queue_optimizer_retention_days` | int | 7 | 1-30 |
| `queue_optimizer_image_engine` | string | WP_Image_Editor_Imagick | Imagick/GD |
| `queue_optimizer_server_type_override` | string | '' | shared/vps/dedicated/'' |
| `queue_optimizer_activated` | int | timestamp | - |

## Server Detection Logic

Detection in `detect_server_type()` checks PHP limits:
- **Dedicated**: memory >= 1GB AND (execution_time >= 300 OR unlimited)
- **VPS**: memory >= 512MB AND execution_time >= 120
- **Shared**: Default fallback (safest)

Manual override via `queue_optimizer_server_type_override` option takes precedence.

## AJAX Endpoints

Both require `qo_admin_nonce` verification and `manage_options` capability:
- `wp_ajax_qo_run_queue` - Triggers `ActionScheduler_QueueRunner::run()`
- `wp_ajax_qo_get_queue_status` - Returns pending/running/failed counts

## Asset Loading

Assets are conditionally loaded based on admin page hook:
- Settings page (`tools_page_queue-optimizer`): admin.css, admin.js
- Dashboard (`index.php`): dashboard-widget.css, dashboard-widget.js

## Deployment Workflow

### WordPress.org SVN

The SVN repository is at `../365i-queue-optimizer-svn/` relative to this repo.

```bash
# Sync trunk
cp -r *.php src/ admin/ templates/ assets/ languages/ ../365i-queue-optimizer-svn/trunk/

# Update assets (screenshots, banners, blueprints)
cp screenshot-*.png ../365i-queue-optimizer-svn/assets/

# Commit trunk
cd ../365i-queue-optimizer-svn
svn commit -m "Version X.Y.Z description"

# Create tag
svn cp trunk tags/X.Y.Z
svn commit -m "Tagging version X.Y.Z"
```

### Release Checklist
1. Update version in `365i-queue-optimizer.php` header AND `QUEUE_OPTIMIZER_VERSION` constant
2. Update `readme.txt` Stable tag and Changelog
3. Update `CHANGELOG.md`
4. Commit to GitHub
5. Sync to SVN trunk, commit
6. Create SVN tag, commit

## Prefix Convention

Use `queue_optimizer_` for options, settings, filters.
Use `qo_` for AJAX actions, CSS classes, JS objects, widget IDs.

## Custom Filters (Extensibility)

The plugin provides filters for third-party customization:
- `queue_optimizer_time_limit`
- `queue_optimizer_concurrent_batches`
- `queue_optimizer_batch_size`
- `queue_optimizer_retention_period`
- `queue_optimizer_image_editors`
- `queue_optimizer_image_memory_limit`
