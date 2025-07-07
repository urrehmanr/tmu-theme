<?php
/**
 * Nonce Manager
 * 
 * Comprehensive nonce management for CSRF protection.
 * Handles creation, validation, and lifecycle of security nonces.
 * 
 * @package TMU\Security
 * @since 1.0.0
 */

namespace TMU\Security;

/**
 * NonceManager class
 * 
 * Manages WordPress nonces for enhanced security
 */
class NonceManager {
    
    /**
     * Nonce actions
     * @var array
     */
    private $nonce_actions = [];
    
    /**
     * Nonce expiration times
     * @var array
     */
    private $nonce_expiration = [];
    
    /**
     * Default nonce lifetime (in seconds)
     * @var int
     */
    private $default_lifetime = 3600; // 1 hour
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_nonce_actions();
        $this->init_hooks();
    }
    
    /**
     * Initialize nonce actions
     */
    private function init_nonce_actions(): void {
        $this->nonce_actions = [
            'tmu_ajax_nonce' => [
                'context' => 'ajax',
                'lifetime' => 3600, // 1 hour
                'description' => 'TMU AJAX requests'
            ],
            'tmu_search_nonce' => [
                'context' => 'search',
                'lifetime' => 1800, // 30 minutes
                'description' => 'Search functionality'
            ],
            'tmu_admin_nonce' => [
                'context' => 'admin',
                'lifetime' => 7200, // 2 hours
                'description' => 'Admin panel actions'
            ],
            'tmu_form_nonce' => [
                'context' => 'form',
                'lifetime' => 3600, // 1 hour
                'description' => 'Form submissions'
            ],
            'tmu_api_nonce' => [
                'context' => 'api',
                'lifetime' => 1800, // 30 minutes
                'description' => 'API requests'
            ],
            'tmu_delete_nonce' => [
                'context' => 'delete',
                'lifetime' => 600, // 10 minutes
                'description' => 'Delete operations'
            ],
            'tmu_upload_nonce' => [
                'context' => 'upload',
                'lifetime' => 3600, // 1 hour
                'description' => 'File uploads'
            ],
            'tmu_settings_nonce' => [
                'context' => 'settings',
                'lifetime' => 7200, // 2 hours
                'description' => 'Settings modifications'
            ],
            'tmu_tmdb_sync_nonce' => [
                'context' => 'tmdb',
                'lifetime' => 1800, // 30 minutes
                'description' => 'TMDB synchronization'
            ],
            'tmu_bulk_action_nonce' => [
                'context' => 'bulk',
                'lifetime' => 1800, // 30 minutes
                'description' => 'Bulk operations'
            ]
        ];
        
        // Allow customization via filters
        $this->nonce_actions = apply_filters('tmu_nonce_actions', $this->nonce_actions);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_nonces']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_nonces']);
        add_action('wp_ajax_*', [$this, 'verify_ajax_nonce'], 1);
        add_action('wp_ajax_nopriv_*', [$this, 'verify_ajax_nonce'], 1);
        
        // Custom nonce verification hooks
        add_action('tmu_verify_nonce', [$this, 'verify_nonce'], 10, 2);
        add_filter('tmu_create_nonce', [$this, 'create_nonce'], 10, 2);
        
        // Nonce cleanup
        add_action('wp_scheduled_delete', [$this, 'cleanup_expired_nonces']);
        
        // Form security
        add_action('wp_footer', [$this, 'add_form_nonces']);
        add_action('admin_footer', [$this, 'add_admin_form_nonces']);
        
        // REST API nonce handling
        add_filter('rest_authentication_errors', [$this, 'rest_nonce_verification']);
    }
    
    /**
     * Enqueue nonces for frontend
     */
    public function enqueue_nonces(): void {
        $nonces = [];
        
        foreach ($this->nonce_actions as $action => $config) {
            if (in_array($config['context'], ['ajax', 'search', 'form', 'api'])) {
                $nonces[$action] = $this->create_nonce($action);
            }
        }
        
        wp_localize_script('tmu-main', 'tmu_nonces', $nonces);
    }
    
    /**
     * Enqueue nonces for admin
     */
    public function enqueue_admin_nonces(): void {
        $nonces = [];
        
        foreach ($this->nonce_actions as $action => $config) {
            if (in_array($config['context'], ['admin', 'delete', 'upload', 'settings', 'tmdb', 'bulk'])) {
                $nonces[$action] = $this->create_nonce($action);
            }
        }
        
        wp_localize_script('tmu-admin', 'tmu_admin_nonces', $nonces);
    }
    
    /**
     * Verify AJAX nonce automatically
     */
    public function verify_ajax_nonce(): void {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        // Only verify TMU AJAX actions
        if (strpos($action, 'tmu_') === 0) {
            $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
            $nonce_action = $_POST['nonce_action'] ?? $_GET['nonce_action'] ?? 'tmu_ajax_nonce';
            
            if (!$this->verify_nonce($nonce, $nonce_action)) {
                $this->handle_nonce_failure('ajax', $action);
            }
        }
    }
    
    /**
     * Create a nonce
     */
    public function create_nonce($action, $user_id = null): string {
        if (!isset($this->nonce_actions[$action])) {
            // Create custom nonce action
            $this->nonce_actions[$action] = [
                'context' => 'custom',
                'lifetime' => $this->default_lifetime,
                'description' => 'Custom nonce action'
            ];
        }
        
        // Use WordPress nonce with additional security
        $nonce = wp_create_nonce($action);
        
        // Store nonce metadata for tracking
        $this->store_nonce_metadata($nonce, $action, $user_id);
        
        return $nonce;
    }
    
    /**
     * Verify a nonce
     */
    public function verify_nonce($nonce, $action): bool {
        if (empty($nonce) || empty($action)) {
            return false;
        }
        
        // Check if action is registered
        if (!isset($this->nonce_actions[$action])) {
            return false;
        }
        
        // Verify using WordPress nonce system
        $result = wp_verify_nonce($nonce, $action);
        
        if ($result === false) {
            $this->log_nonce_failure($nonce, $action, 'verification_failed');
            return false;
        }
        
        // Additional custom verification
        if (!$this->verify_nonce_metadata($nonce, $action)) {
            $this->log_nonce_failure($nonce, $action, 'metadata_verification_failed');
            return false;
        }
        
        // Log successful verification
        $this->log_nonce_usage($nonce, $action);
        
        return true;
    }
    
    /**
     * Store nonce metadata
     */
    private function store_nonce_metadata($nonce, $action, $user_id = null): void {
        $metadata = [
            'action' => $action,
            'user_id' => $user_id ?: get_current_user_id(),
            'created' => time(),
            'ip' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'lifetime' => $this->nonce_actions[$action]['lifetime']
        ];
        
        // Store in transient (expires automatically)
        set_transient('tmu_nonce_' . md5($nonce), $metadata, $metadata['lifetime']);
    }
    
    /**
     * Verify nonce metadata
     */
    private function verify_nonce_metadata($nonce, $action): bool {
        $metadata = get_transient('tmu_nonce_' . md5($nonce));
        
        if ($metadata === false) {
            return false; // Nonce metadata not found or expired
        }
        
        // Verify action matches
        if ($metadata['action'] !== $action) {
            return false;
        }
        
        // Verify user (if logged in)
        $current_user_id = get_current_user_id();
        if ($current_user_id > 0 && $metadata['user_id'] !== $current_user_id) {
            return false;
        }
        
        // Additional security checks
        if (!$this->verify_nonce_context($metadata)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Verify nonce context
     */
    private function verify_nonce_context($metadata): bool {
        // Check IP address (optional, can be disabled for mobile users)
        if (apply_filters('tmu_verify_nonce_ip', true)) {
            $current_ip = $this->get_client_ip();
            if ($metadata['ip'] !== $current_ip) {
                // Allow IP changes for mobile users or dynamic IPs
                if (!apply_filters('tmu_allow_ip_change', true)) {
                    return false;
                }
            }
        }
        
        // Check user agent (optional)
        if (apply_filters('tmu_verify_nonce_user_agent', false)) {
            $current_user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
            if ($metadata['user_agent'] !== $current_user_agent) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Handle nonce failure
     */
    private function handle_nonce_failure($context, $action = ''): void {
        $message = __('Security verification failed. Please refresh the page and try again.', 'tmu-theme');
        
        // Log the failure
        $this->log_security_event('nonce_failure', [
            'context' => $context,
            'action' => $action,
            'ip' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'user_id' => get_current_user_id()
        ]);
        
        switch ($context) {
            case 'ajax':
                wp_die(wp_json_encode([
                    'success' => false,
                    'message' => $message
                ]), 'Security Error', ['response' => 403]);
                break;
                
            case 'form':
                wp_redirect(add_query_arg('error', 'nonce_failed', wp_get_referer()));
                exit;
                break;
                
            case 'api':
                return new \WP_Error('nonce_verification_failed', $message, ['status' => 403]);
                break;
                
            default:
                wp_die($message, 'Security Error', ['response' => 403]);
        }
    }
    
    /**
     * Add form nonces
     */
    public function add_form_nonces(): void {
        if (!is_admin()) {
            echo '<script>';
            echo 'document.addEventListener("DOMContentLoaded", function() {';
            echo 'var forms = document.querySelectorAll("form[data-tmu-form]");';
            echo 'forms.forEach(function(form) {';
            echo 'var nonceField = document.createElement("input");';
            echo 'nonceField.type = "hidden";';
            echo 'nonceField.name = "tmu_form_nonce";';
            echo 'nonceField.value = "' . $this->create_nonce('tmu_form_nonce') . '";';
            echo 'form.appendChild(nonceField);';
            echo '});';
            echo '});';
            echo '</script>';
        }
    }
    
    /**
     * Add admin form nonces
     */
    public function add_admin_form_nonces(): void {
        if (is_admin()) {
            echo '<script>';
            echo 'document.addEventListener("DOMContentLoaded", function() {';
            echo 'var forms = document.querySelectorAll("form[data-tmu-admin-form]");';
            echo 'forms.forEach(function(form) {';
            echo 'var nonceField = document.createElement("input");';
            echo 'nonceField.type = "hidden";';
            echo 'nonceField.name = "tmu_admin_nonce";';
            echo 'nonceField.value = "' . $this->create_nonce('tmu_admin_nonce') . '";';
            echo 'form.appendChild(nonceField);';
            echo '});';
            echo '});';
            echo '</script>';
        }
    }
    
    /**
     * REST API nonce verification
     */
    public function rest_nonce_verification($errors) {
        // Only verify TMU API endpoints
        $route = rest_get_url_prefix();
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        if (strpos($request_uri, '/tmu/') !== false) {
            $nonce = $_REQUEST['_wpnonce'] ?? $_SERVER['HTTP_X_WP_NONCE'] ?? '';
            
            if (!$this->verify_nonce($nonce, 'tmu_api_nonce')) {
                return new \WP_Error(
                    'rest_nonce_invalid',
                    __('Invalid nonce for API request.', 'tmu-theme'),
                    ['status' => 403]
                );
            }
        }
        
        return $errors;
    }
    
    /**
     * Cleanup expired nonces
     */
    public function cleanup_expired_nonces(): void {
        global $wpdb;
        
        // Clean up transients used for nonce metadata
        $expired_transients = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_tmu_nonce_%' 
             AND option_value < " . time()
        );
        
        foreach ($expired_transients as $transient) {
            delete_option($transient->option_name);
            delete_option(str_replace('_transient_', '_transient_timeout_', $transient->option_name));
        }
    }
    
    /**
     * Log nonce failure
     */
    private function log_nonce_failure($nonce, $action, $reason): void {
        $this->log_security_event('nonce_failure', [
            'nonce_hash' => md5($nonce),
            'action' => $action,
            'reason' => $reason,
            'ip' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'user_id' => get_current_user_id(),
            'request_uri' => sanitize_text_field($_SERVER['REQUEST_URI'] ?? '')
        ]);
    }
    
    /**
     * Log nonce usage
     */
    private function log_nonce_usage($nonce, $action): void {
        // Only log in debug mode to avoid performance impact
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log_security_event('nonce_verified', [
                'nonce_hash' => md5($nonce),
                'action' => $action,
                'user_id' => get_current_user_id()
            ]);
        }
    }
    
    /**
     * Log security event
     */
    private function log_security_event($type, $data): void {
        do_action('tmu_security_event', $type, $data, 'medium');
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip(): string {
        $ip_headers = [
            'HTTP_CLIENT_IP',
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
                
                // Handle multiple IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get nonce actions
     */
    public function get_nonce_actions(): array {
        return $this->nonce_actions;
    }
    
    /**
     * Register new nonce action
     */
    public function register_nonce_action($action, $config): void {
        $default_config = [
            'context' => 'custom',
            'lifetime' => $this->default_lifetime,
            'description' => 'Custom nonce action'
        ];
        
        $this->nonce_actions[$action] = array_merge($default_config, $config);
    }
    
    /**
     * Create form nonce field
     */
    public function create_form_nonce_field($action = 'tmu_form_nonce', $name = 'tmu_nonce'): string {
        $nonce = $this->create_nonce($action);
        return sprintf(
            '<input type="hidden" name="%s" value="%s" />',
            esc_attr($name),
            esc_attr($nonce)
        );
    }
    
    /**
     * Create nonce URL
     */
    public function create_nonce_url($url, $action): string {
        return wp_nonce_url($url, $action);
    }
    
    /**
     * Verify form nonce
     */
    public function verify_form_nonce($nonce_name = 'tmu_nonce', $action = 'tmu_form_nonce'): bool {
        $nonce = $_POST[$nonce_name] ?? $_GET[$nonce_name] ?? '';
        return $this->verify_nonce($nonce, $action);
    }
    
    /**
     * Get nonce for JavaScript
     */
    public function get_nonce_for_js($action): string {
        return $this->create_nonce($action);
    }
    
    /**
     * Validate bulk action nonce
     */
    public function verify_bulk_action_nonce(): bool {
        $nonce = $_POST['_wpnonce'] ?? $_GET['_wpnonce'] ?? '';
        return $this->verify_nonce($nonce, 'tmu_bulk_action_nonce');
    }
    
    /**
     * Create AJAX nonce for specific action
     */
    public function create_ajax_nonce($action): string {
        return $this->create_nonce('tmu_ajax_' . $action);
    }
    
    /**
     * Verify AJAX nonce for specific action
     */
    public function verify_ajax_nonce_for_action($action): bool {
        $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
        return $this->verify_nonce($nonce, 'tmu_ajax_' . $action);
    }
}