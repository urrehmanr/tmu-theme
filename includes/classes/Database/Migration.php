<?php
/**
 * TMU Database Migration System
 *
 * @package TMU
 * @version 1.0.0
 */

namespace TMU\Database;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Migration Class
 * 
 * Handles database schema creation, updates, and data preservation
 * during theme activation and updates.
 */
class Migration {
    
    /**
     * Migration instance
     *
     * @var Migration
     */
    private static ?Migration $instance = null;
    
    /**
     * Database version option name
     */
    private const DB_VERSION_OPTION = 'tmu_db_version';
    
    /**
     * Current database version
     */
    private const CURRENT_DB_VERSION = '1.0.0';
    
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
        // Hook into WordPress initialization
        add_action('init', [$this, 'checkDatabaseVersion']);
    }
    
    /**
     * Initialize migration system
     */
    public function init(): void {
        // Check if migration is needed
        $this->checkDatabaseVersion();
    }
    
    /**
     * Check database version and run migrations if needed
     */
    public function checkDatabaseVersion(): void {
        $installed_version = get_option(self::DB_VERSION_OPTION, '0.0.0');
        
        if (version_compare($installed_version, self::CURRENT_DB_VERSION, '<')) {
            $this->runMigrations($installed_version);
        }
    }
    
    /**
     * Run database migrations
     *
     * @param string $from_version Starting version
     */
    public function runMigrations(string $from_version = '0.0.0'): void {
        global $wpdb;
        
        // Disable foreign key checks temporarily
        $wpdb->query('SET foreign_key_checks = 0');
        
        try {
            // Create all tables
            $this->createTables();
            
            // Add indexes for performance
            $this->addIndexes();
            
            // Migrate data if upgrading from plugin
            $this->migratePluginData();
            
            // Update database version
            update_option(self::DB_VERSION_OPTION, self::CURRENT_DB_VERSION);
            
            // Re-enable foreign key checks
            $wpdb->query('SET foreign_key_checks = 1');
            
            tmu_log("Database migration completed successfully from version {$from_version} to " . self::CURRENT_DB_VERSION);
            
        } catch (Exception $e) {
            // Re-enable foreign key checks on error
            $wpdb->query('SET foreign_key_checks = 1');
            
            tmu_log("Database migration failed: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Create database tables
     */
    private function createTables(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = [
            // Movies table
            $wpdb->prefix . 'tmu_movies' => "
                CREATE TABLE {$wpdb->prefix}tmu_movies (
                    ID bigint(20) UNSIGNED NOT NULL,
                    tmdb_id bigint(20) DEFAULT NULL,
                    release_date text DEFAULT NULL,
                    release_timestamp bigint(20) DEFAULT NULL,
                    original_title text DEFAULT NULL,
                    tagline text DEFAULT NULL,
                    production_house text DEFAULT NULL,
                    streaming_platforms text DEFAULT NULL,
                    runtime bigint(20) DEFAULT NULL,
                    certification text DEFAULT NULL,
                    revenue bigint(20) DEFAULT NULL,
                    budget bigint(20) DEFAULT NULL,
                    star_cast text DEFAULT NULL,
                    credits longtext DEFAULT NULL,
                    credits_temp longtext DEFAULT NULL,
                    videos text DEFAULT NULL,
                    images text DEFAULT NULL,
                    average_rating DECIMAL(10,1) DEFAULT 0,
                    vote_count bigint(20) DEFAULT 0,
                    popularity DECIMAL(10,1) DEFAULT 0,
                    total_average_rating DECIMAL(10,1) DEFAULT 0,
                    total_vote_count bigint(20) NOT NULL DEFAULT 0,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    FOREIGN KEY (ID) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION
                ) $charset_collate",
                
            // TV Series table
            $wpdb->prefix . 'tmu_tv_series' => "
                CREATE TABLE {$wpdb->prefix}tmu_tv_series (
                    ID bigint(20) UNSIGNED NOT NULL,
                    tmdb_id bigint(20) DEFAULT NULL,
                    release_date text DEFAULT NULL,
                    release_timestamp bigint(20) DEFAULT NULL,
                    original_title text DEFAULT NULL,
                    finished text DEFAULT NULL,
                    tagline text DEFAULT NULL,
                    production_house text DEFAULT NULL,
                    streaming_platforms text DEFAULT NULL,
                    schedule_time text DEFAULT NULL,
                    runtime bigint(20) DEFAULT NULL,
                    certification text DEFAULT NULL,
                    revenue bigint(20) DEFAULT NULL,
                    budget bigint(20) DEFAULT NULL,
                    star_cast text DEFAULT NULL,
                    credits longtext DEFAULT NULL,
                    credits_temp longtext DEFAULT NULL,
                    videos text DEFAULT NULL,
                    images text DEFAULT NULL,
                    seasons text DEFAULT NULL,
                    last_season bigint(20) DEFAULT NULL,
                    last_episode bigint(20) DEFAULT NULL,
                    average_rating DECIMAL(10,1) DEFAULT 0,
                    vote_count bigint(20) DEFAULT 0,
                    popularity DECIMAL(10,1) DEFAULT 0,
                    where_to_watch text DEFAULT NULL,
                    total_average_rating DECIMAL(10,1) DEFAULT 0,
                    total_vote_count bigint(20) NOT NULL DEFAULT 0,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    FOREIGN KEY (ID) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION
                ) $charset_collate",
                
            // Dramas table
            $wpdb->prefix . 'tmu_dramas' => "
                CREATE TABLE {$wpdb->prefix}tmu_dramas (
                    ID bigint(20) UNSIGNED NOT NULL,
                    tmdb_id bigint(20) DEFAULT NULL,
                    release_date text DEFAULT NULL,
                    release_timestamp bigint(20) DEFAULT NULL,
                    original_title text DEFAULT NULL,
                    finished text DEFAULT NULL,
                    tagline text DEFAULT NULL,
                    seo_genre BIGINT(20) NULL DEFAULT NULL,
                    production_house text DEFAULT NULL,
                    streaming_platforms text DEFAULT NULL,
                    schedule_day text DEFAULT NULL,
                    schedule_time text DEFAULT NULL,
                    schedule_timestamp bigint(20) DEFAULT NULL,
                    runtime bigint(20) DEFAULT NULL,
                    certification text DEFAULT NULL,
                    star_cast text DEFAULT NULL,
                    credits longtext DEFAULT NULL,
                    credits_temp longtext DEFAULT NULL,
                    videos text DEFAULT NULL,
                    images text DEFAULT NULL,
                    average_rating DECIMAL(10,1) DEFAULT 0,
                    vote_count bigint(20) DEFAULT 0,
                    popularity DECIMAL(10,1) DEFAULT 0,
                    where_to_watch text DEFAULT NULL,
                    total_average_rating DECIMAL(10,1) DEFAULT 0,
                    total_vote_count bigint(20) NOT NULL DEFAULT 0,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    FOREIGN KEY (ID) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION
                ) $charset_collate",
                
            // People table
            $wpdb->prefix . 'tmu_people' => "
                CREATE TABLE {$wpdb->prefix}tmu_people (
                    ID bigint(20) UNSIGNED NOT NULL,
                    name text DEFAULT NULL,
                    date_of_birth text DEFAULT NULL,
                    gender text DEFAULT NULL,
                    nick_name text DEFAULT NULL,
                    marital_status text DEFAULT NULL,
                    basic text DEFAULT NULL,
                    videos text DEFAULT NULL,
                    photos text DEFAULT NULL,
                    profession text DEFAULT NULL,
                    net_worth bigint(20) DEFAULT NULL,
                    tmdb_id bigint(20) DEFAULT NULL,
                    birthplace text DEFAULT NULL,
                    dead_on text DEFAULT NULL,
                    social_media_account text DEFAULT NULL,
                    no_movies bigint(20) DEFAULT NULL,
                    no_tv_series bigint(20) DEFAULT NULL,
                    no_dramas bigint(20) DEFAULT NULL,
                    known_for text DEFAULT NULL,
                    popularity DECIMAL(10,1) DEFAULT 0,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    FOREIGN KEY (ID) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION
                ) $charset_collate",
                
            // Videos table
            $wpdb->prefix . 'tmu_videos' => "
                CREATE TABLE {$wpdb->prefix}tmu_videos (
                    ID bigint(20) UNSIGNED NOT NULL,
                    video_data text DEFAULT NULL,
                    post_id bigint(20) UNSIGNED NOT NULL,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    FOREIGN KEY (ID) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION,
                    FOREIGN KEY (post_id) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION
                ) $charset_collate",
                
            // TV Series Seasons table
            $wpdb->prefix . 'tmu_tv_series_seasons' => "
                CREATE TABLE {$wpdb->prefix}tmu_tv_series_seasons (
                    ID bigint(20) UNSIGNED NOT NULL,
                    season_no bigint(20) DEFAULT NULL,
                    season_name text DEFAULT NULL,
                    tv_series bigint(20) UNSIGNED NOT NULL,
                    air_date text DEFAULT NULL,
                    air_date_timestamp bigint(20) DEFAULT NULL,
                    total_episodes bigint(20) DEFAULT NULL,
                    average_rating DECIMAL(10,1) NOT NULL DEFAULT 0,
                    total_average_rating DECIMAL(10,1) DEFAULT 0,
                    total_vote_count bigint(20) NOT NULL DEFAULT 0,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    FOREIGN KEY (ID) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION,
                    FOREIGN KEY (tv_series) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION
                ) $charset_collate",
                
            // TV Series Episodes table
            $wpdb->prefix . 'tmu_tv_series_episodes' => "
                CREATE TABLE {$wpdb->prefix}tmu_tv_series_episodes (
                    ID bigint(20) UNSIGNED NOT NULL,
                    tv_series bigint(20) UNSIGNED NOT NULL,
                    season_no bigint(20) DEFAULT NULL,
                    episode_no bigint(20) DEFAULT NULL,
                    episode_title text DEFAULT NULL,
                    air_date text DEFAULT NULL,
                    air_date_timestamp bigint(20) DEFAULT NULL,
                    episode_type text DEFAULT NULL,
                    runtime bigint(20) DEFAULT NULL,
                    overview text DEFAULT NULL,
                    credits text DEFAULT NULL,
                    average_rating DECIMAL(10,1) NOT NULL DEFAULT 0,
                    vote_count bigint(20) NOT NULL DEFAULT 0,
                    season_id bigint(20) UNSIGNED NOT NULL,
                    total_average_rating DECIMAL(10,1) DEFAULT 0,
                    total_vote_count bigint(20) NOT NULL DEFAULT 0,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    FOREIGN KEY (ID) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION,
                    FOREIGN KEY (tv_series) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION,
                    FOREIGN KEY (season_id) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION
                ) $charset_collate",
                
            // Drama Episodes table
            $wpdb->prefix . 'tmu_dramas_episodes' => "
                CREATE TABLE {$wpdb->prefix}tmu_dramas_episodes (
                    ID bigint(20) UNSIGNED NOT NULL,
                    episode_title text DEFAULT NULL,
                    air_date text DEFAULT NULL,
                    episode_type text DEFAULT NULL,
                    runtime bigint(20) DEFAULT NULL,
                    overview text DEFAULT NULL,
                    credits text DEFAULT NULL,
                    episode_no bigint(20) DEFAULT NULL,
                    dramas bigint(20) UNSIGNED NOT NULL,
                    average_rating DECIMAL(10,1) NOT NULL DEFAULT 0,
                    vote_count bigint(20) NOT NULL DEFAULT 0,
                    video text DEFAULT NULL,
                    total_average_rating DECIMAL(10,1) DEFAULT 0,
                    total_vote_count bigint(20) NOT NULL DEFAULT 0,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    FOREIGN KEY (ID) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION,
                    FOREIGN KEY (dramas) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION
                ) $charset_collate",
                
            // SEO Options table
            $wpdb->prefix . 'tmu_seo_options' => "
                CREATE TABLE {$wpdb->prefix}tmu_seo_options (
                    ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    name text DEFAULT NULL,
                    title text DEFAULT NULL,
                    description text DEFAULT NULL,
                    keywords text DEFAULT NULL,
                    robots text DEFAULT NULL,
                    post_type text DEFAULT NULL,
                    section text DEFAULT NULL,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID)
                ) $charset_collate"
        ];
        
        // Relationship tables
        $relationship_tables = [
            // Movie cast relationships
            $wpdb->prefix . 'tmu_movies_cast' => "
                CREATE TABLE {$wpdb->prefix}tmu_movies_cast (
                    ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    movie bigint(20) UNSIGNED NOT NULL,
                    person bigint(20) UNSIGNED NOT NULL,
                    job varchar(255) DEFAULT NULL,
                    release_year bigint(20) DEFAULT NULL,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    FOREIGN KEY (movie) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION,
                    FOREIGN KEY (person) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION
                ) $charset_collate",
                
            // Movie crew relationships
            $wpdb->prefix . 'tmu_movies_crew' => "
                CREATE TABLE {$wpdb->prefix}tmu_movies_crew (
                    ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    movie bigint(20) UNSIGNED NOT NULL,
                    person bigint(20) UNSIGNED NOT NULL,
                    department varchar(255) DEFAULT NULL,
                    job varchar(255) DEFAULT NULL,
                    release_year bigint(20) DEFAULT NULL,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    FOREIGN KEY (movie) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION,
                    FOREIGN KEY (person) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION
                ) $charset_collate",
                
            // TV Series cast relationships
            $wpdb->prefix . 'tmu_tv_series_cast' => "
                CREATE TABLE {$wpdb->prefix}tmu_tv_series_cast (
                    ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    tv_series bigint(20) UNSIGNED NOT NULL,
                    person bigint(20) UNSIGNED NOT NULL,
                    job varchar(255) DEFAULT NULL,
                    release_year bigint(20) DEFAULT NULL,
                    count bigint(20) DEFAULT NULL,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    FOREIGN KEY (tv_series) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION,
                    FOREIGN KEY (person) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION
                ) $charset_collate",
                
            // TV Series crew relationships
            $wpdb->prefix . 'tmu_tv_series_crew' => "
                CREATE TABLE {$wpdb->prefix}tmu_tv_series_crew (
                    ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    tv_series bigint(20) UNSIGNED NOT NULL,
                    person bigint(20) UNSIGNED NOT NULL,
                    department varchar(255) DEFAULT NULL,
                    job varchar(255) DEFAULT NULL,
                    release_year bigint(20) DEFAULT NULL,
                    count bigint(20) DEFAULT NULL,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    FOREIGN KEY (tv_series) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION,
                    FOREIGN KEY (person) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION
                ) $charset_collate",
                
            // Drama cast relationships
            $wpdb->prefix . 'tmu_dramas_cast' => "
                CREATE TABLE {$wpdb->prefix}tmu_dramas_cast (
                    ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    dramas bigint(20) UNSIGNED NOT NULL,
                    person bigint(20) UNSIGNED NOT NULL,
                    job varchar(255) DEFAULT NULL,
                    release_year bigint(20) DEFAULT NULL,
                    count bigint(20) DEFAULT NULL,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    FOREIGN KEY (dramas) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION,
                    FOREIGN KEY (person) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION
                ) $charset_collate",
                
            // Drama crew relationships
            $wpdb->prefix . 'tmu_dramas_crew' => "
                CREATE TABLE {$wpdb->prefix}tmu_dramas_crew (
                    ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    dramas bigint(20) UNSIGNED NOT NULL,
                    person bigint(20) UNSIGNED NOT NULL,
                    department varchar(255) DEFAULT NULL,
                    job varchar(255) DEFAULT NULL,
                    release_year bigint(20) DEFAULT NULL,
                    count bigint(20) DEFAULT NULL,
                    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    FOREIGN KEY (dramas) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION,
                    FOREIGN KEY (person) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE ON UPDATE NO ACTION
                ) $charset_collate"
        ];
        
        // Merge all tables
        $all_tables = array_merge($tables, $relationship_tables);
        
        // Create tables
        foreach ($all_tables as $table_name => $sql) {
            $existing = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
            if (!$existing) {
                $result = $wpdb->query($sql);
                if ($result === false) {
                    throw new Exception("Failed to create table: {$table_name}");
                }
                tmu_log("Created table: {$table_name}");
            }
        }
        
        // Add custom columns to wp_comments table
        $this->addCommentRatingColumns();
        
        // Add custom columns to wp_posts table
        $this->addPostSeoColumns();
    }
    
    /**
     * Add rating columns to comments table
     */
    private function addCommentRatingColumns(): void {
        global $wpdb;
        
        $table_name = $wpdb->comments;
        
        // Add comment_rating column
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$table_name} LIKE 'comment_rating'");
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE {$table_name} ADD comment_rating INT(11) NOT NULL DEFAULT 0");
            tmu_log("Added comment_rating column to {$table_name}");
        }
        
        // Add parent_post_id column
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$table_name} LIKE 'parent_post_id'");
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE {$table_name} ADD parent_post_id INT(11) DEFAULT NULL");
            tmu_log("Added parent_post_id column to {$table_name}");
        }
    }
    
    /**
     * Add SEO columns to posts table
     */
    private function addPostSeoColumns(): void {
        global $wpdb;
        
        $table_name = $wpdb->posts;
        
        // Add seo_title column
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$table_name} LIKE 'seo_title'");
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE {$table_name} ADD seo_title TEXT NULL DEFAULT NULL");
            tmu_log("Added seo_title column to {$table_name}");
        }
        
        // Add seo_description column
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$table_name} LIKE 'seo_description'");
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE {$table_name} ADD seo_description TEXT NULL DEFAULT NULL");
            tmu_log("Added seo_description column to {$table_name}");
        }
        
        // Add meta_keywords column
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$table_name} LIKE 'meta_keywords'");
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE {$table_name} ADD meta_keywords TEXT NULL DEFAULT NULL");
            tmu_log("Added meta_keywords column to {$table_name}");
        }
    }
    
    /**
     * Add database indexes for performance
     */
    private function addIndexes(): void {
        global $wpdb;
        
        $indexes = [
            // Movie indexes
            "CREATE INDEX idx_tmu_movies_tmdb_id ON {$wpdb->prefix}tmu_movies (tmdb_id)",
            "CREATE INDEX idx_tmu_movies_release_date ON {$wpdb->prefix}tmu_movies (release_date(50))",
            "CREATE INDEX idx_tmu_movies_rating ON {$wpdb->prefix}tmu_movies (total_average_rating)",
            "CREATE INDEX idx_tmu_movies_popularity ON {$wpdb->prefix}tmu_movies (popularity)",
            
            // TV Series indexes
            "CREATE INDEX idx_tmu_tv_series_tmdb_id ON {$wpdb->prefix}tmu_tv_series (tmdb_id)",
            "CREATE INDEX idx_tmu_tv_series_release_date ON {$wpdb->prefix}tmu_tv_series (release_date(50))",
            "CREATE INDEX idx_tmu_tv_series_rating ON {$wpdb->prefix}tmu_tv_series (total_average_rating)",
            
            // Drama indexes
            "CREATE INDEX idx_tmu_dramas_tmdb_id ON {$wpdb->prefix}tmu_dramas (tmdb_id)",
            "CREATE INDEX idx_tmu_dramas_release_date ON {$wpdb->prefix}tmu_dramas (release_date(50))",
            "CREATE INDEX idx_tmu_dramas_rating ON {$wpdb->prefix}tmu_dramas (total_average_rating)",
            
            // People indexes
            "CREATE INDEX idx_tmu_people_tmdb_id ON {$wpdb->prefix}tmu_people (tmdb_id)",
            "CREATE INDEX idx_tmu_people_popularity ON {$wpdb->prefix}tmu_people (popularity)",
            
            // Cast/Crew relationship indexes
            "CREATE INDEX idx_movie_cast_movie ON {$wpdb->prefix}tmu_movies_cast (movie)",
            "CREATE INDEX idx_movie_cast_person ON {$wpdb->prefix}tmu_movies_cast (person)",
            "CREATE INDEX idx_movie_crew_movie ON {$wpdb->prefix}tmu_movies_crew (movie)",
            "CREATE INDEX idx_movie_crew_person ON {$wpdb->prefix}tmu_movies_crew (person)",
            
            "CREATE INDEX idx_tv_cast_series ON {$wpdb->prefix}tmu_tv_series_cast (tv_series)",
            "CREATE INDEX idx_tv_cast_person ON {$wpdb->prefix}tmu_tv_series_cast (person)",
            "CREATE INDEX idx_tv_crew_series ON {$wpdb->prefix}tmu_tv_series_crew (tv_series)",
            "CREATE INDEX idx_tv_crew_person ON {$wpdb->prefix}tmu_tv_series_crew (person)",
            
            "CREATE INDEX idx_drama_cast_drama ON {$wpdb->prefix}tmu_dramas_cast (dramas)",
            "CREATE INDEX idx_drama_cast_person ON {$wpdb->prefix}tmu_dramas_cast (person)",
            "CREATE INDEX idx_drama_crew_drama ON {$wpdb->prefix}tmu_dramas_crew (dramas)",
            "CREATE INDEX idx_drama_crew_person ON {$wpdb->prefix}tmu_dramas_crew (person)",
            
            // Episode indexes
            "CREATE INDEX idx_tv_episodes_series ON {$wpdb->prefix}tmu_tv_series_episodes (tv_series)",
            "CREATE INDEX idx_tv_episodes_season ON {$wpdb->prefix}tmu_tv_series_episodes (season_id)",
            "CREATE INDEX idx_tv_episodes_air_date ON {$wpdb->prefix}tmu_tv_series_episodes (air_date_timestamp)",
            
            "CREATE INDEX idx_drama_episodes_drama ON {$wpdb->prefix}tmu_dramas_episodes (dramas)",
            "CREATE INDEX idx_drama_episodes_air_date ON {$wpdb->prefix}tmu_dramas_episodes (air_date(50))",
            
            // Season indexes
            "CREATE INDEX idx_tv_seasons_series ON {$wpdb->prefix}tmu_tv_series_seasons (tv_series)",
            "CREATE INDEX idx_tv_seasons_air_date ON {$wpdb->prefix}tmu_tv_series_seasons (air_date_timestamp)",
            
            // Video indexes
            "CREATE INDEX idx_videos_post_id ON {$wpdb->prefix}tmu_videos (post_id)"
        ];
        
        foreach ($indexes as $index) {
            // Check if index exists before creating
            $index_name = $this->extractIndexName($index);
            if (!$this->indexExists($index_name)) {
                $result = $wpdb->query($index);
                if ($result !== false) {
                    tmu_log("Created index: {$index_name}");
                }
            }
        }
    }
    
    /**
     * Extract index name from CREATE INDEX statement
     */
    private function extractIndexName(string $sql): string {
        if (preg_match('/CREATE INDEX (\w+) ON/', $sql, $matches)) {
            return $matches[1];
        }
        return '';
    }
    
    /**
     * Check if index exists
     */
    private function indexExists(string $index_name): bool {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
             WHERE TABLE_SCHEMA = %s AND INDEX_NAME = %s",
            DB_NAME,
            $index_name
        ));
        
        return $result > 0;
    }
    
    /**
     * Migrate existing plugin data
     */
    private function migratePluginData(): void {
        // Check if we're migrating from the plugin
        if (!$this->isPluginDataPresent()) {
            return;
        }
        
        tmu_log("Starting plugin data migration");
        
        // Migration is typically not needed as we're using the same table structure
        // But we can add any data transformation logic here if needed
        
        tmu_log("Plugin data migration completed");
    }
    
    /**
     * Check if plugin data is present
     */
    private function isPluginDataPresent(): bool {
        global $wpdb;
        
        // Check if plugin tables exist with data
        $tables_to_check = [
            $wpdb->prefix . 'tmu_movies',
            $wpdb->prefix . 'tmu_tv_series',
            $wpdb->prefix . 'tmu_dramas',
            $wpdb->prefix . 'tmu_people'
        ];
        
        foreach ($tables_to_check as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if ($exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                if ($count > 0) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Backup database before migration
     */
    public function backupDatabase(): bool {
        // This would implement database backup logic
        // For now, we'll just log that backup should be done manually
        tmu_log("Database backup recommended before migration", 'warning');
        return true;
    }
    
    /**
     * Rollback migration
     */
    public function rollbackMigration(): bool {
        // This would implement rollback logic
        tmu_log("Migration rollback initiated", 'warning');
        
        // Reset database version
        update_option(self::DB_VERSION_OPTION, '0.0.0');
        
        return true;
    }
    
    /**
     * Get current database version
     */
    public function getCurrentVersion(): string {
        return get_option(self::DB_VERSION_OPTION, '0.0.0');
    }
    
    /**
     * Check if migration is needed
     */
    public function isMigrationNeeded(): bool {
        $current = $this->getCurrentVersion();
        return version_compare($current, self::CURRENT_DB_VERSION, '<');
    }
}