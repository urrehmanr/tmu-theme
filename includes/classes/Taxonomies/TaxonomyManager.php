<?php
/**
 * TMU Taxonomy Manager
 *
 * @package TMU
 * @version 1.0.0
 */

namespace TMU\Taxonomies;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Taxonomy Manager Class
 * 
 * Manages registration of all custom taxonomies
 */
class TaxonomyManager {
    
    /**
     * Manager instance
     *
     * @var TaxonomyManager
     */
    private static ?TaxonomyManager $instance = null;
    
    /**
     * Taxonomies configuration
     *
     * @var array
     */
    private array $taxonomies = [];
    
    /**
     * Get manager instance
     *
     * @return TaxonomyManager
     */
    public static function getInstance(): TaxonomyManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        $this->taxonomies = tmu_config('taxonomies') ?? [];
    }
    
    /**
     * Initialize taxonomy manager
     */
    public function init(): void {
        add_action('init', [$this, 'registerTaxonomies']);
    }
    
    /**
     * Register all taxonomies
     */
    public function registerTaxonomies(): void {
        foreach ($this->taxonomies as $taxonomy => $config) {
            if ($config['enabled']) {
                $this->registerTaxonomy($taxonomy, $config);
            }
        }
    }
    
    /**
     * Register individual taxonomy
     */
    private function registerTaxonomy(string $taxonomy, array $config): void {
        $labels = $this->getLabels($taxonomy, $config);
        $args = $this->getArgs($taxonomy, $config);
        
        register_taxonomy($taxonomy, $config['post_types'], array_merge($args, [
            'labels' => $labels
        ]));
        
        tmu_log("Registered taxonomy: {$taxonomy}");
    }
    
    /**
     * Get taxonomy labels
     */
    private function getLabels(string $taxonomy, array $config): array {
        $singular = ucfirst(str_replace('-', ' ', $taxonomy));
        $plural = $singular . 's';
        
        // Handle special cases
        switch ($taxonomy) {
            case 'by-year':
                $singular = 'Year';
                $plural = 'Years';
                break;
            case 'nationality':
                $singular = 'Nationality';
                $plural = 'Nationalities';
                break;
            case 'keyword':
                $singular = 'Keyword';
                $plural = 'Keywords';
                break;
        }
        
        return [
            'name' => sprintf(__('%s', 'tmu'), $plural),
            'singular_name' => sprintf(__('%s', 'tmu'), $singular),
            'search_items' => sprintf(__('Search %s', 'tmu'), $plural),
            'all_items' => sprintf(__('All %s', 'tmu'), $plural),
            'parent_item' => sprintf(__('Parent %s', 'tmu'), $singular),
            'parent_item_colon' => sprintf(__('Parent %s:', 'tmu'), $singular),
            'edit_item' => sprintf(__('Edit %s', 'tmu'), $singular),
            'update_item' => sprintf(__('Update %s', 'tmu'), $singular),
            'add_new_item' => sprintf(__('Add New %s', 'tmu'), $singular),
            'new_item_name' => sprintf(__('New %s Name', 'tmu'), $singular),
            'menu_name' => sprintf(__('%s', 'tmu'), $plural),
        ];
    }
    
    /**
     * Get taxonomy arguments
     */
    private function getArgs(string $taxonomy, array $config): array {
        return [
            'hierarchical' => $config['hierarchical'] ?? false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => $config['show_admin_column'] ?? true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => $config['show_in_rest'] ?? true,
            'rewrite' => [
                'slug' => $taxonomy,
                'with_front' => false
            ]
        ];
    }
    
    /**
     * Get registered taxonomies
     */
    public function getRegisteredTaxonomies(): array {
        return array_keys(array_filter($this->taxonomies, function($config) {
            return $config['enabled'] ?? false;
        }));
    }
}