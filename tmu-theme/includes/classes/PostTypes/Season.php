<?php
/**
 * Season Post Type
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
 * Season Post Type Class
 */
class Season extends AbstractPostType {
    
    /**
     * Post type slug
     *
     * @var string
     */
    protected $post_type = 'season';
    
    /**
     * Supported taxonomies
     *
     * @var array
     */
    protected $taxonomies = [];
    
    /**
     * Post type supports
     *
     * @var array
     */
    protected $supports = ['title', 'editor', 'thumbnail', 'custom-fields'];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->meta_fields = array_merge($this->getDefaultMetaFields(), [
            'season_number' => [
                'type' => 'integer',
                'description' => 'Season number',
                'single' => true,
            ],
            'episode_count' => [
                'type' => 'integer',
                'description' => 'Number of episodes in this season',
                'single' => true,
            ],
            'air_date' => [
                'type' => 'string',
                'description' => 'Season air date',
                'single' => true,
            ],
            'tv_show_id' => [
                'type' => 'integer',
                'description' => 'Parent TV show ID',
                'single' => true,
            ],
            'overview' => [
                'type' => 'string',
                'description' => 'Season overview',
                'single' => true,
            ],
            'poster_path' => [
                'type' => 'string',
                'description' => 'Season poster path',
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
            'name' => __('Seasons', 'tmu'),
            'singular_name' => __('Season', 'tmu'),
            'add_new' => __('Add New Season', 'tmu'),
            'add_new_item' => __('Add New Season', 'tmu'),
            'edit_item' => __('Edit Season', 'tmu'),
            'new_item' => __('New Season', 'tmu'),
            'view_item' => __('View Season', 'tmu'),
            'view_items' => __('View Seasons', 'tmu'),
            'search_items' => __('Search Seasons', 'tmu'),
            'not_found' => __('No seasons found.', 'tmu'),
            'not_found_in_trash' => __('No seasons found in Trash.', 'tmu'),
            'all_items' => __('All Seasons', 'tmu'),
            'menu_name' => __('Seasons', 'tmu'),
        ];
    }
    
    /**
     * Get post type arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('TV show seasons', 'tmu'),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=tv',
            'show_in_admin_bar' => false,
            'has_archive' => false,
            'rewrite' => [
                'slug' => 'season',
                'with_front' => false,
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
}