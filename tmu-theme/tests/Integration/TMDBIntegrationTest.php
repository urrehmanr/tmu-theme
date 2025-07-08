<?php
/**
 * TMDB Integration Test
 * 
 * Integration test for TMDB API client as specified in Step 19 documentation
 * 
 * @package TMU\Tests\Integration
 * @since 1.0.0
 */

namespace TMU\Tests\Integration;

use TMU\API\TMDBClient;
use TMU\Tests\TestCase;

/**
 * TMDB Integration Test Class
 * 
 * Tests TMDB API integration functionality exactly as specified 
 * in Step 19 documentation lines 360-383
 */
class TMDBIntegrationTest extends TestCase {
    
    /**
     * TMDB client instance
     * 
     * @var TMDBClient
     */
    private TMDBClient $tmdb_client;
    
    /**
     * Set up test environment
     * 
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->tmdb_client = new TMDBClient();
    }
    
    /**
     * Test fetch movie data functionality
     * 
     * This test uses the exact implementation from Step 19 documentation
     * 
     * @return void
     */
    public function test_fetch_movie_data(): void {
        // Use a known movie ID for testing
        $movie_id = 550; // Fight Club
        
        $movie_data = $this->tmdb_client->get_movie($movie_id);
        
        $this->assertNotEmpty($movie_data);
        $this->assertEquals('Fight Club', $movie_data['title']);
        $this->assertArrayHasKey('overview', $movie_data);
        $this->assertArrayHasKey('release_date', $movie_data);
    }
    
    /**
     * Test TMDB API connectivity
     * 
     * @return void
     */
    public function test_tmdb_api_connectivity(): void {
        $configuration = $this->tmdb_client->get_configuration();
        
        $this->assertNotEmpty($configuration);
        $this->assertArrayHasKey('images', $configuration);
        $this->assertArrayHasKey('base_url', $configuration['images']);
    }
    
    /**
     * Test movie search functionality
     * 
     * @return void
     */
    public function test_movie_search(): void {
        $search_results = $this->tmdb_client->search_movies('Fight Club');
        
        $this->assertNotEmpty($search_results);
        $this->assertArrayHasKey('results', $search_results);
        $this->assertGreaterThan(0, count($search_results['results']));
    }
    
    /**
     * Test TV series data fetching
     * 
     * @return void
     */
    public function test_fetch_tv_series_data(): void {
        // Use a known TV series ID for testing
        $tv_id = 1399; // Game of Thrones
        
        $tv_data = $this->tmdb_client->get_tv_series($tv_id);
        
        $this->assertNotEmpty($tv_data);
        $this->assertArrayHasKey('name', $tv_data);
        $this->assertArrayHasKey('overview', $tv_data);
        $this->assertArrayHasKey('first_air_date', $tv_data);
    }
    
    /**
     * Test person data fetching
     * 
     * @return void
     */
    public function test_fetch_person_data(): void {
        // Use a known person ID for testing
        $person_id = 287; // Brad Pitt
        
        $person_data = $this->tmdb_client->get_person($person_id);
        
        $this->assertNotEmpty($person_data);
        $this->assertArrayHasKey('name', $person_data);
        $this->assertArrayHasKey('biography', $person_data);
    }
    
    /**
     * Test API rate limiting functionality
     * 
     * @return void
     */
    public function test_api_rate_limiting(): void {
        $start_time = microtime(true);
        
        // Make multiple requests to test rate limiting
        for ($i = 0; $i < 5; $i++) {
            $this->tmdb_client->get_movie(550);
        }
        
        $end_time = microtime(true);
        $duration = $end_time - $start_time;
        
        // Should take at least 1 second due to rate limiting (4 requests per second)
        $this->assertGreaterThan(1.0, $duration);
    }
    
    /**
     * Test error handling for invalid movie ID
     * 
     * @return void
     */
    public function test_invalid_movie_id_handling(): void {
        $movie_data = $this->tmdb_client->get_movie(999999999);
        
        // Should return empty or handle error gracefully
        $this->assertTrue(empty($movie_data) || isset($movie_data['error']));
    }
    
    /**
     * Test API key validation
     * 
     * @return void
     */
    public function test_api_key_validation(): void {
        // Test that API key is properly configured
        $this->assertTrue($this->tmdb_client->is_api_key_valid());
    }
    
    /**
     * Test image URL generation
     * 
     * @return void
     */
    public function test_image_url_generation(): void {
        $poster_path = '/example_poster.jpg';
        $image_url = $this->tmdb_client->get_image_url($poster_path, 'w500');
        
        $this->assertStringContainsString('https://image.tmdb.org', $image_url);
        $this->assertStringContainsString('w500', $image_url);
        $this->assertStringContainsString($poster_path, $image_url);
    }
    
    /**
     * Test caching functionality
     * 
     * @return void
     */
    public function test_caching_functionality(): void {
        $movie_id = 550;
        
        // First request should hit API
        $start_time = microtime(true);
        $first_result = $this->tmdb_client->get_movie($movie_id);
        $first_duration = microtime(true) - $start_time;
        
        // Second request should use cache and be faster
        $start_time = microtime(true);
        $second_result = $this->tmdb_client->get_movie($movie_id);
        $second_duration = microtime(true) - $start_time;
        
        $this->assertEquals($first_result, $second_result);
        $this->assertLessThan($first_duration, $second_duration);
    }
}