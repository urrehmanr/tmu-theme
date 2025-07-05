# Step 3: Database Alignment

## 🎯 Goal
Establish database compatibility with the existing TMU plugin while implementing a modern database management system for the theme.

## 📋 What We'll Accomplish
- Analyze existing plugin database schema
- Create DatabaseManager class
- Implement table creation and migration system
- Ensure data compatibility and preservation
- Set up database abstraction layer

---

## 🔍 Existing Database Schema Analysis

Based on the plugin analysis, we have these existing tables:
- `wp_tmu_movies` - Movie data
- `wp_tmu_tv_series` - TV series data  
- `wp_tmu_dramas` - Drama data
- `wp_tmu_people` - Person/celebrity data
- `wp_tmu_*_cast` - Cast relationship tables
- `wp_tmu_*_crew` - Crew relationship tables
- `wp_tmu_*_episodes` - Episode data
- `wp_tmu_*_seasons` - Season data
- `wp_tmu_videos` - Video data
- `wp_tmu_seo_options` - SEO settings

---

## 🏗️ Database Manager Implementation

### Create `src/Database/DatabaseManager.php`

```php
<?php
/**
 * Database Manager
 *
 * @package TMUTheme\Database
 * @since 1.0.0
 */

namespace TMUTheme\Database;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * DatabaseManager class
 * 
 * Handles all database operations and table management
 */
class DatabaseManager {
    
    /**
     * Database version
     */
    const DB_VERSION = '1.0.0';
    
    /**
     * Table prefix
     */
    private $table_prefix;
    
    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    private $wpdb;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix . 'tmu_';
        
        add_action( 'init', [ $this, 'maybe_create_tables' ] );
    }
    
    /**
     * Check if tables need to be created or updated
     *
     * @return void
     */
    public function maybe_create_tables(): void {
        $installed_version = get_option( 'tmu_db_version', '0.0.0' );
        
        if ( version_compare( $installed_version, self::DB_VERSION, '<' ) ) {
            $this->create_tables();
            update_option( 'tmu_db_version', self::DB_VERSION );
        }
    }
    
    /**
     * Create all custom tables
     *
     * @return void
     */
    public function create_tables(): void {
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        $charset_collate = $this->wpdb->get_charset_collate();
        
        // Create each table
        $this->create_movies_table( $charset_collate );
        $this->create_tv_series_table( $charset_collate );
        $this->create_dramas_table( $charset_collate );
        $this->create_people_table( $charset_collate );
        $this->create_relationship_tables( $charset_collate );
        $this->create_episodes_tables( $charset_collate );
        $this->create_videos_table( $charset_collate );
        $this->create_seo_table( $charset_collate );
        
        // Add comment rating column if not exists
        $this->maybe_add_comment_columns();
        
        // Add post SEO columns if not exists
        $this->maybe_add_post_columns();
    }
    
    /**
     * Create movies table
     *
     * @param string $charset_collate Database charset collation.
     * @return void
     */
    private function create_movies_table( string $charset_collate ): void {
        $table_name = $this->table_prefix . 'movies';
        
        $sql = "CREATE TABLE {$table_name} (
            ID bigint(20) UNSIGNED NOT NULL,
            tmdb_id bigint(20) DEFAULT NULL,
            release_date date DEFAULT NULL,
            release_timestamp bigint(20) DEFAULT NULL,
            original_title varchar(255) DEFAULT NULL,
            tagline text DEFAULT NULL,
            runtime int(11) DEFAULT NULL,
            certification varchar(50) DEFAULT NULL,
            budget bigint(20) DEFAULT NULL,
            revenue bigint(20) DEFAULT NULL,
            production_house text DEFAULT NULL,
            streaming_platforms text DEFAULT NULL,
            star_cast longtext DEFAULT NULL,
            credits longtext DEFAULT NULL,
            credits_temp longtext DEFAULT NULL,
            videos text DEFAULT NULL,
            images text DEFAULT NULL,
            average_rating decimal(3,1) DEFAULT 0.0,
            vote_count int(11) DEFAULT 0,
            popularity decimal(10,3) DEFAULT 0.000,
            total_average_rating decimal(3,1) DEFAULT 0.0,
            total_vote_count int(11) DEFAULT 0,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (ID),
            UNIQUE KEY tmdb_id (tmdb_id),
            KEY release_date (release_date),
            KEY popularity (popularity),
            FOREIGN KEY (ID) REFERENCES {$this->wpdb->prefix}posts(ID) ON DELETE CASCADE
        ) {$charset_collate};";
        
        dbDelta( $sql );
    }
    
    /**
     * Create TV series table
     *
     * @param string $charset_collate Database charset collation.
     * @return void
     */
    private function create_tv_series_table( string $charset_collate ): void {
        $table_name = $this->table_prefix . 'tv_series';
        
        $sql = "CREATE TABLE {$table_name} (
            ID bigint(20) UNSIGNED NOT NULL,
            tmdb_id bigint(20) DEFAULT NULL,
            release_date date DEFAULT NULL,
            release_timestamp bigint(20) DEFAULT NULL,
            original_title varchar(255) DEFAULT NULL,
            tagline text DEFAULT NULL,
            status varchar(50) DEFAULT NULL,
            runtime int(11) DEFAULT NULL,
            certification varchar(50) DEFAULT NULL,
            production_house text DEFAULT NULL,
            streaming_platforms text DEFAULT NULL,
            star_cast longtext DEFAULT NULL,
            credits longtext DEFAULT NULL,
            credits_temp longtext DEFAULT NULL,
            videos text DEFAULT NULL,
            images text DEFAULT NULL,
            seasons text DEFAULT NULL,
            last_season int(11) DEFAULT NULL,
            last_episode int(11) DEFAULT NULL,
            average_rating decimal(3,1) DEFAULT 0.0,
            vote_count int(11) DEFAULT 0,
            popularity decimal(10,3) DEFAULT 0.000,
            where_to_watch text DEFAULT NULL,
            total_average_rating decimal(3,1) DEFAULT 0.0,
            total_vote_count int(11) DEFAULT 0,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (ID),
            UNIQUE KEY tmdb_id (tmdb_id),
            KEY release_date (release_date),
            KEY status (status),
            FOREIGN KEY (ID) REFERENCES {$this->wpdb->prefix}posts(ID) ON DELETE CASCADE
        ) {$charset_collate};";
        
        dbDelta( $sql );
    }
    
    /**
     * Create people table
     *
     * @param string $charset_collate Database charset collation.
     * @return void
     */
    private function create_people_table( string $charset_collate ): void {
        $table_name = $this->table_prefix . 'people';
        
        $sql = "CREATE TABLE {$table_name} (
            ID bigint(20) UNSIGNED NOT NULL,
            tmdb_id bigint(20) DEFAULT NULL,
            name varchar(255) DEFAULT NULL,
            date_of_birth date DEFAULT NULL,
            date_of_death date DEFAULT NULL,
            gender varchar(20) DEFAULT NULL,
            birthplace text DEFAULT NULL,
            biography longtext DEFAULT NULL,
            profession varchar(255) DEFAULT NULL,
            known_for longtext DEFAULT NULL,
            social_media_account text DEFAULT NULL,
            photos text DEFAULT NULL,
            no_movies int(11) DEFAULT 0,
            no_tv_series int(11) DEFAULT 0,
            no_dramas int(11) DEFAULT 0,
            popularity decimal(10,3) DEFAULT 0.000,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (ID),
            UNIQUE KEY tmdb_id (tmdb_id),
            KEY name (name),
            KEY popularity (popularity),
            FOREIGN KEY (ID) REFERENCES {$this->wpdb->prefix}posts(ID) ON DELETE CASCADE
        ) {$charset_collate};";
        
        dbDelta( $sql );
    }
    
    /**
     * Create relationship tables (cast/crew)
     *
     * @param string $charset_collate Database charset collation.
     * @return void
     */
    private function create_relationship_tables( string $charset_collate ): void {
        $content_types = [ 'movies', 'tv_series', 'dramas' ];
        
        foreach ( $content_types as $type ) {
            // Cast tables
            $cast_table = $this->table_prefix . $type . '_cast';
            $sql = "CREATE TABLE {$cast_table} (
                ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                {$type} bigint(20) UNSIGNED NOT NULL,
                person bigint(20) UNSIGNED NOT NULL,
                job varchar(255) DEFAULT NULL,
                release_year bigint(20) DEFAULT NULL,
                order_index int(11) DEFAULT 0,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (ID),
                KEY {$type} ({$type}),
                KEY person (person),
                KEY release_year (release_year),
                FOREIGN KEY ({$type}) REFERENCES {$this->wpdb->prefix}posts(ID) ON DELETE CASCADE,
                FOREIGN KEY (person) REFERENCES {$this->wpdb->prefix}posts(ID) ON DELETE CASCADE
            ) {$charset_collate};";
            dbDelta( $sql );
            
            // Crew tables
            $crew_table = $this->table_prefix . $type . '_crew';
            $sql = "CREATE TABLE {$crew_table} (
                ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                {$type} bigint(20) UNSIGNED NOT NULL,
                person bigint(20) UNSIGNED NOT NULL,
                department varchar(100) DEFAULT NULL,
                job varchar(255) DEFAULT NULL,
                release_year bigint(20) DEFAULT NULL,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (ID),
                KEY {$type} ({$type}),
                KEY person (person),
                KEY department (department),
                FOREIGN KEY ({$type}) REFERENCES {$this->wpdb->prefix}posts(ID) ON DELETE CASCADE,
                FOREIGN KEY (person) REFERENCES {$this->wpdb->prefix}posts(ID) ON DELETE CASCADE
            ) {$charset_collate};";
            dbDelta( $sql );
        }
    }
    
    /**
     * Maybe add comment rating columns
     *
     * @return void
     */
    private function maybe_add_comment_columns(): void {
        $table_name = $this->wpdb->prefix . 'comments';
        
        // Check and add comment_rating column
        $result = $this->wpdb->get_var( 
            "SHOW COLUMNS FROM {$table_name} LIKE 'comment_rating'" 
        );
        if ( ! $result ) {
            $this->wpdb->query( 
                "ALTER TABLE {$table_name} ADD comment_rating INT(11) NOT NULL DEFAULT 0" 
            );
        }
        
        // Check and add parent_post_id column
        $result = $this->wpdb->get_var( 
            "SHOW COLUMNS FROM {$table_name} LIKE 'parent_post_id'" 
        );
        if ( ! $result ) {
            $this->wpdb->query( 
                "ALTER TABLE {$table_name} ADD parent_post_id INT(11) DEFAULT NULL" 
            );
        }
    }
    
    /**
     * Maybe add post SEO columns
     *
     * @return void
     */
    private function maybe_add_post_columns(): void {
        $table_name = $this->wpdb->prefix . 'posts';
        
        $columns = [
            'seo_title' => 'TEXT NULL DEFAULT NULL',
            'seo_description' => 'TEXT NULL DEFAULT NULL',
            'meta_keywords' => 'TEXT NULL DEFAULT NULL'
        ];
        
        foreach ( $columns as $column => $definition ) {
            $result = $this->wpdb->get_var( 
                "SHOW COLUMNS FROM {$table_name} LIKE '{$column}'" 
            );
            if ( ! $result ) {
                $this->wpdb->query( 
                    "ALTER TABLE {$table_name} ADD {$column} {$definition}" 
                );
            }
        }
    }
    
    /**
     * Get table name
     *
     * @param string $table Table suffix.
     * @return string
     */
    public function get_table_name( string $table ): string {
        return $this->table_prefix . $table;
    }
    
    /**
     * Check if table exists
     *
     * @param string $table Table name.
     * @return bool
     */
    public function table_exists( string $table ): bool {
        $table_name = $this->get_table_name( $table );
        $result = $this->wpdb->get_var( 
            $this->wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) 
        );
        return $result === $table_name;
    }
    
    /**
     * Get custom data for post
     *
     * @param int    $post_id Post ID.
     * @param string $table   Table suffix.
     * @return object|null
     */
    public function get_post_data( int $post_id, string $table ) {
        $table_name = $this->get_table_name( $table );
        
        if ( ! $this->table_exists( $table ) ) {
            return null;
        }
        
        return $this->wpdb->get_row( 
            $this->wpdb->prepare( 
                "SELECT * FROM {$table_name} WHERE ID = %d", 
                $post_id 
            ) 
        );
    }
    
    /**
     * Insert or update post data
     *
     * @param int    $post_id Post ID.
     * @param string $table   Table suffix.
     * @param array  $data    Data to insert/update.
     * @return bool
     */
    public function save_post_data( int $post_id, string $table, array $data ): bool {
        $table_name = $this->get_table_name( $table );
        
        if ( ! $this->table_exists( $table ) ) {
            return false;
        }
        
        // Add ID to data
        $data['ID'] = $post_id;
        
        // Check if record exists
        $exists = $this->wpdb->get_var( 
            $this->wpdb->prepare( 
                "SELECT ID FROM {$table_name} WHERE ID = %d", 
                $post_id 
            ) 
        );
        
        if ( $exists ) {
            // Update existing record
            $result = $this->wpdb->update( 
                $table_name, 
                $data, 
                [ 'ID' => $post_id ] 
            );
        } else {
            // Insert new record
            $result = $this->wpdb->insert( $table_name, $data );
        }
        
        return $result !== false;
    }
    
    /**
     * Delete post data
     *
     * @param int    $post_id Post ID.
     * @param string $table   Table suffix.
     * @return bool
     */
    public function delete_post_data( int $post_id, string $table ): bool {
        $table_name = $this->get_table_name( $table );
        
        if ( ! $this->table_exists( $table ) ) {
            return false;
        }
        
        $result = $this->wpdb->delete( 
            $table_name, 
            [ 'ID' => $post_id ], 
            [ '%d' ] 
        );
        
        return $result !== false;
    }
}
```

