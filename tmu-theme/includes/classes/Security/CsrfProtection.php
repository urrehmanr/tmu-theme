<?php
/**
 * CSRF Protection
 * 
 * Cross-Site Request Forgery (CSRF) protection through token validation.
 * Ensures all form submissions and AJAX requests are legitimate.
 * 
 * @package TMU\Security
 * @since 1.0.0
 */

namespace TMU\Security;

/**
 * CsrfProtection class
 * 
 * Handles CSRF token generation, validation, and protection
 */
class CsrfProtection {
    
    /**
     * CSRF token name
     * @var string
     */
    private $token_name = 'tmu_csrf_token';
    
    /**
     * Token lifetime in seconds
     * @var int
     */
    private $token_lifetime = 3600; // 1 hour
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_csrf_token']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_csrf_token']);
        
        // AJAX verification
        add_action('wp_ajax_*', [$this, 'verify_csrf_token'], 1);
        add_action('wp_ajax_nopriv_*', [$this, 'verify_csrf_token'], 1);
        
        // Form verification
        add_action('wp_loaded', [$this, 'verify_form_submissions']);
        
        // REST API verification
        add_filter('rest_pre_dispatch', [$this, 'verify_rest_csrf'], 10, 3);
        
        // Comment form protection
        add_action('comment_form', [$this, 'add_comment_csrf_field']);
        add_filter('preprocess_comment', [$this, 'verify_comment_csrf']);
        
        // Login form protection
        add_action('login_form', [$this, 'add_login_csrf_field']);
        add_filter('authenticate', [$this, 'verify_login_csrf'], 25, 3);
        
        // Registration form protection
        add_action('register_form', [$this, 'add_register_csrf_field']);
        add_filter('registration_errors', [$this, 'verify_register_csrf'], 10, 3);
        
        // User profile protection
        add_action('personal_options_update', [$this, 'verify_profile_csrf']);
        add_action('edit_user_profile_update', [$this, 'verify_profile_csrf']);
        
        // Admin actions protection
        add_action('admin_post_*', [$this, 'verify_admin_post_csrf'], 1);
        
        // Custom form handlers
        add_action('wp_ajax_tmu_contact_form', [$this, 'verify_contact_form_csrf'], 1);
        add_action('wp_ajax_nopriv_tmu_contact_form', [$this, 'verify_contact_form_csrf'], 1);
    }
    
    /**
     * Enqueue CSRF token for frontend
     */
    public function enqueue_csrf_token(): void {
        $token = $this->generate_token();
        
        wp_localize_script('tmu-main', 'tmu_csrf', [
            'token' => $token,
            'token_name' => $this->token_name,
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
        
        // Also add to any existing TMU scripts
        wp_localize_script('tmu-search', 'tmu_csrf', [
            'token' => $token,
            'token_name' => $this->token_name
        ]);
    }
    
    /**
     * Enqueue CSRF token for admin
     */
    public function enqueue_admin_csrf_token(): void {
        $token = $this->generate_token();
        
        wp_localize_script('tmu-admin', 'tmu_csrf', [
            'token' => $token,
            'token_name' => $this->token_name,
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }
    
    /**
     * Verify CSRF token for AJAX requests
     */
    public function verify_csrf_token(): void {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        // Only verify TMU AJAX actions
        if (strpos($action, 'tmu_') === 0) {
            $token = $_POST[$this->token_name] ?? $_GET[$this->token_name] ?? '';
            
            if (!$this->validate_token($token)) {
                $this->handle_csrf_failure('ajax', $action);
            }
        }
    }
    
    /**
     * Verify form submissions
     */
    public function verify_form_submissions(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !is_admin()) {
            // Check for TMU forms
            if (isset($_POST['tmu_form_type'])) {
                $token = $_POST[$this->token_name] ?? '';
                
                if (!$this->validate_token($token)) {
                    $this->handle_csrf_failure('form', $_POST['tmu_form_type']);
                }
            }
        }
    }
    
    /**
     * Verify REST API CSRF
     */
    public function verify_rest_csrf($result, $server, $request) {
        $route = $request->get_route();
        
        // Only verify TMU API endpoints that modify data
        if (strpos($route, '/tmu/') === 0 && in_array($request->get_method(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $token = $request->get_header('X-CSRF-Token') ?? $request->get_param($this->token_name);
            
            if (!$this->validate_token($token)) {
                return new \WP_Error(
                    'csrf_token_invalid',
                    __('CSRF token validation failed.', 'tmu-theme'),
                    ['status' => 403]
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Add CSRF field to comment form
     */
    public function add_comment_csrf_field(): void {
        echo $this->get_csrf_field();
    }
    
    /**
     * Verify comment CSRF
     */
    public function verify_comment_csrf($commentdata): array {
        $token = $_POST[$this->token_name] ?? '';
        
        if (!$this->validate_token($token)) {
            wp_die(__('CSRF token validation failed for comment submission.', 'tmu-theme'));
        }
        
        return $commentdata;
    }
    
    /**
     * Add CSRF field to login form
     */
    public function add_login_csrf_field(): void {
        echo $this->get_csrf_field();
    }
    
    /**
     * Verify login CSRF
     */
    public function verify_login_csrf($user, $username, $password) {
        if (!empty($_POST)) {
            $token = $_POST[$this->token_name] ?? '';
            
            if (!$this->validate_token($token)) {
                return new \WP_Error(
                    'csrf_token_invalid',
                    __('Security token validation failed. Please try again.', 'tmu-theme')
                );
            }
        }
        
        return $user;
    }
    
    /**
     * Add CSRF field to registration form
     */
    public function add_register_csrf_field(): void {
        echo $this->get_csrf_field();
    }
    
    /**
     * Verify registration CSRF
     */
    public function verify_register_csrf($errors, $sanitized_user_login, $user_email) {
        $token = $_POST[$this->token_name] ?? '';
        
        if (!$this->validate_token($token)) {
            $errors->add('csrf_token_invalid', __('Security token validation failed.', 'tmu-theme'));
        }
        
        return $errors;
    }
    
    /**
     * Verify profile CSRF
     */
    public function verify_profile_csrf($user_id): void {
        $token = $_POST[$this->token_name] ?? '';
        
        if (!$this->validate_token($token)) {
            wp_die(__('CSRF token validation failed for profile update.', 'tmu-theme'));
        }
    }
    
    /**
     * Verify admin post CSRF
     */
    public function verify_admin_post_csrf(): void {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        // Only verify TMU admin actions
        if (strpos($action, 'tmu_') === 0) {
            $token = $_POST[$this->token_name] ?? $_GET[$this->token_name] ?? '';
            
            if (!$this->validate_token($token)) {
                $this->handle_csrf_failure('admin_post', $action);
            }
        }
    }
    
    /**
     * Verify contact form CSRF
     */
    public function verify_contact_form_csrf(): void {
        $token = $_POST[$this->token_name] ?? '';
        
        if (!$this->validate_token($token)) {
            wp_die(wp_json_encode([
                'success' => false,
                'message' => __('Security token validation failed.', 'tmu-theme')
            ]), 'CSRF Error', ['response' => 403]);
        }
    }
    
    /**
     * Generate CSRF token
     */
    public function generate_token(): string {
        $user_id = get_current_user_id();
        $session_id = session_id() ?: wp_generate_uuid4();
        $timestamp = time();
        
        // Create token data
        $token_data = [
            'user_id' => $user_id,
            'session_id' => $session_id,
            'timestamp' => $timestamp,
            'ip' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '')
        ];
        
        // Create token hash
        $token_string = wp_json_encode($token_data);
        $token_hash = hash_hmac('sha256', $token_string, wp_salt('auth'));
        
        // Combine timestamp and hash
        $token = base64_encode($timestamp . '|' . $token_hash);
        
        // Store token metadata
        $this->store_token_metadata($token, $token_data);
        
        return $token;
    }
    
    /**
     * Validate CSRF token
     */
    public function validate_token($token): bool {
        if (empty($token)) {
            return false;
        }
        
        try {
            $decoded = base64_decode($token);
            $parts = explode('|', $decoded, 2);
            
            if (count($parts) !== 2) {
                return false;
            }
            
            [$timestamp, $hash] = $parts;
            $timestamp = intval($timestamp);
            
            // Check if token has expired
            if ((time() - $timestamp) > $this->token_lifetime) {
                $this->log_csrf_failure($token, 'token_expired');
                return false;
            }
            
            // Verify token metadata
            if (!$this->verify_token_metadata($token)) {
                $this->log_csrf_failure($token, 'metadata_verification_failed');
                return false;
            }
            
            // Recreate token data for verification
            $token_metadata = $this->get_token_metadata($token);
            if (!$token_metadata) {
                return false;
            }
            
            $token_string = wp_json_encode($token_metadata);
            $expected_hash = hash_hmac('sha256', $token_string, wp_salt('auth'));
            
            // Compare hashes
            if (!hash_equals($expected_hash, $hash)) {
                $this->log_csrf_failure($token, 'hash_mismatch');
                return false;
            }
            
            // Additional context verification
            if (!$this->verify_token_context($token_metadata)) {
                $this->log_csrf_failure($token, 'context_verification_failed');
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->log_csrf_failure($token, 'validation_exception', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Store token metadata
     */
    private function store_token_metadata($token, $token_data): void {
        $token_key = 'csrf_' . md5($token);
        set_transient($token_key, $token_data, $this->token_lifetime);
    }
    
    /**
     * Get token metadata
     */
    private function get_token_metadata($token): ?array {
        $token_key = 'csrf_' . md5($token);
        $metadata = get_transient($token_key);
        
        return $metadata ?: null;
    }
    
    /**
     * Verify token metadata
     */
    private function verify_token_metadata($token): bool {
        $metadata = $this->get_token_metadata($token);
        
        if (!$metadata) {
            return false;
        }
        
        // Verify user context
        $current_user_id = get_current_user_id();
        if ($metadata['user_id'] !== $current_user_id) {
            // Allow for logged out to logged in transitions
            if ($metadata['user_id'] === 0 && $current_user_id > 0) {
                return true;
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Verify token context
     */
    private function verify_token_context($metadata): bool {
        // Check IP address (optional, can be disabled for mobile users)
        if (apply_filters('tmu_verify_csrf_ip', false)) {
            $current_ip = $this->get_client_ip();
            if ($metadata['ip'] !== $current_ip) {
                return false;
            }
        }
        
        // Check user agent (optional)
        if (apply_filters('tmu_verify_csrf_user_agent', false)) {
            $current_user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
            if ($metadata['user_agent'] !== $current_user_agent) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Handle CSRF failure
     */
    private function handle_csrf_failure($context, $action = ''): void {
        $message = __('CSRF token validation failed. Please refresh the page and try again.', 'tmu-theme');
        
        // Log the failure
        $this->log_security_event('csrf_failure', [
            'context' => $context,
            'action' => $action,
            'ip' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'user_id' => get_current_user_id(),
            'referer' => sanitize_text_field($_SERVER['HTTP_REFERER'] ?? '')
        ]);
        
        switch ($context) {
            case 'ajax':
                wp_die(wp_json_encode([
                    'success' => false,
                    'message' => $message
                ]), 'CSRF Error', ['response' => 403]);
                break;
                
            case 'form':
                wp_redirect(add_query_arg(['error' => 'csrf_failed'], wp_get_referer()));
                exit;
                break;
                
            case 'admin_post':
                wp_die($message, 'CSRF Error', ['response' => 403, 'back_link' => true]);
                break;
                
            default:
                wp_die($message, 'CSRF Error', ['response' => 403]);
        }
    }
    
    /**
     * Get CSRF field HTML
     */
    public function get_csrf_field($echo = false): string {
        $token = $this->generate_token();
        $field = sprintf(
            '<input type="hidden" name="%s" value="%s" />',
            esc_attr($this->token_name),
            esc_attr($token)
        );
        
        if ($echo) {
            echo $field;
        }
        
        return $field;
    }
    
    /**
     * Get CSRF token for JavaScript
     */
    public function get_token_for_js(): string {
        return $this->generate_token();
    }
    
    /**
     * Add CSRF token to URL
     */
    public function add_token_to_url($url): string {
        $token = $this->generate_token();
        return add_query_arg($this->token_name, $token, $url);
    }
    
    /**
     * Verify token from URL
     */
    public function verify_url_token(): bool {
        $token = $_GET[$this->token_name] ?? '';
        return $this->validate_token($token);
    }
    
    /**
     * Log CSRF failure
     */
    private function log_csrf_failure($token, $reason, $details = ''): void {
        $this->log_security_event('csrf_failure', [
            'token_hash' => md5($token),
            'reason' => $reason,
            'details' => $details,
            'ip' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'user_id' => get_current_user_id(),
            'request_uri' => sanitize_text_field($_SERVER['REQUEST_URI'] ?? '')
        ]);
    }
    
    /**
     * Log security event
     */
    private function log_security_event($type, $data): void {
        do_action('tmu_security_event', $type, $data, 'high');
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
     * Get token name
     */
    public function get_token_name(): string {
        return $this->token_name;
    }
    
    /**
     * Set token lifetime
     */
    public function set_token_lifetime($seconds): void {
        $this->token_lifetime = max(300, intval($seconds)); // Minimum 5 minutes
    }
    
    /**
     * Get token lifetime
     */
    public function get_token_lifetime(): int {
        return $this->token_lifetime;
    }
    
    /**
     * Clean expired tokens
     */
    public function clean_expired_tokens(): void {
        global $wpdb;
        
        // Clean up expired transients
        $expired_transients = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_csrf_%' 
             AND option_value < " . (time() - $this->token_lifetime)
        );
        
        foreach ($expired_transients as $transient) {
            delete_option($transient->option_name);
            delete_option(str_replace('_transient_', '_transient_timeout_', $transient->option_name));
        }
    }
    
    /**
     * Generate token for specific context
     */
    public function generate_context_token($context): string {
        $token = $this->generate_token();
        
        // Store context information
        $token_key = 'csrf_context_' . md5($token);
        set_transient($token_key, $context, $this->token_lifetime);
        
        return $token;
    }
    
    /**
     * Validate token for specific context
     */
    public function validate_context_token($token, $expected_context): bool {
        if (!$this->validate_token($token)) {
            return false;
        }
        
        $token_key = 'csrf_context_' . md5($token);
        $stored_context = get_transient($token_key);
        
        return $stored_context === $expected_context;
    }
}