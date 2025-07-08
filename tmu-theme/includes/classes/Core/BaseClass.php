<?php
/**
 * Base Class for TMU Components
 *
 * @package TMU\Core
 * @version 1.0.0
 */

namespace TMU\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base Class
 * 
 * Provides common functionality for TMU classes including
 * initialization, logging, and utility methods.
 */
class BaseClass {
    
    /**
     * Class initialization timestamp
     *
     * @var float
     */
    protected $init_time;
    
    /**
     * Class options/settings
     *
     * @var array
     */
    protected $options = [];
    
    /**
     * Debug mode flag
     *
     * @var bool
     */
    protected $debug_mode = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_time = microtime(true);
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        $this->init();
    }
    
    /**
     * Initialize the class
     * Override this method in child classes for custom initialization
     */
    protected function init(): void {
        // Default initialization - can be overridden by child classes
    }
    
    /**
     * Log a message
     *
     * @param string $message Log message
     * @param string $level Log level (info, warning, error, debug)
     * @param array $context Additional context data
     */
    protected function log(string $message, string $level = 'info', array $context = []): void {
        if (function_exists('tmu_log')) {
            tmu_log($message, $level, $context);
        } elseif ($this->debug_mode && function_exists('error_log')) {
            $log_message = sprintf('[TMU %s] %s', strtoupper($level), $message);
            if (!empty($context)) {
                $log_message .= ' Context: ' . json_encode($context);
            }
            error_log($log_message);
        }
    }
    
    /**
     * Get option value with default fallback
     *
     * @param string $key Option key
     * @param mixed $default Default value
     * @return mixed Option value
     */
    protected function get_option(string $key, $default = null) {
        if (function_exists('tmu_get_option')) {
            return tmu_get_option($key, $default);
        }
        
        return get_option($key, $default);
    }
    
    /**
     * Set option value
     *
     * @param string $key Option key
     * @param mixed $value Option value
     * @return bool Success status
     */
    protected function set_option(string $key, $value): bool {
        if (function_exists('tmu_set_option')) {
            return tmu_set_option($key, $value);
        }
        
        return update_option($key, $value);
    }
    
    /**
     * Check if a feature is enabled
     *
     * @param string $feature Feature name
     * @return bool Whether feature is enabled
     */
    protected function is_feature_enabled(string $feature): bool {
        return $this->get_option("tmu_{$feature}", 'off') === 'on';
    }
    
    /**
     * Get class name without namespace
     *
     * @return string Short class name
     */
    protected function get_class_name(): string {
        $class = get_class($this);
        return substr($class, strrpos($class, '\\') + 1);
    }
    
    /**
     * Get execution time since initialization
     *
     * @return float Execution time in seconds
     */
    protected function get_execution_time(): float {
        return microtime(true) - $this->init_time;
    }
    
    /**
     * Check if WordPress is in debug mode
     *
     * @return bool Debug mode status
     */
    protected function is_debug_mode(): bool {
        return $this->debug_mode;
    }
    
    /**
     * Sanitize input data
     *
     * @param mixed $input Input data
     * @param string $type Data type (string, int, float, bool, array)
     * @return mixed Sanitized data
     */
    protected function sanitize_input($input, string $type = 'string') {
        switch ($type) {
            case 'string':
                return is_string($input) ? sanitize_text_field($input) : '';
            case 'int':
            case 'integer':
                return (int) $input;
            case 'float':
            case 'number':
                return (float) $input;
            case 'bool':
            case 'boolean':
                return (bool) $input;
            case 'array':
                return is_array($input) ? $input : [];
            case 'email':
                return sanitize_email($input);
            case 'url':
                return esc_url_raw($input);
            default:
                return $input;
        }
    }
    
    /**
     * Validate required fields
     *
     * @param array $data Input data
     * @param array $required Required field names
     * @return array Missing fields
     */
    protected function validate_required_fields(array $data, array $required): array {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        return $missing;
    }
    
    /**
     * Generate a unique cache key
     *
     * @param string $base Base key
     * @param array $params Additional parameters
     * @return string Cache key
     */
    protected function generate_cache_key(string $base, array $params = []): string {
        $key_parts = [$base];
        
        if (!empty($params)) {
            $key_parts[] = md5(serialize($params));
        }
        
        return implode('_', $key_parts);
    }
    
    /**
     * Handle errors in a consistent way
     *
     * @param string $message Error message
     * @param array $context Error context
     * @param bool $throw_exception Whether to throw exception
     * @throws \Exception
     */
    protected function handle_error(string $message, array $context = [], bool $throw_exception = false): void {
        $this->log($message, 'error', $context);
        
        if ($throw_exception) {
            throw new \Exception($message);
        }
    }
    
    /**
     * Format memory usage
     *
     * @param int $bytes Memory in bytes
     * @return string Formatted memory usage
     */
    protected function format_memory_usage(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Check if current user has required capability
     *
     * @param string $capability Required capability
     * @return bool Whether user has capability
     */
    protected function current_user_can(string $capability): bool {
        return current_user_can($capability);
    }
    
    /**
     * Get current memory usage
     *
     * @return string Formatted memory usage
     */
    protected function get_memory_usage(): string {
        return $this->format_memory_usage(memory_get_usage(true));
    }
    
    /**
     * Get current peak memory usage
     *
     * @return string Formatted peak memory usage
     */
    protected function get_peak_memory_usage(): string {
        return $this->format_memory_usage(memory_get_peak_usage(true));
    }
}