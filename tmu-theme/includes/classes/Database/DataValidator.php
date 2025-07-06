<?php
/**
 * TMU Data Validator
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
 * Data Validator Class
 */
class DataValidator {
    
    /**
     * Validate existing plugin data
     *
     * @return array
     */
    public static function validateExistingData(): array {
        global $wpdb;
        
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'data_count' => [],
            'table_status' => [],
            'integrity_issues' => []
        ];
        
        // Check for existing plugin tables
        $plugin_tables = [
            'tmu_movies',
            'tmu_people',
            'tmu_dramas',
            'tmu_tv_series',
            'tmu_videos',
            'tmu_seo_options',
            'tmu_movies_cast',
            'tmu_movies_crew',
            'tmu_tv_series_cast',
            'tmu_tv_series_crew',
            'tmu_dramas_cast',
            'tmu_dramas_crew'
        ];
        
        foreach ($plugin_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            
            if (self::tableExists($table_name)) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
                $results['data_count'][$table] = $count;
                $results['table_status'][$table] = 'exists';
                
                if ($count > 0) {
                    $results['warnings'][] = "Found {$count} records in {$table} table";
                }
            } else {
                $results['table_status'][$table] = 'missing';
            }
        }
        
        // Check for orphaned data
        $orphaned = self::checkOrphanedData();
        if (!empty($orphaned)) {
            $results['warnings'] = array_merge($results['warnings'], $orphaned);
            $results['integrity_issues'] = $orphaned;
        }
        
        // Check extended core tables
        $core_extensions = self::checkCoreTableExtensions();
        if (!empty($core_extensions)) {
            $results['warnings'] = array_merge($results['warnings'], $core_extensions);
        }
        
        // Validate data integrity
        $integrity_check = self::validateDataIntegrity();
        if (!$integrity_check['valid']) {
            $results['errors'] = array_merge($results['errors'], $integrity_check['errors']);
            $results['valid'] = false;
        }
        
        return $results;
    }
    
    /**
     * Check for orphaned data
     *
     * @return array
     */
    private static function checkOrphanedData(): array {
        global $wpdb;
        
        $warnings = [];
        
        // Check for orphaned cast records
        if (self::tableExists($wpdb->prefix . 'tmu_movies_cast')) {
            $orphaned_cast = $wpdb->get_var("
                SELECT COUNT(*) FROM `{$wpdb->prefix}tmu_movies_cast` mc
                LEFT JOIN `{$wpdb->prefix}posts` p ON mc.movie = p.ID
                WHERE p.ID IS NULL
            ");
            
            if ($orphaned_cast > 0) {
                $warnings[] = "Found {$orphaned_cast} orphaned movie cast records";
            }
        }
        
        // Check for orphaned crew records
        if (self::tableExists($wpdb->prefix . 'tmu_movies_crew')) {
            $orphaned_crew = $wpdb->get_var("
                SELECT COUNT(*) FROM `{$wpdb->prefix}tmu_movies_crew` mc
                LEFT JOIN `{$wpdb->prefix}posts` p ON mc.movie = p.ID
                WHERE p.ID IS NULL
            ");
            
            if ($orphaned_crew > 0) {
                $warnings[] = "Found {$orphaned_crew} orphaned movie crew records";
            }
        }
        
        // Check for orphaned TV series cast/crew
        if (self::tableExists($wpdb->prefix . 'tmu_tv_series_cast')) {
            $orphaned_tv_cast = $wpdb->get_var("
                SELECT COUNT(*) FROM `{$wpdb->prefix}tmu_tv_series_cast` tc
                LEFT JOIN `{$wpdb->prefix}posts` p ON tc.tv_series = p.ID
                WHERE p.ID IS NULL
            ");
            
            if ($orphaned_tv_cast > 0) {
                $warnings[] = "Found {$orphaned_tv_cast} orphaned TV series cast records";
            }
        }
        
        // Check for orphaned dramas cast/crew
        if (self::tableExists($wpdb->prefix . 'tmu_dramas_cast')) {
            $orphaned_drama_cast = $wpdb->get_var("
                SELECT COUNT(*) FROM `{$wpdb->prefix}tmu_dramas_cast` dc
                LEFT JOIN `{$wpdb->prefix}posts` p ON dc.drama = p.ID
                WHERE p.ID IS NULL
            ");
            
            if ($orphaned_drama_cast > 0) {
                $warnings[] = "Found {$orphaned_drama_cast} orphaned drama cast records";
            }
        }
        
        return $warnings;
    }
    
    /**
     * Check core table extensions
     *
     * @return array
     */
    private static function checkCoreTableExtensions(): array {
        global $wpdb;
        
        $warnings = [];
        
        // Check if wp_posts has been extended
        if (self::columnExists('posts', 'seo_title')) {
            $warnings[] = 'wp_posts table already has SEO extensions';
        }
        
        if (self::columnExists('comments', 'comment_rating')) {
            $warnings[] = 'wp_comments table already has rating extensions';
        }
        
        return $warnings;
    }
    
    /**
     * Validate data integrity
     *
     * @return array
     */
    private static function validateDataIntegrity(): array {
        global $wpdb;
        
        $results = [
            'valid' => true,
            'errors' => []
        ];
        
        // Check for missing foreign key references
        if (self::tableExists($wpdb->prefix . 'tmu_movies')) {
            $missing_posts = $wpdb->get_var("
                SELECT COUNT(*) FROM `{$wpdb->prefix}tmu_movies` m
                LEFT JOIN `{$wpdb->prefix}posts` p ON m.ID = p.ID
                WHERE p.ID IS NULL
            ");
            
            if ($missing_posts > 0) {
                $results['errors'][] = "Found {$missing_posts} movie records without corresponding post entries";
                $results['valid'] = false;
            }
        }
        
        // Check for duplicate TMDB IDs
        if (self::tableExists($wpdb->prefix . 'tmu_movies')) {
            $duplicate_tmdb = $wpdb->get_var("
                SELECT COUNT(*) FROM (
                    SELECT tmdb_id FROM `{$wpdb->prefix}tmu_movies` 
                    WHERE tmdb_id IS NOT NULL 
                    GROUP BY tmdb_id HAVING COUNT(*) > 1
                ) as duplicates
            ");
            
            if ($duplicate_tmdb > 0) {
                $results['errors'][] = "Found {$duplicate_tmdb} duplicate TMDB IDs in movies table";
                $results['valid'] = false;
            }
        }
        
        return $results;
    }
    
    /**
     * Check if table exists
     *
     * @param string $table_name
     * @return bool
     */
    private static function tableExists(string $table_name): bool {
        global $wpdb;
        
        $query = $wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
        return $wpdb->get_var($query) === $table_name;
    }
    
    /**
     * Check if column exists
     *
     * @param string $table_name
     * @param string $column_name
     * @return bool
     */
    private static function columnExists(string $table_name, string $column_name): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $table_name;
        $query = $wpdb->prepare("SHOW COLUMNS FROM `{$table_name}` LIKE %s", $column_name);
        
        return $wpdb->get_var($query) !== null;
    }
    
    /**
     * Check if index exists
     *
     * @param string $table_name
     * @param string $index_name
     * @return bool
     */
    public static function indexExists(string $table_name, string $index_name): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $table_name;
        $query = $wpdb->prepare("SHOW INDEX FROM `{$table_name}` WHERE Key_name = %s", $index_name);
        
        return $wpdb->get_var($query) !== null;
    }
    
    /**
     * Validate specific table structure
     *
     * @param string $table_name
     * @param array $expected_columns
     * @return array
     */
    public static function validateTableStructure(string $table_name, array $expected_columns): array {
        global $wpdb;
        
        $results = [
            'valid' => true,
            'missing_columns' => [],
            'extra_columns' => []
        ];
        
        $full_table_name = $wpdb->prefix . $table_name;
        
        if (!self::tableExists($full_table_name)) {
            $results['valid'] = false;
            $results['missing_columns'] = $expected_columns;
            return $results;
        }
        
        // Get actual columns
        $actual_columns = $wpdb->get_col("DESCRIBE `{$full_table_name}`");
        
        // Check for missing columns
        foreach ($expected_columns as $column) {
            if (!in_array($column, $actual_columns)) {
                $results['missing_columns'][] = $column;
                $results['valid'] = false;
            }
        }
        
        // Check for extra columns (optional check)
        foreach ($actual_columns as $column) {
            if (!in_array($column, $expected_columns)) {
                $results['extra_columns'][] = $column;
            }
        }
        
        return $results;
    }
    
    /**
     * Clean orphaned data
     *
     * @return array
     */
    public static function cleanOrphanedData(): array {
        global $wpdb;
        
        $results = [
            'cleaned' => 0,
            'errors' => []
        ];
        
        try {
            // Clean orphaned cast records
            if (self::tableExists($wpdb->prefix . 'tmu_movies_cast')) {
                $deleted = $wpdb->query("
                    DELETE mc FROM `{$wpdb->prefix}tmu_movies_cast` mc
                    LEFT JOIN `{$wpdb->prefix}posts` p ON mc.movie = p.ID
                    WHERE p.ID IS NULL
                ");
                $results['cleaned'] += $deleted;
            }
            
            // Clean orphaned crew records
            if (self::tableExists($wpdb->prefix . 'tmu_movies_crew')) {
                $deleted = $wpdb->query("
                    DELETE mc FROM `{$wpdb->prefix}tmu_movies_crew` mc
                    LEFT JOIN `{$wpdb->prefix}posts` p ON mc.movie = p.ID
                    WHERE p.ID IS NULL
                ");
                $results['cleaned'] += $deleted;
            }
            
            // Clean orphaned TV series cast/crew
            if (self::tableExists($wpdb->prefix . 'tmu_tv_series_cast')) {
                $deleted = $wpdb->query("
                    DELETE tc FROM `{$wpdb->prefix}tmu_tv_series_cast` tc
                    LEFT JOIN `{$wpdb->prefix}posts` p ON tc.tv_series = p.ID
                    WHERE p.ID IS NULL
                ");
                $results['cleaned'] += $deleted;
            }
            
            // Clean orphaned dramas cast/crew
            if (self::tableExists($wpdb->prefix . 'tmu_dramas_cast')) {
                $deleted = $wpdb->query("
                    DELETE dc FROM `{$wpdb->prefix}tmu_dramas_cast` dc
                    LEFT JOIN `{$wpdb->prefix}posts` p ON dc.drama = p.ID
                    WHERE p.ID IS NULL
                ");
                $results['cleaned'] += $deleted;
            }
            
        } catch (Exception $e) {
            $results['errors'][] = 'Error cleaning orphaned data: ' . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Get database statistics
     *
     * @return array
     */
    public static function getDatabaseStatistics(): array {
        global $wpdb;
        
        $stats = [
            'tables' => [],
            'total_records' => 0,
            'database_size' => 0
        ];
        
        $tables = [
            'tmu_movies',
            'tmu_people',
            'tmu_tv_series',
            'tmu_dramas',
            'tmu_videos',
            'tmu_seo_options'
        ];
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            
            if (self::tableExists($table_name)) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
                $size = $wpdb->get_var("
                    SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'DB Size in MB'
                    FROM information_schema.tables 
                    WHERE table_schema = DATABASE() AND table_name = '{$table_name}'
                ");
                
                $stats['tables'][$table] = [
                    'records' => $count,
                    'size_mb' => $size ?: 0
                ];
                
                $stats['total_records'] += $count;
                $stats['database_size'] += $size ?: 0;
            }
        }
        
        return $stats;
    }
    
    /**
     * Backup validation - check if backup is needed
     *
     * @return array
     */
    public static function checkBackupRequirements(): array {
        $validation = self::validateExistingData();
        $stats = self::getDatabaseStatistics();
        
        $needs_backup = false;
        $reasons = [];
        
        // Check if there's significant data
        if ($stats['total_records'] > 100) {
            $needs_backup = true;
            $reasons[] = "Significant data found ({$stats['total_records']} records)";
        }
        
        // Check if there are integrity issues
        if (!empty($validation['integrity_issues'])) {
            $needs_backup = true;
            $reasons[] = "Data integrity issues detected";
        }
        
        // Check database size
        if ($stats['database_size'] > 50) { // 50MB threshold
            $needs_backup = true;
            $reasons[] = "Large database size ({$stats['database_size']} MB)";
        }
        
        return [
            'needs_backup' => $needs_backup,
            'reasons' => $reasons,
            'estimated_size' => $stats['database_size'],
            'record_count' => $stats['total_records']
        ];
    }
}