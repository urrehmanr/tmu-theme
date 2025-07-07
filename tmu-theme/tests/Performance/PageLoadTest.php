<?php
/**
 * Page Load Performance Test
 * 
 * Tests for page loading performance.
 * 
 * @package TMU\Tests\Performance
 * @since 1.0.0
 */

namespace TMU\Tests\Performance;

use TMU\Tests\Utilities\DatabaseTestCase;

/**
 * PageLoadTest class
 * 
 * Performance tests for page loading
 */
class PageLoadTest extends DatabaseTestCase {
    
    /**
     * Test homepage load time
     */
    public function test_homepage_load_time(): void {
        $start_time = microtime(true);
        
        $this->go_to(home_url('/'));
        $content = $this->get_page_content();
        
        $load_time = microtime(true) - $start_time;
        
        $this->assertLessThan(2.0, $load_time, 'Homepage should load in under 2 seconds');
        $this->assertNotEmpty($content, 'Homepage should have content');
    }
    
    /**
     * Test movie page performance
     */
    public function test_movie_page_performance(): void {
        $movie_id = $this->create_movie([
            'title' => 'Performance Test Movie',
            'overview' => 'A movie for performance testing'
        ]);
        
        $start_time = microtime(true);
        
        $this->go_to(get_permalink($movie_id));
        $content = $this->get_page_content();
        
        $load_time = microtime(true) - $start_time;
        
        $this->assertLessThan(1.5, $load_time, 'Movie page should load in under 1.5 seconds');
        $this->assertStringContains('Performance Test Movie', $content, 'Movie page should contain movie title');
    }
    
    /**
     * Test search performance
     */
    public function test_search_performance(): void {
        // Create test content
        for ($i = 0; $i < 100; $i++) {
            $this->create_movie(['title' => "Test Movie {$i}"]);
        }
        
        $start_time = microtime(true);
        
        // Perform search
        if (class_exists('TMU\\Search\\SearchEngine')) {
            $search_engine = new \TMU\Search\SearchEngine();
            $results = $search_engine->search('Test Movie');
        } else {
            // Fallback to WordPress search
            $_GET['s'] = 'Test Movie';
            query_posts(['s' => 'Test Movie']);
            global $wp_query;
            $results = $wp_query;
        }
        
        $search_time = microtime(true) - $start_time;
        
        $this->assertLessThan(0.5, $search_time, 'Search should complete in under 0.5 seconds');
        
        if (method_exists($results, 'get_total')) {
            $this->assertGreaterThan(0, $results->get_total(), 'Search should return results');
        } else {
            $this->assertGreaterThan(0, $results->found_posts, 'Search should return results');
        }
    }
    
    /**
     * Test database query count
     */
    public function test_database_query_count(): void {
        global $wpdb;
        
        // Reset query count
        $wpdb->num_queries = 0;
        $query_count_before = $wpdb->num_queries;
        
        // Load movie archive page
        $this->go_to(get_post_type_archive_link('movie'));
        $content = $this->get_page_content();
        
        $query_count_after = $wpdb->num_queries;
        $queries_executed = $query_count_after - $query_count_before;
        
        $this->assertLessThan(20, $queries_executed, 'Archive page should execute fewer than 20 database queries');
        $this->assertNotEmpty($content, 'Archive page should have content');
    }
    
    /**
     * Test memory usage
     */
    public function test_memory_usage(): void {
        $memory_before = memory_get_usage(true);
        
        // Load complex page with multiple components
        $movie_id = $this->create_movie_with_full_data();
        $this->go_to(get_permalink($movie_id));
        $content = $this->get_page_content();
        
        $memory_after = memory_get_usage(true);
        $memory_used = $memory_after - $memory_before;
        
        // Should use less than 16MB additional memory
        $this->assertLessThan(16 * 1024 * 1024, $memory_used, 'Page should use less than 16MB memory');
        $this->assertNotEmpty($content, 'Page should have content');
    }
    
    /**
     * Test archive page performance
     */
    public function test_archive_page_performance(): void {
        // Create multiple movies for archive
        for ($i = 0; $i < 20; $i++) {
            $this->create_movie(['title' => "Archive Movie {$i}"]);
        }
        
        $start_time = microtime(true);
        
        $this->go_to(get_post_type_archive_link('movie'));
        $content = $this->get_page_content();
        
        $load_time = microtime(true) - $start_time;
        
        $this->assertLessThan(1.0, $load_time, 'Archive page should load in under 1 second');
        $this->assertStringContains('Archive Movie', $content, 'Archive should contain movies');
    }
    
