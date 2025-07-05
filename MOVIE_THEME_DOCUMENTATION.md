# WordPress Movie/TV/Drama Theme Development Documentation

## Overview
This documentation provides a comprehensive guide to create a fully customizable WordPress theme for movies, TV shows, and dramas with custom post types, taxonomies, meta fields, database tables, and TMDB integration.

## Table of Contents
1. [Project Structure](#project-structure)
2. [Database Schema](#database-schema)
3. [Custom Post Types](#custom-post-types)
4. [Taxonomies](#taxonomies)
5. [Custom Fields](#custom-fields)
6. [TMDB API Integration](#tmdb-api-integration)
7. [Frontend Templates](#frontend-templates)
8. [SEO Implementation](#seo-implementation)
9. [Admin Interface](#admin-interface)
10. [Gutenberg Integration](#gutenberg-integration)
11. [Implementation Steps](#implementation-steps)

## Project Structure

```
theme-name/
├── functions.php                    # Main theme functions
├── style.css                      # Main stylesheet
├── index.php                      # Default template
├── header.php                     # Header template
├── footer.php                     # Footer template
├── sidebar.php                    # Sidebar template
├── inc/                           # Theme includes
│   ├── setup/                     # Theme setup functions
│   │   ├── init.php              # Theme initialization
│   │   ├── database.php          # Database setup
│   │   ├── settings.php          # Admin settings
│   │   └── options.php           # Theme options
│   ├── post-types/               # Custom post types
│   │   ├── movies.php
│   │   ├── tv-series.php
│   │   ├── dramas.php
│   │   ├── episodes.php
│   │   ├── seasons.php
│   │   ├── people.php
│   │   └── videos.php
│   ├── taxonomies/               # Custom taxonomies
│   │   ├── genre.php
│   │   ├── country.php
│   │   ├── language.php
│   │   ├── by-year.php
│   │   ├── network.php
│   │   └── keywords.php
│   ├── fields/                   # Custom meta fields
│   │   ├── movie-fields.php
│   │   ├── tv-fields.php
│   │   ├── drama-fields.php
│   │   ├── episode-fields.php
│   │   ├── season-fields.php
│   │   └── people-fields.php
│   ├── api/                      # TMDB API integration
│   │   ├── tmdb-client.php
│   │   ├── movie-api.php
│   │   ├── tv-api.php
│   │   ├── drama-api.php
│   │   └── credits-api.php
│   ├── templates/                # Custom templates
│   │   ├── single-movie.php
│   │   ├── single-tv.php
│   │   ├── single-drama.php
│   │   ├── single-episode.php
│   │   ├── single-season.php
│   │   ├── single-person.php
│   │   ├── archive-movies.php
│   │   ├── archive-tv.php
│   │   └── archive-dramas.php
│   ├── seo/                      # SEO functionality
│   │   ├── meta-tags.php
│   │   ├── schema.php
│   │   ├── sitemap.php
│   │   └── breadcrumbs.php
│   ├── blocks/                   # Gutenberg blocks
│   │   ├── movie-card/
│   │   ├── tv-card/
│   │   ├── drama-card/
│   │   ├── cast-crew/
│   │   ├── rating-system/
│   │   └── video-player/
│   └── utils/                    # Utility functions
│       ├── helpers.php
│       ├── ratings.php
│       ├── image-processing.php
│       └── cache.php
├── assets/                       # Static assets
│   ├── css/
│   │   ├── main.css
│   │   ├── single-movie.css
│   │   ├── archive.css
│   │   ├── admin.css
│   │   └── blocks.css
│   ├── js/
│   │   ├── main.js
│   │   ├── ajax.js
│   │   ├── rating.js
│   │   ├── image-gallery.js
│   │   └── blocks.js
│   └── images/
│       ├── no-poster.webp
│       ├── no-image.webp
│       └── icons/
├── templates/                    # Theme templates
│   ├── single-movie.php
│   ├── single-tv.php
│   ├── single-drama.php
│   ├── single-episode.php
│   ├── single-season.php
│   ├── single-person.php
│   ├── archive-movies.php
│   ├── archive-tv.php
│   ├── archive-dramas.php
│   ├── taxonomy-genre.php
│   ├── taxonomy-country.php
│   └── search.php
└── languages/                    # Translation files
    ├── theme-name.pot
    └── theme-name-en_US.mo
```

## Database Schema

### Custom Tables Structure

#### 1. Movies Table (`wp_theme_movies`)
```sql
CREATE TABLE `wp_theme_movies` (
  `ID` bigint(20) UNSIGNED NOT NULL,
  `tmdb_id` bigint(20) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `release_timestamp` bigint(20) DEFAULT NULL,
  `original_title` varchar(255) DEFAULT NULL,
  `tagline` text DEFAULT NULL,
  `runtime` int(11) DEFAULT NULL,
  `certification` varchar(50) DEFAULT NULL,
  `budget` bigint(20) DEFAULT NULL,
  `revenue` bigint(20) DEFAULT NULL,
  `production_house` text DEFAULT NULL,
  `streaming_platforms` text DEFAULT NULL,
  `star_cast` longtext DEFAULT NULL,
  `credits` longtext DEFAULT NULL,
  `videos` longtext DEFAULT NULL,
  `images` longtext DEFAULT NULL,
  `average_rating` decimal(3,1) DEFAULT 0.0,
  `vote_count` int(11) DEFAULT 0,
  `popularity` decimal(10,3) DEFAULT 0.000,
  `total_average_rating` decimal(3,1) DEFAULT 0.0,
  `total_vote_count` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  FOREIGN KEY (`ID`) REFERENCES `wp_posts`(`ID`) ON DELETE CASCADE
);
```

#### 2. TV Series Table (`wp_theme_tv_series`)
```sql
CREATE TABLE `wp_theme_tv_series` (
  `ID` bigint(20) UNSIGNED NOT NULL,
  `tmdb_id` bigint(20) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `release_timestamp` bigint(20) DEFAULT NULL,
  `original_title` varchar(255) DEFAULT NULL,
  `tagline` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `runtime` int(11) DEFAULT NULL,
  `certification` varchar(50) DEFAULT NULL,
  `production_house` text DEFAULT NULL,
  `streaming_platforms` text DEFAULT NULL,
  `star_cast` longtext DEFAULT NULL,
  `credits` longtext DEFAULT NULL,
  `videos` longtext DEFAULT NULL,
  `images` longtext DEFAULT NULL,
  `seasons` longtext DEFAULT NULL,
  `last_season` int(11) DEFAULT NULL,
  `last_episode` int(11) DEFAULT NULL,
  `average_rating` decimal(3,1) DEFAULT 0.0,
  `vote_count` int(11) DEFAULT 0,
  `popularity` decimal(10,3) DEFAULT 0.000,
  `total_average_rating` decimal(3,1) DEFAULT 0.0,
  `total_vote_count` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  FOREIGN KEY (`ID`) REFERENCES `wp_posts`(`ID`) ON DELETE CASCADE
);
```

#### 3. Dramas Table (`wp_theme_dramas`)
```sql
CREATE TABLE `wp_theme_dramas` (
  `ID` bigint(20) UNSIGNED NOT NULL,
  `tmdb_id` bigint(20) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `release_timestamp` bigint(20) DEFAULT NULL,
  `original_title` varchar(255) DEFAULT NULL,
  `tagline` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `runtime` int(11) DEFAULT NULL,
  `certification` varchar(50) DEFAULT NULL,
  `production_house` text DEFAULT NULL,
  `streaming_platforms` text DEFAULT NULL,
  `schedule_day` varchar(20) DEFAULT NULL,
  `schedule_time` varchar(20) DEFAULT NULL,
  `star_cast` longtext DEFAULT NULL,
  `credits` longtext DEFAULT NULL,
  `videos` longtext DEFAULT NULL,
  `images` longtext DEFAULT NULL,
  `average_rating` decimal(3,1) DEFAULT 0.0,
  `vote_count` int(11) DEFAULT 0,
  `popularity` decimal(10,3) DEFAULT 0.000,
  `total_average_rating` decimal(3,1) DEFAULT 0.0,
  `total_vote_count` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  FOREIGN KEY (`ID`) REFERENCES `wp_posts`(`ID`) ON DELETE CASCADE
);
```

#### 4. People Table (`wp_theme_people`)
```sql
CREATE TABLE `wp_theme_people` (
  `ID` bigint(20) UNSIGNED NOT NULL,
  `tmdb_id` bigint(20) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `date_of_death` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `birthplace` text DEFAULT NULL,
  `biography` longtext DEFAULT NULL,
  `profession` varchar(255) DEFAULT NULL,
  `known_for` longtext DEFAULT NULL,
  `social_media` longtext DEFAULT NULL,
  `photos` longtext DEFAULT NULL,
  `no_movies` int(11) DEFAULT 0,
  `no_tv_series` int(11) DEFAULT 0,
  `no_dramas` int(11) DEFAULT 0,
  `popularity` decimal(10,3) DEFAULT 0.000,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  FOREIGN KEY (`ID`) REFERENCES `wp_posts`(`ID`) ON DELETE CASCADE
);
```

#### 5. Cast/Crew Tables
```sql
-- Movies Cast
CREATE TABLE `wp_theme_movies_cast` (
  `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `movie_id` bigint(20) UNSIGNED NOT NULL,
  `person_id` bigint(20) UNSIGNED NOT NULL,
  `character_name` varchar(255) DEFAULT NULL,
  `order_index` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  FOREIGN KEY (`movie_id`) REFERENCES `wp_posts`(`ID`) ON DELETE CASCADE,
  FOREIGN KEY (`person_id`) REFERENCES `wp_posts`(`ID`) ON DELETE CASCADE,
  UNIQUE KEY `movie_person` (`movie_id`, `person_id`)
);

-- Movies Crew
CREATE TABLE `wp_theme_movies_crew` (
  `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `movie_id` bigint(20) UNSIGNED NOT NULL,
  `person_id` bigint(20) UNSIGNED NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `job` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  FOREIGN KEY (`movie_id`) REFERENCES `wp_posts`(`ID`) ON DELETE CASCADE,
  FOREIGN KEY (`person_id`) REFERENCES `wp_posts`(`ID`) ON DELETE CASCADE
);
```

## Custom Post Types

### 1. Movies Post Type
```php
<?php
// inc/post-types/movies.php

function register_movie_post_type() {
    $labels = array(
        'name' => 'Movies',
        'singular_name' => 'Movie',
        'menu_name' => 'Movies',
        'add_new' => 'Add New Movie',
        'add_new_item' => 'Add New Movie',
        'edit_item' => 'Edit Movie',
        'new_item' => 'New Movie',
        'view_item' => 'View Movie',
        'search_items' => 'Search Movies',
        'not_found' => 'No movies found',
        'not_found_in_trash' => 'No movies found in trash',
        'all_items' => 'All Movies',
        'archives' => 'Movie Archives',
        'attributes' => 'Movie Attributes',
        'insert_into_item' => 'Insert into movie',
        'uploaded_to_this_item' => 'Uploaded to this movie',
        'featured_image' => 'Poster',
        'set_featured_image' => 'Set poster',
        'remove_featured_image' => 'Remove poster',
        'use_featured_image' => 'Use as poster',
    );

    $args = array(
        'label' => 'Movies',
        'labels' => $labels,
        'description' => 'Movie database',
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => true,
        'show_in_rest' => true, // Gutenberg support
        'query_var' => true,
        'can_export' => true,
        'delete_with_user' => false,
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-video-alt3',
        'capability_type' => 'post',
        'supports' => array(
            'title',
            'editor',
            'thumbnail',
            'excerpt',
            'comments',
            'revisions',
            'custom-fields',
            'page-attributes'
        ),
        'taxonomies' => array('genre', 'country', 'language', 'by-year', 'keyword'),
        'rewrite' => array(
            'slug' => 'movies',
            'with_front' => false,
            'feeds' => true,
            'pages' => true
        ),
        'template' => array(
            array('core/paragraph', array(
                'placeholder' => 'Enter movie synopsis...'
            )),
            array('theme/movie-details'),
            array('theme/cast-crew'),
            array('theme/videos'),
            array('theme/gallery')
        )
    );

    register_post_type('movie', $args);
}
add_action('init', 'register_movie_post_type');
```

### 2. TV Series Post Type
```php
<?php
// inc/post-types/tv-series.php

function register_tv_series_post_type() {
    $labels = array(
        'name' => 'TV Series',
        'singular_name' => 'TV Series',
        'menu_name' => 'TV Series',
        'add_new' => 'Add New TV Series',
        'add_new_item' => 'Add New TV Series',
        'edit_item' => 'Edit TV Series',
        'new_item' => 'New TV Series',
        'view_item' => 'View TV Series',
        'search_items' => 'Search TV Series',
        'not_found' => 'No TV series found',
        'not_found_in_trash' => 'No TV series found in trash',
        'all_items' => 'All TV Series',
        'archives' => 'TV Series Archives',
        'attributes' => 'TV Series Attributes',
        'insert_into_item' => 'Insert into TV series',
        'uploaded_to_this_item' => 'Uploaded to this TV series',
        'featured_image' => 'Poster',
        'set_featured_image' => 'Set poster',
        'remove_featured_image' => 'Remove poster',
        'use_featured_image' => 'Use as poster',
    );

    $args = array(
        'label' => 'TV Series',
        'labels' => $labels,
        'description' => 'TV Series database',
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => true,
        'show_in_rest' => true, // Gutenberg support
        'query_var' => true,
        'can_export' => true,
        'delete_with_user' => false,
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-format-video',
        'capability_type' => 'post',
        'supports' => array(
            'title',
            'editor',
            'thumbnail',
            'excerpt',
            'comments',
            'revisions',
            'custom-fields',
            'page-attributes'
        ),
        'taxonomies' => array('genre', 'country', 'language', 'by-year', 'network', 'keyword'),
        'rewrite' => array(
            'slug' => 'tv-series',
            'with_front' => false,
            'feeds' => true,
            'pages' => true
        ),
        'template' => array(
            array('core/paragraph', array(
                'placeholder' => 'Enter TV series overview...'
            )),
            array('theme/tv-details'),
            array('theme/seasons-episodes'),
            array('theme/cast-crew'),
            array('theme/videos'),
            array('theme/gallery')
        )
    );

    register_post_type('tv-series', $args);
}
add_action('init', 'register_tv_series_post_type');
```

## Implementation Steps

### Step 1: Theme Setup (functions.php)
```php
<?php
// functions.php

// Theme setup
function theme_setup() {
    // Add theme support
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('custom-logo');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('customize-selective-refresh-widgets');
    
    // Add Gutenberg support
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
    add_editor_style('assets/css/editor-style.css');
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => 'Primary Menu',
        'footer' => 'Footer Menu'
    ));
    
    // Set content width
    $GLOBALS['content_width'] = 1200;
}
add_action('after_setup_theme', 'theme_setup');

// Enqueue styles and scripts
function theme_enqueue_scripts() {
    wp_enqueue_style('theme-style', get_stylesheet_uri(), array(), '1.0.0');
    wp_enqueue_style('theme-main', get_template_directory_uri() . '/assets/css/main.css', array(), '1.0.0');
    
    wp_enqueue_script('theme-main', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('theme-ajax', get_template_directory_uri() . '/assets/js/ajax.js', array('jquery'), '1.0.0', true);
    
    wp_localize_script('theme-ajax', 'theme_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('theme_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'theme_enqueue_scripts');

// Include theme files
require_once get_template_directory() . '/inc/setup/init.php';
require_once get_template_directory() . '/inc/setup/database.php';
require_once get_template_directory() . '/inc/setup/settings.php';

// Include post types
require_once get_template_directory() . '/inc/post-types/movies.php';
require_once get_template_directory() . '/inc/post-types/tv-series.php';
require_once get_template_directory() . '/inc/post-types/dramas.php';
require_once get_template_directory() . '/inc/post-types/episodes.php';
require_once get_template_directory() . '/inc/post-types/seasons.php';
require_once get_template_directory() . '/inc/post-types/people.php';
require_once get_template_directory() . '/inc/post-types/videos.php';

// Include taxonomies
require_once get_template_directory() . '/inc/taxonomies/genre.php';
require_once get_template_directory() . '/inc/taxonomies/country.php';
require_once get_template_directory() . '/inc/taxonomies/language.php';
require_once get_template_directory() . '/inc/taxonomies/by-year.php';
require_once get_template_directory() . '/inc/taxonomies/network.php';
require_once get_template_directory() . '/inc/taxonomies/keywords.php';

// Include custom fields
require_once get_template_directory() . '/inc/fields/movie-fields.php';
require_once get_template_directory() . '/inc/fields/tv-fields.php';
require_once get_template_directory() . '/inc/fields/drama-fields.php';
require_once get_template_directory() . '/inc/fields/episode-fields.php';
require_once get_template_directory() . '/inc/fields/season-fields.php';
require_once get_template_directory() . '/inc/fields/people-fields.php';

// Include API integration
require_once get_template_directory() . '/inc/api/tmdb-client.php';
require_once get_template_directory() . '/inc/api/movie-api.php';
require_once get_template_directory() . '/inc/api/tv-api.php';
require_once get_template_directory() . '/inc/api/drama-api.php';
require_once get_template_directory() . '/inc/api/credits-api.php';

// Include SEO
require_once get_template_directory() . '/inc/seo/meta-tags.php';
require_once get_template_directory() . '/inc/seo/schema.php';
require_once get_template_directory() . '/inc/seo/sitemap.php';
require_once get_template_directory() . '/inc/seo/breadcrumbs.php';

// Include Gutenberg blocks
require_once get_template_directory() . '/inc/blocks/init.php';

// Include utilities
require_once get_template_directory() . '/inc/utils/helpers.php';
require_once get_template_directory() . '/inc/utils/ratings.php';
require_once get_template_directory() . '/inc/utils/image-processing.php';
require_once get_template_directory() . '/inc/utils/cache.php';
```

### Step 2: Database Setup (inc/setup/database.php)
```php
<?php
// inc/setup/database.php

function create_custom_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Movies table
    $table_movies = $wpdb->prefix . 'theme_movies';
    $sql_movies = "CREATE TABLE $table_movies (
        ID bigint(20) UNSIGNED NOT NULL,
        tmdb_id bigint(20) DEFAULT NULL,
        release_date date DEFAULT NULL,
        release_timestamp bigint(20) DEFAULT NULL,
        original_title varchar(255) DEFAULT NULL,
        tagline text DEFAULT NULL,
        runtime int(11) DEFAULT NULL,
        certification varchar(50) DEFAULT NULL,
        budget bigint(20) DEFAULT NULL,
        revenue bigint(20) DEFAULT NULL,
        production_house text DEFAULT NULL,
        streaming_platforms text DEFAULT NULL,
        star_cast longtext DEFAULT NULL,
        credits longtext DEFAULT NULL,
        videos longtext DEFAULT NULL,
        images longtext DEFAULT NULL,
        average_rating decimal(3,1) DEFAULT 0.0,
        vote_count int(11) DEFAULT 0,
        popularity decimal(10,3) DEFAULT 0.000,
        total_average_rating decimal(3,1) DEFAULT 0.0,
        total_vote_count int(11) DEFAULT 0,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (ID),
        FOREIGN KEY (ID) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE
    ) $charset_collate;";
    
    // TV Series table
    $table_tv_series = $wpdb->prefix . 'theme_tv_series';
    $sql_tv_series = "CREATE TABLE $table_tv_series (
        ID bigint(20) UNSIGNED NOT NULL,
        tmdb_id bigint(20) DEFAULT NULL,
        release_date date DEFAULT NULL,
        release_timestamp bigint(20) DEFAULT NULL,
        original_title varchar(255) DEFAULT NULL,
        tagline text DEFAULT NULL,
        status varchar(50) DEFAULT NULL,
        runtime int(11) DEFAULT NULL,
        certification varchar(50) DEFAULT NULL,
        production_house text DEFAULT NULL,
        streaming_platforms text DEFAULT NULL,
        star_cast longtext DEFAULT NULL,
        credits longtext DEFAULT NULL,
        videos longtext DEFAULT NULL,
        images longtext DEFAULT NULL,
        seasons longtext DEFAULT NULL,
        last_season int(11) DEFAULT NULL,
        last_episode int(11) DEFAULT NULL,
        average_rating decimal(3,1) DEFAULT 0.0,
        vote_count int(11) DEFAULT 0,
        popularity decimal(10,3) DEFAULT 0.000,
        total_average_rating decimal(3,1) DEFAULT 0.0,
        total_vote_count int(11) DEFAULT 0,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (ID),
        FOREIGN KEY (ID) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE
    ) $charset_collate;";
    
    // Cast tables
    $table_movies_cast = $wpdb->prefix . 'theme_movies_cast';
    $sql_movies_cast = "CREATE TABLE $table_movies_cast (
        ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        movie_id bigint(20) UNSIGNED NOT NULL,
        person_id bigint(20) UNSIGNED NOT NULL,
        character_name varchar(255) DEFAULT NULL,
        order_index int(11) DEFAULT 0,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (ID),
        FOREIGN KEY (movie_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE,
        FOREIGN KEY (person_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE,
        UNIQUE KEY movie_person (movie_id, person_id)
    ) $charset_collate;";
    
    // Include WordPress upgrade functions
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Create tables
    dbDelta($sql_movies);
    dbDelta($sql_tv_series);
    dbDelta($sql_movies_cast);
    
    // Add options
    add_option('theme_db_version', '1.0');
    add_option('theme_movies_enabled', 'yes');
    add_option('theme_tv_series_enabled', 'yes');
    add_option('theme_dramas_enabled', 'yes');
}

// Run on theme activation
function theme_activation() {
    create_custom_tables();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'theme_activation');
```

### Step 3: TMDB API Integration (inc/api/tmdb-client.php)
```php
<?php
// inc/api/tmdb-client.php

class TMDB_Client {
    private $api_key;
    private $base_url = 'https://api.themoviedb.org/3';
    private $image_base_url = 'https://image.tmdb.org/t/p/';
    
    public function __construct() {
        $this->api_key = get_option('tmdb_api_key', '');
    }
    
    public function get_movie($tmdb_id) {
        $url = $this->base_url . "/movie/{$tmdb_id}";
        $response = $this->make_request($url);
        
        if ($response) {
            return $this->process_movie_data($response);
        }
        
        return false;
    }
    
    public function get_movie_credits($tmdb_id) {
        $url = $this->base_url . "/movie/{$tmdb_id}/credits";
        $response = $this->make_request($url);
        
        if ($response) {
            return $this->process_credits_data($response);
        }
        
        return false;
    }
    
    public function get_movie_videos($tmdb_id) {
        $url = $this->base_url . "/movie/{$tmdb_id}/videos";
        $response = $this->make_request($url);
        
        if ($response) {
            return $this->process_videos_data($response);
        }
        
        return false;
    }
    
    public function get_movie_images($tmdb_id) {
        $url = $this->base_url . "/movie/{$tmdb_id}/images";
        $response = $this->make_request($url);
        
        if ($response) {
            return $this->process_images_data($response);
        }
        
        return false;
    }
    
    private function make_request($url) {
        $url = add_query_arg('api_key', $this->api_key, $url);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || isset($data['success']) && $data['success'] === false) {
            return false;
        }
        
        return $data;
    }
    
    private function process_movie_data($data) {
        return array(
            'title' => $data['title'],
            'original_title' => $data['original_title'],
            'overview' => $data['overview'],
            'release_date' => $data['release_date'],
            'runtime' => $data['runtime'],
            'tagline' => $data['tagline'],
            'budget' => $data['budget'],
            'revenue' => $data['revenue'],
            'vote_average' => $data['vote_average'],
            'vote_count' => $data['vote_count'],
            'popularity' => $data['popularity'],
            'poster_path' => $data['poster_path'],
            'backdrop_path' => $data['backdrop_path'],
            'genres' => $data['genres'],
            'production_companies' => $data['production_companies'],
            'production_countries' => $data['production_countries'],
            'spoken_languages' => $data['spoken_languages']
        );
    }
    
    private function process_credits_data($data) {
        $cast = array();
        $crew = array();
        
        if (isset($data['cast'])) {
            foreach ($data['cast'] as $member) {
                $cast[] = array(
                    'tmdb_id' => $member['id'],
                    'name' => $member['name'],
                    'character' => $member['character'],
                    'order' => $member['order'],
                    'profile_path' => $member['profile_path']
                );
            }
        }
        
        if (isset($data['crew'])) {
            foreach ($data['crew'] as $member) {
                $crew[] = array(
                    'tmdb_id' => $member['id'],
                    'name' => $member['name'],
                    'job' => $member['job'],
                    'department' => $member['department'],
                    'profile_path' => $member['profile_path']
                );
            }
        }
        
        return array(
            'cast' => $cast,
            'crew' => $crew
        );
    }
    
    private function process_videos_data($data) {
        $videos = array();
        
        if (isset($data['results'])) {
            foreach ($data['results'] as $video) {
                if ($video['site'] === 'YouTube') {
                    $videos[] = array(
                        'key' => $video['key'],
                        'name' => $video['name'],
                        'type' => $video['type'],
                        'site' => $video['site'],
                        'size' => $video['size'],
                        'official' => $video['official']
                    );
                }
            }
        }
        
        return $videos;
    }
    
    private function process_images_data($data) {
        $images = array();
        
        if (isset($data['backdrops'])) {
            foreach ($data['backdrops'] as $image) {
                $images[] = array(
                    'file_path' => $image['file_path'],
                    'width' => $image['width'],
                    'height' => $image['height'],
                    'type' => 'backdrop'
                );
            }
        }
        
        if (isset($data['posters'])) {
            foreach ($data['posters'] as $image) {
                $images[] = array(
                    'file_path' => $image['file_path'],
                    'width' => $image['width'],
                    'height' => $image['height'],
                    'type' => 'poster'
                );
            }
        }
        
        return $images;
    }
    
    public function get_image_url($file_path, $size = 'original') {
        return $this->image_base_url . $size . $file_path;
    }
}
```

### Step 4: Custom Fields with ACF Integration (inc/fields/movie-fields.php)
```php
<?php
// inc/fields/movie-fields.php

function register_movie_fields() {
    if (function_exists('acf_add_local_field_group')) {
        acf_add_local_field_group(array(
            'key' => 'group_movie_details',
            'title' => 'Movie Details',
            'fields' => array(
                array(
                    'key' => 'field_tmdb_id',
                    'label' => 'TMDB ID',
                    'name' => 'tmdb_id',
                    'type' => 'number',
                    'instructions' => 'Enter TMDB ID to auto-populate movie data',
                    'required' => 0,
                    'wrapper' => array(
                        'width' => '25',
                    ),
                ),
                array(
                    'key' => 'field_original_title',
                    'label' => 'Original Title',
                    'name' => 'original_title',
                    'type' => 'text',
                    'wrapper' => array(
                        'width' => '50',
                    ),
                ),
                array(
                    'key' => 'field_release_date',
                    'label' => 'Release Date',
                    'name' => 'release_date',
                    'type' => 'date_picker',
                    'display_format' => 'F j, Y',
                    'return_format' => 'Y-m-d',
                    'wrapper' => array(
                        'width' => '25',
                    ),
                ),
                array(
                    'key' => 'field_tagline',
                    'label' => 'Tagline',
                    'name' => 'tagline',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_runtime',
                    'label' => 'Runtime (minutes)',
                    'name' => 'runtime',
                    'type' => 'number',
                    'wrapper' => array(
                        'width' => '25',
                    ),
                ),
                array(
                    'key' => 'field_certification',
                    'label' => 'Certification',
                    'name' => 'certification',
                    'type' => 'select',
                    'choices' => array(
                        'G' => 'G',
                        'PG' => 'PG',
                        'PG-13' => 'PG-13',
                        'R' => 'R',
                        'NC-17' => 'NC-17',
                        'NR' => 'NR'
                    ),
                    'wrapper' => array(
                        'width' => '25',
                    ),
                ),
                array(
                    'key' => 'field_budget',
                    'label' => 'Budget ($)',
                    'name' => 'budget',
                    'type' => 'number',
                    'wrapper' => array(
                        'width' => '25',
                    ),
                ),
                array(
                    'key' => 'field_revenue',
                    'label' => 'Revenue ($)',
                    'name' => 'revenue',
                    'type' => 'number',
                    'wrapper' => array(
                        'width' => '25',
                    ),
                ),
                array(
                    'key' => 'field_production_house',
                    'label' => 'Production House',
                    'name' => 'production_house',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_streaming_platforms',
                    'label' => 'Streaming Platforms',
                    'name' => 'streaming_platforms',
                    'type' => 'textarea',
                    'instructions' => 'Where to watch this movie',
                    'rows' => 2,
                ),
                array(
                    'key' => 'field_star_cast',
                    'label' => 'Star Cast',
                    'name' => 'star_cast',
                    'type' => 'repeater',
                    'layout' => 'table',
                    'max' => 4,
                    'sub_fields' => array(
                        array(
                            'key' => 'field_cast_person',
                            'label' => 'Person',
                            'name' => 'person',
                            'type' => 'post_object',
                            'post_type' => array('person'),
                            'return_format' => 'id',
                        ),
                        array(
                            'key' => 'field_cast_character',
                            'label' => 'Character',
                            'name' => 'character',
                            'type' => 'text',
                        ),
                    ),
                ),
                array(
                    'key' => 'field_fetch_from_tmdb',
                    'label' => 'Fetch Data from TMDB',
                    'name' => 'fetch_from_tmdb',
                    'type' => 'checkbox',
                    'choices' => array(
                        'details' => 'Basic Details',
                        'cast' => 'Cast & Crew',
                        'videos' => 'Videos',
                        'images' => 'Images',
                    ),
                    'layout' => 'horizontal',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'movie',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
        ));
    }
}
add_action('acf/init', 'register_movie_fields');
```

### Step 5: Gutenberg Block Registration (inc/blocks/init.php)
```php
<?php
// inc/blocks/init.php

function register_theme_blocks() {
    // Register Movie Card Block
    register_block_type(get_template_directory() . '/inc/blocks/movie-card');
    
    // Register TV Card Block
    register_block_type(get_template_directory() . '/inc/blocks/tv-card');
    
    // Register Drama Card Block
    register_block_type(get_template_directory() . '/inc/blocks/drama-card');
    
    // Register Cast & Crew Block
    register_block_type(get_template_directory() . '/inc/blocks/cast-crew');
    
    // Register Rating System Block
    register_block_type(get_template_directory() . '/inc/blocks/rating-system');
    
    // Register Video Player Block
    register_block_type(get_template_directory() . '/inc/blocks/video-player');
}
add_action('init', 'register_theme_blocks');

// Enqueue block editor assets
function theme_block_editor_assets() {
    wp_enqueue_script(
        'theme-blocks-js',
        get_template_directory_uri() . '/assets/js/blocks.js',
        array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor'),
        '1.0.0',
        true
    );
    
    wp_enqueue_style(
        'theme-blocks-css',
        get_template_directory_uri() . '/assets/css/blocks.css',
        array(),
        '1.0.0'
    );
}
add_action('enqueue_block_editor_assets', 'theme_block_editor_assets');

// Add block categories
function theme_block_categories($categories) {
    return array_merge(
        $categories,
        array(
            array(
                'slug' => 'movie-theme',
                'title' => 'Movie Theme Blocks',
                'icon' => 'video-alt3',
            ),
        )
    );
}
add_filter('block_categories_all', 'theme_block_categories');
```

This documentation provides a comprehensive foundation for building a WordPress theme for movies, TV shows, and dramas. Each step can be implemented using Cursor 3.7 by following the detailed code examples and structure provided.

The theme includes:
- Complete custom post type system
- Advanced database schema
- TMDB API integration
- Gutenberg block support
- SEO optimization
- Responsive design
- Admin interface
- Rating system
- Image galleries
- Video integration
- Cast & crew management
- Advanced search and filtering

Each component is modular and can be implemented step by step, making it easy to build and customize according to specific requirements.