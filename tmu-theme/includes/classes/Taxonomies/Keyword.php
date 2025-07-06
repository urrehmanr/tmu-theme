<?php
/**
 * Keyword Taxonomy
 *
 * @package TMU\Taxonomies
 * @version 1.0.0
 */

namespace TMU\Taxonomies;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Keyword Taxonomy Class
 * 
 * Keywords and tags for movies and TV shows
 */
class Keyword extends AbstractTaxonomy {
    
    /**
     * Taxonomy slug
     *
     * @var string
     */
    protected $taxonomy = 'keyword';
    
    /**
     * Object types this taxonomy applies to
     *
     * @var array
     */
    protected $object_types = [];
    
    /**
     * Whether this taxonomy is hierarchical
     *
     * @var bool
     */
    protected $hierarchical = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->object_types = $this->getPostTypes();
        
        $this->meta_fields = [
            'trending' => [
                'type' => 'boolean',
                'label' => __('Trending', 'tmu'),
                'description' => __('Mark this keyword as trending', 'tmu'),
                'input_type' => 'checkbox',
                'single' => true,
                'show_in_rest' => true,
            ],
            'search_volume' => [
                'type' => 'integer',
                'label' => __('Search Volume', 'tmu'),
                'description' => __('Monthly search volume for this keyword', 'tmu'),
                'input_type' => 'number',
                'single' => true,
                'show_in_rest' => true,
            ],
        ];
        
        parent::__construct();
    }
    
    /**
     * Get post types for this taxonomy
     *
     * @return array
     */
    private function getPostTypes(): array {
        $post_types = [];
        
        if (tmu_get_option('tmu_movies', 'off') === 'on') {
            $post_types[] = 'movie';
        }
        
        if (tmu_get_option('tmu_tv_series', 'off') === 'on') {
            $post_types[] = 'tv';
        }
        
        return $post_types;
    }
    
    /**
     * Get taxonomy labels
     *
     * @return array
     */
    protected function getLabels(): array {
        return [
            'name' => __('Keywords', 'tmu'),
            'singular_name' => __('Keyword', 'tmu'),
            'search_items' => __('Search Keywords', 'tmu'),
            'popular_items' => __('Popular Keywords', 'tmu'),
            'all_items' => __('All Keywords', 'tmu'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __('Edit Keyword', 'tmu'),
            'update_item' => __('Update Keyword', 'tmu'),
            'add_new_item' => __('Add New Keyword', 'tmu'),
            'new_item_name' => __('New Keyword Name', 'tmu'),
            'separate_items_with_commas' => __('Separate keywords with commas', 'tmu'),
            'add_or_remove_items' => __('Add or remove keywords', 'tmu'),
            'choose_from_most_used' => __('Choose from the most used keywords', 'tmu'),
            'not_found' => __('No keywords found.', 'tmu'),
            'menu_name' => __('Keywords', 'tmu'),
        ];
    }
    
    /**
     * Get taxonomy arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('Keywords and tags for movies and TV shows', 'tmu'),
            'rewrite' => [
                'slug' => 'keyword',
                'with_front' => false,
                'hierarchical' => false,
            ],
            'show_tagcloud' => true,
            'show_admin_column' => false, // Don't show in post list by default
            'meta_box_cb' => 'post_tags_meta_box',
        ]);
    }
    
    /**
     * Check if taxonomy should be registered
     *
     * @return bool
     */
    protected function shouldRegister(): bool {
        $has_movies = tmu_get_option('tmu_movies', 'off') === 'on';
        $has_tv = tmu_get_option('tmu_tv_series', 'off') === 'on';
        $has_dramas = tmu_get_option('tmu_dramas', 'off') === 'on';
        
        // Don't register if only dramas is enabled
        if ($has_dramas && !$has_movies && !$has_tv) {
            return false;
        }
        
        // Register if movies or TV series is enabled
        return $has_movies || $has_tv;
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
            if ($key === 'description') {
                $new_columns['keyword_trending'] = __('Trending', 'tmu');
                $new_columns['keyword_volume'] = __('Search Volume', 'tmu');
                $new_columns['keyword_usage'] = __('Usage', 'tmu');
            }
            
            $new_columns[$key] = $title;
        }
        
        return $new_columns;
    }
    
    /**
     * Display custom column content
     *
     * @param string $content Column content
     * @param string $column_name Column name
     * @param int $term_id Term ID
     * @return string
     */
    public function displayAdminColumnContent(string $content, string $column_name, int $term_id): string {
        switch ($column_name) {
            case 'keyword_trending':
                $trending = get_term_meta($term_id, 'trending', true);
                if ($trending) {
                    $content = '<span class="dashicons dashicons-chart-line" style="color: #ff6b35;" title="' . __('Trending', 'tmu') . '"></span>';
                } else {
                    $content = '—';
                }
                break;
                
            case 'keyword_volume':
                $volume = get_term_meta($term_id, 'search_volume', true);
                if ($volume) {
                    $content = number_format($volume);
                } else {
                    $content = '—';
                }
                break;
                
            case 'keyword_usage':
                $usage = $this->getKeywordUsage($term_id);
                $total = array_sum($usage);
                if ($total > 0) {
                    $content = sprintf(
                        __('Total: %d (Movies: %d, TV: %d)', 'tmu'),
                        $total,
                        $usage['movies'],
                        $usage['tv']
                    );
                } else {
                    $content = __('Not used', 'tmu');
                }
                break;
        }
        
        return $content;
    }
    
    /**
     * Get keyword usage statistics
     *
     * @param int $term_id Term ID
     * @return array
     */
    private function getKeywordUsage(int $term_id): array {
        $usage = [
            'movies' => 0,
            'tv' => 0,
        ];
        
        // Count movies with this keyword
        if (post_type_exists('movie')) {
            $movies = get_posts([
                'post_type' => 'movie',
                'posts_per_page' => -1,
                'tax_query' => [
                    [
                        'taxonomy' => 'keyword',
                        'field' => 'term_id',
                        'terms' => $term_id,
                    ],
                ],
                'fields' => 'ids',
            ]);
            $usage['movies'] = count($movies);
        }
        
        // Count TV shows with this keyword
        if (post_type_exists('tv')) {
            $tv_shows = get_posts([
                'post_type' => 'tv',
                'posts_per_page' => -1,
                'tax_query' => [
                    [
                        'taxonomy' => 'keyword',
                        'field' => 'term_id',
                        'terms' => $term_id,
                    ],
                ],
                'fields' => 'ids',
            ]);
            $usage['tv'] = count($tv_shows);
        }
        
        return $usage;
    }
    
    /**
     * Make columns sortable
     *
     * @param array $columns Sortable columns
     * @return array
     */
    public function makeSortableColumns(array $columns): array {
        $columns['keyword_volume'] = 'keyword_volume';
        $columns['keyword_usage'] = 'keyword_usage';
        return $columns;
    }
}