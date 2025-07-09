<?php
namespace TMU\API\REST;

/**
 * Search REST API Endpoints
 * 
 * Provides REST API endpoints for search functionality
 */
class SearchEndpoints {
    
    /**
     * Initialize REST endpoints
     */
    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes(): void {
        // Advanced search endpoint
        register_rest_route('tmu/v1', '/search', [
            'methods' => 'GET',
            'callback' => [$this, 'search'],
            'permission_callback' => '__return_true',
            'args' => [
                'q' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => 'Search query string'
                ],
                'post_type' => [
                    'required' => false,
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Post types to search'
                ],
                'genre' => [
                    'required' => false,
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Genre filters'
                ],
                'country' => [
                    'required' => false,
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Country filters'
                ],
                'year' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Year range filter (e.g., "2020-2023")'
                ],
                'rating' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Rating range filter (e.g., "7-10")'
                ],
                'per_page' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 20,
                    'minimum' => 1,
                    'maximum' => 100,
                    'description' => 'Number of results per page'
                ],
                'page' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                    'description' => 'Page number'
                ],
                'orderby' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => 'relevance',
                    'enum' => ['relevance', 'date', 'title', 'rating', 'popularity'],
                    'description' => 'Sort order'
                ]
            ]
        ]);
        
        // Autocomplete suggestions endpoint
        register_rest_route('tmu/v1', '/suggestions', [
            'methods' => 'GET',
            'callback' => [$this, 'get_suggestions'],
            'permission_callback' => '__return_true',
            'args' => [
                'q' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => 'Search query string'
                ],
                'limit' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 20,
                    'description' => 'Number of suggestions'
                ]
            ]
        ]);
        
        // Facet data endpoint
        register_rest_route('tmu/v1', '/facets', [
            'methods' => 'GET',
            'callback' => [$this, 'get_facets'],
            'permission_callback' => '__return_true',
            'args' => [
                'q' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => 'Search query string'
                ],
                'filters' => [
                    'required' => false,
                    'type' => 'object',
                    'description' => 'Current filters'
                ]
            ]
        ]);
        
        // Trending content endpoint
        register_rest_route('tmu/v1', '/trending', [
            'methods' => 'GET',
            'callback' => [$this, 'get_trending'],
            'permission_callback' => '__return_true',
            'args' => [
                'post_type' => [
                    'required' => false,
                    'type' => 'string',
                    'enum' => ['movie', 'tv', 'drama'],
                    'description' => 'Content type'
                ],
                'limit' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 20,
                    'minimum' => 1,
                    'maximum' => 50,
                    'description' => 'Number of trending items'
                ]
            ]
        ]);
        
        // Similar content endpoint
        register_rest_route('tmu/v1', '/similar/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_similar'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Post ID'
                ],
                'limit' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 20,
                    'description' => 'Number of similar items'
                ]
            ]
        ]);
    }
    
    /**
     * Handle search request
     */
    public function search(\WP_REST_Request $request): \WP_REST_Response {
        $query = $request->get_param('q');
        $filters = $this->extract_filters($request);
        $options = [
            'per_page' => $request->get_param('per_page'),
            'page' => $request->get_param('page'),
            'orderby' => $request->get_param('orderby')
        ];
        
        $search_manager = \TMU\Search\SearchManager::getInstance();
        $results = $search_manager->search($query, $filters, $options);
        
        return new \WP_REST_Response([
            'results' => array_map([$this, 'format_search_result'], $results->get_results()),
            'total' => $results->get_total(),
            'total_pages' => $results->get_max_pages(),
            'facets' => $results->get_facets(),
            'recommendations' => $results->get_recommendations(),
            'execution_time' => $results->get_execution_time(),
            'query' => $results->get_query(),
            'filters' => $results->get_filters()
        ]);
    }
    
    /**
     * Get autocomplete suggestions
     */
    public function get_suggestions(\WP_REST_Request $request): \WP_REST_Response {
        $query = $request->get_param('q');
        $limit = $request->get_param('limit');
        
        if (strlen($query) < 2) {
            return new \WP_REST_Response([]);
        }
        
        $ajax_search = new \TMU\Search\AjaxSearch();
        $suggestions = $ajax_search->get_suggestions($query, $limit);
        
        return new \WP_REST_Response($suggestions);
    }
    
    /**
     * Get facet data
     */
    public function get_facets(\WP_REST_Request $request): \WP_REST_Response {
        $query = $request->get_param('q');
        $filters = $request->get_param('filters') ?: [];
        
        $filter_manager = new \TMU\Search\FilterManager();
        $facets = $filter_manager->get_facet_data($query, $filters);
        
        return new \WP_REST_Response($facets);
    }
    
    /**
     * Get trending content
     */
    public function get_trending(\WP_REST_Request $request): \WP_REST_Response {
        $post_type = $request->get_param('post_type');
        $limit = $request->get_param('limit');
        
        $recommendation_engine = new \TMU\Search\RecommendationEngine();
        $trending = $recommendation_engine->get_trending_content($post_type, $limit);
        
        return new \WP_REST_Response([
            'trending' => array_map([$this, 'format_search_result'], $trending)
        ]);
    }
    
    /**
     * Get similar content
     */
    public function get_similar(\WP_REST_Request $request): \WP_REST_Response {
        $post_id = $request->get_param('id');
        $limit = $request->get_param('limit');
        
        $recommendation_engine = new \TMU\Search\RecommendationEngine();
        $similar = $recommendation_engine->get_similar_content($post_id, $limit);
        
        return new \WP_REST_Response([
            'similar' => array_map([$this, 'format_search_result'], $similar)
        ]);
    }
    
    /**
     * Extract filters from request
     */
    private function extract_filters(\WP_REST_Request $request): array {
        $filters = [];
        
        $filter_params = ['post_type', 'genre', 'country', 'year', 'rating'];
        
        foreach ($filter_params as $param) {
            $value = $request->get_param($param);
            if (!empty($value)) {
                $filters[$param] = $value;
            }
        }
        
        return $filters;
    }
    
    /**
     * Format search result for API response
     */
    private function format_search_result($post): array {
        $post_type = get_post_type($post->ID);
        $data = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'type' => $post_type,
            'url' => get_permalink($post->ID),
            'excerpt' => get_the_excerpt($post->ID),
            'date' => $post->post_date,
            'modified' => $post->post_modified
        ];
        
        // Add featured image
        if (has_post_thumbnail($post->ID)) {
            $data['featured_image'] = [
                'full' => get_the_post_thumbnail_url($post->ID, 'full'),
                'large' => get_the_post_thumbnail_url($post->ID, 'large'),
                'medium' => get_the_post_thumbnail_url($post->ID, 'medium'),
                'thumbnail' => get_the_post_thumbnail_url($post->ID, 'thumbnail')
            ];
        }
        
        // Add post-type specific data
        switch ($post_type) {
            case 'movie':
                $movie_data = function_exists('tmu_get_movie_data') ? tmu_get_movie_data($post->ID) : [];
                $data = array_merge($data, [
                    'overview' => $movie_data['overview'] ?? '',
                    'release_date' => $movie_data['release_date'] ?? '',
                    'runtime' => $movie_data['runtime'] ?? 0,
                    'rating' => $movie_data['vote_average'] ?? 0,
                    'popularity' => $movie_data['popularity'] ?? 0,
                    'genres' => wp_get_post_terms($post->ID, 'genre', ['fields' => 'names']),
                    'countries' => wp_get_post_terms($post->ID, 'country', ['fields' => 'names'])
                ]);
                break;
                
            case 'tv':
            case 'drama':
                $tv_data = function_exists('tmu_get_tv_data') ? tmu_get_tv_data($post->ID) : [];
                $data = array_merge($data, [
                    'overview' => $tv_data['overview'] ?? '',
                    'first_air_date' => $tv_data['first_air_date'] ?? '',
                    'last_air_date' => $tv_data['last_air_date'] ?? '',
                    'number_of_seasons' => $tv_data['number_of_seasons'] ?? 0,
                    'number_of_episodes' => $tv_data['number_of_episodes'] ?? 0,
                    'rating' => $tv_data['vote_average'] ?? 0,
                    'popularity' => $tv_data['popularity'] ?? 0,
                    'genres' => wp_get_post_terms($post->ID, 'genre', ['fields' => 'names']),
                    'countries' => wp_get_post_terms($post->ID, 'country', ['fields' => 'names'])
                ]);
                break;
                
            case 'people':
                $person_data = function_exists('tmu_get_person_data') ? tmu_get_person_data($post->ID) : [];
                $data = array_merge($data, [
                    'biography' => $person_data['biography'] ?? '',
                    'birthday' => $person_data['birthday'] ?? '',
                    'place_of_birth' => $person_data['place_of_birth'] ?? '',
                    'known_for_department' => $person_data['known_for_department'] ?? '',
                    'popularity' => $person_data['popularity'] ?? 0
                ]);
                break;
        }
        
        return $data;
    }
}