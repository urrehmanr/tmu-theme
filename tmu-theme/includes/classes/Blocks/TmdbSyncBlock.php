<?php
/**
 * TMDB Sync Block
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

namespace TMU\Blocks;

class TmdbSyncBlock extends BaseBlock {
    
    protected $name = 'tmdb-sync';
    protected $title = 'TMDB Sync';
    protected $description = 'Sync content with TMDB database';
    protected $icon = 'update';
    protected $post_types = ['movie', 'tv', 'drama', 'people'];
    
    public static function get_attributes(): array {
        return [
            'tmdb_id' => [
                'type' => 'number',
                'default' => null,
            ],
            'content_type' => [
                'type' => 'string',
                'default' => 'movie',
                'enum' => ['movie', 'tv', 'person'],
            ],
            'auto_sync' => [
                'type' => 'boolean',
                'default' => false,
            ],
            'last_sync' => [
                'type' => 'string',
                'default' => '',
            ],
        ];
    }
    
    public static function render($attributes, $content): string {
        return '<div class="tmu-tmdb-sync">TMDB Sync Block</div>';
    }
}