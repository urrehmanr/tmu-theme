<?php
/**
 * Movie Post Type
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
 * Movie Post Type Class
 */
class Movie extends AbstractPostType {
    
    /**
     * Post type slug
     *
     * @var string
     */
    protected $post_type = 'movie';
    
    /**
     * Supported taxonomies
     *
     * @var array
     */
    protected $taxonomies = ['genre', 'country', 'language', 'by-year', 'production-company'];
    
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
            'runtime' => [
                'type' => 'integer',
                'description' => 'Movie runtime in minutes',
                'single' => true,
            ],
            'budget' => [
                'type' => 'number',
                'description' => 'Movie budget',
                'single' => true,
            ],
            'revenue' => [
                'type' => 'number',
                'description' => 'Movie revenue',
                'single' => true,
            ],
            'director' => [
                'type' => 'string',
                'description' => 'Movie director',
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
            'trailer_url' => [
                'type' => 'string',
                'description' => 'Trailer video URL',
                'single' => true,
            ],
            'imdb_id' => [
                'type' => 'string',
                'description' => 'IMDb ID',
                'single' => true,
            ],
            'status' => [
                'type' => 'string',
                'description' => 'Movie status (released, in production, etc.)',
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
            'name' => __('Movies', 'tmu'),
            'singular_name' => __('Movie', 'tmu'),
            'add_new' => __('Add New Movie', 'tmu'),
            'add_new_item' => __('Add New Movie', 'tmu'),
            'edit_item' => __('Edit Movie', 'tmu'),
            'new_item' => __('New Movie', 'tmu'),
            'view_item' => __('View Movie', 'tmu'),
            'view_items' => __('View Movies', 'tmu'),
            'search_items' => __('Search Movies', 'tmu'),
            'not_found' => __('No movies found.', 'tmu'),
            'not_found_in_trash' => __('No movies found in Trash.', 'tmu'),
            'all_items' => __('All Movies', 'tmu'),
            'archives' => __('Movie Archives', 'tmu'),
            'attributes' => __('Movie Attributes', 'tmu'),
            'insert_into_item' => __('Insert into movie', 'tmu'),
            'uploaded_to_this_item' => __('Uploaded to this movie', 'tmu'),
            'featured_image' => __('Movie Poster', 'tmu'),
            'set_featured_image' => __('Set movie poster', 'tmu'),
            'remove_featured_image' => __('Remove movie poster', 'tmu'),
            'use_featured_image' => __('Use as movie poster', 'tmu'),
            'menu_name' => __('Movies', 'tmu'),
            'filter_items_list' => __('Filter movies list', 'tmu'),
            'items_list_navigation' => __('Movies list navigation', 'tmu'),
            'items_list' => __('Movies list', 'tmu'),
        ];
    }
    
    /**
     * Get post type arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('Movie database management system', 'tmu'),
            'menu_position' => 5,
            'menu_icon' => 'dashicons-format-video',
            'has_archive' => 'movies',
            'rewrite' => [
                'slug' => 'movie',
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
        // DEBUG: Always enable the post type for debugging
        tmu_log("Movie post type shouldRegister called - returning TRUE", 'debug');
        return true;
        
        /*
        // First check if the post type is enabled in the config
        $config_file = TMU_INCLUDES_DIR . '/config/post-types.php';
        
        if (file_exists($config_file)) {
            $post_types_config = include $config_file;
            if (isset($post_types_config['movie']['enabled'])) {
                return (bool) $post_types_config['movie']['enabled'];
            }
        }
        
        // Fall back to option check
        return function_exists('tmu_get_option') ? 
            tmu_get_option('tmu_movies', 'on') === 'on' : 
            true;
        */
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
            
