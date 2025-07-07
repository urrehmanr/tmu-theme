<?php
/**
 * TMDB Sync Actions
 * 
 * Handles TMDB synchronization actions including bulk sync,
 * individual post sync, and batch processing operations.
 * 
 * @package TMU\Admin\Actions
 * @since 1.0.0
 */

namespace TMU\Admin\Actions;

/**
 * TMDBSync class
 * 
 * Handles TMDB synchronization bulk actions
 */
class TMDBSync {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_tmu_bulk_tmdb_sync', [$this, 'handleBulkSync']);
        add_action('admin_post_tmu_bulk_tmdb_sync', [$this, 'handleBulkSyncPost']);
        add_filter('bulk_actions-edit-movie', [$this, 'addBulkActions']);
        add_filter('bulk_actions-edit-tv', [$this, 'addBulkActions']);
        add_filter('bulk_actions-edit-drama', [$this, 'addBulkActions']);
        add_filter('bulk_actions-edit-people', [$this, 'addBulkActions']);
        add_filter('handle_bulk_actions-edit-movie', [$this, 'handleBulkAction'], 10, 3);
        add_filter('handle_bulk_actions-edit-tv', [$this, 'handleBulkAction'], 10, 3);
        add_filter('handle_bulk_actions-edit-drama', [$this, 'handleBulkAction'], 10, 3);
        add_filter('handle_bulk_actions-edit-people', [$this, 'handleBulkAction'], 10, 3);
    }
    
    /**
     * Add bulk actions to post list tables
     * 
     * @param array $actions Existing bulk actions
     * @return array Modified bulk actions
     */
    public function addBulkActions(array $actions): array {
        $actions['tmdb_sync'] = __('Sync with TMDB', 'tmu-theme');
        $actions['tmdb_update_images'] = __('Update TMDB Images', 'tmu-theme');
        $actions['tmdb_update_ratings'] = __('Update TMDB Ratings', 'tmu-theme');
        return $actions;
    }
    
    /**
     * Handle bulk action processing
     * 
     * @param string $redirect_to Redirect URL
     * @param string $doaction Action being performed
     * @param array $post_ids Post IDs
     * @return string Modified redirect URL
     */
    public function handleBulkAction(string $redirect_to, string $doaction, array $post_ids): string {
        if (strpos($doaction, 'tmdb_') !== 0) {
            return $redirect_to;
        }
        
        if (!current_user_can('edit_posts')) {
            return $redirect_to;
        }
        
        $processed = 0;
        $errors = 0;
        
        switch ($doaction) {
            case 'tmdb_sync':
                $result = $this->bulkSyncPosts($post_ids);
                break;
            case 'tmdb_update_images':
                $result = $this->bulkUpdateImages($post_ids);
                break;
            case 'tmdb_update_ratings':
                $result = $this->bulkUpdateRatings($post_ids);
                break;
            default:
                return $redirect_to;
        }
        
        $processed = $result['processed'] ?? 0;
        $errors = $result['errors'] ?? 0;
        
        $redirect_to = add_query_arg([
            'tmu_bulk_action' => $doaction,
            'processed' => $processed,
            'errors' => $errors
        ], $redirect_to);
        
        return $redirect_to;
    }
    
    /**
     * Handle AJAX bulk sync request
     */
    public function handleBulkSync(): void {
        check_ajax_referer('tmu_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'tmu-theme')]);
        }
        
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'all');
        $force = (bool) ($_POST['force'] ?? false);
        $limit = intval($_POST['limit'] ?? 20);
        
        try {
            $result = $this->performBulkSync($post_type, $force, $limit);
            
            wp_send_json_success([
                'message' => sprintf(
                    __('Successfully synced %d items. %d errors encountered.', 'tmu-theme'),
                    $result['processed'],
                    $result['errors']
                ),
                'processed' => $result['processed'],
                'errors' => $result['errors'],
                'details' => $result['details']
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => __('Sync failed: ', 'tmu-theme') . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle POST bulk sync request
     */
    public function handleBulkSyncPost(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'tmu-theme'));
        }
        
        check_admin_referer('tmu_bulk_sync_nonce');
        
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'all');
        $force = (bool) ($_POST['force'] ?? false);
        
        $result = $this->performBulkSync($post_type, $force);
        
        $redirect_url = admin_url('admin.php?page=tmu-quick-actions');
        $redirect_url = add_query_arg([
            'tmu_sync_complete' => 1,
            'processed' => $result['processed'],
            'errors' => $result['errors']
        ], $redirect_url);
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Perform bulk TMDB sync
     * 
     * @param string $post_type Post type to sync
     * @param bool $force Force sync even if recently synced
     * @param int $limit Maximum number of posts to process
     * @return array Sync results
     */
    private function performBulkSync(string $post_type = 'all', bool $force = false, int $limit = 50): array {
        $post_types = $post_type === 'all' ? ['movie', 'tv', 'drama', 'people'] : [$post_type];
        
        $processed = 0;
        $errors = 0;
        $details = [];
        
        foreach ($post_types as $type) {
            $posts = $this->getPostsToSync($type, $force, $limit);
            
            foreach ($posts as $post) {
                try {
                    $result = $this->syncSinglePost($post->ID, $force);
                    
                    if ($result['success']) {
                        $processed++;
                        $details[] = [
                            'post_id' => $post->ID,
                            'title' => $post->post_title,
                            'status' => 'success',
                            'message' => $result['message'] ?? 'Synced successfully'
                        ];
                    } else {
                        $errors++;
                        $details[] = [
                            'post_id' => $post->ID,
                            'title' => $post->post_title,
                            'status' => 'error',
                            'message' => $result['message'] ?? 'Sync failed'
                        ];
                    }
                    
                    // Rate limiting - small delay between requests
                    usleep(250000); // 0.25 seconds
                    
                } catch (Exception $e) {
                    $errors++;
                    $details[] = [
                        'post_id' => $post->ID,
                        'title' => $post->post_title,
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                }
                
                // Prevent timeout on large batches
                if (($processed + $errors) >= $limit) {
                    break 2;
                }
            }
        }
        
        return [
            'processed' => $processed,
            'errors' => $errors,
            'details' => $details
        ];
    }
    
    /**
     * Get posts that need TMDB sync
     * 
     * @param string $post_type Post type
     * @param bool $force Force sync all posts
     * @param int $limit Limit number of posts
     * @return array Posts to sync
     */
    private function getPostsToSync(string $post_type, bool $force = false, int $limit = 50): array {
        $args = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'modified',
            'order' => 'ASC'
        ];
        
        if (!$force) {
            // Only sync posts that haven't been synced in the last 24 hours
            $args['meta_query'] = [
                'relation' => 'OR',
                [
                    'key' => '_tmdb_last_sync',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => '_tmdb_last_sync',
                    'value' => date('Y-m-d H:i:s', strtotime('-24 hours')),
                    'compare' => '<',
                    'type' => 'DATETIME'
                ]
            ];
        }
        
        $query = new \WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Sync single post with TMDB
     * 
     * @param int $post_id Post ID
     * @param bool $force Force sync
     * @return array Sync result
     */
    private function syncSinglePost(int $post_id, bool $force = false): array {
        $post_type = get_post_type($post_id);
        $tmdb_id = $this->getTMDBId($post_id, $post_type);
        
        if (!$tmdb_id) {
            return [
                'success' => false,
                'message' => __('No TMDB ID found', 'tmu-theme')
            ];
        }
        
        // Check if recently synced (unless forced)
        if (!$force) {
            $last_sync = get_post_meta($post_id, '_tmdb_last_sync', true);
            if ($last_sync && strtotime($last_sync) > strtotime('-1 hour')) {
                return [
                    'success' => true,
                    'message' => __('Recently synced, skipped', 'tmu-theme')
                ];
            }
        }
        
        try {
            // This would be the actual TMDB API sync - for now we'll simulate
            $sync_result = $this->performTMDBApiSync($post_id, $tmdb_id, $post_type);
            
            if ($sync_result) {
                update_post_meta($post_id, '_tmdb_last_sync', current_time('mysql'));
                update_post_meta($post_id, '_tmdb_sync_status', 'success');
                
                return [
                    'success' => true,
                    'message' => __('Synced successfully', 'tmu-theme')
                ];
            } else {
                update_post_meta($post_id, '_tmdb_sync_status', 'error');
                
                return [
                    'success' => false,
                    'message' => __('TMDB API sync failed', 'tmu-theme')
                ];
            }
            
        } catch (Exception $e) {
            update_post_meta($post_id, '_tmdb_sync_status', 'error');
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Bulk sync specific posts
     * 
     * @param array $post_ids Post IDs to sync
     * @return array Sync results
     */
    private function bulkSyncPosts(array $post_ids): array {
        $processed = 0;
        $errors = 0;
        
        foreach ($post_ids as $post_id) {
            $result = $this->syncSinglePost($post_id, true);
            
            if ($result['success']) {
                $processed++;
            } else {
                $errors++;
            }
        }
        
        return [
            'processed' => $processed,
            'errors' => $errors
        ];
    }
    
    /**
     * Bulk update images from TMDB
     * 
     * @param array $post_ids Post IDs
     * @return array Update results
     */
    private function bulkUpdateImages(array $post_ids): array {
        $processed = 0;
        $errors = 0;
        
        foreach ($post_ids as $post_id) {
            try {
                // This would fetch and update images from TMDB
                $result = $this->updateImagesFromTMDB($post_id);
                
                if ($result) {
                    $processed++;
                } else {
                    $errors++;
                }
                
            } catch (Exception $e) {
                $errors++;
            }
        }
        
        return [
            'processed' => $processed,
            'errors' => $errors
        ];
    }
    
    /**
     * Bulk update ratings from TMDB
     * 
     * @param array $post_ids Post IDs
     * @return array Update results
     */
    private function bulkUpdateRatings(array $post_ids): array {
        $processed = 0;
        $errors = 0;
        
        foreach ($post_ids as $post_id) {
            try {
                // This would fetch and update ratings from TMDB
                $result = $this->updateRatingsFromTMDB($post_id);
                
                if ($result) {
                    $processed++;
                } else {
                    $errors++;
                }
                
            } catch (Exception $e) {
                $errors++;
            }
        }
        
        return [
            'processed' => $processed,
            'errors' => $errors
        ];
    }
    
    /**
     * Get TMDB ID for a post
     * 
     * @param int $post_id Post ID
     * @param string $post_type Post type
     * @return int|null TMDB ID
     */
    private function getTMDBId(int $post_id, string $post_type): ?int {
        global $wpdb;
        
        $table_map = [
            'movie' => 'tmu_movies',
            'tv' => 'tmu_tv_series',
            'drama' => 'tmu_dramas',
            'people' => 'tmu_people'
        ];
        
        if (!isset($table_map[$post_type])) {
            return null;
        }
        
        $table_name = $wpdb->prefix . $table_map[$post_type];
        $tmdb_id = $wpdb->get_var($wpdb->prepare(
            "SELECT tmdb_id FROM {$table_name} WHERE ID = %d",
            $post_id
        ));
        
        return $tmdb_id ? (int) $tmdb_id : null;
    }
    
    /**
     * Perform TMDB API sync (placeholder for actual implementation)
     * 
     * @param int $post_id Post ID
     * @param int $tmdb_id TMDB ID
     * @param string $post_type Post type
     * @return bool Success status
     */
    private function performTMDBApiSync(int $post_id, int $tmdb_id, string $post_type): bool {
        // This would be implemented in Step 9 (TMDB API Integration)
        // For now, simulate success with a delay
        usleep(500000); // 0.5 second delay to simulate API call
        
        // Simulate 90% success rate
        return (rand(1, 10) <= 9);
    }
    
    /**
     * Update images from TMDB (placeholder)
     * 
     * @param int $post_id Post ID
     * @return bool Success status
     */
    private function updateImagesFromTMDB(int $post_id): bool {
        // Placeholder for Step 9 implementation
        return true;
    }
    
    /**
     * Update ratings from TMDB (placeholder)
     * 
     * @param int $post_id Post ID
     * @return bool Success status
     */
    private function updateRatingsFromTMDB(int $post_id): bool {
        // Placeholder for Step 9 implementation
        return true;
    }
}