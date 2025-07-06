<?php
/**
 * TMU Theme Configuration
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Configuration Settings
 */
return [
    
    /**
     * Post Types Configuration
     */
    'post_types' => [
        'movie' => [
            'enabled' => get_option('tmu_movies', 'on') === 'on',
            'slug' => 'movie',
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'comments', 'custom-fields'],
            'taxonomies' => ['genre', 'country', 'language', 'by-year', 'keyword'],
            'menu_position' => 5,
            'menu_icon' => 'dashicons-format-video'
        ],
        'tv' => [
            'enabled' => get_option('tmu_tv_series', 'on') === 'on',
            'slug' => 'tv',
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'comments', 'custom-fields'],
            'taxonomies' => ['genre', 'country', 'language', 'by-year', 'network', 'keyword'],
            'menu_position' => 4,
            'menu_icon' => 'dashicons-video-alt3'
        ],
        'drama' => [
            'enabled' => get_option('tmu_dramas', 'off') === 'on',
            'slug' => 'drama',
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'comments', 'custom-fields'],
            'taxonomies' => ['genre', 'country', 'language', 'by-year', 'channel'],
            'menu_position' => 6,
            'menu_icon' => 'dashicons-video-alt2'
        ],
        'people' => [
            'enabled' => get_option('tmu_people', 'on') === 'on',
            'slug' => 'people',
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'taxonomies' => ['nationality'],
            'menu_position' => 7,
            'menu_icon' => 'dashicons-groups'
        ],
        'video' => [
            'enabled' => get_option('tmu_videos', 'on') === 'on',
            'slug' => 'video',
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'taxonomies' => [],
            'menu_position' => 8,
            'menu_icon' => 'dashicons-video-alt'
        ],
        'season' => [
            'enabled' => get_option('tmu_tv_series', 'on') === 'on',
            'slug' => 'season',
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'taxonomies' => [],
            'show_in_menu' => 'edit.php?post_type=tv',
            'menu_icon' => 'dashicons-admin-generic'
        ],
        'episode' => [
            'enabled' => get_option('tmu_tv_series', 'on') === 'on',
            'slug' => 'episode',
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'comments', 'custom-fields'],
            'taxonomies' => [],
            'show_in_menu' => 'edit.php?post_type=tv',
            'menu_icon' => 'dashicons-playlist-video'
        ],
        'drama-episode' => [
            'enabled' => get_option('tmu_dramas', 'off') === 'on',
            'slug' => 'drama-episode',
            'hierarchical' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'comments', 'custom-fields'],
            'taxonomies' => [],
            'show_in_menu' => 'edit.php?post_type=drama',
            'menu_icon' => 'dashicons-playlist-video'
        ]
    ],
    
    /**
     * Taxonomies Configuration
     */
    'taxonomies' => [
        'genre' => [
            'enabled' => true,
            'post_types' => ['movie', 'tv', 'drama'],
            'hierarchical' => true,
            'show_admin_column' => true,
            'show_in_rest' => true
        ],
        'country' => [
            'enabled' => true,
            'post_types' => ['movie', 'tv', 'drama'],
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true
        ],
        'language' => [
            'enabled' => true,
            'post_types' => ['movie', 'tv', 'drama'],
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true
        ],
        'by-year' => [
            'enabled' => true,
            'post_types' => ['movie', 'tv', 'drama'],
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true
        ],
        'network' => [
            'enabled' => get_option('tmu_tv_series', 'on') === 'on',
            'post_types' => ['tv'],
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true
        ],
        'channel' => [
            'enabled' => get_option('tmu_dramas', 'off') === 'on',
            'post_types' => ['drama'],
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true
        ],
        'keyword' => [
            'enabled' => get_option('tmu_dramas', 'off') !== 'on' || 
                        get_option('tmu_movies', 'on') === 'on' || 
                        get_option('tmu_tv_series', 'on') === 'on',
            'post_types' => ['movie', 'tv'],
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true
        ],
        'nationality' => [
            'enabled' => get_option('tmu_people', 'on') === 'on',
            'post_types' => ['people'],
            'hierarchical' => false,
            'show_admin_column' => true,
            'show_in_rest' => true
        ]
    ],
    
    /**
     * TMDB API Configuration
     */
    'tmdb' => [
        'api_key' => get_option('tmu_tmdb_api_key', ''),
        'base_url' => 'https://api.themoviedb.org/3',
        'image_base_url' => 'https://image.tmdb.org/t/p/',
        'cache_duration' => 3600, // 1 hour
        'rate_limit' => 40, // requests per 10 seconds
        'timeout' => 30,
        'image_sizes' => [
            'poster' => ['w154', 'w185', 'w342', 'w500', 'w780', 'original'],
            'backdrop' => ['w300', 'w780', 'w1280', 'original'],
            'profile' => ['w45', 'w185', 'h632', 'original']
        ]
    ],
    
    /**
     * Performance Configuration
     */
    'performance' => [
        'cache_enabled' => get_option('tmu_cache_enabled', 'on') === 'on',
        'cache_duration' => [
            'movie_data' => 3600,
            'tv_data' => 3600,
            'drama_data' => 3600,
            'people_data' => 7200,
            'search_results' => 1800,
            'tmdb_data' => 3600
        ],
        'query_optimization' => true,
        'image_optimization' => true,
        'lazy_loading' => true,
        'webp_conversion' => true,
        'css_minification' => true,
        'js_minification' => true
    ],
    
    /**
     * SEO Configuration
     */
    'seo' => [
        'enabled' => get_option('tmu_seo_enabled', 'on') === 'on',
        'schema_markup' => true,
        'opengraph' => true,
        'twitter_cards' => true,
        'breadcrumbs' => true,
        'sitemap' => true,
        'meta_descriptions' => true,
        'canonical_urls' => true
    ],
    
    /**
     * Security Configuration
     */
    'security' => [
        'input_validation' => true,
        'output_sanitization' => true,
        'csrf_protection' => true,
        'rate_limiting' => true,
        'sql_injection_protection' => true,
        'xss_protection' => true,
        'capability_checks' => true
    ],
    
    /**
     * Admin Configuration
     */
    'admin' => [
        'dashboard_widgets' => true,
        'admin_columns' => true,
        'bulk_actions' => true,
        'quick_edit' => true,
        'meta_boxes' => true,
        'admin_bar_items' => true,
        'menu_customization' => true
    ],
    
    /**
     * Frontend Configuration
     */
    'frontend' => [
        'responsive_design' => true,
        'mobile_optimization' => true,
        'accessibility' => true,
        'progressive_enhancement' => true,
        'offline_support' => false,
        'pwa_features' => false
    ],
    
    /**
     * Image Sizes Configuration
     */
    'image_sizes' => [
        'tmu-thumbnail' => [300, 400, true],
        'tmu-medium' => [600, 800, true],
        'tmu-large' => [1200, 1600, true],
        'tmu-poster' => [500, 750, true],
        'tmu-backdrop' => [1280, 720, true],
        'tmu-profile' => [185, 278, true]
    ],
    
    /**
     * Database Tables Configuration
     */
    'database_tables' => [
        'movies' => 'tmu_movies',
        'tv_series' => 'tmu_tv_series',
        'dramas' => 'tmu_dramas',
        'people' => 'tmu_people',
        'videos' => 'tmu_videos',
        'tv_series_seasons' => 'tmu_tv_series_seasons',
        'tv_series_episodes' => 'tmu_tv_series_episodes',
        'dramas_episodes' => 'tmu_dramas_episodes',
        'movie_cast' => 'tmu_movies_cast',
        'movie_crew' => 'tmu_movies_crew',
        'tv_series_cast' => 'tmu_tv_series_cast',
        'tv_series_crew' => 'tmu_tv_series_crew',
        'drama_cast' => 'tmu_dramas_cast',
        'drama_crew' => 'tmu_dramas_crew',
        'seo_options' => 'tmu_seo_options'
    ],
    
    /**
     * Block Categories Configuration
     */
    'block_categories' => [
        'tmu-content' => [
            'slug' => 'tmu-content',
            'title' => __('TMU Content', 'tmu'),
            'icon' => 'video-alt3'
        ],
        'tmu-media' => [
            'slug' => 'tmu-media',
            'title' => __('TMU Media', 'tmu'),
            'icon' => 'format-gallery'
        ],
        'tmu-layout' => [
            'slug' => 'tmu-layout',
            'title' => __('TMU Layout', 'tmu'),
            'icon' => 'layout'
        ]
    ],
    
    /**
     * REST API Configuration
     */
    'rest_api' => [
        'namespace' => 'tmu/v1',
        'endpoints' => [
            'movies',
            'tv-shows',
            'dramas',
            'people',
            'search',
            'tmdb-sync'
        ],
        'authentication' => true,
        'rate_limiting' => true
    ],
    
    /**
     * Logging Configuration
     */
    'logging' => [
        'enabled' => WP_DEBUG,
        'level' => 'info',
        'file' => WP_CONTENT_DIR . '/uploads/tmu-logs/tmu.log',
        'max_files' => 10,
        'max_size' => '10MB'
    ],
    
    /**
     * Cron Jobs Configuration
     */
    'cron_jobs' => [
        'tmdb_sync' => [
            'enabled' => true,
            'schedule' => 'daily',
            'callback' => 'tmu_daily_tmdb_sync'
        ],
        'cache_cleanup' => [
            'enabled' => true,
            'schedule' => 'weekly',
            'callback' => 'tmu_weekly_cache_cleanup'
        ],
        'image_optimization' => [
            'enabled' => true,
            'schedule' => 'weekly',
            'callback' => 'tmu_weekly_image_optimization'
        ]
    ]
];