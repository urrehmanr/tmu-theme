# Step 01: Project Setup and Structure

## Purpose
Establish the foundational directory structure and development environment for the TMU theme, ensuring a clean, maintainable, and WordPress-standards-compliant architecture.

## Overview
This step involves creating the theme's directory structure, setting up the development environment, and establishing the foundation for a modern, object-oriented WordPress theme that will replace the TMU plugin functionality.

## Directory Structure

### Root Theme Structure
```
tmu-theme/
├── style.css                  # Theme identification file
├── index.php                  # Fallback template
├── functions.php              # Theme bootstrap
├── composer.json              # Dependency management
├── composer.lock              # Dependency lock file
├── README.md                  # Theme documentation
├── .gitignore                 # Git ignore rules
├── screenshot.png             # Theme screenshot
├── rtl.css                    # RTL support (if needed)
├── assets/                    # Static assets
│   ├── css/
│   │   ├── admin.css         # Admin interface styles
│   │   ├── frontend.css      # Frontend styles
│   │   └── components/       # Component-specific styles
│   ├── js/
│   │   ├── admin.js          # Admin functionality
│   │   ├── frontend.js       # Frontend functionality
│   │   └── modules/          # JavaScript modules
│   ├── images/
│   │   ├── icons/            # SVG icons
│   │   └── placeholders/     # Placeholder images
│   └── fonts/                # Custom fonts (if any)
├── templates/                 # Template files
│   ├── archive/              # Archive templates
│   ├── single/               # Single post templates
│   ├── parts/                # Template parts
│   └── search/               # Search templates
├── includes/                  # Core functionality
│   ├── classes/              # Class files
│   │   ├── Admin/           # Admin-related classes
│   │   ├── API/             # API integration classes
│   │   ├── Database/        # Database interaction classes
│   │   ├── Frontend/        # Frontend classes
│   │   ├── PostTypes/       # Post type classes
│   │   ├── Taxonomies/      # Taxonomy classes
│   │   ├── Fields/          # Custom fields classes
│   │   └── Utils/           # Utility classes
│   ├── admin/               # Admin functionality
│   ├── api/                 # API endpoints
│   ├── migrations/          # Database migrations
│   ├── config/              # Configuration files
│   └── helpers/             # Helper functions
├── languages/               # Translation files
└── vendor/                  # Composer dependencies
```

### Detailed Class Structure
```
includes/classes/
├── Admin/
│   ├── AdminInterface.php    # Admin UI management
│   ├── MetaBoxes.php        # Meta box registration
│   ├── AdminColumns.php     # Custom admin columns
│   ├── BulkActions.php      # Bulk action handlers
│   └── Settings.php         # Theme settings page
├── API/
│   ├── TMDBClient.php       # TMDB API client
│   ├── DataProcessor.php    # API data processing
│   ├── ImageManager.php     # Image handling
│   └── CreditProcessor.php  # Cast/crew processing
├── Database/
│   ├── Migration.php        # Database migration handler
│   ├── Schema.php           # Database schema definitions
│   ├── QueryBuilder.php     # Custom query builder
│   └── DataManager.php      # Data management operations
├── Frontend/
│   ├── TemplateLoader.php   # Template loading logic
│   ├── AssetManager.php     # Asset enqueueing
│   ├── SearchHandler.php    # Search functionality
│   └── AjaxHandler.php      # AJAX request handling
├── PostTypes/
│   ├── PostTypeManager.php  # Post type registration manager
│   ├── Movie.php            # Movie post type
│   ├── TVShow.php           # TV show post type
│   ├── Drama.php            # Drama post type
│   ├── People.php           # People post type
│   ├── Season.php           # Season post type
│   ├── Episode.php          # Episode post type
│   └── Video.php            # Video post type
├── Taxonomies/
│   ├── TaxonomyManager.php  # Taxonomy registration manager
│   ├── Genre.php            # Genre taxonomy
│   ├── Country.php          # Country taxonomy
│   ├── Language.php         # Language taxonomy
│   └── Network.php          # Network taxonomy
├── Fields/
│   ├── FieldManager.php     # Custom fields manager
│   ├── MetaField.php        # Base meta field class
│   ├── MovieFields.php      # Movie-specific fields
│   ├── TVShowFields.php     # TV show-specific fields
│   └── PeopleFields.php     # People-specific fields
├── Utils/
│   ├── Logger.php           # Logging functionality
│   ├── Validator.php        # Data validation
│   ├── Sanitizer.php        # Data sanitization
│   └── Helper.php           # General helper functions
└── ThemeCore.php            # Main theme class
```

## Core Files Setup

### 1. Theme Identification (`style.css`)
```css
/*
Theme Name: TMU
Description: Modern Movie & TV Database Theme - A comprehensive entertainment content management system with TMDB integration
Version: 1.0.0
Author: TheMovieUpdates
Author URI: https://www.themovieupdates.com
Text Domain: tmu
Domain Path: /languages
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
License: MIT
License URI: https://opensource.org/licenses/MIT
Tags: movies, tv-shows, entertainment, database, tmdb

This theme replaces the TMU plugin with modern WordPress theme architecture.
*/

/* Main theme styles will be loaded via functions.php */
```

