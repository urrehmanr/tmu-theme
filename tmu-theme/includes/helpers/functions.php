<?php
/**
 * TMU Theme Helper Functions
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Log a message with TMU theme context
 *
 * @param string $message Log message
 * @param string $level Log level (info, warning, error, debug)
 * @param array $context Additional context data
 */
function tmu_log(string $message, string $level = 'info', array $context = []): void {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    $log_message = sprintf('[TMU %s] %s', strtoupper($level), $message);
    
    if (!empty($context)) {
        $log_message .= ' Context: ' . wp_json_encode($context);
    }
    
    error_log($log_message);
}

/**
 * Get TMU theme option with default fallback
 *
 * @param string $key Option key
 * @param mixed $default Default value
 * @return mixed Option value
 */
function tmu_get_option(string $key, $default = null) {
    return get_option($key, $default);
}

/**
 * Set TMU theme option
 *
 * @param string $key Option key
 * @param mixed $value Option value
 * @return bool Success status
 */
function tmu_set_option(string $key, $value): bool {
    return update_option($key, $value);
}

/**
 * Check if TMDB API is available and configured
 *
 * @return bool TMDB availability status
 */
function tmu_is_tmdb_available(): bool {
    $api_key = tmu_get_option('tmu_tmdb_api_key', '');
    return !empty($api_key);
}

/**
 * Delete cache for a specific key
 *
 * @param string $key Cache key
 * @return bool Success status
 */
function tmu_delete_cache(string $key): bool {
    return wp_cache_delete($key, 'tmu');
}

/**
 * Get cache for a specific key
 *
 * @param string $key Cache key
 * @return mixed Cached value or false
 */
function tmu_get_cache(string $key) {
    return wp_cache_get($key, 'tmu');
}

/**
 * Set cache for a specific key
 *
 * @param string $key Cache key
 * @param mixed $value Value to cache
 * @param int $expiration Expiration time in seconds
 * @return bool Success status
 */
function tmu_set_cache(string $key, $value, int $expiration = 3600): bool {
    return wp_cache_set($key, $value, 'tmu', $expiration);
}

/**
 * Format bytes to human readable format
 *
 * @param int $bytes Number of bytes
 * @return string Formatted string
 */
