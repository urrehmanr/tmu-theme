<?php
/**
 * Drama Episode Post Type
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
 * Drama Episode Post Type Class
 */
class DramaEpisode extends AbstractPostType {
    
    /**
     * Post type slug
     *
     * @var string
     */
    protected $post_type = 'drama-episode';
    
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
            'episode_number' => [
                'type' => 'integer',
                'description' => 'Episode number',
                'single' => true,
            ],
            'air_date' => [
                'type' => 'string',
                'description' => 'Episode air date',
                'single' => true,
            ],
            'runtime' => [
                'type' => 'integer',
                'description' => 'Episode runtime in minutes',
                'single' => true,
            ],
            'drama_id' => [
                'type' => 'integer',
                'description' => 'Parent drama ID',
                'single' => true,
            ],
            'overview' => [
                'type' => 'string',
                'description' => 'Episode overview',
                'single' => true,
            ],
            'still_path' => [
                'type' => 'string',
                'description' => 'Episode still image path',
                'single' => true,
            ],
            'production_code' => [
                'type' => 'string',
                'description' => 'Production code',
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
            'name' => __('Drama Episodes', 'tmu'),
            'singular_name' => __('Drama Episode', 'tmu'),
            'add_new' => __('Add New Episode', 'tmu'),
            'add_new_item' => __('Add New Episode', 'tmu'),
            'edit_item' => __('Edit Episode', 'tmu'),
            'new_item' => __('New Episode', 'tmu'),
            'view_item' => __('View Episode', 'tmu'),
            'view_items' => __('View Episodes', 'tmu'),
            'search_items' => __('Search Episodes', 'tmu'),
            'not_found' => __('No episodes found.', 'tmu'),
            'not_found_in_trash' => __('No episodes found in Trash.', 'tmu'),
            'all_items' => __('All Episodes', 'tmu'),
            'menu_name' => __('Episodes', 'tmu'),
        ];
    }
    
    /**
     * Get post type arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('Drama series episodes', 'tmu'),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=drama',
            'show_in_admin_bar' => false,
            'has_archive' => false,
            'rewrite' => [
                'slug' => 'drama-episode',
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
        return tmu_get_option('tmu_dramas', 'off') === 'on';
    }
}