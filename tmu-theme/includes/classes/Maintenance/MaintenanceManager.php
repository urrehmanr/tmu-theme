<?php
/**
 * Maintenance Manager
 * 
 * Central coordinator for all TMU theme maintenance activities.
 * 
 * @package TMU\Maintenance
 * @since 1.0.0
 */

namespace TMU\Maintenance;

use TMU\Backup\BackupManager;
use TMU\Monitoring\PerformanceMonitor;
use TMU\Analytics\AnalyticsManager;
use TMU\Logging\LogManager;

class MaintenanceManager {
    
    /**
     * Backup manager instance
     * @var BackupManager
     */
    private $backup_manager;
    
    /**
     * Performance monitor instance
     * @var PerformanceMonitor
     */
    private $performance_monitor;
    
    /**
     * Analytics manager instance
     * @var AnalyticsManager
     */
    private $analytics_manager;
    
    /**
     * Log manager instance
     * @var LogManager
     */
    private $logger;
    
    /**
     * Maintenance status
     * @var bool
     */
    private $maintenance_mode = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->backup_manager = new BackupManager();
        $this->performance_monitor = new PerformanceMonitor();
        $this->analytics_manager = new AnalyticsManager();
        $this->logger = new LogManager();
        
        add_action('init', [$this, 'init_maintenance_system']);
        add_action('wp_ajax_tmu_run_maintenance', [$this, 'run_manual_maintenance']);
        add_action('wp_ajax_tmu_toggle_maintenance_mode', [$this, 'toggle_maintenance_mode']);
        add_action('wp_ajax_tmu_maintenance_status', [$this, 'get_maintenance_status']);
    }
    
    /**
     * Initialize maintenance system
     */
    public function init_maintenance_system(): void {
        // Check if maintenance mode is enabled
        $this->maintenance_mode = get_option('tmu_maintenance_mode', false);
        
        // Register maintenance hooks
        add_action('tmu_daily_maintenance', [$this, 'run_daily_maintenance']);
        add_action('tmu_weekly_maintenance', [$this, 'run_weekly_maintenance']);
        add_action('tmu_monthly_maintenance', [$this, 'run_monthly_maintenance']);
        
        // Schedule maintenance tasks if not already scheduled
        $this->schedule_maintenance_tasks();
        
        // Handle maintenance mode
        if ($this->maintenance_mode) {
            add_action('template_redirect', [$this, 'handle_maintenance_mode']);
        }
        
        // Register admin hooks
        add_action('admin_menu', [$this, 'add_maintenance_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_maintenance_assets']);
    }
    
    /**
     * Schedule maintenance tasks
     */
    public function schedule_maintenance_tasks(): void {
        // Schedule daily maintenance at 3 AM
        if (!wp_next_scheduled('tmu_daily_maintenance')) {
            $tomorrow_3am = strtotime('tomorrow 3:00 AM');
            wp_schedule_event($tomorrow_3am, 'daily', 'tmu_daily_maintenance');
        }
        
        // Schedule weekly maintenance on Sunday at 2 AM
        if (!wp_next_scheduled('tmu_weekly_maintenance')) {
            $next_sunday = strtotime('next Sunday 2:00 AM');
            wp_schedule_event($next_sunday, 'weekly', 'tmu_weekly_maintenance');
        }
        
        // Schedule monthly maintenance on the 1st at 1 AM
        if (!wp_next_scheduled('tmu_monthly_maintenance')) {
            $first_of_month = strtotime('first day of next month 1:00 AM');
            wp_schedule_event($first_of_month, 'monthly', 'tmu_monthly_maintenance');
        }
    }
    
    /**
     * Run daily maintenance
     */
    public function run_daily_maintenance(): void {
        $this->logger->info('Starting daily maintenance');
        
        try {
            $start_time = microtime(true);
            $tasks_completed = 0;
            $tasks_failed = 0;
            
            // Task 1: Clean up temporary files
            if ($this->cleanup_temporary_files()) {
                $tasks_completed++;
                $this->logger->info('Daily maintenance: Temporary files cleaned');
            } else {
                $tasks_failed++;
                $this->logger->warning('Daily maintenance: Failed to clean temporary files');
            }
            
            // Task 2: Optimize database tables
            if ($this->optimize_database_tables()) {
                $tasks_completed++;
                $this->logger->info('Daily maintenance: Database tables optimized');
            } else {
                $tasks_failed++;
                $this->logger->warning('Daily maintenance: Failed to optimize database tables');
            }
            
            // Task 3: Clean expired cache
            if ($this->clean_expired_cache()) {
                $tasks_completed++;
                $this->logger->info('Daily maintenance: Expired cache cleaned');
            } else {
                $tasks_failed++;
                $this->logger->warning('Daily maintenance: Failed to clean expired cache');
            }
            
            // Task 4: Update popular content data
            if ($this->update_popular_content_data()) {
                $tasks_completed++;
                $this->logger->info('Daily maintenance: Popular content data updated');
            } else {
                $tasks_failed++;
                $this->logger->warning('Daily maintenance: Failed to update popular content data');
            }
            
            // Task 5: Generate daily performance report
            if ($this->generate_daily_performance_report()) {
                $tasks_completed++;
                $this->logger->info('Daily maintenance: Performance report generated');
            } else {
                $tasks_failed++;
                $this->logger->warning('Daily maintenance: Failed to generate performance report');
            }
            
            // Task 6: Create daily backup
            if ($this->create_daily_backup()) {
                $tasks_completed++;
                $this->logger->info('Daily maintenance: Backup created');
            } else {
                $tasks_failed++;
                $this->logger->warning('Daily maintenance: Failed to create backup');
            }
            
            $execution_time = microtime(true) - $start_time;
            
            // Log completion
            $this->logger->info('Daily maintenance completed', [
                'tasks_completed' => $tasks_completed,
                'tasks_failed' => $tasks_failed,
                'execution_time' => $execution_time
            ]);
            
            // Update maintenance statistics
            $this->update_maintenance_statistics('daily', $tasks_completed, $tasks_failed, $execution_time);
            
        } catch (Exception $e) {
            $this->logger->error('Daily maintenance failed', ['error' => $e->getMessage()]);
            $this->send_maintenance_alert('Daily maintenance failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Run weekly maintenance
     */
    public function run_weekly_maintenance(): void {
        $this->logger->info('Starting weekly maintenance');
        
        try {
            $start_time = microtime(true);
            $tasks_completed = 0;
            $tasks_failed = 0;
            
            // Task 1: Deep database optimization
            if ($this->deep_database_optimization()) {
                $tasks_completed++;
                $this->logger->info('Weekly maintenance: Deep database optimization completed');
            } else {
                $tasks_failed++;
                $this->logger->warning('Weekly maintenance: Failed deep database optimization');
            }
            
            // Task 2: Update all TMDB data
            if ($this->update_all_tmdb_data()) {
                $tasks_completed++;
                $this->logger->info('Weekly maintenance: TMDB data updated');
            } else {
                $tasks_failed++;
                $this->logger->warning('Weekly maintenance: Failed to update TMDB data');
            }
            
            // Task 3: Clean up old analytics data
            if ($this->cleanup_old_analytics_data()) {
                $tasks_completed++;
                $this->logger->info('Weekly maintenance: Old analytics data cleaned');
            } else {
                $tasks_failed++;
                $this->logger->warning('Weekly maintenance: Failed to clean analytics data');
            }
            
            // Task 4: Generate weekly reports
            if ($this->generate_weekly_reports()) {
                $tasks_completed++;
                $this->logger->info('Weekly maintenance: Reports generated');
            } else {
                $tasks_failed++;
                $this->logger->warning('Weekly maintenance: Failed to generate reports');
            }
            
            // Task 5: Check for theme updates
            if ($this->check_for_updates()) {
                $tasks_completed++;
                $this->logger->info('Weekly maintenance: Update check completed');
            } else {
                $tasks_failed++;
                $this->logger->warning('Weekly maintenance: Failed to check for updates');
            }
            
            // Task 6: Create weekly backup
            if ($this->create_weekly_backup()) {
                $tasks_completed++;
                $this->logger->info('Weekly maintenance: Backup created');
            } else {
                $tasks_failed++;
                $this->logger->warning('Weekly maintenance: Failed to create backup');
            }
            
            $execution_time = microtime(true) - $start_time;
            
            // Log completion
            $this->logger->info('Weekly maintenance completed', [
                'tasks_completed' => $tasks_completed,
                'tasks_failed' => $tasks_failed,
                'execution_time' => $execution_time
            ]);
            
            // Update maintenance statistics
            $this->update_maintenance_statistics('weekly', $tasks_completed, $tasks_failed, $execution_time);
            
        } catch (Exception $e) {
            $this->logger->error('Weekly maintenance failed', ['error' => $e->getMessage()]);
            $this->send_maintenance_alert('Weekly maintenance failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Run monthly maintenance
     */
    public function run_monthly_maintenance(): void {
        $this->logger->info('Starting monthly maintenance');
        
        try {
            $start_time = microtime(true);
            $tasks_completed = 0;
            $tasks_failed = 0;
            
            // Task 1: Archive old data
            if ($this->archive_old_data()) {
                $tasks_completed++;
                $this->logger->info('Monthly maintenance: Old data archived');
            } else {
                $tasks_failed++;
                $this->logger->warning('Monthly maintenance: Failed to archive old data');
            }
            
            // Task 2: Run security audit
            if ($this->run_security_audit()) {
                $tasks_completed++;
                $this->logger->info('Monthly maintenance: Security audit completed');
            } else {
                $tasks_failed++;
                $this->logger->warning('Monthly maintenance: Failed security audit');
            }
            
            // Task 3: Run performance audit
            if ($this->run_performance_audit()) {
                $tasks_completed++;
                $this->logger->info('Monthly maintenance: Performance audit completed');
            } else {
                $tasks_failed++;
                $this->logger->warning('Monthly maintenance: Failed performance audit');
            }
            
            // Task 4: Generate monthly reports
            if ($this->generate_monthly_reports()) {
                $tasks_completed++;
                $this->logger->info('Monthly maintenance: Monthly reports generated');
            } else {
                $tasks_failed++;
                $this->logger->warning('Monthly maintenance: Failed to generate monthly reports');
            }
            
            // Task 5: Create monthly backup
            if ($this->create_monthly_backup()) {
                $tasks_completed++;
                $this->logger->info('Monthly maintenance: Monthly backup created');
            } else {
                $tasks_failed++;
                $this->logger->warning('Monthly maintenance: Failed to create monthly backup');
            }
            
            $execution_time = microtime(true) - $start_time;
            
            // Log completion
            $this->logger->info('Monthly maintenance completed', [
                'tasks_completed' => $tasks_completed,
                'tasks_failed' => $tasks_failed,
                'execution_time' => $execution_time
            ]);
            
            // Update maintenance statistics
            $this->update_maintenance_statistics('monthly', $tasks_completed, $tasks_failed, $execution_time);
            
        } catch (Exception $e) {
            $this->logger->error('Monthly maintenance failed', ['error' => $e->getMessage()]);
            $this->send_maintenance_alert('Monthly maintenance failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Clean up temporary files
     */
    private function cleanup_temporary_files(): bool {
        try {
            $temp_dirs = [
                get_template_directory() . '/temp/',
                WP_CONTENT_DIR . '/uploads/tmu-temp/',
                sys_get_temp_dir() . '/tmu-*'
            ];
            
            $cleaned_files = 0;
            $cutoff_time = time() - (24 * 60 * 60); // 24 hours ago
            
            foreach ($temp_dirs as $temp_dir) {
                if (strpos($temp_dir, '*') !== false) {
                    // Handle glob patterns
                    $files = glob($temp_dir);
                } else {
                    if (!is_dir($temp_dir)) {
                        continue;
                    }
                    $files = glob($temp_dir . '*');
                }
                
                foreach ($files as $file) {
                    if (filemtime($file) < $cutoff_time) {
                        if (is_file($file)) {
                            unlink($file);
                            $cleaned_files++;
                        } elseif (is_dir($file)) {
                            $this->remove_directory_recursive($file);
                            $cleaned_files++;
                        }
                    }
                }
            }
            
            $this->logger->debug('Temporary files cleanup completed', ['files_cleaned' => $cleaned_files]);
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to clean temporary files', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Optimize database tables
     */
    private function optimize_database_tables(): bool {
        try {
            global $wpdb;
            
            $tables = [
                $wpdb->prefix . 'tmu_movies',
                $wpdb->prefix . 'tmu_tv_series',
                $wpdb->prefix . 'tmu_dramas',
                $wpdb->prefix . 'tmu_people',
                $wpdb->prefix . 'tmu_analytics_events'
            ];
            
            foreach ($tables as $table) {
                $wpdb->query("OPTIMIZE TABLE {$table}");
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to optimize database tables', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Clean expired cache
     */
    private function clean_expired_cache(): bool {
        try {
            // Clear WordPress object cache
            wp_cache_flush();
            
            // Clear expired transients
            global $wpdb;
            $wpdb->query("
                DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_timeout_%' 
                AND option_value < UNIX_TIMESTAMP()
            ");
            
            // Clear TMU-specific cache
            $cache_keys = [
                'tmu_movie_cache_*',
                'tmu_tv_cache_*',
                'tmu_people_cache_*',
                'tmu_tmdb_cache_*'
            ];
            
            foreach ($cache_keys as $pattern) {
                $keys = $wpdb->get_col($wpdb->prepare(
                    "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $pattern
                ));
                
                foreach ($keys as $key) {
                    delete_option($key);
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to clean expired cache', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Update popular content data
     */
    private function update_popular_content_data(): bool {
        try {
            global $wpdb;
            
            // Get most viewed content from last 7 days
            $popular_content = $wpdb->get_results(
                "SELECT content_id, COUNT(*) as views 
                 FROM {$wpdb->prefix}tmu_analytics_events 
                 WHERE event_type = 'page_view' 
                 AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY content_id 
                 ORDER BY views DESC 
                 LIMIT 50"
            );
            
            foreach ($popular_content as $content) {
                // Trigger TMDB update for popular content
                do_action('tmu_update_tmdb_data', $content->content_id);
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to update popular content data', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Generate daily performance report
     */
    private function generate_daily_performance_report(): bool {
        try {
            $report_data = $this->performance_monitor->get_performance_summary(24);
            
            $report = [
                'date' => current_time('Y-m-d'),
                'type' => 'daily',
                'data' => $report_data,
                'generated_at' => current_time('mysql')
            ];
            
            // Save report
            $reports = get_option('tmu_daily_reports', []);
            $reports[current_time('Y-m-d')] = $report;
            
            // Keep only last 30 days
            $reports = array_slice($reports, -30, null, true);
            update_option('tmu_daily_reports', $reports);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to generate daily performance report', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Create daily backup
     */
    private function create_daily_backup(): bool {
        try {
            $this->backup_manager->create_daily_backup();
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to create daily backup', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Deep database optimization
     */
    private function deep_database_optimization(): bool {
        try {
            global $wpdb;
            
            // Analyze and optimize all TMU tables
            $tables = [
                $wpdb->prefix . 'tmu_movies',
                $wpdb->prefix . 'tmu_tv_series',
                $wpdb->prefix . 'tmu_dramas',
                $wpdb->prefix . 'tmu_people',
                $wpdb->prefix . 'tmu_analytics_events',
                $wpdb->prefix . 'tmu_performance_logs',
                $wpdb->prefix . 'tmu_error_logs'
            ];
            
            foreach ($tables as $table) {
                $wpdb->query("ANALYZE TABLE {$table}");
                $wpdb->query("OPTIMIZE TABLE {$table}");
            }
            
            // Clean up orphaned data
            $this->cleanup_orphaned_data();
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed deep database optimization', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Cleanup orphaned data
     */
    private function cleanup_orphaned_data(): void {
        global $wpdb;
        
        // Remove orphaned movie data
        $wpdb->query("
            DELETE m FROM {$wpdb->prefix}tmu_movies m
            LEFT JOIN {$wpdb->posts} p ON m.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        // Remove orphaned TV series data
        $wpdb->query("
            DELETE t FROM {$wpdb->prefix}tmu_tv_series t
            LEFT JOIN {$wpdb->posts} p ON t.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        // Remove orphaned drama data
        $wpdb->query("
            DELETE d FROM {$wpdb->prefix}tmu_dramas d
            LEFT JOIN {$wpdb->posts} p ON d.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        // Remove orphaned people data
        $wpdb->query("
            DELETE pe FROM {$wpdb->prefix}tmu_people pe
            LEFT JOIN {$wpdb->posts} p ON pe.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        // Clean up old analytics events (older than 1 year)
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}tmu_analytics_events
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ");
        
        // Clean up old performance logs (older than 3 months)
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}tmu_performance_logs
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 3 MONTH)
        ");
        
        // Clean up old error logs (older than 6 months)
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}tmu_error_logs
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 6 MONTH)
        ");
    }
    
    /**
     * Update all TMDB data
     */
    private function update_all_tmdb_data(): bool {
        try {
            // Trigger comprehensive TMDB sync
            do_action('tmu_tmdb_bulk_sync_job');
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to update TMDB data', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Cleanup old analytics data
     */
    private function cleanup_old_analytics_data(): bool {
        try {
            global $wpdb;
            
            // Remove analytics events older than specified retention period
            $retention_days = get_option('tmu_analytics_retention_days', 365);
            
            $deleted = $wpdb->query($wpdb->prepare("
                DELETE FROM {$wpdb->prefix}tmu_analytics_events
                WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)
            ", $retention_days));
            
            $this->logger->debug('Analytics data cleanup completed', ['deleted_events' => $deleted]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to cleanup analytics data', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Generate weekly reports
     */
    private function generate_weekly_reports(): bool {
        try {
            // Generate comprehensive weekly report
            $report_data = [
                'performance' => $this->performance_monitor->get_performance_summary(168), // 7 days
                'analytics' => $this->analytics_manager->generate_analytics_report(),
                'errors' => $this->get_weekly_error_summary(),
                'backups' => $this->backup_manager->get_backup_statistics()
            ];
            
            $report = [
                'week' => date('Y-W'),
                'type' => 'weekly',
                'data' => $report_data,
                'generated_at' => current_time('mysql')
            ];
            
            // Save report
            $reports = get_option('tmu_weekly_reports', []);
            $reports[date('Y-W')] = $report;
            
            // Keep only last 12 weeks
            $reports = array_slice($reports, -12, null, true);
            update_option('tmu_weekly_reports', $reports);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to generate weekly reports', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Get weekly error summary
     */
    private function get_weekly_error_summary(): array {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT error_type, COUNT(*) as count
             FROM {$wpdb->prefix}tmu_error_logs 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY error_type
             ORDER BY count DESC"
        );
    }
    
    /**
     * Check for updates
     */
    private function check_for_updates(): bool {
        try {
            // This will be implemented by UpdateManager
            do_action('tmu_check_for_updates');
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to check for updates', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Create weekly backup
     */
    private function create_weekly_backup(): bool {
        try {
            $this->backup_manager->create_weekly_backup();
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to create weekly backup', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Archive old data
     */
    private function archive_old_data(): bool {
        try {
            // Archive data older than 2 years
            $this->archive_old_analytics_data();
            $this->archive_old_performance_data();
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to archive old data', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Archive old analytics data
     */
    private function archive_old_analytics_data(): void {
        global $wpdb;
        
        // Move old analytics data to archive table
        $archive_table = $wpdb->prefix . 'tmu_analytics_events_archive';
        
        // Create archive table if it doesn't exist
        $wpdb->query("
            CREATE TABLE IF NOT EXISTS {$archive_table} LIKE {$wpdb->prefix}tmu_analytics_events
        ");
        
        // Move old data
        $wpdb->query("
            INSERT INTO {$archive_table} 
            SELECT * FROM {$wpdb->prefix}tmu_analytics_events 
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 2 YEAR)
        ");
        
        // Delete old data from main table
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}tmu_analytics_events 
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 2 YEAR)
        ");
    }
    
    /**
     * Archive old performance data
     */
    private function archive_old_performance_data(): void {
        global $wpdb;
        
        // Archive performance logs older than 1 year
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}tmu_performance_logs 
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ");
    }
    
    /**
     * Run security audit
     */
    private function run_security_audit(): bool {
        try {
            // This will be implemented by SecurityAuditor
            do_action('tmu_security_audit');
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to run security audit', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Run performance audit
     */
    private function run_performance_audit(): bool {
        try {
            // This will be implemented by PerformanceOptimizer
            do_action('tmu_performance_optimization');
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to run performance audit', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Generate monthly reports
     */
    private function generate_monthly_reports(): bool {
        try {
            // Generate comprehensive monthly report
            $report_data = [
                'performance' => $this->performance_monitor->get_performance_summary(720), // 30 days
                'analytics' => $this->analytics_manager->generate_analytics_report(),
                'security' => $this->get_monthly_security_summary(),
                'maintenance' => $this->get_monthly_maintenance_summary(),
                'backups' => $this->backup_manager->get_backup_statistics()
            ];
            
            $report = [
                'month' => date('Y-m'),
                'type' => 'monthly',
                'data' => $report_data,
                'generated_at' => current_time('mysql')
            ];
            
            // Save report
            $reports = get_option('tmu_monthly_reports', []);
            $reports[date('Y-m')] = $report;
            
            // Keep only last 12 months
            $reports = array_slice($reports, -12, null, true);
            update_option('tmu_monthly_reports', $reports);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to generate monthly reports', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Get monthly security summary
     */
    private function get_monthly_security_summary(): array {
        // This will be populated by SecurityAuditor
        return get_option('tmu_monthly_security_summary', []);
    }
    
    /**
     * Get monthly maintenance summary
     */
    private function get_monthly_maintenance_summary(): array {
        $stats = get_option('tmu_maintenance_statistics', []);
        
        $monthly_stats = [
            'daily_tasks' => 0,
            'weekly_tasks' => 0,
            'monthly_tasks' => 0,
            'total_tasks' => 0,
            'success_rate' => 0
        ];
        
        $current_month = date('Y-m');
        
        foreach ($stats as $date => $stat) {
            if (strpos($date, $current_month) === 0) {
                $monthly_stats['total_tasks'] += $stat['tasks_completed'] + $stat['tasks_failed'];
                
                if ($stat['type'] === 'daily') {
                    $monthly_stats['daily_tasks']++;
                } elseif ($stat['type'] === 'weekly') {
                    $monthly_stats['weekly_tasks']++;
                } elseif ($stat['type'] === 'monthly') {
                    $monthly_stats['monthly_tasks']++;
                }
            }
        }
        
        return $monthly_stats;
    }
    
    /**
     * Create monthly backup
     */
    private function create_monthly_backup(): bool {
        try {
            $this->backup_manager->create_monthly_backup();
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to create monthly backup', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Update maintenance statistics
     */
    private function update_maintenance_statistics($type, $completed, $failed, $execution_time): void {
        $stats = get_option('tmu_maintenance_statistics', []);
        
        $stats[current_time('Y-m-d H:i:s')] = [
            'type' => $type,
            'tasks_completed' => $completed,
            'tasks_failed' => $failed,
            'execution_time' => $execution_time,
            'success_rate' => $completed / ($completed + $failed) * 100
        ];
        
        // Keep only last 90 days
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-90 days'));
        $stats = array_filter($stats, function($key) use ($cutoff_date) {
            return $key >= $cutoff_date;
        }, ARRAY_FILTER_USE_KEY);
        
        update_option('tmu_maintenance_statistics', $stats);
    }
    
    /**
     * Send maintenance alert
     */
    private function send_maintenance_alert($message): void {
        $alert_config = get_option('tmu_maintenance_alerts', []);
        
        if (!empty($alert_config['email'])) {
            wp_mail(
                $alert_config['email'],
                'TMU Maintenance Alert',
                $message,
                ['Content-Type: text/html; charset=UTF-8']
            );
        }
        
        if (!empty($alert_config['webhook_url'])) {
            wp_remote_post($alert_config['webhook_url'], [
                'body' => json_encode([
                    'type' => 'maintenance_alert',
                    'message' => $message,
                    'site' => get_site_url(),
                    'timestamp' => current_time('c')
                ]),
                'headers' => ['Content-Type' => 'application/json'],
                'timeout' => 10,
                'blocking' => false
            ]);
        }
    }
    
    /**
     * Remove directory recursively
     */
    private function remove_directory_recursive($dir): void {
        if (!is_dir($dir)) {
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Run manual maintenance via AJAX
     */
    public function run_manual_maintenance(): void {
        check_ajax_referer('tmu_maintenance_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $type = sanitize_text_field($_POST['type'] ?? 'daily');
        
        try {
            switch ($type) {
                case 'daily':
                    $this->run_daily_maintenance();
                    break;
                case 'weekly':
                    $this->run_weekly_maintenance();
                    break;
                case 'monthly':
                    $this->run_monthly_maintenance();
                    break;
                default:
                    throw new Exception('Invalid maintenance type');
            }
            
            wp_send_json_success('Maintenance completed successfully');
            
        } catch (Exception $e) {
            wp_send_json_error('Maintenance failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Toggle maintenance mode
     */
    public function toggle_maintenance_mode(): void {
        check_ajax_referer('tmu_maintenance_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $this->maintenance_mode = !$this->maintenance_mode;
        update_option('tmu_maintenance_mode', $this->maintenance_mode);
        
        wp_send_json_success([
            'maintenance_mode' => $this->maintenance_mode,
            'message' => $this->maintenance_mode ? 'Maintenance mode enabled' : 'Maintenance mode disabled'
        ]);
    }
    
    /**
     * Get maintenance status
     */
    public function get_maintenance_status(): void {
        check_ajax_referer('tmu_maintenance_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $stats = get_option('tmu_maintenance_statistics', []);
        $latest_stats = end($stats);
        
        $status = [
            'maintenance_mode' => $this->maintenance_mode,
            'last_maintenance' => $latest_stats['type'] ?? 'none',
            'last_run_time' => array_key_last($stats),
            'success_rate' => $latest_stats['success_rate'] ?? 0,
            'scheduled_tasks' => [
                'daily' => wp_next_scheduled('tmu_daily_maintenance'),
                'weekly' => wp_next_scheduled('tmu_weekly_maintenance'),
                'monthly' => wp_next_scheduled('tmu_monthly_maintenance')
            ]
        ];
        
        wp_send_json_success($status);
    }
    
    /**
     * Handle maintenance mode
     */
    public function handle_maintenance_mode(): void {
        if (!current_user_can('manage_options')) {
            $message = get_option('tmu_maintenance_message', 'Site is under maintenance. Please check back later.');
            
            wp_die($message, 'Site Maintenance', [
                'response' => 503,
                'retry-after' => 600
            ]);
        }
    }
    
    /**
     * Add maintenance admin page
     */
    public function add_maintenance_admin_page(): void {
        add_submenu_page(
            'tmu-settings',
            'Maintenance',
            'Maintenance',
            'manage_options',
            'tmu-maintenance',
            [$this, 'render_maintenance_page']
        );
    }
    
    /**
     * Render maintenance admin page
     */
    public function render_maintenance_page(): void {
        ?>
        <div class="wrap">
            <h1>TMU Maintenance</h1>
            
            <div id="tmu-maintenance-dashboard">
                <div class="maintenance-status">
                    <h2>Maintenance Status</h2>
                    <div id="maintenance-status-display">Loading...</div>
                </div>
                
                <div class="maintenance-controls">
                    <h2>Manual Maintenance</h2>
                    <button id="run-daily-maintenance" class="button button-primary">Run Daily Maintenance</button>
                    <button id="run-weekly-maintenance" class="button button-primary">Run Weekly Maintenance</button>
                    <button id="run-monthly-maintenance" class="button button-primary">Run Monthly Maintenance</button>
                    <button id="toggle-maintenance-mode" class="button">Toggle Maintenance Mode</button>
                </div>
                
                <div class="maintenance-reports">
                    <h2>Recent Reports</h2>
                    <div id="maintenance-reports-display">Loading...</div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue maintenance assets
     */
    public function enqueue_maintenance_assets($hook): void {
        if ($hook !== 'tmu-settings_page_tmu-maintenance') {
            return;
        }
        
        wp_enqueue_script(
            'tmu-maintenance-admin',
            get_template_directory_uri() . '/assets/js/maintenance-admin.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('tmu-maintenance-admin', 'tmuMaintenance', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_maintenance_nonce')
        ]);
    }
}