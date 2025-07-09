<?php
/**
 * Migration Executor Class
 *
 * @package TMU\Migration
 * @version 1.0.0
 */

namespace TMU\Migration;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Migration Executor Class
 * Handles the execution of database migrations and theme setup
 */
class MigrationExecutor {
    
    /**
     * Migration manager instance
     *
     * @var MigrationManager
     */
    private $migration_manager;
    
    /**
     * Settings migrator instance
     *
     * @var SettingsMigrator
     */
    private $settings_migrator;
    
    /**
     * Migration validator instance
     *
     * @var MigrationValidator
     */
    private $validator;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->migration_manager = new MigrationManager();
        $this->settings_migrator = new SettingsMigrator();
        $this->validator = new MigrationValidator();
    }
    
    /**
     * Execute all pending migrations
     *
     * @return array Migration results
     */
    public function executePendingMigrations(): array {
        $results = [];
        
        try {
            // Get pending migrations
            $pending_migrations = $this->migration_manager->getPendingMigrations();
            
            if (empty($pending_migrations)) {
                return [
                    'success' => true,
                    'message' => __('No pending migrations found.', 'tmu'),
                    'executed' => []
                ];
            }
            
            // Execute each migration
            foreach ($pending_migrations as $migration) {
                $result = $this->executeMigration($migration);
                $results[] = $result;
                
                if (!$result['success']) {
                    break; // Stop on first failure
                }
            }
            
            return [
                'success' => true,
                'message' => sprintf(__('Executed %d migrations successfully.', 'tmu'), count($results)),
                'executed' => $results
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => sprintf(__('Migration execution failed: %s', 'tmu'), $e->getMessage()),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Execute a single migration
     *
     * @param array $migration Migration data
     * @return array Execution result
     */
    public function executeMigration(array $migration): array {
        try {
            // Validate migration before execution
            if (!$this->validator->validateMigration($migration)) {
                return [
                    'success' => false,
                    'migration' => $migration['name'] ?? 'unknown',
                    'message' => __('Migration validation failed.', 'tmu')
                ];
            }
            
            // Execute the migration
            $start_time = microtime(true);
            
            if (method_exists($this->migration_manager, $migration['method'])) {
                $result = call_user_func([$this->migration_manager, $migration['method']]);
            } else {
                throw new \Exception(sprintf('Migration method %s not found.', $migration['method']));
            }
            
            $execution_time = microtime(true) - $start_time;
            
            // Log successful execution
            tmu_log(sprintf(
                'Migration executed: %s (%.3fs)',
                $migration['name'] ?? 'unknown',
                $execution_time
            ), 'info');
            
            return [
                'success' => true,
                'migration' => $migration['name'] ?? 'unknown',
                'execution_time' => $execution_time,
                'result' => $result
            ];
            
        } catch (\Exception $e) {
            // Log error
            tmu_log(sprintf(
                'Migration failed: %s - %s',
                $migration['name'] ?? 'unknown',
                $e->getMessage()
            ), 'error');
            
            return [
                'success' => false,
                'migration' => $migration['name'] ?? 'unknown',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Execute settings migration
     *
     * @return array Migration result
     */
    public function executeSettingsMigration(): array {
        try {
            $result = $this->settings_migrator->migrateSettings();
            
            return [
                'success' => true,
                'message' => __('Settings migration completed successfully.', 'tmu'),
                'result' => $result
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => sprintf(__('Settings migration failed: %s', 'tmu'), $e->getMessage()),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Rollback last migration
     *
     * @return array Rollback result
     */
    public function rollbackLastMigration(): array {
        try {
            $last_migration = $this->migration_manager->getLastMigration();
            
            if (!$last_migration) {
                return [
                    'success' => false,
                    'message' => __('No migration to rollback.', 'tmu')
                ];
            }
            
            $result = $this->migration_manager->rollbackMigration($last_migration);
            
            return [
                'success' => true,
                'message' => sprintf(__('Rolled back migration: %s', 'tmu'), $last_migration['name']),
                'result' => $result
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => sprintf(__('Migration rollback failed: %s', 'tmu'), $e->getMessage()),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get migration status
     *
     * @return array Migration status
     */
    public function getMigrationStatus(): array {
        try {
            $pending = $this->migration_manager->getPendingMigrations();
            $completed = $this->migration_manager->getCompletedMigrations();
            
            return [
                'success' => true,
                'pending_count' => count($pending),
                'completed_count' => count($completed),
                'pending_migrations' => $pending,
                'completed_migrations' => $completed
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => sprintf(__('Failed to get migration status: %s', 'tmu'), $e->getMessage()),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate all migrations
     *
     * @return array Validation result
     */
    public function validateAllMigrations(): array {
        try {
            $migrations = $this->migration_manager->getAllMigrations();
            $validation_results = [];
            
            foreach ($migrations as $migration) {
                $validation_results[] = [
                    'migration' => $migration['name'] ?? 'unknown',
                    'valid' => $this->validator->validateMigration($migration)
                ];
            }
            
            return [
                'success' => true,
                'results' => $validation_results
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => sprintf(__('Migration validation failed: %s', 'tmu'), $e->getMessage()),
                'error' => $e->getMessage()
            ];
        }
    }
}