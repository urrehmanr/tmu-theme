<?php
/**
 * TMU Post Type Manager
 *
 * @package TMU
 * @version 1.0.0
 */

namespace TMU\PostTypes;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Type Manager Class
 * 
 * Manages registration of all custom post types
 */
class PostTypeManager {
    
    /**
     * Manager instance
     *
     * @var PostTypeManager
     */
    private static ?PostTypeManager $instance = null;
    
    /**
     * Post types configuration
     *
     * @var array
     */
    private array $post_types = [];
    
    /**
     * Get manager instance
     *
     * @return PostTypeManager
     */
    public static function getInstance(): PostTypeManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        $this->post_types = tmu_config('post_types') ?? [];
    }
    
    /**
     * Initialize post type manager
     */
    public function init(): void {
        add_action('init', [$this, 'registerPostTypes']);
        add_action('admin_menu', [$this, 'organizeAdminMenus'], 20);
    }
    
    /**
     * Register all post types
     */
    public function registerPostTypes(): void {
        foreach ($this->post_types as $post_type => $config) {
            if ($config['enabled']) {
                $this->registerPostType($post_type, $config);
            }
        }
    }
    
    /**
     * Register individual post type
     */
    private function registerPostType(string $post_type, array $config): void {
        $labels = $this->getLabels($post_type, $config);
        $args = $this->getArgs($post_type, $config);
        
        register_post_type($post_type, array_merge($args, [
            'labels' => $labels,
            'supports' => $config['supports'] ?? ['title', 'editor', 'thumbnail'],
            'taxonomies' => $config['taxonomies'] ?? []
        ]));
        
        tmu_log("Registered post type: {$post_type}");
    }
    
    /**
     * Get post type labels
     */
    private function getLabels(string $post_type, array $config): array {
        $singular = ucfirst($post_type);
        $plural = $singular . 's';
        
        // Handle special cases
        switch ($post_type) {
            case 'tv':
                $singular = 'TV Show';
                $plural = 'TV Shows';
                break;
            case 'people':
                $singular = 'Person';
                $plural = 'People';
                break;
            case 'drama-episode':
                $singular = 'Drama Episode';
                $plural = 'Drama Episodes';
                break;
        }
        
        return [
            'name' => sprintf(__('%s', 'tmu'), $plural),
            'singular_name' => sprintf(__('%s', 'tmu'), $singular),
            'add_new' => sprintf(__('Add New %s', 'tmu'), $singular),
            'add_new_item' => sprintf(__('Add New %s', 'tmu'), $singular),
            'edit_item' => sprintf(__('Edit %s', 'tmu'), $singular),
            'new_item' => sprintf(__('New %s', 'tmu'), $singular),
            'view_item' => sprintf(__('View %s', 'tmu'), $singular),
            'search_items' => sprintf(__('Search %s', 'tmu'), $plural),
            'not_found' => sprintf(__('No %s found.', 'tmu'), strtolower($plural)),
            'not_found_in_trash' => sprintf(__('No %s found in Trash.', 'tmu'), strtolower($plural)),
            'all_items' => sprintf(__('All %s', 'tmu'), $plural),
            'menu_name' => sprintf(__('%s', 'tmu'), $plural),
        ];
    }
    
    /**
     * Get post type arguments
     */
    private function getArgs(string $post_type, array $config): array {
        $defaults = [
            'public' => true,
            'hierarchical' => $config['hierarchical'] ?? false,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'can_export' => true,
            'delete_with_user' => false,
            'has_archive' => true,
            'capability_type' => 'post',
            'rewrite' => ['with_front' => false, 'feeds' => false],
            'menu_position' => $config['menu_position'] ?? 20,
            'menu_icon' => $config['menu_icon'] ?? 'dashicons-admin-post'
        ];
        
        // Handle nested post types
        if (isset($config['show_in_menu'])) {
            $defaults['show_in_menu'] = $config['show_in_menu'];
        }
        
        return $defaults;
    }
    
    /**
     * Organize admin menus for nested post types
     */
    public function organizeAdminMenus(): void {
        // TV Show nested menus
        if (get_option('tmu_tv_series') === 'on') {
            $this->addNestedMenuItems('tv', [
                ['season', 'Seasons'],
                ['episode', 'Episodes']
            ]);
        }
        
        // Drama nested menus
        if (get_option('tmu_dramas') === 'on') {
            $this->addNestedMenuItems('drama', [
                ['drama-episode', 'Episodes']
            ]);
        }
    }
    
    /**
     * Add nested menu items
     */
    private function addNestedMenuItems(string $parent_post_type, array $items): void {
        foreach ($items as $item) {
            $post_type = $item[0];
            $title = $item[1];
            
            add_submenu_page(
                "edit.php?post_type={$parent_post_type}",
                sprintf(__('All %s', 'tmu'), $title),
                $title,
                'edit_posts',
                "edit.php?post_type={$post_type}"
            );
            
            add_submenu_page(
                "edit.php?post_type={$parent_post_type}",
                sprintf(__('Add New %s', 'tmu'), $title),
                sprintf(__('Add %s', 'tmu'), rtrim($title, 's')),
                'edit_posts',
                "post-new.php?post_type={$post_type}"
            );
        }
    }
    
    /**
     * Get registered post types
     */
    public function getRegisteredPostTypes(): array {
        return array_keys(array_filter($this->post_types, function($config) {
            return $config['enabled'] ?? false;
        }));
    }
}