<?php
/**
 * Update Manager
 * 
 * Automated theme update system for the TMU theme.
 * 
 * @package TMU\Updates
 * @since 1.0.0
 */

namespace TMU\Updates;

use TMU\Backup\BackupManager;
use TMU\Logging\LogManager;

class UpdateManager {
    
    /**
     * Update server URL
     * @var string
     */
    private $update_server_url = 'https://updates.tmu-theme.com/api/';
    
    /**
     * Current theme version
     * @var string
     */
    private $current_version;
    
    /**
     * Backup manager instance
     * @var BackupManager
     */
    private $backup_manager;
    
    /**
     * Logger instance
     * @var LogManager
     */
    private $logger;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->current_version = wp_get_theme()->get('Version');
        $this->backup_manager = new BackupManager();
        $this->logger = new LogManager();
        
        add_action('admin_init', [$this, 'check_for_updates']);
        add_action('wp_ajax_tmu_install_update', [$this, 'install_update']);
        add_action('wp_ajax_tmu_check_updates', [$this, 'manual_update_check']);
        add_action('tmu_check_for_updates', [$this, 'fetch_update_info']);
        
        // Hook into WordPress update system
        add_filter('pre_set_site_transient_update_themes', [$this, 'check_for_theme_update']);
        add_filter('upgrader_pre_download', [$this, 'download_package'], 10, 3);
        
        // Add admin notices
        add_action('admin_notices', [$this, 'update_admin_notices']);
        
