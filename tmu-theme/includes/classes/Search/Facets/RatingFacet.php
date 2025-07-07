<?php
namespace TMU\Search\Facets;

/**
 * Rating Facet
 * 
 * Handles filtering by rating ranges
 */
class RatingFacet {
    
    /**
     * Get facet options with counts
     * 
     * @param string $search_query Current search query
     * @param array $current_filters Current filters applied
     * @return array Facet options with counts
     */
    public function get_options(string $search_query, array $current_filters = []): array {
        $options = [];
        $rating_ranges = $this->get_rating_ranges();
        
        foreach ($rating_ranges as $range_key => $range) {
            $count = $this->get_rating_range_count($range, $search_query, $current_filters);
            
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
            $rating_ranges = $this->get_rating_ranges();
            
            if (!isset($query_args['meta_query'])) {
                $query_args['meta_query'] = [];
            }
            
            $rating_conditions = [];
            
            foreach ($values as $value) {
                if (isset($rating_ranges[$value])) {
                    $range = $rating_ranges[$value];
                    
                    $rating_conditions[] = [
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
                    ];
                }
            }
            
            if (!empty($rating_conditions)) {
                if (count($rating_conditions) === 1) {
                    $query_args['meta_query'][] = $rating_conditions[0];
                } else {
                    $query_args['meta_query'][] = [
                        'relation' => 'OR',
                        ...$rating_conditions
                    ];
                }
            }
        }
        
        return $query_args;
    }
    
    /**
     * Get predefined rating ranges
     * 
     * @return array Rating ranges
     */
    private function get_rating_ranges(): array {
        return [
            '9-10' => [
                'label' => '9.0 - 10.0 ⭐⭐⭐⭐⭐',
                'min' => 9.0,
                'max' => 10.0
            ],
            '8-9' => [
                'label' => '8.0 - 8.9 ⭐⭐⭐⭐',
                'min' => 8.0,
                'max' => 8.9
            ],
            '7-8' => [
                'label' => '7.0 - 7.9 ⭐⭐⭐',
                'min' => 7.0,
                'max' => 7.9
            ],
            '6-7' => [
                'label' => '6.0 - 6.9 ⭐⭐',
                'min' => 6.0,
                'max' => 6.9
            ],
            '5-6' => [
                'label' => '5.0 - 5.9 ⭐',
                'min' => 5.0,
                'max' => 5.9
            ],
            '0-5' => [
                'label' => 'Below 5.0',
                'min' => 0.0,
                'max' => 4.9
            ]
        ];
    }
    
    /**
     * Get count for specific rating range
     * 
     * @param array $range Rating range
     * @param string $search_query Search query
     * @param array $filters Current filters
     * @return int Count
     */
    private function get_rating_range_count(array $range, string $search_query, array $filters): int {
        // Create a copy of filters excluding rating
        $temp_filters = $filters;
        unset($temp_filters['rating']);
        
        $args = [
            'post_type' => ['movie', 'tv', 'drama'],
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
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
        // Post type filter
        if (!empty($filters['post_type'])) {
            $args['post_type'] = $filters['post_type'];
        }
        
        // Taxonomy filters
        $taxonomies = ['genre', 'country', 'language', 'network', 'channel'];
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
                        'relation' => 'OR',
                        [
                            'key' => 'tmu_movie_release_date',
                            'value' => [$range['start'] . '-01-01', $range['end'] . '-12-31'],
                            'compare' => 'BETWEEN',
                            'type' => 'DATE'
                        ],
                        [
                            'key' => 'tmu_tv_first_air_date',
                            'value' => [$range['start'] . '-01-01', $range['end'] . '-12-31'],
                            'compare' => 'BETWEEN',
                            'type' => 'DATE'
                        ]
                    ];
                }
            }
        }
    }
    
    /**
     * Apply runtime filter
     * 
     * @param array $args Query arguments
     * @param mixed $runtime_filter Runtime filter value
     */
    private function apply_runtime_filter(array &$args, $runtime_filter): void {
        if (is_array($runtime_filter)) {
            $runtime_ranges = [
                'short' => ['min' => 0, 'max' => 90],
                'medium' => ['min' => 91, 'max' => 120],
                'long' => ['min' => 121, 'max' => 180],
                'very-long' => ['min' => 181, 'max' => 999]
            ];
            
            foreach ($runtime_filter as $runtime_range_key) {
                if (isset($runtime_ranges[$runtime_range_key])) {
                    $range = $runtime_ranges[$runtime_range_key];
                    
                    $args['meta_query'][] = [
                        'key' => 'tmu_movie_runtime',
                        'value' => [$range['min'], $range['max']],
                        'type' => 'NUMERIC',
                        'compare' => 'BETWEEN'
                    ];
                }
            }
        }
    }
}