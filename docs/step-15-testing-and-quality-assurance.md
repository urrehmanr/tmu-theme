# Step 15: Testing and Quality Assurance - Complete Implementation

## Overview
This final step implements comprehensive testing and quality assurance procedures to ensure the TMU theme meets production standards. The system includes automated testing, performance optimization, security validation, and deployment procedures.

## 1. Testing Framework Setup

### 1.1 PHPUnit Configuration
```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
    bootstrap="tests/bootstrap.php"
    backupGlobals="false"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
    processIsolation="false"
    stopOnFailure="false">
    
    <testsuites>
        <testsuite name="TMU Theme Test Suite">
            <directory>./tests/</directory>
        </testsuite>
    </testsuites>
    
    <filter>
        <whitelist>
            <directory suffix=".php">./src/</directory>
            <exclude>
                <directory>./vendor/</directory>
                <directory>./tests/</directory>
            </exclude>
        </whitelist>
    </filter>
    
    <logging>
        <log type="coverage-html" target="./coverage"/>
        <log type="coverage-text" target="php://stdout" showUncoveredFiles="true"/>
    </logging>
</phpunit>
```

### 1.2 Test Bootstrap
```php
// tests/bootstrap.php
<?php
/**
 * Bootstrap file for PHPUnit tests
 */

// Set up WordPress test environment
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_theme() {
    switch_theme('tmu-theme');
    
    // Load theme files
    require_once get_template_directory() . '/functions.php';
    
    // Initialize theme components
    if (class_exists('TMU\ThemeInitializer')) {
        $theme = new TMU\ThemeInitializer();
        $theme->init();
    }
}

tests_add_filter('muplugins_loaded', '_manually_load_theme');

require $_tests_dir . '/includes/bootstrap.php';

// Additional test utilities
require_once __DIR__ . '/utilities/TestHelper.php';
require_once __DIR__ . '/utilities/TMDBMock.php';
require_once __DIR__ . '/utilities/DatabaseTestCase.php';
```

## 2. Unit Tests

### 2.1 Post Type Tests
```php
// tests/PostTypes/MoviePostTypeTest.php
<?php
namespace TMU\Tests\PostTypes;

use TMU\PostTypes\MoviePostType;
use TMU\Tests\Utilities\DatabaseTestCase;

class MoviePostTypeTest extends DatabaseTestCase {
    private $movie_post_type;
    
    public function setUp(): void {
        parent::setUp();
        $this->movie_post_type = new MoviePostType();
        $this->movie_post_type->register();
    }
    
    public function test_movie_post_type_is_registered(): void {
        $this->assertTrue(post_type_exists('movie'));
    }
    
    public function test_movie_post_type_supports(): void {
        $post_type = get_post_type_object('movie');
        
        $this->assertContains('title', $post_type->supports);
        $this->assertContains('editor', $post_type->supports);
        $this->assertContains('thumbnail', $post_type->supports);
        $this->assertContains('excerpt', $post_type->supports);
    }
    
    public function test_movie_post_type_capabilities(): void {
        $post_type = get_post_type_object('movie');
        
        $this->assertEquals('edit_movies', $post_type->cap->edit_posts);
        $this->assertEquals('edit_movie', $post_type->cap->edit_post);
        $this->assertEquals('delete_movies', $post_type->cap->delete_posts);
        $this->assertEquals('publish_movies', $post_type->cap->publish_posts);
    }
    
    public function test_movie_creation(): void {
        $movie_id = $this->create_movie([
            'title' => 'Test Movie',
            'overview' => 'A test movie for unit testing',
            'release_date' => '2023-01-01'
        ]);
        
        $this->assertGreaterThan(0, $movie_id);
        $this->assertEquals('movie', get_post_type($movie_id));
        $this->assertEquals('Test Movie', get_the_title($movie_id));
    }
    
    public function test_movie_custom_fields(): void {
        $movie_id = $this->create_movie([
            'title' => 'Test Movie with Fields',
            'tmdb_id' => 12345,
            'vote_average' => 8.5,
            'runtime' => 120
        ]);
        
        $movie_data = tmu_get_movie_data($movie_id);
        
        $this->assertEquals(12345, $movie_data['tmdb_id']);
        $this->assertEquals(8.5, $movie_data['vote_average']);
        $this->assertEquals(120, $movie_data['runtime']);
    }
    
    public function test_movie_taxonomies(): void {
        $movie_id = $this->create_movie(['title' => 'Genre Test Movie']);
        
        // Test genre assignment
        $genre_id = $this->create_term('Action', 'genre');
        wp_set_post_terms($movie_id, [$genre_id], 'genre');
        
        $genres = wp_get_post_terms($movie_id, 'genre');
        $this->assertCount(1, $genres);
        $this->assertEquals('Action', $genres[0]->name);
    }
}
```

