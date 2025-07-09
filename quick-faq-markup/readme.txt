=== Quick FAQ Markup ===
Contributors: BSolveIT
Donate link: https://bsolveit.com/donate
Tags: faq, accordion, schema, seo, accessibility, markup, json-ld, shortcode
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive WordPress plugin for creating and managing FAQ sections with drag-and-drop reordering, multiple display styles, and JSON-LD schema markup support.

== Description ==

Quick FAQ Markup is a powerful and user-friendly WordPress plugin designed to help you create professional FAQ sections on your website. Whether you need to answer customer questions, provide product information, or create helpful support documentation, this plugin provides all the tools you need.

= Key Features =

* **Four Beautiful Display Styles**: Classic list, modern accordion, minimal accordion, and card layout
* **Drag-and-Drop Reordering**: Easily organize your FAQs with intuitive admin interface
* **JSON-LD Schema Markup**: Automatic structured data for better SEO and search results
* **Accessibility Compliant**: Full WCAG 2.1 AA compliance with keyboard navigation and screen reader support
* **Shortcode System**: Flexible shortcode with extensive parameters for custom displays
* **Direct Linking**: Anchor links for easy FAQ sharing and navigation
* **Search Functionality**: Optional search box for large FAQ collections
* **Category Management**: Organize FAQs by categories for better content management
* **Performance Optimized**: Smart caching and conditional asset loading
* **Developer Friendly**: Extensive hooks and filters for customization

= Display Styles =

1. **Classic List**: Traditional Q&A format with clean typography
2. **Accordion Modern**: Interactive accordion with smooth animations
3. **Accordion Minimal**: Clean, minimal accordion design
4. **Card Layout**: Modern card-based layout for better visual hierarchy

= SEO Benefits =

* Automatic JSON-LD structured data markup
* Search engine friendly anchor URLs
* Open Graph meta tags for social sharing
* Core Web Vitals optimized performance
* SEO-friendly heading structure

= Shortcode Usage =

`[qfm_faq]` - Display all FAQs with default settings
`[qfm_faq style="accordion-modern"]` - Use modern accordion style
`[qfm_faq category="support" limit="5"]` - Show 5 FAQs from support category
`[qfm_faq style="card-layout" show_search="true"]` - Card layout with search box
`[qfm_faq ids="1,5,10" show_anchors="false"]` - Specific FAQs without anchor links

= Developer Features =

* Comprehensive hook system for customization
* Clean, well-documented code following WordPress standards
* Translation ready with full internationalization support
* REST API integration ready
* Multisite compatible

= Security & Performance =

* All inputs sanitized and outputs escaped
* Nonce verification on all forms
* SQL injection prevention
* Smart caching system
* Conditional asset loading
* Minified CSS and JavaScript

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin dashboard
2. Navigate to Plugins > Add New
3. Search for "Quick FAQ Markup"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Upload the entire `quick-faq-markup` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress

= After Activation =

1. Go to FAQs in your WordPress admin menu
2. Create your first FAQ by clicking "Add New"
3. Use the shortcode `[qfm_faq]` in any post, page, or widget to display your FAQs

== Frequently Asked Questions ==

= How do I display FAQs on my website? =

Use the shortcode `[qfm_faq]` in any post, page, or widget. You can customize the display with various parameters like style, category, and limit.

= Can I customize the appearance of the FAQs? =

Yes! The plugin includes four built-in styles, and developers can create custom styles using CSS and the provided hooks and filters.

= Does the plugin support SEO? =

Absolutely! The plugin automatically generates JSON-LD structured data markup, which helps search engines understand your FAQ content and may display it in rich snippets.

= Is the plugin accessible? =

Yes, the plugin is fully compliant with WCAG 2.1 AA accessibility standards, including keyboard navigation and screen reader support.

= Can I reorder my FAQs? =

Yes, you can easily reorder FAQs using the drag-and-drop interface in the admin area or by setting the order value manually.

= Does the plugin work with my theme? =

The plugin is designed to work with any properly coded WordPress theme. The CSS is theme-agnostic and responsive.

= Can I import/export FAQs? =

The plugin uses WordPress's standard post system, so you can export/import FAQs using WordPress's built-in tools or any compatible plugin.

= Is the plugin translation ready? =

Yes, the plugin is fully internationalized and includes a .pot file for translations. It supports WordPress 6.8+ automatic translation loading.

= How do I get support? =

For support questions, please visit our support forum or contact us through our website at https://bsolveit.com/support

= Can I contribute to the plugin? =

Yes! The plugin is open source and available on GitHub. Contributions are welcome.

== Screenshots ==

1. Admin interface with drag-and-drop FAQ management
2. Classic list display style on frontend
3. Modern accordion display style
4. Card layout display style
5. Plugin settings page
6. FAQ meta box for editing content
7. Shortcode parameters documentation
8. Mobile responsive design

== Changelog ==

= 1.0.0 - 2025-01-08 =
* Initial release
* Four display styles (Classic, Accordion Modern, Accordion Minimal, Card Layout)
* Drag-and-drop FAQ reordering
* JSON-LD schema markup support
* Accessibility compliance (WCAG 2.1 AA)
* Comprehensive shortcode system
* Direct linking with anchor URLs
* Search functionality
* Category management
* Performance optimization with caching
* Translation support
* Developer hooks and filters
* Security hardening with nonce verification
* Core Web Vitals optimization

== Upgrade Notice ==

= 1.0.0 =
Initial release of Quick FAQ Markup. Install to start creating professional FAQ sections with advanced features and SEO optimization.

== Technical Requirements ==

* WordPress 6.0 or higher
* PHP 8.0 or higher
* MySQL 5.6 or higher
* Modern web browser with JavaScript enabled

== Support ==

For technical support, feature requests, or bug reports:

* Support Forum: https://wordpress.org/support/plugin/quick-faq-markup/
* Documentation: https://bsolveit.com/docs/quick-faq-markup/
* Contact: https://bsolveit.com/support/

== Privacy ==

This plugin does not collect any user data or communicate with external servers. All data is stored locally in your WordPress database.

== Credits ==

Developed by BSolveIT - Professional WordPress Development
Website: https://bsolveit.com