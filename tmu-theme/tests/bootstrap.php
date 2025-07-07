<?php
/**
 * Bootstrap file for PHPUnit tests
 */

// Set up WordPress test environment
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Composer autoloader first
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_theme() {
    switch_theme('tmu-theme');
    
    // Load theme files
    require_once get_template_directory() . '/functions.php';
    
    // Initialize theme components
    if (class_exists('TMU\ThemeInitializer')) {
        $theme = new TMU\ThemeInitializer();
        $theme->init();
    }
    
    // Load theme bootstrap if it exists
    $bootstrap_file = get_template_directory() . '/includes/bootstrap.php';
    if (file_exists($bootstrap_file)) {
        require_once $bootstrap_file;
    }
    
    // Initialize theme core
    if (class_exists('TMU\\ThemeCore')) {
        TMU\ThemeCore::getInstance();
    }
}

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

require $_tests_dir . '/includes/bootstrap.php';

// Additional test utilities
require_once __DIR__ . '/utilities/TestHelper.php';
require_once __DIR__ . '/utilities/TMDBMock.php';
require_once __DIR__ . '/utilities/DatabaseTestCase.php';

// Legacy support for existing includes
if (file_exists(__DIR__ . '/includes/TestCase.php')) {
    require_once __DIR__ . '/includes/TestCase.php';
}

if (file_exists(__DIR__ . '/includes/FactoryHelper.php')) {
    require_once __DIR__ . '/includes/FactoryHelper.php';
}