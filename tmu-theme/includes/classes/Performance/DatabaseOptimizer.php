<?php
/**
 * Database Optimizer - Database Performance Optimization
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
 * Database Performance Optimization
 */
class DatabaseOptimizer {
    
    /**
     * Query Monitor instance
     */
    private QueryMonitor $query_monitor;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->query_monitor = new QueryMonitor();
        $this->init();
    }
    
    /**
     * Initialize database optimizer
     */
    public function init(): void {
        // Enable persistent connections
        if (!defined('DB_PERSISTENT')) {
            define('DB_PERSISTENT', true);
        }
        
        // Optimize database settings
        add_action('init', [$this, 'optimize_database_settings']);
        
        // Query optimization hooks
        add_action('pre_get_posts', [$this, 'optimize_main_query']);
        add_filter('posts_clauses', [$this, 'optimize_post_queries'], 10, 2);
        
        // Database maintenance
        add_action('wp_scheduled_delete', [$this, 'optimize_database_cleanup']);
        
        // Create indexes after theme activation
        add_action('after_switch_theme', [$this, 'create_database_indexes']);
        
        // Admin database tools
        add_action('admin_post_tmu_optimize_database', [$this, 'admin_optimize_database']);
        add_action('wp_ajax_tmu_database_stats', [$this, 'get_database_stats']);
        
        // Schedule database maintenance
        add_action('tmu_database_maintenance', [$this, 'perform_database_maintenance']);
        if (!wp_next_scheduled('tmu_database_maintenance')) {
            wp_schedule_event(time(), 'weekly', 'tmu_database_maintenance');
        }
    }
    
    /**
     * Optimize database settings
     */
    public function optimize_database_settings(): void {
        global $wpdb;
        
        // Set optimal MySQL session variables
        $optimizations = [
            "SET SESSION query_cache_type = 'ON'",
            "SET SESSION query_cache_size = 32M",
            "SET SESSION tmp_table_size = 67108864",   // 64MB
            "SET SESSION max_heap_table_size = 67108864", // 64MB
            "SET SESSION join_buffer_size = 2097152",  // 2MB
            "SET SESSION sort_buffer_size = 2097152",  // 2MB
            "SET SESSION read_buffer_size = 131072",   // 128KB
            "SET SESSION read_rnd_buffer_size = 262144" // 256KB
        ];
        
        foreach ($optimizations as $query) {
            $wpdb->query($query);
        }
        
        // Enable query cache for this session
        $wpdb->query("SET SESSION sql_mode = 'TRADITIONAL'");
    }
    
    /**
     * Optimize main query
     */
    public function optimize_main_query($query): void {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Optimize movie archive queries
        if (is_post_type_archive('movie')) {
            $this->optimize_movie_archive_query($query);
        }
        
        // Optimize TV series archive queries
        if (is_post_type_archive('tv')) {
            $this->optimize_tv_archive_query($query);
        }
        
        // Optimize people archive queries
        if (is_post_type_archive('people')) {
            $this->optimize_people_archive_query($query);
        }
        
        // Optimize search queries
        if ($query->is_search()) {
            $this->optimize_search_query($query);
        }
        
        // Optimize taxonomy queries
        if (is_tax(['genre', 'country', 'language', 'network'])) {
            $this->optimize_taxonomy_query($query);
        }
    }
    
    /**
     * Optimize movie archive query
     */
    private function optimize_movie_archive_query($query): void {
        // Limit posts per page for better performance
        $query->set('posts_per_page', 24);
        
        // Disable post meta caching for archive pages
        $query->set('update_post_meta_cache', false);
        
        // Optimize meta query for sorting
        if (get_query_var('orderby') === 'rating') {
            $query->set('meta_key', 'tmu_movie_vote_average');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'DESC');
        } elseif (get_query_var('orderby') === 'popularity') {
            $query->set('meta_key', 'tmu_movie_popularity');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'DESC');
        }
    }
    
    /**
     * Optimize TV archive query
     */
    private function optimize_tv_archive_query($query): void {
        // Similar optimization for TV series
        $query->set('posts_per_page', 24);
        $query->set('update_post_meta_cache', false);
        
        if (get_query_var('orderby') === 'rating') {
            $query->set('meta_key', 'tmu_tv_vote_average');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'DESC');
        }
    }
    
    /**
     * Optimize people archive query
     */
    private function optimize_people_archive_query($query): void {
        $query->set('posts_per_page', 30);
        $query->set('update_post_meta_cache', false);
        
        // Default order by popularity
        $query->set('meta_key', 'tmu_person_popularity');
        $query->set('orderby', 'meta_value_num');
        $query->set('order', 'DESC');
    }
    
    /**
     * Optimize search query
     */
    private function optimize_search_query($query): void {
        // Limit search results for performance
        $query->set('posts_per_page', 20);
        
        // Include only content post types
        $query->set('post_type', ['movie', 'tv', 'people', 'drama']);
        
        // Disable post meta caching for search
        $query->set('update_post_meta_cache', false);
    }
    
    /**
     * Optimize taxonomy query
     */
    private function optimize_taxonomy_query($query): void {
        $query->set('posts_per_page', 24);
        $query->set('update_post_meta_cache', false);
        
        // Order by popularity for taxonomy pages
        $query->set('meta_key', 'tmu_movie_popularity');
        $query->set('orderby', 'meta_value_num');
        $query->set('order', 'DESC');
    }
    
    /**
     * Optimize post queries
     */
    public function optimize_post_queries($clauses, $query): array {
        global $wpdb;
        
        if (is_admin() || !$query->is_main_query()) {
            return $clauses;
        }
        
        $post_type = $query->get('post_type');
        
        if ($post_type === 'movie') {
            return $this->optimize_movie_queries($clauses, $query);
        } elseif ($post_type === 'tv') {
            return $this->optimize_tv_queries($clauses, $query);
        } elseif ($post_type === 'drama') {
            return $this->optimize_drama_queries($clauses, $query);
        } elseif ($post_type === 'people') {
            return $this->optimize_people_query_clauses($clauses, $query);
        }
        
        return $clauses;
    }
    
    /**
     * Optimize movie queries
     */
    public function optimize_movie_queries($clauses, $query): array {
        global $wpdb;
        
        // Join with TMU movies table for better performance
        $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}tmu_movies tm ON {$wpdb->posts}.ID = tm.post_id";
        
        // Add commonly used fields to SELECT
        $clauses['fields'] .= ", tm.tmdb_id, tm.release_date, tm.runtime, tm.tmdb_vote_average, tm.popularity";
        
        // Optimize WHERE clause
        if (!empty($clauses['where'])) {
            $clauses['where'] .= " AND tm.post_id IS NOT NULL";
        }
        
        // Optimize ordering
        if ($query->get('orderby') === 'release_date') {
            $clauses['orderby'] = 'tm.release_date DESC';
        } elseif ($query->get('orderby') === 'rating') {
            $clauses['orderby'] = 'tm.tmdb_vote_average DESC';
        } elseif ($query->get('orderby') === 'popularity') {
            $clauses['orderby'] = 'tm.popularity DESC';
        }
        
        return $clauses;
    }
    
    /**
     * Optimize TV queries
     */
    public function optimize_tv_queries($clauses, $query): array {
        global $wpdb;
        
        // Join with TMU TV series table
        $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}tmu_tv_series ttv ON {$wpdb->posts}.ID = ttv.post_id";
        
        // Add commonly used fields
        $clauses['fields'] .= ", ttv.tmdb_id, ttv.first_air_date, ttv.number_of_seasons, ttv.tmdb_vote_average, ttv.popularity";
        
        // Optimize WHERE clause
        if (!empty($clauses['where'])) {
            $clauses['where'] .= " AND ttv.post_id IS NOT NULL";
        }
        
        // Optimize ordering
        if ($query->get('orderby') === 'first_air_date') {
            $clauses['orderby'] = 'ttv.first_air_date DESC';
        } elseif ($query->get('orderby') === 'rating') {
            $clauses['orderby'] = 'ttv.tmdb_vote_average DESC';
        }
        
        return $clauses;
    }
    
    /**
     * Optimize drama queries
     */
    public function optimize_drama_queries($clauses, $query): array {
        global $wpdb;
        
        if (!is_admin() && $query->is_main_query() && $query->get('post_type') === 'drama') {
            // Join with custom table for better performance
            $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}tmu_dramas td ON {$wpdb->posts}.ID = td.post_id";
            
            // Add commonly used fields to SELECT
            $clauses['fields'] .= ", td.tmdb_id, td.first_air_date, td.number_of_episodes, td.tmdb_vote_average";
            
            // Optimize ordering
            if ($query->get('orderby') === 'first_air_date') {
                $clauses['orderby'] = 'td.first_air_date DESC';
            } elseif ($query->get('orderby') === 'rating') {
                $clauses['orderby'] = 'td.tmdb_vote_average DESC';
            }
        }
        
        return $clauses;
    }
    
    /**
     * Optimize people query clauses
     */
    private function optimize_people_query_clauses($clauses, $query): array {
        global $wpdb;
        
        // Join with TMU people table
        $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}tmu_people tp ON {$wpdb->posts}.ID = tp.post_id";
        
        // Add commonly used fields
        $clauses['fields'] .= ", tp.tmdb_id, tp.known_for_department, tp.popularity";
        
        // Optimize WHERE clause
        if (!empty($clauses['where'])) {
            $clauses['where'] .= " AND tp.post_id IS NOT NULL";
        }
        
        // Default order by popularity
        if (empty($query->get('orderby'))) {
            $clauses['orderby'] = 'tp.popularity DESC';
        }
        
        return $clauses;
    }
    
    /**
     * Create database indexes
     */
    public function create_database_indexes(): void {
        global $wpdb;
        
        $indexes = [
            // TMU Movies indexes
            "CREATE INDEX IF NOT EXISTS idx_tmu_movies_tmdb_id ON {$wpdb->prefix}tmu_movies (tmdb_id)",
            "CREATE INDEX IF NOT EXISTS idx_tmu_movies_release_date ON {$wpdb->prefix}tmu_movies (release_date)",
            "CREATE INDEX IF NOT EXISTS idx_tmu_movies_rating ON {$wpdb->prefix}tmu_movies (tmdb_vote_average)",
            "CREATE INDEX IF NOT EXISTS idx_tmu_movies_popularity ON {$wpdb->prefix}tmu_movies (popularity)",
            "CREATE INDEX IF NOT EXISTS idx_tmu_movies_runtime ON {$wpdb->prefix}tmu_movies (runtime)",
            "CREATE INDEX IF NOT EXISTS idx_tmu_movies_status ON {$wpdb->prefix}tmu_movies (status)",
            
            // TMU TV Series indexes
            "CREATE INDEX IF NOT EXISTS idx_tmu_tv_tmdb_id ON {$wpdb->prefix}tmu_tv_series (tmdb_id)",
            "CREATE INDEX IF NOT EXISTS idx_tmu_tv_first_air_date ON {$wpdb->prefix}tmu_tv_series (first_air_date)",
            "CREATE INDEX IF NOT EXISTS idx_tmu_tv_rating ON {$wpdb->prefix}tmu_tv_series (tmdb_vote_average)",
            "CREATE INDEX IF NOT EXISTS idx_tmu_tv_popularity ON {$wpdb->prefix}tmu_tv_series (popularity)",
            "CREATE INDEX IF NOT EXISTS idx_tmu_tv_status ON {$wpdb->prefix}tmu_tv_series (status)",
            "CREATE INDEX IF NOT EXISTS idx_tmu_tv_seasons ON {$wpdb->prefix}tmu_tv_series (number_of_seasons)",
            
            // TMU People indexes
            "CREATE INDEX IF NOT EXISTS idx_tmu_people_tmdb_id ON {$wpdb->prefix}tmu_people (tmdb_id)",
            "CREATE INDEX IF NOT EXISTS idx_tmu_people_popularity ON {$wpdb->prefix}tmu_people (popularity)",
            "CREATE INDEX IF NOT EXISTS idx_tmu_people_department ON {$wpdb->prefix}tmu_people (known_for_department)",
            "CREATE INDEX IF NOT EXISTS idx_tmu_people_gender ON {$wpdb->prefix}tmu_people (gender)",
            
            // WordPress postmeta indexes for TMU
            "CREATE INDEX IF NOT EXISTS idx_postmeta_tmu_movie_vote ON {$wpdb->postmeta} (meta_key, meta_value) WHERE meta_key = 'tmu_movie_vote_average'",
            "CREATE INDEX IF NOT EXISTS idx_postmeta_tmu_movie_pop ON {$wpdb->postmeta} (meta_key, meta_value) WHERE meta_key = 'tmu_movie_popularity'",
            
            // Term relationships optimization
            "CREATE INDEX IF NOT EXISTS idx_term_relationships_object_id ON {$wpdb->term_relationships} (object_id)",
            "CREATE INDEX IF NOT EXISTS idx_term_taxonomy_term_id ON {$wpdb->term_taxonomy} (term_id, taxonomy)"
        ];
        
        foreach ($indexes as $index) {
            $wpdb->query($index);
        }
        
        // Analyze tables for better query planning
        $this->analyze_tables();
    }
    
    /**
     * Analyze tables
     */
    private function analyze_tables(): void {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'tmu_movies',
            $wpdb->prefix . 'tmu_tv_series',
            $wpdb->prefix . 'tmu_people',
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->term_relationships,
            $wpdb->term_taxonomy
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("ANALYZE TABLE {$table}");
        }
    }
    
    /**
     * Optimize database cleanup
     */
    public function optimize_database_cleanup(): void {
        global $wpdb;
        
        // Clean up orphaned postmeta
        $wpdb->query("
            DELETE pm FROM {$wpdb->postmeta} pm 
            LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
            WHERE p.ID IS NULL
        ");
        
        // Clean up orphaned term relationships
        $wpdb->query("
            DELETE tr FROM {$wpdb->term_relationships} tr 
            LEFT JOIN {$wpdb->posts} p ON p.ID = tr.object_id 
            WHERE p.ID IS NULL
        ");
        
        // Clean up expired transients
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_timeout_%' 
            AND option_value < UNIX_TIMESTAMP()
        ");
        
        // Optimize tables
        $this->optimize_tables();
    }
    
    /**
     * Optimize tables
     */
    private function optimize_tables(): void {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'tmu_movies',
            $wpdb->prefix . 'tmu_tv_series',
            $wpdb->prefix . 'tmu_people',
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->options
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
    }
    
    /**
     * Perform database maintenance
     */
    public function perform_database_maintenance(): void {
        // Run cleanup
        $this->optimize_database_cleanup();
        
        // Update table statistics
        $this->analyze_tables();
        
        // Clean up old revisions
        $this->cleanup_post_revisions();
        
        // Clean up spam comments
        $this->cleanup_spam_comments();
        
        do_action('tmu_database_maintenance_complete');
    }
    
    /**
     * Cleanup post revisions
     */
    private function cleanup_post_revisions(): void {
        global $wpdb;
        
        // Keep only latest 3 revisions per post
        $wpdb->query("
            DELETE FROM {$wpdb->posts} 
            WHERE post_type = 'revision' 
            AND ID NOT IN (
                SELECT * FROM (
                    SELECT ID FROM {$wpdb->posts} 
                    WHERE post_type = 'revision' 
                    ORDER BY post_parent, post_date DESC
                ) AS keep_revisions
            )
        ");
    }
    
    /**
     * Cleanup spam comments
     */
    private function cleanup_spam_comments(): void {
        global $wpdb;
        
        // Delete spam comments older than 30 days
        $wpdb->query("
            DELETE FROM {$wpdb->comments} 
            WHERE comment_approved = 'spam' 
            AND comment_date < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
    }
    
    /**
     * Admin optimize database
     */
    public function admin_optimize_database(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'tmu'));
        }
        
        check_admin_referer('tmu_optimize_database');
        
        // Perform full database optimization
        $this->perform_database_maintenance();
        $this->create_database_indexes();
        
        wp_redirect(add_query_arg('database_optimized', '1', wp_get_referer()));
        exit;
    }
    
    /**
     * Get database statistics
     */
    public function get_database_stats(): array {
        global $wpdb;
        
        $stats = [];
        
        // Get table sizes
        $tables = [
            'tmu_movies' => $wpdb->prefix . 'tmu_movies',
            'tmu_tv_series' => $wpdb->prefix . 'tmu_tv_series',
            'tmu_people' => $wpdb->prefix . 'tmu_people',
            'posts' => $wpdb->posts,
            'postmeta' => $wpdb->postmeta
        ];
        
        foreach ($tables as $name => $table) {
            $result = $wpdb->get_row("
                SELECT 
                    COUNT(*) as row_count,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE() 
                AND table_name = '{$table}'
            ");
            
            $stats['tables'][$name] = [
                'rows' => $result->row_count ?? 0,
                'size_mb' => $result->size_mb ?? 0
            ];
        }
        
        // Get index usage
        $stats['indexes'] = $this->get_index_usage();
        
        // Get query performance
        $stats['queries'] = $this->query_monitor->get_slow_queries();
        
        return $stats;
    }
    
    /**
     * Get index usage statistics
     */
    private function get_index_usage(): array {
        global $wpdb;
        
        $index_stats = [];
        
        $results = $wpdb->get_results("
            SHOW INDEX FROM {$wpdb->prefix}tmu_movies
        ");
        
        foreach ($results as $index) {
            $index_stats[] = [
                'table' => $index->Table,
                'key_name' => $index->Key_name,
                'column' => $index->Column_name,
                'cardinality' => $index->Cardinality
            ];
        }
        
        return $index_stats;
    }
    
    /**
     * Get query monitor
     */
    public function get_query_monitor(): QueryMonitor {
        return $this->query_monitor;
    }
}

/**
 * Query Monitor Class
 */
class QueryMonitor {
    
    private array $queries = [];
    private float $slow_query_threshold = 0.1; // 100ms
    
    /**
     * Constructor
     */
    public function __construct() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_filter('query', [$this, 'log_query']);
        }
    }
    
    /**
     * Log query
     */
    public function log_query($query): string {
        $start_time = microtime(true);
        
        // Execute query and measure time
        global $wpdb;
        $result = $wpdb->get_results($query);
        
        $execution_time = microtime(true) - $start_time;
        
        $this->queries[] = [
            'query' => $query,
            'execution_time' => $execution_time,
            'timestamp' => current_time('mysql'),
            'is_slow' => $execution_time > $this->slow_query_threshold
        ];
        
        return $query;
    }
    
    /**
     * Get slow queries
     */
    public function get_slow_queries(): array {
        return array_filter($this->queries, function($query) {
            return $query['is_slow'];
        });
    }
    
    /**
     * Get all queries
     */
    public function get_all_queries(): array {
        return $this->queries;
    }
    
    /**
     * Get query statistics
     */
    public function get_query_stats(): array {
        $total_queries = count($this->queries);
        $slow_queries = count($this->get_slow_queries());
        $total_time = array_sum(array_column($this->queries, 'execution_time'));
        
        return [
            'total_queries' => $total_queries,
            'slow_queries' => $slow_queries,
            'total_execution_time' => $total_time,
            'average_execution_time' => $total_queries > 0 ? $total_time / $total_queries : 0
        ];
    }
}