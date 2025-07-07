<?php
/**
 * Backup Manager
 * 
 * Comprehensive backup system for the TMU theme.
 * 
 * @package TMU\Backup
 * @since 1.0.0
 */

namespace TMU\Backup;

class BackupManager {
    
    /**
     * Backup directory
     * @var string
     */
    private $backup_dir;
    
    /**
     * Constructor
     */
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->backup_dir = $upload_dir['basedir'] . '/tmu-backups/';
        
        add_action('init', [$this, 'init_backup_system']);
        add_action('tmu_create_backup', [$this, 'create_scheduled_backup']);
        add_action('wp_ajax_tmu_create_manual_backup', [$this, 'create_manual_backup']);
        add_action('wp_ajax_tmu_restore_backup', [$this, 'restore_backup']);
        add_action('wp_ajax_tmu_list_backups', [$this, 'list_backups']);
        add_action('wp_ajax_tmu_delete_backup', [$this, 'delete_backup']);
    }
    
    /**
     * Initialize backup system
     */
    public function init_backup_system(): void {
        // Create backup directory if it doesn't exist
        if (!is_dir($this->backup_dir)) {
            wp_mkdir_p($this->backup_dir);
            
            // Add .htaccess for security
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents($this->backup_dir . '.htaccess', $htaccess_content);
        }
        
        // Schedule automatic backups
        $this->schedule_automatic_backups();
    }
    
    /**
     * Schedule automatic backups
     */
    private function schedule_automatic_backups(): void {
        // Daily database backup
        if (!wp_next_scheduled('tmu_daily_backup')) {
            wp_schedule_event(time() + (3 * HOUR_IN_SECONDS), 'daily', 'tmu_daily_backup');
        }
        
        // Weekly full backup
        if (!wp_next_scheduled('tmu_weekly_backup')) {
            wp_schedule_event(time() + (24 * HOUR_IN_SECONDS), 'weekly', 'tmu_weekly_backup');
        }
        
        // Monthly archive backup
        if (!wp_next_scheduled('tmu_monthly_backup')) {
            wp_schedule_event(time() + (48 * HOUR_IN_SECONDS), 'monthly', 'tmu_monthly_backup');
        }
        
        // Register backup actions
        add_action('tmu_daily_backup', [$this, 'create_daily_backup']);
        add_action('tmu_weekly_backup', [$this, 'create_weekly_backup']);
        add_action('tmu_monthly_backup', [$this, 'create_monthly_backup']);
    }
    
    /**
     * Create scheduled backup
     */
    public function create_scheduled_backup($type = 'manual'): void {
        try {
            $backup_id = $this->create_backup([
                'type' => $type,
                'include_files' => $type !== 'daily',
                'include_database' => true,
                'include_media' => $type === 'monthly'
            ]);
            
            $this->log_backup_event('success', "Scheduled {$type} backup created", ['backup_id' => $backup_id]);
            
        } catch (Exception $e) {
            $this->log_backup_event('error', "Scheduled {$type} backup failed", ['error' => $e->getMessage()]);
            $this->send_backup_alert("Backup failed: " . $e->getMessage());
        }
    }
    
    /**
     * Create daily backup
     */
    public function create_daily_backup(): void {
        $this->create_scheduled_backup('daily');
    }
    
    /**
     * Create weekly backup
     */
    public function create_weekly_backup(): void {
        $this->create_scheduled_backup('weekly');
    }
    
    /**
     * Create monthly backup
     */
    public function create_monthly_backup(): void {
        $this->create_scheduled_backup('monthly');
    }
    
    /**
     * Create manual backup via AJAX
     */
    public function create_manual_backup(): void {
        check_ajax_referer('tmu_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $options = [
                'type' => 'manual',
                'include_files' => $_POST['include_files'] ?? true,
                'include_database' => $_POST['include_database'] ?? true,
                'include_media' => $_POST['include_media'] ?? false
            ];
            
            $backup_id = $this->create_backup($options);
            
            wp_send_json_success([
                'message' => 'Backup created successfully',
                'backup_id' => $backup_id
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('Backup failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Create backup
     * 
     * @param array $options
     * @return string Backup ID
     */
    public function create_backup($options = []): string {
        $defaults = [
            'type' => 'manual',
            'include_files' => true,
            'include_database' => true,
            'include_media' => false
        ];
        
        $options = array_merge($defaults, $options);
        $backup_id = 'backup_' . date('Y-m-d_H-i-s') . '_' . $options['type'];
        $backup_path = $this->backup_dir . $backup_id . '/';
        
        // Create backup directory
        wp_mkdir_p($backup_path);
        
        $backup_info = [
            'id' => $backup_id,
            'type' => $options['type'],
            'created_at' => current_time('mysql'),
            'size' => 0,
            'files' => [],
            'status' => 'in_progress'
        ];
        
        try {
            // Create database backup
            if ($options['include_database']) {
                $db_file = $this->create_database_backup($backup_path);
                $backup_info['files']['database'] = $db_file;
            }
            
            // Create files backup
            if ($options['include_files']) {
                $files_archive = $this->create_files_backup($backup_path, $options['include_media']);
                $backup_info['files']['theme'] = $files_archive;
            }
            
            // Create TMU data backup
            $tmu_data_file = $this->create_tmu_data_backup($backup_path);
            $backup_info['files']['tmu_data'] = $tmu_data_file;
            
            // Calculate total backup size
            $backup_info['size'] = $this->calculate_backup_size($backup_path);
            $backup_info['status'] = 'completed';
            
            // Save backup metadata
            $this->save_backup_metadata($backup_id, $backup_info);
            
            // Clean up old backups
            $this->cleanup_old_backups($options['type']);
            
            return $backup_id;
            
        } catch (Exception $e) {
            $backup_info['status'] = 'failed';
            $backup_info['error'] = $e->getMessage();
            $this->save_backup_metadata($backup_id, $backup_info);
            
            // Clean up failed backup
            $this->remove_directory($backup_path);
            
            throw $e;
        }
    }
    
    /**
     * Create database backup
     */
    private function create_database_backup($backup_path): string {
        global $wpdb;
        
        $db_file = $backup_path . 'database.sql';
        $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
        
        $sql_content = "-- TMU Theme Database Backup\n";
        $sql_content .= "-- Created: " . current_time('mysql') . "\n\n";
        $sql_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            $table_name = $table[0];
            
            // Get table structure
            $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
            $sql_content .= "DROP TABLE IF EXISTS `{$table_name}`;\n";
            $sql_content .= $create_table[1] . ";\n\n";
            
            // Get table data for TMU tables and WordPress core tables
            if (strpos($table_name, 'tmu_') !== false || strpos($table_name, $wpdb->prefix) !== false) {
                $rows = $wpdb->get_results("SELECT * FROM `{$table_name}`", ARRAY_A);
                
                if (!empty($rows)) {
                    $sql_content .= "INSERT INTO `{$table_name}` VALUES\n";
                    $values = [];
                    
                    foreach ($rows as $row) {
                        $escaped_values = array_map(function($value) use ($wpdb) {
                            return $value === null ? 'NULL' : "'" . $wpdb->_escape($value) . "'";
                        }, $row);
                        $values[] = '(' . implode(',', $escaped_values) . ')';
                    }
                    
                    $sql_content .= implode(",\n", $values) . ";\n\n";
                }
            }
        }
        
        $sql_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        file_put_contents($db_file, $sql_content);
        
        return basename($db_file);
    }
    
    /**
     * Create files backup
     */
    private function create_files_backup($backup_path, $include_media = false): string {
        $archive_file = $backup_path . 'theme_files.zip';
        $theme_dir = get_template_directory();
        
        $zip = new ZipArchive();
        
        if ($zip->open($archive_file, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('Cannot create backup archive');
        }
        
        // Add theme files
        $this->add_directory_to_zip($zip, $theme_dir, 'theme/');
        
        // Add media files if requested
        if ($include_media) {
            $upload_dir = wp_upload_dir();
            $this->add_directory_to_zip($zip, $upload_dir['basedir'], 'uploads/');
        }
        
        $zip->close();
        
        return basename($archive_file);
    }
    
    /**
     * Create TMU data backup
     */
    private function create_tmu_data_backup($backup_path): string {
        global $wpdb;
        
        $data_file = $backup_path . 'tmu_data.json';
        
        $tmu_data = [
            'version' => get_option('tmu_theme_version'),
            'settings' => $this->get_tmu_settings(),
            'statistics' => $this->get_tmu_statistics(),
            'metadata' => [
                'backup_created' => current_time('c'),
                'wp_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'mysql_version' => $wpdb->db_version()
            ]
        ];
        
        file_put_contents($data_file, json_encode($tmu_data, JSON_PRETTY_PRINT));
        
        return basename($data_file);
    }
    
    /**
     * Get TMU settings
     */
    private function get_tmu_settings(): array {
        $settings = [];
        
        // Get all TMU options
        global $wpdb;
        $tmu_options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'tmu_%'"
        );
        
        foreach ($tmu_options as $option) {
            $settings[$option->option_name] = maybe_unserialize($option->option_value);
        }
        
        return $settings;
    }
    
    /**
     * Get TMU statistics
     */
    private function get_tmu_statistics(): array {
        global $wpdb;
        
        return [
            'total_movies' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_movies"),
            'total_tv_series' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_tv_series"),
            'total_dramas' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_dramas"),
            'total_people' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_people"),
            'total_posts' => wp_count_posts()->publish ?? 0,
            'total_analytics_events' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_analytics_events"),
            'last_tmdb_sync' => get_option('tmu_last_tmdb_sync', 'never')
        ];
    }
    
    /**
     * Add directory to ZIP archive
     */
    private function add_directory_to_zip($zip, $dir, $zip_path = ''): void {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $file_path = $file->getRealPath();
            $relative_path = $zip_path . substr($file_path, strlen($dir) + 1);
            
            if ($file->isDir()) {
                $zip->addEmptyDir($relative_path);
            } elseif ($file->isFile()) {
                // Skip sensitive files
                if (!$this->should_backup_file($file_path)) {
                    continue;
                }
                
                $zip->addFile($file_path, $relative_path);
            }
        }
    }
    
    /**
     * Check if file should be backed up
     */
    private function should_backup_file($file_path): bool {
        $excluded_patterns = [
            '/node_modules/',
            '/.git/',
            '/vendor/',
            '/tmp/',
            '/cache/',
            '/logs/',
            '/backups/',
            '.log',
            '.tmp'
        ];
        
        foreach ($excluded_patterns as $pattern) {
            if (strpos($file_path, $pattern) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Calculate backup size
     */
    private function calculate_backup_size($backup_path): int {
        $size = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($backup_path, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }
    
    /**
     * Save backup metadata
     */
    private function save_backup_metadata($backup_id, $backup_info): void {
        $metadata_file = $this->backup_dir . $backup_id . '/metadata.json';
        file_put_contents($metadata_file, json_encode($backup_info, JSON_PRETTY_PRINT));
        
        // Also save to database for quick access
        $backups = get_option('tmu_backup_list', []);
        $backups[$backup_id] = $backup_info;
        update_option('tmu_backup_list', $backups);
    }
    
    /**
     * Cleanup old backups
     */
    private function cleanup_old_backups($type): void {
        $retention_limits = [
            'daily' => 7,    // Keep 7 daily backups
            'weekly' => 4,   // Keep 4 weekly backups
            'monthly' => 12, // Keep 12 monthly backups
            'manual' => 5    // Keep 5 manual backups
        ];
        
        $limit = $retention_limits[$type] ?? 5;
        $backups = get_option('tmu_backup_list', []);
        
        // Filter backups by type and sort by creation date
        $type_backups = array_filter($backups, function($backup) use ($type) {
            return $backup['type'] === $type;
        });
        
        uasort($type_backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Remove excess backups
        $to_remove = array_slice($type_backups, $limit, null, true);
        
        foreach ($to_remove as $backup_id => $backup_info) {
            $this->delete_backup_files($backup_id);
            unset($backups[$backup_id]);
        }
        
        update_option('tmu_backup_list', $backups);
    }
    
    /**
     * Delete backup files
     */
    private function delete_backup_files($backup_id): void {
        $backup_path = $this->backup_dir . $backup_id . '/';
        
        if (is_dir($backup_path)) {
            $this->remove_directory($backup_path);
        }
    }
    
    /**
     * Remove directory recursively
     */
    private function remove_directory($dir): void {
        if (!is_dir($dir)) {
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * List backups via AJAX
     */
    public function list_backups(): void {
        check_ajax_referer('tmu_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $backups = get_option('tmu_backup_list', []);
        
        // Sort by creation date (newest first)
        uasort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        wp_send_json_success($backups);
    }
    
    /**
     * Delete backup via AJAX
     */
    public function delete_backup(): void {
        check_ajax_referer('tmu_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $backup_id = sanitize_text_field($_POST['backup_id']);
        
        if (empty($backup_id)) {
            wp_send_json_error('Invalid backup ID');
        }
        
        try {
            $this->delete_backup_files($backup_id);
            
            $backups = get_option('tmu_backup_list', []);
            unset($backups[$backup_id]);
            update_option('tmu_backup_list', $backups);
            
            wp_send_json_success('Backup deleted successfully');
            
        } catch (Exception $e) {
            wp_send_json_error('Failed to delete backup: ' . $e->getMessage());
        }
    }
    
    /**
     * Restore backup
     */
    public function restore_backup(): void {
        check_ajax_referer('tmu_backup_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $backup_id = sanitize_text_field($_POST['backup_id']);
        $restore_options = $_POST['restore_options'] ?? [];
        
        try {
            $this->restore_backup_by_id($backup_id, $restore_options);
            wp_send_json_success('Backup restored successfully');
            
        } catch (Exception $e) {
            wp_send_json_error('Restore failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Restore backup by ID
     */
    private function restore_backup_by_id($backup_id, $options = []): void {
        $backup_path = $this->backup_dir . $backup_id . '/';
        
        if (!is_dir($backup_path)) {
            throw new Exception('Backup not found');
        }
        
        // Load backup metadata
        $metadata_file = $backup_path . 'metadata.json';
        if (!file_exists($metadata_file)) {
            throw new Exception('Backup metadata not found');
        }
        
        $metadata = json_decode(file_get_contents($metadata_file), true);
        
        // Restore database if requested
        if (!empty($options['restore_database']) && isset($metadata['files']['database'])) {
            $this->restore_database($backup_path . $metadata['files']['database']);
        }
        
        // Restore files if requested
        if (!empty($options['restore_files']) && isset($metadata['files']['theme'])) {
            $this->restore_files($backup_path . $metadata['files']['theme']);
        }
        
        // Restore TMU data if requested
        if (!empty($options['restore_tmu_data']) && isset($metadata['files']['tmu_data'])) {
            $this->restore_tmu_data($backup_path . $metadata['files']['tmu_data']);
        }
        
        // Clear caches after restore
        wp_cache_flush();
        
        // Log restore event
        $this->log_backup_event('restore', 'Backup restored successfully', [
            'backup_id' => $backup_id,
            'options' => $options
        ]);
    }
    
    /**
     * Restore database
     */
    private function restore_database($sql_file): void {
        if (!file_exists($sql_file)) {
            throw new Exception('Database backup file not found');
        }
        
        global $wpdb;
        
        $sql_content = file_get_contents($sql_file);
        $queries = explode(";\n", $sql_content);
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                $result = $wpdb->query($query);
                if ($result === false) {
                    throw new Exception('Database restore failed: ' . $wpdb->last_error);
                }
            }
        }
    }
    
    /**
     * Restore files
     */
    private function restore_files($archive_file): void {
        if (!file_exists($archive_file)) {
            throw new Exception('Files backup archive not found');
        }
        
        $zip = new ZipArchive();
        
        if ($zip->open($archive_file) !== TRUE) {
            throw new Exception('Cannot open backup archive');
        }
        
        $extract_path = WP_CONTENT_DIR . '/tmu-restore-temp/';
        wp_mkdir_p($extract_path);
        
        $zip->extractTo($extract_path);
        $zip->close();
        
        // Move files to their correct locations
        if (is_dir($extract_path . 'theme/')) {
            $theme_dir = get_template_directory();
            $this->copy_directory($extract_path . 'theme/', $theme_dir);
        }
        
        // Clean up temp directory
        $this->remove_directory($extract_path);
    }
    
    /**
     * Restore TMU data
     */
    private function restore_tmu_data($data_file): void {
        if (!file_exists($data_file)) {
            throw new Exception('TMU data backup file not found');
        }
        
        $data = json_decode(file_get_contents($data_file), true);
        
        if (isset($data['settings'])) {
            foreach ($data['settings'] as $option_name => $option_value) {
                update_option($option_name, $option_value);
            }
        }
    }
    
    /**
     * Copy directory
     */
    private function copy_directory($source, $destination): void {
        if (!is_dir($source)) {
            return;
        }
        
        if (!is_dir($destination)) {
            wp_mkdir_p($destination);
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $dest_path = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($file->isDir()) {
                wp_mkdir_p($dest_path);
            } else {
                copy($file->getRealPath(), $dest_path);
            }
        }
    }
    
    /**
     * Log backup event
     */
    private function log_backup_event($type, $message, $data = []): void {
        $log_entry = [
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ];
        
        // Log to file
        $log_file = $this->backup_dir . '../logs/backup.log';
        $log_line = date('Y-m-d H:i:s') . " [{$type}] {$message} " . json_encode($data) . "\n";
        file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
        
        // Log to WordPress
        error_log("TMU Backup [{$type}]: {$message}");
    }
    
    /**
     * Send backup alert
     */
    private function send_backup_alert($message): void {
        $alert_config = get_option('tmu_backup_alerts', []);
        
        if (!empty($alert_config['email'])) {
            wp_mail(
                $alert_config['email'],
                'TMU Backup Alert',
                $message,
                ['Content-Type: text/html; charset=UTF-8']
            );
        }
        
        if (!empty($alert_config['webhook_url'])) {
            wp_remote_post($alert_config['webhook_url'], [
                'body' => json_encode([
                    'type' => 'backup_alert',
                    'message' => $message,
                    'site' => get_site_url(),
                    'timestamp' => current_time('c')
                ]),
                'headers' => ['Content-Type' => 'application/json'],
                'timeout' => 10,
                'blocking' => false
            ]);
        }
    }
    
    /**
     * Get backup statistics
     */
    public function get_backup_statistics(): array {
        $backups = get_option('tmu_backup_list', []);
        
        $stats = [
            'total_backups' => count($backups),
            'total_size' => 0,
            'by_type' => [
                'daily' => 0,
                'weekly' => 0,
                'monthly' => 0,
                'manual' => 0
            ],
            'last_backup' => null,
            'oldest_backup' => null
        ];
        
        if (!empty($backups)) {
            foreach ($backups as $backup) {
                $stats['total_size'] += $backup['size'] ?? 0;
                $stats['by_type'][$backup['type']]++;
            }
            
            // Sort by date to find first and last
            $sorted_backups = $backups;
            uasort($sorted_backups, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            $stats['last_backup'] = reset($sorted_backups)['created_at'];
            $stats['oldest_backup'] = end($sorted_backups)['created_at'];
        }
        
        return $stats;
    }
}