<?php
/**
 * Execute Migration Script
 * 
 * Launch day migration execution script as specified in Step 19 documentation
 * Referenced in lines 488-498 for Go-Live checklist
 * 
 * @package TMU\Migration
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WP_CLI') && !defined('ABSPATH')) {
    exit('This script can only be run via WP-CLI or WordPress admin.');
}

// Load WordPress if not already loaded
if (!defined('ABSPATH')) {
    require_once dirname(__FILE__) . '/../../../../wp-config.php';
}

use TMU\Migration\MigrationExecutor;
use TMU\Migration\MigrationValidator;
use TMU\Utils\Logger;

/**
 * Migration Execution Class
 * 
 * Handles the complete migration process as specified in Step 19
 */
class TMUMigrationExecution {
    
    /**
     * Migration executor instance
     * 
     * @var MigrationExecutor
     */
    private MigrationExecutor $executor;
    
    /**
     * Migration validator instance
     * 
     * @var MigrationValidator
     */
    private MigrationValidator $validator;
    
    /**
     * Migration results
     * 
     * @var array
     */
    private array $results = [];
    
    /**
     * Initialize migration execution
     */
    public function __construct() {
        $this->executor = new MigrationExecutor();
        $this->validator = new MigrationValidator();
        
        // Set up error handling
        set_error_handler([$this, 'handle_error']);
        set_exception_handler([$this, 'handle_exception']);
    }
    
    /**
     * Execute complete migration process
     * 
     * @return array Migration results
     */
    public function execute(): array {
        Logger::info('Starting TMU migration execution');
        
        $this->results = [
            'start_time' => current_time('mysql'),
            'status' => 'starting',
            'steps' => [],
            'errors' => [],
            'warnings' => []
        ];
        
        try {
            // Step 1: Pre-migration validation
            $this->log_step('Pre-migration validation');
            $pre_validation = $this->validator->validate_pre_migration();
            $this->results['steps']['pre_validation'] = $pre_validation;
            
            if (!empty($pre_validation['critical_issues'])) {
                throw new Exception('Pre-migration validation failed: ' . implode(', ', $pre_validation['critical_issues']));
            }
            
            // Step 2: Create backup
            $this->log_step('Creating backup');
            $backup_result = $this->create_backup();
            $this->results['steps']['backup'] = $backup_result;
            
            if (!$backup_result['success']) {
                throw new Exception('Backup creation failed: ' . $backup_result['error']);
            }
            
            // Step 3: Enable maintenance mode
            $this->log_step('Enabling maintenance mode');
            $this->enable_maintenance_mode();
            $this->results['steps']['maintenance_mode'] = ['enabled' => true];
            
            // Step 4: Execute data migration
            $this->log_step('Executing data migration');
            $migration_result = $this->executor->execute_migration();
            $this->results['steps']['data_migration'] = $migration_result;
            
            if (!$migration_result['success']) {
                $this->rollback();
                throw new Exception('Data migration failed: ' . $migration_result['error']);
            }
            
            // Step 5: Validate migration
            $this->log_step('Validating migration');
            $validation_result = $this->validator->run_full_validation();
            $this->results['steps']['validation'] = $validation_result;
            
            if ($validation_result['overall_status'] !== 'ready') {
                $this->rollback();
                throw new Exception('Migration validation failed');
            }
            
            // Step 6: Update theme settings
            $this->log_step('Updating theme settings');
            $this->update_theme_settings();
            $this->results['steps']['theme_settings'] = ['updated' => true];
            
            // Step 7: Clear caches
            $this->log_step('Clearing caches');
            $this->clear_all_caches();
            $this->results['steps']['cache_clear'] = ['cleared' => true];
            
            // Step 8: Disable maintenance mode
            $this->log_step('Disabling maintenance mode');
            $this->disable_maintenance_mode();
            $this->results['steps']['maintenance_mode']['disabled'] = true;
            
            // Step 9: Post-migration validation
            $this->log_step('Post-migration validation');
            $post_validation = $this->validator->validate_post_migration();
            $this->results['steps']['post_validation'] = $post_validation;
            
            $this->results['status'] = 'completed';
            $this->results['end_time'] = current_time('mysql');
            
            Logger::info('TMU migration completed successfully');
            
        } catch (Exception $e) {
            $this->results['status'] = 'failed';
            $this->results['errors'][] = $e->getMessage();
            $this->results['end_time'] = current_time('mysql');
            
            Logger::error('TMU migration failed: ' . $e->getMessage());
            
            // Ensure maintenance mode is disabled
            $this->disable_maintenance_mode();
        }
        
        return $this->results;
    }
    
