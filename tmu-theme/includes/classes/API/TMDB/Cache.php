<?php
/**
 * TMDB Cache Management
 * 
 * Caching system for TMDB API responses with intelligent cache invalidation,
 * performance optimization, and memory management capabilities.
 * 
 * @package TMU\API\TMDB
 * @since 1.0.0
 */

namespace TMU\API\TMDB;

/**
 * Cache class for TMDB API responses
 * 
 * Provides intelligent caching for TMDB API responses with TTL management
 */
class Cache {
    
    /**
     * Cache key prefix
     */
    const CACHE_PREFIX = 'tmu_tmdb_';
    
    /**
     * Default cache duration (1 hour)
     */
    const DEFAULT_DURATION = 3600;
    
    /**
     * Cache durations for different data types
     * 
     * @var array
     */
    private static $cacheDurations = [
        'movie_details' => 3600,      // 1 hour
        'tv_details' => 3600,         // 1 hour
        'person_details' => 7200,     // 2 hours
        'search_results' => 1800,     // 30 minutes
        'configuration' => 86400,     // 24 hours
        'genres' => 86400,            // 24 hours
        'countries' => 86400,         // 24 hours
        'languages' => 86400,         // 24 hours
        'popular_movies' => 3600,     // 1 hour
        'popular_tv' => 3600,         // 1 hour
        'trending' => 1800,           // 30 minutes
    ];
    
    /**
     * Maximum cache size (number of entries)
     */
    const MAX_CACHE_SIZE = 1000;
    
    /**
     * Get cached data
     * 
     * @param string $key Cache key
     * @param string $type Data type for TTL determination
     * @return mixed|false Cached data or false if not found/expired
     */
    public static function get(string $key, string $type = 'default') {
        $cache_key = self::generateCacheKey($key);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data === false) {
            return false;
        }
        
        // Check if cache is still valid
        if (isset($cached_data['expires_at']) && $cached_data['expires_at'] < time()) {
            self::delete($key);
            return false;
        }
        
        // Update access time for LRU tracking
        if (isset($cached_data['data'])) {
            $cached_data['last_accessed'] = time();
            set_transient($cache_key, $cached_data, self::getCacheDuration($type));
            return $cached_data['data'];
        }
        
