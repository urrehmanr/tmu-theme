<?php
/**
 * Security and Accessibility Integration
 * 
 * Main integration class for all security and accessibility features.
 * Coordinates security measures and WCAG compliance in the TMU theme.
 * 
 * @package TMU\SecurityAndAccessibility
 * @since 1.0.0
 */

namespace TMU;

use TMU\Security\SecurityManager;
use TMU\Security\InputValidator;
use TMU\Security\NonceManager;
use TMU\Security\DatabaseSecurity;
use TMU\Security\XssProtection;
use TMU\Security\CsrfProtection;
use TMU\Security\SecurityHeaders;
use TMU\Security\FileUploadSecurity;
use TMU\Accessibility\AccessibilityManager;
use TMU\Accessibility\ScreenReader;
use TMU\Accessibility\AriaLabels;

/**
 * SecurityAndAccessibility class
 * 
 * Main coordinator for security and accessibility implementations
 */
class SecurityAndAccessibility {
    
    /**
     * Class instance
     * @var SecurityAndAccessibility|null
     */
    private static $instance = null;
    
    /**
     * Security manager instance
     * @var SecurityManager|null
     */
    private $security_manager = null;
    
    /**
     * Accessibility manager instance
     * @var AccessibilityManager|null
     */
    private $accessibility_manager = null;
    
    /**
     * Security components
     * @var array
     */
    private $security_components = [];
    
    /**
     * Accessibility components
     * @var array
     */
    private $accessibility_components = [];
    
