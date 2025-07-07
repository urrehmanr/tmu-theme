<?php
/**
 * Performance Optimizer
 * 
 * Performance maintenance and optimization system for TMU theme.
 * 
 * @package TMU\Maintenance
 * @since 1.0.0
 */

namespace TMU\Maintenance;

use TMU\Logging\LogManager;

class PerformanceOptimizer {
    
    /**
     * Logger instance
     * @var LogManager
     */
    private $logger;
    
    /**
     * Image quality for optimization
     * @var int
     */
    private $image_quality = 85;
    
    /**
     * Maximum image dimensions
     * @var array
     */
    private $max_dimensions = [
        'width' => 1920,
        'height' => 1080
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new LogManager();
        
        add_action('tmu_performance_optimization', [$this, 'optimize_performance']);
        add_action('wp_ajax_tmu_optimize_performance', [$this, 'optimize_performance_ajax']);
        add_action('wp_ajax_tmu_optimize_images', [$this, 'optimize_images_ajax']);
        add_action('wp_ajax_tmu_optimize_assets', [$this, 'optimize_assets_ajax']);
    }
    
    /**
     * Main performance optimization routine
     */
    public function optimize_performance(): void {
        $this->logger->info('Starting performance optimization');
        
        try {
            $start_time = microtime(true);
            $results = [];
            
            // Optimize images
            $results['images'] = $this->optimize_images();
            
            // Optimize CSS and JS
            $results['assets'] = $this->optimize_css_js();
            
            // Clean cache
            $results['cache'] = $this->clean_cache();
            
            // Optimize database queries
            $results['database'] = $this->optimize_database_queries();
            
            // Generate performance report
            $results['report'] = $this->generate_performance_report();
            
            $execution_time = microtime(true) - $start_time;
            
            $this->logger->info('Performance optimization completed', [
                'results' => $results,
                'execution_time' => $execution_time
            ]);
            
            // Update optimization statistics
            $this->update_optimization_statistics($results, $execution_time);
            
        } catch (\Exception $e) {
            $this->logger->error('Performance optimization failed', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Optimize images
     */
    public function optimize_images(): array {
        $this->logger->info('Starting image optimization');
        
        $upload_dir = wp_upload_dir();
        $image_dir = $upload_dir['basedir'];
        
        // Find images to optimize
        $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $images = [];
        
        foreach ($image_extensions as $ext) {
            $found_images = glob($image_dir . '/**/*.' . $ext, GLOB_BRACE);
            $images = array_merge($images, $found_images);
        }
        
        $optimized = 0;
        $skipped = 0;
        $failed = 0;
        $space_saved = 0;
        
        foreach ($images as $image) {
            try {
                if ($this->needs_optimization($image)) {
                    $original_size = filesize($image);
                    
                    if ($this->optimize_image($image)) {
                        $new_size = filesize($image);
                        $space_saved += ($original_size - $new_size);
                        $optimized++;
                        
                        $this->logger->debug('Image optimized', [
                            'file' => basename($image),
                            'original_size' => $original_size,
                            'new_size' => $new_size,
                            'saved' => $original_size - $new_size
                        ]);
                    } else {
                        $failed++;
                    }
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->logger->warning('Image optimization failed', [
                    'file' => basename($image),
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'total_images' => count($images),
            'optimized' => $optimized,
            'skipped' => $skipped,
            'failed' => $failed,
            'space_saved' => $space_saved
        ];
    }
    
    /**
     * Check if image needs optimization
     */
    public function needs_optimization($image_path): bool {
        if (!file_exists($image_path)) {
            return false;
        }
        
        $file_size = filesize($image_path);
        $image_info = getimagesize($image_path);
        
        if (!$image_info) {
            return false;
        }
        
        // Skip if already optimized recently
        $optimization_log = get_option('tmu_image_optimization_log', []);
        $file_hash = md5_file($image_path);
        
        if (isset($optimization_log[$file_hash]) && 
            (time() - $optimization_log[$file_hash]) < (7 * 24 * 60 * 60)) {
            return false;
        }
        
        // Optimize if:
        // - File is larger than 500KB
        // - Dimensions are larger than max allowed
        // - Image quality appears to be too high (for JPEG)
        $needs_optimization = (
            $file_size > 512000 || // 500KB
            $image_info[0] > $this->max_dimensions['width'] ||
            $image_info[1] > $this->max_dimensions['height'] ||
            ($image_info['mime'] === 'image/jpeg' && $this->estimate_jpeg_quality($image_path) > 90)
        );
        
        return $needs_optimization;
    }
    
    /**
     * Optimize individual image
     */
    public function optimize_image($image_path): bool {
        $image_info = getimagesize($image_path);
        
        if (!$image_info) {
            return false;
        }
        
        $mime_type = $image_info['mime'];
        $original_size = filesize($image_path);
        
        try {
            switch ($mime_type) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($image_path);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($image_path);
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($image_path);
                    break;
                case 'image/webp':
                    $image = imagecreatefromwebp($image_path);
                    break;
                default:
                    return false;
            }
            
            if (!$image) {
                return false;
            }
            
            // Resize if too large
            if ($image_info[0] > $this->max_dimensions['width'] || 
                $image_info[1] > $this->max_dimensions['height']) {
                $image = $this->resize_image($image, $image_info[0], $image_info[1], 
                    $this->max_dimensions['width'], $this->max_dimensions['height']);
            }
            
            // Create backup of original
            $backup_path = $image_path . '.backup';
            copy($image_path, $backup_path);
            
            // Save optimized image
            $success = false;
            
            switch ($mime_type) {
                case 'image/jpeg':
                    $success = imagejpeg($image, $image_path, $this->image_quality);
                    break;
                case 'image/png':
                    // Convert to 8-bit PNG if needed
                    if ($this->is_true_color_png($image_path)) {
                        $image = $this->convert_to_palette($image);
                    }
                    $success = imagepng($image, $image_path, 8);
                    break;
                case 'image/gif':
                    $success = imagegif($image, $image_path);
                    break;
                case 'image/webp':
                    $success = imagewebp($image, $image_path, $this->image_quality);
                    break;
            }
            
            imagedestroy($image);
            
            if ($success) {
                $new_size = filesize($image_path);
                
                // If optimization didn't save space, restore original
                if ($new_size >= $original_size) {
                    copy($backup_path, $image_path);
                    unlink($backup_path);
                    return false;
                }
                
                // Log successful optimization
                $optimization_log = get_option('tmu_image_optimization_log', []);
                $optimization_log[md5_file($image_path)] = time();
                update_option('tmu_image_optimization_log', $optimization_log);
                
                // Clean up backup
                unlink($backup_path);
                
                return true;
            } else {
                // Restore original on failure
                copy($backup_path, $image_path);
                unlink($backup_path);
                return false;
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Image optimization failed', [
                'file' => basename($image_path),
                'error' => $e->getMessage()
            ]);
            
            // Restore original if backup exists
            if (file_exists($backup_path)) {
                copy($backup_path, $image_path);
                unlink($backup_path);
            }
            
            return false;
        }
    }
    
    /**
     * Resize image maintaining aspect ratio exactly as documented
     */
    private function resize_image($image, $original_width, $original_height, $max_width, $max_height) {
        $ratio = min($max_width / $original_width, $max_height / $original_height);
        
        $new_width = intval($original_width * $ratio);
        $new_height = intval($original_height * $ratio);
        
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG and GIF
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefill($new_image, 0, 0, $transparent);
        
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, 
            $new_width, $new_height, $original_width, $original_height);
        
        imagedestroy($image);
        
        return $new_image;
    }
    
    /**
     * Optimize CSS and JS files
     */
    public function optimize_css_js(): array {
        $this->logger->info('Starting CSS/JS optimization');
        
        $theme_dir = get_template_directory();
        $results = [
            'css' => ['optimized' => 0, 'failed' => 0, 'space_saved' => 0],
            'js' => ['optimized' => 0, 'failed' => 0, 'space_saved' => 0]
        ];
        
        // Optimize CSS files
        $css_files = glob($theme_dir . '/assets/css/*.css');
        foreach ($css_files as $css_file) {
            if (strpos($css_file, '.min.css') === false) {
                $original_size = filesize($css_file);
                
                if ($this->minify_css_file($css_file)) {
                    $new_size = filesize($css_file);
                    $results['css']['optimized']++;
                    $results['css']['space_saved'] += ($original_size - $new_size);
                } else {
                    $results['css']['failed']++;
                }
            }
        }
        
        // Optimize JS files
        $js_files = glob($theme_dir . '/assets/js/*.js');
        foreach ($js_files as $js_file) {
            if (strpos($js_file, '.min.js') === false) {
                $original_size = filesize($js_file);
                
                if ($this->minify_js_file($js_file)) {
                    $new_size = filesize($js_file);
                    $results['js']['optimized']++;
                    $results['js']['space_saved'] += ($original_size - $new_size);
                } else {
                    $results['js']['failed']++;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Minify CSS file
     */
    public function minify_css_file($css_file): bool {
        if (!file_exists($css_file)) {
            return false;
        }
        
        try {
            $css_content = file_get_contents($css_file);
            $minified_content = $this->minify_css($css_content);
            
            // Create minified version
            $minified_file = str_replace('.css', '.min.css', $css_file);
            
            if (file_put_contents($minified_file, $minified_content) !== false) {
                $this->logger->debug('CSS file minified', [
                    'original' => basename($css_file),
                    'minified' => basename($minified_file),
                    'original_size' => strlen($css_content),
                    'minified_size' => strlen($minified_content)
                ]);
                
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            $this->logger->error('CSS minification failed', [
                'file' => basename($css_file),
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Minify JS file
     */
    public function minify_js_file($js_file): bool {
        if (!file_exists($js_file)) {
            return false;
        }
        
        try {
            $js_content = file_get_contents($js_file);
            $minified_content = $this->minify_js($js_content);
            
            // Create minified version
            $minified_file = str_replace('.js', '.min.js', $js_file);
            
            if (file_put_contents($minified_file, $minified_content) !== false) {
                $this->logger->debug('JS file minified', [
                    'original' => basename($js_file),
                    'minified' => basename($minified_file),
                    'original_size' => strlen($js_content),
                    'minified_size' => strlen($minified_content)
                ]);
                
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            $this->logger->error('JS minification failed', [
                'file' => basename($js_file),
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Minify CSS content
     */
    private function minify_css($css): string {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        
        // Remove unnecessary spaces
        $css = preg_replace('/\s*{\s*/', '{', $css);
        $css = preg_replace('/;\s*}/', '}', $css);
        $css = preg_replace('/\s*;\s*/', ';', $css);
        $css = preg_replace('/\s*,\s*/', ',', $css);
        $css = preg_replace('/\s*:\s*/', ':', $css);
        $css = preg_replace('/}\s*/', '}', $css);
        
        return trim($css);
    }
    
    /**
     * Minify JS content
     */
    private function minify_js($js): string {
        // Basic JS minification
        // Remove single-line comments
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remove excess whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remove unnecessary spaces around operators
        $js = preg_replace('/\s*([{}();,:])\s*/', '$1', $js);
        
        return trim($js);
    }
    
    /**
     * Clean cache
     */
    public function clean_cache(): array {
        $this->logger->info('Starting cache cleanup');
        
        $results = [
            'wordpress_cache' => false,
            'transients_cleared' => 0,
            'tmu_cache_cleared' => 0
        ];
        
        try {
            // Clear WordPress object cache
            wp_cache_flush();
            $results['wordpress_cache'] = true;
            
            // Clear expired transients
            global $wpdb;
            $expired_transients = $wpdb->query("
                DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_timeout_%' 
                AND option_value < UNIX_TIMESTAMP()
            ");
            $results['transients_cleared'] = $expired_transients;
            
            // Clear TMU-specific cache
            $cache_patterns = [
                'tmu_movie_cache_%',
                'tmu_tv_cache_%',
                'tmu_people_cache_%',
                'tmu_tmdb_cache_%',
                'tmu_search_cache_%'
            ];
            
            $tmu_cache_cleared = 0;
            foreach ($cache_patterns as $pattern) {
                $cache_keys = $wpdb->get_col($wpdb->prepare(
                    "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $pattern
                ));
                
                foreach ($cache_keys as $key) {
                    delete_option($key);
                    $tmu_cache_cleared++;
                }
            }
            
            $results['tmu_cache_cleared'] = $tmu_cache_cleared;
            
        } catch (\Exception $e) {
            $this->logger->error('Cache cleanup failed', ['error' => $e->getMessage()]);
        }
        
        return $results;
    }
    
    /**
     * Optimize database queries
     */
    public function optimize_database_queries(): array {
        $this->logger->info('Starting database query optimization');
        
        global $wpdb;
        $results = [
            'slow_queries_found' => 0,
            'indexes_analyzed' => 0,
            'recommendations' => []
        ];
        
        try {
            // Analyze slow queries if performance schema is available
            $performance_schema_enabled = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM information_schema.SCHEMATA 
                WHERE SCHEMA_NAME = 'performance_schema'
            ");
            
            if ($performance_schema_enabled) {
                $slow_queries = $wpdb->get_results("
                    SELECT SQL_TEXT, AVG_TIMER_WAIT, COUNT_STAR
                    FROM performance_schema.events_statements_summary_by_digest
                    WHERE AVG_TIMER_WAIT > 1000000000
                    AND SQL_TEXT LIKE '%tmu_%'
                    ORDER BY AVG_TIMER_WAIT DESC
                    LIMIT 10
                ");
                
                $results['slow_queries_found'] = count($slow_queries);
                
                foreach ($slow_queries as $query) {
                    $results['recommendations'][] = [
                        'type' => 'slow_query',
                        'query' => substr($query->SQL_TEXT, 0, 100) . '...',
                        'avg_time' => round($query->AVG_TIMER_WAIT / 1000000000, 2) . 's',
                        'count' => $query->COUNT_STAR
                    ];
                }
            }
            
            // Analyze table indexes
            $tmu_tables = [
                $wpdb->prefix . 'tmu_movies',
                $wpdb->prefix . 'tmu_tv_series',
                $wpdb->prefix . 'tmu_dramas',
                $wpdb->prefix . 'tmu_people',
                $wpdb->prefix . 'tmu_analytics_events'
            ];
            
            foreach ($tmu_tables as $table) {
                $indexes = $wpdb->get_results("SHOW INDEXES FROM {$table}");
                $results['indexes_analyzed'] += count($indexes);
                
                // Check for missing commonly needed indexes
                $this->check_table_indexes($table, $results);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Database query optimization failed', ['error' => $e->getMessage()]);
        }
        
        return $results;
    }
    
    /**
     * Check table indexes and suggest improvements
     */
    private function check_table_indexes($table, &$results): void {
        global $wpdb;
        
        $table_name = str_replace($wpdb->prefix . 'tmu_', '', $table);
        
        // Get current indexes
        $current_indexes = $wpdb->get_results("SHOW INDEXES FROM {$table}");
        $index_columns = array_column($current_indexes, 'Column_name');
        
        // Common columns that should be indexed
        $recommended_indexes = [
            'movies' => ['tmdb_id', 'release_date', 'runtime', 'tmdb_popularity'],
            'tv_series' => ['tmdb_id', 'first_air_date', 'status', 'tmdb_popularity'],
            'dramas' => ['tmdb_id', 'first_air_date', 'status', 'tmdb_popularity'],
            'people' => ['tmdb_id', 'birthday', 'popularity'],
            'analytics_events' => ['event_type', 'timestamp', 'content_id']
        ];
        
        if (isset($recommended_indexes[$table_name])) {
            foreach ($recommended_indexes[$table_name] as $column) {
                if (!in_array($column, $index_columns)) {
                    $results['recommendations'][] = [
                        'type' => 'missing_index',
                        'table' => $table_name,
                        'column' => $column,
                        'suggestion' => "Consider adding index on {$column} for better query performance"
                    ];
                }
            }
        }
    }
    
    /**
     * Generate performance report
     */
    public function generate_performance_report(): array {
        $this->logger->info('Generating performance report');
        
        global $wpdb;
        
        try {
            // Get performance metrics from last 24 hours
            $metrics = $wpdb->get_row(
                "SELECT 
                    AVG(response_time) as avg_response_time,
                    MAX(response_time) as max_response_time,
                    MIN(response_time) as min_response_time,
                    COUNT(*) as total_requests,
                    AVG(memory_usage) as avg_memory_usage,
                    MAX(memory_usage) as max_memory_usage
                 FROM {$wpdb->prefix}tmu_performance_logs 
                 WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            
            $report = [
                'date' => current_time('Y-m-d'),
                'metrics' => $metrics ?: (object)[
                    'avg_response_time' => 0,
                    'max_response_time' => 0,
                    'min_response_time' => 0,
                    'total_requests' => 0,
                    'avg_memory_usage' => 0,
                    'max_memory_usage' => 0
                ],
                'recommendations' => $this->generate_performance_recommendations($metrics)
            ];
            
            // Save report
            update_option('tmu_daily_performance_report', $report);
            
            return $report;
            
        } catch (\Exception $e) {
            $this->logger->error('Performance report generation failed', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Generate performance recommendations
     */
    private function generate_performance_recommendations($metrics): array {
        $recommendations = [];
        
        if (!$metrics) {
            return $recommendations;
        }
        
        if ($metrics->avg_response_time > 2) {
            $recommendations[] = 'Average response time is high (' . round($metrics->avg_response_time, 2) . 's). Consider enabling caching or optimizing database queries.';
        }
        
        if ($metrics->max_memory_usage > 134217728) { // 128MB
            $recommendations[] = 'High memory usage detected (' . round($metrics->max_memory_usage / 1024 / 1024, 2) . 'MB). Review memory-intensive operations.';
        }
        
        if ($metrics->total_requests > 10000) {
            $recommendations[] = 'High traffic detected (' . number_format($metrics->total_requests) . ' requests). Consider implementing CDN or load balancing.';
        }
        
        if ($metrics->max_response_time > 10) {
            $recommendations[] = 'Very slow requests detected (' . round($metrics->max_response_time, 2) . 's). Investigate slow database queries or external API calls.';
        }
        
        return $recommendations;
    }
    
    /**
     * Update optimization statistics
     */
    private function update_optimization_statistics($results, $execution_time): void {
        $stats = get_option('tmu_optimization_statistics', []);
        
        $stats[current_time('Y-m-d H:i:s')] = [
            'results' => $results,
            'execution_time' => $execution_time,
            'total_space_saved' => ($results['images']['space_saved'] ?? 0) + 
                                  ($results['assets']['css']['space_saved'] ?? 0) + 
                                  ($results['assets']['js']['space_saved'] ?? 0)
        ];
        
        // Keep only last 30 optimization runs
        if (count($stats) > 30) {
            $stats = array_slice($stats, -30, null, true);
        }
        
        update_option('tmu_optimization_statistics', $stats);
    }
    
    /**
     * AJAX handlers
     */
    public function optimize_performance_ajax(): void {
        check_ajax_referer('tmu_performance_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $this->optimize_performance();
            wp_send_json_success('Performance optimization completed successfully');
        } catch (\Exception $e) {
            wp_send_json_error('Optimization failed: ' . $e->getMessage());
        }
    }
    
    public function optimize_images_ajax(): void {
        check_ajax_referer('tmu_performance_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $results = $this->optimize_images();
            wp_send_json_success($results);
        } catch (\Exception $e) {
            wp_send_json_error('Image optimization failed: ' . $e->getMessage());
        }
    }
    
    public function optimize_assets_ajax(): void {
        check_ajax_referer('tmu_performance_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $results = $this->optimize_css_js();
            wp_send_json_success($results);
        } catch (\Exception $e) {
            wp_send_json_error('Asset optimization failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Helper methods
     */
    private function estimate_jpeg_quality($image_path): int {
        // Simple JPEG quality estimation based on file size vs dimensions
        $image_info = getimagesize($image_path);
        $file_size = filesize($image_path);
        $pixels = $image_info[0] * $image_info[1];
        
        if ($pixels > 0) {
            $bytes_per_pixel = $file_size / $pixels;
            
            // Rough estimation based on typical compression ratios
            if ($bytes_per_pixel > 3) return 95;
            if ($bytes_per_pixel > 2) return 85;
            if ($bytes_per_pixel > 1.5) return 75;
            if ($bytes_per_pixel > 1) return 65;
            return 55;
        }
        
        return 75; // Default assumption
    }
    
    private function is_true_color_png($image_path): bool {
        $image_info = getimagesize($image_path);
        return isset($image_info['bits']) && $image_info['bits'] > 8;
    }
    
    private function convert_to_palette($image) {
        $palette_image = imagecreate(imagesx($image), imagesy($image));
        imagecopymerge($palette_image, $image, 0, 0, 0, 0, imagesx($image), imagesy($image), 100);
        return $palette_image;
    }
}