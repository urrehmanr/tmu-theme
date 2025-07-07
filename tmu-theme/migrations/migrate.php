<?php
/**
 * Database Migration Script
 * 
 * Run database migrations for TMU Theme.
 * 
 * @package TMU\Migrations
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WP_CLI') && !defined('ABSPATH')) {
    exit;
}

echo "Running TMU Theme database migrations...\n";

// Example migration - create custom tables if they don't exist
global $wpdb;

// Movies table
$movies_table = $wpdb->prefix . 'tmu_movies';
if ($wpdb->get_var("SHOW TABLES LIKE '{$movies_table}'") != $movies_table) {
    $sql = "CREATE TABLE {$movies_table} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        tmdb_id int(11) DEFAULT NULL,
        title varchar(255) DEFAULT NULL,
        overview text DEFAULT NULL,
        release_date date DEFAULT NULL,
        runtime int(11) DEFAULT NULL,
        vote_average decimal(3,1) DEFAULT NULL,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY post_id (post_id),
        UNIQUE KEY tmdb_id (tmdb_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    echo "Created movies table\n";
}

// Update theme version
update_option('tmu_theme_version', '2.0.0');
update_option('tmu_theme_migration_version', '2.0.0');

echo "Database migrations completed successfully!\n";