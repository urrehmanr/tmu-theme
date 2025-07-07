<?php
/**
 * Performance Monitor
 * 
 * Advanced performance monitoring for the TMU theme.
 * 
 * @package TMU\Monitoring
 * @since 1.0.0
 */

namespace TMU\Monitoring;

use TMU\Monitoring\PerformanceTracker;

class PerformanceMonitor {
    
    /**
     * Performance tracker instance
     * @var PerformanceTracker
     */
    private $tracker;
    
    /**
     * Performance thresholds
     * @var array
     */
    private $thresholds = [
        'response_time' => 2.0,    // 2 seconds
        'memory_usage' => 128 * 1024 * 1024, // 128MB
        'query_count' => 50,       // 50 queries
        'api_response' => 1.0      // 1 second
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->tracker = new PerformanceTracker();
        add_action('init', [$this, 'init_monitoring']);
        add_action('wp_ajax_tmu_performance_data', [$this, 'get_performance_data']);
        add_action('wp_ajax_tmu_recent_events', [$this, 'get_recent_events']);
    }
    
    /**
     * Initialize monitoring
     */
    public function init_monitoring(): void {
        add_action('wp_footer', [$this, 'log_performance_data'], 999);
        add_action('wp_ajax_tmu_clear_performance_logs', [$this, 'clear_performance_logs']);
        
        // Register performance alerts
        add_action('tmu_performance_alert', [$this, 'handle_performance_alert'], 10, 2);
    }
    
    /**
     * Log performance data to database
     */
    public function log_performance_data(): void {
        global $wpdb;
        
        $metrics = $this->tracker->get_metrics();
        
        if (empty($metrics)) {
            return;
        }
        
        $table_name = $wpdb->prefix . 'tmu_performance_logs';
        
        $wpdb->insert($table_name, [
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'response_time' => $metrics['total_time'] ?? 0,
            'memory_usage' => $metrics['total_memory'] ?? 0,
            'peak_memory' => $metrics['peak_memory'] ?? 0,
            'query_count' => $metrics['total_queries'] ?? 0,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => current_time('mysql')
        ]);
        
        // Check for performance issues
        $this->check_performance_thresholds($metrics);
    }
    
    /**
     * Check performance against thresholds
     */
    private function check_performance_thresholds($metrics): void {
        $alerts = [];
        
        if (($metrics['total_time'] ?? 0) > $this->thresholds['response_time']) {
            $alerts[] = [
                'type' => 'slow_response',
                'value' => $metrics['total_time'],
                'threshold' => $this->thresholds['response_time'],
                'message' => sprintf('Slow response time: %.3fs (threshold: %.3fs)', 
                    $metrics['total_time'], $this->thresholds['response_time'])
            ];
        }
        
        if (($metrics['peak_memory'] ?? 0) > $this->thresholds['memory_usage']) {
            $alerts[] = [
                'type' => 'high_memory',
                'value' => $metrics['peak_memory'],
                'threshold' => $this->thresholds['memory_usage'],
                'message' => sprintf('High memory usage: %s (threshold: %s)', 
                    $this->format_bytes($metrics['peak_memory']), 
                    $this->format_bytes($this->thresholds['memory_usage']))
            ];
        }
        
        if (($metrics['total_queries'] ?? 0) > $this->thresholds['query_count']) {
            $alerts[] = [
                'type' => 'high_queries',
                'value' => $metrics['total_queries'],
                'threshold' => $this->thresholds['query_count'],
                'message' => sprintf('High query count: %d (threshold: %d)', 
                    $metrics['total_queries'], $this->thresholds['query_count'])
            ];
        }
        
        foreach ($alerts as $alert) {
            do_action('tmu_performance_alert', $alert, $metrics);
        }
    }
    
    /**
     * Handle performance alerts
     */
    public function handle_performance_alert($alert, $metrics): void {
        $this->log_performance_alert($alert, $metrics);
        
        // Send alert notifications if configured
        $this->send_performance_notification($alert, $metrics);
    }
    
    /**
     * Log performance alert
     */
    private function log_performance_alert($alert, $metrics): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_error_logs';
        