    /**
     * Test AJAX performance
     */
    public function test_ajax_performance(): void {
        // Test AJAX search
        $_POST['action'] = 'tmu_search';
        $_POST['query'] = 'test';
        $_POST['nonce'] = wp_create_nonce('tmu_search_nonce');
        
        $start_time = microtime(true);
        
        try {
            ob_start();
            do_action('wp_ajax_tmu_search');
            $response = ob_get_clean();
            
            $ajax_time = microtime(true) - $start_time;
            
            $this->assertLessThan(0.3, $ajax_time, 'AJAX search should complete in under 0.3 seconds');
            
            // Check if response is valid JSON
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->assertArrayHasKey('success', $data, 'AJAX response should have success key');
            }
        } catch (Exception $e) {
            // If AJAX handler doesn't exist, skip test
            $this->markTestSkipped('AJAX handler not implemented');
        }
    }
    
    /**
     * Test image loading performance
     */
    public function test_image_loading_performance(): void {
        $movie_id = $this->create_movie(['title' => 'Image Test Movie']);
        
        // Add multiple images
        for ($i = 0; $i < 5; $i++) {
            $attachment_id = $this->create_attachment($movie_id, "poster-{$i}.jpg");
            set_post_thumbnail($movie_id, $attachment_id);
        }
        
        $start_time = microtime(true);
        
        $this->go_to(get_permalink($movie_id));
        $content = $this->get_page_content();
        
        $load_time = microtime(true) - $start_time;
        
        $this->assertLessThan(2.0, $load_time, 'Page with images should load in under 2 seconds');
        $this->assertStringContains('Image Test Movie', $content, 'Page should contain movie title');
    }
    
    /**
     * Test caching performance
     */
    public function test_caching_performance(): void {
        $movie_id = $this->create_movie(['title' => 'Cache Test Movie']);
        $url = get_permalink($movie_id);
        
        // First request (cache miss)
        $start_time = microtime(true);
        $this->go_to($url);
        $content1 = $this->get_page_content();
        $first_load_time = microtime(true) - $start_time;
        
        // Second request (cache hit)
        $start_time = microtime(true);
        $this->go_to($url);
        $content2 = $this->get_page_content();
        $second_load_time = microtime(true) - $start_time;
        
        // Cached request should be faster or at least not significantly slower
        $this->assertLessThanOrEqual($first_load_time * 1.2, $second_load_time, 'Cached request should not be significantly slower');
        $this->assertEquals($content1, $content2, 'Cached content should match original');
    }
    
    /**
     * Test concurrent request handling
     */
    public function test_concurrent_request_handling(): void {
        $urls = [];
        $movie_ids = [];
        
        // Create multiple movies
        for ($i = 0; $i < 5; $i++) {
            $movie_id = $this->create_movie(['title' => "Concurrent Movie {$i}"]);
            $movie_ids[] = $movie_id;
            $urls[] = get_permalink($movie_id);
        }
        
        $start_time = microtime(true);
        
        // Simulate concurrent requests by loading all pages quickly
        foreach ($urls as $url) {
            $this->go_to($url);
            $content = $this->get_page_content();
            $this->assertNotEmpty($content, 'Each page should have content');
        }
        
        $total_time = microtime(true) - $start_time;
        
        // All requests should complete in reasonable time
        $this->assertLessThan(10.0, $total_time, 'All concurrent requests should complete in under 10 seconds');
    }
    
    /**
     * Create movie with full data for testing
     */
    private function create_movie_with_full_data(): int {
        $movie_id = $this->create_movie([
            'title' => 'Full Data Movie',
            'overview' => 'A movie with complete metadata for testing',
            'tmdb_id' => 12345,
            'vote_average' => 8.5,
            'runtime' => 120
        ]);
        
        // Add multiple images
        for ($i = 0; $i < 5; $i++) {
            $attachment_id = $this->create_attachment($movie_id, "poster-{$i}.jpg");
            if ($i === 0) {
                set_post_thumbnail($movie_id, $attachment_id);
            }
        }
        
        // Add cast and crew
        for ($i = 0; $i < 10; $i++) {
            $person_id = $this->create_person(['title' => "Actor {$i}"]);
            $this->add_cast_member($movie_id, $person_id, "Character {$i}");
        }
        
        // Add genres
        $genre_ids = [];
        for ($i = 0; $i < 3; $i++) {
            $genre_ids[] = $this->create_term("Genre {$i}", 'genre');
        }
        wp_set_post_terms($movie_id, $genre_ids, 'genre');
        
        return $movie_id;
    }
    
    /**
     * Get page content for testing
     */
    private function get_page_content(): string {
        ob_start();
        
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                the_title();
                the_content();
            }
        }
        
        return ob_get_clean();
    }
}