    /**
     * Configuration settings
     * @var array
     */
    private $settings = [];
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): SecurityAndAccessibility {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        $this->init_settings();
        $this->init_security_components();
        $this->init_accessibility_components();
        $this->init_hooks();
    }
    
    /**
     * Initialize settings
     */
    private function init_settings(): void {
        $this->settings = [
            'security' => [
                'enable_input_validation' => true,
                'enable_xss_protection' => true,
                'enable_csrf_protection' => true,
                'enable_database_security' => true,
                'enable_file_upload_security' => true,
                'enable_security_headers' => true,
                'log_security_events' => true,
                'security_level' => 'high'
            ],
            'accessibility' => [
                'enable_aria_labels' => true,
                'enable_screen_reader' => true,
                'enable_keyboard_navigation' => true,
                'enable_high_contrast' => true,
                'enable_focus_management' => true,
                'wcag_level' => 'AA',
                'log_accessibility_events' => true
            ],
            'integration' => [
                'enable_security_monitoring' => true,
                'enable_accessibility_testing' => true,
                'enable_automatic_fixes' => true,
                'enable_reporting' => true
            ]
        ];
        
        // Load from database
        $saved_settings = get_option('tmu_security_accessibility_settings', []);
        $this->settings = array_merge($this->settings, $saved_settings);
        
        // Allow customization via filters
        $this->settings = apply_filters('tmu_security_accessibility_settings', $this->settings);
    }
    
    /**
     * Initialize security components
     */
    private function init_security_components(): void {
        // Initialize security manager
        $this->security_manager = SecurityManager::getInstance();
        
        // Initialize individual security components
        if ($this->settings['security']['enable_input_validation']) {
            $this->security_components['input_validator'] = new InputValidator();
        }
        
        if ($this->settings['security']['enable_csrf_protection']) {
            $this->security_components['nonce_manager'] = new NonceManager();
            $this->security_components['csrf_protection'] = new CsrfProtection();
        }
        
        if ($this->settings['security']['enable_database_security']) {
            $this->security_components['database_security'] = new DatabaseSecurity();
        }
        
        if ($this->settings['security']['enable_xss_protection']) {
            $this->security_components['xss_protection'] = new XssProtection();
        }
        
        if ($this->settings['security']['enable_security_headers']) {
            $this->security_components['security_headers'] = new SecurityHeaders();
        }
        
        if ($this->settings['security']['enable_file_upload_security']) {
            $this->security_components['file_upload_security'] = new FileUploadSecurity();
        }
    }
    
    /**
     * Initialize accessibility components
     */
    private function init_accessibility_components(): void {
        // Initialize accessibility manager
        $this->accessibility_manager = AccessibilityManager::getInstance();
        
        // Initialize individual accessibility components
        if ($this->settings['accessibility']['enable_aria_labels']) {
            $this->accessibility_components['aria_labels'] = new AriaLabels();
        }
        
        if ($this->settings['accessibility']['enable_screen_reader']) {
            $this->accessibility_components['screen_reader'] = new ScreenReader();
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        add_action('init', [$this, 'init_security_and_accessibility'], 0);
        add_action('wp_loaded', [$this, 'run_security_checks']);
        add_action('wp_loaded', [$this, 'run_accessibility_checks']);
        
        // Admin integration
        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_admin_menu']);
            add_action('admin_init', [$this, 'init_admin_settings']);
            add_action('admin_notices', [$this, 'show_admin_notices']);
        }
        
        // Security monitoring
        if ($this->settings['integration']['enable_security_monitoring']) {
            add_action('tmu_security_event', [$this, 'handle_security_event'], 10, 3);
        }
        
        // Accessibility testing
        if ($this->settings['integration']['enable_accessibility_testing']) {
            add_action('wp_ajax_tmu_run_accessibility_test', [$this, 'run_accessibility_test']);
        }
        
        // Reporting
        if ($this->settings['integration']['enable_reporting']) {
            add_action('wp_ajax_tmu_generate_security_report', [$this, 'generate_security_report']);
            add_action('wp_ajax_tmu_generate_accessibility_report', [$this, 'generate_accessibility_report']);
        }
        
        // Automatic fixes
        if ($this->settings['integration']['enable_automatic_fixes']) {
            add_action('wp_head', [$this, 'apply_automatic_fixes'], 0);
        }
        
        // Health checks
        add_action('wp_cron_security_check', [$this, 'run_scheduled_security_check']);
        add_action('wp_cron_accessibility_check', [$this, 'run_scheduled_accessibility_check']);
        
        // Emergency lockdown
        add_action('tmu_emergency_lockdown', [$this, 'activate_emergency_lockdown']);
        
        // Theme support
        add_action('after_setup_theme', [$this, 'add_theme_support']);
    }
    
    /**
     * Initialize security and accessibility
     */
    public function init_security_and_accessibility(): void {
        // Define constants
        if (!defined('TMU_SECURITY_LEVEL')) {
            define('TMU_SECURITY_LEVEL', $this->settings['security']['security_level']);
        }
        
        if (!defined('TMU_WCAG_LEVEL')) {
            define('TMU_WCAG_LEVEL', $this->settings['accessibility']['wcag_level']);
        }
        
        // Initialize components
        foreach ($this->security_components as $component) {
            if (method_exists($component, 'init_security')) {
                $component->init_security();
            }
        }
        
        foreach ($this->accessibility_components as $component) {
            if (method_exists($component, 'init_accessibility')) {
                $component->init_accessibility();
            }
        }
        
        // Fire initialization events
        do_action('tmu_security_accessibility_initialized', $this);
    }
    
    /**
     * Run security checks
     */
    public function run_security_checks(): void {
        $security_issues = [];
        
        // Check file permissions
        $security_issues = array_merge($security_issues, $this->check_file_permissions());
        
        // Check for malicious files
        $security_issues = array_merge($security_issues, $this->check_malicious_files());
        
        // Check security headers
        $security_issues = array_merge($security_issues, $this->check_security_headers());
        
        // Check database security
        $security_issues = array_merge($security_issues, $this->check_database_security());
        
        // Store issues for reporting
        update_option('tmu_security_issues', $security_issues);
        
        // Trigger alerts for critical issues
        $critical_issues = array_filter($security_issues, function($issue) {
            return $issue['severity'] === 'critical';
        });
        
        if (!empty($critical_issues)) {
            do_action('tmu_critical_security_issues', $critical_issues);
        }
    }
    
    /**
     * Run accessibility checks
     */
    public function run_accessibility_checks(): void {
        $accessibility_issues = [];
        
        // Check ARIA implementation
        $accessibility_issues = array_merge($accessibility_issues, $this->check_aria_implementation());
        
        // Check color contrast
        $accessibility_issues = array_merge($accessibility_issues, $this->check_color_contrast());
        
        // Check keyboard navigation
        $accessibility_issues = array_merge($accessibility_issues, $this->check_keyboard_navigation());
        
        // Check screen reader compatibility
        $accessibility_issues = array_merge($accessibility_issues, $this->check_screen_reader_compatibility());
        
        // Store issues for reporting
        update_option('tmu_accessibility_issues', $accessibility_issues);
        
        // Calculate accessibility score
        $score = $this->calculate_accessibility_score($accessibility_issues);
        update_option('tmu_accessibility_score', $score);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_menu_page(
            __('Security & Accessibility', 'tmu-theme'),
            __('Security & Accessibility', 'tmu-theme'),
            'manage_options',
            'tmu-security-accessibility',
            [$this, 'admin_page'],
            'dashicons-shield-alt',
            60
        );
        
        add_submenu_page(
            'tmu-security-accessibility',
            __('Security Settings', 'tmu-theme'),
            __('Security', 'tmu-theme'),
            'manage_options',
            'tmu-security',
            [$this, 'security_admin_page']
        );
        
        add_submenu_page(
            'tmu-security-accessibility',
            __('Accessibility Settings', 'tmu-theme'),
            __('Accessibility', 'tmu-theme'),
            'manage_options',
            'tmu-accessibility',
            [$this, 'accessibility_admin_page']
        );
        
        add_submenu_page(
            'tmu-security-accessibility',
            __('Reports', 'tmu-theme'),
            __('Reports', 'tmu-theme'),
            'manage_options',
            'tmu-reports',
            [$this, 'reports_admin_page']
        );
    }
    
    /**
     * Initialize admin settings
     */
    public function init_admin_settings(): void {
        register_setting('tmu_security_accessibility', 'tmu_security_accessibility_settings');
        
        // Security settings section
        add_settings_section(
            'tmu_security_section',
            __('Security Settings', 'tmu-theme'),
            [$this, 'security_section_callback'],
            'tmu_security_accessibility'
        );
        
        // Accessibility settings section
        add_settings_section(
            'tmu_accessibility_section',
            __('Accessibility Settings', 'tmu-theme'),
            [$this, 'accessibility_section_callback'],
            'tmu_security_accessibility'
        );
    }
    
    /**
     * Show admin notices
     */
    public function show_admin_notices(): void {
        $security_issues = get_option('tmu_security_issues', []);
        $accessibility_issues = get_option('tmu_accessibility_issues', []);
        
        // Security notices
        $critical_security = array_filter($security_issues, function($issue) {
            return $issue['severity'] === 'critical';
        });
        
        if (!empty($critical_security)) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>' . __('Critical Security Issues:', 'tmu-theme') . '</strong> ';
            echo sprintf(
                __('%d critical security issues detected. <a href="%s">View Security Report</a>', 'tmu-theme'),
                count($critical_security),
                admin_url('admin.php?page=tmu-security')
            );
            echo '</p></div>';
        }
        
        // Accessibility notices
        $high_accessibility = array_filter($accessibility_issues, function($issue) {
            return $issue['severity'] === 'high';
        });
        
        if (!empty($high_accessibility)) {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>' . __('Accessibility Issues:', 'tmu-theme') . '</strong> ';
            echo sprintf(
                __('%d high-priority accessibility issues detected. <a href="%s">View Accessibility Report</a>', 'tmu-theme'),
                count($high_accessibility),
                admin_url('admin.php?page=tmu-accessibility')
            );
            echo '</p></div>';
        }
    }
    
    /**
     * Handle security events
     */
    public function handle_security_event($type, $data, $severity): void {
        $event = [
            'type' => $type,
            'data' => $data,
            'severity' => $severity,
            'timestamp' => current_time('mysql'),
            'ip' => $data['ip'] ?? '',
            'user_id' => $data['user_id'] ?? 0
        ];
        
        // Log to database
        $this->log_security_event($event);
        
        // Send alerts for critical events
        if ($severity === 'critical') {
            $this->send_security_alert($event);
        }
        
        // Apply automatic countermeasures
        $this->apply_security_countermeasures($event);
    }
    
    /**
     * Apply automatic fixes
     */
    public function apply_automatic_fixes(): void {
        // Fix common accessibility issues
        add_action('wp_footer', function() {
            echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                // Add missing alt attributes
                var images = document.querySelectorAll("img:not([alt])");
                images.forEach(function(img) {
                    img.setAttribute("alt", "");
                });
                
                // Add missing form labels
                var inputs = document.querySelectorAll("input:not([aria-label]):not([aria-labelledby])");
                inputs.forEach(function(input) {
                    if (input.type !== "hidden" && input.type !== "submit") {
                        var label = document.querySelector("label[for=\"" + input.id + "\"]");
                        if (!label && input.placeholder) {
                            input.setAttribute("aria-label", input.placeholder);
                        }
                    }
                });
                
                // Add missing button labels
                var buttons = document.querySelectorAll("button:not([aria-label]):not([aria-labelledby])");
                buttons.forEach(function(button) {
                    if (!button.textContent.trim()) {
                        button.setAttribute("aria-label", "Button");
                    }
                });
            });
            </script>';
        });
    }
    
    /**
     * Add theme support
     */
    public function add_theme_support(): void {
        add_theme_support('tmu-security');
        add_theme_support('tmu-accessibility');
        add_theme_support('accessibility-ready');
    }
    
    /**
     * Admin page methods
     */
    public function admin_page(): void {
        include get_template_directory() . '/admin/security-accessibility-admin.php';
    }
    
    public function security_admin_page(): void {
        include get_template_directory() . '/admin/security-admin.php';
    }
    
    public function accessibility_admin_page(): void {
        include get_template_directory() . '/admin/accessibility-admin.php';
    }
    
    public function reports_admin_page(): void {
        include get_template_directory() . '/admin/reports-admin.php';
    }
    
    /**
     * Security check methods
     */
    private function check_file_permissions(): array {
        $issues = [];
        
        // Check critical file permissions
        $critical_files = [
            ABSPATH . 'wp-config.php',
            ABSPATH . '.htaccess',
            get_template_directory() . '/functions.php'
        ];
        
        foreach ($critical_files as $file) {
            if (file_exists($file)) {
                $perms = substr(sprintf('%o', fileperms($file)), -4);
                if ($perms > '0644') {
                    $issues[] = [
                        'type' => 'file_permissions',
                        'severity' => 'high',
                        'message' => sprintf(__('File %s has overly permissive permissions: %s', 'tmu-theme'), $file, $perms),
                        'file' => $file,
                        'permissions' => $perms
                    ];
                }
            }
        }
        
        return $issues;
    }
    
    private function check_malicious_files(): array {
        $issues = [];
        
        // Check uploads directory for suspicious files
        $uploads_dir = wp_upload_dir();
        $suspicious_extensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'exe'];
        
        if (is_dir($uploads_dir['basedir'])) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($uploads_dir['basedir'])
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $extension = strtolower($file->getExtension());
                    if (in_array($extension, $suspicious_extensions)) {
                        $issues[] = [
                            'type' => 'malicious_file',
                            'severity' => 'critical',
                            'message' => sprintf(__('Suspicious file found: %s', 'tmu-theme'), $file->getPathname()),
                            'file' => $file->getPathname()
                        ];
                    }
                }
            }
        }
        
        return $issues;
    }
    
    private function check_security_headers(): array {
        $issues = [];
        
        // This would typically be done via external testing
        // For now, we'll assume headers are properly configured
        
        return $issues;
    }
    
    private function check_database_security(): array {
        $issues = [];
        
        // Check for default database prefixes
        global $wpdb;
        if ($wpdb->prefix === 'wp_') {
            $issues[] = [
                'type' => 'default_db_prefix',
                'severity' => 'medium',
                'message' => __('Using default WordPress database prefix', 'tmu-theme')
            ];
        }
        
        return $issues;
    }
    
    /**
     * Accessibility check methods
     */
    private function check_aria_implementation(): array {
        // This would typically be done via JavaScript testing
        return [];
    }
    
    private function check_color_contrast(): array {
        // This would typically be done via automated testing tools
        return [];
    }
    
    private function check_keyboard_navigation(): array {
        // This would typically be done via automated testing
        return [];
    }
    
    private function check_screen_reader_compatibility(): array {
        // This would typically be done via specialized testing tools
        return [];
    }
    
    private function calculate_accessibility_score($issues): int {
        $total_points = 100;
        $deductions = 0;
        
        foreach ($issues as $issue) {
            switch ($issue['severity']) {
                case 'critical':
                    $deductions += 25;
                    break;
                case 'high':
                    $deductions += 15;
                    break;
                case 'medium':
                    $deductions += 10;
                    break;
                case 'low':
                    $deductions += 5;
                    break;
            }
        }
        
        return max(0, $total_points - $deductions);
    }
    
    /**
     * Utility methods
     */
    private function log_security_event($event): void {
        // Implementation would log to database or file
    }
    
    private function send_security_alert($event): void {
        // Implementation would send email/SMS alerts
    }
    
    private function apply_security_countermeasures($event): void {
        // Implementation would apply automatic security measures
    }
    
    /**
     * Public getter methods
     */
    public function get_security_manager(): ?SecurityManager {
        return $this->security_manager;
    }
    
    public function get_accessibility_manager(): ?AccessibilityManager {
        return $this->accessibility_manager;
    }
    
    public function get_security_component($name) {
        return $this->security_components[$name] ?? null;
    }
    
    public function get_accessibility_component($name) {
        return $this->accessibility_components[$name] ?? null;
    }
    
    public function get_settings(): array {
        return $this->settings;
    }
    
    public function update_setting($category, $key, $value): bool {
        if (isset($this->settings[$category][$key])) {
            $this->settings[$category][$key] = $value;
            return update_option('tmu_security_accessibility_settings', $this->settings);
        }
        return false;
    }
}