<?php
/**
 * Migration Manager
 * 
 * Data migration management system for TMU theme updates.
 * 
 * @package TMU\Migration
 * @since 1.0.0
 */

namespace TMU\Migration;

use TMU\Logging\LogManager;

class MigrationManager {
    
    /**
     * Logger instance
     * @var LogManager
     */
    private $logger;
    
    /**
     * Migration directory
     * @var string
     */
    private $migration_dir;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new LogManager();
        $this->migration_dir = get_template_directory() . '/migrations/';
        
        add_action('tmu_run_migration', [$this, 'run_migration']);
        add_action('wp_ajax_tmu_run_migration', [$this, 'run_migration_ajax']);
        add_action('init', [$this, 'check_pending_migrations']);
    }
    
    /**
     * Check for pending migrations
     */
    public function check_pending_migrations(): void {
        $pending_migrations = $this->get_pending_migrations();
        
        if (!empty($pending_migrations)) {
            $this->logger->info('Pending migrations found', ['count' => count($pending_migrations)]);
            
            // Auto-run safe migrations
            foreach ($pending_migrations as $migration) {
                if ($this->is_safe_migration($migration)) {
                    $this->run_migration($migration);
                }
            }
        }
    }
    
    /**
     * Get pending migrations
     */
    public function get_pending_migrations(): array {
        $all_migrations = $this->get_all_migrations();
        $completed_migrations = get_option('tmu_completed_migrations', []);
        
        return array_diff($all_migrations, $completed_migrations);
    }
    
    /**
     * Get all available migrations
     */
    public function get_all_migrations(): array {
        $migrations = [];
        
        if (!is_dir($this->migration_dir)) {
            return $migrations;
        }
        
        $files = glob($this->migration_dir . '*.php');
        
        foreach ($files as $file) {
            $migration_name = basename($file, '.php');
            if (preg_match('/^\d{3}_/', $migration_name)) {
                $migrations[] = $migration_name;
            }
        }
        
        sort($migrations);
        
        return $migrations;
    }
    
    /**
     * Run a specific migration
     */
    public function run_migration($migration_name): bool {
        $this->logger->info('Starting migration', ['migration' => $migration_name]);
        
        try {
            $migration_file = $this->migration_dir . $migration_name . '.php';
            
            if (!file_exists($migration_file)) {
                throw new \Exception("Migration file not found: {$migration_name}");
            }
            
            // Create backup before migration
            $this->create_migration_backup($migration_name);
            
            // Include and run migration
            $migration_result = $this->execute_migration_file($migration_file);
            
            if ($migration_result !== false) {
                // Mark migration as completed
                $this->mark_migration_completed($migration_name);
                
                $this->logger->info('Migration completed successfully', ['migration' => $migration_name]);
                return true;
            } else {
                throw new \Exception("Migration execution failed: {$migration_name}");
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Migration failed', [
                'migration' => $migration_name,
                'error' => $e->getMessage()
            ]);
            
            // Attempt rollback
            $this->rollback_migration($migration_name);
            
            return false;
        }
    }
    
    /**
     * Execute migration file
     */
    private function execute_migration_file($migration_file): bool {
        global $wpdb;
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Include migration file
            $migration_result = include $migration_file;
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            return $migration_result !== false;
            
        } catch (\Exception $e) {
            // Rollback transaction
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }
    
    /**
     * Check if migration is safe to auto-run
     */
    private function is_safe_migration($migration_name): bool {
        // Safe migrations are those that don't modify existing data structure
        $safe_patterns = [
            '/^001_/', // Initial setup migrations
            '/^.*_add_index_/', // Index additions
            '/^.*_update_option_/', // Option updates
            '/^.*_cleanup_/' // Data cleanup
        ];
        
        foreach ($safe_patterns as $pattern) {
            if (preg_match($pattern, $migration_name)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Create backup before migration
     */
    private function create_migration_backup($migration_name): void {
        global $wpdb;
        
        $backup_dir = WP_CONTENT_DIR . '/uploads/tmu-migration-backups/';
        
        if (!is_dir($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        $backup_file = $backup_dir . "backup_{$migration_name}_" . date('Y-m-d_H-i-s') . '.sql';
        
        // Get all TMU tables
        $tmu_tables = $wpdb->get_results(
            "SHOW TABLES LIKE '{$wpdb->prefix}tmu_%'",
            ARRAY_N
        );
        
        $sql_backup = "-- TMU Migration Backup for {$migration_name}\n";
        $sql_backup .= "-- Created: " . current_time('mysql') . "\n\n";
        
        foreach ($tmu_tables as $table) {
            $table_name = $table[0];
            
            // Get table structure
            $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
            $sql_backup .= "DROP TABLE IF EXISTS `{$table_name}`;\n";
            $sql_backup .= $create_table[1] . ";\n\n";
            
            // Get table data
            $rows = $wpdb->get_results("SELECT * FROM `{$table_name}`", ARRAY_A);
            
            if (!empty($rows)) {
                $sql_backup .= "INSERT INTO `{$table_name}` VALUES\n";
                $values = [];
                
                foreach ($rows as $row) {
                    $escaped_values = array_map(function($value) use ($wpdb) {
                        return $value === null ? 'NULL' : "'" . $wpdb->_escape($value) . "'";
                    }, $row);
                    $values[] = '(' . implode(',', $escaped_values) . ')';
                }
                
                $sql_backup .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        file_put_contents($backup_file, $sql_backup);
        
        $this->logger->debug('Migration backup created', ['backup_file' => $backup_file]);
    }
    
    /**
     * Mark migration as completed
     */
    private function mark_migration_completed($migration_name): void {
        $completed_migrations = get_option('tmu_completed_migrations', []);
        
        if (!in_array($migration_name, $completed_migrations)) {
            $completed_migrations[] = $migration_name;
            update_option('tmu_completed_migrations', $completed_migrations);
        }
        
        // Log migration completion
        $migration_log = get_option('tmu_migration_log', []);
        $migration_log[] = [
            'migration' => $migration_name,
            'completed_at' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ];
        
        // Keep only last 100 migration logs
        if (count($migration_log) > 100) {
            $migration_log = array_slice($migration_log, -100);
        }
        
        update_option('tmu_migration_log', $migration_log);
    }
    
    /**
     * Rollback migration
     */
    private function rollback_migration($migration_name): void {
        $this->logger->warning('Attempting migration rollback', ['migration' => $migration_name]);
        
        // Find the most recent backup for this migration
        $backup_dir = WP_CONTENT_DIR . '/uploads/tmu-migration-backups/';
        $backup_pattern = $backup_dir . "backup_{$migration_name}_*.sql";
        $backup_files = glob($backup_pattern);
        
        if (!empty($backup_files)) {
            // Get the most recent backup
            usort($backup_files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            $latest_backup = $backup_files[0];
            
            try {
                $this->restore_from_backup($latest_backup);
                $this->logger->info('Migration rollback completed', ['migration' => $migration_name]);
            } catch (\Exception $e) {
                $this->logger->error('Migration rollback failed', [
                    'migration' => $migration_name,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Restore from backup
     */
    private function restore_from_backup($backup_file): void {
        global $wpdb;
        
        if (!file_exists($backup_file)) {
            throw new \Exception('Backup file not found');
        }
        
        $sql_content = file_get_contents($backup_file);
        $queries = explode(";\n", $sql_content);
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query) && !preg_match('/^--/', $query)) {
                $result = $wpdb->query($query);
                if ($result === false) {
                    throw new \Exception('Failed to execute backup query: ' . $wpdb->last_error);
                }
            }
        }
    }
    
    /**
     * Run migration via AJAX
     */
    public function run_migration_ajax(): void {
        check_ajax_referer('tmu_migration_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $migration_name = sanitize_text_field($_POST['migration'] ?? '');
        
        if (empty($migration_name)) {
            wp_send_json_error('Migration name is required');
        }
        
        $result = $this->run_migration($migration_name);
        
        if ($result) {
            wp_send_json_success([
                'message' => 'Migration completed successfully',
                'migration' => $migration_name
            ]);
        } else {
            wp_send_json_error('Migration failed');
        }
    }
    
    /**
     * Get migration status
     */
    public function get_migration_status(): array {
        $all_migrations = $this->get_all_migrations();
        $completed_migrations = get_option('tmu_completed_migrations', []);
        $pending_migrations = $this->get_pending_migrations();
        
        return [
            'total_migrations' => count($all_migrations),
            'completed_migrations' => count($completed_migrations),
            'pending_migrations' => count($pending_migrations),
            'all_migrations' => $all_migrations,
            'completed' => $completed_migrations,
            'pending' => $pending_migrations
        ];
    }
    
    /**
     * Create new migration file
     */
    public function create_migration($name, $description = ''): string {
        if (!is_dir($this->migration_dir)) {
            wp_mkdir_p($this->migration_dir);
        }
        
        // Get next migration number
        $existing_migrations = $this->get_all_migrations();
        $next_number = 1;
        
        if (!empty($existing_migrations)) {
            $last_migration = end($existing_migrations);
            if (preg_match('/^(\d{3})_/', $last_migration, $matches)) {
                $next_number = intval($matches[1]) + 1;
            }
        }
        
        $migration_number = sprintf('%03d', $next_number);
        $migration_name = $migration_number . '_' . sanitize_file_name($name);
        $migration_file = $this->migration_dir . $migration_name . '.php';
        
        $migration_template = $this->get_migration_template($migration_name, $description);
        
        file_put_contents($migration_file, $migration_template);
        
        $this->logger->info('Migration file created', ['migration' => $migration_name]);
        
        return $migration_name;
    }
    
    /**
     * Get migration template
     */
    private function get_migration_template($migration_name, $description): string {
        return "<?php
/**
 * Migration: {$migration_name}
 * Description: {$description}
 * Created: " . current_time('Y-m-d H:i:s') . "
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Migration implementation
try {
    global \$wpdb;
    
    // Add your migration code here
    // Example:
    // \$wpdb->query(\"ALTER TABLE {\$wpdb->prefix}tmu_movies ADD COLUMN new_field VARCHAR(255)\");
    
    // Return true on success
    return true;
    
} catch (Exception \$e) {
    error_log('Migration {$migration_name} failed: ' . \$e->getMessage());
    return false;
}
";
    }
    
    /**
     * Cleanup old migration backups
     */
    public function cleanup_old_migration_backups(): void {
        $backup_dir = WP_CONTENT_DIR . '/uploads/tmu-migration-backups/';
        
        if (!is_dir($backup_dir)) {
            return;
        }
        
        $backup_files = glob($backup_dir . '*.sql');
        $cutoff_time = time() - (30 * 24 * 60 * 60); // 30 days ago
        
        foreach ($backup_files as $backup_file) {
            if (filemtime($backup_file) < $cutoff_time) {
                unlink($backup_file);
            }
        }
    }
}