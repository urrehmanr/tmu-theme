<?php
namespace TMU\API\TMDB;

use TMU\API\TMDB\Client;
use TMU\API\TMDB\Exception as TMDBException;

/**
 * TMDB search functionality for content discovery
 */
class SearchService {
    private $client;
    
    public function __construct() {
        $this->client = new Client();
    }
    
    /**
     * Search for movies
     */
    public function search_movies(string $query, int $page = 1): array {
        try {
            $params = [
                'query' => $query,
                'page' => $page,
                'include_adult' => 'false'
            ];
            
            return $this->client->make_request('/search/movie', $params);
        } catch (TMDBException $e) {
            error_log("Movie search error: " . $e->getMessage());
            return ['results' => [], 'total_results' => 0];
        }
    }
    
    /**
     * Search for TV shows
     */
    public function search_tv_shows(string $query, int $page = 1): array {
        try {
            $params = [
                'query' => $query,
                'page' => $page,
                'include_adult' => 'false'
            ];
            
            return $this->client->make_request('/search/tv', $params);
        } catch (TMDBException $e) {
            error_log("TV show search error: " . $e->getMessage());
            return ['results' => [], 'total_results' => 0];
        }
    }
    
    /**
     * Search for people
     */
    public function search_people(string $query, int $page = 1): array {
        try {
            $params = [
                'query' => $query,
                'page' => $page,
                'include_adult' => 'false'
            ];
            
            return $this->client->make_request('/search/person', $params);
        } catch (TMDBException $e) {
            error_log("Person search error: " . $e->getMessage());
            return ['results' => [], 'total_results' => 0];
        }
    }
    
    /**
     * Multi-search (movies, TV shows, and people)
     */
    public function multi_search(string $query, int $page = 1): array {
        try {
            $params = [
                'query' => $query,
                'page' => $page,
                'include_adult' => 'false'
            ];
            
            $results = $this->client->make_request('/search/multi', $params);
            
            // Categorize results by type
            $categorized = [
                'movies' => [],
                'tv_shows' => [],
                'people' => []
            ];
            
            foreach ($results['results'] as $result) {
                switch ($result['media_type']) {
                    case 'movie':
                        $categorized['movies'][] = $result;
                        break;
                    case 'tv':
                        $categorized['tv_shows'][] = $result;
                        break;
                    case 'person':
                        $categorized['people'][] = $result;
                        break;
                }
            }
            
            return [
                'results' => $categorized,
                'total_results' => $results['total_results'],
                'total_pages' => $results['total_pages']
            ];
        } catch (TMDBException $e) {
            error_log("Multi-search error: " . $e->getMessage());
            return [
                'results' => ['movies' => [], 'tv_shows' => [], 'people' => []], 
                'total_results' => 0
            ];
        }
    }
    
    /**
     * Search for content and check if it exists locally
     */
    public function search_with_local_check(string $query, string $type = 'multi'): array {
        $search_results = match($type) {
            'movie' => $this->search_movies($query),
            'tv' => $this->search_tv_shows($query),
            'person' => $this->search_people($query),
            default => $this->multi_search($query)
        };
        
        if ($type === 'multi') {
            // Check each category for local content
            foreach ($search_results['results'] as $media_type => &$items) {
                foreach ($items as &$item) {
                    $item['local_post_id'] = $this->find_local_post($item['id'], $media_type);
                }
            }
        } else {
            // Check single type for local content
            foreach ($search_results['results'] as &$item) {
                $item['local_post_id'] = $this->find_local_post($item['id'], $type);
            }
        }
        
        return $search_results;
    }
    
