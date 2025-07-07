<?php
/**
 * Search Result Class
 *
 * @package TMU\Search
 * @version 1.0.0
 */

namespace TMU\Search;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Search Result Container
 */
class SearchResult {
    
    /**
     * Search results
     */
    private array $results = [];
    
    /**
     * Total found
     */
    private int $total_found = 0;
    
    /**
     * Facets data
     */
    private array $facets = [];
    
    /**
     * Recommendations
     */
    private array $recommendations = [];
    
    /**
     * Search query
     */
    private string $query = '';
    
    /**
     * Applied filters
     */
    private array $filters = [];
    
    /**
     * Execution time
     */
    private float $execution_time = 0.0;
    
    /**
     * Max pages
     */
    private int $max_pages = 0;
    
    /**
     * Constructor
     */
    public function __construct(array $data = []) {
        $this->results = $data['results'] ?? [];
        $this->total_found = $data['total_found'] ?? 0;
        $this->facets = $data['facets'] ?? [];
        $this->recommendations = $data['recommendations'] ?? [];
        $this->query = $data['query'] ?? '';
        $this->filters = $data['filters'] ?? [];
        $this->execution_time = $data['execution_time'] ?? 0.0;
        $this->max_pages = $data['max_pages'] ?? 0;
    }
    
    /**
     * Get results
     */
    public function get_results(): array {
        return $this->results;
    }
    
    /**
     * Get total found
     */
    public function get_total(): int {
        return $this->total_found;
    }
    
    /**
     * Get facets
     */
    public function get_facets(): array {
        return $this->facets;
    }
    
    /**
     * Get recommendations
     */
    public function get_recommendations(): array {
        return $this->recommendations;
    }
    
    /**
     * Get query
     */
    public function get_query(): string {
        return $this->query;
    }
    
    /**
     * Get filters
     */
    public function get_filters(): array {
        return $this->filters;
    }
    
    /**
     * Get execution time
     */
    public function get_execution_time(): float {
        return $this->execution_time;
    }
    
    /**
     * Get max pages
     */
    public function get_max_pages(): int {
        return $this->max_pages;
    }
    
    /**
     * Check if has results
     */
    public function has_results(): bool {
        return !empty($this->results);
    }
    
    /**
     * Check if has facets
     */
    public function has_facets(): bool {
        return !empty($this->facets);
    }
    
    /**
     * Check if has recommendations
     */
    public function has_recommendations(): bool {
        return !empty($this->recommendations);
    }
    
    /**
     * Get results count
     */
    public function get_results_count(): int {
        return count($this->results);
    }
    
    /**
     * Get specific facet data
     */
    public function get_facet($facet_name): array {
        return $this->facets[$facet_name] ?? [];
    }
    
    /**
     * Convert to array
     */
    public function to_array(): array {
        return [
            'results' => $this->results,
            'total_found' => $this->total_found,
            'facets' => $this->facets,
            'recommendations' => $this->recommendations,
            'query' => $this->query,
            'filters' => $this->filters,
            'execution_time' => $this->execution_time,
            'max_pages' => $this->max_pages,
            'has_results' => $this->has_results(),
            'results_count' => $this->get_results_count()
        ];
    }
}