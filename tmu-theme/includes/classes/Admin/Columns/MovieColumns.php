<?php
/**
 * Movie Admin Columns
 * 
 * Enhanced admin columns for movie post type management with TMDB data,
 * ratings, runtime, and other movie-specific information.
 * 
 * @package TMU\Admin\Columns
 * @since 1.0.0
 */

namespace TMU\Admin\Columns;

/**
 * MovieColumns class
 * 
 * Handles enhanced admin columns for movie post type
 */
class MovieColumns {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_filter('manage_movie_posts_columns', [$this, 'addColumns']);
        add_action('manage_movie_posts_custom_column', [$this, 'renderColumns'], 10, 2);
        add_filter('manage_edit-movie_sortable_columns', [$this, 'addSortableColumns']);
        add_action('pre_get_posts', [$this, 'handleSorting']);
        add_filter('posts_clauses', [$this, 'modifyQueryForSorting'], 10, 2);
    }
    
    /**
     * Add custom columns to movie admin list
     * 
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function addColumns(array $columns): array {
        $new_columns = [];
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['poster'] = __('Poster', 'tmu-theme');
                $new_columns['tmdb_id'] = __('TMDB ID', 'tmu-theme');
                $new_columns['release_date'] = __('Release Date', 'tmu-theme');
                $new_columns['rating'] = __('Rating', 'tmu-theme');
                $new_columns['runtime'] = __('Runtime', 'tmu-theme');
                $new_columns['status'] = __('Status', 'tmu-theme');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Render custom column content
     * 
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function renderColumns(string $column, int $post_id): void {
        switch ($column) {
            case 'poster':
                $this->renderPoster($post_id);
                break;
            case 'tmdb_id':
                $this->renderTMDBId($post_id);
                break;
            case 'release_date':
                $this->renderReleaseDate($post_id);
                break;
            case 'rating':
                $this->renderRating($post_id);
                break;
            case 'runtime':
                $this->renderRuntime($post_id);
                break;
            case 'status':
                $this->renderStatus($post_id);
                break;
        }
    }
    
    /**
     * Add sortable columns
     * 
     * @param array $columns Existing sortable columns
     * @return array Modified sortable columns
     */
    public function addSortableColumns(array $columns): array {
        $columns['release_date'] = 'release_date';
        $columns['rating'] = 'rating';
        $columns['runtime'] = 'runtime';
        return $columns;
    }
    
    /**
     * Handle column sorting
     * 
     * @param \WP_Query $query Current query
     */
    public function handleSorting(\WP_Query $query): void {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'movie') {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        if ($orderby === 'release_date') {
            $query->set('meta_key', 'release_date');
            $query->set('orderby', 'meta_value');
        } elseif ($orderby === 'rating') {
            $query->set('meta_key', 'average_rating');
            $query->set('orderby', 'meta_value_num');
        } elseif ($orderby === 'runtime') {
            $query->set('meta_key', 'runtime');
            $query->set('orderby', 'meta_value_num');
        }
    }
    
    /**
     * Modify query for sorting with database integration
     * 
     * @param array $clauses Query clauses
     * @param \WP_Query $query Current query
     * @return array Modified clauses
     */
    public function modifyQueryForSorting(array $clauses, \WP_Query $query): array {
        global $wpdb;
        
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'movie') {
            return $clauses;
        }
        
        $orderby = $query->get('orderby');
        
        if (in_array($orderby, ['release_date', 'rating', 'runtime'])) {
            $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}tmu_movies ON {$wpdb->posts}.ID = {$wpdb->prefix}tmu_movies.ID";
            
            switch ($orderby) {
                case 'release_date':
                    $clauses['orderby'] = "{$wpdb->prefix}tmu_movies.release_timestamp " . $query->get('order');
                    break;
                case 'rating':
                    $clauses['orderby'] = "{$wpdb->prefix}tmu_movies.average_rating " . $query->get('order');
                    break;
                case 'runtime':
                    $clauses['orderby'] = "{$wpdb->prefix}tmu_movies.runtime " . $query->get('order');
                    break;
            }
        }
        
        return $clauses;
    }
    
    /**
     * Render poster column
     * 
     * @param int $post_id Post ID
     */
    private function renderPoster(int $post_id): void {
        $poster_id = get_post_thumbnail_id($post_id);
        if ($poster_id) {
            $poster = wp_get_attachment_image($poster_id, [50, 75], false, [
                'class' => 'tmu-admin-poster',
                'style' => 'border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'
            ]);
            echo '<div class="tmu-poster-column">' . $poster . '</div>';
        } else {
            echo '<div class="tmu-poster-placeholder" style="width: 50px; height: 75px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px; font-size: 10px; color: #666;">No poster</div>';
        }
    }
    
    /**
     * Render TMDB ID column
     * 
     * @param int $post_id Post ID
     */
    private function renderTMDBId(int $post_id): void {
        $tmdb_id = $this->getMovieData($post_id, 'tmdb_id');
        if ($tmdb_id) {
            $url = 'https://www.themoviedb.org/movie/' . $tmdb_id;
            echo '<a href="' . esc_url($url) . '" target="_blank" class="tmu-tmdb-link" style="color: #0073aa; text-decoration: none;">';
            echo '<span class="dashicons dashicons-external" style="font-size: 16px; vertical-align: middle;"></span> ';
            echo esc_html($tmdb_id);
            echo '</a>';
        } else {
            echo '<span class="tmu-no-tmdb" style="color: #999;">—</span>';
        }
    }
    
    /**
     * Render release date column
     * 
     * @param int $post_id Post ID
     */
    private function renderReleaseDate(int $post_id): void {
        $date = $this->getMovieData($post_id, 'release_date');
        if ($date) {
            $timestamp = strtotime($date);
            if ($timestamp) {
                $formatted = date('M j, Y', $timestamp);
                $year = date('Y', $timestamp);
                echo '<span class="tmu-release-date" style="display: block;">' . esc_html($formatted) . '</span>';
                echo '<small style="color: #666;">(' . esc_html($year) . ')</small>';
            } else {
                echo '<span class="tmu-no-date" style="color: #999;">—</span>';
            }
        } else {
            echo '<span class="tmu-no-date" style="color: #999;">—</span>';
        }
    }
    
    /**
     * Render rating column
     * 
     * @param int $post_id Post ID
     */
    private function renderRating(int $post_id): void {
        $rating = $this->getMovieData($post_id, 'average_rating');
        $vote_count = $this->getMovieData($post_id, 'vote_count');
        
        if ($rating && $rating > 0) {
            $stars = $this->getStarRating($rating);
            echo '<div class="tmu-rating-display">';
            echo '<span class="rating-value" style="font-weight: bold; color: #0073aa;">' . number_format($rating, 1) . '/10</span><br>';
            echo '<span class="rating-stars" style="color: #ffb900; font-size: 14px;">' . $stars . '</span>';
            if ($vote_count > 0) {
                echo '<br><small style="color: #666;">(' . number_format($vote_count) . ' votes)</small>';
            }
            echo '</div>';
        } else {
            echo '<span class="tmu-no-rating" style="color: #999;">Not rated</span>';
        }
    }
    
    /**
     * Render runtime column
     * 
     * @param int $post_id Post ID
     */
    private function renderRuntime(int $post_id): void {
        $runtime = $this->getMovieData($post_id, 'runtime');
        if ($runtime && $runtime > 0) {
            $formatted = $this->formatRuntime($runtime);
            echo '<span class="tmu-runtime" style="color: #333;">' . esc_html($formatted) . '</span>';
        } else {
            echo '<span class="tmu-no-runtime" style="color: #999;">—</span>';
        }
    }
    
    /**
     * Render status column
     * 
     * @param int $post_id Post ID
     */
    private function renderStatus(int $post_id): void {
        $status = get_post_status($post_id);
        $sync_status = get_post_meta($post_id, '_tmdb_last_sync', true);
        
        echo '<div class="tmu-status-display">';
        
        // Post status
        $status_colors = [
            'publish' => '#008a00',
            'draft' => '#b32d2e',
            'pending' => '#996633',
            'private' => '#0073aa'
        ];
        
        $color = $status_colors[$status] ?? '#666';
        echo '<span class="post-status" style="color: ' . $color . '; font-weight: bold;">' . ucfirst($status) . '</span><br>';
        
        // TMDB sync status
        if ($sync_status) {
            $time_diff = human_time_diff(strtotime($sync_status));
            echo '<small style="color: #666;">Synced ' . $time_diff . ' ago</small>';
        } else {
            echo '<small style="color: #e74c3c;">Not synced</small>';
        }
        
        echo '</div>';
    }
    
    /**
     * Get movie data from database
     * 
     * @param int $post_id Post ID
     * @param string $field Field name
     * @return mixed Field value
     */
    private function getMovieData(int $post_id, string $field) {
        global $wpdb;
        
        static $cache = [];
        
        if (!isset($cache[$post_id])) {
            $cache[$post_id] = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tmu_movies WHERE ID = %d",
                $post_id
            ), ARRAY_A);
        }
        
        return $cache[$post_id][$field] ?? null;
    }
    
    /**
     * Generate star rating
     * 
     * @param float $rating Rating (0-10)
     * @return string Star rating HTML
     */
    private function getStarRating(float $rating): string {
        $stars = '';
        $full_stars = floor($rating / 2);
        $half_star = ($rating / 2) - $full_stars >= 0.5;
        
        for ($i = 0; $i < $full_stars; $i++) {
            $stars .= '★';
        }
        
        if ($half_star) {
            $stars .= '☆';
        }
        
        // Fill remaining with empty stars
        $remaining = 5 - $full_stars - ($half_star ? 1 : 0);
        for ($i = 0; $i < $remaining; $i++) {
            $stars .= '☆';
        }
        
        return $stars;
    }
    
    /**
     * Format runtime in minutes to hours and minutes
     * 
     * @param int $minutes Runtime in minutes
     * @return string Formatted runtime
     */
    private function formatRuntime(int $minutes): string {
        if ($minutes < 60) {
            return $minutes . 'm';
        }
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($mins > 0) {
            return $hours . 'h ' . $mins . 'm';
        }
        
        return $hours . 'h';
    }
}