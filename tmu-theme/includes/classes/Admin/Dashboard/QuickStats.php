<?php
/**
 * Quick Stats Dashboard
 * 
 * Provides quick statistics and metrics for TMU content management.
 * Displays comprehensive analytics in dashboard widgets and dedicated pages.
 * 
 * @package TMU\Admin\Dashboard
 * @since 1.0.0
 */

namespace TMU\Admin\Dashboard;

/**
 * QuickStats class
 * 
 * Manages content statistics and analytics display
 */
class QuickStats {
    
    /**
     * Cache duration for statistics (in seconds)
     * @var int
     */
    private const CACHE_DURATION = 3600; // 1 hour
    
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
        add_action('wp_dashboard_setup', [$this, 'addDashboardWidgets']);
        add_action('wp_ajax_tmu_refresh_stats', [$this, 'refreshStats']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        
        // Add stats to admin pages
        add_action('admin_init', [$this, 'addStatsToAdminPages']);
        
        // Clear cache when content is updated
        add_action('save_post', [$this, 'clearStatsCache']);
        add_action('delete_post', [$this, 'clearStatsCache']);
        add_action('created_term', [$this, 'clearStatsCache']);
        add_action('delete_term', [$this, 'clearStatsCache']);
    }
    
    /**
     * Add dashboard widgets
     */
    public function addDashboardWidgets(): void {
        wp_add_dashboard_widget(
            'tmu-quick-stats',
            __('TMU Quick Statistics', 'tmu-theme'),
            [$this, 'renderQuickStatsWidget']
        );
        
        wp_add_dashboard_widget(
            'tmu-content-breakdown',
            __('Content Breakdown', 'tmu-theme'),
            [$this, 'renderContentBreakdownWidget']
        );
        
        wp_add_dashboard_widget(
            'tmu-recent-activity',
            __('Recent Activity', 'tmu-theme'),
            [$this, 'renderRecentActivityWidget']
        );
        
        wp_add_dashboard_widget(
            'tmu-tmdb-sync-status',
            __('TMDB Sync Status', 'tmu-theme'),
            [$this, 'renderTMDBSyncStatusWidget']
        );
    }
    
