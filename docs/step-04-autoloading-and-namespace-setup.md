# Step 04: Autoloading and Namespace Setup

## Purpose
Implement modern PSR-4 autoloading with Composer and establish a clean namespace structure for the TMU theme, ensuring scalable and maintainable code organization following contemporary PHP standards.

## Overview
This step establishes:
1. PSR-4 compliant autoloading with Composer
2. Consistent namespace organization
3. Fallback autoloading for non-Composer environments
4. Dependency management
5. Development tools integration

## Dependencies from Previous Steps
- **[REQUIRED]** `composer.json` [FROM STEP 1] - Basic PHP dependency management
- **[REQUIRED]** `includes/classes/ThemeCore.php` [FROM STEP 1] - Main theme class
- **[REQUIRED]** `includes/config/constants.php` [FROM STEP 1] - Theme constants
- **[REQUIRED]** `functions.php` [FROM STEP 1] - Theme bootstrap
- **[REQUIRED]** Database classes [FROM STEP 3] - Classes to be autoloaded

## Files Created in This Step
- **[UPDATE]** `composer.json` - Enhanced with comprehensive autoloading
- **[CREATE NEW]** `includes/classes/Autoloader.php` - Custom PSR-4 autoloader
- **[CREATE NEW]** `includes/bootstrap.php` - Theme bootstrap with autoloading
- **[CREATE NEW]** `includes/helpers/functions.php` - General helper functions
- **[CREATE NEW]** `includes/helpers/template-functions.php` - Template helper functions
- **[CREATE NEW]** `includes/helpers/admin-functions.php` - Admin helper functions
- **[CREATE NEW]** `phpunit.xml` - PHPUnit configuration
- **[CREATE NEW]** `phpcs.xml` - PHP CodeSniffer configuration

## Tailwind CSS Status
**NOT APPLICABLE** - This step focuses on PHP autoloading and doesn't involve CSS

## Modern PHP Standards Implementation

### Namespace Structure
```
TMU\                           # Root namespace
├── Admin\                     # Admin functionality
│   ├── Settings               # Theme settings
│   ├── MetaBoxes             # Meta box management  
│   ├── AdminColumns          # Custom admin columns
│   └── Welcome               # Welcome screen
├── API\                      # External API integrations
│   ├── TMDB\                 # TMDB API client
│   ├── Client                # Base API client
│   └── Processors\           # Data processors
├── Config\                   # Configuration management
│   ├── ThemeConfig           # Main config
│   └── FieldConfig           # Field configurations
├── Database\                 # Database operations
│   ├── Migration             # Database migrations
│   ├── Schema               # Schema definitions
│   └── QueryBuilder         # Custom queries
├── Fields\                   # Custom fields system
│   ├── FieldManager         # Field management
│   ├── MetaField            # Base field class
│   └── Types\               # Field type classes
├── Frontend\                 # Frontend functionality
│   ├── TemplateLoader       # Template loading
│   ├── AssetManager         # Asset management
│   └── SearchHandler        # Search functionality
├── PostTypes\               # Custom post types
│   ├── PostTypeManager      # Main manager
│   ├── AbstractPostType     # Base class
│   └── Types\               # Individual post types
├── Taxonomies\              # Custom taxonomies
│   ├── TaxonomyManager      # Main manager
│   ├── AbstractTaxonomy     # Base class
│   └── Types\               # Individual taxonomies
├── Utils\                   # Utility classes
│   ├── Logger               # Logging
│   ├── Validator            # Validation
│   └── Helper               # General helpers
└── Migration\               # Migration utilities
    └── SettingsMigrator     # Settings migration
```

## Composer Configuration

### Enhanced `composer.json`
**File Status**: [UPDATE - STEP 4]
**File Path**: `tmu-theme/composer.json`
**Purpose**: Enhanced PHP dependency management with comprehensive autoloading and development tools
**Dependencies**: 
- [EXTENDS] Basic `composer.json` [FROM STEP 1] - Extends existing configuration
- [DEPENDS ON] `includes/classes/` directory structure [FROM STEP 1] - Autoloading targets
- [DEPENDS ON] `includes/helpers/` directory [CREATE NEW - STEP 4] - Helper functions
**Integration**: Used by all PHP classes for PSR-4 autoloading
**Used By**: 
- All PHP classes in `includes/classes/` namespace
- Development tools (PHPUnit, CodeSniffer, PHPStan)
- Helper functions autoloading
**AI Action**: Update existing composer.json with comprehensive autoloading configuration

