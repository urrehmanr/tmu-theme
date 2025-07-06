<?php
/**
 * TMDB API Client
 * 
 * Main TMDB API client providing comprehensive API communication with caching,
 * rate limiting, error handling, and support for all TMDB endpoints.
 * 
 * @package TMU\API\TMDB
 * @since 1.0.0
 */

namespace TMU\API\TMDB;

/**
 * Client class for TMDB API communication
 * 
 * Handles all TMDB API requests with intelligent caching and rate limiting
 */
class Client {
    
    /**
     * TMDB API base URL
     */
    const BASE_URL = 'https://api.themoviedb.org/3';
    
    /**
     * TMDB image base URL
     */
    const IMAGE_BASE_URL = 'https://image.tmdb.org/t/p/';
    
    /**
     * API version
     */
    const API_VERSION = '3';
    
    /**
     * Default timeout for requests
     */
    const DEFAULT_TIMEOUT = 30;
    
    /**
     * API key
     * 
     * @var string
     */
    private $apiKey;
    
    /**
     * Default language
     * 
     * @var string
     */
    private $language;
    
    /**
     * Default region
     * 
     * @var string
     */
    private $region;
    
    /**
     * Request timeout
     * 
     * @var int
     */
    private $timeout;
    
    /**
     * Constructor
     * 
     * @param string|null $apiKey TMDB API key
     * @param string $language Default language
     * @param string $region Default region
     */
    public function __construct(?string $apiKey = null, string $language = 'en-US', string $region = 'US') {
        $this->apiKey = $apiKey ?: get_option('tmu_tmdb_api_key', '');
        $this->language = $language;
        $this->region = $region;
        $this->timeout = self::DEFAULT_TIMEOUT;
        
        if (empty($this->apiKey)) {
            throw new Exception(
                'TMDB API key is required',
                Exception::ERROR_INVALID_API_KEY
            );
        }
    }
    
    /**
     * Get movie details
     * 
     * @param int $movieId TMDB movie ID
     * @param array $appendToResponse Additional data to append
     * @param string|null $language Language override
     * @return array Movie details
     * @throws Exception On API error
     */
    public function getMovieDetails(int $movieId, array $appendToResponse = [], ?string $language = null): array {
        $endpoint = "/movie/{$movieId}";
        
        $params = [
            'language' => $language ?: $this->language,
        ];
        
        if (!empty($appendToResponse)) {
            $params['append_to_response'] = implode(',', $appendToResponse);
        }
        
        return $this->makeRequest($endpoint, $params, 'movie_details');
    }
    
    /**
     * Get TV show details
     * 
     * @param int $tvId TMDB TV show ID
     * @param array $appendToResponse Additional data to append
     * @param string|null $language Language override
     * @return array TV show details
     * @throws Exception On API error
     */
    public function getTVDetails(int $tvId, array $appendToResponse = [], ?string $language = null): array {
        $endpoint = "/tv/{$tvId}";
        
        $params = [
            'language' => $language ?: $this->language,
        ];
        
        if (!empty($appendToResponse)) {
            $params['append_to_response'] = implode(',', $appendToResponse);
        }
        
        return $this->makeRequest($endpoint, $params, 'tv_details');
    }
    
    /**
     * Get person details
     * 
     * @param int $personId TMDB person ID
     * @param array $appendToResponse Additional data to append
     * @param string|null $language Language override
     * @return array Person details
     * @throws Exception On API error
     */
    public function getPersonDetails(int $personId, array $appendToResponse = [], ?string $language = null): array {
        $endpoint = "/person/{$personId}";
        
        $params = [
            'language' => $language ?: $this->language,
        ];
        
        if (!empty($appendToResponse)) {
            $params['append_to_response'] = implode(',', $appendToResponse);
        }
        
        return $this->makeRequest($endpoint, $params, 'person_details');
    }
    
