<?php
/**
 * Dashboard Widgets
 * 
 * Custom dashboard widgets for TMU content management with statistics,
 * recent content, and quick action shortcuts.
 * 
 * @package TMU\Admin\Dashboard
 * @since 1.0.0
 */

namespace TMU\Admin\Dashboard;

/**
 * Widgets class
 * 
 * Manages custom dashboard widgets for TMU content
 */
class Widgets {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_dashboard_setup', [$this, 'addDashboardWidgets']);
        add_action('wp_ajax_tmu_dashboard_stats', [$this, 'refreshStats']);
    }
    
    /**
     * Add dashboard widgets
     */
    public function addDashboardWidgets(): void {
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        wp_add_dashboard_widget(
            'tmu_content_stats',
            __('TMU Content Statistics', 'tmu-theme'),
            [$this, 'renderContentStatsWidget'],
            null,
            null,
            'normal',
            'high'
        );
        
        wp_add_dashboard_widget(
            'tmu_recent_additions',
            __('Recent TMU Additions', 'tmu-theme'),
            [$this, 'renderRecentAdditionsWidget'],
            null,
            null,
            'side',
            'high'
        );
        
        wp_add_dashboard_widget(
            'tmu_tmdb_status',
            __('TMDB Sync Status', 'tmu-theme'),
            [$this, 'renderTMDBStatusWidget'],
            null,
            null,
            'side',
            'default'
        );
        
        wp_add_dashboard_widget(
            'tmu_quick_actions',
            __('TMU Quick Actions', 'tmu-theme'),
            [$this, 'renderQuickActionsWidget'],
            null,
            null,
            'normal',
            'default'
        );
    }
    
    /**
     * Render content statistics widget
     */
    public function renderContentStatsWidget(): void {
        $stats = $this->getContentStats();
        $total_stats = $this->getTotalContentStats();
        ?>
        <div class="tmu-dashboard-stats">
            <div class="stats-grid">
                <?php foreach ($stats as $post_type => $data): ?>
                    <div class="stat-item" data-post-type="<?php echo esc_attr($post_type); ?>">
                        <div class="stat-icon">
                            <?php echo $this->getPostTypeIcon($post_type); ?>
                        </div>
                        <div class="stat-details">
                            <div class="stat-count"><?php echo number_format($data['count']); ?></div>
                            <div class="stat-label"><?php echo esc_html($data['label']); ?></div>
                            <div class="stat-actions">
                                <a href="<?php echo admin_url('edit.php?post_type=' . $post_type); ?>" class="view-all">
                                    <?php _e('View All', 'tmu-theme'); ?>
                                </a>
                                |
                                <a href="<?php echo admin_url('post-new.php?post_type=' . $post_type); ?>" class="add-new">
                                    <?php _e('Add New', 'tmu-theme'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="total-stats">
                <h4><?php _e('Content Overview', 'tmu-theme'); ?></h4>
                <div class="overview-grid">
                    <div class="overview-item">
                        <span class="label"><?php _e('Total Content:', 'tmu-theme'); ?></span>
                        <span class="value"><?php echo number_format($total_stats['total']); ?></span>
                    </div>
                    <div class="overview-item">
                        <span class="label"><?php _e('Published:', 'tmu-theme'); ?></span>
                        <span class="value"><?php echo number_format($total_stats['published']); ?></span>
                    </div>
                    <div class="overview-item">
                        <span class="label"><?php _e('With TMDB:', 'tmu-theme'); ?></span>
                        <span class="value"><?php echo number_format($total_stats['with_tmdb']); ?></span>
                    </div>
                    <div class="overview-item">
                        <span class="label"><?php _e('This Month:', 'tmu-theme'); ?></span>
                        <span class="value"><?php echo number_format($total_stats['this_month']); ?></span>
                    </div>
                </div>
            </div>
            
            <button type="button" id="refresh-stats" class="button button-secondary">
                <?php _e('Refresh Stats', 'tmu-theme'); ?>
            </button>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#refresh-stats').on('click', function() {
                var button = $(this);
                button.text('<?php _e('Refreshing...', 'tmu-theme'); ?>').prop('disabled', true);
                
                $.post(ajaxurl, {
                    action: 'tmu_dashboard_stats',
                    nonce: '<?php echo wp_create_nonce('tmu_dashboard_stats'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }).always(function() {
                    button.text('<?php _e('Refresh Stats', 'tmu-theme'); ?>').prop('disabled', false);
                });
            });
        });
        </script>
        
        <style>
        .tmu-dashboard-stats .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .tmu-dashboard-stats .stat-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 6px;
            border-left: 4px solid #0073aa;
        }
        .tmu-dashboard-stats .stat-icon {
            font-size: 24px;
            margin-right: 15px;
            color: #0073aa;
        }
        .tmu-dashboard-stats .stat-count {
            font-size: 28px;
            font-weight: bold;
            color: #0073aa;
            line-height: 1;
        }
        .tmu-dashboard-stats .stat-label {
            margin: 5px 0;
            font-weight: 500;
            color: #333;
        }
        .tmu-dashboard-stats .stat-actions {
            font-size: 12px;
            margin-top: 8px;
        }
        .tmu-dashboard-stats .stat-actions a {
            text-decoration: none;
            color: #666;
        }
        .tmu-dashboard-stats .stat-actions a:hover {
            color: #0073aa;
        }
        .tmu-dashboard-stats .total-stats {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .tmu-dashboard-stats .total-stats h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .tmu-dashboard-stats .overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }
        .tmu-dashboard-stats .overview-item {
            display: flex;
            justify-content: space-between;
            padding: 8px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .tmu-dashboard-stats .overview-item .label {
            color: #666;
        }
        .tmu-dashboard-stats .overview-item .value {
            font-weight: bold;
            color: #0073aa;
        }
        </style>
        <?php
    }
    
    /**
     * Render recent additions widget
     */
    public function renderRecentAdditionsWidget(): void {
        $recent_posts = $this->getRecentPosts();
        ?>
        <div class="tmu-recent-additions">
            <?php if (!empty($recent_posts)): ?>
                <ul class="recent-posts-list">
                    <?php foreach ($recent_posts as $post): ?>
                        <li class="recent-post-item">
                            <div class="post-thumbnail">
                                <?php if (has_post_thumbnail($post->ID)): ?>
                                    <?php echo get_the_post_thumbnail($post->ID, [40, 60]); ?>
                                <?php else: ?>
                                    <div class="no-thumb"></div>
                                <?php endif; ?>
                            </div>
                            <div class="post-details">
                                <h4 class="post-title">
                                    <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                        <?php echo esc_html($post->post_title); ?>
                                    </a>
                                </h4>
                                <div class="post-meta">
                                    <span class="post-type"><?php echo esc_html(get_post_type_object($post->post_type)->labels->singular_name); ?></span>
                                    <span class="post-date"><?php echo human_time_diff(strtotime($post->post_date)); ?> ago</span>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="widget-footer">
                    <a href="<?php echo admin_url('edit.php?post_type=movie'); ?>" class="view-all-link">
                        <?php _e('View All Content', 'tmu-theme'); ?>
                    </a>
                </div>
            <?php else: ?>
                <p class="no-content"><?php _e('No content added yet.', 'tmu-theme'); ?></p>
            <?php endif; ?>
        </div>
        
        <style>
        .tmu-recent-additions .recent-posts-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .tmu-recent-additions .recent-post-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .tmu-recent-additions .recent-post-item:last-child {
            border-bottom: none;
        }
        .tmu-recent-additions .post-thumbnail {
            margin-right: 10px;
            flex-shrink: 0;
        }
        .tmu-recent-additions .post-thumbnail img {
            width: 40px;
            height: 60px;
            object-fit: cover;
            border-radius: 3px;
        }
        .tmu-recent-additions .no-thumb {
            width: 40px;
            height: 60px;
            background: #f0f0f0;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #999;
        }
        .tmu-recent-additions .post-details {
            flex: 1;
            min-width: 0;
        }
        .tmu-recent-additions .post-title {
            margin: 0 0 5px 0;
            font-size: 13px;
            line-height: 1.3;
        }
        .tmu-recent-additions .post-title a {
            text-decoration: none;
            color: #333;
        }
        .tmu-recent-additions .post-title a:hover {
            color: #0073aa;
        }
        .tmu-recent-additions .post-meta {
            font-size: 11px;
            color: #666;
        }
        .tmu-recent-additions .post-meta span {
            margin-right: 8px;
        }
        .tmu-recent-additions .widget-footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            text-align: center;
        }
        .tmu-recent-additions .view-all-link {
            text-decoration: none;
            font-size: 12px;
        }
        .tmu-recent-additions .no-content {
            text-align: center;
            color: #666;
            font-style: italic;
        }
        </style>
        <?php
    }
    
    /**
     * Render TMDB status widget
     */
    public function renderTMDBStatusWidget(): void {
        $tmdb_stats = $this->getTMDBStats();
        ?>
        <div class="tmu-tmdb-status">
            <div class="status-overview">
                <div class="status-item">
                    <span class="label"><?php _e('Synced Content:', 'tmu-theme'); ?></span>
                    <span class="value success"><?php echo number_format($tmdb_stats['synced']); ?></span>
                </div>
                <div class="status-item">
                    <span class="label"><?php _e('Pending Sync:', 'tmu-theme'); ?></span>
                    <span class="value warning"><?php echo number_format($tmdb_stats['pending']); ?></span>
                </div>
                <div class="status-item">
                    <span class="label"><?php _e('Sync Errors:', 'tmu-theme'); ?></span>
                    <span class="value error"><?php echo number_format($tmdb_stats['errors']); ?></span>
                </div>
            </div>
            
            <?php if ($tmdb_stats['last_sync']): ?>
                <div class="last-sync">
                    <strong><?php _e('Last Sync:', 'tmu-theme'); ?></strong>
                    <?php echo human_time_diff(strtotime($tmdb_stats['last_sync'])); ?> ago
                </div>
            <?php endif; ?>
            
            <div class="sync-actions">
                <button type="button" id="quick-sync" class="button button-primary button-small">
                    <?php _e('Quick Sync', 'tmu-theme'); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=tmu-quick-actions'); ?>" class="button button-secondary button-small">
                    <?php _e('Sync Settings', 'tmu-theme'); ?>
                </a>
            </div>
        </div>
        
        <style>
        .tmu-tmdb-status .status-overview {
            margin-bottom: 15px;
        }
        .tmu-tmdb-status .status-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px;
            background: #f9f9f9;
            border-radius: 3px;
        }
        .tmu-tmdb-status .status-item .label {
            color: #666;
            font-size: 12px;
        }
        .tmu-tmdb-status .status-item .value {
            font-weight: bold;
            font-size: 12px;
        }
        .tmu-tmdb-status .status-item .value.success {
            color: #46b450;
        }
        .tmu-tmdb-status .status-item .value.warning {
            color: #ffb900;
        }
        .tmu-tmdb-status .status-item .value.error {
            color: #dc3232;
        }
        .tmu-tmdb-status .last-sync {
            margin-bottom: 15px;
            padding: 8px;
            background: #e7f4ff;
            border-radius: 3px;
            font-size: 12px;
        }
        .tmu-tmdb-status .sync-actions {
            display: flex;
            gap: 5px;
        }
        .tmu-tmdb-status .sync-actions .button {
            flex: 1;
            text-align: center;
        }
        </style>
        <?php
    }
    
    /**
     * Render quick actions widget
     */
    public function renderQuickActionsWidget(): void {
        ?>
        <div class="tmu-quick-actions-widget">
            <div class="actions-grid">
                <div class="action-item">
                    <div class="action-icon">📊</div>
                    <div class="action-content">
                        <h4><?php _e('Analytics', 'tmu-theme'); ?></h4>
                        <p><?php _e('View content performance and statistics', 'tmu-theme'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=tmu-analytics'); ?>" class="button button-small">
                            <?php _e('View Analytics', 'tmu-theme'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="action-item">
                    <div class="action-icon">🔄</div>
                    <div class="action-content">
                        <h4><?php _e('Bulk Sync', 'tmu-theme'); ?></h4>
                        <p><?php _e('Sync all content with TMDB database', 'tmu-theme'); ?></p>
                        <button type="button" id="bulk-sync-action" class="button button-small">
                            <?php _e('Start Sync', 'tmu-theme'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="action-item">
                    <div class="action-icon">📥</div>
                    <div class="action-content">
                        <h4><?php _e('Import', 'tmu-theme'); ?></h4>
                        <p><?php _e('Import content from external sources', 'tmu-theme'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=tmu-import'); ?>" class="button button-small">
                            <?php _e('Import Tools', 'tmu-theme'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="action-item">
                    <div class="action-icon">🔧</div>
                    <div class="action-content">
                        <h4><?php _e('Settings', 'tmu-theme'); ?></h4>
                        <p><?php _e('Configure TMU theme options', 'tmu-theme'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=tmu-settings'); ?>" class="button button-small">
                            <?php _e('Settings', 'tmu-theme'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .tmu-quick-actions-widget .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .tmu-quick-actions-widget .action-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 6px;
            text-align: center;
            border: 1px solid #e5e5e5;
        }
        .tmu-quick-actions-widget .action-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .tmu-quick-actions-widget .action-content h4 {
            margin: 0 0 8px 0;
            color: #333;
            font-size: 14px;
        }
        .tmu-quick-actions-widget .action-content p {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 12px;
            line-height: 1.4;
        }
        .tmu-quick-actions-widget .button {
            width: 100%;
        }
        </style>
        <?php
    }
    
    /**
     * Get content statistics
     * 
     * @return array Statistics data
     */
    private function getContentStats(): array {
        $stats = [];
        $post_types = ['movie', 'tv', 'drama', 'people'];
        
        foreach ($post_types as $post_type) {
            if (post_type_exists($post_type)) {
                $count = wp_count_posts($post_type);
                $post_type_object = get_post_type_object($post_type);
                
                $stats[$post_type] = [
                    'count' => $count->publish ?? 0,
                    'label' => $post_type_object->labels->name ?? ucfirst($post_type)
                ];
            }
        }
        
        return $stats;
    }
    
    /**
     * Get total content statistics
     * 
     * @return array Total statistics
     */
    private function getTotalContentStats(): array {
        global $wpdb;
        
        $post_types = "'" . implode("','", ['movie', 'tv', 'drama', 'people']) . "'";
        
        // Total content
        $total = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type IN ({$post_types})
        ");
        
        // Published content
        $published = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type IN ({$post_types}) 
            AND post_status = 'publish'
        ");
        
        // Content with TMDB ID
        $with_tmdb = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID) 
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type IN ({$post_types})
            AND pm.meta_key = 'tmdb_id'
            AND pm.meta_value != ''
        ");
        
        // This month's content
        $this_month = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type IN ({$post_types})
            AND YEAR(post_date) = %d
            AND MONTH(post_date) = %d
        ", date('Y'), date('n')));
        
        return [
            'total' => intval($total),
            'published' => intval($published),
            'with_tmdb' => intval($with_tmdb),
            'this_month' => intval($this_month)
        ];
    }
    
    /**
     * Get recent posts
     * 
     * @return array Recent posts
     */
    private function getRecentPosts(): array {
        $query = new \WP_Query([
            'post_type' => ['movie', 'tv', 'drama', 'people'],
            'post_status' => 'any',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        ]);
        
        return $query->posts;
    }
    
    /**
     * Get TMDB statistics
     * 
     * @return array TMDB statistics
     */
    private function getTMDBStats(): array {
        global $wpdb;
        
        $post_types = "'" . implode("','", ['movie', 'tv', 'drama', 'people']) . "'";
        
        // Synced content (has TMDB ID and recent sync)
        $synced = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID) 
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
            JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
            WHERE p.post_type IN ({$post_types})
            AND pm1.meta_key = 'tmdb_id'
            AND pm1.meta_value != ''
            AND pm2.meta_key = '_tmdb_last_sync'
            AND pm2.meta_value > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Pending sync (has TMDB ID but no recent sync)
        $pending = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID) 
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_tmdb_last_sync'
            WHERE p.post_type IN ({$post_types})
            AND pm1.meta_key = 'tmdb_id'
            AND pm1.meta_value != ''
            AND (pm2.meta_value IS NULL OR pm2.meta_value <= DATE_SUB(NOW(), INTERVAL 30 DAY))
        ");
        
        // Sync errors
        $errors = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID) 
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type IN ({$post_types})
            AND pm.meta_key = '_tmdb_sync_error'
            AND pm.meta_value != ''
        ");
        
        // Last sync
        $last_sync = $wpdb->get_var("
            SELECT MAX(meta_value) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_tmdb_last_sync'
        ");
        
        return [
            'synced' => intval($synced),
            'pending' => intval($pending),
            'errors' => intval($errors),
            'last_sync' => $last_sync
        ];
    }
    
    /**
     * Get post type icon
     * 
     * @param string $post_type Post type
     * @return string Icon HTML
     */
    private function getPostTypeIcon(string $post_type): string {
        $icons = [
            'movie' => '🎬',
            'tv' => '📺',
            'drama' => '🎭',
            'people' => '👤'
        ];
        
        return $icons[$post_type] ?? '📄';
    }
    
    /**
     * AJAX handler for refreshing statistics
     */
    public function refreshStats(): void {
        check_ajax_referer('tmu_dashboard_stats', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Unauthorized', 'tmu-theme'));
        }
        
        // Clear any cached stats
        wp_cache_delete('tmu_dashboard_stats');
        
        wp_send_json_success(['message' => __('Statistics refreshed', 'tmu-theme')]);
    }
}