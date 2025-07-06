<?php
/**
 * TMU Theme Factory Helper
 *
 * @package TMU\Tests
 * @version 1.0.0
 */

namespace TMU\Tests;

/**
 * Factory helper for creating test data
 */
class FactoryHelper {
    
    /**
     * Create a test movie post
     *
     * @param array $args Movie arguments
     * @return int Post ID
     */
    public static function createMovie(array $args = []): int {
        $defaults = [
            'post_title' => 'Test Movie ' . wp_generate_password(8, false),
            'post_content' => 'Test movie content',
            'post_status' => 'publish',
            'post_type' => 'movie',
        ];
        
        $args = wp_parse_args($args, $defaults);
        $post_id = wp_insert_post($args);
        
        // Add movie-specific meta data
        $meta_defaults = [
            'tmdb_id' => rand(1000, 999999),
            'release_date' => date('Y-m-d'),
            'runtime' => rand(80, 180),
            'rating' => rand(10, 100) / 10,
            'overview' => 'Test movie overview',
            'poster_url' => 'https://example.com/poster.jpg',
            'backdrop_url' => 'https://example.com/backdrop.jpg',
        ];
        
        foreach ($meta_defaults as $key => $value) {
            if (!isset($args[$key])) {
                update_post_meta($post_id, $key, $value);
            }
        }
        
        // Mark as test post
        update_post_meta($post_id, '_tmu_test_post', '1');
        
        return $post_id;
    }
    
    /**
     * Create a test TV series post
     *
     * @param array $args TV series arguments
     * @return int Post ID
     */
    public static function createTVSeries(array $args = []): int {
        $defaults = [
            'post_title' => 'Test TV Series ' . wp_generate_password(8, false),
            'post_content' => 'Test TV series content',
            'post_status' => 'publish',
            'post_type' => 'tv_series',
        ];
        
        $args = wp_parse_args($args, $defaults);
        $post_id = wp_insert_post($args);
        
        // Add TV series-specific meta data
        $meta_defaults = [
            'tmdb_id' => rand(1000, 999999),
            'first_air_date' => date('Y-m-d'),
            'last_air_date' => date('Y-m-d', strtotime('+1 year')),
            'number_of_seasons' => rand(1, 10),
            'number_of_episodes' => rand(10, 200),
            'rating' => rand(10, 100) / 10,
            'overview' => 'Test TV series overview',
            'poster_url' => 'https://example.com/poster.jpg',
            'backdrop_url' => 'https://example.com/backdrop.jpg',
            'status' => 'Ended',
        ];
        
        foreach ($meta_defaults as $key => $value) {
            if (!isset($args[$key])) {
                update_post_meta($post_id, $key, $value);
            }
        }
        
        // Mark as test post
        update_post_meta($post_id, '_tmu_test_post', '1');
        
        return $post_id;
    }
    
    /**
     * Create a test person post
     *
     * @param array $args Person arguments
     * @return int Post ID
     */
    public static function createPerson(array $args = []): int {
        $defaults = [
            'post_title' => 'Test Person ' . wp_generate_password(8, false),
            'post_content' => 'Test person biography',
            'post_status' => 'publish',
            'post_type' => 'person',
        ];
        
        $args = wp_parse_args($args, $defaults);
        $post_id = wp_insert_post($args);
        
        // Add person-specific meta data
        $meta_defaults = [
            'tmdb_id' => rand(1000, 999999),
            'birthday' => date('Y-m-d', strtotime('-30 years')),
            'birthplace' => 'Test City, Test Country',
            'known_for_department' => 'Acting',
            'profile_url' => 'https://example.com/profile.jpg',
            'popularity' => rand(10, 100) / 10,
        ];
        
        foreach ($meta_defaults as $key => $value) {
            if (!isset($args[$key])) {
                update_post_meta($post_id, $key, $value);
            }
        }
        
        // Mark as test post
        update_post_meta($post_id, '_tmu_test_post', '1');
        
        return $post_id;
    }
    
