# Step 03: Database Migration System

## Purpose
Implement a robust database migration system that preserves existing TMU plugin data while providing a clean setup process for new installations. This system ensures 100% data compatibility and seamless transition from plugin to theme.

## Overview
The database migration system will handle two scenarios:
1. **Existing Plugin Data**: Detect and preserve existing plugin tables and data
2. **Fresh Installation**: Create all necessary tables and relationships for new setups

## Database Schema Analysis

### Existing Plugin Tables
Based on the plugin analysis, the following tables must be preserved:

```sql
-- Core content tables
wp_tmu_movies
wp_tmu_people  
wp_tmu_dramas
wp_tmu_tv_series
wp_tmu_videos
wp_tmu_seo_options

-- Relationship tables
wp_tmu_movies_cast
wp_tmu_movies_crew
wp_tmu_dramas_cast
wp_tmu_dramas_crew
wp_tmu_tv_series_cast
wp_tmu_tv_series_crew

-- Episode/Season tables
wp_tmu_tv_series_episodes
wp_tmu_tv_series_seasons
wp_tmu_dramas_episodes
wp_tmu_dramas_seasons

-- Extended core tables
wp_comments (with comment_rating, parent_post_id columns)
wp_posts (with seo_title, seo_description, meta_keywords columns)
```

## Migration System Architecture

### Directory Structure
```
includes/
├── classes/
│   └── Database/
│       ├── Migration.php          # Main migration handler
│       ├── Schema.php             # Schema definitions
│       ├── MigrationRunner.php    # Migration execution
│       └── DataValidator.php      # Data validation
├── migrations/
│   ├── 001_create_core_tables.php     # Core tables creation
│   ├── 002_create_cast_crew_tables.php # Cast/crew relationship tables
│   ├── 003_create_episode_tables.php   # Episode/season tables
│   ├── 004_extend_core_tables.php      # Extend wp_posts and wp_comments
│   ├── 005_create_indexes.php          # Performance indexes
│   └── 006_seed_initial_data.php       # Initial data seeding
├── config/
│   └── database.php               # Database configuration
```

## Migration Class Implementation

