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
     */
    private function loadDependencies(): void {
        // Load configuration files
        require_once TMU_INCLUDES_DIR . '/config/constants.php';
        require_once TMU_INCLUDES_DIR . '/config/database.php';
        require_once TMU_INCLUDES_DIR . '/config/assets.php';
        
        // Load Step 02 - Theme Initialization classes
        require_once TMU_INCLUDES_DIR . '/classes/ThemeInitializer.php';
        require_once TMU_INCLUDES_DIR . '/classes/Migration/SettingsMigrator.php';
        require_once TMU_INCLUDES_DIR . '/classes/Config/ThemeConfig.php';
        require_once TMU_INCLUDES_DIR . '/classes/Config/DefaultSettings.php';
        require_once TMU_INCLUDES_DIR . '/classes/Admin/Settings.php';
        require_once TMU_INCLUDES_DIR . '/classes/Admin/Welcome.php';
        require_once TMU_INCLUDES_DIR . '/classes/Admin/SettingsAPI.php';
        
        // Load Step 03 - Database Migration classes
        require_once TMU_INCLUDES_DIR . '/classes/Database/Schema.php';
        require_once TMU_INCLUDES_DIR . '/classes/Database/DataValidator.php';
        require_once TMU_INCLUDES_DIR . '/classes/Database/QueryBuilder.php';
        require_once TMU_INCLUDES_DIR . '/classes/Database/DataManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Database/Migration.php';
        
        // Load Step 05 - Post Types
        require_once TMU_INCLUDES_DIR . '/classes/PostTypes/AbstractPostType.php';
        require_once TMU_INCLUDES_DIR . '/classes/PostTypes/PostTypeManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/PostTypes/Movie.php';
        require_once TMU_INCLUDES_DIR . '/classes/PostTypes/TVShow.php';
        require_once TMU_INCLUDES_DIR . '/classes/PostTypes/Season.php';
        require_once TMU_INCLUDES_DIR . '/classes/PostTypes/Episode.php';
        require_once TMU_INCLUDES_DIR . '/classes/PostTypes/Drama.php';
        require_once TMU_INCLUDES_DIR . '/classes/PostTypes/DramaEpisode.php';
        require_once TMU_INCLUDES_DIR . '/classes/PostTypes/People.php';
        require_once TMU_INCLUDES_DIR . '/classes/PostTypes/Video.php';
        
        // Load Step 06 - Taxonomies
        require_once TMU_INCLUDES_DIR . '/classes/Taxonomies/AbstractTaxonomy.php';
        require_once TMU_INCLUDES_DIR . '/classes/Taxonomies/TaxonomyManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Taxonomies/Genre.php';
        require_once TMU_INCLUDES_DIR . '/classes/Taxonomies/Country.php';
        require_once TMU_INCLUDES_DIR . '/classes/Taxonomies/Language.php';
        require_once TMU_INCLUDES_DIR . '/classes/Taxonomies/ByYear.php';
        require_once TMU_INCLUDES_DIR . '/classes/Taxonomies/ProductionCompany.php';
        require_once TMU_INCLUDES_DIR . '/classes/Taxonomies/Network.php';
        require_once TMU_INCLUDES_DIR . '/classes/Taxonomies/Profession.php';
        
        // Load Step 07 - Custom Fields and Meta Boxes
        require_once TMU_INCLUDES_DIR . '/classes/Fields/AbstractField.php';
        require_once TMU_INCLUDES_DIR . '/classes/Fields/FieldManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Fields/TextField.php';
        require_once TMU_INCLUDES_DIR . '/classes/Fields/TextareaField.php';
        require_once TMU_INCLUDES_DIR . '/classes/Fields/NumberField.php';
        require_once TMU_INCLUDES_DIR . '/classes/Fields/SelectField.php';
        require_once TMU_INCLUDES_DIR . '/classes/Fields/ImageField.php';
        require_once TMU_INCLUDES_DIR . '/classes/Fields/TmdbSyncField.php';
        require_once TMU_INCLUDES_DIR . '/classes/Fields/MetaBoxFactory.php';
        
        // Load Step 07 - Gutenberg Blocks
        require_once TMU_INCLUDES_DIR . '/classes/Blocks/BaseBlock.php';
        require_once TMU_INCLUDES_DIR . '/classes/Blocks/BlockRegistry.php';
        require_once TMU_INCLUDES_DIR . '/classes/Blocks/MovieMetadataBlock.php';
        require_once TMU_INCLUDES_DIR . '/classes/Blocks/TvSeriesMetadataBlock.php';
        require_once TMU_INCLUDES_DIR . '/classes/Blocks/DramaMetadataBlock.php';
        require_once TMU_INCLUDES_DIR . '/classes/Blocks/PeopleMetadataBlock.php';
        require_once TMU_INCLUDES_DIR . '/classes/Blocks/DramaEpisodeMetadataBlock.php';
        require_once TMU_INCLUDES_DIR . '/classes/Blocks/VideoMetadataBlock.php';
        require_once TMU_INCLUDES_DIR . '/classes/Blocks/SeasonMetadataBlock.php';
        require_once TMU_INCLUDES_DIR . '/classes/Blocks/TaxonomyImageBlock.php';
        require_once TMU_INCLUDES_DIR . '/classes/Blocks/TaxonomyFaqsBlock.php';
        require_once TMU_INCLUDES_DIR . '/classes/Blocks/BlogPostsListBlock.php';
        require_once TMU_INCLUDES_DIR . '/classes/Blocks/TrendingContentBlock.php';
        require_once TMU_INCLUDES_DIR . '/classes/Blocks/TmdbSyncBlock.php';
        
        // Load Step 08 - Admin UI and Meta Boxes
        require_once TMU_INCLUDES_DIR . '/classes/Admin/AdminManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Admin/Columns/MovieColumns.php';
        require_once TMU_INCLUDES_DIR . '/classes/Admin/MetaBoxes/TMDBBox.php';
        require_once TMU_INCLUDES_DIR . '/classes/Admin/Actions/TMDBSync.php';
        
        // Load Step 11 - SEO and Schema Markup classes
        require_once TMU_INCLUDES_DIR . '/classes/SEO/SEOManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/SEO/SchemaManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/SEO/MetaTags.php';
        require_once TMU_INCLUDES_DIR . '/classes/SEO/SitemapGenerator.php';
        require_once TMU_INCLUDES_DIR . '/classes/SEO/OpenGraph.php';
        require_once TMU_INCLUDES_DIR . '/classes/SEO/TwitterCard.php';
        require_once TMU_INCLUDES_DIR . '/classes/SEO/BreadcrumbManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/SEO/Analytics.php';
        
        // Load individual Schema classes
        require_once TMU_INCLUDES_DIR . '/classes/SEO/Schema/MovieSchema.php';
        require_once TMU_INCLUDES_DIR . '/classes/SEO/Schema/TVShowSchema.php';
        require_once TMU_INCLUDES_DIR . '/classes/SEO/Schema/PersonSchema.php';
        require_once TMU_INCLUDES_DIR . '/classes/SEO/Schema/EpisodeSchema.php';
        require_once TMU_INCLUDES_DIR . '/classes/SEO/Schema/SeasonSchema.php';
        
        // Load Step 12 - Search and Filtering classes
        require_once TMU_INCLUDES_DIR . '/classes/Search/SearchManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Search/SearchEngine.php';
        require_once TMU_INCLUDES_DIR . '/classes/Search/SearchIndexManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Search/QueryBuilder.php';
        require_once TMU_INCLUDES_DIR . '/classes/Search/ResultProcessor.php';
        require_once TMU_INCLUDES_DIR . '/classes/Search/FilterManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Search/AjaxSearch.php';
        require_once TMU_INCLUDES_DIR . '/classes/Search/RecommendationEngine.php';
        require_once TMU_INCLUDES_DIR . '/classes/Search/SearchResult.php';
        
        // Load Facet classes
        require_once TMU_INCLUDES_DIR . '/classes/Search/Facets/PostTypeFacet.php';
        require_once TMU_INCLUDES_DIR . '/classes/Search/Facets/TaxonomyFacet.php';
        require_once TMU_INCLUDES_DIR . '/classes/Search/Facets/YearFacet.php';
        require_once TMU_INCLUDES_DIR . '/classes/Search/Facets/RatingFacet.php';
        require_once TMU_INCLUDES_DIR . '/classes/Search/Facets/RuntimeFacet.php';
        
        // Load API REST endpoints
        require_once TMU_INCLUDES_DIR . '/classes/API/REST/SearchEndpoints.php';
        
        // Load Step 18 - Maintenance and Updates classes
        require_once TMU_INCLUDES_DIR . '/classes/Backup/BackupManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Maintenance/MaintenanceManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Updates/UpdateManager.php';
        require_once TMU_INCLUDES_DIR . '/classes/Maintenance/SecurityAuditor.php';
        
        // Load placeholder classes - will be created in future steps
        // require_once TMU_INCLUDES_DIR . '/classes/API/TMDBClient.php';
        // require_once TMU_INCLUDES_DIR . '/classes/Frontend/TemplateLoader.php';
        // require_once TMU_INCLUDES_DIR . '/classes/Frontend/AssetManager.php';
    }
    
    /**
     * Initialize theme functionality
     */
    public function initTheme(): void {
        // Initialize Step 02 - Theme Initialization
        ThemeInitializer::getInstance();
        Admin\SettingsAPI::getInstance();
        
        // Initialize Step 03 - Database Migration
        Database\Migration::getInstance();
        
        // Initialize Step 05 - Post Types
        PostTypes\PostTypeManager::getInstance();
        
        // Initialize Step 06 - Taxonomies
        Taxonomies\TaxonomyManager::getInstance();
        
        // Initialize Step 07 - Custom Fields and Meta Boxes
        $field_manager = Fields\FieldManager::getInstance();
        new Fields\MetaBoxFactory($field_manager);
        
        // Initialize Step 07 - Gutenberg Blocks
        Blocks\BlockRegistry::getInstance();
        
        // Initialize API controllers for blocks
        API\BlockDataController::getInstance();
        
        // Initialize Step 08 - Admin UI and Meta Boxes
        if (is_admin()) {
            Admin\AdminManager::getInstance();
        }
        
        // Initialize Step 11 - SEO and Schema Markup
        SEO\SEOManager::getInstance();
        
        // Initialize Step 12 - Search and Filtering
        Search\SearchManager::getInstance();
        
        // Initialize API REST endpoints
        $search_endpoints = new API\REST\SearchEndpoints();
        $search_endpoints->init();
        
        // Initialize Step 18 - Maintenance and Updates
        new Backup\BackupManager();
        new Maintenance\MaintenanceManager();
        new Updates\UpdateManager();
        new Maintenance\SecurityAuditor();
        
        // Initialize managers - will be activated in future steps
        // API\TMDBClient::getInstance();
        // Frontend\TemplateLoader::getInstance();
        // Frontend\AssetManager::getInstance();
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