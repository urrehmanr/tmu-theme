<?php
/**
 * TMU Database Configuration
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database schema for TMU tables
 * This preserves the existing plugin database structure
 */
return [
    'version' => '1.0.0',
    'charset' => 'utf8mb4',
    'collate' => 'utf8mb4_unicode_520_ci',
    'engine' => 'InnoDB',
    
    'table_names' => [
        'movies' => 'tmu_movies',
        'people' => 'tmu_people',
        'dramas' => 'tmu_dramas',
        'tv_series' => 'tmu_tv_series',
        'videos' => 'tmu_videos',
        'seo_options' => 'tmu_seo_options',
        'movies_cast' => 'tmu_movies_cast',
        'movies_crew' => 'tmu_movies_crew',
        'dramas_cast' => 'tmu_dramas_cast',
        'dramas_crew' => 'tmu_dramas_crew',
        'tv_series_cast' => 'tmu_tv_series_cast',
        'tv_series_crew' => 'tmu_tv_series_crew',
        'tv_series_episodes' => 'tmu_tv_series_episodes',
        'tv_series_seasons' => 'tmu_tv_series_seasons',
        'dramas_episodes' => 'tmu_dramas_episodes',
        'dramas_seasons' => 'tmu_dramas_seasons',
    ],
    
    'foreign_keys' => true,
    'create_indexes' => true,
    'backup_before_migration' => true,
    
    'tables' => [
        'tmu_movies' => [
            'fields' => [
                'ID' => 'bigint(20) UNSIGNED NOT NULL',
                'tmdb_id' => 'bigint(20) DEFAULT NULL',
                'release_date' => 'text DEFAULT NULL',
                'release_timestamp' => 'bigint(20) DEFAULT NULL',
                'original_title' => 'text DEFAULT NULL',
                'tagline' => 'text DEFAULT NULL',
                'production_house' => 'text DEFAULT NULL',
                'streaming_platforms' => 'text DEFAULT NULL',
                'runtime' => 'bigint(20) DEFAULT NULL',
                'certification' => 'text DEFAULT NULL',
                'revenue' => 'bigint(20) DEFAULT NULL',
                'budget' => 'bigint(20) DEFAULT NULL',
                'star_cast' => 'text DEFAULT NULL',
                'credits' => 'longtext DEFAULT NULL',
                'credits_temp' => 'longtext DEFAULT NULL',
                'videos' => 'text DEFAULT NULL',
                'images' => 'text DEFAULT NULL',
                'average_rating' => 'DECIMAL(10,1) DEFAULT 0',
                'vote_count' => 'bigint(20) DEFAULT 0',
                'popularity' => 'DECIMAL(10,1) DEFAULT 0',
                'total_average_rating' => 'DECIMAL(10,1) DEFAULT 0',
                'total_vote_count' => 'bigint(20) NOT NULL DEFAULT 0'
            ],
            'keys' => [
                'PRIMARY KEY (ID)',
                'FOREIGN KEY (ID) REFERENCES {prefix}posts(ID) ON DELETE CASCADE'
            ]
        ],
        'tmu_tv_series' => [
            'fields' => [
                'ID' => 'bigint(20) UNSIGNED NOT NULL',
                'tmdb_id' => 'bigint(20) DEFAULT NULL',
                'release_date' => 'text DEFAULT NULL',
                'release_timestamp' => 'bigint(20) DEFAULT NULL',
                'original_title' => 'text DEFAULT NULL',
                'finished' => 'text DEFAULT NULL',
                'tagline' => 'text DEFAULT NULL',
                'production_house' => 'text DEFAULT NULL',
                'streaming_platforms' => 'text DEFAULT NULL',
                'schedule_time' => 'text DEFAULT NULL',
                'runtime' => 'bigint(20) DEFAULT NULL',
                'certification' => 'text DEFAULT NULL',
                'revenue' => 'bigint(20) DEFAULT NULL',
                'budget' => 'bigint(20) DEFAULT NULL',
                'star_cast' => 'text DEFAULT NULL',
                'credits' => 'longtext DEFAULT NULL',
                'credits_temp' => 'longtext DEFAULT NULL',
                'videos' => 'text DEFAULT NULL',
                'images' => 'text DEFAULT NULL',
                'seasons' => 'text DEFAULT NULL',
                'last_season' => 'bigint(20) DEFAULT NULL',
                'last_episode' => 'bigint(20) DEFAULT NULL',
                'average_rating' => 'DECIMAL(10,1) DEFAULT 0',
                'vote_count' => 'bigint(20) DEFAULT 0',
                'popularity' => 'DECIMAL(10,1) DEFAULT 0',
                'where_to_watch' => 'text DEFAULT NULL',
                'total_average_rating' => 'DECIMAL(10,1) DEFAULT 0',
                'total_vote_count' => 'bigint(20) NOT NULL DEFAULT 0'
            ],
            'keys' => [
                'PRIMARY KEY (ID)',
                'FOREIGN KEY (ID) REFERENCES {prefix}posts(ID) ON DELETE CASCADE'
            ]
        ],
        'tmu_dramas' => [
            'fields' => [
                'ID' => 'bigint(20) UNSIGNED NOT NULL',
                'tmdb_id' => 'bigint(20) DEFAULT NULL',
                'release_date' => 'text DEFAULT NULL',
                'release_timestamp' => 'bigint(20) DEFAULT NULL',
                'original_title' => 'text DEFAULT NULL',
                'finished' => 'text DEFAULT NULL',
                'tagline' => 'text DEFAULT NULL',
                'seo_genre' => 'BIGINT(20) NULL DEFAULT NULL',
                'production_house' => 'text DEFAULT NULL',
                'streaming_platforms' => 'text DEFAULT NULL',
                'schedule_day' => 'text DEFAULT NULL',
                'schedule_time' => 'text DEFAULT NULL',
                'schedule_timestamp' => 'bigint(20) DEFAULT NULL',
                'runtime' => 'bigint(20) DEFAULT NULL',
                'certification' => 'text DEFAULT NULL',
                'star_cast' => 'text DEFAULT NULL',
                'credits' => 'longtext DEFAULT NULL',
                'credits_temp' => 'longtext DEFAULT NULL',
                'videos' => 'text DEFAULT NULL',
                'images' => 'text DEFAULT NULL',
                'average_rating' => 'DECIMAL(10,1) DEFAULT 0',
                'vote_count' => 'bigint(20) DEFAULT 0',
                'popularity' => 'DECIMAL(10,1) DEFAULT 0',
                'where_to_watch' => 'text DEFAULT NULL',
                'total_average_rating' => 'DECIMAL(10,1) DEFAULT 0',
                'total_vote_count' => 'bigint(20) NOT NULL DEFAULT 0'
            ],
            'keys' => [
                'PRIMARY KEY (ID)',
                'FOREIGN KEY (ID) REFERENCES {prefix}posts(ID) ON DELETE CASCADE'
            ]
        ],
        'tmu_people' => [
            'fields' => [
                'ID' => 'bigint(20) UNSIGNED NOT NULL',
                'name' => 'text DEFAULT NULL',
                'date_of_birth' => 'text DEFAULT NULL',
                'gender' => 'text DEFAULT NULL',
                'nick_name' => 'text DEFAULT NULL',
                'marital_status' => 'text DEFAULT NULL',
                'basic' => 'text DEFAULT NULL',
                'videos' => 'text DEFAULT NULL',
                'photos' => 'text DEFAULT NULL',
                'profession' => 'text DEFAULT NULL',
                'net_worth' => 'bigint(20) DEFAULT NULL',
                'tmdb_id' => 'bigint(20) DEFAULT NULL',
                'birthplace' => 'text DEFAULT NULL',
                'dead_on' => 'text DEFAULT NULL',
                'social_media_account' => 'text DEFAULT NULL',
                'no_movies' => 'bigint(20) DEFAULT NULL',
                'no_tv_series' => 'bigint(20) DEFAULT NULL',
                'no_dramas' => 'bigint(20) DEFAULT NULL',
                'known_for' => 'text DEFAULT NULL',
                'popularity' => 'DECIMAL(10,1) DEFAULT 0'
            ],
            'keys' => [
                'PRIMARY KEY (ID)',
                'FOREIGN KEY (ID) REFERENCES {prefix}posts(ID) ON DELETE CASCADE'
            ]
        ],
        // Additional tables for relationships and episodes
        'tmu_movies_cast' => [
            'fields' => [
                'ID' => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                'movie' => 'bigint(20) UNSIGNED NOT NULL',
                'person' => 'bigint(20) UNSIGNED NOT NULL',
                'job' => 'varchar(255) DEFAULT NULL',
                'release_year' => 'bigint(20) DEFAULT NULL'
            ],
            'keys' => [
                'PRIMARY KEY (ID)',
                'FOREIGN KEY (movie) REFERENCES {prefix}posts(ID) ON DELETE CASCADE',
                'FOREIGN KEY (person) REFERENCES {prefix}posts(ID) ON DELETE CASCADE'
            ]
        ],
        'tmu_tv_series_seasons' => [
            'fields' => [
                'ID' => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                'tv_series_id' => 'bigint(20) UNSIGNED NOT NULL',
                'season_number' => 'int(11) NOT NULL',
                'name' => 'text DEFAULT NULL',
                'overview' => 'longtext DEFAULT NULL',
                'air_date' => 'text DEFAULT NULL',
                'poster_path' => 'text DEFAULT NULL',
                'episode_count' => 'int(11) DEFAULT 0'
            ],
            'keys' => [
                'PRIMARY KEY (ID)',
                'FOREIGN KEY (tv_series_id) REFERENCES {prefix}posts(ID) ON DELETE CASCADE'
            ]
        ],
        'tmu_tv_series_episodes' => [
            'fields' => [
                'ID' => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT',
                'tv_series_id' => 'bigint(20) UNSIGNED NOT NULL',
                'season_number' => 'int(11) NOT NULL',
                'episode_number' => 'int(11) NOT NULL',
                'name' => 'text DEFAULT NULL',
                'overview' => 'longtext DEFAULT NULL',
                'air_date' => 'text DEFAULT NULL',
                'still_path' => 'text DEFAULT NULL',
                'runtime' => 'int(11) DEFAULT NULL',
                'vote_average' => 'DECIMAL(10,1) DEFAULT 0',
                'vote_count' => 'bigint(20) DEFAULT 0'
            ],
            'keys' => [
                'PRIMARY KEY (ID)',
                'FOREIGN KEY (tv_series_id) REFERENCES {prefix}posts(ID) ON DELETE CASCADE'
            ]
        ]
    ],
    'post_meta_fields' => [
        'movies' => [
            'seo_title' => 'text',
            'seo_description' => 'text',
            'meta_keywords' => 'text'
        ],
        'tv_series' => [
            'seo_title' => 'text',
            'seo_description' => 'text',
            'meta_keywords' => 'text'
        ],
        'dramas' => [
            'seo_title' => 'text',
            'seo_description' => 'text',
            'meta_keywords' => 'text'
        ],
        'people' => [
            'seo_title' => 'text',
            'seo_description' => 'text',
            'meta_keywords' => 'text'
        ]
    ]
];