    /**
     * Find local post by TMDB ID
     */
    private function find_local_post(int $tmdb_id, string $type): ?int {
        $post_type = match($type) {
            'movies' => 'movie',
            'tv_shows' => 'tv',
            'people' => 'people',
            default => $type
        };
        
        $posts = get_posts([
            'post_type' => $post_type,
            'meta_key' => 'tmdb_id',
            'meta_value' => $tmdb_id,
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);
        
        return $posts ? $posts[0] : null;
    }
    
    /**
     * Get trending content
     */
    public function get_trending(string $media_type = 'all', string $time_window = 'day'): array {
        try {
            $endpoint = "/trending/{$media_type}/{$time_window}";
            return $this->client->make_request($endpoint);
        } catch (TMDBException $e) {
            error_log("Trending content error: " . $e->getMessage());
            return ['results' => [], 'total_results' => 0];
        }
    }
    
    /**
     * Get popular content
     */
    public function get_popular(string $media_type = 'movie', int $page = 1): array {
        try {
            $endpoint = "/{$media_type}/popular";
            $params = ['page' => $page];
            
            return $this->client->make_request($endpoint, $params);
        } catch (TMDBException $e) {
            error_log("Popular content error: " . $e->getMessage());
            return ['results' => [], 'total_results' => 0];
        }
    }
    
    /**
     * Get top rated content
     */
    public function get_top_rated(string $media_type = 'movie', int $page = 1): array {
        try {
            $endpoint = "/{$media_type}/top_rated";
            $params = ['page' => $page];
            
            return $this->client->make_request($endpoint, $params);
        } catch (TMDBException $e) {
            error_log("Top rated content error: " . $e->getMessage());
            return ['results' => [], 'total_results' => 0];
        }
    }
    
    /**
     * Get now playing movies
     */
    public function get_now_playing(int $page = 1): array {
        try {
            $params = ['page' => $page];
            return $this->client->make_request('/movie/now_playing', $params);
        } catch (TMDBException $e) {
            error_log("Now playing movies error: " . $e->getMessage());
            return ['results' => [], 'total_results' => 0];
        }
    }
    
    /**
     * Get upcoming movies
     */
    public function get_upcoming(int $page = 1): array {
        try {
            $params = ['page' => $page];
            return $this->client->make_request('/movie/upcoming', $params);
        } catch (TMDBException $e) {
            error_log("Upcoming movies error: " . $e->getMessage());
            return ['results' => [], 'total_results' => 0];
        }
    }
    
    /**
     * Get TV shows airing today
     */
    public function get_airing_today(int $page = 1): array {
        try {
            $params = ['page' => $page];
            return $this->client->make_request('/tv/airing_today', $params);
        } catch (TMDBException $e) {
            error_log("Airing today error: " . $e->getMessage());
            return ['results' => [], 'total_results' => 0];
        }
    }
    
    /**
     * Get TV shows on the air
     */
    public function get_on_the_air(int $page = 1): array {
        try {
            $params = ['page' => $page];
            return $this->client->make_request('/tv/on_the_air', $params);
        } catch (TMDBException $e) {
            error_log("On the air error: " . $e->getMessage());
            return ['results' => [], 'total_results' => 0];
        }
    }
    
    /**
     * Discover content with filters
     */
    public function discover(string $media_type = 'movie', array $filters = []): array {
        try {
            $endpoint = "/discover/{$media_type}";
            $params = array_merge([
                'sort_by' => 'popularity.desc',
                'page' => 1
            ], $filters);
            
            return $this->client->make_request($endpoint, $params);
        } catch (TMDBException $e) {
            error_log("Discover content error: " . $e->getMessage());
            return ['results' => [], 'total_results' => 0];
        }
    }
    
    /**
     * Get similar content
     */
    public function get_similar(int $tmdb_id, string $media_type = 'movie', int $page = 1): array {
        try {
            $endpoint = "/{$media_type}/{$tmdb_id}/similar";
            $params = ['page' => $page];
            
            return $this->client->make_request($endpoint, $params);
        } catch (TMDBException $e) {
            error_log("Similar content error: " . $e->getMessage());
            return ['results' => [], 'total_results' => 0];
        }
    }
    
    /**
     * Get recommendations
     */
    public function get_recommendations(int $tmdb_id, string $media_type = 'movie', int $page = 1): array {
        try {
            $endpoint = "/{$media_type}/{$tmdb_id}/recommendations";
            $params = ['page' => $page];
            
            return $this->client->make_request($endpoint, $params);
        } catch (TMDBException $e) {
            error_log("Recommendations error: " . $e->getMessage());
            return ['results' => [], 'total_results' => 0];
        }
    }
    
    /**
     * Get content by genre
     */
    public function get_by_genre(int $genre_id, string $media_type = 'movie', int $page = 1): array {
        try {
            $filters = [
                'with_genres' => $genre_id,
                'page' => $page
            ];
            
            return $this->discover($media_type, $filters);
        } catch (TMDBException $e) {
            error_log("Genre content error: " . $e->getMessage());
            return ['results' => [], 'total_results' => 0];
        }
    }
    
    /**
     * Get content by year
     */
    public function get_by_year(int $year, string $media_type = 'movie', int $page = 1): array {
        try {
            $filters = [
                'page' => $page
            ];
            
            if ($media_type === 'movie') {
                $filters['year'] = $year;
            } else {
                $filters['first_air_date_year'] = $year;
            }
            
            return $this->discover($media_type, $filters);
        } catch (TMDBException $e) {
            error_log("Year content error: " . $e->getMessage());
            return ['results' => [], 'total_results' => 0];
        }
    }
    
    /**
     * Advanced search with multiple filters
     */
    public function advanced_search(array $filters): array {
        $media_type = $filters['media_type'] ?? 'movie';
        unset($filters['media_type']);
        
        return $this->discover($media_type, $filters);
    }
    
    /**
     * Get cached search results
     */
    public function get_cached_search(string $query, string $type = 'multi'): ?array {
        $cache_key = "tmdb_search_{$type}_" . md5($query);
        return get_transient($cache_key) ?: null;
    }
    
    /**
     * Cache search results
     */
    public function cache_search_results(string $query, string $type, array $results): void {
        $cache_key = "tmdb_search_{$type}_" . md5($query);
        set_transient($cache_key, $results, 3600); // Cache for 1 hour
    }
}