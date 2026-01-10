# 365i Queue Optimizer - Project Instructions

This file contains project-specific patterns and learnings for Claude Code.
Reference: https://developer.wordpress.org/plugins/

## Project Architecture

### Directory Structure
```
365i-queue-optimizer/
├── 365i-queue-optimizer.php   # Main plugin file (bootstrap only)
├── admin/                      # Admin-only functionality
│   ├── class-admin.php         # Admin class
│   └── views/                  # Admin templates
├── public/                     # Frontend-only functionality
│   ├── class-public.php        # Public class
│   └── views/                  # Public templates
├── includes/                   # Core logic (shared)
│   ├── class-core.php          # Core functionality
│   └── class-*.php             # Feature classes
├── assets/
│   ├── css/
│   └── js/
├── releases/                   # Release archives (git-ignored)
└── .claude/                    # Claude Code learning system
    ├── sessions/               # Session logs and diaries
    ├── hooks/                  # Automation hooks
    ├── commands/               # Slash commands
    └── skills/                 # Development skills
```

### Code Organization Principles
- **Keep files focused**: Under 300 lines where possible
- **One class per file**: Each class in its own file
- **Separate concerns**: Admin code in admin/, public code in public/, shared in includes/
- **Conditional loading**: Only load code when needed

## WordPress Plugin Handbook Compliance

### Security Checklist (Every PHP File)
```php
// 1. Direct access prevention (FIRST LINE after <?php)
if ( ! defined( 'ABSPATH' ) ) { exit; }

// 2. Capability check before sensitive operations
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized access', 'textdomain' ) );
}

// 3. Nonce verification on form submissions
if ( ! check_admin_referer( 'qo_action', 'qo_nonce' ) ) {
    wp_die( esc_html__( 'Security check failed', 'textdomain' ) );
}

// 4. Input sanitization (ALWAYS)
$text = sanitize_text_field( wp_unslash( $_POST['field'] ) );
$int = absint( $_POST['number'] );
$email = sanitize_email( $_POST['email'] );
$url = esc_url_raw( $_POST['url'] );

// 5. Output escaping (ALWAYS)
echo esc_html( $text );
echo esc_attr( $attribute );
echo esc_url( $url );
echo wp_kses_post( $html_content );
```

### Prefix Convention
Use `qo_` or `queue_optimizer_` prefix for:
- Function names: `qo_get_queue_items()`
- Constants: `QO_VERSION`
- Options: `qo_settings`
- Transients: `qo_cache_*`
- CSS classes: `.qo-container`
- JavaScript globals: `qoAdmin`
- Database tables: `{$wpdb->prefix}qo_*`

### Hook Registration
```php
// Actions - use descriptive names, appropriate priority
add_action( 'admin_init', array( $this, 'register_settings' ) );
add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );

// Filters - always return the filtered value
add_filter( 'plugin_action_links_' . QO_BASENAME, array( $this, 'add_action_links' ) );
```

## Development Workflow

### Before Writing Code
1. Read existing code first - understand the patterns
2. Check where new code belongs (admin/public/includes)
3. Verify file won't exceed 300 lines with additions
4. Plan security measures needed

### While Writing Code
1. Add ABSPATH check if new PHP file
2. Sanitize all input as it comes in
3. Escape all output as it goes out
4. Use WordPress functions over PHP natives

### After Writing Code
1. Verify no security vulnerabilities
2. Check file organization is correct
3. Confirm conditional loading works
4. Test in admin and frontend contexts

## Conditional Loading Patterns

### Main Plugin File Bootstrap
```php
// In main plugin file - minimal bootstrap
if ( is_admin() ) {
    require_once QO_PATH . 'admin/class-admin.php';
    new QO_Admin();
}

if ( ! is_admin() || wp_doing_ajax() ) {
    require_once QO_PATH . 'public/class-public.php';
    new QO_Public();
}

// Always load core
require_once QO_PATH . 'includes/class-core.php';
```

### Feature-Specific Loading
```php
// Only load on specific admin pages
public function maybe_load_assets( $hook ) {
    if ( 'settings_page_qo-settings' !== $hook ) {
        return;
    }
    wp_enqueue_style( 'qo-admin' );
    wp_enqueue_script( 'qo-admin' );
}
add_action( 'admin_enqueue_scripts', array( $this, 'maybe_load_assets' ) );
```

## Release Process

### Archive Creation (ONLY use 7-Zip)
```bash
# Get version from plugin header
# Create archive: plugin-slug-VERSION.zip
# Contents extract to: plugin-slug/ (fixed, no version in folder)

7z a -tzip "releases/365i-queue-optimizer-X.Y.Z.zip" . \
    -xr!.git -xr!.claude -xr!node_modules -xr!vendor \
    -xr!releases -xr!.gitignore -xr!.gitattributes
```

### Pre-Release Checklist
- [ ] Version updated in plugin header
- [ ] Changelog updated
- [ ] All files under 300 lines
- [ ] Security audit passed
- [ ] Tested on target WP versions
- [ ] No debug code remaining

## Common Gotchas

### AJAX Handling
```php
// Register AJAX handlers for both logged-in and logged-out users if needed
add_action( 'wp_ajax_qo_action', array( $this, 'handle_ajax' ) );
add_action( 'wp_ajax_nopriv_qo_action', array( $this, 'handle_ajax_public' ) );

// Always verify nonce in AJAX handlers
public function handle_ajax() {
    check_ajax_referer( 'qo_ajax_nonce', 'nonce' );
    // ... handler code
    wp_send_json_success( $data );
}
```

### Options API
```php
// Use get_option with defaults
$options = get_option( 'qo_settings', array(
    'enabled' => true,
    'limit'   => 10,
) );

// Sanitize before saving
update_option( 'qo_settings', $this->sanitize_settings( $options ) );
```

### Transient Caching
```php
// Check transient first
$data = get_transient( 'qo_cached_data' );
if ( false === $data ) {
    $data = $this->expensive_operation();
    set_transient( 'qo_cached_data', $data, HOUR_IN_SECONDS );
}
```

## Learned Patterns

*This section is updated automatically by the /reflect command based on session diaries.*

<!-- Patterns learned from development sessions will be added here -->

---
*Last updated: Initial setup*
*Learning system: Active*