### 2.2 TMDB Integration Tests
```php
// tests/TMDB/TMDBClientTest.php
<?php
namespace TMU\Tests\TMDB;

use TMU\TMDB\TMDBClient;
use TMU\Tests\Utilities\TMDBMock;
use PHPUnit\Framework\TestCase;

class TMDBClientTest extends TestCase {
    private $tmdb_client;
    private $tmdb_mock;
    
    public function setUp(): void {
        parent::setUp();
        $this->tmdb_mock = new TMDBMock();
        $this->tmdb_client = new TMDBClient();
    }
    
    public function test_get_movie_details(): void {
        $this->tmdb_mock->mock_movie_response(550, [
            'id' => 550,
            'title' => 'Fight Club',
            'overview' => 'A ticking-time-bomb insomniac...',
            'release_date' => '1999-10-15',
            'vote_average' => 8.8
        ]);
        
        $result = $this->tmdb_client->get_movie_details(550);
        
        $this->assertEquals(550, $result['id']);
        $this->assertEquals('Fight Club', $result['title']);
        $this->assertEquals(8.8, $result['vote_average']);
    }
    
    public function test_api_error_handling(): void {
        $this->tmdb_mock->mock_error_response(404, 'The resource you requested could not be found.');
        
        $this->expectException(\TMU\TMDB\TMDBException::class);
        $this->tmdb_client->get_movie_details(999999);
    }
    
    public function test_caching_mechanism(): void {
        $this->tmdb_mock->mock_movie_response(550, ['id' => 550, 'title' => 'Fight Club']);
        
        // First call should hit the API
        $result1 = $this->tmdb_client->get_movie_details(550);
        
        // Second call should use cache
        $result2 = $this->tmdb_client->get_movie_details(550);
        
        $this->assertEquals($result1, $result2);
        $this->assertEquals(1, $this->tmdb_mock->get_call_count());
    }
    
    public function test_rate_limiting(): void {
        // Test that client respects TMDB rate limits
        $start_time = microtime(true);
        
        for ($i = 0; $i < 5; $i++) {
            $this->tmdb_mock->mock_movie_response($i, ['id' => $i]);
            $this->tmdb_client->get_movie_details($i);
        }
        
        $execution_time = microtime(true) - $start_time;
        
        // Should take at least 4 seconds due to rate limiting (1 request per second)
        $this->assertGreaterThan(4, $execution_time);
    }
}
```

### 2.3 Search Engine Tests
```php
// tests/Search/SearchEngineTest.php
<?php
namespace TMU\Tests\Search;

use TMU\Search\SearchEngine;
use TMU\Tests\Utilities\DatabaseTestCase;

class SearchEngineTest extends DatabaseTestCase {
    private $search_engine;
    
    public function setUp(): void {
        parent::setUp();
        $this->search_engine = new SearchEngine();
        $this->create_test_content();
    }
    
    public function test_basic_search(): void {
        $results = $this->search_engine->search('Action Movie');
        
        $this->assertGreaterThan(0, $results->get_total());
        $this->assertContains('action-movie-1', array_column($results->get_results(), 'post_name'));
    }
    
    public function test_filtered_search(): void {
        $filters = ['post_type' => ['movie']];
        $results = $this->search_engine->search('Test', $filters);
        
        $post_types = array_unique(array_map('get_post_type', $results->get_results()));
        $this->assertEquals(['movie'], $post_types);
    }
    
    public function test_faceted_search(): void {
        $results = $this->search_engine->search('', [], ['include_facets' => true]);
        $aggregations = $results->get_aggregations();
        
        $this->assertArrayHasKey('post_types', $aggregations);
        $this->assertArrayHasKey('genres', $aggregations);
        $this->assertArrayHasKey('years', $aggregations);
    }
    
    public function test_search_relevance_scoring(): void {
        $results = $this->search_engine->search('Fight Club');
        $results_array = $results->get_results();
        
        // Results should be ordered by relevance
        $first_result = $results_array[0];
        $this->assertContains('fight', strtolower($first_result->post_title));
    }
    
    public function test_empty_search_query(): void {
        $results = $this->search_engine->search('');
        
        // Should return recent posts when no query provided
        $this->assertGreaterThan(0, $results->get_total());
    }
    
    private function create_test_content(): void {
        // Create test movies
        $this->create_movie([
            'title' => 'Action Movie 1',
            'overview' => 'An exciting action-packed adventure',
            'post_name' => 'action-movie-1'
        ]);
        
        $this->create_movie([
            'title' => 'Fight Club',
            'overview' => 'A cult classic movie',
            'post_name' => 'fight-club'
        ]);
        
        // Create test TV shows
        $this->create_tv_show([
            'title' => 'Drama Series',
            'overview' => 'A compelling drama series'
        ]);
        
        // Create test people
        $this->create_person([
            'title' => 'Test Actor',
            'biography' => 'A talented actor'
        ]);
    }
}
```

