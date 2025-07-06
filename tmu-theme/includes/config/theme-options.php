<?php
/**
 * Theme Options Configuration
 *
 * @package TMU\Config
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Options Configuration
 */
return [
    'post_types' => [
        'movies' => [
            'default' => 'off',
            'type' => 'boolean',
            'label' => __('Movies', 'tmu'),
            'description' => __('Enable Movies post type for movie database management', 'tmu'),
            'option_name' => 'tmu_movies',
            'dependencies' => [],
            'features' => [
                'taxonomies' => ['genre', 'country', 'language', 'by-year', 'production-company'],
                'meta_fields' => ['runtime', 'budget', 'revenue', 'director', 'tmdb_id'],
                'admin_columns' => ['poster', 'rating', 'runtime', 'release_date'],
                'archive_page' => true,
                'search_support' => true,
            ]
        ],
        
        'tv_series' => [
            'default' => 'off',
            'type' => 'boolean',
            'label' => __('TV Series', 'tmu'),
            'description' => __('Enable TV Series post type with seasons and episodes support', 'tmu'),
            'option_name' => 'tmu_tv_series',
            'dependencies' => [],
            'child_post_types' => ['season', 'episode'],
            'features' => [
                'taxonomies' => ['genre', 'country', 'language', 'by-year', 'network'],
                'meta_fields' => ['number_of_seasons', 'number_of_episodes', 'episode_runtime'],
                'admin_columns' => ['poster', 'rating', 'seasons', 'episodes', 'status'],
                'archive_page' => true,
                'hierarchical_structure' => true,
            ]
        ],
        
        'dramas' => [
            'default' => 'off',
            'type' => 'boolean',
            'label' => __('Dramas', 'tmu'),
            'description' => __('Enable Dramas post type for drama series management', 'tmu'),
            'option_name' => 'tmu_dramas',
            'dependencies' => [],
            'child_post_types' => ['drama-episode'],
            'features' => [
                'taxonomies' => ['genre', 'country', 'language', 'by-year', 'network'],
                'meta_fields' => ['number_of_episodes', 'episode_runtime', 'network'],
                'admin_columns' => ['poster', 'rating', 'episodes', 'status'],
                'archive_page' => true,
                'episode_support' => true,
            ]
        ],
        
        'people' => [
            'default' => 'on',
            'type' => 'boolean',
            'label' => __('People', 'tmu'),
            'description' => __('Enable People post type for cast and crew management (always enabled)', 'tmu'),
            'option_name' => 'tmu_people',
            'dependencies' => [],
            'always_enabled' => true,
            'features' => [
                'taxonomies' => ['profession', 'country'],
                'meta_fields' => ['date_of_birth', 'place_of_birth', 'biography', 'known_for_department'],
                'admin_columns' => ['profile_photo', 'profession', 'birth_date'],
                'archive_page' => true,
                'filmography_support' => true,
            ]
        ],
        
        'videos' => [
            'default' => 'off',
            'type' => 'boolean',
            'label' => __('Videos', 'tmu'),
            'description' => __('Enable Videos post type for trailers and video content', 'tmu'),
            'option_name' => 'tmu_videos',
            'dependencies' => [],
            'features' => [
                'taxonomies' => ['video-type', 'language'],
                'meta_fields' => ['video_url', 'video_type', 'duration', 'related_post_id'],
                'admin_columns' => ['thumbnail', 'video_type', 'duration', 'related_content'],
                'archive_page' => true,
                'embed_support' => true,
            ]
        ],
    ],
    
    'nested_post_types' => [
        'season' => [
            'parent' => 'tv_series',
            'label' => __('Seasons', 'tmu'),
            'description' => __('TV show seasons (managed under TV Series)', 'tmu'),
            'show_in_menu' => false,
            'admin_parent' => 'edit.php?post_type=tv',
            'features' => [
                'meta_fields' => ['season_number', 'episode_count', 'air_date', 'tv_show_id'],
                'admin_columns' => ['season_number', 'episode_count', 'air_date'],
                'custom_permalinks' => true,
            ]
        ],
        
        'episode' => [
            'parent' => 'tv_series',
            'label' => __('Episodes', 'tmu'),
            'description' => __('TV show episodes (managed under TV Series)', 'tmu'),
            'show_in_menu' => false,
            'admin_parent' => 'edit.php?post_type=tv',
            'features' => [
                'meta_fields' => ['episode_number', 'season_number', 'air_date', 'runtime'],
                'admin_columns' => ['episode_number', 'season_number', 'air_date', 'runtime'],
                'custom_permalinks' => true,
            ]
        ],
        
        'drama-episode' => [
            'parent' => 'dramas',
            'label' => __('Drama Episodes', 'tmu'),
            'description' => __('Drama series episodes (managed under Dramas)', 'tmu'),
            'show_in_menu' => false,
            'admin_parent' => 'edit.php?post_type=drama',
            'features' => [
                'meta_fields' => ['episode_number', 'air_date', 'runtime', 'drama_id'],
                'admin_columns' => ['episode_number', 'air_date', 'runtime'],
                'custom_permalinks' => true,
            ]
        ],
    ],
    
    'admin_settings' => [
        'menu_organization' => [
            'enable_nested_menus' => true,
            'hide_individual_nested_types' => true,
            'custom_menu_order' => [
                'movie' => 25,
                'tv' => 26,
                'drama' => 27,
                'people' => 28,
                'video' => 29,
            ]
        ],
        
        'archive_settings' => [
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
            'enable_filters' => true,
            'enable_search' => true,
        ],
        
        'permalink_settings' => [
            'movie_slug' => 'movie',
            'tv_slug' => 'tv-show',
            'drama_slug' => 'drama',
            'person_slug' => 'person',
            'video_slug' => 'video',
            'enable_custom_structure' => true,
        ]
    ],
    
    'capabilities' => [
        'manage_tmu_settings' => 'manage_options',
        'edit_tmu_movies' => 'edit_posts',
        'edit_tmu_tv_series' => 'edit_posts',
        'edit_tmu_dramas' => 'edit_posts',
        'edit_tmu_people' => 'edit_posts',
        'edit_tmu_videos' => 'edit_posts',
        'publish_tmu_content' => 'publish_posts',
        'delete_tmu_content' => 'delete_posts',
    ],
    
    'default_settings' => [
        'enable_tmdb_integration' => true,
        'auto_sync_content' => false,
        'cache_duration' => 3600,
        'image_quality' => 'high',
        'enable_comments' => true,
        'enable_excerpts' => true,
        'enable_revisions' => true,
    ]
];