# Step 14: Security and Accessibility

## Overview
This step implements comprehensive security hardening and accessibility compliance for the TMU theme, ensuring robust protection against common vulnerabilities and full WCAG 2.1 AA accessibility standards.

## 1. Security Implementation

### 1.1 Input Validation and Sanitization
```php
// src/Security/InputValidator.php
<?php
namespace TMU\Security;

class InputValidator {
    public function __construct() {
        add_action('init', [$this, 'init_security_filters']);
    }
    
    public function init_security_filters(): void {
        // Sanitize all input data
        add_filter('pre_get_posts', [$this, 'sanitize_query_vars']);
        add_action('wp_ajax_tmu_save_data', [$this, 'validate_ajax_request']);
        add_action('wp_ajax_nopriv_tmu_save_data', [$this, 'validate_ajax_request']);
    }
    
    public function sanitize_query_vars($query): void {
        if (!is_admin() && $query->is_main_query()) {
            $allowed_orderby = ['date', 'title', 'release_date', 'rating', 'popularity'];
            $orderby = $query->get('orderby');
            
            if ($orderby && !in_array($orderby, $allowed_orderby)) {
                $query->set('orderby', 'date');
            }
            
            $order = $query->get('order');
            if ($order && !in_array(strtoupper($order), ['ASC', 'DESC'])) {
                $query->set('order', 'DESC');
            }
        }
    }
    
    public function validate_ajax_request(): void {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tmu_ajax_nonce')) {
            wp_die('Security check failed');
        }
        
        // Validate user permissions
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions');
        }
        
        // Sanitize input data
        $data = $this->sanitize_input_data($_POST);
        
        // Process validated data
        $this->process_validated_data($data);
    }
    
    private function sanitize_input_data($data): array {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'post_id':
                    $sanitized[$key] = intval($value);
                    break;
                case 'tmdb_id':
                    $sanitized[$key] = intval($value);
                    break;
                case 'title':
                case 'original_title':
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
                case 'overview':
                case 'tagline':
                    $sanitized[$key] = sanitize_textarea_field($value);
                    break;
                case 'homepage':
                    $sanitized[$key] = esc_url_raw($value);
                    break;
                case 'release_date':
                    $sanitized[$key] = sanitize_text_field($value);
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sanitized[$key])) {
                        $sanitized[$key] = null;
                    }
                    break;
                default:
                    $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
}
```

### 1.2 SQL Injection Prevention
```php
// src/Security/DatabaseSecurity.php
<?php
namespace TMU\Security;

class DatabaseSecurity {
    public function __construct() {
        add_action('init', [$this, 'init_database_security']);
    }
    
    public function init_database_security(): void {
        // Override direct database queries with prepared statements
        add_filter('tmu_get_movie_data', [$this, 'get_movie_data_secure'], 10, 2);
        add_filter('tmu_search_content', [$this, 'search_content_secure'], 10, 2);
    }
    
    public function get_movie_data_secure($data, $post_id): array {
        global $wpdb;
        
        $post_id = intval($post_id);
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tmu_movies WHERE post_id = %d",
            $post_id
        );
        
        return $wpdb->get_row($query, ARRAY_A) ?: [];
    }
    
    public function search_content_secure($results, $search_params): array {
        global $wpdb;
        
        $search_term = sanitize_text_field($search_params['term']);
        $post_types = array_map('sanitize_text_field', $search_params['post_types']);
        $limit = intval($search_params['limit']) ?: 10;
        
        $post_types_placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        
        $query = $wpdb->prepare(
            "SELECT DISTINCT p.* FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->prefix}tmu_movies m ON p.ID = m.post_id
             LEFT JOIN {$wpdb->prefix}tmu_tv_series t ON p.ID = t.post_id
             WHERE p.post_type IN ({$post_types_placeholders})
             AND p.post_status = 'publish'
             AND (p.post_title LIKE %s OR m.title LIKE %s OR t.name LIKE %s)
             ORDER BY p.post_date DESC
             LIMIT %d",
            array_merge(
                $post_types,
                ["%{$search_term}%", "%{$search_term}%", "%{$search_term}%", $limit]
            )
        );
        
        return $wpdb->get_results($query);
    }
}
```

