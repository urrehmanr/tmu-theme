<?php
/**
 * TMU Theme Test Case
 *
 * @package TMU\Tests
 * @version 1.0.0
 */

namespace TMU\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case for TMU theme tests
 */
class TestCase extends PHPUnitTestCase {
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Reset WordPress state
        $this->resetWordPressState();
        
        // Clear TMU cache
        $this->clearTMUCache();
    }
    
    /**
     * Clean up test environment
     */
    public function tearDown(): void {
        // Clean up any test data
        $this->cleanupTestData();
        
        parent::tearDown();
    }
    
    /**
     * Reset WordPress state for clean testing
     */
    protected function resetWordPressState(): void {
        // Clear global variables
        global $wp_query, $post;
        $wp_query = null;
        $post = null;
        
        // Reset current user
        wp_set_current_user(0);
    }
    
    /**
     * Clear TMU cache for clean testing
     */
    protected function clearTMUCache(): void {
        global $wpdb;
        
        // Delete all TMU transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tmu_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_tmu_%'");
        
        // Clear object cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    /**
     * Clean up test data
     */
    protected function cleanupTestData(): void {
        // Delete test posts
        $test_posts = get_posts([
            'post_type' => 'any',
            'meta_key' => '_tmu_test_post',
            'meta_value' => '1',
            'numberposts' => -1,
            'post_status' => 'any'
        ]);
        
        foreach ($test_posts as $post) {
            wp_delete_post($post->ID, true);
        }
        
        // Delete test options
        $test_options = $GLOBALS['wpdb']->get_col(
            "SELECT option_name FROM {$GLOBALS['wpdb']->options} WHERE option_name LIKE 'tmu_test_%'"
        );
        
        foreach ($test_options as $option) {
            delete_option($option);
        }
    }
    
    /**
     * Create a test post
     *
     * @param array $args Post arguments
     * @return int Post ID
     */
    protected function createTestPost(array $args = []): int {
        $defaults = [
            'post_title' => 'Test Post',
            'post_content' => 'Test content',
            'post_status' => 'publish',
            'post_type' => 'post',
        ];
        
        $args = wp_parse_args($args, $defaults);
        $post_id = wp_insert_post($args);
        
        // Mark as test post for cleanup
        update_post_meta($post_id, '_tmu_test_post', '1');
        
        return $post_id;
    }
    
    /**
     * Create a test user
     *
     * @param string $role User role
     * @param array $args User arguments
     * @return int User ID
     */
    protected function createTestUser(string $role = 'subscriber', array $args = []): int {
        $defaults = [
            'user_login' => 'testuser_' . wp_generate_password(8, false),
            'user_email' => 'test@example.com',
            'user_pass' => 'password',
            'role' => $role,
        ];
        
        $args = wp_parse_args($args, $defaults);
        return wp_insert_user($args);
    }
    
    /**
     * Assert that a post meta exists
     *
     * @param int $post_id Post ID
     * @param string $meta_key Meta key
     * @param mixed $expected_value Expected value
     */
    protected function assertPostMetaExists(int $post_id, string $meta_key, $expected_value = null): void {
        $meta_value = get_post_meta($post_id, $meta_key, true);
        
        $this->assertNotEmpty($meta_value, "Post meta '{$meta_key}' should exist");
        
        if ($expected_value !== null) {
            $this->assertEquals($expected_value, $meta_value, "Post meta '{$meta_key}' should match expected value");
        }
    }
    
    /**
     * Assert that a TMU option exists
     *
     * @param string $option_name Option name
     * @param mixed $expected_value Expected value
     */
    protected function assertTMUOptionExists(string $option_name, $expected_value = null): void {
        $option_value = get_option($option_name);
        
        $this->assertNotFalse($option_value, "TMU option '{$option_name}' should exist");
        
        if ($expected_value !== null) {
            $this->assertEquals($expected_value, $option_value, "TMU option '{$option_name}' should match expected value");
        }
    }
    
    /**
     * Mock WordPress function calls
     *
     * @param string $function_name Function name
     * @param mixed $return_value Return value
     */
    protected function mockWordPressFunction(string $function_name, $return_value): void {
        if (!function_exists($function_name)) {
            eval("function {$function_name}() { return " . var_export($return_value, true) . "; }");
        }
    }
}