    /**
     * Create complete backup
     * 
     * @return array Backup results
     */
    private function create_backup(): array {
        $backup_dir = WP_CONTENT_DIR . '/backups/';
        $timestamp = date('Y-m-d_H-i-s');
        
        if (!is_dir($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        try {
            // Database backup
            $db_backup_file = $backup_dir . "tmu_pre_migration_db_{$timestamp}.sql";
            $db_command = sprintf(
                'mysqldump -h%s -u%s -p%s %s > %s',
                DB_HOST,
                DB_USER,
                DB_PASSWORD,
                DB_NAME,
                $db_backup_file
            );
            
            exec($db_command, $db_output, $db_return);
            
            // Files backup
            $files_backup_file = $backup_dir . "tmu_pre_migration_files_{$timestamp}.tar.gz";
            $files_command = sprintf(
                'tar -czf %s -C %s wp-content/',
                $files_backup_file,
                ABSPATH
            );
            
            exec($files_command, $files_output, $files_return);
            
            $success = ($db_return === 0 && $files_return === 0);
            
            return [
                'success' => $success,
                'db_backup' => $db_backup_file,
                'files_backup' => $files_backup_file,
                'timestamp' => $timestamp,
                'error' => $success ? null : 'Backup command failed'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Enable maintenance mode
     * 
     * @return void
     */
    private function enable_maintenance_mode(): void {
        $maintenance_file = ABSPATH . '.maintenance';
        $maintenance_content = '<?php $upgrading = ' . time() . ';';
        file_put_contents($maintenance_file, $maintenance_content);
    }
    
    /**
     * Disable maintenance mode
     * 
     * @return void
     */
    private function disable_maintenance_mode(): void {
        $maintenance_file = ABSPATH . '.maintenance';
        if (file_exists($maintenance_file)) {
            unlink($maintenance_file);
        }
    }
    
    /**
     * Update theme settings after migration
     * 
     * @return void
     */
    private function update_theme_settings(): void {
        // Mark migration as completed
        update_option('tmu_migration_completed', true);
        update_option('tmu_migration_date', current_time('mysql'));
        
        // Update theme version
        update_option('tmu_theme_version', '1.0.0');
        
        // Set default theme options
        $default_options = [
            'tmu_movies' => 'on',
            'tmu_tv_series' => 'on',
            'tmu_dramas' => 'on',
            'tmu_people' => 'on',
            'tmu_videos' => 'on'
        ];
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }
    
    /**
     * Clear all caches
     * 
     * @return void
     */
    private function clear_all_caches(): void {
        // WordPress object cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // TMU specific caches
        $cache_groups = ['tmu_movies', 'tmu_tv', 'tmu_dramas', 'tmu_people', 'tmu_videos'];
        foreach ($cache_groups as $group) {
            if (function_exists('wp_cache_flush_group')) {
                wp_cache_flush_group($group);
            }
        }
        
        // Clear transients
        delete_expired_transients();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Rollback migration
     * 
     * @return void
     */
    private function rollback(): void {
        Logger::warning('Initiating migration rollback');
        
        try {
            // Restore from backup if available
            $backup_info = $this->results['steps']['backup'] ?? null;
            if ($backup_info && $backup_info['success']) {
                $this->restore_from_backup($backup_info);
            }
            
            // Disable maintenance mode
            $this->disable_maintenance_mode();
            
            Logger::info('Migration rollback completed');
            
        } catch (Exception $e) {
            Logger::error('Rollback failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Restore from backup
     * 
     * @param array $backup_info Backup information
     * @return void
     */
    private function restore_from_backup(array $backup_info): void {
        // Restore database
        if (isset($backup_info['db_backup']) && file_exists($backup_info['db_backup'])) {
            $restore_command = sprintf(
                'mysql -h%s -u%s -p%s %s < %s',
                DB_HOST,
                DB_USER,
                DB_PASSWORD,
                DB_NAME,
                $backup_info['db_backup']
            );
            exec($restore_command);
        }
        
        // Note: File restoration would be more complex and risky
        // In practice, this should be handled carefully
    }
    
    /**
     * Log migration step
     * 
     * @param string $step Step description
     * @return void
     */
    private function log_step(string $step): void {
        echo "TMU Migration: {$step}...\n";
        Logger::info("Migration step: {$step}");
    }
    
    /**
     * Handle errors
     * 
     * @param int $errno Error number
     * @param string $errstr Error string
     * @param string $errfile Error file
     * @param int $errline Error line
     * @return bool
     */
    public function handle_error(int $errno, string $errstr, string $errfile, int $errline): bool {
        $this->results['errors'][] = "Error {$errno}: {$errstr} in {$errfile}:{$errline}";
        Logger::error("Migration error: {$errstr}");
        return true;
    }
    
    /**
     * Handle exceptions
     * 
     * @param Throwable $exception Exception
     * @return void
     */
    public function handle_exception(Throwable $exception): void {
        $this->results['errors'][] = "Exception: " . $exception->getMessage();
        Logger::error("Migration exception: " . $exception->getMessage());
    }
}

// Execute migration if called directly
if (defined('WP_CLI') || (defined('DOING_AJAX') && DOING_AJAX)) {
    $migration = new TMUMigrationExecution();
    $results = $migration->execute();
    
    if (defined('WP_CLI')) {
        WP_CLI::line('Migration Status: ' . $results['status']);
        if ($results['status'] === 'failed') {
            WP_CLI::error('Migration failed: ' . implode(', ', $results['errors']));
        } else {
            WP_CLI::success('Migration completed successfully');
        }
    } else {
        echo json_encode($results);
    }
}