# Step 18: Maintenance and Updates

## Purpose
Implement automated maintenance, update systems, backup procedures, and long-term theme management strategies.

## Dependencies from Previous Steps
- **[REQUIRED]** Complete theme system [FROM STEPS 1-17] - Maintenance targets
- **[REQUIRED]** Database system [FROM STEP 3] - Backup and migration
- **[REQUIRED]** TMDB API [FROM STEP 9] - API updates and maintenance

## Files Created in This Step
- **[CREATE NEW]** `includes/classes/Maintenance/MaintenanceManager.php` - Maintenance coordinator
- **[CREATE NEW]** `includes/classes/Updates/UpdateManager.php` - Update system
- **[CREATE NEW]** `includes/classes/Backup/BackupManager.php` - Backup system
- **[CREATE NEW]** `includes/classes/Migration/MigrationManager.php` - Data migration
- **[CREATE NEW]** `maintenance/` - Maintenance scripts directory

## Tailwind CSS Status
**MAINTAINS** - Maintenance includes Tailwind CSS updates and optimization

**Step 18 Status**: ✅ READY FOR AI IMPLEMENTATION
**Dependencies**: Steps 1-17 must be completed
**Next Step**: Step 19 - Final Implementation Guide

## Overview
This step establishes comprehensive maintenance procedures, update strategies, and lifecycle management for the TMU theme to ensure long-term stability, security, and performance.

## 1. Automated Maintenance System

### 1.1 Maintenance Scheduler
```php
// src/Maintenance/MaintenanceScheduler.php
<?php
namespace TMU\Maintenance;

class MaintenanceScheduler {
    public function __construct() {
        add_action('init', [$this, 'schedule_maintenance_tasks']);
        add_action('tmu_daily_maintenance', [$this, 'run_daily_maintenance']);
        add_action('tmu_weekly_maintenance', [$this, 'run_weekly_maintenance']);
        add_action('tmu_monthly_maintenance', [$this, 'run_monthly_maintenance']);
    }
    
    public function schedule_maintenance_tasks(): void {
        // Schedule daily maintenance
        if (!wp_next_scheduled('tmu_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'tmu_daily_maintenance');
        }
        
        // Schedule weekly maintenance
        if (!wp_next_scheduled('tmu_weekly_maintenance')) {
            wp_schedule_event(time(), 'weekly', 'tmu_weekly_maintenance');
        }
        
        // Schedule monthly maintenance
        if (!wp_next_scheduled('tmu_monthly_maintenance')) {
            wp_schedule_event(time(), 'monthly', 'tmu_monthly_maintenance');
        }
    }
    
    public function run_daily_maintenance(): void {
        $this->log_maintenance_start('daily');
        
        try {
            // Clean up temporary files
            $this->cleanup_temporary_files();
            
            // Optimize database tables
            $this->optimize_database_tables();
            
            // Clean expired cache
            $this->clean_expired_cache();
            
            // Update TMDB data for recently viewed content
            $this->update_popular_content_data();
            
            // Generate performance reports
            $this->generate_daily_performance_report();
            
            $this->log_maintenance_complete('daily', 'success');
            
        } catch (Exception $e) {
            $this->log_maintenance_complete('daily', 'error', $e->getMessage());
            $this->send_maintenance_alert('Daily maintenance failed: ' . $e->getMessage());
        }
    }
    
    public function run_weekly_maintenance(): void {
        $this->log_maintenance_start('weekly');
        
        try {
            // Deep database optimization
            $this->deep_database_optimization();
            
            // Update all TMDB data
            $this->update_all_tmdb_data();
            
            // Clean up old analytics data
            $this->cleanup_old_analytics_data();
            
            // Generate weekly reports
            $this->generate_weekly_reports();
            
            // Check for theme updates
            $this->check_for_updates();
            
            $this->log_maintenance_complete('weekly', 'success');
            
        } catch (Exception $e) {
            $this->log_maintenance_complete('weekly', 'error', $e->getMessage());
            $this->send_maintenance_alert('Weekly maintenance failed: ' . $e->getMessage());
        }
    }
    
    public function run_monthly_maintenance(): void {
        $this->log_maintenance_start('monthly');
        
        try {
            // Archive old data
            $this->archive_old_data();
            
            // Security audit
            $this->run_security_audit();
            
            // Performance audit
            $this->run_performance_audit();
            
            // Generate monthly reports
            $this->generate_monthly_reports();
            
            // Backup critical data
            $this->create_monthly_backup();
            
            $this->log_maintenance_complete('monthly', 'success');
            
        } catch (Exception $e) {
            $this->log_maintenance_complete('monthly', 'error', $e->getMessage());
            $this->send_maintenance_alert('Monthly maintenance failed: ' . $e->getMessage());
        }
    }
    
    private function cleanup_temporary_files(): void {
        $temp_dir = get_template_directory() . '/temp/';
        
        if (is_dir($temp_dir)) {
            $files = glob($temp_dir . '*');
            $cutoff_time = time() - (24 * 60 * 60); // 24 hours ago
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff_time) {
                    unlink($file);
                }
            }
        }
    }
    
    private function optimize_database_tables(): void {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'tmu_movies',
            $wpdb->prefix . 'tmu_tv_series',
            $wpdb->prefix . 'tmu_dramas',
            $wpdb->prefix . 'tmu_people',
            $wpdb->prefix . 'tmu_analytics_events'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
    }
    
    private function update_popular_content_data(): void {
        // Get most viewed content from last 7 days
        global $wpdb;
        
        $popular_content = $wpdb->get_results(
            "SELECT content_id, COUNT(*) as views 
             FROM {$wpdb->prefix}tmu_analytics_events 
             WHERE event_type = 'page_view' 
             AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY content_id 
             ORDER BY views DESC 
             LIMIT 50"
        );
        
        foreach ($popular_content as $content) {
            // Update TMDB data for popular content
            do_action('tmu_update_tmdb_data', $content->content_id);
        }
    }
    
    private function generate_daily_performance_report(): void {
        $report_generator = new \TMU\Reports\PerformanceReportGenerator();
        $report_generator->generate_daily_report();
    }
}
```

