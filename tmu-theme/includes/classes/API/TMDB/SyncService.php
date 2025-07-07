<?php
/**
 * TMDB Sync Service
 * 
 * Orchestrates content synchronization with TMDB API for all content types
 * 
 * @package TMU\API\TMDB
 * @since 1.0.0
 */

namespace TMU\API\TMDB;

use TMU\Fields\Storage\CustomTableStorage;

/**
 * SyncService class
 * 
 * Manages TMDB data synchronization for movies, TV shows, dramas, and people
 */
class SyncService {
    
    /**
     * TMDB API client
     * @var Client
     */
    private $client;
    
    /**
     * Data mapper
     * @var DataMapper
     */
    private $data_mapper;
    
    /**
     * Image sync service
     * @var ImageSyncService
     */
    private $image_service;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->client = new Client();
        $this->data_mapper = new DataMapper();
        $this->image_service = new ImageSyncService();
    }
    
    /**
     * Sync movie data from TMDB
     * 
     * @param int $post_id WordPress post ID
     * @param array $options Sync options
     * @return bool Success status
     */
    public function sync_movie(int $post_id, array $options = []): bool {
        $storage = new CustomTableStorage();
        $tmdb_id = $storage->get($post_id, 'tmdb_id');
        
        if (!$tmdb_id) {
            error_log("No TMDB ID found for movie post {$post_id}");
            return false;
        }
        
        try {
            $data = $this->client->getMovieDetails($tmdb_id);
            
            if (empty($data)) {
                error_log("No data returned from TMDB for movie {$tmdb_id}");
                return false;
            }
            
            $success = $this->data_mapper->mapMovieData($post_id, $data, $options);
            
            if ($success) {
                // Update last sync timestamp
                update_post_meta($post_id, '_tmdb_last_sync', current_time('mysql'));
                
                // Sync images if requested
                if (!empty($options['sync_images']) && !empty($data['images'])) {
                    $this->sync_movie_images($post_id, $data['images']);
                }
                
                // Sync videos if requested
                if (!empty($options['sync_videos']) && !empty($data['videos']['results'])) {
                    $this->sync_movie_videos($post_id, $data['videos']['results']);
                }
                
                do_action('tmu_movie_synced', $post_id, $data);
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log("TMDB Sync Error for movie {$post_id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sync TV show data from TMDB
     * 
     * @param int $post_id WordPress post ID
     * @param array $options Sync options
     * @return bool Success status
     */
    public function sync_tv_show(int $post_id, array $options = []): bool {
        $storage = new CustomTableStorage();
        $tmdb_id = $storage->get($post_id, 'tmdb_id');
        
        if (!$tmdb_id) {
            error_log("No TMDB ID found for TV show post {$post_id}");
            return false;
        }
        
        try {
            $data = $this->client->getTvDetails($tmdb_id);
            
            if (empty($data)) {
                error_log("No data returned from TMDB for TV show {$tmdb_id}");
                return false;
            }
            
            $success = $this->data_mapper->mapTvData($post_id, $data, $options);
            
            if ($success) {
                // Update last sync timestamp
                update_post_meta($post_id, '_tmdb_last_sync', current_time('mysql'));
                
                // Sync images if requested
                if (!empty($options['sync_images']) && !empty($data['images'])) {
                    $this->sync_tv_images($post_id, $data['images']);
                }
                
                // Sync seasons if requested
                if (!empty($options['sync_seasons']) && !empty($data['seasons'])) {
                    $this->sync_tv_seasons($post_id, $data['seasons']);
                }
                
                do_action('tmu_tv_synced', $post_id, $data);
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log("TMDB Sync Error for TV show {$post_id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sync person data from TMDB
     * 
     * @param int $post_id WordPress post ID
     * @param array $options Sync options
     * @return bool Success status
     */
    public function sync_person(int $post_id, array $options = []): bool {
        $storage = new CustomTableStorage();
        $tmdb_id = $storage->get($post_id, 'tmdb_id');
        
        if (!$tmdb_id) {
            error_log("No TMDB ID found for person post {$post_id}");
            return false;
        }
        
        try {
            $data = $this->client->getPersonDetails($tmdb_id);
            
            if (empty($data)) {
                error_log("No data returned from TMDB for person {$tmdb_id}");
                return false;
            }
            
            $success = $this->data_mapper->mapPersonData($post_id, $data, $options);
            
            if ($success) {
                // Update last sync timestamp
                update_post_meta($post_id, '_tmdb_last_sync', current_time('mysql'));
                
                // Sync images if requested
                if (!empty($options['sync_images']) && !empty($data['images'])) {
                    $this->sync_person_images($post_id, $data['images']);
                }
                
                do_action('tmu_person_synced', $post_id, $data);
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log("TMDB Sync Error for person {$post_id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Bulk sync multiple posts
     * 
     * @param array $post_ids Array of post IDs
     * @param array $options Sync options
     * @return array Sync results
     */
    public function bulk_sync(array $post_ids, array $options = []): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($post_ids as $post_id) {
            $post_type = get_post_type($post_id);
            $success = false;
            
            try {
                switch ($post_type) {
                    case 'movie':
                        $success = $this->sync_movie($post_id, $options);
                        break;
                    case 'tv':
                        $success = $this->sync_tv_show($post_id, $options);
                        break;
                    case 'drama':
                        // Drama sync uses TV logic for now
                        $success = $this->sync_tv_show($post_id, $options);
                        break;
                    case 'people':
                        $success = $this->sync_person($post_id, $options);
                        break;
                }
                
                if ($success) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to sync post {$post_id}";
                }
                
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Error syncing post {$post_id}: " . $e->getMessage();
            }
            
            // Rate limiting - pause between requests
            if (!empty($options['rate_limit'])) {
                sleep(1);
            }
        }
        
        return $results;
    }
    
    /**
     * Sync movie images
     * 
     * @param int $post_id Post ID
     * @param array $images TMDB images data
     */
    private function sync_movie_images(int $post_id, array $images): void {
        // Sync poster
        if (!empty($images['posters'][0]['file_path'])) {
            $poster_id = $this->image_service->downloadAndAttachImage(
                $post_id, 
                $images['posters'][0]['file_path'], 
                'poster'
            );
            
            if ($poster_id) {
                set_post_thumbnail($post_id, $poster_id);
            }
        }
        
        // Sync backdrop
        if (!empty($images['backdrops'][0]['file_path'])) {
            $this->image_service->downloadAndAttachImage(
                $post_id, 
                $images['backdrops'][0]['file_path'], 
                'backdrop'
            );
        }
    }
    
    /**
     * Sync TV show images
     * 
     * @param int $post_id Post ID
     * @param array $images TMDB images data
     */
    private function sync_tv_images(int $post_id, array $images): void {
        // Similar to movie images but for TV shows
        $this->sync_movie_images($post_id, $images);
    }
    
    /**
     * Sync person images
     * 
     * @param int $post_id Post ID
     * @param array $images TMDB images data
     */
    private function sync_person_images(int $post_id, array $images): void {
        if (!empty($images['profiles'][0]['file_path'])) {
            $profile_id = $this->image_service->downloadAndAttachImage(
                $post_id, 
                $images['profiles'][0]['file_path'], 
                'profile'
            );
            
            if ($profile_id) {
                set_post_thumbnail($post_id, $profile_id);
            }
        }
    }
    
    /**
     * Sync movie videos
     * 
     * @param int $post_id Post ID
     * @param array $videos TMDB videos data
     */
    private function sync_movie_videos(int $post_id, array $videos): void {
        global $wpdb;
        
        foreach ($videos as $video) {
            // Create video post
            $video_post_id = wp_insert_post([
                'post_type' => 'video',
                'post_title' => $video['name'],
                'post_status' => 'publish',
                'post_parent' => $post_id
            ]);
            
            if ($video_post_id) {
                // Store video data in custom table
                $wpdb->insert($wpdb->prefix . 'tmu_videos', [
                    'post_id' => $video_post_id,
                    'source' => $video['key'],
                    'content_type' => strtolower($video['type']),
                    'related_post_id' => $post_id,
                    'created_at' => current_time('mysql')
                ]);
            }
        }
    }
    
    /**
     * Sync TV seasons
     * 
     * @param int $post_id Post ID
     * @param array $seasons TMDB seasons data
     */
    private function sync_tv_seasons(int $post_id, array $seasons): void {
        foreach ($seasons as $season) {
            // Create season post
            $season_post_id = wp_insert_post([
                'post_type' => 'season',
                'post_title' => $season['name'],
                'post_status' => 'publish',
                'post_parent' => $post_id
            ]);
            
            if ($season_post_id) {
                // Store season data
                update_post_meta($season_post_id, 'season_number', $season['season_number']);
                update_post_meta($season_post_id, 'episode_count', $season['episode_count']);
                update_post_meta($season_post_id, 'air_date', $season['air_date']);
            }
        }
    }
    
    /**
     * Get sync statistics
     * 
     * @return array Sync statistics
     */
    public function getSyncStatistics(): array {
        global $wpdb;
        
        $stats = [
            'total_synced' => 0,
            'movies_synced' => 0,
            'tv_synced' => 0,
            'people_synced' => 0,
            'last_sync' => null
        ];
        
        // Count posts with sync timestamps
        $post_types = ['movie', 'tv', 'drama', 'people'];
        
        foreach ($post_types as $post_type) {
            $count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT p.ID)
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = %s
                AND pm.meta_key = '_tmdb_last_sync'
                AND pm.meta_value != ''
            ", $post_type));
            
            $key = $post_type === 'tv' ? 'tv_synced' : $post_type . 's_synced';
            $stats[$key] = (int) $count;
            $stats['total_synced'] += (int) $count;
        }
        
        // Get last sync time
        $last_sync = $wpdb->get_var("
            SELECT MAX(pm.meta_value)
            FROM {$wpdb->postmeta} pm
            WHERE pm.meta_key = '_tmdb_last_sync'
        ");
        
        if ($last_sync) {
            $stats['last_sync'] = $last_sync;
        }
        
        return $stats;
    }
}