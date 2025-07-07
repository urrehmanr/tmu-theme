<?php
namespace TMU\Search\Facets;

/**
 * Taxonomy Facet
 * 
 * Handles filtering by taxonomy terms (genre, country, language, network, channel)
 */
class TaxonomyFacet {
    
    /**
     * Taxonomy name
     * 
     * @var string
     */
    private string $taxonomy;
    
    /**
     * Constructor
     * 
     * @param string $taxonomy Taxonomy name
     */
    public function __construct(string $taxonomy) {
        $this->taxonomy = $taxonomy;
    }
    
    /**
     * Get facet options with counts
     * 
     * @param string $search_query Current search query
     * @param array $current_filters Current filters applied
     * @return array Facet options with counts
     */
    public function get_options(string $search_query, array $current_filters = []): array {
        $options = [];
        
        // Get terms for this taxonomy
        $terms = get_terms([
            'taxonomy' => $this->taxonomy,
            'hide_empty' => true,
            'number' => 100,
            'orderby' => 'count',
            'order' => 'DESC'
        ]);
        
        if (is_wp_error($terms) || empty($terms)) {
            return $options;
        }
        
        foreach ($terms as $term) {
            $count = $this->get_term_count($term, $search_query, $current_filters);
            
            $options[$term->slug] = [
                'value' => $term->slug,
                'label' => $term->name,
                'count' => $count
            ];
        }
        
        // Sort by count descending, then by name
        uasort($options, function($a, $b) {
            if ($a['count'] === $b['count']) {
                return strcmp($a['label'], $b['label']);
            }
            return $b['count'] <=> $a['count'];
        });
        
        return $options;
    }
    
    /**
     * Apply filter to query arguments
     * 
     * @param array $query_args Current query arguments
     * @param array $values Selected values
     * @return array Modified query arguments
     */
    public function apply_filter(array $query_args, array $values): array {
        if (!empty($values)) {
            if (!isset($query_args['tax_query'])) {
                $query_args['tax_query'] = [];
            }
            
            $query_args['tax_query'][] = [
                'taxonomy' => $this->taxonomy,
                'field' => 'slug',
                'terms' => $values,
                'operator' => 'IN'
            ];
            
            // Set relation if multiple tax queries
            if (count($query_args['tax_query']) > 1) {
                $query_args['tax_query']['relation'] = 'AND';
            }
        }
        
        return $query_args;
    }
    
    /**
     * Get count for specific term
     * 
     * @param \WP_Term $term Term to count
     * @param string $search_query Search query
     * @param array $filters Current filters
     * @return int Count
     */
    private function get_term_count(\WP_Term $term, string $search_query, array $filters): int {
        // Create a copy of filters excluding current taxonomy
        $temp_filters = $filters;
        unset($temp_filters[$this->taxonomy]);
        
        $args = [
            'post_type' => $this->get_applicable_post_types(),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'tax_query' => [
                [
                    'taxonomy' => $this->taxonomy,
                    'field' => 'term_id',
                    'terms' => $term->term_id
                ]
            ]
        ];
        
        // Add search query
        if (!empty($search_query)) {
            $args['s'] = $search_query;
        }
        
        // Apply other filters
        $this->apply_other_filters($args, $temp_filters);
        
        $query = new \WP_Query($args);
        return $query->found_posts;
    }
    
    /**
     * Get applicable post types for this taxonomy
     * 
     * @return array Post types
     */
    private function get_applicable_post_types(): array {
        $post_types = ['movie', 'tv'];
        
        if (get_option('tmu_dramas') === 'on') {
            $post_types[] = 'drama';
        }
        
        // Some taxonomies don't apply to people
        if (in_array($this->taxonomy, ['genre', 'country', 'language', 'network', 'channel'])) {
            return $post_types;
        }
        
        return ['movie', 'tv', 'drama', 'people'];
    }
    
    /**
     * Apply other filters to the query
     * 
     * @param array $args Query arguments
     * @param array $filters Current filters
     */
    private function apply_other_filters(array &$args, array $filters): void {
        // Post type filter
        if (!empty($filters['post_type'])) {
            $args['post_type'] = $filters['post_type'];
        }
        
        // Other taxonomy filters
        $taxonomies = ['genre', 'country', 'language', 'network', 'channel'];
        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy !== $this->taxonomy && !empty($filters[$taxonomy])) {
                $args['tax_query'][] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $filters[$taxonomy],
                    'operator' => 'IN'
                ];
            }
        }
        
        // Year filter
        if (!empty($filters['year'])) {
            $this->apply_year_filter($args, $filters['year']);
        }
        
        // Rating filter
        if (!empty($filters['rating'])) {
            $this->apply_rating_filter($args, $filters['rating']);
        }
        
        // Runtime filter
        if (!empty($filters['runtime'])) {
            $this->apply_runtime_filter($args, $filters['runtime']);
        }
        
        // Set tax_query relation
        if (!empty($args['tax_query']) && count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }
    }
    
    /**
     * Apply year filter
     * 
     * @param array $args Query arguments
     * @param mixed $year_filter Year filter value
     */
    private function apply_year_filter(array &$args, $year_filter): void {
        if (is_string($year_filter) && strpos($year_filter, '-') !== false) {
            [$min_year, $max_year] = explode('-', $year_filter);
            
            if (!isset($args['meta_query'])) {
                $args['meta_query'] = [];
            }
            
            $args['meta_query'][] = [
                'relation' => 'OR',
                [
                    'key' => 'tmu_movie_release_date',
                    'value' => [$min_year . '-01-01', $max_year . '-12-31'],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ],
                [
                    'key' => 'tmu_tv_first_air_date',
                    'value' => [$min_year . '-01-01', $max_year . '-12-31'],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ]
            ];
        }
    }
    
    /**
     * Apply rating filter
     * 
     * @param array $args Query arguments
     * @param mixed $rating_filter Rating filter value
     */
    private function apply_rating_filter(array &$args, $rating_filter): void {
        if (is_string($rating_filter) && strpos($rating_filter, '-') !== false) {
            [$min_rating, $max_rating] = explode('-', $rating_filter);
            
            if (!isset($args['meta_query'])) {
                $args['meta_query'] = [];
            }
            
            $args['meta_query'][] = [
                'relation' => 'OR',
                [
                    'key' => 'tmu_movie_vote_average',
                    'value' => [$min_rating, $max_rating],
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN'
                ],
                [
                    'key' => 'tmu_tv_vote_average',
                    'value' => [$min_rating, $max_rating],
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN'
                ]
            ];
        }
    }
    
    /**
     * Apply runtime filter
     * 
     * @param array $args Query arguments
     * @param mixed $runtime_filter Runtime filter value
     */
    private function apply_runtime_filter(array &$args, $runtime_filter): void {
        if (is_string($runtime_filter) && strpos($runtime_filter, '-') !== false) {
            [$min_runtime, $max_runtime] = explode('-', $runtime_filter);
            
            if (!isset($args['meta_query'])) {
                $args['meta_query'] = [];
            }
            
            $args['meta_query'][] = [
                'key' => 'tmu_movie_runtime',
                'value' => [$min_runtime, $max_runtime],
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            ];
        }
    }
}