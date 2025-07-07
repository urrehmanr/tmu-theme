<?php
/**
 * Database Test Case
 * 
 * Base test case for tests that require database operations.
 * 
 * @package TMU\Tests\Utilities
 * @since 1.0.0
 */

namespace TMU\Tests\Utilities;

use WP_UnitTestCase;

/**
 * DatabaseTestCase class
 * 
 * Base class for database-related tests
 */
class DatabaseTestCase extends WP_UnitTestCase {
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Ensure clean database state
        $this->clean_up_test_data();
        
        // Initialize theme components if needed
        $this->init_theme_components();
    }
    
    /**
     * Tear down test environment
     */
    public function tearDown(): void {
        // Clean up test data
        $this->clean_up_test_data();
        
        parent::tearDown();
    }
    
    /**
     * Initialize theme components
     */
    protected function init_theme_components(): void {
        // Register post types and taxonomies if not already registered
        if (!post_type_exists('movie')) {
            $this->register_test_post_types();
        }
        
        if (!taxonomy_exists('genre')) {
            $this->register_test_taxonomies();
        }
        
        // Create custom tables if they don't exist
        $this->create_test_tables();
    }
    
    /**
     * Register test post types
     */
    protected function register_test_post_types(): void {
        // Movie post type
        register_post_type('movie', [
            'public' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'capability_type' => 'post',
            'has_archive' => true,
            'rewrite' => ['slug' => 'movies'],
            'taxonomies' => ['genre', 'country', 'language']
        ]);
        
        // TV post type
        register_post_type('tv', [
            'public' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
            'capability_type' => 'post',
            'has_archive' => true,
            'rewrite' => ['slug' => 'tv-shows'],
            'taxonomies' => ['genre', 'country', 'language']
        ]);
        
        // People post type
        register_post_type('people', [
            'public' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'capability_type' => 'post',
            'has_archive' => true,
            'rewrite' => ['slug' => 'people']
        ]);
    }
    
    /**
     * Register test taxonomies
     */
    protected function register_test_taxonomies(): void {
        // Genre taxonomy
        register_taxonomy('genre', ['movie', 'tv'], [
            'public' => true,
            'hierarchical' => false,
            'rewrite' => ['slug' => 'genre']
        ]);
        
        // Country taxonomy
        register_taxonomy('country', ['movie', 'tv'], [
            'public' => true,
            'hierarchical' => false,
            'rewrite' => ['slug' => 'country']
        ]);
        
        // Language taxonomy
        register_taxonomy('language', ['movie', 'tv'], [
            'public' => true,
            'hierarchical' => false,
            'rewrite' => ['slug' => 'language']
        ]);
        
        // By year taxonomy
        register_taxonomy('by-year', ['movie', 'tv'], [
            'public' => true,
            'hierarchical' => false,
            'rewrite' => ['slug' => 'year']
        ]);
    }
    
    /**
     * Create test database tables
     */
    protected function create_test_tables(): void {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Movies table
        $movies_table = $wpdb->prefix . 'tmu_movies';
        $movies_sql = "CREATE TABLE IF NOT EXISTS {$movies_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            tmdb_id int(11) DEFAULT NULL,
            title varchar(255) DEFAULT NULL,
            original_title varchar(255) DEFAULT NULL,
            overview text DEFAULT NULL,
            tagline varchar(500) DEFAULT NULL,
            release_date date DEFAULT NULL,
            runtime int(11) DEFAULT NULL,
            budget bigint(20) DEFAULT NULL,
            revenue bigint(20) DEFAULT NULL,
            vote_average decimal(3,1) DEFAULT NULL,
            vote_count int(11) DEFAULT NULL,
            popularity decimal(10,3) DEFAULT NULL,
            status varchar(50) DEFAULT NULL,
            homepage varchar(500) DEFAULT NULL,
            poster_path varchar(255) DEFAULT NULL,
            backdrop_path varchar(255) DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id),
            UNIQUE KEY tmdb_id (tmdb_id),
            KEY release_date (release_date),
            KEY vote_average (vote_average)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($movies_sql);
        
        // TV Series table
        $tv_table = $wpdb->prefix . 'tmu_tv_series';
        $tv_sql = "CREATE TABLE IF NOT EXISTS {$tv_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            tmdb_id int(11) DEFAULT NULL,
            name varchar(255) DEFAULT NULL,
            original_name varchar(255) DEFAULT NULL,
            overview text DEFAULT NULL,
            tagline varchar(500) DEFAULT NULL,
            first_air_date date DEFAULT NULL,
            last_air_date date DEFAULT NULL,
            number_of_episodes int(11) DEFAULT NULL,
            number_of_seasons int(11) DEFAULT NULL,
            episode_run_time varchar(255) DEFAULT NULL,
            vote_average decimal(3,1) DEFAULT NULL,
            vote_count int(11) DEFAULT NULL,
            popularity decimal(10,3) DEFAULT NULL,
            status varchar(50) DEFAULT NULL,
            type varchar(50) DEFAULT NULL,
            homepage varchar(500) DEFAULT NULL,
            poster_path varchar(255) DEFAULT NULL,
            backdrop_path varchar(255) DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id),
            UNIQUE KEY tmdb_id (tmdb_id),
            KEY first_air_date (first_air_date),
            KEY vote_average (vote_average)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($tv_sql);
        
        // People table
        $people_table = $wpdb->prefix . 'tmu_people';
        $people_sql = "CREATE TABLE IF NOT EXISTS {$people_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            tmdb_id int(11) DEFAULT NULL,
            name varchar(255) DEFAULT NULL,
            biography text DEFAULT NULL,
            birthday date DEFAULT NULL,
            deathday date DEFAULT NULL,
            place_of_birth varchar(255) DEFAULT NULL,
            known_for_department varchar(100) DEFAULT NULL,
            gender tinyint(1) DEFAULT NULL,
            popularity decimal(10,3) DEFAULT NULL,
            profile_path varchar(255) DEFAULT NULL,
            homepage varchar(500) DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id),
            UNIQUE KEY tmdb_id (tmdb_id),
            KEY birthday (birthday),
            KEY popularity (popularity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($people_sql);
        
        // Cast table
        $cast_table = $wpdb->prefix . 'tmu_cast';
        $cast_sql = "CREATE TABLE IF NOT EXISTS {$cast_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            person_id bigint(20) NOT NULL,
            character_name varchar(255) DEFAULT NULL,
            cast_order int(11) DEFAULT NULL,
            credit_id varchar(50) DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY person_id (person_id),
            KEY cast_order (cast_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($cast_sql);
        
        // Videos table
        $videos_table = $wpdb->prefix . 'tmu_videos';
        $videos_sql = "CREATE TABLE IF NOT EXISTS {$videos_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            video_key varchar(255) NOT NULL,
            name varchar(255) DEFAULT NULL,
            site varchar(50) DEFAULT NULL,
            size int(11) DEFAULT NULL,
            type varchar(50) DEFAULT NULL,
            official tinyint(1) DEFAULT 0,
            published_at timestamp NULL DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY video_key (video_key),
            KEY type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($videos_sql);
    }
    
    /**
     * Clean up test data
     */
    protected function clean_up_test_data(): void {
        global $wpdb;
        
        // Remove test posts
        $test_post_types = ['movie', 'tv', 'people'];
        foreach ($test_post_types as $post_type) {
            $posts = get_posts([
                'post_type' => $post_type,
                'numberposts' => -1,
                'post_status' => 'any'
            ]);
            
            foreach ($posts as $post) {
                wp_delete_post($post->ID, true);
            }
        }
        
        // Clean custom tables
        $custom_tables = [
            $wpdb->prefix . 'tmu_movies',
            $wpdb->prefix . 'tmu_tv_series',
            $wpdb->prefix . 'tmu_people',
            $wpdb->prefix . 'tmu_cast',
            $wpdb->prefix . 'tmu_videos'
        ];
        
        foreach ($custom_tables as $table) {
            $wpdb->query("TRUNCATE TABLE {$table}");
        }
        
        // Clean test terms
        $test_taxonomies = ['genre', 'country', 'language', 'by-year'];
        foreach ($test_taxonomies as $taxonomy) {
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ]);
            
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $taxonomy);
            }
        }
        
        // Clean test users
        $test_users = get_users([
            'meta_key' => '_test_user',
            'meta_value' => '1'
        ]);
        
        foreach ($test_users as $user) {
            wp_delete_user($user->ID);
        }
        
        // Clear cache
        wp_cache_flush();
    }
    
    /**
     * Create movie with full test data
     * 
     * @param array $args Movie arguments
     * @return int Post ID
     */
    protected function create_movie($args = []): int {
        return TestHelper::create_movie($args);
    }
    
    /**
     * Create TV show with full test data
     * 
     * @param array $args TV show arguments
     * @return int Post ID
     */
    protected function create_tv_show($args = []): int {
        return TestHelper::create_tv_show($args);
    }
    
    /**
     * Create person with full test data
     * 
     * @param array $args Person arguments
     * @return int Post ID
     */
    protected function create_person($args = []): int {
        return TestHelper::create_person($args);
    }
    
    /**
     * Create test term
     * 
     * @param string $name Term name
     * @param string $taxonomy Taxonomy
     * @return int Term ID
     */
    protected function create_term($name, $taxonomy = 'category'): int {
        return TestHelper::create_term($name, $taxonomy);
    }
    
    /**
     * Create test attachment
     * 
     * @param int $parent_id Parent post ID
     * @param string $filename Filename
     * @return int Attachment ID
     */
    protected function create_attachment($parent_id = 0, $filename = 'test-image.jpg'): int {
        return TestHelper::create_attachment($parent_id, $filename);
    }
    
    /**
     * Add cast member to movie
     * 
     * @param int $movie_id Movie post ID
     * @param int $person_id Person post ID
     * @param string $character Character name
     */
    protected function add_cast_member($movie_id, $person_id, $character = 'Test Character'): void {
        TestHelper::add_cast_member($movie_id, $person_id, $character);
    }
    
    /**
     * Assert tables exist
     */
    protected function assert_tables_exist(): void {
        global $wpdb;
        
        $required_tables = [
            'tmu_movies',
            'tmu_tv_series',
            'tmu_people',
            'tmu_cast',
            'tmu_videos'
        ];
        
        foreach ($required_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $result = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
            $this->assertEquals($table_name, $result, "Table '{$table}' should exist");
        }
    }
    
    /**
     * Assert initial data exists
     */
    protected function assert_initial_data_exists(): void {
        // Check if default taxonomies have terms
        $genre_terms = get_terms(['taxonomy' => 'genre', 'hide_empty' => false]);
        $this->assertNotEmpty($genre_terms, 'Genre taxonomy should have initial terms');
        
        // Check if options are set
        $this->assertNotEmpty(get_option('tmu_theme_version'), 'Theme version should be set');
    }
    
    /**
     * Create movie with full metadata
     * 
     * @return int Movie post ID
     */
    protected function create_movie_with_full_data(): int {
        $movie_id = $this->create_movie([
            'title' => 'Full Data Movie',
            'overview' => 'A movie with complete metadata for testing',
            'tmdb_id' => 12345,
            'vote_average' => 8.5,
            'runtime' => 120
        ]);
        
        // Add multiple images
        for ($i = 0; $i < 5; $i++) {
            $this->create_attachment($movie_id, "poster-{$i}.jpg");
        }
        
        // Add cast and crew
        for ($i = 0; $i < 10; $i++) {
            $person_id = $this->create_person(['title' => "Actor {$i}"]);
            $this->add_cast_member($movie_id, $person_id, "Character {$i}");
        }
        
        return $movie_id;
    }
    
    /**
     * Simulate page visit for testing
     * 
     * @param string $url URL to visit
     */
    protected function go_to($url): void {
        TestHelper::go_to($url);
    }
    
    /**
     * Get page content for testing
     * 
     * @return string Page content
     */
    protected function get_page_content(): string {
        return TestHelper::get_page_content();
    }
}