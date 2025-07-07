<?php
namespace TMU\Search\Facets;

/**
 * Year Facet
 * 
 * Handles filtering by release/air year ranges
 */
class YearFacet {
    
    /**
     * Get facet options with counts
     * 
     * @param string $search_query Current search query
     * @param array $current_filters Current filters applied
     * @return array Facet options with counts
     */
    public function get_options(string $search_query, array $current_filters = []): array {
        $options = [];
        $year_ranges = $this->get_year_ranges();
        
        foreach ($year_ranges as $range_key => $range) {
            $count = $this->get_year_range_count($range, $search_query, $current_filters);
            
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
            $year_ranges = $this->get_year_ranges();
            
            if (!isset($query_args['meta_query'])) {
                $query_args['meta_query'] = [];
            }
            
            $year_conditions = [];
            
            foreach ($values as $value) {
                if (isset($year_ranges[$value])) {
                    $range = $year_ranges[$value];
                    
                    $year_conditions[] = [
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
            
            if (!empty($year_conditions)) {
                if (count($year_conditions) === 1) {
                    $query_args['meta_query'][] = $year_conditions[0];
                } else {
                    $query_args['meta_query'][] = [
                        'relation' => 'OR',
                        ...$year_conditions
                    ];
                }
            }
        }
        
        return $query_args;
    }
    
    /**
     * Get predefined year ranges
     * 
     * @return array Year ranges
     */
    private function get_year_ranges(): array {
        $current_year = (int) date('Y');
        
        return [
            '2020s' => [
                'label' => '2020s',
                'start' => 2020,
                'end' => $current_year
            ],
            '2010s' => [
                'label' => '2010s',
                'start' => 2010,
                'end' => 2019
            ],
            '2000s' => [
                'label' => '2000s',
                'start' => 2000,
                'end' => 2009
            ],
            '1990s' => [
                'label' => '1990s',
                'start' => 1990,
                'end' => 1999
            ],
            '1980s' => [
                'label' => '1980s',
                'start' => 1980,
                'end' => 1989
            ],
            '1970s' => [
                'label' => '1970s',
                'start' => 1970,
                'end' => 1979
            ],
            '1960s' => [
                'label' => '1960s',
                'start' => 1960,
                'end' => 1969
            ],
            'older' => [
                'label' => 'Before 1960',
                'start' => 1900,
                'end' => 1959
            ]
        ];
    }
    
    /**
     * Get count for specific year range
     * 
     * @param array $range Year range
     * @param string $search_query Search query
     * @param array $filters Current filters
     * @return int Count
     */
    private function get_year_range_count(array $range, string $search_query, array $filters): int {
        // Create a copy of filters excluding year
        $temp_filters = $filters;
        unset($temp_filters['year']);
        
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
     * Apply rating filter
     * 
     * @param array $args Query arguments
     * @param mixed $rating_filter Rating filter value
     */
    private function apply_rating_filter(array &$args, $rating_filter): void {
        if (is_array($rating_filter)) {
            foreach ($rating_filter as $rating_range) {
                if (strpos($rating_range, '-') !== false) {
                    [$min_rating, $max_rating] = explode('-', $rating_range);
                    
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
            foreach ($runtime_filter as $runtime_range) {
                if (strpos($runtime_range, '-') !== false) {
                    [$min_runtime, $max_runtime] = explode('-', $runtime_range);
                    
                    $args['meta_query'][] = [
                        'key' => 'tmu_movie_runtime',
                        'value' => [$min_runtime, $max_runtime],
                        'type' => 'NUMERIC',
                        'compare' => 'BETWEEN'
                    ];
                }
            }
        }
    }
}