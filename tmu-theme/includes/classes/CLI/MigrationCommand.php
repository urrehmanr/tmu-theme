<?php
/**
 * WP-CLI Migration Command
 * 
 * Provides CLI interface for TMU block system migration
 * 
 * @package TMU\CLI
 * @version 1.0.0
 */

namespace TMU\CLI;

use TMU\Migration\BlockDataMigrator;
use WP_CLI;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TMU Migration Commands
 */
class MigrationCommand {
    
    /**
     * Migrate from meta box system to Gutenberg blocks
     * 
     * ## OPTIONS
     * 
     * [--dry-run]
     * : Run migration without making actual changes
     * 
     * [--post-types=<types>]
     * : Comma-separated list of post types to migrate
     * ---
     * default: movie,tv,drama,people
     * ---
     * 
     * [--batch-size=<size>]
     * : Number of posts to process per batch
     * ---
     * default: 50
     * ---
     * 
     * [--skip-backup]
     * : Skip data backup (not recommended)
     * 
     * ## EXAMPLES
     * 
     *     # Dry run to test migration
     *     wp tmu migrate blocks --dry-run
     * 
     *     # Migrate only movies
     *     wp tmu migrate blocks --post-types=movie
     * 
     *     # Full migration with smaller batches
     *     wp tmu migrate blocks --batch-size=25
     * 
     * @param array $args Positional arguments
     * @param array $assoc_args Associative arguments
     */
    public function blocks($args, $assoc_args) {
        WP_CLI::line('TMU Block Migration Tool');
        WP_CLI::line('======================');
        
        // Parse options
        $options = [
            'dry_run' => isset($assoc_args['dry-run']),
            'post_types' => explode(',', $assoc_args['post-types'] ?? 'movie,tv,drama,people'),
            'batch_size' => intval($assoc_args['batch-size'] ?? 50),
            'backup_data' => !isset($assoc_args['skip-backup']),
        ];
        
        // Validate options
        if ($options['batch_size'] < 1 || $options['batch_size'] > 1000) {
            WP_CLI::error('Batch size must be between 1 and 1000');
        }
        
        $valid_post_types = ['movie', 'tv', 'drama', 'people'];
        foreach ($options['post_types'] as $post_type) {
            if (!in_array($post_type, $valid_post_types)) {
                WP_CLI::error("Invalid post type: {$post_type}. Valid types: " . implode(', ', $valid_post_types));
            }
        }
        
        // Show configuration
        WP_CLI::line('Configuration:');
        WP_CLI::line('- Mode: ' . ($options['dry_run'] ? 'DRY RUN' : 'LIVE'));
        WP_CLI::line('- Post types: ' . implode(', ', $options['post_types']));
        WP_CLI::line('- Batch size: ' . $options['batch_size']);
        WP_CLI::line('- Backup data: ' . ($options['backup_data'] ? 'Yes' : 'No'));
        WP_CLI::line('');
        
        // Confirm if not dry run
        if (!$options['dry_run']) {
            WP_CLI::confirm('This will modify your database. Continue?');
        }
        
        // Pre-migration checks
        WP_CLI::line('Running pre-migration checks...');
        $checks = $this->runPreMigrationChecks($options);
        
        if (!$checks['passed']) {
            WP_CLI::error('Pre-migration checks failed. Please fix issues before continuing.');
        }
        
        WP_CLI::success('Pre-migration checks passed!');
        
        // Run migration
        $migrator = new BlockDataMigrator();
        
        WP_CLI::line('Starting migration...');
        $start_time = microtime(true);
        
        $results = $migrator->runMigration($options);
        
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);
        
        // Display results
        $this->displayMigrationResults($results, $duration, $options['dry_run']);
        
