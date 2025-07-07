<?php
namespace TMU\Search;

/**
 * Search Query Builder
 * 
 * Builds WordPress queries for search functionality
 */
class QueryBuilder {
    
    /**
     * Build search query arguments
     * 
     * @param string $search_query Search query string
     * @param array $filters Applied filters
     * @param array $options Query options
     * @return array WP_Query arguments
     */
    public function build(string $search_query, array $filters = [], array $options = []): array {
        $args = [
            'post_type' => $this->get_searchable_post_types($filters),
            'post_status' => 'publish',
            'posts_per_page' => $options['per_page'] ?? 20,
            'paged' => $options['page'] ?? 1,
            'orderby' => $options['orderby'] ?? 'relevance',
            'order' => $options['order'] ?? 'DESC',
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => true
        ];
        
        // Add search query
        if (!empty($search_query)) {
            $args['s'] = $search_query;
            
            // Add custom meta search
            $args['meta_query'] = $this->build_meta_search($search_query);
        }
        
        // Apply taxonomy filters
        if (!empty($filters)) {
            $args = $this->apply_taxonomy_filters($args, $filters);
            $args = $this->apply_meta_filters($args, $filters);
        }
        
        // Apply custom ordering
        $args = $this->apply_ordering($args, $options);
        
        return $args;
    }
    
