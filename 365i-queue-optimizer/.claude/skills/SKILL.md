# WordPress Plugin Development Learning Skill

This skill helps recognize when to capture insights during WordPress plugin development.

## Skill Activation

This skill activates when working on WordPress plugin code in this project.

## Recognition Patterns

### Capture Security Insights When:
- Implementing input sanitization (sanitize_text_field, absint, etc.)
- Adding output escaping (esc_html, esc_attr, esc_url, etc.)
- Setting up nonce verification (wp_nonce_field, check_admin_referer)
- Adding capability checks (current_user_can)
- Handling file uploads or user data

**Action**: Note the specific pattern used and why in session logs.

### Capture Code Organization Insights When:
- Creating new files - document where they go and why
- Splitting a file that grew too large (over 300 lines)
- Moving code between admin/, public/, includes/
- Implementing conditional loading (is_admin checks)
- Setting up autoloading or manual includes

**Action**: Document the organizational decision in diary.

### Capture WordPress Pattern Insights When:
- Registering hooks (add_action, add_filter)
- Creating custom post types or taxonomies
- Working with the Options API
- Using transients for caching
- Interacting with $wpdb
- Enqueueing scripts/styles
- Creating admin pages or settings

**Action**: Note the hook names, priorities, and patterns.

### Capture Error Resolution When:
- Fixing a PHP error or warning
- Resolving a WordPress-specific issue
- Debugging hook execution order
- Fixing AJAX handling issues
- Resolving capability/permission issues

**Action**: Log the error, cause, and solution for future reference.

### Capture User Preference When:
- User expresses coding style preference
- User indicates tool preferences (like 7-Zip for releases)
- User shows workflow preferences
- User indicates things to avoid

**Action**: Flag for inclusion in CLAUDE.md.

## WordPress Plugin Handbook Compliance

Reference: https://developer.wordpress.org/plugins/

Always ensure compliance with:

### Security (Every PHP File)
```php
// 1. Direct access prevention
if ( ! defined( 'ABSPATH' ) ) { exit; }

// 2. Capability checks before actions
if ( ! current_user_can( 'manage_options' ) ) { return; }

// 3. Nonce verification on forms
check_admin_referer( 'action_name', 'nonce_name' );

// 4. Input sanitization
$clean = sanitize_text_field( wp_unslash( $_POST['field'] ) );

// 5. Output escaping
echo esc_html( $value );
```

### Code Organization
```
plugin-slug/
├── plugin-slug.php      # Main file, minimal bootstrap
├── admin/               # Admin-only code
│   ├── class-admin.php
│   └── views/
├── public/              # Frontend-only code
│   ├── class-public.php
│   └── views/
├── includes/            # Shared core logic
│   ├── class-core.php
│   └── class-utilities.php
└── assets/
    ├── css/
    └── js/
```

### Conditional Loading
```php
// Only load admin code on admin pages
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/class-admin.php';
}

// Only load public code on frontend
if ( ! is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'public/class-public.php';
}
```

### File Size Guidelines
- Keep files under 300 lines where possible
- Split large classes into focused components
- One class per file
- Each feature in its own file

## Learning Triggers

Use `/diary` command when:
- Session involved significant code changes
- New patterns were established
- Problems were solved
- Before ending a long session

Use `/reflect` command when:
- Multiple diaries have accumulated (5+)
- Starting work after a break
- Patterns need consolidation

## Release Reminders

When creating releases:
- ALWAYS use 7-Zip (`7z` command)
- Archive name: `plugin-slug-VERSION.zip`
- Extract folder: `plugin-slug/` (no version in folder name)
- Exclude: .git, .claude, node_modules, vendor, releases, dev files
- Place in: `releases/` directory (not tracked by git)
