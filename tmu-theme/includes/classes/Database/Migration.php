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
        $migration_dir = get_template_directory() . '/includes/migrations/';
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
    
    /**
     * Force run all migrations
     */
    public function forceRunMigrations(): void {
        $this->executeMigrations('0.0.0');
        update_option($this->version_option, $this->current_version);
    }
    
    /**
     * Reset migration status
     */
    public function resetMigrationStatus(): void {
        delete_option($this->version_option);
    }
    
    /**
     * Get migration status
     *
     * @return array
     */
    public function getMigrationStatus(): array {
        $installed_version = get_option($this->version_option, '0.0.0');
        $needs_migration = version_compare($installed_version, $this->current_version, '<');
        
        return [
            'installed_version' => $installed_version,
            'current_version' => $this->current_version,
            'needs_migration' => $needs_migration,
            'migration_files' => $this->getMigrationFiles()
        ];
    }
}