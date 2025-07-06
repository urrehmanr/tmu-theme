# Step 12: Search and Filtering

## Purpose
Implement advanced search functionality with filtering, sorting, and AJAX-powered live search for movies, TV shows, dramas, and people.

## Dependencies from Previous Steps
- **[REQUIRED]** Post types and taxonomies [FROM STEPS 5-6] - Content to search and filter
- **[REQUIRED]** TMDB data [FROM STEP 9] - Rich search criteria and metadata
- **[REQUIRED]** Template system [FROM STEP 10] - Search result display
- **[REQUIRED]** Asset compilation [FROM STEP 1] - JavaScript for live search

## Files Created in This Step
- **[CREATE NEW]** `includes/classes/Search/SearchManager.php` - Main search coordinator
- **[CREATE NEW]** `includes/classes/Search/ElasticSearch.php` - Advanced search engine
- **[CREATE NEW]** `includes/classes/Search/FilterManager.php` - Content filtering system
- **[CREATE NEW]** `includes/classes/Search/AjaxSearch.php` - Live search functionality
- **[CREATE NEW]** `includes/classes/API/REST/SearchEndpoints.php` - Search API endpoints
- **[CREATE NEW]** `assets/src/js/search.js` - Search JavaScript functionality

## Tailwind CSS Status
**USES EXTENSIVELY** - Search interfaces and filter components use Tailwind for responsive design

**Step 12 Status**: ✅ READY FOR AI IMPLEMENTATION
**Dependencies**: Steps 1, 5, 6, 9, 10 must be completed
**Next Step**: Step 13 - Performance Optimization - Advanced Implementation

## Overview
This step implements an advanced search and filtering system with faceted search, AJAX-powered filtering, intelligent recommendations, and enhanced user experience. The system provides powerful content discovery capabilities while maintaining optimal performance.

## 1. Advanced Search Architecture

### 1.1 Search Engine Core
```php
// src/Search/SearchEngine.php
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
        // Sanitize and prepare query
        $search_query = $this->prepare_search_query($query);
        
        // Build database query with filters
        $db_query = $this->query_builder->build($search_query, $filters, $options);
        
        // Execute search
        $raw_results = $this->execute_search($db_query);
        
        // Process and rank results
        $processed_results = $this->result_processor->process($raw_results, $search_query);
        
        // Get aggregations for faceted search
        $aggregations = $this->get_aggregations($filters, $search_query);
        
        return new SearchResult([
            'results' => $processed_results,
            'total_found' => count($raw_results),
            'aggregations' => $aggregations,
            'query' => $search_query,
            'filters' => $filters,
            'execution_time' => microtime(true) - $start_time
        ]);
    }
    
    private function prepare_search_query($query): string {
        // Remove special characters, normalize spaces
        $query = preg_replace('/[^\w\s]/', ' ', $query);
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
        global $wpdb;
        
        // Use WordPress meta query for complex searches
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
}
```

### 1.2 Faceted Search System
```php
// src/Search/FacetedSearch.php
<?php
namespace TMU\Search;

class FacetedSearch {
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
}
```

