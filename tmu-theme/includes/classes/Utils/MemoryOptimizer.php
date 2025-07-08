<?php
/**
 * Memory Optimizer Class
 * 
 * Memory optimization utilities as specified in Step 19 documentation
 * 
 * @package TMU\Utils
 * @since 1.0.0
 */

namespace TMU\Utils;

/**
 * Memory Optimizer Class
 * 
 * Implements memory optimization function from Step 19 documentation
 * lines 572-588 for handling memory exhaustion issues
 */
class MemoryOptimizer {
    
    /**
     * Default memory limit increase
     * 
     * @var string
     */
    private string $default_memory_limit = '256M';
    
    /**
     * Optimize memory usage as specified in Step 19 documentation
     * 
     * This function implements the exact code from Step 19 documentation
     * for memory optimization and garbage collection
     * 
     * @return void
     */
    public function optimize_memory_usage(): void {
        // Increase memory limit temporarily
        ini_set('memory_limit', $this->default_memory_limit);
        
        // Clear object cache periodically
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
    
    /**
     * Set custom memory limit
     * 
     * @param string $limit Memory limit (e.g., '512M', '1G')
     * @return void
     */
    public function set_memory_limit(string $limit): void {
        $this->default_memory_limit = $limit;
        ini_set('memory_limit', $limit);
    }
    
    /**
     * Get current memory usage
     * 
     * @return array Memory usage statistics
     */
    public function get_memory_usage(): array {
        return [
            'current_usage' => memory_get_usage(true),
            'current_usage_formatted' => $this->format_bytes(memory_get_usage(true)),
            'peak_usage' => memory_get_peak_usage(true),
            'peak_usage_formatted' => $this->format_bytes(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit'),
            'usage_percentage' => $this->calculate_usage_percentage()
        ];
    }
    
    /**
     * Check if memory usage is approaching limit
     * 
     * @param float $threshold Threshold percentage (default 80%)
     * @return bool True if approaching limit
     */
    public function is_approaching_limit(float $threshold = 80.0): bool {
        $usage_percentage = $this->calculate_usage_percentage();
        return $usage_percentage >= $threshold;
    }
    
    /**
     * Calculate memory usage percentage
     * 
     * @return float Usage percentage
     */
    private function calculate_usage_percentage(): float {
        $current = memory_get_usage(true);
        $limit = $this->parse_memory_limit(ini_get('memory_limit'));
        
        if ($limit <= 0) {
            return 0.0;
        }
        
        return ($current / $limit) * 100;
    }
    
    /**
     * Parse memory limit string to bytes
     * 
     * @param string $limit Memory limit string
     * @return int Memory limit in bytes
     */
    private function parse_memory_limit(string $limit): int {
        $limit = trim($limit);
        $last_char = strtolower(substr($limit, -1));
        $value = (int) $limit;
        
        switch ($last_char) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
                break;
        }
        
        return $value;
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes Number of bytes
     * @return string Formatted string
     */
    private function format_bytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        
        return number_format($bytes / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }
    
    /**
     * Monitor memory usage during operation
     * 
     * @param callable $callback Function to monitor
     * @return array Operation results with memory stats
     */
    public function monitor_operation(callable $callback): array {
        $start_memory = memory_get_usage(true);
        $start_peak = memory_get_peak_usage(true);
        
        $result = call_user_func($callback);
        
        $end_memory = memory_get_usage(true);
        $end_peak = memory_get_peak_usage(true);
        
        return [
            'result' => $result,
            'memory_stats' => [
                'start_usage' => $start_memory,
                'end_usage' => $end_memory,
                'usage_diff' => $end_memory - $start_memory,
                'start_peak' => $start_peak,
                'end_peak' => $end_peak,
                'peak_diff' => $end_peak - $start_peak,
                'formatted' => [
                    'usage_diff' => $this->format_bytes($end_memory - $start_memory),
                    'peak_diff' => $this->format_bytes($end_peak - $start_peak)
                ]
            ]
        ];
    }
    
    /**
     * Clear specific cache groups
     * 
     * @param array $groups Cache groups to clear
     * @return void
     */
    public function clear_cache_groups(array $groups): void {
        foreach ($groups as $group) {
            if (function_exists('wp_cache_flush_group')) {
                wp_cache_flush_group($group);
            }
        }
    }
    
    /**
     * Perform comprehensive memory cleanup
     * 
     * @return array Cleanup results
     */
    public function comprehensive_cleanup(): array {
        $before = $this->get_memory_usage();
        
        // Clear various caches
        $this->clear_cache_groups(['tmu_movies', 'tmu_tv', 'tmu_dramas', 'tmu_people']);
        
        // Clear WordPress caches
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear transients
        $this->clear_expired_transients();
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            $collected = gc_collect_cycles();
        } else {
            $collected = 0;
        }
        
        $after = $this->get_memory_usage();
        
        return [
            'before' => $before,
            'after' => $after,
            'freed' => $before['current_usage'] - $after['current_usage'],
            'freed_formatted' => $this->format_bytes($before['current_usage'] - $after['current_usage']),
            'cycles_collected' => $collected
        ];
    }
    
    /**
     * Clear expired transients
     * 
     * @return int Number of transients cleared
     */
    private function clear_expired_transients(): int {
        global $wpdb;
        
        $expired = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_%' 
             AND option_value < UNIX_TIMESTAMP()"
        );
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_%' 
             AND option_name NOT LIKE '_transient_timeout_%'
             AND option_name NOT IN (
                 SELECT REPLACE(option_name, '_timeout', '') 
                 FROM {$wpdb->options} 
                 WHERE option_name LIKE '_transient_timeout_%'
             )"
        );
        
        return $expired ?: 0;
    }
    
    /**
     * Set up automatic memory monitoring
     * 
     * @param float $threshold Memory usage threshold for alerts
     * @return void
     */
    public function setup_monitoring(float $threshold = 80.0): void {
        add_action('wp_footer', function() use ($threshold) {
            if ($this->is_approaching_limit($threshold)) {
                error_log('TMU Theme: Memory usage approaching limit - ' . $this->get_memory_usage()['usage_percentage'] . '%');
                $this->optimize_memory_usage();
            }
        });
    }
}