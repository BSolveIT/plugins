# WordPress Plugin Development Learning Skill

This skill helps recognize when to capture insights during WordPress plugin development.

## When to Capture Insights

### Security Patterns
Capture when you:
- Apply input sanitization (sanitize_text_field, absint, etc.)
- Use output escaping (esc_html, esc_attr, esc_url, wp_kses)
- Implement nonce verification
- Add capability checks
- Prevent direct file access

**What to note:** The specific function used, why it was chosen, common mistakes avoided.

### Code Organization Triggers
Capture when you:
- Split a file that grew too large (>300 lines)
- Create a new file for a specific feature
- Implement conditional loading (is_admin(), specific hooks)
- Separate admin from public code
- Decide where new code should live

**What to note:** The decision rationale, file structure pattern, loading strategy.

### WordPress Plugin Handbook Compliance
Capture when following https://developer.wordpress.org/plugins/:
- Proper prefix usage (unique, 4+ characters)
- Settings API implementation
- Internationalization (text domain, translation functions)
- Uninstall procedures
- Multisite considerations

**What to note:** The guideline followed, implementation approach, gotchas discovered.

### WordPress.org Review Feedback
ALWAYS capture when:
- Receiving review feedback from WordPress.org
- Fixing compliance issues
- Adjusting code based on plugin check results

**What to note:** The exact feedback, the fix applied, prevention strategies.

### Error Resolution
Capture when:
- Fixing a bug that took significant debugging
- Discovering a non-obvious WordPress behavior
- Solving a compatibility issue
- Resolving a PHP warning/error

**What to note:** The error, root cause, solution, how to prevent recurrence.

### User Preference Discovery
Capture when the user:
- Expresses a preference for tools (e.g., 7-Zip)
- Shows coding style preferences
- Demonstrates workflow preferences
- Indicates communication preferences

**What to note:** The preference, context, how to apply it going forward.

## Code Separation Guidelines

### File Size Principle
- Target: Files under 300 lines
- Action trigger: When a file exceeds 300 lines, consider splitting
- Exception: Complex single-purpose files may exceed if well-organized

### Separation of Concerns
```
plugin-root/
├── plugin-main.php      # Entry point, constants, includes
├── includes/            # Core functionality
│   ├── class-*.php      # Classes (one per file)
│   ├── functions-*.php  # Grouped utility functions
│   └── hooks.php        # Hook registrations
├── admin/               # Admin-only code
│   ├── settings.php     # Settings page
│   └── admin-*.php      # Admin features
├── public/              # Frontend-only code
│   └── public-*.php     # Public features
└── assets/              # CSS, JS, images
```

### Conditional Loading Pattern
```php
// Only load admin code on admin pages
if ( is_admin() ) {
    require_once PLUGIN_DIR . 'admin/settings.php';
}

// Only load on specific hook
add_action( 'wp_enqueue_scripts', function() {
    // Frontend-only code here
});

// Only load on specific admin page
add_action( 'admin_init', function() {
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'my-settings' ) {
        require_once PLUGIN_DIR . 'admin/settings-page.php';
    }
});
```

## Pattern Recognition Triggers

When you notice yourself doing something 2+ times, ask:
1. Should this be documented in CLAUDE.md?
2. Is this a WordPress best practice others should follow?
3. Did this solve a recurring problem?
4. Would future sessions benefit from knowing this?

If yes to any, use `/diary` to capture it, then `/reflect` to extract patterns.

## Learning Loop

```
Work on plugin
    ↓
Hook captures outcomes (automatic)
    ↓
Session ends or compacts
    ↓
/diary creates structured entry
    ↓
Patterns accumulate in diaries
    ↓
/reflect extracts to CLAUDE.md
    ↓
Next session loads CLAUDE.md
    ↓
Apply learned patterns
    ↓
(repeat)
```