### 1.3 XSS Protection
```php
// src/Security/XssProtection.php
<?php
namespace TMU\Security;

class XssProtection {
    public function __construct() {
        add_action('init', [$this, 'init_xss_protection']);
    }
    
    public function init_xss_protection(): void {
        // Escape output data
        add_filter('tmu_display_movie_title', [$this, 'escape_html']);
        add_filter('tmu_display_movie_overview', [$this, 'escape_html']);
        add_filter('tmu_display_user_input', [$this, 'escape_html']);
        
        // Content Security Policy
        add_action('wp_head', [$this, 'add_csp_headers'], 1);
    }
    
    public function escape_html($content): string {
        return esc_html($content);
    }
    
    public function add_csp_headers(): void {
        $csp_policy = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://api.themoviedb.org",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "img-src 'self' data: https://image.tmdb.org https://secure.gravatar.com",
            "font-src 'self' https://fonts.gstatic.com",
            "connect-src 'self' https://api.themoviedb.org",
            "frame-src 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        
        echo '<meta http-equiv="Content-Security-Policy" content="' . implode('; ', $csp_policy) . '">';
    }
}
```

### 1.4 CSRF Protection
```php
// src/Security/CsrfProtection.php
<?php
namespace TMU\Security;

class CsrfProtection {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_csrf_token']);
        add_action('wp_ajax_*', [$this, 'verify_csrf_token'], 1);
    }
    
    public function enqueue_csrf_token(): void {
        wp_localize_script('tmu-main', 'tmu_security', [
            'ajax_nonce' => wp_create_nonce('tmu_ajax_nonce'),
            'csrf_token' => wp_create_nonce('tmu_csrf_token')
        ]);
    }
    
    public function verify_csrf_token(): void {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $action = $_POST['action'] ?? '';
            
            if (strpos($action, 'tmu_') === 0) {
                if (!wp_verify_nonce($_POST['csrf_token'] ?? '', 'tmu_csrf_token')) {
                    wp_die('CSRF token verification failed');
                }
            }
        }
    }
}
```

## 2. Accessibility Implementation

### 2.1 ARIA Labels and Roles
```php
// src/Accessibility/AriaLabels.php
<?php
namespace TMU\Accessibility;

class AriaLabels {
    public function __construct() {
        add_filter('tmu_navigation_menu', [$this, 'add_navigation_aria']);
        add_filter('tmu_search_form', [$this, 'add_search_aria']);
        add_filter('tmu_content_cards', [$this, 'add_content_aria']);
    }
    
    public function add_navigation_aria($menu_html): string {
        $menu_html = str_replace(
            '<nav class="tmu-navigation"',
            '<nav class="tmu-navigation" role="navigation" aria-label="Main navigation"',
            $menu_html
        );
        
        return $menu_html;
    }
    
    public function add_search_aria($form_html): string {
        $form_html = str_replace(
            '<form class="tmu-search-form"',
            '<form class="tmu-search-form" role="search" aria-label="Search movies and TV shows"',
            $form_html
        );
        
        $form_html = str_replace(
            '<input type="search"',
            '<input type="search" aria-label="Search query" aria-describedby="search-help"',
            $form_html
        );
        
        return $form_html;
    }
    
    public function add_content_aria($cards_html): string {
        $cards_html = str_replace(
            '<div class="tmu-content-grid"',
            '<div class="tmu-content-grid" role="main" aria-label="Content grid"',
            $cards_html
        );
        
        return $cards_html;
    }
}
```

