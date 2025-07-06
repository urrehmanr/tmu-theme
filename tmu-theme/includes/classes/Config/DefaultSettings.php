<?php
/**
 * TMU Default Settings
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
 * Default Settings Class
 */
class DefaultSettings {
    
    /**
     * Get all default settings
     */
    public static function getAll(): array {
        return [
            'content_types' => self::getContentTypeDefaults(),
            'api_settings' => self::getApiDefaults(),
            'display_settings' => self::getDisplayDefaults(),
            'seo_settings' => self::getSeoDefaults(),
            'system_settings' => self::getSystemDefaults(),
        ];
    }
    
    /**
     * Get content type defaults
     */
    public static function getContentTypeDefaults(): array {
        return [
            'tmu_movies' => [
                'default' => 'off',
                'type' => 'boolean',
                'description' => 'Enable Movies post type',
                'section' => 'content_types'
            ],
            'tmu_tv_series' => [
                'default' => 'off',
                'type' => 'boolean',
                'description' => 'Enable TV Series post type',
                'section' => 'content_types'
            ],
            'tmu_dramas' => [
                'default' => 'off',
                'type' => 'boolean',
                'description' => 'Enable Dramas post type',
                'section' => 'content_types'
            ],
        ];
    }
    
    /**
     * Get API defaults
     */
    public static function getApiDefaults(): array {
        return [
            'tmu_tmdb_api_key' => [
                'default' => '',
                'type' => 'string',
                'description' => 'TMDB API Key for fetching movie data',
                'section' => 'api_settings'
            ],
            'tmu_cache_duration' => [
                'default' => 3600,
                'type' => 'integer',
                'description' => 'Cache duration in seconds',
                'section' => 'api_settings'
            ],
        ];
    }
    
    /**
     * Get display defaults
     */
    public static function getDisplayDefaults(): array {
        return [
            'tmu_enable_ratings' => [
                'default' => 'on',
                'type' => 'boolean',
                'description' => 'Enable rating system',
                'section' => 'display_settings'
            ],
            'tmu_enable_comments' => [
                'default' => 'on',
                'type' => 'boolean',
                'description' => 'Enable comments system',
                'section' => 'display_settings'
            ],
            'tmu_enable_ajax_search' => [
                'default' => 'on',
                'type' => 'boolean',
                'description' => 'Enable AJAX search functionality',
                'section' => 'display_settings'
            ],
            'tmu_images_per_page' => [
                'default' => 20,
                'type' => 'integer',
                'description' => 'Number of images per page in galleries',
                'section' => 'display_settings'
            ],
        ];
    }
    
    /**
     * Get SEO defaults
     */
    public static function getSeoDefaults(): array {
        return [
            'tmu_seo_enabled' => [
                'default' => 'on',
                'type' => 'boolean',
                'description' => 'Enable SEO features',
                'section' => 'seo_settings'
            ],
        ];
    }
    
    /**
     * Get system defaults
     */
    public static function getSystemDefaults(): array {
        return [
            'tmu_email' => [
                'default' => '',
                'type' => 'string',
                'description' => 'Contact email address',
                'section' => 'system_settings'
            ],
        ];
    }
    
    /**
     * Get flattened defaults (for backward compatibility)
     */
    public static function getFlatDefaults(): array {
        $all_defaults = self::getAll();
        $flattened = [];
        
        foreach ($all_defaults as $section => $settings) {
            foreach ($settings as $key => $config) {
                $flattened[$key] = $config;
            }
        }
        
        return $flattened;
    }
    
    /**
     * Get default value for a specific setting
     */
    public static function getDefault(string $setting_name) {
        $defaults = self::getFlatDefaults();
        return $defaults[$setting_name]['default'] ?? null;
    }
    
    /**
     * Get setting configuration
     */
    public static function getSettingConfig(string $setting_name): array {
        $defaults = self::getFlatDefaults();
        return $defaults[$setting_name] ?? [];
    }
    
    /**
     * Check if setting exists
     */
    public static function settingExists(string $setting_name): bool {
        $defaults = self::getFlatDefaults();
        return isset($defaults[$setting_name]);
    }
    
    /**
     * Get all setting names
     */
    public static function getSettingNames(): array {
        $defaults = self::getFlatDefaults();
        return array_keys($defaults);
    }
    
    /**
     * Get settings by section
     */
    public static function getSettingsBySection(string $section): array {
        $all_defaults = self::getAll();
        return $all_defaults[$section] ?? [];
    }
    
    /**
     * Get available sections
     */
    public static function getSections(): array {
        return array_keys(self::getAll());
    }
}