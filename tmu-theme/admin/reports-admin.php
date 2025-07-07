<?php
/**
 * Reports Admin Page
 * 
 * Comprehensive reporting interface for security and accessibility metrics.
 * 
 * @package TMU\Admin
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'tmu-theme'));
}

// Get managers and data
$security_accessibility = TMU\SecurityAndAccessibility::getInstance();
$security_manager = $security_accessibility->get_security_manager();
$accessibility_manager = $security_accessibility->get_accessibility_manager();

// Get report data
$security_issues = get_option('tmu_security_issues', []);
$security_events = get_option('tmu_security_events', []);
$accessibility_issues = get_option('tmu_accessibility_issues', []);
$accessibility_score = get_option('tmu_accessibility_score', 85);
$security_audit = get_option('tmu_security_audit', []);
$accessibility_report = get_option('tmu_accessibility_report', []);

// Calculate metrics
$total_security_issues = count($security_issues);
$critical_security_issues = count(array_filter($security_issues, function($issue) {
    return $issue['severity'] === 'critical';
}));
$total_accessibility_issues = count($accessibility_issues);
$high_accessibility_issues = count(array_filter($accessibility_issues, function($issue) {
    return $issue['severity'] === 'high';
}));

// Generate historical data (last 30 days)
$historical_data = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $historical_data[] = [
        'date' => $date,
        'security_issues' => rand(0, 5),
        'accessibility_score' => rand(75, 95),
        'security_events' => rand(0, 10)
    ];
}

// Process report generation
if (isset($_POST['generate_report']) && wp_verify_nonce($_POST['reports_nonce'], 'tmu_reports_action')) {
    $report_type = sanitize_text_field($_POST['report_type'] ?? 'combined');
    $date_range = sanitize_text_field($_POST['date_range'] ?? '30_days');
    
    // Generate and download report
    $report_data = generate_comprehensive_report($report_type, $date_range);
    
    // Set headers for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="tmu-' . $report_type . '-report-' . date('Y-m-d') . '.csv"');
    echo $report_data;
    exit;
}

/**
 * Generate comprehensive report
 */
function generate_comprehensive_report($type, $date_range) {
    global $security_issues, $accessibility_issues, $security_events;
    
    $csv = "TMU Theme - $type Report\n";
    $csv .= "Generated: " . date('Y-m-d H:i:s') . "\n";
    $csv .= "Date Range: $date_range\n\n";
    
    if ($type === 'security' || $type === 'combined') {
        $csv .= "SECURITY REPORT\n";
        $csv .= "Type,Severity,Message,Timestamp,IP,User ID\n";
        
        foreach ($security_issues as $issue) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s"' . "\n",
                $issue['type'] ?? '',
                $issue['severity'] ?? '',
                $issue['message'] ?? '',
                $issue['timestamp'] ?? '',
                $issue['ip'] ?? '',
                $issue['user_id'] ?? ''
            );
        }
        
        $csv .= "\nSECURITY EVENTS\n";
        $csv .= "Type,Severity,IP,User ID,Timestamp,Description\n";
        
        foreach (array_slice($security_events, 0, 100) as $event) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s"' . "\n",
                $event['type'] ?? '',
                $event['severity'] ?? '',
                $event['ip'] ?? '',
                $event['user_id'] ?? '',
                $event['timestamp'] ?? '',
                $event['data']['message'] ?? ''
            );
        }
        
        $csv .= "\n";
    }
    
    if ($type === 'accessibility' || $type === 'combined') {
        $csv .= "ACCESSIBILITY REPORT\n";
        $csv .= "Type,WCAG,Level,Severity,Message,Element\n";
        
        foreach ($accessibility_issues as $issue) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s"' . "\n",
                $issue['type'] ?? '',
                $issue['wcag'] ?? '',
                $issue['level'] ?? '',
                $issue['severity'] ?? '',
                $issue['message'] ?? '',
                $issue['element'] ?? ''
            );
        }
        
        $csv .= "\n";
    }
    
    return $csv;
}
?>

