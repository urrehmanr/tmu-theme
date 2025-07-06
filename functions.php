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
define('TMU_TEMPLATES_DIR', TMU_THEME_DIR . '/templates');

// PHP version check
if (version_compare(PHP_VERSION, '8.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>' . 
             esc_html__('TMU Theme requires PHP 8.0 or higher. You are running PHP ', 'tmu') . 
             PHP_VERSION . '</p></div>';
    });
    return;
}

// Composer autoloader
if (file_exists(TMU_THEME_DIR . '/vendor/autoload.php')) {
    require_once TMU_THEME_DIR . '/vendor/autoload.php';
}

// Theme core initialization
require_once TMU_INCLUDES_DIR . '/classes/ThemeCore.php';

// Initialize theme
if (class_exists('TMU\\ThemeCore')) {
    TMU\ThemeCore::getInstance();
}

// Emergency functions for theme compatibility
if (!function_exists('tmu_get_movie_data')) {
    function tmu_get_movie_data($post_id) {
        return TMU\Utils\DataHelper::getMovieData($post_id);
    }
}

if (!function_exists('tmu_get_tv_data')) {
    function tmu_get_tv_data($post_id) {
        return TMU\Utils\DataHelper::getTVData($post_id);
    }
}

if (!function_exists('tmu_get_drama_data')) {
    function tmu_get_drama_data($post_id) {
        return TMU\Utils\DataHelper::getDramaData($post_id);
    }
}

if (!function_exists('tmu_get_person_data')) {
    function tmu_get_person_data($post_id) {
        return TMU\Utils\DataHelper::getPersonData($post_id);
    }
}