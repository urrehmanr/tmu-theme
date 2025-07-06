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

if (!defined('TMU_ASSETS_BUILD_URL')) {
    define('TMU_ASSETS_BUILD_URL', TMU_ASSETS_URL . '/build');
}

/**
 * Initialize autoloading
 */
function tmu_init_autoloader(): void {
    // Try Composer autoloader first
    if (file_exists(TMU_THEME_DIR . '/vendor/autoload.php')) {
        require_once TMU_THEME_DIR . '/vendor/autoload.php';
        
        // Log that Composer autoloader is being used
        if (function_exists('tmu_log')) {
            tmu_log('Using Composer autoloader', 'info');
        }
        return;
    }
    
    // Fallback to custom autoloader
    require_once TMU_INCLUDES_DIR . '/classes/Autoloader.php';
    
    $autoloader = new TMU\Autoloader();
    $autoloader->register();
    
    // Store autoloader instance globally for access
    $GLOBALS['tmu_autoloader'] = $autoloader;
    
    // Log autoloader initialization
    error_log('TMU Theme: Using custom autoloader');
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
        
        // Custom error handler for TMU theme
        set_error_handler(function($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            
            // Only handle TMU theme errors
            if (strpos($file, TMU_THEME_DIR) !== false) {
                $error_log = "TMU Theme Error: [{$severity}] {$message} in {$file} on line {$line}";
                error_log($error_log);
            }
            
            return false; // Let WordPress handle other errors
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
        
        // Yoast SEO compatibility
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            add_filter('tmu_enable_built_in_seo', '__return_false');
        }
        
        // WP Rocket compatibility
        if (function_exists('rocket_clean_post')) {
            add_action('tmu_post_updated', 'rocket_clean_post');
        }
        
        // WP Super Cache compatibility
        if (function_exists('wp_cache_post_change')) {
            add_action('tmu_post_updated', 'wp_cache_post_change');
        }
    });
}

/**
 * Initialize theme constants for paths
 */
function tmu_init_constants(): void {
    // Additional constants
    if (!defined('TMU_LANG_DIR')) {
        define('TMU_LANG_DIR', TMU_THEME_DIR . '/languages');
    }
    
    if (!defined('TMU_TEMPLATES_DIR')) {
        define('TMU_TEMPLATES_DIR', TMU_THEME_DIR . '/templates');
    }
    
    if (!defined('TMU_CONFIG_DIR')) {
        define('TMU_CONFIG_DIR', TMU_INCLUDES_DIR . '/config');
    }
    
    if (!defined('TMU_CLASSES_DIR')) {
        define('TMU_CLASSES_DIR', TMU_INCLUDES_DIR . '/classes');
    }
    
    if (!defined('TMU_HELPERS_DIR')) {
        define('TMU_HELPERS_DIR', TMU_INCLUDES_DIR . '/helpers');
    }
}

/**
 * Initialize theme debugging
 */
function tmu_init_debugging(): void {
    // Enable debugging if WP_DEBUG is on
    if (defined('WP_DEBUG') && WP_DEBUG) {
        // Store debug info in option for admin display
        $debug_info = [
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'theme_version' => TMU_VERSION,
            'autoloader_type' => file_exists(TMU_THEME_DIR . '/vendor/autoload.php') ? 'composer' : 'custom',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'extensions' => get_loaded_extensions(),
        ];
        
        update_option('tmu_debug_info', $debug_info);
    }
}

/**
 * Get theme information
 *
 * @return array
 */
function tmu_get_theme_info(): array {
    $theme = wp_get_theme();
    
    return [
        'name' => $theme->get('Name'),
        'version' => $theme->get('Version'),
        'description' => $theme->get('Description'),
        'author' => $theme->get('Author'),
        'template' => $theme->get('Template'),
        'stylesheet' => $theme->get('Stylesheet'),
        'theme_root' => $theme->get_theme_root(),
        'theme_root_uri' => $theme->get_theme_root_uri(),
    ];
}

// Initialize constants first
tmu_init_constants();

// Check requirements and initialize if valid
if (tmu_check_requirements()) {
    // Initialize error handling
    tmu_init_error_handling();
    
    // Initialize autoloader
    tmu_init_autoloader();
    
    // Load helper functions
    tmu_load_helpers();
    
    // Initialize compatibility
    tmu_init_compatibility();
    
    // Initialize debugging
    tmu_init_debugging();
    
    // Log successful bootstrap
    error_log('TMU Theme: Bootstrap completed successfully');
} else {
    // Log requirements failure
    error_log('TMU Theme: Bootstrap failed - system requirements not met');
}