### 1.2 Update Manager
```php
// src/Maintenance/UpdateManager.php
<?php
namespace TMU\Maintenance;

class UpdateManager {
    private $update_server_url = 'https://updates.tmu-theme.com/api/';
    private $current_version;
    
    public function __construct() {
        $this->current_version = wp_get_theme()->get('Version');
        add_action('admin_init', [$this, 'check_for_updates']);
        add_action('wp_ajax_tmu_install_update', [$this, 'install_update']);
    }
    
    public function check_for_updates(): void {
        $last_check = get_option('tmu_last_update_check', 0);
        
        // Check for updates once per day
        if (time() - $last_check > 86400) {
            $this->fetch_update_info();
            update_option('tmu_last_update_check', time());
        }
    }
    
    private function fetch_update_info(): void {
        $response = wp_remote_get($this->update_server_url . 'check-version', [
            'body' => [
                'current_version' => $this->current_version,
                'site_url' => get_site_url(),
                'php_version' => PHP_VERSION,
                'wp_version' => get_bloginfo('version')
            ]
        ]);
        
        if (!is_wp_error($response)) {
            $update_data = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($update_data && version_compare($update_data['version'], $this->current_version, '>')) {
                update_option('tmu_available_update', $update_data);
                $this->notify_admin_of_update($update_data);
            }
        }
    }
    
    public function install_update(): void {
        check_ajax_referer('tmu_update_nonce', 'nonce');
        
        if (!current_user_can('update_themes')) {
            wp_die('Insufficient permissions');
        }
        
        $update_data = get_option('tmu_available_update');
        
        if (!$update_data) {
            wp_send_json_error('No update available');
        }
        
        try {
            // Create backup before update
            $this->create_backup();
            
            // Download update package
            $package_url = $update_data['download_url'];
            $temp_file = download_url($package_url);
            
            if (is_wp_error($temp_file)) {
                throw new Exception('Failed to download update: ' . $temp_file->get_error_message());
            }
            
            // Extract and install update
            $this->extract_and_install_update($temp_file, $update_data);
            
            // Clean up
            unlink($temp_file);
            delete_option('tmu_available_update');
            
            // Run post-update tasks
            $this->run_post_update_tasks($update_data);
            
            wp_send_json_success([
                'message' => 'Update installed successfully',
                'new_version' => $update_data['version']
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('Update failed: ' . $e->getMessage());
        }
    }
    
    private function create_backup(): void {
        $backup_dir = WP_CONTENT_DIR . '/backups/tmu-theme/';
        
        if (!is_dir($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        $backup_file = $backup_dir . 'backup-' . date('Y-m-d-H-i-s') . '.zip';
        $theme_dir = get_template_directory();
        
        // Create ZIP backup
        $zip = new ZipArchive();
        
        if ($zip->open($backup_file, ZipArchive::CREATE) === TRUE) {
            $this->add_directory_to_zip($zip, $theme_dir, '');
            $zip->close();
        }
    }
    
    private function extract_and_install_update($temp_file, $update_data): void {
        $theme_dir = get_template_directory();
        $temp_dir = sys_get_temp_dir() . '/tmu-update-' . time();
        
        // Extract update package
        $zip = new ZipArchive();
        
        if ($zip->open($temp_file) === TRUE) {
            $zip->extractTo($temp_dir);
            $zip->close();
        } else {
            throw new Exception('Failed to extract update package');
        }
        
        // Copy files to theme directory
        $this->copy_directory($temp_dir, $theme_dir);
        
        // Clean up temp directory
        $this->remove_directory($temp_dir);
    }
    
    private function run_post_update_tasks($update_data): void {
        // Run database migrations if needed
        if (isset($update_data['migrations'])) {
            foreach ($update_data['migrations'] as $migration) {
                do_action('tmu_run_migration', $migration);
            }
        }
        
        // Clear caches
        wp_cache_flush();
        
        // Update version number
        $this->current_version = $update_data['version'];
        
        // Log update
        error_log("TMU Theme updated to version {$update_data['version']}");
    }
}
```

