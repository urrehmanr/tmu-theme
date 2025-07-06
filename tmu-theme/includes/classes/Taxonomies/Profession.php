<?php
/**
 * Profession Taxonomy
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
 * Profession Taxonomy Class
 */
class Profession extends AbstractTaxonomy {
    
    /**
     * Taxonomy slug
     *
     * @var string
     */
    protected $taxonomy = 'profession';
    
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
    protected $hierarchical = true;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->meta_fields = [
            'department' => [
                'type' => 'string',
                'label' => __('Department', 'tmu'),
                'description' => __('Department this profession belongs to', 'tmu'),
                'input_type' => 'select',
                'options' => [
                    'acting' => __('Acting', 'tmu'),
                    'directing' => __('Directing', 'tmu'),
                    'writing' => __('Writing', 'tmu'),
                    'production' => __('Production', 'tmu'),
                    'cinematography' => __('Cinematography', 'tmu'),
                    'editing' => __('Editing', 'tmu'),
                    'sound' => __('Sound', 'tmu'),
                    'art' => __('Art', 'tmu'),
                    'costume_makeup' => __('Costume & Makeup', 'tmu'),
                    'visual_effects' => __('Visual Effects', 'tmu'),
                    'crew' => __('Crew', 'tmu'),
                    'other' => __('Other', 'tmu'),
                ],
                'single' => true,
                'show_in_rest' => true,
            ],
            'icon' => [
                'type' => 'string',
                'label' => __('Icon', 'tmu'),
                'description' => __('Dashicon class for this profession', 'tmu'),
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
            'name' => __('Professions', 'tmu'),
            'singular_name' => __('Profession', 'tmu'),
            'search_items' => __('Search Professions', 'tmu'),
            'popular_items' => __('Popular Professions', 'tmu'),
            'all_items' => __('All Professions', 'tmu'),
            'parent_item' => __('Parent Profession', 'tmu'),
            'parent_item_colon' => __('Parent Profession:', 'tmu'),
            'edit_item' => __('Edit Profession', 'tmu'),
            'update_item' => __('Update Profession', 'tmu'),
            'add_new_item' => __('Add New Profession', 'tmu'),
            'new_item_name' => __('New Profession Name', 'tmu'),
            'separate_items_with_commas' => __('Separate professions with commas', 'tmu'),
            'add_or_remove_items' => __('Add or remove professions', 'tmu'),
            'choose_from_most_used' => __('Choose from the most used professions', 'tmu'),
            'not_found' => __('No professions found.', 'tmu'),
            'menu_name' => __('Professions', 'tmu'),
        ];
    }
    
    /**
     * Get taxonomy arguments
     *
     * @return array
     */
    protected function getArgs(): array {
        return array_merge($this->getDefaultArgs(), [
            'description' => __('Professions for people in the entertainment industry', 'tmu'),
            'rewrite' => [
                'slug' => 'profession',
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
        // People are always enabled, so professions should always be available
        return true;
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
                $new_columns['profession_icon'] = __('Icon', 'tmu');
                $new_columns['profession_department'] = __('Department', 'tmu');
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
            case 'profession_icon':
                $icon = get_term_meta($term_id, 'icon', true);
                if ($icon) {
                    $content = '<span class="dashicons ' . esc_attr($icon) . '"></span>';
                } else {
                    $content = '<span class="dashicons dashicons-admin-users"></span>';
                }
                break;
                
            case 'profession_department':
                $department = get_term_meta($term_id, 'department', true);
                if ($department) {
                    $departments = [
                        'acting' => __('Acting', 'tmu'),
                        'directing' => __('Directing', 'tmu'),
                        'writing' => __('Writing', 'tmu'),
                        'production' => __('Production', 'tmu'),
                        'cinematography' => __('Cinematography', 'tmu'),
                        'editing' => __('Editing', 'tmu'),
                        'sound' => __('Sound', 'tmu'),
                        'art' => __('Art', 'tmu'),
                        'costume_makeup' => __('Costume & Makeup', 'tmu'),
                        'visual_effects' => __('Visual Effects', 'tmu'),
                        'crew' => __('Crew', 'tmu'),
                        'other' => __('Other', 'tmu'),
                    ];
                    $content = $departments[$department] ?? ucfirst($department);
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
        $columns['profession_department'] = 'profession_department';
        return $columns;
    }
}