```json
{
    "name": "tmu/wordpress-theme",
    "description": "Modern Movie & TV Database WordPress Theme with TMDB Integration",
    "type": "wordpress-theme",
    "version": "1.0.0",
    "license": "MIT",
    "authors": [
        {
            "name": "TheMovieUpdates",
            "email": "info@themovieupdates.com",
            "homepage": "https://www.themovieupdates.com/",
            "role": "Developer"
        }
    ],
    "keywords": [
        "wordpress",
        "theme",
        "movie",
        "tv-show",
        "tmdb",
        "entertainment",
        "database"
    ],
    "homepage": "https://github.com/urrehmanr/tmu-theme",
    "support": {
        "issues": "https://github.com/urrehmanr/tmu-theme/issues",
        "docs": "https://www.themovieupdates.com/docs/tmu-theme"
    },
    "require": {
        "php": ">=7.4",
        "ext-json": "*",
        "ext-curl": "*",
        "composer/installers": "^1.0|^2.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.5",
        "squizlabs/php_codesniffer": "^3.6",
        "wp-coding-standards/wpcs": "^2.3",
        "phpstan/phpstan": "^1.8",
        "mockery/mockery": "^1.4",
        "phpmd/phpmd": "^2.12"
    },
    "autoload": {
        "psr-4": {
            "TMU\\": "includes/classes/"
        },
        "files": [
            "includes/helpers/functions.php",
            "includes/helpers/template-functions.php"
        ]
    },
    "autoload-dev": {
        "psr-4": {
            "TMU\\Tests\\": "tests/",
            "TMU\\Fixtures\\": "tests/fixtures/"
        }
    },
    "scripts": {
        "test": [
            "phpunit --configuration phpunit.xml"
        ],
        "test-coverage": [
            "phpunit --configuration phpunit.xml --coverage-html coverage/"
        ],
        "cs-check": [
            "phpcs --standard=WordPress includes/ --extensions=php"
        ],
        "cs-fix": [
            "phpcbf --standard=WordPress includes/ --extensions=php"
        ],
        "analyze": [
            "phpstan analyse includes/ --level=5"
        ],
        "mess-detect": [
            "phpmd includes/ text cleancode,codesize,controversial,design,naming,unusedcode"
        ],
        "quality": [
            "@cs-check",
            "@analyze",
            "@mess-detect"
        ],
        "post-install-cmd": [
            "@php -r \"file_exists('vendor/bin/phpcs') && shell_exec('vendor/bin/phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs');\""
        ],
        "post-update-cmd": [
            "@php -r \"file_exists('vendor/bin/phpcs') && shell_exec('vendor/bin/phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs');\""
        ]
    },
    "config": {
        "allow-plugins": {
            "composer/installers": true
        },
        "optimize-autoloader": true,
        "sort-packages": true
    },
    "extra": {
        "installer-paths": {
            "vendor/{$vendor}/{$name}/": ["type:wordpress-plugin"],
            "vendor/{$vendor}/{$name}/": ["type:wordpress-muplugin"]
        }
    }
}
```

## Autoloader Implementation

### Primary Autoloader (`includes/classes/Autoloader.php`)
**File Status**: [CREATE NEW - STEP 4]
**File Path**: `tmu-theme/includes/classes/Autoloader.php`
**Purpose**: Custom PSR-4 compliant autoloader for fallback when Composer is not available
**Dependencies**: 
- [DEPENDS ON] `includes/config/constants.php` [FROM STEP 1] - Theme constants like TMU_INCLUDES_DIR
- [DEPENDS ON] `includes/classes/` directory structure [FROM STEP 1] - Classes to autoload
- [DEPENDS ON] Namespace structure defined in this step - Target classes
**Integration**: Fallback autoloader when Composer autoloader is not available
**Used By**: 
- `includes/bootstrap.php` [CREATE NEW - STEP 4] - Bootstrap autoloading
- All theme classes when Composer is not installed
**Features**: 
- PSR-4 compliant autoloading
- Namespace registration
- Class aliases for backward compatibility
- Directory mapping for all TMU namespaces
**AI Action**: Create comprehensive autoloader class with all TMU namespaces

