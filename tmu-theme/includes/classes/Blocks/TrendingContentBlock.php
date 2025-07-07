<?php
/**
 * Trending Content Block
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

namespace TMU\Blocks;

class TrendingContentBlock extends BaseBlock {
    
    protected $name = 'trending-content';
    protected $title = 'Trending Content';
    protected $description = 'Display trending movies, TV shows, and dramas';
    protected $icon = 'chart-line';
    protected $post_type_restricted = false;
    
    public static function get_attributes(): array {
        return [
            'content_type' => [
                'type' => 'string',
                'default' => 'all',
                'enum' => ['all', 'movies', 'tv', 'dramas'],
            ],
            'count' => [
                'type' => 'number',
                'default' => 10,
            ],
            'time_period' => [
                'type' => 'string',
                'default' => 'week',
                'enum' => ['day', 'week', 'month', 'year'],
            ],
        ];
    }
    
    public static function render($attributes, $content): string {
        return '<div class="tmu-trending-content">Trending Content Block</div>';
    }
}