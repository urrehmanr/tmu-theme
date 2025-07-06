<?php
/**
 * TMU Settings API
 *
 * @package TMU\Admin
 * @version 1.0.0
 */

namespace TMU\Admin;

use TMU\Config\ThemeConfig;
use TMU\Config\DefaultSettings;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings API Class
 */
class SettingsAPI {
    
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
    public static function getInstance(): SettingsAPI {
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
        // AJAX handlers for logged-in users
        add_action('wp_ajax_tmu_update_setting', [$this, 'updateSetting']);
        add_action('wp_ajax_tmu_get_setting', [$this, 'getSetting']);
        add_action('wp_ajax_tmu_reset_settings', [$this, 'resetSettings']);
        add_action('wp_ajax_tmu_export_settings', [$this, 'exportSettings']);
        add_action('wp_ajax_tmu_import_settings', [$this, 'importSettings']);
        add_action('wp_ajax_tmu_test_tmdb_api', [$this, 'testTmdbApi']);
        add_action('wp_ajax_tmu_validate_settings', [$this, 'validateSettings']);
        
        // REST API endpoints
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
    }
    
    /**
     * Register REST API routes
     */
    public function registerRestRoutes(): void {
        register_rest_route('tmu/v1', '/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'getSettingsRest'],
            'permission_callback' => [$this, 'checkPermissions']
        ]);
        
