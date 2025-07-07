<?php
/**
 * Log Manager
 * 
 * Centralized logging system for the TMU theme.
 * 
 * @package TMU\Logging
 * @since 1.0.0
 */

namespace TMU\Logging;

class LogManager {
    
    /**
     * Log levels
     */
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';
    
    /**
     * Log handlers
     * @var array
     */
    private $handlers = [];
    
    /**
     * Log context
     * @var array
     */
    private $context = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_handlers();
        add_action('init', [$this, 'register_hooks']);
    }
    
    /**
     * Initialize log handlers
     */
    private function init_handlers(): void {
        // File handler
        $this->handlers['file'] = new FileLogHandler();
        
        // Database handler
        $this->handlers['database'] = new DatabaseLogHandler();
        
        // Email handler for critical issues
        $this->handlers['email'] = new EmailLogHandler();
        
        // External service handler
        $this->handlers['external'] = new ExternalLogHandler();
    }
    
    /**
     * Register WordPress hooks
     */
    public function register_hooks(): void {
        add_action('wp_ajax_tmu_view_logs', [$this, 'view_logs']);
        add_action('wp_ajax_tmu_clear_logs', [$this, 'clear_logs']);
        add_action('wp_ajax_tmu_export_logs', [$this, 'export_logs']);
    }
    
    /**
     * Log emergency message
     */
    public function emergency($message, array $context = []): void {
        $this->log(self::EMERGENCY, $message, $context);
    }
    
    /**
     * Log alert message
     */
    public function alert($message, array $context = []): void {
        $this->log(self::ALERT, $message, $context);
    }
    
    /**
     * Log critical message
     */
    public function critical($message, array $context = []): void {
        $this->log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log error message
     */
    public function error($message, array $context = []): void {
        $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Log warning message
     */
    public function warning($message, array $context = []): void {
        $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Log notice message
     */
    public function notice($message, array $context = []): void {
        $this->log(self::NOTICE, $message, $context);
    }
    
    /**
     * Log info message
     */
    public function info($message, array $context = []): void {
        $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Log debug message
     */
    public function debug($message, array $context = []): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log(self::DEBUG, $message, $context);
        }
    }
    
    /**
     * Log message with specified level
     */
    public function log($level, $message, array $context = []): void {
        $log_entry = [
            'level' => $level,
            'message' => $this->interpolate($message, $context),
            'context' => array_merge($this->context, $context),
            'timestamp' => current_time('c'),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        // Send to enabled handlers
        foreach ($this->handlers as $handler_name => $handler) {
            if ($this->should_handle($handler_name, $level)) {
                $handler->handle($log_entry);
            }
        }
        
        // Trigger action for external integrations
        do_action('tmu_log_entry', $log_entry);
    }
    
    /**
     * Check if handler should process this log level
     */
    private function should_handle($handler_name, $level): bool {
        $config = get_option('tmu_log_config', []);
        
        $handler_config = $config[$handler_name] ?? ['enabled' => true, 'min_level' => self::DEBUG];
        
        if (!$handler_config['enabled']) {
            return false;
        }
        
        $level_hierarchy = [
            self::DEBUG => 0,
            self::INFO => 1,
            self::NOTICE => 2,
            self::WARNING => 3,
            self::ERROR => 4,
            self::CRITICAL => 5,
            self::ALERT => 6,
            self::EMERGENCY => 7
        ];
        
        $min_level = $level_hierarchy[$handler_config['min_level']] ?? 0;
        $current_level = $level_hierarchy[$level] ?? 0;
        
        return $current_level >= $min_level;
    }
    
    /**
     * Interpolate context variables into message
     */
    private function interpolate($message, array $context = []): string {
        $replace = [];
        
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        
        return strtr($message, $replace);
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
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Set global context
     */
    public function set_context(array $context): void {
        $this->context = array_merge($this->context, $context);
    }
    
    /**
     * Clear global context
     */
    public function clear_context(): void {
        $this->context = [];
    }
    
    /**
     * View logs via AJAX
     */
    public function view_logs(): void {
        check_ajax_referer('tmu_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $level = sanitize_text_field($_POST['level'] ?? '');
        $limit = intval($_POST['limit'] ?? 100);
        $offset = intval($_POST['offset'] ?? 0);
        
        $logs = $this->handlers['database']->get_logs($level, $limit, $offset);
        
        wp_send_json_success($logs);
    }
    
    /**
     * Clear logs via AJAX
     */
    public function clear_logs(): void {
        check_ajax_referer('tmu_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $level = sanitize_text_field($_POST['level'] ?? '');
        $days = intval($_POST['days'] ?? 30);
        
        $deleted = $this->handlers['database']->clear_logs($level, $days);
        
        wp_send_json_success(['deleted' => $deleted]);
    }
    
    /**
     * Export logs via AJAX
     */
    public function export_logs(): void {
        check_ajax_referer('tmu_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $level = sanitize_text_field($_POST['level'] ?? '');
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $days = intval($_POST['days'] ?? 7);
        
        $logs = $this->handlers['database']->get_logs_for_export($level, $days);
        
        if ($format === 'csv') {
            $this->export_csv($logs);
        } else {
            wp_send_json_success($logs);
        }
    }
    
    /**
     * Export logs as CSV
     */
    private function export_csv($logs): void {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="tmu_logs_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($logs)) {
            // Header row
            fputcsv($output, ['Timestamp', 'Level', 'Message', 'URL', 'User ID', 'IP Address']);
            
            // Data rows
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['timestamp'],
                    $log['level'],
                    $log['message'],
                    $log['url'],
                    $log['user_id'],
                    $log['ip_address']
                ]);
            }
        }
        
        fclose($output);
        exit;
    }
}

/**
 * File Log Handler
 */
class FileLogHandler {
    
    private $log_dir;
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_dir = $upload_dir['basedir'] . '/tmu-logs/';
        
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }
    }
    
    public function handle($log_entry): void {
        $log_file = $this->log_dir . 'tmu-' . date('Y-m-d') . '.log';
        
        $formatted_entry = sprintf(
            "[%s] %s: %s %s\n",
            $log_entry['timestamp'],
            strtoupper($log_entry['level']),
            $log_entry['message'],
            !empty($log_entry['context']) ? json_encode($log_entry['context']) : ''
        );
        
        file_put_contents($log_file, $formatted_entry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Database Log Handler
 */
class DatabaseLogHandler {
    
    public function handle($log_entry): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_logs';
        
        $wpdb->insert($table_name, [
            'level' => $log_entry['level'],
            'message' => $log_entry['message'],
            'context' => json_encode($log_entry['context']),
            'url' => $log_entry['url'],
            'user_id' => $log_entry['user_id'],
            'ip_address' => $log_entry['ip_address'],
            'user_agent' => $log_entry['user_agent'],
            'timestamp' => current_time('mysql')
        ]);
    }
    
    public function get_logs($level = '', $limit = 100, $offset = 0): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_logs';
        $where = '';
        
        if (!empty($level)) {
            $where = $wpdb->prepare(" WHERE level = %s", $level);
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name $where ORDER BY timestamp DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ), ARRAY_A);
    }
    
    public function clear_logs($level = '', $days = 30): int {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_logs';
        $where = "WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)";
        $params = [$days];
        
        if (!empty($level)) {
            $where .= " AND level = %s";
            $params[] = $level;
        }
        
        return $wpdb->query($wpdb->prepare("DELETE FROM $table_name $where", ...$params));
    }
    
    public function get_logs_for_export($level = '', $days = 7): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_logs';
        $where = "WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)";
        $params = [$days];
        
        if (!empty($level)) {
            $where .= " AND level = %s";
            $params[] = $level;
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name $where ORDER BY timestamp DESC",
            ...$params
        ), ARRAY_A);
    }
}

