<?php
namespace TMU\Search;

/**
 * Search Index Manager
 * 
 * Manages search indexing for improved performance
 */
class SearchIndexManager {
    
    /**
     * Table name for search index
     * 
     * @var string
     */
    private string $search_table;
    
    /**
     * Table name for taxonomy search
     * 
     * @var string
     */
    private string $taxonomy_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->search_table = $wpdb->prefix . 'tmu_search_index';
        $this->taxonomy_table = $wpdb->prefix . 'tmu_taxonomy_search';
    }
    
    /**
     * Create search indexes
     */
    public function create_search_indexes(): void {
        $this->create_search_table();
        $this->create_taxonomy_search_table();
    }
    
    /**
     * Create main search table
     */
    private function create_search_table(): void {
        global $wpdb;
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->search_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            post_type varchar(20) NOT NULL,
            title text NOT NULL,
            content longtext NOT NULL,
            search_vector text NOT NULL,
            popularity decimal(10,2) DEFAULT 0,
            rating decimal(3,1) DEFAULT 0,
            release_year int(4) DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id),
            KEY post_type (post_type),
            KEY popularity (popularity),
            KEY rating (rating),
            KEY release_year (release_year),
            FULLTEXT KEY search_vector (search_vector)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $wpdb->query($sql);
    }
    
    /**
     * Create taxonomy search table
     */
    private function create_taxonomy_search_table(): void {
        global $wpdb;
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->taxonomy_table} (
            term_id bigint(20) NOT NULL,
            taxonomy varchar(32) NOT NULL,
            name varchar(200) NOT NULL,
            search_content text NOT NULL,
            post_count int(11) DEFAULT 0,
            PRIMARY KEY (term_id, taxonomy),
            FULLTEXT KEY search_content (search_content)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $wpdb->query($sql);
    }
    
    /**
     * Index a post
     * 
     * @param int $post_id Post ID
     */
    public function index_post(int $post_id): void {
        global $wpdb;
        
        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, ['movie', 'tv', 'drama', 'people'])) {
            return;
        }
        
        // Prepare search content
        $content_parts = [
            $post->post_title,
            $post->post_content,
            $post->post_excerpt
        ];
        
        // Add custom field content
        $custom_data = $this->get_post_custom_data($post_id);
        if (!empty($custom_data['overview'])) {
            $content_parts[] = $custom_data['overview'];
        }
        if (!empty($custom_data['tagline'])) {
            $content_parts[] = $custom_data['tagline'];
        }
        if (!empty($custom_data['original_title'])) {
            $content_parts[] = $custom_data['original_title'];
        }
        if (!empty($custom_data['biography'])) {
            $content_parts[] = $custom_data['biography'];
        }
        
        // Add taxonomy terms
        $taxonomies = get_object_taxonomies($post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'names']);
            if (!is_wp_error($terms)) {
                $content_parts = array_merge($content_parts, $terms);
            }
        }
        
        $search_vector = implode(' ', array_filter($content_parts));
        
        // Insert or update search index
        $wpdb->replace($this->search_table, [
            'post_id' => $post_id,
            'post_type' => $post->post_type,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'search_vector' => $search_vector,
            'popularity' => $custom_data['popularity'] ?? 0,
            'rating' => $custom_data['vote_average'] ?? 0,
            'release_year' => $custom_data['year'] ?? null,
            'updated_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Remove post from index
     * 
     * @param int $post_id Post ID
     */
    public function remove_post(int $post_id): void {
        global $wpdb;
        
        $wpdb->delete($this->search_table, ['post_id' => $post_id]);
    }
    
    /**
     * Index taxonomy terms
     */
    public function index_taxonomy_terms(): void {
        global $wpdb;
        
        $taxonomies = ['genre', 'country', 'language', 'network', 'channel'];
        
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ]);
            
            if (is_wp_error($terms)) {
                continue;
            }
            
            foreach ($terms as $term) {
                $search_content = $term->name . ' ' . $term->description;
                
                $wpdb->replace($this->taxonomy_table, [
                    'term_id' => $term->term_id,
                    'taxonomy' => $taxonomy,
                    'name' => $term->name,
                    'search_content' => $search_content,
                    'post_count' => $term->count
                ]);
            }
        }
    }
    
    /**
     * Reindex all content
     */
    public function reindex_all(): void {
        global $wpdb;
        
        // Clear existing indexes
        $wpdb->query("TRUNCATE TABLE {$this->search_table}");
        $wpdb->query("TRUNCATE TABLE {$this->taxonomy_table}");
        
        // Reindex posts
        $post_types = ['movie', 'tv', 'drama', 'people'];
        
        foreach ($post_types as $post_type) {
            $posts = get_posts([
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids'
            ]);
            
            foreach ($posts as $post_id) {
                $this->index_post($post_id);
                
                // Prevent timeout for large datasets
                if (count($posts) > 100) {
                    usleep(10000); // 0.01 second
                }
            }
        }
        
        // Reindex taxonomy terms
        $this->index_taxonomy_terms();
    }
    
    /**
     * Search using fulltext index
     * 
     * @param string $query Search query
     * @param array $filters Additional filters
     * @return array Post IDs
     */
    public function fulltext_search(string $query, array $filters = []): array {
        global $wpdb;
        
        if (empty($query)) {
            return [];
        }
        
        $where_conditions = ["MATCH(search_vector) AGAINST(%s IN BOOLEAN MODE)"];
        $params = [$query];
        
        // Add post type filter
        if (!empty($filters['post_type'])) {
            $post_types = (array) $filters['post_type'];
            $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
            $where_conditions[] = "post_type IN ($placeholders)";
            $params = array_merge($params, $post_types);
        }
        
        // Add year filter
        if (!empty($filters['year'])) {
            $where_conditions[] = "release_year = %d";
            $params[] = $filters['year'];
        }
        
        // Add rating filter
        if (!empty($filters['rating_min'])) {
            $where_conditions[] = "rating >= %f";
            $params[] = $filters['rating_min'];
        }
        
        if (!empty($filters['rating_max'])) {
            $where_conditions[] = "rating <= %f";
            $params[] = $filters['rating_max'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT post_id, 
                       MATCH(search_vector) AGAINST(%s IN BOOLEAN MODE) as relevance_score
                FROM {$this->search_table} 
                WHERE {$where_clause}
                ORDER BY relevance_score DESC, popularity DESC
                LIMIT %d";
        
        $params[] = $query; // For relevance score calculation
        $params[] = $filters['limit'] ?? 100;
        
        $prepared_sql = $wpdb->prepare($sql, $params);
        $results = $wpdb->get_results($prepared_sql);
        
        return array_column($results, 'post_id');
    }
    
    /**
     * Get search statistics
     * 
     * @return array Statistics
     */
    public function get_search_statistics(): array {
        global $wpdb;
        
        $stats = [];
        
        // Total indexed posts
        $stats['total_posts'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->search_table}");
        
        // Posts by type
        $post_type_counts = $wpdb->get_results(
            "SELECT post_type, COUNT(*) as count FROM {$this->search_table} GROUP BY post_type"
        );
        
        foreach ($post_type_counts as $row) {
            $stats['by_type'][$row->post_type] = $row->count;
        }
        
        // Total indexed terms
        $stats['total_terms'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->taxonomy_table}");
        
        // Last update
        $stats['last_update'] = $wpdb->get_var(
            "SELECT MAX(updated_at) FROM {$this->search_table}"
        );
        
        return $stats;
    }
    
    /**
     * Get custom data for post
     * 
     * @param int $post_id Post ID
     * @return array Custom data
     */
    private function get_post_custom_data(int $post_id): array {
        $post_type = get_post_type($post_id);
        
        switch ($post_type) {
            case 'movie':
                return function_exists('tmu_get_movie_data') ? tmu_get_movie_data($post_id) : [];
            case 'tv':
            case 'drama':
                return function_exists('tmu_get_tv_data') ? tmu_get_tv_data($post_id) : [];
            case 'people':
                return function_exists('tmu_get_person_data') ? tmu_get_person_data($post_id) : [];
            default:
                return [];
        }
    }
    
    /**
     * Check if search tables exist
     * 
     * @return bool Tables exist
     */
    public function tables_exist(): bool {
        global $wpdb;
        
        $search_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->search_table}'") === $this->search_table;
        $taxonomy_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->taxonomy_table}'") === $this->taxonomy_table;
        
        return $search_table_exists && $taxonomy_table_exists;
    }
}