## 3. Integration Tests

### 3.1 Theme Integration Test
```php
// tests/Integration/ThemeIntegrationTest.php
<?php
namespace TMU\Tests\Integration;

use TMU\Tests\Utilities\DatabaseTestCase;

class ThemeIntegrationTest extends DatabaseTestCase {
    
    public function test_theme_activation(): void {
        // Theme should activate without errors
        $this->assertTrue(wp_get_theme()->exists());
        $this->assertEquals('TMU Theme', wp_get_theme()->get('Name'));
    }
    
    public function test_required_post_types_registered(): void {
        $required_post_types = ['movie', 'tv', 'people', 'video', 'episode', 'season'];
        
        foreach ($required_post_types as $post_type) {
            $this->assertTrue(post_type_exists($post_type), "Post type '{$post_type}' should be registered");
        }
        
        // Drama post type should be registered if enabled
        if (get_option('tmu_dramas') === 'on') {
            $this->assertTrue(post_type_exists('drama'));
            $this->assertTrue(post_type_exists('drama-episode'));
        }
    }
    
    public function test_required_taxonomies_registered(): void {
        $required_taxonomies = ['genre', 'country', 'language', 'by-year'];
        
        foreach ($required_taxonomies as $taxonomy) {
            $this->assertTrue(taxonomy_exists($taxonomy), "Taxonomy '{$taxonomy}' should be registered");
        }
    }
    
    public function test_custom_tables_created(): void {
        global $wpdb;
        
        $required_tables = [
            'tmu_movies',
            'tmu_tv_series',
            'tmu_people',
            'tmu_videos'
        ];
        
        foreach ($required_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $result = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
            $this->assertEquals($table_name, $result, "Table '{$table}' should exist");
        }
    }
    
    public function test_menu_locations_registered(): void {
        $menu_locations = get_registered_nav_menus();
        
        $this->assertArrayHasKey('primary', $menu_locations);
        $this->assertArrayHasKey('footer', $menu_locations);
    }
    
    public function test_image_sizes_registered(): void {
        global $_wp_additional_image_sizes;
        
        $required_sizes = ['movie-poster', 'tv-poster', 'person-photo'];
        
        foreach ($required_sizes as $size) {
            $this->assertArrayHasKey($size, $_wp_additional_image_sizes, "Image size '{$size}' should be registered");
        }
    }
    
    public function test_widget_areas_registered(): void {
        global $wp_registered_sidebars;
        
        $required_sidebars = ['sidebar-main', 'footer-1', 'footer-2', 'footer-3'];
        
        foreach ($required_sidebars as $sidebar) {
            $this->assertArrayHasKey($sidebar, $wp_registered_sidebars, "Sidebar '{$sidebar}' should be registered");
        }
    }
}
```