### 1. Main Migration Class (`includes/classes/Database/Migration.php`)
```php
<?php
/**
 * TMU Database Migration Handler
 *
 * @package TMU\Database
 * @version 1.0.0
 */

namespace TMU\Database;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Migration Handler
 */
class Migration {
    
    /**
     * Migration instance
     *
     * @var Migration
     */
    private static $instance = null;
    
    /**
     * WordPress database instance
     *
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * Migration version option key
     *
     * @var string
     */
    private $version_option = 'tmu_db_version';
    
    /**
     * Current migration version
     *
     * @var string
     */
    private $current_version = '1.0.0';
    
    /**
     * Get migration instance
     *
     * @return Migration
     */
    public static function getInstance(): Migration {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->initHooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void {
        add_action('after_switch_theme', [$this, 'runMigrations']);
        add_action('admin_init', [$this, 'checkMigrationStatus']);
    }
    
    /**
     * Run database migrations
     */
    public function runMigrations(): void {
        $installed_version = get_option($this->version_option, '0.0.0');
        
        if (version_compare($installed_version, $this->current_version, '<')) {
            $this->executeMigrations($installed_version);
            update_option($this->version_option, $this->current_version);
        }
    }
    
    /**
     * Check migration status
     */
    public function checkMigrationStatus(): void {
        $installed_version = get_option($this->version_option, '0.0.0');
        
        if (version_compare($installed_version, $this->current_version, '<')) {
            add_action('admin_notices', [$this, 'showMigrationNotice']);
        }
    }
    
    /**
     * Execute migrations based on version
     *
     * @param string $from_version
     */
    private function executeMigrations(string $from_version): void {
        $migrations = $this->getMigrationFiles();
        
        foreach ($migrations as $migration_file) {
            if ($this->shouldRunMigration($migration_file, $from_version)) {
                $this->runMigrationFile($migration_file);
            }
        }
    }
    
    /**
     * Get migration files
     *
     * @return array
     */
    private function getMigrationFiles(): array {
        $migration_dir = TMU_INCLUDES_DIR . '/migrations/';
        $files = glob($migration_dir . '*.php');
        
        return array_filter($files, function($file) {
            return is_readable($file);
        });
    }
    
    /**
     * Check if migration should run
     *
     * @param string $migration_file
     * @param string $from_version
     * @return bool
     */
    private function shouldRunMigration(string $migration_file, string $from_version): bool {
        // Extract version from filename and compare
        $filename = basename($migration_file);
        
        // For new installations, run all migrations
        if ($from_version === '0.0.0') {
            return true;
        }
        
        // For existing installations, check if tables exist
        return $this->needsMigration($filename);
    }
    
    /**
     * Check if specific migration is needed
     *
     * @param string $filename
     * @return bool
     */
    private function needsMigration(string $filename): bool {
        switch ($filename) {
            case '001_create_core_tables.php':
                return !$this->tableExists('tmu_movies');
            case '002_create_cast_crew_tables.php':
                return !$this->tableExists('tmu_movies_cast');
            case '003_create_episode_tables.php':
                return !$this->tableExists('tmu_tv_series_episodes');
            case '004_extend_core_tables.php':
                return !$this->columnExists('posts', 'seo_title');
            case '005_create_indexes.php':
                return !$this->indexExists('tmu_movies', 'tmdb_id');
            default:
                return true;
        }
    }
    
    /**
     * Run migration file
     *
     * @param string $migration_file
     */
    private function runMigrationFile(string $migration_file): void {
        try {
            require_once $migration_file;
            
            $class_name = $this->getMigrationClassName($migration_file);
            
            if (class_exists($class_name)) {
                $migration = new $class_name();
                $migration->up();
            }
        } catch (Exception $e) {
            error_log("TMU Migration Error: " . $e->getMessage());
            wp_die("Migration failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get migration class name from file
     *
     * @param string $migration_file
     * @return string
     */
    private function getMigrationClassName(string $migration_file): string {
        $filename = basename($migration_file, '.php');
        $parts = explode('_', $filename);
        array_shift($parts); // Remove number prefix
        
        return 'TMU\\Database\\Migrations\\' . implode('', array_map('ucfirst', $parts));
    }
    
    /**
     * Check if table exists
     *
     * @param string $table_name
     * @return bool
     */
    private function tableExists(string $table_name): bool {
        $table_name = $this->wpdb->prefix . $table_name;
        $query = $this->wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
        
        return $this->wpdb->get_var($query) === $table_name;
    }
    
    /**
     * Check if column exists
     *
     * @param string $table_name
     * @param string $column_name
     * @return bool
     */
    private function columnExists(string $table_name, string $column_name): bool {
        $table_name = $this->wpdb->prefix . $table_name;
        $query = $this->wpdb->prepare("SHOW COLUMNS FROM `{$table_name}` LIKE %s", $column_name);
        
        return $this->wpdb->get_var($query) !== null;
    }
    
    /**
     * Check if index exists
     *
     * @param string $table_name
     * @param string $index_name
     * @return bool
     */
    private function indexExists(string $table_name, string $index_name): bool {
        $table_name = $this->wpdb->prefix . $table_name;
        $query = $this->wpdb->prepare("SHOW INDEX FROM `{$table_name}` WHERE Key_name = %s", $index_name);
        
        return $this->wpdb->get_var($query) !== null;
    }
    
    /**
     * Show migration notice
     */
    public function showMigrationNotice(): void {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>TMU Theme:</strong> Database migration is required. Please run the migration to ensure proper functionality.</p>';
        echo '<p><a href="' . admin_url('admin.php?page=tmu-migration') . '" class="button-primary">Run Migration</a></p>';
        echo '</div>';
    }
    
    /**
     * Get database version
     *
     * @return string
     */
    public function getDatabaseVersion(): string {
        return get_option($this->version_option, '0.0.0');
    }
}
```

