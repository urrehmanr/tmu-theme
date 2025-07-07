<?php
/**
 * TMDB API Mock Utility
 * 
 * Provides mocking functionality for TMDB API responses during testing.
 * 
 * @package TMU\Tests\Utilities
 * @since 1.0.0
 */

namespace TMU\Tests\Utilities;

/**
 * TMDBMock class
 * 
 * Mock TMDB API responses for testing
 */
class TMDBMock {
    
    /**
     * Call count tracker
     * @var int
     */
    private $call_count = 0;
    
    /**
     * Mocked responses
     * @var array
     */
    private $mocked_responses = [];
    
    /**
     * Error responses
     * @var array
     */
    private $error_responses = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->setup_hooks();
    }
    
    /**
     * Set up WordPress hooks for intercepting HTTP requests
     */
    private function setup_hooks(): void {
        add_filter('pre_http_request', [$this, 'intercept_http_request'], 10, 3);
    }
    
    /**
     * Mock movie response
     * 
     * @param int $movie_id TMDB movie ID
     * @param array $response Mock response data
     */
    public function mock_movie_response($movie_id, $response): void {
        $this->mocked_responses["movie/{$movie_id}"] = $response;
    }
    
    /**
     * Mock TV show response
     * 
     * @param int $tv_id TMDB TV ID
     * @param array $response Mock response data
     */
    public function mock_tv_response($tv_id, $response): void {
        $this->mocked_responses["tv/{$tv_id}"] = $response;
    }
    
    /**
     * Mock person response
     * 
     * @param int $person_id TMDB person ID
     * @param array $response Mock response data
     */
    public function mock_person_response($person_id, $response): void {
        $this->mocked_responses["person/{$person_id}"] = $response;
    }
    
    /**
     * Mock search response
     * 
     * @param string $query Search query
     * @param array $response Mock response data
     */
    public function mock_search_response($query, $response): void {
        $this->mocked_responses["search/movie?query=" . urlencode($query)] = $response;
    }
    
    /**
     * Mock error response
     * 
     * @param int $status_code HTTP status code
     * @param string $message Error message
     */
    public function mock_error_response($status_code, $message): void {
        $this->error_responses[] = [
            'status_code' => $status_code,
            'message' => $message
        ];
    }
    
    /**
     * Intercept HTTP request
     * 
     * @param false|array|WP_Error $preempt Response to return
     * @param array $args Request arguments
     * @param string $url Request URL
     * @return false|array|WP_Error
     */
    public function intercept_http_request($preempt, $args, $url) {
        // Only intercept TMDB API requests
        if (strpos($url, 'api.themoviedb.org') === false) {
            return $preempt;
        }
        
        $this->call_count++;
        
        // Check for error responses first
        if (!empty($this->error_responses)) {
            $error = array_shift($this->error_responses);
            
            return new \WP_Error(
                'tmdb_api_error',
                $error['message'],
                ['status' => $error['status_code']]
            );
        }
        
        // Parse URL to find endpoint
        $parsed_url = parse_url($url);
        $path = trim($parsed_url['path'], '/');
        
        // Remove API version prefix
        $path = preg_replace('/^3\//', '', $path);
        
        // Add query string for search endpoints
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query_params);
            if (isset($query_params['query'])) {
                $path .= '?query=' . urlencode($query_params['query']);
            }
        }
        
        // Check if we have a mocked response for this endpoint
        if (isset($this->mocked_responses[$path])) {
            $response_data = $this->mocked_responses[$path];
            
            return [
                'headers' => [
                    'content-type' => 'application/json',
                    'x-ratelimit-remaining' => '39',
                    'x-ratelimit-limit' => '40'
                ],
                'body' => wp_json_encode($response_data),
                'response' => [
                    'code' => 200,
                    'message' => 'OK'
                ],
                'cookies' => [],
                'http_response' => null
            ];
        }
        
        // Return default empty response for unmocked endpoints
        return [
            'headers' => ['content-type' => 'application/json'],
            'body' => wp_json_encode(['success' => false, 'message' => 'Mocked endpoint not configured']),
            'response' => [
                'code' => 404,
                'message' => 'Not Found'
            ],
            'cookies' => [],
            'http_response' => null
        ];
    }
    
    /**
     * Get call count
     * 
     * @return int Number of API calls made
     */
    public function get_call_count(): int {
        return $this->call_count;
    }
    
    /**
     * Reset call count
     */
    public function reset_call_count(): void {
        $this->call_count = 0;
    }
    
    /**
     * Clear all mocked responses
     */
    public function clear_mocked_responses(): void {
        $this->mocked_responses = [];
        $this->error_responses = [];
    }
    
    /**
     * Mock popular movies response
     * 
     * @param array $movies Array of movie data
     */
    public function mock_popular_movies($movies): void {
        $response = [
            'page' => 1,
            'total_results' => count($movies),
            'total_pages' => 1,
            'results' => $movies
        ];
        
        $this->mocked_responses['movie/popular'] = $response;
    }
    
    /**
     * Mock movie credits response
     * 
     * @param int $movie_id TMDB movie ID
     * @param array $cast Cast data
     * @param array $crew Crew data
     */
    public function mock_movie_credits($movie_id, $cast = [], $crew = []): void {
        $response = [
            'id' => $movie_id,
            'cast' => $cast,
            'crew' => $crew
        ];
        
        $this->mocked_responses["movie/{$movie_id}/credits"] = $response;
    }
    
    /**
     * Mock movie images response
     * 
     * @param int $movie_id TMDB movie ID
     * @param array $backdrops Backdrop images
     * @param array $posters Poster images
     */
    public function mock_movie_images($movie_id, $backdrops = [], $posters = []): void {
        $response = [
            'id' => $movie_id,
            'backdrops' => $backdrops,
            'posters' => $posters,
            'logos' => []
        ];
        
        $this->mocked_responses["movie/{$movie_id}/images"] = $response;
    }
    
    /**
     * Mock movie videos response
     * 
     * @param int $movie_id TMDB movie ID
     * @param array $videos Video data
     */
    public function mock_movie_videos($movie_id, $videos = []): void {
        $response = [
            'id' => $movie_id,
            'results' => $videos
        ];
        
        $this->mocked_responses["movie/{$movie_id}/videos"] = $response;
    }
    
    /**
     * Create sample movie data
     * 
     * @param array $overrides Data to override defaults
     * @return array Sample movie data
     */
    public function create_sample_movie($overrides = []): array {
        $defaults = [
            'id' => 550,
            'title' => 'Fight Club',
            'original_title' => 'Fight Club',
            'overview' => 'A ticking-time-bomb insomniac and a slippery soap salesman channel primal male aggression into a shocking new form of therapy.',
            'release_date' => '1999-10-15',
            'vote_average' => 8.8,
            'vote_count' => 26280,
            'popularity' => 61.416,
            'runtime' => 139,
            'budget' => 63000000,
            'revenue' => 100853753,
            'status' => 'Released',
            'tagline' => 'Mischief. Mayhem. Soap.',
            'homepage' => '',
            'adult' => false,
            'backdrop_path' => '/fCayJrkfRaCRCTh8GqN30f8oyQF.jpg',
            'poster_path' => '/pB8BM7pdSp6B6Ih7QZ4DrQ3PmJK.jpg',
            'genres' => [
                ['id' => 18, 'name' => 'Drama'],
                ['id' => 53, 'name' => 'Thriller']
            ],
            'production_companies' => [
                [
                    'id' => 508,
                    'name' => 'Regency Enterprises',
                    'logo_path' => '/7PzJdsLGlR7oW4J0J5Xcd0pHGRg.png',
                    'origin_country' => 'US'
                ]
            ],
            'production_countries' => [
                ['iso_3166_1' => 'US', 'name' => 'United States of America']
            ],
            'spoken_languages' => [
                ['english_name' => 'English', 'iso_639_1' => 'en', 'name' => 'English']
            ]
        ];
        
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Create sample TV show data
     * 
     * @param array $overrides Data to override defaults
     * @return array Sample TV show data
     */
    public function create_sample_tv_show($overrides = []): array {
        $defaults = [
            'id' => 1399,
            'name' => 'Game of Thrones',
            'original_name' => 'Game of Thrones',
            'overview' => 'Seven noble families fight for control of the mythical land of Westeros.',
            'first_air_date' => '2011-04-17',
            'last_air_date' => '2019-05-19',
            'vote_average' => 9.3,
            'vote_count' => 11504,
            'popularity' => 369.594,
            'number_of_episodes' => 73,
            'number_of_seasons' => 8,
            'status' => 'Ended',
            'type' => 'Scripted',
            'homepage' => 'http://www.hbo.com/game-of-thrones',
            'backdrop_path' => '/suopoADq0k8YZr4dQXcU6pToj6s.jpg',
            'poster_path' => '/u3bZgnGQ9T01sWNhyveQz0wH0Hl.jpg',
            'genres' => [
                ['id' => 16, 'name' => 'Animation'],
                ['id' => 18, 'name' => 'Drama']
            ]
        ];
        
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Create sample person data
     * 
     * @param array $overrides Data to override defaults
     * @return array Sample person data
     */
    public function create_sample_person($overrides = []): array {
        $defaults = [
            'id' => 287,
            'name' => 'Brad Pitt',
            'biography' => 'William Bradley "Brad" Pitt is an American actor and film producer.',
            'birthday' => '1963-12-18',
            'deathday' => null,
            'place_of_birth' => 'Shawnee, Oklahoma, USA',
            'also_known_as' => ['William Bradley Pitt'],
            'gender' => 2,
            'adult' => false,
            'popularity' => 10.647,
            'profile_path' => '/kU3B75TyRiCgE270EyZnHjfivoq.jpg',
            'homepage' => null,
            'known_for_department' => 'Acting'
        ];
        
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Destructor - clean up hooks
     */
    public function __destruct() {
        remove_filter('pre_http_request', [$this, 'intercept_http_request'], 10);
    }
}