## 2. Database Maintenance

### 2.1 Database Optimizer
```php
// src/Maintenance/DatabaseOptimizer.php
<?php
namespace TMU\Maintenance;

class DatabaseOptimizer {
    public function __construct() {
        add_action('tmu_optimize_database', [$this, 'optimize_database']);
    }
    
    public function optimize_database(): void {
        $this->optimize_tables();
        $this->cleanup_orphaned_data();
        $this->update_statistics();
        $this->analyze_performance();
    }
    
    private function optimize_tables(): void {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'tmu_movies',
            $wpdb->prefix . 'tmu_tv_series',
            $wpdb->prefix . 'tmu_dramas',
            $wpdb->prefix . 'tmu_people',
            $wpdb->prefix . 'tmu_analytics_events',
            $wpdb->prefix . 'tmu_performance_logs',
            $wpdb->prefix . 'tmu_error_logs'
        ];
        
        foreach ($tables as $table) {
            // Optimize table
            $wpdb->query("OPTIMIZE TABLE {$table}");
            
            // Analyze table for better query performance
            $wpdb->query("ANALYZE TABLE {$table}");
            
            // Check table integrity
            $check_result = $wpdb->get_results("CHECK TABLE {$table}");
            
            foreach ($check_result as $result) {
                if ($result->Msg_text !== 'OK') {
                    error_log("Database table issue: {$table} - {$result->Msg_text}");
                }
            }
        }
    }
    
    private function cleanup_orphaned_data(): void {
        global $wpdb;
        
        // Remove orphaned movie data
        $wpdb->query("
            DELETE m FROM {$wpdb->prefix}tmu_movies m
            LEFT JOIN {$wpdb->posts} p ON m.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        // Remove orphaned TV series data
        $wpdb->query("
            DELETE t FROM {$wpdb->prefix}tmu_tv_series t
            LEFT JOIN {$wpdb->posts} p ON t.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        // Remove orphaned drama data
        $wpdb->query("
            DELETE d FROM {$wpdb->prefix}tmu_dramas d
            LEFT JOIN {$wpdb->posts} p ON d.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        // Remove orphaned people data
        $wpdb->query("
            DELETE pe FROM {$wpdb->prefix}tmu_people pe
            LEFT JOIN {$wpdb->posts} p ON pe.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        // Clean up old analytics events (older than 1 year)
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}tmu_analytics_events
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ");
        
        // Clean up old performance logs (older than 3 months)
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}tmu_performance_logs
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 3 MONTH)
        ");
        
        // Clean up old error logs (older than 6 months)
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}tmu_error_logs
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 6 MONTH)
        ");
    }
    
    private function update_statistics(): void {
        global $wpdb;
        
        // Update content statistics
        $stats = [
            'total_movies' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_movies"),
            'total_tv_series' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_tv_series"),
            'total_dramas' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_dramas"),
            'total_people' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tmu_people"),
            'last_updated' => current_time('mysql')
        ];
        
        update_option('tmu_content_statistics', $stats);
    }
    
    private function analyze_performance(): void {
        global $wpdb;
        
        // Get slow queries
        $slow_queries = $wpdb->get_results("
            SELECT query, avg_timer_wait, count_star
            FROM performance_schema.events_statements_summary_by_digest
            WHERE avg_timer_wait > 1000000000
            AND query LIKE '%tmu_%'
            ORDER BY avg_timer_wait DESC
            LIMIT 10
        ");
        
        if (!empty($slow_queries)) {
            $report = "Slow TMU queries detected:\n";
            foreach ($slow_queries as $query) {
                $report .= "Query: {$query->query}\n";
                $report .= "Avg Time: " . ($query->avg_timer_wait / 1000000000) . "s\n";
                $report .= "Count: {$query->count_star}\n\n";
            }
            
            error_log($report);
        }
    }
}
```