### 3.2 Database Migration Test
```php
// tests/Integration/DatabaseMigrationTest.php
<?php
namespace TMU\Tests\Integration;

use TMU\Database\MigrationManager;
use TMU\Tests\Utilities\DatabaseTestCase;

class DatabaseMigrationTest extends DatabaseTestCase {
    private $migration_manager;
    
    public function setUp(): void {
        parent::setUp();
        $this->migration_manager = new MigrationManager();
    }
    
    public function test_fresh_installation(): void {
        // Test migration from scratch
        $this->migration_manager->run_migrations();
        
        // Verify all tables created
        $this->assert_tables_exist();
        
        // Verify initial data seeded
        $this->assert_initial_data_exists();
    }
    
    public function test_plugin_to_theme_migration(): void {
        // Simulate existing plugin data
        $this->create_plugin_test_data();
        
        // Run migration
        $result = $this->migration_manager->migrate_from_plugin();
        
        $this->assertTrue($result);
        
        // Verify data integrity
        $this->assert_plugin_data_migrated();
    }
    
    public function test_version_upgrade(): void {
        // Simulate older theme version
        update_option('tmu_theme_version', '1.0.0');
        
        // Run upgrade
        $this->migration_manager->upgrade_database();
        
        // Verify version updated
        $this->assertEquals('2.0.0', get_option('tmu_theme_version'));
    }
    
    public function test_data_backup_and_restore(): void {
        // Create test data
        $movie_id = $this->create_movie(['title' => 'Backup Test Movie']);
        
        // Create backup
        $backup_file = $this->migration_manager->create_backup();
        $this->assertFileExists($backup_file);
        
        // Delete data
        wp_delete_post($movie_id, true);
        $this->assertNull(get_post($movie_id));
        
        // Restore backup
        $this->migration_manager->restore_backup($backup_file);
        
        // Verify data restored
        $restored_post = get_post($movie_id);
        $this->assertNotNull($restored_post);
        $this->assertEquals('Backup Test Movie', $restored_post->post_title);
    }
    
    private function create_plugin_test_data(): void {
        // Simulate plugin data structure
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix . 'tmu_movies', [
            'post_id' => $this->create_movie(['title' => 'Plugin Movie'])->ID,
            'tmdb_id' => 12345,
            'title' => 'Plugin Movie',
            'overview' => 'A movie from plugin data'
        ]);
    }
    
    private function assert_plugin_data_migrated(): void {
        $movies = get_posts(['post_type' => 'movie', 'title' => 'Plugin Movie']);
        $this->assertCount(1, $movies);
        
        $movie_data = tmu_get_movie_data($movies[0]->ID);
        $this->assertEquals(12345, $movie_data['tmdb_id']);
    }
}
```

## 4. Performance Tests

### 4.1 Page Load Performance Test
```php
// tests/Performance/PageLoadTest.php
<?php
namespace TMU\Tests\Performance;

use TMU\Tests\Utilities\DatabaseTestCase;

class PageLoadTest extends DatabaseTestCase {
    
    public function test_homepage_load_time(): void {
        $start_time = microtime(true);
        
        $this->go_to(home_url('/'));
        $content = $this->get_page_content();
        
        $load_time = microtime(true) - $start_time;
        
        $this->assertLessThan(2.0, $load_time, 'Homepage should load in under 2 seconds');
        $this->assertNotEmpty($content);
    }
    
    public function test_movie_page_performance(): void {
        $movie_id = $this->create_movie([
            'title' => 'Performance Test Movie',
            'overview' => 'A movie for performance testing'
        ]);
        
        $start_time = microtime(true);
        
        $this->go_to(get_permalink($movie_id));
        $content = $this->get_page_content();
        
        $load_time = microtime(true) - $start_time;
        
        $this->assertLessThan(1.5, $load_time, 'Movie page should load in under 1.5 seconds');
        $this->assertContains('Performance Test Movie', $content);
    }
    
    public function test_search_performance(): void {
        // Create test content
        for ($i = 0; $i < 100; $i++) {
            $this->create_movie(['title' => "Test Movie {$i}"]);
        }
        
        $start_time = microtime(true);
        
        // Perform search
        $search_engine = new \TMU\Search\SearchEngine();
        $results = $search_engine->search('Test Movie');
        
        $search_time = microtime(true) - $start_time;
        
        $this->assertLessThan(0.5, $search_time, 'Search should complete in under 0.5 seconds');
        $this->assertGreaterThan(0, $results->get_total());
    }
    
    public function test_database_query_count(): void {
        global $wpdb;
        
        $query_count_before = $wpdb->num_queries;
        
        // Load movie archive page
        $this->go_to(get_post_type_archive_link('movie'));
        $this->get_page_content();
        
        $query_count_after = $wpdb->num_queries;
        $queries_executed = $query_count_after - $query_count_before;
        
        $this->assertLessThan(20, $queries_executed, 'Archive page should execute fewer than 20 database queries');
    }
    
    public function test_memory_usage(): void {
        $memory_before = memory_get_usage(true);
        
        // Load complex page with multiple components
        $movie_id = $this->create_movie_with_full_data();
        $this->go_to(get_permalink($movie_id));
        $this->get_page_content();
        
        $memory_after = memory_get_usage(true);
        $memory_used = $memory_after - $memory_before;
        
        // Should use less than 16MB additional memory
        $this->assertLessThan(16 * 1024 * 1024, $memory_used, 'Page should use less than 16MB memory');
    }
    
    private function create_movie_with_full_data(): int {
        $movie_id = $this->create_movie([
            'title' => 'Full Data Movie',
            'overview' => 'A movie with complete metadata for testing',
            'tmdb_id' => 12345,
            'vote_average' => 8.5,
            'runtime' => 120
        ]);
        
        // Add multiple images
        for ($i = 0; $i < 5; $i++) {
            $this->create_attachment($movie_id, "poster-{$i}.jpg");
        }
        
        // Add cast and crew
        for ($i = 0; $i < 10; $i++) {
            $person_id = $this->create_person(['title' => "Actor {$i}"]);
            $this->add_cast_member($movie_id, $person_id, "Character {$i}");
        }
        
        return $movie_id;
    }
}
```

