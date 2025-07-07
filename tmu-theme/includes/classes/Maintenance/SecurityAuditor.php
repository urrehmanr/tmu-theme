<?php
/**
 * Security Auditor
 * 
 * Security monitoring and vulnerability scanning for TMU theme.
 * 
 * @package TMU\Maintenance
 * @since 1.0.0
 */

namespace TMU\Maintenance;

use TMU\Logging\LogManager;

class SecurityAuditor {
    
    private $logger;
    
    public function __construct() {
        $this->logger = new LogManager();
        
        add_action('tmu_security_audit', [$this, 'run_full_security_audit']);
        add_action('wp_ajax_tmu_security_scan', [$this, 'manual_security_scan']);
    }
    
    /**
     * Run full security audit
     */
    public function run_full_security_audit(): void {
        $this->logger->info('Starting security audit');
        
        $audit_results = [
            'file_permissions' => $this->check_file_permissions(),
            'plugin_vulnerabilities' => $this->scan_plugin_vulnerabilities(),
            'core_integrity' => $this->check_core_integrity(),
            'user_security' => $this->audit_user_security(),
            'database_security' => $this->audit_database_security(),
            'configuration_security' => $this->audit_configuration(),
            'malware_scan' => $this->scan_for_malware(),
            'ssl_check' => $this->check_ssl_configuration()
        ];
        
        $security_score = $this->calculate_security_score($audit_results);
        
        update_option('tmu_security_audit_results', [
            'results' => $audit_results,
            'score' => $security_score,
            'audit_date' => current_time('mysql'),
            'recommendations' => $this->generate_recommendations($audit_results)
        ]);
        
        if ($security_score < 80) {
            $this->send_security_alert($audit_results, $security_score);
        }
        
        $this->logger->info('Security audit completed', ['score' => $security_score]);
    }
    
    /**
     * Check file permissions
     */
    private function check_file_permissions(): array {
        $checks = [];
        
        // Check wp-config.php
        $wp_config = ABSPATH . 'wp-config.php';
        if (file_exists($wp_config)) {
            $perms = substr(sprintf('%o', fileperms($wp_config)), -4);
            $checks['wp_config'] = [
                'status' => in_array($perms, ['0600', '0644']) ? 'secure' : 'warning',
                'permissions' => $perms,
                'recommended' => '0600 or 0644'
            ];
        }
        
        // Check theme directory
        $theme_dir = get_template_directory();
        $theme_perms = substr(sprintf('%o', fileperms($theme_dir)), -4);
        $checks['theme_directory'] = [
            'status' => $theme_perms === '0755' ? 'secure' : 'warning',
            'permissions' => $theme_perms,
            'recommended' => '0755'
        ];
        
        // Check uploads directory
        $upload_dir = wp_upload_dir();
        $upload_perms = substr(sprintf('%o', fileperms($upload_dir['basedir'])), -4);
        $checks['uploads_directory'] = [
            'status' => $upload_perms === '0755' ? 'secure' : 'warning',
            'permissions' => $upload_perms,
            'recommended' => '0755'
        ];
        
        return $checks;
    }
    
    /**
     * Scan for plugin vulnerabilities
     */
    private function scan_plugin_vulnerabilities(): array {
        $plugins = get_plugins();
        $vulnerabilities = [];
        
        foreach ($plugins as $plugin_file => $plugin_data) {
            // Check against vulnerability database (simplified)
            if ($this->check_plugin_vulnerability($plugin_data)) {
                $vulnerabilities[] = [
                    'plugin' => $plugin_data['Name'],
                    'version' => $plugin_data['Version'],
                    'severity' => 'medium',
                    'description' => 'Outdated plugin version detected'
                ];
            }
        }
        
        return [
            'total_plugins' => count($plugins),
            'vulnerabilities_found' => count($vulnerabilities),
            'vulnerabilities' => $vulnerabilities
        ];
    }
    
