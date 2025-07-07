<?php
/**
 * Configuration Optimizer Class
 *
 * Handles WordPress configuration constants and optimizations for the TMU theme.
 *
 * @package TMU
 * @subpackage Performance
 */

namespace TMU\Performance;

/**
 * Class ConfigurationOptimizer
 */
class ConfigurationOptimizer {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'set_performance_constants'], 1);
    }
    
    /**
     * Set performance constants
     */
    public function set_performance_constants(): void {
        // Performance settings in wp-config.php additions
        if (!defined('WP_CACHE')) {
            define('WP_CACHE', true);
        }
        
        if (!defined('COMPRESS_CSS')) {
            define('COMPRESS_CSS', true);
        }
        
        if (!defined('COMPRESS_SCRIPTS')) {
            define('COMPRESS_SCRIPTS', true);
        }
        
        if (!defined('CONCATENATE_SCRIPTS')) {
            define('CONCATENATE_SCRIPTS', true);
        }
        
        if (!defined('ENFORCE_GZIP')) {
            define('ENFORCE_GZIP', true);
        }
        
        // Database optimization
        if (!defined('WP_ALLOW_REPAIR')) {
            define('WP_ALLOW_REPAIR', true);
        }
        
        if (!defined('AUTOMATIC_UPDATER_DISABLED')) {
            define('AUTOMATIC_UPDATER_DISABLED', true);
        }
        
        // Memory limits
        if (!defined('WP_MEMORY_LIMIT')) {
            define('WP_MEMORY_LIMIT', '256M');
        }
        
        if (!defined('WP_MAX_MEMORY_LIMIT')) {
            define('WP_MAX_MEMORY_LIMIT', '512M');
        }
    }
    
    /**
     * Get current configuration status
     */
    public function get_configuration_status(): array {
        return [
            'WP_CACHE' => defined('WP_CACHE') ? WP_CACHE : false,
            'COMPRESS_CSS' => defined('COMPRESS_CSS') ? COMPRESS_CSS : false,
            'COMPRESS_SCRIPTS' => defined('COMPRESS_SCRIPTS') ? COMPRESS_SCRIPTS : false,
            'CONCATENATE_SCRIPTS' => defined('CONCATENATE_SCRIPTS') ? CONCATENATE_SCRIPTS : false,
            'ENFORCE_GZIP' => defined('ENFORCE_GZIP') ? ENFORCE_GZIP : false,
            'WP_ALLOW_REPAIR' => defined('WP_ALLOW_REPAIR') ? WP_ALLOW_REPAIR : false,
            'AUTOMATIC_UPDATER_DISABLED' => defined('AUTOMATIC_UPDATER_DISABLED') ? AUTOMATIC_UPDATER_DISABLED : false,
            'WP_MEMORY_LIMIT' => defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : ini_get('memory_limit'),
            'WP_MAX_MEMORY_LIMIT' => defined('WP_MAX_MEMORY_LIMIT') ? WP_MAX_MEMORY_LIMIT : ini_get('memory_limit'),
        ];
    }
}