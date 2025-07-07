<?php
/**
 * Lazy Loader - Server-side Lazy Loading Coordination
 *
 * @package TMU\Performance
 * @version 1.0.0
 */

namespace TMU\Performance;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Server-side Lazy Loading System
 */
class LazyLoader {
    
    /**
     * Lazy loading enabled
     */
    private bool $lazy_loading_enabled;
    
    /**
     * Critical images count
     */
    private int $critical_images_count = 3;
    
    /**
     * Lazy loading settings
     */
    private array $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->lazy_loading_enabled = get_option('tmu_lazy_loading_enabled', true);
        $this->settings = [
            'placeholder_color' => get_option('tmu_lazy_placeholder_color', '#f0f0f0'),
            'loading_animation' => get_option('tmu_lazy_loading_animation', true),
            'critical_threshold' => get_option('tmu_lazy_critical_threshold', 3),
            'enable_content_lazy_load' => get_option('tmu_lazy_content_enabled', true),
            'enable_background_lazy_load' => get_option('tmu_lazy_background_enabled', true)
        ];
        
        $this->init();
    }
    
    /**
     * Initialize lazy loader
     */
    public function init(): void {
        if (!$this->lazy_loading_enabled) {
            return;
        }
        
        // Enqueue lazy loading scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_lazy_loading_scripts']);
        
        // Content filtering
        add_filter('the_content', [$this, 'add_lazy_loading_to_content'], 20);
        add_filter('post_thumbnail_html', [$this, 'add_lazy_loading_to_thumbnail'], 10, 5);
        add_filter('wp_get_attachment_image', [$this, 'add_lazy_loading_to_attachment_image'], 10, 5);
        
        // Widget content
        add_filter('widget_text', [$this, 'add_lazy_loading_to_content'], 20);
        
        // Comment content
        add_filter('comment_text', [$this, 'add_lazy_loading_to_content'], 20);
        
        // Gallery images
        add_filter('wp_get_attachment_link', [$this, 'add_lazy_loading_to_gallery_images'], 10, 6);
        
        // Background images
        add_action('wp_head', [$this, 'inject_background_lazy_loading_style']);
        
        // Admin settings
        add_action('admin_init', [$this, 'register_lazy_loading_settings']);
        
        // AJAX endpoints
        add_action('wp_ajax_tmu_lazy_load_content', [$this, 'ajax_load_content']);
        add_action('wp_ajax_nopriv_tmu_lazy_load_content', [$this, 'ajax_load_content']);
    }
    
    /**
     * Enqueue lazy loading scripts
     */
    public function enqueue_lazy_loading_scripts(): void {
        if (is_admin()) {
            return;
        }
        
        // Enqueue the main lazy loading script
        wp_enqueue_script(
            'tmu-lazy-load',
            get_template_directory_uri() . '/assets/js/lazy-load.js',
            [],
            '1.0.0',
            true
        );
        
        // Localize script with settings
        wp_localize_script('tmu-lazy-load', 'tmuLazyLoad', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tmu_lazy_load'),
            'settings' => $this->settings,
            'placeholderColor' => $this->settings['placeholder_color'],
            'enableAnimation' => $this->settings['loading_animation'],
            'criticalThreshold' => $this->settings['critical_threshold']
        ]);
        

    }
    
    /**
     * Add lazy loading to content
     */
    public function add_lazy_loading_to_content($content): string {
        if (is_admin() || is_feed() || wp_is_json_request()) {
            return $content;
        }
        
        // Process images in content
        $content = preg_replace_callback(
            '/<img([^>]+)>/i',
            [$this, 'add_lazy_loading_attributes'],
            $content
        );
        
        // Process background images
        if ($this->settings['enable_background_lazy_load']) {
            $content = preg_replace_callback(
                '/style=["\'][^"\']*background-image:\s*url\([^)]+\)[^"\']*["\']/i',
                [$this, 'add_lazy_loading_to_background_images'],
                $content
            );
        }
        
        // Process lazy content blocks
        if ($this->settings['enable_content_lazy_load']) {
            $content = $this->add_lazy_content_blocks($content);
        }
        
        return $content;
    }
    
    /**
     * Add lazy loading attributes to images
     */
    public function add_lazy_loading_attributes($matches): string {
        $img_tag = $matches[0];
        $attributes = $matches[1];
        
        // Skip if already has lazy loading
        if (strpos($attributes, 'data-src') !== false || strpos($attributes, 'loading="lazy"') !== false) {
            return $img_tag;
        }
        
        // Skip critical images (first few images on the page)
        static $image_count = 0;
        $image_count++;
        
        if ($image_count <= $this->critical_images_count) {
            // Add loading="eager" to critical images
            if (strpos($attributes, 'loading=') === false) {
                $attributes .= ' loading="eager" data-critical="true"';
            }
            return '<img' . $attributes . '>';
        }
        
        // Extract src attribute
        if (preg_match('/src=["\']([^"\']+)["\']/', $attributes, $src_matches)) {
            $original_src = $src_matches[1];
            
            // Generate placeholder
            $placeholder = $this->generate_placeholder_image($attributes);
            
            // Replace src with placeholder and add data-src
            $attributes = str_replace(
                'src="' . $original_src . '"',
                'src="' . $placeholder . '" data-src="' . $original_src . '"',
                $attributes
            );
            
            // Handle srcset
            if (preg_match('/srcset=["\']([^"\']+)["\']/', $attributes, $srcset_matches)) {
                $srcset = $srcset_matches[1];
                $attributes = str_replace(
                    'srcset="' . $srcset . '"',
                    'data-srcset="' . $srcset . '"',
                    $attributes
                );
            }
            
            // Handle sizes
            if (preg_match('/sizes=["\']([^"\']+)["\']/', $attributes, $sizes_matches)) {
                $sizes = $sizes_matches[1];
                $attributes = str_replace(
                    'sizes="' . $sizes . '"',
                    'data-sizes="' . $sizes . '"',
                    $attributes
                );
            }
            
            // Add lazy loading class
            if (strpos($attributes, 'class=') !== false) {
                $attributes = preg_replace(
                    '/class=["\']([^"\']*)["\']/',
                    'class="$1 lazy-load"',
                    $attributes
                );
            } else {
                $attributes .= ' class="lazy-load"';
            }
            
            // Add loading attribute
            if (strpos($attributes, 'loading=') === false) {
                $attributes .= ' loading="lazy"';
            }
        }
        
        return '<img' . $attributes . '>';
    }
    
    /**
     * Add lazy loading to post thumbnails
     */
    public function add_lazy_loading_to_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr): string {
        if (is_admin() || empty($html)) {
            return $html;
        }
        
        // Process thumbnail HTML
        return preg_replace_callback(
            '/<img([^>]+)>/i',
            [$this, 'add_lazy_loading_attributes'],
            $html
        );
    }
    
    /**
     * Add lazy loading to attachment images
     */
    public function add_lazy_loading_to_attachment_image($html, $attachment_id, $size, $icon, $attr): string {
        if (is_admin() || empty($html)) {
            return $html;
        }
        
        // Process attachment image HTML
        return preg_replace_callback(
            '/<img([^>]+)>/i',
            [$this, 'add_lazy_loading_attributes'],
            $html
        );
    }
    
    /**
     * Add lazy loading to gallery images
     */
    public function add_lazy_loading_to_gallery_images($link_html, $attachment_id, $size, $permalink, $icon, $text): string {
        if (is_admin() || empty($link_html)) {
            return $link_html;
        }
        
        // Process gallery image HTML
        return preg_replace_callback(
            '/<img([^>]+)>/i',
            [$this, 'add_lazy_loading_attributes'],
            $link_html
        );
    }
    
    /**
     * Add lazy loading to background images
     */
    public function add_lazy_loading_to_background_images($matches): string {
        $style_attr = $matches[0];
        
        // Extract background image URL
        if (preg_match('/background-image:\s*url\(([^)]+)\)/', $style_attr, $bg_matches)) {
            $bg_url = trim($bg_matches[1], '"\'');
            
            // Replace style with data attribute
            $new_style = str_replace(
                'background-image: url(' . $bg_matches[1] . ')',
                '',
                $style_attr
            );
            
            // Clean up any empty style attributes
            if (preg_match('/style=["\']["\']/', $new_style)) {
                $new_style = '';
            }
            
            return $new_style . ' data-bg-src="' . $bg_url . '" class="lazy-background"';
        }
        
        return $style_attr;
    }
    
    /**
     * Add lazy content blocks
     */
    private function add_lazy_content_blocks($content): string {
        // This would identify content sections that can be loaded lazily
        // For example, comment sections, related posts, etc.
        
        // Placeholder for lazy content implementation
        // In a full implementation, this would identify specific content blocks
        
        return $content;
    }
    
    /**
     * Generate placeholder image
     */
    private function generate_placeholder_image($attributes): string {
        // Extract width and height from attributes
        $width = 300;
        $height = 200;
        
        if (preg_match('/width=["\'](\d+)["\']/', $attributes, $width_matches)) {
            $width = intval($width_matches[1]);
        }
        
        if (preg_match('/height=["\'](\d+)["\']/', $attributes, $height_matches)) {
            $height = intval($height_matches[1]);
        }
        
        // Generate SVG placeholder
        $svg = $this->create_svg_placeholder($width, $height);
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    /**
     * Create SVG placeholder
     */
    private function create_svg_placeholder($width, $height): string {
        $color = $this->settings['placeholder_color'];
        
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '">';
        $svg .= '<rect width="100%" height="100%" fill="' . $color . '"/>';
        
        if ($this->settings['loading_animation']) {
            $svg .= '<text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#999" font-family="sans-serif" font-size="14">';
            $svg .= 'Loading...';
            $svg .= '</text>';
        }
        
        $svg .= '</svg>';
        
        return $svg;
    }
    
    /**
     * Inject background lazy loading styles
     */
    public function inject_background_lazy_loading_style(): void {
        if (is_admin()) {
            return;
        }
        
        echo '<style>
            .lazy-background {
                background-color: ' . $this->settings['placeholder_color'] . ';
                transition: opacity 0.3s ease-in-out;
            }
            .lazy-load {
                transition: opacity 0.3s ease-in-out;
            }
            .lazy-loading {
                opacity: 0.6;
            }
            .lazy-loaded {
                opacity: 1;
            }
        </style>';
    }
    
    /**
     * AJAX load content
     */
    public function ajax_load_content(): void {
        check_ajax_referer('tmu_lazy_load', 'nonce');
        
        $content_id = sanitize_text_field($_POST['content_id'] ?? '');
        $content_type = sanitize_text_field($_POST['content_type'] ?? '');
        
        if (empty($content_id) || empty($content_type)) {
            wp_die();
        }
        
        $content = '';
        
        switch ($content_type) {
            case 'comments':
                $content = $this->load_comments_content($content_id);
                break;
            case 'related_posts':
                $content = $this->load_related_posts_content($content_id);
                break;
            case 'widget':
                $content = $this->load_widget_content($content_id);
                break;
        }
        
        wp_send_json_success(['content' => $content]);
    }
    
    /**
     * Load comments content
     */
    private function load_comments_content($post_id): string {
        if (!comments_open($post_id)) {
            return '';
        }
        
        ob_start();
        comments_template();
        return ob_get_clean();
    }
    
    /**
     * Load related posts content
     */
    private function load_related_posts_content($post_id): string {
        $post = get_post($post_id);
        if (!$post) {
            return '';
        }
        
        // Get related posts based on categories/tags
        $related_posts = get_posts([
            'post_type' => $post->post_type,
            'posts_per_page' => 6,
            'post__not_in' => [$post_id],
            'category__in' => wp_get_post_categories($post_id),
            'meta_query' => [
                [
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        if (empty($related_posts)) {
            return '';
        }
        
        ob_start();
        echo '<div class="related-posts-grid">';
        foreach ($related_posts as $related_post) {
            echo '<div class="related-post-item">';
            echo '<a href="' . get_permalink($related_post) . '">';
            echo get_the_post_thumbnail($related_post, 'medium');
            echo '<h4>' . get_the_title($related_post) . '</h4>';
            echo '</a>';
            echo '</div>';
        }
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Load widget content
     */
    private function load_widget_content($widget_id): string {
        // Placeholder for widget lazy loading
        return '';
    }
    
    /**
     * Register lazy loading settings
     */
    public function register_lazy_loading_settings(): void {
        register_setting('tmu_performance', 'tmu_lazy_loading_enabled', [
            'type' => 'boolean',
            'default' => true
        ]);
        
        register_setting('tmu_performance', 'tmu_lazy_placeholder_color', [
            'type' => 'string',
            'default' => '#f0f0f0'
        ]);
        
        register_setting('tmu_performance', 'tmu_lazy_loading_animation', [
            'type' => 'boolean',
            'default' => true
        ]);
        
        register_setting('tmu_performance', 'tmu_lazy_critical_threshold', [
            'type' => 'integer',
            'default' => 3
        ]);
        
        register_setting('tmu_performance', 'tmu_lazy_content_enabled', [
            'type' => 'boolean',
            'default' => true
        ]);
        
        register_setting('tmu_performance', 'tmu_lazy_background_enabled', [
            'type' => 'boolean',
            'default' => true
        ]);
    }
    
    /**
     * Get lazy loading statistics
     */
    public function get_lazy_loading_stats(): array {
        return [
            'enabled' => $this->lazy_loading_enabled,
            'critical_threshold' => $this->critical_images_count,
            'images_processed' => $this->get_images_processed_count(),
            'bandwidth_saved' => $this->calculate_bandwidth_saved(),
            'settings' => $this->settings
        ];
    }
    
    /**
     * Get images processed count
     */
    private function get_images_processed_count(): int {
        // This would track how many images have been processed
        // Placeholder for actual implementation
        return 0;
    }
    
    /**
     * Calculate bandwidth saved
     */
    private function calculate_bandwidth_saved(): float {
        // This would calculate bandwidth savings from lazy loading
        // Placeholder for actual implementation
        return 0.0;
    }
    
    /**
     * Is lazy loading enabled
     */
    public function is_enabled(): bool {
        return $this->lazy_loading_enabled;
    }
    
    /**
     * Get settings
     */
    public function get_settings(): array {
        return $this->settings;
    }
}