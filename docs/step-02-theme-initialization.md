# Step 02: Theme Initialization

## Purpose
Implement a comprehensive theme initialization system that handles theme activation, settings migration from plugin, initial configuration, and proper setup of all TMU theme components with backward compatibility.

## Overview
This step creates a robust initialization system that:
1. Handles theme activation and deactivation gracefully
2. Migrates existing plugin settings and options
3. Sets up initial theme configuration
4. Provides admin interface for theme settings
5. Manages feature toggles (Movies, TV Series, Dramas)

## Analysis from Plugin Settings

### Plugin Settings Structure
From `tmu-plugin/setup/settings.php`, the plugin uses:
- `tmu_movies` - Enable/disable movies functionality
- `tmu_tv_series` - Enable/disable TV series functionality  
- `tmu_dramas` - Enable/disable dramas functionality
- `tmu_email` - Email configuration
- Admin settings page with toggle switches
- AJAX-powered settings updates

## Theme Initialization Architecture

### Directory Structure with File Status
```
includes/classes/
├── ThemeInitializer.php      # [CREATE NEW - STEP 2] Main initialization class
├── Admin/                    # [UPDATE DIR - STEP 2] Extend existing directory from Step 1
│   ├── Settings.php          # [CREATE NEW - STEP 2] Theme settings page - Admin interface
│   ├── SettingsAPI.php       # [CREATE NEW - STEP 2] Settings API handler - AJAX endpoints
│   └── Welcome.php           # [CREATE NEW - STEP 2] Welcome screen - First-time setup
├── Config/                   # [CREATE DIR - STEP 2] Configuration management
│   ├── ThemeConfig.php       # [CREATE NEW - STEP 2] Configuration manager - Central config
│   └── DefaultSettings.php   # [CREATE NEW - STEP 2] Default settings - Initial values
└── Migration/                # [CREATE DIR - STEP 2] Migration functionality
    └── SettingsMigrator.php   # [CREATE NEW - STEP 2] Plugin settings migration - Legacy compatibility
```

### **Dependencies from Step 1:**
- **[REQUIRED]** `includes/classes/ThemeCore.php` - Main theme class from Step 1
- **[REQUIRED]** `includes/config/constants.php` - Constants from Step 1
- **[REQUIRED]** `functions.php` - Theme bootstrap from Step 1
- **[REQUIRED]** Tailwind CSS setup - Admin styling depends on admin.css from Step 1

### **Files Referenced but Created in Other Steps:**
- **`tmu-plugin/setup/settings.php`** - [REFERENCE ONLY] Analyzed for migration logic
- **`includes/classes/Database/Migration.php`** - [CREATE NEW - STEP 3] Database operations
- **`includes/classes/Admin/AdminInterface.php`** - [CREATE NEW - STEP 8] Main admin interface

## Core Implementation

### 1. Theme Initializer (`includes/classes/ThemeInitializer.php`)
**File Status**: [CREATE NEW - STEP 2]
**File Path**: `tmu-theme/includes/classes/ThemeInitializer.php`
**Purpose**: Main initialization class handling theme activation, settings migration
**Dependencies**: 
- [DEPENDS ON] `ThemeCore.php` [FROM STEP 1] - Main theme class
- [DEPENDS ON] `includes/config/constants.php` [FROM STEP 1] - Theme constants
- [DEPENDS ON] `ThemeConfig.php` [CREATE NEW - STEP 2] - Configuration manager
- [DEPENDS ON] `SettingsMigrator.php` [CREATE NEW - STEP 2] - Migration functionality
**Tailwind Status**: USES - Admin interface uses Tailwind CSS from Step 1
**Integration**: Hooks into WordPress theme activation/deactivation system

```php
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
        
        foreach ($defaults as $option_name => $default_value) {
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
     * Check required plugins
     */
    private function checkRequiredPlugins(): void {
        // Check if Meta Box plugin was deactivated
        if (!function_exists('rwmb_meta')) {
            // Meta Box was deactivated, theme can handle this
            add_action('admin_notices', [$this, 'showMetaBoxNotice']);
        }
    }
    
    /**
     * Show Meta Box deactivation notice
     */
    public function showMetaBoxNotice(): void {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>TMU Theme:</strong> Meta Box plugin was detected as deactivated. The theme will use native WordPress meta fields.</p>';
        echo '</div>';
    }
    
    /**
     * Verify database integrity
     */
    private function verifyDatabaseIntegrity(): void {
        $db_version = get_option('tmu_db_version', '0.0.0');
        $theme_version = TMU_VERSION;
        
        if (version_compare($db_version, $theme_version, '<')) {
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
```

