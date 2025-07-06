# Step 13: Performance Optimization

## Purpose
Implement comprehensive performance optimization including caching, database optimization, image optimization, and CDN integration for optimal site speed.

## Dependencies from Previous Steps
- **[REQUIRED]** All previous systems [FROM STEPS 1-12] - Performance optimization targets
- **[REQUIRED]** Asset compilation [FROM STEP 1] - Asset optimization
- **[REQUIRED]** Database system [FROM STEP 3] - Database query optimization
- **[REQUIRED]** TMDB API [FROM STEP 9] - API response caching

## Files Created in This Step
- **[CREATE NEW]** `includes/classes/Performance/CacheManager.php` - Caching system
- **[CREATE NEW]** `includes/classes/Performance/DatabaseOptimizer.php` - Database optimization
- **[CREATE NEW]** `includes/classes/Performance/ImageOptimizer.php` - Image compression
- **[CREATE NEW]** `includes/classes/Performance/CDNManager.php` - CDN integration
- **[CREATE NEW]** `includes/classes/Performance/LazyLoader.php` - Lazy loading system

## Tailwind CSS Status
**CONFIGURES** - Performance optimization for Tailwind CSS compilation and purging

**Step 13 Status**: ✅ READY FOR AI IMPLEMENTATION
**Dependencies**: Steps 1-12 must be completed
**Next Step**: Step 14 - Security and Accessibility

## Overview
This step implements comprehensive performance optimization strategies for the TMU theme, focusing on database optimization, caching mechanisms, asset optimization, and server-side performance enhancements.

## 1. Database Optimization

### 1.1 Query Optimization
```php
// src/Performance/QueryOptimizer.php
<?php
namespace TMU\Performance;

class QueryOptimizer {
    public function __construct() {
        add_action('init', [$this, 'optimize_queries']);
        add_action('pre_get_posts', [$this, 'optimize_main_query']);
    }
    
    public function optimize_queries(): void {
        // Optimize custom post type queries
        add_filter('posts_clauses', [$this, 'optimize_movie_queries'], 10, 2);
        add_filter('posts_clauses', [$this, 'optimize_tv_queries'], 10, 2);
        add_filter('posts_clauses', [$this, 'optimize_drama_queries'], 10, 2);
        
        // Add database indexes
        add_action('after_switch_theme', [$this, 'create_database_indexes']);
    }
    
    public function optimize_movie_queries($clauses, $query): array {
        global $wpdb;
        
        if (!is_admin() && $query->is_main_query() && $query->get('post_type') === 'movie') {
            // Join with custom table for better performance
            $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}tmu_movies tm ON {$wpdb->posts}.ID = tm.post_id";
            
            // Add commonly used fields to SELECT
            $clauses['fields'] .= ", tm.tmdb_id, tm.release_date, tm.runtime, tm.tmdb_vote_average";
            
            // Optimize ordering
            if ($query->get('orderby') === 'release_date') {
                $clauses['orderby'] = 'tm.release_date DESC';
            } elseif ($query->get('orderby') === 'rating') {
                $clauses['orderby'] = 'tm.tmdb_vote_average DESC';
            }
        }
        
        return $clauses;
    }
    
    public function create_database_indexes(): void {
        global $wpdb;
        
        // Create indexes for better query performance
        $indexes = [
            "CREATE INDEX idx_tmu_movies_tmdb_id ON {$wpdb->prefix}tmu_movies (tmdb_id)",
            "CREATE INDEX idx_tmu_movies_release_date ON {$wpdb->prefix}tmu_movies (release_date)",
            "CREATE INDEX idx_tmu_movies_rating ON {$wpdb->prefix}tmu_movies (tmdb_vote_average)",
            "CREATE INDEX idx_tmu_tv_series_tmdb_id ON {$wpdb->prefix}tmu_tv_series (tmdb_id)",
            "CREATE INDEX idx_tmu_tv_series_first_air_date ON {$wpdb->prefix}tmu_tv_series (first_air_date)",
            "CREATE INDEX idx_tmu_dramas_tmdb_id ON {$wpdb->prefix}tmu_dramas (tmdb_id)",
            "CREATE INDEX idx_tmu_people_tmdb_id ON {$wpdb->prefix}tmu_people (tmdb_id)",
        ];
        
        foreach ($indexes as $index) {
            $wpdb->query($index);
        }
    }
}
```

