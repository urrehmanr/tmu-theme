<?php
/**
 * TMDB Rate Limiter
 * 
 * Rate limiting system for TMDB API requests to comply with API limits
 * and prevent overuse. Implements sliding window algorithm with intelligent backoff.
 * 
 * @package TMU\API\TMDB
 * @since 1.0.0
 */

namespace TMU\API\TMDB;

/**
 * RateLimiter class for TMDB API requests
 * 
 * Manages request rate limiting with sliding window algorithm
 */
class RateLimiter {
    
    /**
     * Rate limit cache key prefix
     */
    const RATE_LIMIT_PREFIX = 'tmu_tmdb_rate_';
    
    /**
     * TMDB API rate limits (requests per 10 seconds)
     */
    const DEFAULT_RATE_LIMIT = 40;
    
    /**
     * Time window in seconds
     */
    const TIME_WINDOW = 10;
    
    /**
     * Burst limit (initial burst allowance)
     */
    const BURST_LIMIT = 10;
    
    /**
     * Backoff multiplier for failed requests
     */
    const BACKOFF_MULTIPLIER = 2;
    
    /**
     * Maximum backoff time in seconds
     */
    const MAX_BACKOFF = 300; // 5 minutes
    
    /**
     * Request window tracking
     * 
     * @var array
     */
    private static $requestWindows = [];
    
    /**
     * Current rate limit
     * 
     * @var int
     */
    private static $rateLimit = self::DEFAULT_RATE_LIMIT;
    