## 5. Security Tests

### 5.1 Security Validation Test
```php
// tests/Security/SecurityTest.php
<?php
namespace TMU\Tests\Security;

use TMU\Tests\Utilities\DatabaseTestCase;

class SecurityTest extends DatabaseTestCase {
    
    public function test_input_sanitization(): void {
        $malicious_input = '<script>alert("XSS")</script>';
        
        // Test search input sanitization
        $_GET['s'] = $malicious_input;
        $sanitized = get_search_query();
        
        $this->assertNotContains('<script>', $sanitized);
        $this->assertNotContains('alert', $sanitized);
    }
    
    public function test_sql_injection_prevention(): void {
        global $wpdb;
        
        $malicious_query = "'; DROP TABLE {$wpdb->posts}; --";
        
        // Test custom query functions
        $search_engine = new \TMU\Search\SearchEngine();
        
        // This should not cause any database errors
        $results = $search_engine->search($malicious_query);
        
        // Verify table still exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->posts}'");
        $this->assertEquals($wpdb->posts, $table_exists);
    }
    
    public function test_nonce_verification(): void {
        // Test AJAX endpoints require valid nonces
        $_POST['action'] = 'tmu_search';
        $_POST['query'] = 'test';
        $_POST['nonce'] = 'invalid_nonce';
        
        ob_start();
        try {
            do_action('wp_ajax_nopriv_tmu_search');
        } catch (WPDieException $e) {
            $this->assertContains('Security check failed', $e->getMessage());
        }
        ob_end_clean();
    }
    
    public function test_capability_checks(): void {
        // Create user without admin privileges
        $user_id = $this->create_user_with_role('subscriber');
        wp_set_current_user($user_id);
        
        // Test admin-only functionality
        $this->assertFalse(current_user_can('manage_options'));
        
        // Admin pages should not be accessible
        $this->expectException(WPDieException::class);
        do_action('admin_page_tmu_settings');
    }
    
    public function test_file_upload_security(): void {
        // Test that only allowed file types can be uploaded
        $allowed_types = get_allowed_mime_types();
        
        $this->assertArrayHasKey('jpg|jpeg|jpe', $allowed_types);
        $this->assertArrayHasKey('png', $allowed_types);
        $this->assertArrayNotHasKey('php', $allowed_types);
        $this->assertArrayNotHasKey('exe', $allowed_types);
    }
    
    public function test_data_escape_in_templates(): void {
        $movie_id = $this->create_movie([
            'title' => '<script>alert("XSS")</script>Movie Title'
        ]);
        
        ob_start();
        $this->go_to(get_permalink($movie_id));
        $content = ob_get_clean();
        
        // Content should be escaped
        $this->assertNotContains('<script>', $content);
        $this->assertContains('&lt;script&gt;', $content);
    }
    
    private function create_user_with_role($role): int {
        return wp_create_user('testuser', 'testpass', 'test@example.com');
    }
}
```

## 6. Accessibility Tests