### 1.2 Database Connection Optimization
```php
// src/Performance/DatabaseOptimizer.php
<?php
namespace TMU\Performance;

class DatabaseOptimizer {
    public function __construct() {
        add_action('init', [$this, 'optimize_database_connections']);
    }
    
    public function optimize_database_connections(): void {
        // Enable persistent connections
        if (!defined('DB_PERSISTENT')) {
            define('DB_PERSISTENT', true);
        }
        
        // Optimize MySQL settings
        add_action('wp_loaded', [$this, 'set_mysql_optimizations']);
    }
    
    public function set_mysql_optimizations(): void {
        global $wpdb;
        
        // Set optimal MySQL settings
        $wpdb->query("SET SESSION query_cache_type = ON");
        $wpdb->query("SET SESSION query_cache_size = 32M");
        $wpdb->query("SET SESSION innodb_buffer_pool_size = 128M");
    }
}
```

## 2. Caching System

### 2.1 Object Cache Implementation
```php
// src/Performance/ObjectCache.php
<?php
namespace TMU\Performance;

class ObjectCache {
    private $cache_group = 'tmu_theme';
    private $cache_expiry = 3600; // 1 hour
    
    public function __construct() {
        add_action('init', [$this, 'init_cache']);
    }
    
    public function init_cache(): void {
        // Register cache groups
        wp_cache_add_global_groups([$this->cache_group]);
        
        // Hook into post save to invalidate cache
        add_action('save_post', [$this, 'invalidate_post_cache']);
        add_action('delete_post', [$this, 'invalidate_post_cache']);
    }
    
    public function get_movie_data($post_id): ?array {
        $cache_key = "movie_data_{$post_id}";
        $cached_data = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        global $wpdb;
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tmu_movies WHERE post_id = %d",
            $post_id
        ), ARRAY_A);
        
        if ($data) {
            wp_cache_set($cache_key, $data, $this->cache_group, $this->cache_expiry);
        }
        
        return $data;
    }
    
    public function get_tv_series_data($post_id): ?array {
        $cache_key = "tv_series_data_{$post_id}";
        $cached_data = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        global $wpdb;
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tmu_tv_series WHERE post_id = %d",
            $post_id
        ), ARRAY_A);
        
        if ($data) {
            wp_cache_set($cache_key, $data, $this->cache_group, $this->cache_expiry);
        }
        
        return $data;
    }
    
    public function invalidate_post_cache($post_id): void {
        $post_type = get_post_type($post_id);
        
        switch ($post_type) {
            case 'movie':
                wp_cache_delete("movie_data_{$post_id}", $this->cache_group);
                break;
            case 'tv':
                wp_cache_delete("tv_series_data_{$post_id}", $this->cache_group);
                break;
            case 'drama':
                wp_cache_delete("drama_data_{$post_id}", $this->cache_group);
                break;
            case 'people':
                wp_cache_delete("people_data_{$post_id}", $this->cache_group);
                break;
        }
        
        // Clear related caches
        wp_cache_flush_group($this->cache_group);
    }
}
```

### 2.2 Fragment Cache System
```php
// src/Performance/FragmentCache.php
<?php
namespace TMU\Performance;

class FragmentCache {
    private $cache_group = 'tmu_fragments';
    private $default_expiry = 1800; // 30 minutes
    
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
    
    public function cache_movie_card($movie_id): string {
        return $this->cache_fragment(
            "movie_card_{$movie_id}",
            function() use ($movie_id) {
                get_template_part('template-parts/movie-card', null, ['movie_id' => $movie_id]);
            }
        );
    }
    
    public function cache_tv_series_card($tv_id): string {
        return $this->cache_fragment(
            "tv_series_card_{$tv_id}",
            function() use ($tv_id) {
                get_template_part('template-parts/tv-series-card', null, ['tv_id' => $tv_id]);
            }
        );
    }
}
```