    /**
     * Search movies
     * 
     * @param string $query Search query
     * @param int $page Page number
     * @param bool $includeAdult Include adult content
     * @param string|null $language Language override
     * @param string|null $region Region override
     * @param int|null $year Year filter
     * @param int|null $primaryReleaseYear Primary release year filter
     * @return array Search results
     * @throws Exception On API error
     */
    public function searchMovies(
        string $query,
        int $page = 1,
        bool $includeAdult = false,
        ?string $language = null,
        ?string $region = null,
        ?int $year = null,
        ?int $primaryReleaseYear = null
    ): array {
        $endpoint = '/search/movie';
        
        $params = [
            'query' => $query,
            'page' => $page,
            'include_adult' => $includeAdult ? 'true' : 'false',
            'language' => $language ?: $this->language,
            'region' => $region ?: $this->region,
        ];
        
        if ($year !== null) {
            $params['year'] = $year;
        }
        
        if ($primaryReleaseYear !== null) {
            $params['primary_release_year'] = $primaryReleaseYear;
        }
        
        return $this->makeRequest($endpoint, $params, 'search_results');
    }
    
    /**
     * Search TV shows
     * 
     * @param string $query Search query
     * @param int $page Page number
     * @param bool $includeAdult Include adult content
     * @param string|null $language Language override
     * @param int|null $firstAirDateYear First air date year filter
     * @return array Search results
     * @throws Exception On API error
     */
    public function searchTV(
        string $query,
        int $page = 1,
        bool $includeAdult = false,
        ?string $language = null,
        ?int $firstAirDateYear = null
    ): array {
        $endpoint = '/search/tv';
        
        $params = [
            'query' => $query,
            'page' => $page,
            'include_adult' => $includeAdult ? 'true' : 'false',
            'language' => $language ?: $this->language,
        ];
        
        if ($firstAirDateYear !== null) {
            $params['first_air_date_year'] = $firstAirDateYear;
        }
        
        return $this->makeRequest($endpoint, $params, 'search_results');
    }
    
    /**
     * Search people
     * 
     * @param string $query Search query
     * @param int $page Page number
     * @param bool $includeAdult Include adult content
     * @param string|null $language Language override
     * @param string|null $region Region override
     * @return array Search results
     * @throws Exception On API error
     */
    public function searchPeople(
        string $query,
        int $page = 1,
        bool $includeAdult = false,
        ?string $language = null,
        ?string $region = null
    ): array {
        $endpoint = '/search/person';
        
        $params = [
            'query' => $query,
            'page' => $page,
            'include_adult' => $includeAdult ? 'true' : 'false',
            'language' => $language ?: $this->language,
            'region' => $region ?: $this->region,
        ];
        
        return $this->makeRequest($endpoint, $params, 'search_results');
    }
    
    /**
     * Multi search (movies, TV shows, people)
     * 
     * @param string $query Search query
     * @param int $page Page number
     * @param bool $includeAdult Include adult content
     * @param string|null $language Language override
     * @param string|null $region Region override
     * @return array Search results
     * @throws Exception On API error
     */
    public function multiSearch(
        string $query,
        int $page = 1,
        bool $includeAdult = false,
        ?string $language = null,
        ?string $region = null
    ): array {
        $endpoint = '/search/multi';
        
        $params = [
            'query' => $query,
            'page' => $page,
            'include_adult' => $includeAdult ? 'true' : 'false',
            'language' => $language ?: $this->language,
            'region' => $region ?: $this->region,
        ];
        
        return $this->makeRequest($endpoint, $params, 'search_results');
    }
    
    /**
     * Get popular movies
     * 
     * @param int $page Page number
     * @param string|null $language Language override
     * @param string|null $region Region override
     * @return array Popular movies
     * @throws Exception On API error
     */
    public function getPopularMovies(int $page = 1, ?string $language = null, ?string $region = null): array {
        $endpoint = '/movie/popular';
        
        $params = [
            'page' => $page,
            'language' => $language ?: $this->language,
            'region' => $region ?: $this->region,
        ];
        
        return $this->makeRequest($endpoint, $params, 'popular_movies');
    }
    
    /**
     * Get popular TV shows
     * 
     * @param int $page Page number
     * @param string|null $language Language override
     * @return array Popular TV shows
     * @throws Exception On API error
     */
    public function getPopularTV(int $page = 1, ?string $language = null): array {
        $endpoint = '/tv/popular';
        
        $params = [
            'page' => $page,
            'language' => $language ?: $this->language,
        ];
        
        return $this->makeRequest($endpoint, $params, 'popular_tv');
    }
    
