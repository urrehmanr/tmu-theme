<?php
/**
 * TMDB Meta Box
 * 
 * TMDB integration meta box for content synchronization and management.
 * Provides interface for TMDB ID assignment, data syncing, and status tracking.
 * 
 * @package TMU\Admin\MetaBoxes
 * @since 1.0.0
 */

namespace TMU\Admin\MetaBoxes;

/**
 * TMDBBox class
 * 
 * Handles TMDB integration meta box for post editor
 */
class TMDBBox {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('save_post', [$this, 'saveMetaBox'], 10, 2);
        add_action('wp_ajax_tmu_tmdb_sync', [$this, 'handleTMDBSync']);
        add_action('wp_ajax_tmu_tmdb_search', [$this, 'handleTMDBSearch']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }
    
    /**
     * Add TMDB meta box to supported post types
     */
    public function addMetaBox(): void {
        $post_types = ['movie', 'tv', 'drama', 'people'];
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'tmu-tmdb-integration',
                __('TMDB Integration', 'tmu'),
                [$this, 'renderMetaBox'],
                $post_type,
                'side',
                'high'
            );
        }
    }
    
    /**
     * Enqueue assets for meta box
     * 
     * @param string $hook_suffix Current admin page
     */
    public function enqueueAssets($hook_suffix): void {
        if (!in_array($hook_suffix, ['post.php', 'post-new.php'])) {
            return;
        }
        
        global $post_type;
        if (!in_array($post_type, ['movie', 'tv', 'drama', 'people'])) {
            return;
        }
        
        wp_enqueue_script(
            'tmu-tmdb-metabox',
            get_template_directory_uri() . '/assets/build/js/tmdb-metabox.js',
            ['jquery', 'wp-util'],
            get_theme_file_version('assets/build/js/tmdb-metabox.js'),
            true
        );
        
        wp_localize_script('tmu-tmdb-metabox', 'tmuTMDB', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_tmdb_nonce'),
            'postId' => get_the_ID(),
            'postType' => $post_type,
            'strings' => [
                'searching' => __('Searching TMDB...', 'tmu'),
                'syncing' => __('Syncing with TMDB...', 'tmu'),
                'sync_complete' => __('Sync completed successfully!', 'tmu'),
                'sync_error' => __('Sync failed. Please try again.', 'tmu'),
                'no_results' => __('No results found.', 'tmu'),
                'confirm_overwrite' => __('This will overwrite existing data. Continue?', 'tmu'),
            ],
        ]);
    }
    
    /**
     * Render TMDB meta box
     * 
     * @param \WP_Post $post Current post object
     */
    public function renderMetaBox(\WP_Post $post): void {
        $tmdb_id = tmu_get_meta($post->ID, 'tmdb_id');
        $last_sync = get_post_meta($post->ID, '_tmdb_last_sync', true);
        $sync_status = get_post_meta($post->ID, '_tmdb_sync_status', true);
        
        wp_nonce_field('tmdb_metabox_nonce', 'tmdb_metabox_nonce');
        ?>
        <div class="tmu-tmdb-metabox">
            <?php if ($tmdb_id): ?>
                <div class="tmdb-id-section">
                    <label><strong><?php _e('TMDB ID:', 'tmu'); ?></strong></label>
                    <div class="tmdb-id-display">
                        <input type="number" 
                               id="tmdb_id" 
                               name="tmdb_id" 
                               value="<?php echo esc_attr($tmdb_id); ?>" 
                               style="width: 100%; margin-bottom: 10px;" />
                        <a href="<?php echo esc_url($this->getTMDBUrl($post->post_type, $tmdb_id)); ?>" 
                           target="_blank" 
                           class="button button-small">
                            <span class="dashicons dashicons-external"></span>
                            <?php _e('View on TMDB', 'tmu'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="tmdb-sync-section" style="margin-top: 15px;">
                    <?php if ($last_sync): ?>
                        <div class="sync-status">
                            <label><strong><?php _e('Last Sync:', 'tmu'); ?></strong></label>
                            <div>
                                <?php echo esc_html(human_time_diff(strtotime($last_sync))); ?> <?php _e('ago', 'tmu'); ?>
                                <?php if ($sync_status === 'success'): ?>
                                    <span class="dashicons dashicons-yes" style="color: green;"></span>
                                <?php elseif ($sync_status === 'error'): ?>
                                    <span class="dashicons dashicons-warning" style="color: red;"></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <button type="button" 
                            id="tmdb-sync-btn" 
                            class="button button-primary" 
                            style="width: 100%; margin-top: 10px;">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Sync with TMDB', 'tmu'); ?>
                    </button>
                </div>
            <?php else: ?>
                <div class="tmdb-search-section">
                    <label for="tmdb_search"><strong><?php _e('Search TMDB:', 'tmu'); ?></strong></label>
                    <input type="text" 
                           id="tmdb_search" 
                           placeholder="<?php esc_attr_e('Enter title to search...', 'tmu'); ?>" 
                           style="width: 100%; margin-bottom: 10px;" />
                    
                    <button type="button" 
                            id="tmdb-search-btn" 
                            class="button" 
                            style="width: 100%;">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Search TMDB', 'tmu'); ?>
                    </button>
                    
                    <div id="tmdb-search-results" style="margin-top: 15px; display: none;">
                        <!-- Search results will be populated here -->
                    </div>
                </div>
                
                <div class="tmdb-manual-section" style="margin-top: 15px; border-top: 1px solid #ddd; padding-top: 15px;">
                    <label for="tmdb_id_manual"><strong><?php _e('Or enter TMDB ID manually:', 'tmu'); ?></strong></label>
                    <input type="number" 
                           id="tmdb_id_manual" 
                           name="tmdb_id" 
                           placeholder="<?php esc_attr_e('TMDB ID', 'tmu'); ?>" 
                           style="width: 100%; margin-bottom: 10px;" />
                    
                    <button type="button" 
                            id="tmdb-verify-btn" 
                            class="button" 
                            style="width: 100%;">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Verify & Import', 'tmu'); ?>
                    </button>
                </div>
            <?php endif; ?>
            
            <div class="tmdb-actions" style="margin-top: 15px; border-top: 1px solid #ddd; padding-top: 15px;">
                <div class="action-buttons">
                    <button type="button" 
                            id="tmdb-clear-btn" 
                            class="button" 
                            style="width: 48%;">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Clear Data', 'tmu'); ?>
                    </button>
                    
                    <button type="button" 
                            id="tmdb-refresh-btn" 
                            class="button" 
                            style="width: 48%; float: right;">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Refresh', 'tmu'); ?>
                    </button>
                </div>
                <div style="clear: both;"></div>
            </div>
            
            <div id="tmdb-loading" style="display: none; text-align: center; margin: 15px 0;">
                <span class="spinner is-active"></span>
                <span id="tmdb-loading-text"><?php _e('Processing...', 'tmu'); ?></span>
            </div>
            
            <div id="tmdb-messages" style="margin-top: 10px;">
                <!-- Status messages will appear here -->
            </div>
        </div>
        
        <style>
            .tmu-tmdb-metabox .tmdb-search-result {
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 10px;
                margin-bottom: 10px;
                cursor: pointer;
                transition: background-color 0.2s;
            }
            
            .tmu-tmdb-metabox .tmdb-search-result:hover {
                background-color: #f5f5f5;
            }
            
            .tmu-tmdb-metabox .tmdb-search-result.selected {
                background-color: #e7f3ff;
                border-color: #0073aa;
            }
            
            .tmu-tmdb-metabox .result-title {
                font-weight: bold;
                margin-bottom: 5px;
            }
            
            .tmu-tmdb-metabox .result-meta {
                font-size: 12px;
                color: #666;
            }
            
            .tmu-tmdb-metabox .result-poster {
                float: left;
                margin-right: 10px;
                width: 40px;
                height: 60px;
                background: #f0f0f0;
                border-radius: 4px;
            }
            
            .tmu-tmdb-metabox .notice {
                padding: 8px 12px;
                margin: 10px 0 5px;
                border-radius: 4px;
            }
            
            .tmu-tmdb-metabox .notice-success {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
            }
            
            .tmu-tmdb-metabox .notice-error {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
            }
        </style>
        <?php
    }
    
    /**
     * Save meta box data
     * 
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     */
    public function saveMetaBox(int $post_id, \WP_Post $post): void {
        // Verify nonce
        if (!isset($_POST['tmdb_metabox_nonce']) || 
            !wp_verify_nonce($_POST['tmdb_metabox_nonce'], 'tmdb_metabox_nonce')) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Skip autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Save TMDB ID if provided
        if (isset($_POST['tmdb_id'])) {
            $tmdb_id = intval($_POST['tmdb_id']);
            if ($tmdb_id > 0) {
                $this->saveTMDBId($post_id, $tmdb_id);
            } else {
                $this->clearTMDBId($post_id);
            }
        }
    }
    
    /**
     * Handle TMDB sync AJAX request
     */
    public function handleTMDBSync(): void {
        check_ajax_referer('tmu_tmdb_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $force = (bool) ($_POST['force'] ?? false);
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Invalid request.', 'tmu')]);
        }
        
        $tmdb_id = tmu_get_meta($post_id, 'tmdb_id');
        if (!$tmdb_id) {
            wp_send_json_error(['message' => __('No TMDB ID found.', 'tmu')]);
        }
        
        // Here you would implement the actual TMDB API sync
        // For now, we'll simulate success
        $result = $this->performTMDBSync($post_id, $tmdb_id, $force);
        
        if ($result['success']) {
            update_post_meta($post_id, '_tmdb_last_sync', current_time('mysql'));
            update_post_meta($post_id, '_tmdb_sync_status', 'success');
            
            wp_send_json_success([
                'message' => __('TMDB sync completed successfully!', 'tmu'),
                'last_sync' => human_time_diff(time()) . ' ' . __('ago', 'tmu')
            ]);
        } else {
            update_post_meta($post_id, '_tmdb_sync_status', 'error');
            wp_send_json_error(['message' => $result['message']]);
        }
    }
    
    /**
     * Handle TMDB search AJAX request
     */
    public function handleTMDBSearch(): void {
        check_ajax_referer('tmu_tmdb_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'movie');
        
        if (empty($query)) {
            wp_send_json_error(['message' => __('Search query is required.', 'tmu')]);
        }
        
        // Here you would implement the actual TMDB API search
        // For now, we'll return mock data
        $results = $this->performTMDBSearch($query, $post_type);
        
        wp_send_json_success(['results' => $results]);
    }
    
    /**
     * Get TMDB ID for a post
     * 
     * @param int $post_id Post ID
     * @return int|null TMDB ID
     */
    private function getTMDBId(int $post_id): ?int {
        global $wpdb;
        
        $post_type = get_post_type($post_id);
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
     * Save TMDB ID for a post
     * 
     * @param int $post_id Post ID
     * @param int $tmdb_id TMDB ID
     */
    private function saveTMDBId(int $post_id, int $tmdb_id): void {
        global $wpdb;
        
        $post_type = get_post_type($post_id);
        $table_map = [
            'movie' => 'tmu_movies',
            'tv' => 'tmu_tv_series',
            'drama' => 'tmu_dramas',
            'people' => 'tmu_people'
        ];
        
        if (!isset($table_map[$post_type])) {
            return;
        }
        
        $table_name = $wpdb->prefix . $table_map[$post_type];
        
        // Check if record exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$table_name} WHERE ID = %d",
            $post_id
        ));
        
        if ($exists) {
            $wpdb->update(
                $table_name,
                ['tmdb_id' => $tmdb_id],
                ['ID' => $post_id]
            );
        } else {
            $wpdb->insert(
                $table_name,
                [
                    'ID' => $post_id,
                    'tmdb_id' => $tmdb_id,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ]
            );
        }
    }
    
    /**
     * Clear TMDB ID for a post
     * 
     * @param int $post_id Post ID
     */
    private function clearTMDBId(int $post_id): void {
        global $wpdb;
        
        $post_type = get_post_type($post_id);
        $table_map = [
            'movie' => 'tmu_movies',
            'tv' => 'tmu_tv_series',
            'drama' => 'tmu_dramas',
            'people' => 'tmu_people'
        ];
        
        if (!isset($table_map[$post_type])) {
            return;
        }
        
        $table_name = $wpdb->prefix . $table_map[$post_type];
        
        $wpdb->update(
            $table_name,
            ['tmdb_id' => null],
            ['ID' => $post_id]
        );
    }
    
    /**
     * Get TMDB URL for a post type and ID
     * 
     * @param string $post_type Post type
     * @param int $tmdb_id TMDB ID
     * @return string TMDB URL
     */
    private function getTMDBUrl(string $post_type, int $tmdb_id): string {
        $type_map = [
            'movie' => 'movie',
            'tv' => 'tv',
            'drama' => 'tv',
            'people' => 'person'
        ];
        
        $type = $type_map[$post_type] ?? 'movie';
        return "https://www.themoviedb.org/{$type}/{$tmdb_id}";
    }
    
    /**
     * Perform TMDB sync (placeholder for actual implementation)
     * 
     * @param int $post_id Post ID
     * @param int $tmdb_id TMDB ID
     * @param bool $force Force sync
     * @return array Result
     */
    private function performTMDBSync(int $post_id, int $tmdb_id, bool $force): array {
        // This would be implemented in Step 9 (TMDB API Integration)
        // For now, simulate success
        return [
            'success' => true,
            'message' => __('Sync completed successfully!', 'tmu')
        ];
    }
    
    /**
     * Perform TMDB search (placeholder for actual implementation)
     * 
     * @param string $query Search query
     * @param string $post_type Post type
     * @return array Search results
     */
    private function performTMDBSearch(string $query, string $post_type): array {
        // This would be implemented in Step 9 (TMDB API Integration)
        // For now, return mock data
        return [
            [
                'id' => 550,
                'title' => 'Fight Club',
                'release_date' => '1999-10-15',
                'overview' => 'A ticking-time-bomb insomniac...',
                'poster_path' => '/pB8BM7pdSp6B6Ih7QZ4DrQ3PmJK.jpg'
            ]
        ];
    }
}