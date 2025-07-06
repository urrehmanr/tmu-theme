<?php
/**
 * Language Taxonomy
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
 * Language Taxonomy Class
 */
class Language extends AbstractTaxonomy {
    
    /**
     * Taxonomy slug
     *
     * @var string
     */
    protected $taxonomy = 'language';
    
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
    protected $hierarchical = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->meta_fields = [
            'language_code' => [
                'type' => 'string',
                'label' => __('Language Code', 'tmu'),
                'description' => __('ISO 639-1 language code', 'tmu'),
                'input_type' => 'text',
                'single' => true,
                'show_in_rest' => true,
            ],
            'native_name' => [
                'type' => 'string',
                'label' => __('Native Name', 'tmu'),
                'description' => __('Language name in its native script', 'tmu'),
                'input_type' => 'text',
                'single' => true,
                'show_in_rest' => true,
            ],
            'rtl' => [
                'type' => 'boolean',
                'label' => __('Right to Left', 'tmu'),
                'description' => __('Check if this language is written right-to-left', 'tmu'),
                'input_type' => 'checkbox',
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
            'name' => __('Languages', 'tmu'),
            'singular_name' => __('Language', 'tmu'),
            'search_items' => __('Search Languages', 'tmu'),
            'popular_items' => __('Popular Languages', 'tmu'),
            'all_items' => __('All Languages', 'tmu'),
            'edit_item' => __('Edit Language', 'tmu'),
            'update_item' => __('Update Language', 'tmu'),
            'add_new_item' => __('Add New Language', 'tmu'),
            'new_item_name' => __('New Language Name', 'tmu'),
            'separate_items_with_commas' => __('Separate languages with commas', 'tmu'),
            'add_or_remove_items' => __('Add or remove languages', 'tmu'),
            'choose_from_most_used' => __('Choose from the most used languages', 'tmu'),
            'not_found' => __('No languages found.', 'tmu'),
            'menu_name' => __('Languages', 'tmu'),
        ];
    }
    
    /**
     * Get taxonomy arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('Languages for movies, TV shows, and dramas', 'tmu'),
            'rewrite' => [
                'slug' => 'language',
                'with_front' => false,
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
                $new_columns['language_code'] = __('Code', 'tmu');
                $new_columns['language_native'] = __('Native Name', 'tmu');
                $new_columns['language_rtl'] = __('RTL', 'tmu');
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
            case 'language_code':
                $code = get_term_meta($term_id, 'language_code', true);
                $content = $code ? '<code>' . esc_html(strtoupper($code)) . '</code>' : '—';
                break;
                
            case 'language_native':
                $native = get_term_meta($term_id, 'native_name', true);
                $content = $native ? esc_html($native) : '—';
                break;
                
            case 'language_rtl':
                $rtl = get_term_meta($term_id, 'rtl', true);
                $content = $rtl ? '<span class="dashicons dashicons-arrow-left-alt"></span>' : '<span class="dashicons dashicons-arrow-right-alt"></span>';
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
        $columns['language_code'] = 'language_code';
        $columns['language_native'] = 'language_native';
        return $columns;
    }
}