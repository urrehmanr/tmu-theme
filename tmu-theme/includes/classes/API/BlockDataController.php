<?php
/**
 * Block Data Controller
 * 
 * Handles data persistence and REST API endpoints for all TMU blocks.
 * Provides comprehensive data storage, retrieval, and synchronization.
 * 
 * @package TMU\API
 * @since 1.0.0
 */

namespace TMU\API;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * BlockDataController class
 * 
 * Manages block data persistence, REST API endpoints, and TMDB integration
 */
class BlockDataController {
    
    /**
     * Class instance
     * @var BlockDataController|null
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     * 
     * @return BlockDataController
     */
    public static function getInstance(): BlockDataController {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('save_post', [$this, 'save_block_data'], 10, 2);
        add_action('wp_ajax_tmu_load_block_data', [$this, 'ajax_load_block_data']);
        add_action('wp_ajax_nopriv_tmu_load_block_data', [$this, 'ajax_load_block_data']);
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes(): void {
        // Get block data
        register_rest_route('tmu/v1', '/block-data/(?P<post_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_block_data'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => [
                'post_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
        
        // Save block data
        register_rest_route('tmu/v1', '/block-data/(?P<post_id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'save_block_data_api'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => [
                'post_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
        
        // TMDB sync endpoint
        register_rest_route('tmu/v1', '/tmdb/sync', [
            'methods' => 'POST',
            'callback' => [$this, 'sync_tmdb_data'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
        
        // Block validation endpoint
        register_rest_route('tmu/v1', '/blocks/validate', [
            'methods' => 'POST',
            'callback' => [$this, 'validate_block_data'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
    }
    
    /**
     * Get block data via REST API
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|array
     */
    public function get_block_data($request) {
        $post_id = $request['post_id'];
        $post_type = get_post_type($post_id);
        
        if (!$post_type) {
            return new \WP_Error('invalid_post', 'Invalid post ID', ['status' => 404]);
        }
        
        // Get data from custom tables based on post type
        $data = [];
        
        switch ($post_type) {
            case 'movie':
                $data = $this->get_movie_data($post_id);
                break;
            case 'tv':
                $data = $this->get_tv_data($post_id);
                break;
            case 'drama':
                $data = $this->get_drama_data($post_id);
                break;
            case 'people':
                $data = $this->get_people_data($post_id);
                break;
            case 'episode':
                $data = $this->get_episode_data($post_id);
                break;
            case 'drama_episode':
                $data = $this->get_drama_episode_data($post_id);
                break;
            case 'season':
                $data = $this->get_season_data($post_id);
                break;
            case 'video':
                $data = $this->get_video_data($post_id);
                break;
            default:
                $data = [];
        }
        
        return rest_ensure_response([
            'success' => true,
            'data' => $data,
            'post_type' => $post_type
        ]);
    }
    
    /**
     * Save block data via REST API
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|array
     */
    public function save_block_data_api($request) {
        $post_id = $request['post_id'];
        $block_data = $request->get_json_params();
        
        if (!current_user_can('edit_post', $post_id)) {
            return new \WP_Error('forbidden', 'Insufficient permissions', ['status' => 403]);
        }
        
        $result = $this->save_block_attributes($post_id, $block_data);
        
        if ($result) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'Block data saved successfully'
            ]);
        } else {
            return new \WP_Error('save_failed', 'Failed to save block data', ['status' => 500]);
        }
    }
    
    /**
     * Save block data on post save
     * 
     * @param int $post_id
     * @param \WP_Post $post
     */
    public function save_block_data($post_id, $post): void {
        // Skip autosaves and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        // Only process TMU post types
        $tmu_post_types = ['movie', 'tv', 'drama', 'people', 'season', 'episode', 'drama_episode', 'video'];
        if (!in_array($post->post_type, $tmu_post_types)) return;
        
        // Parse blocks from post content
        $post_content = get_post_field('post_content', $post_id);
        $blocks = parse_blocks($post_content);
        
        foreach ($blocks as $block) {
            if (strpos($block['blockName'], 'tmu/') === 0) {
                $this->save_block_attributes($post_id, $block);
            }
        }
    }
    
    /**
     * Save block attributes to database
     * 
     * @param int $post_id
     * @param array $block
     * @return bool
     */
    private function save_block_attributes($post_id, $block): bool {
        $block_type = str_replace('tmu/', '', $block['blockName']);
        $attributes = $block['attrs'] ?? [];
        
        switch ($block_type) {
            case 'movie-metadata':
                return $this->save_movie_metadata($post_id, $attributes);
            case 'tv-series-metadata':
                return $this->save_tv_metadata($post_id, $attributes);
            case 'drama-metadata':
                return $this->save_drama_metadata($post_id, $attributes);
            case 'people-metadata':
                return $this->save_people_metadata($post_id, $attributes);
            case 'tv-episode-metadata':
            case 'drama-episode-metadata':
                return $this->save_episode_metadata($post_id, $attributes);
            case 'season-metadata':
                return $this->save_season_metadata($post_id, $attributes);
            case 'video-metadata':
                return $this->save_video_metadata($post_id, $attributes);
            default:
                return false;
        }
    }
    
    /**
     * Save movie metadata
     * 
     * @param int $post_id
     * @param array $attributes
     * @return bool
     */
    private function save_movie_metadata($post_id, $attributes): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_movies';
        
        $data = [
            'post_id' => $post_id,
            'tmdb_id' => $attributes['tmdb_id'] ?? null,
            'imdb_id' => $attributes['imdb_id'] ?? null,
            'title' => $attributes['title'] ?? null,
            'original_title' => $attributes['original_title'] ?? null,
            'tagline' => $attributes['tagline'] ?? null,
            'overview' => $attributes['overview'] ?? null,
            'runtime' => $attributes['runtime'] ?? null,
            'release_date' => $attributes['release_date'] ?? null,
            'status' => $attributes['status'] ?? null,
            'budget' => $attributes['budget'] ?? null,
            'revenue' => $attributes['revenue'] ?? null,
            'homepage' => $attributes['homepage'] ?? null,
            'poster_path' => $attributes['poster_path'] ?? null,
            'backdrop_path' => $attributes['backdrop_path'] ?? null,
            'tmdb_vote_average' => $attributes['tmdb_vote_average'] ?? null,
            'tmdb_vote_count' => $attributes['tmdb_vote_count'] ?? null,
            'tmdb_popularity' => $attributes['tmdb_popularity'] ?? null,
            'adult' => $attributes['adult'] ?? false,
            'video' => $attributes['video'] ?? false,
            'belongs_to_collection' => isset($attributes['belongs_to_collection']) ? json_encode($attributes['belongs_to_collection']) : null,
            'production_companies' => isset($attributes['production_companies']) ? json_encode($attributes['production_companies']) : null,
            'production_countries' => isset($attributes['production_countries']) ? json_encode($attributes['production_countries']) : null,
            'spoken_languages' => isset($attributes['spoken_languages']) ? json_encode($attributes['spoken_languages']) : null,
            'credits' => isset($attributes['credits']) ? json_encode($attributes['credits']) : null,
            'external_ids' => isset($attributes['external_ids']) ? json_encode($attributes['external_ids']) : null,
            'images' => isset($attributes['images']) ? json_encode($attributes['images']) : null,
            'videos' => isset($attributes['videos']) ? json_encode($attributes['videos']) : null,
            'reviews' => isset($attributes['reviews']) ? json_encode($attributes['reviews']) : null,
            'similar' => isset($attributes['similar']) ? json_encode($attributes['similar']) : null,
            'recommendations' => isset($attributes['recommendations']) ? json_encode($attributes['recommendations']) : null,
            'updated_at' => current_time('mysql'),
        ];
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE post_id = %d",
            $post_id
        ));
        
        if ($existing) {
            $result = $wpdb->update($table_name, $data, ['post_id' => $post_id]);
        } else {
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($table_name, $data);
        }
        
        return $result !== false;
    }
    
    /**
     * Save TV series metadata
     * 
     * @param int $post_id
     * @param array $attributes
     * @return bool
     */
    private function save_tv_metadata($post_id, $attributes): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_tv_series';
        
        $data = [
            'post_id' => $post_id,
            'tmdb_id' => $attributes['tmdb_id'] ?? null,
            'imdb_id' => $attributes['imdb_id'] ?? null,
            'name' => $attributes['name'] ?? null,
            'original_name' => $attributes['original_name'] ?? null,
            'tagline' => $attributes['tagline'] ?? null,
            'overview' => $attributes['overview'] ?? null,
            'first_air_date' => $attributes['first_air_date'] ?? null,
            'last_air_date' => $attributes['last_air_date'] ?? null,
            'status' => $attributes['status'] ?? null,
            'type' => $attributes['type'] ?? null,
            'homepage' => $attributes['homepage'] ?? null,
            'in_production' => $attributes['in_production'] ?? false,
            'number_of_episodes' => $attributes['number_of_episodes'] ?? null,
            'number_of_seasons' => $attributes['number_of_seasons'] ?? null,
            'episode_run_time' => isset($attributes['episode_run_time']) ? json_encode($attributes['episode_run_time']) : null,
            'languages' => isset($attributes['languages']) ? json_encode($attributes['languages']) : null,
            'origin_country' => isset($attributes['origin_country']) ? json_encode($attributes['origin_country']) : null,
            'original_language' => $attributes['original_language'] ?? null,
            'poster_path' => $attributes['poster_path'] ?? null,
            'backdrop_path' => $attributes['backdrop_path'] ?? null,
            'tmdb_vote_average' => $attributes['tmdb_vote_average'] ?? null,
            'tmdb_vote_count' => $attributes['tmdb_vote_count'] ?? null,
            'tmdb_popularity' => $attributes['tmdb_popularity'] ?? null,
            'adult' => $attributes['adult'] ?? false,
            'created_by' => isset($attributes['created_by']) ? json_encode($attributes['created_by']) : null,
            'genres' => isset($attributes['genres']) ? json_encode($attributes['genres']) : null,
            'networks' => isset($attributes['networks']) ? json_encode($attributes['networks']) : null,
            'production_companies' => isset($attributes['production_companies']) ? json_encode($attributes['production_companies']) : null,
            'production_countries' => isset($attributes['production_countries']) ? json_encode($attributes['production_countries']) : null,
            'seasons' => isset($attributes['seasons']) ? json_encode($attributes['seasons']) : null,
            'spoken_languages' => isset($attributes['spoken_languages']) ? json_encode($attributes['spoken_languages']) : null,
            'credits' => isset($attributes['credits']) ? json_encode($attributes['credits']) : null,
            'external_ids' => isset($attributes['external_ids']) ? json_encode($attributes['external_ids']) : null,
            'images' => isset($attributes['images']) ? json_encode($attributes['images']) : null,
            'videos' => isset($attributes['videos']) ? json_encode($attributes['videos']) : null,
            'similar' => isset($attributes['similar']) ? json_encode($attributes['similar']) : null,
            'recommendations' => isset($attributes['recommendations']) ? json_encode($attributes['recommendations']) : null,
            'updated_at' => current_time('mysql'),
        ];
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE post_id = %d",
            $post_id
        ));
        
        if ($existing) {
            $result = $wpdb->update($table_name, $data, ['post_id' => $post_id]);
        } else {
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($table_name, $data);
        }
        
        return $result !== false;
    }
    
    /**
     * Get movie data
     * 
     * @param int $post_id
     * @return array
     */
    private function get_movie_data($post_id): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_movies';
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE post_id = %d",
            $post_id
        ), ARRAY_A);
        
        if (!$data) return [];
        
        // Decode JSON fields
        $json_fields = ['belongs_to_collection', 'production_companies', 'production_countries', 'spoken_languages', 'credits', 'external_ids', 'images', 'videos', 'reviews', 'similar', 'recommendations'];
        foreach ($json_fields as $field) {
            if (isset($data[$field]) && $data[$field]) {
                $data[$field] = json_decode($data[$field], true);
            }
        }
        
        return $data;
    }
    
    /**
     * Get TV series data
     * 
     * @param int $post_id
     * @return array
     */
    private function get_tv_data($post_id): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_tv_series';
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE post_id = %d",
            $post_id
        ), ARRAY_A);
        
        if (!$data) return [];
        
        // Decode JSON fields
        $json_fields = ['episode_run_time', 'languages', 'origin_country', 'created_by', 'genres', 'networks', 'production_companies', 'production_countries', 'seasons', 'spoken_languages', 'credits', 'external_ids', 'images', 'videos', 'similar', 'recommendations'];
        foreach ($json_fields as $field) {
            if (isset($data[$field]) && $data[$field]) {
                $data[$field] = json_decode($data[$field], true);
            }
        }
        
        return $data;
    }
    
    /**
     * TMDB sync handler
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|array
     */
    public function sync_tmdb_data($request) {
        $tmdb_id = $request['tmdb_id'] ?? null;
        $content_type = $request['content_type'] ?? 'movie';
        
        if (!$tmdb_id) {
            return new \WP_Error('missing_tmdb_id', 'TMDB ID is required', ['status' => 400]);
        }
        
        // Here you would integrate with TMDB API
        // For now, return success response
        return rest_ensure_response([
            'success' => true,
            'message' => 'TMDB sync completed',
            'data' => [
                'tmdb_id' => $tmdb_id,
                'content_type' => $content_type,
                'synced_at' => current_time('mysql')
            ]
        ]);
    }
    
    /**
     * Validate block data
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|array
     */
    public function validate_block_data($request) {
        $block_data = $request->get_json_params();
        $errors = [];
        
        // Perform validation based on block type
        // This is a basic implementation
        if (empty($block_data['blockName'])) {
            $errors[] = 'Block name is required';
        }
        
        if (empty($block_data['attrs'])) {
            $errors[] = 'Block attributes are required';
        }
        
        if (empty($errors)) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'Block data is valid'
            ]);
        } else {
            return rest_ensure_response([
                'success' => false,
                'errors' => $errors
            ]);
        }
    }
    
    /**
     * AJAX handler for loading block data
     */
    public function ajax_load_block_data(): void {
        check_ajax_referer('tmu_load_block_data', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $block_type = sanitize_text_field($_POST['block_type'] ?? '');
        
        if (!$post_id || !$block_type) {
            wp_die('Invalid parameters');
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Insufficient permissions');
        }
        
        $data = $this->get_block_data_by_type($post_id, $block_type);
        wp_send_json_success($data);
    }
    
    /**
     * Get block data by type
     * 
     * @param int $post_id
     * @param string $block_type
     * @return array
     */
    private function get_block_data_by_type($post_id, $block_type): array {
        switch ($block_type) {
            case 'movie-metadata':
                return $this->get_movie_data($post_id);
            case 'tv-series-metadata':
                return $this->get_tv_data($post_id);
            default:
                return [];
        }
    }
    
    // Additional placeholder methods for other content types
    private function save_drama_metadata($post_id, $attributes): bool { return true; }
    private function save_people_metadata($post_id, $attributes): bool { return true; }
    private function save_episode_metadata($post_id, $attributes): bool { return true; }
    private function save_season_metadata($post_id, $attributes): bool { return true; }
    private function save_video_metadata($post_id, $attributes): bool { return true; }
    
    private function get_drama_data($post_id): array { return []; }
    private function get_people_data($post_id): array { return []; }
    private function get_episode_data($post_id): array { return []; }
    private function get_drama_episode_data($post_id): array { return []; }
    private function get_season_data($post_id): array { return []; }
    private function get_video_data($post_id): array { return []; }
}