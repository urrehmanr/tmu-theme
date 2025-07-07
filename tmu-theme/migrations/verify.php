<?php
/**
 * Database Migration Verification Script
 * 
 * Verify that database migrations completed successfully.
 * 
 * @package TMU\Migrations
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WP_CLI') && !defined('ABSPATH')) {
    exit;
}

echo "Verifying TMU Theme database migrations...\n";

global $wpdb;

$errors = [];

// Check if movies table exists
$movies_table = $wpdb->prefix . 'tmu_movies';
if ($wpdb->get_var("SHOW TABLES LIKE '{$movies_table}'") != $movies_table) {
    $errors[] = "Movies table does not exist";
}

// Check theme version
$theme_version = get_option('tmu_theme_version');
if ($theme_version !== '2.0.0') {
    $errors[] = "Theme version not updated correctly. Expected: 2.0.0, Got: {$theme_version}";
}

// Check migration version
$migration_version = get_option('tmu_theme_migration_version');
if ($migration_version !== '2.0.0') {
    $errors[] = "Migration version not set correctly. Expected: 2.0.0, Got: {$migration_version}";
}

if (empty($errors)) {
    echo "✅ All database migrations verified successfully!\n";
    exit(0);
} else {
    echo "❌ Migration verification failed:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    exit(1);
}