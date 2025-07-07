<?php
/**
 * TMDB Background Sync Jobs
 * 
 * Dedicated background sync job management system for TMDB operations
 * 
 * @package TMU\Cron
 * @since 1.0.0
 */

namespace TMU\Cron;

use TMU\API\TMDB\SyncService;
use TMU\API\TMDB\Client;
use TMU\API\TMDB\Cache;
use TMU\API\TMDB\Exception;

/**
 * TMDBJobs class
 * 
 * Manages background TMDB sync jobs and scheduling
 */
class TMDBJobs {
    
    /**
     * Sync service
     * @var SyncService
     */
    private $sync_service;
    
    /**
     * TMDB client
     * @var Client
     */
    private $client;
    
    /**
     * Cache manager
     * @var Cache
     */
    private $cache;
    
    /**
     * Job queue option name
     */
    const JOB_QUEUE_OPTION = 'tmu_tmdb_job_queue';
    
    /**
     * Job status option prefix
     */
    const JOB_STATUS_PREFIX = 'tmu_tmdb_job_status_';
    
    /**
     * Max jobs per batch
     */
    const MAX_JOBS_PER_BATCH = 10;
    
    /**
     * Job types
     */
    const JOB_TYPE_SYNC = 'sync';
    const JOB_TYPE_IMAGE_SYNC = 'image_sync';
    const JOB_TYPE_BULK_SYNC = 'bulk_sync';
    const JOB_TYPE_CLEANUP = 'cleanup';
    const JOB_TYPE_UPDATE_POPULARITY = 'update_popularity';
    
    /**
     * Initialize the job system
     */
    public function init(): void {
        $this->sync_service = new SyncService();
        $this->client = new Client();
        $this->cache = new Cache();
        
        // Register custom cron schedules
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);
        
        // Register job hooks
        add_action('tmu_tmdb_sync_job', [$this, 'process_sync_job']);
        add_action('tmu_tmdb_image_sync_job', [$this, 'process_image_sync_job']);
        add_action('tmu_tmdb_bulk_sync_job', [$this, 'process_bulk_sync_job']);
        add_action('tmu_tmdb_cleanup_job', [$this, 'process_cleanup_job']);
        add_action('tmu_tmdb_update_popularity_job', [$this, 'process_update_popularity_job']);
        add_action('tmu_tmdb_queue_processor', [$this, 'process_job_queue']);
        
