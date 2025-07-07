<?php
/**
 * Database Security
 * 
 * SQL injection prevention and database security measures.
 * Ensures all database queries use prepared statements and proper escaping.
 * 
 * @package TMU\Security
 * @since 1.0.0
 */

namespace TMU\Security;

/**
 * DatabaseSecurity class
 * 
 * Handles database security and SQL injection prevention
 */
class DatabaseSecurity {
    
    /**
     * Allowed tables for queries
     * @var array
     */
    private $allowed_tables = [];
    
    /**
     * Query cache
     * @var array
     */
    private $query_cache = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_allowed_tables();
        $this->init_hooks();
    }
    
    /**
     * Initialize allowed tables
     */
    private function init_allowed_tables(): void {
        global $wpdb;
        
        $this->allowed_tables = [
            // WordPress core tables
            'posts' => $wpdb->posts,
            'postmeta' => $wpdb->postmeta,
            'terms' => $wpdb->terms,
            'term_taxonomy' => $wpdb->term_taxonomy,
            'term_relationships' => $wpdb->term_relationships,
            'users' => $wpdb->users,
            'usermeta' => $wpdb->usermeta,
            'options' => $wpdb->options,
            
            // TMU custom tables
            'tmu_movies' => $wpdb->prefix . 'tmu_movies',
            'tmu_tv_series' => $wpdb->prefix . 'tmu_tv_series',
            'tmu_dramas' => $wpdb->prefix . 'tmu_dramas',
            'tmu_people' => $wpdb->prefix . 'tmu_people',
            'tmu_seasons' => $wpdb->prefix . 'tmu_seasons',
            'tmu_episodes' => $wpdb->prefix . 'tmu_episodes',
            'tmu_drama_episodes' => $wpdb->prefix . 'tmu_drama_episodes',
            'tmu_videos' => $wpdb->prefix . 'tmu_videos',
            'tmu_networks' => $wpdb->prefix . 'tmu_networks',
            'tmu_channels' => $wpdb->prefix . 'tmu_channels',
        ];
        
        // Allow customization via filters
        $this->allowed_tables = apply_filters('tmu_allowed_tables', $this->allowed_tables);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        add_action('init', [$this, 'init_database_security']);
        
        // Override direct database queries with prepared statements
        add_filter('tmu_get_movie_data', [$this, 'get_movie_data_secure'], 10, 2);
        add_filter('tmu_get_tv_data', [$this, 'get_tv_data_secure'], 10, 2);
        add_filter('tmu_get_drama_data', [$this, 'get_drama_data_secure'], 10, 2);
        add_filter('tmu_get_people_data', [$this, 'get_people_data_secure'], 10, 2);
        add_filter('tmu_search_content', [$this, 'search_content_secure'], 10, 2);
        
        // Query monitoring and logging
        add_action('tmu_database_query', [$this, 'log_database_query'], 10, 3);
        
        // Database connection security
        add_filter('wpdb_connection_setup', [$this, 'secure_database_connection']);
        
        // Query validation
        add_filter('query', [$this, 'validate_query'], 10, 1);
    }
    
    /**
     * Initialize database security measures
     */
    public function init_database_security(): void {
        // Set secure database connection options
        $this->set_database_security_options();
        
        // Initialize query validation
        $this->init_query_validation();
        
        // Set up query monitoring
        $this->init_query_monitoring();
    }
    
    /**
     * Get movie data securely
     */
    public function get_movie_data_secure($data, $post_id): array {
        global $wpdb;
        
        $post_id = intval($post_id);
        
        if ($post_id <= 0) {
            return [];
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->allowed_tables['tmu_movies']} WHERE ID = %d",
            $post_id
        );
        
        $result = $wpdb->get_row($query, ARRAY_A);
        
        // Log the query for monitoring
        $this->log_query($query, 'get_movie_data', $post_id);
        
        return $result ?: [];
    }
    
    /**
     * Get TV series data securely
     */
    public function get_tv_data_secure($data, $post_id): array {
        global $wpdb;
        
        $post_id = intval($post_id);
        
        if ($post_id <= 0) {
            return [];
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->allowed_tables['tmu_tv_series']} WHERE ID = %d",
            $post_id
        );
        
        $result = $wpdb->get_row($query, ARRAY_A);
        
        $this->log_query($query, 'get_tv_data', $post_id);
        
        return $result ?: [];
    }
    
    /**
     * Get drama data securely
     */
    public function get_drama_data_secure($data, $post_id): array {
        global $wpdb;
        
        $post_id = intval($post_id);
        
        if ($post_id <= 0) {
            return [];
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->allowed_tables['tmu_dramas']} WHERE ID = %d",
            $post_id
        );
        
        $result = $wpdb->get_row($query, ARRAY_A);
        
        $this->log_query($query, 'get_drama_data', $post_id);
        
        return $result ?: [];
    }
    
    /**
     * Get people data securely
     */
    public function get_people_data_secure($data, $post_id): array {
        global $wpdb;
        
        $post_id = intval($post_id);
        
        if ($post_id <= 0) {
            return [];
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->allowed_tables['tmu_people']} WHERE ID = %d",
            $post_id
        );
        
        $result = $wpdb->get_row($query, ARRAY_A);
        
        $this->log_query($query, 'get_people_data', $post_id);
        
        return $result ?: [];
    }
    
    /**
     * Search content securely
     */
    public function search_content_secure($results, $search_params): array {
        global $wpdb;
        
        $search_term = sanitize_text_field($search_params['term'] ?? '');
        $post_types = array_map('sanitize_text_field', $search_params['post_types'] ?? ['movie']);
        $limit = intval($search_params['limit'] ?? 10);
        $offset = intval($search_params['offset'] ?? 0);
        
        // Validate parameters
        if (empty($search_term) || $limit <= 0 || $limit > 100) {
            return [];
        }
        
        // Validate post types
        $allowed_post_types = ['movie', 'tv', 'drama', 'people'];
        $post_types = array_intersect($post_types, $allowed_post_types);
        
        if (empty($post_types)) {
            return [];
        }
        
        $post_types_placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        $search_pattern = '%' . $wpdb->esc_like($search_term) . '%';
        
        $query = $wpdb->prepare(
            "SELECT DISTINCT p.ID, p.post_title, p.post_type, p.post_date,
                    COALESCE(m.title, t.name, d.title, pe.name) as display_title,
                    COALESCE(m.poster_path, t.poster_path, d.poster_path, pe.profile_path) as image_path,
                    COALESCE(m.overview, t.overview, d.overview, pe.biography) as description,
                    COALESCE(m.tmdb_vote_average, t.tmdb_vote_average, d.tmdb_vote_average, 0) as rating
             FROM {$this->allowed_tables['posts']} p
             LEFT JOIN {$this->allowed_tables['tmu_movies']} m ON p.ID = m.ID AND p.post_type = 'movie'
             LEFT JOIN {$this->allowed_tables['tmu_tv_series']} t ON p.ID = t.ID AND p.post_type = 'tv'
             LEFT JOIN {$this->allowed_tables['tmu_dramas']} d ON p.ID = d.ID AND p.post_type = 'drama'
             LEFT JOIN {$this->allowed_tables['tmu_people']} pe ON p.ID = pe.ID AND p.post_type = 'people'
             WHERE p.post_type IN ({$post_types_placeholders})
             AND p.post_status = 'publish'
             AND (
                 p.post_title LIKE %s OR 
                 m.title LIKE %s OR 
                 m.original_title LIKE %s OR
                 t.name LIKE %s OR 
                 t.original_name LIKE %s OR
                 d.title LIKE %s OR 
                 d.original_title LIKE %s OR
                 pe.name LIKE %s OR
                 pe.original_name LIKE %s
             )
             ORDER BY 
                 CASE 
                     WHEN p.post_title LIKE %s THEN 1
                     WHEN COALESCE(m.title, t.name, d.title, pe.name) LIKE %s THEN 2
                     ELSE 3
                 END,
                 COALESCE(m.tmdb_vote_average, t.tmdb_vote_average, d.tmdb_vote_average, 0) DESC,
                 p.post_date DESC
             LIMIT %d OFFSET %d",
            array_merge(
                $post_types,
                array_fill(0, 9, $search_pattern), // 9 LIKE comparisons
                [$search_pattern, $search_pattern], // 2 ORDER BY comparisons
                [$limit, $offset]
            )
        );
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        $this->log_query($query, 'search_content', $search_term);
        
        return $results ?: [];
    }
    
    /**
     * Execute secure query
     */
    public function execute_secure_query($query, $table, $params = []): array {
        global $wpdb;
        
        // Validate table
        if (!$this->is_allowed_table($table)) {
            $this->log_security_event('unauthorized_table_access', [
                'table' => $table,
                'query' => $query
            ]);
            return [];
        }
        
        // Validate query structure
        if (!$this->validate_query_structure($query)) {
            $this->log_security_event('invalid_query_structure', [
                'query' => $query
            ]);
            return [];
        }
        
        // Prepare and execute query
        if (!empty($params)) {
            $prepared_query = $wpdb->prepare($query, $params);
        } else {
            $prepared_query = $query;
        }
        
        $result = $wpdb->get_results($prepared_query, ARRAY_A);
        
        $this->log_query($prepared_query, 'execute_secure_query', $table);
        
        return $result ?: [];
    }
    
    /**
     * Secure database connection
     */
    public function secure_database_connection($connection): void {
        if ($connection) {
            // Set secure connection options
            mysqli_options($connection, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
            
            // Set character set
            mysqli_set_charset($connection, 'utf8mb4');
            
            // Set SQL mode for strict queries
            mysqli_query($connection, "SET sql_mode = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        }
    }
    
    /**
     * Validate query
     */
    public function validate_query($query): string {
        // Remove potential SQL injection patterns
        $dangerous_patterns = [
            '/(\s|^)(union|select|insert|update|delete|drop|create|alter|exec|execute|script|javascript|vbscript)\s/i',
            '/(\s|^)(or|and)\s+\d+\s*=\s*\d+/i',
            '/(\s|^)(or|and)\s+[\'"].*[\'\"]\s*=\s*[\'"].*[\'\"]/i',
            '/(\s|^)(or|and)\s+\d+\s*[<>]\s*\d+/i',
            '/(\s|^)(\-\-|\#|\/\*)/i',
            '/(\s|^)(information_schema|mysql|sys|performance_schema)\./i'
        ];
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $query)) {
                $this->log_security_event('sql_injection_attempt', [
                    'query' => $query,
                    'pattern' => $pattern
                ]);
                
                // Return empty string to prevent execution
                return '';
            }
        }
        
        return $query;
    }
    
    /**
     * Set database security options
     */
    private function set_database_security_options(): void {
        global $wpdb;
        
        // Set connection timeout
        $wpdb->query("SET SESSION connect_timeout = 10");
        
        // Set query timeout
        $wpdb->query("SET SESSION max_execution_time = 30");
        
        // Disable LOAD DATA LOCAL INFILE
        $wpdb->query("SET GLOBAL local_infile = 0");
        
        // Set secure file privileges
        $wpdb->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    }
    
    /**
     * Initialize query validation
     */
    private function init_query_validation(): void {
        // Add query filters for validation
        add_filter('posts_where', [$this, 'validate_posts_where']);
        add_filter('posts_orderby', [$this, 'validate_posts_orderby']);
        add_filter('posts_join', [$this, 'validate_posts_join']);
    }
    
    /**
     * Initialize query monitoring
     */
    private function init_query_monitoring(): void {
        // Enable query logging in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('shutdown', [$this, 'log_slow_queries']);
        }
    }
    
    /**
     * Validate posts WHERE clause
     */
    public function validate_posts_where($where): string {
        return $this->sanitize_sql_clause($where);
    }
    
    /**
     * Validate posts ORDER BY clause
     */
    public function validate_posts_orderby($orderby): string {
        return $this->sanitize_sql_clause($orderby);
    }
    
    /**
     * Validate posts JOIN clause
     */
    public function validate_posts_join($join): string {
        return $this->sanitize_sql_clause($join);
    }
    
    /**
     * Sanitize SQL clause
     */
    private function sanitize_sql_clause($clause): string {
        // Remove dangerous SQL patterns
        $clause = preg_replace('/(\s|^)(\-\-|\#|\/\*).*$/m', '', $clause);
        $clause = preg_replace('/(\s|^)(union|exec|execute|script)\s/i', ' ', $clause);
        
        return $clause;
    }
    
    /**
     * Is allowed table
     */
    private function is_allowed_table($table): bool {
        return in_array($table, array_values($this->allowed_tables)) || 
               array_key_exists($table, $this->allowed_tables);
    }
    
    /**
     * Validate query structure
     */
    private function validate_query_structure($query): bool {
        // Check for basic SQL structure validity
        $query = trim($query);
        
        // Must start with SELECT, INSERT, UPDATE, or DELETE
        if (!preg_match('/^(SELECT|INSERT|UPDATE|DELETE)\s/i', $query)) {
            return false;
        }
        
        // Check for balanced parentheses
        if (substr_count($query, '(') !== substr_count($query, ')')) {
            return false;
        }
        
        // Check for balanced quotes
        if (substr_count($query, "'") % 2 !== 0 || substr_count($query, '"') % 2 !== 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Log query
     */
    private function log_query($query, $context, $identifier = ''): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            do_action('tmu_database_query', $query, $context, $identifier);
        }
    }
    
    /**
     * Log database query
     */
    public function log_database_query($query, $context, $identifier): void {
        $log_data = [
            'query' => $query,
            'context' => $context,
            'identifier' => $identifier,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'ip' => $this->get_client_ip()
        ];
        
        // Store in cache for analysis
        $this->query_cache[] = $log_data;
        
        // Keep only last 100 queries
        if (count($this->query_cache) > 100) {
            array_shift($this->query_cache);
        }
    }
    
    /**
     * Log slow queries
     */
    public function log_slow_queries(): void {
        global $wpdb;
        
        if (isset($wpdb->queries)) {
            foreach ($wpdb->queries as $query_data) {
                $execution_time = $query_data[1] ?? 0;
                
                // Log queries that take more than 1 second
                if ($execution_time > 1.0) {
                    $this->log_security_event('slow_query', [
                        'query' => $query_data[0],
                        'execution_time' => $execution_time,
                        'stack_trace' => $query_data[2] ?? ''
                    ]);
                }
            }
        }
    }
    
    /**
     * Log security event
     */
    private function log_security_event($type, $data): void {
        do_action('tmu_security_event', $type, $data, 'high');
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip(): string {
        $ip_headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get query cache
     */
    public function get_query_cache(): array {
        return $this->query_cache;
    }
    
    /**
     * Clear query cache
     */
    public function clear_query_cache(): void {
        $this->query_cache = [];
    }
    
    /**
     * Get allowed tables
     */
    public function get_allowed_tables(): array {
        return $this->allowed_tables;
    }
    
    /**
     * Add allowed table
     */
    public function add_allowed_table($key, $table_name): void {
        $this->allowed_tables[$key] = $table_name;
    }
}