<?php
/**
 * Bootstrap file for PHPUnit tests
 */

// Set up WordPress test environment
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
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
}

tests_add_filter('muplugins_loaded', '_manually_load_theme');

require $_tests_dir . '/includes/bootstrap.php';

// Additional test utilities
require_once __DIR__ . '/utilities/TestHelper.php';
require_once __DIR__ . '/utilities/TMDBMock.php';
require_once __DIR__ . '/utilities/DatabaseTestCase.php';