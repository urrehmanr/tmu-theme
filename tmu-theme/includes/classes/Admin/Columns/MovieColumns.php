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
                $new_columns['poster'] = __('Poster', 'tmu');
                $new_columns['release_date'] = __('Release Date', 'tmu');
                $new_columns['tmdb_id'] = __('TMDB ID', 'tmu');
                $new_columns['rating'] = __('Rating', 'tmu');
                $new_columns['runtime'] = __('Runtime', 'tmu');
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
            case 'release_date':
                $this->renderReleaseDate($post_id);
                break;
            case 'tmdb_id':
                $this->renderTMDBId($post_id);
                break;
            case 'rating':
                $this->renderRating($post_id);
                break;
            case 'runtime':
                $this->renderRuntime($post_id);
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
        return $columns;
    }
    
    /**
     * Handle column sorting
     * 
     * @param \WP_Query $query Current query
     */
    public function handleSorting(\WP_Query $query): void {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        if ($orderby === 'release_date') {
            $query->set('meta_key', 'release_date');
            $query->set('orderby', 'meta_value');
        } elseif ($orderby === 'rating') {
            $query->set('meta_key', 'vote_average');
            $query->set('orderby', 'meta_value_num');
        }
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
        $tmdb_id = tmu_get_meta($post_id, 'tmdb_id');
        if ($tmdb_id) {
            $url = 'https://www.themoviedb.org/movie/' . $tmdb_id;
            echo '<a href="' . esc_url($url) . '" target="_blank" class="tmu-tmdb-link">';
            echo esc_html($tmdb_id);
            echo '</a>';
        } else {
            echo '<span class="tmu-no-tmdb">No TMDB ID</span>';
        }
    }
    
    /**
     * Render release date column
     * 
     * @param int $post_id Post ID
     */
    private function renderReleaseDate(int $post_id): void {
        $date = tmu_get_meta($post_id, 'release_date');
        if ($date) {
            $formatted = date('M j, Y', strtotime($date));
            echo '<span class="tmu-release-date">' . esc_html($formatted) . '</span>';
        } else {
            echo '<span class="tmu-no-date">—</span>';
        }
    }
    
    /**
     * Render rating column
     * 
     * @param int $post_id Post ID
     */
    private function renderRating(int $post_id): void {
        $rating = tmu_get_meta($post_id, 'vote_average', 0);
        if ($rating > 0) {
            echo '<div class="tmu-rating-display">';
            echo '<span class="rating-value">' . number_format($rating, 1) . '</span>';
            echo '<span class="rating-stars">' . $this->getStarRating($rating) . '</span>';
            echo '</div>';
        } else {
            echo '<span class="tmu-no-rating">Not rated</span>';
        }
    }
    
    /**
     * Render runtime column
     * 
     * @param int $post_id Post ID
     */
    private function renderRuntime(int $post_id): void {
        $runtime = tmu_get_meta($post_id, 'runtime');
        if ($runtime && $runtime > 0) {
            $formatted = $this->formatRuntime($runtime);
            echo '<span class="tmu-runtime">' . esc_html($formatted) . '</span>';
        } else {
            echo '<span class="tmu-no-runtime">—</span>';
        }
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