<?php
/**
 * TMU Core Helper Functions
 *
 * @package TMU
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get TMU option with fallback
 *
 * @param string $option_name Option name
 * @param mixed $default Default value
 * @return mixed
 */
function tmu_get_option(string $option_name, $default = null) {
    if (class_exists('TMU\\Config\\ThemeConfig')) {
        $config = TMU\Config\ThemeConfig::getInstance();
        $defaults = $config->getDefaultSettings();
        
        $default_value = $defaults[$option_name]['default'] ?? $default;
        return get_option($option_name, $default_value);
    }
    
    return get_option($option_name, $default);
}

/**
 * Update TMU option
 *
 * @param string $option_name Option name
 * @param mixed $value Option value
 * @return bool
 */
function tmu_update_option(string $option_name, $value): bool {
    return update_option($option_name, $value);
}

/**
 * Check if TMU feature is enabled
 *
 * @param string $feature Feature name (movies, tv_series, dramas)
 * @return bool
 */
function tmu_is_feature_enabled(string $feature): bool {
    if (class_exists('TMU\\Config\\ThemeConfig')) {
        $config = TMU\Config\ThemeConfig::getInstance();
        return $config->isFeatureEnabled($feature);
    }
    
    // Fallback
    return tmu_get_option("tmu_{$feature}", 'off') === 'on';
}

/**
 * Get TMDB API key
 *
 * @return string
 */
function tmu_get_tmdb_api_key(): string {
    if (class_exists('TMU\\Config\\ThemeConfig')) {
        $config = TMU\Config\ThemeConfig::getInstance();
        return $config->getTmdbApiKey();
    }
    
    return tmu_get_option('tmu_tmdb_api_key', '');
}

/**
 * Log TMU message
 *
 * @param string $message Message to log
 * @param string $level Log level (info, warning, error, debug)
 */
function tmu_log(string $message, string $level = 'info'): void {
    if (class_exists('TMU\\Utils\\Logger')) {
        TMU\Utils\Logger::getInstance()->log($level, $message);
    } else {
        // Fallback to error_log
        $prefix = strtoupper($level);
        error_log("TMU [{$prefix}]: {$message}");
    }
}

/**
 * Sanitize TMU input
 *
 * @param mixed $input Input to sanitize
 * @param string $type Sanitization type
 * @return mixed
 */
function tmu_sanitize($input, string $type = 'text') {
    if (class_exists('TMU\\Utils\\Sanitizer')) {
        return TMU\Utils\Sanitizer::sanitize($input, $type);
    }
    
    // Fallback sanitization
    switch ($type) {
        case 'email':
            return sanitize_email($input);
        case 'url':
            return esc_url_raw($input);
        case 'textarea':
            return sanitize_textarea_field($input);
        case 'html':
            return wp_kses_post($input);
        case 'int':
            return intval($input);
        case 'float':
            return floatval($input);
        case 'array':
            return is_array($input) ? array_map('sanitize_text_field', $input) : [];
        case 'json':
            if (is_string($input)) {
                $decoded = json_decode($input, true);
                return is_array($decoded) ? $decoded : [];
            }
            return is_array($input) ? $input : [];
        default:
            return sanitize_text_field($input);
    }
}

/**
 * Validate TMU data
 *
 * @param mixed $data Data to validate
 * @param array $rules Validation rules
 * @return bool|WP_Error
 */
function tmu_validate($data, array $rules) {
    if (class_exists('TMU\\Utils\\Validator')) {
        return TMU\Utils\Validator::validate($data, $rules);
    }
    
    // Basic validation fallback
    foreach ($rules as $rule) {
        if ($rule === 'required' && empty($data)) {
            return new WP_Error('validation_failed', 'Required field is empty');
        }
    }
    
    return true;
}

/**
 * Get TMU cache
 *
 * @param string $key Cache key
 * @param mixed $default Default value
 * @return mixed
 */
function tmu_get_cache(string $key, $default = null) {
    return get_transient("tmu_{$key}") ?: $default;
}

/**
 * Set TMU cache
 *
 * @param string $key Cache key
 * @param mixed $value Value to cache
 * @param int $expiration Expiration time in seconds
 * @return bool
 */
function tmu_set_cache(string $key, $value, int $expiration = null): bool {
    if ($expiration === null) {
        $expiration = tmu_get_option('tmu_cache_timeout', 3600);
    }
    
    return set_transient("tmu_{$key}", $value, $expiration);
}

/**
 * Delete TMU cache
 *
 * @param string $key Cache key
 * @return bool
 */
function tmu_delete_cache(string $key): bool {
    return delete_transient("tmu_{$key}");
}

/**
 * Clear all TMU cache
 */
function tmu_clear_all_cache(): void {
    global $wpdb;
    
    // Delete all TMU transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tmu_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_tmu_%'");
    
    // Clear object cache if available
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    tmu_log('All TMU cache cleared', 'info');
}

/**
 * Get TMU version
 *
 * @return string
 */
function tmu_get_version(): string {
    return TMU_VERSION;
}

