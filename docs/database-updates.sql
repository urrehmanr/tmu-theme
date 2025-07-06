-- TMU Database Schema Updates for Gutenberg Block System Compatibility
-- Execute these updates to align database with block specifications
-- IMPORTANT: Create a backup before running these updates!

-- Movies table updates
ALTER TABLE `wp_tmu_movies` 
ADD COLUMN `imdb_id` varchar(20) DEFAULT NULL AFTER `tmdb_id`,
ADD COLUMN `status` varchar(50) DEFAULT NULL AFTER `revenue`,
ADD COLUMN `homepage` text DEFAULT NULL AFTER `status`,
ADD COLUMN `poster_path` text DEFAULT NULL AFTER `homepage`,
ADD COLUMN `backdrop_path` text DEFAULT NULL AFTER `poster_path`,
ADD COLUMN `adult` tinyint(1) DEFAULT 0 AFTER `backdrop_path`,
ADD COLUMN `video` tinyint(1) DEFAULT 0 AFTER `adult`,
ADD COLUMN `belongs_to_collection` longtext DEFAULT NULL AFTER `video`,
ADD COLUMN `production_companies` longtext DEFAULT NULL AFTER `belongs_to_collection`,
ADD COLUMN `production_countries` longtext DEFAULT NULL AFTER `production_companies`,
ADD COLUMN `spoken_languages` longtext DEFAULT NULL AFTER `production_countries`,
ADD COLUMN `external_ids` longtext DEFAULT NULL AFTER `spoken_languages`,
ADD COLUMN `similar` longtext DEFAULT NULL AFTER `external_ids`,
ADD COLUMN `recommendations` longtext DEFAULT NULL AFTER `similar`,
ADD INDEX `imdb_id_idx` (`imdb_id`),
ADD INDEX `status_idx` (`status`);

-- TV Series table updates  
ALTER TABLE `wp_tmu_tv_series`
ADD COLUMN `imdb_id` varchar(20) DEFAULT NULL AFTER `tmdb_id`,
ADD COLUMN `name` text DEFAULT NULL AFTER `imdb_id`,
ADD COLUMN `original_name` text DEFAULT NULL AFTER `name`,
ADD COLUMN `type` varchar(50) DEFAULT NULL AFTER `original_name`,
ADD COLUMN `homepage` text DEFAULT NULL AFTER `type`,
ADD COLUMN `in_production` tinyint(1) DEFAULT 0 AFTER `homepage`,
ADD COLUMN `number_of_episodes` int(11) DEFAULT NULL AFTER `in_production`,
ADD COLUMN `number_of_seasons` int(11) DEFAULT NULL AFTER `number_of_episodes`,
ADD COLUMN `episode_run_time` longtext DEFAULT NULL AFTER `number_of_seasons`,
ADD COLUMN `languages` longtext DEFAULT NULL AFTER `episode_run_time`,
ADD COLUMN `origin_country` longtext DEFAULT NULL AFTER `languages`,
ADD COLUMN `original_language` varchar(10) DEFAULT NULL AFTER `origin_country`,
ADD COLUMN `poster_path` text DEFAULT NULL AFTER `original_language`,
ADD COLUMN `backdrop_path` text DEFAULT NULL AFTER `poster_path`,
ADD COLUMN `created_by` longtext DEFAULT NULL AFTER `backdrop_path`,
ADD COLUMN `networks` longtext DEFAULT NULL AFTER `created_by`,
ADD COLUMN `genres` longtext DEFAULT NULL AFTER `networks`,
ADD COLUMN `production_companies` longtext DEFAULT NULL AFTER `genres`,
ADD COLUMN `production_countries` longtext DEFAULT NULL AFTER `production_companies`,
ADD COLUMN `spoken_languages` longtext DEFAULT NULL AFTER `production_countries`,
ADD COLUMN `external_ids` longtext DEFAULT NULL AFTER `spoken_languages`,
ADD COLUMN `similar` longtext DEFAULT NULL AFTER `external_ids`,
ADD COLUMN `recommendations` longtext DEFAULT NULL AFTER `similar`,
ADD INDEX `imdb_id_idx` (`imdb_id`),
ADD INDEX `type_idx` (`type`),
ADD INDEX `in_production_idx` (`in_production`);

