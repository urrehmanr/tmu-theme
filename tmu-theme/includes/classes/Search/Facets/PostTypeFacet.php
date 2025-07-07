<?php
namespace TMU\Search\Facets;

/**
 * Post Type Facet
 * 
 * Handles filtering by post type (movie, tv, drama, people)
 */
class PostTypeFacet {
    
    /**
     * Get facet options with counts
     * 
     * @param string $search_query Current search query
     * @param array $current_filters Current filters applied
     * @return array Facet options with counts
     */
    public function get_options(string $search_query, array $current_filters = []): array {
        $options = [];
        $post_types = ['movie', 'tv', 'people'];
        
        if (get_option('tmu_dramas') === 'on') {
            $post_types[] = 'drama';
        }
        
        foreach ($post_types as $post_type) {
            $count = $this->get_post_type_count($post_type, $search_query, $current_filters);
            
            $options[$post_type] = [
                'value' => $post_type,
                'label' => $this->get_post_type_label($post_type),
                'count' => $count
            ];
        }
        
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
            $query_args['post_type'] = $values;
        }
        
        return $query_args;
    }
    
    /**
     * Get count for specific post type
     * 
     * @param string $post_type Post type to count
     * @param string $search_query Search query
     * @param array $filters Current filters
     * @return int Count
     */
    private function get_post_type_count(string $post_type, string $search_query, array $filters): int {
        $args = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        ];
        
        // Add search query
        if (!empty($search_query)) {
            $args['s'] = $search_query;
        }
        
        // Apply other filters (excluding post_type)
        $this->apply_other_filters($args, $filters);
        
        $query = new \WP_Query($args);
        return $query->found_posts;
    }
    
    /**
     * Apply other filters to the query
     * 
     * @param array $args Query arguments
     * @param array $filters Current filters
     */
    private function apply_other_filters(array &$args, array $filters): void {
        // Genre filter
        if (!empty($filters['genre'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'genre',
                'field' => 'slug',
                'terms' => $filters['genre'],
                'operator' => 'IN'
            ];
        }
        
        // Country filter
        if (!empty($filters['country'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'country',
                'field' => 'slug',
                'terms' => $filters['country'],
                'operator' => 'IN'
            ];
        }
        
        // Language filter
        if (!empty($filters['language'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'language',
                'field' => 'slug',
                'terms' => $filters['language'],
                'operator' => 'IN'
            ];
        }
        
        // Year filter
        if (!empty($filters['year'])) {
            $this->apply_year_filter($args, $filters['year']);
        }
        
        // Rating filter
        if (!empty($filters['rating'])) {
            $this->apply_rating_filter($args, $filters['rating']);
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
            
            $args['meta_query'][] = [
                'relation' => 'OR',
                [
                    'key' => 'tmu_movie_release_date',
                    'value' => [$min_year, $max_year],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ],
                [
                    'key' => 'tmu_tv_first_air_date',
                    'value' => [$min_year, $max_year],
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
     * Get human-readable label for post type
     * 
     * @param string $post_type Post type
     * @return string Label
     */
    private function get_post_type_label(string $post_type): string {
        $labels = [
            'movie' => __('Movies', 'tmu'),
            'tv' => __('TV Shows', 'tmu'),
            'drama' => __('Dramas', 'tmu'),
            'people' => __('People', 'tmu')
        ];
        
        return $labels[$post_type] ?? ucfirst($post_type);
    }
}