```php
<?php
/**
 * TMU Theme Autoloader
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
 * PSR-4 Compliant Autoloader
 */
class Autoloader {
    
    /**
     * Namespace prefix for this autoloader
     *
     * @var string
     */
    private $prefix = 'TMU\\';
    
    /**
     * Base directory for the namespace prefix
     *
     * @var string
     */
    private $base_dir;
    
    /**
     * Registered namespaces and their paths
     *
     * @var array
     */
    private $namespaces = [];
    
    /**
     * Class aliases for backward compatibility
     *
     * @var array
     */
    private $aliases = [];
    
    /**
     * Constructor
     *
     * @param string $base_dir Base directory for the namespace prefix
     */
    public function __construct(string $base_dir = null) {
        $this->base_dir = $base_dir ?: TMU_INCLUDES_DIR . '/classes/';
        $this->setupDefaultNamespaces();
        $this->setupAliases();
    }
    
    /**
     * Register autoloader with SPL
     *
     * @param bool $prepend Whether to prepend the autoloader
     */
    public function register(bool $prepend = false): void {
        spl_autoload_register([$this, 'loadClass'], true, $prepend);
    }
    
    /**
     * Unregister autoloader
     */
    public function unregister(): void {
        spl_autoload_unregister([$this, 'loadClass']);
    }
    
    /**
     * Setup default namespaces
     */
    private function setupDefaultNamespaces(): void {
        $this->namespaces = [
            'TMU\\Admin\\' => $this->base_dir . 'Admin/',
            'TMU\\API\\' => $this->base_dir . 'API/',
            'TMU\\Config\\' => $this->base_dir . 'Config/',
            'TMU\\Database\\' => $this->base_dir . 'Database/',
            'TMU\\Fields\\' => $this->base_dir . 'Fields/',
            'TMU\\Frontend\\' => $this->base_dir . 'Frontend/',
            'TMU\\PostTypes\\' => $this->base_dir . 'PostTypes/',
            'TMU\\Taxonomies\\' => $this->base_dir . 'Taxonomies/',
            'TMU\\Utils\\' => $this->base_dir . 'Utils/',
            'TMU\\Migration\\' => $this->base_dir . 'Migration/',
            'TMU\\' => $this->base_dir,
        ];
    }
    
    /**
     * Setup class aliases for backward compatibility
     */
    private function setupAliases(): void {
        $this->aliases = [
            // Legacy plugin class mappings if needed
            'TMU_Settings' => 'TMU\\Admin\\Settings',
            'TMU_PostTypes' => 'TMU\\PostTypes\\PostTypeManager',
            'TMU_Taxonomies' => 'TMU\\Taxonomies\\TaxonomyManager',
        ];
    }
    
    /**
     * Add a namespace
     *
     * @param string $prefix The namespace prefix
     * @param string $base_dir Base directory for the namespace
     * @param bool $prepend Whether to prepend the namespace
     */
    public function addNamespace(string $prefix, string $base_dir, bool $prepend = false): void {
        // Normalize namespace prefix
        $prefix = trim($prefix, '\\') . '\\';
        
        // Normalize base directory
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';
        
        if ($prepend) {
            $this->namespaces = [$prefix => $base_dir] + $this->namespaces;
        } else {
            $this->namespaces[$prefix] = $base_dir;
        }
    }
    
    /**
     * Load a class file for the given class name
     *
     * @param string $class The fully-qualified class name
     * @return mixed The mapped file name on success, or boolean false on failure
     */
    public function loadClass(string $class) {
        // Check for aliases first
        if (isset($this->aliases[$class])) {
            return class_alias($this->aliases[$class], $class);
        }
        
        // Try to load from registered namespaces
        foreach ($this->namespaces as $prefix => $base_dir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }
            
            // Get the relative class name
            $relative_class = substr($class, $len);
            
            // Replace namespace separators with directory separators
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            
            // If the file exists, require it
            if ($this->requireFile($file)) {
                return $file;
            }
        }
        
        // No file found
        return false;
    }
    
    /**
     * Require a file if it exists
     *
     * @param string $file The file to require
     * @return bool True if the file exists and was included, false otherwise
     */
    private function requireFile(string $file): bool {
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
        return false;
    }
    
    /**
     * Get all registered namespaces
     *
     * @return array
     */
    public function getNamespaces(): array {
        return $this->namespaces;
    }
    
    /**
     * Get all registered aliases
     *
     * @return array
     */
    public function getAliases(): array {
        return $this->aliases;
    }
    
    /**
     * Add a class alias
     *
     * @param string $alias The alias name
     * @param string $original The original class name
     */
    public function addAlias(string $alias, string $original): void {
        $this->aliases[$alias] = $original;
    }
    
    /**
     * Check if a class can be loaded
     *
     * @param string $class The class name to check
     * @return bool
     */
    public function canLoadClass(string $class): bool {
        return $this->loadClass($class) !== false;
    }
}
```

### Bootstrap Integration (`includes/bootstrap.php`)
**File Status**: [CREATE NEW - STEP 4]
**File Path**: `tmu-theme/includes/bootstrap.php`
**Purpose**: Theme bootstrap that initializes autoloading, helper functions, and error handling
**Dependencies**: 
- [DEPENDS ON] `includes/classes/Autoloader.php` [CREATE NEW - STEP 4] - Fallback autoloader
- [DEPENDS ON] `vendor/autoload.php` [AUTO-GENERATED] - Composer autoloader (preferred)
- [DEPENDS ON] `includes/config/constants.php` [FROM STEP 1] - Theme constants
- [DEPENDS ON] Helper files [CREATE NEW - STEP 4] - Helper functions
**Integration**: Core bootstrap file that sets up the entire theme environment
**Used By**: 
- `functions.php` [FROM STEP 1] - Main theme entry point
- `includes/classes/ThemeCore.php` [FROM STEP 1] - Theme initialization
**Features**: 
- Composer autoloader detection and fallback
- Helper functions loading
- Error handling setup
- System requirements checking
**AI Action**: Create bootstrap file that handles all theme initialization

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

// Define theme constants if not already defined
if (!defined('TMU_VERSION')) {
    define('TMU_VERSION', '1.0.0');
}

if (!defined('TMU_THEME_DIR')) {
    define('TMU_THEME_DIR', get_template_directory());
}

if (!defined('TMU_THEME_URL')) {
    define('TMU_THEME_URL', get_template_directory_uri());
}

if (!defined('TMU_INCLUDES_DIR')) {
    define('TMU_INCLUDES_DIR', TMU_THEME_DIR . '/includes');
}