### 2. Settings Migrator (`includes/classes/Migration/SettingsMigrator.php`)
**File Status**: [CREATE NEW - STEP 2]
**File Path**: `tmu-theme/includes/classes/Migration/SettingsMigrator.php`
**Purpose**: Migrate existing plugin settings to theme options for backward compatibility
**Dependencies**: 
- [DEPENDS ON] None - Standalone migration functionality
- [REFERENCES] `tmu-plugin/setup/settings.php` [REFERENCE ONLY] - Analyzed for setting names
**Migration Logic**: Preserves existing plugin configuration during theme switch
**AI Action**: Create class that reads existing plugin options and converts to theme options

```php
<?php
/**
 * TMU Settings Migrator
 *
 * @package TMU\Migration
 * @version 1.0.0
 */

namespace TMU\Migration;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Migrator Class
 */
class SettingsMigrator {
    
    /**
     * Plugin option mappings
     */
    private $option_mappings = [
        'tmu_movies' => 'tmu_movies',
        'tmu_tv_series' => 'tmu_tv_series',
        'tmu_dramas' => 'tmu_dramas',
        'tmu_email' => 'tmu_email',
    ];
    
    /**
     * Migrate plugin settings to theme
     */
    public function migrate(): bool {
        $migrated_any = false;
        
        foreach ($this->option_mappings as $plugin_option => $theme_option) {
            $plugin_value = get_option($plugin_option);
            
            if ($plugin_value !== false) {
                // Option exists from plugin
                $theme_value = get_option($theme_option);
                
                if ($theme_value === false) {
                    // Theme option doesn't exist, migrate it
                    add_option($theme_option, $plugin_value);
                    $migrated_any = true;
                    
                    error_log("TMU: Migrated {$plugin_option} to {$theme_option} with value: {$plugin_value}");
                }
            }
        }
        
        if ($migrated_any) {
            // Mark migration as completed
            update_option('tmu_settings_migrated', true);
            update_option('tmu_migration_date', current_time('mysql'));
        }
        
        return $migrated_any;
    }
    
    /**
     * Check if settings were migrated
     */
    public function isMigrated(): bool {
        return (bool) get_option('tmu_settings_migrated', false);
    }
    
    /**
     * Get migration status
     */
    public function getMigrationStatus(): array {
        $status = [
            'migrated' => $this->isMigrated(),
            'migration_date' => get_option('tmu_migration_date'),
            'migrated_options' => []
        ];
        
        foreach ($this->option_mappings as $plugin_option => $theme_option) {
            $plugin_value = get_option($plugin_option);
            $theme_value = get_option($theme_option);
            
            $status['migrated_options'][$plugin_option] = [
                'plugin_value' => $plugin_value,
                'theme_value' => $theme_value,
                'migrated' => $plugin_value !== false && $theme_value !== false
            ];
        }
        
        return $status;
    }
}
```

### 3. Theme Configuration (`includes/classes/Config/ThemeConfig.php`)
```php
<?php
/**
 * TMU Theme Configuration
 *
 * @package TMU\Config
 * @version 1.0.0
 */

namespace TMU\Config;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Configuration Class
 */
class ThemeConfig {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Configuration data
     */
    private $config = [];
    
    /**
     * Get instance
     */
    public static function getInstance(): ThemeConfig {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        $this->loadConfiguration();
    }
    
    /**
     * Load configuration
     */
    private function loadConfiguration(): void {
        // Load configuration files
        $config_files = [
            'theme-options' => TMU_INCLUDES_DIR . '/config/theme-options.php',
            'post-types' => TMU_INCLUDES_DIR . '/config/post-types.php',
            'taxonomies' => TMU_INCLUDES_DIR . '/config/taxonomies.php',
            'fields' => TMU_INCLUDES_DIR . '/config/fields.php',
        ];
        
        foreach ($config_files as $key => $file) {
            if (file_exists($file)) {
                $this->config[$key] = require $file;
            }
        }
    }
    
    /**
     * Get configuration value
     */
    public function get(string $key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * Get default settings
     */
    public function getDefaultSettings(): array {
        return $this->get('theme-options', []);
    }
    
    /**
     * Get post types configuration
     */
    public function getPostTypesConfig(): array {
        return $this->get('post-types', []);
    }
    
    /**
     * Get taxonomies configuration
     */
    public function getTaxonomiesConfig(): array {
        return $this->get('taxonomies', []);
    }
    
    /**
     * Get fields configuration
     */
    public function getFieldsConfig(): array {
        return $this->get('fields', []);
    }
    
    /**
     * Check if feature is enabled
     */
    public function isFeatureEnabled(string $feature): bool {
        $option_name = "tmu_{$feature}";
        return get_option($option_name, 'off') === 'on';
    }
    
    /**
     * Get TMDB API key
     */
    public function getTmdbApiKey(): string {
        return get_option('tmu_tmdb_api_key', '');
    }
    
    /**
     * Get cache duration
     */
    public function getCacheDuration(): int {
        return (int) get_option('tmu_cache_duration', 3600);
    }
}
```