### 1.3 Smart Query Builder
```php
// src/Search/QueryBuilder.php
<?php
namespace TMU\Search;

class QueryBuilder {
    public function build($search_query, $filters = [], $options = []): array {
        $args = [
            'post_type' => $this->get_searchable_post_types(),
            'post_status' => 'publish',
            'posts_per_page' => $options['per_page'] ?? 20,
            'paged' => $options['page'] ?? 1,
            'orderby' => $options['orderby'] ?? 'relevance',
            'order' => $options['order'] ?? 'DESC'
        ];
        
        // Add search query
        if (!empty($search_query)) {
            $args['s'] = $search_query;
            
            // Custom search in custom fields
            $args['meta_query'] = $this->build_meta_search($search_query);
        }
        
        // Apply filters
        if (!empty($filters)) {
            $faceted_search = new FacetedSearch();
            $args = $faceted_search->apply_filters($args, $filters);
        }
        
        // Custom ordering
        if ($options['orderby'] === 'relevance') {
            $args['orderby'] = 'relevance';
        } elseif ($options['orderby'] === 'rating') {
            $args['meta_key'] = 'vote_average';
            $args['orderby'] = 'meta_value_num';
        } elseif ($options['orderby'] === 'popularity') {
            $args['meta_key'] = 'popularity';
            $args['orderby'] = 'meta_value_num';
        } elseif ($options['orderby'] === 'date') {
            $args['orderby'] = 'date';
        }
        
        return $args;
    }
    
    private function build_meta_search($search_query): array {
        return [
            'relation' => 'OR',
            [
                'key' => 'original_title',
                'value' => $search_query,
                'compare' => 'LIKE'
            ],
            [
                'key' => 'overview',
                'value' => $search_query,
                'compare' => 'LIKE'
            ],
            [
                'key' => 'tagline',
                'value' => $search_query,
                'compare' => 'LIKE'
            ]
        ];
    }
    
    private function get_searchable_post_types(): array {
        $post_types = ['movie', 'tv', 'people'];
        
        if (get_option('tmu_dramas') === 'on') {
            $post_types[] = 'drama';
        }
        
        return $post_types;
    }
}
```

## 2. AJAX Search Interface

### 2.1 Real-time Search Component
```php
// src/Search/AjaxHandler.php
<?php
namespace TMU\Search;

class AjaxHandler {
    public function init(): void {
        add_action('wp_ajax_tmu_search', [$this, 'handle_search']);
        add_action('wp_ajax_nopriv_tmu_search', [$this, 'handle_search']);
        add_action('wp_ajax_tmu_get_suggestions', [$this, 'get_suggestions']);
        add_action('wp_ajax_nopriv_tmu_get_suggestions', [$this, 'get_suggestions']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    public function handle_search(): void {
        if (!wp_verify_nonce($_POST['nonce'], 'tmu_search_nonce')) {
            wp_die('Security check failed');
        }
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        $filters = $this->sanitize_filters($_POST['filters'] ?? []);
        $options = [
            'page' => intval($_POST['page'] ?? 1),
            'per_page' => intval($_POST['per_page'] ?? 20),
            'orderby' => sanitize_text_field($_POST['orderby'] ?? 'relevance')
        ];
        
        $search_engine = new SearchEngine();
        $results = $search_engine->search($query, $filters, $options);
        
        $response = [
            'html' => $this->render_results($results->get_results()),
            'facets' => $this->render_facets($results->get_aggregations()),
            'pagination' => $this->render_pagination($results),
            'total' => $results->get_total(),
            'execution_time' => $results->get_execution_time()
        ];
        
        wp_send_json_success($response);
    }
    
    public function get_suggestions(): void {
        $query = sanitize_text_field($_GET['q'] ?? '');
        
        if (strlen($query) < 2) {
            wp_send_json_success([]);
        }
        
        $suggestions = [
            'posts' => $this->get_post_suggestions($query),
            'terms' => $this->get_term_suggestions($query),
            'people' => $this->get_people_suggestions($query)
        ];
        
        wp_send_json_success($suggestions);
    }
    
    private function get_post_suggestions($query): array {
        $posts = get_posts([
            'post_type' => ['movie', 'tv', 'drama'],
            's' => $query,
            'posts_per_page' => 5,
            'orderby' => 'relevance'
        ]);
        
        return array_map(function($post) {
            $data = tmu_get_post_data($post->ID);
            return [
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => get_post_type($post->ID),
                'year' => $data['year'] ?? '',
                'poster' => get_the_post_thumbnail_url($post->ID, 'thumbnail'),
                'url' => get_permalink($post->ID)
            ];
        }, $posts);
    }
    
    private function render_results($results): string {
        ob_start();
        
        if (empty($results)) {
            echo '<div class="tmu-no-results">';
            echo '<h3>No results found</h3>';
            echo '<p>Try adjusting your search terms or filters.</p>';
            echo '</div>';
        } else {
            echo '<div class="tmu-search-results-grid">';
            foreach ($results as $post) {
                $post_type = get_post_type($post->ID);
                if ($post_type === 'people') {
                    get_template_part('templates/components/person-card', null, [
                        'person_data' => tmu_get_person_data($post->ID),
                        'post_id' => $post->ID
                    ]);
                } else {
                    get_template_part('templates/components/movie-card', null, [
                        'movie_data' => tmu_get_movie_data($post->ID),
                        'post_id' => $post->ID
                    ]);
                }
            }
            echo '</div>';
        }
        
        return ob_get_clean();
    }
    
    public function enqueue_scripts(): void {
        wp_enqueue_script(
            'tmu-search',
            get_template_directory_uri() . '/assets/js/search.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('tmu-search', 'tmuSearch', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_search_nonce'),
            'strings' => [
                'searching' => __('Searching...', 'tmu'),
                'no_results' => __('No results found', 'tmu'),
                'load_more' => __('Load More', 'tmu')
            ]
        ]);
    }
}
```

