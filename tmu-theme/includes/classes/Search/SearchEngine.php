<?php
namespace TMU\Search;

class SearchEngine {
    private $index_manager;
    private $query_builder;
    private $result_processor;
    
    public function __construct() {
        $this->index_manager = new SearchIndexManager();
        $this->query_builder = new QueryBuilder();
        $this->result_processor = new ResultProcessor();
    }
    
    public function search($query, $filters = [], $options = []): SearchResult {
        $start_time = microtime(true);
        
        // Prepare search query
        $search_query = $this->prepare_search_query($query);
        
        // Build database query with filters
        $db_query = $this->query_builder->build($search_query, $filters, $options);
        
        // Execute search
        $raw_results = $this->execute_search($db_query);
        
        // Process and rank results
        $processed_results = $this->result_processor->process($raw_results, $search_query);
        
        // Get aggregations for faceted search
        $aggregations = $this->get_aggregations($filters, $search_query);
        
        $execution_time = microtime(true) - $start_time;
        
        return new SearchResult([
            'results' => $processed_results,
            'total_found' => count($raw_results),
            'aggregations' => $aggregations,
            'query' => $search_query,
            'filters' => $filters,
            'execution_time' => $execution_time
        ]);
    }
    
    private function prepare_search_query($query): string {
        // Remove special characters, normalize spaces
        $query = preg_replace('/[^\w\s\-_"\'()]/', ' ', $query);
        $query = preg_replace('/\s+/', ' ', trim($query));
        
        // Handle quoted phrases
        $phrases = [];
        if (preg_match_all('/"([^"]+)"/', $query, $matches)) {
            $phrases = $matches[1];
            $query = preg_replace('/"[^"]+"/', '', $query);
        }
        
        return $query;
    }
    
    private function execute_search($db_query): array {
        // Use WordPress WP_Query for search
        $wp_query = new \WP_Query($db_query);
        
        return $wp_query->posts;
    }
    
    private function get_aggregations($filters, $query): array {
        return [
            'post_types' => $this->get_post_type_counts($query, $filters),
            'genres' => $this->get_genre_counts($query, $filters),
            'years' => $this->get_year_counts($query, $filters),
            'countries' => $this->get_country_counts($query, $filters),
            'ratings' => $this->get_rating_ranges($query, $filters)
        ];
    }
    
    private function get_post_type_counts($query, $filters): array {
        $counts = [];
        $post_types = ['movie', 'tv', 'people'];
        
        if (get_option('tmu_dramas') === 'on') {
            $post_types[] = 'drama';
        }
        
        foreach ($post_types as $post_type) {
            $temp_filters = $filters;
            unset($temp_filters['post_type']);
            
            $args = [
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'no_found_rows' => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false
            ];
            
            if (!empty($query)) {
                $args['s'] = $query;
            }
            
            $temp_query = new \WP_Query($args);
            $counts[$post_type] = $temp_query->found_posts;
        }
        
        return $counts;
    }
    
    private function get_genre_counts($query, $filters): array {
        $counts = [];
        $genres = get_terms([
            'taxonomy' => 'genre',
            'hide_empty' => true,
            'number' => 50
        ]);
        
        foreach ($genres as $genre) {
            $temp_filters = $filters;
            unset($temp_filters['genre']);
            
            $args = [
                'post_type' => ['movie', 'tv', 'drama'],
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'no_found_rows' => true,
                'tax_query' => [
                    [
                        'taxonomy' => 'genre',
                        'field' => 'term_id',
                        'terms' => $genre->term_id
                    ]
                ]
            ];
            
            if (!empty($query)) {
                $args['s'] = $query;
            }
            
            $temp_query = new \WP_Query($args);
            $counts[$genre->slug] = $temp_query->found_posts;
        }
        
        return $counts;
    }
    
    private function get_year_counts($query, $filters): array {
        $counts = [];
        $years = range(date('Y'), 1900, -1);
        
        foreach ($years as $year) {
            $temp_filters = $filters;
            unset($temp_filters['year']);
            
            $args = [
                'post_type' => ['movie', 'tv', 'drama'],
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'no_found_rows' => true,
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => 'tmu_movie_release_date',
                        'value' => $year,
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'tmu_tv_first_air_date',
                        'value' => $year,
                        'compare' => 'LIKE'
                    ]
                ]
            ];
            
            if (!empty($query)) {
                $args['s'] = $query;
            }
            
            $temp_query = new \WP_Query($args);
            if ($temp_query->found_posts > 0) {
                $counts[$year] = $temp_query->found_posts;
            }
        }
        
        return $counts;
    }
    
    private function get_country_counts($query, $filters): array {
        $counts = [];
        $countries = get_terms([
            'taxonomy' => 'country',
            'hide_empty' => true,
            'number' => 100
        ]);
        
        foreach ($countries as $country) {
            $temp_filters = $filters;
            unset($temp_filters['country']);
            
            $args = [
                'post_type' => ['movie', 'tv', 'drama'],
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'no_found_rows' => true,
                'tax_query' => [
                    [
                        'taxonomy' => 'country',
                        'field' => 'term_id',
                        'terms' => $country->term_id
                    ]
                ]
            ];
            
            if (!empty($query)) {
                $args['s'] = $query;
            }
            
            $temp_query = new \WP_Query($args);
            $counts[$country->slug] = $temp_query->found_posts;
        }
        
        return $counts;
    }
    
    private function get_rating_ranges($query, $filters): array {
        $ranges = [
            '9-10' => ['min' => 9, 'max' => 10],
            '8-9' => ['min' => 8, 'max' => 9],
            '7-8' => ['min' => 7, 'max' => 8],
            '6-7' => ['min' => 6, 'max' => 7],
            '5-6' => ['min' => 5, 'max' => 6],
            '0-5' => ['min' => 0, 'max' => 5]
        ];
        
        $counts = [];
        
        foreach ($ranges as $range_key => $range) {
            $temp_filters = $filters;
            unset($temp_filters['rating']);
            
            $args = [
                'post_type' => ['movie', 'tv', 'drama'],
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'no_found_rows' => true,
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => 'tmu_movie_vote_average',
                        'value' => [$range['min'], $range['max']],
                        'type' => 'NUMERIC',
                        'compare' => 'BETWEEN'
                    ],
                    [
                        'key' => 'tmu_tv_vote_average',
                        'value' => [$range['min'], $range['max']],
                        'type' => 'NUMERIC',
                        'compare' => 'BETWEEN'
                    ]
                ]
            ];
            
            if (!empty($query)) {
                $args['s'] = $query;
            }
            
            $temp_query = new \WP_Query($args);
            $counts[$range_key] = $temp_query->found_posts;
        }
        
        return $counts;
    }
    
    public function get_trending_content($post_type = null, $limit = 20): array {
        $cache_key = "tmu_trending_{$post_type}_{$limit}";
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $post_types = $post_type ? [$post_type] : ['movie', 'tv', 'drama'];
        
        $args = [
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => 'tmu_movie_popularity',
                    'compare' => 'EXISTS'
                ]
            ],
            'orderby' => 'meta_value_num',
            'meta_key' => 'tmu_movie_popularity',
            'order' => 'DESC',
            'date_query' => [
                [
                    'after' => '30 days ago',
                    'column' => 'post_modified'
                ]
            ]
        ];
        
        $trending = get_posts($args);
        
        set_transient($cache_key, $trending, HOUR_IN_SECONDS);
        
        return $trending;
    }
}