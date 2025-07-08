<?php
namespace TMU\Search;

class AjaxSearch {
    public function init(): void {
        add_action('wp_ajax_tmu_search', [$this, 'handle_search']);
        add_action('wp_ajax_nopriv_tmu_search', [$this, 'handle_search']);
        add_action('wp_ajax_tmu_get_suggestions', [$this, 'handle_suggestions']);
        add_action('wp_ajax_nopriv_tmu_get_suggestions', [$this, 'handle_suggestions']);
        add_action('wp_ajax_tmu_filter_search', [$this, 'handle_filter_search']);
        add_action('wp_ajax_nopriv_tmu_filter_search', [$this, 'handle_filter_search']);
    }
    
    public function handle_search(): void {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'tmu_search_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        $filters = $this->sanitize_filters($_POST['filters'] ?? []);
        $options = [
            'page' => intval($_POST['page'] ?? 1),
            'per_page' => intval($_POST['per_page'] ?? 20),
            'orderby' => sanitize_text_field($_POST['orderby'] ?? 'relevance')
        ];
        
        $search_manager = SearchManager::getInstance();
        $results = $search_manager->search($query, $filters, $options);
        
        $response = [
            'html' => $this->render_results($results->get_results()),
            'facets' => $this->render_facets($results->get_facets()),
            'pagination' => $this->render_pagination($results),
            'total' => $results->get_total(),
            'execution_time' => $results->get_execution_time(),
            'has_more' => $options['page'] < $results->get_max_pages()
        ];
        
        wp_send_json_success($response);
    }
    
    public function handle_suggestions(): void {
        $query = sanitize_text_field($_GET['q'] ?? '');
        
        if (strlen($query) < 2) {
            wp_send_json_success([]);
        }
        
        $suggestions = $this->get_suggestions($query);
        wp_send_json_success($suggestions);
    }
    
    public function handle_filter_search(): void {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'tmu_search_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        $filters = $this->sanitize_filters($_POST['filters'] ?? []);
        
        $filter_manager = new FilterManager();
        $facet_data = $filter_manager->get_facet_data($query, $filters);
        
        wp_send_json_success([
            'facets' => $this->render_facets($facet_data)
        ]);
    }
    
    public function get_suggestions($query): array {
        $suggestions = [
            'posts' => $this->get_post_suggestions($query),
            'terms' => $this->get_term_suggestions($query),
            'people' => $this->get_people_suggestions($query)
        ];
        
        return $suggestions;
    }
    
    private function get_post_suggestions($query): array {
        $posts = get_posts([
            'post_type' => ['movie', 'tv', 'drama'],
            's' => $query,
            'posts_per_page' => 5,
            'orderby' => 'relevance'
        ]);
        
        return array_map(function($post) {
            $post_type = get_post_type($post->ID);
            $data = [];
            
            if ($post_type === 'movie') {
                $movie_data = tmu_get_movie_data($post->ID);
                $data = [
                    'year' => $movie_data['release_date'] ? date('Y', strtotime($movie_data['release_date'])) : '',
                    'rating' => $movie_data['vote_average']
                ];
            } elseif (in_array($post_type, ['tv', 'drama'])) {
                $tv_data = tmu_get_tv_data($post->ID);
                $data = [
                    'year' => $tv_data['first_air_date'] ? date('Y', strtotime($tv_data['first_air_date'])) : '',
                    'rating' => $tv_data['vote_average']
                ];
            }
            
            return [
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => $post_type,
                'year' => $data['year'] ?? '',
                'rating' => $data['rating'] ?? '',
                'poster' => get_the_post_thumbnail_url($post->ID, 'thumbnail'),
                'url' => get_permalink($post->ID)
            ];
        }, $posts);
    }
    
    private function get_term_suggestions($query): array {
        $terms = get_terms([
            'taxonomy' => ['genre', 'country', 'language', 'network'],
            'search' => $query,
            'number' => 3,
            'hide_empty' => true
        ]);
        
        return array_map(function($term) {
            return [
                'id' => $term->term_id,
                'name' => $term->name,
                'taxonomy' => $term->taxonomy,
                'count' => $term->count,
                'url' => get_term_link($term)
            ];
        }, $terms);
    }
    
    private function get_people_suggestions($query): array {
        $people = get_posts([
            'post_type' => 'people',
            's' => $query,
            'posts_per_page' => 3,
            'orderby' => 'relevance'
        ]);
        
        return array_map(function($person) {
            $person_data = tmu_get_person_data($person->ID);
            return [
                'id' => $person->ID,
                'name' => $person->post_title,
                'known_for' => $person_data['known_for_department'] ?? '',
                'profile' => get_the_post_thumbnail_url($person->ID, 'thumbnail'),
                'url' => get_permalink($person->ID)
            ];
        }, $people);
    }
    
    private function sanitize_filters($filters): array {
        $sanitized = [];
        
        foreach ($filters as $filter_type => $values) {
            $filter_type = sanitize_key($filter_type);
            
            if (is_array($values)) {
                $sanitized[$filter_type] = array_map('sanitize_text_field', $values);
            } else {
                $sanitized[$filter_type] = [sanitize_text_field($values)];
            }
        }
        
        return $sanitized;
    }
    
    private function render_results($results): string {
        if (empty($results)) {
            return $this->render_no_results();
        }
        
        ob_start();
        echo '<div class="tmu-search-results-grid grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">';
        
        foreach ($results as $post) {
            $post_type = get_post_type($post->ID);
            
            if ($post_type === 'people') {
                $this->render_person_result($post);
            } else {
                $this->render_content_result($post);
            }
        }
        
        echo '</div>';
        return ob_get_clean();
    }
    
