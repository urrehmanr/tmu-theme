<?php
/**
 * PHP Optimizer Class
 *
 * Handles PHP-level performance optimizations for the TMU theme.
 *
 * @package TMU
 * @subpackage Performance
 */

namespace TMU\Performance;

/**
 * Class PhpOptimizer
 */
class PhpOptimizer {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'optimize_php_settings']);
    }
    
    /**
     * Optimize PHP settings
     */
    public function optimize_php_settings(): void {
        // Increase memory limit for complex operations
        if (ini_get('memory_limit') < '256M') {
            ini_set('memory_limit', '256M');
        }
        
        // Enable OPcache if available
        if (function_exists('opcache_get_status')) {
            $opcache_status = opcache_get_status();
            if (!$opcache_status['opcache_enabled']) {
                ini_set('opcache.enable', 1);
                ini_set('opcache.memory_consumption', 128);
                ini_set('opcache.max_accelerated_files', 4000);
            }
        }
        
        // Optimize session handling
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_divisor', 100);
        ini_set('session.gc_maxlifetime', 3600);
    }
}