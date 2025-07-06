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
define('TMU_ASSETS_BUILD_URL', TMU_ASSETS_URL . '/build'); // Webpack output for Tailwind CSS

// Load configuration - Required for theme constants
require_once TMU_INCLUDES_DIR . '/config/constants.php';

// Composer autoloader - Required for PSR-4 namespacing
if (file_exists(TMU_THEME_DIR . '/vendor/autoload.php')) {
    require_once TMU_THEME_DIR . '/vendor/autoload.php';
}

// Theme initialization - Main theme class that manages everything
require_once TMU_INCLUDES_DIR . '/classes/ThemeCore.php';

// Initialize theme - This starts Tailwind CSS asset loading
if (class_exists('TMU\\ThemeCore')) {
    TMU\ThemeCore::getInstance();
}