### 4. Admin Settings (`includes/classes/Admin/Settings.php`)
```php
<?php
/**
 * TMU Admin Settings
 *
 * @package TMU\Admin
 * @version 1.0.0
 */

namespace TMU\Admin;

use TMU\Config\ThemeConfig;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Settings Class
 */
class Settings {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Theme config
     */
    private $config;
    
    /**
     * Get instance
     */
    public static function getInstance(): Settings {
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
     * Initialize hooks
     */
    private function initHooks(): void {
        add_action('admin_menu', [$this, 'addAdminPages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }
    
    /**
     * Add admin pages
     */
    public function addAdminPages(): void {
        // Main settings page
        add_menu_page(
            __('TMU Settings', 'tmu'),
            __('TMU Settings', 'tmu'),
            'manage_options',
            'tmu-settings',
            [$this, 'renderSettingsPage'],
            'dashicons-video-alt2',
            30
        );
        
        // API settings submenu
        add_submenu_page(
            'tmu-settings',
            __('API Settings', 'tmu'),
            __('API Settings', 'tmu'),
            'manage_options',
            'tmu-api-settings',
            [$this, 'renderApiSettingsPage']
        );
        
        // SEO settings submenu
        add_submenu_page(
            'tmu-settings',
            __('SEO Settings', 'tmu'),
            __('SEO Settings', 'tmu'),
            'manage_options',
            'tmu-seo-settings',
            [$this, 'renderSeoSettingsPage']
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueueAdminAssets($hook): void {
        if (strpos($hook, 'tmu-') === false) {
            return;
        }
        
        wp_enqueue_style(
            'tmu-admin-settings',
            TMU_ASSETS_URL . '/css/admin-settings.css',
            [],
            TMU_VERSION
        );
        
        wp_enqueue_script(
            'tmu-admin-settings',
            TMU_ASSETS_URL . '/js/admin-settings.js',
            ['jquery'],
            TMU_VERSION,
            true
        );
        
        wp_localize_script('tmu-admin-settings', 'tmuSettings', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_settings_nonce'),
            'apiNonce' => wp_create_nonce('tmu_api_nonce'),
        ]);
    }
    
    /**
     * Render main settings page
     */
    public function renderSettingsPage(): void {
        $options = [
            'tmu_movies' => get_option('tmu_movies', 'off'),
            'tmu_tv_series' => get_option('tmu_tv_series', 'off'),
            'tmu_dramas' => get_option('tmu_dramas', 'off'),
        ];
        
        ?>
        <div class="wrap tmu-settings">
            <h1><?php _e('TMU Theme Settings', 'tmu'); ?></h1>
            
            <div class="tmu-settings-container">
                <div class="tmu-settings-content">
                    <h2><?php _e('Content Types', 'tmu'); ?></h2>
                    <p><?php _e('Enable or disable different content types for your website.', 'tmu'); ?></p>
                    
                    <div class="tmu-setting-item">
                        <label for="tmu_movies">
                            <strong><?php _e('Movies', 'tmu'); ?></strong>
                            <p class="description"><?php _e('Enable movie database functionality', 'tmu'); ?></p>
                        </label>
                        <div class="tmu-toggle">
                            <input type="checkbox" 
                                   id="tmu_movies" 
                                   name="tmu_movies" 
                                   value="on" 
                                   <?php checked($options['tmu_movies'], 'on'); ?>
                                   data-setting="tmu_movies">
                            <span class="tmu-toggle-slider"></span>
                        </div>
                    </div>
                    
                    <div class="tmu-setting-item">
                        <label for="tmu_tv_series">
                            <strong><?php _e('TV Series', 'tmu'); ?></strong>
                            <p class="description"><?php _e('Enable TV series database functionality', 'tmu'); ?></p>
                        </label>
                        <div class="tmu-toggle">
                            <input type="checkbox" 
                                   id="tmu_tv_series" 
                                   name="tmu_tv_series" 
                                   value="on" 
                                   <?php checked($options['tmu_tv_series'], 'on'); ?>
                                   data-setting="tmu_tv_series">
                            <span class="tmu-toggle-slider"></span>
                        </div>
                    </div>
                    
                    <div class="tmu-setting-item">
                        <label for="tmu_dramas">
                            <strong><?php _e('Dramas', 'tmu'); ?></strong>
                            <p class="description"><?php _e('Enable drama series database functionality', 'tmu'); ?></p>
                        </label>
                        <div class="tmu-toggle">
                            <input type="checkbox" 
                                   id="tmu_dramas" 
                                   name="tmu_dramas" 
                                   value="on" 
                                   <?php checked($options['tmu_dramas'], 'on'); ?>
                                   data-setting="tmu_dramas">
                            <span class="tmu-toggle-slider"></span>
                        </div>
                    </div>
                </div>
                
                <div class="tmu-settings-sidebar">
                    <div class="tmu-settings-box">
                        <h3><?php _e('Quick Actions', 'tmu'); ?></h3>
                        <p><a href="<?php echo admin_url('admin.php?page=tmu-migration'); ?>" class="button"><?php _e('Database Migration', 'tmu'); ?></a></p>
                        <p><a href="<?php echo admin_url('admin.php?page=tmu-api-settings'); ?>" class="button"><?php _e('API Settings', 'tmu'); ?></a></p>
                        <p><a href="<?php echo admin_url('admin.php?page=tmu-seo-settings'); ?>" class="button"><?php _e('SEO Settings', 'tmu'); ?></a></p>
                    </div>
                    
                    <div class="tmu-settings-box">
                        <h3><?php _e('Documentation', 'tmu'); ?></h3>
                        <p><a href="#" target="_blank"><?php _e('Theme Documentation', 'tmu'); ?></a></p>
                        <p><a href="#" target="_blank"><?php _e('API Documentation', 'tmu'); ?></a></p>
                        <p><a href="#" target="_blank"><?php _e('Support Forum', 'tmu'); ?></a></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render API settings page
     */
    public function renderApiSettingsPage(): void {
        $api_key = get_option('tmu_tmdb_api_key', '');
        $cache_duration = get_option('tmu_cache_duration', 3600);
        
        ?>
        <div class="wrap tmu-settings">
            <h1><?php _e('TMU API Settings', 'tmu'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('tmu_api_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('TMDB API Key', 'tmu'); ?></th>
                        <td>
                            <input type="text" 
                                   name="tmu_tmdb_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php _e('Enter your TMDB API key to enable automatic data fetching.', 'tmu'); ?>
                                <a href="https://www.themoviedb.org/settings/api" target="_blank"><?php _e('Get API Key', 'tmu'); ?></a>
                            </p>
                            <button type="button" id="test-api" class="button"><?php _e('Test Connection', 'tmu'); ?></button>
                            <span id="api-test-result"></span>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Cache Duration', 'tmu'); ?></th>
                        <td>
                            <input type="number" 
                                   name="tmu_cache_duration" 
                                   value="<?php echo esc_attr($cache_duration); ?>" 
                                   min="300" 
                                   max="86400" />
                            <p class="description"><?php _e('Cache duration in seconds (300-86400).', 'tmu'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render SEO settings page
     */
    public function renderSeoSettingsPage(): void {
        // SEO settings implementation
        echo '<div class="wrap"><h1>SEO Settings</h1><p>SEO settings will be implemented here.</p></div>';
    }
}
```