            // Add custom columns after title
            if ($key === 'title') {
                $new_columns['movie_poster'] = __('Poster', 'tmu');
                $new_columns['movie_rating'] = __('Rating', 'tmu');
                $new_columns['movie_runtime'] = __('Runtime', 'tmu');
                $new_columns['movie_release_date'] = __('Release Date', 'tmu');
                $new_columns['movie_tmdb_id'] = __('TMDB ID', 'tmu');
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
            case 'movie_poster':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, [50, 75]);
                } else {
                    echo '<span class="dashicons dashicons-format-image"></span>';
                }
                break;
                
            case 'movie_rating':
                $rating = get_post_meta($post_id, 'average_rating', true);
                $vote_count = get_post_meta($post_id, 'vote_count', true);
                
                if ($rating > 0) {
                    echo sprintf('%.1f (%d)', floatval($rating), intval($vote_count));
                } else {
                    echo '—';
                }
                break;
                
            case 'movie_runtime':
                $runtime = get_post_meta($post_id, 'runtime', true);
                
                if ($runtime > 0) {
                    $hours = floor($runtime / 60);
                    $minutes = $runtime % 60;
                    
                    if ($hours > 0) {
                        echo sprintf('%dh %dm', $hours, $minutes);
                    } else {
                        echo sprintf('%dm', $minutes);
                    }
                } else {
                    echo '—';
                }
                break;
                
            case 'movie_release_date':
                $release_date = get_post_meta($post_id, 'release_date', true);
                
                if ($release_date) {
                    $timestamp = strtotime($release_date);
                    echo $timestamp ? date('M j, Y', $timestamp) : $release_date;
                } else {
                    echo '—';
                }
                break;
                
            case 'movie_tmdb_id':
                $tmdb_id = get_post_meta($post_id, 'tmdb_id', true);
                
                if ($tmdb_id) {
                    echo '<code>' . esc_html($tmdb_id) . '</code>';
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    /**
     * Make columns sortable
     *
     * @param array $columns Sortable columns
     * @return array
     */
    public function makeSortableColumns(array $columns): array {
        $columns['movie_rating'] = 'movie_rating';
        $columns['movie_runtime'] = 'movie_runtime';
        $columns['movie_release_date'] = 'movie_release_date';
        $columns['movie_tmdb_id'] = 'movie_tmdb_id';
        
        return $columns;
    }
    
    /**
     * Handle sorting for custom columns
     *
     * @param \WP_Query $query Query object
     */
    public function handleSorting(\WP_Query $query): void {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        switch ($orderby) {
            case 'movie_rating':
                $query->set('meta_key', 'average_rating');
                $query->set('orderby', 'meta_value_num');
                break;
                
            case 'movie_runtime':
                $query->set('meta_key', 'runtime');
                $query->set('orderby', 'meta_value_num');
                break;
                
            case 'movie_release_date':
                $query->set('meta_key', 'release_date');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'movie_tmdb_id':
                $query->set('meta_key', 'tmdb_id');
                $query->set('orderby', 'meta_value_num');
                break;
        }
    }
    
    /**
     * Add row actions
     *
     * @param array $actions Existing actions
     * @param \WP_Post $post Post object
     * @return array
     */
    public function addRowActions(array $actions, \WP_Post $post): array {
        if ($post->post_type === $this->post_type) {
            $tmdb_id = get_post_meta($post->ID, 'tmdb_id', true);
            
            if ($tmdb_id && tmu_is_tmdb_available()) {
                $sync_url = wp_nonce_url(
                    admin_url("admin-post.php?action=tmu_sync_movie&post_id={$post->ID}"),
                    'tmu_sync_movie_' . $post->ID
                );
                
                $actions['sync_tmdb'] = sprintf(
                    '<a href="%s" class="tmu-sync-movie" data-post-id="%d">%s</a>',
                    $sync_url,
                    $post->ID,
                    __('Sync with TMDB', 'tmu')
                );
            }
        }
        
        return $actions;
    }
    
    /**
     * Add bulk actions
     *
     * @param array $actions Existing actions
     * @return array
     */
    public function addBulkActions(array $actions): array {
        if (tmu_is_tmdb_available()) {
            $actions['sync_tmdb'] = __('Sync with TMDB', 'tmu');
        }
        
        $actions['clear_cache'] = __('Clear Cache', 'tmu');
        
        return $actions;
    }
    
    /**
     * Handle bulk actions
     *
     * @param string $redirect_to Redirect URL
     * @param string $doaction Action name
     * @param array $post_ids Post IDs
     * @return string
     */
    public function handleBulkActions(string $redirect_to, string $doaction, array $post_ids): string {
        switch ($doaction) {
            case 'sync_tmdb':
                $synced = 0;
                
                foreach ($post_ids as $post_id) {
                    $tmdb_id = get_post_meta($post_id, 'tmdb_id', true);
                    
                    if ($tmdb_id) {
                        // TODO: Implement TMDB sync in API step
                        $synced++;
                    }
                }
                
                $redirect_to = add_query_arg('synced_movies', $synced, $redirect_to);
                break;
                
            case 'clear_cache':
                foreach ($post_ids as $post_id) {
                    tmu_delete_cache("movie_{$post_id}");
                }
                
                $redirect_to = add_query_arg('cleared_cache', count($post_ids), $redirect_to);
                break;
        }
        
        return $redirect_to;
    }
    
    /**
     * Add custom hooks
     */
    protected function addHooks(): void {
        // Custom movie hooks
        add_action('save_post_movie', [$this, 'onMovieSaved'], 10, 2);
        add_filter('the_content', [$this, 'enhanceMovieContent']);
    }
    
    /**
     * Handle movie saved
     *
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     */
    public function onMovieSaved(int $post_id, \WP_Post $post): void {
        // Clear cache when movie is saved
        tmu_delete_cache("movie_{$post_id}");
        
        // Log movie update
        tmu_log("Movie updated: {$post->post_title} (ID: {$post_id})", 'info');
    }
    
    /**
     * Enhance movie content
     *
     * @param string $content Post content
     * @return string
     */
    public function enhanceMovieContent(string $content): string {
        if (is_singular('movie') && is_main_query()) {
            // Add movie information to content
            $movie_info = $this->getMovieInfoHTML(get_the_ID());
            $content = $movie_info . $content;
        }
        
        return $content;
    }
    
    /**
     * Get movie information HTML
     *
     * @param int $post_id Post ID
     * @return string
     */
    private function getMovieInfoHTML(int $post_id): string {
        $runtime = get_post_meta($post_id, 'runtime', true);
        $rating = get_post_meta($post_id, 'average_rating', true);
        $release_date = get_post_meta($post_id, 'release_date', true);
        
        $html = '<div class="movie-info bg-gray-100 p-4 rounded-lg mb-6">';
        $html .= '<h3 class="text-lg font-semibold mb-2">' . __('Movie Information', 'tmu') . '</h3>';
        $html .= '<div class="grid grid-cols-1 md:grid-cols-3 gap-4">';
        
        if ($runtime) {
            $hours = floor($runtime / 60);
            $minutes = $runtime % 60;
            $runtime_text = $hours > 0 ? sprintf('%dh %dm', $hours, $minutes) : sprintf('%dm', $minutes);
            $html .= '<div><strong>' . __('Runtime:', 'tmu') . '</strong> ' . $runtime_text . '</div>';
        }
        
        if ($rating) {
            $html .= '<div><strong>' . __('Rating:', 'tmu') . '</strong> ' . number_format(floatval($rating), 1) . '/10</div>';
        }
        
        if ($release_date) {
            $timestamp = strtotime($release_date);
            $date_text = $timestamp ? date('F j, Y', $timestamp) : $release_date;
            $html .= '<div><strong>' . __('Release Date:', 'tmu') . '</strong> ' . $date_text . '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}