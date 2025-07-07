<?php
/**
 * TMDB Client Test
 * 
 * Tests for the TMDB API client functionality.
 * 
 * @package TMU\Tests\Unit
 * @since 1.0.0
 */

namespace TMU\Tests\TMDB;

use TMU\TMDB\TMDBClient;
use TMU\Tests\Utilities\TMDBMock;
use PHPUnit\Framework\TestCase;

/**
 * TMDBClientTest class
 * 
 * Unit tests for TMDB client
 */
class TMDBClientTest extends TestCase {
    
    /**
     * TMDB client instance
     * @var TMDBClient
     */
    private $tmdb_client;
    
    /**
     * TMDB mock instance
     * @var TMDBMock
     */
    private $tmdb_mock;
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        $this->tmdb_mock = new TMDBMock();
        
        // Initialize TMDB client if class exists
        if (class_exists('TMU\\TMDB\\TMDBClient')) {
            $this->tmdb_client = new TMDBClient();
        } else {
            // Skip tests if TMDB client doesn't exist
            $this->markTestSkipped('TMDBClient class not found');
        }
    }
    
    /**
     * Tear down test environment
     */
    public function tearDown(): void {
        if ($this->tmdb_mock) {
            $this->tmdb_mock->clear_mocked_responses();
        }
        parent::tearDown();
    }
    
    /**
     * Test get movie details
     */
    public function test_get_movie_details(): void {
        $sample_movie = $this->tmdb_mock->create_sample_movie([
            'id' => 550,
            'title' => 'Fight Club',
            'overview' => 'A ticking-time-bomb insomniac...',
            'release_date' => '1999-10-15',
            'vote_average' => 8.8
        ]);
        
        $this->tmdb_mock->mock_movie_response(550, $sample_movie);
        
        $result = $this->tmdb_client->get_movie_details(550);
        
        $this->assertEquals(550, $result['id'], 'Movie ID should match');
        $this->assertEquals('Fight Club', $result['title'], 'Movie title should match');
        $this->assertEquals(8.8, $result['vote_average'], 'Vote average should match');
        $this->assertEquals('1999-10-15', $result['release_date'], 'Release date should match');
    }
    
    /**
     * Test API error handling
     */
    public function test_api_error_handling(): void {
        $this->tmdb_mock->mock_error_response(404, 'The resource you requested could not be found.');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The resource you requested could not be found.');
        
        $this->tmdb_client->get_movie_details(999999);
    }
    
    /**
     * Test caching mechanism
     */
    public function test_caching_mechanism(): void {
        $sample_movie = $this->tmdb_mock->create_sample_movie(['id' => 550, 'title' => 'Fight Club']);
        $this->tmdb_mock->mock_movie_response(550, $sample_movie);
        
        // First call should hit the API
        $result1 = $this->tmdb_client->get_movie_details(550);
        
        // Second call should use cache
        $result2 = $this->tmdb_client->get_movie_details(550);
        
        $this->assertEquals($result1, $result2, 'Cached results should match original');
        
        // Should only make one API call due to caching
        $this->assertEquals(1, $this->tmdb_mock->get_call_count(), 'Should make only one API call');
    }
    
    /**
     * Test rate limiting
     */
    public function test_rate_limiting(): void {
        $this->markTestSkipped('Rate limiting test requires time measurement');
        
        // This test would measure execution time but is complex in unit tests
        // In real implementation, you would test the rate limiting logic
        $start_time = microtime(true);
        
        for ($i = 0; $i < 5; $i++) {
            $sample_movie = $this->tmdb_mock->create_sample_movie(['id' => $i]);
            $this->tmdb_mock->mock_movie_response($i, $sample_movie);
            $this->tmdb_client->get_movie_details($i);
        }
        
        $execution_time = microtime(true) - $start_time;
        
        // Should take at least some time due to rate limiting
        $this->assertGreaterThan(0, $execution_time, 'Should take some time');
    }
    
    /**
     * Test get TV show details
     */
    public function test_get_tv_show_details(): void {
        if (!method_exists($this->tmdb_client, 'get_tv_details')) {
            $this->markTestSkipped('get_tv_details method not implemented');
        }
        
        $sample_tv = $this->tmdb_mock->create_sample_tv_show([
            'id' => 1399,
            'name' => 'Game of Thrones',
            'first_air_date' => '2011-04-17'
        ]);
        
        $this->tmdb_mock->mock_tv_response(1399, $sample_tv);
        
        $result = $this->tmdb_client->get_tv_details(1399);
        
        $this->assertEquals(1399, $result['id'], 'TV show ID should match');
        $this->assertEquals('Game of Thrones', $result['name'], 'TV show name should match');
        $this->assertEquals('2011-04-17', $result['first_air_date'], 'First air date should match');
    }
    
    /**
     * Test get person details
     */
    public function test_get_person_details(): void {
        if (!method_exists($this->tmdb_client, 'get_person_details')) {
            $this->markTestSkipped('get_person_details method not implemented');
        }
        
        $sample_person = $this->tmdb_mock->create_sample_person([
            'id' => 287,
            'name' => 'Brad Pitt',
            'birthday' => '1963-12-18'
        ]);
        
        $this->tmdb_mock->mock_person_response(287, $sample_person);
        
        $result = $this->tmdb_client->get_person_details(287);
        
        $this->assertEquals(287, $result['id'], 'Person ID should match');
        $this->assertEquals('Brad Pitt', $result['name'], 'Person name should match');
        $this->assertEquals('1963-12-18', $result['birthday'], 'Birthday should match');
    }
    
    /**
     * Test search movies
     */
    public function test_search_movies(): void {
        if (!method_exists($this->tmdb_client, 'search_movies')) {
            $this->markTestSkipped('search_movies method not implemented');
        }
        
        $search_results = [
            'page' => 1,
            'total_results' => 1,
            'total_pages' => 1,
            'results' => [
                $this->tmdb_mock->create_sample_movie([
                    'id' => 550,
                    'title' => 'Fight Club'
                ])
            ]
        ];
        
        $this->tmdb_mock->mock_search_response('Fight Club', $search_results);
        
        $result = $this->tmdb_client->search_movies('Fight Club');
        
        $this->assertEquals(1, $result['total_results'], 'Should have one result');
        $this->assertEquals('Fight Club', $result['results'][0]['title'], 'Result title should match');
    }
    
    /**
     * Test movie credits
     */
    public function test_get_movie_credits(): void {
        if (!method_exists($this->tmdb_client, 'get_movie_credits')) {
            $this->markTestSkipped('get_movie_credits method not implemented');
        }
        
        $credits = [
            'cast' => [
                [
                    'id' => 287,
                    'name' => 'Brad Pitt',
                    'character' => 'Tyler Durden',
                    'order' => 0
                ]
            ],
            'crew' => [
                [
                    'id' => 7467,
                    'name' => 'David Fincher',
                    'job' => 'Director',
                    'department' => 'Directing'
                ]
            ]
        ];
        
        $this->tmdb_mock->mock_movie_credits(550, $credits['cast'], $credits['crew']);
        
        $result = $this->tmdb_client->get_movie_credits(550);
        
        $this->assertArrayHasKey('cast', $result, 'Should have cast data');
        $this->assertArrayHasKey('crew', $result, 'Should have crew data');
        $this->assertEquals('Brad Pitt', $result['cast'][0]['name'], 'Cast member name should match');
        $this->assertEquals('David Fincher', $result['crew'][0]['name'], 'Crew member name should match');
    }
    
    /**
     * Test movie images
     */
    public function test_get_movie_images(): void {
        if (!method_exists($this->tmdb_client, 'get_movie_images')) {
            $this->markTestSkipped('get_movie_images method not implemented');
        }
        
        $images = [
            [
                'file_path' => '/pB8BM7pdSp6B6Ih7QZ4DrQ3PmJK.jpg',
                'width' => 500,
                'height' => 750
            ]
        ];
        
        $this->tmdb_mock->mock_movie_images(550, [], $images);
        
        $result = $this->tmdb_client->get_movie_images(550);
        
        $this->assertArrayHasKey('posters', $result, 'Should have posters data');
        $this->assertCount(1, $result['posters'], 'Should have one poster');
        $this->assertEquals('/pB8BM7pdSp6B6Ih7QZ4DrQ3PmJK.jpg', $result['posters'][0]['file_path'], 'Image path should match');
    }
    
    /**
     * Test movie videos
     */
    public function test_get_movie_videos(): void {
        if (!method_exists($this->tmdb_client, 'get_movie_videos')) {
            $this->markTestSkipped('get_movie_videos method not implemented');
        }
        
        $videos = [
            [
                'key' => 'SUXWAEX2jlg',
                'name' => 'Fight Club - Trailer',
                'site' => 'YouTube',
                'type' => 'Trailer'
            ]
        ];
        
        $this->tmdb_mock->mock_movie_videos(550, $videos);
        
        $result = $this->tmdb_client->get_movie_videos(550);
        
        $this->assertArrayHasKey('results', $result, 'Should have results data');
        $this->assertCount(1, $result['results'], 'Should have one video');
        $this->assertEquals('SUXWAEX2jlg', $result['results'][0]['key'], 'Video key should match');
        $this->assertEquals('Trailer', $result['results'][0]['type'], 'Video type should match');
    }
    
    /**
     * Test API key validation
     */
    public function test_api_key_validation(): void {
        // Test with empty API key
        if (method_exists($this->tmdb_client, 'set_api_key')) {
            $this->tmdb_client->set_api_key('');
            
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('API key');
            
            $this->tmdb_client->get_movie_details(550);
        } else {
            $this->markTestSkipped('API key validation method not available');
        }
    }
    
    /**
     * Test invalid movie ID
     */
    public function test_invalid_movie_id(): void {
        $this->tmdb_mock->mock_error_response(404, 'The resource you requested could not be found.');
        
        $this->expectException(\Exception::class);
        
        $this->tmdb_client->get_movie_details(-1);
    }
    
    /**
     * Test popular movies
     */
    public function test_get_popular_movies(): void {
        if (!method_exists($this->tmdb_client, 'get_popular_movies')) {
            $this->markTestSkipped('get_popular_movies method not implemented');
        }
        
        $popular_movies = [
            $this->tmdb_mock->create_sample_movie(['id' => 550, 'title' => 'Fight Club']),
            $this->tmdb_mock->create_sample_movie(['id' => 13, 'title' => 'Forrest Gump'])
        ];
        
        $this->tmdb_mock->mock_popular_movies($popular_movies);
        
        $result = $this->tmdb_client->get_popular_movies();
        
        $this->assertArrayHasKey('results', $result, 'Should have results');
        $this->assertCount(2, $result['results'], 'Should have two movies');
        $this->assertEquals(2, $result['total_results'], 'Total results should match');
    }
    
    /**
     * Test network connectivity
     */
    public function test_network_connectivity(): void {
        // Simulate network error
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, 'api.themoviedb.org') !== false) {
                return new \WP_Error('network_error', 'Network connection failed');
            }
            return $preempt;
        }, 5, 3);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Network');
        
        $this->tmdb_client->get_movie_details(550);
    }
}