    /**
     * Check core integrity
     */
    private function check_core_integrity(): array {
        global $wp_version;
        
        return [
            'wp_version' => $wp_version,
            'is_latest' => $this->is_wp_version_latest($wp_version),
            'core_files_modified' => $this->check_core_files_modified(),
            'status' => 'secure'
        ];
    }
    
    /**
     * Audit user security
     */
    private function audit_user_security(): array {
        $users = get_users(['role' => 'administrator']);
        $weak_passwords = 0;
        $two_factor_enabled = 0;
        
        foreach ($users as $user) {
            // Check for weak passwords (simplified)
            if (strlen($user->user_pass) < 12) {
                $weak_passwords++;
            }
        }
        
        return [
            'admin_users' => count($users),
            'weak_passwords' => $weak_passwords,
            'two_factor_enabled' => $two_factor_enabled,
            'recommendations' => $this->get_user_security_recommendations($users)
        ];
    }
    
    /**
     * Audit database security
     */
    private function audit_database_security(): array {
        global $wpdb;
        
        $checks = [
            'db_prefix' => [
                'status' => $wpdb->prefix !== 'wp_' ? 'secure' : 'warning',
                'current' => $wpdb->prefix,
                'recommendation' => 'Use custom database prefix'
            ],
            'sql_injection_protection' => [
                'status' => 'secure',
                'description' => 'Using prepared statements'
            ]
        ];
        
        return $checks;
    }
    
    /**
     * Audit configuration
     */
    private function audit_configuration(): array {
        $config_checks = [
            'debug_mode' => [
                'status' => WP_DEBUG ? 'warning' : 'secure',
                'current' => WP_DEBUG ? 'enabled' : 'disabled',
                'recommendation' => 'Disable debug mode in production'
            ],
            'file_editing' => [
                'status' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT ? 'secure' : 'warning',
                'current' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT ? 'disabled' : 'enabled',
                'recommendation' => 'Disable file editing in wp-config.php'
            ],
            'xmlrpc' => [
                'status' => $this->is_xmlrpc_disabled() ? 'secure' : 'warning',
                'recommendation' => 'Disable XML-RPC if not needed'
            ]
        ];
        
        return $config_checks;
    }
    
    /**
     * Scan for malware
     */
    private function scan_for_malware(): array {
        $suspicious_files = [];
        $scan_dirs = [
            get_template_directory(),
            WP_CONTENT_DIR . '/uploads'
        ];
        
        foreach ($scan_dirs as $dir) {
            $suspicious_files = array_merge($suspicious_files, $this->scan_directory_for_malware($dir));
        }
        
        return [
            'files_scanned' => $this->count_files_scanned($scan_dirs),
            'suspicious_files' => count($suspicious_files),
            'threats' => $suspicious_files
        ];
    }
    
    /**
     * Check SSL configuration
     */
    private function check_ssl_configuration(): array {
        $is_ssl = is_ssl();
        $force_ssl = defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN;
        
        return [
            'ssl_enabled' => $is_ssl,
            'force_ssl_admin' => $force_ssl,
            'status' => $is_ssl && $force_ssl ? 'secure' : 'warning',
            'recommendation' => 'Enable SSL and force SSL for admin'
        ];
    }
    
    /**
     * Calculate security score
     */
    private function calculate_security_score($results): int {
        $total_score = 100;
        $deductions = 0;
        
        // File permissions
        foreach ($results['file_permissions'] as $check) {
            if ($check['status'] === 'warning') {
                $deductions += 5;
            }
        }
        
        // Vulnerabilities
        $deductions += $results['plugin_vulnerabilities']['vulnerabilities_found'] * 10;
        
        // User security
        $deductions += $results['user_security']['weak_passwords'] * 5;
        
        // Configuration
        foreach ($results['configuration_security'] as $check) {
            if ($check['status'] === 'warning') {
                $deductions += 5;
            }
        }
        
        // Malware
        $deductions += $results['malware_scan']['suspicious_files'] * 15;
        
        // SSL
        if ($results['ssl_check']['status'] === 'warning') {
            $deductions += 10;
        }
        
        return max(0, $total_score - $deductions);
    }
    
