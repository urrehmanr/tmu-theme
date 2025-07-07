<?php
/**
 * Error Tracker
 * 
 * Comprehensive error monitoring for the TMU theme.
 * 
 * @package TMU\Monitoring
 * @since 1.0.0
 */

namespace TMU\Monitoring;

use TMU\Logging\LogManager;

class ErrorTracker {
    
    /**
     * Log manager instance
     * @var LogManager
     */
    private $logger;
    
    /**
     * Error storage
     * @var array
     */
    private $errors = [];
    
    /**
     * Error types
     */
    const ERROR_TYPES = [
        E_ERROR => 'Fatal Error',
        E_WARNING => 'Warning', 
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Standards',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new LogManager();
        $this->init_error_tracking();
        add_action('wp_ajax_tmu_get_error_stats', [$this, 'get_error_statistics']);
        add_action('wp_ajax_tmu_clear_error_logs', [$this, 'clear_error_logs']);
    }
    
    /**
     * Initialize error tracking
     */
    public function init_error_tracking(): void {
        // Set error handler for PHP errors
        set_error_handler([$this, 'handle_php_error']);
        
        // Set exception handler for uncaught exceptions
        set_exception_handler([$this, 'handle_exception']);
        
        // Register shutdown function for fatal errors
        register_shutdown_function([$this, 'handle_fatal_error']);
        
        // WordPress specific error hooks
        add_action('wp_die_handler', [$this, 'handle_wp_die']);
        add_filter('wp_die_ajax_handler', [$this, 'handle_ajax_error']);
        
        // Database error tracking
        add_action('wp_db_error', [$this, 'handle_database_error']);
        
        // HTTP error tracking
        add_action('wp_http_request_args', [$this, 'track_http_requests'], 10, 2);
        
        // Plugin/theme error tracking
        add_action('activated_plugin', [$this, 'track_plugin_activation']);
        add_action('deactivated_plugin', [$this, 'track_plugin_deactivation']);
    }
    
    /**
     * Handle PHP errors
     */
    public function handle_php_error($severity, $message, $file, $line): bool {
        // Only track errors in TMU theme files or if configured to track all
        $track_all = get_option('tmu_error_track_all', false);
        $is_theme_error = strpos($file, get_template_directory()) !== false;
        
        if (!$track_all && !$is_theme_error) {
            return false;
        }
        
        $error_data = [
            'type' => 'php_error',
            'severity' => $severity,
            'severity_name' => self::ERROR_TYPES[$severity] ?? 'Unknown',
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'context' => $this->get_error_context(),
            'stack_trace' => $this->get_stack_trace()
        ];
        
        $this->log_error($error_data);
        
        // Continue with normal error handling
        return false;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public function handle_exception($exception): void {
        $error_data = [
            'type' => 'exception',
            'exception_type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => $this->get_error_context(),
            'stack_trace' => $exception->getTraceAsString()
        ];
        
        $this->log_error($error_data);
    }
    
    /**
     * Handle fatal errors
     */
    public function handle_fatal_error(): void {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $track_all = get_option('tmu_error_track_all', false);
            $is_theme_error = strpos($error['file'], get_template_directory()) !== false;
            
            if ($track_all || $is_theme_error) {
                $error_data = [
                    'type' => 'fatal_error',
                    'severity' => $error['type'],
                    'severity_name' => self::ERROR_TYPES[$error['type']] ?? 'Unknown',
                    'message' => $error['message'],
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'context' => $this->get_error_context(),
                    'stack_trace' => 'Fatal error - no stack trace available'
                ];
                
                $this->log_error($error_data);
                $this->flush_errors();
            }
        }
    }
    
    /**
     * Handle WordPress die calls
     */
    public function handle_wp_die($handler): callable {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $this->log_error([
                'type' => 'wp_die_ajax',
                'message' => 'WordPress AJAX die occurred',
                'context' => $this->get_error_context()
            ]);
        }
        
