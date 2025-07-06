<?php
/**
 * TMU Theme Test Bootstrap
 *
 * @package TMU\Tests
 * @version 1.0.0
 */

// Load WordPress test environment
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Composer autoloader first
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

// WordPress test functions
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the theme for testing
 */
function _manually_load_theme() {
    // Switch to our theme
    switch_theme('tmu');
    
    // Load theme bootstrap
    require_once dirname(__DIR__) . '/includes/bootstrap.php';
    
    // Initialize theme core
    if (class_exists('TMU\\ThemeCore')) {
        TMU\ThemeCore::getInstance();
    }
}

// Add filter to load our theme
tests_add_filter('muplugins_loaded', '_manually_load_theme');

// Set up WordPress test environment constants
if (!defined('WP_TESTS_DOMAIN')) {
    define('WP_TESTS_DOMAIN', 'example.org');
}

if (!defined('WP_TESTS_EMAIL')) {
    define('WP_TESTS_EMAIL', 'admin@example.org');
}

if (!defined('WP_TESTS_TITLE')) {
    define('WP_TESTS_TITLE', 'Test Blog');
}

if (!defined('WP_PHP_BINARY')) {
    define('WP_PHP_BINARY', 'php');
}

// Set up test database
if (!defined('WP_TESTS_CONFIG_FILE_PATH')) {
    define('WP_TESTS_CONFIG_FILE_PATH', $_tests_dir . '/wp-tests-config.php');
}

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Add custom test utilities
require_once __DIR__ . '/includes/TestCase.php';
require_once __DIR__ . '/includes/FactoryHelper.php';