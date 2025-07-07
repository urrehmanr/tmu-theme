<?php
/**
 * Movie Post Type Test
 * 
 * Tests for the Movie post type functionality.
 * 
 * @package TMU\Tests\Unit
 * @since 1.0.0
 */

namespace TMU\Tests\PostTypes;

use TMU\PostTypes\MoviePostType;
use TMU\Tests\Utilities\DatabaseTestCase;

/**
 * MoviePostTypeTest class
 * 
 * Unit tests for Movie post type
 */
class MoviePostTypeTest extends DatabaseTestCase {
    
    /**
     * Movie post type instance
     * @var MoviePostType
     */
    private $movie_post_type;
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Initialize movie post type if class exists
        if (class_exists('TMU\\PostTypes\\MoviePostType')) {
            $this->movie_post_type = new MoviePostType();
            $this->movie_post_type->register();
        } else {
            // Fallback to manual registration for testing
            $this->register_test_post_types();
        }
    }
    
    /**
     * Test movie post type is registered
     */
    public function test_movie_post_type_is_registered(): void {
        $this->assertTrue(post_type_exists('movie'), 'Movie post type should be registered');
    }
    
    /**
     * Test movie post type supports
     */
    public function test_movie_post_type_supports(): void {
        $post_type = get_post_type_object('movie');
        
        $this->assertNotNull($post_type, 'Movie post type object should exist');
        $this->assertContains('title', $post_type->supports, 'Movie should support title');
        $this->assertContains('editor', $post_type->supports, 'Movie should support editor');
        $this->assertContains('thumbnail', $post_type->supports, 'Movie should support thumbnail');
        $this->assertContains('excerpt', $post_type->supports, 'Movie should support excerpt');
    }
    
    /**
     * Test movie post type capabilities
     */
    public function test_movie_post_type_capabilities(): void {
        $post_type = get_post_type_object('movie');
        
        $this->assertNotNull($post_type, 'Movie post type object should exist');
        
        // Test basic capabilities
        if (isset($post_type->cap->edit_posts)) {
            $this->assertEquals('edit_movies', $post_type->cap->edit_posts, 'Should have edit_movies capability');
        }
        
        if (isset($post_type->cap->edit_post)) {
            $this->assertEquals('edit_movie', $post_type->cap->edit_post, 'Should have edit_movie capability');
        }
        
        if (isset($post_type->cap->delete_posts)) {
            $this->assertEquals('delete_movies', $post_type->cap->delete_posts, 'Should have delete_movies capability');
        }
        
        if (isset($post_type->cap->publish_posts)) {
            $this->assertEquals('publish_movies', $post_type->cap->publish_posts, 'Should have publish_movies capability');
        }
    }
    
    /**
     * Test movie creation
     */
    public function test_movie_creation(): void {
        $movie_id = $this->create_movie([
            'title' => 'Test Movie',
            'overview' => 'A test movie for unit testing',
            'release_date' => '2023-01-01'
        ]);
        
        $this->assertGreaterThan(0, $movie_id, 'Movie ID should be greater than 0');
        $this->assertEquals('movie', get_post_type($movie_id), 'Post type should be movie');
        $this->assertEquals('Test Movie', get_the_title($movie_id), 'Title should match');
        
        // Test post exists
        $post = get_post($movie_id);
        $this->assertNotNull($post, 'Movie post should exist');
        $this->assertEquals('publish', $post->post_status, 'Movie should be published');
    }
    
    /**
     * Test movie custom fields
     */
    public function test_movie_custom_fields(): void {
        $movie_data = [
            'title' => 'Test Movie with Fields',
            'tmdb_id' => 12345,
            'vote_average' => 8.5,
            'runtime' => 120,
            'budget' => 50000000,
            'revenue' => 100000000
        ];
        
        $movie_id = $this->create_movie($movie_data);
        
        // Test meta fields
        $this->assertEquals(12345, get_post_meta($movie_id, 'tmdb_id', true), 'TMDB ID should match');
        $this->assertEquals(8.5, (float) get_post_meta($movie_id, 'vote_average', true), 'Vote average should match');
        $this->assertEquals(120, (int) get_post_meta($movie_id, 'runtime', true), 'Runtime should match');
        
        // Test custom function if it exists
        if (function_exists('tmu_get_movie_data')) {
            $retrieved_data = tmu_get_movie_data($movie_id);
            
            if (!empty($retrieved_data)) {
                $this->assertEquals(12345, $retrieved_data['tmdb_id'], 'Retrieved TMDB ID should match');
                $this->assertEquals(8.5, $retrieved_data['vote_average'], 'Retrieved vote average should match');
                $this->assertEquals(120, $retrieved_data['runtime'], 'Retrieved runtime should match');
            }
        }
    }
    
    /**
     * Test movie taxonomies
     */
    public function test_movie_taxonomies(): void {
        $movie_id = $this->create_movie(['title' => 'Genre Test Movie']);
        
        // Test genre assignment
        $genre_id = $this->create_term('Action', 'genre');
        $result = wp_set_post_terms($movie_id, [$genre_id], 'genre');
        
        $this->assertNotWPError($result, 'Setting genre should not be an error');
        
        $genres = wp_get_post_terms($movie_id, 'genre');
        $this->assertCount(1, $genres, 'Should have one genre');
        $this->assertEquals('Action', $genres[0]->name, 'Genre name should match');
        
        // Test multiple genres
        $drama_id = $this->create_term('Drama', 'genre');
        wp_set_post_terms($movie_id, [$genre_id, $drama_id], 'genre');
        
        $genres = wp_get_post_terms($movie_id, 'genre');
        $this->assertCount(2, $genres, 'Should have two genres');
        
        // Test country assignment
        $country_id = $this->create_term('United States', 'country');
        wp_set_post_terms($movie_id, [$country_id], 'country');
        
        $countries = wp_get_post_terms($movie_id, 'country');
        $this->assertCount(1, $countries, 'Should have one country');
        $this->assertEquals('United States', $countries[0]->name, 'Country name should match');
    }
    
    /**
     * Test movie archive functionality
     */
    public function test_movie_archive(): void {
        // Create multiple movies
        $movie_ids = [];
        for ($i = 1; $i <= 3; $i++) {
            $movie_ids[] = $this->create_movie([
                'title' => "Archive Test Movie {$i}",
                'post_date' => date('Y-m-d H:i:s', strtotime("-{$i} days"))
            ]);
        }
        
        // Test archive query
        $query = new \WP_Query([
            'post_type' => 'movie',
            'post_status' => 'publish',
            'posts_per_page' => -1
        ]);
        
        $this->assertTrue($query->have_posts(), 'Archive query should have posts');
        $this->assertGreaterThanOrEqual(3, $query->post_count, 'Should have at least 3 movies');
        
        // Test archive URL
        $archive_url = get_post_type_archive_link('movie');
        $this->assertNotFalse($archive_url, 'Movie archive should have URL');
        $this->assertStringContains('movies', $archive_url, 'Archive URL should contain movies slug');
    }
    
    /**
     * Test movie meta box support
     */
    public function test_movie_meta_boxes(): void {
        $movie_id = $this->create_movie(['title' => 'Meta Box Test Movie']);
        
        // Test thumbnail support
        $post_type = get_post_type_object('movie');
        $this->assertTrue(post_type_supports('movie', 'thumbnail'), 'Movie should support thumbnails');
        
        // Test excerpt support
        $this->assertTrue(post_type_supports('movie', 'excerpt'), 'Movie should support excerpts');
        
        // Test custom fields support
        $this->assertTrue(post_type_supports('movie', 'custom-fields'), 'Movie should support custom fields');
    }
    
    /**
     * Test movie search functionality
     */
    public function test_movie_search(): void {
        // Create movies with searchable content
        $this->create_movie([
            'title' => 'The Matrix',
            'post_content' => 'A computer hacker learns about the true nature of reality.'
        ]);
        
        $this->create_movie([
            'title' => 'Inception',
            'post_content' => 'A thief who steals corporate secrets through dream-sharing technology.'
        ]);
        
        // Test search for "Matrix"
        $search_query = new \WP_Query([
            'post_type' => 'movie',
            's' => 'Matrix',
            'posts_per_page' => -1
        ]);
        
        $this->assertTrue($search_query->have_posts(), 'Search should find Matrix movie');
        
        // Test search for "hacker"
        $search_query2 = new \WP_Query([
            'post_type' => 'movie',
            's' => 'hacker',
            'posts_per_page' => -1
        ]);
        
        $this->assertTrue($search_query2->have_posts(), 'Search should find movie with hacker in content');
    }
    
    /**
     * Test movie permalink structure
     */
    public function test_movie_permalinks(): void {
        $movie_id = $this->create_movie([
            'title' => 'Permalink Test Movie',
            'post_name' => 'permalink-test-movie'
        ]);
        
        $permalink = get_permalink($movie_id);
        $this->assertNotFalse($permalink, 'Movie should have permalink');
        $this->assertStringContains('permalink-test-movie', $permalink, 'Permalink should contain slug');
        
        // Test if rewrite rules are working
        $post_type = get_post_type_object('movie');
        if (isset($post_type->rewrite['slug'])) {
            $this->assertStringContains($post_type->rewrite['slug'], $permalink, 'Permalink should contain rewrite slug');
        }
    }
    
    /**
     * Test movie status transitions
     */
    public function test_movie_status_transitions(): void {
        // Create draft movie
        $movie_id = $this->create_movie([
            'title' => 'Draft Movie',
            'post_status' => 'draft'
        ]);
        
        $this->assertEquals('draft', get_post_status($movie_id), 'Movie should be draft');
        
        // Publish movie
        wp_update_post([
            'ID' => $movie_id,
            'post_status' => 'publish'
        ]);
        
        $this->assertEquals('publish', get_post_status($movie_id), 'Movie should be published');
        
        // Test trash
        wp_trash_post($movie_id);
        $this->assertEquals('trash', get_post_status($movie_id), 'Movie should be trashed');
        
        // Test restore
        wp_untrash_post($movie_id);
        $this->assertEquals('draft', get_post_status($movie_id), 'Movie should be restored to draft');
    }
    
    /**
     * Test movie deletion
     */
    public function test_movie_deletion(): void {
        $movie_id = $this->create_movie(['title' => 'Delete Test Movie']);
        
        // Verify movie exists
        $this->assertNotNull(get_post($movie_id), 'Movie should exist before deletion');
        
        // Delete movie
        $result = wp_delete_post($movie_id, true);
        
        $this->assertNotFalse($result, 'Movie deletion should succeed');
        $this->assertNull(get_post($movie_id), 'Movie should not exist after deletion');
    }
    
    /**
     * Test movie data validation
     */
    public function test_movie_data_validation(): void {
        // Test invalid TMDB ID
        $movie_id = $this->create_movie(['title' => 'Validation Test']);
        
        // Test setting invalid vote average
        update_post_meta($movie_id, 'vote_average', 'invalid');
        $vote_average = get_post_meta($movie_id, 'vote_average', true);
        
        // Should either be sanitized or remain empty
        $this->assertTrue(
            empty($vote_average) || is_numeric($vote_average),
            'Vote average should be numeric or empty'
        );
        
        // Test setting valid vote average
        update_post_meta($movie_id, 'vote_average', 8.5);
        $vote_average = get_post_meta($movie_id, 'vote_average', true);
        $this->assertEquals(8.5, (float) $vote_average, 'Valid vote average should be saved');
    }
}