<?php
/**
 * Query Optimizer Class
 *
 * Handles database query optimization for the TMU theme.
 *
 * @package TMU
 * @subpackage Performance
 */

namespace TMU\Performance;

/**
 * Class QueryOptimizer
 */
class QueryOptimizer {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'optimize_queries']);
        add_action('pre_get_posts', [$this, 'optimize_main_query']);
    }
    
    /**
     * Optimize queries
     */
    public function optimize_queries(): void {
        // Optimize custom post type queries
        add_filter('posts_clauses', [$this, 'optimize_movie_queries'], 10, 2);
        add_filter('posts_clauses', [$this, 'optimize_tv_queries'], 10, 2);
        add_filter('posts_clauses', [$this, 'optimize_drama_queries'], 10, 2);
        
        // Add database indexes
        add_action('after_switch_theme', [$this, 'create_database_indexes']);
    }
    
    /**
     * Optimize main query
     */
    public function optimize_main_query($query): void {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Basic query optimization for all post types
        $query->set('update_post_meta_cache', false);
        $query->set('update_post_term_cache', false);
    }
    
    /**
     * Optimize movie queries
     */
    public function optimize_movie_queries($clauses, $query): array {
        global $wpdb;
        
        if (!is_admin() && $query->is_main_query() && $query->get('post_type') === 'movie') {
            // Join with custom table for better performance
            $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}tmu_movies tm ON {$wpdb->posts}.ID = tm.post_id";
            
            // Add commonly used fields to SELECT
            $clauses['fields'] .= ", tm.tmdb_id, tm.release_date, tm.runtime, tm.tmdb_vote_average";
            
            // Optimize ordering
            if ($query->get('orderby') === 'release_date') {
                $clauses['orderby'] = 'tm.release_date DESC';
            } elseif ($query->get('orderby') === 'rating') {
                $clauses['orderby'] = 'tm.tmdb_vote_average DESC';
            }
        }
        
        return $clauses;
    }
    
    /**
     * Optimize TV queries
     */
    public function optimize_tv_queries($clauses, $query): array {
        global $wpdb;
        
        if (!is_admin() && $query->is_main_query() && $query->get('post_type') === 'tv') {
            // Join with custom table for better performance
            $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}tmu_tv_series ttv ON {$wpdb->posts}.ID = ttv.post_id";
            
            // Add commonly used fields to SELECT
            $clauses['fields'] .= ", ttv.tmdb_id, ttv.first_air_date, ttv.number_of_seasons, ttv.tmdb_vote_average";
            
            // Optimize ordering
            if ($query->get('orderby') === 'first_air_date') {
                $clauses['orderby'] = 'ttv.first_air_date DESC';
            } elseif ($query->get('orderby') === 'rating') {
                $clauses['orderby'] = 'ttv.tmdb_vote_average DESC';
            }
        }
        
        return $clauses;
    }
    
    /**
     * Optimize drama queries
     */
    public function optimize_drama_queries($clauses, $query): array {
        global $wpdb;
        
        if (!is_admin() && $query->is_main_query() && $query->get('post_type') === 'drama') {
            // Join with custom table for better performance
            $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}tmu_dramas td ON {$wpdb->posts}.ID = td.post_id";
            
            // Add commonly used fields to SELECT
            $clauses['fields'] .= ", td.tmdb_id, td.first_air_date, td.number_of_episodes, td.tmdb_vote_average";
            
            // Optimize ordering
            if ($query->get('orderby') === 'first_air_date') {
                $clauses['orderby'] = 'td.first_air_date DESC';
            } elseif ($query->get('orderby') === 'rating') {
                $clauses['orderby'] = 'td.tmdb_vote_average DESC';
            }
        }
        
        return $clauses;
    }
    
    /**
     * Create database indexes
     */
    public function create_database_indexes(): void {
        global $wpdb;
        
        // Create indexes for better query performance
        $indexes = [
            "CREATE INDEX idx_tmu_movies_tmdb_id ON {$wpdb->prefix}tmu_movies (tmdb_id)",
            "CREATE INDEX idx_tmu_movies_release_date ON {$wpdb->prefix}tmu_movies (release_date)",
            "CREATE INDEX idx_tmu_movies_rating ON {$wpdb->prefix}tmu_movies (tmdb_vote_average)",
            "CREATE INDEX idx_tmu_tv_series_tmdb_id ON {$wpdb->prefix}tmu_tv_series (tmdb_id)",
            "CREATE INDEX idx_tmu_tv_series_first_air_date ON {$wpdb->prefix}tmu_tv_series (first_air_date)",
            "CREATE INDEX idx_tmu_dramas_tmdb_id ON {$wpdb->prefix}tmu_dramas (tmdb_id)",
            "CREATE INDEX idx_tmu_people_tmdb_id ON {$wpdb->prefix}tmu_people (tmdb_id)",
        ];
        
        foreach ($indexes as $index) {
            $wpdb->query($index);
        }
    }
}