## Configuration Files

### Theme Options (`includes/config/theme-options.php`)
```php
<?php
/**
 * TMU Theme Options Configuration
 */

return [
    'tmu_movies' => [
        'default' => 'off',
        'type' => 'boolean',
        'description' => 'Enable Movies post type'
    ],
    'tmu_tv_series' => [
        'default' => 'off',
        'type' => 'boolean', 
        'description' => 'Enable TV Series post type'
    ],
    'tmu_dramas' => [
        'default' => 'off',
        'type' => 'boolean',
        'description' => 'Enable Dramas post type'
    ],
    'tmu_enable_ratings' => [
        'default' => 'on',
        'type' => 'boolean',
        'description' => 'Enable rating system'
    ],
    'tmu_enable_comments' => [
        'default' => 'on', 
        'type' => 'boolean',
        'description' => 'Enable comments system'
    ],
    'tmu_tmdb_api_key' => [
        'default' => '',
        'type' => 'string',
        'description' => 'TMDB API Key'
    ],
    'tmu_cache_duration' => [
        'default' => 3600,
        'type' => 'integer',
        'description' => 'Cache duration in seconds'
    ]
];
```

## Assets Integration

### Admin Settings CSS (`assets/css/admin-settings.css`)
```css
.tmu-settings-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.tmu-settings-content {
    flex: 2;
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.tmu-settings-sidebar {
    flex: 1;
}

.tmu-settings-box {
    background: #fff;
    padding: 15px;
    margin-bottom: 15px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.tmu-setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.tmu-setting-item:last-child {
    border-bottom: none;
}

.tmu-toggle {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.tmu-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.tmu-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.tmu-toggle-slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

.tmu-toggle input:checked + .tmu-toggle-slider {
    background-color: #2196F3;
}

.tmu-toggle input:checked + .tmu-toggle-slider:before {
    transform: translateX(26px);
}
```