## 3. Asset Optimization

### 3.1 CSS Optimization
```php
// src/Performance/AssetOptimizer.php
<?php
namespace TMU\Performance;

class AssetOptimizer {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'optimize_css'], 999);
        add_action('wp_enqueue_scripts', [$this, 'optimize_js'], 999);
        add_action('wp_head', [$this, 'add_resource_hints'], 1);
    }
    
    public function optimize_css(): void {
        // Combine and minify CSS files
        if (!is_admin() && !wp_is_json_request()) {
            $this->combine_css_files();
            $this->add_critical_css();
        }
    }
    
    public function optimize_js(): void {
        // Defer non-critical JavaScript
        add_filter('script_loader_tag', [$this, 'defer_scripts'], 10, 3);
        
        // Combine JavaScript files
        $this->combine_js_files();
    }
    
    public function combine_css_files(): void {
        global $wp_styles;
        
        $combined_css = '';
        $handles_to_remove = [];
        
        foreach ($wp_styles->queue as $handle) {
            if (strpos($handle, 'tmu-') === 0) {
                $style = $wp_styles->registered[$handle];
                $css_content = file_get_contents($style->src);
                $combined_css .= $this->minify_css($css_content);
                $handles_to_remove[] = $handle;
            }
        }
        
        if (!empty($combined_css)) {
            $combined_file = get_template_directory() . '/assets/css/combined.min.css';
            file_put_contents($combined_file, $combined_css);
            
            // Remove individual stylesheets
            foreach ($handles_to_remove as $handle) {
                wp_dequeue_style($handle);
            }
            
            // Enqueue combined stylesheet
            wp_enqueue_style('tmu-combined', get_template_directory_uri() . '/assets/css/combined.min.css');
        }
    }
    
    public function add_critical_css(): void {
        $critical_css = $this->get_critical_css();
        if ($critical_css) {
            echo "<style id='tmu-critical-css'>{$critical_css}</style>";
        }
    }
    
    public function get_critical_css(): string {
        // Above-the-fold CSS
        return '
            body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
            .tmu-header { background: #1a1a1a; color: white; padding: 1rem 0; }
            .tmu-navigation { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
            .tmu-hero { min-height: 400px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
            .tmu-content-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; padding: 2rem; }
        ';
    }
    
    public function defer_scripts($tag, $handle, $src): string {
        // Defer non-critical scripts
        $defer_scripts = ['tmu-interactions', 'tmu-search', 'tmu-lazy-load'];
        
        if (in_array($handle, $defer_scripts)) {
            return str_replace('<script ', '<script defer ', $tag);
        }
        
        return $tag;
    }
    
    public function add_resource_hints(): void {
        // DNS prefetch for external resources
        echo '<link rel="dns-prefetch" href="//image.tmdb.org">';
        echo '<link rel="dns-prefetch" href="//api.themoviedb.org">';
        echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">';
        
        // Preconnect for critical resources
        echo '<link rel="preconnect" href="https://image.tmdb.org" crossorigin>';
        
        // Preload critical resources
        echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/css/combined.min.css" as="style">';
        echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/js/critical.min.js" as="script">';
    }
    
    private function minify_css($css): string {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove unnecessary whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove unnecessary semicolons
        $css = str_replace(';}', '}', $css);
        
        return trim($css);
    }
}
```

