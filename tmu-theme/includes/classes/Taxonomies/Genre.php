<?php
/**
 * Genre Taxonomy
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
 * Genre Taxonomy Class
 */
class Genre extends AbstractTaxonomy {
    
    /**
     * Taxonomy slug
     *
     * @var string
     */
    protected $taxonomy = 'genre';
    
    /**
     * Object types this taxonomy applies to
     *
     * @var array
     */
    protected $object_types = ['movie', 'tv', 'drama'];
    
    /**
     * Whether this taxonomy is hierarchical
     *
     * @var bool
     */
    protected $hierarchical = true;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->meta_fields = [
            'color' => [
                'type' => 'string',
                'label' => __('Color', 'tmu'),
                'description' => __('Color code for this genre', 'tmu'),
                'input_type' => 'color',
                'single' => true,
                'show_in_rest' => true,
            ],
            'icon' => [
                'type' => 'string',
                'label' => __('Icon', 'tmu'),
                'description' => __('Dashicon class for this genre', 'tmu'),
                'input_type' => 'text',
                'single' => true,
                'show_in_rest' => true,
            ],
            'description_extended' => [
                'type' => 'string',
                'label' => __('Extended Description', 'tmu'),
                'description' => __('Detailed description of this genre', 'tmu'),
                'input_type' => 'textarea',
                'single' => true,
                'show_in_rest' => true,
            ],
        ];
        
        parent::__construct();
    }
    
    /**
     * Get taxonomy labels
     *
     * @return array
     */
    protected function getLabels(): array {
        return [
            'name' => __('Genres', 'tmu'),
            'singular_name' => __('Genre', 'tmu'),
            'search_items' => __('Search Genres', 'tmu'),
            'popular_items' => __('Popular Genres', 'tmu'),
            'all_items' => __('All Genres', 'tmu'),
            'parent_item' => __('Parent Genre', 'tmu'),
            'parent_item_colon' => __('Parent Genre:', 'tmu'),
            'edit_item' => __('Edit Genre', 'tmu'),
            'update_item' => __('Update Genre', 'tmu'),
            'add_new_item' => __('Add New Genre', 'tmu'),
            'new_item_name' => __('New Genre Name', 'tmu'),
            'separate_items_with_commas' => __('Separate genres with commas', 'tmu'),
            'add_or_remove_items' => __('Add or remove genres', 'tmu'),
            'choose_from_most_used' => __('Choose from the most used genres', 'tmu'),
            'not_found' => __('No genres found.', 'tmu'),
            'menu_name' => __('Genres', 'tmu'),
        ];
    }
    
    /**
     * Get taxonomy arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('Genres for movies, TV shows, and dramas', 'tmu'),
            'rewrite' => [
                'slug' => 'genre',
                'with_front' => false,
                'hierarchical' => true,
            ],
            'show_tagcloud' => true,
            'sort' => true,
        ]);
    }
    
    /**
     * Check if taxonomy should be registered
     *
     * @return bool
     */
    protected function shouldRegister(): bool {
        return (
            tmu_get_option('tmu_movies', 'off') === 'on' ||
            tmu_get_option('tmu_tv_series', 'off') === 'on' ||
            tmu_get_option('tmu_dramas', 'off') === 'on'
        );
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
                $new_columns['genre_color'] = __('Color', 'tmu');
                $new_columns['genre_icon'] = __('Icon', 'tmu');
                $new_columns['genre_usage'] = __('Usage', 'tmu');
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
            case 'genre_color':
                $color = get_term_meta($term_id, 'color', true);
                if ($color) {
                    $content = '<span class="genre-color-preview" style="display: inline-block; width: 20px; height: 20px; background-color: ' . esc_attr($color) . '; border: 1px solid #ccc; border-radius: 3px;"></span>';
                } else {
                    $content = '—';
                }
                break;
                
            case 'genre_icon':
                $icon = get_term_meta($term_id, 'icon', true);
                if ($icon) {
                    $content = '<span class="dashicons ' . esc_attr($icon) . '"></span>';
                } else {
                    $content = '—';
                }
                break;
                
            case 'genre_usage':
                $usage = $this->getGenreUsage($term_id);
                $content = sprintf(
                    __('Movies: %d, TV: %d, Dramas: %d', 'tmu'),
                    $usage['movies'],
                    $usage['tv'],
                    $usage['dramas']
                );
                break;
        }
        
        return $content;
    }
    
    /**
     * Get genre usage statistics
     *
     * @param int $term_id Term ID
     * @return array
     */
    private function getGenreUsage(int $term_id): array {
        $usage = [
            'movies' => 0,
            'tv' => 0,
            'dramas' => 0,
        ];
        
        // Count movies
        $movies = get_posts([
            'post_type' => 'movie',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'genre',
                    'field' => 'term_id',
                    'terms' => $term_id,
                ],
            ],
            'fields' => 'ids',
        ]);
        $usage['movies'] = count($movies);
        
        // Count TV shows
        $tv_shows = get_posts([
            'post_type' => 'tv',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'genre',
                    'field' => 'term_id',
                    'terms' => $term_id,
                ],
            ],
            'fields' => 'ids',
        ]);
        $usage['tv'] = count($tv_shows);
        
        // Count dramas
        $dramas = get_posts([
            'post_type' => 'drama',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'genre',
                    'field' => 'term_id',
                    'terms' => $term_id,
                ],
            ],
            'fields' => 'ids',
        ]);
        $usage['dramas'] = count($dramas);
        
        return $usage;
    }
    
    /**
     * Make columns sortable
     *
     * @param array $columns Sortable columns
     * @return array
     */
    public function makeSortableColumns(array $columns): array {
        $columns['genre_usage'] = 'genre_usage';
        return $columns;
    }
}