    private function render_content_result($post): void {
        $post_type = get_post_type($post->ID);
        $data = $post_type === 'movie' ? tmu_get_movie_data($post->ID) : tmu_get_tv_data($post->ID);
        $year = '';
        
        if ($post_type === 'movie' && !empty($data['release_date'])) {
            $year = date('Y', strtotime($data['release_date']));
        } elseif (in_array($post_type, ['tv', 'drama']) && !empty($data['first_air_date'])) {
            $year = date('Y', strtotime($data['first_air_date']));
        }
        
        ?>
        <div class="tmu-search-result-item bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="block">
                <div class="relative">
                    <?php if (has_post_thumbnail($post->ID)): ?>
                        <img src="<?php echo esc_url(get_the_post_thumbnail_url($post->ID, 'medium')); ?>" 
                             alt="<?php echo esc_attr($post->post_title); ?>"
                             class="w-full h-64 object-cover">
                    <?php else: ?>
                        <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                            <span class="text-gray-500">No Image</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($data['vote_average'])): ?>
                        <div class="absolute top-2 right-2 bg-yellow-500 text-white text-xs px-2 py-1 rounded">
                            ⭐ <?php echo esc_html(number_format($data['vote_average'], 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="absolute bottom-2 left-2 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded">
                        <?php echo esc_html(ucfirst($post_type)); ?>
                    </div>
                </div>
                
                <div class="p-4">
                    <h3 class="font-semibold text-sm mb-1 line-clamp-2"><?php echo esc_html($post->post_title); ?></h3>
                    <?php if ($year): ?>
                        <p class="text-gray-600 text-xs"><?php echo esc_html($year); ?></p>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <?php
    }
    
    private function render_person_result($post): void {
        $person_data = tmu_get_person_data($post->ID);
        ?>
        <div class="tmu-search-result-item bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="block">
                <div class="relative">
                    <?php if (has_post_thumbnail($post->ID)): ?>
                        <img src="<?php echo esc_url(get_the_post_thumbnail_url($post->ID, 'medium')); ?>" 
                             alt="<?php echo esc_attr($post->post_title); ?>"
                             class="w-full h-64 object-cover">
                    <?php else: ?>
                        <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                            <span class="text-gray-500">No Image</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="absolute bottom-2 left-2 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded">
                        Person
                    </div>
                </div>
                
                <div class="p-4">
                    <h3 class="font-semibold text-sm mb-1"><?php echo esc_html($post->post_title); ?></h3>
                    <?php if (!empty($person_data['known_for_department'])): ?>
                        <p class="text-gray-600 text-xs"><?php echo esc_html($person_data['known_for_department']); ?></p>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <?php
    }
    
    private function render_no_results(): string {
        ob_start();
        ?>
        <div class="tmu-no-results text-center py-12">
            <div class="max-w-md mx-auto">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2"><?php _e('No results found', 'tmu'); ?></h3>
                <p class="text-gray-600"><?php _e('Try adjusting your search terms or filters to find what you\'re looking for.', 'tmu'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_facets($facets): string {
        ob_start();
        
        foreach ($facets as $facet_name => $facet_data) {
            if (empty($facet_data)) continue;
            
            $this->render_facet($facet_name, $facet_data);
        }
        
        return ob_get_clean();
    }
    
    private function render_facet($facet_name, $facet_data): void {
        $labels = [
            'post_types' => __('Content Type', 'tmu'),
            'genres' => __('Genres', 'tmu'),
            'countries' => __('Countries', 'tmu'),
            'years' => __('Years', 'tmu'),
            'ratings' => __('Ratings', 'tmu')
        ];
        
        $label = $labels[$facet_name] ?? ucfirst($facet_name);
        
        ?>
        <div class="tmu-facet-group mb-6">
            <h4 class="font-semibold text-sm mb-3 text-gray-900"><?php echo esc_html($label); ?></h4>
            <div class="space-y-2 max-h-48 overflow-y-auto">
                <?php foreach ($facet_data as $value => $count): ?>
                    <?php if ($count > 0): ?>
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   class="tmu-filter-checkbox mr-2" 
                                   data-filter-type="<?php echo esc_attr($facet_name); ?>" 
                                   value="<?php echo esc_attr($value); ?>">
                            <span class="text-sm text-gray-700 flex-1"><?php echo esc_html($this->format_facet_value($facet_name, $value)); ?></span>
                            <span class="text-xs text-gray-500">(<?php echo esc_html($count); ?>)</span>
                        </label>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    private function format_facet_value($facet_name, $value): string {
        switch ($facet_name) {
            case 'post_types':
                $labels = [
                    'movie' => __('Movies', 'tmu'),
                    'tv' => __('TV Shows', 'tmu'),
                    'drama' => __('Dramas', 'tmu'),
                    'people' => __('People', 'tmu')
                ];
                return $labels[$value] ?? $value;
                
            case 'genres':
            case 'countries':
                $taxonomy = rtrim($facet_name, 's'); // Remove 's' to get taxonomy name
                $term = get_term_by('slug', $value, $taxonomy);
                return $term ? $term->name : $value;
                
            default:
                return $value;
        }
    }
    
    private function render_pagination($results): string {
        $current_page = $results->get_current_page();
        $max_pages = $results->get_max_pages();
        $total = $results->get_total();
        
        if ($max_pages <= 1) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="tmu-search-pagination flex items-center justify-between mt-8">
            <div class="text-sm text-gray-700">
                <?php printf(__('Showing %d results', 'tmu'), $total); ?>
            </div>
            
            <?php if ($current_page < $max_pages): ?>
                <button class="tmu-load-more bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                    <?php _e('Load More', 'tmu'); ?>
                </button>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}