        // Register update hooks
        add_action('upgrader_process_complete', [$this, 'theme_update_complete'], 10, 2);
    }
    
    /**
     * Check for updates (runs daily)
     */
    public function check_for_updates(): void {
        $last_check = get_option('tmu_last_update_check', 0);
        
        // Check for updates once per day
        if (time() - $last_check > 86400) {
            $this->fetch_update_info();
            update_option('tmu_last_update_check', time());
        }
    }
    
    /**
     * Fetch update information from server
     */
    public function fetch_update_info(): void {
        $this->logger->info('Checking for theme updates');
        
        try {
            $response = wp_remote_get($this->update_server_url . 'check-version', [
                'body' => [
                    'current_version' => $this->current_version,
                    'site_url' => get_site_url(),
                    'php_version' => PHP_VERSION,
                    'wp_version' => get_bloginfo('version'),
                    'theme_data' => $this->get_theme_data()
                ],
                'timeout' => 30
            ]);
            
            if (is_wp_error($response)) {
                $this->logger->warning('Update check failed', ['error' => $response->get_error_message()]);
                return;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            
            if ($status_code !== 200) {
                $this->logger->warning('Update server returned error', ['status_code' => $status_code]);
                return;
            }
            
            $update_data = json_decode(wp_remote_retrieve_body($response), true);
            
            if (!$update_data) {
                $this->logger->warning('Invalid update response format');
                return;
            }
            
            // Check if update is available
            if (isset($update_data['version']) && version_compare($update_data['version'], $this->current_version, '>')) {
                update_option('tmu_available_update', $update_data);
                $this->notify_admin_of_update($update_data);
                $this->logger->info('Update available', [
                    'current_version' => $this->current_version,
                    'new_version' => $update_data['version']
                ]);
            } else {
                delete_option('tmu_available_update');
                $this->logger->debug('No updates available');
            }
            
        } catch (Exception $e) {
            $this->logger->error('Update check failed', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get theme data for update check
     */
    private function get_theme_data(): array {
        $theme = wp_get_theme();
        
        return [
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'author' => $theme->get('Author'),
            'theme_uri' => $theme->get('ThemeURI'),
            'text_domain' => $theme->get('TextDomain'),
            'active_plugins' => get_option('active_plugins', []),
            'multisite' => is_multisite(),
            'locale' => get_locale()
        ];
    }
    
    /**
     * Notify admin of available update
     */
    private function notify_admin_of_update($update_data): void {
        // Set transient for admin notice
        set_transient('tmu_update_notice', $update_data, DAY_IN_SECONDS);
        
        // Send email notification if configured
        $notification_config = get_option('tmu_update_notifications', []);
        
        if (!empty($notification_config['email_updates'])) {
            $this->send_update_email_notification($update_data);
        }
        
        // Send webhook notification if configured
        if (!empty($notification_config['webhook_url'])) {
            $this->send_update_webhook_notification($update_data);
        }
    }
    
    /**
     * Send email notification about available update
     */
    private function send_update_email_notification($update_data): void {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf('[%s] TMU Theme Update Available - v%s', $site_name, $update_data['version']);
        
        $message = sprintf(
            "A new version of the TMU Theme is available for %s.\n\n" .
            "Current Version: %s\n" .
            "New Version: %s\n" .
            "Release Date: %s\n\n" .
            "What's New:\n%s\n\n" .
            "You can update the theme from your WordPress admin dashboard.\n\n" .
            "Update URL: %s\n\n" .
            "Note: A backup will be created automatically before the update.",
            $site_name,
            $this->current_version,
            $update_data['version'],
            $update_data['release_date'] ?? 'Not specified',
            $update_data['changelog'] ?? 'No changelog available',
            admin_url('themes.php')
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Send webhook notification about available update
     */
    private function send_update_webhook_notification($update_data): void {
        $notification_config = get_option('tmu_update_notifications', []);
        
        wp_remote_post($notification_config['webhook_url'], [
            'body' => json_encode([
                'type' => 'update_available',
                'site' => get_site_url(),
                'site_name' => get_bloginfo('name'),
                'current_version' => $this->current_version,
                'new_version' => $update_data['version'],
                'release_date' => $update_data['release_date'] ?? null,
                'changelog' => $update_data['changelog'] ?? null,
                'timestamp' => current_time('c')
            ]),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 10,
            'blocking' => false
        ]);
    }
    
    /**
     * Install update via AJAX
     */
    public function install_update(): void {
        check_ajax_referer('tmu_update_nonce', 'nonce');
        
        if (!current_user_can('update_themes')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $update_data = get_option('tmu_available_update');
        
        if (!$update_data) {
            wp_send_json_error('No update available');
        }
        
        try {
            $this->logger->info('Starting theme update', [
                'from_version' => $this->current_version,
                'to_version' => $update_data['version']
            ]);
            
            // Pre-update validation
            $this->validate_update_requirements($update_data);
            
            // Create backup before update
            $backup_id = $this->create_backup();
            
            // Download update package
            $package_path = $this->download_update_package($update_data);
            
            // Validate package integrity
            $this->validate_package_integrity($package_path, $update_data);
            
            // Extract and install update
            $this->extract_and_install_update($package_path, $update_data);
            
            // Run post-update tasks
            $this->run_post_update_tasks($update_data);
            
            // Clean up
            unlink($package_path);
            delete_option('tmu_available_update');
            delete_transient('tmu_update_notice');
            
            $this->logger->info('Theme update completed successfully', [
                'new_version' => $update_data['version'],
                'backup_id' => $backup_id
            ]);
            
            wp_send_json_success([
                'message' => 'Update installed successfully',
                'new_version' => $update_data['version'],
                'backup_id' => $backup_id
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('Theme update failed', ['error' => $e->getMessage()]);
            wp_send_json_error('Update failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate update requirements
     */
    private function validate_update_requirements($update_data): void {
        // Check PHP version requirement
        if (isset($update_data['requires_php']) && version_compare(PHP_VERSION, $update_data['requires_php'], '<')) {
            throw new Exception(sprintf(
                'Update requires PHP %s or higher. Current version: %s',
                $update_data['requires_php'],
                PHP_VERSION
            ));
        }
        
        // Check WordPress version requirement
        if (isset($update_data['requires_wp']) && version_compare(get_bloginfo('version'), $update_data['requires_wp'], '<')) {
            throw new Exception(sprintf(
                'Update requires WordPress %s or higher. Current version: %s',
                $update_data['requires_wp'],
                get_bloginfo('version')
            ));
        }
        
        // Check disk space
        $required_space = $update_data['package_size'] ?? 50 * 1024 * 1024; // Default 50MB
        $available_space = disk_free_space(get_template_directory());
        
        if ($available_space < $required_space * 2) { // Need double space for extraction
            throw new Exception('Insufficient disk space for update');
        }
        
        // Check write permissions
        if (!is_writable(get_template_directory())) {
            throw new Exception('Theme directory is not writable');
        }
    }
    
    /**
     * Create backup before update
     */
    private function create_backup(): string {
        $this->logger->info('Creating backup before update');
        
        $backup_id = $this->backup_manager->create_backup([
            'type' => 'pre_update',
            'include_files' => true,
            'include_database' => true,
            'include_media' => false
        ]);
        
        $this->logger->info('Pre-update backup created', ['backup_id' => $backup_id]);
        
        return $backup_id;
    }
    
    /**
     * Download update package
     */
    private function download_update_package($update_data): string {
        $package_url = $update_data['download_url'];
        
        if (empty($package_url)) {
            throw new Exception('No download URL provided');
        }
        
        $this->logger->info('Downloading update package', ['url' => $package_url]);
        
        // Download to temporary location
        $temp_file = download_url($package_url);
        
        if (is_wp_error($temp_file)) {
            throw new Exception('Failed to download update: ' . $temp_file->get_error_message());
        }
        
        return $temp_file;
    }
    
    /**
     * Validate package integrity
     */
    private function validate_package_integrity($package_path, $update_data): void {
        // Check file size
        $actual_size = filesize($package_path);
        $expected_size = $update_data['package_size'] ?? null;
        
        if ($expected_size && abs($actual_size - $expected_size) > 1024) { // Allow 1KB difference
            throw new Exception('Package size mismatch');
        }
        
        // Check hash if provided
        if (isset($update_data['package_hash'])) {
            $actual_hash = hash_file('sha256', $package_path);
            
            if ($actual_hash !== $update_data['package_hash']) {
                throw new Exception('Package hash validation failed');
            }
        }
        
        // Check if it's a valid ZIP file
        $zip = new ZipArchive();
        $result = $zip->open($package_path, ZipArchive::CHECKCONS);
        
        if ($result !== TRUE) {
            throw new Exception('Invalid package format');
        }
        
        $zip->close();
    }
    
    /**
     * Extract and install update
     */
    private function extract_and_install_update($package_path, $update_data): void {
        $theme_dir = get_template_directory();
        $temp_dir = sys_get_temp_dir() . '/tmu-update-' . time();
        
        $this->logger->info('Extracting update package');
        
        // Extract package to temporary directory
        $zip = new ZipArchive();
        
        if ($zip->open($package_path) !== TRUE) {
            throw new Exception('Failed to open update package');
        }
        
        if (!$zip->extractTo($temp_dir)) {
            $zip->close();
            throw new Exception('Failed to extract update package');
        }
        
        $zip->close();
        
        // Validate extracted contents
        $this->validate_extracted_contents($temp_dir);
        
        // Create backup of current theme (additional safety)
        $backup_dir = $theme_dir . '_backup_' . time();
        $this->copy_directory($theme_dir, $backup_dir);
        
        try {
            // Copy new files to theme directory
            $this->logger->info('Installing update files');
            $this->copy_directory($temp_dir, $theme_dir);
            
            // Remove backup directory if successful
            $this->remove_directory($backup_dir);
            
        } catch (Exception $e) {
            // Restore from backup on failure
            $this->logger->error('Update installation failed, restoring backup');
            $this->remove_directory($theme_dir);
            rename($backup_dir, $theme_dir);
            throw $e;
        } finally {
            // Clean up temp directory
            $this->remove_directory($temp_dir);
        }
    }
    
    /**
     * Validate extracted contents
     */
    private function validate_extracted_contents($temp_dir): void {
        // Check for required files
        $required_files = [
            'style.css',
            'functions.php',
            'index.php'
        ];
        
        foreach ($required_files as $file) {
            if (!file_exists($temp_dir . '/' . $file)) {
                throw new Exception("Required file missing: {$file}");
            }
        }
        
        // Validate style.css header
        $style_css = file_get_contents($temp_dir . '/style.css');
        
        if (!preg_match('/Theme Name:\s*TMU\s*Theme/i', $style_css)) {
            throw new Exception('Invalid theme package');
        }
    }
    
    /**
     * Run post-update tasks
     */
    private function run_post_update_tasks($update_data): void {
        $this->logger->info('Running post-update tasks');
        
        // Run database migrations if needed
        if (isset($update_data['migrations']) && is_array($update_data['migrations'])) {
            foreach ($update_data['migrations'] as $migration) {
                $this->run_migration($migration);
            }
        }
        
        // Clear all caches
        wp_cache_flush();
        
        // Clear any compiled CSS/JS
        $this->clear_compiled_assets();
        
        // Update theme version
        $this->current_version = $update_data['version'];
        update_option('tmu_theme_version', $this->current_version);
        
        // Update last update time
        update_option('tmu_last_update_time', current_time('mysql'));
        
        // Trigger update complete action
        do_action('tmu_theme_updated', $update_data['version'], $update_data);
        
        // Send update completion notification
        $this->send_update_completion_notification($update_data);
    }
    
    /**
     * Run database migration
     */
    private function run_migration($migration): void {
        $this->logger->info('Running migration', ['migration' => $migration]);
        
        try {
            do_action('tmu_run_migration', $migration);
        } catch (Exception $e) {
            $this->logger->error('Migration failed', [
                'migration' => $migration,
                'error' => $e->getMessage()
            ]);
            throw new Exception("Migration failed: {$migration}");
        }
    }
    
    /**
     * Clear compiled assets
     */
    private function clear_compiled_assets(): void {
        $asset_dirs = [
            get_template_directory() . '/assets/dist/',
            get_template_directory() . '/assets/build/',
            get_template_directory() . '/dist/'
        ];
        
        foreach ($asset_dirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '*.{css,js,map}', GLOB_BRACE);
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }
    }
    
    /**
     * Send update completion notification
     */
    private function send_update_completion_notification($update_data): void {
        $notification_config = get_option('tmu_update_notifications', []);
        
        if (!empty($notification_config['email_updates'])) {
            $admin_email = get_option('admin_email');
            $site_name = get_bloginfo('name');
            
            $subject = sprintf('[%s] TMU Theme Updated Successfully - v%s', $site_name, $update_data['version']);
            
            $message = sprintf(
                "The TMU Theme has been successfully updated on %s.\n\n" .
                "New Version: %s\n" .
                "Update Time: %s\n\n" .
                "A backup was created before the update and can be restored if needed.\n\n" .
                "Visit your site: %s",
                $site_name,
                $update_data['version'],
                current_time('Y-m-d H:i:s'),
                home_url()
            );
            
            wp_mail($admin_email, $subject, $message);
        }
        
        if (!empty($notification_config['webhook_url'])) {
            wp_remote_post($notification_config['webhook_url'], [
                'body' => json_encode([
                    'type' => 'update_completed',
                    'site' => get_site_url(),
                    'site_name' => get_bloginfo('name'),
                    'new_version' => $update_data['version'],
                    'update_time' => current_time('c'),
                    'timestamp' => current_time('c')
                ]),
                'headers' => ['Content-Type' => 'application/json'],
                'timeout' => 10,
                'blocking' => false
            ]);
        }
    }
    
    /**
     * Add directory to ZIP exactly as documented
     */
    private function add_directory_to_zip($zip, $dir, $zipPath): void {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipPath . substr($filePath, strlen($dir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    /**
     * Copy directory recursively
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
     * Manual update check via AJAX
     */
    public function manual_update_check(): void {
        check_ajax_referer('tmu_update_nonce', 'nonce');
        
        if (!current_user_can('update_themes')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $this->fetch_update_info();
        
        $update_data = get_option('tmu_available_update');
        
        if ($update_data) {
            wp_send_json_success([
                'update_available' => true,
                'current_version' => $this->current_version,
                'new_version' => $update_data['version'],
                'changelog' => $update_data['changelog'] ?? '',
                'release_date' => $update_data['release_date'] ?? ''
            ]);
        } else {
            wp_send_json_success([
                'update_available' => false,
                'message' => 'No updates available'
            ]);
        }
    }
    
    /**
     * Hook into WordPress theme update system
     */
    public function check_for_theme_update($transient): object {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $theme_slug = get_option('stylesheet');
        $update_data = get_option('tmu_available_update');
        
        if ($update_data && version_compare($update_data['version'], $this->current_version, '>')) {
            $transient->response[$theme_slug] = [
                'theme' => $theme_slug,
                'new_version' => $update_data['version'],
                'url' => $update_data['details_url'] ?? '',
                'package' => $update_data['download_url'] ?? ''
            ];
        }
        
        return $transient;
    }
    
    /**
     * Download package for WordPress updater
     */
    public function download_package($result, $package, $upgrader): string {
        // Only handle TMU theme updates
        if (strpos($package, 'updates.tmu-theme.com') === false) {
            return $result;
        }
        
        $update_data = get_option('tmu_available_update');
        
        if (!$update_data) {
            return new WP_Error('no_update_data', 'No update data available');
        }
        
        try {
            // Create backup before download
            $this->create_backup();
            
            // Download package
            return $this->download_update_package($update_data);
            
        } catch (Exception $e) {
            return new WP_Error('download_failed', $e->getMessage());
        }
    }
    
    /**
     * Theme update completion hook
     */
    public function theme_update_complete($upgrader, $extra): void {
        if ($extra['type'] !== 'theme' || $extra['action'] !== 'update') {
            return;
        }
        
        $theme_slug = get_option('stylesheet');
        
        if (isset($extra['themes']) && in_array($theme_slug, $extra['themes'])) {
            $update_data = get_option('tmu_available_update');
            
            if ($update_data) {
                $this->run_post_update_tasks($update_data);
                delete_option('tmu_available_update');
                delete_transient('tmu_update_notice');
            }
        }
    }
    
    /**
     * Display admin notices for updates
     */
    public function update_admin_notices(): void {
        $update_notice = get_transient('tmu_update_notice');
        
        if (!$update_notice || !current_user_can('update_themes')) {
            return;
        }
        
        $message = sprintf(
            'A new version of TMU Theme is available. <strong>Version %s</strong> is now available. <a href="%s">Please update now</a>.',
            $update_notice['version'],
            admin_url('themes.php')
        );
        
        printf('<div class="notice notice-warning is-dismissible"><p>%s</p></div>', $message);
    }
    
    /**
     * Get update history
     */
    public function get_update_history(): array {
        return get_option('tmu_update_history', []);
    }
    
    /**
     * Add update to history
     */
    private function add_to_update_history($update_data): void {
        $history = $this->get_update_history();
        
        $history[] = [
            'version' => $update_data['version'],
            'updated_at' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'changelog' => $update_data['changelog'] ?? ''
        ];
        
        // Keep only last 20 updates
        $history = array_slice($history, -20);
        
        update_option('tmu_update_history', $history);
    }
    
    /**
     * Rollback to previous version
     */
    public function rollback_update($backup_id): bool {
        if (!current_user_can('update_themes')) {
            return false;
        }
        
        try {
            $this->logger->info('Rolling back theme update', ['backup_id' => $backup_id]);
            
            // Restore from backup
            $this->backup_manager->restore_backup_by_id($backup_id, [
                'restore_files' => true,
                'restore_database' => true,
                'restore_tmu_data' => true
            ]);
            
            $this->logger->info('Theme rollback completed');
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Theme rollback failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Enable automatic updates
     */
    public function enable_automatic_updates(): void {
        update_option('tmu_auto_updates_enabled', true);
        
        // Schedule automatic update check
        if (!wp_next_scheduled('tmu_auto_update_check')) {
            wp_schedule_event(time(), 'twicedaily', 'tmu_auto_update_check');
        }
        
        add_action('tmu_auto_update_check', [$this, 'auto_update_check']);
    }
    
    /**
     * Disable automatic updates
     */
    public function disable_automatic_updates(): void {
        update_option('tmu_auto_updates_enabled', false);
        wp_clear_scheduled_hook('tmu_auto_update_check');
    }
    
    /**
     * Automatic update check
     */
    public function auto_update_check(): void {
        if (!get_option('tmu_auto_updates_enabled', false)) {
            return;
        }
        
        $this->fetch_update_info();
        
        $update_data = get_option('tmu_available_update');
        
        if ($update_data) {
            // Only auto-update for patch releases or if specifically enabled for minor updates
            $auto_update_level = get_option('tmu_auto_update_level', 'patch');
            
            if ($this->should_auto_update($update_data, $auto_update_level)) {
                $this->logger->info('Starting automatic update', ['version' => $update_data['version']]);
                
                try {
                    $this->install_update_silently($update_data);
                } catch (Exception $e) {
                    $this->logger->error('Automatic update failed', ['error' => $e->getMessage()]);
                }
            }
        }
    }
    
    /**
     * Check if should auto-update based on level
     */
    private function should_auto_update($update_data, $level): bool {
        $current_parts = explode('.', $this->current_version);
        $new_parts = explode('.', $update_data['version']);
        
        switch ($level) {
            case 'patch':
                // Only patch releases (x.x.X)
                return $current_parts[0] === $new_parts[0] && $current_parts[1] === $new_parts[1];
                
            case 'minor':
                // Minor and patch releases (x.X.x)
                return $current_parts[0] === $new_parts[0];
                
            case 'major':
                // All releases
                return true;
                
            default:
                return false;
        }
    }
    
    /**
     * Install update silently (for automatic updates)
     */
    private function install_update_silently($update_data): void {
        // Same as install_update but without AJAX responses
        $this->validate_update_requirements($update_data);
        $backup_id = $this->create_backup();
        $package_path = $this->download_update_package($update_data);
        $this->validate_package_integrity($package_path, $update_data);
        $this->extract_and_install_update($package_path, $update_data);
        $this->run_post_update_tasks($update_data);
        
        unlink($package_path);
        delete_option('tmu_available_update');
        delete_transient('tmu_update_notice');
        
        $this->add_to_update_history($update_data);
    }
}