        register_rest_route('tmu/v1', '/settings/(?P<setting>[a-zA-Z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'getSettingRest'],
            'permission_callback' => [$this, 'checkPermissions']
        ]);
        
        register_rest_route('tmu/v1', '/settings/(?P<setting>[a-zA-Z0-9_-]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'updateSettingRest'],
            'permission_callback' => [$this, 'checkPermissions']
        ]);
    }
    
    /**
     * Check permissions
     */
    public function checkPermissions(): bool {
        return current_user_can('manage_options');
    }
    
    /**
     * Update setting via AJAX
     */
    public function updateSetting(): void {
        check_ajax_referer('tmu_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $setting = sanitize_text_field($_POST['setting'] ?? '');
        $value = sanitize_text_field($_POST['value'] ?? '');
        
        if (empty($setting)) {
            wp_send_json_error(['message' => 'Setting name is required']);
        }
        
        // Validate setting exists
        if (!DefaultSettings::settingExists($setting)) {
            wp_send_json_error(['message' => 'Invalid setting name']);
        }
        
        // Validate value based on setting type
        $setting_config = DefaultSettings::getSettingConfig($setting);
        $validated_value = $this->validateSettingValue($value, $setting_config);
        
        if ($validated_value === false) {
            wp_send_json_error(['message' => 'Invalid setting value']);
        }
        
        // Update the setting
        update_option($setting, $validated_value);
        
        // Log the change
        error_log("TMU: Setting '{$setting}' updated to '{$validated_value}' by user " . get_current_user_id());
        
        wp_send_json_success([
            'message' => 'Setting updated successfully',
            'setting' => $setting,
            'value' => $validated_value
        ]);
    }
    
    /**
     * Get setting via AJAX
     */
    public function getSetting(): void {
        check_ajax_referer('tmu_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $setting = sanitize_text_field($_POST['setting'] ?? '');
        
        if (empty($setting)) {
            wp_send_json_error(['message' => 'Setting name is required']);
        }
        
        if (!DefaultSettings::settingExists($setting)) {
            wp_send_json_error(['message' => 'Invalid setting name']);
        }
        
        $default = DefaultSettings::getDefault($setting);
        $value = get_option($setting, $default);
        
        wp_send_json_success([
            'setting' => $setting,
            'value' => $value,
            'default' => $default
        ]);
    }
    
    /**
     * Reset settings to defaults
     */
    public function resetSettings(): void {
        check_ajax_referer('tmu_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $section = sanitize_text_field($_POST['section'] ?? '');
        
        if (empty($section)) {
            // Reset all settings
            $all_settings = DefaultSettings::getFlatDefaults();
            foreach ($all_settings as $setting => $config) {
                update_option($setting, $config['default']);
            }
            $reset_count = count($all_settings);
        } else {
            // Reset specific section
            $section_settings = DefaultSettings::getSettingsBySection($section);
            foreach ($section_settings as $setting => $config) {
                update_option($setting, $config['default']);
            }
            $reset_count = count($section_settings);
        }
        
        error_log("TMU: {$reset_count} settings reset to defaults by user " . get_current_user_id());
        
        wp_send_json_success([
            'message' => "Successfully reset {$reset_count} settings to defaults",
            'reset_count' => $reset_count
        ]);
    }
    
    /**
     * Export settings
     */
    public function exportSettings(): void {
        check_ajax_referer('tmu_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $all_settings = DefaultSettings::getFlatDefaults();
        $export_data = [];
        
        foreach ($all_settings as $setting => $config) {
            $export_data[$setting] = get_option($setting, $config['default']);
        }
        
        $export_data['_export_meta'] = [
            'version' => TMU_VERSION,
            'date' => current_time('mysql'),
            'site_url' => site_url()
        ];
        
        wp_send_json_success([
            'message' => 'Settings exported successfully',
            'data' => $export_data,
            'filename' => 'tmu-settings-' . date('Y-m-d-H-i-s') . '.json'
        ]);
    }
    
    /**
     * Import settings
     */
    public function importSettings(): void {
        check_ajax_referer('tmu_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $import_data = json_decode(stripslashes($_POST['data'] ?? ''), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => 'Invalid JSON data']);
        }
        
        if (!is_array($import_data)) {
            wp_send_json_error(['message' => 'Invalid import data format']);
        }
        
        // Validate and import settings
        $imported_count = 0;
        $errors = [];
        
        foreach ($import_data as $setting => $value) {
            // Skip meta information
            if ($setting === '_export_meta') {
                continue;
            }
            
            if (!DefaultSettings::settingExists($setting)) {
                $errors[] = "Unknown setting: {$setting}";
                continue;
            }
            
            $setting_config = DefaultSettings::getSettingConfig($setting);
            $validated_value = $this->validateSettingValue($value, $setting_config);
            
            if ($validated_value === false) {
                $errors[] = "Invalid value for setting: {$setting}";
                continue;
            }
            
            update_option($setting, $validated_value);
            $imported_count++;
        }
        
        error_log("TMU: {$imported_count} settings imported by user " . get_current_user_id());
        
        wp_send_json_success([
            'message' => "Successfully imported {$imported_count} settings",
            'imported_count' => $imported_count,
            'errors' => $errors
        ]);
    }
    
    /**
     * Test TMDB API
     */
    public function testTmdbApi(): void {
        check_ajax_referer('tmu_api_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => 'API key is required']);
        }
        
        // Test TMDB API connection
        $test_url = "https://api.themoviedb.org/3/configuration?api_key=" . $api_key;
        $response = wp_remote_get($test_url, [
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'TMU Theme/' . TMU_VERSION
            ]
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => 'API connection failed: ' . $response->get_error_message()
            ]);
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($response_code !== 200) {
            wp_send_json_error([
                'message' => 'API request failed with code: ' . $response_code
            ]);
        }
        
        if (!isset($data['images'])) {
            wp_send_json_error([
                'message' => 'Invalid API response or invalid API key'
            ]);
        }
        
        wp_send_json_success([
            'message' => 'API connection successful',
            'api_info' => [
                'base_url' => $data['images']['base_url'] ?? '',
                'poster_sizes' => $data['images']['poster_sizes'] ?? [],
                'backdrop_sizes' => $data['images']['backdrop_sizes'] ?? []
            ]
        ]);
    }
    
    /**
     * Validate settings
     */
    public function validateSettings(): void {
        check_ajax_referer('tmu_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $validation_errors = $this->config->validateConfiguration();
        
        if (empty($validation_errors)) {
            wp_send_json_success([
                'message' => 'All settings are valid',
                'valid' => true
            ]);
        } else {
            wp_send_json_success([
                'message' => 'Validation completed with errors',
                'valid' => false,
                'errors' => $validation_errors
            ]);
        }
    }
    
    /**
     * Validate setting value
     */
    private function validateSettingValue($value, array $config) {
        $type = $config['type'] ?? 'string';
        
        switch ($type) {
            case 'boolean':
                return in_array($value, ['on', 'off', '1', '0', 'true', 'false']) ? $value : false;
                
            case 'integer':
                return is_numeric($value) ? (int) $value : false;
                
            case 'string':
                return is_string($value) ? $value : false;
                
            default:
                return $value;
        }
    }
    
    /**
     * Get settings via REST API
     */
    public function getSettingsRest($request) {
        $all_settings = DefaultSettings::getFlatDefaults();
        $settings_data = [];
        
        foreach ($all_settings as $setting => $config) {
            $settings_data[$setting] = [
                'value' => get_option($setting, $config['default']),
                'default' => $config['default'],
                'type' => $config['type'],
                'description' => $config['description']
            ];
        }
        
        return rest_ensure_response($settings_data);
    }
    
    /**
     * Get single setting via REST API
     */
    public function getSettingRest($request) {
        $setting = $request->get_param('setting');
        
        if (!DefaultSettings::settingExists($setting)) {
            return new \WP_Error('invalid_setting', 'Invalid setting name', ['status' => 404]);
        }
        
        $config = DefaultSettings::getSettingConfig($setting);
        $value = get_option($setting, $config['default']);
        
        return rest_ensure_response([
            'setting' => $setting,
            'value' => $value,
            'default' => $config['default'],
            'type' => $config['type'],
            'description' => $config['description']
        ]);
    }
    
    /**
     * Update setting via REST API
     */
    public function updateSettingRest($request) {
        $setting = $request->get_param('setting');
        $value = $request->get_param('value');
        
        if (!DefaultSettings::settingExists($setting)) {
            return new \WP_Error('invalid_setting', 'Invalid setting name', ['status' => 404]);
        }
        
        $config = DefaultSettings::getSettingConfig($setting);
        $validated_value = $this->validateSettingValue($value, $config);
        
        if ($validated_value === false) {
            return new \WP_Error('invalid_value', 'Invalid setting value', ['status' => 400]);
        }
        
        update_option($setting, $validated_value);
        
        return rest_ensure_response([
            'message' => 'Setting updated successfully',
            'setting' => $setting,
            'value' => $validated_value
        ]);
    }
}