<?php
/**
 * Taxonomy Image Block
 * 
 * Handles taxonomy image display and management
 * 
 * @package TMU\Blocks
 * @since 1.0.0
 */

namespace TMU\Blocks;

/**
 * TaxonomyImageBlock class
 */
class TaxonomyImageBlock extends BaseBlock {
    
    protected $name = 'taxonomy-image';
    protected $title = 'Taxonomy Image';
    protected $description = 'Display taxonomy images';
    protected $icon = 'format-image';
    protected $keywords = ['taxonomy', 'image', 'category'];
    protected $post_types = [];
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
            'image_url' => [
                'type' => 'string',
                'default' => '',
            ],
            'image_alt' => [
                'type' => 'string',
                'default' => '',
            ],
            'display_name' => [
                'type' => 'boolean',
                'default' => true,
            ],
        ];
    }
    
    public static function render($attributes, $content): string {
        $attributes = self::validate_attributes($attributes);
        
        if (empty($attributes['image_url'])) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="tmu-taxonomy-image">
            <img src="<?php echo esc_url($attributes['image_url']); ?>" 
                 alt="<?php echo esc_attr($attributes['image_alt']); ?>" />
            <?php if ($attributes['display_name'] && $attributes['term_id']): ?>
                <?php $term = get_term($attributes['term_id']); ?>
                <?php if ($term && !is_wp_error($term)): ?>
                <div class="taxonomy-name">
                    <?php echo esc_html($term->name); ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}