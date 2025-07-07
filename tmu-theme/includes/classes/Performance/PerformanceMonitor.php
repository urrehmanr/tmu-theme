<?php
/**
 * Performance Monitor - Performance Metrics Tracking
 *
 * @package TMU\Performance
 * @version 1.0.0
 */

namespace TMU\Performance;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Advanced Performance Monitoring System
 */
class PerformanceMonitor {
    
    /**
     * Start time
     */
    private float $start_time;
    
    /**
     * Memory start
     */
    private int $memory_start;
    
    /**
     * Performance metrics
     */
    private array $metrics = [];
    
    /**
     * Query performance data
     */
    private array $query_data = [];
    
    /**
     * Asset performance data
     */
    private array $asset_data = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->start_time = microtime(true);
        $this->memory_start = memory_get_usage();
        
        $this->init();
    }
    
    /**
     * Initialize performance monitor
     */
    public function init(): void {
        // Core performance hooks
        add_action('wp_loaded', [$this, 'capture_loading_metrics']);
        add_action('wp_footer', [$this, 'capture_final_metrics']);
        add_action('shutdown', [$this, 'log_performance_data']);
        
        // Query monitoring
        add_filter('query', [$this, 'track_query_performance']);
        add_action('wp_footer', [$this, 'output_performance_debug'], 9999);
        
        // Asset monitoring
        add_filter('script_loader_tag', [$this, 'track_script_performance'], 10, 3);
        add_filter('style_loader_tag', [$this, 'track_style_performance'], 10, 4);
        
        // AJAX monitoring
        add_action('wp_ajax_tmu_performance_stats', [$this, 'get_performance_stats']);
        
        // Core Web Vitals monitoring
        add_action('wp_head', [$this, 'inject_web_vitals_script']);
        add_action('wp_ajax_tmu_log_web_vitals', [$this, 'log_web_vitals']);
        add_action('wp_ajax_nopriv_tmu_log_web_vitals', [$this, 'log_web_vitals']);
        
        // Performance alerts
        add_action('tmu_performance_alert', [$this, 'handle_performance_alert']);
        
        // Scheduled performance reports
        add_action('tmu_daily_performance_report', [$this, 'generate_daily_report']);
        if (!wp_next_scheduled('tmu_daily_performance_report')) {
            wp_schedule_event(time(), 'daily', 'tmu_daily_performance_report');
        }
    }
    
    /**
     * Capture loading metrics
     */
    public function capture_loading_metrics(): void {
        $this->metrics['wp_loaded_time'] = microtime(true) - $this->start_time;
        $this->metrics['wp_loaded_memory'] = memory_get_usage() - $this->memory_start;
        $this->metrics['wp_loaded_queries'] = get_num_queries();
    }
    
    /**
     * Capture final metrics
     */
    public function capture_final_metrics(): void {
        $this->metrics['total_execution_time'] = microtime(true) - $this->start_time;
        $this->metrics['total_memory_usage'] = memory_get_usage() - $this->memory_start;
        $this->metrics['peak_memory_usage'] = memory_get_peak_usage();
        $this->metrics['total_queries'] = get_num_queries();
        $this->metrics['slow_queries'] = $this->count_slow_queries();
        
        // Server metrics
        $this->metrics['server_load'] = $this->get_server_load();
        $this->metrics['php_version'] = PHP_VERSION;
        $this->metrics['wordpress_version'] = get_bloginfo('version');
        
        // Page-specific metrics
        $this->metrics['page_type'] = $this->get_page_type();
        $this->metrics['post_id'] = get_queried_object_id();
        $this->metrics['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $this->metrics['request_uri'] = $_SERVER['REQUEST_URI'] ?? '';
        
        // Template metrics
        $this->metrics['template_hierarchy'] = $this->get_template_hierarchy();
        
        // Asset metrics
        $this->metrics['enqueued_scripts'] = $this->count_enqueued_scripts();
        $this->metrics['enqueued_styles'] = $this->count_enqueued_styles();
    }
    
    /**
     * Track query performance
     */
    public function track_query_performance($query): string {
        $start_time = microtime(true);
        
        // Execute the query
        global $wpdb;
        $wpdb->query($query);
        
        $execution_time = microtime(true) - $start_time;
        
        // Track slow queries (> 100ms)
        if ($execution_time > 0.1) {
            $this->query_data[] = [
                'query' => $query,
                'execution_time' => $execution_time,
                'timestamp' => current_time('mysql'),
                'memory_usage' => memory_get_usage(),
                'is_slow' => true
            ];
        }
        
        return $query;
    }
    
    /**
     * Track script performance
     */
    public function track_script_performance($tag, $handle, $src): string {
        $this->asset_data['scripts'][] = [
            'handle' => $handle,
            'src' => $src,
            'size' => $this->get_asset_size($src),
            'timestamp' => microtime(true)
        ];
        
        return $tag;
    }
    
    /**
     * Track style performance
     */
    public function track_style_performance($html, $handle, $href, $media): string {
        $this->asset_data['styles'][] = [
            'handle' => $handle,
            'href' => $href,
            'media' => $media,
            'size' => $this->get_asset_size($href),
            'timestamp' => microtime(true)
        ];
        
        return $html;
    }
    
    /**
     * Log performance data
     */
    public function log_performance_data(): void {
        // Only log if performance is poor or debugging is enabled
        if ($this->should_log_performance()) {
            $log_data = [
                'timestamp' => current_time('mysql'),
                'url' => $_SERVER['REQUEST_URI'] ?? '',
                'metrics' => $this->metrics,
                'queries' => $this->query_data,
                'assets' => $this->asset_data
            ];
            
            // Log to database
            $this->save_performance_log($log_data);
            
            // Send alerts if thresholds exceeded
            $this->check_performance_thresholds();
        }
    }
    
    /**
     * Output performance debug information
     */
    public function output_performance_debug(): void {
        if (!current_user_can('manage_options') || !isset($_GET['debug_performance'])) {
            return;
        }
        
        $execution_time = $this->metrics['total_execution_time'] ?? 0;
        $memory_usage = $this->metrics['total_memory_usage'] ?? 0;
        $peak_memory = $this->metrics['peak_memory_usage'] ?? 0;
        $total_queries = $this->metrics['total_queries'] ?? 0;
        $slow_queries = $this->metrics['slow_queries'] ?? 0;
        
        echo "<!-- TMU Performance Debug -->\n";
        echo "<!-- Execution Time: " . round($execution_time, 4) . "s -->\n";
        echo "<!-- Memory Usage: " . $this->format_bytes($memory_usage) . " -->\n";
        echo "<!-- Peak Memory: " . $this->format_bytes($peak_memory) . " -->\n";
        echo "<!-- Total Queries: {$total_queries} -->\n";
        echo "<!-- Slow Queries: {$slow_queries} -->\n";
        echo "<!-- Page Type: " . ($this->metrics['page_type'] ?? 'unknown') . " -->\n";
        
        if (!empty($this->query_data)) {
            echo "<!-- Slow Queries: -->\n";
            foreach ($this->query_data as $query_info) {
                echo "<!-- Query (" . round($query_info['execution_time'], 4) . "s): " . 
                     substr(str_replace(["\n", "\r"], ' ', $query_info['query']), 0, 100) . "... -->\n";
            }
        }
        
        // Asset information
        if (!empty($this->asset_data['scripts'])) {
            $total_script_size = array_sum(array_column($this->asset_data['scripts'], 'size'));
            echo "<!-- Total Scripts: " . count($this->asset_data['scripts']) . 
                 " (" . $this->format_bytes($total_script_size) . ") -->\n";
        }
        
        if (!empty($this->asset_data['styles'])) {
            $total_style_size = array_sum(array_column($this->asset_data['styles'], 'size'));
            echo "<!-- Total Styles: " . count($this->asset_data['styles']) . 
                 " (" . $this->format_bytes($total_style_size) . ") -->\n";
        }
    }
    
    /**
     * Inject Web Vitals monitoring script
     */
    public function inject_web_vitals_script(): void {
        if (is_admin()) {
            return;
        }
        ?>
        <script>
        // Core Web Vitals monitoring
        (function() {
            function sendWebVital(name, value, id) {
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'tmu_log_web_vitals',
                        metric: name,
                        value: value,
                        id: id,
                        url: window.location.href,
                        nonce: '<?php echo wp_create_nonce('tmu_web_vitals'); ?>'
                    })
                });
            }
            
            // Performance Observer for Core Web Vitals
            if ('PerformanceObserver' in window) {
                // Largest Contentful Paint (LCP)
                new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    const lastEntry = entries[entries.length - 1];
                    sendWebVital('LCP', lastEntry.startTime, lastEntry.id);
                }).observe({ type: 'largest-contentful-paint', buffered: true });
                
                // First Input Delay (FID)
                new PerformanceObserver((list) => {
                    list.getEntries().forEach((entry) => {
                        sendWebVital('FID', entry.processingStart - entry.startTime, entry.name);
                    });
                }).observe({ type: 'first-input', buffered: true });
                
                // Cumulative Layout Shift (CLS)
                let clsValue = 0;
                new PerformanceObserver((list) => {
                    list.getEntries().forEach((entry) => {
                        if (!entry.hadRecentInput) {
                            clsValue += entry.value;
                        }
                    });
                    sendWebVital('CLS', clsValue, 'cls');
                }).observe({ type: 'layout-shift', buffered: true });
            }
            
            // Navigation Timing
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const navigation = performance.getEntriesByType('navigation')[0];
                    if (navigation) {
                        sendWebVital('TTFB', navigation.responseStart - navigation.requestStart, 'ttfb');
                        sendWebVital('FCP', navigation.loadEventEnd - navigation.navigationStart, 'fcp');
                        sendWebVital('Load', navigation.loadEventEnd - navigation.navigationStart, 'load');
                    }
                }, 0);
            });
        })();
        </script>
        <?php
    }
    
    /**
     * Log Web Vitals data
     */
    public function log_web_vitals(): void {
        check_ajax_referer('tmu_web_vitals', 'nonce');
        
        $metric = sanitize_text_field($_POST['metric'] ?? '');
        $value = floatval($_POST['value'] ?? 0);
        $id = sanitize_text_field($_POST['id'] ?? '');
        $url = esc_url_raw($_POST['url'] ?? '');
        
        if ($metric && $value >= 0) {
            global $wpdb;
            
            $wpdb->insert(
                $wpdb->prefix . 'tmu_web_vitals',
                [
                    'metric_name' => $metric,
                    'metric_value' => $value,
                    'metric_id' => $id,
                    'page_url' => $url,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'timestamp' => current_time('mysql')
                ],
                ['%s', '%f', '%s', '%s', '%s', '%s']
            );
        }
        
        wp_die();
    }
    
    /**
     * Get performance statistics
     */
    public function get_performance_stats(): array {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'tmu'));
        }
        
        global $wpdb;
        
        $stats = [];
        
        // Recent performance data
        $recent_logs = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}tmu_performance_logs 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY timestamp DESC
            LIMIT 100
        ");
        
        $stats['recent_performance'] = array_map(function($log) {
            return [
                'timestamp' => $log->timestamp,
                'execution_time' => $log->execution_time,
                'memory_usage' => $log->memory_usage,
                'query_count' => $log->query_count,
                'page_type' => $log->page_type
            ];
        }, $recent_logs);
        
        // Web Vitals data
        $web_vitals = $wpdb->get_results("
            SELECT metric_name, AVG(metric_value) as avg_value, 
                   MIN(metric_value) as min_value, MAX(metric_value) as max_value
            FROM {$wpdb->prefix}tmu_web_vitals 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY metric_name
        ");
        
        $stats['web_vitals'] = array_reduce($web_vitals, function($carry, $vital) {
            $carry[$vital->metric_name] = [
                'average' => round($vital->avg_value, 2),
                'min' => round($vital->min_value, 2),
                'max' => round($vital->max_value, 2)
            ];
            return $carry;
        }, []);
        
        // Performance trends
        $stats['trends'] = $this->get_performance_trends();
        
        // Current system status
        $stats['system_status'] = [
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'server_load' => $this->get_server_load(),
            'disk_usage' => $this->get_disk_usage()
        ];
        
        return $stats;
    }
    
    /**
     * Handle performance alerts
     */
    public function handle_performance_alert($alert_data): void {
        $alert_type = $alert_data['type'] ?? '';
        $threshold = $alert_data['threshold'] ?? 0;
        $current_value = $alert_data['value'] ?? 0;
        
        // Send email alert to admin
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = "Performance Alert: {$alert_type} - {$site_name}";
        $message = "A performance alert has been triggered on {$site_name}.\n\n";
        $message .= "Alert Type: {$alert_type}\n";
        $message .= "Threshold: {$threshold}\n";
        $message .= "Current Value: {$current_value}\n";
        $message .= "Time: " . current_time('mysql') . "\n";
        $message .= "URL: " . home_url() . "\n\n";
        $message .= "Please check your site's performance and consider optimization.";
        
        wp_mail($admin_email, $subject, $message);
        
        // Log alert
        error_log("TMU Performance Alert: {$alert_type} exceeded threshold. Current: {$current_value}, Threshold: {$threshold}");
    }
    
    /**
     * Generate daily performance report
     */
    public function generate_daily_report(): void {
        global $wpdb;
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Get yesterday's performance data
        $performance_data = $wpdb->get_results($wpdb->prepare("
            SELECT AVG(execution_time) as avg_time, 
                   AVG(memory_usage) as avg_memory,
                   AVG(query_count) as avg_queries,
                   COUNT(*) as total_requests
            FROM {$wpdb->prefix}tmu_performance_logs 
            WHERE DATE(timestamp) = %s
        ", $yesterday));
        
        $report = [
            'date' => $yesterday,
            'avg_execution_time' => round($performance_data[0]->avg_time ?? 0, 4),
            'avg_memory_usage' => round($performance_data[0]->avg_memory ?? 0),
            'avg_queries' => round($performance_data[0]->avg_queries ?? 0),
            'total_requests' => $performance_data[0]->total_requests ?? 0
        ];
        
        // Store report
        update_option('tmu_daily_performance_report_' . $yesterday, $report);
        
        // Clean up old reports (keep 30 days)
        $old_date = date('Y-m-d', strtotime('-30 days'));
        delete_option('tmu_daily_performance_report_' . $old_date);
    }
    
    /**
     * Should log performance data
     */
    private function should_log_performance(): bool {
        $execution_time = $this->metrics['total_execution_time'] ?? 0;
        $memory_usage = $this->metrics['total_memory_usage'] ?? 0;
        $query_count = $this->metrics['total_queries'] ?? 0;
        
        // Log if performance is poor
        if ($execution_time > 3.0 || // > 3 seconds
            $memory_usage > 128 * 1024 * 1024 || // > 128MB
            $query_count > 100) { // > 100 queries
            return true;
        }
        
        // Log if debugging is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return true;
        }
        
        // Random sampling (10% of requests)
        return (rand(1, 100) <= 10);
    }
    
    /**
     * Save performance log
     */
    private function save_performance_log($log_data): void {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'tmu_performance_logs',
            [
                'timestamp' => $log_data['timestamp'],
                'url' => $log_data['url'],
                'execution_time' => $this->metrics['total_execution_time'] ?? 0,
                'memory_usage' => $this->metrics['total_memory_usage'] ?? 0,
                'peak_memory' => $this->metrics['peak_memory_usage'] ?? 0,
                'query_count' => $this->metrics['total_queries'] ?? 0,
                'slow_queries' => $this->metrics['slow_queries'] ?? 0,
                'page_type' => $this->metrics['page_type'] ?? '',
                'post_id' => $this->metrics['post_id'] ?? 0,
                'template_hierarchy' => maybe_serialize($this->metrics['template_hierarchy'] ?? []),
                'raw_data' => maybe_serialize($log_data)
            ],
            ['%s', '%s', '%f', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s']
        );
    }
    
    /**
     * Check performance thresholds
     */
    private function check_performance_thresholds(): void {
        $execution_time = $this->metrics['total_execution_time'] ?? 0;
        $memory_usage = $this->metrics['total_memory_usage'] ?? 0;
        $query_count = $this->metrics['total_queries'] ?? 0;
        
        // Execution time threshold (5 seconds)
        if ($execution_time > 5.0) {
            do_action('tmu_performance_alert', [
                'type' => 'Slow Page Load',
                'threshold' => 5.0,
                'value' => $execution_time
            ]);
        }
        
        // Memory usage threshold (256MB)
        if ($memory_usage > 256 * 1024 * 1024) {
            do_action('tmu_performance_alert', [
                'type' => 'High Memory Usage',
                'threshold' => 256,
                'value' => round($memory_usage / 1024 / 1024, 2)
            ]);
        }
        
        // Query count threshold (200 queries)
        if ($query_count > 200) {
            do_action('tmu_performance_alert', [
                'type' => 'Too Many Queries',
                'threshold' => 200,
                'value' => $query_count
            ]);
        }
    }
    
    /**
     * Count slow queries
     */
    private function count_slow_queries(): int {
        return count($this->query_data);
    }
    
    /**
     * Get server load
     */
    private function get_server_load(): float {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load[0] ?? 0;
        }
        
        return 0;
    }
    
    /**
     * Get disk usage
     */
    private function get_disk_usage(): array {
        $total = disk_total_space('.');
        $free = disk_free_space('.');
        $used = $total - $free;
        
        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'percentage' => $total > 0 ? round(($used / $total) * 100, 2) : 0
        ];
    }
    
    /**
     * Get page type
     */
    private function get_page_type(): string {
        if (is_front_page()) return 'front_page';
        if (is_home()) return 'blog_home';
        if (is_single()) return 'single_' . get_post_type();
        if (is_page()) return 'page';
        if (is_category()) return 'category';
        if (is_tag()) return 'tag';
        if (is_tax()) return 'taxonomy_' . get_queried_object()->taxonomy;
        if (is_author()) return 'author';
        if (is_date()) return 'date';
        if (is_search()) return 'search';
        if (is_404()) return '404';
        
        return 'unknown';
    }
    
    /**
     * Get template hierarchy
     */
    private function get_template_hierarchy(): array {
        global $template;
        
        $hierarchy = [];
        
        if (isset($template)) {
            $hierarchy['current_template'] = basename($template);
        }
        
        return $hierarchy;
    }
    
    /**
     * Count enqueued scripts
     */
    private function count_enqueued_scripts(): int {
        global $wp_scripts;
        
        return count($wp_scripts->queue ?? []);
    }
    
    /**
     * Count enqueued styles
     */
    private function count_enqueued_styles(): int {
        global $wp_styles;
        
        return count($wp_styles->queue ?? []);
    }
    
    /**
     * Get asset size
     */
    private function get_asset_size($url): int {
        // For external URLs, we can't easily get size
        if (strpos($url, home_url()) !== 0) {
            return 0;
        }
        
        $file_path = str_replace(home_url(), ABSPATH, $url);
        
        return file_exists($file_path) ? filesize($file_path) : 0;
    }
    
    /**
     * Get performance trends
     */
    private function get_performance_trends(): array {
        global $wpdb;
        
        $trends = [];
        
        // Get last 7 days of performance data
        $results = $wpdb->get_results("
            SELECT DATE(timestamp) as date,
                   AVG(execution_time) as avg_time,
                   AVG(memory_usage) as avg_memory,
                   AVG(query_count) as avg_queries
            FROM {$wpdb->prefix}tmu_performance_logs 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(timestamp)
            ORDER BY date
        ");
        
        foreach ($results as $result) {
            $trends[] = [
                'date' => $result->date,
                'execution_time' => round($result->avg_time, 4),
                'memory_usage' => round($result->avg_memory),
                'query_count' => round($result->avg_queries)
            ];
        }
        
        return $trends;
    }
    
    /**
     * Format bytes
     */
    private function format_bytes($bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Get current metrics
     */
    public function get_metrics(): array {
        return $this->metrics;
    }
    
    /**
     * Get query data
     */
    public function get_query_data(): array {
        return $this->query_data;
    }
    
    /**
     * Get asset data
     */
    public function get_asset_data(): array {
        return $this->asset_data;
    }
}