function tmu_format_bytes(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Check if we're in development mode
 *
 * @return bool Development mode status
 */
function tmu_is_dev_mode(): bool {
    return defined('WP_DEBUG') && WP_DEBUG;
}

/**
 * Get theme version
 *
 * @return string Theme version
 */
function tmu_get_version(): string {
    return defined('TMU_VERSION') ? TMU_VERSION : '1.0.0';
}

/**
 * Check if a feature is enabled in theme settings
 *
 * @param string $feature Feature name
 * @return bool Feature status
 */
function tmu_is_feature_enabled(string $feature): bool {
    return tmu_get_option("tmu_{$feature}", 'off') === 'on';
}

/**
 * Sanitize and validate input
 *
 * @param mixed $input Input to sanitize
 * @param string $type Data type (string, int, email, url, etc.)
 * @return mixed Sanitized input
 */
function tmu_sanitize_input($input, string $type = 'string') {
    switch ($type) {
        case 'string':
            return sanitize_text_field($input);
        case 'textarea':
            return sanitize_textarea_field($input);
        case 'email':
            return sanitize_email($input);
        case 'url':
            return esc_url_raw($input);
        case 'int':
        case 'integer':
            return (int) $input;
        case 'float':
            return (float) $input;
        case 'bool':
        case 'boolean':
            return (bool) $input;
        case 'array':
            return is_array($input) ? $input : [];
        default:
            return $input;
    }
}

/**
 * Generate a nonce for TMU actions
 *
 * @param string $action Action name
 * @return string Nonce value
 */
function tmu_create_nonce(string $action): string {
    return wp_create_nonce("tmu_{$action}");
}

/**
 * Verify a TMU nonce
 *
 * @param string $nonce Nonce to verify
 * @param string $action Action name
 * @return bool Verification result
 */
function tmu_verify_nonce(string $nonce, string $action): bool {
    return wp_verify_nonce($nonce, "tmu_{$action}") !== false;
}

/**
 * Get current user capabilities for TMU
 *
 * @return array User capabilities
 */
function tmu_get_user_capabilities(): array {
    if (!is_user_logged_in()) {
        return [];
    }
    
    $user = wp_get_current_user();
    return $user->allcaps ?? [];
}

/**
 * Check if current user can perform TMU action
 *
 * @param string $capability Required capability
 * @return bool Permission status
 */
function tmu_current_user_can(string $capability): bool {
    return current_user_can($capability);
}

/**
 * Format runtime in minutes to human readable format
 *
 * @param int $minutes Runtime in minutes
 * @return string Formatted runtime
 */
function tmu_format_runtime(int $minutes): string {
    if ($minutes <= 0) {
        return '';
    }
    
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    if ($hours > 0) {
        return $hours . 'h' . ($mins > 0 ? ' ' . $mins . 'm' : '');
    }
    
    return $mins . 'm';
}

/**
 * Get asset URL with cache busting
 *
 * @param string $asset Asset path relative to assets directory
 * @return string Full asset URL
 */
function tmu_asset_url(string $asset): string {
    $base_url = defined('TMU_ASSETS_BUILD_URL') ? TMU_ASSETS_BUILD_URL : TMU_ASSETS_URL;
    $version = tmu_get_version();
    
    return $base_url . '/' . ltrim($asset, '/') . '?v=' . $version;
}

/**
 * Get template part with TMU theme context
 *
 * @param string $slug Template slug
 * @param string $name Template name
 * @param array $args Arguments to pass to template
 */
function tmu_get_template_part(string $slug, string $name = '', array $args = []): void {
    if (!empty($args)) {
        set_query_var('tmu_template_args', $args);
    }
    
    get_template_part($slug, $name);
    
    if (!empty($args)) {
        set_query_var('tmu_template_args', null);
    }
}

/**
 * Get template arguments in template files
 *
 * @return array Template arguments
 */
function tmu_get_template_args(): array {
    return get_query_var('tmu_template_args', []);
}

/**
 * Get file version based on file modification time
 * 
 * @param string $file_path Path to file relative to theme directory
 * @return string File version
 */
function get_theme_file_version(string $file_path): string {
    $absolute_path = get_template_directory() . '/' . $file_path;
    
    if (file_exists($absolute_path)) {
        return (string) filemtime($absolute_path);
    }
    
    return TMU_VERSION ?? '1.0.0';
}

/**
 * Debug post types and taxonomies registration
 * 
 * @return array Registration status
 */
function tmu_debug_registrations(): array {
    global $wp_post_types, $wp_taxonomies;
    
    $result = [
        'post_types' => [],
        'taxonomies' => [],
    ];
    
    // Check post types
    $expected_post_types = [
        'movie', 'tv', 'drama', 'people', 'video', 'season', 'episode', 'drama-episode'
    ];
    
    foreach ($expected_post_types as $post_type) {
        $result['post_types'][$post_type] = [
            'registered' => post_type_exists($post_type),
            'object' => isset($wp_post_types[$post_type]) ? $wp_post_types[$post_type] : null,
        ];
    }
    
    // Check taxonomies
    $expected_taxonomies = [
        'genre', 'country', 'language', 'network', 'production-company', 
        'by-year', 'profession', 'nationality'
    ];
    
    foreach ($expected_taxonomies as $taxonomy) {
        $result['taxonomies'][$taxonomy] = [
            'registered' => taxonomy_exists($taxonomy),
            'object' => isset($wp_taxonomies[$taxonomy]) ? $wp_taxonomies[$taxonomy] : null,
        ];
    }
    
    // Log the results
    tmu_log('Post types registration status: ' . wp_json_encode($result['post_types']), 'debug');
    tmu_log('Taxonomies registration status: ' . wp_json_encode($result['taxonomies']), 'debug');
    
    return $result;
}