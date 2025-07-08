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
 */
class ThemeCore {
    
    /**
     * Theme instance
     *
     * @var ThemeCore
     */
    private static $instance = null;
    
    /**
     * Theme version
     *
     * @var string
     */
    private $version = TMU_VERSION;
    
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
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void {
        add_action('init', [$this, 'initTheme']);
        add_action('after_setup_theme', [$this, 'themeSetup']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }
    
    /**
     * Load required dependencies
     * 
     * Classes are now loaded via PSR-4 autoloading (Composer or fallback autoloader)
     * This method now only loads configuration files.
     */
    private function loadDependencies(): void {
        // Load configuration files only - classes are autoloaded
        $config_files = [
            TMU_INCLUDES_DIR . '/config/constants.php',
            TMU_INCLUDES_DIR . '/config/database.php',
            TMU_INCLUDES_DIR . '/config/assets.php'
        ];
        
        foreach ($config_files as $config_file) {
            if (file_exists($config_file)) {
                require_once $config_file;
            }
        }
        
        // Log successful dependency loading
        if (function_exists('tmu_log')) {
            tmu_log('Dependencies loaded using PSR-4 autoloading', 'info');
        }
    }
    
    /**
     * Initialize theme functionality
     * 
     * All classes are autoloaded when referenced, following the 19-step architecture
     */
    public function initTheme(): void {
        try {
            // Initialize Step 02 - Theme Initialization
            if (class_exists('TMU\\ThemeInitializer')) {
                ThemeInitializer::getInstance();
            }
            
            if (class_exists('TMU\\Admin\\SettingsAPI')) {
                Admin\SettingsAPI::getInstance();
            }
            
            // Initialize Step 03 - Database Migration
            if (class_exists('TMU\\Database\\Migration')) {
                Database\Migration::getInstance();
            }
            
            // Initialize Step 05 - Post Types
            if (class_exists('TMU\\PostTypes\\PostTypeManager')) {
                PostTypes\PostTypeManager::getInstance();
            }
            
            // Initialize Step 06 - Taxonomies
            if (class_exists('TMU\\Taxonomies\\TaxonomyManager')) {
                Taxonomies\TaxonomyManager::getInstance();
            }
            
            // Initialize Step 07 - Custom Fields
            if (class_exists('TMU\\Fields\\FieldManager')) {
                Fields\FieldManager::getInstance();
            }
            
            // Initialize Step 08 - Admin UI and Meta Boxes
            if (is_admin() && class_exists('TMU\\Admin\\AdminManager')) {
                Admin\AdminManager::getInstance();
            }
            
            // Initialize Step 11 - SEO and Schema Markup
            if (class_exists('TMU\\SEO\\SEOManager')) {
                SEO\SEOManager::getInstance();
            }
            
            // Initialize Step 12 - Search and Filtering
            if (class_exists('TMU\\Search\\SearchManager')) {
                Search\SearchManager::getInstance();
            }
            
            // Initialize API REST endpoints
            if (class_exists('TMU\\API\\REST\\SearchEndpoints')) {
                $search_endpoints = new API\REST\SearchEndpoints();
                $search_endpoints->init();
            }
            
            // Initialize Step 18 - Maintenance and Updates
            $maintenance_classes = [
                'TMU\\Backup\\BackupManager',
                'TMU\\Maintenance\\MaintenanceScheduler', 
                'TMU\\Maintenance\\DatabaseOptimizer',
                'TMU\\Updates\\UpdateManager',
                'TMU\\Maintenance\\SecurityAuditor',
                'TMU\\Migration\\MigrationManager',
                'TMU\\Maintenance\\TmdbDataUpdater',
                'TMU\\Maintenance\\PerformanceOptimizer',
                'TMU\\Maintenance\\SuccessMetrics'
            ];
            
            foreach ($maintenance_classes as $class) {
                if (class_exists($class)) {
                    new $class();
                }
            }
            
            // Log successful initialization
            if (function_exists('tmu_log')) {
                tmu_log('Theme initialization completed with autoloading', 'info');
            }
            
        } catch (\Exception $e) {
            // Log initialization errors
            if (function_exists('tmu_log')) {
                tmu_log('Theme initialization error: ' . $e->getMessage(), 'error');
            }
            
            // In debug mode, show the error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                wp_die('TMU Theme Initialization Error: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Theme setup
     */
    public function themeSetup(): void {
        // Add theme support
        add_theme_support('post-thumbnails');
        add_theme_support('title-tag');
        add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
        add_theme_support('customize-selective-refresh-widgets');
        add_theme_support('editor-styles');
        add_theme_support('wp-block-styles');
        add_theme_support('responsive-embeds');
        
        // Set image sizes for movie posters and media
        add_image_size('tmu-poster-small', 185, 278, true);    // Movie poster small
        add_image_size('tmu-poster-medium', 300, 450, true);   // Movie poster medium
        add_image_size('tmu-poster-large', 500, 750, true);    // Movie poster large
        add_image_size('tmu-backdrop-small', 533, 300, true);  // Backdrop small
        add_image_size('tmu-backdrop-medium', 800, 450, true); // Backdrop medium
        add_image_size('tmu-backdrop-large', 1280, 720, true); // Backdrop large
        
        // Load text domain
        load_theme_textdomain('tmu', TMU_THEME_DIR . '/languages');
        
        // Register nav menus
        register_nav_menus([
            'primary' => __('Primary Menu', 'tmu'),
            'footer' => __('Footer Menu', 'tmu'),
            'mobile' => __('Mobile Menu', 'tmu'),
        ]);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueueAssets(): void {
        // Main stylesheet (compiled Tailwind CSS)
        wp_enqueue_style(
            'tmu-main-style',
            TMU_ASSETS_BUILD_URL . '/css/main.css',
            [],
            $this->version
        );
        
        // Main JavaScript (compiled)
        wp_enqueue_script(
            'tmu-main-script',
            TMU_ASSETS_BUILD_URL . '/js/main.js',
            ['jquery'],
            $this->version,
            true
        );
        
        // Localize scripts with AJAX data
        wp_localize_script('tmu-main-script', 'tmu_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_ajax_nonce'),
            'loading_text' => __('Loading...', 'tmu'),
            'error_text' => __('Something went wrong. Please try again.', 'tmu'),
            'api_url' => home_url('/wp-json/tmu/v1/'),
        ]);
        
        // Enqueue Alpine.js for enhanced interactivity
        wp_enqueue_script(
            'alpinejs',
            'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js',
            [],
            '3.13.0',
            true
        );
        
        // Add defer attribute to Alpine.js
        add_filter('script_loader_tag', function($tag, $handle) {
            if ($handle === 'alpinejs') {
                return str_replace(' src', ' defer src', $tag);
            }
            return $tag;
        }, 10, 2);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueueAdminAssets(): void {
        // Admin stylesheet (compiled Tailwind CSS)
        wp_enqueue_style(
            'tmu-admin-style',
            TMU_ASSETS_BUILD_URL . '/css/admin.css',
            [],
            $this->version
        );
        
        // Admin JavaScript
        wp_enqueue_script(
            'tmu-admin-script',
            TMU_ASSETS_BUILD_URL . '/js/admin.js',
            ['jquery', 'wp-api'],
            $this->version,
            true
        );
        
        // Localize admin scripts
        wp_localize_script('tmu-admin-script', 'tmu_admin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_admin_nonce'),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'api_url' => home_url('/wp-json/tmu/v1/'),
            'tmdb_api_key' => get_option('tmu_tmdb_api_key', ''),
            'strings' => [
                'sync_success' => __('Data synchronized successfully!', 'tmu'),
                'sync_error' => __('Error synchronizing data. Please try again.', 'tmu'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'tmu'),
                'loading' => __('Loading...', 'tmu'),
                'tmdb_id_required' => __('TMDB ID is required for synchronization.', 'tmu'),
            ],
        ]);
    }
    
    /**
     * Get theme version
     *
     * @return string
     */
    public function getVersion(): string {
        return $this->version;
    }
}