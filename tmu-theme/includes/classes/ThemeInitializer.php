<?php
/**
 * TMU Theme Initializer
 *
 * @package TMU
 * @version 1.0.0
 */

namespace TMU;

use TMU\Admin\Settings;
use TMU\Admin\Welcome;
use TMU\Config\ThemeConfig;
use TMU\Migration\SettingsMigrator;
use TMU\Database\Migration;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Initializer Class
 */
class ThemeInitializer {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Theme configuration
     */
    private $config;
    
    /**
     * Get instance
     */
    public static function getInstance(): ThemeInitializer {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        $this->config = ThemeConfig::getInstance();
        $this->initHooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void {
        add_action('after_switch_theme', [$this, 'onThemeActivation']);
        add_action('switch_theme', [$this, 'onThemeDeactivation']);
        add_action('admin_init', [$this, 'initAdmin']);
        add_action('init', [$this, 'initThemeFeatures']);
        add_action('wp_loaded', [$this, 'afterLoad']);
    }
    
    /**
     * Handle theme activation
     */
    public function onThemeActivation(): void {
        // Set activation flag
        set_transient('tmu_theme_activated', true, 30);
        
        // Run database migrations
        $this->runDatabaseMigrations();
        
        // Migrate plugin settings if they exist
        $this->migratePluginSettings();
        
        // Initialize default settings
        $this->initializeDefaultSettings();
        
        // Set up initial configuration
        $this->setupInitialConfiguration();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log activation
        error_log('TMU Theme activated successfully');
        
        // Redirect to welcome page
        if (!is_admin()) {
            wp_redirect(admin_url('admin.php?page=tmu-welcome'));
            exit;
        }
    }
    
    /**
     * Handle theme deactivation
     */
    public function onThemeDeactivation(): void {
        // Clean up temporary data
        delete_transient('tmu_theme_activated');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        error_log('TMU Theme deactivated');
    }
    
    /**
     * Run database migrations
     */
    private function runDatabaseMigrations(): void {
        try {
            $migration = Migration::getInstance();
            $migration->runMigrations();
            
            // Log successful migration
            error_log('TMU Theme: Database migrations completed successfully');
        } catch (Exception $e) {
            // Log migration error
            error_log('TMU Theme: Database migration failed - ' . $e->getMessage());
            
            // Show admin notice
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error">';
                echo '<p><strong>TMU Theme:</strong> Database migration failed. Please check the error logs or contact support.</p>';
                echo '<p>Error: ' . esc_html($e->getMessage()) . '</p>';
                echo '</div>';
            });
        }
    }
    
    /**
     * Migrate plugin settings
     */
    private function migratePluginSettings(): void {
        $migrator = new SettingsMigrator();
        $migrator->migrate();
    }
    
    /**
     * Initialize default settings
     */
    private function initializeDefaultSettings(): void {
        $defaults = $this->config->getDefaultSettings();
        
        foreach ($defaults as $option_name => $option_config) {
            $default_value = $option_config['default'] ?? '';
            if (get_option($option_name) === false) {
                add_option($option_name, $default_value);
            }
        }
    }
    
    /**
     * Setup initial configuration
     */
    private function setupInitialConfiguration(): void {
        // Set theme version
        update_option('tmu_theme_version', TMU_VERSION);
        
        // Set installation date
        if (!get_option('tmu_install_date')) {
            update_option('tmu_install_date', current_time('mysql'));
        }
        
        // Initialize feature flags
        $this->initializeFeatureFlags();
        
        // Setup default theme options
        $this->setupDefaultThemeOptions();
    }
    
    /**
     * Initialize feature flags
     */
    private function initializeFeatureFlags(): void {
        $features = [
            'tmu_movies' => 'off',
            'tmu_tv_series' => 'off', 
            'tmu_dramas' => 'off'
        ];
        
        foreach ($features as $feature => $default) {
            if (!get_option($feature)) {
                add_option($feature, $default);
            }
        }
    }
    
    /**
     * Setup default theme options
     */
    private function setupDefaultThemeOptions(): void {
        $theme_options = [
            'tmu_enable_ratings' => 'on',
            'tmu_enable_comments' => 'on',
            'tmu_enable_ajax_search' => 'on',
            'tmu_tmdb_api_key' => '',
            'tmu_images_per_page' => 20,
            'tmu_cache_duration' => 3600,
            'tmu_seo_enabled' => 'on'
        ];
        
        foreach ($theme_options as $option => $value) {
            if (!get_option($option)) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * Initialize admin features
     */
    public function initAdmin(): void {
        if (is_admin()) {
            Settings::getInstance();
            
            // Show welcome screen after activation
            if (get_transient('tmu_theme_activated')) {
                delete_transient('tmu_theme_activated');
                Welcome::getInstance()->showWelcomeScreen();
            }
        }
    }
    
    /**
     * Initialize theme features
     */
    public function initThemeFeatures(): void {
        // Add theme support based on settings
        $this->setupThemeSupport();
        
        // Initialize menus
        $this->setupNavigationMenus();
        
        // Setup image sizes
        $this->setupImageSizes();
        
        // Load text domain
        $this->loadTextDomain();
    }
    
    /**
     * Setup theme support
     */
    private function setupThemeSupport(): void {
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
        add_theme_support('wp-block-styles');
        add_theme_support('align-wide');
        add_theme_support('editor-styles');
        
        // Custom logo support
        add_theme_support('custom-logo', [
            'height' => 250,
            'width' => 250,
            'flex-width' => true,
            'flex-height' => true,
        ]);
        
        // Custom background
        add_theme_support('custom-background', [
            'default-color' => 'ffffff',
            'default-image' => '',
        ]);
    }
    
    /**
     * Setup navigation menus
     */
    private function setupNavigationMenus(): void {
        register_nav_menus([
            'primary' => __('Primary Menu', 'tmu'),
            'footer' => __('Footer Menu', 'tmu'),
            'mobile' => __('Mobile Menu', 'tmu'),
        ]);
    }
    
    /**
     * Setup image sizes
     */
    private function setupImageSizes(): void {
        // Theme specific image sizes
        add_image_size('tmu-thumbnail', 300, 400, true);
        add_image_size('tmu-medium', 600, 800, true);
        add_image_size('tmu-large', 1200, 1600, true);
        add_image_size('tmu-poster', 500, 750, true);
        add_image_size('tmu-backdrop', 1280, 720, true);
        add_image_size('tmu-profile', 300, 450, true);
        add_image_size('tmu-gallery', 400, 300, true);
    }
    
    /**
     * Load text domain
     */
    private function loadTextDomain(): void {
        load_theme_textdomain('tmu', TMU_THEME_DIR . '/languages');
    }
    
    /**
     * After load actions
     */
    public function afterLoad(): void {
        // Check for required plugins
        $this->checkRequiredPlugins();
        
        // Verify database integrity
        $this->verifyDatabaseIntegrity();
        
        // Initialize AJAX handlers
        $this->initializeAjaxHandlers();
    }
    
    /**
     * Check theme requirements
     */
    private function checkRequiredPlugins(): void {
        // Theme is standalone - no external plugins required
        $this->verifyThemeRequirements();
    }
    
    /**
     * Verify theme requirements are met
     */
    private function verifyThemeRequirements(): void {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', [$this, 'showPhpVersionNotice']);
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.5', '<')) {
            add_action('admin_notices', [$this, 'showWpVersionNotice']);
        }
    }
    
    /**
     * Show PHP version notice
     */
    public function showPhpVersionNotice(): void {
        echo '<div class="notice notice-error">';
        echo '<p><strong>TMU Theme:</strong> This theme requires PHP 7.4 or higher. You are running PHP ' . PHP_VERSION . '.</p>';
        echo '</div>';
    }
    
    /**
     * Show WordPress version notice
     */
    public function showWpVersionNotice(): void {
        echo '<div class="notice notice-error">';
        echo '<p><strong>TMU Theme:</strong> This theme requires WordPress 5.5 or higher. You are running WordPress ' . get_bloginfo('version') . '.</p>';
        echo '</div>';
    }
    
    /**
     * Verify database integrity
     */
    private function verifyDatabaseIntegrity(): void {
        $migration = Migration::getInstance();
        $migration_status = $migration->getMigrationStatus();
        
        if ($migration_status['needs_migration']) {
            // Database needs update
            add_action('admin_notices', [$this, 'showDatabaseUpdateNotice']);
        }
    }
    
    /**
     * Show database update notice
     */
    public function showDatabaseUpdateNotice(): void {
        echo '<div class="notice notice-warning">';
        echo '<p><strong>TMU Theme:</strong> Database update required. ';
        echo '<a href="' . admin_url('admin.php?page=tmu-migration') . '">Update Database</a></p>';
        echo '</div>';
    }
    
    /**
     * Initialize AJAX handlers
     */
    private function initializeAjaxHandlers(): void {
        add_action('wp_ajax_tmu_update_settings', [$this, 'handleSettingsUpdate']);
        add_action('wp_ajax_tmu_test_api', [$this, 'handleApiTest']);
    }
    
    /**
     * Handle settings update via AJAX
     */
    public function handleSettingsUpdate(): void {
        check_ajax_referer('tmu_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $setting = sanitize_text_field($_POST['setting']);
        $value = sanitize_text_field($_POST['value']);
        
        $allowed_settings = ['tmu_movies', 'tmu_tv_series', 'tmu_dramas'];
        
        if (in_array($setting, $allowed_settings)) {
            update_option($setting, $value);
            wp_send_json_success(['message' => 'Setting updated successfully']);
        } else {
            wp_send_json_error(['message' => 'Invalid setting']);
        }
    }
    
    /**
     * Handle API test via AJAX
     */
    public function handleApiTest(): void {
        check_ajax_referer('tmu_api_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        // Test TMDB API connection
        $test_url = "https://api.themoviedb.org/3/configuration?api_key=" . $api_key;
        $response = wp_remote_get($test_url);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'API test failed: ' . $response->get_error_message()]);
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['images'])) {
                wp_send_json_success(['message' => 'API connection successful']);
            } else {
                wp_send_json_error(['message' => 'Invalid API key']);
            }
        }
    }
}