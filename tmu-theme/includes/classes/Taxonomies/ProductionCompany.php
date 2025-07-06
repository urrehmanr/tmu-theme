<?php
/**
 * Production Company Taxonomy
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
 * Production Company Taxonomy Class
 */
class ProductionCompany extends AbstractTaxonomy {
    
    /**
     * Taxonomy slug
     *
     * @var string
     */
    protected $taxonomy = 'production-company';
    
    /**
     * Object types this taxonomy applies to
     *
     * @var array
     */
    protected $object_types = ['movie'];
    
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
                'description' => __('URL to the company logo', 'tmu'),
                'input_type' => 'url',
                'single' => true,
                'show_in_rest' => true,
            ],
            'founded_year' => [
                'type' => 'integer',
                'label' => __('Founded Year', 'tmu'),
                'description' => __('Year the company was founded', 'tmu'),
                'input_type' => 'number',
                'single' => true,
                'show_in_rest' => true,
            ],
            'headquarters' => [
                'type' => 'string',
                'label' => __('Headquarters', 'tmu'),
                'description' => __('Location of company headquarters', 'tmu'),
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
                'description' => __('The Movie Database company ID', 'tmu'),
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
            'name' => __('Production Companies', 'tmu'),
            'singular_name' => __('Production Company', 'tmu'),
            'search_items' => __('Search Production Companies', 'tmu'),
            'popular_items' => __('Popular Production Companies', 'tmu'),
            'all_items' => __('All Production Companies', 'tmu'),
            'parent_item' => __('Parent Company', 'tmu'),
            'parent_item_colon' => __('Parent Company:', 'tmu'),
            'edit_item' => __('Edit Production Company', 'tmu'),
            'update_item' => __('Update Production Company', 'tmu'),
            'add_new_item' => __('Add New Production Company', 'tmu'),
            'new_item_name' => __('New Production Company Name', 'tmu'),
            'separate_items_with_commas' => __('Separate production companies with commas', 'tmu'),
            'add_or_remove_items' => __('Add or remove production companies', 'tmu'),
            'choose_from_most_used' => __('Choose from the most used production companies', 'tmu'),
            'not_found' => __('No production companies found.', 'tmu'),
            'menu_name' => __('Production Companies', 'tmu'),
        ];
    }
    
    /**
     * Get taxonomy arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('Production companies for movies', 'tmu'),
            'rewrite' => [
                'slug' => 'production-company',
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
        return tmu_get_option('tmu_movies', 'off') === 'on';
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
                $new_columns['company_logo'] = __('Logo', 'tmu');
                $new_columns['company_founded'] = __('Founded', 'tmu');
                $new_columns['company_headquarters'] = __('Headquarters', 'tmu');
                $new_columns['company_tmdb_id'] = __('TMDB ID', 'tmu');
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
            case 'company_logo':
                $logo_url = get_term_meta($term_id, 'logo_url', true);
                if ($logo_url) {
                    $content = '<img src="' . esc_url($logo_url) . '" alt="Logo" style="max-width: 50px; max-height: 30px;" />';
                } else {
                    $content = '<span class="dashicons dashicons-building"></span>';
                }
                break;
                
            case 'company_founded':
                $founded = get_term_meta($term_id, 'founded_year', true);
                $content = $founded ? esc_html($founded) : '—';
                break;
                
            case 'company_headquarters':
                $headquarters = get_term_meta($term_id, 'headquarters', true);
                $content = $headquarters ? esc_html($headquarters) : '—';
                break;
                
            case 'company_tmdb_id':
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
        $columns['company_founded'] = 'company_founded';
        $columns['company_headquarters'] = 'company_headquarters';
        $columns['company_tmdb_id'] = 'company_tmdb_id';
        return $columns;
    }
}