## 3. Content Maintenance

### 3.1 TMDB Data Updater
```php
// src/Maintenance/TmdbDataUpdater.php
<?php
namespace TMU\Maintenance;

class TmdbDataUpdater {
    private $api_key;
    private $batch_size = 50;
    
    public function __construct() {
        $this->api_key = get_option('tmu_tmdb_api_key');
        add_action('tmu_update_tmdb_data', [$this, 'update_content_data']);
    }
    
    public function update_all_content(): void {
        $this->update_movies();
        $this->update_tv_series();
        $this->update_dramas();
        $this->update_people();
    }
    
    private function update_movies(): void {
        global $wpdb;
        
        // Get movies that need updating (older than 30 days)
        $movies = $wpdb->get_results("
            SELECT post_id, tmdb_id 
            FROM {$wpdb->prefix}tmu_movies 
            WHERE tmdb_id IS NOT NULL 
            AND (last_updated IS NULL OR last_updated < DATE_SUB(NOW(), INTERVAL 30 DAY))
            LIMIT {$this->batch_size}
        ");
        
        foreach ($movies as $movie) {
            $this->update_movie_data($movie->post_id, $movie->tmdb_id);
            
            // Rate limiting - TMDB allows 40 requests per 10 seconds
            usleep(250000); // 0.25 seconds delay
        }
    }
    
    private function update_movie_data($post_id, $tmdb_id): void {
        $api_url = "https://api.themoviedb.org/3/movie/{$tmdb_id}?api_key={$this->api_key}&append_to_response=credits,images,videos,reviews,similar,recommendations";
        
        $response = wp_remote_get($api_url);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($data) {
                $this->save_movie_data($post_id, $data);
            }
        }
    }
    
    private function save_movie_data($post_id, $data): void {
        global $wpdb;
        
        $update_data = [
            'title' => $data['title'] ?? null,
            'original_title' => $data['original_title'] ?? null,
            'tagline' => $data['tagline'] ?? null,
            'overview' => $data['overview'] ?? null,
            'runtime' => $data['runtime'] ?? null,
            'release_date' => $data['release_date'] ?? null,
            'status' => $data['status'] ?? null,
            'budget' => $data['budget'] ?? null,
            'revenue' => $data['revenue'] ?? null,
            'homepage' => $data['homepage'] ?? null,
            'poster_path' => $data['poster_path'] ?? null,
            'backdrop_path' => $data['backdrop_path'] ?? null,
            'tmdb_vote_average' => $data['vote_average'] ?? null,
            'tmdb_vote_count' => $data['vote_count'] ?? null,
            'tmdb_popularity' => $data['popularity'] ?? null,
            'adult' => $data['adult'] ?? false,
            'video' => $data['video'] ?? false,
            'belongs_to_collection' => isset($data['belongs_to_collection']) ? json_encode($data['belongs_to_collection']) : null,
            'production_companies' => isset($data['production_companies']) ? json_encode($data['production_companies']) : null,
            'production_countries' => isset($data['production_countries']) ? json_encode($data['production_countries']) : null,
            'spoken_languages' => isset($data['spoken_languages']) ? json_encode($data['spoken_languages']) : null,
            'credits' => isset($data['credits']) ? json_encode($data['credits']) : null,
            'images' => isset($data['images']) ? json_encode($data['images']) : null,
            'videos' => isset($data['videos']) ? json_encode($data['videos']) : null,
            'reviews' => isset($data['reviews']) ? json_encode($data['reviews']) : null,
            'similar' => isset($data['similar']) ? json_encode($data['similar']) : null,
            'recommendations' => isset($data['recommendations']) ? json_encode($data['recommendations']) : null,
            'last_updated' => current_time('mysql')
        ];
        
        $wpdb->update(
            $wpdb->prefix . 'tmu_movies',
            $update_data,
            ['post_id' => $post_id]
        );
        
        // Update post title if needed
        if ($data['title']) {
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $data['title']
            ]);
        }
    }
}
```

