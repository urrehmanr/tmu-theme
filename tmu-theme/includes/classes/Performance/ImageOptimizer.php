<?php
/**
 * Image Optimizer - Image Performance Optimization
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
 * Advanced Image Optimization System
 */
class ImageOptimizer {
    
    /**
     * Supported image formats
     */
    private array $supported_formats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    /**
     * WebP quality
     */
    private int $webp_quality = 80;
    
    /**
     * JPEG quality
     */
    private int $jpeg_quality = 85;
    
    /**
     * PNG compression level
     */
    private int $png_compression = 9;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize image optimizer
     */
    public function init(): void {
        // Image optimization hooks
        add_filter('wp_generate_attachment_metadata', [$this, 'generate_optimized_images'], 10, 2);
        add_filter('wp_get_attachment_image_attributes', [$this, 'add_lazy_loading_attributes'], 10, 3);
        add_filter('the_content', [$this, 'optimize_content_images']);
        
        // WebP support
        add_filter('wp_get_attachment_url', [$this, 'serve_webp_images'], 10, 2);
        add_filter('wp_get_attachment_image_src', [$this, 'serve_webp_image_src'], 10, 4);
        
        // TMDB image optimization
        add_filter('tmu_tmdb_image_url', [$this, 'optimize_tmdb_image_url'], 10, 3);
        add_filter('tmu_poster_image_url', [$this, 'optimize_poster_image']);
        add_filter('tmu_backdrop_image_url', [$this, 'optimize_backdrop_image']);
        
        // Admin optimization tools
        add_action('admin_post_tmu_optimize_images', [$this, 'admin_optimize_images']);
        add_action('wp_ajax_tmu_image_stats', [$this, 'get_image_stats']);
        
        // Responsive images
        add_filter('wp_calculate_image_srcset', [$this, 'optimize_responsive_images'], 10, 5);
        
        // Critical image preloading
        add_action('wp_head', [$this, 'preload_critical_images'], 1);
        
        // Image cleanup
        add_action('delete_attachment', [$this, 'cleanup_optimized_images']);
    }
    
    /**
     * Generate optimized images
     */
    public function generate_optimized_images($metadata, $attachment_id): array {
        if (!$metadata || !isset($metadata['file'])) {
            return $metadata;
        }
        
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/' . $metadata['file'];
        
        if (!file_exists($file_path)) {
            return $metadata;
        }
        
        $file_type = wp_check_filetype($file_path);
        $extension = strtolower($file_type['ext']);
        
        if (!in_array($extension, $this->supported_formats)) {
            return $metadata;
        }
        
        // Generate WebP version
        $this->generate_webp_version($file_path, $metadata);
        
        // Optimize original image
        $this->optimize_image($file_path, $extension);
        
        // Generate optimized thumbnail sizes
        if (isset($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size_name => $size_data) {
                $size_file_path = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/' . $size_data['file'];
                
                if (file_exists($size_file_path)) {
                    $this->optimize_image($size_file_path, $extension);
                    $this->generate_webp_version($size_file_path, $metadata, $size_name);
                }
            }
        }
        
        return $metadata;
    }
    
    /**
     * Generate WebP version
     */
    private function generate_webp_version($file_path, $metadata, $size_name = null): bool {
        if (!function_exists('imagewebp')) {
            return false;
        }
        
        $path_info = pathinfo($file_path);
        $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
        
        // Skip if WebP already exists and is newer
        if (file_exists($webp_path) && filemtime($webp_path) >= filemtime($file_path)) {
            return true;
        }
        
        $image = $this->create_image_resource($file_path);
        
        if (!$image) {
            return false;
        }
        
        // Apply optimization before converting to WebP
        $image = $this->apply_image_optimizations($image);
        
        $success = imagewebp($image, $webp_path, $this->webp_quality);
        imagedestroy($image);
        
        if ($success) {
            // Store WebP info in metadata
            if ($size_name) {
                $metadata['sizes'][$size_name]['webp'] = basename($webp_path);
            } else {
                $metadata['webp'] = basename($webp_path);
            }
            
            // Set proper file permissions
            chmod($webp_path, 0644);
        }
        
        return $success;
    }
    
