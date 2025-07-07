<?php
namespace TMU\API\TMDB;

use TMU\API\TMDB\SyncService;
use TMU\API\TMDB\Exception as TMDBException;

/**
 * Automated sync scheduler for background TMDB data synchronization
 */
class SyncScheduler {
    private $sync_service;
    
    public function __construct() {
        $this->sync_service = new SyncService();
    }
    
    /**
     * Initialize the sync scheduler
     */
    public function init(): void {
        add_action('init', [$this, 'schedule_sync_events']);
        add_action('tmu_daily_sync', [$this, 'run_daily_sync']);
        add_action('tmu_weekly_sync', [$this, 'run_weekly_sync']);
        add_action('tmu_cleanup_sync', [$this, 'run_cleanup_sync']);
    }
    
    /**
     * Schedule sync events
     */
    public function schedule_sync_events(): void {
        // Daily sync for recent updates
        if (!wp_next_scheduled('tmu_daily_sync')) {
            wp_schedule_event(time(), 'daily', 'tmu_daily_sync');
        }
        
        // Weekly sync for complete data refresh
        if (!wp_next_scheduled('tmu_weekly_sync')) {
            wp_schedule_event(time(), 'weekly', 'tmu_weekly_sync');
        }
        
        // Monthly cleanup
        if (!wp_next_scheduled('tmu_cleanup_sync')) {
            wp_schedule_event(time(), 'monthly', 'tmu_cleanup_sync');
        }
    }
    
    /**
     * Run daily sync for recent updates
     */
    public function run_daily_sync(): void {
        if (!get_option('tmu_tmdb_auto_sync', 0)) {
            return;
        }
        
        $this->log_sync_start('daily');
        
        try {
            $this->sync_recent_updates();
            $this->clean_expired_cache();
            $this->log_sync_complete('daily');
        } catch (TMDBException $e) {
            $this->log_sync_error('daily', $e->getMessage());
        }
    }
    
    /**
     * Run weekly sync for complete data refresh
     */
    public function run_weekly_sync(): void {
        if (!get_option('tmu_tmdb_auto_sync', 0)) {
            return;
        }
        
        $this->log_sync_start('weekly');
        
        try {
            $this->sync_all_content();
            $this->update_popularity_scores();
            $this->log_sync_complete('weekly');
        } catch (TMDBException $e) {
            $this->log_sync_error('weekly', $e->getMessage());
        }
    }
    
    /**
     * Run cleanup sync for maintenance
     */
    public function run_cleanup_sync(): void {
        $this->log_sync_start('cleanup');
        
        try {
            $this->cleanup_orphaned_data();
            $this->optimize_database();
            $this->log_sync_complete('cleanup');
        } catch (TMDBException $e) {
            $this->log_sync_error('cleanup', $e->getMessage());
        }
    }
    