    /**
     * Build meta search query
     * 
     * @param string $search_query Search query
     * @return array Meta query
     */
    private function build_meta_search(string $search_query): array {
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
                'key' => 'tmu_tv_name',
                'value' => $search_query,
                'compare' => 'LIKE'
            ],
            [
                'key' => 'tmu_person_biography',
                'value' => $search_query,
                'compare' => 'LIKE'
            ],
            [
                'key' => 'tmu_person_also_known_as',
                'value' => $search_query,
                'compare' => 'LIKE'
            ]
        ];
    }
    
    /**
     * Apply taxonomy filters
     * 
     * @param array $args Query arguments
     * @param array $filters Filters
     * @return array Modified arguments
     */
    private function apply_taxonomy_filters(array $args, array $filters): array {
        $tax_query = [];
        
        // Genre filter
        if (!empty($filters['genre'])) {
            $tax_query[] = [
                'taxonomy' => 'genre',
                'field' => 'slug',
                'terms' => (array) $filters['genre'],
                'operator' => 'IN'
            ];
        }
        
        // Country filter
        if (!empty($filters['country'])) {
            $tax_query[] = [
                'taxonomy' => 'country',
                'field' => 'slug',
                'terms' => (array) $filters['country'],
                'operator' => 'IN'
            ];
        }
        
        // Language filter
        if (!empty($filters['language'])) {
            $tax_query[] = [
                'taxonomy' => 'language',
                'field' => 'slug',
                'terms' => (array) $filters['language'],
                'operator' => 'IN'
            ];
        }
        
        // Network filter (TV shows)
        if (!empty($filters['network'])) {
            $tax_query[] = [
                'taxonomy' => 'network',
                'field' => 'slug',
                'terms' => (array) $filters['network'],
                'operator' => 'IN'
            ];
        }
        
        // Channel filter (Dramas)
        if (!empty($filters['channel'])) {
            $tax_query[] = [
                'taxonomy' => 'channel',
                'field' => 'slug',
                'terms' => (array) $filters['channel'],
                'operator' => 'IN'
            ];
        }
        
        if (!empty($tax_query)) {
            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }
            $args['tax_query'] = $tax_query;
        }
        
        return $args;
    }
    
    /**
     * Apply meta filters
     * 
     * @param array $args Query arguments
     * @param array $filters Filters
     * @return array Modified arguments
     */
    private function apply_meta_filters(array $args, array $filters): array {
        if (!isset($args['meta_query'])) {
            $args['meta_query'] = [];
        }
        
        // Year filter
        if (!empty($filters['year'])) {
            $args['meta_query'][] = $this->build_year_filter($filters['year']);
        }
        
        // Rating filter
        if (!empty($filters['rating'])) {
            $args['meta_query'][] = $this->build_rating_filter($filters['rating']);
        }
        
        // Runtime filter (movies only)
        if (!empty($filters['runtime'])) {
            $args['meta_query'][] = $this->build_runtime_filter($filters['runtime']);
        }
        
        // Set meta query relation
        if (count($args['meta_query']) > 1) {
            $args['meta_query']['relation'] = 'AND';
        }
        
        return $args;
    }
    
    /**
     * Build year filter
     * 
     * @param mixed $year_filter Year filter value
     * @return array Meta query condition
     */
    private function build_year_filter($year_filter): array {
        if (is_string($year_filter) && strpos($year_filter, '-') !== false) {
            [$start_year, $end_year] = explode('-', $year_filter);
            
            return [
                'relation' => 'OR',
                [
                    'key' => 'tmu_movie_release_date',
                    'value' => [$start_year . '-01-01', $end_year . '-12-31'],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ],
                [
                    'key' => 'tmu_tv_first_air_date',
                    'value' => [$start_year . '-01-01', $end_year . '-12-31'],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ]
            ];
        } elseif (is_numeric($year_filter)) {
            return [
                'relation' => 'OR',
                [
                    'key' => 'tmu_movie_release_date',
                    'value' => $year_filter,
                    'compare' => 'LIKE'
                ],
                [
                    'key' => 'tmu_tv_first_air_date',
                    'value' => $year_filter,
                    'compare' => 'LIKE'
                ]
            ];
        }
        
        return [];
    }
    
    /**
     * Build rating filter
     * 
     * @param mixed $rating_filter Rating filter value
     * @return array Meta query condition
     */
    private function build_rating_filter($rating_filter): array {
        if (is_string($rating_filter) && strpos($rating_filter, '-') !== false) {
            [$min_rating, $max_rating] = explode('-', $rating_filter);
            
            return [
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
        } elseif (is_numeric($rating_filter)) {
            return [
                'relation' => 'OR',
                [
                    'key' => 'tmu_movie_vote_average',
                    'value' => $rating_filter,
                    'type' => 'NUMERIC',
                    'compare' => '>='
                ],
                [
                    'key' => 'tmu_tv_vote_average',
                    'value' => $rating_filter,
                    'type' => 'NUMERIC',
                    'compare' => '>='
                ]
            ];
        }
        
        return [];
    }
    
    /**
     * Build runtime filter
     * 
     * @param mixed $runtime_filter Runtime filter value
     * @return array Meta query condition
     */
    private function build_runtime_filter($runtime_filter): array {
        if (is_string($runtime_filter) && strpos($runtime_filter, '-') !== false) {
            [$min_runtime, $max_runtime] = explode('-', $runtime_filter);
            
            return [
                'key' => 'tmu_movie_runtime',
                'value' => [$min_runtime, $max_runtime],
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            ];
        } elseif (is_numeric($runtime_filter)) {
            return [
                'key' => 'tmu_movie_runtime',
                'value' => $runtime_filter,
                'type' => 'NUMERIC',
                'compare' => '>='
            ];
        }
        
        return [];
    }
    
    /**
     * Apply custom ordering
     * 
     * @param array $args Query arguments
     * @param array $options Query options
     * @return array Modified arguments
     */
    private function apply_ordering(array $args, array $options): array {
        $orderby = $options['orderby'] ?? 'relevance';
        
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
                
            case 'modified':
                $args['orderby'] = 'modified';
                $args['order'] = 'DESC';
                break;
                
            case 'relevance':
            default:
                $args['orderby'] = 'relevance';
                $args['order'] = 'DESC';
                break;
        }
        
        return $args;
    }
    
    /**
     * Get searchable post types
     * 
     * @param array $filters Applied filters
     * @return array Post types
     */
    private function get_searchable_post_types(array $filters = []): array {
        // If post type filter is applied, use it
        if (!empty($filters['post_type'])) {
            return (array) $filters['post_type'];
        }
        
        // Default searchable post types
        $post_types = ['movie', 'tv', 'people'];
        
        if (get_option('tmu_dramas') === 'on') {
            $post_types[] = 'drama';
        }
        
        return $post_types;
    }
    
    /**
     * Build faceted search query
     * 
     * @param string $facet_type Facet type
     * @param string $search_query Search query
     * @param array $filters Current filters
     * @return array Query arguments for facet counting
     */
    public function build_facet_query(string $facet_type, string $search_query, array $filters = []): array {
        // Remove the current facet from filters to get accurate counts
        $temp_filters = $filters;
        unset($temp_filters[$facet_type]);
        
        $args = [
            'post_type' => $this->get_searchable_post_types($temp_filters),
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
            $args['meta_query'] = $this->build_meta_search($search_query);
        }
        
        // Apply remaining filters
        if (!empty($temp_filters)) {
            $args = $this->apply_taxonomy_filters($args, $temp_filters);
            $args = $this->apply_meta_filters($args, $temp_filters);
        }
        
        return $args;
    }
}