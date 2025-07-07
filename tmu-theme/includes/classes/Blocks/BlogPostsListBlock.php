<?php
/**
 * Blog Posts List Block
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

namespace TMU\Blocks;

class BlogPostsListBlock extends BaseBlock {
    
    protected $name = 'blog-posts-list';
    protected $title = 'Blog Posts List';
    protected $description = 'Display a list of blog posts';
    protected $icon = 'list-view';
    protected $post_type_restricted = false;
    
    public static function get_attributes(): array {
        return [
            'post_count' => [
                'type' => 'number',
                'default' => 5,
            ],
            'category' => [
                'type' => 'string',
                'default' => '',
            ],
            'show_excerpt' => [
                'type' => 'boolean',
                'default' => true,
            ],
        ];
    }
    
    public static function render($attributes, $content): string {
        return '<div class="tmu-blog-posts-list">Blog Posts List Block</div>';
    }
}