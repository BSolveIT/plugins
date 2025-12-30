=== 365i Environment Indicator ===
Contributors: bsolveit
Tags: environment, development, staging, production, admin bar
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Displays a prominent admin bar label showing the current environment: DEV (green), STAGING (orange), or LIVE (red) to prevent accidental changes.

== Description ==

Environment Indicator adds a prominent visual indicator to the WordPress admin bar showing the current site environment. This high-visibility indicator helps prevent accidental changes on production sites by making the environment instantly recognizable to logged-in users.

Perfect for developers, agencies, and teams managing multiple environments (development, staging, production) across different sites.

= Key Features =

**Smart Environment Detection**
* Automatically detects environment using WordPress core constants (`WP_ENVIRONMENT_TYPE`)
* Supports popular hosting providers: WP Engine, Pantheon, Kinsta, Flywheel
* Legacy Bedrock constant support (`WP_ENV`)
* Subdomain-based heuristics (dev.example.com, staging.example.com)
* Manual override option for custom setups

**Customization Options**
* **Custom Colors** - Match your branding or personal preferences for each environment
* **Custom Labels** - Rename DEV/STAGING/LIVE to LOCAL, UAT, PRODUCTION, or anything you prefer
* **Role-Based Visibility** - Control which user roles can see the indicator (great for client sites)

**Visual Enhancements**
* Admin bar label (always visible)
* Full admin bar background coloring (high-visibility option)
* Top border in wp-admin screens
* Admin footer watermark
* Dashboard widget showing environment status and system information

**Export/Import**
* Export settings as JSON to deploy across multiple sites
* Import settings from JSON file
* Perfect for agencies managing client sites with consistent configurations

**Multisite Compatible**
* Full support for WordPress multisite installations
* Network-wide settings when network-activated
* Per-site settings when activated on individual sites

= How It Works =

Detection runs in this order:

1. WordPress core constant: `WP_ENVIRONMENT_TYPE`
2. Legacy constant: `WP_ENV`
3. Hosting provider constants (WP Engine, Pantheon, Kinsta, Flywheel)
4. Subdomain patterns (dev, staging, test, qa)
5. Default to LIVE if no detection

You can always override automatic detection and manually select the environment.

= Perfect For =

* **Developers** - Never accidentally deploy to the wrong environment
* **Agencies** - Manage multiple client sites with consistent environment indicators
* **Teams** - Everyone instantly knows which environment they're working in
* **Managed Hosting** - Works out-of-the-box with WP Engine, Pantheon, Kinsta, and Flywheel

= Zero Impact on Visitors =

The indicator only displays for logged-in users. Public visitors see no performance impact or visual changes.

== Installation ==

1. Upload the `environment-indicator` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu in WordPress
3. Visit Settings → Environment Indicator to customize (optional)

The indicator will appear immediately in your admin bar using automatic detection.

== Frequently Asked Questions ==

= Does this affect public visitors? =

No. The indicator only displays for logged-in users who can see the WordPress admin bar. There is zero impact on public visitors.

= Can I customize the colors? =

Yes! Enable custom colors in Settings → Environment Indicator and choose any color for DEV, STAGING, and LIVE environments.

= Can I change the labels from DEV/STAGING/LIVE? =

Absolutely. Enable custom labels and rename them to anything you want: LOCAL, UAT, PRODUCTION, or company-specific terms.

= Does it work on multisite? =

Yes. When network-activated, settings are managed network-wide from Network Admin → Settings → Environment Indicator.

= Which hosting providers are supported? =

Environment Indicator automatically detects environments on:
* WP Engine (WPE_ENVIRONMENT)
* Pantheon (PANTHEON_ENVIRONMENT)
* Kinsta (KINSTA_ENV_TYPE)
* Flywheel (FLYWHEEL_CONFIG_DIR)
* Any host using WP_ENVIRONMENT_TYPE or WP_ENV constants

= Can I restrict who sees the indicator? =

