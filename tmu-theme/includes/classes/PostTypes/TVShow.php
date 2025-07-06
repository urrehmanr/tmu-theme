<?php
/**
 * TV Show Post Type
 *
 * @package TMU\PostTypes
 * @version 1.0.0
 */

namespace TMU\PostTypes;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TV Show Post Type Class
 */
class TVShow extends AbstractPostType {
    
    /**
     * Post type slug
     *
     * @var string
     */
    protected $post_type = 'tv';
    
    /**
     * Supported taxonomies
     *
     * @var array
     */
    protected $taxonomies = ['genre', 'country', 'language', 'by-year', 'network'];
    
    /**
     * Post type supports
     *
     * @var array
     */
    protected $supports = ['title', 'editor', 'thumbnail', 'comments', 'excerpt', 'custom-fields'];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->meta_fields = array_merge($this->getDefaultMetaFields(), [
            'number_of_seasons' => [
                'type' => 'integer',
                'description' => 'Number of seasons',
                'single' => true,
            ],
            'number_of_episodes' => [
                'type' => 'integer',
                'description' => 'Total number of episodes',
                'single' => true,
            ],
            'episode_runtime' => [
                'type' => 'integer',
                'description' => 'Average episode runtime in minutes',
                'single' => true,
            ],
            'first_air_date' => [
                'type' => 'string',
                'description' => 'First air date',
                'single' => true,
            ],
            'last_air_date' => [
                'type' => 'string',
                'description' => 'Last air date',
                'single' => true,
            ],
            'status' => [
                'type' => 'string',
                'description' => 'TV show status (airing, ended, cancelled, etc.)',
                'single' => true,
            ],
            'type' => [
                'type' => 'string',
                'description' => 'TV show type (scripted, reality, documentary, etc.)',
                'single' => true,
            ],
            'network' => [
                'type' => 'string',
                'description' => 'TV network',
                'single' => true,
            ],
            'poster_url' => [
                'type' => 'string',
                'description' => 'Poster image URL',
                'single' => true,
            ],
            'backdrop_url' => [
                'type' => 'string',
                'description' => 'Backdrop image URL',
                'single' => true,
            ],
        ]);
    }
    
    /**
     * Get post type labels
     *
     * @return array
     */
    protected function getLabels(): array {
        return [
            'name' => __('TV Shows', 'tmu'),
            'singular_name' => __('TV Show', 'tmu'),
            'add_new' => __('Add New TV Show', 'tmu'),
            'add_new_item' => __('Add New TV Show', 'tmu'),
            'edit_item' => __('Edit TV Show', 'tmu'),
            'new_item' => __('New TV Show', 'tmu'),
            'view_item' => __('View TV Show', 'tmu'),
            'view_items' => __('View TV Shows', 'tmu'),
            'search_items' => __('Search TV Shows', 'tmu'),
            'not_found' => __('No TV shows found.', 'tmu'),
            'not_found_in_trash' => __('No TV shows found in Trash.', 'tmu'),
            'all_items' => __('All TV Shows', 'tmu'),
            'archives' => __('TV Show Archives', 'tmu'),
            'attributes' => __('TV Show Attributes', 'tmu'),
            'featured_image' => __('TV Show Poster', 'tmu'),
            'set_featured_image' => __('Set TV show poster', 'tmu'),
            'remove_featured_image' => __('Remove TV show poster', 'tmu'),
            'use_featured_image' => __('Use as TV show poster', 'tmu'),
            'menu_name' => __('TV Shows', 'tmu'),
        ];
    }
    
    /**
     * Get post type arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('TV Show database management system', 'tmu'),
            'menu_position' => 4,
            'menu_icon' => 'dashicons-video-alt3',
            'has_archive' => 'tv-shows',
            'rewrite' => [
                'slug' => 'tv-show',
                'with_front' => false,
                'feeds' => true,
                'pages' => true,
            ],
        ]);
    }
    
    /**
     * Check if post type should be registered
     *
     * @return bool
     */
    protected function shouldRegister(): bool {
        return tmu_get_option('tmu_tv_series', 'off') === 'on';
    }
    
    /**
     * Add custom admin columns
     *
     * @param array $columns Existing columns
     * @return array
     */
    public function addAdminColumns(array $columns): array {
        $new_columns = [];
        
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            
            if ($key === 'title') {
                $new_columns['tv_poster'] = __('Poster', 'tmu');
                $new_columns['tv_rating'] = __('Rating', 'tmu');
                $new_columns['tv_seasons'] = __('Seasons', 'tmu');
                $new_columns['tv_episodes'] = __('Episodes', 'tmu');
                $new_columns['tv_status'] = __('Status', 'tmu');
                $new_columns['tv_tmdb_id'] = __('TMDB ID', 'tmu');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display custom column content
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function displayAdminColumnContent(string $column, int $post_id): void {
        switch ($column) {
            case 'tv_poster':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, [50, 75]);
                } else {
                    echo '<span class="dashicons dashicons-format-image"></span>';
                }
                break;
                
            case 'tv_rating':
                $rating = get_post_meta($post_id, 'average_rating', true);
                $vote_count = get_post_meta($post_id, 'vote_count', true);
                
                if ($rating > 0) {
                    echo sprintf('%.1f (%d)', floatval($rating), intval($vote_count));
                } else {
                    echo '—';
                }
                break;
                
            case 'tv_seasons':
                $seasons = get_post_meta($post_id, 'number_of_seasons', true);
                echo $seasons ? intval($seasons) : '—';
                break;
                
            case 'tv_episodes':
                $episodes = get_post_meta($post_id, 'number_of_episodes', true);
                echo $episodes ? intval($episodes) : '—';
                break;
                
            case 'tv_status':
                $status = get_post_meta($post_id, 'status', true);
                if ($status) {
                    echo '<span class="tv-status status-' . esc_attr($status) . '">' . esc_html(ucfirst($status)) . '</span>';
                } else {
                    echo '—';
                }
                break;
                
            case 'tv_tmdb_id':
                $tmdb_id = get_post_meta($post_id, 'tmdb_id', true);
                echo $tmdb_id ? '<code>' . esc_html($tmdb_id) . '</code>' : '—';
                break;
        }
    }
    
    /**
     * Check if post type should be registered
     *
     * @return bool
     */
    protected function shouldRegister(): bool {
        return tmu_get_option('tmu_tv_series', 'off') === 'on';
    }
}