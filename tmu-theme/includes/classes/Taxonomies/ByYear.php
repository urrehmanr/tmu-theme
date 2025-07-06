<?php
/**
 * By Year Taxonomy
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
 * By Year Taxonomy Class
 */
class ByYear extends AbstractTaxonomy {
    
    /**
     * Taxonomy slug
     *
     * @var string
     */
    protected $taxonomy = 'by-year';
    
    /**
     * Object types this taxonomy applies to
     *
     * @var array
     */
    protected $object_types = ['movie', 'tv', 'drama'];
    
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
            'decade' => [
                'type' => 'string',
                'label' => __('Decade', 'tmu'),
                'description' => __('Decade this year belongs to (e.g., 1990s, 2000s)', 'tmu'),
                'input_type' => 'text',
                'single' => true,
                'show_in_rest' => true,
            ],
            'century' => [
                'type' => 'string',
                'label' => __('Century', 'tmu'),
                'description' => __('Century this year belongs to (e.g., 20th, 21st)', 'tmu'),
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
            'name' => __('Years', 'tmu'),
            'singular_name' => __('Year', 'tmu'),
            'search_items' => __('Search Years', 'tmu'),
            'popular_items' => __('Popular Years', 'tmu'),
            'all_items' => __('All Years', 'tmu'),
            'parent_item' => __('Parent Year', 'tmu'),
            'parent_item_colon' => __('Parent Year:', 'tmu'),
            'edit_item' => __('Edit Year', 'tmu'),
            'update_item' => __('Update Year', 'tmu'),
            'add_new_item' => __('Add New Year', 'tmu'),
            'new_item_name' => __('New Year Name', 'tmu'),
            'separate_items_with_commas' => __('Separate years with commas', 'tmu'),
            'add_or_remove_items' => __('Add or remove years', 'tmu'),
            'choose_from_most_used' => __('Choose from the most used years', 'tmu'),
            'not_found' => __('No years found.', 'tmu'),
            'menu_name' => __('Years', 'tmu'),
        ];
    }
    
    /**
     * Get taxonomy arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('Release years for movies, TV shows, and dramas', 'tmu'),
            'rewrite' => [
                'slug' => 'year',
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
                $new_columns['year_decade'] = __('Decade', 'tmu');
                $new_columns['year_century'] = __('Century', 'tmu');
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
            case 'year_decade':
                $decade = get_term_meta($term_id, 'decade', true);
                $content = $decade ? esc_html($decade) : $this->calculateDecade($term_id);
                break;
                
            case 'year_century':
                $century = get_term_meta($term_id, 'century', true);
                $content = $century ? esc_html($century) : $this->calculateCentury($term_id);
                break;
        }
        
        return $content;
    }
    
    /**
     * Calculate decade from year
     *
     * @param int $term_id Term ID
     * @return string
     */
    private function calculateDecade(int $term_id): string {
        $term = get_term($term_id);
        if (!$term || is_wp_error($term)) {
            return '—';
        }
        
        $year = intval($term->name);
        if ($year < 1000 || $year > 9999) {
            return '—';
        }
        
        $decade_start = floor($year / 10) * 10;
        return $decade_start . 's';
    }
    
    /**
     * Calculate century from year
     *
     * @param int $term_id Term ID
     * @return string
     */
    private function calculateCentury(int $term_id): string {
        $term = get_term($term_id);
        if (!$term || is_wp_error($term)) {
            return '—';
        }
        
        $year = intval($term->name);
        if ($year < 1000 || $year > 9999) {
            return '—';
        }
        
        $century = ceil($year / 100);
        $suffix = $this->getOrdinalSuffix($century);
        return $century . $suffix;
    }
    
    /**
     * Get ordinal suffix for number
     *
     * @param int $number Number
     * @return string
     */
    private function getOrdinalSuffix(int $number): string {
        if ($number % 100 >= 11 && $number % 100 <= 13) {
            return 'th';
        }
        
        switch ($number % 10) {
            case 1:
                return 'st';
            case 2:
                return 'nd';
            case 3:
                return 'rd';
            default:
                return 'th';
        }
    }
    
    /**
     * Auto-generate year terms
     *
     * @param int $start_year Starting year
     * @param int $end_year Ending year
     */
    public function generateYearTerms(int $start_year = 1900, int $end_year = null): void {
        if ($end_year === null) {
            $end_year = intval(date('Y')) + 5;
        }
        
        for ($year = $start_year; $year <= $end_year; $year++) {
            if (!term_exists($year, 'by-year')) {
                $term = wp_insert_term($year, 'by-year');
                
                if (!is_wp_error($term)) {
                    $term_id = $term['term_id'];
                    
                    // Auto-calculate and save decade and century
                    $decade_start = floor($year / 10) * 10;
                    $decade = $decade_start . 's';
                    update_term_meta($term_id, 'decade', $decade);
                    
                    $century = ceil($year / 100);
                    $suffix = $this->getOrdinalSuffix($century);
                    update_term_meta($term_id, 'century', $century . $suffix);
                }
            }
        }
    }
}