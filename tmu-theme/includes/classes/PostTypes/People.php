<?php
/**
 * People Post Type
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
 * People Post Type Class
 */
class People extends AbstractPostType {
    
    /**
     * Post type slug
     *
     * @var string
     */
    protected $post_type = 'people';
    
    /**
     * Supported taxonomies
     *
     * @var array
     */
    protected $taxonomies = ['profession', 'country'];
    
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
            'date_of_birth' => [
                'type' => 'string',
                'description' => 'Date of birth',
                'single' => true,
            ],
            'date_of_death' => [
                'type' => 'string',
                'description' => 'Date of death (if applicable)',
                'single' => true,
            ],
            'place_of_birth' => [
                'type' => 'string',
                'description' => 'Place of birth',
                'single' => true,
            ],
            'biography' => [
                'type' => 'string',
                'description' => 'Biography',
                'single' => true,
            ],
            'known_for_department' => [
                'type' => 'string',
                'description' => 'Department known for (Acting, Directing, etc.)',
                'single' => true,
            ],
            'gender' => [
                'type' => 'integer',
                'description' => 'Gender (0=not specified, 1=female, 2=male, 3=non-binary)',
                'single' => true,
            ],
            'profile_path' => [
                'type' => 'string',
                'description' => 'Profile image path',
                'single' => true,
            ],
            'imdb_id' => [
                'type' => 'string',
                'description' => 'IMDb ID',
                'single' => true,
            ],
            'also_known_as' => [
                'type' => 'string',
                'description' => 'Alternative names (JSON array)',
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
            'name' => __('People', 'tmu'),
            'singular_name' => __('Person', 'tmu'),
            'add_new' => __('Add New Person', 'tmu'),
            'add_new_item' => __('Add New Person', 'tmu'),
            'edit_item' => __('Edit Person', 'tmu'),
            'new_item' => __('New Person', 'tmu'),
            'view_item' => __('View Person', 'tmu'),
            'view_items' => __('View People', 'tmu'),
            'search_items' => __('Search People', 'tmu'),
            'not_found' => __('No people found.', 'tmu'),
            'not_found_in_trash' => __('No people found in Trash.', 'tmu'),
            'all_items' => __('All People', 'tmu'),
            'archives' => __('People Archives', 'tmu'),
            'attributes' => __('Person Attributes', 'tmu'),
            'featured_image' => __('Profile Photo', 'tmu'),
            'set_featured_image' => __('Set profile photo', 'tmu'),
            'remove_featured_image' => __('Remove profile photo', 'tmu'),
            'use_featured_image' => __('Use as profile photo', 'tmu'),
            'menu_name' => __('People', 'tmu'),
        ];
    }
    
    /**
     * Get post type arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('People database for cast and crew', 'tmu'),
            'menu_position' => 7,
            'menu_icon' => 'dashicons-groups',
            'has_archive' => 'people',
            'rewrite' => [
                'slug' => 'person',
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
        // People are always enabled as they're needed for cast/crew
        return true;
    }
}