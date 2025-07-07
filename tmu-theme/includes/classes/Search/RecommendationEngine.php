<?php
/**
 * Recommendation Engine
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
 * Intelligent Content Recommendation Engine
 */
class RecommendationEngine {
    
    /**
     * Cache group
     */
    private string $cache_group = 'tmu_recommendations';
    
    /**
     * Cache expiry
     */
    private int $cache_expiry = 3600;
    
    /**
     * Get similar content
     */
    public function get_similar_content($post_id, $limit = 10): array {
        $cache_key = "similar_content_{$post_id}_{$limit}";
        $cached = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $post_type = get_post_type($post_id);
        
        $similar_content = match ($post_type) {
            'movie' => $this->get_similar_movies($post_id, $limit),
            'tv', 'drama' => $this->get_similar_shows($post_id, $limit),
            'people' => $this->get_related_people($post_id, $limit),
            default => []
        };
        
        wp_cache_set($cache_key, $similar_content, $this->cache_group, $this->cache_expiry);
        
        return $similar_content;
    }
    
    /**
     * Get similar movies
     */
    private function get_similar_movies($movie_id, $limit): array {
        $movie_data = $this->get_movie_data($movie_id);
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
                'key' => 'tmu_movie_vote_average',
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
    
    /**
     * Get similar TV shows
     */
    private function get_similar_shows($show_id, $limit): array {
        $show_data = $this->get_tv_data($show_id);
        $genres = wp_get_post_terms($show_id, 'genre', ['fields' => 'ids']);
        $networks = wp_get_post_terms($show_id, 'network', ['fields' => 'ids']);
        
        $args = [
            'post_type' => ['tv', 'drama'],
            'post_status' => 'publish',
            'posts_per_page' => $limit * 2,
            'post__not_in' => [$show_id],
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
        
        // Match by network
        if (!empty($networks)) {
            $args['tax_query'][] = [
                'taxonomy' => 'network',
                'field' => 'term_id',
                'terms' => $networks,
                'operator' => 'IN'
            ];
        }
        
        $shows = get_posts($args);
        
        // Score and sort by similarity
        $scored_shows = array_map(function($show) use ($show_id) {
            return [
                'post' => $show,
                'score' => $this->calculate_similarity_score($show_id, $show->ID)
            ];
        }, $shows);
        
        usort($scored_shows, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return array_slice(array_column($scored_shows, 'post'), 0, $limit);
    }
    
    /**
     * Get related people
     */
    private function get_related_people($person_id, $limit): array {
        $person_data = $this->get_person_data($person_id);
        $known_for = $person_data['known_for_department'] ?? '';
        
        $args = [
            'post_type' => 'people',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'post__not_in' => [$person_id],
            'meta_query' => []
        ];
        
        // Match by department
        if ($known_for) {
            $args['meta_query'][] = [
                'key' => 'tmu_person_known_for_department',
                'value' => $known_for,
                'compare' => '='
            ];
        }
        
        return get_posts($args);
    }
    
    /**
     * Calculate similarity score
     */
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
        $data_1 = $this->get_post_data($post_id_1);
        $data_2 = $this->get_post_data($post_id_2);
        
        if ($data_1['vote_average'] && $data_2['vote_average']) {
            $rating_diff = abs($data_1['vote_average'] - $data_2['vote_average']);
            $rating_similarity = max(0, 1 - ($rating_diff / 10));
            $score += $rating_similarity * 0.3;
        }
        
        // Year similarity (20% weight)
        $year_1 = $this->extract_year($data_1);
        $year_2 = $this->extract_year($data_2);
        
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
    
    /**
     * Get trending content
     */
    public function get_trending_content($post_type = null, $limit = 20): array {
        $cache_key = "trending_content_{$post_type}_{$limit}";
        $cached = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $post_types = $post_type ? [$post_type] : ['movie', 'tv', 'drama'];
        
        $args = [
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_key' => 'tmu_movie_popularity',
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
        
        wp_cache_set($cache_key, $trending, $this->cache_group, HOUR_IN_SECONDS);
        
        return $trending;
    }
    
    /**
     * Get personalized recommendations
     */
    public function get_personalized_recommendations($user_id, $limit = 10): array {
        if (!$user_id) {
            return $this->get_trending_content(null, $limit);
        }
        
        $cache_key = "personalized_recs_{$user_id}_{$limit}";
        $cached = wp_cache_get($cache_key, $this->cache_group);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Get user's viewing history/preferences
        $user_genres = $this->get_user_preferred_genres($user_id);
        $user_ratings = $this->get_user_rating_preferences($user_id);
        
        $recommendations = $this->generate_recommendations_from_preferences(
            $user_genres, 
            $user_ratings, 
            $limit
        );
        
        wp_cache_set($cache_key, $recommendations, $this->cache_group, $this->cache_expiry);
        
        return $recommendations;
    }
    
    /**
     * Get user preferred genres
     */
    private function get_user_preferred_genres($user_id): array {
        // This would integrate with a rating/viewing system
        // For now, return popular genres
        return get_terms([
            'taxonomy' => 'genre',
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 5,
            'fields' => 'ids'
        ]);
    }
    
    /**
     * Get user rating preferences
     */
    private function get_user_rating_preferences($user_id): array {
        // This would analyze user's rating patterns
        // For now, return high-rated content preferences
        return [
            'min_rating' => 7.0,
            'preferred_range' => [7.0, 10.0]
        ];
    }
    
    /**
     * Generate recommendations from preferences
     */
    private function generate_recommendations_from_preferences($genres, $rating_prefs, $limit): array {
        $args = [
            'post_type' => ['movie', 'tv', 'drama'],
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => [],
            'tax_query' => []
        ];
        
        // Filter by preferred genres
        if (!empty($genres)) {
            $args['tax_query'][] = [
                'taxonomy' => 'genre',
                'field' => 'term_id',
                'terms' => $genres,
                'operator' => 'IN'
            ];
        }
        
        // Filter by rating preferences
        if (!empty($rating_prefs['preferred_range'])) {
            $args['meta_query'][] = [
                'key' => 'tmu_movie_vote_average',
                'value' => $rating_prefs['preferred_range'],
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            ];
        }
        
        return get_posts($args);
    }
    
    /**
     * Get movie data
     */
    private function get_movie_data($post_id): array {
        global $wpdb;
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tmu_movies WHERE post_id = %d",
            $post_id
        ), ARRAY_A);
        
        return $data ?: [];
    }
    
    /**
     * Get TV data
     */
    private function get_tv_data($post_id): array {
        global $wpdb;
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tmu_tv_series WHERE post_id = %d",
            $post_id
        ), ARRAY_A);
        
        return $data ?: [];
    }
    
    /**
     * Get person data
     */
    private function get_person_data($post_id): array {
        global $wpdb;
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tmu_people WHERE post_id = %d",
            $post_id
        ), ARRAY_A);
        
        return $data ?: [];
    }
    
    /**
     * Get post data (universal)
     */
    private function get_post_data($post_id): array {
        $post_type = get_post_type($post_id);
        
        return match ($post_type) {
            'movie' => $this->get_movie_data($post_id),
            'tv', 'drama' => $this->get_tv_data($post_id),
            'people' => $this->get_person_data($post_id),
            default => []
        };
    }
    
    /**
     * Extract year from data
     */
    private function extract_year($data): ?int {
        $date_field = $data['release_date'] ?? $data['first_air_date'] ?? null;
        
        if ($date_field) {
            return (int) date('Y', strtotime($date_field));
        }
        
        return null;
    }
    
    /**
     * Clear recommendation cache
     */
    public function clear_cache($post_id = null): void {
        if ($post_id) {
            wp_cache_delete("similar_content_{$post_id}", $this->cache_group);
        } else {
            wp_cache_flush_group($this->cache_group);
        }
    }
}