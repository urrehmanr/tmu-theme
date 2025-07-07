<?php
/**
 * Drama Admin Columns
 * 
 * Enhanced admin columns for drama post type management with drama-specific data,
 * channel information, air schedule, and episode counts.
 * 
 * @package TMU\Admin\Columns
 * @since 1.0.0
 */

namespace TMU\Admin\Columns;

/**
 * DramaColumns class
 * 
 * Handles enhanced admin columns for drama post type
 */
class DramaColumns {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_filter('manage_drama_posts_columns', [$this, 'addColumns']);
        add_action('manage_drama_posts_custom_column', [$this, 'renderColumns'], 10, 2);
        add_filter('manage_edit-drama_sortable_columns', [$this, 'addSortableColumns']);
        add_action('pre_get_posts', [$this, 'handleSorting']);
        add_filter('posts_clauses', [$this, 'modifyQueryForSorting'], 10, 2);
    }
    
    /**
     * Add custom columns to drama admin list
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
                $new_columns['tmdb_id'] = __('TMDB ID', 'tmu');
                $new_columns['release_date'] = __('Air Date', 'tmu');
                $new_columns['channel'] = __('Channel', 'tmu');
                $new_columns['schedule'] = __('Schedule', 'tmu');
                $new_columns['status'] = __('Status', 'tmu');
                $new_columns['rating'] = __('Rating', 'tmu');
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
            case 'channel':
                $this->renderChannel($post_id);
                break;
            case 'schedule':
                $this->renderSchedule($post_id);
                break;
            case 'status':
                $this->renderStatus($post_id);
                break;
            case 'rating':
                $this->renderRating($post_id);
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
        $columns['channel'] = 'channel';
        return $columns;
    }
    
    /**
     * Handle column sorting
     * 
     * @param \WP_Query $query Current query
     */
    public function handleSorting(\WP_Query $query): void {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'drama') {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        if ($orderby === 'release_date') {
            $query->set('meta_key', 'release_date');
            $query->set('orderby', 'meta_value');
        } elseif ($orderby === 'rating') {
            $query->set('meta_key', 'average_rating');
            $query->set('orderby', 'meta_value_num');
        } elseif ($orderby === 'channel') {
            $query->set('meta_key', 'production_house');
            $query->set('orderby', 'meta_value');
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
        
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'drama') {
            return $clauses;
        }
        
        $orderby = $query->get('orderby');
        
        if (in_array($orderby, ['release_date', 'rating', 'channel'])) {
            $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}tmu_dramas ON {$wpdb->posts}.ID = {$wpdb->prefix}tmu_dramas.ID";
            
            switch ($orderby) {
                case 'release_date':
                    $clauses['orderby'] = "{$wpdb->prefix}tmu_dramas.release_timestamp " . $query->get('order');
                    break;
                case 'rating':
                    $clauses['orderby'] = "{$wpdb->prefix}tmu_dramas.average_rating " . $query->get('order');
                    break;
                case 'channel':
                    $clauses['orderby'] = "{$wpdb->prefix}tmu_dramas.production_house " . $query->get('order');
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
        $tmdb_id = $this->getDramaData($post_id, 'tmdb_id');
        if ($tmdb_id) {
            $url = 'https://www.themoviedb.org/tv/' . $tmdb_id;
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
        $date = $this->getDramaData($post_id, 'release_date');
        if ($date) {
            $timestamp = strtotime($date);
            if ($timestamp) {
                $formatted = date('M j, Y', $timestamp);
                $year = date('Y', $timestamp);
                echo '<span class="tmu-air-date" style="display: block;">' . esc_html($formatted) . '</span>';
                echo '<small style="color: #666;">(' . esc_html($year) . ')</small>';
            } else {
                echo '<span class="tmu-no-date" style="color: #999;">—</span>';
            }
        } else {
            echo '<span class="tmu-no-date" style="color: #999;">—</span>';
        }
    }
    
    /**
     * Render channel column
     * 
     * @param int $post_id Post ID
     */
    private function renderChannel(int $post_id): void {
        $channel = $this->getDramaData($post_id, 'production_house');
        if ($channel) {
            echo '<span class="tmu-channel" style="color: #333; font-weight: 500;">' . esc_html($channel) . '</span>';
        } else {
            echo '<span class="tmu-no-channel" style="color: #999;">—</span>';
        }
    }
    
    /**
     * Render schedule column
     * 
     * @param int $post_id Post ID
     */
    private function renderSchedule(int $post_id): void {
        $schedule_day = $this->getDramaData($post_id, 'schedule_day');
        $schedule_time = $this->getDramaData($post_id, 'schedule_time');
        
        if ($schedule_day || $schedule_time) {
            echo '<div class="tmu-schedule-info">';
            if ($schedule_day) {
                echo '<span class="schedule-day" style="display: block; font-weight: bold; color: #0073aa;">' . esc_html($schedule_day) . '</span>';
            }
            if ($schedule_time) {
                echo '<small style="color: #666;">' . esc_html($schedule_time) . '</small>';
            }
            echo '</div>';
        } else {
            echo '<span class="tmu-no-schedule" style="color: #999;">—</span>';
        }
    }
    
    /**
     * Render status column
     * 
     * @param int $post_id Post ID
     */
    private function renderStatus(int $post_id): void {
        $finished = $this->getDramaData($post_id, 'finished');
        $post_status = get_post_status($post_id);
        $sync_status = get_post_meta($post_id, '_tmdb_last_sync', true);
        
        echo '<div class="tmu-status-display">';
        
        // Drama status
        if ($finished !== null) {
            $is_finished = $finished === 'Yes';
            $status_color = $is_finished ? '#e74c3c' : '#27ae60';
            $status_text = $is_finished ? 'Finished' : 'Ongoing';
            echo '<span class="drama-status" style="color: ' . $status_color . '; font-weight: bold;">' . esc_html($status_text) . '</span><br>';
        }
        
        // Post status
        $post_status_colors = [
            'publish' => '#008a00',
            'draft' => '#b32d2e',
            'pending' => '#996633',
            'private' => '#0073aa'
        ];
        
        $color = $post_status_colors[$post_status] ?? '#666';
        echo '<span class="post-status" style="color: ' . $color . '; font-size: 12px;">' . ucfirst($post_status) . '</span>';
        
        // TMDB sync status
        if ($sync_status) {
            $time_diff = human_time_diff(strtotime($sync_status));
            echo '<br><small style="color: #666;">Synced ' . $time_diff . ' ago</small>';
        } else {
            echo '<br><small style="color: #e74c3c;">Not synced</small>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render rating column
     * 
     * @param int $post_id Post ID
     */
    private function renderRating(int $post_id): void {
        $rating = $this->getDramaData($post_id, 'average_rating');
        $vote_count = $this->getDramaData($post_id, 'vote_count');
        
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
     * Get drama data from database
     * 
     * @param int $post_id Post ID
     * @param string $field Field name
     * @return mixed Field value
     */
    private function getDramaData(int $post_id, string $field) {
        global $wpdb;
        
        static $cache = [];
        
        if (!isset($cache[$post_id])) {
            $cache[$post_id] = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tmu_dramas WHERE ID = %d",
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
}