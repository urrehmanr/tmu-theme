<?php
/**
 * Theme Integration Test
 * 
 * Integration tests for the TMU theme functionality.
 * 
 * @package TMU\Tests\Integration
 * @since 1.0.0
 */

namespace TMU\Tests\Integration;

use TMU\Tests\Utilities\DatabaseTestCase;

/**
 * ThemeIntegrationTest class
 * 
 * Integration tests for theme components
 */
class ThemeIntegrationTest extends DatabaseTestCase {
    
    /**
     * Test theme activation
     */
    public function test_theme_activation(): void {
        // Theme should activate without errors
        $this->assertTrue(wp_get_theme()->exists(), 'Theme should exist');
        
        $theme_name = wp_get_theme()->get('Name');
        $this->assertStringContains('TMU', $theme_name, 'Theme name should contain TMU');
    }
    
    /**
     * Test required post types registered
     */
    public function test_required_post_types_registered(): void {
        $required_post_types = ['movie', 'tv', 'people', 'video', 'episode', 'season'];
        
        foreach ($required_post_types as $post_type) {
            $this->assertTrue(
                post_type_exists($post_type), 
                "Post type '{$post_type}' should be registered"
            );
        }
        
        // Drama post type should be registered if enabled
        if (get_option('tmu_dramas') === 'on') {
            $this->assertTrue(post_type_exists('drama'), 'Drama post type should be registered when enabled');
            $this->assertTrue(post_type_exists('drama-episode'), 'Drama episode post type should be registered when enabled');
        }
    }
    
    /**
     * Test required taxonomies registered
     */
    public function test_required_taxonomies_registered(): void {
        $required_taxonomies = ['genre', 'country', 'language', 'by-year'];
        
        foreach ($required_taxonomies as $taxonomy) {
            $this->assertTrue(
                taxonomy_exists($taxonomy), 
                "Taxonomy '{$taxonomy}' should be registered"
            );
        }
    }
    
    /**
     * Test custom tables created
     */
    public function test_custom_tables_created(): void {
        global $wpdb;
        
        $required_tables = [
            'tmu_movies',
            'tmu_tv_series',
            'tmu_people',
            'tmu_videos'
        ];
        
        foreach ($required_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $result = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
            $this->assertEquals($table_name, $result, "Table '{$table}' should exist");
        }
    }
    
    /**
     * Test menu locations registered
     */
    public function test_menu_locations_registered(): void {
        $menu_locations = get_registered_nav_menus();
        
        $required_locations = ['primary', 'footer'];
        
        foreach ($required_locations as $location) {
            $this->assertArrayHasKey($location, $menu_locations, "Menu location '{$location}' should be registered");
        }
    }
    
    /**
     * Test image sizes registered
     */
    public function test_image_sizes_registered(): void {
        global $_wp_additional_image_sizes;
        
        $required_sizes = ['movie-poster', 'tv-poster', 'person-photo'];
        
        foreach ($required_sizes as $size) {
            $this->assertTrue(
                has_image_size($size) || isset($_wp_additional_image_sizes[$size]),
                "Image size '{$size}' should be registered"
            );
        }
    }
    
    /**
     * Test widget areas registered
     */
    public function test_widget_areas_registered(): void {
        global $wp_registered_sidebars;
        
        $required_sidebars = ['sidebar-main', 'footer-1', 'footer-2', 'footer-3'];
        
        foreach ($required_sidebars as $sidebar) {
            $this->assertArrayHasKey(
                $sidebar, 
                $wp_registered_sidebars, 
                "Sidebar '{$sidebar}' should be registered"
            );
        }
    }
    
    /**
     * Test theme supports
     */
    public function test_theme_supports(): void {
        $required_supports = [
            'post-thumbnails',
            'title-tag',
            'custom-logo',
            'html5',
            'automatic-feed-links'
        ];
        
        foreach ($required_supports as $support) {
            $this->assertTrue(
                current_theme_supports($support),
                "Theme should support '{$support}'"
            );
        }
    }
    
    /**
     * Test enqueued scripts and styles
     */
    public function test_enqueued_assets(): void {
        // Trigger asset enqueuing
        do_action('wp_enqueue_scripts');
        
        // Test main theme stylesheet
        $this->assertTrue(wp_style_is('tmu-main-style', 'enqueued'), 'Main stylesheet should be enqueued');
        
        // Test main theme script
        $this->assertTrue(wp_script_is('tmu-main-script', 'enqueued'), 'Main script should be enqueued');
        
        // Test admin assets (if in admin)
        if (is_admin()) {
            do_action('admin_enqueue_scripts');
            $this->assertTrue(wp_style_is('tmu-admin-style', 'enqueued'), 'Admin stylesheet should be enqueued');
        }
    }
    
    /**
     * Test template hierarchy
     */
    public function test_template_hierarchy(): void {
        // Test movie single template
        $movie_id = $this->create_movie(['title' => 'Template Test Movie']);
        
        $template = get_single_template();
        $movie_template = locate_template(['single-movie.php']);
        
        if ($movie_template) {
            $this->assertFileExists($movie_template, 'Movie single template should exist');
        }
        
        // Test movie archive template
        $archive_template = locate_template(['archive-movie.php']);
        if ($archive_template) {
            $this->assertFileExists($archive_template, 'Movie archive template should exist');
        }
        
        // Test search template
        $search_template = locate_template(['search.php']);
        if ($search_template) {
            $this->assertFileExists($search_template, 'Search template should exist');
        }
    }
    