### 3.2 Image Optimization
```php
// src/Performance/ImageOptimizer.php
<?php
namespace TMU\Performance;

class ImageOptimizer {
    public function __construct() {
        add_action('init', [$this, 'init_image_optimization']);
    }
    
    public function init_image_optimization(): void {
        // Enable WebP support
        add_filter('wp_generate_attachment_metadata', [$this, 'generate_webp_versions']);
        
        // Implement lazy loading
        add_filter('wp_get_attachment_image_attributes', [$this, 'add_lazy_loading']);
        
        // Optimize TMDB images
        add_filter('tmu_tmdb_image_url', [$this, 'optimize_tmdb_image_url'], 10, 3);
    }
    
    public function generate_webp_versions($metadata): array {
        if (!function_exists('imagewebp')) {
            return $metadata;
        }
        
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/' . $metadata['file'];
        
        // Generate WebP version
        $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file_path);
        
        switch (strtolower(pathinfo($file_path, PATHINFO_EXTENSION))) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($file_path);
                break;
            case 'png':
                $image = imagecreatefrompng($file_path);
                break;
            default:
                return $metadata;
        }
        
        if ($image) {
            imagewebp($image, $webp_path, 80);
            imagedestroy($image);
        }
        
        return $metadata;
    }
    
    public function add_lazy_loading($attributes): array {
        if (!is_admin() && !wp_is_json_request()) {
            $attributes['loading'] = 'lazy';
            $attributes['data-src'] = $attributes['src'];
            $attributes['src'] = 'data:image/svg+xml;base64,' . base64_encode(
                '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"><rect width="1" height="1" fill="#f0f0f0"/></svg>'
            );
            $attributes['class'] = ($attributes['class'] ?? '') . ' lazy-load';
        }
        
        return $attributes;
    }
    
    public function optimize_tmdb_image_url($url, $size, $type): string {
        // Use appropriate TMDB image size based on context
        $size_map = [
            'thumbnail' => 'w154',
            'medium' => 'w342',
            'large' => 'w500',
            'full' => 'w780'
        ];
        
        $tmdb_size = $size_map[$size] ?? 'w342';
        
        // Replace size in TMDB URL
        $url = preg_replace('/\/w\d+\//', "/{$tmdb_size}/", $url);
        
        return $url;
    }
}
```

## 4. Server-Side Performance

### 4.1 PHP Optimization
```php
// src/Performance/PhpOptimizer.php
<?php
namespace TMU\Performance;

class PhpOptimizer {
    public function __construct() {
        add_action('init', [$this, 'optimize_php_settings']);
    }
    
    public function optimize_php_settings(): void {
        // Increase memory limit for complex operations
        if (ini_get('memory_limit') < '256M') {
            ini_set('memory_limit', '256M');
        }
        
        // Enable OPcache if available
        if (function_exists('opcache_get_status')) {
            $opcache_status = opcache_get_status();
            if (!$opcache_status['opcache_enabled']) {
                ini_set('opcache.enable', 1);
                ini_set('opcache.memory_consumption', 128);
                ini_set('opcache.max_accelerated_files', 4000);
            }
        }
        
        // Optimize session handling
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_divisor', 100);
        ini_set('session.gc_maxlifetime', 3600);
    }
}
```

### 4.2 Content Delivery Network (CDN) Integration
```php
// src/Performance/CdnIntegration.php
<?php
namespace TMU\Performance;

class CdnIntegration {
    private $cdn_url;
    
    public function __construct() {
        $this->cdn_url = get_option('tmu_cdn_url', '');
        
        if ($this->cdn_url) {
            add_filter('wp_get_attachment_url', [$this, 'use_cdn_for_attachments']);
            add_filter('tmu_tmdb_image_url', [$this, 'use_cdn_for_tmdb_images']);
        }
    }
    
    public function use_cdn_for_attachments($url): string {
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        
        if (strpos($url, $base_url) === 0) {
            return str_replace($base_url, $this->cdn_url, $url);
        }
        
        return $url;
    }
    
    public function use_cdn_for_tmdb_images($url): string {
        // Proxy TMDB images through CDN
        $encoded_url = base64_encode($url);
        return $this->cdn_url . '/tmdb-proxy/' . $encoded_url;
    }
}
```

