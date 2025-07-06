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

// Load bootstrap - This handles all autoloading and initialization
require_once __DIR__ . '/includes/bootstrap.php';

// Initialize theme - This starts Tailwind CSS asset loading
if (class_exists('TMU\\ThemeCore')) {
    TMU\ThemeCore::getInstance();
}