### Admin Settings JavaScript (`assets/js/admin-settings.js`)
```javascript
jQuery(document).ready(function($) {
    // Handle toggle changes
    $('.tmu-toggle input[type="checkbox"]').on('change', function() {
        const setting = $(this).data('setting');
        const value = $(this).is(':checked') ? 'on' : 'off';
        
        $.ajax({
            url: tmuSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'tmu_update_settings',
                setting: setting,
                value: value,
                nonce: tmuSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showNotice('Setting updated successfully', 'success');
                } else {
                    showNotice('Failed to update setting', 'error');
                }
            },
            error: function() {
                showNotice('An error occurred', 'error');
            }
        });
    });
    
    // Test API connection
    $('#test-api').on('click', function() {
        const apiKey = $('input[name="tmu_tmdb_api_key"]').val();
        const button = $(this);
        const result = $('#api-test-result');
        
        if (!apiKey) {
            result.html('<span style="color: red;">Please enter an API key</span>');
            return;
        }
        
        button.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: tmuSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'tmu_test_api',
                api_key: apiKey,
                nonce: tmuSettings.apiNonce
            },
            success: function(response) {
                if (response.success) {
                    result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                } else {
                    result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                }
            },
            error: function() {
                result.html('<span style="color: red;">✗ Connection failed</span>');
            },
            complete: function() {
                button.prop('disabled', false).text('Test Connection');
            }
        });
    });
    
    // Show notice helper
    function showNotice(message, type) {
        const notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after(notice);
        
        setTimeout(function() {
            notice.fadeOut();
        }, 3000);
    }
});
```

## Integration with Theme Core

### Loading in ThemeCore (`includes/classes/ThemeCore.php`)
```php
// In the loadDependencies method:
require_once TMU_INCLUDES_DIR . '/classes/ThemeInitializer.php';

// In the __construct method:
ThemeInitializer::getInstance();
```

## Testing and Verification

### Initialization Test (`tests/InitializationTest.php`)
```php
<?php
namespace TMU\Tests;

use PHPUnit\Framework\TestCase;
use TMU\ThemeInitializer;
use TMU\Config\ThemeConfig;

class InitializationTest extends TestCase {
    public function testThemeActivation(): void {
        $initializer = ThemeInitializer::getInstance();
        $initializer->onThemeActivation();
        
        // Verify default options are set
        $this->assertEquals('off', get_option('tmu_movies'));
        $this->assertEquals('off', get_option('tmu_tv_series'));
        $this->assertEquals('off', get_option('tmu_dramas'));
        
        // Verify theme version is set
        $this->assertEquals(TMU_VERSION, get_option('tmu_theme_version'));
    }
    
    public function testSettingsMigration(): void {
        // Set up plugin options
        update_option('tmu_movies', 'on');
        update_option('tmu_tv_series', 'on');
        
        $initializer = ThemeInitializer::getInstance();
        $initializer->onThemeActivation();
        
        // Verify migration
        $this->assertEquals('on', get_option('tmu_movies'));
        $this->assertEquals('on', get_option('tmu_tv_series'));
    }
}
```

## Next Steps

1. **[Step 04: Autoloading and Namespace Setup](./04_autoloading-and-namespace-setup.md)** - Configure PSR-4 autoloading
2. **[Step 05: Post Types Registration](./05_post-types-registration.md)** - Register custom post types  
3. **[Step 06: Taxonomies Registration](./06_taxonomies-registration.md)** - Register custom taxonomies

## Verification Checklist

- [ ] ThemeInitializer class implemented
- [ ] Settings migration system operational
- [ ] Admin settings pages functional
- [ ] AJAX settings updates working
- [ ] Default theme options configured
- [ ] Image sizes and theme support added
- [ ] Navigation menus registered
- [ ] Text domain loaded
- [ ] Database integrity checks implemented
- [ ] API testing functionality working

---

This initialization system ensures a smooth transition from plugin to theme while maintaining all settings and providing an enhanced admin experience.