### 2. Theme Bootstrap (`functions.php`)
```php
<?php
/**
 * TMU Theme Bootstrap
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Theme constants
define('TMU_VERSION', '1.0.0');
define('TMU_THEME_DIR', get_template_directory());
define('TMU_THEME_URL', get_template_directory_uri());
define('TMU_INCLUDES_DIR', TMU_THEME_DIR . '/includes');
define('TMU_ASSETS_URL', TMU_THEME_URL . '/assets');

// Composer autoloader
if (file_exists(TMU_THEME_DIR . '/vendor/autoload.php')) {
    require_once TMU_THEME_DIR . '/vendor/autoload.php';
}

// Theme initialization
require_once TMU_INCLUDES_DIR . '/classes/ThemeCore.php';

// Initialize theme
if (class_exists('TMU\\ThemeCore')) {
    TMU\\ThemeCore::getInstance();
}
```

### 3. Composer Configuration (`composer.json`)
```json
{
    "name": "tmu/wordpress-theme",
    "description": "Modern Movie & TV Database WordPress Theme",
    "type": "wordpress-theme",
    "version": "1.0.0",
    "license": "MIT",
    "authors": [
        {
            "name": "Your Name",
            "email": "your.email@example.com"
        }
    ],
    "require": {
        "php": ">=7.4",
        "composer/installers": "^1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.0",
        "squizlabs/php_codesniffer": "^3.6",
        "wp-coding-standards/wpcs": "^2.3"
    },
    "autoload": {
        "psr-4": {
            "TMU\\": "includes/classes/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "TMU\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "cs-check": "phpcs --standard=WordPress includes/",
        "cs-fix": "phpcbf --standard=WordPress includes/"
    },
    "config": {
        "allow-plugins": {
            "composer/installers": true
        }
    }
}
```

### 4. Git Configuration (`.gitignore`)
```gitignore
# WordPress
wp-config.php
wp-content/uploads/
wp-content/cache/
wp-content/backup*
wp-content/advanced-cache.php
wp-content/wp-cache-config.php

# Theme specific
node_modules/
vendor/
.env
.env.local
.env.development.local
.env.test.local
.env.production.local

# Logs
*.log
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# Build artifacts
/build/
/dist/
assets/css/*.min.css
assets/js/*.min.js

# IDE
.vscode/
.idea/
*.swp
*.swo
*~

# OS
.DS_Store
Thumbs.db

# Composer
composer.lock (remove this if you want to commit the lock file)
```

## Development Environment Setup

### 1. WordPress Local Development
```bash
# Recommended local development setup
# Using Local by Flywheel, XAMPP, or similar

# Theme directory structure
wp-content/
└── themes/
    └── tmu/
        ├── (theme files as outlined above)
```

### 2. Composer Installation
```bash
# Navigate to theme directory
cd wp-content/themes/tmu

# Install dependencies
composer install

# For development
composer install --dev
```

### 3. Development Tools Setup
```bash
# Install PHP CodeSniffer for WordPress
composer global require "squizlabs/php_codesniffer=*"
composer global require wp-coding-standards/wpcs

# Configure PHPCS
phpcs --config-set installed_paths ~/.composer/vendor/wp-coding-standards/wpcs
phpcs --config-set default_standard WordPress
```

## Theme Core Class Foundation

