<?php
/**
 * TMU Post Types Configuration
 *
 * @package TMU\Config
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Types Configuration Array
 * 
 * This configuration defines all custom post types for the TMU theme
 * Each post type can be conditionally registered based on theme settings
 */
return [
    'movie' => [
        'enabled' => true,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => true,
        'show_in_rest' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-video-alt',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments'],
        'labels' => [
            'name' => __('Movies', 'tmu'),
            'singular_name' => __('Movie', 'tmu'),
            'menu_name' => __('Movies', 'tmu'),
            'add_new' => __('Add New Movie', 'tmu'),
            'add_new_item' => __('Add New Movie', 'tmu'),
            'edit_item' => __('Edit Movie', 'tmu'),
            'new_item' => __('New Movie', 'tmu'),
            'view_item' => __('View Movie', 'tmu'),
            'view_items' => __('View Movies', 'tmu'),
            'search_items' => __('Search Movies', 'tmu'),
            'not_found' => __('No movies found', 'tmu'),
            'not_found_in_trash' => __('No movies found in trash', 'tmu'),
            'parent_item_colon' => __('Parent Movie:', 'tmu'),
            'all_items' => __('All Movies', 'tmu'),
        ],
        'rewrite' => [
            'slug' => 'movie',
            'with_front' => false,
            'pages' => true,
            'feeds' => true,
        ],
        'has_archive' => true,
        'archive_slug' => 'movies',
        'taxonomies' => ['genre', 'country', 'language', 'year'],
        'meta_fields' => [
            'tmdb_id', 'release_date', 'runtime', 'budget', 'revenue',
            'production_companies', 'director', 'cast', 'crew',
            'rating', 'vote_count', 'popularity', 'overview'
        ]
    ],
    
    'tv-series' => [
        'enabled' => true,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => true,
        'show_in_rest' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => 6,
        'menu_icon' => 'dashicons-desktop',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments'],
        'labels' => [
            'name' => __('TV Series', 'tmu'),
            'singular_name' => __('TV Series', 'tmu'),
            'menu_name' => __('TV Series', 'tmu'),
            'add_new' => __('Add New Series', 'tmu'),
            'add_new_item' => __('Add New TV Series', 'tmu'),
            'edit_item' => __('Edit TV Series', 'tmu'),
            'new_item' => __('New TV Series', 'tmu'),
            'view_item' => __('View TV Series', 'tmu'),
            'view_items' => __('View TV Series', 'tmu'),
            'search_items' => __('Search TV Series', 'tmu'),
            'not_found' => __('No TV series found', 'tmu'),
            'not_found_in_trash' => __('No TV series found in trash', 'tmu'),
            'parent_item_colon' => __('Parent Series:', 'tmu'),
            'all_items' => __('All TV Series', 'tmu'),
        ],
        'rewrite' => [
            'slug' => 'tv-series',
            'with_front' => false,
            'pages' => true,
            'feeds' => true,
        ],
        'has_archive' => true,
        'archive_slug' => 'tv-series',
        'taxonomies' => ['genre', 'country', 'language', 'network', 'year'],
        'meta_fields' => [
            'tmdb_id', 'first_air_date', 'last_air_date', 'number_of_seasons',
            'number_of_episodes', 'networks', 'creators', 'cast', 'crew',
            'rating', 'vote_count', 'popularity', 'overview', 'status'
        ]
    ],
    
    'drama' => [
        'enabled' => true,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => true,
        'show_in_rest' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => 7,
        'menu_icon' => 'dashicons-format-video',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments'],
        'labels' => [
            'name' => __('Dramas', 'tmu'),
            'singular_name' => __('Drama', 'tmu'),
            'menu_name' => __('Dramas', 'tmu'),
            'add_new' => __('Add New Drama', 'tmu'),
            'add_new_item' => __('Add New Drama', 'tmu'),
            'edit_item' => __('Edit Drama', 'tmu'),
            'new_item' => __('New Drama', 'tmu'),
            'view_item' => __('View Drama', 'tmu'),
            'view_items' => __('View Dramas', 'tmu'),
            'search_items' => __('Search Dramas', 'tmu'),
            'not_found' => __('No dramas found', 'tmu'),
            'not_found_in_trash' => __('No dramas found in trash', 'tmu'),
            'parent_item_colon' => __('Parent Drama:', 'tmu'),
            'all_items' => __('All Dramas', 'tmu'),
        ],
        'rewrite' => [
            'slug' => 'drama',
            'with_front' => false,
            'pages' => true,
            'feeds' => true,
        ],
        'has_archive' => true,
        'archive_slug' => 'dramas',
        'taxonomies' => ['genre', 'country', 'language', 'network', 'year'],
        'meta_fields' => [
            'tmdb_id', 'first_air_date', 'last_air_date', 'number_of_episodes',
            'broadcast_network', 'schedule_day', 'schedule_time', 'cast', 'crew',
            'rating', 'vote_count', 'popularity', 'overview', 'status'
        ]
    ],
    
    'people' => [
        'enabled' => true,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => true,
        'show_in_rest' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => 8,
        'menu_icon' => 'dashicons-admin-users',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
        'labels' => [
            'name' => __('People', 'tmu'),
            'singular_name' => __('Person', 'tmu'),
            'menu_name' => __('People', 'tmu'),
            'add_new' => __('Add New Person', 'tmu'),
            'add_new_item' => __('Add New Person', 'tmu'),
            'edit_item' => __('Edit Person', 'tmu'),
            'new_item' => __('New Person', 'tmu'),
            'view_item' => __('View Person', 'tmu'),
            'view_items' => __('View People', 'tmu'),
            'search_items' => __('Search People', 'tmu'),
            'not_found' => __('No people found', 'tmu'),
            'not_found_in_trash' => __('No people found in trash', 'tmu'),
            'parent_item_colon' => __('Parent Person:', 'tmu'),
            'all_items' => __('All People', 'tmu'),
        ],
        'rewrite' => [
            'slug' => 'person',
            'with_front' => false,
            'pages' => true,
            'feeds' => true,
        ],
        'has_archive' => true,
        'archive_slug' => 'people',
        'taxonomies' => ['profession', 'nationality'],
        'meta_fields' => [
            'tmdb_id', 'birthday', 'deathday', 'place_of_birth', 'biography',
            'known_for_department', 'gender', 'popularity', 'profile_path',
            'imdb_id', 'homepage', 'social_media'
        ]
    ],
    
    'season' => [
        'enabled' => true,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => false,
        'show_in_nav_menus' => false,
        'show_in_admin_bar' => false,
        'show_in_rest' => true,
        'capability_type' => 'post',
        'hierarchical' => true,
        'menu_position' => null,
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => ['title', 'editor', 'thumbnail', 'page-attributes', 'custom-fields'],
        'labels' => [
            'name' => __('Seasons', 'tmu'),
            'singular_name' => __('Season', 'tmu'),
            'menu_name' => __('Seasons', 'tmu'),
            'add_new' => __('Add New Season', 'tmu'),
            'add_new_item' => __('Add New Season', 'tmu'),
            'edit_item' => __('Edit Season', 'tmu'),
            'new_item' => __('New Season', 'tmu'),
            'view_item' => __('View Season', 'tmu'),
            'view_items' => __('View Seasons', 'tmu'),
            'search_items' => __('Search Seasons', 'tmu'),
            'not_found' => __('No seasons found', 'tmu'),
            'not_found_in_trash' => __('No seasons found in trash', 'tmu'),
            'parent_item_colon' => __('Parent Season:', 'tmu'),
            'all_items' => __('All Seasons', 'tmu'),
        ],
        'rewrite' => [
            'slug' => 'season',
            'with_front' => false,
            'pages' => true,
            'feeds' => false,
        ],
        'has_archive' => false,
        'taxonomies' => [],
        'meta_fields' => [
            'tmdb_id', 'season_number', 'episode_count', 'air_date',
            'overview', 'poster_path', 'series_id'
        ]
    ],
    
    'episode' => [
        'enabled' => true,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => false,
        'show_in_nav_menus' => false,
        'show_in_admin_bar' => false,
        'show_in_rest' => true,
        'capability_type' => 'post',
        'hierarchical' => true,
        'menu_position' => null,
        'menu_icon' => 'dashicons-media-video',
        'supports' => ['title', 'editor', 'thumbnail', 'page-attributes', 'custom-fields'],
        'labels' => [
            'name' => __('Episodes', 'tmu'),
            'singular_name' => __('Episode', 'tmu'),
            'menu_name' => __('Episodes', 'tmu'),
            'add_new' => __('Add New Episode', 'tmu'),
            'add_new_item' => __('Add New Episode', 'tmu'),
            'edit_item' => __('Edit Episode', 'tmu'),
            'new_item' => __('New Episode', 'tmu'),
            'view_item' => __('View Episode', 'tmu'),
            'view_items' => __('View Episodes', 'tmu'),
            'search_items' => __('Search Episodes', 'tmu'),
            'not_found' => __('No episodes found', 'tmu'),
            'not_found_in_trash' => __('No episodes found in trash', 'tmu'),
            'parent_item_colon' => __('Parent Episode:', 'tmu'),
            'all_items' => __('All Episodes', 'tmu'),
        ],
        'rewrite' => [
            'slug' => 'episode',
            'with_front' => false,
            'pages' => true,
            'feeds' => false,
        ],
        'has_archive' => false,
        'taxonomies' => [],
        'meta_fields' => [
            'tmdb_id', 'episode_number', 'season_number', 'air_date',
            'runtime', 'overview', 'still_path', 'vote_average', 'vote_count',
            'series_id', 'season_id', 'guest_stars', 'crew'
        ]
    ],
    
    'video' => [
        'enabled' => true,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => false,
        'show_in_nav_menus' => false,
        'show_in_admin_bar' => false,
        'show_in_rest' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => null,
        'menu_icon' => 'dashicons-video-alt3',
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'labels' => [
            'name' => __('Videos', 'tmu'),
            'singular_name' => __('Video', 'tmu'),
            'menu_name' => __('Videos', 'tmu'),
            'add_new' => __('Add New Video', 'tmu'),
            'add_new_item' => __('Add New Video', 'tmu'),
            'edit_item' => __('Edit Video', 'tmu'),
            'new_item' => __('New Video', 'tmu'),
            'view_item' => __('View Video', 'tmu'),
            'view_items' => __('View Videos', 'tmu'),
            'search_items' => __('Search Videos', 'tmu'),
            'not_found' => __('No videos found', 'tmu'),
            'not_found_in_trash' => __('No videos found in trash', 'tmu'),
            'parent_item_colon' => __('Parent Video:', 'tmu'),
            'all_items' => __('All Videos', 'tmu'),
        ],
        'rewrite' => [
            'slug' => 'video',
            'with_front' => false,
            'pages' => true,
            'feeds' => false,
        ],
        'has_archive' => true,
        'archive_slug' => 'videos',
        'taxonomies' => ['video-type'],
        'meta_fields' => [
            'video_type', 'video_key', 'video_site', 'video_size',
            'iso_639_1', 'iso_3166_1', 'official', 'published_at',
            'related_id', 'related_type'
        ]
    ]
];