    /**
     * Sync recent updates (last 7 days)
     */
    private function sync_recent_updates(): void {
        $recent_posts = get_posts([
            'post_type' => ['movie', 'tv', 'drama'],
            'posts_per_page' => 50,
            'meta_key' => 'tmdb_id',
            'meta_value' => '',
            'meta_compare' => '!=',
            'date_query' => [
                [
                    'after' => '7 days ago'
                ]
            ],
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);
        
        $processed = 0;
        $errors = 0;
        
        foreach ($recent_posts as $post) {
            try {
                $options = [
                    'sync_images' => false, // Skip images for daily sync
                    'sync_credits' => true,
                    'sync_videos' => false
                ];
                
                $result = $this->sync_post_by_type($post, $options);
                
                if ($result) {
                    $processed++;
                } else {
                    $errors++;
                }
                
                // Prevent timeout and respect rate limits
                if ($processed % 10 === 0) {
                    sleep(2);
                }
                
            } catch (TMDBException $e) {
                $errors++;
                error_log("Daily sync error for post {$post->ID}: " . $e->getMessage());
            }
        }
        
        update_option('tmu_last_daily_sync', [
            'date' => current_time('mysql'),
            'processed' => $processed,
            'errors' => $errors
        ]);
    }
    
    /**
     * Sync all content (weekly full refresh)
     */
    private function sync_all_content(): void {
        $post_types = ['movie', 'tv'];
        if (get_option('tmu_dramas') === 'on') {
            $post_types[] = 'drama';
        }
        
        $total_processed = 0;
        $total_errors = 0;
        
        foreach ($post_types as $post_type) {
            $posts = get_posts([
                'post_type' => $post_type,
                'posts_per_page' => 100, // Process in batches
                'meta_key' => 'tmdb_id',
                'meta_value' => '',
                'meta_compare' => '!=',
                'orderby' => 'modified',
                'order' => 'ASC' // Start with oldest
            ]);
            
            $processed = 0;
            $errors = 0;
            
            foreach ($posts as $post) {
                try {
                    $options = [
                        'sync_images' => get_option('tmu_tmdb_sync_images', 1),
                        'sync_credits' => true,
                        'sync_videos' => get_option('tmu_tmdb_sync_videos', 1)
                    ];
                    
                    $result = $this->sync_post_by_type($post, $options);
                    
                    if ($result) {
                        $processed++;
                    } else {
                        $errors++;
                    }
                    
                    // Prevent timeout and respect rate limits
                    if ($processed % 5 === 0) {
                        sleep(3);
                    }
                    
                } catch (TMDBException $e) {
                    $errors++;
                    error_log("Weekly sync error for post {$post->ID}: " . $e->getMessage());
                }
            }
            
            $total_processed += $processed;
            $total_errors += $errors;
        }
        
        update_option('tmu_last_weekly_sync', [
            'date' => current_time('mysql'),
            'processed' => $total_processed,
            'errors' => $total_errors
        ]);
    }
    
    /**
     * Sync a post by its type
     */
    private function sync_post_by_type($post, $options): bool {
        switch ($post->post_type) {
            case 'movie':
                return $this->sync_service->sync_movie($post->ID, $options);
            case 'tv':
                return $this->sync_service->sync_tv_show($post->ID, $options);
            case 'drama':
                return $this->sync_service->sync_drama($post->ID, $options);
            default:
                return false;
        }
    }
    
    /**
     * Update popularity scores for all content
     */
    private function update_popularity_scores(): void {
        global $wpdb;
        
        // Update movie popularity rankings
        $wpdb->query("
            UPDATE {$wpdb->prefix}tmu_movies 
            SET popularity_rank = (
                SELECT COUNT(*) + 1 
                FROM {$wpdb->prefix}tmu_movies AS m2 
                WHERE m2.popularity > {$wpdb->prefix}tmu_movies.popularity
            )
        ");
        
        // Update TV show popularity rankings
        $wpdb->query("
            UPDATE {$wpdb->prefix}tmu_tv_series 
            SET popularity_rank = (
                SELECT COUNT(*) + 1 
                FROM {$wpdb->prefix}tmu_tv_series AS t2 
                WHERE t2.popularity > {$wpdb->prefix}tmu_tv_series.popularity
            )
        ");
        
        // Update drama popularity rankings if enabled
        if (get_option('tmu_dramas') === 'on') {
            $wpdb->query("
                UPDATE {$wpdb->prefix}tmu_dramas 
                SET popularity_rank = (
                    SELECT COUNT(*) + 1 
                    FROM {$wpdb->prefix}tmu_dramas AS d2 
                    WHERE d2.popularity > {$wpdb->prefix}tmu_dramas.popularity
                )
            ");
        }
    }
    
    /**
     * Clean expired cache entries
     */
    private function clean_expired_cache(): void {
        global $wpdb;
        
        $expired_transients = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_tmdb_%' 
             AND option_value < UNIX_TIMESTAMP()"
        );
        
        foreach ($expired_transients as $transient) {
            $transient_name = str_replace('_transient_timeout_', '', $transient->option_name);
            delete_transient($transient_name);
        }
    }
    
    /**
     * Cleanup orphaned data
     */
    private function cleanup_orphaned_data(): void {
        global $wpdb;
        
        // Remove TMDB data for deleted posts
        $tables = [
            'tmu_movies',
            'tmu_tv_series',
            'tmu_dramas',
            'tmu_people',
            'tmu_videos'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("
                DELETE FROM {$wpdb->prefix}{$table} 
                WHERE post_id NOT IN (
                    SELECT ID FROM {$wpdb->posts} 
                    WHERE post_status = 'publish'
                )
            ");
        }
    }
    
    /**
     * Optimize database tables
     */
    private function optimize_database(): void {
        global $wpdb;
        
        $tables = [
            'tmu_movies',
            'tmu_tv_series',
            'tmu_dramas',
            'tmu_people',
            'tmu_videos'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}{$table}");
        }
    }
    
    /**
     * Log sync start
     */
    private function log_sync_start(string $type): void {
        error_log("TMU TMDB {$type} sync started at " . current_time('mysql'));
    }
    
    /**
     * Log sync completion
     */
    private function log_sync_complete(string $type): void {
        error_log("TMU TMDB {$type} sync completed at " . current_time('mysql'));
    }
    
    /**
     * Log sync error
     */
    private function log_sync_error(string $type, string $message): void {
        error_log("TMU TMDB {$type} sync error: " . $message);
    }
    
    /**
     * Clear all scheduled events
     */
    public function clear_scheduled_events(): void {
        wp_clear_scheduled_hook('tmu_daily_sync');
        wp_clear_scheduled_hook('tmu_weekly_sync');
        wp_clear_scheduled_hook('tmu_cleanup_sync');
    }
    
    /**
     * Get sync status
     */
    public function get_sync_status(): array {
        return [
            'daily' => get_option('tmu_last_daily_sync', []),
            'weekly' => get_option('tmu_last_weekly_sync', []),
            'auto_sync_enabled' => get_option('tmu_tmdb_auto_sync', 0),
            'next_daily' => wp_next_scheduled('tmu_daily_sync'),
            'next_weekly' => wp_next_scheduled('tmu_weekly_sync')
        ];
    }
}