    /**
     * Get trending content
     * 
     * @param string $mediaType Media type (all, movie, tv, person)
     * @param string $timeWindow Time window (day, week)
     * @param int $page Page number
     * @return array Trending content
     * @throws Exception On API error
     */
    public function getTrending(string $mediaType = 'all', string $timeWindow = 'week', int $page = 1): array {
        $endpoint = "/trending/{$mediaType}/{$timeWindow}";
        
        $params = [
            'page' => $page,
        ];
        
        return $this->makeRequest($endpoint, $params, 'trending');
    }
    
    /**
     * Get movie genres
     * 
     * @param string|null $language Language override
     * @return array Movie genres
     * @throws Exception On API error
     */
    public function getMovieGenres(?string $language = null): array {
        $endpoint = '/genre/movie/list';
        
        $params = [
            'language' => $language ?: $this->language,
        ];
        
        return $this->makeRequest($endpoint, $params, 'genres');
    }
    
    /**
     * Get TV genres
     * 
     * @param string|null $language Language override
     * @return array TV genres
     * @throws Exception On API error
     */
    public function getTVGenres(?string $language = null): array {
        $endpoint = '/genre/tv/list';
        
        $params = [
            'language' => $language ?: $this->language,
        ];
        
        return $this->makeRequest($endpoint, $params, 'genres');
    }
    
    /**
     * Get configuration
     * 
     * @return array API configuration
     * @throws Exception On API error
     */
    public function getConfiguration(): array {
        $endpoint = '/configuration';
        
        return $this->makeRequest($endpoint, [], 'configuration');
    }
    
    /**
     * Get countries
     * 
     * @param string|null $language Language override
     * @return array Countries
     * @throws Exception On API error
     */
    public function getCountries(?string $language = null): array {
        $endpoint = '/configuration/countries';
        
        $params = [
            'language' => $language ?: $this->language,
        ];
        
        return $this->makeRequest($endpoint, $params, 'countries');
    }
    
    /**
     * Get languages
     * 
     * @return array Languages
     * @throws Exception On API error
     */
    public function getLanguages(): array {
        $endpoint = '/configuration/languages';
        
        return $this->makeRequest($endpoint, [], 'languages');
    }
    
    /**
     * Get movie credits
     * 
     * @param int $movieId TMDB movie ID
     * @param string|null $language Language override
     * @return array Movie credits
     * @throws Exception On API error
     */
    public function getMovieCredits(int $movieId, ?string $language = null): array {
        $endpoint = "/movie/{$movieId}/credits";
        
        $params = [
            'language' => $language ?: $this->language,
        ];
        
        return $this->makeRequest($endpoint, $params, 'movie_details');
    }
    
    /**
     * Get TV credits
     * 
     * @param int $tvId TMDB TV show ID
     * @param string|null $language Language override
     * @return array TV credits
     * @throws Exception On API error
     */
    public function getTVCredits(int $tvId, ?string $language = null): array {
        $endpoint = "/tv/{$tvId}/credits";
        
        $params = [
            'language' => $language ?: $this->language,
        ];
        
        return $this->makeRequest($endpoint, $params, 'tv_details');
    }
    
    /**
     * Get person movie credits
     * 
     * @param int $personId TMDB person ID
     * @param string|null $language Language override
     * @return array Person movie credits
     * @throws Exception On API error
     */
    public function getPersonMovieCredits(int $personId, ?string $language = null): array {
        $endpoint = "/person/{$personId}/movie_credits";
        
        $params = [
            'language' => $language ?: $this->language,
        ];
        
        return $this->makeRequest($endpoint, $params, 'person_details');
    }
    
    /**
     * Get person TV credits
     * 
     * @param int $personId TMDB person ID
     * @param string|null $language Language override
     * @return array Person TV credits
     * @throws Exception On API error
     */
    public function getPersonTVCredits(int $personId, ?string $language = null): array {
        $endpoint = "/person/{$personId}/tv_credits";
        
        $params = [
            'language' => $language ?: $this->language,
        ];
        
        return $this->makeRequest($endpoint, $params, 'person_details');
    }
    