    /**
     * Create image resource
     */
    private function create_image_resource($file_path) {
        $file_type = wp_check_filetype($file_path);
        $extension = strtolower($file_type['ext']);
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagecreatefromjpeg($file_path);
            case 'png':
                return imagecreatefrompng($file_path);
            case 'gif':
                return imagecreatefromgif($file_path);
            case 'webp':
                return imagecreatefromwebp($file_path);
            default:
                return false;
        }
    }
    
    /**
     * Apply image optimizations
     */
    private function apply_image_optimizations($image) {
        if (!$image) {
            return $image;
        }
        
        // Apply sharpening filter
        if (function_exists('imagefilter')) {
            imagefilter($image, IMG_FILTER_CONTRAST, -5);
            imagefilter($image, IMG_FILTER_BRIGHTNESS, 5);
        }
        
        return $image;
    }
    
    /**
     * Optimize image
     */
    private function optimize_image($file_path, $extension): bool {
        if (!file_exists($file_path)) {
            return false;
        }
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return $this->optimize_jpeg($file_path);
            case 'png':
                return $this->optimize_png($file_path);
            case 'gif':
                return $this->optimize_gif($file_path);
            default:
                return false;
        }
    }
    
    /**
     * Optimize JPEG
     */
    private function optimize_jpeg($file_path): bool {
        $image = imagecreatefromjpeg($file_path);
        
        if (!$image) {
            return false;
        }
        
        // Apply optimizations
        $image = $this->apply_image_optimizations($image);
        
        // Save with optimization
        $success = imagejpeg($image, $file_path, $this->jpeg_quality);
        imagedestroy($image);
        
        return $success;
    }
    
    /**
     * Optimize PNG
     */
    private function optimize_png($file_path): bool {
        $image = imagecreatefrompng($file_path);
        
        if (!$image) {
            return false;
        }
        
        // Preserve transparency
        imagealphablending($image, false);
        imagesavealpha($image, true);
        
        // Apply optimizations
        $image = $this->apply_image_optimizations($image);
        
        // Save with compression
        $success = imagepng($image, $file_path, $this->png_compression);
        imagedestroy($image);
        
        return $success;
    }
    
    /**
     * Optimize GIF
     */
    private function optimize_gif($file_path): bool {
        // GIF optimization is limited in PHP
        // For production, consider using external tools like gifsicle
        return true;
    }
    
    /**
     * Add lazy loading attributes
     */
    public function add_lazy_loading_attributes($attr, $attachment, $size): array {
        if (is_admin() || wp_is_json_request()) {
            return $attr;
        }
        
        // Skip if lazy loading is disabled
        if (isset($attr['loading']) && $attr['loading'] === 'eager') {
            return $attr;
        }
        
        // Add lazy loading
        $attr['loading'] = 'lazy';
        
        // Add data attributes for advanced lazy loading
        if (isset($attr['src'])) {
            $attr['data-src'] = $attr['src'];
            $attr['src'] = $this->get_placeholder_image($attr['width'] ?? 300, $attr['height'] ?? 200);
        }
        
        if (isset($attr['srcset'])) {
            $attr['data-srcset'] = $attr['srcset'];
            unset($attr['srcset']);
        }
        
        if (isset($attr['sizes'])) {
            $attr['data-sizes'] = $attr['sizes'];
            unset($attr['sizes']);
        }
        
        // Add lazy loading class
        $attr['class'] = ($attr['class'] ?? '') . ' lazy-load';
        
        return $attr;
    }
    
    /**
     * Get placeholder image
     */
    private function get_placeholder_image($width, $height): string {
        // Generate SVG placeholder
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '">';
        $svg .= '<rect width="100%" height="100%" fill="#f0f0f0"/>';
        $svg .= '<text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#999" font-family="sans-serif" font-size="14">';
        $svg .= 'Loading...';
        $svg .= '</text>';
        $svg .= '</svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    /**
     * Optimize content images
     */
    public function optimize_content_images($content): string {
        // Add lazy loading to images in content
        $content = preg_replace_callback(
            '/<img([^>]+)>/i',
            [$this, 'optimize_content_image_callback'],
            $content
        );
        
        return $content;
    }
    
    /**
     * Optimize content image callback
     */
    private function optimize_content_image_callback($matches): string {
        $img_tag = $matches[0];
        $attributes = $matches[1];
        
        // Skip if already optimized
        if (strpos($attributes, 'data-src') !== false) {
            return $img_tag;
        }
        
        // Extract src attribute
        if (preg_match('/src=["\']([^"\']+)["\']/', $attributes, $src_matches)) {
            $original_src = $src_matches[1];
            
            // Try to serve WebP version
            $webp_src = $this->get_webp_version($original_src);
            
            if ($webp_src && $this->browser_supports_webp()) {
                $attributes = str_replace($original_src, $webp_src, $attributes);
            }
            
            // Add lazy loading
            $placeholder = $this->get_placeholder_image(300, 200);
            $attributes = str_replace('src="' . $original_src . '"', 'src="' . $placeholder . '" data-src="' . $original_src . '"', $attributes);
            
            // Add lazy loading class
            if (strpos($attributes, 'class=') !== false) {
                            $attributes = preg_replace('/class=["\']([^"\']*)["\']/', 'class="$1 lazy-load"', $attributes);
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
     * Serve WebP images
     */
    public function serve_webp_images($url, $attachment_id): string {
        if (!$this->browser_supports_webp()) {
            return $url;
        }
        
        $webp_url = $this->get_webp_version($url);
        
        return $webp_url ?: $url;
    }
    
    /**
     * Serve WebP image src
     */
    public function serve_webp_image_src($image, $attachment_id, $size, $icon): array {
        if (!$this->browser_supports_webp() || !$image) {
            return $image;
        }
        
        $webp_url = $this->get_webp_version($image[0]);
        
        if ($webp_url) {
            $image[0] = $webp_url;
        }
        
        return $image;
    }
    
    /**
     * Get WebP version of image
     */
    private function get_webp_version($url): ?string {
        // Extract file path from URL
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        
        if (strpos($url, $base_url) !== 0) {
            return null;
        }
        
        $file_path = str_replace($base_url, $upload_dir['basedir'], $url);
        $path_info = pathinfo($file_path);
        $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
        
        if (file_exists($webp_path)) {
            return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $webp_path);
        }
        
        return null;
    }
    
    /**
     * Check if browser supports WebP
     */
    private function browser_supports_webp(): bool {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        
        return strpos($accept, 'image/webp') !== false;
    }
    
    /**
     * Optimize TMDB image URL
     */
    public function optimize_tmdb_image_url($url, $size, $type): string {
        // Map WordPress sizes to TMDB sizes
        $size_map = [
            'thumbnail' => 'w154',
            'medium' => 'w342',
            'medium_large' => 'w500',
            'large' => 'w780',
            'full' => 'original'
        ];
        
        $tmdb_size = $size_map[$size] ?? 'w342';
        
        // Replace size in TMDB URL
        $optimized_url = preg_replace('/\/w\d+\//', "/{$tmdb_size}/", $url);
        
        // Add optimization parameters
        $optimized_url = add_query_arg([
            'quality' => $this->webp_quality,
            'format' => $this->browser_supports_webp() ? 'webp' : 'jpg'
        ], $optimized_url);
        
        return $optimized_url;
    }
    
    /**
     * Optimize poster image
     */
    public function optimize_poster_image($url): string {
        return $this->optimize_tmdb_image_url($url, 'medium', 'poster');
    }
    
    /**
     * Optimize backdrop image
     */
    public function optimize_backdrop_image($url): string {
        return $this->optimize_tmdb_image_url($url, 'large', 'backdrop');
    }
    
    /**
     * Optimize responsive images
     */
    public function optimize_responsive_images($sources, $size_array, $image_src, $image_meta, $attachment_id): array {
        if (!$this->browser_supports_webp()) {
            return $sources;
        }
        
        foreach ($sources as $width => $source) {
            $webp_url = $this->get_webp_version($source['url']);
            
            if ($webp_url) {
                $sources[$width]['url'] = $webp_url;
            }
        }
        
        return $sources;
    }
    
    /**
     * Preload critical images
     */
    public function preload_critical_images(): void {
        if (is_admin()) {
            return;
        }
        
        $critical_images = [];
        
        // Preload hero/featured images
        if (is_single() || is_page()) {
            $featured_image = get_the_post_thumbnail_url(get_the_ID(), 'large');
            
            if ($featured_image) {
                $critical_images[] = $featured_image;
            }
        }
        
        // Preload site logo
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'medium');
            if ($logo_url) {
                $critical_images[] = $logo_url;
            }
        }
        
        // Output preload links
        foreach ($critical_images as $image_url) {
            $webp_url = $this->get_webp_version($image_url);
            $preload_url = $webp_url && $this->browser_supports_webp() ? $webp_url : $image_url;
            
            echo '<link rel="preload" as="image" href="' . esc_url($preload_url) . '">' . "\n";
        }
    }
    
    /**
     * Cleanup optimized images
     */
    public function cleanup_optimized_images($attachment_id): void {
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        if (!$metadata || !isset($metadata['file'])) {
            return;
        }
        
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/' . $metadata['file'];
        $path_info = pathinfo($file_path);
        
        // Delete WebP version
        $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
        if (file_exists($webp_path)) {
            unlink($webp_path);
        }
        
        // Delete WebP versions of thumbnails
        if (isset($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size_data) {
                $size_file_path = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/' . $size_data['file'];
                $size_path_info = pathinfo($size_file_path);
                $size_webp_path = $size_path_info['dirname'] . '/' . $size_path_info['filename'] . '.webp';
                
                if (file_exists($size_webp_path)) {
                    unlink($size_webp_path);
                }
            }
        }
    }
    
    /**
     * Admin optimize images
     */
    public function admin_optimize_images(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'tmu'));
        }
        
        check_admin_referer('tmu_optimize_images');
        
        // Get all images
        $images = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        
        $optimized_count = 0;
        
        foreach ($images as $image_id) {
            $metadata = wp_get_attachment_metadata($image_id);
            
            if ($metadata) {
                $this->generate_optimized_images($metadata, $image_id);
                $optimized_count++;
            }
        }
        
        wp_redirect(add_query_arg([
            'images_optimized' => '1',
            'optimized_count' => $optimized_count
        ], wp_get_referer()));
        exit;
    }
    
    /**
     * Get image statistics
     */
    public function get_image_stats(): array {
        $upload_dir = wp_upload_dir();
        $stats = [
            'total_images' => 0,
            'webp_images' => 0,
            'total_size' => 0,
            'webp_size' => 0,
            'savings' => 0
        ];
        
        // Count images
        $images = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        
        $stats['total_images'] = count($images);
        
        foreach ($images as $image_id) {
            $metadata = wp_get_attachment_metadata($image_id);
            
            if ($metadata && isset($metadata['file'])) {
                $file_path = $upload_dir['basedir'] . '/' . $metadata['file'];
                
                if (file_exists($file_path)) {
                    $file_size = filesize($file_path);
                    $stats['total_size'] += $file_size;
                    
                    // Check for WebP version
                    $webp_path = $this->get_webp_version($upload_dir['baseurl'] . '/' . $metadata['file']);
                    
                    if ($webp_path) {
                        $webp_file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $webp_path);
                        
                        if (file_exists($webp_file_path)) {
                            $stats['webp_images']++;
                            $webp_size = filesize($webp_file_path);
                            $stats['webp_size'] += $webp_size;
                            $stats['savings'] += ($file_size - $webp_size);
                        }
                    }
                }
            }
        }
        
        return $stats;
    }
}