        // Register management hooks
        add_action('init', [$this, 'schedule_jobs']);
        add_action('wp_ajax_tmu_restart_failed_jobs', [$this, 'restart_failed_jobs']);
        add_action('wp_ajax_tmu_clear_job_queue', [$this, 'clear_job_queue']);
    }
    
    /**
     * Add custom cron schedules
     * 
     * @param array $schedules
     * @return array
     */
    public function add_cron_schedules($schedules): array {
        $schedules['tmu_every_15_minutes'] = [
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => __('Every 15 Minutes', 'tmu')
        ];
        
        $schedules['tmu_every_30_minutes'] = [
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display' => __('Every 30 Minutes', 'tmu')
        ];
        
        $schedules['tmu_twice_daily'] = [
            'interval' => 12 * HOUR_IN_SECONDS,
            'display' => __('Twice Daily', 'tmu')
        ];
        
        return $schedules;
    }
    
    /**
     * Schedule TMDB sync jobs
     */
    public function schedule_jobs(): void {
        // Only schedule if auto sync is enabled
        if (!get_option('tmu_tmdb_auto_sync', 0)) {
            return;
        }
        
        // Queue processor (runs frequently to process job queue)
        if (!wp_next_scheduled('tmu_tmdb_queue_processor')) {
            wp_schedule_event(time(), 'tmu_every_15_minutes', 'tmu_tmdb_queue_processor');
        }
        
        // Regular sync job (hourly for recent updates)
        if (!wp_next_scheduled('tmu_tmdb_sync_job')) {
            wp_schedule_event(time(), 'hourly', 'tmu_tmdb_sync_job');
        }
        
        // Image sync job (every 30 minutes)
        if (!wp_next_scheduled('tmu_tmdb_image_sync_job')) {
            wp_schedule_event(time(), 'tmu_every_30_minutes', 'tmu_tmdb_image_sync_job');
        }
        
        // Bulk sync job (twice daily for comprehensive sync)
        if (!wp_next_scheduled('tmu_tmdb_bulk_sync_job')) {
            wp_schedule_event(time(), 'tmu_twice_daily', 'tmu_tmdb_bulk_sync_job');
        }
        
        // Cleanup job (daily)
        if (!wp_next_scheduled('tmu_tmdb_cleanup_job')) {
            wp_schedule_event(time(), 'daily', 'tmu_tmdb_cleanup_job');
        }
        
        // Update popularity scores (weekly)
        if (!wp_next_scheduled('tmu_tmdb_update_popularity_job')) {
            wp_schedule_event(time(), 'weekly', 'tmu_tmdb_update_popularity_job');
        }
    }
    
    /**
     * Process sync job
     */
    public function process_sync_job(): void {
        $this->log_job_start('sync_job');
        
        try {
            // Get recently modified posts with TMDB IDs
            $recent_posts = get_posts([
                'post_type' => ['movie', 'tv', 'drama', 'people'],
                'posts_per_page' => 25,
                'meta_key' => 'tmdb_id',
                'meta_value' => '',
                'meta_compare' => '!=',
                'orderby' => 'modified',
                'order' => 'DESC',
                'date_query' => [
                    [
                        'column' => 'post_modified',
                        'after' => '1 hour ago'
                    ]
                ]
            ]);
            
            $processed = 0;
            $errors = 0;
            
            foreach ($recent_posts as $post) {
                $job_id = $this->add_job_to_queue([
                    'type' => self::JOB_TYPE_SYNC,
                    'post_id' => $post->ID,
                    'post_type' => $post->post_type,
                    'options' => ['sync_images' => false] // Skip images for regular sync
                ]);
                
                $processed++;
                
                // Rate limiting
                sleep(1);
            }
            
            $this->log_job_complete('sync_job', [
                'processed' => $processed,
                'errors' => $errors,
                'posts_found' => count($recent_posts)
            ]);
            
        } catch (Exception $e) {
            $this->log_job_error('sync_job', $e->getMessage());
        }
    }
    
    /**
     * Process image sync job
     */
    public function process_image_sync_job(): void {
        if (!get_option('tmu_tmdb_sync_images', 1)) {
            return;
        }
        
        $this->log_job_start('image_sync_job');
        
        try {
            // Get posts that need image sync
            $posts_needing_images = get_posts([
                'post_type' => ['movie', 'tv', 'drama', 'people'],
                'posts_per_page' => 10,
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => 'tmdb_id',
                        'value' => '',
                        'compare' => '!='
                    ],
                    [
                        'key' => '_thumbnail_id',
                        'compare' => 'NOT EXISTS'
                    ]
                ]
            ]);
            
            $processed = 0;
            
            foreach ($posts_needing_images as $post) {
                $this->add_job_to_queue([
                    'type' => self::JOB_TYPE_IMAGE_SYNC,
                    'post_id' => $post->ID,
                    'post_type' => $post->post_type,
                    'options' => ['sync_images' => true, 'sync_videos' => false]
                ]);
                
                $processed++;
            }
            
            $this->log_job_complete('image_sync_job', [
                'processed' => $processed,
                'posts_found' => count($posts_needing_images)
            ]);
            
        } catch (Exception $e) {
            $this->log_job_error('image_sync_job', $e->getMessage());
        }
    }
    
    /**
     * Process bulk sync job
     */
    public function process_bulk_sync_job(): void {
        $this->log_job_start('bulk_sync_job');
        
        try {
            // Get posts that haven't been synced in the last week
            $stale_posts = get_posts([
                'post_type' => ['movie', 'tv', 'drama', 'people'],
                'posts_per_page' => 50,
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => 'tmdb_id',
                        'value' => '',
                        'compare' => '!='
                    ],
                    [
                        'relation' => 'OR',
                        [
                            'key' => '_tmdb_last_sync',
                            'value' => date('Y-m-d H:i:s', strtotime('-1 week')),
                            'compare' => '<',
                            'type' => 'DATETIME'
                        ],
                        [
                            'key' => '_tmdb_last_sync',
                            'compare' => 'NOT EXISTS'
                        ]
                    ]
                ]
            ]);
            
            $processed = 0;
            
            foreach ($stale_posts as $post) {
                $this->add_job_to_queue([
                    'type' => self::JOB_TYPE_BULK_SYNC,
                    'post_id' => $post->ID,
                    'post_type' => $post->post_type,
                    'options' => [
                        'sync_images' => get_option('tmu_tmdb_sync_images', 1),
                        'sync_videos' => get_option('tmu_tmdb_sync_videos', 1),
                        'sync_credits' => true
                    ]
                ]);
                
                $processed++;
            }
            
            $this->log_job_complete('bulk_sync_job', [
                'processed' => $processed,
                'posts_found' => count($stale_posts)
            ]);
            
        } catch (Exception $e) {
            $this->log_job_error('bulk_sync_job', $e->getMessage());
        }
    }
    
    /**
     * Process cleanup job
     */
    public function process_cleanup_job(): void {
        $this->log_job_start('cleanup_job');
        
        try {
            $cleaned = 0;
            
            // Clear expired cache
            $cache_cleared = $this->cache->clearExpired();
            $cleaned += $cache_cleared;
            
            // Clean up old job statuses (older than 30 days)
            $this->cleanup_old_job_statuses();
            
            // Clean up orphaned TMDB data
            $this->cleanup_orphaned_tmdb_data();
            
            // Optimize database tables
            $this->optimize_tmdb_tables();
            
            $this->log_job_complete('cleanup_job', [
                'cache_cleared' => $cache_cleared,
                'total_cleaned' => $cleaned
            ]);
            
        } catch (Exception $e) {
            $this->log_job_error('cleanup_job', $e->getMessage());
        }
    }
    
    /**
     * Process update popularity job
     */
    public function process_update_popularity_job(): void {
        $this->log_job_start('update_popularity_job');
        
        try {
            // Update popularity scores for popular content
            $popular_posts = get_posts([
                'post_type' => ['movie', 'tv', 'drama'],
                'posts_per_page' => 100,
                'meta_key' => 'tmdb_id',
                'meta_value' => '',
                'meta_compare' => '!=',
                'orderby' => 'meta_value_num',
                'meta_key' => 'tmdb_popularity',
                'order' => 'DESC'
            ]);
            
            $updated = 0;
            
            foreach ($popular_posts as $post) {
                $this->add_job_to_queue([
                    'type' => self::JOB_TYPE_UPDATE_POPULARITY,
                    'post_id' => $post->ID,
                    'post_type' => $post->post_type,
                    'options' => ['update_popularity_only' => true]
                ]);
                
                $updated++;
            }
            
            $this->log_job_complete('update_popularity_job', [
                'posts_updated' => $updated,
                'posts_found' => count($popular_posts)
            ]);
            
        } catch (Exception $e) {
            $this->log_job_error('update_popularity_job', $e->getMessage());
        }
    }
    
    /**
     * Process job queue
     */
    public function process_job_queue(): void {
        $queue = get_option(self::JOB_QUEUE_OPTION, []);
        
        if (empty($queue)) {
            return;
        }
        
        // Process up to MAX_JOBS_PER_BATCH jobs
        $jobs_to_process = array_splice($queue, 0, self::MAX_JOBS_PER_BATCH);
        
        foreach ($jobs_to_process as $job) {
            $this->process_individual_job($job);
            
            // Rate limiting between jobs
            sleep(2);
        }
        
        // Update queue
        update_option(self::JOB_QUEUE_OPTION, $queue);
    }
    
    /**
     * Process individual job
     * 
     * @param array $job
     */
    private function process_individual_job($job): void {
        $job_id = $job['id'] ?? uniqid();
        $start_time = microtime(true);
        
        try {
            $this->update_job_status($job_id, 'processing', [
                'started_at' => current_time('mysql'),
                'job_data' => $job
            ]);
            
            $result = false;
            
            switch ($job['type']) {
                case self::JOB_TYPE_SYNC:
                case self::JOB_TYPE_IMAGE_SYNC:
                case self::JOB_TYPE_BULK_SYNC:
                case self::JOB_TYPE_UPDATE_POPULARITY:
                    $result = $this->execute_sync_job($job);
                    break;
                default:
                    throw new Exception("Unknown job type: {$job['type']}");
            }
            
            $execution_time = microtime(true) - $start_time;
            
            if ($result) {
                $this->update_job_status($job_id, 'completed', [
                    'completed_at' => current_time('mysql'),
                    'execution_time' => $execution_time,
                    'result' => 'success'
                ]);
            } else {
                $this->update_job_status($job_id, 'failed', [
                    'failed_at' => current_time('mysql'),
                    'execution_time' => $execution_time,
                    'error' => 'Sync operation returned false'
                ]);
            }
            
        } catch (Exception $e) {
            $execution_time = microtime(true) - $start_time;
            
            $this->update_job_status($job_id, 'failed', [
                'failed_at' => current_time('mysql'),
                'execution_time' => $execution_time,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            error_log("TMDB Job {$job_id} failed: " . $e->getMessage());
        }
    }
    
    /**
     * Execute sync job
     * 
     * @param array $job
     * @return bool
     */
    private function execute_sync_job($job): bool {
        $post_id = $job['post_id'];
        $post_type = $job['post_type'];
        $options = $job['options'] ?? [];
        
        switch ($post_type) {
            case 'movie':
                return $this->sync_service->sync_movie($post_id, $options);
            case 'tv':
            case 'drama':
                return $this->sync_service->sync_tv_show($post_id, $options);
            case 'people':
                return $this->sync_service->sync_person($post_id, $options);
            default:
                throw new Exception("Unsupported post type: {$post_type}");
        }
    }
    
    /**
     * Add job to queue
     * 
     * @param array $job_data
     * @return string Job ID
     */
    public function add_job_to_queue($job_data): string {
        $job_id = uniqid('tmdb_job_');
        $job = array_merge($job_data, [
            'id' => $job_id,
            'created_at' => current_time('mysql'),
            'priority' => $job_data['priority'] ?? 10
        ]);
        
        $queue = get_option(self::JOB_QUEUE_OPTION, []);
        $queue[] = $job;
        
        // Sort by priority (lower number = higher priority)
        usort($queue, function($a, $b) {
            return ($a['priority'] ?? 10) <=> ($b['priority'] ?? 10);
        });
        
        update_option(self::JOB_QUEUE_OPTION, $queue);
        
        $this->update_job_status($job_id, 'queued', [
            'queued_at' => current_time('mysql'),
            'job_data' => $job
        ]);
        
        return $job_id;
    }
    
    /**
     * Update job status
     * 
     * @param string $job_id
     * @param string $status
     * @param array $data
     */
    private function update_job_status($job_id, $status, $data = []): void {
        $status_data = array_merge($data, [
            'status' => $status,
            'updated_at' => current_time('mysql')
        ]);
        
        update_option(self::JOB_STATUS_PREFIX . $job_id, $status_data);
    }
    
    /**
     * Get job status
     * 
     * @param string $job_id
     * @return array|null
     */
    public function get_job_status($job_id): ?array {
        return get_option(self::JOB_STATUS_PREFIX . $job_id, null);
    }
    
    /**
     * Get queue statistics
     * 
     * @return array
     */
    public function get_queue_statistics(): array {
        $queue = get_option(self::JOB_QUEUE_OPTION, []);
        
        $stats = [
            'total_queued' => count($queue),
            'by_type' => [],
            'by_priority' => [],
            'oldest_job' => null,
            'newest_job' => null
        ];
        
        foreach ($queue as $job) {
            $type = $job['type'] ?? 'unknown';
            $priority = $job['priority'] ?? 10;
            
            $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
            $stats['by_priority'][$priority] = ($stats['by_priority'][$priority] ?? 0) + 1;
            
            if (!$stats['oldest_job'] || $job['created_at'] < $stats['oldest_job']['created_at']) {
                $stats['oldest_job'] = $job;
            }
            
            if (!$stats['newest_job'] || $job['created_at'] > $stats['newest_job']['created_at']) {
                $stats['newest_job'] = $job;
            }
        }
        
        return $stats;
    }
    
    /**
     * Clear job queue
     */
    public function clear_job_queue(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        delete_option(self::JOB_QUEUE_OPTION);
        wp_send_json_success(['message' => 'Job queue cleared successfully']);
    }
    
    /**
     * Restart failed jobs
     */
    public function restart_failed_jobs(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Find failed job statuses
        $failed_jobs = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE %s",
            self::JOB_STATUS_PREFIX . '%'
        ));
        
        $restarted = 0;
        
        foreach ($failed_jobs as $job_option) {
            $status_data = maybe_unserialize($job_option->option_value);
            
            if (is_array($status_data) && $status_data['status'] === 'failed') {
                if (isset($status_data['job_data'])) {
                    $this->add_job_to_queue($status_data['job_data']);
                    $restarted++;
                }
            }
        }
        
        wp_send_json_success([
            'message' => "Restarted {$restarted} failed jobs",
            'restarted' => $restarted
        ]);
    }
    
    /**
     * Clean up old job statuses
     */
    private function cleanup_old_job_statuses(): void {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        $old_statuses = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE %s",
            self::JOB_STATUS_PREFIX . '%'
        ));
        
        $deleted = 0;
        
        foreach ($old_statuses as $status_option) {
            $status_data = maybe_unserialize(get_option($status_option->option_name));
            
            if (is_array($status_data) && 
                isset($status_data['updated_at']) && 
                $status_data['updated_at'] < $cutoff_date) {
                delete_option($status_option->option_name);
                $deleted++;
            }
        }
        
        error_log("TMDB Jobs: Cleaned up {$deleted} old job statuses");
    }
    
    /**
     * Clean up orphaned TMDB data
     */
    private function cleanup_orphaned_tmdb_data(): void {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'tmu_movies',
            $wpdb->prefix . 'tmu_tv_series',
            $wpdb->prefix . 'tmu_dramas',
            $wpdb->prefix . 'tmu_people'
        ];
        
        $total_deleted = 0;
        
        foreach ($tables as $table) {
            $result = $wpdb->query("
                DELETE t FROM {$table} t 
                LEFT JOIN {$wpdb->posts} p ON t.post_id = p.ID 
                WHERE p.ID IS NULL
            ");
            
            if ($result !== false) {
                $total_deleted += $result;
            }
        }
        
        error_log("TMDB Jobs: Cleaned up {$total_deleted} orphaned TMDB records");
    }
    
    /**
     * Optimize TMDB tables
     */
    private function optimize_tmdb_tables(): void {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'tmu_movies',
            $wpdb->prefix . 'tmu_tv_series',
            $wpdb->prefix . 'tmu_dramas',
            $wpdb->prefix . 'tmu_people',
            $wpdb->prefix . 'tmu_videos'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
        
        error_log("TMDB Jobs: Optimized TMDB database tables");
    }
    
    /**
     * Log job start
     * 
     * @param string $job_name
     */
    private function log_job_start($job_name): void {
        error_log("TMDB Job Started: {$job_name} at " . current_time('mysql'));
    }
    
    /**
     * Log job completion
     * 
     * @param string $job_name
     * @param array $stats
     */
    private function log_job_complete($job_name, $stats = []): void {
        $stats_string = !empty($stats) ? ' - ' . json_encode($stats) : '';
        error_log("TMDB Job Completed: {$job_name} at " . current_time('mysql') . $stats_string);
    }
    
    /**
     * Log job error
     * 
     * @param string $job_name
     * @param string $error
     */
    private function log_job_error($job_name, $error): void {
        error_log("TMDB Job Error: {$job_name} - {$error}");
    }
    
    /**
     * Unschedule all TMDB jobs
     */
    public function unschedule_all_jobs(): void {
        $scheduled_hooks = [
            'tmu_tmdb_sync_job',
            'tmu_tmdb_image_sync_job',
            'tmu_tmdb_bulk_sync_job',
            'tmu_tmdb_cleanup_job',
            'tmu_tmdb_update_popularity_job',
            'tmu_tmdb_queue_processor'
        ];
        
        foreach ($scheduled_hooks as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
        }
    }
}