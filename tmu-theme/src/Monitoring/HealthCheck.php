<?php
/**
 * Health Check and Monitoring
 * 
 * System health monitoring and status checks.
 * 
 * @package TMU\Monitoring
 * @since 1.0.0
 */

namespace TMU\Monitoring;

/**
 * HealthCheck class
 * 
 * Performs system health checks and monitoring
 */
class HealthCheck {
    
    /**
     * Run comprehensive health check
     * 
     * @return array Health check results
     */
    public function run_health_check(): array {
        $checks = [
            'database' => $this->check_database_connection(),
            'tmdb_api' => $this->check_tmdb_api(),
            'file_permissions' => $this->check_file_permissions(),
            'memory_usage' => $this->check_memory_usage(),
            'disk_space' => $this->check_disk_space(),
            'cache_status' => $this->check_cache_status()
        ];
        
        $overall_status = !in_array(false, $checks, true) ? 'healthy' : 'unhealthy';
        
        return [
            'status' => $overall_status,
            'timestamp' => current_time('mysql'),
            'checks' => $checks
        ];
    }
    
    /**
     * Check database connection
     * 
     * @return bool Database connection status
     */
    private function check_database_connection(): bool {
        global $wpdb;
        
        try {
            $wpdb->get_var("SELECT 1");
            return true;
        } catch (Exception $e) {
            error_log("Database health check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check TMDB API connectivity
     * 
     * @return bool TMDB API status
     */
    private function check_tmdb_api(): bool {
        if (!get_option('tmu_tmdb_api_key')) {
            return true; // Not configured, so not an error
        }
        
        try {
            if (class_exists('TMU\\TMDB\\TMDBClient')) {
                $client = new \TMU\TMDB\TMDBClient();
                $result = $client->get_movie_details(550); // Test with Fight Club
                return !empty($result['id']);
            }
            return true; // Skip if class doesn't exist
        } catch (Exception $e) {
            error_log("TMDB API health check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check file permissions
     * 
     * @return bool File permissions status
     */
    private function check_file_permissions(): bool {
        $upload_dir = wp_upload_dir();
        
        return is_writable($upload_dir['basedir']);
    }
    
    /**
     * Check memory usage
     * 
     * @return bool Memory usage status
     */
    private function check_memory_usage(): bool {
        $memory_limit = $this->convert_to_bytes(ini_get('memory_limit'));
        $memory_usage = memory_get_usage(true);
        
        return ($memory_usage / $memory_limit) < 0.8; // Less than 80%
    }
    
    /**
     * Check disk space
     * 
     * @return bool Disk space status
     */
    private function check_disk_space(): bool {
        $upload_dir = wp_upload_dir();
        $free_bytes = disk_free_space($upload_dir['basedir']);
        $total_bytes = disk_total_space($upload_dir['basedir']);
        
        if ($free_bytes === false || $total_bytes === false) {
            return false;
        }
        
        return ($free_bytes / $total_bytes) > 0.1; // More than 10% free
    }
    
    /**
     * Check cache status
     * 
     * @return bool Cache status
     */
    private function check_cache_status(): bool {
        // Check if object cache is working
        $test_key = 'tmu_cache_test_' . time();
        $test_value = 'cache_working';
        
        wp_cache_set($test_key, $test_value);
        $cached_value = wp_cache_get($test_key);
        
        wp_cache_delete($test_key);
        
        return $cached_value === $test_value;
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
    
    /**
     * Check WordPress core integrity
     * 
     * @return bool WordPress core status
     */
    public function check_wordpress_core(): bool {
        // Check if WordPress core files are intact
        $core_files = [
            ABSPATH . 'wp-config.php',
            ABSPATH . 'wp-load.php',
            ABSPATH . 'wp-settings.php'
        ];
        
        foreach ($core_files as $file) {
            if (!file_exists($file)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check theme integrity
     * 
     * @return bool Theme integrity status
     */
    public function check_theme_integrity(): bool {
        $theme_path = get_template_directory();
        
        $required_files = [
            $theme_path . '/style.css',
            $theme_path . '/functions.php',
            $theme_path . '/index.php'
        ];
        
        foreach ($required_files as $file) {
            if (!file_exists($file)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check plugin dependencies
     * 
     * @return bool Plugin dependencies status
     */
    public function check_plugin_dependencies(): bool {
        // Check if required plugins are active
        $required_plugins = [];
        
        foreach ($required_plugins as $plugin) {
            if (!is_plugin_active($plugin)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check security status
     * 
     * @return bool Security status
     */
    public function check_security_status(): bool {
        $security_checks = [
            // Check if admin user has strong password (placeholder)
            'admin_password' => true,
            
            // Check if WordPress is up to date
            'wp_version' => !function_exists('get_core_updates') || empty(get_core_updates()),
            
            // Check if theme is up to date
            'theme_version' => get_option('tmu_theme_version') === '2.0.0',
            
            // Check file permissions
            'file_permissions' => !is_writable(ABSPATH . 'wp-config.php')
        ];
        
        return !in_array(false, $security_checks, true);
    }
    
    /**
     * Get system information
     * 
     * @return array System information
     */
    public function get_system_info(): array {
        global $wp_version;
        
        return [
            'php_version' => PHP_VERSION,
            'wp_version' => $wp_version,
            'theme_version' => get_option('tmu_theme_version', '1.0.0'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'mysql_version' => $this->get_mysql_version()
        ];
    }
    
    /**
     * Get MySQL version
     * 
     * @return string MySQL version
     */
    private function get_mysql_version(): string {
        global $wpdb;
        
        try {
            return $wpdb->get_var("SELECT VERSION()");
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
    
    /**
     * Get performance metrics
     * 
     * @return array Performance metrics
     */
    public function get_performance_metrics(): array {
        $start_time = microtime(true);
        
        // Test database query performance
        global $wpdb;
        $db_start = microtime(true);
        $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}");
        $db_time = microtime(true) - $db_start;
        
        // Test cache performance
        $cache_start = microtime(true);
        $test_key = 'perf_test_' . time();
        wp_cache_set($test_key, 'test_value');
        wp_cache_get($test_key);
        wp_cache_delete($test_key);
        $cache_time = microtime(true) - $cache_start;
        
        $total_time = microtime(true) - $start_time;
        
        return [
            'database_query_time' => round($db_time * 1000, 2) . 'ms',
            'cache_operation_time' => round($cache_time * 1000, 2) . 'ms',
            'total_check_time' => round($total_time * 1000, 2) . 'ms',
            'memory_usage' => $this->format_bytes(memory_get_usage(true)),
            'peak_memory' => $this->format_bytes(memory_get_peak_usage(true))
        ];
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
     * Log health check results
     * 
     * @param array $results Health check results
     * @return void
     */
    public function log_health_check(array $results): void {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'status' => $results['status'],
            'checks' => $results['checks']
        ];
        
        // Store in transient for recent history
        $recent_checks = get_transient('tmu_health_check_history') ?: [];
        array_unshift($recent_checks, $log_entry);
        
        // Keep only last 10 checks
        $recent_checks = array_slice($recent_checks, 0, 10);
        
        set_transient('tmu_health_check_history', $recent_checks, HOUR_IN_SECONDS);
        
        // Log critical issues
        if ($results['status'] === 'unhealthy') {
            error_log('TMU Theme Health Check Failed: ' . wp_json_encode($results));
        }
    }
    
    /**
     * Get health check history
     * 
     * @return array Health check history
     */
    public function get_health_check_history(): array {
        return get_transient('tmu_health_check_history') ?: [];
    }
    
    /**
     * Clear health check history
     * 
     * @return void
     */
    public function clear_health_check_history(): void {
        delete_transient('tmu_health_check_history');
    }
}