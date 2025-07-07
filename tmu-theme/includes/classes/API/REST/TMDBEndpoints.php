<?php
/**
 * TMDB REST API Endpoints
 * 
 * Dedicated REST API endpoints for TMDB operations
 * 
 * @package TMU\API\REST
 * @since 1.0.0
 */

namespace TMU\API\REST;

use TMU\API\TMDB\Client;
use TMU\API\TMDB\SyncService;
use TMU\API\TMDB\SearchService;
use TMU\API\TMDB\Exception;

/**
 * TMDBEndpoints class
 * 
 * Handles REST API endpoints for TMDB operations
 */
class TMDBEndpoints {
    
    /**
     * TMDB API client
     * @var Client
     */
    private $client;
    
    /**
     * Sync service
     * @var SyncService
     */
    private $sync_service;
    
    /**
     * Search service
     * @var SearchService
     */
    private $search_service;
    
    /**
     * Initialize the endpoints
     */
    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
        
        $this->client = new Client();
        $this->sync_service = new SyncService();
        $this->search_service = new SearchService();
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes(): void {
        $namespace = 'tmu/v1';
        $base = 'tmdb';
        
        // TMDB Search endpoints
        register_rest_route($namespace, "/{$base}/search", [
            'methods' => 'GET',
            'callback' => [$this, 'search_content'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => [
                'query' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Search query'
                ],
                'type' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => 'multi',
                    'enum' => ['movie', 'tv', 'person', 'multi'],
                    'description' => 'Content type to search'
                ],
                'page' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                    'description' => 'Page number'
                ]
            ]
        ]);
        
        // TMDB Movie details endpoint
        register_rest_route($namespace, "/{$base}/movie/(?P<id>\d+)", [
            'methods' => 'GET',
            'callback' => [$this, 'get_movie_details'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'TMDB movie ID'
                ],
                'append_to_response' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => 'credits,images,videos,keywords',
                    'description' => 'Additional data to include'
                ]
            ]
        ]);
        
        // TMDB TV details endpoint
        register_rest_route($namespace, "/{$base}/tv/(?P<id>\d+)", [
            'methods' => 'GET',
            'callback' => [$this, 'get_tv_details'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'TMDB TV show ID'
                ],
                'append_to_response' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => 'credits,images,videos,keywords',
                    'description' => 'Additional data to include'
                ]
            ]
        ]);
        
        // TMDB Person details endpoint
        register_rest_route($namespace, "/{$base}/person/(?P<id>\d+)", [
            'methods' => 'GET',
            'callback' => [$this, 'get_person_details'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'TMDB person ID'
                ],
                'append_to_response' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => 'movie_credits,tv_credits,images',
                    'description' => 'Additional data to include'
                ]
            ]
        ]);
        
        // TMDB Sync endpoint
        register_rest_route($namespace, "/{$base}/sync", [
            'methods' => 'POST',
            'callback' => [$this, 'sync_content'],
            'permission_callback' => [$this, 'check_edit_permissions'],
            'args' => [
                'post_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'WordPress post ID to sync'
                ],
                'options' => [
                    'required' => false,
                    'type' => 'object',
                    'default' => [],
                    'description' => 'Sync options'
                ]
            ]
        ]);
        
        // TMDB Bulk sync endpoint
        register_rest_route($namespace, "/{$base}/bulk-sync", [
            'methods' => 'POST',
            'callback' => [$this, 'bulk_sync_content'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'post_ids' => [
                    'required' => false,
                    'type' => 'array',
                    'description' => 'Array of post IDs to sync'
                ],
                'post_type' => [
                    'required' => false,
                    'type' => 'string',
                    'enum' => ['movie', 'tv', 'drama', 'people'],
                    'description' => 'Post type to bulk sync'
                ],
                'options' => [
                    'required' => false,
                    'type' => 'object',
                    'default' => [],
                    'description' => 'Sync options'
                ]
            ]
        ]);
        
        // TMDB Popular content endpoints
        register_rest_route($namespace, "/{$base}/popular/(?P<type>movie|tv)", [
            'methods' => 'GET',
            'callback' => [$this, 'get_popular_content'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => [
                'type' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['movie', 'tv'],
                    'description' => 'Content type'
                ],
                'page' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                    'description' => 'Page number'
                ]
            ]
        ]);
        
        // TMDB Trending content endpoint
        register_rest_route($namespace, "/{$base}/trending/(?P<media_type>all|movie|tv|person)/(?P<time_window>day|week)", [
            'methods' => 'GET',
            'callback' => [$this, 'get_trending_content'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => [
                'media_type' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['all', 'movie', 'tv', 'person'],
                    'description' => 'Media type'
                ],
                'time_window' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['day', 'week'],
                    'description' => 'Time window'
                ],
                'page' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                    'description' => 'Page number'
                ]
            ]
        ]);
        
        // TMDB Configuration endpoint
        register_rest_route($namespace, "/{$base}/configuration", [
            'methods' => 'GET',
            'callback' => [$this, 'get_configuration'],
            'permission_callback' => [$this, 'check_permissions']
        ]);
        
        // TMDB Genres endpoint
        register_rest_route($namespace, "/{$base}/genres/(?P<type>movie|tv)", [
            'methods' => 'GET',
            'callback' => [$this, 'get_genres'],
            'permission_callback' => [$this, 'check_permissions'],
            'args' => [
                'type' => [
                    'required' => true,
                    'type' => 'string',
                    'enum' => ['movie', 'tv'],
                    'description' => 'Content type'
                ]
            ]
        ]);
        
        // TMDB API status endpoint
        register_rest_route($namespace, "/{$base}/status", [
            'methods' => 'GET',
            'callback' => [$this, 'get_api_status'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);
    }
    
    /**
     * Search content via TMDB
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function search_content($request) {
        try {
            $query = $request['query'];
            $type = $request['type'];
            $page = $request['page'];
            
            $result = $this->search_service->search($query, $type, $page);
            
            return rest_ensure_response([
                'success' => true,
                'data' => $result,
                'search_params' => [
                    'query' => $query,
                    'type' => $type,
                    'page' => $page
                ]
            ]);
            
        } catch (Exception $e) {
            return new \WP_Error(
                'tmdb_search_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get movie details from TMDB
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_movie_details($request) {
        try {
            $movie_id = $request['id'];
            $append_to_response = explode(',', $request['append_to_response']);
            
            $result = $this->client->getMovieDetails($movie_id, $append_to_response);
            
            return rest_ensure_response([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            return new \WP_Error(
                'tmdb_movie_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get TV show details from TMDB
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_tv_details($request) {
        try {
            $tv_id = $request['id'];
            $append_to_response = explode(',', $request['append_to_response']);
            
            $result = $this->client->getTVDetails($tv_id, $append_to_response);
            
            return rest_ensure_response([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            return new \WP_Error(
                'tmdb_tv_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get person details from TMDB
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_person_details($request) {
        try {
            $person_id = $request['id'];
            $append_to_response = explode(',', $request['append_to_response']);
            
            $result = $this->client->getPersonDetails($person_id, $append_to_response);
            
            return rest_ensure_response([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            return new \WP_Error(
                'tmdb_person_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Sync content with TMDB
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function sync_content($request) {
        try {
            $post_id = $request['post_id'];
            $options = $request['options'];
            
            if (!get_post($post_id)) {
                return new \WP_Error(
                    'invalid_post',
                    'Invalid post ID',
                    ['status' => 404]
                );
            }
            
            $post_type = get_post_type($post_id);
            $result = false;
            
            switch ($post_type) {
                case 'movie':
                    $result = $this->sync_service->sync_movie($post_id, $options);
                    break;
                case 'tv':
                case 'drama':
                    $result = $this->sync_service->sync_tv_show($post_id, $options);
                    break;
                case 'people':
                    $result = $this->sync_service->sync_person($post_id, $options);
                    break;
                default:
                    return new \WP_Error(
                        'unsupported_post_type',
                        'Unsupported post type for TMDB sync',
                        ['status' => 400]
                    );
            }
            
            if ($result) {
                return rest_ensure_response([
                    'success' => true,
                    'message' => 'Content synced successfully',
                    'post_id' => $post_id,
                    'post_type' => $post_type,
                    'synced_at' => current_time('mysql')
                ]);
            } else {
                return new \WP_Error(
                    'sync_failed',
                    'Failed to sync content with TMDB',
                    ['status' => 500]
                );
            }
            
        } catch (Exception $e) {
            return new \WP_Error(
                'tmdb_sync_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Bulk sync content with TMDB
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function bulk_sync_content($request) {
        try {
            $post_ids = $request['post_ids'] ?? [];
            $post_type = $request['post_type'] ?? '';
            $options = $request['options'] ?? [];
            
            // If no post IDs provided, get all posts of the specified type
            if (empty($post_ids) && !empty($post_type)) {
                $posts = get_posts([
                    'post_type' => $post_type,
                    'posts_per_page' => -1,
                    'meta_key' => 'tmdb_id',
                    'meta_value' => '',
                    'meta_compare' => '!=',
                    'fields' => 'ids'
                ]);
                $post_ids = $posts;
            }
            
            if (empty($post_ids)) {
                return new \WP_Error(
                    'no_posts',
                    'No posts found to sync',
                    ['status' => 400]
                );
            }
            
            $results = $this->sync_service->bulk_sync($post_ids, $options);
            
            return rest_ensure_response([
                'success' => true,
                'message' => 'Bulk sync completed',
                'results' => $results,
                'total_posts' => count($post_ids),
                'completed_at' => current_time('mysql')
            ]);
            
        } catch (Exception $e) {
            return new \WP_Error(
                'tmdb_bulk_sync_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get popular content from TMDB
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_popular_content($request) {
        try {
            $type = $request['type'];
            $page = $request['page'];
            
            if ($type === 'movie') {
                $result = $this->client->getPopularMovies($page);
            } else {
                $result = $this->client->getPopularTV($page);
            }
            
            return rest_ensure_response([
                'success' => true,
                'data' => $result,
                'type' => $type,
                'page' => $page
            ]);
            
        } catch (Exception $e) {
            return new \WP_Error(
                'tmdb_popular_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get trending content from TMDB
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_trending_content($request) {
        try {
            $media_type = $request['media_type'];
            $time_window = $request['time_window'];
            $page = $request['page'];
            
            $result = $this->client->getTrending($media_type, $time_window, $page);
            
            return rest_ensure_response([
                'success' => true,
                'data' => $result,
                'media_type' => $media_type,
                'time_window' => $time_window,
                'page' => $page
            ]);
            
        } catch (Exception $e) {
            return new \WP_Error(
                'tmdb_trending_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get TMDB configuration
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_configuration($request) {
        try {
            $result = $this->client->getConfiguration();
            
            return rest_ensure_response([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            return new \WP_Error(
                'tmdb_config_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get genres from TMDB
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_genres($request) {
        try {
            $type = $request['type'];
            
            if ($type === 'movie') {
                $result = $this->client->getMovieGenres();
            } else {
                $result = $this->client->getTVGenres();
            }
            
            return rest_ensure_response([
                'success' => true,
                'data' => $result,
                'type' => $type
            ]);
            
        } catch (Exception $e) {
            return new \WP_Error(
                'tmdb_genres_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get TMDB API status
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_api_status($request) {
        try {
            $is_connected = $this->client->testConnection();
            $api_key = get_option('tmu_tmdb_api_key', '');
            $has_api_key = !empty($api_key);
            
            $status = [
                'api_connected' => $is_connected,
                'api_key_configured' => $has_api_key,
                'last_check' => current_time('mysql'),
                'endpoints_available' => $is_connected,
                'sync_enabled' => get_option('tmu_tmdb_auto_sync', 0),
                'image_sync_enabled' => get_option('tmu_tmdb_sync_images', 1),
                'video_sync_enabled' => get_option('tmu_tmdb_sync_videos', 1)
            ];
            
            return rest_ensure_response([
                'success' => true,
                'data' => $status
            ]);
            
        } catch (Exception $e) {
            return rest_ensure_response([
                'success' => false,
                'data' => [
                    'api_connected' => false,
                    'error' => $e->getMessage(),
                    'last_check' => current_time('mysql')
                ]
            ]);
        }
    }
    
    /**
     * Check basic permissions
     * 
     * @return bool
     */
    public function check_permissions(): bool {
        return current_user_can('read');
    }
    
    /**
     * Check edit permissions
     * 
     * @return bool
     */
    public function check_edit_permissions(): bool {
        return current_user_can('edit_posts');
    }
    
    /**
     * Check admin permissions
     * 
     * @return bool
     */
    public function check_admin_permissions(): bool {
        return current_user_can('manage_options');
    }
}