### Main Theme Class (`includes/classes/ThemeCore.php`)
```php
<?php
/**
 * TMU Theme Core Class
 *
 * @package TMU
 * @version 1.0.0
 */

namespace TMU;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Theme Core Class
 */
class ThemeCore {
    
    /**
     * Theme instance
     *
     * @var ThemeCore
     */
    private static $instance = null;
    
    /**
     * Theme version
     *
     * @var string
     */
    private $version = TMU_VERSION;
    
    /**
     * Get theme instance
     *
     * @return ThemeCore
     */
    public static function getInstance(): ThemeCore {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->initHooks();
        $this->loadDependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void {
        add_action('init', [$this, 'initTheme']);
        add_action('after_setup_theme', [$this, 'themeSetup']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }
    
    /**
     * Load required dependencies
     */
    private function loadDependencies(): void {
        // Load configuration
        require_once TMU_INCLUDES_DIR . '/config/config.php';
        
        // Load managers
        require_once TMU_INCLUDES_DIR . '/classes/Database/Migration.php';
        require_once TMU_INCLUDES_DIR . '/classes/PostTypes/PostTypeManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Taxonomies/TaxonomyManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Fields/FieldManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Admin/AdminInterface.php';
        require_once TMU_INCLUDES_DIR . '/classes/API/TMDBClient.php';
        require_once TMU_INCLUDES_DIR . '/classes/Frontend/TemplateLoader.php';
    }
    
    /**
     * Initialize theme functionality
     */
    public function initTheme(): void {
        // Initialize managers
        Database\Migration::getInstance();
        PostTypes\PostTypeManager::getInstance();
        Taxonomies\TaxonomyManager::getInstance();
        Fields\FieldManager::getInstance();
        Admin\AdminInterface::getInstance();
        API\TMDBClient::getInstance();
        Frontend\TemplateLoader::getInstance();
    }
    
    /**
     * Theme setup
     */
    public function themeSetup(): void {
        // Add theme support
        add_theme_support('post-thumbnails');
        add_theme_support('title-tag');
        add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
        add_theme_support('customize-selective-refresh-widgets');
        
        // Set image sizes
        add_image_size('tmu-thumbnail', 300, 400, true);
        add_image_size('tmu-medium', 600, 800, true);
        add_image_size('tmu-large', 1200, 1600, true);
        
        // Load text domain
        load_theme_textdomain('tmu', TMU_THEME_DIR . '/languages');
        
        // Register nav menus
        register_nav_menus([
            'primary' => __('Primary Menu', 'tmu'),
            'footer' => __('Footer Menu', 'tmu'),
        ]);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueueAssets(): void {
        // Styles
        wp_enqueue_style('tmu-frontend', TMU_ASSETS_URL . '/css/frontend.css', [], $this->version);
        
        // Scripts
        wp_enqueue_script('tmu-frontend', TMU_ASSETS_URL . '/js/frontend.js', ['jquery'], $this->version, true);
        
        // Localize scripts
        wp_localize_script('tmu-frontend', 'tmu_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_ajax_nonce'),
        ]);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueueAdminAssets(): void {
        // Styles
        wp_enqueue_style('tmu-admin', TMU_ASSETS_URL . '/css/admin.css', [], $this->version);
        
        // Scripts
        wp_enqueue_script('tmu-admin', TMU_ASSETS_URL . '/js/admin.js', ['jquery'], $this->version, true);
        
        // Localize admin scripts
        wp_localize_script('tmu-admin', 'tmu_admin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_admin_nonce'),
        ]);
    }
    
    /**
     * Get theme version
     *
     * @return string
     */
    public function getVersion(): string {
        return $this->version;
    }
}
```

## Integration with Existing Plugin Data

### Database Table Preservation
The theme must be designed to use the existing database tables created by the plugin:

1. **No Schema Changes**: The theme should work with existing table structures
2. **Data Migration**: Provide migration scripts for fresh installations
3. **Backward Compatibility**: Ensure existing data remains accessible
4. **Performance**: Optimize queries while maintaining data integrity

### Plugin Feature Mapping
Each plugin feature must be mapped to theme components:

- **Post Types**: Plugin CPT registration → Theme CPT classes
- **Taxonomies**: Plugin taxonomy registration → Theme taxonomy classes
- **Meta Fields**: Plugin Meta Box fields → Theme custom field classes
- **TMDB Integration**: Plugin API calls → Theme API service classes
- **Admin Interface**: Plugin admin pages → Theme admin classes
- **Frontend Templates**: Plugin template files → Theme template system

## Best Practices Implementation

### 1. WordPress Coding Standards
- Follow WordPress PHP, CSS, and JavaScript coding standards
- Use proper WordPress hooks and filters
- Implement proper sanitization and validation
- Use WordPress coding conventions for file naming

### 2. Security Considerations
- Sanitize all user inputs
- Validate data before processing
- Use nonces for form submissions
- Implement proper capability checks
- Escape output data appropriately

### 3. Performance Optimization
- Lazy load assets when possible
- Minimize database queries
- Use WordPress caching mechanisms
- Optimize image loading
- Implement proper asset minification

### 4. Accessibility
- Follow WCAG guidelines
- Implement proper ARIA attributes
- Ensure keyboard navigation
- Provide alternative text for images
- Use semantic HTML structure

## Testing Strategy

### 1. Unit Testing
- Set up PHPUnit for testing
- Test all class methods
- Mock external dependencies
- Test edge cases and error conditions

### 2. Integration Testing
- Test WordPress hooks integration
- Test database operations
- Test API integrations
- Test admin interface functionality

### 3. Manual Testing
- Test theme activation/deactivation
- Test data migration
- Test frontend functionality
- Test admin interface
- Test responsive design

## Documentation Requirements

### 1. Code Documentation
- Document all classes and methods
- Use PHPDoc standards
- Include usage examples
- Document hooks and filters

### 2. User Documentation
- Installation guide
- Configuration instructions
- Feature explanations
- Troubleshooting guide

## Next Steps

After completing this step, proceed to:
1. **[Step 02: Theme Initialization](./02_theme-initialization.md)** - Set up theme activation and initialization
2. **[Step 03: Database Migration System](./03_database-migration-system.md)** - Create database migration system
3. **[Step 04: Autoloading and Namespace Setup](./04_autoloading-and-namespace-setup.md)** - Configure autoloading and namespaces

## Verification Checklist

- [ ] Theme directory structure created
- [ ] Core theme files implemented
- [ ] Composer configuration set up
- [ ] Development environment configured
- [ ] Theme core class implemented
- [ ] Git repository initialized
- [ ] Documentation structure established
- [ ] Testing framework prepared

---

This step establishes the foundation for a modern, maintainable WordPress theme that will successfully replace the TMU plugin while preserving all existing functionality.