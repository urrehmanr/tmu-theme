<?php
/**
 * Country Taxonomy
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
 * Country Taxonomy Class
 */
class Country extends AbstractTaxonomy {
    
    /**
     * Taxonomy slug
     *
     * @var string
     */
    protected $taxonomy = 'country';
    
    /**
     * Object types this taxonomy applies to
     *
     * @var array
     */
    protected $object_types = ['movie', 'tv', 'drama', 'people'];
    
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
            'country_code' => [
                'type' => 'string',
                'label' => __('Country Code', 'tmu'),
                'description' => __('ISO 3166-1 alpha-2 country code', 'tmu'),
                'input_type' => 'text',
                'single' => true,
                'show_in_rest' => true,
            ],
            'continent' => [
                'type' => 'string',
                'label' => __('Continent', 'tmu'),
                'description' => __('Continent this country belongs to', 'tmu'),
                'input_type' => 'select',
                'options' => [
                    'africa' => __('Africa', 'tmu'),
                    'antarctica' => __('Antarctica', 'tmu'),
                    'asia' => __('Asia', 'tmu'),
                    'europe' => __('Europe', 'tmu'),
                    'north_america' => __('North America', 'tmu'),
                    'oceania' => __('Oceania', 'tmu'),
                    'south_america' => __('South America', 'tmu'),
                ],
                'single' => true,
                'show_in_rest' => true,
            ],
            'flag_emoji' => [
                'type' => 'string',
                'label' => __('Flag Emoji', 'tmu'),
                'description' => __('Unicode flag emoji for this country', 'tmu'),
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
            'name' => __('Countries', 'tmu'),
            'singular_name' => __('Country', 'tmu'),
            'search_items' => __('Search Countries', 'tmu'),
            'popular_items' => __('Popular Countries', 'tmu'),
            'all_items' => __('All Countries', 'tmu'),
            'parent_item' => __('Parent Country', 'tmu'),
            'parent_item_colon' => __('Parent Country:', 'tmu'),
            'edit_item' => __('Edit Country', 'tmu'),
            'update_item' => __('Update Country', 'tmu'),
            'add_new_item' => __('Add New Country', 'tmu'),
            'new_item_name' => __('New Country Name', 'tmu'),
            'separate_items_with_commas' => __('Separate countries with commas', 'tmu'),
            'add_or_remove_items' => __('Add or remove countries', 'tmu'),
            'choose_from_most_used' => __('Choose from the most used countries', 'tmu'),
            'not_found' => __('No countries found.', 'tmu'),
            'menu_name' => __('Countries', 'tmu'),
        ];
    }
    
    /**
     * Get taxonomy arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('Countries for movies, TV shows, dramas, and people', 'tmu'),
            'rewrite' => [
                'slug' => 'country',
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
            tmu_get_option('tmu_movies', 'off') === 'on' ||
            tmu_get_option('tmu_tv_series', 'off') === 'on' ||
            tmu_get_option('tmu_dramas', 'off') === 'on' ||
            tmu_get_option('tmu_people', 'off') === 'on'
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
                $new_columns['country_flag'] = __('Flag', 'tmu');
                $new_columns['country_code'] = __('Code', 'tmu');
                $new_columns['country_continent'] = __('Continent', 'tmu');
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
            case 'country_flag':
                $flag = get_term_meta($term_id, 'flag_emoji', true);
                $content = $flag ? '<span style="font-size: 18px;">' . esc_html($flag) . '</span>' : '—';
                break;
                
            case 'country_code':
                $code = get_term_meta($term_id, 'country_code', true);
                $content = $code ? '<code>' . esc_html(strtoupper($code)) . '</code>' : '—';
                break;
                
            case 'country_continent':
                $continent = get_term_meta($term_id, 'continent', true);
                if ($continent) {
                    $continents = [
                        'africa' => __('Africa', 'tmu'),
                        'antarctica' => __('Antarctica', 'tmu'),
                        'asia' => __('Asia', 'tmu'),
                        'europe' => __('Europe', 'tmu'),
                        'north_america' => __('North America', 'tmu'),
                        'oceania' => __('Oceania', 'tmu'),
                        'south_america' => __('South America', 'tmu'),
                    ];
                    $content = $continents[$continent] ?? ucfirst($continent);
                } else {
                    $content = '—';
                }
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
        $columns['country_code'] = 'country_code';
        $columns['country_continent'] = 'country_continent';
        return $columns;
    }
}