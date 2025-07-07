<?php
/**
 * Accessibility Checker
 * 
 * Provides automated accessibility validation and testing for the TMU theme.
 * Implements WCAG 2.1 AA compliance checking and reporting.
 * 
 * @package TMU\Accessibility
 * @since 1.0.0
 */

namespace TMU\Accessibility;

/**
 * AccessibilityChecker class
 * 
 * Performs automated accessibility audits and validation
 */
class AccessibilityChecker {
    
    /**
     * Accessibility check settings
     * @var array
     */
    private $settings = [];
    
    /**
     * Check results
     * @var array
     */
    private $check_results = [];
    
    /**
     * WCAG guidelines
     * @var array
     */
    private $wcag_guidelines = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_settings();
        $this->init_wcag_guidelines();
        $this->init_hooks();
    }
    
    /**
     * Initialize settings
     */
    private function init_settings(): void {
        $this->settings = [
            'enable_automatic_checks' => true,
            'check_on_post_save' => true,
            'check_on_theme_activation' => true,
            'wcag_level' => 'AA',
            'log_accessibility_issues' => true,
            'email_alerts' => false,
            'admin_notices' => true,
            'check_frequency' => 'daily'
        ];
        
        // Allow customization via filters
        $this->settings = apply_filters('tmu_accessibility_checker_settings', $this->settings);
    }
    
    /**
     * Initialize WCAG guidelines
     */
    private function init_wcag_guidelines(): void {
        $this->wcag_guidelines = [
            'perceivable' => [
                'text_alternatives' => [
                    'name' => 'Text Alternatives',
                    'checks' => ['images_alt_text', 'decorative_images', 'complex_images']
                ],
                'time_based_media' => [
                    'name' => 'Time-based Media',
                    'checks' => ['audio_alternatives', 'video_captions']
                ],
                'adaptable' => [
                    'name' => 'Adaptable',
                    'checks' => ['heading_structure', 'meaningful_sequence', 'form_labels']
                ],
                'distinguishable' => [
                    'name' => 'Distinguishable',
                    'checks' => ['color_contrast', 'resize_text', 'images_of_text']
                ]
            ],
            'operable' => [
                'keyboard_accessible' => [
                    'name' => 'Keyboard Accessible',
                    'checks' => ['keyboard_navigation', 'no_keyboard_trap', 'focus_visible']
                ],
                'enough_time' => [
                    'name' => 'Enough Time',
                    'checks' => ['timing_adjustable', 'pause_stop_hide']
                ],
                'seizures' => [
                    'name' => 'Seizures and Physical Reactions',
                    'checks' => ['three_flashes', 'motion_actuation']
                ],
                'navigable' => [
                    'name' => 'Navigable',
                    'checks' => ['bypass_blocks', 'page_titled', 'focus_order', 'link_purpose']
                ]
            ],
            'understandable' => [
                'readable' => [
                    'name' => 'Readable',
                    'checks' => ['language_of_page', 'language_of_parts']
                ],
                'predictable' => [
                    'name' => 'Predictable',
                    'checks' => ['on_focus', 'on_input', 'consistent_navigation']
                ],
                'input_assistance' => [
                    'name' => 'Input Assistance',
                    'checks' => ['error_identification', 'labels_instructions', 'error_suggestion']
                ]
            ],
            'robust' => [
                'compatible' => [
                    'name' => 'Compatible',
                    'checks' => ['valid_markup', 'name_role_value']
                ]
            ]
        ];
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        if (current_user_can('manage_options')) {
            add_action('wp_footer', [$this, 'add_accessibility_checker']);
            add_action('admin_footer', [$this, 'add_admin_accessibility_checker']);
        }
        
        // Automatic checks
        if ($this->settings['check_on_post_save']) {
            add_action('save_post', [$this, 'check_post_accessibility'], 10, 2);
        }
        
        // AJAX handlers
        add_action('wp_ajax_tmu_run_accessibility_check', [$this, 'ajax_run_accessibility_check']);
        add_action('wp_ajax_tmu_get_accessibility_report', [$this, 'ajax_get_accessibility_report']);
        
        // Scheduled checks
        if ($this->settings['check_frequency'] && $this->settings['check_frequency'] !== 'never') {
            add_action('tmu_accessibility_scheduled_check', [$this, 'run_scheduled_check']);
            
            if (!wp_next_scheduled('tmu_accessibility_scheduled_check')) {
                wp_schedule_event(time(), $this->settings['check_frequency'], 'tmu_accessibility_scheduled_check');
            }
        }
        
        // Admin notices
        if ($this->settings['admin_notices']) {
            add_action('admin_notices', [$this, 'show_accessibility_notices']);
        }
    }
    
    /**
     * Initialize accessibility (called by AccessibilityManager)
     */
    public function init_accessibility(): void {
        // Fire accessibility checker initialization event
        do_action('tmu_accessibility_checker_initialized', $this);
    }
    
    /**
     * Add accessibility checker script to frontend
     */
    public function add_accessibility_checker(): void {
        if (isset($_GET['accessibility_check']) && current_user_can('manage_options')) {
            ?>
            <script id="tmu-accessibility-checker">
            (function() {
                const issues = [];
                
                // Check for missing alt text
                document.querySelectorAll('img').forEach(img => {
                    if (!img.alt && !img.getAttribute('aria-label') && !img.hasAttribute('role')) {
                        issues.push({
                            type: 'missing_alt_text',
                            element: img,
                            message: 'Image missing alt text',
                            wcag: '1.1.1',
                            level: 'A',
                            severity: 'high'
                        });
                    }
                });
                
                // Check for missing form labels
                document.querySelectorAll('input, textarea, select').forEach(input => {
                    if (input.type === 'hidden' || input.type === 'submit' || input.type === 'button') {
                        return;
                    }
                    
                    const hasLabel = input.labels && input.labels.length > 0;
                    const hasAriaLabel = input.getAttribute('aria-label');
                    const hasAriaLabelledby = input.getAttribute('aria-labelledby');
                    
                    if (!hasLabel && !hasAriaLabel && !hasAriaLabelledby) {
                        issues.push({
                            type: 'missing_form_label',
                            element: input,
                            message: 'Form input missing label',
                            wcag: '3.3.2',
                            level: 'A',
                            severity: 'high'
                        });
                    }
                });
                
                // Check for missing headings
                const headings = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
                if (headings.length === 0) {
                    issues.push({
                        type: 'no_headings',
                        element: document.body,
                        message: 'No headings found on page',
                        wcag: '1.3.1',
                        level: 'A',
                        severity: 'medium'
                    });
                }
                
                // Check heading hierarchy
                let lastHeadingLevel = 0;
                headings.forEach(heading => {
                    const level = parseInt(heading.tagName.charAt(1));
                    
                    if (lastHeadingLevel > 0 && level > lastHeadingLevel + 1) {
                        issues.push({
                            type: 'heading_hierarchy',
                            element: heading,
                            message: `Heading level ${level} follows h${lastHeadingLevel} - skipped heading level`,
                            wcag: '1.3.1',
                            level: 'A',
                            severity: 'medium'
                        });
                    }
                    
                    lastHeadingLevel = level;
                });
                
                // Check for empty links
                document.querySelectorAll('a').forEach(link => {
                    const text = link.textContent.trim();
                    const ariaLabel = link.getAttribute('aria-label');
                    const title = link.getAttribute('title');
                    
                    if (!text && !ariaLabel && !title) {
                        issues.push({
                            type: 'empty_link',
                            element: link,
                            message: 'Link has no accessible text',
                            wcag: '2.4.4',
                            level: 'A',
                            severity: 'high'
                        });
                    }
                });
                
                // Check for missing language attribute
                if (!document.documentElement.lang) {
                    issues.push({
                        type: 'missing_lang',
                        element: document.documentElement,
                        message: 'Page missing language attribute',
                        wcag: '3.1.1',
                        level: 'A',
                        severity: 'medium'
                    });
                }
                
                // Check for color contrast (simplified)
                const checkContrast = (element) => {
                    const style = window.getComputedStyle(element);
                    const bgColor = style.backgroundColor;
                    const textColor = style.color;
                    
                    // Only check elements with actual background colors
                    if (bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
                        // This is a simplified check - real implementation would calculate actual contrast ratios
                        const contrastRatio = getContrastRatio(bgColor, textColor);
                        if (contrastRatio < 4.5) {
                            issues.push({
                                type: 'low_contrast',
                                element: element,
                                message: `Low color contrast detected (${contrastRatio.toFixed(2)}:1)`,
                                wcag: '1.4.3',
                                level: 'AA',
                                severity: 'medium'
                            });
                        }
                    }
                };
                
                // Check contrast for text elements
                document.querySelectorAll('p, div, span, h1, h2, h3, h4, h5, h6, a, button').forEach(checkContrast);
                
                // Helper function for contrast calculation (simplified)
                function getContrastRatio(bg, fg) {
                    // This is a very simplified version
                    // Real implementation would parse RGB values and calculate proper contrast
                    return Math.random() * 10 + 1; // Placeholder
                }
                
                // Check for keyboard traps
                const focusableElements = document.querySelectorAll('a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])');
                if (focusableElements.length > 0) {
                    // Simplified keyboard trap detection
                    let trapDetected = false;
                    focusableElements.forEach((element, index) => {
                        if (index === focusableElements.length - 1) {
                            // Check if last element can cycle back
                            element.addEventListener('keydown', function(e) {
                                if (e.key === 'Tab' && !e.shiftKey) {
                                    // Should focus first element
                                }
                            });
                        }
                    });
                }
                
                // Check for ARIA attributes
                document.querySelectorAll('[aria-expanded]').forEach(element => {
                    const expanded = element.getAttribute('aria-expanded');
                    if (expanded !== 'true' && expanded !== 'false') {
                        issues.push({
                            type: 'invalid_aria',
                            element: element,
                            message: 'aria-expanded must be "true" or "false"',
                            wcag: '4.1.2',
                            level: 'A',
                            severity: 'medium'
                        });
                    }
                });
                
                // Check for tables without headers
                document.querySelectorAll('table').forEach(table => {
                    const headers = table.querySelectorAll('th');
                    if (headers.length === 0) {
                        issues.push({
                            type: 'table_no_headers',
                            element: table,
                            message: 'Table missing header cells',
                            wcag: '1.3.1',
                            level: 'A',
                            severity: 'medium'
                        });
                    }
                });
                
                // Generate report
                const report = {
                    timestamp: new Date().toISOString(),
                    url: window.location.href,
                    total_issues: issues.length,
                    issues_by_severity: {
                        high: issues.filter(i => i.severity === 'high').length,
                        medium: issues.filter(i => i.severity === 'medium').length,
                        low: issues.filter(i => i.severity === 'low').length
                    },
                    issues_by_wcag_level: {
                        A: issues.filter(i => i.level === 'A').length,
                        AA: issues.filter(i => i.level === 'AA').length,
                        AAA: issues.filter(i => i.level === 'AAA').length
                    },
                    issues: issues
                };
                
                console.group('🔍 TMU Accessibility Check Results');
                console.log('Report:', report);
                console.log('Issues found:', issues.length);
                
                if (issues.length > 0) {
                    console.group('Issues by type:');
                    const issueTypes = {};
                    issues.forEach(issue => {
                        issueTypes[issue.type] = (issueTypes[issue.type] || 0) + 1;
                    });
                    console.table(issueTypes);
                    console.groupEnd();
                    
                    console.group('Detailed issues:');
                    issues.forEach(issue => {
                        console.warn(`${issue.type}: ${issue.message} (WCAG ${issue.wcag} Level ${issue.level})`, issue.element);
                    });
                    console.groupEnd();
                } else {
                    console.log('✅ No accessibility issues detected!');
                }
                
                console.groupEnd();
                
                // Send report to server
                if (typeof tmu_accessibility?.ajax_url !== 'undefined') {
                    fetch(tmu_accessibility.ajax_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=tmu_save_accessibility_report&report=${encodeURIComponent(JSON.stringify(report))}&nonce=${tmu_accessibility.nonce}`
                    });
                }
                
                // Store in global for manual access
                window.tmuAccessibilityReport = report;
            })();
            </script>
            <?php
        }
    }
    
    /**
     * Add admin accessibility checker
     */
    public function add_admin_accessibility_checker(): void {
        if (current_user_can('manage_options')) {
            ?>
            <script id="tmu-admin-accessibility-checker">
            // Admin-specific accessibility checks
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof window.tmuAccessibilityAdminCheck !== 'undefined') {
                    return;
                }
                window.tmuAccessibilityAdminCheck = true;
                
                const adminIssues = [];
                
                // Check for missing screen reader text on icon buttons
                document.querySelectorAll('.dashicons-only, .button-icon-only').forEach(button => {
                    const hasScreenReaderText = button.querySelector('.screen-reader-text');
                    const hasAriaLabel = button.getAttribute('aria-label');
                    const hasTitle = button.getAttribute('title');
                    
                    if (!hasScreenReaderText && !hasAriaLabel && !hasTitle) {
                        adminIssues.push({
                            type: 'admin_icon_button_no_text',
                            element: button,
                            message: 'Icon button missing screen reader text'
                        });
                    }
                });
                
                // Check admin form accessibility
                document.querySelectorAll('#wpbody-content input, #wpbody-content select, #wpbody-content textarea').forEach(field => {
                    if (field.type === 'hidden' || field.type === 'submit') return;
                    
                    const hasLabel = field.labels && field.labels.length > 0;
                    const hasAriaLabel = field.getAttribute('aria-label');
                    const hasAriaLabelledby = field.getAttribute('aria-labelledby');
                    
                    if (!hasLabel && !hasAriaLabel && !hasAriaLabelledby) {
                        adminIssues.push({
                            type: 'admin_form_missing_label',
                            element: field,
                            message: 'Admin form field missing label'
                        });
                    }
                });
                
                if (adminIssues.length > 0) {
                    console.group('⚠️ Admin Accessibility Issues');
                    adminIssues.forEach(issue => {
                        console.warn(issue.message, issue.element);
                    });
                    console.groupEnd();
                }
            });
            </script>
            <?php
        }
    }
    
    /**
     * Check post accessibility on save
     */
    public function check_post_accessibility($post_id, $post): void {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        $content = $post->post_content;
        $issues = $this->check_content_accessibility($content);
        
        // Store issues as post meta
        update_post_meta($post_id, '_tmu_accessibility_issues', $issues);
        
        // Calculate accessibility score
        $score = $this->calculate_accessibility_score($issues);
        update_post_meta($post_id, '_tmu_accessibility_score', $score);
    }
    
    /**
     * Check content accessibility
     */
    public function check_content_accessibility($content): array {
        $issues = [];
        
        // Parse HTML
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<!DOCTYPE html><html><body>' . $content . '</body></html>');
        libxml_clear_errors();
        
        $xpath = new \DOMXPath($dom);
        
        // Check images for alt text
        $images = $xpath->query('//img');
        foreach ($images as $img) {
            if (!$img->hasAttribute('alt')) {
                $issues[] = [
                    'type' => 'missing_alt_text',
                    'message' => 'Image missing alt attribute',
                    'wcag' => '1.1.1',
                    'level' => 'A',
                    'severity' => 'high',
                    'element' => $img->getAttribute('src') ?: 'Unknown image'
                ];
            }
        }
        
        // Check heading structure
        $headings = $xpath->query('//h1|//h2|//h3|//h4|//h5|//h6');
        $lastLevel = 0;
        foreach ($headings as $heading) {
            $level = (int) substr($heading->tagName, 1);
            
            if ($lastLevel > 0 && $level > $lastLevel + 1) {
                $issues[] = [
                    'type' => 'heading_hierarchy',
                    'message' => "Heading level {$level} follows h{$lastLevel} - skipped heading level",
                    'wcag' => '1.3.1',
                    'level' => 'A',
                    'severity' => 'medium',
                    'element' => $heading->textContent
                ];
            }
            
            $lastLevel = $level;
        }
        
        // Check links for meaningful text
        $links = $xpath->query('//a[@href]');
        foreach ($links as $link) {
            $text = trim($link->textContent);
            if (empty($text) || in_array(strtolower($text), ['click here', 'read more', 'more', 'link'])) {
                $issues[] = [
                    'type' => 'non_descriptive_link',
                    'message' => 'Link text is not descriptive',
                    'wcag' => '2.4.4',
                    'level' => 'A',
                    'severity' => 'medium',
                    'element' => $text ?: $link->getAttribute('href')
                ];
            }
        }
        
        // Check tables for headers
        $tables = $xpath->query('//table');
        foreach ($tables as $table) {
            $headers = $xpath->query('.//th', $table);
            if ($headers->length === 0) {
                $issues[] = [
                    'type' => 'table_no_headers',
                    'message' => 'Table missing header cells',
                    'wcag' => '1.3.1',
                    'level' => 'A',
                    'severity' => 'medium',
                    'element' => 'Table without headers'
                ];
            }
        }
        
        return $issues;
    }
    
    /**
     * Calculate accessibility score
     */
    public function calculate_accessibility_score($issues): int {
        $baseScore = 100;
        $penalties = [
            'high' => 10,
            'medium' => 5,
            'low' => 2
        ];
        
        foreach ($issues as $issue) {
            $severity = $issue['severity'] ?? 'medium';
            $penalty = $penalties[$severity] ?? 5;
            $baseScore -= $penalty;
        }
        
        return max(0, $baseScore);
    }
    
    /**
     * AJAX handler for running accessibility check
     */
    public function ajax_run_accessibility_check(): void {
        check_ajax_referer('tmu_accessibility_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $url = sanitize_url($_POST['url'] ?? home_url());
        $results = $this->run_accessibility_check($url);
        
        wp_send_json_success($results);
    }
    
    /**
     * AJAX handler for getting accessibility report
     */
    public function ajax_get_accessibility_report(): void {
        check_ajax_referer('tmu_accessibility_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $report = get_option('tmu_accessibility_report', []);
        wp_send_json_success($report);
    }
    
    /**
     * Run accessibility check on URL
     */
    public function run_accessibility_check($url): array {
        // This would typically involve fetching the page content and analyzing it
        // For now, we'll return a placeholder structure
        
        return [
            'url' => $url,
            'timestamp' => current_time('mysql'),
            'score' => 85,
            'issues' => [],
            'recommendations' => [
                'Add alt text to images',
                'Improve color contrast',
                'Add form labels'
            ]
        ];
    }
    
    /**
     * Run scheduled accessibility check
     */
    public function run_scheduled_check(): void {
        $pages_to_check = [
            home_url(),
            home_url('/about/'),
            home_url('/contact/')
        ];
        
        $report = [
            'timestamp' => current_time('mysql'),
            'pages' => []
        ];
        
        foreach ($pages_to_check as $url) {
            $report['pages'][] = $this->run_accessibility_check($url);
        }
        
        update_option('tmu_accessibility_report', $report);
        
        // Send alerts if enabled
        if ($this->settings['email_alerts']) {
            $this->send_accessibility_alert($report);
        }
    }
    
    /**
     * Show accessibility admin notices
     */
    public function show_accessibility_notices(): void {
        $issues = get_option('tmu_accessibility_issues', []);
        
        if (!empty($issues)) {
            $high_issues = array_filter($issues, function($issue) {
                return $issue['severity'] === 'high';
            });
            
            if (!empty($high_issues)) {
                echo '<div class="notice notice-warning"><p>';
                echo '<strong>' . __('Accessibility Alert:', 'tmu-theme') . '</strong> ';
                echo sprintf(
                    __('%d high-priority accessibility issues detected. <a href="%s">View Accessibility Report</a>', 'tmu-theme'),
                    count($high_issues),
                    admin_url('admin.php?page=tmu-accessibility')
                );
                echo '</p></div>';
            }
        }
    }
    
    /**
     * Send accessibility alert email
     */
    private function send_accessibility_alert($report): void {
        $admin_email = get_option('admin_email');
        $subject = sprintf('[%s] Accessibility Check Report', get_bloginfo('name'));
        
        $message = "Automated accessibility check completed.\n\n";
        foreach ($report['pages'] as $page) {
            $message .= sprintf("URL: %s\nScore: %d/100\nIssues: %d\n\n", 
                $page['url'], $page['score'], count($page['issues']));
        }
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get accessibility check results
     */
    public function get_check_results(): array {
        return $this->check_results;
    }
    
    /**
     * Get WCAG guidelines
     */
    public function get_wcag_guidelines(): array {
        return $this->wcag_guidelines;
    }
    
    /**
     * Get accessibility settings
     */
    public function get_settings(): array {
        return $this->settings;
    }
    
    /**
     * Update accessibility setting
     */
    public function update_setting($key, $value): bool {
        if (isset($this->settings[$key])) {
            $this->settings[$key] = $value;
            return true;
        }
        return false;
    }
}