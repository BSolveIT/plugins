# Changelog

All notable changes to the Quick FAQ Markup plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.4] - 2025-01-09

### Added
- Default sorting by menu_order for FAQ admin list
- `set_default_faq_admin_sorting()` method to handle automatic sorting
- Enhanced debug logging for admin query state tracking

### Changed
- **UX IMPROVEMENT**: FAQs now display in numerical order (1, 2, 3, 4, 5) by default without requiring manual sorting
- **UX IMPROVEMENT**: Order column now shows as active/sorted column in admin interface by default
- Admin list page now automatically sorts by Order column (ascending) when no other sorting is specified
- URL parameters are automatically set to ensure visual sorting indicators match actual behavior

### Fixed
- Fixed misleading visual indicator where Date column appeared sorted when FAQs were actually sorted by Order
- Resolved visual disconnect between actual sorting behavior and admin interface indicators
- Enhanced admin interface consistency with proper sorting column highlighting

### Technical
- Added pre_get_posts hook registration for `set_default_faq_admin_sorting` method
- Implemented URL parameter manipulation to synchronize visual indicators with query behavior
- Enhanced debugging capabilities with comprehensive query state logging

## [2.0.3] - 2025-01-09

### Fixed
- **CRITICAL**: Fixed infinite loop in auto-increment order functionality causing display order values like 3844
- Fixed `Maximum execution time of 60 seconds exceeded` error during FAQ creation
- Replaced `wp_update_post()` with `wp_insert_post_data` filter to prevent recursive save_post hook triggers
- Added `temp_order_for_save` property to safely store calculated order values
- Improved post save logic to prevent hang/timeout issues during FAQ creation

### Technical
- Added `filter_post_data_for_order()` method to handle order assignment without recursion
- Modified `save_meta_data()` to use temporary storage instead of `wp_update_post()`
- Enhanced logging to track order assignment via wp_insert_post_data filter

## [2.0.2] - 2025-01-09

### Added
- Auto-increment order functionality for new FAQs
- `get_next_faq_order()` method for automatic order assignment
- Usage instructions in meta box to guide users

### Changed
- **UX IMPROVEMENT**: WordPress title field now serves as the question field
- **UX IMPROVEMENT**: Removed separate "Question" meta field to eliminate confusion
- **UX IMPROVEMENT**: Removed duplicate order fields by removing page-attributes support
- **UX IMPROVEMENT**: New FAQs automatically receive incremental order numbers
- Updated admin columns to reflect new structure (title column now labeled "Question")
- Updated frontend to use `get_the_title()` instead of meta field for questions
- Simplified meta box interface with cleaner, more intuitive layout
- Enhanced save logic to handle auto-increment order assignment

### Removed
- Separate "Question" meta field from FAQ meta box
- `page-attributes` support from FAQ post type (eliminates duplicate order interface)
- Question field handling from admin save logic
- Question column from admin list (now uses title column)

### Fixed
- Eliminated user confusion from duplicate title/question fields
- Removed duplicate order field interfaces
- Streamlined FAQ creation workflow
- Improved consistency with WordPress post editing experience

### Security
- Maintained all existing security measures during UX improvements

## [2.0.1] - 2025-01-09

### Fixed
- **CRITICAL**: Fixed fatal error in `Quick_FAQ_Markup_Frontend` class preventing shortcode usage
- Resolved `Call to undefined method Quick_FAQ_Markup_Frontend::add_faqs_to_schema()` error
- Fixed malformed PHP code structure that mixed JavaScript and PHP methods
- Restored ability to save and edit pages containing `[qfm_faq]` shortcode
- Corrected JavaScript/PHP code separation in frontend class

### Security
- Maintained all existing security measures during code restructuring

## [2.0.0] - 2025-01-09

### Added
- Hierarchical FAQ Categories taxonomy (`qfm_faq_category`)
- WordPress standard taxonomy interface for category management
- Taxonomy-based filtering in shortcodes
- Cache invalidation for taxonomy term changes
- Helper methods for category validation and management
- Support for multiple categories per FAQ
- **Admin List View Enhancements**:
  - Category filtering dropdown with "All Categories" option
  - Reordered admin columns for better user experience
  - Enhanced category column with proper taxonomy display
  - Sortable category column functionality
  - Improved admin filtering with taxonomy-based queries

### Changed
- **BREAKING**: Replaced meta field category system with WordPress taxonomy
- **BREAKING**: Category data structure now uses taxonomy terms instead of meta fields
- Shortcode category parameter now uses taxonomy slugs/IDs
- FAQ queries now use `tax_query` instead of `meta_query` for categories
- Admin interface now uses standard WordPress category metabox
- Cache system updated to handle taxonomy-based queries
- **Admin Column Order**: Reordered columns to Title, Question, Answer, Category, Order, Date for better workflow
- **Drag Handle Repositioning**: Fixed drag handle positioning from vertical stack (above title) to horizontal layout (left of title)
- Enhanced drag handle styling with flexbox layout for better alignment and user experience
- Implemented JavaScript-based drag handle injection to work around WordPress built-in column limitations
- Added `inject_title_drag_handles()` method to admin class for proper HTML structure generation
- Improved drag-and-drop functionality with dedicated handle selector for better precision

### Removed
- **BREAKING**: Category text input field from FAQ meta box
- **BREAKING**: `_qfm_faq_category` meta field system
- **BREAKING**: Category admin column (replaced by taxonomy column)
- Category-related CSS for removed meta field interface

