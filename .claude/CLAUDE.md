# Claude Code Learning File - 365i Environment Indicator

This file contains learned patterns and project-specific guidance for the 365i Environment Indicator WordPress plugin.

## Project Architecture

### Core Principle: Separation of Concerns

```
365i-environment-indicator/
├── environment-indicator.php   # Entry point only - constants, includes
├── includes/                   # Core functionality
│   ├── helpers.php            # Settings & utilities (~150 lines)
│   ├── detection.php          # Environment detection (~120 lines)
│   ├── admin-bar.php          # Admin bar UI (~180 lines)
│   ├── settings.php           # Settings page (~400 lines - consider splitting)
│   └── dashboard-widget.php   # Dashboard widget (~130 lines)
├── assets/
│   ├── admin.css              # All admin styles
│   └── settings.js            # Settings page JS
├── languages/                  # Translation files (WP.org managed)
├── readme.txt                  # WordPress.org readme
├── uninstall.php              # Cleanup on delete
└── blueprint.json             # WordPress Playground config
```

### File Size Guidelines
- Target: Under 300 lines per file
- Current exception: settings.php (~400 lines) - acceptable for Settings API complexity
- Action: If any file exceeds 400 lines, split by feature

### Conditional Loading
This plugin currently loads all code for logged-in admin users. Future optimization:
```php
// Only load settings page code when on settings page
add_action( 'admin_init', function() {
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'i365ei-settings' ) {
        require_once I365EI_PLUGIN_DIR . 'includes/settings-heavy.php';
    }
});
```

## WordPress Plugin Handbook Compliance

Reference: https://developer.wordpress.org/plugins/

### Required Prefixes (CRITICAL)
- **Functions:** `i365ei_` (6 characters, exceeds 4-char minimum)
- **Constants:** `I365EI_`
- **CSS Classes:** `i365ei-`
- **HTML IDs:** `i365ei_`
- **Options:** `i365ei_settings`
- **Global Variables:** `$i365ei_*`

### Security Checklist (Every File)
```php
// 1. Direct access prevention (first line after <?php)
if ( ! defined( 'ABSPATH' ) ) { exit; }

// 2. Capability checks before actions
if ( ! current_user_can( 'manage_options' ) ) { return; }

// 3. Nonce verification on forms
if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'i365ei_action' ) ) { return; }

// 4. Input sanitization
$value = sanitize_text_field( wp_unslash( $_POST['field'] ) );

// 5. Output escaping
echo esc_html( $value );
echo esc_attr( $attribute );
echo esc_url( $url );
```

### Settings API Pattern
```php
// Registration
register_setting( 'i365ei_settings_group', 'i365ei_settings', [
    'sanitize_callback' => 'i365ei_sanitize_settings',
    'default' => i365ei_get_default_settings(),
] );

// Sections and fields
add_settings_section( 'i365ei_main', __( 'Title', 'text-domain' ), ... );
add_settings_field( 'i365ei_field', __( 'Label', 'text-domain' ), ... );
```

## Development Workflow

### Making Changes
1. Read existing code first (understand before modifying)
2. Follow existing patterns in the file
3. Keep changes minimal and focused
4. Test in WordPress environment
5. Update version if functionality changed

### Version Bumping (MANDATORY for any change)
Update ALL of these:
1. `environment-indicator.php` line 5 (header)
2. `environment-indicator.php` line 18 (I365EI_VERSION)
3. `readme.txt` line 7 (Stable tag)
4. `readme.txt` changelog section
5. Root `CLAUDE.md` line 15

### Creating Releases
Use `/release` command or:
```bash
cd "e:/Development" && 7z a -tzip "365i-environment-indicator-VERSION.zip" "./365i-environment-indicator" \
  "-x!365i-environment-indicator/.git" \
  "-x!365i-environment-indicator/.claude" \
  "-x!365i-environment-indicator/*.zip" \
  "-x!365i-environment-indicator/CLAUDE.md" \
  "-x!365i-environment-indicator/specification.md" \
  "-x!365i-environment-indicator/.gitattributes" \
  "-x!365i-environment-indicator/.gitignore"
```

## Code Organization Principles

### Single Responsibility
Each file handles ONE concern:
- `detection.php` - ONLY environment detection logic
- `helpers.php` - ONLY settings retrieval/storage
- `admin-bar.php` - ONLY admin bar modifications
- `settings.php` - ONLY settings page UI

### Function Naming
```php
// Retrieval functions
i365ei_get_environment()
i365ei_get_settings()
i365ei_get_default_settings()

// Action functions
i365ei_update_settings()
i365ei_render_admin_bar()

// Check functions
i365ei_is_network_activated()
i365ei_should_show_indicator()

// Sanitization
i365ei_sanitize_settings()
i365ei_escape_css_color()
```

### Global Caching Pattern
```php
function i365ei_get_settings() {
    global $i365ei_settings_cache;
    if ( null === $i365ei_settings_cache ) {
        $i365ei_settings_cache = get_option( 'i365ei_settings', i365ei_get_default_settings() );
    }
    return $i365ei_settings_cache;
}
```

## Common Gotchas

### WordPress.org Submission
1. **Hidden files rejected:** Never include .gitignore, .gitattributes in zip
2. **CLAUDE.md rejected:** Only README.md, CHANGELOG.md, LICENSE.md allowed
3. **Prefix length:** Must be 4+ characters (we use 6: i365ei_)
4. **Global variables:** ALL must be prefixed, including in uninstall.php
5. **filter_input:** Don't use - use direct $_POST with sanitization instead

### PHP/WordPress
1. **$_POST access:** Always use `wp_unslash()` before sanitization
2. **CSS colors:** Validate with regex, not just sanitize_hex_color (for rgba support)
3. **Multisite:** Use `get_site_option()` when network-activated
4. **Text domain:** Must match plugin slug exactly: `365i-environment-indicator`

## Learned Patterns

### WordPress.org Compliance (2024-01)
- **Prefix Requirements**: Function prefixes must be 4+ unique characters
  - Changed from `ei_` to `i365ei_` to meet requirements
  - Apply to: functions, constants, CSS classes, global variables, HTML IDs

- **File Sanitization**: Use `wp_check_filetype()` for uploaded files
  - Don't rely solely on $_FILES['type'] (user-controlled)
  - Validate extension against allowed list

- **CSS Color Escaping**: Create dedicated validation function
  ```php
  function i365ei_escape_css_color( $color ) {
      if ( preg_match( '/^#[a-fA-F0-9]{3,6}$/', $color ) ) {
          return $color;
      }
      return '#000000'; // Safe default
  }
  ```

### Release Process (2024-01)
- **Tool**: Always use 7-Zip (`7z` command)
- **Archive name**: `365i-environment-indicator-VERSION.zip`
- **Folder name**: `365i-environment-indicator/` (no version in folder)
- **Exclusions**: .git, .claude, CLAUDE.md, specification.md, .gitignore, .gitattributes

### User Preferences
- **Archive tool**: 7-Zip preferred over built-in zip
- **Communication**: Keep responses concise
- **Commits**: Detailed messages with emoji prefix for type

---

*This file is automatically updated by the /reflect command based on session diaries.*
*Last updated: 2024-01-06*
