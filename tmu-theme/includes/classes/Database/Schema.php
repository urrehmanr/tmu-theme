<?php
/**
 * TMU Database Schema Definitions
 *
 * @package TMU\Database
 * @version 1.0.0
 */

namespace TMU\Database;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Schema Definitions
 */
class Schema {
    
    /**
     * Get movies table schema
     *
     * @return string
     */
    public static function getMoviesTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_movies` (
            `ID` bigint(20) UNSIGNED NOT NULL,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `release_date` text DEFAULT NULL,
            `release_timestamp` bigint(20) DEFAULT NULL,
            `original_title` text DEFAULT NULL,
            `tagline` text DEFAULT NULL,
            `production_house` text DEFAULT NULL,
            `streaming_platforms` text DEFAULT NULL,
            `runtime` bigint(20) DEFAULT NULL,
            `certification` text DEFAULT NULL,
            `revenue` bigint(20) DEFAULT NULL,
            `budget` bigint(20) DEFAULT NULL,
            `star_cast` text DEFAULT NULL,
            `credits` longtext DEFAULT NULL,
            `credits_temp` longtext DEFAULT NULL,
            `videos` text DEFAULT NULL,
            `images` text DEFAULT NULL,
            `average_rating` DECIMAL(10,1) DEFAULT 0,
            `vote_count` bigint(20) DEFAULT 0,
            `popularity` DECIMAL(10,1) DEFAULT 0,
            `total_average_rating` DECIMAL(10,1) DEFAULT 0,
            `total_vote_count` bigint(20) NOT NULL DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `tmdb_id` (`tmdb_id`),
            KEY `release_timestamp` (`release_timestamp`),
            KEY `popularity` (`popularity`),
            FOREIGN KEY (`ID`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get people table schema
     *
     * @return string
     */
    public static function getPeopleTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_people` (
            `ID` bigint(20) UNSIGNED NOT NULL,
            `name` text DEFAULT NULL,
            `date_of_birth` text DEFAULT NULL,
            `gender` text DEFAULT NULL,
            `nick_name` text DEFAULT NULL,
            `marital_status` text DEFAULT NULL,
            `basic` text DEFAULT NULL,
            `videos` text DEFAULT NULL,
            `photos` text DEFAULT NULL,
            `profession` text DEFAULT NULL,
            `net_worth` bigint(20) DEFAULT NULL,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `birthplace` text DEFAULT NULL,
            `dead_on` text DEFAULT NULL,
            `social_media_account` text DEFAULT NULL,
            `no_movies` bigint(20) DEFAULT NULL,
            `no_tv_series` bigint(20) DEFAULT NULL,
            `no_dramas` bigint(20) DEFAULT NULL,
            `known_for` text DEFAULT NULL,
            `popularity` DECIMAL(10,1) DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `tmdb_id` (`tmdb_id`),
            KEY `date_of_birth` (`date_of_birth`(10)),
            KEY `popularity` (`popularity`),
            FOREIGN KEY (`ID`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get TV series table schema
     *
     * @return string
     */
    public static function getTVSeriesTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_tv_series` (
            `ID` bigint(20) UNSIGNED NOT NULL,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `release_date` text DEFAULT NULL,
            `release_timestamp` bigint(20) DEFAULT NULL,
            `original_title` text DEFAULT NULL,
            `finished` text DEFAULT NULL,
            `tagline` text DEFAULT NULL,
            `production_house` text DEFAULT NULL,
            `streaming_platforms` text DEFAULT NULL,
            `schedule_time` text DEFAULT NULL,
            `runtime` bigint(20) DEFAULT NULL,
            `certification` text DEFAULT NULL,
            `revenue` bigint(20) DEFAULT NULL,
            `budget` bigint(20) DEFAULT NULL,
            `star_cast` text DEFAULT NULL,
            `credits` longtext DEFAULT NULL,
            `credits_temp` longtext DEFAULT NULL,
            `videos` text DEFAULT NULL,
            `images` text DEFAULT NULL,
            `seasons` text DEFAULT NULL,
            `last_season` bigint(20) DEFAULT NULL,
            `last_episode` bigint(20) DEFAULT NULL,
            `average_rating` DECIMAL(10,1) DEFAULT 0,
            `vote_count` bigint(20) DEFAULT 0,
            `popularity` DECIMAL(10,1) DEFAULT 0,
            `where_to_watch` text DEFAULT NULL,
            `total_average_rating` DECIMAL(10,1) DEFAULT 0,
            `total_vote_count` bigint(20) NOT NULL DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `tmdb_id` (`tmdb_id`),
            KEY `release_timestamp` (`release_timestamp`),
            KEY `popularity` (`popularity`),
            FOREIGN KEY (`ID`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get dramas table schema
     *
     * @return string
     */
    public static function getDramasTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_dramas` (
            `ID` bigint(20) UNSIGNED NOT NULL,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `release_date` text DEFAULT NULL,
            `release_timestamp` bigint(20) DEFAULT NULL,
            `original_title` text DEFAULT NULL,
            `finished` text DEFAULT NULL,
            `tagline` text DEFAULT NULL,
            `seo_genre` BIGINT(20) NULL DEFAULT NULL,
            `production_house` text DEFAULT NULL,
            `streaming_platforms` text DEFAULT NULL,
            `schedule_day` text DEFAULT NULL,
            `schedule_time` text DEFAULT NULL,
            `schedule_timestamp` bigint(20) DEFAULT NULL,
            `runtime` bigint(20) DEFAULT NULL,
            `certification` text DEFAULT NULL,
            `star_cast` text DEFAULT NULL,
            `credits` longtext DEFAULT NULL,
            `credits_temp` longtext DEFAULT NULL,
            `videos` text DEFAULT NULL,
            `images` text DEFAULT NULL,
            `average_rating` DECIMAL(10,1) DEFAULT 0,
            `vote_count` bigint(20) DEFAULT 0,
            `popularity` DECIMAL(10,1) DEFAULT 0,
            `where_to_watch` text DEFAULT NULL,
            `total_average_rating` DECIMAL(10,1) DEFAULT 0,
            `total_vote_count` bigint(20) NOT NULL DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `tmdb_id` (`tmdb_id`),
            KEY `release_timestamp` (`release_timestamp`),
            KEY `schedule_timestamp` (`schedule_timestamp`),
            KEY `popularity` (`popularity`),
            FOREIGN KEY (`ID`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get videos table schema
     *
     * @return string
     */
    public static function getVideosTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_videos` (
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
    }
    
    /**
     * Get movies cast table schema
     *
     * @return string
     */
    public static function getMoviesCastTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_movies_cast` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `movie` bigint(20) UNSIGNED NOT NULL,
            `person` bigint(20) UNSIGNED NOT NULL,
            `job` varchar(255) DEFAULT NULL,
            `character_name` varchar(255) DEFAULT NULL,
            `release_year` bigint(20) DEFAULT NULL,
            `order_no` int(11) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `movie` (`movie`),
            KEY `person` (`person`),
            KEY `release_year` (`release_year`),
            FOREIGN KEY (`movie`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY (`person`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get movies crew table schema
     *
     * @return string
     */
    public static function getMoviesCrewTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_movies_crew` (
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
            KEY `department` (`department`),
            KEY `release_year` (`release_year`),
            FOREIGN KEY (`movie`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY (`person`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get TV series cast table schema
     *
     * @return string
     */
    public static function getTVSeriesCastTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_tv_series_cast` (
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
    }
    
    /**
     * Get TV series crew table schema
     *
     * @return string
     */
    public static function getTVSeriesCrewTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_tv_series_crew` (
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
            KEY `department` (`department`),
            KEY `release_year` (`release_year`),
            FOREIGN KEY (`tv_series`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY (`person`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get dramas cast table schema
     *
     * @return string
     */
    public static function getDramasCastTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_dramas_cast` (
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
    }
    
    /**
     * Get dramas crew table schema
     *
     * @return string
     */
    public static function getDramasCrewTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_dramas_crew` (
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
            KEY `department` (`department`),
            KEY `release_year` (`release_year`),
            FOREIGN KEY (`drama`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY (`person`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get TV series episodes table schema
     *
     * @return string
     */
    public static function getTVSeriesEpisodesTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_tv_series_episodes` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `tv_series` bigint(20) UNSIGNED NOT NULL,
            `season_number` int(11) NOT NULL,
            `episode_number` int(11) NOT NULL,
            `title` text DEFAULT NULL,
            `overview` longtext DEFAULT NULL,
            `air_date` text DEFAULT NULL,
            `air_timestamp` bigint(20) DEFAULT NULL,
            `runtime` int(11) DEFAULT NULL,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `still_path` text DEFAULT NULL,
            `vote_average` DECIMAL(10,1) DEFAULT 0,
            `vote_count` int(11) DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `tv_series` (`tv_series`),
            KEY `season_number` (`season_number`),
            KEY `episode_number` (`episode_number`),
            KEY `tmdb_id` (`tmdb_id`),
            KEY `air_timestamp` (`air_timestamp`),
            FOREIGN KEY (`tv_series`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get TV series seasons table schema
     *
     * @return string
     */
    public static function getTVSeriesSeasonsTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_tv_series_seasons` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `tv_series` bigint(20) UNSIGNED NOT NULL,
            `season_number` int(11) NOT NULL,
            `name` text DEFAULT NULL,
            `overview` longtext DEFAULT NULL,
            `air_date` text DEFAULT NULL,
            `air_timestamp` bigint(20) DEFAULT NULL,
            `episode_count` int(11) DEFAULT 0,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `poster_path` text DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `tv_series` (`tv_series`),
            KEY `season_number` (`season_number`),
            KEY `tmdb_id` (`tmdb_id`),
            KEY `air_timestamp` (`air_timestamp`),
            FOREIGN KEY (`tv_series`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get dramas episodes table schema
     *
     * @return string
     */
    public static function getDramasEpisodesTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_dramas_episodes` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `drama` bigint(20) UNSIGNED NOT NULL,
            `season_number` int(11) NOT NULL,
            `episode_number` int(11) NOT NULL,
            `title` text DEFAULT NULL,
            `overview` longtext DEFAULT NULL,
            `air_date` text DEFAULT NULL,
            `air_timestamp` bigint(20) DEFAULT NULL,
            `runtime` int(11) DEFAULT NULL,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `still_path` text DEFAULT NULL,
            `vote_average` DECIMAL(10,1) DEFAULT 0,
            `vote_count` int(11) DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `drama` (`drama`),
            KEY `season_number` (`season_number`),
            KEY `episode_number` (`episode_number`),
            KEY `tmdb_id` (`tmdb_id`),
            KEY `air_timestamp` (`air_timestamp`),
            FOREIGN KEY (`drama`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get dramas seasons table schema
     *
     * @return string
     */
    public static function getDramasSeasonsTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_dramas_seasons` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `drama` bigint(20) UNSIGNED NOT NULL,
            `season_number` int(11) NOT NULL,
            `name` text DEFAULT NULL,
            `overview` longtext DEFAULT NULL,
            `air_date` text DEFAULT NULL,
            `air_timestamp` bigint(20) DEFAULT NULL,
            `episode_count` int(11) DEFAULT 0,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `poster_path` text DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `drama` (`drama`),
            KEY `season_number` (`season_number`),
            KEY `tmdb_id` (`tmdb_id`),
            KEY `air_timestamp` (`air_timestamp`),
            FOREIGN KEY (`drama`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get SEO options table schema
     *
     * @return string
     */
    public static function getSEOOptionsTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_seo_options` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` text DEFAULT NULL,
            `title` text DEFAULT NULL,
            `description` text DEFAULT NULL,
            `keywords` text DEFAULT NULL,
            `robots` text DEFAULT NULL,
            `post_type` text DEFAULT NULL,
            `section` text DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `post_type` (`post_type`(50)),
            KEY `section` (`section`(50))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get core table extensions
     *
     * @return array
     */
    public static function getCoreTableExtensions(): array {
        global $wpdb;
        
        return [
            // Extend wp_posts table
            "ALTER TABLE `{$wpdb->prefix}posts` ADD COLUMN `seo_title` TEXT NULL DEFAULT NULL",
            "ALTER TABLE `{$wpdb->prefix}posts` ADD COLUMN `seo_description` TEXT NULL DEFAULT NULL",
            "ALTER TABLE `{$wpdb->prefix}posts` ADD COLUMN `meta_keywords` TEXT NULL DEFAULT NULL",
            
            // Extend wp_comments table
            "ALTER TABLE `{$wpdb->prefix}comments` ADD COLUMN `comment_rating` INT(11) NOT NULL DEFAULT 0",
            "ALTER TABLE `{$wpdb->prefix}comments` ADD COLUMN `parent_post_id` INT(11) DEFAULT NULL",
            
            // Add indexes for performance
            "ALTER TABLE `{$wpdb->prefix}posts` ADD INDEX `seo_title` (`seo_title`(50))",
            "ALTER TABLE `{$wpdb->prefix}comments` ADD INDEX `comment_rating` (`comment_rating`)",
            "ALTER TABLE `{$wpdb->prefix}comments` ADD INDEX `parent_post_id` (`parent_post_id`)",
        ];
    }
    
    /**
     * Get performance indexes
     *
     * @return array
     */
    public static function getPerformanceIndexes(): array {
        global $wpdb;
        
        return [
            // Additional performance indexes
            "ALTER TABLE `{$wpdb->prefix}tmu_movies` ADD INDEX `avg_rating_idx` (`average_rating`)",
            "ALTER TABLE `{$wpdb->prefix}tmu_movies` ADD INDEX `vote_count_idx` (`vote_count`)",
            "ALTER TABLE `{$wpdb->prefix}tmu_movies` ADD INDEX `release_date_idx` (`release_date`(20))",
            
            "ALTER TABLE `{$wpdb->prefix}tmu_people` ADD INDEX `profession_idx` (`profession`(50))",
            "ALTER TABLE `{$wpdb->prefix}tmu_people` ADD INDEX `gender_idx` (`gender`(10))",
            
            "ALTER TABLE `{$wpdb->prefix}tmu_tv_series` ADD INDEX `finished_idx` (`finished`(10))",
            "ALTER TABLE `{$wpdb->prefix}tmu_tv_series` ADD INDEX `last_season_idx` (`last_season`)",
            
            "ALTER TABLE `{$wpdb->prefix}tmu_dramas` ADD INDEX `schedule_day_idx` (`schedule_day`(20))",
            "ALTER TABLE `{$wpdb->prefix}tmu_dramas` ADD INDEX `seo_genre_idx` (`seo_genre`)",
            
            // Cast/Crew indexes
            "ALTER TABLE `{$wpdb->prefix}tmu_movies_cast` ADD INDEX `job_idx` (`job`(50))",
            "ALTER TABLE `{$wpdb->prefix}tmu_movies_cast` ADD INDEX `character_idx` (`character_name`(50))",
            "ALTER TABLE `{$wpdb->prefix}tmu_movies_crew` ADD INDEX `job_dept_idx` (`job`(50), `department`(50))",
            
            // Episode indexes
            "ALTER TABLE `{$wpdb->prefix}tmu_tv_series_episodes` ADD INDEX `season_episode_idx` (`season_number`, `episode_number`)",
            "ALTER TABLE `{$wpdb->prefix}tmu_dramas_episodes` ADD INDEX `season_episode_idx` (`season_number`, `episode_number`)",
        ];
    }
    
    /**
     * Get all table names
     *
     * @return array
     */
    public static function getAllTableNames(): array {
        return [
            'tmu_movies',
            'tmu_people',
            'tmu_tv_series',
            'tmu_dramas',
            'tmu_videos',
            'tmu_seo_options',
            'tmu_movies_cast',
            'tmu_movies_crew',
            'tmu_tv_series_cast',
            'tmu_tv_series_crew',
            'tmu_dramas_cast',
            'tmu_dramas_crew',
            'tmu_tv_series_episodes',
            'tmu_tv_series_seasons',
            'tmu_dramas_episodes',
            'tmu_dramas_seasons'
        ];
    }
}