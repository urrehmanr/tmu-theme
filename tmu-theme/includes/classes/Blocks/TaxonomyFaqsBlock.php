<?php
/**
 * Taxonomy FAQs Block
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

namespace TMU\Blocks;

class TaxonomyFaqsBlock extends BaseBlock {
    
    protected $name = 'taxonomy-faqs';
    protected $title = 'Taxonomy FAQs';
    protected $description = 'Display FAQs for taxonomy terms';
    protected $icon = 'editor-help';
    protected $post_type_restricted = false;
    
    public static function get_attributes(): array {
        return [
            'taxonomy' => [
                'type' => 'string',
                'default' => '',
            ],
            'term_id' => [
                'type' => 'number',
                'default' => null,
            ],
            'faqs' => [
                'type' => 'array',
                'default' => [],
            ],
        ];
    }
    
    public static function render($attributes, $content): string {
        return '<div class="tmu-taxonomy-faqs">FAQs Block</div>';
    }
}