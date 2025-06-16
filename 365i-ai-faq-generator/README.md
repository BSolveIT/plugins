# 365i AI FAQ Generator

A WordPress plugin that integrates with Cloudflare AI workers to generate intelligent FAQ content using advanced AI technologies.

## Description

The 365i AI FAQ Generator provides a comprehensive solution for creating, enhancing, and managing FAQ content on WordPress websites. It leverages 6 specialized Cloudflare workers to offer:

- **Question Generation**: AI-powered question creation based on topics
- **Answer Generation**: Intelligent answer creation for questions
- **FAQ Enhancement**: Content improvement and optimization
- **SEO Analysis**: FAQ content analysis for search optimization
- **FAQ Extraction**: Extract FAQ content from URLs
- **Topic Generation**: Generate relevant topics from input text

## Features

### Core Functionality
- 🤖 **AI-Powered Generation**: Create FAQ content using advanced AI workers
- 📊 **SEO Optimization**: Built-in SEO analysis and recommendations
- 🎨 **Multiple Themes**: Default, modern, and minimal display themes
- 📋 **Export Options**: JSON, CSV, and XML export formats
- 🔄 **Auto-Save**: Automatic local storage with version history
- 📱 **Responsive Design**: Mobile-friendly interface and shortcode

### Admin Features
- 🎛️ **Admin Dashboard**: Comprehensive overview with worker status monitoring
- ⚙️ **Worker Configuration**: Manage Cloudflare worker endpoints and rate limits
- 📈 **Usage Analytics**: Track API usage and rate limiting
- 🛠️ **Settings Management**: Configurable defaults and options
- 🔧 **Debug Mode**: Troubleshooting and development tools

### Frontend Integration
- 📄 **Shortcode Support**: `[ai_faq_generator]` with extensive customization options
- 🏗️ **Schema Markup**: Automatic JSON-LD structured data generation
- 💾 **Local Storage**: Browser-based FAQ management and persistence
- 🎯 **Conditional Loading**: Assets loaded only when needed

## Installation

1. Upload the plugin files to `/wp-content/plugins/365i-ai-faq-generator/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your Cloudflare worker endpoints in the admin settings
4. Start generating FAQ content!

## Usage

### Basic Shortcode
```
[ai_faq_generator]
```

### Advanced Shortcode Options
```
[ai_faq_generator 
    topic="WordPress Development" 
    count="8" 
    theme="modern" 
    show_export="true"
    auto_save="true"
]
```

### Shortcode Parameters
- `mode`: Display mode (full, generator-only, display-only)
- `theme`: Visual theme (default, modern, minimal)
- `count`: Number of FAQs to generate (default: 12)
- `topic`: Pre-filled topic for generation
- `show_schema`: Include schema markup (true/false)
- `show_export`: Show export options (true/false)
- `auto_save`: Enable auto-save (true/false)
- `class`: Additional CSS classes
- `id`: Custom container ID

## Cloudflare Workers

The plugin integrates with 6 specialized Cloudflare workers:

1. **Question Generator** - Generate relevant questions from topics
2. **Answer Generator** - Create comprehensive answers for questions
3. **FAQ Enhancer** - Improve and optimize existing FAQ content
4. **SEO Analyzer** - Analyze FAQ content for SEO optimization
5. **FAQ Extractor** - Extract FAQ content from external URLs
6. **Topic Generator** - Generate relevant topics from input text

## Configuration

### Worker Settings
Navigate to **AI FAQ Gen > Workers** to configure:
- Worker URLs and endpoints
- Rate limiting (requests per hour)
- Enable/disable individual workers
- Test worker connections

### General Settings
Navigate to **AI FAQ Gen > Settings** to configure:
- Default FAQ count
- Auto-save interval
- Debug mode
- Export preferences

## Technical Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher (8.0+ recommended)
- **Cloudflare Workers**: Active worker endpoints
- **Browser**: Modern browser with JavaScript enabled

## Development

### File Structure
```
365i-ai-faq-generator/
├── 365i-ai-faq-generator.php          # Main plugin file
├── includes/                          # Core PHP classes
│   ├── class-ai-faq-core.php         # Core functionality
│   ├── class-ai-faq-workers.php      # Worker integration
│   ├── class-ai-faq-admin.php        # Admin interface
│   └── class-ai-faq-frontend.php     # Frontend functionality
├── templates/                         # HTML templates
│   ├── partials/                     # Reusable template parts
│   ├── admin/                        # Admin page templates
│   └── frontend/                     # Frontend templates
├── assets/                           # CSS and JavaScript
│   ├── css/                         # Stylesheets
│   └── js/                          # JavaScript files
├── CHANGELOG.md                      # Version history
└── README.md                         # This file
```

### Hooks and Filters

#### Actions
- `ai_faq_gen_activated` - Fired after plugin activation
- `ai_faq_gen_deactivated` - Fired after plugin deactivation
- `ai_faq_gen_uninstalled` - Fired during plugin uninstall

#### Filters
- `ai_faq_gen_shortcode_output` - Modify shortcode output
- `ai_faq_gen_worker_config` - Customize worker configuration
- `ai_faq_gen_default_options` - Modify default plugin options

## Security

The plugin implements WordPress security best practices:
- ✅ ABSPATH checks on all files
- ✅ Nonce verification for all forms and AJAX requests
- ✅ Capability checks for admin functions
- ✅ Input sanitization and output escaping
- ✅ Rate limiting for API requests
- ✅ Secure data storage and retrieval

## Support

For support and documentation:
- **Website**: [365i.co.uk](https://365i.co.uk)
- **GitHub**: [BSolveIT/plugins](https://github.com/BSolveIT/plugins)
- **Version**: 2.0.0

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history and updates.

---

**Developed by 365i.co.uk** - Intelligent WordPress solutions powered by AI