## 4. Security Maintenance

### 4.1 Security Auditor
```php
// src/Maintenance/SecurityAuditor.php
<?php
namespace TMU\Maintenance;

class SecurityAuditor {
    public function __construct() {
        add_action('tmu_security_audit', [$this, 'run_security_audit']);
    }
    
    public function run_security_audit(): void {
        $audit_results = [
            'file_permissions' => $this->check_file_permissions(),
            'vulnerable_files' => $this->scan_for_vulnerable_files(),
            'outdated_dependencies' => $this->check_dependencies(),
            'security_headers' => $this->check_security_headers(),
            'database_security' => $this->check_database_security(),
            'user_access' => $this->audit_user_access()
        ];
        
        $this->generate_security_report($audit_results);
        
        // Send alerts for critical issues
        $this->check_for_critical_issues($audit_results);
    }
    
    private function check_file_permissions(): array {
        $results = [];
        $theme_dir = get_template_directory();
        
        // Check critical files
        $critical_files = [
            'functions.php',
            'style.css',
            'index.php'
        ];
        
        foreach ($critical_files as $file) {
            $file_path = $theme_dir . '/' . $file;
            if (file_exists($file_path)) {
                $perms = substr(sprintf('%o', fileperms($file_path)), -4);
                $results[$file] = [
                    'permissions' => $perms,
                    'secure' => in_array($perms, ['0644', '0604'])
                ];
            }
        }
        
        return $results;
    }
    
    private function scan_for_vulnerable_files(): array {
        $vulnerable_patterns = [
            'eval\s*\(',
            'base64_decode\s*\(',
            'file_get_contents\s*\(\s*["\']http',
            'system\s*\(',
            'exec\s*\(',
            'shell_exec\s*\(',
            'passthru\s*\('
        ];
        
        $results = [];
        $theme_dir = get_template_directory();
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($theme_dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                
                foreach ($vulnerable_patterns as $pattern) {
                    if (preg_match('/' . $pattern . '/i', $content)) {
                        $results[] = [
                            'file' => $file->getPathname(),
                            'pattern' => $pattern,
                            'line' => $this->find_pattern_line($content, $pattern)
                        ];
                    }
                }
            }
        }
        
        return $results;
    }
    
    private function check_dependencies(): array {
        $composer_file = get_template_directory() . '/composer.json';
        
        if (!file_exists($composer_file)) {
            return ['status' => 'no_composer'];
        }
        
        $composer_data = json_decode(file_get_contents($composer_file), true);
        $outdated = [];
        
        // Check for known vulnerable packages
        $vulnerable_packages = [
            'symfony/http-foundation' => '< 4.4.7',
            'twig/twig' => '< 2.14.11'
        ];
        
        foreach ($vulnerable_packages as $package => $vulnerable_version) {
            if (isset($composer_data['require'][$package])) {
                $current_version = $composer_data['require'][$package];
                if (version_compare($current_version, $vulnerable_version, '<')) {
                    $outdated[] = [
                        'package' => $package,
                        'current' => $current_version,
                        'vulnerable' => $vulnerable_version
                    ];
                }
            }
        }
        
        return $outdated;
    }
    
    private function check_security_headers(): array {
        $test_url = home_url();
        $response = wp_remote_get($test_url);
        
        if (is_wp_error($response)) {
            return ['status' => 'error', 'message' => $response->get_error_message()];
        }
        
        $headers = wp_remote_retrieve_headers($response);
        
        $required_headers = [
            'X-Frame-Options',
            'X-XSS-Protection',
            'X-Content-Type-Options',
            'Strict-Transport-Security',
            'Content-Security-Policy'
        ];
        
        $results = [];
        
        foreach ($required_headers as $header) {
            $results[$header] = [
                'present' => isset($headers[$header]),
                'value' => $headers[$header] ?? null
            ];
        }
        
        return $results;
    }
    
    private function generate_security_report($audit_results): void {
        $report = "TMU Theme Security Audit Report\n";
        $report .= "Generated: " . current_time('Y-m-d H:i:s') . "\n\n";
        
        // File permissions
        $report .= "File Permissions:\n";
        foreach ($audit_results['file_permissions'] as $file => $data) {
            $status = $data['secure'] ? 'SECURE' : 'INSECURE';
            $report .= "  {$file}: {$data['permissions']} - {$status}\n";
        }
        
        // Vulnerable files
        if (!empty($audit_results['vulnerable_files'])) {
            $report .= "\nPotentially Vulnerable Code:\n";
            foreach ($audit_results['vulnerable_files'] as $vuln) {
                $report .= "  {$vuln['file']}:{$vuln['line']} - {$vuln['pattern']}\n";
            }
        }
        
        // Save report
        $report_file = WP_CONTENT_DIR . '/uploads/tmu-security-audit-' . date('Y-m-d') . '.txt';
        file_put_contents($report_file, $report);
    }
}
```