/**
 * Email Log Handler
 */
class EmailLogHandler {
    
    public function handle($log_entry): void {
        // Only send emails for critical levels
        $email_levels = ['emergency', 'alert', 'critical', 'error'];
        
        if (!in_array($log_entry['level'], $email_levels)) {
            return;
        }
        
        $config = get_option('tmu_log_email_config', []);
        
        if (empty($config['recipients'])) {
            return;
        }
        
        $subject = sprintf('[TMU Alert] %s: %s', 
            strtoupper($log_entry['level']), 
            wp_parse_url(home_url(), PHP_URL_HOST)
        );
        
        $message = sprintf(
            "A %s level event occurred on your TMU website:\n\n" .
            "Level: %s\n" .
            "Message: %s\n" .
            "URL: %s\n" .
            "Time: %s\n" .
            "User: %s\n" .
            "IP: %s\n\n" .
            "Context: %s",
            $log_entry['level'],
            strtoupper($log_entry['level']),
            $log_entry['message'],
            $log_entry['url'],
            $log_entry['timestamp'],
            $log_entry['user_id'] ?: 'Guest',
            $log_entry['ip_address'],
            json_encode($log_entry['context'], JSON_PRETTY_PRINT)
        );
        
        foreach ($config['recipients'] as $recipient) {
            wp_mail($recipient, $subject, $message);
        }
    }
}

/**
 * External Log Handler
 */
class ExternalLogHandler {
    
    public function handle($log_entry): void {
        $config = get_option('tmu_log_external_config', []);
        
        if (empty($config['webhook_url'])) {
            return;
        }
        
        wp_remote_post($config['webhook_url'], [
            'body' => json_encode($log_entry),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 10,
            'blocking' => false
        ]);
    }
}