### Fixed
- Improved category management workflow
- Better performance for category-based queries
- Enhanced category hierarchy support

### Security
- Maintained all existing security measures
- Added proper capability checks for taxonomy operations

## [1.0.0] - 2025-01-08

### Added
- **Core Features**
  - Custom post type (`qfm_faq`) for FAQ management
  - Four beautiful display styles: Classic, Accordion Modern, Accordion Minimal, Card Layout
  - Comprehensive shortcode system with extensive parameters
  - Drag-and-drop FAQ reordering in admin interface
  - Category-based FAQ organization

- **SEO & Schema Markup**
  - Automatic JSON-LD structured data generation
  - Open Graph meta tags for social sharing
  - SEO-friendly anchor URLs for direct linking
  - Core Web Vitals optimization

- **Accessibility Features**
  - Full WCAG 2.1 AA compliance
  - Keyboard navigation support
  - Screen reader optimization
  - High contrast mode support
  - Reduced motion preference support

- **User Experience**
  - Search functionality with live filtering
  - Smooth animations (respecting user preferences)
  - Responsive design for all devices
  - Direct FAQ linking with anchor URLs
  - Smart scroll behavior with offset detection

- **Performance Optimizations**
  - Conditional asset loading (only when needed)
  - Smart caching system with configurable duration
  - Database query optimization
  - CSS and JavaScript minification ready
  - Lazy loading compatible

- **Developer Features**
  - Extensive hook and filter system
  - Clean, documented code following WordPress standards
  - Translation-ready with .pot file
  - REST API integration ready
  - Multisite compatibility

- **Security Features**
  - All inputs sanitized with appropriate WordPress functions
  - All outputs escaped to prevent XSS
  - Nonce verification on all forms and AJAX endpoints
  - Capability checks for all admin actions
  - SQL injection prevention via prepared statements

- **Admin Interface**
  - Intuitive FAQ editing with rich text editor
  - Bulk actions for FAQ management
  - Custom admin columns with sortable fields
  - Settings page with comprehensive options
  - Real-time order updates via AJAX

- **Translation Support**
  - Full internationalization (i18n) support
  - WordPress 6.8+ automatic translation loading
  - Translation template (.pot) file included
  - All user-facing strings are translatable

### Technical Specifications
- **WordPress**: Requires 6.0+, tested up to 6.8
- **PHP**: Requires 8.0+
- **Database**: Optimized queries with proper indexing
- **Standards**: WordPress Coding Standards compliant
- **Security**: Passes WordPress Plugin Check tool
- **Accessibility**: WCAG 2.1 AA compliant
- **Performance**: Core Web Vitals optimized

### File Structure
```
quick-faq-markup/
├── quick-faq-markup.php          # Main plugin file
├── readme.txt                    # WordPress.org readme
├── LICENSE                       # GPL v2 license
├── CHANGELOG.md                   # This changelog
├── uninstall.php                 # Clean uninstall
├── languages/                    # Translation files
│   └── quick-faq-markup.pot      # Translation template
├── admin/                        # Admin functionality
│   ├── class-quick-faq-markup-admin.php
│   ├── css/quick-faq-markup-admin.css
│   ├── js/quick-faq-markup-admin.js
│   └── partials/
│       ├── faq-meta-box.php
│       └── settings-page.php
├── includes/                     # Core functionality
│   ├── class-quick-faq-markup.php
│   ├── class-quick-faq-markup-frontend.php
│   ├── class-quick-faq-markup-shortcode.php
│   └── class-quick-faq-markup-schema.php
└── public/                       # Frontend assets
    ├── css/quick-faq-markup-public.css
    └── js/quick-faq-markup-public.js
```

### Security Measures Implemented
- Input sanitization using WordPress sanitize_* functions
- Output escaping using WordPress esc_* functions
- Nonce verification on all forms and AJAX requests
- Capability checks before sensitive operations
- SQL injection prevention via $wpdb->prepare()
- CSRF protection on all admin actions
- Direct file access prevention (ABSPATH checks)

### Performance Optimizations
- Conditional asset loading (CSS/JS only when needed)
- Database query caching with configurable duration
- Optimized SQL queries with proper joins
- Minification-ready assets
- Core Web Vitals optimization
- Lazy loading compatibility
- Smart image handling in FAQ content

### Browser Compatibility
- Modern browsers (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
- Internet Explorer 11 (basic functionality)
- Mobile browsers (iOS Safari, Chrome Mobile, Samsung Internet)

### Known Limitations
- None identified in this release

### Upgrade Notes
- This is the initial release
- No upgrade procedures required

---

## Future Roadmap

### Planned for v1.1.0
- Gutenberg block integration
- FAQ import/export functionality
- Advanced search with highlighting
- FAQ analytics and usage tracking
- Custom CSS editor in admin

### Planned for v1.2.0
- FAQ categories taxonomy
- FAQ tagging system
- Advanced schema markup options
- Integration with popular page builders
- FAQ voting/rating system

### Long-term Goals
- REST API endpoints
- FAQ submission form for frontend
- Advanced caching integration
- Multi-language FAQ support
- FAQ performance analytics

---

*For support, bug reports, or feature requests, please visit our [support page](https://bsolveit.com/support) or [GitHub repository](https://github.com/BSolveIT/plugins).*