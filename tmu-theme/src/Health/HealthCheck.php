<?php
/**
 * Health Check REST API
 * 
 * REST API endpoints for health monitoring and status checks.
 * 
 * @package TMU\Health
 * @since 1.0.0
 */

namespace TMU\Health;

class HealthCheck {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_health_endpoints']);
    }
    
    /**
     * Register REST API endpoints
     */
    public function register_health_endpoints(): void {
        register_rest_route('tmu/v1', '/health', [
            'methods' => 'GET',
            'callback' => [$this, 'health_check'],
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route('tmu/v1', '/health/detailed', [
            'methods' => 'GET',
            'callback' => [$this, 'detailed_health_check'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ]);
    }
    
    /**
     * Basic health check endpoint
     * 
     * @return array Health status
     */
    public function health_check(): array {
        $status = 'healthy';
        $checks = [
            'database' => $this->check_database(),
            'cache' => $this->check_cache(),
            'tmdb_api' => $this->check_tmdb_api(),
        ];
        
        foreach ($checks as $check) {
            if (!$check['status']) {
                $status = 'unhealthy';
                break;
            }
        }
        
        return [
            'status' => $status,
            'timestamp' => current_time('c'),
            'version' => wp_get_theme()->get('Version'),
            'checks' => $checks
        ];
    }
    
    /**
     * Detailed health check endpoint
     * 
     * @return array Detailed health information
     */
    public function detailed_health_check(): array {
        return [
            'status' => 'healthy',
            'timestamp' => current_time('c'),
            'version' => wp_get_theme()->get('Version'),
            'environment' => wp_get_environment_type(),
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit')
            ],
            'database' => $this->detailed_database_check(),
            'cache' => $this->detailed_cache_check(),
            'tmdb_api' => $this->detailed_tmdb_check(),
            'disk_space' => $this->check_disk_space(),
            'performance' => $this->check_performance_metrics()
        ];
    }
    
    /**
     * Check database connection
     * 
     * @return array Database status
     */
    private function check_database(): array {
        global $wpdb;
        
        try {
            $result = $wpdb->get_var("SELECT 1");
            return [
                'status' => $result === '1',
                'message' => $result === '1' ? 'Database connection OK' : 'Database connection failed'
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check cache functionality
     * 
     * @return array Cache status
     */
    private function check_cache(): array {
        $test_key = 'tmu_health_check_' . time();
        $test_value = 'test_value';
        
        wp_cache_set($test_key, $test_value, 'tmu_health', 60);
        $cached_value = wp_cache_get($test_key, 'tmu_health');
        
        return [
            'status' => $cached_value === $test_value,
            'message' => $cached_value === $test_value ? 'Cache working' : 'Cache not working'
        ];
    }
    
    /**
     * Check TMDB API connectivity
     * 
     * @return array TMDB API status
     */
    private function check_tmdb_api(): array {
        $tmdb_key = get_option('tmu_tmdb_api_key');
        
        if (!$tmdb_key) {
            return [
                'status' => false,
                'message' => 'TMDB API key not configured'
            ];
        }
        
        $response = wp_remote_get("https://api.themoviedb.org/3/configuration?api_key={$tmdb_key}");
        
        if (is_wp_error($response)) {
            return [
                'status' => false,
                'message' => 'TMDB API error: ' . $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        return [
            'status' => $status_code === 200,
            'message' => $status_code === 200 ? 'TMDB API accessible' : "TMDB API returned status {$status_code}"
        ];
    }
    
    /**
     * Detailed database check
     * 
     * @return array Detailed database information
     */
    private function detailed_database_check(): array {
        global $wpdb;
        
        try {
            $start_time = microtime(true);
            $result = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}");
            $query_time = microtime(true) - $start_time;
            
            return [
                'status' => true,
                'query_time' => round($query_time * 1000, 2) . 'ms',
                'posts_count' => $result,
                'server_version' => $wpdb->get_var("SELECT VERSION()")
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Detailed cache check
     * 
     * @return array Detailed cache information
     */
    private function detailed_cache_check(): array {
        $test_key = 'tmu_detailed_cache_test_' . time();
        $test_value = 'detailed_test_value';
        
        $start_time = microtime(true);
        wp_cache_set($test_key, $test_value, 'tmu_health', 60);
        $set_time = microtime(true) - $start_time;
        
        $start_time = microtime(true);
        $cached_value = wp_cache_get($test_key, 'tmu_health');
        $get_time = microtime(true) - $start_time;
        
        wp_cache_delete($test_key, 'tmu_health');
        
        return [
            'status' => $cached_value === $test_value,
            'set_time' => round($set_time * 1000, 2) . 'ms',
            'get_time' => round($get_time * 1000, 2) . 'ms',
            'cache_driver' => defined('WP_CACHE') && WP_CACHE ? 'Object Cache' : 'Database'
        ];
    }
    
    /**
     * Detailed TMDB API check
     * 
     * @return array Detailed TMDB API information
     */
    private function detailed_tmdb_check(): array {
        $tmdb_key = get_option('tmu_tmdb_api_key');
        
        if (!$tmdb_key) {
            return [
                'status' => false,
                'message' => 'TMDB API key not configured'
            ];
        }
        
        $start_time = microtime(true);
        $response = wp_remote_get("https://api.themoviedb.org/3/configuration?api_key={$tmdb_key}");
        $response_time = microtime(true) - $start_time;
        
        if (is_wp_error($response)) {
            return [
                'status' => false,
                'error' => $response->get_error_message(),
                'response_time' => round($response_time * 1000, 2) . 'ms'
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return [
            'status' => $status_code === 200,
            'status_code' => $status_code,
            'response_time' => round($response_time * 1000, 2) . 'ms',
            'api_configuration' => $data ? 'Valid' : 'Invalid response'
        ];
    }
    
    /**
     * Check disk space
     * 
     * @return array Disk space information
     */
    private function check_disk_space(): array {
        $upload_dir = wp_upload_dir();
        $free_bytes = disk_free_space($upload_dir['basedir']);
        $total_bytes = disk_total_space($upload_dir['basedir']);
        
        if ($free_bytes === false || $total_bytes === false) {
            return [
                'status' => false,
                'message' => 'Unable to check disk space'
            ];
        }
        
        $used_bytes = $total_bytes - $free_bytes;
        $usage_percentage = ($used_bytes / $total_bytes) * 100;
        
        return [
            'total' => $this->format_bytes($total_bytes),
            'used' => $this->format_bytes($used_bytes),
            'free' => $this->format_bytes($free_bytes),
            'usage_percentage' => round($usage_percentage, 2) . '%',
            'status' => $usage_percentage < 90 ? 'OK' : 'WARNING'
        ];
    }
    
    /**
     * Check performance metrics
     * 
     * @return array Performance metrics
     */
    private function check_performance_metrics(): array {
        $start_time = microtime(true);
        
        // Memory usage
        $memory_usage = memory_get_usage(true);
        $peak_memory = memory_get_peak_usage(true);
        $memory_limit = $this->convert_to_bytes(ini_get('memory_limit'));
        
        // Server load
        $load_average = sys_getloadavg();
        
        $total_time = microtime(true) - $start_time;
        
        return [
            'memory_usage' => $this->format_bytes($memory_usage),
            'peak_memory' => $this->format_bytes($peak_memory),
            'memory_limit' => $this->format_bytes($memory_limit),
            'memory_usage_percentage' => round(($memory_usage / $memory_limit) * 100, 2) . '%',
            'load_average' => $load_average ? $load_average : 'Not available',
            'check_time' => round($total_time * 1000, 2) . 'ms'
        ];
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes Bytes
     * @return string Formatted bytes
     */
    private function format_bytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Convert memory limit to bytes
     * 
     * @param string $value Memory limit value
     * @return int Bytes
     */
    private function convert_to_bytes($value): int {
        $unit = strtolower(substr($value, -1));
        $num = (int) $value;
        
        switch ($unit) {
            case 'g': $num *= 1024;
            case 'm': $num *= 1024;
            case 'k': $num *= 1024;
        }
        
        return $num;
    }
}