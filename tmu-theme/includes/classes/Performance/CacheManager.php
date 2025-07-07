<?php
/**
 * Cache Manager - Comprehensive Caching System
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
 * Advanced Cache Management System
 */
class CacheManager {
    
    /**
     * Cache groups
     */
    private array $cache_groups = [
        'tmu_movies',
        'tmu_tv_series',
        'tmu_people',
        'tmu_dramas',
        'tmu_search',
        'tmu_recommendations',
        'tmu_fragments',
        'tmu_api_responses'
    ];
    
    /**
     * Default cache expiry times
     */
    private array $cache_expiry = [
        'short' => 300,     // 5 minutes
        'medium' => 1800,   // 30 minutes
        'long' => 3600,     // 1 hour
        'daily' => 86400,   // 24 hours
        'weekly' => 604800  // 7 days
    ];
    
    /**
     * Fragment cache instance
     */
    private FragmentCache $fragment_cache;
    
    /**
     * Object cache instance
     */
    private ObjectCache $object_cache;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->fragment_cache = new FragmentCache();
        $this->object_cache = new ObjectCache();
        
        $this->init();
    }
    
    /**
     * Initialize cache manager
     */
    public function init(): void {
        // Register cache groups as global
        wp_cache_add_global_groups($this->cache_groups);
        
        // Hook into WordPress events
        add_action('save_post', [$this, 'invalidate_post_cache'], 10, 2);
        add_action('delete_post', [$this, 'invalidate_post_cache'], 10, 2);
        add_action('wp_update_nav_menu', [$this, 'invalidate_navigation_cache']);
        add_action('customize_save_after', [$this, 'invalidate_customizer_cache']);
        
        // Hook into TMDB sync events
        add_action('tmu_tmdb_sync_complete', [$this, 'invalidate_content_cache']);
        add_action('tmu_movie_updated', [$this, 'invalidate_movie_cache']);
        add_action('tmu_tv_updated', [$this, 'invalidate_tv_cache']);
        add_action('tmu_person_updated', [$this, 'invalidate_person_cache']);
        
        // Admin cache management
        add_action('admin_post_tmu_clear_cache', [$this, 'admin_clear_cache']);
        add_action('wp_ajax_tmu_cache_stats', [$this, 'get_cache_stats']);
        
        // Preload critical content
        add_action('init', [$this, 'preload_critical_content']);
        
        // Cache warming scheduler
        add_action('tmu_cache_warming', [$this, 'warm_cache']);
        if (!wp_next_scheduled('tmu_cache_warming')) {
            wp_schedule_event(time(), 'hourly', 'tmu_cache_warming');
        }
    }
    
    /**
     * Get cached data with fallback
     */
    public function get($key, $group = 'default', $callback = null, $expiry = null): mixed {
        $cached_data = wp_cache_get($key, $group);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // If callback provided, execute and cache result
        if ($callback && is_callable($callback)) {
            $data = $callback();
            $expiry = $expiry ?? $this->cache_expiry['medium'];
            
            wp_cache_set($key, $data, $group, $expiry);
            
            return $data;
        }
        
        return false;
    }
    
    /**
     * Set cache data
     */
    public function set($key, $data, $group = 'default', $expiry = null): bool {
        $expiry = $expiry ?? $this->cache_expiry['medium'];
        
        return wp_cache_set($key, $data, $group, $expiry);
    }
    
    /**
     * Delete cache data
     */
    public function delete($key, $group = 'default'): bool {
        return wp_cache_delete($key, $group);
    }
    
    /**
     * Cache movie data
     */
    public function cache_movie_data($post_id): array {
        return $this->get(
            "movie_data_{$post_id}",
            'tmu_movies',
            function() use ($post_id) {
                global $wpdb;
                
                $movie_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}tmu_movies WHERE post_id = %d",
                    $post_id
                ), ARRAY_A);
                
                if ($movie_data) {
                    // Enhance with additional data
                    $movie_data['genres'] = wp_get_post_terms($post_id, 'genre', ['fields' => 'names']);
                    $movie_data['countries'] = wp_get_post_terms($post_id, 'country', ['fields' => 'names']);
                    $movie_data['languages'] = wp_get_post_terms($post_id, 'language', ['fields' => 'names']);
                    $movie_data['permalink'] = get_permalink($post_id);
                    $movie_data['thumbnail'] = get_the_post_thumbnail_url($post_id, 'medium');
                }
                
                return $movie_data ?: [];
            },
            $this->cache_expiry['long']
        );
    }
    
    /**
     * Cache TV series data
     */
    public function cache_tv_data($post_id): array {
        return $this->get(
            "tv_data_{$post_id}",
            'tmu_tv_series',
            function() use ($post_id) {
                global $wpdb;
                
                $tv_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}tmu_tv_series WHERE post_id = %d",
                    $post_id
                ), ARRAY_A);
                
                if ($tv_data) {
                    // Enhance with additional data
                    $tv_data['genres'] = wp_get_post_terms($post_id, 'genre', ['fields' => 'names']);
                    $tv_data['networks'] = wp_get_post_terms($post_id, 'network', ['fields' => 'names']);
                    $tv_data['countries'] = wp_get_post_terms($post_id, 'country', ['fields' => 'names']);
                    $tv_data['permalink'] = get_permalink($post_id);
                    $tv_data['thumbnail'] = get_the_post_thumbnail_url($post_id, 'medium');
                }
                
                return $tv_data ?: [];
            },
            $this->cache_expiry['long']
        );
    }
    
    /**
     * Cache person data
     */
    public function cache_person_data($post_id): array {
        return $this->get(
            "person_data_{$post_id}",
            'tmu_people',
            function() use ($post_id) {
                global $wpdb;
                
                $person_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}tmu_people WHERE post_id = %d",
                    $post_id
                ), ARRAY_A);
                
                if ($person_data) {
                    // Enhance with additional data
                    $person_data['permalink'] = get_permalink($post_id);
                    $person_data['profile'] = get_the_post_thumbnail_url($post_id, 'medium');
                    
                    // Get filmography
                    $person_data['filmography'] = $this->get_person_filmography($post_id);
                }
                
                return $person_data ?: [];
            },
            $this->cache_expiry['long']
        );
    }
    
    /**
     * Cache TMDB API responses
     */
    public function cache_tmdb_response($endpoint, $params, $data): void {
        $cache_key = 'tmdb_' . md5($endpoint . serialize($params));
        
        $this->set($cache_key, $data, 'tmu_api_responses', $this->cache_expiry['daily']);
    }
    
    /**
     * Get cached TMDB response
     */
    public function get_tmdb_response($endpoint, $params): mixed {
        $cache_key = 'tmdb_' . md5($endpoint . serialize($params));
        
        return $this->get($cache_key, 'tmu_api_responses');
    }
    
    /**
     * Cache search results
     */
    public function cache_search_results($query, $filters, $results): void {
        $cache_key = 'search_' . md5($query . serialize($filters));
        
        $this->set($cache_key, $results, 'tmu_search', $this->cache_expiry['short']);
    }
    
    /**
     * Get cached search results
     */
    public function get_search_results($query, $filters): mixed {
        $cache_key = 'search_' . md5($query . serialize($filters));
        
        return $this->get($cache_key, 'tmu_search');
    }
    
    /**
     * Fragment caching wrapper
     */
    public function cache_fragment($key, $callback, $expiry = null): string {
        return $this->fragment_cache->cache_fragment($key, $callback, $expiry);
    }
    
    /**
     * Invalidate post cache
     */
    public function invalidate_post_cache($post_id, $post = null): void {
        if (!$post) {
            $post = get_post($post_id);
        }
        
        if (!$post) {
            return;
        }
        
        $post_type = $post->post_type;
        
        switch ($post_type) {
            case 'movie':
                $this->delete("movie_data_{$post_id}", 'tmu_movies');
                wp_cache_flush_group('tmu_movies');
                break;
                
            case 'tv':
            case 'drama':
                $this->delete("tv_data_{$post_id}", 'tmu_tv_series');
                wp_cache_flush_group('tmu_tv_series');
                break;
                
            case 'people':
                $this->delete("person_data_{$post_id}", 'tmu_people');
                wp_cache_flush_group('tmu_people');
                break;
        }
        
        // Clear related fragment cache
        $this->fragment_cache->invalidate_post_fragments($post_id);
        
        // Clear search cache that might contain this post
        wp_cache_flush_group('tmu_search');
        wp_cache_flush_group('tmu_recommendations');
    }
    
    /**
     * Invalidate navigation cache
     */
    public function invalidate_navigation_cache(): void {
        wp_cache_flush_group('tmu_fragments');
        $this->delete('navigation_menu', 'tmu_fragments');
    }
    
    /**
     * Invalidate customizer cache
     */
    public function invalidate_customizer_cache(): void {
        wp_cache_flush_group('tmu_fragments');
        $this->delete('theme_options', 'tmu_fragments');
    }
    
    /**
     * Invalidate content cache
     */
    public function invalidate_content_cache(): void {
        // Clear all content-related cache groups
        foreach (['tmu_movies', 'tmu_tv_series', 'tmu_people', 'tmu_dramas'] as $group) {
            wp_cache_flush_group($group);
        }
        
        // Clear related caches
        wp_cache_flush_group('tmu_search');
        wp_cache_flush_group('tmu_recommendations');
        wp_cache_flush_group('tmu_fragments');
    }
    
    /**
     * Invalidate movie cache
     */
    public function invalidate_movie_cache($post_id): void {
        $this->delete("movie_data_{$post_id}", 'tmu_movies');
        $this->fragment_cache->invalidate_post_fragments($post_id);
    }
    
    /**
     * Invalidate TV cache
     */
    public function invalidate_tv_cache($post_id): void {
        $this->delete("tv_data_{$post_id}", 'tmu_tv_series');
        $this->fragment_cache->invalidate_post_fragments($post_id);
    }
    
    /**
     * Invalidate person cache
     */
    public function invalidate_person_cache($post_id): void {
        $this->delete("person_data_{$post_id}", 'tmu_people');
        $this->fragment_cache->invalidate_post_fragments($post_id);
    }
    
    /**
     * Admin clear cache
     */
    public function admin_clear_cache(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'tmu'));
        }
        
        check_admin_referer('tmu_clear_cache');
        
        $this->clear_all_cache();
        
        wp_redirect(add_query_arg('cache_cleared', '1', wp_get_referer()));
        exit;
    }
    
    /**
     * Clear all cache
     */
    public function clear_all_cache(): void {
        // Clear object cache groups
        foreach ($this->cache_groups as $group) {
            wp_cache_flush_group($group);
        }
        
        // Clear WordPress transients
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_tmu_%' 
             OR option_name LIKE '_transient_timeout_tmu_%'"
        );
        
        // Clear file-based cache if exists
        $this->clear_file_cache();
        
        // Clear external cache if configured
        $this->clear_external_cache();
        
        do_action('tmu_cache_cleared');
    }
    
    /**
     * Get cache statistics
     */
    public function get_cache_stats(): array {
        $stats = [
            'object_cache_hits' => 0,
            'object_cache_misses' => 0,
            'fragment_cache_size' => 0,
            'api_cache_size' => 0,
            'total_cached_items' => 0
        ];
        
        // Get object cache stats if available
        if (function_exists('wp_cache_get_stats')) {
            $cache_stats = wp_cache_get_stats();
            $stats['object_cache_hits'] = $cache_stats['hits'] ?? 0;
            $stats['object_cache_misses'] = $cache_stats['misses'] ?? 0;
        }
        
        // Count cached items per group
        foreach ($this->cache_groups as $group) {
            $stats['groups'][$group] = $this->count_cache_group_items($group);
            $stats['total_cached_items'] += $stats['groups'][$group];
        }
        
        return $stats;
    }
    
    /**
     * Preload critical content
     */
    public function preload_critical_content(): void {
        // Only preload on frontend
        if (is_admin()) {
            return;
        }
        
        // Preload popular movies
        $popular_movies = get_posts([
            'post_type' => 'movie',
            'posts_per_page' => 10,
            'meta_key' => 'tmu_movie_popularity',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'fields' => 'ids'
        ]);
        
        foreach ($popular_movies as $movie_id) {
            $this->cache_movie_data($movie_id);
        }
        
        // Preload navigation menu
        $this->cache_fragment('navigation_menu', function() {
            return wp_nav_menu([
                'theme_location' => 'primary',
                'echo' => false
            ]);
        }, $this->cache_expiry['daily']);
    }
    
    /**
     * Warm cache
     */
    public function warm_cache(): void {
        // Warm most popular content
        $this->warm_popular_content();
        
        // Warm search results for common queries
        $this->warm_search_cache();
        
        // Warm recommendation cache
        $this->warm_recommendation_cache();
    }
    
    /**
     * Warm popular content
     */
    private function warm_popular_content(): void {
        $post_types = ['movie', 'tv', 'people'];
        
        foreach ($post_types as $post_type) {
            $posts = get_posts([
                'post_type' => $post_type,
                'posts_per_page' => 20,
                'meta_key' => 'tmu_movie_popularity',
                'orderby' => 'meta_value_num',
                'order' => 'DESC',
                'fields' => 'ids'
            ]);
            
            foreach ($posts as $post_id) {
                switch ($post_type) {
                    case 'movie':
                        $this->cache_movie_data($post_id);
                        break;
                    case 'tv':
                        $this->cache_tv_data($post_id);
                        break;
                    case 'people':
                        $this->cache_person_data($post_id);
                        break;
                }
            }
        }
    }
    
    /**
     * Warm search cache
     */
    private function warm_search_cache(): void {
        $common_queries = [
            'action', 'comedy', 'drama', 'thriller', 'horror',
            'marvel', 'disney', 'netflix', 'hbo'
        ];
        
        // This would integrate with the search system
        // For now, just placeholder
        foreach ($common_queries as $query) {
            // Warm search cache for common queries
            $cache_key = 'search_' . md5($query . serialize([]));
            if (!$this->get($cache_key, 'tmu_search')) {
                // Would perform actual search and cache results
                $this->set($cache_key, [], 'tmu_search', $this->cache_expiry['short']);
            }
        }
    }
    
    /**
     * Warm recommendation cache
     */
    private function warm_recommendation_cache(): void {
        $popular_movies = get_posts([
            'post_type' => 'movie',
            'posts_per_page' => 10,
            'meta_key' => 'tmu_movie_popularity',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'fields' => 'ids'
        ]);
        
        foreach ($popular_movies as $movie_id) {
            $cache_key = "similar_content_{$movie_id}_10";
            if (!$this->get($cache_key, 'tmu_recommendations')) {
                // Would generate recommendations and cache
                $this->set($cache_key, [], 'tmu_recommendations', $this->cache_expiry['long']);
            }
        }
    }
    
    /**
     * Clear file cache
     */
    private function clear_file_cache(): void {
        // Clear any file-based cache directories
        $cache_dirs = [
            get_template_directory() . '/cache/',
            WP_CONTENT_DIR . '/cache/tmu/'
        ];
        
        foreach ($cache_dirs as $cache_dir) {
            if (is_dir($cache_dir)) {
                $this->recursive_rmdir($cache_dir);
            }
        }
    }
    
    /**
     * Clear external cache
     */
    private function clear_external_cache(): void {
        // Clear Cloudflare cache if configured
        if (defined('TMU_CLOUDFLARE_API_KEY')) {
            $this->clear_cloudflare_cache();
        }
        
        // Clear other CDN cache if configured
        do_action('tmu_clear_external_cache');
    }
    
    /**
     * Get person filmography
     */
    private function get_person_filmography($person_id): array {
        global $wpdb;
        
        // This would query cast/crew relationships
        // For now, return empty array
        return [];
    }
    
    /**
     * Count cache group items
     */
    private function count_cache_group_items($group): int {
        // This is an approximation since we can't directly count cache items
        // In a production environment, you'd implement actual counting
        return 0;
    }
    
    /**
     * Recursive directory removal
     */
    private function recursive_rmdir($dir): bool {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->recursive_rmdir($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
    
    /**
     * Clear Cloudflare cache
     */
    private function clear_cloudflare_cache(): void {
        // Implement Cloudflare cache clearing
        // This would use Cloudflare API
    }
    
    /**
     * Get fragment cache instance
     */
    public function get_fragment_cache(): FragmentCache {
        return $this->fragment_cache;
    }
    
    /**
     * Get object cache instance
     */
    public function get_object_cache(): ObjectCache {
        return $this->object_cache;
    }
}

/**
 * Fragment Cache Helper Class
 */
class FragmentCache {
    
    private string $cache_group = 'tmu_fragments';
    private int $default_expiry = 1800; // 30 minutes
    
    /**
     * Cache fragment
     */
    public function cache_fragment($key, $callback, $expiry = null): string {
        $expiry = $expiry ?? $this->default_expiry;
        $cached_content = wp_cache_get($key, $this->cache_group);
        
        if ($cached_content !== false) {
            return $cached_content;
        }
        
        ob_start();
        $callback();
        $content = ob_get_clean();
        
        wp_cache_set($key, $content, $this->cache_group, $expiry);
        
        return $content;
    }
    
    /**
     * Cache movie card
     */
    public function cache_movie_card($movie_id): string {
        return $this->cache_fragment(
            "movie_card_{$movie_id}",
            function() use ($movie_id) {
                get_template_part('template-parts/movie-card', null, ['movie_id' => $movie_id]);
            }
        );
    }
    
    /**
     * Cache TV series card
     */
    public function cache_tv_series_card($tv_id): string {
        return $this->cache_fragment(
            "tv_series_card_{$tv_id}",
            function() use ($tv_id) {
                get_template_part('template-parts/tv-series-card', null, ['tv_id' => $tv_id]);
            }
        );
    }
    
    /**
     * Invalidate post fragments
     */
    public function invalidate_post_fragments($post_id): void {
        $post_type = get_post_type($post_id);
        
        // Clear specific post fragments
        wp_cache_delete("movie_card_{$post_id}", $this->cache_group);
        wp_cache_delete("tv_card_{$post_id}", $this->cache_group);
        wp_cache_delete("person_card_{$post_id}", $this->cache_group);
        wp_cache_delete("single_content_{$post_id}", $this->cache_group);
    }
}

/**
 * Object Cache Helper Class
 */
class ObjectCache {
    
    private string $cache_group = 'tmu_objects';
    private int $default_expiry = 3600; // 1 hour
    
    /**
     * Cache object data
     */
    public function cache_object($key, $data, $expiry = null): bool {
        $expiry = $expiry ?? $this->default_expiry;
        
        return wp_cache_set($key, $data, $this->cache_group, $expiry);
    }
    
    /**
     * Get cached object
     */
    public function get_object($key): mixed {
        return wp_cache_get($key, $this->cache_group);
    }
}