        return false;
    }
    
    /**
     * Set cached data
     * 
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param string $type Data type for TTL determination
     * @param int|null $duration Custom cache duration in seconds
     * @return bool Success status
     */
    public static function set(string $key, $data, string $type = 'default', ?int $duration = null): bool {
        $cache_key = self::generateCacheKey($key);
        $cache_duration = $duration ?? self::getCacheDuration($type);
        
        // Prepare cache entry
        $cache_entry = [
            'data' => $data,
            'created_at' => time(),
            'last_accessed' => time(),
            'expires_at' => time() + $cache_duration,
            'type' => $type,
            'size' => self::calculateDataSize($data),
        ];
        
        // Check cache size and cleanup if necessary
        self::cleanupCacheIfNeeded();
        
        return set_transient($cache_key, $cache_entry, $cache_duration);
    }
    
    /**
     * Delete cached data
     * 
     * @param string $key Cache key
     * @return bool Success status
     */
    public static function delete(string $key): bool {
        $cache_key = self::generateCacheKey($key);
        return delete_transient($cache_key);
    }
    
    /**
     * Clear all TMDB cache
     * 
     * @return int Number of cache entries cleared
     */
    public static function clear(): int {
        global $wpdb;
        
        $count = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . '%',
                '_transient_timeout_' . self::CACHE_PREFIX . '%'
            )
        );
        
        return intval($count / 2); // Each transient has timeout entry
    }
    
    /**
     * Clear cache by type
     * 
     * @param string $type Cache type to clear
     * @return int Number of cache entries cleared
     */
    public static function clearByType(string $type): int {
        global $wpdb;
        
        // Get all cache keys of this type
        $cache_keys = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} 
                 WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . '%'
            )
        );
        
        $cleared = 0;
        foreach ($cache_keys as $cache) {
            $data = maybe_unserialize($cache->option_value);
            if (isset($data['type']) && $data['type'] === $type) {
                $key = str_replace('_transient_' . self::CACHE_PREFIX, '', $cache->option_name);
                if (self::delete($key)) {
                    $cleared++;
                }
            }
        }
        
        return $cleared;
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public static function getStats(): array {
        global $wpdb;
        
        $cache_entries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} 
                 WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . '%'
            )
        );
        
        $stats = [
            'total_entries' => 0,
            'total_size' => 0,
            'by_type' => [],
            'expired_entries' => 0,
            'memory_usage' => 0,
        ];
        
        $current_time = time();
        
        foreach ($cache_entries as $cache) {
            $data = maybe_unserialize($cache->option_value);
            
            if (!is_array($data) || !isset($data['type'])) {
                continue;
            }
            
            $stats['total_entries']++;
            
            if (isset($data['size'])) {
                $stats['total_size'] += $data['size'];
            }
            
            if (!isset($stats['by_type'][$data['type']])) {
                $stats['by_type'][$data['type']] = 0;
            }
            $stats['by_type'][$data['type']]++;
            
            if (isset($data['expires_at']) && $data['expires_at'] < $current_time) {
                $stats['expired_entries']++;
            }
        }
        
        $stats['memory_usage'] = memory_get_usage(true);
        
        return $stats;
    }
    
    /**
     * Warm up cache with frequently accessed data
     * 
     * @param array $items Items to warm up cache for
     * @return array Results of cache warming
     */
    public static function warmUp(array $items): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];
        
        foreach ($items as $item) {
            if (!isset($item['key']) || !isset($item['type'])) {
                $results['skipped']++;
                continue;
            }
            
            // Check if already cached
            if (self::get($item['key'], $item['type']) !== false) {
                $results['skipped']++;
                continue;
            }
            
            // This would be called by the client to actually fetch and cache data
            // For now, just mark as would-be-processed
            $results['success']++;
        }
        
        return $results;
    }
    
    /**
     * Generate cache key with prefix and hashing
     * 
     * @param string $key Original key
     * @return string Generated cache key
     */
    private static function generateCacheKey(string $key): string {
        return self::CACHE_PREFIX . md5($key);
    }
    
    /**
     * Get cache duration for data type
     * 
     * @param string $type Data type
     * @return int Cache duration in seconds
     */
    private static function getCacheDuration(string $type): int {
        return self::$cacheDurations[$type] ?? self::DEFAULT_DURATION;
    }
    
    /**
     * Calculate approximate data size
     * 
     * @param mixed $data Data to measure
     * @return int Approximate size in bytes
     */
    private static function calculateDataSize($data): int {
        return strlen(serialize($data));
    }
    
    /**
     * Cleanup cache if it exceeds size limits
     */
    private static function cleanupCacheIfNeeded(): void {
        global $wpdb;
        
        $cache_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . '%'
            )
        );
        
        if ($cache_count <= self::MAX_CACHE_SIZE) {
            return;
        }
        
        // Get cache entries with access times for LRU cleanup
        $cache_entries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 ORDER BY option_name",
                '_transient_' . self::CACHE_PREFIX . '%'
            )
        );
        
        $entries_to_remove = [];
        $current_time = time();
        
        foreach ($cache_entries as $cache) {
            $data = maybe_unserialize($cache->option_value);
            
            if (!is_array($data)) {
                continue;
            }
            
            // Remove expired entries first
            if (isset($data['expires_at']) && $data['expires_at'] < $current_time) {
                $entries_to_remove[] = [
                    'key' => str_replace('_transient_' . self::CACHE_PREFIX, '', $cache->option_name),
                    'priority' => 1, // High priority for removal
                    'last_accessed' => $data['last_accessed'] ?? 0,
                ];
            } else {
                // Add to LRU removal candidates
                $entries_to_remove[] = [
                    'key' => str_replace('_transient_' . self::CACHE_PREFIX, '', $cache->option_name),
                    'priority' => 2, // Lower priority
                    'last_accessed' => $data['last_accessed'] ?? 0,
                ];
            }
        }
        
        // Sort by priority then by last accessed time
        usort($entries_to_remove, function($a, $b) {
            if ($a['priority'] === $b['priority']) {
                return $a['last_accessed'] - $b['last_accessed'];
            }
            return $a['priority'] - $b['priority'];
        });
        
        // Remove oldest entries until under limit
        $to_remove = $cache_count - self::MAX_CACHE_SIZE + 10; // Remove extra for buffer
        for ($i = 0; $i < $to_remove && $i < count($entries_to_remove); $i++) {
            self::delete($entries_to_remove[$i]['key']);
        }
    }
    
    /**
     * Check if cache key exists
     * 
     * @param string $key Cache key
     * @return bool Whether key exists and is valid
     */
    public static function exists(string $key): bool {
        return self::get($key) !== false;
    }
    
    /**
     * Update cache TTL for existing entry
     * 
     * @param string $key Cache key
     * @param int $duration New TTL in seconds
     * @return bool Success status
     */
    public static function extend(string $key, int $duration): bool {
        $data = self::get($key);
        if ($data === false) {
            return false;
        }
        
        $cache_key = self::generateCacheKey($key);
        $cached_entry = get_transient($cache_key);
        
        if ($cached_entry && isset($cached_entry['data'])) {
            $cached_entry['expires_at'] = time() + $duration;
            return set_transient($cache_key, $cached_entry, $duration);
        }
        
        return false;
    }
    
    /**
     * Validate cache integrity
     * 
     * @return array Validation results
     */
    public static function validate(): array {
        global $wpdb;
        
        $validation = [
            'total_checked' => 0,
            'corrupted' => 0,
            'expired' => 0,
            'valid' => 0,
            'cleaned' => 0,
        ];
        
        $cache_entries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} 
                 WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . '%'
            )
        );
        
        $current_time = time();
        
        foreach ($cache_entries as $cache) {
            $validation['total_checked']++;
            
            $data = maybe_unserialize($cache->option_value);
            
            if (!is_array($data) || !isset($data['data'])) {
                $validation['corrupted']++;
                $key = str_replace('_transient_' . self::CACHE_PREFIX, '', $cache->option_name);
                self::delete($key);
                $validation['cleaned']++;
                continue;
            }
            
            if (isset($data['expires_at']) && $data['expires_at'] < $current_time) {
                $validation['expired']++;
                $key = str_replace('_transient_' . self::CACHE_PREFIX, '', $cache->option_name);
                self::delete($key);
                $validation['cleaned']++;
                continue;
            }
            
            $validation['valid']++;
        }
        
        return $validation;
    }
}