    /**
     * Build image URL
     * 
     * @param string $imagePath Image path from TMDB
     * @param string $size Image size (w92, w154, w185, w342, w500, w780, original)
     * @return string Complete image URL
     */
    public function buildImageUrl(string $imagePath, string $size = 'w500'): string {
        if (empty($imagePath)) {
            return '';
        }
        
        // Remove leading slash if present
        $imagePath = ltrim($imagePath, '/');
        
        return self::IMAGE_BASE_URL . $size . '/' . $imagePath;
    }
    
    /**
     * Test API connection
     * 
     * @return bool Whether connection is successful
     */
    public function testConnection(): bool {
        try {
            $result = $this->getConfiguration();
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Set API key
     * 
     * @param string $apiKey TMDB API key
     */
    public function setApiKey(string $apiKey): void {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Set default language
     * 
     * @param string $language Language code
     */
    public function setLanguage(string $language): void {
        $this->language = $language;
    }
    
    /**
     * Set default region
     * 
     * @param string $region Region code
     */
    public function setRegion(string $region): void {
        $this->region = $region;
    }
    
    /**
     * Set request timeout
     * 
     * @param int $timeout Timeout in seconds
     */
    public function setTimeout(int $timeout): void {
        $this->timeout = max(5, $timeout);
    }
    
    /**
     * Make API request
     * 
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @param string $cacheType Cache type for TTL
     * @return array API response data
     * @throws Exception On API error
     */
    private function makeRequest(string $endpoint, array $params = [], string $cacheType = 'default'): array {
        // Add API key to parameters
        $params['api_key'] = $this->apiKey;
        
        // Generate cache key
        $cache_key = md5($endpoint . serialize($params));
        
        // Check cache first
        $cached_data = Cache::get($cache_key, $cacheType);
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Check rate limiting
        if (!RateLimiter::isAllowed($endpoint)) {
            $wait_time = RateLimiter::getWaitTime($endpoint);
            throw new Exception(
                "Rate limit exceeded. Wait {$wait_time} seconds.",
                Exception::ERROR_REQUEST_LIMIT_EXCEEDED,
                429,
                ['wait_time' => $wait_time]
            );
        }
        
        // Build URL
        $url = self::BASE_URL . $endpoint . '?' . http_build_query($params);
        
        // Make HTTP request
        $response = wp_remote_get($url, [
            'timeout' => $this->timeout,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'TMU-Theme/1.0 (+' . home_url() . ')',
            ],
        ]);
        
        // Record request for rate limiting
        $success = !is_wp_error($response);
        RateLimiter::recordRequest($endpoint, $success);
        
        // Handle HTTP errors
        if (is_wp_error($response)) {
            throw Exception::fromHttpError($response);
        }
        
        // Get response data
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Parse JSON
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(
                'Invalid JSON response from TMDB API',
                Exception::ERROR_INVALID_FORMAT,
                $http_code
            );
        }
        
        // Handle API errors
        if ($http_code !== 200) {
            throw Exception::fromApiResponse($data, $http_code);
        }
        
        // Handle TMDB API error responses
        if (isset($data['status_code']) && $data['status_code'] !== 1) {
            throw Exception::fromApiResponse($data, $http_code);
        }
        
        // Cache successful response
        Cache::set($cache_key, $data, $cacheType);
        
        return $data;
    }
    
    /**
     * Clear all cached data for this client
     * 
     * @return int Number of cache entries cleared
     */
    public function clearCache(): int {
        return Cache::clear();
    }
    
    /**
     * Get client statistics
     * 
     * @return array Client statistics
     */
    public function getStats(): array {
        return [
            'api_key_set' => !empty($this->apiKey),
            'language' => $this->language,
            'region' => $this->region,
            'timeout' => $this->timeout,
            'cache_stats' => Cache::getStats(),
            'rate_limit_stats' => RateLimiter::getAllStats(),
        ];
    }
}