    /**
     * Check if request is allowed
     * 
     * @param string $endpoint API endpoint for tracking
     * @return bool Whether request is allowed
     */
    public static function isAllowed(string $endpoint = 'default'): bool {
        $key = self::getRateLimitKey($endpoint);
        $current_time = time();
        
        // Get current window data
        $window_data = self::getWindowData($key);
        
        // Clean old requests outside time window
        $window_data = self::cleanOldRequests($window_data, $current_time);
        
        // Check if under rate limit
        if (count($window_data['requests']) >= self::$rateLimit) {
            // Check if we can use burst allowance
            if (!self::canUseBurst($window_data, $current_time)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Record a request
     * 
     * @param string $endpoint API endpoint
     * @param bool $success Whether request was successful
     * @return bool Whether request was recorded
     */
    public static function recordRequest(string $endpoint = 'default', bool $success = true): bool {
        $key = self::getRateLimitKey($endpoint);
        $current_time = time();
        
        $window_data = self::getWindowData($key);
        $window_data = self::cleanOldRequests($window_data, $current_time);
        
        // Record the request
        $window_data['requests'][] = [
            'time' => $current_time,
            'success' => $success,
        ];
        
        // Update failure tracking
        if (!$success) {
            $window_data['consecutive_failures']++;
            $window_data['last_failure'] = $current_time;
        } else {
            $window_data['consecutive_failures'] = 0;
        }
        
        // Update statistics
        $window_data['total_requests']++;
        if ($success) {
            $window_data['successful_requests']++;
        } else {
            $window_data['failed_requests']++;
        }
        
        return self::saveWindowData($key, $window_data);
    }
    
    /**
     * Get time until next request is allowed
     * 
     * @param string $endpoint API endpoint
     * @return int Seconds until next request allowed
     */
    public static function getWaitTime(string $endpoint = 'default'): int {
        if (self::isAllowed($endpoint)) {
            return 0;
        }
        
        $key = self::getRateLimitKey($endpoint);
        $window_data = self::getWindowData($key);
        $current_time = time();
        
        // Check for backoff due to failures
        $backoff_time = self::getBackoffTime($window_data, $current_time);
        if ($backoff_time > 0) {
            return $backoff_time;
        }
        
        // Calculate wait time based on rate limit
        if (empty($window_data['requests'])) {
            return 0;
        }
        
        // Find oldest request in current window
        $oldest_request = min(array_column($window_data['requests'], 'time'));
        $window_end = $oldest_request + self::TIME_WINDOW;
        
        return max(0, $window_end - $current_time);
    }
    
    /**
     * Reset rate limits for endpoint
     * 
     * @param string $endpoint API endpoint
     * @return bool Success status
     */
    public static function reset(string $endpoint = 'default'): bool {
        $key = self::getRateLimitKey($endpoint);
        return delete_transient($key);
    }
    
    /**
     * Get rate limit statistics
     * 
     * @param string $endpoint API endpoint
     * @return array Rate limit statistics
     */
    public static function getStats(string $endpoint = 'default'): array {
        $key = self::getRateLimitKey($endpoint);
        $window_data = self::getWindowData($key);
        $current_time = time();
        
        $window_data = self::cleanOldRequests($window_data, $current_time);
        
        $stats = [
            'endpoint' => $endpoint,
            'rate_limit' => self::$rateLimit,
            'time_window' => self::TIME_WINDOW,
            'current_requests' => count($window_data['requests']),
            'remaining_requests' => max(0, self::$rateLimit - count($window_data['requests'])),
            'wait_time' => self::getWaitTime($endpoint),
            'total_requests' => $window_data['total_requests'],
            'successful_requests' => $window_data['successful_requests'],
            'failed_requests' => $window_data['failed_requests'],
            'consecutive_failures' => $window_data['consecutive_failures'],
            'success_rate' => $window_data['total_requests'] > 0 
                ? round(($window_data['successful_requests'] / $window_data['total_requests']) * 100, 2) 
                : 0,
            'is_allowed' => self::isAllowed($endpoint),
            'window_reset_in' => 0,
        ];
        
        // Calculate window reset time
        if (!empty($window_data['requests'])) {
            $oldest_request = min(array_column($window_data['requests'], 'time'));
            $stats['window_reset_in'] = max(0, ($oldest_request + self::TIME_WINDOW) - $current_time);
        }
        
        return $stats;
    }
    
    /**
     * Set custom rate limit
     * 
     * @param int $limit New rate limit
     */
    public static function setRateLimit(int $limit): void {
        self::$rateLimit = max(1, $limit);
    }
    
    /**
     * Get current rate limit
     * 
     * @return int Current rate limit
     */
    public static function getRateLimit(): int {
        return self::$rateLimit;
    }
    
    /**
     * Clear all rate limit data
     * 
     * @return int Number of entries cleared
     */
    public static function clearAll(): int {
        global $wpdb;
        
        $count = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::RATE_LIMIT_PREFIX . '%'
            )
        );
        
        return intval($count);
    }
    
    /**
     * Get adaptive wait time based on system load
     * 
     * @param string $endpoint API endpoint
     * @return int Adaptive wait time in seconds
     */
    public static function getAdaptiveWaitTime(string $endpoint = 'default'): int {
        $base_wait = self::getWaitTime($endpoint);
        
        // Factor in system load
        $load_factor = self::getSystemLoadFactor();
        
        // Factor in current hour (reduce load during peak hours)
        $hour_factor = self::getHourFactor();
        
        $adaptive_wait = intval($base_wait * $load_factor * $hour_factor);
        
        return min($adaptive_wait, self::MAX_BACKOFF);
    }
    
    /**
     * Check if burst allowance can be used
     * 
     * @param array $window_data Window data
     * @param int $current_time Current timestamp
     * @return bool Whether burst can be used
     */
    private static function canUseBurst(array $window_data, int $current_time): bool {
        // No burst if there are recent failures
        if ($window_data['consecutive_failures'] > 0) {
            return false;
        }
        
        // Check if burst was recently used
        $burst_key = self::getRateLimitKey('burst_used');
        $last_burst = get_transient($burst_key);
        
        if ($last_burst && ($current_time - $last_burst) < 60) {
            return false; // Only allow burst once per minute
        }
        
        // Allow burst and record usage
        set_transient($burst_key, $current_time, 60);
        return true;
    }
    
    /**
     * Get backoff time for failed requests
     * 
     * @param array $window_data Window data
     * @param int $current_time Current timestamp
     * @return int Backoff time in seconds
     */
    private static function getBackoffTime(array $window_data, int $current_time): int {
        if ($window_data['consecutive_failures'] === 0) {
            return 0;
        }
        
        // Exponential backoff: 2^failures seconds, capped at MAX_BACKOFF
        $backoff = min(
            pow(self::BACKOFF_MULTIPLIER, $window_data['consecutive_failures']),
            self::MAX_BACKOFF
        );
        
        $time_since_failure = $current_time - $window_data['last_failure'];
        
        return max(0, $backoff - $time_since_failure);
    }
    
    /**
     * Get rate limit key for endpoint
     * 
     * @param string $endpoint API endpoint
     * @return string Rate limit key
     */
    private static function getRateLimitKey(string $endpoint): string {
        return self::RATE_LIMIT_PREFIX . md5($endpoint);
    }
    
    /**
     * Get window data for endpoint
     * 
     * @param string $key Rate limit key
     * @return array Window data
     */
    private static function getWindowData(string $key): array {
        $data = get_transient($key);
        
        if (!is_array($data)) {
            return [
                'requests' => [],
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'consecutive_failures' => 0,
                'last_failure' => 0,
                'created_at' => time(),
            ];
        }
        
        return $data;
    }
    
    /**
     * Save window data
     * 
     * @param string $key Rate limit key
     * @param array $data Window data
     * @return bool Success status
     */
    private static function saveWindowData(string $key, array $data): bool {
        // Keep data for at least one time window
        return set_transient($key, $data, self::TIME_WINDOW + 60);
    }
    
    /**
     * Clean old requests outside time window
     * 
     * @param array $window_data Window data
     * @param int $current_time Current timestamp
     * @return array Cleaned window data
     */
    private static function cleanOldRequests(array $window_data, int $current_time): array {
        $cutoff_time = $current_time - self::TIME_WINDOW;
        
        $window_data['requests'] = array_filter(
            $window_data['requests'],
            function($request) use ($cutoff_time) {
                return $request['time'] > $cutoff_time;
            }
        );
        
        // Reset array keys
        $window_data['requests'] = array_values($window_data['requests']);
        
        return $window_data;
    }
    
    /**
     * Get system load factor for adaptive waiting
     * 
     * @return float Load factor (1.0 = normal, >1.0 = higher load)
     */
    private static function getSystemLoadFactor(): float {
        // Simple memory-based load factor
        $memory_usage = memory_get_usage(true);
        $memory_limit = self::parseMemoryLimit(ini_get('memory_limit'));
        
        if ($memory_limit > 0) {
            $memory_percentage = $memory_usage / $memory_limit;
            
            if ($memory_percentage > 0.8) {
                return 2.0; // High memory usage, slow down
            } elseif ($memory_percentage > 0.6) {
                return 1.5; // Medium memory usage, moderate slowdown
            }
        }
        
        return 1.0; // Normal load
    }
    
    /**
     * Get hour factor for time-based rate adjustment
     * 
     * @return float Hour factor (1.0 = normal, >1.0 = peak hours)
     */
    private static function getHourFactor(): float {
        $hour = intval(date('H'));
        
        // Peak hours (9 AM - 5 PM): slightly slower
        if ($hour >= 9 && $hour <= 17) {
            return 1.2;
        }
        
        // Off-peak hours: normal speed
        return 1.0;
    }
    
    /**
     * Parse memory limit string to bytes
     * 
     * @param string $memory_limit Memory limit string (e.g., '256M')
     * @return int Memory limit in bytes
     */
    private static function parseMemoryLimit(string $memory_limit): int {
        $memory_limit = trim($memory_limit);
        $last_char = strtolower($memory_limit[strlen($memory_limit) - 1]);
        $number = intval($memory_limit);
        
        switch ($last_char) {
            case 'g':
                $number *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $number *= 1024 * 1024;
                break;
            case 'k':
                $number *= 1024;
                break;
        }
        
        return $number;
    }
    
    /**
     * Force wait for specified time
     * 
     * @param int $seconds Seconds to wait
     */
    public static function wait(int $seconds): void {
        if ($seconds > 0) {
            sleep(min($seconds, 30)); // Cap at 30 seconds for safety
        }
    }
    
    /**
     * Get all endpoint statistics
     * 
     * @return array All endpoint statistics
     */
    public static function getAllStats(): array {
        global $wpdb;
        
        $cache_entries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::RATE_LIMIT_PREFIX . '%'
            )
        );
        
        $all_stats = [];
        
        foreach ($cache_entries as $cache) {
            $endpoint = str_replace('_transient_' . self::RATE_LIMIT_PREFIX, '', $cache->option_name);
            $endpoint = substr($endpoint, 32); // Remove MD5 hash length
            
            if ($endpoint !== 'burst_used') {
                $all_stats[] = self::getStats($endpoint);
            }
        }
        
        return $all_stats;
    }
}