### 2.2 Keyboard Navigation
```php
// src/Accessibility/KeyboardNavigation.php
<?php
namespace TMU\Accessibility;

class KeyboardNavigation {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_keyboard_scripts']);
        add_filter('tmu_interactive_elements', [$this, 'add_keyboard_attributes']);
    }
    
    public function enqueue_keyboard_scripts(): void {
        wp_enqueue_script(
            'tmu-keyboard-navigation',
            get_template_directory_uri() . '/assets/js/keyboard-navigation.js',
            [],
            '1.0.0',
            true
        );
    }
    
    public function add_keyboard_attributes($elements): array {
        foreach ($elements as &$element) {
            if (!isset($element['tabindex'])) {
                $element['tabindex'] = 0;
            }
            
            if (!isset($element['role'])) {
                $element['role'] = 'button';
            }
            
            if (!isset($element['aria-label'])) {
                $element['aria-label'] = $element['title'] ?? 'Interactive element';
            }
        }
        
        return $elements;
    }
}
```

### 2.3 Screen Reader Support
```javascript
// assets/js/keyboard-navigation.js
class TMUKeyboardNavigation {
    constructor() {
        this.focusableElements = 'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])';
        this.init();
    }
    
    init() {
        this.setupKeyboardHandlers();
        this.setupFocusManagement();
        this.setupSkipLinks();
    }
    
    setupKeyboardHandlers() {
        document.addEventListener('keydown', (e) => {
            switch (e.key) {
                case 'Tab':
                    this.handleTabNavigation(e);
                    break;
                case 'Enter':
                case ' ':
                    this.handleActivation(e);
                    break;
                case 'Escape':
                    this.handleEscape(e);
                    break;
                case 'ArrowUp':
                case 'ArrowDown':
                case 'ArrowLeft':
                case 'ArrowRight':
                    this.handleArrowNavigation(e);
                    break;
            }
        });
    }
    
    handleTabNavigation(e) {
        const focusableElements = document.querySelectorAll(this.focusableElements);
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        if (e.shiftKey) {
            if (document.activeElement === firstElement) {
                lastElement.focus();
                e.preventDefault();
            }
        } else {
            if (document.activeElement === lastElement) {
                firstElement.focus();
                e.preventDefault();
            }
        }
    }
    
    handleActivation(e) {
        const target = e.target;
        
        if (target.classList.contains('tmu-card') || target.closest('.tmu-card')) {
            const card = target.closest('.tmu-card') || target;
            const link = card.querySelector('a');
            
            if (link) {
                link.click();
                e.preventDefault();
            }
        }
    }
    
    handleEscape(e) {
        // Close modals, dropdowns, etc.
        const modal = document.querySelector('.tmu-modal.active');
        if (modal) {
            this.closeModal(modal);
            e.preventDefault();
        }
        
        const dropdown = document.querySelector('.tmu-dropdown.open');
        if (dropdown) {
            this.closeDropdown(dropdown);
            e.preventDefault();
        }
    }
    
    handleArrowNavigation(e) {
        const grid = e.target.closest('.tmu-content-grid');
        if (!grid) return;
        
        const cards = Array.from(grid.querySelectorAll('.tmu-card'));
        const currentIndex = cards.indexOf(e.target.closest('.tmu-card'));
        
        if (currentIndex === -1) return;
        
        let newIndex;
        const columns = this.getGridColumns(grid);
        
        switch (e.key) {
            case 'ArrowUp':
                newIndex = currentIndex - columns;
                break;
            case 'ArrowDown':
                newIndex = currentIndex + columns;
                break;
            case 'ArrowLeft':
                newIndex = currentIndex - 1;
                break;
            case 'ArrowRight':
                newIndex = currentIndex + 1;
                break;
        }
        
        if (newIndex >= 0 && newIndex < cards.length) {
            cards[newIndex].focus();
            e.preventDefault();
        }
    }
    
    setupFocusManagement() {
        // Add focus indicators
        document.addEventListener('focusin', (e) => {
            e.target.classList.add('tmu-focused');
        });
        
        document.addEventListener('focusout', (e) => {
            e.target.classList.remove('tmu-focused');
        });
        
        // Trap focus in modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                const modal = document.querySelector('.tmu-modal.active');
                if (modal) {
                    this.trapFocus(modal, e);
                }
            }
        });
    }
    
    setupSkipLinks() {
        const skipLink = document.createElement('a');
        skipLink.href = '#main-content';
        skipLink.className = 'tmu-skip-link';
        skipLink.textContent = 'Skip to main content';
        
        document.body.insertBefore(skipLink, document.body.firstChild);
        
        skipLink.addEventListener('click', (e) => {
            e.preventDefault();
            const mainContent = document.getElementById('main-content');
            if (mainContent) {
                mainContent.focus();
                mainContent.scrollIntoView();
            }
        });
    }
    
    trapFocus(modal, e) {
        const focusableElements = modal.querySelectorAll(this.focusableElements);
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        if (e.shiftKey) {
            if (document.activeElement === firstElement) {
                lastElement.focus();
                e.preventDefault();
            }
        } else {
            if (document.activeElement === lastElement) {
                firstElement.focus();
                e.preventDefault();
            }
        }
    }
    
    getGridColumns(grid) {
        const style = window.getComputedStyle(grid);
        const columns = style.gridTemplateColumns.split(' ').length;
        return columns;
    }
    
    closeModal(modal) {
        modal.classList.remove('active');
        const trigger = document.querySelector(`[data-modal="${modal.id}"]`);
        if (trigger) {
            trigger.focus();
        }
    }
    
    closeDropdown(dropdown) {
        dropdown.classList.remove('open');
        const trigger = dropdown.querySelector('.tmu-dropdown-trigger');
        if (trigger) {
            trigger.focus();
        }
    }
}

// Initialize keyboard navigation
document.addEventListener('DOMContentLoaded', () => {
    new TMUKeyboardNavigation();
});
```

