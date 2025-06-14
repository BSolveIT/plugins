# Product Requirements Document
## Queue Optimizer Plugin v1.0

### 1. Overview
**What**: A lightweight WordPress plugin to manage and optimize background queue processing (think scheduled tasks) without bundling heavy libraries or frameworks.  
**Why**: Your current build is either too bare-bones or over-engineered—this one does the job simply, safely, and in line with WordPress.org guidelines.

### 2. Objectives
- **Reliability**: Use native WP scheduling (`wp_schedule_event`) or properly include Action Scheduler only if strictly necessary.
- **Simplicity**: Minimal file structure, no React, no git submodules, no third-party monoliths.
- **Standards**: PSR-12 PHP, WP coding standards, i18n, capability checks, nonces, escape all output.

### 3. Target Audience & Use Cases
- **Site Owners** who need fine-tuned control over background jobs (image processing, report generation, etc.).
- **Developers** who want an easy-to-read, easy-to-extend plugin skeleton for scheduling tasks.

### 4. User Stories
1. *As an admin*, I can set the job time limit (seconds) via a single clean settings page.
2. *As an admin*, I can choose how many concurrent batches run at once.
3. *As an admin*, I can view pending/processing/completed job counts and trigger a manual run.
4. *As an admin*, I can enable or disable detailed logging.

### 5. Functional Requirements

| Section             | Details                                                                                     |
|---------------------|---------------------------------------------------------------------------------------------|
| **Settings Page**   | Single WP Admin submenu under “Tools” or top-level menu; form fields for: <br> • Time limit (number) <br> • Concurrent batches (number) <br> • Logging toggle (checkbox) |
| **Scheduler Logic** | • Use `wp_schedule_event` for recurring runs <br> • Provide a “Run Now” button calling an AJAX handler with proper nonce checks <br> • Track status counts via custom DB table or options |
| **Logging**         | If enabled, write simple log entries (timestamp + message) to `<plugin>/logs/` or WP cron logs. |
| **Cleanup**         | On uninstall, remove scheduled hooks, delete options, and flush logs.                       |

### 6. Non-Functional Requirements
- **Performance**: Minimal overhead; assets only load on the plugin’s admin pages.
- **Security**: Capability check (`manage_options`) on all admin actions; nonce verification on form submissions and AJAX.
- **Compatibility**: WP 5.8+ (PHP 7.4+); WordPress.org repo-ready.
- **Internationalisation**: Wrap all user-facing text in `__()`/`_e()` with text domain `queue-optimizer`.

### 7. Technical & Architectural Constraints
- **File Structure**:
  ```
  queue-optimizer/
  ├─ queue-optimizer.php
  ├─ admin/
  │   └─ class-settings-page.php
  ├─ includes/
  │   ├─ class-scheduler.php
  │   └─ uninstall.php
  ├─ assets/
  │   ├─ admin.js
  │   └─ admin.css
  └─ readme.txt
  ```
- **Autoloading**: PSR-4 in `composer.json` or simple `require_once` in main plugin file.
- **No Bundled Libraries**: If Action Scheduler is needed, declare it as a Composer dependency or use built-in scheduling.

### 8. UI / UX Sketch
- **Settings Page Layout**:
  - Title: “Queue Optimizer Settings”
  - Fields in a single form panel, Save button at bottom
  - Below form: status dashboard showing counts with “Run Now” and “Clear Logs” buttons

*(Keep styling simple, use WordPress admin CSS classes; no heavy frameworks.)*

### 9. Deliverables
- Fully working plugin zip ready for WP.org submission
- Well-formatted `readme.txt` with installation and usage instructions
- Inline code comments for all major methods/classes
- Basic unit tests (optional but encouraged) for scheduler logic

### 10. Acceptance Criteria
- [ ] Settings form saves & sanitises inputs correctly
- [ ] Scheduled events fire at correct intervals
- [ ] Manual “Run Now” works via AJAX with nonce security
- [ ] Logs write only when enabled, and are cleaned up on uninstall
- [ ] Plugin passes PHP_CodeSniffer with WP Coding Standards
- [ ] Plugin zip installs cleanly on a fresh WP 6.x install

### 11. Timeline & Milestones
1. **Week 1**: Scaffold plugin structure & basic scheduler class
2. **Week 2**: Build settings page & AJAX “Run Now”
3. **Week 3**: Add logging, uninstall cleanup, testing
4. **Week 4**: Code review, standards compliance, packaging for WP.org
