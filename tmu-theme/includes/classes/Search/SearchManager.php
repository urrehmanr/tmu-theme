<?php
namespace TMU\Search;

class SearchManager {
    /**
     * Instance
     * @var SearchManager|null
     */
    private static $instance = null;
    
    private $search_engine;
    private $filter_manager;
    private $ajax_search;
    private $recommendation_engine;
    
    /**
     * Get instance
     * 
     * @return SearchManager
     */
    public static function getInstance(): SearchManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->search_engine = new SearchEngine();
        $this->filter_manager = new FilterManager();
        $this->ajax_search = new AjaxSearch();
        $this->recommendation_engine = new RecommendationEngine();
        
        // Initialize right away
        $this->init();
    }
    
    public function init(): void {
        add_action('init', [$this, 'register_search_endpoints']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('pre_get_posts', [$this, 'enhance_search_query']);
        add_filter('posts_search', [$this, 'enhance_search_sql'], 10, 2);
        add_filter('posts_orderby', [$this, 'enhance_search_orderby'], 10, 2);
        
        // Initialize sub-components
        $this->ajax_search->init();
        
        // Create search index if not exists
        $this->maybe_create_search_index();
    }
    
    public function search($query, $filters = [], $options = []): SearchResult {
        $start_time = microtime(true);
        
        // Sanitize and prepare query
        $search_query = $this->prepare_search_query($query);
        
        // Build WordPress query arguments
        $args = $this->build_search_args($search_query, $filters, $options);
        
        // Execute search
        $wp_query = new \WP_Query($args);
        
        // Get facet data
        $facets = $this->filter_manager->get_facet_data($search_query, $filters);
        
        // Process results
        $results = $wp_query->posts;
        
        // Get recommendations if no results
        $recommendations = [];
        if (empty($results) && !empty($search_query)) {
            $recommendations = $this->get_search_recommendations($search_query);
        }
        
        $execution_time = microtime(true) - $start_time;
        
        return new SearchResult([
            'results' => $results,
            'total_found' => $wp_query->found_posts,
            'facets' => $facets,
            'recommendations' => $recommendations,
            'query' => $search_query,
            'filters' => $filters,
            'execution_time' => $execution_time,
            'max_pages' => $wp_query->max_num_pages
        ]);
    }
    
    private function prepare_search_query($query): string {
        // Remove special characters, normalize spaces
        $query = preg_replace('/[^\w\s\-_"\'()]/', ' ', $query);
        $query = preg_replace('/\s+/', ' ', trim($query));
        
        return $query;
    }
    
    private function build_search_args($search_query, $filters, $options): array {
        $args = [
            'post_type' => $this->get_searchable_post_types(),
            'post_status' => 'publish',
            'posts_per_page' => $options['per_page'] ?? 20,
            'paged' => $options['page'] ?? 1,
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => true
        ];
        
        // Add search query
        if (!empty($search_query)) {
            $args['s'] = $search_query;
            
            // Custom search in meta fields
            $args['meta_query'] = $this->build_meta_search($search_query);
        }
        
        // Apply filters
        $args = $this->filter_manager->apply_filters($args, $filters);
        
        // Apply sorting
        $args = $this->apply_sorting($args, $options['orderby'] ?? 'relevance');
        
        return $args;
    }
    
    private function build_meta_search($search_query): array {
        return [
            'relation' => 'OR',
            [
                'key' => 'tmu_movie_original_title',
                'value' => $search_query,
                'compare' => 'LIKE'
            ],
            [
                'key' => 'tmu_movie_overview',
                'value' => $search_query,
                'compare' => 'LIKE'
            ],
            [
                'key' => 'tmu_movie_tagline',
                'value' => $search_query,
                'compare' => 'LIKE'
            ],
            [
                'key' => 'tmu_tv_overview',
                'value' => $search_query,
                'compare' => 'LIKE'
            ],
            [
                'key' => 'tmu_person_biography',
                'value' => $search_query,
                'compare' => 'LIKE'
            ]
        ];
    }
    
    private function apply_sorting($args, $orderby): array {
        switch ($orderby) {
            case 'rating':
                $args['meta_key'] = 'tmu_movie_vote_average';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
                
            case 'popularity':
                $args['meta_key'] = 'tmu_movie_popularity';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
                
            case 'date':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
                
            case 'title':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
                
            case 'relevance':
            default:
                $args['orderby'] = 'relevance';
                $args['order'] = 'DESC';
                break;
        }
        
        return $args;
    }
    
    private function get_searchable_post_types(): array {
        $post_types = ['movie', 'tv', 'people'];
        
        if (get_option('tmu_dramas') === 'on') {
            $post_types[] = 'drama';
        }
        
        return $post_types;
    }
    
    private function get_search_recommendations($query): array {
        // Get similar terms from taxonomy
        $similar_terms = get_terms([
            'taxonomy' => ['genre', 'country', 'language'],
            'search' => $query,
            'number' => 5
        ]);
        
        $recommendations = [];
        
        foreach ($similar_terms as $term) {
            $recommendations[] = [
                'type' => 'term',
                'title' => $term->name,
                'url' => get_term_link($term),
                'count' => $term->count
            ];
        }
        
        return $recommendations;
    }
    
    public function enhance_search_query($query): void {
        if (!is_admin() && $query->is_main_query() && $query->is_search()) {
            $query->set('post_type', $this->get_searchable_post_types());
        }
    }
    
    public function enhance_search_sql($search, $query): string {
        if (!$query->is_search() || empty($search)) {
            return $search;
        }
        
        global $wpdb;
        
        $search_term = $query->get('s');
        if (empty($search_term)) {
            return $search;
        }
        
        // Add relevance scoring
        $relevance_score = "
            (
                CASE WHEN {$wpdb->posts}.post_title LIKE '%{$search_term}%' THEN 50 ELSE 0 END +
                CASE WHEN {$wpdb->posts}.post_content LIKE '%{$search_term}%' THEN 20 ELSE 0 END +
                CASE WHEN {$wpdb->posts}.post_excerpt LIKE '%{$search_term}%' THEN 10 ELSE 0 END
            ) as relevance_score
        ";
        
        return $search . ", " . $relevance_score;
    }
    
    public function enhance_search_orderby($orderby, $query): string {
        if (!$query->is_search() || $query->get('orderby') !== 'relevance') {
            return $orderby;
        }
        
        return 'relevance_score DESC, post_date DESC';
    }
    
    public function enqueue_scripts(): void {
        if (is_search() || is_page_template('templates/search.php')) {
            wp_enqueue_script(
                'tmu-search',
                get_template_directory_uri() . '/assets/js/search.js',
                ['jquery', 'jquery-ui-autocomplete'],
                TMU_VERSION,
                true
            );
            
            wp_localize_script('tmu-search', 'tmuSearch', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'rest_url' => rest_url('tmu/v1/'),
                'nonce' => wp_create_nonce('tmu_search_nonce'),
                'strings' => [
                    'searching' => __('Searching...', 'tmu'),
                    'no_results' => __('No results found', 'tmu'),
                    'load_more' => __('Load More', 'tmu'),
                    'loading' => __('Loading...', 'tmu'),
                    'error' => __('An error occurred. Please try again.', 'tmu')
                ]
            ]);
        }
    }
    
    public function register_search_endpoints(): void {
        add_action('rest_api_init', function() {
            register_rest_route('tmu/v1', '/search', [
                'methods' => 'GET',
                'callback' => [$this, 'rest_search'],
                'permission_callback' => '__return_true',
                'args' => [
                    'q' => [
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'post_type' => [
                        'required' => false,
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'per_page' => [
                        'required' => false,
                        'type' => 'integer',
                        'default' => 20
                    ],
                    'page' => [
                        'required' => false,
                        'type' => 'integer',
                        'default' => 1
                    ]
                ]
            ]);
            
            register_rest_route('tmu/v1', '/suggestions', [
                'methods' => 'GET',
                'callback' => [$this, 'rest_suggestions'],
                'permission_callback' => '__return_true',
                'args' => [
                    'q' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ]);
        });
    }
    
    public function rest_search($request): \WP_REST_Response {
        $query = $request->get_param('q');
        $post_type = $request->get_param('post_type');
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        
        $filters = [];
        if ($post_type) {
            $filters['post_type'] = $post_type;
        }
        
        $options = [
            'per_page' => $per_page,
            'page' => $page
        ];
        
        $results = $this->search($query, $filters, $options);
        
        return new \WP_REST_Response([
            'results' => array_map([$this, 'format_search_result'], $results->get_results()),
            'total' => $results->get_total(),
            'facets' => $results->get_facets(),
            'execution_time' => $results->get_execution_time()
        ]);
    }
    
    public function rest_suggestions($request): \WP_REST_Response {
        $query = $request->get_param('q');
        
        if (strlen($query) < 2) {
            return new \WP_REST_Response([]);
        }
        
        $suggestions = $this->ajax_search->get_suggestions($query);
        
        return new \WP_REST_Response($suggestions);
    }
    
    private function format_search_result($post): array {
        $post_type = get_post_type($post->ID);
        $data = [];
        
        switch ($post_type) {
            case 'movie':
                $movie_data = tmu_get_movie_data($post->ID);
                $data = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'type' => 'movie',
                    'year' => $movie_data['release_date'] ? date('Y', strtotime($movie_data['release_date'])) : null,
                    'rating' => $movie_data['vote_average'],
                    'overview' => $movie_data['overview'],
                    'poster' => get_the_post_thumbnail_url($post->ID, 'medium'),
                    'url' => get_permalink($post->ID)
                ];
                break;
                
            case 'tv':
            case 'drama':
                $tv_data = tmu_get_tv_data($post->ID);
                $data = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'type' => $post_type,
                    'year' => $tv_data['first_air_date'] ? date('Y', strtotime($tv_data['first_air_date'])) : null,
                    'rating' => $tv_data['vote_average'],
                    'overview' => $tv_data['overview'],
                    'poster' => get_the_post_thumbnail_url($post->ID, 'medium'),
                    'url' => get_permalink($post->ID)
                ];
                break;
                
            case 'people':
                $person_data = tmu_get_person_data($post->ID);
                $data = [
                    'id' => $post->ID,
                    'name' => $post->post_title,
                    'type' => 'person',
                    'known_for' => $person_data['known_for_department'],
                    'biography' => $person_data['biography'],
                    'profile' => get_the_post_thumbnail_url($post->ID, 'medium'),
                    'url' => get_permalink($post->ID)
                ];
                break;
        }
        
        return $data;
    }
    
    private function maybe_create_search_index(): void {
        if (!get_option('tmu_search_index_created')) {
            $this->create_search_index();
            update_option('tmu_search_index_created', true);
        }
    }
    
    private function create_search_index(): void {
        global $wpdb;
        
        // Create search index table
        $table_name = $wpdb->prefix . 'tmu_search_index';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function get_search_engine(): SearchEngine {
        return $this->search_engine;
    }
    
    public function get_filter_manager(): FilterManager {
        return $this->filter_manager;
    }
    
    public function get_recommendation_engine(): RecommendationEngine {
        return $this->recommendation_engine;
    }
}