    /**
     * Test AJAX endpoints
     */
    public function test_ajax_endpoints(): void {
        // Test public AJAX endpoint
        $this->assertTrue(has_action('wp_ajax_nopriv_tmu_search'), 'Public search AJAX endpoint should be registered');
        $this->assertTrue(has_action('wp_ajax_tmu_search'), 'Logged-in search AJAX endpoint should be registered');
        
        // Test admin AJAX endpoints
        $admin_endpoints = ['tmu_save_data', 'tmu_sync_movie', 'tmu_bulk_sync'];
        
        foreach ($admin_endpoints as $endpoint) {
            $this->assertTrue(
                has_action("wp_ajax_{$endpoint}"),
                "Admin AJAX endpoint '{$endpoint}' should be registered"
            );
        }
    }
    
    /**
     * Test REST API endpoints
     */
    public function test_rest_api_endpoints(): void {
        // Get REST routes
        $routes = rest_get_server()->get_routes();
        
        $expected_routes = [
            '/tmu/v1/movies',
            '/tmu/v1/tv-shows',
            '/tmu/v1/people',
            '/tmu/v1/search'
        ];
        
        foreach ($expected_routes as $route) {
            $this->assertArrayHasKey($route, $routes, "REST route '{$route}' should be registered");
        }
    }
    
    /**
     * Test custom capabilities
     */
    public function test_custom_capabilities(): void {
        $admin_role = get_role('administrator');
        
        if ($admin_role) {
            $required_caps = [
                'edit_movies',
                'edit_others_movies',
                'publish_movies',
                'read_private_movies',
                'delete_movies'
            ];
            
            foreach ($required_caps as $cap) {
                $this->assertTrue(
                    $admin_role->has_cap($cap),
                    "Administrator should have '{$cap}' capability"
                );
            }
        }
    }
    
    /**
     * Test rewrite rules
     */
    public function test_rewrite_rules(): void {
        global $wp_rewrite;
        
        // Flush rewrite rules to ensure they're current
        flush_rewrite_rules();
        
        $rules = get_option('rewrite_rules');
        
        // Test movie archive rule
        $movie_rule_found = false;
        foreach ($rules as $pattern => $replacement) {
            if (strpos($pattern, 'movies') !== false) {
                $movie_rule_found = true;
                break;
            }
        }
        
        $this->assertTrue($movie_rule_found, 'Movie rewrite rules should exist');
    }
    
    /**
     * Test database integration
     */
    public function test_database_integration(): void {
        // Create a movie and verify it's saved to both posts and custom table
        $movie_id = $this->create_movie([
            'title' => 'Database Integration Test',
            'tmdb_id' => 99999,
            'vote_average' => 9.0
        ]);
        
        // Check WordPress post
        $post = get_post($movie_id);
        $this->assertNotNull($post, 'Movie should be saved as WordPress post');
        $this->assertEquals('movie', $post->post_type, 'Post type should be movie');
        
        // Check custom table (if function exists)
        if (function_exists('tmu_get_movie_data')) {
            $movie_data = tmu_get_movie_data($movie_id);
            if (!empty($movie_data)) {
                $this->assertEquals(99999, $movie_data['tmdb_id'], 'TMDB ID should be saved in custom table');
                $this->assertEquals(9.0, $movie_data['vote_average'], 'Vote average should be saved in custom table');
            }
        }
    }
    
    /**
     * Test search integration
     */
    public function test_search_integration(): void {
        // Create test content
        $movie_id = $this->create_movie([
            'title' => 'Search Integration Movie',
            'post_content' => 'This is a movie for testing search functionality'
        ]);
        
        // Test WordPress search
        $search_query = new \WP_Query([
            'post_type' => 'movie',
            's' => 'Integration',
            'posts_per_page' => 10
        ]);
        
        $this->assertTrue($search_query->have_posts(), 'Search should find movies');
        
        // Test custom search (if available)
        if (class_exists('TMU\\Search\\SearchEngine')) {
            $search_engine = new \TMU\Search\SearchEngine();
            $results = $search_engine->search('Integration');
            $this->assertGreaterThan(0, $results->get_total(), 'Custom search should find results');
        }
    }
    
    /**
     * Test cache integration
     */
    public function test_cache_integration(): void {
        $movie_id = $this->create_movie(['title' => 'Cache Test Movie']);
        
        // Test object cache
        $cache_key = "tmu_movie_{$movie_id}";
        $test_data = ['test' => 'data'];
        
        wp_cache_set($cache_key, $test_data);
        $cached_data = wp_cache_get($cache_key);
        
        $this->assertEquals($test_data, $cached_data, 'Object cache should work');
        
        // Test transients
        $transient_key = "tmu_test_transient_{$movie_id}";
        set_transient($transient_key, $test_data, 3600);
        $transient_data = get_transient($transient_key);
        
        $this->assertEquals($test_data, $transient_data, 'Transients should work');
        
        // Clean up
        delete_transient($transient_key);
    }
    
    /**
     * Test multisite compatibility
     */
    public function test_multisite_compatibility(): void {
        if (!is_multisite()) {
            $this->markTestSkipped('Multisite not enabled');
            return;
        }
        
        // Test that theme works on multiple sites
        $current_blog_id = get_current_blog_id();
        
        // Test on main site
        switch_to_blog(1);
        $this->assertTrue(post_type_exists('movie'), 'Movie post type should exist on main site');
        
        // Switch back
        switch_to_blog($current_blog_id);
        $this->assertTrue(post_type_exists('movie'), 'Movie post type should exist on current site');
    }
}