### 2.2 Frontend Search JavaScript
```javascript
// assets/js/search.js
(function($) {
    'use strict';
    
    class TMUSearch {
        constructor() {
            this.searchForm = $('.tmu-search-form');
            this.searchInput = $('.tmu-search-input');
            this.filtersContainer = $('.tmu-search-filters');
            this.resultsContainer = $('.tmu-search-results');
            this.facetsContainer = $('.tmu-search-facets');
            this.paginationContainer = $('.tmu-search-pagination');
            
            this.currentQuery = '';
            this.currentFilters = {};
            this.currentPage = 1;
            this.isLoading = false;
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.initAutocomplete();
            this.initUrlParams();
        }
        
        bindEvents() {
            // Search form submission
            this.searchForm.on('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });
            
            // Real-time search (debounced)
            let searchTimeout;
            this.searchInput.on('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.searchInput.val().length >= 2) {
                        this.performSearch();
                    }
                }, 300);
            });
            
            // Filter changes
            $(document).on('change', '.tmu-filter-checkbox', () => {
                this.updateFilters();
                this.performSearch();
            });
            
            // Sort order changes
            $(document).on('change', '.tmu-sort-select', () => {
                this.performSearch();
            });
            
            // Load more results
            $(document).on('click', '.tmu-load-more', () => {
                this.loadMoreResults();
            });
            
            // Clear filters
            $(document).on('click', '.tmu-clear-filters', () => {
                this.clearFilters();
            });
        }
        
        initAutocomplete() {
            this.searchInput.autocomplete({
                source: (request, response) => {
                    $.ajax({
                        url: tmuSearch.ajax_url,
                        data: {
                            action: 'tmu_get_suggestions',
                            q: request.term
                        },
                        success: (data) => {
                            if (data.success) {
                                const suggestions = this.formatSuggestions(data.data);
                                response(suggestions);
                            }
                        }
                    });
                },
                minLength: 2,
                delay: 200,
                select: (event, ui) => {
                    if (ui.item.url) {
                        window.location.href = ui.item.url;
                        return false;
                    }
                }
            });
        }
        
        formatSuggestions(data) {
            const suggestions = [];
            
            // Add posts
            data.posts.forEach(post => {
                suggestions.push({
                    label: `${post.title} (${post.year}) - ${post.type}`,
                    value: post.title,
                    url: post.url,
                    type: 'post'
                });
            });
            
            // Add people
            data.people.forEach(person => {
                suggestions.push({
                    label: `${person.name} - Person`,
                    value: person.name,
                    url: person.url,
                    type: 'person'
                });
            });
            
            return suggestions.slice(0, 10);
        }
        
        performSearch(resetPage = true) {
            if (this.isLoading) return;
            
            this.isLoading = true;
            
            if (resetPage) {
                this.currentPage = 1;
            }
            
            this.currentQuery = this.searchInput.val();
            this.updateFilters();
            
            const searchData = {
                action: 'tmu_search',
                nonce: tmuSearch.nonce,
                query: this.currentQuery,
                filters: this.currentFilters,
                page: this.currentPage,
                orderby: $('.tmu-sort-select').val() || 'relevance'
            };
            
            // Show loading state
            this.showLoading();
            
            $.ajax({
                url: tmuSearch.ajax_url,
                type: 'POST',
                data: searchData,
                success: (response) => {
                    if (response.success) {
                        this.displayResults(response.data);
                        this.updateUrl();
                    } else {
                        this.showError();
                    }
                },
                error: () => {
                    this.showError();
                },
                complete: () => {
                    this.isLoading = false;
                    this.hideLoading();
                }
            });
        }
        
        updateFilters() {
            this.currentFilters = {};
            
            $('.tmu-filter-checkbox:checked').each((index, checkbox) => {
                const $checkbox = $(checkbox);
                const filterType = $checkbox.data('filter-type');
                const filterValue = $checkbox.val();
                
                if (!this.currentFilters[filterType]) {
                    this.currentFilters[filterType] = [];
                }
                
                this.currentFilters[filterType].push(filterValue);
            });
        }
        
        displayResults(data) {
            // Update results
            if (this.currentPage === 1) {
                this.resultsContainer.html(data.html);
            } else {
                this.resultsContainer.find('.tmu-search-results-grid').append($(data.html).find('.tmu-search-results-grid').html());
            }
            
            // Update facets
            this.facetsContainer.html(data.facets);
            
            // Update pagination
            this.paginationContainer.html(data.pagination);
            
            // Update results count
            $('.tmu-results-count').text(data.total);
            
            // Update execution time
            $('.tmu-execution-time').text(data.execution_time.toFixed(3));
        }
        
        loadMoreResults() {
            this.currentPage++;
            this.performSearch(false);
        }
        
        clearFilters() {
            $('.tmu-filter-checkbox').prop('checked', false);
            this.currentFilters = {};
            this.performSearch();
        }
        
        showLoading() {
            this.resultsContainer.addClass('loading');
            $('.tmu-loading-spinner').show();
        }
        
        hideLoading() {
            this.resultsContainer.removeClass('loading');
            $('.tmu-loading-spinner').hide();
        }
        
        showError() {
            this.resultsContainer.html('<div class="tmu-error">An error occurred while searching. Please try again.</div>');
        }
        
        updateUrl() {
            const params = new URLSearchParams();
            
            if (this.currentQuery) {
                params.set('s', this.currentQuery);
            }
            
            Object.keys(this.currentFilters).forEach(filterType => {
                this.currentFilters[filterType].forEach(value => {
                    params.append(`filter[${filterType}]`, value);
                });
            });
            
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.replaceState({}, '', newUrl);
        }
        
        initUrlParams() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Set search query
            const query = urlParams.get('s');
            if (query) {
                this.searchInput.val(query);
                this.currentQuery = query;
            }
            
            // Set filters
            urlParams.forEach((value, key) => {
                const filterMatch = key.match(/^filter\[(.+)\]$/);
                if (filterMatch) {
                    const filterType = filterMatch[1];
                    $(`.tmu-filter-checkbox[data-filter-type="${filterType}"][value="${value}"]`).prop('checked', true);
                }
            });
            
            // Perform initial search if there are parameters
            if (query || Object.keys(this.currentFilters).length > 0) {
                this.performSearch();
            }
        }
    }
    
    // Initialize search when document is ready
    $(document).ready(() => {
        new TMUSearch();
    });
    
})(jQuery);
```