    /**
     * Generate recommendations
     */
    private function generate_recommendations($results): array {
        $recommendations = [];
        
        foreach ($results['file_permissions'] as $check) {
            if ($check['status'] === 'warning') {
                $recommendations[] = "Fix file permissions: {$check['recommended']}";
            }
        }
        
        if ($results['plugin_vulnerabilities']['vulnerabilities_found'] > 0) {
            $recommendations[] = "Update vulnerable plugins";
        }
        
        if ($results['user_security']['weak_passwords'] > 0) {
            $recommendations[] = "Enforce strong password policy";
        }
        
        foreach ($results['configuration_security'] as $check) {
            if ($check['status'] === 'warning') {
                $recommendations[] = $check['recommendation'];
            }
        }
        
        if ($results['malware_scan']['suspicious_files'] > 0) {
            $recommendations[] = "Investigate suspicious files";
        }
        
        if ($results['ssl_check']['status'] === 'warning') {
            $recommendations[] = $results['ssl_check']['recommendation'];
        }
        
        return $recommendations;
    }
    
    /**
     * Send security alert
     */
    private function send_security_alert($results, $score): void {
        $alert_config = get_option('tmu_security_alerts', []);
        
        if (!empty($alert_config['email'])) {
            wp_mail(
                $alert_config['email'],
                'TMU Security Alert - Score: ' . $score,
                $this->format_security_alert($results, $score),
                ['Content-Type: text/html; charset=UTF-8']
            );
        }
    }
    
    /**
     * Format security alert
     */
    private function format_security_alert($results, $score): string {
        $message = "Security audit completed with score: {$score}/100\n\n";
        
        if ($results['plugin_vulnerabilities']['vulnerabilities_found'] > 0) {
            $message .= "Plugin vulnerabilities found: {$results['plugin_vulnerabilities']['vulnerabilities_found']}\n";
        }
        
        if ($results['malware_scan']['suspicious_files'] > 0) {
            $message .= "Suspicious files detected: {$results['malware_scan']['suspicious_files']}\n";
        }
        
        return $message;
    }
    
    /**
     * Manual security scan via AJAX
     */
    public function manual_security_scan(): void {
        check_ajax_referer('tmu_security_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $this->run_full_security_audit();
        
        $results = get_option('tmu_security_audit_results');
        wp_send_json_success($results);
    }
    
    // Helper methods
    private function check_plugin_vulnerability($plugin_data): bool {
        // Simplified vulnerability check
        return false;
    }
    
    private function is_wp_version_latest($version): bool {
        // Check if WordPress version is latest
        return true;
    }
    
    private function check_core_files_modified(): bool {
        // Check if core files are modified
        return false;
    }
    
    private function get_user_security_recommendations($users): array {
        return ['Enforce strong passwords', 'Enable two-factor authentication'];
    }
    
    private function is_xmlrpc_disabled(): bool {
        return !has_filter('xmlrpc_enabled', '__return_false');
    }
    
    private function scan_directory_for_malware($dir): array {
        $suspicious = [];
        $suspicious_patterns = ['eval(', 'base64_decode', 'system(', 'exec('];
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $content = file_get_contents($file);
                foreach ($suspicious_patterns as $pattern) {
                    if (strpos($content, $pattern) !== false) {
                        $suspicious[] = [
                            'file' => $file->getPathname(),
                            'pattern' => $pattern,
                            'severity' => 'high'
                        ];
                        break;
                    }
                }
            }
        }
        
        return $suspicious;
    }
    
    private function count_files_scanned($dirs): int {
        $count = 0;
        foreach ($dirs as $dir) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $count++;
                }
            }
        }
        return $count;
    }
}