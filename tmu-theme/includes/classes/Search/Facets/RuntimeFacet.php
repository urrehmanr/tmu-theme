<?php
namespace TMU\Search\Facets;

/**
 * Runtime Facet
 * 
 * Handles filtering by movie runtime duration
 */
class RuntimeFacet {
    
    /**
     * Get facet options with counts
     * 
     * @param string $search_query Current search query
     * @param array $current_filters Current filters applied
     * @return array Facet options with counts
     */
    public function get_options(string $search_query, array $current_filters = []): array {
        $options = [];
        $runtime_ranges = $this->get_runtime_ranges();
        
        foreach ($runtime_ranges as $range_key => $range) {
            $count = $this->get_runtime_range_count($range, $search_query, $current_filters);
            
            $options[$range_key] = [
                'value' => $range_key,
                'label' => $range['label'],
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
            $runtime_ranges = $this->get_runtime_ranges();
            
            // Runtime only applies to movies
            $query_args['post_type'] = ['movie'];
            
            if (!isset($query_args['meta_query'])) {
                $query_args['meta_query'] = [];
            }
            
            $runtime_conditions = [];
            
            foreach ($values as $value) {
                if (isset($runtime_ranges[$value])) {
                    $range = $runtime_ranges[$value];
                    
                    $runtime_conditions[] = [
                        'key' => 'tmu_movie_runtime',
                        'value' => [$range['min'], $range['max']],
                        'type' => 'NUMERIC',
                        'compare' => 'BETWEEN'
                    ];
                }
            }
            
            if (!empty($runtime_conditions)) {
                if (count($runtime_conditions) === 1) {
                    $query_args['meta_query'][] = $runtime_conditions[0];
                } else {
                    $query_args['meta_query'][] = [
                        'relation' => 'OR',
                        ...$runtime_conditions
                    ];
                }
            }
        }
        
        return $query_args;
    }
    
    /**
     * Get predefined runtime ranges
     * 
     * @return array Runtime ranges in minutes
     */
    private function get_runtime_ranges(): array {
        return [
            'short' => [
                'label' => 'Short (< 90 min)',
                'min' => 0,
                'max' => 89
            ],
            'standard' => [
                'label' => 'Standard (90-120 min)',
                'min' => 90,
                'max' => 120
            ],
            'long' => [
                'label' => 'Long (121-150 min)',
                'min' => 121,
                'max' => 150
            ],
            'very-long' => [
                'label' => 'Very Long (151-180 min)',
                'min' => 151,
                'max' => 180
            ],
            'epic' => [
                'label' => 'Epic (> 180 min)',
                'min' => 181,
                'max' => 999
            ]
        ];
    }
    
    /**
     * Get count for specific runtime range
     * 
     * @param array $range Runtime range
     * @param string $search_query Search query
     * @param array $filters Current filters
     * @return int Count
     */
    private function get_runtime_range_count(array $range, string $search_query, array $filters): int {
        // Create a copy of filters excluding runtime
        $temp_filters = $filters;
        unset($temp_filters['runtime']);
        
        $args = [
            'post_type' => 'movie', // Runtime only applies to movies
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query' => [
                [
                    'key' => 'tmu_movie_runtime',
                    'value' => [$range['min'], $range['max']],
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN'
                ],
                [
                    'key' => 'tmu_movie_runtime',
                    'compare' => 'EXISTS'
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
     * Apply other filters to the query
     * 
     * @param array $args Query arguments
     * @param array $filters Current filters
     */
    private function apply_other_filters(array &$args, array $filters): void {
        // Post type filter - already set to 'movie' for runtime
        
        // Taxonomy filters
        $taxonomies = ['genre', 'country', 'language'];
        foreach ($taxonomies as $taxonomy) {
            if (!empty($filters[$taxonomy])) {
                if (!isset($args['tax_query'])) {
                    $args['tax_query'] = [];
                }
                
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
        if (is_array($year_filter)) {
            $year_ranges = [
                '2020s' => ['start' => 2020, 'end' => (int) date('Y')],
                '2010s' => ['start' => 2010, 'end' => 2019],
                '2000s' => ['start' => 2000, 'end' => 2009],
                '1990s' => ['start' => 1990, 'end' => 1999],
                '1980s' => ['start' => 1980, 'end' => 1989],
                '1970s' => ['start' => 1970, 'end' => 1979],
                '1960s' => ['start' => 1960, 'end' => 1969],
                'older' => ['start' => 1900, 'end' => 1959]
            ];
            
            foreach ($year_filter as $year_range_key) {
                if (isset($year_ranges[$year_range_key])) {
                    $range = $year_ranges[$year_range_key];
                    
                    $args['meta_query'][] = [
                        'key' => 'tmu_movie_release_date',
                        'value' => [$range['start'] . '-01-01', $range['end'] . '-12-31'],
                        'compare' => 'BETWEEN',
                        'type' => 'DATE'
                    ];
                }
            }
        }
    }
    
    /**
     * Apply rating filter
     * 
     * @param array $args Query arguments
     * @param mixed $rating_filter Rating filter value
     */
    private function apply_rating_filter(array &$args, $rating_filter): void {
        if (is_array($rating_filter)) {
            $rating_ranges = [
                '9-10' => ['min' => 9.0, 'max' => 10.0],
                '8-9' => ['min' => 8.0, 'max' => 8.9],
                '7-8' => ['min' => 7.0, 'max' => 7.9],
                '6-7' => ['min' => 6.0, 'max' => 6.9],
                '5-6' => ['min' => 5.0, 'max' => 5.9],
                '0-5' => ['min' => 0.0, 'max' => 4.9]
            ];
            
            foreach ($rating_filter as $rating_range_key) {
                if (isset($rating_ranges[$rating_range_key])) {
                    $range = $rating_ranges[$rating_range_key];
                    
                    $args['meta_query'][] = [
                        'key' => 'tmu_movie_vote_average',
                        'value' => [$range['min'], $range['max']],
                        'type' => 'NUMERIC',
                        'compare' => 'BETWEEN'
                    ];
                }
            }
        }
    }
}