## 3. Intelligent Recommendations

### 3.1 Recommendation Engine
```php
// src/Search/RecommendationEngine.php
<?php
namespace TMU\Search;

class RecommendationEngine {
    public function get_similar_content($post_id, $limit = 10): array {
        $post_type = get_post_type($post_id);
        
        switch ($post_type) {
            case 'movie':
                return $this->get_similar_movies($post_id, $limit);
            case 'tv':
            case 'drama':
                return $this->get_similar_shows($post_id, $limit);
            case 'people':
                return $this->get_related_people($post_id, $limit);
            default:
                return [];
        }
    }
    
    private function get_similar_movies($movie_id, $limit): array {
        $movie_data = tmu_get_movie_data($movie_id);
        $genres = wp_get_post_terms($movie_id, 'genre', ['fields' => 'ids']);
        $year = $movie_data['release_date'] ? date('Y', strtotime($movie_data['release_date'])) : null;
        
        $args = [
            'post_type' => 'movie',
            'post_status' => 'publish',
            'posts_per_page' => $limit * 2, // Get more to filter out
            'post__not_in' => [$movie_id],
            'meta_query' => [],
            'tax_query' => []
        ];
        
        // Match by genre
        if (!empty($genres)) {
            $args['tax_query'][] = [
                'taxonomy' => 'genre',
                'field' => 'term_id',
                'terms' => $genres,
                'operator' => 'IN'
            ];
        }
        
        // Similar rating range
        if ($movie_data['vote_average']) {
            $rating_min = max(0, $movie_data['vote_average'] - 2);
            $rating_max = min(10, $movie_data['vote_average'] + 2);
            
            $args['meta_query'][] = [
                'key' => 'vote_average',
                'value' => [$rating_min, $rating_max],
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            ];
        }
        
        // Similar year range
        if ($year) {
            $year_terms = get_terms([
                'taxonomy' => 'by-year',
                'name' => range($year - 5, $year + 5),
                'fields' => 'ids'
            ]);
            
            if (!empty($year_terms)) {
                $args['tax_query'][] = [
                    'taxonomy' => 'by-year',
                    'field' => 'term_id',
                    'terms' => $year_terms,
                    'operator' => 'IN'
                ];
            }
        }
        
        $movies = get_posts($args);
        
        // Score and sort by similarity
        $scored_movies = array_map(function($movie) use ($movie_id) {
            return [
                'post' => $movie,
                'score' => $this->calculate_similarity_score($movie_id, $movie->ID)
            ];
        }, $movies);
        
        usort($scored_movies, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return array_slice(array_column($scored_movies, 'post'), 0, $limit);
    }
    
    private function calculate_similarity_score($post_id_1, $post_id_2): float {
        $score = 0.0;
        
        // Genre similarity (40% weight)
        $genres_1 = wp_get_post_terms($post_id_1, 'genre', ['fields' => 'ids']);
        $genres_2 = wp_get_post_terms($post_id_2, 'genre', ['fields' => 'ids']);
        $genre_intersection = array_intersect($genres_1, $genres_2);
        $genre_union = array_unique(array_merge($genres_1, $genres_2));
        
        if (!empty($genre_union)) {
            $score += (count($genre_intersection) / count($genre_union)) * 0.4;
        }
        
        // Rating similarity (30% weight)
        $data_1 = tmu_get_movie_data($post_id_1);
        $data_2 = tmu_get_movie_data($post_id_2);
        
        if ($data_1['vote_average'] && $data_2['vote_average']) {
            $rating_diff = abs($data_1['vote_average'] - $data_2['vote_average']);
            $rating_similarity = max(0, 1 - ($rating_diff / 10));
            $score += $rating_similarity * 0.3;
        }
        
        // Year similarity (20% weight)
        $year_1 = $data_1['release_date'] ? date('Y', strtotime($data_1['release_date'])) : null;
        $year_2 = $data_2['release_date'] ? date('Y', strtotime($data_2['release_date'])) : null;
        
        if ($year_1 && $year_2) {
            $year_diff = abs($year_1 - $year_2);
            $year_similarity = max(0, 1 - ($year_diff / 50)); // 50 year max difference
            $score += $year_similarity * 0.2;
        }
        
        // Popularity similarity (10% weight)
        if ($data_1['popularity'] && $data_2['popularity']) {
            $pop_1 = $data_1['popularity'];
            $pop_2 = $data_2['popularity'];
            $pop_diff = abs($pop_1 - $pop_2) / max($pop_1, $pop_2);
            $pop_similarity = max(0, 1 - $pop_diff);
            $score += $pop_similarity * 0.1;
        }
        
        return $score;
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
            'meta_key' => 'popularity',
            'orderby' => 'meta_value_num',
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
```

