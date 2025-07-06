<?php
/**
 * Channel Taxonomy
 *
 * @package TMU\Taxonomies
 * @version 1.0.0
 */

namespace TMU\Taxonomies;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Channel Taxonomy Class
 * 
 * TV channels for drama series
 */
class Channel extends AbstractTaxonomy {
    
    /**
     * Taxonomy slug
     *
     * @var string
     */
    protected $taxonomy = 'channel';
    
    /**
     * Object types this taxonomy applies to
     *
     * @var array
     */
    protected $object_types = ['drama'];
    
    /**
     * Whether this taxonomy is hierarchical
     *
     * @var bool
     */
    protected $hierarchical = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->meta_fields = [
            'logo_url' => [
                'type' => 'string',
                'label' => __('Logo URL', 'tmu'),
                'description' => __('Channel logo image URL', 'tmu'),
                'input_type' => 'url',
                'single' => true,
                'show_in_rest' => true,
            ],
            'website' => [
                'type' => 'string',
                'label' => __('Website', 'tmu'),
                'description' => __('Channel official website', 'tmu'),
                'input_type' => 'url',
                'single' => true,
                'show_in_rest' => true,
            ],
            'country' => [
                'type' => 'string',
                'label' => __('Country', 'tmu'),
                'description' => __('Channel country of origin', 'tmu'),
                'input_type' => 'text',
                'single' => true,
                'show_in_rest' => true,
            ],
        ];
        
        parent::__construct();
    }
    
    /**
     * Get taxonomy labels
     *
     * @return array
     */
    protected function getLabels(): array {
        return [
            'name' => __('Channels', 'tmu'),
            'singular_name' => __('Channel', 'tmu'),
            'search_items' => __('Search Channels', 'tmu'),
            'popular_items' => __('Popular Channels', 'tmu'),
            'all_items' => __('All Channels', 'tmu'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __('Edit Channel', 'tmu'),
            'update_item' => __('Update Channel', 'tmu'),
            'add_new_item' => __('Add New Channel', 'tmu'),
            'new_item_name' => __('New Channel Name', 'tmu'),
            'separate_items_with_commas' => __('Separate channels with commas', 'tmu'),
            'add_or_remove_items' => __('Add or remove channels', 'tmu'),
            'choose_from_most_used' => __('Choose from the most used channels', 'tmu'),
            'not_found' => __('No channels found.', 'tmu'),
            'menu_name' => __('Channels', 'tmu'),
        ];
    }
    
    /**
     * Get taxonomy arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('TV channels for drama series', 'tmu'),
            'rewrite' => [
                'slug' => 'channel',
                'with_front' => false,
                'hierarchical' => false,
            ],
            'show_tagcloud' => true,
            'meta_box_cb' => 'post_tags_meta_box',
        ]);
    }
    
    /**
     * Check if taxonomy should be registered
     *
     * @return bool
     */
    protected function shouldRegister(): bool {
        return tmu_get_option('tmu_dramas', 'off') === 'on';
    }
    
    /**
     * Add custom admin columns
     *
     * @param array $columns Existing columns
     * @return array
     */
    public function addAdminColumns(array $columns): array {
        $new_columns = [];
        
        foreach ($columns as $key => $title) {
            if ($key === 'description') {
                $new_columns['channel_logo'] = __('Logo', 'tmu');
                $new_columns['channel_country'] = __('Country', 'tmu');
                $new_columns['channel_dramas'] = __('Dramas', 'tmu');
            }
            
            $new_columns[$key] = $title;
        }
        
        return $new_columns;
    }
    
    /**
     * Display custom column content
     *
     * @param string $content Column content
     * @param string $column_name Column name
     * @param int $term_id Term ID
     * @return string
     */
    public function displayAdminColumnContent(string $content, string $column_name, int $term_id): string {
        switch ($column_name) {
            case 'channel_logo':
                $logo_url = get_term_meta($term_id, 'logo_url', true);
                if ($logo_url) {
                    $content = '<img src="' . esc_url($logo_url) . '" style="max-width: 50px; max-height: 30px;" alt="Channel Logo" />';
                } else {
                    $content = '—';
                }
                break;
                
            case 'channel_country':
                $country = get_term_meta($term_id, 'country', true);
                $content = $country ? esc_html($country) : '—';
                break;
                
            case 'channel_dramas':
                $dramas = get_posts([
                    'post_type' => 'drama',
                    'posts_per_page' => -1,
                    'tax_query' => [
                        [
                            'taxonomy' => 'channel',
                            'field' => 'term_id',
                            'terms' => $term_id,
                        ],
                    ],
                    'fields' => 'ids',
                ]);
                $content = count($dramas);
                break;
        }
        
        return $content;
    }
}