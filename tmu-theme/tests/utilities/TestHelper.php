<?php
/**
 * Test Helper Utility
 * 
 * Provides common helper methods for tests.
 * 
 * @package TMU\Tests\Utilities
 * @since 1.0.0
 */

namespace TMU\Tests\Utilities;

/**
 * TestHelper class
 * 
 * Common helper methods for all tests
 */
class TestHelper {
    
    /**
     * Create a test movie post
     * 
     * @param array $args Movie data
     * @return int Post ID
     */
    public static function create_movie($args = []): int {
        $defaults = [
            'post_title' => 'Test Movie',
            'post_content' => 'Test movie overview',
            'post_type' => 'movie',
            'post_status' => 'publish',
            'meta_input' => [
                'tmdb_id' => 12345,
                'vote_average' => 7.5,
                'runtime' => 120,
                'release_date' => '2023-01-01'
            ]
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $post_id = wp_insert_post($args);
        
        if (is_wp_error($post_id)) {
            throw new \Exception('Failed to create test movie: ' . $post_id->get_error_message());
        }
        
        // Add to custom table if function exists
        if (function_exists('tmu_save_movie_data') && isset($args['movie_data'])) {
            tmu_save_movie_data($post_id, $args['movie_data']);
        }
        
        return $post_id;
    }
    
    /**
     * Create a test TV show post
     * 
     * @param array $args TV show data
     * @return int Post ID
     */
    public static function create_tv_show($args = []): int {
        $defaults = [
            'post_title' => 'Test TV Show',
            'post_content' => 'Test TV show overview',
            'post_type' => 'tv',
            'post_status' => 'publish',
            'meta_input' => [
                'tmdb_id' => 54321,
                'vote_average' => 8.0,
                'first_air_date' => '2023-01-01',
                'number_of_seasons' => 3
            ]
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $post_id = wp_insert_post($args);
        
        if (is_wp_error($post_id)) {
            throw new \Exception('Failed to create test TV show: ' . $post_id->get_error_message());
        }
        
        return $post_id;
    }
    
    /**
     * Create a test person post
     * 
     * @param array $args Person data
     * @return int Post ID
     */
    public static function create_person($args = []): int {
        $defaults = [
            'post_title' => 'Test Person',
            'post_content' => 'Test person biography',
            'post_type' => 'people',
            'post_status' => 'publish',
            'meta_input' => [
                'tmdb_id' => 98765,
                'biography' => 'Test biography',
                'birthday' => '1980-01-01'
            ]
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $post_id = wp_insert_post($args);
        
        if (is_wp_error($post_id)) {
            throw new \Exception('Failed to create test person: ' . $post_id->get_error_message());
        }
        
        return $post_id;
    }
    
    /**
     * Create a test taxonomy term
     * 
     * @param string $name Term name
     * @param string $taxonomy Taxonomy name
     * @return int Term ID
     */
    public static function create_term($name, $taxonomy = 'category'): int {
        $term = wp_insert_term($name, $taxonomy);
        
        if (is_wp_error($term)) {
            throw new \Exception('Failed to create term: ' . $term->get_error_message());
        }
        
        return $term['term_id'];
    }
    
    /**
     * Create a test attachment
     * 
     * @param int $parent_id Parent post ID
     * @param string $filename Filename
     * @return int Attachment ID
     */
    public static function create_attachment($parent_id = 0, $filename = 'test-image.jpg'): int {
        $upload_dir = wp_upload_dir();
        
        // Create a test image file
        $image_content = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=');
        
        $file_path = $upload_dir['path'] . '/' . $filename;
        file_put_contents($file_path, $image_content);
        
        $attachment = [
            'guid' => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => 'image/jpeg',
            'post_title' => 'Test Image',
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => $parent_id
        ];
        
        $attach_id = wp_insert_attachment($attachment, $file_path, $parent_id);
        
        if (is_wp_error($attach_id)) {
            throw new \Exception('Failed to create attachment: ' . $attach_id->get_error_message());
        }
        
        // Generate attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        return $attach_id;
    }
    
    /**
     * Add cast member to movie
     * 
     * @param int $movie_id Movie post ID
     * @param int $person_id Person post ID
     * @param string $character Character name
     */
    public static function add_cast_member($movie_id, $person_id, $character = 'Test Character'): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tmu_cast';
        
        $wpdb->insert(
            $table_name,
            [
                'post_id' => $movie_id,
                'person_id' => $person_id,
                'character_name' => $character,
                'cast_order' => 1
            ],
            ['%d', '%d', '%s', '%d']
        );
    }
    
    /**
     * Create user with specific role
     * 
     * @param string $role User role
     * @return int User ID
     */
    public static function create_user_with_role($role = 'subscriber'): int {
        $username = 'testuser_' . time() . '_' . wp_rand(1000, 9999);
        $email = $username . '@example.com';
        
        $user_id = wp_create_user($username, 'password123', $email);
        
        if (is_wp_error($user_id)) {
            throw new \Exception('Failed to create user: ' . $user_id->get_error_message());
        }
        
        $user = new \WP_User($user_id);
        $user->set_role($role);
        
        return $user_id;
    }
    
    /**
     * Clean up test data
     */
    public static function cleanup(): void {
        global $wpdb;
        
        // Remove test posts
        $test_posts = get_posts([
            'post_type' => ['movie', 'tv', 'people'],
            'meta_query' => [
                [
                    'key' => '_test_post',
                    'value' => '1',
                    'compare' => '='
                ]
            ],
            'numberposts' => -1
        ]);
        
        foreach ($test_posts as $post) {
            wp_delete_post($post->ID, true);
        }
        
        // Remove test terms
        $test_terms = get_terms([
            'taxonomy' => ['genre', 'country', 'language'],
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key' => '_test_term',
                    'value' => '1',
                    'compare' => '='
                ]
            ]
        ]);
        
        foreach ($test_terms as $term) {
            wp_delete_term($term->term_id, $term->taxonomy);
        }
        
        // Clean cache
        wp_cache_flush();
    }
    