## 4. Advanced Filtering Components

### 4.1 Filter Templates
```php
// templates/search/filters.php
<div class="tmu-search-filters">
    <div class="tmu-filters-header">
        <h3><?php _e('Filters', 'tmu'); ?></h3>
        <button class="tmu-clear-filters"><?php _e('Clear All', 'tmu'); ?></button>
    </div>
    
    <div class="tmu-filter-group">
        <h4><?php _e('Content Type', 'tmu'); ?></h4>
        <div class="tmu-filter-options">
            <label class="tmu-filter-option">
                <input type="checkbox" class="tmu-filter-checkbox" data-filter-type="post_type" value="movie">
                <span><?php _e('Movies', 'tmu'); ?></span>
                <span class="tmu-filter-count">(<?php echo $facets['post_type']['movie'] ?? 0; ?>)</span>
            </label>
            <label class="tmu-filter-option">
                <input type="checkbox" class="tmu-filter-checkbox" data-filter-type="post_type" value="tv">
                <span><?php _e('TV Shows', 'tmu'); ?></span>
                <span class="tmu-filter-count">(<?php echo $facets['post_type']['tv'] ?? 0; ?>)</span>
            </label>
            <?php if (get_option('tmu_dramas') === 'on'): ?>
            <label class="tmu-filter-option">
                <input type="checkbox" class="tmu-filter-checkbox" data-filter-type="post_type" value="drama">
                <span><?php _e('Dramas', 'tmu'); ?></span>
                <span class="tmu-filter-count">(<?php echo $facets['post_type']['drama'] ?? 0; ?>)</span>
            </label>
            <?php endif; ?>
            <label class="tmu-filter-option">
                <input type="checkbox" class="tmu-filter-checkbox" data-filter-type="post_type" value="people">
                <span><?php _e('People', 'tmu'); ?></span>
                <span class="tmu-filter-count">(<?php echo $facets['post_type']['people'] ?? 0; ?>)</span>
            </label>
        </div>
    </div>
    
    <div class="tmu-filter-group">
        <h4><?php _e('Genres', 'tmu'); ?></h4>
        <div class="tmu-filter-options tmu-scrollable">
            <?php foreach ($facets['genres'] ?? [] as $genre_slug => $count): ?>
                <?php $genre = get_term_by('slug', $genre_slug, 'genre'); ?>
                <?php if ($genre): ?>
                <label class="tmu-filter-option">
                    <input type="checkbox" class="tmu-filter-checkbox" data-filter-type="genre" value="<?php echo esc_attr($genre_slug); ?>">
                    <span><?php echo esc_html($genre->name); ?></span>
                    <span class="tmu-filter-count">(<?php echo $count; ?>)</span>
                </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="tmu-filter-group">
        <h4><?php _e('Release Year', 'tmu'); ?></h4>
        <div class="tmu-year-range-slider">
            <input type="range" class="tmu-year-min" min="1900" max="<?php echo date('Y'); ?>" value="1900">
            <input type="range" class="tmu-year-max" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>">
            <div class="tmu-year-display">
                <span class="tmu-year-min-display">1900</span> - 
                <span class="tmu-year-max-display"><?php echo date('Y'); ?></span>
            </div>
        </div>
    </div>
    
    <div class="tmu-filter-group">
        <h4><?php _e('Rating', 'tmu'); ?></h4>
        <div class="tmu-rating-filter">
            <div class="tmu-rating-range">
                <input type="range" class="tmu-rating-slider" min="0" max="10" step="0.1" value="0">
                <div class="tmu-rating-display">
                    <span class="tmu-rating-value">0.0</span>+ ⭐
                </div>
            </div>
        </div>
    </div>
    
    <div class="tmu-filter-group">
        <h4><?php _e('Countries', 'tmu'); ?></h4>
        <div class="tmu-filter-search">
            <input type="text" class="tmu-country-search" placeholder="<?php _e('Search countries...', 'tmu'); ?>">
        </div>
        <div class="tmu-filter-options tmu-scrollable">
            <?php foreach ($facets['countries'] ?? [] as $country_slug => $count): ?>
                <?php $country = get_term_by('slug', $country_slug, 'country'); ?>
                <?php if ($country): ?>
                <label class="tmu-filter-option">
                    <input type="checkbox" class="tmu-filter-checkbox" data-filter-type="country" value="<?php echo esc_attr($country_slug); ?>">
                    <span><?php echo esc_html($country->name); ?></span>
                    <span class="tmu-filter-count">(<?php echo $count; ?>)</span>
                </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
```

