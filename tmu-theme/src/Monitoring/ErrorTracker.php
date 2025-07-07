<?php
namespace TMU\Monitoring;

class ErrorTracker {
    private $errors = [];
    
    public function __construct() {
        add_action('init', [$this, 'init_error_tracking']);
        add_action('wp_footer', [$this, 'report_errors']);
    }
    
    public function init_error_tracking(): void {
        set_error_handler([$this, 'handle_error']);
        set_exception_handler([$this, 'handle_exception']);
        register_shutdown_function([$this, 'handle_fatal_error']);
        
        // Track WordPress errors
        add_action('wp_die_handler', [$this, 'track_wp_die']);
        add_filter('wp_die_ajax_handler', [$this, 'track_ajax_error']);
    }
    
    public function handle_error($severity, $message, $file, $line): bool {
        // Only track errors in TMU theme files
        if (strpos($file, get_template_directory()) !== false) {
            $this->errors[] = [
                'type' => 'error',
                'severity' => $severity,
                'message' => $message,
                'file' => $file,
                'line' => $line,
                'timestamp' => current_time('c'),
                'url' => $_SERVER['REQUEST_URI'] ?? '',
                'user_id' => get_current_user_id()
            ];
        }
        
        return false; // Don't prevent default error handling
    }
    
    public function handle_exception($exception): void {
        $this->errors[] = [
            'type' => 'exception',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => current_time('c'),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_id' => get_current_user_id()
        ];
    }
    
    public function handle_fatal_error(): void {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            if (strpos($error['file'], get_template_directory()) !== false) {
                $this->errors[] = [
                    'type' => 'fatal_error',
                    'message' => $error['message'],
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'timestamp' => current_time('c'),
                    'url' => $_SERVER['REQUEST_URI'] ?? '',
                    'user_id' => get_current_user_id()
                ];
                
                $this->report_errors();
            }
        }
    }
    
    public function report_errors(): void {
        if (!empty($this->errors)) {
            $error_config = get_option('tmu_error_tracking_config', []);
            
            if (!empty($error_config['webhook_url'])) {
                wp_remote_post($error_config['webhook_url'], [
                    'body' => json_encode([
                        'service' => 'tmu-theme',
                        'environment' => wp_get_environment_type(),
                        'errors' => $this->errors,
                        'timestamp' => current_time('c')
                    ]),
                    'headers' => ['Content-Type' => 'application/json'],
                    'timeout' => 10,
                    'blocking' => false
                ]);
            }
            
            // Log errors locally
            foreach ($this->errors as $error) {
                error_log('TMU Error: ' . json_encode($error));
            }
        }
    }
}