        $wpdb->insert($table_name, [
            'error_type' => 'performance_alert',
            'message' => $alert['message'],
            'file' => $_SERVER['REQUEST_URI'] ?? '',
            'line' => 0,
            'severity' => $this->get_alert_severity($alert['type']),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ]);
    }
    
    /**
     * Get alert severity
     */
    private function get_alert_severity($type): int {
        $severities = [
            'slow_response' => 3,
            'high_memory' => 2,
            'high_queries' => 2
        ];
        
        return $severities[$type] ?? 1;
    }
    
    /**
     * Send performance notification
     */
    private function send_performance_notification($alert, $metrics): void {
        $notification_config = get_option('tmu_performance_notifications', []);
        
        if (empty($notification_config['webhook_url'])) {
            return;
        }
        
        $payload = [
            'alert' => $alert,
            'metrics' => $metrics,
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'timestamp' => current_time('c'),
            'site_url' => get_site_url()
        ];
        
        wp_remote_post($notification_config['webhook_url'], [
            'body' => json_encode($payload),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 10,
            'blocking' => false
        ]);
    }
    
    /**
     * Get performance data for dashboard
     */
    public function get_performance_data(): void {
        check_ajax_referer('tmu_health_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        
        $hours = intval($_POST['hours'] ?? 24);
        $table_name = $wpdb->prefix . 'tmu_performance_logs';
        
        // Get performance data over time
        $performance_data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE_FORMAT(timestamp, '%%H:%%i') as time,
                AVG(response_time) as avg_response,
                MAX(response_time) as max_response,
                AVG(memory_usage) as avg_memory,
                COUNT(*) as request_count
             FROM $table_name 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d HOUR)
             GROUP BY DATE_FORMAT(timestamp, '%%Y-%%m-%%d %%H:%%i')
             ORDER BY timestamp",
            $hours
        ));
        
        // Get error data
        $error_table = $wpdb->prefix . 'tmu_error_logs';
        $error_data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE_FORMAT(timestamp, '%%H:%%i') as time,
                COUNT(*) as error_count
             FROM $error_table 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d HOUR)
             GROUP BY DATE_FORMAT(timestamp, '%%Y-%%m-%%d %%H:%%i')
             ORDER BY timestamp",
            $hours
        ));
        
        $response_data = [
            'performance' => [
                'labels' => array_column($performance_data, 'time'),
                'data' => array_map('floatval', array_column($performance_data, 'avg_response'))
            ],
            'errors' => [
                'labels' => array_column($error_data, 'time'),
                'data' => array_map('intval', array_column($error_data, 'error_count'))
            ]
        ];
        
        wp_send_json_success($response_data);
    }
    
    /**
     * Get recent events
     */
    public function get_recent_events(): void {
        check_ajax_referer('tmu_health_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        
        $events = [];
        
        // Get recent performance alerts
        $error_table = $wpdb->prefix . 'tmu_error_logs';
        $performance_alerts = $wpdb->get_results(
            "SELECT 'performance' as type, message, timestamp 
             FROM $error_table 
             WHERE error_type = 'performance_alert' 
             ORDER BY timestamp DESC 
             LIMIT 5"
        );
        
        foreach ($performance_alerts as $alert) {
            $events[] = [
                'type' => 'performance',
                'message' => $alert->message,
                'timestamp' => $alert->timestamp
            ];
        }
        
        // Get recent errors
        $recent_errors = $wpdb->get_results(
            "SELECT 'error' as type, message, timestamp 
             FROM $error_table 
             WHERE error_type != 'performance_alert' 
             ORDER BY timestamp DESC 
             LIMIT 5"
        );
        
        foreach ($recent_errors as $error) {
            $events[] = [
                'type' => 'error',
                'message' => $error->message,
                'timestamp' => $error->timestamp
            ];
        }
        
        // Sort by timestamp
        usort($events, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        wp_send_json_success(array_slice($events, 0, 10));
    }
    
    /**
     * Clear performance logs
     */
    public function clear_performance_logs(): void {
        check_ajax_referer('tmu_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        
        $days = intval($_POST['days'] ?? 30);
        
        // Clear old performance logs
        $performance_table = $wpdb->prefix . 'tmu_performance_logs';
        $deleted_performance = $wpdb->query($wpdb->prepare(
            "DELETE FROM $performance_table 
             WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        // Clear old error logs
        $error_table = $wpdb->prefix . 'tmu_error_logs';
        $deleted_errors = $wpdb->query($wpdb->prepare(
            "DELETE FROM $error_table 
             WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        // Clear old analytics events
        $analytics_table = $wpdb->prefix . 'tmu_analytics_events';
        $deleted_analytics = $wpdb->query($wpdb->prepare(
            "DELETE FROM $analytics_table 
             WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        wp_send_json_success([
            'performance_logs_deleted' => $deleted_performance,
            'error_logs_deleted' => $deleted_errors,
            'analytics_events_deleted' => $deleted_analytics
        ]);
    }
    
    /**
     * Get performance summary
     */
    public function get_performance_summary($hours = 24): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_performance_logs';
        
        $summary = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_requests,
                AVG(response_time) as avg_response_time,
                MAX(response_time) as max_response_time,
                MIN(response_time) as min_response_time,
                AVG(memory_usage) as avg_memory_usage,
                MAX(memory_usage) as max_memory_usage,
                AVG(query_count) as avg_query_count,
                MAX(query_count) as max_query_count
             FROM $table_name 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d HOUR)",
            $hours
        ), ARRAY_A);
        
        return $summary ?: [];
    }
    
    /**
     * Format bytes to human readable format
     */
    private function format_bytes($bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Set performance thresholds
     */
    public function set_thresholds($thresholds): void {
        $this->thresholds = array_merge($this->thresholds, $thresholds);
        update_option('tmu_performance_thresholds', $this->thresholds);
    }
    
    /**
     * Get performance thresholds
     */
    public function get_thresholds(): array {
        return $this->thresholds;
    }
}