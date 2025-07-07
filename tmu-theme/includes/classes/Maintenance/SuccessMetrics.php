<?php
/**
 * Success Metrics Tracker
 * 
 * Tracks and reports all success metrics as documented in Step 18.
 * 
 * @package TMU\Maintenance
 * @since 1.0.0
 */

namespace TMU\Maintenance;

use TMU\Logging\LogManager;

class SuccessMetrics {
    
    /**
     * Logger instance
     * @var LogManager
     */
    private $logger;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new LogManager();
        
        // Hook into various events to track metrics
        add_action('tmu_update_completed', [$this, 'track_update_success']);
        add_action('tmu_update_failed', [$this, 'track_update_failure']);
        add_action('tmu_maintenance_completed', [$this, 'track_maintenance_completion']);
        add_action('tmu_security_audit_completed', [$this, 'track_security_audit']);
        add_action('tmu_performance_optimized', [$this, 'track_performance_improvement']);
        add_action('tmu_backup_completed', [$this, 'track_backup_success']);
        add_action('tmu_backup_failed', [$this, 'track_backup_failure']);
        
        // Schedule metrics calculation
        add_action('tmu_calculate_metrics', [$this, 'calculate_all_metrics']);
        
        // Register admin interface
        add_action('wp_ajax_tmu_get_metrics', [$this, 'get_metrics_ajax']);
    }
    
    /**
     * Track update success rate (target: > 99%)
     */
    public function track_update_success($update_data): void {
        $metrics = get_option('tmu_update_metrics', [
            'total_attempts' => 0,
            'successful_updates' => 0,
            'failed_updates' => 0,
            'last_successful_update' => null
        ]);
        
        $metrics['total_attempts']++;
        $metrics['successful_updates']++;
        $metrics['last_successful_update'] = current_time('mysql');
        
        update_option('tmu_update_metrics', $metrics);
        
        $this->logger->info('Update success tracked', [
            'version' => $update_data['version'] ?? 'unknown',
            'success_rate' => $this->calculate_update_success_rate()
        ]);
    }
    
    /**
     * Track update failure
     */
    public function track_update_failure($error_data): void {
        $metrics = get_option('tmu_update_metrics', [
            'total_attempts' => 0,
            'successful_updates' => 0,
            'failed_updates' => 0,
            'last_failed_update' => null
        ]);
        
        $metrics['total_attempts']++;
        $metrics['failed_updates']++;
        $metrics['last_failed_update'] = current_time('mysql');
        
        update_option('tmu_update_metrics', $metrics);
        
        $this->logger->warning('Update failure tracked', [
            'error' => $error_data['error'] ?? 'unknown',
            'success_rate' => $this->calculate_update_success_rate()
        ]);
    }
    
    /**
     * Track maintenance task completion (target: 100%)
     */
    public function track_maintenance_completion($maintenance_data): void {
        $today = current_time('Y-m-d');
        $metrics = get_option('tmu_maintenance_metrics', []);
        
        if (!isset($metrics[$today])) {
            $metrics[$today] = [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'failed_tasks' => 0,
                'completion_rate' => 0
            ];
        }
        
        $metrics[$today]['total_tasks'] += $maintenance_data['total_tasks'] ?? 0;
        $metrics[$today]['completed_tasks'] += $maintenance_data['completed_tasks'] ?? 0;
        $metrics[$today]['failed_tasks'] += $maintenance_data['failed_tasks'] ?? 0;
        
        // Calculate completion rate
        if ($metrics[$today]['total_tasks'] > 0) {
            $metrics[$today]['completion_rate'] = round(
                ($metrics[$today]['completed_tasks'] / $metrics[$today]['total_tasks']) * 100,
                2
            );
        }
        
        // Keep only last 30 days
        $metrics = array_slice($metrics, -30, null, true);
        
        update_option('tmu_maintenance_metrics', $metrics);
        
        $this->logger->info('Maintenance completion tracked', [
            'date' => $today,
            'completion_rate' => $metrics[$today]['completion_rate']
        ]);
    }
    
    /**
     * Track security audit pass rate (target: > 95%)
     */
    public function track_security_audit($audit_data): void {
        $metrics = get_option('tmu_security_metrics', [
            'total_audits' => 0,
            'passed_audits' => 0,
            'failed_audits' => 0,
            'last_audit_date' => null,
            'last_audit_score' => 0
        ]);
        
        $metrics['total_audits']++;
        $metrics['last_audit_date'] = current_time('mysql');
        $metrics['last_audit_score'] = $audit_data['score'] ?? 0;
        
        // Consider audit passed if score >= 95
        if ($metrics['last_audit_score'] >= 95) {
            $metrics['passed_audits']++;
        } else {
            $metrics['failed_audits']++;
        }
        
        update_option('tmu_security_metrics', $metrics);
        
        $this->logger->info('Security audit tracked', [
            'score' => $metrics['last_audit_score'],
            'pass_rate' => $this->calculate_security_pass_rate()
        ]);
    }
    
    /**
     * Track performance optimization impact (target: 20% improvement)
     */
    public function track_performance_improvement($performance_data): void {
        $today = current_time('Y-m-d');
        $metrics = get_option('tmu_performance_metrics', []);
        
        if (!isset($metrics[$today])) {
            $metrics[$today] = [
                'optimizations_run' => 0,
                'total_improvement' => 0,
                'average_improvement' => 0,
                'load_time_before' => 0,
                'load_time_after' => 0
            ];
        }
        
        $metrics[$today]['optimizations_run']++;
        
        if (isset($performance_data['improvement_percentage'])) {
            $metrics[$today]['total_improvement'] += $performance_data['improvement_percentage'];
            $metrics[$today]['average_improvement'] = round(
                $metrics[$today]['total_improvement'] / $metrics[$today]['optimizations_run'],
                2
            );
        }
        
        if (isset($performance_data['load_time_before'])) {
            $metrics[$today]['load_time_before'] = $performance_data['load_time_before'];
        }
        
        if (isset($performance_data['load_time_after'])) {
            $metrics[$today]['load_time_after'] = $performance_data['load_time_after'];
        }
        
        // Keep only last 30 days
        $metrics = array_slice($metrics, -30, null, true);
        
        update_option('tmu_performance_metrics', $metrics);
        
        $this->logger->info('Performance improvement tracked', [
            'date' => $today,
            'improvement' => $metrics[$today]['average_improvement'] . '%'
        ]);
    }
    
    /**
     * Track automated backup success (target: 100%)
     */
    public function track_backup_success($backup_data): void {
        $metrics = get_option('tmu_backup_metrics', [
            'total_backups' => 0,
            'successful_backups' => 0,
            'failed_backups' => 0,
            'last_successful_backup' => null
        ]);
        
        $metrics['total_backups']++;
        $metrics['successful_backups']++;
        $metrics['last_successful_backup'] = current_time('mysql');
        
        update_option('tmu_backup_metrics', $metrics);
        
        $this->logger->info('Backup success tracked', [
            'backup_id' => $backup_data['backup_id'] ?? 'unknown',
            'success_rate' => $this->calculate_backup_success_rate()
        ]);
    }
    
    /**
     * Track backup failure
     */
    public function track_backup_failure($error_data): void {
        $metrics = get_option('tmu_backup_metrics', [
            'total_backups' => 0,
            'successful_backups' => 0,
            'failed_backups' => 0,
            'last_failed_backup' => null
        ]);
        
        $metrics['total_backups']++;
        $metrics['failed_backups']++;
        $metrics['last_failed_backup'] = current_time('mysql');
        
        update_option('tmu_backup_metrics', $metrics);
        
        $this->logger->warning('Backup failure tracked', [
            'error' => $error_data['error'] ?? 'unknown',
            'success_rate' => $this->calculate_backup_success_rate()
        ]);
    }
    
    /**
     * Calculate all metrics as documented
     */
    public function calculate_all_metrics(): array {
        $metrics = [
            'update_success_rate' => $this->calculate_update_success_rate(),
            'maintenance_task_completion' => $this->calculate_maintenance_completion_rate(),
            'security_audit_pass_rate' => $this->calculate_security_pass_rate(),
            'performance_optimization_impact' => $this->calculate_performance_impact(),
            'database_optimization_improvement' => $this->calculate_database_optimization(),
            'automated_backup_success' => $this->calculate_backup_success_rate(),
            'system_uptime_during_maintenance' => $this->calculate_uptime_during_maintenance(),
            'error_rate_post_maintenance' => $this->calculate_post_maintenance_error_rate(),
            'last_calculated' => current_time('mysql')
        ];
        
        update_option('tmu_success_metrics', $metrics);
        
        return $metrics;
    }
    
    /**
     * Calculate update success rate
     */
    private function calculate_update_success_rate(): float {
        $metrics = get_option('tmu_update_metrics', [
            'total_attempts' => 0,
            'successful_updates' => 0
        ]);
        
        if ($metrics['total_attempts'] === 0) {
            return 100.0; // No updates attempted yet
        }
        
        return round(($metrics['successful_updates'] / $metrics['total_attempts']) * 100, 2);
    }
    
    /**
     * Calculate maintenance task completion rate
     */
    private function calculate_maintenance_completion_rate(): float {
        $metrics = get_option('tmu_maintenance_metrics', []);
        
        if (empty($metrics)) {
            return 100.0;
        }
        
        $total_tasks = 0;
        $completed_tasks = 0;
        
        foreach ($metrics as $day_metrics) {
            $total_tasks += $day_metrics['total_tasks'];
            $completed_tasks += $day_metrics['completed_tasks'];
        }
        
        if ($total_tasks === 0) {
            return 100.0;
        }
        
        return round(($completed_tasks / $total_tasks) * 100, 2);
    }
    
    /**
     * Calculate security audit pass rate
     */
    private function calculate_security_pass_rate(): float {
        $metrics = get_option('tmu_security_metrics', [
            'total_audits' => 0,
            'passed_audits' => 0
        ]);
        
        if ($metrics['total_audits'] === 0) {
            return 100.0;
        }
        
        return round(($metrics['passed_audits'] / $metrics['total_audits']) * 100, 2);
    }
    
    /**
     * Calculate performance optimization impact
     */
    private function calculate_performance_impact(): float {
        $metrics = get_option('tmu_performance_metrics', []);
        
        if (empty($metrics)) {
            return 0.0;
        }
        
        $total_improvement = 0;
        $optimization_count = 0;
        
        foreach ($metrics as $day_metrics) {
            $total_improvement += $day_metrics['total_improvement'];
            $optimization_count += $day_metrics['optimizations_run'];
        }
        
        if ($optimization_count === 0) {
            return 0.0;
        }
        
        return round($total_improvement / $optimization_count, 2);
    }
    
    /**
     * Calculate database optimization improvement (target: 30% reduction in query times)
     */
    private function calculate_database_optimization(): float {
        $metrics = get_option('tmu_database_optimization_metrics', [
            'queries_before_optimization' => [],
            'queries_after_optimization' => [],
            'last_optimization_date' => null
        ]);
        
        if (empty($metrics['queries_before_optimization']) || empty($metrics['queries_after_optimization'])) {
            return 0.0;
        }
        
        $avg_before = array_sum($metrics['queries_before_optimization']) / count($metrics['queries_before_optimization']);
        $avg_after = array_sum($metrics['queries_after_optimization']) / count($metrics['queries_after_optimization']);
        
        if ($avg_before === 0) {
            return 0.0;
        }
        
        $improvement = (($avg_before - $avg_after) / $avg_before) * 100;
        
        return round($improvement, 2);
    }
    
    /**
     * Calculate backup success rate
     */
    private function calculate_backup_success_rate(): float {
        $metrics = get_option('tmu_backup_metrics', [
            'total_backups' => 0,
            'successful_backups' => 0
        ]);
        
        if ($metrics['total_backups'] === 0) {
            return 100.0;
        }
        
        return round(($metrics['successful_backups'] / $metrics['total_backups']) * 100, 2);
    }
    
    /**
     * Calculate system uptime during maintenance (target: > 99%)
     */
    private function calculate_uptime_during_maintenance(): float {
        $metrics = get_option('tmu_uptime_metrics', [
            'total_maintenance_time' => 0,
            'downtime_during_maintenance' => 0
        ]);
        
        if ($metrics['total_maintenance_time'] === 0) {
            return 100.0;
        }
        
        $uptime = (($metrics['total_maintenance_time'] - $metrics['downtime_during_maintenance']) / $metrics['total_maintenance_time']) * 100;
        
        return round($uptime, 2);
    }
    
    /**
     * Calculate error rate post-maintenance (target: < 0.1%)
     */
    private function calculate_post_maintenance_error_rate(): float {
        $metrics = get_option('tmu_error_rate_metrics', [
            'total_requests_post_maintenance' => 0,
            'errors_post_maintenance' => 0
        ]);
        
        if ($metrics['total_requests_post_maintenance'] === 0) {
            return 0.0;
        }
        
        return round(($metrics['errors_post_maintenance'] / $metrics['total_requests_post_maintenance']) * 100, 4);
    }
    
    /**
     * Get metrics via AJAX
     */
    public function get_metrics_ajax(): void {
        check_ajax_referer('tmu_metrics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $metrics = $this->calculate_all_metrics();
        
        wp_send_json_success($metrics);
    }
    
    /**
     * Get formatted metrics report
     */
    public function get_metrics_report(): string {
        $metrics = get_option('tmu_success_metrics', []);
        
        if (empty($metrics)) {
            $metrics = $this->calculate_all_metrics();
        }
        
        $report = "TMU Theme Success Metrics Report\n";
        $report .= "Generated: " . current_time('Y-m-d H:i:s') . "\n\n";
        
        $report .= "Update Success Rate: {$metrics['update_success_rate']}% (Target: > 99%)\n";
        $report .= "Maintenance Task Completion: {$metrics['maintenance_task_completion']}% (Target: 100%)\n";
        $report .= "Security Audit Pass Rate: {$metrics['security_audit_pass_rate']}% (Target: > 95%)\n";
        $report .= "Performance Optimization Impact: {$metrics['performance_optimization_impact']}% (Target: 20%)\n";
        $report .= "Database Optimization: {$metrics['database_optimization_improvement']}% (Target: 30%)\n";
        $report .= "Automated Backup Success: {$metrics['automated_backup_success']}% (Target: 100%)\n";
        $report .= "System Uptime During Maintenance: {$metrics['system_uptime_during_maintenance']}% (Target: > 99%)\n";
        $report .= "Error Rate Post-Maintenance: {$metrics['error_rate_post_maintenance']}% (Target: < 0.1%)\n";
        
        return $report;
    }
}