if (!defined('TMU_ASSETS_URL')) {
    define('TMU_ASSETS_URL', TMU_THEME_URL . '/assets');
}

/**
 * Initialize autoloading
 */
function tmu_init_autoloader(): void {
    // Try Composer autoloader first
    if (file_exists(TMU_THEME_DIR . '/vendor/autoload.php')) {
        require_once TMU_THEME_DIR . '/vendor/autoload.php';
        return;
    }
    
    // Fallback to custom autoloader
    require_once TMU_INCLUDES_DIR . '/classes/Autoloader.php';
    
    $autoloader = new TMU\Autoloader();
    $autoloader->register();
    
    // Store autoloader instance globally for access
    $GLOBALS['tmu_autoloader'] = $autoloader;
}

/**
 * Get the autoloader instance
 *
 * @return TMU\Autoloader|null
 */
function tmu_get_autoloader(): ?TMU\Autoloader {
    return $GLOBALS['tmu_autoloader'] ?? null;
}

/**
 * Load helper functions
 */
function tmu_load_helpers(): void {
    $helper_files = [
        TMU_INCLUDES_DIR . '/helpers/functions.php',
        TMU_INCLUDES_DIR . '/helpers/template-functions.php',
        TMU_INCLUDES_DIR . '/helpers/admin-functions.php',
    ];
    
    foreach ($helper_files as $file) {
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

/**
 * Initialize error handling
 */
function tmu_init_error_handling(): void {
    // Set custom error handler for development
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Custom error handler
        set_error_handler(function($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            
            $error_log = "TMU Theme Error: [{$severity}] {$message} in {$file} on line {$line}";
            error_log($error_log);
            
            return true;
        });
    }
}

/**
 * Check system requirements
 */
function tmu_check_requirements(): bool {
    $requirements = [
        'php_version' => '7.4.0',
        'wordpress_version' => '6.0',
        'extensions' => ['json', 'curl', 'gd'],
    ];
    
    // Check PHP version
    if (version_compare(PHP_VERSION, $requirements['php_version'], '<')) {
        add_action('admin_notices', function() use ($requirements) {
            echo '<div class="notice notice-error"><p>';
            echo sprintf(
                __('TMU Theme requires PHP %s or higher. Current version: %s', 'tmu'),
                $requirements['php_version'],
                PHP_VERSION
            );
            echo '</p></div>';
        });
        return false;
    }
    
    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, $requirements['wordpress_version'], '<')) {
        add_action('admin_notices', function() use ($requirements) {
            global $wp_version;
            echo '<div class="notice notice-error"><p>';
            echo sprintf(
                __('TMU Theme requires WordPress %s or higher. Current version: %s', 'tmu'),
                $requirements['wordpress_version'],
                $wp_version
            );
            echo '</p></div>';
        });
        return false;
    }
    
    // Check PHP extensions
    foreach ($requirements['extensions'] as $extension) {
        if (!extension_loaded($extension)) {
            add_action('admin_notices', function() use ($extension) {
                echo '<div class="notice notice-error"><p>';
                echo sprintf(
                    __('TMU Theme requires the %s PHP extension.', 'tmu'),
                    $extension
                );
                echo '</p></div>';
            });
            return false;
        }
    }
    
    return true;
}

/**
 * Initialize theme compatibility
 */
function tmu_init_compatibility(): void {
    // Handle legacy Meta Box plugin compatibility
    if (function_exists('rwmb_meta')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>TMU Theme:</strong> Meta Box plugin detected. The theme will use its own field system but can import existing data.</p>';
            echo '</div>';
        });
    }
    
    // Handle other plugin compatibility
    add_action('plugins_loaded', function() {
        // Rank Math SEO compatibility
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            add_filter('tmu_enable_built_in_seo', '__return_false');
        }
        
        // WP Rocket compatibility
        if (function_exists('rocket_clean_post')) {
            add_action('tmu_post_updated', 'rocket_clean_post');
        }
    });
}

// Initialize everything
tmu_check_requirements();
tmu_init_error_handling();
tmu_init_autoloader();
tmu_load_helpers();
tmu_init_compatibility();
```

## Helper Functions Implementation

### Core Functions (`includes/helpers/functions.php`)
**File Status**: [CREATE NEW - STEP 4]
**File Path**: `tmu-theme/includes/helpers/functions.php`
**Purpose**: Core helper functions used throughout the theme
**Dependencies**: 
- [DEPENDS ON] `TMU\Config\ThemeConfig` [CREATE NEW - STEP 7] - Theme configuration
- [DEPENDS ON] `TMU\Utils\Logger` [CREATE NEW - STEP 11] - Logging functionality
- [DEPENDS ON] WordPress core functions - get_option, sanitize_text_field, etc.
**Integration**: Auto-loaded by bootstrap.php and Composer
**Used By**: 
- Template files - Theme templates
- Admin interfaces - Settings and admin pages
- Other helper functions - Function composition
**Features**: 
- Configuration management
- Logging and debugging
- Caching utilities
- Sanitization and validation helpers
**AI Action**: Create comprehensive helper functions file

```php
<?php
/**
 * TMU Core Helper Functions
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get TMU option with fallback
 *
 * @param string $option_name Option name
 * @param mixed $default Default value
 * @return mixed
 */
