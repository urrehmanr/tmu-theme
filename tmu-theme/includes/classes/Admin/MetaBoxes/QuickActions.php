<?php
/**
 * Quick Actions Meta Box
 * 
 * Provides quick action buttons and shortcuts within post editor interface.
 * Enables rapid content management and common operations.
 * 
 * @package TMU\Admin\MetaBoxes
 * @since 1.0.0
 */

namespace TMU\Admin\MetaBoxes;

/**
 * QuickActions class
 * 
 * Manages quick action shortcuts in post editor
 */
class QuickActions {
    
    /**
     * Available actions by post type
     * @var array
     */
    private $available_actions = [
        'movie' => [
            'sync_tmdb' => 'Sync with TMDB',
            'update_images' => 'Update Images',
            'generate_trailer' => 'Find Trailer',
            'set_featured' => 'Set as Featured',
            'quick_publish' => 'Quick Publish',
            'duplicate_post' => 'Duplicate Movie'
        ],
        'tv' => [
            'sync_tmdb' => 'Sync with TMDB',
            'update_images' => 'Update Images',
            'sync_episodes' => 'Sync Episodes',
            'set_featured' => 'Set as Featured',
            'quick_publish' => 'Quick Publish',
            'duplicate_post' => 'Duplicate TV Show'
        ],
        'drama' => [
            'sync_tmdb' => 'Sync with TMDB',
            'update_images' => 'Update Images',
            'sync_episodes' => 'Sync Episodes',
            'set_featured' => 'Set as Featured',
            'quick_publish' => 'Quick Publish',
            'duplicate_post' => 'Duplicate Drama'
        ],
        'people' => [
            'sync_tmdb' => 'Sync with TMDB',
            'update_images' => 'Update Images',
            'find_credits' => 'Find Credits',
            'set_featured' => 'Set as Featured',
            'quick_publish' => 'Quick Publish',
            'duplicate_post' => 'Duplicate Person'
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->initHooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void {
        add_action('add_meta_boxes', [$this, 'addMetaBox']);
        add_action('wp_ajax_tmu_quick_action', [$this, 'handleQuickAction']);
        add_action('save_post', [$this, 'saveQuickActionSettings'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }
    
    /**
     * Add meta box to post types
     */
    public function addMetaBox(): void {
        $post_types = ['movie', 'tv', 'drama', 'people'];
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'tmu-quick-actions',
                __('Quick Actions', 'tmu'),
                [$this, 'renderMetaBox'],
                $post_type,
                'side',
                'high'
            );
        }
    }
    
    /**
     * Render quick actions meta box
     * 
     * @param \WP_Post $post Current post object
     */
    public function renderMetaBox(\WP_Post $post): void {
        $post_type = $post->post_type;
        $actions = $this->available_actions[$post_type] ?? [];
        
        // Get current action settings
        $action_settings = get_post_meta($post->ID, '_tmu_quick_action_settings', true) ?: [];
        
        wp_nonce_field('tmu_quick_actions', 'tmu_quick_actions_nonce');
        ?>
        <div class="tmu-quick-actions-box">
            <?php if (!empty($actions)): ?>
                <div class="quick-actions-grid">
                    <?php foreach ($actions as $action_key => $action_label): ?>
                        <div class="quick-action-item" data-action="<?php echo esc_attr($action_key); ?>">
                            <button type="button" 
                                    class="button quick-action-btn <?php echo $this->getActionButtonClass($action_key); ?>"
                                    data-action="<?php echo esc_attr($action_key); ?>"
                                    data-post-id="<?php echo $post->ID; ?>"
                                    <?php echo $this->isActionDisabled($action_key, $post) ? 'disabled' : ''; ?>>
                                <?php echo $this->getActionIcon($action_key); ?>
                                <?php echo esc_html(__($action_label, 'tmu')); ?>
                            </button>
                            
                            <div class="action-status" id="status-<?php echo esc_attr($action_key); ?>">
                                <?php echo $this->getActionStatus($action_key, $post); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="quick-actions-settings">
                    <h4><?php _e('Action Settings', 'tmu'); ?></h4>
                    
                    <label>
                        <input type="checkbox" 
                               name="auto_sync_tmdb" 
                               value="1" 
                               <?php checked(isset($action_settings['auto_sync_tmdb'])); ?>>
                        <?php _e('Auto-sync with TMDB on save', 'tmu'); ?>
                    </label>
                    
                    <label>
                        <input type="checkbox" 
                               name="auto_update_images" 
                               value="1" 
                               <?php checked(isset($action_settings['auto_update_images'])); ?>>
                        <?php _e('Auto-update images from TMDB', 'tmu'); ?>
                    </label>
                    
                    <label>
                        <input type="checkbox" 
                               name="notify_on_completion" 
                               value="1" 
                               <?php checked(isset($action_settings['notify_on_completion'])); ?>>
                        <?php _e('Show notifications when actions complete', 'tmu'); ?>
                    </label>
                </div>
                
                <div class="bulk-quick-actions">
                    <h4><?php _e('Bulk Actions', 'tmu'); ?></h4>
                    <button type="button" class="button" onclick="tmuBulkQuickAction('sync_all', '<?php echo $post_type; ?>')">
                        <?php _e('Sync All Similar Content', 'tmu'); ?>
                    </button>
                </div>
                
            <?php else: ?>
                <p><?php _e('No quick actions available for this content type.', 'tmu'); ?></p>
            <?php endif; ?>
        </div>
        
        <div id="quick-action-progress" class="quick-action-progress" style="display:none;">
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <div class="progress-text"></div>
        </div>
        
        <style>
        .tmu-quick-actions-box .quick-actions-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .quick-action-item {
            position: relative;
        }
        
        .quick-action-btn {
            width: 100%;
            text-align: left;
            position: relative;
            padding: 8px 12px;
            min-height: 36px;
        }
        
        .quick-action-btn.primary {
            background: #2271b1;
            color: white;
            border-color: #2271b1;
        }
        
        .quick-action-btn.success {
            background: #00a32a;
            color: white;
            border-color: #00a32a;
        }
        
        .quick-action-btn.warning {
            background: #dba617;
            color: white;
            border-color: #dba617;
        }
        
        .quick-action-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .action-status {
            font-size: 11px;
            margin-top: 2px;
            color: #646970;
        }
        
        .action-status.success {
            color: #00a32a;
        }
        
        .action-status.error {
            color: #d63638;
        }
        
        .quick-actions-settings {
            border-top: 1px solid #c3c4c7;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .quick-actions-settings h4 {
            margin: 0 0 10px 0;
            font-size: 12px;
            text-transform: uppercase;
            color: #646970;
        }
        
        .quick-actions-settings label {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
        }
        
        .bulk-quick-actions {
            border-top: 1px solid #c3c4c7;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .bulk-quick-actions h4 {
            margin: 0 0 10px 0;
            font-size: 12px;
            text-transform: uppercase;
            color: #646970;
        }
        
        .quick-action-progress {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            background: #f9f9f9;
        }
        
        .progress-bar {
            width: 100%;
            height: 16px;
            background: #e1e1e1;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .progress-fill {
            height: 100%;
            background: #2271b1;
            width: 0;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            font-size: 12px;
            text-align: center;
            color: #646970;
        }
        </style>
        <?php
    }
    
    /**
     * Handle quick action AJAX requests
     */
    public function handleQuickAction(): void {
        check_ajax_referer('tmu_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Unauthorized', 'tmu')]);
        }
        
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id || !$action) {
            wp_send_json_error(['message' => __('Invalid parameters', 'tmu')]);
        }
        
        try {
            $result = $this->executeQuickAction($action, $post_id);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Execute quick action
     * 
     * @param string $action Action to execute
     * @param int $post_id Post ID
     * @return array Result data
     */
    private function executeQuickAction(string $action, int $post_id): array {
        $post = get_post($post_id);
        if (!$post) {
            throw new \Exception(__('Post not found', 'tmu'));
        }
        
        switch ($action) {
            case 'sync_tmdb':
                return $this->syncWithTMDB($post_id);
                
            case 'update_images':
                return $this->updateImages($post_id);
                
            case 'generate_trailer':
                return $this->generateTrailer($post_id);
                
            case 'sync_episodes':
                return $this->syncEpisodes($post_id);
                
            case 'find_credits':
                return $this->findCredits($post_id);
                
            case 'set_featured':
                return $this->setFeatured($post_id);
                
            case 'quick_publish':
                return $this->quickPublish($post_id);
                
            case 'duplicate_post':
                return $this->duplicatePost($post_id);
                
            default:
                throw new \Exception(__('Unknown action', 'tmu'));
        }
    }
    
    /**
     * Sync with TMDB
     * 
     * @param int $post_id Post ID
     * @return array Result
     */
    private function syncWithTMDB(int $post_id): array {
        $tmdb_id = get_post_meta($post_id, 'tmdb_id', true);
        
        if (!$tmdb_id) {
            throw new \Exception(__('No TMDB ID found', 'tmu'));
        }
        
        // Trigger TMDB sync (would integrate with TMDB API)
        do_action('tmu_sync_single_post_tmdb', $post_id);
        
        // Update last sync time
        update_post_meta($post_id, '_last_tmdb_sync', current_time('mysql'));
        
        return [
            'message' => __('Successfully synced with TMDB', 'tmu'),
            'action' => 'sync_tmdb',
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Update images from TMDB
     * 
     * @param int $post_id Post ID
     * @return array Result
     */
    private function updateImages(int $post_id): array {
        // Trigger image update
        do_action('tmu_update_post_images', $post_id);
        
        return [
            'message' => __('Images updated successfully', 'tmu'),
            'action' => 'update_images',
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Generate trailer link
     * 
     * @param int $post_id Post ID
     * @return array Result
     */
    private function generateTrailer(int $post_id): array {
        // Find and set trailer (would integrate with TMDB/YouTube)
        do_action('tmu_find_trailer', $post_id);
        
        return [
            'message' => __('Trailer search completed', 'tmu'),
            'action' => 'generate_trailer',
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Sync episodes for TV shows/dramas
     * 
     * @param int $post_id Post ID
     * @return array Result
     */
    private function syncEpisodes(int $post_id): array {
        // Trigger episode sync
        do_action('tmu_sync_episodes', $post_id);
        
        return [
            'message' => __('Episode sync initiated', 'tmu'),
            'action' => 'sync_episodes',
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Find credits for people
     * 
     * @param int $post_id Post ID
     * @return array Result
     */
    private function findCredits(int $post_id): array {
        // Find and update credits
        do_action('tmu_find_credits', $post_id);
        
        return [
            'message' => __('Credits search completed', 'tmu'),
            'action' => 'find_credits',
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Set as featured content
     * 
     * @param int $post_id Post ID
     * @return array Result
     */
    private function setFeatured(int $post_id): array {
        $is_featured = get_post_meta($post_id, '_is_featured', true);
        $new_status = !$is_featured;
        
        update_post_meta($post_id, '_is_featured', $new_status);
        
        return [
            'message' => $new_status 
                ? __('Content set as featured', 'tmu')
                : __('Content removed from featured', 'tmu'),
            'action' => 'set_featured',
            'featured' => $new_status,
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Quick publish post
     * 
     * @param int $post_id Post ID
     * @return array Result
     */
    private function quickPublish(int $post_id): array {
        $post = get_post($post_id);
        
        if ($post->post_status === 'publish') {
            throw new \Exception(__('Post is already published', 'tmu'));
        }
        
        wp_update_post([
            'ID' => $post_id,
            'post_status' => 'publish'
        ]);
        
        return [
            'message' => __('Content published successfully', 'tmu'),
            'action' => 'quick_publish',
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Duplicate post
     * 
     * @param int $post_id Post ID
     * @return array Result
     */
    private function duplicatePost(int $post_id): array {
        $post = get_post($post_id);
        
        $new_post_data = [
            'post_title' => $post->post_title . ' (Copy)',
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_type' => $post->post_type,
            'post_status' => 'draft'
        ];
        
        $new_post_id = wp_insert_post($new_post_data);
        
        if (is_wp_error($new_post_id)) {
            throw new \Exception($new_post_id->get_error_message());
        }
        
        // Copy meta data
        $meta_data = get_post_meta($post_id);
        foreach ($meta_data as $key => $values) {
            foreach ($values as $value) {
                update_post_meta($new_post_id, $key, $value);
            }
        }
        
        // Copy taxonomies
        $taxonomies = get_object_taxonomies($post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);
            wp_set_object_terms($new_post_id, $terms, $taxonomy);
        }
        
        return [
            'message' => __('Content duplicated successfully', 'tmu'),
            'action' => 'duplicate_post',
            'new_post_id' => $new_post_id,
            'edit_url' => admin_url("post.php?post={$new_post_id}&action=edit"),
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Save quick action settings
     * 
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     */
    public function saveQuickActionSettings(int $post_id, \WP_Post $post): void {
        if (!isset($_POST['tmu_quick_actions_nonce']) || 
            !wp_verify_nonce($_POST['tmu_quick_actions_nonce'], 'tmu_quick_actions')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $settings = [];
        
        if (isset($_POST['auto_sync_tmdb'])) {
            $settings['auto_sync_tmdb'] = true;
        }
        
        if (isset($_POST['auto_update_images'])) {
            $settings['auto_update_images'] = true;
        }
        
        if (isset($_POST['notify_on_completion'])) {
            $settings['notify_on_completion'] = true;
        }
        
        update_post_meta($post_id, '_tmu_quick_action_settings', $settings);
        
        // Execute auto actions if enabled
        if (isset($settings['auto_sync_tmdb'])) {
            wp_schedule_single_event(time() + 5, 'tmu_auto_sync_tmdb', [$post_id]);
        }
        
        if (isset($settings['auto_update_images'])) {
            wp_schedule_single_event(time() + 10, 'tmu_auto_update_images', [$post_id]);
        }
    }
    
    /**
     * Enqueue scripts for quick actions
     * 
     * @param string $hook_suffix Current admin page
     */
    public function enqueueScripts(string $hook_suffix): void {
        global $post_type;
        
        if (in_array($hook_suffix, ['post.php', 'post-new.php']) && 
            in_array($post_type, ['movie', 'tv', 'drama', 'people'])) {
            
            wp_enqueue_script(
                'tmu-quick-actions',
                get_template_directory_uri() . '/assets/build/js/quick-actions.js',
                ['jquery', 'wp-util'],
                get_theme_file_version('assets/build/js/quick-actions.js'),
                true
            );
            
            wp_localize_script('tmu-quick-actions', 'tmuQuickActions', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('tmu_admin_nonce'),
                'strings' => [
                    'confirm_action' => __('Are you sure you want to perform this action?', 'tmu'),
                    'processing' => __('Processing...', 'tmu'),
                    'success' => __('Action completed successfully!', 'tmu'),
                    'error' => __('Action failed. Please try again.', 'tmu'),
                ]
            ]);
        }
    }
    
    /**
     * Get action button class
     * 
     * @param string $action Action key
     * @return string CSS class
     */
    private function getActionButtonClass(string $action): string {
        $classes = [
            'sync_tmdb' => 'primary',
            'update_images' => 'primary',
            'set_featured' => 'success',
            'quick_publish' => 'success',
            'duplicate_post' => 'secondary'
        ];
        
        return $classes[$action] ?? 'secondary';
    }
    
    /**
     * Get action icon
     * 
     * @param string $action Action key
     * @return string Icon HTML
     */
    private function getActionIcon(string $action): string {
        $icons = [
            'sync_tmdb' => '🔄 ',
            'update_images' => '🖼️ ',
            'generate_trailer' => '🎬 ',
            'sync_episodes' => '📺 ',
            'find_credits' => '👥 ',
            'set_featured' => '⭐ ',
            'quick_publish' => '🚀 ',
            'duplicate_post' => '📄 '
        ];
        
        return $icons[$action] ?? '🔧 ';
    }
    
    /**
     * Check if action is disabled
     * 
     * @param string $action Action key
     * @param \WP_Post $post Post object
     * @return bool True if disabled
     */
    private function isActionDisabled(string $action, \WP_Post $post): bool {
        switch ($action) {
            case 'sync_tmdb':
                return !get_post_meta($post->ID, 'tmdb_id', true);
                
            case 'quick_publish':
                return $post->post_status === 'publish';
                
            case 'sync_episodes':
                return !in_array($post->post_type, ['tv', 'drama']);
                
            case 'find_credits':
                return $post->post_type !== 'people';
                
            case 'generate_trailer':
                return $post->post_type === 'people';
                
            default:
                return false;
        }
    }
    
    /**
     * Get action status
     * 
     * @param string $action Action key
     * @param \WP_Post $post Post object
     * @return string Status HTML
     */
    private function getActionStatus(string $action, \WP_Post $post): string {
        $last_sync = get_post_meta($post->ID, '_last_tmdb_sync', true);
        $is_featured = get_post_meta($post->ID, '_is_featured', true);
        
        switch ($action) {
            case 'sync_tmdb':
                return $last_sync 
                    ? '<span class="success">Last sync: ' . human_time_diff(strtotime($last_sync)) . ' ago</span>'
                    : '<span>Never synced</span>';
                    
            case 'set_featured':
                return $is_featured 
                    ? '<span class="success">Currently featured</span>'
                    : '<span>Not featured</span>';
                    
            case 'quick_publish':
                return $post->post_status === 'publish'
                    ? '<span class="success">Published</span>'
                    : '<span>Draft</span>';
                    
            default:
                return '';
        }
    }
}