    /**
     * Render quick statistics widget
     */
    public function renderQuickStatsWidget(): void {
        $stats = $this->getQuickStats();
        ?>
        <div class="tmu-quick-stats-widget">
            <div class="stats-grid">
                <?php foreach ($stats as $stat_key => $stat_data): ?>
                    <div class="stat-item <?php echo esc_attr($stat_key); ?>">
                        <div class="stat-icon">
                            <?php echo $this->getStatIcon($stat_key); ?>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($stat_data['count']); ?></div>
                            <div class="stat-label"><?php echo esc_html($stat_data['label']); ?></div>
                            <?php if (isset($stat_data['change'])): ?>
                                <div class="stat-change <?php echo $stat_data['change'] >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $stat_data['change'] >= 0 ? '↗' : '↘'; ?>
                                    <?php echo abs($stat_data['change']); ?> this week
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="stat-actions">
                            <a href="<?php echo admin_url($stat_data['link']); ?>" class="button button-small">
                                <?php _e('View All', 'tmu-theme'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="stats-footer">
                <button type="button" id="refresh-stats" class="button button-small">
                    <?php _e('Refresh Statistics', 'tmu-theme'); ?>
                </button>
                <span class="last-updated">
                    <?php printf(__('Last updated: %s', 'tmu-theme'), $this->getLastUpdated()); ?>
                </span>
            </div>
        </div>
        
        <style>
        .tmu-quick-stats-widget .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .stat-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px;
            background: #f9f9f9;
            border-radius: 4px;
            border-left: 4px solid #2271b1;
        }
        
        .stat-item.movies { border-left-color: #d63638; }
        .stat-item.tv_shows { border-left-color: #00a32a; }
        .stat-item.dramas { border-left-color: #dba617; }
        .stat-item.people { border-left-color: #8b5cf6; }
        
        .stat-icon {
            font-size: 20px;
            line-height: 1;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            line-height: 1.2;
            color: #1d2327;
        }
        
        .stat-label {
            font-size: 12px;
            color: #646970;
            margin-bottom: 4px;
        }
        
        .stat-change {
            font-size: 11px;
            font-weight: 500;
        }
        
        .stat-change.positive { color: #00a32a; }
        .stat-change.negative { color: #d63638; }
        
        .stats-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 10px;
            border-top: 1px solid #c3c4c7;
            font-size: 12px;
            color: #646970;
        }
        </style>
        <?php
    }
    
    /**
     * Render content breakdown widget
     */
    public function renderContentBreakdownWidget(): void {
        $breakdown = $this->getContentBreakdown();
        ?>
        <div class="tmu-content-breakdown-widget">
            <div class="breakdown-chart">
                <?php foreach ($breakdown as $type => $data): ?>
                    <div class="breakdown-item">
                        <div class="breakdown-bar">
                            <div class="breakdown-fill" 
                                 style="width: <?php echo $data['percentage']; ?>%; background-color: <?php echo $data['color']; ?>;">
                            </div>
                        </div>
                        <div class="breakdown-label">
                            <span class="breakdown-type"><?php echo esc_html($data['label']); ?></span>
                            <span class="breakdown-count"><?php echo number_format($data['count']); ?> (<?php echo $data['percentage']; ?>%)</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="breakdown-summary">
                <h4><?php _e('Content Summary', 'tmu-theme'); ?></h4>
                <ul>
                    <li><?php printf(__('Total Content: %s items', 'tmu-theme'), number_format($this->getTotalContentCount())); ?></li>
                    <li><?php printf(__('Published: %s items', 'tmu-theme'), number_format($this->getPublishedContentCount())); ?></li>
                    <li><?php printf(__('Drafts: %s items', 'tmu-theme'), number_format($this->getDraftContentCount())); ?></li>
                    <li><?php printf(__('With TMDB Data: %s items', 'tmu-theme'), number_format($this->getTMDBLinkedCount())); ?></li>
                </ul>
            </div>
        </div>
        
        <style>
        .breakdown-item {
            margin-bottom: 12px;
        }
        
        .breakdown-bar {
            height: 20px;
            background: #e1e1e1;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 4px;
        }
        
        .breakdown-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .breakdown-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
        }
        
        .breakdown-type {
            font-weight: 500;
            color: #1d2327;
        }
        
        .breakdown-count {
            color: #646970;
        }
        
        .breakdown-summary {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #c3c4c7;
        }
        
        .breakdown-summary h4 {
            margin: 0 0 10px 0;
            font-size: 13px;
            color: #1d2327;
        }
        
        .breakdown-summary ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .breakdown-summary li {
            padding: 2px 0;
            font-size: 12px;
            color: #646970;
        }
        </style>
        <?php
    }
    
    /**
     * Render recent activity widget
     */
    public function renderRecentActivityWidget(): void {
        $activities = $this->getRecentActivity();
        ?>
        <div class="tmu-recent-activity-widget">
            <?php if ($activities): ?>
                <ul class="activity-list">
                    <?php foreach ($activities as $activity): ?>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <?php echo $this->getActivityIcon($activity['type']); ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <a href="<?php echo $activity['link']; ?>"><?php echo esc_html($activity['title']); ?></a>
                                </div>
                                <div class="activity-meta">
                                    <span class="activity-type"><?php echo esc_html($activity['type_label']); ?></span>
                                    <span class="activity-time"><?php echo human_time_diff(strtotime($activity['time'])); ?> ago</span>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><?php _e('No recent activity found.', 'tmu-theme'); ?></p>
            <?php endif; ?>
            
            <div class="activity-footer">
                <a href="<?php echo admin_url('edit.php?post_type=movie'); ?>" class="button button-small">
                    <?php _e('View All Content', 'tmu-theme'); ?>
                </a>
            </div>
        </div>
        
        <style>
        .activity-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            font-size: 16px;
            line-height: 1;
            margin-top: 2px;
        }
        
        .activity-content {
            flex: 1;
            min-width: 0;
        }
        
        .activity-title {
            font-size: 13px;
            line-height: 1.3;
            margin-bottom: 2px;
        }
        
        .activity-title a {
            text-decoration: none;
            color: #1d2327;
        }
        
        .activity-title a:hover {
            color: #2271b1;
        }
        
        .activity-meta {
            font-size: 11px;
            color: #646970;
        }
        
        .activity-type {
            font-weight: 500;
            margin-right: 8px;
        }
        
        .activity-footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #c3c4c7;
        }
        </style>
        <?php
    }
    
    /**
     * Render TMDB sync status widget
     */
    public function renderTMDBSyncStatusWidget(): void {
        $sync_status = $this->getTMDBSyncStatus();
        ?>
        <div class="tmu-tmdb-sync-widget">
            <div class="sync-overview">
                <div class="sync-stat">
                    <div class="sync-number"><?php echo number_format($sync_status['total_items']); ?></div>
                    <div class="sync-label"><?php _e('Total Items', 'tmu-theme'); ?></div>
                </div>
                <div class="sync-stat">
                    <div class="sync-number"><?php echo number_format($sync_status['synced_items']); ?></div>
                    <div class="sync-label"><?php _e('TMDB Linked', 'tmu-theme'); ?></div>
                </div>
                <div class="sync-stat">
                    <div class="sync-number"><?php echo number_format($sync_status['pending_sync']); ?></div>
                    <div class="sync-label"><?php _e('Needs Sync', 'tmu-theme'); ?></div>
                </div>
            </div>
            
            <div class="sync-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $sync_status['sync_percentage']; ?>%;"></div>
                </div>
                <div class="progress-text">
                    <?php printf(__('%s%% of content linked to TMDB', 'tmu-theme'), $sync_status['sync_percentage']); ?>
                </div>
            </div>
            
            <div class="sync-actions">
                <button type="button" id="bulk-tmdb-sync" class="button button-primary button-small">
                    <?php _e('Sync All Missing', 'tmu-theme'); ?>
                </button>
                <button type="button" id="refresh-tmdb-status" class="button button-small">
                    <?php _e('Refresh Status', 'tmu-theme'); ?>
                </button>
            </div>
            
            <?php if ($sync_status['last_sync']): ?>
                <div class="sync-info">
                    <small>
                        <?php printf(__('Last sync: %s', 'tmu-theme'), human_time_diff(strtotime($sync_status['last_sync']))); ?> ago
                    </small>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .sync-overview {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .sync-stat {
            text-align: center;
            flex: 1;
        }
        
        .sync-number {
            font-size: 18px;
            font-weight: bold;
            color: #2271b1;
        }
        
        .sync-label {
            font-size: 11px;
            color: #646970;
            margin-top: 2px;
        }
        
        .sync-progress {
            margin-bottom: 15px;
        }
        
        .progress-bar {
            height: 16px;
            background: #e1e1e1;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .progress-fill {
            height: 100%;
            background: #00a32a;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            font-size: 12px;
            text-align: center;
            color: #646970;
        }
        
        .sync-actions {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }
        
        .sync-info {
            text-align: center;
            color: #646970;
        }
        </style>
        <?php
    }
    
    /**
     * Get quick statistics
     * 
     * @return array Statistics data
     */
    private function getQuickStats(): array {
        $cache_key = 'tmu_quick_stats';
        $stats = get_transient($cache_key);
        
        if (false === $stats) {
            $stats = [
                'movies' => [
                    'count' => $this->getPostTypeCount('movie'),
                    'label' => __('Movies', 'tmu-theme'),
                    'link' => 'edit.php?post_type=movie',
                    'change' => $this->getWeeklyChange('movie')
                ],
                'tv_shows' => [
                    'count' => $this->getPostTypeCount('tv'),
                    'label' => __('TV Shows', 'tmu-theme'),
                    'link' => 'edit.php?post_type=tv',
                    'change' => $this->getWeeklyChange('tv')
                ],
                'dramas' => [
                    'count' => $this->getPostTypeCount('drama'),
                    'label' => __('Dramas', 'tmu-theme'),
                    'link' => 'edit.php?post_type=drama',
                    'change' => $this->getWeeklyChange('drama')
                ],
                'people' => [
                    'count' => $this->getPostTypeCount('people'),
                    'label' => __('People', 'tmu-theme'),
                    'link' => 'edit.php?post_type=people',
                    'change' => $this->getWeeklyChange('people')
                ]
            ];
            
            set_transient($cache_key, $stats, self::CACHE_DURATION);
        }
        
        return $stats;
    }
    
    /**
     * Get content breakdown
     * 
     * @return array Breakdown data
     */
    private function getContentBreakdown(): array {
        $cache_key = 'tmu_content_breakdown';
        $breakdown = get_transient($cache_key);
        
        if (false === $breakdown) {
            $total = $this->getTotalContentCount();
            
            $breakdown = [
                'movies' => [
                    'count' => $this->getPostTypeCount('movie'),
                    'label' => __('Movies', 'tmu-theme'),
                    'color' => '#d63638'
                ],
                'tv_shows' => [
                    'count' => $this->getPostTypeCount('tv'),
                    'label' => __('TV Shows', 'tmu-theme'),
                    'color' => '#00a32a'
                ],
                'dramas' => [
                    'count' => $this->getPostTypeCount('drama'),
                    'label' => __('Dramas', 'tmu-theme'),
                    'color' => '#dba617'
                ],
                'people' => [
                    'count' => $this->getPostTypeCount('people'),
                    'label' => __('People', 'tmu-theme'),
                    'color' => '#8b5cf6'
                ]
            ];
            
            // Calculate percentages
            foreach ($breakdown as &$item) {
                $item['percentage'] = $total > 0 ? round(($item['count'] / $total) * 100, 1) : 0;
            }
            
            set_transient($cache_key, $breakdown, self::CACHE_DURATION);
        }
        
        return $breakdown;
    }
    
    /**
     * Get recent activity
     * 
     * @return array Activity data
     */
    private function getRecentActivity(): array {
        $cache_key = 'tmu_recent_activity';
        $activities = get_transient($cache_key);
        
        if (false === $activities) {
            $recent_posts = get_posts([
                'post_type' => ['movie', 'tv', 'drama', 'people'],
                'posts_per_page' => 10,
                'post_status' => ['publish', 'draft'],
                'orderby' => 'modified',
                'order' => 'DESC'
            ]);
            
            $activities = [];
            foreach ($recent_posts as $post) {
                $activities[] = [
                    'title' => $post->post_title,
                    'type' => $post->post_type,
                    'type_label' => ucfirst($post->post_type),
                    'time' => $post->post_modified,
                    'link' => admin_url("post.php?post={$post->ID}&action=edit")
                ];
            }
            
            set_transient($cache_key, $activities, self::CACHE_DURATION);
        }
        
        return $activities;
    }
    
    /**
     * Get TMDB sync status
     * 
     * @return array Sync status data
     */
    private function getTMDBSyncStatus(): array {
        $cache_key = 'tmu_tmdb_sync_status';
        $status = get_transient($cache_key);
        
        if (false === $status) {
            $total_items = $this->getTotalContentCount();
            $synced_items = $this->getTMDBLinkedCount();
            $pending_sync = $total_items - $synced_items;
            $sync_percentage = $total_items > 0 ? round(($synced_items / $total_items) * 100) : 0;
            
            $status = [
                'total_items' => $total_items,
                'synced_items' => $synced_items,
                'pending_sync' => $pending_sync,
                'sync_percentage' => $sync_percentage,
                'last_sync' => get_option('tmu_last_bulk_sync', '')
            ];
            
            set_transient($cache_key, $status, self::CACHE_DURATION);
        }
        
        return $status;
    }
    
    /**
     * Helper methods for data retrieval
     */
    
    /**
     * Get post type count
     * 
     * @param string $post_type Post type
     * @return int Count
     */
    private function getPostTypeCount(string $post_type): int {
        $counts = wp_count_posts($post_type);
        return $counts->publish ?? 0;
    }
    
    /**
     * Get weekly change for post type
     * 
     * @param string $post_type Post type
     * @return int Change count
     */
    private function getWeeklyChange(string $post_type): int {
        $week_ago = date('Y-m-d H:i:s', strtotime('-1 week'));
        
        $query = new \WP_Query([
            'post_type' => $post_type,
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => $week_ago,
                    'inclusive' => true
                ]
            ],
            'fields' => 'ids'
        ]);
        
        return $query->found_posts;
    }
    
    /**
     * Get total content count
     * 
     * @return int Total count
     */
    private function getTotalContentCount(): int {
        $total = 0;
        $post_types = ['movie', 'tv', 'drama', 'people'];
        
        foreach ($post_types as $post_type) {
            $total += $this->getPostTypeCount($post_type);
        }
        
        return $total;
    }
    
    /**
     * Get published content count
     * 
     * @return int Published count
     */
    private function getPublishedContentCount(): int {
        return $this->getTotalContentCount(); // Already counts published only
    }
    
    /**
     * Get draft content count
     * 
     * @return int Draft count
     */
    private function getDraftContentCount(): int {
        global $wpdb;
        
        $post_types = ['movie', 'tv', 'drama', 'people'];
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_type IN ($placeholders) 
             AND post_status = 'draft'",
            $post_types
        ));
    }
    
    /**
     * Get TMDB linked count
     * 
     * @return int TMDB linked count
     */
    private function getTMDBLinkedCount(): int {
        global $wpdb;
        
        $post_types = ['movie', 'tv', 'drama', 'people'];
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type IN ($placeholders)
             AND p.post_status = 'publish'
             AND pm.meta_key = 'tmdb_id'
             AND pm.meta_value != ''",
            $post_types
        ));
    }
    
    /**
     * Get last updated timestamp
     * 
     * @return string Formatted time
     */
    private function getLastUpdated(): string {
        $timestamp = get_option('tmu_stats_last_updated', time());
        return date('M j, Y g:i A', $timestamp);
    }
    
    /**
     * Get stat icon
     * 
     * @param string $stat_key Stat key
     * @return string Icon
     */
    private function getStatIcon(string $stat_key): string {
        $icons = [
            'movies' => '🎬',
            'tv_shows' => '📺',
            'dramas' => '🎭',
            'people' => '👥'
        ];
        
        return $icons[$stat_key] ?? '📊';
    }
    
    /**
     * Get activity icon
     * 
     * @param string $activity_type Activity type
     * @return string Icon
     */
    private function getActivityIcon(string $activity_type): string {
        $icons = [
            'movie' => '🎬',
            'tv' => '📺',
            'drama' => '🎭',
            'people' => '👥'
        ];
        
        return $icons[$activity_type] ?? '📝';
    }
    
    /**
     * Refresh statistics via AJAX
     */
    public function refreshStats(): void {
        check_ajax_referer('tmu_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'tmu-theme')]);
        }
        
        // Clear all stats caches
        $this->clearStatsCache();
        
        // Get fresh stats
        $stats = $this->getQuickStats();
        
        wp_send_json_success([
            'stats' => $stats,
            'message' => __('Statistics refreshed successfully', 'tmu-theme')
        ]);
    }
    
    /**
     * Clear statistics cache
     */
    public function clearStatsCache(): void {
        delete_transient('tmu_quick_stats');
        delete_transient('tmu_content_breakdown');
        delete_transient('tmu_recent_activity');
        delete_transient('tmu_tmdb_sync_status');
        
        update_option('tmu_stats_last_updated', time());
    }
    
    /**
     * Enqueue scripts for stats functionality
     * 
     * @param string $hook_suffix Current admin page
     */
    public function enqueueScripts(string $hook_suffix): void {
        if ($hook_suffix === 'index.php') { // Dashboard
            wp_enqueue_script(
                'tmu-quick-stats',
                get_template_directory_uri() . '/assets/build/js/quick-stats.js',
                ['jquery', 'wp-util'],
                get_theme_file_version('assets/build/js/quick-stats.js'),
                true
            );
            
            wp_localize_script('tmu-quick-stats', 'tmuQuickStats', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('tmu_admin_nonce'),
                'strings' => [
                    'refreshing' => __('Refreshing...', 'tmu-theme'),
                    'refreshed' => __('Statistics refreshed!', 'tmu-theme'),
                    'error' => __('Failed to refresh statistics', 'tmu-theme')
                ]
            ]);
        }
    }
    
    /**
     * Add stats to admin pages
     */
    public function addStatsToAdminPages(): void {
        // Add stats display to relevant admin pages
        add_action('load-edit.php', [$this, 'addStatsToListPages']);
    }
    
    /**
     * Add stats to list pages
     */
    public function addStatsToListPages(): void {
        global $post_type;
        
        if (in_array($post_type, ['movie', 'tv', 'drama', 'people'])) {
            add_action('all_admin_notices', [$this, 'renderListPageStats']);
        }
    }
    
    /**
     * Render stats on list pages
     */
    public function renderListPageStats(): void {
        global $post_type;
        
        $stats = $this->getPostTypeStats($post_type);
        ?>
        <div class="notice notice-info tmu-list-stats">
            <p>
                <strong><?php printf(__('%s Statistics:', 'tmu-theme'), ucfirst($post_type)); ?></strong>
                <?php printf(__('Total: %s', 'tmu-theme'), number_format($stats['total'])); ?> |
                <?php printf(__('Published: %s', 'tmu-theme'), number_format($stats['published'])); ?> |
                <?php printf(__('TMDB Linked: %s', 'tmu-theme'), number_format($stats['tmdb_linked'])); ?> |
                <?php printf(__('This Week: +%s', 'tmu-theme'), number_format($stats['weekly_change'])); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Get post type specific stats
     * 
     * @param string $post_type Post type
     * @return array Stats
     */
    private function getPostTypeStats(string $post_type): array {
        global $wpdb;
        
        $published = $this->getPostTypeCount($post_type);
        
        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
            $post_type
        ));
        
        $tmdb_linked = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = %s
             AND pm.meta_key = 'tmdb_id'
             AND pm.meta_value != ''",
            $post_type
        ));
        
        return [
            'total' => $total,
            'published' => $published,
            'tmdb_linked' => $tmdb_linked,
            'weekly_change' => $this->getWeeklyChange($post_type)
        ];
    }
}