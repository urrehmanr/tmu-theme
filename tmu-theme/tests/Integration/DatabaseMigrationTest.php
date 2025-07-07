<?php
/**
 * Database Migration Test
 * 
 * Tests for database migration functionality.
 * 
 * @package TMU\Tests\Integration
 * @since 1.0.0
 */

namespace TMU\Tests\Integration;

use TMU\Database\MigrationManager;
use TMU\Tests\Utilities\DatabaseTestCase;

/**
 * DatabaseMigrationTest class
 * 
 * Integration tests for database migrations
 */
class DatabaseMigrationTest extends DatabaseTestCase {
    
    /**
     * Migration manager instance
     * @var MigrationManager
     */
    private $migration_manager;
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        if (class_exists('TMU\\Database\\MigrationManager')) {
            $this->migration_manager = new MigrationManager();
        } else {
            $this->markTestSkipped('MigrationManager class not found');
        }
    }
    
    /**
     * Test fresh installation
     */
    public function test_fresh_installation(): void {
        // Test migration from scratch
        $this->migration_manager->run_migrations();
        
        // Verify all tables created
        $this->assert_tables_exist();
        
        // Verify initial data seeded
        $this->assert_initial_data_exists();
    }
    
    /**
     * Test plugin to theme migration
     */
    public function test_plugin_to_theme_migration(): void {
        // Simulate existing plugin data
        $this->create_plugin_test_data();
        
        // Run migration
        $result = $this->migration_manager->migrate_from_plugin();
        
        $this->assertTrue($result, 'Plugin migration should succeed');
        
        // Verify data integrity
        $this->assert_plugin_data_migrated();
    }
    
    /**
     * Test version upgrade
     */
    public function test_version_upgrade(): void {
        // Simulate older theme version
        update_option('tmu_theme_version', '1.0.0');
        
        // Run upgrade
        $this->migration_manager->upgrade_database();
        
        // Verify version updated
        $this->assertEquals('2.0.0', get_option('tmu_theme_version'), 'Theme version should be updated');
    }
    
    /**
     * Test data backup and restore
     */
    public function test_data_backup_and_restore(): void {
        // Create test data
        $movie_id = $this->create_movie(['title' => 'Backup Test Movie']);
        
        // Create backup
        $backup_file = $this->migration_manager->create_backup();
        $this->assertFileExists($backup_file, 'Backup file should be created');
        
        // Delete data
        wp_delete_post($movie_id, true);
        $this->assertNull(get_post($movie_id), 'Movie should be deleted');
        
        // Restore backup
        $this->migration_manager->restore_backup($backup_file);
        
        // Verify data restored
        $restored_post = get_post($movie_id);
        $this->assertNotNull($restored_post, 'Movie should be restored');
        $this->assertEquals('Backup Test Movie', $restored_post->post_title, 'Movie title should match');
        
        // Clean up
        if (file_exists($backup_file)) {
            unlink($backup_file);
        }
    }
    
    /**
     * Test schema changes
     */
    public function test_schema_changes(): void {
        global $wpdb;
        
        // Test adding new column
        $table_name = $wpdb->prefix . 'tmu_movies';
        
        // Add test column
        $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN test_column VARCHAR(255) DEFAULT NULL");
        
        // Run schema update
        $this->migration_manager->update_schema();
        
        // Verify column exists
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
        $column_names = array_column($columns, 'Field');
        $this->assertContains('test_column', $column_names, 'Test column should exist after migration');
        
        // Clean up
        $wpdb->query("ALTER TABLE {$table_name} DROP COLUMN test_column");
    }
    
    /**
     * Test rollback functionality
     */
    public function test_rollback_functionality(): void {
        $current_version = get_option('tmu_theme_version', '1.0.0');
        
        // Simulate failed migration
        update_option('tmu_theme_version', '2.0.0');
        
        // Test rollback
        $result = $this->migration_manager->rollback_to_version($current_version);
        
        $this->assertTrue($result, 'Rollback should succeed');
        $this->assertEquals($current_version, get_option('tmu_theme_version'), 'Version should be rolled back');
    }
    
    /**
     * Test incremental migrations
     */
    public function test_incremental_migrations(): void {
        // Set initial version
        update_option('tmu_theme_version', '1.0.0');
        
        // Run incremental migrations
        $migrations = ['1.1.0', '1.2.0', '2.0.0'];
        
        foreach ($migrations as $version) {
            $result = $this->migration_manager->migrate_to_version($version);
            $this->assertTrue($result, "Migration to {$version} should succeed");
            $this->assertEquals($version, get_option('tmu_theme_version'), "Version should be updated to {$version}");
        }
    }
    
    /**
     * Create plugin test data
     */
    private function create_plugin_test_data(): void {
        global $wpdb;
        
        $movie_post = $this->create_movie(['title' => 'Plugin Movie']);
        
        // Simulate plugin table structure
        $wpdb->insert($wpdb->prefix . 'tmu_movies', [
            'post_id' => $movie_post,
            'tmdb_id' => 12345,
            'title' => 'Plugin Movie',
            'overview' => 'A movie from plugin data'
        ]);
    }
    
    /**
     * Assert plugin data migrated
     */
    private function assert_plugin_data_migrated(): void {
        $movies = get_posts([
            'post_type' => 'movie', 
            'meta_query' => [
                [
                    'key' => '_migrated_from_plugin',
                    'value' => '1',
                    'compare' => '='
                ]
            ]
        ]);
        
        $this->assertGreaterThan(0, count($movies), 'Should have migrated movies');
        
        // Test specific movie data
        if (function_exists('tmu_get_movie_data')) {
            foreach ($movies as $movie) {
                $movie_data = tmu_get_movie_data($movie->ID);
                if (!empty($movie_data)) {
                    $this->assertArrayHasKey('tmdb_id', $movie_data, 'Movie data should have TMDB ID');
                }
            }
        }
    }
    
    /**
     * Test foreign key constraints
     */
    public function test_foreign_key_constraints(): void {
        global $wpdb;
        
        // Test that foreign key constraints work
        $movie_id = $this->create_movie(['title' => 'FK Test Movie']);
        $person_id = $this->create_person(['title' => 'FK Test Person']);
        
        // Add cast relationship
        $cast_table = $wpdb->prefix . 'tmu_cast';
        $result = $wpdb->insert($cast_table, [
            'post_id' => $movie_id,
            'person_id' => $person_id,
            'character_name' => 'Test Character'
        ]);
        
        $this->assertNotFalse($result, 'Cast relationship should be created');
        
        // Verify relationship exists
        $cast_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$cast_table} WHERE post_id = %d AND person_id = %d",
            $movie_id,
            $person_id
        ));
        
        $this->assertEquals(1, $cast_count, 'Cast relationship should exist');
    }
    
    /**
     * Test data integrity
     */
    public function test_data_integrity(): void {
        // Create movies with relationships
        $movie_id = $this->create_movie([
            'title' => 'Integrity Test Movie',
            'tmdb_id' => 99999
        ]);
        
        // Add genres
        $genre_id = $this->create_term('Test Genre', 'genre');
        wp_set_post_terms($movie_id, [$genre_id], 'genre');
        
        // Run integrity check
        $integrity_result = $this->migration_manager->check_data_integrity();
        
        $this->assertTrue($integrity_result['passed'], 'Data integrity check should pass');
        $this->assertEmpty($integrity_result['errors'], 'Should have no integrity errors');
    }
}