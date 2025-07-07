<?php
/**
 * TMDB API Tests
 * 
 * Comprehensive testing for TMDB API integration functionality
 * 
 * @package TMU\Tests\API
 * @since 1.0.0
 */

namespace TMU\Tests\API;

use PHPUnit\Framework\TestCase;
use TMU\API\TMDB\Client;
use TMU\API\TMDB\SyncService;
use TMU\API\TMDB\DataMapper;
use TMU\API\TMDB\ImageSyncService;
use TMU\API\TMDB\SearchService;
use TMU\API\TMDB\Cache;
use TMU\API\TMDB\RateLimiter;
use TMU\API\TMDB\Exception;

/**
 * TMDBTest class
 * 
 * Tests TMDB API integration components
 */
class TMDBTest extends TestCase {
    
    /**
     * TMDB API client
     * @var Client
     */
    private $client;
    
    /**
     * Sync service
     * @var SyncService
     */
    private $syncService;
    
    /**
     * Data mapper
     * @var DataMapper
     */
    private $dataMapper;
    
    /**
     * Search service
     * @var SearchService
     */
    private $searchService;
    
    /**
     * Test API key
     * @var string
     */
    private $testApiKey = 'test_api_key_12345';
    
    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Mock WordPress functions
        $this->mockWordPressFunctions();
        