## 5. Performance Monitoring

### 5.1 Performance Metrics
```php
// src/Performance/PerformanceMonitor.php
<?php
namespace TMU\Performance;

class PerformanceMonitor {
    private $start_time;
    private $memory_start;
    
    public function __construct() {
        $this->start_time = microtime(true);
        $this->memory_start = memory_get_usage();
        
        add_action('wp_footer', [$this, 'log_performance_metrics']);
    }
    
    public function log_performance_metrics(): void {
        if (current_user_can('manage_options') && isset($_GET['debug_performance'])) {
            $execution_time = microtime(true) - $this->start_time;
            $memory_usage = memory_get_usage() - $this->memory_start;
            $peak_memory = memory_get_peak_usage();
            
            echo "<!-- TMU Performance Metrics -->";
            echo "<!-- Execution Time: " . round($execution_time, 4) . "s -->";
            echo "<!-- Memory Usage: " . $this->format_bytes($memory_usage) . " -->";
            echo "<!-- Peak Memory: " . $this->format_bytes($peak_memory) . " -->";
            echo "<!-- Database Queries: " . get_num_queries() . " -->";
        }
    }
    
    private function format_bytes($bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
```

### 5.2 Database Query Monitoring
```php
// src/Performance/QueryMonitor.php
<?php
namespace TMU\Performance;

class QueryMonitor {
    private $queries = [];
    
    public function __construct() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_filter('query', [$this, 'log_query']);
            add_action('wp_footer', [$this, 'display_query_log']);
        }
    }
    
    public function log_query($query): string {
        $this->queries[] = [
            'query' => $query,
            'time' => microtime(true),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ];
        
        return $query;
    }
    
    public function display_query_log(): void {
        if (current_user_can('manage_options') && isset($_GET['debug_queries'])) {
            echo '<div id="tmu-query-log" style="position: fixed; bottom: 0; left: 0; right: 0; background: #000; color: #fff; padding: 10px; max-height: 300px; overflow-y: auto; z-index: 9999; font-family: monospace; font-size: 12px;">';
            echo '<h4>Database Queries (' . count($this->queries) . ')</h4>';
            
            foreach ($this->queries as $i => $query_data) {
                echo '<div style="margin-bottom: 10px; padding: 5px; background: #333;">';
                echo '<strong>Query ' . ($i + 1) . ':</strong><br>';
                echo '<code>' . htmlspecialchars($query_data['query']) . '</code>';
                echo '</div>';
            }
            
            echo '</div>';
        }
    }
}
```

## 6. Lazy Loading Implementation

### 6.1 Content Lazy Loading
```php
// src/Performance/LazyLoader.php
<?php
namespace TMU\Performance;

class LazyLoader {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_lazy_loading_scripts']);
        add_filter('the_content', [$this, 'add_lazy_loading_to_content']);
    }
    
    public function enqueue_lazy_loading_scripts(): void {
        wp_enqueue_script(
            'tmu-lazy-load',
            get_template_directory_uri() . '/assets/js/lazy-load.js',
            [],
            '1.0.0',
            true
        );
    }
    
    public function add_lazy_loading_to_content($content): string {
        // Add lazy loading to images in content
        $content = preg_replace_callback(
            '/<img([^>]+)>/i',
            [$this, 'add_lazy_loading_attributes'],
            $content
        );
        
        return $content;
    }
    
    public function add_lazy_loading_attributes($matches): string {
        $img_tag = $matches[0];
        
        // Skip if already has lazy loading
        if (strpos($img_tag, 'data-src') !== false) {
            return $img_tag;
        }
        
        // Extract src attribute
        preg_match('/src=["\']([^"\']+)["\']/', $img_tag, $src_matches);
        if (!isset($src_matches[1])) {
            return $img_tag;
        }
        
        $src = $src_matches[1];
        $placeholder = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"><rect width="1" height="1" fill="#f0f0f0"/></svg>'
        );
        
        // Replace src with placeholder and add data-src
        $img_tag = str_replace('src="' . $src . '"', 'src="' . $placeholder . '" data-src="' . $src . '"', $img_tag);
        
        // Add lazy loading class
        if (strpos($img_tag, 'class=') !== false) {
            $img_tag = preg_replace('/class=["\']([^"\']*)["\']/', 'class="$1 lazy-load"', $img_tag);
        } else {
            $img_tag = str_replace('<img', '<img class="lazy-load"', $img_tag);
        }
        
        return $img_tag;
    }
}
```