function tmu_get_option(string $option_name, $default = null) {
    $config = TMU\Config\ThemeConfig::getInstance();
    $defaults = $config->getDefaultSettings();
    
    $default_value = $defaults[$option_name]['default'] ?? $default;
    return get_option($option_name, $default_value);
}

/**
 * Check if TMU feature is enabled
 *
 * @param string $feature Feature name (movies, tv_series, dramas)
 * @return bool
 */
function tmu_is_feature_enabled(string $feature): bool {
    $config = TMU\Config\ThemeConfig::getInstance();
    return $config->isFeatureEnabled($feature);
}

/**
 * Get TMDB API key
 *
 * @return string
 */
function tmu_get_tmdb_api_key(): string {
    $config = TMU\Config\ThemeConfig::getInstance();
    return $config->getTmdbApiKey();
}

/**
 * Log TMU message
 *
 * @param string $message Message to log
 * @param string $level Log level (info, warning, error)
 */
function tmu_log(string $message, string $level = 'info'): void {
    if (class_exists('TMU\\Utils\\Logger')) {
        TMU\Utils\Logger::getInstance()->log($level, $message);
    } else {
        error_log("TMU [{$level}]: {$message}");
    }
}

/**
 * Sanitize TMU input
 *
 * @param mixed $input Input to sanitize
 * @param string $type Sanitization type
 * @return mixed
 */
function tmu_sanitize($input, string $type = 'text') {
    if (class_exists('TMU\\Utils\\Sanitizer')) {
        return TMU\Utils\Sanitizer::sanitize($input, $type);
    }
    
    // Fallback sanitization
    switch ($type) {
        case 'email':
            return sanitize_email($input);
        case 'url':
            return esc_url_raw($input);
        case 'textarea':
            return sanitize_textarea_field($input);
        case 'html':
            return wp_kses_post($input);
        default:
            return sanitize_text_field($input);
    }
}

/**
 * Validate TMU data
 *
 * @param mixed $data Data to validate
 * @param array $rules Validation rules
 * @return bool|WP_Error
 */
function tmu_validate($data, array $rules) {
    if (class_exists('TMU\\Utils\\Validator')) {
        return TMU\Utils\Validator::validate($data, $rules);
    }
    
    return true; // Fallback
}

/**
 * Get TMU cache
 *
 * @param string $key Cache key
 * @param mixed $default Default value
 * @return mixed
 */
function tmu_get_cache(string $key, $default = null) {
    return get_transient("tmu_{$key}") ?: $default;
}

/**
 * Set TMU cache
 *
 * @param string $key Cache key
 * @param mixed $value Value to cache
 * @param int $expiration Expiration time
 * @return bool
 */
function tmu_set_cache(string $key, $value, int $expiration = null): bool {
    if ($expiration === null) {
        $config = TMU\Config\ThemeConfig::getInstance();
        $expiration = $config->getCacheDuration();
    }
    
    return set_transient("tmu_{$key}", $value, $expiration);
}

/**
 * Delete TMU cache
 *
 * @param string $key Cache key
 * @return bool
 */
function tmu_delete_cache(string $key): bool {
    return delete_transient("tmu_{$key}");
}

/**
 * Get TMU version
 *
 * @return string
 */
function tmu_get_version(): string {
    return TMU_VERSION;
}

/**
 * Check if in development mode
 *
 * @return bool
 */
function tmu_is_development(): bool {
    return defined('WP_DEBUG') && WP_DEBUG;
}
```

### Template Functions (`includes/helpers/template-functions.php`)
**File Status**: [CREATE NEW - STEP 4]
**File Path**: `tmu-theme/includes/helpers/template-functions.php`
**Purpose**: Template-specific helper functions for frontend display
**Dependencies**: 
- [DEPENDS ON] `TMU\Database\DataManager` [CREATE NEW - STEP 3] - Database operations
- [DEPENDS ON] `TMU\Frontend\TemplateLoader` [CREATE NEW - STEP 10] - Template loading
- [DEPENDS ON] WordPress template functions - get_post_meta, get_template_part, etc.
**Integration**: Auto-loaded by bootstrap.php and Composer
**Used By**: 
- Template files - Single, archive, and custom templates
- Widget classes - Custom widgets
- Shortcode handlers - Content display
**Features**: 
- Meta data retrieval
- Template part loading
- Rating display
- Breadcrumb generation
**AI Action**: Create template helper functions file

```php
<?php
/**
 * TMU Template Helper Functions
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get TMU post meta
 *
 * @param int $post_id Post ID
 * @param string $key Meta key
 * @param mixed $default Default value
 * @return mixed
 */
function tmu_get_meta(int $post_id, string $key, $default = '') {
    // Try custom table first
    if (class_exists('TMU\\Database\\DataManager')) {
        $data_manager = TMU\Database\DataManager::getInstance();
        $value = $data_manager->getMeta($post_id, $key);
        
        if ($value !== null) {
            return $value;
        }
    }
    
    // Fallback to WordPress meta
    return get_post_meta($post_id, $key, true) ?: $default;
}

