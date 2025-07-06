<?php
/**
 * Create Core Tables Migration
 *
 * @package TMU\Database\Migrations
 * @version 1.0.0
 */

namespace TMU\Database\Migrations;

use TMU\Database\Schema;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create Core Tables
 */
class CreateCoreTables {
    
    /**
     * Run the migration
     */
    public function up(): void {
        global $wpdb;
        
        // Require WordPress database upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create movies table
        $sql = Schema::getMoviesTableSchema();
        dbDelta($sql);
        
        // Create people table
        $sql = Schema::getPeopleTableSchema();
        dbDelta($sql);
        
        // Create TV series table
        $sql = Schema::getTVSeriesTableSchema();
        dbDelta($sql);
        
        // Create dramas table
        $sql = Schema::getDramasTableSchema();
        dbDelta($sql);
        
        // Create videos table
        $sql = "CREATE TABLE `{$wpdb->prefix}tmu_videos` (
            `ID` bigint(20) UNSIGNED NOT NULL,
            `video_data` text DEFAULT NULL,
            `post_id` bigint(20) UNSIGNED NOT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `post_id` (`post_id`),
            FOREIGN KEY (`ID`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY (`post_id`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
        dbDelta($sql);
        
        // Create SEO options table
        $sql = Schema::getSEOOptionsTableSchema();
        dbDelta($sql);
        
        // Log migration
        error_log('TMU Migration: Core tables created successfully');
    }
    
    /**
     * Rollback the migration
     */
    public function down(): void {
        global $wpdb;
        
        // Drop tables in reverse order to handle foreign key dependencies
        $tables = [
            'tmu_videos',
            'tmu_seo_options',
            'tmu_dramas',
            'tmu_tv_series',
            'tmu_people',
            'tmu_movies'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}{$table}`");
        }
        
        error_log('TMU Migration: Core tables dropped');
    }
}