<?php
/**
 * People Admin Columns
 * 
 * Enhanced admin columns for people post type management with person-specific data,
 * profession information, popularity scores, and career statistics.
 * 
 * @package TMU\Admin\Columns
 * @since 1.0.0
 */

namespace TMU\Admin\Columns;

/**
 * PeopleColumns class
 * 
 * Handles enhanced admin columns for people post type
 */
class PeopleColumns {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_filter('manage_people_posts_columns', [$this, 'addColumns']);
        add_action('manage_people_posts_custom_column', [$this, 'renderColumns'], 10, 2);
        add_filter('manage_edit-people_sortable_columns', [$this, 'addSortableColumns']);
        add_action('pre_get_posts', [$this, 'handleSorting']);
        add_filter('posts_clauses', [$this, 'modifyQueryForSorting'], 10, 2);
    }
    
    /**
     * Add custom columns to people admin list
     * 
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function addColumns(array $columns): array {
        $new_columns = [];
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['photo'] = __('Photo', 'tmu');
                $new_columns['tmdb_id'] = __('TMDB ID', 'tmu');
                $new_columns['birth_date'] = __('Birth Date', 'tmu');
                $new_columns['known_for'] = __('Known For', 'tmu');
                $new_columns['popularity'] = __('Popularity', 'tmu');
                $new_columns['credits'] = __('Credits', 'tmu');
                $new_columns['status'] = __('Status', 'tmu');
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
            case 'photo':
                $this->renderPhoto($post_id);
                break;
            case 'tmdb_id':
                $this->renderTMDBId($post_id);
                break;
            case 'birth_date':
                $this->renderBirthDate($post_id);
                break;
            case 'known_for':
                $this->renderKnownFor($post_id);
                break;
            case 'popularity':
                $this->renderPopularity($post_id);
                break;
            case 'credits':
                $this->renderCredits($post_id);
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
        $columns['birth_date'] = 'birth_date';
        $columns['popularity'] = 'popularity';
        $columns['credits'] = 'credits';
        return $columns;
    }
    
    /**
     * Handle column sorting
     * 
     * @param \WP_Query $query Current query
     */
    public function handleSorting(\WP_Query $query): void {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'people') {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        if ($orderby === 'birth_date') {
            $query->set('meta_key', 'date_of_birth');
            $query->set('orderby', 'meta_value');
        } elseif ($orderby === 'popularity') {
            $query->set('meta_key', 'popularity');
            $query->set('orderby', 'meta_value_num');
        } elseif ($orderby === 'credits') {
            $query->set('meta_key', 'no_movies');
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
        
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'people') {
            return $clauses;
        }
        
        $orderby = $query->get('orderby');
        
        if (in_array($orderby, ['birth_date', 'popularity', 'credits'])) {
            $clauses['join'] .= " LEFT JOIN {$wpdb->prefix}tmu_people ON {$wpdb->posts}.ID = {$wpdb->prefix}tmu_people.ID";
            
            switch ($orderby) {
                case 'birth_date':
                    $clauses['orderby'] = "{$wpdb->prefix}tmu_people.date_of_birth " . $query->get('order');
                    break;
                case 'popularity':
                    $clauses['orderby'] = "{$wpdb->prefix}tmu_people.popularity " . $query->get('order');
                    break;
                case 'credits':
                    $clauses['orderby'] = "({$wpdb->prefix}tmu_people.no_movies + {$wpdb->prefix}tmu_people.no_tv_series + {$wpdb->prefix}tmu_people.no_dramas) " . $query->get('order');
                    break;
            }
        }
        
        return $clauses;
    }
    
    /**
     * Render photo column
     * 
     * @param int $post_id Post ID
     */
    private function renderPhoto(int $post_id): void {
        $photo_id = get_post_thumbnail_id($post_id);
        if ($photo_id) {
            $photo = wp_get_attachment_image($photo_id, [50, 75], false, [
                'class' => 'tmu-admin-photo',
                'style' => 'border-radius: 50%; width: 50px; height: 50px; object-fit: cover; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'
            ]);
            echo '<div class="tmu-photo-column">' . $photo . '</div>';
        } else {
            echo '<div class="tmu-photo-placeholder" style="width: 50px; height: 50px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 10px; color: #666;">No photo</div>';
        }
    }
    
    /**
     * Render TMDB ID column
     * 
     * @param int $post_id Post ID
     */
    private function renderTMDBId(int $post_id): void {
        $tmdb_id = $this->getPeopleData($post_id, 'tmdb_id');
        if ($tmdb_id) {
            $url = 'https://www.themoviedb.org/person/' . $tmdb_id;
            echo '<a href="' . esc_url($url) . '" target="_blank" class="tmu-tmdb-link" style="color: #0073aa; text-decoration: none;">';
            echo '<span class="dashicons dashicons-external" style="font-size: 16px; vertical-align: middle;"></span> ';
            echo esc_html($tmdb_id);
            echo '</a>';
        } else {
            echo '<span class="tmu-no-tmdb" style="color: #999;">—</span>';
        }
    }
    
    /**
     * Render birth date column
     * 
     * @param int $post_id Post ID
     */
    private function renderBirthDate(int $post_id): void {
        $birth_date = $this->getPeopleData($post_id, 'date_of_birth');
        $death_date = $this->getPeopleData($post_id, 'dead_on');
        
        if ($birth_date) {
            $timestamp = strtotime($birth_date);
            if ($timestamp) {
                $formatted = date('M j, Y', $timestamp);
                $age = date_diff(date_create($birth_date), date_create('today'))->y;
                
                echo '<div class="tmu-birth-info">';
                echo '<span class="birth-date" style="display: block;">' . esc_html($formatted) . '</span>';
                
                if ($death_date) {
                    $death_timestamp = strtotime($death_date);
                    $death_age = date_diff(date_create($birth_date), date_create($death_date))->y;
                    echo '<small style="color: #e74c3c;">Died at ' . $death_age . '</small>';
                } else {
                    echo '<small style="color: #666;">Age: ' . $age . '</small>';
                }
                echo '</div>';
            } else {
                echo '<span class="tmu-no-date" style="color: #999;">—</span>';
            }
        } else {
            echo '<span class="tmu-no-date" style="color: #999;">—</span>';
        }
    }
    
    /**
     * Render known for column
     * 
     * @param int $post_id Post ID
     */
    private function renderKnownFor(int $post_id): void {
        $known_for = $this->getPeopleData($post_id, 'known_for');
        if ($known_for) {
            echo '<span class="tmu-known-for" style="color: #333; font-weight: 500;">' . esc_html($known_for) . '</span>';
        } else {
            echo '<span class="tmu-no-known-for" style="color: #999;">—</span>';
        }
    }
    
    /**
     * Render popularity column
     * 
     * @param int $post_id Post ID
     */
    private function renderPopularity(int $post_id): void {
        $popularity = $this->getPeopleData($post_id, 'popularity');
        if ($popularity && $popularity > 0) {
            $popularity_level = $this->getPopularityLevel($popularity);
            echo '<div class="tmu-popularity-display">';
            echo '<span class="popularity-value" style="font-weight: bold; color: ' . $popularity_level['color'] . ';">' . number_format($popularity, 1) . '</span><br>';
            echo '<small style="color: #666;">' . $popularity_level['label'] . '</small>';
            echo '</div>';
        } else {
            echo '<span class="tmu-no-popularity" style="color: #999;">—</span>';
        }
    }
    
    /**
     * Render credits column
     * 
     * @param int $post_id Post ID
     */
    private function renderCredits(int $post_id): void {
        $movies = $this->getPeopleData($post_id, 'no_movies') ?? 0;
        $tv_shows = $this->getPeopleData($post_id, 'no_tv_series') ?? 0;
        $dramas = $this->getPeopleData($post_id, 'no_dramas') ?? 0;
        
        $total = $movies + $tv_shows + $dramas;
        
        if ($total > 0) {
            echo '<div class="tmu-credits-display">';
            echo '<span class="total-credits" style="font-weight: bold; color: #0073aa;">' . number_format($total) . ' total</span><br>';
            echo '<small style="color: #666;">';
            $credit_parts = [];
            if ($movies > 0) $credit_parts[] = $movies . ' movies';
            if ($tv_shows > 0) $credit_parts[] = $tv_shows . ' TV';
            if ($dramas > 0) $credit_parts[] = $dramas . ' dramas';
            echo implode(', ', $credit_parts);
            echo '</small>';
            echo '</div>';
        } else {
            echo '<span class="tmu-no-credits" style="color: #999;">—</span>';
        }
    }
    
    /**
     * Render status column
     * 
     * @param int $post_id Post ID
     */
    private function renderStatus(int $post_id): void {
        $death_date = $this->getPeopleData($post_id, 'dead_on');
        $post_status = get_post_status($post_id);
        $sync_status = get_post_meta($post_id, '_tmdb_last_sync', true);
        
        echo '<div class="tmu-status-display">';
        
        // Life status
        if ($death_date) {
            echo '<span class="life-status" style="color: #e74c3c; font-weight: bold;">Deceased</span><br>';
        } else {
            echo '<span class="life-status" style="color: #27ae60; font-weight: bold;">Alive</span><br>';
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
     * Get people data from database
     * 
     * @param int $post_id Post ID
     * @param string $field Field name
     * @return mixed Field value
     */
    private function getPeopleData(int $post_id, string $field) {
        global $wpdb;
        
        static $cache = [];
        
        if (!isset($cache[$post_id])) {
            $cache[$post_id] = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tmu_people WHERE ID = %d",
                $post_id
            ), ARRAY_A);
        }
        
        return $cache[$post_id][$field] ?? null;
    }
    
    /**
     * Get popularity level with color coding
     * 
     * @param float $popularity Popularity score
     * @return array Level info with color and label
     */
    private function getPopularityLevel(float $popularity): array {
        if ($popularity >= 50) {
            return ['color' => '#e74c3c', 'label' => 'Very High'];
        } elseif ($popularity >= 20) {
            return ['color' => '#f39c12', 'label' => 'High'];
        } elseif ($popularity >= 10) {
            return ['color' => '#f1c40f', 'label' => 'Medium'];
        } elseif ($popularity >= 5) {
            return ['color' => '#27ae60', 'label' => 'Low'];
        } else {
            return ['color' => '#95a5a6', 'label' => 'Very Low'];
        }
    }
}