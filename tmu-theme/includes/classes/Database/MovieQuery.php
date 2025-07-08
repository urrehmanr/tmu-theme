<?php
/**
 * Movie Query Class
 * 
 * Efficient database queries class exactly as specified in Step 19 documentation
 * 
 * @package TMU\Database
 * @since 1.0.0
 */

namespace TMU\Database;

/**
 * MovieQuery Class
 * 
 * Implements the exact performance optimization guidelines
 * from Step 19 documentation lines 282-308
 */
class MovieQuery {
    
    /**
     * Get popular movies with efficient caching
     * 
     * This method implements the exact code from Step 19 documentation
     * for performance optimization guidelines
     * 
     * @param int $limit Number of movies to retrieve
     * @return array Popular movies data
     */
    public function get_popular_movies(int $limit = 10): array {
        global $wpdb;
        
        // Use prepared statements
        $query = $wpdb->prepare(
            "SELECT p.ID, p.post_title, m.tmdb_vote_average, m.poster_path
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->prefix}tmu_movies m ON p.ID = m.post_id
             WHERE p.post_type = 'movie' 
             AND p.post_status = 'publish'
             ORDER BY m.tmdb_popularity DESC
             LIMIT %d",
            $limit
        );
        
        // Use caching
        $cache_key = "popular_movies_{$limit}";
        $results = wp_cache_get($cache_key, 'tmu_movies');
        
        if ($results === false) {
            $results = $wpdb->get_results($query);
            wp_cache_set($cache_key, $results, 'tmu_movies', 3600);
        }
        
        return $results;
    }
    
    /**
     * Get movies by genre with optimized queries
     * 
     * @param int $genre_id Genre ID
     * @param int $limit Number of movies to retrieve
     * @return array Movies by genre
     */
    public function get_movies_by_genre(int $genre_id, int $limit = 10): array {
        global $wpdb;
        
        $cache_key = "movies_genre_{$genre_id}_{$limit}";
        $results = wp_cache_get($cache_key, 'tmu_movies');
        
        if ($results === false) {
            $query = $wpdb->prepare(
                "SELECT p.ID, p.post_title, m.tmdb_vote_average, m.poster_path
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->prefix}tmu_movies m ON p.ID = m.post_id
                 INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                 INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                 WHERE p.post_type = 'movie' 
                 AND p.post_status = 'publish'
                 AND tt.taxonomy = 'genre'
                 AND tt.term_id = %d
                 ORDER BY m.tmdb_popularity DESC
                 LIMIT %d",
                $genre_id,
                $limit
            );
            
            $results = $wpdb->get_results($query);
            wp_cache_set($cache_key, $results, 'tmu_movies', 3600);
        }
        
        return $results;
    }
    
    /**
     * Get recently added movies
     * 
     * @param int $limit Number of movies to retrieve
     * @return array Recently added movies
     */
    public function get_recent_movies(int $limit = 10): array {
        global $wpdb;
        
        $cache_key = "recent_movies_{$limit}";
        $results = wp_cache_get($cache_key, 'tmu_movies');
        
        if ($results === false) {
            $query = $wpdb->prepare(
                "SELECT p.ID, p.post_title, m.tmdb_vote_average, m.poster_path, p.post_date
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->prefix}tmu_movies m ON p.ID = m.post_id
                 WHERE p.post_type = 'movie' 
                 AND p.post_status = 'publish'
                 ORDER BY p.post_date DESC
                 LIMIT %d",
                $limit
            );
            
            $results = $wpdb->get_results($query);
            wp_cache_set($cache_key, $results, 'tmu_movies', 1800); // 30 minutes cache
        }
        
        return $results;
    }
    
    /**
     * Get movie statistics
     * 
     * @return array Movie statistics
     */
    public function get_movie_statistics(): array {
        global $wpdb;
        
        $cache_key = 'movie_statistics';
        $stats = wp_cache_get($cache_key, 'tmu_movies');
        
        if ($stats === false) {
            $stats = [
                'total_movies' => $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'movie' AND post_status = 'publish'"
                ),
                'avg_rating' => $wpdb->get_var(
                    "SELECT AVG(tmdb_vote_average) FROM {$wpdb->prefix}tmu_movies WHERE tmdb_vote_average > 0"
                ),
                'total_genres' => $wpdb->get_var(
                    "SELECT COUNT(DISTINCT t.term_id) 
                     FROM {$wpdb->terms} t
                     INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                     WHERE tt.taxonomy = 'genre'"
                )
            ];
            
            wp_cache_set($cache_key, $stats, 'tmu_movies', 7200); // 2 hours cache
        }
        
        return $stats;
    }
    
    /**
     * Search movies with optimized full-text search
     * 
     * @param string $search_term Search term
     * @param int $limit Number of results
     * @return array Search results
     */
    public function search_movies(string $search_term, int $limit = 20): array {
        global $wpdb;
        
        $search_term = sanitize_text_field($search_term);
        $cache_key = "movie_search_" . md5($search_term) . "_{$limit}";
        $results = wp_cache_get($cache_key, 'tmu_movies');
        
        if ($results === false) {
            $query = $wpdb->prepare(
                "SELECT p.ID, p.post_title, m.tmdb_vote_average, m.poster_path, m.overview
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->prefix}tmu_movies m ON p.ID = m.post_id
                 WHERE p.post_type = 'movie' 
                 AND p.post_status = 'publish'
                 AND (p.post_title LIKE %s OR m.overview LIKE %s)
                 ORDER BY p.post_title ASC
                 LIMIT %d",
                '%' . $wpdb->esc_like($search_term) . '%',
                '%' . $wpdb->esc_like($search_term) . '%',
                $limit
            );
            
            $results = $wpdb->get_results($query);
            wp_cache_set($cache_key, $results, 'tmu_movies', 1800); // 30 minutes cache
        }
        
        return $results;
    }
    
    /**
     * Clear movie-related caches
     * 
     * @return void
     */
    public function clear_cache(): void {
        wp_cache_flush_group('tmu_movies');
    }
    
    /**
     * Get movies by year with pagination
     * 
     * @param int $year Release year
     * @param int $page Page number
     * @param int $per_page Items per page
     * @return array Movies data with pagination info
     */
    public function get_movies_by_year(int $year, int $page = 1, int $per_page = 20): array {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        $cache_key = "movies_year_{$year}_{$page}_{$per_page}";
        $results = wp_cache_get($cache_key, 'tmu_movies');
        
        if ($results === false) {
            // Get total count
            $total_query = $wpdb->prepare(
                "SELECT COUNT(*) 
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->prefix}tmu_movies m ON p.ID = m.post_id
                 WHERE p.post_type = 'movie' 
                 AND p.post_status = 'publish'
                 AND YEAR(m.release_date) = %d",
                $year
            );
            $total = $wpdb->get_var($total_query);
            
            // Get movies
            $movies_query = $wpdb->prepare(
                "SELECT p.ID, p.post_title, m.tmdb_vote_average, m.poster_path, m.release_date
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->prefix}tmu_movies m ON p.ID = m.post_id
                 WHERE p.post_type = 'movie' 
                 AND p.post_status = 'publish'
                 AND YEAR(m.release_date) = %d
                 ORDER BY m.release_date DESC
                 LIMIT %d OFFSET %d",
                $year,
                $per_page,
                $offset
            );
            $movies = $wpdb->get_results($movies_query);
            
            $results = [
                'movies' => $movies,
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page)
            ];
            
            wp_cache_set($cache_key, $results, 'tmu_movies', 3600);
        }
        
        return $results;
    }
}