### 6.1 WCAG Compliance Test
```php
// tests/Accessibility/WCAGComplianceTest.php
<?php
namespace TMU\Tests\Accessibility;

use TMU\Tests\Utilities\DatabaseTestCase;

class WCAGComplianceTest extends DatabaseTestCase {
    
    public function test_semantic_html_structure(): void {
        $movie_id = $this->create_movie(['title' => 'Accessibility Test Movie']);
        
        $this->go_to(get_permalink($movie_id));
        $content = $this->get_page_content();
        
        // Check for proper heading hierarchy
        $this->assertContains('<h1', $content);
        $this->assertRegExp('/<h[1-6][^>]*>/', $content);
        
        // Check for main landmark
        $this->assertContains('<main', $content);
        
        // Check for navigation landmark
        $this->assertContains('<nav', $content);
    }
    
    public function test_image_alt_attributes(): void {
        $movie_id = $this->create_movie(['title' => 'Alt Text Test']);
        $this->create_attachment($movie_id, 'poster.jpg');
        
        $this->go_to(get_permalink($movie_id));
        $content = $this->get_page_content();
        
        // All images should have alt attributes
        preg_match_all('/<img[^>]+>/', $content, $images);
        
        foreach ($images[0] as $img_tag) {
            $this->assertContains('alt=', $img_tag, 'Image missing alt attribute: ' . $img_tag);
        }
    }
    
    public function test_form_labels(): void {
        $this->go_to(home_url('/search'));
        $content = $this->get_page_content();
        
        // All form inputs should have associated labels
        preg_match_all('/<input[^>]+>/', $content, $inputs);
        
        foreach ($inputs[0] as $input_tag) {
            if (strpos($input_tag, 'type="hidden"') === false) {
                // Input should have label, aria-label, or aria-labelledby
                $has_label = preg_match('/(?:aria-label|aria-labelledby|id=")/', $input_tag);
                $this->assertTrue($has_label, 'Input missing label: ' . $input_tag);
            }
        }
    }
    
    public function test_color_contrast(): void {
        // This would typically integrate with automated tools
        // For now, we test that CSS variables are defined
        $this->go_to(home_url('/'));
        $content = $this->get_page_content();
        
        // Check that contrast-related CSS variables are present
        $this->assertContains('--text-color', $content);
        $this->assertContains('--background-color', $content);
        $this->assertContains('--primary-color', $content);
    }
    
    public function test_keyboard_navigation(): void {
        $this->go_to(home_url('/'));
        $content = $this->get_page_content();
        
        // Check for skip links
        $this->assertContains('skip-to-content', $content);
        
        // Interactive elements should be focusable
        preg_match_all('/<(?:a|button|input|select|textarea)[^>]*>/', $content, $interactive);
        
        foreach ($interactive[0] as $element) {
            // Should not have tabindex="-1" unless it's intentional
            if (strpos($element, 'tabindex="-1"') !== false) {
                $this->assertContains('aria-hidden="true"', $element, 'Focusable element should not have tabindex="-1": ' . $element);
            }
        }
    }
    
    public function test_aria_attributes(): void {
        $this->go_to(home_url('/'));
        $content = $this->get_page_content();
        
        // Check for proper ARIA usage
        $this->assertContains('role="banner"', $content); // Header
        $this->assertContains('role="main"', $content);   // Main content
        $this->assertContains('role="navigation"', $content); // Navigation
        
        // Interactive elements should have proper ARIA
        if (strpos($content, 'aria-expanded') !== false) {
            $this->assertRegExp('/aria-expanded="(?:true|false)"/', $content);
        }
    }
}
```

## 7. Browser Compatibility Tests

### 7.1 Cross-Browser Testing Setup
```javascript
// tests/browser/playwright.config.js
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/browser',
    timeout: 30000,
    expect: {
        timeout: 5000
    },
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: 'html',
    use: {
        baseURL: 'http://localhost:8080',
        trace: 'on-first-retry',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'firefox',
            use: { ...devices['Desktop Firefox'] },
        },
        {
            name: 'webkit',
            use: { ...devices['Desktop Safari'] },
        },
        {
            name: 'Mobile Chrome',
            use: { ...devices['Pixel 5'] },
        },
        {
            name: 'Mobile Safari',
            use: { ...devices['iPhone 12'] },
        },
    ],
    webServer: {
        command: 'npm run serve:test',
        port: 8080,
    },
});
```