    /**
     * Create a test episode post
     *
     * @param int $series_id Parent TV series ID
     * @param array $args Episode arguments
     * @return int Post ID
     */
    public static function createEpisode(int $series_id, array $args = []): int {
        $defaults = [
            'post_title' => 'Test Episode ' . wp_generate_password(8, false),
            'post_content' => 'Test episode content',
            'post_status' => 'publish',
            'post_type' => 'episode',
            'post_parent' => $series_id,
        ];
        
        $args = wp_parse_args($args, $defaults);
        $post_id = wp_insert_post($args);
        
        // Add episode-specific meta data
        $meta_defaults = [
            'tmdb_id' => rand(1000, 999999),
            'air_date' => date('Y-m-d'),
            'episode_number' => rand(1, 24),
            'season_number' => rand(1, 10),
            'runtime' => rand(20, 60),
            'rating' => rand(10, 100) / 10,
            'overview' => 'Test episode overview',
            'still_url' => 'https://example.com/still.jpg',
        ];
        
        foreach ($meta_defaults as $key => $value) {
            if (!isset($args[$key])) {
                update_post_meta($post_id, $key, $value);
            }
        }
        
        // Mark as test post
        update_post_meta($post_id, '_tmu_test_post', '1');
        
        return $post_id;
    }
    
    /**
     * Create test taxonomy terms
     *
     * @param string $taxonomy Taxonomy name
     * @param array $terms Term names
     * @return array Term IDs
     */
    public static function createTerms(string $taxonomy, array $terms): array {
        $term_ids = [];
        
        foreach ($terms as $term_name) {
            $term = wp_insert_term($term_name, $taxonomy);
            
            if (!is_wp_error($term)) {
                $term_ids[] = $term['term_id'];
            }
        }
        
        return $term_ids;
    }
    
    /**
     * Create test settings
     *
     * @param array $settings Settings to create
     */
    public static function createSettings(array $settings): void {
        foreach ($settings as $key => $value) {
            update_option("tmu_test_{$key}", $value);
        }
    }
    
    /**
     * Create test TMDB data structure
     *
     * @param string $type Data type (movie, tv, person)
     * @param array $overrides Data overrides
     * @return array TMDB data structure
     */
    public static function createTMDBData(string $type, array $overrides = []): array {
        $defaults = [];
        
        switch ($type) {
            case 'movie':
                $defaults = [
                    'id' => rand(1000, 999999),
                    'title' => 'Test Movie',
                    'overview' => 'Test movie overview',
                    'release_date' => date('Y-m-d'),
                    'runtime' => rand(80, 180),
                    'vote_average' => rand(10, 100) / 10,
                    'vote_count' => rand(100, 10000),
                    'poster_path' => '/test-poster.jpg',
                    'backdrop_path' => '/test-backdrop.jpg',
                    'genres' => [
                        ['id' => 28, 'name' => 'Action'],
                        ['id' => 12, 'name' => 'Adventure']
                    ],
                    'production_companies' => [
                        ['id' => 1, 'name' => 'Test Studio']
                    ],
                ];
                break;
                
            case 'tv':
                $defaults = [
                    'id' => rand(1000, 999999),
                    'name' => 'Test TV Series',
                    'overview' => 'Test TV series overview',
                    'first_air_date' => date('Y-m-d'),
                    'last_air_date' => date('Y-m-d', strtotime('+1 year')),
                    'number_of_seasons' => rand(1, 10),
                    'number_of_episodes' => rand(10, 200),
                    'vote_average' => rand(10, 100) / 10,
                    'vote_count' => rand(100, 10000),
                    'poster_path' => '/test-poster.jpg',
                    'backdrop_path' => '/test-backdrop.jpg',
                    'genres' => [
                        ['id' => 18, 'name' => 'Drama'],
                        ['id' => 9648, 'name' => 'Mystery']
                    ],
                    'networks' => [
                        ['id' => 1, 'name' => 'Test Network']
                    ],
                ];
                break;
                
            case 'person':
                $defaults = [
                    'id' => rand(1000, 999999),
                    'name' => 'Test Person',
                    'biography' => 'Test person biography',
                    'birthday' => date('Y-m-d', strtotime('-30 years')),
                    'place_of_birth' => 'Test City, Test Country',
                    'known_for_department' => 'Acting',
                    'profile_path' => '/test-profile.jpg',
                    'popularity' => rand(10, 100) / 10,
                ];
                break;
        }
        
        return wp_parse_args($overrides, $defaults);
    }
    
    /**
     * Clean up all test data
     */
    public static function cleanupAll(): void {
        global $wpdb;
        
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
        $test_options = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'tmu_test_%'"
        );
        
        foreach ($test_options as $option) {
            delete_option($option);
        }
        
        // Clear test cache
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tmu_test_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_tmu_test_%'");
    }
}