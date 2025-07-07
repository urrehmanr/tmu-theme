<?php
/**
 * Security Headers
 * 
 * HTTP security headers implementation for enhanced protection.
 * Sets various security headers to protect against common attacks.
 * 
 * @package TMU\Security
 * @since 1.0.0
 */

namespace TMU\Security;

/**
 * SecurityHeaders class
 * 
 * Manages HTTP security headers
 */
class SecurityHeaders {
    
    /**
     * Security headers configuration
     * @var array
     */
    private $headers_config = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_headers_config();
        $this->init_hooks();
    }
    
    /**
     * Initialize headers configuration
     */
    private function init_headers_config(): void {
        $this->headers_config = [
            'x_frame_options' => [
                'enabled' => true,
                'value' => 'DENY',
                'description' => 'Prevents clickjacking attacks'
            ],
            'x_xss_protection' => [
                'enabled' => true,
                'value' => '1; mode=block',
                'description' => 'Enables XSS filtering'
            ],
            'x_content_type_options' => [
                'enabled' => true,
                'value' => 'nosniff',
                'description' => 'Prevents MIME type sniffing'
            ],
            'referrer_policy' => [
                'enabled' => true,
                'value' => 'strict-origin-when-cross-origin',
                'description' => 'Controls referrer information'
            ],
            'permissions_policy' => [
                'enabled' => true,
                'value' => 'camera=(), microphone=(), geolocation=(), interest-cohort=()',
                'description' => 'Controls browser features'
            ],
            'strict_transport_security' => [
                'enabled' => true,
                'value' => 'max-age=31536000; includeSubDomains; preload',
                'description' => 'Enforces HTTPS connections',
                'https_only' => true
            ],
            'content_security_policy' => [
                'enabled' => true,
                'value' => [
                    "default-src 'self'",
                    "script-src 'self' 'unsafe-inline' https://api.themoviedb.org https://www.google.com https://www.gstatic.com",
                    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                    "img-src 'self' data: https: blob:",
                    "font-src 'self' https://fonts.gstatic.com",
                    "connect-src 'self' https://api.themoviedb.org",
                    "media-src 'self' data: blob:",
                    "object-src 'none'",
                    "frame-src 'none'",
                    "base-uri 'self'",
                    "form-action 'self'",
                    "upgrade-insecure-requests"
                ],
                'description' => 'Content Security Policy',
                'report_only' => false
            ],
            'expect_ct' => [
                'enabled' => true,
                'value' => 'max-age=86400, enforce',
                'description' => 'Certificate Transparency',
                'https_only' => true
            ],
            'cross_origin_embedder_policy' => [
                'enabled' => false,
                'value' => 'require-corp',
                'description' => 'Cross-Origin Embedder Policy'
            ],
            'cross_origin_opener_policy' => [
                'enabled' => true,
                'value' => 'same-origin',
                'description' => 'Cross-Origin Opener Policy'
            ],
            'cross_origin_resource_policy' => [
                'enabled' => true,
                'value' => 'same-site',
                'description' => 'Cross-Origin Resource Policy'
            ]
        ];
        
        // Allow customization via filters
        $this->headers_config = apply_filters('tmu_security_headers_config', $this->headers_config);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        add_action('init', [$this, 'set_security_headers']);
        add_action('wp_head', [$this, 'add_meta_security_headers'], 1);
        add_action('admin_head', [$this, 'add_admin_security_headers'], 1);
        
        // Remove WordPress version from headers
        add_filter('the_generator', '__return_empty_string');
        remove_action('wp_head', 'wp_generator');
        
        // Remove X-Pingback header
        add_filter('wp_headers', [$this, 'remove_pingback_header']);
        
        // Disable XML-RPC if not needed
        add_filter('xmlrpc_enabled', [$this, 'disable_xmlrpc']);
        
        // Remove WordPress API exposure
        add_filter('rest_authentication_errors', [$this, 'restrict_rest_api']);
        
        // Server signature removal
        add_action('send_headers', [$this, 'remove_server_signature']);
        
        // Additional security headers for specific contexts
        add_action('wp_ajax_*', [$this, 'add_ajax_security_headers']);
        add_action('wp_ajax_nopriv_*', [$this, 'add_ajax_security_headers']);
        
        // API endpoint security headers
        add_action('rest_api_init', [$this, 'add_rest_api_headers']);
    }
    
    /**
     * Set security headers
     */
    public function set_security_headers(): void {
        if (headers_sent()) {
            return;
        }
        
        foreach ($this->headers_config as $header_key => $config) {
            if (!$config['enabled']) {
                continue;
            }
            
            // Skip HTTPS-only headers if not on HTTPS
            if (isset($config['https_only']) && $config['https_only'] && !is_ssl()) {
                continue;
            }
            
            $this->set_header($header_key, $config);
        }
        
        // Additional custom headers
        $this->set_custom_headers();
    }
    
    /**
     * Set individual header
     */
    private function set_header($header_key, $config): void {
        $header_name = $this->get_header_name($header_key);
        $header_value = $this->get_header_value($config);
        
        if ($header_name && $header_value) {
            header("{$header_name}: {$header_value}");
        }
    }
    
    /**
     * Get header name from key
     */
    private function get_header_name($header_key): string {
        $header_names = [
            'x_frame_options' => 'X-Frame-Options',
            'x_xss_protection' => 'X-XSS-Protection',
            'x_content_type_options' => 'X-Content-Type-Options',
            'referrer_policy' => 'Referrer-Policy',
            'permissions_policy' => 'Permissions-Policy',
            'strict_transport_security' => 'Strict-Transport-Security',
            'content_security_policy' => 'Content-Security-Policy',
            'expect_ct' => 'Expect-CT',
            'cross_origin_embedder_policy' => 'Cross-Origin-Embedder-Policy',
            'cross_origin_opener_policy' => 'Cross-Origin-Opener-Policy',
            'cross_origin_resource_policy' => 'Cross-Origin-Resource-Policy'
        ];
        
        return $header_names[$header_key] ?? '';
    }
    
    /**
     * Get header value from config
     */
    private function get_header_value($config): string {
        $value = $config['value'];
        
        if (is_array($value)) {
            return implode('; ', $value);
        }
        
        return (string) $value;
    }
    
    /**
     * Set custom headers
     */
    private function set_custom_headers(): void {
        // Remove server information
        header_remove('Server');
        header_remove('X-Powered-By');
        
        // Cache control for sensitive pages
        if (is_admin() || is_user_logged_in()) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
        
        // Custom TMU headers
        header('X-TMU-Security: enabled');
        
        // Feature policy (older browsers)
        if ($this->headers_config['permissions_policy']['enabled']) {
            header('Feature-Policy: ' . $this->headers_config['permissions_policy']['value']);
        }
    }
    
    /**
     * Add meta security headers
     */
    public function add_meta_security_headers(): void {
        // CSP as meta tag (fallback)
        if ($this->headers_config['content_security_policy']['enabled']) {
            $csp_value = $this->get_header_value($this->headers_config['content_security_policy']);
            echo '<meta http-equiv="Content-Security-Policy" content="' . esc_attr($csp_value) . '">' . "\n";
        }
        
        // Additional meta security tags
        echo '<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex, notranslate">' . "\n";
    }
    
    /**
     * Add admin security headers
     */
    public function add_admin_security_headers(): void {
        // Admin-specific CSP
        $admin_csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "object-src 'none'",
            "frame-src 'self'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        
        $admin_csp = apply_filters('tmu_admin_csp_policy', $admin_csp);
        
        echo '<meta http-equiv="Content-Security-Policy" content="' . esc_attr(implode('; ', $admin_csp)) . '">' . "\n";
    }
    
    /**
     * Remove pingback header
     */
    public function remove_pingback_header($headers): array {
        unset($headers['X-Pingback']);
        return $headers;
    }
    
    /**
     * Disable XML-RPC
     */
    public function disable_xmlrpc($enabled): bool {
        return apply_filters('tmu_enable_xmlrpc', false);
    }
    
    /**
     * Restrict REST API
     */
    public function restrict_rest_api($access) {
        if (!is_user_logged_in() && !apply_filters('tmu_allow_public_rest_api', false)) {
            return new \WP_Error(
                'rest_not_logged_in',
                __('You are not currently logged in.', 'tmu-theme'),
                ['status' => 401]
            );
        }
        
        return $access;
    }
    
    /**
     * Remove server signature
     */
    public function remove_server_signature(): void {
        // Additional server signature removal
        if (function_exists('apache_setenv')) {
            apache_setenv('ServerTokens', 'Prod');
            apache_setenv('ServerSignature', 'Off');
        }
    }
    
    /**
     * Add AJAX security headers
     */
    public function add_ajax_security_headers(): void {
        if (headers_sent()) {
            return;
        }
        
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Cache-Control: no-cache, no-store, must-revalidate');
    }
    
    /**
     * Add REST API headers
     */
    public function add_rest_api_headers(): void {
        add_action('rest_api_init', function() {
            if (headers_sent()) {
                return;
            }
            
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            header('Cross-Origin-Resource-Policy: same-site');
        });
    }
    
    /**
     * Check if CSP violations
     */
    public function handle_csp_violations(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
            $_SERVER['CONTENT_TYPE'] === 'application/csp-report') {
            
            $input = file_get_contents('php://input');
            $report = json_decode($input, true);
            
            if ($report && isset($report['csp-report'])) {
                $this->log_csp_violation($report['csp-report']);
            }
        }
    }
    
    /**
     * Log CSP violation
     */
    private function log_csp_violation($violation): void {
        do_action('tmu_security_event', 'csp_violation', [
            'blocked_uri' => $violation['blocked-uri'] ?? '',
            'document_uri' => $violation['document-uri'] ?? '',
            'violated_directive' => $violation['violated-directive'] ?? '',
            'original_policy' => $violation['original-policy'] ?? '',
            'ip' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'user_id' => get_current_user_id()
        ], 'medium');
    }
    
    /**
     * Get security headers status
     */
    public function get_headers_status(): array {
        $status = [];
        
        foreach ($this->headers_config as $header_key => $config) {
            $status[$header_key] = [
                'enabled' => $config['enabled'],
                'description' => $config['description'],
                'header_name' => $this->get_header_name($header_key),
                'value' => $this->get_header_value($config)
            ];
        }
        
        return $status;
    }
    
    /**
     * Update header configuration
     */
    public function update_header_config($header_key, $config): void {
        if (isset($this->headers_config[$header_key])) {
            $this->headers_config[$header_key] = array_merge(
                $this->headers_config[$header_key],
                $config
            );
        }
    }
    
    /**
     * Enable header
     */
    public function enable_header($header_key): void {
        if (isset($this->headers_config[$header_key])) {
            $this->headers_config[$header_key]['enabled'] = true;
        }
    }
    
    /**
     * Disable header
     */
    public function disable_header($header_key): void {
        if (isset($this->headers_config[$header_key])) {
            $this->headers_config[$header_key]['enabled'] = false;
        }
    }
    
    /**
     * Add custom CSP directive
     */
    public function add_csp_directive($directive, $values): void {
        if (!isset($this->headers_config['content_security_policy']['value'])) {
            return;
        }
        
        $current_csp = $this->headers_config['content_security_policy']['value'];
        
        if (is_array($current_csp)) {
            // Find existing directive or add new one
            $found = false;
            foreach ($current_csp as $index => $csp_directive) {
                if (strpos($csp_directive, $directive) === 0) {
                    $current_csp[$index] = $directive . ' ' . implode(' ', (array)$values);
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $current_csp[] = $directive . ' ' . implode(' ', (array)$values);
            }
            
            $this->headers_config['content_security_policy']['value'] = $current_csp;
        }
    }
    
    /**
     * Remove CSP directive
     */
    public function remove_csp_directive($directive): void {
        if (!isset($this->headers_config['content_security_policy']['value'])) {
            return;
        }
        
        $current_csp = $this->headers_config['content_security_policy']['value'];
        
        if (is_array($current_csp)) {
            $this->headers_config['content_security_policy']['value'] = array_filter(
                $current_csp,
                function($csp_directive) use ($directive) {
                    return strpos($csp_directive, $directive) !== 0;
                }
            );
        }
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
     * Test security headers
     */
    public function test_headers(): array {
        $test_results = [];
        
        // Test if headers are being sent
        ob_start();
        $this->set_security_headers();
        $headers = headers_list();
        ob_end_clean();
        
        foreach ($this->headers_config as $header_key => $config) {
            if (!$config['enabled']) {
                continue;
            }
            
            $header_name = $this->get_header_name($header_key);
            $found = false;
            
            foreach ($headers as $header) {
                if (stripos($header, $header_name . ':') === 0) {
                    $found = true;
                    break;
                }
            }
            
            $test_results[$header_key] = [
                'enabled' => $config['enabled'],
                'found' => $found,
                'header_name' => $header_name,
                'status' => $found ? 'pass' : 'fail'
            ];
        }
        
        return $test_results;
    }
    
    /**
     * Generate security report
     */
    public function generate_security_report(): array {
        $report = [
            'timestamp' => current_time('mysql'),
            'headers_status' => $this->get_headers_status(),
            'test_results' => $this->test_headers(),
            'recommendations' => $this->get_security_recommendations()
        ];
        
        return $report;
    }
    
    /**
     * Get security recommendations
     */
    private function get_security_recommendations(): array {
        $recommendations = [];
        
        // Check HTTPS
        if (!is_ssl()) {
            $recommendations[] = [
                'priority' => 'high',
                'message' => 'Enable HTTPS for enhanced security',
                'action' => 'Install SSL certificate and configure HTTPS'
            ];
        }
        
        // Check CSP
        if (!$this->headers_config['content_security_policy']['enabled']) {
            $recommendations[] = [
                'priority' => 'high',
                'message' => 'Enable Content Security Policy',
                'action' => 'Configure CSP headers to prevent XSS attacks'
            ];
        }
        
        // Check HSTS
        if (!$this->headers_config['strict_transport_security']['enabled'] && is_ssl()) {
            $recommendations[] = [
                'priority' => 'medium',
                'message' => 'Enable HSTS (HTTP Strict Transport Security)',
                'action' => 'Configure HSTS headers for HTTPS enforcement'
            ];
        }
        
        return $recommendations;
    }
}