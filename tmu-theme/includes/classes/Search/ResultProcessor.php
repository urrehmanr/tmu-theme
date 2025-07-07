<?php
namespace TMU\Search;

/**
 * Search Result Processor
 * 
 * Processes and ranks search results for relevance
 */
class ResultProcessor {
    
    /**
     * Process search results
     * 
     * @param array $raw_results Raw WP_Post objects
     * @param string $search_query Original search query
     * @return array Processed and ranked results
     */
    public function process(array $raw_results, string $search_query): array {
        if (empty($raw_results) || empty($search_query)) {
            return $raw_results;
        }
        
        // Calculate relevance scores for each result
        $scored_results = array_map(function($post) use ($search_query) {
            return [
                'post' => $post,
                'score' => $this->calculate_relevance_score($post, $search_query)
            ];
        }, $raw_results);
        
        // Sort by relevance score
        usort($scored_results, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Return only the posts
        return array_column($scored_results, 'post');
    }
    
    /**
     * Calculate relevance score for a post
     * 
     * @param \WP_Post $post Post object
     * @param string $search_query Search query
     * @return float Relevance score
     */
    private function calculate_relevance_score(\WP_Post $post, string $search_query): float {
        $score = 0.0;
        $query_terms = $this->extract_query_terms($search_query);
        
        if (empty($query_terms)) {
            return $score;
        }
        
        // Title match (highest weight: 40%)
        $title_score = $this->calculate_text_match_score($post->post_title, $query_terms);
        $score += $title_score * 0.4;
        
        // Content match (20%)
        $content_score = $this->calculate_text_match_score($post->post_content, $query_terms);
        $score += $content_score * 0.2;
        
        // Meta fields match (25%)
        $meta_score = $this->calculate_meta_match_score($post->ID, $query_terms);
        $score += $meta_score * 0.25;
        
        // Taxonomy terms match (10%)
        $taxonomy_score = $this->calculate_taxonomy_match_score($post->ID, $query_terms);
        $score += $taxonomy_score * 0.1;
        
        // Popularity boost (5%)
        $popularity_score = $this->calculate_popularity_score($post->ID);
        $score += $popularity_score * 0.05;
        
        return $score;
    }
    
    /**
     * Extract search terms from query
     * 
     * @param string $search_query Search query
     * @return array Search terms
     */
    private function extract_query_terms(string $search_query): array {
        // Handle quoted phrases
        $phrases = [];
        if (preg_match_all('/"([^"]+)"/', $search_query, $matches)) {
            $phrases = $matches[1];
            $search_query = preg_replace('/"[^"]+"/', '', $search_query);
        }
        
        // Extract individual words
        $words = array_filter(explode(' ', strtolower(trim($search_query))));
        
        // Remove stop words
        $words = $this->remove_stop_words($words);
        
        return array_merge($phrases, $words);
    }
    
    /**
     * Calculate text match score
     * 
     * @param string $text Text to search in
     * @param array $query_terms Query terms
     * @return float Match score (0-1)
     */
    private function calculate_text_match_score(string $text, array $query_terms): float {
        if (empty($text) || empty($query_terms)) {
            return 0.0;
        }
        
        $text_lower = strtolower($text);
        $score = 0.0;
        $max_score = count($query_terms);
        
        foreach ($query_terms as $term) {
            $term_lower = strtolower($term);
            
            // Exact match bonus
            if (strpos($text_lower, $term_lower) !== false) {
                if (strlen($term) > 3) {
                    $score += 1.0; // Full points for longer terms
                } else {
                    $score += 0.5; // Partial points for short terms
                }
                
                // Additional bonus for exact word match
                if (preg_match('/\b' . preg_quote($term_lower, '/') . '\b/', $text_lower)) {
                    $score += 0.5;
                }
            }
            
            // Partial match for longer terms
            if (strlen($term) > 4) {
                $partial_term = substr($term_lower, 0, -1);
                if (strpos($text_lower, $partial_term) !== false) {
                    $score += 0.3;
                }
            }
        }
        
        return min($score / $max_score, 1.0);
    }
    
    /**
     * Calculate meta fields match score
     * 
     * @param int $post_id Post ID
     * @param array $query_terms Query terms
     * @return float Match score (0-1)
     */
    private function calculate_meta_match_score(int $post_id, array $query_terms): float {
        $post_type = get_post_type($post_id);
        $meta_fields = $this->get_searchable_meta_fields($post_type);
        
        $total_score = 0.0;
        $field_count = 0;
        
        foreach ($meta_fields as $field) {
            $value = get_post_meta($post_id, $field, true);
            if (!empty($value)) {
                $field_score = $this->calculate_text_match_score($value, $query_terms);
                $total_score += $field_score;
                $field_count++;
            }
        }
        
        return $field_count > 0 ? $total_score / $field_count : 0.0;
    }
    
    /**
     * Calculate taxonomy match score
     * 
     * @param int $post_id Post ID
     * @param array $query_terms Query terms
     * @return float Match score (0-1)
     */
    private function calculate_taxonomy_match_score(int $post_id, array $query_terms): float {
        $taxonomies = get_object_taxonomies(get_post_type($post_id));
        $total_score = 0.0;
        $term_count = 0;
        
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'names']);
            
            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term_name) {
                    $term_score = $this->calculate_text_match_score($term_name, $query_terms);
                    $total_score += $term_score;
                    $term_count++;
                }
            }
        }
        
        return $term_count > 0 ? $total_score / $term_count : 0.0;
    }
    
    /**
     * Calculate popularity score
     * 
     * @param int $post_id Post ID
     * @return float Popularity score (0-1)
     */
    private function calculate_popularity_score(int $post_id): float {
        $post_type = get_post_type($post_id);
        $popularity = 0;
        
        switch ($post_type) {
            case 'movie':
                $movie_data = function_exists('tmu_get_movie_data') ? tmu_get_movie_data($post_id) : [];
                $popularity = $movie_data['popularity'] ?? 0;
                break;
                
            case 'tv':
            case 'drama':
                $tv_data = function_exists('tmu_get_tv_data') ? tmu_get_tv_data($post_id) : [];
                $popularity = $tv_data['popularity'] ?? 0;
                break;
                
            case 'people':
                $person_data = function_exists('tmu_get_person_data') ? tmu_get_person_data($post_id) : [];
                $popularity = $person_data['popularity'] ?? 0;
                break;
        }
        
        // Normalize popularity score (assuming max popularity around 1000)
        return min($popularity / 1000, 1.0);
    }
    
    /**
     * Get searchable meta fields for post type
     * 
     * @param string $post_type Post type
     * @return array Meta field names
     */
    private function get_searchable_meta_fields(string $post_type): array {
        switch ($post_type) {
            case 'movie':
                return [
                    'tmu_movie_original_title',
                    'tmu_movie_overview',
                    'tmu_movie_tagline'
                ];
                
            case 'tv':
            case 'drama':
                return [
                    'tmu_tv_name',
                    'tmu_tv_original_name',
                    'tmu_tv_overview'
                ];
                
            case 'people':
                return [
                    'tmu_person_biography',
                    'tmu_person_also_known_as',
                    'tmu_person_known_for_department'
                ];
                
            default:
                return [];
        }
    }
    
    /**
     * Remove common stop words
     * 
     * @param array $words Words array
     * @return array Filtered words
     */
    private function remove_stop_words(array $words): array {
        $stop_words = [
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
            'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were', 'be',
            'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will',
            'would', 'could', 'should', 'may', 'might', 'must', 'can', 'shall',
            'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it',
            'we', 'they', 'me', 'him', 'her', 'us', 'them'
        ];
        
        return array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 2 && !in_array(strtolower($word), $stop_words);
        });
    }
    
    /**
     * Highlight search terms in text
     * 
     * @param string $text Original text
     * @param string $search_query Search query
     * @param string $highlight_class CSS class for highlighting
     * @return string Text with highlighted terms
     */
    public function highlight_search_terms(string $text, string $search_query, string $highlight_class = 'search-highlight'): string {
        $query_terms = $this->extract_query_terms($search_query);
        
        if (empty($query_terms)) {
            return $text;
        }
        
        foreach ($query_terms as $term) {
            if (strlen($term) > 2) {
                $pattern = '/\b(' . preg_quote($term, '/') . ')\b/i';
                $replacement = '<span class="' . esc_attr($highlight_class) . '">$1</span>';
                $text = preg_replace($pattern, $replacement, $text);
            }
        }
        
        return $text;
    }
    
    /**
     * Generate search excerpt
     * 
     * @param \WP_Post $post Post object
     * @param string $search_query Search query
     * @param int $length Excerpt length
     * @return string Search excerpt
     */
    public function generate_search_excerpt(\WP_Post $post, string $search_query, int $length = 160): string {
        $content = strip_tags($post->post_content);
        $query_terms = $this->extract_query_terms($search_query);
        
        if (empty($query_terms)) {
            return wp_trim_words($content, 20);
        }
        
        // Find the first occurrence of any search term
        $first_match_pos = strlen($content);
        foreach ($query_terms as $term) {
            $pos = stripos($content, $term);
            if ($pos !== false && $pos < $first_match_pos) {
                $first_match_pos = $pos;
            }
        }
        
        if ($first_match_pos < strlen($content)) {
            // Extract excerpt around the first match
            $start = max(0, $first_match_pos - 50);
            $excerpt = substr($content, $start, $length);
            
            // Trim to word boundaries
            if ($start > 0) {
                $excerpt = '...' . ltrim($excerpt);
                $first_space = strpos($excerpt, ' ');
                if ($first_space !== false) {
                    $excerpt = substr($excerpt, $first_space);
                }
            }
            
            if (strlen($excerpt) >= $length) {
                $last_space = strrpos(substr($excerpt, 0, $length), ' ');
                if ($last_space !== false) {
                    $excerpt = substr($excerpt, 0, $last_space) . '...';
                }
            }
            
            return trim($excerpt);
        }
        
        // Fallback to regular excerpt
        return wp_trim_words($content, 20);
    }
}