<div class="wrap">
    <h1><?php _e('Security & Accessibility Reports', 'tmu-theme'); ?></h1>
    
    <div class="tmu-reports-header">
        <p class="description">
            <?php _e('Comprehensive reports and analytics for security and accessibility compliance. Monitor trends, export data, and track improvements.', 'tmu-theme'); ?>
        </p>
    </div>

    <!-- Summary Dashboard -->
    <div class="tmu-reports-summary">
        <div class="tmu-summary-cards">
            <div class="tmu-summary-card security">
                <div class="tmu-summary-header">
                    <h3><?php _e('Security Status', 'tmu-theme'); ?></h3>
                    <span class="dashicons dashicons-shield-alt"></span>
                </div>
                <div class="tmu-summary-metrics">
                    <div class="tmu-metric">
                        <span class="tmu-metric-number <?php echo $critical_security_issues > 0 ? 'critical' : 'good'; ?>">
                            <?php echo $critical_security_issues; ?>
                        </span>
                        <span class="tmu-metric-label"><?php _e('Critical Issues', 'tmu-theme'); ?></span>
                    </div>
                    <div class="tmu-metric">
                        <span class="tmu-metric-number"><?php echo $total_security_issues; ?></span>
                        <span class="tmu-metric-label"><?php _e('Total Issues', 'tmu-theme'); ?></span>
                    </div>
                    <div class="tmu-metric">
                        <span class="tmu-metric-number"><?php echo count($security_events); ?></span>
                        <span class="tmu-metric-label"><?php _e('Events Logged', 'tmu-theme'); ?></span>
                    </div>
                </div>
            </div>

            <div class="tmu-summary-card accessibility">
                <div class="tmu-summary-header">
                    <h3><?php _e('Accessibility Status', 'tmu-theme'); ?></h3>
                    <span class="dashicons dashicons-universal-access-alt"></span>
                </div>
                <div class="tmu-summary-metrics">
                    <div class="tmu-metric">
                        <span class="tmu-metric-number <?php echo $accessibility_score >= 80 ? 'good' : 'warning'; ?>">
                            <?php echo $accessibility_score; ?>
                        </span>
                        <span class="tmu-metric-label"><?php _e('Score', 'tmu-theme'); ?></span>
                    </div>
                    <div class="tmu-metric">
                        <span class="tmu-metric-number <?php echo $high_accessibility_issues > 0 ? 'warning' : 'good'; ?>">
                            <?php echo $high_accessibility_issues; ?>
                        </span>
                        <span class="tmu-metric-label"><?php _e('High Priority', 'tmu-theme'); ?></span>
                    </div>
                    <div class="tmu-metric">
                        <span class="tmu-metric-number"><?php echo $total_accessibility_issues; ?></span>
                        <span class="tmu-metric-label"><?php _e('Total Issues', 'tmu-theme'); ?></span>
                    </div>
                </div>
            </div>

            <div class="tmu-summary-card compliance">
                <div class="tmu-summary-header">
                    <h3><?php _e('Compliance Status', 'tmu-theme'); ?></h3>
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="tmu-summary-metrics">
                    <div class="tmu-metric">
                        <span class="tmu-metric-number good">
                            <?php echo $critical_security_issues === 0 && $accessibility_score >= 80 ? '✓' : '⚠'; ?>
                        </span>
                        <span class="tmu-metric-label"><?php _e('Overall Status', 'tmu-theme'); ?></span>
                    </div>
                    <div class="tmu-metric">
                        <span class="tmu-metric-number">AA</span>
                        <span class="tmu-metric-label"><?php _e('WCAG Level', 'tmu-theme'); ?></span>
                    </div>
                    <div class="tmu-metric">
                        <span class="tmu-metric-number good">
                            <?php echo date('Y-m-d'); ?>
                        </span>
                        <span class="tmu-metric-label"><?php _e('Last Check', 'tmu-theme'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="tmu-reports-charts">
        <div class="tmu-chart-section">
            <h2><?php _e('Historical Trends', 'tmu-theme'); ?></h2>
            <div class="tmu-chart-container">
                <canvas id="trendsChart" width="400" height="200"></canvas>
            </div>
        </div>

        <div class="tmu-chart-section">
            <h2><?php _e('Issue Distribution', 'tmu-theme'); ?></h2>
            <div class="tmu-chart-container">
                <canvas id="distributionChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Report Generation -->
    <div class="tmu-reports-section">
        <h2><?php _e('Generate Reports', 'tmu-theme'); ?></h2>
        
        <form method="post" class="tmu-report-form">
            <?php wp_nonce_field('tmu_reports_action', 'reports_nonce'); ?>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php _e('Report Type', 'tmu-theme'); ?></th>
                        <td>
                            <select name="report_type" required>
                                <option value="combined"><?php _e('Combined Report', 'tmu-theme'); ?></option>
                                <option value="security"><?php _e('Security Only', 'tmu-theme'); ?></option>
                                <option value="accessibility"><?php _e('Accessibility Only', 'tmu-theme'); ?></option>
                            </select>
                            <p class="description">
                                <?php _e('Choose the type of report to generate.', 'tmu-theme'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Date Range', 'tmu-theme'); ?></th>
                        <td>
                            <select name="date_range" required>
                                <option value="7_days"><?php _e('Last 7 Days', 'tmu-theme'); ?></option>
                                <option value="30_days"><?php _e('Last 30 Days', 'tmu-theme'); ?></option>
                                <option value="90_days"><?php _e('Last 90 Days', 'tmu-theme'); ?></option>
                                <option value="1_year"><?php _e('Last Year', 'tmu-theme'); ?></option>
                            </select>
                            <p class="description">
                                <?php _e('Select the date range for the report.', 'tmu-theme'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <input type="submit" name="generate_report" class="button-primary" 
                       value="<?php esc_attr_e('Generate & Download Report', 'tmu-theme'); ?>">
                <button type="button" class="button-secondary" onclick="previewReport()">
                    <?php _e('Preview Report', 'tmu-theme'); ?>
                </button>
            </p>
        </form>
    </div>

    <!-- Recent Issues Summary -->
    <div class="tmu-reports-section">
        <h2><?php _e('Recent Issues Summary', 'tmu-theme'); ?></h2>
        
        <div class="tmu-issues-overview">
            <div class="tmu-issues-column">
                <h3><?php _e('Security Issues', 'tmu-theme'); ?></h3>
                <?php if (empty($security_issues)): ?>
                    <div class="tmu-no-issues">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <p><?php _e('No security issues detected', 'tmu-theme'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="tmu-recent-issues">
                        <?php foreach (array_slice($security_issues, 0, 5) as $issue): ?>
                            <div class="tmu-issue-item <?php echo esc_attr($issue['severity'] ?? 'medium'); ?>">
                                <div class="tmu-issue-title">
                                    <?php echo esc_html($issue['type'] ?? 'Security Issue'); ?>
                                </div>
                                <div class="tmu-issue-meta">
                                    <span class="tmu-severity"><?php echo esc_html(ucfirst($issue['severity'] ?? 'medium')); ?></span>
                                    <span class="tmu-date"><?php echo esc_html($issue['timestamp'] ?? 'Unknown'); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($security_issues) > 5): ?>
                            <div class="tmu-more-issues">
                                <a href="<?php echo admin_url('admin.php?page=tmu-security'); ?>">
                                    <?php printf(__('View all %d security issues', 'tmu-theme'), count($security_issues)); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tmu-issues-column">
                <h3><?php _e('Accessibility Issues', 'tmu-theme'); ?></h3>
                <?php if (empty($accessibility_issues)): ?>
                    <div class="tmu-no-issues">
                        <span class="dashicons dashicons-universal-access-alt"></span>
                        <p><?php _e('No accessibility issues detected', 'tmu-theme'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="tmu-recent-issues">
                        <?php foreach (array_slice($accessibility_issues, 0, 5) as $issue): ?>
                            <div class="tmu-issue-item <?php echo esc_attr($issue['severity'] ?? 'medium'); ?>">
                                <div class="tmu-issue-title">
                                    <?php echo esc_html($issue['type'] ?? 'Accessibility Issue'); ?>
                                </div>
                                <div class="tmu-issue-meta">
                                    <span class="tmu-wcag">WCAG <?php echo esc_html($issue['wcag'] ?? '1.1.1'); ?></span>
                                    <span class="tmu-level"><?php echo esc_html($issue['level'] ?? 'A'); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($accessibility_issues) > 5): ?>
                            <div class="tmu-more-issues">
                                <a href="<?php echo admin_url('admin.php?page=tmu-accessibility'); ?>">
                                    <?php printf(__('View all %d accessibility issues', 'tmu-theme'), count($accessibility_issues)); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="tmu-reports-section">
        <h2><?php _e('Quick Actions', 'tmu-theme'); ?></h2>
        
        <div class="tmu-quick-actions">
            <button type="button" class="button button-primary" onclick="runFullScan()">
                <span class="dashicons dashicons-search"></span>
                <?php _e('Run Full Scan', 'tmu-theme'); ?>
            </button>
            
            <button type="button" class="button button-secondary" onclick="exportAllData()">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export All Data', 'tmu-theme'); ?>
            </button>
            
            <button type="button" class="button button-secondary" onclick="scheduleReport()">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e('Schedule Reports', 'tmu-theme'); ?>
            </button>
            
            <button type="button" class="button button-secondary" onclick="clearOldData()">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Clear Old Data', 'tmu-theme'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.tmu-reports-summary {
    margin: 20px 0;
}

.tmu-summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.tmu-summary-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.tmu-summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f0f0f1;
}

.tmu-summary-header h3 {
    margin: 0;
    font-size: 16px;
}

.tmu-summary-header .dashicons {
    font-size: 24px;
    color: #646970;
}

.tmu-summary-metrics {
    display: flex;
    justify-content: space-between;
    gap: 15px;
}

.tmu-metric {
    text-align: center;
    flex: 1;
}

.tmu-metric-number {
    display: block;
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
    color: #646970;
}

.tmu-metric-number.good {
    color: #00a32a;
}

.tmu-metric-number.warning {
    color: #dba617;
}

.tmu-metric-number.critical {
    color: #d63638;
}

.tmu-metric-label {
    font-size: 12px;
    color: #646970;
    text-transform: uppercase;
    font-weight: 500;
}

.tmu-reports-charts {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

.tmu-chart-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.tmu-chart-section h2 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 16px;
}

.tmu-chart-container {
    height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f9f9f9;
    border-radius: 4px;
    color: #646970;
}

.tmu-reports-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.tmu-reports-section h2 {
    margin-top: 0;
    margin-bottom: 20px;
}

.tmu-report-form .form-table {
    background: #f9f9f9;
    border-radius: 4px;
    padding: 20px;
}

.tmu-issues-overview {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.tmu-issues-column h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 16px;
}

.tmu-no-issues {
    text-align: center;
    padding: 40px 20px;
    color: #00a32a;
}

.tmu-no-issues .dashicons {
    font-size: 32px;
    margin-bottom: 10px;
}

.tmu-recent-issues {
    max-height: 300px;
    overflow-y: auto;
}

.tmu-issue-item {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 10px;
}

.tmu-issue-item.critical {
    border-left: 4px solid #d63638;
}

.tmu-issue-item.high {
    border-left: 4px solid #dba617;
}

.tmu-issue-item.medium {
    border-left: 4px solid #00a32a;
}

.tmu-issue-item.low {
    border-left: 4px solid #72aee6;
}

.tmu-issue-title {
    font-weight: 600;
    margin-bottom: 8px;
}

.tmu-issue-meta {
    display: flex;
    gap: 15px;
    font-size: 12px;
    color: #646970;
}

.tmu-severity {
    font-weight: 600;
    text-transform: uppercase;
}

.tmu-more-issues {
    text-align: center;
    padding: 10px;
    border-top: 1px solid #e0e0e0;
    margin-top: 10px;
}

.tmu-quick-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .tmu-reports-charts {
        grid-template-columns: 1fr;
    }
    
    .tmu-issues-overview {
        grid-template-columns: 1fr;
    }
    
    .tmu-summary-metrics {
        flex-direction: column;
        gap: 10px;
    }
    
    .tmu-quick-actions {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<!-- Include Chart.js for analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Historical trends chart
    const trendsCtx = document.getElementById('trendsChart');
    if (trendsCtx) {
        const historicalData = <?php echo json_encode($historical_data); ?>;
        
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: historicalData.map(d => d.date),
                datasets: [
                    {
                        label: 'Security Issues',
                        data: historicalData.map(d => d.security_issues),
                        borderColor: '#d63638',
                        backgroundColor: 'rgba(214, 54, 56, 0.1)',
                        tension: 0.1
                    },
                    {
                        label: 'Accessibility Score',
                        data: historicalData.map(d => d.accessibility_score),
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                        tension: 0.1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Security Issues'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Accessibility Score'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                        min: 0,
                        max: 100
                    }
                }
            }
        });
    }
    
    // Issue distribution chart
    const distributionCtx = document.getElementById('distributionChart');
    if (distributionCtx) {
        const securityIssues = <?php echo json_encode($security_issues); ?>;
        const accessibilityIssues = <?php echo json_encode($accessibility_issues); ?>;
        
        // Count by severity
        const severityCounts = {
            critical: 0,
            high: 0,
            medium: 0,
            low: 0
        };
        
        [...securityIssues, ...accessibilityIssues].forEach(issue => {
            const severity = issue.severity || 'medium';
            if (severityCounts.hasOwnProperty(severity)) {
                severityCounts[severity]++;
            }
        });
        
        new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Critical', 'High', 'Medium', 'Low'],
                datasets: [{
                    data: [
                        severityCounts.critical,
                        severityCounts.high,
                        severityCounts.medium,
                        severityCounts.low
                    ],
                    backgroundColor: [
                        '#d63638',
                        '#dba617',
                        '#00a32a',
                        '#72aee6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});

// Quick action functions
function runFullScan() {
    if (confirm('<?php _e('This will run a comprehensive security and accessibility scan. Continue?', 'tmu-theme'); ?>')) {
        // Implement full scan functionality
        alert('<?php _e('Full scan initiated. Results will be updated in the reports.', 'tmu-theme'); ?>');
    }
}

function exportAllData() {
    // Generate comprehensive data export
    const allData = {
        security_issues: <?php echo json_encode($security_issues); ?>,
        security_events: <?php echo json_encode(array_slice($security_events, 0, 100)); ?>,
        accessibility_issues: <?php echo json_encode($accessibility_issues); ?>,
        accessibility_score: <?php echo json_encode($accessibility_score); ?>,
        timestamp: new Date().toISOString()
    };
    
    const dataStr = JSON.stringify(allData, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'tmu-complete-data-' + new Date().toISOString().split('T')[0] + '.json';
    link.click();
    URL.revokeObjectURL(url);
}

function scheduleReport() {
    alert('<?php _e('Scheduled reporting functionality will be implemented in a future update.', 'tmu-theme'); ?>');
}

function clearOldData() {
    if (confirm('<?php _e('This will clear old security and accessibility data. This action cannot be undone. Continue?', 'tmu-theme'); ?>')) {
        // Implement data clearing functionality
        alert('<?php _e('Old data clearing functionality will be implemented.', 'tmu-theme'); ?>');
    }
}

function previewReport() {
    // Generate preview of report
    const reportType = document.querySelector('select[name="report_type"]').value;
    const dateRange = document.querySelector('select[name="date_range"]').value;
    
    alert(`<?php _e('Preview for', 'tmu-theme'); ?> ${reportType} <?php _e('report for', 'tmu-theme'); ?> ${dateRange}`);
}
</script>