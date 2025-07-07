<?php
/**
 * TV Show Admin Columns
 * 
 * Enhanced admin columns for TV show post type management with series-specific data,
 * network information, episode counts, and TMDB integration.
 * 
 * @package TMU\Admin\Columns
 * @since 1.0.0
 */

namespace TMU\Admin\Columns;

/**
 * TVColumns class
 * 
 * Handles enhanced admin columns for TV show post type
 */
class TVColumns {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_filter('manage_tv_posts_columns', [$this, 'addColumns']);
        add_action('manage_tv_posts_custom_column', [$this, 'renderColumns'], 10, 2);
        add_filter('manage_edit-tv_sortable_columns', [$this, 'addSortableColumns']);
        add_action('pre_get_posts', [$this, 'handleSorting']);
        add_filter('posts_clauses', [$this, 'modifyQueryForSorting'], 10, 2);
    }
    
    /**
     * Add custom columns to TV show admin list
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
                $new_columns['first_air_date'] = __('First Air Date', 'tmu');
                $new_columns['networks'] = __('Network', 'tmu');
                $new_columns['seasons'] = __('Seasons', 'tmu');
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
            case 'first_air_date':
                $this->renderFirstAirDate($post_id);
                break;
            case 'networks':
                $this->renderNetworks($post_id);
                break;
            case 'seasons':
                $this->renderSeasons($post_id);
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
        $columns['first_air_date'] = 'first_air_date';
        $columns['rating'] = 'rating';
        $columns['seasons'] = 'seasons';
        return $columns;
    }
    
    /**
     * Handle column sorting
     * 
     * @param \WP_Query $query Current query
     */
    public function handleSorting(\WP_Query $query): void {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'tv') {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        if ($orderby === 'first_air_date') {
            $query->set('meta_key', 'first_air_date');
            $query->set('orderby', 'meta_value');
        } elseif ($orderby === 'rating') {
            $query->set('meta_key', 'average_rating');
            $query->set('orderby', 'meta_value_num');
        } elseif ($orderby === 'seasons') {
            $query->set('meta_key', 'number_of_seasons');
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
        
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'tv') {
            return $clauses;
        }
        
        $orderby = $query->get('orderby');
        
        if (in_array($orderby, ['first_air_date', 'rating', 'seasons'])) {
            $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}tmu_tv_series ON {$wpdb->posts}.ID = {$wpdb->prefix}tmu_tv_series.ID";
            
            switch ($orderby) {
                case 'first_air_date':
                    $clauses['orderby'] = "{$wpdb->prefix}tmu_tv_series.release_timestamp " . $query->get('order');
                    break;
                case 'rating':
                    $clauses['orderby'] = "{$wpdb->prefix}tmu_tv_series.average_rating " . $query->get('order');
                    break;
                case 'seasons':
                    $clauses['orderby'] = "{$wpdb->prefix}tmu_tv_series.number_of_seasons " . $query->get('order');
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
        $tmdb_id = $this->getTVData($post_id, 'tmdb_id');
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
     * Render first air date column
     * 
     * @param int $post_id Post ID
     */
    private function renderFirstAirDate(int $post_id): void {
        $date = $this->getTVData($post_id, 'release_date');
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
     * Render networks column
     * 
     * @param int $post_id Post ID
     */
    private function renderNetworks(int $post_id): void {
        $networks = $this->getTVData($post_id, 'networks');
        if ($networks) {
            $networks_data = json_decode($networks, true);
            if (is_array($networks_data) && !empty($networks_data)) {
                $network_names = array_column($networks_data, 'name');
                echo '<span class="tmu-networks" style="color: #333;">' . esc_html(implode(', ', array_slice($network_names, 0, 2))) . '</span>';
                if (count($network_names) > 2) {
                    echo '<br><small style="color: #666;">+' . (count($network_names) - 2) . ' more</small>';
                }
            } else {
                echo '<span class="tmu-no-networks" style="color: #999;">—</span>';
            }
        } else {
            echo '<span class="tmu-no-networks" style="color: #999;">—</span>';
        }
    }
    
    /**
     * Render seasons column
     * 
     * @param int $post_id Post ID
     */
    private function renderSeasons(int $post_id): void {
        $season_count = $this->getTVData($post_id, 'number_of_seasons');
        $episode_count = $this->getTVData($post_id, 'number_of_episodes');
        
        if ($season_count) {
            echo '<div class="tmu-season-info">';
            echo '<span class="season-count" style="font-weight: bold; color: #0073aa;">' . number_format($season_count) . ' seasons</span>';
            if ($episode_count) {
                echo '<br><small style="color: #666;">' . number_format($episode_count) . ' episodes</small>';
            }
            echo '</div>';
        } else {
            echo '<span class="tmu-no-seasons" style="color: #999;">—</span>';
        }
    }
    
    /**
     * Render status column
     * 
     * @param int $post_id Post ID
     */
    private function renderStatus(int $post_id): void {
        $status = $this->getTVData($post_id, 'status');
        $post_status = get_post_status($post_id);
        $sync_status = get_post_meta($post_id, '_tmdb_last_sync', true);
        
        echo '<div class="tmu-status-display">';
        
        // Series status
        if ($status) {
            $status_colors = [
                'Ended' => '#e74c3c',
                'Returning Series' => '#27ae60',
                'Canceled' => '#c0392b',
                'In Production' => '#f39c12',
                'Planned' => '#3498db'
            ];
            
            $color = $status_colors[$status] ?? '#666';
            echo '<span class="series-status" style="color: ' . $color . '; font-weight: bold;">' . esc_html($status) . '</span><br>';
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
        $rating = $this->getTVData($post_id, 'average_rating');
        $vote_count = $this->getTVData($post_id, 'vote_count');
        
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
     * Get TV show data from database
     * 
     * @param int $post_id Post ID
     * @param string $field Field name
     * @return mixed Field value
     */
    private function getTVData(int $post_id, string $field) {
        global $wpdb;
        
        static $cache = [];
        
        if (!isset($cache[$post_id])) {
            $cache[$post_id] = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tmu_tv_series WHERE ID = %d",
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