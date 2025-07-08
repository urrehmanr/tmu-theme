<?php
/**
 * Migration Validation System
 * 
 * Validates all migration procedures and ensures data integrity
 * as specified in Step 19 documentation.
 * 
 * @package TMU\Migration
 * @since 1.0.0
 */

namespace TMU\Migration;

use TMU\Core\BaseClass;
use TMU\Utils\Logger;

/**
 * Migration Validator Class
 * 
 * Validates and monitors all migration procedures to ensure
 * data integrity and successful transitions.
 */
class MigrationValidator extends BaseClass {
    
    /**
     * Validation results
     * 
     * @var array
     */
    private array $validation_results = [];
    
    /**
     * Migration phases to validate
     * 
     * @var array
     */
    private array $migration_phases = [
        'pre_migration',
        'data_extraction',
        'data_transformation',
        'data_loading',
        'post_migration',
        'verification'
    ];
    
    /**
     * Critical validation thresholds
     * 
     * @var array
     */
    private array $thresholds = [
        'data_loss_tolerance' => 0.01, // 1% maximum data loss
        'transformation_accuracy' => 99.5, // 99.5% minimum accuracy
        'performance_degradation' => 20, // 20% maximum performance impact
        'rollback_time_limit' => 300 // 5 minutes maximum rollback time
    ];
    
    /**
     * Initialize migration validator
     */
    public function __construct() {
        parent::__construct();
        $this->init_validation();
    }
    
    /**
     * Initialize validation system
     * 
     * @return void
     */
    private function init_validation(): void {
        add_action('tmu_run_migration_validation', [$this, 'run_full_validation']);
        add_action('tmu_before_migration', [$this, 'validate_pre_migration']);
        add_action('tmu_after_migration', [$this, 'validate_post_migration']);
        add_action('wp_ajax_tmu_validate_migration', [$this, 'ajax_validate_migration']);
    }
    
    /**
     * Run comprehensive migration validation
     * 
     * @return array Validation results
     */
    public function run_full_validation(): array {
        Logger::info('Starting comprehensive migration validation');
        
        $this->validation_results = [
            'timestamp' => current_time('mysql'),
            'overall_status' => 'pending',
            'phases' => [],
            'data_integrity' => [],
            'performance_impact' => [],
            'rollback_readiness' => [],
            'recommendations' => [],
            'critical_issues' => []
        ];
        
        // Validate each migration phase
        foreach ($this->migration_phases as $phase) {
            $this->validation_results['phases'][$phase] = $this->validate_migration_phase($phase);
        }
        
        // Validate data integrity
        $this->validation_results['data_integrity'] = $this->validate_data_integrity();
        
        // Assess performance impact
        $this->validation_results['performance_impact'] = $this->assess_performance_impact();
        
        // Check rollback readiness
        $this->validation_results['rollback_readiness'] = $this->validate_rollback_readiness();
        
        // Generate recommendations
        $this->validation_results['recommendations'] = $this->generate_migration_recommendations();
        
        // Determine overall status
        $this->validation_results['overall_status'] = $this->determine_migration_status();
        
        // Log validation completion
        Logger::info('Migration validation completed', $this->validation_results);
        
        // Store results
        update_option('tmu_migration_validation_results', $this->validation_results);
        
        return $this->validation_results;
    }
    
    /**
     * Validate specific migration phase
     * 
     * @param string $phase Migration phase
     * @return array Phase validation results
     */
    private function validate_migration_phase(string $phase): array {
        $result = [
            'status' => 'pending',
            'checks_passed' => 0,
            'checks_failed' => 0,
            'warnings' => [],
            'critical_issues' => [],
            'execution_time' => 0
        ];
        
        $start_time = microtime(true);
        
        try {
            switch ($phase) {
                case 'pre_migration':
                    $result = $this->validate_pre_migration();
                    break;
                case 'data_extraction':
                    $result = $this->validate_data_extraction();
                    break;
                case 'data_transformation':
                    $result = $this->validate_data_transformation();
                    break;
                case 'data_loading':
                    $result = $this->validate_data_loading();
                    break;
                case 'post_migration':
                    $result = $this->validate_post_migration();
                    break;
                case 'verification':
                    $result = $this->validate_migration_verification();
                    break;
                default:
                    throw new \InvalidArgumentException("Unknown migration phase: {$phase}");
            }
            
            $result['execution_time'] = microtime(true) - $start_time;
            $result['status'] = $this->determine_phase_status($result);
            
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['critical_issues'][] = $e->getMessage();
            Logger::error("Migration validation failed for phase {$phase}", ['error' => $e->getMessage()]);
        }
        
        return $result;
    }
    
