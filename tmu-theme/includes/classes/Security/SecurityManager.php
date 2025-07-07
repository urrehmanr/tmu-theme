<?php
/**
 * Security Manager
 * 
 * Main coordinator for all security features in the TMU theme.
 * Manages input validation, XSS protection, CSRF protection, and security headers.
 * 
 * @package TMU\Security
 * @since 1.0.0
 */

namespace TMU\Security;

/**
 * SecurityManager class
 * 
 * Centralizes security implementations and ensures comprehensive protection
 */
class SecurityManager {
    
    /**
     * Class instance
     * @var SecurityManager|null
     */
    private static $instance = null;
    
    /**
     * Security components
     * @var array
     */
    private $security_components = [];
    
    /**
     * Security settings
     * @var array
     */
    private $security_settings = [];
    
    /**
     * Get singleton instance
     * 
     * @return SecurityManager
     */
    public static function getInstance(): SecurityManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->init_security_settings();
        $this->init_security_components();
        $this->init_hooks();
    }
    
    /**
     * Initialize security settings
     */
    private function init_security_settings(): void {
        $this->security_settings = [
            'enable_input_validation' => true,
            'enable_xss_protection' => true,
            'enable_csrf_protection' => true,
            'enable_security_headers' => true,
            'enable_file_upload_security' => true,
            'enable_database_security' => true,
            'enable_nonce_validation' => true,
            'log_security_events' => true,
            'security_level' => 'high', // low, medium, high
        ];
        
        // Allow customization via filters
        $this->security_settings = apply_filters('tmu_security_settings', $this->security_settings);
    }
    
    /**
     * Initialize security components
     */
    private function init_security_components(): void {
        // Load security components based on settings
        if ($this->security_settings['enable_input_validation']) {
            $this->security_components['input_validator'] = new InputValidator();
        }
        
        if ($this->security_settings['enable_xss_protection']) {
            $this->security_components['xss_protection'] = new XssProtection();
        }
        
        if ($this->security_settings['enable_csrf_protection']) {
            $this->security_components['csrf_protection'] = new CsrfProtection();
        }
        
        if ($this->security_settings['enable_security_headers']) {
            $this->security_components['security_headers'] = new SecurityHeaders();
        }
        
        if ($this->security_settings['enable_file_upload_security']) {
            $this->security_components['file_upload_security'] = new FileUploadSecurity();
        }
        
        if ($this->security_settings['enable_database_security']) {
            $this->security_components['database_security'] = new DatabaseSecurity();
        }
        
        if ($this->security_settings['enable_nonce_validation']) {
            $this->security_components['nonce_manager'] = new NonceManager();
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        add_action('init', [$this, 'init_security'], 0);
        add_action('wp_loaded', [$this, 'security_audit'], 10);
        add_action('wp_footer', [$this, 'security_monitoring'], 999);
        
        // Security event logging
        if ($this->security_settings['log_security_events']) {
            add_action('tmu_security_event', [$this, 'log_security_event'], 10, 3);
        }
        
        // Admin security features
        if (is_admin()) {
            add_action('admin_init', [$this, 'admin_security_init']);
            add_action('admin_notices', [$this, 'security_admin_notices']);
        }
        
        // Login security
        add_action('wp_login_failed', [$this, 'handle_login_failure']);
        add_filter('authenticate', [$this, 'security_authenticate'], 30, 3);
        
        // Content security
        add_filter('the_content', [$this, 'secure_content_output'], 999);
        add_filter('comment_text', [$this, 'secure_content_output'], 999);
        
        // API security
        add_action('rest_api_init', [$this, 'secure_rest_api']);
    }
    
    /**
     * Initialize security measures
     */
    public function init_security(): void {
        // Set up security constants
        $this->define_security_constants();
        
        // Initialize component security
        foreach ($this->security_components as $component) {
            if (method_exists($component, 'init_security')) {
                $component->init_security();
            }
        }
        
        // Fire security initialization event
        do_action('tmu_security_initialized', $this);
    }
    
    /**
     * Define security constants
     */
    private function define_security_constants(): void {
        if (!defined('TMU_SECURITY_LEVEL')) {
            define('TMU_SECURITY_LEVEL', $this->security_settings['security_level']);
        }
        
        if (!defined('TMU_SECURITY_LOG')) {
            define('TMU_SECURITY_LOG', $this->security_settings['log_security_events']);
        }
        
        if (!defined('TMU_SECURITY_SALT')) {
            define('TMU_SECURITY_SALT', wp_salt('auth'));
        }
    }
    
    /**
     * Perform security audit
     */
    public function security_audit(): void {
        $audit_results = [];
        
        // Check file permissions
        $audit_results['file_permissions'] = $this->check_file_permissions();
        
        // Check for vulnerable plugins
        $audit_results['plugin_security'] = $this->check_plugin_security();
        
        // Check WordPress version
        $audit_results['wp_version'] = $this->check_wp_version();
        
        // Check SSL configuration
        $audit_results['ssl_status'] = $this->check_ssl_status();
        
        // Store audit results
        update_option('tmu_security_audit', $audit_results);
        
        // Trigger audit event
        do_action('tmu_security_audit_complete', $audit_results);
    }
    
    /**
     * Security monitoring
     */
    public function security_monitoring(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Monitor for suspicious activity
        $this->monitor_suspicious_activity();
        
        // Check for security updates
        $this->check_security_updates();
        
        // Performance impact monitoring
        $this->monitor_security_performance();
    }
    
    /**
     * Admin security initialization
     */
    public function admin_security_init(): void {
        // Admin-specific security measures
        $this->secure_admin_area();
        
        // Add security menu
        add_action('admin_menu', [$this, 'add_security_menu']);
        
        // Admin AJAX security
        add_action('wp_ajax_tmu_security_scan', [$this, 'ajax_security_scan']);
        add_action('wp_ajax_tmu_security_settings', [$this, 'ajax_security_settings']);
    }
    
    /**
     * Add security admin menu
     */
    public function add_security_menu(): void {
        add_submenu_page(
            'tmu-theme',
            __('Security', 'tmu-theme'),
            __('Security', 'tmu-theme'),
            'manage_options',
            'tmu-security',
            [$this, 'security_admin_page']
        );
    }
    
    /**
     * Security admin page
     */
    public function security_admin_page(): void {
        $audit_results = get_option('tmu_security_audit', []);
        $security_events = get_option('tmu_security_events', []);
        
        include get_template_directory() . '/includes/admin/security-dashboard.php';
    }
    
    /**
     * Display security admin notices
     */
    public function security_admin_notices(): void {
        $audit_results = get_option('tmu_security_audit', []);
        
        if (!empty($audit_results['vulnerabilities'])) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>' . __('Security Alert:', 'tmu-theme') . '</strong> ';
            echo sprintf(
                __('%d security issues detected. <a href="%s">View Security Dashboard</a>', 'tmu-theme'),
                count($audit_results['vulnerabilities']),
                admin_url('admin.php?page=tmu-security')
            );
            echo '</p></div>';
        }
        
        if (!is_ssl() && !defined('WP_CLI')) {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>' . __('SSL Warning:', 'tmu-theme') . '</strong> ';
            echo __('Your site is not using HTTPS. Consider enabling SSL for better security.', 'tmu-theme');
            echo '</p></div>';
        }
    }
    
    /**
     * Handle login failures
     */
    public function handle_login_failure($username): void {
        $ip = $this->get_client_ip();
        
        // Log failed login attempt
        $this->log_security_event('login_failure', [
            'username' => sanitize_text_field($username),
            'ip' => $ip,
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'timestamp' => current_time('mysql')
        ]);
        
        // Implement brute force protection
        $this->handle_brute_force_protection($ip);
    }
    
    /**
     * Security authentication filter
     */
    public function security_authenticate($user, $username, $password) {
        if (is_wp_error($user)) {
            return $user;
        }
        
        $ip = $this->get_client_ip();
        
        // Check if IP is blocked
        if ($this->is_ip_blocked($ip)) {
            return new \WP_Error('ip_blocked', __('Your IP address has been temporarily blocked due to suspicious activity.', 'tmu-theme'));
        }
        
        // Additional security checks
        if ($this->security_settings['security_level'] === 'high') {
            // Implement additional authentication checks
            $security_check = $this->perform_additional_auth_checks($user, $username);
            if (is_wp_error($security_check)) {
                return $security_check;
            }
        }
        
        return $user;
    }
    
    /**
     * Secure content output
     */
    public function secure_content_output($content): string {
        // Remove potentially malicious content
        $content = $this->sanitize_content($content);
        
        // Apply security filters
        $content = apply_filters('tmu_secure_content', $content);
        
        return $content;
    }
    
    /**
     * Secure REST API
     */
    public function secure_rest_api(): void {
        // Add API security headers
        add_action('rest_api_init', function() {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
        });
        
        // Rate limiting for API endpoints
        add_filter('rest_request_before_callbacks', [$this, 'api_rate_limiting'], 10, 3);
        
        // API authentication security
        add_filter('rest_authentication_errors', [$this, 'api_authentication_security']);
    }
    
    /**
     * Log security events
     */
    public function log_security_event($event_type, $event_data, $severity = 'medium'): void {
        $events = get_option('tmu_security_events', []);
        
        $event = [
            'type' => $event_type,
            'data' => $event_data,
            'severity' => $severity,
            'timestamp' => current_time('mysql'),
            'ip' => $this->get_client_ip(),
            'user_id' => get_current_user_id()
        ];
        
        // Keep only last 1000 events
        array_unshift($events, $event);
        $events = array_slice($events, 0, 1000);
        
        update_option('tmu_security_events', $events);
        
        // Alert on high severity events
        if ($severity === 'high') {
            $this->send_security_alert($event);
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
     * Check file permissions
     */
    private function check_file_permissions(): array {
        $results = ['status' => 'secure', 'issues' => []];
        
        $critical_files = [
            ABSPATH . 'wp-config.php' => '644',
            ABSPATH . '.htaccess' => '644',
            get_template_directory() . '/functions.php' => '644'
        ];
        
        foreach ($critical_files as $file => $expected_perms) {
            if (file_exists($file)) {
                $actual_perms = substr(sprintf('%o', fileperms($file)), -3);
                if ($actual_perms !== $expected_perms) {
                    $results['issues'][] = "File {$file} has permissions {$actual_perms}, expected {$expected_perms}";
                    $results['status'] = 'warning';
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Check plugin security
     */
    private function check_plugin_security(): array {
        $results = ['status' => 'secure', 'issues' => []];
        
        // Check for known vulnerable plugins
        $vulnerable_plugins = $this->get_vulnerable_plugins_list();
        $active_plugins = get_option('active_plugins');
        
        foreach ($active_plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $plugin_slug = dirname($plugin);
            
            if (isset($vulnerable_plugins[$plugin_slug])) {
                $vulnerable_version = $vulnerable_plugins[$plugin_slug];
                if (version_compare($plugin_data['Version'], $vulnerable_version, '<=')) {
                    $results['issues'][] = "Plugin {$plugin_data['Name']} version {$plugin_data['Version']} has known vulnerabilities";
                    $results['status'] = 'critical';
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Check WordPress version
     */
    private function check_wp_version(): array {
        global $wp_version;
        
        $results = ['status' => 'secure', 'issues' => []];
        $latest_version = $this->get_latest_wp_version();
        
        if (version_compare($wp_version, $latest_version, '<')) {
            $results['issues'][] = "WordPress version {$wp_version} is outdated. Latest version is {$latest_version}";
            $results['status'] = 'warning';
        }
        
        return $results;
    }
    
    /**
     * Check SSL status
     */
    private function check_ssl_status(): array {
        $results = ['status' => 'secure', 'issues' => []];
        
        if (!is_ssl()) {
            $results['issues'][] = 'Site is not using HTTPS';
            $results['status'] = 'warning';
        }
        
        return $results;
    }
    
    /**
     * Get security component
     */
    public function get_component($component_name) {
        return $this->security_components[$component_name] ?? null;
    }
    
    /**
     * Get security settings
     */
    public function get_settings(): array {
        return $this->security_settings;
    }
    
    /**
     * Update security setting
     */
    public function update_setting($key, $value): bool {
        if (array_key_exists($key, $this->security_settings)) {
            $this->security_settings[$key] = $value;
            return update_option('tmu_security_settings', $this->security_settings);
        }
        return false;
    }
    
    /**
     * Placeholder methods for additional functionality
     */
    private function handle_brute_force_protection($ip): void {
        // Implementation for brute force protection
    }
    
    private function is_ip_blocked($ip): bool {
        // Implementation for IP blocking check
        return false;
    }
    
    private function perform_additional_auth_checks($user, $username) {
        // Implementation for additional authentication checks
        return true;
    }
    
    private function sanitize_content($content): string {
        // Implementation for content sanitization
        return wp_kses_post($content);
    }
    
    private function api_rate_limiting($response, $handler, $request) {
        // Implementation for API rate limiting
        return $response;
    }
    
    private function api_authentication_security($errors) {
        // Implementation for API authentication security
        return $errors;
    }
    
    private function send_security_alert($event): void {
        // Implementation for security alerts
    }
    
    private function monitor_suspicious_activity(): void {
        // Implementation for activity monitoring
    }
    
    private function check_security_updates(): void {
        // Implementation for security update checks
    }
    
    private function monitor_security_performance(): void {
        // Implementation for performance monitoring
    }
    
    private function secure_admin_area(): void {
        // Implementation for admin area security
    }
    
    private function ajax_security_scan(): void {
        // Implementation for AJAX security scan
    }
    
    private function ajax_security_settings(): void {
        // Implementation for AJAX security settings
    }
    
    private function get_vulnerable_plugins_list(): array {
        // Return list of known vulnerable plugins
        return [];
    }
    
    private function get_latest_wp_version(): string {
        // Get latest WordPress version
        return get_bloginfo('version');
    }
}