### 7.2 Browser Functionality Tests
```javascript
// tests/browser/search.spec.js
const { test, expect } = require('@playwright/test');

test.describe('Search Functionality', () => {
    test('search form works across browsers', async ({ page }) => {
        await page.goto('/');
        
        // Test search input
        await page.fill('[data-testid="search-input"]', 'action movie');
        await page.click('[data-testid="search-button"]');
        
        // Wait for results
        await page.waitForSelector('[data-testid="search-results"]');
        
        // Verify results displayed
        const results = await page.locator('[data-testid="search-results"] .movie-card');
        expect(await results.count()).toBeGreaterThan(0);
    });
    
    test('autocomplete functionality', async ({ page }) => {
        await page.goto('/');
        
        // Type in search box
        await page.fill('[data-testid="search-input"]', 'fight');
        
        // Wait for autocomplete
        await page.waitForSelector('[data-testid="autocomplete-suggestions"]');
        
        // Check suggestions appear
        const suggestions = await page.locator('[data-testid="autocomplete-suggestions"] li');
        expect(await suggestions.count()).toBeGreaterThan(0);
        
        // Click first suggestion
        await suggestions.first().click();
        
        // Should navigate to movie page
        expect(page.url()).toContain('/movie/');
    });
    
    test('filter functionality', async ({ page }) => {
        await page.goto('/search');
        
        // Apply genre filter
        await page.check('[data-filter="genre"][value="action"]');
        
        // Wait for filtered results
        await page.waitForLoadState('networkidle');
        
        // Verify filter applied
        const filterCount = await page.locator('[data-testid="filter-count"]').textContent();
        expect(filterCount).toContain('Action');
    });
});

test.describe('Movie Pages', () => {
    test('movie page loads correctly', async ({ page }) => {
        await page.goto('/movie/test-movie/');
        
        // Check key elements exist
        await expect(page.locator('h1')).toContainText('Test Movie');
        await expect(page.locator('[data-testid="movie-poster"]')).toBeVisible();
        await expect(page.locator('[data-testid="movie-overview"]')).toBeVisible();
        
        // Test responsive design
        const poster = page.locator('[data-testid="movie-poster"]');
        await expect(poster).toBeVisible();
    });
    
    test('tab navigation works', async ({ page }) => {
        await page.goto('/movie/test-movie/');
        
        // Click cast tab
        await page.click('[data-tab="cast"]');
        await expect(page.locator('[data-testid="cast-list"]')).toBeVisible();
        
        // Click media tab
        await page.click('[data-tab="media"]');
        await expect(page.locator('[data-testid="media-gallery"]')).toBeVisible();
    });
});

test.describe('Responsive Design', () => {
    test('mobile navigation', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 667 });
        await page.goto('/');
        
        // Mobile menu should be hidden initially
        await expect(page.locator('[data-testid="mobile-menu"]')).not.toBeVisible();
        
        // Click menu toggle
        await page.click('[data-testid="mobile-menu-toggle"]');
        
        // Menu should appear
        await expect(page.locator('[data-testid="mobile-menu"]')).toBeVisible();
    });
    
    test('responsive grid layout', async ({ page }) => {
        await page.goto('/movies');
        
        // Desktop: 4 columns
        await page.setViewportSize({ width: 1200, height: 800 });
        const desktopCards = await page.locator('.movie-grid .movie-card').count();
        
        // Tablet: 3 columns
        await page.setViewportSize({ width: 768, height: 600 });
        const tabletCards = await page.locator('.movie-grid .movie-card').count();
        
        // Mobile: 2 columns
        await page.setViewportSize({ width: 375, height: 667 });
        const mobileCards = await page.locator('.movie-grid .movie-card').count();
        
        // Layout should adapt
        expect(desktopCards).toBeGreaterThanOrEqual(tabletCards);
        expect(tabletCards).toBeGreaterThanOrEqual(mobileCards);
    });
});
```

## 8. Deployment and Monitoring

### 8.1 Deployment Script
```bash
#!/bin/bash
# deploy.sh - Theme deployment script

set -e

echo "Starting TMU Theme deployment..."

# Configuration
THEME_NAME="tmu-theme"
BUILD_DIR="build"
DIST_DIR="dist"

# Clean previous builds
echo "Cleaning previous builds..."
rm -rf $BUILD_DIR $DIST_DIR
mkdir -p $BUILD_DIR $DIST_DIR

# Install dependencies
echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader
npm ci --production

# Build assets
echo "Building assets..."
npm run build:production

# Copy theme files
echo "Copying theme files..."
cp -r src/ $BUILD_DIR/
cp -r templates/ $BUILD_DIR/
cp -r assets/dist/ $BUILD_DIR/assets/
cp functions.php style.css index.php $BUILD_DIR/

# Generate version info
echo "Generating version info..."
COMMIT_HASH=$(git rev-parse --short HEAD)
BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

cat > $BUILD_DIR/version.json << EOF
{
    "version": "2.0.0",
    "commit": "$COMMIT_HASH",
    "build_date": "$BUILD_DATE",
    "php_version": "$(php -r 'echo PHP_VERSION;')",
    "wp_version": "6.0+"
}
EOF

# Run tests
echo "Running tests..."
vendor/bin/phpunit --testsuite=production
npm run test:browser:ci

# Create distribution package
echo "Creating distribution package..."
cd $BUILD_DIR
zip -r "../$DIST_DIR/$THEME_NAME-$COMMIT_HASH.zip" .
cd ..

# Deploy to staging (if configured)
if [ "$DEPLOY_STAGING" = "true" ]; then
    echo "Deploying to staging..."
    rsync -avz --delete $BUILD_DIR/ $STAGING_SERVER:$STAGING_PATH/
fi

# Deploy to production (if configured)
if [ "$DEPLOY_PRODUCTION" = "true" ]; then
    echo "Deploying to production..."
    rsync -avz --delete $BUILD_DIR/ $PRODUCTION_SERVER:$PRODUCTION_PATH/
fi

echo "Deployment completed successfully!"
echo "Package created: $DIST_DIR/$THEME_NAME-$COMMIT_HASH.zip"
```

