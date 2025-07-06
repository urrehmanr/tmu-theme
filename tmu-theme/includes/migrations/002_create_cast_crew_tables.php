<?php
/**
 * Create Cast/Crew Tables Migration
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
 * Create Cast/Crew Tables
 */
class CreateCastCrewTables {
    
    /**
     * Run the migration
     */
    public function up(): void {
        global $wpdb;
        
        // Require WordPress database upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create movies cast table
        $sql = Schema::getMoviesCastTableSchema();
        dbDelta($sql);
        
        // Create movies crew table
        $sql = "CREATE TABLE `{$wpdb->prefix}tmu_movies_crew` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `movie` bigint(20) UNSIGNED NOT NULL,
            `person` bigint(20) UNSIGNED NOT NULL,
            `job` varchar(255) DEFAULT NULL,
            `department` varchar(255) DEFAULT NULL,
            `release_year` bigint(20) DEFAULT NULL,
            `order_no` int(11) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `movie` (`movie`),
            KEY `person` (`person`),
            KEY `job` (`job`),
            KEY `department` (`department`),
            KEY `release_year` (`release_year`),
            FOREIGN KEY (`movie`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY (`person`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
        dbDelta($sql);
        
        // Create TV series cast table
        $sql = "CREATE TABLE `{$wpdb->prefix}tmu_tv_series_cast` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `tv_series` bigint(20) UNSIGNED NOT NULL,
            `person` bigint(20) UNSIGNED NOT NULL,
            `job` varchar(255) DEFAULT NULL,
            `character_name` varchar(255) DEFAULT NULL,
            `release_year` bigint(20) DEFAULT NULL,
            `order_no` int(11) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `tv_series` (`tv_series`),
            KEY `person` (`person`),
            KEY `release_year` (`release_year`),
            FOREIGN KEY (`tv_series`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY (`person`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
        dbDelta($sql);
        
        // Create TV series crew table
        $sql = "CREATE TABLE `{$wpdb->prefix}tmu_tv_series_crew` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `tv_series` bigint(20) UNSIGNED NOT NULL,
            `person` bigint(20) UNSIGNED NOT NULL,
            `job` varchar(255) DEFAULT NULL,
            `department` varchar(255) DEFAULT NULL,
            `release_year` bigint(20) DEFAULT NULL,
            `order_no` int(11) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `tv_series` (`tv_series`),
            KEY `person` (`person`),
            KEY `job` (`job`),
            KEY `department` (`department`),
            KEY `release_year` (`release_year`),
            FOREIGN KEY (`tv_series`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY (`person`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
        dbDelta($sql);
        
        // Create dramas cast table
        $sql = "CREATE TABLE `{$wpdb->prefix}tmu_dramas_cast` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `drama` bigint(20) UNSIGNED NOT NULL,
            `person` bigint(20) UNSIGNED NOT NULL,
            `job` varchar(255) DEFAULT NULL,
            `character_name` varchar(255) DEFAULT NULL,
            `release_year` bigint(20) DEFAULT NULL,
            `order_no` int(11) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `drama` (`drama`),
            KEY `person` (`person`),
            KEY `release_year` (`release_year`),
            FOREIGN KEY (`drama`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY (`person`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
        dbDelta($sql);
        
        // Create dramas crew table
        $sql = "CREATE TABLE `{$wpdb->prefix}tmu_dramas_crew` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `drama` bigint(20) UNSIGNED NOT NULL,
            `person` bigint(20) UNSIGNED NOT NULL,
            `job` varchar(255) DEFAULT NULL,
            `department` varchar(255) DEFAULT NULL,
            `release_year` bigint(20) DEFAULT NULL,
            `order_no` int(11) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `drama` (`drama`),
            KEY `person` (`person`),
            KEY `job` (`job`),
            KEY `department` (`department`),
            KEY `release_year` (`release_year`),
            FOREIGN KEY (`drama`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY (`person`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
        dbDelta($sql);
        
        // Log migration
        error_log('TMU Migration: Cast/Crew tables created successfully');
    }
    
    /**
     * Rollback the migration
     */
    public function down(): void {
        global $wpdb;
        
        // Drop tables in reverse order
        $tables = [
            'tmu_dramas_crew',
            'tmu_dramas_cast',
            'tmu_tv_series_crew',
            'tmu_tv_series_cast',
            'tmu_movies_crew',
            'tmu_movies_cast'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}{$table}`");
        }
        
        error_log('TMU Migration: Cast/Crew tables dropped');
    }
}