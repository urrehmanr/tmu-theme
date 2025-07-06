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
        
        // Load default configuration if files don't exist
        if (!isset($this->config['theme-options'])) {
            $this->config['theme-options'] = $this->getDefaultThemeOptions();
        }
    }
    
    /**
     * Get default theme options
     */
    private function getDefaultThemeOptions(): array {
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
            'tmu_enable_ajax_search' => [
                'default' => 'on',
                'type' => 'boolean',
                'description' => 'Enable AJAX search functionality'
            ],
            'tmu_tmdb_api_key' => [
                'default' => '',
                'type' => 'string',
                'description' => 'TMDB API Key'
            ],
            'tmu_images_per_page' => [
                'default' => 20,
                'type' => 'integer',
                'description' => 'Number of images per page'
            ],
            'tmu_cache_duration' => [
                'default' => 3600,
                'type' => 'integer',
                'description' => 'Cache duration in seconds'
            ],
            'tmu_seo_enabled' => [
                'default' => 'on',
                'type' => 'boolean',
                'description' => 'Enable SEO features'
            ],
            'tmu_email' => [
                'default' => '',
                'type' => 'string',
                'description' => 'Contact email address'
            ]
        ];
    }
    
    /**
     * Get configuration value
     */
    public function get(string $key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * Set configuration value
     */
    public function set(string $key, $value): void {
        $this->config[$key] = $value;
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
    
    /**
     * Get all enabled features
     */
    public function getEnabledFeatures(): array {
        $enabled = [];
        $features = ['movies', 'tv_series', 'dramas'];
        
        foreach ($features as $feature) {
            if ($this->isFeatureEnabled($feature)) {
                $enabled[] = $feature;
            }
        }
        
        return $enabled;
    }
    
    /**
     * Get theme version
     */
    public function getThemeVersion(): string {
        return get_option('tmu_theme_version', TMU_VERSION);
    }
    
    /**
     * Get installation date
     */
    public function getInstallDate(): string {
        return get_option('tmu_install_date', '');
    }
    
    /**
     * Check if theme is properly configured
     */
    public function isConfigured(): bool {
        // Check if essential settings are configured
        $api_key = $this->getTmdbApiKey();
        $enabled_features = $this->getEnabledFeatures();
        
        return !empty($api_key) && !empty($enabled_features);
    }
    
    /**
     * Get configuration status
     */
    public function getConfigurationStatus(): array {
        return [
            'theme_version' => $this->getThemeVersion(),
            'install_date' => $this->getInstallDate(),
            'api_key_configured' => !empty($this->getTmdbApiKey()),
            'enabled_features' => $this->getEnabledFeatures(),
            'is_configured' => $this->isConfigured(),
            'cache_duration' => $this->getCacheDuration()
        ];
    }
    
    /**
     * Validate configuration
     */
    public function validateConfiguration(): array {
        $errors = [];
        
        // Validate API key format
        $api_key = $this->getTmdbApiKey();
        if (!empty($api_key) && strlen($api_key) !== 32) {
            $errors[] = 'TMDB API key should be 32 characters long';
        }
        
        // Validate cache duration
        $cache_duration = $this->getCacheDuration();
        if ($cache_duration < 300 || $cache_duration > 86400) {
            $errors[] = 'Cache duration should be between 300 and 86400 seconds';
        }
        
        // Check if at least one feature is enabled
        $enabled_features = $this->getEnabledFeatures();
        if (empty($enabled_features)) {
            $errors[] = 'At least one content type (Movies, TV Series, or Dramas) should be enabled';
        }
        
        return $errors;
    }
}