        // Post-migration validation
        if (!$options['dry_run']) {
            WP_CLI::line('Running post-migration validation...');
            $validation = $migrator->validateMigration();
            $this->displayValidationResults($validation);
        }
    }
    
    /**
     * Validate migration results
     * 
     * ## EXAMPLES
     * 
     *     # Validate migration integrity
     *     wp tmu migrate validate
     * 
     * @param array $args Positional arguments
     * @param array $assoc_args Associative arguments
     */
    public function validate($args, $assoc_args) {
        WP_CLI::line('TMU Migration Validation');
        WP_CLI::line('=======================');
        
        $migrator = new BlockDataMigrator();
        $validation = $migrator->validateMigration();
        
        $this->displayValidationResults($validation);
    }
    
    /**
     * Rollback migration (restore from backup)
     * 
     * ## OPTIONS
     * 
     * [--backup-date=<date>]
     * : Date of backup to restore (YYYYMMDD_HHIISS format)
     * 
     * [--confirm]
     * : Skip confirmation prompt
     * 
     * ## EXAMPLES
     * 
     *     # List available backups
     *     wp tmu migrate rollback
     * 
     *     # Restore specific backup
     *     wp tmu migrate rollback --backup-date=20231215_143022
     * 
     * @param array $args Positional arguments
     * @param array $assoc_args Associative arguments
     */
    public function rollback($args, $assoc_args) {
        global $wpdb;
        
        WP_CLI::line('TMU Migration Rollback');
        WP_CLI::line('=====================');
        
        // List available backups
        $backup_tables = $wpdb->get_results(
            "SHOW TABLES LIKE '{$wpdb->prefix}postmeta_backup_%'"
        );
        
        if (empty($backup_tables)) {
            WP_CLI::error('No backup tables found. Cannot rollback.');
        }
        
        // Extract backup dates
        $available_backups = [];
        foreach ($backup_tables as $table) {
            $table_name = array_values($table)[0];
            if (preg_match('/backup_(\d{8}_\d{6})$/', $table_name, $matches)) {
                $available_backups[] = $matches[1];
            }
        }
        
        if (empty($available_backups)) {
            WP_CLI::error('No valid backup tables found.');
        }
        
        // Show available backups
        WP_CLI::line('Available backups:');
        foreach ($available_backups as $backup_date) {
            $formatted_date = DateTime::createFromFormat('Ymd_His', $backup_date);
            WP_CLI::line("- {$backup_date} ({$formatted_date->format('Y-m-d H:i:s')})");
        }
        
        $backup_date = $assoc_args['backup-date'] ?? null;
        
        if (!$backup_date) {
            WP_CLI::error('Please specify --backup-date with one of the available backup dates.');
        }
        
        if (!in_array($backup_date, $available_backups)) {
            WP_CLI::error("Backup date {$backup_date} not found.");
        }
        
        // Confirm rollback
        if (!isset($assoc_args['confirm'])) {
            WP_CLI::confirm("This will restore data from backup {$backup_date}. All current data will be lost. Continue?");
        }
        
        // Perform rollback
        WP_CLI::line('Performing rollback...');
        
        $backup_table = "{$wpdb->prefix}postmeta_backup_{$backup_date}";
        
        // Restore postmeta
        $result = $wpdb->query("
            REPLACE INTO {$wpdb->prefix}postmeta 
            SELECT * FROM {$backup_table}
        ");
        
        if ($result === false) {
            WP_CLI::error('Failed to restore postmeta: ' . $wpdb->last_error);
        }
        
        WP_CLI::success("Rollback completed! Restored {$result} meta records.");
    }
    
    /**
     * Clean up migration artifacts
     * 
     * ## OPTIONS
     * 
     * [--remove-backups]
     * : Remove backup tables
     * 
     * [--remove-logs]
     * : Remove migration logs
     * 
     * [--confirm]
     * : Skip confirmation prompt
     * 
     * ## EXAMPLES
     * 
     *     # Clean up everything
     *     wp tmu migrate cleanup --remove-backups --remove-logs
     * 
     * @param array $args Positional arguments
     * @param array $assoc_args Associative arguments
     */
    public function cleanup($args, $assoc_args) {
        global $wpdb;
        
        WP_CLI::line('TMU Migration Cleanup');
        WP_CLI::line('====================');
        
        $remove_backups = isset($assoc_args['remove-backups']);
        $remove_logs = isset($assoc_args['remove-logs']);
        
        if (!$remove_backups && !$remove_logs) {
            WP_CLI::error('Please specify --remove-backups and/or --remove-logs');
        }
        
        // Confirm cleanup
        if (!isset($assoc_args['confirm'])) {
            WP_CLI::confirm('This will permanently delete migration artifacts. Continue?');
        }
        
        if ($remove_backups) {
            WP_CLI::line('Removing backup tables...');
            
            $backup_tables = $wpdb->get_results(
                "SHOW TABLES LIKE '{$wpdb->prefix}postmeta_backup_%'"
            );
            
            foreach ($backup_tables as $table) {
                $table_name = array_values($table)[0];
                $result = $wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");
                
                if ($result === false) {
                    WP_CLI::warning("Failed to drop table {$table_name}");
                } else {
                    WP_CLI::line("Removed backup table: {$table_name}");
                }
            }
        }
        
        if ($remove_logs) {
            WP_CLI::line('Removing migration logs...');
            
            // Remove migration log files
            $log_dir = WP_CONTENT_DIR . '/uploads/tmu-migration-logs';
            if (is_dir($log_dir)) {
                $files = glob($log_dir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                        WP_CLI::line("Removed log file: " . basename($file));
                    }
                }
                rmdir($log_dir);
            }
        }
        
        WP_CLI::success('Cleanup completed!');
    }
    
    /**
     * Run pre-migration checks
     *
     * @param array $options Migration options
     * @return array Check results
     */
    private function runPreMigrationChecks(array $options): array {
        global $wpdb;
        
        $checks = [
            'passed' => true,
            'issues' => [],
        ];
        
        // Check database permissions
        $result = $wpdb->query("SHOW GRANTS");
        if ($result === false) {
            $checks['issues'][] = 'Cannot check database permissions';
            $checks['passed'] = false;
        }
        
        // Check if custom tables exist
        $required_tables = ['tmu_movies', 'tmu_tv_series', 'tmu_dramas', 'tmu_people'];
        foreach ($required_tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'");
            if (!$exists) {
                $checks['issues'][] = "Required table {$table} does not exist";
                $checks['passed'] = false;
            }
        }
        
        // Check disk space for backups
        if ($options['backup_data']) {
            $free_space = disk_free_space(ABSPATH);
            $postmeta_size = $wpdb->get_var("
                SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) 
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE() 
                AND table_name = '{$wpdb->prefix}postmeta'
            ");
            
            if ($free_space && $postmeta_size && $free_space < ($postmeta_size * 1024 * 1024 * 2)) {
                $checks['issues'][] = 'Insufficient disk space for backup';
                $checks['passed'] = false;
            }
        }
        
        // Check for existing post data
        foreach ($options['post_types'] as $post_type) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type = %s",
                $post_type
            ));
            
            if ($count > 0) {
                WP_CLI::line("Found {$count} {$post_type} posts to migrate");
            }
        }
        
        // Display issues
        if (!empty($checks['issues'])) {
            WP_CLI::line('Issues found:');
            foreach ($checks['issues'] as $issue) {
                WP_CLI::warning("- {$issue}");
            }
        }
        
        return $checks;
    }
    
    /**
     * Display migration results
     *
     * @param array $results Migration results
     * @param float $duration Migration duration
     * @param bool $dry_run Whether this was a dry run
     */
    private function displayMigrationResults(array $results, float $duration, bool $dry_run): void {
        WP_CLI::line('');
        WP_CLI::line('Migration Results:');
        WP_CLI::line('==================');
        WP_CLI::line("Duration: {$duration} seconds");
        WP_CLI::line("Posts processed: {$results['posts_processed']}");
        WP_CLI::line("Posts migrated: {$results['posts_migrated']}");
        
        if (!empty($results['errors'])) {
            WP_CLI::line('');
            WP_CLI::warning('Errors encountered:');
            foreach ($results['errors'] as $error) {
                WP_CLI::line("- {$error}");
            }
        }
        
        if (!empty($results['warnings'])) {
            WP_CLI::line('');
            WP_CLI::warning('Warnings:');
            foreach ($results['warnings'] as $warning) {
                WP_CLI::line("- {$warning}");
            }
        }
        
        if ($dry_run) {
            WP_CLI::success('Dry run completed successfully! No changes were made.');
        } else {
            WP_CLI::success('Migration completed successfully!');
        }
    }
    
    /**
     * Display validation results
     *
     * @param array $validation Validation results
     */
    private function displayValidationResults(array $validation): void {
        WP_CLI::line('');
        WP_CLI::line('Validation Results:');
        WP_CLI::line('==================');
        
        if ($validation['data_integrity']) {
            WP_CLI::success('Data integrity check passed!');
        } else {
            WP_CLI::error('Data integrity issues found!');
        }
        
        if (!empty($validation['missing_data'])) {
            WP_CLI::line('');
            WP_CLI::warning('Missing data:');
            foreach ($validation['missing_data'] as $issue) {
                WP_CLI::line("- Post {$issue['post_id']} ({$issue['post_type']}): {$issue['issue']}");
            }
        }
        
        if (!empty($validation['inconsistencies'])) {
            WP_CLI::line('');
            WP_CLI::warning('Data inconsistencies:');
            foreach ($validation['inconsistencies'] as $issue) {
                WP_CLI::line("- {$issue}");
            }
        }
    }
}

// Register WP-CLI command
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('tmu migrate', MigrationCommand::class);
}