/**
 * Get TMU template part
 *
 * @param string $template Template name
 * @param array $variables Variables to pass to template
 */
function tmu_get_template_part(string $template, array $variables = []): void {
    if (class_exists('TMU\\Frontend\\TemplateLoader')) {
        TMU\Frontend\TemplateLoader::getInstance()->getTemplatePart($template, $variables);
    } else {
        // Fallback
        get_template_part($template);
    }
}

/**
 * Display TMU rating
 *
 * @param int $post_id Post ID
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function tmu_rating(int $post_id, bool $echo = true) {
    $rating_data = tmu_get_meta($post_id, 'rating', ['average' => 0, 'count' => 0]);
    
    $output = sprintf(
        '<div class="tmu-rating" data-rating="%.1f" data-count="%d">',
        $rating_data['average'],
        $rating_data['count']
    );
    
    // Star display
    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= round($rating_data['average']) ? 'filled' : 'empty';
        $output .= "<span class=\"star {$class}\">★</span>";
    }
    
    $output .= sprintf(
        '<span class="rating-text">%.1f (%d reviews)</span>',
        $rating_data['average'],
        $rating_data['count']
    );
    
    $output .= '</div>';
    
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Display TMU breadcrumbs
 *
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function tmu_breadcrumbs(bool $echo = true) {
    $output = '<nav class="tmu-breadcrumbs">';
    $output .= '<a href="' . home_url() . '">' . __('Home', 'tmu') . '</a>';
    
    if (is_singular()) {
        $post_type = get_post_type();
        $post_type_object = get_post_type_object($post_type);
        
        if ($post_type_object && $post_type !== 'post') {
            $output .= ' / <a href="' . get_post_type_archive_link($post_type) . '">';
            $output .= $post_type_object->labels->name;
            $output .= '</a>';
        }
        
        $output .= ' / <span>' . get_the_title() . '</span>';
    }
    
    $output .= '</nav>';
    
    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}
```

### Admin Functions (`includes/helpers/admin-functions.php`)
**File Status**: [CREATE NEW - STEP 4]
**File Path**: `tmu-theme/includes/helpers/admin-functions.php`
**Purpose**: Admin-specific helper functions for backend functionality
**Dependencies**: 
- [DEPENDS ON] WordPress admin functions - add_action, add_filter, etc.
- [DEPENDS ON] TMU admin classes - Settings, MetaBoxes, etc.
**Integration**: Auto-loaded by bootstrap.php and Composer
**Used By**: 
- Admin interface classes - Settings pages, meta boxes
- Admin hooks and filters - WordPress integration
**Features**: 
- Admin utility functions
- Settings helpers
- Meta box utilities
- Admin notice helpers
**AI Action**: Create admin helper functions file

```php
<?php
/**
 * TMU Admin Helper Functions
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add TMU admin notice
 *
 * @param string $message Notice message
 * @param string $type Notice type (success, error, warning, info)
 * @param bool $dismissible Whether notice is dismissible
 */
function tmu_add_admin_notice(string $message, string $type = 'info', bool $dismissible = true): void {
    $class = "notice notice-{$type}";
    if ($dismissible) {
        $class .= ' is-dismissible';
    }
    
    add_action('admin_notices', function() use ($message, $class) {
        echo "<div class=\"{$class}\"><p>{$message}</p></div>";
    });
}

/**
 * Get TMU admin setting
 *
 * @param string $setting Setting name
 * @param mixed $default Default value
 * @return mixed
 */
function tmu_get_admin_setting(string $setting, $default = null) {
    $settings = get_option('tmu_settings', []);
    return $settings[$setting] ?? $default;
}

/**
 * Save TMU admin setting
 *
 * @param string $setting Setting name
 * @param mixed $value Setting value
 * @return bool
 */
function tmu_save_admin_setting(string $setting, $value): bool {
    $settings = get_option('tmu_settings', []);
    $settings[$setting] = $value;
    return update_option('tmu_settings', $settings);
}
```

## Development Tools Configuration

### PHPUnit Configuration (`phpunit.xml`)
**File Status**: [CREATE NEW - STEP 4]
**File Path**: `tmu-theme/phpunit.xml`
**Purpose**: PHPUnit testing framework configuration
**Dependencies**: 
- [DEPENDS ON] `tests/bootstrap.php` [CREATE NEW - STEP 17] - Test bootstrap
- [DEPENDS ON] `includes/classes/` directory - Classes to test
**Integration**: Used by Composer scripts for testing
**Used By**: 
- Development testing - Unit and integration tests
- CI/CD pipelines - Automated testing
**AI Action**: Create PHPUnit configuration file

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
    bootstrap="tests/bootstrap.php"
    backupGlobals="false"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
    processIsolation="false"
    stopOnFailure="false"
    syntaxCheck="false"
    verbose="true"
>
    <testsuites>
        <testsuite name="TMU Theme Test Suite">
            <directory>./tests/</directory>
        </testsuite>
    </testsuites>
    
    <filter>
        <whitelist>
            <directory suffix=".php">./includes/classes/</directory>
        </whitelist>
    </filter>
    
    <logging>
        <log type="coverage-html" target="./coverage" lowUpperBound="35" highLowerBound="70"/>
        <log type="coverage-text" target="php://stdout" showUncoveredFiles="true"/>
    </logging>
</phpunit>
```