---

## 🔄 Data Migration Utility

### Create `src/Database/Migration.php`

```php
<?php
/**
 * Migration Utility
 *
 * @package TMUTheme\Database
 * @since 1.0.0
 */

namespace TMUTheme\Database;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Migration class
 * 
 * Handles data migration from plugin to theme
 */
class Migration {
    
    /**
     * Database manager instance
     *
     * @var DatabaseManager
     */
    private $db_manager;
    
    /**
     * Constructor
     *
     * @param DatabaseManager $db_manager Database manager instance.
     */
    public function __construct( DatabaseManager $db_manager ) {
        $this->db_manager = $db_manager;
    }
    
    /**
     * Check if plugin data exists
     *
     * @return bool
     */
    public function has_plugin_data(): bool {
        global $wpdb;
        
        $tables = [
            'tmu_movies',
            'tmu_tv_series', 
            'tmu_dramas',
            'tmu_people'
        ];
        
        foreach ( $tables as $table ) {
            $table_name = $wpdb->prefix . $table;
            $result = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
            if ( $result ) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Migrate plugin data to theme tables
     *
     * @return array Migration results.
     */
    public function migrate_data(): array {
        $results = [
            'success' => false,
            'migrated' => 0,
            'errors' => []
        ];
        
        if ( ! $this->has_plugin_data() ) {
            $results['errors'][] = 'No plugin data found to migrate.';
            return $results;
        }
        
        try {
            // Note: In most cases, we'll reuse existing plugin tables
            // This migration is mainly for schema updates if needed
            $results['success'] = true;
            $results['migrated'] = $this->count_existing_data();
            
        } catch ( \Exception $e ) {
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Count existing data
     *
     * @return int
     */
    private function count_existing_data(): int {
        global $wpdb;
        
        $count = 0;
        $tables = [ 'tmu_movies', 'tmu_tv_series', 'tmu_dramas', 'tmu_people' ];
        
        foreach ( $tables as $table ) {
            $table_name = $wpdb->prefix . $table;
            $result = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
            if ( $result ) {
                $table_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
                $count += (int) $table_count;
            }
        }
        
        return $count;
    }
}
```

---

## ✅ Verification Checklist

After completing this step, verify:

- [ ] DatabaseManager class is created and functional
- [ ] Tables are created successfully
- [ ] Existing plugin data is preserved
- [ ] Foreign key relationships work correctly
- [ ] Migration utility is implemented
- [ ] No database errors in logs

---

## 🔍 Testing Database Setup

```php
// Test in functions.php or a test file
$theme = \TMUTheme\Theme::get_instance();
$db_manager = $theme->get_component('database');

// Test table creation
var_dump($db_manager->table_exists('movies')); // Should return true

// Test data operations
$test_data = [
    'tmdb_id' => 12345,
    'original_title' => 'Test Movie',
    'release_date' => '2024-01-01'
];

$result = $db_manager->save_post_data(1, 'movies', $test_data);
var_dump($result); // Should return true
```

---

## 🎯 Next Step

Once database alignment is complete, proceed to **[Step 4: Custom Post Types](04_custom-post-types.md)** to implement the post type registration system.

---

*Estimated time for this step: 30-45 minutes*