        return $handler;
    }
    
    /**
     * Handle AJAX errors
     */
    public function handle_ajax_error($handler): callable {
        $this->log_error([
            'type' => 'ajax_error',
            'message' => 'AJAX error occurred',
            'context' => $this->get_error_context()
        ]);
        
        return $handler;
    }
    
    /**
     * Handle database errors
     */
    public function handle_database_error($error): void {
        global $wpdb;
        
        $this->log_error([
            'type' => 'database_error',
            'message' => $error,
            'last_query' => $wpdb->last_query ?? '',
            'last_error' => $wpdb->last_error ?? '',
            'context' => $this->get_error_context()
        ]);
    }
    
    /**
     * Track HTTP requests for errors
     */
    public function track_http_requests($args, $url): array {
        add_action('http_api_debug', function($response, $context, $class, $parsed_args, $request_url) {
            if (is_wp_error($response)) {
                $this->log_error([
                    'type' => 'http_error',
                    'message' => $response->get_error_message(),
                    'url' => $request_url,
                    'error_code' => $response->get_error_code(),
                    'context' => $this->get_error_context()
                ]);
            }
        }, 10, 5);
        
        return $args;
    }
    
    /**
     * Track plugin activation errors
     */
    public function track_plugin_activation($plugin): void {
        if (isset($_GET['error'])) {
            $this->log_error([
                'type' => 'plugin_activation_error',
                'message' => 'Plugin activation failed',
                'plugin' => $plugin,
                'context' => $this->get_error_context()
            ]);
        }
    }
    
    /**
     * Track plugin deactivation
     */
    public function track_plugin_deactivation($plugin): void {
        $this->logger->info('Plugin deactivated: ' . $plugin, [
            'plugin' => $plugin,
            'user_id' => get_current_user_id()
        ]);
    }
    
    /**
     * Log error data
     */
    private function log_error($error_data): void {
        $error_data = array_merge([
            'timestamp' => current_time('c'),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version')
        ], $error_data);
        
        // Store in local array
        $this->errors[] = $error_data;
        
        // Log to database immediately for fatal errors
        if ($error_data['type'] === 'fatal_error') {
            $this->store_error_in_database($error_data);
        }
        
        // Log using LogManager
        $log_level = $this->get_log_level($error_data);
        $this->logger->log($log_level, $error_data['message'], $error_data);
        
        // Send notification for critical errors
        if ($this->is_critical_error($error_data)) {
            $this->send_error_notification($error_data);
        }
    }
    
    /**
     * Get error context
     */
    private function get_error_context(): array {
        return [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'time_limit' => ini_get('max_execution_time'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'wp_query_count' => get_num_queries(),
            'wp_doing_ajax' => defined('DOING_AJAX') && DOING_AJAX,
            'wp_doing_cron' => defined('DOING_CRON') && DOING_CRON,
            'wp_doing_autosave' => defined('DOING_AUTOSAVE') && DOING_AUTOSAVE
        ];
    }
    
    /**
     * Get stack trace
     */
    private function get_stack_trace(): string {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        array_shift($trace); // Remove this function
        array_shift($trace); // Remove error handler
        
        $formatted_trace = [];
        foreach ($trace as $i => $frame) {
            $file = $frame['file'] ?? 'unknown';
            $line = $frame['line'] ?? 0;
            $function = $frame['function'] ?? 'unknown';
            $class = isset($frame['class']) ? $frame['class'] . '::' : '';
            
            $formatted_trace[] = sprintf('#%d %s(%d): %s%s()', $i, $file, $line, $class, $function);
        }
        
        return implode("\n", $formatted_trace);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip(): string {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Store error in database
     */
    private function store_error_in_database($error_data): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_error_logs';
        
        $wpdb->insert($table_name, [
            'error_type' => $error_data['type'],
            'message' => $error_data['message'],
            'file' => $error_data['file'] ?? '',
            'line' => $error_data['line'] ?? 0,
            'trace' => $error_data['stack_trace'] ?? '',
            'severity' => $error_data['severity'] ?? 0,
            'url' => $error_data['url'],
            'user_id' => $error_data['user_id'],
            'timestamp' => current_time('mysql')
        ]);
    }
    
    /**
     * Get log level for error
     */
    private function get_log_level($error_data): string {
        $type = $error_data['type'];
        $severity = $error_data['severity'] ?? 0;
        
        if ($type === 'fatal_error' || $type === 'exception') {
            return LogManager::CRITICAL;
        }
        
        if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            return LogManager::ERROR;
        }
        
        if (in_array($severity, [E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING])) {
            return LogManager::WARNING;
        }
        
        if (in_array($severity, [E_NOTICE, E_USER_NOTICE, E_STRICT])) {
            return LogManager::NOTICE;
        }
        
        return LogManager::INFO;
    }
    
    /**
     * Check if error is critical
     */
    private function is_critical_error($error_data): bool {
        $critical_types = ['fatal_error', 'exception', 'database_error'];
        $critical_severities = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        
        return in_array($error_data['type'], $critical_types) || 
               in_array($error_data['severity'] ?? 0, $critical_severities);
    }
    
    /**
     * Send error notification
     */
    private function send_error_notification($error_data): void {
        $notification_config = get_option('tmu_error_notifications', []);
        
        if (empty($notification_config['webhook_url'])) {
            return;
        }
        
        wp_remote_post($notification_config['webhook_url'], [
            'body' => json_encode([
                'type' => 'error_alert',
                'error' => $error_data,
                'site' => get_site_url(),
                'timestamp' => current_time('c')
            ]),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 10,
            'blocking' => false
        ]);
    }
    
    /**
     * Flush errors to database
     */
    public function flush_errors(): void {
        foreach ($this->errors as $error_data) {
            $this->store_error_in_database($error_data);
        }
        
        $this->errors = [];
    }
    
    /**
     * Get error statistics
     */
    public function get_error_statistics(): void {
        check_ajax_referer('tmu_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_error_logs';
        $hours = intval($_POST['hours'] ?? 24);
        
        // Error count by type
        $error_types = $wpdb->get_results($wpdb->prepare(
            "SELECT error_type, COUNT(*) as count
             FROM $table_name 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d HOUR)
             GROUP BY error_type
             ORDER BY count DESC",
            $hours
        ));
        
        // Error trends over time
        $error_trends = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE_FORMAT(timestamp, '%%H:%%i') as time,
                COUNT(*) as count
             FROM $table_name 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d HOUR)
             GROUP BY DATE_FORMAT(timestamp, '%%Y-%%m-%%d %%H:%%i')
             ORDER BY timestamp",
            $hours
        ));
        
        // Most frequent errors
        $frequent_errors = $wpdb->get_results($wpdb->prepare(
            "SELECT message, COUNT(*) as count, MAX(timestamp) as last_occurrence
             FROM $table_name 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d HOUR)
             GROUP BY message
             ORDER BY count DESC
             LIMIT 10",
            $hours
        ));
        
        // Error rate
        $total_errors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d HOUR)",
            $hours
        ));
        
        $stats = [
            'total_errors' => intval($total_errors),
            'error_rate' => round($total_errors / $hours, 2),
            'error_types' => $error_types,
            'error_trends' => $error_trends,
            'frequent_errors' => $frequent_errors
        ];
        
        wp_send_json_success($stats);
    }
    
    /**
     * Clear error logs
     */
    public function clear_error_logs(): void {
        check_ajax_referer('tmu_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        
        $days = intval($_POST['days'] ?? 30);
        $table_name = $wpdb->prefix . 'tmu_error_logs';
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name 
             WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        wp_send_json_success(['deleted' => $deleted]);
    }
    
    /**
     * Get error summary for dashboard
     */
    public function get_error_summary($hours = 24): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_error_logs';
        
        return [
            'total_errors' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name 
                 WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d HOUR)",
                $hours
            )),
            'fatal_errors' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name 
                 WHERE error_type IN ('fatal_error', 'exception') 
                 AND timestamp >= DATE_SUB(NOW(), INTERVAL %d HOUR)",
                $hours
            )),
            'warnings' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name 
                 WHERE error_type = 'php_error' AND severity IN (%d, %d, %d, %d)
                 AND timestamp >= DATE_SUB(NOW(), INTERVAL %d HOUR)",
                E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING, $hours
            ))
        ];
    }
}