### PHPStan Configuration (`phpstan.neon`)
**File Status**: [CREATE NEW - STEP 4]
**File Path**: `tmu-theme/phpstan.neon`
**Purpose**: PHPStan static analysis configuration
**Dependencies**: 
- [DEPENDS ON] `includes/classes/` directory - Classes to analyze
- [DEPENDS ON] PHPStan WordPress stubs - WordPress function definitions
**Integration**: Used by Composer scripts for static analysis
**Used By**: 
- Development quality checks - Static analysis
- CI/CD pipelines - Code quality enforcement
**AI Action**: Create PHPStan configuration file

```yaml
parameters:
    level: 5
    paths:
        - includes/classes
    excludePaths:
        - includes/classes/vendor/*
    ignoreErrors:
        - '#Call to function is_plugin_active\(\) with incorrect case#'
    wordpress:
        stubs: true
```

### PHPCS Configuration (`.phpcs.xml`)
**File Status**: [CREATE NEW - STEP 4]
**File Path**: `tmu-theme/.phpcs.xml`
**Purpose**: PHP CodeSniffer configuration for code standards enforcement
**Dependencies**: 
- [DEPENDS ON] `includes/classes/` directory - Classes to check
- [DEPENDS ON] WordPress Coding Standards - WPCS rules
**Integration**: Used by Composer scripts for code style checking
**Used By**: 
- Development quality checks - Code style enforcement
- CI/CD pipelines - Code standards validation
**AI Action**: Create PHPCS configuration file

```xml
<?xml version="1.0"?>
<ruleset name="TMU Theme Coding Standards">
    <description>TMU Theme coding standards</description>

    <!-- Files to check -->
    <file>includes/</file>
    
    <!-- Exclude vendor directory -->
    <exclude-pattern>vendor/*</exclude-pattern>
    <exclude-pattern>node_modules/*</exclude-pattern>
    
    <!-- Use WordPress coding standards -->
    <rule ref="WordPress">
        <!-- Allow array short syntax -->
        <exclude name="Generic.Arrays.DisallowShortArraySyntax"/>
        <!-- Allow short open tags in templates -->
        <exclude name="Generic.PHP.DisallowShortOpenTag.EchoFound"/>
    </rule>
    
    <!-- Set text domain -->
    <rule ref="WordPress.WP.I18n">
        <properties>
            <property name="text_domain" type="array" value="tmu"/>
        </properties>
    </rule>
    
    <!-- Verify that everything in the global namespace is prefixed -->
    <rule ref="WordPress.NamingConventions.PrefixAllGlobals">
        <properties>
            <property name="prefixes" type="array" value="tmu,TMU"/>
        </properties>
    </rule>
</ruleset>
```

## Testing Framework

### Test Bootstrap (`tests/bootstrap.php`)
```php
<?php
/**
 * TMU Theme Test Bootstrap
 */

// Load WordPress test environment
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

// Set up theme
function _manually_load_theme() {
    switch_theme('tmu');
    require_once dirname(__DIR__) . '/functions.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_theme');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';
```

### Autoloader Test (`tests/AutoloaderTest.php`)
```php
<?php
namespace TMU\Tests;

use PHPUnit\Framework\TestCase;
use TMU\Autoloader;

class AutoloaderTest extends TestCase {
    private $autoloader;
    
    public function setUp(): void {
        $this->autoloader = new Autoloader();
    }
    
    public function testAutoloaderRegistration(): void {
        $this->autoloader->register();
        $this->assertTrue(in_array([$this->autoloader, 'loadClass'], spl_autoload_functions()));
        $this->autoloader->unregister();
    }
    
    public function testClassLoading(): void {
        $this->autoloader->register();
        
        // Test loading a class
        $this->assertTrue(class_exists('TMU\\Config\\ThemeConfig'));
        $this->assertTrue(class_exists('TMU\\Admin\\Settings'));
        
        $this->autoloader->unregister();
    }
    
    public function testNamespaceMapping(): void {
        $namespaces = $this->autoloader->getNamespaces();
        
        $this->assertArrayHasKey('TMU\\Admin\\', $namespaces);
        $this->assertArrayHasKey('TMU\\API\\', $namespaces);
        $this->assertArrayHasKey('TMU\\', $namespaces);
    }
}
```

## Integration with Functions.php

### Updated `functions.php`
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

// Load bootstrap
require_once __DIR__ . '/includes/bootstrap.php';

