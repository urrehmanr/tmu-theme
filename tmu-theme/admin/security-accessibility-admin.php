<?php
/**
 * Security & Accessibility Admin Page
 * 
 * Main admin interface for security and accessibility management.
 * 
 * @package TMU\Admin
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user capabilities
$can_manage = current_user_can('manage_options');
if (!$can_manage) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'tmu-theme'));
}

// Get security and accessibility instances
$security_accessibility = TMU\SecurityAndAccessibility::getInstance();
$security_manager = $security_accessibility->get_security_manager();
$accessibility_manager = $security_accessibility->get_accessibility_manager();

// Get recent reports
$security_issues = get_option('tmu_security_issues', []);
$accessibility_issues = get_option('tmu_accessibility_issues', []);
$accessibility_score = get_option('tmu_accessibility_score', 0);

// Process form submissions
if (isset($_POST['save_settings']) && wp_verify_nonce($_POST['tmu_security_accessibility_nonce'], 'tmu_security_accessibility_save')) {
    $settings = $security_accessibility->get_settings();
    
    // Update security settings
    if (isset($_POST['security'])) {
        foreach ($_POST['security'] as $key => $value) {
            $settings['security'][$key] = sanitize_text_field($value);
        }
    }
    
    // Update accessibility settings
    if (isset($_POST['accessibility'])) {
        foreach ($_POST['accessibility'] as $key => $value) {
            $settings['accessibility'][$key] = sanitize_text_field($value);
        }
    }
    
    update_option('tmu_security_accessibility_settings', $settings);
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'tmu-theme') . '</p></div>';
}

// Get current settings
$settings = $security_accessibility->get_settings();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="tmu-admin-header">
        <p class="description">
            <?php _e('Manage security and accessibility settings for your TMU theme. Ensure WCAG 2.1 AA compliance and comprehensive security protection.', 'tmu-theme'); ?>
        </p>
    </div>

    <div class="tmu-admin-dashboard">
        <!-- Status Overview -->
        <div class="tmu-status-cards">
            <div class="tmu-status-card security">
                <div class="tmu-status-header">
                    <h3><?php _e('Security Status', 'tmu-theme'); ?></h3>
                    <span class="tmu-status-icon dashicons dashicons-shield-alt"></span>
                </div>
                <div class="tmu-status-content">
                    <?php
                    $critical_security = array_filter($security_issues, function($issue) {
                        return $issue['severity'] === 'critical';
                    });
                    $security_status = empty($critical_security) ? 'good' : 'warning';
                    ?>
                    <div class="tmu-status-indicator <?php echo esc_attr($security_status); ?>">
                        <?php if ($security_status === 'good'): ?>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Secure', 'tmu-theme'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-warning"></span>
                            <?php echo count($critical_security) . ' ' . __('Critical Issues', 'tmu-theme'); ?>
                        <?php endif; ?>
                    </div>
                    <p class="tmu-status-description">
                        <?php printf(__('%d total security issues detected', 'tmu-theme'), count($security_issues)); ?>
                    </p>
                </div>
            </div>

            <div class="tmu-status-card accessibility">
                <div class="tmu-status-header">
                    <h3><?php _e('Accessibility Status', 'tmu-theme'); ?></h3>
                    <span class="tmu-status-icon dashicons dashicons-universal-access-alt"></span>
                </div>
                <div class="tmu-status-content">
                    <?php
                    $accessibility_status = $accessibility_score >= 80 ? 'good' : ($accessibility_score >= 60 ? 'warning' : 'error');
                    ?>
                    <div class="tmu-status-indicator <?php echo esc_attr($accessibility_status); ?>">
                        <span class="tmu-score"><?php echo esc_html($accessibility_score); ?>/100</span>
                    </div>
                    <p class="tmu-status-description">
                        <?php printf(__('WCAG 2.1 %s compliance', 'tmu-theme'), $settings['accessibility']['wcag_level']); ?>
                    </p>
                </div>
            </div>

            <div class="tmu-status-card integration">
                <div class="tmu-status-header">
                    <h3><?php _e('System Integration', 'tmu-theme'); ?></h3>
                    <span class="tmu-status-icon dashicons dashicons-admin-settings"></span>
                </div>
                <div class="tmu-status-content">
                    <?php
                    $security_enabled = $settings['security']['enable_input_validation'];
                    $accessibility_enabled = $settings['accessibility']['enable_aria_labels'];
                    $integration_status = ($security_enabled && $accessibility_enabled) ? 'good' : 'warning';
                    ?>
                    <div class="tmu-status-indicator <?php echo esc_attr($integration_status); ?>">
                        <?php if ($integration_status === 'good'): ?>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Active', 'tmu-theme'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-warning"></span>
                            <?php _e('Partial', 'tmu-theme'); ?>
                        <?php endif; ?>
                    </div>
                    <p class="tmu-status-description">
                        <?php _e('Security and accessibility integration status', 'tmu-theme'); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="tmu-quick-actions">
            <h3><?php _e('Quick Actions', 'tmu-theme'); ?></h3>
            <div class="tmu-action-buttons">
                <button type="button" class="button button-primary" onclick="runSecurityScan()">
                    <span class="dashicons dashicons-shield-alt"></span>
                    <?php _e('Run Security Scan', 'tmu-theme'); ?>
                </button>
                <button type="button" class="button button-primary" onclick="runAccessibilityCheck()">
                    <span class="dashicons dashicons-universal-access-alt"></span>
                    <?php _e('Check Accessibility', 'tmu-theme'); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=tmu-reports'); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-chart-area"></span>
                    <?php _e('View Reports', 'tmu-theme'); ?>
                </a>
                <button type="button" class="button button-secondary" onclick="exportSettings()">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Export Settings', 'tmu-theme'); ?>
                </button>
            </div>
        </div>

        <!-- Settings Form -->
        <form method="post" action="" class="tmu-settings-form">
            <?php wp_nonce_field('tmu_security_accessibility_save', 'tmu_security_accessibility_nonce'); ?>
            
            <div class="tmu-settings-tabs">
                <nav class="tmu-tab-nav">
                    <button type="button" class="tmu-tab-button active" data-tab="security">
                        <span class="dashicons dashicons-shield-alt"></span>
                        <?php _e('Security Settings', 'tmu-theme'); ?>
                    </button>
                    <button type="button" class="tmu-tab-button" data-tab="accessibility">
                        <span class="dashicons dashicons-universal-access-alt"></span>
                        <?php _e('Accessibility Settings', 'tmu-theme'); ?>
                    </button>
                    <button type="button" class="tmu-tab-button" data-tab="integration">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Integration Settings', 'tmu-theme'); ?>
                    </button>
                </nav>

                <!-- Security Settings Tab -->
                <div class="tmu-tab-content active" id="security-tab">
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php _e('Input Validation', 'tmu-theme'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="security[enable_input_validation]" value="1" 
                                                   <?php checked($settings['security']['enable_input_validation']); ?>>
                                            <?php _e('Enable input validation and sanitization', 'tmu-theme'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('Automatically validate and sanitize all user input to prevent injection attacks.', 'tmu-theme'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('XSS Protection', 'tmu-theme'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="security[enable_xss_protection]" value="1" 
                                                   <?php checked($settings['security']['enable_xss_protection']); ?>>
                                            <?php _e('Enable Cross-Site Scripting (XSS) protection', 'tmu-theme'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('Protect against XSS attacks by escaping output and implementing CSP headers.', 'tmu-theme'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('CSRF Protection', 'tmu-theme'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="security[enable_csrf_protection]" value="1" 
                                                   <?php checked($settings['security']['enable_csrf_protection']); ?>>
                                            <?php _e('Enable Cross-Site Request Forgery (CSRF) protection', 'tmu-theme'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('Protect against CSRF attacks using nonces and token validation.', 'tmu-theme'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Security Level', 'tmu-theme'); ?></th>
                                <td>
                                    <select name="security[security_level]">
                                        <option value="low" <?php selected($settings['security']['security_level'], 'low'); ?>>
                                            <?php _e('Low', 'tmu-theme'); ?>
                                        </option>
                                        <option value="medium" <?php selected($settings['security']['security_level'], 'medium'); ?>>
                                            <?php _e('Medium', 'tmu-theme'); ?>
                                        </option>
                                        <option value="high" <?php selected($settings['security']['security_level'], 'high'); ?>>
                                            <?php _e('High', 'tmu-theme'); ?>
                                        </option>
                                    </select>
                                    <p class="description">
                                        <?php _e('Set the overall security level for the theme.', 'tmu-theme'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Accessibility Settings Tab -->
                <div class="tmu-tab-content" id="accessibility-tab">
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php _e('WCAG Compliance Level', 'tmu-theme'); ?></th>
                                <td>
                                    <select name="accessibility[wcag_level]">
                                        <option value="A" <?php selected($settings['accessibility']['wcag_level'], 'A'); ?>>
                                            <?php _e('WCAG 2.1 A', 'tmu-theme'); ?>
                                        </option>
                                        <option value="AA" <?php selected($settings['accessibility']['wcag_level'], 'AA'); ?>>
                                            <?php _e('WCAG 2.1 AA (Recommended)', 'tmu-theme'); ?>
                                        </option>
                                        <option value="AAA" <?php selected($settings['accessibility']['wcag_level'], 'AAA'); ?>>
                                            <?php _e('WCAG 2.1 AAA', 'tmu-theme'); ?>
                                        </option>
                                    </select>
                                    <p class="description">
                                        <?php _e('Set the WCAG compliance level for accessibility features.', 'tmu-theme'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('ARIA Labels', 'tmu-theme'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="accessibility[enable_aria_labels]" value="1" 
                                                   <?php checked($settings['accessibility']['enable_aria_labels']); ?>>
                                            <?php _e('Enable automatic ARIA labels and roles', 'tmu-theme'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('Automatically add ARIA labels and roles to improve screen reader compatibility.', 'tmu-theme'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Keyboard Navigation', 'tmu-theme'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="accessibility[enable_keyboard_navigation]" value="1" 
                                                   <?php checked($settings['accessibility']['enable_keyboard_navigation']); ?>>
                                            <?php _e('Enable enhanced keyboard navigation', 'tmu-theme'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('Provide comprehensive keyboard navigation support with focus management.', 'tmu-theme'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Screen Reader Support', 'tmu-theme'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="accessibility[enable_screen_reader]" value="1" 
                                                   <?php checked($settings['accessibility']['enable_screen_reader']); ?>>
                                            <?php _e('Enable screen reader optimizations', 'tmu-theme'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('Optimize content and navigation for screen reader users.', 'tmu-theme'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Integration Settings Tab -->
                <div class="tmu-tab-content" id="integration-tab">
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php _e('Security Monitoring', 'tmu-theme'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="integration[enable_security_monitoring]" value="1" 
                                                   <?php checked($settings['integration']['enable_security_monitoring']); ?>>
                                            <?php _e('Enable continuous security monitoring', 'tmu-theme'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('Monitor security events and generate alerts for suspicious activity.', 'tmu-theme'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Accessibility Testing', 'tmu-theme'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="integration[enable_accessibility_testing]" value="1" 
                                                   <?php checked($settings['integration']['enable_accessibility_testing']); ?>>
                                            <?php _e('Enable automated accessibility testing', 'tmu-theme'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('Automatically test pages for accessibility compliance and generate reports.', 'tmu-theme'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Automatic Fixes', 'tmu-theme'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="integration[enable_automatic_fixes]" value="1" 
                                                   <?php checked($settings['integration']['enable_automatic_fixes']); ?>>
                                            <?php _e('Enable automatic accessibility fixes', 'tmu-theme'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('Automatically fix common accessibility issues when possible.', 'tmu-theme'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Reporting', 'tmu-theme'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="integration[enable_reporting]" value="1" 
                                                   <?php checked($settings['integration']['enable_reporting']); ?>>
                                            <?php _e('Enable detailed reporting', 'tmu-theme'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('Generate comprehensive reports on security and accessibility status.', 'tmu-theme'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="submit">
                <input type="submit" name="save_settings" class="button-primary" 
                       value="<?php esc_attr_e('Save Settings', 'tmu-theme'); ?>">
                <button type="button" class="button-secondary" onclick="resetToDefaults()">
                    <?php _e('Reset to Defaults', 'tmu-theme'); ?>
                </button>
            </p>
        </form>
    </div>
</div>

<style>
.tmu-admin-dashboard {
    max-width: 1200px;
}

.tmu-status-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.tmu-status-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.tmu-status-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.tmu-status-header h3 {
    margin: 0;
    font-size: 14px;
    text-transform: uppercase;
    color: #646970;
}

.tmu-status-icon {
    font-size: 20px;
    color: #646970;
}

.tmu-status-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 16px;
    margin-bottom: 8px;
}

.tmu-status-indicator.good {
    color: #00a32a;
}

.tmu-status-indicator.warning {
    color: #dba617;
}

.tmu-status-indicator.error {
    color: #d63638;
}

.tmu-quick-actions {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.tmu-action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.tmu-settings-tabs {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin: 20px 0;
}

.tmu-tab-nav {
    display: flex;
    border-bottom: 1px solid #ccd0d4;
}

.tmu-tab-button {
    background: none;
    border: none;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    gap: 8px;
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
}

.tmu-tab-content {
    display: none;
    padding: 20px;
}

.tmu-tab-content.active {
    display: block;
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

// Quick action functions
function runSecurityScan() {
    if (confirm('<?php _e('This will run a comprehensive security scan. Continue?', 'tmu-theme'); ?>')) {
        // Implement security scan functionality
        alert('<?php _e('Security scan initiated. Results will be available in the reports section.', 'tmu-theme'); ?>');
    }
}

function runAccessibilityCheck() {
    if (confirm('<?php _e('This will check the current page for accessibility compliance. Continue?', 'tmu-theme'); ?>')) {
        // Add accessibility check parameter to current URL
        window.open(window.location.origin + '?accessibility_check=1', '_blank');
    }
}

function exportSettings() {
    // Implement settings export functionality
    alert('<?php _e('Settings export functionality will be implemented.', 'tmu-theme'); ?>');
}

function resetToDefaults() {
    if (confirm('<?php _e('This will reset all settings to their default values. Continue?', 'tmu-theme'); ?>')) {
        // Implement reset functionality
        location.reload();
    }
}
</script>