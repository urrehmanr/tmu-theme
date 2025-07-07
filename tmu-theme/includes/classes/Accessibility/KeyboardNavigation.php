<?php
/**
 * Keyboard Navigation
 * 
 * Handles keyboard navigation accessibility features for the TMU theme.
 * Implements WCAG 2.1 AA keyboard navigation requirements.
 * 
 * @package TMU\Accessibility
 * @since 1.0.0
 */

namespace TMU\Accessibility;

/**
 * KeyboardNavigation class
 * 
 * Manages keyboard navigation functionality and accessibility compliance
 */
class KeyboardNavigation {
    
    /**
     * Settings for keyboard navigation
     * @var array
     */
    private $settings = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_settings();
        $this->init_hooks();
    }
    
    /**
     * Initialize settings
     */
    private function init_settings(): void {
        $this->settings = [
            'enable_tab_navigation' => true,
            'enable_arrow_navigation' => true,
            'enable_escape_handling' => true,
            'enable_focus_management' => true,
            'enable_skip_links' => true,
            'enable_focus_indicators' => true,
            'enable_keyboard_shortcuts' => true,
            'log_keyboard_events' => false
        ];
        
        // Allow customization via filters
        $this->settings = apply_filters('tmu_keyboard_navigation_settings', $this->settings);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_keyboard_scripts']);
        add_filter('tmu_interactive_elements', [$this, 'add_keyboard_attributes']);
        add_action('wp_head', [$this, 'add_keyboard_styles'], 10);
        add_action('wp_footer', [$this, 'add_keyboard_handlers'], 999);
        
        // Admin keyboard navigation
        if (is_admin()) {
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_keyboard_scripts']);
            add_action('admin_head', [$this, 'add_admin_keyboard_styles']);
        }
    }
    
    /**
     * Initialize accessibility (called by AccessibilityManager)
     */
    public function init_accessibility(): void {
        // Set up keyboard navigation constants
        if (!defined('TMU_KEYBOARD_NAVIGATION')) {
            define('TMU_KEYBOARD_NAVIGATION', true);
        }
        
        // Fire keyboard navigation initialization event
        do_action('tmu_keyboard_navigation_initialized', $this);
    }
    
    /**
     * Enqueue keyboard navigation scripts
     */
    public function enqueue_keyboard_scripts(): void {
        wp_enqueue_script(
            'tmu-keyboard-navigation',
            get_template_directory_uri() . '/assets/js/keyboard-navigation.js',
            [],
            '1.0.0',
            true
        );
        
        // Localize script with settings
        wp_localize_script('tmu-keyboard-navigation', 'tmu_keyboard_nav', [
            'settings' => $this->settings,
            'focusable_elements' => $this->get_focusable_elements_selector(),
            'strings' => [
                'skip_to_content' => __('Skip to main content', 'tmu-theme'),
                'skip_to_navigation' => __('Skip to navigation', 'tmu-theme'),
                'menu_expanded' => __('Menu expanded', 'tmu-theme'),
                'menu_collapsed' => __('Menu collapsed', 'tmu-theme')
            ]
        ]);
    }
    
    /**
     * Enqueue admin keyboard navigation scripts
     */
    public function enqueue_admin_keyboard_scripts(): void {
        wp_enqueue_script(
            'tmu-admin-keyboard-navigation',
            get_template_directory_uri() . '/assets/js/admin-keyboard-navigation.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('tmu-admin-keyboard-navigation', 'tmu_admin_keyboard', [
            'settings' => $this->settings,
            'strings' => [
                'save_shortcut' => __('Ctrl+S to save', 'tmu-theme'),
                'preview_shortcut' => __('Ctrl+P to preview', 'tmu-theme')
            ]
        ]);
    }
    
    /**
     * Add keyboard attributes to interactive elements
     */
    public function add_keyboard_attributes($elements): array {
        foreach ($elements as &$element) {
            // Add tabindex if not set
            if (!isset($element['tabindex'])) {
                $element['tabindex'] = 0;
            }
            
            // Add role if not set
            if (!isset($element['role'])) {
                switch ($element['type'] ?? '') {
                    case 'card':
                        $element['role'] = 'article';
                        break;
                    case 'filter':
                        $element['role'] = 'button';
                        break;
                    case 'link':
                        $element['role'] = 'link';
                        break;
                    default:
                        $element['role'] = 'button';
                }
            }
            
            // Add aria-label if not set
            if (!isset($element['aria-label'])) {
                $element['aria-label'] = $element['title'] ?? $element['text'] ?? 'Interactive element';
            }
            
            // Add keyboard event handlers
            $element['data-keyboard-nav'] = 'true';
        }
        
        return $elements;
    }
    
    /**
     * Add keyboard navigation styles
     */
    public function add_keyboard_styles(): void {
        if (!$this->settings['enable_focus_indicators']) {
            return;
        }
        
        echo '<style id="tmu-keyboard-styles">
        /* Focus indicators */
        .tmu-keyboard-focus,
        [data-keyboard-nav="true"]:focus {
            outline: 2px solid #005fcc !important;
            outline-offset: 2px !important;
            box-shadow: 0 0 0 2px rgba(0, 95, 204, 0.2) !important;
        }
        
        /* Skip links */
        .tmu-skip-link {
            position: absolute !important;
            top: -40px !important;
            left: 6px !important;
            background: #000 !important;
            color: #fff !important;
            padding: 8px 12px !important;
            text-decoration: none !important;
            z-index: 100000 !important;
            border-radius: 4px !important;
            font-weight: bold !important;
            font-size: 14px !important;
            line-height: 1 !important;
        }
        
        .tmu-skip-link:focus {
            top: 6px !important;
            outline: 2px solid #fff !important;
        }
        
        /* Keyboard navigation indicators */
        .tmu-keyboard-active {
            border: 2px solid #005fcc !important;
        }
        
        /* Focus within containers */
        .tmu-content-grid:focus-within,
        .tmu-search-form:focus-within {
            box-shadow: 0 0 0 2px rgba(0, 95, 204, 0.1) !important;
        }
        
        /* High contrast mode keyboard indicators */
        @media (prefers-contrast: high) {
            .tmu-keyboard-focus,
            [data-keyboard-nav="true"]:focus {
                outline: 3px solid #000 !important;
                background: #fff !important;
                color: #000 !important;
            }
        }
        </style>' . "\n";
    }
    
    /**
     * Add admin keyboard styles
     */
    public function add_admin_keyboard_styles(): void {
        echo '<style id="tmu-admin-keyboard-styles">
        /* Admin keyboard shortcuts indicator */
        .tmu-admin-shortcut-hint {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: #fff;
            padding: 10px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .tmu-admin-shortcut-hint.visible {
            opacity: 1;
        }
        
        /* Admin focus indicators */
        .wp-admin [data-keyboard-nav="true"]:focus {
            outline: 2px solid #0073aa !important;
            box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.2) !important;
        }
        </style>' . "\n";
    }
    
    /**
     * Add keyboard event handlers
     */
    public function add_keyboard_handlers(): void {
        echo '<script id="tmu-keyboard-handlers">
        document.addEventListener("DOMContentLoaded", function() {
            // Initialize keyboard navigation if script is loaded
            if (typeof TMUKeyboardNavigation !== "undefined") {
                new TMUKeyboardNavigation();
            }
            
            // Fallback basic keyboard handling
            document.addEventListener("keydown", function(e) {
                // Handle escape key for modals/dropdowns
                if (e.key === "Escape") {
                    const activeModal = document.querySelector(".tmu-modal.active");
                    if (activeModal) {
                        activeModal.classList.remove("active");
                        const trigger = document.querySelector("[data-modal=\"" + activeModal.id + "\"]");
                        if (trigger) trigger.focus();
                    }
                    
                    const openDropdown = document.querySelector(".tmu-dropdown.open");
                    if (openDropdown) {
                        openDropdown.classList.remove("open");
                        const trigger = openDropdown.querySelector(".tmu-dropdown-trigger");
                        if (trigger) trigger.focus();
                    }
                }
                
                // Handle Enter/Space on card elements
                if ((e.key === "Enter" || e.key === " ") && e.target.classList.contains("tmu-card")) {
                    e.preventDefault();
                    const link = e.target.querySelector("a");
                    if (link) link.click();
                }
            });
        });
        </script>' . "\n";
    }
    
    /**
     * Get focusable elements selector
     */
    private function get_focusable_elements_selector(): string {
        return 'a, button, input, textarea, select, details, [tabindex]:not([tabindex="-1"]), [contenteditable="true"]';
    }
    
    /**
     * Log keyboard navigation events
     */
    public function log_keyboard_event($event_type, $data): void {
        if (!$this->settings['log_keyboard_events']) {
            return;
        }
        
        $log_entry = [
            'type' => 'keyboard_navigation',
            'event' => $event_type,
            'data' => $data,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        
        // Log to database or file
        do_action('tmu_log_accessibility_event', $log_entry);
    }
    
    /**
     * Get keyboard navigation settings
     */
    public function get_settings(): array {
        return $this->settings;
    }
    
    /**
     * Update keyboard navigation setting
     */
    public function update_setting($key, $value): bool {
        if (isset($this->settings[$key])) {
            $this->settings[$key] = $value;
            return true;
        }
        return false;
    }
    
    /**
     * Check if keyboard navigation is enabled
     */
    public function is_enabled(): bool {
        return $this->settings['enable_tab_navigation'] ?? true;
    }
    
    /**
     * Get keyboard shortcuts
     */
    public function get_keyboard_shortcuts(): array {
        return apply_filters('tmu_keyboard_shortcuts', [
            'skip_to_content' => 'Alt+1',
            'skip_to_navigation' => 'Alt+2',
            'skip_to_search' => 'Alt+3',
            'skip_to_footer' => 'Alt+4',
            'toggle_menu' => 'Alt+M',
            'open_search' => 'Alt+S'
        ]);
    }
    
    /**
     * Add skip link target validation
     */
    public function validate_skip_links(): array {
        $skip_targets = [
            '#main-content',
            '#site-navigation', 
            '#site-search',
            '#site-footer'
        ];
        
        $missing_targets = [];
        
        foreach ($skip_targets as $target) {
            // Note: This would need to be validated on frontend
            $missing_targets[] = $target;
        }
        
        return $missing_targets;
    }
}