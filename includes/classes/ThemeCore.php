<?php
/**
 * TMU Theme Core Class
 *
 * @package TMU
 * @version 1.0.0
 */

namespace TMU;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Theme Core Class
 * 
 * Initializes all theme functionality including post types, taxonomies,
 * blocks, TMDB integration, and performance optimizations.
 */
class ThemeCore {
    
    /**
     * Theme instance
     *
     * @var ThemeCore
     */
    private static ?ThemeCore $instance = null;
    
    /**
     * Theme version
     *
     * @var string
     */
    private string $version = TMU_VERSION;
    
    /**
     * Loaded modules
     *
     * @var array
     */
    private array $modules = [];
    
    /**
     * Get theme instance
     *
     * @return ThemeCore
     */
    public static function getInstance(): ThemeCore {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->initHooks();
        $this->loadDependencies();
        $this->initModules();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void {
        add_action('init', [$this, 'initTheme'], 1);
        add_action('after_setup_theme', [$this, 'themeSetup']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueueBlockAssets']);
        add_action('switch_theme', [$this, 'onThemeActivation']);
        add_action('after_switch_theme', [$this, 'afterThemeActivation']);
        
        // Theme deactivation
        add_action('switch_theme', [$this, 'onThemeDeactivation']);
        
        // AJAX handlers
        add_action('wp_ajax_tmu_sync_tmdb', [$this, 'handleTmdbSync']);
        add_action('wp_ajax_tmu_search_content', [$this, 'handleContentSearch']);
        add_action('wp_ajax_nopriv_tmu_search_content', [$this, 'handleContentSearch']);
    }
    
    /**
     * Load required dependencies
     */
    private function loadDependencies(): void {
        // Configuration
        require_once TMU_INCLUDES_DIR . '/config/config.php';
        
        // Helper functions
        require_once TMU_INCLUDES_DIR . '/helpers/functions.php';
        
        // Core classes
        $this->loadCoreClasses();
    }
    
    /**
     * Load core classes
     */
    private function loadCoreClasses(): void {
        $classes = [
            // Database
            'Database/Migration',
            'Database/QueryOptimizer',
            
            // Post Types and Taxonomies
            'PostTypes/PostTypeManager',
            'Taxonomies/TaxonomyManager',
            
            // Custom Fields and Blocks
            'Blocks/BlockRegistry',
            'Fields/FieldManager',
            
            // API Integration
            'API/TMDBClient',
            'API/DataProcessor',
            
            // Admin
            'Admin/AdminInterface',
            'Admin/MetaBoxes',
            'Admin/Settings',
            
            // Frontend
            'Frontend/TemplateLoader',
            'Frontend/AssetManager',
            'Frontend/SearchHandler',
            
            // Performance
            'Performance/CacheManager',
            'Performance/QueryOptimizer',
            'Performance/AssetOptimizer',
            
            // SEO
            'SEO/SchemaManager',
            'SEO/MetaManager',
            'SEO/SitemapGenerator',
            
            // Security
            'Security/InputValidator',
            'Security/OutputSanitizer',
            
            // Utils
            'Utils/Logger',
            'Utils/DataHelper',
            'Utils/ImageProcessor'
        ];
        
        foreach ($classes as $class) {
            $file = TMU_INCLUDES_DIR . '/classes/' . $class . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }
    
    /**
     * Initialize theme modules
     */
    private function initModules(): void {
        // Initialize modules in order of dependency
        $this->modules = [
            'migration' => Database\Migration::getInstance(),
            'postTypes' => PostTypes\PostTypeManager::getInstance(),
            'taxonomies' => Taxonomies\TaxonomyManager::getInstance(),
            'blocks' => Blocks\BlockRegistry::getInstance(),
            'fields' => Fields\FieldManager::getInstance(),
            'tmdb' => API\TMDBClient::getInstance(),
            'admin' => Admin\AdminInterface::getInstance(),
            'templates' => Frontend\TemplateLoader::getInstance(),
            'cache' => Performance\CacheManager::getInstance(),
            'seo' => SEO\SchemaManager::getInstance(),
            'security' => Security\InputValidator::getInstance()
        ];
    }
    
    /**
     * Initialize theme functionality
     */
    public function initTheme(): void {
        // Load text domain
        load_theme_textdomain('tmu', TMU_THEME_DIR . '/languages');
        
        // Initialize each module
        foreach ($this->modules as $name => $module) {
            if (method_exists($module, 'init')) {
                $module->init();
            }
        }
        
        // Register REST API routes
        $this->registerRestRoutes();
    }
    
    /**
     * Theme setup
     */
    public function themeSetup(): void {
        // Add theme support
        add_theme_support('post-thumbnails');
        add_theme_support('title-tag');
        add_theme_support('html5', [
            'search-form', 
            'comment-form', 
            'comment-list', 
            'gallery', 
            'caption',
            'style',
            'script'
        ]);
        add_theme_support('customize-selective-refresh-widgets');
        add_theme_support('responsive-embeds');
        add_theme_support('align-wide');
        add_theme_support('editor-styles');
        add_theme_support('wp-block-styles');
        
        // Add editor styles
        add_editor_style('assets/build/css/editor.css');
        
        // Set image sizes
        add_image_size('tmu-thumbnail', 300, 400, true);
        add_image_size('tmu-medium', 600, 800, true);
        add_image_size('tmu-large', 1200, 1600, true);
        add_image_size('tmu-poster', 500, 750, true);
        add_image_size('tmu-backdrop', 1280, 720, true);
        
        // Register nav menus
        register_nav_menus([
            'primary' => __('Primary Menu', 'tmu'),
            'footer' => __('Footer Menu', 'tmu'),
            'mobile' => __('Mobile Menu', 'tmu')
        ]);
        
        // Set content width
        if (!isset($GLOBALS['content_width'])) {
            $GLOBALS['content_width'] = 1200;
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueueAssets(): void {
        // Styles
        wp_enqueue_style(
            'tmu-style',
            TMU_ASSETS_URL . '/build/css/style.css',
            [],
            $this->version
        );
        
        // Scripts
        wp_enqueue_script(
            'tmu-frontend',
            TMU_ASSETS_URL . '/build/js/frontend.js',
            ['jquery'],
            $this->version,
            true
        );
        
        // Localize scripts
        wp_localize_script('tmu-frontend', 'tmuData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_nonce'),
            'restUrl' => rest_url('tmu/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'isLoggedIn' => is_user_logged_in(),
            'userId' => get_current_user_id(),
            'themeUrl' => TMU_THEME_URL,
            'assetsUrl' => TMU_ASSETS_URL
        ]);
        
        // Load Alpine.js
        wp_enqueue_script(
            'alpinejs',
            'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js',
            [],
            '3.13.2',
            true
        );
        
        // Add defer attribute to Alpine.js
        add_filter('script_loader_tag', function($tag, $handle) {
            if ($handle === 'alpinejs') {
                return str_replace('<script ', '<script defer ', $tag);
            }
            return $tag;
        }, 10, 2);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueueAdminAssets(): void {
        // Admin styles
        wp_enqueue_style(
            'tmu-admin',
            TMU_ASSETS_URL . '/build/css/admin.css',
            [],
            $this->version
        );
        
        // Admin scripts
        wp_enqueue_script(
            'tmu-admin',
            TMU_ASSETS_URL . '/build/js/admin.js',
            ['jquery', 'wp-api'],
            $this->version,
            true
        );
        
        // Localize admin scripts
        wp_localize_script('tmu-admin', 'tmuAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_admin_nonce'),
            'restUrl' => rest_url('tmu/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'tmdbApiKey' => get_option('tmu_tmdb_api_key', ''),
            'strings' => [
                'syncSuccess' => __('Sync completed successfully', 'tmu'),
                'syncError' => __('Sync failed. Please try again.', 'tmu'),
                'confirmDelete' => __('Are you sure you want to delete this item?', 'tmu')
            ]
        ]);
    }
    
    /**
     * Enqueue block editor assets
     */
    public function enqueueBlockAssets(): void {
        wp_enqueue_script(
            'tmu-blocks',
            TMU_ASSETS_URL . '/build/js/blocks.js',
            [
                'wp-blocks',
                'wp-element',
                'wp-editor',
                'wp-components',
                'wp-i18n',
                'wp-data'
            ],
            $this->version,
            true
        );
        
        wp_enqueue_style(
            'tmu-blocks-editor',
            TMU_ASSETS_URL . '/build/css/editor.css',
            ['wp-edit-blocks'],
            $this->version
        );
    }
    
    /**
     * Register REST API routes
     */
    private function registerRestRoutes(): void {
        add_action('rest_api_init', function() {
            // TMDB endpoints
            register_rest_route('tmu/v1', '/tmdb/movie/(?P<id>\d+)', [
                'methods' => 'GET',
                'callback' => [$this->modules['tmdb'], 'getMovieData'],
                'permission_callback' => 'is_user_logged_in'
            ]);
            
            register_rest_route('tmu/v1', '/tmdb/tv/(?P<id>\d+)', [
                'methods' => 'GET',
                'callback' => [$this->modules['tmdb'], 'getTVData'],
                'permission_callback' => 'is_user_logged_in'
            ]);
            
            // Search endpoints
            register_rest_route('tmu/v1', '/search', [
                'methods' => 'GET',
                'callback' => [$this->modules['templates'], 'handleSearch'],
                'permission_callback' => '__return_true'
            ]);
        });
    }
    
    /**
     * Handle theme activation
     */
    public function onThemeActivation(): void {
        // Run database migrations
        if (isset($this->modules['migration'])) {
            $this->modules['migration']->runMigrations();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        $this->setDefaultOptions();
    }
    
    /**
     * Handle after theme activation
     */
    public function afterThemeActivation(): void {
        // Create necessary pages
        $this->createDefaultPages();
        
        // Setup default menus
        $this->setupDefaultMenus();
    }
    
    /**
     * Handle theme deactivation
     */
    public function onThemeDeactivation(): void {
        // Clear scheduled events
        wp_clear_scheduled_hook('tmu_daily_sync');
        wp_clear_scheduled_hook('tmu_weekly_cleanup');
        
        // Clear cache
        if (isset($this->modules['cache'])) {
            $this->modules['cache']->clearAll();
        }
    }
    
    /**
     * Set default theme options
     */
    private function setDefaultOptions(): void {
        $defaults = [
            'tmu_movies' => 'on',
            'tmu_tv_series' => 'on',
            'tmu_dramas' => 'off',
            'tmu_people' => 'on',
            'tmu_videos' => 'on',
            'tmu_tmdb_api_key' => '',
            'tmu_cache_enabled' => 'on',
            'tmu_seo_enabled' => 'on',
            'tmu_performance_mode' => 'standard'
        ];
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * Create default pages
     */
    private function createDefaultPages(): void {
        $pages = [
            'movies' => [
                'title' => 'Movies',
                'content' => '<!-- wp:tmu/movie-archive /-->'
            ],
            'tv-shows' => [
                'title' => 'TV Shows',
                'content' => '<!-- wp:tmu/tv-archive /-->'
            ],
            'people' => [
                'title' => 'People',
                'content' => '<!-- wp:tmu/people-archive /-->'
            ]
        ];
        
        foreach ($pages as $slug => $page) {
            if (!get_page_by_path($slug)) {
                wp_insert_post([
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug
                ]);
            }
        }
    }
    
    /**
     * Setup default menus
     */
    private function setupDefaultMenus(): void {
        // Create primary menu if it doesn't exist
        $menu_name = 'Primary Menu';
        $menu_exists = wp_get_nav_menu_object($menu_name);
        
        if (!$menu_exists) {
            $menu_id = wp_create_nav_menu($menu_name);
            
            // Add menu items
            $pages = get_pages();
            foreach ($pages as $page) {
                wp_update_nav_menu_item($menu_id, 0, [
                    'menu-item-title' => $page->post_title,
                    'menu-item-object' => 'page',
                    'menu-item-object-id' => $page->ID,
                    'menu-item-type' => 'post_type',
                    'menu-item-status' => 'publish'
                ]);
            }
            
            // Assign to theme location
            $locations = get_theme_mod('nav_menu_locations');
            $locations['primary'] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }
    }
    
    /**
     * Handle TMDB sync AJAX request
     */
    public function handleTmdbSync(): void {
        check_ajax_referer('tmu_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'tmu'));
        }
        
        $post_id = intval($_POST['post_id']);
        $post_type = get_post_type($post_id);
        
        try {
            $result = false;
            
            switch ($post_type) {
                case 'movie':
                    $result = $this->modules['tmdb']->syncMovie($post_id);
                    break;
                case 'tv':
                    $result = $this->modules['tmdb']->syncTVShow($post_id);
                    break;
                case 'drama':
                    $result = $this->modules['tmdb']->syncDrama($post_id);
                    break;
            }
            
            if ($result) {
                wp_send_json_success([
                    'message' => __('Sync completed successfully', 'tmu')
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Sync failed. Please try again.', 'tmu')
                ]);
            }
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle content search AJAX request
     */
    public function handleContentSearch(): void {
        $search_term = sanitize_text_field($_GET['s'] ?? '');
        $post_types = array_map('sanitize_key', $_GET['types'] ?? ['movie', 'tv', 'drama', 'people']);
        
        $results = $this->modules['templates']->searchContent($search_term, $post_types);
        
        wp_send_json_success($results);
    }
    
    /**
     * Get theme version
     *
     * @return string
     */
    public function getVersion(): string {
        return $this->version;
    }
    
    /**
     * Get loaded modules
     *
     * @return array
     */
    public function getModules(): array {
        return $this->modules;
    }
    
    /**
     * Get specific module
     *
     * @param string $name Module name
     * @return object|null
     */
    public function getModule(string $name): ?object {
        return $this->modules[$name] ?? null;
    }
}