<?php
/**
 * Nationality Taxonomy
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
 * Nationality Taxonomy Class
 * 
 * People nationalities for cast and crew
 */
class Nationality extends AbstractTaxonomy {
    
    /**
     * Taxonomy slug
     *
     * @var string
     */
    protected $taxonomy = 'nationality';
    
    /**
     * Object types this taxonomy applies to
     *
     * @var array
     */
    protected $object_types = ['people'];
    
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
            'flag_emoji' => [
                'type' => 'string',
                'label' => __('Flag Emoji', 'tmu'),
                'description' => __('Flag emoji for this nationality', 'tmu'),
                'input_type' => 'text',
                'single' => true,
                'show_in_rest' => true,
            ],
            'iso_code' => [
                'type' => 'string',
                'label' => __('ISO Code', 'tmu'),
                'description' => __('Two-letter country ISO code', 'tmu'),
                'input_type' => 'text',
                'single' => true,
                'show_in_rest' => true,
            ],
            'continent' => [
                'type' => 'string',
                'label' => __('Continent', 'tmu'),
                'description' => __('Continent this nationality belongs to', 'tmu'),
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
            'name' => __('Nationalities', 'tmu'),
            'singular_name' => __('Nationality', 'tmu'),
            'search_items' => __('Search Nationalities', 'tmu'),
            'popular_items' => __('Popular Nationalities', 'tmu'),
            'all_items' => __('All Nationalities', 'tmu'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => __('Edit Nationality', 'tmu'),
            'update_item' => __('Update Nationality', 'tmu'),
            'add_new_item' => __('Add New Nationality', 'tmu'),
            'new_item_name' => __('New Nationality Name', 'tmu'),
            'separate_items_with_commas' => __('Separate nationalities with commas', 'tmu'),
            'add_or_remove_items' => __('Add or remove nationalities', 'tmu'),
            'choose_from_most_used' => __('Choose from the most used nationalities', 'tmu'),
            'not_found' => __('No nationalities found.', 'tmu'),
            'menu_name' => __('Nationalities', 'tmu'),
        ];
    }
    
    /**
     * Get taxonomy arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('People nationalities for cast and crew members', 'tmu'),
            'rewrite' => [
                'slug' => 'nationality',
                'with_front' => false,
                'hierarchical' => false,
            ],
            'show_tagcloud' => true,
            'show_admin_column' => true,
            'meta_box_cb' => 'post_tags_meta_box',
        ]);
    }
    
    /**
     * Check if taxonomy should be registered
     *
     * @return bool
     */
    protected function shouldRegister(): bool {
        // Always register since people post type is always enabled
        return post_type_exists('people');
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
                $new_columns['nationality_flag'] = __('Flag', 'tmu');
                $new_columns['nationality_continent'] = __('Continent', 'tmu');
                $new_columns['nationality_people'] = __('People', 'tmu');
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
            case 'nationality_flag':
                $flag = get_term_meta($term_id, 'flag_emoji', true);
                $iso_code = get_term_meta($term_id, 'iso_code', true);
                if ($flag) {
                    $content = '<span style="font-size: 20px;">' . esc_html($flag) . '</span>';
                } elseif ($iso_code) {
                    $content = '<code>' . esc_html(strtoupper($iso_code)) . '</code>';
                } else {
                    $content = '—';
                }
                break;
                
            case 'nationality_continent':
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
                    $content = $continents[$continent] ?? esc_html($continent);
                } else {
                    $content = '—';
                }
                break;
                
            case 'nationality_people':
                $people = get_posts([
                    'post_type' => 'people',
                    'posts_per_page' => -1,
                    'tax_query' => [
                        [
                            'taxonomy' => 'nationality',
                            'field' => 'term_id',
                            'terms' => $term_id,
                        ],
                    ],
                    'fields' => 'ids',
                ]);
                $content = count($people);
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
        $columns['nationality_continent'] = 'nationality_continent';
        $columns['nationality_people'] = 'nationality_people';
        return $columns;
    }
    
    /**
     * Add hooks after registration
     */
    protected function addHooks(): void {
        parent::addHooks();
        
        // Seed popular nationalities after registration
        add_action('admin_init', [$this, 'seedPopularNationalities']);
    }
    
    /**
     * Seed popular nationalities
     */
    public function seedPopularNationalities(): void {
        if (get_option('tmu_nationalities_seeded') === '1') {
            return;
        }
        
        $nationalities = [
            ['name' => 'American', 'flag' => '🇺🇸', 'iso' => 'US', 'continent' => 'north_america'],
            ['name' => 'British', 'flag' => '🇬🇧', 'iso' => 'GB', 'continent' => 'europe'],
            ['name' => 'Canadian', 'flag' => '🇨🇦', 'iso' => 'CA', 'continent' => 'north_america'],
            ['name' => 'Australian', 'flag' => '🇦🇺', 'iso' => 'AU', 'continent' => 'oceania'],
            ['name' => 'French', 'flag' => '🇫🇷', 'iso' => 'FR', 'continent' => 'europe'],
            ['name' => 'German', 'flag' => '🇩🇪', 'iso' => 'DE', 'continent' => 'europe'],
            ['name' => 'Italian', 'flag' => '🇮🇹', 'iso' => 'IT', 'continent' => 'europe'],
            ['name' => 'Spanish', 'flag' => '🇪🇸', 'iso' => 'ES', 'continent' => 'europe'],
            ['name' => 'Japanese', 'flag' => '🇯🇵', 'iso' => 'JP', 'continent' => 'asia'],
            ['name' => 'South Korean', 'flag' => '🇰🇷', 'iso' => 'KR', 'continent' => 'asia'],
            ['name' => 'Chinese', 'flag' => '🇨🇳', 'iso' => 'CN', 'continent' => 'asia'],
            ['name' => 'Indian', 'flag' => '🇮🇳', 'iso' => 'IN', 'continent' => 'asia'],
            ['name' => 'Brazilian', 'flag' => '🇧🇷', 'iso' => 'BR', 'continent' => 'south_america'],
            ['name' => 'Mexican', 'flag' => '🇲🇽', 'iso' => 'MX', 'continent' => 'north_america'],
            ['name' => 'Russian', 'flag' => '🇷🇺', 'iso' => 'RU', 'continent' => 'europe'],
        ];
        
        foreach ($nationalities as $nationality_data) {
            if (!term_exists($nationality_data['name'], 'nationality')) {
                $term = wp_insert_term($nationality_data['name'], 'nationality');
                
                if (!is_wp_error($term)) {
                    update_term_meta($term['term_id'], 'flag_emoji', $nationality_data['flag']);
                    update_term_meta($term['term_id'], 'iso_code', $nationality_data['iso']);
                    update_term_meta($term['term_id'], 'continent', $nationality_data['continent']);
                }
            }
        }
        
        update_option('tmu_nationalities_seeded', '1');
    }
}