### 2. Schema Definition Class (`includes/classes/Database/Schema.php`)
```php
<?php
/**
 * TMU Database Schema Definitions
 *
 * @package TMU\Database
 * @version 1.0.0
 */

namespace TMU\Database;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Schema Definitions
 */
class Schema {
    
    /**
     * Get movies table schema
     *
     * @return string
     */
    public static function getMoviesTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_movies` (
            `ID` bigint(20) UNSIGNED NOT NULL,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `release_date` text DEFAULT NULL,
            `release_timestamp` bigint(20) DEFAULT NULL,
            `original_title` text DEFAULT NULL,
            `tagline` text DEFAULT NULL,
            `production_house` text DEFAULT NULL,
            `streaming_platforms` text DEFAULT NULL,
            `runtime` bigint(20) DEFAULT NULL,
            `certification` text DEFAULT NULL,
            `revenue` bigint(20) DEFAULT NULL,
            `budget` bigint(20) DEFAULT NULL,
            `star_cast` text DEFAULT NULL,
            `credits` longtext DEFAULT NULL,
            `credits_temp` longtext DEFAULT NULL,
            `videos` text DEFAULT NULL,
            `images` text DEFAULT NULL,
            `average_rating` DECIMAL(10,1) DEFAULT 0,
            `vote_count` bigint(20) DEFAULT 0,
            `popularity` DECIMAL(10,1) DEFAULT 0,
            `total_average_rating` DECIMAL(10,1) DEFAULT 0,
            `total_vote_count` bigint(20) NOT NULL DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `tmdb_id` (`tmdb_id`),
            KEY `release_timestamp` (`release_timestamp`),
            KEY `popularity` (`popularity`),
            FOREIGN KEY (`ID`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get people table schema
     *
     * @return string
     */
    public static function getPeopleTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_people` (
            `ID` bigint(20) UNSIGNED NOT NULL,
            `name` text DEFAULT NULL,
            `date_of_birth` text DEFAULT NULL,
            `gender` text DEFAULT NULL,
            `nick_name` text DEFAULT NULL,
            `marital_status` text DEFAULT NULL,
            `basic` text DEFAULT NULL,
            `videos` text DEFAULT NULL,
            `photos` text DEFAULT NULL,
            `profession` text DEFAULT NULL,
            `net_worth` bigint(20) DEFAULT NULL,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `birthplace` text DEFAULT NULL,
            `dead_on` text DEFAULT NULL,
            `social_media_account` text DEFAULT NULL,
            `no_movies` bigint(20) DEFAULT NULL,
            `no_tv_series` bigint(20) DEFAULT NULL,
            `no_dramas` bigint(20) DEFAULT NULL,
            `known_for` text DEFAULT NULL,
            `popularity` DECIMAL(10,1) DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `tmdb_id` (`tmdb_id`),
            KEY `date_of_birth` (`date_of_birth`(10)),
            KEY `popularity` (`popularity`),
            FOREIGN KEY (`ID`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get TV series table schema
     *
     * @return string
     */
    public static function getTVSeriesTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_tv_series` (
            `ID` bigint(20) UNSIGNED NOT NULL,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `release_date` text DEFAULT NULL,
            `release_timestamp` bigint(20) DEFAULT NULL,
            `original_title` text DEFAULT NULL,
            `finished` text DEFAULT NULL,
            `tagline` text DEFAULT NULL,
            `production_house` text DEFAULT NULL,
            `streaming_platforms` text DEFAULT NULL,
            `schedule_time` text DEFAULT NULL,
            `runtime` bigint(20) DEFAULT NULL,
            `certification` text DEFAULT NULL,
            `revenue` bigint(20) DEFAULT NULL,
            `budget` bigint(20) DEFAULT NULL,
            `star_cast` text DEFAULT NULL,
            `credits` longtext DEFAULT NULL,
            `credits_temp` longtext DEFAULT NULL,
            `videos` text DEFAULT NULL,
            `images` text DEFAULT NULL,
            `seasons` text DEFAULT NULL,
            `last_season` bigint(20) DEFAULT NULL,
            `last_episode` bigint(20) DEFAULT NULL,
            `average_rating` DECIMAL(10,1) DEFAULT 0,
            `vote_count` bigint(20) DEFAULT 0,
            `popularity` DECIMAL(10,1) DEFAULT 0,
            `where_to_watch` text DEFAULT NULL,
            `total_average_rating` DECIMAL(10,1) DEFAULT 0,
            `total_vote_count` bigint(20) NOT NULL DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `tmdb_id` (`tmdb_id`),
            KEY `release_timestamp` (`release_timestamp`),
            KEY `popularity` (`popularity`),
            FOREIGN KEY (`ID`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get dramas table schema
     *
     * @return string
     */
    public static function getDramasTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_dramas` (
            `ID` bigint(20) UNSIGNED NOT NULL,
            `tmdb_id` bigint(20) DEFAULT NULL,
            `release_date` text DEFAULT NULL,
            `release_timestamp` bigint(20) DEFAULT NULL,
            `original_title` text DEFAULT NULL,
            `finished` text DEFAULT NULL,
            `tagline` text DEFAULT NULL,
            `seo_genre` BIGINT(20) NULL DEFAULT NULL,
            `production_house` text DEFAULT NULL,
            `streaming_platforms` text DEFAULT NULL,
            `schedule_day` text DEFAULT NULL,
            `schedule_time` text DEFAULT NULL,
            `schedule_timestamp` bigint(20) DEFAULT NULL,
            `runtime` bigint(20) DEFAULT NULL,
            `certification` text DEFAULT NULL,
            `star_cast` text DEFAULT NULL,
            `credits` longtext DEFAULT NULL,
            `credits_temp` longtext DEFAULT NULL,
            `videos` text DEFAULT NULL,
            `images` text DEFAULT NULL,
            `average_rating` DECIMAL(10,1) DEFAULT 0,
            `vote_count` bigint(20) DEFAULT 0,
            `popularity` DECIMAL(10,1) DEFAULT 0,
            `where_to_watch` text DEFAULT NULL,
            `total_average_rating` DECIMAL(10,1) DEFAULT 0,
            `total_vote_count` bigint(20) NOT NULL DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `tmdb_id` (`tmdb_id`),
            KEY `release_timestamp` (`release_timestamp`),
            KEY `schedule_timestamp` (`schedule_timestamp`),
            KEY `popularity` (`popularity`),
            FOREIGN KEY (`ID`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get movies cast table schema
     *
     * @return string
     */
    public static function getMoviesCastTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_movies_cast` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `movie` bigint(20) UNSIGNED NOT NULL,
            `person` bigint(20) UNSIGNED NOT NULL,
            `job` varchar(255) DEFAULT NULL,
            `character_name` varchar(255) DEFAULT NULL,
            `release_year` bigint(20) DEFAULT NULL,
            `order_no` int(11) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `movie` (`movie`),
            KEY `person` (`person`),
            KEY `release_year` (`release_year`),
            FOREIGN KEY (`movie`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY (`person`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get SEO options table schema
     *
     * @return string
     */
    public static function getSEOOptionsTableSchema(): string {
        global $wpdb;
        
        return "CREATE TABLE `{$wpdb->prefix}tmu_seo_options` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` text DEFAULT NULL,
            `title` text DEFAULT NULL,
            `description` text DEFAULT NULL,
            `keywords` text DEFAULT NULL,
            `robots` text DEFAULT NULL,
            `post_type` text DEFAULT NULL,
            `section` text DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `post_type` (`post_type`(50)),
            KEY `section` (`section`(50))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
    }
    
    /**
     * Get core table extensions
     *
     * @return array
     */
    public static function getCoreTableExtensions(): array {
        global $wpdb;
        
        return [
            // Extend wp_posts table
            "ALTER TABLE `{$wpdb->prefix}posts` ADD COLUMN `seo_title` TEXT NULL DEFAULT NULL",
            "ALTER TABLE `{$wpdb->prefix}posts` ADD COLUMN `seo_description` TEXT NULL DEFAULT NULL",
            "ALTER TABLE `{$wpdb->prefix}posts` ADD COLUMN `meta_keywords` TEXT NULL DEFAULT NULL",
            
            // Extend wp_comments table
            "ALTER TABLE `{$wpdb->prefix}comments` ADD COLUMN `comment_rating` INT(11) NOT NULL DEFAULT 0",
            "ALTER TABLE `{$wpdb->prefix}comments` ADD COLUMN `parent_post_id` INT(11) DEFAULT NULL",
            
            // Add indexes for performance
            "ALTER TABLE `{$wpdb->prefix}posts` ADD INDEX `seo_title` (`seo_title`(50))",
            "ALTER TABLE `{$wpdb->prefix}comments` ADD INDEX `comment_rating` (`comment_rating`)",
            "ALTER TABLE `{$wpdb->prefix}comments` ADD INDEX `parent_post_id` (`parent_post_id`)",
        ];
    }
}
```

## Migration Files

### 1. Core Tables Migration (`includes/migrations/001_create_core_tables.php`)
```php
<?php
/**
 * Create Core Tables Migration
 *
 * @package TMU\Database\Migrations
 * @version 1.0.0
 */

namespace TMU\Database\Migrations;

use TMU\Database\Schema;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create Core Tables
 */
class CreateCoreTables {
    
    /**
     * Run the migration
     */
    public function up(): void {
        global $wpdb;
        
        // Require WordPress database upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create movies table
        $sql = Schema::getMoviesTableSchema();
        dbDelta($sql);
        
        // Create people table
        $sql = Schema::getPeopleTableSchema();
        dbDelta($sql);
        
        // Create TV series table
        $sql = Schema::getTVSeriesTableSchema();
        dbDelta($sql);
        
        // Create dramas table
        $sql = Schema::getDramasTableSchema();
        dbDelta($sql);
        
        // Create videos table
        $sql = "CREATE TABLE `{$wpdb->prefix}tmu_videos` (
            `ID` bigint(20) UNSIGNED NOT NULL,
            `video_data` text DEFAULT NULL,
            `post_id` bigint(20) UNSIGNED NOT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY `post_id` (`post_id`),
            FOREIGN KEY (`ID`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
            FOREIGN KEY (`post_id`) REFERENCES `{$wpdb->prefix}posts`(`ID`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
        dbDelta($sql);
        
        // Create SEO options table
        $sql = Schema::getSEOOptionsTableSchema();
        dbDelta($sql);
        
        // Log migration
        error_log('TMU Migration: Core tables created successfully');
    }
    
    /**
     * Rollback the migration
     */
    public function down(): void {
        global $wpdb;
        
        // Drop tables in reverse order
        $tables = [
            'tmu_videos',
            'tmu_seo_options',
            'tmu_dramas',
            'tmu_tv_series',
            'tmu_people',
            'tmu_movies'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}{$table}`");
        }
        
        error_log('TMU Migration: Core tables dropped');
    }
}
```

### 2. Extend Core Tables Migration (`includes/migrations/004_extend_core_tables.php`)
```php
<?php
/**
 * Extend Core Tables Migration
 *
 * @package TMU\Database\Migrations
 * @version 1.0.0
 */

namespace TMU\Database\Migrations;

use TMU\Database\Schema;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Extend Core Tables
 */
class ExtendCoreTables {
    
    /**
     * Run the migration
     */
    public function up(): void {
        global $wpdb;
        
        $extensions = Schema::getCoreTableExtensions();
        
        foreach ($extensions as $sql) {
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                error_log("TMU Migration Error: {$wpdb->last_error}");
            }
        }
        
        error_log('TMU Migration: Core tables extended successfully');
    }
    
    /**
     * Rollback the migration
     */
    public function down(): void {
        global $wpdb;
        
        // Remove added columns
        $wpdb->query("ALTER TABLE `{$wpdb->prefix}posts` DROP COLUMN `seo_title`");
        $wpdb->query("ALTER TABLE `{$wpdb->prefix}posts` DROP COLUMN `seo_description`");
        $wpdb->query("ALTER TABLE `{$wpdb->prefix}posts` DROP COLUMN `meta_keywords`");
        
        $wpdb->query("ALTER TABLE `{$wpdb->prefix}comments` DROP COLUMN `comment_rating`");
        $wpdb->query("ALTER TABLE `{$wpdb->prefix}comments` DROP COLUMN `parent_post_id`");
        
        error_log('TMU Migration: Core table extensions removed');
    }
}
```

## Data Validation and Safety

### Data Validator (`includes/classes/Database/DataValidator.php`)
```php
<?php
/**
 * TMU Data Validator
 *
 * @package TMU\Database
 * @version 1.0.0
 */

namespace TMU\Database;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data Validator
 */
class DataValidator {
    
    /**
     * Validate existing plugin data
     *
     * @return array
     */
    public static function validateExistingData(): array {
        global $wpdb;
        
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'data_count' => []
        ];
        
        // Check for existing plugin tables
        $plugin_tables = [
            'tmu_movies',
            'tmu_people',
            'tmu_dramas',
            'tmu_tv_series'
        ];
        
        foreach ($plugin_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            
            if (self::tableExists($table_name)) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
                $results['data_count'][$table] = $count;
                
                if ($count > 0) {
                    $results['warnings'][] = "Found {$count} records in {$table} table";
                }
            }
        }
        
        // Check for orphaned data
        $orphaned = self::checkOrphanedData();
        if (!empty($orphaned)) {
            $results['warnings'] = array_merge($results['warnings'], $orphaned);
        }
        
        return $results;
    }
    
    /**
     * Check for orphaned data
     *
     * @return array
     */
    private static function checkOrphanedData(): array {
        global $wpdb;
        
        $warnings = [];
        
        // Check for orphaned cast/crew records
        $orphaned_cast = $wpdb->get_var("
            SELECT COUNT(*) FROM `{$wpdb->prefix}tmu_movies_cast` mc
            LEFT JOIN `{$wpdb->prefix}posts` p ON mc.movie = p.ID
            WHERE p.ID IS NULL
        ");
        
        if ($orphaned_cast > 0) {
            $warnings[] = "Found {$orphaned_cast} orphaned cast records";
        }
        
        return $warnings;
    }
    
    /**
     * Check if table exists
     *
     * @param string $table_name
     * @return bool
     */
    private static function tableExists(string $table_name): bool {
        global $wpdb;
        
        $query = $wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
        return $wpdb->get_var($query) === $table_name;
    }
}
```

## Admin Interface Integration

### Migration Admin Page
```php
<?php
/**
 * TMU Migration Admin Page
 *
 * @package TMU\Admin
 * @version 1.0.0
 */

namespace TMU\Admin;

use TMU\Database\Migration;
use TMU\Database\DataValidator;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Migration Admin Page
 */
class MigrationAdmin {
    
    /**
     * Initialize admin page
     */
    public function init(): void {
        add_action('admin_menu', [$this, 'addAdminPage']);
        add_action('admin_post_tmu_run_migration', [$this, 'handleMigration']);
    }
    
    /**
     * Add admin page
     */
    public function addAdminPage(): void {
        add_management_page(
            'TMU Database Migration',
            'TMU Migration',
            'manage_options',
            'tmu-migration',
            [$this, 'renderMigrationPage']
        );
    }
    
    /**
     * Render migration page
     */
    public function renderMigrationPage(): void {
        $migration = Migration::getInstance();
        $current_version = $migration->getDatabaseVersion();
        $validation = DataValidator::validateExistingData();
        
        ?>
        <div class="wrap">
            <h1>TMU Database Migration</h1>
            
            <div class="card">
                <h2>Migration Status</h2>
                <p><strong>Current Database Version:</strong> <?php echo esc_html($current_version); ?></p>
                <p><strong>Target Version:</strong> 1.0.0</p>
                
                <?php if (!empty($validation['data_count'])): ?>
                <h3>Existing Data Found</h3>
                <ul>
                    <?php foreach ($validation['data_count'] as $table => $count): ?>
                        <li><?php echo esc_html($table); ?>: <?php echo esc_html($count); ?> records</li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                
                <?php if (!empty($validation['warnings'])): ?>
                <h3>Warnings</h3>
                <ul>
                    <?php foreach ($validation['warnings'] as $warning): ?>
                        <li><?php echo esc_html($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="tmu_run_migration">
                    <?php wp_nonce_field('tmu_migration_nonce'); ?>
                    
                    <p class="submit">
                        <input type="submit" name="submit" class="button-primary" value="Run Migration">
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle migration request
     */
    public function handleMigration(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        check_admin_referer('tmu_migration_nonce');
        
        try {
            $migration = Migration::getInstance();
            $migration->runMigrations();
            
            wp_redirect(add_query_arg('message', 'success', admin_url('tools.php?page=tmu-migration')));
            exit;
        } catch (Exception $e) {
            wp_redirect(add_query_arg('message', 'error', admin_url('tools.php?page=tmu-migration')));
            exit;
        }
    }
}
```

## Configuration and Settings

### Database Configuration (`includes/config/database.php`)
```php
<?php
/**
 * TMU Database Configuration
 *
 * @package TMU\Config
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

return [
    'version' => '1.0.0',
    'charset' => 'utf8mb4',
    'collate' => 'utf8mb4_unicode_520_ci',
    'engine' => 'InnoDB',
    
    'tables' => [
        'movies' => 'tmu_movies',
        'people' => 'tmu_people',
        'dramas' => 'tmu_dramas',
        'tv_series' => 'tmu_tv_series',
        'videos' => 'tmu_videos',
        'seo_options' => 'tmu_seo_options',
        'movies_cast' => 'tmu_movies_cast',
        'movies_crew' => 'tmu_movies_crew',
        'dramas_cast' => 'tmu_dramas_cast',
        'dramas_crew' => 'tmu_dramas_crew',
        'tv_series_cast' => 'tmu_tv_series_cast',
        'tv_series_crew' => 'tmu_tv_series_crew',
        'tv_series_episodes' => 'tmu_tv_series_episodes',
        'tv_series_seasons' => 'tmu_tv_series_seasons',
        'dramas_episodes' => 'tmu_dramas_episodes',
        'dramas_seasons' => 'tmu_dramas_seasons',
    ],
    
    'foreign_keys' => true,
    'create_indexes' => true,
    'backup_before_migration' => true,
];
```

## Testing and Verification

### Migration Testing
```php
<?php
/**
 * TMU Migration Tests
 *
 * @package TMU\Tests
 * @version 1.0.0
 */

namespace TMU\Tests;

use PHPUnit\Framework\TestCase;
use TMU\Database\Migration;
use TMU\Database\DataValidator;

/**
 * Migration Tests
 */
class MigrationTest extends TestCase {
    
    /**
     * Test migration execution
     */
    public function testMigrationExecution(): void {
        $migration = Migration::getInstance();
        
        // Test migration runs without errors
        $migration->runMigrations();
        
        // Verify tables were created
        $this->assertTrue($this->tableExists('tmu_movies'));
        $this->assertTrue($this->tableExists('tmu_people'));
        $this->assertTrue($this->tableExists('tmu_dramas'));
        $this->assertTrue($this->tableExists('tmu_tv_series'));
    }
    
    /**
     * Test data validation
     */
    public function testDataValidation(): void {
        $validation = DataValidator::validateExistingData();
        
        $this->assertIsArray($validation);
        $this->assertArrayHasKey('valid', $validation);
        $this->assertArrayHasKey('errors', $validation);
        $this->assertArrayHasKey('warnings', $validation);
        $this->assertArrayHasKey('data_count', $validation);
    }
    
    /**
     * Helper method to check if table exists
     */
    private function tableExists(string $table_name): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $table_name;
        $query = $wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
        
        return $wpdb->get_var($query) === $table_name;
    }
}
```

## Best Practices and Considerations

### 1. Data Safety
- Always backup existing data before migration
- Use transactions where possible
- Implement rollback functionality
- Validate data integrity after migration

### 2. Performance Optimization
- Create appropriate indexes
- Use batch processing for large datasets
- Monitor query performance
- Implement proper caching

### 3. Error Handling
- Log all migration activities
- Provide clear error messages
- Implement graceful failure recovery
- Monitor migration status

### 4. Version Control
- Track migration versions
- Maintain migration history
- Support incremental updates
- Handle version conflicts

## Next Steps

After completing this migration system:

1. **[Step 04: Autoloading and Namespace Setup](./04_autoloading-and-namespace-setup.md)** - Configure autoloading
2. **[Step 05: Post Types Registration](./05_post-types-registration.md)** - Register custom post types
3. **[Step 06: Taxonomies Registration](./06_taxonomies-registration.md)** - Register custom taxonomies

## Verification Checklist

- [ ] Migration class implemented
- [ ] Schema definitions created
- [ ] Migration files created
- [ ] Data validator implemented
- [ ] Admin interface created
- [ ] Testing framework set up
- [ ] Error handling implemented
- [ ] Logging system configured
- [ ] Performance optimizations applied
- [ ] Documentation completed

---

This migration system ensures seamless transition from the TMU plugin to the theme while preserving all existing data and maintaining database integrity.