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
    $composer_autoload = TMU_THEME_DIR . '/vendor/autoload.php';
    $fallback_autoloader = TMU_INCLUDES_DIR . '/classes/Autoloader.php';
    
    // Try Composer autoloader first (preferred method)
    if (file_exists($composer_autoload)) {
        require_once $composer_autoload;
        
        // Load helper functions
        $helpers = [
            TMU_INCLUDES_DIR . '/helpers/functions.php',
            TMU_INCLUDES_DIR . '/helpers/template-functions.php',
            TMU_INCLUDES_DIR . '/helpers/admin-functions.php'
        ];
        
        foreach ($helpers as $helper) {
            if (file_exists($helper)) {
                require_once $helper;
            }
        }
        
        return;
    }
    
    // Fallback to custom autoloader
    if (file_exists($fallback_autoloader)) {
        require_once $fallback_autoloader;
        
        $autoloader = new TMU\Autoloader();
        $autoloader->register(true); // Prepend to ensure it loads first
        
        // Load helper functions manually when using fallback
        $helpers = [
            TMU_INCLUDES_DIR . '/helpers/functions.php',
            TMU_INCLUDES_DIR . '/helpers/template-functions.php',
            TMU_INCLUDES_DIR . '/helpers/admin-functions.php'
        ];
        
        foreach ($helpers as $helper) {
            if (file_exists($helper)) {
                require_once $helper;
            }
        }
    } else {
        // If no autoloader is available, show error in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            wp_die('TMU Theme Error: No autoloader found. Please run "composer install" or ensure Autoloader.php exists.');
        }
    }
}

/**
 * Check system requirements
 */
function tmu_check_requirements(): bool {
    $requirements = [
        'php_version' => '7.4.0',
        'wp_version' => '5.0',
        'extensions' => ['json', 'curl']
    ];
    
    // Check PHP version
    if (version_compare(PHP_VERSION, $requirements['php_version'], '<')) {
        add_action('admin_notices', function() use ($requirements) {
            echo '<div class="notice notice-error"><p>';
            printf(
                esc_html__('TMU Theme requires PHP %s or higher. Current version: %s', 'tmu'),
                $requirements['php_version'],
                PHP_VERSION
            );
            echo '</p></div>';
        });
        return false;
    }
    
    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, $requirements['wp_version'], '<')) {
        add_action('admin_notices', function() use ($requirements) {
            global $wp_version;
            echo '<div class="notice notice-error"><p>';
            printf(
                esc_html__('TMU Theme requires WordPress %s or higher. Current version: %s', 'tmu'),
                $requirements['wp_version'],
                $wp_version
            );
            echo '</p></div>';
        });
        return false;
    }
    
    // Check required extensions
    foreach ($requirements['extensions'] as $extension) {
        if (!extension_loaded($extension)) {
            add_action('admin_notices', function() use ($extension) {
                echo '<div class="notice notice-error"><p>';
                printf(
                    esc_html__('TMU Theme requires the %s PHP extension.', 'tmu'),
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
 * Initialize error handling
 */
function tmu_init_error_handling(): void {
    // Only in debug mode
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    // Set custom error handler for TMU namespace
    set_error_handler(function($severity, $message, $file, $line) {
        if (strpos($file, 'tmu-theme') === false) {
            return false; // Let WordPress handle non-TMU errors
        }
        
        $error_types = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_NOTICE => 'Notice',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice'
        ];
        
        $type = $error_types[$severity] ?? 'Unknown Error';
        
        error_log(sprintf(
            '[TMU Theme %s] %s in %s on line %d: %s',
            $type,
            $message,
            $file,
            $line,
            $message
        ));
        
        return true;
    });
}

/**
 * Load configuration files
 */
function tmu_load_config(): void {
    $config_files = [
        TMU_INCLUDES_DIR . '/config/constants.php',
        TMU_INCLUDES_DIR . '/config/database.php',
        TMU_INCLUDES_DIR . '/config/assets.php'
    ];
    
    foreach ($config_files as $config_file) {
        if (file_exists($config_file)) {
            require_once $config_file;
        }
    }
}

/**
 * Initialize the theme bootstrap
 */
function tmu_bootstrap(): void {
    // Check system requirements first
    if (!tmu_check_requirements()) {
        return;
    }
    
    // Initialize error handling
    tmu_init_error_handling();
    
    // Load configuration
    tmu_load_config();
    
    // Initialize autoloading
    tmu_init_autoloader();
    
    // Initialize theme core if autoloading is working
    if (class_exists('TMU\\ThemeCore')) {
        TMU\ThemeCore::getInstance();
    } else {
        // Fallback: try to load ThemeCore manually
        $theme_core_file = TMU_INCLUDES_DIR . '/classes/ThemeCore.php';
        if (file_exists($theme_core_file)) {
            require_once $theme_core_file;
            if (class_exists('TMU\\ThemeCore')) {
                TMU\ThemeCore::getInstance();
            }
        }
    }
}

// Bootstrap the theme
tmu_bootstrap();