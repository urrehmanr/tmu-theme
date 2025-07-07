<?php
/**
 * XSS Protection
 * 
 * Cross-Site Scripting (XSS) protection and output sanitization.
 * Ensures all user content is properly escaped and filtered.
 * 
 * @package TMU\Security
 * @since 1.0.0
 */

namespace TMU\Security;

/**
 * XssProtection class
 * 
 * Handles XSS prevention and output sanitization
 */
class XssProtection {
    
    /**
     * Allowed HTML tags and attributes
     * @var array
     */
    private $allowed_html = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_allowed_html();
        $this->init_hooks();
    }
    
    /**
     * Initialize allowed HTML
     */
    private function init_allowed_html(): void {
        $this->allowed_html = [
            'p' => [],
            'br' => [],
            'strong' => [],
            'b' => [],
            'em' => [],
            'i' => [],
            'u' => [],
            'a' => [
                'href' => [],
                'title' => [],
                'target' => [],
                'rel' => []
            ],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'h1' => [],
            'h2' => [],
            'h3' => [],
            'h4' => [],
            'h5' => [],
            'h6' => [],
            'blockquote' => [],
            'code' => [],
            'pre' => [],
            'span' => [
                'class' => []
            ],
            'div' => [
                'class' => [],
                'id' => []
            ]
        ];
        
        // Allow customization via filters
        $this->allowed_html = apply_filters('tmu_allowed_html', $this->allowed_html);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        add_action('init', [$this, 'init_xss_protection']);
        
        // Escape output data
        add_filter('tmu_display_movie_title', [$this, 'escape_html']);
        add_filter('tmu_display_movie_overview', [$this, 'escape_content']);
        add_filter('tmu_display_tv_title', [$this, 'escape_html']);
        add_filter('tmu_display_tv_overview', [$this, 'escape_content']);
        add_filter('tmu_display_drama_title', [$this, 'escape_html']);
        add_filter('tmu_display_drama_overview', [$this, 'escape_content']);
        add_filter('tmu_display_people_name', [$this, 'escape_html']);
        add_filter('tmu_display_people_biography', [$this, 'escape_content']);
        add_filter('tmu_display_user_input', [$this, 'escape_html']);
        add_filter('tmu_display_search_term', [$this, 'escape_search_term']);
        
        // Content filtering
        add_filter('the_content', [$this, 'filter_content'], 999);
        add_filter('the_excerpt', [$this, 'filter_content'], 999);
        add_filter('comment_text', [$this, 'filter_content'], 999);
        add_filter('get_comment_text', [$this, 'filter_content'], 999);
        
        // Form input filtering
        add_filter('pre_comment_content', [$this, 'filter_comment_content']);
        
        // Admin output filtering
        if (is_admin()) {
            add_filter('esc_html', [$this, 'admin_escape_html'], 10, 2);
        }
        
        // Content Security Policy
        add_action('wp_head', [$this, 'add_csp_headers'], 1);
        add_action('admin_head', [$this, 'add_admin_csp_headers'], 1);
        
        // Script and style filtering
        add_filter('script_loader_src', [$this, 'filter_script_src']);
        add_filter('style_loader_src', [$this, 'filter_style_src']);
        
        // AJAX response filtering
        add_filter('wp_die_ajax_handler', [$this, 'filter_ajax_response']);
        add_filter('wp_die_handler', [$this, 'filter_die_response']);
    }
    
    /**
     * Initialize XSS protection measures
     */
    public function init_xss_protection(): void {
        // Set up output buffering for additional protection
        if (!is_admin()) {
            ob_start([$this, 'filter_output_buffer']);
        }
        
        // Initialize input filtering
        $this->init_input_filtering();
        
        // Set up URL validation
        $this->init_url_validation();
        
        // Initialize JavaScript filtering
        $this->init_javascript_filtering();
    }
    
    /**
     * Escape HTML content
     */
    public function escape_html($content): string {
        return esc_html($content);
    }
    
    /**
     * Escape content with allowed HTML
     */
    public function escape_content($content): string {
        return wp_kses($content, $this->allowed_html);
    }
    
    /**
     * Escape search terms
     */
    public function escape_search_term($term): string {
        // Remove script tags and potentially dangerous content
        $term = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $term);
        $term = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $term);
        $term = preg_replace('/javascript:/i', '', $term);
        $term = preg_replace('/vbscript:/i', '', $term);
        $term = preg_replace('/onload|onerror|onclick|onmouseover/i', '', $term);
        
        return esc_html($term);
    }
    
    /**
     * Filter content
     */
    public function filter_content($content): string {
        // Remove potentially dangerous content
        $content = $this->remove_malicious_content($content);
        
        // Filter HTML tags
        $content = wp_kses($content, $this->get_content_allowed_html());
        
        // Remove dangerous attributes
        $content = $this->remove_dangerous_attributes($content);
        
        return $content;
    }
    
    /**
     * Filter comment content
     */
    public function filter_comment_content($content): string {
        // Stricter filtering for comments
        $comment_allowed_html = [
            'p' => [],
            'br' => [],
            'strong' => [],
            'b' => [],
            'em' => [],
            'i' => [],
            'a' => [
                'href' => [],
                'title' => []
            ]
        ];
        
        $content = wp_kses($content, $comment_allowed_html);
        $content = $this->remove_malicious_content($content);
        
        return $content;
    }
    
    /**
     * Admin escape HTML
     */
    public function admin_escape_html($safe_text, $text): string {
        // Additional escaping for admin areas
        if (strpos($text, '<script') !== false || strpos($text, 'javascript:') !== false) {
            $this->log_xss_attempt($text, 'admin_content');
            return esc_html(strip_tags($text));
        }
        
        return $safe_text;
    }
    
    /**
     * Add Content Security Policy headers
     */
    public function add_csp_headers(): void {
        if (headers_sent()) {
            return;
        }
        
        $csp_policy = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://api.themoviedb.org https://www.google.com https://www.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "img-src 'self' data: https: blob:",
            "font-src 'self' https://fonts.gstatic.com",
            "connect-src 'self' https://api.themoviedb.org",
            "media-src 'self' data: blob:",
            "object-src 'none'",
            "frame-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "upgrade-insecure-requests"
        ];
        
        $csp_policy = apply_filters('tmu_csp_policy', $csp_policy);
        
        header('Content-Security-Policy: ' . implode('; ', $csp_policy));
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
    
    /**
     * Add admin CSP headers
     */
    public function add_admin_csp_headers(): void {
        if (headers_sent()) {
            return;
        }
        
        $admin_csp_policy = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "object-src 'none'",
            "frame-src 'self'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        
        $admin_csp_policy = apply_filters('tmu_admin_csp_policy', $admin_csp_policy);
        
        header('Content-Security-Policy: ' . implode('; ', $admin_csp_policy));
    }
    
    /**
     * Filter script sources
     */
    public function filter_script_src($src): string {
        // Validate script sources
        if ($this->is_suspicious_url($src)) {
            $this->log_xss_attempt($src, 'script_src');
            return '';
        }
        
        return $src;
    }
    
    /**
     * Filter style sources
     */
    public function filter_style_src($src): string {
        // Validate style sources
        if ($this->is_suspicious_url($src)) {
            $this->log_xss_attempt($src, 'style_src');
            return '';
        }
        
        return $src;
    }
    
    /**
     * Filter AJAX responses
     */
    public function filter_ajax_response($callback) {
        return function($message, $title = '', $args = []) use ($callback) {
            $message = $this->escape_content($message);
            $title = $this->escape_html($title);
            return $callback($message, $title, $args);
        };
    }
    
    /**
     * Filter die responses
     */
    public function filter_die_response($callback) {
        return function($message, $title = '', $args = []) use ($callback) {
            if (!is_admin()) {
                $message = $this->escape_content($message);
                $title = $this->escape_html($title);
            }
            return $callback($message, $title, $args);
        };
    }
    
    /**
     * Filter output buffer
     */
    public function filter_output_buffer($buffer): string {
        // Only filter if not admin and not AJAX
        if (is_admin() || wp_doing_ajax()) {
            return $buffer;
        }
        
        // Remove dangerous script patterns
        $buffer = preg_replace('/<script[^>]*>.*?eval\s*\(/is', '<script>', $buffer);
        $buffer = preg_replace('/<script[^>]*>.*?document\.write\s*\(/is', '<script>', $buffer);
        $buffer = preg_replace('/javascript:\s*void\s*\(0\)/i', '#', $buffer);
        
        // Remove dangerous event handlers from HTML attributes
        $buffer = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $buffer);
        
        return $buffer;
    }
    
    /**
     * Remove malicious content
     */
    private function remove_malicious_content($content): string {
        // Remove script tags
        $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);
        
        // Remove style tags with javascript
        $content = preg_replace('/<style[^>]*>.*?expression\s*\(/is', '<style>', $content);
        $content = preg_replace('/<style[^>]*>.*?javascript:/is', '<style>', $content);
        
        // Remove iframe tags
        $content = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi', '', $content);
        
        // Remove object and embed tags
        $content = preg_replace('/<(object|embed)\b[^<]*(?:(?!<\/\1>)<[^<]*)*<\/\1>/mi', '', $content);
        
        // Remove form tags in content
        $content = preg_replace('/<form\b[^<]*(?:(?!<\/form>)<[^<]*)*<\/form>/mi', '', $content);
        
        // Remove dangerous URL schemes
        $content = preg_replace('/javascript:/i', '', $content);
        $content = preg_replace('/vbscript:/i', '', $content);
        $content = preg_replace('/data:/i', '', $content);
        $content = preg_replace('/file:/i', '', $content);
        
        return $content;
    }
    
    /**
     * Remove dangerous attributes
     */
    private function remove_dangerous_attributes($content): string {
        // Remove event handlers
        $content = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
        
        // Remove dangerous CSS expressions
        $content = preg_replace('/style\s*=\s*["\'][^"\']*expression\s*\([^"\']*["\']/i', '', $content);
        $content = preg_replace('/style\s*=\s*["\'][^"\']*javascript:[^"\']*["\']/i', '', $content);
        
        // Remove dangerous href attributes
        $content = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $content);
        $content = preg_replace('/href\s*=\s*["\']vbscript:[^"\']*["\']/i', 'href="#"', $content);
        
        return $content;
    }
    
    /**
     * Get content allowed HTML
     */
    private function get_content_allowed_html(): array {
        $content_html = $this->allowed_html;
        
        // Add additional tags for content
        $content_html['img'] = [
            'src' => [],
            'alt' => [],
            'title' => [],
            'width' => [],
            'height' => [],
            'class' => []
        ];
        
        $content_html['table'] = [];
        $content_html['tr'] = [];
        $content_html['td'] = [];
        $content_html['th'] = [];
        $content_html['thead'] = [];
        $content_html['tbody'] = [];
        $content_html['tfoot'] = [];
        
        return apply_filters('tmu_content_allowed_html', $content_html);
    }
    
    /**
     * Initialize input filtering
     */
    private function init_input_filtering(): void {
        // Filter $_GET variables
        if (!empty($_GET)) {
            foreach ($_GET as $key => $value) {
                $_GET[$key] = $this->filter_input_value($value);
            }
        }
        
        // Filter $_POST variables (excluding admin and specific contexts)
        if (!is_admin() && !wp_doing_ajax() && !empty($_POST)) {
            foreach ($_POST as $key => $value) {
                if (!in_array($key, ['content', 'description', 'excerpt'])) {
                    $_POST[$key] = $this->filter_input_value($value);
                }
            }
        }
    }
    
    /**
     * Filter input value
     */
    private function filter_input_value($value) {
        if (is_array($value)) {
            return array_map([$this, 'filter_input_value'], $value);
        }
        
        if (is_string($value)) {
            // Remove script tags and dangerous content
            $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $value);
            $value = preg_replace('/javascript:/i', '', $value);
            $value = preg_replace('/vbscript:/i', '', $value);
            $value = preg_replace('/on\w+\s*=/i', '', $value);
        }
        
        return $value;
    }
    
    /**
     * Initialize URL validation
     */
    private function init_url_validation(): void {
        add_filter('wp_redirect', [$this, 'validate_redirect_url']);
        add_filter('allowed_redirect_hosts', [$this, 'filter_redirect_hosts']);
    }
    
    /**
     * Validate redirect URL
     */
    public function validate_redirect_url($location): string {
        if ($this->is_suspicious_url($location)) {
            $this->log_xss_attempt($location, 'redirect_url');
            return home_url();
        }
        
        return $location;
    }
    
    /**
     * Filter redirect hosts
     */
    public function filter_redirect_hosts($hosts): array {
        // Only allow specific trusted hosts
        $trusted_hosts = [
            parse_url(home_url(), PHP_URL_HOST),
            'api.themoviedb.org'
        ];
        
        return array_merge($hosts, $trusted_hosts);
    }
    
    /**
     * Initialize JavaScript filtering
     */
    private function init_javascript_filtering(): void {
        add_filter('wp_inline_script_tag', [$this, 'filter_inline_script']);
    }
    
    /**
     * Filter inline scripts
     */
    public function filter_inline_script($tag): string {
        // Check for dangerous JavaScript patterns
        if (preg_match('/eval\s*\(|document\.write\s*\(|innerHTML\s*=/i', $tag)) {
            $this->log_xss_attempt($tag, 'inline_script');
            return '';
        }
        
        return $tag;
    }
    
    /**
     * Check if URL is suspicious
     */
    private function is_suspicious_url($url): bool {
        if (empty($url)) {
            return false;
        }
        
        // Check for dangerous schemes
        if (preg_match('/^(javascript|vbscript|data|file):/i', $url)) {
            return true;
        }
        
        // Check for suspicious patterns
        if (preg_match('/[<>"\'\(\)]/i', $url)) {
            return true;
        }
        
        // Check for external URLs that might be malicious
        $parsed_url = parse_url($url);
        if (isset($parsed_url['host'])) {
            $site_host = parse_url(home_url(), PHP_URL_HOST);
            $allowed_hosts = apply_filters('tmu_allowed_external_hosts', [
                'api.themoviedb.org',
                'image.tmdb.org',
                'fonts.googleapis.com',
                'fonts.gstatic.com'
            ]);
            
            if ($parsed_url['host'] !== $site_host && !in_array($parsed_url['host'], $allowed_hosts)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log XSS attempt
     */
    private function log_xss_attempt($content, $context): void {
        do_action('tmu_security_event', 'xss_attempt', [
            'content' => substr($content, 0, 500), // Limit content length
            'context' => $context,
            'ip' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'user_id' => get_current_user_id(),
            'url' => sanitize_text_field($_SERVER['REQUEST_URI'] ?? '')
        ], 'high');
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip(): string {
        $ip_headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get allowed HTML tags
     */
    public function get_allowed_html(): array {
        return $this->allowed_html;
    }
    
    /**
     * Add allowed HTML tag
     */
    public function add_allowed_html($tag, $attributes = []): void {
        $this->allowed_html[$tag] = $attributes;
    }
    
    /**
     * Remove allowed HTML tag
     */
    public function remove_allowed_html($tag): void {
        unset($this->allowed_html[$tag]);
    }
    
    /**
     * Clean content for output
     */
    public function clean_content($content, $context = 'default'): string {
        switch ($context) {
            case 'title':
                return $this->escape_html($content);
            case 'url':
                return esc_url($content);
            case 'attribute':
                return esc_attr($content);
            case 'textarea':
                return esc_textarea($content);
            case 'content':
            default:
                return $this->escape_content($content);
        }
    }
}