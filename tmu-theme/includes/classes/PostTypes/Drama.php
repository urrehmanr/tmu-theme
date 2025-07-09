<?php
/**
 * Drama Post Type
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
 * Drama Post Type Class
 */
class Drama extends AbstractPostType {
    
    /**
     * Post type slug
     *
     * @var string
     */
    protected $post_type = 'drama';
    
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
                'description' => 'Drama status (airing, ended, cancelled, etc.)',
                'single' => true,
            ],
            'network' => [
                'type' => 'string',
                'description' => 'TV network or platform',
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
            'name' => __('Dramas', 'tmu'),
            'singular_name' => __('Drama', 'tmu'),
            'add_new' => __('Add New Drama', 'tmu'),
            'add_new_item' => __('Add New Drama', 'tmu'),
            'edit_item' => __('Edit Drama', 'tmu'),
            'new_item' => __('New Drama', 'tmu'),
            'view_item' => __('View Drama', 'tmu'),
            'view_items' => __('View Dramas', 'tmu'),
            'search_items' => __('Search Dramas', 'tmu'),
            'not_found' => __('No dramas found.', 'tmu'),
            'not_found_in_trash' => __('No dramas found in Trash.', 'tmu'),
            'all_items' => __('All Dramas', 'tmu'),
            'archives' => __('Drama Archives', 'tmu'),
            'attributes' => __('Drama Attributes', 'tmu'),
            'featured_image' => __('Drama Poster', 'tmu'),
            'set_featured_image' => __('Set drama poster', 'tmu'),
            'remove_featured_image' => __('Remove drama poster', 'tmu'),
            'use_featured_image' => __('Use as drama poster', 'tmu'),
            'menu_name' => __('Dramas', 'tmu'),
        ];
    }
    
    /**
     * Get post type arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('Drama series database management system', 'tmu'),
            'menu_position' => 6,
            'menu_icon' => 'dashicons-heart',
            'has_archive' => 'dramas',
            'rewrite' => [
                'slug' => 'drama',
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
        // First check if the post type is enabled in the config
        $config_file = TMU_INCLUDES_DIR . '/config/post-types.php';
        
        if (file_exists($config_file)) {
            $post_types_config = include $config_file;
            if (isset($post_types_config['drama']['enabled'])) {
                return (bool) $post_types_config['drama']['enabled'];
            }
        }
        
        // Fall back to option check
        return function_exists('tmu_get_option') ? 
            tmu_get_option('tmu_dramas', 'on') === 'on' : 
            true;
    }
}