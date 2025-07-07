<?php
/**
 * Security Test
 * 
 * Security validation tests for the TMU theme.
 * 
 * @package TMU\Tests\Security
 * @since 1.0.0
 */

namespace TMU\Tests\Security;

use TMU\Tests\Utilities\DatabaseTestCase;

/**
 * SecurityTest class
 * 
 * Security validation tests
 */
class SecurityTest extends DatabaseTestCase {
    
    /**
     * Test input sanitization
     */
    public function test_input_sanitization(): void {
        $malicious_input = '<script>alert("XSS")</script>';
        
        // Test search input sanitization
        $_GET['s'] = $malicious_input;
        $sanitized = get_search_query();
        
        $this->assertNotContains('<script>', $sanitized, 'Script tags should be removed');
        $this->assertNotContains('alert', $sanitized, 'JavaScript should be removed');
    }
    
    /**
     * Test SQL injection prevention
     */
    public function test_sql_injection_prevention(): void {
        global $wpdb;
        
        $malicious_query = "'; DROP TABLE {$wpdb->posts}; --";
        
        // Test custom query functions
        if (class_exists('TMU\\Search\\SearchEngine')) {
            $search_engine = new \TMU\Search\SearchEngine();
            
            // This should not cause any database errors
            $results = $search_engine->search($malicious_query);
            
            // Verify table still exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->posts}'");
            $this->assertEquals($wpdb->posts, $table_exists, 'Posts table should still exist');
        } else {
            $this->markTestSkipped('SearchEngine class not available');
        }
    }
    
    /**
     * Test nonce verification
     */
    public function test_nonce_verification(): void {
        // Test AJAX endpoints require valid nonces
        $_POST['action'] = 'tmu_search';
        $_POST['query'] = 'test';
        $_POST['nonce'] = 'invalid_nonce';
        
        ob_start();
        try {
            do_action('wp_ajax_nopriv_tmu_search');
            $output = ob_get_clean();
            
            // Should either die with error or return error response
            $this->assertTrue(
                strpos($output, 'Security check failed') !== false || 
                strpos($output, 'error') !== false,
                'Invalid nonce should be rejected'
            );
        } catch (\WPDieException $e) {
            ob_end_clean();
            $this->assertStringContains('Security check failed', $e->getMessage());
        }
    }
    
    /**
     * Test capability checks
     */
    public function test_capability_checks(): void {
        // Create user without admin privileges
        $user_id = $this->create_user_with_role('subscriber');
        wp_set_current_user($user_id);
        
        $this->assertFalse(current_user_can('manage_options'), 'Subscriber should not have admin capabilities');
        
        // Test admin-only functionality
        try {
            // This should fail for non-admin users
            if (has_action('admin_action_tmu_settings')) {
                do_action('admin_action_tmu_settings');
                $this->fail('Admin action should not be accessible to subscribers');
            }
        } catch (\WPDieException $e) {
            $this->assertStringContains('permission', strtolower($e->getMessage()));
        }
        
        // Reset to admin user
        wp_set_current_user(1);
    }
    
    /**
     * Test file upload security
     */
    public function test_file_upload_security(): void {
        // Test that only allowed file types can be uploaded
        $allowed_types = get_allowed_mime_types();
        
        $this->assertArrayHasKey('jpg|jpeg|jpe', $allowed_types, 'JPEG files should be allowed');
        $this->assertArrayHasKey('png', $allowed_types, 'PNG files should be allowed');
        $this->assertArrayNotHasKey('php', $allowed_types, 'PHP files should not be allowed');
        $this->assertArrayNotHasKey('exe', $allowed_types, 'Executable files should not be allowed');
        
        // Test file validation function if available
        if (function_exists('tmu_validate_file_upload')) {
            // Test malicious file
            $malicious_file = [
                'name' => 'malicious.php',
                'type' => 'application/x-php',
                'tmp_name' => '/tmp/test',
                'size' => 1000
            ];
            
            $result = tmu_validate_file_upload($malicious_file);
            $this->assertFalse($result['valid'], 'Malicious files should be rejected');
        }
    }
    