## 5. Performance Optimization

### 5.1 Search Indexing
```php
// src/Search/SearchIndexManager.php
<?php
namespace TMU\Search;

class SearchIndexManager {
    public function create_search_indexes(): void {
        global $wpdb;
        
        // Create custom search table for better performance
        $search_table = $wpdb->prefix . 'tmu_search_index';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$search_table} (
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
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;";
        
        $wpdb->query($sql);
        
        // Create taxonomy search table
        $tax_search_table = $wpdb->prefix . 'tmu_taxonomy_search';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$tax_search_table} (
            term_id bigint(20) NOT NULL,
            taxonomy varchar(32) NOT NULL,
            name varchar(200) NOT NULL,
            search_content text NOT NULL,
            post_count int(11) DEFAULT 0,
            PRIMARY KEY (term_id, taxonomy),
            FULLTEXT KEY search_content (search_content)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;";
        
        $wpdb->query($sql);
    }
    
    public function index_post($post_id): void {
        global $wpdb;
        
        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, ['movie', 'tv', 'drama', 'people'])) {
            return;
        }
        
        $search_table = $wpdb->prefix . 'tmu_search_index';
        
        // Prepare search content
        $content_parts = [
            $post->post_title,
            $post->post_content,
            $post->post_excerpt
        ];
        
        // Add custom field content
        $custom_data = tmu_get_post_data($post_id);
        if ($custom_data['overview']) {
            $content_parts[] = $custom_data['overview'];
        }
        if ($custom_data['tagline']) {
            $content_parts[] = $custom_data['tagline'];
        }
        if ($custom_data['original_title']) {
            $content_parts[] = $custom_data['original_title'];
        }
        
        // Add taxonomy terms
        $taxonomies = get_object_taxonomies($post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'names']);
            $content_parts = array_merge($content_parts, $terms);
        }
        
        $search_vector = implode(' ', array_filter($content_parts));
        
        // Insert or update search index
        $wpdb->replace($search_table, [
            'post_id' => $post_id,
            'post_type' => $post->post_type,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'search_vector' => $search_vector,
            'popularity' => $custom_data['popularity'] ?? 0,
            'rating' => $custom_data['vote_average'] ?? 0,
            'release_year' => $custom_data['year'] ?? null,
            'updated_at' => current_time('mysql')
        ]);
    }
    
    public function reindex_all(): void {
        $post_types = ['movie', 'tv', 'drama', 'people'];
        
        foreach ($post_types as $post_type) {
            $posts = get_posts([
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids'
            ]);
            
            foreach ($posts as $post_id) {
                $this->index_post($post_id);
                
                // Prevent timeout
                if (count($posts) > 100) {
                    usleep(100000); // 0.1 second
                }
            }
        }
    }
}
```

## 6. Success Metrics

- [ ] Advanced search functionality implemented
- [ ] AJAX-powered real-time search working
- [ ] Faceted filtering system operational
- [ ] Autocomplete suggestions functional
- [ ] Recommendation engine providing relevant results
- [ ] Search performance optimized with indexing
- [ ] Mobile-responsive search interface
- [ ] Analytics tracking search behavior

## Next Steps

After completing this step, the theme will have:
- Powerful faceted search capabilities
- Real-time AJAX search with autocomplete
- Intelligent content recommendations
- Optimized search performance
- Enhanced content discoverability
- Advanced filtering options
- Mobile-friendly search experience
- Comprehensive search analytics