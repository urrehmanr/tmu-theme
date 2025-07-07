<?php
namespace TMU\Search;

class FilterManager {
    private $facets = [];
    
    public function __construct() {
        $this->register_facets();
    }
    
    private function register_facets(): void {
        $this->facets = [
            'post_type' => new Facets\PostTypeFacet(),
            'genre' => new Facets\TaxonomyFacet('genre'),
            'country' => new Facets\TaxonomyFacet('country'),
            'language' => new Facets\TaxonomyFacet('language'),
            'year' => new Facets\YearFacet(),
            'rating' => new Facets\RatingFacet(),
            'runtime' => new Facets\RuntimeFacet()
        ];
        
        if (get_option('tmu_tv_series') === 'on') {
            $this->facets['network'] = new Facets\TaxonomyFacet('network');
        }
        
        if (get_option('tmu_dramas') === 'on') {
            $this->facets['channel'] = new Facets\TaxonomyFacet('channel');
        }
    }
    
    public function get_facet_data($search_query, $current_filters = []): array {
        $facet_data = [];
        
        foreach ($this->facets as $facet_name => $facet) {
            // Skip current facet when calculating counts
            $temp_filters = $current_filters;
            unset($temp_filters[$facet_name]);
            
            $facet_data[$facet_name] = $facet->get_options($search_query, $temp_filters);
        }
        
        return $facet_data;
    }
    
    public function apply_filters($query_args, $filters): array {
        foreach ($filters as $facet_name => $values) {
            if (isset($this->facets[$facet_name]) && !empty($values)) {
                $query_args = $this->facets[$facet_name]->apply_filter($query_args, $values);
            }
        }
        
        return $query_args;
    }
    
    public function get_active_filters($filters): array {
        $active_filters = [];
        
        foreach ($filters as $facet_name => $values) {
            if (!empty($values)) {
                $active_filters[$facet_name] = [
                    'label' => $this->get_facet_label($facet_name),
                    'values' => $this->format_filter_values($facet_name, $values)
                ];
            }
        }
        
        return $active_filters;
    }
    
    private function get_facet_label($facet_name): string {
        $labels = [
            'post_type' => __('Content Type', 'tmu'),
            'genre' => __('Genre', 'tmu'),
            'country' => __('Country', 'tmu'),
            'language' => __('Language', 'tmu'),
            'year' => __('Year', 'tmu'),
            'rating' => __('Rating', 'tmu'),
            'runtime' => __('Runtime', 'tmu'),
            'network' => __('Network', 'tmu'),
            'channel' => __('Channel', 'tmu')
        ];
        
        return $labels[$facet_name] ?? $facet_name;
    }
    
    private function format_filter_values($facet_name, $values): array {
        $formatted = [];
        
        foreach ($values as $value) {
            switch ($facet_name) {
                case 'post_type':
                    $formatted[] = $this->format_post_type_value($value);
                    break;
                    
                case 'genre':
                case 'country':
                case 'language':
                case 'network':
                case 'channel':
                    $term = get_term_by('slug', $value, $facet_name);
                    $formatted[] = $term ? $term->name : $value;
                    break;
                    
                case 'year':
                    $formatted[] = $value;
                    break;
                    
                case 'rating':
                    $formatted[] = $this->format_rating_value($value);
                    break;
                    
                case 'runtime':
                    $formatted[] = $this->format_runtime_value($value);
                    break;
                    
                default:
                    $formatted[] = $value;
                    break;
            }
        }
        
        return $formatted;
    }
    
    private function format_post_type_value($value): string {
        $labels = [
            'movie' => __('Movies', 'tmu'),
            'tv' => __('TV Shows', 'tmu'),
            'drama' => __('Dramas', 'tmu'),
            'people' => __('People', 'tmu')
        ];
        
        return $labels[$value] ?? $value;
    }
    
    private function format_rating_value($value): string {
        return $value . '+ ⭐';
    }
    
    private function format_runtime_value($value): string {
        $ranges = [
            'short' => __('Under 90 minutes', 'tmu'),
            'medium' => __('90-150 minutes', 'tmu'),
            'long' => __('Over 150 minutes', 'tmu')
        ];
        
        return $ranges[$value] ?? $value;
    }
    
    public function get_filter_options($facet_name, $search_query = '', $current_filters = []): array {
        if (!isset($this->facets[$facet_name])) {
            return [];
        }
        
        return $this->facets[$facet_name]->get_options($search_query, $current_filters);
    }
    
    public function clear_filter($filters, $facet_name, $value = null): array {
        if ($value === null) {
            unset($filters[$facet_name]);
        } else {
            if (isset($filters[$facet_name])) {
                $filters[$facet_name] = array_filter($filters[$facet_name], function($v) use ($value) {
                    return $v !== $value;
                });
                
                if (empty($filters[$facet_name])) {
                    unset($filters[$facet_name]);
                }
            }
        }
        
        return $filters;
    }
    
    public function get_filter_counts($search_query, $filters): array {
        $counts = [];
        
        foreach ($this->facets as $facet_name => $facet) {
            $counts[$facet_name] = $facet->get_total_count($search_query, $filters);
        }
        
        return $counts;
    }
}