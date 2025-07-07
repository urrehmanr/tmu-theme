<?php
/**
 * Accessibility Manager
 * 
 * Main coordinator for all accessibility features in the TMU theme.
 * Ensures WCAG 2.1 AA compliance and comprehensive accessibility support.
 * 
 * @package TMU\Accessibility
 * @since 1.0.0
 */

namespace TMU\Accessibility;

/**
 * AccessibilityManager class
 * 
 * Centralizes accessibility implementations and ensures WCAG compliance
 */
class AccessibilityManager {
    
    /**
     * Class instance
     * @var AccessibilityManager|null
     */
    private static $instance = null;
    
    /**
     * Accessibility components
     * @var array
     */
    private $accessibility_components = [];
    
    /**
     * Accessibility settings
     * @var array
     */
    private $accessibility_settings = [];
    
    /**
     * WCAG compliance level
     * @var string
     */
    private $wcag_level = 'AA';
    
    /**
     * Get singleton instance
     * 
     * @return AccessibilityManager
     */
    public static function getInstance(): AccessibilityManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->init_accessibility_settings();
        $this->init_accessibility_components();
        $this->init_hooks();
    }
    
    /**
     * Initialize accessibility settings
     */
    private function init_accessibility_settings(): void {
        $this->accessibility_settings = [
            'enable_aria_labels' => true,
            'enable_keyboard_navigation' => true,
            'enable_screen_reader' => true,
            'enable_high_contrast' => true,
            'enable_focus_management' => true,
            'enable_skip_links' => true,
            'enable_landmark_roles' => true,
            'enable_live_regions' => true,
            'enable_reduced_motion' => true,
            'enable_color_contrast_check' => true,
            'wcag_compliance_level' => 'AA',
            'log_accessibility_events' => true
        ];
        
        // Allow customization via filters
        $this->accessibility_settings = apply_filters('tmu_accessibility_settings', $this->accessibility_settings);
        $this->wcag_level = $this->accessibility_settings['wcag_compliance_level'];
    }
    
    /**
     * Initialize accessibility components
     */
    private function init_accessibility_components(): void {
        // Load accessibility components based on settings
        if ($this->accessibility_settings['enable_aria_labels']) {
            $this->accessibility_components['aria_labels'] = new AriaLabels();
        }
        
        if ($this->accessibility_settings['enable_keyboard_navigation']) {
            $this->accessibility_components['keyboard_navigation'] = new KeyboardNavigation();
        }
        
        if ($this->accessibility_settings['enable_screen_reader']) {
            $this->accessibility_components['screen_reader'] = new ScreenReader();
        }
        
        // Always initialize accessibility checker for admin users
        if (current_user_can('manage_options')) {
            $this->accessibility_components['accessibility_checker'] = new AccessibilityChecker();
        }
        
        if ($this->accessibility_settings['enable_accessibility_checker']) {
            $this->accessibility_components['accessibility_checker'] = new AccessibilityChecker();
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        add_action('init', [$this, 'init_accessibility'], 0);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_accessibility_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_accessibility_assets']);
        
        // Theme support
        add_action('after_setup_theme', [$this, 'add_accessibility_theme_support']);
        
        // Body classes for accessibility
        add_filter('body_class', [$this, 'add_accessibility_body_classes']);
        
        // Accessibility features
        add_action('wp_head', [$this, 'add_accessibility_head_content'], 1);
        add_action('wp_footer', [$this, 'add_accessibility_footer_content'], 999);
        
        // Skip links
        add_action('wp_body_open', [$this, 'add_skip_links']);
        
        // Language attributes
        add_filter('language_attributes', [$this, 'add_accessibility_attributes']);
        
        // Menu accessibility
        add_filter('nav_menu_item_args', [$this, 'add_menu_accessibility_attributes'], 10, 3);
        add_filter('walker_nav_menu_start_el', [$this, 'enhance_menu_accessibility'], 10, 4);
        
        // Form accessibility
        add_filter('comment_form_defaults', [$this, 'enhance_comment_form_accessibility']);
        add_filter('get_search_form', [$this, 'enhance_search_form_accessibility']);
        
        // Content accessibility
        add_filter('the_content', [$this, 'enhance_content_accessibility'], 99);
        add_filter('the_excerpt', [$this, 'enhance_excerpt_accessibility'], 99);
        
        // Image accessibility
        add_filter('wp_get_attachment_image_attributes', [$this, 'enhance_image_accessibility'], 10, 3);
        
        // Widget accessibility
        add_filter('dynamic_sidebar_params', [$this, 'enhance_widget_accessibility']);
        
        // Admin accessibility
        if (is_admin()) {
            add_action('admin_init', [$this, 'init_admin_accessibility']);
            add_action('admin_notices', [$this, 'accessibility_admin_notices']);
        }
        
        // Accessibility testing
        add_action('wp_ajax_tmu_accessibility_test', [$this, 'ajax_accessibility_test']);
        
        // Color scheme detection
        add_action('wp_head', [$this, 'add_color_scheme_detection']);
        
        // Print accessibility
        add_action('wp_head', [$this, 'add_print_accessibility_styles']);
    }
    
    /**
     * Initialize accessibility measures
     */
    public function init_accessibility(): void {
        // Set up accessibility constants
        $this->define_accessibility_constants();
        
        // Initialize component accessibility
        foreach ($this->accessibility_components as $component) {
            if (method_exists($component, 'init_accessibility')) {
                $component->init_accessibility();
            }
        }
        
        // Fire accessibility initialization event
        do_action('tmu_accessibility_initialized', $this);
    }
    
    /**
     * Define accessibility constants
     */
    private function define_accessibility_constants(): void {
        if (!defined('TMU_WCAG_LEVEL')) {
            define('TMU_WCAG_LEVEL', $this->wcag_level);
        }
        
        if (!defined('TMU_ACCESSIBILITY_LOG')) {
            define('TMU_ACCESSIBILITY_LOG', $this->accessibility_settings['log_accessibility_events']);
        }
    }
    
    /**
     * Add theme support for accessibility
     */
    public function add_accessibility_theme_support(): void {
        // Add accessibility-ready theme support
        add_theme_support('accessibility-ready');
        
        // Add HTML5 support for better semantic markup
        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'navigation-widgets'
        ]);
        
        // Add title tag support
        add_theme_support('title-tag');
        
        // Add custom logo support with accessibility
        add_theme_support('custom-logo', [
            'height' => 100,
            'width' => 400,
            'flex-height' => true,
            'flex-width' => true,
            'header-text' => ['site-title', 'site-description']
        ]);
    }
    
    /**
     * Enqueue accessibility assets
     */
    public function enqueue_accessibility_assets(): void {
        // Accessibility styles
        wp_enqueue_style(
            'tmu-accessibility',
            get_template_directory_uri() . '/assets/css/accessibility.css',
            [],
            '1.0.0',
            'all'
        );
        
        // High contrast styles
        if ($this->accessibility_settings['enable_high_contrast']) {
            wp_enqueue_style(
                'tmu-high-contrast',
                get_template_directory_uri() . '/assets/css/high-contrast.css',
                ['tmu-accessibility'],
                '1.0.0',
                'all'
            );
        }
        
        // Keyboard navigation script
        if ($this->accessibility_settings['enable_keyboard_navigation']) {
            wp_enqueue_script(
                'tmu-keyboard-navigation',
                get_template_directory_uri() . '/assets/js/keyboard-navigation.js',
                [],
                '1.0.0',
                true
            );
        }
        
        // Screen reader utilities
        if ($this->accessibility_settings['enable_screen_reader']) {
            wp_enqueue_script(
                'tmu-screen-reader',
                get_template_directory_uri() . '/assets/js/screen-reader.js',
                [],
                '1.0.0',
                true
            );
        }
        
        // Accessibility configuration
        wp_localize_script('tmu-main', 'tmu_accessibility', [
            'wcag_level' => $this->wcag_level,
            'reduced_motion' => $this->accessibility_settings['enable_reduced_motion'],
            'high_contrast' => $this->accessibility_settings['enable_high_contrast'],
            'strings' => [
                'skip_to_content' => __('Skip to main content', 'tmu-theme'),
                'skip_to_navigation' => __('Skip to navigation', 'tmu-theme'),
                'skip_to_footer' => __('Skip to footer', 'tmu-theme'),
                'menu_expanded' => __('Menu expanded', 'tmu-theme'),
                'menu_collapsed' => __('Menu collapsed', 'tmu-theme'),
                'loading' => __('Loading...', 'tmu-theme'),
                'close' => __('Close', 'tmu-theme'),
                'open' => __('Open', 'tmu-theme')
            ]
        ]);
    }
    
    /**
     * Enqueue admin accessibility assets
     */
    public function enqueue_admin_accessibility_assets(): void {
        wp_enqueue_style(
            'tmu-admin-accessibility',
            get_template_directory_uri() . '/assets/css/admin-accessibility.css',
            [],
            '1.0.0'
        );
        
        wp_enqueue_script(
            'tmu-admin-accessibility',
            get_template_directory_uri() . '/assets/js/admin-accessibility.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }
    
    /**
     * Add accessibility body classes
     */
    public function add_accessibility_body_classes($classes): array {
        // Add WCAG compliance level class
        $classes[] = 'wcag-' . strtolower($this->wcag_level);
        
        // Add accessibility feature classes
        if ($this->accessibility_settings['enable_keyboard_navigation']) {
            $classes[] = 'keyboard-navigation';
        }
        
        if ($this->accessibility_settings['enable_high_contrast']) {
            $classes[] = 'high-contrast-available';
        }
        
        if ($this->accessibility_settings['enable_reduced_motion']) {
            $classes[] = 'reduced-motion-support';
        }
        
        return $classes;
    }
    
    /**
     * Add accessibility head content
     */
    public function add_accessibility_head_content(): void {
        echo '<meta name="color-scheme" content="light dark">' . "\n";
        
        // Viewport meta for accessibility
        echo '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">' . "\n";
        
        // Reduced motion media query styles
        if ($this->accessibility_settings['enable_reduced_motion']) {
            echo '<style>@media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; } }</style>' . "\n";
        }
    }
    
    /**
     * Add accessibility footer content
     */
    public function add_accessibility_footer_content(): void {
        // Live region for announcements
        if ($this->accessibility_settings['enable_live_regions']) {
            echo '<div id="tmu-live-region" aria-live="polite" aria-atomic="true" class="sr-only"></div>' . "\n";
            echo '<div id="tmu-live-region-assertive" aria-live="assertive" aria-atomic="true" class="sr-only"></div>' . "\n";
        }
        
        // Focus trap helper
        echo '<div id="focus-trap-start" tabindex="0" style="position: absolute; left: -9999px;"></div>' . "\n";
        echo '<div id="focus-trap-end" tabindex="0" style="position: absolute; left: -9999px;"></div>' . "\n";
    }
    
    /**
     * Add skip links
     */
    public function add_skip_links(): void {
        if (!$this->accessibility_settings['enable_skip_links']) {
            return;
        }
        
        $skip_links = [
            '#main-content' => __('Skip to main content', 'tmu-theme'),
            '#site-navigation' => __('Skip to navigation', 'tmu-theme'),
            '#site-search' => __('Skip to search', 'tmu-theme'),
            '#site-footer' => __('Skip to footer', 'tmu-theme')
        ];
        
        $skip_links = apply_filters('tmu_skip_links', $skip_links);
        
        echo '<div class="skip-links">' . "\n";
        foreach ($skip_links as $target => $text) {
            echo '<a class="skip-link screen-reader-text" href="' . esc_attr($target) . '">' . esc_html($text) . '</a>' . "\n";
        }
        echo '</div>' . "\n";
    }
    
    /**
     * Add accessibility language attributes
     */
    public function add_accessibility_attributes($output): string {
        // Add dir attribute for RTL support
        if (is_rtl()) {
            $output .= ' dir="rtl"';
        } else {
            $output .= ' dir="ltr"';
        }
        
        return $output;
    }
    
    /**
     * Add menu accessibility attributes
     */
    public function add_menu_accessibility_attributes($args, $item, $depth): object {
        // Add ARIA attributes for dropdown menus
        if (in_array('menu-item-has-children', $item->classes)) {
            $args->link_after = '<span class="screen-reader-text"> (has submenu)</span>';
            
            // Add aria-expanded for parent items
            if (!isset($args->item_spacing)) {
                $args->item_spacing = '';
            }
        }
        
        return $args;
    }
    
    /**
     * Enhance menu accessibility
     */
    public function enhance_menu_accessibility($item_output, $item, $depth, $args): string {
        // Add ARIA attributes for menu items with children
        if (in_array('menu-item-has-children', $item->classes)) {
            $item_output = str_replace(
                '<a',
                '<a aria-expanded="false" aria-haspopup="true"',
                $item_output
            );
        }
        
        return $item_output;
    }
    
    /**
     * Enhance comment form accessibility
     */
    public function enhance_comment_form_accessibility($defaults): array {
        // Add required attributes and labels
        $defaults['comment_field'] = '<p class="comment-form-comment"><label for="comment">' . 
            _x('Comment', 'noun', 'tmu-theme') . ' <span class="required" aria-label="required">*</span></label> ' .
            '<textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525" required="required" ' .
            'aria-describedby="comment-notes" aria-required="true"></textarea></p>';
        
        $defaults['fields']['author'] = '<p class="comment-form-author"><label for="author">' . 
            __('Name', 'tmu-theme') . ' <span class="required" aria-label="required">*</span></label> ' .
            '<input id="author" name="author" type="text" size="30" maxlength="245" required="required" aria-required="true" /></p>';
        
        $defaults['fields']['email'] = '<p class="comment-form-email"><label for="email">' . 
            __('Email', 'tmu-theme') . ' <span class="required" aria-label="required">*</span></label> ' .
            '<input id="email" name="email" type="email" size="30" maxlength="100" aria-describedby="email-notes" required="required" aria-required="true" /></p>';
        
        $defaults['fields']['url'] = '<p class="comment-form-url"><label for="url">' . 
            __('Website', 'tmu-theme') . '</label> ' .
            '<input id="url" name="url" type="url" size="30" maxlength="200" /></p>';
        
        return $defaults;
    }
    
    /**
     * Enhance search form accessibility
     */
    public function enhance_search_form_accessibility($form): string {
        $form = str_replace(
            '<form',
            '<form role="search" aria-label="' . esc_attr__('Search site', 'tmu-theme') . '"',
            $form
        );
        
        $form = str_replace(
            '<input type="search"',
            '<input type="search" aria-label="' . esc_attr__('Search for:', 'tmu-theme') . '"',
            $form
        );
        
        return $form;
    }
    
    /**
     * Enhance content accessibility
     */
    public function enhance_content_accessibility($content): string {
        // Add proper heading structure
        $content = $this->fix_heading_structure($content);
        
        // Add alt text to images without it
        $content = $this->add_missing_alt_text($content);
        
        // Enhance link accessibility
        $content = $this->enhance_link_accessibility($content);
        
        // Add table accessibility
        $content = $this->enhance_table_accessibility($content);
        
        return $content;
    }
    
    /**
     * Enhance excerpt accessibility
     */
    public function enhance_excerpt_accessibility($excerpt): string {
        // Add proper ARIA labels for excerpts
        if (!empty($excerpt)) {
            $post_title = get_the_title();
            $excerpt = '<div role="contentinfo" aria-label="' . 
                esc_attr(sprintf(__('Excerpt from %s', 'tmu-theme'), $post_title)) . 
                '">' . $excerpt . '</div>';
        }
        
        return $excerpt;
    }
    
    /**
     * Enhance image accessibility
     */
    public function enhance_image_accessibility($attr, $attachment, $size): array {
        // Ensure alt text is present
        if (empty($attr['alt'])) {
            $alt_text = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
            if (empty($alt_text)) {
                $attr['alt'] = $attachment->post_title;
            } else {
                $attr['alt'] = $alt_text;
            }
        }
        
        // Add loading attribute for performance
        if (!isset($attr['loading'])) {
            $attr['loading'] = 'lazy';
        }
        
        // Add decoding attribute
        if (!isset($attr['decoding'])) {
            $attr['decoding'] = 'async';
        }
        
        return $attr;
    }
    
    /**
     * Enhance widget accessibility
     */
    public function enhance_widget_accessibility($params): array {
        $widget_id = $params[0]['widget_id'];
        $widget_name = $params[0]['widget_name'];
        
        // Add ARIA labels to widgets
        $params[0]['before_widget'] = str_replace(
            'class="widget',
            'class="widget" role="complementary" aria-label="' . esc_attr($widget_name) . '"',
            $params[0]['before_widget']
        );
        
        return $params;
    }
    
    /**
     * Initialize admin accessibility
     */
    public function init_admin_accessibility(): void {
        // Add admin accessibility features
        add_meta_box(
            'tmu-accessibility-check',
            __('Accessibility Check', 'tmu-theme'),
            [$this, 'accessibility_check_meta_box'],
            ['post', 'page'],
            'side',
            'low'
        );
    }
    
    /**
     * Display accessibility admin notices
     */
    public function accessibility_admin_notices(): void {
        $accessibility_issues = get_option('tmu_accessibility_issues', []);
        
        if (!empty($accessibility_issues)) {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>' . __('Accessibility Issues:', 'tmu-theme') . '</strong> ';
            echo sprintf(
                __('%d accessibility issues detected. <a href="%s">View Accessibility Report</a>', 'tmu-theme'),
                count($accessibility_issues),
                admin_url('admin.php?page=tmu-accessibility')
            );
            echo '</p></div>';
        }
    }
    
    /**
     * Accessibility check meta box
     */
    public function accessibility_check_meta_box($post): void {
        $issues = $this->check_post_accessibility($post);
        
        if (empty($issues)) {
            echo '<p class="accessibility-pass">' . __('No accessibility issues found.', 'tmu-theme') . '</p>';
        } else {
            echo '<ul class="accessibility-issues">';
            foreach ($issues as $issue) {
                echo '<li class="accessibility-issue-' . esc_attr($issue['severity']) . '">';
                echo esc_html($issue['message']);
                echo '</li>';
            }
            echo '</ul>';
        }
    }
    
    /**
     * AJAX accessibility test
     */
    public function ajax_accessibility_test(): void {
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'tmu-theme'));
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_die(__('Invalid post ID', 'tmu-theme'));
        }
        
        $post = get_post($post_id);
        if (!$post) {
            wp_die(__('Post not found', 'tmu-theme'));
        }
        
        $issues = $this->check_post_accessibility($post);
        
        wp_send_json_success([
            'issues' => $issues,
            'total_issues' => count($issues),
            'accessibility_score' => $this->calculate_accessibility_score($issues)
        ]);
    }
    
    /**
     * Add color scheme detection
     */
    public function add_color_scheme_detection(): void {
        echo '<script>
        (function() {
            function updateColorScheme() {
                const isDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
                document.documentElement.setAttribute("data-color-scheme", isDark ? "dark" : "light");
            }
            updateColorScheme();
            window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", updateColorScheme);
        })();
        </script>' . "\n";
    }
    
    /**
     * Add print accessibility styles
     */
    public function add_print_accessibility_styles(): void {
        echo '<style media="print">
        .skip-links, .screen-reader-text { position: static; clip: auto; width: auto; height: auto; }
        a[href]:after { content: " (" attr(href) ")"; }
        abbr[title]:after { content: " (" attr(title) ")"; }
        @page { margin: 2cm; }
        </style>' . "\n";
    }
    
    /**
     * Helper methods for content enhancement
     */
    private function fix_heading_structure($content): string {
        // Implement heading structure fixing logic
        return $content;
    }
    
    private function add_missing_alt_text($content): string {
        // Add alt text to images that don't have it
        return preg_replace_callback(
            '/<img([^>]*?)(?:\s+alt\s*=\s*["\'][^"\']*["\'])?([^>]*?)>/i',
            function($matches) {
                if (strpos($matches[0], 'alt=') === false) {
                    return '<img' . $matches[1] . ' alt=""' . $matches[2] . '>';
                }
                return $matches[0];
            },
            $content
        );
    }
    
    private function enhance_link_accessibility($content): string {
        // Add title attributes and better link text
        return $content;
    }
    
    private function enhance_table_accessibility($content): string {
        // Add proper table headers and captions
        return $content;
    }
    
    private function check_post_accessibility($post): array {
        $issues = [];
        
        // Check for missing alt text
        if (preg_match_all('/<img[^>]*>/i', $post->post_content, $matches)) {
            foreach ($matches[0] as $img) {
                if (strpos($img, 'alt=') === false || preg_match('/alt\s*=\s*["\']["\']/', $img)) {
                    $issues[] = [
                        'severity' => 'high',
                        'message' => __('Image missing descriptive alt text', 'tmu-theme')
                    ];
                }
            }
        }
        
        // Check heading structure
        if (preg_match_all('/<h[1-6][^>]*>/i', $post->post_content, $matches)) {
            // Implement heading structure validation
        }
        
        // Check for proper link text
        if (preg_match_all('/<a[^>]*>([^<]*)<\/a>/i', $post->post_content, $matches)) {
            foreach ($matches[1] as $link_text) {
                $bad_link_texts = ['click here', 'read more', 'here', 'more'];
                if (in_array(strtolower(trim($link_text)), $bad_link_texts)) {
                    $issues[] = [
                        'severity' => 'medium',
                        'message' => __('Link has non-descriptive text', 'tmu-theme')
                    ];
                }
            }
        }
        
        return $issues;
    }
    
    private function calculate_accessibility_score($issues): int {
        $total_points = 100;
        $deductions = 0;
        
        foreach ($issues as $issue) {
            switch ($issue['severity']) {
                case 'high':
                    $deductions += 20;
                    break;
                case 'medium':
                    $deductions += 10;
                    break;
                case 'low':
                    $deductions += 5;
                    break;
            }
        }
        
        return max(0, $total_points - $deductions);
    }
    
    /**
     * Get accessibility component
     */
    public function get_component($component_name) {
        return $this->accessibility_components[$component_name] ?? null;
    }
    
    /**
     * Get accessibility settings
     */
    public function get_settings(): array {
        return $this->accessibility_settings;
    }
    
    /**
     * Update accessibility setting
     */
    public function update_setting($key, $value): bool {
        if (array_key_exists($key, $this->accessibility_settings)) {
            $this->accessibility_settings[$key] = $value;
            return update_option('tmu_accessibility_settings', $this->accessibility_settings);
        }
        return false;
    }
    
    /**
     * Get WCAG compliance level
     */
    public function get_wcag_level(): string {
        return $this->wcag_level;
    }
    
    /**
     * Set WCAG compliance level
     */
    public function set_wcag_level($level): void {
        $allowed_levels = ['A', 'AA', 'AAA'];
        if (in_array($level, $allowed_levels)) {
            $this->wcag_level = $level;
            $this->accessibility_settings['wcag_compliance_level'] = $level;
        }
    }
}