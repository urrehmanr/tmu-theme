<?php
/**
 * TMDB Data Updater
 * 
 * Content maintenance and TMDB data synchronization system.
 * 
 * @package TMU\Maintenance
 * @since 1.0.0
 */

namespace TMU\Maintenance;

use TMU\Logging\LogManager;

class TmdbDataUpdater {
    
    /**
     * TMDB API key
     * @var string
     */
    private $api_key;
    
    /**
     * Batch size for processing
     * @var int
     */
    private $batch_size = 50;
    
    /**
     * Logger instance
     * @var LogManager
     */
    private $logger;
    
    /**
     * Rate limiting delay (microseconds)
     * @var int
     */
    private $rate_limit_delay = 250000; // 0.25 seconds (40 requests per 10 seconds)
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('tmu_tmdb_api_key');
        $this->logger = new LogManager();
        
        add_action('tmu_update_tmdb_data', [$this, 'update_content_data']);
        add_action('tmu_tmdb_bulk_sync_job', [$this, 'update_all_content']);
        add_action('wp_ajax_tmu_update_tmdb_content', [$this, 'update_content_ajax']);
    }
    
    /**
     * Update all content from TMDB
     */
    public function update_all_content(): void {
        $this->logger->info('Starting TMDB bulk content update');
        
        try {
            $start_time = microtime(true);
            
            // Update each content type
            $results = [
                'movies' => $this->update_movies(),
                'tv_series' => $this->update_tv_series(),
                'dramas' => $this->update_dramas(),
                'people' => $this->update_people()
            ];
            
            $execution_time = microtime(true) - $start_time;
            
            $this->logger->info('TMDB bulk update completed', [
                'results' => $results,
                'execution_time' => $execution_time
            ]);
            
            // Update content statistics
            $this->update_content_statistics();
            
        } catch (\Exception $e) {
            $this->logger->error('TMDB bulk update failed', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Update movies from TMDB
     */
    private function update_movies(): array {
        global $wpdb;
        
        $this->logger->info('Starting movie updates');
        
        // Get movies that need updating (older than 30 days or never updated)
        $movies = $wpdb->get_results($wpdb->prepare("
            SELECT post_id, tmdb_id, title 
            FROM {$wpdb->prefix}tmu_movies 
            WHERE tmdb_id IS NOT NULL 
            AND tmdb_id > 0
            AND (last_updated IS NULL OR last_updated < DATE_SUB(NOW(), INTERVAL 30 DAY))
            ORDER BY RAND()
            LIMIT %d
        ", $this->batch_size));
        
        $updated = 0;
        $failed = 0;
        
        foreach ($movies as $movie) {
            try {
                if ($this->update_movie_data($movie->post_id, $movie->tmdb_id)) {
                    $updated++;
                    $this->logger->debug('Movie updated', [
                        'post_id' => $movie->post_id,
                        'tmdb_id' => $movie->tmdb_id,
                        'title' => $movie->title
                    ]);
                } else {
                    $failed++;
                }
                
                // Rate limiting - TMDB allows 40 requests per 10 seconds
                usleep($this->rate_limit_delay);
                
            } catch (\Exception $e) {
                $failed++;
                $this->logger->warning('Movie update failed', [
                    'post_id' => $movie->post_id,
                    'tmdb_id' => $movie->tmdb_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return ['updated' => $updated, 'failed' => $failed, 'total' => count($movies)];
    }
    
    /**
     * Update TV series from TMDB
     */
    private function update_tv_series(): array {
        global $wpdb;
        
        $this->logger->info('Starting TV series updates');
        
        // Get TV series that need updating
        $tv_series = $wpdb->get_results($wpdb->prepare("
            SELECT post_id, tmdb_id, title 
            FROM {$wpdb->prefix}tmu_tv_series 
            WHERE tmdb_id IS NOT NULL 
            AND tmdb_id > 0
            AND (last_updated IS NULL OR last_updated < DATE_SUB(NOW(), INTERVAL 30 DAY))
            ORDER BY RAND()
            LIMIT %d
        ", $this->batch_size));
        
        $updated = 0;
        $failed = 0;
        
        foreach ($tv_series as $series) {
            try {
                if ($this->update_tv_series_data($series->post_id, $series->tmdb_id)) {
                    $updated++;
                } else {
                    $failed++;
                }
                
                usleep($this->rate_limit_delay);
                
            } catch (\Exception $e) {
                $failed++;
                $this->logger->warning('TV series update failed', [
                    'post_id' => $series->post_id,
                    'tmdb_id' => $series->tmdb_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return ['updated' => $updated, 'failed' => $failed, 'total' => count($tv_series)];
    }
    
    /**
     * Update dramas from TMDB
     */
    private function update_dramas(): array {
        global $wpdb;
        
        $this->logger->info('Starting drama updates');
        
        // Get dramas that need updating
        $dramas = $wpdb->get_results($wpdb->prepare("
            SELECT post_id, tmdb_id, title 
            FROM {$wpdb->prefix}tmu_dramas 
            WHERE tmdb_id IS NOT NULL 
            AND tmdb_id > 0
            AND (last_updated IS NULL OR last_updated < DATE_SUB(NOW(), INTERVAL 30 DAY))
            ORDER BY RAND()
            LIMIT %d
        ", $this->batch_size));
        
        $updated = 0;
        $failed = 0;
        
        foreach ($dramas as $drama) {
            try {
                if ($this->update_drama_data($drama->post_id, $drama->tmdb_id)) {
                    $updated++;
                } else {
                    $failed++;
                }
                
                usleep($this->rate_limit_delay);
                
            } catch (\Exception $e) {
                $failed++;
                $this->logger->warning('Drama update failed', [
                    'post_id' => $drama->post_id,
                    'tmdb_id' => $drama->tmdb_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return ['updated' => $updated, 'failed' => $failed, 'total' => count($dramas)];
    }
    
    /**
     * Update people from TMDB
     */
    private function update_people(): array {
        global $wpdb;
        
        $this->logger->info('Starting people updates');
        
        // Get people that need updating
        $people = $wpdb->get_results($wpdb->prepare("
            SELECT post_id, tmdb_id, name 
            FROM {$wpdb->prefix}tmu_people 
            WHERE tmdb_id IS NOT NULL 
            AND tmdb_id > 0
            AND (last_updated IS NULL OR last_updated < DATE_SUB(NOW(), INTERVAL 60 DAY))
            ORDER BY RAND()
            LIMIT %d
        ", $this->batch_size));
        
        $updated = 0;
        $failed = 0;
        
        foreach ($people as $person) {
            try {
                if ($this->update_person_data($person->post_id, $person->tmdb_id)) {
                    $updated++;
                } else {
                    $failed++;
                }
                
                usleep($this->rate_limit_delay);
                
            } catch (\Exception $e) {
                $failed++;
                $this->logger->warning('Person update failed', [
                    'post_id' => $person->post_id,
                    'tmdb_id' => $person->tmdb_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return ['updated' => $updated, 'failed' => $failed, 'total' => count($people)];
    }
    
    /**
     * Update individual movie data
     */
    private function update_movie_data($post_id, $tmdb_id): bool {
        $api_url = "https://api.themoviedb.org/3/movie/{$tmdb_id}";
        $api_url .= "?api_key={$this->api_key}";
        $api_url .= "&append_to_response=credits,images,videos,reviews,similar,recommendations,keywords,external_ids";
        
        $response = wp_remote_get($api_url, [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'TMU Theme TMDB Updater'
            ]
        ]);
        
        if (is_wp_error($response)) {
            $this->logger->error('TMDB API request failed', [
                'tmdb_id' => $tmdb_id,
                'error' => $response->get_error_message()
            ]);
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            $this->logger->warning('TMDB API returned error', [
                'tmdb_id' => $tmdb_id,
                'status_code' => $status_code
            ]);
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$data) {
            $this->logger->error('Invalid TMDB API response', ['tmdb_id' => $tmdb_id]);
            return false;
        }
        
        return $this->save_movie_data($post_id, $data);
    }
    
    /**
     * Save movie data to database
     */
    private function save_movie_data($post_id, $data): bool {
        global $wpdb;
        
        try {
            $update_data = [
                'title' => $data['title'] ?? null,
                'original_title' => $data['original_title'] ?? null,
                'tagline' => $data['tagline'] ?? null,
                'overview' => $data['overview'] ?? null,
                'runtime' => $data['runtime'] ?? null,
                'release_date' => $data['release_date'] ?? null,
                'status' => $data['status'] ?? null,
                'budget' => $data['budget'] ?? null,
                'revenue' => $data['revenue'] ?? null,
                'homepage' => $data['homepage'] ?? null,
                'poster_path' => $data['poster_path'] ?? null,
                'backdrop_path' => $data['backdrop_path'] ?? null,
                'tmdb_vote_average' => $data['vote_average'] ?? null,
                'tmdb_vote_count' => $data['vote_count'] ?? null,
                'tmdb_popularity' => $data['popularity'] ?? null,
                'adult' => $data['adult'] ?? false,
                'video' => $data['video'] ?? false,
                'belongs_to_collection' => isset($data['belongs_to_collection']) ? json_encode($data['belongs_to_collection']) : null,
                'production_companies' => isset($data['production_companies']) ? json_encode($data['production_companies']) : null,
                'production_countries' => isset($data['production_countries']) ? json_encode($data['production_countries']) : null,
                'spoken_languages' => isset($data['spoken_languages']) ? json_encode($data['spoken_languages']) : null,
                'genres' => isset($data['genres']) ? json_encode($data['genres']) : null,
                'keywords' => isset($data['keywords']) ? json_encode($data['keywords']) : null,
                'credits' => isset($data['credits']) ? json_encode($data['credits']) : null,
                'images' => isset($data['images']) ? json_encode($data['images']) : null,
                'videos' => isset($data['videos']) ? json_encode($data['videos']) : null,
                'reviews' => isset($data['reviews']) ? json_encode($data['reviews']) : null,
                'similar' => isset($data['similar']) ? json_encode($data['similar']) : null,
                'recommendations' => isset($data['recommendations']) ? json_encode($data['recommendations']) : null,
                'external_ids' => isset($data['external_ids']) ? json_encode($data['external_ids']) : null,
                'last_updated' => current_time('mysql')
            ];
            
            $result = $wpdb->update(
                $wpdb->prefix . 'tmu_movies',
                $update_data,
                ['post_id' => $post_id]
            );
            
            if ($result !== false) {
                // Update post title if needed
                if (!empty($data['title'])) {
                    wp_update_post([
                        'ID' => $post_id,
                        'post_title' => $data['title']
                    ]);
                }
                
                // Update post content if needed
                if (!empty($data['overview'])) {
                    wp_update_post([
                        'ID' => $post_id,
                        'post_content' => $data['overview']
                    ]);
                }
                
                // Update genres and other taxonomies
                $this->update_movie_taxonomies($post_id, $data);
                
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to save movie data', [
                'post_id' => $post_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Update TV series data
     */
    private function update_tv_series_data($post_id, $tmdb_id): bool {
        $api_url = "https://api.themoviedb.org/3/tv/{$tmdb_id}";
        $api_url .= "?api_key={$this->api_key}";
        $api_url .= "&append_to_response=credits,images,videos,reviews,similar,recommendations,keywords,external_ids";
        
        $response = wp_remote_get($api_url, [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'TMU Theme TMDB Updater'
            ]
        ]);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$data) {
            return false;
        }
        
        return $this->save_tv_series_data($post_id, $data);
    }
    
    /**
     * Save TV series data
     */
    private function save_tv_series_data($post_id, $data): bool {
        global $wpdb;
        
        try {
            $update_data = [
                'title' => $data['name'] ?? null,
                'original_title' => $data['original_name'] ?? null,
                'overview' => $data['overview'] ?? null,
                'first_air_date' => $data['first_air_date'] ?? null,
                'last_air_date' => $data['last_air_date'] ?? null,
                'status' => $data['status'] ?? null,
                'type' => $data['type'] ?? null,
                'number_of_episodes' => $data['number_of_episodes'] ?? null,
                'number_of_seasons' => $data['number_of_seasons'] ?? null,
                'episode_run_time' => isset($data['episode_run_time']) ? json_encode($data['episode_run_time']) : null,
                'homepage' => $data['homepage'] ?? null,
                'poster_path' => $data['poster_path'] ?? null,
                'backdrop_path' => $data['backdrop_path'] ?? null,
                'tmdb_vote_average' => $data['vote_average'] ?? null,
                'tmdb_vote_count' => $data['vote_count'] ?? null,
                'tmdb_popularity' => $data['popularity'] ?? null,
                'in_production' => $data['in_production'] ?? false,
                'languages' => isset($data['languages']) ? json_encode($data['languages']) : null,
                'origin_country' => isset($data['origin_country']) ? json_encode($data['origin_country']) : null,
                'networks' => isset($data['networks']) ? json_encode($data['networks']) : null,
                'production_companies' => isset($data['production_companies']) ? json_encode($data['production_companies']) : null,
                'production_countries' => isset($data['production_countries']) ? json_encode($data['production_countries']) : null,
                'seasons' => isset($data['seasons']) ? json_encode($data['seasons']) : null,
                'genres' => isset($data['genres']) ? json_encode($data['genres']) : null,
                'keywords' => isset($data['keywords']) ? json_encode($data['keywords']) : null,
                'credits' => isset($data['credits']) ? json_encode($data['credits']) : null,
                'images' => isset($data['images']) ? json_encode($data['images']) : null,
                'videos' => isset($data['videos']) ? json_encode($data['videos']) : null,
                'similar' => isset($data['similar']) ? json_encode($data['similar']) : null,
                'recommendations' => isset($data['recommendations']) ? json_encode($data['recommendations']) : null,
                'external_ids' => isset($data['external_ids']) ? json_encode($data['external_ids']) : null,
                'last_updated' => current_time('mysql')
            ];
            
            $result = $wpdb->update(
                $wpdb->prefix . 'tmu_tv_series',
                $update_data,
                ['post_id' => $post_id]
            );
            
            return $result !== false;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to save TV series data', [
                'post_id' => $post_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Update drama data
     */
    private function update_drama_data($post_id, $tmdb_id): bool {
        // Dramas are treated as TV series in TMDB
        return $this->update_tv_series_data($post_id, $tmdb_id);
    }
    
    /**
     * Update person data
     */
    private function update_person_data($post_id, $tmdb_id): bool {
        $api_url = "https://api.themoviedb.org/3/person/{$tmdb_id}";
        $api_url .= "?api_key={$this->api_key}";
        $api_url .= "&append_to_response=movie_credits,tv_credits,combined_credits,external_ids,images";
        
        $response = wp_remote_get($api_url, [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'TMU Theme TMDB Updater'
            ]
        ]);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$data) {
            return false;
        }
        
        return $this->save_person_data($post_id, $data);
    }
    
    /**
     * Save person data
     */
    private function save_person_data($post_id, $data): bool {
        global $wpdb;
        
        try {
            $update_data = [
                'name' => $data['name'] ?? null,
                'biography' => $data['biography'] ?? null,
                'birthday' => $data['birthday'] ?? null,
                'deathday' => $data['deathday'] ?? null,
                'place_of_birth' => $data['place_of_birth'] ?? null,
                'profile_path' => $data['profile_path'] ?? null,
                'adult' => $data['adult'] ?? false,
                'gender' => $data['gender'] ?? null,
                'known_for_department' => $data['known_for_department'] ?? null,
                'popularity' => $data['popularity'] ?? null,
                'also_known_as' => isset($data['also_known_as']) ? json_encode($data['also_known_as']) : null,
                'movie_credits' => isset($data['movie_credits']) ? json_encode($data['movie_credits']) : null,
                'tv_credits' => isset($data['tv_credits']) ? json_encode($data['tv_credits']) : null,
                'combined_credits' => isset($data['combined_credits']) ? json_encode($data['combined_credits']) : null,
                'external_ids' => isset($data['external_ids']) ? json_encode($data['external_ids']) : null,
                'images' => isset($data['images']) ? json_encode($data['images']) : null,
                'last_updated' => current_time('mysql')
            ];
            
            $result = $wpdb->update(
                $wpdb->prefix . 'tmu_people',
                $update_data,
                ['post_id' => $post_id]
            );
            
            return $result !== false;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to save person data', [
                'post_id' => $post_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Update movie taxonomies
     */
    private function update_movie_taxonomies($post_id, $data): void {
        // Update genres
        if (!empty($data['genres'])) {
            $genre_ids = [];
            foreach ($data['genres'] as $genre) {
                $term = get_term_by('name', $genre['name'], 'genre');
                if (!$term) {
                    $term = wp_insert_term($genre['name'], 'genre');
                    if (!is_wp_error($term)) {
                        $genre_ids[] = $term['term_id'];
                    }
                } else {
                    $genre_ids[] = $term->term_id;
                }
            }
            wp_set_object_terms($post_id, $genre_ids, 'genre');
        }
        
        // Update countries
        if (!empty($data['production_countries'])) {
            $country_ids = [];
            foreach ($data['production_countries'] as $country) {
                $term = get_term_by('name', $country['name'], 'country');
                if (!$term) {
                    $term = wp_insert_term($country['name'], 'country');
                    if (!is_wp_error($term)) {
                        $country_ids[] = $term['term_id'];
                    }
                } else {
                    $country_ids[] = $term->term_id;
                }
            }
            wp_set_object_terms($post_id, $country_ids, 'country');
        }
    }
    
    /**
     * Update content statistics
     */
    private function update_content_statistics(): void {
        global $wpdb;
        
        $stats = [
            'total_movies' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_movies"),
            'total_tv_series' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_tv_series"),
            'total_dramas' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_dramas"),
            'total_people' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_people"),
            'movies_with_tmdb' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_movies WHERE tmdb_id IS NOT NULL"),
            'tv_series_with_tmdb' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_tv_series WHERE tmdb_id IS NOT NULL"),
            'last_updated' => current_time('mysql')
        ];
        
        update_option('tmu_content_statistics', $stats);
        
        $this->logger->debug('Content statistics updated', $stats);
    }
    
    /**
     * Update content via AJAX
     */
    public function update_content_ajax(): void {
        check_ajax_referer('tmu_tmdb_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $content_id = intval($_POST['content_id'] ?? 0);
        $content_type = sanitize_text_field($_POST['content_type'] ?? '');
        
        if (!$content_id || !$content_type) {
            wp_send_json_error('Invalid parameters');
        }
        
        try {
            $result = $this->update_single_content($content_id, $content_type);
            
            if ($result) {
                wp_send_json_success([
                    'message' => 'Content updated successfully',
                    'content_id' => $content_id,
                    'content_type' => $content_type
                ]);
            } else {
                wp_send_json_error('Failed to update content');
            }
            
        } catch (\Exception $e) {
            wp_send_json_error('Update failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Update single content item
     */
    public function update_single_content($post_id, $content_type): bool {
        global $wpdb;
        
        switch ($content_type) {
            case 'movie':
                $tmdb_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT tmdb_id FROM {$wpdb->prefix}tmu_movies WHERE post_id = %d",
                    $post_id
                ));
                return $tmdb_id ? $this->update_movie_data($post_id, $tmdb_id) : false;
                
            case 'tv_series':
                $tmdb_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT tmdb_id FROM {$wpdb->prefix}tmu_tv_series WHERE post_id = %d",
                    $post_id
                ));
                return $tmdb_id ? $this->update_tv_series_data($post_id, $tmdb_id) : false;
                
            case 'drama':
                $tmdb_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT tmdb_id FROM {$wpdb->prefix}tmu_dramas WHERE post_id = %d",
                    $post_id
                ));
                return $tmdb_id ? $this->update_drama_data($post_id, $tmdb_id) : false;
                
            case 'people':
                $tmdb_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT tmdb_id FROM {$wpdb->prefix}tmu_people WHERE post_id = %d",
                    $post_id
                ));
                return $tmdb_id ? $this->update_person_data($post_id, $tmdb_id) : false;
                
            default:
                return false;
        }
    }
    
    /**
     * Get TMDB update statistics
     */
    public function get_update_statistics(): array {
        global $wpdb;
        
        $stats = [
            'last_bulk_update' => get_option('tmu_last_tmdb_bulk_update'),
            'content_with_tmdb_ids' => [
                'movies' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_movies WHERE tmdb_id IS NOT NULL"),
                'tv_series' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_tv_series WHERE tmdb_id IS NOT NULL"),
                'dramas' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_dramas WHERE tmdb_id IS NOT NULL"),
                'people' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_people WHERE tmdb_id IS NOT NULL")
            ],
            'recently_updated' => [
                'movies' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_movies WHERE last_updated >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
                'tv_series' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_tv_series WHERE last_updated >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
                'dramas' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_dramas WHERE last_updated >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
                'people' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_people WHERE last_updated >= DATE_SUB(NOW(), INTERVAL 7 DAY)")
            ]
        ];
        
        return $stats;
    }
}