/**
 * Check if in development mode
 *
 * @return bool
 */
function tmu_is_development(): bool {
    return defined('WP_DEBUG') && WP_DEBUG;
}

/**
 * Get TMU URL
 *
 * @param string $path Optional path to append
 * @return string
 */
function tmu_get_url(string $path = ''): string {
    return TMU_THEME_URL . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Get TMU asset URL
 *
 * @param string $asset Asset path
 * @return string
 */
function tmu_get_asset_url(string $asset): string {
    return TMU_ASSETS_URL . '/' . ltrim($asset, '/');
}

/**
 * Get TMU build asset URL
 *
 * @param string $asset Asset path
 * @return string
 */
function tmu_get_build_asset_url(string $asset): string {
    return TMU_ASSETS_BUILD_URL . '/' . ltrim($asset, '/');
}

/**
 * Check if TMDB API is available
 *
 * @return bool
 */
function tmu_is_tmdb_available(): bool {
    $api_key = tmu_get_tmdb_api_key();
    return !empty($api_key) && tmu_get_option('tmu_tmdb_api_enabled', false);
}

/**
 * Format rating for display
 *
 * @param float $rating Rating value
 * @param int $precision Decimal precision
 * @return string
 */
function tmu_format_rating(float $rating, int $precision = 1): string {
    return number_format($rating, $precision);
}

/**
 * Format duration in minutes to human readable
 *
 * @param int $minutes Duration in minutes
 * @return string
 */
function tmu_format_duration(int $minutes): string {
    if ($minutes < 60) {
        return sprintf(_n('%d minute', '%d minutes', $minutes, 'tmu'), $minutes);
    }
    
    $hours = floor($minutes / 60);
    $remaining_minutes = $minutes % 60;
    
    if ($remaining_minutes === 0) {
        return sprintf(_n('%d hour', '%d hours', $hours, 'tmu'), $hours);
    }
    
    return sprintf(
        __('%d hour %d minutes', 'tmu'),
        $hours,
        $remaining_minutes
    );
}

/**
 * Format file size
 *
 * @param int $bytes File size in bytes
 * @param int $precision Decimal precision
 * @return string
 */
function tmu_format_file_size(int $bytes, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Generate nonce for TMU actions
 *
 * @param string $action Action name
 * @return string
 */
function tmu_create_nonce(string $action): string {
    return wp_create_nonce("tmu_{$action}");
}

/**
 * Verify TMU nonce
 *
 * @param string $nonce Nonce value
 * @param string $action Action name
 * @return bool
 */
function tmu_verify_nonce(string $nonce, string $action): bool {
    return wp_verify_nonce($nonce, "tmu_{$action}") !== false;
}

/**
 * Get current user capabilities for TMU
 *
 * @return array
 */
function tmu_get_user_capabilities(): array {
    if (!is_user_logged_in()) {
        return [];
    }
    
    $capabilities = [];
    $user = wp_get_current_user();
    
    $tmu_caps = [
        'manage_tmu_settings' => 'manage_options',
        'edit_tmu_content' => 'edit_posts',
        'publish_tmu_content' => 'publish_posts',
        'delete_tmu_content' => 'delete_posts',
    ];
    
    foreach ($tmu_caps as $tmu_cap => $wp_cap) {
        $capabilities[$tmu_cap] = $user->has_cap($wp_cap);
    }
    
    return $capabilities;
}

/**
 * Check if user can perform TMU action
 *
 * @param string $capability Capability name
 * @return bool
 */
function tmu_user_can(string $capability): bool {
    $capabilities = tmu_get_user_capabilities();
    return $capabilities[$capability] ?? false;
}

/**
 * Get TMU post types
 *
 * @return array
 */
function tmu_get_post_types(): array {
    $post_types = [];
    
    if (tmu_is_feature_enabled('movies')) {
        $post_types[] = 'movie';
    }
    
    if (tmu_is_feature_enabled('tv_series')) {
        $post_types[] = 'tv_series';
    }
    
    if (tmu_is_feature_enabled('dramas')) {
        $post_types[] = 'drama';
    }
    
    $post_types[] = 'person';
    
    return $post_types;
}

/**
 * Get TMU taxonomies
 *
 * @return array
 */
function tmu_get_taxonomies(): array {
    return [
        'genre',
        'country',
        'language',
        'production_company',
        'director',
        'writer',
        'network',
    ];
}

/**
 * Debug function for development
 *
 * @param mixed $data Data to debug
 * @param string $label Optional label
 */
function tmu_debug($data, string $label = ''): void {
    if (!tmu_is_development()) {
        return;
    }
    
    $debug_output = $label ? "TMU Debug - {$label}: " : "TMU Debug: ";
    $debug_output .= print_r($data, true);
    
    error_log($debug_output);
}

/**
 * Get memory usage for debugging
 *
 * @param bool $formatted Whether to format the output
 * @return string|int
 */
function tmu_get_memory_usage(bool $formatted = true) {
    $memory = memory_get_usage(true);
    
    if ($formatted) {
        return tmu_format_file_size($memory);
    }
    
    return $memory;
}