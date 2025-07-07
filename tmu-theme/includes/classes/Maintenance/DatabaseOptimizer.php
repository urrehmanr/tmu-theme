<?php
/**
 * Database Optimizer
 * 
 * Database maintenance and optimization exactly as specified in Step 18 documentation.
 * 
 * @package TMU\Maintenance
 * @since 1.0.0
 */

namespace TMU\Maintenance;

use TMU\Logging\LogManager;

class DatabaseOptimizer {
    
    /**
     * Logger instance
     * @var LogManager
     */
    private $logger;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new LogManager();
        add_action('tmu_optimize_database', [$this, 'optimize_database']);
    }
    
    /**
     * Main database optimization method
     */
    public function optimize_database(): void {
        $this->logger->info('Starting database optimization');
        
        try {
            $this->optimize_tables();
            $this->cleanup_orphaned_data();
            $this->update_statistics();
            $this->analyze_performance();
            
            $this->logger->info('Database optimization completed successfully');
        } catch (\Exception $e) {
            $this->logger->error('Database optimization failed', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Optimize database tables exactly as documented
     */
    private function optimize_tables(): void {
        global $wpdb;
        
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
            // Optimize table
            $wpdb->query("OPTIMIZE TABLE {$table}");
            
            // Analyze table for better query performance
            $wpdb->query("ANALYZE TABLE {$table}");
            
            // Check table integrity
            $check_result = $wpdb->get_results("CHECK TABLE {$table}");
            
            foreach ($check_result as $result) {
                if ($result->Msg_text !== 'OK') {
                    error_log("Database table issue: {$table} - {$result->Msg_text}");
                }
            }
        }
    }
    
    /**
     * Cleanup orphaned data exactly as documented
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
     * Update statistics exactly as documented
     */
    private function update_statistics(): void {
        global $wpdb;
        
        // Update content statistics
        $stats = [
            'total_movies' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_movies"),
            'total_tv_series' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_tv_series"),
            'total_dramas' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_dramas"),
            'total_people' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_people"),
            'last_updated' => current_time('mysql')
        ];
        
        update_option('tmu_content_statistics', $stats);
    }
    
    /**
     * Analyze performance exactly as documented
     */
    private function analyze_performance(): void {
        global $wpdb;
        
        // Get slow queries
        $slow_queries = $wpdb->get_results("
            SELECT query, avg_timer_wait, count_star
            FROM performance_schema.events_statements_summary_by_digest
            WHERE avg_timer_wait > 1000000000
            AND query LIKE '%tmu_%'
            ORDER BY avg_timer_wait DESC
            LIMIT 10
        ");
        
        if (!empty($slow_queries)) {
            $report = "Slow TMU queries detected:\n";
            foreach ($slow_queries as $query) {
                $report .= "Query: {$query->query}\n";
                $report .= "Avg Time: " . ($query->avg_timer_wait / 1000000000) . "s\n";
                $report .= "Count: {$query->count_star}\n\n";
            }
            
            error_log($report);
        }
    }
}