    /**
     * Test data escape in templates
     */
    public function test_data_escape_in_templates(): void {
        $movie_id = $this->create_movie([
            'title' => '<script>alert("XSS")</script>Movie Title'
        ]);
        
        ob_start();
        $this->go_to(get_permalink($movie_id));
        $content = ob_get_clean();
        
        // Content should be escaped
        $this->assertNotContains('<script>', $content, 'Script tags should be escaped');
        $this->assertStringContains('&lt;script&gt;', $content, 'Script tags should be HTML entities');
    }
    
    /**
     * Test CSRF protection
     */
    public function test_csrf_protection(): void {
        // Test that forms include CSRF tokens
        if (function_exists('tmu_get_csrf_token')) {
            $token = tmu_get_csrf_token();
            $this->assertNotEmpty($token, 'CSRF token should be generated');
            $this->assertGreaterThan(10, strlen($token), 'CSRF token should be sufficiently long');
        }
        
        // Test CSRF validation
        if (function_exists('tmu_verify_csrf_token')) {
            $this->assertFalse(tmu_verify_csrf_token('invalid_token'), 'Invalid CSRF token should be rejected');
        }
    }
    
    /**
     * Test XSS protection
     */
    public function test_xss_protection(): void {
        $xss_payloads = [
            '<script>alert("XSS")</script>',
            'javascript:alert("XSS")',
            '<img src="x" onerror="alert(\'XSS\')">',
            '<svg onload="alert(\'XSS\')">',
            '"><script>alert("XSS")</script>'
        ];
        
        foreach ($xss_payloads as $payload) {
            $sanitized = wp_kses_post($payload);
            $this->assertNotContains('alert', $sanitized, "XSS payload should be sanitized: {$payload}");
            $this->assertNotContains('javascript:', $sanitized, "JavaScript protocol should be removed: {$payload}");
        }
    }
    
    /**
     * Test session security
     */
    public function test_session_security(): void {
        // Test secure session configuration
        if (function_exists('tmu_configure_session_security')) {
            tmu_configure_session_security();
            
            $this->assertTrue(ini_get('session.cookie_httponly'), 'Session cookies should be HTTP only');
            $this->assertTrue(ini_get('session.cookie_secure') || !is_ssl(), 'Session cookies should be secure on HTTPS');
        }
    }
    
    /**
     * Test password security
     */
    public function test_password_security(): void {
        $weak_passwords = ['password', '123456', 'admin', 'test'];
        
        foreach ($weak_passwords as $password) {
            if (function_exists('tmu_validate_password_strength')) {
                $result = tmu_validate_password_strength($password);
                $this->assertFalse($result['strong'], "Weak password should be rejected: {$password}");
            }
        }
        
        // Test strong password
        $strong_password = 'StrongP@ssw0rd123!';
        if (function_exists('tmu_validate_password_strength')) {
            $result = tmu_validate_password_strength($strong_password);
            $this->assertTrue($result['strong'], 'Strong password should be accepted');
        }
    }
    
    /**
     * Test HTTP headers security
     */
    public function test_http_headers_security(): void {
        // Start output buffering to capture headers
        ob_start();
        
        // Trigger header output
        if (function_exists('tmu_set_security_headers')) {
            tmu_set_security_headers();
        }
        
        $headers = headers_list();
        ob_end_clean();
        
        $required_headers = [
            'X-Content-Type-Options',
            'X-Frame-Options',
            'X-XSS-Protection'
        ];
        
        foreach ($required_headers as $header) {
            $header_found = false;
            foreach ($headers as $sent_header) {
                if (strpos($sent_header, $header) === 0) {
                    $header_found = true;
                    break;
                }
            }
            $this->assertTrue($header_found, "Security header '{$header}' should be set");
        }
    }
    