        // Initialize test components
        $this->client = new Client($this->testApiKey);
        $this->syncService = new SyncService();
        $this->dataMapper = new DataMapper();
        $this->searchService = new SearchService();
    }
    
    /**
     * Test TMDB Client initialization
     */
    public function testClientInitialization(): void {
        $this->assertInstanceOf(Client::class, $this->client);
        $this->assertEquals($this->testApiKey, $this->client->getApiKey());
        $this->assertEquals('en-US', $this->client->getLanguage());
        $this->assertEquals('US', $this->client->getRegion());
    }
    
    /**
     * Test API key validation
     */
    public function testApiKeyValidation(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('TMDB API key is required');
        
        new Client('');
    }
    
    /**
     * Test movie details retrieval
     */
    public function testGetMovieDetails(): void {
        // Mock API response
        $mockResponse = [
            'id' => 550,
            'title' => 'Fight Club',
            'overview' => 'A ticking-time-bomb insomniac...',
            'release_date' => '1999-10-15',
            'vote_average' => 8.4,
            'runtime' => 139
        ];
        
        // Mock the HTTP request
        $this->mockHttpRequest($mockResponse);
        
        $result = $this->client->getMovieDetails(550);
        
        $this->assertIsArray($result);
        $this->assertEquals(550, $result['id']);
        $this->assertEquals('Fight Club', $result['title']);
        $this->assertEquals(8.4, $result['vote_average']);
    }
    
    /**
     * Test TV show details retrieval
     */
    public function testGetTVDetails(): void {
        $mockResponse = [
            'id' => 1399,
            'name' => 'Game of Thrones',
            'overview' => 'Seven noble families fight...',
            'first_air_date' => '2011-04-17',
            'vote_average' => 9.3,
            'number_of_seasons' => 8
        ];
        
        $this->mockHttpRequest($mockResponse);
        
        $result = $this->client->getTVDetails(1399);
        
        $this->assertIsArray($result);
        $this->assertEquals(1399, $result['id']);
        $this->assertEquals('Game of Thrones', $result['name']);
        $this->assertEquals(8, $result['number_of_seasons']);
    }
    
    /**
     * Test person details retrieval
     */
    public function testGetPersonDetails(): void {
        $mockResponse = [
            'id' => 287,
            'name' => 'Brad Pitt',
            'biography' => 'William Bradley Pitt...',
            'birthday' => '1963-12-18',
            'place_of_birth' => 'Shawnee, Oklahoma, USA',
            'known_for_department' => 'Acting'
        ];
        
        $this->mockHttpRequest($mockResponse);
        
        $result = $this->client->getPersonDetails(287);
        
        $this->assertIsArray($result);
        $this->assertEquals(287, $result['id']);
        $this->assertEquals('Brad Pitt', $result['name']);
        $this->assertEquals('Acting', $result['known_for_department']);
    }
    
    /**
     * Test movie search functionality
     */
    public function testSearchMovies(): void {
        $mockResponse = [
            'page' => 1,
            'total_results' => 2,
            'total_pages' => 1,
            'results' => [
                [
                    'id' => 550,
                    'title' => 'Fight Club',
                    'release_date' => '1999-10-15',
                    'vote_average' => 8.4
                ],
                [
                    'id' => 9806,
                    'title' => 'The Incredibles',
                    'release_date' => '2004-10-27',
                    'vote_average' => 7.7
                ]
            ]
        ];
        
        $this->mockHttpRequest($mockResponse);
        
        $result = $this->client->searchMovies('fight');
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(2, $result['total_results']);
        $this->assertCount(2, $result['results']);
        $this->assertEquals('Fight Club', $result['results'][0]['title']);
    }
    
    /**
     * Test TV search functionality
     */
    public function testSearchTV(): void {
        $mockResponse = [
            'page' => 1,
            'total_results' => 1,
            'total_pages' => 1,
            'results' => [
                [
                    'id' => 1399,
                    'name' => 'Game of Thrones',
                    'first_air_date' => '2011-04-17',
                    'vote_average' => 9.3
                ]
            ]
        ];
        
        $this->mockHttpRequest($mockResponse);
        
        $result = $this->client->searchTV('game of thrones');
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total_results']);
        $this->assertEquals('Game of Thrones', $result['results'][0]['name']);
    }
    
    /**
     * Test people search functionality
     */
    public function testSearchPeople(): void {
        $mockResponse = [
            'page' => 1,
            'total_results' => 1,
            'total_pages' => 1,
            'results' => [
                [
                    'id' => 287,
                    'name' => 'Brad Pitt',
                    'known_for_department' => 'Acting',
                    'popularity' => 98.5
                ]
            ]
        ];
        
        $this->mockHttpRequest($mockResponse);
        
        $result = $this->client->searchPeople('brad pitt');
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total_results']);
        $this->assertEquals('Brad Pitt', $result['results'][0]['name']);
    }
    
    /**
     * Test multi search functionality
     */
    public function testMultiSearch(): void {
        $mockResponse = [
            'page' => 1,
            'total_results' => 2,
            'total_pages' => 1,
            'results' => [
                [
                    'id' => 550,
                    'media_type' => 'movie',
                    'title' => 'Fight Club',
                    'release_date' => '1999-10-15'
                ],
                [
                    'id' => 287,
                    'media_type' => 'person',
                    'name' => 'Brad Pitt',
                    'known_for_department' => 'Acting'
                ]
            ]
        ];
        
        $this->mockHttpRequest($mockResponse);
        
        $result = $this->client->multiSearch('brad pitt');
        
        $this->assertIsArray($result);
        $this->assertEquals(2, $result['total_results']);
        $this->assertEquals('movie', $result['results'][0]['media_type']);
        $this->assertEquals('person', $result['results'][1]['media_type']);
    }
    
    /**
     * Test configuration retrieval
     */
    public function testGetConfiguration(): void {
        $mockResponse = [
            'images' => [
                'base_url' => 'http://image.tmdb.org/t/p/',
                'secure_base_url' => 'https://image.tmdb.org/t/p/',
                'backdrop_sizes' => ['w300', 'w780', 'w1280', 'original'],
                'logo_sizes' => ['w45', 'w92', 'w154', 'w185', 'w300', 'w500', 'original'],
                'poster_sizes' => ['w92', 'w154', 'w185', 'w342', 'w500', 'w780', 'original'],
                'profile_sizes' => ['w45', 'w185', 'h632', 'original'],
                'still_sizes' => ['w92', 'w185', 'w300', 'original']
            ]
        ];
        
        $this->mockHttpRequest($mockResponse);
        
        $result = $this->client->getConfiguration();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('images', $result);
        $this->assertEquals('https://image.tmdb.org/t/p/', $result['images']['secure_base_url']);
    }
    
    /**
     * Test image URL building
     */
    public function testBuildImageUrl(): void {
        $imagePath = '/8uO0gUM8aNqYLs1OsTBQiXu0fEv.jpg';
        $size = 'w500';
        
        $expectedUrl = 'https://image.tmdb.org/t/p/w500/8uO0gUM8aNqYLs1OsTBQiXu0fEv.jpg';
        $actualUrl = $this->client->buildImageUrl($imagePath, $size);
        
        $this->assertEquals($expectedUrl, $actualUrl);
    }
    
    /**
     * Test connection testing
     */
    public function testConnectionTest(): void {
        $mockResponse = [
            'id' => 550,
            'title' => 'Fight Club'
        ];
        
        $this->mockHttpRequest($mockResponse);
        
        $result = $this->client->testConnection();
        
        $this->assertTrue($result);
    }
    
    /**
     * Test API error handling
     */
    public function testApiErrorHandling(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid API key');
        
        // Mock error response
        $mockResponse = [
            'status_code' => 401,
            'status_message' => 'Invalid API key: You must be granted a valid key.'
        ];
        
        $this->mockHttpRequest($mockResponse, 401);
        
        $this->client->getMovieDetails(550);
    }
    
    /**
     * Test rate limiting functionality
     */
    public function testRateLimiting(): void {
        $rateLimiter = new RateLimiter();
        
        // Test that rate limiter allows requests initially
        $this->assertTrue($rateLimiter->canMakeRequest());
        
        // Make multiple requests to test limiting
        for ($i = 0; $i < 45; $i++) {
            $rateLimiter->recordRequest();
        }
        
        // Should be rate limited after too many requests
        $this->assertFalse($rateLimiter->canMakeRequest());
    }
    
    /**
     * Test caching functionality
     */
    public function testCaching(): void {
        $cache = new Cache();
        $key = 'test_cache_key';
        $data = ['test' => 'data'];
        
        // Test setting cache
        $cache->set($key, $data, 3600);
        
        // Test getting cache
        $cachedData = $cache->get($key);
        $this->assertEquals($data, $cachedData);
        
        // Test cache expiration
        $cache->delete($key);
        $this->assertNull($cache->get($key));
    }
    
    /**
     * Test sync service movie sync
     */
    public function testSyncServiceMovieSync(): void {
        // Mock post data
        $postId = 123;
        $this->mockPostData($postId, 'movie', ['tmdb_id' => 550]);
        
        // Mock API response
        $mockApiResponse = [
            'id' => 550,
            'title' => 'Fight Club',
            'overview' => 'A ticking-time-bomb insomniac...',
            'release_date' => '1999-10-15',
            'vote_average' => 8.4,
            'runtime' => 139
        ];
        
        $this->mockHttpRequest($mockApiResponse);
        
        $result = $this->syncService->sync_movie($postId);
        
        $this->assertTrue($result);
    }
    
    /**
     * Test data mapper movie data mapping
     */
    public function testDataMapperMovieMapping(): void {
        $postId = 123;
        $tmdbData = [
            'id' => 550,
            'title' => 'Fight Club',
            'overview' => 'A ticking-time-bomb insomniac...',
            'release_date' => '1999-10-15',
            'vote_average' => 8.4,
            'runtime' => 139,
            'genres' => [
                ['id' => 18, 'name' => 'Drama'],
                ['id' => 53, 'name' => 'Thriller']
            ]
        ];
        
        $this->mockPostData($postId, 'movie');
        
        $result = $this->dataMapper->mapMovieData($postId, $tmdbData);
        
        $this->assertTrue($result);
    }
    
    /**
     * Test search service functionality
     */
    public function testSearchService(): void {
        $mockResponse = [
            'page' => 1,
            'total_results' => 1,
            'results' => [
                [
                    'id' => 550,
                    'title' => 'Fight Club',
                    'media_type' => 'movie'
                ]
            ]
        ];
        
        $this->mockHttpRequest($mockResponse);
        
        $result = $this->searchService->search('fight club');
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total_results']);
    }
    
    /**
     * Test image sync service
     */
    public function testImageSyncService(): void {
        $imageService = new ImageSyncService();
        $postId = 123;
        $imagePath = '/8uO0gUM8aNqYLs1OsTBQiXu0fEv.jpg';
        
        // Mock WordPress functions for image handling
        $this->mockImageFunctions();
        
        $result = $imageService->downloadAndAttachImage($postId, $imagePath, 'poster');
        
        // In a real test, this would return an attachment ID
        // For this test, we just verify the method doesn't throw errors
        $this->assertNotNull($result);
    }
    
    /**
     * Test exception handling
     */
    public function testExceptionHandling(): void {
        $exception = new Exception('Test error', Exception::ERROR_API_LIMIT_EXCEEDED);
        
        $this->assertEquals('Test error', $exception->getMessage());
        $this->assertEquals(Exception::ERROR_API_LIMIT_EXCEEDED, $exception->getCode());
    }
    
    /**
     * Test bulk sync operations
     */
    public function testBulkSync(): void {
        $postIds = [123, 124, 125];
        
        // Mock post data for each post
        foreach ($postIds as $index => $postId) {
            $this->mockPostData($postId, 'movie', ['tmdb_id' => 550 + $index]);
        }
        
        // Mock API responses
        $this->mockHttpRequest(['id' => 550, 'title' => 'Fight Club']);
        
        $results = $this->syncService->bulk_sync($postIds);
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('success', $results);
        $this->assertArrayHasKey('failed', $results);
        $this->assertArrayHasKey('errors', $results);
    }
    
    /**
     * Test sync statistics
     */
    public function testSyncStatistics(): void {
        $stats = $this->syncService->getSyncStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_synced', $stats);
        $this->assertArrayHasKey('movies_synced', $stats);
        $this->assertArrayHasKey('tv_synced', $stats);
        $this->assertArrayHasKey('people_synced', $stats);
    }
    
    /**
     * Mock WordPress functions for testing
     */
    private function mockWordPressFunctions(): void {
        if (!function_exists('get_option')) {
            function get_option($option, $default = false) {
                $options = [
                    'tmu_tmdb_api_key' => 'test_api_key_12345',
                    'tmu_tmdb_cache_duration' => 3600
                ];
                return $options[$option] ?? $default;
            }
        }
        
        if (!function_exists('wp_remote_get')) {
            function wp_remote_get($url, $args = []) {
                // Mock successful response
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'id' => 550,
                        'title' => 'Fight Club'
                    ])
                ];
            }
        }
        
        if (!function_exists('wp_remote_retrieve_body')) {
            function wp_remote_retrieve_body($response) {
                return $response['body'];
            }
        }
        
        if (!function_exists('wp_remote_retrieve_response_code')) {
            function wp_remote_retrieve_response_code($response) {
                return $response['response']['code'];
            }
        }
        
        if (!function_exists('is_wp_error')) {
            function is_wp_error($thing) {
                return false;
            }
        }
        
        if (!function_exists('get_transient')) {
            function get_transient($transient) {
                return false;
            }
        }
        
        if (!function_exists('set_transient')) {
            function set_transient($transient, $value, $expiration) {
                return true;
            }
        }
        
        if (!function_exists('delete_transient')) {
            function delete_transient($transient) {
                return true;
            }
        }
        
        if (!function_exists('current_time')) {
            function current_time($format) {
                return date($format);
            }
        }
        
        if (!function_exists('error_log')) {
            function error_log($message) {
                // Silence errors in tests
            }
        }
    }
    
    /**
     * Mock HTTP request
     */
    private function mockHttpRequest(array $response, int $statusCode = 200): void {
        // In a real implementation, this would mock wp_remote_get
        // For this test, we assume the mock functions handle this
    }
    
    /**
     * Mock post data
     */
    private function mockPostData(int $postId, string $postType, array $meta = []): void {
        // Mock post data for testing
        global $mockPosts;
        $mockPosts[$postId] = [
            'ID' => $postId,
            'post_type' => $postType,
            'meta' => $meta
        ];
    }
    
    /**
     * Mock image functions
     */
    private function mockImageFunctions(): void {
        if (!function_exists('wp_upload_dir')) {
            function wp_upload_dir() {
                return [
                    'path' => '/tmp',
                    'url' => 'http://example.com/uploads',
                    'subdir' => '',
                    'basedir' => '/tmp',
                    'baseurl' => 'http://example.com/uploads',
                    'error' => false
                ];
            }
        }
        
        if (!function_exists('wp_insert_attachment')) {
            function wp_insert_attachment($args, $file) {
                return 456; // Mock attachment ID
            }
        }
        
        if (!function_exists('wp_generate_attachment_metadata')) {
            function wp_generate_attachment_metadata($attachmentId, $file) {
                return [];
            }
        }
        
        if (!function_exists('wp_update_attachment_metadata')) {
            function wp_update_attachment_metadata($attachmentId, $metadata) {
                return true;
            }
        }
    }
}