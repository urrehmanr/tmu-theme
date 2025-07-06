<?php
/**
 * Video Post Type
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
 * Video Post Type Class
 */
class Video extends AbstractPostType {
    
    /**
     * Post type slug
     *
     * @var string
     */
    protected $post_type = 'video';
    
    /**
     * Supported taxonomies
     *
     * @var array
     */
    protected $taxonomies = ['video-type', 'language'];
    
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
            'video_type' => [
                'type' => 'string',
                'description' => 'Video type (trailer, clip, featurette, etc.)',
                'single' => true,
            ],
            'video_url' => [
                'type' => 'string',
                'description' => 'Video URL',
                'single' => true,
            ],
            'video_key' => [
                'type' => 'string',
                'description' => 'Video key (YouTube ID, etc.)',
                'single' => true,
            ],
            'video_site' => [
                'type' => 'string',
                'description' => 'Video hosting site (YouTube, Vimeo, etc.)',
                'single' => true,
            ],
            'duration' => [
                'type' => 'integer',
                'description' => 'Video duration in seconds',
                'single' => true,
            ],
            'related_post_id' => [
                'type' => 'integer',
                'description' => 'Related content post ID',
                'single' => true,
            ],
            'related_post_type' => [
                'type' => 'string',
                'description' => 'Related content post type',
                'single' => true,
            ],
            'quality' => [
                'type' => 'string',
                'description' => 'Video quality (HD, 4K, etc.)',
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
            'name' => __('Videos', 'tmu'),
            'singular_name' => __('Video', 'tmu'),
            'add_new' => __('Add New Video', 'tmu'),
            'add_new_item' => __('Add New Video', 'tmu'),
            'edit_item' => __('Edit Video', 'tmu'),
            'new_item' => __('New Video', 'tmu'),
            'view_item' => __('View Video', 'tmu'),
            'view_items' => __('View Videos', 'tmu'),
            'search_items' => __('Search Videos', 'tmu'),
            'not_found' => __('No videos found.', 'tmu'),
            'not_found_in_trash' => __('No videos found in Trash.', 'tmu'),
            'all_items' => __('All Videos', 'tmu'),
            'archives' => __('Video Archives', 'tmu'),
            'attributes' => __('Video Attributes', 'tmu'),
            'featured_image' => __('Video Thumbnail', 'tmu'),
            'set_featured_image' => __('Set video thumbnail', 'tmu'),
            'remove_featured_image' => __('Remove video thumbnail', 'tmu'),
            'use_featured_image' => __('Use as video thumbnail', 'tmu'),
            'menu_name' => __('Videos', 'tmu'),
        ];
    }
    
    /**
     * Get post type arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('Video content management system', 'tmu'),
            'menu_position' => 8,
            'menu_icon' => 'dashicons-video-alt2',
            'has_archive' => 'videos',
            'rewrite' => [
                'slug' => 'video',
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
        return tmu_get_option('tmu_videos', 'off') === 'on';
    }
}