    /**
     * Test API security
     */
    public function test_api_security(): void {
        // Test that API endpoints require authentication
        $response = wp_remote_get(rest_url('tmu/v1/movies'));
        
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            
            // Should either require auth or be public with rate limiting
            $this->assertTrue(
                in_array($code, [200, 401, 403, 429]),
                'API should have proper authentication or rate limiting'
            );
        }
    }
    
    /**
     * Test database security
     */
    public function test_database_security(): void {
        global $wpdb;
        
        // Test prepared statements are used
        $test_id = 1;
        $query = $wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE ID = %d", $test_id);
        
        $this->assertStringContains('SELECT * FROM', $query, 'Prepared statement should be generated');
        $this->assertNotContains('%d', $query, 'Placeholder should be replaced');
        
        // Test that direct queries are escaped
        $unsafe_value = "'; DROP TABLE posts; --";
        $escaped = esc_sql($unsafe_value);
        
        $this->assertNotEquals($unsafe_value, $escaped, 'SQL should be escaped');
        $this->assertNotContains('DROP TABLE', $escaped, 'Dangerous SQL should be escaped');
    }
    
    /**
     * Test file permissions
     */
    public function test_file_permissions(): void {
        $theme_dir = get_template_directory();
        
        // Test that sensitive files are not writable by web server
        $sensitive_files = [
            $theme_dir . '/functions.php',
            $theme_dir . '/style.css'
        ];
        
        foreach ($sensitive_files as $file) {
            if (file_exists($file)) {
                $perms = fileperms($file);
                $this->assertNotEquals(0777, $perms & 0777, "File should not be world-writable: {$file}");
            }
        }
    }
    
    /**
     * Test configuration security
     */
    public function test_configuration_security(): void {
        // Test that debug mode is disabled in production
        if (defined('WP_ENV') && WP_ENV === 'production') {
            $this->assertFalse(WP_DEBUG, 'Debug mode should be disabled in production');
            $this->assertFalse(WP_DEBUG_DISPLAY, 'Debug display should be disabled in production');
        }
        
        // Test database credentials are not exposed
        $this->assertFalse(defined('DB_PASSWORD') && empty(DB_PASSWORD), 'Database password should be set');
    }
    
    /**
     * Test rate limiting
     */
    public function test_rate_limiting(): void {
        // Test API rate limiting if implemented
        if (function_exists('tmu_check_rate_limit')) {
            $ip = '192.168.1.1';
            
            // Should allow initial requests
            $this->assertTrue(tmu_check_rate_limit($ip), 'Initial request should be allowed');
            
            // Simulate multiple requests
            for ($i = 0; $i < 100; $i++) {
                tmu_check_rate_limit($ip);
            }
            
            // Should block after too many requests
            $this->assertFalse(tmu_check_rate_limit($ip), 'Should block after rate limit exceeded');
        }
    }
    
    /**
     * Test content validation
     */
    public function test_content_validation(): void {
        $malicious_content = '<script>alert("XSS")</script><iframe src="evil.com"></iframe>';
        
        // Test content filtering
        $filtered = wp_kses_post($malicious_content);
        $this->assertNotContains('<script>', $filtered, 'Script tags should be removed');
        $this->assertNotContains('<iframe>', $filtered, 'Iframe tags should be removed');
        
        // Test custom content validation if available
        if (function_exists('tmu_validate_content')) {
            $result = tmu_validate_content($malicious_content);
            $this->assertFalse($result['safe'], 'Malicious content should be flagged as unsafe');
        }
    }
    
    /**
     * Helper method to create user with role
     */
    private function create_user_with_role($role = 'subscriber'): int {
        $username = 'testuser_' . time() . '_' . wp_rand(1000, 9999);
        $email = $username . '@example.com';
        
        $user_id = wp_create_user($username, 'password123', $email);
        
        if (is_wp_error($user_id)) {
            $this->fail('Failed to create test user: ' . $user_id->get_error_message());
        }
        
        $user = new \WP_User($user_id);
        $user->set_role($role);
        
        // Mark as test user for cleanup
        update_user_meta($user_id, '_test_user', '1');
        
        return $user_id;
    }
}