### 6.2 JavaScript Lazy Loading
```javascript
// assets/js/lazy-load.js
class TMULazyLoader {
    constructor() {
        this.imageObserver = null;
        this.contentObserver = null;
        this.init();
    }
    
    init() {
        if ('IntersectionObserver' in window) {
            this.setupImageObserver();
            this.setupContentObserver();
        } else {
            // Fallback for older browsers
            this.loadAllImages();
        }
    }
    
    setupImageObserver() {
        this.imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                    this.imageObserver.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            this.imageObserver.observe(img);
        });
    }
    
    setupContentObserver() {
        this.contentObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadContent(entry.target);
                    this.contentObserver.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '100px 0px',
            threshold: 0.01
        });
        
        document.querySelectorAll('[data-lazy-content]').forEach(element => {
            this.contentObserver.observe(element);
        });
    }
    
    loadImage(img) {
        const src = img.getAttribute('data-src');
        if (src) {
            img.src = src;
            img.classList.remove('lazy-load');
            img.classList.add('lazy-loaded');
            img.removeAttribute('data-src');
        }
    }
    
    loadContent(element) {
        const contentUrl = element.getAttribute('data-lazy-content');
        if (contentUrl) {
            fetch(contentUrl)
                .then(response => response.text())
                .then(html => {
                    element.innerHTML = html;
                    element.removeAttribute('data-lazy-content');
                    
                    // Re-observe any new lazy images
                    element.querySelectorAll('img[data-src]').forEach(img => {
                        this.imageObserver.observe(img);
                    });
                })
                .catch(error => {
                    console.error('Error loading lazy content:', error);
                });
        }
    }
    
    loadAllImages() {
        document.querySelectorAll('img[data-src]').forEach(img => {
            this.loadImage(img);
        });
    }
}

// Initialize lazy loader when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new TMULazyLoader();
});
```

## 7. Performance Configuration

### 7.1 WordPress Configuration
```php
// Performance settings in wp-config.php additions
define('WP_CACHE', true);
define('COMPRESS_CSS', true);
define('COMPRESS_SCRIPTS', true);
define('CONCATENATE_SCRIPTS', true);
define('ENFORCE_GZIP', true);

// Database optimization
define('WP_ALLOW_REPAIR', true);
define('AUTOMATIC_UPDATER_DISABLED', true);

// Memory limits
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

### 7.2 Server Configuration Recommendations
```apache
# .htaccess optimizations
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
</IfModule>

<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

<IfModule mod_headers.c>
    Header set Cache-Control "public, max-age=31536000" 
    Header set X-Content-Type-Options nosniff
    Header set X-Frame-Options DENY
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

## Success Metrics

- **Page Load Time**: < 3 seconds on 3G connection
- **First Contentful Paint**: < 1.5 seconds
- **Largest Contentful Paint**: < 2.5 seconds
- **Cumulative Layout Shift**: < 0.1
- **Database Queries**: < 50 per page
- **Memory Usage**: < 64MB per request
- **Cache Hit Rate**: > 80%
- **Image Optimization**: 50% size reduction
- **Core Web Vitals**: All metrics in "Good" range

This comprehensive performance optimization ensures the TMU theme delivers exceptional user experience while maintaining all functionality.