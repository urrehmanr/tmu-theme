<?php
/**
 * Security Admin Page
 * 
 * Dedicated admin interface for security management and monitoring.
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

// Get security manager instance
$security_accessibility = TMU\SecurityAndAccessibility::getInstance();
$security_manager = $security_accessibility->get_security_manager();

// Get security data
$security_issues = get_option('tmu_security_issues', []);
$security_events = get_option('tmu_security_events', []);
$security_audit = get_option('tmu_security_audit', []);

// Process security actions
if (isset($_POST['action']) && wp_verify_nonce($_POST['security_nonce'], 'tmu_security_action')) {
    switch ($_POST['action']) {
        case 'run_security_scan':
            // Trigger security scan
            do_action('tmu_run_security_scan');
            echo '<div class="notice notice-success"><p>' . __('Security scan initiated successfully!', 'tmu-theme') . '</p></div>';
            break;
        case 'clear_security_log':
            delete_option('tmu_security_events');
            echo '<div class="notice notice-success"><p>' . __('Security log cleared successfully!', 'tmu-theme') . '</p></div>';
            break;
    }
}

// Categorize security issues
$critical_issues = array_filter($security_issues, function($issue) {
    return $issue['severity'] === 'critical';
});
$high_issues = array_filter($security_issues, function($issue) {
    return $issue['severity'] === 'high';
});
$medium_issues = array_filter($security_issues, function($issue) {
    return $issue['severity'] === 'medium';
});
$low_issues = array_filter($security_issues, function($issue) {
    return $issue['severity'] === 'low';
});
?>

<div class="wrap">
    <h1><?php _e('Security Dashboard', 'tmu-theme'); ?></h1>
    
    <div class="tmu-security-header">
        <p class="description">
            <?php _e('Monitor and manage security for your TMU theme. View security events, scan for vulnerabilities, and configure security settings.', 'tmu-theme'); ?>
        </p>
    </div>

    <!-- Security Overview -->
    <div class="tmu-security-overview">
        <div class="tmu-security-stats">
            <div class="tmu-stat-card critical">
                <div class="tmu-stat-number"><?php echo count($critical_issues); ?></div>
                <div class="tmu-stat-label"><?php _e('Critical Issues', 'tmu-theme'); ?></div>
            </div>
            <div class="tmu-stat-card high">
                <div class="tmu-stat-number"><?php echo count($high_issues); ?></div>
                <div class="tmu-stat-label"><?php _e('High Priority', 'tmu-theme'); ?></div>
            </div>
            <div class="tmu-stat-card medium">
                <div class="tmu-stat-number"><?php echo count($medium_issues); ?></div>
                <div class="tmu-stat-label"><?php _e('Medium Priority', 'tmu-theme'); ?></div>
            </div>
            <div class="tmu-stat-card low">
                <div class="tmu-stat-number"><?php echo count($low_issues); ?></div>
                <div class="tmu-stat-label"><?php _e('Low Priority', 'tmu-theme'); ?></div>
            </div>
        </div>

        <!-- Security Actions -->
        <div class="tmu-security-actions">
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('tmu_security_action', 'security_nonce'); ?>
                <input type="hidden" name="action" value="run_security_scan">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-shield-alt"></span>
                    <?php _e('Run Security Scan', 'tmu-theme'); ?>
                </button>
            </form>
            
            <button type="button" class="button button-secondary" onclick="downloadSecurityReport()">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Download Report', 'tmu-theme'); ?>
            </button>
            
            <form method="post" style="display: inline;" onsubmit="return confirm('<?php _e('Are you sure you want to clear the security log?', 'tmu-theme'); ?>')">
                <?php wp_nonce_field('tmu_security_action', 'security_nonce'); ?>
                <input type="hidden" name="action" value="clear_security_log">
                <button type="submit" class="button button-secondary">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Clear Log', 'tmu-theme'); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Security Issues -->
    <div class="tmu-security-section">
        <h2><?php _e('Security Issues', 'tmu-theme'); ?></h2>
        
        <?php if (empty($security_issues)): ?>
            <div class="tmu-no-issues">
                <span class="dashicons dashicons-yes-alt"></span>
                <p><?php _e('No security issues detected. Your site appears to be secure!', 'tmu-theme'); ?></p>
            </div>
        <?php else: ?>
            <div class="tmu-issues-list">
                <?php foreach (['critical', 'high', 'medium', 'low'] as $severity): ?>
                    <?php
                    $issues = array_filter($security_issues, function($issue) use ($severity) {
                        return $issue['severity'] === $severity;
                    });
                    if (empty($issues)) continue;
                    ?>
                    
                    <div class="tmu-issue-group <?php echo esc_attr($severity); ?>">
                        <h3>
                            <span class="tmu-severity-icon dashicons dashicons-warning"></span>
                            <?php echo ucfirst($severity) . ' ' . __('Priority Issues', 'tmu-theme'); ?>
                            <span class="tmu-issue-count">(<?php echo count($issues); ?>)</span>
                        </h3>
                        
                        <div class="tmu-issue-items">
                            <?php foreach ($issues as $issue): ?>
                                <div class="tmu-issue-item">
                                    <div class="tmu-issue-title"><?php echo esc_html($issue['type'] ?? 'Security Issue'); ?></div>
                                    <div class="tmu-issue-description"><?php echo esc_html($issue['message'] ?? 'No description available'); ?></div>
                                    <div class="tmu-issue-meta">
                                        <span class="tmu-issue-time"><?php echo esc_html($issue['timestamp'] ?? 'Unknown time'); ?></span>
                                        <?php if (isset($issue['ip'])): ?>
                                            <span class="tmu-issue-ip">IP: <?php echo esc_html($issue['ip']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Security Events Log -->
    <div class="tmu-security-section">
        <h2><?php _e('Recent Security Events', 'tmu-theme'); ?></h2>
        
        <?php if (empty($security_events)): ?>
            <p><?php _e('No security events recorded yet.', 'tmu-theme'); ?></p>
        <?php else: ?>
            <div class="tmu-events-table-wrapper">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Time', 'tmu-theme'); ?></th>
                            <th><?php _e('Event Type', 'tmu-theme'); ?></th>
                            <th><?php _e('Severity', 'tmu-theme'); ?></th>
                            <th><?php _e('IP Address', 'tmu-theme'); ?></th>
                            <th><?php _e('User', 'tmu-theme'); ?></th>
                            <th><?php _e('Description', 'tmu-theme'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($security_events, 0, 50) as $event): ?>
                            <tr>
                                <td><?php echo esc_html($event['timestamp'] ?? ''); ?></td>
                                <td><?php echo esc_html($event['type'] ?? ''); ?></td>
                                <td>
                                    <span class="tmu-severity-badge <?php echo esc_attr($event['severity'] ?? 'medium'); ?>">
                                        <?php echo esc_html(ucfirst($event['severity'] ?? 'medium')); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($event['ip'] ?? ''); ?></td>
                                <td>
                                    <?php
                                    $user_id = $event['user_id'] ?? 0;
                                    if ($user_id) {
                                        $user = get_user_by('id', $user_id);
                                        echo esc_html($user ? $user->user_login : 'Unknown');
                                    } else {
                                        echo __('Guest', 'tmu-theme');
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html($event['data']['message'] ?? 'No description'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Security Audit Results -->
    <?php if (!empty($security_audit)): ?>
        <div class="tmu-security-section">
            <h2><?php _e('Security Audit Results', 'tmu-theme'); ?></h2>
            
            <div class="tmu-audit-results">
                <?php if (isset($security_audit['file_permissions'])): ?>
                    <div class="tmu-audit-item">
                        <h4><?php _e('File Permissions', 'tmu-theme'); ?></h4>
                        <div class="tmu-audit-status <?php echo esc_attr($security_audit['file_permissions']['status']); ?>">
                            <?php echo esc_html(ucfirst($security_audit['file_permissions']['status'])); ?>
                        </div>
                        <?php if (!empty($security_audit['file_permissions']['issues'])): ?>
                            <ul class="tmu-audit-issues">
                                <?php foreach ($security_audit['file_permissions']['issues'] as $issue): ?>
                                    <li><?php echo esc_html($issue); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($security_audit['plugin_security'])): ?>
                    <div class="tmu-audit-item">
                        <h4><?php _e('Plugin Security', 'tmu-theme'); ?></h4>
                        <div class="tmu-audit-status <?php echo esc_attr($security_audit['plugin_security']['status']); ?>">
                            <?php echo esc_html(ucfirst($security_audit['plugin_security']['status'])); ?>
                        </div>
                        <?php if (!empty($security_audit['plugin_security']['issues'])): ?>
                            <ul class="tmu-audit-issues">
                                <?php foreach ($security_audit['plugin_security']['issues'] as $issue): ?>
                                    <li><?php echo esc_html($issue); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($security_audit['wp_version'])): ?>
                    <div class="tmu-audit-item">
                        <h4><?php _e('WordPress Version', 'tmu-theme'); ?></h4>
                        <div class="tmu-audit-status <?php echo esc_attr($security_audit['wp_version']['status']); ?>">
                            <?php echo esc_html(ucfirst($security_audit['wp_version']['status'])); ?>
                        </div>
                        <?php if (!empty($security_audit['wp_version']['issues'])): ?>
                            <ul class="tmu-audit-issues">
                                <?php foreach ($security_audit['wp_version']['issues'] as $issue): ?>
                                    <li><?php echo esc_html($issue); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($security_audit['ssl_status'])): ?>
                    <div class="tmu-audit-item">
                        <h4><?php _e('SSL Status', 'tmu-theme'); ?></h4>
                        <div class="tmu-audit-status <?php echo esc_attr($security_audit['ssl_status']['status']); ?>">
                            <?php echo esc_html(ucfirst($security_audit['ssl_status']['status'])); ?>
                        </div>
                        <?php if (!empty($security_audit['ssl_status']['issues'])): ?>
                            <ul class="tmu-audit-issues">
                                <?php foreach ($security_audit['ssl_status']['issues'] as $issue): ?>
                                    <li><?php echo esc_html($issue); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.tmu-security-overview {
    margin: 20px 0;
}

.tmu-security-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.tmu-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    min-width: 120px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.tmu-stat-card.critical {
    border-left: 4px solid #d63638;
}

.tmu-stat-card.high {
    border-left: 4px solid #dba617;
}

.tmu-stat-card.medium {
    border-left: 4px solid #00a32a;
}

.tmu-stat-card.low {
    border-left: 4px solid #72aee6;
}

.tmu-stat-number {
    font-size: 36px;
    font-weight: bold;
    line-height: 1;
    margin-bottom: 5px;
}

.tmu-stat-card.critical .tmu-stat-number {
    color: #d63638;
}

.tmu-stat-card.high .tmu-stat-number {
    color: #dba617;
}

.tmu-stat-card.medium .tmu-stat-number {
    color: #00a32a;
}

.tmu-stat-card.low .tmu-stat-number {
    color: #72aee6;
}

.tmu-stat-label {
    font-size: 12px;
    text-transform: uppercase;
    color: #646970;
    font-weight: 500;
}

.tmu-security-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.tmu-security-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.tmu-no-issues {
    text-align: center;
    padding: 40px;
    color: #00a32a;
}

.tmu-no-issues .dashicons {
    font-size: 48px;
    margin-bottom: 10px;
}

.tmu-issue-group {
    margin-bottom: 30px;
}

.tmu-issue-group h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ccd0d4;
}

.tmu-issue-group.critical h3 {
    color: #d63638;
}

.tmu-issue-group.high h3 {
    color: #dba617;
}

.tmu-issue-group.medium h3 {
    color: #00a32a;
}

.tmu-issue-group.low h3 {
    color: #72aee6;
}

.tmu-issue-count {
    background: #f0f0f1;
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 12px;
    font-weight: normal;
}

.tmu-issue-item {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 10px;
}

.tmu-issue-title {
    font-weight: 600;
    margin-bottom: 5px;
}

.tmu-issue-description {
    color: #646970;
    margin-bottom: 10px;
}

.tmu-issue-meta {
    display: flex;
    gap: 15px;
    font-size: 12px;
    color: #646970;
}

.tmu-events-table-wrapper {
    overflow-x: auto;
}

.tmu-severity-badge {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.tmu-severity-badge.critical {
    background: #f8d7da;
    color: #721c24;
}

.tmu-severity-badge.high {
    background: #fff3cd;
    color: #856404;
}

.tmu-severity-badge.medium {
    background: #d4edda;
    color: #155724;
}

.tmu-severity-badge.low {
    background: #d1ecf1;
    color: #0c5460;
}

.tmu-audit-results {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.tmu-audit-item {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 15px;
}

.tmu-audit-item h4 {
    margin: 0 0 10px 0;
}

.tmu-audit-status {
    padding: 5px 10px;
    border-radius: 3px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    margin-bottom: 10px;
    display: inline-block;
}

.tmu-audit-status.secure {
    background: #d4edda;
    color: #155724;
}

.tmu-audit-status.warning {
    background: #fff3cd;
    color: #856404;
}

.tmu-audit-status.critical {
    background: #f8d7da;
    color: #721c24;
}

.tmu-audit-issues {
    margin: 0;
    padding-left: 20px;
}

.tmu-audit-issues li {
    margin-bottom: 5px;
    font-size: 13px;
    color: #646970;
}
</style>

<script>
function downloadSecurityReport() {
    // Generate CSV report
    const issues = <?php echo json_encode($security_issues); ?>;
    const events = <?php echo json_encode(array_slice($security_events, 0, 100)); ?>;
    
    let csv = "Security Report\n\n";
    csv += "Issues\n";
    csv += "Type,Severity,Message,Timestamp\n";
    
    issues.forEach(issue => {
        csv += `"${issue.type || ''}","${issue.severity || ''}","${issue.message || ''}","${issue.timestamp || ''}"\n`;
    });
    
    csv += "\n\nEvents\n";
    csv += "Type,Severity,IP,User ID,Timestamp,Message\n";
    
    events.forEach(event => {
        csv += `"${event.type || ''}","${event.severity || ''}","${event.ip || ''}","${event.user_id || ''}","${event.timestamp || ''}","${event.data?.message || ''}"\n`;
    });
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'tmu-security-report-' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>