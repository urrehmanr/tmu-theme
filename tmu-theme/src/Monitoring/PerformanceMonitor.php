<?php
/**
 * Performance Monitor
 * 
 * Performance monitoring and metrics collection.
 * 
 * @package TMU\Monitoring
 * @since 1.0.0
 */

namespace TMU\Monitoring;

class PerformanceMonitor {
    
    /**
     * Performance metrics storage
     * @var array
     */
    private $metrics = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'start_monitoring']);
        add_action('wp_footer', [$this, 'log_metrics']);
    }
    
    /**
     * Start performance monitoring
     */
    public function start_monitoring(): void {
        $this->metrics['start_time'] = microtime(true);
        $this->metrics['start_memory'] = memory_get_usage();
        
        // Hook into various WordPress actions to track performance
        add_action('wp_head', [$this, 'track_head_performance']);
        add_action('wp_footer', [$this, 'track_footer_performance']);
    }
    
    /**
     * Track head performance metrics
     */
    public function track_head_performance(): void {
        $this->metrics['head_time'] = microtime(true) - $this->metrics['start_time'];
        $this->metrics['head_memory'] = memory_get_usage() - $this->metrics['start_memory'];
    }
    
    /**
     * Track footer performance metrics
     */
    public function track_footer_performance(): void {
        $this->metrics['total_time'] = microtime(true) - $this->metrics['start_time'];
        $this->metrics['total_memory'] = memory_get_usage() - $this->metrics['start_memory'];
        $this->metrics['peak_memory'] = memory_get_peak_usage();
        $this->metrics['database_queries'] = get_num_queries();
    }
    
    /**
     * Log performance metrics
     */
    public function log_metrics(): void {
        if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
            error_log('TMU Performance Metrics: ' . json_encode($this->metrics));
        }
        
        // Send to external monitoring service
        $this->send_to_monitoring_service();
    }
    
    /**
     * Send metrics to external monitoring service
     */
    private function send_to_monitoring_service(): void {
        $monitoring_url = get_option('tmu_monitoring_webhook');
        
        if ($monitoring_url && $this->metrics['total_time'] > 2) { // Only log slow requests
            wp_remote_post($monitoring_url, [
                'body' => json_encode([
                    'site' => get_site_url(),
                    'timestamp' => current_time('c'),
                    'metrics' => $this->metrics,
                    'url' => $_SERVER['REQUEST_URI'] ?? '',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]),
                'headers' => ['Content-Type' => 'application/json']
            ]);
        }
    }
    
    /**
     * Get current performance metrics
     * 
     * @return array Performance metrics
     */
    public function get_metrics(): array {
        return $this->metrics;
    }
    
    /**
     * Get formatted performance report
     * 
     * @return array Formatted performance report
     */
    public function get_performance_report(): array {
        $current_time = microtime(true);
        $current_memory = memory_get_usage();
        
        $total_time = isset($this->metrics['start_time']) 
            ? $current_time - $this->metrics['start_time'] 
            : 0;
            
        $total_memory = isset($this->metrics['start_memory']) 
            ? $current_memory - $this->metrics['start_memory'] 
            : $current_memory;
        
        return [
            'page_load_time' => round($total_time * 1000, 2) . 'ms',
            'memory_usage' => $this->format_bytes($total_memory),
            'peak_memory' => $this->format_bytes(memory_get_peak_usage()),
            'database_queries' => get_num_queries(),
            'timestamp' => current_time('c'),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'status' => $this->get_performance_status($total_time)
        ];
    }
    
    /**
     * Get performance status based on load time
     * 
     * @param float $load_time Load time in seconds
     * @return string Performance status
     */
    private function get_performance_status(float $load_time): string {
        if ($load_time < 1) {
            return 'excellent';
        } elseif ($load_time < 2) {
            return 'good';
        } elseif ($load_time < 3) {
            return 'average';
        } else {
            return 'poor';
        }
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes Bytes
     * @return string Formatted bytes
     */
    private function format_bytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Track custom metric
     * 
     * @param string $name Metric name
     * @param mixed $value Metric value
     */
    public function track_metric(string $name, $value): void {
        $this->metrics['custom'][$name] = $value;
    }
    
    /**
     * Start timing a custom operation
     * 
     * @param string $operation Operation name
     */
    public function start_timer(string $operation): void {
        $this->metrics['timers'][$operation]['start'] = microtime(true);
    }
    
    /**
     * End timing a custom operation
     * 
     * @param string $operation Operation name
     */
    public function end_timer(string $operation): void {
        if (isset($this->metrics['timers'][$operation]['start'])) {
            $this->metrics['timers'][$operation]['duration'] = 
                microtime(true) - $this->metrics['timers'][$operation]['start'];
        }
    }
    
    /**
     * Get timer duration
     * 
     * @param string $operation Operation name
     * @return float|null Duration in seconds
     */
    public function get_timer_duration(string $operation): ?float {
        return $this->metrics['timers'][$operation]['duration'] ?? null;
    }
}