### 2.4 Color Contrast and Visual Accessibility
```scss
// assets/scss/accessibility.scss
.tmu-skip-link {
    position: absolute;
    top: -40px;
    left: 6px;
    background: #000;
    color: #fff;
    padding: 8px;
    text-decoration: none;
    z-index: 100000;
    border-radius: 4px;
    
    &:focus {
        top: 6px;
    }
}

.tmu-focused {
    outline: 2px solid #005fcc;
    outline-offset: 2px;
}

// High contrast mode support
@media (prefers-contrast: high) {
    .tmu-card {
        border: 2px solid #000;
    }
    
    .tmu-button {
        border: 2px solid #000;
        background: #fff;
        color: #000;
        
        &:hover, &:focus {
            background: #000;
            color: #fff;
        }
    }
}

// Reduced motion support
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

// Dark mode support
@media (prefers-color-scheme: dark) {
    .tmu-theme {
        --bg-color: #1a1a1a;
        --text-color: #ffffff;
        --accent-color: #4a9eff;
        --border-color: #333333;
    }
}
```

## 3. Security Headers and Configuration

### 3.1 Security Headers
```php
// src/Security/SecurityHeaders.php
<?php
namespace TMU\Security;

class SecurityHeaders {
    public function __construct() {
        add_action('init', [$this, 'set_security_headers']);
    }
    
    public function set_security_headers(): void {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Content Type Options
        header('X-Content-Type-Options: nosniff');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions Policy
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        
        // HSTS (only on HTTPS)
        if (is_ssl()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
```