### 8.2 Health Check and Monitoring
```php
// src/Monitoring/HealthCheck.php
<?php
namespace TMU\Monitoring;

class HealthCheck {
    public function run_health_check(): array {
        $checks = [
            'database' => $this->check_database_connection(),
            'tmdb_api' => $this->check_tmdb_api(),
            'file_permissions' => $this->check_file_permissions(),
            'memory_usage' => $this->check_memory_usage(),
            'disk_space' => $this->check_disk_space(),
            'cache_status' => $this->check_cache_status()
        ];
        
        $overall_status = !in_array(false, $checks, true) ? 'healthy' : 'unhealthy';
        
        return [
            'status' => $overall_status,
            'timestamp' => current_time('mysql'),
            'checks' => $checks
        ];
    }
    
    private function check_database_connection(): bool {
        global $wpdb;
        
        try {
            $wpdb->get_var("SELECT 1");
            return true;
        } catch (Exception $e) {
            error_log("Database health check failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function check_tmdb_api(): bool {
        if (!get_option('tmu_tmdb_api_key')) {
            return true; // Not configured, so not an error
        }
        
        try {
            $client = new \TMU\TMDB\TMDBClient();
            $result = $client->get_movie_details(550); // Test with Fight Club
            return !empty($result['id']);
        } catch (Exception $e) {
            error_log("TMDB API health check failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function check_file_permissions(): bool {
        $upload_dir = wp_upload_dir();
        
        return is_writable($upload_dir['basedir']);
    }
    
    private function check_memory_usage(): bool {
        $memory_limit = $this->convert_to_bytes(ini_get('memory_limit'));
        $memory_usage = memory_get_usage(true);
        
        return ($memory_usage / $memory_limit) < 0.8; // Less than 80%
    }
    
    private function check_disk_space(): bool {
        $upload_dir = wp_upload_dir();
        $free_bytes = disk_free_space($upload_dir['basedir']);
        $total_bytes = disk_total_space($upload_dir['basedir']);
        
        return ($free_bytes / $total_bytes) > 0.1; // More than 10% free
    }
    
    private function check_cache_status(): bool {
        // Check if object cache is working
        $test_key = 'tmu_cache_test_' . time();
        $test_value = 'cache_working';
        
        wp_cache_set($test_key, $test_value);
        $cached_value = wp_cache_get($test_key);
        
        wp_cache_delete($test_key);
        
        return $cached_value === $test_value;
    }
    
    private function convert_to_bytes($value): int {
        $unit = strtolower(substr($value, -1));
        $num = (int) $value;
        
        switch ($unit) {
            case 'g': $num *= 1024;
            case 'm': $num *= 1024;
            case 'k': $num *= 1024;
        }
        
        return $num;
    }
}
```

## 9. Success Metrics

### 9.1 Test Coverage Requirements
- [ ] Unit test coverage: 90%+
- [ ] Integration test coverage: 80%+
- [ ] All critical paths tested
- [ ] Security tests passing
- [ ] Accessibility tests passing
- [ ] Cross-browser compatibility verified

### 9.2 Performance Benchmarks
- [ ] Page load time: <2 seconds
- [ ] Search response time: <500ms
- [ ] Memory usage: <64MB per request
- [ ] Database queries: <20 per page
- [ ] Core Web Vitals: Good scores

### 9.3 Quality Assurance Checklist
- [ ] Code standards compliance (PHPCS/WPCS)
- [ ] Static analysis passing (PHPStan)
- [ ] No critical security vulnerabilities
- [ ] WCAG 2.1 AA compliance
- [ ] Mobile responsiveness verified
- [ ] SEO optimization confirmed

## Final Validation

After completing all 15 steps, the TMU theme will have:

✅ **Complete Functionality Migration** - 100% plugin features preserved  
✅ **Modern Architecture** - Clean OOP structure with proper namespacing  
✅ **Enhanced Performance** - Optimized database queries and caching  
✅ **Superior User Experience** - Modern responsive design with accessibility  
✅ **Robust Security** - Input validation, sanitization, and protection  
✅ **SEO Excellence** - Schema markup, meta tags, and sitemaps  
✅ **Advanced Search** - Faceted search with intelligent recommendations  
✅ **Comprehensive Testing** - Automated testing with quality assurance  
✅ **Production Ready** - Deployment procedures and monitoring systems  

The theme successfully transforms the TMU plugin into a modern, scalable, and maintainable WordPress theme while preserving all existing functionality and enhancing the overall user experience.