Yes. Enable role-based visibility and select which user roles can see the environment indicator. This is useful for client sites where you don't want clients to see the indicator.

= Does this plugin block actions or restrict editing? =

No. Environment Indicator is visual only. It does not restrict editing, block actions, or change how WordPress behaves. It simply provides a clear visual reminder of which environment you're working in.

= What happens if no environment is detected? =

If no environment constants or recognized subdomain patterns are found, the site defaults to LIVE. You can override this using manual mode in the settings.

= Can I export settings to use on other sites? =

Yes! Use the Export/Import feature to download your settings as JSON and import them on other sites. Perfect for agencies deploying the same configuration across client sites.

= How do I set the environment constant? =

Add one of these lines to your `wp-config.php` file:

For WordPress 5.5+:
`define( 'WP_ENVIRONMENT_TYPE', 'development' ); // or 'staging' or 'production'`

For legacy Bedrock:
`define( 'WP_ENV', 'development' ); // or 'staging' or 'production'`

== Screenshots ==

1. Admin bar indicator showing DEV environment (green)
2. Settings page with live preview and all customization options
3. Dashboard widget displaying environment status and system information
4. Custom colors and labels configuration
5. Admin bar with full background coloring enabled (high-visibility)
6. Role-based visibility settings

== Changelog ==

= 1.0.5 =
* Fix: Added translators comment for sprintf() placeholder in detection.php
* Fix: Replaced parse_url() with wp_parse_url() in dashboard-widget.php for consistency
* Fix: Sanitized $_FILES input in settings.php for WordPress.org compliance
* Fix: Reduced tags to 5 (WordPress.org limit)

= 1.0.4 =
* Fix: Updated text domain to match plugin slug (365i-environment-indicator) for WordPress.org compliance

= 1.0.3 =
* Fix: Removed negative margins from dashboard widget to improve integration
* Enhancement: Widget footer now properly contained within widget boundaries

= 1.0.2 =
* Fix: Dashboard widget settings button layout and sizing improvements
* Fix: Button now properly fits within widget footer container
* Enhancement: Improved icon placement with space-between layout

= 1.0.1 =
* Enhancement: Redesigned dashboard widget with professional, modern UI
* Enhancement: Added gradient header with rainbow accent bar
* Enhancement: Color-coded card icons with gradient backgrounds (blue, green, purple, orange)
* Enhancement: Smooth hover animations and micro-interactions
* Enhancement: Improved responsive design for mobile devices
* Enhancement: Better typography with proper font weights and spacing
* Enhancement: Enhanced settings button with animated arrow on hover
* Fix: Separated import/export form from main settings form to prevent save button triggering file upload prompt
* Fix: Improved visual hierarchy and information architecture

= 1.0.0 =
* Initial release
* Automatic environment detection (WordPress core, WP Engine, Pantheon, Kinsta, Flywheel)
* Custom colors for each environment
* Custom labels for each environment
* Role-based visibility controls
* Dashboard widget with environment status
* Export/Import settings functionality
* Visual enhancements (admin bar background, top border, footer watermark)
* Full multisite support
* Subdomain-based detection heuristics
* Manual environment override

== Upgrade Notice ==

= 1.0.5 =
WordPress.org compliance fixes: translators comment, wp_parse_url usage, sanitized file upload, reduced tags.

= 1.0.4 =
Text domain updated to match plugin slug for WordPress.org compliance. No functional changes.

= 1.0.3 =
Dashboard widget visual improvements with proper footer integration and removed negative margins.

= 1.0.2 =
Dashboard widget settings button layout improvements and proper container fitting.

= 1.0.1 =
Dashboard widget redesigned with professional UI, improved animations, and better mobile responsiveness. Settings form bug fix.

= 1.0.0 =
Initial release with comprehensive environment detection, customization options, and visual enhancements.

== Development ==

Development of this plugin happens on GitHub: https://github.com/BSolveIT/environment-indicator

Bug reports and feature requests are welcome!
