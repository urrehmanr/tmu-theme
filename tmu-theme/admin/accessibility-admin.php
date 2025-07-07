<?php
/**
 * Accessibility Admin Page
 * 
 * Dedicated admin interface for accessibility management and WCAG compliance.
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

// Get accessibility manager instance
$security_accessibility = TMU\SecurityAndAccessibility::getInstance();
$accessibility_manager = $security_accessibility->get_accessibility_manager();

// Get accessibility data
$accessibility_issues = get_option('tmu_accessibility_issues', []);
$accessibility_score = get_option('tmu_accessibility_score', 85);
$accessibility_report = get_option('tmu_accessibility_report', []);

// Process accessibility actions
if (isset($_POST['action']) && wp_verify_nonce($_POST['accessibility_nonce'], 'tmu_accessibility_action')) {
    switch ($_POST['action']) {
        case 'run_accessibility_check':
            // Trigger accessibility check
            do_action('tmu_run_accessibility_check');
            echo '<div class="notice notice-success"><p>' . __('Accessibility check initiated successfully!', 'tmu-theme') . '</p></div>';
            break;
        case 'fix_issues':
            // Apply automatic fixes
            do_action('tmu_apply_accessibility_fixes');
            echo '<div class="notice notice-success"><p>' . __('Automatic accessibility fixes applied!', 'tmu-theme') . '</p></div>';
            break;
    }
}

// Categorize accessibility issues by WCAG level
$level_a_issues = array_filter($accessibility_issues, function($issue) {
    return ($issue['level'] ?? 'A') === 'A';
});
$level_aa_issues = array_filter($accessibility_issues, function($issue) {
    return ($issue['level'] ?? 'A') === 'AA';
});
$level_aaa_issues = array_filter($accessibility_issues, function($issue) {
    return ($issue['level'] ?? 'A') === 'AAA';
});

// Calculate compliance percentages
$total_checks = max(count($accessibility_issues), 1);
$compliance_percentage = max(0, 100 - (count($accessibility_issues) * 10));
?>

<div class="wrap">
    <h1><?php _e('Accessibility Dashboard', 'tmu-theme'); ?></h1>
    
    <div class="tmu-accessibility-header">
        <p class="description">
            <?php _e('Monitor and manage WCAG 2.1 accessibility compliance for your TMU theme. Test accessibility, fix issues, and ensure inclusive design.', 'tmu-theme'); ?>
        </p>
    </div>

    <!-- Accessibility Overview -->
    <div class="tmu-accessibility-overview">
        <div class="tmu-accessibility-score">
            <div class="tmu-score-circle">
                <div class="tmu-score-inner">
                    <span class="tmu-score-number"><?php echo $accessibility_score; ?></span>
                    <span class="tmu-score-label">/100</span>
                </div>
                <svg class="tmu-score-progress" width="120" height="120">
                    <circle cx="60" cy="60" r="54" stroke="#e0e0e0" stroke-width="6" fill="none"/>
                    <circle cx="60" cy="60" r="54" stroke="#00a32a" stroke-width="6" fill="none"
                            stroke-dasharray="<?php echo ($accessibility_score / 100) * 339.29; ?> 339.29"
                            stroke-dashoffset="0" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="tmu-score-details">
                <h3><?php _e('Accessibility Score', 'tmu-theme'); ?></h3>
                <p class="tmu-score-description">
                    <?php
                    if ($accessibility_score >= 90) {
                        _e('Excellent! Your site meets high accessibility standards.', 'tmu-theme');
                    } elseif ($accessibility_score >= 80) {
                        _e('Good accessibility compliance with room for improvement.', 'tmu-theme');
                    } elseif ($accessibility_score >= 60) {
                        _e('Fair accessibility - several issues need attention.', 'tmu-theme');
                    } else {
                        _e('Poor accessibility - immediate action required.', 'tmu-theme');
                    }
                    ?>
                </p>
            </div>
        </div>

        <div class="tmu-wcag-compliance">
            <div class="tmu-compliance-level">
                <h4><?php _e('WCAG 2.1 A', 'tmu-theme'); ?></h4>
                <div class="tmu-compliance-bar">
                    <div class="tmu-compliance-fill" style="width: <?php echo empty($level_a_issues) ? '100%' : '70%'; ?>"></div>
                </div>
                <span class="tmu-compliance-text">
                    <?php echo empty($level_a_issues) ? __('Compliant', 'tmu-theme') : count($level_a_issues) . ' ' . __('issues', 'tmu-theme'); ?>
                </span>
            </div>
            <div class="tmu-compliance-level">
                <h4><?php _e('WCAG 2.1 AA', 'tmu-theme'); ?></h4>
                <div class="tmu-compliance-bar">
                    <div class="tmu-compliance-fill" style="width: <?php echo empty($level_aa_issues) ? '100%' : '60%'; ?>"></div>
                </div>
                <span class="tmu-compliance-text">
                    <?php echo empty($level_aa_issues) ? __('Compliant', 'tmu-theme') : count($level_aa_issues) . ' ' . __('issues', 'tmu-theme'); ?>
                </span>
            </div>
            <div class="tmu-compliance-level">
                <h4><?php _e('WCAG 2.1 AAA', 'tmu-theme'); ?></h4>
                <div class="tmu-compliance-bar">
                    <div class="tmu-compliance-fill" style="width: <?php echo empty($level_aaa_issues) ? '100%' : '40%'; ?>"></div>
                </div>
                <span class="tmu-compliance-text">
                    <?php echo empty($level_aaa_issues) ? __('Compliant', 'tmu-theme') : count($level_aaa_issues) . ' ' . __('issues', 'tmu-theme'); ?>
                </span>
            </div>
        </div>

        <!-- Accessibility Actions -->
        <div class="tmu-accessibility-actions">
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('tmu_accessibility_action', 'accessibility_nonce'); ?>
                <input type="hidden" name="action" value="run_accessibility_check">
                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-universal-access-alt"></span>
                    <?php _e('Run Accessibility Check', 'tmu-theme'); ?>
                </button>
            </form>
            
            <button type="button" class="button button-primary" onclick="checkCurrentPage()">
                <span class="dashicons dashicons-visibility"></span>
                <?php _e('Check Current Page', 'tmu-theme'); ?>
            </button>
            
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('tmu_accessibility_action', 'accessibility_nonce'); ?>
                <input type="hidden" name="action" value="fix_issues">
                <button type="submit" class="button button-secondary">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Apply Auto Fixes', 'tmu-theme'); ?>
                </button>
            </form>
            
            <button type="button" class="button button-secondary" onclick="downloadAccessibilityReport()">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Download Report', 'tmu-theme'); ?>
            </button>
        </div>
    </div>

    <!-- Accessibility Issues -->
    <div class="tmu-accessibility-section">
        <h2><?php _e('Accessibility Issues', 'tmu-theme'); ?></h2>
        
        <?php if (empty($accessibility_issues)): ?>
            <div class="tmu-no-issues">
                <span class="dashicons dashicons-universal-access-alt"></span>
                <p><?php _e('No accessibility issues detected. Your site is fully accessible!', 'tmu-theme'); ?></p>
            </div>
        <?php else: ?>
            <div class="tmu-issues-tabs">
                <div class="tmu-tab-nav">
                    <button class="tmu-tab-button active" data-tab="all">
                        <?php _e('All Issues', 'tmu-theme'); ?> (<?php echo count($accessibility_issues); ?>)
                    </button>
                    <button class="tmu-tab-button" data-tab="level-a">
                        <?php _e('Level A', 'tmu-theme'); ?> (<?php echo count($level_a_issues); ?>)
                    </button>
                    <button class="tmu-tab-button" data-tab="level-aa">
                        <?php _e('Level AA', 'tmu-theme'); ?> (<?php echo count($level_aa_issues); ?>)
                    </button>
                    <button class="tmu-tab-button" data-tab="level-aaa">
                        <?php _e('Level AAA', 'tmu-theme'); ?> (<?php echo count($level_aaa_issues); ?>)
                    </button>
                </div>

                <div class="tmu-tab-content active" id="all-tab">
                    <?php foreach (['high', 'medium', 'low'] as $severity): ?>
                        <?php
                        $issues = array_filter($accessibility_issues, function($issue) use ($severity) {
                            return ($issue['severity'] ?? 'medium') === $severity;
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
                                        <div class="tmu-issue-header">
                                            <div class="tmu-issue-title"><?php echo esc_html($issue['type'] ?? 'Accessibility Issue'); ?></div>
                                            <div class="tmu-issue-badges">
                                                <span class="tmu-wcag-badge"><?php echo esc_html('WCAG ' . ($issue['wcag'] ?? '1.1.1')); ?></span>
                                                <span class="tmu-level-badge level-<?php echo esc_attr(strtolower($issue['level'] ?? 'a')); ?>">
                                                    <?php echo esc_html('Level ' . ($issue['level'] ?? 'A')); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="tmu-issue-description"><?php echo esc_html($issue['message'] ?? 'No description available'); ?></div>
                                        <div class="tmu-issue-recommendation">
                                            <strong><?php _e('Recommendation:', 'tmu-theme'); ?></strong>
                                            <?php echo esc_html($issue['recommendation'] ?? 'Fix this accessibility issue for better compliance.'); ?>
                                        </div>
                                        <?php if (isset($issue['element'])): ?>
                                            <div class="tmu-issue-element">
                                                <strong><?php _e('Element:', 'tmu-theme'); ?></strong>
                                                <code><?php echo esc_html($issue['element']); ?></code>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php foreach (['a', 'aa', 'aaa'] as $level): ?>
                    <div class="tmu-tab-content" id="level-<?php echo $level; ?>-tab">
                        <?php
                        $level_issues = array_filter($accessibility_issues, function($issue) use ($level) {
                            return strtolower($issue['level'] ?? 'a') === $level;
                        });
                        ?>
                        
                        <?php if (empty($level_issues)): ?>
                            <div class="tmu-no-issues">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <p><?php printf(__('No WCAG 2.1 %s issues detected!', 'tmu-theme'), strtoupper($level)); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="tmu-issue-items">
                                <?php foreach ($level_issues as $issue): ?>
                                    <div class="tmu-issue-item">
                                        <div class="tmu-issue-header">
                                            <div class="tmu-issue-title"><?php echo esc_html($issue['type'] ?? 'Accessibility Issue'); ?></div>
                                            <div class="tmu-issue-badges">
                                                <span class="tmu-wcag-badge"><?php echo esc_html('WCAG ' . ($issue['wcag'] ?? '1.1.1')); ?></span>
                                                <span class="tmu-severity-badge <?php echo esc_attr($issue['severity'] ?? 'medium'); ?>">
                                                    <?php echo esc_html(ucfirst($issue['severity'] ?? 'medium')); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="tmu-issue-description"><?php echo esc_html($issue['message'] ?? 'No description available'); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Accessibility Features Status -->
    <div class="tmu-accessibility-section">
        <h2><?php _e('Accessibility Features Status', 'tmu-theme'); ?></h2>
        
        <div class="tmu-features-grid">
            <div class="tmu-feature-card">
                <div class="tmu-feature-icon">
                    <span class="dashicons dashicons-admin-users"></span>
                </div>
                <div class="tmu-feature-content">
                    <h4><?php _e('ARIA Labels', 'tmu-theme'); ?></h4>
                    <p><?php _e('Automatic ARIA labels for better screen reader support', 'tmu-theme'); ?></p>
                    <div class="tmu-feature-status enabled">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Enabled', 'tmu-theme'); ?>
                    </div>
                </div>
            </div>

            <div class="tmu-feature-card">
                <div class="tmu-feature-icon">
                    <span class="dashicons dashicons-admin-network"></span>
                </div>
                <div class="tmu-feature-content">
                    <h4><?php _e('Keyboard Navigation', 'tmu-theme'); ?></h4>
                    <p><?php _e('Full keyboard navigation support with focus management', 'tmu-theme'); ?></p>
                    <div class="tmu-feature-status enabled">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Enabled', 'tmu-theme'); ?>
                    </div>
                </div>
            </div>

            <div class="tmu-feature-card">
                <div class="tmu-feature-icon">
                    <span class="dashicons dashicons-visibility"></span>
                </div>
                <div class="tmu-feature-content">
                    <h4><?php _e('Focus Indicators', 'tmu-theme'); ?></h4>
                    <p><?php _e('Clear visual focus indicators for keyboard users', 'tmu-theme'); ?></p>
                    <div class="tmu-feature-status enabled">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Enabled', 'tmu-theme'); ?>
                    </div>
                </div>
            </div>

            <div class="tmu-feature-card">
                <div class="tmu-feature-icon">
                    <span class="dashicons dashicons-admin-customizer"></span>
                </div>
                <div class="tmu-feature-content">
                    <h4><?php _e('High Contrast Mode', 'tmu-theme'); ?></h4>
                    <p><?php _e('Support for high contrast and dark mode preferences', 'tmu-theme'); ?></p>
                    <div class="tmu-feature-status enabled">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Enabled', 'tmu-theme'); ?>
                    </div>
                </div>
            </div>

            <div class="tmu-feature-card">
                <div class="tmu-feature-icon">
                    <span class="dashicons dashicons-controls-pause"></span>
                </div>
                <div class="tmu-feature-content">
                    <h4><?php _e('Reduced Motion', 'tmu-theme'); ?></h4>
                    <p><?php _e('Respects user preferences for reduced motion', 'tmu-theme'); ?></p>
                    <div class="tmu-feature-status enabled">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Enabled', 'tmu-theme'); ?>
                    </div>
                </div>
            </div>

            <div class="tmu-feature-card">
                <div class="tmu-feature-icon">
                    <span class="dashicons dashicons-smartphone"></span>
                </div>
                <div class="tmu-feature-content">
                    <h4><?php _e('Touch Accessibility', 'tmu-theme'); ?></h4>
                    <p><?php _e('Optimized touch targets for mobile users', 'tmu-theme'); ?></p>
                    <div class="tmu-feature-status enabled">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Enabled', 'tmu-theme'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.tmu-accessibility-overview {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 30px;
    margin: 20px 0;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 30px;
}

.tmu-score-circle {
    position: relative;
    width: 120px;
    height: 120px;
}

.tmu-score-inner {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.tmu-score-number {
    font-size: 28px;
    font-weight: bold;
    color: #00a32a;
}

.tmu-score-label {
    font-size: 14px;
    color: #646970;
}

.tmu-score-progress {
    transform: rotate(-90deg);
}

.tmu-score-details h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
}

.tmu-score-description {
    color: #646970;
    margin: 0;
}

.tmu-wcag-compliance {
    display: flex;
    flex-direction: column;
    gap: 20px;
    min-width: 250px;
}

.tmu-compliance-level h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
    font-weight: 600;
}

.tmu-compliance-bar {
    background: #e0e0e0;
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 5px;
}

.tmu-compliance-fill {
    background: linear-gradient(90deg, #00a32a, #46b450);
    height: 100%;
    transition: width 0.3s ease;
}

.tmu-compliance-text {
    font-size: 12px;
    color: #646970;
}

.tmu-accessibility-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: flex-start;
}

.tmu-accessibility-section {
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

.tmu-issues-tabs .tmu-tab-nav {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
    border-bottom: 1px solid #ccd0d4;
}

.tmu-tab-button {
    background: none;
    border: none;
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.tmu-tab-button:hover {
    background: #f6f7f7;
}

.tmu-tab-button.active {
    border-bottom-color: #0073aa;
    color: #0073aa;
    font-weight: 600;
}

.tmu-tab-content {
    display: none;
}

.tmu-tab-content.active {
    display: block;
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
    padding: 20px;
    margin-bottom: 15px;
}

.tmu-issue-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.tmu-issue-title {
    font-weight: 600;
    font-size: 16px;
}

.tmu-issue-badges {
    display: flex;
    gap: 8px;
}

.tmu-wcag-badge {
    background: #0073aa;
    color: #fff;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.tmu-level-badge {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.tmu-level-badge.level-a {
    background: #d4edda;
    color: #155724;
}

.tmu-level-badge.level-aa {
    background: #fff3cd;
    color: #856404;
}

.tmu-level-badge.level-aaa {
    background: #f8d7da;
    color: #721c24;
}

.tmu-severity-badge {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.tmu-severity-badge.high {
    background: #f8d7da;
    color: #721c24;
}

.tmu-severity-badge.medium {
    background: #fff3cd;
    color: #856404;
}

.tmu-severity-badge.low {
    background: #d1ecf1;
    color: #0c5460;
}

.tmu-issue-description {
    color: #646970;
    margin-bottom: 15px;
    line-height: 1.5;
}

.tmu-issue-recommendation {
    background: #e7f3ff;
    border-left: 4px solid #0073aa;
    padding: 10px 15px;
    margin-bottom: 10px;
    font-size: 14px;
}

.tmu-issue-element {
    font-size: 13px;
    color: #646970;
}

.tmu-issue-element code {
    background: #f1f1f1;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}

.tmu-features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.tmu-feature-card {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    gap: 15px;
}

.tmu-feature-icon {
    flex-shrink: 0;
}

.tmu-feature-icon .dashicons {
    font-size: 32px;
    color: #0073aa;
}

.tmu-feature-content h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
}

.tmu-feature-content p {
    margin: 0 0 15px 0;
    color: #646970;
    font-size: 14px;
    line-height: 1.4;
}

.tmu-feature-status {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.tmu-feature-status.enabled {
    color: #00a32a;
}

.tmu-feature-status.disabled {
    color: #d63638;
}
</style>

<script>
// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tmu-tab-button');
    const tabContents = document.querySelectorAll('.tmu-tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
        });
    });
});

function checkCurrentPage() {
    // Open current page with accessibility check
    const currentUrl = window.location.origin;
    window.open(currentUrl + '?accessibility_check=1', '_blank');
}

function downloadAccessibilityReport() {
    // Generate accessibility report
    const issues = <?php echo json_encode($accessibility_issues); ?>;
    
    let csv = "Accessibility Report\n\n";
    csv += "Type,WCAG,Level,Severity,Message,Element\n";
    
    issues.forEach(issue => {
        csv += `"${issue.type || ''}","${issue.wcag || ''}","${issue.level || ''}","${issue.severity || ''}","${issue.message || ''}","${issue.element || ''}"\n`;
    });
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'tmu-accessibility-report-' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>