// Initialize theme
if (class_exists('TMU\\ThemeCore')) {
    TMU\ThemeCore::getInstance();
}
```

## Next Steps

1. **[Step 05: Post Types Registration](./05_post-types-registration.md)** - Register custom post types
2. **[Step 06: Taxonomies Registration](./06_taxonomies-registration.md)** - Register custom taxonomies
3. **[Step 07: Custom Fields System](./07_custom-fields-system.md)** - Implement meta fields system

## Verification Checklist

- [ ] Composer configuration complete
- [ ] PSR-4 autoloading implemented
- [ ] Namespace structure organized
- [ ] Helper functions created
- [ ] Development tools configured
- [ ] Testing framework operational
- [ ] Error handling implemented
- [ ] Compatibility checks working
- [ ] Performance optimizations applied
- [ ] Documentation complete

## AI Implementation Instructions for Step 4

### **Prerequisites Check**
Before implementing Step 4, verify these files exist from previous steps:
- **[REQUIRED]** `composer.json` [FROM STEP 1] - Basic PHP dependency management
- **[REQUIRED]** `includes/classes/ThemeCore.php` [FROM STEP 1] - Main theme class
- **[REQUIRED]** `includes/config/constants.php` [FROM STEP 1] - Theme constants
- **[REQUIRED]** `functions.php` [FROM STEP 1] - Theme bootstrap
- **[REQUIRED]** Database classes [FROM STEP 3] - Classes to be autoloaded

### **Implementation Order for AI Models**

#### **Phase 1: Create Directories** (Required First)
```bash
mkdir -p tmu-theme/includes/helpers
mkdir -p tmu-theme/tests
# Other directories already exist from previous steps
```

#### **Phase 2: Update Composer Configuration** (Critical First)
1. **[UPDATE FIRST]** `composer.json` - Enhanced autoloading configuration
2. **[RUN AFTER UPDATE]** `composer install` - Install dependencies
3. **[RUN AFTER INSTALL]** `composer dump-autoload` - Generate autoloader

#### **Phase 3: Core Autoloading Classes** (Exact Order)
1. **[CREATE FIRST]** `includes/classes/Autoloader.php` - Custom PSR-4 autoloader
2. **[CREATE SECOND]** `includes/bootstrap.php` - Theme bootstrap with autoloading

#### **Phase 4: Helper Functions** (Exact Order)
1. **[CREATE FIRST]** `includes/helpers/functions.php` - Core helper functions
2. **[CREATE SECOND]** `includes/helpers/template-functions.php` - Template helpers
3. **[CREATE THIRD]** `includes/helpers/admin-functions.php` - Admin helpers

#### **Phase 5: Development Tools** (Exact Order)
1. **[CREATE FIRST]** `phpunit.xml` - PHPUnit configuration
2. **[CREATE SECOND]** `phpstan.neon` - PHPStan configuration
3. **[CREATE THIRD]** `.phpcs.xml` - PHPCS configuration

#### **Phase 6: Testing Framework** (Exact Order)
1. **[CREATE FIRST]** `tests/bootstrap.php` - Test bootstrap
2. **[CREATE SECOND]** `tests/AutoloaderTest.php` - Autoloader tests

#### **Phase 7: Integration Updates** (Final)
1. **[UPDATE]** `functions.php` - Include bootstrap
2. **[UPDATE]** `includes/classes/ThemeCore.php` - Use autoloaded classes

### **Key Implementation Notes**
- **Composer Priority**: Always try Composer autoloader first, fallback to custom
- **PSR-4 Compliance**: All classes must follow PSR-4 naming conventions
- **Helper Functions**: Auto-loaded via Composer files directive
- **Development Tools**: Optional but recommended for code quality
- **Testing**: Essential for maintaining code quality

### **Critical Dependencies**
- **PHP Version**: Minimum 7.4 (as specified in composer.json)
- **WordPress Version**: Minimum 6.0 (as specified in bootstrap)
- **PHP Extensions**: json, curl, gd (required for functionality)
- **Composer**: Required for autoloading and dependency management

### **Testing Requirements**
1. **Autoloader Test** - Verify PSR-4 autoloading works
2. **Helper Functions Test** - Verify helper functions load correctly
3. **Namespace Resolution** - Verify all namespaces resolve correctly
4. **Composer Integration** - Verify Composer autoloader works
5. **Development Tools** - Verify all development tools function

### **Integration Points**
- **functions.php** - Includes bootstrap.php to initialize autoloading
- **ThemeCore.php** - Uses autoloaded classes from other steps
- **Future Steps** - All subsequent steps depend on this autoloading
- **Development Workflow** - Composer scripts provide development tools

### **Verification Commands**
```bash
# Install dependencies
composer install

# Run tests
composer test

# Check code style
composer cs-check

# Run static analysis
composer analyze

# Run all quality checks
composer quality
```

### **Common Issues and Solutions**
1. **Autoloader Not Found**: Ensure bootstrap.php is included in functions.php
2. **Class Not Found**: Verify PSR-4 namespace mapping in composer.json
3. **Helper Functions Missing**: Check files array in composer.json autoload section
4. **Development Tools Failing**: Run `composer install` to install dev dependencies

---

This autoloading and namespace setup provides a solid foundation for modern PHP development while maintaining WordPress compatibility and ensuring scalable code organization.

**Step 4 Status**: ✅ READY FOR AI IMPLEMENTATION
**Dependencies**: Steps 1-3 must be completed
**Next Step**: Step 5 - Post Types Registration