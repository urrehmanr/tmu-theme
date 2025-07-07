<?php
/**
 * Analytics Manager
 * 
 * Coordinates all analytics functionality for the TMU theme.
 * 
 * @package TMU\Analytics
 * @since 1.0.0
 */

namespace TMU\Analytics;

use TMU\Analytics\UserAnalytics;
use TMU\Analytics\GoogleAnalytics;

class AnalyticsManager {
    
    /**
     * User analytics instance
     * @var UserAnalytics
     */
    private $user_analytics;
    
    /**
     * Google Analytics instance
     * @var GoogleAnalytics
     */
    private $google_analytics;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_analytics();
        add_action('init', [$this, 'register_hooks']);
        add_action('admin_init', [$this, 'create_analytics_tables']);
    }
    
    /**
     * Initialize analytics components
     */
    private function init_analytics(): void {
        $this->user_analytics = new UserAnalytics();
        $this->google_analytics = new GoogleAnalytics();
    }
    
    /**
     * Register WordPress hooks
     */
    public function register_hooks(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_analytics_assets']);
        add_action('wp_ajax_tmu_analytics_report', [$this, 'generate_analytics_report']);
        add_action('wp_ajax_tmu_export_analytics', [$this, 'export_analytics_data']);
    }
    
    /**
     * Enqueue analytics assets
     */
    public function enqueue_analytics_assets(): void {
        // Chart.js for dashboard
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            '3.9.1',
            true
        );
        
        // Health dashboard script
        wp_enqueue_script(
            'tmu-health-dashboard',
            get_template_directory_uri() . '/assets/js/health-dashboard.js',
            ['jquery', 'chart-js'],
            '1.0.0',
            true
        );
        
        wp_localize_script('tmu-health-dashboard', 'tmu_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_health_nonce')
        ]);
    }
    
    /**
     * Create analytics database tables
     */
    public function create_analytics_tables(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Analytics events table
        $events_table = $wpdb->prefix . 'tmu_analytics_events';
        $events_sql = "CREATE TABLE IF NOT EXISTS $events_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(100) NOT NULL,
            event_data longtext,
            user_id bigint(20) unsigned DEFAULT 0,
            session_id varchar(100),
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            url text,
            referrer text,
            user_agent text,
            screen_resolution varchar(20),
            viewport_size varchar(20),
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        // Performance logs table
        $performance_table = $wpdb->prefix . 'tmu_performance_logs';
        $performance_sql = "CREATE TABLE IF NOT EXISTS $performance_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url text,
            response_time float,
            memory_usage bigint(20),
            peak_memory bigint(20),
            query_count int,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            user_agent text,
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY response_time (response_time)
        ) $charset_collate;";
        
        // Error logs table
        $error_table = $wpdb->prefix . 'tmu_error_logs';
        $error_sql = "CREATE TABLE IF NOT EXISTS $error_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            error_type varchar(50),
            message text,
            file text,
            line int,
            trace longtext,
            severity int,
            url text,
            user_id bigint(20) unsigned DEFAULT 0,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY error_type (error_type),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($events_sql);
        dbDelta($performance_sql);
        dbDelta($error_sql);
    }
    
    /**
     * Generate analytics report
     */
    public function generate_analytics_report(): void {
        check_ajax_referer('tmu_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        
        $start_date = sanitize_text_field($_POST['start_date'] ?? date('Y-m-d', strtotime('-7 days')));
        $end_date = sanitize_text_field($_POST['end_date'] ?? date('Y-m-d'));
        
        $report = [
            'page_views' => $this->get_page_views($start_date, $end_date),
            'popular_content' => $this->get_popular_content($start_date, $end_date),
            'user_behavior' => $this->get_user_behavior($start_date, $end_date),
            'performance_metrics' => $this->get_performance_metrics($start_date, $end_date),
            'error_summary' => $this->get_error_summary($start_date, $end_date)
        ];
        
        wp_send_json_success($report);
    }
    
    /**
     * Get page views data
     */
    private function get_page_views($start_date, $end_date): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_analytics_events';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(timestamp) as date,
                COUNT(*) as views
             FROM $table_name 
             WHERE event_type = 'page_view' 
             AND DATE(timestamp) BETWEEN %s AND %s
             GROUP BY DATE(timestamp)
             ORDER BY date",
            $start_date,
            $end_date
        ));
    }
    
    /**
     * Get popular content data
     */
    private function get_popular_content($start_date, $end_date): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_analytics_events';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                url,
                COUNT(*) as views
             FROM $table_name 
             WHERE event_type = 'page_view' 
             AND DATE(timestamp) BETWEEN %s AND %s
             GROUP BY url
             ORDER BY views DESC
             LIMIT 10",
            $start_date,
            $end_date
        ));
    }
    
    /**
     * Get user behavior data
     */
    private function get_user_behavior($start_date, $end_date): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_analytics_events';
        
        return [
            'average_session_duration' => $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(CAST(JSON_EXTRACT(event_data, '$.seconds') AS UNSIGNED))
                 FROM $table_name 
                 WHERE event_type = 'time_on_page' 
                 AND DATE(timestamp) BETWEEN %s AND %s",
                $start_date,
                $end_date
            )),
            'bounce_rate' => $this->calculate_bounce_rate($start_date, $end_date),
            'popular_searches' => $this->get_popular_searches($start_date, $end_date)
        ];
    }
    
    /**
     * Get performance metrics
     */
    private function get_performance_metrics($start_date, $end_date): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_performance_logs';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(timestamp) as date,
                AVG(response_time) as avg_response_time,
                MAX(response_time) as max_response_time,
                COUNT(*) as request_count
             FROM $table_name 
             WHERE DATE(timestamp) BETWEEN %s AND %s
             GROUP BY DATE(timestamp)
             ORDER BY date",
            $start_date,
            $end_date
        ));
    }
    
    /**
     * Get error summary
     */
    private function get_error_summary($start_date, $end_date): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_error_logs';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                error_type,
                COUNT(*) as count
             FROM $table_name 
             WHERE DATE(timestamp) BETWEEN %s AND %s
             GROUP BY error_type
             ORDER BY count DESC",
            $start_date,
            $end_date
        ));
    }
    
    /**
     * Calculate bounce rate
     */
    private function calculate_bounce_rate($start_date, $end_date): float {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_analytics_events';
        
        $total_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id)
             FROM $table_name 
             WHERE DATE(timestamp) BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));
        
        $single_page_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM (
                 SELECT session_id, COUNT(*) as page_count
                 FROM $table_name 
                 WHERE event_type = 'page_view' 
                 AND DATE(timestamp) BETWEEN %s AND %s
                 GROUP BY session_id
                 HAVING page_count = 1
             ) as single_page",
            $start_date,
            $end_date
        ));
        
        return $total_sessions > 0 ? ($single_page_sessions / $total_sessions) * 100 : 0;
    }
    
    /**
     * Get popular searches
     */
    private function get_popular_searches($start_date, $end_date): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_analytics_events';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                JSON_EXTRACT(event_data, '$.query') as query,
                COUNT(*) as count
             FROM $table_name 
             WHERE event_type = 'search' 
             AND DATE(timestamp) BETWEEN %s AND %s
             GROUP BY JSON_EXTRACT(event_data, '$.query')
             ORDER BY count DESC
             LIMIT 10",
            $start_date,
            $end_date
        ));
    }
    
    /**
     * Export analytics data
     */
    public function export_analytics_data(): void {
        check_ajax_referer('tmu_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $data_type = sanitize_text_field($_POST['data_type'] ?? 'events');
        
        switch ($data_type) {
            case 'events':
                $this->export_events_data($format);
                break;
            case 'performance':
                $this->export_performance_data($format);
                break;
            case 'errors':
                $this->export_error_data($format);
                break;
        }
    }
    
    /**
     * Export events data
     */
    private function export_events_data($format): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_analytics_events';
        $data = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC");
        
        if ($format === 'csv') {
            $this->output_csv($data, 'tmu_analytics_events');
        } else {
            wp_send_json_success($data);
        }
    }
    
    /**
     * Output CSV data
     */
    private function output_csv($data, $filename): void {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data)) {
            // Header row
            fputcsv($output, array_keys((array) $data[0]));
            
            // Data rows
            foreach ($data as $row) {
                fputcsv($output, (array) $row);
            }
        }
        
        fclose($output);
        exit;
    }
}