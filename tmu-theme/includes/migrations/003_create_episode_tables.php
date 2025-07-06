<?php
/**
 * Create Episode/Season Tables Migration
 *
 * @package TMU\Database\Migrations
 * @version 1.0.0
 */

namespace TMU\Database\Migrations;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create Episode/Season Tables
 */
class CreateEpisodeTables {
    
    /**
     * Run the migration
     */
    public function up(): void {
        global $wpdb;
        
        // Require WordPress database upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create TV series seasons table
        $sql = "CREATE TABLE `{$wpdb->prefix}tmu_tv_series_seasons` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `tv_series` bigint(20) UNSIGNED NOT NULL,
            `season_number` int(11) NOT NULL,
            `name` varchar(255) DEFAULT NULL,
            `overview` text DEFAULT NULL,
            `air_date` date DEFAULT NULL,
            `episode_count` int(11) DEFAULT 0,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `poster_path` varchar(255) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `tv_series` (`tv_series`),
            KEY `season_number` (`season_number`),
            KEY `tmdb_id` (`tmdb_id`),
            UNIQUE KEY `unique_tv_series_season` (`tv_series`, `season_number`),
            FOREIGN KEY (`tv_series`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
        dbDelta($sql);
        
        // Create TV series episodes table
        $sql = "CREATE TABLE `{$wpdb->prefix}tmu_tv_series_episodes` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `tv_series` bigint(20) UNSIGNED NOT NULL,
            `season_id` bigint(20) UNSIGNED NOT NULL,
            `episode_number` int(11) NOT NULL,
            `season_number` int(11) NOT NULL,
            `name` varchar(255) DEFAULT NULL,
            `overview` text DEFAULT NULL,
            `air_date` date DEFAULT NULL,
            `runtime` int(11) DEFAULT NULL,
            `still_path` varchar(255) DEFAULT NULL,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `vote_average` decimal(3,1) DEFAULT 0.0,
            `vote_count` int(11) DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `tv_series` (`tv_series`),
            KEY `season_id` (`season_id`),
            KEY `episode_number` (`episode_number`),
            KEY `season_number` (`season_number`),
            KEY `tmdb_id` (`tmdb_id`),
            UNIQUE KEY `unique_tv_series_episode` (`tv_series`, `season_number`, `episode_number`),
            FOREIGN KEY (`tv_series`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY (`season_id`) REFERENCES `{$wpdb->prefix}tmu_tv_series_seasons`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
        dbDelta($sql);
        
        // Create drama seasons table
        $sql = "CREATE TABLE `{$wpdb->prefix}tmu_dramas_seasons` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `drama` bigint(20) UNSIGNED NOT NULL,
            `season_number` int(11) NOT NULL,
            `name` varchar(255) DEFAULT NULL,
            `overview` text DEFAULT NULL,
            `air_date` date DEFAULT NULL,
            `episode_count` int(11) DEFAULT 0,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `poster_path` varchar(255) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `drama` (`drama`),
            KEY `season_number` (`season_number`),
            KEY `tmdb_id` (`tmdb_id`),
            UNIQUE KEY `unique_drama_season` (`drama`, `season_number`),
            FOREIGN KEY (`drama`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
        dbDelta($sql);
        
        // Create drama episodes table
        $sql = "CREATE TABLE `{$wpdb->prefix}tmu_dramas_episodes` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `drama` bigint(20) UNSIGNED NOT NULL,
            `season_id` bigint(20) UNSIGNED NOT NULL,
            `episode_number` int(11) NOT NULL,
            `season_number` int(11) NOT NULL,
            `name` varchar(255) DEFAULT NULL,
            `overview` text DEFAULT NULL,
            `air_date` date DEFAULT NULL,
            `runtime` int(11) DEFAULT NULL,
            `still_path` varchar(255) DEFAULT NULL,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `vote_average` decimal(3,1) DEFAULT 0.0,
            `vote_count` int(11) DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `drama` (`drama`),
            KEY `season_id` (`season_id`),
            KEY `episode_number` (`episode_number`),
            KEY `season_number` (`season_number`),
            KEY `tmdb_id` (`tmdb_id`),
            UNIQUE KEY `unique_drama_episode` (`drama`, `season_number`, `episode_number`),
            FOREIGN KEY (`drama`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY (`season_id`) REFERENCES `{$wpdb->prefix}tmu_dramas_seasons`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
        dbDelta($sql);
        
        // Log migration
        error_log('TMU Migration: Episode/Season tables created successfully');
    }
    
    /**
     * Rollback the migration
     */
    public function down(): void {
        global $wpdb;
        
        // Drop tables in reverse order to handle foreign key dependencies
        $tables = [
            'tmu_dramas_episodes',
            'tmu_dramas_seasons',
            'tmu_tv_series_episodes',
            'tmu_tv_series_seasons'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}{$table}`");
        }
        
        error_log('TMU Migration: Episode/Season tables dropped');
    }
}