### 3.2 File Upload Security
```php
// src/Security/FileUploadSecurity.php
<?php
namespace TMU\Security;

class FileUploadSecurity {
    private $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private $max_file_size = 5242880; // 5MB
    
    public function __construct() {
        add_filter('wp_handle_upload_prefilter', [$this, 'validate_file_upload']);
        add_filter('upload_mimes', [$this, 'restrict_upload_mimes']);
    }
    
    public function validate_file_upload($file): array {
        // Check file size
        if ($file['size'] > $this->max_file_size) {
            $file['error'] = 'File size exceeds maximum allowed size of 5MB';
            return $file;
        }
        
        // Check file type
        if (!in_array($file['type'], $this->allowed_types)) {
            $file['error'] = 'File type not allowed';
            return $file;
        }
        
        // Check file extension
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($file_ext, $allowed_extensions)) {
            $file['error'] = 'File extension not allowed';
            return $file;
        }
        
        // Validate image file
        if (!$this->is_valid_image($file['tmp_name'])) {
            $file['error'] = 'Invalid image file';
            return $file;
        }
        
        return $file;
    }
    
    public function restrict_upload_mimes($mimes): array {
        // Remove potentially dangerous file types
        unset($mimes['svg']);
        unset($mimes['svgz']);
        unset($mimes['exe']);
        unset($mimes['php']);
        
        return $mimes;
    }
    
    private function is_valid_image($file_path): bool {
        $image_info = getimagesize($file_path);
        return $image_info !== false;
    }
}
```

## 4. Accessibility Testing and Validation

### 4.1 Accessibility Checker
```php
// src/Accessibility/AccessibilityChecker.php
<?php
namespace TMU\Accessibility;

class AccessibilityChecker {
    public function __construct() {
        if (current_user_can('manage_options')) {
            add_action('wp_footer', [$this, 'add_accessibility_checker']);
        }
    }
    
    public function add_accessibility_checker(): void {
        if (isset($_GET['accessibility_check'])) {
            ?>
            <script>
            (function() {
                const issues = [];
                
                // Check for missing alt text
                document.querySelectorAll('img').forEach(img => {
                    if (!img.alt && !img.getAttribute('aria-label')) {
                        issues.push('Missing alt text: ' + img.src);
                    }
                });
                
                // Check for missing labels
                document.querySelectorAll('input, textarea, select').forEach(input => {
                    if (!input.labels.length && !input.getAttribute('aria-label') && !input.getAttribute('aria-labelledby')) {
                        issues.push('Missing label: ' + input.name);
                    }
                });
                
                // Check for missing headings
                const headings = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
                if (headings.length === 0) {
                    issues.push('No headings found on page');
                }
                
                // Check for color contrast (simplified)
                document.querySelectorAll('*').forEach(element => {
                    const style = window.getComputedStyle(element);
                    const bgColor = style.backgroundColor;
                    const textColor = style.color;
                    
                    if (bgColor !== 'rgba(0, 0, 0, 0)' && textColor !== 'rgba(0, 0, 0, 0)') {
                        // Simplified contrast check - in real implementation, use proper contrast calculation
                        if (this.getContrastRatio(bgColor, textColor) < 4.5) {
                            issues.push('Low contrast detected: ' + element.tagName);
                        }
                    }
                });
                
                console.log('Accessibility Issues:', issues);
            })();
            </script>
            <?php
        }
    }
}
```

## Success Metrics

### Security Metrics
- **Vulnerability Score**: 0 critical, 0 high vulnerabilities
- **Security Headers**: All recommended headers implemented
- **Input Validation**: 100% of user inputs validated and sanitized
- **SQL Injection**: All database queries use prepared statements
- **XSS Protection**: All output properly escaped
- **CSRF Protection**: All forms protected with tokens

### Accessibility Metrics
- **WCAG 2.1 AA Compliance**: 100% compliance
- **Keyboard Navigation**: All interactive elements accessible via keyboard
- **Screen Reader Support**: All content readable by screen readers
- **Color Contrast**: Minimum 4.5:1 ratio for normal text, 3:1 for large text
- **Focus Management**: Clear focus indicators and proper focus flow
- **Alternative Text**: All images have descriptive alt text

This comprehensive security and accessibility implementation ensures the TMU theme is both secure and inclusive for all users.