## 5. Performance Maintenance

### 5.1 Performance Optimizer
```php
// src/Maintenance/PerformanceOptimizer.php
<?php
namespace TMU\Maintenance;

class PerformanceOptimizer {
    public function __construct() {
        add_action('tmu_performance_optimization', [$this, 'optimize_performance']);
    }
    
    public function optimize_performance(): void {
        $this->optimize_images();
        $this->optimize_css_js();
        $this->clean_cache();
        $this->optimize_database_queries();
        $this->generate_performance_report();
    }
    
    private function optimize_images(): void {
        $upload_dir = wp_upload_dir();
        $image_dir = $upload_dir['basedir'];
        
        // Find unoptimized images
        $images = glob($image_dir . '/**/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        
        foreach ($images as $image) {
            // Check if image needs optimization
            if ($this->needs_optimization($image)) {
                $this->optimize_image($image);
            }
        }
    }
    
    private function needs_optimization($image_path): bool {
        $file_size = filesize($image_path);
        $image_info = getimagesize($image_path);
        
        if (!$image_info) {
            return false;
        }
        
        // Optimize if file is larger than 500KB or dimensions are very large
        return $file_size > 512000 || $image_info[0] > 2000 || $image_info[1] > 2000;
    }
    
    private function optimize_image($image_path): void {
        $image_info = getimagesize($image_path);
        
        if (!$image_info) {
            return;
        }
        
        $mime_type = $image_info['mime'];
        
        switch ($mime_type) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($image_path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($image_path);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($image_path);
                break;
            default:
                return;
        }
        
        if ($image) {
            // Resize if too large
            if ($image_info[0] > 1920 || $image_info[1] > 1080) {
                $image = $this->resize_image($image, $image_info[0], $image_info[1], 1920, 1080);
            }
            
            // Save optimized image
            switch ($mime_type) {
                case 'image/jpeg':
                    imagejpeg($image, $image_path, 85);
                    break;
                case 'image/png':
                    imagepng($image, $image_path, 8);
                    break;
                case 'image/gif':
                    imagegif($image, $image_path);
                    break;
            }
            
            imagedestroy($image);
        }
    }
    
    private function optimize_css_js(): void {
        $theme_dir = get_template_directory();
        
        // Minify CSS files
        $css_files = glob($theme_dir . '/assets/css/*.css');
        foreach ($css_files as $css_file) {
            if (strpos($css_file, '.min.css') === false) {
                $this->minify_css_file($css_file);
            }
        }
        
        // Minify JS files
        $js_files = glob($theme_dir . '/assets/js/*.js');
        foreach ($js_files as $js_file) {
            if (strpos($js_file, '.min.js') === false) {
                $this->minify_js_file($js_file);
            }
        }
    }
    
    private function generate_performance_report(): void {
        global $wpdb;
        
        // Get performance metrics from last 24 hours
        $metrics = $wpdb->get_results(
            "SELECT 
                AVG(response_time) as avg_response_time,
                MAX(response_time) as max_response_time,
                MIN(response_time) as min_response_time,
                COUNT(*) as total_requests,
                AVG(memory_usage) as avg_memory_usage,
                MAX(memory_usage) as max_memory_usage
             FROM {$wpdb->prefix}tmu_performance_logs 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        
        if (!empty($metrics)) {
            $report = [
                'date' => current_time('Y-m-d'),
                'metrics' => $metrics[0],
                'recommendations' => $this->generate_performance_recommendations($metrics[0])
            ];
            
            update_option('tmu_daily_performance_report', $report);
        }
    }
    
    private function generate_performance_recommendations($metrics): array {
        $recommendations = [];
        
        if ($metrics->avg_response_time > 2) {
            $recommendations[] = 'Average response time is high. Consider enabling caching or optimizing database queries.';
        }
        
        if ($metrics->max_memory_usage > 134217728) { // 128MB
            $recommendations[] = 'High memory usage detected. Review memory-intensive operations.';
        }
        
        if ($metrics->total_requests > 10000) {
            $recommendations[] = 'High traffic detected. Consider implementing CDN or load balancing.';
        }
        
        return $recommendations;
    }
}
```

## Success Metrics

- **Update Success Rate**: > 99%
- **Maintenance Task Completion**: 100%
- **Security Audit Pass Rate**: > 95%
- **Performance Optimization Impact**: 20% improvement in load times
- **Database Optimization**: 30% reduction in query times
- **Automated Backup Success**: 100%
- **System Uptime During Maintenance**: > 99%
- **Error Rate Post-Maintenance**: < 0.1%

This comprehensive maintenance and update system ensures the TMU theme remains secure, performant, and up-to-date throughout its lifecycle.