    /**
     * Mock TMDB API response
     * 
     * @param int $movie_id TMDB movie ID
     * @param array $response Mock response data
     */
    public static function mock_tmdb_response($movie_id, $response): void {
        add_filter('pre_http_request', function($preempt, $args, $url) use ($movie_id, $response) {
            if (strpos($url, "movie/{$movie_id}") !== false) {
                return [
                    'headers' => [],
                    'body' => wp_json_encode($response),
                    'response' => [
                        'code' => 200,
                        'message' => 'OK'
                    ]
                ];
            }
            return $preempt;
        }, 10, 3);
    }
    
    /**
     * Assert array structure
     * 
     * @param array $expected Expected structure
     * @param array $actual Actual array
     * @param string $message Error message
     */
    public static function assertArrayStructure($expected, $actual, $message = ''): void {
        if (!is_array($actual)) {
            throw new \PHPUnit\Framework\AssertionFailedError($message ?: 'Expected array, got ' . gettype($actual));
        }
        
        foreach ($expected as $key => $value) {
            if (!array_key_exists($key, $actual)) {
                throw new \PHPUnit\Framework\AssertionFailedError($message ?: "Key '{$key}' not found in array");
            }
            
            if (is_array($value)) {
                self::assertArrayStructure($value, $actual[$key], $message);
            }
        }
    }
    
    /**
     * Get page content for testing
     * 
     * @return string Page content
     */
    public static function get_page_content(): string {
        ob_start();
        
        // Capture WordPress output
        if (function_exists('the_content')) {
            the_content();
        }
        
        $content = ob_get_clean();
        
        return $content ?: '';
    }
    
    /**
     * Simulate page visit
     * 
     * @param string $url URL to visit
     */
    public static function go_to($url): void {
        global $wp, $wp_query, $wp_the_query;
        
        $GLOBALS['wp_the_query'] = new \WP_Query();
        $GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];
        
        $wp->main($url);
    }
}