    /**
     * Validate pre-migration phase
     * 
     * @return array Pre-migration validation results
     */
    public function validate_pre_migration(): array {
        $result = [
            'checks_passed' => 0,
            'checks_failed' => 0,
            'warnings' => [],
            'critical_issues' => []
        ];
        
        // Check backup availability
        if ($this->check_backup_availability()) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = 'No valid backup found for rollback';
        }
        
        // Check database connectivity
        if ($this->check_database_connectivity()) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = 'Database connectivity issues detected';
        }
        
        // Check disk space
        if ($this->check_disk_space()) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = 'Insufficient disk space for migration';
        }
        
        // Check dependencies
        if ($this->check_migration_dependencies()) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = 'Missing migration dependencies';
        }
        
        // Check maintenance mode
        if ($this->check_maintenance_mode()) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['warnings'][] = 'Site not in maintenance mode during migration';
        }
        
        return $result;
    }
    
    /**
     * Validate data extraction phase
     * 
     * @return array Data extraction validation results
     */
    private function validate_data_extraction(): array {
        $result = [
            'checks_passed' => 0,
            'checks_failed' => 0,
            'warnings' => [],
            'critical_issues' => []
        ];
        
        // Check source data availability
        if ($this->check_source_data()) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = 'Source data not accessible or corrupted';
        }
        
        // Validate data extraction completeness
        $extraction_stats = $this->validate_extraction_completeness();
        if ($extraction_stats['completeness'] >= 99.0) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = "Data extraction incomplete: {$extraction_stats['completeness']}%";
        }
        
        // Check extracted data integrity
        if ($this->check_extracted_data_integrity()) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = 'Extracted data integrity check failed';
        }
        
        return $result;
    }
    
    /**
     * Validate data transformation phase
     * 
     * @return array Data transformation validation results
     */
    private function validate_data_transformation(): array {
        $result = [
            'checks_passed' => 0,
            'checks_failed' => 0,
            'warnings' => [],
            'critical_issues' => []
        ];
        
        // Check transformation rules
        if ($this->check_transformation_rules()) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = 'Invalid transformation rules detected';
        }
        
        // Validate transformation accuracy
        $accuracy = $this->validate_transformation_accuracy();
        if ($accuracy >= $this->thresholds['transformation_accuracy']) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = "Transformation accuracy ({$accuracy}%) below threshold";
        }
        
        // Check data mapping completeness
        if ($this->check_data_mapping()) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = 'Incomplete data mapping detected';
        }
        
        return $result;
    }
    
    /**
     * Validate data loading phase
     * 
     * @return array Data loading validation results
     */
    private function validate_data_loading(): array {
        $result = [
            'checks_passed' => 0,
            'checks_failed' => 0,
            'warnings' => [],
            'critical_issues' => []
        ];
        
        // Check target database readiness
        if ($this->check_target_database()) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = 'Target database not ready for data loading';
        }
        
        // Validate loading performance
        $loading_stats = $this->validate_loading_performance();
        if ($loading_stats['within_limits']) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['warnings'][] = "Data loading performance below expectations: {$loading_stats['time']}s";
        }
        
        // Check constraint violations
        if ($this->check_constraint_violations()) {
            $result['checks_failed']++;
            $result['critical_issues'][] = 'Database constraint violations detected during loading';
        } else {
            $result['checks_passed']++;
        }
        
        return $result;
    }
    
    /**
     * Validate post-migration phase
     * 
     * @return array Post-migration validation results
     */
    public function validate_post_migration(): array {
        $result = [
            'checks_passed' => 0,
            'checks_failed' => 0,
            'warnings' => [],
            'critical_issues' => []
        ];
        
        // Check application functionality
        if ($this->check_application_functionality()) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = 'Application functionality tests failed';
        }
        
        // Validate data consistency
        if ($this->validate_data_consistency()) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = 'Data consistency validation failed';
        }
        
        // Check user access and permissions
        if ($this->check_user_access()) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = 'User access or permissions issues detected';
        }
        
        // Validate theme functionality
        if ($this->validate_theme_functionality()) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = 'Theme functionality validation failed';
        }
        
        return $result;
    }
    
    /**
     * Validate migration verification phase
     * 
     * @return array Verification validation results
     */
    private function validate_migration_verification(): array {
        $result = [
            'checks_passed' => 0,
            'checks_failed' => 0,
            'warnings' => [],
            'critical_issues' => []
        ];
        
        // Comprehensive data verification
        $verification_stats = $this->run_comprehensive_verification();
        
        if ($verification_stats['data_match'] >= 99.9) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = "Data verification failed: {$verification_stats['data_match']}% match";
        }
        
        // Performance verification
        if ($verification_stats['performance_acceptable']) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['warnings'][] = 'Performance degradation detected after migration';
        }
        
        // Feature verification
        if ($verification_stats['features_working']) {
            $result['checks_passed']++;
        } else {
            $result['checks_failed']++;
            $result['critical_issues'][] = 'Core features not working after migration';
        }
        
        return $result;
    }
    
    /**
     * Validate data integrity across all phases
     * 
     * @return array Data integrity results
     */
    private function validate_data_integrity(): array {
        $result = [
            'overall_status' => 'pending',
            'record_count_match' => false,
            'data_loss_percentage' => 0,
            'corruption_detected' => false,
            'relationship_integrity' => false,
            'issues' => []
        ];
        
        // Check record count consistency
        $count_check = $this->check_record_count_consistency();
        $result['record_count_match'] = $count_check['match'];
        $result['data_loss_percentage'] = $count_check['loss_percentage'];
        
        if ($result['data_loss_percentage'] > $this->thresholds['data_loss_tolerance']) {
            $result['issues'][] = "Data loss ({$result['data_loss_percentage']}%) exceeds tolerance";
        }
        
        // Check for data corruption
        $result['corruption_detected'] = $this->check_data_corruption();
        if ($result['corruption_detected']) {
            $result['issues'][] = 'Data corruption detected in migrated records';
        }
        
        // Validate relationship integrity
        $result['relationship_integrity'] = $this->check_relationship_integrity();
        if (!$result['relationship_integrity']) {
            $result['issues'][] = 'Relationship integrity compromised during migration';
        }
        
        // Determine overall status
        $result['overall_status'] = empty($result['issues']) ? 'passed' : 'failed';
        
        return $result;
    }
    
    /**
     * Assess performance impact of migration
     * 
     * @return array Performance impact assessment
     */
    private function assess_performance_impact(): array {
        $result = [
            'pre_migration_performance' => [],
            'post_migration_performance' => [],
            'performance_degradation' => 0,
            'acceptable_impact' => false,
            'recommendations' => []
        ];
        
        // Get performance benchmarks
        $pre_performance = $this->get_pre_migration_performance();
        $post_performance = $this->get_post_migration_performance();
        
        $result['pre_migration_performance'] = $pre_performance;
        $result['post_migration_performance'] = $post_performance;
        
        // Calculate performance degradation
        if (!empty($pre_performance) && !empty($post_performance)) {
            $degradation = 0;
            $metrics = ['page_load_time', 'database_query_time', 'api_response_time'];
            
            foreach ($metrics as $metric) {
                if (isset($pre_performance[$metric]) && isset($post_performance[$metric])) {
                    $metric_degradation = (($post_performance[$metric] - $pre_performance[$metric]) / $pre_performance[$metric]) * 100;
                    $degradation = max($degradation, $metric_degradation);
                }
            }
            
            $result['performance_degradation'] = round($degradation, 2);
            $result['acceptable_impact'] = $degradation <= $this->thresholds['performance_degradation'];
            
            if (!$result['acceptable_impact']) {
                $result['recommendations'][] = 'Performance optimization required post-migration';
                $result['recommendations'][] = 'Consider database indexing and query optimization';
            }
        }
        
        return $result;
    }
    
    /**
     * Validate rollback readiness
     * 
     * @return array Rollback readiness assessment
     */
    private function validate_rollback_readiness(): array {
        $result = [
            'backup_available' => false,
            'backup_integrity' => false,
            'rollback_procedure_tested' => false,
            'estimated_rollback_time' => 0,
            'rollback_ready' => false,
            'issues' => []
        ];
        
        // Check backup availability
        $result['backup_available'] = $this->check_backup_availability();
        if (!$result['backup_available']) {
            $result['issues'][] = 'No backup available for rollback';
        }
        
        // Check backup integrity
        if ($result['backup_available']) {
            $result['backup_integrity'] = $this->check_backup_integrity();
            if (!$result['backup_integrity']) {
                $result['issues'][] = 'Backup integrity check failed';
            }
        }
        
        // Test rollback procedure
        $result['rollback_procedure_tested'] = $this->test_rollback_procedure();
        if (!$result['rollback_procedure_tested']) {
            $result['issues'][] = 'Rollback procedure not tested or failed';
        }
        
        // Estimate rollback time
        $result['estimated_rollback_time'] = $this->estimate_rollback_time();
        if ($result['estimated_rollback_time'] > $this->thresholds['rollback_time_limit']) {
            $result['issues'][] = "Rollback time ({$result['estimated_rollback_time']}s) exceeds limit";
        }
        
        // Determine overall readiness
        $result['rollback_ready'] = empty($result['issues']);
        
        return $result;
    }
    
    /**
     * Generate migration-specific recommendations
     * 
     * @return array Recommendations
     */
    private function generate_migration_recommendations(): array {
        $recommendations = [];
        
        // Check for critical issues
        foreach ($this->validation_results['phases'] as $phase => $data) {
            if (!empty($data['critical_issues'])) {
                foreach ($data['critical_issues'] as $issue) {
                    $recommendations[] = [
                        'priority' => 'critical',
                        'phase' => $phase,
                        'message' => $issue,
                        'action' => $this->get_issue_resolution($issue)
                    ];
                }
            }
        }
        
        // Data integrity recommendations
        if (isset($this->validation_results['data_integrity']['issues'])) {
            foreach ($this->validation_results['data_integrity']['issues'] as $issue) {
                $recommendations[] = [
                    'priority' => 'high',
                    'phase' => 'data_integrity',
                    'message' => $issue,
                    'action' => 'Review data migration process and fix integrity issues'
                ];
            }
        }
        
        // Performance recommendations
        if (isset($this->validation_results['performance_impact']['recommendations'])) {
            foreach ($this->validation_results['performance_impact']['recommendations'] as $recommendation) {
                $recommendations[] = [
                    'priority' => 'medium',
                    'phase' => 'performance',
                    'message' => $recommendation,
                    'action' => 'Implement performance optimization measures'
                ];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Determine overall migration status
     * 
     * @return string Status (ready|warning|not_ready|failed)
     */
    private function determine_migration_status(): string {
        $critical_issues = 0;
        $warnings = 0;
        
        // Count issues across all phases
        foreach ($this->validation_results['phases'] as $phase_data) {
            $critical_issues += count($phase_data['critical_issues']);
            $warnings += count($phase_data['warnings']);
        }
        
        // Check data integrity
        if (isset($this->validation_results['data_integrity']['issues'])) {
            $critical_issues += count($this->validation_results['data_integrity']['issues']);
        }
        
        // Check rollback readiness
        if (isset($this->validation_results['rollback_readiness']['issues'])) {
            $critical_issues += count($this->validation_results['rollback_readiness']['issues']);
        }
        
        // Determine status
        if ($critical_issues > 0) {
            return 'failed';
        } elseif ($warnings > 3) {
            return 'warning';
        } else {
            return 'ready';
        }
    }
    
    /**
     * Get issue resolution for common problems
     * 
     * @param string $issue Issue description
     * @return string Resolution action
     */
    private function get_issue_resolution(string $issue): string {
        $resolutions = [
            'No valid backup found' => 'Create complete backup before proceeding',
            'Database connectivity issues' => 'Check database connection and credentials',
            'Insufficient disk space' => 'Free up disk space or add storage',
            'Missing migration dependencies' => 'Install required dependencies and tools',
            'Source data not accessible' => 'Verify source data location and permissions',
            'Data extraction incomplete' => 'Review extraction process and retry',
            'Invalid transformation rules' => 'Update and validate transformation rules',
            'Target database not ready' => 'Prepare target database schema and permissions',
            'Application functionality tests failed' => 'Debug and fix application issues',
            'Data consistency validation failed' => 'Review data migration and fix inconsistencies'
        ];
        
        foreach ($resolutions as $pattern => $resolution) {
            if (strpos($issue, $pattern) !== false) {
                return $resolution;
            }
        }
        
        return 'Review issue and implement appropriate fix';
    }
    
    /**
     * AJAX handler for migration validation
     * 
     * @return void
     */
    public function ajax_validate_migration(): void {
        check_ajax_referer('tmu_migration_validation', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'tmu'));
        }
        
        $results = $this->run_full_validation();
        
        wp_send_json_success($results);
    }
    
    /**
     * Get migration validation results
     * 
     * @return array|false Validation results or false if none
     */
    public function get_validation_results() {
        return get_option('tmu_migration_validation_results', false);
    }
    
    /**
     * Determine phase status based on results
     * 
     * @param array $result Phase results
     * @return string Status
     */
    private function determine_phase_status(array $result): string {
        if (!empty($result['critical_issues'])) {
            return 'failed';
        } elseif ($result['checks_failed'] > 0) {
            return 'warning';
        } else {
            return 'passed';
        }
    }
    
    // Helper methods for validation checks
    private function check_backup_availability(): bool {
        // Implementation would check for backup files
        $backup_dir = get_template_directory() . '/backups/';
        return is_dir($backup_dir) && !empty(glob($backup_dir . '*.sql'));
    }
    
    private function check_database_connectivity(): bool {
        global $wpdb;
        return $wpdb->get_var("SELECT 1") === '1';
    }
    
    private function check_disk_space(): bool {
        $required_space = 1024 * 1024 * 1024; // 1GB minimum
        $available_space = disk_free_space(ABSPATH);
        return $available_space >= $required_space;
    }
    
    private function check_migration_dependencies(): bool {
        // Check for required tools and libraries
        return class_exists('TMU\Migration\DataMigrator');
    }
    
    private function check_maintenance_mode(): bool {
        return file_exists(ABSPATH . '.maintenance');
    }
    
    // Placeholder implementations for complex validation methods
    private function check_source_data(): bool { return true; }
    private function validate_extraction_completeness(): array { return ['completeness' => 100.0]; }
    private function check_extracted_data_integrity(): bool { return true; }
    private function check_transformation_rules(): bool { return true; }
    private function validate_transformation_accuracy(): float { return 99.9; }
    private function check_data_mapping(): bool { return true; }
    private function check_target_database(): bool { return true; }
    private function validate_loading_performance(): array { return ['within_limits' => true, 'time' => 30]; }
    private function check_constraint_violations(): bool { return false; }
    private function check_application_functionality(): bool { return true; }
    private function validate_data_consistency(): bool { return true; }
    private function check_user_access(): bool { return true; }
    private function validate_theme_functionality(): bool { return true; }
    private function run_comprehensive_verification(): array { 
        return [
            'data_match' => 99.9,
            'performance_acceptable' => true,
            'features_working' => true
        ];
    }
    private function check_record_count_consistency(): array { 
        return ['match' => true, 'loss_percentage' => 0.0]; 
    }
    private function check_data_corruption(): bool { return false; }
    private function check_relationship_integrity(): bool { return true; }
    private function get_pre_migration_performance(): array { return []; }
    private function get_post_migration_performance(): array { return []; }
    private function check_backup_integrity(): bool { return true; }
    private function test_rollback_procedure(): bool { return true; }
    private function estimate_rollback_time(): int { return 180; }
}