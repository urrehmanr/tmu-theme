<?php
/**
 * Post Type Manager
 *
 * @package TMU\PostTypes
 * @version 1.0.0
 */

namespace TMU\PostTypes;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Type Manager Class
 */
class PostTypeManager {
    
    /**
     * Manager instance
     *
     * @var PostTypeManager
     */
    private static $instance = null;
    
    /**
     * Registered post types
     *
     * @var array
     */
    private $post_types = [];
    
    /**
     * Post type instances
     *
     * @var array
     */
    private $post_type_instances = [];
    
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
     * Constructor
     */
    private function __construct() {
        $this->initializePostTypes();
        
        // Don't register post types in constructor to avoid early registration
        // $this->registerAllPostTypes();
        
        // Add WordPress hooks
        $this->initHooks();
        
        // Register activation hook for flushing rewrite rules
        add_action('after_switch_theme', [$this, 'flushRewriteRules']);
        
        // Register deactivation hook
        add_action('switch_theme', [$this, 'deactivatePostTypes']);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void {
        // Register post types on init with priority 5
        add_action('init', [$this, 'registerAllPostTypes'], 5);
        
        // Admin hooks
        add_action('admin_menu', [$this, 'organizeAdminMenus'], 20);
        add_action('admin_init', [$this, 'setupAdminCustomizations']);
    }
    
    /**
     * Initialize post type instances
     */
    private function initializePostTypes(): void {
        // Load post type configuration
        $post_type_config = [];
        $config_file = TMU_INCLUDES_DIR . '/config/post-types.php';
        
        if (file_exists($config_file)) {
            $post_type_config = include $config_file;
        }
        
        // Create post type instances
        $this->post_type_instances = [
            'movie' => new Movie(),
            'tv' => new TVShow(),
            'drama' => new Drama(),
            'people' => new People(),
            'video' => new Video(),
            'season' => new Season(),
            'episode' => new Episode(),
            'drama-episode' => new DramaEpisode(),
        ];
        
        // Log post type initialization
        if (function_exists('tmu_log')) {
            tmu_log('Initialized ' . count($this->post_type_instances) . ' post type instances', 'info');
        }
    }
    
    /**
     * Register all post types
     */
    public function registerAllPostTypes(): void {
        // Enable feature flags for post types based on options
        $this->setFeatureFlags();
        
        foreach ($this->post_type_instances as $slug => $post_type) {
            if ($post_type instanceof AbstractPostType) {
                $post_type->register();
                
                if ($post_type->exists()) {
                    $this->post_types[$slug] = $post_type;
                    if (function_exists('tmu_log')) {
                        tmu_log("Registered post type: {$slug}", 'info');
                    }
                }
            }
        }
        
        // Flush rewrite rules if needed
        $this->maybeFlushRewriteRules();
    }
    
    /**
     * Set feature flags for post types based on options
     */
    private function setFeatureFlags(): void {
        // If no options are set, enable all post types by default
        if (!get_option('tmu_movies') && !get_option('tmu_tv_series') && !get_option('tmu_dramas')) {
            update_option('tmu_movies', 'on');
            update_option('tmu_tv_series', 'on');
            update_option('tmu_dramas', 'on');
            
            if (function_exists('tmu_log')) {
                tmu_log('No post type options found, enabling all by default', 'info');
            }
        }
    }
    
    /**
     * Organize admin menus for nested post types
     */
    public function organizeAdminMenus(): void {
        // Season submenu under TV Shows
        if ($this->isPostTypeRegistered('tv') && $this->isPostTypeRegistered('season')) {
            add_submenu_page(
                'edit.php?post_type=tv',
                __('All Seasons', 'tmu'),
                __('All Seasons', 'tmu'),
                'edit_posts',
                'edit.php?post_type=season'
            );
            
            add_submenu_page(
                'edit.php?post_type=tv',
                __('Add New Season', 'tmu'),
                __('Add New Season', 'tmu'),
                'edit_posts',
                'post-new.php?post_type=season'
            );
        }
        
        // Episode submenu under TV Shows
        if ($this->isPostTypeRegistered('tv') && $this->isPostTypeRegistered('episode')) {
            add_submenu_page(
                'edit.php?post_type=tv',
                __('All Episodes', 'tmu'),
                __('All Episodes', 'tmu'),
                'edit_posts',
                'edit.php?post_type=episode'
            );
            
            add_submenu_page(
                'edit.php?post_type=tv',
                __('Add New Episode', 'tmu'),
                __('Add New Episode', 'tmu'),
                'edit_posts',
                'post-new.php?post_type=episode'
            );
        }
        
        // Drama Episode submenu under Dramas
        if ($this->isPostTypeRegistered('drama') && $this->isPostTypeRegistered('drama-episode')) {
            add_submenu_page(
                'edit.php?post_type=drama',
                __('All Episodes', 'tmu'),
                __('All Episodes', 'tmu'),
                'edit_posts',
                'edit.php?post_type=drama-episode'
            );
            
            add_submenu_page(
                'edit.php?post_type=drama',
                __('Add New Episode', 'tmu'),
                __('Add New Episode', 'tmu'),
                'edit_posts',
                'post-new.php?post_type=drama-episode'
            );
        }
    }
    
    /**
     * Setup admin customizations
     */
    public function setupAdminCustomizations(): void {
        foreach ($this->post_types as $post_type) {
            $this->setupPostTypeAdminHooks($post_type);
        }
    }
    
    /**
     * Setup admin hooks for a post type
     *
     * @param AbstractPostType $post_type Post type instance
     */
    private function setupPostTypeAdminHooks(AbstractPostType $post_type): void {
        $post_type_slug = $post_type->getPostType();
        
        // Admin columns
        add_filter("manage_{$post_type_slug}_posts_columns", [$post_type, 'addAdminColumns']);
        add_action("manage_{$post_type_slug}_posts_custom_column", [$post_type, 'displayAdminColumnContent'], 10, 2);
        add_filter("manage_edit-{$post_type_slug}_sortable_columns", [$post_type, 'makeSortableColumns']);
        add_action('pre_get_posts', [$post_type, 'handleSorting']);
        
        // Row actions
        add_filter("{$post_type_slug}_row_actions", [$post_type, 'addRowActions'], 10, 2);
        
        // Bulk actions
        add_filter("bulk_actions-edit-{$post_type_slug}", [$post_type, 'addBulkActions']);
        add_filter("handle_bulk_actions-edit-{$post_type_slug}", [$post_type, 'handleBulkActions'], 10, 3);
    }
    
    /**
     * Check if post type is registered
     *
     * @param string $post_type Post type slug
     * @return bool
     */
    public function isPostTypeRegistered(string $post_type): bool {
        return isset($this->post_types[$post_type]) && $this->post_types[$post_type]->exists();
    }
    
    /**
     * Get post type instance
     *
     * @param string $post_type Post type slug
     * @return AbstractPostType|null
     */
    public function getPostType(string $post_type): ?AbstractPostType {
        return $this->post_types[$post_type] ?? null;
    }
    
    /**
     * Get all registered post types
     *
     * @return array
     */
    public function getRegisteredPostTypes(): array {
        return array_keys($this->post_types);
    }
    
    /**
     * Get all post type instances
     *
     * @return array
     */
    public function getPostTypeInstances(): array {
        return $this->post_types;
    }
    
    /**
     * Get post types count
     *
     * @return int
     */
    public function getPostTypesCount(): int {
        return count($this->post_types);
    }
    
    /**
     * Get post type by query var
     *
     * @param string $query_var Query variable
     * @return AbstractPostType|null
     */
    public function getPostTypeByQueryVar(string $query_var): ?AbstractPostType {
        foreach ($this->post_types as $post_type) {
            $post_type_object = $post_type->getPostTypeObject();
            if ($post_type_object && $post_type_object->query_var === $query_var) {
                return $post_type;
            }
        }
        
        return null;
    }
    
    /**
     * Maybe flush rewrite rules
     */
    private function maybeFlushRewriteRules(): void {
        $post_types_option = get_option('tmu_registered_post_types', []);
        $current_post_types = $this->getRegisteredPostTypes();
        
        // Check if post types have changed
        if ($post_types_option !== $current_post_types) {
            flush_rewrite_rules();
            update_option('tmu_registered_post_types', $current_post_types);
            tmu_log('Flushed rewrite rules due to post type changes', 'info');
        }
    }
    
    /**
     * Activate post type
     *
     * @param string $post_type Post type slug
     * @return bool
     */
    public function activatePostType(string $post_type): bool {
        if (isset($this->post_type_instances[$post_type])) {
            $option_name = "tmu_{$post_type}";
            if ($post_type === 'tv') {
                $option_name = 'tmu_tv_series';
            }
            
            update_option($option_name, 'on');
            
            // Re-register post types
            $this->registerAllPostTypes();
            
            tmu_log("Activated post type: {$post_type}", 'info');
            return true;
        }
        
        return false;
    }
    
    /**
     * Deactivate post type
     *
     * @param string $post_type Post type slug
     * @return bool
     */
    public function deactivatePostType(string $post_type): bool {
        $option_name = "tmu_{$post_type}";
        if ($post_type === 'tv') {
            $option_name = 'tmu_tv_series';
        }
        
        update_option($option_name, 'off');
        
        // Remove from registered post types
        unset($this->post_types[$post_type]);
        
        // Re-register remaining post types
        $this->registerAllPostTypes();
        
        tmu_log("Deactivated post type: {$post_type}", 'info');
        return true;
    }
    
    /**
     * Get post type status (enabled/disabled)
     *
     * @param string $post_type Post type slug
     * @return bool
     */
    public function isPostTypeEnabled(string $post_type): bool {
        $option_name = "tmu_{$post_type}";
        if ($post_type === 'tv') {
            $option_name = 'tmu_tv_series';
        }
        
        return get_option($option_name, 'off') === 'on';
    }
    
    /**
     * Get statistics about post types
     *
     * @return array
     */
    public function getStatistics(): array {
        $stats = [
            'total_post_types' => count($this->post_type_instances),
            'registered_post_types' => count($this->post_types),
            'post_counts' => [],
        ];
        
        foreach ($this->post_types as $slug => $post_type) {
            $stats['post_counts'][$slug] = $post_type->getPostsCount();
        }
        
        return $stats;
    }
    
    /**
     * Reset all post types (for testing)
     */
    public function resetPostTypes(): void {
        $this->post_types = [];
        $this->initializePostTypes();
    }

    /**
     * Flush rewrite rules on theme activation
     */
    public function flushRewriteRules(): void {
        // First, register all post types
        $this->registerAllPostTypes();
        
        // Then flush rewrite rules
        flush_rewrite_rules();
        
        // Store activation timestamp
        update_option('tmu_post_types_activated', time());
    }
    
    /**
     * Deactivate post types on theme switch
     */
    public function deactivatePostTypes(): void {
        // Store deactivation timestamp
        update_option('tmu_post_types_deactivated', time());
    }
}