-- Dramas table updates
ALTER TABLE `wp_tmu_dramas`
ADD COLUMN `imdb_id` varchar(20) DEFAULT NULL AFTER `tmdb_id`,
ADD COLUMN `name` text DEFAULT NULL AFTER `imdb_id`,
ADD COLUMN `original_name` text DEFAULT NULL AFTER `name`,
ADD COLUMN `type` varchar(50) DEFAULT NULL AFTER `original_name`,
ADD COLUMN `homepage` text DEFAULT NULL AFTER `type`,
ADD COLUMN `number_of_episodes` int(11) DEFAULT NULL AFTER `homepage`,
ADD COLUMN `episode_run_time` longtext DEFAULT NULL AFTER `number_of_episodes`,
ADD COLUMN `languages` longtext DEFAULT NULL AFTER `episode_run_time`,
ADD COLUMN `origin_country` longtext DEFAULT NULL AFTER `languages`,
ADD COLUMN `original_language` varchar(10) DEFAULT NULL AFTER `origin_country`,
ADD COLUMN `poster_path` text DEFAULT NULL AFTER `original_language`,
ADD COLUMN `backdrop_path` text DEFAULT NULL AFTER `poster_path`,
ADD COLUMN `created_by` longtext DEFAULT NULL AFTER `backdrop_path`,
ADD COLUMN `networks` longtext DEFAULT NULL AFTER `created_by`,
ADD COLUMN `genres` longtext DEFAULT NULL AFTER `networks`,
ADD COLUMN `production_companies` longtext DEFAULT NULL AFTER `genres`,
ADD COLUMN `production_countries` longtext DEFAULT NULL AFTER `production_companies`,
ADD COLUMN `spoken_languages` longtext DEFAULT NULL AFTER `production_countries`,
ADD COLUMN `external_ids` longtext DEFAULT NULL AFTER `spoken_languages`,
ADD COLUMN `similar` longtext DEFAULT NULL AFTER `external_ids`,
ADD COLUMN `recommendations` longtext DEFAULT NULL AFTER `similar`,
ADD INDEX `imdb_id_idx` (`imdb_id`),
ADD INDEX `type_idx` (`type`);

-- People table updates
ALTER TABLE `wp_tmu_people`
ADD COLUMN `imdb_id` varchar(20) DEFAULT NULL AFTER `tmdb_id`,
ADD COLUMN `also_known_as` longtext DEFAULT NULL AFTER `imdb_id`,
ADD COLUMN `biography` longtext DEFAULT NULL AFTER `also_known_as`,
ADD COLUMN `birthday` date DEFAULT NULL AFTER `biography`,
ADD COLUMN `deathday` date DEFAULT NULL AFTER `birthday`,
ADD COLUMN `external_ids` longtext DEFAULT NULL AFTER `deathday`,
ADD COLUMN `images` longtext DEFAULT NULL AFTER `external_ids`,
ADD INDEX `imdb_id_idx` (`imdb_id`),
ADD INDEX `birthday_idx` (`birthday`);

-- Episodes table updates (both TV and Drama)
ALTER TABLE `wp_tmu_tv_series_episodes`
ADD COLUMN `name` text DEFAULT NULL AFTER `title`,
ADD COLUMN `overview` longtext DEFAULT NULL AFTER `name`,
ADD COLUMN `episode_type` varchar(50) DEFAULT 'standard' AFTER `overview`,
ADD COLUMN `production_code` varchar(50) DEFAULT NULL AFTER `episode_type`,
ADD COLUMN `crew` longtext DEFAULT NULL AFTER `production_code`,
ADD COLUMN `guest_stars` longtext DEFAULT NULL AFTER `crew`;

ALTER TABLE `wp_tmu_dramas_episodes`
ADD COLUMN `name` text DEFAULT NULL AFTER `title`,
ADD COLUMN `overview` longtext DEFAULT NULL AFTER `name`,
ADD COLUMN `episode_type` varchar(50) DEFAULT 'standard' AFTER `overview`,
ADD COLUMN `production_code` varchar(50) DEFAULT NULL AFTER `episode_type`,
ADD COLUMN `crew` longtext DEFAULT NULL AFTER `production_code`,
ADD COLUMN `guest_stars` longtext DEFAULT NULL AFTER `crew`,
ADD COLUMN `special_features` longtext DEFAULT NULL AFTER `guest_stars`;

-- Create new tables for advanced block features
CREATE TABLE `wp_tmu_block_settings` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `post_id` bigint(20) UNSIGNED NOT NULL,
    `block_type` varchar(100) NOT NULL,
    `block_data` longtext DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `post_id` (`post_id`),
    KEY `block_type` (`block_type`),
    FOREIGN KEY (`post_id`) REFERENCES `wp_posts`(`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- Taxonomy metadata table for taxonomy blocks
CREATE TABLE `wp_tmu_taxonomy_meta` (
    `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `term_id` bigint(20) UNSIGNED NOT NULL,
    `meta_key` varchar(255) DEFAULT NULL,
    `meta_value` longtext DEFAULT NULL,
    PRIMARY KEY (`meta_id`),
    KEY `term_id` (`term_id`),
    KEY `meta_key` (`meta_key`),
    FOREIGN KEY (`term_id`) REFERENCES `wp_terms`(`term_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;