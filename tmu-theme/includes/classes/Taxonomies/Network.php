<?php
/**
 * Network Taxonomy
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
 * Network Taxonomy Class
 */
class Network extends AbstractTaxonomy {
    
    /**
     * Taxonomy slug
     *
     * @var string
     */
    protected $taxonomy = 'network';
    
    /**
     * Object types this taxonomy applies to
     *
     * @var array
     */
    protected $object_types = ['tv', 'drama'];
    
    /**
     * Whether this taxonomy is hierarchical
     *
     * @var bool
     */
    protected $hierarchical = true;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->meta_fields = [
            'logo_url' => [
                'type' => 'string',
                'label' => __('Logo URL', 'tmu'),
                'description' => __('URL to the network logo', 'tmu'),
                'input_type' => 'url',
                'single' => true,
                'show_in_rest' => true,
            ],
            'network_type' => [
                'type' => 'string',
                'label' => __('Network Type', 'tmu'),
                'description' => __('Type of network or platform', 'tmu'),
                'input_type' => 'select',
                'options' => [
                    'broadcast' => __('Broadcast TV', 'tmu'),
                    'cable' => __('Cable TV', 'tmu'),
                    'streaming' => __('Streaming Service', 'tmu'),
                    'premium' => __('Premium Channel', 'tmu'),
                    'syndication' => __('Syndication', 'tmu'),
                    'web' => __('Web Series', 'tmu'),
                    'other' => __('Other', 'tmu'),
                ],
                'single' => true,
                'show_in_rest' => true,
            ],
            'country' => [
                'type' => 'string',
                'label' => __('Country', 'tmu'),
                'description' => __('Country of origin', 'tmu'),
                'input_type' => 'text',
                'single' => true,
                'show_in_rest' => true,
            ],
            'website' => [
                'type' => 'string',
                'label' => __('Website', 'tmu'),
                'description' => __('Official website URL', 'tmu'),
                'input_type' => 'url',
                'single' => true,
                'show_in_rest' => true,
            ],
            'tmdb_id' => [
                'type' => 'integer',
                'label' => __('TMDB ID', 'tmu'),
                'description' => __('The Movie Database network ID', 'tmu'),
                'input_type' => 'number',
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
            'name' => __('Networks', 'tmu'),
            'singular_name' => __('Network', 'tmu'),
            'search_items' => __('Search Networks', 'tmu'),
            'popular_items' => __('Popular Networks', 'tmu'),
            'all_items' => __('All Networks', 'tmu'),
            'parent_item' => __('Parent Network', 'tmu'),
            'parent_item_colon' => __('Parent Network:', 'tmu'),
            'edit_item' => __('Edit Network', 'tmu'),
            'update_item' => __('Update Network', 'tmu'),
            'add_new_item' => __('Add New Network', 'tmu'),
            'new_item_name' => __('New Network Name', 'tmu'),
            'separate_items_with_commas' => __('Separate networks with commas', 'tmu'),
            'add_or_remove_items' => __('Add or remove networks', 'tmu'),
            'choose_from_most_used' => __('Choose from the most used networks', 'tmu'),
            'not_found' => __('No networks found.', 'tmu'),
            'menu_name' => __('Networks', 'tmu'),
        ];
    }
    
    /**
     * Get taxonomy arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('TV networks and streaming platforms', 'tmu'),
            'rewrite' => [
                'slug' => 'network',
                'with_front' => false,
                'hierarchical' => true,
            ],
            'show_tagcloud' => true,
            'sort' => true,
        ]);
    }
    
    /**
     * Check if taxonomy should be registered
     *
     * @return bool
     */
    protected function shouldRegister(): bool {
        return (
            tmu_get_option('tmu_tv_series', 'off') === 'on' ||
            tmu_get_option('tmu_dramas', 'off') === 'on'
        );
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
                $new_columns['network_logo'] = __('Logo', 'tmu');
                $new_columns['network_type'] = __('Type', 'tmu');
                $new_columns['network_country'] = __('Country', 'tmu');
                $new_columns['network_tmdb_id'] = __('TMDB ID', 'tmu');
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
            case 'network_logo':
                $logo_url = get_term_meta($term_id, 'logo_url', true);
                if ($logo_url) {
                    $content = '<img src="' . esc_url($logo_url) . '" alt="Logo" style="max-width: 50px; max-height: 30px;" />';
                } else {
                    $content = '<span class="dashicons dashicons-networking"></span>';
                }
                break;
                
            case 'network_type':
                $type = get_term_meta($term_id, 'network_type', true);
                if ($type) {
                    $types = [
                        'broadcast' => __('Broadcast TV', 'tmu'),
                        'cable' => __('Cable TV', 'tmu'),
                        'streaming' => __('Streaming Service', 'tmu'),
                        'premium' => __('Premium Channel', 'tmu'),
                        'syndication' => __('Syndication', 'tmu'),
                        'web' => __('Web Series', 'tmu'),
                        'other' => __('Other', 'tmu'),
                    ];
                    $content = $types[$type] ?? ucfirst($type);
                } else {
                    $content = '—';
                }
                break;
                
            case 'network_country':
                $country = get_term_meta($term_id, 'country', true);
                $content = $country ? esc_html($country) : '—';
                break;
                
            case 'network_tmdb_id':
                $tmdb_id = get_term_meta($term_id, 'tmdb_id', true);
                $content = $tmdb_id ? '<code>' . esc_html($tmdb_id) . '</code>' : '—';
                break;
        }
        
        return $content;
    }
    
    /**
     * Make columns sortable
     *
     * @param array $columns Sortable columns
     * @return array
     */
    public function makeSortableColumns(array $columns): array {
        $columns['network_type'] = 'network_type';
        $columns['network_country'] = 'network_country';
        $columns['network_tmdb_id'] = 'network_tmdb_id';
        return $columns;
    }
}